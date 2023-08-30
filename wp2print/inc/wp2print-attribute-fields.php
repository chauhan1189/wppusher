<?php
// ----------------------------------------------------------------
// add additional attributes fields
// ----------------------------------------------------------------
add_action('woocommerce_after_add_attribute_fields', 'print_products_attribute_add_attribute_fields', 12);
function print_products_attribute_add_attribute_fields() {
	?>
	<div class="form-field">
		<label for="attribute_img"><?php _e('Display attribute images', 'wp2print') ?></label>
		<select name="attribute_img">
			<option value="0"><?php _e('No', 'wp2print') ?></option>
			<option value="1"><?php _e('Yes', 'wp2print') ?></option>
		</select>
	</div>
	<div class="form-field">
		<label for="attribute_orderby"><?php _e('Help text', 'woocommerce'); ?></label>
		<?php wp_editor('', 'attribute_help_text', 'textarea_rows=5&media_buttons=0'); ?>
	</div>
	<?php
}

add_action('woocommerce_after_edit_attribute_fields', 'print_products_attribute_edit_attribute_fields', 12);
function print_products_attribute_edit_attribute_fields() {
	$attr_id = absint($_GET['edit']);
	$attribute_data = print_products_attribute_data($attr_id);
	?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="help_text"><?php _e('Display attribute images', 'wp2print'); ?></label>
		</th>
		<td><select name="attribute_img">
			<option value="0"><?php _e('No', 'wp2print') ?></option>
			<option value="1"<?php if ($attribute_data->attribute_img == 1) { echo ' SELECTED'; } ?>><?php _e('Yes', 'wp2print') ?></option>
		</select></td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="help_text"><?php _e('Help text', 'wp2print'); ?></label>
		</th>
		<td><?php wp_editor($attribute_data->attribute_help_text, 'attribute_help_text', 'textarea_rows=5&media_buttons=0'); ?></td>
	</tr>
	<?php
}

add_action('woocommerce_attribute_added', 'print_products_attribute_save_fields', 10, 2);
add_action('woocommerce_attribute_updated', 'print_products_attribute_save_fields', 10, 2);
function print_products_attribute_save_fields($attribute_id, $attribute) {
	global $wpdb;
	$update = array();
	$update['attribute_img'] = (int)$_POST['attribute_img'];
	$update['attribute_help_text'] = $_POST['attribute_help_text'];
	$wpdb->update($wpdb->prefix.'woocommerce_attribute_taxonomies', $update, array( 'attribute_id' => $attribute_id));
}

function print_products_attribute_data($attribute_id) {
	global $wpdb;
	return $wpdb->get_row(sprintf("SELECT * FROM %swoocommerce_attribute_taxonomies WHERE attribute_id = %s", $wpdb->prefix, $attribute_id));
}

// ----------------------------------------------------------------
// attribute images
// ----------------------------------------------------------------
$wp2print_attribute_images = get_option('wp2print_attribute_images');

add_action('init', 'print_products_attribute_images_init');
function print_products_attribute_images_init() {
	$attributes = print_products_get_registered_attributes();
	if (count($attributes)) {
		foreach($attributes as $aslug) {
			add_action($aslug.'_add_form_fields', 'print_products_attribute_image_field_add');
			add_action($aslug.'_edit_form_fields', 'print_products_attribute_image_field_edit', 10, 2);
			add_action('create_'.$aslug, 'print_products_attribute_image_save', 10, 2);
			add_action('edited_'.$aslug, 'print_products_attribute_image_save', 10, 2);
			add_filter('manage_edit-'.$aslug.'_columns', 'print_products_attribute_image_column_field');
			add_filter('manage_'.$aslug.'_custom_column', 'print_products_attribute_image_column_content', 10, 3);
		}
	}
}

