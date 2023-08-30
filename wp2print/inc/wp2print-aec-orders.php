<?php
add_action('admin_menu', 'print_products_aec_orders_admin_menu', 100);
function print_products_aec_orders_admin_menu() {
	add_menu_page(__('RapidQuotes', 'wp2print'), __('RapidQuotes', 'wp2print'), 'rapid_quotes', 'aec-quotes', 'print_products_aec_orders_admin_quotes_page');
	add_submenu_page('aec-quotes', __('New RapidQuote', 'wp2print'), __('New RapidQuote', 'wp2print'), 'rapid_quotes', 'aec-quotes-add', 'print_products_aec_orders_admin_add_quote_page');
}

function print_products_aec_orders_admin_quotes_page() {
	global $wpdb, $print_products_settings;
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-aec-quotes.php';
}

function print_products_aec_orders_admin_add_quote_page() {
	global $print_products_settings, $print_products_plugin_aec, $wpdb;
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'admin-aec-quotes-add.php';
}

add_action('wp_loaded', 'print_products_aec_orders_actions');
function print_products_aec_orders_actions() {
	if (isset($_POST['print_products_aec_orders_action'])) {
		switch ($_POST['print_products_aec_orders_action']) {
			case 'create':
				print_products_aec_orders_create_order();
			break;
		}
	}
	if (isset($_GET['aecorder']) && $_GET['aecorder'] == 'true') {
		print_products_aec_orders_order_process();
	}
}

function print_products_aec_orders_create_order() {
	global $wpdb, $print_products_plugin_aec;

	$ao_user = $_POST['ao_user'];
	$ao_product = $_POST['ao_product'];
	$ao_qty = (int)$_POST['ao_qty'];
	$ao_material = $_POST['ao_material'][$ao_product];
	$ao_project_name = trim($_POST['ao_project_name']);
	$ao_email_subject = trim($_POST['ao_email_subject']);
	$ao_email_message = trim($_POST['ao_email_message']);
	$ao_smparams = $_POST['ao_smparams'];
	$ao_artworkfiles = $_POST['ao_artworkfiles'];
	$ao_total_price = $_POST['ao_total_price'];
	$ao_total_area = $_POST['ao_total_area'];
	$ao_total_pages = $_POST['ao_total_pages'];
	$ao_area_bw = $_POST['ao_area_bw'];
	$ao_pages_bw = $_POST['ao_pages_bw'];
	$ao_area_cl = $_POST['ao_area_cl'];
	$ao_pages_cl = $_POST['ao_pages_cl'];
	$ao_table_values = $_POST['ao_table_values'];

	$ao_product_type = print_products_get_type($ao_product);

	$user_data = get_userdata($ao_user);
	if ($user_data) {
		// create order record
		$insert = array();
		$insert['user_id'] = $ao_user;
		$insert['product_id'] = $ao_product;
		$insert['qty'] = $ao_qty;
		$insert['term_id'] = $ao_material;
		$insert['project_name'] = $ao_project_name;
		$insert['smparams'] = $ao_smparams;
		$insert['artworkfiles'] = $ao_artworkfiles;
		$insert['total_price'] = $ao_total_price;
		$insert['total_area'] = $ao_total_area;
		$insert['total_pages'] = $ao_total_pages;
		$insert['area_bw'] = $ao_area_bw;
		$insert['pages_bw'] = $ao_pages_bw;
		$insert['area_cl'] = $ao_area_cl;
		$insert['pages_cl'] = $ao_pages_cl;
		$insert['table_values'] = $ao_table_values;
		$insert['status'] = 0;
		$insert['created'] = current_time('mysql');
		$wpdb->insert($wpdb->prefix."print_products_aec_orders", $insert);
		$order_id = $wpdb->insert_id;

		$pay_now_text = __('Pay now', 'wp2print');
		if (strlen($print_products_plugin_aec['pay_now_text'])) {
			$pay_now_text = $print_products_plugin_aec['pay_now_text'];
		}

		$pay_now_link = '<a href="'.site_url('?aecorder=true&uid='.md5($ao_user).'&oid='.md5($order_id)).'" style="background:#0085ba; border:1px solid #006799; border-color:#0073aa #006799 #006799; border-radius:3px; color:#fff; font-size:13px; line-height:26px; height:26px; text-decoration:none;font-family:Arial; display:inline-block; padding:0 10px 1px;">'.$pay_now_text.'</a>';

		if ($ao_product_type == 'aecbwc') {
			$page_detail_matrix = print_products_aec_orders_page_detail_matrix_aecbwc($ao_table_values);
		} else if ($ao_product_type == 'aecsimple') {
			$page_detail_matrix = print_products_aec_orders_page_detail_matrix_aecsimple($ao_table_values);
		} else {
			$page_detail_matrix = print_products_aec_orders_page_detail_matrix_aec($ao_table_values);
		}

		$ao_email_subject = str_replace('{PROJECT-NAME}', $ao_project_name, $ao_email_subject);

		$ao_email_message = nl2br($ao_email_message);
		$ao_email_message = str_replace('{PAY-NOW-LINK}', $pay_now_link, $ao_email_message);
		$ao_email_message = str_replace('{PAGE-DETAIL-MATRIX}', $page_detail_matrix, $ao_email_message);
		$ao_email_message = str_replace('{TOTAL-PRICE}', print_products_display_price($ao_total_price), $ao_email_message);
		$ao_email_message = str_replace('{PROJECT-NAME}', $ao_project_name, $ao_email_message);

		$headers = 'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>' . "\r\n";
		add_filter('wp_mail_content_type', function(){ return "text/html"; });
		wp_mail($user_data->user_email, $ao_email_subject, $ao_email_message, $headers);
		$_SESSION['aec_order_message'] = __('RapidQuote was created and customer email was successfully sent.', 'wp2print');
		wp_redirect('admin.php?page=aec-quotes');
		exit;
	}
}

