<?php
eval(base64_decode('ZnVuY3Rpb24gYTEyKCl7aWYoZ2V0X29wdGlvbigncHJpbnRfcHJvZHVjdHNfbGljZW5zZV9hY3RpdmF0aW9uJykgJiYgcHJpbnRfcHJvZHVjdHNfY2hlY2tfbWQoZXhwbG9kZSgnOicsZ2V0X29wdGlvbigncHJpbnRfcHJvZHVjdHNfbGljZW5zZV9hY3RpdmF0aW9uJykpKSl7cmV0dXJuIHRydWU7fXJldHVybiBmYWxzZTt9'));

function print_products_check_md($sarray) {
	$home_url = $_SERVER['SERVER_NAME'];
	if (md5($sarray[0].$home_url) == $sarray[1]) {
		return true;
	}
}

function print_products_get_product_types() {
	$product_types = array(
		'area'  => __('Area product', 'wp2print'),
		'fixed' => __('Fixed size product', 'wp2print'),
		'book'  => __('Book product', 'wp2print')
	);
	$license_type = print_products_license_type();
	if ($license_type == 'aec_only') {
		$product_types = array(
			'aec' => __('AEC % Coverage product', 'wp2print'),
			'aecbwc' => __('AEC B/W or Color product', 'wp2print'),
			'aecsimple' => __('AEC Simple product', 'wp2print')
		);
	} else if ($license_type == 'all') {
		$product_types = array(
			'area'  => __('Area product', 'wp2print'),
			'fixed' => __('Fixed size product', 'wp2print'),
			'book'  => __('Book product', 'wp2print'),
			'aec'   => __('AEC % Coverage product', 'wp2print'),
			'aecbwc' => __('AEC B/W or Color product', 'wp2print'),
			'aecsimple' => __('AEC Simple product', 'wp2print')
		);
	}
	return $product_types;
}

function print_products_is_wp2print_type($type) {
	$product_types = array('area', 'fixed', 'book', 'aec', 'aecbwc', 'aecsimple', 'custom');
	if (in_array($type, $product_types)) {
		return true;
	}
	return false;
}

if (function_exists('get_option')) {
	$print_products_license_activation = get_option('print_products_license_activation');
}
function print_products_license_type() {
	global $print_products_license_activation;
	$ladata = explode(':', $print_products_license_activation);
	if (substr($ladata[0], -7) == 'h9C2hWe') {
		return 'aec_only';
	} else if (substr($ladata[0], -7) == 'd7vh8Rw') {
		return 'all';
	}
	return 'except_aec';
}

function print_products_check_license_expiry() {
	$check_license_expiry = false;
	$license_activation = explode(':', get_option('print_products_license_activation'));
	$data = array ();
	$data['secret_key'] = PRINT_PRODUCTS_API_SECRET_KEY;
	$data['slm_action'] = 'slm_check';
	$data['license_key'] = $license_activation[0];
	$data['item_reference'] = 'wp2print plugin';

	// send data to activation server
	$ch = curl_init(PRINT_PRODUCTS_API_SERVER_URL);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = json_decode(curl_exec($ch));
	$response_result = $response->result;
	if ($response_result == 'success') {
		$check_license_expiry = true;
		$date_expiry = $response->date_expiry;
		if (strlen($date_expiry)) {
			$nowdate = strtotime(current_time('mysql'));
			$date_expiry = strtotime($date_expiry);
			if ($date_expiry < $nowdate) {
				$check_license_expiry = false;
			}
		}
	}
	return $check_license_expiry;
}

function print_products_is_allow_aec() {
	$license_type = print_products_license_type();
	if ($license_type != 'except_aec') {
		return true;
	}
	return false;
}

function print_products_get_type($product_id) {
	global $print_products_settings;
	if ($terms = wp_get_object_terms($product_id, 'product_type')) {
		return sanitize_title(current($terms)->slug);
	}
}

function print_products_price_matrix_get_types() {
	return array(0 => __('Printing matrix', 'wp2print'), 1 => __('Finishing matrix', 'wp2print'));
}

