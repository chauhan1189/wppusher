<?php
$print_products_plugin_api = get_option("print_products_plugin_api");

add_action('wp_loaded', 'print_products_sso_actions');
function print_products_sso_actions() {
	global $print_products_plugin_api;
	if ($print_products_plugin_api['enable']) {
		if (isset($_POST['method']) && isset($_POST['user'])) {
			switch ($_POST['method']) {
				case 'exists':
					print_products_sso_exists();
				break;
				case 'login':
					print_products_sso_login();
				break;
				case 'data':
					print_products_sso_update_data();
				break;
			}
		}
	}
}

function print_products_sso_exists() {
	$user = trim($_POST['user']);
	$time = trim($_POST['time']);
	$hash = trim($_POST['hash']);

	if (strlen($user) && strlen($time) && strlen($hash)) {
		if (print_products_sso_check($user, $time, $hash)) {
			if (is_email($user)) {
				$userdata = get_user_by('email', $user);
			} else {
				$userdata = get_user_by('login', $user);
			}
			if ($userdata && !$userdata->errors) {
				echo 'TRUE';
			} else {
				echo 'FALSE';
			}
			exit;
		}
	}
	wp_die('Incorrect SSO request.', 'SSO Error', array('response' => 400));
}

function print_products_sso_login() {
	$user = trim($_POST['user']);
	$time = trim($_POST['time']);
	$hash = trim($_POST['hash']);

	if (strlen($user) && strlen($time) && strlen($hash)) {
		if (print_products_sso_check($user, $time, $hash)) {
			if (is_email($user)) {
				$userdata = get_user_by('email', $user);
			} else {
				$userdata = get_user_by('login', $user);
			}
			if ($userdata) {
				$user_id = $userdata->ID;
				$user_login = $userdata->user_login;
				wp_set_current_user($user_id, $user_login);
				wp_set_auth_cookie($user_id);
				do_action('wp_login', $user_login, $userdata);
				wp_redirect(home_url('/'));
				exit;
			}
		}
	}
	wp_die('Incorrect SSO request.', 'SSO Error', array('response' => 400));
}

function print_products_sso_update_data() {
	global $wpdb;

	$user = trim($_POST['user']);
	$time = trim($_POST['time']);
	$hash = trim($_POST['hash']);
	$email = trim($_POST['email']);

	$udata = array(
		'email' => $email,
		'fname' => trim($_POST['firstName']),
		'lname' => trim($_POST['lastName']),
		'billing_first_name' => trim($_POST['billing_first_name']),
		'billing_last_name' => trim($_POST['billing_last_name']),
		'billing_company' => trim($_POST['billing_company']),
		'billing_address_1' => trim($_POST['billing_address_1']),
		'billing_address_2' => trim($_POST['billing_address_2']),
		'billing_city' => trim($_POST['billing_city']),
		'billing_postcode' => trim($_POST['billing_postcode']),
		'billing_country' => trim($_POST['billing_country']),
		'billing_state' => trim($_POST['billing_state']),
		'billing_email' => trim($_POST['billing_email']),
		'billing_phone' => trim($_POST['billing_phone']),
		'shipping_first_name' => trim($_POST['shipping_first_name']),
		'shipping_last_name' => trim($_POST['shipping_last_name']),
		'shipping_company' => trim($_POST['shipping_company']),
		'shipping_address_1' => trim($_POST['shipping_address_1']),
		'shipping_address_2' => trim($_POST['shipping_address_2']),
		'shipping_city' => trim($_POST['shipping_city']),
		'shipping_postcode' => trim($_POST['shipping_postcode']),
		'shipping_country' => trim($_POST['shipping_country']),
		'shipping_state' => trim($_POST['shipping_state']),
		'groupid' => trim($_POST['groupid'])
	);
	if (strlen($user) && strlen($time) && strlen($hash) && $user == 'admin') {
		if (print_products_sso_check($user, $time, $hash)) {
			$userdata = get_user_by('email', $email);
			if ($userdata && !$userdata->errors) {
				$udata['uid'] = $userdata->ID;
				print_products_sso_update_user($udata);
			} else {
				print_products_sso_create_user($udata);
			}
			wp_redirect(home_url('/'));
			exit;
		}
	}
	wp_die('Incorrect SSO request.', 'SSO Error', array('response' => 400));
}

function print_products_sso_check($user, $time, $hash) {
	global $print_products_plugin_api;
	$cstime = time();
	$difftime = $cstime - $time; // allow 300 sec
	$shared_secret = $print_products_plugin_api['key'];
	$check_hash = hash('sha256', $shared_secret . $user . $time);
	if ($check_hash == $hash && $difftime < 301) {
		return true;
	}
	return false;
}

function print_products_sso_create_user($udata) {
	$user_email = $udata['email'];
	$user_login = strtolower(str_replace(array('@', '.'), '', $user_email));
	$user_pass = 
	$userdata = array();
	$userdata['role'] = 'subscriber';
	$userdata['user_login'] = $user_login;
	$userdata['user_pass'] = md5($user_email);
	$userdata['user_email'] = $user_email;
	$userdata['first_name'] = $udata['fname'];
	$userdata['last_name'] = $udata['lname'];
	$userdata['nickname'] = $udata['fname'].' '.$udata['lname'];
	$userdata['display_name'] = $udata['fname'].' '.$udata['lname'];
	$user_id = wp_insert_user($userdata);
	print_products_sso_update_user_fields($user_id, $udata);
}

function print_products_sso_update_user($udata) {
	$user_id = $udata['uid'];
	$userdata = array();
	$userdata['ID'] = $user_id;
	$userdata['first_name'] = $udata['fname'];
	$userdata['last_name'] = $udata['lname'];
	$userdata['nickname'] = $udata['fname'].' '.$udata['lname'];
	$userdata['display_name'] = $udata['fname'].' '.$udata['lname'];
	wp_update_user($userdata);
	print_products_sso_update_user_fields($user_id, $udata);
}

function print_products_sso_update_user_fields($user_id, $udata) {
	$billing_fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'email', 'phone');
	foreach($billing_fields as $bfield) {
		update_user_meta($user_id, 'billing_'.$bfield, $udata['billing_'.$bfield]);
	}

	$shipping_fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state');
	foreach($shipping_fields as $sfield) {
		update_user_meta($user_id, 'shipping_'.$sfield, $udata['shipping_'.$sfield]);
	}
	if ($udata['groupid']) {
		update_user_meta($user_id, '_user_group', $udata['groupid']);
	}
}
?>