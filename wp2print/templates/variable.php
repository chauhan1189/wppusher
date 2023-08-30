<?php
/**
 * Variable product add to cart
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$artwork_source = get_post_meta($product->id, '_artwork_source', true);
$artwork_allow_later = get_post_meta($product->id, '_artwork_allow_later', true);
$artwork_file_count = (int)get_post_meta($product_id, '_artwork_file_count', true);
$artwork_afile_types = get_post_meta($product_id, '_artwork_afile_types', true);

$attribute_keys = array_keys( $attributes );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart add-cart-form" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->id ); ?>" data-product_variations="<?php echo esc_attr( json_encode( $available_variations ) ) ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) : ?>
					<tr>
						<td class="label"><label for="<?php echo sanitize_title( $attribute_name ); ?>"><?php echo wc_attribute_label( $attribute_name ); ?></label></td>
						<td class="value">
							<?php
								$selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) : $product->get_variation_default_attribute( $attribute_name );
								wc_dropdown_variation_attribute_options( array( 'options' => $options, 'attribute' => $attribute_name, 'product' => $product, 'selected' => $selected ) );
								echo end( $attribute_keys ) === $attribute_name ? '<a class="reset_variations" href="#">' . __( 'Clear selection', 'woocommerce' ) . '</a>' : '';
							?>
						</td>
					</tr>
		        <?php endforeach;?>
			</tbody>
		</table>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="single_variation_wrap" style="display:none;">
			<?php
				/**
				 * woocommerce_before_single_variation Hook
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * woocommerce_single_variation hook. Used to output the cart button and placeholder for variation data.
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				if (strlen($artwork_source)) {
					woocommerce_single_variation(); ?>
					<div class="variations_button">
						<input type="hidden" name="quantity" value="1">
						<?php if (strlen($artwork_source)) { ?>
							<?php if ($artwork_source == 'artwork' || $artwork_source == 'both') { ?>
								<input type="button" value="<?php _e('Upload your own design', 'wp2print'); ?>" class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt artwork-btn upload-artwork-btn">
								<?php if ($artwork_allow_later) { ?>
									<button class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt artwork-btn simple-add-btn ch-price"><?php _e('Upload later', 'wp2print'); ?></button>
								<?php } ?>
							<?php } ?>
							<?php if (($artwork_source == 'design' || $artwork_source == 'both') && print_products_designer_installed()) {
								$personalizeclass = 'personalize';
								if (print_products_designer_installed()) {
									$window_type = personalize_get_window_type();
									if ($window_type == 'Modal Pop-up window') {
										$personalizeclass .= ' personalizep';
									}
								}
								?>&nbsp;
								<button class="single_add_to_cart_button <?php print_products_buttons_class(); ?> alt design-online-btn <?php echo $personalizeclass; ?>"><?php _e('DESIGN ONLINE', 'wp2print'); ?></button>
							<?php } ?>
						<?php } else { ?>
							<button type="submit" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
						<?php } ?>

						<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->id ); ?>" />
						<input type="hidden" name="variation_id" class="variation_id" value="" />
						<input type="hidden" name="print_products_checkout_process_action" value="add-to-cart">
						<input type="hidden" name="product_type" value="variable">
						<input type="hidden" name="product_id" value="<?php echo absint( $product->id ); ?>" />
						<input type="hidden" name="atcaction" class="atc-action" value="design">
						<input type="hidden" name="artworkfiles" class="artwork-files" value="">
					</div>
					<?php
				} else {
					do_action( 'woocommerce_single_variation' );
				}

				/**
				 * woocommerce_after_single_variation Hook
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>
<script>
jQuery(document).ready(function() {
	jQuery('.upload-artwork-btn').click(function(){ jQuery('.add-cart-form .atc-action').val('artwork'); });
	jQuery('.design-online-btn').click(function(){ jQuery('.add-cart-form .atc-action').val('design'); });
	jQuery('.simple-add-btn').click(function(){ jQuery('.add-cart-form .atc-action').val('artwork'); });
});
</script>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php if (strlen($artwork_source)) { ?>
	<?php include('product-upload-artwork.php'); ?>
<?php } ?>