function print_products_get_matrix_num_type($mtype_id) {
	global $wpdb;
	return (int)$wpdb->get_var(sprintf("SELECT num_type FROM %sprint_products_matrix_types WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
}

function print_products_get_matrix_ltext_attr($mtype_id) {
	global $wpdb;
	return (int)$wpdb->get_var(sprintf("SELECT ltext_attr FROM %sprint_products_matrix_types WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
}

function print_products_get_matrix_ltext($aterms, $lattr) {
	if (strlen($aterms) && $lattr) {
		$aterms = explode('-', $aterms);
		foreach($aterms as $aterm) {
			$adata = explode(':', $aterm);
			if ((int)$adata[0] == $lattr) {
				return $adata[1];
			}
		}
	}
}

function print_products_get_num_types($sunit = '') {
	$dimension_unit = print_products_get_dimension_unit();
	$square_unit = print_products_get_square_unit($dimension_unit);
	if (strlen($sunit)) { $square_unit = $sunit; }
	$perimeter_unit = str_replace('&#178;', '', $square_unit);
	return array(
		0 => __('Quantity', 'wp2print'),
		1 => __('Total Pages', 'wp2print'),
		2 => __('Total Area', 'wp2print').' ('.$square_unit.')',
		3 => __('Total Perimeter', 'wp2print').' ('.$perimeter_unit.')',
		4 => __('Total Width', 'wp2print'),
		5 => __('Count of Letters', 'wp2print'),
		6 => __('Quantity Mailed', 'wp2print')
	);
}

function print_products_get_num_type_labels($sunit = '') {
	$dimension_unit = print_products_get_dimension_unit();
	$square_unit = print_products_get_square_unit($dimension_unit);
	if (strlen($sunit)) { $square_unit = $sunit; }
	$perimeter_unit = str_replace('&#178;', '', $square_unit);
	return array(
		0 => __('Quantities', 'wp2print'),
		1 => __('Total Pages', 'wp2print'),
		2 => __('Total Areas', 'wp2print').' ('.$square_unit.')',
		3 => __('Total Perimeters', 'wp2print').' ('.$perimeter_unit.')',
		4 => __('Total Widths', 'wp2print'),
		5 => __('Count of Letters', 'wp2print'),
		6 => __('Quantity Mailed', 'wp2print')
	);
}

function print_products_get_weight_unit() {
	return get_option('woocommerce_weight_unit');
}

function print_products_get_area_units() {
	$dimension_unit = print_products_get_dimension_unit();
	if ($dimension_unit == 'ft') {
		return array('ft&#178;', 'in&#178;');
	} else if ($dimension_unit == 'in') {
		return array('in&#178;', 'ft&#178;');
	} else {
		return array('m&#178;', 'cm&#178;');
	}
}

function print_products_get_area_unit($aunit) {
	$area_units = print_products_get_area_units();
	if (isset($area_units[$aunit])) {
		return $area_units[$aunit];
	} else {
		return $area_units[0];
	}
}

function print_products_get_area_size($width, $height, $dimension_unit, $area_unit) {
	if ($width > 0 && $height > 0) {
		if ($dimension_unit == 'in') {
			if ($area_unit == 1) {
				$width = $width * 0.0833;
				$height = $height * 0.0833;
			}
		} else if ($dimension_unit == 'ft') {
			if ($area_unit == 1) { // in in2
				$width = $width / 0.0833;
				$height = $height / 0.0833;
			}
		} else {
			if ($area_unit == 1) {
				if ($dimension_unit == 'mm') {
					$width = $width / 10;
					$height = $height / 10;
				} else if ($dimension_unit == 'm') {
					$width = $width * 100;
					$height = $height * 100;
				}
			} else {
				if ($dimension_unit == 'mm') {
					$width = $width / 1000;
					$height = $height / 1000;
				} else if ($dimension_unit == 'cm') {
					$width = $width / 100;
					$height = $height / 100;
				}
			}
		}
	}
	return array($width, $height);
}

function print_products_get_dimension_unit() {
	return get_option('woocommerce_dimension_unit');
}

function print_products_get_aec_dimension_unit() {
	global $print_products_plugin_aec;
	$aec_dimensions_unit = print_products_get_dimension_unit();
	if (strlen($print_products_plugin_aec['aec_dimensions_unit'])) {
		$aec_dimensions_unit = $print_products_plugin_aec['aec_dimensions_unit'];
	}
	return $aec_dimensions_unit;
}

function print_products_get_square_unit($unit = '') {
	if (!$unit) { $unit = print_products_get_dimension_unit(); }
	if ($unit == 'in' || $unit == 'ft') {
		return 'ft&#178;';
	}
	return 'm&#178;';
}

function print_products_get_area_square_unit($dimension_unit) {
	switch ($dimension_unit) {
		case 'mm':
		case 'cm':
		case 'm':
			return 'm';
		break;
		case 'ft':
		case 'in':
		case 'yd':
			return 'in';
		break;
	}
}

$terms_names = array();
$attribute_names = array();
$attribute_slugs = array();
$attribute_types = array();
$attribute_imgs = array();
$attribute_help_texts = array();
$attribute_sorts = array();
function print_products_price_matrix_attr_names_init($attributes = '') {
	global $attribute_names, $attribute_slugs, $attribute_types, $attribute_imgs, $attribute_help_texts, $attribute_sorts, $terms_names, $wpdb;
	if (!IS_WOOCOMMERCE) { return; }
	if (!$attributes) {
		$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
	}
	if ($attributes) {
		$taxs = array();
		foreach($attributes as $attribute) {
			$taxs[] = 'pa_'.$attribute->attribute_name;
			$attribute_names[$attribute->attribute_id] = wc_attribute_label('pa_'.$attribute->attribute_name);
			$attribute_slugs[$attribute->attribute_id] = $attribute->attribute_name;
			$attribute_types[$attribute->attribute_id] = $attribute->attribute_type;
			$attribute_imgs[$attribute->attribute_id] = $attribute->attribute_img;
			$attribute_help_texts[$attribute->attribute_id] = $attribute->attribute_help_text;
			$attribute_sorts[$attribute->attribute_id] = (int)$attribute->attribute_order;
		}
		$attr_terms = $wpdb->get_results(sprintf("SELECT t.*, tt.taxonomy FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.term_order, t.name", $wpdb->prefix, $wpdb->prefix, implode("','", $taxs)));
		if ($attr_terms) {
			foreach($attr_terms as $attr_term) {
				$terms_names[$attr_term->term_id] = $attr_term->name;
			}
		}
	}
}

function print_products_price_matrix_attr_names($pmtattributes) {
	global $attribute_names;
	$price_matrix_attr_names = array();
	if (count($pmtattributes)) {
		foreach($pmtattributes as $pmtattribute) {
			$price_matrix_attr_names[] = $attribute_names[$pmtattribute];
		}
	}
	return $price_matrix_attr_names;
}

function print_products_get_attribute_terms($aterms) {
	global $terms_names;
	$attribute_terms = array();
	foreach($terms_names as $tid => $tname) {
		if (in_array($tid, $aterms)) {
			$attribute_terms[$tid] = $tname;
		}
	}
	return $attribute_terms;
}

function print_products_get_terms_names($aterms) {
	global $wpdb;
	$terms_names = array();
	$attr_terms = $wpdb->get_results(sprintf("SELECT * FROM %sterms WHERE term_id IN (%s)", $wpdb->prefix, implode(",", $aterms)));
	if ($attr_terms) {
		foreach($attr_terms as $attr_term) {
			$terms_names[$attr_term->term_id] = $attr_term->name;
		}
	}
	return $terms_names;
}

function print_products_sort_attribute_terms($attr_terms) {
	$sorted_aterms = array();
	if ($attr_terms) {
		foreach($attr_terms as $attr_id => $aterms) {
			$attr_terms = array();
			if (is_array($aterms)) {
				$aterms = print_products_get_attribute_terms($aterms);
				foreach($aterms as $term_id => $term_name) {
					$attr_terms[] = $term_id;
				}
			}
			$sorted_aterms[$attr_id] = $attr_terms;
		}
	}
	return $sorted_aterms;
}

function print_products_price_matrix_get_array($mtattributes, $aterms) {
	$matrix_array = array();
	$mattr_total = count($mtattributes);
	$attr_id = $mtattributes[0];
	$terms_ids = $aterms[$attr_id];
	foreach($terms_ids as $terms_id) {
		if ($mattr_total > 1) {
			$attr_id2 = $mtattributes[1];
			$terms_ids2 = $aterms[$attr_id2];
			foreach($terms_ids2 as $terms_id2) {
				if ($mattr_total > 2) {
					$attr_id3 = $mtattributes[2];
					$terms_ids3 = $aterms[$attr_id3];
					foreach($terms_ids3 as $terms_id3) {
						if ($mattr_total > 3) {
							$attr_id4 = $mtattributes[3];
							$terms_ids4 = $aterms[$attr_id4];
							foreach($terms_ids4 as $terms_id4) {
								if ($mattr_total > 4) {
									$attr_id5 = $mtattributes[4];
									$terms_ids5 = $aterms[$attr_id5];
									foreach($terms_ids5 as $terms_id5) {
										$matrix_array[] = array($terms_id, $terms_id2, $terms_id3, $terms_id4, $terms_id5);
									}
								} else {
									$matrix_array[] = array($terms_id, $terms_id2, $terms_id3, $terms_id4);
								}
							}
						} else {
							$matrix_array[] = array($terms_id, $terms_id2, $terms_id3);
						}
					}
				} else {
					$matrix_array[] = array($terms_id, $terms_id2);
				}
			}
		} else {
			$matrix_array[] = array($terms_id);
		}
	}
	return $matrix_array;
}

function print_products_price_finishing_matrix_get_array($mtattributes, $aterms) {
	global $print_products_settings;

	$matrix_array = array();
	if (count($mtattributes)) {
		$mattr_total = count($mtattributes);
		$attr_id = $mtattributes[0];
		$terms_ids = $aterms[$attr_id];
		if ($terms_ids && count($terms_ids)) {
			foreach($terms_ids as $terms_id) {
				if (count($mtattributes) > 1) {
					for ($a=1; $a<count($mtattributes); $a++) {
						$sub_attr_id = $mtattributes[$a];
						if ($aterms[$sub_attr_id]) {
							$sub_terms_ids = $aterms[$sub_attr_id];
							foreach($sub_terms_ids as $sub_terms_id) {
								$matrix_array[] = array($terms_id, $sub_terms_id);
							}
						} else {
							$matrix_array[] = array($terms_id, $sub_attr_id);
						}
					}
				} else {
					$matrix_array[] = $terms_id;
				}
			}
		}
	}
	return $matrix_array;
}

function print_products_get_matrix_numbers($num, $mtype_id) {
	global $wpdb;
	$lastnum = $num;
	$matrix_numbers = array(0, 0);
	$numbers = explode(',', $wpdb->get_var(sprintf("SELECT numbers FROM %sprint_products_matrix_types WHERE mtype_id = %s", $wpdb->prefix, $mtype_id)));
	if ($num > 0 && $numbers) {
		for ($i=0; $i<count($numbers); $i++) {
			$anumb = (int)$numbers[$i];
			if ($num < $anumb) {
				return array($lastnum, $anumb);
			} else if ($num == $anumb) {
				return array($anumb, $anumb);
			}
			$lastnum = $anumb;
		}
		if (count($numbers) == 1) {
			$matrix_numbers = array($numbers[0], $lastnum);
		} else {
			$matrix_numbers = array($numbers[count($numbers) - 2], $lastnum);
		}
	}
	return $matrix_numbers;
}

function print_products_get_matrix_price_aterms($aterms, $attribute_types) {
	$price_aterms = ''; $patsep = '';
	$aterms_array = explode('-', $aterms);
	if ($aterms_array) {
		foreach($aterms_array as $atline) {
			$atline_array = explode(':', $atline);
			if ($attribute_types[$atline_array[0]] != 'text') {
				$price_aterms .= $patsep . $atline;
				$patsep = '-';
			}
		}
	}
	return $price_aterms;
}

function print_products_get_matrix_price($mtype_id, $mval, $nmb, $nums) {
	global $wpdb;
	$pmatrix = array();
	$print_products_matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
	if ($print_products_matrix_prices) {
		foreach($print_products_matrix_prices as $print_products_matrix_price) {
			$pmkey = $print_products_matrix_price->aterms.'-'.$print_products_matrix_price->number;
			$pmatrix[$pmkey] = $print_products_matrix_price->price;
		}
	}
	return print_products_get_mprice($mval, $pmatrix, $nmb, $nums);
}

function print_products_get_mprice($mval, $pmatrix, $nmb, $nums) {
	$matrix_price = 0;
	$min_nmb = $nums[0];
	$max_nmb = $nums[1];
	if ($nmb == $min_nmb && $nmb < $max_nmb) {
		$mval = $mval . '-' . $max_nmb;
		if ($pmatrix[$mval]) {
			$matrix_price = ($pmatrix[$mval] / $max_nmb) * $nmb;
		}
	} else if ($nmb == $min_nmb && $nmb == $max_nmb) {
		$mval = $mval . '-' . $nmb;
		if ($pmatrix[$mval]) {
			$matrix_price = $pmatrix[$mval];
		}
	} else if ($nmb > $min_nmb && $nmb < $max_nmb) {
		$min_mval = $mval . '-' . $min_nmb;
		$max_mval = $mval . '-' . $max_nmb;
		if ($pmatrix[$min_mval] && $pmatrix[$max_mval]) {
			$matrix_price = $pmatrix[$min_mval] + ($nmb - $min_nmb) * ($pmatrix[$max_mval] - $pmatrix[$min_mval]) / ($max_nmb - $min_nmb);
		}
	} else if ($nmb > $min_nmb && $nmb > $max_nmb) {
		$min_mval = $mval . '-' . $min_nmb;
		$max_mval = $mval . '-' . $max_nmb;
		if ($pmatrix[$min_mval] && $pmatrix[$max_mval]) {
			if ($min_nmb == $max_nmb) {
				$matrix_price = $pmatrix[$max_mval] * $nmb;
			} else {
				$matrix_price = $pmatrix[$max_mval] + ($nmb - $max_nmb) * ($pmatrix[$max_mval] - $pmatrix[$min_mval]) / ($max_nmb - $min_nmb);
			}
		}
	}
	return $matrix_price;
}

function print_products_get_mailing_price($product_id, $mval, $nmb, $nums) {
	$mailing_price = 0;
	$mailing_matrix = get_post_meta($product_id, '_mailing_attributes', true);
	if ($mailing_matrix) {
		$mailing_price = print_products_get_mprice($mval, $mailing_matrix, $nmb, $nums);
	}
	return $mailing_price;
}

function print_products_get_numb_price($price, $nmb_val, $nmb) {
	if ($nmb_val != $nmb) {
		if ($nmb == 1 && $nmb_val < 10) {
			$price = $price * $nmb_val;
		} else {
			$price = ($price / $nmb) * $nmb_val;
		}
	}
	return $price;
}

function print_products_get_matrix_title($mtype_id) {
	global $wpdb;
	return $wpdb->get_var(sprintf("SELECT title FROM %sprint_products_matrix_types WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
}

function print_products_avals($avals) {
	$avals = str_replace('{dc}', '-', $avals);
	$avals = str_replace('{cc}', ':', $avals);
	$avals = str_replace('{vc}', '|', $avals);
	return $avals;
}

function print_products_get_attributes_vals($product_attributes, $ptype, $attribute_labels, $attribute_display = array()) {
	global $attribute_names, $attribute_types, $terms_names, $print_products_settings;
	$size_attribute = $print_products_settings['size_attribute'];
	$printing_attributes = unserialize($print_products_settings['printing_attributes']);
	if (!$attribute_names) { print_products_price_matrix_attr_names_init(); }
	$attr_terms = array();
	$pqnmb = 0;
	$aprefix = '';
	if ($ptype == 'book') {
		foreach($product_attributes as $akey => $product_attribute) {
			$aarray = explode(':', $product_attribute);
			if ($aarray[0] == $size_attribute) {
				$attr_terms[] = print_products_attribute_label($aarray[0], $attribute_labels, $attribute_names[$aarray[0]]).': <strong>'.$terms_names[$aarray[1]].'</strong>';
				unset($product_attributes[$akey]);
			}
		}
	}
	foreach($product_attributes as $product_attribute) {
		$adisplay = true;
		$aarray = explode(':', $product_attribute);
		$aarray[1] = print_products_avals($aarray[1]);
		if (!is_admin() && $attribute_display && is_array($attribute_display) && isset($attribute_display[$aarray[0]])) {
			if ($attribute_display[$aarray[0]] == 1) {
				$adisplay = false;
			}
		}
		if ($adisplay) {
			if (substr($aarray[0], 0, 3) == 'pq|') {
				$aprefix = str_replace('pq|', '', $aarray[0]);
				$attr_terms[] = $aprefix.' '.print_products_attribute_label('pquantity', $attribute_labels, __('Pages Quantity', 'wp2print')).': <strong>'.$aarray[1].'</strong>';
				$pqnmb++;
			} else if ($aarray[0] == 'qm') {
				$attr_terms[] = __('Quantity mailed', 'wp2print').': <strong>'.$aarray[1].'</strong>';
			} else {
				$attr_line = print_products_attribute_label($aarray[0], $attribute_labels, $attribute_names[$aarray[0]]).': <strong>'.$terms_names[$aarray[1]].'</strong>';
				if (strlen($aprefix) && in_array($aarray[0], $printing_attributes)) {
					$attr_line = $aprefix.' '.print_products_attribute_label($aarray[0], $attribute_labels, $attribute_names[$aarray[0]]).': <strong>'.$terms_names[$aarray[1]].'</strong>';
				}
				if ($attribute_types[$aarray[0]] == 'text') {
					$attr_line = print_products_attribute_label($aarray[0], $attribute_labels, $attribute_names[$aarray[0]]).': <strong>'.$aarray[1].'</strong>';
				}
				$attr_terms[] = $attr_line;
			}
		}
	}
	return $attr_terms;
}

function print_products_get_product_attributes_list($item_data) {
	$attributes = array();
	if ($item_data) {
		$dimension_unit = print_products_get_dimension_unit();
		$aec_dimension_unit = print_products_get_aec_dimension_unit();
		$attribute_labels = (array)get_post_meta($item_data->product_id, '_attribute_labels', true);
		$attribute_display = (array)get_post_meta($item_data->product_id, '_attribute_display', true);
		$additional = unserialize($item_data->additional);
		if ($item_data->product_type == 'area' && $item_data->additional) {
			$attributes[] = array('name' => print_products_attribute_label('width', $attribute_labels, __('Width', 'wp2print')).' ('.$dimension_unit.')', 'value' => $additional['width']);
			$attributes[] = array('name' => print_products_attribute_label('height', $attribute_labels, __('Height', 'wp2print')).' ('.$dimension_unit.')', 'value' => $additional['height']);
		}
		if (($item_data->product_type == 'aec' || $item_data->product_type == 'aecbwc' || $item_data->product_type == 'aecsimple') && $item_data->additional) {
			$project_name = $additional['project_name'];
			if ($project_name) {
				$attributes[] = array('name' => __('Project Name', 'wp2print'), 'value' => $project_name);
			}
		}
		$product_attributes = unserialize($item_data->product_attributes);
		if ($product_attributes) {
			$product_attributes = print_products_quantity_mailed_product_attributes($product_attributes, $additional);
			$attr_terms = print_products_get_attributes_vals($product_attributes, $item_data->product_type, $attribute_labels, $attribute_display);
			if ($attr_terms) {
				foreach($attr_terms as $attr_term_line) {
					$aline_array = explode(':', $attr_term_line);
					$attributes[] = array('name' => trim($aline_array[0]), 'value' => trim(strip_tags($aline_array[1])));
				}
			}
		}
		if (($item_data->product_type == 'aec' || $item_data->product_type == 'aecsimple') && $item_data->additional) {
			$total_area = $additional['total_area'];
			$total_pages = $additional['total_pages'];
			if ($total_area) {
				$attributes[] = array('name' => __('Total Area', 'wp2print'), 'value' => number_format($total_area, 2).' '.$aec_dimension_unit.'<sup>2</sup>');
			}
			if ($total_pages) {
				$attributes[] = array('name' => __('Total Pages', 'wp2print'), 'value' => $total_pages);
			}
		} else if ($item_data->product_type == 'aecbwc' && $item_data->additional) {
			$total_area = $additional['total_area'];
			$total_pages = $additional['total_pages'];
			$area_bw = $additional['area_bw'];
			$pages_bw = $additional['pages_bw'];
			$area_cl = $additional['area_cl'];
			$pages_cl = $additional['pages_cl'];
			if ($total_area) {
				$attributes[] = array('name' => __('Total Area', 'wp2print'), 'value' => number_format($total_area, 2).' '.$aec_dimension_unit.'<sup>2</sup>');
			}
			if ($total_pages) {
				$attributes[] = array('name' => __('Total Pages', 'wp2print'), 'value' => $total_pages);
			}
			if ($area_bw) {
				$attributes[] = array('name' => __('Area B/W', 'wp2print'), 'value' => number_format($area_bw, 2).' '.$aec_dimension_unit.'<sup>2</sup>');
			}
			if ($pages_bw) {
				$attributes[] = array('name' => __('Pages B/W', 'wp2print'), 'value' => $pages_bw);
			}
			if ($area_cl) {
				$attributes[] = array('name' => __('Area Color', 'wp2print'), 'value' => number_format($area_cl, 2).' '.$aec_dimension_unit.'<sup>2</sup>');
			}
			if ($pages_cl) {
				$attributes[] = array('name' => __('Pages Color', 'wp2print'), 'value' => $pages_cl);
			}
		} else if ($item_data->product_type == 'custom' && $item_data->additional) {
			$additional = unserialize($item_data->additional);
			if (strlen($additional['attributes'])) {
				$attributes[] = array('name' => 'custom_attributes', 'value' => $additional['attributes']);
			}
		}
		if (strlen($additional['pspeed']) && strlen($additional['pspeed_label'])) {
			$pspeed_label = explode(';', $additional['pspeed_label']);
			$attributes[] = array('name' => $pspeed_label[0], 'value' => $pspeed_label[1]);
		}
	}
	return $attributes;
}

function print_products_product_attributes_list_html($item_data) {
	global $order_vendor_item;
	$attributes = print_products_get_product_attributes_list($item_data);
	if (strlen($item_data->product_attributes)) {
		$product_attributes = unserialize($item_data->product_attributes);
	}
	if ($attributes) { ?>
		<div class="print-products-area">
			<ul class="product-attributes-list">
				<?php foreach($attributes as $attribute) {
					if ($attribute['name'] == 'custom_attributes') {
						echo '<li>'.nl2br($attribute['value']).'</li>';
					} else {
						echo '<li>'.$attribute['name'].': <strong>'.$attribute['value'].'</strong></li>';
					}
				} ?>
			</ul>
			<?php if (is_cart() && $product_attributes) { ?>
				<div class="modify-attr"><a href="<?php echo print_products_get_modify_url($item_data->product_id, $item_data->cart_item_key); ?>" class="button"><?php _e('Modify', 'wp2print'); ?></a></div>
			<?php } ?>
		</div>
		<?php if ($order_vendor_item) {
			if (strlen($item_data->artwork_files)) {
				$artwork_files = unserialize($item_data->artwork_files);
				foreach($artwork_files as $artwork_file) {
					echo '<a href="'.$artwork_file.'">'.basename($artwork_file).'</a><br>';
				}
			}
		}
	}
}

function print_products_quantity_mailed_product_attributes($product_attributes, $additional) {
	global $print_products_settings;
	$postage_attribute = $print_products_settings['postage_attribute'];
	if (isset($additional['quantity_mailed']) && (int)$additional['quantity_mailed']) {
		$t_product_attributes = $product_attributes;
		$product_attributes = array();
		foreach($t_product_attributes as $t_product_attribute) {
			$product_attributes[] = $t_product_attribute;
			$paarray = explode(':', $t_product_attribute);
			if ($paarray[0] == $postage_attribute) {
				$product_attributes[] = 'qm:'.$additional['quantity_mailed'];
			}
		}
	}
	return $product_attributes;
}

function print_products_product_thumbs_list_html($item_data) {
	global $print_products_plugin_options;
	if ($item_data) {
		$artwork_files = unserialize($item_data->artwork_files);
		$artwork_thumbs = unserialize($item_data->artwork_thumbs);
		if ($artwork_files) { ?>
			<div class="print-products-area">
				<ul class="product-attributes-list">
					<?php if ($item_data->product_type == 'aec' || $item_data->product_type == 'aecbwc' || $item_data->product_type == 'aecsimple') { ?>
						<li><?php _e('Files', 'wp2print'); ?>:</li>
						<?php foreach($artwork_files as $af_key => $artwork_file) {
							$artwork_thumb = $artwork_thumbs[$af_key];
							echo '<li>'.print_products_artwork_file_html($artwork_file, $artwork_thumb, 'download').'</li>';
						} ?>
					<?php } else { ?>
						<li><?php _e('Artwork Files', 'wp2print'); ?>:</li>
						<li><ul class="product-artwork-files-list ftp<?php echo $print_products_plugin_options['dfincart']; ?>">
							<?php foreach($artwork_files as $af_key => $artwork_file) {
								$artwork_thumb = $artwork_thumbs[$af_key];
								echo '<li>'.print_products_artwork_file_html($artwork_file, $artwork_thumb, $item_data->item_id).'</li>';
							} ?>
						</ul></li>
					<?php } ?>
				</ul>
			</div>
		<?php }
	}
}

function print_products_product_modify_list_html($item_id, $item_data) {
	if ($item_data) {
		$artwork_files = unserialize($item_data->artwork_files);
		if ($artwork_files) { ?>
			<div class="print-products-area">
				<ul class="product-attributes-list">
					<?php if ($item_data->product_type == 'aec' || $item_data->product_type == 'aecbwc' || $item_data->product_type == 'aecsimple') { ?>
						<li><?php _e('Files', 'wp2print'); ?>:</li>
					<?php } else { ?>
						<li><?php _e('Artwork Files', 'wp2print'); ?>:</li>
					<?php } ?>
					<li><ul class="product-artwork-files-list oi-files-list">
						<?php foreach($artwork_files as $af_key => $artwork_file) {
							echo '<li><i class="i-check"></i> <a href="'.print_products_get_amazon_file_url($artwork_file).'" title="'.__('Download', 'wp2print').'" target="_blank">'.basename($artwork_file).'</a><span class="af-replace"> - <a href="#replace" class="afile-replace" onclick="return order_artwork_replace('.$item_id.', '.$af_key.');"><span>'.__('Replace file', 'wp2print').'</span></a></span></li>';
						} ?>
					</ul></li>
				</ul>
			</div>
		<?php } else { ?>
			<div class="print-products-area">
				<ul class="product-attributes-list">
					<li><a href="#add-file" onclick="return order_artwork_replace(<?php echo $item_id; ?>, 0);"><?php _e('Add artwork file', 'wp2print'); ?></a></li>
				</ul>
			</div>
		<?php }
	}
}

function print_products_allow_modify_files($item_status) {
	global $print_products_fmodification_options;
	$allowed_statuses = array();
	if (!$print_products_fmodification_options) { $print_products_fmodification_options = get_option("print_products_fmodification_options"); }
	if (isset($print_products_fmodification_options['statuses']) && is_array($print_products_fmodification_options['statuses'])) {
		$allowed_statuses = $print_products_fmodification_options['statuses'];
	}
	return in_array($item_status, $allowed_statuses);
}

function print_products_get_modify_url($product_id, $cart_item_key) {
	$modify_url = get_permalink($product_id);
	if (strpos($modify_url, '?')) {
		$modify_url .= '&';
	} else {
		$modify_url .= '?';
	}
	$modify_url .= 'modify='.$cart_item_key;
	return $modify_url;
}

function print_products_artwork_files_html($artwork_files, $prod_cart_data) {
	global $print_products_plugin_options;
	$artwork_thumbs = unserialize($prod_cart_data->artwork_thumbs);
	?>
	<div class="print-products-area">
		<?php if ($prod_cart_data->product_type == 'aec' || $prod_cart_data->product_type == 'aecbwc' || $prod_cart_data->product_type == 'aecsimple') { ?>
			<?php if (count($artwork_files) == 1) { echo __('File', 'wp2print').': '; } else { echo __('Files', 'wp2print').':'; } ?><br />
		<?php } ?>
		<ul class="artwork-files-list ftp<?php echo $print_products_plugin_options['dfincart']; ?>">
			<?php foreach($artwork_files as $af_key => $artwork_file) {
				$artwork_thumb = $artwork_thumbs[$af_key]; ?>
				<li><?php echo print_products_artwork_file_html($artwork_file, $artwork_thumb, $cart_item_key); ?></li>
			<?php } ?>
		</ul>
	</div>
	<?php
}

function print_products_artwork_file_html($artwork_file, $artwork_thumb, $key) {
	global $print_products_plugin_options;
	$imgext = array('jpg', 'jpeg', 'png', 'tif', 'tiff', 'psd');
	$fileext = array('ai', 'doc', 'eps', 'jpg', 'jpeg', 'pdf', 'png', 'ppt', 'psd', 'tif', 'tiff', 'txt', 'xls', 'xlsx', 'zip', 'csv');
	$earray = explode('.', basename($artwork_file));
	$ext = end($earray);

	$icon_file = PRINT_PRODUCTS_PLUGIN_URL.'images/icons/file.png';
	if (in_array($ext, $fileext)) {
		$icon_file = PRINT_PRODUCTS_PLUGIN_URL.'images/icons/'.$ext.'.png';
	}
	$dfincart = $print_products_plugin_options['dfincart'];

	$showthumbs = false;

	$fvalue = '<img src="'.$icon_file.'" style="width:84px;">';
	if ($dfincart == 'filenames') {
		$fvalue = basename($artwork_file);
	} else if ($dfincart == 'thumbs') {
		if (strlen($artwork_thumb)) {
			$showthumbs = true;
			$imagesize = @getimagesize($artwork_thumb);
			if ($imagesize) {
				$fvalue = '<img src="'.$artwork_thumb.'" class="blitline-img" style="width:84px;">';
			} else {
				$wait_icon = PRINT_PRODUCTS_PLUGIN_URL.'images/icons/wait.gif';
				$fvalue = '<img src="'.$wait_icon.'" style="width:84px;" id="'.md5($artwork_thumb).'" class="spinning-icon" data-thumb="'.$artwork_thumb.'" data-icon="'.$icon_file.'" data-file="'.$artwork_file.'">';
				$artwork_thumb = $wait_icon;
			}
		}
	}
	if ($key == 'download') {
		return '<a href="'.print_products_get_amazon_file_url($artwork_file).'" title="'.__('Download', 'wp2print').'">'.$fvalue.'</a>';
	} else if ($showthumbs) {
		return '<a href="'.$artwork_thumb.'" rel="prettyPhoto" data-rel="prettyPhoto['.$key.']" title="'.__('View', 'wp2print').'">'.$fvalue.'</a>';
	} else if (in_array($ext, $imgext)) {
		return '<a href="'.print_products_get_amazon_file_url($artwork_file).'" rel="prettyPhoto" data-rel="prettyPhoto['.$key.']" title="'.__('View', 'wp2print').'">'.$fvalue.'</a>';
	} else {
		return '<a href="'.print_products_get_amazon_file_url($artwork_file).'" target="_blank" title="'.__('View', 'wp2print').'">'.$fvalue.'</a>';
	}
}

add_filter('body_class', 'print_products_body_class');
function print_products_body_class($classes) {
	global $post;
	$classes[] = 'print-products-installed';
	if (function_exists('is_cart') && (is_cart() || is_checkout())) {
		$classes[] = 'print-products-area';
	} else if (is_single() || is_page('my-account')) {
		if (is_single() && $post->post_type == 'product') {
			$product = wc_get_product($post->ID);
			$ptype = $product->get_type();
			if (!print_products_is_wp2print_type($ptype)) {
				$artwork_source = get_post_meta($post->ID, '_artwork_source', true);
				if (!$artwork_source) {
					$classes[] = 'variable-not-artwork-source';
				}
				return $classes;
			}
		}
		$classes[] = 'print-products-area';
	}
	return $classes;
}

function print_products_clear_cart_data() {
	global $wpdb;
	$print_products_clear_date = get_option('print_products_clear_date');
	if ($print_products_clear_date != date('Y-m-d')) {
		$cdate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') - 1, date('d'), date('Y')));
		$wpdb->query(sprintf("DELETE FROM %sprint_products_cart_data WHERE date_added < '%s'", $wpdb->prefix, $cdate));
		update_option('print_products_clear_date', date('Y-m-d'));
	}
}

function print_products_get_min_price($product_id) {
	global $wpdb;
	$price = 1;
	if (!IS_WOOCOMMERCE) { return; }
	$price_decimals = wc_get_price_decimals();
	$mtype_id = $wpdb->get_var(sprintf("SELECT mtype_id FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder LIMIT 0, 1", $wpdb->prefix, $product_id));
	if ($mtype_id) {
		$price = $wpdb->get_var(sprintf("SELECT MIN(price) FROM %sprint_products_matrix_prices WHERE price > 0 AND mtype_id = %s", $wpdb->prefix, $mtype_id));
	}
	return number_format($price, $price_decimals, '.', '');
}

function print_products_update_product_price($product_id) {
	global $wpdb;
	$product_price = print_products_get_min_price($product_id);
	update_post_meta($product_id, '_price', $product_price);
	update_post_meta($product_id, '_regular_price', $product_price);
}

function print_products_get_product_sku($mtype_id, $aterms) {
	global $wpdb, $attribute_types;

	if (strlen($aterms)) {
		$aterms = explode('-', $aterms);
		$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
		print_products_price_matrix_attr_names_init($attributes);

		$sku_aterms = array();
		foreach($aterms as $aterm) {
			$aterm_array = explode(':', $aterm);
			$akey = $aterm_array[0];
			$aval = $aterm_array[1];
			if ($attribute_types[$akey] != 'text') {
				$sku_aterms[] = $aterm;
			}
		}
		if (count($sku_aterms)) {
			return $wpdb->get_var(sprintf("SELECT sku FROM %sprint_products_matrix_sku WHERE mtype_id = %s AND aterms = '%s'", $wpdb->prefix, $mtype_id, implode('-', $sku_aterms)));
		}
	}
}

function print_products_get_item_sku($order_item_data, $onlypp = false) {
	global $print_products_settings;
	if ($order_item_data) {
		$additional = unserialize($order_item_data->additional);
		if ($onlypp) {
			return $additional['sku'];
		}
		$item_sku = get_post_meta($order_item_data->product_id, '_sku', true);
		if (strlen($additional['sku'])) {
			$item_sku = $additional['sku'];
		}
		return $item_sku;
	}
}

function print_products_attribute_label($attribute, $attribute_labels, $def_label = '') {
	if (strlen($attribute_labels[$attribute])) {
		return $attribute_labels[$attribute];
	}
	return $def_label;
}

function print_products_attribute_help_icon($attribute_id, $dhtext = '') {
	global $print_products_plugin_options, $attribute_help_texts;
	if ($print_products_plugin_options['ahelpicon'] == 1) {
		if ($attribute_id == 'ltext') {
			$help_text = $dhtext;
		} else {
			$help_text = $attribute_help_texts[$attribute_id];
		}
		if (strlen($help_text)) { ?>
			<div class="a-help">
				<img src="https://d2a5bpm7zc6p04.cloudfront.net/images/info.png">
				<div class="a-help-text"><div class="ah-text-box"><?php echo wpautop($help_text); ?></div></div>
			</div>
			<?php
		}
	}
}

function print_products_designer_installed() {
	return function_exists('personalize_init');
}

function print_products_buttons_class() {
	global $current_user_group;
	$buttons_class = '';
	if ($current_user_group) {
		$theme = unserialize($current_user_group->theme);
		if (strlen($theme['butclass'])) {
			$buttons_class = $theme['butclass'];
		}
	}
	if (!strlen($buttons_class)) {
		$print_products_plugin_options = get_option('print_products_plugin_options');
		if (strlen($print_products_plugin_options['butclass'])) {
			$buttons_class = $print_products_plugin_options['butclass'];
		}
	}
	if (!strlen($buttons_class)) {
		$buttons_class = 'button';
	}
	echo $buttons_class;
}

function print_products_get_thumb($attach_id, $width, $height, $crop = false) {
	if (is_numeric($attach_id)) {
		$image_src = wp_get_attachment_image_src($attach_id, 'full');
		$file_path = get_attached_file($attach_id);
	} else {
		$imagesize = getimagesize($attach_id);
		$image_src[0] = $attach_id;
		$image_src[1] = $imagesize[0];
		$image_src[2] = $imagesize[1];
		$file_path = $_SERVER["DOCUMENT_ROOT"].str_replace(get_bloginfo('siteurl'), '', $attach_id);
		
	}
	
	$file_info = pathinfo($file_path);
	$extension = '.'. $file_info['extension'];

	// image path without extension
	$no_ext_path = $file_info['dirname'].'/'.$file_info['filename'];

	$resized_img_path = $no_ext_path.'-'.$width.'x'.$height.$extension;

	// if file size is larger than the target size
	if ($image_src[1] > $width || $image_src[2] > $height) {
		// if resized version already exists
		if (file_exists($resized_img_path)) {
			return str_replace(basename($image_src[0]), basename($resized_img_path), $image_src[0]);
		}

		if (!$crop) {
			// calculate size proportionaly
			$proportional_size = wp_constrain_dimensions($image_src[1], $image_src[2], $width, $height);
			$resized_img_path = $no_ext_path.'-'.$proportional_size[0].'x'.$proportional_size[1].$extension;			

			// if file already exists
			if (file_exists($resized_img_path)) {
				return str_replace(basename($image_src[0]), basename($resized_img_path), $image_src[0]);
			}
		}

		// resize image if no such resized file
		$image = wp_get_image_editor($file_path);
		if (!is_wp_error($image)) {
			$image->resize($width, $height, $crop);
			$image->save($resized_img_path);
			return str_replace(basename($image_src[0]), basename($resized_img_path), $image_src[0]);
		}
	}

	// return without resizing
	return $image_src[0];
}

function print_products_amazon_s3_get_path($ptype) {
	global $current_user;
	$amazon_s3_path = '';
	$cdate = date('Y-m-d');
	$ctime = date('His', strtotime(current_time('mysql')));
	$user_login = $current_user->user_login;
	if (!strlen($user_login)) { $user_login = 'unknown'; }
	if (strlen($ptype)) {
		switch ($ptype) {
			case 'date':
				$amazon_s3_path = $cdate;
			break;
			case 'username':
				$amazon_s3_path = $user_login;
			break;
			case 'date/username':
				$amazon_s3_path = $cdate.'/'.$user_login;
			break;
			case 'username/date':
				$amazon_s3_path = $user_login.'/'.$cdate;
			break;
			case 'username/date/time':
				$amazon_s3_path = $user_login.'/'.$cdate.'/'.$ctime;
			break;
			case 'date/time':
				$amazon_s3_path = $cdate.'/'.$ctime;
			break;
			case 'date/username/time':
				$amazon_s3_path = $cdate.'/'.$user_login.'/'.$ctime;
			break;
		}
		$amazon_s3_path = $amazon_s3_path.'/';
	}
	return $amazon_s3_path;
}

function print_products_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size) {
	$s3_access_key = $amazon_s3_settings['s3_access_key'];
	$s3_secret_key = $amazon_s3_settings['s3_secret_key'];
	$s3_bucketname = $amazon_s3_settings['s3_bucketname'];
	$s3_region = $amazon_s3_settings['s3_region'];
	$s3path = print_products_amazon_s3_get_path($amazon_s3_settings['s3_path']);

	if (strlen($s3_region)) {
		$amazon_url = 'https://'.$s3_bucketname.'.s3-'.$s3_region.'.amazonaws.com/';

		$short_date = gmdate('Ymd');
		$iso_date = gmdate("Ymd\THis\Z");
		$expiration_date = gmdate('Y-m-d\TG:i:s\Z', strtotime('+1 year'));

		$policy = utf8_encode(
			json_encode(
				array(
					'expiration' => $expiration_date,  
					'conditions' => array(
						array('acl' => print_products_get_s3_acl()),
						array('bucket' => $s3_bucketname),
						array('starts-with', '$key', $s3path),
						array('starts-with', '$name', ''),
						array('starts-with', '$Content-Type', ''),
						array('starts-with', '$Content-Disposition', ''),
						array('content-length-range', '1', 5000000000),
						array('x-amz-credential' => $s3_access_key.'/'.$short_date.'/'.$s3_region.'/s3/aws4_request'),
						array('x-amz-algorithm' => 'AWS4-HMAC-SHA256'),
						array('X-amz-date' => $iso_date)
					)
				)
			)
		); 
		$kdate = hash_hmac('sha256', $short_date, 'AWS4' . $s3_secret_key, true);
		$kregion = hash_hmac('sha256', $s3_region, $kdate, true);
		$kservice = hash_hmac('sha256', "s3", $kregion, true);
		$ksigning = hash_hmac('sha256', "aws4_request", $kservice, true);
		$signature = hash_hmac('sha256', base64_encode($policy), $ksigning);
		$amazon_file_url = $amazon_url.$s3path;
		$multiparams = "multipart_params: {
			'key': '".$s3path."$"."{filename}',
			'acl': '".print_products_get_s3_acl()."',
			'X-Amz-Credential' : '".$s3_access_key."/".$short_date."/".$s3_region."/s3/aws4_request',
			'X-Amz-Algorithm' : 'AWS4-HMAC-SHA256',
			'X-Amz-Date' : '".$iso_date."',
			'policy' : '".base64_encode($policy)."',
			'X-Amz-Signature' : '".$signature."'
		},";
	} else {
		$amazon_url = 'https://'.$s3_bucketname.'.s3.amazonaws.com/';
		$policy = base64_encode(json_encode(array(
			'expiration' => date('Y-m-d\TH:i:s.000\Z', strtotime('+1 year')),  
			'conditions' => array(
				array('bucket' => $s3_bucketname),
				array('acl' => print_products_get_s3_acl()),
				array('starts-with', '$key', $s3path),
				array('starts-with', '$Content-Type', ''),
				array('starts-with', '$Content-Disposition', ''),
				array('starts-with', '$name', ''),
				array('starts-with', '$Filename', $s3path),
			)
		)));
		$signature = base64_encode(hash_hmac('sha1', $policy, $s3_secret_key, true));
		$amazon_file_url = $amazon_url.$s3path;
		$multiparams = "multipart_params: {
			'key': '".$s3path."$"."{filename}', // use filename as a key
			'Filename': '".$s3path."$"."{filename}', // adding this to keep consistency across the runtimes
			'acl': '".print_products_get_s3_acl()."',
			'AWSAccessKeyId' : '".$s3_access_key."',
			'policy': '".$policy."',
			'signature': '".$signature."'
		},";
	}
	$amazon_s3_data = array(
		'amazon_url' => $amazon_url,
		'amazon_file_url' => $amazon_file_url,
		'multiparams' => $multiparams
	);

	return $amazon_s3_data;
}

function print_products_aec_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size) {
	$s3_access_key = $amazon_s3_settings['s3_access_key'];
	$s3_secret_key = $amazon_s3_settings['s3_secret_key'];
	$s3_bucketname = $amazon_s3_settings['s3_bucketname'];
	$s3_region = $amazon_s3_settings['s3_region'];

	$amazon_url = 'https://'.$s3_bucketname.'.s3.amazonaws.com/';
	$amazon_file_url = $amazon_url;

	if (strlen($s3_region)) {
		$short_date = gmdate('Ymd');
		$kdate = hash_hmac('sha256', $short_date, 'AWS4' . $s3_secret_key, true);
		$kregion = hash_hmac('sha256', $s3_region, $kdate, true);
		$kservice = hash_hmac('sha256', "s3", $kregion, true);
		$ksigning = hash_hmac('sha256', "aws4_request", $kservice);

		$amazonS3_params = "amazonS3 : {
				accessKeyId: '".$s3_access_key."',
				acl: '".print_products_get_s3_acl()."',
				key: '<FILENAME>',
				signatureKey: '".$ksigning."',
				bucket: '".$s3_bucketname."',
				region: '".$s3_region."',
				v4: true
			}
		";
	} else {
		$policy = base64_encode(json_encode(array(
			'expiration' => date('Y-m-d\TH:i:s.000\Z', strtotime('+1 year')),  
			'conditions' => array(
				array('bucket' => $s3_bucketname),
				array('acl' => print_products_get_s3_acl()),
				array('starts-with', '$Filename', ''),
				array('starts-with', '$key', ''),
				array('starts-with', '$Content-Type', ''),
				array('eq', '$success_action_status', '201')
			)
		)));
		$signature = base64_encode(hash_hmac('sha1', $policy, $s3_secret_key, true));
		$amazonS3_params = "amazonS3 : {
				accessKeyId: '".$s3_access_key."',
				policy: '".$policy."',
				signature: '".$signature."',
				acl: '".print_products_get_s3_acl()."',
				key: '<FILENAME>'
			}
		";
	}
	$amazon_s3_data = array(
		'amazon_url' => $amazon_url,
		'amazon_file_url' => $amazon_file_url,
		'amazonS3_params' => $amazonS3_params
	);

	return $amazon_s3_data;
}

