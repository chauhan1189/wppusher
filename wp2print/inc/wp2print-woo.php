<?php
// Add tab to woocommerce product edit page
add_filter('product_type_selector', 'print_products_selector_matrix');
function print_products_selector_matrix($sarray) {
	$product_types = print_products_get_product_types();
	foreach($product_types as $tpkey => $tpname) {
		$sarray[$tpkey] = $tpname;
	}
	return $sarray;
}

add_action('woocommerce_product_write_panel_tabs', 'print_products_price_matrix_tabs');
function print_products_price_matrix_tabs() {
    ?>
    <li class="file_source_tab hide_if_aec"><a href="#file_source"><span><?php _e('File source', 'wp2print'); ?></span></a></li>
    <li class="attribute_options printing_attributes_tab hide_if_simple hide_if_virtual hide_if_grouped hide_if_external hide_if_variable"><a href="#printingattributes"><span><?php _e('Printing attributes', 'wp2print'); ?></span></a></li>
    <li class="attribute_options finishing_attributes_tab hide_if_simple hide_if_virtual hide_if_grouped hide_if_external hide_if_variable"><a href="#finishingattributes"><span><?php _e('Finishing attributes', 'wp2print'); ?></span></a></li>
    <li class="attribute_options attribute_labels_tab hide_if_simple hide_if_virtual hide_if_grouped hide_if_external hide_if_variable"><a href="#attributelabels"><span><?php _e('Attribute labels', 'wp2print'); ?></span></a></li>
	<li class="attribute_options price_matrix_tab hide_if_simple hide_if_virtual hide_if_grouped hide_if_external hide_if_variable"><a href="#pricematrix"><span><?php _e('Prices', 'wp2print'); ?></span></a></li>
	<li class="variations_options hide_if_simple hide_if_virtual hide_if_grouped hide_if_external hide_if_variable hide_if_area hide_if_book hide_if_aec hide_if_aecbwc hide_if_aecsimple"><a href="#unitpricetable"><span><?php _e('Unit price table', 'wp2print'); ?></span></a></li>
	<li class="linked_product_options hide_if_simple hide_if_virtual hide_if_grouped hide_if_external hide_if_variable hide_if_aec hide_if_aecbwc hide_if_aecsimple"><a href="#add-on-products"><span><?php _e('Add-on products', 'wp2print'); ?></span></a></li>
    <?php
		
}

