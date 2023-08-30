<?php
global $wpdb;
$qo_per_page = 20;
$qo_page = 1;
if (isset($_GET['qopage'])) { $qo_page = (int)$_GET['qopage']; }

$qo_start = ($qo_page - 1) * $qo_per_page;

$where = '';
$stransit = '';
if (isset($_GET['s']) && strlen(trim($_GET['s']))) {
	$s = trim($_GET['s']);
	$where = " WHERE u.display_name LIKE '%".$s."%' OR u.user_email LIKE '%".$s."%'";

	$order_ids = array();
	$quote_orders_items = $wpdb->get_results(sprintf("SELECT qi.* FROM %sprint_products_quotes_items qi LEFT JOIN %sposts p ON p.ID = qi.product_id WHERE p.post_title LIKE '".$s."' OR p.post_content LIKE '".$s."'", $wpdb->prefix, $wpdb->prefix, '%'.$s.'%', '%'.$s.'%'));
	if ($quote_orders_items) {
		foreach($quote_orders_items as $quote_orders_item) {
			if (!in_array($quote_orders_item->order_id, $order_ids)) {
				$order_ids[] = $quote_orders_item->order_id;
			}
		}
		$where .= " OR q.order_id IN (".implode(',', $order_ids).")";
	}
	$stransit = 's='.$s.'&';
}

$quote_orders = $wpdb->get_results(sprintf("SELECT SQL_CALC_FOUND_ROWS q.*, u.display_name FROM %sprint_products_quotes q LEFT JOIN %susers u ON u.ID = q.user_id %s ORDER BY q.order_id DESC LIMIT %s, %s", $wpdb->prefix, $wpdb->prefix, $where, $qo_start, $qo_per_page));
$quote_orders_total = $wpdb->get_var("SELECT FOUND_ROWS()");
$quote_orders_pages = 1;
if ($quote_orders_total) {
	$quote_orders_pages = ceil($quote_orders_total / $qo_per_page);
}
?>
<style>.wp2print-sq-history table td { vertical-align:middle; }</style>
<?php if (isset($_GET['resent'])) { ?>
	<div class="updated notice is-dismissible">
		<p><?php _e('Quote email was successfully sent.', 'wp2print'); ?></p>
	</div>
<?php } ?>
<div class="wrap wp2print-create-order wp2print-sq-history">
	<h2><?php _e('Send Quote history', 'wp2print'); ?></h2>
	<form class="sqh-search-form">
		<input type="hidden" name="page" value="print-products-send-quote-history">
		<p>
			<input id="post-search-input" type="text" name="s" value="<?php if (isset($_GET['s'])) { echo $_GET['s']; } ?>">
			<input id="search-submit" type="submit" class="button" value="<?php _e('Search', 'wp2print'); ?>">
		</p>
	</form>
	<table class="wp-list-table widefat" width="100%">
		<thead>
			<tr>
				<th><?php _e('ID', 'wp2print'); ?></th>
				<th><?php _e('Customer', 'wp2print'); ?></th>
				<th><?php _e('Product', 'wp2print'); ?></th>
				<th style="text-align:center;"><?php _e('Qty', 'wp2print'); ?></th>
				<th><?php _e('Price', 'wp2print'); ?></th>
				<th style="text-align:center; width:140px;"><?php _e('Converted', 'wp2print'); ?></th>
				<th><?php _e('Resend Quote email', 'wp2print'); ?></th>
				<th><?php _e('Created', 'wp2print'); ?></th>
				<th><?php _e('Expired', 'wp2print'); ?></th>
				<th>&nbsp;</th>
			</tr>
			<?php if ($quote_orders) { ?>
				<?php foreach ($quote_orders as $quote_order) {
					$qitems = array();
					$quote_order_items = $wpdb->get_results(sprintf("SELECT qi.*, p.post_title FROM %sprint_products_quotes_items qi LEFT JOIN %sposts p ON p.ID = qi.product_id WHERE qi.order_id = %s", $wpdb->prefix, $wpdb->prefix, $quote_order->order_id));
					if ($quote_order_items) {
						foreach($quote_order_items as $quote_order_item) {
							$qitems['name'][] = $quote_order_item->post_title;
							$qitems['quantity'][] = $quote_order_item->quantity;
							$qitems['price'][] = wc_price($quote_order_item->price);
						}
					}
					?>
					<tr>
						<td><?php echo $quote_order->order_id; ?></td>
						<td><?php echo $quote_order->display_name; ?></td>
						<td><?php if (isset($qitems['name'])) { echo implode('<br>', $qitems['name']); } ?></td>
						<td style="text-align:center;"><?php if (isset($qitems['name'])) { echo implode('<br>', $qitems['quantity']); } ?></td>
						<td><?php if (isset($qitems['name'])) { echo implode('<br>', $qitems['price']); } ?></td>
						<td style="text-align:center;"><?php $img = 'icon-no.svg'; if ($quote_order->status == 1) { $img = 'icon-yes.svg'; } ?><img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL . 'images/' . $img; ?>" alt="" style="width:18px;margin-top:2px;"></td>
						<td><?php if ($quote_order->status != 1) { ?><a href="#resend" class="button sqh-resend-email" data-oid="<?php echo $quote_order->order_id; ?>"><?php _e('Resend Quote email', 'wp2print'); ?></a><?php } ?></td>
						<td><?php echo date('Y-m-d', strtotime($quote_order->created)); ?></td>
						<td><?php if (strlen($quote_order->expire_date)) { echo $quote_order->expire_date; } else { echo '-'; } ?></td>
						<td><?php if ($quote_order->status == 0) { ?><a href="#duplicate" class="fai-duplicate sqh-duplicate" data-oid="<?php echo $quote_order->order_id; ?>" title="<?php _e('Duplicate', 'wp2print'); ?>"><?php _e('Duplicate', 'wp2print'); ?></a><?php } ?></td>
					</tr>
				<?php } ?>
			<?php } else { ?>
				<tr>
					<td colspan="9"><?php _e('No quote orders.', 'wp2print'); ?></td>
				</tr>
			<?php } ?>
		</thead>
		<tbody>
		</tbody>
	</table>
	<?php if ($quote_orders_pages > 1) { ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo $quote_orders_total; ?> <?php _e('items', 'wp2print'); ?></span>

				<?php if ($qo_page > 1) { ?>
					<a href="admin.php?page=print-products-send-quote-history&<?php echo $stransit; ?>qopage=<?php echo ($qo_page - 1); ?>" class="prev-page button"><span aria-hidden="true">&lsaquo;</span></a>
				<?php } else { ?>
					<span aria-hidden="true" class="tablenav-pages-navspan button disabled">&lsaquo;</span>
				<?php } ?>
				<span class="paging-input" id="table-paging"><span class="tablenav-paging-text"><?php echo $qo_page; ?> <?php _e('of', 'wp2print'); ?> <span class="total-pages"><?php echo $quote_orders_pages; ?></span></span></span>
				<?php if (($qo_page + 1) <= $quote_orders_pages) { ?>
					<a href="admin.php?page=print-products-send-quote-history&<?php echo $stransit; ?>qopage=<?php echo ($qo_page + 1); ?>" class="next-page button"><span aria-hidden="true">&rsaquo;</span></a>
				<?php } else { ?>
					<span aria-hidden="true" class="tablenav-pages-navspan button disabled">&rsaquo;</span>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
	<div style="display:none;">
		<form method="POST" class="sqh-form">
		<input type="hidden" name="sqh_action" class="sqh-action">
		<input type="hidden" name="order_id" class="sqh-order-id">
		</form>
	</div>
</div>