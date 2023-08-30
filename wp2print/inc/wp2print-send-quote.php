<?php
function print_products_send_quote_admin_page() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-send-quote.php';
}

function print_products_send_quote_history_admin_page() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-send-quote-history.php';
}

add_action('wp_loaded', 'print_products_send_quote_actions');
function print_products_send_quote_actions() {
	if (isset($_POST['print_products_send_quote_action']) && $_POST['print_products_send_quote_action'] == 'process') {
		$send_quote_data = print_products_send_quote_get_order_data();
		switch ($_POST['process_step']) {
			case '1':
				print_products_send_quote_set_customer();
			break;
			case '2':
				if (isset($_POST['product_action']) && strlen($_POST['product_action'])) {
					$redirect = 'admin.php?page=print-products-send-quote&step=2';
					if ($_POST['product_action'] == 'add') {
						$product_key = print_products_send_quote_add_product();
						$redirect .= '&product_key='.$product_key;
					} else if ($_POST['product_action'] == 'attributes') {
						print_products_send_quote_set_product_data();
					} else if ($_POST['product_action'] == 'duplicate') {
						print_products_send_quote_duplicate_product();
					} else if ($_POST['product_action'] == 'delete') {
						print_products_send_quote_delete_product();
					}
					wp_redirect($redirect);
					exit;
				}
			break;
			case 'send':
				print_products_send_quote_save_order();
			break;
		}
	}
	if (isset($_POST['AjaxAction']) && $_POST['AjaxAction'] == 'send-quote-add-user') {
		$user_login = sanitize_user($_POST['u_username']);
		$user_email = sanitize_text_field($_POST['u_email']);
		$user_fname = sanitize_text_field($_POST['u_fname']);
		$user_lname = sanitize_text_field($_POST['u_lname']);
		$user_pass = sanitize_text_field($_POST['u_pass']);
		$print_products_send_quote_options = get_option("print_products_send_quote_options");

		$error = '';
		if (!validate_username($user_login)) {
			$error = __('<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.');
		} else if (username_exists($user_login)) {
			$error = __('<strong>ERROR</strong>: This username is already registered. Please choose another one.');
		} elseif (!is_email($user_email)) {
			$error = __('<strong>ERROR</strong>: The email address isn&#8217;t correct.');
		} elseif (email_exists($user_email)) {
			$error = __('<strong>ERROR</strong>: This email is already registered, please choose another one.');
		}

		if (!strlen($error)) {
			$new_user = new stdClass;
			$new_user->user_login = $user_login;
			$new_user->user_email = $user_email;
			$new_user->user_pass = $user_pass;
			$new_user->first_name = $user_fname;
			$new_user->last_name = $user_lname;
			$new_user->role = get_option('default_role');
			$new_user->display_name = $user_login;
			if (strlen($user_fname)) {
				$new_user->display_name = $user_fname.' '.$user_lname;
			}
			$user_id = wp_insert_user($new_user);

			// send email to customer
			$admin_email = get_option('admin_email');
			$site_name = get_bloginfo('name');
			$subject = $print_products_send_quote_options['cnu_email_subject'];
			$message = $print_products_send_quote_options['cnu_email_message'];
			$message = str_replace('{USERNAME}', $user_login, $message);
			$message = str_replace('{EMAIL}', $user_email, $message);
			$message = str_replace('{PASSWORD}', $user_pass, $message);
			$headers = "From: ".$site_name." <".$admin_email.">" . "\r\n";
			wp_mail($user_email, $subject, $message, $headers);

			$user_line = $new_user->display_name.' ('.__('Email', 'wp2print').': '.$new_user->user_email.')';
			$resp = 'success;'.$user_id.';'.$user_line;
		} else {
			$resp = 'error;'.$error;
		}
		echo $resp;
		exit;
	}
	if (isset($_POST['sqh_action'])) {
		switch ($_POST['sqh_action']) {
			case 'resend':
				$print_products_send_quote_options = get_option("print_products_send_quote_options");
				$order_id = (int)$_POST['order_id'];
				if ($order_id) {
					$email_subject = $print_products_send_quote_options['email_subject'];
					$email_message = $print_products_send_quote_options['email_message'];
					print_products_send_quote_order_email($order_id, $email_subject, $email_message);
					$redirect = 'admin.php?page=print-products-send-quote-history';
					if (isset($_GET['s'])) { $redirect .= '&s='.$_GET['s']; }
					if (isset($_GET['qopage'])) { $redirect .= '&qopage='.$_GET['qopage']; }
					$redirect .= '&resent=true';
					wp_redirect($redirect);
					exit;
				}
			break;
			case 'duplicate':
				$order_id = (int)$_POST['order_id'];
				if ($order_id) {
					print_products_send_quote_duplicate_order($order_id);
					wp_redirect('admin.php?page=print-products-send-quote');
					exit;
				}
			break;
		}
	}
	if (isset($_GET['qorder']) && $_GET['qorder'] == 'true') {
		print_products_send_quote_order_process();
	}
}

