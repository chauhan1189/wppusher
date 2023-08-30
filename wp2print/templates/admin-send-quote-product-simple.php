<div class="co-box">
	<p class="form-field">
		<label><?php _e('Quantity', 'wp2print'); ?>: <span class="req">*</span></label>
		<input type="text" name="quantity" class="quantity" value="<?php if ($product_data['quantity']) { echo $product_data['quantity']; } else { echo '1'; } ?>" onblur="matrix_calculate_price()">
	</p>
</div>
<input type="hidden" name="product_type" value="simple">
<script>
<?php if (!$product_data['price']) { ?>
jQuery(document).ready(function() {
	matrix_calculate_price();
});
<?php } ?>
function matrix_calculate_price() {
	var quantity = parseInt(jQuery('.send-quote-form .quantity').val());
	var price = <?php echo $product->get_price(); ?>;
	var subtotal = price * quantity;
	jQuery('.send-quote-form .p-price').val(subtotal.toFixed(2));
	jQuery('.send-quote-form .t-price').html(matrix_html_price(subtotal));
}
</script>