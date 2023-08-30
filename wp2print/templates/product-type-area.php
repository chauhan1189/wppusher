<?php
global $product, $wpdb, $print_products_settings, $attribute_names, $attribute_types, $attribute_imgs, $wp2print_attribute_images;

$woocommerce_calc_taxes = get_option('woocommerce_calc_taxes');
$woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
$price_display_incl_suffix = get_option('woocommerce_price_display_suffix');
$price_display_excl_suffix = get_option('woocommerce_price_display_excl_suffix');
$print_products_plugin_options = get_option('print_products_plugin_options');

unset($_SESSION['artworkfiles']);

$product_id = $product->id;

$dimension_unit = print_products_get_dimension_unit();

$artwork_source = get_post_meta($product_id, '_artwork_source', true);
$artwork_allow_later = get_post_meta($product_id, '_artwork_allow_later', true);
$artwork_file_count = (int)get_post_meta($product_id, '_artwork_file_count', true);
$artwork_afile_types = get_post_meta($product_id, '_artwork_afile_types', true);
$product_shipping_weights = unserialize(get_post_meta($product_id, '_product_shipping_weights', true));
$product_display_weight = get_post_meta($product_id, '_product_display_weight', true);
$product_display_price = get_post_meta($product_id, '_product_display_price', true);
if (!$product_display_price || $woocommerce_calc_taxes != 'yes' || $woocommerce_prices_include_tax == 'yes') { $product_display_price = 'excl'; }

$size_attribute = $print_products_settings['size_attribute'];
$material_attribute = $print_products_settings['material_attribute'];

$area_product_min_width = get_post_meta($product_id, '_area_min_width', true);
$area_product_max_width = get_post_meta($product_id, '_area_max_width', true);
$area_product_min_height = get_post_meta($product_id, '_area_min_height', true);
$area_product_max_height = get_post_meta($product_id, '_area_max_height', true);
$area_product_min_quantity = (int)get_post_meta($product_id, '_area_min_quantity', true);
$area_product_width_round = get_post_meta($product_id, '_area_width_round', true);
$area_product_height_round = get_post_meta($product_id, '_area_height_round', true);
$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
$attribute_display = (array)get_post_meta($product_id, '_attribute_display', true);
$attribute_as_radio = (array)get_post_meta($product_id, '_attribute_as_radio', true);
$area_unit = (int)get_post_meta($product_id, '_area_unit', true);
$use_production_speed = (int)get_post_meta($product_id, '_use_production_speed', true);
$production_speed_label = get_post_meta($product_id, '_production_speed_label', true);
$production_speed_options = get_post_meta($product_id, '_production_speed_options', true);
$production_speed_sd_data = get_post_meta($product_id, '_production_speed_sd_data', true);
$order_min_price = (float)get_post_meta($product_id, '_order_min_price', true);
$order_max_price = (float)get_post_meta($product_id, '_order_max_price', true);
$addon_products = get_post_meta($product_id, '_addon_products', true);
$max_price_message = $print_products_plugin_options['max_price_message'];

if (!strlen($production_speed_label)) { $production_speed_label = __('Production speed', 'wp2print'); }

