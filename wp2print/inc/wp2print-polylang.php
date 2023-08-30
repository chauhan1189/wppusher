<?php
function print_products_polylang_installed() {
	if (class_exists('Polylang')) {
		return true;
	}
	return false;
}

add_action('pll_save_post', 'print_products_polylang_save_post', 20, 3);
function print_products_polylang_save_post($post_id, $post, $translations) {
	if ($post->post_type == 'product') {
		if ($translations) {
			foreach ($translations as $lang => $lang_post_id) {
				print_products_polylang_update_post_metas($post_id, $lang_post_id);
			}
		}
	}
}

function print_products_polylang_update_post_metas($parent_post_id, $lang_post_id) {
	$pmetas = array(
		'_artwork_source',
		'_product_shipping_weights',
		'_product_shipping_base_quantity',
		'_product_display_weight',
		'_product_display_price'
	);
	foreach($pmetas as $meta_key) {
		$meta_value = get_post_meta($parent_post_id, $meta_key, true);
		if (strlen($meta_value)) {
			update_post_meta($lang_post_id, $meta_key, $meta_value);
		} else {
			delete_post_meta($lang_post_id, $meta_key);
		}
	}
}

function print_products_polylang_get_product_ids($products) {
	$product_ids = array();
	if ($products) {
		foreach($products as $product_id) {
			$product_id = (int)$product_id;
			$product_ids[] = $product_id;
			$post_translations = get_the_terms($product_id, 'post_translations');
			if ($post_translations) {
				$translations = unserialize($post_translations[0]->description);
				if ($translations && count($translations)) {
					foreach($translations as $tp_id) {
						if (!in_array($tp_id, $product_ids)) {
							$product_ids[] = $tp_id;
						}
					}
				}
			}
		}
	}
	return $product_ids;
}

function print_products_polylang_get_category_ids($categories) {
	$category_ids = array();
	if ($categories) {
		foreach($categories as $category_id) {
			$category_id = (int)$category_id;
			$category_ids[] = $category_id;
			$term_translations = print_products_polylang_get_term_translations($category_id);
			if ($term_translations) {
				$translations = unserialize($term_translations->description);
				if ($translations && count($translations)) {
					foreach($translations as $tc_id) {
						if (!in_array($tc_id, $category_ids)) {
							$category_ids[] = $tc_id;
						}
					}
				}
			}
		}
	}
	return $category_ids;
}

function print_products_polylang_get_term_translations($category_id) {
	global $wpdb;
	return $wpdb->get_row(sprintf("SELECT tt.* FROM %sterm_taxonomy tt LEFT JOIN %sterm_relationships tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'term_translations' AND tr.object_id = %s", $wpdb->prefix, $wpdb->prefix, $category_id));
}
?>