<?php
$print_products_cart_data = array();

add_action('wp_loaded', 'print_products_checkout_process_loaded');
function print_products_checkout_process_loaded() {
	print_products_get_cart_data();
	// artwork files upload
	if (isset($_GET['ajaxupload']) && $_GET['ajaxupload'] == 'artwork') {
		if ($_FILES['file']['name']) {
			require_once('./wp-admin/includes/file.php');

			$ufile = wp_handle_upload($_FILES['file'], array('test_form' => false), current_time('mysql'));
			if (!isset($ufile['error'])) {
				echo $ufile['url'];
			}
		}
		if ($_FILES['Filedata']['name']) {
			require_once('./wp-admin/includes/file.php');

			$ufile = wp_handle_upload($_FILES['Filedata'], array('test_form' => false), current_time('mysql'));
			if (!isset($ufile['error'])) {
				echo $ufile['url'];
			}
		}
		exit;
	}

	// update cart action
	if (isset($_REQUEST['print_products_checkout_process_action']) && $_REQUEST['print_products_checkout_process_action'] == 'update-cart') {
		$cart_item_key = $_REQUEST['cart_item_key'];
		switch ($_REQUEST['product_type']) {
			case "fixed":
				print_products_checkout_fixed($cart_item_key, true);
			break;
			case "book":
				print_products_checkout_book($cart_item_key, true);
			break;
			case "area":
				print_products_checkout_area($cart_item_key, true);
			break;
			case "aec":
				print_products_checkout_aec($cart_item_key, true);
			break;
			case "aecbwc":
				print_products_checkout_aecbwc($cart_item_key, true);
			break;
			case "aecsimple":
				print_products_checkout_aecsimple($cart_item_key, true);
			break;
			case "custom":
				print_products_checkout_custom($cart_item_key, true);
			break;
		}

		wp_redirect(wc_get_cart_url());
		exit;
	}

	if (isset($_POST['cart_upload_action']) && $_POST['cart_upload_action'] == 'save') {
		print_products_cart_upload_save();
	}
}

add_action('woocommerce_add_to_cart', 'print_products_add_to_cart', 10, 2);
function print_products_add_to_cart($cart_item_key, $product_id) {
	if (isset($_REQUEST['print_products_checkout_process_action'])) {
		switch ($_REQUEST['print_products_checkout_process_action']) {
			case "add-to-cart":
				$artwork_source = get_post_meta($product_id, '_artwork_source', true);
				switch ($_REQUEST['product_type']) {
					case "fixed":
						print_products_checkout_fixed($cart_item_key);
					break;
					case "book":
						print_products_checkout_book($cart_item_key);
					break;
					case "area":
						print_products_checkout_area($cart_item_key);
					break;
					case "aec":
						print_products_checkout_aec($cart_item_key);
					break;
					case "aecbwc":
						print_products_checkout_aecbwc($cart_item_key);
					break;
					case "aecsimple":
						print_products_checkout_aecsimple($cart_item_key);
					break;
					case "custom":
						print_products_checkout_custom($cart_item_key);
					break;
					case "simple":
						if (strlen($artwork_source)) {
							print_products_checkout_simple($cart_item_key);
						}
					break;
					case "variable":
						if (strlen($artwork_source)) {
							print_products_checkout_variable($cart_item_key);
						}
					break;
				}
			break;
			case "reorder":
				print_products_reorder_product($cart_item_key);
			break;
		}
	}
}

function print_products_add_cart_data($cartdata) {
	global $wpdb;

	$cart_item_key = $cartdata['cart_item_key'];

	$artwork_thumbs = print_products_get_artwork_thumbs($cartdata['artwork_files']);

	$insert = array();
	$insert['cart_item_key'] = $cart_item_key;
	$insert['product_id'] = $cartdata['product_id'];
	$insert['product_type'] = $cartdata['product_type'];
	$insert['quantity'] = $cartdata['quantity'];
	$insert['price'] = $cartdata['price'];
	$insert['product_attributes'] = $cartdata['product_attributes'];
	$insert['additional'] = $cartdata['additional'];
	$insert['artwork_files'] = $cartdata['artwork_files'];
	$insert['artwork_thumbs'] = serialize($artwork_thumbs);
	$insert['atcaction'] = $cartdata['atcaction'];
	$insert['date_added'] = current_time('mysql');
	$wpdb->insert($wpdb->prefix."print_products_cart_data", $insert);
	print_products_get_cart_data();

	WC()->cart->set_quantity($cart_item_key, $cartdata['quantity']);

	unset($_REQUEST['print_products_checkout_process_action']);

	if (strlen($cartdata['additional'])) {
		$additional = unserialize($cartdata['additional']);
		if (isset($additional['addon_products']) && is_array($additional['addon_products']) && count($additional['addon_products'])) {
			foreach($additional['addon_products'] as $ap_pid => $ap_qty) {
				if ($ap_qty && $ap_qty > 0) {
					WC()->cart->add_to_cart($ap_pid, $ap_qty, 0, array(), array('unique_key' => md5(microtime() . rand() . md5($ap_pid))));
				}
			}
		}
	}
}

function print_products_update_cart_data($cartdata) {
	global $wpdb;

	$cart_item_key = $cartdata['cart_item_key'];

	$update = array();
	$update['quantity'] = $cartdata['quantity'];
	$update['price'] = $cartdata['price'];
	$update['product_attributes'] = $cartdata['product_attributes'];
	$update['additional'] = $cartdata['additional'];
	$wpdb->update($wpdb->prefix."print_products_cart_data", $update, array('cart_item_key' => $cart_item_key));
	print_products_get_cart_data();

	WC()->cart->set_quantity($cart_item_key, $cartdata['quantity']);
}

function print_products_get_cart_data() {
	global $wpdb, $print_products_cart_data;
	if (WC()->cart) {
		$cart = WC()->cart->get_cart();
		if ($cart) {
			$cart_item_keys = array();
			foreach ($cart as $cart_item_key => $values) {
				$cart_item_keys[] = $cart_item_key;
			}
			$prod_cart_datas = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key IN ('%s')", $wpdb->prefix, implode("','", $cart_item_keys)));
			if ($prod_cart_datas) {
				foreach($prod_cart_datas as $prod_cart_data) {
					$print_products_cart_data[$prod_cart_data->cart_item_key] = $prod_cart_data;
				}
			}
		}
	}
}

function print_products_get_pspeed_data($product_id, $psval) {
	$pspeed_label = get_post_meta($product_id, '_production_speed_label', true);
	$pspeed_options = get_post_meta($product_id, '_production_speed_options', true);
	$pspeed_sd_data = get_post_meta($product_id, '_production_speed_sd_data', true);

	if (!strlen($pspeed_label)) { $pspeed_label = __('Production speed', 'wp2print'); }
	$pspeed_value = $pspeed_options[$psval]['label'];

	$pspeed_data = $pspeed_label.';'.$pspeed_value;

	if ($pspeed_sd_data && $pspeed_sd_data['show']) {
		$sd_val = print_products_get_shipping_date($pspeed_options[$psval]['days'], $pspeed_sd_data['time'], $pspeed_sd_data['weekend']);
		$pspeed_data = $pspeed_data . ' - ' . $sd_val;
	}

	return $pspeed_data;
}

