<?php
global $wpdb, $print_products_settings, $attribute_names, $attribute_types;

$size_attribute = $print_products_settings['size_attribute'];
$material_attribute = $print_products_settings['material_attribute'];

$dimension_unit = print_products_get_dimension_unit();

$area_product_min_width = get_post_meta($product_id, '_area_min_width', true);
$area_product_max_width = get_post_meta($product_id, '_area_max_width', true);
$area_product_min_height = get_post_meta($product_id, '_area_min_height', true);
$area_product_max_height = get_post_meta($product_id, '_area_max_height', true);
$area_product_min_quantity = (int)get_post_meta($product_id, '_area_min_quantity', true);
$area_product_width_round = get_post_meta($product_id, '_area_width_round', true);
$area_product_height_round = get_post_meta($product_id, '_area_height_round', true);
$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
$area_unit = (int)get_post_meta($product_id, '_area_unit', true);

if (!$area_product_min_quantity) { $area_product_min_quantity = 1; }

$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
print_products_price_matrix_attr_names_init($attributes);

$anmb = 0;
$product_attributes = array();
if ($product_data['product_attributes']) {
	$product_attributes = unserialize($product_data['product_attributes']);
}

$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
if ($product_type_matrix_types) { ?>
	<div class="print-products-area product-attributes">
		<div class="co-box">
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
							<p class="form-field">
								<label><?php echo print_products_attribute_label('quantity', $attribute_labels, __('Quantity', 'wp2print')); ?>: <span class="req">*</span></label>
								<?php if ($num_style == 1) { ?>
									<select name="quantity" class="quantity" onchange="matrix_calculate_price()">
										<?php foreach($numbers as $number) { ?>
											<option value="<?php echo $number; ?>"<?php if ($product_data['quantity'] && $product_data['quantity'] == $number) { echo ' SELECTED'; } ?>><?php echo $number; ?></option>
										<?php } ?>
									</select>
								<?php } else { ?>
									<input type="text" name="quantity" class="quantity" value="<?php if ($product_data['quantity']) { echo $product_data['quantity']; } else { echo $area_product_min_quantity; } ?>" onblur="matrix_calculate_price()">
								<?php } ?>
							</p>
							<p class="form-field">
								<label><?php echo print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')); ?> (<?php echo $dimension_unit; ?>):</label>
								<input type="text" name="width" class="width" value="<?php if ($product_data['width']) { echo $product_data['width']; } else { echo $area_product_min_width; } ?>" onblur="matrix_width_action()">
							</p>
							<p class="form-field">
								<label><?php echo print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')); ?> (<?php echo $dimension_unit; ?>):</label>
								<input type="text" name="height" class="height" value="<?php if ($product_data['height']) { echo $product_data['height']; } else { echo $area_product_min_height; } ?>" onblur="matrix_height_action()">
							</p>
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
											<input type="text" name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr smatrix-attr-text" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();">
										</p>
										<?php
									} else {
										if ($aterms) {
											$aterms = print_products_get_attribute_terms($aterms);
											$attr_class = '';
											if ($mattribute == $size_attribute) { $attr_class = ' smatrix-size'; }
											if ($mattribute == $material_attribute) { $attr_class = ' smatrix-material'; } ?>
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
									<p class="form-field  matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
										<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
										<div class="attr-box">
											<input type="text" name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();">
										</div>
									</p>
									<?php
								} else {
									if ($aterms) {
										$aterms = print_products_get_attribute_terms($aterms);
										$hide_style = ''; $attr_class = 'fmatrix-attr';
										if ($mattribute == $size_attribute) { $attr_class = 'fmatrix-size'; $hide_style = ' style="display:none;"'; } ?>
										<p class="form-field  matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>"<?php echo $hide_style; ?>>
											<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
											<select name="fattribute[<?php echo $mattribute; ?>]" class="<?php echo $attr_class; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();">
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
		</div>

		<input type="hidden" name="product_type" value="area">
		<input type="hidden" name="smparams" class="sm-params" value="<?php if ($product_data['smparams']) { echo $product_data['smparams']; } ?>">
		<input type="hidden" name="fmparams" class="fm-params" value="<?php if ($product_data['fmparams']) { echo $product_data['fmparams']; } ?>">
	</div>
	<?php
	$smatrix = array();
	$fmatrix = array();
	$smnumbers = array();
	$fmnumbers = array();
	foreach($product_type_matrix_types as $product_type_matrix_type) {
		$mtype_id = $product_type_matrix_type->mtype_id;
		$mtype = $product_type_matrix_type->mtype;
		$numbers = $product_type_matrix_type->numbers;
		$num_type = $product_type_matrix_type->num_type;

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
	var price = 0;
	var dim_unit = '<?php echo $dimension_unit; ?>';
	var a_unit = <?php echo $area_unit; ?>;
	var width_round = <?php echo (float)$area_product_width_round; ?>;
	var height_round = <?php echo (float)$area_product_height_round; ?>;

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

	<?php if (!count($product_attributes)) { ?>
	jQuery(document).ready(function() {
		matrix_calculate_price();
	});
	<?php } ?>

	function matrix_calculate_price() {
		var w, h, area_size;
		var smparams = '';
		var fmparams = '';

		price = 0;

		var min_width = <?php echo (int)$area_product_min_width; ?>;
		var max_width = <?php echo (int)$area_product_max_width; ?>;
		var min_height = <?php echo (int)$area_product_min_height; ?>;
		var max_height = <?php echo (int)$area_product_max_height; ?>;
		var min_quantity = <?php echo (int)$area_product_min_quantity; ?>;

		var quantity = parseInt(jQuery('.product-attributes .quantity').val());
		var width = parseFloat(jQuery('.product-attributes .width').val());
		var height = parseFloat(jQuery('.product-attributes .height').val());

		jQuery('.product-attributes .quantity').val(quantity);

		if (quantity <= 0 || !jQuery.isNumeric(quantity)) { quantity = 1; jQuery('.product-attributes .quantity').val('1'); }

		if (width < min_width) {
			width = min_width;
			jQuery('.product-attributes .width').val(width);
		} else if (width > max_width) {
			width = max_width;
			jQuery('.product-attributes .width').val(width);
		}

		if (height < min_height) {
			height = min_height;
			jQuery('.product-attributes .height').val(height);
		} else if (height > max_height) {
			height = max_height;
			jQuery('.product-attributes .height').val(height);
		}

		// simple matrix
		jQuery('.matrix-type-simple').each(function(){
			var mtid = jQuery(this).attr('data-mtid');
			var ntp = jQuery(this).attr('data-ntp');
			var smval = ''; var psmval = ''; var smsep = '';
			var material_val = jQuery(this).find('.print-attributes .smatrix-material').eq(0).val();

			jQuery(this).find('.print-attributes .smatrix-attr').each(function(){
				var aid = jQuery(this).attr('data-aid');
				var fval = jQuery(this).val();
				smval += smsep + aid+':'+fval;
				if (!jQuery(this).hasClass('smatrix-attr-text')) {
					psmval += smsep + aid+':'+fval;
				}
				smsep = '-';
			});

			if (quantity < min_quantity) {
				alert("<?php _e('Min quantity is ', 'wp2print'); ?>"+min_quantity);
				jQuery('.product-attributes .quantity').val(min_quantity);
				quantity = min_quantity;
			}

			w = width;
			h = height;
			if (ntp == 2 || ntp == 3) { // area type
				area_size = matrix_get_area_size(width, height);
				w = area_size[0];
				h = area_size[1];
			}

			var nmb_val = quantity;
			if (ntp == 2) {
				nmb_val = quantity * w * h;
			} else if (ntp == 3) {
				nmb_val = quantity * ((w * 2) + (h * 2));
			} else if (ntp == 4) {
				nmb_val = quantity * (w * 2);
			}

			var numbers = numbers_array[mtid].split(',');
			var min_number = parseInt(numbers[0]);

			var nums = matrix_get_numbers(nmb_val, numbers);
			var smprice = matrix_get_price(smatrix, psmval, nmb_val, nums);
			if (smprice) { price = price + smprice; }

			if (smparams != '') { smparams += ';'; }
			smparams += mtid+'|'+smval+'|'+nmb_val+'|'+ntp;
		});
		jQuery('.send-quote-form .sm-params').val(smparams);

		// finishing matrix
		jQuery('.matrix-type-finishing').each(function(){
			var mtid = jQuery(this).attr('data-mtid');
			var ntp = jQuery(this).attr('data-ntp');

			w = width;
			h = height;
			if (ntp == 2 || ntp == 3) { // area type
				area_size = matrix_get_area_size(width, height);
				w = area_size[0];
				h = area_size[1];
			}

			jQuery(this).find('.finishing-attributes .fmatrix-attr').each(function(){
				var fprice = 0;
				var aid = jQuery(this).attr('data-aid');
				var fval = jQuery(this).val();

				var fmval = aid+':'+fval;

				var nmb_val = quantity;
				if (ntp == 2) {
					nmb_val = quantity * w * h;
				} else if (ntp == 3) {
					nmb_val = quantity * ((w * 2) + (h * 2));
				} else if (ntp == 4) {
					nmb_val = quantity * (w * 2);
				}

				var numbers = numbers_array[mtid].split(',');
				var nums = matrix_get_numbers(nmb_val, numbers);
				var fmprice = matrix_get_price(fmatrix, fmval, nmb_val, nums);
				if (fmprice) { price = price + fmprice; }

				if (fmparams != '') { fmparams += ';'; }
				fmparams += mtid+'|'+fmval+'|'+nmb_val+'|'+ntp;
			});
		});
		jQuery('.send-quote-form .fm-params').val(fmparams);

		if (price < 0) { price = 0; }

		jQuery('.send-quote-form .p-price').val(price.toFixed(2));
		jQuery('.send-quote-form .t-price').html(matrix_html_price(price));
	}

	function matrix_get_area_size(width, height) {
		if (width > 0 && height > 0) {
			if (dim_unit == 'in') {
				if (a_unit == 1) { // in ft2
					width = width * 0.0833;
					height = height * 0.0833;
				}
			} else if (dim_unit == 'ft') {
				if (a_unit == 1) { // in in2
					width = width / 0.0833;
					height = height / 0.0833;
				}
			} else {
				if (a_unit == 1) { // in cm2
					if (dim_unit == 'mm') {
						width = width / 10;
						height = height / 10;
					} else if (dim_unit == 'm') {
						width = width * 100;
						height = height * 100;
					}
				} else {
					if (dim_unit == 'mm') {
						width = width / 1000;
						height = height / 1000;
					} else if (dim_unit == 'cm') {
						width = width / 100;
						height = height / 100;
					}
				}
			}
		}
		return [width, height];
	}

	function matrix_width_action() {
		var width = parseFloat(jQuery('.product-attributes .width').val());
		var rounded_width = matrix_round_number(width, width_round);
		jQuery('.product-attributes .width').val(rounded_width);
		matrix_calculate_price();
	}

	function matrix_height_action() {
		var height = parseFloat(jQuery('.product-attributes .height').val());
		var rounded_height = matrix_round_number(height, height_round);
		jQuery('.product-attributes .height').val(rounded_height);
		matrix_calculate_price();
	}

	function matrix_round_number(n, r) {
		if (r > 0) {
			var ns = n.toString();
			if (r == 1000) {
				return Math.ceil(n);
			} else if (ns.indexOf('.')) {
				n = n.toFixed(3);
				ns = n.toString();
				var inmb = parseInt(n);
				var dnmb = parseInt(ns.substring(ns.indexOf('.')+1, ns.length));
				var needed_nmb = matrix_get_needed_number(dnmb, r);
				if (needed_nmb == 0) {
					n = inmb;
				} else if (needed_nmb == 1) {
					n = inmb + 1;
				} else {
					var tofx = 1;
					if (r == 125) {
						tofx = 3;
					} else if (r == 250) {
						tofx = 2;
					}
					n = parseFloat(inmb + '.' + needed_nmb).toFixed(tofx);
				}
				return n;
			} else {
				return n;
			}
		} else {
			return Math.round(n);
		}
	}

	function matrix_get_needed_number(n, r) {
		var ln = 0;
		125 - 250
		for (var i=r; i<1000; i=i+r) {
			var dn = Math.ceil(i / 2);
			if (n < dn) {
				return ln;
			} else if (n > dn && n <= i) {
				var dn2 = Math.ceil((ln + i) / 2);
				if (n < dn2) {
					return ln;
				} else {
					return i;
				}
			}
			ln = i;
		}
		return 1;
	}
	</script>
<?php } else { ?>
	<p class="form-field"><?php _e('No product attributes.', 'wp2print'); ?></p>
<?php } ?>