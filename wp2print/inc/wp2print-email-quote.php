<?php
function print_products_email_quote_options() {
	return get_option("print_products_email_quote_options");
}

function print_products_email_quote_enabled() {
	global $current_user;
	$email_quote_options = print_products_email_quote_options();
	if ($email_quote_options && $email_quote_options['enable'] == 1) {
		$no_private_disable = true;
		$user_group = print_products_users_groups_get_user_group($current_user->ID);
		if ($email_quote_options['disable_private'] == 1 && $user_group) {
			$no_private_disable = false;
		}
		if ($no_private_disable) {
			return true;
		}
	}
	return false;
}

function print_products_email_quote_form() {
	if (print_products_email_quote_enabled()) { ?>
		<div class="email-quote-box">
			<form method="POST" class="email-quote-form" action="<?php echo site_url('/index.php'); ?>">
			<input type="text" name="eq_email" class="eq-email" placeholder="<?php _e('Email address', 'wp2print'); ?>">
			<input type="button" class="button alt email-quote-btn" value="<?php _e('Email Quote', 'wp2print'); ?>" onclick="wp2print_email_quote()">
			<div class="eq-errors">
				<span class="error-empty"><?php _e('Please enter email address.', 'wp2print'); ?></span>
				<span class="error-incorrect" style="display:none;"><?php _e('Email address is incorrect.', 'wp2print'); ?></span>
			</div>
			<div class="eq-success"><?php _e('We have mailed a quotation to you.', 'wp2print'); ?></div>
			</form>
		</div>
	<?php }
}

function print_products_ajax_email_quote_send() {
	global $terms_names, $attribute_names, $attribute_types;
	print_products_price_matrix_attr_names_init();

	$email_quote_options = print_products_email_quote_options();
	$subject = $email_quote_options['subject'];
	$heading = $email_quote_options['heading'];
	$toptext = trim($email_quote_options['toptext']);
	$bottomtext = trim($email_quote_options['bottomtext']);

	if (!strlen($subject)) { $subject = __('Price Quote', 'wp2print'); }
	if (!strlen($heading)) { $heading = $subject; }

	$email = $_POST['email'];
	$product_id = $_POST['product_id'];
	$product_type = $_POST['product_type'];
	$quantity = $_POST['quantity'];
	$smparams = $_POST['smparams'];
	$fmparams = $_POST['fmparams'];
	$price = $_POST['price'];
	$booksqty = $_POST['booksqty'];
	$pagesqty = $_POST['pagesqty'];
	$width = $_POST['width'];
	$height = $_POST['height'];
	$project_name = $_POST['project_name'];
	$total_price = $_POST['total_price'];
	$total_area = $_POST['total_area'];
	$total_pages = $_POST['total_pages'];
	$area_bw = $_POST['area_bw'];
	$pages_bw = $_POST['pages_bw'];
	$area_cl = $_POST['area_cl'];
	$pages_cl = $_POST['pages_cl'];

	$product_name = get_the_title($product_id);
	$product_url = get_permalink($product_id);
	$attribute_labels = (array)get_post_meta($product_id, '_attribute_labels', true);
	$attribute_display = (array)get_post_meta($product_id, '_attribute_display', true);
	$dimension_unit = print_products_get_aec_dimension_unit();

	if ($product_type == 'book') {
		$pagesqty = explode(';', $pagesqty);
	}

	$product_attributes = array();
	if ($smparams) {
		$smattrs = explode(';', $smparams);
		foreach($smattrs as $smkey => $smattr) {
			$smarray = explode('|', $smattr);
			$mtype_id = $smarray[0];
			$atarray = explode('-', $smarray[1]);
			if ($product_type == 'book') {
				$atit = print_products_get_matrix_title($mtype_id);
				$product_attributes[] = 'pq|'.$atit.':'.$pagesqty[$smkey];
			}
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}
	if ($fmparams) {
		$fmattrs = explode(';', $fmparams);
		foreach($fmattrs as $fmattr) {
			$fmarray = explode('|', $fmattr);
			$atarray = explode('-', $fmarray[1]);
			foreach($atarray as $attr_term) {
				if (!in_array($attr_term, $product_attributes)) {
					$product_attributes[] = $attr_term;
				}
			}
		}
	}
	$attributes_vals = print_products_get_attributes_vals($product_attributes, $product_type, $attribute_labels, $attribute_display);

	$nl = '<br>';

	$quote_details  = __('Product', 'wp2print').': '.$product_name.$nl;
	$quote_details .= __('URL', 'wp2print').': <a href="'.$product_url.'">'.$product_url.'</a>'.$nl.$nl;

	if ($product_type == 'book') {
		$quote_details .= print_products_attribute_label('bquantity', $attribute_labels, __('Quantity of bound books', 'wp2print')).': '.$booksqty.$nl;
	} else {
		$quote_details .= print_products_attribute_label('quantity', $attribute_labels, __('Quantity', 'wp2print')).': '.$quantity.$nl;
	}
	if ($product_type == 'area') {
		$quote_details .= print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')).' ('.$dimension_unit.'): '.$width.$nl;
		$quote_details .= print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')).' ('.$dimension_unit.'): '.$height.$nl;
	} else if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') {
		$quote_details .= __('Project Name', 'wp2print').': '.$project_name.$nl;
	}

	foreach($attributes_vals as $attributes_val) {
		$quote_details .= strip_tags($attributes_val).$nl;
	}

	if ($product_type == 'aec' || $product_type == 'aecsimple') {
		$quote_details .= __('Total Area', 'wp2print').': '.number_format($total_area, 2).' '.$dimension_unit.'<sup>2</sup>'.$nl;
		$quote_details .= __('Total Pages', 'wp2print').': '.$total_pages.$nl;
	} else if ($product_type == 'aecbwc') {
		$quote_details .= __('Total Area', 'wp2print').': '.number_format($total_area, 2).' '.$dimension_unit.'<sup>2</sup>'.$nl;
		$quote_details .= __('Total Pages', 'wp2print').': '.$total_pages.$nl;
		$quote_details .= __('Area B/W', 'wp2print').': '.number_format($area_bw, 2).' '.$dimension_unit.'<sup>2</sup>'.$nl;
		$quote_details .= __('Pages B/W', 'wp2print').': '.$pages_bw.$nl;
		$quote_details .= __('Area Color', 'wp2print').': '.number_format($area_cl, 2).' '.$dimension_unit.'<sup>2</sup>'.$nl;
		$quote_details .= __('Pages Color', 'wp2print').': '.$pages_cl.$nl;
	}

	if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') {
		$quote_details .= $nl.__('Total price', 'wp2print').': '.wc_price($total_price);
	} else {
		$quote_details .= $nl.__('Total price', 'wp2print').': '.wc_price($price);
	}

	$wc_emails = new WC_Emails();

	// customer email
	$message = '';
	if (strlen($toptext)) { $message .= $toptext.$nl.$nl; }
	$message .= $quote_details;
	if (strlen($bottomtext)) { $message .= $nl.$nl.$bottomtext; }

	$message = $wc_emails->wrap_message($heading, $message);
	$wc_emails->send($email, $subject, $message);

	// admin email
	$admin_email = get_option('admin_email');

	$message  = __('Customer', 'wp2print').': <a href="mailto:'.$email.'">'.$email.'</a>'.$nl.$nl;
	$message .= $quote_details;

	$message = $wc_emails->wrap_message($heading, $message);
	$wc_emails->send($admin_email, $subject, $message);
}
?>