function print_products_get_s3_acl() {
	global $print_products_amazon_s3_settings;
	if ($print_products_amazon_s3_settings['s3_access'] == 'private') {
		return 'private';
	}
	return 'public-read';
}

function print_products_get_amazon_file_url($fileurl) {
	global $amazonS3Client, $print_products_amazon_s3_settings;
	if ($print_products_amazon_s3_settings['s3_access'] == 'private' && $amazonS3Client) {
		$fkey = substr($fileurl, strpos($fileurl, 'amazonaws.com') + 14);
		$fileurl = $amazonS3Client->getObjectUrl($print_products_amazon_s3_settings['s3_bucketname'], $fkey, '+48 hours');
	}
	return $fileurl;
}

function print_products_is_empty_amazon_region() {
	$file_upload_target = get_option("print_products_file_upload_target");
	if ($file_upload_target == 'amazon') {
		$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
		if (!strlen($amazon_s3_settings['s3_region'])) {
			return true;
		}
	}
	return false;
}

function print_products_tab_classes() {
	$tab_classes = array();
	$product_types = print_products_get_product_types();
	foreach($product_types as $tpkey => $tpname) {
		$tab_classes[] = 'hide_if_'.$tpkey;
	}
	return implode(' ', $tab_classes);
}

function print_products_get_uploader_lang_js_file() {
	$uploader_lang_file = 'language_en.js';
	$wplangcode = get_locale();
	$langarray = explode('_', $wplangcode);
	$lang = $langarray[0];
	if (file_exists(PRINT_PRODUCTS_PLUGIN_DIR . '/js/universal/Localization/language_'.$lang.'.js')) {
		$uploader_lang_file = 'language_'.$lang.'.js';
	}
	return $uploader_lang_file;
}

