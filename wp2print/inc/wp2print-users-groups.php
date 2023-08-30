<?php
$current_user_group = false;
add_action('wp_loaded', 'print_products_users_groups_actions');
function print_products_users_groups_actions() {
	global $wpdb, $current_user, $current_user_group;

	if (isset($_POST['print_products_users_groups_action']) && $_POST['print_products_users_groups_action'] == 'true') {
		$group_id = $_POST['group_id'];
		$group_name = trim($_POST['group_name']);
		$use_printshop = (int)$_POST['use_printshop'];
		$use_privatestore = (int)$_POST['use_privatestore'];
		$theme = serialize($_POST['theme']);
		$categories = $_POST['categories'];
		$products = $_POST['products'];
		$payment_method = $_POST['payment_method'];
		$invoice_zero = (int)$_POST['invoice_zero'];
		$free_shipping = (int)$_POST['free_shipping'];
		$shipping_rate = $_POST['shipping_rate'];
		$tax_rate = $_POST['tax_rate'];
		$login_code_required = (int)$_POST['login_code_required'];
		$login_code = trim($_POST['login_code']);
		$login_redirect = trim($_POST['login_redirect']);
		$logout_redirect = trim($_POST['logout_redirect']);
		$order_emails = trim($_POST['order_emails']);
		$tax_id = trim($_POST['tax_id']);
		$accounting_id = trim($_POST['accounting_id']);
		$orders_approving = (int)$_POST['orders_approving'];
		$orders_approving_amount = $_POST['orders_approving_amount'];
		$assign_new_user = (int)$_POST['assign_new_user'];
		$aregister_domain = trim($_POST['aregister_domain']);
		$users = $_POST['users'];
		$superusers = $_POST['superusers'];
		$orders_email_contents = serialize($_POST['orders_email_contents']);
		$options = serialize($_POST['options']);
		$billing_addresses = serialize($_POST['billing_addresses']);
		$shipping_addresses = serialize($_POST['shipping_addresses']);
		$allow_modify_pdf = (int)$_POST['allow_modify_pdf'];

		if ($payment_method && is_array($payment_method)) {
			$payment_method = implode(';', $payment_method);
		}

		if (!$tax_rate) { $tax_rate = NULL; }

		if (!is_array($superusers)) { $superusers = array(); }
		$aregister_domain = str_replace('@', '', $aregister_domain);

		if (print_products_polylang_installed()) {
			$categories = print_products_polylang_get_category_ids($categories);
			$products = print_products_polylang_get_product_ids($products);
		}

		$categories = serialize($categories);
		$products = serialize($products);

		switch($_POST['action']) {
			case 'add':
				$insert = array();
				$insert['group_name'] = $group_name;
				$insert['use_printshop'] = $use_printshop;
				$insert['use_privatestore'] = $use_privatestore;
				$insert['theme'] = $theme;
				$insert['categories'] = $categories;
				$insert['products'] = $products;
				$insert['payment_method'] = $payment_method;
				$insert['invoice_zero'] = $invoice_zero;
				$insert['free_shipping'] = $free_shipping;
				$insert['shipping_rate'] = $shipping_rate;
				$insert['tax_rate'] = $tax_rate;
				$insert['login_code_required'] = $login_code_required;
				$insert['login_code'] = $login_code;
				$insert['login_redirect'] = $login_redirect;
				$insert['logout_redirect'] = $logout_redirect;
				$insert['order_emails'] = $order_emails;
				$insert['tax_id'] = $tax_id;
				$insert['accounting_id'] = $accounting_id;
				$insert['orders_approving'] = $orders_approving;
				$insert['orders_approving_amount'] = $orders_approving_amount;
				$insert['assign_new_user'] = $assign_new_user;
				$insert['aregister_domain'] = $aregister_domain;
				$insert['orders_email_contents'] = $orders_email_contents;
				$insert['options'] = $options;
				$insert['billing_addresses'] = $billing_addresses;
				$insert['shipping_addresses'] = $shipping_addresses;
				$insert['allow_modify_pdf'] = $allow_modify_pdf;
				$insert['created'] = current_time('mysql');
				$wpdb->insert($wpdb->prefix.'print_products_users_groups', $insert);
				$group_id = $wpdb->insert_id;
				if ($users) {
					foreach($users as $user_id) {
						update_user_meta($user_id, '_user_group', $group_id);
					}
				}
				if ($superusers) {
					foreach($superusers as $user_id) {
						print_products_users_groups_user_set_superuser($user_id, $group_id);
					}
				}
				if ($assign_new_user == 1) {
					$wpdb->query(sprintf("UPDATE %sprint_products_users_groups SET assign_new_user = 0 WHERE group_id != %s", $wpdb->prefix, $group_id));
				}
			break;
			case 'edit':
				$update = array();
				$update['group_name'] = $group_name;
				$update['use_printshop'] = $use_printshop;
				$update['use_privatestore'] = $use_privatestore;
				$update['theme'] = $theme;
				$update['categories'] = $categories;
				$update['products'] = $products;
				$update['payment_method'] = $payment_method;
				$update['invoice_zero'] = $invoice_zero;
				$update['free_shipping'] = $free_shipping;
				$update['shipping_rate'] = $shipping_rate;
				$update['tax_rate'] = $tax_rate;
				$update['login_code_required'] = $login_code_required;
				$update['login_code'] = $login_code;
				$update['login_redirect'] = $login_redirect;
				$update['logout_redirect'] = $logout_redirect;
				$update['order_emails'] = $order_emails;
				$update['tax_id'] = $tax_id;
				$update['accounting_id'] = $accounting_id;
				$update['orders_approving'] = $orders_approving;
				$update['orders_approving_amount'] = $orders_approving_amount;
				$update['assign_new_user'] = $assign_new_user;
				$update['aregister_domain'] = $aregister_domain;
				$update['orders_email_contents'] = $orders_email_contents;
				$update['options'] = $options;
				$update['billing_addresses'] = $billing_addresses;
				$update['shipping_addresses'] = $shipping_addresses;
				$update['allow_modify_pdf'] = $allow_modify_pdf;
				$wpdb->update($wpdb->prefix.'print_products_users_groups', $update, array('group_id' => $group_id));

				$group_users = get_users(array('meta_key' => '_user_group', 'meta_value' => $group_id));
				if ($group_users) {
					foreach($group_users as $group_user) {
						delete_user_meta($group_user->ID, '_user_group');
					}
				}
				if ($users) {
					foreach($users as $user_id) {
						update_user_meta($user_id, '_user_group', $group_id);
					}
				}
				print_products_users_groups_delete_superuser($group_id);
				if ($superusers) {
					foreach($superusers as $user_id) {
						print_products_users_groups_user_set_superuser($user_id, $group_id);
					}
				}
				if ($assign_new_user == 1) {
					$wpdb->query(sprintf("UPDATE %sprint_products_users_groups SET assign_new_user = 0 WHERE group_id != %s", $wpdb->prefix, $group_id));
				}
			break;
			case 'delete':
				$group = $_POST['group'];
				if ($group) {
					foreach($group as $group_id) {
						$wpdb->delete($wpdb->prefix.'print_products_users_groups', array('group_id' => $group_id));
						$group_users = get_users(array('meta_key' => '_user_group', 'meta_value' => $group_id));
						if ($group_users) {
							foreach($group_users as $group_user) {
								delete_user_meta($group_user->ID, '_user_group');
							}
						}
						print_products_users_groups_delete_superuser($group_id);
					}
				}
			break;
		}
		wp_redirect('users.php?page=print-products-users-groups');
		exit;
	}
	if (isset($_POST['external_login']) && $_POST['external_login'] == 'true') {
		print_products_users_groups_external_login();
	} else if (isset($_POST['external_register']) && $_POST['external_register'] == 'true') {
		print_products_users_groups_external_register();
	}
}

