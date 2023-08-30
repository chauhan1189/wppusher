<?php
add_action('wp_loaded', 'print_products_oistatus_actions');
function print_products_oistatus_actions() {
	if (isset($_POST['OisAjaxAction'])) {
		switch ($_POST['OisAjaxAction']) {
			case 'change-oistatus-from-list':
				print_products_oistatus_ajax_change();
			break;
			case 'oistatus-submit-tracking-info':
				print_products_oistatus_submit_tracking_info();
			break;
		}
		exit;
	}
}

$print_products_oistatus_options = get_option('print_products_oistatus_options');
function print_products_oistatus_get_options() {
	global $print_products_oistatus_options;
	return $print_products_oistatus_options;
}

function print_products_oistatus_allowed() {
	$oistatus_options = print_products_oistatus_get_options();
	if ($oistatus_options && isset($oistatus_options['use']) && $oistatus_options['use'] == 1) {
		return true;
	}
	return false;
}

function print_products_oistatus_get_statuses() {
	$statuses = array();
	$oistatus_options = print_products_oistatus_get_options();
	if ($oistatus_options && isset($oistatus_options['list']) && count($oistatus_options['list'])) {
		foreach($oistatus_options['list'] as $ois_data) {
			if (strlen($ois_data['name'])) {
				$ois_data['slug'] = print_products_oistatus_get_slug($ois_data['name']);
				$statuses[] = $ois_data;
			}
		}
	}
	return $statuses;
}

function print_products_oistatus_get_status_by_slug($slug) {
	$statuses = print_products_oistatus_get_statuses();
	if ($statuses) {
		foreach($statuses as $status) {
			if ($status['slug'] == $slug) {
				return $status;
			}
		}
	}
}

function print_products_oistatus_get_list() {
	$oi_statuses = array();
	$statuses = print_products_oistatus_get_statuses();
	if ($statuses) {
		foreach($statuses as $status) {
			$oi_statuses[$status['slug']] = $status['name'];
		}
	}
	return $oi_statuses;
}

function print_products_oistatus_get_slug($name) {
	return trim(str_replace(' ', '-', strtolower($name)));
}

function print_products_oistatus_get_default_status() {
	$oistatus_options = print_products_oistatus_get_options();
	if ($oistatus_options && isset($oistatus_options['default']) && strlen($oistatus_options['default'])) {
		return $oistatus_options['default'];
	}
	return '';
}

function print_products_oistatus_add($item_id) {
	if (print_products_oistatus_allowed()) {
		$default_status = print_products_oistatus_get_default_status();
		if (strlen($default_status)) {
			wc_add_order_item_meta($item_id, '_item_status', $default_status);
		}
	}
}

// update from orders list
function print_products_oistatus_ajax_change() {
	$order_id = (int)$_POST['order_id'];
	$item_id = (int)$_POST['item_id'];
	$item_status = $_POST['item_status'];
	$ioption = (int)$_POST['ioption'];
	$blog_id = 0;
	if (isset($_POST['blog_id'])) {
		$blog_id = (int)$_POST['blog_id'];
		switch_to_blog($blog_id);
	}
	if ($order_id && $item_id) {
		$order = wc_get_order($order_id);
		$order_items_name = array();
		$old_item_status = wc_get_order_item_meta($item_id, '_item_status', true);
		if ($ioption == 1) { // update status for all items
			$order_items = $order->get_items();
			foreach($order_items as $order_item) {
				$order_item_id = $order_item->get_id();
				wc_update_order_item_meta($order_item_id, '_item_status', $item_status);
				$order_items_name[] = print_products_oistatus_get_item_name($order_item_id);
			}
		} else {
			wc_update_order_item_meta($item_id, '_item_status', $item_status);
			$order_items_name[] = print_products_oistatus_get_item_name($item_id);
		}
		if ($old_item_status != $item_status && strlen($item_status)) {
			$item_name = implode(', ', $order_items_name);
			print_products_oistatus_send_email($order_id, $order, $item_name, $item_status);
		}
		$ois_data = print_products_oistatus_get_status_by_slug($item_status);
		if ($ois_data) {
			$ostatus = $ois_data['ostatus'];
			if ($ois_data['assign'] == 1 && $ostatus) {
				$wc_order_statuses = wc_get_order_statuses();
				$oupdate = true;
				$order_items = $order->get_items();
				foreach($order_items as $order_item) {
					$order_item_id = $order_item->get_id();
					$order_item_status = wc_get_order_item_meta($order_item_id, '_item_status', true);
					if ($order_item_status != $item_status) {
						$oupdate = false;
					}
				}
				if ($oupdate) {
					$order->update_status($ostatus);
					echo $wc_order_statuses[$ostatus];
				}
			}
		}
	}
	if ($blog_id) {
		restore_current_blog();
	}
}

