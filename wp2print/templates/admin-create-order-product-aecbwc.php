<?php
global $wpdb, $print_products_settings, $attribute_names, $attribute_types, $print_products_plugin_aec;

$dimension_unit = print_products_get_aec_dimension_unit();
$area_square_unit = print_products_get_area_square_unit($dimension_unit);
$aec_sizes = print_products_get_aec_sizes();
$aec_enable_size = (int)$print_products_plugin_aec['aec_enable_size'];

$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
$inc_coverage_prices = get_post_meta($product_id, '_inc_coverage_prices', true);
$apply_round_up = (int)get_post_meta($product_id, '_apply_round_up', true);
$round_up_discounts = get_post_meta($product_id, '_round_up_discounts', true);

$size_attribute = $print_products_settings['size_attribute'];
$material_attribute = $print_products_settings['material_attribute'];
$page_count_attribute = $print_products_settings['page_count_attribute'];

$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
print_products_price_matrix_attr_names_init($attributes);

$anmb = 0;
$total_price = 0;
$total_area = 0;
$total_pages = 0;
$area_bw = 0;
$pages_bw = 0;
$area_cl = 0;
$pages_cl = 0;

$product_attributes = array();
if ($product_data['product_attributes']) {
	$product_attributes = unserialize($product_data['product_attributes']);
}

