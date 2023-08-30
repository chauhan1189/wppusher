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
			</div>
		</div>
	</div>
	<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
	<script type="text/javascript">
	var omf_item_id = 0;
	jQuery(document).ready(function() {
		jQuery('.omf-upload-btn').click(function(){
			omf_item_id = jQuery(this).attr('rel');
			jQuery('#filelist').html('').hide();
			jQuery('#uploadfiles').hide();
			jQuery('#continuebtn').hide();
			jQuery.colorbox({inline:true, href:"#upload-artwork"});
			return false;
		});
		jQuery('#continuebtn').click(function(){
			jQuery.colorbox.close();
			return false;
		});
		var ufilecount = <?php echo $artwork_file_count; ?>;
		var ufilenum = 0;
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
					jQuery('#filelist').html('').hide();
					jQuery('#uploadfiles').hide();
					jQuery('#continuebtn').hide();

					document.getElementById('uploadfiles').onclick = function() {
						uploader.start();
						jQuery('#uploadfiles').attr('disabled', 'disabled');
						jQuery('.upload-loading').show();
						jQuery('#continuebtn').hide();
						return false;
					};
				},
				FilesAdded: function(up, files) {
					var ucounterror = false;
					jQuery('#filelist').show();
					plupload.each(files, function(file) {
						ufilenum++;
						if (ufilenum <= ufilecount) {
							document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
						} else {
							ucounterror = true;
						}
					});
					jQuery('#uploadfiles').removeAttr('disabled');
					jQuery('#uploadfiles').show();
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
				},
				<?php } ?>
				FileUploaded: function(up, file, response) {
					<?php if ($upload_to == 'amazon') { ?>
						var ufileurl = '<?php echo $amazon_file_url; ?>'+file.name;
					<?php } else { ?>
						var ufileurl = response['response'];
					<?php } ?>
					if (ufileurl != '') {
						var artworkfiles = jQuery('.orders-missing-files-form .artwork-files-'+omf_item_id).val();
						if (artworkfiles != '') { artworkfiles += ';'; }
						artworkfiles += ufileurl;
						jQuery('.orders-missing-files-form .artwork-files-'+omf_item_id).val(artworkfiles);
						omf_show_files_list();
					}
				},
				UploadComplete: function(files) {
					jQuery('.upload-loading').hide();
					jQuery('#continuebtn').show();
				},
				Error: function(up, err) {
					alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
				}
			}
		});
		uploader.init();
	});
	function omf_show_files_list() {
		var flist_html = '';
		var artworkfiles = jQuery('.orders-missing-files-form .artwork-files-'+omf_item_id).val();
		var slist = artworkfiles.split(';');
		for (var f=0; f<slist.length; f++) {
			flist_html += omf_basename(slist[f]) + '<br />';
		}
		flist_html += '<input type="button" value="<?php _e('Clear', 'wp2print'); ?>" class="act-button reject-button" style="padding:4px 10px;margin-top:5px;" onclick="omf_clear_files('+omf_item_id+');">';
		jQuery('.orders-missing-files-form .files-list-'+omf_item_id).html(flist_html);
	}
	function omf_clear_files(iid) {
		jQuery('.orders-missing-files-form .artwork-files-'+iid).val('');
		jQuery('.orders-missing-files-form .files-list-'+iid).html('');
	}
	function omf_basename(path) {
		return path.split('/').reverse()[0];
	}
	</script>