function print_products_checkout_fixed($cart_item_key, $update = false, $onlyreturn = false) {
	global $wpdb, $attribute_types, $print_products_settings;

	print_products_price_matrix_attr_names_init();

	$sku = '';
	$price = 0;
	$weight = 0;
	$production_speed = '';
	$production_speed_label = '';
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$smparams = $_REQUEST['smparams'];
	$fmparams = $_REQUEST['fmparams'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	$postage_attribute = (int)$print_products_settings['postage_attribute'];

	$addon_products = array();
	if (isset($_REQUEST['addon_products'])) { $addon_products = $_REQUEST['addon_products']; }

	if (!strlen($atcaction)) { $atcaction = 'artwork'; }
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }
	if (isset($_REQUEST['production_speed'])) {
		$production_speed = (int)$_REQUEST['production_speed'];
		$production_speed_label = print_products_get_pspeed_data($product_id, $production_speed);
	}

	$quantity_mailed = 0;
	if (isset($_POST['quantity_mailed'])) {
		$quantity_mailed = (int)$_POST['quantity_mailed'];
	}

	$product_attributes = array();
	if ($smparams) {
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$aterms = $smarray[1];
			$number = $smarray[2];
			$num_type = print_products_get_matrix_num_type($mtype_id);

			$nmb_val = $quantity;
			if ($num_type == 5) {
				$ltext_attr = (int)print_products_get_matrix_ltext_attr($mtype_id);
				$ltext = print_products_get_matrix_ltext($aterms, $ltext_attr);
				$nmb_val = strlen(str_replace(' ', '', $ltext));
			}

			$paterms = print_products_get_matrix_price_aterms($aterms, $attribute_types);

			$nums = print_products_get_matrix_numbers($nmb_val, $mtype_id);
			$smprice = print_products_get_matrix_price($mtype_id, $paterms, $nmb_val, $nums);
			if ($smprice) {
				if ($num_type == 5) {
					$smprice = $smprice * $quantity;
				}
				$price += $smprice;
			}

			$pattributes = array();
			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
				$pattributes[] = $attr_term;
			}

			$weight_qty = $nmb_val;
			if ($quantity_mailed) { $weight_qty = $nmb_val - $quantity_mailed; }
			$pweight = print_products_get_total_product_weight($product_id, 'fixed', $quantity, $weight_qty, $pattributes);
			$weight = $weight + $pweight;
			if (!strlen($sku)) {
				$sku = print_products_get_product_sku($mtype_id, $aterms);
			}
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$mtype_id = $fmarray[0];
			$aterms = $fmarray[1];
			$number = $fmarray[2];
			$aterms_array = explode('-', $aterms);

			$nmb_val = $quantity;

			foreach($aterms_array as $attr_term) {
				$attr_term_array = explode(':', $attr_term);
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
				if ($attr_term_array[0] == $postage_attribute) {
					$nmb_val = $quantity_mailed;
				}
			}

			$paterms = print_products_get_matrix_price_aterms($aterms, $attribute_types);

			$nums = print_products_get_matrix_numbers($nmb_val, $mtype_id);
			$fmprice = print_products_get_matrix_price($mtype_id, $paterms, $nmb_val, $nums);
			if ($fmprice) {
				$price += $fmprice;
			}
		}
	}

	$price = print_products_production_speed_price($product_id, $price, $production_speed);

	$discount_price = print_products_user_discount_get_discount_price($price);

	$price = print_products_user_discount_get_discounted_price($price);

	$price = print_products_order_min_price($product_id, $price);

	$additional = array('weight' => $weight, 'sku' => $sku, 'pspeed' => $production_speed, 'pspeed_label' => $production_speed_label, 'discount_price' => $discount_price, 'addon_products' => $addon_products, 'quantity_mailed' => $quantity_mailed);

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'fixed',
		'quantity' => $quantity,
		'price' => $price,
		'product_attributes' => serialize($product_attributes),
		'additional' => serialize($additional),
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_book($cart_item_key, $update = false, $onlyreturn = false) {
	global $wpdb, $print_products_settings, $attribute_types;

	print_products_price_matrix_attr_names_init();

	$sku = '';
	$price = 0;
	$weight = 0;
	$total_pages = 0;
	$production_speed = '';
	$production_speed_label = '';
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = (int)$_REQUEST['quantity'];
	$smparams = $_REQUEST['smparams'];
	$fmparams = $_REQUEST['fmparams'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	if (!strlen($atcaction)) { $atcaction = 'artwork'; }
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }
	if (isset($_REQUEST['production_speed'])) {
		$production_speed = (int)$_REQUEST['production_speed'];
		$production_speed_label = print_products_get_pspeed_data($product_id, $production_speed);
	}
	$size_attribute = $print_products_settings['size_attribute'];

	$addon_products = array();
	if (isset($_REQUEST['addon_products'])) { $addon_products = $_REQUEST['addon_products']; }

	$bqflag = true;
	$product_attributes = array();
	$page_quantity = array();
	if ($smparams) {
		$smnmb = 0;
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$aterms = $smarray[1];
			$number = $smarray[2];
			$number_type = $smarray[3];

			$atit = print_products_get_matrix_title($mtype_id);

			$pqty = $_REQUEST['page_quantity_'.$mtype_id];

			$page_quantity[$mtype_id] = $pqty;

			$nmb_val = $pqty;
			if ($number_type == 1) {
				$nmb_val = $pqty * $quantity;
				$bqflag = false;
			}

			$paterms = print_products_get_matrix_price_aterms($aterms, $attribute_types);

			$nums = print_products_get_matrix_numbers($nmb_val, $mtype_id);
			$smprice = print_products_get_matrix_price($mtype_id, $paterms, $nmb_val, $nums);
			if ($smprice) {
				$price += $smprice;
			}

			$total_pages = $total_pages + $nmb_val;

			$product_attributes[] = 'pq|'.$atit.':'.$pqty;

			$pattributes = array();
			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				$second_size = false;
				$atar = explode(':', $attr_term);
				if ($smnmb > 0 && $atar[0] == $size_attribute) {
					$second_size = true;
				}
				if (!$second_size) {
					$product_attributes[] = $attr_term;
				}
				$pattributes[] = $attr_term;
			}

			$pweight = print_products_get_total_product_weight($product_id, 'book', $quantity, $nmb_val, $pattributes, $smnmb);
			$weight = $weight + $pweight;
			if (!strlen($sku)) {
				$sku = print_products_get_product_sku($mtype_id, $aterms);
			}
			$smnmb++;
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$mtype_id = $fmarray[0];
			$aterms = $fmarray[1];
			$number = $fmarray[2];
			$number_type = $fmarray[3];

			$nmb_val = $quantity;
			if ($number_type == 1) {
				$nmb_val = $total_pages;
			}

			$paterms = print_products_get_matrix_price_aterms($aterms, $attribute_types);

			$nums = print_products_get_matrix_numbers($nmb_val, $mtype_id);
			$fmprice = print_products_get_matrix_price($mtype_id, $paterms, $nmb_val, $nums);
			if ($fmprice) {
				$price += $fmprice;
			}

			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}
	if ($bqflag) {
		$price = $price * $quantity;
	}

	$price = print_products_production_speed_price($product_id, $price, $production_speed);

	$discount_price = print_products_user_discount_get_discount_price($price);

	$price = print_products_user_discount_get_discounted_price($price);

	$price = print_products_order_min_price($product_id, $price);

	$additional = array('total_pages' => $total_pages, 'page_quantity' => $page_quantity, 'weight' => $weight, 'sku' => $sku, 'pspeed' => $production_speed, 'pspeed_label' => $production_speed_label, 'discount_price' => $discount_price, 'addon_products' => $addon_products);

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'book',
		'quantity' => $quantity,
		'price' => $price,
		'product_attributes' => serialize($product_attributes),
		'additional' => serialize($additional),
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_area($cart_item_key, $update = false, $onlyreturn = false) {
	global $wpdb, $attribute_types;

	print_products_price_matrix_attr_names_init();

	$sku = '';
	$price = 0;
	$weight = 0;
	$production_speed = '';
	$production_speed_label = '';
	$dimension_unit = print_products_get_dimension_unit();
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = (int)$_REQUEST['quantity'];
	$width = (float)$_REQUEST['width'];
	$height = (float)$_REQUEST['height'];
	$smparams = $_REQUEST['smparams'];
	$fmparams = $_REQUEST['fmparams'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	if (!strlen($atcaction)) { $atcaction = 'artwork'; }
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }
	if (isset($_REQUEST['production_speed'])) {
		$production_speed = (int)$_REQUEST['production_speed'];
		$production_speed_label = print_products_get_pspeed_data($product_id, $production_speed);
	}

	$addon_products = array();
	if (isset($_REQUEST['addon_products'])) { $addon_products = $_REQUEST['addon_products']; }

	$area_unit = (int)get_post_meta($product_id, '_area_unit', true);

	$product_attributes = array();
	if ($smparams) {
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$aterms = $smarray[1];
			$number = $smarray[2];
			$number_type = (int)$smarray[3];

			$w = $width;
			$h = $height;
			if ($number_type == 2 || $number_type == 3) { // area type
				$area_size = print_products_get_area_size($width, $height, $dimension_unit, $area_unit);
				$w = $area_size[0];
				$h = $area_size[1];
			}

			$nmb_val = $quantity;
			if ($number_type == 2) {
				$nmb_val = $quantity * $w * $h;
			} else if ($number_type == 3) {
				$nmb_val = $quantity * (($w * 2) + ($h * 2));
			} else if ($number_type == 4) {
				$nmb_val = $quantity * ($w * 2);
			}

			$paterms = print_products_get_matrix_price_aterms($aterms, $attribute_types);

			$nums = print_products_get_matrix_numbers($nmb_val, $mtype_id);
			$smprice = print_products_get_matrix_price($mtype_id, $paterms, $nmb_val, $nums);
			if ($smprice) {
				$price += $smprice;
			}

			$pattributes = array();
			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				$pattributes[] = $attr_term;
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
			$pweight = print_products_get_total_product_weight($product_id, 'area', $quantity, $nmb_val, $pattributes);
			$weight = $weight + $pweight;
			if (!strlen($sku)) {
				$sku = print_products_get_product_sku($mtype_id, $aterms);
			}
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$mtype_id = $fmarray[0];
			$aterms = $fmarray[1];
			$number = $fmarray[2];
			$number_type = $fmarray[3];

			$w = $width;
			$h = $height;
			if ($number_type == 2 || $number_type == 3) { // area type
				$area_size = print_products_get_area_size($width, $height, $dimension_unit, $area_unit);
				$w = $area_size[0];
				$h = $area_size[1];
			}

			$nmb_val = $quantity;
			if ($number_type == 2) {
				$nmb_val = $quantity * $w * $h;
			} else if ($number_type == 3) {
				$nmb_val = $quantity * (($w * 2) + ($h * 2));
			} else if ($number_type == 4) {
				$nmb_val = $quantity * ($w * 2);
			}

			$paterms = print_products_get_matrix_price_aterms($aterms, $attribute_types);

			$nums = print_products_get_matrix_numbers($nmb_val, $mtype_id);
			$fmprice = print_products_get_matrix_price($mtype_id, $paterms, $nmb_val, $nums);
			if ($fmprice) {
				$price += $fmprice;
			}

			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}

	$price = print_products_production_speed_price($product_id, $price, $production_speed);

	$discount_price = print_products_user_discount_get_discount_price($price);

	$price = print_products_user_discount_get_discounted_price($price);

	$price = print_products_order_min_price($product_id, $price);

	$additional = array('width' => $_REQUEST['width'], 'height' => $_REQUEST['height'], 'weight' => $weight, 'sku' => $sku, 'pspeed' => $production_speed, 'pspeed_label' => $production_speed_label, 'discount_price' => $discount_price, 'addon_products' => $addon_products);

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'area',
		'quantity' => $quantity,
		'price' => $price,
		'product_attributes' => serialize($product_attributes),
		'additional' => serialize($additional),
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_aec($cart_item_key, $update = false, $onlyreturn = false) {
	global $wpdb, $attribute_types;

	print_products_price_matrix_attr_names_init();

	$sku = '';
	$weight = 0;
	$production_speed = '';
	$production_speed_label = '';
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$smparams = $_REQUEST['smparams'];
	$fmparams = $_REQUEST['fmparams'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	$price = $_REQUEST['aec_total_price'];
	$discount_price = (float)$_REQUEST['udprice'];
	$project_name = $_REQUEST['aec_project_name'];
	$total_area = $_REQUEST['aec_total_area'];
	$total_pages = $_REQUEST['aec_total_pages'];
	$table_values = $_REQUEST['aec_table_values'];
	if (!strlen($atcaction)) { $atcaction = 'artwork'; }
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }
	if (isset($_REQUEST['production_speed'])) {
		$production_speed = (int)$_REQUEST['production_speed'];
		$production_speed_label = print_products_get_pspeed_data($product_id, $production_speed);
	}

	$product_attributes = array();
	if ($smparams) {
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$aterms = $smarray[1];
			$number = $smarray[2];

			$nmb_val = $quantity;

			$pattributes = array();
			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
				$pattributes[] = $attr_term;
			}

			$pweight = print_products_get_total_product_weight($product_id, 'aec', $quantity, $nmb_val, $pattributes);
			$weight = $weight + $pweight;
			if (!strlen($sku)) {
				$sku = print_products_get_product_sku($mtype_id, $aterms);
			}
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$mtype_id = $fmarray[0];
			$aterms = $fmarray[1];
			$number = $fmarray[2];

			$nmb_val = $quantity;

			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}

	$additional = array('weight' => $weight, 'project_name' => $project_name, 'total_area' => round($total_area, 2), 'total_pages' => $total_pages, 'sku' => $sku, 'table_values' => $table_values, 'pspeed' => $production_speed, 'pspeed_label' => $production_speed_label, 'discount_price' => $discount_price);

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'aec',
		'quantity' => $quantity,
		'price' => $price,
		'product_attributes' => serialize($product_attributes),
		'additional' => serialize($additional),
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_aecbwc($cart_item_key, $update = false, $onlyreturn = false) {
	global $wpdb, $attribute_types;

	print_products_price_matrix_attr_names_init();

	$sku = '';
	$weight = 0;
	$production_speed = '';
	$production_speed_label = '';
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$smparams = $_REQUEST['smparams'];
	$fmparams = $_REQUEST['fmparams'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	$price = $_REQUEST['aec_total_price'];
	$discount_price = (float)$_REQUEST['udprice'];
	$project_name = $_REQUEST['aec_project_name'];
	$total_area = $_REQUEST['aec_total_area'];
	$total_pages = $_REQUEST['aec_total_pages'];
	$aec_area_bw = $_REQUEST['aec_area_bw'];
	$aec_pages_bw = $_REQUEST['aec_pages_bw'];
	$aec_area_cl = $_REQUEST['aec_area_cl'];
	$aec_pages_cl = $_REQUEST['aec_pages_cl'];
	$table_values = $_REQUEST['aec_table_values'];
	if (!strlen($atcaction)) { $atcaction = 'artwork'; }
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }
	if (isset($_REQUEST['production_speed'])) {
		$production_speed = (int)$_REQUEST['production_speed'];
		$production_speed_label = print_products_get_pspeed_data($product_id, $production_speed);
	}

	$product_attributes = array();
	if ($smparams) {
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$aterms = $smarray[1];
			$number = $smarray[2];

			$nmb_val = $quantity;

			$pattributes = array();
			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
				$pattributes[] = $attr_term;
			}

			$pweight = print_products_get_total_product_weight($product_id, 'aecbwc', $quantity, $nmb_val, $pattributes);
			$weight = $weight + $pweight;
			if (!strlen($sku)) {
				$sku = print_products_get_product_sku($mtype_id, $aterms);
			}
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$mtype_id = $fmarray[0];
			$aterms = $fmarray[1];
			$number = $fmarray[2];

			$nmb_val = $quantity;

			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}

	$additional = array('weight' => $weight, 'project_name' => $project_name, 'total_area' => round($total_area, 2), 'total_pages' => $total_pages, 'sku' => $sku, 'area_bw' => $aec_area_bw, 'pages_bw' => $aec_pages_bw, 'area_cl' => $aec_area_cl, 'pages_cl' => $aec_pages_cl, 'table_values' => $table_values, 'pspeed' => $production_speed, 'pspeed_label' => $production_speed_label, 'discount_price' => $discount_price);

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'aecbwc',
		'quantity' => $quantity,
		'price' => $price,
		'product_attributes' => serialize($product_attributes),
		'additional' => serialize($additional),
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_aecsimple($cart_item_key, $update = false, $onlyreturn = false) {
	global $wpdb, $attribute_types;

	print_products_price_matrix_attr_names_init();

	$sku = '';
	$weight = 0;
	$production_speed = '';
	$production_speed_label = '';
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$smparams = $_REQUEST['smparams'];
	$fmparams = $_REQUEST['fmparams'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	$price = $_REQUEST['aec_total_price'];
	$discount_price = (float)$_REQUEST['udprice'];
	$project_name = $_REQUEST['aec_project_name'];
	$total_area = $_REQUEST['aec_total_area'];
	$total_pages = $_REQUEST['aec_total_pages'];
	$table_values = $_REQUEST['aec_table_values'];
	if (!strlen($atcaction)) { $atcaction = 'artwork'; }
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }
	if (isset($_REQUEST['production_speed'])) {
		$production_speed = (int)$_REQUEST['production_speed'];
		$production_speed_label = print_products_get_pspeed_data($product_id, $production_speed);
	}

	$product_attributes = array();
	if ($smparams) {
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$aterms = $smarray[1];
			$number = $smarray[2];

			$nmb_val = $quantity;

			$pattributes = array();
			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
				$pattributes[] = $attr_term;
			}

			$pweight = print_products_get_total_product_weight($product_id, 'aecsimple', $quantity, $nmb_val, $pattributes);
			$weight = $weight + $pweight;
			if (!strlen($sku)) {
				$sku = print_products_get_product_sku($mtype_id, $aterms);
			}
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$mtype_id = $fmarray[0];
			$aterms = $fmarray[1];
			$number = $fmarray[2];

			$nmb_val = $quantity;

			$atarray = explode('-', $aterms);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}

	$additional = array('weight' => $weight, 'project_name' => $project_name, 'total_area' => round($total_area, 2), 'total_pages' => $total_pages, 'sku' => $sku, 'table_values' => $table_values, 'pspeed' => $production_speed, 'pspeed_label' => $production_speed_label, 'discount_price' => $discount_price);

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'aecsimple',
		'quantity' => $quantity,
		'price' => $price,
		'product_attributes' => serialize($product_attributes),
		'additional' => serialize($additional),
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_custom($cart_item_key, $update = false, $onlyreturn = false) {
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$price = $_REQUEST['price'];

	$additional = array();
	if (isset($_REQUEST['attributes'])) {
		$additional = array(
			'attributes' => $_REQUEST['attributes'],
			'shipping_specify' => $_REQUEST['shipping_specify'],
			'weight' => $_REQUEST['weight'],
			'sboxes' => $_REQUEST['sboxes'],
			'shipping_cost' => $_REQUEST['shipping_cost'],
			'cptype' => $_REQUEST['cptype']
		);
	}

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'custom',
		'quantity' => $quantity,
		'price' => $price,
		'additional' => serialize($additional),
		'atcaction' => 'artwork'
	);

	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}


