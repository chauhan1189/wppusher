<?php
$step = '1';
if (isset($_GET['step'])) { $step = $_GET['step']; }
$order_data = print_products_create_order_get_order_data();
if ($step != '1' && $step != 'completed' && empty($order_data)) {
	wp_redirect('admin.php?page=print-products-create-order');
	exit;
}
$print_products_send_quote_options = get_option("print_products_send_quote_options");
?>
<div class="wrap wp2print-create-order">
	<h2><?php _e('Create Order', 'wp2print'); ?></h2>
	<form method="POST" action="admin.php?page=print-products-create-order&step=<?php echo $step + 1; ?>" class="create-order-form" onsubmit="return create_order_process(<?php echo $step; ?>);" data-error-required="<?php _e('Please fill required field(s).', 'wp2print'); ?>" data-custom-product="<?php echo $print_products_send_quote_options['custom_product']; ?>" data-error-sure="<?php _e('Are you sure?', 'wp2print'); ?>">
		<input type="hidden" name="print_products_create_order_action" value="process">
		<div class="create-order-wrap">
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 1 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php if ($step == '1') { ?>
				<input type="hidden" name="process_step" value="1">
				<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 1: <?php _e('Select order customer', 'wp2print'); ?></div>
				<?php $wpusers = get_users();
				if ($wpusers) { ?>
					<p class="form-field">
						<label><?php _e('Customer', 'wp2print'); ?>: <span class="req">*</span></label>
						<select name="order_customer" class="order-customer">
							<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
							<?php foreach($wpusers as $wpuser) { ?>
								<option value="<?php echo $wpuser->ID; ?>"<?php if ($wpuser->ID == $order_data['customer']) { echo ' SELECTED'; } ?>><?php echo $wpuser->display_name; ?> (<?php echo $wpuser->user_email; ?>)</option>
							<?php } ?>
						</select>
					</p>
				<?php } ?>
				<p class="form-field">
					<input type="button" value="<?php _e('Create new user account', 'wp2print'); ?>" class="button co-add-user-btn">
				</p>
				<p class="submit"><input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary"></p>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 2 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == '2') {
				$customer_id = $order_data['customer'];
				if ($order_data['billing_address']) {
					$customer_billing_address = $order_data['billing_address'];
					$customer_shipping_address = $order_data['shipping_address'];
				} else {
					$customer_billing_address = print_products_create_order_get_customer_address($customer_id, 'billing');
					$customer_shipping_address = print_products_create_order_get_customer_address($customer_id, 'shipping');
				}
				$address_fields = array(
					'company' => array(
						'label' => __('Company', 'woocommerce').': <span class="req">*</span>',
						'type'  => 'text'
					),
					'address_1' => array(
						'label' => __('Address 1', 'woocommerce').': <span class="req">*</span>',
						'type'  => 'text'
					),
					'address_2' => array(
						'label' => __('Address 2', 'woocommerce').':',
						'type'  => 'text'
					),
					'city' => array(
						'label' => __('City', 'woocommerce').': <span class="req">*</span>',
						'type'  => 'text'
					),
					'postcode' => array(
						'label' => __('Postcode', 'woocommerce').': <span class="req">*</span>',
						'type'  => 'text'
					),
					'country' => array(
						'label'   => __('Country', 'woocommerce').': <span class="req">*</span>',
						'type'    => 'select',
						'style'   => 'width:95%;',
						'class'   => 'js_field-country select short',
						'options' => array('' => __('Select a country&hellip;', 'woocommerce') ) + WC()->countries->get_shipping_countries()
					),
					'state' => array(
						'label' => __('State', 'woocommerce').': <span class="req">*</span>',
						'class' => 'js_field-state select short',
						'type'  => 'text',
						'style' => 'width:95%;'
					)
				);
				?>
				<input type="hidden" name="process_step" value="2">
				<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 2: <?php _e('Customer billing and shipping address', 'wp2print'); ?></div>
				<table cellspacing="0" cellpadding="0" width="100%" class="co-addresses">
					<tr>
						<td valign="top" class="co-address">
							<div class="edit_address co-billing-address">
								<label><?php _e('Billing Address', 'wp2print'); ?></label>
								<?php foreach ($address_fields as $key => $field) {
									$field['id'] = 'billing_' . $key;
									$field['name'] = 'billing_address[' . $key . ']';
									$field['value'] = $customer_billing_address[$key];
									if ($field['type'] == 'select') {
										woocommerce_wp_select($field);
									} else {
										woocommerce_wp_text_input($field);
									}
									?>
								<?php } ?>
								<p class="form-field">
									<label><?php _e('Email', 'wp2print'); ?>: <span class="req">*</span></label>
									<input type="text" name="billing_address[email]" value="<?php echo $customer_billing_address['email']; ?>">
								</p>
								<p class="form-field">
									<label><?php _e('Phone', 'wp2print'); ?>: <span class="req">*</span></label>
									<input type="text" name="billing_address[phone]" value="<?php echo $customer_billing_address['phone']; ?>">
								</p>
							</div>
							<a href="#copy" class="copy-billing" onclick="return create_order_copy_billing();"><?php _e('Copy billing address to shipping', 'wp2print'); ?> >></a>
						</td>
						<td width="20">&nbsp;</td>
						<td valign="top" class="co-address">
							<div class="edit_address co-shipping-address">
								<label><?php _e('Shipping Address', 'wp2print'); ?></label>
								<?php foreach ($address_fields as $key => $field) {
									$field['id'] = 'shipping_' . $key;
									$field['name'] = 'shipping_address[' . $key . ']';
									$field['value'] = $customer_shipping_address[$key];
									if ($field['type'] == 'select') {
										woocommerce_wp_select($field);
									} else {
										woocommerce_wp_text_input($field);
									}
									?>
								<?php } ?>
							</div>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="button" value="<?php _e('Back', 'wp2print'); ?>" class="button" onclick="window.location.href='admin.php?page=print-products-create-order&step=<?php echo $step - 1; ?>';">
					<input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary">
				</p>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 3 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == '3') {
				if (isset($_GET['product_key'])) {
					$product_key = $_GET['product_key'];

					$product_data = $order_data['products'][$product_key];

					$product_id = (int)$product_data['product_id'];
					$product = wc_get_product($product_id);
					$product_type = print_products_get_type($product_id);

					$product_name = $product_data['name'];
					if (print_products_is_custom_product($product_id) && strlen($product_data['cptype'])) {
						$product_name = $product_data['cptype'];
					}
					?>
					<input type="hidden" name="process_step" value="3">
					<input type="hidden" name="product_action" value="attributes">
					<input type="hidden" name="product_key" value="<?php echo $product_key; ?>">
					<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 3: <?php _e('Select product attributes', 'wp2print'); ?></div>
					<p class="form-field">
						<label><?php _e('Product', 'wp2print'); ?>: <span><?php echo $product_name; ?></span></label>
					</p>
					<?php if (print_products_is_custom_product($product_id)) { ?>
						<?php include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-create-order-product-custom.php'; ?>
					<?php } else if (file_exists(PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-create-order-product-'.$product_type.'.php')) {
						include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-create-order-product-'.$product_type.'.php';
					} ?>
					<p class="submit" style="border-top:1px solid #C1C1C1;padding-top:20px;">
						<input type="button" value="<?php _e('Back', 'wp2print'); ?>" class="button" onclick="window.location.href='admin.php?page=print-products-create-order&step=<?php echo $step; ?>';">
						<input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary">
					</p>
				<?php } else {
					$products = $order_data['products'];
					$wooproducts = get_posts(array('post_type' => 'product', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'asc'));
					?>
					<input type="hidden" name="process_step" value="3">
					<input type="hidden" name="product_action" class="product-action">
					<input type="hidden" name="product_key" class="product-key">
					<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 3: <?php _e('Order Items', 'wp2print'); ?></div>
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
									<td><strong><?php echo $product['name']; ?></strong><?php print_products_create_order_product_data_html($product); ?></td>
									<td><?php echo $product['quantity']; ?></td>
									<td><?php echo wc_price($product['price']); ?></td>
									<td><a href="admin.php?page=print-products-create-order&step=3&product_key=<?php echo $product_key; ?>" class="fai-edit" title="<?php _e('Edit', 'wp2print'); ?>"><?php _e('Edit', 'wp2print'); ?></a> | <a href="#duplicate" onclick="return create_order_duplicate_product('<?php echo $product_key; ?>');" class="fai-duplicate" title="<?php _e('Duplicate', 'wp2print'); ?>"><?php _e('Duplicate', 'wp2print'); ?></a> | <a href="#delete" class="co-prod-delete fai-delete" onclick="return create_order_delete_product('<?php echo $product_key; ?>');" title="<?php _e('Delete', 'wp2print'); ?>"><?php _e('Delete', 'wp2print'); ?></a></td>
								</tr>
							<?php } ?>
						<?php } else { ?>
							<tr><td colspan="4"><?php _e('No selected products.', 'wp2print'); ?></td></tr>
						<?php } ?>
					</table>
					<?php if ($wooproducts) { ?>
						<p class="form-field" style="border-top:1px solid #C1C1C1;">
							<div style="padding-bottom:7px;"><?php _e('Add new product', 'wp2print'); ?>:</div>
							<select name="order_product" class="order-product" onchange="create_order_cproduct()">
								<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
								<?php foreach($wooproducts as $wooproduct) { ?>
									<option value="<?php echo $wooproduct->ID; ?>"><?php echo $wooproduct->post_title; ?></option>
								<?php } ?>
							</select>
						</p>
						<p class="form-field co-cproduct-type" style="display:none;">
							<label><?php _e('Custom product type', 'wp2print'); ?>:</label>
							<input type="text" name="cptype" class="cptype" value="<?php if (isset($order_data['product_data']['cptype'])) { echo $order_data['product_data']['cptype']; } ?>">
						</p>
						<p class="form-field"><a href="#add-new" class="button co-add-product" onclick="return create_order_add_product();" data-error="<?php _e('Please select product.', 'wp2print'); ?>"><?php _e('Add product', 'wp2print'); ?></a></p>
					<?php } ?>
					<p class="submit" style="border-top:1px solid #C1C1C1;padding-top:20px;">
						<input type="button" value="<?php _e('Back', 'wp2print'); ?>" class="button" onclick="window.location.href='admin.php?page=print-products-create-order&step=<?php echo $step - 1; ?>';">
						<?php if ($products) { ?><input type="submit" value="<?php _e('Continue', 'wp2print'); ?>" class="button button-primary"><?php } ?>
					</p>
				<?php } ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP 4 /////////////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == '4') {
				$customer_data = get_userdata($order_data['customer']);
				$products = $order_data['products'];

				$subtotal = 0;
				$tax_rate = 0;
				$tax_rate_id = 1;
				$customer_shipping_address = $order_data['shipping_address'];
				if (strlen($customer_shipping_address['country']) && strlen($customer_shipping_address['state'])) {
					$args = array(
						'country' => $customer_shipping_address['country'],
						'state' => $customer_shipping_address['state'],
						'city' => $customer_shipping_address['city'],
						'postcode' => $customer_shipping_address['postcode']
					);
					$tax_rates = WC_Tax::find_rates($args);
					if ($tax_rates) {
						$tax_rates_keys = array_keys($tax_rates);
						$tax_rate_id = $tax_rates_keys[0];
						$tax_rate = (float)$tax_rates[$tax_rate_id]['rate'];
					}
				}
				?>
				<input type="hidden" name="process_step" value="create">
				<div class="co-step-title"><?php _e('Step', 'wp2print'); ?> 4: <?php _e('Order Confirmation', 'wp2print'); ?></div>
				<div class="co-confirmation">
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td class="co-head"><?php _e('Customer', 'wp2print'); ?>:</td>
							<td class="co-value"><span class="co-edit"><a href="admin.php?page=print-products-create-order&step=1" class="fai-edit"><?php _e('edit', 'wp2print'); ?></a></span>
							<strong><?php echo $customer_data->display_name; ?> (<?php echo $customer_data->user_email; ?>)</strong></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Billing Address', 'wp2print'); ?>:</td>
							<td class="co-value"><span class="co-edit"><a href="admin.php?page=print-products-create-order&step=2" class="fai-edit"><?php _e('edit', 'wp2print'); ?></a></span>
							<strong><?php echo print_products_create_order_get_address_html($order_data['billing_address']); ?></strong></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Shipping Address', 'wp2print'); ?>:</td>
							<td class="co-value"><span class="co-edit"><a href="admin.php?page=print-products-create-order&step=2" class="fai-edit"><?php _e('edit', 'wp2print'); ?></a></span>
							<strong><?php echo print_products_create_order_get_address_html($order_data['shipping_address']); ?></strong></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Order Items', 'wp2print'); ?>:</td>
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
												<td><strong><?php echo $product_name; ?></strong><?php print_products_create_order_product_data_html($product); ?></td>
												<td><?php echo $product['quantity']; ?></td>
												<td><?php echo wc_price($product['price']); ?></td>
											</tr>
										<?php } ?>
									<?php } ?>
								</table>
								<span class="co-edit" style="padding-top:15px;"><a href="admin.php?page=print-products-create-order&step=3" class="fai-edit"><?php _e('edit', 'wp2print'); ?></a></span>
							</td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Subtotal', 'wp2print'); ?>:</td>
							<td class="co-value"><input type="text" name="price" class="p-price" value="<?php echo $subtotal; ?>" onblur="matrix_set_tax(); matrix_set_prices();"></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Tax', 'wp2print'); ?>:</td>
							<td class="co-value"><input type="text" name="tax" class="tax-price" value="<?php if ($order_data['tax']) { echo $order_data['tax']; } else { echo '0.00'; } ?>" data-rate="<?php echo $tax_rate; ?>" onblur="matrix_set_prices()"><input type="hidden" name="tax_rate_id" value="<?php echo $tax_rate_id; ?>"></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Shipping', 'wp2print'); ?>:</td>
							<td class="co-value"><input type="text" name="shipping" class="shipping-price" value="<?php if ($order_data['shipping']) { echo $order_data['shipping']; } else { echo '0.00'; } ?>" onblur="matrix_set_shipping_tax(); matrix_set_prices();"></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Shipping Tax', 'wp2print'); ?>:</td>
							<td class="co-value"><input type="text" name="shipping_tax" class="shipping-tax-price" value="<?php if ($order_data['shipping_tax']) { echo $order_data['shipping_tax']; } else { echo '0.00'; } ?>" onblur="matrix_set_prices()"></td>
						</tr>
						<tr>
							<td class="co-head"><?php _e('Total', 'wp2print'); ?>:</td>
							<td class="co-value"><input type="text" name="total" class="total-price" value="<?php if ($order_data['total']) { echo $order_data['total']; } ?>"></td>
						</tr>
					</table>
				</div>
				<p class="submit" style="text-align:center;">
					<input type="submit" value="<?php _e('Create Order', 'wp2print'); ?>" class="button button-primary button-create">
				</p>
				<script>jQuery(document).ready(function() { matrix_set_prices(); });</script>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php //////////////////////////////////////////////// STEP COMPLETED /////////////////////////////////////////////////// ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
			<?php } else if ($step == 'completed') {
				$order_id = $_GET['order'];
				$order = wc_get_order($order_id);
				$customer_id = $order->get_customer_id();
				$customer_data = get_userdata($customer_id);
				$order_items = $order->get_items('line_item');
				$shipping_tax = print_products_create_order_get_order_shipping_tax($order_id);
				?>
				<h3><?php _e('Order was successfully created.', 'wp2print'); ?></h3>
				<div class="co-order">
					<ul>
						<li><?php _e('Order ID', 'wp2print'); ?>: <span><a href="post.php?post=<?php echo $order_id; ?>&action=edit"><?php echo $order_id; ?></a></span></li>
						<li><?php _e('Customer', 'wp2print'); ?>: <span><?php echo $customer_data->display_name; ?> (<?php echo $customer_data->user_email; ?>)</span></li>
						<li><?php _e('Billing Address', 'wp2print'); ?>:<br /><span><?php echo print_products_create_order_get_address_html($order->get_address()); ?></span></li>
						<li><?php _e('Shipping Address', 'wp2print'); ?>:<br /><span><?php echo print_products_create_order_get_address_html($order->get_address('shipping')); ?></span></li>
						<li style="line-height:22px;">
								<table cellspacing="0" cellpadding="0">
									<tr>
										<td><?php _e('Product', 'wp2print'); ?></td>
										<td><?php _e('Quantity', 'wp2print'); ?></td>
										<td><?php _e('Subtotal', 'wp2print'); ?></td>
									</tr>
									<?php if ($order_items) { ?>
										<?php foreach($order_items as $item_id => $item) { ?>
											<tr>
												<td><strong><?php echo $item->get_name(); ?></strong><?php print_products_create_order_get_order_item_attributes($item_id); ?></td>
												<td><?php echo $item->get_quantity(); ?></td>
												<td><?php echo wc_price($item->get_total()); ?></td>
											</tr>
										<?php } ?>
									<?php } ?>
								</table>
						</li>
						<li style="line-height:25px;"><?php _e('Subtotal', 'wp2print'); ?>: <span><?php echo wc_price($order->get_subtotal()); ?></span><br>
						<?php _e('Tax', 'wp2print'); ?>: <span><?php echo wc_price($order->get_total_tax()); ?></span><br>
						<?php _e('Shipping', 'wp2print'); ?>: <span><?php echo wc_price($order->get_shipping_total()); ?></span><br>
						<?php _e('Shipping Tax', 'wp2print'); ?>: <span><?php echo wc_price($shipping_tax); ?></span><br>
						<?php _e('Total', 'wp2print'); ?>: <span><?php echo wc_price($order->get_total()); ?></span></li>
					</ul>
				</div>
			<?php } ?>
			<?php /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// ?>
		</div>
	</form>
