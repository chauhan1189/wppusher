<?php
$file_upload_max_size = get_option('print_products_file_upload_max_size');
$file_upload_target = get_option("print_products_file_upload_target");
$amazon_s3_settings = get_option("print_products_amazon_s3_settings");

if (!$file_upload_max_size) { $file_upload_max_size = 2; }

$upload_to = 'host';
$uploader_url = get_bloginfo('url').'/index.php?ajaxupload=artwork';
if ($file_upload_target == 'amazon' && $amazon_s3_settings['s3_access_key'] && $amazon_s3_settings['s3_secret_key']) {
	$upload_to = 'amazon';
	$s3_data = print_products_aec_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size);
	$uploader_url = $s3_data['amazon_url'];
	$amazon_file_url = $s3_data['amazon_file_url'];
}
?>
	<script type="text/javascript">var platform = false;</script>
	<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/pdf-parser-bwc.js?ver=1.0.14" data-optimized="0" data-no-optimize="1" data-no-defer="1"></script>
	<script type="text/javascript" src="https://wp2printapp.s3.amazonaws.com/20170919/universaluploader.gzip.js" data-optimized="0" data-no-optimize="1" data-no-defer="1"></script>
	<script type="text/javascript" src="https://wp2printapp.s3.amazonaws.com/20170919/language_en.js" data-optimized="0" data-no-optimize="1" data-no-defer="1"></script>
	<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/platform.js" data-optimized="0" data-no-optimize="1" data-no-defer="1"></script>
	<script type="text/javascript">
	var parsednmb = 0;
	var amazon_file_url = '<?php echo $amazon_file_url; ?>';
	var uploader_message = aec_uploader_message();
	if (uploader_message == 0 || uploader_message == 32) {
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
			jQuery('.add-cart-form .simple-add-btn').hide();
			jQuery('.upload-pdf-processing').show();

			files_number1 = universalUploader.Html5.files.length;
			jQuery('.upload-pdf-processing ul li.tx').remove();
			pdf_parsing_process();
		});
		universalUploader.bindEventListener("UploadComplete", function (uploaderId, file){
			not_uploaded = false;
			if (autosubmit) {
				jQuery('form.add-cart-form').submit();
				autosubmit = false;
			}
		});
		universalUploader.bindEventListener("FileUploadComplete", function (uploaderId, file, response){
			<?php if ($upload_to == 'amazon') { ?>
				var ufileurl = amazon_file_url + file.name;
			<?php } else { ?>
				var ufileurl = response;
			<?php } ?>
			if (ufileurl != '') {
				var artworkfiles = jQuery('.add-cart-form .artwork-files').val();
				if (artworkfiles != '') { artworkfiles += ';'; }
				artworkfiles += ufileurl;
				jQuery('.add-cart-form .artwork-files').val(artworkfiles);
			}
		});
		universalUploader.bindEventListener("FilesRemoved", function (uploaderId, files){
			display_totals(0, 0, 0, 0, 0, 0, 0, '');
			jQuery('.low-cost-options-box').hide();
			jQuery('.add-cart-form .artwork-files').val('');
			jQuery('.add-cart-form .simple-add-btn').hide();
			stable = new Array();
			stable_res = new Array();
			parsednmb = 0;
			files_cur = 0;
			files_cur1 = 0;
			files_number = 0;
		});
	}
	if (uploader_message != 0) {
		if (uploader_message == 'ie' || uploader_message == 'safari') {
			jQuery('#universalUploader_holder').hide();
		}
		jQuery('.uploader-warn-message .warn-message-'+uploader_message).show();
		jQuery('.uploader-warn-message').show();
	}
	function pdf_parsing_process() {
		var ftotal = universalUploader.Html5.files.length;
		if (parsednmb < ftotal) {
			var file = universalUploader.Html5.files[parsednmb];
			jQuery('.upload-pdf-processing ul').append('<li class="tx afile-'+parsednmb+'" style="display:none;"><?php _e('Analyzing file', 'wp2print'); ?>: '+file.name+' (<?php _e('processing page', 'wp2print'); ?> <span class="pp-cp">1</span> <?php _e('of', 'wp2print'); ?> <span class="pp-tp">1</span>)</li>');
			parse_pdf_file(file);
			parsednmb++;
		} else {
			pdf_parsing_complete();
		}
	}
	function pdf_parsing_complete() {
		jQuery('.upload-pdf-processing').hide();
		jQuery('.low-cost-options-box').show();
		jQuery('.add-cart-form .simple-add-btn').show();
	}
	function show_pdf_results_table() {
		jQuery.colorbox({inline:true, href:"#pdf-results-table"});
	}
	function close_lco_table() {
		jQuery.colorbox.close();
	}
	function aec_uploader_message() {
		if (platform) {
			var bname = platform.name;
			if (bname == 'IE') {
				var bver = parseFloat(platform.version);
				if (bver < 12) {
					return 'ie';
				}
			} else if (bname == 'Safari') {
				return 'safari';
			} else if (bname == 'Firefox' || bname == 'Chrome') {
				var osbit = platform.os.architecture + '';
				if (platform.os.family == 'OS X') { osbit = '64'; }
				if (osbit == '32') {
					return 32;
				}
			}
		}
		return 0;
	}
	</script>
<div style="display:none;">
	<div id="pdf-results-table" class="pdf-results-box">
		<div class="pdf-results-table">
			<table id="dialog_table">
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
			<div class="pdf-results-close">
				<input type="button" value="<?php _e('Close', 'wp2print'); ?>" onclick="close_lco_table()">
			</div>
		</div>
	</div>
</div>