function print_products_checkout_simple($cart_item_key, $update = false, $onlyreturn = false) {
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];

	if (!$product_id) { $product_id = $_REQUEST['product_id']; }

	$cart_data = WC()->cart->get_cart();
	$cart_item = $cart_data[$cart_item_key];
	$_product = wc_get_product($product_id);
	$price = $_product->get_price();

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'simple',
		'quantity' => $quantity,
		'price' => $price,
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_checkout_variable($cart_item_key, $update = false, $onlyreturn = false) {
	$product_id = $_REQUEST['add-to-cart'];
	$quantity = $_REQUEST['quantity'];
	$atcaction = $_REQUEST['atcaction'];
	$artworkfiles = $_REQUEST['artworkfiles'];
	$variation_id = $_REQUEST['variation_id'];
	$price = get_post_meta($variation_id, '_price', true);
	if (!$product_id) { $product_id = $_REQUEST['product_id']; }

	$cartdata = array(
		'cart_item_key' => $cart_item_key,
		'product_id' => $product_id,
		'product_type' => 'variable',
		'quantity' => $quantity,
		'price' => $price,
		'atcaction' => $atcaction
	);
	if (strlen($artworkfiles)) {
		$cartdata['artwork_files'] = serialize(explode(';', $artworkfiles));
	}
	if ($onlyreturn) {
		return $cartdata;
	}
	if ($update) {
		print_products_update_cart_data($cartdata);
	} else {
		print_products_add_cart_data($cartdata);
	}
}

