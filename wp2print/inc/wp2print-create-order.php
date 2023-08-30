<?php
function print_products_create_order_admin_page() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-create-order.php';
}

add_action('wp_loaded', 'print_products_create_order_actions');
function print_products_create_order_actions() {
	if (isset($_POST['print_products_create_order_action']) && $_POST['print_products_create_order_action'] == 'process') {
		$order_data = print_products_create_order_get_order_data();
		switch ($_POST['process_step']) {
			case '1':
				print_products_create_order_set_customer();
			break;
			case '2':
				print_products_create_order_set_customer_address();
			break;
			case '3':
				if (isset($_POST['product_action']) && strlen($_POST['product_action'])) {
					$redirect = 'admin.php?page=print-products-create-order&step=3';
					if ($_POST['product_action'] == 'add') {
						$product_key = print_products_create_order_add_product();
						$redirect .= '&product_key='.$product_key;
					} else if ($_POST['product_action'] == 'attributes') {
						print_products_create_order_set_product_data();
					} else if ($_POST['product_action'] == 'duplicate') {
						print_products_create_order_duplicate_product();
					} else if ($_POST['product_action'] == 'delete') {
						print_products_create_order_delete_product();
					}
					wp_redirect($redirect);
					exit;
				}
			break;
			case 'create':
				print_products_create_order_save_order();
			break;
		}
	}
	if (isset($_POST['AjaxAction']) && $_POST['AjaxAction'] == 'create-order-add-user') {
		$user_login = sanitize_user($_POST['u_username']);
		$user_email = sanitize_text_field($_POST['u_email']);
		$user_fname = sanitize_text_field($_POST['u_fname']);
		$user_lname = sanitize_text_field($_POST['u_lname']);
		$user_pass = sanitize_text_field($_POST['u_pass']);
		$print_products_create_order_options = get_option("print_products_create_order_options");

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
			$subject = $print_products_create_order_options['cnu_email_subject'];
			$message = $print_products_create_order_options['cnu_email_message'];
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
	if (isset($_POST['user_orders_action']) && $_POST['user_orders_action'] == 'reorder') {
		$order_id = (int)$_POST['order_id'];
		$item_id = (int)$_POST['item_id'];
		if ($order_id && $item_id) {
			print_products_create_order_duplicate_order($order_id, $item_id);
			wp_redirect('admin.php?page=print-products-create-order');
			exit;
		}
	}
}

function print_products_create_order_get_order_data() {
	$order_data = array();
	if (isset($_SESSION['create_order_data'])) { $order_data = $_SESSION['create_order_data']; }
	return $order_data;
}

function print_products_create_order_set_order_data($order_data) {
	$_SESSION['create_order_data'] = $order_data;
}

function print_products_create_order_set_customer() {
	$order_data = print_products_create_order_get_order_data();
	$order_data['customer'] = $_POST['order_customer'];
	print_products_create_order_set_order_data($order_data);
	
}

function print_products_create_order_set_customer_address() {
	$order_data = print_products_create_order_get_order_data();
	$order_data['billing_address'] = $_POST['billing_address'];
	$order_data['shipping_address'] = $_POST['shipping_address'];
	print_products_create_order_set_order_data($order_data);
	
}

function print_products_create_order_add_product() {
	$product_id = (int)$_POST['order_product'];
	$product_key = md5(time());

	$product_name = get_the_title($product_id);

	$products = array();
	$order_data = print_products_create_order_get_order_data();
	if (isset($order_data['products'])) {
		$products = $order_data['products'];
	}
	$products[$product_key] = array('product_id' => $product_id, 'name' => $product_name, 'cptype' => $_POST['cptype']);

	$order_data['products'] = $products;
	print_products_create_order_set_order_data($order_data);
	return $product_key;
}

function print_products_create_order_set_product_data() {
	$product_key = $_POST['product_key'];
	$product_type = $_POST['product_type'];
	$quantity = (int)$_POST['quantity'];
	$price = (float)$_POST['price'];

	$order_data = print_products_create_order_get_order_data();

	$product_data = $order_data['products'][$product_key];
	$product_data['product_type'] = $product_type;
	$product_data['quantity'] = $quantity;
	$product_data['price'] = $price;
	$product_data['atcaction'] = 'artwork';
	

	if ($product_type == 'custom') {
		$product_data['attributes'] = $_POST['attributes'];
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
		case 'aec':
			$checkout_data = print_products_checkout_aec($product_key, false, true);
		break;
		case 'aecbwc':
			$checkout_data = print_products_checkout_aecbwc($product_key, false, true);
		break;
		case 'aecsimple':
			$checkout_data = print_products_checkout_aecsimple($product_key, false, true);
		break;
		case 'custom':
			$custom_data = array('attributes' => $product_data['attributes'], 'cptype' => $product_data['cptype']);
			$checkout_data = array(
				'additional' => serialize($custom_data)
			);
		break;
	}

	if ($checkout_data) {
		$product_data['smparams'] = $_POST['smparams'];
		$product_data['fmparams'] = $_POST['fmparams'];
		$product_data['product_attributes'] = $checkout_data['product_attributes'];
		$product_data['additional'] = $checkout_data['additional'];
	}

	switch ($product_type) {
		case 'area':
			$product_data['width'] = $_POST['width'];
			$product_data['height'] = $_POST['height'];
		break;
		case 'aec':
			$product_data['project_name'] = $_POST['aec_project_name'];
			$product_data['total_area'] = $_POST['aec_total_area'];
			$product_data['total_pages'] = $_POST['aec_total_pages'];
		break;
		case 'aecbwc':
			$product_data['project_name'] = $_POST['aec_project_name'];
			$product_data['total_area'] = $_POST['aec_total_area'];
			$product_data['total_pages'] = $_POST['aec_total_pages'];
			$product_data['area_bw'] = $_POST['aec_area_bw'];
			$product_data['pages_bw'] = $_POST['aec_pages_bw'];
			$product_data['area_cl'] = $_POST['aec_area_cl'];
			$product_data['pages_cl'] = $_POST['aec_pages_cl'];
		break;
		case 'aecsimple':
			$product_data['project_name'] = $_POST['aec_project_name'];
			$product_data['total_area'] = $_POST['aec_total_area'];
			$product_data['total_pages'] = $_POST['aec_total_pages'];
		break;
		case 'variable':
			$product_data['attributes'] = $_POST['attributes'];
			$product_data['variation_id'] = (int)$_POST['variation_id'];
		break;
	}

	$order_data['products'][$product_key] = $product_data;
	print_products_create_order_set_order_data($order_data);
}

function print_products_create_order_duplicate_product() {
	$product_key = $_POST['product_key'];
	if ($product_key) {
		$new_product_key = md5(time());
		$order_data = print_products_create_order_get_order_data();
		$products = $order_data['products'];
		$products[$new_product_key] = $products[$product_key];
		$order_data['products'] = $products;
		print_products_create_order_set_order_data($order_data);
	}
}

function print_products_create_order_delete_product() {
	$product_key = $_POST['product_key'];
	if ($product_key) {
		$order_data = print_products_create_order_get_order_data();
		$products = $order_data['products'];
		unset($products[$product_key]);
		$order_data['products'] = $products;
		print_products_create_order_set_order_data($order_data);
	}
}

function print_products_create_order_duplicate_order($order_id, $order_item_id) {
	global $wpdb;
	$order = wc_get_order($order_id);
	if ($order) {
		$customer_id = $order->get_customer_id();

		$billing_address = array(
			'company' => $order->get_billing_company(),
			'address_1' => $order->get_billing_address_1(),
			'address_2' => $order->get_billing_address_2(),
			'city' => $order->get_billing_city(),
			'postcode' => $order->get_billing_postcode(),
			'country' => $order->get_billing_country(),
			'state' => $order->get_billing_state(),
			'email' => $order->get_billing_email(),
			'phone' => $order->get_billing_phone()
		);

		$shipping_address = array(
			'company' => $order->get_shipping_company(),
			'address_1' => $order->get_shipping_address_1(),
			'address_2' => $order->get_shipping_address_2(),
			'city' => $order->get_shipping_city(),
			'postcode' => $order->get_shipping_postcode(),
			'country' => $order->get_shipping_country(),
			'state' => $order->get_shipping_state()
		);

		$products = array();
		$order_items = $order->get_items('line_item');
		if ($order_items) {
			foreach($order_items as $item_id => $item) {
				if ($item_id == $order_item_id) {
					$product_key = md5(time());
					$product_id = $item->get_product_id();
					$product_name = get_the_title($product_id);
					$quantity = $item->get_quantity();
					$price = $item->get_total();
					$product_attributes = '';
					$additional = '';
					$artwork_files = '';
					$artwork_thumbs = '';
					$atcaction = '';

					$print_products_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s", $wpdb->prefix, $item_id));
					if ($print_products_data) {
						$product_type = $print_products_data->product_type;
						$quantity = $print_products_data->quantity;
						$price = $print_products_data->price;
						$product_attributes = $print_products_data->product_attributes;
						$additional = $print_products_data->additional;
						$artwork_files = $print_products_data->artwork_files;
						$artwork_thumbs = $print_products_data->artwork_thumbs;
						$atcaction = $print_products_data->atcaction;
					}

					$product_data = array(
						'product_id' => $product_id,
						'name' => $product_name,
						'product_type' => $product_type,
						'quantity' => $quantity,
						'price' => $price,
						'product_attributes' => $product_attributes,
						'artwork_files' => $artwork_files,
						'artwork_thumbs' => $artwork_thumbs,
						'atcaction' => $atcaction
					);
					if (strlen($additional)) {
						$additional = unserialize($additional);
						foreach($additional as $akey => $aval) {
							$product_data[$akey] = $aval;
						}
					}
					$pdf_link = wc_get_order_item_meta($item_id, '_pdf_link');
					if (strlen($pdf_link)) {
						$product_data['pdf_link'] = $pdf_link;
						$product_data['edit_session_key'] = wc_get_order_item_meta($item_id, '_edit_session_key');
						$product_data['image_link'] = wc_get_order_item_meta($item_id, '_image_link');
						$db_link_key = wc_get_order_item_meta($item_id, '_db_link_key');
						if (strlen($db_link_key)) {
							$product_data['db_link_key'] = $db_link_key;
						}
					}
					$products[$product_key] = $product_data;
				}
			}
		}

		$order_data = array();
		$order_data['customer'] = $customer_id;
		$order_data['billing_address'] = $billing_address;
		$order_data['shipping_address'] = $shipping_address;
		$order_data['products'] = $products;
		print_products_create_order_set_order_data($order_data);
	}
}

function print_products_create_order_product_data_html($product_data) {
	global $wpdb;
	$product_id = $product_data['product_id'];
	$product_type = $product_data['product_type'];
	$dimension_unit = print_products_get_aec_dimension_unit();
	$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
	$attribute_display = (array)get_post_meta($product_id, '_attribute_display', true);
	$product_attributes = unserialize($product_data['product_attributes']);
	$additional = unserialize($product_data['additional']);
	$artwork_files = '';
	if (isset($product_data['artwork_files']) && strlen($product_data['artwork_files'])) {
		$artwork_files = unserialize($product_data['artwork_files']);
	}
	if ($product_type != 'simple') { ?>
		<ul style="margin-bottom:0px;">
			<?php if ($product_type == 'area') {
				echo '<li>'.print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')).' ('.$dimension_unit.'): <strong>'.$product_data['width'].'</strong></li>';
				echo '<li>'.print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')).' ('.$dimension_unit.'): <strong>'.$product_data['height'].'</strong></li>';
			}
			if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') {
				$project_name = $product_data['project_name'];
				if ($project_name) {
					echo '<li>'.__('Project Name', 'wp2print').': <strong>'.$project_name.'</strong></li>';
				}
			}
			if ($product_attributes) {
				$product_attributes = print_products_quantity_mailed_product_attributes($product_attributes, $additional);
				$attr_terms = print_products_get_attributes_vals($product_attributes, $product_type, $attribute_labels, $attribute_display);
				echo '<li>'.implode('</li><li>', $attr_terms).'</li>';
			}
			if ($product_type == 'aec' || $product_type == 'aecsimple') {
				$total_area = $product_data['total_area'];
				$total_pages = $product_data['total_pages'];
				if ($total_area) {
					echo '<li>'.__('Total Area', 'wp2print').': <strong>'.number_format($total_area, 2).' '.$dimension_unit.'<sup>2</sup></strong></li>';
				}
				if ($total_pages) {
					echo '<li>'.__('Total Pages', 'wp2print').': <strong>'.$total_pages.'</strong></li>';
				}
			} else if ($product_type == 'aecbwc') {
				$total_area = $product_data['total_area'];
				$total_pages = $product_data['total_pages'];
				$area_bw = $product_data['area_bw'];
				$pages_bw = $product_data['pages_bw'];
				$area_cl = $product_data['area_cl'];
				$pages_cl = $product_data['pages_cl'];
				if ($total_area) {
					echo '<li>'.__('Total Area', 'wp2print').': <strong>'.number_format($total_area, 2).' '.$dimension_unit.'<sup>2</sup></strong></li>';
				}
				if ($total_pages) {
					echo '<li>'.__('Total Pages', 'wp2print').': <strong>'.$total_pages.'</strong></li>';
				}
				if ($area_bw) {
					echo '<li>'.__('Area B/W', 'wp2print').': <strong>'.number_format($area_bw, 2).' '.$dimension_unit.'<sup>2</sup></strong></li>';
				}
				if ($pages_bw) {
					echo '<li>'.__('Pages B/W', 'wp2print').': <strong>'.$pages_bw.'</strong></li>';
				}
				if ($area_cl) {
					echo '<li>'.__('Area Color', 'wp2print').': <strong>'.number_format($area_cl, 2).' '.$dimension_unit.'<sup>2</sup></strong></li>';
				}
				if ($pages_cl) {
					echo '<li>'.__('Pages Color', 'wp2print').': <strong>'.$pages_cl.'</strong></li>';
				}
			} else if ($product_type == 'variable') {
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
						echo '<li>'.$attribute_names[$aname].': <strong>'.$product->get_attribute($akey).'</strong></li>';
					}
				}
			} else if ($product_type == 'custom') {
				if ($product_data['attributes']) { echo '<li>'.nl2br($product_data['attributes']).'</li>'; }
			}
			if ($artwork_files) {
				?>
				<li><?php _e('Artwork Files', 'wp2print'); ?>:</li>
				<li>
					<?php foreach($artwork_files as $af_key => $artwork_file) {
						echo '<a href="'.print_products_get_amazon_file_url($artwork_file).'" title="'.__('Download', 'wp2print').'" target="_blank">'.basename($artwork_file).'</a><br>';
					} ?>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
}

