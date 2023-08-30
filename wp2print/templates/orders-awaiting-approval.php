<?php
global $wp, $wpdb, $current_user;

$group_users = array();
$group_users_name = array();
$view_order_id = false;
$allow_modify_pdf = false;
$approval_statuses = print_products_orders_proof_get_approval_statuses();

if (isset($_GET['view'])) { $view_order_id = $_GET['view']; }

$is_superuser = get_user_meta($current_user->ID, '_superuser_group', true);

if ($is_superuser) {
	$superuser_groups = str_replace(';', "','", $is_superuser);
	$superuser_groups_array = explode(';', $is_superuser);
	$wp_group_users = $wpdb->get_results(sprintf("SELECT user_id FROM %susermeta WHERE meta_key = '_user_group' AND meta_value IN ('%s')", $wpdb->base_prefix, $superuser_groups));
	if ($wp_group_users) {
		foreach($wp_group_users as $wp_group_user) {
			$group_users[] = $wp_group_user->user_id;
			$group_users_name[$wp_group_user->user_id] = $wpdb->get_var(sprintf("SELECT display_name FROM %susers WHERE ID = %s", $wpdb->base_prefix, $wp_group_user->user_id));
		}
	}
} else {
	$group_users_name[$current_user->ID] = $current_user->display_name;
}
// check order owner
if ($view_order_id) {
	$order_customer = get_post_meta($view_order_id, '_customer_user', true);
	if ($is_superuser) {
		if (!in_array($order_customer, $group_users)) {
			$view_order_id = false;
		}
	} else {
		if ($order_customer != $current_user->ID) {
			$view_order_id = false;
		}
	}
}

