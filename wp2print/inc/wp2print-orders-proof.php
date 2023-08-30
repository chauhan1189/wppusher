<?php
// Send proof functionality
add_action('wp_loaded', 'print_products_orders_proof_actions');
function print_products_orders_proof_actions() {
	global $wpdb, $current_user, $current_user_group;
	if (isset($_POST['orders_proof_action'])) {
		switch ($_POST['orders_proof_action']) {
			case 'send':
				$order_id = $_POST['order_id'];
				$item_id = $_POST['item_id'];
				$proof_files = $_POST['proof_files'];
				$email_subject = trim($_POST['email_subject']);
				$email_message = trim($_POST['email_message']);

				$myaccount_page_id = get_option('woocommerce_myaccount_page_id');
				if ($order_id && $item_id) {
					$order = new WC_Order($order_id);
					$user_email = $order->billing_email;

					wc_update_order_item_meta($item_id, '_approval_status', 'awaiting');
					wc_update_order_item_meta($item_id, '_approval_type', '1');
					wc_update_order_item_meta($item_id, '_proof_files', $proof_files);

					// send email to order user
					$oaa_link = get_permalink($myaccount_page_id).'orders-awaiting-approval/?view='.$order_id;
					$email_message = str_replace('[ORDERS_AWAITING_APPROVAL_LINK]', $oaa_link, $email_message);
					$headers = 'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>' . "\r\n";
					wp_mail($user_email, $email_subject, $email_message, $headers);
				}
				wp_redirect('post.php?post='.$order_id.'&action=edit&proofsent=true');
				exit;
			break;
			case 'superuser-submit':
				$order_id = $_POST['order_id'];
				$aa_action = $_POST['aa_action'];
				$order_comments = trim($_POST['order_comments']);
				$order = wc_get_order($order_id);
				$redirectto = $_POST['redirectto'];
				if ($aa_action == 'approve') {
					foreach ($order->get_items() as $item_id => $item) {
						wc_update_order_item_meta($item_id, '_approval_status', 'approved');
						wc_update_order_item_meta($item_id, '_approval_approved', current_time('mysql'));
						$awaiting_approval_message = __('Order was successfully approved.', 'wp2print');
					}
					if (strlen($order_comments)) {
						$data = array(
							'comment_post_ID' => $order_id,
							'comment_author' => $current_user->display_name,
							'comment_author_email' => $current_user->user_email,
							'comment_content' => $order_comments,
							'comment_type' => 'order_note',
							'comment_parent' => 0,
							'user_id' => $current_user->ID,
							'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
							'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
							'comment_date' => current_time('mysql'),
							'comment_approved' => 1,
						);
						$comment_id = wp_insert_comment($data);
						add_comment_meta($comment_id, 'is_customer_note', 1);
					}
				} else {
					foreach ($order->get_items() as $item_id => $item) {
						wc_update_order_item_meta($item_id, '_approval_status', 'rejected');
						wc_update_order_item_meta($item_id, '_approval_rejected', current_time('mysql'));
						$awaiting_approval_message = __('Order was successfully rejected.', 'wp2print');
					}
					if (!strlen($order_comments)) { $order_comments = $current_user->display_name.' is cancelled order.'; }
					$order->update_status('cancelled', $order_comments);
				}

				print_products_orders_proof_send_admin_notification($aa_action, $order_id, $item_id);

				$_SESSION['awaiting_approval_message'] = $awaiting_approval_message;
				if (!print_products_orders_proof_show_menu_item()) {
					unset($_SESSION['awaiting_approval_message']);
					$redirectto = str_replace('orders-awaiting-approval/', '', $redirectto);
				}
				wp_redirect($redirectto);
				exit;
			break;
		}
	}
	if (isset($_POST['awaiting_approval_submit']) && $_POST['awaiting_approval_submit'] == 'true') {
		$admin_email = get_option('admin_email');
		$order_id = $_POST['order_id'];
		$item_id = $_POST['item_id'];
		$awaiting_approval_action = $_POST['awaiting_approval_action'];
		$order_comments = trim($_POST['order_comments']);
		$redirectto = $_POST['redirectto'];

		$the_order = wc_get_order($order_id);
		$user_info = get_userdata($the_order->user_id);

		if ($awaiting_approval_action == 'approve') {
			wc_update_order_item_meta($item_id, '_approval_status', 'approved');
			wc_update_order_item_meta($item_id, '_approval_approved', current_time('mysql'));
			$awaiting_approval_message = __('Order item was successfully approved.', 'wp2print');
		} else {
			wc_update_order_item_meta($item_id, '_approval_status', 'rejected');
			wc_update_order_item_meta($item_id, '_approval_rejected', current_time('mysql'));
			$awaiting_approval_message = __('Order item was successfully rejected.', 'wp2print');

			// send email to customer
			$is_superuser = get_user_meta($current_user->ID, '_superuser_group', true);
			if ($is_superuser) {
				$orders_email_contents = unserialize($current_user_group->orders_email_contents);
				if (!strlen($orders_email_contents['email_subject_order_rejection'])) {
					$orders_email_contents['email_subject_order_rejection'] = 'There is a problem with your order';
				}
				if (!strlen($orders_email_contents['email_message_order_rejection'])) {
					$orders_email_contents['email_message_order_rejection'] = 'We are not able to proceed with your order [ORDERID]. Your order was not approved for production for the following reason:'.chr(10).chr(10).'[COMMENTS]'.chr(10).chr(10).'Please return to the website and place a new order.';
				}

				$subject = $orders_email_contents['email_subject_order_rejection'];
				$message = $orders_email_contents['email_message_order_rejection'];
				$message = str_replace('[ORDERID]', $order_id, $message);
				$message = str_replace('[COMMENTS]', $order_comments, $message);
				$headers = 'From: '.get_bloginfo('name').' <'.$admin_email.'>' . "\r\n";

				wp_mail($user_info->user_email, $subject, $message, $headers);
			}
		}

		print_products_orders_proof_send_admin_notification($awaiting_approval_action, $order_id, $item_id);

		if (strlen($order_comments)) {
			$data = array(
				'comment_post_ID' => $order_id,
				'comment_author' => $current_user->display_name,
				'comment_author_email' => $current_user->user_email,
				'comment_content' => $order_comments,
				'comment_type' => 'order_note',
				'comment_parent' => 0,
				'user_id' => $current_user->ID,
				'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
				'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
				'comment_date' => current_time('mysql'),
				'comment_approved' => 1,
			);
			$comment_id = wp_insert_comment($data);
			add_comment_meta($comment_id, 'is_customer_note', 1);
		}

		$_SESSION['awaiting_approval_message'] = $awaiting_approval_message;
		if (!print_products_orders_proof_show_menu_item()) {
			unset($_SESSION['awaiting_approval_message']);
			$redirectto = str_replace('orders-awaiting-approval/', '', $redirectto);
		}
		wp_redirect($redirectto);
		exit;
	}
}