function print_products_create_order_get_address_html($address) {
	$address_lines = array();
	if (strlen($address['company'])) {
		$address_lines[] = $address['company'];
	}
	if (strlen($address['address_1'])) {
		$address_lines[] = $address['address_1'];
	}
	if (strlen($address['address_2'])) {
		$address_lines[] = $address['address_2'];
	}
	$address_lines[] = $address['city'].', '.$address['state'].' '.$address['postcode'].', '.$address['country'];
	if (strlen($address['email'])) {
		$address_lines[] = $address['email'];
	}
	if (strlen($address['phone'])) {
		$address_lines[] = $address['phone'];
	}
	return implode('<br>', $address_lines);
}

function print_products_create_order_get_customer_address($user_id, $atype) {
	$address = false;
	if ($atype == 'billing') {
		$billing_company = get_user_meta($user_id, 'billing_company', true);
		if ($billing_company) {
			$address = array(
				'country' => get_user_meta($user_id, 'billing_country', true),
				'address_1' => get_user_meta($user_id, 'billing_address_1', true),
				'address_2' => get_user_meta($user_id, 'billing_address_2', true),
				'city' => get_user_meta($user_id, 'billing_city', true),
				'state' => get_user_meta($user_id, 'billing_state', true),
				'postcode' => get_user_meta($user_id, 'billing_postcode', true),
				'company' => get_user_meta($user_id, 'billing_company', true),
				'email' => get_user_meta($user_id, 'billing_email', true),
				'phone' => get_user_meta($user_id, 'billing_phone', true)
			);
		}
	} else {
		$shipping_company = get_user_meta($user_id, 'shipping_company', true);
		if ($shipping_company) {
			$address = array(
				'country' => get_user_meta($user_id, 'shipping_country', true),
				'address_1' => get_user_meta($user_id, 'shipping_address_1', true),
				'address_2' => get_user_meta($user_id, 'shipping_address_2', true),
				'city' => get_user_meta($user_id, 'shipping_city', true),
				'state' => get_user_meta($user_id, 'shipping_state', true),
				'postcode' => get_user_meta($user_id, 'shipping_postcode', true),
				'company' => get_user_meta($user_id, 'shipping_company', true)
			);
		}
	}
	return $address;
}