function print_products_oistatus_send_email($order_id, $order, $item_name, $item_status) {
	global $wpdb, $ois_color;

	$date_format = get_option('date_format');
	$order_date = date_i18n($date_format, strtotime($order->order_date));
	$user_email = $order->get_billing_email();

	$replacements = array('{ORDER_ID}' => $order_id, '{ORDER_DATE}' => $order_date, '{ITEM_NAME}' => $item_name);

	$ois_data = print_products_oistatus_get_status_by_slug($item_status);

	if ($ois_data) {
		$subject = stripslashes($ois_data['subject']);
		$heading = stripslashes($ois_data['heading']);
		$message = stripslashes($ois_data['message']);
		$ois_color = stripslashes($ois_data['color']);
		$ois_send = (int)$ois_data['send'];
		if (strlen($subject) && $ois_send) {
			foreach($replacements as $r_key => $r_val) {
				$subject = str_replace($r_key, $r_val, $subject);
				$heading = str_replace($r_key, $r_val, $heading);
				$message = str_replace($r_key, $r_val, $message);
			}
			if (strlen($ois_color)) {
				add_filter('woocommerce_email_styles', function($email_styles){
					global $ois_color;
					$email_styles .= chr(10).'#template_header {background-color:'.esc_attr($ois_color).';}';
					return $email_styles;
				});
			}
			$mailer = WC()->mailer();
			$message = $mailer->wrap_message($heading, $message);
			$mailer->send($user_email, $subject, $message);
		}
	}
}

function print_products_oistatus_get_item_name($item_id) {
	global $wpdb;
	return $wpdb->get_var(sprintf("SELECT order_item_name FROM %swoocommerce_order_items WHERE order_item_id = %s", $wpdb->prefix, $item_id));
}

add_filter('manage_shop_order_posts_columns', 'print_products_oistatus_manage_shop_order_posts_columns', 25);
function print_products_oistatus_manage_shop_order_posts_columns($columns) {
	if (print_products_oistatus_allowed() && print_products_vendor_allow()) {
		$new_columns = array();
		foreach($columns as $column_key => $column_val) {
			$new_columns[$column_key] = $column_val;
			if ($column_key == 'order_status') {
				$new_columns['p-status'] = __('Production Status', 'wp2print');
			}
		}
	    return $new_columns;
	} else {
	    return $columns;
	}
}

$oistatuses = false;
add_action('manage_shop_order_posts_custom_column', 'print_products_oistatus_manage_shop_order_posts_custom_column', 25);
function print_products_oistatus_manage_shop_order_posts_custom_column($name) {
    global $post, $oistatuses;
	if (print_products_oistatus_allowed() && print_products_vendor_allow()) {
		$order_id = $post->ID;
		$order = wc_get_order($order_id);
		$order_items = $order->get_items();
		if (!$oistatuses) { $oistatuses = print_products_oistatus_get_list(); }
		if ($name == 'p-status') { ?>
			<div class="ois-block ois-order-<?php echo $order_id; ?>">
				<?php foreach($order_items as $order_item) {
					$item_id = $order_item->get_id();
					$item_status = wc_get_order_item_meta($item_id, '_item_status', true); ?>
					<div class="oil-line ois-block-<?php echo $item_id; ?>" rel="<?php echo $item_id; ?>" style="line-height:16px;margin-bottom:5px;"><?php echo $order_item->get_name(); ?><br>
						<?php if (print_products_vendor_allow_for_item($item_id)) { ?>
							<a href="" onclick="return false;"><select name="ois" class="ois-ldd-<?php echo $item_id; ?>" onchange="wp2print_ois_change(<?php echo $order_id; ?>, <?php echo $item_id; ?>, 'list')">
								<option value="">-- <?php _e('Status', 'wp2print'); ?> --</option>
								<?php foreach($oistatuses as $ois_key => $ois_val) { ?>
									<option value="<?php echo $ois_key; ?>"<?php if ($ois_key == $item_status) { echo ' SELECTED'; } ?>><?php echo $ois_val; ?></option>
								<?php } ?>
							</select></a>
						<?php } else { ?>
							<?php echo $oistatuses[$item_status]; ?>
						<?php } ?>
					</div>
				<?php } ?>
				<div class="ois-success"><?php _e('Updated.', 'wp2print'); ?></div>
			</div>
		<?php }
	}
}