function print_products_production_speed_price($product_id, $price, $production_speed) {
	if (strlen($production_speed)) {
		$production_speed_options = get_post_meta($product_id, '_production_speed_options', true);
		if ($production_speed_options && is_array($production_speed_options)) {
			$ps_percent = $production_speed_options[$production_speed]['percent'];
			if ($ps_percent && $price) {
				$pprice = ($price / 100) * $ps_percent;
				$price = $price + $pprice;
			}
		}
	}
	return $price;
}

function print_products_order_min_price($product_id, $price) {
	$order_min_price = (float)get_post_meta($product_id, '_order_min_price', true);
	if ($order_min_price > 0 && $price < $order_min_price) {
		$price = $order_min_price;
	}
	return $price;
}

function print_products_match_order_item_attributes($order_item_data) {
	global $wpdb;
	$matched = true;
	$numbers = array();
	$numbers_style = '';
	$p_attributes = array();
	$p_attributes_terms = array();

	$product_id = $order_item_data->product_id;
	$oi_quantity = $order_item_data->quantity;
	$oi_product_attributes = unserialize($order_item_data->product_attributes);

	$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
	if ($product_type_matrix_types) {
		foreach($product_type_matrix_types as $product_type_matrix_type) {
			if (!count($numbers)) {
				$numbers = explode(',', $product_type_matrix_type->numbers);
			}
			if ($numbers_style == '') {
				$numbers_style = $product_type_matrix_type->num_style;
			}
			$mattributes = unserialize($product_type_matrix_type->attributes);
			$materms = unserialize($product_type_matrix_type->aterms);
			$p_attributes = array_merge($p_attributes, $mattributes); 
			if ($materms) {
				foreach($materms as $aid => $materm) {
					if (is_array($p_attributes_terms[$aid])) {
						$p_attributes_terms[$aid] = array_merge($p_attributes_terms[$aid], $materm);
					} else {
						$p_attributes_terms[$aid] = $materm;
					}
				}
			}
		}
	}

	if ($oi_product_attributes) {
		foreach($oi_product_attributes as $oi_product_attribute) {
			$oia_data = explode(':', $oi_product_attribute);
			$oi_aid = $oia_data[0];
			$oi_tid = $oia_data[1];
			if (substr($oi_aid, 0, 2) != 'pq') {
				if (is_array($p_attributes) && !in_array($oi_aid, $p_attributes)) {
					$matched = false;
				}
				if (is_array($p_attributes_terms[$oi_aid]) && !in_array($oi_tid, $p_attributes_terms[$oi_aid])) {
					$matched = false;
				}
			}
		}
	}
	if ($numbers_style == 1 && !in_array($oi_quantity, $numbers)) {
		$matched = false;
	}
	return $matched;
}