function print_products_aec_orders_order_process() {
	global $wpdb;

	$uid = $_GET['uid'];
	$oid = $_GET['oid'];

	if (strlen($uid) && strlen($oid)) {
		$user_data = $wpdb->get_row(sprintf("SELECT * FROM %susers WHERE MD5(ID) = '%s'", $wpdb->prefix, $uid));
		$order_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_aec_orders WHERE MD5(order_id) = '%s'", $wpdb->prefix, $oid));
		if ($user_data && $order_data) {
			// change aec order status
			$update = array();
			$update['status'] = 1;
			$wpdb->update($wpdb->prefix.'print_products_aec_orders', array('status' => 1), array('order_id' => $order_data->order_id));

			// login user
			$user_id = $user_data->ID;
			$user_login = $user_data->user_login;
			wp_set_current_user($user_id, $user_login);
			wp_set_auth_cookie($user_id);
			do_action('wp_login', $user_login, $user);

			// add product to cart
			$product_id = $order_data->product_id;
			$quantity = $order_data->qty;
			$product_type_matrix_type = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE mtype = 0 AND product_id = %s ORDER BY mtype, sorder LIMIT 0, 1", $wpdb->prefix, $product_id));
			if ($product_type_matrix_type) {
				$mtype_id = $product_type_matrix_type->mtype_id;
			}
			$smparams = $mtype_id.'|'.$order_data->smparams.'|'.$quantity;

			$_REQUEST['print_products_checkout_process_action'] = 'add-to-cart';
			$_REQUEST['product_type'] = print_products_get_type($product_id);
			$_REQUEST['product_id'] = $product_id;
			$_REQUEST['add-to-cart'] = $product_id;
			$_REQUEST['quantity'] = $quantity;
			$_REQUEST['smparams'] = $smparams;
			$_REQUEST['atcaction'] = 'artwork';
			$_REQUEST['artworkfiles'] = $order_data->artworkfiles;
			$_REQUEST['aec_project_name'] = $order_data->project_name;
			$_REQUEST['aec_total_price'] = $order_data->total_price;
			$_REQUEST['aec_total_area'] = $order_data->total_area;
			$_REQUEST['aec_total_pages'] = $order_data->total_pages;
			$_REQUEST['aec_area_bw'] = $order_data->area_bw;
			$_REQUEST['aec_pages_bw'] = $order_data->pages_bw;
			$_REQUEST['aec_area_cl'] = $order_data->area_cl;
			$_REQUEST['aec_pages_cl'] = $order_data->pages_cl;
			$_REQUEST['aec_table_values'] = $order_data->table_values;

			WC()->cart->empty_cart();

			$cart_item_data = array();
			$cart_item_data['unique_key'] = md5(microtime() . rand() . md5($product_id));
			WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);

			// redirect to checkout page
			$checkout_url = WC()->cart->get_checkout_url();
			wp_redirect($checkout_url);
			exit;
		}
	}
}