define('ALLOW_UNFILTERED_UPLOADS', true);
add_filter('upload_mimes', 'print_products_myme_types');
function print_products_myme_types($mime_types) {
	$mime_types['csv'] = 'text/csv';
	return $mime_types;
}

function print_products_get_aec_sizes() {
	return array(
		100 => __('Full size', 'wp2print'),
		200 => __('200% - 4x Area', 'wp2print'),
		140 => __('140% - 2x Area', 'wp2print'),
		70  => __('70% - 1/2 Area', 'wp2print'),
		50  => __('50% - 1/4 Area', 'wp2print')
	);
}

function print_products_format_price($price) {
	if (!IS_WOOCOMMERCE) { return; }
	$price_decimals = wc_get_price_decimals();
	$decimal_sep = wc_get_price_decimal_separator();
	$thousand_sep = wc_get_price_thousand_separator();
	return number_format($price, $price_decimals, $decimal_sep, $thousand_sep);
}

function print_products_display_price($price) {
	$price = print_products_format_price($price);
	$currency_symbol = get_woocommerce_currency_symbol();
	$currency_pos = get_option('woocommerce_currency_pos');
	if ($currency_pos == 'left') {
		return $currency_symbol . $price;
	} else if ($currency_pos == 'right') {
		return $price . $currency_symbol;
	} else if ($currency_pos == 'left_space') {
		return $currency_symbol . ' ' . $price;
	} else if ($currency_pos == 'right_space') {
		return $price . ' ' . $currency_symbol;
	}
}

