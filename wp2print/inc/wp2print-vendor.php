<?php
$print_products_vendor_options = get_option('print_products_vendor_options');
$print_products_employee_options = get_option('print_products_employee_options');
$print_products_vendor_companies = get_option('print_products_vendor_companies');
$print_products_prodview_options = get_option('print_products_prodview_options');

add_action('wp_loaded', 'print_products_vendor_actions');
function print_products_vendor_actions() {
	if (isset($_POST['AjaxAction'])) {
		if ($_POST['AjaxAction'] == 'vendor-company-action') {
			$vc_id = (int)$_POST['vc_id'];
			$print_products_vendor_companies = get_option('print_products_vendor_companies');
			if (!is_array($print_products_vendor_companies)) { $print_products_vendor_companies = array(); }
			if ($vc_id) {
				switch($_POST['vc_atype']) {
					case 'add':
					case 'update':
						$print_products_vendor_companies[$vc_id] = array(
							'name' => trim($_POST['vc_name']),
							'address1' => trim($_POST['vc_address1']),
							'address2' => trim($_POST['vc_address2']),
							'city' => trim($_POST['vc_city']),
							'postcode' => trim($_POST['vc_postcode']),
							'state' => trim($_POST['vc_state']),
							'country' => trim($_POST['vc_country']),
							'email' => trim($_POST['vc_email']),
							'send' => (int)$_POST['vc_send'],
							'employees' => explode(',', $_POST['vc_employees']),
							'access' => (int)$_POST['vc_access']
						);
					break;
					case 'delete':
						unset($print_products_vendor_companies[$vc_id]);
					break;
				}
				update_option('print_products_vendor_companies', $print_products_vendor_companies);
			}
			exit;
		} else if ($_POST['AjaxAction'] == 'oi-vendor-unassign') {
			$item_id = (int)$_POST['item_id'];
			if ($item_id) {
				wc_update_order_item_meta($item_id, '_item_vendor_employee', '0');
			}
			exit;
		} else if ($_POST['AjaxAction'] == 'vendor-assign-to-me') {
			print_products_vendor_assign_to_me_submit();
			exit;
		}
	}
}

function print_products_vendor_get_vendors() {
	$vendors = array();
	$vusers = get_users(array('role__in' => array('vendor', 'adminlite', 'administrator'), 'orderby' => 'display_name'));
	if ($vusers) {
		foreach($vusers as $vuser) {
			$vendors[$vuser->ID] = $vuser->display_name;
		}
	}
	return $vendors;
}

function print_products_vendor_get_employees() {
	$employees = array();
	$vusers = get_users(array('role__in' => array('vendor', 'adminlite', 'administrator'), 'orderby' => 'display_name'));
	if ($vusers) {
		foreach($vusers as $vuser) {
			$employees[$vuser->ID] = array('name' => $vuser->display_name, 'email' => $vuser->user_email, 'phone' => get_user_meta($vuser->ID, 'billing_phone', true));
		}
	}
	return $employees;
}

function print_products_vendor_is_vendor() {
	global $current_user;
	if (in_array('vendor', $current_user->roles)) {
		return true;
	}
	return false;
}

function print_products_vendor_get_option($okey) {
	global $print_products_vendor_options;
	return $print_products_vendor_options[$okey];
}

function print_products_vendor_get_companies() {
	global $print_products_vendor_companies;
	return $print_products_vendor_companies;
}

function print_products_vendor_get_company($cid) {
	global $print_products_vendor_companies;
	return $print_products_vendor_companies[$cid];
}

function print_products_vendor_get_user_company($user_id) {
	global $print_products_vendor_companies;
	if ($print_products_vendor_companies) {
		foreach($print_products_vendor_companies as $vcid => $vendor_company) {
			if ($vendor_company['employees'] && is_array($vendor_company['employees'])) {
				if (in_array($user_id, $vendor_company['employees'])) {
					$vendor_company['id'] = $vcid;
					return $vendor_company;
				}
			}
		}
	}
	return false;
}

function print_products_vendor_allow() {
    global $print_products_vendor_companies, $current_user;
	$vendor_allow = false;
	if (in_array('administrator', $current_user->roles)) {
		$vendor_allow = true;
	} else if (in_array('adminlite', $current_user->roles)) {
		$vendor_allow = true;
	} else if (in_array('vendor', $current_user->roles)) {
		if ($print_products_vendor_companies) {
			foreach($print_products_vendor_companies as $vendor_company) {
				if ($vendor_company['access'] == 1 && is_array($vendor_company['employees']) ) {
					if (in_array($current_user->ID, $vendor_company['employees'])) {
						$vendor_allow = true;
					}
				}
			}
		}
	}
	return $vendor_allow;
}

function print_products_vendor_allow_for_item($item_id) {
    global $current_user;
	if (print_products_vendor_is_vendor()) {
		$vendor_company = print_products_vendor_get_user_company($current_user->ID);
		if ($vendor_company) {
			$item_vendor = (int)wc_get_order_item_meta($item_id, '_item_vendor', true);
			if ($item_vendor && $item_vendor == $vendor_company['id']) {
				return true;
			}
		}
	} else if (in_array('administrator', $current_user->roles) || in_array('adminlite', $current_user->roles)) {
		return true;
	}
	return false;
}