add_action('admin_menu', 'print_products_users_groups_admin_page_menu');
function print_products_users_groups_admin_page_menu() {
	global $current_user_group, $current_user;
	add_users_page(
		__('Users Groups', 'wp2print'), // meta title
		__('Users Groups', 'wp2print'), // admin menu title
		'create_users',
		'print-products-users-groups',
		'print_products_users_groups_admin_page'
	);
}

function print_products_users_groups_admin_page() {
	global $wpdb;
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-users-groups.php';
}

add_action('admin_enqueue_scripts', 'print_products_users_groups_enqueue_scripts');
function print_products_users_groups_enqueue_scripts($hook_suffix) {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('print-products-script-handle', PRINT_PRODUCTS_PLUGIN_URL.'js/wp2print-ugscript.js', array( 'wp-color-picker' ), false, true);
	wp_enqueue_media();
}

function print_products_users_groups_get_groups() {
	global $wpdb;
	$groups = array();
	$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_name", $wpdb->prefix));
	if ($users_groups) {
		foreach($users_groups as $users_group) {
			$groups[$users_group->group_id] = $users_group->group_name;
		}
	}
	return $groups;
}

function print_products_users_groups_get_group_superusers($group_id) {
	global $wpdb;
	$superusers = array();
	$user_superuser_groups = $wpdb->get_results(sprintf("SELECT * FROM %susermeta WHERE meta_key = '_superuser_group'", $wpdb->base_prefix));
	if ($user_superuser_groups) {
		foreach($user_superuser_groups as $user_superuser_group) {
			$user_id = $user_superuser_group->user_id;
			$usgroups = explode(';', $user_superuser_group->meta_value);
			if (in_array($group_id, $usgroups)) {
				if (!in_array($user_id, $superusers)) {
					$superusers[] = $user_id;
				}
			}
		}
	}
	return $superusers;
}

function print_products_users_groups_user_set_superuser($user_id, $group_id) {
	$superuser_group_meta = get_user_meta($user_id, '_superuser_group', true);
	if (strlen($superuser_group_meta)) {
		$sugroups = explode(';', $superuser_group_meta);
		if (!in_array($group_id, $sugroups)) {
			$sugroups[] = $group_id;
		}
		$superuser_group = implode(';', $sugroups);
	} else {
		$superuser_group = $group_id;
	}
	update_user_meta($user_id, '_superuser_group', $superuser_group);
}

function print_products_users_groups_user_remove_superuser($user_id, $group_id) {
	$superuser_group_meta = get_user_meta($user_id, '_superuser_group', true);
	if (strlen($superuser_group_meta)) {
		$superuser_group = array();
		$sugroups = explode(';', $superuser_group_meta);
		foreach($sugroups as $sugroup_id) {
			if ($sugroup_id != $group_id) {
				$superuser_group[] = $sugroup_id;
			}
		}
		$superuser_group = implode(';', $superuser_group);
		update_user_meta($user_id, '_superuser_group', $superuser_group);
	}
}

function print_products_users_groups_delete_superuser($group_id) {
	global $wpdb;
	$group_superuser_users = $wpdb->get_results(sprintf("SELECT * FROM %susermeta WHERE meta_key = '_superuser_group'", $wpdb->base_prefix));
	if ($group_superuser_users) {
		foreach($group_superuser_users as $group_superuser_user) {
			print_products_users_groups_user_remove_superuser($group_superuser_user->user_id, $group_id);
		}
	}
}

function print_products_users_groups_is_superuser($user_id) {
	return get_user_meta($user_id, '_superuser_group', true);
}

