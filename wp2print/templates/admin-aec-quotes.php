<?php
$transit = '';
$search_transit = '';
$records_per_page = 20;

$view = (int)$_GET['view'];

$paged = 1;
if (isset($_GET['paged'])) {
	$paged = (int)$_GET['paged'];
	$transit = '&paged='.$paged;
}

$where = 'a.status = 0';

$s_date_range = '';
if (isset($_GET['s_date_range']) && $_GET['s_date_range']) {
	$s_date_range = (int)$_GET['s_date_range'];
	$s_date = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - $s_date_range, date('Y')));
	$where .= " AND a.created >= '".$s_date."'";
	$search_transit = '&s_date_range='.$s_date_range;
	$transit .= '&s_date_range='.$s_date_range;
}

$limit_start = ($paged - 1) * $records_per_page;

$aec_quotes = $wpdb->get_results(sprintf("SELECT SQL_CALC_FOUND_ROWS a.*, u.display_name FROM %sprint_products_aec_orders a LEFT JOIN %susers u ON u.ID = a.user_id WHERE %s ORDER BY a.order_id DESC LIMIT %s, %s", $wpdb->prefix, $wpdb->prefix, $where, $limit_start, $records_per_page));
$aec_quotes_total = $wpdb->get_var("SELECT FOUND_ROWS()");
$aec_quotes_total_pages = ceil($aec_quotes_total / $records_per_page);

