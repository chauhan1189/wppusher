<?php
/*add_action('wp_loaded', 'zapier_test_action');
function zapier_test_action() {
	if (isset($_GET['zapier']) && $_GET['zapier'] == 'test') {
		$zapier_data = get_option('zapier_data');
		var_dump($zapier_data);
		exit;
	}
}*/

add_filter('wc_zapier_data', 'print_products_zapier_wc_zapier_data');
function print_products_zapier_wc_zapier_data($data) {
	global $wpdb;
	$downloadable_files = array();
	$order = wc_get_order($data['id']);
	if ($order) {
		$oikey = 0;
		$customer_id = $order->get_customer_id();
		foreach($order->get_items() as $item_id => $order_item) {
			$item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s", $wpdb->prefix, $item_id));
			if ($item_data) {
				$additional = unserialize($item_data->additional);
				$attributes = print_products_zapier_get_attributes($item_data);
				$customer_files = print_products_zapier_get_customer_files($item_id, $item_data);
				if (strlen($attributes)) {
					$data['line_items'][$oikey]->name .= ': '.$attributes;
				}
				if ($customer_files && is_array($customer_files)) {
					$downloadable_files = array_merge($downloadable_files, $customer_files);
				}
				if (strlen($additional['sku'])) {
					$data['line_items'][$oikey]->sku = $additional['sku'];
				}
			}
			$pdf_files = wc_get_order_item_meta($item_id, '_pdf_link', true);
			if (strlen($pdf_files)) {
				$data['line_items'][$oikey]->PDF_Files = $pdf_files;
			}
			$oikey++;
		}
		if (count($downloadable_files)) {
			$data['downloadable_files'] = implode("\n", $downloadable_files);
		}
		$accounting_id = print_products_users_groups_get_user_accounting_id($customer_id);
		if (strlen($accounting_id)) {
			$data['user_id'] = $accounting_id;
		}
	}
	//update_option('zapier_data', $data);
	return $data;
}

function print_products_zapier_get_attributes($item_data) {
	$attributes = array();
	$item_attributes = print_products_get_product_attributes_list($item_data);
	if ($item_attributes) {
		foreach($item_attributes as $item_attribute) {
			if ($item_attribute['name'] == 'custom_attributes') {
				$attributes[] = str_replace(array('<br>', '</ br>'), '; ', nl2br($item_attribute['value']));
			} else {
				$attributes[] = $item_attribute['name'].': '.$item_attribute['value'];
			}
		}
	}
	return implode("; ", $attributes);
}

function print_products_zapier_get_customer_files($item_id, $item_data) {
	$artwork_files = '';
	if (strlen($item_data->artwork_files)) {
		$artwork_files = unserialize($item_data->artwork_files);
	}
	$designer_images = wc_get_order_item_meta($item_id, '_image_link', true);
	if (strlen($designer_images)) {
		$artwork_files = explode(',', $designer_images);
	}
	return $artwork_files;
}
?>