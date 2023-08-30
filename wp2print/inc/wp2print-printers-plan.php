<?php
$printersplan_options = get_option("print_products_printersplan_options");

add_action('woocommerce_checkout_update_order_meta', 'print_products_printers_plan_created_order');
function print_products_printers_plan_created_order($order_id) {
	global $printersplan_options;
	$upload_dir = wp_get_upload_dir();
	$xmlfilename = 'printers-plan-'.$order_id.'.xml';
	$xmlfilepath = $upload_dir['path'].'/'.$xmlfilename;
	$xmlfileurl = $upload_dir['url'].'/'.$xmlfilename;
	if (class_exists('SimpleXMLElement') && $printersplan_options['enable'] == 1 && strlen($printersplan_options['url'])) {
		$order = wc_get_order($order_id);
		$order_items = $order->get_items('line_item');

		$xml = new DOMDocument();
		$xml->encoding = 'utf-8';
		$xml->xmlVersion = '1.0';
		$xml->formatOutput = true;

		// cXML
		$root = $xml->createElement('cXML');
		$root->setAttributeNode(new DOMAttr('payloadID', $order_id));
		$root->setAttributeNode(new DOMAttr('timestamp', date('Y-m-d')));

		// Header
		$Header = $xml->createElement('Header');

		$From_array = array('key' => 'domain', 'value' => $printersplan_options['domain_from'], 'tags' => array('Identity' => $printersplan_options['from']));
		$From = print_products_printers_plan_create_header_element($xml, 'From', $From_array);
		$Header->appendChild($From);

		$To_array = array('key' => 'domain', 'value' => $printersplan_options['domain_to'], 'tags' => array('Identity' => $printersplan_options['to']));
		$To = print_products_printers_plan_create_header_element($xml, 'To', $To_array);
		$Header->appendChild($To);

		$Sender_array = array('key' => 'domain', 'value' => $printersplan_options['domain_from'], 'tags' => array('Identity' => $printersplan_options['from'], 'SharedSecret' => $printersplan_options['shared_secret']));
		$Sender = print_products_printers_plan_create_header_element($xml, 'Sender', $Sender_array);
		$Header->appendChild($Sender);

		// Request
		$Request = $xml->createElement('Request');
		$OrderRequest = $xml->createElement('OrderRequest');
		$OrderRequestHeader = $xml->createElement('OrderRequestHeader');
		$OrderRequestHeader->setAttributeNode(new DOMAttr('orderID', $order_id));
		$OrderRequestHeader->setAttributeNode(new DOMAttr('orderDate', date('Y-m-d H:i:s', strtotime($order->get_date_created()))));
		$OrderRequestHeader->setAttributeNode(new DOMAttr('type', $order->get_status()));

		$ORH_ID = $xml->createElement('ID', $printersplan_options['from']);
		$ORH_ID->setAttributeNode(new DOMAttr('domain', $printersplan_options['domain_from']));
		$OrderRequestHeader->appendChild($ORH_ID);

		$Total = print_products_printers_plan_get_amount($xml, $order, 'Total');
		$OrderRequestHeader->appendChild($Total);

		$Shipping = print_products_printers_plan_get_amount($xml, $order, 'Shipping');
		$OrderRequestHeader->appendChild($Shipping);

		$Tax = print_products_printers_plan_get_amount($xml, $order, 'Tax');
		$OrderRequestHeader->appendChild($Tax);

		$ShipTo = print_products_printers_plan_get_address($xml, $order, 'ShipTo');
		$OrderRequestHeader->appendChild($ShipTo);

		$BillTo = print_products_printers_plan_get_address($xml, $order, 'BillTo');
		$OrderRequestHeader->appendChild($BillTo);

		$Contact = $xml->createElement('Contact');
		$Contact->setAttributeNode(new DOMAttr('role', 'buyer'));
		$Name = $xml->createElement('Name', $order->get_billing_first_name().' '.$order->get_billing_last_name());
		$Email = $xml->createElement('Email', $order->get_billing_email());
		$Phone = $xml->createElement('Phone', $order->get_billing_phone());
		$Contact->appendChild($Name);
		$Contact->appendChild($Email);
		$Contact->appendChild($Phone);
		$OrderRequestHeader->appendChild($Contact);

		$Comments = $xml->createElement('Comments', $order->get_customer_note());
		$OrderRequestHeader->appendChild($Comments);

		$OrderRequest->appendChild($OrderRequestHeader);

		$lnum = 1;
		foreach($order_items as $order_item) {
			$item_id = $order_item->get_id();
			$ItemOut = print_products_printers_plan_get_order_item($xml, $item_id, $order_item, $order);
			$ItemOut->setAttributeNode(new DOMAttr('lineNumber', $lnum));
			$OrderRequest->appendChild($ItemOut);
			$lnum++;
		}

		$Request->appendChild($OrderRequest);

		// ------------------------------------------

		$root->appendChild($Header);
		$root->appendChild($Request);

		// ------------------------------------------

		$xml->appendChild($root);

		$xml->save($xmlfilepath);

		print_products_printers_send_xml($printersplan_options['url'], $xmlfilepath);

		@unlink($xmlfilepath);

		return $xmlfileurl;
	}
}

