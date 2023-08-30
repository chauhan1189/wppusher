<?php
/**
 * Order Item Details
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 4.0
 */
global $wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}
?>
<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
	<td class="product-name">
		<?php
			$is_visible = $product && $product->is_visible();
			$order_item_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_order_items WHERE item_id = '%s'", $wpdb->prefix, $item_id));

			echo apply_filters( 'woocommerce_order_item_name', $is_visible ? sprintf( '<a href="%s">%s</a>', get_permalink( $item['product_id'] ), $item['name'] ) : $item['name'], $item, $is_visible );

			if ($order_item_data) {
				$sku = print_products_get_item_sku($order_item_data);
				if (strlen($sku)) {
					echo ' &ndash; (' . esc_html($sku) . ')';
				}
			}

			echo apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', $item['qty'] ) . '</strong>', $item );

			do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order );

			$order->display_item_meta( $item );
			echo wc_display_item_downloads( $item );

			if ($order_item_data) {
				$item_status = wc_get_order_item_meta($item_id, '_item_status', true);
				print_products_product_attributes_list_html($order_item_data);
				if (print_products_allow_modify_files($item_status) && !is_checkout()) {
					print_products_product_modify_list_html($item_id, $order_item_data);
				} else {
					print_products_product_thumbs_list_html($order_item_data);
				}
			}

			$designer_image = wc_get_order_item_meta($item_id, '_image_link', true);
			if (strlen($designer_image)) {
				$dimages = explode(',', $designer_image); ?>
				<div class="print-products-area">
					<ul class="product-attributes-list">
						<li><?php _e('Designer File', 'wp2print'); ?>:</li>
						<li>
							<ul class="product-artwork-files-list">
								<?php foreach($dimages as $dimage) { ?>
									<li><a href="<?php echo $dimage; ?>" rel="prettyPhoto" data-rel="prettyPhoto[<?php echo $item_id; ?>]"><img src="<?php echo $dimage; ?>" width="100" style="width:70px;border:1px solid #C1C1C1;"></a></li>
								<?php } ?>
							</ul>
						</li>
					</ul>
				</div>
			<?php }
			do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order );
			echo '<div class="clear"></div>';
			$myaccount_page_id = (int)get_option('woocommerce_myaccount_page_id');
			if ($myaccount_page_id && is_page($myaccount_page_id)) {
				if ($order_item_data) {
					$matched_attributes = print_products_match_order_item_attributes($order_item_data);
					if ($matched_attributes) {
						$smparams = print_products_get_order_item_smparams($order_item_data);
						$fmparams = print_products_get_order_item_fmparams($order_item_data);
					} else { ?>
						<div class="pp-reorder-error">
							<?php _e('This product can no longer be purchased on this website', 'wp2print'); ?>
						</div>
						<?php
					}
					if ($order_item_data->atcaction == 'design') { ?>
						<div class="reorder-buttons">
							<input type="button" value="<?php _e('Reorder with no changes', 'wp2print'); ?>" class="button black-btn" onclick="<?php if (!$matched_attributes) { ?>reorder_error();<?php } else { ?>reorder_product_action(<?php echo $item_id; ?>, 'designnochange');<?php } ?>">
							<input type="button" value="<?php _e('Reorder with design change', 'wp2print'); ?>" class="button black-btn" onclick="<?php if (!$matched_attributes) { ?>reorder_error();<?php } else { ?>reorder_product_action(<?php echo $item_id; ?>, 'design');<?php } ?>">
						</div>
					<?php } else if ($order_item_data->atcaction == 'artwork') { ?>
						<div class="reorder-buttons">
							<input type="button" value="<?php _e('Reorder', 'wp2print'); ?>" class="button black-btn" onclick="<?php if (!$matched_attributes) { ?>reorder_error();<?php } else { ?>reorder_product_action(<?php echo $item_id; ?>, 'artwork');<?php } ?>">
						</div>
					<?php } ?>
					<form method="POST" class="history-reorder-form-<?php echo $item_id; ?>">
						<input type="hidden" name="print_products_checkout_process_action" value="reorder">
						<input type="hidden" name="add-to-cart" value="<?php echo $order_item_data->product_id; ?>">
						<input type="hidden" name="smparams" value="<?php echo $smparams; ?>">
						<input type="hidden" name="fmparams" value="<?php echo $fmparams; ?>">
						<input type="hidden" name="reorder_order_id" value="<?php echo $order->get_id(); ?>">
						<input type="hidden" name="reorder_item_id" value="<?php echo $item_id; ?>">
						<input type="hidden" name="quantity" value="<?php echo $order_item_data->quantity; ?>">
						<input type="hidden" name="atcaction" class="atc-action">
						<input type="hidden" name="redesign" class="redesign-fld">
					</form>
				<?php }
			}
		?>
	</td>
	<td class="product-total" style="vertical-align:top;">
		<?php echo $order->get_formatted_line_subtotal( $item ); ?>
	</td>
</tr>
<?php if ( $order->has_status( array( 'completed', 'processing' ) ) && ( $purchase_note = get_post_meta( $product->id, '_purchase_note', true ) ) ) : ?>
<tr class="product-purchase-note">
	<td colspan="3"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); ?></td>
</tr>
<?php endif; ?>