add_action('show_user_profile', 'print_products_users_groups_profile_field');
add_action('edit_user_profile', 'print_products_users_groups_profile_field');
function print_products_users_groups_profile_field($profileuser) {
	global $wpdb, $print_products_plugin_options, $current_user;
	$user_group = get_user_meta($profileuser->ID, '_user_group', true);
	$user_accounting_id = get_user_meta($profileuser->ID, '_user_accounting_id', true);
	$user_invoice_payment = get_user_meta($profileuser->ID, '_user_invoice_payment', true);
	if ($print_products_plugin_options['allowmodifygroup']) {
		$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_name", $wpdb->prefix));
		if ($users_groups) { ?>
			<h3><?php _e('User Group', 'wp2print'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label><?php _e('Group', 'wp2print'); ?></label></th>
					<td>
						<select name="usergroup">
							<option value="">-- <?php _e('Select Group', 'wp2print'); ?> --</option>
							<?php foreach($users_groups as $users_group) { $s = ''; if ($users_group->group_id == $user_group) { $s = ' SELECTED'; } ?>
								<option value="<?php echo $users_group->group_id; ?>"<?php echo $s; ?>><?php echo $users_group->group_name; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			</table>
		<?php }
	} else {
		if ($user_group) {
			$group_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_users_groups WHERE group_id = %s", $wpdb->prefix, $user_group));
			?>
			<h3><?php _e('User Group', 'wp2print'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label><?php _e('Group', 'wp2print'); ?></label></th>
					<td><?php echo $group_data->group_name; ?></td>
				</tr>
			</table>
		<?php } ?>
	<?php } ?>
	<h3><?php _e('Customer Accounting ID', 'wp2print'); ?></h3>
	<table class="form-table">
		<tr>
			<th><label><?php _e('Customer Accounting ID', 'wp2print'); ?></label></th>
			<td>
				<input type="text" name="user_accounting_id" value="<?php echo $user_accounting_id; ?>">
			</td>
		</tr>
	</table>
	<?php if (print_products_users_groups_is_allow_change_invoice_payment()) { ?>
		<h3><?php _e('Invoice Payment Method', 'wp2print'); ?></h3>
		<table class="form-table">
			<tr>
				<th><label><?php _e('Enable Invoice payment', 'wp2print'); ?></label></th>
				<td>
					<input type="checkbox" name="user_invoice_payment" value="1"<?php if ($user_invoice_payment) { echo ' CHECKED'; } ?>>
				</td>
			</tr>
		</table>
		<?php
	}
}

add_action('personal_options_update', 'print_products_users_groups_save_profile_field');
add_action('edit_user_profile_update', 'print_products_users_groups_save_profile_field');
function print_products_users_groups_save_profile_field($user_id) {
	global $print_products_plugin_options, $current_user;
	update_usermeta($user_id, '_user_accounting_id', $_POST['user_accounting_id']);
	if ($print_products_plugin_options['allowmodifygroup']) {
		update_usermeta($user_id, '_user_group', $_POST['usergroup']);
	}
	if (print_products_users_groups_is_allow_change_invoice_payment()) {
		update_usermeta($user_id, '_user_invoice_payment', $_POST['user_invoice_payment']);
	}
}

function print_products_users_groups_is_allow_change_invoice_payment() {
	global $current_user;
	if (in_array('administrator', $current_user->roles) || in_array('adminlite', $current_user->roles) || in_array('sales', $current_user->roles)) {
		return true;
	}
	return false;
}

function print_products_users_groups_data($group_id) {
	global $wpdb;
	return $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_users_groups WHERE group_id = %s", $wpdb->prefix, $group_id));
}

function print_products_users_groups_get_user_group($user_id) {
	$user_group = get_user_meta($user_id, '_user_group', true);
	if ($user_group) {
		$group_data = print_products_users_groups_data($user_group);
		if ($group_data) {
			return $group_data;
		}
	}
	return false;
}

function print_products_users_groups_get_group_users($group_id) {
	$group_users = array();
	$gusers = get_users(array('meta_key' => '_user_group', 'meta_value' => $group_id));
	if ($gusers) {
		foreach($gusers as $guser) {
			$group_users[] = $guser->ID;
		}
	}
	return $group_users;
}

// group login redirect
add_filter('login_redirect', 'print_products_users_groups_login_redirect', 10, 3);
function print_products_users_groups_login_redirect($redirect_to, $requested_redirect_to, $user) {
	if (!is_wp_error($user)) {
		$user_group = print_products_users_groups_get_user_group($user->ID);
		if ($user_group) {
			$login_redirect = $user_group->login_redirect;
			if (strlen($login_redirect)) {
				$redirect_to = $login_redirect;
			}
		}
	}
	return $redirect_to;
}

add_filter('woocommerce_login_redirect', 'print_products_users_groups_woo_login_redirect', 10, 2);
function print_products_users_groups_woo_login_redirect($redirect_to, $user) {
	$user_group = print_products_users_groups_get_user_group($user->ID);
	if ($user_group) {
		$login_redirect = $user_group->login_redirect;
		if (strlen($login_redirect)) {
			$redirect_to = $login_redirect;
		}
	}
	return $redirect_to;
}

// group logout redirect
add_filter('logout_redirect', 'print_products_users_groups_logout_redirect', 10, 3);
function print_products_users_groups_logout_redirect($redirect_to, $requested_redirect_to, $user) {
	$user_group = print_products_users_groups_get_user_group($user->ID);
	if ($user_group) {
		$logout_redirect = $user_group->logout_redirect;
		if (strlen($logout_redirect)) {
			$redirect_to = $logout_redirect;
		}
	}
	return $redirect_to;
}

add_filter('allowed_redirect_hosts', 'print_products_users_groups_allowed_redirect_hosts', 11);
function print_products_users_groups_allowed_redirect_hosts($hosts) {
	global $wpdb;
	$home_info = parse_url(home_url());
	$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_name", $wpdb->prefix));
	if ($users_groups) {
		foreach($users_groups as $users_group) {
			if (strlen($users_group->logout_redirect)) {
				$lr_info = parse_url($users_group->logout_redirect);
				if ($lr_info['host'] != $home_info['host']) {
					$hosts[] = $lr_info['host'];
				}
			}
		}
	}
	return $hosts;
}

// group Tax ID
add_action('woocommerce_new_order', 'print_products_users_groups_add_order_tax_id');
function print_products_users_groups_add_order_tax_id($order_id) {
	global $current_user;
	$user_group = print_products_users_groups_get_user_group($current_user->ID);
	if ($user_group) {
		$tax_id = $user_group->tax_id;
		if (strlen($tax_id)) {
			update_post_meta($order_id, '_tax_id', $tax_id);
		}
	}
}

add_action('woocommerce_email_customer_details', 'print_products_users_groups_email_tax_id', 100, 3);
function print_products_users_groups_email_tax_id($order, $sent_to_admin, $plain_text) {
	global $current_user;
	$user_group = print_products_users_groups_get_user_group($current_user->ID);
	if ($user_group) {
		$tax_id = $user_group->tax_id;
		if (strlen($tax_id)) {
			echo '<p><br><strong>'.__('Tax ID', 'wp2print').':</strong> '.$tax_id.'</p>';
		}
	}
}

add_action('woocommerce_admin_order_data_after_billing_address', 'print_products_users_groups_admin_tax_id');
function print_products_users_groups_admin_tax_id($order) {
	$order_tax_id = get_post_meta($order->id, '_tax_id', true);
	if (strlen($order_tax_id)) {
		echo '<p><strong>'.__('Tax ID', 'wp2print').':</strong> '.$order_tax_id.'</p>';
	}
}

function print_products_users_groups_get_option($option) {
	global $current_user_group;
	if ($current_user_group) {
		return $current_user_group->$option;
	}
}

// group theme settings
add_filter('printshop_homepage_url', 'print_products_users_groups_homepage_url');
add_filter('kadence_logo_link', 'print_products_users_groups_homepage_url');
function print_products_users_groups_homepage_url($url) {
	global $current_user_group;
	if ($current_user_group) {
		$theme = unserialize($current_user_group->theme);
		if (strlen($theme['homeurl'])) {
			$url = $theme['homeurl'];
		}
	}
	return $url;
}

add_filter('printshop_site_logo', 'print_products_users_groups_site_logo');
function print_products_users_groups_site_logo($logo) {
	global $current_user_group;
	if ($current_user_group) {
		$theme = unserialize($current_user_group->theme);
		if (strlen($theme['logo'])) {
			$logo = $theme['logo'];
		}
	}
	return $logo;
}

add_filter('printshop_background_color', 'print_products_users_groups_background_color', 10, 2);
function print_products_users_groups_background_color($style, $type) {
	global $current_user_group;
	if ($current_user_group) {
		$theme = unserialize($current_user_group->theme);
		if (strlen($theme[$type])) {
			$style .= 'background-color:'.$theme[$type].' !important;';
		}
	}
	return $style;
}

add_filter('printshop_get_group_color', 'print_products_users_groups_get_color', 10, 1);
function print_products_users_groups_get_color($type) {
	global $current_user_group;
	if ($current_user_group) {
		$theme = unserialize($current_user_group->theme);
		if (strlen($theme[$type])) {
			return $theme[$type];
		}
	}
}

add_filter('printshop_menu', 'print_products_users_groups_menu', 10, 2);
function print_products_users_groups_menu($menu, $type) {
	global $current_user_group;
	if ($current_user_group) {
		$theme = unserialize($current_user_group->theme);
		if (strlen($theme[$type.'menu'])) {
			$menu = $theme[$type.'menu'];
		}
	}
	return $menu;
}

// payment gateway
add_filter('woocommerce_available_payment_gateways', 'print_products_users_groups_payment_gateway');
function print_products_users_groups_payment_gateway($available_gateways) {
	global $current_user_group, $current_user;

	$pgateways = WC()->payment_gateways();
	$payment_gateways = $pgateways->payment_gateways();
	$user_invoice_payment = get_user_meta($current_user->ID, '_user_invoice_payment', true);
	if (is_checkout()) {
		if ($current_user_group) {
			$payment_method = $current_user_group->payment_method;
			$invoice_zero = $current_user_group->invoice_zero;
			$cart_total = WC()->cart->total;
			if ($cart_total == 0 && $invoice_zero) {
				$available_gateways = array('cod' => $payment_gateways['cod']);
			} else if (strlen($payment_method)) {
				$available_gateways = array();
				$payment_methods = explode(';', $payment_method);
				foreach($payment_methods as $payment_method) {
					$available_gateways[$payment_method] = $payment_gateways[$payment_method];
				}
			}
		} else if ($user_invoice_payment) {
			if (!isset($available_gateways['cod'])) {
				$available_gateways = array('cod' => $payment_gateways['cod']);
			}
		}
	}
	return $available_gateways;
}

// shipping method
add_filter('woocommerce_package_rates', 'print_products_package_rates', 10);
function print_products_package_rates($rates) {
	global $current_user_group;
	if ($current_user_group) {
		$free_shipping = (int)$current_user_group->free_shipping;
		$shipping_rate = (float)$current_user_group->shipping_rate;
		if ($free_shipping) {
			$rates = array('free_shipping:1' => new WC_Shipping_Rate('free_shipping:1', __('Free Shipping', 'woocommerce'), 0, array(), 'free_shipping'));
		} else if ($shipping_rate) {
			$rates = array('flat_rate:1' => new WC_Shipping_Rate('flat_rate:1', __('Flat Rate', 'woocommerce'), $shipping_rate, array(), 'flat_rate'));
		}
	}
	return $rates;
}

// tax rate
$wp2print_tax_total = 0;
$wp2print_matched_tax_rates = false;
add_action('woocommerce_calculate_totals', 'print_products_users_groups_calculate_totals', 10);
function print_products_users_groups_calculate_totals($cart) {
	global $current_user_group, $wp2print_tax_total;
	if ($current_user_group) {
		if (strlen($current_user_group->tax_rate)) {
			$tax_rate = (float)$current_user_group->tax_rate;
			$cart_total = WC()->cart->cart_contents_total;
			$wp2print_tax_total = ($cart_total / 100) * $tax_rate;
			WC()->cart->tax_total = $wp2print_tax_total;
		}
	}
}

add_filter('woocommerce_matched_tax_rates' , 'print_products_users_groups_matched_tax_rates', 10);
function print_products_users_groups_matched_tax_rates($matched_tax_rates) {
	global $current_user_group, $wp2print_matched_tax_rates;
	$wp2print_matched_tax_rates = $matched_tax_rates;
	if ($current_user_group && $matched_tax_rates) {
		if (strlen($current_user_group->tax_rate)) {
			$tax_rate = (float)$current_user_group->tax_rate;
			foreach($matched_tax_rates as $trkey => $taxrate) {
				$matched_tax_rates[$trkey]['rate'] = $tax_rate;
			}
		}
	}
	return $matched_tax_rates;
}

add_filter('woocommerce_cart_totals_order_total_html', 'print_products_users_groups_cart_totals_order_total_html', 10);
function print_products_users_groups_cart_totals_order_total_html($value) {
	global $wp2print_tax_total, $wp2print_matched_tax_rates;
	if ($wp2print_tax_total && (WC()->cart->tax_display_cart == 'incl' || (get_option( 'woocommerce_tax_total_display' ) == 'itemized' && !$wp2print_matched_tax_rates))) {
		$value = '<strong>' . WC()->cart->get_total() . '</strong> ';
		$value .= '<small class="includes_tax">' . sprintf( __( '(includes %s Tax)', 'woocommerce' ), wc_price($wp2print_tax_total) ) . '</small>';
	}
	return $value;
}

add_filter('woocommerce_cart_totals_taxes_total_html', 'print_products_users_groups_cart_totals_taxes_total_html', 10);
function print_products_users_groups_cart_totals_taxes_total_html($value) {
	global $wp2print_tax_total;
	if ($wp2print_tax_total && WC()->cart->tax_display_cart == 'excl') {
		$value = wc_price($wp2print_tax_total);
	}
	return $value;
}

add_action('woocommerce_checkout_update_order_meta', 'print_products_users_groups_checkout_update_order_meta', 10, 2);
function print_products_users_groups_checkout_update_order_meta($order_id, $posted) {
	global $current_user_group, $wpdb;
	if ($current_user_group) {
		if (strlen($current_user_group->tax_rate)) {
			$tax_rate = (float)$current_user_group->tax_rate;
			$cart_total = WC()->cart->cart_contents_total;
			$tax_total = ($cart_total / 100) * $tax_rate;
			$check_tax = $wpdb->get_var(sprintf("SELECT order_item_id FROM %swoocommerce_order_items WHERE order_id = %s AND order_item_type = 'tax'", $wpdb->prefix, $order_id));
			if (!$check_tax) {
				$order_item_id = wc_add_order_item($order_id, array('order_item_name' => 'TAX-1', 'order_item_type' => 'tax'));
				wc_add_order_item_meta($order_item_id, 'tax_amount', $tax_total);
				wc_add_order_item_meta($order_item_id, 'label', __('Tax', 'woocommerce'));
			}
		}
	}
}

// available categories
add_filter('get_terms_args', 'print_products_users_groups_visible_categories', 25);
function print_products_users_groups_visible_categories($args) {
	global $current_user_group, $wpdb, $wp_query;
	if (!is_admin() && ($args['taxonomy'] == 'product_cat' || in_array('product_cat', (array)$args['taxonomy']))) {
		if (!$args['object_ids'] && !$args['include']) {
			$uncategorized = get_option('default_product_cat');
			$has_categories = false;
			if ($current_user_group) {
				$cug_options = unserialize($current_user_group->options);
				$categories = print_products_users_groups_get_categories($current_user_group);

				// select groups categories where display_categories = 1
				$all_groups_categories = array();
				$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_id", $wpdb->prefix));
				if ($users_groups) {
					foreach($users_groups as $users_group) {
						$options = unserialize($users_group->options);
						$gcategories = unserialize($users_group->categories);
						if (is_array($gcategories)) {
							$all_groups_categories = array_merge($all_groups_categories, $gcategories);
							if ($options['display_categories']) {
								$categories = array_merge($categories, $gcategories);
							}
						}
					}
				}
				if ($cug_options['display_public_products'] == 1) {
					$prod_cats = $wpdb->get_results(sprintf("SELECT term_id FROM %sterm_taxonomy WHERE taxonomy = 'product_cat' AND count > 0", $wpdb->prefix));
					if ($prod_cats) {
						if (!is_array($categories)) { $categories = array(); }
						foreach($prod_cats as $prod_cat) {
							$cat_id = $prod_cat->term_id;
							if (!in_array($cat_id, $all_groups_categories) && !in_array($cat_id, $categories)) {
								$categories[] = $cat_id;
							}
						}
					}
				}
				if ($categories && $uncategorized) {
					$ukey = array_search($uncategorized, $categories);
					if ($ukey !== false) {
						unset($categories[$ukey]);
					}
				}
				if ($categories) {
					$args['include'] = array_unique($categories);
					$has_categories = true;
				}
			}
			if (!$has_categories) {
				$excl_categories = array();
				if ($uncategorized) {
					$excl_categories[] = $uncategorized;
				}
				$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_id", $wpdb->prefix));
				if ($users_groups) {
					if ($current_user_group) {
						$prod_cats = $wpdb->get_results(sprintf("SELECT term_id FROM %sterm_taxonomy WHERE taxonomy = 'product_cat'", $wpdb->prefix));
						if ($prod_cats) {
							foreach($prod_cats as $prod_cat) {
								$excl_categories[] = $prod_cat->term_id;
							}
						}
						foreach($users_groups as $users_group) {
							$options = unserialize($users_group->options);
							$categories = unserialize($users_group->categories);
							if ($categories && $options['display_categories']) {
								foreach($categories as $category) {
									$ckey = array_search($category, $excl_categories);
									unset($excl_categories[$ckey]);
								}
							}
						}
					} else {
						foreach($users_groups as $users_group) {
							$categories = unserialize($users_group->categories);
							if ($categories) {
								$excl_categories = array_merge($excl_categories, $categories);
							}
						}
					}
				}
				if (count($excl_categories)) {
					$args['exclude'] = array_unique($excl_categories);
				}
			}
		}
	}
	return $args;
}

// available products
add_action('pre_get_posts', 'print_products_users_groups_visible_products');
function print_products_users_groups_visible_products($query) {
	global $current_user_group, $wpdb;
	$query_vars = $query->query_vars;
	$ppprocess = false;

	if (is_search() && $query->is_main_query() && !$query_vars['post_type']) {
		$post_types = get_post_types(array('exclude_from_search' => false));
		unset($post_types['product']);
		$query->set('post_type', $post_types);
	} else {
		$wwof_display = false; // parameter for 'WooCommerce Wholesale Order Form' plugin
		if (is_ajax() && isset($_POST['action']) && $_POST['action'] == 'wwof_display_product_listing') {
			$wwof_display = true;
		}
		if (isset($query_vars['post_type']) && $query_vars['post_type'] == 'product' && (!is_admin() || $wwof_display)) {
			$ppprocess = true;
		} else if (isset($query_vars['product_cat']) && strlen($query_vars['product_cat'])) {
			$ppprocess = true;
		}
		if ($ppprocess) {
			$has_products = false;
			if ($current_user_group) {
				$products = print_products_users_groups_get_products($current_user_group);
				$public_products = print_products_users_groups_get_public_products($current_user_group);
				if ($public_products) {
					$products = array_merge($products, $public_products);
				}
				if (!$products) {
					// select groups products where display_products = 1
					$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_id", $wpdb->prefix));
					if ($users_groups) {
						foreach($users_groups as $users_group) {
							$options = unserialize($users_group->options);
							if ($options['display_products']) {
								$gproducts = print_products_users_groups_get_products($users_group);
								if (count($gproducts)) {
									$products = array_merge($products, $gproducts);
								}
							}
						}
					}
				}
				if ($products) {
					$query->set('post__in', array_unique($products));
					$has_products = true;
				}
			}
			if (!$has_products) {
				$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_id", $wpdb->prefix));
				if ($users_groups) {
					$excl_products = array();
					if ($current_user_group) {
						foreach($users_groups as $users_group) {
							$options = unserialize($users_group->options);
							if (!$options['display_products']) {
								$products = print_products_users_groups_get_products($users_group);
								$excl_products = array_merge($excl_products, $products);
							}
						}
					} else {
						foreach($users_groups as $users_group) {
							$products = print_products_users_groups_get_products($users_group);
							if ($products) {
								$excl_products = array_merge($excl_products, $products);
							}
						}
					}
					if (count($excl_products)) {
						$query->set('post__not_in', array_unique($excl_products));
					}
				}
			}
		}
	}
}

function print_products_users_groups_get_products($users_group) {
	global $wpdb;
	$group_products = array();
	$products = unserialize($users_group->products);
	$categories = unserialize($users_group->categories);
	if ($products) {
		$group_products = $products;
	}
	if ($categories) {
		$cat_products = $wpdb->get_results(sprintf("SELECT tr.object_id FROM %sterm_relationships tr LEFT JOIN %sterm_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy = 'product_cat' AND tt.term_id IN (%s)", $wpdb->prefix, $wpdb->prefix, implode(',', $categories)));
		if ($cat_products) {
			foreach($cat_products as $cat_product) {
				$product_id = $cat_product->object_id;
				if (!in_array($product_id, $group_products)) {
					$group_products[] = $product_id;
				}
			}
		}
	}
	return $group_products;
}

function print_products_users_groups_get_public_products($group_data) {
	global $wpdb;
	$public_products = array();
	$options = unserialize($group_data->options);
	if ($options['display_public_products']) {
		$group_products = array();
		$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_id", $wpdb->prefix));
		if ($users_groups) {
			foreach($users_groups as $users_group) {
				$products = print_products_users_groups_get_products($users_group);
				$group_products = array_merge($group_products, $products);
			}
		}
		$all_products = $wpdb->get_results(sprintf("SELECT * FROM %sposts WHERE post_type = 'product' AND post_status = 'publish' ORDER BY ID", $wpdb->prefix));
		if ($all_products) {
			foreach($all_products as $product) {
				if (!in_array($product->ID, $group_products)) {
					$public_products[] = $product->ID;
				}
			}
		}
	}
	return $public_products;
}

function print_products_users_groups_get_categories($users_group) {
	global $wpdb;
	$group_categories = array();
	$products = unserialize($users_group->products);
	$categories = unserialize($users_group->categories);
	if ($categories) {
		$group_categories = $categories;
	}
	if ($products) {
		$products_cats = $wpdb->get_results(sprintf("SELECT tt.term_id FROM %sterm_relationships tr LEFT JOIN %sterm_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy = 'product_cat' AND tr.object_id IN (%s)", $wpdb->prefix, $wpdb->prefix, implode(',', $products)));
		if ($products_cats) {
			foreach($products_cats as $products_cat) {
				$cat_id = $products_cat->term_id;
				if (!in_array($cat_id, $group_categories)) {
					$group_categories[] = $cat_id;
				}
			}
		}
	}
	return $group_categories;
}

add_filter('woocommerce_disable_admin_bar', 'print_products_users_groups_disable_admin_bar');
add_filter('woocommerce_prevent_admin_access', 'print_products_users_groups_disable_admin_bar');
function print_products_users_groups_disable_admin_bar($disable) {
	return false;
}

add_action('woocommerce_order_status_new', 'print_products_users_groups_new_order');
add_action('woocommerce_order_status_processing', 'print_products_users_groups_new_order');
add_action('woocommerce_order_status_on-hold', 'print_products_users_groups_new_order');
add_action('woocommerce_order_status_completed', 'print_products_users_groups_new_order');
function print_products_users_groups_new_order($order_id) {
	global $wpdb, $current_user_group;
	if (!$current_user_group) {
		$user = wp_get_current_user();
		$current_user_group = print_products_users_groups_get_user_group($user->ID);
	}
	if (!is_admin() && $current_user_group) {
		$order = wc_get_order($order_id);
		$order_subtotal = $order->get_subtotal();
		if (strlen($current_user_group->order_emails)) {
			print_products_users_groups_send_to_group_emails_list($order_id);
		}
		if ($current_user_group->orders_approving) {
			$approval = true;
			$orders_approving_amount = (float)$current_user_group->orders_approving_amount;
			if ($order_subtotal < $orders_approving_amount) {
				$approval = false;
			}
			if ($approval) {
				foreach ($order->get_items() as $item_id => $item) {
					wc_update_order_item_meta($item_id, '_approval_status', 'awaiting');
					wc_update_order_item_meta($item_id, '_approval_type', '0');
				}

				$oaa_link = print_products_get_my_account_custom_page_url('orders-awaiting-approval');
				$oaa_link_part = str_replace(site_url('/'), '', $oaa_link);

				// send email to superuser
				$orders_email_contents = unserialize($current_user_group->orders_email_contents);
				if (!strlen($orders_email_contents['email_subject_order_approval'])) {
					$orders_email_contents['email_subject_order_approval'] = 'New order awaiting your approval';
				}
				if (!strlen($orders_email_contents['email_message_order_approval'])) {
					$orders_email_contents['email_message_order_approval'] = 'There is a new order from the '.get_bloginfo('name').' awaiting your approval. Please visit the website and give your approval to begin production:'.chr(10).chr(10).site_url();
				}
				$subject = $orders_email_contents['email_subject_order_approval'];
				$message = $orders_email_contents['email_message_order_approval'];
				$message = str_replace('[ORDERID]', $order_id, $message);
				$message = str_replace('wp-admin/admin.php?page=orders-awaiting-approval', $oaa_link_part, $message);
				$headers = 'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>' . "\r\n";

				$group_superusers = print_products_users_groups_get_group_superusers($current_user_group->group_id);
				if ($group_superusers) {
					foreach($group_superusers as $user_id) {
						$user_info = get_userdata($user_id);
						if ($user_info) {
							wp_mail($user_info->user_email, $subject, $message, $headers);
						}
					}
				}
			}
		}
	}
}

// send comnfirmation email to group emails list
function print_products_users_groups_send_to_group_emails_list($order_id) {
	global $current_user_group;
	if ($current_user_group && strlen($current_user_group->order_emails)) {
		$order_emails = preg_split('/'.chr(10).'/', $current_user_group->order_emails);
		if (count($order_emails) > 0) {
			$order_data = wc_get_order( $order_id );
			if ($order_data->post_status == 'wc-on-hold') {
				$wcecpo = WC()->mailer()->emails['WC_Email_Customer_On_Hold_Order'];
			} else if ($order_data->post_status == 'wc-completed') {
				$wcecpo = WC()->mailer()->emails['WC_Email_Customer_Completed_Order'];
			} else {
				$wcecpo = WC()->mailer()->emails['WC_Email_Customer_Processing_Order'];
			}
			$wcecpo->object = $order_data;

			$wcecpo->find['order-date']   = '{order_date}';
			$wcecpo->find['order-number'] = '{order_number}';

			$wcecpo->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $wcecpo->object->order_date ) );
			$wcecpo->replace['order-number'] = $wcecpo->object->get_order_number();

			$mailer = WC()->mailer();
			$subject = $wcecpo->get_subject();
			$message = $wcecpo->get_content();
			$headers = $wcecpo->get_headers();
			$attachments = $wcecpo->get_attachments();

			foreach($order_emails as $order_email) { $order_email = str_replace(chr(13), '', $order_email);
				if (strlen($order_email)) {
					$mailer->send($order_email, $subject, $message, $headers, $attachments);
				}
			}
		}
	}
}

// use printshop theme
add_action('plugins_loaded', 'theme_per_user_change_theme');
function theme_per_user_change_theme() {
	add_filter('template', 'print_products_users_groups_group_theme');
	add_filter('stylesheet', 'print_products_users_groups_group_theme');
	add_filter('option_current_theme', 'print_products_users_groups_group_theme');
	add_filter('option_template', 'print_products_users_groups_group_theme');
	add_filter('option_stylesheet', 'print_products_users_groups_group_theme');
}

function print_products_users_groups_group_theme($template) {
	global $current_user_group;
	if (!is_admin() && $current_user_group) {
		if ($current_user_group->use_printshop == 1) {
			$template = 'printshop';
		} else if ($current_user_group->use_privatestore == 1) {
			$template = 'private-store';
		}
	}
	return $template;
}

add_action('setup_theme', 'print_products_users_groups_setup_theme');
function print_products_users_groups_setup_theme() {
	global $current_user_group;
	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		$current_user_group = print_products_users_groups_get_user_group($user->ID);
	}
}

