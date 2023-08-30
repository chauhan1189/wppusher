<?php
$file_upload_max_size = get_option('print_products_file_upload_max_size');
$file_upload_target = get_option("print_products_file_upload_target");
$amazon_s3_settings = get_option("print_products_amazon_s3_settings");

if (!$file_upload_max_size) { $file_upload_max_size = 2; }
if (!$artwork_file_count) { $artwork_file_count = 25; }
if (!is_array($artwork_afile_types)) { $artwork_afile_types = array('all'); }
if (!count($artwork_afile_types)) { $artwork_afile_types = array('all'); }

$umime_types = '';
if (!in_array('all', $artwork_afile_types)) {
	$umime_types = '{title : "Specific files", extensions : "'.implode(',', $artwork_afile_types).'"}';
	$umime_types = str_replace('jpg/jpeg', 'jpg,jpeg', $umime_types);
}

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
	<div style="position:absolute;left:-20000px;">
		<div id="upload-artwork" class="upload-artwork-block print-products-area" style="margin:30px 30px 0; border:1px solid #C1C1C1; padding:20px; width:600px; height:400px;">
			<p style="margin:0 0 12px;"><?php _e('Please select artwork files', 'wp2print'); ?>:</p>
			<div id="filelist" class="ua-files-list" style="padding:10px 0; border-top:1px solid #C1C1C1; border-bottom:1px solid #C1C1C1;">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
			<div id="uacontainer" class="artwork-buttons">
				<a id="pickfiles" href="javascript:;" class="artwork-select"><?php _e('Select files', 'wp2print'); ?></a>
				<a id="uploadfiles" href="javascript:;" class="artwork-upload"><?php _e('Upload files', 'wp2print'); ?></a>
				<a id="continuebtn" href="javascript:;" class="artwork-continue"><?php _e('CONTINUE >>', 'wp2print'); ?></a>
				<img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/ajax-loading.gif" class="upload-loading" style="display:none;">
				<?php do_action('print_products_upload_artwork_buttons'); ?>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.upload-artwork-btn').click(function(){
			if (jQuery(this).hasClass('ch-price')) {
				if (price < 0) { return false; }
			}
			jQuery.colorbox({inline:true, href:"#upload-artwork"});
		});
		jQuery('#uploadfiles').click(function(){
			if (ufadded) {
				uploader.start();
			}
			return false;
		});
		jQuery('#continuebtn').click(function(){
			jQuery.colorbox.close();
			jQuery('.atc-action').val('artwork');
			jQuery('.add-cart-form').submit();
			return false;
		});
		var ufilecount = <?php echo $artwork_file_count; ?>;
		var ufilenum = 0;
		var ufadded = false;
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
				max_file_size : '<?php echo $file_upload_max_size; ?>mb',
				mime_types: [<?php echo $umime_types; ?>]
			},
			<?php if ($upload_to == 'amazon') { ?>
			multipart: true,
			<?php echo $multiparams; ?>
			<?php } ?>
			init: {
				PostInit: function() {
					jQuery('.add-cart-form .artwork-files').val('');
					jQuery('#filelist').html('').hide();
					jQuery('#uploadfiles').hide();
					jQuery('#continuebtn').hide();
				},
				FilesAdded: function(up, files) {
					var ucounterror = false;
					ufadded = true;
					jQuery('#filelist').show();
					plupload.each(files, function(file) {
						ufilenum++;
						if (ufilenum <= ufilecount) {
							document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
						} else {
							ucounterror = true;
						}
					});
					jQuery('#uploadfiles').show();
					jQuery('#continuebtn').hide();
					if (ucounterror) {
						alert("<?php _e('Max files count is', 'wp2print'); ?> "+ufilecount);
					}
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
					jQuery('.upload-loading').show();
				},
				<?php } ?>
				FileUploaded: function(up, file, response) {
					<?php if ($upload_to == 'amazon') { ?>
						var ufileurl = '<?php echo $amazon_file_url; ?>'+file.name;
					<?php } else { ?>
						var ufileurl = response['response'];
					<?php } ?>
					if (ufileurl != '') {
						var artworkfiles = jQuery('.add-cart-form .artwork-files').val();
						if (artworkfiles != '') { artworkfiles += ';'; }
						artworkfiles += ufileurl;
						jQuery('.add-cart-form .artwork-files').val(artworkfiles);
					}
				},
				UploadComplete: function(files) {
					ufadded = false;
					jQuery('.upload-loading').hide();
					var show_continuebtn = true;
					if (jQuery('.add-cart-form .autofitter-action').length) {
						var ufiles = jQuery('.add-cart-form .artwork-files').val().split(';');
						if (ufiles.length == 1) {
							var furl = ufiles[0];
							var fext = furl.substring(furl.length - 3);
							if (fext == 'pdf' || fext == 'PDF') {
								show_continuebtn = false;
								jQuery('#pickfiles').hide();
								jQuery('#uploadfiles').hide();
								jQuery('a.autofitter-btn').show();
							}
						}
					}
					if (show_continuebtn) {
						jQuery('#continuebtn').show();
					}
				},
				Error: function(up, err) {
					alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
				}
			}
		});
		uploader.init();
	});
	</script>
	<?php do_action('print_products_upload_artwork_script'); ?>