function print_products_get_order_item_smparams($order_item_data) {
	global $wpdb, $print_products_settings;
	$smparams = '';
	$product_id = $order_item_data->product_id;
	$oi_product_type = $order_item_data->product_type;
	$oi_quantity = $order_item_data->quantity;
	$oi_product_attributes = unserialize($order_item_data->product_attributes);
	$oi_additional = unserialize($order_item_data->additional);
	$dimension_unit = print_products_get_dimension_unit();
	$sakey = $print_products_settings['size_attribute'];

	$anmb = 0;
	$saval = 0;
	$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
	if ($product_type_matrix_types) {
		foreach($product_type_matrix_types as $product_type_matrix_type) {
			$mtype_id = $product_type_matrix_type->mtype_id;
			$num_type = $product_type_matrix_type->num_type;
			$mattributes = unserialize($product_type_matrix_type->attributes);

			if (strlen($smparams)) { $smparams .= ';'; }

			if ($oi_product_type == 'book' && strpos($order_item_data->product_attributes, 'pq|') !== false) {
				$pqnmb = 0;
				$aarray = array();
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if ($oi_aid == $sakey) { $saval = $oi_tid; }
					if ($anmb == 0) {
						if (substr($oi_aid, 0, 2) == 'pq' && $pqnmb > 0) { break; }
						if (in_array($oi_aid, $mattributes)) {
							$aarray[] = $oi_product_attribute;
						}
					} else {
						if ($pqnmb > 1) {
							if (!count($aarray)) {
								$aarray[] = $sakey.':'.$saval;
							}
							if (in_array($oi_aid, $mattributes)) {
								$aarray[] = $oi_product_attribute;
							}
						}
					}
					if (substr($oi_aid, 0, 2) == 'pq') {
						$pq = $oi_tid * $oi_quantity;
						$pqnmb++;
					}
				}
				$smparams .= $mtype_id.'|'.implode('-', $aarray).'|'.$pq.'|'.$num_type;
			} if ($oi_product_type == 'area') {
				$width = $oi_additional['width'];
				$height = $oi_additional['height'];
				$q = $oi_quantity;
				if ($num_type == 2) {
					$q = $oi_quantity * $width * $height;
				} else if ($num_type == 3) {
					$q = $oi_quantity * (($width * 2) + ($height * 2));
				} else if ($num_type == 4) {
					$q = $oi_quantity * ($width * 2);
				}
				$aarray = array();
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if (in_array($oi_aid, $mattributes)) {
						$aarray[] = $oi_product_attribute;
					}
				}
				$smparams .= $mtype_id.'|'.implode('-', $aarray).'|'.$q.'|'.$num_type;
			} else {
				$aarray = array();
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if (in_array($oi_aid, $mattributes)) {
						$aarray[] = $oi_product_attribute;
					}
				}
				$smparams .= $mtype_id.'|'.implode('-', $aarray).'|'.$oi_quantity;
			}
			$anmb++;
		}
	}
	return $smparams;
}

function print_products_get_order_item_fmparams($order_item_data) {
	global $wpdb, $print_products_settings;
	$fmparams = '';
	$product_id = $order_item_data->product_id;
	$oi_product_type = $order_item_data->product_type;
	$oi_quantity = $order_item_data->quantity;
	$oi_product_attributes = unserialize($order_item_data->product_attributes);
	$oi_additional = unserialize($order_item_data->additional);
	$dimension_unit = print_products_get_dimension_unit();
	$sakey = $print_products_settings['size_attribute'];

	$anmb = 0;
	$saval = 0;

	$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 1 ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
	if ($product_type_matrix_types) {
		foreach($product_type_matrix_types as $product_type_matrix_type) {
			$mtype_id = $product_type_matrix_type->mtype_id;
			$num_type = $product_type_matrix_type->num_type;
			$mattributes = unserialize($product_type_matrix_type->attributes);

			if (strlen($fmparams)) { $fmparams .= ';'; }

			if ($oi_product_type == 'book') {
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if ($oi_aid == $sakey) { $saval = $oi_tid; }

					if (in_array($oi_aid, $mattributes)) {
						$aarray = array();
						$aarray[] = $sakey.':'.$saval;
						$aarray[] = $oi_product_attribute;
						if (strlen($fmparams)) { $fmparams .= ';'; }
						$q = $oi_quantity;
						if ($num_type == 1) { $q = $oi_additional['total_pages']; }
						$fmparams .= $mtype_id.'|'.implode('-', $aarray).'|'.$q.'|'.$num_type;
					}
				}
			} if ($oi_product_type == 'area') {
				$width = $oi_additional['width'];
				$height = $oi_additional['height'];
				$q = $oi_quantity;
				if ($num_type == 2) {
					$q = $oi_quantity * $width * $height;
				} else if ($num_type == 3) {
					$q = $oi_quantity * (($width * 2) + ($height * 2));
				} else if ($num_type == 4) {
					$q = $oi_quantity * ($width * 2);
				}
				$aarray = array();
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if (in_array($oi_aid, $mattributes)) {
						if (strlen($fmparams)) { $fmparams .= ';'; }
						$fmparams .= $mtype_id.'|'.$oi_product_attribute.'|'.$q.'|'.$num_type;
					}
				}
			} if ($oi_product_type == 'aec' || $oi_product_type == 'aecbwc' || $oi_product_type == 'aecsimple') {
				$aarray = array();
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if ($oi_aid == $sakey) { $saval = $oi_tid; }
					if (in_array($oi_aid, $mattributes)) {
						if (strlen($fmparams)) { $fmparams .= ';'; }
						$fmparams .= $mtype_id.'|'.$oi_product_attribute.'|'.$oi_quantity;
					}
				}
			} else {
				$aarray = array();
				foreach($oi_product_attributes as $oi_product_attribute) {
					$oia_data = explode(':', $oi_product_attribute);
					$oi_aid = $oia_data[0];
					$oi_tid = $oia_data[1];
					if ($oi_aid == $sakey) {
						$aarray[] = $oi_product_attribute;
					}

					if (in_array($oi_aid, $mattributes)) {
						$aarray[] = $oi_product_attribute;
					}
				}
				$fmparams .= $mtype_id.'|'.implode('-', $aarray).'|'.$oi_quantity;
			}
		}
	}
	return $fmparams;
}

function print_products_reorder_product($cart_item_key) {
	global $wpdb;
	$item_id = $_REQUEST['reorder_item_id'];
	$order_id = $_REQUEST['reorder_order_id'];
	$_SESSION['reorder_note'] = __('Re-order of original order', 'wp2print').' '.$order_id;
	$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
	if ($order_item_data) {
		$product_id = $order_item_data->product_id;
		$additional = unserialize($order_item_data->additional);
		$product_type = $order_item_data->product_type;
		$artwork_files = unserialize($order_item_data->artwork_files);

		$edit_session_key = wc_get_order_item_meta($item_id, '_edit_session_key', true);
		if (strlen($edit_session_key) && print_products_designer_installed()) {
			$update = array();
			$update['printImage'] = wc_get_order_item_meta($item_id, '_image_link', true);
			$update['pdfUrl'] = wc_get_order_item_meta($item_id, '_pdf_link', true);
			$wpdb->update(CART_DATA_TABLE, $update, array('cart_item_key' => $cart_item_key));
			$_REQUEST['atcaction'] = 'design';
		}

		$artwork_source = get_post_meta($product_id, '_artwork_source', true);

		if ($artwork_files && is_array($artwork_files)) {
			$_REQUEST['artworkfiles'] = implode(';', $artwork_files);
		}
		switch ($product_type) {
			case 'fixed':
				print_products_checkout_fixed($cart_item_key);
			break;
			case 'book':
				$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY mtype, sorder", $wpdb->prefix, $product_id));

				$oi_product_attributes = unserialize($order_item_data->product_attributes);
				if (strpos($order_item_data->product_attributes, 'pq|') !== false) {
					$pqarray = array();
					foreach($oi_product_attributes as $oi_product_attribute) {
						$oia_data = explode(':', $oi_product_attribute);
						$oi_aid = $oia_data[0];
						$oi_tid = $oia_data[1];
						if (substr($oi_aid, 0, 2) == 'pq') {
							$pqarray[] = $oi_tid;
						}
					}
					if ($product_type_matrix_types) {
						foreach($product_type_matrix_types as $pmkey => $product_type_matrix_type) {
							$_REQUEST['page_quantity_'.$product_type_matrix_type->mtype_id] = $pqarray[$pmkey];
						}
					}
				}
				print_products_checkout_book($cart_item_key);
			break;
			case 'area':
				$_REQUEST['width'] = $additional['width'];
				$_REQUEST['height'] = $additional['height'];
				print_products_checkout_area($cart_item_key);
			break;
			case 'aec':
				$_REQUEST['aec_total_price'] = $order_item_data->price;
				$_REQUEST['aec_project_name'] = $additional['project_name'];
				$_REQUEST['aec_total_area'] = $additional['total_area'];
				$_REQUEST['aec_total_pages'] = $additional['total_pages'];
				$_REQUEST['aec_table_values'] = $additional['table_values'];
				print_products_checkout_aec($cart_item_key);
			break;
			case 'aecbwc':
				$_REQUEST['aec_total_price'] = $order_item_data->price;
				$_REQUEST['aec_project_name'] = $additional['project_name'];
				$_REQUEST['aec_total_area'] = $additional['total_area'];
				$_REQUEST['aec_total_pages'] = $additional['total_pages'];
				$_REQUEST['aec_area_bw'] = $additional['area_bw'];
				$_REQUEST['aec_pages_bw'] = $additional['pages_bw'];
				$_REQUEST['aec_area_cl'] = $additional['area_cl'];
				$_REQUEST['aec_pages_cl'] = $additional['pages_cl'];
				$_REQUEST['aec_table_values'] = $additional['table_values'];
				print_products_checkout_aecbwc($cart_item_key);
			break;
			case 'aecsimple':
				$_REQUEST['aec_total_price'] = $order_item_data->price;
				$_REQUEST['aec_project_name'] = $additional['project_name'];
				$_REQUEST['aec_total_area'] = $additional['total_area'];
				$_REQUEST['aec_total_pages'] = $additional['total_pages'];
				$_REQUEST['aec_table_values'] = $additional['table_values'];
				print_products_checkout_aecsimple($cart_item_key);
			break;
			case "simple":
				if (strlen($artwork_source)) {
					print_products_checkout_simple($cart_item_key);
				}
			break;
			case "variable":
				if (strlen($artwork_source)) {
					print_products_checkout_variable($cart_item_key);
				}
			break;
		}
	}
}

