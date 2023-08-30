<?php
$file_upload_max_size = get_option('print_products_file_upload_max_size');
$file_upload_target = get_option("print_products_file_upload_target");
$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
$material_attribute = $print_products_settings['material_attribute'];

$material_label = '';
$material_slug = '';
$material_attribute_row = $wpdb->get_row(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies WHERE attribute_id = %s", $wpdb->prefix, $material_attribute));
if ($material_attribute_row) {
	$material_label = $material_attribute_row->attribute_label;
	$material_slug = $material_attribute_row->attribute_name;
}

if (!$file_upload_max_size) { $file_upload_max_size = 2; }

$upload_to = 'host';
$uploader_url = get_bloginfo('url').'/index.php?ajaxupload=artwork';
if ($file_upload_target == 'amazon' && $amazon_s3_settings['s3_access_key'] && $amazon_s3_settings['s3_secret_key']) {
	$upload_to = 'amazon';
	$s3_data = print_products_aec_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size);
	$uploader_url = $s3_data['amazon_url'];
	$amazon_file_url = $s3_data['amazon_file_url'];
}

$aec_sizes = print_products_get_aec_sizes();
$dimension_unit = print_products_get_aec_dimension_unit();
$area_square_unit = print_products_get_area_square_unit($dimension_unit);
$aec_enable_size = (int)$print_products_plugin_aec['aec_enable_size'];

$products_types = array();
$products_material_terms = array();
$apply_round_up_vals = array();
$inc_coverage_prices_vals = array();
$round_up_discounts_vals = array();

