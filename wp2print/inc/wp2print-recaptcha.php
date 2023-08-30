<?php
$wp2print_recaptcha_options = array();
add_action('wp_loaded', 'print_products_recaptcha_init', 9);
function print_products_recaptcha_init() {
	global $pagenow, $wp2print_recaptcha_options;
	$wp2print_recaptcha_options = get_option('print_products_recaptcha_options');
	if ($pagenow == 'wp-login.php' && print_products_recaptcha_is_active()) {
		print_products_recaptcha_wp_enqueue_scripts();
		wp_register_script('wp2print', PRINT_PRODUCTS_PLUGIN_URL . 'js/wp2print.js', array('jquery'), '1.0.1', true);
		wp_enqueue_script('wp2print');
	}
}

function print_products_recaptcha_is_active() {
	global $wp2print_recaptcha_options;
	if ($wp2print_recaptcha_options && $wp2print_recaptcha_options['use'] && strlen($wp2print_recaptcha_options['site_key']) && strlen($wp2print_recaptcha_options['secret_key'])) {
		return true;
	}
	return false;
}

function print_products_recaptcha_is_v2() {
	global $wp2print_recaptcha_options;
	if ($wp2print_recaptcha_options && ($wp2print_recaptcha_options['version'] == 'V2' || $wp2print_recaptcha_options['version'] == '')) {
		return true;
	}
	return false;
}

function print_products_recaptcha_is_v3() {
	global $wp2print_recaptcha_options;
	if ($wp2print_recaptcha_options && $wp2print_recaptcha_options['version'] == 'V3') {
		return true;
	}
	return false;
}

// V3 version
add_action('wp_footer', 'print_products_recaptcha_wp_footer');
function print_products_recaptcha_wp_footer() {
	global $wp2print_recaptcha_options;
	if (print_products_recaptcha_is_active() && print_products_recaptcha_is_v3()) { ?>
		<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $wp2print_recaptcha_options['site_key']; ?>"></script>
		<script>
		grecaptcha.ready(function() {
			grecaptcha.execute('<?php echo $wp2print_recaptcha_options['site_key']; ?>', {action: 'login'}).then(function(token) {});
		});
		</script>
		<?php
	}
}

add_action('wp_enqueue_scripts', 'print_products_recaptcha_wp_enqueue_scripts');
function print_products_recaptcha_wp_enqueue_scripts() {
	if (print_products_recaptcha_is_active() && print_products_recaptcha_is_v2()) {
		wp_register_script('wp2print-google-recaptcha', 'https://www.google.com/recaptcha/api.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('wp2print-google-recaptcha');
	}
}

add_action('login_footer', 'print_products_recaptcha_login_footer');
function print_products_recaptcha_login_footer() {
	if (print_products_recaptcha_is_active()) {
		if (print_products_recaptcha_is_v2()) { ?>
			<style>.g-recaptcha{transform:scale(0.90); transform-origin:0 0; margin-bottom:10px;}</style>
			<?php
		} else if (print_products_recaptcha_is_v3()) {
			print_products_recaptcha_wp_footer();
		}
	}
}

add_action('login_form', 'print_products_recaptcha_wp_auth_form');
add_action('register_form', 'print_products_recaptcha_wp_auth_form');
function print_products_recaptcha_wp_auth_form() {
	if (print_products_recaptcha_is_active() && print_products_recaptcha_is_v2()) {
		wp_enqueue_script('wp2print-google-recaptcha');
		print_products_recaptcha_field();
	}
}

add_action('woocommerce_login_form', 'print_products_recaptcha_woocommerce_auth_form');
add_action('woocommerce_register_form', 'print_products_recaptcha_woocommerce_auth_form');
add_action('wp2print_simple_submit_form', 'print_products_recaptcha_woocommerce_auth_form');
function print_products_recaptcha_woocommerce_auth_form() {
	if (print_products_recaptcha_is_active() && print_products_recaptcha_is_v2()) {
		print_products_recaptcha_field();
	}
}

function print_products_recaptcha_field() {
	global $wp2print_recaptcha_options; ?>
	<div class="g-recaptcha" data-callback="wp2print_recaptcha" data-sitekey="<?php echo $wp2print_recaptcha_options['site_key']; ?>"></div>
	<?php
}

add_filter('wp_authenticate_user', 'print_products_recaptcha_wp_authenticate_user', 10, 2);
function print_products_recaptcha_wp_authenticate_user($user, $password) {
	if (print_products_recaptcha_is_active() && print_products_recaptcha_is_v2()) {
		if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
			return new WP_Error('invalid_captacha', __('Invalid Captcha.', 'wp2print'));
		}
	}
	return $user;
}

add_filter('wp2print_simple_submit_valid', 'print_products_recaptcha_wp2print_simple_submit_valid');
function print_products_recaptcha_wp2print_simple_submit_valid($valid) {
	if (print_products_recaptcha_is_active() && print_products_recaptcha_is_v2()) {
		if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
			$valid = false;
		}
	}
	return $valid;
}
?>