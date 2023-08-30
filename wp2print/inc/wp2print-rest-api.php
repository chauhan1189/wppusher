<?php
add_filter( 'rest_pre_echo_response', 'wp2print_rest_api_orders');
function wp2print_rest_api_orders($response) {
	if ($response && is_array($response)) {
		if (isset($response['order_key'])) {
			$response['line_items'] = wp2print_rest_api_get_line_items($response['id'], $response['line_items']);
		} else if (isset($response[0]['order_key'])) {
			foreach($response as $okey => $odata) {
				$response[$okey]['line_items'] = wp2print_rest_api_get_line_items($odata['id'], $odata['line_items']);
			}
		}
	}
	return $response;
}

function wp2print_rest_api_get_line_items($order_id, $line_items) {
	global $wpdb;
	$approval_statuses = print_products_orders_proof_get_approval_statuses();
	$approval_status = get_post_meta($order_id, '_approval_status', true);
	foreach($line_items as $ikey => $idata) {
		$item_id = $idata['id'];
		$meta_data = $idata['meta_data'];
		$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));

		$attributes = wp2print_rest_api_get_attributes($item_id, $order_item_data);
		if ($attributes) {
			$line_items[$ikey]['attributes'] = $attributes;
		}

		$download_files = wp2print_rest_api_get_download_files($item_id, $order_item_data);
		if ($download_files) {
			$line_items[$ikey]['download_files'] = $download_files;
		}

		if (strlen($approval_status)) {
			$line_items[$ikey]['proofing_status'] = $approval_statuses[$approval_status];
		}

		$production_status = wp2print_rest_api_get_production_status($item_id);
		if (strlen($production_status)) {
			$line_items[$ikey]['production_status'] = $production_status;
		}

		$vendor_assignment = wp2print_rest_api_get_vendor_assignment($item_id);
		if (strlen($vendor_assignment)) {
			$line_items[$ikey]['vendor_assignment'] = $vendor_assignment;
		}
	}
	return $line_items;
}

function wp2print_rest_api_get_attributes($item_id, $order_item_data) {
	$attributes = '';
	if ($order_item_data) {
		$attributes = array();
		$item_attributes = print_products_get_product_attributes_list($order_item_data);
		if ($item_attributes) {
			foreach($item_attributes as $item_attribute) {
				if ($item_attribute['name'] == 'custom_attributes') {
					$attributes[] = str_replace(array('<br>', '</ br>'), '; ', nl2br($item_attribute['value']));
				} else {
					$attributes[] = $item_attribute['name'].': '.$item_attribute['value'];
				}
			}
		}
	}
	return $attributes;
}

function wp2print_rest_api_get_download_files($item_id, $order_item_data) {
	$download_files = array();
	if ($order_item_data && strlen($order_item_data->artwork_files)) {
		$afiles = unserialize($order_item_data->artwork_files);
		foreach($afiles as $afile) {
			$download_files[] = print_products_get_amazon_file_url($afile);
		}
	}
	$artwork_file_url = wc_get_order_item_meta($item_id, '_artwork_file_url', true);
	if (strlen($artwork_file_url)) {
		$download_files[] = $artwork_file_url;
	}
	$designer_image_link = wc_get_order_item_meta($item_id, '_image_link', true);
	$designer_pdf_link = wc_get_order_item_meta($item_id, '_pdf_link', true);
	if (strlen($designer_image_link)) {
		$designer_image_link = explode(',', $designer_image_link);
		$download_files = array_merge($download_files, $designer_image_link);
	}
	if (strlen($designer_pdf_link)) {
		$designer_pdf_link = explode(',', $designer_pdf_link);
		$download_files = array_merge($download_files, $designer_pdf_link);
	}
	if (is_array($download_files) && count($download_files) == 1) {
		$download_files = $download_files[0];
	}
	return $download_files;
}

function wp2print_rest_api_get_production_status($item_id) {
	$production_status = '';
	$item_status = wc_get_order_item_meta($item_id, '_item_status', true);
	if (strlen($item_status)) {
		$status = print_products_oistatus_get_status_by_slug($item_status);
		$production_status = $status['name'];
	}
	return $production_status;
}

function wp2print_rest_api_get_vendor_assignment($item_id) {
	global $wpdb;
	$vendor_assignment = '';
	$item_vendor = wc_get_order_item_meta($item_id, '_item_vendor', true);
	if ($item_vendor) {
		$vendor_data = $wpdb->get_row(sprintf("SELECT * FROM %susers WHERE ID = %s", $wpdb->prefix, $item_vendor));
		if ($vendor_data) {
			$vendor_assignment = $vendor_data->display_name.' ('.$vendor_data->user_email.')';
		}
	}
	return $vendor_assignment;
}
?>