jQuery(document).ready(function() {
	jQuery('.pmo-attributes .atermitem').click(function(){
		var ao = jQuery(this).attr('rel');
		var achecked = false;
		jQuery('.pmo-attributes .atermitem').each(function(){
			if (jQuery(this).is(':checked')) {
				achecked = true;
			}
		});
		if (achecked) {
			jQuery('.'+ao).attr('checked', 'checked');
		} else {
			jQuery('.'+ao).removeAttr('checked');
		}
	});
	jQuery('#product-type').change(function(){
		print_products_check_tax_select();
	});
	print_products_check_tax_select();
	print_products_num_type();

	// disable select checkbox for rejected orders
	if (jQuery('mark.rejected-prod').size()) {
		jQuery('mark.rejected-prod').parent().prev().find('input').attr('disabled', 'disabled');
	}
	if (jQuery('mark.await-approval').size()) {
		jQuery('mark.await-approval').parent().prev().find('input').attr('disabled', 'disabled');
	}
	if (jQuery('.order_data_column_container #order_status').size()) {
		var ostatus = jQuery('.order_data_column_container #order_status').val();
		if (ostatus == 'wc-rejected-prod' || ostatus == 'wc-await-approval') {
			jQuery('.order_data_column_container #order_status').attr('disabled', 'disabled');
			jQuery('#woocommerce-order-actions').hide();
		}
	}

	// show General tab for wp2print products
	if (jQuery('ul.product_data_tabs').size()) {
		jQuery('p._tax_status_field').parent().addClass('show_if_fixed show_if_book show_if_area show_if_aec');
		jQuery('select#product-type').change();
	}
	jQuery('.apply-round-up').click(function(){
		if (jQuery(this).is(':checked')) {
			jQuery('table.round-up-discount').show();
		} else {
			jQuery('table.round-up-discount').hide();
		}
	});
	if (jQuery('select.bq-style').size()) {
		print_products_bq_style();
		print_products_pq_style();
	}
	jQuery('.order-send-proof').click(function(){
		var oid = jQuery(this).attr('data-oid');
		var iid = jQuery(this).attr('data-iid');
		jQuery('.order-proof-form .proof-order-id').val(oid);
		jQuery('.order-proof-form .proof-item-id').val(iid);
		jQuery.colorbox({inline:true, href:"#upload-artwork"});
		return false;
	});
	if (jQuery('#order_data')) {
		jQuery('#order_data').prepend('<input type="button" class="button button-primary order-print-btn" value="'+order_print_label+'" onclick="window.print();">');
	}
	jQuery('.i-v-date').datepicker({dateFormat:'yy-mm-dd'});
	jQuery('.pp-sq-edate').datepicker({dateFormat:'yy-mm-dd'});
	jQuery('.item-rsdate').datepicker({dateFormat:'yy-mm-dd', onSelect:function(){
		var sdate = jQuery(this).val();
		var oid = jQuery(this).attr('data-order-id');
		var iid = jQuery(this).attr('data-item-id');
		if (sdate) {
			wp2print_oirsdate_change(oid, iid, sdate);
		}
	}});

	jQuery('.order-item-vendor').change(function(){
		var oval = jQuery(this).val();
		var orel = jQuery(this).attr('rel');

		if (oval) {
			jQuery('.v-order-item-'+orel+' .order-vendor-address').slideDown();
		} else {
			jQuery('.v-order-item-'+orel+' .order-vendor-address').slideUp();
		}
	});
	jQuery('.order-vendor-address .ovendor-address').click(function(){
		var orel = jQuery(this).attr('rel');
		var ova = jQuery('.v-order-item-'+orel+' .order-vendor-address .ovendor-address:checked').val();
		if (ova == 'vendor') {
			jQuery('.v-order-item-'+orel+' .order-vendor-address .customer-address .address-line').slideUp();
			jQuery('.v-order-item-'+orel+' .order-vendor-address .vendor-address .address-line').slideDown();
		} else {
			jQuery('.v-order-item-'+orel+' .order-vendor-address .vendor-address .address-line').slideUp();
			jQuery('.v-order-item-'+orel+' .order-vendor-address .customer-address .address-line').slideDown();
		}
	});
	jQuery('.users-groups-form .delete-theme-logo').click(function(){
		var confmess = jQuery(this).data('confirm');
		var d = confirm(confmess);
		if (d) {
			jQuery('.users-groups-form .theme-logo').val('');
			jQuery('.users-groups-form').submit();
		}
	});
	// order item status
	jQuery('.ois-list .ois-name').click(function(){
		var orel = jQuery(this).attr('rel');
		if (jQuery('.ois-row-'+orel+' .ois-form').is(':visible')) {
			jQuery('.ois-row-'+orel+' .ois-form').slideUp();
		} else {
			jQuery('.ois-row-'+orel+' .ois-form').slideDown();
		}
	});
	jQuery('.ois-color').wpColorPicker();
	// send quote
	send_quote_cproduct();
	jQuery('.send-quote-form .order-customer').select2();
	jQuery('.send-quote-form .sq-add-user-btn').click(function(){
		jQuery.colorbox({inline:true, href:"#sq-add-user"});
	});
	// create order
	create_order_cproduct();
	jQuery('.create-order-form .order-customer').select2();
	jQuery('.create-order-form .co-add-user-btn').click(function(){
		jQuery.colorbox({inline:true, href:"#co-add-user"});
	});
	// order show old artwork files
	jQuery('.show-old-files').click(function(){
		if (jQuery('.old-files-list').is(':visible')) {
			jQuery('.old-files-list').slideUp();
		} else {
			jQuery('.old-files-list').slideDown();
		}
		return false;
	});
	if (jQuery('input#_manage_stock').length) {
		jQuery('input#_manage_stock').parent().addClass('show_if_fixed').show();
	}
	// send quote history
	jQuery('.sqh-resend-email').click(function(){
		var oid = jQuery(this).attr('data-oid');
		jQuery('form.sqh-form .sqh-action').val('resend');
		jQuery('form.sqh-form .sqh-order-id').val(oid);
		jQuery('form.sqh-form').submit();
		return false;
	});
	jQuery('.sqh-duplicate').click(function(){
		var oid = jQuery(this).attr('data-oid');
		jQuery('form.sqh-form .sqh-action').val('duplicate');
		jQuery('form.sqh-form .sqh-order-id').val(oid);
		jQuery('form.sqh-form').submit();
		return false;
	});
	// orders list
	if (jQuery('.ois-block').size()) {
		var oilines = [];
		jQuery('.ois-block .oil-line').each(function(){
			var oi = parseInt(jQuery(this).attr('rel'));
			var h = jQuery(this).height();
			var mt2 = h / 2;
			var mt3 = h / 2.3;
			jQuery('.oil-item-'+oi).height(h);
			jQuery('.oil-item-'+oi+' span').css('padding-top', mt2+'px');
			jQuery('.oil-item-'+oi+' span.mrk').css('padding-top', mt3+'px');
		});
	}
});
jQuery(function() {
	jQuery('.wp2print-wrap').tooltip();
});
function print_products_select_taxonomy(element) {
	jQuery('#print_products_sort_order_form').submit();
}
function print_products_check_tax_select() {
	var product_type = jQuery('#product-type').val();
	if (product_type == 'fixed' || product_type == 'area' || product_type == 'book' || product_type == 'aec') {
		jQuery('._tax_status_field').parent().show();
		jQuery('li.inventory_tab').addClass('show_if_fixed show_if_area show_if_book show_if_aec').show();
		setTimeout(function(){
			jQuery('li.inventory_tab').addClass('show_if_fixed show_if_area show_if_book show_if_aec').show();
		}, 2000);
	}
}