add_action('woocommerce_new_order_item', 'print_products_add_order_item_meta', 11, 2);
function print_products_add_order_item_meta($item_id, $item) {
	global $wpdb;
	$item_type = $item->get_type();
	if ($item_type == 'line_item') {
		$product_id = $item->get_product_id();
		$variation_id = $item->get_variation_id();
		$cart_item_key = $item->legacy_cart_item_key;
		$discount_price = 0;

		$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
		if ($prod_cart_data) {
			$insert = array();
			$insert['item_id'] = $item_id;
			$insert['product_id'] = $prod_cart_data->product_id;
			$insert['product_type'] = $prod_cart_data->product_type;
			$insert['quantity'] = $prod_cart_data->quantity;
			$insert['price'] = $prod_cart_data->price;
			$insert['product_attributes'] = $prod_cart_data->product_attributes;
			$insert['additional'] = $prod_cart_data->additional;
			$insert['artwork_files'] = $prod_cart_data->artwork_files;
			$insert['artwork_thumbs'] = $prod_cart_data->artwork_thumbs;
			$insert['atcaction'] = $prod_cart_data->atcaction;
			$wpdb->insert($wpdb->prefix."print_products_order_items", $insert);

			$additional = unserialize($prod_cart_data->additional);
			wc_add_order_item_meta($item_id, '_sku', $additional['sku']);
			if (isset($additional['discount_price']) && $additional['discount_price']) {
				$discount_price = $additional['discount_price'];
			}
		} else {
			if ($variation_id) {
				$product = wc_get_product($variation_id);
			} else {
				$product = wc_get_product($product_id);
			}
			$product_price = (float)$product->get_price();
			$product_regular_price = (float)$product->get_regular_price();
			if ($product_regular_price > $product_price) {
				$discount_price = $product_regular_price - $product_price;
			}
		}

		$artwork_source = get_post_meta($product_id, '_artwork_source', true);
		$artwork_file_url = get_post_meta($product_id, '_artwork_file_url', true);
		$artwork_file_url_order = get_post_meta($product_id, '_artwork_file_url_order', true);
		$artwork_file_url_email = get_post_meta($product_id, '_artwork_file_url_email', true);
		if (!strlen($artwork_source) && strlen($artwork_file_url)) {
			wc_add_order_item_meta($item_id, '_artwork_file_url', $artwork_file_url);
			wc_add_order_item_meta($item_id, '_artwork_file_url_order', $artwork_file_url_order);
			wc_add_order_item_meta($item_id, '_artwork_file_url_email', $artwork_file_url_email);
		}
		if ($discount_price) {
			wc_add_order_item_meta($item_id, '_discount_price', $discount_price);
		}
		print_products_oistatus_add($item_id);
	}
}

add_action('woocommerce_checkout_update_order_meta', 'print_products_checkout_update_order_meta', 10, 2);
function print_products_checkout_update_order_meta($order_id, $posted) {
	if (strlen($posted['order_comments'])) {
		update_post_meta($order_id, '_order_notes', $posted['order_comments']);
	}
	if (isset($_SESSION['reorder_note'])) {
		$order = wc_get_order($order_id);
		$order->add_order_note($_SESSION['reorder_note']);
		unset($_SESSION['reorder_note']);
	}
}

add_action('woocommerce_new_order', 'print_products_woo_new_order');
function print_products_woo_new_order($order_id) {
	global $wpdb;
	$cart_contents = WC()->cart->cart_contents;
	if ($cart_contents) {
		$upd = false;
		foreach ($cart_contents as $cart_item_key => $values) {
			$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
			if ($prod_cart_data) {
				$price = $prod_cart_data->price / $prod_cart_data->quantity;
				$cart_contents[$cart_item_key]['line_total'] = $price;
				$cart_contents[$cart_item_key]['line_subtotal'] = $price;
				$upd = true;
			}
		}
		if ($upd) {
			WC()->cart->cart_contents = $cart_contents;
		}
	}
}

add_action('woocommerce_after_cart_item_quantity_update', 'print_products_woo_cart_item_quantity_update', 10, 2);
function print_products_woo_cart_item_quantity_update($cart_item_key, $quantity) {
	global $wpdb;
	$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
	if ($prod_cart_data) {
		$wpdb->update($wpdb->prefix.'print_products_cart_data', array('quantity' => $quantity), array('cart_item_key' => $cart_item_key));
	}
}

add_filter('woocommerce_cart_subtotal', 'print_products_cart_subtotal', 10, 3);
function print_products_cart_subtotal($cart_subtotal, $compound, $cart) {
	global $wpdb;
	$exist_matrix_prods = false;
	$new_cart_subtotal = 0;
	if (count($cart->cart_contents)) {
		foreach($cart->cart_contents as $cart_item_key => $values) {
			$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
			if ($prod_cart_data) {
				if (print_products_is_wp2print_type($prod_cart_data->product_type)) {
					$new_cart_subtotal = $new_cart_subtotal + $prod_cart_data->price;
				} else {
					$new_cart_subtotal = $new_cart_subtotal + ($prod_cart_data->price * $prod_cart_data->quantity);
				}
				$exist_matrix_prods = true;
			} else {
				$new_cart_subtotal = $new_cart_subtotal + $values['line_subtotal'];
			}
		}
	}
	if ($exist_matrix_prods) {
		$cart_subtotal = wc_price($new_cart_subtotal);
	}
	return $cart_subtotal;
}

add_action('woocommerce_before_calculate_totals', 'print_products_woo_before_calculate_totals');
function print_products_woo_before_calculate_totals($cart) {
	global $print_products_cart_data;
	$cart_contents = $cart->get_cart();
	foreach ($cart_contents as $cart_item_key => $values) {
		$product_id = $values['product_id'];
		$product = $values['data'];
		$product_type = $product->get_type();
		if (print_products_is_wp2print_type($product_type) || print_products_is_custom_product($product_id)) {
			$product_price = $print_products_cart_data[$cart_item_key]->price / $values['quantity'];
			$product_weight = print_products_cart_get_product_weight($cart_item_key, true);
			if ($product_weight) {
				$product->set_weight($product_weight);
				$cart_contents[$cart_item_key]['data']->weight = $product_weight;
			}
			$product->set_price($product_price);
			$cart_contents[$cart_item_key]['data'] = $product;
			$cart_contents[$cart_item_key]['data']->price = $product_price;
			if ($_REQUEST['print_products_checkout_process_action'] == 'update-cart' && $print_products_cart_data[$cart_item_key]) {
				$cart_contents[$cart_item_key]['quantity'] = $print_products_cart_data[$cart_item_key]->quantity;
			}
		}
	}
	WC()->cart->cart_contents = $cart_contents;
}

