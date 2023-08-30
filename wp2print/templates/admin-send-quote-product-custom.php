<?php
if (!isset($product_data['shipping_specify'])) { $product_data['shipping_specify'] = 'weight'; }
?>
<div class="co-box">
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
		<?php echo get_woocommerce_currency_symbol(); ?> <input type="text" name="price" value="<?php if ($product_data['price']) { echo $product_data['price']; } ?>" onblur="matrix_calculate_price()" style="width:98% !important;">
	</p>
	<p class="form-field shipping-specify-radio">
		<input type="radio" name="shipping_specify" class="ss-weight" value="weight" onclick="sq_custom_shipping_specify()"<?php if ($product_data['shipping_specify'] == 'weight') { echo ' CHECKED'; } ?>><?php _e('Specify shipping weight', 'wp2print'); ?><br>
		<input type="radio" name="shipping_specify" class="ss-cost" value="cost" onclick="sq_custom_shipping_specify()"<?php if ($product_data['shipping_specify'] == 'cost') { echo ' CHECKED'; } ?>><?php _e('Specify shipping cost', 'wp2print'); ?>
	</p>
	<p class="form-field sh-specify-weight">
		<label><?php _e('Weight', 'wp2print'); ?> (<?php echo print_products_get_weight_unit(); ?>):</label>
		<input type="text" name="weight" value="<?php if ($product_data['weight']) { echo $product_data['weight']; } ?>">
	</p>
	<p class="form-field sh-specify-weight">
		<label><?php _e('Shipping box count', 'wp2print'); ?>:</label>
		<input type="text" name="sboxes" value="<?php if ($product_data['sboxes']) { echo $product_data['sboxes']; } ?>">
	</p>
	<p class="form-field sh-specify-cost">
		<label><?php _e('Shipping cost', 'wp2print'); ?>:</label>
		<input type="text" name="shipping_cost" value="<?php if ($product_data['shipping_cost']) { echo $product_data['shipping_cost']; } ?>">
	</p>
</div>
<input type="hidden" name="product_type" value="custom">
<script>
function sq_custom_shipping_specify() {
	var shipping_specify = 'weight';
	if (jQuery('.shipping-specify-radio input.ss-cost').is(':checked')) {
		shipping_specify = 'cost';
	}
	if (shipping_specify == 'weight') {
		jQuery('.sh-specify-cost').hide();
		jQuery('.sh-specify-weight').fadeIn();
	} else {
		jQuery('.sh-specify-weight').hide();
		jQuery('.sh-specify-cost').fadeIn();
	}
}
sq_custom_shipping_specify();
</script>