add_action('woocommerce_product_write_panels', 'print_products_price_matrix_tabs_output');
function print_products_price_matrix_tabs_output() {
    global $post, $wpdb, $attribute_names, $terms_names, $print_products_settings;
	$product_id = $post->ID;
	$product_type = print_products_get_type($product_id);
	$dimension_unit = print_products_get_dimension_unit();
	$dimension_unit_aec = print_products_get_aec_dimension_unit();

	$area_min_width = get_post_meta($product_id, '_area_min_width', true);
	$area_max_width = get_post_meta($product_id, '_area_max_width', true);
	$area_min_height = get_post_meta($product_id, '_area_min_height', true);
	$area_max_height = get_post_meta($product_id, '_area_max_height', true);
	$area_min_quantity = get_post_meta($product_id, '_area_min_quantity', true);
	$area_width_round = get_post_meta($product_id, '_area_width_round', true);
	$area_height_round = get_post_meta($product_id, '_area_height_round', true);
	$area_unit = (int)get_post_meta($product_id, '_area_unit', true);
	$auval = print_products_get_area_unit($area_unit);

	if (!$area_min_quantity) { $area_min_quantity = 1; }

	$num_types = print_products_get_num_types($auval);
	$num_type_labels = print_products_get_num_type_labels($auval);

	$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
	print_products_price_matrix_attr_names_init($attributes);
	$mtype_names = print_products_price_matrix_get_types();
	$rounding_vals = array(125 => 0.125, 250 => 0.25, 500 => 0.5, 1000 => 1);

	$product_type_printing_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
	$product_type_finishing_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 1 ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
	$product_type_matrix_types = array_merge($product_type_printing_matrix_types, $product_type_finishing_matrix_types);

    $personalize = get_post_meta($product_id, 'personalize', true);
	$artwork_source = get_post_meta($product_id, '_artwork_source', true);
	$artwork_allow_later = get_post_meta($product_id, '_artwork_allow_later', true);
	$artwork_file_url = get_post_meta($product_id, '_artwork_file_url', true);
	$artwork_file_url_order = (int)get_post_meta($product_id, '_artwork_file_url_order', true);
	$artwork_file_url_email = (int)get_post_meta($product_id, '_artwork_file_url_email', true);
	$artwork_file_count = (int)get_post_meta($product_id, '_artwork_file_count', true);
	$artwork_afile_types = get_post_meta($product_id, '_artwork_afile_types', true);
	$cart_upload_button = (int)get_post_meta($product_id, '_cart_upload_button', true);
	$cart_upload_button_required = (int)get_post_meta($product_id, '_cart_upload_button_required', true);
	$cart_upload_button_text = get_post_meta($product_id, '_cart_upload_button_text', true);
	$cart_upload_file_types = get_post_meta($product_id, '_cart_upload_file_types', true);
	$use_production_speed = (int)get_post_meta($product_id, '_use_production_speed', true);
	$production_speed_label = get_post_meta($product_id, '_production_speed_label', true);
	$production_speed_options = get_post_meta($product_id, '_production_speed_options', true);
	$order_min_price = get_post_meta($product_id, '_order_min_price', true);
	$order_max_price = get_post_meta($product_id, '_order_max_price', true);
	$unitpricetable = get_post_meta($product_id, '_unitpricetable', true);
	$postage_attribute = (int)$print_products_settings['postage_attribute'];

	if (!$artwork_file_count) { $artwork_file_count = 25; }
	if (!is_array($artwork_afile_types)) { $artwork_afile_types = array(); }
	if (!is_array($cart_upload_file_types)) { $cart_upload_file_types = array(); }
	if (!strlen($cart_upload_button_text)) { $cart_upload_button_text = __('Upload your database', 'wp2print'); }

	$artwsources = array('artwork' => __('Customer uploads file', 'wp2print'));
	$artwsources = apply_filters('print_products_file_source_options', $artwsources);
	$ftypes = array('jpg/jpeg','pdf','png','psd','tif','zip','sitx','gz','tar');
	$aunits = print_products_get_area_units();
	$show_printing_matrix = true;
	if ($product_type == 'area' && !$product_type_printing_matrix_types) {
		$show_printing_matrix = false;
		if (strlen($area_min_width) && strlen($area_min_height) && strlen($area_min_quantity) && strlen($area_unit)) {
			$show_printing_matrix = true;
		}
	}
	?>
	<script>
	jQuery('li.attribute_tab').addClass('<?php echo print_products_tab_classes(); ?>');
	<?php if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') { ?>jQuery('li.file_source_tab').remove();<?php } ?>
	jQuery(document).ready(function(){
		<?php if ($_GET['matrixopt'] == '1') {
			if ($_GET['mtype'] == '1') {
				echo "jQuery('.finishing_attributes_tab a').trigger('click');";
			} else {
				echo "jQuery('.printing_attributes_tab a').trigger('click');";
			}
		} else if ($_GET['matrixp'] == '1') {
			echo "jQuery('.price_matrix_tab a').trigger('click');";
		} ?>
	});</script>
    <div id="file_source" class="panel woocommerce_options_panel">
        <div class="options_group">
			<p class="form-field" style="margin-bottom:0px;">
				<label><?php _e('File source', 'wp2print'); ?>:</label>
				<select name="artwork_source" onchange="artwork_source_change()" class="artwork-source">
					<option value=""><?php _e('No file upload required', 'wp2print'); ?></option>
					<?php foreach($artwsources as $awkey => $awval) { $s = ''; if ($awkey == $artwork_source) { $s = ' SELECTED'; } ?>
						<option value="<?php echo $awkey; ?>"<?php echo $s; ?>><?php echo $awval; ?></option>
					<?php } ?>
				</select>
			</p>
			<p class="form-field no-upload-option" style="margin-top:0px;">
				<label><?php _e('Full URL to file', 'wp2print'); ?>:</label>
				<input type="text" name="artwork_file_url" value="<?php echo $artwork_file_url; ?>" style="width:100%;">
				<input type="checkbox" name="artwork_file_url_order" value="1"<?php if ($artwork_file_url_order == 1) { echo ' CHECKED'; } ?>>&nbsp;&nbsp;<?php _e("Display 'URL to file' in admin Order detail page", 'wp2print'); ?><br>
				<input type="checkbox" name="artwork_file_url_email" value="1"<?php if ($artwork_file_url_email == 1) { echo ' CHECKED'; } ?>>&nbsp;&nbsp;<?php _e("Display 'URL to file' in order confirmation emails", 'wp2print'); ?>
			</p>
			<p class="form-field artwork-option" style="padding-top:0px !important; margin-top:0px;">
				<label><strong><?php _e('Uploads configuration', 'wp2print'); ?></strong>:</label>
			</p>
			<p class="form-field artwork-option" style="padding-top:0px !important; margin:0px;">
				<label><?php _e('Maximum file count', 'wp2print'); ?>:</label>
				<input type="number" name="artwork_file_count" value="<?php echo $artwork_file_count; ?>" style="width:60px;">
			</p>
			<p class="form-field artwork-option" style="padding-top:0px !important; margin:0px;">
				<label><?php _e('Allowed file types', 'wp2print'); ?>:</label>
				<input type="checkbox" name="artwork_afile_types[]" value="all" class="artwork-afile-type-all" onclick="artwork_aftypes_change(1)"<?php if (!count($artwork_afile_types) || in_array('all', $artwork_afile_types)) { echo ' CHECKED'; } ?>> <?php _e('All file types', 'wp2print'); ?>
				<span class="artwork-afile-types-list"><br>
					<?php foreach($ftypes as $ftype) { ?>
						<input type="checkbox" name="artwork_afile_types[]" value="<?php echo $ftype; ?>" class="artwork-afile-type" onclick="artwork_aftypes_change(0)"<?php if (in_array($ftype, $artwork_afile_types)) { echo ' CHECKED'; } ?>> <?php echo $ftype; ?>&nbsp;
					<?php } ?>
				</span>
			</p>
			<p class="form-field artwork-option" style="padding-top:0px !important; margin:0px;">
				<label><?php _e('Allow upload later', 'wp2print'); ?>:</label>
				<input type="checkbox" name="artwork_allow_later" value="1"<?php if ($artwork_allow_later) { echo ' CHECKED'; } ?>>
			</p>
			<p class="form-field">
				<label><?php _e('Display cart upload button', 'wp2print'); ?>:</label>
				<input type="checkbox" name="cart_upload_button" value="1" class="cart-upload-button" onclick="artwork_cart_upload_button_change()"<?php if ($cart_upload_button) { echo ' CHECKED'; } ?>>
			</p>
			<p class="form-field cart-upload-button-option" style="padding-top:0px !important; margin:0px;">
				<label><?php _e('Cart upload is required', 'wp2print'); ?>:</label>
				<input type="checkbox" name="cart_upload_button_required" value="1"<?php if ($cart_upload_button_required) { echo ' CHECKED'; } ?>>
			</p>
			<p class="form-field cart-upload-button-option" style="padding-top:0px !important; margin:0px;">
				<label><?php _e('Cart upload button text', 'wp2print'); ?>:</label>
				<input type="text" name="cart_upload_button_text" value="<?php echo $cart_upload_button_text; ?>" style="width:80%;">
			</p>
			<?php $cub_ftypes = array('csv', 'xls', 'xlsx', 'txt'); ?>
			<p class="form-field cart-upload-button-option" style="padding-top:0px !important; margin:0px;">
				<label><?php _e('Cart upload file types', 'wp2print'); ?>:</label>
				<span>
					<?php foreach($cub_ftypes as $ftype) { ?>
						<input type="checkbox" name="cart_upload_file_types[]" value="<?php echo $ftype; ?>"<?php if (in_array($ftype, $cart_upload_file_types)) { echo ' CHECKED'; } ?>> <?php echo $ftype; ?>&nbsp;&nbsp;
					<?php } ?>
				</span>
			</p>
			<?php if (function_exists('personalize_designer_fields')) { ?>
				<div class="personalize-fields" style="display:none;">
					<p class="form-field">
						<label><strong><?php _e('Designer configuration', 'wp2print'); ?></strong>:</label>
					</p>
					<?php personalize_designer_fields(); ?>
					<input type="hidden" name="personalize" value="<?php echo $personalize; ?>" class="personalize-fld">
				</div>
			<?php } ?>
        </div>	
    </div>
	<script>
	function artwork_source_change() {
		var asval = jQuery('.artwork-source').val();
		if (asval == 'design' || asval == 'both') {
			jQuery('.personalize-fields').show();
			jQuery('.personalize-fld').val('y');
		} else {
			jQuery('.personalize-fields').hide();
			jQuery('.personalize-fld').val('n');
		}
		if (asval == 'artwork' || asval == 'both') {
			jQuery('p.artwork-option').show();
		} else {
			jQuery('p.artwork-option').hide();
		}
		if (asval != '') {
			jQuery('p.no-upload-option').hide();
		} else {
			jQuery('p.no-upload-option').show();
		}
	}
	function artwork_aftypes_change(a) {
		if (a == 1) {
			if (jQuery('.artwork-afile-type-all').is(':checked')) {
				jQuery('.artwork-afile-type').removeAttr('checked');
				jQuery('.artwork-afile-types-list').hide();
			} else {
				jQuery('.artwork-afile-types-list').show();
			}
		} else {
			jQuery('.artwork-afile-type-all').removeAttr('checked');
		}
	}
	function artwork_cart_upload_button_change() {
		var ubch = jQuery('input.cart-upload-button').is(':checked');
		if (ubch) {
			jQuery('.cart-upload-button-option').show();
		} else {
			jQuery('.cart-upload-button-option').hide();
		}
	}
	artwork_source_change();
	artwork_aftypes_change(1);
	artwork_cart_upload_button_change();
	</script>
    <div id="printingattributes" class="panel woocommerce_options_panel" style="padding:10px;">
		<?php if ($post->post_status == 'auto-draft') { ?>
			<p style="color:#FF0000;"><?php _e('Please publish product before adding matrix options.', 'wp2print'); ?></p>
		<?php } else { ?>
			<?php if ($product_type == 'area') { ?>
				<table class="wp-list-table widefat striped">
					<tr>
						<td style="padding-right:0px;"><?php _e('Min Width', 'wp2print'); ?> (<?php echo $dimension_unit; ?>): <span style="color:#FF0000;">*</span></td>
						<td style="padding-left:0px;"><input type="text" name="area_min_width" value="<?php echo $area_min_width; ?>" style="width:70px;"></td>
						<td style="padding-right:0px;"><?php _e('Max Width', 'wp2print'); ?> (<?php echo $dimension_unit; ?>):</td>
						<td style="padding-left:0px;"><input type="text" name="area_max_width" value="<?php echo $area_max_width; ?>" style="width:70px;"></td>
						<td style="padding-right:0px;"><?php _e('Rounding', 'wp2print'); ?>:</td>
						<td style="padding-left:0px;"><select name="area_width_round">
							<option value="0">None</option>
							<?php foreach($rounding_vals as $rkey => $rval) { $s = ''; if ($rkey == $area_width_round) { $s = ' SELECTED'; } ?>
								<option value="<?php echo $rkey; ?>"<?php echo $s; ?>><?php echo $rval; ?></option>
							<?php } ?>
						</select></td>
					</tr>
					<tr>
						<td style="padding-right:0px;"><?php _e('Min Height', 'wp2print'); ?> (<?php echo $dimension_unit; ?>): <span style="color:#FF0000;">*</span></td>
						<td style="padding-left:0px;"><input type="text" name="area_min_height" value="<?php echo $area_min_height; ?>" style="width:70px;"></td>
						<td style="padding-right:0px;"><?php _e('Max Height', 'wp2print'); ?> (<?php echo $dimension_unit; ?>):</td>
						<td style="padding-left:0px;"><input type="text" name="area_max_height" value="<?php echo $area_max_height; ?>" style="width:70px;"></td>
						<td style="padding-right:0px;"><?php _e('Rounding', 'wp2print'); ?>:</td>
						<td style="padding-left:0px;"><select name="area_height_round">
							<option value="0">None</option>
							<?php foreach($rounding_vals as $rkey => $rval) { $s = ''; if ($rkey == $area_height_round) { $s = ' SELECTED'; } ?>
								<option value="<?php echo $rkey; ?>"<?php echo $s; ?>><?php echo $rval; ?></option>
							<?php } ?>
						</select></td>
					</tr>
					<tr>
						<td style="padding-right:0px;"><?php _e('Min Quantity', 'wp2print'); ?>: <span style="color:#FF0000;">*</span></td>
						<td style="padding-left:0px;"><input type="text" name="area_min_quantity" value="<?php echo $area_min_quantity; ?>" style="width:70px;"></td>
						<td style="padding-right:0px;"><?php _e('Total Areas', 'wp2print'); ?>: <span style="color:#FF0000;">*</span></td>
						<td style="padding-left:0px;">
							<select name="area_unit">
								<?php foreach($aunits as $aukey => $auval) { ?>
									<option value="<?php echo $aukey; ?>"<?php if ($aukey == $area_unit) { echo ' SELECTED'; } ?>><?php echo $auval; ?></option>
								<?php } ?>
							</select>
						</td>
						<td colspan="2">&nbsp;</td>
					</tr>
				</table><br />
			<?php } ?>
			<?php if ($show_printing_matrix) { ?>
				<?php if ($product_type_printing_matrix_types) { ?>
					<table class="wp-list-table widefat striped">
						<tr>
							<td><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
							<td><strong><?php _e('Quantities', 'wp2print'); ?></strong></td>
							<td align="center"><strong><?php _e('Display Sort Order', 'wp2print'); ?></strong></td>
							<td align="center"><strong><?php _e('Action', 'wp2print'); ?></strong></td>
						</tr>
						<?php foreach($product_type_printing_matrix_types as $product_type_matrix_type) {
							$mtype_id = $product_type_matrix_type->mtype_id;
							$mtattributes = unserialize($product_type_matrix_type->attributes);
							$numbers = $product_type_matrix_type->numbers;
							$sorder = $product_type_matrix_type->sorder;
							$num_type = $product_type_matrix_type->num_type;
							$mtattr_names = print_products_price_matrix_attr_names($mtattributes);
							?>
							<tr>
								<td><?php echo implode(', ', $mtattr_names); ?></td>
								<td><div style="max-width:300px;"><?php echo $num_type_labels[$num_type]; ?>: <?php echo $numbers; ?></div></td>
								<td align="center"><?php echo $sorder; ?></td>
								<td align="center"><a href="edit.php?post_type=product&page=print-products-price-matrix-options&pid=<?php echo $product_id; ?>&mtid=<?php echo $mtype_id; ?>" class="button-primary" style="width:60px;margin-bottom:4px;"><?php _e('edit', 'wp2print'); ?></a><br><a href="edit.php?post_type=product&page=print-products-price-matrix-options&pid=<?php echo $product_id; ?>&mtid=<?php echo $mtype_id; ?>&mtype=0&print_products_price_matrix_types_action=delete" class="button-primary" onclick="return confirm('<?php _e('Are you sure?', 'wp2print'); ?>');" style="width:60px;"><?php _e('delete', 'wp2print'); ?></a></td>
							</tr>
						<?php } ?>
					</table><br>
				<?php } else {
					echo '<hr><p>'.__('No Printing attributes', 'wp2print').'</p><hr>';
				} ?>
				<a href="edit.php?post_type=product&page=print-products-price-matrix-options&pid=<?php echo $product_id; ?>&pmtaction=add&mtype=0" class="button-primary"><?php _e('Add new printing price matrix', 'wp2print'); ?></a>
				<?php } else { ?>
					<p style="color:#FF0000;"><?php _e('Please fill required area data before adding printing attributes.', 'wp2print'); ?></p>
			<?php } ?>
		<?php } ?>
    </div>
    <div id="finishingattributes" class="panel woocommerce_options_panel" style="padding:10px;">
		<?php if ($post->post_status == 'auto-draft') { ?>
			<p style="color:#FF0000;"><?php _e('Please publish product before adding matrix options.', 'wp2print'); ?></p>
		<?php } else {
			if ($product_type_finishing_matrix_types) { ?>
				<table class="wp-list-table widefat striped">
					<tr>
						<td><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
						<td><strong><?php _e('Quantities', 'wp2print'); ?></strong></td>
						<td align="center"><strong><?php _e('Display Sort Order', 'wp2print'); ?></strong></td>
						<td align="center"><strong><?php _e('Action', 'wp2print'); ?></strong></td>
					</tr>
					<?php foreach($product_type_finishing_matrix_types as $product_type_matrix_type) {
						$mtype_id = $product_type_matrix_type->mtype_id;
						$mtattributes = unserialize($product_type_matrix_type->attributes);
						$numbers = $product_type_matrix_type->numbers;
						$sorder = $product_type_matrix_type->sorder;
						$num_type = $product_type_matrix_type->num_type;
						$mtattr_names = print_products_price_matrix_attr_names($mtattributes);
						?>
						<tr>
							<td><?php echo implode(', ', $mtattr_names); ?></td>
							<td><div style="max-width:300px;"><?php echo $num_type_labels[$num_type]; ?>: <?php echo $numbers; ?></div></td>
							<td align="center"><?php echo $sorder; ?></td>
							<td align="center"><a href="edit.php?post_type=product&page=print-products-price-matrix-options&pid=<?php echo $product_id; ?>&mtid=<?php echo $mtype_id; ?>" class="button-primary" style="width:60px;margin-bottom:4px;"><?php _e('edit', 'wp2print'); ?></a><br><a href="edit.php?post_type=product&page=print-products-price-matrix-options&pid=<?php echo $product_id; ?>&mtid=<?php echo $mtype_id; ?>&mtype=1&print_products_price_matrix_types_action=delete" class="button-primary" onclick="return confirm('<?php _e('Are you sure?', 'wp2print'); ?>');" style="width:60px;"><?php _e('delete', 'wp2print'); ?></a></td>
						</tr>
					<?php } ?>
				</table><br>
			<?php } else {
				echo '<hr><p>'.__('No Finishing attributes', 'wp2print').'</p><hr>';
			} ?>
			<a href="edit.php?post_type=product&page=print-products-price-matrix-options&pid=<?php echo $product_id; ?>&pmtaction=add&mtype=1" class="button-primary"><?php _e('Add new finishing price matrix', 'wp2print'); ?></a>
		<?php } ?>
    </div>
    <div id="attributelabels" class="panel woocommerce_options_panel" style="padding:10px;">
		<?php 
		$attribute_labels = get_post_meta($product_id, '_attribute_labels', true);
		$attribute_display = get_post_meta($product_id, '_attribute_display', true);
		$attribute_as_radio = get_post_meta($product_id, '_attribute_as_radio', true);
		if (!is_array($attribute_labels)) { $attribute_labels = array(); }
		if (!is_array($attribute_display)) { $attribute_display = array(); }
		if (!is_array($attribute_as_radio)) { $attribute_as_radio = array(); }
		if ($product_type == 'book') { ?>
			<table class="wp-list-table widefat striped">
				<tr>
					<td style="width:30%;vertical-align:middle;"><?php _e('Books quantity label', 'wp2print'); ?></td>
					<td><input type="text" name="attribute_labels[bquantity]" value="<?php echo $attribute_labels['bquantity']; ?>" style="width:200px;" placeholder="<?php _e('Quantity of bound books', 'wp2print'); ?>"></td>
				</tr>
				<tr>
					<td style="vertical-align:middle;"><?php _e('Pages quantity label', 'wp2print'); ?></td>
					<td><input type="text" name="attribute_labels[pquantity]" value="<?php echo $attribute_labels['pquantity']; ?>" style="width:200px;" placeholder="<?php _e('Pages Quantity', 'wp2print'); ?>"></td>
				</tr>
			</table><br>
		<?php } else if ($product_type == 'area') { ?>
			<table class="wp-list-table widefat striped">
				<tr>
					<td style="width:30%;vertical-align:middle;"><?php _e('Quantity label', 'wp2print'); ?></td>
					<td><input type="text" name="attribute_labels[quantity]" value="<?php echo $attribute_labels['quantity']; ?>" style="width:200px;" placeholder="<?php _e('Quantity', 'wp2print'); ?>"></td>
				</tr>
				<tr>
					<td style="vertical-align:middle;"><?php _e('Width label', 'wp2print'); ?></td>
					<td><input type="text" name="attribute_labels[width]" value="<?php echo $attribute_labels['width']; ?>" style="width:200px;" placeholder="<?php _e('Width', 'wp2print'); ?>"></td>
				</tr>
				<tr>
					<td style="vertical-align:middle;"><?php _e('Height label', 'wp2print'); ?></td>
					<td><input type="text" name="attribute_labels[height]" value="<?php echo $attribute_labels['height']; ?>" style="width:200px;" placeholder="<?php _e('Height', 'wp2print'); ?>"></td>
				</tr>
			</table><br>
		<?php } else { ?>
			<table class="wp-list-table widefat striped">
				<tr>
					<td style="width:30%;vertical-align:middle;"><?php _e('Quantity label', 'wp2print'); ?></td>
					<td><input type="text" name="attribute_labels[quantity]" value="<?php echo $attribute_labels['quantity']; ?>" style="width:200px;" placeholder="<?php _e('Quantity', 'wp2print'); ?>"></td>
				</tr>
			</table><br>
		<?php } ?>
		<?php if ($product_type_printing_matrix_types || $product_type_finishing_matrix_types) {
			$pattributes = array();
			if ($product_type_printing_matrix_types) {
				foreach($product_type_printing_matrix_types as $product_type_matrix_type) {
					$mtattributes = unserialize($product_type_matrix_type->attributes);
					if ($mtattributes) {
						foreach($mtattributes as $mtattribute) {
							if (!in_array($mtattribute, $pattributes)) {
								$pattributes[] = $mtattribute;
							}
						}
					}
				}
			}
			if ($product_type_finishing_matrix_types) {
				foreach($product_type_finishing_matrix_types as $product_type_matrix_type) {
					$mtattributes = unserialize($product_type_matrix_type->attributes);
					if ($mtattributes) {
						foreach($mtattributes as $mtattribute) {
							if (!in_array($mtattribute, $pattributes)) {
								$pattributes[] = $mtattribute;
							}
						}
					}
				}
			}
			print_products_price_matrix_attr_names_init($pattributes);
			?>
			<table class="wp-list-table widefat striped">
				<tr>
					<td style="width:30%;"><strong><?php _e('Attribute', 'wp2print'); ?></strong></td>
					<td><strong><?php _e('Label', 'wp2print'); ?></strong></td>
					<td align="center"><strong><?php _e('Display as radio buttons', 'wp2print'); ?></strong></td>
					<td align="center"><strong><?php _e('Do not display', 'wp2print'); ?></strong></td>
				</tr>
				<?php foreach($pattributes as $pattribute) { ?>
					<tr>
						<td style="vertical-align:middle;"><?php echo $attribute_names[$pattribute]; ?></td>
						<td><input type="text" name="attribute_labels[<?php echo $pattribute; ?>]" value="<?php echo $attribute_labels[$pattribute]; ?>" style="width:200px;" placeholder="<?php echo $attribute_names[$pattribute]; ?>"></td>
						<td align="center"><input type="checkbox" name="attribute_as_radio[<?php echo $pattribute; ?>]" value="1" <?php if ($attribute_as_radio[$pattribute] == 1) { echo ' CHECKED'; } ?>></td>
						<td align="center"><input type="checkbox" name="attribute_display[<?php echo $pattribute; ?>]" value="1" <?php if ($attribute_display[$pattribute] == 1) { echo ' CHECKED'; } ?>></td>
					</tr>
					<?php if ($pattribute == $postage_attribute) { ?>
						<tr>
							<td style="vertical-align:middle;"><?php _e('Quantity Mailed', 'wp2print'); ?></td>
							<td><input type="text" name="attribute_labels[qm]" value="<?php echo $attribute_labels['qm']; ?>" style="width:200px;" placeholder="<?php _e('Quantity Mailed', 'wp2print'); ?>"></td>
							<td align="center">&nbsp;</td>
							<td align="center">&nbsp;</td>
						</tr>
					<?php } ?>
				<?php } ?>
			</table><br>
		<?php } else {
			echo '<hr><p>'.__('No selected attributes', 'wp2print').'</p><hr>';
		} ?>
    </div>
    <div id="pricematrix" class="panel woocommerce_options_panel" style="padding:15px;">
		<?php if ($post->post_status == 'auto-draft') { ?>
			<p style="color:#FF0000;"><?php _e('Please publish product before adding price matrix.', 'wp2print'); ?></p>
		<?php } else {
			if ($product_type == 'aec') {
				$inc_coverage_prices = (array)get_post_meta($product_id, '_inc_coverage_prices', true);
				$apply_round_up = (int)get_post_meta($product_id, '_apply_round_up', true);
				$round_up_discounts = (array)get_post_meta($product_id, '_round_up_discounts', true);
				$aec_coverage_ranges = print_products_get_aec_coverage_ranges();
				$price_decimals = wc_get_price_decimals();
				$lcrange = 0;
				$icp_vals = array();
				foreach($aec_coverage_ranges as $crange) {
					$icp_vals[$crange] = $lcrange.'% - '.$crange.'%';
					$lcrange = $crange;
				}
				if ($product_type_printing_matrix_types) {
					$material_attribute = $print_products_settings['material_attribute'];
					$material_attrs = array();
					foreach($product_type_printing_matrix_types as $product_type_printing_matrix_type) {
						$mattributes = unserialize($product_type_printing_matrix_type->attributes);
						$materms = unserialize($product_type_printing_matrix_type->aterms);
						if (in_array($material_attribute, $mattributes)) {
							$material_attrs = $materms[$material_attribute];
						}
					}
					$disc_numbers = array(1, 10, 50, 100, 1000);
					if (count($material_attrs)) { ?>
						<div class="inc-coverage-box">
							<table cellpadding="0" cellspacing="0">
								<tr>
									<td><strong><?php _e('Paper Type', 'wp2print'); ?></strong>&nbsp;&nbsp;</td>
									<td><strong><?php _e('Coverage', 'wp2print'); ?> %</strong>&nbsp;&nbsp;</td>
									<td><strong><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit_aec; ?><sup>2</sup></strong></td>
								</tr>
								<?php foreach($material_attrs as $material_attr) { $material_attr = (int)$material_attr; ?>
									<?php foreach($icp_vals as $icp_val => $icp_label) { ?>
										<tr>
											<td><?php echo $terms_names[$material_attr]; ?>&nbsp;&nbsp;</td>
											<td align="center"><?php echo $icp_label; ?></td>
											<td><div style="float:left;padding:3px 3px 0 0;">$</div><input type="text" name="inc_coverage_prices[<?php echo $material_attr; ?>][<?php echo $icp_val; ?>]" value="<?php echo number_format((float)$inc_coverage_prices[$material_attr][$icp_val], $price_decimals); ?>" style="width:50px;"></td>
										</tr>
									<?php } ?>
								<?php } ?>
							</table>
						</div>
						<div class="round-up-box">
							<table>
								<tr>
									<td><input type="checkbox" name="apply_round_up" value="1" class="apply-round-up"<?php if ($apply_round_up == 1) { echo ' CHECKED'; } ?>></td>
									<td><?php _e('Round-up area to nearest', 'wp2print'); ?> <?php echo $dimension_unit_aec; ?><sup>2</sup></td>
								</tr>
							</table>
							<table cellpadding="0" cellspacing="0" class="round-up-discount"<?php if ($apply_round_up != 1) { echo ' style="display:none;"'; } ?>>
								<tr>
									<td colspan="5" style="text-align:center;"><?php _e('Volume discount', 'wp2print'); ?></td>
								</tr>
								<tr>
									<?php foreach($disc_numbers as $disc_number) { ?>
										<td><strong><?php echo $disc_number; ?><?php echo $dimension_unit_aec; ?><sup>2</sup></strong>&nbsp;&nbsp;</td>
									<?php } ?>
								</tr>
								<tr>
									<?php foreach($disc_numbers as $disc_number) { ?>
										<td><input type="text" name="round_up_discounts[<?php echo $disc_number; ?>]" value="<?php echo $round_up_discounts[$disc_number]; ?>"><div>%</div></td>
									<?php } ?>
								</tr>
							</table>
						</div>
					<?php
					} else {
						echo '<p style="color:#FF0000;">'.__('No Paper Type selected.', 'wp2print').'</p>';
					}
				} else {
					echo '<p style="color:#FF0000;">'.__('You must create Price Matrix Options first.', 'wp2print').'</p>';
				}
				if ($product_type_finishing_matrix_types) { $nmb = 1; ?>
					<div style="border-top:1px solid #eee; margin-top:10px; padding-top:10px;">
						<table width="100%">
							<tr>
								<td width="60%"><strong><?php _e('Select price matrix type', 'wp2print'); ?>:</strong></td>
								<td><strong><?php _e('Proportional quantity', 'wp2print'); ?></strong></td>
							</tr>
							<?php
							foreach($product_type_finishing_matrix_types as $product_type_finishing_matrix_type) {
								$mtattributes = unserialize($product_type_finishing_matrix_type->attributes);
								$mtattr_names = print_products_price_matrix_attr_names($mtattributes);
								?>
								<tr>
									<td><?php echo $nmb; ?>. <a href="edit.php?post_type=product&page=print-products-price-matrix-values&mtype_id=<?php echo $product_type_finishing_matrix_type->mtype_id; ?>"><?php echo $mtype_names[$product_type_finishing_matrix_type->mtype]; ?> (<?php echo implode(', ', $mtattr_names); ?>)</a></td>
									<td><?php echo $num_types[$product_type_finishing_matrix_type->num_type]; ?></td>
								</tr>
							<?php $nmb++; } ?>
						</table>
					</div>
					<?php
				}
			} else if ($product_type == 'aecbwc') {
				$inc_coverage_prices = (array)get_post_meta($product_id, '_inc_coverage_prices', true);
				$apply_round_up = (int)get_post_meta($product_id, '_apply_round_up', true);
				$round_up_discounts = (array)get_post_meta($product_id, '_round_up_discounts', true);
				$price_decimals = wc_get_price_decimals();
				if ($product_type_printing_matrix_types) {
					$material_attribute = $print_products_settings['material_attribute'];
					$material_attrs = array();
					foreach($product_type_printing_matrix_types as $product_type_printing_matrix_type) {
						$mattributes = unserialize($product_type_printing_matrix_type->attributes);
						$materms = unserialize($product_type_printing_matrix_type->aterms);
						if (in_array($material_attribute, $mattributes)) {
							$material_attrs = $materms[$material_attribute];
						}
					}
					$disc_numbers = array(1, 10, 50, 100, 1000);
					if (count($material_attrs)) { ?>
						<div class="inc-coverage-box">
							<h3 class="hndle" style="padding:0 0 8px;border:none;"><?php _e('Black/White pages', 'wp2print'); ?>:</h3>
							<table cellpadding="0" cellspacing="0">
								<tr>
									<td><strong><?php _e('Paper Type', 'wp2print'); ?></strong>&nbsp;&nbsp;</td>
									<td><strong><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit_aec; ?><sup>2</sup></strong></td>
								</tr>
								<?php foreach($material_attrs as $material_attr) { $material_attr = (int)$material_attr; ?>
									<tr>
										<td><?php echo $terms_names[$material_attr]; ?>&nbsp;&nbsp;</td>
										<td><div style="float:left;padding:3px 3px 0 0;">$</div><input type="text" name="inc_coverage_prices[0][<?php echo $material_attr; ?>]" value="<?php echo number_format((float)$inc_coverage_prices[0][$material_attr], $price_decimals); ?>" style="width:50px;"></td>
									</tr>
								<?php } ?>
							</table>
							<h3 class="hndle" style="padding:10px 0 8px;border:none;"><?php _e('Color pages', 'wp2print'); ?>:</h3>
							<table cellpadding="0" cellspacing="0">
								<tr>
									<td><strong><?php _e('Paper Type', 'wp2print'); ?></strong>&nbsp;&nbsp;</td>
									<td><strong><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit_aec; ?><sup>2</sup></strong></td>
								</tr>
								<?php foreach($material_attrs as $material_attr) { $material_attr = (int)$material_attr; ?>
									<tr>
										<td><?php echo $terms_names[$material_attr]; ?>&nbsp;&nbsp;</td>
										<td><div style="float:left;padding:3px 3px 0 0;">$</div><input type="text" name="inc_coverage_prices[1][<?php echo $material_attr; ?>]" value="<?php echo number_format((float)$inc_coverage_prices[1][$material_attr], $price_decimals); ?>" style="width:50px;"></td>
									</tr>
								<?php } ?>
							</table>
						</div>
						<div class="round-up-box">
							<table>
								<tr>
									<td><input type="checkbox" name="apply_round_up" value="1" class="apply-round-up"<?php if ($apply_round_up == 1) { echo ' CHECKED'; } ?>></td>
									<td><?php _e('Round-up area to nearest', 'wp2print'); ?> <?php echo $dimension_unit_aec; ?><sup>2</sup></td>
								</tr>
							</table>
							<table cellpadding="0" cellspacing="0" class="round-up-discount"<?php if ($apply_round_up != 1) { echo ' style="display:none;"'; } ?>>
								<tr>
									<td colspan="5" style="text-align:center;"><?php _e('Volume discount', 'wp2print'); ?></td>
								</tr>
								<tr>
									<?php foreach($disc_numbers as $disc_number) { ?>
										<td><strong><?php echo $disc_number; ?><?php echo $dimension_unit_aec; ?><sup>2</sup></strong>&nbsp;&nbsp;</td>
									<?php } ?>
								</tr>
								<tr>
									<?php foreach($disc_numbers as $disc_number) { ?>
										<td><input type="text" name="round_up_discounts[<?php echo $disc_number; ?>]" value="<?php echo $round_up_discounts[$disc_number]; ?>"><div>%</div></td>
									<?php } ?>
								</tr>
							</table>
						</div>
					<?php
					} else {
						echo '<p style="color:#FF0000;">'.__('No Paper Type selected.', 'wp2print').'</p>';
					}
				} else {
					echo '<p style="color:#FF0000;">'.__('You must create Price Matrix Options first.', 'wp2print').'</p>';
				}
				if ($product_type_finishing_matrix_types) { $nmb = 1; ?>
					<div style="border-top:1px solid #eee; margin-top:10px; padding-top:10px;">
						<table width="100%">
							<tr>
								<td width="60%"><strong><?php _e('Select price matrix type', 'wp2print'); ?>:</strong></td>
								<td><strong><?php _e('Proportional quantity', 'wp2print'); ?></strong></td>
							</tr>
							<?php
							foreach($product_type_finishing_matrix_types as $product_type_finishing_matrix_type) {
								$mtattributes = unserialize($product_type_finishing_matrix_type->attributes);
								$mtattr_names = print_products_price_matrix_attr_names($mtattributes);
								?>
								<tr>
									<td><?php echo $nmb; ?>. <a href="edit.php?post_type=product&page=print-products-price-matrix-values&mtype_id=<?php echo $product_type_finishing_matrix_type->mtype_id; ?>"><?php echo $mtype_names[$product_type_finishing_matrix_type->mtype]; ?> (<?php echo implode(', ', $mtattr_names); ?>)</a></td>
									<td><?php echo $num_types[$product_type_finishing_matrix_type->num_type]; ?></td>
								</tr>
							<?php $nmb++; } ?>
						</table>
					</div>
					<?php
				}
			} else if ($product_type == 'aecsimple') {
				$inc_coverage_prices = (array)get_post_meta($product_id, '_inc_coverage_prices', true);
				$apply_round_up = (int)get_post_meta($product_id, '_apply_round_up', true);
				$round_up_discounts = (array)get_post_meta($product_id, '_round_up_discounts', true);
				$price_decimals = wc_get_price_decimals();
				if ($product_type_printing_matrix_types) {
					$material_attribute = $print_products_settings['material_attribute'];
					$material_attrs = array();
					foreach($product_type_printing_matrix_types as $product_type_printing_matrix_type) {
						$mattributes = unserialize($product_type_printing_matrix_type->attributes);
						$materms = unserialize($product_type_printing_matrix_type->aterms);
						if (in_array($material_attribute, $mattributes)) {
							$material_attrs = $materms[$material_attribute];
						}
					}
					$disc_numbers = array(1, 10, 50, 100, 1000);
					if (count($material_attrs)) { ?>
						<div class="inc-coverage-box">
							<table cellpadding="0" cellspacing="0">
								<tr>
									<td><strong><?php _e('Paper Type', 'wp2print'); ?></strong>&nbsp;&nbsp;</td>
									<td><strong><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit_aec; ?><sup>2</sup></strong></td>
								</tr>
								<?php foreach($material_attrs as $material_attr) { $material_attr = (int)$material_attr; ?>
									<tr>
										<td><?php echo $terms_names[$material_attr]; ?>&nbsp;&nbsp;</td>
										<td><div style="float:left;padding:3px 3px 0 0;">$</div><input type="text" name="inc_coverage_prices[<?php echo $material_attr; ?>]" value="<?php echo number_format((float)$inc_coverage_prices[$material_attr], $price_decimals); ?>" style="width:50px;"></td>
									</tr>
								<?php } ?>
							</table>
						</div>
						<div class="round-up-box">
							<table>
								<tr>
									<td><input type="checkbox" name="apply_round_up" value="1" class="apply-round-up"<?php if ($apply_round_up == 1) { echo ' CHECKED'; } ?>></td>
									<td><?php _e('Round-up area to nearest', 'wp2print'); ?> <?php echo $dimension_unit_aec; ?><sup>2</sup></td>
								</tr>
							</table>
							<table cellpadding="0" cellspacing="0" class="round-up-discount"<?php if ($apply_round_up != 1) { echo ' style="display:none;"'; } ?>>
								<tr>
									<td colspan="5" style="text-align:center;"><?php _e('Volume discount', 'wp2print'); ?></td>
								</tr>
								<tr>
									<?php foreach($disc_numbers as $disc_number) { ?>
										<td><strong><?php echo $disc_number; ?><?php echo $dimension_unit_aec; ?><sup>2</sup></strong>&nbsp;&nbsp;</td>
									<?php } ?>
								</tr>
								<tr>
									<?php foreach($disc_numbers as $disc_number) { ?>
										<td><input type="text" name="round_up_discounts[<?php echo $disc_number; ?>]" value="<?php echo $round_up_discounts[$disc_number]; ?>"><div>%</div></td>
									<?php } ?>
								</tr>
							</table>
						</div>
					<?php
					} else {
						echo '<p style="color:#FF0000;">'.__('No Paper Type selected.', 'wp2print').'</p>';
					}
				} else {
					echo '<p style="color:#FF0000;">'.__('You must create Price Matrix Options first.', 'wp2print').'</p>';
				}
				if ($product_type_finishing_matrix_types) { $nmb = 1; ?>
					<div style="border-top:1px solid #eee; margin-top:10px; padding-top:10px;">
						<table width="100%">
							<tr>
								<td width="60%"><strong><?php _e('Select price matrix type', 'wp2print'); ?>:</strong></td>
								<td><strong><?php _e('Proportional quantity', 'wp2print'); ?></strong></td>
							</tr>
							<?php
							foreach($product_type_finishing_matrix_types as $product_type_finishing_matrix_type) {
								$mtattributes = unserialize($product_type_finishing_matrix_type->attributes);
								$mtattr_names = print_products_price_matrix_attr_names($mtattributes);
								?>
								<tr>
									<td><?php echo $nmb; ?>. <a href="edit.php?post_type=product&page=print-products-price-matrix-values&mtype_id=<?php echo $product_type_finishing_matrix_type->mtype_id; ?>"><?php echo $mtype_names[$product_type_finishing_matrix_type->mtype]; ?> (<?php echo implode(', ', $mtattr_names); ?>)</a></td>
									<td><?php echo $num_types[$product_type_finishing_matrix_type->num_type]; ?></td>
								</tr>
							<?php $nmb++; } ?>
						</table>
					</div>
					<?php
				}
			} else {
				if ($product_type_matrix_types) { $nmb = 1; ?>
					<table width="100%">
						<tr>
							<td width="60%"><strong><?php _e('Select price matrix type', 'wp2print'); ?>:</strong></td>
							<td><strong><?php _e('Proportional quantity', 'wp2print'); ?></strong></td>
						</tr>
						<?php
						foreach($product_type_matrix_types as $product_type_matrix_type) {
							$mtattributes = unserialize($product_type_matrix_type->attributes);
							$mtattr_names = print_products_price_matrix_attr_names($mtattributes);
							?>
							<tr>
								<td><?php echo $nmb; ?>. <a href="edit.php?post_type=product&page=print-products-price-matrix-values&mtype_id=<?php echo $product_type_matrix_type->mtype_id; ?>"><?php echo $mtype_names[$product_type_matrix_type->mtype]; ?> (<?php echo implode(', ', $mtattr_names); ?>)</a></td>
								<td><?php echo $num_types[$product_type_matrix_type->num_type]; ?></td>
							</tr>
						<?php $nmb++; } ?>
					</table>
				<?php } else {
					echo '<p style="color:#FF0000;">'.__('You must create Price Matrix Options first.', 'wp2print').'</p>';
				}
			}
			$product_display_price = get_post_meta($product_id, '_product_display_price', true);
			$dpvals = array(
				'excl' => __('Exclude tax price', 'wp2print'),
				'incl' => __('Include tax price', 'wp2print'),
				'both' => __('Include tax and Exclude tax prices', 'wp2print')
			);
			?>
			<div style="border-top:1px solid #eee; margin-top:10px; padding-top:15px;">
				<table style="width:auto;">
					<tr>
						<td><?php _e('Minimum price', 'wp2print'); ?>:</td>
						<td><input type="text" name="order_min_price" value="<?php echo $order_min_price; ?>" style="width:100px;"></td>
					</tr>
					<tr>
						<td><?php _e('Maximum price', 'wp2print'); ?>:</td>
						<td><input type="text" name="order_max_price" value="<?php echo $order_max_price; ?>" style="width:100px;"></td>
					</tr>
				</table>
			</div>
			<div style="margin-top:5px; padding-top:10px;">
				<?php
				if (!$production_speed_options) {
					$production_speed_options = array(
						array('label' => __('Slow', 'wp2print'), 'percent' => 0),
						array('label' => __('Fast', 'wp2print'), 'percent' => 20),
						array('label' => __('Very fast', 'wp2print'), 'percent' => 50)
					);
				}
				if (!strlen($production_speed_label)) { $production_speed_label = __('Production speed', 'wp2print'); }
				$production_speed_sd_data = get_post_meta($product_id, '_production_speed_sd_data', true);
				if (!$production_speed_sd_data) {
					$production_speed_sd_data = array(
						'show' => 0,
						'label' => __('Shipment date', 'wp2print'),
						'weekend' => 1,
						'time' => '13:00'
					);
				}
			?>
				<div class="pso-box">
					<table>
						<tr>
							<td><input type="checkbox" name="use_production_speed" value="1" onclick="wp2print_pso_check(this)" style="margin-top:-3px;"<?php if ($use_production_speed) { echo ' CHECKED'; } ?>>&nbsp;<?php _e("Apply 'Production speed' multiplier", 'wp2print'); ?></td>
						</tr>
					</table>
					<div class="pso-list"<?php if (!$use_production_speed) { echo ' style="display:none;"'; } ?>>
						<table class="pso-label-table">
							<tr>
								<td><?php _e('Dropdown label', 'wp2print'); ?>:</td>
								<td><input type="text" name="production_speed_label" value="<?php echo $production_speed_label; ?>"></td>
							</tr>
						</table>
						<table cellspacing="0" cellpadding="0" class="pso-list-table">
							<tr class="tr-h">
								<td colspan="5"><?php _e('Production speed options list', 'wp2print'); ?></td>
							</tr>
							<tr class="tr-h">
								<td><?php _e('N', 'wp2print'); ?></td>
								<td><?php _e('Label', 'wp2print'); ?></td>
								<td><?php _e('Percent', 'wp2print'); ?>, %</td>
								<td><?php _e('Days', 'wp2print'); ?></td>
								<td><?php _e('Remove', 'wp2print'); ?></td>
							</tr>
							<?php foreach($production_speed_options as $psokey => $pso_option) { ?>
								<tr class="pso-tr pso-tr<?php echo $psokey; ?>">
									<td><?php echo $psokey + 1; ?></td>
									<td><input type="text" name="production_speed_options[<?php echo $psokey; ?>][label]" value="<?php echo $pso_option['label']; ?>"></td>
									<td><input type="text" name="production_speed_options[<?php echo $psokey; ?>][percent]" value="<?php echo $pso_option['percent']; ?>"></td>
									<td><input type="text" name="production_speed_options[<?php echo $psokey; ?>][days]" value="<?php echo (int)$pso_option['days']; ?>"></td>
									<td><a href="#remove" class="pso-rem" onclick="wp2print_pso_remove(<?php echo $psokey; ?>); return false;">X</a></td>
								</tr>
							<?php } ?>
						</table>
						<a href="#add" onclick="wp2print_pso_add(); return false;"><?php _e('Add new row', 'wp2print'); ?></a>
						<div class="pso-data" style="display:none;" data-rmessage="<?php _e('Are you sure?', 'wp2print'); ?>" data-num="<?php echo $psokey + 1; ?>">
							<table>
								<tr class="pso-tr pso-tr{N}">
									<td>0</td>
									<td><input type="text" name="production_speed_options[{N}][label]"></td>
									<td><input type="text" name="production_speed_options[{N}][percent]"></td>
									<td><input type="text" name="production_speed_options[{N}][days]"></td>
									<td><a href="#remove" class="pso-rem" onclick="wp2print_pso_remove({N}); return false;">X</a></td>
								</tr>
							</table>
						</div>
						<table class="pso-sd-ch">
							<tr>
								<td><input type="checkbox" name="production_speed_sd_data[show]" value="1" onclick="wp2print_pso_sd_check(this)" style="margin-top:-3px;"<?php if ($production_speed_sd_data['show']) { echo ' CHECKED'; } ?>>&nbsp;<?php _e("Display Shipment date", 'wp2print'); ?></td>
							</tr>
						</table>
						<div class="pso-sd-data"<?php if (!$production_speed_sd_data['show']) { echo ' style="display:none;"'; } ?>>
							<table>
								<tr>
									<td><?php _e('Text label for shipment date', 'wp2print'); ?>:&nbsp;</td>
									<td><input type="text" name="production_speed_sd_data[label]" value="<?php echo $production_speed_sd_data['label']; ?>"></td>
								</tr>
								<tr>
									<td><?php _e('Exclude weekend days', 'wp2print'); ?>:</td>
									<td><input type="checkbox" name="production_speed_sd_data[weekend]" value="1"<?php if ($production_speed_sd_data['weekend']) { echo ' CHECKED'; } ?>></td>
								</tr>
								<tr>
									<td><?php _e('Order cutoff time', 'wp2print'); ?>:</td>
									<td><select name="production_speed_sd_data[time]">
										<?php for ($h=0; $h<=23; $h++) { ?>
											<?php for ($m=0; $m<60; $m=$m+30) {
												$hm = sprintf("%02d", $h).':'.sprintf("%02d", $m); ?>
												<option value="<?php echo $hm; ?>"<?php if ($production_speed_sd_data['time'] == $hm) { echo ' SELECTED'; } ?>><?php echo $hm; ?></option>
											<?php } ?>
										<?php } ?>
									</select></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<table>
					<tr>
						<td><strong><?php _e('Display Price', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
						<td>
							<select name="product_display_price" class="select short">
								<?php foreach($dpvals as $dpkey => $dpval) { $s = ''; if ($product_display_price == $dpkey) { $s = ' SELECTED'; } ?>
									<option value="<?php echo $dpkey; ?>"<?php echo $s; ?>><?php echo $dpval; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
				</table>
			</div>
		<?php } ?>
    </div>
    <div id="unitpricetable" class="panel woocommerce_options_panel" style="padding:15px;">
		<?php
		if (!is_array($unitpricetable)) {
			$unitpricetable = array(
				'show' => 0,
				'quantities' => '',
				'attribute' => '',
				'aoptions' => array(),
				'unitprices' => 0,
				'utext' => ''
			);
		}
		if (!is_array($unitpricetable['aoptions'])) { $unitpricetable['aoptions'] = array(); }
		$ppattributes = array();
		if ($product_type_printing_matrix_types) {
			foreach($product_type_printing_matrix_types as $product_type_printing_matrix_type) {
				$mattributes = unserialize($product_type_printing_matrix_type->attributes);
				$materms = unserialize($product_type_printing_matrix_type->aterms);
				if ($mattributes) {
					foreach($mattributes as $attr_id) {
						$aterms = array();
						if ($materms && isset($materms[$attr_id])) {
							foreach($materms[$attr_id] as $term_id) {
								$aterms[$term_id] = $term_id;
							}
						}
						$ppattributes[$attr_id] = array('name' => $attribute_names[$attr_id], 'aterms' => $aterms);
					}
				}
			}
		} ?>
		<?php if (!$product_type_printing_matrix_types) { ?>
			<p style="color:#FF0000;"><?php _e('Please add printing attributes firstly.', 'wp2print'); ?></p>
		<?php } else { ?>
			<table class="unit-price-table">
				<tr>
					<td><strong><?php _e('Show unit price table', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
					<td><input type="checkbox" name="unitpricetable[show]" value="1"<?php if ($unitpricetable['show'] == 1) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td><strong><?php _e('Quantity values in columns', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
					<td><input type="text" name="unitpricetable[quantities]" value="<?php echo $unitpricetable['quantities']; ?>" style="width:350px;"></td>
				</tr>
				<tr>
					<td><strong><?php _e('Row attribute', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
					<td><select name="unitpricetable[attribute]" class="upt-attribute" onchange="upt_attribute_change()">
						<option value="">-- <?php _e('Select attribute', 'wp2print'); ?> --</option>
						<?php foreach($ppattributes as $aid => $adata) { ?>
							<option value="<?php echo $aid; ?>"<?php if ($aid == $unitpricetable['attribute']) { echo ' SELECTED'; } ?>><?php echo $adata['name']; ?></option>
						<?php } ?>
					</select></td>
				</tr>
				<tr>
					<td><strong><?php _e('Row attribute items', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
					<td>
						<div class="upt-aoptions upt-aoptions-"<?php if ($unitpricetable['attribute']) { echo ' style="display:none;"'; } ?>><?php _e('Select attribute', 'wp2print'); ?></div>
						<?php foreach($ppattributes as $aid => $adata) { ?>
						<div class="upt-aoptions upt-aoptions-<?php echo $aid; ?>"<?php if ($aid != $unitpricetable['attribute']) { echo ' style="display:none;"'; } ?>>
							<?php foreach($adata['aterms'] as $aterm) { ?>
								<input type="checkbox" name="unitpricetable[aoptions][]" value="<?php echo $aterm; ?>"<?php if (in_array($aterm, $unitpricetable['aoptions'])) { echo ' CHECKED'; } ?>> <?php echo $terms_names[$aterm]; ?><br>
							<?php } ?>
						</div>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php _e('Display unit prices', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
					<td><input type="checkbox" name="unitpricetable[unitprices]" value="1"<?php if ($unitpricetable['unitprices'] == 1) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td><strong><?php _e('Unit text', 'wp2print'); ?>:&nbsp;&nbsp;</strong></td>
					<td><input type="text" name="unitpricetable[utext]" value="<?php echo $unitpricetable['utext']; ?>" style="width:150px;"></td>
				</tr>
			</table>
			<script>
			function upt_attribute_change() {
				var attribute = jQuery('.unit-price-table .upt-attribute').val();
				jQuery('.unit-price-table .upt-aoptions').hide();
				jQuery('.unit-price-table .upt-aoptions input').removeAttr('checked');
				jQuery('.unit-price-table .upt-aoptions-'+attribute).fadeIn();
			}
			</script>
		<?php } ?>
    </div>
    <div id="add-on-products" class="panel woocommerce_options_panel" style="padding:15px;">
		<h2 style="font-weight:700; padding:0 0 5px;"><?php _e('Select Add-on products', 'wp2print'); ?>:</h2>
		<?php
		$wc_products = wc_get_products(array('type' => 'simple'));
		$addon_products = get_post_meta($product_id, '_addon_products', true);
		if (!is_array($addon_products)) { $addon_products = array(); }
		if ($wc_products) { ?>
			<table style="width:auto;">
				<?php foreach($wc_products as $wc_product) {
					$pid = $wc_product->get_id(); ?>
					<tr>
						<td><input type="checkbox" name="addon_products[]" value="<?php echo $pid; ?>"<?php if (in_array($pid, $addon_products)) { echo ' CHECKED'; } ?>></td>
						<td><?php echo $wc_product->get_name(); ?></td>
					</tr>
				<?php } ?>
			</table>
		<?php } ?>
    </div>
    <?php
}

add_action('woocommerce_process_product_meta', 'print_products_price_matrix_process');
function print_products_price_matrix_process($post_id) {
	$product_type = print_products_get_type($post_id);
	if ($product_type == 'area') {
		update_post_meta($post_id, '_area_min_width', $_POST['area_min_width']);
		update_post_meta($post_id, '_area_max_width', $_POST['area_max_width']);
		update_post_meta($post_id, '_area_min_height', $_POST['area_min_height']);
		update_post_meta($post_id, '_area_max_height', $_POST['area_max_height']);
		update_post_meta($post_id, '_area_min_quantity', $_POST['area_min_quantity']);
		update_post_meta($post_id, '_area_width_round', $_POST['area_width_round']);
		update_post_meta($post_id, '_area_height_round', $_POST['area_height_round']);
		update_post_meta($post_id, '_area_unit', $_POST['area_unit']);
	}
	update_post_meta($post_id, '_attribute_labels', $_POST['attribute_labels']);
	update_post_meta($post_id, '_attribute_display', $_POST['attribute_display']);
	update_post_meta($post_id, '_attribute_as_radio', $_POST['attribute_as_radio']);

	// production speed options
	$use_production_speed = (int)$_POST['use_production_speed'];
	$production_speed_label = $_POST['production_speed_label'];
	$production_speed_sd_data = $_POST['production_speed_sd_data'];
	$production_speed_options = '';
	if ($use_production_speed) {
		$pso_options = $_POST['production_speed_options'];
		unset($pso_options['{N}']);
		if ($pso_options && count($pso_options)) {
			$production_speed_options = array();
			foreach($pso_options as $pso_option) {
				$production_speed_options[] = array('label' => $pso_option['label'], 'percent' => $pso_option['percent'], 'days' => $pso_option['days']);
			}
		} else {
			$use_production_speed = 0;
		}
	}
	update_post_meta($post_id, '_use_production_speed', $use_production_speed);
	update_post_meta($post_id, '_production_speed_options', $production_speed_options);
	update_post_meta($post_id, '_production_speed_label', $production_speed_label);
	update_post_meta($post_id, '_production_speed_sd_data', $production_speed_sd_data);
	update_post_meta($post_id, '_order_min_price', $_POST['order_min_price']);
	update_post_meta($post_id, '_order_max_price', $_POST['order_max_price']);
	update_post_meta($post_id, '_unitpricetable', $_POST['unitpricetable']);
	update_post_meta($post_id, '_addon_products', $_POST['addon_products']);
}

add_action('woocommerce_delete_order_item', 'print_products_delete_order_item', 10, 1);
function print_products_delete_order_item($item_id) {
	global $wpdb;
	$wpdb->delete($wpdb->prefix."print_products_order_items", array('item_id' => $item_id));
}

$woocommerce_disable_product_list_price = get_option('woocommerce_disable_product_list_price');
add_filter('wc_get_template', 'print_products_woo_get_template', 10, 2);
function print_products_woo_get_template($located, $template_name) {
	global $product, $woocommerce_disable_product_list_price;
	$print_type = false;
	$artwork_source = false;
	if ($product) {
		$product_type = print_products_get_type($product->id);
		$artwork_source = get_post_meta($product->id, '_artwork_source', true);
		if (print_products_is_wp2print_type($product_type)) { $print_type = true; }
	}

	$ptemplate = '';
	switch ($template_name) {
		case 'loop/price.php':
			if ($woocommerce_disable_product_list_price == 'yes') {
				$ptemplate = 'empty.php';
			}
		break;
		case 'single-product/product-image.php':
			if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') {
				$ptemplate = 'empty.php';
			}
		break;
		case 'single-product/price.php':
			if ($print_type) {
				$ptemplate = 'empty.php';
			}
		break;
		case 'single-product/add-to-cart/simple.php':
			if ($artwork_source) {
				$ptemplate = 'simple.php';
			}
			if ($print_type) {
				$ptemplate = 'product-type-'.$product_type.'.php';
			}
		break;
		case 'single-product/add-to-cart/variable.php':
			if ($artwork_source) {
				$ptemplate = 'variable.php';
			}
			if ($print_type) {
				$ptemplate = 'product-type-'.$product_type.'.php';
			}
		break;
		case 'cart/cart.php':
			if (!print_products_designer_installed()) {
				$ptemplate = 'cart.php';
			}
		break;
		case 'checkout/review-order.php':
			$ptemplate = 'review-order.php';
		break;
		case 'order/order-details-item.php':
			$ptemplate = 'order-details-item.php';
		break;
		case 'emails/email-order-items.php':
			$ptemplate = 'email-order-items.php';
		break;
		case 'emails/email-addresses.php':
			$ptemplate = 'email-addresses.php';
		break;
	}
	if (strlen($ptemplate)) {
		$located = PRINT_PRODUCTS_TEMPLATES_DIR.$ptemplate;
	}
	return $located;
}

add_action('woocommerce_area_add_to_cart', 'print_products_add_to_cart_template');
add_action('woocommerce_book_add_to_cart', 'print_products_add_to_cart_template');
add_action('woocommerce_fixed_add_to_cart', 'print_products_add_to_cart_template');
add_action('woocommerce_aec_add_to_cart', 'print_products_add_to_cart_template');
add_action('woocommerce_aecbwc_add_to_cart', 'print_products_add_to_cart_template');
add_action('woocommerce_aecsimple_add_to_cart', 'print_products_add_to_cart_template');
function print_products_add_to_cart_template() {
	global $product;
	$product_type = print_products_get_type($product->id);
	$template = 'product-type-'.$product_type.'.php';
	include PRINT_PRODUCTS_TEMPLATES_DIR . $template;
}

add_action('woocommerce_before_single_product_summary', 'print_products_before_single_product_summary', 10);
function print_products_before_single_product_summary() {
	global $product;
	$product_type = print_products_get_type($product->id);
	if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') { ?>
		<div class="upload-pdf-processing" style="display:none;"><div class="prtext"><ul><li class="tl"><?php _e('Processing...', 'wp2print'); ?></li></ul></div></div>
		<?php include PRINT_PRODUCTS_TEMPLATES_DIR.'product-type-aec-upload.php'; ?>
	<?php }
}

// add unique key to each cart product
add_filter('woocommerce_add_cart_item_data', 'print_products_woocommerce_add_cart_item_data', 10, 2);
function print_products_woocommerce_add_cart_item_data($cart_item_data, $product_id) {
	if (!print_products_designer_installed()) {
		$cart_item_data['unique_key'] = md5(microtime() . rand() . md5($product_id));
	}
	return $cart_item_data;
}

add_filter('woocommerce_loop_add_to_cart_link', 'print_products_woo_loop_add_to_cart_link', 10, 2);
function print_products_woo_loop_add_to_cart_link($add_to_cart_link, $product) {
	return '';
}

// hide weight fields
//add_filter('wc_product_dimensions_enabled', 'print_products_hide_woo_fields');
add_filter('wc_product_weight_enabled', 'print_products_hide_woo_weight_field');
function print_products_hide_woo_weight_field($enabled) {
	global $wpdb, $print_products_settings;
	if (is_admin() && $_GET['post'] && $_GET['action'] == 'edit') {
		$ptype = print_products_get_type($_GET['post']);
		if (print_products_is_wp2print_type($ptype)) {
			$material_attribute = $print_products_settings['material_attribute'];
			$product_matrix_options = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder", $wpdb->prefix, $_GET['post']));
			if ($product_matrix_options) {
				foreach($product_matrix_options as $pmokey => $product_matrix_option) {
					$aterms = unserialize($product_matrix_option->aterms);
					$tmaterials = $aterms[$material_attribute];
					if ($tmaterials) {
						$enabled = false;
					}
				}
			}
		}
	}
	return $enabled;
}

add_filter('wc_product_sku_enabled', 'print_products_hide_woo_sku_field');
function print_products_hide_woo_sku_field($enabled) {
	if (is_admin() && $_GET['post'] && $_GET['action'] == 'edit') {
		$ptype = print_products_get_type($_GET['post']);
		if (print_products_is_wp2print_type($ptype)) {
			$enabled = false;
		}
	}
	return $enabled;
}

// product shipping (material base)
add_action('woocommerce_product_options_dimensions', 'print_products_product_shipping_options');
function print_products_product_shipping_options() {
	global $post, $wpdb, $print_products_settings;
	$product_id = $post->ID;
	$product_type = print_products_get_type($product_id);
	$size_attribute = $print_products_settings['size_attribute'];
	$material_attribute = $print_products_settings['material_attribute'];
	$page_count_attribute = $print_products_settings['page_count_attribute'];

	if (print_products_is_wp2print_type($product_type)) {
		$weight_unit = print_products_get_weight_unit();
		$square_unit = print_products_get_square_unit();
		$product_shipping_weights = unserialize(get_post_meta($product_id, '_product_shipping_weights', true));
		$product_shipping_base_quantity = get_post_meta($product_id, '_product_shipping_base_quantity', true);
		$product_display_weight = get_post_meta($product_id, '_product_display_weight', true);
		$product_max_weight_per_box = get_post_meta($product_id, '_product_max_weight_per_box', true);

		if ($product_type == 'book') { $product_shipping_base_quantity = unserialize($product_shipping_base_quantity); }

		$product_matrix_options = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder", $wpdb->prefix, $product_id));
		if ($product_matrix_options) {
			foreach($product_matrix_options as $pmokey => $product_matrix_option) {
				$title = $product_matrix_option->title;
				$aterms = unserialize($product_matrix_option->aterms);
				$tsizes = $aterms[$size_attribute];
				$tmaterials = $aterms[$material_attribute];
				$tpagecounts = $aterms[$page_count_attribute];
				if (!is_array($tsizes)) { $tsizes = array(); }
				if (!is_array($tpagecounts)) { $tpagecounts = array(); }
				if ($tmaterials) {
					$terms_names = array();
					$attr_terms = $wpdb->get_results(sprintf("SELECT * FROM %sterms WHERE term_id IN (%s)", $wpdb->prefix, implode(",", array_merge($tmaterials, $tsizes, $tpagecounts))));
					if ($attr_terms) {
						foreach($attr_terms as $attr_term) {
							$terms_names[$attr_term->term_id] = $attr_term->name;
						}
					}
					?>
					<div class="options_group" style="padding:10px;">
						<?php if ($product_type == 'area') { ?>
							<table>
								<tr>
									<td style="width:145px;"><strong><?php _e('Material', 'wp2print'); ?></strong></td>
									<td><strong><?php _e('Weight', 'wp2print'); ?></strong></td>
									<td></td>
								</tr>
								<?php foreach($tmaterials as $tmaterial) { ?>
									<tr>
										<td><?php echo $terms_names[$tmaterial]; ?>&nbsp;&nbsp;</td>
										<td><input type="text" name="product_shipping_weights[<?php echo $tmaterial; ?>]" value="<?php echo $product_shipping_weights[$tmaterial]; ?>" style="width:100px;"></td>
										<td>(<?php echo $weight_unit; ?>/<?php echo $square_unit; ?>)</td>
									</tr>
								<?php } ?>
							</table>
						<?php } else if ($tsizes) { ?>
							<?php if ($product_type == 'book') {
								if (strlen($title)) { $title = '('.$title.') '; } ?>
								<table>
									<tr>
										<td style="width:145px;"><strong><?php echo $title; ?><?php _e('Material', 'wp2print'); ?></strong></td>
										<?php foreach($tsizes as $tsize) { ?>
											<td align="center"><strong><?php echo $terms_names[$tsize]; ?></strong></td>
										<?php } ?>
									</tr>
									<?php foreach($tmaterials as $tmaterial) { ?>
										<tr>
											<td><?php echo $terms_names[$tmaterial]; ?>&nbsp;&nbsp;</td>
											<?php foreach($tsizes as $tsize) { ?>
												<td><input type="text" name="product_shipping_weights[<?php echo $pmokey; ?>][<?php echo $tmaterial; ?>][<?php echo $tsize; ?>]" value="<?php echo $product_shipping_weights[$pmokey][$tmaterial][$tsize]; ?>" style="width:90px;"></td>
											<?php } ?>
										</tr>
									<?php } ?>
								</table>
								<table>
									<tr>
										<td style="width:145px;"><strong><?php echo $title; ?><?php _e('Base Quantity', 'wp2print'); ?></strong></td>
										<td><input type="text" name="product_shipping_base_quantity[<?php echo $pmokey; ?>]" value="<?php echo $product_shipping_base_quantity[$pmokey]; ?>" style="width:90px;"></td>
									</tr>
								</table>
							<?php } else { ?>
								<?php if ($tpagecounts) { ?>
									<div style="width:100%; overflow:auto; margin-bottom:10px;">
									<table>
										<tr>
											<td><strong><?php _e('Material', 'wp2print'); ?>&nbsp;&nbsp;</strong></td>
											<td><strong><?php _e('Size', 'wp2print'); ?>&nbsp;&nbsp;</strong></td>
											<?php foreach($tpagecounts as $tpagecount) { ?>
												<td><strong><?php echo $terms_names[$tpagecount]; ?></strong></td>
											<?php } ?>
										</tr>
										<?php foreach($tmaterials as $tmaterial) { ?>
											<?php foreach($tsizes as $tsize) { ?>
												<tr>
													<td nowrap><?php echo $terms_names[$tmaterial]; ?>&nbsp;&nbsp;</td>
													<td nowrap><?php echo $terms_names[$tsize]; ?>&nbsp;&nbsp;</td>
													<?php foreach($tpagecounts as $tpagecount) { ?>
														<td><input type="text" name="product_shipping_weights[<?php echo $tmaterial; ?>][<?php echo $tsize; ?>][<?php echo $tpagecount; ?>]" value="<?php echo $product_shipping_weights[$tmaterial][$tsize][$tpagecount]; ?>" style="width:70px;"></td>
													<?php } ?>
												</tr>
											<?php } ?>
										<?php } ?>
									</table>
									</div>
								<?php } else { ?>
									<table>
										<tr>
											<td style="width:145px;"><strong><?php _e('Material', 'wp2print'); ?></strong></td>
											<?php foreach($tsizes as $tsize) { ?>
												<td align="center"><strong><?php echo $terms_names[$tsize]; ?></strong></td>
											<?php } ?>
										</tr>
										<?php foreach($tmaterials as $tmaterial) { ?>
											<tr>
												<td><?php echo $terms_names[$tmaterial]; ?>&nbsp;&nbsp;</td>
												<?php foreach($tsizes as $tsize) { ?>
													<td><input type="text" name="product_shipping_weights[<?php echo $tmaterial; ?>][<?php echo $tsize; ?>]" value="<?php echo $product_shipping_weights[$tmaterial][$tsize]; ?>" style="width:90px;"></td>
												<?php } ?>
											</tr>
										<?php } ?>
									</table>
								<?php } ?>
								<table>
									<tr>
										<td style="width:145px;"><strong><?php _e('Base Quantity', 'wp2print'); ?></strong></td>
										<td><input type="text" name="product_shipping_base_quantity" value="<?php echo $product_shipping_base_quantity; ?>" style="width:90px;"></td>
									</tr>
								</table>
							<?php } ?>
						<?php } else { ?>
							<table>
								<tr>
									<td style="width:145px;"><strong><?php _e('Material', 'wp2print'); ?></strong></td>
									<td><strong><?php _e('Weight', 'wp2print'); ?></strong></td>
									<td></td>
								</tr>
								<?php foreach($tmaterials as $tmaterial) { ?>
									<tr>
										<td><?php echo $terms_names[$tmaterial]; ?>&nbsp;&nbsp;</td>
										<td><input type="text" name="product_shipping_weights[<?php echo $tmaterial; ?>]" value="<?php echo $product_shipping_weights[$tmaterial]; ?>" style="width:100px;"></td>
										<td>(<?php echo $weight_unit; ?>/<?php echo $square_unit; ?>)</td>
									</tr>
								<?php } ?>
							</table>
						<?php } ?>
						<table>
							<tr>
								<td style="width:145px;"><strong><?php _e('Max. weight per box', 'wp2print'); ?></strong></td>
								<td><input type="text" name="product_max_weight_per_box" value="<?php echo $product_max_weight_per_box; ?>" style="width:90px;"></td>
							</tr>
						</table>
						<table>
							<tr>
								<td style="width:145px;"><strong><?php _e('Display Weight', 'wp2print'); ?></strong></td>
								<td><input type="checkbox" name="product_display_weight" value="1"<?php if ($product_display_weight) { echo ' CHECKED'; } ?>></td>
							</tr>
						</table>
					</div>
					<?php
				}
			}
		}
	}
}

add_action('woocommerce_product_options_sku', 'print_products_product_options_sku');
function print_products_product_options_sku() {
	global $post, $thepostid, $wpdb, $print_products_settings, $terms_names;
	$product_type = print_products_get_type($thepostid);
	if (print_products_is_wp2print_type($product_type)) {
		$printing_mtype_id = $wpdb->get_var(sprintf("SELECT mtype_id FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder ASC LIMIT 0, 1", $wpdb->prefix, $thepostid));
		if ($printing_mtype_id) {
			?>
			<div style="border-bottom:1px solid #eee;">
				<p class="form-field _sku_field">
					<label><?php _e('SKU Matrix', 'wp2print'); ?></label>
					<a href="edit.php?post_type=product&page=print-products-price-matrix-sku&mtype_id=<?php echo $printing_mtype_id; ?>"><?php _e('Printing SKU Matrix', 'wp2print'); ?></a>
				</p>
			</div>
			<?php
		}
	}
}

// save custom data
add_action('save_post', 'print_products_product_custom_options_save', 10, 2);
function print_products_product_custom_options_save($post_id, $post) {
	if ($post->post_type == 'product') {
		$product_type = print_products_get_type($post_id);
		// update shipping weights
		$product_shipping_weights = serialize($_POST['product_shipping_weights']);
		$product_shipping_base_quantity = $_POST['product_shipping_base_quantity'];
		$product_display_weight = (int)$_POST['product_display_weight'];
		$product_max_weight_per_box = $_POST['product_max_weight_per_box'];
		$product_display_price = $_POST['product_display_price'];

		if ($product_type == 'book') { $product_shipping_base_quantity = serialize($product_shipping_base_quantity); }
		update_post_meta($post_id, '_product_shipping_weights', $product_shipping_weights);
		update_post_meta($post_id, '_product_shipping_base_quantity', $product_shipping_base_quantity);
		update_post_meta($post_id, '_product_display_weight', $product_display_weight);
		update_post_meta($post_id, '_product_display_price', $product_display_price);
		update_post_meta($post_id, '_product_max_weight_per_box', $product_max_weight_per_box);
		
	}
}

add_action('woocommerce_before_order_itemmeta', 'print_products_before_order_itemmeta', 10, 2);
function print_products_before_order_itemmeta($item_id, $item) {
	global $wpdb, $print_products_plugin_options;
	$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
	if ($order_item_data) {
		$item_sku = print_products_get_item_sku($order_item_data, true);
		if (isset($_GET['action']) && $_GET['action'] == 'generate_wpo_wcpdf') {
			if ($item_sku) {
				echo '<ul><li>'.__('SKU', 'woocommerce').': ' . esc_html($item_sku).'</li></ul>';
			}
			print_products_wcpdf_order_item_data($order_item_data);
		} else {
			if ($item_sku) {
				echo ' &ndash; (' . esc_html($item_sku) . ')';
			}
		}
	}
}

add_action('woocommerce_order_item_line_item_html', 'print_products_order_item_line_item_html', 10, 3);
function print_products_order_item_line_item_html($item_id, $item, $order) {
	global $wpdb, $print_products_plugin_options; ?>
	<tr>
		<td>&nbsp;</td>
		<td colspan="10"><div style="margin-top:-25px;">
		<?php
		$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
		if ($order_item_data) {
			print_products_product_attributes_list_html($order_item_data);
			$additional = unserialize($order_item_data->additional);
			$artwork_files = unserialize($order_item_data->artwork_files);
			$artwork_old_files = unserialize($order_item_data->artwork_old_files);
			$artwork_thumbs = unserialize($order_item_data->artwork_thumbs);
			if ($artwork_files) { ?>
				<div class="print-products-area">
					<ul class="product-attributes-list">
						<?php if ($order_item_data->product_type == 'aec' || $order_item_data->product_type == 'aecbwc' || $order_item_data->product_type == 'aecsimple') { ?>
							<li><?php _e('Files', 'wp2print'); ?>:</li>
						<?php } else { ?>
							<li><?php _e('Artwork Files', 'print-products'); ?>:</li>
						<?php } ?>
						<li><ul class="product-artwork-files-list ftpfilenames oi-files-list">
							<?php foreach($artwork_files as $af_key => $artwork_file) {
								echo '<li><i class="i-check"></i> <a href="'.print_products_get_amazon_file_url($artwork_file).'" title="'.__('Download', 'wp2print').'" target="_blank">'.basename($artwork_file).'</a><span class="af-replace"> - <a href="#replace" class="afile-replace" onclick="return order_artwork_replace('.$item_id.', '.$af_key.');"><span>'.__('Replace file', 'wp2print').'</span></a></span></li>';
							} ?>
						</ul></li>
					</ul>
				</div>
				<?php
			} else {
				?>
				<div class="print-products-area">
					<a href="#add-file" onclick="return order_artwork_replace(<?php echo $item_id; ?>, 0);"><?php _e('Add artwork file', 'wp2print'); ?></a>
				</div>
				<?php
			}
			if ($artwork_old_files) { ?>
				<div class="print-products-area old-files-container">
					<ul class="product-attributes-list">
						<li><a href="#show-old-files" class="show-old-files"><?php _e('Check old versions of files'); ?></a></li>
						<li class="old-files-list"><ul class="product-artwork-files-list ftpfilenames">
							<?php foreach($artwork_old_files as $af_key => $artwork_old_file) {
								echo '<li><i class="i-triangle"></i> <a href="'.print_products_get_amazon_file_url($artwork_old_file).'" title="'.__('Download', 'wp2print').'" target="_blank">'.basename($artwork_old_file).'</a></li>';
							} ?>
						</ul></li>
					</ul>
				</div>
				<?php
			}
		}
		// Order artwork file url
		$artwork_file_url = wc_get_order_item_meta($item_id, '_artwork_file_url', true);
		$artwork_file_url_order = (int)wc_get_order_item_meta($item_id, '_artwork_file_url_order', true);
		if (strlen($artwork_file_url) && $artwork_file_url_order) {
			echo '<a href="'.$artwork_file_url.'" title="'.__('Download', 'wp2print').'" target="_blank">'.basename($artwork_file_url).'</a>';
		}

		// Approval status
		$order_id = $order->get_id();
		$order_status = $order->get_status();
		$approval_statuses = print_products_orders_proof_get_approval_statuses();
		$approval_status = wc_get_order_item_meta($item_id, '_approval_status', true);
		$proof_files = wc_get_order_item_meta($item_id, '_proof_files', true); ?>
		<div class="order-proof-container">
			<div class="order-proof">
				<ul class="clear">
					<li><?php _e('Approval', 'wp2print'); ?>:</li>
					<?php if (strlen($approval_status)) { ?><li><span class="<?php echo $approval_status; ?>" title="<?php echo $approval_statuses[$approval_status]; ?>"></span></li><?php } ?>
					<?php if ($order_status != 'cancelled' && (!strlen($approval_status) || $approval_status == 'rejected')) { ?><li><a href="#send-proof" class="button button-primary order-send-proof" data-oid="<?php echo $order_id; ?>" data-iid="<?php echo $item_id; ?>"><?php _e('Send proof', 'wp2print'); ?></a></li><?php } ?>
				</ul>
				<?php if (strlen($proof_files)) { $pfiles = explode(';', $proof_files); ?>
					<div class="clear"></div>
					<div class="proof-files">
						<?php foreach($pfiles as $proof_file) { ?>
							<a href="<?php echo print_products_get_amazon_file_url($proof_file); ?>" target="_blank"><?php echo basename($proof_file); ?></a><br>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		?>
		</div></td>
	</tr>
	<?php
	// AEC table
	$dimension_unit = print_products_get_aec_dimension_unit();
	$aec_sizes = print_products_get_aec_sizes();
	$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
	if ($order_item_data) {
		if ($order_item_data->product_type == 'aec') {
			$additional = unserialize($order_item_data->additional);
			$table_values = $additional['table_values'];
			if (strlen($table_values)) {
				$table_lines = explode('|', $table_values);
				$last_size = ''; ?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="10">
						<div class="low-cost-options-table"<?php if (count($table_lines) > 11) { echo ' style="height:400px;"'; } ?>>
							<table cellspacing="1" cellpadding="0" width="100%">
								<thead>
									<tr>
										<th style="text-align:left;"><?php _e('File Name', 'wp2print'); ?></th>
										<th style="text-align:center"><?php _e('Page', 'wp2print'); ?></th>
										<th style="text-align:center" nowrap><?php _e('% Coverage', 'wp2print'); ?></th>
										<th style="text-align:left"><?php _e('Print size', 'wp2print'); ?></th>
										<th style="text-align:right" nowrap><?php _e('Printed Area', 'wp2print'); ?> (<?php echo $dimension_unit; ?><sup>2</sup>)</th>
										<th style="text-align:right"><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit; ?><sup>2</sup></th>
										<th style="text-align:right"><?php _e('Price', 'wp2print'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($table_lines as $table_line) {
										$lvalues = explode(';', $table_line);
										$tdbg = '';
										if (($lvalues[3] != '' && $lvalues[3] != '100') || ($lvalues[3] == '' && $last_size != '100')) {
											$tdbg = 'background:#fc8c8c !important;';
										} ?>
										<tr>
											<td style="text-align:left;width:150px;word-break:break-all;<?php echo $tdbg; ?>"><?php echo $lvalues[0]; ?></td>
											<td style="text-align:center;<?php echo $tdbg; ?>"><?php echo $lvalues[1]; ?></td>
											<td style="text-align:center;<?php echo $tdbg; ?>"><?php echo $lvalues[2]; ?></td>
											<td style="text-align:left;<?php echo $tdbg; ?>" nowrap><?php echo $aec_sizes[$lvalues[3]]; ?></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo $lvalues[4]; ?> <?php echo $dimension_unit; ?><sup>2</sup></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo print_products_display_price($lvalues[5]); ?></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo print_products_display_price($lvalues[6]); ?></td>
										</tr>
										<?php if ($lvalues[3] != '') { $last_size = $lvalues[3]; } ?>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
				<?php
			}
		} else if ($order_item_data->product_type == 'aecbwc') {
			$additional = unserialize($order_item_data->additional);
			$table_values = $additional['table_values'];
			if (strlen($table_values)) {
				$table_lines = explode('|', $table_values);
				$last_size = ''; ?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="10">
						<div class="low-cost-options-table"<?php if (count($table_lines) > 11) { echo ' style="height:400px;"'; } ?>>
							<table cellspacing="1" cellpadding="0" width="100%">
								<thead>
									<tr>
										<th style="text-align:left;"><?php _e('File Name', 'wp2print'); ?></th>
										<th style="text-align:center"><?php _e('Page', 'wp2print'); ?></th>
										<th style="text-align:center"><?php _e('Original color', 'wp2print'); ?></th>
										<th style="text-align:center" nowrap><?php _e('Original size', 'wp2print'); ?></th>
										<th style="text-align:center"><?php _e('Convert color', 'wp2print'); ?></th>
										<th style="text-align:left"><?php _e('Print size', 'wp2print'); ?></th>
										<th style="text-align:right"><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit; ?><sup>2</sup></th>
										<th style="text-align:right"><?php _e('Price', 'wp2print'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($table_lines as $table_line) {
										$lvalues = explode(';', $table_line);
										$tdbg = '';
										if (($lvalues[5] != '' && $lvalues[5] != '100') || ($lvalues[5] == '' && $last_size != '100')) {
											$tdbg = 'background:#fc8c8c !important;';
										}
										$ccolor = '&nbsp;';
										if ($lvalues[2] == 'color' && $lvalues[4] == 'bw') { $ccolor = '<img src="'.PRINT_PRODUCTS_PLUGIN_URL.'images/icon-bw.png">'; }
										?>
										<tr>
											<td style="text-align:left;width:150px;word-break:break-all;<?php echo $tdbg; ?>"><?php echo $lvalues[0]; ?></td>
											<td style="text-align:center;<?php echo $tdbg; ?>"><?php echo $lvalues[1]; ?></td>
											<td style="text-align:center;<?php echo $tdbg; ?>"><img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/icon-<?php echo $lvalues[2]; ?>.png"></td>
											<td style="text-align:center;<?php echo $tdbg; ?>" nowrap><?php echo $lvalues[3]; ?></td>
											<td style="text-align:center;<?php echo $tdbg; ?>"><?php echo $ccolor; ?></td>
											<td style="text-align:left;<?php echo $tdbg; ?>" nowrap><?php echo $aec_sizes[$lvalues[5]]; ?></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo print_products_display_price($lvalues[6]); ?></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo print_products_display_price($lvalues[7]); ?></td>
										</tr>
										<?php if ($lvalues[5] != '') { $last_size = $lvalues[5]; } ?>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
				<?php
			}
		} else if ($order_item_data->product_type == 'aecsimple') {
			$additional = unserialize($order_item_data->additional);
			$table_values = $additional['table_values'];
			if (strlen($table_values)) {
				$table_lines = explode('|', $table_values);
				$last_size = ''; ?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="10">
						<div class="low-cost-options-table"<?php if (count($table_lines) > 11) { echo ' style="height:400px;"'; } ?>>
							<table cellspacing="1" cellpadding="0" width="100%">
								<thead>
									<tr>
										<th style="text-align:left;"><?php _e('File Name', 'wp2print'); ?></th>
										<th style="text-align:center"><?php _e('Page', 'wp2print'); ?></th>
										<th style="text-align:left"><?php _e('Print size', 'wp2print'); ?></th>
										<th style="text-align:right" nowrap><?php _e('Printed Area', 'wp2print'); ?> (<?php echo $dimension_unit; ?><sup>2</sup>)</th>
										<th style="text-align:right"><?php _e('Price', 'wp2print'); ?>/<?php echo $dimension_unit; ?><sup>2</sup></th>
										<th style="text-align:right"><?php _e('Price', 'wp2print'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($table_lines as $table_line) {
										$lvalues = explode(';', $table_line);
										$tdbg = '';
										if (($lvalues[2] != '' && $lvalues[2] != '100') || ($lvalues[2] == '' && $last_size != '100')) {
											$tdbg = 'background:#fc8c8c !important;';
										} ?>
										<tr>
											<td style="text-align:left;width:150px;word-break:break-all;<?php echo $tdbg; ?>"><?php echo $lvalues[0]; ?></td>
											<td style="text-align:center;<?php echo $tdbg; ?>"><?php echo $lvalues[1]; ?></td>
											<td style="text-align:left;<?php echo $tdbg; ?>" nowrap><?php echo $aec_sizes[$lvalues[2]]; ?></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo $lvalues[3]; ?> <?php echo $dimension_unit; ?><sup>2</sup></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo print_products_display_price($lvalues[4]); ?></td>
											<td style="text-align:right;<?php echo $tdbg; ?>"><?php echo print_products_display_price($lvalues[5]); ?></td>
										</tr>
										<?php if ($lvalues[2] != '') { $last_size = $lvalues[2]; } ?>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
				<?php
			}
		}
	}
}

function print_products_wcpdf_order_item_data($order_item_data) {
	print_products_product_attributes_list_html($order_item_data);
	$additional = unserialize($order_item_data->additional);
	$artwork_files = unserialize($order_item_data->artwork_files);
	$artwork_thumbs = unserialize($order_item_data->artwork_thumbs);
	if ($artwork_files) { ?>
		<div class="print-products-area">
			<ul class="product-attributes-list">
				<?php if ($order_item_data->product_type == 'aec' || $order_item_data->product_type == 'aecbwc' || $order_item_data->product_type == 'aecsimple') { ?>
					<li><?php _e('Files', 'wp2print'); ?>:</li>
				<?php } else { ?>
					<li><?php _e('Artwork Files', 'print-products'); ?>:</li>
				<?php } ?>
				<li><ul class="product-artwork-files-list ftpfilenames oi-files-list">
					<?php foreach($artwork_files as $af_key => $artwork_file) {
						echo '<li><a href="'.print_products_get_amazon_file_url($artwork_file).'" title="'.__('Download', 'wp2print').'" target="_blank">'.basename($artwork_file).'</a></li>';
					} ?>
				</ul></li>
			</ul>
		</div>
		<?php
	}
}

add_action('woocommerce_process_product_meta', 'print_products_process_product_meta');
function print_products_process_product_meta($post_id) {
	update_post_meta($post_id, '_artwork_source', $_POST['artwork_source']);
	update_post_meta($post_id, '_artwork_allow_later', $_POST['artwork_allow_later']);
	update_post_meta($post_id, '_artwork_file_count', $_POST['artwork_file_count']);
	update_post_meta($post_id, '_artwork_afile_types', $_POST['artwork_afile_types']);
	update_post_meta($post_id, '_cart_upload_button', $_POST['cart_upload_button']);
	update_post_meta($post_id, '_cart_upload_button_required', $_POST['cart_upload_button_required']);
	update_post_meta($post_id, '_cart_upload_button_text', $_POST['cart_upload_button_text']);
	update_post_meta($post_id, '_cart_upload_file_types', $_POST['cart_upload_file_types']);

	$artwork_file_url = trim($_POST['artwork_file_url']);
	if (strlen($artwork_file_url) && substr($artwork_file_url, 0, 4) != 'http') {
		$artwork_file_url = 'http://' . $artwork_file_url;
	}
	update_post_meta($post_id, '_artwork_file_url', $artwork_file_url);
	update_post_meta($post_id, '_artwork_file_url_order', (int)$_POST['artwork_file_url_order']);
	update_post_meta($post_id, '_artwork_file_url_email', (int)$_POST['artwork_file_url_email']);
}

add_action('woocommerce_process_product_meta_aec', 'print_products_process_aec_product_meta');
add_action('woocommerce_process_product_meta_aecbwc', 'print_products_process_aec_product_meta');
add_action('woocommerce_process_product_meta_aecsimple', 'print_products_process_aec_product_meta');
function print_products_process_aec_product_meta($post_id) {
	if (isset($_POST['inc_coverage_prices'])) {
		update_post_meta($post_id, '_inc_coverage_prices', $_POST['inc_coverage_prices']);
		update_post_meta($post_id, '_apply_round_up', (int)$_POST['apply_round_up']);
		update_post_meta($post_id, '_round_up_discounts', $_POST['round_up_discounts']);

		// update price
		$inc_coverage_prices = $_POST['inc_coverage_prices'];
		if (is_array($inc_coverage_prices) && count($inc_coverage_prices)) {
			$product_price = 1000000000000000000;
			foreach($inc_coverage_prices as $icprices) {
				if (is_array($icprices)) {
					foreach($icprices as $icprice) {
						$icprice = (float)$icprice;
						if ($icprice < $product_price) {
							$product_price = $icprice;
						}
					}
				} else {
					$icprice = (float)$icprices;
					if ($icprice < $product_price) {
						$product_price = $icprice;
					}
				}
			}
			update_post_meta($post_id, '_price', $product_price);
			update_post_meta($post_id, '_regular_price', $product_price);
		}
	}
}

add_filter('woocommerce_tax_settings', 'print_products_woo_tax_settings');
function print_products_woo_tax_settings($settings) {
	$new_settings = array();
	foreach($settings as $skey => $sval) {
		if ($sval['id'] == 'woocommerce_price_display_suffix') {
			$sval['title'] = __('Price Display Include Suffix:', 'wp2print');
			$new_settings[] = $sval;
			$new_settings[] = array(
				'title' => __('Price Display Exclude Suffix:', 'wp2print'),
				'id' => 'woocommerce_price_display_excl_suffix',
				'default' => '',
				'placeholder' => 'N/A',
				'type' => 'text',
				'desc_tip' => __("Define text to show after your product prices (excluding tax). This could be, for example, 'excl. tax' to explain your pricing.", 'wp2print')
			);
		} else {
			$new_settings[] = $sval;
		}
	}
	return $new_settings;
}

add_filter('woocommerce_get_price_suffix', 'print_products_woo_get_price_suffix');
function print_products_woo_get_price_suffix($price_display_suffix) {
	if (is_front_page()) {
		$price_display_suffix = '';
	}
	return $price_display_suffix;
}

add_action('woocommerce_duplicate_product', 'print_products_woo_duplicate_product', 10, 2);
function print_products_woo_duplicate_product($new_id, $post) {
	global $wpdb;
	$matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype_id", $wpdb->prefix, $post->ID));
	if ($matrix_types) {
		foreach($matrix_types as $matrix_type) {
			$mtype_id = $matrix_type->mtype_id;

			$insert = array();
			$insert['product_id'] = $new_id;
			$insert['mtype'] = $matrix_type->mtype;
			$insert['title'] = $matrix_type->title;
			$insert['attributes'] = $matrix_type->attributes;
			$insert['aterms'] = $matrix_type->aterms;
			$insert['numbers'] = $matrix_type->numbers;
			$insert['num_type'] = $matrix_type->num_type;
			$insert['sorder'] = $matrix_type->sorder;
			$insert['num_style'] = $matrix_type->num_style;
			$insert['def_quantity'] = $matrix_type->def_quantity;
			$wpdb->insert($wpdb->prefix."print_products_matrix_types", $insert);
			$new_mtype_id = $wpdb->insert_id;

			$matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s ORDER BY mtype_id", $wpdb->prefix, $mtype_id));
			if ($matrix_prices) {
				foreach($matrix_prices as $matrix_price) {
					$insert = array();
					$insert['mtype_id'] = $new_mtype_id;
					$insert['aterms'] = $matrix_price->aterms;
					$insert['number'] = $matrix_price->number;
					$insert['price'] = $matrix_price->price;
					$wpdb->insert($wpdb->prefix."print_products_matrix_prices", $insert);
				}
			}
		}
	}
}

add_filter('woocommerce_get_settings_products', 'print_products_settings_products', 10);
function print_products_settings_products($settings) {
	foreach ($settings as $skey => $setting) {
		if ($setting['id'] == 'woocommerce_dimension_unit') {
			if (!isset($setting['options']['ft'])) {
				$settings[$skey]['options']['ft'] = __('ft');
			}
		}
	}
	$settings[] = array(
		'title' => __('Product List Prices', 'wp2print'),
		'type' => 'title',
		'desc' => '',
		'id' => 'price_options'
	);
	$settings[] = array(
		'title'         => __('Disable Product List Price', 'wp2print'),
		'desc'          => 'Check checkbox if you want to hide product prices on list.',
		'id'            => 'woocommerce_disable_product_list_price',
		'default'       => 'no',
		'type'          => 'checkbox'
	);
	$settings[] = array(
		'type' 	=> 'sectionend',
		'id' 	=> 'price_options'
	);
	return $settings;
}

add_action('delete_post', 'print_products_wp_delete_product', 10);
function print_products_wp_delete_product($pid) {
	global $wpdb;
	$matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype_id", $wpdb->prefix, $pid));
	if ($matrix_types) {
		$mtypes = array();
		foreach($matrix_types as $matrix_type) {
			$mtypes[] = $matrix_type->mtype_id;
		}
		$wpdb->query(sprintf("DELETE FROM %sprint_products_matrix_types WHERE product_id = %s", $wpdb->prefix, $pid));
		if (count($mtypes)) {
			$wpdb->query(sprintf("DELETE FROM %sprint_products_matrix_prices WHERE mtype_id IN (%s)", $wpdb->prefix, implode(',', $mtypes)));
		}
	}
}

add_filter('woocommerce_order_amount_item_subtotal', 'print_products_order_amount_item_subtotal', 11, 4);
function print_products_order_amount_item_subtotal($subtotal, $order, $item, $inc_tax) {
	$product_id = $item->get_product_id();
	$product_type = print_products_get_type($product_id);
	if (print_products_is_wp2print_type($product_type)) {
		$isubtotal = floatval($item->get_subtotal());
		$isubtotal_tax = floatval($item->get_subtotal_tax());
		$quantity = $item->get_quantity();
		if ($inc_tax) {
			$subtotal = ( $isubtotal + $isubtotal_tax ) / max( 1, $quantity );
		} else {
			$subtotal = ( $isubtotal / max( 1, $quantity ) );
		}
		$decimals = print_products_get_order_item_decimals($quantity);
		$subtotal = round($subtotal, $decimals);
	}
	return $subtotal;
}

add_filter('woocommerce_my_account_my_orders_columns', 'print_products_my_account_my_orders_columns');
function print_products_my_account_my_orders_columns($columns) {
	$new_columns = array();
	foreach($columns as $ckey => $cval) {
		if ($ckey == 'order-actions') {
			$new_columns['order-notes'] = __('Notes', 'wp2print');
		}
		$new_columns[$ckey] = $cval;
	}
	return $new_columns;
}

add_action('woocommerce_my_account_my_orders_column_order-notes', 'print_products_my_account_my_orders_column_order_notes');
function print_products_my_account_my_orders_column_order_notes($order) {
	echo get_post_meta($order->ID, '_order_notes', true);
}

add_action('wpo_wcpdf_before_item_meta', 'print_products_wpo_wcpdf_before_item_meta', 11, 3);
function print_products_wpo_wcpdf_before_item_meta($type, $item, $order) {
	echo '<br><br>';
	print_products_before_order_itemmeta($item['item_id'], $item);
}

add_filter('product_attributes_type_selector', 'print_products_woo_attributes_type_selector');
function print_products_woo_attributes_type_selector($types) {
	if (!isset($types['select'])) {
		$types['select'] = __('Select', 'woocommerce');
	}
	if (!isset($types['text'])) {
		$types['text'] = __('Text', 'woocommerce');
	}
	return $types;
}

function print_products_woo_get_order_item_meta($item_id, $meta_key) {
	$designer_image = $wpdb->get_var(sprintf("SELECT meta_value FROM %swoocommerce_order_itemmeta WHERE order_item_id = %s AND meta_key = '_image_link'", $wpdb->prefix, $item_id));
}

add_filter('get_terms_args', 'print_products_woo_product_subcategories_args', 12);
function print_products_woo_product_subcategories_args($args) {
	$uncategorized = get_option('default_product_cat');
	if ($uncategorized) {
		if (is_array($args['exclude'])) {
			if (count($args['exclude'])) {
				$args['exclude'][] = $uncategorized;
			} else {
				$args['exclude'] = array($uncategorized);
			}
		} else {
			if (strlen($args['exclude'])) {
				$args['exclude'] = $args['exclude'] . ',' . $uncategorized;
			} else {
				$args['exclude'] = array($uncategorized);
			}
		}
	}
	return $args;
}

add_action('woocommerce_attribute_added', 'print_products_woo_woocommerce_attribute_added', 11, 2);
function print_products_woo_woocommerce_attribute_added($id, $data) {
	global $wpdb;
	$print_products_settings = get_option('print_products_settings');
	$finishing_attributes = unserialize($print_products_settings['finishing_attributes']);
	if (!is_array($finishing_attributes)) { $finishing_attributes = array(); }
	$finishing_attributes[] = $id;
	$print_products_settings['finishing_attributes'] = serialize($finishing_attributes);
	update_option("print_products_settings", $print_products_settings);

	$attribute_order = (int)$wpdb->get_var(sprintf("SELECT MAX(attribute_order) FROM %swoocommerce_attribute_taxonomies", $wpdb->prefix));
	$attribute_order = $attribute_order + 1;
	$wpdb->update($wpdb->prefix.'woocommerce_attribute_taxonomies', array('attribute_order' => $attribute_order), array('attribute_id' => $id));
}

add_filter('woocommerce_cart_totals_order_total_html', 'print_products_woo_cart_totals_order_total_html');
function print_products_woo_cart_totals_order_total_html($html) {
	if (!strpos($html, 'includes_tax')) {
		$woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
		$price_display_incl_suffix = get_option('woocommerce_price_display_suffix');
		$price_display_excl_suffix = get_option('woocommerce_price_display_excl_suffix');
		if ($woocommerce_prices_include_tax == 'yes' && strlen($price_display_excl_suffix)) {
			$html .= '<small class="includes_tax">'.$price_display_excl_suffix.'</small>';
		} else if ($woocommerce_prices_include_tax == 'no' && strlen($price_display_incl_suffix)) {
			$html .= '<small class="includes_tax">'.$price_display_incl_suffix.'</small>';
		}
	}
	return $html;
}

add_filter('woocommerce_product_is_visible', 'print_products_woo_product_is_visible', 11, 2);
function print_products_woo_product_is_visible($visible, $product_id) {
	if (print_products_is_custom_product($product_id)) {
		$visible = false;
	}
	return $visible;
}

add_filter('woocommerce_cart_item_name', 'print_products_woo_cart_item_name', 25, 2);
function print_products_woo_cart_item_name($name, $cart_item) {
	if (print_products_is_custom_product($cart_item['product_id'])) {
		$name = strip_tags($name);
		$name = '</strong></a><strong>'.$name.'</strong><a href="#"><strong>';
	}
	return $name;
}

add_filter('woocommerce_product_get_weight', 'print_products_woo_product_get_weight', 11, 2);
function print_products_woo_product_get_weight($weight, $product) {
	if (!is_admin() && WC()->cart) {
		$product_id = $product->get_id();
		$cart = WC()->cart->get_cart();
		if ($cart) {
			$total_weight = 0;
			$total_prods = 0;
			foreach ($cart as $cart_item_key => $values) {
				$cart_product_id = $values['product_id'];
				if ($cart_product_id == $product_id) {
					$product_type = $product->get_type();
					if (print_products_is_wp2print_type($product_type) || print_products_is_custom_product($product_id)) {
						$weight = print_products_cart_get_product_weight($cart_item_key);
						$total_weight = $total_weight + $weight;
						$total_prods++;
					}
				}
			}
			if ($total_prods > 1 && $total_weight) {
				$weight = $total_weight / $total_prods;
			}
		}
	}
	return $weight;
}

add_filter('woocommerce_is_purchasable', 'print_products_woo_is_purchasable', 10, 2);
function print_products_woo_is_purchasable($purchasable, $product) {
	$product_type = $product->get_type();
	if (print_products_is_wp2print_type($product_type)) {
		$purchasable = true;
	}
	return $purchasable;
}

add_filter('woocommerce_hidden_order_itemmeta', 'print_products_woo_hidden_order_itemmeta');
function print_products_woo_hidden_order_itemmeta($metakeys) {
	$metakeys[] = '_sku';
	$metakeys[] = '_artwork_file_url';
	$metakeys[] = '_artwork_file_url_order';
	$metakeys[] = '_artwork_file_url_email';
	$metakeys[] = '_item_status';
	$metakeys[] = '_discount_price';
	$metakeys[] = '_approval_status';
	$metakeys[] = '_approval_type';
	$metakeys[] = '_approval_approved';
	$metakeys[] = '_approval_rejected';
	$metakeys[] = '_proof_files';
	$metakeys[] = '_item_rsdate';
	return $metakeys;
}

add_action('template_redirect', 'print_products_woo_template_redirect');
function print_products_woo_template_redirect() {
	global $wpdb;
	if (is_checkout()) {
		$cart = WC()->cart->get_cart();
		if ($cart) {
			$goback = false;
			foreach($cart as $cart_item_key => $values) {
				$product_id = $values['product_id'];
				$product = $values['data'];
				$cart_upload_button = get_post_meta($product_id, '_cart_upload_button', true);
				$cart_upload_button_required = get_post_meta($product_id, '_cart_upload_button_required', true);
				if ($cart_upload_button && $cart_upload_button_required) {
					$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
					if ($prod_cart_data) {
						if (!strlen($prod_cart_data->artwork_files)) {
							$cart_upload_button_text = get_post_meta($product_id, '_cart_upload_button_text', true);
							if (!strlen($cart_upload_button_text)) { $cart_upload_button_text = __('Upload your database', 'wp2print'); }

							wc_add_notice($cart_upload_button_text.' '.__('button is required for', 'wp2print').' '.$product->get_name(), 'error');

							$goback = true;
						}
					}
				}
			}
			if ($goback) {
				$cart_url = wc_get_cart_url();
				wp_redirect($cart_url);
				exit;
			}
		}
	}
}

add_action('woocommerce_thankyou', 'print_products_woo_checkout_order_processed', 25);
function print_products_woo_checkout_order_processed($order_id) {
	$_payment_method = get_post_meta($order_id, '_payment_method', true);
	if ($_payment_method == 'cheque') {
		$order = wc_get_order($order_id);
		if ($order->get_status() == 'on-hold') {
			$order->update_status('processing');
		}
	}
}

add_action('woocommerce_after_single_product_summary', 'print_products_woo_after_single_product_summary', 9);
function print_products_woo_after_single_product_summary() {
	global $post, $attribute_names, $terms_names;
	$unitpricetable = get_post_meta($post->ID, '_unitpricetable', true);
	$upt_show = false;
	$upt_quantities = array();
	$upt_attribute = 0;
	$upt_aoptions = array();
	$upt_unitprices = 0;
	$upt_utext = '';
	if ($unitpricetable && is_array($unitpricetable)) {
		$upt_show = (int)$unitpricetable['show'];
		$upt_quantities = explode(',', $unitpricetable['quantities']);
		$upt_attribute = $unitpricetable['attribute'];
		$upt_aoptions = $unitpricetable['aoptions'];
		$upt_unitprices = (int)$unitpricetable['unitprices'];
		$upt_utext = $unitpricetable['utext'];
	}
	if ($upt_show && count($upt_quantities) && $upt_attribute && is_array($upt_aoptions) && count($upt_aoptions)) { ?>
		<style>
		.upt-container{
			float:right;
			margin: 0 0 20px 0;
			clear:right;
		}
		table.unit-price-table{
			width:auto;
			margin:0;
			border-collapse:collapse !important;
		}
		table.unit-price-table td.td-hd{
			color:#000;
			padding:10px;
		}
		</style>
		<div class="upt-container entry-content">
			<table border="1" class="unit-price-table">
				<tr>
					<td class="td-hd"><?php _e('Quantity', 'wp2print'); ?></td>
					<?php foreach($upt_quantities as $qty) { ?>
						<td class="td-hd" ><?php echo $qty; ?></td>
					<?php } ?>
				</tr>
				<?php foreach($upt_aoptions as $term_id) { ?>
					<tr>
						<td class="td-hd" data-label="<?php echo $attribute_names[$upt_attribute]; ?>"><?php echo $terms_names[$term_id]; ?></td>
						<?php foreach($upt_quantities as $qty) { ?>
							<td class="up-<?php echo $qty; ?>-<?php echo $term_id; ?>" data-label="<?php echo $qty; ?>">0</td>
						<?php } ?>
					</tr>
				<?php } ?>
			</table>
		</div>
		<?php
	}
}

add_action('woocommerce_thankyou', 'print_products_woo_thankyou');
function print_products_woo_thankyou($order_id) {
	global $wpdb;
	$order = wc_get_order($order_id);
	if ($order && is_user_logged_in()) {
		$show_button = false;
		$order_items = $order->get_items(apply_filters('woocommerce_purchase_order_item_types', 'line_item'));
		foreach ($order_items as $item_id => $item) {
			$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
			if ($order_item_data && !$show_button) {
				$artwork_source = get_post_meta($order_item_data->product_id, '_artwork_source', true);
				if ($order_item_data->atcaction == 'artwork' && !strlen($order_item_data->artwork_files) && ($artwork_source == 'artwork' || $artwork_source == 'both')) {
					$show_button = true;
				}
			}
		}
		if ($show_button) {
			$myaccount_page_id = get_option('woocommerce_myaccount_page_id');
			$myaccount_page = get_permalink($myaccount_page_id);
			if (substr($myaccount_page, -1) != '/') { $myaccount_page .= '/'; }
			$myaccount_page .= 'orders-missing-files/?view='.$order_id;
			?>
			<script>jQuery('p.woocommerce-thankyou-order-received').html('<?php _e('Thank you. Your order has been received. If you have the files you would like us to print, please upload them now:', 'wp2print'); ?>&nbsp;&nbsp;<a href="<?php echo $myaccount_page; ?>" class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt" style="margin-bottom:20px;"><?php _e('Upload your own design', 'wp2print'); ?></a>');
			</script>
			<?php
		}
	}
}

add_action('wp_loaded', 'print_products_order_artwork_upload');
function print_products_order_artwork_upload() {
	global $wpdb;
	if (isset($_POST['artwork_upload_action']) && $_POST['artwork_upload_action'] == 'upload') {
		$item_id = (int)$_POST['item_id'];
		$au_file = (int)$_POST['au_file'];
		$au_new_file = $_POST['au_new_file'];
		$redirect_to = $_POST['redirect_to'];
		$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
		if ($order_item_data && strlen($au_new_file)) {
			$artwork_files = unserialize($order_item_data->artwork_files);
			$artwork_old_files = array();
			if ($order_item_data->artwork_old_files) {
				$artwork_old_files = unserialize($order_item_data->artwork_old_files);
			}
			if ($artwork_files) {
				$artwork_old_files[] = $artwork_files[$au_file];
				$artwork_files[$au_file] = $au_new_file;
			} else {
				$artwork_files[] = $au_new_file;
			}

			$update = array();
			$update['artwork_files'] = serialize($artwork_files);
			$update['artwork_old_files'] = serialize($artwork_old_files);
			$wpdb->update($wpdb->prefix.'print_products_order_items', $update, array('item_id' => $item_id));
		}
		wp_redirect($redirect_to);
		exit;
	}
}

// add column to Users list
add_filter('manage_users_columns', 'print_products_manage_users_columns', 25);
function print_products_manage_users_columns($columns) {
	$new_columns = array();
	foreach($columns as $ckey => $cname) {
		if ($ckey == 'posts') {
			$new_columns['order-history'] = __('Order history', 'wp2print');
		}
		$new_columns[$ckey] = $cname;
	}
	return $new_columns;
}

add_filter('manage_users_custom_column', 'print_products_manage_users_custom_column', 25, 3);
function print_products_manage_users_custom_column($val, $column, $user_id) {
	if ($column == 'order-history') {
		$val = '<a href="admin.php?page=print-products-users-orders&user_id='.$user_id.'">'.__('View history', 'wp2print').'</a>';
	}
	return $val;
}

// add buttons to edit order page
add_action('woocommerce_admin_order_data_after_billing_address', 'print_products_edit_order_admin_order_data_after_billing_address', 25);
function print_products_edit_order_admin_order_data_after_billing_address($order) {
	$user_id = $order->user_id;
	$user_group = (int)get_user_meta($user_id, '_user_group', true); ?>
	<p class="form-field">
		<table cellspacing="0" cellpadding="0">
			<tr>
				<?php if ($user_group) { ?><td style="padding-right:15px;"><a href="admin.php?page=print-products-groups-orders&group_id=<?php echo $user_group; ?>" class="button"><?php _e("Group order history", 'wp2print'); ?></a></td><?php } ?>
				<td style="padding-right:15px;"><a href="admin.php?page=print-products-users-orders&user_id=<?php echo $user_id; ?>" class="button"><?php _e("Order history", 'wp2print'); ?></a></td>
				<td><a href="admin.php?page=print-products-users-artwork-files&user_id=<?php echo $user_id; ?>" class="button"><?php _e("View user's files", 'wp2print'); ?></a></td>
			</tr>
		</table>
	</p>
	<?php
}

add_filter('woocommerce_product_description_heading', 'print_products_woocommerce_product_description_heading', 100);
function print_products_woocommerce_product_description_heading($heading) {
	$currtheme = get_option('stylesheet');
	if ($currtheme == 'ascend_premium' || $currtheme == 'ascend_premium_child') {
		$heading = '';
	}
	return $heading;
}

add_filter('woocommerce_admin_html_order_item_class', 'print_products_woocommerce_admin_html_order_item_class', 11, 3);
function print_products_woocommerce_admin_html_order_item_class($class, $item, $order) {
	if (strlen($class)) { $class .= ' '; }
	$class .= 'item-'.$item->get_id();
	return $class;
}

function print_products_admin_footer_edit_order_js() {
	global $post;
	$order = wc_get_order($post->ID);
	$line_items = $order->get_items(apply_filters('woocommerce_admin_order_item_types', 'line_item')); ?>
	<script>
	<?php foreach ($line_items as $item_id => $item) {
		$quantity = $item->get_quantity();
		$item_subtotal = $order->get_item_subtotal($item, false, true);
		$decimals = print_products_get_order_item_decimals($quantity); ?>
		jQuery('.woocommerce_order_items .item-<?php echo $item_id; ?> .item_cost .view').html('<?php echo wc_price($item_subtotal, array('decimals' => $decimals)); ?>');
	<?php } ?>
	</script>
	<?php
}
?>