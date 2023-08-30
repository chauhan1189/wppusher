<?php
add_action('wp_loaded', 'print_products_price_matrix_types_init');
function print_products_price_matrix_types_init() {
	global $wpdb;
	// form submit
	if (isset($_REQUEST['print_products_price_matrix_types_action'])) {
		switch ($_REQUEST['print_products_price_matrix_types_action']) {
			case "submit":
				$pmtaction = $_POST['pmtaction'];
				$mtype_id = $_POST['mtid'];
				$pid = $_POST['pid'];
				$mtype = $_POST['mtype'];
				$pattributes = $_POST['attributes'];
				$pterms = $_POST['aterms'];
				$title = trim($_POST['title']);
				$def_quantity = (int)$_POST['def_quantity'];
				$numbers = str_replace(' ', '', $_POST['numbers']);
				$num_style = $_POST['num_style'];
				$num_type = $_POST['num_type'];
				$bq_numbers = $_POST['bq_numbers'];
				$book_min_quantity = $_POST['book_min_quantity'];
				$pq_style = $_POST['pq_style'];
				$pq_numbers = $_POST['pq_numbers'];
				$ltext_attr = (int)$_POST['ltext_attr'];
				$sorder = (int)$_POST['sorder'];

				$aterms = array();
				$attributes = array();
				if ($pattributes) {
					$attributes = $pattributes;
					foreach($pattributes as $pattribute) {
						$aterms[$pattribute] = $pterms[$pattribute];
					}
				}

				if ($pmtaction == 'edit' && $mtype_id) {
					$update = array();
					$update["product_id"] = $pid;
					$update["mtype"] = $mtype;
					$update["attributes"] = serialize($attributes);
					$update["aterms"] = serialize($aterms);
					$update["title"] = $title;
					$update["def_quantity"] = $def_quantity;
					$update["numbers"] = $numbers;
					$update["num_style"] = $num_style;
					$update["num_type"] = $num_type;
					$update["bq_numbers"] = $bq_numbers;
					$update["ltext_attr"] = $ltext_attr;
					$update["book_min_quantity"] = $book_min_quantity;
					$update["pq_style"] = $pq_style;
					$update["pq_numbers"] = $pq_numbers;
					$update["sorder"] = $sorder;
					$wpdb->update($wpdb->prefix."print_products_matrix_types", $update, array('mtype_id' => $mtype_id));
				} else {
					$insert = array();
					$insert['product_id'] = $pid;
					$insert['mtype'] = $mtype;
					$insert['attributes'] = serialize($attributes);
					$insert['aterms'] = serialize($aterms);
					$insert['title'] = $title;
					$insert['def_quantity'] = $def_quantity;
					$insert['numbers'] = $numbers;
					$insert['num_style'] = $num_style;
					$insert['num_type'] = $num_type;
					$insert['bq_numbers'] = $bq_numbers;
					$insert['ltext_attr'] = $ltext_attr;
					$insert['book_min_quantity'] = $book_min_quantity;
					$insert['pq_style'] = $pq_style;
					$insert['pq_numbers'] = $pq_numbers;
					$insert['sorder'] = $sorder;
					$wpdb->insert($wpdb->prefix."print_products_matrix_types", $insert);
				}
				wp_redirect('post.php?post='.$pid.'&action=edit&matrixopt=1&mtype='.$mtype);
				exit;
			break;
			case "delete":
				$pid = $_GET['pid'];
				$mtype_id = $_GET['mtid'];
				$mtype = $_GET['mtype'];
				if ($mtype_id) {
					$wpdb->delete($wpdb->prefix."print_products_matrix_types", array('mtype_id' => $mtype_id));
				}
				wp_redirect('post.php?post='.$pid.'&action=edit&matrixopt=1&mtype='.$mtype);
				exit;
			break;
		}
	}
}

