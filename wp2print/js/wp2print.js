jQuery(document).ready(function() {
	jQuery('.product-thumbnail').show();

	jQuery('#wp2print_billing_addresses').change(function(){
		var akey = jQuery(this).val();
		if (akey > 0) {
			wp2print_set_billing_address(akey);
		}
		return false;
	});

	jQuery('#wp2print_shipping_addresses').change(function(){
		var akey = jQuery(this).val();
		if (akey > 0) {
			wp2print_set_shipping_address(akey);
		}
		return false;
	});
	if (jQuery('.print-products-area .product-attributes-list .a-help').size()) {
		jQuery('.print-products-area .product-attributes-list .a-help').each(function(){
			var awidth = jQuery(this).parent().width() + 29;
			var aleft = awidth - 14;
			jQuery(this).find('.a-help-text').css('width', awidth+'px');
			jQuery(this).find('.a-help-text').css('margin-left', '-'+aleft+'px');
		});
	}
	jQuery('.print-products-area .product-attributes-list .a-help img').hover(
		function(){ jQuery(this).parent().find('.a-help-text').fadeIn(); },
		function(){ jQuery(this).parent().find('.a-help-text').hide(); }
	);
	jQuery('.ma-section-head').click(function(){
		var thisdiv = jQuery(this);
		var reldiv = thisdiv.attr('rel');
		if (jQuery('.'+reldiv).is(':visible')) {
			jQuery('.'+reldiv).slideUp(400, function(){
				thisdiv.removeClass('opened');
			});
		} else {
			jQuery('.'+reldiv).slideDown(400, function(){
				thisdiv.addClass('opened');
			});
		}
	});
	if (jQuery('.attribute-images').size()) {
		jQuery('.attribute-images img').click(function(){
			var relto = jQuery(this).attr('rel');
			var iclass = jQuery(this).attr('class').replace(' active', '');
			var aterm_id = iclass.replace('attribute-image-', '');
			jQuery('.'+relto+' select option').removeAttr('selected');
			jQuery('.'+relto+' select option[value="'+aterm_id+'"]').attr('selected', 'selected');
			jQuery('.'+relto+' select').trigger('change');
			matrix_image_aradio(relto, aterm_id);
			return false;
		});
		jQuery('.matrix-type-simple').each(function(){
			jQuery(this).find('.print-attributes .smatrix-attr').each(function(){
				var aid = jQuery(this).attr('data-aid');
				if (jQuery('.attribute-images-'+aid).length) {
					if (!jQuery('.attribute-images-'+aid+' img.active').length) {
						jQuery(this).trigger('change');
					}
				}
			});
		});
		jQuery('.matrix-type-finishing').each(function(){
			jQuery(this).find('.finishing-attributes .fmatrix-attr').each(function(){
				var aid = jQuery(this).attr('data-aid');
				if (jQuery('.attribute-images-'+aid).length) {
					if (!jQuery('.attribute-images-'+aid+' img.active').length) {
						jQuery(this).trigger('change');
					}
				}
			});
		});
	}
	jQuery('.upload-artwork-btn').click(function(){ jQuery('.add-cart-form .atc-action').val('artwork'); });
	jQuery('.design-online-btn').click(function(){ jQuery('.add-cart-form .atc-action').val('design'); });
	jQuery('.simple-add-btn').click(function(){ jQuery('.add-cart-form .atc-action').val('artwork'); });

	var spiniconnum = 1;
	if (jQuery('.spinning-icon').size()) {
		var sitimer = setInterval(function() {
			jQuery('.spinning-icon').each(function(){
				var thumb_src = jQuery(this).attr('data-thumb');
				var thisid = jQuery(this).attr('id');
				var imgspinning = new Image();
				imgspinning.src = thumb_src;
				imgspinning.onload = function() {
					jQuery('#'+thisid).attr('src', thumb_src);
					jQuery('#'+thisid).removeClass('spinning-icon').addClass('blitline-img');
					jQuery('#'+thisid).parent().attr('href', thumb_src);
				}
			});
			spiniconnum++;
			if (spiniconnum >= 6) {
				jQuery('.spinning-icon').each(function(){
					var icon_src = jQuery(this).attr('data-icon');
					var file_src = jQuery(this).attr('data-file');
					var thisid = jQuery(this).attr('id');
					var imgspinning = new Image();
					imgspinning.src = icon_src;
					imgspinning.onload = function() {
						jQuery('#'+thisid).attr('src', icon_src);
						jQuery('#'+thisid).removeClass('spinning-icon');
						jQuery('#'+thisid).parent().attr('href', file_src);
					}
				});
			}
			if (!jQuery('.spinning-icon').size() || spiniconnum >= 6) {
				clearInterval(sitimer);
			}
		}, 5000);
	}

	// reCaptcha
	if (jQuery('.g-recaptcha').length) {
		jQuery('form').each(function(){
			if (jQuery(this).find('.g-recaptcha').length) {
				jQuery(this).find('[type=submit]').attr('disabled', 'disabled');
			}
		});
	}
});