function print_products_create_order_save_order() {
	global $wpdb;
	$subtotal = (float)$_POST['price'];
	$tax = (float)$_POST['tax'];
	$tax_rate_id = $_POST['tax_rate_id'];
	$shipping = (float)$_POST['shipping'];
	$shipping_tax = (float)$_POST['shipping_tax'];
	$total = (float)$_POST['total'];

	$order_data = print_products_create_order_get_order_data();
	$customer_id = (int)$order_data['customer'];
	$products = $order_data['products'];

	$customer_data = get_userdata($customer_id);

	$billing_address = array(
       'first_name' => $customer_data->first_name,
       'last_name'  => $customer_data->last_name,
       'company'    => $order_data['billing_address']['company'],
       'email'      => $order_data['billing_address']['email'],
       'phone'      => $order_data['billing_address']['phone'],
       'address_1'  => $order_data['billing_address']['address_1'],
       'address_2'  => $order_data['billing_address']['address_2'],
       'city'       => $order_data['billing_address']['city'],
       'state'      => $order_data['billing_address']['state'],
       'postcode'   => $order_data['billing_address']['postcode'],
       'country'    => $order_data['billing_address']['country']
	);
	$shipping_address = array(
       'first_name' => $customer_data->first_name,
       'last_name' => $customer_data->last_name,
       'company'    => $order_data['shipping_address']['company'],
       'address_1'  => $order_data['shipping_address']['address_1'],
       'address_2'  => $order_data['shipping_address']['address_2'],
       'city'       => $order_data['shipping_address']['city'],
       'state'      => $order_data['shipping_address']['state'],
       'postcode'   => $order_data['shipping_address']['postcode'],
       'country'    => $order_data['shipping_address']['country']
	);

	$order = wc_create_order(array('customer_id' => $customer_id));
	$order->set_address($billing_address, 'billing');
	$order->set_address($shipping_address, 'shipping');

	if ($products) {
		foreach($products as $product_key => $product) {
			$product_id = $product['product_id'];
			$product_type = $product['product_type'];
			$quantity = (int)$product['quantity'];

			$order_item_id = $order->add_product(get_product($product_id), $quantity, array('totals' => array('tax' => $tax)));
			$products[$product_key]['order_item_id'] = $order_item_id;
		}
	}

	$order->calculate_totals();
	$order->set_total($total);

	$order_id = $order->get_id();

	wp_update_post(array('ID' => $order_id, 'post_status' => 'wc-on-hold'));

	update_post_meta($order_id, '_order_total', $total);
	update_post_meta($order_id, '_order_tax', $tax);
	update_post_meta($order_id, '_order_shipping', $shipping);

	print_products_create_order_set_shipping_meta($order_id, $shipping);
	print_products_create_order_set_tax_meta($order_id, $tax, $shipping_tax);

	if ($products) {
		foreach($products as $product) {
			$order_item_id = $product['order_item_id'];
			$price = (float)$product['price'];

			if ($order_item_id) {
				wc_update_order_item_meta($order_item_id, '_line_subtotal', $price);
				wc_update_order_item_meta($order_item_id, '_line_total', $price);

				if (isset($product['pdf_link'])) {
					wc_update_order_item_meta($order_item_id, '_edit_session_key', $product['edit_session_key']);
					wc_update_order_item_meta($order_item_id, '_pdf_link', $product['pdf_link']);
					wc_update_order_item_meta($order_item_id, '_image_link', $product['image_link']);
					if (isset($product['db_link_key'])) {
						wc_update_order_item_meta($order_item_id, '_db_link_key', $product['db_link_key']);
					}
				}

				// add record to print_products_order_items
				if ($product['product_attributes'] || $product['attributes']) {
					$insert = array();
					$insert['item_id'] = $order_item_id;
					$insert['product_id'] = $product['product_id'];
					$insert['product_type'] = $product['product_type'];
					$insert['quantity'] = $product['quantity'];
					$insert['price'] = $price;
					$insert['product_attributes'] = $product['product_attributes'];
					$insert['additional'] = $product['additional'];
					$insert['atcaction'] = $product['atcaction'];
					$wpdb->insert($wpdb->prefix."print_products_order_items", $insert);
				}
			}
		}
	}

	print_products_create_order_set_tax_item_meta('tax', $order_id, $tax_rate_id, $tax);
	print_products_create_order_set_tax_item_meta('shipping_tax', $order_id, $tax_rate_id, $shipping_tax);

	unset($_SESSION['create_order_data']);

	wp_redirect('admin.php?page=print-products-create-order&step=completed&order='.$order_id);
	exit;
}