function print_products_oistatus_popup_html() {
	if (print_products_oistatus_allowed()) { ?>
		<div class="ois-popup-hidden" style="display:none;">
			<div id="ois-popup" class="ois-popup" data-allow-popup="<?php if (print_products_vendor_allow()) { echo '1'; } else { echo '0'; } ?>">
				<h2><?php _e('Modify production status for order #', 'wp2print'); ?><span></span></h2>
				<div class="ois-i-options">
					<ul>
						<li><input type="radio" name="ois_option" value="0" checked><?php _e('Modify status of this item only', 'wp2print'); ?></li>
						<li><input type="radio" name="ois_option" value="1"><?php _e('Modify status for all items in this order', 'wp2print'); ?></li>
					</ul>
				</div>
				<input type="button" value="<?php _e('Submit', 'wp2print'); ?>" class="button-primary" onclick="wp2print_ois_submit()">
			</div>
		</div>
		<?php
	}
	print_products_oistatus_tracking_popup_html();
}

function print_products_oistatus_network_popup_html() {
	if (print_products_oistatus_allowed()) { ?>
		<div class="ois-popup-hidden" style="display:none;">
			<div id="ois-popup" class="ois-popup">
				<h2><?php _e('Modify production status for order #', 'wp2print'); ?><span></span></h2>
				<div class="ois-i-options">
					<ul>
						<li><input type="radio" name="ois_option" value="0" checked><?php _e('Modify status of this item only', 'wp2print'); ?></li>
						<li><input type="radio" name="ois_option" value="1"><?php _e('Modify status for all items in this order', 'wp2print'); ?></li>
					</ul>
				</div>
				<input type="button" value="<?php _e('Submit', 'wp2print'); ?>" class="button-primary" onclick="wp2print_ois_network_submit()">
			</div>
		</div>
		<?php
	}
	print_products_oistatus_tracking_popup_html();
}

function print_products_oistatus_tracking_popup_html() {
	if (print_products_oistatus_tracking_prompt()) {
		$oistatus_options = print_products_oistatus_get_options();
		$tracking_dcompany = $oistatus_options['tracking_dcompany'];
		$tracking_companies = print_products_oistatus_get_tracking_companies(); ?>
		<div style="display:none;">
			<div id="ois-tracking-popup" class="ois-tracking-popup" data-status="<?php echo $oistatus_options['tracking_status']; ?>">
				<h2><?php _e('Enter tracking information', 'wp2print'); ?></h2>
				<?php if ($tracking_companies) { ?>
					<div class="ois-tp-row">
						<select name="tracking_company" class="tracking-company" data-defcompany="<?php echo $tracking_dcompany; ?>">
							<option value="">-- <?php _e('Select shipping company', 'wp2print'); ?> --</option>
							<?php foreach($tracking_companies as $tracking_company) { ?>
								<option value="<?php echo $tracking_company; ?>"<?php if ($tracking_company == $tracking_dcompany) { echo ' SELECTED'; } ?>><?php echo $tracking_company; ?></option>
							<?php } ?>
						</select>
					</div>
				<?php } ?>
				<div class="ois-tp-row">
					<textarea name="tracking_numbers" class="tracking-numbers" placeholder="<?php _e('Enter tracking numbers', 'wp2print'); ?>"></textarea>
				</div>
				<input type="button" value="<?php _e('Send email to customer', 'wp2print'); ?>" class="button-primary" onclick="wp2print_ois_tracking_submit(1)">&nbsp;&nbsp;<input type="button" value="<?php _e('Do not send email', 'wp2print'); ?>" class="button-primary" onclick="wp2print_ois_tracking_submit(0)">
			</div>
		</div>
		<?php
	}
}

// order edit page
add_action('woocommerce_admin_order_item_headers', 'print_products_oistatus_woocommerce_admin_order_item_headers', 11);
function print_products_oistatus_woocommerce_admin_order_item_headers($order) {
	if (print_products_oistatus_allowed()) { ?>
		<th class="item_status"><?php esc_html_e('Production Status', 'wp2print'); ?></th>
		<?php
	}
}