function print_products_orders_proof_send_admin_notification($action, $order_id, $item_id) {
	$admin_email = get_option('admin_email');
	$print_products_email_options = get_option('print_products_email_options');
	if ($print_products_email_options['proof_admin_send'] == 1) {
		if ($action == 'approve') {
			$subject = $print_products_email_options['proof_admin_subject_approvals'];
			$message = $print_products_email_options['proof_admin_message_approvals'];
		} else {
			$subject = $print_products_email_options['proof_admin_subject_rejections'];
			$message = $print_products_email_options['proof_admin_message_rejections'];
		}
		if (strlen($subject) && strlen($message)) {
			$subject = str_replace('[ORDER_ID]', $order_id, $subject);
			$message = str_replace('[ORDER_ID]', $order_id, $message);
			$message = str_replace('[ORDER_ITEM_ID]', $item_id, $message);
			$headers = 'From: '.get_bloginfo('name').' <'.$admin_email.'>' . "\r\n";
			wp_mail($admin_email, $subject, $message, $headers);
		}
	}
}

add_action('admin_notices', 'print_products_orders_proof_admin_notices');
function print_products_orders_proof_admin_notices() {
	if (isset($_GET['proofsent']) && $_GET['proofsent'] == 'true') { ?>
		<div id="message" class="updated notice notice-success">
			<p><?php _e('Approval order email was successfully sent.', 'wp2print'); ?></p>
		</div>
		<?php
	}
}

