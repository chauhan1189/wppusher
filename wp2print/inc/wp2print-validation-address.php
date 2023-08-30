<?php
add_action('wp_loaded', 'print_products_vaddress_actions');
function print_products_vaddress_actions() {
	if (isset($_POST['validaddressaction']) && $_POST['validaddressaction'] == 'save') {
		$order_id = (int)$_POST['order_id'];
		if ($order_id) {
			$order = wc_get_order($order_id);

			$odata = array(
				'address' => $order->get_shipping_address_1(),
				'address2' => $order->get_shipping_address_2(),
				'city' => $order->get_shipping_city(),
				'state' => $order->get_shipping_state(),
				'zip' => $order->get_shipping_postcode()
			);

			$verified_address = print_products_vaddress_verify($odata);
			if (!isset($verified_address['error'])) {
				update_post_meta($order_id, '_shipping_address_1', $verified_address['address']);
				update_post_meta($order_id, '_shipping_address_2', '');
				update_post_meta($order_id, '_shipping_city', $verified_address['city']);
				update_post_meta($order_id, '_shipping_state', $verified_address['state']);
				update_post_meta($order_id, '_shipping_postcode', $verified_address['zip']);
			}
		}
		wp_redirect(admin_url('post.php?post='.$order_id.'&action=edit&message=1'));
		exit;
	}
}

add_action('woocommerce_admin_order_data_after_shipping_address', 'print_products_vaddress_admin_order_data_after_shipping_address');
function print_products_vaddress_admin_order_data_after_shipping_address($order) {
	$valid_address_options = get_option("print_products_valid_address_options");
	$order_shipping_country = $order->get_shipping_country();
	if (isset($valid_address_options['enable']) && $valid_address_options['enable'] == 1 && $order_shipping_country == 'US') {
		$city = $order->get_shipping_city();
		$state = $order->get_shipping_state();
		$zip = $order->get_shipping_postcode();

		$odata = array(
			'address' => $order->get_shipping_address_1(),
			'address2' => $order->get_shipping_address_2(),
			'city' => $city,
			'state' => $state,
			'zip' => $zip
		);

		$verified_address = print_products_vaddress_verify($odata);
		if (isset($verified_address['error'])) {
			?>
			<div class="sh-verify-address">
				<script>jQuery('.sh-verify-address').parent().find('.address p').css('border', '1px solid #FF0000');</script>
			</div>
			<?php
		} else {
			$show_content = false;
			if (strtolower($city) != strtolower($verified_address['city'])) { $show_content = true; }
			if (strtolower($state) != strtolower($verified_address['state'])) { $show_content = true; }
			if (strtolower($zip) != strtolower($verified_address['zip'])) { $show_content = true; }
			if ($show_content) {
				?>
				<div class="sh-verify-address">
					<div class="sva-title"><?php _e('USPS recommended address', 'wp2print'); ?></div>
					<div class="sva-content">
						<?php echo $verified_address['address']; ?><br>
						<?php echo $verified_address['city']; ?>, <?php echo $verified_address['state']; ?> <?php echo $verified_address['zip']; ?><br>
						<input type="button" value="<?php _e('Use verified address', 'wp2print'); ?>" class="button" style="margin-top:10px;" onclick="sva_save_address();">
					</div>
				</div>
				<?php
			}
		}
	}
}

function print_products_vaddress_verify($data) {
	$user = '776PRINT5318';

	$usps_url = "http://production.shippingapis.com/ShippingAPI.dll?API=Verify";

	$xml_data = "<AddressValidateRequest USERID='$user'>" .
		"<IncludeOptionalElements>true</IncludeOptionalElements>" .
		"<ReturnCarrierRoute>true</ReturnCarrierRoute>" .
		"<Address ID='0'>" .
		"<FirmName />" .
		"<Address1>".$data['address']."</Address1>" .
		"<Address2>".$data['address2']."</Address2>" .
		"<City>".$data['city']."</City>" .
		"<State>".$data['state']."</State>" .
		"<Zip5>".$data['zip']."</Zip5>" .
		"<Zip4></Zip4>" .
		"</Address>" .
		"</AddressValidateRequest>";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $usps_url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'XML=' . $xml_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
	$result = curl_exec($ch);
	curl_close($ch);

	$verify_data = json_decode(json_encode(simplexml_load_string($result)), true);

	if (isset($verify_data['Address']['Error'])) {
		return array('error' => $verify_data['Address']['Error']['Description']);
	}
	return array(
		'address' => $verify_data['Address']['Address2'],
		'city' => $verify_data['Address']['City'],
		'state' => $verify_data['Address']['State'],
		'zip' => $verify_data['Address']['Zip5']
	);
}
?>