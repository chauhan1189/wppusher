<?php
$print_products_settings_error_message = '';
add_action('wp_loaded', 'print_products_activation_process');
function print_products_activation_process() {
	global $print_products_settings_error_message;
	if (isset($_POST['print_products_settings_submit'])) {
		switch ($_POST['print_products_settings_submit']) {
			case "license":
				$actval = 1;
				$license_key = trim($_POST['license_key']);
				$slm_action = trim($_POST['slm_action']);
				if ($slm_action == 'slm_deactivate') { $actval = 2; }
				if (strlen($license_key)) {
					$data = array ();
					$data['secret_key'] = PRINT_PRODUCTS_API_SECRET_KEY;
					$data['slm_action'] = $slm_action;
					$data['license_key'] = $license_key;
					$data['registered_domain'] = $_SERVER['SERVER_NAME'];
					$data['item_reference'] = 'wp2print plugin';

					// send data to activation server
					$ch = curl_init(PRINT_PRODUCTS_API_SERVER_URL);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$response = json_decode(curl_exec($ch));
					$response_result = $response->result;
					$response_message = $response->message;
					if ($actval == 1) {
						if ($response_result == 'success') {
							$home_url = $_SERVER['SERVER_NAME'];
							$license_activation = $license_key.':'.md5($license_key.$home_url);
							update_option('print_products_license_activation', $license_activation);
							wp_redirect('admin.php?page=print-products-settings&tab=license&activate='.$actval);
							exit;
						} else {
							$print_products_settings_error_message = $response_message;
						}
					} else {
						delete_option('print_products_license_activation');
						wp_redirect('admin.php?page=print-products-settings&tab=license&activate='.$actval);
						exit;
					}
				}
			break;
			case "fuploads":
				$file_upload_target = $_POST['file_upload_target'];
				$file_upload_max_size = $_POST['file_upload_max_size'];
				$amazon_s3_settings = array(
					's3_access_key' => trim($_POST['s3_access_key']),
					's3_secret_key' => trim($_POST['s3_secret_key']),
					's3_bucketname' => trim($_POST['s3_bucketname']),
					's3_region' => trim($_POST['s3_region']),
					's3_access' => trim($_POST['s3_access']),
					's3_path' => trim($_POST['s3_path'])
				);
				update_option("print_products_file_upload_target", $file_upload_target);
				update_option("print_products_file_upload_max_size", $file_upload_max_size);
				update_option("print_products_amazon_s3_settings", $amazon_s3_settings);

				wp_redirect('admin.php?page=print-products-settings&tab=fuploads&success=true');
				exit;
			break;
			case "infoform":
				$info_form_options = $_POST['info_form_options'];

				update_option("print_products_info_form_options", $info_form_options);

				wp_redirect('admin.php?page=print-products-settings&tab=infoform&success=true');
				exit;
			break;
			case "options":
				$print_products_plugin_options = $_POST['print_products_plugin_options'];

				update_option("print_products_plugin_options", $print_products_plugin_options);

				wp_redirect('admin.php?page=print-products-settings&tab=options&success=true');
				exit;
			break;
			case "api":
				$print_products_plugin_api = $_POST['print_products_plugin_api'];

				update_option("print_products_plugin_api", $print_products_plugin_api);

				wp_redirect('admin.php?page=print-products-settings&tab=api&success=true');
				exit;
			break;
			case "aec":
				$print_products_plugin_aec = $_POST['print_products_plugin_aec'];

				update_option("print_products_plugin_aec", $print_products_plugin_aec);

				wp_redirect('admin.php?page=print-products-settings&tab=aec&success=true');
				exit;
			break;
			case "proofing":
				$print_products_email_options = $_POST['print_products_email_options'];

				update_option("print_products_email_options", $print_products_email_options);

				wp_redirect('admin.php?page=print-products-settings&tab=proofing&success=true');
				exit;
			break;
			case "jobticket":
				$exclude_prices = (int)$_POST['print_products_jobticket_options']['exclude_prices'];
				$print_products_jobticket_options = array('exclude_prices' => $exclude_prices);

				update_option("print_products_jobticket_options", $print_products_jobticket_options);

				wp_redirect('admin.php?page=print-products-settings&tab=jobticket&success=true');
				exit;
			break;
			case "emailquote":
				$print_products_email_quote_options = $_POST['print_products_email_quote_options'];

				update_option("print_products_email_quote_options", $print_products_email_quote_options);

				wp_redirect('admin.php?page=print-products-settings&tab=emailquote&success=true');
				exit;
			break;
			case "vendor":
				$print_products_vendor_options = array(
					'shipping_address' => $_POST['shipping_address'],
					'billing_address' => $_POST['billing_address'],
					'use_billing' => (int)$_POST['use_billing'],
					'show_column' => (int)$_POST['show_column'],
					'show_to_customer' => (int)$_POST['show_to_customer'],
					'show_assign_to_me' => (int)$_POST['show_assign_to_me'],
					'email_subject' => $_POST['email_subject'],
					'email_header' => $_POST['email_header'],
					'email_top_text' => $_POST['email_top_text']
				);

				update_option("print_products_vendor_options", $print_products_vendor_options);

				wp_redirect('admin.php?page=print-products-settings&tab=vendor&success=true');
				exit;
			break;
			case "employee":
				$print_products_employee_options = array();
				$print_products_employee_options['show_column'] = (int)$_POST['show_column'];
				$print_products_employee_options['show_to_customer'] = (int)$_POST['show_to_customer'];
				$print_products_employee_options['show_contact_info'] = (int)$_POST['show_contact_info'];

				update_option("print_products_employee_options", $print_products_employee_options);

				wp_redirect('admin.php?page=print-products-settings&tab=employee&success=true');
				exit;
			break;
			case "createorder":
				$print_products_create_order_options = $_POST['print_products_create_order_options'];

				update_option("print_products_create_order_options", $print_products_create_order_options);

				wp_redirect('admin.php?page=print-products-settings&tab=createorder&success=true');
				exit;
			break;
			case "sendquote":
				$print_products_send_quote_options = $_POST['print_products_send_quote_options'];

				update_option("print_products_send_quote_options", $print_products_send_quote_options);

				wp_redirect('admin.php?page=print-products-settings&tab=sendquote&success=true');
				exit;
			break;
			case "validaddress":
				$print_products_valid_address_options = $_POST['print_products_valid_address_options'];

				update_option("print_products_valid_address_options", $print_products_valid_address_options);

				wp_redirect('admin.php?page=print-products-settings&tab=validaddress&success=true');
				exit;
			break;
			case "shipping":
				$print_products_shipping_options = $_POST['print_products_shipping_options'];
				$print_products_shipping_options['excluded_dates'] = trim($print_products_shipping_options['excluded_dates']);

				update_option("print_products_shipping_options", $print_products_shipping_options);

				wp_redirect('admin.php?page=print-products-settings&tab=shipping&success=true');
				exit;
			break;
			case "oistatus":
				$print_products_oistatus_options = $_POST['print_products_oistatus_options'];

				$ois_data = $_POST['ois_data'];
				unset($ois_data['{N}']);
				$ois_list = array();
				if ($ois_data && is_array($ois_data) && count($ois_data)) {
					foreach($ois_data as $oistatus) {
						if (strlen($oistatus['name'])) {
							$sort = (int)$oistatus['sort'];
							if (array_key_exists($sort, $ois_list)) {
								$max = max(array_keys($ois_list));
								$sort = $max + 1;
								$oistatus['sort'] = $sort;
							}
							$ois_list[$sort] = $oistatus;
						}
					}
				}
				ksort($ois_list);

				$print_products_oistatus_options['list'] = $ois_list;

				update_option("print_products_oistatus_options", $print_products_oistatus_options);

				wp_redirect('admin.php?page=print-products-settings&tab=oistatus&success=true');
				exit;
			break;
			case "prodview":
				$print_products_prodview_options = $_POST['print_products_prodview_options'];

				update_option("print_products_prodview_options", $print_products_prodview_options);

				wp_redirect('admin.php?page=print-products-settings&tab=prodview&success=true');
			break;
			case "recaptcha":
				$print_products_recaptcha_options = $_POST['print_products_recaptcha_options'];

				update_option("print_products_recaptcha_options", $print_products_recaptcha_options);

				wp_redirect('admin.php?page=print-products-settings&tab=recaptcha&success=true');
			break;
			case "fmodification":
				$print_products_fmodification_options = $_POST['print_products_fmodification_options'];

				update_option("print_products_fmodification_options", $print_products_fmodification_options);

				wp_redirect('admin.php?page=print-products-settings&tab=fmodification&success=true');
			break;
			case "printersplan":
				$print_products_printersplan_options = $_POST['print_products_printersplan_options'];

				update_option("print_products_printersplan_options", $print_products_printersplan_options);

				wp_redirect('admin.php?page=print-products-settings&tab=printersplan&success=true');
			break;
		}
	}
}


