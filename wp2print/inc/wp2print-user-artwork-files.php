<?php
add_action('show_user_profile', 'print_products_user_artwork_files_user_profile', 100);
add_action('edit_user_profile', 'print_products_user_artwork_files_user_profile', 100);
function print_products_user_artwork_files_user_profile($profileuser) {
	?>
	<h3><?php _e('User artwork files', 'wp2print'); ?></h3>
	<table class="form-table">
		<tr>
			<th><label><?php _e('User artwork files', 'wp2print'); ?></label></th>
			<td><a href="admin.php?page=print-products-users-artwork-files&user_id=<?php echo $profileuser->ID; ?>" class="button"><?php _e("View user's files", 'wp2print'); ?></a></td>
		</tr>
	</table>
	<?php
}

function print_products_user_artwork_files_admin_page() {
	global $wpdb;
	$wp_users = get_users(array('orderby' => 'display_name'));

	$user_id = 0;
	$user_name = '';
	if (isset($_GET['user_id'])) { $user_id = (int)$_GET['user_id']; }

	$user_files = false;
	if ($user_id) {
		$user_orders = $wpdb->get_results(sprintf("SELECT oi.order_id, ppoi.artwork_files FROM %swoocommerce_order_items oi LEFT JOIN %spostmeta pm ON pm.post_id = oi.order_id LEFT JOIN %sprint_products_order_items ppoi ON ppoi.item_id = oi.order_item_id WHERE oi.order_item_type = 'line_item' AND pm.meta_key = '_customer_user' AND pm.meta_value = '%s' AND ppoi.artwork_files != '' ORDER BY oi.order_id DESC", $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $user_id));
		if ($user_orders) {
			foreach($user_orders as $user_order) {
				if (strlen($user_order->artwork_files)) {
					$artwork_files = unserialize($user_order->artwork_files);
					foreach($artwork_files as $afile) {
						$user_files[$user_order->order_id][] = '<a href="'.print_products_get_amazon_file_url($afile).'" target="_blank">'.$afile.'</a>';
					}
				}
			}
		}
		$user_designer_orders = $wpdb->get_results(sprintf("SELECT oi.order_id, oim.meta_value FROM %swoocommerce_order_items oi LEFT JOIN %spostmeta pm ON pm.post_id = oi.order_id LEFT JOIN %swoocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id WHERE oi.order_item_type = 'line_item' AND pm.meta_key = '_customer_user' AND pm.meta_value = '%s' AND oim.meta_key = '_image_link' ORDER BY oi.order_id DESC", $wpdb->prefix, $wpdb->prefix, $wpdb->prefix, $user_id));
		if ($user_designer_orders) {
			foreach($user_designer_orders as $user_designer_order) {
				if (strlen($user_designer_order->meta_value)) {
					$image_links = explode(',', $user_designer_order->meta_value);
					foreach($image_links as $image_link) {
						$user_files[$user_designer_order->order_id][] = '<a href="'.$image_link.'" target="_blank">'.$image_link.'</a>';
					}
				}
			}
		}
	}
	?>
	<div class="wrap wp2print-uaf-wrap">
		<h2><?php _e('Users artwork files', 'wp2print'); ?></h2>
		<div class="tablenav top">
			<form>
				<?php if (isset($_GET['post_type'])) { ?><input type="hidden" name="post_type" value="<?php echo $_GET['post_type']; ?>"><?php } ?>
				<input type="hidden" name="page" value="print-products-users-artwork-files">
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
						<th><?php _e('Customer', 'wp2print'); ?></th>
						<th><?php _e('OrderID', 'wp2print'); ?></th>
						<th><?php _e('Artwork Files', 'wp2print'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($user_files) { ?>
						<?php foreach ($user_files as $order_id => $afiles) { ?>
							<tr>
								<td><?php echo $user_name; ?></td>
								<td><a href="post.php?post=<?php echo $order_id; ?>&action=edit"><?php echo $order_id; ?></a></td>
								<td><?php echo implode('<br>', $afiles); ?></td>
							</tr>
						<?php } ?>
					<?php } else { ?>
						<tr>
							<td colspan="3"><?php _e('Nothing found.', 'wp2print'); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	</div>
	<?php
}
?>