add_filter('woocommerce_cart_contents_weight', 'print_products_cart_contents_weight');
function print_products_cart_contents_weight($weight) {
	global $wpdb;
	$cart_contents_weight = 0;
	$cart = WC()->cart->get_cart();
	foreach ($cart as $cart_item_key => $values) {
		$product_id = $values['product_id'];
		$product = $values['data'];
		$product_type = $product->get_type();
		if (print_products_is_wp2print_type($product_type) || print_products_is_custom_product($product_id)) {
			$product_weight = print_products_cart_get_product_weight($cart_item_key);
		} else {
			$product_weight = $product->get_weight() * $values['quantity'];
		}
		$cart_contents_weight += $product_weight;
	}
	return $cart_contents_weight;
}

$wp2print_custom_items_only = false;
$wp2print_custom_shipping_cost = 0;
add_filter('woocommerce_cart_shipping_packages', 'print_products_cart_shipping_packages', 10);
function print_products_cart_shipping_packages($packages) {
	global $wpdb, $wp2print_custom_items_only, $wp2print_custom_shipping_cost;
	$remove_from_packages = array();
	if ($packages) {
		foreach($packages as $pkey => $package) {
			foreach($package['contents'] as $cart_item_key => $item) {
				$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
				if ($prod_cart_data) {
					$product_id = $prod_cart_data->product_id;
					$product_type = $prod_cart_data->product_type;
					$additional = unserialize($prod_cart_data->additional);
					$weight = (float)$additional['weight'];
					if (print_products_is_wp2print_type($product_type) || print_products_is_custom_product($product_id)) {
						$packages[$pkey]['contents'][$cart_item_key]['quantity'] = 1;
						if ($weight) {
							$packages[$pkey]['contents'][$cart_item_key]['data']->set_weight($weight);
						}
					}
					if (print_products_is_custom_product($product_id)) {
						if (isset($additional['shipping_specify']) && $additional['shipping_specify'] == 'cost') {
							$remove_from_packages[] = $cart_item_key;
							if (isset($additional['shipping_cost']) && $additional['shipping_cost']) {
								$wp2print_custom_shipping_cost += (float)$additional['shipping_cost'];
							}
						}
					}
				}
			}
		}
	}
	if ($remove_from_packages && count($remove_from_packages)) {
		if (count($remove_from_packages) == count($package['contents'])) {
			$wp2print_custom_items_only = true;
		}
		foreach($packages as $pkey => $package) {
			foreach($remove_from_packages as $cart_item_key) {
				unset($packages[$pkey]['contents'][$cart_item_key]);
			}
		}
	}
	return $packages;
}

add_filter('woocommerce_package_rates', 'print_products_cart_package_rates', 11, 2);
function print_products_cart_package_rates($rates, $package) {
	global $wp2print_custom_items_only, $wp2print_custom_shipping_cost;
	if ($wp2print_custom_shipping_cost) {
		foreach ($rates as $rate_id => $rate) {
			if ($wp2print_custom_items_only) {
				$rate->cost = $wp2print_custom_shipping_cost;
			} else {
				$rate->cost += $wp2print_custom_shipping_cost;
			}
		}
	}
	return $rates;
}

function print_products_cart_get_product_weight($cart_item_key) {
	global $print_products_cart_data;
	$product_weight = 0;
	if ($print_products_cart_data[$cart_item_key]) {
		$prod_cart_data = $print_products_cart_data[$cart_item_key];
		$additional = unserialize($prod_cart_data->additional);
		$weight = (float)$additional['weight'];
		if ($weight) {
			$product_weight = $weight;
		}
	}
	return $product_weight;
}

add_filter('wf_ups_rate_request', 'pp_wf_ups_rate_request', 11, 2);
function pp_wf_ups_rate_request($request, $package) {
	$ups_boxes = array();
	$other_total_weight = 0;
	$need_separation = false;
	$print_products_shipping_options = get_option("print_products_shipping_options");
	if (isset($print_products_shipping_options['multiple']) && $print_products_shipping_options['multiple'] == 1) {
		foreach ($package['contents'] as $cart_item_key => $item) {
			$product = $item['data'];
			$product_id = $product->get_id();
			$product_type = $product->get_type();
			$product_weight = $product->get_weight();
			if (print_products_is_custom_product($product_id)) { $product_type = 'custom'; }

			if (print_products_is_wp2print_type($product_type) || $product_type == 'custom') {
				if ($product_type == 'custom') {
					$custom_product_data = print_products_get_custom_product_data($cart_item_key);
					if ($custom_product_data) {
						$weight = $custom_product_data['weight'];
						$sboxes = $custom_product_data['sboxes'];
						if ($weight && $sboxes) {
							$need_separation = true;
							$weight_per_box = ceil($weight / $sboxes);
							for ($b=1; $b<=$sboxes; $b++) {
								if ($weight > $weight_per_box) {
									$ups_boxes[] = $weight_per_box;
								} else {
									$ups_boxes[] = $weight;
								}
								$weight = $weight - $weight_per_box;
							}
						}
					}
					if (!$need_separation) {
						$other_total_weight = $other_total_weight + $product_weight;
					}
				} else {
					$product_weight = print_products_cart_get_product_weight($cart_item_key);
					if ($product_weight) {
						$product_max_weight_per_box = (float)get_post_meta($product_id, '_product_max_weight_per_box', true);
						if ($product_max_weight_per_box && $product_weight > $product_max_weight_per_box) {
							$need_separation = true;
							$boxes = ceil($product_weight / $product_max_weight_per_box);
							$full_boxes = floor($product_weight / $product_max_weight_per_box);
							for ($b=1; $b<=$boxes; $b++) {
								if ($b <= $full_boxes) {
									$ups_boxes[] = $product_max_weight_per_box;
								} else {
									$ups_boxes[] = $product_weight;
								}
								$product_weight = $product_weight - $product_max_weight_per_box;
							}
						} else {
							$other_total_weight = $other_total_weight + $product_weight;
						}
					}
				}
			} else {
				$other_total_weight = $other_total_weight + ($product_weight * $item['quantity']);
			}
		}
		if ($need_separation) {
			if ($other_total_weight) {
				$ups_boxes[] = $other_total_weight;
			}
			$ups_packages = array();
			foreach($ups_boxes as $ups_box_weight) {
				$ups_packages[] = '<Package><PackagingType><Code>02</Code><Description>Package/customer supplied</Description></PackagingType><Description>Rate</Description><PackageWeight><UnitOfMeasurement><Code>LBS</Code></UnitOfMeasurement><Weight>'.wc_get_weight($ups_box_weight, 'LBS').'</Weight></PackageWeight></Package>';
			}
			$request = print_products_ups_replace_packages($request, $ups_packages);
		}
	}
	return $request;
}

function print_products_ups_replace_packages($request, $ups_packages) {
	$request_before = substr($request, 0, strpos($request, '<Package>'));
	$request_after = substr($request, strrpos($request, '</Package>') + 10);
	$request = $request_before . trim(implode('', $ups_packages)) . $request_after;
	return $request;
}

function print_products_order_get_item_weight($order_item_id) {
	global $wpdb;
	$weight = 0;
	$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s", $wpdb->prefix, (int)$order_item_id));
	if ($order_item_data) {
		$additional = unserialize($order_item_data->additional);
		$weight = (float)$additional['weight'];
	}
	return $weight;
}