function print_products_vendor_allow_assign_to_me() {
	global $print_products_vendor_options, $current_user;
	if ($print_products_vendor_options['show_assign_to_me'] && print_products_vendor_allow()) {
		return true;
	}
	return false;
}

function print_products_vendor_assign_to_me_popup_html() {
	global $current_user;
	if (print_products_vendor_allow_assign_to_me()) {
		$vendor_company = print_products_vendor_get_user_company($current_user->ID); ?>
		<div style="display:none;">
			<div id="vatm-popup" class="vatm-popup" data-vendor-id="<?php echo $current_user->ID; ?>" data-company-id="<?php if ($vendor_company) { echo $vendor_company['id']; } ?>">
				<h2><?php _e('Assigning order items to vendor', 'wp2print'); ?></h2>
				<div class="ois-i-options">
					<ul>
						<li><input type="radio" name="voi_option" value="0" checked><?php _e('Assign only this item', 'wp2print'); ?></li>
						<li><input type="radio" name="voi_option" value="1"><?php _e('Assign all items in order', 'wp2print'); ?></li>
					</ul>
				</div>
				<input type="button" value="<?php _e('Submit', 'wp2print'); ?>" class="button-primary" onclick="wp2print_employee_assign_to_me_submit()">
			</div>
		</div>
		<?php
	}
}

function print_products_vendor_assign_to_me_submit() {
	global $current_user;
	$order_id = (int)$_POST['order_id'];
	$item_id = (int)$_POST['item_id'];
	$poption = (int)$_POST['poption'];
	$vendor_company = print_products_vendor_get_user_company($current_user->ID);
	if ($order_id && $item_id) {
		$order = wc_get_order($order_id);
		if ($poption) {
			$order_items = $order->get_items();
			foreach($order_items as $order_item_id => $order_item) {
				wc_update_order_item_meta($order_item_id, '_item_vendor_employee', $current_user->ID);
				if ($vendor_company) {
					wc_update_order_item_meta($order_item_id, '_item_vendor', $vendor_company['id']);
				}
			}
		} else {
			wc_update_order_item_meta($item_id, '_item_vendor_employee', $current_user->ID);
			if ($vendor_company) {
				wc_update_order_item_meta($item_id, '_item_vendor', $vendor_company['id']);
			}
		}
	}
	if ($vendor_company) {
		print_products_vendor_set_order_vendor($order_id, array($vendor_company));
	}
}

add_action('add_meta_boxes', 'print_products_vendor_add_meta_boxes', 11);
function print_products_vendor_add_meta_boxes() {
	global $print_products_vendor_companies;
	if (print_products_vendor_allow()) {
		add_meta_box('order-employees-box', __('Employee Assignment', 'wp2print'), 'print_products_employees_meta_box', 'shop_order', 'normal');
		if ($print_products_vendor_companies && is_array($print_products_vendor_companies)) {
			add_meta_box('order-vendors-box', __('Vendor Information', 'wp2print'), 'print_products_vendor_meta_box', 'shop_order', 'normal');
		}
	}
}