if ($view_order_id) {
	$item_nmb = 1;
	$the_order = wc_get_order($view_order_id);
	$order_user_id = $the_order->user_id;
	if ($order_user_id) {
		$user_info = get_userdata($order_user_id);
	}

	$allow_modify_pdf = false;
	$order_user_group = print_products_users_groups_get_user_group($order_user_id);
	if ($order_user_group) {
		$allow_modify_pdf = $order_user_group->allow_modify_pdf;
	}
	?>
	<div class="wrap orders-awaiting-approval-details">
		<div class="ma-section">
			<div class="ma-section-head opened" rel="order-details">
				<strong><?php _e('Order details', 'wp2print'); ?></strong>
			</div>
			<div class="ma-section-content order-details">
				<table width="100%" class="">
					<tr>
						<td colspan="3"><?php _e('Order #', 'wp2print'); ?>: <?php echo $view_order_id; ?></td>
					</tr>
					<tr>
						<td colspan="3"><?php _e('Order Date', 'wp2print'); ?>: <?php echo $the_order->order_date; ?></td>
					</tr>
					<tr>
						<td colspan="3"><?php _e('Payment', 'wp2print'); ?>: <?php echo $the_order->payment_method_title; ?></td>
					</tr>
					<tr>
						<td valign="top" style="width:33%;">
							<strong><?php _e('Customer Details', 'wp2print'); ?>:</strong><br />
							<?php _e('Name', 'wp2print'); ?>: <?php echo $user_info->display_name; ?><br />
							<?php _e('Email', 'wp2print'); ?>: <?php echo $user_info->billing_email; ?><br />
							<?php _e('Phone', 'wp2print'); ?>: <?php echo $user_info->billing_phone; ?><br />
							<?php _e('IP Address', 'wp2print'); ?>: <?php echo get_post_meta($view_order_id, '_customer_ip_address', true); ?>
						</td>
						<?php if ($address = $the_order->get_formatted_billing_address()) { ?>
						<td valign="top" style="width:33%;">
							<strong><?php _e('Billing Details', 'wp2print'); ?>:</strong><br />
							<?php echo $address; ?>
						</td>
						<?php } ?>
						<?php if ($address = $the_order->get_formatted_shipping_address()) { ?>
						<td valign="top" style="width:33%;">
							<strong><?php _e('Shipping Details', 'wp2print'); ?>:</strong><br />
							<?php echo $address; ?>
						</td>
						<?php } ?>
					</tr>
					<tr>
						<td colspan="3" style="background:#F4F4F4;"><strong><?php _e('Items Details', 'wp2print'); ?>:</strong></td>
					</tr>
					<tr>
						<td colspan="3">
							<table cellspacing="0" cellpadding="0" width="60%" class="items-table">
								<?php foreach ( $the_order->get_items() as $item_id => $item ) {
									$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
									$approval_status = wc_get_order_item_meta($item_id, '_approval_status', true);
									$proof_files = wc_get_order_item_meta($item_id, '_proof_files', true); ?>
									<tr style="background:rgba(0, 0, 0, 0.025);">
										<td><strong><?php _e('Item', 'wp2print'); ?> <?php echo $item_nmb; ?></strong></td>
										<td><strong><?php _e('Cost', 'wp2print'); ?></strong></td>
										<td><strong><?php _e('Qty', 'wp2print'); ?></strong></td>
										<td><strong><?php _e('Total', 'wp2print'); ?></strong></td>
									</tr>
									<tr>
										<td><?php echo $item['name']; ?>
											<?php if ($order_item_data) {
												$artwork_files = unserialize($order_item_data->artwork_files);
												$artwork_thumbs = unserialize($order_item_data->artwork_thumbs);
												print_products_product_attributes_list_html($order_item_data);
												if ($artwork_files) { ?>
													<div class="print-products-area">
														<ul class="product-attributes-list">
															<li><?php _e('Artwork Files', 'wp2print'); ?>:</li>
															<li><ul class="product-artwork-files-list">
																<?php foreach($artwork_files as $af_key => $artwork_file) {
																	$artwork_thumb = $artwork_thumbs[$af_key];
																	echo '<li>'.print_products_artwork_file_html($artwork_file, $artwork_thumb, 'download').'</li>';
																} ?>
															</ul></li>
														</ul>
													</div>
												<?php }
											}
											$show_designer_files = false;
											if ($is_superuser || !$proof_files) { $show_designer_files = true; }
											if ($show_designer_files) {
												$designer_image = wc_get_order_item_meta($item_id, '_image_link', true);
												if (strlen($designer_image)) {
													$dimages = explode(',', $designer_image); ?>
													<div class="print-products-area">
														<ul class="product-attributes-list">
															<li><?php _e('Designer File', 'wp2print'); ?>:</li>
															<li>
																<ul class="product-artwork-files-list">
																	<?php foreach($dimages as $dimage) { ?>
																		<li><a href="<?php echo $dimage; ?>" title="<?php _e('Download', 'wp2print'); ?>"><img src="<?php echo $dimage; ?>" style="width:70px;border:1px solid #C1C1C1;"></a></li>
																	<?php } ?>
																</ul>
															</li>
													</div>
													<?php
												}
												$pdf_link = wc_get_order_item_meta($item_id, '_pdf_link', true);
												if (strlen($pdf_link)) { $pdf_links = explode(',', $pdf_link); ?>
													<div class="print-products-area">
														<ul class="product-attributes-list">
															<li><?php _e('PDF File(s)', 'wp2print'); ?>:</li>
															<li>
																<ul class="product-artwork-files-list">
																	<?php foreach($pdf_links as $pdf_link) { ?>
																		<li><a href="<?php echo $pdf_link; ?>" title="<?php _e('Download', 'wp2print'); ?>" target="_blank"><img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/icon_doc_pdf.png"></a></li>
																	<?php } ?>
																</ul>
															</li>
														</ul>
														<?php if ($allow_modify_pdf && function_exists('personalize_reedit_order_item')) {
															$p_template_data = print_products_get_order_item_template_data($order_item_data);
															$sess_key = wc_get_order_item_meta($item_id, '_edit_session_key', true);
															$prod_id = wc_get_order_item_meta($item_id, '_product_id', true);
															?>
															<a href="<?php echo site_url('/?oaa_reedit=true&oiid='.$item_id.'&skey='.$sess_key.'&pid='.$prod_id.'&d_product_id='.$p_template_data['d_product_id'].'&d_template_id='.$p_template_data['d_template_id'].'&rurl='.$_SERVER['REQUEST_URI']); ?>" data-rel="nofollow" class="button"><?php _e('Re-edit', 'wp2print'); ?></a>
														<?php } ?>
													</div>
												<?php } ?>
											<?php } ?>
										</td>
										<td><?php echo wc_price($the_order->get_item_total($item, false, true), array('currency' => $the_order->get_order_currency())); ?></td>
										<td><?php echo $item['qty']; ?></td>
										<td><?php echo wc_price($item['line_total'], array('currency' => $the_order->get_order_currency())); ?></td>
									</tr>
									<?php if ($approval_status != 'awaiting' && !strlen($proof_files)) { ?>
										<tr>
											<td colspan="4"><?php _e('New proof not submitted for this item.', 'wp2print'); ?></td>
										</tr>
									<?php } ?>
									<?php if (strlen($approval_status) && !$is_superuser) { ?>
										<?php if ($approval_status == 'awaiting') { ?>
											<tr>
												<td colspan="4">
													<div class="order-item-proofs">
														<?php if (strlen($proof_files)) { $pfiles = explode(';', $proof_files); ?>
															<div class="download-proofs-list">
																<div class="oip-label"><strong><?php _e('Download your proofs', 'wp2print'); ?></strong></div>
																<ul>
																	<?php foreach($pfiles as $proof_file) { ?>
																		<li><a href="<?php echo print_products_get_amazon_file_url($proof_file); ?>" class="button button-primary oaa-download-btn" target="_blank"><?php _e('Download', 'wp2print'); ?></a> <?php echo basename($proof_file); ?></li>
																	<?php } ?>
																</ul>
															</div>
														<?php } ?>
														<div class="oip-label"><strong><?php _e('Approval of proofs', 'wp2print'); ?></strong></div>
														<form method="POST" class="awaiting-approval-form aaform-<?php echo $view_order_id; ?>-<?php echo $item_id; ?>">
														<div class="oip-form">
															<div class="oip-form-label"><?php _e('Comments', 'wp2print'); ?>:</div>
															<textarea name="order_comments" style="width:100%; height:150px;"></textarea>
															<input type="submit" value="<?php _e('Approve', 'wp2print'); ?>" class="act-button approve-button" onclick="jQuery('.aaform-<?php echo $view_order_id; ?>-<?php echo $item_id; ?> .aa-action').val('approve');">
															<input type="submit" value="<?php _e('Reject', 'wp2print'); ?>" class="act-button reject-button" onclick="jQuery('.aaform-<?php echo $view_order_id; ?>-<?php echo $item_id; ?> .aa-action').val('reject');" style="float:right;">
														</div>
														<input type="hidden" name="awaiting_approval_submit" value="true">
														<input type="hidden" name="order_id" value="<?php echo $view_order_id; ?>">
														<input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
														<input type="hidden" name="awaiting_approval_action" class="aa-action">
														<input type="hidden" name="redirectto" value="<?php echo get_permalink() . 'orders-awaiting-approval/'; ?>">
														</form>
													</div>
												</td>
											</tr>
										<?php } else { ?>
											<tr>
												<td colspan="4">
													<ul class="oi-approval">
														<li><?php _e('Approval', 'wp2print'); ?>:</li>
														<li><span class="<?php echo $approval_status; ?>" title="<?php echo $approval_statuses[$approval_status]; ?>"></span></li>
													</ul>
												</td>
											</tr>
										<?php } ?>
									<?php } ?>
									<tr style="background:none;"><td colspan="4" style="border-top:1px dotted #C1C1C1;"></td></tr>
								<?php $item_nmb++; } ?>
								<tr>
									<td colspan="3" style="text-align:right;"><strong><?php _e('Subtotal', 'wp2print'); ?>:</strong></td>
									<td><?php echo wc_price($the_order->get_subtotal(), array('currency' => $the_order->get_order_currency())); ?></td>
								</tr>
								<tr>
									<td colspan="3" style="text-align:right;"><strong><?php _e('Shipping', 'wp2print'); ?>:</strong></td>
									<td><?php echo wc_price($the_order->get_total_shipping(), array('currency' => $the_order->get_order_currency())); ?></td>
								</tr>
								<?php if (wc_tax_enabled()) : ?>
									<?php foreach ($the_order->get_tax_totals() as $code => $tax) : ?>
										<tr>
											<td colspan="3" style="text-align:right;"><strong><?php echo $tax->label; ?>:</strong></td>
											<td><?php echo $tax->formatted_amount; ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
								<tr>
									<td colspan="3" style="text-align:right;"><strong><?php _e('Order Total', 'wp2print'); ?>:</strong></td>
									<td><?php echo $the_order->get_formatted_order_total(); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php if ($is_superuser) { ?>
						<tr>
							<td colspan="3" style="background:#F4F4F4;"><strong><?php _e('Approval of proofs', 'wp2print'); ?>:</strong></td>
						</tr>
						<tr>
							<td colspan="3">
								<div class="order-item-proofs">
									<form method="POST" class="awaiting-approval-form aaform-superuser">
									<div class="oip-form">
										<div class="oip-form-label"><?php _e('Comments', 'wp2print'); ?>:</div>
										<textarea name="order_comments" style="width:100%; height:150px;"></textarea>
										<input type="submit" value="<?php _e('Approve', 'wp2print'); ?>" class="act-button approve-button" onclick="jQuery('.aaform-superuser .aa-action').val('approve');">
										<input type="submit" value="<?php _e('Reject entire order', 'wp2print'); ?>" class="act-button reject-button" onclick="jQuery('.aaform-aaform-superuser .aa-action').val('reject');" style="float:right;">
									</div>
									<input type="hidden" name="orders_proof_action" value="superuser-submit">
									<input type="hidden" name="order_id" value="<?php echo $view_order_id; ?>">
									<input type="hidden" name="aa_action" class="aa-action">
									<input type="hidden" name="redirectto" value="<?php echo get_permalink() . 'orders-awaiting-approval/'; ?>">
									</form>
								</div>
							</td>
						</tr>
					<?php } ?>
				</table>
			</div>
		</div>
	</div>
	<?php
} else {
	$aa_orders = print_products_orders_proof_get_awaiting_orders($is_superuser, $group_users);
	?>
	<div class="wrap orders-awaiting-approval-wrap">
		<?php if (strlen($_SESSION['awaiting_approval_message'])) { ?>
			<div class="notice-success"><p><?php echo $_SESSION['awaiting_approval_message']; ?></p></div>
		<?php unset($_SESSION['awaiting_approval_message']); } ?>
		<table class="woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<th scope="col" class="manage-column" style="width:60px;"><?php _e('Order', 'wp2print'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Author', 'wp2print'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Purchased', 'wp2print'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Total', 'wp2print'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Payment', 'wp2print'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Date', 'wp2print'); ?></th>
					<th scope="col" class="manage-column" style="width:40px;"><?php _e('View', 'wp2print'); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php if ($aa_orders) {
					foreach($aa_orders as $aa_order) {
						$order_id = $aa_order->ID;
						$the_order = wc_get_order($order_id);
						$item_count = $the_order->get_item_count();
						?>
						<tr>
							<td><a href="?view=<?php echo $order_id; ?>">#<?php echo $order_id; ?></a></td>
							<td><?php echo $group_users_name[$aa_order->user_id]; ?></td>
							<td><?php echo $item_count; ?> <?php if ($item_count > 1) { _e('items', 'wp2print'); } else { _e('item', 'wp2print'); } ?></td>
							<td><?php echo $the_order->get_formatted_order_total(); ?></td>
							<td><?php echo $the_order->payment_method_title; ?></td>
							<td><?php echo date('M j, Y', strtotime($aa_order->post_date)); ?></td>
							<td><a href="?view=<?php echo $order_id; ?>" class="woocommerce-button button view"><?php _e('View', 'wp2print'); ?></a></td>
						</tr>
					<?php } ?>
				<?php } else { ?>
					<tr>
						<td colspan="7"><?php _e('No orders.', 'wp2print'); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
<?php } ?>