$dranges = array(
	1  => __('1 day', 'wp2print'),
	7  => __('7 days', 'wp2print'),
	30 => __('30 days', 'wp2print'),
	90 => __('90 days', 'wp2print')
);
?>
<div class="wrap iclean-services-wrap">
	<h1><?php _e('RapidQuotes', 'wp2print'); ?></h1>
	<?php if (isset($_SESSION['aec_order_message'])) { ?><div id="message" class="updated fade"><p><?php _e($_SESSION['aec_order_message'], 'wp2print'); unset($_SESSION['aec_order_message']); ?></p></div><?php } ?>
	<?php if ($view) {
		$aec_order = $wpdb->get_row(sprintf("SELECT a.*, u.display_name FROM %sprint_products_aec_orders a LEFT JOIN %susers u ON u.ID = a.user_id WHERE a.order_id = %s AND status = 0", $wpdb->prefix, $wpdb->prefix, $view));
		if ($aec_order) {
			$product_id = $aec_order->product_id;
			$product_type = print_products_get_type($product_id);
			$smparams = explode(':', $aec_order->smparams);
			$dimension_unit = print_products_get_aec_dimension_unit();
			$material_attribute = $print_products_settings['material_attribute'];
			$material_attribute_row = $wpdb->get_row(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies WHERE attribute_id = %s", $wpdb->prefix, $material_attribute));
			if ($material_attribute_row) {
				$material_label = $material_attribute_row->attribute_label;
				$material_slug = $material_attribute_row->attribute_name;
				$material_term = $wpdb->get_var(sprintf("SELECT t.name FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'pa_%s' AND t.term_id = %s", $wpdb->prefix, $wpdb->prefix, $material_slug, $smparams[1]));
			}
			$artworkfiles = explode(';', $aec_order->artworkfiles);
			?>
			<div class="view-aec-quote">
				<table>
					<tr>
						<td><?php _e('Customer', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->display_name; ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('AEC Product', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo get_the_title($aec_order->product_id); ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('Quantity', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->qty; ?></strong></td>
					</tr>
					<tr>
						<td><?php echo $material_label; ?>:&nbsp;</td>
						<td><strong><?php echo $material_term; ?></strong></td>
					</tr>
					<tr>
						<td nowrap><?php _e('Project name', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->project_name; ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('Files', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php $sep = ''; foreach($artworkfiles as $afile) { echo $sep.basename($afile); $sep = ', '; } ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('Total Area', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->total_area; ?> <?php echo $dimension_unit; ?><sup>2</sup></strong></td>
					</tr>
					<tr>
						<td><?php _e('Total Pages', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->total_pages; ?></strong></td>
					</tr>
					<?php if ($product_type == 'aecbwc') { ?>
					<tr>
						<td><?php _e('Area B/W', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->area_bw; ?> <?php echo $dimension_unit; ?><sup>2</sup></strong></td>
					</tr>
					<tr>
						<td><?php _e('Pages B/W', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->pages_bw; ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('Area Color', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->area_cl; ?> <?php echo $dimension_unit; ?><sup>2</sup></strong></td>
					</tr>
					<tr>
						<td><?php _e('Pages Color', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo $aec_order->pages_cl; ?></strong></td>
					</tr>
					<?php } ?>
					<tr>
						<td><?php _e('Total price', 'wp2print'); ?>:&nbsp;</td>
						<td><strong><?php echo print_products_display_price($aec_order->total_price); ?></strong></td>
					</tr>
				</table>
			</div>
		<?php } ?>
	<?php } ?>
	<form class="search-form wp-clearfix" action="admin.php">
		<input type="hidden" name="page" value="aec-quotes">
		<p style="float:left; margin-bottom:5px;">
			<?php _e('Date range', 'wp2print'); ?>:
			<select name="s_date_range">
				<option value=""><?php _e('All', 'wp2print'); ?></option>
				<?php foreach($dranges as $drkey => $drname) { ?>
					<option value="<?php echo $drkey; ?>"<?php if ($drkey == $s_date_range) { echo ' SELECTED'; } ?>><?php echo $drname; ?></option>
				<?php } ?>
			</select>
			<input type="submit" value="<?php _e('Filter', 'wp2print'); ?>" class="button" id="search-submit">
		</p>
	</form>
	<table class="wp-list-table widefat fixed striped users">
		<thead>
			<tr>
				<th class="manage-column" scope="col"><?php _e('Date', 'wp2print'); ?></th>
				<th class="manage-column" scope="col"><?php _e('Customer', 'wp2print'); ?></th>
				<th class="manage-column" scope="col"><?php _e('Project name', 'wp2print'); ?></th>
				<th class="manage-column" scope="col"><?php _e('Count of files', 'wp2print'); ?></th>
				<th class="manage-column" scope="col"><?php _e('Price', 'wp2print'); ?></th>
				<th class="manage-column" scope="col"><?php _e('View', 'wp2print'); ?></th>
			</tr>
		</thead>
		<tbody id="the-list">
			<?php if ($aec_quotes) { ?>
				<?php foreach($aec_quotes as $aec_quote) {
					$order_id = $aec_quote->order_id;
					?>
					<tr>
						<td><?php echo date('Y-m-d H:i', strtotime($aec_quote->created)); ?></td>
						<td><?php echo $aec_quote->display_name; ?></td>
						<td><?php echo $aec_quote->project_name; ?></td>
						<td><?php echo count(explode(';', $aec_quote->artworkfiles)); ?></td>
						<td><?php echo print_products_display_price($aec_quote->total_price); ?></td>
						<td><a href="admin.php?page=aec-quotes&view=<?php echo $order_id; ?><?php echo $transit; ?>"><?php _e('View', 'wp2print'); ?></a></td>
					</tr>
				<?php } ?>
			<?php } else { ?>
				<tr>
					<td colspan="6"><?php _e('No quotes found.', 'wp2print'); ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php if ($aec_quotes_total_pages > 1) { ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php if (($paged - 1) > 0) { ?>
					<a href="admin.php?page=aec-quotes&paged=<?php echo ($paged - 1); ?><?php echo $search_transit; ?>" class="prev-page"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">&lsaquo;</span></a>
				<?php } else { ?>
					<span aria-hidden="true" class="tablenav-pages-navspan">&lsaquo;</span>
				<?php } ?>
				<span class="paging-input" id="table-paging"><span class="tablenav-paging-text"><?php echo $paged; ?> <?php _e('of', 'wp2print'); ?> <span class="total-pages"><?php echo $aec_quotes_total_pages; ?></span></span></span>
				<?php if ($paged < $aec_quotes_total_pages) { ?>
					<a href="admin.php?page=aec-quotes&paged=<?php echo ($paged + 1); ?><?php echo $search_transit; ?>" class="next-page"><span class="screen-reader-text">Next page</span><span aria-hidden="true">&rsaquo;</span></a>
				<?php } else { ?>
					<span aria-hidden="true" class="tablenav-pages-navspan">&rsaquo;</span>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>