add_action('updated_option', 'print_products_users_groups_updated_option', 20, 3);
function print_products_users_groups_updated_option($option, $old_value, $value) {
	if ($option == 'sidebars_widgets') {
		$currtheme = get_option('stylesheet');
		update_option($currtheme.'_sidebars_widgets', $value);
	}
}

add_filter('sidebars_widgets', 'print_products_users_groups_sidebars_widgets');
function print_products_users_groups_sidebars_widgets($sidebars_widgets) {
	global $current_user_group;
	if (!is_admin() && $current_user_group && $current_user_group->use_printshop) {
		$currtheme_sidebars_widgets = get_option('printshop_sidebars_widgets');
		if ($currtheme_sidebars_widgets) {
			$sidebars_widgets = array();
			foreach($currtheme_sidebars_widgets as $sidebar => $widgets) {
				if (is_array($widgets)) {
					$sidebars_widgets[$sidebar] = $widgets;
				}
			}
		}
	}
	return $sidebars_widgets;
}

add_filter('wc_customer_order_xml_export_suite_format_field_data_options', 'print_products_order_xml_export_suite_format_field_data_options', 10, 2);
function print_products_order_xml_export_suite_format_field_data_options($options, $export_type) {
	if ($export_type == 'orders') {
		if (!in_array('TaxID', $options)) {
			$options[] = 'TaxID';
		}
	}
	return $options;
}