function print_products_create_order_get_order_item_attributes($item_id) {
	global $wpdb;
	$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));
	if ($order_item_data) {
		print_products_product_attributes_list_html($order_item_data);
	}
}

function print_products_create_order_set_shipping_meta($order_id, $value) {
	$order_item_id = wc_add_order_item($order_id, array('order_item_name' => __('Shipping', 'wp2print'), 'order_item_type' => 'shipping'));
	if ($order_item_id) {
		wc_add_order_item_meta($order_item_id, 'method_id', 'flat_rate:1');
		wc_add_order_item_meta($order_item_id, 'cost', $value);
	}
}

function print_products_create_order_set_tax_meta($order_id, $value, $stax) {
	global $wpdb;
	$order_item_id = $wpdb->get_var(sprintf("SELECT order_item_id FROM %swoocommerce_order_items WHERE order_id = %s AND order_item_type = 'tax'", $wpdb->prefix, $order_id));
	if (!$order_item_id) {
		$order_item_id = wc_add_order_item($order_id, array('order_item_name' => __('Tax', 'wp2print'), 'order_item_type' => 'tax'));
	}
	if ($order_item_id) {
		wc_update_order_item_meta($order_item_id, 'tax_amount', $value);
		wc_update_order_item_meta($order_item_id, 'shipping_tax_amount', $stax);
	}
}