add_action('woocommerce_admin_order_item_values', 'print_products_oistatus_woocommerce_admin_order_item_values', 11, 3);
function print_products_oistatus_woocommerce_admin_order_item_values($product, $item, $item_id) {
    global $post, $oistatuses;
	$order_id = $post->ID;
	$item_type = $item->get_type();
	if ($item_type == 'line_item') {
		if (print_products_oistatus_allowed()) {
			if (!$oistatuses) { $oistatuses = print_products_oistatus_get_list(); }
			$item_status = wc_get_order_item_meta($item_id, '_item_status', true); ?>
			<td class="item_status ois-order-<?php echo $order_id; ?>">
				<?php if ($item_type == 'line_item') { ?>
					<?php if (print_products_vendor_allow_for_item($item_id)) { ?>
						<select name="oistatus[<?php echo $item_id; ?>]" class="ois-ldd-<?php echo $item_id; ?>" onchange="wp2print_ois_change(<?php echo $order_id; ?>, <?php echo $item_id; ?>, 'detail')" style="min-width:120px;">
							<option value="">-- <?php _e('Status', 'wp2print'); ?> --</option>
							<?php foreach($oistatuses as $ois_key => $ois_val) { ?>
								<option value="<?php echo $ois_key; ?>"<?php if ($ois_key == $item_status) { echo ' SELECTED'; } ?>><?php echo $ois_val; ?></option>
							<?php } ?>
						</select>
						<div class="ois-success ois-success-<?php echo $item_id; ?>"><?php _e('Updated.', 'wp2print'); ?></div>
					<?php } else { ?>
						<?php echo $oistatuses[$item_status]; ?>
					<?php } ?>
				<?php } ?>
			</td>
			<?php
		}
	}
}

// my account page (Orders list)
add_filter('woocommerce_my_account_my_orders_columns', 'print_products_oistatus_woocommerce_my_account_my_orders_columns', 20);
function print_products_oistatus_woocommerce_my_account_my_orders_columns($columns) {
	if (print_products_oistatus_allowed()) {
		$old_columns = $columns;
		$columns = array();
		foreach($old_columns as $c_key => $c_name) {
			$columns[$c_key] = $c_name;
			if ($c_key == 'order-status') {
				$columns['oistatus'] = __('Production Status', 'wp2print');
			}
		}
	}
	return $columns;
}

function print_products_oistatus_get_order_items_statuses($order) {
    global $oistatuses;
	$order_items_statuses = array();
	$order_items = $order->get_items();
	if (!$oistatuses) { $oistatuses = print_products_oistatus_get_list(); }
	if ($order_items) {
		foreach($order_items as $order_item) {
			$item_id = $order_item->get_id();
			$item_type = $order_item->get_type();
			$item_status = wc_get_order_item_meta($item_id, '_item_status', true);
			if ($item_type == 'line_item') {
				$order_items_statuses[] = array('name' => $order_item->get_name(), 'status' => $oistatuses[$item_status]);
			}
		}
	}
	return $order_items_statuses;
}

add_action('woocommerce_my_account_my_orders_column_oistatus', 'print_products_oistatus_my_account_my_orders_column_oistatus');
function print_products_oistatus_my_account_my_orders_column_oistatus($order) {
    global $oistatuses;
	$order_items = print_products_oistatus_get_order_items_statuses($order);
	if ($order_items) {
		foreach($order_items as $order_item) { ?>
			<div class="ois-ma-istatus"><?php echo $order_item['name']; ?>: <span><?php echo $order_item['status']; ?></span></div>
			<?php
		}
	}
}

add_action('woocommerce_view_order', 'print_products_oistatus_woocommerce_view_order', 9);
function print_products_oistatus_woocommerce_view_order($order_id) {
	$order = wc_get_order($order_id);
	$order_date = $order->get_date_created();
	$order_items = print_products_oistatus_get_order_items_statuses($order);
	$order_items_html = '';
	if ($order_items) {
		foreach($order_items as $order_item) {
			$order_items_html .= '<br>'.$order_item['name'].': <mark>'.$order_item['status'].'</mark>';
		}
	}
	?>
	<p class="ois-order-status"><?php _e('Order', 'wp2print'); ?> <mark>#<?php echo $order_id; ?></mark> <?php _e('was placed on', 'wp2print'); ?> <mark><?php echo wc_format_datetime($order_date); ?></mark><br>
	<?php _e('Order Status', 'wp2print'); ?>: <mark><?php echo wc_get_order_status_name($order->get_status()); ?></mark></p>
	<p class="ois-order-status top-border"><?php _e('Production Status', 'wp2print'); ?>: <?php echo $order_items_html; ?></p>
	<?php print_products_vendor_view_order_show_vendors($order); ?>
	<?php print_products_vendor_view_order_show_employees($order); ?>
	<?php
}

