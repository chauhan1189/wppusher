<?php
// ADD TABLES

// cart data table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_cart_data (
  cart_item_key varchar(100) DEFAULT NULL,
  product_id int(11) DEFAULT NULL,
  product_type varchar(50) DEFAULT NULL,
  quantity int(11) DEFAULT NULL,
  price float DEFAULT NULL,
  product_attributes text,
  additional text,
  artwork_files text,
  artwork_thumbs text,
  atcaction varchar(50) DEFAULT NULL,
  date_added datetime DEFAULT NULL,
  KEY cart_item_key (cart_item_key)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// order items table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_order_items (
  item_id int(11) NOT NULL DEFAULT '0',
  product_id int(11) DEFAULT NULL,
  product_type varchar(50) DEFAULT NULL,
  quantity int(11) DEFAULT NULL,
  price float DEFAULT NULL,
  product_attributes text,
  additional text,
  artwork_files text,
  artwork_thumbs text,
  artwork_old_files text,
  atcaction varchar(50) DEFAULT NULL,
  PRIMARY KEY (item_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// matrix types table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_matrix_types (
  mtype_id int(11) NOT NULL AUTO_INCREMENT,
  product_id int(11) DEFAULT NULL,
  mtype tinyint(4) DEFAULT '0',
  title varchar(200) DEFAULT NULL,
  def_quantity int(11) DEFAULT NULL,
  attributes text,
  aterms text,
  numbers varchar(200) DEFAULT NULL,
  num_style tinyint(4) DEFAULT NULL,
  num_type tinyint(4) DEFAULT NULL,
  bq_numbers varchar(200) DEFAULT NULL,
  ltext_attr int(11) DEFAULT NULL,
  book_min_quantity int(11) DEFAULT '1',
  pq_style tinyint(4) DEFAULT '0',
  pq_numbers varchar(200) DEFAULT NULL,
  sorder int(11) DEFAULT NULL,
  PRIMARY KEY (mtype_id),
  KEY product_id (product_id),
  KEY mtype (mtype)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// matrix prices table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_matrix_prices (
  mtype_id int(11) DEFAULT NULL,
  aterms text,
  number int(11) DEFAULT NULL,
  price float DEFAULT NULL,
  KEY mtype_id (mtype_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// matrix sku table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_matrix_sku (
  mtype_id int(11) DEFAULT NULL,
  aterms text,
  sku varchar(100) DEFAULT NULL,
  KEY mtype_id (mtype_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// user groups table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_users_groups (
  group_id int(11) NOT NULL AUTO_INCREMENT,
  group_name varchar(200) DEFAULT NULL,
  use_printshop TINYINT DEFAULT '0',
  theme text,
  categories text,
  products text,
  payment_method varchar(200) DEFAULT NULL,
  invoice_zero TINYINT DEFAULT '0',
  shipping_rate float DEFAULT NULL,
  tax_rate float DEFAULT NULL,
  login_code_required TINYINT DEFAULT '0',
  login_code varchar(200) DEFAULT NULL,
  login_redirect varchar(255) DEFAULT NULL,
  logout_redirect varchar(255) DEFAULT NULL,
  order_emails text DEFAULT NULL,
  tax_id varchar(200) DEFAULT NULL,
  accounting_id varchar(200) DEFAULT NULL,
  orders_approving TINYINT DEFAULT '0',
  orders_approving_amount float DEFAULT NULL,
  assign_new_user TINYINT DEFAULT '0',
  aregister_domain varchar(200) DEFAULT NULL,
  orders_email_contents text,
  options text,
  billing_addresses text,
  shipping_addresses text,
  allow_modify_pdf TINYINT DEFAULT '0',
  created datetime DEFAULT NULL,
  KEY group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// aec orders table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_aec_orders (
  order_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  product_id int(11) DEFAULT NULL,
  qty int(11) DEFAULT NULL,
  term_id int(11) DEFAULT NULL,
  payment_method varchar(50) DEFAULT NULL,
  project_name varchar(200) DEFAULT NULL,
  smparams text DEFAULT NULL,
  artworkfiles text DEFAULT NULL,
  total_price float DEFAULT NULL,
  total_area float DEFAULT NULL,
  total_pages int(11) DEFAULT NULL,
  area_bw float DEFAULT NULL,
  pages_bw int(11) DEFAULT NULL,
  area_cl float DEFAULT NULL,
  pages_cl int(11) DEFAULT NULL,
  table_values text DEFAULT NULL,
  status tinyint DEFAULT '0',
  created datetime DEFAULT NULL,
  KEY order_id (order_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// quote orders table
$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_quotes (
  order_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  sender varchar(100) DEFAULT NULL,
  expire_date varchar(10) DEFAULT NULL,
  status tinyint DEFAULT '0',
  created datetime DEFAULT NULL,
  KEY order_id (order_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

$wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "print_products_quotes_items (
  order_id int(11) NOT NULL,
  product_id int(11) DEFAULT NULL,
  product_type varchar(10) DEFAULT NULL,
  variation_id int(11) DEFAULT NULL,
  attributes text DEFAULT NULL,
  quantity int(11) DEFAULT NULL,
  price float DEFAULT NULL,
  smparams text DEFAULT NULL,
  fmparams text DEFAULT NULL,
  width int(11) DEFAULT NULL,
  height int(11) DEFAULT NULL,
  product_attributes text DEFAULT NULL,
  additional text DEFAULT NULL,
  artworkfiles text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

// add additional fields to wp_woocommerce_attribute_taxonomies table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "woocommerce_attribute_taxonomies ADD attribute_order INT NULL DEFAULT '0'");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "woocommerce_attribute_taxonomies ADD attribute_img TINYINT NULL DEFAULT '0'");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "woocommerce_attribute_taxonomies ADD attribute_help_text TEXT NULL");

// add additional fields to wp_terms table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "terms ADD term_order INT NULL DEFAULT '0'");

// add additional fields to wp_print_products_matrix_types table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD num_style TINYINT NULL DEFAULT '0'");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD def_quantity INT NULL DEFAULT '0'");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD pq_style TINYINT(4) DEFAULT '0' AFTER num_type");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD pq_numbers VARCHAR(200) DEFAULT NULL AFTER pq_style");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD bq_numbers VARCHAR(200) DEFAULT NULL AFTER num_type");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD book_min_quantity INT(11) DEFAULT '1' AFTER bq_numbers");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types ADD ltext_attr INT(11) DEFAULT '0' AFTER bq_numbers");

// add index to wp_print_products_matrix_prices table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_prices ADD INDEX genindex (mtype_id, aterms(250), number)");

// add additional fields to wp_print_products_users_groups table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD use_printshop TINYINT NULL DEFAULT '0' AFTER group_name");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD use_privatestore TINYINT NULL DEFAULT '0' AFTER use_printshop");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD free_shipping TINYINT NULL AFTER payment_method");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD shipping_rate FLOAT NULL AFTER free_shipping");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD tax_rate FLOAT NULL AFTER shipping_rate");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD orders_approving TINYINT NULL DEFAULT '0' AFTER tax_id");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD orders_email_contents TEXT NULL AFTER orders_approving");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD invoice_zero TINYINT DEFAULT '0' AFTER payment_method");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD options TEXT NULL AFTER orders_email_contents");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD aregister_domain varchar(200) DEFAULT NULL AFTER orders_approving");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD login_code varchar(200) DEFAULT NULL AFTER tax_rate");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD login_code_required TINYINT NULL DEFAULT '0' AFTER tax_rate");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD billing_addresses TEXT NULL AFTER options");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD shipping_addresses TEXT NULL AFTER billing_addresses");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD allow_modify_pdf TINYINT NULL DEFAULT '0' AFTER shipping_addresses");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD assign_new_user TINYINT DEFAULT '0' AFTER orders_approving");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD accounting_id VARCHAR(200) NULL AFTER tax_id");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_users_groups ADD orders_approving_amount FLOAT NULL AFTER orders_approving");

// add additional fields to wp_print_products_aec_orders table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_aec_orders ADD area_bw FLOAT NULL AFTER total_pages");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_aec_orders ADD pages_bw INT NULL AFTER area_bw");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_aec_orders ADD area_cl FLOAT NULL AFTER pages_bw");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_aec_orders ADD pages_cl INT NULL AFTER area_cl");

// add additional fields to wp_print_products_quote_orders table
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_quotes ADD PRIMARY KEY (`order_id`)");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_quotes ADD sender VARCHAR(100) NULL AFTER user_id");

// change encoding of some fields
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_cart_data CHANGE artwork_files artwork_files TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_cart_data ADD artwork_thumbs TEXT NULL AFTER artwork_files");

$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_order_items CHANGE artwork_files artwork_files TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_order_items ADD artwork_thumbs TEXT NULL AFTER artwork_files");
$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_order_items ADD artwork_old_files TEXT NULL AFTER artwork_thumbs");

$wpdb->query("ALTER TABLE " . $wpdb->prefix . "print_products_matrix_types CHANGE title title VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");

// plugin version
update_option('wp2print_version', $wp2print_data['Version']);

// default attributes
$print_products_installed = (int)get_option('print_products_installed');
if (!$print_products_installed) {
	$print_products_settings = array();
	$printing_attributes = array();
	$finishing_attributes = array();

	// Size attribute
	$size_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'size'", $wpdb->prefix));
	if (!$size_check) {
		$aterms = array('A0','A1','A2','A3','A4','A5','A6','A7','A8','A9','A10','Letter (ANSI A)','Tabloid (ANSI B)','ANSI C','ANSI D','ANSI E','Arch A','Arch B','Arch C','Arch D','Arch E','Arch E1','Arch E2','Arch E3','B0','B1','B2','B3','B4','B5','B6','B7','B8','B9','B10','B1+','B2+','C0','C1','C2','C3','C4','C5','C6','C7','C8','C9','C10','A-2 envelope','A-6 envelope','A-7 envelope','A-8 envelope','A-10 envelope','A-Slim envelope','6(1/4) Commercial envelope','6(3/4) Commercial envelope','7 Official envelope','7(3/4) Official envelope','8(5/8) Official envelope','9 Official envelope','10 Official envelope','11 Official envelope','12 Official envelope','14 Official envelope','Legal','DL','CD case insert','85mm x 55mm','3.5in x 2in');

		$size_id = print_products_install_add_attribute('Size', 0);
		print_products_install_add_attribute_terms('pa_size', $aterms);
		$print_products_settings['size_attribute'] = $size_id;
		$printing_attributes[] = $size_id;
	}

	// Color attribute
	$color_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'color'", $wpdb->prefix));
	if (!$color_check) {
		$aterms = array('1/0 Black/White front only','1/1 Black/White both sides','4/0 Full-color front only','4/1 Full-color front - Black/White back','4/4 Full-color both sides');

		$color_id = print_products_install_add_attribute('Color', 1);
		print_products_install_add_attribute_terms('pa_color', $aterms);
		$print_products_settings['colour_attribute'] = $color_id;
		$printing_attributes[] = $color_id;
	}

	// Page count attribute
	$page_count_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'page-count'", $wpdb->prefix));
	if (!$page_count_check) {
		$aterms = array('8-page (4 pages plus cover)','12-page (8 pages plus cover)','16-page (12 pages plus cover)','20-page (16 pages plus cover)','24-page (20 pages plus cover)','28-page (24 pages plus cover)','32-page (28 pages plus cover)','36-page (32 pages plus cover)','40-page (36 pages plus cover)','44-page (40 pages plus cover)');

		$page_count_id = print_products_install_add_attribute('Page Count', 2);
		print_products_install_add_attribute_terms('pa_page-count', $aterms);
		$print_products_settings['page_count_attribute'] = $page_count_id;
		$printing_attributes[] = $page_count_id;
	}

	// Paper type attribute
	$paper_type_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'paper-type'", $wpdb->prefix));
	if (!$paper_type_check) {
		$aterms = array('80gsm Matte','80gsm Semi-Matte','80gsm Gloss','90gsm Matte','90gsm Semi-Matte','90gsm Gloss','100gsm Matte','100gsm Semi-Matte','100gsm Gloss','120gsm Matte','120gsm Semi-Matte','120gsm Gloss','150gsm Matte','150gsm Semi-Matte','150gsm Gloss','180gsm Matte','180gsm Semi-Matte','180gsm Gloss','200gsm Matte','200gsm Semi-Matte','200gsm Gloss','250gsm Matte','250gsm Semi-Matte','250gsm Gloss','300gsm Matte','300gsm Semi-Matte','300gsm Gloss','350gsm Matte','350gsm Semi-Matte','350gsm Gloss','400gsm Matte','400gsm Semi-Matte','400gsm Gloss','50# Cover Matte','50# Cover Semi-Matte','50# Cover Gloss','60# Cover Matte','60# Cover Semi-Matte','60# Cover Gloss','80# Cover Matte','80# Cover Semi-Matte','80# Cover Gloss','90# Cover Matte','90# Cover Semi-Matte','90# Cover Gloss','100# Cover Matte','100# Cover Semi-Matte','100# Cover Gloss','80# Text Matte','80# Text Semi-Matte','80# Text Gloss','100# Text Matte','100# Text Semi-Matte','100# Text Gloss','120# Text Matte','120# Text Semi-Matte','120# Text Gloss','12pt C1S','12pt C2S','14pt C1S','14pt C2S','16pt C1S','16pt C2S','White vinyl','Transparent vinyl','Blueblack vinyl','Reinforced vinyl','Self-adhesive vinyl','Translite','Backlite');

		$paper_type_id = print_products_install_add_attribute('Paper Type', 3);
		print_products_install_add_attribute_terms('pa_paper-type', $aterms);
		$print_products_settings['material_attribute'] = $paper_type_id;
		$printing_attributes[] = $paper_type_id;
	}

	// Mounting attribute
	$mounting_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'mounting'", $wpdb->prefix));
	if (!$mounting_check) {
		$aterms = array('None','400gsm card','Gatorboard','Folex');

		$mounting_id = print_products_install_add_attribute('Mounting', 4);
		print_products_install_add_attribute_terms('pa_mounting', $aterms);
		$finishing_attributes[] = $mounting_id;
	}

	// Encapsulation attribute
	$encapsulation_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'encapsulation'", $wpdb->prefix));
	if (!$encapsulation_check) {
		$aterms = array('None','Double-sided Matte','Double-sided Semi-Matte','Double-sided Gloss');

		$encapsulation_id = print_products_install_add_attribute('Encapsulation', 5);
		print_products_install_add_attribute_terms('pa_encapsulation', $aterms);
		$finishing_attributes[] = $encapsulation_id;
	}

	// Folding attribute
	$folding_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'folding'", $wpdb->prefix));
	if (!$folding_check) {
		$aterms = array('None','Scoring only','Half-fold','Letter-fold','Z-fold','Gate-fold');

		$folding_id = print_products_install_add_attribute('Folding', 6);
		print_products_install_add_attribute_terms('pa_folding', $aterms);
		$finishing_attributes[] = $folding_id;
	}

	// Binding attribute
	$binding_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'binding'", $wpdb->prefix));
	if (!$binding_check) {
		$aterms = array('None','Staples','Perfect','Spiral','Wire-O');

		$paper_type_id = print_products_install_add_attribute('Binding', 7);
		print_products_install_add_attribute_terms('pa_binding', $aterms);
		$finishing_attributes[] = $paper_type_id;
	}

	// Cover page attribute
	$cover_page_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'cover-page'", $wpdb->prefix));
	if (!$cover_page_check) {
		$aterms = array('None','White card','Transparent sheet');

		$cover_page_id = print_products_install_add_attribute('Cover Page', 8);
		print_products_install_add_attribute_terms('pa_cover-page', $aterms);
		$finishing_attributes[] = $cover_page_id;
	}

	// Drilling attribute
	$drilling_check = $wpdb->get_var(sprintf("SELECT attribute_id FROM %swoocommerce_attribute_taxonomies WHERE attribute_name = 'drilling'", $wpdb->prefix));
	if (!$drilling_check) {
		$aterms = array('None','2-hole','3-hole','4-hole');

		$drilling_id = print_products_install_add_attribute('Drilling', 9);
		print_products_install_add_attribute_terms('pa_drilling', $aterms);
		$finishing_attributes[] = $drilling_id;
	}

	if (count($print_products_settings)) {
		$print_products_settings['printing_attributes'] = serialize($printing_attributes);
		$print_products_settings['finishing_attributes'] = serialize($finishing_attributes);
		update_option('print_products_settings', $print_products_settings);
	}
	flush_rewrite_rules();
	delete_transient('wc_attribute_taxonomies');
	update_option('print_products_installed', '1');
}

$print_products_plugin_aec = get_option('print_products_plugin_aec');
if (!$print_products_plugin_aec) {
	$print_products_plugin_aec = array();
	$print_products_plugin_aec['aec_enable_size'] = '1';
	$print_products_plugin_aec['aec_dimensions_unit'] = 'ft';
	$print_products_plugin_aec['aec_coverage_ranges'] = '5,25,50,75,100';
	$print_products_plugin_aec['pay_now_text'] = 'Pay Now';
	update_option('print_products_plugin_aec', $print_products_plugin_aec);
}

$print_products_send_quote_options = get_option('print_products_send_quote_options');
if (!$print_products_send_quote_options) {
	$print_products_send_quote_options = array();
	$print_products_send_quote_options['pay_now_text'] = 'Pay Now';
	$print_products_send_quote_options['email_subject'] = 'Quote Request';
	$print_products_send_quote_options['email_message'] = '{QUOTE-DETAIL}'.chr(10).chr(10).'{PAY-NOW-LINK}';
	update_option('print_products_send_quote_options', $print_products_send_quote_options);
}

function print_products_install_add_attribute($alabel, $aorder = 0) {
	global $wpdb;
	$aname = str_replace(' ', '-', trim(strtolower($alabel)));
	$insert = array();
	$insert['attribute_name'] = $aname;
	$insert['attribute_label'] = $alabel;
	$insert['attribute_type'] = 'select';
	$insert['attribute_orderby'] = 'menu_order';
	$insert['attribute_public'] = '0';
	$insert['attribute_order'] = $aorder;
	$wpdb->insert($wpdb->prefix."woocommerce_attribute_taxonomies", $insert);
	return $wpdb->insert_id;
}

function print_products_install_add_attribute_terms($taxonomy, $aterms) {
	global $wpdb;
	foreach($aterms as $i => $aterm) {
		$insert = array();
		$insert['name'] = $aterm;
		$insert['slug'] = str_replace('_', '-', $taxonomy) . '-' . sanitize_title($aterm);
		$insert['term_order'] = $i;
		$wpdb->insert($wpdb->prefix."terms", $insert);
		$term_id = $wpdb->insert_id;

		$insert = array();
		$insert['term_id'] = $term_id;
		$insert['taxonomy'] = $taxonomy;
		$insert['description'] = '';
		$wpdb->insert($wpdb->prefix."term_taxonomy", $insert);
	}
}

// copy translation files
$plugins_lang_folder = $_SERVER['DOCUMENT_ROOT'].'/wp-content/languages/plugins';
if (is_dir($plugins_lang_folder)) {
	$langpofiles = glob(PRINT_PRODUCTS_PLUGIN_DIR.'/languages/*.po');
	if (count($langpofiles)) {
		foreach($langpofiles as $langpofile) {
			@copy($langpofile, $plugins_lang_folder.'/'.basename($langpofile));
		}
	}
	$langmofiles = glob(PRINT_PRODUCTS_PLUGIN_DIR.'/languages/*.mo');
	if (count($langmofiles)) {
		foreach($langmofiles as $langmofile) {
			@copy($langmofile, $plugins_lang_folder.'/'.basename($langmofile));
		}
	}
}

// update price fields
$term_tax_ids = array();
$term_taxes = $wpdb->get_results(sprintf("SELECT tt.* FROM %sterms t LEFT JOIN %sterm_taxonomy tt ON tt.term_id = t.term_id WHERE t.slug IN ('fixed', 'book', 'area', 'aec')", $wpdb->prefix, $wpdb->prefix));
if ($term_taxes) {
	foreach($term_taxes as $term_tax) { $term_tax_ids[] = $term_tax->term_taxonomy_id; }
	$term_relations = $wpdb->get_results(sprintf("SELECT * FROM %sterm_relationships WHERE term_taxonomy_id IN (%s)", $wpdb->prefix, implode(',', $term_tax_ids)));
	if ($term_relations) {
		foreach($term_relations as $term_relation) {
			$product_id = $term_relation->object_id;
			print_products_update_product_price($product_id);
		}
	}
}

// default attributes
$print_products_quotes_redone = (int)get_option('print_products_quotes_redone');
if (!$print_products_quotes_redone) {
	$quote_orders = $wpdb->get_results(sprintf("SELECT * FROM %sprint_products_quote_orders ORDER BY order_id", $wpdb->prefix));
	if ($quote_orders) {
		foreach($quote_orders as $quote_order) {
			$order_id = $quote_order->order_id;

			$insert = array();
			$insert['order_id'] = $order_id;
			$insert['user_id'] = $quote_order->user_id;
			$insert['expire_date'] = $quote_order->expire_date;
			$insert['status'] = $quote_order->status;
			$insert['created'] = $quote_order->created;
			$wpdb->insert($wpdb->prefix."print_products_quotes", $insert);

			$insert = array();
			$insert['order_id'] = $order_id;
			$insert['product_id'] = $quote_order->product_id;
			$insert['product_type'] = $quote_order->product_type;
			$insert['variation_id'] = $quote_order->variation_id;
			$insert['attributes'] = $quote_order->attributes;
			$insert['quantity'] = $quote_order->quantity;
			$insert['price'] = $quote_order->price;
			$insert['smparams'] = $quote_order->smparams;
			$insert['fmparams'] = $quote_order->fmparams;
			$insert['product_attributes'] = $quote_order->product_attributes;
			$insert['width'] = $quote_order->width;
			$insert['height'] = $quote_order->height;
			$insert['additional'] = $quote_order->additional;
			$insert['artworkfiles'] = $quote_order->artworkfiles;
			$wpdb->insert($wpdb->prefix."print_products_quotes_items", $insert);
		}
	}
	update_option('print_products_quotes_redone', '1');
}
?>