function print_products_send_quote_get_order_data() {
	$send_quote_data = array();
	if (isset($_SESSION['send_quote_data'])) { $send_quote_data = $_SESSION['send_quote_data']; }
	return $send_quote_data;
}

function print_products_send_quote_set_order_data($send_quote_data) {
	$_SESSION['send_quote_data'] = $send_quote_data;
}

function print_products_send_quote_get_order($order_id) {
	global $wpdb;
	return $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_quotes WHERE order_id = %s", $wpdb->prefix, $order_id));
}

function print_products_send_quote_get_order_items($order_id) {
	global $wpdb;
	return $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_quotes_items WHERE order_id = %s", $wpdb->prefix, $order_id));
}

function print_products_send_quote_set_customer() {
	$send_quote_data = print_products_send_quote_get_order_data();
	$send_quote_data['sender'] = $_POST['order_sender'];
	$send_quote_data['customer'] = $_POST['order_customer'];
	print_products_send_quote_set_order_data($send_quote_data);
}

function print_products_send_quote_add_product() {
	$product_id = (int)$_POST['order_product'];
	$product_key = md5(time());

	$product_name = get_the_title($product_id);

	$products = array();
	$send_quote_data = print_products_send_quote_get_order_data();
	if (isset($send_quote_data['products'])) {
		$products = $send_quote_data['products'];
	}
	$products[$product_key] = array('product_id' => $product_id, 'name' => $product_name, 'cptype' => $_POST['cptype']);

	$send_quote_data['products'] = $products;
	print_products_send_quote_set_order_data($send_quote_data);
	return $product_key;
}

function print_products_send_quote_set_product_data() {
	$product_key = $_POST['product_key'];
	$product_type = $_POST['product_type'];
	$quantity = (int)$_POST['quantity'];
	$price = (float)$_POST['price'];

	$smparams = '';
	$fmparams = '';
	$artworkfiles = '';
	if (isset($_POST['smparams'])) {
		$smparams = $_POST['smparams'];
		$fmparams = $_POST['fmparams'];
	}
	if (isset($_POST['artworkfiles'])) {
		$artworkfiles = $_POST['artworkfiles'];
	}

	$send_quote_data = print_products_send_quote_get_order_data();

	$product_data = $send_quote_data['products'][$product_key];
	$product_data['product_type'] = $product_type;
	$product_data['quantity'] = $quantity;
	$product_data['price'] = $price;
	$product_data['smparams'] = $smparams;
	$product_data['fmparams'] = $fmparams;
	$product_data['artworkfiles'] = $artworkfiles;

	if ($product_type == 'custom') {
		$product_data['attributes'] = $_POST['attributes'];
		$product_data['shipping_specify'] = $_POST['shipping_specify'];
		$product_data['weight'] = $_POST['weight'];
		$product_data['sboxes'] = $_POST['sboxes'];
		$product_data['shipping_cost'] = $_POST['shipping_cost'];
	}

	$checkout_data = false;
	switch ($product_type) {
		case 'fixed':
			$checkout_data = print_products_checkout_fixed($product_key, false, true);
		break;
		case 'book':
			$checkout_data = print_products_checkout_book($product_key, false, true);
		break;
		case 'area':
			$checkout_data = print_products_checkout_area($product_key, false, true);
		break;
		case 'custom':
			$custom_data = array('cptype' => $product_data['cptype'], 'shipping_specify' => $product_data['shipping_specify'], 'weight' => $product_data['weight'], 'sboxes' => $product_data['sboxes'], 'shipping_cost' => $product_data['shipping_cost']);
			$checkout_data = array(
				'additional' => serialize($custom_data)
			);
		break;
	}

	if ($checkout_data) {
		if ($checkout_data['product_attributes']) {
			$product_data['product_attributes'] = $checkout_data['product_attributes'];
		}
		$product_data['additional'] = $checkout_data['additional'];
	}

	switch ($product_type) {
		case 'area':
			$product_data['width'] = $_POST['width'];
			$product_data['height'] = $_POST['height'];
		break;
		case 'variable':
			$product_data['attributes'] = $_POST['attributes'];
			$product_data['variation_id'] = (int)$_POST['variation_id'];
		break;
	}

	$send_quote_data['products'][$product_key] = $product_data;
	print_products_send_quote_set_order_data($send_quote_data);
}

