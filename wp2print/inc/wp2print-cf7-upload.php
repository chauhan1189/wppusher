<?php
add_action('wpcf7_init', 'wp2print_cf7_upload_add_widget');
function wp2print_cf7_upload_add_widget() {
	wpcf7_add_shortcode('wp2print_files_upload', 'wp2print_cf7_upload_widget_handler');
}

function wp2print_cf7_upload_widget_handler($tag) {
	$print_products_info_form_options = get_option("print_products_info_form_options");
	$file_upload_max_size = get_option('print_products_file_upload_max_size');
	$file_upload_target = get_option("print_products_file_upload_target");
	$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
	if (!$file_upload_max_size) { $file_upload_max_size = 2; }
	$upload_to = 'host';
	$plupload_url = get_bloginfo('url').'/index.php?ajaxupload=artwork';
	if ($file_upload_target == 'amazon' && $amazon_s3_settings['s3_access_key'] && $amazon_s3_settings['s3_secret_key']) {
		$upload_to = 'amazon';

		$s3_data = print_products_amazon_s3_get_data($amazon_s3_settings, $file_upload_max_size);
		$plupload_url = $s3_data['amazon_url'];
		$amazon_file_url = $s3_data['amazon_file_url'];
		$multiparams = $s3_data['multiparams'];
	}
	ob_start(); ?>
	<div class="wp2print-info-form">
	<div id="uploadblock" class="uploads-box">
		<div class="uploads-fields">
			<div id="uplcontainer" class="upload-buttons">
				<a id="selectfiles" href="javascript:;" class="select-btn"><?php _e('Select files', 'wp2print'); ?></a>
				<img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/ajax-loading.gif" class="upload-loading" style="display:none;">
				<a id="uploadfiles" href="javascript:;" class="upload-btn" style="visibility:hidden;"><?php _e('Upload files', 'wp2print'); ?></a>
			</div>
			<div id="filelist" class="files-list"></div>
		</div>
		<input type="hidden" name="uploaded_files" class="wif-uploaded-files">
	</div>
	</div>
	<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
	<script>
	var uploaded = false;
	jQuery(document).ready(function() {
		var uploader = new plupload.Uploader({
			runtimes : 'html5,flash,silverlight,html4',
			file_data_name: 'file',
			browse_button : 'selectfiles', // you can pass an id...
			container: document.getElementById('uplcontainer'), // ... or DOM Element itself
			flash_swf_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.swf',
			silverlight_xap_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.xap',
			drop_element: document.getElementById('uploadblock'), // ... or DOM Element itself
			url : '<?php echo $plupload_url; ?>',
			dragdrop: true,
			filters : {
				max_file_size : '<?php echo $file_upload_max_size; ?>mb'
			},
			<?php if ($upload_to == 'amazon') { ?>
			multipart: true,
			<?php echo $multiparams; ?>
			<?php } ?>
			init: {
				PostInit: function() {
					jQuery('#filelist').html('').hide();

					document.getElementById('uploadfiles').onclick = function() {
						uploader.start();
						jQuery('.upload-loading').css('visibility', 'visible');
						return false;
					};
				},
				FilesAdded: function(up, files) {
					jQuery('#filelist').show();
					plupload.each(files, function(file) {
						document.getElementById('filelist').innerHTML += '<span id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></span>';
					});
				},
				UploadProgress: function(up, file) {
					document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = file.percent + "%";
				},
				<?php if ($upload_to == 'amazon') { ?>
				BeforeUpload: function(up, file) {
					var regex = /(?:\.([^.]+))?$/;
					for (var i = 0; i < up.files.length; i++) {
						if (file.id == up.files[i].id) {
							var ext = regex.exec(up.files[i].name)[1];
							if (ext == 'pdf') {
								up.settings.multipart_params['Content-Type'] = 'application/pdf';
							} else {
								up.settings.multipart_params['Content-Type'] = file.type;
							}
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
						var artworkfiles = jQuery('#uploadblock .wif-uploaded-files').val();
						if (artworkfiles != '') { artworkfiles += ';'; }
						artworkfiles += ufileurl;
						jQuery('#uploadblock .wif-uploaded-files').val(artworkfiles);
					}
				},
				UploadComplete: function(files) {
					jQuery('.upload-loading').css('visibility', 'hidden');
					uploaded = true;
					jQuery('.uploads-box').parents('form.wpcf7-form').submit();
				},
				Error: function(up, err) {
					alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
				}
			}
		});
		uploader.init();
		jQuery('.uploads-box').parents('form.wpcf7-form').find('.wpcf7-submit').click(function(e){
			if (jQuery('#filelist span').size()) {
				if (!uploaded) {
					jQuery('#uploadfiles').trigger('click');
					return false;
				}
			}
			return true;
		});
	});
	document.addEventListener('wpcf7mailsent', function( event ) {
		uploaded = false;
		jQuery('#uploadblock .wif-uploaded-files').val('');
		jQuery('.uploads-box .files-list').html('');
	}, false);
	</script>
	<?php
	return ob_get_clean();
}

add_filter('wpcf7_posted_data', 'wp2print_cf7_upload_posted_data');
function wp2print_cf7_upload_posted_data($posted_data) {
	if (isset($_REQUEST['uploaded_files']) && strlen($_REQUEST['uploaded_files'])) {
		$uploaded_files = explode(';', $_REQUEST['uploaded_files']);
		foreach($uploaded_files as $ukey => $ufile) {
			$uploaded_files[$ukey] = print_products_get_amazon_file_url($ufile);
		}
		$posted_data['wp2print_files_upload'] = implode(chr(10).chr(10), $uploaded_files);
	}
	return $posted_data;
}

add_filter('rest_authentication_errors', 'wp2print_cf7_upload_rest_authentication_errors');
function wp2print_cf7_upload_rest_authentication_errors() { return true; }
?>