add_filter('wc_customer_order_xml_export_suite_order_data', 'print_products_order_xml_export_suite_order_data', 10, 2);
function print_products_order_xml_export_suite_order_data($order_data, $order) {
	$order_data['TaxID'] = get_post_meta($order->id, '_tax_id', true);
	return $order_data;
}

add_filter('woocommerce_is_purchasable', 'print_products_users_groups_is_purchasable', 10, 2);
function print_products_users_groups_is_purchasable($purchasable, $product) {
	global $current_user_group, $current_user;
	if ($current_user_group) {
		$invoice_zero = $current_user_group->invoice_zero;
		if ($invoice_zero) {
			$product_type = $product->get_type();
			if (print_products_is_wp2print_type($product_type)) {
				$purchasable = true;
			}
		}
	}
	return $purchasable;
}

add_action('user_register', 'print_products_users_groups_user_register', 11);
function print_products_users_groups_user_register($user_id) {
	global $wpdb;
	$user = new WP_User($user_id);
	if ($user) {
		$user_email_data = explode('@', $user->user_email);
		$user_email_domain = $user_email_data[1];

		$user_group_id = $wpdb->get_var(sprintf("SELECT group_id FROM %sprint_products_users_groups WHERE assign_new_user = 1", $wpdb->prefix));
		if (!$user_group_id) {
			$user_group_id = $wpdb->get_var(sprintf("SELECT group_id FROM %sprint_products_users_groups WHERE aregister_domain = '%s'", $wpdb->prefix, $user_email_domain));
		}

		if ($user_group_id) {
			update_usermeta($user_id, '_user_group', $user_group_id);
		}
	}
}

