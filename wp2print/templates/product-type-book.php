<?php
global $product, $wpdb, $print_products_settings, $attribute_names, $attribute_types, $attribute_imgs, $wp2print_attribute_images;

$woocommerce_calc_taxes = get_option('woocommerce_calc_taxes');
$woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
$price_display_incl_suffix = get_option('woocommerce_price_display_suffix');
$price_display_excl_suffix = get_option('woocommerce_price_display_excl_suffix');
$print_products_plugin_options = get_option('print_products_plugin_options');

unset($_SESSION['artworkfiles']);

$product_id = $product->id;

$page_color_names = array(0 => __('Black/White Pages', 'wp2print'), 1 => __('Colour Pages', 'wp2print'));

$artwork_source = get_post_meta($product_id, '_artwork_source', true);
$artwork_allow_later = get_post_meta($product_id, '_artwork_allow_later', true);
$artwork_file_count = (int)get_post_meta($product_id, '_artwork_file_count', true);
$artwork_afile_types = get_post_meta($product_id, '_artwork_afile_types', true);
$product_shipping_weights = unserialize(get_post_meta($product_id, '_product_shipping_weights', true));
$product_shipping_base_quantities = unserialize(get_post_meta($product_id, '_product_shipping_base_quantity', true));
$product_display_weight = get_post_meta($product_id, '_product_display_weight', true);
$product_display_price = get_post_meta($product_id, '_product_display_price', true);
$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
$attribute_display = (array)get_post_meta($product_id, '_attribute_display', true);
$attribute_as_radio = (array)get_post_meta($product_id, '_attribute_as_radio', true);
$use_production_speed = (int)get_post_meta($product_id, '_use_production_speed', true);
$production_speed_label = get_post_meta($product_id, '_production_speed_label', true);
$production_speed_options = get_post_meta($product_id, '_production_speed_options', true);
$production_speed_sd_data = get_post_meta($product_id, '_production_speed_sd_data', true);
$order_min_price = (float)get_post_meta($product_id, '_order_min_price', true);
$order_max_price = (float)get_post_meta($product_id, '_order_max_price', true);
$addon_products = get_post_meta($product_id, '_addon_products', true);
$max_price_message = $print_products_plugin_options['max_price_message'];

if (!strlen($production_speed_label)) { $production_speed_label = __('Production speed', 'wp2print'); }

if (!$product_display_price || $woocommerce_calc_taxes != 'yes' || $woocommerce_prices_include_tax == 'yes') { $product_display_price = 'excl'; }

$size_attribute = $print_products_settings['size_attribute'];
$material_attribute = $print_products_settings['material_attribute'];

$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
print_products_price_matrix_attr_names_init($attributes);

$anmb = 0;
$is_modify = false;
if (isset($_GET['modify']) && strlen($_GET['modify'])) {
	$cart_item_key = $_GET['modify'];
	$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
	if ($prod_cart_data) {
		$is_modify = true;
		$quantity_val = $prod_cart_data->quantity;
		$product_attributes = unserialize($prod_cart_data->product_attributes);
		$additional = unserialize($prod_cart_data->additional);
	}
}

