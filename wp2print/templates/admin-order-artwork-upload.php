<?php
$file_upload_target = get_option("print_products_file_upload_target");
$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
$email_options = get_option("print_products_email_options");

$umime_types = '';

$upload_to = 'host';
$plupload_url = get_bloginfo('url').'/index.php?ajaxupload=artwork';
if ($file_upload_target == 'amazon' && $amazon_s3_settings['s3_access_key'] && $amazon_s3_settings['s3_secret_key']) {
	$upload_to = 'amazon';

	$s3_data = print_products_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size);
	$plupload_url = $s3_data['amazon_url'];
	$amazon_file_url = $s3_data['amazon_file_url'];
	$multiparams = $s3_data['multiparams'];
}
?>
<div style="display:none;">
	<div id="au-upload-artwork" class="print-products-area order-upload-pdf" style="margin:30px 30px 0; border:1px solid #C1C1C1; padding:20px; width:600px; height:400px;">
		<p style="margin:0 0 12px;"><?php _e('Please select file', 'wp2print'); ?>:</p>
		<div id="aufilelist" class="ua-files-list" style="padding:10px 0; border-top:1px solid #C1C1C1; border-bottom:1px solid #C1C1C1;">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
		<div id="aucontainer" class="artwork-buttons">
			<a id="selectfiles" href="javascript:;" class="<?php if (is_admin()) { echo 'button '; } ?>artwork-select"><?php _e('Select file', 'wp2print'); ?></a>
		</div>
		<div class="clear"></div>
		<form method="POST" class="artwork-upload-form">
		<input type="hidden" name="artwork_upload_action" value="upload">
		<input type="hidden" name="item_id" class="au-item-id">
		<input type="hidden" name="au_file" class="au-file">
		<input type="hidden" name="au_new_file" class="au-new-file">
		<input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<div class="order-proof-submit">
			<a id="auuploadfiles" href="javascript:;" class="button button-primary" style="display:none;"><?php _e('Submit', 'wp2print'); ?></a>
		</div>
		</form>
	</div>
</div>
<script type="text/javascript">
function order_artwork_replace(item_id, findex) {
	jQuery('.artwork-upload-form .au-item-id').val(item_id);
	jQuery('.artwork-upload-form .au-file').val(findex);
	jQuery.colorbox({inline:true, href:"#au-upload-artwork"});
	return false;
}
jQuery(document).ready(function() {
	var au_uploader = new plupload.Uploader({
		runtimes : 'html5,flash,silverlight,html4',
		file_data_name: 'file',
		browse_button : 'selectfiles', // you can pass an id...
		container: document.getElementById('aucontainer'), // ... or DOM Element itself
		flash_swf_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.swf',
		silverlight_xap_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.xap',
		drop_element: document.getElementById('au-upload-artwork'), // ... or DOM Element itself
		url : '<?php echo $plupload_url; ?>',
		dragdrop: true,
		filters : {
			mime_types: [<?php echo $umime_types; ?>]
		},
		<?php if ($upload_to == 'amazon') { ?>
		multipart: true,
		<?php echo $multiparams; ?>
		<?php } ?>
		init: {
			PostInit: function() {
				jQuery('#aufilelist').html('').hide();
				document.getElementById('auuploadfiles').onclick = function() {
					au_uploader.start();
					jQuery('#auuploadfiles').attr('disabled', 'disabled');
					return false;
				};
			},
			FilesAdded: function(up, files) {
				jQuery('#aufilelist').show();
				plupload.each(files, function(file) {
					document.getElementById('aufilelist').innerHTML = '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
				});
				jQuery('#auuploadfiles').removeAttr('disabled');
				jQuery('#auuploadfiles').show();
			},
			UploadProgress: function(up, file) {
				document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
			},
			<?php if ($upload_to == 'amazon') { ?>
			BeforeUpload: function(up, file) {
				var regex = /(?:\.([^.]+))?$/;
				for (var i = 0; i < up.files.length; i++) {
					if (file.id == up.files[i].id) {
						up.settings.multipart_params['Content-Type'] = 'application/pdf';
					}
				}
				up.settings.multipart_params['Content-Disposition'] = 'attachment';
			},
			<?php } ?>
			FileUploaded: function(up, file, response) {
				<?php if ($upload_to == 'amazon') { ?>
					var ufileurl = '<?php echo $amazon_file_url; ?>'+file.name;
				<?php } else { ?>
					var ufileurl = response['response'];
				<?php } ?>
				if (ufileurl != '') {
					jQuery('.artwork-upload-form .au-new-file').val(ufileurl);
				}
			},
			UploadComplete: function(files) {
				jQuery('form.artwork-upload-form').submit();
			},
			Error: function(up, err) {
				alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
			}
		}
	});
	au_uploader.init();
});
</script>
