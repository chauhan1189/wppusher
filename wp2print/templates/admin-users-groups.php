<?php
$action = '';
$group_id = 0;

if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['group_id'])) { $group_id = $_GET['group_id']; }

$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_name", $wpdb->prefix));
$total_groups = count($users_groups);
?>
<div class="wrap users-groups-wrap">
	<?php if ($action == 'add' || $action == 'edit') {
		$ptitle = 'Add Group';
		$button = 'Submit';
		$theme = array();
		$categories = array();
		$products = array();
		$users = array();
		$superusers = array();
		$options = array();
		$payment_method = array();
		$billing_addresses = array();
		$shipping_addresses = array();

		if ($action == 'edit' && $group_id) {
			$ptitle = 'Edit Group';
			$button = 'Update';

			$group_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_users_groups WHERE group_id = %s", $wpdb->prefix, $group_id));
			if ($group_data) {
				$group_name = $group_data->group_name;
				$use_printshop = $group_data->use_printshop;
				$use_privatestore = $group_data->use_privatestore;
				$theme = unserialize($group_data->theme);
				$categories = unserialize($group_data->categories);
				$products = unserialize($group_data->products);
				$payment_method = $group_data->payment_method;
				$invoice_zero = $group_data->invoice_zero;
				$free_shipping = $group_data->free_shipping;
				$shipping_rate = $group_data->shipping_rate;
				$tax_rate = $group_data->tax_rate;
				$login_code_required = $group_data->login_code_required;
				$login_code = $group_data->login_code;
				$login_redirect = $group_data->login_redirect;
				$logout_redirect = $group_data->logout_redirect;
				$order_emails = $group_data->order_emails;
				$tax_id = $group_data->tax_id;
				$accounting_id = $group_data->accounting_id;
				$orders_approving = $group_data->orders_approving;
				$orders_approving_amount = $group_data->orders_approving_amount;
				$assign_new_user = $group_data->assign_new_user;
				$aregister_domain = $group_data->aregister_domain;
				$orders_email_contents = unserialize($group_data->orders_email_contents);
				$options = unserialize($group_data->options);
				$billing_addresses = unserialize($group_data->billing_addresses);
				$shipping_addresses = unserialize($group_data->shipping_addresses);
				$allow_modify_pdf = $group_data->allow_modify_pdf;

				if (strlen($payment_method)) {
					$payment_method = explode(';', $payment_method);
				}
				if (!is_array($payment_method)) { $payment_method = array(); }

				if (!is_array($theme)) { $theme = array(); }
				if (!is_array($categories)) { $categories = array(); }
				if (!is_array($products)) { $products = array(); }
			}
			$group_users = get_users(array(
				'meta_key'     => '_user_group',
				'meta_value'   => $group_id
			));
			if ($group_users) {
				foreach($group_users as $group_user) {
					$users[] = $group_user->ID;
				}
			}
			$superusers = print_products_users_groups_get_group_superusers($group_id);
		}
		if (!$orders_email_contents) {
			$oaa_link = print_products_get_my_account_custom_page_url('orders-awaiting-approval');
			$orders_email_contents = array(
				'email_subject_order_approval' => 'New order awaiting your approval',
				'email_message_order_approval' => 'There is a new order from the '.get_bloginfo('name').' awaiting your approval. Please visit the website and give your approval to begin production:'.chr(10).chr(10).$oaa_link,
				'email_subject_order_rejection' => 'There is a problem with your order',
				'email_message_order_rejection' => 'We are not able to proceed with your order [ORDERID]. Your order was not approved for production for the following reason:'.chr(10).chr(10).'[COMMENTS]'.chr(10).chr(10).'Please return to the website and place a new order.'
			);
		}
		// woo data
		$woo_categories = get_terms('product_cat', 'hide_empty=0');
		$woo_products = get_posts('post_type=product&posts_per_page=-1&orderby=title&order=asc');
		$available_gateways = array();
		$available_shippings = array();
		if (class_exists('WooCommerce')) {
			$pgateways = new WC_Payment_Gateways();
			$available_gateways = $pgateways->payment_gateways();

			$wcshipping = new WC_Shipping();
			$available_shippings = $wcshipping->get_shipping_methods();
		}
		$slide_groups = get_terms('slide-page', 'hide_empty=0');
		$woocommerce_currency = get_woocommerce_currency_symbol();

		// wp data
		$wp_users = get_users();
		$wp_menus = wp_get_nav_menus();
		$wp_themes = wp_get_themes();

		// wp themes
		$currtheme = get_option('stylesheet');
		$printshop_exists = false;
		$privatestore_exists = false;
		if (array_key_exists('printshop', $wp_themes)) { $printshop_exists = true; }
		if (array_key_exists('private-store', $wp_themes)) { $privatestore_exists = true; }

		// groups products and categories
		$products_groups = array();
		if ($users_groups) {
			foreach($users_groups as $users_group) {
				if ($users_group->group_id != $group_id) {
					$g_name = $users_group->group_name;
					$g_products = unserialize($users_group->products);

					if ($g_products) {
						foreach($g_products as $pid) {
							$products_groups[$pid][] = $g_name;
						}
					}
				}
			}
		}
		?>
		<h1><?php _e($ptitle, 'wp2print'); ?></h2>
		<form method="POST" action="users.php?page=print-products-users-groups" class="users-groups-form" onsubmit="return users_groups_form_submit();">
			<input type="hidden" name="print_products_users_groups_action" value="true">
			<input type="hidden" name="action" value="<?php echo $action; ?>">
			<input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
			<table>
				<tr>
					<td class="label" style="width:280px;"><?php _e('Group Name', 'wp2print'); ?>:</td>
					<td><input type="text" name="group_name" value="<?php echo $group_name; ?>" class="group-name"></td>
				</tr>
				<?php if ($printshop_exists) { ?>
					<tr>
						<td class="label"><?php _e('Use theme Printshop for Group', 'wp2print'); ?>:</td>
						<td><input type="checkbox" name="use_printshop" value="1" class="use-printshop" onclick="wp2print_group_use_printshop();"<?php if ($use_printshop == 1) { echo ' CHECKED'; } ?>></td>
					</tr>
				<?php } ?>
				<?php if ($privatestore_exists) { ?>
					<tr>
						<td class="label"><?php _e('Use theme Private Store for Group', 'wp2print'); ?>:</td>
						<td><input type="checkbox" name="use_privatestore" value="1" class="use-privatestore" onclick="wp2print_group_use_privatestore();"<?php if ($use_privatestore == 1) { echo ' CHECKED'; } ?>></td>
					</tr>
				<?php } ?>
				<?php if ($printshop_exists || $privatestore_exists || $currtheme == 'ascend_premium') { ?>
					<tr class="printshop-theme-layout printshop-theme-logo">
						<td class="label"><?php _e('Theme Logo', 'wp2print'); ?>:</td>
						<td style="padding-bottom:10px;">
							<div class="logo-image"><?php if ($theme['logo']) { echo '<img src="'.$theme['logo'].'" style="max-width:280px;margin-bottom:10px;">'; } ?></div>
							<input class="button" type="button" value="<?php _e('Upload', 'wp2print'); ?>" onclick="open_media_uploader_image()" />
							<?php if ($theme['logo']) { ?><input class="button delete-theme-logo" type="button" value="<?php _e('Delete', 'wp2print'); ?>" data-confirm="<?php _e('Are you sure?', 'wp2print'); ?>" /><?php } ?>
							<input type="hidden" name="theme[logo]" value="<?php echo $theme['logo']; ?>" class="theme-logo">
						</td>
					</tr>
				<?php } ?>
				<?php if ($printshop_exists) { ?>
					<tr class="printshop-theme-layout printshop-theme-settings"<?php if ($use_printshop != 1) { echo ' style="display:none;"'; } ?>>
						<td class="label"><?php _e('Theme Settings', 'wp2print'); ?>:</td>
						<td>
							<table class="thm-settings">
								<tr>
									<td style="width:180px;"><?php _e('Site Background', 'wp2print'); ?>:</td>
									<td style="padding-top:5px;"><input type="text" name="theme[background]" value="<?php echo $theme['background']; ?>" class="theme-background"></td>
								</tr>
								<tr>
									<td><?php _e('Top bar background color', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[topbar]" value="<?php echo $theme['topbar']; ?>" class="theme-topbar"></td>
								</tr>
								<tr>
									<td><?php _e('Logo and menu background', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[logomenu]" value="<?php echo $theme['logomenu']; ?>" class="theme-logomenu"></td>
								</tr>
								<tr>
									<td><?php _e('Footer background color', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[footer]" value="<?php echo $theme['footer']; ?>" class="theme-footer"></td>
								</tr>
								<tr>
									<td><?php _e('Content Background', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[content]" value="<?php echo $theme['content']; ?>" class="theme-content"></td>
								</tr>
								<tr>
									<td><?php _e('Products background', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[prodbg]" value="<?php echo $theme['prodbg']; ?>" class="theme-prodbg"></td>
								</tr>
								<tr>
									<td><?php _e('Products text color', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[prodtext]" value="<?php echo $theme['prodtext']; ?>" class="theme-prodtext"></td>
								</tr>
								<tr>
									<td><?php _e('Buttons CSS class', 'wp2print'); ?>:&nbsp;</td>
									<td style="padding-top:5px;"><input type="text" name="theme[butclass]" value="<?php echo substr($theme['butclass'], 0, 50); ?>" class="theme-butclass" style="width:200px;"></td>
								</tr>
							</table>
						</td>
					</tr>
				<?php } ?>
				<?php if ($printshop_exists || $privatestore_exists) { ?>
					<tr class="printshop-theme-layout printshop-theme-menus"<?php if ($use_printshop != 1 && $use_privatestore != 1) { echo ' style="display:none;"'; } ?>>
						<td class="label"><?php _e('Theme Menus', 'wp2print'); ?>:</td>
						<td>
							<table class="thm-settings">
								<tr>
									<td style="width:180px;"><?php _e('Primary menu position', 'wp2print'); ?>:&nbsp;</td>
									<td><select name="theme[primarymenu]">
										<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
										<?php if ($wp_menus) { ?>
											<?php foreach($wp_menus as $wp_menu) { ?>
												<option value="<?php echo $wp_menu->slug; ?>"<?php if ($theme['primarymenu'] == $wp_menu->slug) { echo ' SELECTED'; } ?>><?php echo $wp_menu->name; ?></option>
											<?php } ?>
										<?php } ?>
									</select></td>
								</tr>
								<tr>
									<td><?php _e('Secondary menu position', 'wp2print'); ?>:&nbsp;</td>
									<td><select name="theme[secondarymenu]">
										<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
										<?php if ($wp_menus) { ?>
											<?php foreach($wp_menus as $wp_menu) { ?>
												<option value="<?php echo $wp_menu->slug; ?>"<?php if ($theme['secondarymenu'] == $wp_menu->slug) { echo ' SELECTED'; } ?>><?php echo $wp_menu->name; ?></option>
											<?php } ?>
										<?php } ?>
									</select></td>
								</tr>
								<tr>
									<td><?php _e('Mobile menu position', 'wp2print'); ?>:&nbsp;</td>
									<td><select name="theme[mobilemenu]">
										<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
										<?php if ($wp_menus) { ?>
											<?php foreach($wp_menus as $wp_menu) { ?>
												<option value="<?php echo $wp_menu->slug; ?>"<?php if ($theme['mobilemenu'] == $wp_menu->slug) { echo ' SELECTED'; } ?>><?php echo $wp_menu->name; ?></option>
											<?php } ?>
										<?php } ?>
									</select></td>
								</tr>
							</table>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<td class="label"><?php _e('Visible Categories', 'wp2print'); ?>:</td>
					<td>
						<div class="chbox-list">
							<?php if ($woo_categories) {
								foreach($woo_categories as $woo_category) { ?>
									<input type="checkbox" name="categories[]" value="<?php echo $woo_category->term_id; ?>"<?php if (in_array($woo_category->term_id, $categories)) { echo ' CHECKED'; } ?>><?php echo $woo_category->name; ?><br />
								<?php } ?>
							<?php } ?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Include private categories in listing of all categories', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="options[display_categories]" value="1"<?php if ($options['display_categories']) { echo ' CHECKED'; } ?>>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Visible Products', 'wp2print'); ?>:</td>
					<td>
						<div class="chbox-list">
							<?php if ($woo_products) {
								foreach($woo_products as $woo_product) { ?>
									<input type="checkbox" name="products[]" value="<?php echo $woo_product->ID; ?>"<?php if (in_array($woo_product->ID, $products)) { echo ' CHECKED'; } ?>><?php echo $woo_product->post_title; ?>
									<?php if (isset($products_groups[$woo_product->ID])) { echo ' - <font style="color:#0073aa;">'.implode(', ', $products_groups[$woo_product->ID]).'</font>'; } ?><br />
								<?php } ?>
							<?php } ?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Include private products in listing of all products', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="options[display_products]" value="1"<?php if ($options['display_products']) { echo ' CHECKED'; } ?>>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Display public products to group members', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="options[display_public_products]" value="1"<?php if ($options['display_public_products']) { echo ' CHECKED'; } ?>>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Payment method', 'wp2print'); ?>:</td>
					<td>
						<div class="chbox-list">
							<?php foreach($available_gateways as $pm_key => $pm_val) { ?>
								<input type="checkbox" name="payment_method[]" value="<?php echo $pm_key; ?>"<?php if (in_array($pm_key, $payment_method)) { echo ' CHECKED'; } ?>><?php echo $pm_val->title; ?><br />
							<?php } ?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Use Invoice payment method of zero-value orders', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="invoice_zero" value="1"<?php if ($invoice_zero) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Free Shipping', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="free_shipping" value="1"<?php if ($free_shipping) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Shipping amount', 'wp2print'); ?>:<div style="float:right; margin-right:-3px;"><?php echo $woocommerce_currency; ?></div></td>
					<td><input type="text" name="shipping_rate" value="<?php echo $shipping_rate; ?>" style="width:100px;"> <?php _e('Flat Rate Shipping', 'wp2print'); ?></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Tax rate', 'wp2print'); ?>:</td>
					<td><input type="text" name="tax_rate" value="<?php echo $tax_rate; ?>" style="width:100px;"> %</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Login code required for login', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="login_code_required" value="1"<?php if ($login_code_required) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Login code', 'wp2print'); ?>:</td>
					<td><input type="text" name="login_code" value="<?php echo $login_code; ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Homepage URL for group', 'wp2print'); ?>:</td>
					<td><input type="text" name="theme[homeurl]" value="<?php echo $theme['homeurl']; ?>" class="group-homepage"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Landing page after login', 'wp2print'); ?>:</td>
					<td><input type="text" name="login_redirect" value="<?php echo $login_redirect; ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Landing page after logout', 'wp2print'); ?>:&nbsp;</td>
					<td><input type="text" name="logout_redirect" value="<?php echo $logout_redirect; ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Email address list', 'wp2print'); ?>:</td>
					<td><textarea name="order_emails"><?php echo $order_emails; ?></textarea></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Tax ID', 'wp2print'); ?>:</td>
					<td><input type="text" name="tax_id" value="<?php echo $tax_id; ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Orders Approving', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="orders_approving" value="1"<?php if ($orders_approving) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Order value threshold for approval', 'wp2print'); ?>:<div style="float:right; margin-right:-3px;"><?php echo $woocommerce_currency; ?></td>
					<td><input type="text" name="orders_approving_amount" value="<?php echo $orders_approving_amount; ?>" style="width:100px;"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Assign all new user to group', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="assign_new_user" value="1"<?php if ($assign_new_user) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Autoregister domain name', 'wp2print'); ?>:</td>
					<td><input type="text" name="aregister_domain" value="<?php echo $aregister_domain; ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Group Accounting ID', 'wp2print'); ?>:</td>
					<td><input type="text" name="accounting_id" value="<?php echo $accounting_id; ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Printers Plan Customer ID', 'wp2print'); ?>:</td>
					<td><input type="text" name="options[printers_plan_customer_id]" value="<?php if (isset($options['printers_plan_customer_id'])) { echo $options['printers_plan_customer_id']; } ?>"></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Group Users', 'wp2print'); ?>:</td>
					<td>
						<div class="group-users-container">
							<div class="gu-right"><?php _e('Superuser', 'wp2print'); ?></div>
							<div class="gu-left"><?php _e('Username', 'wp2print'); ?></div>
							<div class="chbox-list" style="height:125px;">
								<?php if ($wp_users) {
									foreach($wp_users as $wp_user) { ?>
										<div style="width:30px;float:right;text-align:right;"><input type="checkbox" name="superusers[]" value="<?php echo $wp_user->ID; ?>"<?php if (in_array($wp_user->ID, $superusers)) { echo ' CHECKED'; } ?>></div>
										<input type="checkbox" name="users[]" value="<?php echo $wp_user->ID; ?>"<?php if (in_array($wp_user->ID, $users)) { echo ' CHECKED'; } ?>><?php echo $wp_user->user_login.' ('.$wp_user->display_name.')'; ?><br />
									<?php } ?>
								<?php } ?>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Allow superusers to modify Designer PDF files', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="allow_modify_pdf" value="1"<?php if ($allow_modify_pdf) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label"><?php _e('Do not display coupon field during checkout', 'wp2print'); ?>:</td>
					<td><input type="checkbox" name="options[hide_coupon_field]" value="1"<?php if ($options['hide_coupon_field'] == 1) { echo ' CHECKED'; } ?>></td>
				</tr>
				<tr>
					<td class="label" valign="top"><?php _e('Orders Email Contents', 'wp2print'); ?>:</td>
					<td class="orders-email-contents">
						<table width="100%">
							<tr>
								<td><?php _e('Email title for mail to superuser for new order pending approval', 'wp2print'); ?>:</td>
							</tr>
							<tr>
								<td class="padbot"><input type="text" name="orders_email_contents[email_subject_order_approval]" value="<?php echo $orders_email_contents['email_subject_order_approval']; ?>"></td>
							</tr>
							<tr>
								<td><?php _e('Email body for mail to superuser for new order pending approval', 'wp2print'); ?>:</td>
							</tr>
							<tr>
								<td><textarea name="orders_email_contents[email_message_order_approval]" style="height:"><?php echo $orders_email_contents['email_message_order_approval']; ?></textarea></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td><?php _e('Email title to user following order rejection', 'wp2print'); ?>:</td>
							</tr>
							<tr>
								<td class="padbot"><input type="text" name="orders_email_contents[email_subject_order_rejection]" value="<?php echo $orders_email_contents['email_subject_order_rejection']; ?>"></td>
							</tr>
							<tr>
								<td><?php _e('Email body to user following order rejection', 'wp2print'); ?>:</td>
							</tr>
							<tr>
								<td><textarea name="orders_email_contents[email_message_order_rejection]"><?php echo $orders_email_contents['email_message_order_rejection']; ?></textarea></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top"><?php _e('Group Addresses', 'wp2print'); ?>:</td>
					<td>
						<div class="group-addresses-content" data-dmessage="<?php _e('Are you sure?', 'wp2print'); ?>">
						<table width="100%">
							<tr>
								<td class="gaddresses-label"><?php _e('Billing Addresses', 'wp2print'); ?>
								<a href="#TB_inline?width=400&height=580&inlineId=group-address-form" class="thickbox" onclick="print_products_group_address_add('billing');" style="float:right;"><?php _e('Add New', 'wp2print'); ?></a></td>
							</tr>
							<tr>
								<td class="ga-billing-addresses">
									<table width="100%" cellspacing="0" cellpadding="0" class="group-addresses-table">
										<?php if ($billing_addresses) { ?>
											<?php foreach($billing_addresses as $akey => $address) { ?>
												<tr class="billing-<?php echo $akey; ?>">
													<td><input type="checkbox" name="billing_addresses[<?php echo $akey; ?>][active]" value="1"<?php if ($address['active'] == 1) { echo ' CHECKED'; } ?>></td>
													<td class="a-line"><?php echo $address['label']; ?></td>
													<td align="right"><a href="#TB_inline?width=400&height=580&inlineId=group-address-form" class="thickbox" onclick="print_products_group_address_edit(<?php echo $akey; ?>, 'billing');"><?php _e('Edit', 'wp2print'); ?></a>&nbsp;|&nbsp;<a href="#delete" class="delete-addr" onclick="print_products_group_address_delete('billing-<?php echo $akey; ?>'); return false;"><?php _e('Delete', 'wp2print'); ?></a>
													<div class="a-info" style="display:none;">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][label]" value="<?php echo $address['label']; ?>" class="a-label">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][fname]" value="<?php echo $address['fname']; ?>" class="a-fname">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][lname]" value="<?php echo $address['lname']; ?>" class="a-lname">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][company]" value="<?php echo $address['company']; ?>" class="a-company">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][country]" value="<?php echo $address['country']; ?>" class="a-country">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][address]" value="<?php echo $address['address']; ?>" class="a-address">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][address2]" value="<?php echo $address['address2']; ?>" class="a-address2">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][city]" value="<?php echo $address['city']; ?>" class="a-city">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][state]" value="<?php echo $address['state']; ?>" class="a-state">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][zip]" value="<?php echo $address['zip']; ?>" class="a-zip">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][phone]" value="<?php echo $address['phone']; ?>" class="a-phone">
														<input type="hidden" name="billing_addresses[<?php echo $akey; ?>][email]" value="<?php echo $address['email']; ?>" class="a-email">
													</div>
													</td>
												</tr>
											<?php } ?>
										<?php } else { ?>
											<tr class="a-noaddress"><td class="gaddresses-no"><?php _e('No billing addresses.', 'wp2print'); ?></td></tr>
										<?php } ?>
									</table>
								</td>
							</tr>
							<tr><td style="height:5px;"></td></tr>
							<tr>
								<td class="gaddresses-label"><?php _e('Shipping Addresses', 'wp2print'); ?>
								<a href="#TB_inline?width=400&height=530&inlineId=group-address-form" class="thickbox" onclick="print_products_group_address_add('shipping');" style="float:right;"><?php _e('Add New', 'wp2print'); ?></a></td>
							</tr>
							<tr>
								<td class="ga-shipping-addresses">
									<table width="100%" cellspacing="0" cellpadding="0" class="group-addresses-table">
										<?php if ($shipping_addresses) { ?>
											<?php foreach($shipping_addresses as $akey => $address) { ?>
												<tr class="shipping-<?php echo $akey; ?>">
													<td><input type="checkbox" name="shipping_addresses[<?php echo $akey; ?>][active]" value="1"<?php if ($address['active'] == 1) { echo ' CHECKED'; } ?>></td>
													<td class="a-line"><?php echo $address['label']; ?></td>
													<td align="right"><a href="#TB_inline?width=400&height=530&inlineId=group-address-form" class="thickbox" onclick="print_products_group_address_edit(<?php echo $akey; ?>, 'shipping');"><?php _e('Edit', 'wp2print'); ?></a>&nbsp;|&nbsp;<a href="#delete" class="delete-addr" onclick="print_products_group_address_delete('shipping-<?php echo $akey; ?>'); return false;"><?php _e('Delete', 'wp2print'); ?></a>
													<div class="a-info" style="display:none;">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][label]" value="<?php echo $address['label']; ?>" class="a-label">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][fname]" value="<?php echo $address['fname']; ?>" class="a-fname">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][lname]" value="<?php echo $address['lname']; ?>" class="a-lname">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][company]" value="<?php echo $address['company']; ?>" class="a-company">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][country]" value="<?php echo $address['country']; ?>" class="a-country">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][address]" value="<?php echo $address['address']; ?>" class="a-address">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][address2]" value="<?php echo $address['address2']; ?>" class="a-address2">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][city]" value="<?php echo $address['city']; ?>" class="a-city">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][state]" value="<?php echo $address['state']; ?>" class="a-state">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][zip]" value="<?php echo $address['zip']; ?>" class="a-zip">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][phone]" value="<?php echo $address['phone']; ?>" class="a-phone">
														<input type="hidden" name="shipping_addresses[<?php echo $akey; ?>][email]" value="<?php echo $address['email']; ?>" class="a-email">
													</div>
													</td>
												</tr>
											<?php } ?>
										<?php } else { ?>
											<tr class="a-noaddress"><td class="gaddresses-no"><?php _e('No shipping addresses.', 'wp2print'); ?></td></tr>
										<?php } ?>
									</table>
								</td>
							</tr>
						</table>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label">&nbsp;</td>
					<td class="submit"><input type="submit" value="<?php _e($button, 'wp2print'); ?>" class="button button-primary button-large"></td>
				</tr>
			</table>
		</form>
		<?php add_thickbox(); ?>
		<?php
		$woocountries = WC()->countries->get_shipping_countries();
		$woostates = WC()->countries->get_states();
		?>
		<div style="display:none;">
			<div id="group-address-form">
				<form method="POST" class="group-address-form" onsubmit="return print_products_group_address_save();" data-edit="<?php _e('Edit', 'wp2print'); ?>" data-delete="<?php _e('Delete', 'wp2print'); ?>">
					<input type="hidden" name="gaaction" value="add" class="ga-action">
					<input type="hidden" name="gatype" class="ga-type">
					<input type="hidden" name="garel" class="ga-rel">
					<h2 class="ga-add-title"><?php _e('Add address', 'wp2print'); ?></h2>
					<h2 class="ga-edit-title" style="display:none;"><?php _e('Edit address', 'wp2print'); ?></h2>
					<div class="ga-error"><?php _e('All fields are required.', 'wp2print'); ?></div>
					<table width="100%">
						<tr>
							<td colspan="2"><label><?php _e('Label', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[label]" class="ga-label"></td>
						</tr>
						<tr>
							<td width="50%"><label><?php _e('First Name', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[fname]" class="ga-fname"></td>
							<td width="50%"><label><?php _e('Last Name', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[lname]" class="ga-lname"></td>
						</tr>
						<tr>
							<td colspan="2"><label><?php _e('Company', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[company]" class="ga-company"></td>
						</tr>
						<tr>
							<td colspan="2"><label><?php _e('Country', 'wp2print'); ?>: <span>*</span></label>
							<select name="group_address[country]" class="ga-country" onchange="print_products_group_address_country_change();">
								<option value=""><?php _e('Select a country', 'wp2print'); ?></option>
								<?php foreach($woocountries as $ccode => $cname) { ?>
									<option value="<?php echo $ccode; ?>"><?php echo $cname; ?></option>
								<?php } ?>
							</select></td>
						</tr>
						<tr>
							<td colspan="2"><label><?php _e('Street address', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[address]" class="ga-address"></td>
						</tr>
						<tr>
							<td colspan="2"><input type="text" name="group_address[address2]" class="ga-address2" placeholder="<?php _e('Address line 2', 'wp2print'); ?>"></td>
						</tr>
						<tr>
							<td colspan="2"><label><?php _e('City', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[city]" class="ga-city"></td>
						</tr>
						<tr>
							<td><label><?php _e('State', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[statetext]" class="ga-state-text" style="display:none;">
							<?php foreach($woostates as $ckey => $cstates) { if (count($cstates)) { ?>
								<select name="group_address[state-<?php echo $ckey; ?>]" class="ga-state ga-state-<?php echo $ckey; ?>" style="display:none;">
									<option value="">-- <?php _e('Select State', 'wp2print'); ?> --</option>
									<?php foreach($cstates as $stkey => $stname) { ?>
										<option value="<?php echo $stkey; ?>"><?php echo $stname; ?></option>
									<?php } ?>
								</select>
							<?php }} ?>
							</td>
							<td><label><?php _e('Zip', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[zip]" class="ga-zip"></td>
						</tr>
						<tr class="ga-phone-email">
							<td><label><?php _e('Phone', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[phone]" class="ga-phone"></td>
							<td><label><?php _e('Email', 'wp2print'); ?>: <span>*</span></label>
							<input type="text" name="group_address[email]" class="ga-email"></td>
						</tr>
						<tr>
							<td colspan="2" align="right"><input type="submit" value="<?php _e('Save address', 'wp2print'); ?>" class="button button-primary"></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		<script>
		function users_groups_form_submit() {
			var group_name = jQuery('.users-groups-form .group-name').val();
			if (group_name == '') {
				alert('<?php _e('Group Name is required field.', 'wp2print'); ?>');
				return false;
			}
		}
		var media_uploader = null;
		function open_media_uploader_image()
		{
			media_uploader = wp.media({
				frame:    "post",
				state:    "insert",
				multiple: false
			});

			media_uploader.on("insert", function(){
				var json = media_uploader.state().get("selection").first().toJSON();

				var image_url = json.url;
				jQuery('.logo-image').html('<img src="'+image_url+'">');
				jQuery('.theme-logo').val(image_url);
			});

			media_uploader.open();
		}
		</script>
	<?php } else { ?>
		<?php
		$group_users = array();
		$users_in_groups = get_users(array('meta_key' => '_user_group'));
		if ($users_in_groups) {
			foreach($users_in_groups as $user_in_group) {
				$user_group_id = (int)get_user_meta($user_in_group->ID, '_user_group', true);
				$group_users[$user_group_id]++;
			}
		}
		?>
		<h1><?php _e('Users Groups', 'wp2print'); ?> <a class="page-title-action" href="users.php?page=print-products-users-groups&action=add"><?php _e('Add Group', 'wp2print'); ?></a></h2>
		<form method="POST" action="users.php?page=print-products-users-groups" class="users-groups-delete-form" onsubmit="return users_groups_delete_form_submit();">
			<input type="hidden" name="print_products_users_groups_action" value="true">
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="action" class="action-select">
						<option value=""><?php _e('Bulk Actions', 'wp2print'); ?></option>
						<option value="delete"><?php _e('Delete', 'wp2print'); ?></option>
					</select>
					<input type="submit" id="doaction" class="button action" value="Apply"  />
				</div>
				<div class="tablenav-pages" style="margin-top:10px;"><?php echo $total_groups; ?> <?php if ($total_groups == 1) { _e('group', 'wp2print'); } else { _e('groups', 'wp2print'); } ?></div>
			</div>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox" />
						</td>
						<th scope="col" class="manage-column column-name"><?php _e('Group Name', 'wp2print'); ?></th>
						<th scope="col" class="manage-column column-users"><?php _e('Users', 'wp2print'); ?></th>
						<th scope="col" class="manage-column column-history"><?php _e('Order history', 'wp2print'); ?></th>
						<th scope="col" class="manage-column column-created" style="width:100px;"><?php _e('Created', 'wp2print'); ?></th>
					</tr>
				</thead>
				<tbody id="the-list">
					<?php
					if ($users_groups) {
						foreach($users_groups as $users_group) { ?>
							<tr class="iedit">
								<th scope="row" class="check-column">
									<label class="screen-reader-text" for="cb-select-231">Select Business card</label>
									<input id="cb-select-<?php echo $users_group->group_id; ?>" type="checkbox" name="group[]" value="<?php echo $users_group->group_id; ?>" />
								</th>
								<td class="name column-name column-primary">
									<strong><a class="row-title" href="users.php?page=print-products-users-groups&action=edit&group_id=<?php echo $users_group->group_id; ?>"><?php echo $users_group->group_name; ?></a></strong>
								</td>
								<td class="name column-users">
									<?php echo (int)$group_users[$users_group->group_id]; ?>
								</td>
								<td class="name column-history">
									<a href="admin.php?page=print-products-groups-orders&group_id=<?php echo $users_group->group_id; ?>"><?php _e('View history', 'wp2print'); ?></a>
								</td>
								<td class="name column-created">
									<?php echo date("Y/m/d", strtotime($users_group->created)); ?>
								</td>
							</tr>
						<?php } ?>
					<?php } else { ?>
						<tr class="no-items">
							<td class="colspanchange" colspan="5"><?php _e('No Groups', 'wp2print'); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>
		<script>
		function users_groups_delete_form_submit() {
			var actval = jQuery('.users-groups-delete-form .action-select').val();
			if (actval == 'delete') {
				var d = confirm('<?php _e('Are you sure?', 'wp2print'); ?>')
				if (d) {
					return true;
				}
			}
			return false;
		}
		</script>
	<?php } ?>
</div>