function print_products_send_quote_duplicate_product() {
	$product_key = $_POST['product_key'];
	if ($product_key) {
		$new_product_key = md5(time());
		$send_quote_data = print_products_send_quote_get_order_data();
		$products = $send_quote_data['products'];
		$products[$new_product_key] = $products[$product_key];
		$send_quote_data['products'] = $products;
		print_products_send_quote_set_order_data($send_quote_data);
	}
}

function print_products_send_quote_delete_product() {
	$product_key = $_POST['product_key'];
	if ($product_key) {
		$send_quote_data = print_products_send_quote_get_order_data();
		$products = $send_quote_data['products'];
		unset($products[$product_key]);
		$send_quote_data['products'] = $products;
		print_products_send_quote_set_order_data($send_quote_data);
	}
}

function print_products_send_quote_save_order() {
	global $wpdb;
	$print_products_send_quote_options = get_option("print_products_send_quote_options");
	$send_quote_data = print_products_send_quote_get_order_data();
	$sender = $send_quote_data['sender'];
	$customer_id = (int)$send_quote_data['customer'];
	$products = $send_quote_data['products'];

	$expire_date = $_POST['expire_date'];
	$email_subject = trim($_POST['email_subject']);
	$email_message = trim($_POST['email_message']);

	// add record to db
	$insert = array();
	$insert['user_id'] = $customer_id;
	$insert['sender'] = $sender;
	$insert['expire_date'] = $expire_date;
	$insert['created'] = current_time('mysql');

	$wpdb->insert($wpdb->prefix."print_products_quotes", $insert);
	$order_id = $wpdb->insert_id;

	if ($products) {
		foreach($products as $product_data) {
			$insert = array();
			$insert['order_id'] = $order_id;
			$insert['product_id'] = $product_data['product_id'];
			$insert['product_type'] = $product_data['product_type'];
			$insert['quantity'] = $product_data['quantity'];
			$insert['price'] = $product_data['price'];
			$insert['smparams'] = $product_data['smparams'];
			$insert['fmparams'] = $product_data['fmparams'];
			$insert['product_attributes'] = $product_data['product_attributes'];
			$insert['additional'] = $product_data['additional'];
			$insert['artworkfiles'] = $product_data['artworkfiles'];

			$product_type = $product_data['product_type'];
			switch ($product_type) {
				case 'area':
					$insert['width'] = $product_data['width'];
					$insert['height'] = $product_data['height'];
				break;
				case 'variable':
					$insert['variation_id'] = $product_data['variation_id'];
					$insert['attributes'] = serialize($product_data['attributes']);
				break;
				case 'custom':
					$insert['attributes'] = $product_data['attributes'];
				break;
			}
			$wpdb->insert($wpdb->prefix."print_products_quotes_items", $insert);
		}
	}

	// send email
	print_products_send_quote_order_email($order_id, $email_subject, $email_message);

	unset($_SESSION['send_quote_data']);

	wp_redirect('admin.php?page=print-products-send-quote&step=completed&order='.$order_id);
	exit;
}