function print_products_settings() {
	global $print_products_settings_error_message, $wpdb;
	$tab = $_GET['tab'];
	if (!strlen($tab)) { $tab = 'license'; }

	$print_products_license_activation = get_option('print_products_license_activation');
	if ($print_products_license_activation) {
		$ppla = explode(':', $print_products_license_activation);
		$print_products_license_key = $ppla[0];
	}
	?>
	<div class="wrap wp2print-wrap wp2print-settings-wrap">
		<h2><?php _e('wp2print Settings', 'wp2print'); ?></h2><br />
		<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<a href="admin.php?page=print-products-settings&tab=license" class="nav-tab<?php if ($tab == 'license') { echo ' nav-tab-active'; } ?>"><?php _e('License', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=fuploads" class="nav-tab<?php if ($tab == 'fuploads') { echo ' nav-tab-active'; } ?>"><?php _e('File uploads', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=infoform" class="nav-tab<?php if ($tab == 'infoform') { echo ' nav-tab-active'; } ?>"><?php _e('Simple Submit Form', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=options" class="nav-tab<?php if ($tab == 'options') { echo ' nav-tab-active'; } ?>"><?php _e('Options', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=api" class="nav-tab<?php if ($tab == 'api') { echo ' nav-tab-active'; } ?>"><?php _e('Single Sign-on', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=aec" class="nav-tab<?php if ($tab == 'aec') { echo ' nav-tab-active'; } ?>"><?php _e('RapidQuote', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=proofing" class="nav-tab<?php if ($tab == 'proofing') { echo ' nav-tab-active'; } ?>"><?php _e('Proofing', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=jobticket" class="nav-tab<?php if ($tab == 'jobticket') { echo ' nav-tab-active'; } ?>"><?php _e('Job-ticket', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=emailquote" class="nav-tab<?php if ($tab == 'emailquote') { echo ' nav-tab-active'; } ?>"><?php _e('Email Quote', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=vendor" class="nav-tab<?php if ($tab == 'vendor') { echo ' nav-tab-active'; } ?>"><?php _e('Vendor assignment', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=employee" class="nav-tab<?php if ($tab == 'employee') { echo ' nav-tab-active'; } ?>"><?php _e('Employee Assignment', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=createorder" class="nav-tab<?php if ($tab == 'createorder') { echo ' nav-tab-active'; } ?>"><?php _e('Create Order', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=sendquote" class="nav-tab<?php if ($tab == 'sendquote') { echo ' nav-tab-active'; } ?>"><?php _e('Send Quote', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=validaddress" class="nav-tab<?php if ($tab == 'validaddress') { echo ' nav-tab-active'; } ?>"><?php _e('Address validation', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=shipping" class="nav-tab<?php if ($tab == 'shipping') { echo ' nav-tab-active'; } ?>"><?php _e('Shipping', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=checkp" class="nav-tab<?php if ($tab == 'checkp') { echo ' nav-tab-active'; } ?>"><?php _e('Product configuration report', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=oistatus" class="nav-tab<?php if ($tab == 'oistatus') { echo ' nav-tab-active'; } ?>"><?php _e('Production Status', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=prodview" class="nav-tab<?php if ($tab == 'prodview') { echo ' nav-tab-active'; } ?>"><?php _e('Production View', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=recaptcha" class="nav-tab<?php if ($tab == 'recaptcha') { echo ' nav-tab-active'; } ?>"><?php _e('reCaptcha', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=fmodification" class="nav-tab<?php if ($tab == 'fmodification') { echo ' nav-tab-active'; } ?>"><?php _e('File Modification', 'wp2print'); ?></a>
			<a href="admin.php?page=print-products-settings&tab=printersplan" class="nav-tab<?php if ($tab == 'printersplan') { echo ' nav-tab-active'; } ?>"><?php _e('Printers Plan', 'wp2print'); ?></a>
		</h2>
		<?php if ($tab == 'license') {
			$license_key = $print_products_license_key;
			if (isset($_POST['print_products_activation_action']) && $_POST['print_products_activation_action'] == 'true') {
				$license_key = trim($_POST['license_key']);
			}
			?>
			<form action="admin.php?page=print-products-settings" method="POST">
			<input type="hidden" name="print_products_settings_submit" value="license">
			<?php if (strlen($print_products_settings_error_message)) { ?>
				<div id="message" class="error fade"><p style="color:#FF0000;"><?php echo $print_products_settings_error_message; ?></p></div>
			<?php } else if (isset($_GET['activate']) && $_GET['activate'] == '1') { ?>
				<div id="message" class="updated fade"><p><?php _e('License Key was successfully activated.', 'wp2print'); ?></p></div>
			<?php } else if (isset($_GET['activate']) && $_GET['activate'] == '2') { ?>
				<div id="message" class="updated fade"><p><?php _e('License Key was successfully deactivated.', 'wp2print'); ?></p></div>
			<?php } ?>
			<?php if ($print_products_license_key) { ?>
				<p><?php _e('You can deactivate the license key for `wp2print` plugin.', 'wp2print'); ?></p>
			<?php } else { ?>
				<p><?php _e('Please enter the license key for `wp2print` plugin to activate it.', 'wp2print'); ?></p>
			<?php } ?>
			<table>
				<tr>
					<td><?php _e('License Key', 'wp2print'); ?>:
					<?php print_products_help_icon('license_key'); ?></td>
					<td><input type="text" name="license_key" value="<?php echo $license_key; ?>" style="width:250px;"></td>
					<td>
						<?php if ($print_products_license_key) { ?>
							<input type="hidden" name="slm_action" value="slm_deactivate">
							<input type="submit" class="button-primary" value="<?php _e('Deactivate', 'wp2print') ?>" />
						<?php } else { ?>
							<input type="hidden" name="slm_action" value="slm_activate">
							<input type="submit" class="button-primary" value="<?php _e('Activate', 'wp2print') ?>" />
						<?php } ?>
					</td>
				</tr>
			</table>
			</form>
		<?php } else if ($tab == 'fuploads') {
			$file_upload_target = get_option("print_products_file_upload_target");
			$file_upload_max_size = get_option("print_products_file_upload_max_size");
			$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
			$s3_path_vals = array('date', 'date/time', 'date/username', 'date/username/time', 'username', 'username/date', 'username/date/time');
			$s3_region_vals = array(
				'us-east-1' => 'US East (N. Virginia)',
				'us-east-2' => 'US East (Ohio)',
				'us-west-1' => 'US West (N. California)',
				'us-west-2' => 'US West (Oregon)',
				'ca-central-1' => 'Canada (Central)',
				'eu-central-1' => 'EU (Frankfurt)',
				'eu-west-1' => 'EU (Ireland)',
				'eu-west-2' => 'EU (London)',
				'eu-west-3' => 'EU (Paris)',
				'eu-north-1' => 'EU (Stockholm)',
				'ap-east-1' => 'Asia Pacific (Hong Kong)',
				'ap-south-1' => 'Asia Pacific (Mumbai)',
				'ap-northeast-3' => 'Asia Pacific (Osaka-Local)',
				'ap-northeast-2' => 'Asia Pacific (Seoul)',
				'ap-southeast-1' => 'Asia Pacific (Singapore)',
				'ap-southeast-2' => 'Asia Pacific (Sydney)',
				'ap-northeast-1' => 'Asia Pacific (Tokyo)',
				'cn-north-1' => 'China (Beijing)',
				'cn-northwest-1' => 'China (Ningxia)',
				'sa-east-1' => 'South America (Sao Paulo)',
				'me-south-1' => 'Middle East (Bahrain)',
				'us-gov-east-1' => 'AWS GovCloud (US-East)',
				'us-gov-west-1' => 'AWS GovCloud (US-West)'
			);
			$s3_access_vals = array('public' => __('Public', 'wp2print'), 'private' => __('Private', 'wp2print'));
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="fuploads">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('File uploads settings were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('File upload target', 'wp2print'); ?>:
				<?php print_products_help_icon('file_upload_target'); ?></td>
				<td><select name="file_upload_target">
					<option value="host"><?php _e('Host server', 'wp2print'); ?></option>
					<option value="amazon"<?php if ($file_upload_target == 'amazon') { echo ' SELECTED'; } ?>><?php _e('Amazon S3', 'wp2print'); ?></option>
				</select></td>
			  </tr>
			  <tr>
				<td><?php _e('S3 Access Key', 'wp2print'); ?>:
				<?php print_products_help_icon('s3_access_key'); ?></td>
				<td><input type="text" name="s3_access_key" value="<?php echo $amazon_s3_settings['s3_access_key']; ?>" style="width:400px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('S3 Secret Key', 'wp2print'); ?>:
				<?php print_products_help_icon('s3_secret_key'); ?></td>
				<td><input type="password" name="s3_secret_key" value="<?php echo $amazon_s3_settings['s3_secret_key']; ?>" style="width:400px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('S3 Bucketname', 'wp2print'); ?>:
				<?php print_products_help_icon('s3_bucketname'); ?></td>
				<td><input type="text" name="s3_bucketname" value="<?php echo $amazon_s3_settings['s3_bucketname']; ?>" style="width:400px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('S3 Region', 'wp2print'); ?>:
				<?php print_products_help_icon('s3_region'); ?></td>
				<td>
					<select name="s3_region">
						<option value=""><?php _e('v2 signature', 'wp2print'); ?></option>
						<?php foreach($s3_region_vals as $rkey => $rval) { $s = ''; if ($rkey == $amazon_s3_settings['s3_region']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $rkey; ?>"<?php echo $s; ?>><?php echo $rval; ?></option>
						<?php } ?>
					</select>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('S3 Path', 'wp2print'); ?>:
				<?php print_products_help_icon('s3_path'); ?></td>
				<td>
					<select name="s3_path">
						<option value="">-- <?php _e('Select Path', 'wp2print'); ?> --</option>
						<?php foreach($s3_path_vals as $s3_path_val) { $s = ''; if ($s3_path_val == $amazon_s3_settings['s3_path']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $s3_path_val; ?>"<?php echo $s; ?>><?php echo $s3_path_val; ?></option>
						<?php } ?>
					</select>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('S3 Files Access', 'wp2print'); ?>:
				<?php print_products_help_icon('s3_access'); ?></td>
				<td>
					<select name="s3_access">
						<?php foreach($s3_access_vals as $akey => $aval) { $s = ''; if ($akey == $amazon_s3_settings['s3_access']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $akey; ?>"<?php echo $s; ?>><?php echo $aval; ?></option>
						<?php } ?>
					</select>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('File upload max size', 'wp2print'); ?>, Mb:
				<?php print_products_help_icon('file_upload_max_size'); ?></td>
				<td><input type="number" name="file_upload_max_size" value="<?php echo (int)$file_upload_max_size; ?>" min="1" style="width:60px;">
				</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'infoform') {
			$print_products_info_form_options = get_option("print_products_info_form_options");
			$countries = print_products_info_form_get_countries();
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="infoform">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Form settings were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Form title', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_form_title'); ?></td>
				<td><input type="text" name="info_form_options[form_title]" value="<?php echo $print_products_info_form_options['form_title']; ?>" style="width:450px;">
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Form success text', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_form_success_text'); ?></td>
				<td><textarea name="info_form_options[form_success_text]" style="width:450px;height:150px;"><?php echo $print_products_info_form_options['form_success_text']; ?></textarea>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Default country', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_default_country'); ?></td>
				<td>
					<select name="info_form_options[default_country]" style="width:450px;">
						<option value="">-- <?php _e('Select country', 'wp2print'); ?> --</option>
						<?php foreach($countries as $ckey => $cval) { $s = ''; if ($ckey == $print_products_info_form_options['default_country']) { $s = ' SELECTED'; } ?>
							<option value="<?php echo $ckey; ?>"<?php echo $s; ?>><?php echo $cval; ?></option>
						<?php } ?>
					</select>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Enable State field', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_enable_state_field'); ?></td>
				<td><input type="checkbox" name="info_form_options[enable_state_field]" value="1"<?php if ($print_products_info_form_options['enable_state_field']) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('State field label', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_state_field_label'); ?></td>
				<td><input type="text" name="info_form_options[state_field_label]" value="<?php echo $print_products_info_form_options['state_field_label']; ?>" style="width:450px;">
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Zip field label', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_zip_field_label'); ?></td>
				<td><input type="text" name="info_form_options[zip_field_label]" value="<?php echo $print_products_info_form_options['zip_field_label']; ?>" style="width:450px;">
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Customer email subject', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_customer_email_subject'); ?></td>
				<td><input type="text" name="info_form_options[customer_email_subject]" value="<?php echo $print_products_info_form_options['customer_email_subject']; ?>" style="width:450px;">
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Customer email heading', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_customer_email_heading'); ?></td>
				<td><input type="text" name="info_form_options[customer_email_heading]" value="<?php echo $print_products_info_form_options['customer_email_heading']; ?>" style="width:450px;">
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Customer email content', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_customer_email_content'); ?></td>
				<td><textarea name="info_form_options[customer_email_content]" style="width:450px;height:150px;"><?php echo $print_products_info_form_options['customer_email_content']; ?></textarea>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Admin email subject', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_admin_email_subject'); ?></td>
				<td><input type="text" name="info_form_options[admin_email_subject]" value="<?php echo $print_products_info_form_options['admin_email_subject']; ?>" style="width:450px;">
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Admin email heading', 'wp2print'); ?>:
				<?php print_products_help_icon('infoform_admin_email_heading'); ?></td>
				<td><input type="text" name="info_form_options[admin_email_heading]" value="<?php echo $print_products_info_form_options['admin_email_heading']; ?>" style="width:450px;">
				</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'options') {
			$print_products_plugin_options = get_option("print_products_plugin_options");
			$dfc_types = array('icons' => __('Icons', 'wp2print'), 'thumbs' => __('Thumbnails', 'wp2print'), 'filenames' => __('Filenames', 'wp2print')); ?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="options">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Buttons CSS class', 'wp2print'); ?>:
				<?php print_products_help_icon('options_butclass'); ?></td>
				<td><input type="text" name="print_products_plugin_options[butclass]" value="<?php echo $print_products_plugin_options['butclass']; ?>" style="width:300px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Display files in cart as', 'wp2print'); ?>:
				<?php print_products_help_icon('options_dfincart'); ?></td>
				<td><select name="print_products_plugin_options[dfincart]">
					<?php foreach($dfc_types as $tkey => $tval) { ?>
						<option value="<?php echo $tkey; ?>"<?php if ($tkey == $print_products_plugin_options['dfincart']) { echo ' SELECTED'; } ?>><?php echo $tval; ?></option>
					<?php } ?>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Display attributes help icon', 'wp2print'); ?>:
				<?php print_products_help_icon('options_ahelpicon'); ?></td>
				<td><input type="checkbox" name="print_products_plugin_options[ahelpicon]" value="1"<?php if ($print_products_plugin_options['ahelpicon'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Allow users to modify group', 'wp2print'); ?>:
				<?php print_products_help_icon('options_allowmodifygroup'); ?></td>
				<td><input type="checkbox" name="print_products_plugin_options[allowmodifygroup]" value="1"<?php if ($print_products_plugin_options['allowmodifygroup'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Maximum price message', 'wp2print'); ?>:
				<?php print_products_help_icon('options_max_price_message'); ?></td>
				<td><textarea name="print_products_plugin_options[max_price_message]" style="width:400px; height:200px;"><?php echo $print_products_plugin_options['max_price_message']; ?></textarea></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'api') {
			$print_products_plugin_api = get_option("print_products_plugin_api");
			$dfc_types = array('icons' => __('Icons', 'wp2print'), 'filenames' => __('Filenames', 'wp2print')); ?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="api">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Enable Single Sign-on', 'wp2print'); ?>:
				<?php print_products_help_icon('api_enable'); ?></td>
				<td><input type="checkbox" name="print_products_plugin_api[enable]" value="1"<?php if ($print_products_plugin_api['enable']) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr><td colspan="2" height="5"></td></tr>
			  <tr>
				<td><?php _e('API Key', 'wp2print'); ?>:
				<?php print_products_help_icon('api_key'); ?></td>
				<td><input type="text" name="print_products_plugin_api[key]" value="<?php echo $print_products_plugin_api['key']; ?>" style="width:400px;"></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'aec') {
			$print_products_plugin_aec = get_option("print_products_plugin_aec");
			$dfc_types = array('icons' => __('Icons', 'wp2print'), 'filenames' => __('Filenames', 'wp2print'));
			$dimunits = array('m', 'cm', 'mm', 'in', 'yd', 'ft');
			if (!$print_products_plugin_aec['aec_dimensions_unit']) {
				$print_products_plugin_aec['aec_dimensions_unit'] = print_products_get_dimension_unit();
				update_option("print_products_plugin_aec", $print_products_plugin_aec);
			}
			if (!isset($print_products_plugin_aec['upload_widget_text'])) {
				$print_products_plugin_aec['upload_widget_text'] = __('Drag files here.', 'wp2print');
			}
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="aec">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td width="185"><?php _e('Coverage % Ranges', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_coverage_ranges'); ?></td>
				<td><input type="text" name="print_products_plugin_aec[aec_coverage_ranges]" value="<?php echo $print_products_plugin_aec['aec_coverage_ranges']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Dimensions unit', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_dimensions_unit'); ?></td>
				<td><select name="print_products_plugin_aec[aec_dimensions_unit]">
					<option value="">-- <?php _e('Select unit', 'wp2print'); ?> --</option>
					<?php foreach($dimunits as $dimunit) { ?>
						<option value="<?php echo $dimunit; ?>"<?php if ($dimunit == $print_products_plugin_aec['aec_dimensions_unit']) { echo ' SELECTED'; } ?>><?php echo $dimunit; ?></option>
					<?php } ?>
				</select></td>
			  </tr>
					
			  <tr>
				<td><?php _e('Enable size modification in Low-cost option pop-up', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_enable_size'); ?></td>
				<td><input type="checkbox" name="print_products_plugin_aec[aec_enable_size]" value="1"<?php if ($print_products_plugin_aec['aec_enable_size']) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Pay Now button text', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_pay_now_text'); ?></td>
				<td><input type="text" name="print_products_plugin_aec[pay_now_text]" value="<?php echo $print_products_plugin_aec['pay_now_text']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_order_email_subject'); ?></td>
				<td><input type="text" name="print_products_plugin_aec[order_email_subject]" value="<?php echo $print_products_plugin_aec['order_email_subject']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_order_email_message'); ?></td>
				<td><textarea name="print_products_plugin_aec[order_email_message]" style="width:500px;height:150px;"><?php echo $print_products_plugin_aec['order_email_message']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: {PAGE-DETAIL-MATRIX}, {TOTAL-PRICE}, {PAY-NOW-LINK}, {PROJECT-NAME}
				</td>
			  </tr>
			  <tr>
				<td><?php _e('File upload widget text hint', 'wp2print'); ?>:
				<?php print_products_help_icon('aec_upload_widget_text'); ?></td>
				<td><input type="text" name="print_products_plugin_aec[upload_widget_text]" value="<?php echo $print_products_plugin_aec['upload_widget_text']; ?>" style="width:500px;"></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'proofing') {
			$print_products_email_options = get_option("print_products_email_options");
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="proofing">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Email options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td colspan="2" class="pp-head-td"><?php _e('Approval order email', 'wp2print'); ?>:&nbsp;</td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('email_order_proof_subject'); ?></td>
				<td><input type="text" name="print_products_email_options[order_proof_subject]" value="<?php echo $print_products_email_options['order_proof_subject']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message', 'wp2print'); ?>:
				<?php print_products_help_icon('email_order_proof_message'); ?></td>
				<td><textarea name="print_products_email_options[order_proof_message]" style="width:500px;height:150px;"><?php echo $print_products_email_options['order_proof_message']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: [ORDERS_AWAITING_APPROVAL_LINK]
				</td>
			  </tr>
			  <tr>
				<td colspan="2">&nbsp;</td>
			  </tr>
			  <tr>
				<td colspan="2" class="pp-head-td"><?php _e('Notification to admin', 'wp2print'); ?>:&nbsp;</td>
			  </tr>
			  <tr>
				<td>&nbsp;</td>
				<td><input type="checkbox" name="print_products_email_options[proof_admin_send]" value="1"<?php if ($print_products_email_options['proof_admin_send'] == 1) { echo ' CHECKED'; } ?>><?php _e('Send notification to admin', 'wp2print'); ?></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject for approvals', 'wp2print'); ?>:
				<?php print_products_help_icon('proof_admin_subject_approvals'); ?></td>
				<td><input type="text" name="print_products_email_options[proof_admin_subject_approvals]" value="<?php echo $print_products_email_options['proof_admin_subject_approvals']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message for approvals', 'wp2print'); ?>:
				<?php print_products_help_icon('proof_admin_message_approvals'); ?></td>
				<td><textarea name="print_products_email_options[proof_admin_message_approvals]" style="width:500px;height:150px;"><?php echo $print_products_email_options['proof_admin_message_approvals']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: [ORDER_ID], [ORDER_ITEM_ID]
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject for rejections', 'wp2print'); ?>:
				<?php print_products_help_icon('proof_admin_subject_rejections'); ?></td>
				<td><input type="text" name="print_products_email_options[proof_admin_subject_rejections]" value="<?php echo $print_products_email_options['proof_admin_subject_rejections']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message for rejections', 'wp2print'); ?>:
				<?php print_products_help_icon('proof_admin_message_rejections'); ?></td>
				<td><textarea name="print_products_email_options[proof_admin_message_rejections]" style="width:500px;height:150px;"><?php echo $print_products_email_options['proof_admin_message_rejections']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: [ORDER_ID], [ORDER_ITEM_ID]
				</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'jobticket') {
			$print_products_jobticket_options = get_option("print_products_jobticket_options");
			if (!$print_products_jobticket_options) { $print_products_jobticket_options = array('exclude_prices' => 0);}
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="jobticket">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Job-ticket options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Job-ticket excludes prices', 'wp2print'); ?>:
				<?php print_products_help_icon('jobticket_exclude_prices'); ?></td>
				<td><input type="checkbox" name="print_products_jobticket_options[exclude_prices]" value="1"<?php if ($print_products_jobticket_options['exclude_prices'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'emailquote') {
			$print_products_email_quote_options = get_option("print_products_email_quote_options");
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="emailquote">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Email quote options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Enable Widget', 'wp2print'); ?>:
				<?php print_products_help_icon('emailquote_enable'); ?></td>
				<td><input type="checkbox" name="print_products_email_quote_options[enable]" value="1"<?php if ($print_products_email_quote_options['enable'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('emailquote_subject'); ?></td>
				<td><input type="text" name="print_products_email_quote_options[subject]" value="<?php echo $print_products_email_quote_options['subject']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Message Heading', 'wp2print'); ?>:
				<?php print_products_help_icon('emailquote_heading'); ?></td>
				<td><input type="text" name="print_products_email_quote_options[heading]" value="<?php echo $print_products_email_quote_options['heading']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Message Top Text', 'wp2print'); ?>:
				<?php print_products_help_icon('emailquote_toptext'); ?></td>
				<td><textarea name="print_products_email_quote_options[toptext]" style="width:500px;height:100px;"><?php echo $print_products_email_quote_options['toptext']; ?></textarea></td>
			  </tr>
			  <tr>
				<td><?php _e('Message Bottom Text', 'wp2print'); ?>:
				<?php print_products_help_icon('emailquote_bottomtext'); ?></td>
				<td><textarea name="print_products_email_quote_options[bottomtext]" style="width:500px;height:100px;"><?php echo $print_products_email_quote_options['bottomtext']; ?></textarea></td>
			  </tr>
			  <tr>
				<td><?php _e('Disable widget in Private Stores', 'wp2print'); ?>:
				<?php print_products_help_icon('emailquote_disable_private'); ?></td>
				<td><input type="checkbox" name="print_products_email_quote_options[disable_private]" value="1"<?php if ($print_products_email_quote_options['disable_private'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'vendor') {
			$print_products_vendor_options = get_option("print_products_vendor_options");
			$shipping_address = $print_products_vendor_options['shipping_address'];
			$billing_address = $print_products_vendor_options['billing_address'];
			$use_billing = $print_products_vendor_options['use_billing'];
			$show_column = (int)$print_products_vendor_options['show_column'];
			$show_to_customer = (int)$print_products_vendor_options['show_to_customer'];
			$show_assign_to_me = (int)$print_products_vendor_options['show_assign_to_me'];
			$shipping_countries = WC()->countries->get_shipping_countries();
			$print_products_vendor_companies = get_option("print_products_vendor_companies");
			$vendor_users = array();
			$wp_v_users = get_users(array('role' => 'vendor'));
			if ($wp_v_users) {
				foreach($wp_v_users as $wp_v_user) {
					$vendor_users[$wp_v_user->ID] = $wp_v_user->display_name;
				}
			}
			?>
			<form method="POST" class="print-products-settings-vendor-form">
			<input type="hidden" name="print_products_settings_submit" value="vendor">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Vendor options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<div class="pp-vc-wrap" data-del-error="<?php _e('Are you sure?', 'wp2print'); ?>" data-del-label="<?php _e('Delete', 'wp2print'); ?>" data-yes="<?php _e('Yes', 'wp2print'); ?>" data-no="<?php _e('No', 'wp2print'); ?>">
				<h3><?php _e('Vendor Companies', 'wp2print'); ?></h3>
				<table class="pp-vc-table" cellpadding="0" cellspacing="0">
					<tr>
						<td style="font-weight:700;"><?php _e('Company name', 'wp2print'); ?></td>
						<td style="font-weight:700;"><?php _e('Address', 'wp2print'); ?></td>
						<td style="font-weight:700;"><?php _e('Email', 'wp2print'); ?></td>
						<td style="font-weight:700;"><?php _e('Send email', 'wp2print'); ?></td>
						<td style="font-weight:700;"><?php _e('Employees', 'wp2print'); ?></td>
						<td style="font-weight:700;"><?php _e('Grant access', 'wp2print'); ?></td>
						<td>&nbsp;</td>
					</tr>
					<?php $vcid = 0; ?>
					<?php if ($print_products_vendor_companies) { ?>
						<?php foreach ($print_products_vendor_companies as $vcid => $vcompany) {
							$address = $vcompany['address1'];
							if (strlen($vcompany['address2'])) { $address .= ', '.$vcompany['address2']; }
							if (strlen($vcompany['city'])) { $address .= ', '.$vcompany['city']; }
							if (strlen($vcompany['state'])) { $address .= ', '.$vcompany['state']; }
							if (strlen($vcompany['postcode'])) { $address .= ' '.$vcompany['postcode']; }
							if (strlen($vcompany['country'])) { $address .= ', '.$vcompany['country']; }
							if (!isset($vcompany['send'])) { $vcompany['send'] = 1; }
							if (!isset($vcompany['access'])) { $vcompany['access'] = 0; }
							?>
							<tr class="vc-<?php echo $vcid; ?>">
								<td class="vc-nm">
									<a href="#edit" onclick="return wp2print_ppvc_edit(<?php echo $vcid; ?>);" class="lc-name"><?php echo $vcompany['name']; ?></a>
									<div class="vc-data" style="display:none;">
										<span class="c-name"><?php echo $vcompany['name']; ?></span>
										<span class="c-address1"><?php echo $vcompany['address1']; ?></span>
										<span class="c-address2"><?php echo $vcompany['address2']; ?></span>
										<span class="c-city"><?php echo $vcompany['city']; ?></span>
										<span class="c-postcode"><?php echo $vcompany['postcode']; ?></span>
										<span class="c-state"><?php echo $vcompany['state']; ?></span>
										<span class="c-country"><?php echo $vcompany['country']; ?></span>
										<span class="c-email"><?php echo $vcompany['email']; ?></span>
										<span class="c-send"><?php echo (int)$vcompany['send']; ?></span>
										<span class="c-employees"><?php if ($vcompany['employees'] && is_array($vcompany['employees'])) { echo implode(',', $vcompany['employees']); } ?></span>
										<span class="c-access"><?php echo (int)$vcompany['access']; ?></span>
									</div>
								</td>
								<td class="lc-address"><?php echo $address; ?></td>
								<td class="lc-email"><?php echo $vcompany['email']; ?></td>
								<td class="lc-send"><?php if ($vcompany['send'] == 1) { _e('Yes', 'wp2print'); } else { _e('No', 'wp2print'); } ?></td>
								<td class="lc-employees"><?php if ($vcompany['employees'] && is_array($vcompany['employees'])) { foreach($vcompany['employees'] as $ekey => $empl) { if ($ekey > 0) { echo ', '; } echo $vendor_users[$empl]; } } ?></td>
								<td class="lc-access"><?php if ($vcompany['access'] == 1) { _e('Yes', 'wp2print'); } else { _e('No', 'wp2print'); } ?></td>
								<td class="vc-del"><a href="#delete" onclick="return wp2print_ppvc_delete(<?php echo $vcid; ?>);"><?php _e('Delete', 'wp2print'); ?></a></td>
							</tr>
						<?php } ?>
					<?php } else { ?>
						<tr class="vc-no-records">
							<td colspan="7"><?php _e('No companies yet.', 'wp2print'); ?></td>
						</tr>
					<?php } ?>
				</table>
				<div class="pp-vc-add"><a href="#add-new" class="button" onclick="return wp2print_ppvc_add();"><?php _e('Add New', 'wp2print'); ?></a></div>
				<div class="pp-vc-form" data-last-cid="<?php echo $vcid; ?>" data-company-id="" data-atype="">
					<table cellpadding="0" cellspacing="0">
						<tr>
							<td><?php _e('Company name', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_name" class="vc-name" data-error="<?php _e('Company name is required field.', 'wp2print'); ?>"></td>
						</tr>
						<tr>
							<td><?php _e('Address 1', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_address1" class="vc-address1"></td>
						</tr>
						<tr>
							<td><?php _e('Address 2', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_address2" class="vc-address2"></td>
						</tr>
						<tr>
							<td><?php _e('City', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_city" class="vc-city"></td>
						</tr>
						<tr>
							<td><?php _e('Postcode', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_postcode" class="vc-postcode"></td>
						</tr>
						<tr>
							<td><?php _e('State', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_state" class="vc-state"></td>
						</tr>
						<tr>
							<td><?php _e('Country', 'wp2print'); ?>:</td>
							<td><select name="vc_country" class="vc-country">
								<option value="">-- Select country --</option>
								<?php foreach($shipping_countries as $sc_code => $sc_name) { ?>
									<option value="<?php echo $sc_code; ?>"><?php echo $sc_name; ?></option>
								<?php } ?>
							</select></td>
						</tr>
						<tr>
							<td><?php _e('Email', 'wp2print'); ?>:</td>
							<td><input type="text" name="vc_email" class="vc-email" data-error="<?php _e('Email is required field.', 'wp2print'); ?>"></td>
						</tr>
						<tr>
							<td><?php _e('Send assignment email', 'wp2print'); ?>:</td>
							<td><input type="checkbox" name="vc_send" class="vc-send" CHECKED></td>
						</tr>
						<tr>
							<td><?php _e('Employees', 'wp2print'); ?>:</td>
							<td><ul class="vc-elist">
								<?php foreach($vendor_users as $vuid => $vuname) { ?>
									<li><input type="checkbox" name="vc_employees[]" value="<?php echo $vuid; ?>" data-name="<?php echo $vuname; ?>"><?php echo $vuname; ?></li>
								<?php } ?>
							</ul></td>
						</tr>
						<tr>
							<td><?php _e('Grant access to all orders', 'wp2print'); ?>:</td>
							<td><input type="checkbox" name="vc_access" class="vc-access"></td>
						</tr>
						<tr>
							<td colspan="2"><input type="button" class="button-primary" value="<?php _e('Save', 'wp2print'); ?>" onclick="wp2print_ppvc_save()"></td>
						</tr>
					</table>
				</div>
			</div>
			<table style="width:auto;">
			  <tr>
				<td colspan="2" style="font-weight:700;"><?php _e('Vendor email options', 'wp2print'); ?></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('vendor_email_subject'); ?></td>
				<td><input type="text" name="email_subject" value="<?php echo $print_products_vendor_options['email_subject']; ?>" style="width:460px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Header', 'wp2print'); ?>:
				<?php print_products_help_icon('vendor_email_header'); ?></td>
				<td><input type="text" name="email_header" value="<?php echo $print_products_vendor_options['email_header']; ?>" style="width:460px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Top Text', 'wp2print'); ?>:
				<?php print_products_help_icon('vendor_email_top_text'); ?></td>
				<td><input type="text" name="email_top_text" value="<?php echo $print_products_vendor_options['email_top_text']; ?>" style="width:460px;"></td>
			  </tr>
			</table><br>
			<table style="width:auto;">
			  <tr>
				<td><input type="checkbox" name="show_column" value="1"<?php if ($show_column == 1) { echo ' CHECKED'; } ?>></td>
				<td><?php _e('Display Vendor in Orders pages', 'wp2print'); ?>
				<?php print_products_help_icon('vendor_show_column'); ?></td>
			  </tr>
			</table>
			<table style="width:auto;">
			  <tr>
				<td><input type="checkbox" name="show_to_customer" value="1"<?php if ($show_to_customer == 1) { echo ' CHECKED'; } ?>></td>
				<td><?php _e('Display Vendor to customer', 'wp2print'); ?>
				<?php print_products_help_icon('vendor_show_to_customer'); ?></td>
			  </tr>
			</table>
			<table style="width:auto;">
			  <tr>
				<td><input type="checkbox" name="show_assign_to_me" value="1"<?php if ($show_assign_to_me == 1) { echo ' CHECKED'; } ?>></td>
				<td><?php _e('Display Assign to Me to vendor employees', 'wp2print'); ?>
				<?php print_products_help_icon('vendor_show_assign_to_me'); ?></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'employee') {
			$print_products_employee_options = get_option("print_products_employee_options");
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="employee">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><input type="checkbox" name="show_column" value="1"<?php if ($print_products_employee_options['show_column'] == 1) { echo ' CHECKED'; } ?>></td>
				<td><?php _e('Display Employee in Orders pages', 'wp2print'); ?>
				<?php print_products_help_icon('employee_show_column'); ?></td>
			  </tr>
			</table>
			<table style="width:auto;">
			  <tr>
				<td><input type="checkbox" name="show_to_customer" value="1"<?php if ($print_products_employee_options['show_to_customer'] == 1) { echo ' CHECKED'; } ?>></td>
				<td><?php _e('Display responsible employee to customer', 'wp2print'); ?>
				<?php print_products_help_icon('employee_show_to_customer'); ?></td>
			  </tr>
			</table>
			<table style="width:auto;">
			  <tr>
				<td><input type="checkbox" name="show_contact_info" value="1"<?php if ($print_products_employee_options['show_contact_info'] == 1) { echo ' CHECKED'; } ?>></td>
				<td><?php _e('Display contact info of responsible employee to customer', 'wp2print'); ?>
				<?php print_products_help_icon('employee_show_contact_info'); ?></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'createorder') {
			$print_products_create_order_options = get_option("print_products_create_order_options"); ?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="createorder">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td colspan="2"><?php echo strtoupper(__('Create new user email', 'wp2print')); ?></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_cnu_email_subject'); ?></td>
				<td><input type="text" name="print_products_create_order_options[cnu_email_subject]" value="<?php echo $print_products_create_order_options['cnu_email_subject']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_cnu_email_message'); ?></td>
				<td><textarea name="print_products_create_order_options[cnu_email_message]" style="width:500px;height:150px;"><?php echo $print_products_create_order_options['cnu_email_message']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: {USERNAME}, {EMAIL}, {PASSWORD}</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'sendquote') {
			$print_products_send_quote_options = get_option("print_products_send_quote_options");
			if (!strlen($print_products_send_quote_options['expired_message'])) { $print_products_send_quote_options['expired_message'] = __('We are sorry but this quotation has expired. Please contact us at 123-345-5678 and we will help you complete this order.', 'wp2print'); }
			$woo_products = get_posts(array('post_type' => 'product', 'orderby' => 'title', 'order' => 'asc', 'posts_per_page' => -1));
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="sendquote">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Pay Now button text', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_pay_now_text'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[pay_now_text]" value="<?php echo $print_products_send_quote_options['pay_now_text']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Send BCC copy of quote to', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_bcc_email'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[bcc_email]" value="<?php echo $print_products_send_quote_options['bcc_email']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject 1', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_email_subject'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[email_subject]" value="<?php echo $print_products_send_quote_options['email_subject']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message 1', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_email_message'); ?></td>
				<td><textarea name="print_products_send_quote_options[email_message]" style="width:500px;height:150px;"><?php echo $print_products_send_quote_options['email_message']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: {QUOTE-DETAIL}, {PAY-NOW-LINK}
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Send mail 2', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_quote_send_email2'); ?></td>
				<td><input type="checkbox" name="print_products_send_quote_options[send_email2]" value="1" class="send-email2" onclick="send_email_option(2)"<?php if ($print_products_send_quote_options['send_email2']) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr class="send-email2-option" style="display:none;">
				<td><?php _e('Days to send mail 2', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_quote_send_email2_days'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[send_email2_days]" value="<?php echo $print_products_send_quote_options['send_email2_days']; ?>" style="width:50px;"></td>
			  </tr>
			  <tr class="send-email2-option" style="display:none;">
				<td><?php _e('Email Subject 2', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_email_subject2'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[email_subject2]" value="<?php echo $print_products_send_quote_options['email_subject2']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr class="send-email2-option" style="display:none;">
				<td><?php _e('Email Message 2', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_email_message2'); ?></td>
				<td><textarea name="print_products_send_quote_options[email_message2]" style="width:500px;height:150px;"><?php echo $print_products_send_quote_options['email_message2']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: {QUOTE-DETAIL}, {PAY-NOW-LINK}
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Send mail 3', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_quote_send_email3'); ?></td>
				<td><input type="checkbox" name="print_products_send_quote_options[send_email3]" value="1" class="send-email3" onclick="send_email_option(3)"<?php if ($print_products_send_quote_options['send_email3']) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr class="send-email3-option" style="display:none;">
				<td><?php _e('Days to send mail 3', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_quote_send_email3_days'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[send_email3_days]" value="<?php echo $print_products_send_quote_options['send_email3_days']; ?>" style="width:50px;"></td>
			  </tr>
			  <tr class="send-email3-option" style="display:none;">
				<td><?php _e('Email Subject 3', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_email_subject3'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[email_subject3]" value="<?php echo $print_products_send_quote_options['email_subject3']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr class="send-email3-option" style="display:none;">
				<td><?php _e('Email Message 3', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_email_message3'); ?></td>
				<td><textarea name="print_products_send_quote_options[email_message3]" style="width:500px;height:150px;"><?php echo $print_products_send_quote_options['email_message3']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: {QUOTE-DETAIL}, {PAY-NOW-LINK}
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Quote valid period (days)', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_quote_period'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[quote_period]" value="<?php echo $print_products_send_quote_options['quote_period']; ?>" style="width:100px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Expired quote message', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_expired_message'); ?></td>
				<td><textarea name="print_products_send_quote_options[expired_message]" style="width:500px;height:150px;"><?php echo $print_products_send_quote_options['expired_message']; ?></textarea></td>
			  </tr>
			  <tr>
				<td><?php _e('Custom product', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_custom_product'); ?></td>
				<td><select name="print_products_send_quote_options[custom_product]">
					<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
					<?php if ($woo_products) { ?>
						<?php foreach($woo_products as $woo_product) { ?>
							<option value="<?php echo $woo_product->ID; ?>"<?php if ($print_products_send_quote_options['custom_product'] == $woo_product->ID) { echo ' SELECTED'; } ?>><?php echo $woo_product->post_title; ?></option>
						<?php } ?>
					<?php } ?>
				</select></td>
			  </tr>
			  <tr>
				<td colspan="2"><hr></td>
			  </tr>
			  <tr>
				<td colspan="2"><?php echo strtoupper(__('Create new user email', 'wp2print')); ?></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_cnu_email_subject'); ?></td>
				<td><input type="text" name="print_products_send_quote_options[cnu_email_subject]" value="<?php echo $print_products_send_quote_options['cnu_email_subject']; ?>" style="width:500px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message', 'wp2print'); ?>:
				<?php print_products_help_icon('sendquote_cnu_email_message'); ?></td>
				<td><textarea name="print_products_send_quote_options[cnu_email_message]" style="width:500px;height:150px;"><?php echo $print_products_send_quote_options['cnu_email_message']; ?></textarea><br /><?php _e('Use', 'wp2print'); ?>: {USERNAME}, {EMAIL}, {PASSWORD}</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
			<script>
			function send_email_option(o) {
				var cch = jQuery('.send-email'+o).is(':checked');
				if (cch) {
					jQuery('.send-email'+o+'-option').show();
				} else {
					jQuery('.send-email'+o+'-option').hide();
				}
			}
			jQuery(document).ready(function() {
				send_email_option(2);
				send_email_option(3);
			});
			</script>
		<?php } else if ($tab == 'validaddress') {
			$print_products_valid_address_options = get_option("print_products_valid_address_options");
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="validaddress">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Verify USA addresses with USPS', 'wp2print'); ?>:
				<?php print_products_help_icon('valid_address_verify'); ?></td>
				<td><input type="checkbox" name="print_products_valid_address_options[enable]" value="1"<?php if (isset($print_products_valid_address_options['enable']) && $print_products_valid_address_options['enable'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'shipping') {
			$print_products_shipping_options = get_option("print_products_shipping_options");
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="shipping">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Each line item packed into multiple boxes', 'wp2print'); ?>:
				<?php print_products_help_icon('shipping_multiple'); ?></td>
				<td><input type="checkbox" name="print_products_shipping_options[multiple]" value="1"<?php if (isset($print_products_shipping_options['multiple']) && $print_products_shipping_options['multiple'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Excluded dates', 'wp2print'); ?> (DD/MM/YYYY):
				<?php print_products_help_icon('shipping_excluded_dates'); ?></td>
				<td><textarea name="print_products_shipping_options[excluded_dates]" style="width:200px; height:150px;"><?php echo $print_products_shipping_options['excluded_dates']; ?></textarea></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'checkp') {
			$wc_products = $wpdb->get_results(sprintf("SELECT * FROM %sposts WHERE post_type = 'product' AND post_status = 'publish' ORDER BY ID DESC", $wpdb->prefix));
			if ($wc_products) {
				$product_ids = array();
				foreach($wc_products as $wc_product) {
					$product = wc_get_product($wc_product->ID);
					$product_type = $product->get_type();
					if (print_products_is_wp2print_type($product_type)) {
						$product_ids[] = $wc_product->ID;
					}
				}
				?>
				<form method="POST" action="<?php echo home_url('/'); ?>" class="ch-product-conf-form">
				<input type="button" class="button button-primary" value="<?php _e('Check product configuration', 'wp2print'); ?>" onclick="wp2print_check_product_config()" >
				<input type="hidden" name="ch_pids" value="<?php echo implode(';', $product_ids); ?>" class="ch-pids">
				</form>
				<p class="ch-analyzing" style="display:none;"><?php _e('Analyzing', 'wp2print'); ?>...</p>
				<div class="ch-product-conf"></div>
			<?php } else { ?>
				<p><?php _e('No products', 'wp2print'); ?>.</p>
			<?php } ?>
		<?php } else if ($tab == 'oistatus') {
			$wc_order_statuses = wc_get_order_statuses();
			$print_products_oistatus_options = get_option("print_products_oistatus_options");
			$ois_list = array();
			if (isset($print_products_oistatus_options['list']) && is_array($print_products_oistatus_options['list'])) {
				$ois_list = $print_products_oistatus_options['list'];
			}
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="oistatus">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td colspan="2"><h3 style="margin-top:0;"><?php _e('Production status configuration', 'wp2print'); ?></h3></td>
			  </tr>
			  <tr>
				<td><?php _e('Use status for each order item', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_use'); ?></td>
				<td><input type="checkbox" name="print_products_oistatus_options[use]" value="1"<?php if (isset($print_products_oistatus_options['use']) && $print_products_oistatus_options['use'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td valign="top"><?php _e('Statuses list', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_list'); ?></td>
				<td style="width:500px;padding-top:5px;">
					<div class="ois-list" data-nmb="<?php echo count($ois_list); ?>" data-message="<?php _e('Are you sure?', 'wp2print'); ?>">
						<?php if (count($ois_list)) { ?>
							<?php foreach($ois_list as $ois_key => $ois_data) { ?>
								<div class="ois-row ois-row-<?php echo $ois_key; ?>">
									<div class="ois-name" rel="<?php echo $ois_key; ?>"><?php if (strlen($ois_data['name'])) { echo $ois_data['name']; } else { echo '-- NONAME --'; } ?></div>
									<div class="ois-form">
										<table cellpadding="0" cellspacing="0" style="width:100%;">
											<tr>
												<td class="td-0"><?php _e('Status Name', 'wp2print'); ?>:</td>
												<td class="td-1"><input type="text" name="ois_data[<?php echo $ois_key; ?>][name]" value="<?php echo $ois_data['name']; ?>"></td>
											</tr>
											<tr>
												<td><?php _e('Send email', 'wp2print'); ?>:</td>
												<td><input type="checkbox" name="ois_data[<?php echo $ois_key; ?>][send]" value="1"<?php if ($ois_data['send'] == 1) { echo ' CHECKED'; } ?>></td>
											</tr>
											<tr>
												<td><?php _e('Email Subject', 'wp2print'); ?>:</td>
												<td><input type="text" name="ois_data[<?php echo $ois_key; ?>][subject]" value="<?php echo stripslashes($ois_data['subject']); ?>"></td>
											</tr>
											<tr>
												<td><?php _e('Email Heading', 'wp2print'); ?>:</td>
												<td><input type="text" name="ois_data[<?php echo $ois_key; ?>][heading]" value="<?php echo stripslashes($ois_data['heading']); ?>"></td>
											</tr>
											<tr>
												<td><?php _e('Email Message', 'wp2print'); ?>:</td>
												<td><textarea name="ois_data[<?php echo $ois_key; ?>][message]"><?php echo stripslashes($ois_data['message']); ?></textarea><br><?php _e('Use {ORDER_ID}, {ORDER_DATE}, {ITEM_NAME} in text', 'wp2print'); ?>.</td>
											</tr>
											<tr>
												<td><?php _e('Color', 'wp2print'); ?>:</td>
												<td><input type="text" name="ois_data[<?php echo $ois_key; ?>][color]" value="<?php echo $ois_data['color']; ?>" class="ois-color" style="width:100px;"></td>
											</tr>
											<tr>
												<td><?php _e('Sort Order', 'wp2print'); ?>:</td>
												<td><input type="text" name="ois_data[<?php echo $ois_key; ?>][sort]" value="<?php echo $ois_data['sort']; ?>" style="width:40px;"></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td><input type="checkbox" name="ois_data[<?php echo $ois_key; ?>][assign]" value="1" style="margin:-2px 5px 0 0;"<?php if ($ois_data['assign'] == 1) { echo ' CHECKED'; } ?>><span><?php _e('Automatically assign Order Status', 'wp2print'); ?></span></td>
											</tr>
											<tr>
												<td><?php _e('Order Status', 'wp2print'); ?>:</td>
												<td><select name="ois_data[<?php echo $ois_key; ?>][ostatus]">
													<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
													<?php foreach($wc_order_statuses as $os_key => $os_name) { ?>
														<option value="<?php echo $os_key; ?>"<?php if ($ois_data['ostatus'] == $os_key) { echo ' SELECTED'; } ?>><?php echo $os_name; ?></option>
													<?php } ?>
												</select></td>
											</tr>
										</table>
										<div class="ois-delete"><a href="#delete" onclick="return wp2print_ois_delete(<?php echo $ois_key; ?>);"><?php _e('Delete', 'wp2print'); ?></a></div>
									</div>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
					<input type="button" class="button" value="<?php _e('Add New Status', 'wp2print'); ?>" style="margin:10px 0;" onclick="wp2print_ois_add()">
					<div class="ois-hidden" style="display:none;">
						<div class="ois-row ois-row-{N}">
							<div class="ois-form">
								<table cellpadding="0" cellspacing="0" style="width:100%;">
									<tr>
										<td class="td-0"><?php _e('Status Name', 'wp2print'); ?>:</td>
										<td class="td-1"><input type="text" name="ois_data[{N}][name]"></td>
									</tr>
									<tr>
										<td><?php _e('Send email', 'wp2print'); ?>:</td>
										<td><input type="checkbox" name="ois_data[{N}][send]" value="1" CHECKED></td>
									</tr>
									<tr>
										<td><?php _e('Email Subject', 'wp2print'); ?>:</td>
										<td><input type="text" name="ois_data[{N}][subject]"></td>
									</tr>
									<tr>
										<td><?php _e('Email Heading', 'wp2print'); ?>:</td>
										<td><input type="text" name="ois_data[{N}][heading]"></td>
									</tr>
									<tr>
										<td><?php _e('Email Message', 'wp2print'); ?>:</td>
										<td><textarea name="ois_data[{N}][message]"></textarea><br><?php _e('Use {ORDER_ID}, {ORDER_DATE}, {ITEM_NAME} in text', 'wp2print'); ?>.</td>
									</tr>
									<tr>
										<td><?php _e('Color', 'wp2print'); ?>:</td>
										<td><input type="text" name="ois_data[{N}][color]" class="ois-color-fld" style="width:100px;"></td>
									</tr>
									<tr>
										<td><?php _e('Sort Order', 'wp2print'); ?>:</td>
										<td><input type="text" name="ois_data[{N}][sort]" value="0" style="width:40px;"></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><input type="checkbox" name="ois_data[{N}][assign]" value="1" style="margin:-2px 5px 0 0;"><span><?php _e('Automatically assign Order Status', 'wp2print'); ?></span></td>
									</tr>
									<tr>
										<td><?php _e('Order Status', 'wp2print'); ?>:</td>
										<td><select name="ois_data[{N}][ostatus]">
											<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
											<?php foreach($wc_order_statuses as $os_key => $os_name) { ?>
												<option value="<?php echo $os_key; ?>"><?php echo $os_name; ?></option>
											<?php } ?>
										</select></td>
									</tr>
								</table>
								<div class="ois-delete"><a href="#delete" onclick="return wp2print_ois_delete({N});"><?php _e('Delete', 'wp2print'); ?></a></div>
							</div>
						</div>
					</div>
				</td>
			  </tr>
			  <tr>
				<td><?php _e('Default status', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_default'); ?></td>
				<td><select name="print_products_oistatus_options[default]">
					<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
					<?php if (count($ois_list)) { ?>
						<?php foreach($ois_list as $ois_key => $ois_data) {
							$ois_slug = print_products_oistatus_get_slug($ois_data['name']); ?>
							<option value="<?php echo $ois_slug; ?>"<?php if ($ois_slug == $print_products_oistatus_options['default']) { echo ' SELECTED'; } ?>><?php echo $ois_data['name']; ?></option>
						<?php } ?>
					<?php } ?>
				</select></td>
			  </tr>
			  <tr><td colspan="2"><hr></td></tr>
			  <tr>
				<td colspan="2"><h3 style="margin-top:0;"><?php _e('Shipment tracking information', 'wp2print'); ?></h3></td>
			  </tr>
			  <tr>
				<td><?php _e('Prompt for tracking numbers', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_prompt'); ?></td>
				<td><input type="checkbox" name="print_products_oistatus_options[tracking_prompt]" value="1" style="margin:0;"<?php if ($print_products_oistatus_options['tracking_prompt'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Shipped status', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_status'); ?></td>
				<td><select name="print_products_oistatus_options[tracking_status]">
					<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
					<?php if (count($ois_list)) { ?>
						<?php foreach($ois_list as $ois_key => $ois_data) {
							$ois_slug = print_products_oistatus_get_slug($ois_data['name']); ?>
							<option value="<?php echo $ois_slug; ?>"<?php if ($ois_slug == $print_products_oistatus_options['tracking_status']) { echo ' SELECTED'; } ?>><?php echo $ois_data['name']; ?></option>
						<?php } ?>
					<?php } ?>
				</select></td>
			  </tr>
			  <tr>
				<td><?php _e('Shipping companies', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_companies'); ?></td>
				<td><textarea name="print_products_oistatus_options[tracking_companies]" style="width:50%; height:120px;"><?php echo $print_products_oistatus_options['tracking_companies']; ?></textarea></td>
			  </tr>
			  <?php $tracking_companies = print_products_oistatus_get_tracking_companies(); ?>
			  <tr>
				<td><?php _e('Default shipping company', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_dcompany'); ?></td>
				<td><select name="print_products_oistatus_options[tracking_dcompany]" style="width:50%;">
					<option value="">-- <?php _e('Select', 'wp2print'); ?> --</option>
					<?php if (count($tracking_companies)) { ?>
						<?php foreach($tracking_companies as $tracking_company) { ?>
							<option value="<?php echo $tracking_company; ?>"<?php if ($tracking_company == $print_products_oistatus_options['tracking_dcompany']) { echo ' SELECTED'; } ?>><?php echo $tracking_company; ?></option>
						<?php } ?>
					<?php } ?>
				</select></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Subject', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_subject'); ?></td>
				<td><input type="text" name="print_products_oistatus_options[tracking_subject]" value="<?php echo $print_products_oistatus_options['tracking_subject']; ?>" style="width:100%;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Heading', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_heading'); ?></td>
				<td><input type="text" name="print_products_oistatus_options[tracking_heading]" value="<?php echo $print_products_oistatus_options['tracking_heading']; ?>" style="width:100%;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Email Message', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_tracking_message'); ?></td>
				<td><textarea name="print_products_oistatus_options[tracking_message]" style="width:100%; height:170px;"><?php echo $print_products_oistatus_options['tracking_message']; ?></textarea><br><?php _e('Use {ORDER_ID}, {ITEM_NAME}, {SHIPPING_COMPANY}, {TRACKING_NUMBERS} in email message.', 'wp2print'); ?></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
			</form>
		<?php } else if ($tab == 'prodview') {
			$print_products_prodview_options = get_option("print_products_prodview_options");
			$wc_order_statuses = wc_get_order_statuses();
			$exclude_woo = array();
			if (isset($print_products_prodview_options['exclude_woo'])) {
				$exclude_woo = $print_products_prodview_options['exclude_woo'];
			}
			if (!is_array($exclude_woo)) { $exclude_woo = array(); }
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="prodview">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Exclude Orders status', 'wp2print'); ?>:
				<?php print_products_help_icon('oistatus_exclude_wstatus'); ?></td>
				<td>
					<?php foreach($wc_order_statuses as $os_key => $os_name) { ?>
						<input type="checkbox" name="print_products_prodview_options[exclude_woo][]" value="<?php echo $os_key; ?>"<?php if (in_array($os_key, $exclude_woo)) { echo ' CHECKED'; } ?>><?php echo $os_name; ?><br>
					<?php } ?><br>
				</td>
			  </tr>
			</table>
			<h3><?php _e('Display Controls', 'wp2print'); ?></h3>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Display Vendor in Production pages', 'wp2print'); ?>:
				<?php print_products_help_icon('prodview_display_vendor'); ?></td>
				<td><input type="checkbox" name="print_products_prodview_options[display_vendor]" value="1"<?php if ($print_products_prodview_options['display_vendor'] == '1') { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Display Employee in Production pages', 'wp2print'); ?>:
				<?php print_products_help_icon('prodview_display_employee'); ?></td>
				<td><input type="checkbox" name="print_products_prodview_options[display_employee]" value="1"<?php if ($print_products_prodview_options['display_employee'] == '1') { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Display Customer Company in Production pages', 'wp2print'); ?>:
				<?php print_products_help_icon('prodview_display_customer'); ?></td>
				<td><input type="checkbox" name="print_products_prodview_options[display_customer]" value="1"<?php if ($print_products_prodview_options['display_customer'] == '1') { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Display Required ship date in Production pages', 'wp2print'); ?>:
				<?php print_products_help_icon('prodview_display_shipdate'); ?></td>
				<td><input type="checkbox" name="print_products_prodview_options[display_shipdate]" value="1"<?php if ($print_products_prodview_options['display_shipdate'] == '1') { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Display Required ship date in Orders pages', 'wp2print'); ?>:
				<?php print_products_help_icon('prodview_orders_display_shipdate'); ?></td>
				<td><input type="checkbox" name="print_products_prodview_options[orders_display_shipdate]" value="1"<?php if ($print_products_prodview_options['orders_display_shipdate'] == '1') { echo ' CHECKED'; } ?>></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
		<?php } else if ($tab == 'recaptcha') {
			$print_products_recaptcha_options = get_option("print_products_recaptcha_options");
			$p_versions = array('V2', 'V3');
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="recaptcha">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Use reCaptcha', 'wp2print'); ?>:
				<?php print_products_help_icon('recaptcha_use'); ?></td>
				<td><input type="checkbox" name="print_products_recaptcha_options[use]" value="1"<?php if (isset($print_products_recaptcha_options['use']) && $print_products_recaptcha_options['use'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td colspan="2" style="font-weight:700;padding-top:10px;"><?php _e('Google reCaptcha API Keys', 'wp2print'); ?>:</td>
			  </tr>
			  <tr>
				<td colspan="2" style="padding-bottom:10px;"><?php _e('Get API keys here', 'wp2print'); ?>: <a href="https://www.google.com/recaptcha/admin/" target="_blank">https://www.google.com/recaptcha/</a></td>
			  </tr>
			  <tr>
				<td><?php _e('Site Key', 'wp2print'); ?>:
				<?php print_products_help_icon('recaptcha_site_key'); ?></td>
				<td><input type="text" name="print_products_recaptcha_options[site_key]" value="<?php echo $print_products_recaptcha_options['site_key']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Secret Key', 'wp2print'); ?>:
				<?php print_products_help_icon('recaptcha_secret_key'); ?></td>
				<td><input type="text" name="print_products_recaptcha_options[secret_key]" value="<?php echo $print_products_recaptcha_options['secret_key']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Version', 'wp2print'); ?>:
				<?php print_products_help_icon('recaptcha_version'); ?></td>
				<td><select name="print_products_recaptcha_options[version]">
					<?php foreach($p_versions as $p_version) { ?>
						<option value="<?php echo $p_version; ?>"<?php if ($p_version == $print_products_recaptcha_options['version']) { echo ' SELECTED'; } ?>><?php echo $p_version; ?></option>
					<?php } ?>
					</select>
				</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
		<?php } else if ($tab == 'fmodification') {
			$statuses = array();
			$pstatuses = print_products_oistatus_get_list();
			$print_products_fmodification_options = get_option("print_products_fmodification_options");
			if ($print_products_fmodification_options && $print_products_fmodification_options['statuses'] && is_array($print_products_fmodification_options['statuses'])) {
				$statuses = $print_products_fmodification_options['statuses'];
			}
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="fmodification">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Show for production statuses', 'wp2print'); ?>:
				<?php print_products_help_icon('fmodification_production_statuses'); ?></td>
				<td>
				<?php if ($pstatuses && is_array($pstatuses) && count($pstatuses)) { ?>
					<?php foreach($pstatuses as $ps_key => $ps_name) { ?>
						<input type="checkbox" name="print_products_fmodification_options[statuses][]" value="<?php echo $ps_key; ?>"<?php if (in_array($ps_key, $statuses)) { echo ' CHECKED'; } ?>><?php echo $ps_name; ?><br>
					<?php } ?>
				<?php } ?>
				</td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
		<?php } else if ($tab == 'printersplan') {
			$print_products_printersplan_options = get_option("print_products_printersplan_options");
			?>
			<form method="POST">
			<input type="hidden" name="print_products_settings_submit" value="printersplan">
			<?php if(isset($_GET['success']) && $_GET['success'] == 'true') { ?>
				<div id="message" class="updated fade"><p><?php _e('Options were successfully saved.', 'wp2print'); ?></p></div>
			<?php } ?>
			<table style="width:auto;">
			  <tr>
				<td><?php _e('Enable XML process', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_enable'); ?></td>
				<td><input type="checkbox" name="print_products_printersplan_options[enable]" value="1"<?php if ($print_products_printersplan_options['enable'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			  <tr>
				<td><?php _e('Domain from', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_domain_from'); ?></td>
				<td><input type="text" name="print_products_printersplan_options[domain_from]" value="<?php echo $print_products_printersplan_options['domain_from']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('From', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_from'); ?></td>
				<td><input type="text" name="print_products_printersplan_options[from]" value="<?php echo $print_products_printersplan_options['from']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Domain to', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_domain_to'); ?></td>
				<td><input type="text" name="print_products_printersplan_options[domain_to]" value="<?php echo $print_products_printersplan_options['domain_to']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('To', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_to'); ?></td>
				<td><input type="text" name="print_products_printersplan_options[to]" value="<?php echo $print_products_printersplan_options['to']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('SharedSecret', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_shared_secret'); ?></td>
				<td><input type="text" name="print_products_printersplan_options[shared_secret]" value="<?php echo $print_products_printersplan_options['shared_secret']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('URL', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_url'); ?></td>
				<td><input type="text" name="print_products_printersplan_options[url]" value="<?php echo $print_products_printersplan_options['url']; ?>" style="width:350px;"></td>
			  </tr>
			  <tr>
				<td><?php _e('Concatenate the filename', 'wp2print'); ?>:
				<?php print_products_help_icon('printersplan_concatenate'); ?></td>
				<td><input type="checkbox" name="print_products_printersplan_options[concatenate]" value="1"<?php if ($print_products_printersplan_options['concatenate'] == 1) { echo ' CHECKED'; } ?>></td>
			  </tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Settings', 'wp2print'); ?>" /></p>
		<?php } ?>
	</div>
	<?php
}
?>