function print_products_orders_proof_get_approval_statuses() {
	$statuses = array(
		'awaiting' => __('Awaiting approval', 'wp2print'),
		'approved' => __('Approved for production', 'wp2print'),
		'rejected' => __('Rejected for production', 'wp2print')
	);
	return $statuses;
}

function print_products_orders_proof_show_menu_item() {
	global $wpdb, $current_user;
	$is_superuser = get_user_meta($current_user->ID, '_superuser_group', true);
	if ($is_superuser) {
		return true;
	} else {
		$aa_orders = print_products_orders_proof_get_awaiting_orders(false);
		if ($aa_orders) {
			return true;
		}
	}
	return false;
}

add_filter('woocommerce_account_menu_items', 'print_products_orders_proof_account_menu_items', 11);
function print_products_orders_proof_account_menu_items($items) {
	if (print_products_orders_proof_show_menu_item()) {
		$new_items = array();
		foreach($items as $ikey => $ival) {
			$new_items[$ikey] = $ival;
			if ($ikey == 'orders') {
				$new_items['orders-awaiting-approval'] = __('Orders awaiting approval', 'wp2print');
			}
		}
		return $new_items;
	}
	return $items;
}

add_action('init', 'print_products_orders_proof_rewrite_endpoint');
function print_products_orders_proof_rewrite_endpoint() {
	if (print_products_my_account_is_front()) {
		add_rewrite_endpoint('orders-awaiting-approval', EP_ROOT | EP_PAGES);
	} else {
		add_rewrite_endpoint('orders-awaiting-approval', EP_PAGES);
	}
	flush_rewrite_rules();
}

add_filter('query_vars', 'print_products_orders_proof_query_vars', 10);
function print_products_orders_proof_query_vars($vars) {
	$vars[] = 'orders-awaiting-approval';
	return $vars;
}

add_action('parse_request', 'print_products_orders_proof_parse_request', 10);
function print_products_orders_proof_parse_request() {
	global $wp;
	$var = 'orders-awaiting-approval';
	if (isset($wp->query_vars['name']) && $wp->query_vars['name'] == $var) {
		unset($wp->query_vars['name']);
		$wp->query_vars[$var] = $var;
	}
}

add_action('pre_get_posts', 'print_products_orders_proof_pre_get_posts');
function print_products_orders_proof_pre_get_posts($q) {
	if ( ! $q->is_main_query() ) {
		return;
	}
	if (print_products_is_showing_page_on_front($q) && ! print_products_page_on_front_is($q->get( 'page_id'))) {
		$_query = wp_parse_args($q->query);
		$qv_array = array('orders-awaiting-approval' => 'orders-awaiting-approval');
		if (!empty($_query) && array_intersect( array_keys($_query), array_keys($qv_array))) {
			$q->is_page     = true;
			$q->is_home     = false;
			$q->is_singular = true;
			$q->set('page_id', (int)get_option( 'page_on_front'));
			add_filter('redirect_canonical', '__return_false');
		}
	}
}

add_action('woocommerce_account_orders-awaiting-approval_endpoint', 'print_products_account_orders_awaiting_approval');
function print_products_account_orders_awaiting_approval() {
	include PRINT_PRODUCTS_TEMPLATES_DIR . 'orders-awaiting-approval.php';
}