function print_products_employees_meta_box() {
	global $post, $current_user;

	$order_id = $post->ID;
	$order_data = wc_get_order($order_id);
	$order_items = $order_data->get_items('line_item');

	$vendors = print_products_vendor_get_vendors();

	if ($order_items && $vendors) { ?>
		<div class="ppv-area">
			<table class="woocommerce_order_items employee-assign-table" width="100%">
				<thead>
					<tr>
						<th><?php _e('Item', 'wp2print'); ?></th>
						<th><?php _e('Employee', 'wp2print'); ?></th>
						<?php if (print_products_vendor_allow_assign_to_me()) { ?><th>&nbsp;</th><?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach($order_items as $item_id => $item) {
						$product = $item->get_product();
						$product_link = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '';
						$item_vendor_employee = wc_get_order_item_meta($item_id, '_item_vendor_employee', true);
						?>
						<tr><td colspan="3"><hr></td></tr>
						<tr class="e-oitem e-oitem-<?php echo $item_id; ?>">
							<td valign="top">
								<a href="<?php echo $product_link; ?>" class="wc-order-item-name"><?php echo wp_kses_post($item->get_name()); ?></a>
								<?php do_action( 'woocommerce_before_order_itemmeta', $item_id, $item, $product ); ?>
							</td>
							<td valign="top">
								<select name="item_vendor_employee[<?php echo $item_id; ?>]" class="order-item-employee">
									<option value="">- <?php _e('Select Employee', 'wp2print'); ?> -</option>
									<?php foreach($vendors as $v_id => $v_name) { ?>
										<option value="<?php echo $v_id; ?>"<?php if ($v_id == $item_vendor_employee) { echo ' SELECTED'; } ?>><?php echo $v_name; ?></option>
									<?php } ?>
								</select>
								<div class="oive-success"><?php _e('Updated.', 'wp2print'); ?></div>
							</td>
							<?php if (print_products_vendor_allow_assign_to_me()) { ?><td valign="top"><?php if ($current_user->ID != $item_vendor_employee) { ?><input type="button" value="<?php _e('Assign to me', 'wp2print'); ?>" class="button" onclick="wp2print_employee_assign_to_me(<?php echo $order_id; ?>, <?php echo $item_id; ?>);"><?php } ?></td><?php } ?>
					<?php } ?>
				</tbody>
			</table>
			<div class="ppv-submit"><input type="submit" value="<?php _e('Update', 'wp2print'); ?>" class="button button-primary"></div>
		</div>
		<?php
	}
}

function print_products_vendor_meta_box() {
	global $post, $print_products_vendor_companies;

	$order_id = $post->ID;
	$order_data = wc_get_order($order_id);
	$order_items = $order_data->get_items('line_item');

	$vendor_companies = $print_products_vendor_companies;

	$customer_address = print_products_vendor_get_address($order_id, 'customer');
	$company_address = print_products_vendor_get_address($order_id, 'company');

	$decimal_separator  = wc_get_price_decimal_separator();
	$thousand_separator = wc_get_price_thousand_separator();
	$decimals           = wc_get_price_decimals();
	$currency_symbol = get_woocommerce_currency_symbol();

	if ($order_items && $vendor_companies) { ?>
		<div class="ppv-area">
			<table class="woocommerce_order_items vendor-assign-table" width="100%">
				<thead>
					<tr>
						<th><?php _e('Item', 'wp2print'); ?></th>
						<th><?php _e('Vendor', 'wp2print'); ?></th>
						<th><?php _e('Price', 'wp2print'); ?></th>
						<th><?php _e('Delivery date', 'wp2print'); ?></th>
						<th><?php _e('Purchase order', 'wp2print'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($order_items as $item_id => $item) {
						$product = $item->get_product();
						$product_link = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '';
						$item_vendor = wc_get_order_item_meta($item_id, '_item_vendor', true);
						$item_vendor_address = wc_get_order_item_meta($item_id, '_item_vendor_address', true);
						$item_vendor_price = wc_get_order_item_meta($item_id, '_item_vendor_price', true);
						$item_vendor_date = wc_get_order_item_meta($item_id, '_item_vendor_date', true);
						$item_vendor_order = wc_get_order_item_meta($item_id, '_item_vendor_order', true);
						if (!$item_vendor_address) { $item_vendor_address = 'customer'; }
						if (!strlen($item_vendor_order)) { $item_vendor_order = $order_id; }
						if ($item_vendor_price) {
							$item_vendor_price = number_format($item_vendor_price, $decimals, $decimal_separator, $thousand_separator);
						}
						?>
						<tr><td colspan="5"><hr></td></tr>
						<tr class="v-order-item-<?php echo $item_id; ?>">
							<td valign="top">
								<a href="<?php echo $product_link; ?>" class="wc-order-item-name"><?php echo wp_kses_post($item->get_name()); ?></a>
								<?php do_action( 'woocommerce_before_order_itemmeta', $item_id, $item, $product ); ?>
							</td>
							<td valign="top" style="width:160px;">
								<select name="item_vendor[<?php echo $item_id; ?>]" class="order-item-vendor" rel="<?php echo $item_id; ?>">
									<option value="">- <?php _e('Select Vendor', 'wp2print'); ?> -</option>
									<?php foreach($vendor_companies as $vcid => $vendor_company) { ?>
										<option value="<?php echo $vcid; ?>"<?php if ($vcid == $item_vendor) { echo ' SELECTED'; } ?>><?php echo $vendor_company['name']; ?></option>
									<?php } ?>
								</select>
								<div class="order-vendor-address"<?php if (!$item_vendor) { echo ' style="display:none;"'; } ?>>
									<div class="customer-address">
										<input type="radio" name="item_vendor_address[<?php echo $item_id; ?>]" value="customer" class="ovendor-address" rel="<?php echo $item_id; ?>"<?php if ($item_vendor_address == 'customer') { echo ' CHECKED'; } ?>><?php _e('Dropship to customer', 'wp2print'); ?>
										<div class="address-line"<?php if ($item_vendor_address != 'customer') { echo ' style="display:none;"'; } ?>><?php echo $customer_address; ?></div>
									</div>
									<div class="vendor-address">
										<input type="radio" name="item_vendor_address[<?php echo $item_id; ?>]" value="vendor" class="ovendor-address" rel="<?php echo $item_id; ?>"<?php if ($item_vendor_address == 'vendor') { echo ' CHECKED'; } ?>><?php _e('Ship to company', 'wp2print'); ?>
										<div class="address-line"<?php if ($item_vendor_address != 'vendor') { echo ' style="display:none;"'; } ?>><?php echo $company_address; ?></div>
									</div>
								</div>
							</td>
							<td valign="top" nowrap><?php echo get_woocommerce_currency_symbol(); ?> <input type="text" name="item_vendor_price[<?php echo $item_id; ?>]" value="<?php echo $item_vendor_price; ?>" class="i-v-price" style="width:80px;"></td>
							<td valign="top"><input type="text" name="item_vendor_date[<?php echo $item_id; ?>]" value="<?php echo $item_vendor_date; ?>" class="i-v-date"></td>
							<td valign="top"><input type="text" name="item_vendor_order[<?php echo $item_id; ?>]" value="<?php echo $item_vendor_order; ?>" class="i-v-order"></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="ppv-submit"><input type="submit" value="<?php _e('Update', 'wp2print'); ?>" class="button button-primary"></div>
		</div>
		<?php
	}
}

$order_vendor_item = false;
add_action('save_post', 'print_products_vendor_save_post', 11, 2); 
function print_products_vendor_save_post($post_id, $post){
	global $order_vendor_item;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}
	if ($post->post_type == 'shop_order' && isset($_POST['item_vendor'])) {
		$post_item_vendor = $_POST['item_vendor'];
		$post_item_vendor_employee = $_POST['item_vendor_employee'];
		$post_item_vendor_address = $_POST['item_vendor_address'];
		$post_item_vendor_price = $_POST['item_vendor_price'];
		$post_item_vendor_date = $_POST['item_vendor_date'];
		$post_item_vendor_order = $_POST['item_vendor_order'];

		$order_data = wc_get_order($post_id);
		$order_items = $order_data->get_items('line_item');
		$vendor_companies = print_products_vendor_get_companies();

		if ($order_items) {
			$ivendors = array();
			foreach($order_items as $item_id => $item) {
				$item_vendor = (int)$post_item_vendor[$item_id];
				$item_vendor_employee = (int)$post_item_vendor_employee[$item_id];
				$item_vendor_address = $post_item_vendor_address[$item_id];
				$item_vendor_price = $post_item_vendor_price[$item_id];
				$item_vendor_date = $post_item_vendor_date[$item_id];
				$item_vendor_order = $post_item_vendor_order[$item_id];
				if (!$item_vendor_order) { $item_vendor_order = $post_id; }

				$old_item_vendor = wc_get_order_item_meta($item_id, '_item_vendor', true);

				wc_update_order_item_meta($item_id, '_item_vendor', $item_vendor);
				wc_update_order_item_meta($item_id, '_item_vendor_employee', $item_vendor_employee);
				wc_update_order_item_meta($item_id, '_item_vendor_address', $item_vendor_address);
				wc_update_order_item_meta($item_id, '_item_vendor_price', $item_vendor_price);
				wc_update_order_item_meta($item_id, '_item_vendor_date', $item_vendor_date);
				wc_update_order_item_meta($item_id, '_item_vendor_order', $item_vendor_order);

				if ($item_vendor && !in_array($item_vendor, $ivendors)) { $ivendors[] = $item_vendor; }

				if ($item_vendor && $old_item_vendor != $item_vendor) {
					$order_vendor_item = $item_id;
					print_products_vendor_send_email_to_vendor($item_vendor, $order_data, $item_vendor_order, $item_vendor_price);
					$order_vendor_item = false;
				}
			}
			// set order to vendors
			delete_post_meta($post_id, '_order_vendor');
			print_products_vendor_set_order_vendor($post_id, $ivendors);
		}
	}
}

function print_products_vendor_set_order_vendor($order_id, $ivendors) {
	if ($ivendors && count($ivendors)) {
		$ivendors = array_unique($ivendors);
		foreach($ivendors as $ivendor) {
			add_post_meta($order_id, '_order_vendor', $ivendor);
		}
	}
}

function print_products_vendor_send_email_to_vendor($item_vendor, $order_data, $item_vendor_order = 0, $item_vendor_price = 0) {
	$vendor_companies = print_products_vendor_get_companies();
	$email_subject = print_products_vendor_get_option('email_subject');

	$order_id = $order_data->get_id();
	$company_data = $vendor_companies[$item_vendor];
	if (!isset($company_data['send'])) { $company_data['send'] = 1; }
	if (!$item_vendor_order) { $item_vendor_order = $order_id; }

	// send order email to vendor
	if ($company_data['send'] == 1) {
		$wcecpo = WC()->mailer()->emails['WC_Email_New_Order'];
		$wcecpo->object = $order_data;

		$wcecpo->find['order-date']   = '{order_date}';
		$wcecpo->find['order-number'] = '{order_number}';

		$wcecpo->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $wcecpo->object->order_date ) );
		$wcecpo->replace['order-number'] = $item_vendor_order;

		$wcecpo->recipient = $vendor_companies[$item_vendor]['email'];

		if (!strlen($email_subject)) { $email_subject = $wcecpo->get_subject(); }

		$email_content = $wcecpo->get_content();
		$email_content = str_replace('#'.$order_id, '#'.$item_vendor_order, $email_content);
		$email_content = print_products_vendor_remove_top_line_text($email_content);

		if ($item_vendor_price) {
			$email_content = print_products_vendor_remove_prices($email_content, $item_vendor_price);
		}

		$wcecpo->send($wcecpo->get_recipient(), $email_subject, $email_content, $wcecpo->get_headers(), $wcecpo->get_attachments());
	}
}

