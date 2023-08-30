<?php
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
$artworkfiles = '';
if (isset($product_data['artworkfiles'])) { $artworkfiles = $product_data['artworkfiles']; }
?>
	<div id="upload-artwork" class="upload-artwork-block" style="border:1px solid #C1C1C1; padding:10px; margin-top:20px;">
		<p style="margin:0 0 0px;"><label><?php _e('Artwork Files', 'wp2print'); ?>:</label></p>
		<div id="filelist" class="ua-files-list" style="padding:10px 0; margin-bottom:10px; border-top:1px solid #C1C1C1; border-bottom:1px solid #C1C1C1;">
			<?php if (strlen($artworkfiles)) {
				$afiles = explode(';', $artworkfiles);
				foreach($afiles as $afile) {
					echo '<div>'.basename($afile).'</div>';
				} ?>
			<?php } ?>
		</div>
		<div id="uacontainer" class="artwork-buttons">
			<a id="pickfiles" href="javascript:;" class="button artwork-select"><?php _e('Select files', 'wp2print'); ?></a>
			<img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/ajax-loading.gif" class="upload-loading" style="margin:6px 0 0 4px; display:none;">
		</div>
	</div>
	<input type="hidden" name="artworkfiles" value="<?php echo $artworkfiles; ?>" class="artwork-files">
	<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
	<script type="text/javascript">
	var uploader = false;
	var squploaded = true;
	jQuery(document).ready(function() {
		uploader = new plupload.Uploader({
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
				max_file_size : '<?php echo $file_upload_max_size; ?>mb'
			},
			<?php if ($upload_to == 'amazon') { ?>
			multipart: true,
			<?php echo $multiparams; ?>
			<?php } ?>
			init: {
				PostInit: function() {
					<?php if (!strlen($artworkfiles)) { ?>jQuery('#filelist').html('').hide();<?php } ?>
				},
				FilesAdded: function(up, files) {
					jQuery('#filelist').show();
					plupload.each(files, function(file) {
						document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
					});
					squploaded = false;
				},
				UploadProgress: function(up, file) {
					document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
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
						var artworkfiles = jQuery('.send-quote-form .artwork-files').val();
						if (artworkfiles != '') { artworkfiles += ';'; }
						artworkfiles += ufileurl;
						jQuery('.send-quote-form .artwork-files').val(artworkfiles);
					}
				},
				UploadComplete: function(files) {
					jQuery('.upload-loading').hide();
					squploaded = true;
					jQuery('.send-quote-form').submit();
				},
				Error: function(up, err) {
					alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
				}
			}
		});
		uploader.init();
	});
	</script>
