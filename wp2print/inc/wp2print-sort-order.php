<?php
add_filter('get_terms_orderby', 'print_products_get_terms_orderby', 10, 2);
function print_products_get_terms_orderby($orderby, $args) {
	return 't.term_order';
}

function print_products_sort_order_page() {
	global $wpdb, $wp_locale;

	$post_type_taxonomies = array();
	$product_taxonomies = get_object_taxonomies('product');
	if ($product_taxonomies) {
		foreach($product_taxonomies as $product_taxonomy) {
			if (substr($product_taxonomy, 0, 3) == 'pa_') {
				$post_type_taxonomies[] = $product_taxonomy;
			}
		}
	}

	$taxonomy = isset($_GET['taxonomy']) ? sanitize_key($_GET['taxonomy']) : '';
	if ($taxonomy == '' || !taxonomy_exists($taxonomy)) {
		reset($post_type_taxonomies);   
		$taxonomy = current($post_type_taxonomies);
	}
	$taxonomy_data = get_taxonomy($taxonomy);
	?>
	<div class="wrap">
		<div class="icon32" id="icon-edit"><br></div>
		<h2><?php _e('Attribute sort order', 'wp2print'); ?></h2>
		<div id="ajax-response"></div>
		<noscript>
			<div class="error message">
				<p><?php _e("This plugin can't work without javascript, because it's use drag and drop and AJAX.", 'wp2print'); ?></p>
			</div>
		</noscript>
		<div class="clear"></div>
		<form action="edit.php" id="print_products_sort_order_form" class="pp-sort-order-form">
			<input type="hidden" name="post_type" value="product" />
			<input type="hidden" name="page" value="print-products-sort-order" />
			<?php if ($post_type_taxonomies) { ?>
				<h2 class="subtitle"><?php _e('Products Attributes', 'wp2print'); ?></h2>
				<table cellspacing="0" class="wp-list-taxonomy">
					<tbody id="the-list">
						<?php foreach ($post_type_taxonomies as $post_type_taxonomy) {
							$taxonomy_info = get_taxonomy($post_type_taxonomy); ?>
							<tr>
								<td class="check-column" scope="row"><input type="radio" onclick="print_products_select_taxonomy(this)" value="<?php echo $post_type_taxonomy; ?>" <?php if ($post_type_taxonomy == $taxonomy) { echo 'CHECKED'; } ?> name="taxonomy">&nbsp;</td>
								<td class="categories column-categories"><b><?php echo $taxonomy_info->label; ?></b></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php } ?>
			<h2 class="subtitle"><?php echo $taxonomy_data->label; ?> <?php _e('Terms', 'wp2print'); ?></h2>
			<div id="order-terms">
				<div id="post-body">                    
					<ul class="sortable" id="tto_sortable">
						<?php
						$args = array(
							'orderby'    => 'term_order',
							'depth'      => 0,
							'child_of'   => 0,
							'hide_empty' => 0
						);
						$taxonomy_terms = get_terms($taxonomy, $args);
						if (count($taxonomy_terms) > 0) {
							echo print_products_terms_tree($taxonomy_terms, $args['depth'], $args);
						}
						?>
					</ul>
					<div class="clear"></div>
				</div>
				<div class="alignleft actions">
					<p class="submit"><a href="javascript:;" class="save-order button-primary"><?php _e('Update', 'wp2print'); ?></a></p>
				</div>
			</div> 
		</form>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				var NestedSortableSerializedData;
				jQuery("ul.sortable").sortable({
						'tolerance':'intersect',
						'cursor':'pointer',
						'items':'> li',
						'axi': 'y',
						'placeholder':'placeholder',
						'nested': 'ul'
				});
			});
			
			jQuery(".save-order").bind( "click", function() {
				var mySortable = new Array();
				jQuery(".sortable").each(  function(){
					var serialized = jQuery(this).sortable("serialize");
					var parent_tag = jQuery(this).parent().get(0).tagName;
					parent_tag = parent_tag.toLowerCase()
					if (parent_tag == 'li') {
						var tag_id = jQuery(this).parent().attr('id');
						mySortable[tag_id] = serialized;
					} else {
						mySortable[0] = serialized;
					}
				});
				
				var serialize_data = print_products_serialize(mySortable);
				jQuery.post( ajaxurl, { action:'update-taxonomy-order', order: serialize_data, taxonomy : '<?php echo  $taxonomy ?>' }, function() {
					jQuery("#ajax-response").html('<div class="message updated fade"><p><?php _e('Terms Order Updated', 'wp2print'); ?></p></div>');
					jQuery("#ajax-response div").delay(3000).hide("slow");
				});
			});
		</script>
	</div>
	<?php 
}

function print_products_terms_tree($taxonomy_terms, $depth, $r) {
	$pptwalker = new Print_Products_Terms_Walker; 
	$args = array($taxonomy_terms, $depth, $r);
	return call_user_func_array(array(&$pptwalker, 'walk'), $args);
}

add_action('wp_ajax_update-taxonomy-order', 'print_products_sort_order_save');
function print_products_sort_order_save() {
	global $wpdb; 
	$taxonomy = stripslashes($_POST['taxonomy']);
	$data = stripslashes($_POST['order']);
	$unserialised_data = unserialize($data);
			
	if (is_array($unserialised_data)) {
		foreach($unserialised_data as $key => $values ) {
			$items = explode("&", $values);
			unset($item);
			foreach($items as $item_key => $item_) {
				$items[$item_key] = trim(str_replace("item[]=", "", $item_));
			}
			if (is_array($items) && count($items) > 0) {
				foreach($items as $item_key => $term_id) {
					$wpdb->update($wpdb->terms, array('term_order' => $item_key + 1), array('term_id' => $term_id));
				} 
			}
		}
	}
	die();
}

class Print_Products_Terms_Walker extends Walker {
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	function start_lvl(&$output, $depth = 0, $args = array()) {
		extract($args, EXTR_SKIP);
		
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class='children sortable'>\n";
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
		extract($args, EXTR_SKIP);
			
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $term, $depth = 0, $args = array(), $current_object_id = 0) {
		$indent = '';
		if ($depth) {
			$indent = str_repeat("\t", $depth);
		}

		$taxonomy = get_taxonomy($term->term_taxonomy_id);
		$output .= $indent . '<li class="term_type_li" id="item_'.$term->term_id.'"><div class="item"><span>'.apply_filters( 'the_title', $term->name, $term->term_id ).' </span></div>';
	}

	function end_el(&$output, $object, $depth = 0, $args = array()) {
		$output .= "</li>\n";
	}
}
?>