add_filter('woocommerce_order_get_items', 'print_products_vendor_order_get_items', 11);
function print_products_vendor_order_get_items($items) {
	global $order_vendor_item;
	if ($order_vendor_item) {
		foreach($items as $item_id => $item) {
			if ($item_id != $order_vendor_item) {
				unset($items[$item_id]);
			}
		}
	}
	return $items;
}

add_filter('woocommerce_hidden_order_itemmeta', 'print_products_vendor_hidden_order_itemmeta');
function print_products_vendor_hidden_order_itemmeta($metakeys) {
	$metakeys[] = '_item_vendor';
	$metakeys[] = '_item_vendor_employee';
	$metakeys[] = '_item_vendor_address';
	$metakeys[] = '_item_vendor_price';
	$metakeys[] = '_item_vendor_date';
	$metakeys[] = '_item_vendor_order';
	return $metakeys;
}

add_filter('woocommerce_email_heading_new_order', 'print_products_vendor_woocommerce_email_heading_new_order');
function print_products_vendor_woocommerce_email_heading_new_order($heading) {
	global $order_vendor_item;
	if ($order_vendor_item) {
		$email_header = print_products_vendor_get_option('email_header');
		if (strlen($email_header)) {
			$heading = $email_header;
		}
	}
	return $heading;
}

