<?php
$cart = WC()->cart->get_cart();
$file_upload_max_size = get_option('print_products_file_upload_max_size');
$file_upload_target = get_option("print_products_file_upload_target");
$amazon_s3_settings = get_option("print_products_amazon_s3_settings");
$email_options = get_option("print_products_email_options");

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
if ($cart) { ?>
<div style="position:absolute;left:-20000px;">
	<?php $cind = 0; foreach ($cart as $cart_item_key => $values) { ?>
		<div id="upload-artwork<?php echo $cind; ?>" class="upload-artwork-block print-products-area" style="margin:30px 30px 0; border:1px solid #C1C1C1; padding:20px; width:600px; height:400px;">
			<p style="margin:0 0 12px;"><?php _e('Please select file(s)', 'wp2print'); ?>:</p>
			<div id="filelist<?php echo $cind; ?>" class="ua-files-list" style="padding:10px 0; border-top:1px solid #C1C1C1; border-bottom:1px solid #C1C1C1;">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
			<div id="uacontainer<?php echo $cind; ?>" class="artwork-buttons">
				<a id="pickfiles<?php echo $cind; ?>" href="javascript:;" class="artwork-select"><?php _e('Select files', 'wp2print'); ?></a>
				<a id="uploadfiles<?php echo $cind; ?>" href="javascript:;" class="artwork-upload"><?php _e('Upload files', 'wp2print'); ?></a>
				<img src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>images/ajax-loading.gif" class="upload-loading" style="display:none;">
			</div>
			<div class="clear"></div>
			<form method="POST" id="cartuploadform<?php echo $cind; ?>" class="cart-upload-form">
			<input type="hidden" name="cart_upload_action" value="save">
			<input type="hidden" name="cart_item_key" value="<?php echo $cart_item_key; ?>" class="cart-item-key">
			<input type="hidden" name="artwork_files" class="artwork-files">
			<input type="hidden" name="redirect_to" value="<?php the_permalink(); ?>">
			</form>
		</div>
	<?php $cind++; } ?>
</div>
<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
<script type="text/javascript">
var uploaders = [];
<?php $cind = 0; foreach ($cart as $cart_item_key => $values) { ?>
	uploaders[<?php echo $cind; ?>] = '<?php echo $cart_item_key; ?>';
<?php $cind++; } ?>

function wp2print_cart_upload_button(cikey, ftypes) {
	var cuftypes = [];
	var aind = wp2print_cart_upload_get_inex(cikey);
	if (ftypes != '') { cuftypes = [{title : "Specific files", extensions : ftypes}]; }

	wp2print_cart_upload_init(aind, cuftypes);
	jQuery.colorbox({inline:true, href:"#upload-artwork"+aind});
}

function wp2print_cart_upload_init(aind, cuftypes) {
	var uploader = new plupload.Uploader({
		runtimes : 'html5,flash,silverlight,html4',
		file_data_name: 'file',
		browse_button : 'pickfiles'+aind, // you can pass an id...
		container: document.getElementById('uacontainer'+aind), // ... or DOM Element itself
		flash_swf_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.swf',
		silverlight_xap_url : '<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/Moxie.xap',
		drop_element: document.getElementById('upload-artwork'+aind), // ... or DOM Element itself
		url : '<?php echo $plupload_url; ?>',
		dragdrop: true,
		filters : {
			max_file_size : '<?php echo $file_upload_max_size; ?>mb',
			mime_types: cuftypes
		},
		<?php if ($upload_to == 'amazon') { ?>
		multipart: true,
		<?php echo $multiparams; ?>
		<?php } ?>
		init: {
			PostInit: function() {
				jQuery('#filelist'+aind).html('').hide();
				jQuery('#uploadfiles'+aind).hide();

				document.getElementById('uploadfiles'+aind).onclick = function() {
					uploader.start();
					jQuery('#uploadfiles'+aind).attr('disabled', 'disabled');
					jQuery('#uacontainer'+aind+' .upload-loading').show();
					return false;
				};
			},
			FilesAdded: function(up, files) {
				var ucounterror = false;
				jQuery('#filelist'+aind).show();
				plupload.each(files, function(file) {
					document.getElementById('filelist'+aind).innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
				});
				jQuery('#uploadfiles'+aind).removeAttr('disabled');
				jQuery('#uploadfiles'+aind).show();
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
					var artworkfiles = jQuery('#cartuploadform'+aind+' .artwork-files').val();
					if (artworkfiles != '') { artworkfiles += ';'; }
					artworkfiles += ufileurl;
					jQuery('#cartuploadform'+aind+' .artwork-files').val(artworkfiles);
				}
			},
			UploadComplete: function(files) {
				jQuery('#uacontainer'+aind+' .upload-loading').hide();
				jQuery.colorbox.close();
				jQuery('#cartuploadform'+aind).submit();
			},
			Error: function(up, err) {
				alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
			}
		}
	});
	uploader.init();
}

function wp2print_cart_upload_get_inex(cikey) {
	for(var i=0; i<uploaders.length; i++) {
		if (uploaders[i] == cikey) {
			return i;
		}
	}
}
</script>
<?php } ?>