function print_products_send_quote_duplicate_order($order_id) {
	global $wpdb;
	$order_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_quotes WHERE order_id = '%s' AND status = 0", $wpdb->prefix, $order_id));
	if ($order_data) {
		$send_quote_data = array();
		$send_quote_data['sender'] = $order_data->sender;
		$send_quote_data['customer'] = $order_data->user_id;
		$send_quote_data['expire_date'] = $order_data->expire_date;

		$products = array();
		$order_items = print_products_send_quote_get_order_items($order_id);
		if ($order_items) {
			foreach($order_items as $oikey => $order_item) {
				$product_key = md5(time().$oikey);
				$product_id = $order_item->product_id;
				$product_data = array(
					'product_id' => $product_id,
					'name' => get_the_title($product_id),
					'product_type' => $order_item->product_type,
					'quantity' => $order_item->quantity,
					'price' => $order_item->price,
					'smparams' => $order_item->smparams,
					'fmparams' => $order_item->fmparams,
					'product_attributes' => $order_item->product_attributes,
					'artworkfiles' => $order_item->artworkfiles,
					'width' => $order_item->width,
					'height' => $order_item->height,
					'variation_id' => $order_item->variation_id,
					'attributes' => $order_item->attributes,
					'additional' => $order_item->additional
				);
				if ($order_item->product_type == 'custom') {
					$additional = unserialize($order_item->additional);
					$product_data['cptype'] = $additional['cptype'];
					$product_data['weight'] = $additional['weight'];
					$product_data['sboxes'] = $additional['sboxes'];
					$product_data['shipping_cost'] = $additional['shipping_cost'];
				}
				$products[$product_key] = $product_data;
			}
		}
		$send_quote_data['products'] = $products;

		print_products_send_quote_set_order_data($send_quote_data);
	}
}

function print_products_send_quote_order_email($order_id, $email_subject, $email_message) {
	global $wpdb;

	$send_quote_data = print_products_send_quote_get_order_data();
	$print_products_send_quote_options = get_option("print_products_send_quote_options");

	$order_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_quotes WHERE order_id = '%s'", $wpdb->prefix, $order_id));
	if ($order_data) {
		$customer_id = $order_data->user_id;
		$customer_data = get_userdata($customer_id);

		$pay_now_text = __('Pay now', 'wp2print');
		if (strlen($print_products_send_quote_options['pay_now_text'])) {
			$pay_now_text = $print_products_send_quote_options['pay_now_text'];
		}

		$pay_now_link = '<a href="'.site_url('?qorder=true&uid='.md5($customer_id).'&oid='.md5($order_id)).'" style="background:#0085ba; border:1px solid #006799; border-color:#0073aa #006799 #006799; border-radius:3px; color:#fff; font-size:13px; line-height:26px; height:26px; text-decoration:none;font-family:Arial; display:inline-block; padding:0 10px 1px;">'.$pay_now_text.'</a>';

		$quote_detail = print_products_send_quote_quote_detail($order_id);

		$email_message = nl2br($email_message);
		$email_message = '<p style="font-size:13px;">'.__('Customer', 'wp2print').': <strong>'.$customer_data->first_name.' '.$customer_data->last_name.' ('.$customer_data->user_email.')</strong></p>'.$email_message;
		$email_message = str_replace('{QUOTE-DETAIL}', $quote_detail, $email_message);
		$email_message = str_replace('{PAY-NOW-LINK}', $pay_now_link, $email_message);

		$email_message = print_products_send_quote_billing_info($customer_id, $email_message);

		$headers = 'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>' . "\r\n";
		if (strlen($print_products_send_quote_options['bcc_email'])) {
			$headers .= 'Bcc: '.$print_products_send_quote_options['bcc_email'] . "\r\n";
		}

		add_filter('wp_mail_content_type', function(){ return "text/html"; });
		if (isset($send_quote_data['sender']) && strlen($send_quote_data['sender'])) {
			add_filter('wp_mail_from', function($email_from){
				$send_quote_data = print_products_send_quote_get_order_data();
				return $send_quote_data['sender'];
			}, 11);
		}

		wp_mail($customer_data->user_email, $email_subject, $email_message, $headers);
	}
}

