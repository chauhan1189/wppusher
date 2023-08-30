<?php if ($_GET['iformsuccess'] == 'true') { ?>
	<p class="wp2print-info-form-success"><?php echo nl2br($print_products_info_form_options['form_success_text']); ?></p>
<?php } else { ?>
	<?php
	$ssdata = array('project_name' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'address2' => '', 'city' => '', 'country' => $print_products_info_form_options['default_country'], 'state' => '', 'zip' => '', 'comments' => '');
	if (is_user_logged_in()) {
		global $current_user;
		$user_id = $current_user->ID;
		$country = get_user_meta($user_id, 'billing_country', true);
		$state = get_user_meta($user_id, 'billing_state', true);
		if (strlen($country)) {
			$ocountries = new WC_Countries();
			$wccountries = $ocountries->__get('countries');
			$wcstates = $ocountries->get_states($country);
			$wccountry = $wccountries[$country];
			if ($scp = strpos($wccountry, '(')) {
				$wccountry = trim(substr($wccountry, 0, $scp));
			}
			$country = array_search($wccountry, $countries);
			if (strlen($state)) {
				$state = $wcstates[$state];
			}
		}

		$ssdata['first_name'] = get_user_meta($user_id, 'first_name', true);
		$ssdata['last_name'] = get_user_meta($user_id, 'last_name', true);
		$ssdata['email'] = $current_user->user_email;
		$ssdata['phone'] = get_user_meta($user_id, 'billing_phone', true);
		$ssdata['address'] = get_user_meta($user_id, 'billing_address_1', true);
		$ssdata['address2'] = get_user_meta($user_id, 'billing_address_2', true);
		$ssdata['city'] = get_user_meta($user_id, 'billing_city', true);
		$ssdata['country'] = $country;
		$ssdata['state'] = $state;
		$ssdata['zip'] = get_user_meta($user_id, 'billing_postcode', true);
	}
	if (isset($_POST['wp2printinfoform']) && $_POST['wp2printinfoform'] == 'submit') {
		$ssdata = array(
			'project_name' => $_POST['project_name'],
			'first_name' => $_POST['first_name'],
			'last_name' => $_POST['last_name'],
			'email' => $_POST['email'],
			'phone' => $_POST['phone'],
			'address' => $_POST['address'],
			'address2' => $_POST['address2'],
			'city' => $_POST['city'],
			'country' => $_POST['country'],
			'state' => $_POST['state'],
			'zip' => $_POST['zip'],
			'comments' => $_POST['comments']
		);
	}
	?>
	<form method="POST" class="wp2print-info-form" enctype="multipart/form-data" onsubmit="return iform_submit();">
		<input type="hidden" name="wp2printinfoform" value="submit">
		<h2 class="form-title"><?php echo $print_products_info_form_options['form_title']; ?></h2>
		<div class="fields-box">
			<?php if (isset($_POST['iformerror']) && strlen($_POST['iformerror'])) { echo '<div class="iform-error">'.$_POST['iformerror'].'</div>'; } ?>
			<div class="field-row">
				<label><?php _e('Project Name', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="project_name" class="wif-project-name" value="<?php echo $ssdata['project_name']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('First Name', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="first_name" class="wif-first-name" value="<?php echo $ssdata['first_name']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('Last Name', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="last_name" class="wif-last-name" value="<?php echo $ssdata['last_name']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('Email', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="email" class="wif-email" value="<?php echo $ssdata['email']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('Telephone', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="phone" class="wif-phone" value="<?php echo $ssdata['phone']; ?>" />
			</div>
			<div class="field-row">
				<strong class="row-title"><?php _e('Delivery address', 'wp2print'); ?>:</strong>
			</div>
			<div class="field-row">
				<label><?php _e('Address line 1', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="address" class="wif-address" value="<?php echo $ssdata['address']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('Address line 2', 'wp2print'); ?></label>
				<input type="text" name="address2" class="wif-address2" value="<?php echo $ssdata['address2']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('City', 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="city" class="wif-city" value="<?php echo $ssdata['city']; ?>" />
			</div>
			<div class="field-row">
				<label><?php _e('Country', 'wp2print'); ?> <font class="red">*</font></label>
				<select name="country" class="wif-country" onchange="iform_country()">
					<?php foreach($countries as $ckey => $cval) { $s = ''; if ($ckey == $ssdata['country']) { $s = ' SELECTED'; } ?>
						<option value="<?php echo $ckey; ?>"<?php echo $s; ?>><?php _e($cval, 'woocommerce'); ?></option>
					<?php } ?>
				</select>
			</div>
			<?php if ($print_products_info_form_options['enable_state_field']) {
				$state_field_label = $print_products_info_form_options['state_field_label'];
				if (!strlen($state_field_label)) { $state_field_label = 'State'; } ?>
				<div class="field-row state-row">
					<label><?php _e($state_field_label, 'wp2print'); ?> <font class="red">*</font></label>
					<select name="state" class="wif-state" data-selected="<?php echo $ssdata['state']; ?>"><option value="">-- <?php _e('Select State', 'wp2print'); ?> --</option></select>
					<input type="text" name="state_text" class="wif-state-text" value="<?php echo $ssdata['state']; ?>" style="display:none;" />
				</div>
			<?php } ?>
			<?php $zip_field_label = $print_products_info_form_options['zip_field_label'];
			if (!strlen($zip_field_label)) { $zip_field_label = 'Zip'; } ?>
			<div class="field-row">
				<label><?php _e($zip_field_label, 'wp2print'); ?> <font class="red">*</font></label>
				<input type="text" name="zip" class="wif-zip" value="<?php echo $ssdata['zip']; ?>" />
			</div>
			<div class="field-row comments-row">
				<label><?php _e('Comments', 'wp2print'); ?></label>
				<textarea name="comments" class="wif-comments"><?php echo $ssdata['comments']; ?></textarea>
			</div>
		</div>
		<div id="uploadblock" class="uploads-box">
			<label><?php _e('Select file(s) to upload', 'wp2print'); ?></label>
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
		<?php do_action( 'wp2print_simple_submit_form' ); ?>
		<div class="submit-box">
			<input type="submit" value="<?php _e('Submit', 'wp2print'); ?>">
		</div>
		<input type="hidden" name="redirect_page" value="<?php the_permalink(); ?>">
	</form>
<script type="text/javascript" src="<?php echo PRINT_PRODUCTS_PLUGIN_URL; ?>js/plupload/plupload.full.min.js?ver=3.1.2"></script>
<script>
var states = new Array();
states[2] = "Berat,Bulqize,Delvine,Devoll,Diber,Durres,Elbasan,Fier,Gjirokaster,Gramsh,Has,Kavaje,Kolonje,Korce,Kruje,Kucove,Kukes,Kurbin,Lezhe,Librazhd,Lushnje,Malesi e Madhe,Mallakaster,Mat,Mirdite,Peqin,Permet,Pogradec,Puke,Sarande,Shkoder,Skrapar,Tepelene,Tirane,Tropoje,Vlore";
states[3] = "Adrar,Ain Defla,Ain Temouchent,Algiers,Annaba,Batna,Bechar,Bejaia,Biskra,Blida,Bordj Bou Arreridj,Bouira,Boumerdes,Chlef,Constantine,Djelfa,El Bayadh,El Oued,El Tarf,Ghardaia,Guelma,Illizi,Jijel,Khenchela,Laghouat,M'Sila,Mascara,Medea,Mila,Mostaganem,Naama,Oran,Ouargla,Oum el Bouaghi,Relizane,Saida,Setif,Sidi Bel Abbes,Skikda,Souk Ahras,Tamanghasset,Tebessa,Tiaret,Tindouf,Tipaza,Tissemsilt,Tizi Ouzou,Tlemcen";
states[5] = "Andorra la Vella,Canillo,Encamp,Escaldes-Engordany,La Massana,Ordino,Sant Julia de Loria";
states[6] = "Bengo,Benguela,Bie,Cabinda,Cuando Cubango,Cuanza Norte,Cuanza Sul,Cunene,Huambo,Huila,Luanda,Lunda Norte,Lunda Sul,Malanje,Moxico,Namibe,Uige,Zaire";
states[9] = "Barbuda,Saint George,Saint John,Saint Mary,Saint Paul,Saint Peter,Saint Philip";
states[10] = "Buenos Aires,Capital federal,Catamarca,Chaco,Chubut,Cordoba,Corrientes,Entre Rios,Formosa,Jujuy,La Pampa,La Rioja,Mendoza,Misiones,Neuquen,Rio Negro,Salta,San Juan,San Luis,Santa Cruz,Santa Fe,Santiago del Estero,Tierra del Fuego,Tucuman";
states[11] = "Aragatsotn,Ararat,Armavir,Gegharkunik,Kotayk,Lori,Shirak,Syunik,Tavush,Vayots Dzor,Yerevan (capital district)";
states[13] = "Australian Antarctic Territory,Australian Capital Territory,New South Wales,Northern Territory,Queensland,South Australia,Tasmania,Victoria,Western Australia";
states[14] = "Burgenland,Carinthia,Karnten,Lower Austria,Niederosterreich,Oberosterreich,Salzburg,Steiermark,Styria,Tirol,Tyrol,Upper Austria,Vienna,Voralberg,Vorarlberg,Wien";
states[15] = "Abseron,Agcabadi,Agdam,Agdas,Agstafa,Agsu,Ali Bayramli,Astara,Babak,Baki,Balakan,Barda,Beylagan,Bilasuvar,Cabrayll,Calilabad,Culfa,Daskasan,Davaci,Fuzuli,Gadabay,Ganca,Goranboy,Goycay,Haciqabul,Imisli,Ismayilli,Kalbacar,Kurdamir,Lacin,Lankaran,Lerik,Masalli,Mingacevir,Naftalan,Naxcivan,Neftcala,Oguz,Ordubad,Qabala,Qax,Qazax,Qobustan,Quba,Qubadli,Qusar,Saatli,Sabirabad,Sadarak,Sahbuz,Saki,Salyan,Samaxi,Samkir,Samux,Sarur,Siyazan,Sumqayit,Susa,Tartar,Tovuz,Ucar,Xacmaz,Xankandi,Xanlar,Xizi,Xocali,Xocavand,Yardimli,Yevlax,Zangilan,Zaqatala,Zardab";
states[16] = "Acklins and Crooked Islands,Bimini,Cat Island,Exuma,Freeport,Fresh Creek,Governor's Harbour,Green Turtle Cay,Harbour Island,High Rock,Inagua,Kemps Bay,Long Island,Marsh Harbour,Mayaguana,New Providence,Nicholls Town and Berry Islands,Ragged Island,Rock Sound,San Salvador and Rum Cay,Sandy Point";
states[17] = "Capital Governorate,Central Governorate,Muharraq Governorate,Northern Governorate,Southern Governorate";
states[18] = "Barisal Division,Chittagong Division,Dhaka Division,Khulna Division,Rajshahi Division,Sylhet Division";
states[19] = "Christ Church,Saint Andrew,Saint George,Saint James,Saint John,Saint Joseph,Saint Lucy,Saint Michael,Saint Peter,Saint Philip,Saint Thomas";
states[20] = "Brest Voblast,Homiel Voblast,Hrodna Voblast,Mahilyow Voblast,Minsk Voblast,Vitsebsk Voblast";
states[21] = "Antwerp,Antwerpen,Brabant Wallon,Brussels-Capital,Brussels-Capital Region,East Flanders,Flemish Brabant,Hainaut,Liege,Liege,Limburg,Luxembourg,Namur,Vlaamse Brabant,Walloon Brabant,West Flanders";
states[22] = "Belize,Cayo,Corozal,Orange Walk,Stann Creek,Toledo";
states[23] = "Alibori,Atakora,Atlantique,Borgou,Collines,Donga,Kouffo,Littoral,Mono,Oueme,Plateau,Zou";
states[26] = "Chuquisaca,Cochabamba,El Beni,La Paz,Oruro,Pando,Potosi,Santa Cruz,Tarija";
states[27] = "Federacija Bosna i Hercegovina,Republika Srpska";
states[28] = "Central,Ghanzi,Kgalagadi,Kgatleng,Kweneng,North-East,North-West,South-East,Southern";
states[30] = "Acre,Alagoas,Amapa,Amazonas,Bahia,Ceara,Distrito Federal,Espirito Santo,Goias,Maranhao,Mato Grosso,Mato Grosso do Sul,Minas Gerais,Para,Paraiba,Parana,Pernambuco,Piaui,Rio de Janeiro,Rio Grande do Norte,Rio Grande do Sul,Rondonia,Roraima,Santa Catarina,Sao Paulo,Sergipe,Tocatins";
states[32] = "Belait,Brunei-Muara,Temburong,Tutong";
states[33] = "Blagoevgrad,Burgas,Dobric,Gabrovo,Haskovo,Jambol,Kardzali,Kyustendil,Lovec,Montana,Pazardzik,Pernik,Pleven,Plovdiv,Razgrad,Ruse,Silistra,Sliven,Smoljan,Sofia,Stara Zagora,Sumen,Targovishte,Varna,Veliko Tarnovo,Vidin,Vraca";
states[34] = "Bale,Bam,Banwa,Bazega,Bougouriba,Boulgou,Boulkiemde,Comoe,Ganzourgou,Gnagna,Gourma,Houet,Ioba,Kadiogo,Kenedougou,Komondjari,Kompienga,Kossi,Koulpulogo,Kouritenga,Kourweogo,Leraba,Loroum,Mouhoun,Nahouri,Namentenga,Nayala,Noumbiel,Oubritenga,Oudalan,Passore,Poni,Sanguie,Sanmatenga,Seno,Siasili,Soum,Sourou,Tapoa,Tui,Yagha,Yatenga,Ziro,Zondoma,Zoundweogo";
states[35] = "Bubanza,Bujumbura,Bururi,Cankuzo,Cibitoke,Gitega,Karuzi,Kayanza,Makamba,Muramvya,Mwaro,Ngozi,Rutana,Ruyigi";
states[36] = "Baat Dambang,Banteay Mean Chey,Kach Kong,Kampong Chaam,Kampong Chhnang,Kampong Spueu,Kampong Thum,Kampot,Kandaal,Krachoh,Krong Kaeb,Krong Pailin,Mondol Kiri,Otdar Mean Chey,Phnom Penh,Pousaat,Preah Vihear,Prey Veaeng,Rotanak Kiri,Siem Reab,Stueng Traeng,Svaay Rieng,Taakaev,Xrong Preah Sihanouk";
states[37] = "Adamaoua Region,Centre Region,East Region,Far North Region,Littoral Region,North Region,North-West Region,South Region,South-West Region,West Region";
states[38] = "Alberta,British Columbia,Manitoba,New Brunswick,Newfoundland,Newfoundland and Labrador,Northwest Territories,Nova Scotia,Nunavut,Ontario,Prince Edward Island,Quebec,Saskatchewan,Yukon Territory";
states[39] = "Barlavento Islands,Sotavento Islands";
states[41] = "Bamingui-Bangoran,Bangui,Basse-Kotto,Haut-Mbomou,Haute-Kotto,Kemo,Lobaye,Mambere-Kadei,Mbomou,Nana-Grebizi,Nana-Mambere,Ombella-M'Poko,Ouaka,Ouham,Ouham-Pende,Sangha-Mbaere,Vakaga";
states[42] = "Batha,Biltine,Borkou-Ennedi-Tibesti,Chari-Baguirmi,Guera,Kanem,Lac,Logone-Occidental,Logone-Oriental,Mayo-Kebbi,Moyen-Chari,Ouaddai,Salamat,Tandjile";
states[43] = "Aisen del General Carlos Ibanez,Antofagasta,Araucania,Arica and Parinacota,Atacama,Bio-Bio,Coquimbo,Los Lagos,Los Rios,Magallanes,Maule,O'Higgins,Santiago Metropolitan Region,Tarapaca,Valparaiso";
states[44] = "Anhui,Beijing Municipality,Chongqing Municipality,Fujian,Gansu,Guangdong,Guangxi Autonomous Region,Guizhou,Hainan,Hebei,Heilongjiang,Henan,Hong Kong Special Administrative Region,Hubei,Hunan,Inner Mongolia Autonomous Region,Jiangsu,Jiangxi,Jilin,Liaoning,Macau Special Administrative Region,Ningxia Autonomous Region,Qinghai,Shaanxi,Shandong,Shanghai Municipality,Shanxi,Sichuan,Taiwan (disputed),Tianjin Municipality,Tibet Autonomous Region,Xinjiang Autonomous Region,Yunnan,Zhejiang";
states[47] = "Amazonas,Antioquia,Arauca,Atlantico,Bolivar,Boyaca,Caldas,Caqueta,Casanare,Cauca,Cesar,Choco,Cordoba,Cundinamarca,Distrito Capital de Bogota,Guainia,Guaviare,Huila,La Guajira,Magdalena,Meta,Narino,Norte de Santander,Putumayo,Quindio,Risaralda,San Andres, Providencia y Santa Catalina,Santander,Sucre,Tolima,Valle del Cauca,Vaupes,Vichada";
states[48] = "Anjouan Ndzouani,Grande Comore Ngazidja,Moheli Moili";
states[49] = "Bouenza,Brazzaville,Cuvette,Cuvette-Ouest,Kouilou,Lekoumou,Likouala,Niari,Plateaux,Pool,Sangha";
states[51] = "Alajuela,Cartago,Guanacaste,Heredia,Limon,Puntarenas,San Jose";
states[52] = "Agneby,Bafing,Bas-Sassandra,Denguele,Dix-Huit Montagnes,Fromager,Haut-Sassandra,Lacs,Lagunes,Marahoue,Moyen-Cavally,Moyen-Comoe,N'zi-Comoe,Savanes,Sud-Bandama,Sud-Comoe,Vallee du Bandama,Worodouqou,Zanzan";
states[53] = "Bjelovar-Bilogora,Brod-Posavina,Dubrovnik-Neretva,Istria,Karlovac,Koprivnica-Krizevci,Krapina-Zagorje,Lika-Senj,Medimurje,Osijek-Baranja,Pozega-Slavonia,Primorje-Gorski Kotar,Sibenik-Knin,Sisak-Moslavina,Split-Dalmatia,Varazdin,Virovitica-Podravina,Vukovar-Srijem,Zadar,Zagreb,Zagreb (city)";
states[54] = "Camagoey,Camaguey,Ciego de Avila,Ciego de Avila,Cienfuegos,Ciudad de la Habana,Granma,Guantanamo,Holguin,Holgun,Isla de la Juventud,La Habana,Las Tunas,Matanzas,Municipio Especial Isla de la Juventud,Pinar del Rio,Pinar del Roo,Sancti Spiritus,Sancti Spritus,Santiago de Cuba,Villa Clara";
states[55] = "Famagusta,Kyrenia,Larnarca,Limassol,Nicosia,Paphos";
states[56] = "Carlsbad Region,Central Bohemian Region,Hradec Kralove Region,Liberec Region,Moravian-Silesian Region,Olomouc Region,Pardubice Region,Plzen Region,Prague (capital city),South Bohemian Region,South Moravian Region,Usti nad Labem Region,Vysocina Region,Zlin Region";
states[57] = "Capital Region,Central Jutland,North Jutland,South Denmark,Zealand";
states[58] = "Ali Sabiah,Arta,Dikhil,Djibouti,Obock,Tadjoura";
states[59] = "Saint Andrew,Saint David,Saint George,Saint John,Saint Joseph,Saint Luke,Saint Mark,Saint Patrick,Saint Paul,Saint Peter";
states[60] = "Azua,Baoruco,Barahona,Dajabon,Distrito Nacional,Duarte,El Seybo,Elias Pina,Espaillat,Hato Mayor,Hermanas Mirabal,Independencia,La Altagracia,La Romana,La Vega,Maria Trinidad Sanchez,Monsenor Nouel,Monte Cristi,Monte Plata,Pedernales,Peravia,Puerto Plata,Samana,San Cristobal,San Juan,San Pedro de Macoris,Sanchez Ramirez,Santiago de los Caballeros,Santiago Rodriguez,Valverde";
states[62] = "Azuay,Bolivar,Canar,Carchi,Chimborazo,Cotopaxi,El Oro,Esmeraldas,Galapagos,Guayas,Imbabura,Loja,Los Rios,Manabi,Morona-Santiago,Napo,Orellana,Pastaza,Pichincha,Sucumbios,Tungurahua,Zamora-Chinchipe";
states[63] = "Alexandria Governorate,Aswan Governorate,Asyut Governorate,Beheira Governorate,Beni Suef Governorate,Cairo Governorate,Dakahlia Governorate,Damietta Governorate,Faiyum Governorate,Gharbia Governorate,Giza Governorate,Ismailia Governorate,Kafr el-Sheikh Governorate,Matruh Governorate,Minya Governorate,Monufia Governorate,New Valley Governorate,North Sinai Governorate,Port Said Governorate,Qalyubia Governorate,Qena Governorate,Red Sea Governorate,Sharqia Governorate,Sohag Governorate,South Sinai Governorate,Suez Governorate";
states[64] = "Ahuachapan,Cabanas,Chalatenango,Cuscatlan,La Libertad,La Paz,La Union,Morazan,San Miguel,San Salvador,San Vicente,Santa Ana,Sonsonate,Usulutan";
states[65] = "Annobon,Bioko Norte,Bioko Sur,Centro Sur,Kie-Ntem,Litoral,Region Continental,Region Insular,Wele-Nzas";
states[66] = "Anseba,Debub,Debubawi Keyih Bahri,Gash-Barka,Maakel,Semenawi Keyih Bahri";
states[67] = "Harju County,Hiiu County,Ida-Viru County,Jarva County,Jogeva County,Laane County,Laane-Viru County,Parnu County,Polva County,Rapla County,Saare County,Tartu County,Valga County,Viljandi County,Voru County";
states[68] = "Addis Ababa,Afar,Amara,Benshangul-Gumaz,Dire Dawa,Gambela Peoples,Harari People,Oromia,Somali,Southern Nations, Nationalities and Peoples,Tigrai";
states[71] = "Central,Eastern,Northern,Rotuma,Western";
states[72] = "Aland Islands,Eastern Finland,Lapland,Oulu,Southern Finland,Western Finland";
states[73] = "Ain,Aisne,Allier,Alpes de Hautes-Provence,Alpes-Maritimes,Ardeche,Ardennes,Ariege,Aube,Aude,Aveyron,Bas-Rhin,Bouches-du-Rhone,Calvados,Cantal,Charente,Charente-Maritime,Cher,Correze,Corse-du-Sud,Cote-d'Or,Cotes d'Armor,Creuse,Deux-Sevres,Dordogne,Doubs,Drome,Essonne,Eure,Eure-et-Loir,Finistere,Gard,Gers,Gironde,Haut-Rhin,Haute-Corse,Haute-Garonne,Haute-Loire,Haute-Marne,Haute-Saone,Haute-Savoie,Haute-Vienne,Hautes-Alpes,Hautes-Pyrenees,Hauts-de-Seine,Herault,Ille-et-Vilaine,Indre,Indre-et-Loire,Isere,Jura,Landes,Loir-et-Cher,Loire,Loire-Atlantique,Loiret,Lot,Lot-et-Garonne,Lozere,Maine-et-Loire,Manche,Marne,Mayenne,Meurthe-et-Moselle,Meuse,Morbihan,Moselle,Nievre,Nord,Oise,Orne,Paris,Pas-de-Calais,Puy-de-Dome,Pyrenees-Atlantiques,Pyrenees-Orientales,Rhone,Saone-et-Loire,Sarthe,Savoie,Seine-et-Marne,Seine-Maritime,Seine-Saint-Denis,Somme,Tarn,Tarn-et-Garonne,Territoire-de-Belfort,Val-d'Oise,Val-de-Marne,Var,Vaucluse,Vendee,Vienne,Vosges,Yonne,Yvelines";
states[75] = "Cayenne,Saint-Laurent-du-Maroni";
states[78] = "Estuaire,Haut-Ogooue,Moyen-Ogooue,Ngounie,Nyanga,Ogooue-Ivindo,Ogooue-Lolo,Ogooue-Maritime,Woleu-Ntem";
states[79] = "Banjul,Central River,Lower River,MacCarthy Island,North Bank,Upper River,Western";
states[80] = "Abkhazia,Adjara,Guria,Imereti,Kakheti,Kvemo Kartli,Mtskheta-Mtianeti,Racha-Lechkhumi and Kvemo Svaneti,Samegrelo-Zemo Svaneti,Samtskhe-Javakheti,Shida Kartli,Tbilisi";
states[81] = "Baden-Wrttemberg,Baden-Wurttemberg,Bavaria,Bayern,Berlin,Brandenburg,Bremen,Hamburg,Hesse,Hessen,Lower Saxony,Mecklenburg-Vorpommern,Niedersachsen,Nordrhein-Westfalen,North Rhine-Westphalia,Rheinland-Pfalz,Rhineland-Palatinate,Saarland,Sachsen,Sachsen-Anhalt,Saxony,Saxony-Anhalt,Schleswig-Holstein,Thringen,Thuringia";
states[82] = "Ashanti Region,Brong-Ahafo Region,Central Region,Eastern Region,Greater Accra Region,Northern Region,Upper East Region,Upper West Region,Volta Region,Western Region";
states[84] = "Achaea,Aetolia-Acarnania,Arcadia,Argolis,Arta,Attica,Boeotia,Chalcidice,Chania,Chios,Corfu,Corinthia,Cyclades,Dodecanese,Drama,Euboea,Evros,Evrytania,Florina,Grevena,Ilia,Imathia,Ioannina,Irakleion,Karditsa,Kastoria,Kavala,Kefallinia,Kilkis,Kozani,Laconia,Larissa,Lasithion,Lefkada,Lesbos,Magnesia,Messinia,Mount Athos,Pella,Phocis,Phthiotis,Preveza,Rethymnon,Rhodope,Samos,Serres,Thesprotia,Thessaloniki,Trikala,Xanthi,Zakynthos";
states[86] = "Saint Andrew,Saint David,Saint George,Saint John,Saint Mark,Saint Patrick";
states[89] = "Alta Verapez,Baja Verapez,Chimaltenango,Chiquimula,El Progreso,Escuintla,Guatemala,Huehuetenango,Izabal,Jalapa,Jutiapa,Peten,Quetzaltenango,Quiche,Reta.thuleu,Sacatepequez,San Marcos,Santa Rosa,Solol6,Suchitepequez,Totonicapan,Zacapa";
states[90] = "Beyla,Boffa,Boke,Conakry,Coyah,Dabola,Dalaba,Dinguiraye,Dubreka,Faranah,Forecariah,Fria,Gaoual,Guekedou,Kankan,Kerouane,Kindia,Kissidougou,Koubia,Koundara,Kouroussa,Labe,Lelouma,Lola,Macenta,Mali,Mamou,Mandiana,Nzerekore,Pita,Siguiri,Telimele,Tougue,Yomou";
states[91] = "Bafata,Biombo,Bissau,Bolama,Cacheu,Gabu,Oio,Quloara,Tombali";
states[92] = "Barima-Waini,Cuyuni-Mazaruni,Demerara-Mahaica,East Berbice-Corentyne,Essequibo Islands-West Demerara,Mahaica-Berbice,Pomeroon-Supenaam,Potaro-Siparuni,Upper Demerara-Berbice,Upper Takutu-Upper Essequibo";
states[93] = "Artibonite Department,Centre Department,Grande'Anse Department,Nippes,Nord Department,Nord-Est Department,Nord-Ouest Department,Ouest Department,Sud Department,Sud-Est Department";
states[95] = "Atlantida,Choluteca,Colon,Comayagua,Copan,Cortes,El Paraiso,Francisco Morazan,Gracias a Dios,Intibuca,Islas de la Bahia,La Paz,Lempira,Ocotepeque,Olancho,Santa Barbara,Valle,Yoro";
states[97] = "Bacs-Kiskun,Baranya,Bekes,Bekescsaba,Borsod-Abauj-Zemplen,Budapest,Csongrad,Debrecen,Dunaujvaros,Eger,Fejer,Gyor,Gyor-Moson-Sopron,Hajdu-Bihar,Heves,Hodmezovasarhely,Jasz-Nagykun-Szolnok,Kaposvar,Kecskemet,Komarom-Esztergom,Miskolc,Nagykanizsa,Nograd,Nyiregyhaza,Pecs,Pest,Salgotarjan,Somogy,Sopron,Szabolcs-Szatmar-Bereg,Szeged,Szekesfehervar,Szekszard,Szolnok,Szombathely,Tatabanya,Tolna,Vas,Veszprem,Zala,Zalaegerszeg";
states[98] = "Capital Region,East,Northeast,Northwest,South,Southern Peninsula,West,Westfjords";
states[99] = "Andaman and Nicobar Islands,Andhra Pradesh,Arunachal Pradesh,Assam,Bihar,Chandigarh,Chhattisgarh,Dadra and Nagar Haveli,Daman and Diu,Delhi,Goa,Gujarat,Haryana,Himachal Pradesh,Jammu and Kashmir,Jharkhand,Karnataka,Kerala,Lakshadweep,Madhya Pradesh,Maharashtra,Manipur,Meghalaya,Mizoram,Nagaland,Orissa,Pondicherry,Punjab,Rajasthan,Sikkim,Tamil Nadu,Tripura,Uttar Pradesh,Uttaranchal,West Bengal";
states[100] = "Aceh,Bali,Bangka Belitung,Banten,Bengkulu,East Kalimantan,Gorontalo,Irian Jaya,Jakarta Raya,Jambi,Jawa Barat,Jawa Tengah,Jawa Timur,Kalimantan Barat,Kalimantan Selatan,Kalimantan Timur,Kepulauan Riau,Lampung,Maluku,Maluku Utara,Nusa Tenggara Barat,Nusa Tenggara Timur,Papua,Riau,Sulawesi Selatan,Sulawesi Tengah,Sulawesi Tenggara,Sulawesi Utara,Sumatera Utara,Sumatra Barat,Sumatra Selatan,Yogyakarta";
states[101] = "Ardabil,Bushehr,Chaharmahal and Bakhtiari,East Azarbaijan,Esfahan,Fars,Gilan,Golestan,Hamadan,Hormozgan,Iiam,Kerman,Kermanshah,Khuzestan,Kohgiluyeh and Boyer-Ahmad,Kordestan,Lorestan,Markazi,Mazandaran,North Khorasan,Qazvin,Qom,Razavi Khorasan,Semnan,Sistan and Baluchestan,South Khorasan,Tehran,West Azarbaijan,Yazd,Zanjan";
states[102] = "Al-Anbar Governorate,Al-Muthanna Governorate,Al-Qadisiyyah Governorate,Arbil Governorate,As-Sulaymaniyyah Governorate,Babil Governorate,Baghdad Governorate,Basra Governorate,Dhi Qar Governorate,Diyala Governorate,Duhok Governorate,Karbala Governorate,Kirkuk Governorate,Maysan Governorate,Najaf Governorate,Ninawa Governorate,Salah ad Din Governorate,Wasit Governorate";
states[103] = "Antrim,Armagh,Carlow,Cavan,Clare,Cork,County Carlow,County Cavan,County Clare,County Cork,County Donegal,County Dublin,County Galway,County Kerry,County Kildare,County Kilkenny,County Laois,County Leitrim,County Limerick,County Longford,County Louth,County Mayo,County Meath,County Monaghan,County Offaly,County Roscommon,County Sligo,County Tipperary,County Waterford,County Westmeath,County Wexford,County Wicklow,Donegal,Down,Dublin,Fermanagh,Galway,Kerry,Kildare,Kilkenny,Laois,Leitrim,Limerick,Londonderry,Longford,Louth,Mayo,Meath,Monaghan,Offaly,Roscommon,Sligo,Tipperary,Tyrone,Waterford,Westmeath,Wexford,Wicklow";
states[104] = "Center,Haifa,Jerusalem,North,South,Tel Aviv";
states[105] = "Abruzzi,Apulia,Basilicata,Calabria,Campania,Emilia-Romagna,Friuli-Venezia Giulia,Lazio,Liguria,Lombardy,Marche,Molise,Piedmont,Sardinia,Sicily,Trentino-Alto Adige,Tuscany,Umbria,Valle d'Aosta,Veneto";
states[106] = "Clarendon,Hanover,Kingston,Manchester,Portland,Saint Andrew,Saint Ann,Saint Catherine,Saint Elizabeth,Saint James,Saint Mary,Saint Thomea,Trelawny,Westmoreland";
states[107] = "Aichi Prefecture,Akita Prefecture,Aomori Prefecture,Chiba Prefecture,Ehime Prefecture,Fukui Prefecture,Fukuoka Prefecture,Fukusima Prefecture,Gifu Prefecture,Gunma Prefecture,Hiroshima Prefecture,Hokkaido Prefecture,Hyogo Prefecture,Ibaraki Prefecture,Ishikawa Prefecture,Iwate Prefecture,Kagawa Prefecture,Kagoshima Prefecture,Kanagawa Prefecture,Kochi Prefecture,Kumamoto Prefecture,Kyoto Prefecture,Mie Prefecture,Miyagi Prefecture,Miyazaki Prefecture,Nagano Prefecture,Nagasaki Prefecture,Nara Prefecture,Niigata Prefecture,Oita Prefecture,Okayama Prefecture,Okinawa Prefecture,Osaka Prefecture,Saga Prefecture,Saitama Prefecture,Shiga Prefecture,Shimane Prefecture,Shizuoka Prefecture,Tochigi Prefecture,Tokushima Prefecture,Tokyo Prefecture,Tottori Prefecture,Toyama Prefecture,Wakayama Prefecture,Yamagata Prefecture,Yamaguchi Prefecture,Yamanashi Prefecture";
states[108] = "Ajln,Al 'Aqaba,Al Balqa',Al Karak,Al Mafraq,Amman,At Tafilah,Az Zarga,Irbid,Jarash,Ma'an,Madaba";
states[109] = "Akmola,Aktobe,Almaty,Almaty (city),Astana,Atyrau,Baikonur,East Kazakhstan,Karagandy,Kostanay,Kyzylorda,Mangystau,North Kazakhstan,Pavlodar,South Kazakhstan,West Kazakhstan,Zhambyl";
states[110] = "Central,Coast,Eastern,Nairobi,North-Eastern,Nyanza,Rift Valley,Western";
states[111] = "Gilbert Islands,Line Islands,Phoenix Islands";
states[112] = "Chagang-do,Hamgyongbuk-do,Hamgyongnam-do,Hwanghaebuk-do,Hwanghaenam-do,Kaesong-si,Kangwon-do,Najin Sonbong-si,Nampo-si,Pyonganbuk-do,Pyongannam-do,Pyongyang-ai,Yanggang-do";
states[113] = "Busan Gwang'yeogsi,Chungcheongbugdo,Chungcheongnamdo,Daegu Gwang'yeogsi,Daejeon Gwang'yeogsi,Gang'weondo,Gwangju Gwang'yeogsi,Gyeonggido,Gyeongsangbugdo,Gyeongsangnamdo,Incheon Gwang'yeogsi,Jejudo,Jeonrabugdo,Jeonranamdo,Seoul Teugbyeolsi,Ulsan Gwang'yeogsi";
states[114] = "Al Ahmadi Governorate,Al Asimah Governorate,Al Farwaniyah Governorate,Al Jahra Governorate,Hawalli Governorate,Mubarak Al-Kabeer Governorate";
states[115] = "Batken,Bishkek,Chuy,Issyk-Kul,Jalal-Abad,Naryn,Osh,Talas";
states[116] = "Attapu,Bokeo,Bolikhamxai,Champasak,Houaphan,Khammouan,Louang Namtha,Louangphabang,Oudomxai,Phongsali,Salavan,Savannakhet,Vientiane,Xaignabouli,Xekong,Xiangkhoang,Xiasomboun";
states[117] = "Aizkraukle,Aluksne,Balvi,Bauska,Cesis,Daugavpils,Daugavpils (city),Dobele,Gulbene,Jekabpils,Jelgava,Jelgava (city),Jurmala (city),Kraslava,Kuldiga,Liepaja,Liepaja (city),Limbazi,Ludza,Madona,Ogre,Preili,Rezekne,Rezekne (city),Riga,Riga (city),Saldus,Talsi,Tukums,Valka,Valmiera,Ventspils,Ventspils (city)";
states[118] = "Aakkar Governorate,Baalbek-Hermel Governorate,Beirut Governorate,Beqaa Governorate,Mount Lebanon Governorate,Nabatieh Governorate,North Governorate,South Governorate";
states[119] = "Berea,Butha-Buthe,Leribe,Mafeteng,Maseru,Mohale's Hoek,Mokhotlong,Qacha's Nek,Quthing,Thaba-Tseka";
states[120] = "Bomi,Bong,Grand Basaa,Grand Cape Mount,Grand Gedeh,Grand Kru,Lofa,Margibi,Maryland,Montserrado,Nimba,Rivercess,Sinoe";
states[121] = "Al Butnan,Al Jabal al Akhdar,Al Jabal al Gharbi,Al Jfara,Al Jufrah,Al Kufrah,Al Marj,Al Murgub,Al Wahat,An Nuqat al Khams,Az Zawiyah,Benghazi,Darnah,Ghat,Misurata,Murzuq,Nalut,Sabha,Surt,Tripoli,Wadi Al Hayaa,Wadi Al Shatii";
states[122] = "Balzers,Eschen,Gamprin,Mauren,Planken,Ruggell,Schaan,Schellenberg,Triesen,Triesenberg,Vaduz";
states[123] = "Alytus County,Kaunas County,Klaipeda County,Marijampole County,Panevezys County,Siauliai County,Taurage County,Telsiai County,Utena County,Vilnius County";
states[124] = "Diekirch,Grevenmacher,Luxembourg";
states[126] = "Aerodrom municipality,Aracinovo,Berovo,Bitola,Bogdanci,Bogovinje,Bosilovo,Brvenica,Butel,Cair,Caska,Centar,Centar Zupa,Cesinovo-Oblesevo,Cucer Sandevo,Debar,Debarca,Delcevo,Demir Hisar,Demir Kapija,Dojran,Dolneni,Drugovo,Gazi Baba,Gevgelija,Gjorce Petrov,Gostivar,Gradsko,Ilinden,Jegunovce,Karbinci,Karpos,Kavadarci,Kicevo,Kisela Voda,Kocani,Konce,Kratovo,Kriva Palanka,Krivogastani,Krusevo,Kumanovo,Lipkovo,Lozovo,Makedonska Kamenica,Makedonski Brod,Mavrovo-i-Rostusa,Mogila,Negotino,Novaci,Novo Selo,Ohrid,Oslomej,Pehcevo,Petrovec,Plasnica,Prilep,Probistip,Radovis,Rankovce,Resen,Rosoman,Saraj,Sopiste,Staro Nagoricane,Stip,Struga,Strumica,Studenicani,Suto Orizari,Sveti Nikole,Tearce,Tetovo,Valandovo,Vasilevo,Veles,Vevcani,Vinica,Vranestica,Vrapciste,Zajas,Zelenikovo,Zelino,Zrnovci";
states[127] = "Antananarivo,Antsiranana,Fianarantsoa,Mahajanga,Toamasina,Toliara";
states[128] = "Balaka,Blantyre,Chikwawa,Chiradzulu,Chitipa,Dedza,Dowa,Karonga,Kasungu,Likoma Island,Lilongwe,Machinga,Mangochi,Mchinji,Mulanje,Mwanza,Mzimba,Nkhata Bay,Nkhotakota,Nsanje,Ntcheu,Ntchisi,Phalomba,Rumphi,Salima,Thyolo,Zomba";
states[129] = "Johor,Kedah,Kelantan,Melaka,Negeri Sembilan,Pahang,Perak,Perlis,Pulau Pinang,Sabah,Sarawak,Selangor,Terengganu,Wilayah Persekutuan Kuala Lumpur,Wilayah Persekutuan Labuan,Wilayah Persekutuan Putrajaya";
states[130] = "Alif Alif Atoll,Baa,Dhaalu,Faafu,Gaaf Alif,Gaafu Dhaalu Atoll,Gnaviyani,Haa Alif,Haa Dhaalu Atoll,Kaafu,Laamu Atoll,Lhaviyani,Male Capital,Meemu,Noonu,Raa,Seenu Atoll,Shaviyani,Thaa,Vaavu";
states[131] = "Bamako,Gao,Kayes,Kidal,Koulikoro,Mopti,Segou,Sikasso,Tombouctou";
states[132] = "Attard,Balzan,Birzebbuga,Birgu,Birkirkara,Bormla,Dingli,Fgura,Floriana,Fontana,Ghajnsielem,Gharb,Gharghur,Ghasri,Ghaxaq,Gudja,Gzira,Hamrun,Iklin,Isla,Kalkara,Kercem,Kirkop,Lija,Luqa,Marsa,Marsaskala,Marsaxlokk,Mdina,Mellieha,Mgarr,Mosta,Mqabba,Msida,Mtarfa,Munxar,Nadur,Naxxar,Paola,Pembroke,Pieta,Qala,Qormi,Qrendi,Rabat Gozo,Rabat Malta,Safi,Saint John,Saint Julian's,Saint Lawrence,Saint Lucia's,Saint Paul's Bay,Sannat,Santa Venera,Siggiewi,Sliema,Swieqi,Ta' Xbiex,Tarxien,Valletta,Xaghra,Xewkija,Xghajra";
states[133] = "Ailinglapalap,Ailuk,Arno,Aur,Bikini,Ebon,Eniwetok,Jaluit,Kili,Kwajalein,Lae,Lib,Likiep,Majuro,Maloelap,Mejit,Mili,Namorik,Namu,Rongelap,Ujae,Ujelang,Utirik,Wotho,Wotje";
states[135] = "Adrar,Assaba,Brakna,Dakhlet Nouadhibou,Gorgol,Guidimaka,Hodh ech Chargui,Hodh el Charbi,Inchiri,Nouakchott,Tagant,Tiris Zemmour,Trarza";
states[136] = "Agalega Islands,Beau Bassin-Rose Hill,Black River,Cargados Carajos Shoals,Curepipe,Flacq,Grand Port,Moka,Pamplemousses,Plaines Wilhems,Port Louis,Port Louis (district),Quatre Bornes,Riviere du Rempart,Rodrigues Island,Savanne,Vacosa-Phoenix";
states[138] = "Aguascalientes,Baja California,Baja California Sur,Campeche,Chiapas,Chihushua,Coahuila,Col ima,Distrito Federal,Durango,Guanajuato,Guerrero,Hidalgo,Jalisco,Mexico,Michoacin,Moreloa,Nayarit,Nuevo Leon,Oaxaca,Puebla,Queretaro,Quintana Roo,San Luis Potosi,Sinaloa,Sonora,Tabasco,Tamaulipas,Tlaxcala,Veracruz,Yucatan,Zacatecas";
states[139] = "Chuuk,Kosrae,Pohnpei,Yap";
states[140] = "Anenii Noi,Balti,Basarabeanca,Bender,Briceni,Cahul,Cainari,Calarasi,Cameanca,Cantemir,Causeni/Causani,Ceadir-Lunga,Chisinau,Cimislia,Comrat,Criuleni,Donduseni,Drochia,Dubasari,Edinet,Falesti,Floresti,Glodeni,Hincesti,Ialoveni,Leova,Nisporeni,Ocnita,Orhei,Rezina,Ribnita,Riscani,Singerei,Slobozia,Soroca,Stefan-Voda,Straseni,Taraclia,Telenesti,Tiraspol,Ungheni,Vulcanesti";
states[142] = "Arhangay,Bayan-Olgiy,Bayanhongor,Bulgan,Darhan uul,Dornod,Dornogov,,DundgovL,Dzavhan,Govi-Altay,Govi-Smber,Hentiy,Khovd,Khovsgol,Omnogovi,Orhon,Ovorkhangai,Selenge,Shbaatar,Tov,Ulanbaatar,Uvs";
states[144] = "Agadir,Ait Baha,Ait Melloul,Al Haouz,Al Hoceima,Assa-Zag,Azilal,Ben Sllmane,Beni Mellal,Berkane,Boujdour,Boulemane,Casablanca,Chefchaouene,Chichaoua,El Hajeb,El Jadida,Errachidia,Es Smara,Essaouira,Fes,Figuig,Guelmim,Ifrane,Jerada,Kelaat Sraghna,Kenitra,Khemisaet,Khenifra,Khouribga,Laayoune (EH),Larache,Marrakech,Meknses,Nador,Ouarzazate,Oued ed Dahab (EH),Oujda,Rabat-Sale,Safi,Sefrou,Settat,Sidl Kacem,Tan-Tan,Tanger,Taounate,Taroudannt,Tata,Taza,Tetouan,Tiznit";
states[145] = "Cabo Delgado,Gaza,Inhambane,Manica,Maputo,Maputo (city),Nampula,Niassa,Sofala,Tete,Zambezia";
states[146] = "Ayeyarwady Division,Bago Division,Chin State,Kachin State,Kayah State,Kayin State,Magway Division,Mandalay Division,Mon State,Rakhine State,Sagaing Division,Shan State,Tanintharyi Division,Yangon Division";
states[147] = "Caprivi,Erongo,Hardap,Karas,Khomae,Kunene,Ohangwena,Okavango,Omaheke,Omusati,Oshana,Oshikoto,Otjozondjupa";
states[148] = "Aiwo,Anabar,Anetan,Anibare,Baiti,Boe,Buada,Denigomodu,Ewa,Ijuw,Meneng,Nibok,Uaboe,Yaren";
states[149] = "Bagmati,Bheri,Dhawalagiri,Gandaki,Janakpur,Karnali,Kosi,Lumbini,Mahakali,Mechi,Narayani,Rapti,Sagarmatha,Seti";
states[150] = "Drente,Drenthe,Flevoland,Friesland,Gelderland,Groningen,Limburg,Noord-Brabant,Noord-Holland,North Brabant,North Holland,Overijssel,South Holland,Utrecht,Zeeland,Zuid-Holland";
states[153] = "Auckland Region,Bay of Plenty Region,Canterbury Region,Gisborne Region,Hawke's Bay Region,Manawatu-Wanganui Region,Marlborough Region,Nelson Region,Northland Region,Otago Region,Southland Region,Taranaki Region,Tasman Region,Waikato Region,Wellington Region,West Coast Region";
states[154] = "Atlantico Norte,Atlantico Sur,Boaco,Carazo,Chinandega,Chontales,Esteli,Grenada,Jinotega,Leon,Madriz,Managua,Masaya,Matagalpa,Nueva Segovia,Rio San Juan,Rivas";
states[155] = "Agadez Region,Diffa Region,Dosso Region,Maradi Region,Niamey Capital,Tahoua Region,Tillaberi Region,Zinder Region";
states[156] = "Abia,Abuja Capital Territory,Adamawa,Akwa Ibom,Anambra,Bauchi,Bayelsa,Benue,Borno,Cross River,Delta,Ebonyi,Edo,Ekiti,Enugu,Gombe,Imo,Jigawa,Kaduna,Kano,Katsina,Kebbi,Kogi,Kwara,Lagos,Nassarawa,Niger,Ogun,Ondo,Osun,Oyo,Plateau,Rivers,Sokoto,Taraba,Yobe,Zamfara";
states[160] = "Akershus,Aust-Agder,Buskerud,Finnmark,Hedmark,Hordaland,Jan Mayen,More og Romsdal,Nord-Trondelag,Nordland,Oppland,Oslo,Rogaland,Sogn og Fjordane,Svalbard,Sor-Trondelag,Telemark,Troms,Vest-Agder,Vestfold,Ostfold";
states[161] = "Ad Dakhillyah,Al Batinah,Al Janblyah,Al Wusta,Ash Sharqlyah,Az Zahirah,Masqat,Musandam";
states[162] = "Azad Kashmir,Balochistan,Federally Administered Tribal Areas,Islamabad Capital Territory,North-West Frontier,Northern Areas,Punjab,Sindh";
states[163] = "Aimeliik,Airai,Angaur,Hatobohei,Kayangel,Koror,Melekeok,Ngaraard,Ngarchelong,Ngardmau,Ngatpang,Ngchesar,Ngeremlengui,Ngiwal,Peleliu,Sonsorol";
states[164] = "Bocas del Toro,Chiriqui,Cocle,Colon,Comarca de San Blas,Darien,Herrera,Loa Santoa,Panama,Veraguas";
states[165] = "Central,Chimbu,East New Britain,East Sepik,Eastern Highlands,Enga,Gulf,Madang,Manus,Milne Bay,Morobe,National Capital (Port Moresby),New Ireland,North Solomons,Northern (Oro),Santaun,Southern Highlands,West New Britain,Western (Fly River),Western Highlands";
states[166] = "Alto Paraguay Department,Alto Parana Department,Amambay Department,Asuncion Capital,Boqueron Department,Caaguazu Department,Caazapa Department,Canindeyu Department,Central Department,Concepcion Department,Cordillera Department,Guaira Department,Itapua Department,Misiones Department,Neembucu Department,Paraguari Department,Presidente Hayes Department,San Pedro Department";
states[167] = "Amazonas Region,Ancash Region,Apurimac Region,Arequipa Region,Ayacucho Region,Cajamarca Region,Cuzco Region,El Callao Region,Huancavelica Region,Huanuco Region,Ica Region,Junin Region,La Libertad Region,Lambayeque Region,Lima Region,Loreto Region,Madre de Dios Region,Moquegua Region,Pasco Region,Piura Region,Puno Region,San Martin Region,Tacna Region,Tumbes Region,Ucayali Region";
states[168] = "Abra,Agusan del Norte,Agusan del Sur,Aklan,Albay,Antique,Apayao,Aurora,Basilan,Batanes,Batangas,Batasn,Benguet,Biliran,Bohol,Bukidnon,Bulacan,Cagayan,Camarines Norte,Camarines Sur,Camiguin,Capiz,Catanduanes,Cavite,Cebu,Compostela Valley,Davao,Davao del Sur,Davao Oriental,Eastern Samar,Guimaras,Ifugao,Ilocos Norte,Ilocos Sur,Iloilo,Isabela,Kalinga-Apayso,La Union,Laguna,Lanao del Norte,Lanao del Sur,Leyte,Maguindanao,Marinduque,Masbate,Mindoro Occidental,Mindoro Oriental,Misamis Occidental,Misamis Oriental,Mountain,Negroe Occidental,Negros Oriental,North Cotabato,Northern Samar,Nueva Ecija,Nueva Vizcaya,Palawan,Pampanga,Pangasinan,Quezon,Quirino,Rizal,Romblon,Sarangani,Siquijor,Sorsogon,South Cotabato,Southern Leyte,Sultan Kudarat,Sulu,Surigao del Norte,Surigao del Sur,Tarlac,Tawi-Tawi,Western Samar,Zambales,Zamboanga del Norte,Zamboanga del Sur,Zamboanga Sibiguey";
states[170] = "Greater Poland,Kuyavia-Pomerania,Lesser Poland,Lodz,Lower Silesia,Lublin,Lubusz,Masovia,Opole,Podlachia,Pomerania,Silesia,Subcarpathia,Swietokrzyskie,Warmia-Masuria,West Pomerania";
states[171] = "Autonomous Region of Azores,Autonomous Region of Madeira,Aveiro,Beja,Braga,Braganca,Castelo Branco,Coimbra,Evora,Faro,Guarda,Leiria,Lisbon,Oporto,Portalegre,Santarem,Setubal,Viana do Castelo,Vila Real,Viseu";
states[173] = "Ad Dawhah Municipality,Al Ghuwayriyah Municipality,Al Jumayliyah Municipality,Al Khawr Municipality,Al Wakrah Municipality,Ar Rayyan Municipality,Jariyan al Batnah Municipality,Madinat ash Shamal Municipality,Umm Salal Municipality";
states[175] = "Alba,Arad,Arges,Bacau,Bihor,Bistrita-Nasaud,Botosani,Braila,Brasov,Bucuresti,Buzau,Calarasi,Caras-Severin,Cluj,Constanta,Covasna,Dambovita,Dolj,Galati,Giurgiu,Gorj,Harghita,Hunedoara,Ialomita,Iasi,Ilfov,Maramures,Mehedinti,Mures,Neamt,Olt,Prahova,Salaj,Satu Mare,Sibiu,Suceava,Teleorman,Timis,Tulcea,Valcea,Vaslui,Vrancea";
states[176] = "Алтайский край,Амурская область,Архангельская область,Астраханская область,Белгородская область,Брянская область,Владимирская область,Волгоградская область,Вологодская область,Воронежская область,Еврейская автономная область,Забайкальский край,Ивановская область,Иркутская область,Кабардино-Балкарская Республика,Калининградская область,Калужская область,Камчатский край,Карачаево-Черкесская республика,Кемеровская область,Кировская область,Костромская область,Краснодарский край,Красноярский край,Курганская область,Курская область,Ленинградская область,Липецкая область,Магаданская область,Москва,Московская область,Мурманская область,Ненецкий автономный округ,Нижегородская область,Новгородская область,Новосибирская область,Омская область,Оренбургская область,Орловская область,Пензенская область,Пермский край,Приморский край,Псковская область,Республика Адыгея,Республика Алтай,Республика Башкортостан,Республика Бурятия,Республика Дагестан,Республика Ингушетия,Республика Калмыкия,Республика Карелия,Республика Коми,Республика Марий Эл,Республика Мордовия,Республика Саха Якутия,Республика Северная Осетия-Алания,Республика Татарстан,Республика Тыва,Республика Хакасия,Ростовская область,Рязанская область,Самарская область,Санкт-Петербург,Саратовская область,Сахалинская область,Свердловская область,Смоленская область,Ставропольский край,Тамбовская область,Тверская область,Томская область,Тульская область,Тюменская область,Удмуртская Республика,Ульяновская область,Хабаровский край,Ханты-Мансийский автономный округ - Югра,Челябинская область,Чеченская республика,Чувашская Республика,Чукотский автономный округ,Ямало-Ненецкий автономный округ,Ярославская область";
states[177] = "Butare,Byumba,Cyangugu,East,Gikongoro,Gisenyi,Gitarama,Kibungo,Kibuye,Kigali-Rural Kigali y' Icyaro,Kigali-Ville Kigali Ngari,Mutara,North,Ruhengeri,South,Ville de Kigali,West";
states[179] = "Anse la Raye,Castries,Choiseul,Dauphin,Dennery,Gros Islet,Laborie,Micoud,Praslin,Soufriere,Vieux Fort";
states[180] = "Charlotte,Grenadines,Saint Andrew,Saint David,Saint George,Saint Patrick";
states[181] = "A'ana,Aiga-i-le-Tai,Atua,Fa'aaaleleaga,Gaga'emauga,Gagaifomauga,Palauli,Satupa'itea,Tuamasaga,Va'a-o-Fonoti,Vaisigano";
states[182] = "Acquaviva,Borgo Maggiore,Chiesanuova,Domagnano,Faetano,Fiorentino,Montegiardino,San Marino,Serravalle";
states[183] = "Principe,Sao Tome";
states[184] = "Al Batah,Al Jawf,Al Madinah,Al Qasim,Ash Sharqiyah,Asir,Ha'il,Jlzan,Makkah,Najran,Riyadh,Tabuk,The Northern Border";
states[185] = "Dakar,Diourbel,Fatick,Kaolack,Kolda,Louga,Matam,Saint-Louis,Tambacounda,Thies,Ziguinchor";
states[186] = "Anse aux Pins,Anse Boileau,Anse Etoile,Anse Louis,Anse Royale,Baie Lazare,Baie Sainte Anne,Beau Vallon,Bel Air,Bel Ombre,Cascade,Glacis,Grand' Anse (Mahe),Grand' Anse (Praslin),La Digue,La Riviere Anglaise,Mont Buxton,Mont Fleuri,Plaisance,Pointe La Rue,Port Glaud,Saint Louis,Takamaka";
states[187] = "Eastern,Northern,Southern,Western Area";
states[188] = "Central Singapore,North East,North West,South East,South West";
states[189] = "Banska Bystrica,Bratislava,Kosice,Nitra,Presov,Trencin,Trnava,Zilina";
states[190] = "Ajdovscina,Beltinci,Benedikt,Bistrica ob Sotli,Bled,Bloke,Bohinj,Borovnica,Bovec,Braslovce,Brda,Brezica,Brezovica,Cankova,Celje,Cerklje na Gorenjskem,Cerknica,Cerkno,Cerkvenjak,Crensovci,Crna na Koroskem,Crnomelj,Destrnik,Divaca,Dobje,Dobrepolje,Dobrna,Dobrova-Polhov Gradec,Dobrovnik,Dol pri Ljubljani,Dolenjske Toplice,Domzale,Dornava,Dravograd,Duplek,Gorenja vas-Poljane,Gornja Radgona,Gornji Grad,Gornji Petrovci,Gorsnica,Grad,Grosuplje,Hajdina,Hoce-Slivnica,Hodos,Hrastnik,Hrpelje-Kozina,Idrija,Ig,IIrska Bistrica,Ivancna Gorica,Izola,Jesenice,Jezersko,Jorjul,Jovevje,Jursinci,Kamnik,Kanal,Kidricevo,Kobarid,Kobilje,Komen,Komenda,Koper,Kostel,Kozje,Kranj,Kranjska Gora,Krizevci,Krsko,Kungota,Kuzma,Lasko,Lenart,Lendava,Litija,Ljubljana,Ljubno,Ljutomer,Logatec,Loska dolina,Loski Potok,Lovrenc na Pohorju,Luce,Lukovica,Majsperk,Maribor,Markovci,Medvode,Menges,Metlika,Mezica,Miklavz na Dravskern polju,Miren-Kostanjevica,Mirna Pec,Mislinja,Moravce,Moravske Toplice,Mozirje,Murska Sobota,Muta,Naklo,Nazarje,Nova Gorica,Nova mesto,Odranci,Oplotnica,Ormoz,Osilnica,Pesnica,Piran,Pivka,Podcetrtek,Podlehnik,Podvelka,Polzela,Postojna,Prebold,Preddvor,Prevalje,Ptuj,Puconci,Race-Fram,Radece,Radenci,Radlje ob Dravi,Radovljica,Ravne na Koroskem,Razkri?je,Ribnica,Ribnica na Pohorju,Rogaska Slatina,Rogasovci,Rogatec,Ruse,Salovci,Selnica ob Dravi,Semic,Sempeter-Vrtojba,Sencur,Sentilj,Sentjernej,Sentjur pri Celju,Sevnica,Se?ana,Skocjan,Skofja Loka,Skoftjica,Slovenj Gradec,Slovenska Bistrica,Slovenske Konjice,Smarje pri Jelsah,Smartno ob Paki,Smartno pri Litiji,Sodra?ica,Solcava,Sostanj,Starse,Store,Sveta Ana,Sveti Andraz v Slovenskih goricah,Sveti Jurij,Tabor,Tisina,Tolmin,Trbovje,Trebnje,Trnovska vas,Trzic,Trzin,Turnisce,Velenje,Velika Lasce,Velika Polana,Verzej,Videm,Vipava,Vitanje,Vojnik,Vransko,Vrhnika,Vuzenica,Zagorje ob Savi,Zalec,Zavrc,Zelezniki,Zetale,Ziri,Zirovnica,Zrece,Zuzemberk";
states[191] = "Capital Territory (Honiara),Central,Choiseul,Guadalcanal,Isabel,Makira,Malaita,Rennell and Bellona,Temotu,Western";
states[192] = "Awdal,Bakool,Banaadir,Bari,Bay,Galguduud,Gedo,Hiirsan,Jubbada Dhexe,Jubbada Hoose,Mudug,Nugaal,Saneag,Shabeellaha Dhexe,Shabeellaha Hoose,Sool,Togdheer,Woqooyi Galbeed";
states[193] = "Eastern Cape,Free State,Gauteng,Kwazulu-Natal,Limpopo,Mpumalanga,North-West,Northern Cape,Western Cape";
states[195] = "Alava,Alava,Albacete,Alicante,Almeria,Almeria,Andalucia,Aragon,Asturias,Avila,Avila,Badajoz,Baleares,Barcelona,Basque Country,Burgos,Caceres,Caceres,Cadiz,Cadiz,Canary Islands,Cantabria,Cantabria,Castellon,Castellon,Castilla y Leon,Castilla-La Mancha,Catalonia,Ceuta,Ciudad Real,Cordoba,Cuenca,Extremadura,Galicia,Girona,Granada,Guadalajara,Guipuzcoa,Guipuzcoa,Huelva,Huesca,Jaen,Jaen,La Coruna,La Coruna,La Rioja,Las Palmas,Leon,Leon,Lleida,Lugo,Madrid,Malaga,Malaga,Melilla,Murcia,Navarra,Ourense,Palencia,Pontevedra,Salamanca,Santa Cruz de Tenerife,Segovia,Sevilla,Soria,Tarragona,Tenerife,Teruel,Toledo,Valencia,Valladolid,Vizcaya,Zamora,Zaragoza";
states[196] = "Central,Eastern,North Central,North Western,Northern,Sabaragamuwa,Southern,Uva,Western";
states[197] = "Ascension,Saint Helena,Tristan da Cunha";
states[198] = "Miquelon-Longlade,Saint-Pierre";
states[199] = "Al Jazirah,Al Qadarif,Blue Nile,Kassala State,Khartoum State,North Darfur,North Kurdufan,Northern,Red Sea,River Nile,Sennar,South Darfur,South Kurdufan,West Darfur,Western Kurdufan,White Nile";
states[200] = "Brokopondo,Commewijne,Coronie,Marowijne,Nickerie,Para,Paramaribo,Saramacca,Sipaliwini,Wanica";
states[202] = "Hhohho,Lubombo,Manzini,Shiselweni";
states[203] = "Blekinge lan,Dalarnas lan,Gavleborge lan,Gotlands lan,Hallands lan,Jamtlande lan,Jonkopings lan,Kalmar lan,Kronoberge lan,Norrbottena lan,Orebro lan,Ostergotlands lan,Skane lan,Sodermanlands lan,Stockholms lan,Uppsala lan,Varmlanda lan,Vasterbottens lan,Vasternorrlands lan,Vastmanlanda lan,Vastra Gotalands lan";
states[204] = "Aargau,Appenzell Ausserrhoden,Appenzell Innerrhoden,Basel-City,Basel-Country,Berne,Fribourg,Geneva,Glarus,Grisons,Jura,Jura,Lucerne,Neuchatel,Nidwalden,Obwalden,Schaffhausen,Schwyz,Solothurn,St. Gallen,Thurgau,Ticino,Uri,Valais,Vaud,Zug,Zurich";
states[205] = "Al-Hasakah Governorate,Aleppo Governorate,Ar-Raqqah Governorate,As-Suwayda Governorate,Damascus Governorate,Daraa Governorate,Deir ez-Zor Governorate,Hama Governorate,Homs Governorate,Idlib Governorate,Latakia Governorate,Quneitra Governorate,Rif Dimashq Governorate,Tartus Governorate";
states[206] = "Changhua County,Chiayi (city),Chiayi County,Hsinchu (city),Hsinchu County,Hualien County,Ilan County,Kaohsiung (municipality),Kaohsiung County,Keelung (city),Kinmen County,Lienchiang County,Miaoli County,Nantou County,Penghu County,Pingtung County,Taichung (city),Taichung County,Tainan City,Tainan County,Taipei (municipality),Taipei County,Taitung County,Taoyuan County,Yunlin County";
states[207] = "Gorno-Badakhshan Autonomous,Khatlon,Region of Republican Subordination,Sughd";
states[208] = "Arusha,Dar-es-Salaam,Dodoma,Iringa,Kagera,Kaskazini Pemba,Kaskazini Unguja,Kilimanjaro,Kusini Unguja,Lindi,Manyara,Mara,Mbeya,Mjini Magharibi,Morogoro,Mtwara,Mwanza,Pwani,Rukwa,Rusini Pemba,Ruvuma,Shinyanga,Singida,Tabora,Tanga,Xigoma";
states[209] = "Amnat Charoen,Ang Thong,Bangkok,Buri Ram,Chachoengsao,Chai Nat,Chaiyaphum,Chanthaburi,Chiang Mai,Chiang Rai,Chon Buri,Chumphon,Kalasin,Kamphaeng Phet,Kanchanaburi,Khon Kaen,Krabi,Lampang,Lamphun,Loei,Lop Buri,Mae Hong Son,Maha Sarakham,Mukdahan,Nakhon Nayok,Nakhon Pathom,Nakhon Phanom,Nakhon Ratchasima,Nakhon Sawan,Nakhon Si Thammarat,Nan,Narathiwat,Nong Bua Lam Phu,Nong Khai,Nonthaburi,Pathum Thani,Pattani,Pattaya,Phang Nga,Phattalung,Phayao,Phetchabun,Phetchaburi,Phichit,Phitsanulok,Phra Nakhon Si Ayutthaya,Phrae,Phuket,Prachin Buri,Prachuap Khiri Khan,Ranong,Ratchaburi,Rayong,Roi Et,Sa Kaeo,Sakon Nakhon,Samut Prakan,Samut Sakhon,Samut Songkhram,Saraburi,Satun,Si Sa Ket,Sing Buri,Songkhla,Sukhothai,Suphan Buri,Surat Thani,Surin,Tak,Trang,Trat,Ubon Ratchathani,Udon Thani,Uthai Thani,Uttaradit,Yala,Yasothon";
states[210] = "Centrale Region,Kara Region,Maritime Region,Plateaux Region,Savanes Region";
states[212] = "Eua,Ha'apai,Niuas,Tongatapu,Vava'";
states[213] = "Arima,Chaguanas,Couva-Tabaquite-Talparo,Diego Martin,Eastern Tobago,Penal-Debe,Point Fortin,Port of Spain,Princes Town,Rio Claro-Mayaro,San Fernando,San Juan-Laventille,Sangre Grande,Siparia,Tunapuna-Piarco,Western Tobago";
states[214] = "Ariana Governorate,Beja Governorate,Ben Arous Governorate,Bizerte Governorate,Gabes Governorate,Gafsa Governorate,Jendouba Governorate,Kairouan Governorate,Kasserine Governorate,Kebili Governorate,Kef Governorate,Mahdia Governorate,Manouba Governorate,Medenine Governorate,Monastir Governorate,Nabeul Governorate,Sfax Governorate,Sidi Bou Zid Governorate,Siliana Governorate,Sousse Governorate,Tataouine Governorate,Tozeur Governorate,Tunis Governorate,Zaghouan Governorate";
states[215] = "Adana,Adiyaman,Afyonkarahisar,Agrg,Aksaray,Amasya,Ankara,Antalya,Ardahan,Artvin,Aydin,Balikesir,Bartin,Batman,Bayburt,Bilecik,Bingol,Bitlis,Bolu,Burdur,Bursa,Canakkale,Cankiri,Corum,Denizli,Diyarbakir,Duzce,Edirne,Elazig,Erzincan,Erzurum,Eskisehir,Gaziantep,Giresun,Gumushane,Hakkari,Hatay,Icel,Igdir,Isparta,Istanbul,Izmir,Kahramanmaras,Karabuk,Karaman,Kars,Kastamonu,Kayseri,Kilis,Kirikkale,Kirklareli,Kirsehir,Kocaeli,Konya,Kutahya,Malatya,Manisa,Mardin,Mugla,Mus,Nevsehir,Nigde,Ordu,Osmaniye,Rize,Sakarya,Samsun,Sanliurfa,Siirt,Sinop,Sirnak,Sivas,Tekirdag,Tokat,Trabzon,Tunceli,Usak,Van,Yalova,Yozgat,Zonguldak";
states[216] = "Ahal,Balkan,Dasoguz,Lebap,Mary";
states[217] = "Ambergris Cays,Dellis Cay,East Caicos,French Cay,Grand Turk,Little Water Cay,Middle Caicos,North Caicos,Parrot Cay,Pine Cay,Providenciales,Salt Cay,South Caicos,West Caicos";
states[218] = "Funafuti,Nanumanga,Nanumea,Niutao,Nui,Nukufetau,Nukulaelae,Vaitupu";
states[219] = "Adjumani,Apac,Arua,Bugiri,Bundibugyo,Bushenyi,Busia,Gulu,Hoima,Iganga,Jinja,Kabale,Kabarole,Kaberamaido,Kalangala,Kampala,Kamuli,Kamwenge,Kanungu,Kapchorwa,Kasese,Katakwi,Kayunga,Kibaale,Kiboga,Kisoro,Kitgum,Kotido,Kumi,Kyenjojo,Lira,Luwero,Masaka,Masindi,Mayuge,Mbale,Mbarara,Moroto,Moyo,Mpigi,Mubende,Mukono,Nakapiripirit,Nakasongola,Nebbi,Ntungamo,Pader,Pallisa,Rakai,Rukungiri,Sembabule,Sironko,Soroti,Tororo,Wakiso,Yumbe";
states[221] = "Abu Dhabi Emirate,Ajman Emirate,Dubai Emirate,Fujairah Emirate,Ras al-Khaimah Emirate,Sharjah Emirate,Umm al-Quwain Emirate";
states[222] = "Aberdeenshire,Anglesey,Angus,Argyll,Ayrshire,Banffshire,Bedfordshire,Berwickshire,Breconshire,Buckinghamshire,Bute,Caernarvonshire,Caithness,Cambridgeshire,Cardiganshire,Carmarthenshire,Cheshire,Clackmannanshire,Cornwall and Isles of Scilly,Cumbria,Denbighshire,Derbyshire,Devon,Dorset,Dumbartonshire,Dumfriesshire,Durham,East Lothian,East Sussex,England,Essex,Fife,Flintshire,Glamorgan,Gloucestershire,Greater London,Greater Manchester,Hampshire,Hertfordshire,Inverness,Kent,Kincardineshire,Kinross-shire,Kirkcudbrightshire,Lanarkshire,Lancashire,Leicestershire,Lincolnshire,London,Merionethshire,Merseyside,Midlothian,Monmouthshire,Montgomeryshire,Moray,Nairnshire,Norfolk,North Yorkshire,Northamptonshire,Northern Ireland,Northumberland,Nottinghamshire,Orkney,Oxfordshire,Peebleshire,Pembrokeshire,Perthshire,Radnorshire,Renfrewshire,Ross & Cromarty,Roxburghshire,Scotland,Selkirkshire,Shetland,Shropshire,Somerset,South Yorkshire,Staffordshire,Stirlingshire,Suffolk,Surrey,Sutherland,Tyne and Wear,Wales,Warwickshire,West Lothian,West Midlands,West Sussex,West Yorkshire,Wigtownshire,Wiltshire,Worcestershire";
states[223] = "Alabama,Alaska,American Samoa,Arizona,Arkansas,Armed Forces Africa,Armed Forces Americas,Armed Forces Canada,Armed Forces Europe,Armed Forces Middle East,Armed Forces Pacific,California,Colorado,Connecticut,Delaware,District of Columbia,Federated States Of Micronesia,Florida,Georgia,Guam,Hawaii,Idaho,Illinois,Indiana,Iowa,Kansas,Kentucky,Louisiana,Maine,Marshall Islands,Maryland,Massachusetts,Michigan,Minnesota,Mississippi,Missouri,Montana,Nebraska,Nevada,New Hampshire,New Jersey,New Mexico,New York,North Carolina,North Dakota,Northern Mariana Islands,Ohio,Oklahoma,Oregon,Palau,Pennsylvania,Puerto Rico,Rhode Island,South Carolina,South Dakota,Tennessee,Texas,Utah,Vermont,Virgin Islands,Virginia,Washington,West Virginia,Wisconsin,Wyoming";
states[224] = "Baker Island,Howland Island,Jarvis Island,Johnston Atoll,Kingman Reef,Midway Islands,Navassa Island,Palmyra Atoll,Wake Island";
states[225] = "Artigas,Canelones,Cerro Largo,Colonia,Durazno,Flores,Florida,Lavalleja,Maldonado,Montevideo,Paysandu,Rio Negro,Rivera,Rocha,Salto,San Jose,Soriano,Tacuarembo,Treinta y Tres";
states[226] = "Andijon,Buxoro,Farg'ona,Jizzax,Khorazm,Namangan,Navoiy,Qashqadaryo,Qoraqalpogiston Respublikasi,Samarqand,Sirdaryo,Surxondaryo,Toshkent,Xorazm";
states[227] = "Malampa,Penama,Sanma,Shefa,Tafea,Torba";
states[229] = "Amazonas,Anzoategui,Apure,Aragua,Barinas,Bolivar,Carabobo,Cojedes,Delta Amacuro,Dependencias Federales,Distrito Federal,Falcon,Guarico,Lara,Merida,Miranda,Monagas,Nueva Esparta,Portuguesa,Sucre,Tachira,Trujillo,Vargas,Yaracuy,Zulia";
states[230] = "An Giang,Ba Ria - Vung Tau,Bac Can,Bac Giang,Bac Lieu,Bac Ninh,Ben Tre,Binh Dinh,Binh Duong,Binh Phuoc,Binh Thuan,Ca Mau,Can Tho,Cao Bang,Da Nang, thanh pho,Dac Lac,Dong Nai,Dong Thap,Gia Lai,Ha Giang,Ha Nam,Ha Noi, thu do,Ha Tay,Ha Tinh,Hai Duong,Hai Phong, thanh pho,Ho Chi Minh, thanh pho,Hoa Binh,Hung Yen,Khanh Hoa,Kien Giang,Kon Tum,Lai Chau,Lam Dong,Lang Son,Lao Cai,Long An,Nam Dinh,Nghe An,Ninh Binh,Ninh Thuan,Phu Tho,Phu Yen,Quang Binh,Quang Nam,Quang Ngai,Quang Ninh,Quang Tri,Soc Trang,Son La,Tay Ninh,Thai Binh,Thai Nguyen,Thanh Hoa,Thua Thien-Hue,Tien Giang,Tra Vinh,Tuyen Quang,Vinh Long,Vinh Phuc,Yen Bai";
states[235] = "Abyan Governorate,Ad Dali Governorate,Adan Governorate,Al Bayda' Governorate,Al Hudaydah Governorate,Al Hydaydah Governorate,Al Jawf Governorate,Al Mahrah Governorate,Al Mahwit Governorate,Amran Governorate,Dhamar Governorate,Hadramawt Governorate,Hajjah Governorate,Ibb Governorate,Lahij Governorate,Ma'rib Governorate,Sa'dah Governorate,San'a' Governorate,Shabwah Governorate,Ta'izz Governorate";
states[238] = "Central,Copperbelt,Eastern,Luapula,Lusaka,North-Western,Northern,Southern,Western";
states[239] = "Bulawayo,Harare,Manicaland,Mashonaland Central,Mashonaland East,Mashonaland West,Masvingo,Matabeleland North,Matabeleland South,Midlands";
states[242] = "Badakhshan,Badghis,Baghlan,Balkh,Bamiyan,Daykundi,Farah,Faryab,Ghazni,Ghor,Helmand,Herat,Jowzjan,Kabul,Kandahar,Kapisa,Khost,Kunar,Kunduz,Laghman,Logar,Nangarhar,Nimruz,Nurestan,Oruzgan,Paktia,Paktika,Panjshir,Parwan,Samangan,Sar-e Pol,Takhar,Wardak,Zabul";
states[243] = "Bandundu,Bas-Congo,Equateur,Haut-Congo,Kasai-Occidental,Kasai-Oriental,Katanga,Kinshasa,Maniema,Nord-Kivu,Orientale,Sud-Kivu";
states[244] = "Andrijevica Municipality,Bar Municipality,Berane Municipality,Bijelo Polje Municipality,Budva Municipality,Cetinje Municipality,Danilovgrad Municipality,Herceg-Novi Municipality,Kolasin Municipality,Kotor Municipality,Mojkovac Municipality,Niksic Municipality,Plav Municipality,Pljevlja Municipality,Pluzine Municipality,Podgorica Municipality,Rozaje Municipality,Savnik Municipality,Tivat Municipality,Ulcinj Municipality,Zabljak Municipality";
states[245] = "Beograd (city),Bor,Branicevo,Central Banat,Jablanica,Kolubara,Kosovo,Kosovo-Pomoravlje,Kosovska Mitrovica,Macva,Moravica,Nisava,North Banat,Northern Backa,Pcinja,Pec,Pirot,Podunavlje,Pomoravlje,Prizren,Rasina,Raska,South Backa,South Banat,Srem,Sumadija,Toplica,West Backa,Zajecar,Zlatibor";
states[246] = "Central Equatoria,Eastern Equatoria,Jonglei,Lakes,Northern Bahr el Ghazal,Unity,Upper Nile,Warrap,Western Bahr el Ghazal,Western Equatoria";
states[247] = "Aileu,Ainaro,Baucau,Bobonaro,Cova Lima,Dili,Ermera,Lautem,Liquica,Manafahi,Manatuto,Oecussi,Viqueque";

jQuery(document).ready(function() {
	iform_country();
	iform_state();
});

function iform_country() {
	var cval = jQuery('form.wp2print-info-form .wif-country').val();
	var sshtml = '<option value="">-- <?php _e('Select State', 'wp2print'); ?> --</option>';
	if (states[cval]) {
		var cstates = states[cval].split(',');
		for (var i=0; i<cstates.length; i++) {
			sshtml += "\n" + '<option value="'+cstates[i]+'">'+cstates[i]+'</option>';
		}
		jQuery('form.wp2print-info-form .wif-state-text').val('').hide();
		jQuery('form.wp2print-info-form .wif-state').show();
	} else {
		jQuery('form.wp2print-info-form .wif-state').hide();
		jQuery('form.wp2print-info-form .wif-state-text').show();
	}
	jQuery('form.wp2print-info-form .wif-state').html(sshtml);
}
function iform_state() {
	var sselected = jQuery('form.wp2print-info-form .wif-state').attr('data-selected');
	if (jQuery('form.wp2print-info-form .wif-state').is(':visible') && sselected != '') {
		jQuery('form.wp2print-info-form .wif-state option').each(function(){
			if (jQuery(this).html() == sselected) {
				jQuery(this).attr('selected', 'selected');
			}
		});
	}
}
var uploaded = false;
function iform_submit() {
	var ferror = '';
	var project_name = iform_trim(jQuery('form.wp2print-info-form .wif-project-name').val());
	var first_name = iform_trim(jQuery('form.wp2print-info-form .wif-first-name').val());
	var last_name = iform_trim(jQuery('form.wp2print-info-form .wif-last-name').val());
	var email = iform_trim(jQuery('form.wp2print-info-form .wif-email').val());
	var phone = iform_trim(jQuery('form.wp2print-info-form .wif-phone').val());
	var address = iform_trim(jQuery('form.wp2print-info-form .wif-address').val());
	var city = iform_trim(jQuery('form.wp2print-info-form .wif-city').val());
	var country = iform_trim(jQuery('form.wp2print-info-form .wif-country').val());
	var zip = iform_trim(jQuery('form.wp2print-info-form .wif-zip').val());

	if (project_name == '') {
		ferror += "<?php _e('Project Name is required field.', 'wp2print'); ?>" + "\n";
	}
	if (first_name == '') {
		ferror += "<?php _e('First Name is required field.', 'wp2print'); ?>" + "\n";
	}
	if (last_name == '') {
		ferror += "<?php _e('Last Name is required field.', 'wp2print'); ?>" + "\n";
	}
	if (email == '') {
		ferror += "<?php _e('Email is required field.', 'wp2print'); ?>" + "\n";
	} else if (!/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(email)) {
		ferror += "<?php _e('Email is incorrect.', 'wp2print'); ?>" + "\n";
	}
	if (phone == '') {
		ferror += "<?php _e('Telephone is required field.', 'wp2print'); ?>" + "\n";
	}
	if (address == '') {
		ferror += "<?php _e('Address is required field.', 'wp2print'); ?>" + "\n";
	}
	if (city == '') {
		ferror += "<?php _e('City is required field.', 'wp2print'); ?>" + "\n";
	}
	if (country == '') {
		ferror += "<?php _e('Country is required field.', 'wp2print'); ?>" + "\n";
	}
	if (jQuery('form.wp2print-info-form .state-row').size()) {
		var state = jQuery('form.wp2print-info-form .wif-state').val();
		var state_text = iform_trim(jQuery('form.wp2print-info-form .wif-state-text').val());
		if (state == '' && state_text == '') {
			ferror += "<?php echo sprintf(__('%s is required field.', 'wp2print'), $print_products_info_form_options['state_field_label']); ?>" + "\n";
		}
	}
	if (zip == '') {
		ferror += "<?php echo sprintf(__('%s is required field.', 'wp2print'), $print_products_info_form_options['zip_field_label']); ?>" + "\n";
	}

	if (ferror == '') {
		if (jQuery('#filelist span').size() && !uploaded) {
			jQuery('#uploadfiles').trigger('click');
			return false;
		} else {
			return true;
		}
	} else {
		alert(ferror);
		return false;
	}
}

function iform_trim(str) {
	if (str != 'undefined') {
		return str.replace(/^\s+|\s+$/g,"");
	} else {
		return '';
	}
}

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
				jQuery('form.wp2print-info-form').submit();
			},
			Error: function(up, err) {
				alert("<?php _e('Upload error', 'wp2print'); ?>: "+err.message); // err.code
			}
		}
	});
	uploader.init();
});
</script>
<?php } ?>