$args = array('post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array('aec', 'aecbwc', 'aecsimple'))), 'orderby' => 'title', 'order' => 'asc');
$aecproducts = get_posts($args);
if ($aecproducts) {
	foreach($aecproducts as $aecproduct) {
		$product_type_matrix_type = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE mtype = 0 AND product_id = %s ORDER BY mtype, sorder LIMIT 0, 1", $wpdb->prefix, $aecproduct->ID));
		if ($product_type_matrix_type) {
			$mtype_id = $product_type_matrix_type->mtype_id;
			$aterms = unserialize($product_type_matrix_type->aterms);
			$products_material_terms[$aecproduct->ID] = $aterms[$material_attribute];
		}

		$products_types[$aecproduct->ID] = print_products_get_type($aecproduct->ID);
		$apply_round_up_vals[$aecproduct->ID] = (int)get_post_meta($aecproduct->ID, '_apply_round_up', true);
		$inc_coverage_prices_vals[$aecproduct->ID] = (array)get_post_meta($aecproduct->ID, '_inc_coverage_prices', true);
		$round_up_discounts_vals[$aecproduct->ID] = (array)get_post_meta($aecproduct->ID, '_round_up_discounts', true);
	}
}
$attribute_names = array();
$material_attr_terms = $wpdb->get_results(sprintf("SELECT t.*, tt.taxonomy FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'pa_%s' ORDER BY t.term_order, t.name", $wpdb->prefix, $wpdb->prefix, $material_slug));
if ($material_attr_terms) {
	foreach($material_attr_terms as $material_attr_term) {
		$attribute_names[$material_attr_term->term_id] = $material_attr_term->name;
	}
}
$mstyle = '';
?>
<div class="wrap wp2print-wrap wp2print-aec-orders-wrap">
	<h2><?php _e('Add RapidQuote', 'wp2print'); ?></h2>
	<form method="POST" class="ao-form" onsubmit="return aec_order_submit();">
		<input type="hidden" name="print_products_aec_orders_action" value="create">
		<div class="aec-order-wrap">
			<div class="aec-order-box">
				<div class="aec-left-fields">
					<div class="fields-box">
						<?php $wpusers = get_users();
						if ($wpusers) { ?>
							<p class="form-field">
								<label><?php _e('Customer', 'wp2print'); ?>:</label>
								<select name="ao_user" class="ao-user" onchange="aec_order_check()">
									<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
									<?php foreach($wpusers as $wpuser) { ?>
										<option value="<?php echo $wpuser->ID; ?>"><?php echo $wpuser->display_name; ?></option>
									<?php } ?>
								</select>
							</p>
						<?php } ?>
						<?php if ($aecproducts) { ?>
							<p class="form-field">
								<label><?php _e('AEC Product', 'wp2print'); ?>:</label>
								<select name="ao_product" class="ao-product" onchange="aec_order_product_change();">
									<?php foreach($aecproducts as $aecproduct) { ?>
										<option value="<?php echo $aecproduct->ID; ?>"><?php echo $aecproduct->post_title; ?></option>
									<?php } ?>
								</select>
							</p>
						<?php } ?>
						<p class="form-field">
							<label><?php _e('Quantity', 'wp2print'); ?>:</label>
							<input type="text" name="ao_qty" value="1" class="ao-qty" id="qty" onblur="aec_order_check()">
						</p>
						<p class="form-field material-field">
							<label><?php _e($material_label, 'wp2print'); ?>:</label>
							<?php foreach($products_material_terms as $pid => $product_material_terms) { ?>
								<select name="ao_material[<?php echo $pid; ?>]" class="ao-material ao-material-<?php echo $pid; ?>" data-aid="<?php echo $material_attribute; ?>" onchange="aec_order_product_change();"<?php echo $mstyle; ?>>
									<?php foreach($product_material_terms as $product_material_term) { ?>
										<option value="<?php echo $product_material_term; ?>"><?php echo $attribute_names[$product_material_term]; ?></option>
									<?php } ?>
								</select>
							<?php $mstyle = ' style="display:none;"'; } ?>
						</p>
						<p class="form-field">
							<label><?php _e('Project name', 'wp2print'); ?>:</label>
							<input type="text" name="ao_project_name" class="ao-project-name" onkeyup="aec_order_check()" onblur="aec_order_check()">
						</p>
						<p class="form-field">
							<label><?php _e('Email Subject', 'wp2print'); ?>:</label>
							<input type="text" name="ao_email_subject" class="ao-email-subject" value="<?php echo $print_products_plugin_aec['order_email_subject']; ?>">
						</p>
						<p class="form-field">
							<label><?php _e('Email Message', 'wp2print'); ?>:</label>
							<textarea name="ao_email_message" class="ao-email-message" style="width:100%;height:150px;"><?php echo $print_products_plugin_aec['order_email_message']; ?></textarea><br />
							<?php _e('Use', 'wp2print'); ?>: {PAGE-DETAIL-MATRIX}, {TOTAL-PRICE}, {PAY-NOW-LINK}, {PROJECT-NAME}
						</p>
					</div>
				</div>
				<div class="aec-right-fields">
					<div class="uploader-box">
						<label><?php _e('Files', 'wp2print'); ?>:</label>
						<div id="universalUploader_holder" class="uploader-holder">
							<noscript>Please enable JavaScript.</noscript>
						</div>
						<div class="upload-pdf-processing" style="display:none;"><div class="prtext"><ul><li class="tl"><?php _e('Processing...', 'wp2print'); ?></li></ul></div></div>

						<p class="form-field low-cost-options-box" style="display:none;">
							<input type="button" onclick="show_pdf_results_table();" value="<?php _e('Low-cost options', 'wp2print'); ?>">
						</p>
						<p class="form-field">
							<label class="aec-total-area"><?php _e('Total Area', 'wp2print'); ?>: <span>0</span> <?php echo $dimension_unit; ?><sup>2</sup></label>
							<label class="aec-total-pages"><?php _e('Total Pages', 'wp2print'); ?>: <span>0</span></label>
							<label class="aecbwc-totals aec-area-bw" style="display:none;"><?php _e('Area B/W', 'wp2print'); ?>:&nbsp;<span>0</span>&nbsp;<?php echo $dimension_unit; ?><sup>2</sup></label>
							<label class="aecbwc-totals aec-pages-bw" style="display:none;"><?php _e('Pages B/W', 'wp2print'); ?>:&nbsp;<span>0</span></label>
							<label class="aecbwc-totals aec-area-cl" style="display:none;"><?php _e('Area Color', 'wp2print'); ?>:&nbsp;<span>0</span>&nbsp;<?php echo $dimension_unit; ?><sup>2</sup></label>
							<label class="aecbwc-totals aec-pages-cl" style="display:none;"><?php _e('Pages Color', 'wp2print'); ?>:&nbsp;<span>0</span></label>
							<label class="price-label"><?php _e('Price', 'wp2print'); ?>: <span class="pprice">0</span></label>
						</p>
						<input type="submit" value="<?php _e('Send RapidQuote', 'wp2print'); ?>" class="button button-primary create-order-btn" style="display:none;">
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="ao_smparams" class="ao-smparams">
		<input type="hidden" name="ao_artworkfiles" class="artwork-files">
		<input type="hidden" name="ao_total_price" class="aec-total-price">
		<input type="hidden" name="ao_total_area" class="aec-total-area">
		<input type="hidden" name="ao_total_pages" class="aec-total-pages">
		<input type="hidden" name="ao_area_bw" class="aec-area-bw">
		<input type="hidden" name="ao_pages_bw" class="aec-pages-bw">
		<input type="hidden" name="ao_area_cl" class="aec-area-cl">
		<input type="hidden" name="ao_pages_cl" class="aec-pages-cl">
		<input type="hidden" name="ao_table_values" class="aec-table-values">
	</form>
	<div style="display:none;">
		<div id="pdf-results-table" class="pdf-results-box">
			<div class="pdf-results-table">
				<table id="dialog_table_aec" style="width:100%;display:none;">
					<thead>
						<tr>
							<th style="text-align:left;"><?php _e('File Name', 'wp2print'); ?></th>
							<th style="text-align:center"><?php _e('Page', 'wp2print'); ?></th>
							<th style="text-align:center"><?php _e('% Coverage', 'wp2print'); ?></th>
							<?php if ($aec_enable_size) { ?><th style="text-align:right"><?php _e('Print size', 'wp2print'); ?></th><?php } ?>
							<th style="text-align:right"><?php if ($apply_round_up) { _e('Rounded Area', 'wp2print'); } else { _e('Printed Area', 'wp2print'); } ?> (<?php echo $dimension_unit; ?><sup>2</sup>)</th>
							<th style="text-align:right"><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit; ?><sup>2</sup></th>
							<th style="text-align:right"><?php _e('Price', 'wp2print'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<table id="dialog_table_aecbwc" style="width:100%;display:none;">
					<thead>
						<tr>
							<th style="text-align:left;"><?php _e('File Name', 'wp2print'); ?></th>
							<th style="text-align:center"><?php _e('Page', 'wp2print'); ?></th>
							<th style="text-align:center"><?php _e('Original color', 'wp2print'); ?></th>
							<th style="text-align:center"><?php _e('Original size', 'wp2print'); ?></th>
							<?php if ($aec_enable_size) { ?>
							<th><?php _e('Convert', 'wp2print'); ?></th>
							<th><?php _e('Print size', 'wp2print'); ?></th>
							<?php } ?>
							<th style="text-align:right"><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit; ?><sup>2</sup></th>
							<th style="text-align:right"><?php _e('Price', 'wp2print'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<table id="dialog_table_aecsimple" style="width:100%;display:none;">
					<thead>
						<tr>
							<th style="text-align:left;"><?php _e('File Name', 'wp2print'); ?></th>
							<th style="text-align:center"><?php _e('Page', 'wp2print'); ?></th>
							<?php if ($aec_enable_size) { ?><th style="text-align:right"><?php _e('Print size', 'wp2print'); ?></th><?php } ?>
							<th style="text-align:right"><?php if ($apply_round_up) { _e('Rounded Area', 'wp2print'); } else { _e('Printed Area', 'wp2print'); } ?> (<?php echo $dimension_unit; ?><sup>2</sup>)</th>
							<th style="text-align:right"><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit; ?><sup>2</sup></th>
							<th style="text-align:right"><?php _e('Price', 'wp2print'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<div class="pdf-results-close">
					<input type="button" value="<?php _e('Close', 'wp2print'); ?>" onclick="close_lco_table()">
				</div>
			</div>
		</div>
	</div>
</div>
<script>
var not_uploaded = false;
var autosubmit = false;
var aec_total_area = 0;
var aec_enable_size = <?php echo $aec_enable_size; ?>;
var price_decimals = <?php echo wc_get_price_decimals(); ?>;
var global_area_display_units = '<?php echo $dimension_unit; ?>';
var global_width_measure = '<?php echo $area_square_unit; ?>';
var button_browse_text = '<?php _e('Select files', 'wp2print'); ?>';
var button_upload_text = '<?php _e('Upload files', 'wp2print'); ?>';
var button_cancel_text = '<?php _e('Cancel', 'wp2print'); ?>';
var button_clear_text = '<?php _e('Clear', 'wp2print'); ?>';
var amazon_file_url = '<?php echo $amazon_file_url; ?>';
var iurl = '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/';

var prod_types = new Array();
<?php foreach($products_types as $pt_pid => $pt_val) { ?>
prod_types[<?php echo $pt_pid; ?>] = '<?php echo $pt_val; ?>';
<?php } ?>

var apply_round_up = new Array();
<?php foreach($apply_round_up_vals as $aru_pid => $aru_val) { ?>
apply_round_up[<?php echo $aru_pid; ?>] = <?php if ($aru_val == 1) { echo 'true'; } else { echo 'false'; } ?>;
<?php } ?>

<?php $aec_coverage_ranges = print_products_get_aec_coverage_ranges(); ?>
var coverage_ranges = [<?php echo implode(', ', $aec_coverage_ranges); ?>];

var inc_coverage_prices = new Array();
var inc_coverage_prices_b = new Array();
var inc_coverage_prices_c = new Array();
<?php foreach($inc_coverage_prices_vals as $pid => $inc_coverage_prices) { ?>
	<?php if ($products_types[$pid] == 'aecbwc') { ?>
		inc_coverage_prices_b[<?php echo $pid; ?>] = new Array();
		inc_coverage_prices_c[<?php echo $pid; ?>] = new Array();
		<?php foreach($inc_coverage_prices[0] as $mid => $pprice) { ?>
			inc_coverage_prices_b[<?php echo $pid; ?>]['<?php echo $mid; ?>'] = <?php echo (float)$pprice; ?>;
		<?php } ?>
		<?php foreach($inc_coverage_prices[1] as $mid => $pprice) { ?>
			inc_coverage_prices_c[<?php echo $pid; ?>]['<?php echo $mid; ?>'] = <?php echo (float)$pprice; ?>;
		<?php } ?>
	<?php } else if ($products_types[$pid] == 'aecsimple') { ?>
		inc_coverage_prices[<?php echo $pid; ?>] = new Array();
		<?php foreach($inc_coverage_prices as $mid => $pprice) { ?>
			inc_coverage_prices[<?php echo $pid; ?>]['<?php echo $mid; ?>'] = <?php echo (float)$pprice; ?>;
		<?php } ?>
	<?php } else { ?>
		inc_coverage_prices[<?php echo $pid; ?>] = new Array();
		<?php foreach($inc_coverage_prices as $mid => $percprices) {
			foreach($percprices as $pnum => $pprice) { ?>
				inc_coverage_prices[<?php echo $pid; ?>]['<?php echo $mid; ?>-<?php echo $pnum; ?>'] = <?php echo (float)$pprice; ?>;
		<?php }} ?>
	<?php } ?>
<?php } ?>

var round_up_discounts = new Array();
round_up_discounts[0] = 0;
<?php foreach($round_up_discounts_vals as $pid => $round_up_discounts) { ?>
	round_up_discounts[<?php echo $pid; ?>] = new Array();
	<?php foreach($round_up_discounts as $mnum => $round_up_discount_price) { ?>
round_up_discounts[<?php echo $pid; ?>][<?php echo $mnum; ?>] = <?php echo (float)$round_up_discount_price; ?>;
	<?php } ?>
<?php } ?>

var print_color_array = new Array();
print_color_array[0] = new Object();
print_color_array[0].value = 'color';
print_color_array[0].content = '<?php _e('Print in color', 'wp2print'); ?>';
print_color_array[1] = new Object();
print_color_array[1].value = 'bw';
print_color_array[1].content = '<?php _e('Print in B/W', 'wp2print'); ?>';

var color_array = new Array();
<?php $saind = 0; ?>
<?php foreach($aec_sizes as $sval => $sname) { ?>
color_array[<?php echo $saind; ?>] = new Object();
color_array[<?php echo $saind; ?>].value = <?php echo $sval; ?>;
color_array[<?php echo $saind; ?>].content = '<?php echo $sname; ?>';
<?php $saind++; } ?>
</script>
<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/pdf-parser-admin.js?ver=1.0.15"></script>
<script type="text/javascript" src="https://wp2printapp.s3.amazonaws.com/20170919/universaluploader.gzip.js"></script>
<script type="text/javascript" src="https://wp2printapp.s3.amazonaws.com/20170919/language_en.js"></script>
<script type="text/javascript">
var files_added = false;
var parsednmb = 0;
var amazon_file_url = '<?php echo $amazon_file_url; ?>';

universalUploader.init({
	serialNumber: "0081831741461771532272179149278921871210198",
	uploaders: "drag-and-drop",
	singleUploader : true,
	renderTabHeader: false,
	fileFilter_types:'pdf',
	width: '100%',
	height: '300',
	holder: 'universalUploader_holder',
	imagesPath : 'https://d207ec9xtrf2ml.cloudfront.net/jscripts/multipow10052015/universal/images/',
	url: '<?php echo $uploader_url; ?>',
	<?php if ($upload_to == 'amazon') {
		echo $s3_data['amazonS3_params'];
	} ?>
});

universalUploader.bindEventListener("Init", function (inited){
	if(inited) {
		jQuery('#uploadButton_drag-and-drop').addClass('uuUploadButton');
		jQuery('#browseButton_drag-and-drop span span').html('<?php _e('Select files', 'wp2print'); ?>');
		jQuery('#universalUploader_holder #tabs_container').css('height', 'auto');
		jQuery('#universalUploader_holder #drag-and-drop_content').css('height', 'auto');
	} else {
		alert("UniversalUploader failed to init!");
	}
});
universalUploader.bindEventListener("FilesAdded", function (uploaderId, files){
	not_uploaded = true;
	jQuery('.low-cost-options-box').hide();
	jQuery('.create-order-btn').hide();
	jQuery('.uuUploadButton').show();
	jQuery('.upload-pdf-processing').show();

	files_number1 = universalUploader.Html5.files.length;
	jQuery('.upload-pdf-processing ul li.tx').remove();
	pdf_parsing_process();
});
universalUploader.bindEventListener("FileUploadComplete", function (uploaderId, file, response){
	<?php if ($upload_to == 'amazon') { ?>
		var ufileurl = amazon_file_url + file.name;
	<?php } else { ?>
		var ufileurl = response;
	<?php } ?>
	if (ufileurl != '') {
		var artworkfiles = jQuery('.ao-form .artwork-files').val();
		if (artworkfiles != '') { artworkfiles += ';'; }
		artworkfiles += ufileurl;
		jQuery('.ao-form .artwork-files').val(artworkfiles);
	}
	not_uploaded = false;
	aec_order_check();
});
universalUploader.bindEventListener("FilesRemoved", function (uploaderId, files){
	display_totals(0, 0, 0, '');
	jQuery('.low-cost-options-box').hide();
	jQuery('.ao-form .artwork-files').val('');
	jQuery('.uuUploadButton').hide();
	jQuery('.create-order-btn').hide();
	stable = new Array();
	stable_res = new Array();
	parsednmb = 0;
	files_cur = 0;
	files_cur1 = 0;
	files_number = 0;
	not_uploaded = false;
	files_added = false;
	aec_order_check();
});

function matrix_set_price(price) {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	if (apply_round_up[pid] == 1 && round_up_discounts[pid].length) {
		var rounded_total_area = matrix_get_area_number();
		var aec_discount_percent = round_up_discounts[pid][rounded_total_area];
		if (aec_discount_percent > 0) {
			var aec_discount_price = (price / 100) * aec_discount_percent;
			price = price - aec_discount_price;
		}
	}
	price = price.toFixed(2);
	jQuery('.price-label .pprice').html(matrix_html_price(price));
	jQuery('.ao-form .aec-total-price').val(price);
}

function matrix_set_totals(area_bw, pages_bw, area_cl, pages_cl) {
	jQuery('.aec-area-bw span').html(area_bw.toFixed(2));
	jQuery('.aec-pages-bw span').html(pages_bw);
	jQuery('.aec-area-cl span').html(area_cl.toFixed(2));
	jQuery('.aec-pages-cl span').html(pages_cl);

	jQuery('.ao-form .aec-area-bw').val(area_bw.toFixed(2));
	jQuery('.ao-form .aec-pages-bw').val(pages_bw);
	jQuery('.ao-form .aec-area-cl').val(area_cl.toFixed(2));
	jQuery('.ao-form .aec-pages-cl').val(pages_cl);
}

function matrix_set_total_area(total_area) {
	jQuery('.aec-total-area span').html(total_area.toFixed(2));
	jQuery('.ao-form .aec-total-area').val(total_area);
}

function matrix_set_total_pages(total_pages) {
	jQuery('.aec-total-pages span').html(total_pages);
	jQuery('.ao-form .aec-total-pages').val(total_pages);
}

function matrix_set_table_values(table_values) {
	jQuery('.ao-form .aec-table-values').val(table_values);
}

function matrix_html_price(price) {
	price = parseFloat(price);
	var currency_symbol = '<?php echo get_woocommerce_currency_symbol(); ?>';
	var currency_pos = '<?php echo get_option('woocommerce_currency_pos'); ?>';
	var fprice = matrix_format_price(price.toFixed(price_decimals));
	if (currency_pos == 'left') {
		return currency_symbol + fprice;
	} else if (currency_pos == 'right') {
		return fprice + currency_symbol;
	} else if (currency_pos == 'left_space') {
		return currency_symbol + ' ' + fprice;
	} else if (currency_pos == 'right_space') {
		return fprice + ' ' + currency_symbol;
	}
}

function matrix_format_price(p) {
	var decimal_sep = '<?php echo wc_get_price_decimal_separator(); ?>';
	var thousand_sep = '<?php echo wc_get_price_thousand_separator(); ?>';
	var pparts = p.toString().split('.');
	pparts[0] = pparts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand_sep);
	return pparts.join(decimal_sep);
}

function pdf_parsing_process() {
	var ftotal = universalUploader.Html5.files.length;
	if (parsednmb < ftotal) {
		var file = universalUploader.Html5.files[parsednmb];
		jQuery('.upload-pdf-processing ul').append('<li class="tx afile-'+parsednmb+'" style="display:none;"><?php _e('Analyzing file', 'wp2print'); ?>: '+file.name+' (<?php _e('processing page', 'wp2print'); ?> <span class="pp-cp">1</span> <?php _e('of', 'wp2print'); ?> <span class="pp-tp">1</span>)</li>');
		parse_pdf_file(file);
		parsednmb++;
	} else {
		files_added = true;
		pdf_parsing_complete();
	}
}
function pdf_parsing_complete() {
	jQuery('.upload-pdf-processing').hide();
	jQuery('.low-cost-options-box').show();
	aec_order_check();
}
function show_pdf_results_table() {
	var ptype = aec_get_product_type();
	jQuery('.pdf-results-table table').hide();
	if (ptype == 'aecbwc') {
		jQuery('.pdf-results-table #dialog_table_aecbwc').show();
	} else if (ptype == 'aecsimple') {
		jQuery('.pdf-results-table #dialog_table_aecsimple').show();
	} else {
		jQuery('.pdf-results-table #dialog_table_aec').show();
	}
	jQuery.colorbox({inline:true, href:"#pdf-results-table"});
}
function close_lco_table() {
	jQuery.colorbox.close();
}
function aec_get_product_type() {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	return prod_types[pid];
}
function aec_apply_round_up() {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	return apply_round_up[pid];
}
function aec_get_material() {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	return jQuery('.ao-form .ao-material-'+pid).val();
}

function aec_get_coverage_price_aec(aec_material, pnum) {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	return parseFloat(inc_coverage_prices[pid][aec_material + '-' + pnum]);
}

function aec_get_coverage_price_aecbwc(aec_material, c) {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	if (c == 'color') {
		return parseFloat(inc_coverage_prices_c[pid][aec_material]);
	} else {
		return parseFloat(inc_coverage_prices_b[pid][aec_material]);
	}
}

function aec_get_coverage_price_aecsimple(aec_material) {
	var pid = parseInt(jQuery('form.ao-form .ao-product').val());
	return parseFloat(inc_coverage_prices[pid][aec_material]);
}

function matrix_get_area_number() {
	var anumbers = [1, 10, 50, 100, 1000];
	for (var a=anumbers.length-1; a>=0; a--) {
		if (aec_total_area >= anumbers[a]) {
			return anumbers[a];
		}
	}
	return 0;
}
var aec_order_product = 0;
function aec_order_product_change() {
	var pid = jQuery('form.ao-form .ao-product').val();
	var ptype = aec_get_product_type();
	if (aec_order_product != pid) {
		jQuery('.ao-form .ao-material').hide();
		jQuery('.ao-form .ao-material-'+pid).fadeIn();
		jQuery('.ao-form .material-field').fadeIn();
	}
	jQuery('.low-cost-options-box').hide();
	if (pid && files_added) {
		if (ptype == 'aecbwc') {
			show_table_result_aecbwc();
			calculate_totals_aecbwc();
		} else {
			show_table_result_aec();
			calculate_totals_aec();
		}
		jQuery('.low-cost-options-box').show();
	}
	if (ptype == 'aecbwc') {
		jQuery('.aecbwc-totals').show();
	} else {
		jQuery('.aecbwc-totals').hide();
	}
	aec_order_check();
}
function aec_order_check() {
	var error = false;
	var ao_user = jQuery('form.ao-form .ao-user').val();
	var ao_product = jQuery('form.ao-form .ao-product').val();
	var ao_qty = parseInt(jQuery('form.ao-form .ao-qty').val());
	var ao_project_name = jQuery('form.ao-form .ao-project-name').val();

	if (ao_user == '') {
		error = true;
	}
	if (ao_product == '') {
		error = true;
	}
	if (ao_qty < 1) {
		error = true;
	}
	if (ao_project_name == '') {
		error = true;
	}
	if (!files_added) {
		error = true;
	}
	if (not_uploaded) {
		error = true;
	}
	if (error) {
		jQuery('.create-order-btn').hide();
		return false;
	} else {
		jQuery('.create-order-btn').show();
		return true;
	}
}
function aec_order_submit() {
	if (aec_order_check()) {
		var pid = jQuery('form.ao-form .ao-product').val();
		var aid = jQuery('.ao-form .ao-material-'+pid).attr('data-aid');
		var aval = jQuery('.ao-form .ao-material-'+pid).val();
		jQuery('form.ao-form .ao-smparams').val(aid+':'+aval);
		return true;
	}
	if (!files_added) {
		alert('Please select files.');
	} else if (not_uploaded) {
		alert('Please upload files.');
	} else {
		alert('Please fill field(s) correctly.');
	}
	return false;
}
jQuery(document).ready(function() {
	aec_order_product_change();
});
</script>