function print_products_attribute_image_field_add($tag) {
	?>
	<div class="form-field term-image-wrap">
		<label for="customfield"><?php _e('Image', 'wp2print'); ?></label>
		<div class="uploader"></div>
		<input class="button" type="button" value="<?php _e('Upload', 'wp2print') ?>" onclick="open_media_uploader_image()" />
		<input type="hidden" name="attribute_image" class="attribute-image-id">
		<p><?php _e('Image size: 100px x 80px', 'wp2print') ?></p>

		<script>
		var media_uploader = null;
		function open_media_uploader_image()
		{
			media_uploader = wp.media({
				frame:    "post",
				state:    "insert",
				multiple: false
			});

			media_uploader.on("insert", function(){
				var json = media_uploader.state().get("selection").first().toJSON();

				var image_id = json.id;
				var image_url = json.url;
				jQuery('.uploader').html('<img src="'+image_url+'" style="width:100px;">');
				jQuery('.attribute-image-id').val(image_id);
			});

			media_uploader.open();
		}
		</script>
	</div>
	<?php
}

function print_products_attribute_image_field_edit($tag) {
	global $wp2print_attribute_images;
    $term_id = $tag->term_id;
	$wp2print_attribute_images = get_option('wp2print_attribute_images');
	$attribute_image = $wp2print_attribute_images[$term_id];
	?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="presenter_id"><?php _e('Image', 'wp2print'); ?></label>
		</th>
		<td>
			<div class="uploader"><?php if ($attribute_image) { echo '<img src="'.print_products_get_thumb($attribute_image, 100, 80, true).'">'; } ?></div>
			<input class="button" type="button" value="<?php _e('Upload', 'wp2print') ?>" onclick="open_media_uploader_image()" />
			<input class="button del-button" type="button" value="<?php _e('Delete', 'wp2print') ?>" onclick="delete_media_uploader_image()"<?php if (!$attribute_image) { echo ' style="display:none;"'; } ?> />
			<input type="hidden" name="attribute_image" value="<?php echo $attribute_image; ?>" class="attribute-image-id">
			<p class="description"><?php _e('Image size: 100px x 80px', 'wp2print') ?></p>

			<script>
			var media_uploader = null;
			function open_media_uploader_image()
			{
				media_uploader = wp.media({
					frame:    "post",
					state:    "insert",
					multiple: false
				});

				media_uploader.on("insert", function(){
					var json = media_uploader.state().get("selection").first().toJSON();

					var image_id = json.id;
					var image_url = json.url;
					jQuery('.uploader').html('<img src="'+image_url+'" style="width:100px;">');
					jQuery('.attribute-image-id').val(image_id);
					jQuery('.del-button').show();
				});

				media_uploader.open();
			}
			function delete_media_uploader_image() {
				var d = confirm('<?php _e('Are you sure?', 'wp2print'); ?>');
				if (d) {
					jQuery('.uploader').html('');
					jQuery('.attribute-image-id').val('');
					jQuery('.del-button').hide();
				}
			}
			</script>
		</td>
	</tr>
	<?php
}

function print_products_attribute_image_save($term_id) {
	global $wp2print_attribute_images;
    if (isset($_POST['attribute_image'])) {
		$wp2print_attribute_images[$term_id] = $_POST['attribute_image'];
        update_option('wp2print_attribute_images', $wp2print_attribute_images);
    }
}

function print_products_attribute_image_column_field($columns){
	$new_columns = array();
	foreach($columns as $ckey => $cval) {
		$new_columns[$ckey] = $cval;
		if ($ckey == 'name') {
			$new_columns['aimage'] = __('Image', 'wp2print');
		}
	}
    return $new_columns;
}

function print_products_attribute_image_column_content($content, $column_name, $term_id) {
	global $wp2print_attribute_images;
	if ($column_name == 'aimage') {
		$attribute_image = $wp2print_attribute_images[$term_id];
		if ($attribute_image) {
			echo '<img src="'.print_products_get_thumb($attribute_image, 30, 30, true).'" style="border:1px solid #EEE;">';
		}
	}
	return $content;
}

function print_products_get_registered_attributes() {
	global $wpdb;
	$registered_attributes = array();
	$pa_taxonomies = $wpdb->get_results(sprintf("SELECT * FROM %sterm_taxonomy WHERE taxonomy LIKE '%s'", $wpdb->prefix, 'pa_%'));
	if ($pa_taxonomies) {
		foreach($pa_taxonomies as $pa_taxonomy) {
			$registered_attributes[] = $pa_taxonomy->taxonomy;
		}
	}
	return $registered_attributes;
}
?>