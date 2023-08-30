<?php
add_filter('woocommerce_account_menu_items', 'print_products_orders_missing_files_account_menu_items', 20);
function print_products_orders_missing_files_account_menu_items($items) {
	$new_items = array();
	foreach($items as $ikey => $ival) {
		$new_items[$ikey] = $ival;
		if (isset($items['orders-awaiting-approval'])) {
			if ($ikey == 'orders-awaiting-approval') {
				$new_items['orders-missing-files'] = __('Orders missing files', 'wp2print');
			}
		} else {
			if ($ikey == 'orders') {
				$new_items['orders-missing-files'] = __('Orders missing files', 'wp2print');
			}
		}
	}
	return $new_items;
}

add_action('init', 'print_products_orders_missing_files_rewrite_endpoint');
function print_products_orders_missing_files_rewrite_endpoint() {
	if (print_products_my_account_is_front()) {
		add_rewrite_endpoint('orders-missing-files', EP_ROOT | EP_PAGES);
	} else {
		add_rewrite_endpoint('orders-missing-files', EP_PAGES);
	}
	flush_rewrite_rules();
}

add_filter('query_vars', 'print_products_orders_missing_files_query_vars', 10);
function print_products_orders_missing_files_query_vars($vars) {
	$vars[] = 'orders-missing-files';
	return $vars;
}

add_action('parse_request', 'print_products_orders_missing_files_parse_request', 10);
function print_products_orders_missing_files_parse_request() {
	global $wp;
	$var = 'orders-missing-files';
	if (isset($wp->query_vars['name']) && $wp->query_vars['name'] == $var) {
		unset($wp->query_vars['name']);
		$wp->query_vars[$var] = $var;
	}
}

add_action('pre_get_posts', 'print_products_orders_missing_files_pre_get_posts');
function print_products_orders_missing_files_pre_get_posts($q) {
	if ( ! $q->is_main_query() ) {
		return;
	}
	if (print_products_is_showing_page_on_front($q) && ! print_products_page_on_front_is($q->get('page_id'))) {
		$_query = wp_parse_args($q->query);
		$qv_array = array('orders-missing-files' => 'orders-missing-files');
		if (!empty($_query) && array_intersect( array_keys($_query), array_keys($qv_array))) {
			$q->is_page     = true;
			$q->is_home     = false;
			$q->is_singular = true;
			$q->set('page_id', (int)get_option( 'page_on_front'));
			add_filter('redirect_canonical', '__return_false');
		}
	}
}

add_action('woocommerce_account_orders-missing-files_endpoint', 'print_products_orders_missing_files_account_page');
function print_products_orders_missing_files_account_page() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'orders-missing-files.php';
}

add_action('wp_loaded', 'print_products_orders_missing_files_actions');
function print_products_orders_missing_files_actions() {
	global $wpdb, $current_user;
	if (isset($_POST['orders_missing_files_submit']) && $_POST['orders_missing_files_submit'] == 'true') {
		$order_id = $_POST['order_id'];
		$artworkfiles = $_POST['artworkfiles'];
		$redirectto = $_POST['redirectto'];
		if ($order_id && $artworkfiles) {
			foreach($artworkfiles as $item_id => $artwork_files) {
				if (strlen($artwork_files)) {
					$update = array();
					$update['artwork_files'] = serialize(explode(';', $artwork_files));
					$wpdb->update($wpdb->prefix.'print_products_order_items', $update, array('item_id' => $item_id));
				}
			}
		}
		$_SESSION['orders_missing_files_message'] = __('Files were successfully saved.', 'wp2print');
		wp_redirect($redirectto);
		exit;
	}
}

add_filter('the_title', 'print_products_orders_missing_files_the_title', 12, 2);
function print_products_orders_missing_files_the_title($title, $id) {
	global $wp_query;
	if (is_account_page() && is_main_query() && in_the_loop() && isset($wp_query->query_vars['orders-missing-files']) && !is_admin()) {
		$title = __('Orders missing files', 'wp2print');
	}
	return $title;
}
?>