function print_products_vendor_remove_top_line_text($content) {
	if ($ppos = strpos($content, '<p>')) {
		$pendpos = strpos($content, '</p>');
		$before_content = substr($content, 0, $ppos + 3);
		$after_content = substr($content, $pendpos);
		$email_top_text = print_products_vendor_get_option('email_top_text');
		$content = $before_content . $email_top_text . $after_content;
	}
	return $content;
}

function print_products_vendor_remove_prices($content, $item_vendor_price = '') {
	if (strpos($content, '<tfoot>')) {
		$before_tfoot = substr($content, 0, strpos($content, '<tfoot>'));
		$after_tfoot = substr($content, strpos($content, '</tfoot>') + 8);
		$content = $before_tfoot . $after_tfoot;
	}
	if ($item_vendor_price) {
		$item_vendor_price = wc_price($item_vendor_price);
	}
	$pspanpos = strpos($content, '<span class="woocommerce-Price-amount');
	if ($pspanpos) {
		$before_content = substr($content, 0, $pspanpos);
		$after_content = substr($content, $pspanpos);
		$endtd = strpos($after_content, '</td>');
		$after_content = substr($after_content, $endtd);
		$content = $before_content . $item_vendor_price . $after_content;
	}
	return $content;
}

function print_products_vendor_get_address($order_id, $type) {
	if ($type == 'customer') {
		$company = get_post_meta($order_id, '_shipping_company', true);
		$address_1 = $company.'<br>'.get_post_meta($order_id, '_shipping_address_1', true);
		$address_2 = get_post_meta($order_id, '_shipping_address_2', true);
		$city = get_post_meta($order_id, '_shipping_city', true);
		$state = get_post_meta($order_id, '_shipping_state', true);
		$postcode = get_post_meta($order_id, '_shipping_postcode', true);
		$country = get_post_meta($order_id, '_shipping_country', true);
	} else {
		$address_1 = get_option('woocommerce_store_address');
		$address_2 = get_option('woocommerce_store_address_2');
		$city = get_option('woocommerce_store_city');
		$postcode = get_option('woocommerce_store_postcode');
		$country_state = get_option('woocommerce_default_country');
		$country_state = explode(':', $country_state);
		$country = $country_state[0];
		$state = $country_state[1];
	}

	$address_line = $address_1.'<br>';
	if (strlen($address_2)) { $address_line .= $address_2.'<br>'; }
	$address_line .= $city.', '.$state.' '.$postcode.', '.$country;

	return $address_line;
}

// add vendor search to admin orders list
add_action('pre_get_posts', 'print_products_vendor_woocommerce_orders_search');
function print_products_vendor_woocommerce_orders_search($query) {
    global $post_type, $pagenow, $current_user, $wpdb;
    if (is_admin() && $pagenow == 'edit.php' && $post_type == 'shop_order') {
		if (isset($_GET['s']) && strlen($_GET['s'])) {
			$order_ids = print_products_vendor_get_s_orders($_GET['s']);
			if ($order_ids && count($order_ids)) {
				$query->query_vars['s'] = '';
				$query->query_vars['post__in'] = $order_ids;
			}
		}
		$vendor_company_id = 0;
		if (print_products_vendor_is_vendor()) {
			$vendor_company = print_products_vendor_get_user_company($current_user->ID);
			if ($vendor_company) {
				if ($vendor_company['access'] != 1) {
					$vendor_company_id = $vendor_company['id'];
				}
			}
		}
		if (isset($_GET['_vendor_company']) && strlen($_GET['_vendor_company'])) {
			$vendor_company_id = (int)$_GET['_vendor_company'];
		}
		if ($vendor_company_id) {
			$query->query_vars['meta_query'] = array(
				array(
					'key'     => '_order_vendor',
					'value'   => $vendor_company_id,
					'compare' => '=',
				),
			);
		}
		if (isset($_GET['_vendor_employee']) && strlen($_GET['_vendor_employee'])) {
			$order_ids = array();
			$order_items = $wpdb->get_results(sprintf("SELECT oi.order_id FROM %swoocommerce_order_items oi LEFT JOIN %swoocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id WHERE oim.meta_key = '_item_vendor_employee' AND oim.meta_value = '%s'", $wpdb->prefix, $wpdb->prefix, $_GET['_vendor_employee']));
			if ($order_items) {
				if (isset($query->query_vars['post__in']) && $query->query_vars['post__in']) {
					$order_ids = $query->query_vars['post__in'];
				}
				foreach($order_items as $order_item) {
					if (!in_array($order_item->order_id, $order_ids)) {
						$order_ids[] = $order_item->order_id;
					}
				}
			} else {
				$order_ids[] = 0;
			}
			$query->query_vars['post__in'] = $order_ids;
		}
    }   
}

add_action('restrict_manage_posts', 'print_products_vendor_restrict_manage_posts', 11);
function print_products_vendor_restrict_manage_posts($spt) {
	if ($spt == 'shop_order') {
		if (print_products_vendor_show_orders_vendor_column()) {
			print_products_vendor_filter_vendor_dropdown();
		}
		if (print_products_vendor_show_orders_employee_column()) {
			print_products_vendor_filter_employee_dropdown();
		}
	}
}