$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
if ($product_type_matrix_types) { ?>
	<div class="print-products-area product-attributes">
		<div class="co-box">
			<p class="form-field">
				<label><?php _e('Project Name', 'wp2print'); ?>:</label>
				<input type="text" name="aec_project_name" class="aec-project-name" value="<?php if ($product_data['project_name']) { echo $product_data['project_name']; } ?>">
			</p>
			<?php $sattrex = 0; $mtypecount = array();
			foreach($product_type_matrix_types as $product_type_matrix_type) {
				$mtype_id = $product_type_matrix_type->mtype_id;
				$mtype = $product_type_matrix_type->mtype;
				$mattributes = unserialize($product_type_matrix_type->attributes);
				$materms = unserialize($product_type_matrix_type->aterms);
				$numbers = explode(',', $product_type_matrix_type->numbers);
				$num_style = $product_type_matrix_type->num_style;
				$num_type = $product_type_matrix_type->num_type;

				$mtypecount[$mtype]++;

				if ($mattributes) { $mattributes = print_products_sort_attributes($mattributes); ?>
					<?php if ($mtype == 0) { // simple matrix ?>
						<div class="matrix-type-simple" data-mtid="<?php echo $mtype_id; ?>" data-ntp="<?php echo $num_type; ?>">
							<?php if ($numbers) { ?>
								<p class="form-field">
									<label><?php echo print_products_attribute_label('quantity', $attribute_labels, __('Quantity', 'wp2print')); ?>: <span class="req">*</span></label>
									<?php if ($num_style == 1) { ?>
										<select name="quantity" id="qty" class="quantity" onchange="matrix_calculate_price();">
											<?php foreach($numbers as $number) { ?>
												<option value="<?php echo $number; ?>"<?php if ($product_data['quantity'] && $product_data['quantity'] == $number) { echo ' SELECTED'; } ?>><?php echo $number; ?></option>
											<?php } ?>
										</select>
									<?php } else { ?>
										<input type="text" name="quantity" id="qty" class="quantity" value="<?php if ($product_data['quantity']) { echo $product_data['quantity']; } else { echo $numbers[0]; } ?>" onblur="matrix_calculate_price();">
									<?php } ?>
								</p>
							<?php } ?>
							<div class="print-attributes">
								<?php foreach($mattributes as $mattribute) {
									$matype = $attribute_types[$mattribute];
									$aterms = $materms[$mattribute];
									$aval = '';
									if (count($product_attributes)) {
										$avals = explode(':', $product_attributes[$anmb]);
										$akey = $avals[0];
										$aval = $avals[1];
										$anmb++;
									}
									if ($matype == 'text') { ?>
										<p class="form-field matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
											<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
											<div class="attr-box">
												<input type="text" name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr smatrix-attr-text" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();">
											</div>
										</p>
										<?php
									} else {
										if ($aterms) {
											$aterms = print_products_get_attribute_terms($aterms);
											$attr_class = '';
											if ($mattribute == $size_attribute) { $attr_class = ' smatrix-size'; }
											if ($mattribute == $material_attribute) { $attr_class = ' smatrix-material'; }
											if ($mattribute == $page_count_attribute) { $attr_class = ' smatrix-pagecount'; } ?>
											<p class="form-field matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
												<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
												<select name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr<?php echo $attr_class; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();">
													<?php foreach($aterms as $aterm_id => $aterm_name) { ?>
														<option value="<?php echo $aterm_id; ?>"<?php if ($aval == $aterm_id) { echo ' SELECTED'; } ?>><?php echo $aterm_name; ?></option>
													<?php } ?>
												</select>
											</p>
										<?php } ?>
									<?php } ?>
								<?php } ?>
							</div>
						</div>
					<?php } else { // finishing matrix ?>
						<div class="matrix-type-finishing" data-mtid="<?php echo $mtype_id; ?>" data-ntp="<?php echo $num_type; ?>">
							<div class="finishing-attributes">
							<?php foreach($mattributes as $mattribute) {
								$matype = $attribute_types[$mattribute];
								$aterms = $materms[$mattribute];
								$aval = '';
								if (count($product_attributes)) {
									$avals = explode(':', $product_attributes[$anmb]);
									$akey = $avals[0];
									$aval = $avals[1];
									$anmb++;
								}
								if ($matype == 'text') { ?>
									<p class="form-field matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
										<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
										<input type="text" name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();">
									</p>
									<?php
								} else {
									if ($aterms) {
										$aterms = print_products_get_attribute_terms($aterms); ?>
										<p class="form-field matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
											<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
											<select name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();">
												<?php foreach($aterms as $aterm_id => $aterm_name) { ?>
													<option value="<?php echo $aterm_id; ?>"<?php if ($aval == $aterm_id) { echo ' SELECTED'; } ?>><?php echo $aterm_name; ?></option>
												<?php } ?>
											</select>
										</p>
									<?php } ?>
								<?php } ?>
							<?php } ?>
							</div>
						</div>
					<?php } ?>
					<?php
					$lmtype = $mtype;
				}
			} ?>

			<p class="form-field">
				<label class="product-price"><?php _e('Total Area', 'wp2print'); ?>, <?php echo $dimension_unit; ?><sup>2</sup>:</label>
				<input type="text" name="aec_total_area" class="aec-total-area" value="<?php if ($product_data['total_area']) { echo $product_data['total_area']; } ?>">
			</p>

			<p class="form-field">
				<label class="product-price"><?php _e('Total Pages', 'wp2print'); ?>:</label>
				<input type="text" name="aec_total_pages" class="aec-total-pages" value="<?php if ($product_data['total_pages']) { echo $product_data['total_pages']; } ?>">
			</p>

			<p class="form-field">
				<label class="product-price"><?php _e('Area B/W', 'wp2print'); ?>, <?php echo $dimension_unit; ?><sup>2</sup>:</label>
				<input type="text" name="aec_area_bw" class="aec-area-bw" value="<?php if ($product_data['area_bw']) { echo $product_data['area_bw']; } ?>">
			</p>

			<p class="form-field">
				<label class="product-price"><?php _e('Pages B/W', 'wp2print'); ?>:</label>
				<input type="text" name="aec_pages_bw" class="aec-pages-bw" value="<?php if ($product_data['pages_bw']) { echo $product_data['pages_bw']; } ?>">
			</p>

			<p class="form-field">
				<label class="product-price"><?php _e('Area Color', 'wp2print'); ?>, <?php echo $dimension_unit; ?><sup>2</sup>:</label>
				<input type="text" name="aec_area_cl" class="aec-area-cl" value="<?php if ($product_data['area_cl']) { echo $product_data['area_cl']; } ?>">
			</p>

			<p class="form-field">
				<label class="product-price"><?php _e('Pages Color', 'wp2print'); ?>:</label>
				<input type="text" name="aec_pages_cl" class="aec-pages-cl" value="<?php if ($product_data['pages_cl']) { echo $product_data['pages_cl']; } ?>">
			</p>

			<p class="form-field">
				<label><?php _e('Subtotal', 'wp2print'); ?>: <span class="req">*</span></label>
				<input type="text" name="price" class="p-price" value="<?php if ($product_data['price']) { echo $product_data['price']; } ?>">
			</p>
		</div>

		<input type="hidden" name="product_type" value="aecbwc">
		<input type="hidden" name="smparams" class="sm-params" value="<?php if ($product_data['smparams']) { echo $product_data['smparams']; } ?>">
		<input type="hidden" name="fmparams" class="fm-params" value="<?php if ($product_data['fmparams']) { echo $product_data['fmparams']; } ?>">
	</div>
	<?php
	$smatrix = array();
	$fmatrix = array();
	foreach($product_type_matrix_types as $product_type_matrix_type) {
		$mtype_id = $product_type_matrix_type->mtype_id;
		$mtype = $product_type_matrix_type->mtype;
		$numbers = $product_type_matrix_type->numbers;

		$mnumbers[$mtype_id] = $numbers;

		$matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
		if ($matrix_prices) {
			foreach($matrix_prices as $matrix_price) {
				$aterms = $matrix_price->aterms;
				$number = $matrix_price->number;
				$price = $matrix_price->price;

				if ($mtype == 1) {
					$fmatrix[$aterms.'-'.$number] = $price;
				} else {
					$smatrix[$aterms.'-'.$number] = $price;
				}
			}
		}
	}
	?>
	<script>
	var matrix_price = 0;
	var aec_total_area = 0;
	var aec_enable_size = <?php echo $aec_enable_size; ?>;
	var apply_round_up = <?php if ($apply_round_up) { echo 'true'; } else { echo 'false'; } ?>;
	var price_decimals = <?php echo wc_get_price_decimals(); ?>;
	var global_area_display_units = '<?php echo $dimension_unit; ?>';
	var global_width_measure = '<?php echo $area_square_unit; ?>';

	var numbers_array = new Array();
	<?php foreach($mnumbers as $ntp => $narr) { ?>
	numbers_array[<?php echo $ntp; ?>] = '<?php echo $narr; ?>';
	<?php } ?>

	var smatrix = new Object();
	<?php foreach($smatrix as $mkey => $mval) { ?>
	smatrix['<?php echo $mkey; ?>'] = <?php echo $mval; ?>;
	<?php } ?>

	var fmatrix = new Object();
	<?php foreach($fmatrix as $mkey => $mval) { ?>
	fmatrix['<?php echo $mkey; ?>'] = <?php echo $mval; ?>;
	<?php } ?>

	<?php $aec_coverage_ranges = print_products_get_aec_coverage_ranges(); ?>
	var coverage_ranges = [<?php echo implode(', ', $aec_coverage_ranges); ?>];
	var inc_coverage_prices_b = new Array();
	var inc_coverage_prices_c = new Array();
	<?php if (is_array($inc_coverage_prices) && count($inc_coverage_prices)) { ?>
		<?php foreach($inc_coverage_prices[0] as $mid => $pprice) { ?>
	inc_coverage_prices_b['<?php echo $mid; ?>'] = <?php echo (float)$pprice; ?>;
		<?php } ?>
		<?php foreach($inc_coverage_prices[1] as $mid => $pprice) { ?>
	inc_coverage_prices_c['<?php echo $mid; ?>'] = <?php echo (float)$pprice; ?>;
		<?php } ?>
	<?php } ?>

	var round_up_discounts = new Array();
	round_up_discounts[0] = 0;
	<?php if (is_array($round_up_discounts) && count($round_up_discounts)) { ?>
		<?php foreach($round_up_discounts as $mnum => $round_up_discount_price) { ?>
	round_up_discounts[<?php echo $mnum; ?>] = <?php echo (float)$round_up_discount_price; ?>;
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

	<?php if (!count($product_attributes)) { ?>
	jQuery(document).ready(function() {
		matrix_calculate_price();
	});
	<?php } ?>

	function matrix_calculate_price() {
		var smparams = '';
		var fmparams = '';

		matrix_price = 0;

		var quantity = parseInt(jQuery('.product-attributes .quantity').val());

		jQuery('.product-attributes .quantity').val(quantity);

		if (quantity <= 0 || !jQuery.isNumeric(quantity)) { quantity = 1; jQuery('.product-attributes .quantity').val('1'); }

		// simple matrix
		jQuery('.matrix-type-simple').each(function(){
			var mtid = jQuery(this).attr('data-mtid');
			var ntp = jQuery(this).attr('data-ntp');
			var smval = ''; var psmval = ''; var smsep = '';
			var size_val = parseInt(jQuery(this).find('.print-attributes .smatrix-size').eq(0).val());
			var material_val = parseInt(jQuery(this).find('.print-attributes .smatrix-material').eq(0).val());
			var pagecount_val = parseInt(jQuery(this).find('.print-attributes .smatrix-pagecount').eq(0).val());

			jQuery(this).find('.print-attributes .smatrix-attr').each(function(){
				var aid = jQuery(this).attr('data-aid');
				var fval = jQuery(this).val();
				fval = matrix_aval(fval);
				smval += smsep + aid+':'+fval;
				if (!jQuery(this).hasClass('smatrix-attr-text')) {
					psmval += smsep + aid+':'+fval;
				}
				smsep = '-';
			});

			var nmb_val = quantity;
			var numbers = numbers_array[mtid].split(',');
			var min_number = parseInt(numbers[0]);

			jQuery('.area-wh-error').hide();
			if (nmb_val < min_number) {
				var emessage = '<?php _e('Min quantity is ', 'wp2print'); ?>'+min_number;
				jQuery('.area-wh-error').html(emessage).animate({height: 'show'}, 200);
				setTimeout(function(){ jQuery('.area-wh-error').animate({height: 'hide'}); }, 6000);
				jQuery('.product-attributes .quantity').val(min_number);
				quantity = min_number;
				nmb_val = quantity;
			}

			if (smparams != '') { smparams += ';'; }
			smparams += mtid+'|'+smval+'|'+nmb_val;
		});
		jQuery('.create-order-form .sm-params').val(smparams);

		// finishing matrix
		jQuery('.matrix-type-finishing').each(function(){
			var mtid = jQuery(this).attr('data-mtid');
			var ntp = jQuery(this).attr('data-ntp');
			var fmsize_aid = 0;
			var fmsize_val = 0;
			if (jQuery('.matrix-type-simple').find('.smatrix-size').size()) {
				fmsize_aid = jQuery('.matrix-type-simple').find('.smatrix-size').attr('data-aid');
				fmsize_val = jQuery('.matrix-type-simple').find('.smatrix-size').val();
			}

			jQuery(this).find('.finishing-attributes .fmatrix-attr').each(function(){
				var fprice = 0;
				var aid = jQuery(this).attr('data-aid');
				var fval = jQuery(this).val();
				fval = matrix_aval(fval);
				var fmval = aid+':'+fval;
				if (fmsize_aid) {
					fmval = fmsize_aid+':'+fmsize_val+'-'+aid+':'+fval;
				}

				var nmb_val = quantity;
				var numbers = numbers_array[mtid].split(',');

				var nums = matrix_get_numbers(nmb_val, numbers);
				var fmprice = matrix_get_price(fmatrix, fmval, nmb_val, nums);
				if (fmprice) { matrix_price = matrix_price + fmprice; }

				if (fmparams != '') { fmparams += ';'; }
				fmparams += mtid+'|'+fmval+'|'+nmb_val;
			});
		});
		jQuery('.create-order-form .fm-params').val(fmparams);

		if (matrix_price < 0) { matrix_price = 0; }

		jQuery('.create-order-form .p-price').val(matrix_price.toFixed(2));

		matrix_set_tax();
		matrix_set_prices();
	}
	</script>
<?php } ?>