function print_products_aec_orders_page_detail_matrix_aec($table_values) {
	$dimension_unit = print_products_get_aec_dimension_unit();
	$aec_sizes = print_products_get_aec_sizes();
	if (strlen($table_values)) {
		$table_lines = explode('|', $table_values);
		$page_detail_matrix = '<table border="1" style="width:100%;font-size:13px;border-collapse:collapse;">';
		$page_detail_matrix .= '<thead><tr>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('File Name', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:center;padding:4px;">'.__('Page', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:center;padding:4px;">'.__('% Coverage', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('Print size', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Printed Area', 'wp2print').' ('.$dimension_unit.'<sup>2</sup>)</th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Price', 'wp2print').'/'.$dimension_unit.'<sup>2</sup></th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Price', 'wp2print').'</th>';
		$page_detail_matrix .= '</tr></thead><tbody>';
		foreach($table_lines as $table_line) {
			$lvalues = explode(';', $table_line);
			$page_detail_matrix .= '<tr>';
			$page_detail_matrix .= '<td style="text-align:left;padding:4px;">'.$lvalues[0].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[1].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[2].'</td>';
			$page_detail_matrix .= '<td style="text-align:left;padding:4px;" nowrap>'.$aec_sizes[$lvalues[3]].'</td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.$lvalues[4].' '.$dimension_unit.'<sup>2</sup></td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.print_products_display_price($lvalues[5]).'</td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.print_products_display_price($lvalues[6]).'</td>';
			$page_detail_matrix .= '</tr>';
		}
		$page_detail_matrix .= '</tbody></table>';
	}
	return $page_detail_matrix;
}

function print_products_aec_orders_page_detail_matrix_aecbwc($table_values) {
	$dimension_unit = print_products_get_aec_dimension_unit();
	$aec_sizes = print_products_get_aec_sizes();
	if (strlen($table_values)) {
		$table_lines = explode('|', $table_values);
		$page_detail_matrix = '<table border="1" style="width:100%;font-size:13px;border-collapse:collapse;">';
		$page_detail_matrix .= '<thead><tr>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('File Name', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:center;padding:4px;">'.__('Page', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:center;padding:4px;">'.__('Original color', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('Original size', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('Convert size', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Print size', 'wp2print').' ('.$dimension_unit.'<sup>2</sup>)</th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Price', 'wp2print').'/'.$dimension_unit.'<sup>2</sup></th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Price', 'wp2print').'</th>';
		$page_detail_matrix .= '</tr></thead><tbody>';
		foreach($table_lines as $table_line) {
			$lvalues = explode(';', $table_line);
			$page_detail_matrix .= '<tr>';
			$page_detail_matrix .= '<td style="text-align:left;padding:4px;">'.$lvalues[0].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[1].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[2].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[3].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[4].'</td>';
			$page_detail_matrix .= '<td style="text-align:left;padding:4px;">'.$aec_sizes[$lvalues[5]].'</td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.print_products_display_price($lvalues[6]).'</td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.print_products_display_price($lvalues[7]).'</td>';
			$page_detail_matrix .= '</tr>';
		}
		$page_detail_matrix .= '</tbody></table>';
	}
	return $page_detail_matrix;
}

function print_products_aec_orders_page_detail_matrix_aecsimple($table_values) {
	$dimension_unit = print_products_get_aec_dimension_unit();
	$aec_sizes = print_products_get_aec_sizes();
	if (strlen($table_values)) {
		$table_lines = explode('|', $table_values);
		$page_detail_matrix = '<table border="1" style="width:100%;font-size:13px;border-collapse:collapse;">';
		$page_detail_matrix .= '<thead><tr>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('File Name', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:center;padding:4px;">'.__('Page', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:left;padding:4px;">'.__('Print size', 'wp2print').'</th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Printed Area', 'wp2print').' ('.$dimension_unit.'<sup>2</sup>)</th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Price', 'wp2print').'/'.$dimension_unit.'<sup>2</sup></th>';
		$page_detail_matrix .= '<th style="text-align:right;padding:4px;">'.__('Price', 'wp2print').'</th>';
		$page_detail_matrix .= '</tr></thead><tbody>';
		foreach($table_lines as $table_line) {
			$lvalues = explode(';', $table_line);
			$page_detail_matrix .= '<tr>';
			$page_detail_matrix .= '<td style="text-align:left;padding:4px;">'.$lvalues[0].'</td>';
			$page_detail_matrix .= '<td style="text-align:center;padding:4px;">'.$lvalues[1].'</td>';
			$page_detail_matrix .= '<td style="text-align:left;padding:4px;" nowrap>'.$aec_sizes[$lvalues[2]].'</td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.$lvalues[3].' '.$dimension_unit.'<sup>2</sup></td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.print_products_display_price($lvalues[4]).'</td>';
			$page_detail_matrix .= '<td style="text-align:right;padding:4px;">'.print_products_display_price($lvalues[5]).'</td>';
			$page_detail_matrix .= '</tr>';
		}
		$page_detail_matrix .= '</tbody></table>';
	}
	return $page_detail_matrix;
}
?>