function print_products_vendor_filter_vendor_dropdown() {
    global $print_products_vendor_companies;
	if (print_products_vendor_allow() && $print_products_vendor_companies) {
		$_vendor_company = 0;
		if (isset($_GET['_vendor_company'])) { $_vendor_company = (int)$_GET['_vendor_company']; } ?>
		<select class="wc-vendor-company" name="_vendor_company">
			<option value=""><?php esc_html_e('Filter by Vendor', 'wp2print'); ?></option>
			<?php foreach($print_products_vendor_companies as $vcid => $vcdata) { ?>
				<option value="<?php echo $vcid; ?>"<?php if ($vcid == $_vendor_company) { echo ' SELECTED'; } ?>><?php echo $vcdata['name']; ?></option>
			<?php } ?>
		</select>
		<?php
	}
}

function print_products_vendor_filter_employee_dropdown() {
	$vendor_employees = print_products_vendor_get_employees();
	if (print_products_vendor_allow() && $vendor_employees) {
		$_vendor_employee = 0;
		if (isset($_GET['_vendor_employee'])) { $_vendor_employee = (int)$_GET['_vendor_employee']; } ?>
		<select class="wc-vendor-employee" name="_vendor_employee">
			<option value=""><?php esc_html_e('Filter by Employee', 'wp2print'); ?></option>
			<?php foreach($vendor_employees as $veid => $vedata) { ?>
				<option value="<?php echo $veid; ?>"<?php if ($veid == $_vendor_employee) { echo ' SELECTED'; } ?>><?php echo $vedata['name']; ?></option>
			<?php } ?>
		</select>
		<?php
	}
}

add_filter('admin_body_class', 'print_products_vendor_admin_body_class');
function print_products_vendor_admin_body_class($classes) {
	if (print_products_vendor_is_vendor()) {
		if (strlen($classes)) { $classes .= ' '; }
		$classes .= 'vendor-role-user';
	}
	return $classes;
}

add_action('admin_head', 'print_products_vendor_admin_head');
function print_products_vendor_admin_head() {
	if (print_products_vendor_is_vendor()) {
		?>
		<style>
		body.vendor-role-user.post-type-shop_order .page-title-action,
		body.vendor-role-user .order-proof-container,
		body.vendor-role-user .woocommerce-Price-amount,
		body.vendor-role-user .wc-order-totals-items,
		body.vendor-role-user .wc-order-bulk-actions,
		body.vendor-role-user #wpo_wcpdf-data-input-box,
		body.vendor-role-user #wpo_wcpdf-box,
		body.vendor-role-user #order_data a.edit_address { display:none; }
		</style>
		<script>
		jQuery(document).ready(function() {
			jQuery('#adminmenu #menu-posts-shop_order .wp-submenu li a').each(function(){
				if (jQuery(this).attr('href') != 'edit.php?post_type=shop_order&page=print-products-production-view') {
					jQuery(this).parent().remove();
				}
			});
		});
		</script>
		<?php
	}
}

add_action('admin_footer', 'print_products_vendor_admin_footer');
function print_products_vendor_admin_footer() {
	if (print_products_vendor_is_vendor()) {
		?>
		<script>
		jQuery('body.vendor-role-user.post-type-shop_order .page-title-action').remove();
		jQuery('body.vendor-role-user .order-proof-container').remove();
		jQuery('body.vendor-role-user .woocommerce-Price-amount').remove();
		jQuery('body.vendor-role-user .wc-order-totals-items').remove();
		jQuery('body.vendor-role-user .wc-order-bulk-actions').remove();
		jQuery('body.vendor-role-user #wpo_wcpdf-data-input-box').remove();
		jQuery('body.vendor-role-user #wpo_wcpdf-box').remove();
		</script>
		<?php
	}
}

add_action('woocommerce_email_after_order_table', 'print_products_vendor_email_after_order_table', 11);
function print_products_vendor_email_after_order_table($order) {
	global $current_user, $order_vendor_item;
	if ($order_vendor_item) {
		$item_vendor_date = wc_get_order_item_meta($order_vendor_item, '_item_vendor_date', true);
		if ($item_vendor_date) {
			echo '<div><strong>'.__('Requested delivery date', 'wp2print').':</strong> '.$item_vendor_date.'</div><br>';
		}
	}
}

add_filter('woocommerce_order_formatted_shipping_address', 'print_products_vendor_order_formatted_shipping_address', 11, 2);
function print_products_vendor_order_formatted_shipping_address($address, $order) {
	global $current_user, $order_vendor_item;
	if ($order_vendor_item) {
		$is_vendor_address = false;

		$_item_vendor = (int)wc_get_order_item_meta($order_vendor_item, '_item_vendor', true);
		$_item_vendor_address = wc_get_order_item_meta($order_vendor_item, '_item_vendor_address', true);

		if ($_item_vendor && $_item_vendor_address == 'vendor') {
			unset($address['first_name']);
			unset($address['last_name']);

			$company_data = print_products_vendor_get_company($_item_vendor);
			$address['company'] = $company_data['name'];
			$address['address_1'] = $company_data['address1'];
			$address['address_2'] = $company_data['address2'];
			$address['city'] = $company_data['city'];
			$address['state'] = $company_data['state'];
			$address['postcode'] = $company_data['postcode'];
			$address['country'] = $company_data['country'];
		}
	}
	return $address;
}

