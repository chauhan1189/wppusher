<?php
add_action('wp_loaded', 'print_products_oirsdate_actions');
function print_products_oirsdate_actions() {
	if (isset($_POST['OirsdateAjaxAction'])) {
		switch ($_POST['OirsdateAjaxAction']) {
			case 'change-oirsdate':
				print_products_oirsdate_change_oirsdate();
			break;
		}
		exit;
	}
}

function print_products_oirsdate_change_oirsdate() {
	$order_id = $_POST['order_id'];
	$item_id = $_POST['item_id'];
	$item_sdate = $_POST['item_sdate'];
	$poption = (int)$_POST['poption'];
	if ($poption == 1) {
		$order = wc_get_order($order_id);
		$order_items = $order->get_items();
		foreach($order_items as $order_item) {
			$order_item_id = $order_item->get_id();
			wc_update_order_item_meta($order_item_id, '_item_rsdate', $item_sdate);
		}
	} else {
		wc_update_order_item_meta($item_id, '_item_rsdate', $item_sdate);
	}
}

function print_products_oirsdate_show_prodview_shipdate_column() {
	global $print_products_prodview_options;
	return (int)$print_products_prodview_options['display_shipdate'];
}

function print_products_oirsdate_show_orders_shipdate_column() {
	global $print_products_prodview_options;
	return (int)$print_products_prodview_options['orders_display_shipdate'];
}

function print_products_oirsdate_popup_html() {
	global $post;
	?>
	<div style="display:none;">
		<div id="oirsdate-popup" class="oirsdate-popup">
			<h2><?php _e('Modify required ship date for order #', 'wp2print'); ?><span><?php echo $post->ID; ?></span></h2>
			<ul>
				<li><input type="radio" name="oirsdate_option" value="0" checked><?php _e('Apply to this item only', 'wp2print'); ?></li>
				<li><input type="radio" name="oirsdate_option" value="1"><?php _e('Apply to all items in order ', 'wp2print'); ?></li>
			</ul>
			<input type="button" value="<?php _e('Submit', 'wp2print'); ?>" class="button-primary" onclick="wp2print_oirsdate_submit()">
		</div>
	</div>
	<?php
}

// order edit page
add_action('woocommerce_admin_order_item_headers', 'print_products_oirsdate_woocommerce_admin_order_item_headers', 12);
function print_products_oirsdate_woocommerce_admin_order_item_headers($order) {
	if (print_products_oirsdate_show_orders_shipdate_column()) { ?>
		<th class="item_rsdate"><?php esc_html_e('Required ship date', 'wp2print'); ?></th>
		<?php
	}
}

add_action('woocommerce_admin_order_item_values', 'print_products_oirsdate_woocommerce_admin_order_item_values', 12, 3);
function print_products_oirsdate_woocommerce_admin_order_item_values($product, $item, $item_id) {
    global $post;
	if (print_products_oirsdate_show_orders_shipdate_column()) {
		$order_id = $post->ID;
		$item_type = $item->get_type();
		if ($item_type == 'line_item') {
			$item_rsdate = wc_get_order_item_meta($item_id, '_item_rsdate', true); ?>
			<td class="item_rsdate oirsd-order-<?php echo $order_id; ?>"><input type="text" name="oirsdate[<?php echo $item_id; ?>]" value="<?php echo $item_rsdate; ?>" class="item-rsdate" data-order-id="<?php echo $order_id; ?>" data-item-id="<?php echo $item_id; ?>">
			<div class="oirsdate-success oirsdate-success-<?php echo $item_id; ?>"><?php _e('Updated.', 'wp2print'); ?></div></td>
			<?php
		}
	}
}
?>