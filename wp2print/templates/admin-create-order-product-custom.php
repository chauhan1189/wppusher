<div class="co-box product-attributes">
	<p class="form-field">
		<label><?php _e('Quantity', 'wp2print'); ?>: <span class="req">*</span></label>
		<input type="text" name="quantity" class="quantity" value="<?php if ($product_data['quantity']) { echo $product_data['quantity']; } else { echo '1'; } ?>" onblur="matrix_calculate_price()">
	</p>
	<p class="form-field">
		<label><?php _e('Attributes', 'wp2print'); ?>:</label>
		<textarea name="attributes" style="width:100%; height:150px;"><?php echo $product_data['attributes']; ?></textarea>
	</p>
	<p class="form-field">
		<label><?php _e('Price', 'wp2print'); ?>: <span class="req">*</span></label>
		<?php echo get_woocommerce_currency_symbol(); ?> <input type="text" name="cprice" value="<?php if ($product_data['price']) { echo $product_data['price']; } else { echo '1'; } ?>" onblur="matrix_calculate_price()" class="c-price" style="width:98% !important;">
	</p>
	<p class="form-field">
		<label><?php _e('Subtotal', 'wp2print'); ?>: <span class="req">*</span></label>
		<input type="text" name="price" class="p-price" value="<?php if ($product_data['price']) { echo $product_data['price']; } else { echo '1'; } ?>">
	</p>
</div>
<input type="hidden" name="product_type" value="custom">
<script>
var price = 0;
function matrix_calculate_price() {
	price = 0;
	var quantity = parseInt(jQuery('.product-attributes .quantity').val());
	var cprice = parseFloat(jQuery('.product-attributes .c-price').val());

	price = cprice * quantity;

	jQuery('.create-order-form .p-price').val(price.toFixed(2));

	matrix_set_tax();
	matrix_set_prices();
}
</script>