function print_products_price_matrix_types() {
	global $wpdb, $attribute_names, $print_products_settings;
	$pmtaction = $_GET['pmtaction'];
	$pid = $_GET['pid'];
	$mtid = (int)$_GET['mtid'];
	$mtype = (int)$_GET['mtype'];
	if ($mtid) { $pmtaction = 'edit'; }

	$area_unit = (int)get_post_meta($pid, '_area_unit', true);
	$auval = print_products_get_area_unit($area_unit);

	$mtype_names = print_products_price_matrix_get_types();
	$num_types = print_products_get_num_types($auval);
	$num_type_labels = print_products_get_num_type_labels($auval);

	$pptype = print_products_get_type($pid);
	$size_attribute = $print_products_settings['size_attribute'];
	$printing_attributes = unserialize($print_products_settings['printing_attributes']);
	$finishing_attributes = unserialize($print_products_settings['finishing_attributes']);

	$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
	print_products_price_matrix_attr_names_init($attributes);

	if ($pptype == 'area' && in_array($size_attribute, $printing_attributes)) {
		$sizekey = array_search($size_attribute, $printing_attributes);
		unset($printing_attributes[$sizekey]);
	}

	$mtype_attributes = array(0 => $printing_attributes, 1 => $finishing_attributes);

	$num_styles = array(
		0 => __('Textfield', 'wp2print'),
		1 => __('Dropdown', 'wp2print')
	);
	?>
	<div class="wrap wp2print-wrap">
		<?php
			$product_data = get_post($pid);
			$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $pid));

			$attributes_vals = array();
			$aterms_vals = array();
			$numbers_val = '';
			$num_style_val = '';
			$num_type_val = '';
			$title_val = '';
			$def_quantity_val = '1';
			$pq_style_val = '';
			$bq_numbers_val = '';
			$book_min_quantity_val = '';
			$pq_numbers_val = '';
			$sorder_val = 0;
			$ltext_attr_val = 0;
			$texts_attributes = array();
			if ($pptype == 'book') { $num_type_val = 1; }
			if ($pptype == 'area') { $num_type_val = 2; }
			// select size attribute from first matrix
			if ($pptype == 'book' && $mtype == 0) {
				$product_type_first_matrix_type_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder LIMIT 0, 1", $wpdb->prefix, $pid));
				if ($product_type_first_matrix_type_data) {
					$fmt_aterms = unserialize($product_type_first_matrix_type_data->aterms);
					if ($fmt_aterms[$size_attribute]) {
						$attributes_vals = array($size_attribute);
						$aterms_vals = $fmt_aterms[$size_attribute];
					}
				}
			}
			if ($pmtaction == 'edit') {
				$product_type_matrix_type_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE mtype_id = %s", $wpdb->prefix, $mtid));
				if ($product_type_matrix_type_data) {
					$mtype = $product_type_matrix_type_data->mtype;
					$numbers_val = $product_type_matrix_type_data->numbers;
					$num_style_val = $product_type_matrix_type_data->num_style;
					$num_type_val = $product_type_matrix_type_data->num_type;
					$sorder_val = $product_type_matrix_type_data->sorder;
					$title_val = $product_type_matrix_type_data->title;
					$def_quantity_val = $product_type_matrix_type_data->def_quantity;
					$bq_numbers_val = $product_type_matrix_type_data->bq_numbers;
					$ltext_attr_val = (int)$product_type_matrix_type_data->ltext_attr;
					$book_min_quantity_val = $product_type_matrix_type_data->book_min_quantity;
					$pq_style_val = $product_type_matrix_type_data->pq_style;
					$pq_numbers_val = $product_type_matrix_type_data->pq_numbers;
					$mtattributes = unserialize($product_type_matrix_type_data->attributes);
					$mtaterms = unserialize($product_type_matrix_type_data->aterms);
					if (count($mtattributes)) {
						foreach($mtattributes as $mtattribute) {
							$attributes_vals[] = $mtattribute;
						}
					}
					if (is_array($mtaterms)) {
						foreach($mtaterms as $mtaterm_key => $mtaterm_vals) {
							if (is_array($mtaterm_vals)) {
								foreach($mtaterm_vals as $mtaterm_val) {
									$aterms_vals[] = $mtaterm_val;
								}
							}
						}
					}
				}
			}
			?>
			<?php if ($mtype == 1) { ?>
				<h2><?php _e('Attributes for finishing price matrix for'); echo ' "'.$product_data->post_title.'"'; ?></h2>
			<?php } else { ?>
				<h2><?php _e('Attributes for printing price matrix for'); echo ' "'.$product_data->post_title.'"'; ?></h2>
			<?php } ?>
			<hr>
			<?php if ($mtype_attributes[$mtype]) { ?>
				<form action="edit.php?post_type=product&page=print-products-price-matrix-options" method="POST" class="mtform">
				<input type="hidden" name="print_products_price_matrix_types_action" value="submit">
				<input type="hidden" name="pmtaction" value="<?php echo $pmtaction; ?>">
				<input type="hidden" name="mtype" value="<?php echo $mtype; ?>">
				<input type="hidden" name="pid" value="<?php echo $pid; ?>">
				<?php if ($pmtaction == 'edit') { ?>
					<input type="hidden" name="mtid" value="<?php echo $mtid; ?>">
				<?php } ?>
				<table>
					<tr>
						<td><strong><?php _e('Attributes', 'wp2print'); ?>:&nbsp;</strong>
						<?php print_products_help_icon('attributes'); ?>
						<td>
							<table cellspacing="0" cellpadding="0" class="pmo-attributes">
								<tr>
									<td>
										<?php
											$tpattributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies WHERE attribute_id IN (%s) ORDER BY attribute_order, attribute_label", $wpdb->prefix, implode(',', $mtype_attributes[$mtype])));
											if ($tpattributes) { ?>
											<table class="wp-list-table widefat striped">
												<?php
												foreach($tpattributes as $attribute) {
													$attr_slug = $attribute->attribute_name;
													$attr_type = $attribute->attribute_type;
													$attr_terms = $wpdb->get_results(sprintf("SELECT t.* FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'pa_%s' ORDER BY t.term_order, t.name", $wpdb->prefix, $wpdb->prefix, $attr_slug));
													if ($attr_type == 'text') { $texts_attributes[$attribute->attribute_id] = $attribute->attribute_label; }

													$ch = ''; if (in_array($attribute->attribute_id, $attributes_vals)) { $ch = ' CHECKED'; } ?>
													<tr>
														<td><input type="checkbox" name="attributes[]" value="<?php echo $attribute->attribute_id; ?>" class="attr-<?php echo $attribute->attribute_id; ?>"<?php echo $ch; ?>><?php echo $attribute->attribute_label; ?>&nbsp;</td>
														<td>
															<?php if ($attr_terms) {
																foreach($attr_terms as $attr_term) { $ch = ''; if (in_array($attr_term->term_id, $aterms_vals)) { $ch = ' CHECKED'; } ?>
																	<input type="checkbox" name="aterms[<?php echo $attribute->attribute_id; ?>][]" value="<?php echo $attr_term->term_id; ?>" rel="attr-<?php echo $attribute->attribute_id; ?>" class="atermitem"<?php echo $ch; ?>><?php echo $attr_term->name; ?><br>
																<?php
																}
															} else { ?>
																<?php if ($attr_type == 'text') { echo '&nbsp;'; } else { _e('Please add attribute terms.', 'wp2print'); } ?>
															<?php } ?>
														</td>
													</tr>
												<?php } ?>
											</table>
											<?php } else { ?>
												<?php _e('Please add product attributes.', 'wp2print'); ?>
											<?php } ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php if ($mtype == 0) { ?>
						<?php if ($pptype == 'book') { ?>
							<tr>
								<td><strong><?php _e('Attribute prefix', 'wp2print'); ?>:</strong>
								<?php print_products_help_icon('attribute_prefix'); ?></td>
								<td><input type="text" name="title" value="<?php echo $title_val; ?>" style="width:300px;" placeholder="<?php _e('Prefix text for attribute label', 'wp2print'); ?>"></td>
							</tr>
						<?php } else { ?>
							<tr>
								<td><strong><?php _e('Quantity display style', 'wp2print'); ?>:</strong>
								<?php print_products_help_icon('quantity_display_style'); ?></td>
								<td><select name="num_style">
									<?php foreach($num_styles as $nskey => $nsval) { $s = ''; if ($nskey == $num_style_val) { $s = ' SELECTED'; } ?>
										<option value="<?php echo $nskey; ?>"<?php echo $s; ?>><?php echo $nsval; ?></option>
									<?php } ?>
								</select></td>
							</tr>
						<?php } ?>
					<?php } ?>
					<tr>
						<td><strong><?php _e('Proportional quantity', 'wp2print'); ?>:</strong>
						<?php print_products_help_icon('proportional_quantity'); ?></td>
						<td><select name="num_type" class="num-type" onchange="print_products_num_type()">
							<?php foreach($num_types as $ntkey => $ntval) { $s = ''; if ($ntkey == $num_type_val) { $s = ' SELECTED'; } ?>
								<option value="<?php echo $ntkey; ?>"<?php echo $s; ?>><?php echo $ntval; ?></option>
							<?php } ?>
						</select></td>
					</tr>
					<tr>
						<td><strong class="numbers-label">
							<?php foreach($num_type_labels as $ntl_key => $ntl_val) { ?>
								<font class="nlabel nlabel-<?php echo $ntl_key; ?>"><?php echo $ntl_val; ?></font>
							<?php } ?>:</strong>
						<?php print_products_help_icon('quantities'); ?></td>
						<td><input type="text" name="numbers" value="<?php echo $numbers_val; ?>" style="width:300px;" placeholder="<?php _e('Enter a comma separated list of numbers', 'wp2print'); ?>"></td>
					</tr>
					<?php if ($texts_attributes && count($texts_attributes)) { ?>
					<tr class="ltext-attr-tr">
						<td><strong><?php _e('Letter text attribute', 'wp2print'); ?>:</strong>
						<?php print_products_help_icon('letter_text_attribute'); ?></td>
						<td><select name="ltext_attr">
							<?php foreach($texts_attributes as $ta_id => $ta_name) { ?>
								<option value="<?php echo $ta_id; ?>"<?php if ($ta_id == $ltext_attr_val) { echo ' SELECTED'; } ?>><?php echo $ta_name; ?></option>
							<?php } ?>
						</select></td>
					</tr>
					<?php } ?>
					<?php if ($mtype == 0) { ?>
						<?php if ($pptype == 'book') { ?>
						<tr>
							<td><strong><?php _e('Books quantity display style', 'wp2print'); ?>:</strong>
							<?php print_products_help_icon('quantity_display_style'); ?></td>
							<td><select name="num_style" onchange="print_products_bq_style()" class="bq-style">
								<?php foreach($num_styles as $nskey => $nsval) { $s = ''; if ($nskey == $num_style_val) { $s = ' SELECTED'; } ?>
									<option value="<?php echo $nskey; ?>"<?php echo $s; ?>><?php echo $nsval; ?></option>
								<?php } ?>
							</select></td>
						</tr>
						<tr class="bq-numbers-tr">
							<td><strong><?php _e('Books quantity numbers', 'wp2print'); ?>:</strong>
							<?php print_products_help_icon('quantities'); ?></td>
							<td><input type="text" name="bq_numbers" value="<?php echo $bq_numbers_val; ?>" style="width:300px;" placeholder="<?php _e('Enter a comma separated list of numbers', 'wp2print'); ?>"></td>
						</tr>
						<tr class="bq-min-tr">
							<td><strong><?php _e('Min. Quantity of Books', 'wp2print'); ?>:</strong>
							<?php print_products_help_icon('quantities'); ?></td>
							<td><input type="text" name="book_min_quantity" value="<?php echo $book_min_quantity_val; ?>" style="width:100px;"></td>
						</tr>
						<tr>
							<td><strong><?php _e('Pages quantity display style', 'wp2print'); ?>:</strong>
							<?php print_products_help_icon('quantity_display_style'); ?></td>
							<td><select name="pq_style" onchange="print_products_pq_style()" class="pq-style">
								<?php foreach($num_styles as $nskey => $nsval) { $s = ''; if ($nskey == $pq_style_val) { $s = ' SELECTED'; } ?>
									<option value="<?php echo $nskey; ?>"<?php echo $s; ?>><?php echo $nsval; ?></option>
								<?php } ?>
							</select></td>
						</tr>
						<tr class="pq-numbers-tr">
							<td><strong><?php _e('Pages quantity numbers', 'wp2print'); ?>:</strong>
							<?php print_products_help_icon('quantities'); ?></td>
							<td><input type="text" name="pq_numbers" value="<?php echo $pq_numbers_val; ?>" style="width:300px;" placeholder="<?php _e('Enter a comma separated list of numbers', 'wp2print'); ?>"></td>
						</tr>
						<tr class="pq-defval-tr">
							<td><strong><?php _e('Default value for quantity', 'wp2print'); ?>:</strong>
							<?php print_products_help_icon('default_quantity_value'); ?></td>
							<td><input type="text" name="def_quantity" value="<?php echo $def_quantity_val; ?>" style="width:100px;" placeholder="<?php _e('Default value for quantity field', 'wp2print'); ?>"></td>
						</tr>
						<?php } ?>
					<?php } ?>
					<tr>
						<td><strong><?php _e('Display Sort Order', 'wp2print'); ?>:</strong>
						<?php print_products_help_icon('display_sort_order'); ?></td>
						<td><input type="text" name="sorder" value="<?php echo $sorder_val; ?>" style="width:40px;"></td>
					</tr>
					<tr>
						<td><a class="page-title-action" href="post.php?post=<?php echo $pid; ?>&action=edit&matrixopt=1&mtype=<?php echo $mtype; ?>"><?php _e('Back', 'wp2print'); ?></a></td>
						<td><p class="submit"><input type="submit" class="button-primary" value="<?php if ($pmtaction == 'edit') { _e('Update', 'wp2print'); } else { _e('Add', 'wp2print'); } ?>" /></p></td>
					</tr>
				</table>
				</form>
			<?php } else { ?>
				<?php _e('Please check attributes options.', 'wp2print'); ?>
			<?php } ?>
			<hr>
	</div>
<?php
}
?>