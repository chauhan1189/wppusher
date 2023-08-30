<?php
add_action('wp_loaded', 'print_products_price_matrix_values_init');
function print_products_price_matrix_values_init() {
	global $wpdb, $attribute_names, $attribute_types, $print_products_settings;
	$postage_attribute = (int)$print_products_settings['postage_attribute'];
	// form submit
	if (isset($_POST['print_products_price_matrix_values_action'])) {
		set_time_limit(3600);
		switch ($_POST['print_products_price_matrix_values_action']) {
			case "submit":
				$pid = $_POST['pid'];
				$mtype_id = $_POST['mtype_id'];
				$mprices = $_POST['mprices'];
				$decimal_separator = wc_get_price_decimal_separator();
				$thousand_separator = wc_get_price_thousand_separator();
				if ($mtype_id) {
					$wpdb->delete($wpdb->prefix."print_products_matrix_prices", array('mtype_id' => $mtype_id));
					if (count($mprices)) {
						foreach($mprices as $mpkey => $mprice) {
							$garray = explode('|', $mpkey);
							$aterms = $garray[0];
							$number = $garray[1];

							$mprice = str_replace('$', '', $mprice);
							$mprice = str_replace($thousand_separator, '', $mprice);
							if ($decimal_separator != '.') {
								$mprice = str_replace($decimal_separator, '.', $mprice);
							}

							$insert = array();
							$insert['mtype_id'] = $mtype_id;
							$insert['aterms'] = $aterms;
							$insert['number'] = $number;
							$insert['price'] = $mprice;
							$wpdb->insert($wpdb->prefix."print_products_matrix_prices", $insert);
						}
					}
				}
				print_products_update_product_price($pid);
				wp_redirect('post.php?post='.$pid.'&action=edit&matrixp=1');
				exit;
			break;
			case "import":
			case "export":
				$csvsep = ',';
				$csvnl = "\r\n";
				$is_import = false;
				$mtype_id = $_POST['mtype_id'];
				$size_attribute = $print_products_settings['size_attribute'];

				$import_csv_data = array();
				if ($_POST['print_products_price_matrix_values_action'] == 'import') {
					require_once('includes/image.php');
					require_once('includes/file.php');
					require_once('includes/media.php');
					$is_import = true;

					$ufile = wp_handle_upload($_FILES['import_file'], array('test_form' => false), current_time('mysql'));
					if ($ufile && !$ufile['error']) {
						$csv_file = $ufile['file'];
						if (($handle = fopen($csv_file, "r")) !== false) {
							$clnmb = 0;
							while (($data = fgetcsv($handle, 1000, $csvsep)) !== false) {
								if ($clnmb > 0) {
									$import_csv_data[] = $data;
								}
								$clnmb++;
							}
							fclose($handle);
						}
					}
				}

				$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
				print_products_price_matrix_attr_names_init($attributes);

				$terms_names = array();
				$terms_attrs = array();
				$terms_attrs_name = array();
				if ($attributes) {
					$taxs = array();
					$attr_ids = array();
					$attr_names = array();
					foreach($attributes as $attribute) {
						$taxs[] = 'pa_'.$attribute->attribute_name;
						$attr_ids['pa_'.$attribute->attribute_name] = $attribute->attribute_id;
						$attr_names['pa_'.$attribute->attribute_name] = $attribute->attribute_label;
					}
					$attr_terms = $wpdb->get_results(sprintf("SELECT t.*, tt.taxonomy FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.term_order, t.name", $wpdb->prefix, $wpdb->prefix, implode("','", $taxs)));
					if ($attr_terms) {
						foreach($attr_terms as $attr_term) {
							$terms_names[$attr_term->term_id] = $attr_term->name;
							$terms_attrs[$attr_term->term_id] = $attr_ids[$attr_term->taxonomy];
							$terms_attrs_name[$attr_term->term_id] = $attr_names[$attr_term->taxonomy];
						}
					}
				}

				$product_type_matrix_type_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE mtype_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $mtype_id));
				$pid = $product_type_matrix_type_data->product_id;
				$mtype = $product_type_matrix_type_data->mtype;
				$numbers = explode(',', $product_type_matrix_type_data->numbers);
				$mtattributes = unserialize($product_type_matrix_type_data->attributes);
				$aterms = unserialize($product_type_matrix_type_data->aterms);
				$aterms = print_products_sort_attribute_terms($aterms);
				$product_type = print_products_get_type($pid);

				if ($mtattributes) {
					foreach($mtattributes as $mkey => $mtattribute) {
						if ($attribute_types[$mtattribute] == 'text') {
							unset($mtattributes[$mkey]);
						}
					}
				}

				if ($mtype == 1 && !in_array($postage_attribute, $mtattributes) && ($product_type == 'fixed' || $product_type == 'book')) {
					$printing_matrix_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY mtype, sorder LIMIT 0, 1", $wpdb->prefix, $pid));
					if ($printing_matrix_data) {
						$paterms = unserialize($printing_matrix_data->aterms);
						if ($paterms[$size_attribute]) {
							$aterms[$size_attribute] = $paterms[$size_attribute];
							$sarray = array($size_attribute);
							$mtattributes = array_merge($sarray, $mtattributes);
						}
					}
				}

				$matrix_prices = array();
				$print_products_matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
				if ($print_products_matrix_prices) {
					foreach($print_products_matrix_prices as $print_products_matrix_price) {
						$key = $print_products_matrix_price->aterms.'|'.$print_products_matrix_price->number;
						$matrix_prices[$key] = $print_products_matrix_price->price;
					}
				}

				$import_update_data = array();
				if ($mtype == 1) {
					$csv_filename = 'export-finishing-prices-'.$mtype_id.'.csv';
					if (in_array($postage_attribute, $mtattributes)) {
						$file_content .= __('Option', 'wp2print').$csvsep.__('Option item', 'wp2print');
						foreach ($numbers as $number) {
							$file_content .= $csvsep.$number;
						}
						$file_content .= $csvnl;
						$matrix_sets = print_products_price_finishing_matrix_get_array($mtattributes, $aterms);
						foreach($matrix_sets as $mskey => $matrix_set) { $fkeys = array();
							$sep = '';
							if (is_array($matrix_set)) {
								foreach ($matrix_set as $term_id) { $fkeys[] = $terms_attrs[$term_id].':'.$term_id;
									if ($terms_attrs[$term_id] != $size_attribute) {
										$file_content .= $sep.$terms_attrs_name[$term_id];
										$sep = $csvsep;
									}
									$file_content .= $sep.$terms_names[$term_id];
									$sep = $csvsep;
								}
							} else {
								$term_id = $matrix_set;
								$fkeys[] = $terms_attrs[$term_id].':'.$term_id;
								if ($terms_attrs[$term_id] != $size_attribute) {
									$file_content .= $sep.$terms_attrs_name[$term_id];
									$sep = $csvsep;
								}
								$file_content .= $sep.$terms_names[$term_id];
								$sep = $csvsep;
							}
							$import_line = $import_csv_data[$mskey];
							$imp_nmb = count($fkeys) + 1;
							foreach ($numbers as $number) {
								$fkey = implode('-', $fkeys).'|'.$number;
								$price_val = $matrix_prices[$fkey];
								$file_content .= $csvsep.$price_val;

								$import_price = $import_line[$imp_nmb];
								$import_update_data[] = $fkey.'|'.$import_price;
								$imp_nmb++;
							}
							$file_content .= $csvnl;
						}
					} else if (in_array($size_attribute, $mtattributes)) {
						$file_content .= __('Size', 'wp2print').$csvsep.__('Option', 'wp2print').$csvsep.__('Option item', 'wp2print');
						foreach ($numbers as $number) {
							$file_content .= $csvsep.$number;
						}
						$file_content .= $csvnl;

						$matrix_sets = print_products_price_finishing_matrix_get_array($mtattributes, $aterms);
						foreach($matrix_sets as $mskey => $matrix_set) { $fkeys = array();
							$sep = '';
							foreach ($matrix_set as $term_id) { $fkeys[] = $terms_attrs[$term_id].':'.$term_id;
								if ($terms_attrs[$term_id] != $size_attribute) {
									$file_content .= $sep.$terms_attrs_name[$term_id];
									$sep = $csvsep;
								}
								$file_content .= $sep.$terms_names[$term_id];
								$sep = $csvsep;
							}
							$import_line = $import_csv_data[$mskey];
							$imp_nmb = count($fkeys) + 1;
							foreach ($numbers as $number) {
								$fkey = implode('-', $fkeys).'|'.$number;
								$price_val = $matrix_prices[$fkey];
								$file_content .= $csvsep.$price_val;

								$import_price = $import_line[$imp_nmb];
								$import_update_data[] = $fkey.'|'.$import_price;
								$imp_nmb++;
							}
							$file_content .= $csvnl;
						}
					} else {
						$file_content .= __('Option', 'wp2print').$csvsep.__('Option item', 'wp2print');
						foreach ($numbers as $number) {
							$file_content .= $csvsep.$number;
						}
						$file_content .= $csvnl;

						$ilnmb = 0;
						foreach($mtattributes as $ma_key => $mattr_id) {
							foreach($aterms[$mattr_id] as $mskey => $term_id) {
								$file_content .= $terms_attrs_name[$term_id];
								$file_content .= $csvsep.$terms_names[$term_id];
								$import_line = $import_csv_data[$ilnmb];
								$imp_nmb = 2;
								foreach ($numbers as $number) {
									$fkey = $terms_attrs[$term_id].':'.$term_id.'|'.$number;
									$price_val = $matrix_prices[$fkey];
									$file_content .= $csvsep.$price_val;

									$import_price = $import_line[$imp_nmb];
									$import_update_data[] = $fkey.'|'.$import_price;
									$imp_nmb++;
								}
								$ilnmb++;
								$file_content .= $csvnl;
							}
						}
					}
				} else {
					$sep = '';
					$csv_filename = 'export-printing-prices-'.$mtype_id.'.csv';
					foreach ($mtattributes as $mtattribute) {
						$file_content .= $sep.$attribute_names[$mtattribute];
						$sep = $csvsep;
					}
					foreach ($numbers as $number) {
						$file_content .= $sep.$number;
					}
					$file_content .= $csvnl;

					$matrix_sets = print_products_price_matrix_get_array($mtattributes, $aterms);
					foreach($matrix_sets as $mskey => $matrix_set) { $fkeys = array();
						$sep = '';
						foreach ($matrix_set as $term_id) {
							$fkeys[] = $terms_attrs[$term_id].':'.$term_id;
							$file_content .= $sep.$terms_names[$term_id];
							$sep = $csvsep;
						}
						$import_line = $import_csv_data[$mskey];
						$imp_nmb = count($fkeys);
						foreach ($numbers as $number) {
							$fkey = implode('-', $fkeys).'|'.$number;
							$price_val = $matrix_prices[$fkey];
							$file_content .= $sep.$price_val;

							$import_price = $import_line[$imp_nmb];
							$import_update_data[] = $fkey.'|'.$import_price;
							$imp_nmb++;
						}
						$file_content .= $csvnl;
					}
				}
				if ($is_import) {
					if (count($import_csv_data)) {
						$mtype_id_data = array();
						$mtype_id_rows = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
						if ($mtype_id_rows) {
							foreach($mtype_id_rows as $mtype_id_row) {
								$mtype_id_data[] = $mtype_id_row->aterms.'|'.$mtype_id_row->number;
							}
						}
						foreach($import_update_data as $update_data) {
							$udata = explode('|', $update_data);
							$aterms = $udata[0];
							$number = $udata[1];
							$price = $udata[2];

							$price = str_replace('$', '', $price);
							$price = str_replace(',', '', $price);
							$price = (float)$price;

							if (in_array($aterms.'|'.$number, $mtype_id_data)) {
								$update = array();
								$update['price'] = $price;
								$wpdb->update($wpdb->prefix."print_products_matrix_prices", $update, array('mtype_id' => $mtype_id, 'aterms' => $aterms, 'number' => $number));
							} else {
								$insert = array();
								$insert['mtype_id'] = $mtype_id;
								$insert['aterms'] = $aterms;
								$insert['number'] = $number;
								$insert['price'] = $price;
								$wpdb->insert($wpdb->prefix."print_products_matrix_prices", $insert);
							}
						}
					}
					print_products_update_product_price($pid);
					wp_redirect('edit.php?post_type=product&page=print-products-price-matrix-values&mtype_id='.$mtype_id);
				} else {
					header("Content-Type: application/zip");
					header("Content-Disposition: attachment; filename=".basename($csv_filename));
					header("Content-Length: ".strlen($file_content));
					echo($file_content);
				}
				exit;
			break;
		}
	}
}

