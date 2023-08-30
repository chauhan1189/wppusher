<?php
global $wpdb, $current_user;
$orders_per_page = 20;
$print_products_prodview_options = get_option("print_products_prodview_options");
$oistatuses = print_products_oistatus_get_list();
$approval_statuses = print_products_orders_proof_get_approval_statuses();
$oiemployees = print_products_vendor_get_vendors();
$oivendors = print_products_vendor_get_vendors_array();

$print_products_oistatus_options = get_option("print_products_oistatus_options");
$ois_list = array();
if (isset($print_products_oistatus_options['list']) && is_array($print_products_oistatus_options['list'])) {
	$ois_list = $print_products_oistatus_options['list'];
}

$pvpage = 1;
if (isset($_GET['pvpage'])) { $pvpage = (int)$_GET['pvpage']; }
$limit_start = ($pvpage - 1) * $orders_per_page;

$where = "p.post_type = 'shop_order'";
if ($print_products_prodview_options['exclude_woo'] && is_array($print_products_prodview_options['exclude_woo'])) {
	$include_statuses = array();
	$wc_order_statuses = wc_get_order_statuses();
	foreach($wc_order_statuses as $os_key => $os_name) {
		if (!in_array($os_key, $print_products_prodview_options['exclude_woo'])) {
			$include_statuses[] = $os_key;
		}
	}
	$where .= " AND p.post_status IN ('".implode("','", $include_statuses)."')";
}
$stransit = '';
$order_ids = array();
$vendor_company_id = 0;
if (print_products_vendor_is_vendor()) {
	$vendor_company = print_products_vendor_get_user_company($current_user->ID);
	if ($vendor_company) {
		if ($vendor_company['access'] != 1) {
			$vendor_company_id = $vendor_company['id'];
		}
	}
}
if (isset($_GET['_vendor_company']) && $_GET['_vendor_company']) {
	$vendor_company_id = (int)$_GET['_vendor_company'];
	$stransit .= '&_vendor_company='.$_GET['_vendor_company'];
}
if ($vendor_company_id) {
	$vc_orders = $wpdb->get_results(sprintf("SELECT post_id FROM %spostmeta WHERE meta_key = '_order_vendor' AND meta_value = '%s'", $wpdb->prefix, $vendor_company_id));
	if ($vc_orders) {
		foreach($vc_orders as $vc_order) {
			$order_ids[] = $vc_order->post_id;
		}
	} else {
		$order_ids = array(0);
	}
}
if (isset($_GET['_vendor_employee']) && $_GET['_vendor_employee']) {
	$order_ids = array(0);
	$vendor_employee = (int)$_GET['_vendor_employee'];
	$stransit .= '&_vendor_employee='.$_GET['_vendor_employee'];
	$order_items = $wpdb->get_results(sprintf("SELECT oi.order_id FROM %swoocommerce_order_items oi LEFT JOIN %swoocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id WHERE oim.meta_key = '_item_vendor_employee' AND oim.meta_value = '%s'", $wpdb->prefix, $wpdb->prefix, $_GET['_vendor_employee']));
	if ($order_items) {
		$order_ids = array();
		foreach($order_items as $order_item) {
			if (!in_array($order_item->order_id, $order_ids)) {
				$order_ids[] = $order_item->order_id;
			}
		}
	}
}
if ($order_ids && count($order_ids)) {
	$where .= " AND p.ID IN (".implode(',', $order_ids).")";
}