function print_products_orders_proof_get_awaiting_orders($is_superuser, $group_users = false) {
	global $wpdb, $current_user;
	$awaiting_orders = false;
	if ($is_superuser) {
		if ($group_users) {
			$awaiting_orders = $wpdb->get_results(sprintf("SELECT p.*, pm.meta_value as user_id FROM %sposts p LEFT JOIN %spostmeta pm ON pm.post_id = p.ID WHERE p.post_type = 'shop_order' AND p.post_status != 'trash' AND pm.meta_key = '_customer_user' AND pm.meta_value IN ('%s') AND p.ID IN (SELECT oi.order_id FROM %swoocommerce_order_items oi LEFT JOIN %swoocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id WHERE oim.meta_key = '_approval_status' AND oim.meta_value = 'awaiting') ORDER BY p.ID DESC", $wpdb->prefix, $wpdb->prefix, implode("','", $group_users), $wpdb->prefix, $wpdb->prefix));
		}
	} else {
		$awaiting_orders = $wpdb->get_results(sprintf("SELECT p.*, pm.meta_value as user_id FROM %sposts p LEFT JOIN %spostmeta pm ON pm.post_id = p.ID WHERE p.post_type = 'shop_order' AND p.post_status != 'trash' AND pm.meta_key = '_customer_user' AND pm.meta_value = '%s' AND p.ID IN (SELECT oi.order_id FROM %swoocommerce_order_items oi LEFT JOIN %swoocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id LEFT JOIN %swoocommerce_order_itemmeta oim2 ON oim2.order_item_id = oi.order_item_id WHERE oim.meta_key = '_approval_status' AND oim.meta_value = 'awaiting' AND oim2.meta_key = '_approval_type') ORDER BY p.ID DESC", $wpdb->prefix, $wpdb->prefix, $current_user->ID, $wpdb->prefix, $wpdb->prefix, $wpdb->prefix));
		
	}
	
	return $awaiting_orders;
}

// admin orders list
add_filter('manage_shop_order_posts_columns', 'print_products_orders_proof_shop_order_posts_columns', 11);
function print_products_orders_proof_shop_order_posts_columns($columns) {
	$new_columns = array();
	foreach($columns as $column_key => $column_val) {
		$new_columns[$column_key] = $column_val;
		if ($column_key == 'order_status') {
			$new_columns['approval'] = '<span class="icon-approval"></span>';
		}
	}
    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'print_products_orders_proof_shop_order_posts_custom_column');
function print_products_orders_proof_shop_order_posts_custom_column($name) {
    global $post, $wpdb;
	$approval_statuses = print_products_orders_proof_get_approval_statuses();
	switch ($name) {
		case 'approval':
			$order = wc_get_order($post->ID);
			foreach ($order->get_items() as $item_id => $item) {
				$ashtml = '&nbsp;';
				$approval_status = wc_get_order_item_meta($item_id, '_approval_status', true);
				if (strlen($approval_status)) {
					$title = $approval_statuses[$approval_status];
					if ($approval_status == 'approved') {
						$approval_approved = wc_get_order_item_meta($item_id, '_approval_approved', true);
						$title .= chr(10) . __('Approved on', 'wp2print').' '.$approval_approved;
					} else if ($approval_status == 'rejected') {
						$approval_rejected = wc_get_order_item_meta($item_id, '_approval_rejected', true);
						$title .= chr(10) . __('Rejected on', 'wp2print').' '.$approval_rejected;
					}
					$ashtml = '<mark class="'.$approval_status.'" title="'.$title.'"></mark>';
				}
				echo '<div class="oil-item oil-item-'.$item_id.'"><span class="mrk">'.$ashtml.'</span></div>';
			}
		break;
	}
}

add_filter('the_title', 'print_products_orders_proof_the_title', 12, 2);
function print_products_orders_proof_the_title($title, $id) {
	global $wp_query;
	if (is_account_page() && is_main_query() && in_the_loop() && isset($wp_query->query_vars['orders-awaiting-approval']) && !is_admin()) {
		$title = __('Orders awaiting approval', 'wp2print');
	}
	return $title;
}
?>