if (!$area_product_min_quantity) { $area_product_min_quantity = 1; }

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
						<ul class="product-attributes-list numbers-list">
							<li>
								<label><?php echo print_products_attribute_label('quantity', $attribute_labels, __('Quantity', 'wp2print')); ?>:</label><br />
								<?php if ($num_style == 1) { ?>
									<select name="quantity" class="quantity" onchange="matrix_calculate_price()">
										<?php foreach($numbers as $number) { ?>
											<option value="<?php echo $number; ?>"<?php if ($quantity_val && $quantity_val == $number) { echo ' SELECTED'; } ?>><?php echo $number; ?></option>
										<?php } ?>
									</select>
								<?php } else { ?>
									<input type="text" name="quantity" class="quantity" value="<?php if ($quantity_val) { echo $quantity_val; } else { echo $area_product_min_quantity; } ?>" onblur="matrix_calculate_price()">
								<?php } ?>
							</li>
							<li>
								<label><?php echo print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')); ?> (<?php echo $dimension_unit; ?>):</label><br />
								<input type="text" name="width" class="width" value="<?php if ($additional['width']) { echo $additional['width']; } else { echo $area_product_min_width; } ?>" onblur="matrix_width_action()">
								<div class="area-wh-error width-error">
									<span class="min-width-error"><?php _e('Min width is', 'wp2print'); ?> <?php echo $area_product_min_width.' '.$dimension_unit; ?></span>
									<span class="max-width-error"><?php _e('Max width is', 'wp2print'); ?> <?php echo $area_product_max_width.' '.$dimension_unit; ?></span>
								</div>
							</li>
							<li>
								<label><?php echo print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')); ?> (<?php echo $dimension_unit; ?>):</label><br />
								<input type="text" name="height" class="height" value="<?php if ($additional['height']) { echo $additional['height']; } else { echo $area_product_min_height; } ?>" onblur="matrix_height_action()">
								<div class="area-wh-error height-error">
									<span class="min-height-error"><?php _e('Min height is', 'wp2print'); ?> <?php echo $area_product_min_height.' '.$dimension_unit; ?></span>
									<span class="max-height-error"><?php _e('Max height is', 'wp2print'); ?> <?php echo $area_product_max_height.' '.$dimension_unit; ?></span>
								</div>
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
										$aimg = $attribute_imgs[$mattribute];
										$aterms = print_products_get_attribute_terms($aterms);
										$attr_class = '';
										if ($mattribute == $size_attribute) { $attr_class = ' smatrix-size'; }
										if ($mattribute == $material_attribute) { $attr_class = ' smatrix-material'; }

										$do_not_display = (int)$attribute_display[$mattribute];
										if ($do_not_display) {
											if (!$is_modify) { $aterms_keys = array_keys($aterms); $aval = $aterms_keys[0]; } ?>
											<input type="hidden" name="sattribute[<?php echo $mattribute; ?>]" class="smatrix-attr<?php echo $attr_class; ?>" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>">
										<?php } else { ?>
											<li class="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>">
												<label><?php echo print_products_attribute_label($mattribute, $attribute_labels, $attribute_names[$mattribute]); ?>:</label><br />
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
									$hide_style = ''; $attr_class = 'fmatrix-attr fmatrix-attr-'.$mtype_id.$mattribute;
									if ($mattribute == $size_attribute) { $attr_class = 'fmatrix-size'; $hide_style = ' style="display:none;"'; }
									$do_not_display = (int)$attribute_display[$mattribute];
									if ($do_not_display) {
										if (!$is_modify) { $aterms_keys = array_keys($aterms); $aval = $aterms_keys[0]; } ?>
										<input type="hidden" name="fattribute[<?php echo $mattribute; ?>]" class="fmatrix-attr" value="<?php echo $aval; ?>" data-aid="<?php echo $mattribute; ?>">
									<?php } else { ?>
										<li class="matrix-attribute-<?php echo $mtype_id; ?>-<?php echo $mattribute; ?>"<?php echo $hide_style; ?>>
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
												<select name="fattribute[<?php echo $mattribute; ?>]" class="<?php echo $attr_class; ?>" data-aid="<?php echo $mattribute; ?>" onchange="matrix_calculate_price();<?php if ($aimg) { ?> matrix_attribute_image(this, <?php echo $mattribute; ?>, <?php echo $mtype_id; ?>);<?php } ?>">
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
				<input type="hidden" name="product_type" value="area" class="product-type">
				<input type="hidden" name="product_id" value="<?php echo $product->id; ?>" class="product-id">
				<input type="hidden" name="cart_item_key" value="<?php echo $cart_item_key; ?>">
				<input type="submit" value="<?php _e('Update cart', 'wp2print'); ?>" class="single_add_to_cart_button <?php print_products_buttons_class(); ?> update-cart-btn" onclick="return products_add_cart_action();">
			<?php } else { ?>
				<input type="hidden" name="print_products_checkout_process_action" value="add-to-cart">
				<input type="hidden" name="product_type" value="area" class="product-type">
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

	var shipping_weights = new Object();
	<?php if (is_array($product_shipping_weights) && count($product_shipping_weights)) {
		foreach($product_shipping_weights as $mterm => $weight) { if ($weight) { ?>
			shipping_weights['<?php echo $mterm; ?>'] = <?php echo (float)$weight; ?>;
		<?php }}
	} ?>

	jQuery(document).ready(function() {
		setTimeout(function(){
			matrix_calculate_price();
		});
	});

	jQuery(document).keypress(function(e) {
		if (e.which == 13) { return false; }
	});

	function matrix_calculate_price() {
		var w, h, area_size;
		var smparams = '';
		var fmparams = '';
		var weight_number = 0;
		var pdprice = '<?php echo $product_display_price; ?>';
		var na_price = false;

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

		jQuery('.area-wh-error, .area-wh-error span').hide();
		if (width < min_width) {
			matrix_area_show_wh_error('width', 'min');
			width = min_width;
			jQuery('.product-attributes .width').val(width);
		} else if (width > max_width) {
			matrix_area_show_wh_error('width', 'max');
			width = max_width;
			jQuery('.product-attributes .width').val(width);
		}

		if (height < min_height) {
			matrix_area_show_wh_error('height', 'min');
			height = min_height;
			jQuery('.product-attributes .height').val(height);
		} else if (height > max_height) {
			matrix_area_show_wh_error('height', 'max');
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
			if (ntp == 2) { // total area
				nmb_val = quantity * w * h;
			} else if (ntp == 3) { // total perimeter
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

			var mtweight = matrix_shipping_get_weight(nmb_val, material_val);
			weight_number = weight_number + mtweight;

			if (smprice == -1) { na_price = true; }
		});
		jQuery('.sm-params').val(smparams);

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
			matrix_shipping_weight(weight_number);
		}
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
			return n;
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

	function matrix_shipping_get_weight(number, material_val) {
		var product_weight = 0;
		if (material_val) {
			if (shipping_weights[material_val]) {
				var pweight = shipping_weights[material_val];
				product_weight = pweight * number;
			}
		}
		return product_weight;
	}

	function matrix_shipping_weight(product_weight) {
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

	function matrix_area_show_wh_error(tp, mm) {
		jQuery('.'+tp+'-error .'+mm+'-'+tp+'-error').show();
		jQuery('.'+tp+'-error').animate({height: 'show'}, 200);
		setTimeout(function(){ jQuery('.'+tp+'-error').animate({height: 'hide'}); }, 6000);
	}

	function matrix_attribute_image(o, aid, mtp) {
		var aval = jQuery(o).val();
		jQuery('.matrix-attribute-'+mtp+'-'+aid+' .attribute-images-'+aid+' img').removeClass('active');
		jQuery('.matrix-attribute-'+mtp+'-'+aid+' .attribute-images-'+aid+' .attribute-image-'+aval).addClass('active');
	}

	function products_add_cart_action() {
		if (price < 0) {
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