function print_products_vendor_get_vendors_array() {
	global $print_products_vendor_companies;
	$vendors_array = array();
	if ($print_products_vendor_companies) {
		foreach($print_products_vendor_companies as $cid => $cdata) {
			$vendors_array[$cid] = $cdata['name'];
		}
	}
	return $vendors_array;
}

function print_products_vendor_show_orders_vendor_column() {
	global $print_products_vendor_options;
	return (int)$print_products_vendor_options['show_column'];
}

function print_products_vendor_show_prodview_vendor_column() {
	global $print_products_prodview_options;
	return (int)$print_products_prodview_options['display_vendor'];
}

function print_products_vendor_show_orders_employee_column() {
	global $print_products_employee_options;
	return (int)$print_products_employee_options['show_column'];
}

function print_products_vendor_show_prodview_employee_column() {
	global $print_products_prodview_options;
	return (int)$print_products_prodview_options['display_employee'];
}

function print_products_vendor_show_prodview_ccompany_column() {
	global $print_products_prodview_options;
	return (int)$print_products_prodview_options['display_customer'];
}

// admin orders list
$oiemployees = false;
$oivendors = false;
add_filter('manage_shop_order_posts_columns', 'print_products_vendor_manage_shop_order_posts_columns', 26);
function print_products_vendor_manage_shop_order_posts_columns($columns) {
	if (print_products_vendor_show_orders_vendor_column()) {
		$new_columns = array();
		foreach($columns as $column_key => $column_val) {
			$new_columns[$column_key] = $column_val;
			if ($column_key == 'order_status') {
				$new_columns['oi-vendor'] = __('Vendor', 'wp2print');
			}
		}
		$columns = $new_columns;
	}
	if (print_products_vendor_show_orders_employee_column()) {
		$new_columns = array();
		foreach($columns as $column_key => $column_val) {
			$new_columns[$column_key] = $column_val;
			if ($column_key == 'order_status') {
				$new_columns['oi-employee'] = __('Employee', 'wp2print');
			}
		}
		$columns = $new_columns;
	}
	return $columns;
}

add_action('manage_shop_order_posts_custom_column', 'print_products_vendor_manage_shop_order_posts_custom_column', 26);
function print_products_vendor_manage_shop_order_posts_custom_column($name) {
    global $post, $oiemployees, $oivendors;
	$order_id = $post->ID;
	$order = wc_get_order($order_id);
	$order_items = $order->get_items('line_item');
	if (!$oiemployees) { $oiemployees = print_products_vendor_get_vendors(); }
	if (!$oivendors) { $oivendors = print_products_vendor_get_vendors_array(); }
	if (print_products_vendor_show_orders_employee_column()) {
		if ($name == 'oi-employee') {
			foreach($order_items as $order_item) {
				$item_id = $order_item->get_id();
				$item_vendor_employee = (int)wc_get_order_item_meta($item_id, '_item_vendor_employee', true); ?>
				<div class="oil-item oil-item-<?php echo $item_id; ?>"><span><?php echo $oiemployees[$item_vendor_employee]; ?></span></div>
			<?php }
		}
	}
	if (print_products_vendor_show_orders_vendor_column()) {
		if ($name == 'oi-vendor') {
			foreach($order_items as $order_item) {
				$item_id = $order_item->get_id();
				$item_vendor = (int)wc_get_order_item_meta($item_id, '_item_vendor', true); ?>
				<div class="oil-item oil-item-<?php echo $item_id; ?>"><span><?php echo $oivendors[$item_vendor]; ?></span></div>
			<?php }
		}
	}
}

// admin order edit page
add_action('woocommerce_admin_order_item_headers', 'print_products_vendor_woocommerce_admin_order_item_headers');
function print_products_vendor_woocommerce_admin_order_item_headers($order) { ?>
	<?php if (print_products_vendor_show_orders_employee_column()) { ?><th class="item_employee"><?php esc_html_e('Employee', 'wp2print'); ?></th><?php } ?>
	<?php if (print_products_vendor_show_orders_vendor_column()) { ?><th class="item_vendor"><?php esc_html_e('Vendor', 'wp2print'); ?></th><?php } ?>
	<?php
}

add_action('woocommerce_admin_order_item_values', 'print_products_vendor_woocommerce_admin_order_item_values', 11, 3);
function print_products_vendor_woocommerce_admin_order_item_values($product, $item, $item_id) {
    global $post, $oiemployees, $oivendors, $current_user;
	if (!$oiemployees) { $oiemployees = print_products_vendor_get_vendors(); }
	if (!$oivendors) { $oivendors = print_products_vendor_get_vendors_array(); }
	$item_type = $item->get_type();
	$item_vendor = (int)wc_get_order_item_meta($item_id, '_item_vendor', true);
	$item_vendor_employee = (int)wc_get_order_item_meta($item_id, '_item_vendor_employee', true); ?>
	<?php if (print_products_vendor_show_orders_employee_column()) { ?>
		<td class="item_employee">
			<?php if ($item_type == 'line_item') { ?>
				<div class="oiv-block oiv-employee-<?php echo $item_id; ?>" data-confirm="<?php esc_html_e('Are you sure?', 'wp2print'); ?>"><?php echo $oiemployees[$item_vendor_employee]; ?><?php if ($item_vendor_employee && $item_vendor_employee == $current_user->ID) { ?> <input type="checkbox" name="ivemployee" onclick="return wp2print_vendor_unassign(<?php echo $item_id; ?>);" CHECKED><?php } ?></div>
			<?php } ?>
		</td>
	<?php } ?>
	<?php if (print_products_vendor_show_orders_vendor_column()) { ?>
		<td class="item_vendor">
			<?php if ($item_type == 'line_item') { ?>
				<div class="oiv-block"><?php echo $oivendors[$item_vendor]; ?></div>
			<?php } ?>
		</td>
	<?php } ?>
	<?php
}