function print_products_printers_send_xml($url, $xmlfile) {
	$xml = file_get_contents($xmlfile);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function print_products_printers_plan_create_header_element($xml, $ename, $data) {
	$Element = $xml->createElement($ename);
	$Element_Credential = $xml->createElement('Credential');
	$Element_Credential->setAttributeNode(new DOMAttr($data['key'], $data['value']));
	foreach($data['tags'] as $tag_key => $tag_val) {
		$Element_Tag = $xml->createElement($tag_key, $tag_val);
		$Element_Credential->appendChild($Element_Tag);
	}
	$Element->appendChild($Element_Credential);
	return $Element;
}

function print_products_printers_plan_get_address($xml, $order, $ename) {
	if ($ename == 'ShipTo') {
		$address = array(
			'name' => $order->get_shipping_first_name().' '.$order->get_shipping_last_name(),
			'company' => $order->get_shipping_company(),
			'address_1' => $order->get_shipping_address_1(),
			'address_2' => $order->get_shipping_address_2(),
			'city' => $order->get_shipping_city(),
			'state' => $order->get_shipping_state(),
			'postcode' => $order->get_shipping_postcode(),
			'country' => $order->get_shipping_country()
		);
	} else {
		$address = array(
			'name' => $order->get_billing_first_name().' '.$order->get_billing_last_name(),
			'company' => $order->get_billing_company(),
			'address_1' => $order->get_billing_address_1(),
			'address_2' => $order->get_billing_address_2(),
			'city' => $order->get_billing_city(),
			'state' => $order->get_billing_state(),
			'postcode' => $order->get_billing_postcode(),
			'country' => $order->get_billing_country()
		);
	}
	$Element = $xml->createElement($ename);
	$Address = $xml->createElement('Address');
	$Name = $xml->createElement('Name', $address['name']);
	$PostalAddress = $xml->createElement('PostalAddress');
	$PostalAddress->setAttributeNode(new DOMAttr('name', 'default'));

	$CompanyName = $xml->createElement('CompanyName', $address['company']);
	$Street = $xml->createElement('Street', $address['address_1']);
	$Street2 = $xml->createElement('Street', $address['address_2']);
	$City = $xml->createElement('City', $address['city']);
	$State = $xml->createElement('State', $address['state']);
	$PostalCode = $xml->createElement('PostalCode', $address['postcode']);
	$Country = $xml->createElement('Country', $address['country']);
	$Country->setAttributeNode(new DOMAttr('isoCountryCode', $address['country']));

	$PostalAddress->appendChild($CompanyName);
	$PostalAddress->appendChild($Street);
	$PostalAddress->appendChild($Street2);
	$PostalAddress->appendChild($City);
	$PostalAddress->appendChild($State);
	$PostalAddress->appendChild($PostalCode);
	$PostalAddress->appendChild($Country);

	$Address->appendChild($Name);
	$Address->appendChild($PostalAddress);

	$Element->appendChild($Address);

	return $Element;
}

function print_products_printers_plan_get_amount($xml, $order, $type) {
	if ($type == 'Shipping') {
		$amount = $order->get_shipping_total();
	} else if ($type == 'Tax') {
		$amount = $order->get_total_tax();
	} else {
		$amount = $order->get_total();
	}
	$Tag = $xml->createElement($type);
	$Money = $xml->createElement('Money', number_format($amount, 2));
	$Money->setAttributeNode(new DOMAttr('currency', $order->get_currency()));
	$Tag->appendChild($Money);
	return $Tag;
}

function print_products_printers_plan_get_order_item($xml, $item_id, $order_item, $order) {
	global $printersplan_options, $wpdb;
	$product = $order_item->get_product();
	$product_sku = $product->get_sku();

	$attributes = false;
	$uploaded_file = '';
	$item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s", $wpdb->prefix, $item_id));
	if ($item_data) {
		$attributes = print_products_get_product_attributes_list($item_data);
		if (strlen($item_data->additional)) {
			$additional = unserialize($item_data->additional);
			if (isset($additional['sku'])) {
				$product_sku = $additional['sku'];
			}
		}
		$pdf_files = wc_get_order_item_meta($item_id, '_pdf_link', true);
		if (strlen($pdf_files)) {
			$pdf_files = explode(';', $pdf_files);
			$uploaded_file = basename($pdf_files[0]);
		} else if (strlen($item_data->artwork_files)) {
			$artwork_files = unserialize($item_data->artwork_files);
			$uploaded_file = basename($artwork_files[0]);
		}
	}
	$order_item_name = $order_item->get_name();
	if ($printersplan_options['concatenate'] == 1 && strlen($uploaded_file)) {
		$order_item_name = $order_item_name.' - '.$uploaded_file;
	}

	$ItemOut = $xml->createElement('ItemOut');
	$ItemOut->setAttributeNode(new DOMAttr('quantity', $order_item->get_quantity()));

	$ItemID = $xml->createElement('ItemID');
	$SupplierPartID = $xml->createElement('SupplierPartID', $product_sku);
	$ItemID->appendChild($SupplierPartID);
	$ItemOut->appendChild($ItemID);

	$ItemDetail = $xml->createElement('ItemDetail');
	$UnitPrice = $xml->createElement('UnitPrice');
	$Money = $xml->createElement('Money', number_format($order_item->get_total(), 2));
	$Money->setAttributeNode(new DOMAttr('currency', $order->get_currency()));
	$UnitPrice->appendChild($Money);
	$ItemDetail->appendChild($UnitPrice);

	$Description = $xml->createElement('Description');
	$ShortName = $xml->createElement('ShortName', $order_item_name);
	$Description->appendChild($ShortName);
	$ItemDetail->appendChild($Description);

	$UnitOfMeasure = $xml->createElement('UnitOfMeasure', 'LOT');
	$ItemDetail->appendChild($UnitOfMeasure);

	$siteintegration_customer_id = print_products_printers_plan_get_siteintegration_customer_id($order->get_customer_id());
	$Extrinsic = $xml->createElement('Extrinsic', $siteintegration_customer_id);
	$Extrinsic->setAttributeNode(new DOMAttr('name', 'SiteIntegration-CustomerID'));
	$ItemDetail->appendChild($Extrinsic);

	if ($attributes) {
		foreach($attributes as $attribute) {
			if (strlen($attribute['value'])) {
				if ($attribute['name'] == 'custom_attributes') {
					$Extrinsic = $xml->createElement('Extrinsic', str_replace(array('<br>', '</ br>'), '; ', nl2br($attribute['value'])));
					$Extrinsic->setAttributeNode(new DOMAttr('name', 'Custom'));
				} else {
					$Extrinsic = $xml->createElement('Extrinsic', $attribute['value']);
					$Extrinsic->setAttributeNode(new DOMAttr('name', $attribute['name']));
				}
				$ItemDetail->appendChild($Extrinsic);
			}
		}
	}

	$ItemOut->appendChild($ItemDetail);

	return $ItemOut;
}

// add field to user account
add_action('show_user_profile', 'print_products_printers_plan_profile_field');
add_action('edit_user_profile', 'print_products_printers_plan_profile_field');
function print_products_printers_plan_profile_field($profileuser) {
	$printers_plan_customer_id = get_user_meta($profileuser->ID, '_printers_plan_customer_id', true); ?>
	<h3><?php _e('Printers Plan', 'wp2print'); ?></h3>
	<table class="form-table">
		<tr>
			<th><label><?php _e('Printers Plan Customer ID', 'wp2print'); ?></label></th>
			<td>
				<input type="text" name="printers_plan_customer_id" value="<?php echo $printers_plan_customer_id; ?>">
			</td>
		</tr>
	</table>
	<?php
}

add_action('personal_options_update', 'print_products_printers_plan_save_profile_field');
add_action('edit_user_profile_update', 'print_products_printers_plan_save_profile_field');
function print_products_printers_plan_save_profile_field($user_id) {
	update_usermeta($user_id, '_printers_plan_customer_id', $_POST['printers_plan_customer_id']);
}

function print_products_printers_plan_get_siteintegration_customer_id($user_id) {
	$siteintegration_customer_id = get_user_meta($user_id, '_printers_plan_customer_id', true);
	if (!strlen($siteintegration_customer_id)) {
		$user_group = print_products_users_groups_get_user_group($user_id);
		if ($user_group) {
			$options = unserialize($user_group->options);
			if (isset($options['printers_plan_customer_id']) && $options['printers_plan_customer_id']) {
				$siteintegration_customer_id = $options['printers_plan_customer_id'];
			}
		}
	}
	return $siteintegration_customer_id;
}

add_action('wp_loaded', 'print_products_printers_plan_wp_loaded');
function print_products_printers_plan_wp_loaded() {
	if (isset($_GET['hivistaxml'])) {
		$xmlurl = print_products_printers_plan_created_order(1196);
		echo '<a href="'.$xmlurl.'" target="_blank">XML FILE</a>';
		exit;
	}
}
?>