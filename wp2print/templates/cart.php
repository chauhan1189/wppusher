<?php
/**
 * Cart Page
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 4.0
 */

global $woocommerce, $wpdb, $attribute_names, $terms_names;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

print_products_price_matrix_attr_names_init();

wc_print_notices();

do_action( 'woocommerce_before_cart' ); ?>

<form action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

<?php do_action( 'woocommerce_before_cart_table' ); ?>

<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
	<thead>
		<tr>
			<th class="product-remove">&nbsp;</th>
			<th class="product-thumbnail">&nbsp;</th>
			<th class="product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-quantity"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
			<th class="product-price"><?php _e( 'Price', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php do_action( 'woocommerce_before_cart_contents' ); ?>

		<?php
		if (!print_products_designer_installed()) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$artwork_files = false;
					$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
					?>
					<tr class="wp2print-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

						<td class="product-remove" style="max-width:40px;">
							<?php
								echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
									'<a href="%s" class="remove" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
									esc_url( WC()->cart->get_remove_url( $cart_item_key ) ),
									__( 'Remove this item', 'woocommerce' ),
									esc_attr( $product_id ),
									esc_attr( $_product->get_sku() )
								), $cart_item_key );
							?>
						</td>

						<td class="product-thumbnail">
							<?php do_action('print_products_cart_product_thumbnail', $prod_cart_data, $_product, $cart_item, $cart_item_key, false); ?>
						</td>

						<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>" style="line-height:19px;">
							<?php
								if (!$_product->is_visible() || (!empty($_product->variation_id) && !$_product->parent_is_visible() )) {
									echo apply_filters('woocommerce_in_cart_product_title', $_product->get_title(), $cart_item, $cart_item_key);
								} else {
									printf('<a href="%s"><strong>%s</strong></a>', esc_url(get_permalink(apply_filters('woocommerce_in_cart_product_id', $cart_item['product_id']))), apply_filters('woocommerce_in_cart_product_title', $_product->get_title(), $cart_item, $cart_item_key));
								}

								$item_sku = '';
								if ($prod_cart_data) {
									$item_sku = print_products_get_item_sku($prod_cart_data);
								}
								if (!$item_sku) { $item_sku = $_product->get_sku(); }
								if ($item_sku) { echo ' &ndash; ('.$item_sku.')'; }
								echo '<br/>';

								// attributes
								if ($prod_cart_data) {
									print_products_product_attributes_list_html($prod_cart_data);
								}

								// Meta data
								echo wc_get_formatted_cart_item_data($cart_item);

								// Backorder notification
								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
									echo '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>';
								}
							?>
						</td>

						<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
							<?php
							$ptype = print_products_get_type($_product->id);
							if (print_products_is_wp2print_type($ptype)) {
								$product_quantity = sprintf( esc_attr($cart_item['quantity']).' <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item_key, $cart_item['quantity'] );
							} else {
								if ( $_product->is_sold_individually() ) {
									$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
								} else {
									$product_quantity = woocommerce_quantity_input( array(
										'input_name'  => "cart[{$cart_item_key}][qty]",
										'input_value' => $cart_item['quantity'],
										'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
										'min_value'   => '0'
									), $_product, false );
								}
							}
							echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
							?>
						</td>

						<td class="product-subtotal" data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>">
							<?php
							$cart_item_subtotal = $woocommerce->cart->get_product_subtotal($_product, $cart_item['quantity']);
							if ($prod_cart_data) {
								if (print_products_is_wp2print_type($prod_cart_data->product_type)) {
									$cart_item_subtotal = woocommerce_price($prod_cart_data->price);
								} else {
									$cart_item_subtotal = woocommerce_price($prod_cart_data->price * $prod_cart_data->quantity);
								}
							}
							echo apply_filters('woocommerce_cart_item_subtotal', $cart_item_subtotal, $cart_item, $cart_item_key);
							?>
						</td>

					</tr>
					<?php
				}
			}
		}

		do_action( 'woocommerce_cart_contents' );
		?>
		<tr>
			<td colspan="6" class="actions">

				<?php if ( WC()->cart->coupons_enabled() ) { ?>
					<div class="coupon">

						<label for="coupon_code"><?php _e( 'Coupon', 'woocommerce' ); ?>:</label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <input type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>" />

						<?php do_action( 'woocommerce_cart_coupon' ); ?>
					</div>
				<?php } ?>

				<input type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>" />

				<?php do_action( 'woocommerce_cart_actions' ); ?>

				<?php wp_nonce_field( 'woocommerce-cart' ); ?>
			</td>
		</tr>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
	</tbody>
</table>

<?php do_action( 'woocommerce_after_cart_table' ); ?>

</form>

<?php $theme = get_option('template');
if ($theme == 'outstock') { ?>
	<div class="row">
		<div class="col-xs-12 col-md-6">
			<?php do_action( 'woocommerce_cart_collaterals' ); ?>
		</div>
		<div class="col-xs-12 col-md-6">
			<?php woocommerce_cart_totals(); ?>
		</div>
	</div>
<?php } else { ?>
	<div class="cart-collaterals">
		<?php do_action( 'woocommerce_cart_collaterals' ); ?>
	</div>
<?php } ?>

<?php do_action( 'woocommerce_after_cart' ); ?>
