<?php
global $wpdb, $print_products_settings, $attribute_names, $attribute_types;

$page_color_names = array(0 => __('Black/White Pages', 'wp2print'), 1 => __('Colour Pages', 'wp2print'));

$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);

$size_attribute = $print_products_settings['size_attribute'];
$material_attribute = $print_products_settings['material_attribute'];

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
			<?php $pcnmb = 0; $mtypecount = array();
			foreach($product_type_matrix_types as $product_type_matrix_type) {
				$mtype_id = $product_type_matrix_type->mtype_id;
				$mtype = $product_type_matrix_type->mtype;
				$mattributes = unserialize($product_type_matrix_type->attributes);
				$materms = unserialize($product_type_matrix_type->aterms);
				$numbers = explode(',', $product_type_matrix_type->numbers);
				$num_style = $product_type_matrix_type->num_style;
				$num_type = $product_type_matrix_type->num_type;
				$bq_numbers = explode(',', $product_type_matrix_type->bq_numbers);
				$book_min_quantity = (int)$product_type_matrix_type->book_min_quantity;
				$pq_style = $product_type_matrix_type->pq_style;
				$pq_numbers = explode(',', $product_type_matrix_type->pq_numbers);
				$title = $product_type_matrix_type->title;
				$def_quantity = (int)$product_type_matrix_type->def_quantity;

				if (strlen($title)) { $title .= ' '; }
				if (!$book_min_quantity) { $book_min_quantity = 1; }

				$mtypecount[$mtype]++;

				if ($mattributes) { $mattributes = print_products_sort_attributes($mattributes); ?>
					<?php if (!$qb) { $qb = true; ?>
						<p class="form-field">
							<label><?php echo print_products_attribute_label('bquantity', $attribute_labels, __('Quantity of bound books', 'wp2print')); ?>: <span class="req">*</span></label>
							<?php if ($num_style == 1 && count($bq_numbers)) { ?>
								<select name="quantity" class="books-quantity" onchange="matrix_calculate_price()" data-book-min-quantity="<?php echo $book_min_quantity; ?>">
									<?php foreach($bq_numbers as $bq_number) { ?>
										<option value="<?php echo $bq_number; ?>"<?php if ($product_data['quantity'] && $product_data['quantity'] == $bq_number) { echo ' SELECTED'; } ?>><?php echo $bq_number; ?></option>
									<?php } ?>
								</select>
							<?php } else { ?>
								<input type="text" name="quantity" class="books-quantity" value="<?php if ($product_data['quantity']) { echo $product_data['quantity']; } else { echo $book_min_quantity; } ?>" onblur="matrix_calculate_price()" data-book-min-quantity="<?php echo $book_min_quantity; ?>">
							<?php } ?>
						</p>
					<?php } ?>
					<?php if ($mtype == 0) { // simple matrix ?>
						<div class="matrix-type-simple" data-mtid="<?php echo $mtype_id; ?>" data-ntp="<?php echo $num_type; ?>">
							<?php
							$pqval = '';
							if (count($product_attributes)) {
								$avals = explode(':', $product_attributes[$anmb]);
								$pqval = $avals[1];
								$anmb++;
							}
							?>
							<?php if (in_array($size_attribute, $mattributes)) {
								$sizekey = array_search($size_attribute, $mattributes);
								$aterms = $materms[$size_attribute];
								$aterms = print_products_get_attribute_terms($aterms);
								$attr_class = ' smatrix-size'; $hide_style = '';
								if ($mtypecount[0] > 1) {
									$attr_class = ' matrix-size-hidden'; $hide_style = ' style="display:none;"';
								} else {
									$aval = '';
									if (count($product_attributes)) {
										$avals = explode(':', $product_attributes[$anmb]);
										$akey = $avals[0];
										$aval = $avals[1];
										$anmb++;
									}
								}
								?>
								<p class="form-field print-attributes"<?php echo $hide_style; ?>>
									<label><?php echo print_products_attribute_label($size_attribute, $attribute_labels, $attribute_names[$size_attribute]); ?>:</label>
									<select name="sattribute[<?php echo $size_attribute; ?>]" class="smatrix-attr<?php echo $attr_class; ?>" data-aid="<?php echo $size_attribute; ?>"<?php if ($mtypecount[0] == 1) { ?> onchange="matrix_book_size_change(this)"<?php } ?>>
										<?php foreach($aterms as $aterm_id => $aterm_name) { ?>
											<option value="<?php echo $aterm_id; ?>"<?php if ($aval == $aterm_id) { echo ' SELECTED'; } ?>><?php echo $aterm_name; ?></option>
										<?php } ?>
									</select>
								</p>
								<?php 
								unset($mattributes[$sizekey]);
							} ?>
							<p class="form-field numbers-list">
								<label><?php echo $title; ?><?php echo print_products_attribute_label('pquantity', $attribute_labels, __('Pages Quantity', 'wp2print')); ?>:</label>
								<?php if ($pq_style == 1 && count($pq_numbers)) { ?>
									<select name="page_quantity_<?php echo $mtype_id; ?>" class="quantity" onchange="matrix_calculate_price()">
										<?php foreach($pq_numbers as $pq_number) { ?>
											<option value="<?php echo $pq_number; ?>"<?php if ($pqval == $pq_number) { echo ' SELECTED'; } ?>><?php echo $pq_number; ?></option>
										<?php } ?>
									</select>
								<?php } else { ?>
									<input type="text" name="page_quantity_<?php echo $mtype_id; ?>" class="quantity" value="<?php if ($pqval) { echo $pqval; } else { echo $def_quantity; } ?>" onblur="matrix_calculate_price()">
								<?php } ?>
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
											if ($mattribute == $material_attribute) { $attr_class = ' smatrix-material'; } ?>
											<p class="form-field matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
												<label><?php echo $title; ?><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label>
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
					$pcnmb++;
				}
			} ?>
			<p class="form-field">
				<label><?php _e('Subtotal', 'wp2print'); ?>: <span class="req">*</span></label>
				<input type="text" name="price" class="p-price" value="<?php if ($product_data['price']) { echo $product_data['price']; } ?>">
			</p>
		</div>

		<input type="hidden" name="product_type" value="book">
		<input type="hidden" name="smparams" class="sm-params" value="<?php if ($product_data['smparams']) { echo $product_data['smparams']; } ?>">
		<input type="hidden" name="fmparams" class="fm-params" value="<?php if ($product_data['fmparams']) { echo $product_data['fmparams']; } ?>">
	</div>
	<?php
	$smatrix = array();
	$fmatrix = array();
	$mnumbers = array();
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
	var price = 0;
	var books_quantity = 0;
	var total_pages = 0;

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

	function matrix_book_size_change(o) {
		var sind = jQuery(o).val();
		jQuery('.matrix-size-hidden option').removeAttr('selected');
		jQuery('.matrix-size-hidden option[value="'+sind+'"]').attr('selected', 'selected');
		matrix_calculate_price();
	}

	function matrix_calculate_price() {
		var smparams = '';
		var fmparams = '';
		var pdprice = '<?php echo $product_display_price; ?>';

		price = 0;
		total_pages = 0;

		var min_quantity = parseInt(jQuery('.product-attributes .books-quantity').attr('data-book-min-quantity'));
		books_quantity = parseInt(jQuery('.product-attributes .books-quantity').val());
		jQuery('.product-attributes .books-quantity').val(books_quantity);

		jQuery('.area-wh-error').hide();
		if (books_quantity < min_quantity || !jQuery.isNumeric(books_quantity)) {
			jQuery('.area-wh-error').animate({height: 'show'}, 200);
			setTimeout(function(){ jQuery('.area-wh-error').animate({height: 'hide'}); }, 6000);
			jQuery('.product-attributes .books-quantity').val(min_quantity);
			books_quantity = min_quantity;
		}

		var bqflag = true;
		// simple matrix
		jQuery('.matrix-type-simple').each(function(){
			var mtid = jQuery(this).attr('data-mtid');
			var ntp = jQuery(this).attr('data-ntp');
			var qty = parseInt(jQuery(this).find('.quantity').val());
			var smval = ''; var psmval = ''; var smsep = '';

			jQuery(this).find('.quantity').val(qty);

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

			var nmb_val = qty;
			if (ntp == 1) {
				nmb_val = qty * books_quantity;
				bqflag = false;
			}

			var numbers = numbers_array[mtid].split(',');

			var nums = matrix_get_numbers(nmb_val, numbers);
			var smprice = matrix_get_price(smatrix, psmval, nmb_val, nums);
			if (smprice) { price = price + smprice; }

			if (smparams != '') { smparams += ';'; }
			smparams += mtid+'|'+smval+'|'+nmb_val+'|'+ntp;

			total_pages = total_pages + nmb_val;
		});
		jQuery('.create-order-form .sm-params').val(smparams);

		// finishing matrix
		jQuery('.matrix-type-finishing').each(function(){
			var mtid = jQuery(this).attr('data-mtid');
			var ntp = jQuery(this).attr('data-ntp');
			var size_aid = 0;
			var size_val = 0;

			if (jQuery('.matrix-type-simple').find('.smatrix-size').size()) {
				size_aid = jQuery('.matrix-type-simple').find('.smatrix-size').eq(0).attr('data-aid');
				size_val = jQuery('.matrix-type-simple').find('.smatrix-size').eq(0).val();
			}

			jQuery(this).find('.finishing-attributes .fmatrix-attr').each(function(){
				var fprice = 0;
				var aid = jQuery(this).attr('data-aid');
				var fval = jQuery(this).val();
				fval = matrix_aval(fval);
				var fmval = aid+':'+fval;
				if (size_aid) {
					fmval = size_aid+':'+size_val+'-'+aid+':'+fval;
				}

				var nmb_val = books_quantity;
				if (ntp == 1) {
					nmb_val = total_pages;
				}

				var numbers = numbers_array[mtid].split(',');
				var nums = matrix_get_numbers(nmb_val, numbers);
				var fmprice = matrix_get_price(fmatrix, fmval, nmb_val, nums);
				if (fmprice) { price = price + fmprice; }

				if (fmparams != '') { fmparams += ';'; }
				fmparams += mtid+'|'+fmval+'|'+nmb_val+'|'+ntp;
			});
		});
		jQuery('.create-order-form .fm-params').val(fmparams);

		if (price < 0) { price = 0; }

		jQuery('.create-order-form .p-price').val(price.toFixed(2));

		matrix_set_tax();
		matrix_set_prices();
	}
	</script>
<?php } else { ?>
	<p class="form-field"><?php _e('No product attributes.', 'wp2print'); ?></p>
<?php } ?>