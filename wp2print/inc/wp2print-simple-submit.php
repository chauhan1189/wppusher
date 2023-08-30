<?php
add_action('wp_loaded', 'print_products_info_form_init');
function print_products_info_form_init() {
	if (isset($_POST['wp2printinfoform']) && $_POST['wp2printinfoform'] == 'submit') {
		$countries = print_products_info_form_get_countries();
		$print_products_info_form_options = get_option("print_products_info_form_options");
		$project_name = trim($_POST['project_name']);
		$first_name = trim($_POST['first_name']);
		$last_name = trim($_POST['last_name']);
		$email = trim($_POST['email']);
		$phone = trim($_POST['phone']);
		$address = trim($_POST['address']);
		$address2 = trim($_POST['address2']);
		$city = trim($_POST['city']);
		$country = trim($_POST['country']);
		$state = trim($_POST['state']);
		$state_text = trim($_POST['state_text']);
		$zip = trim($_POST['zip']);
		$comments = trim($_POST['comments']);
		$uploaded_files = trim($_POST['uploaded_files']);
		$redirect_page = $_POST['redirect_page'];

		if (!strlen($state)) { $state = $state_text; }
		if (!strlen($state)) { $state = $state_text; }

		$ssform_valid = apply_filters('wp2print_simple_submit_valid', true);

		if ($ssform_valid) {
			$headers = '';
			if (strlen($print_products_info_form_options['from_name']) && strlen($print_products_info_form_options['from_email'])) {
				$headers = "From: ".$print_products_info_form_options['from_name']." <".$print_products_info_form_options['from_email'].">" . "\r\n";
			}

			// send email to user
			$subject = $print_products_info_form_options['customer_email_subject'];
			$message = $print_products_info_form_options['customer_email_content'];
			$customer_email_heading = $print_products_info_form_options['customer_email_heading'];
			print_products_info_form_send($email, $subject, $customer_email_heading, $message, $headers);

			// send email to admin
			$admin_email = get_option('admin_email');
			$subject = $print_products_info_form_options['admin_email_subject'];

			$message  = '<table cellspacing="0" cellpadding="6" style="width: 100%; color: #737373; border: 1px solid #e4e4e4;" border="1">';
			$message .= '<tr><td><strong>Project Name: </strong></td><td>'.$project_name.'</td></tr>';
			$message .= '<tr><td><strong>First Name: </strong></td><td>'.$first_name.'</td></tr>';
			$message .= '<tr><td><strong>Last Name: </strong></td><td>'.$last_name.'</td></tr>';
			$message .= '<tr><td><strong>Email: </strong></td><td>'.$email.'</td></tr>';
			$message .= '<tr><td><strong>Telephone: </strong></td><td>'.$phone.'</td></tr>';
			$message .= '<tr><td><strong>Address line 1: </strong></td><td>'.$address.'</td></tr>';
			if (strlen($address2)) {
				$message .= '<tr><td><strong>Address line 2: </strong></td><td>'.$address2.'</td></tr>';
			}
			$message .= '<tr><td><strong>City: </strong></td><td>'.$city.'</td></tr>';
			$message .= '<tr><td><strong>Country: </strong></td><td>'.$countries[$country].'</td></tr>';
			$message .= '<tr><td><strong>'.$print_products_info_form_options['state_field_label'].': </strong></td><td>'.$state.'</td></tr>';
			$message .= '<tr><td><strong>'.$print_products_info_form_options['zip_field_label'].': </strong></td><td>'.$zip.'</td></tr>';
			if (strlen($uploaded_files)) {
				$message .= '<tr><td colspan="2"><strong>Uploaded Files:</strong></td></tr>';
				$message .= '<tr><td colspan="2">';
				$uploaded_files = explode(';', $uploaded_files);
				foreach($uploaded_files as $uploaded_file) {
					$message .= '<a href="'.print_products_get_amazon_file_url($uploaded_file).'">'.basename($uploaded_file).'</a><br>';
				}
				$message .= '</td></tr>';
			}
			if (strlen($comments)) {
				$message .= '<tr><td colspan="2"><strong>Comments:</strong></td></tr>';
				$message .= '<tr><td colspan="2">'.$comments.'</td></tr>';
			}
			$message .= '</table>';

			$admin_email_heading = $print_products_info_form_options['admin_email_heading'];
			print_products_info_form_send($admin_email, $subject, $admin_email_heading, $message, $headers);

			// redirect to same page with success text
			if (strpos($redirect_page, '?')) {
				$redirect_page .= '&';
			} else {
				$redirect_page .= '?';
			}
			$redirect_page .= 'iformsuccess=true';
			wp_redirect($redirect_page);
			exit;
		} else {
			$_POST['iformerror'] = __('Invalid Captcha.', 'wp2print');
		}
	}
}

add_shortcode('wp2print-simple-submit', 'print_products_info_form_shortcode');
function print_products_info_form_shortcode() {
	$countries = print_products_info_form_get_countries();
	$print_products_info_form_options = get_option("print_products_info_form_options");
	$file_upload_max_size = get_option('print_products_file_upload_max_size');
	$file_upload_target = get_option("print_products_file_upload_target");
	$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
	if (!$file_upload_max_size) { $file_upload_max_size = 2; }
	$upload_to = 'host';
	$plupload_url = get_bloginfo('url').'/index.php?ajaxupload=artwork';
	if ($file_upload_target == 'amazon' && $amazon_s3_settings['s3_access_key'] && $amazon_s3_settings['s3_secret_key']) {
		$upload_to = 'amazon';

		$s3_data = print_products_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size);
		$plupload_url = $s3_data['amazon_url'];
		$amazon_file_url = $s3_data['amazon_file_url'];
		$multiparams = $s3_data['multiparams'];
	}

	ob_start();
	include(PRINT_PRODUCTS_TEMPLATES_DIR.'simple-submit.php');
	return ob_get_clean();
}

function print_products_info_form_send($to, $subject, $email_heading, $message, $headers) {
	if (class_exists('WooCommerce')) {
		$woo = WC();
		$mailer = $woo->mailer();
		$message = $mailer->wrap_message($email_heading, $message);
		$mailer->send($to, $subject, $message);
	} else {
		wp_mail($to, $subject, $message, $headers);
	}
}
?>