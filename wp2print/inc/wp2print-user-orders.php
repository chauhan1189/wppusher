<?php
function print_products_user_orders_admin_page() {
	global $wpdb;
	$wp_users = get_users(array('orderby' => 'display_name'));
	$wc_ostatuses = wc_get_order_statuses();
	$oistatuses = print_products_oistatus_get_list();
	$approval_statuses = print_products_orders_proof_get_approval_statuses();

	$user_id = 0;
	$user_name = '';
	if (isset($_GET['user_id'])) { $user_id = (int)$_GET['user_id']; }

	$user_orders = false;
	if ($user_id) {
		$user_orders = $wpdb->get_results(sprintf("SELECT o.* FROM %sposts o LEFT JOIN %spostmeta pm ON pm.post_id = o.ID WHERE o.post_type = 'shop_order' AND pm.meta_key = '_customer_user' AND pm.meta_value = '%s' ORDER BY o.ID DESC", $wpdb->prefix, $wpdb->prefix, $user_id));
	}
	?>
	<div class="wrap wp2print-uaf-wrap wp2print-uo-wrap">
		<h2><?php _e('Users Orders', 'wp2print'); ?></h2>
		<div class="tablenav top">
			<form>
				<?php if (isset($_GET['post_type'])) { ?><input type="hidden" name="post_type" value="<?php echo $_GET['post_type']; ?>"><?php } ?>
				<input type="hidden" name="page" value="print-products-users-orders">
				<div class="alignleft actions bulkactions">
					<select name="user_id">
						<option value="">-- <?php _e('Select user', 'wp2print'); ?> --</option>
						<?php foreach($wp_users as $wp_user) { ?>
							<option value="<?php echo $wp_user->ID; ?>"<?php if ($wp_user->ID == $user_id) { echo ' SELECTED'; $user_name = $wp_user->display_name; } ?>><?php echo $wp_user->display_name; ?></option>
						<?php } ?>
					</select>
					<input type="submit" class="button" value="<?php _e('Filter', 'wp2print'); ?>">
				</div>
			</form>
		</div>
		<?php if ($user_id) { ?>
			<table class="wp-list-table widefat" width="100%">
				<thead>
					<tr>
						<th><?php _e('Customer name', 'wp2print'); ?></th>
						<th><?php _e('Date', 'wp2print'); ?></th>
						<th><?php _e('OrderID', 'wp2print'); ?></th>
						<th><?php _e('Amount', 'wp2print'); ?></th>
						<th><?php _e('Order Status', 'wp2print'); ?></th>
						<th><?php _e('Order Items', 'wp2print'); ?></th>
						<th><?php _e('Proof Status', 'wp2print'); ?></th>
						<th><?php _e('Production Status', 'wp2print'); ?></th>
						<th><?php _e('Reorder', 'wp2print'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($user_orders) { ?>
						<?php foreach ($user_orders as $user_order) {
							$order_id = $user_order->ID;
							$order = wc_get_order($order_id);
							$order_date = $order->get_date_created();
							$order_total = $order->get_total();
							$order_items = $order->get_items();

							$oitems = array();
							$prod_statuses = array();
							$proof_statuses = array();
							$reorder_links = array();
							foreach($order_items as $item_id => $order_item) {
								$approval_status = wc_get_order_item_meta($item_id, '_approval_status', true);
								$item_status = wc_get_order_item_meta($item_id, '_item_status', true);

								$approval_html = '&nbsp;';
								if (strlen($approval_status)) {
									$approval_html = '<mark class="'.$approval_status.'" title="'.$approval_statuses[$approval_status].'"></mark>';
								}

								$oitems[] = '<tr><td>'.$order_item->get_name().'</td></tr>';
								$proof_statuses[] = '<tr><td>'.$approval_html.'</td></tr>';
								$prod_statuses[] = '<tr><td>'.$oistatuses[$item_status].'</td></tr>';
								$reorder_links[] = '<tr><td><a href="#reorder" class="button" onclick="return user_orders_reorder('.$order_id.', '.$item_id.');">'.__('Reorder', 'wp2print').'</a></td></tr>';
							}
							?>
							<tr>
								<td><?php echo $user_name; ?></td>
								<td><?php echo wc_format_datetime($order_date, 'd-M-y'); ?></td>
								<td><a href="post.php?post=<?php echo $order_id; ?>&action=edit"><?php echo $order_id; ?></a></td>
								<td><?php echo wc_price($order_total); ?></td>
								<td><?php echo $wc_ostatuses[$user_order->post_status]; ?></td>
								<td><table cellspacing="0" cellpadding="0"><?php echo implode('', $oitems); ?></table></td>
								<td class="column-approval"><table cellspacing="0" cellpadding="0" width="100%"><?php echo implode('', $proof_statuses); ?></table></td>
								<td><table cellspacing="0" cellpadding="0"><?php echo implode('', $prod_statuses); ?></table></td>
								<td><table cellspacing="0" cellpadding="0"><?php echo implode('', $reorder_links); ?></table></td>
							</tr>
						<?php } ?>
					<?php } else { ?>
						<tr>
							<td colspan="9"><?php _e('Nothing found.', 'wp2print'); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	</div>
	<div style="display:none;">
		<form method="POST" class="user-orders-form">
		<input type="hidden" name="user_orders_action" value="reorder">
		<input type="hidden" name="order_id" class="uoa-order-id">
		<input type="hidden" name="item_id" class="uoa-item-id">
		</form>
	</div>
	<?php
}
?>