function print_products_get_total_product_weight($product_id, $product_type, $quantity, $number, $product_attributes, $pmkey = 0) {
	global $wpdb, $print_products_settings;
	$product_weight = 0;
	$size_attribute = $print_products_settings['size_attribute'];
	$material_attribute = $print_products_settings['material_attribute'];
	$page_count_attribute = $print_products_settings['page_count_attribute'];

	$product_shipping_weights = get_post_meta($product_id, '_product_shipping_weights', true);
	$product_shipping_quantity = get_post_meta($product_id, '_product_shipping_base_quantity', true);
	if ($product_shipping_weights) {
		$product_shipping_weights = unserialize($product_shipping_weights);

		$psize = '';
		$pmaterial = '';
		$ppagecount = '';
		if ($product_attributes) {
			foreach ($product_attributes as $product_attribute) {
				$paarray = explode(':', $product_attribute);
				if ($paarray[0] == $material_attribute && !$pmaterial) {
					$pmaterial = $paarray[1];
				}
				if ($paarray[0] == $page_count_attribute && !$ppagecount) {
					$ppagecount = $paarray[1];
				}
				if ($paarray[0] == $size_attribute && !$psize) {
					$psize = $paarray[1];
				}
			}
		}
		if ($pmaterial) {
			if ($product_type == 'area') {
				$pweight = $product_shipping_weights[$pmaterial];
				if ($pweight) {
					$product_weight = print_products_get_product_weight($product_type, $number, $pweight, $product_shipping_quantity);
				}
			} else if ($product_type == 'book') {
				$product_shipping_quantity = unserialize($product_shipping_quantity);
				if ($psize) {
					$pweight = $product_shipping_weights[$pmkey][$pmaterial][$psize];
					if ($pweight) {
						$pp_product_weight = print_products_get_product_weight($product_type, $number, $pweight, $product_shipping_quantity[$pmkey]);
						$product_weight = $product_weight + $pp_product_weight;
					}
				}
			} else {
				if ($psize) {
					if ($ppagecount) {
						$pweight = $product_shipping_weights[$pmaterial][$psize][$ppagecount];
					} else {
						$pweight = $product_shipping_weights[$pmaterial][$psize];
					}
					if ($pweight) {
						$product_weight = print_products_get_product_weight($product_type, $number, $pweight, $product_shipping_quantity);
					}
				}
			}
		}
	}
	return $product_weight;
}

function print_products_get_product_weight($product_type, $number, $pweight, $pbqty) {
	if ($product_type == 'area') {
		$product_weight = $pweight * $number;
	} else {
		if ($pbqty) {
			$product_weight = ($pweight / $pbqty) * $number;
		} else {
			$product_weight = $pweight * $number;
		}
	}
	return $product_weight;
}

add_action('print_products_cart_product_thumbnail', 'print_products_cart_product_thumbnail_output', 10, 5);
function print_products_cart_product_thumbnail_output($prod_cart_data, $_product, $cart_item, $cart_item_key, $designer_thumb) {
	$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
	$artwork_files = false;
	if ($prod_cart_data) {
		$artwork_files = unserialize($prod_cart_data->artwork_files);
	}
	if ($artwork_files && count($artwork_files)) {
		print_products_artwork_files_html($artwork_files, $prod_cart_data);
	}
	if (!$designer_thumb && !$artwork_files) {
		$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
		if (!$_product->is_visible()) {
			echo $thumbnail;
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $_product->get_permalink( $cart_item ) ), $thumbnail );
		}
	}
	$cart_upload_button = (int)get_post_meta($product_id, '_cart_upload_button', true);
	$cart_upload_file_types = get_post_meta($product_id, '_cart_upload_file_types', true);
	if (is_array($cart_upload_file_types)) { $cart_upload_file_types = implode(',', $cart_upload_file_types); }
	if ($cart_upload_button) {
		$cart_upload_button_text = get_post_meta($product_id, '_cart_upload_button_text', true);
		if (!strlen($cart_upload_button_text)) { $cart_upload_button_text = __('Upload your database', 'wp2print'); }
		echo '<div class="cart-upload-button-box"><input type="button" value="'.$cart_upload_button_text.'" class="button" onclick="wp2print_cart_upload_button(\''.$cart_item_key.'\', \''.$cart_upload_file_types.'\');"></div>';
	}
}

add_action('wp_footer', 'print_products_woocommerce_after_cart');
function print_products_woocommerce_after_cart() {
	if (is_cart()) {
		include(PRINT_PRODUCTS_TEMPLATES_DIR . 'cart-upload.php');
	}
}

function print_products_cart_upload_save() {
	global $wpdb;
	$cart_item_key = $_POST['cart_item_key'];
	$new_artwork_files = $_POST['artwork_files'];
	$redirect_to = $_POST['redirect_to'];
	if (strlen($cart_item_key) && strlen($new_artwork_files)) {
		$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
		if ($prod_cart_data) {
			if (strlen($prod_cart_data->artwork_files)) {
				$artwork_files = unserialize($prod_cart_data->artwork_files);
				$artwork_thumbs = unserialize($prod_cart_data->artwork_thumbs);

				$new_artwork_files = explode(';', $new_artwork_files);
				$new_artwork_thumbs = print_products_get_artwork_thumbs($new_artwork_files);
				foreach($new_artwork_files as $afkey => $afile) {
					$artwork_files[] = $afile;
					$artwork_thumbs[] = $new_artwork_thumbs[$afkey];
				}
			} else {
				$artwork_files = explode(';', $new_artwork_files);
				$artwork_thumbs = print_products_get_artwork_thumbs($artwork_files);
			}
			$update = array();
			$update['artwork_files'] = serialize($artwork_files);
			$update['artwork_thumbs'] = serialize($artwork_thumbs);
			$wpdb->update($wpdb->prefix."print_products_cart_data", $update, array('cart_item_key' => $cart_item_key));
			print_products_get_cart_data();
		}
	}
	wp_redirect($redirect_to);
	exit;
}

add_filter('woocommerce_in_cart_product_title', 'print_products_cart_product_title', 11, 3);
add_filter('woocommerce_cart_item_name', 'print_products_cart_product_title', 11, 3);
function print_products_cart_product_title($title, $cart_item, $cart_item_key) {
	global $wpdb;
	$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
	if ($prod_cart_data) {
		if (strlen($prod_cart_data->additional)) {
			$additional = unserialize($prod_cart_data->additional);
			if (isset($additional['cptype']) && strlen($additional['cptype'])) {
				$title = $additional['cptype'];
			}
		}
	}
	return $title;
}

add_filter('woocommerce_order_item_name', 'print_products_order_item_name', 11, 2);
add_filter('woocommerce_order_item_get_name', 'print_products_order_item_name', 11, 2);
function print_products_order_item_name($name, $item) {
	global $wpdb;
	$item_id = $item->get_id();
	$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
	if ($order_item_data) {
		if (strlen($order_item_data->additional)) {
			$additional = unserialize($order_item_data->additional);
			if (isset($additional['cptype']) && strlen($additional['cptype'])) {
				$name = $additional['cptype'];
			}
		}
	}
	return $name;
}

add_action('woocommerce_order_item_meta_end', 'print_products_order_item_meta_end', 10, 2);
function print_products_order_item_meta_end($item_id, $item) {
	$artwork_file_url = wc_get_order_item_meta($item_id, '_artwork_file_url', true);
	$artwork_file_url_email = (int)wc_get_order_item_meta($item_id, '_artwork_file_url_email', true);
	if (strlen($artwork_file_url) && $artwork_file_url_email) {
		echo '<a href="'.$artwork_file_url.'" class="afu-link" target="_blank">'.__('Download file', 'wp2print').'</a>';
	}
}

add_filter('woocommerce_cart_item_price', 'print_products_cart_item_price', 11, 3);
function print_products_cart_item_price($price, $cart_item, $cart_item_key) {
	global $wpdb;
	$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
	if ($prod_cart_data) {
		$price = wc_price($prod_cart_data->price);
	}
	return $price;
}
?>