add_action('wp_login', 'print_products_users_groups_wp_login', 11, 2);
function print_products_users_groups_wp_login($user_login, $user) {
	if (!isset($_POST['external_login']) && !isset($_POST['external_register'])) {
		$user_id = $user->ID;
		$user_group = get_user_meta($user_id, '_user_group', true);
		if ($user_group) {
			$group_data = print_products_users_groups_data($user_group);
			if ($group_data) {
				$logout_redirect = $group_data->logout_redirect;
				$login_code_required = $group_data->login_code_required;
				if ($login_code_required) {
					if (!strlen($logout_redirect)) { $logout_redirect = home_url('/'); }
					wp_logout();
					wp_die(__('Login code is required for login.', 'wp2print'), 'Login Code Error', array('response' => 400));
				}
			}
		}
	}
}

function print_products_users_groups_external_login() {
	$login_username = trim($_POST['login_username']);
	$login_password = trim($_POST['login_password']);
	$login_code = trim($_POST['login_code']);

	if (strlen($login_username) && strlen($login_password) && strlen($login_code)) {
		$userdata = get_user_by('login', $login_username);
		if (!$userdata) { $userdata = get_user_by('email', $login_username); }
		if ($userdata) {
			$user_id = $userdata->ID;

			$user_group = get_user_meta($user_id, '_user_group', true);
			if ($user_group) {
				$group_data = print_products_users_groups_data($user_group);
				if ($group_data) {
					$login_process = true;
					$login_code_required = $group_data->login_code_required;
					$login_redirect = $group_data->login_redirect;
					if (!strlen($login_redirect)) { $login_redirect = home_url('/'); }
					if ($login_code_required) {
						$login_process = false;
						if ($login_code == $group_data->login_code) {
							$login_process = true;
						}
					}
					if ($login_process) {
						$user_login = $userdata->user_login;

						$creds = array();
						$creds['user_login'] = $user_login;
						$creds['user_password'] = $login_password;
						$creds['remember'] = false;
						$user = wp_signon($creds, false);
						if (!is_wp_error($user)) {
							wp_set_current_user($user_id, $user_login);
							wp_set_auth_cookie($user_id);
							wp_redirect($login_redirect);
							exit;
						}
					}
				}
			}
		}
	}
	wp_die(__('Login is rejected.', 'wp2print'), 'External Login Error', array('response' => 400));
}