</div>
<div style="display:none;">
	<div id="co-add-user" class="sq-add-user">
		<h2><?php _e('Create new user account', 'wp2print'); ?></h2>
		<form method="POST" class="co-add-user-form" onsubmit="return wp2print_coau_submit();" data-required="<?php _e('Please fill required fields.', 'wp2print'); ?>">
			<table class="form-table">
				<tr>
					<th><label><?php _e('Username', 'wp2print'); ?>:</label> <span style="color:#FF0000;">*</span></th>
					<td><input type="text" name="coau_username" class="coau-username"></td>
				</tr>
				<tr>
					<th><label><?php _e('Email', 'wp2print'); ?>:</label> <span style="color:#FF0000;">*</span></th>
					<td><input type="email" name="coau_email" class="coau-email"></td>
				</tr>
				<tr>
					<th><label><?php _e('First Name', 'wp2print'); ?>:</label></th>
					<td><input type="text" name="coau_fname" class="coau-fname"></td>
				</tr>
				<tr>
					<th><label><?php _e('Last Name', 'wp2print'); ?>:</label></th>
					<td><input type="text" name="coau_lname" class="coau-lname"></td>
				</tr>
				<tr>
					<th><label><?php _e('Password', 'wp2print'); ?>:</label> <span style="color:#FF0000;">*</span></th>
					<td><input type="text" name="coau_pass" class="coau-pass" value="<?php echo wp_generate_password(24); ?>"></td>
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