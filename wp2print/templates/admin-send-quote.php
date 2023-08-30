<?php
$step = '1';
if (isset($_GET['step'])) { $step = $_GET['step']; }
$send_quote_data = print_products_send_quote_get_order_data();
if ($step != '1' && $step != 'completed' && empty($send_quote_data)) {
	wp_redirect('admin.php?page=print-products-send-quote');
	exit;
}
$print_products_send_quote_options = get_option("print_products_send_quote_options");
?>
<div class="wrap wp2print-create-order">
	<h2><?php _e('Send Quote', 'wp2print'); ?></h2>
	<div style="float:right; margin:-35px 5px 0 0;"><a href="<?php if (current_user_can('manage_options')) { echo 'admin.php?'; } else { echo 'edit.php?post_type=shop_order&'; } ?>page=print-products-send-quote-history" class="button"><?php _e('Send Quote history', 'wp2print'); ?></a></div>
	<form method="POST" action="admin.php?page=print-products-send-quote&step=<?php echo $step + 1; ?>" class="send-quote-form" onsubmit="return send_quote_process(<?php echo $step; ?>);" data-error-required="<?php _e('Please fill required field(s).', 'wp2print'); ?>" data-confirm-wfiles="<?php _e('Do you want to continue without files?', 'wp2print'); ?>" data-custom-product="<?php echo $print_products_send_quote_options['custom_product']; ?>" data-error-sure="<?php _e('Are you sure?', 'wp2print'); ?>">
		<input type="hidden" name="print_products_send_quote_action" value="process">
		<div class="create-order-wrap">
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 1 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php if ($step == '1') { ?>
				<input type="hidden" name="process_step" value="1">
				<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 1: <?php _e('Select customer', 'wp2print'); ?></div>
				<?php $adminusers = get_users(array('role__in' => array('administrator', 'sales'))); ?>
				<p class="form-field">
					<label><?php _e('Sender', 'wp2print'); ?>:</label>
					<select name="order_sender" class="order-sender">
						<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
						<?php foreach($adminusers as $adminuser) { ?>
							<option value="<?php echo $adminuser->user_email; ?>"<?php if ($adminuser->user_email == $send_quote_data['sender']) { echo ' SELECTED'; } ?>><?php echo $adminuser->display_name; ?> (<?php echo $adminuser->user_email; ?>)</option>
						<?php } ?>
					</select>
				</p>
				<?php $wpusers = get_users(array('orderby' => 'display_name', 'order' => 'asc'));
				if ($wpusers) { ?>
					<p class="form-field">
						<label><?php _e('Customer', 'wp2print'); ?>: <span class="req">*</span></label>
						<select name="order_customer" class="order-customer">
							<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
							<?php foreach($wpusers as $wpuser) {
								$first_name = get_user_meta($wpuser->ID, 'first_name', true);
								$last_name = get_user_meta($wpuser->ID, 'last_name', true);
								$billing_company = get_user_meta($wpuser->ID, 'billing_company', true);
								$name = $wpuser->display_name;
								if (strlen($first_name)) {
									$name = $first_name.' '.$last_name;
								}
								$company = '';
								if (strlen($billing_company)) {
									$company = '; '.__('Company', 'wp2print').': '.$billing_company;
								} ?>
								<option value="<?php echo $wpuser->ID; ?>"<?php if ($wpuser->ID == $send_quote_data['customer']) { echo ' SELECTED'; } ?>><?php echo $name; ?> (<?php _e('Email', 'wp2print'); ?>: <?php echo $wpuser->user_email; ?><?php echo $company; ?>)</option>
							<?php } ?>
						</select>
					</p>
				<?php } ?>
				<p class="form-field">
					<input type="button" value="<?php _e('Create new user account', 'wp2print'); ?>" class="button sq-add-user-btn">
				</p>
				<p class="submit"><input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary"></p>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 2 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == '2') {
				if (isset($_GET['product_key'])) {
					$product_key = $_GET['product_key'];

					$product_data = $send_quote_data['products'][$product_key];

					$product_id = (int)$product_data['product_id'];
					$product = wc_get_product($product_id);
					$product_type = print_products_get_type($product_id);
					$artwork_source = get_post_meta($product_id, '_artwork_source', true);

					$product_name = $product_data['name'];
					if (print_products_is_custom_product($product_id) && strlen($product_data['cptype'])) {
						$product_name = $product_data['cptype'];
					}
					?>
					<input type="hidden" name="process_step" value="2">
					<input type="hidden" name="product_action" class="product-action" value="attributes">
					<input type="hidden" name="product_key" value="<?php echo $product_key; ?>">
					<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 2: <?php _e('Select product attributes', 'wp2print'); ?></div>
					<p class="form-field">
						<label><?php _e('Product', 'wp2print'); ?>: <span><?php echo $product_name; ?></span></label>
					</p>
					<?php if (print_products_is_custom_product($product_id)) { ?>
						<?php include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-send-quote-product-custom.php'; ?>
					<?php } else { ?>
						<?php if (file_exists(PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-send-quote-product-'.$product_type.'.php')) {
							include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-send-quote-product-'.$product_type.'.php';
						} ?>
						<?php if ($artwork_source != '') { ?>
							<?php include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-send-quote-upload.php'; ?>
						<?php } ?>
						<p class="form-field">
							<label><?php _e('Subtotal', 'wp2print'); ?>: <span class="t-price"><?php if (isset($product_data['price']) && $product_data['price']) { echo wc_price($product_data['price']); } else { echo wc_price($product->get_price()); } ?></span></label>
							<input type="hidden" name="price" class="p-price" value="<?php if (isset($product_data['price']) && $product_data['price']) { echo $product_data['price']; } else { echo $product->get_price(); } ?>">
						</p>
					<?php } ?>
					<p class="submit" style="border-top:1px solid #C1C1C1;padding-top:20px;">
						<input type="button" value="<?php _e('Back', 'wp2print'); ?>" class="button" onclick="window.location.href='admin.php?page=print-products-send-quote&step=<?php echo $step; ?>';">
						<input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary">
					</p>
				<?php } else {
					$products = $send_quote_data['products'];
					$wooproducts = get_posts(array('post_type' => 'product', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'asc'));
					?>
					<input type="hidden" name="process_step" value="2">
					<input type="hidden" name="product_action" class="product-action">
					<input type="hidden" name="product_key" class="product-key">
					<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 2: <?php _e('Quote products', 'wp2print'); ?></div>
					<table width="100%" cellspacing="0" cellpadding="0" class="co-order-products">
						<tr>
							<th style="text-align:left;"><?php _e('Product', 'wp2print'); ?></th>
							<th style="text-align:left;"><?php _e('Quantity', 'wp2print'); ?></th>
							<th style="text-align:left;"><?php _e('Subtotal', 'wp2print'); ?></th>
							<th style="text-align:left;"><?php _e('Actions', 'wp2print'); ?></th>
						</tr>
						<?php if ($products) { ?>
							<?php foreach ($products as $product_key => $product) { ?>
								<tr>
									<td><strong><?php echo $product['name']; ?></strong><?php print_products_send_quote_product_data_html($product); ?></td>
									<td><?php echo $product['quantity']; ?></td>
									<td><?php echo wc_price($product['price']); ?></td>
									<td><a href="admin.php?page=print-products-send-quote&step=2&product_key=<?php echo $product_key; ?>" class="fai-edit" title="<?php _e('Edit', 'wp2print'); ?>"><?php _e('Edit', 'wp2print'); ?></a> | <a href="#duplicate" onclick="return send_quote_duplicate_product('<?php echo $product_key; ?>');" class="fai-duplicate" title="<?php _e('Duplicate', 'wp2print'); ?>"><?php _e('Duplicate', 'wp2print'); ?></a> | <a href="#delete" class="co-prod-delete fai-delete" onclick="return send_quote_delete_product('<?php echo $product_key; ?>');" title="<?php _e('Delete', 'wp2print'); ?>"><?php _e('Delete', 'wp2print'); ?></a></td>
								</tr>
							<?php } ?>
						<?php } else { ?>
							<tr><td colspan="4"><?php _e('No selected products.', 'wp2print'); ?></td></tr>
						<?php } ?>
					</table>
					<?php if ($wooproducts) { ?>
						<p class="form-field" style="border-top:1px solid #C1C1C1;">
							<div style="padding-bottom:7px;"><?php _e('Add new product', 'wp2print'); ?>:</div>
							<select name="order_product" class="order-product" onchange="send_quote_cproduct()">
								<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
								<?php foreach($wooproducts as $wooproduct) {
									$product_type = print_products_get_type($wooproduct->ID);
									if (in_array($product_type, array('fixed', 'book', 'area', 'simple', 'variable'))) { ?>
										<option value="<?php echo $wooproduct->ID; ?>"><?php echo $wooproduct->post_title; ?></option>
									<?php } ?>
								<?php } ?>
							</select>
						</p>
						<p class="form-field sq-cproduct-type" style="display:none;">
							<label><?php _e('Custom product type', 'wp2print'); ?>:</label>
							<input type="text" name="cptype" class="cptype">
						</p>
						<p class="form-field"><a href="#add-new" class="button co-add-product" onclick="return send_quote_add_product();" data-error="<?php _e('Please select product.', 'wp2print'); ?>"><?php _e('Add product', 'wp2print'); ?></a></p>
					<?php } ?>
					<p class="submit" style="border-top:1px solid #C1C1C1;padding-top:20px;">
						<input type="button" value="<?php _e('Back', 'wp2print'); ?>" class="button" onclick="window.location.href='admin.php?page=print-products-send-quote&step=<?php echo $step - 1; ?>';">
						<?php if ($products) { ?><input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary"><?php } ?>
					</p>
				<?php } ?>
				<script>
				function matrix_html_price(price) {
					var price_decimals = <?php echo wc_get_price_decimals(); ?>;
					var currency_symbol = '<?php echo get_woocommerce_currency_symbol(); ?>';
					var currency_pos = '<?php echo get_option('woocommerce_currency_pos'); ?>';
					var fprice = matrix_format_price(price.toFixed(price_decimals));
					if (currency_pos == 'left') {
						return currency_symbol + fprice;
					} else if (currency_pos == 'right') {
						return fprice + currency_symbol;
					} else if (currency_pos == 'left_space') {
						return currency_symbol + ' ' + fprice;
					} else if (currency_pos == 'right_space') {
						return fprice + ' ' + currency_symbol;
					}
				}

				function matrix_format_price(p) {
					var decimal_sep = '<?php echo wc_get_price_decimal_separator(); ?>';
					var thousand_sep = '<?php echo wc_get_price_thousand_separator(); ?>';
					var pparts = p.toString().split('.');
					pparts[0] = pparts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand_sep);
					return pparts.join(decimal_sep);
				}
				</script>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 3 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == '3') {
				$subtotal = 0;
				$print_products_send_quote_options = get_option("print_products_send_quote_options");
				$customer_data = get_userdata($send_quote_data['customer']);
				$products = $send_quote_data['products'];
				$quote_period = (int)$print_products_send_quote_options['quote_period'];
				$expire_date = '';
				if ($quote_period) {
					$expire_date = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $quote_period, date('Y')));
				} ?>
				<input type="hidden" name="process_step" value="send">
				<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 3: <?php _e('Quote Confirmation', 'wp2print'); ?></div>
				<div class="co-confirmation">
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td class="co-head" style="width:20%;"><?php _e('Customer', 'wp2print'); ?>:</td>
							<td class="co-value"><span class="co-edit"><a href="admin.php?page=print-products-send-quote&step=1" class="fai-edit"><?php _e('edit', 'wp2print'); ?></a></span>
							<strong><?php echo $customer_data->display_name; ?> (<?php echo $customer_data->user_email; ?>)</strong></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Products', 'wp2print'); ?>:</td>
							<td class="co-value">
								<table cellspacing="0" cellpadding="0" width="100%">
									<tr>
										<td><?php _e('Product', 'wp2print'); ?></td>
										<td><?php _e('Quantity', 'wp2print'); ?></td>
										<td><?php _e('Subtotal', 'wp2print'); ?></td>
									</tr>
									<?php if ($products) { ?>
										<?php foreach ($products as $product) {
											$product_id = $product['product_id'];
											$product_name = $product['name'];
											if (print_products_is_custom_product($product_id) && strlen($product['cptype'])) {
												$product_name = $product['cptype'];
											}
											if ($product['price']) { $subtotal = $subtotal + $product['price']; }
											?>
											<tr>
												<td><strong><?php echo $product_name; ?></strong><?php print_products_send_quote_product_data_html($product); ?></td>
												<td><?php echo $product['quantity']; ?></td>
												<td><?php echo wc_price($product['price']); ?></td>
											</tr>
										<?php } ?>
									<?php } ?>
									<tr>
										<td colspan="2">&nbsp;</td>
										<td><?php echo wc_price($subtotal); ?></td>
									</tr>
								</table>
							<span class="co-edit" style="padding-top:15px;"><a href="admin.php?page=print-products-send-quote&step=2" class="fai-edit"><?php _e('edit', 'wp2print'); ?></a></span></td>
						</tr>
						<tr>
							<td class="co-head" style="line-height:25px;"><?php _e('Expiration date', 'wp2print'); ?>:</td>
							<td class="co-value" style="line-height:25px;"><input type="text" name="expire_date" value="<?php echo $expire_date; ?>" class="pp-sq-edate" style="width:100%;"></td>
						</tr>
						<tr>
							<td class="co-head" style="line-height:25px;"><?php _e('Email Options', 'wp2print'); ?>:</td>
							<td class="co-value" style="line-height:25px;">
								<input type="text" name="email_subject" value="<?php echo $print_products_send_quote_options['email_subject']; ?>" style="width:100%;">
								<textarea name="email_message" style="width:100%; height:200px;"><?php echo $print_products_send_quote_options['email_message']; ?></textarea>
							</td>
						</tr>
					</table>
				</div>
				<p class="submit" style="text-align:center;">
					<input type="submit" value="<?php _e('Send Quote', 'wp2print'); ?>" class="button button-primary button-create">
				</p>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP COMPLETED /////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == 'completed') {
				$subtotal = 0;
				$order_id = $_GET['order'];
				$order = print_products_send_quote_get_order($order_id);
				$customer_id = $order->user_id;
				$customer_data = get_userdata($customer_id);
				$order_items = print_products_send_quote_get_order_items($order_id);
				?>
				<h3 style="margin-top:0px;"><?php _e('Quote was successfully sent.', 'wp2print'); ?></h3>
				<div class="sq-order">
					<div class="sq-o-line"><?php _e('Customer', 'wp2print'); ?>: <strong><?php echo $customer_data->display_name; ?> (<?php echo $customer_data->user_email; ?>)</strong></div>
					<div class="sq-o-line">
						<table cellspacing="0" cellpadding="0">
							<tr>
								<td><?php _e('Product', 'wp2print'); ?></td>
								<td><?php _e('Quantity', 'wp2print'); ?></td>
								<td><?php _e('Subtotal', 'wp2print'); ?></td>
							</tr>
							<?php if ($order_items) { ?>
								<?php foreach ($order_items as $order_item) {
									$product_id = $order_item->product_id;
									$product = wc_get_product($product_id);
									$quantity = $order_item->quantity;
									$product_price = $order_item->price;
									$additional = unserialize($order_item->additional);

									$product_name = $product->get_name();
									if (print_products_is_custom_product($product_id) && isset($additional['cptype']) && strlen($additional['cptype'])) {
										$product_name = $additional['cptype'];
									}
									if ($product_price) { $subtotal = $subtotal + $product_price; }
									?>
									<tr>
										<td><strong><?php echo $product_name; ?></strong><?php print_products_send_quote_product_data_html($order_item); ?></td>
										<td><?php echo $quantity; ?></td>
										<td><?php echo wc_price($product_price); ?></td>
									</tr>
								<?php } ?>
							<?php } ?>
							<tr>
								<td colspan="2">&nbsp;</td>
								<td><?php echo wc_price($subtotal); ?></td>
							</tr>
						</table>
					</div>
					<?php if (strlen($order->expire_date)) { ?>
						<div class="sq-o-line"><?php _e('Expiration date', 'wp2print'); ?>: <strong><?php echo $order->expire_date; ?></strong></div>
					<?php } ?>
				</div>
			<?php } ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
		</div>
	</form>
