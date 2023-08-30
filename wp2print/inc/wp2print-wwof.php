<?php
add_action('pre_get_posts', 'print_products_wwof_pre_get_posts', 11);
function print_products_wwof_pre_get_posts($query) {
	$query_vars = $query->query_vars;
	if (isset($query_vars['post_type']) && $query_vars['post_type'] == 'product' && is_ajax() && isset($_POST['action']) && $_POST['action'] == 'wwof_display_product_listing') {
		$exclude_products = array();
		$wp2print_types = print_products_get_product_types();

		$post_in = $query->get('post__in');
		$wc_products = print_products_wwof_get_products($post_in);
		if ($wc_products) {
			foreach ($wc_products as $wc_product) {
				$product_id = $wc_product->ID;
				$product = wc_get_product($product_id);
				$product_type = $product->get_type();
				if (isset($wp2print_types[$product_type])) {
					if ($product_type == 'fixed') {
						if (!print_products_wwof_check_product_mtype($product_id)) {
							$exclude_products[] = $product_id;
						}
					} else {
						$exclude_products[] = $product_id;
					}
				}
			}
		}
		if (count($exclude_products)) {
			if (is_array($post_in) && count($post_in)) {
				$include = array();
				foreach($post_in as $pid) {
					if (!in_array($pid, $exclude_products)) {
						$include[] = $pid;
					}
				}
				if (count($include)) {
					$query->set('post__in', $include);
				} else {
					$query->set('post__not_in', $exclude_products);
				}
			} else {
				$post__not_in = $query->get('post__not_in');
				if (is_array($post__not_in) && count($post__not_in)) {
					$query->set('post__not_in', array_merge($post__not_in, $exclude_products));
				} else {
					$query->set('post__not_in', $exclude_products);
				}
			}
		}
	}
}

add_filter('wwof_filter_product_item_price', 'print_products_wwof_product_item_price', 11, 2);
function print_products_wwof_product_item_price($price_html, $product) {
	if ($product->get_type() == 'fixed') {
		$product_id = $product->get_id();
		$product_mtype = print_products_wwof_get_product_mtype($product_id);
		if ($product_mtype) {
			$style = '';
			$mprices = print_products_wwof_get_product_mprices($product_mtype->mtype_id);
			$price_html = '<span class="wp2print-wwof-prices-'.$product_id.'">';
			foreach($mprices as $number => $price) {
				$price_html .= '<span class="price price-qty-'.$number.'"'.$style.'>' . wc_price($price) . '</span>';
				$style = ' style="display:none;"';
			}
			$price_html .= '</span>';
		}
	}
	return $price_html;
}

add_filter('wwof_filter_product_item_quantity', 'print_products_wwof_product_item_quantity', 11, 2);
function print_products_wwof_product_item_quantity($quantity_field , $product) {
	if ($product->get_type() == 'fixed') {
		$product_id = $product->get_id();
		$product_mtype = print_products_wwof_get_product_mtype($product_id);
		if ($product_mtype) {
			$numbers = explode(',', $product_mtype->numbers);
			$quantity_field = '<select name="quantity" class="wp2print-wwof-qty qty" onchange="wp2print_wwof('.$product_id.', this);">';
			foreach($numbers as $number) {
				$quantity_field .= '<option value="'.$number.'">'.$number.'</option>';
			}
			$quantity_field .= '</select>';
		}
	}
	return $quantity_field;
}

function print_products_wwof_get_products($post__in = false) {
	global $wpdb;
	$where = array("post_type = 'product'", "post_status = 'publish'");
	if ($post__in) {
		$where[] = "ID IN (".implode(',', $post__in).")";
	}
	return $wpdb->get_results(sprintf("SELECT * FROM %sposts WHERE %s ORDER BY ID DESC", $wpdb->prefix, implode(' AND ', $where)));
}

function print_products_wwof_check_product_mtype($product_id) {
	global $wpdb;
	$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s", $wpdb->prefix, $product_id));
	if (count($product_type_matrix_types) == 1) {
		if ($product_type_matrix_types[0]->mtype == 0 && $product_type_matrix_types[0]->num_style == 1) {
			$success = true;
			$attributes = unserialize($product_type_matrix_types[0]->attributes);
			$aterms = unserialize($product_type_matrix_types[0]->aterms);
			if (count($attributes)) {
				foreach($attributes as $aid) {
					$a_terms = $aterms[$aid];
					if (count($a_terms) > 1) {
						$success = false;
					}
				}
			}
			return $success;
		}
	}
	return false;
}

function print_products_wwof_get_product_mtype($product_id) {
	global $wpdb;
	return $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 LIMIT 0, 1", $wpdb->prefix, $product_id));
}

function print_products_wwof_get_product_mprices($mtype_id) {
	global $wpdb;
	$mprices = array();
	$matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s ORDER BY number ASC", $wpdb->prefix, $mtype_id));
	if ($matrix_prices) {
		foreach($matrix_prices as $matrix_price) {
			$mprices[$matrix_price->number] = $matrix_price->price;
		}
	}
	return $mprices;
}

add_action('woocommerce_add_to_cart', 'print_products_wwof_add_to_cart', 10, 2);
function print_products_wwof_add_to_cart($cart_item_key, $product_id) {
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'wwof_add_product_to_cart' && $_REQUEST['product_type'] == 'fixed') {
		$product_mtype = print_products_wwof_get_product_mtype($product_id);
		if ($product_mtype) {
			$quantity = $_REQUEST['quantity'];
			$smparams = $product_mtype->mtype_id.'|';
			$attributes = unserialize($product_mtype->attributes);
			$aterms = unserialize($product_mtype->aterms);
			if (count($attributes)) {
				$atstring = '';
				foreach($attributes as $aid) {
					if (strlen($atstring)) { $atstring .= '-'; }
					$atstring .= $aid.':'.$aterms[$aid][0];
				}
				$smparams .= $atstring;
			}
			$smparams .= '|'.$quantity;

			$_REQUEST['smparams'] = $smparams; // 54|2:107-3:19|20
			$_REQUEST['atcaction'] = 'artwork';
			print_products_checkout_fixed($cart_item_key);
		}
	}
}
?>