function print_products_send_quote_billing_info($customer_id, $email_message) {
	$billing_infos = array(
		'{billing-first-name}' => get_user_meta($customer_id, 'billing_first_name', true),
		'{billing-last-name}'  => get_user_meta($customer_id, 'billing_last_name', true),
		'{billing-company}'    => get_user_meta($customer_id, 'billing_company', true),
		'{billing-address1}'   => get_user_meta($customer_id, 'billing_address_1', true),
		'{billing-address2}'   => get_user_meta($customer_id, 'billing_address_2', true),
		'{billing-city}'       => get_user_meta($customer_id, 'billing_city', true),
		'{billing-state}'      => get_user_meta($customer_id, 'billing_state', true),
		'{billing-zip-code}'   => get_user_meta($customer_id, 'billing_postcode', true)
	);

	foreach($billing_infos as $bi_key => $bi_val) {
		if (strlen($bi_val)) {
			$email_message = str_replace($bi_key, $bi_val, $email_message);
		} else {
			$email_message = str_replace($bi_key.'<br />'."\r\n", '', $email_message);
			$email_message = str_replace($bi_key.' ', '', $email_message);
			$email_message = str_replace($bi_key, '', $email_message);
		}
	}

	return $email_message;
}

function print_products_send_quote_order_process() {
	global $wpdb;

	$uid = $_GET['uid'];
	$oid = $_GET['oid'];

	if (strlen($uid) && strlen($oid)) {
		$user_data = $wpdb->get_row(sprintf("SELECT * FROM %susers WHERE MD5(ID) = '%s'", $wpdb->prefix, $uid));
		$order_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_quotes WHERE MD5(order_id) = '%s'", $wpdb->prefix, $oid));
		if ($user_data && $order_data) {
			$order_id = $order_data->order_id;
			if (strlen($order_data->expire_date)) {
				$expire_date = strtotime($order_data->expire_date);
				if ($expire_date < time()) {
					$print_products_send_quote_options = get_option("print_products_send_quote_options");
					wp_die(nl2br($print_products_send_quote_options['expired_message']));
				}
			}
			// login user
			$user_id = $order_data->user_id;
			$user_data = get_userdata($user_id);
			$user_login = $user_data->user_login;
			wp_set_current_user($user_id, $user_login);
			wp_set_auth_cookie($user_id);
			do_action('wp_login', $user_login, $user);

			// add product to cart
			WC()->cart->empty_cart();
			$order_items = print_products_send_quote_get_order_items($order_id);
			if ($order_items) {
				foreach($order_items as $order_item) {
					$product_id = $order_item->product_id;
					$product_type = $order_item->product_type;
					$quantity = $order_item->quantity;
					$additional = unserialize($order_item->additional);

					if ($product_type == 'variable') { $product_id = $order_item->variation_id; }

					$_REQUEST['print_products_checkout_process_action'] = 'add-to-cart';
					$_REQUEST['product_type'] = $product_type;
					$_REQUEST['product_id'] = $product_id;
					$_REQUEST['add-to-cart'] = $product_id;
					$_REQUEST['quantity'] = $quantity;
					$_REQUEST['smparams'] = $order_item->smparams;
					$_REQUEST['fmparams'] = $order_item->fmparams;
					$_REQUEST['atcaction'] = 'artwork';
					$_REQUEST['artworkfiles'] = $order_item->artworkfiles;
					$_REQUEST['price'] = $order_item->price;

					switch ($product_type) {
						case 'book':
							if ($additional['page_quantity'] && is_array($additional['page_quantity'])) {
								foreach($additional['page_quantity'] as $mtype_id => $pq) {
									$_REQUEST['page_quantity_'.$mtype_id] = $pq;
								}
							}
						break;
						case 'area':
							$_REQUEST['width'] = $order_item->width;
							$_REQUEST['height'] = $order_item->height;
						break;
						case 'custom':
							$_REQUEST['attributes'] = $order_item->attributes;
							$_REQUEST['shipping_specify'] = $additional['shipping_specify'];
							$_REQUEST['weight'] = $additional['weight'];
							$_REQUEST['sboxes'] = $additional['sboxes'];
							$_REQUEST['shipping_cost'] = $additional['shipping_cost'];
							$_REQUEST['cptype'] = $additional['cptype'];
						break;
						case 'variable':
							$_REQUEST['variation_id'] = $order_item->variation_id;
						break;
					}

					$cart_item_data = array();
					$cart_item_data['unique_key'] = md5(microtime() . rand() . md5($product_id));
					WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);
				}
			}

			// update quote status
			$wpdb->update($wpdb->prefix.'print_products_quotes', array('status' => '1'), array('order_id' => $order_id));

			// redirect to checkout page
			$checkout_url = WC()->cart->get_checkout_url();
			wp_redirect($checkout_url);
			exit;
		}
	}
}

