<?php
/**
 * Simple product add to cart
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

?>

<?php
	$artwork_source = get_post_meta($product->id, '_artwork_source', true);
	$artwork_allow_later = get_post_meta($product->id, '_artwork_allow_later', true);
	$artwork_file_count = (int)get_post_meta($product_id, '_artwork_file_count', true);
	$artwork_afile_types = get_post_meta($product_id, '_artwork_afile_types', true);

	// Availability
	$availability      = $product->get_availability();
	$availability_html = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>';

	echo apply_filters( 'woocommerce_stock_html', $availability_html, $availability['availability'], $product );
?>

<?php if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart add-cart-form" method="post" enctype='multipart/form-data'>
	 	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	 	<?php
	 		if ( ! $product->is_sold_individually() ) {
	 			woocommerce_quantity_input( array(
	 				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
	 				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product ),
	 				'input_value' => ( isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : 1 )
	 			) );
	 		}
	 	?>

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
	 	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->id ); ?>" />
		<input type="hidden" name="print_products_checkout_process_action" value="add-to-cart">
		<input type="hidden" name="product_type" value="simple" class="product-type">
		<input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
		<input type="hidden" name="atcaction" class="atc-action" value="design">
		<input type="hidden" name="artworkfiles" class="artwork-files" value="">

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
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
<?php endif; ?>