function print_products_get_aec_coverage_ranges() {
	global $print_products_plugin_aec;
	$coverage_ranges = array(5,25,50,75,100);
	if (strlen($print_products_plugin_aec['aec_coverage_ranges'])) {
		$coverage_ranges = explode(',', trim($print_products_plugin_aec['aec_coverage_ranges']));
	}
	return $coverage_ranges;
}

function print_products_get_myaccount_pagename() {
	$myaccount_page_id = (int)wc_get_page_id('myaccount');
	$myaccount_page = get_post($myaccount_page_id);
	if ($myaccount_page) {
		return $myaccount_page->post_name;
	}
}

function print_products_ajax_get_price_with_tax() {
	$product_id = $_POST['product_id'];
	$price = $_POST['price'];
	$_product = new WC_Product($product_id);
	if (function_exists('wc_get_price_including_tax')) {
		$price_incl_tax = wc_get_price_including_tax($_product, array('qty' => 1, 'price' => $price));
	} else {
		$price_incl_tax = $_product->get_price_including_tax(1, $price);
	}
	echo $price_incl_tax;
}

function print_products_get_my_account_custom_page_url($cpslug) {
	$myaccount_page_id = get_option('woocommerce_myaccount_page_id');
	return get_permalink($myaccount_page_id).$cpslug.'/';
}