</div>
<div style="display:none;">
	<div id="sq-add-user" class="sq-add-user">
		<h2><?php _e('Create new user account', 'wp2print'); ?></h2>
		<form method="POST" class="sq-add-user-form" onsubmit="return wp2print_sqau_submit();" data-required="<?php _e('Please fill required fields.', 'wp2print'); ?>">
			<table class="form-table">
				<tr>
					<th><label><?php _e('Username', 'wp2print'); ?>:</label> <span style="color:#FF0000;">*</span></th>
					<td><input type="text" name="sqau_username" class="sqau-username"></td>
				</tr>
				<tr>
					<th><label><?php _e('Email', 'wp2print'); ?>:</label> <span style="color:#FF0000;">*</span></th>
					<td><input type="email" name="sqau_email" class="sqau-email"></td>
				</tr>
				<tr>
					<th><label><?php _e('First Name', 'wp2print'); ?>:</label></th>
					<td><input type="text" name="sqau_fname" class="sqau-fname"></td>
				</tr>
				<tr>
					<th><label><?php _e('Last Name', 'wp2print'); ?>:</label></th>
					<td><input type="text" name="sqau_lname" class="sqau-lname"></td>
				</tr>
				<tr>
					<th><label><?php _e('Password', 'wp2print'); ?>:</label> <span style="color:#FF0000;">*</span></th>
					<td><input type="text" name="sqau_pass" class="sqau-pass" value="<?php echo wp_generate_password(24); ?>"></td>
				</tr>
			</table>
			<div class="sq-add-user-error"></div>
			<p class="submit">
				<input type="submit" value="<?php _e('Add New User', 'wp2print'); ?>" class="button button-primary">
			</p>
			<img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/ajax-loading.gif" class="sq-add-user-loading">
		</form>
	</div>
</div>