function print_products_send_quote_object_to_array($product_data) {
	return array(
		'product_id' => $product_data->product_id,
		'product_type' => $product_data->product_type,
		'variation_id' => $product_data->variation_id,
		'attributes' => $product_data->attributes,
		'quantity' => $product_data->quantity,
		'price' => $product_data->price,
		'smparams' => $product_data->smparams,
		'fmparams' => $product_data->fmparams,
		'width' => $product_data->width,
		'height' => $product_data->height,
		'product_attributes' => $product_data->product_attributes,
		'additional' => $product_data->additional,
		'artworkfiles' => $product_data->artworkfiles
	);
}

function print_products_send_quote_product_data_html($product_data) {
	global $wpdb;
	if (is_object($product_data)) { $product_data = print_products_send_quote_object_to_array($product_data); }
	$product_id = $product_data['product_id'];
	$product_type = $product_data['product_type'];
	$dimension_unit = print_products_get_dimension_unit();
	$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
	$attribute_display = (array)get_post_meta($product_id, '_attribute_display', true);
	$product_attributes = unserialize($product_data['product_attributes']);
	$additional = unserialize($product_data['additional']); ?>
	<ul style="margin-bottom:0px;">
		<?php if ($product_type == 'area') {
			echo '<li>'.print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')).' ('.$dimension_unit.'): <strong>'.$product_data['width'].'</strong></li>';
			echo '<li>'.print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')).' ('.$dimension_unit.'): <strong>'.$product_data['height'].'</strong></li>';
		}
		if ($product_attributes) {
			$product_attributes = print_products_quantity_mailed_product_attributes($product_attributes, $additional);
			$attr_terms = print_products_get_attributes_vals($product_attributes, $product_type, $attribute_labels, $attribute_display);
			echo '<li>'.implode('</li><li>', $attr_terms).'</li>';
		}
		if ($product_type == 'custom') {
			if ($product_data['attributes']) { echo '<li>'.nl2br($product_data['attributes']).'</li>'; }
			if ($product_data['additional']) {
				$additional = unserialize($product_data['additional']);
				if ($additional['weight']) { echo '<li>'.__('Weight', 'wp2print').' ('.print_products_get_weight_unit().'): <strong>'.$additional['weight'].'</strong></li>'; }
				if ($additional['sboxes']) { echo '<li>'.__('Shipping box count', 'wp2print').': <strong>'.$additional['sboxes'].'</strong></li>'; }
				if ($additional['shipping_cost']) { echo '<li>'.__('Shipping cost', 'wp2print').': <strong>'.wc_price($additional['shipping_cost']).'</strong></li>'; }
			}
		}
		if ($product_type == 'variable') {
			$attribute_names = array();
			$wc_attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
			if ($wc_attributes) {
				foreach($wc_attributes as $wc_attribute) {
					$attribute_names[$wc_attribute->attribute_name] = $wc_attribute->attribute_label;
				}
			}

			if (isset($product_data['variation_id']) && $product_data['variation_id']) {
				$variation_id = $product_data['variation_id'];
				$product = wc_get_product($variation_id);
				if ($product) {
					$attributes = $product->get_attributes();
					if ($attributes) {
						foreach($attributes as $akey => $aval) {
							$aname = str_replace('pa_', '', $akey);
							echo '<li>'.$attribute_names[$aname].': <strong>'.$product->get_attribute($akey).'</strong></li>';
						}
					}
				}
			}
		}
		if (isset($product_data['artworkfiles']) && strlen($product_data['artworkfiles'])) {
			$afiles = array();
			$artworkfiles = explode(';', $product_data['artworkfiles']);
			foreach($artworkfiles as $afile) {
				$afiles[] = basename($afile);
			}
			echo '<li>'.__('Artwork Files', 'wp2print').': <strong>'.implode(', ', $afiles).'</strong></li>';
		}
		?>
	</ul>
	<?php
}