add_action('network_admin_menu', 'print_products_oistatus_network_admin_menu');
function print_products_oistatus_network_admin_menu() {
	add_submenu_page('woonet-woocommerce', __('Production View', 'wp2print'), __('Production View', 'wp2print'), 'create_users', 'woonet-woocommerce-production-view', 'print_products_oistatus_network_admin_page');
}

function print_products_oistatus_admin_page() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-production-view.php';
}

function print_products_oistatus_network_production_view_page() {
	wp_redirect( network_site_url( 'wp-admin/network/admin.php?page=woonet-woocommerce-production-view' ) );
	exit;
}

function print_products_oistatus_network_admin_page() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-network-production-view.php';
}

function print_products_oistatus_approval_status($item_id, $approval_statuses) {
	$approval_status = wc_get_order_item_meta($item_id, '_approval_status', true);
	$title = $approval_statuses[$approval_status];
	if ($approval_status == 'approved') {
		$approval_approved = wc_get_order_item_meta($item_id, '_approval_approved', true);
		$title .= chr(10) . __('Approved on', 'wp2print').' '.$approval_approved;
	} else if ($approval_status == 'rejected') {
		$approval_rejected = wc_get_order_item_meta($item_id, '_approval_rejected', true);
		$title .= chr(10) . __('Rejected on', 'wp2print').' '.$approval_rejected;
	}
	if (strlen($approval_status)) {
		echo '<mark class="'.$approval_status.'" title="'.$title.'"></mark>';
	} else {
		echo '&nbsp;';
	}
}

function print_products_oistatus_submit_tracking_info() {
	$oistatus_options = print_products_oistatus_get_options();
	$order_id = $_POST['order_id'];
	$item_id = $_POST['item_id'];
	$blog_id = (int)$_POST['blog_id'];
	$tracking_company = $_POST['tracking_company'];
	$tracking_numbers = $_POST['tracking_numbers'];
	$send_email = (int)$_POST['send_email'];

	if ($blog_id) { switch_to_blog($blog_id); }

	$order = wc_get_order($order_id);
	$order_items = $order->get_items();
	$item_name = $order_items[$item_id]->get_name();

	// send email
	$user_email = $order->get_billing_email();
	$subject = $oistatus_options['tracking_subject'];
	$heading = $oistatus_options['tracking_heading'];
	$message = $oistatus_options['tracking_message'];

	if (strlen($subject) && strlen($message) && $send_email) {
		$message = str_replace('{ORDER_ID}', $order_id, $message);
		$message = str_replace('{ITEM_NAME}', $item_name, $message);
		$message = str_replace('{SHIPPING_COMPANY}', $tracking_company, $message);
		$message = str_replace('{TRACKING_NUMBERS}', $tracking_numbers, $message);

		$mailer = WC()->mailer();
		$email_message = $mailer->wrap_message($heading, $message);
		$mailer->send($user_email, $subject, $email_message);
	}

	// add order note
	$order_note  = __('ItemID', 'wp2print').': '.$item_id.chr(10);
	$order_note .= __('Item', 'wp2print').': '.$item_name.chr(10);
	$order_note .= __('Shipping company', 'wp2print').': '.$tracking_company.chr(10);
	$order_note .= __('Tracking numbers', 'wp2print').':'.chr(10).$tracking_numbers;
	$order->add_order_note($order_note);

	if ($blog_id) { restore_current_blog(); }
}

function print_products_oistatus_tracking_prompt() {
	$oistatus_options = print_products_oistatus_get_options();
	return (int)$oistatus_options['tracking_prompt'];
}

function print_products_oistatus_get_tracking_companies() {
	$tracking_companies = array();
	$oistatus_options = print_products_oistatus_get_options();
	if (strlen($oistatus_options['tracking_companies'])) {
		$tracking_companies = print_products_text2array($oistatus_options['tracking_companies']);
	}
	return $tracking_companies;
}
?>