function print_products_users_groups_external_register() {
	global $wpdb;
	$register_fname = trim($_POST['register_fname']);
	$register_lname = trim($_POST['register_lname']);
	$register_username = trim($_POST['register_username']);
	$register_password = trim($_POST['register_password']);
	$register_email = trim($_POST['register_email']);
	$register_code = trim($_POST['register_code']);

	if (strlen($register_username) && strlen($register_password) && strlen($register_email) && is_email($register_email) && strlen($register_code)) {
		$user_by_login = get_user_by('login', $register_username);
		$user_by_email = get_user_by('email', $register_email);
		if (!$user_by_login && !$user_by_email) {
			$group_id = $wpdb->get_var(sprintf("SELECT group_id FROM %sprint_products_users_groups WHERE login_code = '%s'", $wpdb->prefix, $register_code));
			if ($group_id) {
				$userdata = array();
				$userdata['role'] = 'subscriber';
				$userdata['user_login'] = $register_username;
				$userdata['user_pass'] = $register_password;
				$userdata['user_email'] = $register_email;
				$userdata['first_name'] = $register_fname;
				$userdata['last_name'] = $register_lname;
				$user_id = wp_insert_user($userdata);
				update_user_meta($user_id, '_user_group', $group_id);

				$_POST['login_username'] = $register_username;
				$_POST['login_password'] = $register_password;
				$_POST['login_code'] = $register_code;
				print_products_users_groups_external_login();
			}
		}
	}
}

add_action('wp_footer', 'print_products_users_groups_wp_footer');
function print_products_users_groups_wp_footer() {
	global $current_user_group;
	if (is_checkout() && $current_user_group) {
		$addresses_js = '';
		$billing_count = 0;
		$shipping_count = 0;
		$billing_addresses = unserialize($current_user_group->billing_addresses);
		$shipping_addresses = unserialize($current_user_group->shipping_addresses);

		if ($billing_addresses) {
			foreach($billing_addresses as $akey => $address) {
				if ($address['active'] == 1) {
					$aline = $address['fname'].'|'.$address['lname'].'|'.$address['company'].'|'.$address['country'].'|'.$address['address'].'|'.$address['address2'].'|'.$address['city'].'|'.$address['state'].'|'.$address['zip'].'|'.$address['phone'].'|'.$address['email'];
					$addresses_js .= 'wp2print_billing_address['.$akey.'] = "'.$aline.'";'.chr(10);
					$billing_count++;
				}
			}
		}
		if ($shipping_addresses) {
			foreach($shipping_addresses as $akey => $address) {
				if ($address['active'] == 1) {
					$aline = $address['fname'].'|'.$address['lname'].'|'.$address['company'].'|'.$address['country'].'|'.$address['address'].'|'.$address['address2'].'|'.$address['city'].'|'.$address['state'].'|'.$address['zip'];
					$addresses_js .= 'wp2print_shipping_address['.$akey.'] = "'.$aline.'";'.chr(10);
					$shipping_count++;
				}
			}
		}

		if (strlen($addresses_js)) { ?>
			<script type="text/javascript">
			var wp2print_billing_address = new Array();
			var wp2print_shipping_address = new Array();
			<?php echo $addresses_js; ?>
			<?php if ($billing_count || $shipping_count) { ?>
			jQuery(document).ready(function() {
				<?php if ($billing_count == 1) { ?>
				for (var akey in wp2print_billing_address) {
					wp2print_set_billing_address(akey);
				}
				<?php } ?>
				<?php if ($shipping_count == 1) { ?>
				for (var akey in wp2print_shipping_address) {
					wp2print_set_shipping_address(akey);
				}
				<?php } ?>
			});
			<?php } ?>
			</script>
			<?php
		}
	}
}

add_filter('woocommerce_checkout_fields', 'print_products_users_groups_checkout_fields');
function print_products_users_groups_checkout_fields($fields) {
	global $current_user_group, $current_user;
	if ($current_user_group) {
		$baddresses = unserialize($current_user_group->billing_addresses);
		$saddresses = unserialize($current_user_group->shipping_addresses);

		if ($baddresses) {
			$billing_addresses = array();
			$billing_addresses[0] = '-- '.__('Select address', 'wp2print').' --';
			foreach($baddresses as $akey => $address) {
				if ($address['active'] == 1) {
					$billing_addresses[$akey] = $address['label'];
				}
			}
			if (count($billing_addresses) > 2) {
				$billing_addresses_field = array(
					'label'    => __('Predefined addresses', 'wp2print'),
					'required' => false,
					'class'    => array('form-row'),
					'clear'    => true,
					'type'     => 'select',
					'options'  => $billing_addresses
				);
				$fields['billing'] = print_products_users_groups_array_unshift_assoc($fields['billing'], 'wp2print_billing_addresses', $billing_addresses_field);
			}
		}

		if ($saddresses) {
			$shipping_addresses = array();
			$shipping_addresses[0] = '-- '.__('Select address', 'wp2print').' --';
			foreach($saddresses as $akey => $address) {
				if ($address['active'] == 1) {
					$shipping_addresses[$akey] = $address['label'];
				}
			}
			if (count($shipping_addresses) > 2) {
				$shipping_addresses_field = array(
					'label'    => __('Predefined addresses', 'wp2print'),
					'required' => false,
					'class'    => array('form-row'),
					'clear'    => true,
					'type'     => 'select',
					'options'  => $shipping_addresses
				);
				$fields['shipping'] = print_products_users_groups_array_unshift_assoc($fields['shipping'], 'wp2print_shipping_addresses', $shipping_addresses_field);
			}
		}

	}
	return $fields;
}

function print_products_users_groups_array_unshift_assoc(&$arr, $key, $val) {
	$arr         = array_reverse( $arr, true );
	$arr[ $key ] = $val;

	return array_reverse( $arr, true );
}

// ascend site actions
add_action( 'wp_loaded', function(){
	global $ascend;
	if ($ascend) {
		if (is_user_logged_in()) {
			$user = wp_get_current_user();
			$current_user_group = print_products_users_groups_get_user_group($user->ID);
			if ($ascend && $current_user_group) {
				$theme = unserialize($current_user_group->theme);
				if (strlen($theme['logo'])) {
					remove_action('kadence_start_vertical_header', 'ascend_the_custom_logo', 10);
					remove_action('kadence_below_logo_header_center', 'ascend_the_custom_logo', 20);
					remove_action('kadence_center_logo_header_center', 'ascend_the_custom_logo', 20);
					remove_action('kadence_center_extras_header_left', 'ascend_the_custom_logo', 20);
					remove_action('kadence_header_left', 'ascend_the_custom_logo', 20);

					add_action('kadence_start_vertical_header', 'print_products_users_groups_ascend_logo', 20);
					add_action('kadence_below_logo_header_center', 'print_products_users_groups_ascend_logo', 20);
					add_action('kadence_center_logo_header_center', 'print_products_users_groups_ascend_logo', 20);
					add_action('kadence_center_extras_header_left', 'print_products_users_groups_ascend_logo', 20);
					add_action('kadence_header_left', 'print_products_users_groups_ascend_logo', 20);
				}
			}
		}
	}
});