function print_products_my_account_is_front() {
	$page_on_front     = get_option('page_on_front');
	$myaccount_page_id = get_option('woocommerce_myaccount_page_id');
	if ($page_on_front == $myaccount_page_id) {
		return true;
	}
	return false;
}

function print_products_is_showing_page_on_front($q) {
	return $q->is_home() && 'page' === get_option('show_on_front');
}

function print_products_page_on_front_is($page_id) {
	return absint( get_option( 'page_on_front' ) ) === absint( $page_id );
}

function print_products_is_ups_shipping_installed() {
	return class_exists('UPS_WooCommerce_Shipping');
}

function print_products_get_artwork_thumbs($artwork_files) {
	$artwork_thumbs = array();
	$allowed_ext = array('jpg', 'jpeg', 'png', 'pdf');
	if ($artwork_files) {
		if (!is_array($artwork_files)) {
			$artwork_files = unserialize($artwork_files);
		}
		$blitline = new Blitline(PRINT_PRODUCTS_BLITLINE_API_KEY);
		foreach($artwork_files as $afkey => $artwork_file) {
			$file_name = basename($artwork_file);
			$file_ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
			$blitline_img = '';
			if (in_array($file_ext, $allowed_ext)) {
				$artwork_file = print_products_get_amazon_file_url($artwork_file);
				$blitline_img = $blitline->job($artwork_file, $afkey);
				if (!$blitline_img) { $blitline_img = ''; }
			}
			$artwork_thumbs[] = $blitline_img;
		}
	}
	return $artwork_thumbs;
}

function print_products_get_custom_product() {
	$print_products_send_quote_options = get_option("print_products_send_quote_options");
	return (int)$print_products_send_quote_options['custom_product'];
}

function print_products_is_custom_product($product_id) {
	$custom_product = print_products_get_custom_product();
	if ($product_id == $custom_product) {
		return true;
	}
	return false;
}

function print_products_get_custom_product_data($cart_item_key) {
	global $wpdb;

	$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
	if ($prod_cart_data) {
		if ($prod_cart_data->additional) {
			return unserialize($prod_cart_data->additional);
		}
	}
}

function print_products_ajax_check_product_config() {
	global $wpdb, $print_products_settings;
	$product_id = (int)$_POST['product_id'];
	$size_attribute = $print_products_settings['size_attribute'];
	$material_attribute = $print_products_settings['material_attribute'];
	$colour_attribute = $print_products_settings['colour_attribute'];
	$page_count_attribute = $print_products_settings['page_count_attribute'];

	if ($product_id) {
		$product = wc_get_product($product_id);
		$product_type = $product->get_type();
		$product_name = get_the_title($product_id);

		echo '<p class="chproduct"><span>'.__('Product', 'wp2print').' '.$product_name.' (ID = '.$product_id.'):</span> <a href="'.admin_url('post.php?post='.$product_id.'&action=edit').'" target="_blank">'.__('Edit', 'wp2print').'</a></p>';

		// Shipping checking
		$shipping = true;
		$tsizes = false; $tmaterials = false; $tpagecounts = false; $tcolours = false;
		$product_matrix_options = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s AND mtype = 0 ORDER BY sorder", $wpdb->prefix, $product_id));
		if ($product_matrix_options) {
			foreach($product_matrix_options as $pmokey => $product_matrix_option) {
				$aterms = unserialize($product_matrix_option->aterms);
				$tsizes = $aterms[$size_attribute];
				$tmaterials = $aterms[$material_attribute];
				$tpagecounts = $aterms[$page_count_attribute];
				$tcolours = $aterms[$colour_attribute];
			}
		}
		if ($tmaterials && ($tsizes || $tpagecounts)) {
			$product_shipping_weights = get_post_meta($product_id, '_product_shipping_weights', true);
			$product_shipping_base_quantity = (int)get_post_meta($product_id, '_product_shipping_base_quantity', true);
			if (strlen($product_shipping_weights)) {
				$product_shipping_weights = unserialize($product_shipping_weights);
				if (is_array($product_shipping_weights)) {
					foreach($product_shipping_weights as $psweights) {
						if (is_array($psweights)) {
							foreach($psweights as $psweight) {
								if (is_array($psweight)) {
									foreach($psweight as $pw) {
										if (!$pw) {
											$shipping = false;
										}
									}
								} else {
								}
							}
						} else {
							$shipping = false;
						}
					}
				} else {
					$shipping = false;
				}
			}
			if (!strlen($product_shipping_base_quantity)) {
				$shipping = false;
			}
		}
		if ($shipping) {
			echo '<p class="chcomplete">'.__('Shipping', 'wp2print').': '.__('complete', 'wp2print').'</p>';
		} else {
			echo '<p class="chmissing">'.__('Shipping', 'wp2print').': '.__('missing data', 'wp2print').'</p>';
		}

		// File source checking
		$fsource = true;
		$artwork_source = get_post_meta($product_id, '_artwork_source', true);
		if ($artwork_source == 'design' || $artwork_source == 'both') {
			if ($tsizes && ($tcolours || $tpagecounts)) {
				$personalize_sc_product_id = get_post_meta($product_id, '_personalize_sc_product_id', true);
				if ($personalize_sc_product_id && is_array($personalize_sc_product_id)) {
					foreach($personalize_sc_product_id as $sc_product_id) {
						if (is_array($sc_product_id)) {
							foreach($sc_product_id as $sc_pid_val) {
								if (is_array($sc_pid_val)) {
									foreach($sc_pid_val as $scpid) {
										if (!strlen($scpid)) {
											$fsource = false;
										}
									}
								} else {
									if (!strlen($sc_pid_val)) {
										$fsource = false;
									}
								}
							}
						} else {
							if (!strlen($sc_pid)) {
								$fsource = false;
							}
						}
					}
				} else {
					$fsource = false;
				}
			}
		}
		
		if ($fsource) {
			echo '<p class="chcomplete">'.__('File source', 'wp2print').': '.__('complete', 'wp2print').'</p>';
		} else {
			echo '<p class="chmissing">'.__('File source', 'wp2print').': '.__('missing data', 'wp2print').'</p>';
		}

		// Pricing checking
		$pricing = true;
		if ($product_type == 'aec' || $product_type == 'aecbwc' || $product_type == 'aecsimple') {
			$inc_coverage_prices = get_post_meta($product_id, '_inc_coverage_prices', true);
			if ($inc_coverage_prices) {
				if (is_array($inc_coverage_prices)) {
					foreach($inc_coverage_prices as $inc_coverage_price) {
						if (is_array($inc_coverage_price)) {
							foreach($inc_coverage_price as $icprice) {
								if (!strlen($icprice)) {
									$pricing = false;
								}
							}
						}
					}
				}
			} else {
				$pricing = false;
			}
		} else {
			$product_type_matrix_types = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_types WHERE product_id = %s ORDER BY mtype, sorder", $wpdb->prefix, $product_id));
			if ($product_type_matrix_types) {
				foreach($product_type_matrix_types as $product_type_matrix_type) {
					$mtype_id = $product_type_matrix_type->mtype_id;
					$matrix_prices = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_matrix_prices WHERE mtype_id = %s", $wpdb->prefix, $mtype_id));
					if ($matrix_prices) {
						foreach($matrix_prices as $matrix_price) {
							if (!strlen($matrix_price->price)) {
								$pricing = false;
							}
						}
					} else {
						$pricing = false;
					}
				}
			} else {
				$pricing = false;
			}
		}
		if ($pricing) {
			echo '<p class="chcomplete">'.__('Pricing', 'wp2print').': '.__('complete', 'wp2print').'</p>';
		} else {
			echo '<p class="chmissing">'.__('Pricing', 'wp2print').': '.__('missing data', 'wp2print').'</p>';
		}
		echo '<hr />';
	}
}

function print_products_ajax_check_product_send_result() {
	$result_html = $_POST['result_html'];
	$admin_email = get_option('admin_email');
	$subject = 'Check product configuration';

	$result_html = str_replace('class="chcomplete"', 'style="color:#339900;"', $result_html);
	$result_html = str_replace('class="chmissing"', 'style="color:#FF0000;"', $result_html);

	add_filter('wp_mail_content_type', function(){ return "text/html"; });

	wp_mail($admin_email, $subject, $result_html);
	echo '<p>'.__('Analysis complete', 'wp2print').'!</p>';
}

function print_products_get_shipping_date($days, $time, $weekend) {
	$days = (int)$days;
	$weekend = (int)$weekend;

	$now = strtotime(current_time('mysql'));

	$time_data = explode(':', $time);

	$sd_time = mktime(date('H', $now), date('i', $now), 0, date('m', $now), date('d', $now) + $days, date('Y', $now));
	$cutoff_time = mktime($time_data[0], $time_data[1], 0, date('m', $now), date('d', $now) + $days, date('Y', $now));
	if ($days == 0 && $sd_time > $cutoff_time) {
		$days = $days + 1;
	}
	$excluded_dates = false;
	$print_products_shipping_options = get_option('print_products_shipping_options');
	if (isset($print_products_shipping_options['excluded_dates']) && strlen($print_products_shipping_options['excluded_dates'])) {
		$excluded_dates = explode(PHP_EOL, $print_products_shipping_options['excluded_dates']);
	}

	for ($d=0; $d<=30; $d++) {
		$is_weekend = false;
		$is_excluded = false;
		$is_matched = false;
		$sd_time = mktime(date('H', $now), date('i', $now), 0, date('m', $now), date('d', $now) + $d, date('Y', $now));
		if ($weekend) {
			$day_num = date('w', $sd_time);
			if ($day_num == 0 || $day_num == 6) {
				$is_weekend = true;
			}
		}
		if ($excluded_dates) {
			foreach($excluded_dates as $excluded_date) {
				if (strpos($excluded_date, '/')) {
					$ddata = explode('/', $excluded_date);
					$hd_time = mktime(0, 0, 0, $ddata[1], $ddata[0], $ddata[2]);
				} else {
					$hd_time = strtotime($excluded_date);
				}
				if (date('Y-m-d', $hd_time) == date('Y-m-d', $sd_time)) {
					$is_excluded = true;
				}
			}
		}
		if (!$is_weekend && !$is_excluded) {
			if ($d >= $days) { break; }
		}
	}

	$sd_month = __(date('F', $sd_time));
	$sd_day = date('j', $sd_time);
	$sd_date = $sd_month.' '.$sd_day;
	$date_format = get_option('date_format');
	if (substr($date_format, 0, 1) == 'd' || substr($date_format, 0, 1) == 'j') {
		$sd_date = $sd_day.' '.$sd_month;
	}

	return $sd_date;
}