$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
if ($product_type_matrix_types) { ?>
	<div class="print-products-area product-attributes" style="margin:0 0 15px 0;">
		<form method="POST" class="add-cart-form" onsubmit="return products_add_cart_action();">
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
					<ul class="product-attributes-list numbers-list">
						<li>
							<label><?php echo print_products_attribute_label('bquantity', $attribute_labels, __('Quantity of bound books', 'wp2print')); ?>:</label><br />
							<?php if ($num_style == 1 && count($bq_numbers)) { ?>
								<select name="quantity" class="books-quantity" onchange="matrix_calculate_price()" data-book-min-quantity="<?php echo $book_min_quantity; ?>">
									<?php foreach($bq_numbers as $bq_number) { ?>
										<option value="<?php echo $bq_number; ?>"<?php if ($quantity_val && $quantity_val == $bq_number) { echo ' SELECTED'; } ?>><?php echo $bq_number; ?></option>
									<?php } ?>
								</select>
							<?php } else { ?>
								<input type="text" name="quantity" class="books-quantity" value="<?php if ($quantity_val) { echo $quantity_val; } else { echo $book_min_quantity; } ?>" onblur="matrix_calculate_price()" data-book-min-quantity="<?php echo $book_min_quantity; ?>">
								<div class="area-wh-error">
									<span><?php _e('Min quantity is', 'wp2print'); ?> <?php echo $book_min_quantity; ?></span>
								</div>
							<?php } ?>
						</li>
					</ul>
				<?php } ?>
				<?php if ($mtype == 0) { // simple matrix ?>
					<div class="matrix-type-simple" data-mtid="<?php echo $mtype_id; ?>" data-ntp="<?php echo $num_type; ?>">
						<?php
						$pqval = '';
						if ($is_modify) {
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
								if ($is_modify) {
									$avals = explode(':', $product_attributes[$anmb]);
									$akey = $avals[0];
									$aval = $avals[1];
									$anmb++;
								}
							}
							$is_radio = false;
							if (isset($attribute_as_radio[$size_attribute]) && $attribute_as_radio[$size_attribute]) { $is_radio = true; }
							$do_not_display = (int)$attribute_display[$size_attribute];
							if ($do_not_display) {
								if (!$is_modify) { $aterms_keys = array_keys($aterms); $aval = $aterms_keys[0]; } ?>
								<div class="print-attributes"<?php echo $hide_style; ?>>
									<input type="hidden" name="sattribute[<?php echo $size_attribute; ?>]" class="smatrix-attr<?php echo $attr_class; ?>" value="<?php echo $aval; ?>" data-aid="<?php echo $size_attribute; ?>">
								</div>
							<?php } else { ?>
								<ul class="product-attributes-list print-attributes"<?php echo $hide_style; ?>>
									<li>
										<label><?php echo print_products_attribute_label($size_attribute, $attribute_labels, $attribute_names[$size_attribute]); ?>:</label><br />
										<div class="attr-box<?php if ($is_radio) { echo ' attr-radio-box'; } ?>">
											<?php if ($is_radio) { ?>
												<div class="a-radio a-radio-<?php echo $mtype_id; ?>-<?php echo $size_attribute; ?>">
													<?php foreach($aterms as $aterm_id => $aterm_name) { if (!strlen($aval)) { $aval = $aterm_id; } ?>
														<label><input type="radio" name="rattribute[<?php echo $mtype_id.$mattribute; ?>]" value="<?php echo $aterm_id; ?>" rel="smatrix-attr-<?php echo $mtype_id.$size_attribute; ?>" onclick="matrix_aradio(this);"<?php if ($aval == $aterm_id) { echo ' CHECKED'; } ?>><span class="button alt"><?php echo $aterm_name; ?></span></label>
													<?php } ?>
												</div>
											<?php } ?>
											<?php if ($is_radio) { ?><div style="display:none;"><?php } ?>
											<select name="sattribute[<?php echo $size_attribute; ?>]" class="smatrix-attr smatrix-attr-<?php echo $mtype_id.$size_attribute; ?><?php echo $attr_class; ?>" data-aid="<?php echo $size_attribute; ?>"<?php if ($mtypecount[0] == 1) { ?> onchange="matrix_book_size_change(this)"<?php } ?>>
												<?php foreach($aterms as $aterm_id => $aterm_name) { ?>
													<option value="<?php echo $aterm_id; ?>"<?php if ($aval == $aterm_id) { echo ' SELECTED'; } ?>><?php echo $aterm_name; ?></option>
												<?php } ?>
											</select>
											<?php if ($is_radio) { ?></div><?php } ?>
											<?php print_products_attribute_help_icon($size_attribute); ?>
										</div>
									</li>
								</ul>
							<?php
							}
							unset($mattributes[$sizekey]);
						} ?>
						<ul class="product-attributes-list numbers-list">
							<li>
								<label><?php echo $title; ?><?php echo print_products_attribute_label('pquantity', $attribute_labels, __('Pages Quantity', 'wp2print')); ?>:</label><br />
								<?php if ($pq_style == 1 && count($pq_numbers)) { ?>
									<select name="page_quantity_<?php echo $mtype_id; ?>" class="quantity" onchange="matrix_calculate_price()">
										<?php foreach($pq_numbers as $pq_number) { ?>
											<option value="<?php echo $pq_number; ?>"<?php if ($pqval == $pq_number) { echo ' SELECTED'; } ?>><?php echo $pq_number; ?></option>
										<?php } ?>
									</select>
								<?php } else { ?>
									<input type="text" name="page_quantity_<?php echo $mtype_id; ?>" class="quantity" value="<?php if ($pqval) { echo $pqval; } else { echo $def_quantity; } ?>" onblur="matrix_calculate_price()">
								<?php } ?>
							</li>
						</ul>
						<ul class="product-attributes-list print-attributes">
							<?php foreach($mattributes as $mattribute) {
								$matype = $attribute_types[$mattribute];
								$aterms = $materms[$mattribute];
								$aval = '';
								if ($is_modify) {
									$avals = explode(':', $product_attributes[$anmb]);
									$akey = $avals[0];
									$aval = $avals[1];
									$anmb++;
								}
								$is_radio = false;
								if (isset($attribute_as_radio[$mattribute]) && $attribute_as_radio[$mattribute]) { $is_radio = true; }
								if ($matype == 'text') { ?>
									<li class="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
										<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label><br />
										<div class="attr-box">
											<input type="text" name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr smatrix-attr-text" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();"><?php print_products_attribute_help_icon($mattribute); ?>
										</div>
									</li>
									<?php
								} else {
									if ($aterms) {
										$aterms = print_products_get_attribute_terms($aterms);
										$attr_class = '';
										$aimg = $attribute_imgs[$mattribute];
										if ($mattribute == $material_attribute) { $attr_class = ' smatrix-material'; }

										$do_not_display = (int)$attribute_display[$mattribute];
										if ($do_not_display) {
											if (!$is_modify) { $aterms_keys = array_keys($aterms); $aval = $aterms_keys[0]; } ?>
											<input type="hidden" name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr<?php echo $attr_class; ?>" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>">
										<?php } else { ?>
											<li class="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
												<label><?php echo $title; ?><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label><br />
												<div class="attr-box<?php if ($is_radio) { echo ' attr-radio-box'; } ?>">
													<?php if ($is_radio) { ?>
														<div class="a-radio a-radio-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
															<?php foreach($aterms as $aterm_id => $aterm_name) { if (!strlen($aval)) { $aval = $aterm_id; } ?>
																<label><input type="radio" name="rattribute[<?php echo $mtype_id.$mattribute; ?>]" value="<?php echo $aterm_id; ?>" rel="smatrix-attr-<?php echo $mtype_id.$mattribute; ?>" onclick="matrix_aradio(this);"<?php if ($aval == $aterm_id) { echo ' CHECKED'; } ?>><span class="button alt"><?php echo $aterm_name; ?></span></label>
															<?php } ?>
														</div>
													<?php } ?>
													<?php if ($is_radio) { ?><div style="display:none;"><?php } ?>
													<select name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr smatrix-attr-<?php echo $mtype_id.$mattribute; ?><?php echo $attr_class; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();<?php if ($aimg) { ?> matrix_attribute_image(this, <?php echo $mattribute; ?>, <?php echo $mtype_id; ?>);<?php } ?>">
														<?php foreach($aterms as $aterm_id => $aterm_name) { ?>
															<option value="<?php echo $aterm_id; ?>"<?php if ($aval == $aterm_id) { echo ' SELECTED'; } ?>><?php echo $aterm_name; ?></option>
														<?php } ?>
													</select>
													<?php if ($is_radio) { ?></div><?php } ?>
													<?php print_products_attribute_help_icon($mattribute); ?>
												</div>
												<?php if ($aimg) { $showai = false; $ainmb = 1;
													foreach($aterms as $aterm_id => $aterm_name) {
														if ($wp2print_attribute_images[$aterm_id]) { $showai = true; }
													}
													if ($showai) { ?>
														<div class="attribute-images attribute-images-<?php echo $mattribute; ?>">
															<ul>
																<?php foreach($aterms as $aterm_id => $aterm_name) {
																	if ($wp2print_attribute_images[$aterm_id]) { ?>
																		<li><img src="<?php echo print_products_get_thumb($wp2print_attribute_images[$aterm_id], 100, 80, true) ?>" class="attribute-image-<?php echo $aterm_id; ?><?php if ($ainmb == 1) { echo ' active'; } ?>" rel="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>"></li>
																	<?php } ?>
																<?php $ainmb++; } ?>
															</ul>
														</div>
													<?php } ?>
												<?php } ?>
											</li>
										<?php } ?>
									<?php } ?>
								<?php } ?>
							<?php } ?>
						</ul>
					</div>
				<?php } else { // finishing matrix ?>
					<div class="matrix-type-finishing" data-mtid="<?php echo $mtype_id; ?>" data-ntp="<?php echo $num_type; ?>">
						<ul class="product-attributes-list finishing-attributes">
						<?php foreach($mattributes as $mattribute) {
							$matype = $attribute_types[$mattribute];
							$aterms = $materms[$mattribute];
							$aval = '';
							if ($is_modify) {
								$avals = explode(':', $product_attributes[$anmb]);
								$akey = $avals[0];
								$aval = $avals[1];
								$anmb++;
							}
							$is_radio = false;
							if (isset($attribute_as_radio[$mattribute]) && $attribute_as_radio[$mattribute]) { $is_radio = true; }
							if ($matype == 'text') { ?>
								<li class="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
									<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label><br />
									<div class="attr-box">
										<input type="text" name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();"><?php print_products_attribute_help_icon($mattribute); ?>
									</div>
							</li>
								<?php
							} else {
								if ($aterms) {
									$aimg = $attribute_imgs[$mattribute];
									$aterms = print_products_get_attribute_terms($aterms);
									$do_not_display = (int)$attribute_display[$mattribute];
									if ($do_not_display) {
										if (!$is_modify) { $aterms_keys = array_keys($aterms); $aval = $aterms_keys[0]; } ?>
										<input type="hidden" name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>">
									<?php } else { ?>
										<li class="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
											<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label><br />
											<div class="attr-box<?php if ($is_radio) { echo ' attr-radio-box'; } ?>">
												<?php if ($is_radio) { ?>
													<div class="a-radio a-radio-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
														<?php foreach($aterms as $aterm_id => $aterm_name) { if (!strlen($aval)) { $aval = $aterm_id; } ?>
															<label><input type="radio" name="rattribute[<?php echo $mtype_id.$mattribute; ?>]" value="<?php echo $aterm_id; ?>" rel="fmatrix-attr-<?php echo $mtype_id.$mattribute; ?>" onclick="matrix_aradio(this);"<?php if ($aval == $aterm_id) { echo ' CHECKED'; } ?>><span class="button alt"><?php echo $aterm_name; ?></span></label>
														<?php } ?>
													</div>
												<?php } ?>
												<?php if ($is_radio) { ?><div style="display:none;"><?php } ?>
												<select name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr fmatrix-attr-<?php echo $mtype_id.$mattribute; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();<?php if ($aimg) { ?> matrix_attribute_image(this, <?php echo $mattribute; ?>, <?php echo $mtype_id; ?>);<?php } ?>">
													<?php foreach($aterms as $aterm_id => $aterm_name) { ?>
														<option value="<?php echo $aterm_id; ?>"<?php if ($aval == $aterm_id) { echo ' SELECTED'; } ?>><?php echo $aterm_name; ?></option>
													<?php } ?>
												</select>
												<?php if ($is_radio) { ?></div><?php } ?>
												<?php print_products_attribute_help_icon($mattribute); ?>
											</div>
											<?php if ($aimg) { $showai = false; $ainmb = 1;
												foreach($aterms as $aterm_id => $aterm_name) {
													if ($wp2print_attribute_images[$aterm_id]) { $showai = true; }
												}
												if ($showai) { ?>
													<div class="attribute-images attribute-images-<?php echo $mattribute; ?>">
														<ul>
															<?php foreach($aterms as $aterm_id => $aterm_name) {
																if ($wp2print_attribute_images[$aterm_id]) { ?>
																	<li><img src="<?php echo print_products_get_thumb($wp2print_attribute_images[$aterm_id], 100, 80, true) ?>" class="attribute-image-<?php echo $aterm_id; ?><?php if ($ainmb == 1) { echo ' active'; } ?>" rel="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>"></li>
																<?php } ?>
															<?php $ainmb++; } ?>
														</ul>
													</div>
												<?php } ?>
											<?php } ?>
										</li>
									<?php } ?>
								<?php } ?>
							<?php } ?>
						<?php } ?>
						</ul>
					</div>
				<?php } ?>
				<?php
				$lmtype = $mtype;
				$pcnmb++;
			}
		} ?>
		<?php if ($addon_products && is_array($addon_products) && count($addon_products) && !$is_modify) { ?>
			<ul class="product-attributes-list addon-products-list">
				<?php foreach($addon_products as $aproduct_id) {
					$aproduct = wc_get_product($aproduct_id); ?>
					<li>
						<label><?php echo $aproduct->get_name(); ?>:</label><br />
						<input type="text" name="addon_products[<?php echo $aproduct_id; ?>]" value="0" data-price="<?php echo $aproduct->get_price(); ?>" onchange="matrix_calculate_price();" onblur="matrix_calculate_price();" onkeyup="matrix_calculate_price();">
					</li>
				<?php } ?>
			</ul>
		<?php } ?>
		<?php if ($use_production_speed && $production_speed_options && is_array($production_speed_options) && count($production_speed_options)) { ?>
			<ul class="product-attributes-list">
				<li>
					<label><?php echo $production_speed_label; ?>:</label><br />
					<select name="production_speed" class="production-speed" onchange="matrix_production_speed(); matrix_calculate_price();">
						<?php foreach($production_speed_options as $okey => $pso) { ?>
							<option value="<?php echo $okey; ?>" data-p="<?php echo $pso['percent']; ?>"><?php echo $pso['label']; ?></option>
						<?php } ?>
					</select>
				</li>
				<?php if ($production_speed_sd_data && $production_speed_sd_data['show']) {
					if (!strlen($production_speed_sd_data['label'])) { $production_speed_sd_data['label'] = __('Shipment date', 'wp2print'); } ?>
					<li class="shipp-date-row">
						<label><?php echo $production_speed_sd_data['label']; ?>:&nbsp;</label>
						<?php foreach($production_speed_options as $okey => $pso) {
							$sd_val = print_products_get_shipping_date($pso['days'], $production_speed_sd_data['time'], $production_speed_sd_data['weekend']); ?>
							<span class="sd-val sd-<?php echo $okey; ?>"<?php if ($okey > 0) { echo ' style="display:none;"'; } ?>><?php echo $sd_val; ?></span>
						<?php } ?>
					</li>
				<?php } ?>
			</ul>
		<?php } ?>
	 	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
		<?php if ($product_display_price == 'both') { ?>
			<div class="product-price product-price-incl-tax price-incl-tax"><?php _e('Price', 'wp2print'); ?>: <span class="pprice">0.00</span> <?php echo $price_display_incl_suffix; ?></div>
			<div class="product-price product-price-excl-tax price-excl-tax"><span class="pprice">0.00</span> <?php echo $price_display_excl_suffix; ?></div>
		<?php } else if ($product_display_price == 'incl') { ?>
			<div class="product-price price-incl-tax"><?php _e('Price', 'wp2print'); ?>: <span class="pprice">0.00</span> <?php echo $price_display_incl_suffix; ?></div>
		<?php } else { ?>
			<div class="product-price price-excl-tax"><?php _e('Price', 'wp2print'); ?>: <span class="pprice">0.00</span> <?php echo $price_display_excl_suffix; ?></div>
		<?php } ?>

		<?php if ($product_display_weight) { ?><div class="product-weight"><?php _e('Weight', 'wp2print'); ?>: <span class="pweight">0</span> <?php echo print_products_get_weight_unit(); ?></div><?php } ?>
		<div class="product-add-button">
			<?php if ($is_modify) { ?>
				<input type="hidden" name="print_products_checkout_process_action" value="update-cart">
				<input type="hidden" name="product_type" value="book" class="product-type">
				<input type="hidden" name="product_id" value="<?php echo $product->id; ?>" class="product-id">
				<input type="hidden" name="cart_item_key" value="<?php echo $cart_item_key; ?>">
				<input type="submit" value="<?php _e('Update cart', 'wp2print'); ?>" class="single_add_to_cart_button <?php print_products_buttons_class(); ?> update-cart-btn" onclick="return products_add_cart_action();">
			<?php } else { ?>
				<input type="hidden" name="print_products_checkout_process_action" value="add-to-cart">
				<input type="hidden" name="product_type" value="book" class="product-type">
				<input type="hidden" name="product_id" value="<?php echo $product->id; ?>" class="product-id">
				<input type="hidden" name="add-to-cart" value="<?php echo $product->id; ?>">
				<?php if (strlen($artwork_source)) { ?>
					<?php if ($artwork_source == 'artwork' || $artwork_source == 'both') { ?>
						<input type="button" value="<?php _e('Upload your own design', 'wp2print'); ?>" class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt artwork-btn upload-artwork-btn ch-price" onclick="return products_add_cart_action();">
						<?php if ($artwork_allow_later) { ?>
							<button class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt artwork-btn simple-add-btn ch-price" onclick="return products_add_cart_action();"><?php _e('Upload later', 'wp2print'); ?></button>
						<?php } ?>
					<?php } ?>
					<?php if (($artwork_source == 'design' || $artwork_source == 'both') && print_products_designer_installed()) {
						$personalizeclass = 'personalize';
						$window_type = personalize_get_window_type();
						if ($window_type == 'Modal Pop-up window') {
							$personalizeclass .= ' personalizep';
						}
						?>
						<button class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt design-online-btn <?php echo $personalizeclass; ?>" onclick="return products_add_cart_action();"><?php _e('DESIGN ONLINE', 'wp2print'); ?></button>
					<?php } ?>
				<?php } else { ?>
					<input type="submit" value="<?php _e('ADD TO CART', 'wp2print'); ?>" class="single_add_to_cart_button button alt simple-add-btn" onclick="return products_add_cart_action();">
				<?php } ?>
			<?php } ?>
		</div>
		<div class="product-na-text" style="display:none;"><?php _e('This product is not available with the set of options. Please choose another set of options.', 'wp2print'); ?></div>
		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
		<input type="hidden" name="smparams" class="sm-params">
		<input type="hidden" name="fmparams" class="fm-params">
		<input type="hidden" name="atcaction" class="atc-action" value="design">
		<input type="hidden" name="artworkfiles" class="artwork-files" value="">
		<input type="hidden" name="pprice" class="p-price">
		</form>
		<?php print_products_email_quote_form(); ?>
		<link itemprop="availability" href="http://schema.org/<?php echo $product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>" />
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
	var order_min_price = <?php echo $order_min_price; ?>;
	var order_max_price = <?php echo $order_max_price; ?>;

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

	var shipping_base_quantity = new Object();
	var shipping_weights = new Object();
	<?php if (count($product_shipping_base_quantities)) {
		foreach($product_shipping_base_quantities as $psbqkey => $product_shipping_base_quantity) { ?>
			shipping_base_quantity['<?php echo $psbqkey; ?>'] = <?php echo (int)$product_shipping_base_quantity; ?>;
		<?php }
	} ?>
	<?php if (is_array($product_shipping_weights) && count($product_shipping_weights)) {
		foreach($product_shipping_weights as $pswkey => $product_shipping_weight) {
			foreach($product_shipping_weight as $mterm => $sterms) {
				foreach($sterms as $sterm => $weight) { if ($weight) { ?>
					shipping_weights['<?php echo $pswkey.'-'.$mterm.'-'.$sterm; ?>'] = <?php echo (float)$weight; ?>;
				<?php }}
			}
		}
	} ?>

	jQuery(document).ready(function() {
		setTimeout(function(){
			matrix_calculate_price();
		});
	});

	jQuery(document).keypress(function(e) {
		if (e.which == 13) { return false; }
	});

	function matrix_book_size_change(o) {
		var sind = jQuery(o).val();
		if (jQuery('select.matrix-size-hidden').size()) {
			jQuery('.matrix-size-hidden option').removeAttr('selected');
			jQuery('.matrix-size-hidden option[value="'+sind+'"]').attr('selected', 'selected');
		} else {
			jQuery('.matrix-size-hidden').val(sind);
		}
		matrix_calculate_price();
	}

	function matrix_calculate_price() {
		var smparams = '';
		var fmparams = '';
		var pdprice = '<?php echo $product_display_price; ?>';
		var na_price = false;

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

			if (smprice == -1) { na_price = true; }
		});
		jQuery('.sm-params').val(smparams);

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

				if (fmprice == -1) { na_price = true; }
			});
		});
		jQuery('.fm-params').val(fmparams);

		// addon products
		if (jQuery('.addon-products-list').length) {
			var ap_total = 0;
			jQuery('.addon-products-list li input').each(function(){
				var ap_val = parseInt(jQuery(this).val());
				if (isNaN(ap_val)) { ap_val = 0; jQuery(this).val('0'); }
				var ap_price = parseFloat(jQuery(this).attr('data-price'));
				if (ap_val && ap_val > 0) {
					ap_total = ap_total + (ap_price * ap_val);
				}
			});
			price = price + ap_total;
		}

		var show_weight = true;
		if (!na_price) {
			if (bqflag) {
				price = price * books_quantity;
			}
			if (price < 0) { price = 0; }
			price = matrix_production_speed_price(price);
			price = matrix_user_discount_price(price);

			if (order_min_price > 0 && price < order_min_price) {
				price = order_min_price;
			}
			if (order_max_price > 0 && price > order_max_price) {
				jQuery('.add-cart-form .product-price').slideUp();
				jQuery('.product-add-button').slideUp();
				jQuery('.add-cart-form .product-weight').slideUp();
				jQuery('.email-quote-box').slideUp();
				jQuery.colorbox({inline:true, href:"#max-price-message"});
				show_weight = false;
			} else {
				jQuery('.add-cart-form .product-price').slideDown();
				jQuery('.product-add-button').slideDown();
				jQuery('.add-cart-form .product-weight').slideDown();
				jQuery('.email-quote-box').slideDown();
			}

			if (pdprice == 'both' || pdprice == 'incl') {
				jQuery.post('<?php echo site_url('index.php'); ?>',
					{
						AjaxAction: 'product-get-price-with-tax',
						product_id: <?php echo $product_id; ?>,
						price: price
					},
					function(data) {
						var pricewithtax = parseFloat(data);
						jQuery('.product-attributes .price-incl-tax .pprice').html(matrix_html_price(pricewithtax));
					}
				);
			}
			jQuery('.product-attributes .price-excl-tax .pprice').html(matrix_html_price(price));
			jQuery('.add-cart-form .product-na-text').hide();
		} else {
			jQuery('.add-cart-form .product-add-button').hide();
			jQuery('.add-cart-form .product-na-text').slideDown();
			jQuery('.product-attributes .product-price .pprice').html('N/A');
		}

		jQuery('.add-cart-form .p-price').val(price);
		if (show_weight) {
			matrix_shipping_weight();
		}
	}

	function matrix_production_speed_price(p) {
		if (jQuery('select.production-speed').length && p > 0) {
			var psp = parseFloat(jQuery('select.production-speed option:selected').attr('data-p'));
			if (psp > 0) {
				var pp = (p / 100) * psp;
				p = p + pp;
			}
		}
		return p;
	}

	function matrix_production_speed() {
		var psval = jQuery('select.production-speed').val();
		jQuery('.shipp-date-row .sd-val').hide();
		jQuery('.shipp-date-row .sd-'+psval).fadeIn(100);
	}

	function matrix_user_discount_price(p) {
		var udiscount = <?php echo print_products_user_discount_get_discount_amount(); ?>;
		if (udiscount) {
			var discount_price = (p / 100) * udiscount;
			p = p - discount_price;
		}
		return p;
	}

	function matrix_html_price(price) {
		var price_decimals = <?php echo wc_get_price_decimals(); ?>;
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

	function matrix_shipping_weight() {
		var size_val = jQuery('.smatrix-size').eq(0).val();
		var product_weight = 0;
		if (size_val) {
			var mind = 0;
			jQuery('.matrix-type-simple').each(function(){
				var number = jQuery(this).find('.quantity').val();
				var material_val = jQuery(this).find('.smatrix-material').val();
				number = number * books_quantity;
				if (material_val) {
					var swkey = mind+'-'+material_val+'-'+size_val;
					if (shipping_weights[swkey]) {
						var pweight = shipping_weights[swkey];
						if (shipping_base_quantity[mind]) {
							product_weight = product_weight + ((pweight / shipping_base_quantity[mind]) * number);
						} else {
							product_weight = product_weight + (pweight * number);
						}
					}
				}
				mind++;
			});
		}
		if (product_weight) {
			jQuery('.product-weight .pweight').html(product_weight.toFixed(1));
			jQuery('.product-weight').animate({height:'show'}, 100);
		} else {
			jQuery('.product-weight').animate({height:'hide'}, 100);
		}
		matrix_update_theme_shipping_weight(product_weight);
	}

	function matrix_update_theme_shipping_weight(product_weight) {
		if (product_weight) {
			product_weight = product_weight.toFixed(1) + ' <?php echo print_products_get_weight_unit(); ?>';
		}
		jQuery('.woocommerce-product-attributes-item--weight .woocommerce-product-attributes-item__value').html(product_weight);
	}

	function matrix_attribute_image(o, aid, mtp) {
		var aval = jQuery(o).val();
		jQuery('.matrix-attribute-'+mtp+'-'+aid+' .attribute-images-'+aid+' img').removeClass('active');
		jQuery('.matrix-attribute-'+mtp+'-'+aid+' .attribute-images-'+aid+' .attribute-image-'+aval).addClass('active');
	}

	function products_add_cart_action() {
		if (books_quantity < 1) {
			alert("<?php _e('Please enter correct quantity of bound books.', 'wp2print'); ?>");
			return false;
		} else if (total_pages < 1) {
			alert("<?php _e('Please enter correct quantity of pages.', 'wp2print'); ?>");
			return false;
		} else if (price < 0) {
			alert("<?php _e('This combination of size and material not offered. Please select another.', 'wp2print'); ?>");
			return false;
		}
	}
	</script>
	<?php include('product-upload-artwork.php'); ?>
	<div style="display:none;">
		<div id="max-price-message" class="max-price-message">
			<?php echo wpautop($max_price_message); ?>
		</div>
	</div>
<?php } ?>