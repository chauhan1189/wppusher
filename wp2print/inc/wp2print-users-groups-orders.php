<?php
function print_products_groups_orders_admin_page() {
	global $wpdb;
	$users_groups = print_products_users_groups_get_groups();
	$wc_ostatuses = wc_get_order_statuses();
	$oistatuses = print_products_oistatus_get_list();
	$approval_statuses = print_products_orders_proof_get_approval_statuses();

	$group_id = 0;
	$group_name = 0;
	if (isset($_GET['group_id'])) { $group_id = (int)$_GET['group_id']; }

	$group_orders = false;
	if ($group_id) {
		$group_users = print_products_users_groups_get_group_users($group_id);
		if (!count($group_users)) { $group_users = array(0); }
		$group_orders = $wpdb->get_results(sprintf("SELECT o.*, u.display_name FROM %sposts o LEFT JOIN %spostmeta pm ON pm.post_id = o.ID LEFT JOIN %susers u ON u.ID = pm.meta_value WHERE o.post_type = 'shop_order' AND pm.meta_key = '_customer_user' AND u.ID IN (%s) ORDER BY o.ID DESC", $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, implode(",", $group_users)));
	}
	?>
	<div class="wrap wp2print-uaf-wrap">
		<h2><?php _e('Group order history', 'wp2print'); ?></h2>
		<div class="tablenav top">
			<form>
				<?php if (isset($_GET['post_type'])) { ?><input type="hidden" name="post_type" value="<?php echo $_GET['post_type']; ?>"><?php } ?>
				<input type="hidden" name="page" value="print-products-groups-orders">
				<div class="alignleft actions bulkactions">
					<select name="group_id">
						<option value="">-- <?php _e('Select Group', 'wp2print'); ?> --</option>
						<?php foreach($users_groups as $gid => $gname) { ?>
							<option value="<?php echo $gid; ?>"<?php if ($gid == $group_id) { echo ' SELECTED'; $group_name = $gname; } ?>><?php echo $gname; ?></option>
						<?php } ?>
					</select>
					<input type="submit" class="button" value="<?php _e('Filter', 'wp2print'); ?>">
				</div>
			</form>
		</div>
		<?php if ($group_id) { ?>
			<table class="wp-list-table widefat" width="100%">
				<thead>
					<tr>
						<th><?php _e('Group name', 'wp2print'); ?></th>
						<th><?php _e('Customer name', 'wp2print'); ?></th>
						<th><?php _e('Date', 'wp2print'); ?></th>
						<th><?php _e('OrderID', 'wp2print'); ?></th>
						<th><?php _e('Amount', 'wp2print'); ?></th>
						<th><?php _e('Order Status', 'wp2print'); ?></th>
						<th><?php _e('Order Items', 'wp2print'); ?></th>
						<th><?php _e('Proof Status', 'wp2print'); ?></th>
						<th><?php _e('Production Status', 'wp2print'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($group_orders) { ?>
						<?php foreach ($group_orders as $user_order) {
							$order_id = $user_order->ID;
							$order = wc_get_order($order_id);
							$order_date = $order->get_date_created();
							$order_total = $order->get_total();
							$order_items = $order->get_items();
							$user_name = $user_order->display_name;

							$oitems = array();
							$prod_statuses = array();
							$proof_statuses = array();
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
							}
							?>
							<tr>
								<td><?php echo $group_name; ?></td>
								<td><?php echo $user_name; ?></td>
								<td><?php echo wc_format_datetime($order_date, 'd-M-y'); ?></td>
								<td><a href="post.php?post=<?php echo $order_id; ?>&action=edit"><?php echo $order_id; ?></a></td>
								<td><?php echo wc_price($order_total); ?></td>
								<td><?php echo $wc_ostatuses[$user_order->post_status]; ?></td>
								<td><table cellspacing="0" cellpadding="0"><?php echo implode('', $oitems); ?></table></td>
								<td class="column-approval"><table cellspacing="0" cellpadding="0" width="100%"><?php echo implode('', $proof_statuses); ?></table></td>
								<td><table cellspacing="0" cellpadding="0"><?php echo implode('', $prod_statuses); ?></table></td>
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
	<?php
}
?>