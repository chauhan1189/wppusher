<?php
global $wp, $wpdb, $current_user;

$view_order_id = $_GET['view'];

if ($view_order_id) {
	$order_data = $wpdb->get_row(sprintf("SELECT p.* FROM %sposts p LEFT JOIN %spostmeta pm ON pm.post_id = p.ID WHERE p.post_type = 'shop_order' AND p.post_status != 'trash' AND p.ID = %s AND pm.meta_key = '_customer_user' AND pm.meta_value = '%s' ORDER BY p.ID DESC", $wpdb->prefix, $wpdb->prefix, $view_order_id, $current_user->ID)); ?>
	<div class="wrap orders-awaiting-approval-details">
		<h2><?php _e('Order Details', 'wp2print'); ?></h2>
		<?php if ($order_data) {
			$the_order = wc_get_order($view_order_id); ?>
			<form method="POST" class="orders-missing-files-form">
			<table width="100%">
				<tr>
					<td><?php _e('Order #', 'wp2print'); ?>: <?php echo $view_order_id; ?></td>
				</tr>
				<tr>
					<td><strong><?php _e('Items missing files', 'wp2print'); ?>:</strong></td>
				</tr>
				<tr>
					<td colspan="3">
						<table cellspacing="0" cellpadding="0" width="60%" class="items-table" style="margin:0px;">
							<tr style="background:#F4F4F4;">
								<td style="width:35%;"><strong><?php _e('Item', 'wp2print'); ?></strong></td>
								<td><strong><?php _e('Files', 'wp2print'); ?></strong></td>
								<td style="width:80px;"><strong><?php _e('Actions', 'wp2print'); ?></strong></td>
							</tr>
							<?php foreach ($the_order->get_items() as $item_id => $item) {
								$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s' AND atcaction = 'artwork'", $wpdb->prefix, $item_id));
								if ($order_item_data) {
									$artwork_files = $order_item_data->artwork_files;
									if (!strlen($artwork_files)) { ?>
										<tr style="border-bottom:1px solid #F4F4F4;">
											<td><?php echo $item['name']; ?>
												<?php print_products_product_attributes_list_html($order_item_data); ?>
											</td>
											<td class="files-list-<?php echo $item_id; ?>">&nbsp;</td>
											<td nowrap><a href="#upload-files" class="woocommerce-button button view omf-upload-btn" rel="<?php echo $item_id; ?>"><?php _e('Upload files', 'wp2print'); ?></a><input type="hidden" name="artworkfiles[<?php echo $item_id; ?>]" class="artwork-files-<?php echo $item_id; ?>"></td>
										</tr>
										<tr><td colspan="3"><hr style="margin:0px;"></td></tr>
									<?php } ?>
								<?php } ?>
							<?php } ?>
						</table>
					</td>
				</tr>
				<tr>
					<td><input type="submit" value="<?php _e('Save', 'wp2print'); ?>" class="woocommerce-button button" style="float:right;padding:10px 40px;"></td>
				</tr>
			</table>
			<input type="hidden" name="orders_missing_files_submit" value="true">
			<input type="hidden" name="order_id" value="<?php echo $view_order_id; ?>">
			<input type="hidden" name="redirectto" value="<?php echo get_permalink() . 'orders-missing-files/'; ?>">
			</form>
			<?php include('orders-missing-files-upload.php'); ?>
		<?php } else { ?>
			<p><?php _e("You aren't allowed to view this order.", 'wp2print'); ?></p>
		<?php } ?>
	</div>
	<?php
} else {
	$missing_files_orders = false;
	$missing_items = array();
	$user_orders = $wpdb->get_results(sprintf("SELECT p.* FROM %sposts p LEFT JOIN %spostmeta pm ON pm.post_id = p.ID WHERE p.post_type = 'shop_order' AND p.post_status != 'trash' AND pm.meta_key = '_customer_user' AND pm.meta_value = '%s' ORDER BY p.ID DESC", $wpdb->prefix, $wpdb->prefix, $current_user->ID));
	if ($user_orders) {
		foreach($user_orders as $user_order) {
			$order_id = $user_order->ID;
			$order_items = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_order_items WHERE order_id = %s AND order_item_type = 'line_item'", $wpdb->prefix, $order_id));
			$missing_files = false;
			if ($order_items) {
				foreach($order_items as $order_item) {
					$pp_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s AND atcaction = 'artwork'", $wpdb->prefix, $order_item->order_item_id));
					if ($pp_item_data) {
						$artwork_files = $pp_item_data->artwork_files;
						if (!strlen($artwork_files)) {
							$missing_files = true;
							$missing_items[$order_id]++;
						}
					}
				}
			}
			if ($missing_files) {
				$missing_files_orders[] = $user_order;
			}
		}
	}
	?>
	<div class="wrap orders-awaiting-approval-wrap">
		<?php if (isset($_SESSION['orders_missing_files_message']) && strlen($_SESSION['orders_missing_files_message'])) { ?>
			<div class="notice-success"><p><?php echo $_SESSION['orders_missing_files_message']; ?></p></div>
		<?php unset($_SESSION['orders_missing_files_message']); } ?>
		<table class="woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<th scope="col" class="manage-column" style="width:60px;"><?php _e('Order', 'wp2print'); ?></th>
					<th scope="col" class="manage-column" style="text-align:center;"><?php _e('Items missing files', 'wp2print'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Date', 'wp2print'); ?></th>
					<th scope="col" class="manage-column" style="width:80px;"><?php _e('Actions', 'wp2print'); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php if ($missing_files_orders) {
					foreach($missing_files_orders as $missing_files_order) {
						$order_id = $missing_files_order->ID;
						$order_date = $missing_files_order->post_date;
						$the_order = wc_get_order($order_id);
						$item_count = $the_order->get_item_count();
						?>
						<tr>
							<td><a href="?view=<?php echo $order_id; ?>">#<?php echo $order_id; ?></a></td>
							<td style="text-align:center;"><?php echo $missing_items[$order_id]; ?></td>
							<td><?php echo date('M j, Y', strtotime($order_date)); ?></td>
							<td><a href="?view=<?php echo $order_id; ?>" class="woocommerce-button button view"><?php _e('Add files', 'wp2print'); ?></a></td>
						</tr>
					<?php } ?>
				<?php } else { ?>
					<tr>
						<td colspan="4"><?php _e('No orders.', 'wp2print'); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
<?php } ?>