function print_products_create_order_set_tax_item_meta($type, $order_id, $tax_rate_id, $tax) {
	global $wpdb;
	if ($type == 'shipping_tax') {
		$shipping_order_item_id = $wpdb->get_var(sprintf("SELECT order_item_id FROM %swoocommerce_order_items WHERE order_id = %s AND order_item_type = 'shipping'", $wpdb->prefix, $order_id));
		if ($shipping_order_item_id) {
			$taxes = array('total' => array($tax_rate_id => (string)$tax));
			wc_add_order_item_meta($shipping_order_item_id, 'total_tax', $tax);
			wc_add_order_item_meta($shipping_order_item_id, 'taxes', $taxes);
		}
	} else {
		$order_line_items = $wpdb->get_results(sprintf("SELECT order_item_id FROM %swoocommerce_order_items WHERE order_id = %s AND order_item_type = 'line_item'", $wpdb->prefix, $order_id));
		if ($order_line_items) {
			foreach($order_line_items as $order_line_item) {
				$line_item_order_item_id = $order_line_item->order_item_id;
				if ($line_item_order_item_id) {
					$line_tax_data = wc_get_order_item_meta($line_item_order_item_id, '_line_tax_data');
					if ($line_tax_data) {
						foreach($line_tax_data as $akey => $aarray) {
							foreach($aarray as $ak => $aval) {
								$aarray[$tax_rate_id] = (string)$tax;
							}
							$line_tax_data[$akey] = $aarray;
						}
					}
					wc_update_order_item_meta($line_item_order_item_id, '_line_tax_data', $line_tax_data);
					wc_update_order_item_meta($line_item_order_item_id, '_line_subtotal_tax', $tax);
					wc_update_order_item_meta($line_item_order_item_id, '_line_tax', $tax);
				}
			}
		}
	}
}