if (isset($_GET['s']) && strlen($_GET['s'])) {
	$s = trim($_GET['s']);
	$sfields = array();
	if (is_numeric($s)) {
		$sfields[] = "p.ID = '".$s."'";
	} else {
		$s = str_replace("'", "''", $s);
		$swords = explode(' ', $s);

		$sconditions = array();
		foreach($swords as $sword) {
			$sconditions[] = "oi.order_item_name LIKE '%".$sword."%'";
		}
		$sfields[] = "(".implode(' AND ', $sconditions).")";

		if (print_products_vendor_show_prodview_vendor_column()) {
			$order_ids = print_products_vendor_get_s_orders($_GET['s']);
			if ($order_ids && count($order_ids)) {
				$sfields[] = "p.ID IN (".implode(',', $order_ids).")";
			}
		}
	}
	$where .= ' AND ('.implode(' OR ', $sfields).')';
	$stransit .= '&s='.$_GET['s'];
}
$wc_orders = $wpdb->get_results(sprintf("SELECT SQL_CALC_FOUND_ROWS p.* FROM %sposts p LEFT JOIN %swoocommerce_order_items oi ON oi.order_id = p.ID WHERE %s GROUP BY p.ID ORDER BY p.ID DESC LIMIT %s, %s", $wpdb->prefix, $wpdb->prefix, $where, $limit_start, $orders_per_page));
$wc_orders_total = $wpdb->get_var("SELECT FOUND_ROWS()");
$wc_orders_total_pages = ceil($wc_orders_total / $orders_per_page);
$colspan = 5;
if (print_products_vendor_show_prodview_employee_column()) { $colspan++; }
if (print_products_vendor_show_prodview_vendor_column()) { $colspan++; }
if (print_products_vendor_show_prodview_ccompany_column()) { $colspan++; }
if (print_products_oirsdate_show_prodview_shipdate_column()) { $colspan++; }
?>
<div class="wrap wp2print-production-view">
	<h2><?php _e('Production View', 'wp2print'); ?></h2>
	<form class="sqh-search-form">
		<?php if (isset($_GET['post_type'])) { ?><input type="hidden" name="post_type" value="<?php echo $_GET['post_type']; ?>"><?php } ?>
		<input type="hidden" name="page" value="print-products-production-view">
		<p>
			<input id="post-search-input" type="text" name="s" value="<?php if (isset($_GET['s'])) { echo $_GET['s']; } ?>" style="vertical-align:middle;">
			<?php print_products_vendor_filter_vendor_dropdown(); ?>
			<?php print_products_vendor_filter_employee_dropdown(); ?>
			<input id="search-submit" type="submit" class="button" value="<?php _e('Search', 'wp2print'); ?>" style="vertical-align:middle;">
		</p>
	</form>
	<table class="wp-list-table widefat" width="100%">
		<thead>
			<tr>
				<th colspan="<?php echo $colspan; ?>">&nbsp;</th>
				<th colspan="<?php echo count($ois_list); ?>" style="text-align:center;"><?php _e('Production Status', 'wp2print'); ?></th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<th><?php _e('OrderID', 'wp2print'); ?></th>
				<th><?php _e('Date', 'wp2print'); ?></th>
				<th><?php _e('Status', 'wp2print'); ?></th>
				<?php if (print_products_vendor_show_prodview_ccompany_column()) { ?>
					<th><?php _e('Customer Company', 'wp2print'); ?></th>
				<?php } ?>
				<th style="text-align:center;"><span class="icon-approval"></span></th>
				<th style="min-width:120px;"><?php _e('Item', 'wp2print'); ?></th>
				<?php if (print_products_vendor_show_prodview_employee_column()) { ?>
					<th><?php _e('Employee', 'wp2print'); ?></th>
				<?php } ?>
				<?php if (print_products_vendor_show_prodview_vendor_column()) { ?>
					<th><?php _e('Vendor', 'wp2print'); ?></th>
				<?php } ?>
				<?php if (print_products_oirsdate_show_prodview_shipdate_column()) { ?>
					<th><?php _e('Required ship date', 'wp2print'); ?></th>
				<?php } ?>
				<?php foreach($ois_list as $ois_data) { ?>
					<th style="text-align:center;width:7%;"><?php echo $ois_data['name']; ?></th>
				<?php } ?>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($wc_orders) { ?>
				<?php foreach($wc_orders as $wc_order) {
					$order_id = $wc_order->ID;
					$order = wc_get_order($order_id);
					$order_date = $order->get_date_created();
					$order_items = $order->get_items();
					$order_company = $order->get_shipping_company();
					if (!strlen($order_company)) { $order_company = $order->get_billing_company(); }
					?>
					<tr class="ois-order-<?php echo $order_id; ?>">
						<td><a href="post.php?post=<?php echo $order_id; ?>&action=edit"><?php echo $order_id; ?></a></td>
						<td><?php echo wc_format_datetime($order_date); ?></td>
						<td class="o-status"><?php echo wc_get_order_status_name($order->get_status()); ?></td>
						<?php if (print_products_vendor_show_prodview_ccompany_column()) { ?>
							<td class="o-ccompany"><?php echo $order_company; ?></td>
						<?php } ?>
						<td class="column-approval">
							<table cellspacing="0" cellpadding="0" width="100%" class="ois-items">
								<?php foreach($order_items as $item_id => $order_item) { ?>
									<tr><td><?php print_products_oistatus_approval_status($item_id, $approval_statuses); ?></td></tr>
								<?php } ?>
							</table>
						</td>
						<td>
							<table cellspacing="0" cellpadding="0" width="100%" class="ois-items">
								<?php foreach($order_items as $item_id => $order_item) { ?>
									<tr><td><?php echo $order_item->get_name(); ?></td></tr>
								<?php } ?>
							</table>
						</td>
						<?php if (print_products_vendor_show_prodview_employee_column()) { ?>
							<td class="column-employee">
								<table cellspacing="0" cellpadding="0" width="100%" class="ois-items">
									<?php foreach($order_items as $item_id => $order_item) {
										$item_vendor_employee = (int)wc_get_order_item_meta($item_id, '_item_vendor_employee', true); ?>
										<tr><td><?php echo $oiemployees[$item_vendor_employee]; ?></td></tr>
									<?php } ?>
								</table>
							</td>
						<?php } ?>
						<?php if (print_products_vendor_show_prodview_vendor_column()) { ?>
							<td class="column-vendor">
								<table cellspacing="0" cellpadding="0" width="100%" class="ois-items">
									<?php foreach($order_items as $item_id => $order_item) {
										$item_vendor = (int)wc_get_order_item_meta($item_id, '_item_vendor', true); ?>
										<tr><td><?php echo $oivendors[$item_vendor]; ?></td></tr>
									<?php } ?>
								</table>
							</td>
						<?php } ?>
						<?php if (print_products_oirsdate_show_prodview_shipdate_column()) { ?>
							<td class="column-shipdate">
								<table cellspacing="0" cellpadding="0" width="100%" class="ois-items">
									<?php foreach($order_items as $item_id => $order_item) {
										$item_rsdate = wc_get_order_item_meta($item_id, '_item_rsdate', true); ?>
										<tr><td><?php echo $item_rsdate; ?></td></tr>
									<?php } ?>
								</table>
							</td>
						<?php } ?>
						<td colspan="<?php echo count($ois_list); ?>">
							<table cellspacing="0" cellpadding="0" width="100%" class="ois-graph">
								<?php foreach($order_items as $item_id => $order_item) {
									$item_status = wc_get_order_item_meta($item_id, '_item_status', true); ?>
									<tr class="ois-graph-<?php echo $item_id; ?>">
										<?php foreach($ois_list as $ois_data) {
											$oiskey = print_products_oistatus_get_slug($ois_data['name']);
											$class = '';
											if ($oiskey == $item_status) { $class = ' active'; } ?>
											<td class="ois-<?php echo $oiskey; ?><?php echo $class; ?>" style="width:<?php echo 100 / count($ois_list); ?>%;background:<?php echo $ois_data['color']; ?>;">&nbsp;</td>
										<?php } ?>
									</tr>
								<?php } ?>
							</table>
						</td>
						<td><?php foreach($order_items as $order_item) {
							$item_id = $order_item->get_id();
							$item_status = wc_get_order_item_meta($item_id, '_item_status', true);
							if (print_products_vendor_allow_for_item($item_id)) { ?>
								<select name="ois" class="ois-ldd-<?php echo $item_id; ?>" onchange="wp2print_ois_change(<?php echo $order_id; ?>, <?php echo $item_id; ?>, 'pview')">
									<option value="">-- <?php _e('Status', 'wp2print'); ?> --</option>
									<?php foreach($oistatuses as $ois_key => $ois_val) { ?>
										<option value="<?php echo $ois_key; ?>"<?php if ($ois_key == $item_status) { echo ' SELECTED'; } ?>><?php echo $ois_val; ?></option>
									<?php } ?>
								</select>
							<?php } else { ?>
								&nbsp;
							<?php } ?><br>
						<?php } ?><div class="ois-success"><?php _e('Updated.', 'wp2print'); ?></div></td>
					</tr>
				<?php } ?>
			<?php } else { ?>
				<tr>
					<td colspan="<?php echo count($ois_list) + 7; ?>"><?php _e('No orders found.', 'wp2print'); ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php if ($wc_orders_total_pages > 1) { ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo $wc_orders_total; ?> <?php _e('items', 'wp2print'); ?></span>

				<?php if ($pvpage > 1) { ?>
					<a href="admin.php?page=print-products-production-view&pvpage=<?php echo ($pvpage - 1); ?><?php echo $stransit; ?>" class="prev-page button"><span aria-hidden="true">&lsaquo;</span></a>
				<?php } else { ?>
					<span aria-hidden="true" class="tablenav-pages-navspan button disabled">&lsaquo;</span>
				<?php } ?>
				<span class="paging-input" id="table-paging"><span class="tablenav-paging-text"><?php echo $pvpage; ?> <?php _e('of', 'wp2print'); ?> <span class="total-pages"><?php echo $wc_orders_total_pages; ?></span></span></span>
				<?php if (($pvpage + 1) <= $wc_orders_total_pages) { ?>
					<a href="admin.php?page=print-products-production-view&pvpage=<?php echo ($pvpage + 1); ?><?php echo $stransit; ?>" class="next-page button"><span aria-hidden="true">&rsaquo;</span></a>
				<?php } else { ?>
					<span aria-hidden="true" class="tablenav-pages-navspan button disabled">&rsaquo;</span>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>