function print_products_price_matrix_values() {
	global $wpdb, $attribute_names, $attribute_types, $print_products_settings;
	$mtype_id = $_GET['mtype_id'];
	$mtype_names = print_products_price_matrix_get_types();
	$size_attribute = $print_products_settings['size_attribute'];
	$postage_attribute = (int)$print_products_settings['postage_attribute'];
	$num_types = print_products_get_num_type_labels();
	$price_decimals = wc_get_price_decimals();
	$decimal_separator = wc_get_price_decimal_separator();
	$thousand_separator = wc_get_price_thousand_separator();

	print_products_check_attributes_slug();

	$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
	print_products_price_matrix_attr_names_init($attributes);

	$terms_names = array();
	$terms_attrs = array();
	$terms_attrs_name = array();
	if ($attributes) {
		$taxs = array();
		$attr_ids = array();
		$attr_names = array();
		foreach($attributes as $attribute) {
			$taxs[] = 'pa_'.$attribute->attribute_name;
			$attr_ids['pa_'.$attribute->attribute_name] = $attribute->attribute_id;
			$attr_names['pa_'.$attribute->attribute_name] = $attribute->attribute_label;
		}
		$attr_terms = $wpdb->get_results(sprintf("SELECT t.*, tt.taxonomy FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.term_order, t.name", $wpdb->prefix, $wpdb->prefix, implode("','", $taxs)));
		if ($attr_terms) {
			foreach($attr_terms as $attr_term) {
				$terms_names[$attr_term->term_id] = $attr_term->name;
				$terms_attrs[$attr_term->term_id] = $attr_ids[$attr_term->taxonomy];
				$terms_attrs_name[$attr_term->term_id] = $attr_names[$attr_term->taxonomy];
			}
		}
	}
?>
	<div class="wrap">
		<?php if ($mtype_id) {
			$product_type_matrix_type_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE mtype_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $mtype_id));
			$pid = $product_type_matrix_type_data->product_id;
			$mtype = $product_type_matrix_type_data->mtype;
			$numbers = explode(',', $product_type_matrix_type_data->numbers);
			$mtattributes = unserialize($product_type_matrix_type_data->attributes);
			$aterms = unserialize($product_type_matrix_type_data->aterms);
			$num_type = $product_type_matrix_type_data->num_type;
			$product_type = print_products_get_type($pid);
			$area_unit = (int)get_post_meta($pid, '_area_unit', true);
			$auval = print_products_get_area_unit($area_unit);

			$aterms = print_products_sort_attribute_terms($aterms);

			$num_types = print_products_get_num_type_labels($auval);

			if ($mtattributes) {
				foreach($mtattributes as $mkey => $mtattribute) {
					if ($attribute_types[$mtattribute] == 'text') {
						unset($mtattributes[$mkey]);
					}
				}
			}

			if ($mtype == 1 && !in_array($postage_attribute, $mtattributes) && ($product_type == 'fixed' || $product_type == 'book')) {
				$printing_matrix_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY mtype, sorder LIMIT 0, 1", $wpdb->prefix, $pid));
				if ($printing_matrix_data) {
					$paterms = unserialize($printing_matrix_data->aterms);
					if ($paterms[$size_attribute]) {
						$aterms[$size_attribute] = $paterms[$size_attribute];
						$sarray = array($size_attribute);
						$mtattributes = array_merge($sarray, $mtattributes);
					}
				}
			}

			$product_data = get_post($pid);

			$matrix_prices = array();
			$print_products_matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
			if ($print_products_matrix_prices) {
				foreach($print_products_matrix_prices as $print_products_matrix_price) {
					$key = $print_products_matrix_price->aterms.'|'.$print_products_matrix_price->number;
					$matrix_prices[$key] = $print_products_matrix_price->price;
				}
			}
			?>

			<?php if ($mtype == 1) { ?>
				<h2><?php echo __('Prices for Finishing for', 'wp2print').' "'.$product_data->post_title.'"'; ?></h2><br>
			<?php } else { ?>
				<h2><?php echo __('Prices for Printing for', 'wp2print').' "'.$product_data->post_title.'"'; ?></h2><br>
			<?php } ?>

			<form action="edit.php?post_type=product&page=print-products-price-matrix-values" method="POST" class="matrix-values" enctype="multipart/form-data">
			<input type="hidden" name="print_products_price_matrix_values_action" value="submit" class="matrix-values-action">
			<input type="hidden" name="mtype_id" value="<?php echo $mtype_id; ?>">
			<input type="hidden" name="pid" value="<?php echo $pid; ?>">
			<?php if ($mtattributes) { ?>
				<?php if ($mtype == 1) {
					if (in_array($postage_attribute, $mtattributes)) {
						$matrix_sets = print_products_price_finishing_matrix_get_array($mtattributes, $aterms); ?>
						<table class="wp-list-table widefat striped">
							<tr>
								<td><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
								<td colspan="<?php echo count($numbers); ?>" align="center"><strong><?php echo $num_types[$num_type]; ?></strong></td>
							</tr>
							<tr>
								<td><strong><?php _e('Option', 'wp2print'); ?></strong></td>
								<?php foreach ($numbers as $number) { ?>
									<td align="center"><strong><?php echo $number; ?></strong></td>
								<?php } ?>
							</tr>
							<?php foreach($matrix_sets as $matrix_set) { $fkeys = array(); ?>
								<tr>
									<?php if (is_array($matrix_set)) { ?>
										<?php foreach ($matrix_set as $term_id) { $fkeys[] = $terms_attrs[$term_id].':'.$term_id; ?>
											<?php if ($terms_attrs[$term_id] != $size_attribute) { ?>
												<td><?php echo $terms_attrs_name[$term_id]; ?></td>
											<?php } ?>
											<td><?php echo $terms_names[$term_id]; ?></td>
										<?php } ?>
									<?php } else {
										$term_id = $matrix_set;
										$fkeys[] = $terms_attrs[$term_id].':'.$term_id; ?>
										<td><?php echo $terms_names[$term_id]; ?></td>
									<?php } ?>
									<?php foreach ($numbers as $number) {
										$fkey = implode('-', $fkeys).'|'.$number;
										$price_val = $matrix_prices[$fkey]; ?>
										<td align="center"><input type="text" name="mprices[<?php echo $fkey; ?>]" value="<?php echo $price_val; ?>" class="mpricefld"></td>
									<?php } ?>
								</tr>
							<?php } ?>
						</table>
						<?php
					} else if (in_array($size_attribute, $mtattributes)) {
						$matrix_sets = print_products_price_finishing_matrix_get_array($mtattributes, $aterms); ?>
						<table class="wp-list-table widefat striped">
							<tr>
								<td colspan="3"><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
								<td colspan="<?php echo count($numbers); ?>" align="center"><strong><?php echo $num_types[$num_type]; ?></strong></td>
							</tr>
							<tr>
								<td><strong><?php _e('Size', 'wp2print'); ?></strong></td>
								<td><strong><?php _e('Option', 'wp2print'); ?></strong></td>
								<td><strong><?php _e('Option item', 'wp2print'); ?></strong></td>
								<?php foreach ($numbers as $number) { ?>
									<td align="center"><strong><?php echo $number; ?></strong></td>
								<?php } ?>
							</tr>
							<?php foreach($matrix_sets as $matrix_set) { $fkeys = array(); ?>
								<tr>
									<?php foreach ($matrix_set as $term_id) { $fkeys[] = $terms_attrs[$term_id].':'.$term_id; ?>
										<?php if ($terms_attrs[$term_id] != $size_attribute) { ?>
											<td><?php echo $terms_attrs_name[$term_id]; ?></td>
										<?php } ?>
										<td><?php echo $terms_names[$term_id]; ?></td>
									<?php } ?>
									<?php foreach ($numbers as $number) {
										$fkey = implode('-', $fkeys).'|'.$number;
										$price_val = $matrix_prices[$fkey]; ?>
										<td align="center"><input type="text" name="mprices[<?php echo $fkey; ?>]" value="<?php echo $price_val; ?>" class="mpricefld"></td>
									<?php } ?>
								</tr>
							<?php } ?>
						</table>
					<?php } else { ?>
						<table class="wp-list-table widefat striped">
							<tr>
								<td colspan="2"><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
								<td colspan="<?php echo count($numbers); ?>" align="center"><strong><?php echo $num_types[$num_type]; ?></strong></td>
							</tr>
							<tr>
								<td><strong><?php _e('Option', 'wp2print'); ?></strong></td>
								<td><strong><?php _e('Option item', 'wp2print'); ?></strong></td>
								<?php foreach ($numbers as $number) { ?>
									<td align="center"><strong><?php echo $number; ?></strong></td>
								<?php } ?>
							</tr>
							<?php foreach($mtattributes as $mattr_id) {
								foreach($aterms[$mattr_id] as $term_id) { ?>
									<tr>
										<td><?php echo $terms_attrs_name[$term_id]; ?></td>
										<td><?php echo $terms_names[$term_id]; ?></td>
										<?php foreach ($numbers as $number) {
											$fkey = $terms_attrs[$term_id].':'.$term_id.'|'.$number;
											$price_val = $matrix_prices[$fkey]; ?>
											<td align="center"><input type="text" name="mprices[<?php echo $fkey; ?>]" value="<?php echo $price_val; ?>" class="mpricefld"></td>
										<?php } ?>
									</tr>
							<?php }} ?>
						</table>
					<?php } ?>
				<?php } else {
					$matrix_sets = print_products_price_matrix_get_array($mtattributes, $aterms); ?>
					<table class="wp-list-table widefat striped">
						<tr>
							<td colspan="<?php echo count($mtattributes); ?>"><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
							<td colspan="<?php echo count($numbers); ?>" align="center"><strong><?php echo $num_types[$num_type]; ?></strong></td>
						</tr>
						<tr>
							<?php foreach ($mtattributes as $mtattribute) { ?>
								<td><strong><?php echo $attribute_names[$mtattribute]; ?></strong></td>
							<?php } ?>
							<?php foreach ($numbers as $number) { ?>
								<td align="center"><strong><?php echo $number; ?></strong></td>
							<?php } ?>
						</tr>
						<?php foreach($matrix_sets as $matrix_set) { $fkeys = array(); ?>
							<tr>
								<?php foreach ($matrix_set as $term_id) { $fkeys[] = $terms_attrs[$term_id].':'.$term_id; ?>
									<td><?php echo $terms_names[$term_id]; ?></td>
								<?php } ?>
								<?php foreach ($numbers as $number) {
									$fkey = implode('-', $fkeys).'|'.$number;
									$price_val = $matrix_prices[$fkey]; ?>
									<td align="center"><input type="text" name="mprices[<?php echo $fkey; ?>]" value="<?php echo $price_val; ?>" class="mpricefld"></td>
								<?php } ?>
							</tr>
						<?php } ?>
					</table>
				<?php } ?>
				<p class="submit">
					<div style="width:30%; float:left;">
						<a class="page-title-action" href="post.php?post=<?php echo $pid; ?>&action=edit&matrixp=1"><?php _e('Back', 'wp2print'); ?></a><input type="submit" class="button-primary" value="<?php _e('Update', 'wp2print'); ?>" onclick="jQuery('form.matrix-values .matrix-values-action').val('submit');" />
					</div>
					<?php if ($mtype_id) { ?>
					<div style="float:right; text-align:right; padding-left:10px;">
						<input type="button" class="button-primary" value="<?php _e('Export Prices', 'wp2print'); ?>" onclick="pmv_export();" />
					</div>
					<div style="float:right; border-right:1px solid #C1C1C1; padding-right:10px;">
						<div style="width:200px; float:left; overflow:hidden;">
							<input type="file" name="import_file">
						</div>
						<div style="float:left;">
							<input type="button" class="button-primary" value="<?php _e('Import Prices', 'wp2print'); ?>" onclick="pmv_import();" />
						</div>
					</div>
					<?php } ?>
				</p>
			<?php } ?>
			</form>
			<script>
			function pmv_import() {
				jQuery('form.matrix-values .matrix-values-action').val('import');
				jQuery('form.matrix-values').submit();
			}
			function pmv_export() {
				jQuery('form.matrix-values .matrix-values-action').val('export');
				jQuery('form.matrix-values').submit();
			}
			</script>
		<?php } ?>
	</div>
<?php
}
?>