function print_products_vendor_get_s_orders($s, $vc_order_ids = array()) {
    global $wpdb, $print_products_vendor_companies;
	$order_ids = array();
	if ($print_products_vendor_companies) {
		$s = trim($_GET['s']);
		$s = str_replace("'", "''", $s);
		$swords = explode(' ', $s);

		$sconditions = array();
		foreach($print_products_vendor_companies as $cid => $cdata) {
			$scount = 0;
			foreach($swords as $sword) {
				if (strpos($cdata['name'], $sword) !== false) {
					$scount++;
				}
			}
			if ($scount == count($swords)) {
				$sconditions[] = "oim.meta_value = '".$cid."'";
			}
		}

		if ($sconditions) {
			$vc_condition = '';
			if (count($vc_order_ids)) {
				$vc_condition = ' AND oi.order_id IN ('.implode(',', $vc_order_ids).')';
			}
			$orders = $wpdb->get_results(sprintf("SELECT oi.order_id FROM %swoocommerce_order_items oi LEFT JOIN %swoocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id WHERE oi.order_item_type = 'line_item' AND oim.meta_key = '_item_vendor' AND (%s) %s", $wpdb->prefix, $wpdb->prefix, implode(' OR ', $sconditions), $vc_condition));
			if ($orders) {
				foreach($orders as $order) {
					$order_ids[] = $order->order_id;
				}
			}
		}
	}
	return $order_ids;
}

function print_products_vendor_view_order_show_vendors($order) {
	global $print_products_vendor_options, $oivendors;
	if ($print_products_vendor_options['show_to_customer'] == 1) {
		if (!$oivendors) { $oivendors = print_products_vendor_get_vendors_array(); }
		$order_items_html = __('Vendor', 'wp2print').': ';
		$order_items = $order->get_items();
		if ($order_items) {
			$oiv = array();
			foreach($order_items as $item_id => $order_item) {
				$item_vendor = (int)wc_get_order_item_meta($item_id, '_item_vendor', true);
				if ($item_vendor && !in_array($item_vendor, $oiv)) { $oiv[] = $item_vendor; }
			}
			if (count($oiv)) {
				if (count($oiv) > 1) {
					foreach($order_items as $item_id => $order_item) {
						$item_vendor = (int)wc_get_order_item_meta($item_id, '_item_vendor', true);
						$order_items_html .= '<br>'.$order_item['name'].': <mark>'.$oivendors[$item_vendor].'</mark>';
					}
				} else {
					$order_items_html .= '<mark>'.$oivendors[$oiv[0]].'</mark>';
				}
			}
		}
		echo '<p class="ois-order-status top-border">'.$order_items_html.'</p>';
	}
}

function print_products_vendor_view_order_show_employees($order) {
	global $print_products_employee_options, $oiemployees;
	if ($print_products_employee_options['show_to_customer'] == 1) {
		if (!$oiemployees) { $oiemployees = print_products_vendor_get_employees(); }
		$order_items_html = __('Responsible employee', 'wp2print').': ';
		$order_items = $order->get_items();
		if ($order_items) {
			$oiempl = array();
			foreach($order_items as $item_id => $order_item) {
				$item_vendor_employee = (int)wc_get_order_item_meta($item_id, '_item_vendor_employee', true);
				if ($item_vendor_employee && !in_array($item_vendor_employee, $oiempl)) { $oiempl[] = $item_vendor_employee; }
			}
			if (count($oiempl)) {
				if (count($oiempl) > 1) {
					foreach($order_items as $item_id => $order_item) {
						$item_vendor_employee = (int)wc_get_order_item_meta($item_id, '_item_vendor_employee', true);
						$order_items_html .= '<br>'.$order_item['name'].': <mark>'.$oiemployees[$item_vendor_employee]['name'].'</mark>';
						$order_items_html .= print_products_vendor_get_employees_info($item_vendor_employee);
					}
				} else {
					$order_items_html .= '<mark>'.$oiemployees[$oiempl[0]]['name'].'</mark>';
					$order_items_html .= print_products_vendor_get_employees_info($oiempl[0]);
				}
			}
		}
		echo '<p class="ois-order-status top-border">'.$order_items_html.'</p>';
	}
}

function print_products_vendor_get_employees_info($eid) {
	global $print_products_employee_options, $oiemployees;
	$employees_info = '';
	if (!$oiemployees) { $oiemployees = print_products_vendor_get_employees(); }
	if ($print_products_employee_options['show_contact_info'] == 1) {
		$employees_info = '<br>'.__('Email', 'wp2print').': '.$oiemployees[$eid]['email'];
		if (strlen($oiemployees[$eid]['phone'])) {
			$employees_info .= '<br>'.__('Phone', 'wp2print').': '.$oiemployees[$eid]['phone'];
		}
	}
	return $employees_info;
}
?>