<?php
$file_upload_target = get_option("print_products_file_upload_target");
$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
$email_options = get_option("print_products_email_options");

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
	<div id="upload-artwork" class="order-upload-pdf" style="margin:30px 30px 0; border:1px solid #C1C1C1; padding:20px; width:600px; height:400px;">
		<p style="margin:0 0 12px;"><?php _e('Please select PDF file(s)', 'wp2print'); ?>:</p>
		<div id="filelist" class="ua-files-list" style="padding:10px 0; border-top:1px solid #C1C1C1; border-bottom:1px solid #C1C1C1;">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
		<div id="uacontainer" class="artwork-buttons">
			<a id="pickfiles" href="javascript:;" class="button artwork-select"><?php _e('Select files', 'wp2print'); ?></a>
		</div>
		<div class="clear"></div>
		<form method="POST" class="order-proof-form">
		<input type="hidden" name="orders_proof_action" value="send">
		<input type="hidden" name="order_id" class="proof-order-id">
		<input type="hidden" name="item_id" class="proof-item-id">
		<input type="hidden" name="proof_files" class="proof-files">
		<div class="order-proof-email">
			<input type="text" name="email_subject" value="<?php echo $email_options['order_proof_subject']; ?>" class="op-email-subject" placeholder="<?php _e('Email Subject', 'wp2print'); ?>" title="<?php _e('Email Subject', 'wp2print'); ?>">
			<textarea name="email_message" class="op-email-message" placeholder="<?php _e('Email Message', 'wp2print'); ?>" title="<?php _e('Email Message', 'wp2print'); ?>"><?php echo $email_options['order_proof_message']; ?></textarea>
		</div>
		<div class="clear"></div>
		<div class="order-proof-submit">
			<a id="uploadfiles" href="javascript:;" class="button button-primary"><?php _e('Send proof', 'wp2print'); ?></a>
		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
<script type="text/javascript">
jQuery(document).ready(function() {
	var uploader = new plupload.Uploader({
		runtimes : 'html5,flash,silverlight,html4',
		file_data_name: 'file',
		browse_button : 'pickfiles', // you can pass an id...
		container: document.getElementById('uacontainer'), // ... or DOM Element itself
		flash_swf_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.swf',
		silverlight_xap_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.xap',
		drop_element: document.getElementById('upload-artwork'), // ... or DOM Element itself
		url : '<?php echo $plupload_url; ?>',
		dragdrop: true,
		filters : {
			mime_types: [{title : "Specific files", extensions : "pdf"}]
		},
		<?php if ($upload_to == 'amazon') { ?>
		multipart: true,
		<?php echo $multiparams; ?>
		<?php } ?>
		init: {
			PostInit: function() {
				jQuery('#filelist').html('').hide();
				//jQuery('#uploadfiles').hide();

				document.getElementById('uploadfiles').onclick = function() {
					uploader.start();
					jQuery('#uploadfiles').attr('disabled', 'disabled');
					return false;
				};
			},
			FilesAdded: function(up, files) {
				var ucounterror = false;
				jQuery('#filelist').show();
				plupload.each(files, function(file) {
					document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
				});
				jQuery('#uploadfiles').removeAttr('disabled');
				jQuery('#uploadfiles').show();
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
					var prooffiles = jQuery('.order-proof-form .proof-files').val();
					if (prooffiles != '') { prooffiles += ';'; }
					prooffiles += ufileurl;
					jQuery('.order-proof-form .proof-files').val(prooffiles);
				}
			},
			UploadComplete: function(files) {
				jQuery('form.order-proof-form').submit();
			},
			Error: function(up, err) {
				alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
			}
		}
	});
	uploader.init();
});
</script>
