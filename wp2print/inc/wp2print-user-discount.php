<?php
function print_products_user_discount_get_discount_amount() {
	global $current_user;
	$user_discount = 0;
	if (is_user_logged_in()) {
		$user_discount = (int)get_user_meta($current_user->ID, '_user_discount', true);
	}
	return $user_discount;
}

function print_products_user_discount_get_discount_price($price) {
	$discount_price = 0;
	$user_discount = print_products_user_discount_get_discount_amount();
	if ($user_discount) {
		$price = (float)$price;
		$discount_price = ($price / 100) * $user_discount;
	}
	return $discount_price;
}

function print_products_user_discount_get_discounted_price($price) {
	$user_discount = print_products_user_discount_get_discount_amount();
	if ($user_discount) {
		$price = (float)$price;
		$discount_price = ($price / 100) * $user_discount;
		$price = $price - $discount_price;
	}
	return $price;
}

// simple product price
add_filter('woocommerce_product_get_price', 'print_products_user_discount_product_get_price', 50, 2);
add_filter('woocommerce_variation_prices_price', 'print_products_user_discount_product_get_price', 50, 2);
add_filter('woocommerce_product_variation_get_price', 'print_products_user_discount_product_get_price', 50, 2);
function print_products_user_discount_product_get_price($price, $product) {
	$ud_calculate = true;
	$product_type = $product->get_type();
	if (print_products_is_wp2print_type($product_type) && (is_cart() || is_checkout())) { $ud_calculate = false; }
	if ($ud_calculate) {
		$price = print_products_user_discount_get_discounted_price($price);
	}
	return $price;
}

// show discount on admin page
add_filter('woocommerce_order_item_get_subtotal', 'print_products_user_discount_order_item_get_subtotal', 50, 2);
function print_products_user_discount_order_item_get_subtotal($subtotal, $item) {
	if (is_admin()) {
		$item_id = $item->get_id();
		$discount_price = (float)wc_get_order_item_meta($item_id, '_discount_price');
		if ($discount_price) {
			$subtotal = $subtotal + $discount_price;
		}
	}
	return $subtotal;
}

// admin part
add_action('show_user_profile', 'print_products_user_discount_profile_field');
add_action('edit_user_profile', 'print_products_user_discount_profile_field');
function print_products_user_discount_profile_field($profileuser) {
	global $current_user;
	if (current_user_can('manage_options', $current_user->ID)) {
		$user_discount = (int)get_user_meta($profileuser->ID, '_user_discount', true); ?>
		<h3><?php _e('Discount level', 'wp2print'); ?></h3>
		<table class="form-table">
			<tr>
				<th><label><?php _e('Discount amount', 'wp2print'); ?></label></th>
				<td>
					<input type="text" name="user_discount" value="<?php echo $user_discount; ?>" style="width:40px;">%
				</td>
			</tr>
		</table>
		<?php
	}
}

add_action('personal_options_update', 'print_products_user_discount_save_profile_field');
add_action('edit_user_profile_update', 'print_products_user_discount_save_profile_field');
function print_products_user_discount_save_profile_field($user_id) {
	global $current_user;
	if (current_user_can('manage_options', $current_user->ID)) {
		update_usermeta($user_id, '_user_discount', (int)$_POST['user_discount']);
	}
}
?>