function print_products_send_quote_quote_detail($order_id) {
	global $wpdb;
	$quote_detail = '<table border="0" style="width:100%;font-size:13px;"><tbody>';

	$products = print_products_send_quote_get_order_items($order_id);
	if ($products) {
		foreach($products as $product) {
			$product_id = $product->product_id;
			$product_data = array(
				'product_type' => $product->product_type,
				'quantity' => $product->quantity,
				'price' => $product->price,
				'smparams' => $product->smparams,
				'fmparams' => $product->fmparams,
				'width' => $product->width,
				'height' => $product->height,
				'product_attributes' => $product->product_attributes,
				'additional' => $product->additional,
				'attributes' => $product->attributes,
				'artworkfiles' => $product->artworkfiles
			);

			$product_type = $product_data['product_type'];
			$dimension_unit = print_products_get_dimension_unit();
			$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
			$attribute_display = (array)get_post_meta($product_id, '_attribute_display', true);
			$product_attributes = unserialize($product_data['product_attributes']);
			$additional = unserialize($product_data['additional']);
			$product_name = get_the_title($product_id);
			if (print_products_is_custom_product($product_id) && $additional['cptype'] && strlen($additional['cptype'])) {
				$product_name = $additional['cptype'];
			}

			$quote_detail .= '<tr><td>'.__('Product', 'wp2print').': <strong>'.$product_name.'</strong></td></tr>';
			$quote_detail .= '<tr><td>'.__('Quantity', 'wp2print').': <strong>'.$product_data['quantity'].'</strong></td></tr>';
			if ($product_type == 'area') {
				$quote_detail .= '<tr><td>'.print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')).' ('.$dimension_unit.'): <strong>'.$product_data['width'].'</strong></td></tr>';
				$quote_detail .= '<tr><td>'.print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')).' ('.$dimension_unit.'): <strong>'.$product_data['height'].'</strong></td></tr>';
			}
			if ($product_attributes) {
				$attr_terms = print_products_get_attributes_vals($product_attributes, $product_type, $attribute_labels, $attribute_display);
				$quote_detail .= '<tr><td>'.implode('</td></tr><tr><td>', $attr_terms).'</td></tr>';
			}
			if ($product_type == 'custom') {
				if ($product_data['attributes']) { $quote_detail .= '<tr><td>'.nl2br($product_data['attributes']).'</td></tr>'; }
				if ($additional['weight']) { $quote_detail .= '<tr><td>'.__('Weight', 'wp2print').' ('.print_products_get_weight_unit().'): <strong>'.$additional['weight'].'</strong></td></tr>'; }
				if ($additional['sboxes']) { $quote_detail .= '<tr><td>'.__('Shipping box count', 'wp2print').': <strong>'.$additional['sboxes'].'</strong></td></tr>'; }
				if ($additional['shipping_cost']) { $quote_detail .= '<tr><td>'.__('Shipping cost', 'wp2print').': <strong>'.wc_price($additional['shipping_cost']).'</strong></td></tr>'; }
			}
			if ($product_type == 'variable') {
				$attribute_names = array();
				$wc_attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
				if ($wc_attributes) {
					foreach($wc_attributes as $wc_attribute) {
						$attribute_names[$wc_attribute->attribute_name] = $wc_attribute->attribute_label;
					}
				}

				$variation_id = $product_data['variation_id'];
				$product = wc_get_product($variation_id);
				$attributes = $product->get_attributes();
				if ($attributes) {
					foreach($attributes as $akey => $aval) {
						$aname = str_replace('pa_', '', $akey);
						$quote_detail .= '<tr><td>'.$attribute_names[$aname].': <strong>'.$product->get_attribute($akey).'</strong></td></tr>';
					}
				}
			}
			if (strlen($product_data['artworkfiles'])) {
				$afiles = array();
				$artworkfiles = explode(';', $product_data['artworkfiles']);
				foreach($artworkfiles as $afile) {
					$afiles[] = basename($afile);
				}
				$quote_detail .= '<tr><td>'.__('Artwork Files', 'wp2print').': <strong>'.implode(', ', $afiles).'</strong></td></tr>';
			}
			$quote_detail .= '<tr><td>'.__('Subtotal', 'wp2print').': <strong>'.wc_price($product_data['price']).'</strong></td></tr>';
			if (count($products) > 1) {
				$quote_detail .= '<tr><td>-------------------------------------------------------------------------</td></tr>';
			}
		}
	}
	$quote_detail .= '</tbody></table>';

	return $quote_detail;
}