function print_products_get_order_item_template_data($order_item_data) {
	$product_id = $order_item_data->product_id;
	$product_attributes = unserialize($order_item_data->product_attributes);
	$print_products_settings = get_option('print_products_settings');
	$size_attribute = $print_products_settings['size_attribute'];
	$colour_attribute = $print_products_settings['colour_attribute'];
	$page_count_attribute = $print_products_settings['page_count_attribute'];

	$d_product_id = get_post_meta($product_id, 'a_product_id', true);
	$d_template_id = get_post_meta($product_id, 'a_template_id', true);

	$personalize_sc_product_id = get_post_meta($product_id, '_personalize_sc_product_id', true);
	$personalize_sc_template_id = get_post_meta($product_id, '_personalize_sc_template_id', true);

	if ($product_attributes) {
		$size = 0; $colour = 0; $pcount = 0;
		foreach($product_attributes as $akey => $product_attribute) {
			$padata = explode(':', $product_attribute);
			if ($padata[0] == $size_attribute) {
				$size = $padata[1];
			}
			if ($padata[0] == $colour_attribute) {
				$colour = $padata[1];
			}
			if ($padata[0] == $page_count_attribute) {
				$pcount = $padata[1];
			}
		}
	}
	if ($personalize_sc_product_id) {
		if ($size && $colour & $pcount) {
			$d_product_id = $personalize_sc_product_id[$size][$colour][$pcount];
		} else if ($size && $colour) {
			$d_product_id = $personalize_sc_product_id[$size][$colour];
		} else if ($size && $pcount) {
			$d_product_id = $personalize_sc_product_id[$size][$pcount];
		}
	}
	if ($personalize_sc_template_id) {
		if ($size && $colour & $pcount) {
			$d_template_id = $personalize_sc_template_id[$size][$colour][$pcount];
		} else if ($size && $colour) {
			$d_template_id = $personalize_sc_template_id[$size][$colour];
		} else if ($size && $pcount) {
			$d_template_id = $personalize_sc_template_id[$size][$pcount];
		}
	}
	return array('d_product_id' => $d_product_id, 'd_template_id' => $d_template_id);
}

function print_products_check_attributes_slug() {
	global $wpdb;
	$attributes_slug = array();
	$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_id", $wpdb->prefix));
	if ($attributes) {
		foreach($attributes as $attribute) {
			$attribute_id = $attribute->attribute_id;
			$attribute_name = $attribute->attribute_name;
			if (in_array($attribute_name, $attributes_slug)) {
				$attribute_name = print_products_rename_attribute_slug($attribute_name);
				$wpdb->update($wpdb->prefix.'woocommerce_attribute_taxonomies', array('attribute_name' => $attribute_name), array('attribute_id' => $attribute_id));
			}
			$attributes_slug[] = $attribute_name;
		}
	}
}

