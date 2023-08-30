<?php
add_action('wp_loaded', 'print_products_price_matrix_sku_init');
function print_products_price_matrix_sku_init() {
	global $wpdb, $attribute_names, $attribute_types, $print_products_settings;
	// form submit
	if (isset($_POST['print_products_price_matrix_sku_action'])) {
		switch ($_POST['print_products_price_matrix_sku_action']) {
			case "submit":
				$pid = $_POST['pid'];
				$mtype_id = $_POST['mtype_id'];
				$msku = $_POST['msku'];
				if ($mtype_id) {
					$wpdb->delete($wpdb->prefix."print_products_matrix_sku", array('mtype_id' => $mtype_id));
					if (count($msku)) {
						foreach($msku as $aterms => $sku) {
							$insert = array();
							$insert['mtype_id'] = $mtype_id;
							$insert['aterms'] = $aterms;
							$insert['sku'] = $sku;
							$wpdb->insert($wpdb->prefix."print_products_matrix_sku", $insert);
						}
					}
				}
				wp_redirect('post.php?post='.$pid.'&action=edit&matrixs=1');
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
				if ($_POST['print_products_price_matrix_sku_action'] == 'import') {
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

				$matrix_sku = array();
				$print_products_matrix_skus = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_sku WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
				if ($print_products_matrix_skus) {
					foreach($print_products_matrix_skus as $print_products_matrix_sku) {
						$matrix_sku[$print_products_matrix_sku->aterms] = $print_products_matrix_sku->sku;
					}
				}

				$import_update_data = array();
				$sep = '';
				$csv_filename = 'export-printing-sku-'.$mtype_id.'.csv';
				foreach ($mtattributes as $mtattribute) {
					$file_content .= $sep.$attribute_names[$mtattribute];
					$sep = $csvsep;
				}
				$file_content .= $sep.__('SKU', 'wp2print').$csvnl;

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
					$fkey = implode('-', $fkeys);
					$sku_val = $matrix_sku[$fkey];
					$file_content .= $sep.$sku_val;

					$import_sku = $import_line[$imp_nmb];
					$import_update_data[] = $fkey.'|'.$import_sku;
					$imp_nmb++;
					$file_content .= $csvnl;
				}
				if ($is_import) {
					if (count($import_csv_data)) {
						$mtype_id_data = array();
						$mtype_id_rows = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_sku WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
						if ($mtype_id_rows) {
							foreach($mtype_id_rows as $mtype_id_row) {
								$mtype_id_data[] = $mtype_id_row->aterms;
							}
						}
						foreach($import_update_data as $update_data) {
							$udata = explode('|', $update_data);
							$aterms = $udata[0];
							$sku = $udata[1];

							if (in_array($aterms, $mtype_id_data)) {
								$update = array();
								$update['sku'] = $sku;
								$wpdb->update($wpdb->prefix."print_products_matrix_sku", $update, array('mtype_id' => $mtype_id, 'aterms' => $aterms));
							} else {
								$insert = array();
								$insert['mtype_id'] = $mtype_id;
								$insert['aterms'] = $aterms;
								$insert['sku'] = $sku;
								$wpdb->insert($wpdb->prefix."print_products_matrix_sku", $insert);
							}
						}
					}
					wp_redirect('edit.php?post_type=product&page=print-products-price-matrix-sku&mtype_id='.$mtype_id);
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

function print_products_price_matrix_sku() {
	global $wpdb, $attribute_names, $attribute_types, $print_products_settings;
	$mtype_id = $_GET['mtype_id'];

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
			$mtattributes = unserialize($product_type_matrix_type_data->attributes);
			$aterms = unserialize($product_type_matrix_type_data->aterms);

			$aterms = print_products_sort_attribute_terms($aterms);

			if ($mtattributes) {
				foreach($mtattributes as $mkey => $mtattribute) {
					if ($attribute_types[$mtattribute] == 'text') {
						unset($mtattributes[$mkey]);
					}
				}
			}

			$product_data = get_post($pid);

			$matrix_sku = array();
			$print_products_matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_sku WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
			if ($print_products_matrix_prices) {
				foreach($print_products_matrix_prices as $print_products_matrix_price) {
					$matrix_sku[$print_products_matrix_price->aterms] = $print_products_matrix_price->sku;
				}
			}
			?>

			<h2><?php echo __('SKU list for Printing for', 'wp2print').' "'.$product_data->post_title.'"'; ?></h2><br>

			<form action="edit.php?post_type=product&page=print-products-price-matrix-sku" method="POST" class="matrix-values" enctype="multipart/form-data">
			<input type="hidden" name="print_products_price_matrix_sku_action" value="submit" class="matrix-values-action">
			<input type="hidden" name="mtype_id" value="<?php echo $mtype_id; ?>">
			<input type="hidden" name="pid" value="<?php echo $pid; ?>">
			<?php if ($mtattributes) {
				$matrix_sets = print_products_price_matrix_get_array($mtattributes, $aterms); ?>
				<table class="wp-list-table widefat striped">
					<tr>
						<td colspan="<?php echo count($mtattributes); ?>"><strong><?php _e('Attributes', 'wp2print'); ?></strong></td>
						<td align="center">&nbsp;</td>
					</tr>
					<tr>
						<?php foreach ($mtattributes as $mtattribute) { ?>
							<td><strong><?php echo $attribute_names[$mtattribute]; ?></strong></td>
						<?php } ?>
						<td align="center"><strong><?php _e('SKU', 'wp2print'); ?></strong></td>
					</tr>
					<?php foreach($matrix_sets as $matrix_set) { $fkeys = array(); ?>
						<tr>
							<?php foreach ($matrix_set as $term_id) { $fkeys[] = $terms_attrs[$term_id].':'.$term_id; ?>
								<td><?php echo $terms_names[$term_id]; ?></td>
							<?php } ?>
							<?php $fkey = implode('-', $fkeys);
							$sku_val = $matrix_sku[$fkey]; ?>
							<td align="center"><input type="text" name="msku[<?php echo $fkey; ?>]" value="<?php echo $sku_val; ?>" class="mskufld"></td>
						</tr>
					<?php } ?>
				</table>
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