function wp2print_recaptcha() {
	jQuery('form').each(function(){
		if (jQuery(this).find('.g-recaptcha').length) {
			var grr = jQuery(this).find('.g-recaptcha-response').val();
			if (grr != '') {
				jQuery(this).find('[type=submit]').removeAttr('disabled');
			}
		}
	});
}

function reorder_error() {
	jQuery('.pp-reorder-error').slideDown();
}

function reorder_product_action(item_id, tp) {
	jQuery('.history-reorder-form-'+item_id+' .atc-action').val(tp);
	jQuery('.history-reorder-form-'+item_id+' .redesign-fld').val('false');
	if (tp == 'design') {
		jQuery('.history-reorder-form-'+item_id+' .redesign-fld').val('true');
	}
	jQuery('.history-reorder-form-'+item_id).submit();
}

function matrix_aradio(o) {
	var afield = jQuery(o).attr('rel');
	var oval = jQuery(o).val();
	if (jQuery(o).is(':checked')) {
		jQuery('.'+afield+' option').removeAttr('selected');
		jQuery('.'+afield+' option[value="'+oval+'"]').attr('selected', 'selected');
		jQuery('.'+afield).trigger('change');
	}
}

function matrix_image_aradio(orel, aterm_id) {
	var rarray = orel.split('-');
	var mtype_id = rarray[2];
	var mattr_id = rarray[3];
	if (jQuery('.a-radio-'+mtype_id+'-'+mattr_id).length) {
		jQuery('.a-radio-'+mtype_id+'-'+mattr_id+' input').removeAttr('checked');
		jQuery('.a-radio-'+mtype_id+'-'+mattr_id+' input[value="'+aterm_id+'"]').attr('checked', 'checked');
	}
}

function matrix_get_numbers(num, numbers) {
	var lastnum = num;
	var matrix_numbers = new Array();
	matrix_numbers[0] = 0;
	matrix_numbers[1] = 0;
	if (num > 0) {
		for (var i=0; i<numbers.length; i++) {
			anumb = parseInt(numbers[i]);
			if (num < anumb) {
				matrix_numbers[0] = lastnum;
				matrix_numbers[1] = anumb;
				return matrix_numbers;
			} else if (num == anumb) {
				matrix_numbers[0] = anumb;
				matrix_numbers[1] = anumb;
				return matrix_numbers;
			}
			lastnum = anumb;
		}
		if (numbers.length == 1) {
			matrix_numbers[0] = numbers[0];
		} else {
			matrix_numbers[0] = numbers[numbers.length - 2];
		}
		matrix_numbers[1] = lastnum;
	}
	return matrix_numbers;
}