function print_products_users_groups_ascend_logo() {
	global $ascend, $current_user;
	$current_user_group = print_products_users_groups_get_user_group($current_user->ID);
	$theme = unserialize($current_user_group->theme);

	echo '<div id="logo" class="logocase kad-header-height">';
		echo '<a class="brand logofont" href="'.esc_url(apply_filters('kadence_logo_link', home_url('/'))).'">';
		$liu = '';
		if(isset($ascend['logo']['id']) && !empty($ascend['logo']['id'])) {
			if(isset($ascend['logo_width']) && !empty($ascend['logo_width'])) {
				$width = $ascend['logo_width'];
			} else {
				$width = 300;
			}
			$width = apply_filters('kadence_logo_width', $width);
			$alt = get_bloginfo('name');
			$img = ascend_get_image($width, null, false, 'ascend-logo', $alt, $ascend['logo']['id'], false);
			$img_src = $theme['logo'];

			echo '<img src="'.esc_url($img_src).'" width="'.esc_attr($img['width']).'" height="'.esc_attr($img['height']).'" class="'.esc_attr($img['class']).'" style="max-height:'.esc_attr($img['height']).'px" alt="'.esc_attr($img['alt']).'">';
			if(isset($ascend['trans_logo']['id']) && !empty($ascend['trans_logo']['id'])) {
				$img = ascend_get_image($width, null, false, 'ascend-trans-logo', $alt, $ascend['trans_logo']['id'], false);
				echo '<img src="'.esc_url($img_src).'" width="'.esc_attr($img['width']).'" height="'.esc_attr($img['height']).'" class="'.esc_attr($img['class']).'" style="max-height:'.esc_attr($img['height']).'px" alt="'.esc_attr($img['alt']).'">';
			}
			$liu = 'kad-logo-used';
		}
		if(isset($ascend['site_title']) && $ascend['site_title'] == 1) {
			echo '<span class="kad-site-title '.$liu.'">';
			echo apply_filters('kad_site_name', get_bloginfo('name')); 
			if(isset($ascend['site_tagline']) && $ascend['site_tagline'] == 1) {
				echo '<span class="kad-site-tagline">';
				echo apply_filters('kad_site_tagline', get_bloginfo('description'));
				echo '</span>';
			}
			echo '</span>';
		} else if( isset( $ascend[ 'site_tagline' ] ) && 1 == $ascend[ 'site_tagline' ] &&  isset( $ascend[ 'site_title' ] ) && 0 == $ascend[ 'site_title' ] ) {
			echo '<span class="kad-site-title '.$liu.'">';
				echo '<span class="kad-site-tagline">';
				echo apply_filters('kad_site_tagline', get_bloginfo('description'));
				echo '</span>';
			echo '</span>';
		}
		echo '</a>';
	echo '</div>';
}

function print_products_users_groups_get_user_accounting_id($user_id) {
	$accounting_id = '';
	$user_group = print_products_users_groups_get_user_group($user_id);
	if ($user_group) {
		if (strlen($user_group->accounting_id)) {
			$accounting_id = $user_group->accounting_id;
		}
	}
	if (!strlen($accounting_id)) {
		$user_accounting_id = get_user_meta($user_id, '_user_accounting_id', true);
		if (strlen($user_accounting_id)) {
			$accounting_id = $user_accounting_id;
		}
	}
	return $accounting_id;
}

// Group order history
add_filter('woocommerce_account_menu_items', 'print_products_users_groups_account_menu_items', 25);
function print_products_users_groups_account_menu_items($items) {
	global $current_user;
	$is_superuser = print_products_users_groups_is_superuser($current_user->ID);
	$new_items = array();
	foreach($items as $ikey => $ival) {
		$new_items[$ikey] = $ival;
		if ($ikey == 'orders' && $is_superuser) {
			$new_items['group-orders'] = __('Group order history', 'wp2print');
		}
	}
	return $new_items;
}

add_filter('woocommerce_get_endpoint_url', 'print_products_users_groups_get_endpoint_url', 11, 3);
function print_products_users_groups_get_endpoint_url($url, $endpoint, $value) {
	if ($endpoint == 'group-orders') {
		$url = str_replace('group-orders/', 'orders/?allgroup=true', $url);
	}
	if ($endpoint == 'orders' && $value && isset($_REQUEST['allgroup'])) {
		if (strpos($url, '?')) {
			$url = $url . '&allgroup=true';
		} else {
			$url = $url . '?allgroup=true';
		}
	}
	return $url;
}

add_action('woocommerce_before_account_orders', 'print_products_users_groups_before_account_orders');
function print_products_users_groups_before_account_orders($has_orders) {
	global $current_user;
	$page_url = wc_get_endpoint_url('orders');
	$is_superuser = print_products_users_groups_is_superuser($current_user->ID);
	if ($is_superuser) {
		if (isset($_REQUEST['allgroup']) && $_REQUEST['allgroup'] == 'true') { ?>
			<a href="<?php echo $page_url; ?>" class="button alt" style="margin-bottom:20px;"><?php _e('Display all my orders', 'wp2print'); ?></a><br>
		<?php } else { ?>
			<a href="<?php echo $page_url; ?>?allgroup=true" class="button alt" style="margin-bottom:20px;"><?php _e('Display orders for all group members', 'wp2print'); ?></a>
			<?php
		}
	}
}

add_filter('woocommerce_order_query_args', 'print_products_users_groups_order_query_args');
function print_products_users_groups_order_query_args($args) {
	global $current_user;
	$is_superuser = print_products_users_groups_is_superuser($current_user->ID);
	if ($is_superuser && isset($_REQUEST['allgroup']) && $_REQUEST['allgroup'] == 'true') {
		$args['customer'] = print_products_users_groups_get_group_users($is_superuser);
	}
	return $args;
}

add_filter('user_has_cap', 'print_products_users_groups_user_has_cap', 11, 4);
function print_products_users_groups_user_has_cap($allcaps, $caps, $args, $user) {
	global $wpdb;
	if (!isset($allcaps['view_order']) && isset($args[0]) && $args[0] == 'view_order') {
		if (count($args) == 3) {
			$order_id = $args[2];
		} else {
			$order_id = $args[1];
		}
		$group_id = print_products_users_groups_is_superuser($user->ID);
		if ($group_id) {
			$group_users = print_products_users_groups_get_group_users($group_id);
			if ($group_users) {
				$order_user = (int)$wpdb->get_var(sprintf("SELECT meta_value FROM %spostmeta WHERE post_id = %s AND meta_key = '_customer_user'", $wpdb->prefix, $order_id));
				if ($order_user && in_array($order_user, $group_users)) {
					$allcaps['view_order'] = true;
				}
			}
		}
	}
	return $allcaps;
}

add_filter('woocommerce_coupons_enabled', 'print_products_users_groups_coupons_enabled', 11);
function print_products_users_groups_coupons_enabled($enabled) {
	global $current_user_group;
	if ($current_user_group) {
		$options = unserialize($current_user_group->options);
		if ($options['hide_coupon_field'] == 1) {
			$enabled = false;
		}
	}
	return $enabled;
}

// ----------------------------------------
// 'Import users from CSV with meta' plugin
// ----------------------------------------
add_action('acui_tab_import_before_import_button', 'print_products_users_groups_import_users_groups_dropdown');
function print_products_users_groups_import_users_groups_dropdown() {
	global $wpdb;
	$users_groups = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_users_groups ORDER BY group_name", $wpdb->prefix));
	if ($users_groups) { ?>
		<table class="form-table">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="users_group"><?php _e( 'Assign all users to group', 'wp2print' ); ?></label></th>
					<td>
						<select name="print_products_users_group">
							<option value="">-- <?php _e( 'Select Group', 'wp2print' ); ?> --</option>
							<?php foreach($users_groups as $users_group) { ?>
								<option value="<?php echo $users_group->group_id; ?>"><?php echo $users_group->group_name; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
}

add_action('post_acui_import_single_user', 'print_products_users_groups_import_users_process', 11, 3);
function print_products_users_groups_import_users_process($headers, $data, $user_id) {
	global $wpdb;
	$print_products_users_group = (int)$_POST['print_products_users_group'];
	if ($print_products_users_group) {
		update_user_meta($user_id, '_user_group', $print_products_users_group);
	}
}
// ----------------------------------------
?>