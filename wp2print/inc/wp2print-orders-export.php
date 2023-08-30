<?php
add_filter('woe_get_order_product_fields', 'print_products_orders_export_woe_get_order_product_fields', 11);
function print_products_orders_export_woe_get_order_product_fields($fields) {
	$fields['attributes'] = array(
		'label' => 'Attributes',
		'format' => 'string',
		'colname' => 'Attributes'
	);
	$fields['pdf_files'] = array(
		'label' => 'PDF_Files',
		'format' => 'string',
		'colname' => 'PDF_Files'
	);
	$fields['customer_files'] = array(
		'label' => 'Customer_Files',
		'format' => 'string',
		'colname' => 'Customer_Files'
	);
	return $fields;
}

add_filter('woe_fetch_order', 'print_products_orders_export_woe_fetch_order', 11, 2);
function print_products_orders_export_woe_fetch_order($row, $order) {
	$products = $row['products'];
	if ($products) {
		foreach($products as $item_id => $item_data) {
			$attributes = print_products_orders_export_get_attributes($item_id);
			$pdf_files = wc_get_order_item_meta($item_id, '_pdf_link', true);
			$customer_files = print_products_orders_export_get_artwork_files($item_id);

			$products[$item_id]['attributes'] = $attributes;
			$products[$item_id]['pdf_files'] = $pdf_files;
			$products[$item_id]['customer_files'] = $customer_files;
		}
	}
	$row['products'] = $products;
	return $row;
}

function print_products_orders_export_get_artwork_files($item_id) {
	global $wpdb;
	$artwork_files = '';
	$ppoitem = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s", $wpdb->prefix, $item_id));
	if ($ppoitem) {
		if (strlen($ppoitem->artwork_files)) {
			$artwork_files = unserialize($ppoitem->artwork_files);
			$artwork_files = implode(';', $artwork_files);
		}
	}
	return $artwork_files;
}

function print_products_orders_export_get_attributes($item_id) {
	global $wpdb;
	$attributes = '';
	$item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = %s", $wpdb->prefix, $item_id));
	if ($item_data) {
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
		$attributes = implode("; ", $attributes);
	}
	return $attributes;
}
?>