function print_products_serialize(mixed_value) {
	var _utf8Size = function (str) {
		var size = 0,
			i = 0,
			l = str.length,
			code = '';
		for (i = 0; i < l; i++) {
			code = str.charCodeAt(i);
			if (code < 0x0080) {
				size += 1;
			} else if (code < 0x0800) {
				size += 2;
			} else {
				size += 3;
			}
		}
		return size;
	};
	var _getType = function (inp) {
		var type = typeof inp,
			match;
		var key;

		if (type === 'object' && !inp) {
			return 'null';
		}
		if (type === "object") {
			if (!inp.constructor) {
				return 'object';
			}
			var cons = inp.constructor.toString();
			match = cons.match(/(\w+)\(/);
			if (match) {
				cons = match[1].toLowerCase();
			}
			var types = ["boolean", "number", "string", "array"];
			for (key in types) {
				if (cons == types[key]) {
					type = types[key];
					break;
				}
			}
		}
		return type;
	};
	var type = _getType(mixed_value);
	var val, ktype = '';

	switch (type) {
	case "function":
		val = "";
		break;
	case "boolean":
		val = "b:" + (mixed_value ? "1" : "0");
		break;
	case "number":
		val = (Math.round(mixed_value) == mixed_value ? "i" : "d") + ":" + mixed_value;
		break;
	case "string":
		val = "s:" + _utf8Size(mixed_value) + ":\"" + mixed_value + "\"";
		break;
	case "array":
	case "object":
		val = "a";
		var count = 0;
		var vals = "";
		var okey;
		var key;
		for (key in mixed_value) {
			if (mixed_value.hasOwnProperty(key)) {
				ktype = _getType(mixed_value[key]);
				if (ktype === "function") {
					continue;
				}

				okey = (key.match(/^[0-9]+$/) ? parseInt(key, 10) : key);
				vals += this.print_products_serialize(okey) + this.print_products_serialize(mixed_value[key]);
				count++;
			}
		}
		val += ":" + count + ":{" + vals + "}";
		break;
	case "undefined":
		// undefined
	default:
		val = "N";
		break;
	}
	if (type !== "object" && type !== "array") {
		val += ";";
	}
	return val;
}

function print_products_num_type() {
	var ntype = jQuery('.num-type').val();
	jQuery('.numbers-label .nlabel').hide();
	jQuery('.numbers-label .nlabel-'+ntype).show();
	if (ntype == 5) {
		jQuery('.ltext-attr-tr').show();
	} else {
		jQuery('.ltext-attr-tr').hide();
	}
}

function print_products_bq_style() {
	var bq_style = jQuery('select.bq-style').val();
	if (bq_style == 1) {
		jQuery('.bq-numbers-tr').show();
		jQuery('.bq-min-tr').hide();
	} else {
		jQuery('.bq-numbers-tr').hide();
		jQuery('.bq-min-tr').show();
	}
}

function print_products_pq_style() {
	var pq_style = jQuery('select.pq-style').val();
	if (pq_style == 1) {
		jQuery('.pq-numbers-tr').show();
		jQuery('.pq-defval-tr').hide();
	} else {
		jQuery('.pq-numbers-tr').hide();
		jQuery('.pq-defval-tr').show();
	}
}

function print_products_group_address_add(atype) {
	jQuery('.group-address-form')[0].reset();
	jQuery('.group-address-form .ga-action').val('add');
	jQuery('.group-address-form .ga-type').val(atype);
	jQuery('.group-address-form .ga-add-title').show();
	jQuery('.group-address-form .ga-edit-title').hide();
	jQuery('.group-address-form .ga-error').hide();
	if (atype == 'shipping') {
		jQuery('.group-address-form .ga-phone-email').hide();
	} else {
		jQuery('.group-address-form .ga-phone-email').show();
	}
	jQuery('.group-address-form .ga-country option').removeAttr('selected');
	print_products_group_address_country_change();
}

function print_products_group_address_edit(akey, atype) {
	var relobj = '.'+atype+'-'+akey;
	jQuery('.group-address-form')[0].reset();
	jQuery('.group-address-form .ga-action').val('edit');
	jQuery('.group-address-form .ga-type').val(atype);
	jQuery('.group-address-form .ga-rel').val(akey);
	jQuery('.group-address-form .ga-add-title').hide();
	jQuery('.group-address-form .ga-edit-title').show();
	jQuery('.group-address-form .ga-error').hide();

	var country = jQuery(relobj+' .a-country').val();
	var state = jQuery(relobj+' .a-state').val();

	jQuery('.group-address-form .ga-label').val(jQuery(relobj+' .a-label').val());
	jQuery('.group-address-form .ga-fname').val(jQuery(relobj+' .a-fname').val());
	jQuery('.group-address-form .ga-lname').val(jQuery(relobj+' .a-lname').val());
	jQuery('.group-address-form .ga-company').val(jQuery(relobj+' .a-company').val());
	jQuery('.group-address-form .ga-country option[value="'+country+'"]').attr('selected', 'selected');
	jQuery('.group-address-form .ga-address').val(jQuery(relobj+' .a-address').val());
	jQuery('.group-address-form .ga-address2').val(jQuery(relobj+' .a-address2').val());
	jQuery('.group-address-form .ga-city').val(jQuery(relobj+' .a-city').val());
	jQuery('.group-address-form .ga-zip').val(jQuery(relobj+' .a-zip').val());
	if (atype == 'billing') {
		jQuery('.group-address-form .ga-phone').val(jQuery(relobj+' .a-phone').val());
		jQuery('.group-address-form .ga-email').val(jQuery(relobj+' .a-email').val());
		jQuery('.group-address-form .ga-phone-email').show();
	} else {
		jQuery('.group-address-form .ga-phone-email').hide();
	}

	print_products_group_address_country_change();
	if (jQuery('.group-address-form .ga-state-'+country).size()) {
		jQuery('.group-address-form .ga-state-'+country+' option[value="'+state+'"]').attr('selected', 'selected');
	} else {
		jQuery('.group-address-form .ga-state-text').val(state);
	}
}

function print_products_group_address_delete(akey) {
	var delmessage = jQuery('.group-addresses-content').attr('data-dmessage');
	var d = confirm(delmessage);
	if (d) {
		jQuery('.group-addresses-content .'+akey).remove();
	}
}

function print_products_group_address_country_change() {
	var country = jQuery('.group-address-form .ga-country').val();
	if (country != '') {
		if (jQuery('.group-address-form .ga-state-'+country).size()) {
			jQuery('.group-address-form .ga-state option').removeAttr('selected');

			jQuery('.group-address-form .ga-state-'+country).show();
			jQuery('.group-address-form .ga-state-text').hide();
		} else {
			jQuery('.group-address-form .ga-state').hide();
			jQuery('.group-address-form .ga-state-text').show();
		}
	} else {
		jQuery('.group-address-form .ga-state').hide();
		jQuery('.group-address-form .ga-state-text').show();
	}
}

function print_products_group_address_save() {
	var error = false;
	var gaaction = jQuery('.group-address-form .ga-action').val();
	var gatype = wp2print_trim(jQuery('.group-address-form .ga-type').val());
	var garel = wp2print_trim(jQuery('.group-address-form .ga-rel').val());
	var label = wp2print_trim(jQuery('.group-address-form .ga-label').val());
	var fname = wp2print_trim(jQuery('.group-address-form .ga-fname').val());
	var lname = wp2print_trim(jQuery('.group-address-form .ga-lname').val());
	var company = wp2print_trim(jQuery('.group-address-form .ga-company').val());
	var country = wp2print_trim(jQuery('.group-address-form .ga-country').val());
	var address = wp2print_trim(jQuery('.group-address-form .ga-address').val());
	var address2 = wp2print_trim(jQuery('.group-address-form .ga-address2').val());
	var city = wp2print_trim(jQuery('.group-address-form .ga-city').val());
	var zip = wp2print_trim(jQuery('.group-address-form .ga-zip').val());
	var phone = wp2print_trim(jQuery('.group-address-form .ga-phone').val());
	var email = wp2print_trim(jQuery('.group-address-form .ga-email').val());

	var state = wp2print_trim(jQuery('.group-address-form .ga-state-text').val());
	if (jQuery('.group-address-form .ga-state-'+country).size()) {
		state = jQuery('.group-address-form .ga-state-'+country).val();
	}

	jQuery('.group-address-form .ga-error').hide();

	if (label == '') { error = true; }
	if (fname == '') { error = true; }
	if (lname == '') { error = true; }
	if (company == '') { error = true; }
	if (country == '') { error = true; }
	if (address == '') { error = true; }
	if (city == '') { error = true; }
	if (state == '') { error = true; }
	if (zip == '') { error = true; }
	if (gatype == 'billing') {
		if (phone == '') { error = true; }
		if (email == '') { error = true; }
	}

	if (error) {
		jQuery('.group-address-form .ga-error').slideDown();
	} else {
		if (gaaction == 'edit') {
			var arel = '.'+gatype+'-'+garel;
			jQuery(arel+' .a-line').html(label);
			jQuery(arel+' .a-label').val(label);
			jQuery(arel+' .a-fname').val(fname);
			jQuery(arel+' .a-lname').val(lname);
			jQuery(arel+' .a-company').val(company);
			jQuery(arel+' .a-country').val(country);
			jQuery(arel+' .a-address').val(address);
			jQuery(arel+' .a-address2').val(address2);
			jQuery(arel+' .a-city').val(city);
			jQuery(arel+' .a-state').val(state);
			jQuery(arel+' .a-zip').val(zip);
			if (gatype == 'billing') {
				jQuery(arel+' .a-phone').val(phone);
				jQuery(arel+' .a-email').val(email);
			}
		} else {
			var akey = new Date().getTime();
			var elabel = jQuery('.group-address-form').attr('data-edit');
			var dlabel = jQuery('.group-address-form').attr('data-delete');
			var ahtml = '<tr class="billing-'+akey+'">';
			ahtml += '<td><input type="checkbox" name="'+gatype+'_addresses['+akey+'][active]" value="1"></td>';
			ahtml += '<td class="a-line">'+label+'</td>';
			ahtml += '<td align="right"><a href="#TB_inline?width=400&height=535&inlineId=group-address-form" class="thickbox" onclick="print_products_group_address_edit(\''+akey+'\', \'billing\');">'+elabel+'</a>&nbsp;|&nbsp;<a href="#delete" class="delete-addr" onclick="print_products_group_address_delete(\'billing-'+akey+'\'); return false;">'+dlabel+'</a>';
			ahtml += '<div class="a-info" style="display:none;">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][label]" value="'+label+'" class="a-fname">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][fname]" value="'+fname+'" class="a-fname">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][lname]" value="'+lname+'" class="a-lname">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][company]" value="'+company+'" class="a-company">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][country]" value="'+country+'" class="a-country">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][address]" value="'+address+'" class="a-address">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][address2]" value="'+address2+'" class="a-address2">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][city]" value="'+city+'" class="a-city">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][state]" value="'+state+'" class="a-state">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][zip]" value="'+zip+'" class="a-zip">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][phone]" value="'+phone+'" class="a-phone">';
			ahtml += '<input type="hidden" name="'+gatype+'_addresses['+akey+'][email]" value="'+email+'" class="a-email">';
			ahtml += '</div></td>';
			ahtml += '</tr>';

			if (jQuery('.ga-'+gatype+'-addresses table .a-noaddress').size()) {
				jQuery('.ga-'+gatype+'-addresses table .a-noaddress').remove();
			}
			jQuery('.ga-'+gatype+'-addresses table').append(ahtml);
		}
		self.parent.tb_remove();
	}
	return false;
}

function wp2print_trim(str) {
	if (str != 'undefined') {
		return str.replace(/^\s+|\s+$/g,"");
	} else {
		return '';
	}
}

function create_order_process(step) {
	var error = false;
	if (step == 1) {
		var customer = jQuery('.create-order-form .order-customer').val();
		if (customer == '') {
			error = true;
		}
	} else if (step == 2) {
		var afilled = true;
		jQuery('.create-order-form .co-billing-address .form-field').each(function(){
			if (jQuery(this).find('select').size()) {
				var fval = jQuery(this).find('select').val();
			} else {
				var fval = jQuery(this).find('input').val();
				if (jQuery(this).find('input').attr('id') == 'billing_address_2') {
					fval = 'FILLED';
				}
			}
			if (fval == '') {
				afilled = false;
			}
		});
		jQuery('.create-order-form .co-shipping-address .form-field').each(function(){
			if (jQuery(this).find('select').size()) {
				var fval = jQuery(this).find('select').val();
			} else {
				var fval = jQuery(this).find('input').val();
				if (jQuery(this).find('input').attr('id') == 'shipping_address_2') {
					fval = 'FILLED';
				}
			}
			if (fval == '') {
				afilled = false;
			}
		});
		if (!afilled) {
			error = true;
		}
	} else if (step == 3) {
		var quantity = jQuery('.create-order-form .quantity').val();
		if (quantity == '') {
			error = true;
		}
	} else if (step == 4) {
		var subtotal = parseFloat(jQuery('.create-order-form .p-price').val());
		var total = parseFloat(jQuery('.create-order-form .total-price').val());
		if ((subtotal == '' || subtotal == 0) && (total == '' || total == 0)) {
			error = true;
		}
	}
	if (error) {
		var error_message = jQuery('.create-order-form').attr('data-error-required');
		alert(error_message);
		return false;
	}
}

function create_order_copy_billing() {
	jQuery('.co-shipping-address #shipping_company').val(jQuery('.co-billing-address #billing_company').val());
	jQuery('.co-shipping-address #shipping_address_1').val(jQuery('.co-billing-address #billing_address_1').val());
	jQuery('.co-shipping-address #shipping_address_2').val(jQuery('.co-billing-address #billing_address_2').val());
	jQuery('.co-shipping-address #shipping_city').val(jQuery('.co-billing-address #billing_city').val());
	jQuery('.co-shipping-address #shipping_postcode').val(jQuery('.co-billing-address #billing_postcode').val());

	var country = jQuery('.co-billing-address #billing_country').val();
	var country_name = jQuery('.co-billing-address #billing_country option:selected').text();
	jQuery('.co-shipping-address #shipping_country option').removeAttr('selected');
	if (country) {
		jQuery('.co-shipping-address #shipping_country option[value="'+country+'"]').attr('selected', 'selected');
		jQuery('.co-shipping-address #select2-shipping_country-container').html(country_name);
		jQuery('.co-shipping-address #shipping_country').trigger('change');
	}
	var state = jQuery('.co-billing-address #billing_state').val();
	var state_name = state;
	if (jQuery('.co-billing-address #billing_state').size()) {
		state_name = jQuery('.co-billing-address #billing_state option:selected').text();
	}
	if (jQuery('.co-shipping-address select#shipping_state').size()) {
		jQuery('.co-shipping-address select#shipping_state option[value="'+state+'"]').attr('selected', 'selected');
		jQuery('.co-shipping-address #select2-shipping_state-container').html(state_name);
	} else {
		jQuery('.co-shipping-address #shipping_state').val(state);
	}
	return false;
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

function matrix_set_tax() {
	var tax_rate = parseFloat(jQuery('.create-order-form .tax-price').attr('data-rate'));
	if (tax_rate) {
		var subtotal = parseFloat(jQuery('.create-order-form .p-price').val());
		if (subtotal > 0) {
			var tax_price = (subtotal / 100) * tax_rate;
			jQuery('.create-order-form .tax-price').val(tax_price.toFixed(2));
		}
	}
}

function matrix_set_shipping_tax() {
	var tax_rate = parseFloat(jQuery('.create-order-form .tax-price').attr('data-rate'));
	if (tax_rate) {
		var shipping = parseFloat(jQuery('.create-order-form .shipping-price').val());
		if (shipping > 0) {
			var shipping_tax_price = (shipping / 100) * tax_rate;
			jQuery('.create-order-form .shipping-tax-price').val(shipping_tax_price.toFixed(2));
		}
	}
}

function matrix_set_prices() {
	var subtotal = parseFloat(jQuery('.create-order-form .p-price').val());
	var tax = parseFloat(jQuery('.create-order-form .tax-price').val());
	var shipping = parseFloat(jQuery('.create-order-form .shipping-price').val());
	var shipping_tax = parseFloat(jQuery('.create-order-form .shipping-tax-price').val());
	var total = subtotal + tax + shipping + shipping_tax;
	jQuery('.create-order-form .total-price').val(total.toFixed(2));
}

function matrix_aval(val) {
	val = val.replace('-', '{dc}');
	val = val.replace(':', '{cc}');
	val = val.replace('|', '{vc}');
	return val;
}

function sva_save_address() {
	jQuery('form.sva-form').submit();
}

// send quote
function send_quote_cproduct() {
	var pid = parseInt(jQuery('.send-quote-form select.order-product').val());
	var cpid = parseInt(jQuery('.send-quote-form').attr('data-custom-product'));
	if (pid == cpid) {
		jQuery('.send-quote-form .sq-cproduct-type').slideDown();
	} else {
		jQuery('.send-quote-form .sq-cproduct-type').slideUp();
	}
}

function send_quote_add_product() {
	var pid = parseInt(jQuery('.send-quote-form select.order-product').val());
	if (!pid) {
		alert(jQuery('.send-quote-form .co-add-product').attr('data-error'));
		return false;
	}
	jQuery('.send-quote-form .product-action').val('add');
	jQuery('.send-quote-form').submit();
	return true;
}

function send_quote_duplicate_product(pkey) {
	jQuery('.send-quote-form .product-key').val(pkey);
	jQuery('.send-quote-form .product-action').val('duplicate');
	jQuery('.send-quote-form').submit();
}

function send_quote_delete_product(pkey) {
	var d = confirm(jQuery('.send-quote-form').attr('data-error-sure'));
	if (d) {
		jQuery('.send-quote-form .product-key').val(pkey);
		jQuery('.send-quote-form .product-action').val('delete');
		jQuery('.send-quote-form').submit();
	}
	return false;
}

function send_quote_process(step) {
	var error = false;
	if (step == 1) {
		var customer = jQuery('.send-quote-form .order-customer').val();
		if (customer == '') {
			error = true;
		}
	} else if (step == 2) {
		var product_action = jQuery('.send-quote-form .product-action').val();
		if (product_action == 'attributes') {
			var quantity = jQuery('.send-quote-form .quantity').val();
			if (quantity == '') {
				error = true;
			} else {
				if (jQuery('.send-quote-form .artwork-files').length) {
					var artworkfiles = jQuery('.send-quote-form .artwork-files').val();
					if (artworkfiles == '' && squploaded) {
						var ctext = jQuery('.send-quote-form').attr('data-confirm-wfiles');
						var cnf = confirm(ctext);
						if (!cnf) {
							jQuery('div.moxie-shim input[type="file"]').trigger('click');
							return false;
						}
					} else {
						if (!squploaded) {
							jQuery('.upload-loading').show();
							uploader.start();
							return false;
						}
					}
				}
			}
		}
	}
	if (error) {
		var error_message = jQuery('.send-quote-form').attr('data-error-required');
		alert(error_message);
		return false;
	}
}

// create order
function create_order_cproduct() {
	var pid = parseInt(jQuery('.create-order-form select.order-product').val());
	var cpid = parseInt(jQuery('.create-order-form').attr('data-custom-product'));
	if (pid == cpid) {
		jQuery('.create-order-form .co-cproduct-type').slideDown();
	} else {
		jQuery('.create-order-form .co-cproduct-type').slideUp();
	}
}

function create_order_add_product() {
	var pid = parseInt(jQuery('.create-order-form select.order-product').val());
	if (!pid) {
		alert(jQuery('.create-order-form .co-add-product').attr('data-error'));
		return false;
	}
	jQuery('.create-order-form .product-action').val('add');
	jQuery('.create-order-form').submit();
	return true;
}

function create_order_duplicate_product(pkey) {
	jQuery('.create-order-form .product-key').val(pkey);
	jQuery('.create-order-form .product-action').val('duplicate');
	jQuery('.create-order-form').submit();
}

function create_order_delete_product(pkey) {
	var d = confirm(jQuery('.create-order-form').attr('data-error-sure'));
	if (d) {
		jQuery('.create-order-form .product-key').val(pkey);
		jQuery('.create-order-form .product-action').val('delete');
		jQuery('.create-order-form').submit();
	}
	return false;
}

var cpc_num = 0;
var cpc_url = '';
var cpc_products = '';
function wp2print_check_product_config() {
	cpc_num = 0;
	cpc_url = jQuery('.ch-product-conf-form').attr('action');
	cpc_products = jQuery('.ch-product-conf-form .ch-pids').val().split(';');
	jQuery('.ch-product-conf').html('');
	jQuery('.ch-product-conf-form .button').hide();
	jQuery('.ch-analyzing').show();
	wp2print_check_product_config_process();
}

function wp2print_check_product_config_process() {
	if (cpc_num < cpc_products.length) {
		jQuery.post(
			cpc_url,
			{
				AjaxAction: 'check-product-config',
				product_id: cpc_products[cpc_num]
			},
			function(data) {
				jQuery('.ch-product-conf').append(data);
				cpc_num++;
				wp2print_check_product_config_process();
			}
		);
	} else {
		jQuery('.ch-analyzing').hide();
		jQuery('.ch-product-conf-form .button').show();
		var cpc_html = jQuery('.ch-product-conf').html();
		jQuery.post(
			cpc_url,
			{
				AjaxAction: 'check-product-send-result',
				result_html: cpc_html
			},
			function(data) {
				jQuery('.ch-product-conf').append(data);
			}
		);
	}
}

function wp2print_pso_check(o) {
	var psoch = jQuery(o).is(':checked');
	if (psoch) {
		jQuery('.pso-list').slideDown();
	} else {
		jQuery('.pso-list').slideUp();
	}
}

function wp2print_pso_add() {
	var trnum = parseInt(jQuery('.pso-data').attr('data-num'));
	var tr_html = jQuery('.pso-data table tbody').html();
	tr_html = tr_html.replace(new RegExp('{N}', 'gi'), trnum);
	jQuery('.pso-list table.pso-list-table').append(tr_html);
	wp2print_pso_renum();
	trnum = trnum + 1;
	jQuery('.pso-data').attr('data-num', trnum)
}

function wp2print_pso_remove(n) {
	var rmessage = jQuery('.pso-data').attr('data-rmessage');
	var rem = confirm(rmessage);
	if (rem) {
		jQuery('.pso-list table.pso-list-table tr.pso-tr'+n).remove();
		wp2print_pso_renum();
	}
}

function wp2print_pso_renum() {
	var n = 1;
	if (jQuery('.pso-list table.pso-list-table tr.pso-tr').length) {
		jQuery('.pso-list table.pso-list-table tr.pso-tr').each(function(){
			jQuery(this).find('td').eq(0).html(n);
			n++;
		});
	}
	
}

function wp2print_pso_sd_check(o) {
	var sdch = jQuery(o).is(':checked');
	if (sdch) {
		jQuery('.pso-sd-data').slideDown();
	} else {
		jQuery('.pso-sd-data').slideUp();
	}
}

function wp2print_ois_add() {
	var form_html = jQuery('.ois-hidden').html();
	var fnmb = parseInt(jQuery('.ois-list').attr('data-nmb'));
	form_html = form_html.replace(new RegExp('{N}', 'gi'), fnmb);
	form_html = form_html.replace('ois-color-fld', 'ois-color-select');
	jQuery('.ois-list').append(form_html);
	jQuery('.ois-color-select').wpColorPicker();
	jQuery('.ois-list .ois-row-'+fnmb+' .ois-form').slideDown();
	fnmb = fnmb + 1;
	jQuery('.ois-list').attr('data-nmb', fnmb);
}

function wp2print_ois_delete(o) {
	var dm = jQuery('.ois-list').attr('data-message');
	var d = confirm(dm);
	if (d) {
		jQuery('.ois-list .ois-row-'+o).remove();
	}
}

var oistm = 0;
function wp2print_ois_change(order_id, item_id, ptp) {
	var oitems = jQuery('.ois-order-'+order_id+' select').length;
	var item_status = jQuery('.ois-ldd-'+item_id).val();
	var allow_popup = parseInt(jQuery('.ois-popup').attr('data-allow-popup'));
	oistm = 0;
	if (allow_popup == 1) {
		jQuery('.ois-popup h2 span').html(order_id);
		jQuery('.ois-popup').attr('data-order-id', order_id);
		jQuery('.ois-popup').attr('data-item-id', item_id);
		jQuery('.ois-popup').attr('data-status', item_status);
		jQuery('.ois-popup').attr('data-ptp', ptp);

		if (oitems > 1) {
			oistm = 500;
			jQuery.colorbox({inline:true, href:"#ois-popup"});
		} else {
			wp2print_ois_submit();
		}
	} else {
		wp2print_ois_submit();
	}
}

function wp2print_ois_network_change(blog_id, order_id, item_id, ptp) {
	var oitems = jQuery('.ois-order-'+blog_id+'-'+order_id+' select').length;
	var item_status = jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-ldd-'+item_id).val();
	jQuery('.ois-popup h2 span').html(order_id);
	jQuery('.ois-popup').attr('data-blog-id', blog_id);
	jQuery('.ois-popup').attr('data-order-id', order_id);
	jQuery('.ois-popup').attr('data-item-id', item_id);
	jQuery('.ois-popup').attr('data-status', item_status);
	jQuery('.ois-popup').attr('data-ptp', ptp);
	oistm = 0;

	if (oitems > 1) {
		oistm = 500;
		jQuery.colorbox({inline:true, href:"#ois-popup"});
	} else {
		wp2print_ois_network_submit();
	}
}

function wp2print_ois_submit() {
	var order_id = jQuery('.ois-popup').attr('data-order-id');
	var item_id = jQuery('.ois-popup').attr('data-item-id');
	var istatus = jQuery('.ois-popup').attr('data-status');
	var ptp = jQuery('.ois-popup').attr('data-ptp');
	var ioption = 0;
	if (jQuery('.ois-popup .ois-i-options input').eq(1).is(':checked')) { ioption = 1; }
	jQuery.colorbox.close();
	if (ioption == 1) {
		jQuery('.ois-order-'+order_id+' select option').removeAttr('selected');
		jQuery('.ois-order-'+order_id+' select option[value="'+istatus+'"]').attr('selected', 'selected');
	}
	jQuery('.ois-popup .ois-i-options input').eq(0).trigger('click')

	jQuery('.ois-order-'+order_id+' select').attr('disabled', 'disabled');
	jQuery.post(wp2print_adminurl,
		{
			OisAjaxAction: 'change-oistatus-from-list',
			order_id: order_id,
			item_id: item_id,
			ioption: ioption,
			item_status: istatus
		},
		function(data) {
			jQuery('.ois-order-'+order_id+' select').removeAttr('disabled');
			if (ioption == 1) {
				jQuery('.ois-order-'+order_id+' .ois-success').slideDown();
			} else {
				if (ptp == 'detail') {
					jQuery('.ois-order-'+order_id+' .ois-success-'+item_id).slideDown();
				} else {
					jQuery('.ois-order-'+order_id+' .ois-success').slideDown();
				}
			}
			setTimeout(function(){ jQuery('.ois-order-'+order_id+' .ois-success').slideUp(); }, 2000);
			if (ptp == 'pview') {
				if (ioption == 1) {
					jQuery('.ois-order-'+order_id+' .ois-graph td').removeClass('active');
					jQuery('.ois-order-'+order_id+' .ois-graph td.ois-'+istatus).addClass('active');
				} else {
					jQuery('.ois-graph-'+item_id+' td').removeClass('active');
					jQuery('.ois-graph-'+item_id+' td.ois-'+istatus).addClass('active');
				}
				if (data != '') {
					jQuery('.ois-order-'+order_id+' .o-status').html(data);
				}
			}
		}
	);
	setTimeout(function(){
		wp2print_ois_tracking(order_id, item_id, istatus, 0);
	}, oistm);
}

function wp2print_ois_network_submit() {
	var blog_id = jQuery('.ois-popup').attr('data-blog-id');
	var order_id = jQuery('.ois-popup').attr('data-order-id');
	var item_id = jQuery('.ois-popup').attr('data-item-id');
	var istatus = jQuery('.ois-popup').attr('data-status');
	var ptp = jQuery('.ois-popup').attr('data-ptp');
	var ioption = 0;
	if (jQuery('.ois-popup .ois-i-options input').eq(1).is(':checked')) { ioption = 1; }
	jQuery.colorbox.close();
	if (ioption == 1) {
		jQuery('.ois-order-'+blog_id+'-'+order_id+' select option').removeAttr('selected');
		jQuery('.ois-order-'+blog_id+'-'+order_id+' select option[value="'+istatus+'"]').attr('selected', 'selected');
	}
	jQuery('.ois-popup .ois-i-options input').eq(0).trigger('click')

	jQuery('.ois-order-'+blog_id+'-'+order_id+' select').attr('disabled', 'disabled');
	jQuery.post(wp2print_adminurl,
		{
			OisAjaxAction: 'change-oistatus-from-list',
			blog_id: blog_id,
			order_id: order_id,
			item_id: item_id,
			ioption: ioption,
			item_status: istatus
		},
		function(data) {
			jQuery('.ois-order-'+blog_id+'-'+order_id+' select').removeAttr('disabled');
			if (ioption == 1) {
				jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-success').slideDown();
			} else {
				if (ptp == 'detail') {
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-success-'+item_id).slideDown();
				} else {
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-success').slideDown();
				}
			}
			setTimeout(function(){ jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-success').slideUp(); }, 2000);
			if (ptp == 'pview') {
				if (ioption == 1) {
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-graph td').removeClass('active');
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-graph td.ois-'+istatus).addClass('active');
				} else {
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-graph-'+item_id+' td').removeClass('active');
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .ois-graph-'+item_id+' td.ois-'+istatus).addClass('active');
				}
				if (data != '') {
					jQuery('.ois-order-'+blog_id+'-'+order_id+' .o-status').html(data);
				}
			}
		}
	);
	setTimeout(function(){
		wp2print_ois_tracking(order_id, item_id, istatus, blog_id);
	}, oistm);
}

function wp2print_ois_tracking(order_id, item_id, istatus, blog_id) {
	if (jQuery('#ois-tracking-popup').length && istatus != '') {
		var shipped_status = jQuery('#ois-tracking-popup').attr('data-status');
		if (shipped_status == istatus) {
			jQuery('.ois-tracking-popup').attr('data-order-id', order_id);
			jQuery('.ois-tracking-popup').attr('data-item-id', item_id);
			jQuery('.ois-tracking-popup').attr('data-blog-id', blog_id);
			jQuery.colorbox({inline:true, href:"#ois-tracking-popup"});
		}
	}
}

function wp2print_ois_tracking_submit(se) {
	var order_id = jQuery('.ois-tracking-popup').attr('data-order-id');
	var order_item_id = jQuery('.ois-tracking-popup').attr('data-item-id');
	var blog_id = jQuery('.ois-tracking-popup').attr('data-blog-id');
	var tracking_company = jQuery('.ois-tracking-popup .tracking-company').val();
	var tracking_numbers = jQuery('.ois-tracking-popup .tracking-numbers').val();
	jQuery.colorbox.close();
	jQuery.post(wp2print_adminurl,
		{
			OisAjaxAction: 'oistatus-submit-tracking-info',
			order_id: order_id,
			item_id: order_item_id,
			blog_id: blog_id,
			tracking_company: tracking_company,
			tracking_numbers: tracking_numbers,
			send_email: se
		}
	);
	var defcompany = jQuery('.ois-tracking-popup .tracking-company').attr('data-defcompany');
	jQuery('.ois-tracking-popup .tracking-company option').removeAttr('selected');
	jQuery('.ois-tracking-popup .tracking-numbers').val('');
	if (defcompany != '') {
		jQuery('.ois-tracking-popup .tracking-company option[value="'+defcompany+'"]').attr('selected', 'selected');
	}
}

function wp2print_sqau_submit() {
	var username = jQuery('.sq-add-user-form .sqau-username').val();
	var email = jQuery('.sq-add-user-form .sqau-email').val();
	var fname = jQuery('.sq-add-user-form .sqau-fname').val();
	var lname = jQuery('.sq-add-user-form .sqau-lname').val();
	var pass = jQuery('.sq-add-user-form .sqau-pass').val();

	jQuery('.sq-add-user-form .sq-add-user-error').hide();
	if (username == '' || email == '' || pass == '') {
		var err = jQuery('.sq-add-user-form').attr('data-required');
		jQuery('.sq-add-user-form .sq-add-user-error').html(err).fadeIn();
	} else {
		jQuery('.sq-add-user-form .sq-add-user-loading').show();
		jQuery('.sq-add-user-form .button-primary').attr('disabled', 'disabled');
		jQuery.post(wp2print_adminurl,
			{
				AjaxAction: 'send-quote-add-user',
				u_username: username,
				u_email: email,
				u_fname: fname,
				u_lname: lname,
				u_pass: pass
			},
			function(data) {
				jQuery('.sq-add-user-form .sq-add-user-loading').hide();
				var r_data = data.split(';');
				if (r_data[0] == 'success') {
					jQuery('.send-quote-form .order-customer').append('<option value="'+r_data[1]+'">'+r_data[2]+'</option>');
					jQuery('.send-quote-form .order-customer option').removeAttr('selected');
					jQuery('.send-quote-form .order-customer option[value="'+r_data[1]+'"]').attr('selected', 'selected');
					jQuery.colorbox.close();
					jQuery('.sq-add-user-form .form-table input').val('');
				} else {
					jQuery('.sq-add-user-form .sq-add-user-error').html(r_data[1]).fadeIn();
				}
				jQuery('.sq-add-user-form .button-primary').removeAttr('disabled');
			}
		);
	}
	return false;
}

function wp2print_coau_submit() {
	var username = jQuery('.co-add-user-form .coau-username').val();
	var email = jQuery('.co-add-user-form .coau-email').val();
	var fname = jQuery('.co-add-user-form .coau-fname').val();
	var lname = jQuery('.co-add-user-form .coau-lname').val();
	var pass = jQuery('.co-add-user-form .coau-pass').val();

	jQuery('.co-add-user-form .sq-add-user-error').hide();
	if (username == '' || email == '' || pass == '') {
		var err = jQuery('.co-add-user-form').attr('data-required');
		jQuery('.co-add-user-form .sq-add-user-error').html(err).fadeIn();
	} else {
		jQuery('.co-add-user-form .sq-add-user-loading').show();
		jQuery('.co-add-user-form .button-primary').attr('disabled', 'disabled');
		jQuery.post(wp2print_adminurl,
			{
				AjaxAction: 'create-order-add-user',
				u_username: username,
				u_email: email,
				u_fname: fname,
				u_lname: lname,
				u_pass: pass
			},
			function(data) {
				jQuery('.co-add-user-form .sq-add-user-loading').hide();
				var r_data = data.split(';');
				if (r_data[0] == 'success') {
					jQuery('.create-order-form .order-customer').append('<option value="'+r_data[1]+'">'+r_data[2]+'</option>');
					jQuery('.create-order-form .order-customer option').removeAttr('selected');
					jQuery('.create-order-form .order-customer option[value="'+r_data[1]+'"]').attr('selected', 'selected');
					jQuery.colorbox.close();
					jQuery('.co-add-user-form .form-table input').val('');
				} else {
					jQuery('.co-add-user-form .sq-add-user-error').html(r_data[1]).fadeIn();
				}
				jQuery('.co-add-user-form .button-primary').removeAttr('disabled');
			}
		);
	}
	return false;
}

function wp2print_group_use_printshop() {
	var use_printshop = jQuery('.users-groups-form .use-printshop').is(':checked');
	if (use_printshop) {
		jQuery('.users-groups-form .use-privatestore').removeAttr('checked');
		jQuery('.users-groups-form .printshop-theme-settings').show();
		jQuery('.users-groups-form .printshop-theme-menus').show();
	} else {
		jQuery('.users-groups-form .printshop-theme-settings').hide();
		jQuery('.users-groups-form .printshop-theme-menus').hide();
	}
}

function wp2print_group_use_privatestore() {
	var use_privatestore = jQuery('.users-groups-form .use-privatestore').is(':checked');
	if (use_privatestore) {
		jQuery('.users-groups-form .use-printshop').removeAttr('checked');
		jQuery('.users-groups-form .printshop-theme-settings').hide();
		jQuery('.users-groups-form .printshop-theme-menus').show();
	} else {
		jQuery('.users-groups-form .printshop-theme-menus').hide();
	}
}

// vendor companies
function wp2print_ppvc_clear_form() {
	jQuery('.pp-vc-wrap .pp-vc-form input[type="text"]').val('');
	jQuery('.pp-vc-wrap .pp-vc-form select option').removeAttr('selected');
	jQuery('.pp-vc-wrap .pp-vc-form input[type="checkbox"]').removeAttr('checked');
}

function wp2print_ppvc_add() {
	var last_cid = parseInt(jQuery('.pp-vc-wrap .pp-vc-form').attr('data-last-cid'));
	var cid = last_cid + 1;
	wp2print_ppvc_clear_form();
	jQuery('.pp-vc-wrap .pp-vc-form').attr('data-company-id', cid).attr('data-atype', 'add');
	jQuery('.pp-vc-wrap .pp-vc-form input.vc-send').attr('checked', 'checked');
	jQuery('.pp-vc-wrap .pp-vc-form').slideDown();
	return false;
}

function wp2print_ppvc_edit(cid) {
	var c_name = jQuery('.vc-'+cid+' .vc-data .c-name').html();
	var c_address1 = jQuery('.vc-'+cid+' .vc-data .c-address1').html();
	var c_address2 = jQuery('.vc-'+cid+' .vc-data .c-address2').html();
	var c_city = jQuery('.vc-'+cid+' .vc-data .c-city').html();
	var c_postcode = jQuery('.vc-'+cid+' .vc-data .c-postcode').html();
	var c_state = jQuery('.vc-'+cid+' .vc-data .c-state').html();
	var c_country = jQuery('.vc-'+cid+' .vc-data .c-country').html();
	var c_email = jQuery('.vc-'+cid+' .vc-data .c-email').html();
	var c_send = jQuery('.vc-'+cid+' .vc-data .c-send').html();
	var c_employees = jQuery('.vc-'+cid+' .vc-data .c-employees').html();
	var c_access = jQuery('.vc-'+cid+' .vc-data .c-access').html();

	wp2print_ppvc_clear_form();

	jQuery('.pp-vc-wrap .pp-vc-form').attr('data-company-id', cid).attr('data-atype', 'update');
	jQuery('.pp-vc-wrap .pp-vc-form .vc-name').val(c_name);
	jQuery('.pp-vc-wrap .pp-vc-form .vc-address1').val(c_address1);
	jQuery('.pp-vc-wrap .pp-vc-form .vc-address2').val(c_address2);
	jQuery('.pp-vc-wrap .pp-vc-form .vc-city').val(c_city);
	jQuery('.pp-vc-wrap .pp-vc-form .vc-postcode').val(c_postcode);
	jQuery('.pp-vc-wrap .pp-vc-form .vc-state').val(c_state);
	jQuery('.pp-vc-wrap .pp-vc-form .vc-country option[value="'+c_country+'"]').attr('selected', 'selected');
	jQuery('.pp-vc-wrap .pp-vc-form .vc-email').val(c_email);

	if (c_send == 1) {
		jQuery('.pp-vc-wrap .pp-vc-form input.vc-send').attr('checked', 'checked');
	} else {
		jQuery('.pp-vc-wrap .pp-vc-form input.vc-send').removeAttr('checked');
	}

	if (c_access == 1) {
		jQuery('.pp-vc-wrap .pp-vc-form input.vc-access').attr('checked', 'checked');
	} else {
		jQuery('.pp-vc-wrap .pp-vc-form input.vc-access').removeAttr('checked');
	}

	if (c_employees != '') {
		var c_earray = c_employees.split(',');
		for (var e=0; e<c_earray.length; e++) {
			jQuery('.pp-vc-wrap .pp-vc-form .vc-elist input[value="'+c_earray[e]+'"]').attr('checked', 'checked');
		}
	}

	jQuery('.pp-vc-wrap .pp-vc-form').slideDown();
	return false;
}

function wp2print_ppvc_save() {
	var vc_error = '';
	var atype = jQuery('.pp-vc-wrap .pp-vc-form').attr('data-atype');
	var vc_id = jQuery('.pp-vc-wrap .pp-vc-form').attr('data-company-id');
	var vc_name = jQuery('.pp-vc-wrap .pp-vc-form .vc-name').val();
	var vc_address1 = jQuery('.pp-vc-wrap .pp-vc-form .vc-address1').val();
	var vc_address2 = jQuery('.pp-vc-wrap .pp-vc-form .vc-address2').val();
	var vc_city = jQuery('.pp-vc-wrap .pp-vc-form .vc-city').val();
	var vc_postcode = jQuery('.pp-vc-wrap .pp-vc-form .vc-postcode').val();
	var vc_state = jQuery('.pp-vc-wrap .pp-vc-form .vc-state').val();
	var vc_country = jQuery('.pp-vc-wrap .pp-vc-form .vc-country').val();
	var vc_email = jQuery('.pp-vc-wrap .pp-vc-form .vc-email').val();
	var vc_send = 0;
	var vc_access = 0;

	if (jQuery('.pp-vc-wrap .pp-vc-form .vc-send').is(':checked')) { vc_send = 1; }
	if (jQuery('.pp-vc-wrap .pp-vc-form .vc-access').is(':checked')) { vc_access = 1; }

	var vc_send_label = jQuery('.pp-vc-wrap').attr('data-no');
	if (vc_send == 1) {
		var vc_send_label = jQuery('.pp-vc-wrap').attr('data-yes');
	}

	var vc_access_label = jQuery('.pp-vc-wrap').attr('data-no');
	if (vc_access == 1) {
		var vc_access_label = jQuery('.pp-vc-wrap').attr('data-yes');
	}

	var vc_employees = '';
	var vc_employees_name = '';
	jQuery('.pp-vc-wrap .pp-vc-form .vc-elist input').each(function(){
		if (jQuery(this).is(':checked')) {
			if (vc_employees != '') { vc_employees += ','; vc_employees_name += ', '; }
			vc_employees += jQuery(this).val();
			vc_employees_name += jQuery(this).attr('data-name');
		}
	});

	var vc_address = vc_address1;
	if (vc_address2 != '') { vc_address += ', '+vc_address2; }
	if (vc_city != '') { vc_address += ', '+vc_city; }
	if (vc_state != '') { vc_address += ', '+vc_state; }
	if (vc_postcode != '') { vc_address += ' '+vc_postcode; }
	if (vc_country != '') { vc_address += ', '+vc_country; }

	if (vc_name == '') {
		vc_error = jQuery('.pp-vc-wrap .pp-vc-form .vc-name').attr('data-error');
	} else if (vc_email == '') {
		vc_error = jQuery('.pp-vc-wrap .pp-vc-form .vc-email').attr('data-error');
	}
	if (vc_error != '') {
		alert(vc_error);
	} else {
		jQuery.post(wp2print_adminurl,
			{
				AjaxAction: 'vendor-company-action',
				vc_atype: atype,
				vc_id: vc_id,
				vc_name: vc_name,
				vc_address1: vc_address1,
				vc_address2: vc_address2,
				vc_city: vc_city,
				vc_postcode: vc_postcode,
				vc_state: vc_state,
				vc_country: vc_country,
				vc_email: vc_email,
				vc_send: vc_send,
				vc_employees: vc_employees,
				vc_access: vc_access
			}
		);
		if (atype == 'update') {
			jQuery('.vc-'+vc_id+' .lc-name').html(vc_name);
			jQuery('.vc-'+vc_id+' .lc-address').html(vc_address);
			jQuery('.vc-'+vc_id+' .lc-email').html(vc_email);
			jQuery('.vc-'+vc_id+' .lc-send').html(vc_send_label);
			jQuery('.vc-'+vc_id+' .lc-employees').html(vc_employees_name);
			jQuery('.vc-'+vc_id+' .lc-access').html(vc_access_label);

			jQuery('.vc-'+vc_id+' .vc-data .c-name').html(vc_name);
			jQuery('.vc-'+vc_id+' .vc-data .c-address1').html(vc_address1);
			jQuery('.vc-'+vc_id+' .vc-data .c-address2').html(vc_address2);
			jQuery('.vc-'+vc_id+' .vc-data .c-city').html(vc_city);
			jQuery('.vc-'+vc_id+' .vc-data .c-postcode').html(vc_postcode);
			jQuery('.vc-'+vc_id+' .vc-data .c-state').html(vc_state);
			jQuery('.vc-'+vc_id+' .vc-data .c-country').html(vc_country);
			jQuery('.vc-'+vc_id+' .vc-data .c-email').html(vc_email);
			jQuery('.vc-'+vc_id+' .vc-data .c-send').html(vc_send);
			jQuery('.vc-'+vc_id+' .vc-data .c-employees').html(vc_employees);
			jQuery('.vc-'+vc_id+' .vc-data .c-access').html(vc_access);
		} else {
			var del_label = jQuery('.pp-vc-wrap').attr('data-del-label');

			var vc_tr_html = '<tr class="vc-'+vc_id+'">';
			vc_tr_html += '<td class="vc-nm"><a href="#edit" onclick="return wp2print_ppvc_edit('+vc_id+');" class="lc-name">'+vc_name+'</a>';
			vc_tr_html += '<div class="vc-data" style="display:none;"><span class="c-name">'+vc_name+'</span><span class="c-address1">'+vc_address1+'</span><span class="c-address2">'+vc_address2+'</span><span class="c-city">'+vc_city+'</span><span class="c-postcode">'+vc_postcode+'</span><span class="c-state">'+vc_state+'</span><span class="c-country">'+vc_country+'</span><span class="c-email">'+vc_email+'</span><span class="c-send">'+vc_send+'</span><span class="c-employees">'+vc_employees+'</span><span class="c-access">'+vc_access+'</span></div></td>';
			vc_tr_html += '<td class="lc-address">'+vc_address+'</td>';
			vc_tr_html += '<td class="lc-email">'+vc_email+'</td>';
			vc_tr_html += '<td class="lc-send">'+vc_send_label+'</td>';
			vc_tr_html += '<td class="lc-employees">'+vc_employees_name+'</td>';
			vc_tr_html += '<td class="lc-access">'+vc_access_label+'</td>';
			vc_tr_html += '<td class="vc-del"><a href="#delete" onclick="return wp2print_ppvc_delete('+vc_id+');">'+del_label+'</a></td></tr>';

			if (jQuery('.pp-vc-wrap .pp-vc-table .vc-no-records').length) {
				jQuery('.pp-vc-wrap .pp-vc-table .vc-no-records').remove();
			}
			jQuery('.pp-vc-wrap .pp-vc-table').append(vc_tr_html);
			jQuery('.pp-vc-wrap .pp-vc-form').attr('data-last-cid', vc_id);
		}
		jQuery('.pp-vc-wrap .pp-vc-form').slideUp();
		setTimeout(function(){ wp2print_ppvc_clear_form(); }, 1000);
	}
}

function wp2print_ppvc_delete(cid) {
	var dmessage = jQuery('.pp-vc-wrap').attr('data-del-error');
	var d = confirm(dmessage);
	if (d) {
		jQuery.post(wp2print_adminurl,
			{
				AjaxAction: 'vendor-company-action',
				vc_atype: 'delete',
				vc_id: cid
			}
		);
		jQuery('.pp-vc-wrap .pp-vc-table .vc-'+cid).remove();
	}
	return false;
}

function wp2print_vendor_unassign(iid) {
	var mess = jQuery('.oiv-employee-'+iid).attr('data-confirm');
	var cnf = confirm(mess);
	if (cnf) {
		jQuery.post(wp2print_adminurl,
			{
				AjaxAction: 'oi-vendor-unassign',
				item_id: iid
			}
		);
		jQuery('.oiv-employee-'+iid).slideUp();
	}
	return false;
}

function user_orders_reorder(order_id, item_id) {
	jQuery('.user-orders-form .uoa-order-id').val(order_id);
	jQuery('.user-orders-form .uoa-item-id').val(item_id);
	jQuery('.user-orders-form').submit();
	return false;
}

function wp2print_oirsdate_change(order_id, item_id, sdate) {
	var oitems = jQuery('.item-rsdate').length;
	jQuery('.oirsdate-popup').attr('data-order-id', order_id);
	jQuery('.oirsdate-popup').attr('data-item-id', item_id);
	jQuery('.oirsdate-popup').attr('data-sdate', sdate);
	if (oitems > 1) {
		jQuery.colorbox({inline:true, href:"#oirsdate-popup"});
	} else {
		wp2print_oirsdate_submit();
	}
}

function wp2print_oirsdate_submit() {
	var order_id = jQuery('.oirsdate-popup').attr('data-order-id');
	var item_id = jQuery('.oirsdate-popup').attr('data-item-id');
	var sdate = jQuery('.oirsdate-popup').attr('data-sdate');

	var poption = 0;
	if (jQuery('.oirsdate-popup input').eq(1).is(':checked')) { poption = 1; }

	jQuery.colorbox.close();

	if (poption == 1) {
		jQuery('.item-rsdate').val(sdate);
	}
	jQuery('.oirsdate-popup input').eq(0).trigger('click');

	jQuery.post(wp2print_adminurl,
		{
			OirsdateAjaxAction: 'change-oirsdate',
			order_id: order_id,
			item_id: item_id,
			item_sdate: sdate,
			poption: poption
		},
		function(data) {
			if (poption == 1) {
				jQuery('.oirsdate-success').slideDown();
			} else {
				jQuery('.oirsdate-success-'+item_id).slideDown();
			}
			setTimeout(function(){ jQuery('.oirsdate-success').slideUp(); }, 2000);
		}
	);
}

function wp2print_employee_assign_to_me(order_id, item_id) {
	jQuery('.vatm-popup').attr('data-order-id', order_id);
	jQuery('.vatm-popup').attr('data-item-id', item_id);
	if (jQuery('.employee-assign-table .e-oitem').length > 1) {
		jQuery.colorbox({inline:true, href:"#vatm-popup"});
	} else {
		wp2print_employee_assign_to_me_submit();
	}
	
}

function wp2print_employee_assign_to_me_submit() {
	var order_id = jQuery('.vatm-popup').attr('data-order-id');
	var item_id = jQuery('.vatm-popup').attr('data-item-id');
	var vendor_id = jQuery('.vatm-popup').attr('data-vendor-id');
	var vendor_company_id = jQuery('.vatm-popup').attr('data-company-id');

	var poption = 0;
	if (jQuery('.vatm-popup input').eq(1).is(':checked')) { poption = 1; }

	jQuery.colorbox.close();

	jQuery('.vatm-popup input').eq(0).trigger('click');

	if (poption == 1) {
		jQuery('.employee-assign-table .order-item-employee option[value="'+vendor_id+'"]').attr('selected', 'selected');
		jQuery('.employee-assign-table .e-oitem .button').hide();
		if (vendor_company_id) {
			jQuery('.vendor-assign-table .order-item-vendor option[value="'+vendor_company_id+'"]').attr('selected', 'selected');
		}
		jQuery('.employee-assign-table .oive-success').slideDown();
	} else {
		jQuery('.employee-assign-table .e-oitem-'+item_id+' .order-item-employee option[value="'+vendor_id+'"]').attr('selected', 'selected');
		jQuery('.employee-assign-table .e-oitem-'+item_id+' .button').hide();
		if (vendor_company_id) {
			jQuery('.vendor-assign-table .v-order-item-'+item_id+' .order-item-vendor option[value="'+vendor_company_id+'"]').attr('selected', 'selected');
		}
		jQuery('.employee-assign-table .e-oitem-'+item_id+' .oive-success').slideDown();
	}

	jQuery.post(wp2print_adminurl,
		{
			AjaxAction: 'vendor-assign-to-me',
			order_id: order_id,
			item_id: item_id,
			poption: poption
		},
		function(data) {
			setTimeout(function(){ jQuery('.employee-assign-table .oive-success').slideUp(); }, 2000);
		}
	);
}

function wp2print_replace(s, f, r) {
	return s.replace(new RegExp(f, 'gi'), r);
}