add_action('init', 'print_products_send_quote_cron_job');
function print_products_send_quote_cron_job() {
	if (!wp_next_scheduled('print_products_send_quote_cron')) {
		wp_schedule_event(mktime(6, 0, 0, date("m"), date("d"), date("Y")), 'daily', 'print_products_send_quote_cron');
	}
}

add_action('print_products_send_quote_cron', 'print_products_send_quote_cron_actions');
function print_products_send_quote_cron_actions() {
	global $wpdb;
	$cdate = date('Y-m-d');
	$ppsqc_date = get_option('ppsqc_date');
	$print_products_send_quote_options = get_option("print_products_send_quote_options");
	if ($ppsqc_date != $cdate && ((int)$print_products_send_quote_options['send_email2'] || (int)$print_products_send_quote_options['send_email3'])) {
		$send_email2_days = (int)$print_products_send_quote_options['send_email2_days'];
		if ((int)$print_products_send_quote_options['send_email2'] && $send_email2_days) {
			$email_subject2 = $print_products_send_quote_options['email_subject2'];
			$email_message2 = $print_products_send_quote_options['email_message2'];
			$adate = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - $send_email2_days, date('Y')));
			$quote_orders = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_quotes WHERE status = 0 AND created >= '%s' AND created <= '%s' ORDER BY order_id", $wpdb->prefix, $adate.' 00:00:00', $adate.' 23:59:59'));
			if ($quote_orders) {
				foreach($quote_orders as $quote_order) {
					print_products_send_quote_order_email($quote_order->order_id, $email_subject2, $email_message2);
				}
			}
		}

		$send_email3_days = (int)$print_products_send_quote_options['send_email3_days'];
		if ((int)$print_products_send_quote_options['send_email3'] && $send_email3_days) {
			$email_subject3 = $print_products_send_quote_options['email_subject3'];
			$email_message3 = $print_products_send_quote_options['email_message3'];
			$adate = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - $send_email3_days, date('Y')));
			$quote_orders = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_quotes WHERE status = 0 AND created >= '%s' AND created <= '%s' ORDER BY order_id", $wpdb->prefix, $adate.' 00:00:00', $adate.' 23:59:59'));
			if ($quote_orders) {
				foreach($quote_orders as $quote_order) {
					print_products_send_quote_order_email($quote_order->order_id, $email_subject3, $email_message3);
				}
			}
		}
		update_option('ppsqc_date', $cdate);
	}
}
?>