function print_products_create_order_get_order_shipping_tax($order_id) {
	global $wpdb;
	$shipping_order_item_id = $wpdb->get_var(sprintf("SELECT order_item_id FROM %swoocommerce_order_items WHERE order_id = %s AND order_item_type = 'shipping'", $wpdb->prefix, $order_id));
	if ($shipping_order_item_id) {
		return wc_get_order_item_meta($shipping_order_item_id, 'total_tax');
	}
}

function print_products_create_order_totals_box() {
	$tax_rate_id = 1;
	$tax_rate = 0;
	$order_data = print_products_create_order_get_order_data();

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
	<div class="co-box" style="margin-top:15px;">
		<p class="form-field">
			<label><?php _e('Subtotal', 'wp2print'); ?>: <span class="req">*</span></label>
			<input type="text" name="price" class="p-price" value="<?php if ($order_data['subtotal']) { echo $product_data['subtotal']; } ?>" onblur="matrix_set_tax(); matrix_set_prices();">
		</p>
		<p class="form-field">
			<label><?php _e('Tax', 'wp2print'); ?>:</label>
			<input type="text" name="tax" class="tax-price" value="<?php if ($order_data['tax']) { echo $order_data['tax']; } else { echo '0.00'; } ?>" data-rate="<?php echo $tax_rate; ?>" onblur="matrix_set_prices()">
			<input type="hidden" name="tax_rate_id" value="<?php echo $tax_rate_id; ?>">
		</p>
		<p class="form-field">
			<label><?php _e('Shipping', 'wp2print'); ?>:</label>
			<input type="text" name="shipping" class="shipping-price" value="<?php if ($order_data['shipping']) { echo $order_data['shipping']; } else { echo '0.00'; } ?>" onblur="matrix_set_shipping_tax(); matrix_set_prices();">
		</p>
		<p class="form-field">
			<label><?php _e('Shipping Tax', 'wp2print'); ?>:</label>
			<input type="text" name="shipping_tax" class="shipping-tax-price" value="<?php if ($order_data['shipping_tax']) { echo $order_data['shipping_tax']; } else { echo '0.00'; } ?>" onblur="matrix_set_prices()">
		</p>
		<p class="form-field">
			<label><?php _e('Total', 'wp2print'); ?>: <span class="req">*</span></label>
			<input type="text" name="total" class="total-price" value="<?php if ($order_data['total']) { echo $order_data['total']; } ?>">
		</p>
	</div>
	<?php
}
?>