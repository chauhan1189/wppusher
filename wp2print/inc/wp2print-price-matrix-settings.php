<?php
add_action('wp_loaded', 'print_products_attributes_options_init');
function print_products_attributes_options_init() {
	global $wpdb;
	// form submit
	if (isset($_POST['print_products_attributes_options_action'])) {
		switch ($_POST['print_products_attributes_options_action']) {
			case "submit":
				$print_products_settings = get_option('print_products_settings');
				$print_products_settings['size_attribute'] = $_POST['size_attribute'];
				$print_products_settings['colour_attribute'] = $_POST['colour_attribute'];
				$print_products_settings['material_attribute'] = $_POST['material_attribute'];
				$print_products_settings['page_count_attribute'] = $_POST['page_count_attribute'];
				$print_products_settings['postage_attribute'] = $_POST['postage_attribute'];
				$print_products_settings['finishing_attributes'] = serialize($_POST['finishing_attributes']);

				update_option("print_products_settings", $print_products_settings);

				$sorder_notallowed = array(
					$print_products_settings['size_attribute'],
					$print_products_settings['colour_attribute'],
					$print_products_settings['page_count_attribute'],
					$print_products_settings['material_attribute']
				);

				$attributes_order = $_POST['attributes_order'];
				if (is_array($attributes_order)) {
					foreach($attributes_order as $attribute_id => $sorder) {
						$attribute_order = (int)$sorder;
						switch ($attribute_id) {
							case $print_products_settings['size_attribute']:
								$attribute_order = 0;
							break;
							case $print_products_settings['colour_attribute']:
								$attribute_order = 1;
							break;
							case $print_products_settings['page_count_attribute']:
								$attribute_order = 2;
							break;
							case $print_products_settings['material_attribute']:
								$attribute_order = 3;
							break;
						}
						$wpdb->update($wpdb->prefix.'woocommerce_attribute_taxonomies', array('attribute_order' => $attribute_order), array('attribute_id' => $attribute_id));
					}
				}

				$_SESSION['print_products_attributes_options_message'] = __('Settings were successfully saved.', 'wp2print');

				wp_redirect('edit.php?post_type=product&page=print-products-attributes-options');
				exit;
			break;
			case "pasave":
				$print_products_settings = get_option('print_products_settings');
				$print_products_settings['printing_attributes'] = serialize($_POST['printing_attributes']);
				update_option("print_products_settings", $print_products_settings);

				$_SESSION['print_products_attributes_options_message'] = __('Settings were successfully saved.', 'wp2print');

				wp_redirect('edit.php?post_type=product&page=print-products-attributes-options&pachange=true');
				exit;
			break;
		}
	}
}

function print_products_attributes_options() {
	if (isset($_GET['pachange']) && $_GET['pachange'] == 'true') {
		print_products_attributes_options_printing_attributes();
	} else {
		print_products_attributes_options_general();
	}
}

function print_products_attributes_options_printing_attributes() {
	global $wpdb;
	$wc_attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
	$print_products_settings = get_option('print_products_settings');
	$printing_attributes = unserialize($print_products_settings['printing_attributes']);
	?>
	<div class="wrap wp2print-wrap">
		<h2><?php _e('Printing attributes', 'wp2print'); ?></h2><br>
		<?php if(strlen($_SESSION['print_products_attributes_options_message'])) { ?><div id="message" class="updated fade"><p><?php echo $_SESSION['print_products_attributes_options_message']; ?></p></div><?php unset($_SESSION['print_products_attributes_options_message']); } ?>
		<form action="edit.php?post_type=product&page=print-products-attributes-options&pachange=true" method="POST">
		<input type="hidden" name="print_products_attributes_options_action" value="pasave">
		<table>
			<tr>
				<td>
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; ?>
							<input type="checkbox" name="printing_attributes[]" value="<?php echo $wc_attribute->attribute_id; ?>"<?php if (in_array($wc_attribute->attribute_id, $printing_attributes)) { echo ' CHECKED'; } ?>><?php echo $wc_attribute->attribute_label; ?><br>
							<?php
						}
					} ?>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save', 'wp2print') ?>" /></p>
		</form>
	</div>
	<?php
}