function matrix_get_price(pmatrix, mval, nmb, nums) {
	var p = 0;
	var min_nmb = nums[0];
	var max_nmb = nums[1];
	if (nmb == min_nmb && nmb < max_nmb) {
		mval = mval + '-' + max_nmb;
		if (pmatrix[mval]) {
			p = (pmatrix[mval] / max_nmb) * nmb;
		}
	} else if (nmb == min_nmb && nmb == max_nmb) {
		mval = mval + '-' + nmb;
		if (pmatrix[mval]) {
			p = pmatrix[mval];
		}
	} else if (nmb > min_nmb && nmb < max_nmb) {
		var min_mval = mval + '-' + min_nmb;
		var max_mval = mval + '-' + max_nmb;
		if (pmatrix[min_mval] && pmatrix[max_mval]) {
			p = pmatrix[min_mval] + (nmb - min_nmb) * (pmatrix[max_mval] - pmatrix[min_mval]) / (max_nmb - min_nmb);
		}
	} else if (nmb > min_nmb && nmb > max_nmb) {
		var min_mval = mval + '-' + min_nmb;
		var max_mval = mval + '-' + max_nmb;
		if (pmatrix[min_mval] && pmatrix[max_mval]) {
			if (min_nmb == max_nmb) {
				p = pmatrix[max_mval] * nmb;
			} else {
				p = pmatrix[max_mval] + (nmb - max_nmb) * (pmatrix[max_mval] - pmatrix[min_mval]) / (max_nmb - min_nmb);
			}
		}
	}
	return p;
}

function matrix_aval(val) {
	if (val.indexOf('-') > -1) { val = wp2print_replace(val, '-', '{dc}'); }
	if (val.indexOf(':') > -1) { val = wp2print_replace(val, ':', '{cc}'); }
	if (val.indexOf('|') > -1) { val = wp2print_replace(val, '|', '{vc}'); }
	return val;
}

function wp2print_set_billing_address(akey) {
	var avals = wp2print_billing_address[akey].split('|');
	jQuery('#billing_first_name').val(avals[0]);
	jQuery('#billing_last_name').val(avals[1]);
	jQuery('#billing_company').val(avals[2]);
	jQuery('#billing_country').val(avals[3]).change();
	jQuery("#billing_country_chosen").find('span').html(jQuery('#billing_country option[value="'+avals[3]+'"]').text());
	jQuery('#billing_address_1').val(avals[4]);
	jQuery('#billing_address_2').val(avals[5]);
	jQuery('#billing_city').val(avals[6]);
	jQuery('#billing_state').val(avals[7]);
	jQuery('#billing_postcode').val(avals[8]);
	jQuery('#billing_phone').val(avals[9]);
	jQuery('#billing_email').val(avals[10]);

	jQuery('#billing_state option').removeAttr('selected');
	jQuery('#billing_state option[value="'+avals[7]+'"]').attr('selected', 'selected');
	var sname = jQuery('#billing_state option[value="'+avals[7]+'"]').text();
	jQuery('#select2-billing_state-container').html(sname);
}

function wp2print_set_shipping_address(akey) {
	var avals = wp2print_shipping_address[akey].split('|');
	jQuery('#shipping_first_name').val(avals[0]);
	jQuery('#shipping_last_name').val(avals[1]);
	jQuery('#shipping_company').val(avals[2]);
	jQuery('#shipping_country').val(avals[3]).change();
	jQuery("#shipping_country_chosen").find('span').html(jQuery('#shipping_country option[value="'+avals[3]+'"]').text());
	jQuery('#shipping_address_1').val(avals[4]);
	jQuery('#shipping_address_2').val(avals[5]);
	jQuery('#shipping_city').val(avals[6]);
	jQuery('#shipping_state').val(avals[7]);
	jQuery('#shipping_postcode').val(avals[8]);

	jQuery('#shipping_state option').removeAttr('selected');
	jQuery('#shipping_state option[value="'+avals[7]+'"]').attr('selected', 'selected');
	var sname = jQuery('#shipping_state option[value="'+avals[7]+'"]').text();
	jQuery('#select2-shipping_state-container').html(sname);
}