function print_products_rename_attribute_slug($slug, $nmb = 2) {
	global $wpdb;
	$new_slug = $slug . $nmb;
	$check_attribute = $wpdb->get_row(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = '%s'", $wpdb->prefix, $new_slug));
	if ($check_attribute) {
		$nmb = $nmb + 1;
		return print_products_rename_attribute_slug($slug, $nmb);
	}
	return $new_slug;
}

function print_products_sort_attributes($attributes) {
	global $attribute_sorts;
	$skey_attributes = array();
	foreach($attributes as $attribute) {
		$skey_attributes[$attribute_sorts[$attribute]] = $attribute;
	}
	ksort($skey_attributes);
	$sorted_attributes = array();
	foreach($skey_attributes as $skey_attribute) {
		$sorted_attributes[] = $skey_attribute;
	}
	return $sorted_attributes;
}

function print_products_text2array($text) {
	$text_array = array();
	if (strlen($text)) {
		$tarray = explode("\n", $text);
		foreach($tarray as $tval) {
			$tval = trim($tval);
			if (strlen($tval)) {
				$text_array[] = $tval;
			}
		}
	}
	return $text_array;
}

function print_products_get_order_item_decimals($qty) {
	$decimals = 4;
	if ($qty > 1000) {
		$decimals = 6;
	}
	return $decimals;
}

function print_products_help_icon($fkey) {
	$htexts = array(
		'size_attribute' => 'Help text for field Size attribute',
		'colour_attribute' => 'Help text for field Colour attribute',
		'material_attribute' => 'Help text for field Material attribute',
		'page_count_attribute' => 'Help text for field Page Count attribute',
		'postage_attribute' => 'Help text for field Postage attribute',
		'printing_attributes' => 'Help text for field Printing attributes',
		'finishing_attributes' => 'Help text for field Finishing attributes',
		'attributes_order' => 'Help text for field Attributes sort order',
		'attributes' => 'Help text for field Attributes',
		'attribute_prefix' => 'Help text for field Attribute prefix',
		'quantity_display_style' => 'Help text for field Quantity display style',
		'letter_text_attribute' => 'Help text for field Letter text attribute',
		'proportional_quantity' => 'Help text for field Proportional quantity',
		'quantities' => 'Help text for field Quantities',
		'default_quantity_value' => 'Help text for field Default value for quantity',
		'display_sort_order' => 'Help text for field Display Sort Order',
		'license_key' => 'Help text for field License Key',
		'file_upload_target' => 'Help text for field File upload target',
		's3_access_key' => 'Help text for field S3 Access Key',
		's3_secret_key' => 'Help text for field S3 Secret Key',
		's3_bucketname' => 'Help text for field S3 Bucketname',
		's3_region' => 'Help text for field S3 Region',
		's3_path' => 'Help text for field S3 Path',
		's3_access' => 'Help text for field S3 Files Access',
		'file_upload_max_size' => 'Help text for field File upload max size',
		'infoform_form_title' => 'Help text for field Form title',
		'infoform_form_success_text' => 'Help text for field Form success text',
		'infoform_default_country' => 'Help text for field Default country',
		'infoform_enable_state_field' => 'Help text for field Enable State field',
		'infoform_state_field_label' => 'Help text for field State field label',
		'infoform_zip_field_label' => 'Help text for field Zip field label',
		'infoform_customer_email_subject' => 'Help text for field Customer email subject',
		'infoform_customer_email_heading' => 'Help text for field Customer email heading',
		'infoform_customer_email_content' => 'Help text for field Customer email content',
		'infoform_admin_email_subject' => 'Help text for field Admin email subject',
		'infoform_admin_email_heading' => 'Help text for field Admin email heading',
		'options_butclass' => 'Help text for field Buttons CSS class',
		'options_dfincart' => 'Help text for field Display files in cart as',
		'options_ahelpicon' => 'Help text for field Display attributes help icon',
		'options_allowmodifygroup' => 'Help text for field Allow users to modify group',
		'options_max_price_message' => 'Help text for field Maximum price message',
		'api_enable' => 'Help text for field Enable Single Sign-on',
		'api_key' => 'Help text for field API Key',
		'aec_coverage_ranges' => 'Help text for field Coverage % Ranges',
		'aec_dimensions_unit' => 'Help text for field Dimensions unit',
		'aec_enable_size' => 'Help text for field Enable size modification in Low-cost option pop-up',
		'aec_pay_now_text' => 'Help text for field Pay Now button text',
		'aec_order_email_subject' => 'Help text for field RapidQuote Email Subject',
		'aec_order_email_message' => 'Help text for field RapidQuote Email Message',
		'aec_upload_widget_text' => 'Help text for field File upload widget text hint',
		'email_order_proof_subject' => 'Help text for field Approval Order Email Subject',
		'email_order_proof_message' => 'Help text for field Approval Order Email Message',
		'proof_admin_subject_approvals' => 'Help text for field Email Subject for approvals',
		'proof_admin_message_approvals' => 'Help text for field Email Message for approvals',
		'proof_admin_subject_rejections' => 'Help text for field Email Subject for rejections',
		'proof_admin_message_rejections' => 'Help text for field Email Message for approvals',
		'jobticket_exclude_prices' => 'Help text for field Job-ticket excludes prices',
		'emailquote_enable' => 'Help text for field Enable Widget',
		'emailquote_subject' => 'Help text for field Email Quote Email Subject',
		'emailquote_heading' => 'Help text for field Message Heading',
		'emailquote_toptext' => 'Help text for field Message Top Text',
		'emailquote_bottomtext' => 'Help text for field Message Bottom Text',
		'emailquote_disable_private' => 'Help text for field Disable widget in Private Stores',
		'vendor_shipping_address' => 'Help text for field Vendor Shipping Address',
		'vendor_billing_address' => 'Help text for field Vendor Billing Address',
		'vendor_use_billing' => 'Help text for field Use printshop billing address',
		'vendor_show_column' => 'Help text for field Display Vendor in Orders pages',
		'vendor_show_to_customer' => 'Help text for field Display Vendor to customer',
		'employee_show_column' => 'Help text for field Display Employee in Orders pages',
		'employee_show_to_customer' => 'Help text for field Display responsible employee to customer',
		'employee_show_contact_info' => 'Help text for field Display contact info of responsible employee to customer',
		'vendor_show_assign_to_me' => 'Help text for field Display Assign to Me to vendor employees',
		'vendor_email_subject' => 'Help text for field Vendor Email Subject',
		'vendor_email_header' => 'Help text for field Vendor Email Header',
		'vendor_email_top_text' => 'Help text for field Vendor Email Top Text',
		'sendquote_pay_now_text' => 'Help text for field Send Qoute Pay Now button text',
		'sendquote_bcc_email' => 'Help text for field Send BCC copy of quote to',
		'sendquote_email_subject' => 'Help text for field Send Qoute Email Subject',
		'sendquote_email_message' => 'Help text for field Send Qoute Email Message',
		'sendquote_quote_period' => 'Help text for field Send Qoute Valid Period',
		'sendquote_expired_message' => 'Help text for field Send Qoute Expired Message',
		'sendquote_custom_product' => 'Help text for field Send Qoute Custom product',
		'valid_address_verify' => 'Help text for field Verify USA addresses with USPS',
		'shipping_multiple' => 'Help text for field Each line item packed into multiple boxes',
		'shipping_excluded_dates' => 'Help text for field Excluded dates',
		'sendquote_quote_send_email2' => 'Help text for field Send mail 2',
		'sendquote_quote_send_email2_days' => 'Help text for field Days to send mail 2',
		'sendquote_email_subject2' => 'Help text for field Email Subject 2',
		'sendquote_email_message2' => 'Help text for field Email Message 2',
		'sendquote_quote_send_email3' => 'Help text for field Send mail 3',
		'sendquote_quote_send_email3_days' => 'Help text for field Days to send mail 3',
		'sendquote_email_subject3' => 'Help text for field Email Subject 3',
		'sendquote_email_message3' => 'Help text for field Email Message 3',
		'sendquote_cnu_email_subject' => 'Help text for field Email Subject',
		'sendquote_cnu_email_message' => 'Help text for field Email Message',
		'oistatus_use' => 'Help text for field Use status for each order item',
		'oistatus_list' => 'Help text for field Order Items Statuses list',
		'oistatus_default' => 'Help text for field Order Items Default Status',
		'oistatus_subject' => 'Help text for field Order Items Email Subject',
		'oistatus_message' => 'Help text for field Order Items Email Message',
		'oistatus_exclude_wstatus' => 'Help text for field Exclude Orders status',
		'oistatus_tracking_prompt' => 'Help text for field Prompt for tracking numbers',
		'oistatus_tracking_status' => 'Help text for field Shipped status',
		'oistatus_tracking_companies' => 'Help text for field Shipping companies',
		'oistatus_tracking_dcompany' => 'Help text for field Default shipping company',
		'oistatus_tracking_subject' => 'Help text for field Tracking Email Subject',
		'oistatus_tracking_heading' => 'Help text for field Tracking Email Heading',
		'oistatus_tracking_message' => 'Help text for field Tracking Email Message',
		'recaptcha_use' => 'Help text for field Use reCaptcha',
		'recaptcha_site_key' => 'Help text for field Google reCaptcha Site Key',
		'recaptcha_secret_key' => 'Help text for field Google reCaptcha Secret Key',
		'recaptcha_version' => 'Help text for field Google reCaptcha Version',
		'fmodification_production_statuses' => 'Help text for field Show for production statuses',
		'prodview_display_vendor' => 'Help text for field Display Vendor in Production pages',
		'prodview_display_employee' => 'Help text for field Display Employee in Production pages',
		'prodview_display_customer' => 'Help text for field Display Customer Company in Production pages',
		'prodview_display_shipdate' => 'Help text for field Display Required ship date in Production pages',
		'prodview_orders_display_shipdate' => 'Help text for field Display Required ship date in Orders pages',
		'printersplan_enable' => 'Help text for field Printers Plan Enable XML process',
		'printersplan_domain_from' => 'Help text for field Printers Plan Domain from',
		'printersplan_from' => 'Help text for field Printers Plan From',
		'printersplan_domain_to' => 'Help text for field Printers Plan Domain to',
		'printersplan_to' => 'Help text for field Printers Plan To',
		'printersplan_shared_secret' => 'Help text for field Printers Plan SharedSecret',
		'printersplan_url' => 'Help text for field Printers Plan URL',
		'printersplan_concatenate' => 'Help text for field Printers Plan Concatenate the filename'
		
	); ?>
	<img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/help.png" class="help-icon" title="<?php _e($htexts[$fkey], 'wp2print'); ?>" width="16" height="16">
	<?php
}

function print_products_info_form_get_countries() {
	return array(242 => "Afghanistan", 2 => "Albania", 3 => "Algeria", 5 => "Andorra", 6 => "Angola", 7 => "Anguilla", 8 => "Antarctica", 9 => "Antigua and Barbuda", 10 => "Argentina", 11 => "Armenia", 12 => "Aruba", 13 => "Australia", 14 => "Austria", 15 => "Azerbaijan", 16 => "Bahamas", 17 => "Bahrain", 18 => "Bangladesh", 19 => "Barbados", 20 => "Belarus", 21 => "Belgium", 22 => "Belize", 23 => "Benin", 24 => "Bermuda", 25 => "Bhutan", 26 => "Bolivia", 27 => "Bosnia and Herzegowina", 28 => "Botswana", 29 => "Bouvet Island", 30 => "Brazil", 31 => "British Indian Ocean Territory", 32 => "Brunei Darussalam", 33 => "Bulgaria", 34 => "Burkina Faso", 35 => "Burundi", 36 => "Cambodia", 37 => "Cameroon", 38 => "Canada", 39 => "Cape Verde", 40 => "Cayman Islands", 41 => "Central African Republic", 42 => "Chad", 43 => "Chile", 44 => "China", 45 => "Christmas Island", 46 => "Cocos (Keeling) Islands", 47 => "Colombia", 48 => "Comoros", 49 => "Congo", 243 => "Congo (Kinshasa)", 50 => "Cook Islands", 51 => "Costa Rica", 52 => "Cote D'Ivoire", 53 => "Croatia", 54 => "Cuba", 55 => "Cyprus", 56 => "Czech Republic", 57 => "Denmark", 58 => "Djibouti", 59 => "Dominica", 60 => "Dominican Republic", 61 => "East Timor", 62 => "Ecuador", 63 => "Egypt", 64 => "El Salvador", 65 => "Equatorial Guinea", 66 => "Eritrea", 67 => "Estonia", 68 => "Ethiopia", 69 => "Falkland Islands (Malvinas)", 70 => "Faroe Islands", 71 => "Fiji", 72 => "Finland", 73 => "France", 74 => "France, Metropolitan", 75 => "French Guiana", 76 => "French Polynesia", 77 => "French Southern Territories", 78 => "Gabon", 79 => "Gambia", 80 => "Georgia", 81 => "Germany", 82 => "Ghana", 83 => "Gibraltar", 84 => "Greece", 85 => "Greenland", 86 => "Grenada", 87 => "Guadeloupe", 88 => "Guam", 89 => "Guatemala", 90 => "Guinea", 91 => "Guinea-bissau", 92 => "Guyana", 93 => "Haiti", 94 => "Heard and Mc Donald Islands", 95 => "Honduras", 96 => "Hong Kong", 97 => "Hungary", 98 => "Iceland", 99 => "India", 100 => "Indonesia", 101 => "Iran", 102 => "Iraq", 103 => "Ireland", 104 => "Israel", 105 => "Italy", 106 => "Jamaica", 107 => "Japan", 108 => "Jordan", 109 => "Kazakhstan", 110 => "Kenya", 111 => "Kiribati", 112 => "Korea, Democratic Peoples Republic of", 113 => "Korea, Republic of", 114 => "Kuwait", 115 => "Kyrgyzstan", 116 => "Lao Peoples Democratic Republic", 117 => "Latvia", 118 => "Lebanon", 119 => "Lesotho", 120 => "Liberia", 121 => "Libyan Arab Jamahiriya", 122 => "Liechtenstein", 123 => "Lithuania", 124 => "Luxembourg", 125 => "Macau", 126 => "Macedonia", 127 => "Madagascar", 128 => "Malawi", 129 => "Malaysia", 130 => "Maldives", 131 => "Mali", 132 => "Malta", 133 => "Marshall Islands", 134 => "Martinique", 135 => "Mauritania", 136 => "Mauritius", 137 => "Mayotte", 138 => "Mexico", 139 => "Micronesia", 140 => "Moldova", 141 => "Monaco", 142 => "Mongolia", 244 => "Montenegro", 143 => "Montserrat", 144 => "Morocco", 145 => "Mozambique", 146 => "Myanmar", 147 => "Namibia", 148 => "Nauru", 149 => "Nepal", 150 => "Netherlands", 151 => "Netherlands Antilles", 152 => "New Caledonia", 153 => "New Zealand", 154 => "Nicaragua", 155 => "Niger", 156 => "Nigeria", 157 => "Niue", 158 => "Norfolk Island", 159 => "Northern Mariana Islands", 160 => "Norway", 161 => "Oman", 162 => "Pakistan", 163 => "Palau", 164 => "Panama", 165 => "Papua New Guinea", 166 => "Paraguay", 167 => "Peru", 168 => "Philippines", 169 => "Pitcairn", 170 => "Poland", 171 => "Portugal", 172 => "Puerto Rico", 173 => "Qatar", 174 => "Reunion", 175 => "Romania", 176 => "Russian Federation", 177 => "Rwanda", 178 => "Saint Kitts and Nevis", 179 => "Saint Lucia", 180 => "Saint Vincent and the Grenadines", 181 => "Samoa", 182 => "San Marino", 183 => "Sao Tome and Principe", 184 => "Saudi Arabia", 185 => "Senegal", 245 => "Serbia", 186 => "Seychelles", 187 => "Sierra Leone", 188 => "Singapore", 189 => "Slovakia (Slovak Republic)", 190 => "Slovenia", 191 => "Solomon Islands", 192 => "Somalia", 193 => "South Africa", 194 => "South Georgia and the South Sandwich Islands", 246 => "South Sudan", 195 => "Spain", 196 => "Sri Lanka", 197 => "St. Helena", 198 => "St. Pierre and Miquelon", 199 => "Sudan", 200 => "Suriname", 201 => "Svalbard and Jan Mayen Islands", 202 => "Swaziland", 203 => "Sweden", 204 => "Switzerland", 205 => "Syrian Arab Republic", 206 => "Taiwan, Province of China", 207 => "Tajikistan", 208 => "Tanzania, United Republic of", 209 => "Thailand", 247 => "Timor-Leste", 210 => "Togo", 211 => "Tokelau", 212 => "Tonga", 213 => "Trinidad and Tobago", 214 => "Tunisia", 215 => "Turkey", 216 => "Turkmenistan", 217 => "Turks and Caicos Islands", 218 => "Tuvalu", 219 => "Uganda", 220 => "Ukraine", 221 => "United Arab Emirates", 222 => "United Kingdom", 223 => "United States", 224 => "United States Minor Outlying Islands", 225 => "Uruguay", 226 => "Uzbekistan", 227 => "Vanuatu", 228 => "Vatican City State (Holy See)", 229 => "Venezuela", 230 => "Viet Nam", 231 => "Virgin Islands (British)", 232 => "Virgin Islands (U.S.)", 233 => "Wallis and Futuna Islands", 234 => "Western Sahara", 235 => "Yemen", 236 => "Yugoslavia", 237 => "Zaire", 238 => "Zambia", 239 => "Zimbabwe");
}

?>