function print_products_attributes_options_general() {
	global $wpdb;
	$product_types = get_terms('product-type', 'hide_empty=0&orderby=id&order=asc');
	$wc_attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
	$print_products_settings = get_option('print_products_settings');
	$printing_attributes = unserialize($print_products_settings['printing_attributes']);
	$finishing_attributes = unserialize($print_products_settings['finishing_attributes']);

	$sorder_notallowed = array(
		$print_products_settings['size_attribute'],
		$print_products_settings['colour_attribute'],
		$print_products_settings['page_count_attribute'],
		$print_products_settings['material_attribute']
	);

	if (!is_array($printing_attributes)) { $printing_attributes = array(); }
	if (!is_array($finishing_attributes)) { $finishing_attributes = array(); }
	?>
	<div class="wrap wp2print-wrap">
		<h2><?php _e('Attributes Options', 'wp2print'); ?></h2><br>
		<?php if(strlen($_SESSION['print_products_attributes_options_message'])) { ?><div id="message" class="updated fade"><p><?php echo $_SESSION['print_products_attributes_options_message']; ?></p></div><?php unset($_SESSION['print_products_attributes_options_message']); }
		if ($wc_attributes) { ?>
		<form action="edit.php?post_type=product&page=print-products-attributes-options" method="POST">
		<input type="hidden" name="print_products_attributes_options_action" value="submit">
		<table>
			<tr>
				<td><?php _e('Size attribute', 'wp2print'); ?>:
				<?php print_products_help_icon('size_attribute'); ?></td>
				<td><select name="size_attribute" style="width:200px;">
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; if ($wc_attribute->attribute_id == $print_products_settings['size_attribute']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $wc_attribute->attribute_id; ?>"<?php echo $s; ?>><?php echo $wc_attribute->attribute_label; ?></option>
					<?php }}?>
				</select></td>
			</tr>
			<tr>
				<td><?php _e('Colour attribute', 'wp2print'); ?>:
				<?php print_products_help_icon('colour_attribute'); ?></td>
				<td><select name="colour_attribute" style="width:200px;">
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; if ($wc_attribute->attribute_id == $print_products_settings['colour_attribute']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $wc_attribute->attribute_id; ?>"<?php echo $s; ?>><?php echo $wc_attribute->attribute_label; ?></option>
					<?php }}?>
				</select></td>
			</tr>
			<tr>
				<td><?php _e('Page Count attribute', 'wp2print'); ?>:
				<?php print_products_help_icon('page_count_attribute'); ?></td>
				<td><select name="page_count_attribute" style="width:200px;">
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; if ($wc_attribute->attribute_id == $print_products_settings['page_count_attribute']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $wc_attribute->attribute_id; ?>"<?php echo $s; ?>><?php echo $wc_attribute->attribute_label; ?></option>
					<?php }}?>
				</select></td>
			</tr>
			<tr>
				<td><?php _e('Paper Type attribute', 'wp2print'); ?>:
				<?php print_products_help_icon('material_attribute'); ?></td>
				<td><select name="material_attribute" style="width:200px;">
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; if ($wc_attribute->attribute_id == $print_products_settings['material_attribute']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $wc_attribute->attribute_id; ?>"<?php echo $s; ?>><?php echo $wc_attribute->attribute_label; ?></option>
					<?php }}?>
				</select></td>
			</tr>
			<tr>
				<td><?php _e('Postage attribute', 'wp2print'); ?>:
				<?php print_products_help_icon('postage_attribute'); ?></td>
				<td><select name="postage_attribute" style="width:200px;">
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; if (isset($print_products_settings['postage_attribute']) && $wc_attribute->attribute_id == $print_products_settings['postage_attribute']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $wc_attribute->attribute_id; ?>"<?php echo $s; ?>><?php echo $wc_attribute->attribute_label; ?></option>
					<?php }}?>
				</select></td>
			</tr>
			<tr><td colspan="2" class="tddivider"><hr /></td></tr>
			<tr>
				<td><?php _e('Printing attributes', 'wp2print'); ?>:
				<?php print_products_help_icon('printing_attributes'); ?></td>
				<td>
					<?php if ($wc_attributes) {
						$pattributes = array();
						foreach($wc_attributes as $wc_attribute) {
							if (in_array($wc_attribute->attribute_id, $printing_attributes)) {
								$pattributes[] = $wc_attribute->attribute_label;
							}
						}
						if (count($pattributes)) { ?>
							<ul class="pa-list">
								<?php foreach($pattributes as $pattribute) { ?>
									<li><?php echo $pattribute; ?></li>
								<?php } ?>
							</ul>
							<?php
						}
					} ?>
				</td>
			</tr>
			<tr><td colspan="2" class="tddivider"><hr /></td></tr>
			<tr>
				<td><?php _e('Finishing attributes', 'wp2print'); ?>:
				<?php print_products_help_icon('finishing_attributes'); ?></td>
				<td>
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = '';
							if (!in_array($wc_attribute->attribute_id, $sorder_notallowed)) { ?>
								<input type="checkbox" name="finishing_attributes[]" value="<?php echo $wc_attribute->attribute_id; ?>"<?php if (in_array($wc_attribute->attribute_id, $finishing_attributes)) { echo ' CHECKED'; } ?>><?php echo $wc_attribute->attribute_label; ?><br>
								<?php
							}
						}
					} ?>
				</td>
			</tr>
			<tr><td colspan="2" class="tddivider"><hr /></td></tr>
			<tr>
				<td><?php _e('Attributes sort order', 'wp2print'); ?>:
				<?php print_products_help_icon('attributes_order'); ?></td>
				<td>
					<?php if ($wc_attributes) {
						foreach($wc_attributes as $wc_attribute) { $s = ''; ?>
							<input type="text" name="attributes_order[<?php echo $wc_attribute->attribute_id; ?>]" value="<?php echo $wc_attribute->attribute_order; ?>" style="width:25px; padding:1px; font-size:12px;"<?php if (in_array($wc_attribute->attribute_id, $sorder_notallowed)) { echo ' readonly'; } ?>> <?php echo $wc_attribute->attribute_label; ?><br>
							<?php
						}
					} ?>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save', 'wp2print') ?>" /></p>
		</form>
		<?php } else { ?>
			<?php _e('Please add product attributes.', 'wp2print'); ?>
		<?php } ?>
	</div>
	<?php
}

add_action('presenters_edit_form_fields', 'presenters_taxonomy_custom_fields', 10, 2);

?>