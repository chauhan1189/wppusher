<?php
global $wpdb, $print_products_settings, $attribute_names, $attribute_types, $terms_names;

$attributes = $wpdb->get_results(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies ORDER BY attribute_order, attribute_label", $wpdb->prefix));
print_products_price_matrix_attr_names_init($attributes);
?>
<div class="co-box">
	<p class="form-field">
		<label><?php _e('Quantity', 'wp2print'); ?>: <span class="req">*</span></label>
		<input type="text" name="quantity" class="quantity" value="<?php if ($product_data['quantity']) { echo $product_data['quantity']; } else { echo '1'; } ?>" onblur="variable_set_variation()">
	</p>
	<?php
	$available_variations = $product->get_available_variations();
	if ($available_variations) {
		$attributes = $product->get_attributes();
		foreach ($attributes as $attribute_name => $options) { $oterms = $options->get_terms();
			if ($oterms) { $aslug = sanitize_title($attribute_name); ?>
				<p class="form-field">
					<label><?php echo wc_attribute_label($attribute_name); ?></label>
					<select name="attributes[<?php echo $aslug; ?>]" class="attribute-select" onchange="variable_set_variation()">
						<?php foreach($oterms as $oterm) { ?>
							<option value="<?php echo $oterm->slug; ?>"<?php if ($product_data['attributes'] && $product_data['attributes'][$aslug] == $oterm->slug) { echo ' SELECTED'; } ?>><?php echo $oterm->name; ?></option>
						<?php } ?>
					</select>
				</p>
			<?php } ?>
		<?php } ?>
	<?php } ?>
	<p class="form-field">
		<label><?php _e('Subtotal', 'wp2print'); ?>: <span class="req">*</span></label>
		<input type="text" name="price" class="p-price" value="<?php if ($product_data['price']) { echo $product_data['price']; } ?>">
	</p>
</div>
<input type="hidden" name="product_type" value="variable">
<input type="hidden" name="variation_id" value="<?php if ($product_data['variation_id']) { echo $product_data['variation_id']; } ?>" class="variation-id">
<script>
var variations = [];
<?php if ($available_variations) { ?>
<?php foreach($available_variations as $vkey => $available_variation) { ?>
variations[<?php echo $vkey; ?>] = { 'key' : '<?php echo implode('-', $available_variation['attributes']); ?>', 'variation_id' : <?php echo $available_variation['variation_id']; ?>, 'price' : <?php echo $available_variation['display_price']; ?> };
<?php } ?>
<?php } ?>
function variable_set_variation() {
	var price = 0;
	var subtotal = 0;
	var quantity = parseInt(jQuery('.create-order-form .quantity').val());
	if (variations.length) {
		var vkey = '';
		jQuery('.create-order-form .attribute-select').each(function(){
			if (vkey != '') { vkey += '-'; }
			vkey += jQuery(this).val();
		});
		for (var i=0; i<variations.length; i++) {
			var variation_data = variations[i];
			if (variation_data.key == vkey) {
				price = variation_data.price;
				jQuery('.create-order-form .variation-id').val(variation_data.variation_id);
			}
		}
	}
	subtotal = price * quantity;
	jQuery('.create-order-form .p-price').val(subtotal.toFixed(2));
	matrix_set_tax();
	matrix_set_prices();
}
<?php if (!$product_data['attributes']) { ?>
jQuery(document).ready(function() {
	variable_set_variation();
});
<?php } ?>
</script>