function wp2print_trim(str) {
	if (str != 'undefined') {
		return str.replace(/^\s+|\s+$/g,"");
	} else {
		return '';
	}
}

function wp2print_wwof(pid, o) {
	var n = jQuery(o).val();
	jQuery('.wp2print-wwof-prices-'+pid+' span.price').hide();
	jQuery('.wp2print-wwof-prices-'+pid+' span.price-qty-'+n).fadeIn();
}

function wp2print_email_quote() {
	var eqerror = '';
	var email = wp2print_trim(jQuery('.email-quote-box .eq-email').val());
	if (email == '') {
		eqerror = 'empty';
	} else if (!/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(email)) {
		eqerror = 'incorrect';
	}
	jQuery('.email-quote-box .eq-errors, .email-quote-box .eq-errors span').hide();
	if (eqerror == '') {
		var ptype = jQuery('.add-cart-form input.product-type').val();
		wp2print_email_quote_request(email, ptype);
		jQuery('.email-quote-box .eq-success').slideDown();
		setTimeout(function(){ jQuery('.email-quote-box .eq-success').slideUp(); }, 5000);
	} else {
		jQuery('.email-quote-box .eq-errors .error-'+eqerror).show();
		jQuery('.email-quote-box .eq-errors').slideDown();
	}
}

function wp2print_email_quote_request(email, tp) {
	var site_url = jQuery('.email-quote-box .email-quote-form').attr('action');
	var product_id = jQuery('.add-cart-form input.product-id').val();
	var quantity = jQuery('.add-cart-form .quantity').val();
	var smparams = jQuery('.add-cart-form .sm-params').val();
	var fmparams = jQuery('.add-cart-form .fm-params').val();
	var price = jQuery('.add-cart-form .p-price').val();

	var rparams = {AjaxAction: 'email-quote-send', email: email, product_id: product_id, product_type: tp, quantity: quantity, smparams: smparams, fmparams: fmparams, price: price};

	if (tp == 'area') {
		rparams.width = jQuery('.add-cart-form .width').val();
		rparams.height = jQuery('.add-cart-form .height').val();
	} else if (tp == 'book') {
		var pagesqty = '';
		jQuery('.add-cart-form .quantity').each(function(){
			if (pagesqty != '') { pagesqty += ';'; }
			pagesqty += jQuery(this).val();
		});
		rparams.booksqty = jQuery('.add-cart-form .books-quantity').val();
		rparams.pagesqty = pagesqty;
	} else if (tp == 'aec' || tp == 'aecsimple') {
		rparams.project_name = jQuery('.add-cart-form .aec-project-name').val();
		rparams.total_price = jQuery('.add-cart-form .aec-total-price').val();
		rparams.total_area = jQuery('.add-cart-form .aec-total-area').val();
		rparams.total_pages = jQuery('.add-cart-form .aec-total-pages').val();
	} else if (tp == 'aecbwc') {
		rparams.project_name = jQuery('.add-cart-form .aec-project-name').val();
		rparams.total_price = jQuery('.add-cart-form .aec-total-price').val();
		rparams.total_area = jQuery('.add-cart-form .aec-total-area').val();
		rparams.total_pages = jQuery('.add-cart-form .aec-total-pages').val();
		rparams.area_bw = jQuery('.add-cart-form .aec-area-bw').val();
		rparams.pages_bw = jQuery('.add-cart-form .aec-pages-bw').val();
		rparams.area_cl = jQuery('.add-cart-form .aec-area-cl').val();
		rparams.pages_cl = jQuery('.add-cart-form .aec-pages-cl').val();
	}

	jQuery.post(site_url, rparams);
}

function wp2print_replace(s, f, r) {
	return s.replace(new RegExp(f, 'gi'), r);
}

if (jQuery('.woocommerce-MyAccount-content .ois-order-status').size()) {
	jQuery('.woocommerce-MyAccount-content mark.order-number').parent().remove();
}
