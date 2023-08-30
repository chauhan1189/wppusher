<?php
global $wpdb;
$print_products_plugin_aec = get_option("print_products_plugin_aec");
if (!isset($print_products_plugin_aec['upload_widget_text'])) {
	$print_products_plugin_aec['upload_widget_text'] = __('Drag files here.', 'wp2print');
	update_option("print_products_plugin_aec", $print_products_plugin_aec);
}

$is_modify = false;
if (isset($_GET['modify']) && strlen($_GET['modify'])) {
	$cart_item_key = $_GET['modify'];
	$prod_cart_data = $wpdb->get_row(sprintf("SELECT * FROM %sprint_products_cart_data WHERE cart_item_key = '%s'", $wpdb->prefix, $cart_item_key));
	if ($prod_cart_data) {
		$is_modify = true;
		$artwork_files = unserialize($prod_cart_data->artwork_files);
		$additional = unserialize($prod_cart_data->additional);
		$project_name = $additional['project_name'];
	}
}
?>
<div class="print-products-area aec-product-left">
	<div class="project-name-area">
		<label><?php _e('Project name', 'wp2print'); ?>:</label>
		<div class="pname-fld"><input type="text" name="project_name" class="project-name" value="<?php echo $project_name; ?>" onblur="matrix_set_project_name()"></div>
		<div class="project-name-error" style="display:none;"><?php _e('Project name cannot be empty.', 'wp2print'); ?></div>
	</div>
	<?php if ($is_modify) { ?>
		<div class="files-list"><br />
			<label><?php _e('Files', 'wp2print'); ?>:</label>
			<div class="cf">
			<?php foreach($artwork_files as $artwork_file) { ?>
				<?php echo basename($artwork_file); ?><br />
			<?php } ?>
			</div>
		</div>
	<?php } else { ?>
		<div class="uploader-warn-message" style="display:none;">
			<p class="warn-message-ie" style="display:none;"><?php _e('This application is not compatible with the Internet Explorer browser. Please switch to a different browser.', 'wp2print'); ?></p>
			<p class="warn-message-safari" style="display:none;"><?php _e('This application is not compatible with the Safari browser. Please switch to a different browser.', 'wp2print'); ?></p>
			<p class="warn-message-32" style="display:none;"><?php _e('Your are running a 32-bit version of your browser. Please limit the files you upload to a maximum of 30Mb.', 'wp2print'); ?></p>
		</div>
		<div id="universalUploader_holder" class="uploader-holder" style="position:relative;">
			<noscript>For full functionality of this site it is necessary to enable JavaScript.</noscript>
			<div class="uploader-placeholder"><span><?php echo $print_products_plugin_aec['upload_widget_text']; ?></span></div>
		</div>
	<?php } ?>
</div>