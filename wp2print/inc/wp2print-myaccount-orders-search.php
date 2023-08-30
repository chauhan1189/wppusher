<?php
add_action('woocommerce_before_account_orders', 'print_products_myaccount_orders_search_form', 11);
function print_products_myaccount_orders_search_form($has_orders) {
	?>
	<div class="woocommerce-myaccount-orders-search">
		<form action="<?php echo wc_get_endpoint_url('orders'); ?>" class="orders-search-form">
			<?php if (isset($_REQUEST['allgroup']) && $_REQUEST['allgroup'] == 'true') { ?><input type="hidden" name="allgroup" value="true"><?php } ?>
			<input type="text" name="orders_sterm" value="<?php if (isset($_REQUEST['orders_sterm'])) { echo $_REQUEST['orders_sterm']; } ?>" class="orders-sterm" style="width:50%">
			<input type="submit" value="<?php _e('Search', 'wp2print'); ?>" class="button alt orders-search-submit">
		</form>
	</div>
	<?php
}

add_filter('woocommerce_order_query_args', 'print_products_myaccount_orders_search_query_args', 11);
function print_products_myaccount_orders_search_query_args($query_vars) {
	if (isset($_REQUEST['orders_sterm'])) {
		$query_vars['orders_sterm'] = trim($_REQUEST['orders_sterm']);
	}
	return $query_vars;
}

add_action('pre_get_posts', 'print_products_myaccount_orders_search_orders_request', 11);
function print_products_myaccount_orders_search_orders_request($query) {
	if (isset($query->query['orders_sterm'])) {
		$query->set('s', $query->query['orders_sterm']);
	}
}

add_filter('posts_where', 'print_products_myaccount_orders_search_posts_where');
function print_products_myaccount_orders_search_posts_where($where) {
	global $wpdb;
	if (isset($_REQUEST['orders_sterm']) && strpos($where, '_customer_user') && strpos($where, 'shop_order')) {
		$ptable = $wpdb->prefix . 'posts';
		$orders_sterm = str_replace("'", "''", trim($_REQUEST['orders_sterm']));
		$orders_ids = print_products_myaccount_orders_search_get_orders($orders_sterm);
		$replacing = $ptable.".ID = '".$orders_sterm."'";
		if ($orders_ids) {
			$replacing .= " OR ".$ptable.".ID IN (".implode(',', $orders_ids).")";
		}
		$where = str_replace($ptable.".post_title LIKE", $replacing." OR ".$ptable.".post_title LIKE", $where);
	}
	return $where;
}

function print_products_myaccount_orders_search_get_orders($orders_sterm) {
	global $wpdb;
	$orders_ids = false;
	$where = array();
	$where2 = array();
	$sterms = explode(' ', $orders_sterm);
	foreach($sterms as $sterm) {
		$where[] = sprintf("meta_value LIKE '%s'", '%'.str_replace("'", "''", $sterm).'%');
		$where2[] = sprintf("order_item_name LIKE '%s'", '%'.str_replace("'", "''", $sterm).'%');
	}
	$oids = $wpdb->get_results(sprintf("SELECT DISTINCT post_id FROM %spostmeta WHERE (meta_key = '_billing_address_index' OR meta_key = '_shipping_address_index') AND %s", $wpdb->prefix, implode(' AND ', $where)));
	if ($oids) {
		$orders_ids = array();
		foreach($oids as $oid) {
			$orders_ids[] = $oid->post_id;
		}
	}
	$oids = $wpdb->get_results(sprintf("SELECT DISTINCT order_id FROM %swoocommerce_order_items WHERE order_item_type = 'line_item' AND %s", $wpdb->prefix, implode(' AND ', $where2)));
	if ($oids) {
		if (!is_array($orders_ids)) { $orders_ids = array(); }
		foreach($oids as $oid) {
			if (!in_array($oid->order_id, $orders_ids)) {
				$orders_ids[] = $oid->order_id;
			}
		}
	}
	
	return $orders_ids;
}

add_filter('woocommerce_get_endpoint_url', 'print_products_myaccount_orders_search_endpoint_url', 11);
function print_products_myaccount_orders_search_endpoint_url($url) {
	$opos = strpos($url, '/orders/');
	if ($opos && isset($_REQUEST['orders_sterm'])) {
		$opart = substr($url, $opos + 1);
		$oarray = explode('/', $opart);
		$npart = (int)$oarray[1];
		if ($npart) {
			if (strpos($url, '?')) {
				$url = $url . '&orders_sterm=' . $_REQUEST['orders_sterm'];
			} else {
				$url = $url . '?orders_sterm=' . $_REQUEST['orders_sterm'];
			}
		}
	}
	return $url;
}
?>