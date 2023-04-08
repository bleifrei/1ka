<?php

/*
 *  Class, ki skrbi za vse v zvezi z GDPR uredbo
 *
 */



class GDPR{


	// GDPR avtoritete po drzavah
	public static $authorities = array(
		array('country'=>'Austria', 'drzava'=>'Avstrija', 'name'=>'Andrea Jelinek', 'title'=>'Director, Austrian Data Protection Authority', 'email'=>'dsb@dsb.gv.at', 'phone'=>'+43 1 531 15 202525', 'fax'=>'+43 1 531 15 202690'),
		array('country'=>'Austria', 'drzava'=>'Avstrija', 'name'=>'Dietmar Wagner', 'title'=>'Compliance-Officer of the FMA', 'email'=>'compliance@fma.gv.at', 'phone'=>'+43 1 249 59-6112', 'fax'=>''),
		array('country'=>'Belgium', 'drzava'=>'Belgija', 'name'=>'', 'title'=>'', 'email'=>'commission@privacycommission.be', 'phone'=>'+32 2 274 48 00', 'fax'=>'+32 2 274 48 10'),
		array('country'=>'Bulgaria', 'drzava'=>'Bolgarija', 'name'=>'Ventsislav Karadjov', 'title'=>'Chairman of the Commission for Personal Data Protection', 'email'=>'kzld@cpdp.bg', 'phone'=>'+359 2 915 3523', 'fax'=>'+359 2 915 3525'),
		array('country'=>'Croatia', 'drzava'=>'Hrvaška', 'name'=>'Anto RAJKOVAČA', 'title'=>'Director of the Croatian Data Protection Agency', 'email'=>'azop@azop.hr', 'phone'=>'+385 1 4609 000', 'fax'=>'+385 1 4609 099'),
		array('country'=>'Cyprus', 'drzava'=>'Ciper', 'name'=>'Irene LOIZIDOU NIKOLAIDOU', 'title'=>'', 'email'=>'commissioner@dataprotection.gov.cy', 'phone'=>'+357 22 818 456', 'fax'=>'+357 22 304 565'),
		array('country'=>'Czech Republic', 'drzava'=>'Češka republika', 'name'=>'Ivana JANŮ', 'title'=>'President of the Office for Personal Data Protection', 'email'=>'posta@uoou.cz', 'phone'=>'+420 234 665 111', 'fax'=>'+420 234 665 444'),
		array('country'=>'Denmark', 'drzava'=>'Danska', 'name'=>'Cristina Angela GULISANO', 'title'=>'Director, Danish Data Protection Agency', 'email'=>'dt@datatilsynet.dk', 'phone'=>'+45 33 1932 00', 'fax'=>'+45 33 19 32 18'),
		array('country'=>'Estonia', 'drzava'=>'Estonija', 'name'=>'Viljar PEEP', 'title'=>'Director General, Estonian Data Protection Inspectorate', 'email'=>'info@aki.ee', 'phone'=>'+372 6274 135', 'fax'=>'+372 6274 137'),
		array('country'=>'Finland', 'drzava'=>'Finska', 'name'=>'Reijo AARNIO', 'title'=>'Ombudsman of the Finnish Data Protection Authority', 'email'=>'tietosuoja@om.fi', 'phone'=>'+358 10 3666 700', 'fax'=>'+358 10 3666 735'),
		array('country'=>'France', 'drzava'=>'Francija', 'name'=>'Isabelle FALQUE-PIERROTIN', 'title'=>'President of CNIL', 'email'=>'', 'phone'=>'01 47 22 43 34', 'fax'=>'01 47 38 72 43'),
		array('country'=>'Germany', 'drzava'=>'Nemčija', 'name'=>'Andrea VOSSHOFF', 'title'=>'Federal Commissioner for Freedom of Information', 'email'=>'poststelle@bfdi.bund.de', 'phone'=>'+49 228 997799 0', 'fax'=>'+49 228 997799 550'),
		array('country'=>'Greece', 'drzava'=>'Grčija', 'name'=>'Petros CHRISTOFOROS', 'title'=>'President of the Hellenic Data Protection Authority', 'email'=>'contact@dpa.gr', 'phone'=>'+30 210 6475 600', 'fax'=>'+30 210 6475 628'),
		array('country'=>'Hungary', 'drzava'=>'Madžarska', 'name'=>'Attila PÉTERFALVI', 'title'=>'President of the National Authority for Data Protection and Freedom of Information', 'email'=>'peterfalvi.attila@naih.hu', 'phone'=>'+36 1 3911 400', 'fax'=>''),
		array('country'=>'Ireland', 'drzava'=>'Irska', 'name'=>'Helen DIXON', 'title'=>'Data Protection Commissioner', 'email'=>'info@dataprotection.ie', 'phone'=>'+353 57 868 4800', 'fax'=>'+353 57 868 4757'),
		array('country'=>'Italy', 'drzava'=>'Italija', 'name'=>'Antonello SORO', 'title'=>'President of Garante per la protezione dei dati personali', 'email'=>'garante@garanteprivacy.it', 'phone'=>'+39 06 69677 1', 'fax'=>'+39 06 69677 785'),
		array('country'=>'Latvia', 'drzava'=>'Latvija', 'name'=>'Signe PLUMINA', 'title'=>'Director of Data State Inspectorate', 'email'=>'info@dvi.gov.lv', 'phone'=>'+371 6722 3131', 'fax'=>'+371 6722 3556'),
		array('country'=>'Lithuania', 'drzava'=>'Litva', 'name'=>'Algirdas KUNČINAS', 'title'=>'Director of the State Data Protection Inspectorate', 'email'=>'ada@ada.lt', 'phone'=>'+370 5 279 14 45', 'fax'=>'+370 5 261 94 94'),
		array('country'=>'Luxembourg', 'drzava'=>'Luksemburg', 'name'=>'Tine A. LARSEN', 'title'=>'President of the Commission Nationale pour la Protection des Données', 'email'=>'info@cnpd.lu', 'phone'=>'+352 2610 60 1', 'fax'=>'+352 2610 60 29'),
		array('country'=>'Malta', 'drzava'=>'Malta', 'name'=>'Saviour CACHIA', 'title'=>'Information and Data Protection Commissioner', 'email'=>'commissioner.dataprotection@gov.mt', 'phone'=>'+356 2328 7100', 'fax'=>'+356 2328 7198'),
		array('country'=>'Netherlands', 'drzava'=>'Nizozemska', 'name'=>'Aleid WOLFSEN', 'title'=>'Chairman of Autoriteit Persoonsgegevens', 'email'=>'info@autoriteitpersoonsgegevens.nl', 'phone'=>'+31 70 888 8500', 'fax'=>'+31 70 888 8501'),
		array('country'=>'Poland', 'drzava'=>'Poljska', 'name'=>'Edyta BIELAK-JOMAA', 'title'=>'Inspector General for the Protection of Personal Data', 'email'=>'kancelaria@giodo.gov.pl', 'phone'=>'+48 22 53 10 440', 'fax'=>'+48 22 53 10 441'),
		array('country'=>'Portugal', 'drzava'=>'Portugalska', 'name'=>'Filipa CALVÃO', 'title'=>'President, Comissão Nacional de Protecção de Dados', 'email'=>'geral@cnpd.pt', 'phone'=>'+351 21 392 84 00', 'fax'=>'+351 21 397 68 32'),
		array('country'=>'Romania', 'drzava'=>'Romunija', 'name'=>'Ancuţa Gianina OPRE', 'title'=>'President of the National Supervisory Authority for Personal Data Processing', 'email'=>'anspdcp@dataprotection.ro', 'phone'=>'+40 21 252 5599', 'fax'=>'+40 21 252 5757'),
		array('country'=>'Slovakia', 'drzava'=>'Slovaška', 'name'=>'Soňa PŐTHEOVÁ', 'title'=>'President of the Office for Personal Data Protection of the Slovak Republic', 'email'=>'statny.dozor@pdp.gov.sk', 'phone'=>'+ 421 2 32 31 32 14', 'fax'=>'+ 421 2 32 31 32 34'),
		array('country'=>'Slovenia', 'drzava'=>'Slovenija', 'name'=>'Mojca PRELESNIK', 'title'=>'Information Commissioner of the Republic of Slovenia', 'email'=>'gp.ip@ip-rs.si', 'phone'=>'+386 1 230 9730', 'fax'=>'+386 1 230 9778'),
		array('country'=>'Spain', 'drzava'=>'Španija', 'name'=>'María del Mar España Martí', 'title'=>'Director of the Spanish Data Protection Agency', 'email'=>'internacional@agpd.es', 'phone'=>'+34 91399 6200', 'fax'=>'+34 91455 5699'),
		array('country'=>'Sweden', 'drzava'=>'Švedska', 'name'=>'Kristina SVAHN STARRSJÖ', 'title'=>'Director General of the Data Inspection Board', 'email'=>'datainspektionen@datainspektionen.se', 'phone'=>'+46 8 657 6100', 'fax'=>'+46 8 652 8652'),
		array('country'=>'United Kingdom', 'drzava'=>'Velika Britanija', 'name'=>'Elizabeth DENHAM', 'title'=>'Information Commissioner', 'email'=>'international.team@ico.org.uk', 'phone'=>'+44 1625 545 745', 'fax'=>''),
	);


	function __construct(){
		global $site_url;

	}


	// Prikazemo vsebino zavihka gdpr - seznam anket
	public function displayGDPRSurveyList(){
		global $site_url;
		global $lang;

		$survey_list = array();
		$survey_list = $this->getUserSurveys();


		echo '<div style="font-style:italic; margin-top:-10px;">';

		echo '<p>'.$lang['srv_gdpr_survey_list_text'].'</p>';

		echo $lang['srv_gdpr_survey_list_text2'].'<ul style="margin-top:2px;">';
		echo '	<li>'.$lang['srv_gdpr_survey_list_li_1'].'</li>';
		echo '	<li>'.$lang['srv_gdpr_survey_list_li_2'].'</li>';
		echo '	<li>'.$lang['srv_gdpr_survey_list_li_3'].'</li>';
		echo '	<li>'.$lang['srv_gdpr_survey_list_li_4'].'</li>';
		echo '</ul>';

		echo '<p>'.$lang['srv_gdpr_survey_list_text3'].'</p>';

		echo '</div>';


		echo '<table class="gdpr_surveys">';

		echo '<tr>';
		echo '<th>'.$lang['srv_gdpr_survey_list_survey'].'</th>';
		echo '<th>'.$lang['srv_gdpr_survey_list_activity'].'</th>';
		echo '<th>'.$lang['srv_gdpr_survey_list_pot_gdpr'].'</th>';
		echo '<th>'.$lang['srv_gdpr_survey_list_gdpr'].'</th>';
		echo '</tr>';

		foreach($survey_list as $anketa){

			// Nastavimo barvo vrstice
			if($anketa['gdpr'] == 1)
				$color = ' class="green_row"';
			elseif($anketa['potential_gdpr'] == 1)
				$color = ' class="red_row"';
			else
				$color = '';

			echo '<tr '.$color.'>';

			echo '<td><a href="'.$site_url.'admin/survey/index.php?anketa='.$anketa['id'].'&a=gdpr_settings">'.$anketa['naslov'].'</a></td>';
			echo '<td>'.$anketa['active'].'</td>';
			echo '<td>'.$anketa['potential_gdpr'].'</td>';

			//echo '<td>'.$anketa['gdpr'].'</td>';
			echo '<td><input type="checkbox" value="1" class="pointer" onClick="setGDPRSurvey(\''.$anketa['id'].'\', this.checked); return false;" '.($anketa['gdpr'] == '1' ? ' checked="checked"' : '').'</td>';

			echo '</tr>';
		}

		echo '</table>';
	}

	// Prikazemo vsebino zavihka gdpr - nastavitve uporabnika
	public function displayGDPRUser($error=array()){
		global $site_url;
		global $lang;

		$user_settings = self::getUserSettings();
        
		echo '<form name="settingsgdpr" id="form_gdpr_user_settings" method="post">';

		echo '	<input name="submited" value="1" type="hidden">';

		echo '	<fieldset><legend>'.$lang['srv_gdpr_user_settings_title'].'</legend>';

        echo '<p class="italic">'.$lang['srv_gdpr_user_settings_desc1'].'<br />';
		echo $lang['srv_gdpr_user_settings_desc2'].'<br /><br />';
        echo $lang['srv_gdpr_user_settings_desc3'].'</p>';
        

        // PODATKI AVTORJA
        // Opozorilo za obvezna polja
        if($user_settings['firstname'] == '' || $user_settings['lastname'] == '' || $user_settings['email'] == '')
            echo '<p><span class="red bold">'.$lang['srv_gdpr_user_settings_err'].'</span></p>';
        else
            echo '<br />';
	
		echo '		<div class="setting '.($user_settings['firstname'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_firstname'].':</label></span> ';
		echo '		<input class="text" name="firstname" value="'.$user_settings['firstname'].'" type="text"></div>';

		echo '		<div class="setting '.($user_settings['lastname'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_lastname'].':</label></span> ';
		echo '		<input class="text" name="lastname" value="'.$user_settings['lastname'].'" type="text"></div>';

        $email = ($user_settings['email'] == '') ? User::getInstance()->primaryEmail() : $user_settings['email'];
		echo '		<div class="setting '.($user_settings['email'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_email'].':</label></span> ';
		echo '		<input class="text '.(isset($error['email']) ? ' red' : '').'" name="email" value="'.$email.'" type="text"> '.(isset($error['email']) ? '<span class="red italic">'.$lang['srv_remind_email_hard'].'</span>' : '').'</div>';

		echo '		<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_phone'].':</label></span> ';
		echo '		<input class="text" name="phone" value="'.$user_settings['phone'].'" type="text"></div>';

		echo '		<br />';
		
		// Naslov in drzava
		echo '		<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_address'].':</label></span> ';
		echo '		<input class="text" name="address" value="'.$user_settings['address'].'" type="text"></div>';

		echo '		<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_country'].':</label></span> ';

		echo '		<select name="country" onChange="editGDPRAuthority(this.value); return false;">';
		//echo '			<option value="" '.($user_settings['country'] == '' ? ' selected="selected"' : '').'>'.$lang['srv_gdpr_user_settings_country_select'].'</option>';
        $country_filter = array();
        foreach(self::$authorities as $authority){

			if (in_array($authority['country'], $country_filter)) {
		        continue;
		    }

			if($lang['id'] == '1')
				echo '			<option value="'.$authority['drzava'].'" '.(($user_settings['country'] == $authority['drzava'] || $user_settings['country'] == $authority['country']) ? ' selected="selected"' : '').'>'.$authority['drzava'].'</option>';
			else
				echo '			<option value="'.$authority['country'].'" '.(($user_settings['country'] == $authority['drzava'] || $user_settings['country'] == $authority['country']) ? ' selected="selected"' : '').'>'.$authority['country'].'</option>';

			$country_filter[] = $authority['country'];
		}
		echo '		</select>';
		echo '		</div>';
        
        
		echo '		<br />';
        
        
		// ORGANIZACIJA ALI ZASEBNIK
		echo '		<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_type'].':</label></span> ';
		echo '			<label for="type_0"><input class="radio" name="type" id="type_0" value="0" type="radio" '.($user_settings['type'] != '1' ? ' checked="checked"' : '').' onClick="toggleGDPRDPO();"> '.$lang['srv_gdpr_user_settings_type_0'].'</label>';
		echo '			<label for="type_1"><input class="radio" name="type" id="type_1" value="1" type="radio" '.($user_settings['type'] == '1' ? ' checked="checked"' : '').' onClick="toggleGDPRDPO();"> '.$lang['srv_gdpr_user_settings_type_1'].'</label>';
		echo '		</div>';

        
        // PODATKI PODJETJA
        echo '		<div id="gdpr_organization" '.($user_settings['type'] != '1' ? ' style="display:none;"' : '').'>';
        
        // Opozorilo za obvezna polja
        if($user_settings['organization'] == '' || $user_settings['organization_maticna'] == '')
            echo '<p><span class="red bold">'.$lang['srv_gdpr_user_settings_err2'].'</span></p>';

		echo '			<div class="setting '.($user_settings['organization'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_organization'].':</label></span> ';
		echo '			<input class="text" name="organization" value="'.$user_settings['organization'].'" type="text"></div>';
        
        echo '			<div class="setting '.($user_settings['organization_maticna'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_organization_maticna'].':</label></span> ';
        echo '			<input class="text" name="organization_maticna" value="'.$user_settings['organization_maticna'].'" type="text"></div>';
        
        /*echo '			<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_organization_davcna'].':</label></span> ';
		echo '			<input class="text" name="organization_davcna" value="'.$user_settings['organization_davcna'].'" type="text"></div>';*/
        
        echo '		</div>';


        // IMA DPO
        echo '		<div id="gdpr_has_dpo" '.($user_settings['type'] != '0' ? ' style="display:none;"' : '').'>';
        
        echo '		<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_has_dpo'].':</label></span> ';
		echo '			<label for="has_dpo_0"><input class="radio" name="has_dpo" id="has_dpo_0" value="0" type="radio" '.($user_settings['has_dpo'] != '1' ? ' checked="checked"' : '').' onClick="toggleGDPRHasDPO();"> '.$lang['no'].'</label>';
		echo '			<label for="has_dpo_1"><input class="radio" name="has_dpo" id="has_dpo_1" value="1" type="radio" '.($user_settings['has_dpo'] == '1' ? ' checked="checked"' : '').' onClick="toggleGDPRHasDPO();"> '.$lang['yes'].'</label>';
		echo '		</div>';
        
        echo '<br /><br />';

        echo '		</div>';


        // DPO
        echo '		<div id="gdpr_dpo" '.($user_settings['type'] != '1' && $user_settings['has_dpo'] != '1' ? ' style="display:none;"' : '').'>';
        
        echo '			<p class="bold">'.$lang['srv_gdpr_user_settings_dpo'].':</p>';
        
        // Opozorilo za obvezna polja
        if($user_settings['dpo_firstname'] == '' || $user_settings['dpo_lastname'] == '' || $user_settings['dpo_email'] == '')
            echo '<p><span class="red bold">'.$lang['srv_gdpr_user_settings_err'].'</span></p>';
        else
            echo '<br />';
		
		echo '			<div class="setting '.($user_settings['dpo_firstname'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_dpo_firstname'].':</label></span> ';
		echo '			<input class="text" name="dpo_firstname" value="'.$user_settings['dpo_firstname'].'" type="text"></div>';
		
		echo '			<div class="setting '.($user_settings['dpo_lastname'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_dpo_lastname'].':</label></span> ';
		echo '			<input class="text" name="dpo_lastname" value="'.$user_settings['dpo_lastname'].'" type="text"></div>';
		
		echo '			<div class="setting '.($user_settings['dpo_email'] == '' ? ' red' : '').'"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_dpo_email'].':</label></span> ';
		echo '			<input class="text '.(isset($error['dpo_email']) ? ' red' : '').'" name="dpo_email" value="'.$user_settings['dpo_email'].'" type="text"> '.(isset($error['dpo_email']) ? '<span class="red italic">'.$lang['srv_remind_email_hard'].'</span>' : '').'</div>';
		
		echo '			<div class="setting"><span class="nastavitveSpan2"><label>'.$lang['srv_gdpr_user_settings_dpo_phone'].':</label></span> ';
		echo '			<input class="text" name="dpo_phone" value="'.$user_settings['dpo_phone'].'" type="text"></div>';
		
		echo '		</div>';


		// Podatki trenutne avtoritete
		echo '<div id="gdpr_authority_info">';
		self::displayGDPRAuthority($user_settings['country']);
		echo '</div>';

		echo '	</fieldset>';


		// Gumb shrani
		echo '<div class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="editGDPRProfile(); return false;"><span>'.$lang['edit1337'].'</span></a></div>';

		// div za prikaz uspešnosti shranjevanja
		if ($_GET['s'] == '1') {
			echo '<span class="clr"></span>';
			echo '<div id="success_save"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}

		echo '</form>';
		echo '<span class="clr"></span>';
	}

	// Prikazemo GDPR avtoriteto za izbrano drzavo
	public function displayGDPRAuthority($country){
		global $lang;

		if($country != ''){

			$current_authorities = array();

			// Dobimo vse avtoritete za drzavo
			foreach(self::$authorities as $authority){
				// Ce je ta avtoriteta izbrana
				if($country == $authority['drzava'] || $country == $authority['country'])
					$current_authorities[] = $authority;
			}

			echo '<p class="bold">'.$lang['srv_gdpr_user_settings_authority'].':</p>';

			// Prikazemo podatke za vse avtoritete (lahko jih je vec na drzavo)
			foreach ($current_authorities as $authority) {
				echo '<div class="gdpr_authority_info_data">';

				if($authority['name'] != '')
					echo '<span class="bold">'.$authority['name'].'</span><br />';
				if($authority['title'] != '')
					echo '<span>'.$authority['title'].'</span><br />';
				if($authority['email'] != '')
					echo '<span class="spaceLeft">'.$lang['srv_gdpr_user_settings_email'].': '.$authority['email'].'</span><br />';
				if($authority['phone'] != '')
					echo '<span class="spaceLeft">'.$lang['srv_gdpr_user_settings_phone'].': '.$authority['phone'].'</span><br />';
				if($authority['fax'] != '')
					echo '<span class="spaceLeft">Fax: '.$authority['fax'].'</span><br />';

				echo '</div>';
			}
		}
	}

	// Prikazemo vsebino zavihka gdpr - zahteve za izbris
	public function displayGDPRRequests(){
		global $site_url;
		global $lang;

		echo '<div style="font-style:italic; margin-top:-10px;">';
		echo '<p>'.$lang['srv_gdpr_requests_desc'].'</p>';
		echo '</div>';

		// Seznam cakajocih zahtevkov
		$request_list = array();
		$request_list = $this->getUserRequests($ank_id=0, $status=0);
		
		// Seznam opravljenih zahtevkov
		$request_list_done = array();
		$request_list_done = $this->getUserRequests($ank_id=0, $status=1);

		if(count($request_list) > 0){
			echo '<table class="gdpr_surveys requests">';

			echo '<tr>';
			echo '<th>'.$lang['srv_gdpr_requests_survey'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_recnum'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_ip'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_url'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_email'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_date'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_text'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_type'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_date_sent'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_done'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_comment'].'</th>';
			echo '</tr>';

			foreach($request_list as $request_id => $request){

				echo '<tr '.($request['status'] == 0 ? ' class="red_row"' : '').'>';

				echo '<td><a href="'.$site_url.'admin/survey/index.php?anketa='.$request['ank_id'].'&a=data">'.$request['naslov'].'</a></td>';
				
				//echo '<td>'.$request['recnum'].'</td>';
				//echo '<td>'.$request['ip'].'</td>';
				echo '<td>'.$request['url'].'</td>';
				echo '<td>'.$request['email'].'</td>';	
				//echo '<td>'.$request['date'].'</td>';
				echo '<td>'.$request['text'].'</td>';
				
				echo '<td>'.$lang['srv_gdpr_requests_type_'.$request['type']].'</td>';
				
				echo '<td>'.date('j.n.Y', strtotime($request['datum'])).'</td>';
				
				// Checkbox ce je zahteva opravljena
				//echo '<td>'.($request['status'] == '1' ? $lang['srv_gdpr_requests_status_1'] : $lang['srv_gdpr_requests_status_0']).'</td>';
				echo '<td><input type="checkbox" value="1" class="pointer" onClick="setGDPRRequestStatus(\''.$request_id.'\', this.checked); return false;" '.($request['status'] == '1' ? ' checked="checked"' : '').'></td>';

				// Komentar avtorja
				echo '<td><textarea style="height:30px; width:200px;" onBlur="setGDPRRequestComment(\''.$request_id.'\', this.value);">'.$request['comment'].'</textarea></td>';


				echo '</tr>';
			}

			echo '</table>';
		}
		else{
			echo '<p>'.$lang['srv_gdpr_requests_none'].'</p>';
		}
		
		
		// Tabela opravljenih zahtevkov
		if(count($request_list_done) > 0){
			
			echo '<br /><span class="requests_table_title">'.$lang['srv_gdpr_requests_done'].'</span>';
			
			echo '<table class="gdpr_surveys requests" style="margin-top:0;">';

			echo '<tr>';
			echo '<th>'.$lang['srv_gdpr_requests_survey'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_recnum'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_ip'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_url'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_email'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_date'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_text'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_type'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_date_sent'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_done'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_comment'].'</th>';
			echo '</tr>';

			foreach($request_list_done as $request_id => $request){

				echo '<tr '.($request['status'] == 0 ? ' class="red_row"' : '').'>';

				echo '<td><a href="'.$site_url.'admin/survey/index.php?anketa='.$request['ank_id'].'&a=data">'.$request['naslov'].'</a></td>';
				
				//echo '<td>'.$request['recnum'].'</td>';
				//echo '<td>'.$request['ip'].'</td>';
				echo '<td>'.$request['url'].'</td>';
				echo '<td>'.$request['email'].'</td>';	
				//echo '<td>'.$request['date'].'</td>';
				echo '<td>'.$request['text'].'</td>';
				
				echo '<td>'.$lang['srv_gdpr_requests_type_'.$request['type']].'</td>';
				
				echo '<td>'.date('j.n.Y', strtotime($request['datum'])).'</td>';
				
				// Checkbox ce je zahteva opravljena
				//echo '<td>'.($request['status'] == '1' ? $lang['srv_gdpr_requests_status_1'] : $lang['srv_gdpr_requests_status_0']).'</td>';
				echo '<td><input type="checkbox" value="1" class="pointer" onClick="setGDPRRequestStatus(\''.$request_id.'\', this.checked); return false;" '.($request['status'] == '1' ? ' checked="checked"' : '').'></td>';

				// Komentar avtorja
				echo '<td><textarea style="height:30px; width:200px;" onBlur="setGDPRRequestComment(\''.$request_id.'\', this.value);">'.$request['comment'].'</textarea></td>';


				echo '</tr>';
			}

			echo '</table>';
		}
	}

	// Prikazemo vsebino zavihka gdpr - VSE zahteve za izbris (samo admini)
	public function displayGDPRRequestsAll(){
		global $site_url;
		global $lang;

		$sql = sisplet_query("SELECT r.*,
									a.naslov,
									u.email AS u_email, u.name AS u_name, u.surname AS u_surname,
									gu.type AS gu_type, gu.organization AS gu_organization, gu.dpo_firstname AS gu_dpo_firstname, gu.dpo_lastname AS gu_dpo_lastname, gu.dpo_email AS gu_dpo_email, gu.dpo_phone AS gu_dpo_phone, gu.email AS gu_email, gu.firstname AS gu_firstname, gu.lastname AS gu_lastname, gu.phone AS gu_phone, gu.address AS gu_address, gu.country AS gu_country
								FROM srv_gdpr_requests AS r
								LEFT JOIN srv_anketa AS a ON (r.ank_id=a.id)
								LEFT JOIN users AS u ON (r.usr_id=u.id)
								LEFT JOIN srv_gdpr_user AS gu ON (r.usr_id=gu.usr_id)
								WHERE r.status='0'
								ORDER BY date(r.datum) ASC");
		if(mysqli_num_rows($sql) > 0){

			echo '<table class="gdpr_surveys requests">';

			echo '<tr>';
			echo '<th>'.$lang['srv_gdpr_requests_author'].'</th>';

			echo '<th>'.$lang['srv_gdpr_requests_responsible'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_organization'].'</th>';

			echo '<th>'.$lang['srv_gdpr_requests_survey'].'</th>';

			//echo '<th>'.$lang['srv_gdpr_requests_recnum'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_ip'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_url'].'</th>';	
			echo '<th>'.$lang['srv_gdpr_requests_email'].'</th>';	
			//echo '<th>'.$lang['srv_gdpr_requests_date'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_text'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_type'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_date_sent'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_status'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_comment'].'</th>';
			echo '</tr>';


			while($row = mysqli_fetch_array($sql)){

				echo '<tr '.($row['status'] == 0 ? ' class="red_row"' : '').'>';

				// Avtor
				echo '<td>'.$row['u_name'].' '.$row['u_surname'].'<br />('.$row['u_email'].')</td>';

				// Odgovorna oseba
				echo '<td style="text-align:left; white-space:nowrap;">';
				if($row['gu_firstname'] != '' || $row['gu_lastname'] != '' || $row['gu_email'] != '')
					echo '<span class="bold">'.$lang['srv_gdpr_user_settings_firstname'].':</span> '.$row['gu_firstname'].' '.$row['gu_lastname'].($row['gu_email'] != '' ? ' ('.$row['gu_email'].')' : '').'<br />';
				if($row['gu_phone'] != '')
					echo '<span class="bold">'.$lang['srv_gdpr_user_settings_phone'].':</span> '.$row['gu_phone'].'<br />';
				if($row['gu_address'] != '' || $row['gu_country'] != '')
					echo '<span class="bold">'.$lang['srv_gdpr_user_settings_address'].':</span> '.$row['gu_address'].($row['gu_country'] != '' ? ', '.$row['gu_country'] : '');
				echo '</td>';
				
				// Organizacija
				echo '<td style="text-align:left; white-space:nowrap;">';
				if($row['gu_type'] == '1'){
					if($row['gu_organization'] != '')			
						echo '<span class="bold">'.$lang['srv_gdpr_user_settings_organization'].':</span> '.$row['gu_organization'].'<br />';
					if($row['gu_dpo_firstname'] != '' || $row['gu_dpo_lastname'] != '' || $row['gu_dpo_email'] != '')
						echo '<span class="bold">DPO:</span> '.$row['gu_dpo_firstname'].' '.$row['gu_dpo_lastname'].($row['gu_dpo_email'] != '' ? ' ('.$row['gu_dpo_email'].')' : '').'<br />';
					if($row['gu_dpo_phone'] != '')
						echo '<span class="bold">'.$lang['srv_gdpr_user_settings_phone'].':</span> '.$row['gu_dpo_phone'].'<br />';
				}
				else{
					echo '/';
				}
				echo '</td>';

				// Anketa
				echo '<td><a href="'.$site_url.'admin/survey/index.php?anketa='.$row['ank_id'].'&a=data">'.$row['naslov'].'</a></td>';

				//echo '<td>'.$row['recnum'].'</td>';
				//echo '<td>'.$row['ip'].'</td>';
				echo '<td>'.$row['url'].'</td>';
				echo '<td>'.$row['email'].'</td>';	
				//echo '<td>'.$row['date'].'</td>';
				echo '<td>'.$row['text'].'</td>';
				echo '<td>'.$lang['srv_gdpr_requests_type_'.$row['type']].'</td>';
				echo '<td>'.date('j.n.Y', strtotime($row['datum'])).'</td>';
				echo '<td>'.($row['status'] == '1' ? $lang['srv_gdpr_requests_status_1'] : $lang['srv_gdpr_requests_status_0']).'</td>';
				echo '<td style="text-align:left;">'.$row['comment'].'</td>';

				echo '</tr>';
			}

			echo '</table>';
		}
		else{
			echo '<p>'.$lang['srv_gdpr_requests_none'].'</p>';
		}
		
		
		// Opravljeni zahtevki
		$sql = sisplet_query("SELECT r.*,
									a.naslov,
									u.email AS u_email, u.name AS u_name, u.surname AS u_surname,
									gu.type AS gu_type, gu.organization AS gu_organization, gu.dpo_firstname AS gu_dpo_firstname, gu.dpo_lastname AS gu_dpo_lastname, gu.dpo_email AS gu_dpo_email, gu.dpo_phone AS gu_dpo_phone, gu.email AS gu_email, gu.firstname AS gu_firstname, gu.lastname AS gu_lastname, gu.phone AS gu_phone, gu.address AS gu_address, gu.country AS gu_country
								FROM srv_gdpr_requests AS r
								LEFT JOIN srv_anketa AS a ON (r.ank_id=a.id)
								LEFT JOIN users AS u ON (r.usr_id=u.id)
								LEFT JOIN srv_gdpr_user AS gu ON (r.usr_id=gu.usr_id)
								WHERE r.status='1'
								ORDER BY date(r.datum) DESC");
		if(mysqli_num_rows($sql) > 0){

			echo '<br />';
			echo '<a href="#" onClick="$(\'#table_requests_done\').toggle(); $(\'#requests_table_title_plus\').toggle(); $(\'#requests_table_title_minus\').toggle();">';
			echo '	<span class="requests_table_title"><span id="requests_table_title_plus">+</span><span id="requests_table_title_minus" style="display:none;">-</span> '.$lang['srv_gdpr_requests_done'].'</span>';
			echo '</a>';
			
			echo '<table class="gdpr_surveys requests" id="table_requests_done" style="margin-top:0; display:none;">';

			echo '<tr>';
			echo '<th>'.$lang['srv_gdpr_requests_author'].'</th>';

			echo '<th>'.$lang['srv_gdpr_requests_responsible'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_organization'].'</th>';

			echo '<th>'.$lang['srv_gdpr_requests_survey'].'</th>';

			//echo '<th>'.$lang['srv_gdpr_requests_recnum'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_ip'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_url'].'</th>';	
			echo '<th>'.$lang['srv_gdpr_requests_email'].'</th>';	
			//echo '<th>'.$lang['srv_gdpr_requests_date'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_text'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_type'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_date_sent'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_status'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_comment'].'</th>';
			echo '</tr>';


			while($row = mysqli_fetch_array($sql)){

				echo '<tr '.($row['status'] == 0 ? ' class="red_row"' : '').'>';

				// Avtor
				echo '<td>'.$row['u_name'].' '.$row['u_surname'].'<br />('.$row['u_email'].')</td>';

				// Odgovorna oseba
				echo '<td style="text-align:left; white-space:nowrap;">';
				if($row['gu_firstname'] != '' || $row['gu_lastname'] != '' || $row['gu_email'] != '')
					echo '<span class="bold">'.$lang['srv_gdpr_user_settings_firstname'].':</span> '.$row['gu_firstname'].' '.$row['gu_lastname'].($row['gu_email'] != '' ? ' ('.$row['gu_email'].')' : '').'<br />';
				if($row['gu_phone'] != '')
					echo '<span class="bold">'.$lang['srv_gdpr_user_settings_phone'].':</span> '.$row['gu_phone'].'<br />';
				if($row['gu_address'] != '' || $row['gu_country'] != '')
					echo '<span class="bold">'.$lang['srv_gdpr_user_settings_address'].':</span> '.$row['gu_address'].($row['gu_country'] != '' ? ', '.$row['gu_country'] : '');
				echo '</td>';
				
				// Organizacija
				echo '<td style="text-align:left; white-space:nowrap;">';
				if($row['gu_type'] == '1'){
					if($row['gu_organization'] != '')			
						echo '<span class="bold">'.$lang['srv_gdpr_user_settings_organization'].':</span> '.$row['gu_organization'].'<br />';
					if($row['gu_dpo_firstname'] != '' || $row['gu_dpo_lastname'] != '' || $row['gu_dpo_email'] != '')
						echo '<span class="bold">DPO:</span> '.$row['gu_dpo_firstname'].' '.$row['gu_dpo_lastname'].($row['gu_dpo_email'] != '' ? ' ('.$row['gu_dpo_email'].')' : '').'<br />';
					if($row['gu_dpo_phone'] != '')
						echo '<span class="bold">'.$lang['srv_gdpr_user_settings_phone'].':</span> '.$row['gu_dpo_phone'].'<br />';
				}
				else{
					echo '/';
				}
				echo '</td>';

				// Anketa
				echo '<td><a href="'.$site_url.'admin/survey/index.php?anketa='.$row['ank_id'].'&a=data">'.$row['naslov'].'</a></td>';

				//echo '<td>'.$row['recnum'].'</td>';
				//echo '<td>'.$row['ip'].'</td>';
				echo '<td>'.$row['url'].'</td>';
				echo '<td>'.$row['email'].'</td>';	
				//echo '<td>'.$row['date'].'</td>';
				echo '<td>'.$row['text'].'</td>';
				echo '<td>'.$lang['srv_gdpr_requests_type_'.$row['type']].'</td>';
				echo '<td>'.date('j.n.Y', strtotime($row['datum'])).'</td>';
				echo '<td>'.($row['status'] == '1' ? $lang['srv_gdpr_requests_status_1'] : $lang['srv_gdpr_requests_status_0']).'</td>';
				echo '<td style="text-align:left;">'.$row['comment'].'</td>';

				echo '</tr>';
			}

			echo '</table>';
		}
	}


	// Prikazemo vsebino zavihka gdpr - nastavitve posamezne ankete
	public function displayGDPRSurvey($ank_id){
		global $site_url;
		global $lang;
		global $admin_languages;
		global $global_user_id;

        $gdpr_settings = self::getSurveySettings($ank_id);
        
        // Prikaz naprednih nastavitev
        if($gdpr_settings != 0 && ($gdpr_settings['name'] == 1 || $gdpr_settings['email'] == 1 || $gdpr_settings['location'] == 1 || $gdpr_settings['phone'] == 1 || $gdpr_settings['web'] == 1 || $gdpr_settings['other'] == 1))
            $gdpr_show_advanced = true;
        else
            $gdpr_show_advanced = false;


        // Preverimo, če imamo anketo v večih jezikih
        $survey_settings = SurveyInfo::getInstance()->getSurveyRow();

        $language_slo = ($survey_settings['lang_resp'] == 1) ? true : false;
        $language_eng = ($survey_settings['lang_resp'] > 1) ? true : false;

        $sqlLang = sisplet_query("SELECT lang_id FROM srv_language WHERE ank_id='".$ank_id."' ORDER BY lang_id ASC");
        while ($rowLang = mysqli_fetch_array($sqlLang)) {
            
            if($rowLang['lang_id'] == '1'){
                $language_slo = true;
            }
            else{
                $language_eng = true;
                break;
            }
        }


		// GDPR nastavitve ankete
		echo '<fieldset class="wide">';
		echo '<legend>'.$lang['srv_gdpr_survey_settings'].'</legend>';

		// Besedilo na vrhu
		echo '<p class="italic">'.$lang['srv_gdpr_survey_settings_desc1'].'</p>';
		
		echo '<p class="italic">'.$lang['srv_gdpr_survey_settings_desc2'].' <a href="'.$site_url.'admin/survey/index.php?a=gdpr" target="_blank"><span class="bold">'.$lang['srv_here'].' >></span></a></p>';
		
		echo '<p class="italic">'.$lang['srv_gdpr_survey_settings_desc3'].'</p>';

		// Ali gre za gdpr anketo
		echo '<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_data'].':</span>';
		echo '<label for="is_gdpr_1"><input type="radio" name="is_gdpr" id="is_gdpr_1" '.($gdpr_settings != 0 ? ' checked':'').' value="1" onClick="showGDPRSettings();">'.$lang['yes'].'</label> ';
		echo '<label for="is_gdpr_0"><input type="radio" name="is_gdpr" id="is_gdpr_0" '.($gdpr_settings == 0 ? ' checked':'').' value="0" onClick="showGDPRSettings();">'.$lang['no'].'</label> ';


		echo '<br /><br />';


        // Oznacena kot GDPR - prikazemo identifikatorje
        echo '<div id="gdpr_data_identifiers" '.($gdpr_settings == 0 ? ' style="display:none;"' : '').'>';
        
        echo '<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_data_q'].'</span><br /><br />';

        // Osebni podatek ime
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_name'].':</span>';
		echo '	<label for="name_1"><input type="radio" name="name" id="name_1" '.($gdpr_settings['name'] != 0 ? ' checked':'').' value="1" onClick="showGDPRSettings();">'.$lang['yes'].'</label> ';
		echo '	<label for="name_0"><input type="radio" name="name" id="name_0" '.($gdpr_settings['name'] == 0 ? ' checked':'').' value="0" onClick="showGDPRSettings();">'.$lang['no'].'</label> ';

		echo '	<br />';

		// Osebni podatek email
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_email'].':</span>';
		echo '	<label for="email_1"><input type="radio" name="email" id="email_1" '.($gdpr_settings['email'] != 0 ? ' checked':'').' value="1" onClick="showGDPRSettings();">'.$lang['yes'].'</label> ';
		echo '	<label for="email_0"><input type="radio" name="email" id="email_0" '.($gdpr_settings['email'] == 0 ? ' checked':'').' value="0" onClick="showGDPRSettings();">'.$lang['no'].'</label> ';

		echo '	<br />';

		// Osebni podatek lokacija
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_location'].':</span>';
		echo '	<label for="location_1"><input type="radio" name="location" id="location_1" '.($gdpr_settings['location'] != 0 ? ' checked':'').' value="1" onClick="showGDPRSettings();">'.$lang['yes'].'</label> ';
		echo '	<label for="location_0"><input type="radio" name="location" id="location_0" '.($gdpr_settings['location'] == 0 ? ' checked':'').' value="0" onClick="showGDPRSettings();">'.$lang['no'].'</label> ';

		echo '	<br />';

		// Osebni podatek telefon
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_phone'].':</span>';
		echo '	<label for="phone_1"><input type="radio" name="phone" id="phone_1" '.($gdpr_settings['phone'] != 0 ? ' checked':'').' value="1" onClick="showGDPRSettings();">'.$lang['yes'].'</label> ';
		echo '	<label for="phone_0"><input type="radio" name="phone" id="phone_0" '.($gdpr_settings['phone'] == 0 ? ' checked':'').' value="0" onClick="showGDPRSettings();">'.$lang['no'].'</label> ';
		
		echo '	<br />';

		// Osebni podatek spletni identifikator
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_web'].':</span>';
		echo '	<label for="web_1"><input type="radio" name="web" id="web_1" '.($gdpr_settings['web'] != 0 ? ' checked':'').' value="1" onClick="showGDPRSettings();">'.$lang['yes'].'</label> ';
		echo '	<label for="web_0"><input type="radio" name="web" id="web_0" '.($gdpr_settings['web'] == 0 ? ' checked':'').' value="0" onClick="showGDPRSettings();">'.$lang['no'].'</label> ';
		
		echo '	<br />';

		// Osebni podatek drugo
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_other'].':</span>';
		echo '	<label for="other_1"><input type="radio" name="other" id="other_1" '.($gdpr_settings['other'] != 0 ? ' checked':'').' value="1" onChange="showGDPRSettings(); toggleGDPROtherText(this);">'.$lang['yes'].'</label> ';
		echo '	<label for="other_0"><input type="radio" name="other" id="other_0" '.($gdpr_settings['other'] == 0 ? ' checked':'').' value="0" onChange="showGDPRSettings(); toggleGDPROtherText(this);">'.$lang['no'].'</label> ';
		
        echo '<div id="other_text" '.($gdpr_settings['other'] == 0 ? ' style="display:none;"' : '').'>';
        if($language_slo){
            echo '	<span class="nastavitveSpan1">&nbsp;</span>';
            echo '	<textarea class="other" name="other_text_slo" id="other_text_slo" style="width:500px; height:80px; margin-top:10px;">'.$gdpr_settings['other_text_slo'].'</textarea> <span class="italic">'.$admin_languages['1'].'</span>';
            echo '<br />';
        }
        if($language_eng){
            echo '	<span class="nastavitveSpan1">&nbsp;</span>';
            echo '	<textarea class="other" name="other_text_eng" id="other_text_eng" style="width:500px; height:80px; margin-top:10px;">'.$gdpr_settings['other_text_eng'].'</textarea> <span class="italic">'.$admin_languages['2'].'</span>';
        }
        echo '</div>';
        
        echo '</div>';


		echo '	<br /><br />';


		// Oznacena kot GDPR - prikazemo dodatne nastavitve gdpr
		echo '<div id="gdpr_data_settings" '.(!$gdpr_show_advanced ? ' style="display:none;"' : '').'>';

		// Ali se uporabi 1ka template v uvodu
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_1ka_template'].':</span>';
		echo '	<label for="1ka_template_1"><input type="radio" name="1ka_template" id="1ka_template_1" '.(!isset($gdpr_settings['1ka_template']) || $gdpr_settings['1ka_template'] != 0 ? ' checked':'').' value="1" onClick="showGDPRTemplate(this.value);">'.$lang['yes'].'</label> ';
		echo '	<label for="1ka_template_0"><input type="radio" name="1ka_template" id="1ka_template_0" '.(isset($gdpr_settings['1ka_template']) && $gdpr_settings['1ka_template'] == 0 ? ' checked':'').' value="0" onClick="showGDPRTemplate(this.value);">'.$lang['no'].'</label> ';

		echo '	<div class="spaceLeft floatRight red" style="display:inline; width:520px;">';
		// Obvestilo z linkom na preview preduvoda
		echo '<span id="gdpr_data_template" class="italic" '.(isset($gdpr_settings['1ka_template']) && $gdpr_settings['1ka_template'] == 0 ? ' style="display:none;"' : '').'>';
		echo $lang['srv_gdpr_survey_gdpr_1ka_template_note'];
		echo '<br /><span class="bold"><a href="#" onClick="previewGDPRIntro(); return false;">'.$lang['srv_gdpr_survey_gdpr_1ka_template_preview'].'</a></span>';
		echo '</span>';
		// Warning ce ne uporablja template preduvoda
		echo '		<span id="gdpr_data_template_warning" class="italic red" '.(!isset($gdpr_settings['1ka_template']) || $gdpr_settings['1ka_template'] == 1 ? ' style="display:none;"' : '').'>'.$lang['srv_gdpr_survey_gdpr_1ka_template_warning'].'</span>';
		echo '	</div>';
	
		echo '	<br /><br /><br /><br />';

		// Podrobnosti o zbiranju podatkov (popup v uvodu)
		/*if($gdpr_settings['about'] == ''){
            $about_array = self::getGDPRInfoArray($ank_id);
            $about_text = self::getGDPRTextFromArray($about_array, $type='textarea');
		}
		else{
			$about_text = $gdpr_settings['about'];
		}
		echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_about'].':<br /><br /><span class="italic">'.$lang['srv_gdpr_survey_gdpr_about_note'].'</span></span>';
		echo '	<textarea name="about" id="about" style="width:500px; height:200px;" disabled="disabled">'.$about_text.'</textarea> ';*/

        echo '	<span class="nastavitveSpan1" >'.$lang['srv_gdpr_survey_gdpr_about'].':<br /><br /><span class="italic">'.$lang['srv_gdpr_survey_gdpr_about_note'].'</span></span>';
		if($language_slo){
            $about_array = self::getGDPRInfoArray($ank_id, $language_id='1');
            $about_text = self::getGDPRTextFromArray($about_array, $type='textarea');

            echo '	<textarea name="about" id="about" style="width:500px; height:200px;" disabled="disabled">'.$about_text.'</textarea> <span class="italic">'.$admin_languages['1'].'</span>';
            echo '	<br><br><span class="nastavitveSpan1">&nbsp;</span>';
        }
        if($language_eng){
            $about_array = self::getGDPRInfoArray($ank_id, $language_id='2');
            $about_text = self::getGDPRTextFromArray($about_array, $type='textarea');

            echo '	<textarea name="about" id="about" style="width:500px; height:200px;" disabled="disabled">'.$about_text.'</textarea> <span class="italic">'.$admin_languages['2'].'</span>';
        }

		echo '<br /><br />';
		
		// Povezava na splosne gdpr nastavitve - ce ni izpolnil osebnih podatkov, je rdec warning
		echo '<a href="'.$site_url.'admin/survey/index.php?a=gdpr" target="_blank"><span class="bold">'.$lang['srv_gdpr_general_settings'].'</span></a>';
		if(!self::checkUserSettings())
			echo '<br /><span class="red italic">'.$lang['srv_gdpr_general_settings_warning'].'</span>';

		echo '<br /><br />';
		
		echo '</div>';

		echo '</fieldset>';


        echo '<br class="clr" />';


        // Dodatne informacije
        echo '<fieldset id="gdpr_additional_info" class="wide" '.(!$gdpr_show_advanced ? ' style="display:none;"' : '').'>';
		echo '<legend>'.$lang['srv_gdpr_survey_settings'].'</legend>';

        echo '	<br />';


		// Cas hranjenja podatkov
		echo '	<span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_expire'].':</span>';
		echo '	<label for="expire_0"><input type="radio" name="expire" id="expire_0" '.($gdpr_settings['expire'] == 0 ? ' checked':'').' value="0" onClick="toggleGDPRInfoText(this);">'.$lang['srv_gdpr_survey_gdpr_expire_0'].'</label> ';
        
        echo '	<br /><span class="nastavitveSpan1">&nbsp;</span>';
        echo '	<label for="expire_1"><input type="radio" name="expire" id="expire_1" '.($gdpr_settings['expire'] != 0 ? ' checked':'').' value="1" onClick="toggleGDPRInfoText(this);">'.$lang['srv_gdpr_survey_gdpr_expire_1'].'</label> ';
        if($language_slo){
            echo '	<br /><span class="nastavitveSpan1">&nbsp;</span><input type="text" class="line_text expire" placeholder="'.$lang['srv_gdpr_survey_gdpr_expire_1_placeholder'].'" name="expire_text_slo" id="expire_text_slo" value="'.$gdpr_settings['expire_text_slo'].'" '.($gdpr_settings['expire'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['1'].'</span>';
        }
        if($language_eng){
            echo '	<br /><span class="nastavitveSpan1">&nbsp;</span><input type="text" class="line_text expire" placeholder="'.$lang['srv_gdpr_survey_gdpr_expire_1_placeholder'].'" name="expire_text_eng" id="expire_text_eng" value="'.$gdpr_settings['expire_text_eng'].'" '.($gdpr_settings['expire'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['2'].'</span>';
        }

		echo '	<br /><br />';


		// Drugi uporabniki podatkov
		echo '	<span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_other_users'].':</span>';
		echo '	<label for="other_users_0"><input type="radio" name="other_users" id="other_users_0" '.($gdpr_settings['other_users'] == 0 ? ' checked':'').' value="0" onClick="toggleGDPRInfoText(this);">'.$lang['srv_gdpr_survey_gdpr_other_users_0'].'</label> ';
        
        echo '	<br /><span class="nastavitveSpan1">&nbsp;</span>';
        echo '	<label for="other_users_1"><input type="radio" name="other_users" id="other_users_1" '.($gdpr_settings['other_users'] != 0 ? ' checked':'').' value="1" onClick="toggleGDPRInfoText(this);">'.$lang['srv_gdpr_survey_gdpr_other_users_1'].'</label> ';
        if($language_slo){
            echo '	<br /><span class="nastavitveSpan1">&nbsp;</span><input type="text" class="line_text other_users" placeholder="'.$lang['srv_gdpr_survey_gdpr_other_users_1_placeholder'].'" name="other_users_text_slo" id="other_users_text_slo" value="'.$gdpr_settings['other_users_text_slo'].'" '.($gdpr_settings['other_users'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['1'].'</span>';
        }
        if($language_eng){
            echo '	<br /><span class="nastavitveSpan1">&nbsp;</span><input type="text" class="line_text other_users" placeholder="'.$lang['srv_gdpr_survey_gdpr_other_users_1_placeholder'].'" name="other_users_text_eng" id="other_users_text_eng" value="'.$gdpr_settings['other_users_text_eng'].'" '.($gdpr_settings['other_users'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['2'].'</span>';
        }

		echo '	<br /><br />';


		// Izvoz v tuje drzave
		echo '	<span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_export'].':</span>';
		echo '	<label for="export_0"><input type="radio" name="export" id="export_0" '.($gdpr_settings['export'] == 0 ? ' checked':'').' value="0" onClick="toggleGDPRInfoText(this);">'.$lang['srv_gdpr_survey_gdpr_export_0'].'</label> ';
        
        echo '	<br /><span class="nastavitveSpan1">&nbsp;</span>';
        echo '	<label for="export_1"><input type="radio" name="export" id="export_1" '.($gdpr_settings['export'] != 0 ? ' checked':'').' value="1" onClick="toggleGDPRInfoText(this);">'.$lang['srv_gdpr_survey_gdpr_export_country'].'</label> ';
        if($language_slo){
            echo '	<br /><span class="nastavitveSpan1">&nbsp;</span><input type="text" class="line_text export" placeholder="'.$lang['srv_gdpr_survey_gdpr_export_country_placeholder'].'" name="export_country_slo" id="export_country_slo" value="'.$gdpr_settings['export_country_slo'].'" '.($gdpr_settings['export'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['1'].'</span>';
        }
        if($language_eng){
            echo '	<br /><span class="nastavitveSpan1">&nbsp;</span><input type="text" class="line_text export" placeholder="'.$lang['srv_gdpr_survey_gdpr_export_country_placeholder'].'" name="export_country_eng" id="export_country_eng" value="'.$gdpr_settings['export_country_eng'].'" '.($gdpr_settings['export'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['2'].'</span>';
        }

        echo '	<br /><br /><span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_export_user'].':</span>';
        if($language_slo){
            echo ' <input type="text" class="line_text export" placeholder="'.$lang['srv_gdpr_survey_gdpr_export_user_placeholder'].'" name="export_user_slo" id="export_user_slo" value="'.$gdpr_settings['export_user_slo'].'" '.($gdpr_settings['export'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['1'].'</span>';
            echo ' <br />';
        }
        if($language_eng){
            echo ' <input type="text" class="line_text export" placeholder="'.$lang['srv_gdpr_survey_gdpr_export_user_placeholder'].'" name="export_user_eng" id="export_user_eng" value="'.$gdpr_settings['export_user_eng'].'" '.($gdpr_settings['export'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['2'].'</span>';
        }

        echo '<br /><br /><span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_export_legal'].':</span>';
        if($language_slo){
            echo ' <input type="text" class="line_text long export" placeholder="'.$lang['srv_gdpr_survey_gdpr_export_legal_placeholder'].'" name="export_legal_slo" id="export_legal_slo" value="'.$gdpr_settings['export_legal_slo'].'" '.($gdpr_settings['export'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['1'].'</span>';
            echo ' <br /><span class="nastavitveSpan1">&nbsp;</span>';
        }
        if($language_eng){
            echo ' <input type="text" class="line_text long export" placeholder="'.$lang['srv_gdpr_survey_gdpr_export_legal_placeholder'].'" name="export_legal_eng" id="export_legal_eng" value="'.$gdpr_settings['export_legal_eng'].'" '.($gdpr_settings['export'] == 0 ? ' disabled="disabled"' : '').'> <span class="italic">'.$admin_languages['2'].'</span>';
        }

		echo '	<br /><br />';


        // Pooblascena oseba za varstvo podatkov
        if($gdpr_settings['authorized'] == ''){
            
            $user_settings = self::getUserSettings();
            
            // Zasebnik brez DPO
            if($user_settings['type'] == '0' && $user_settings['has_dpo'] == '0'){

                // DPO mail je enak navadnemu mailu, ki ga je vnesel v splosnih nastavitvah
                if($user_settings['email'] != ''){
                    $gdpr_authorized = $user_settings['email'];
                }
                // Ce ga ni vnesel, je DPO mail enak mailu avtorja ankete
                else{
                    $gdpr_authorized = User::getInstance()->primaryEmail();
                }
            }
            // Zasebnik z DPO ali organizacija
            else{

                // DPO mail je enak DPO mailu, ki ga je vnesel v splosnih nastavitvah
                if($user_settings['dpo_email'] != ''){
                    $gdpr_authorized = $user_settings['dpo_email'];
                }
                // Ce ga ni vnesel, je DPO mail enak splosnemu mailu oz. mailu avtorja ankete
                else{
                    if($user_settings['email'] != '')
                        $gdpr_authorized = $user_settings['email'];
                    else
                        $gdpr_authorized = User::getInstance()->primaryEmail();
                }
            }
        }
        else{
            $gdpr_authorized = $gdpr_settings['authorized'];
        }
		echo '	<span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_authorized'].':</span>';
		echo '	<input type="text" name="authorized" id="authorized" value="'.$gdpr_authorized.'">';
		
		echo '	<br /><br />';


        // Kontaktni email
        if($gdpr_settings['contact_email'] == ''){

            $user_settings = self::getUserSettings();

            // Kontaktni mail je enak mailu, ki ga je vnesel v splosnih nastavitvah
            if($user_settings['email'] != ''){
                $gdpr_contact_email = $user_settings['email'];
            }
            // Ce ga ni vnesel, je kontaktni mail enak mailu avtorja ankete
            else{
                $gdpr_contact_email = User::getInstance()->primaryEmail();
            }
        }
        else{
            $gdpr_contact_email = $gdpr_settings['contact_email'];
        }
		echo '	<span class="nastavitveSpan1">'.$lang['srv_gdpr_survey_gdpr_contact_email'].':</span>';
		echo '	<input type="text" name="contact_email" id="contact_email" value="'.$gdpr_contact_email.'">';
		
        echo '	<br /><br />';
        

        // Opomba
		echo '	<span class="nastavitveSpan1">'.$lang['note'].':</span>';
		if($language_slo){
            echo '	<textarea name="note_slo" id="note_slo" style="width:500px; height:80px;">'.$gdpr_settings['note_slo'].'</textarea> <span class="italic">'.$admin_languages['1'].'</span>';
            echo '	<span class="nastavitveSpan1">&nbsp;</span>';
        }
        if($language_eng){
            echo '	<textarea name="note_eng" id="note_eng" style="width:500px; height:80px;">'.$gdpr_settings['note_eng'].'</textarea> <span class="italic">'.$admin_languages['2'].'</span>';
        }
		
		echo '	<br /><br />';

		echo '</fieldset>';


        // Gumb shrani spremembe
		echo '<br class="clr" />';

		//echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $ank_id . '.submit(); return false;"><span>';
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onClick="editGDPRSurvey(\''.$ank_id.'\'); return false;"><span>';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';

        echo '<br /><br />';
         

        // Export - informacije dane posamezniku
        echo '<fieldset id="gdpr_export_individual" class="wide" '.(!$gdpr_show_advanced ? ' style="display:none;"' : '').'>';
        echo '<legend>'.$lang['srv_gdpr_survey_gdpr_export_individual'].'</legend>';

        echo '	<br />';

        echo '<a href="#" onClick="previewGDPRExport(\'1\'); return false;"><span class="faicon preview"></span>'.$lang['srv_poglejanketo2'].'</a>';
        echo '	<br />';

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);

        if(!$userAccess->checkUserAccess($what='gdpr_export')){
            $userAccess->displayNoAccess($what='gdpr_export');
        }
        else{

            echo '<br />';

            if($language_slo){
                echo '<span class="bold">'.$admin_languages['1'].':</span><br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=pdf_gdpr_individual&anketa='.$ank_id.'&language=1').'" target="_blank"><span class="faicon pdf"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
                echo '<br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=rtf_gdpr_individual&anketa='.$ank_id.'&language=1').'" target="_blank"><span class="faicon rtf"></span>&nbsp;DOC - (Microsoft Word)</a>';
                
                echo '<br /><br />';
            }

            if($language_eng){
                echo '<span class="bold">'.$admin_languages['2'].':</span><br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=pdf_gdpr_individual&anketa='.$ank_id.'&language=2').'" target="_blank"><span class="faicon pdf"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
                echo '<br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=rtf_gdpr_individual&anketa='.$ank_id.'&language=2').'" target="_blank"><span class="faicon rtf"></span>&nbsp;DOC - (Microsoft Word)</a>';
            
                echo '<br /><br />';
            }
        }

        echo '</fieldset>';


        echo '	<br />';        


        // Export - evidenca dejavnosti obdelav
        echo '<fieldset id="gdpr_export_activity" class="wide" '.(!$gdpr_show_advanced ? ' style="display:none;"' : '').'>';
        echo '<legend>'.$lang['srv_gdpr_survey_gdpr_export_activity'].'</legend>';

        echo '	<br />';

        echo '<a href="#" onClick="previewGDPRExport(\'2\'); return false;"><span class="faicon preview"></span>'.$lang['srv_poglejanketo2'].'</a>';
        echo '	<br />';

        if(!$userAccess->checkUserAccess($what='gdpr_export')){
            $userAccess->displayNoAccess($what='gdpr_export');
        }
        else{     

            echo '<br />';

            if($language_slo){
                echo '<span class="bold">'.$admin_languages['1'].':</span><br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=pdf_gdpr_activity&anketa='.$ank_id.'&language=1').'" target="_blank"><span class="faicon pdf"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
                echo '	<br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=rtf_gdpr_activity&anketa='.$ank_id.'&language=1').'" target="_blank"><span class="faicon rtf"></span>&nbsp;DOC - (Microsoft Word)</a>';
                    
                echo '<br /><br />';
            }

            if($language_eng){
                echo '<span class="bold">'.$admin_languages['2'].':</span><br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=pdf_gdpr_activity&anketa='.$ank_id.'&language=2').'" target="_blank"><span class="faicon pdf"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
                echo '	<br />';
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=rtf_gdpr_activity&anketa='.$ank_id.'&language=2').'" target="_blank"><span class="faicon rtf"></span>&nbsp;DOC - (Microsoft Word)</a>';
                
                echo '<br /><br />';
            }
        }

        echo '</fieldset>';
	}

	// Prikazemo vsebino zavihka gdpr - nastavitve posamezne ankete
	public function displayGDPRSurveyRequests($ank_id){
		global $site_url;
		global $lang;
		
		echo '<div style="font-style:italic; margin-top:-10px;">';
		echo '<p>'.$lang['srv_gdpr_requests_desc'].'</p>';
		echo '</div>';

		$request_list = array();
		$request_list = self::getUserRequests($ank_id, $status=0);
		
		$request_list_done = array();
		$request_list_done = self::getUserRequests($ank_id, $status=1);

		if(count($request_list) > 0){
			echo '<table class="gdpr_surveys requests">';

			echo '<tr>';
			//echo '<th>'.$lang['srv_gdpr_requests_recnum'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_ip'].'</th>';		
			echo '<th>'.$lang['srv_gdpr_requests_url'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_email'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_date'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_text'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_type'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_date_sent'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_done'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_comment'].'</th>';
			echo '</tr>';

			foreach($request_list as $request_id => $request){

				echo '<tr '.($request['status'] == 0 ? ' class="red_row"' : '').'>';

				//echo '<td>'.$request['recnum'].'</td>';
				//echo '<td>'.$request['ip'].'</td>';	
				echo '<td>'.$request['url'].'</td>';
				echo '<td>'.$request['email'].'</td>';
				//echo '<td>'.$request['date'].'</td>';
				echo '<td>'.$request['text'].'</td>';
				
				echo '<td>'.$lang['srv_gdpr_requests_type_'.$request['type']].'</td>';
				
				echo '<td>'.date('j.n.Y', strtotime($request['datum'])).'</td>';
				
				//echo '<td>'.($request['status'] == '1' ? $lang['srv_gdpr_requests_status_1'] : $lang['srv_gdpr_requests_status_0']).'</td>';
				echo '<td><input type="checkbox" value="1" class="pointer" onClick="setGDPRRequestStatusSurvey(\''.$request_id.'\', this.checked); return false;" '.($request['status'] == '1' ? ' checked="checked"' : '').'</td>';

				echo '<td><textarea style="height:30px; width:200px;" onBlur="setGDPRRequestCommentSurvey(\''.$request_id.'\', this.value);">'.$request['comment'].'</textarea></td>';

				echo '</tr>';
			}

			echo '</table>';
		}
		else{
			echo '<p>'.$lang['srv_gdpr_requests_none'].'</p>';
		}
		
		
		// Tabela opravljenih zahtevkov
		if(count($request_list_done) > 0){
			
			echo '<br /><span class="requests_table_title">'.$lang['srv_gdpr_requests_done'].'</span>';
			
			echo '<table class="gdpr_surveys requests" style="margin-top:0;">';

			echo '<tr>';
			//echo '<th>'.$lang['srv_gdpr_requests_recnum'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_ip'].'</th>';		
			echo '<th>'.$lang['srv_gdpr_requests_url'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_email'].'</th>';
			//echo '<th>'.$lang['srv_gdpr_requests_date'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_text'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_type'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_date_sent'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_done'].'</th>';
			echo '<th>'.$lang['srv_gdpr_requests_comment'].'</th>';
			echo '</tr>';

			foreach($request_list_done as $request_id => $request){

				echo '<tr '.($request['status'] == 0 ? ' class="red_row"' : '').'>';

				//echo '<td>'.$request['recnum'].'</td>';
				//echo '<td>'.$request['ip'].'</td>';	
				echo '<td>'.$request['url'].'</td>';
				echo '<td>'.$request['email'].'</td>';
				//echo '<td>'.$request['date'].'</td>';
				echo '<td>'.$request['text'].'</td>';
				
				echo '<td>'.$lang['srv_gdpr_requests_type_'.$request['type']].'</td>';
				
				echo '<td>'.date('j.n.Y', strtotime($request['datum'])).'</td>';
				
				//echo '<td>'.($request['status'] == '1' ? $lang['srv_gdpr_requests_status_1'] : $lang['srv_gdpr_requests_status_0']).'</td>';
				echo '<td><input type="checkbox" value="1" class="pointer" onClick="setGDPRRequestStatusSurvey(\''.$request_id.'\', this.checked); return false;" '.($request['status'] == '1' ? ' checked="checked"' : '').'</td>';

				echo '<td><textarea style="height:30px; width:200px;" onBlur="setGDPRRequestCommentSurvey(\''.$request_id.'\', this.value);">'.$request['comment'].'</textarea></td>';

				echo '</tr>';
			}

			echo '</table>';
		}
    }
    
    // Prikaze DPA zavihek
	public static function displayGDPRDPA(){
		global $lang;
		global $site_url;

        echo '<p>'.$lang['srv_gdpr_dpa_text'].'</p>';
        echo '<ul>';
        echo '  <li><a href="'.$site_url.'uploadi/dokumenti/DPA_SLO.pdf">'.$lang['srv_gdpr_dpa_slo'].'</a></li>';
        echo '  <li><a href="'.$site_url.'uploadi/dokumenti/DPA_ANG.pdf">'.$lang['srv_gdpr_dpa_eng'].'</a></li>';
        echo '</ul>';

        echo '<p>'.$lang['srv_gdpr_dpa_info'].'</p>';
	}



	// Pridobimo vse ankete userja in za vsako preverimo ce je GDPR
	private function getUserSurveys(){

		$survey_list = array();

		// Pridobimo seznam vseh anket uporabnika
		$SL = new SurveyList();
        $surveys = $SL->getSurveysSimple();

		$temp_gdpr = array();
		$temp_gdpr_p = array();
		$temp_naslov = array();
		$temp_active = array();

		$key = 0;
		foreach($surveys as $anketa){

			$potential_gdpr = $this->potentialGDPRSurvey($anketa['id']);
			$gdpr = $this->isGDPRSurvey($anketa['id']);

			$temp_gdpr[$key] = $gdpr;
			$temp_gdpr_p[$key] = $potential_gdpr;
			$temp_naslov[$key] = $anketa['naslov'];
			$temp_active[$key] = $anketa['active'];

			$survey_list[$key] = array(
				'id' => $anketa['id'],
				'naslov' => $anketa['naslov'],
				'active' => $anketa['active'],
				'potential_gdpr' => $potential_gdpr,
				'gdpr' => $gdpr
			);

			$key++;
		}

		array_multisort($temp_gdpr, SORT_DESC, $temp_gdpr_p, SORT_DESC, $temp_active, SORT_DESC, $temp_naslov, SORT_DESC, $survey_list);

		return $survey_list;
	}

	// Pridobimo vse zahteve za izbris za userja (za vse ankete ali samo za doloceno anketo)
	private function getUserRequests($ank_id=0, $status=0){
		global $global_user_id;

		$requests = array();

		$anketa_query = '';
		if($ank_id != 0)
			$anketa_query = " AND r.ank_id='".$ank_id."'";
			
		$status_query = " AND r.status='".$status."'";
		
		$order_by = ($status == 0) ? 'ASC' : 'DESC';

		$sql = sisplet_query("SELECT r.*, a.naslov 
								FROM srv_gdpr_requests r, srv_anketa a 
								WHERE r.usr_id='".$global_user_id."' AND r.ank_id=a.id ".$anketa_query." ".$status_query."
								ORDER BY date(datum) ".$order_by."");
		while($row = mysqli_fetch_array($sql)){
			$requests[$row['id']] = $row;
		}

		return $requests;
	}

	// Pridobimo vse zahteve za izbris za userja (za vse ankete ali samo za doloceno anketo)
	public static function countUserUnfinishedRequests(){
		global $global_user_id;

		$sql = sisplet_query("SELECT COUNT(id) FROM srv_gdpr_requests WHERE usr_id='".$global_user_id."' AND status!='1'");
		$row = mysqli_fetch_array($sql);
		
		return $row['COUNT(id)'];
	}


	// Pridobimo vse gdpr nastavitve userja
	private static function getUserSettings(){
		global $global_user_id;
		global $lang;

		$sql = sisplet_query("SELECT * FROM srv_gdpr_user WHERE usr_id='".$global_user_id."'");
		$row = mysqli_fetch_array($sql);

		if($row['country'] == ''){
			$row['country'] = ($lang['id'] == '1') ? 'Slovenija' : 'Slovenia';
		}

		return $row;
	}
	
	// Preverimo ce je uporabnik izpolnil gdpr profil
	private function checkUserSettings(){
		global $global_user_id;
		global $lang;

		$sql = sisplet_query("SELECT * FROM srv_gdpr_user WHERE usr_id='".$global_user_id."'");
		$row = mysqli_fetch_array($sql);

		if($row['firstname'] == '' || $row['lastname'] == '' || $row['email'] == '')
			return false;
        
        // Ce ima dpo so obvezni ime, priimek in posta
		if(($row['type'] == '1' || $row['has_dpo'] == '1') && ($row['dpo_firstname'] == '' || $row['dpo_lastname'] == '' || $row['dpo_email'] == ''))
            return false;
        
        // Za podjetje sta obvezni ime in maticna
        if($row['type'] == '1' && ($row['organization'] == '' || $row['organization_maticna'] == ''))
			return false;

		return true;
	}

	// Pridobimo vse gdpr nastavitve za anketo
	public static function getSurveySettings($ank_id){

		$sql = sisplet_query("SELECT * FROM srv_gdpr_anketa WHERE ank_id='".$ank_id."'");

		if(mysqli_num_rows($sql) > 0){
			$row = mysqli_fetch_array($sql);
			return $row;
		}
		else
			return 0;
	}

	// Vrne text za gdpr preduvod glede na to kaj je oznaceno da se zbira
	public static function getSurveyIntro($ank_id){
		global $lang;
		global $site_url;
		
		// Poseben GDPR text za gorenje
		if (Common::checkModule('gorenje')){
		
			$naslov = '<p>'.$lang['gorenje_gdpr_1_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.sprintf($lang['gorenje_gdpr_1_1'], $site_url, $site_url).'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_1_2'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_2_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_2'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_3_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_3'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_4_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_4'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_5_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_5'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_6_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_6'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_7_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_7'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_8_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_8'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_9_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_9'].'</p>';

			$naslov .= '<br />';

			$naslov .= '<p>'.$lang['gorenje_gdpr_10_naslov'].'</p>';
			$naslov .= '<p style="font-weight:normal; margin:10px 10px 10px 0;">'.$lang['gorenje_gdpr_10'].'</p>';
		}
		else{
			$user_settings = GDPR::getSurveySettings($ank_id);

            $translation = ($lang['id'] == '1') ? '_slo' : '_eng';

			$naslov = '<h3 style="margin-top: 0;">'.$lang['srv_gdpr_intro_title'].'</h3>';
			
			$naslov .= '<p>'.$lang['srv_gdpr_intro'].':</p>';
			$naslov .= '<ul>';
			if($user_settings['name'])
				$naslov .= '<li>'.$lang['srv_gdpr_intro_name'].'</li>';
			if($user_settings['email'])
				$naslov .= '<li>'.$lang['srv_gdpr_intro_email'].'</li>';
			if($user_settings['location'])
				$naslov .= '<li>'.$lang['srv_gdpr_intro_location'].'</li>';
			if($user_settings['phone'])
				$naslov .= '<li>'.$lang['srv_gdpr_intro_phone'].'</li>';
			if($user_settings['web'])
				$naslov .= '<li>'.$lang['srv_gdpr_intro_web'].'</li>';
			if($user_settings['other'])
				$naslov .= '<li>'.$lang['srv_gdpr_intro_other'].' - '.$user_settings['other_text'.$translation].'</li>';
			$naslov .= '</ul>';

			$naslov .= '<p>'.$lang['srv_gdpr_intro2'];
			$naslov .= ' '.$lang['srv_gdpr_intro3'].'</p>';
		}

		$naslov .= '<br />';	
		$naslov .= '<p>'.$lang['srv_gdpr_intro4'].'</p>';
		
		return $naslov;
	}
	
	// Preverimo ce je anketa potrjena s strani urednika, da je gdpr
	public static function isGDPRSurvey($ank_id){

		$sql = sisplet_query("SELECT * FROM srv_gdpr_anketa WHERE ank_id='".$ank_id."'");

		if(mysqli_num_rows($sql) > 0){
			return 1;
		}
		else
			return 0;
	}
	
	// Preverimo ce je anketa gdpr in ima vklopljen gdpr 1ka template uvod
	public static function isGDPRSurveyTemplate($ank_id){

		$sql = sisplet_query("SELECT * FROM srv_gdpr_anketa WHERE ank_id='".$ank_id."' AND 1ka_template='1'");

		if(mysqli_num_rows($sql) > 0){
			return 1;
		}
		else
			return 0;
	}


	/*
	 *	Preverimo ce ima anketa kaksne nastavitve, ki lahko padejo pod gdpr
	 *		- preverjamo imena spremenljivk "ime", "priimek", "firstname", "lastname", "email" (za tipe text) in tip vprasanja lokacija
	 *		- preverjamo ce ima vklopljena vabila
	 *		- preverjamo ce je tel. anketa
	 */
	public function potentialGDPRSurvey($ank_id){

		$gdpr = 0;

		// Preverimo ce obstaja kaksno vprasanje za ime, priimek, email, lokacijo
		$gdpr_questions = $this->getGDPRSurveyQuestions($ank_id);
		if(count($gdpr_questions) > 0)
			$gdpr = 1;

		// Preverimo ce ima vklopljena vabila
		if($this->checkSurveyInvitations($ank_id))
			$gdpr = 1;

		// Preverimo ce je telefonska anketa
		if($this->checkSurveyTelephone($ank_id))
			$gdpr = 1;

		return $gdpr;
	}

	// Preverimo ce obstaja v anketi kaksno vprasanje za ime, priimek, email, lokacijo - vrnemo array vprasanj, ki so problematicna
	private function getGDPRSurveyQuestions($ank_id){

		$gdpr_questions = array();

		// Loop cez vsa vprasanja
		$sql = sisplet_query("SELECT s.id, s.variable, s.variable_custom, s.sistem, s.tip
								FROM srv_spremenljivka s, srv_grupa g
								WHERE s.gru_id=g.id AND g.ank_id='".$ank_id."'
									AND ((s.variable IN ('ime', 'priimek', 'email', 'firstname', 'lastname') AND s.tip='21' /*AND s.sistem='1'*/) OR s.tip='26')");
		while($row = mysqli_fetch_array($sql)){
			$gdpr_questions[] = $row;
		}

		return $gdpr_questions;
	}

	// Preverimo ce ima anketa vklopljena email vabila
	private function checkSurveyInvitations($ank_id){

		$gdpr_email = SurveyInfo::getInstance()->checkSurveyModule('email');

		return $gdpr_email;
	}

	// Preverimo ce je telefonska anketa
	private function checkSurveyTelephone($ank_id){

		$gdpr_phone = SurveyInfo::getInstance()->checkSurveyModule('phone');

		return $gdpr_phone;
    }


	// Poskrbi za vse potrebno ko respondent zahteva izbris oz. vpogled v podatke
	public function sendGDPRRequest($request_data){
		global $lang;
        global $gdpr_admin_email;
        global $app_settings;
		
		$errors = array();


		// Natavimo angleski jezik
		if((!empty($_POST['drupal_lang']) && $_POST['drupal_lang'] == 2) || (!empty($_POST['lang_id']) && $_POST['lang_id'] == 2)){
			$file = '../lang/2.php';
			include($file);
		}

		
		// Preverimo email
		if(!isset($request_data['email']) || $request_data['email'] == '')
			$errors['email'] = '1';
		elseif(!validEmail($request_data['email']))
			$errors['email'] = $lang['srv_remind_email_hard'];
		else
			$email = $request_data['email'];
			
		// Preverimo naslov ankete - naslov ni obvezen
		$survey_name = (isset($request_data['srv-name'])) ? $request_data['srv-name'] : '';
		
		// Preverimo url ankete
		if(!isset($request_data['srv-url']) || $request_data['srv-url'] == '')
				$errors['srv-url'] = '1';
		else{	
			$survey_url = $request_data['srv-url'];
			
			// Preverimo url ankete in pridobimo podatke za anketo (avtor, id...)
			$survey_data = self::getSurveyFromURL($survey_url);

			if(!$survey_data || empty($survey_data)) {
               if((!empty($_POST['drupal_lang']) && $_POST['drupal_lang'] == 2) || (!empty($_POST['lang_id']) && $_POST['lang_id'] == 2)) {
                   $errors['srv-url'] = 'Invalid survey URL. Enter the correct URL for the 1KA survey. If you have any problems, please contact 1KA helpdesk (<a href="mailto:help@1ka.si?subject=GDPR">help@1ka.si</a>).';
               }
			   else{
                   $errors['srv-url'] = 'Nepravilen URL ankete. Vpišite pravilen URL 1KA ankete. V primeru težav kontaktirajte Center za pomoč uporabnikom 1KA (<a href="mailto:help@1ka.si?subject=GDPR">help@1ka.si</a>).';
               }
            }
		}
			
		// Preverimo ce imamo action
		if(!isset($request_data['gdpr-action']))
			$errors['gdpr-action'] = '1';
		else{
			$action = $request_data['gdpr-action'];
			
			// Nastavimo jezik vmesnika
			if(!empty($_POST['drupal_lang']))
				$jezik = $_POST['drupal_lang'];
			elseif(!empty($_POST['lang_id']))
				$jezik = $_POST['lang_id'];
			else
				$jezik = $survey_data['usr_lang'];

			
			if($jezik == '2'){
				if($action == '1')
					$action_text = '<b>Delete</b> personal and survey data for a specific survey.';
				elseif($action == '2')
					$action_text = '<b>Gain insight</b> into personal and survey data for a specific survey.';
				elseif($action == '3')
					$action_text = '<b>Change</b> personal data in a specific survey.';
				elseif($action == '4')
					$action_text = '<b>Transmission</b> of personal data from a specific survey.';
				elseif($action == '5')
					$action_text = '<b>Restriction</b> of processing of personal data in a specific survey.';
				elseif($action == '6')
					$action_text = '<b>Withdrawal of consent</b> of processing of personal data in a specific survey.';
				else
					$errors['gdpr-action'] = '1';
			}
			else{
				if($action == '1')
					$action_text = '<b>izbris</b> osebnih in anketnih podatkov iz omenjene ankete.';
				elseif($action == '2')
					$action_text = '<b>vpogled</b> v osebne in anketne podatke iz omenjene ankete.';
				elseif($action == '3')
					$action_text = '<b>spremembo</b> osebnih in anketnih podatkov iz omenjene ankete.';
				elseif($action == '4')
					$action_text = '<b>Prenos</b> osebnih podatkov iz omenjene ankete.';
				elseif($action == '5')
					$action_text = '<b>Omejitev obdelave</b> osebnih podatkov v omenjeni anketi.';
				elseif($action == '6')
					$action_text = '<b>Preklic privolitve v obdelavo</b> osebnih podatkov v omenjeni anketi.';
				else
					$errors['gdpr-action'] = '1';
			}			
		}

		// Preverimo opis
		//$note = (isset($request_data['gdpr-note'])) ? $request_data['gdpr-note'] : '';
		if(!isset($request_data['gdpr-note']) || $request_data['gdpr-note'] == '')
			$errors['gdpr-note'] = '1';
		else
			$note = $request_data['gdpr-note'];
		
		
		// Ce imamo vse potrebne podatke posredujemo zahtevo
		if(empty($errors)){
			
			// Zabelezimo zahtevo v bazo
			$sql = sisplet_query("INSERT INTO srv_gdpr_requests
										(usr_id, ank_id, email, url, datum, text, type)
									VALUES
										('".$survey_data['usr_id']."', '".$survey_data['ank_id']."', '".$email."', '".$survey_url."', NOW(), '".$note."', '".$action."')");
			
			// Nastavimo podatke maila (text)
			// ANG
			if($jezik == '2'){
				$subject = 'Request for deletion/insight or change of personal survey data';
				
				$content = 'Dear 1KA user,<br />';	
				$content .= '<p>As an author of the survey <b>'.$survey_data['title'].' ('.$survey_data['url'].')</b>, in which you collected personal data (GDPR), we would like to inform you that the respondent with e-mail '.$email.' submitted a request for:<br />';	
				$content .= '&nbsp;&nbsp;&nbsp;- '.$action_text.'</p>';
	
				$content .= '<p>';
				$content .= 'Respondent’s email:<br /><b>'.$email.'</b><br /><br />';
				$content .= 'Survey URL:<br /><b>'.$survey_url.'</b><br /><br />';
				$content .= 'The submitted request relates to the following personal data:<br /><b>'.$note.'</b>';
				$content .= '</p>';	
				
				$content .= '<p>Please process the request within <b>one month</b> and inform the respondent to the above email address of the (<a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0679" target="_blank">Article 19 of the GDPR Regulation</a>).</p>';	
				
				$content .= '<p>If you do not execute the request after one month, we will notify you again. If the request is not executed, we reserve the right to delete this survey.</p>';	
                
                // Podpis
                $signature = Common::getEmailSignature();
                $content .= $signature;
			}
			// SLO
			else{
				$subject = 'Obvestilo o zahtevi za izbris/vpogled ali spremembo osebnih oziroma anketnih podatkov';
				
				$content = 'Pozdravljeni,<br />';
				$content .= '<p>Kot avtorja ankete <b>'.$survey_data['title'].' ('.$survey_data['url'].')</b>, v kateri ste zbirali osebne podatke (GDPR), vas želimo obvestiti, da je respondent z elektronskim naslovom '.$email.' oddal prošnjo za:<br />';	
				$content .= '&nbsp;&nbsp;&nbsp;- '.$action_text.'</p>';
	
				$content .= '<p>';
				$content .= 'Email respondenta:<br /><b>'.$email.'</b><br /><br />';
				$content .= 'URL ankete:<br /><b>'.$survey_url.'</b><br /><br />';
				$content .= 'Oddana prošnja se nanaša na naslednje osebne podatke:<br /><b>'.$note.'</b>';
				$content .= '</p>';	
				
				$content .= '<p>Prosimo, da <b>v roku enega meseca</b> izvršite zahtevo in o tem obvestite respondenta na zgoraj navedeni elektronski naslov respondenta (<a href="http://eur-lex.europa.eu/legal-content/SL/TXT/?uri=uriserv:OJ.L_.2016.119.01.0001.01.SLV&toc=OJ:L:2016:119:FULL" target="_blank">Člen 19 uredbe GDPR</a>).</p>';	
				
				$content .= '<p>V primeru, da tega po enem mesecu ne boste izvršili, vas bomo ponovno obvestili. Če se zahteva ne izvrši, si pridružujemo pravico, da anketo izbrišemo.</p>';	

                // Podpis
                $signature = Common::getEmailSignature();
                $content .= $signature;
			}
								
			// Posljemo mail avtorju ankete in na help@1ka.si za obvestilo adminu
			try{
				$MA = new MailAdapter();
				$MA->addRecipients($survey_data['author_email']);
				
				if(Common::checkModule('gorenje')){
					//$MA->addRecipients('dusan.rutnik@gorenje.com');
					$MA->addRecipients('gdpr@gorenje.com');
				}
				elseif(isset($gdpr_admin_email) && $gdpr_admin_email != ''){
					$MA->addRecipients($gdpr_admin_email);
				}
				else{
					$MA->addRecipients('help@1ka.si');
					$MA->addRecipients('enklikanketa@gmail.com');
				}

				$resultX = $MA->sendMail($content, $subject);
		   }
		   catch (Exception $e){
		   }			
			
			// Vrnemo vse ok
        if($jezik == '2'){
            $success_text = 'Your request for the deletion, change or insight into your personal information for a particular survey is submitted to 
                            the author of the survey. The author of the survey must, within a month since you submitted the request, execute your 
                            request and inform you about it. If the author fails to do so within 30 days, we will delete the survey, including your information.';
        }else {
            $success_text = 'Prošnja za izbris, spremembo ali vpogled do vaših osebnih podatkov iz določene ankete je posredovana avtorju ankete. 
                            Avtor ankete mora v roku meseca dni od oddane zahteve urediti vašo zahtevo in vas o tem obvestiti. 
                            Če v roku 30 dni avtor tega ne izvede, bomo anketo izbrisali, vključno z vašimi podatki.';
        }

			$response = json_encode(array('success' => $success_text), true);
		}
		else{
			// Vrnemo errorje
			//$response = json_encode(array('error' => $errors), true);   
			$response = json_encode(array('error' => $errors, 'posted' => $request_data), true);   
		}
		
		echo $response;
	}
	
	// Poiscemo anketo na podlagi vnesenega url-ja
	private function getSurveyFromURL($url){
		global $site_url;	
		
		$data = array();
		
		$url_data = parse_url($url);
		$path = $url_data['path'];
		$host = $url_data['host'];
		
		// Preverimo ce gre za pravo domeno 
		if($host == parse_url($site_url, PHP_URL_HOST)){
			
			$arr = explode("/", $path);
			
			// Pridobimo id ankete iz url-ja
			if($arr[1] == 'a'){
				$data['ank_id'] = $arr[2];
			}
			else{
				// Preverimo ce imamo mogoce lep url
				$nice_url = $arr[1];
				
				$sqlN = sisplet_query("SELECT ank_id, link FROM srv_nice_links WHERE link='".$nice_url."'");
				if(mysqli_num_rows($sqlN) > 0){
					$rowN = mysqli_fetch_array($sqlN);		
					$data['ank_id'] = $rowN['ank_id'];
				}
				else
					return false;
			}
		
			// Nastavimo url ankete
			$data['url'] = $site_url.'a/'.$data['ank_id'];
			
			// Pridobimo avtorja in naslov ankete
			$sql = sisplet_query("SELECT a.naslov, u.id, u.lang, u.email
									FROM srv_anketa a, users u
									WHERE a.id='".$data['ank_id']."' AND u.id=a.insert_uid");
			if(mysqli_num_rows($sql) == 1){
				$row = mysqli_fetch_array($sql);
				
				$data['title'] = $row['naslov'];
				$data['usr_id'] = $row['id'];
				$data['usr_lang'] = $row['lang'];
				$data['author_email'] = $row['email'];
			}
			else
				return false;
		}
		else
			return false;
		
		if(isset($data['ank_id']) && isset($data['usr_id']) && isset($data['title']) && isset($data['url']) && isset($data['author_email']))
			return $data;
		else
			return false;
	}
	
	// Prikaze obrazec za zahteve za izbris (v simple frontend)
	public static function displayGDPRRequestForm($status=array()){
		global $lang;
		global $site_url;
		
		// Uspesno poslan zahtevek
		if(isset($status['success'])){
			echo $status['success'];			
		}
		else{	
			// Ce imamo errorje
			$error = (isset($status['error'])) ? $status['error'] : array();
			
			// Kar smo predhodno poslali
			$posted = (isset($status['posted'])) ? $status['posted'] : array();
			
			// Nastavimo se jezik ob napakah
			if(isset($posted['lang_id'])){
				$file = '../../lang/'.$posted['lang_id'].'.php';
				include($file);
			}
			
			echo '		<h1>'.$lang['srv_gdpr_drupal_title'].'</h1>';
			
			echo '		<p>'.$lang['srv_gdpr_drupal_intro1'].'</p>';
			echo '		<p>'.$lang['srv_gdpr_drupal_intro2'].'</p>';
			
			echo '		<form name="gdpr" id="gdpr" action="'.$site_url.'utils/gdpr_request.php" method="post">';
			
			// Jezik vmesnika
			echo '			<input id="lang_id" name="lang_id" value="'.$lang['id'].'" type="hidden">';
			
			// Email
			echo '			<div class="form_row '.(isset($error['email']) ? ' red' : '').'"><div class="label"><label for="email">'.$lang['srv_gdpr_drupal_field_email'].':</label></div>';
			echo '			<input class="regfield" id="email" name="email" value="'.(isset($posted['email']) && !isset($error['email']) ? $posted['email'] : '').'" placeholder="'.$lang['email'].'" type="text">';
			if(isset($error['email']) && $error['email'] != '1')
				echo '<span class="spaceLeft">'.$error['email'].'</span>';
			echo '			</div>';
			
			// Ime ankete
			echo '			<div class="form_row '.(isset($error['srv-name']) ? ' red' : '').'"><div class="label"><label for="srv-name">'.$lang['srv_gdpr_drupal_field_srv-name'].':</label></div>';
			echo '			<input class="regfield" id="srv-name" name="srv-name" value="'.(isset($posted['srv-name']) && !isset($error['srv-name']) ? $posted['srv-name'] : '').'" placeholder="'.$lang['srv_gdpr_drupal_field_srv-name'].'" type="text">';
			if(isset($error['srv-name']) && $error['srv-name'] != '1')
				echo '<span class="spaceLeft">'.$error['srv-name'].'</span>';
			echo '			</div>';
			
			// URL ankete
			echo '			<div class="form_row '.(isset($error['srv-url']) ? ' red' : '').'"><div class="label"><label for="srv-url">'.$lang['srv_gdpr_drupal_field_srv-url'].':</label></div>';
			echo '			<input class="regfield" id="srv-url" name="srv-url" value="'.(isset($posted['srv-url']) && !isset($error['srv-url']) ? $posted['srv-url'] : '').'" placeholder="'.$lang['srv_gdpr_drupal_field_srv-url'].'" type="text">';
			if(isset($error['srv-url']) && $error['srv-url'] != '1')
				echo '<span class="spaceLeft">'.$error['srv-url'].'</span>';
			echo '			</div>';
				
			echo '			<br />';
					
			// Tip zahteve
			echo '			<p>'.$lang['srv_gdpr_drupal_q1_title'].'</p>';
			
			echo '			<div class="form_row '.(isset($error['gdpr-action']) ? ' red' : '').'"><label for="gdpr-action_1">';
			echo '				<input type="radio" id="gdpr-action_1" name="gdpr-action" value="1" '.(isset($posted['gdpr-action']) && $posted['gdpr-action'] == '1' ? ' checked="checked"' : '').'> '.$lang['srv_gdpr_drupal_q1_answer1'];	
			echo '			</label></div>';    
            echo '			<div class="form_row '.(isset($error['gdpr-action']) ? ' red' : '').'"><label for="gdpr-action_2">';
			echo '				<input type="radio" id="gdpr-action_2" name="gdpr-action" value="2" '.(isset($posted['gdpr-action']) && $posted['gdpr-action'] == '2' ? ' checked="checked"' : '').'> '.$lang['srv_gdpr_drupal_q1_answer2'];	
			echo '			</label></div>';        
            echo '			<div class="form_row '.(isset($error['gdpr-action']) ? ' red' : '').'"><label for="gdpr-action_3">';
			echo '				<input type="radio" id="gdpr-action_3" name="gdpr-action" value="3" '.(isset($posted['gdpr-action']) && $posted['gdpr-action'] == '3' ? ' checked="checked"' : '').'> '.$lang['srv_gdpr_drupal_q1_answer3'];	
            echo '			</label></div>';           
            echo '			<div class="form_row '.(isset($error['gdpr-action']) ? ' red' : '').'"><label for="gdpr-action_4">';
			echo '				<input type="radio" id="gdpr-action_4" name="gdpr-action" value="4" '.(isset($posted['gdpr-action']) && $posted['gdpr-action'] == '4' ? ' checked="checked"' : '').'> '.$lang['srv_gdpr_drupal_q1_answer4'];	
			echo '			</label></div>';
            echo '			<div class="form_row '.(isset($error['gdpr-action']) ? ' red' : '').'"><label for="gdpr-action_5">';
			echo '				<input type="radio" id="gdpr-action_5" name="gdpr-action" value="5" '.(isset($posted['gdpr-action']) && $posted['gdpr-action'] == '5' ? ' checked="checked"' : '').'> '.$lang['srv_gdpr_drupal_q1_answer5'];	
            echo '			</label></div>';
            echo '			<div class="form_row '.(isset($error['gdpr-action']) ? ' red' : '').'"><label for="gdpr-action_6">';
			echo '				<input type="radio" id="gdpr-action_6" name="gdpr-action" value="6" '.(isset($posted['gdpr-action']) && $posted['gdpr-action'] == '6' ? ' checked="checked"' : '').'> '.$lang['srv_gdpr_drupal_q1_answer6'];	
			echo '			</label></div>';
			
			echo '			<br />';
			
			// Opomba
			echo '			<p '.(isset($error['gdpr-note']) ? ' class="red"' : '').'>'.$lang['srv_gdpr_drupal_q2_note'].'</p>';
			echo '			<textarea id="gdpr-note" name="gdpr-note" value="" '.(isset($error['gdpr-note']) ? ' class="red"' : '').'>'.(isset($posted['gdpr-note']) ? $posted['gdpr-note'] : '').'</textarea>';

			echo '			<br /><br />';
							
			
			// Poslji prosnjo
			echo '			<p>'.$lang['srv_gdpr_drupal_end'].'</p>';
			//echo '			<input name="submit" value="'.$lang['srv_potrdi'].'" class="regfield" type="submit"><br />';
			echo '			<input name="submit" value="'.$lang['srv_potrdi'].'" class="regfield" type="button" onClick="sendGDPRRequest();"><br />';
			
			echo '		</form>';
		}
    }
    

    // Vrnemo celoten gdpr text za respondenta (pravice...)  v obliki array-a
    public static function getGDPRInfoArray($ank_id, $language_id=''){
        global $global_user_id;
        global $lang;

        $gdpr_settings = self::getUserSettings();
        $gdpr_survey_settings = self::getSurveySettings($ank_id);

        // Force language
        $language_id_bck = '';
        if($language_id != '' && $lang['id'] != $language_id){

            // Shranimo star jezik da lahko preklopimo nazaj
            $language_id_bck = $lang['id'];

            $file = '../../lang/'.$language_id.'.php';
            include($file);

            $translation = ($language_id == '1') ? '_slo' : '_eng';
        }
        else{
            $translation = ($lang['id'] == '1') ? '_slo' : '_eng';
        }

        $result = array();        

        // OSEBNI PODATKI
        $result[0]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text1_1'];
        $result[0]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text1_2'];

        // Avtor raziskave
        $research_author = self::getResearchAuthor($ank_id, $gdpr_settings, $gdpr_survey_settings);
        if($research_author != '')
            $result[0]['text'][1] = $lang['srv_gdpr_survey_gdpr_about_text1_3'].' <strong>'.$research_author.'</strong>';
        
        $result[0]['text'][2] = $lang['srv_gdpr_survey_gdpr_about_text1_4'].':';
        $temp_text = '';
        if($gdpr_survey_settings['name'])
            $temp_text .= $lang['srv_gdpr_intro_name'].', ';
        if($gdpr_survey_settings['email'])
            $temp_text .= $lang['srv_gdpr_intro_email'].', ';
        if($gdpr_survey_settings['location'])
            $temp_text .= $lang['srv_gdpr_intro_location'].', ';
        if($gdpr_survey_settings['phone'])
            $temp_text .= $lang['srv_gdpr_intro_phone'].', ';
        if($gdpr_survey_settings['web'])
            $temp_text .= $lang['srv_gdpr_intro_web'].', ';
        if($gdpr_survey_settings['other'])
            $temp_text .= $lang['srv_gdpr_intro_other'].' - '.$gdpr_survey_settings['other_text'.$translation].', ';
        
        $result[0]['text'][2] .= ' <strong>'.substr(ucfirst(strtolower($temp_text)), 0,-2).'</strong>'; 

        
        // UPORABA IN HRAMBA PODATKOV
        $result[1]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text2_1'];
        $result[1]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text2_2'];
        $result[1]['text'][1] = $lang['srv_gdpr_survey_gdpr_about_text2_3'];
        $result[1]['text'][2] = $lang['srv_gdpr_survey_gdpr_about_text2_4'];
		
		if($gdpr_survey_settings['expire'] == '1' && $gdpr_survey_settings['expire_text'.$translation] != '')
            $result[1]['text'][2] .= ' <strong>'.$gdpr_survey_settings['expire_text'.$translation].'</strong>.';
        else
            $result[1]['text'][2] .= ' <strong>'.$lang['srv_gdpr_survey_gdpr_about_text2_5'].'</strong>';


        // UPORABNIKI OSEBNI PODATKOV
        $result[2]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text3_1'];
        $result[2]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text3_2'];
        
        if($gdpr_survey_settings['other_users'] == '1' && $gdpr_survey_settings['other_users_text'.$translation] != '')
            $result[2]['text'][1] = $lang['srv_gdpr_survey_gdpr_about_text3_32'].' <strong>'.$gdpr_survey_settings['other_users_text'.$translation].'</strong>. ';
        else
            $result[2]['text'][1] = '<strong>'.$lang['srv_gdpr_survey_gdpr_about_text3_31'].'.</strong>';

        $result[2]['text'][2] = $lang['srv_gdpr_survey_gdpr_about_text3_4'];
    

        // IZNOS PODATKOV V TRETJE DRŽAVE
        $result[3]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text4_1'];

        if($gdpr_survey_settings['export'] == '1'){
            $result[3]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text4_22'].' '.$lang['srv_gdpr_survey_gdpr_about_text4_22_2'].' <strong>'.$gdpr_survey_settings['export_country'.$translation].'</strong> '.$lang['srv_gdpr_survey_gdpr_about_text4_22_3'];
            $result[3]['text'][1] = $lang['srv_gdpr_survey_gdpr_about_text4_22_4'].' <strong>'.$gdpr_survey_settings['export_user'.$translation].'</strong>';
            $result[3]['text'][1] = ' '.$lang['srv_gdpr_survey_gdpr_about_text4_22_5'].' <strong>'.$gdpr_survey_settings['export_legal'.$translation].'</strong>.';
        }
        else{
            $result[3]['text'][0] = '<strong>'.$lang['srv_gdpr_survey_gdpr_about_text4_21'].'</strong>';
        }
   

        // PODATKI O POOBLAŠČENI OSEBI ZA VARSTVO OSEBNIH PODATKOV
        $result[4]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text5_1'];
        
        // DPO
        if($gdpr_survey_settings['authorized'] == ''){
            
            // Zasebnik brez DPO
            if($gdpr_settings['type'] == '0' && $gdpr_settings['has_dpo'] == '0'){

                // DPO mail je enak navadnemu mailu, ki ga je vnesel v splosnih nastavitvah
                if($gdpr_settings['email'] != ''){
                    $gdpr_authorized = $gdpr_settings['email'];
                }
                // Ce ga ni vnesel, je DPO mail enak mailu avtorja ankete
                else{
                    $gdpr_authorized = User::getInstance()->primaryEmail();
                }
            }
            // Zasebnik z DPO ali organizacija
            else{

                // DPO mail je enak DPO mailu, ki ga je vnesel v splosnih nastavitvah
                if($gdpr_settings['dpo_email'] != ''){
                    $gdpr_authorized = $gdpr_settings['dpo_email'];
                }
                // Ce ga ni vnesel, je DPO mail enak splosnemu mailu oz. mailu avtorja ankete
                else{
                    if($gdpr_settings['email'] != ''){
                        $gdpr_authorized = $gdpr_settings['email'];
                    }
                    else{
                        $gdpr_authorized = User::getInstance()->primaryEmail();
                    }
                }
            }
        }
        else{
            $gdpr_authorized = $gdpr_survey_settings['authorized'];
        }

        // Kontaktni email
        if($gdpr_survey_settings['contact_email'] == ''){

            $user_settings = self::getUserSettings();

            // Kontaktni mail je enak mailu, ki ga je vnesel v splosnih nastavitvah
            if($user_settings['email'] != ''){
                $gdpr_contact_email = $user_settings['email'];
            }
            // Ce ga ni vnesel, je kontaktni mail enak mailu avtorja ankete
            else{
                $gdpr_contact_email = User::getInstance()->primaryEmail();
            }
        }
        else{
            $gdpr_contact_email = $gdpr_survey_settings['contact_email'];
        }

        $result[4]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text5_2'].' <strong>'.$gdpr_authorized.'</strong>';
        
        // Ce mail ni isti izpisemo se avtorja
        if($gdpr_authorized != $gdpr_contact_email)
            $result[4]['text'][1] = $lang['srv_gdpr_survey_gdpr_about_text5_2_2'].' <strong>'.$gdpr_contact_email.'</strong>';

        // ZAVAROVANJE PODATKOV
        $result[5]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text6_1'];
        $result[5]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text6_2'];


        // IZBRIS, SPREMEMBA ALI VPOGLED DO OSEBNIH ANKETNIH PODATKOV
        $result[6]['heading'] = $lang['srv_gdpr_survey_gdpr_about_text7_1'];
        $result[6]['text'][0] = $lang['srv_gdpr_survey_gdpr_about_text7_2'];
        $result[6]['text'][1] = $lang['srv_gdpr_survey_gdpr_about_text7_3'];
        
        if($gdpr_survey_settings['contact_email'] != ''){
            $result[6]['text'][1] .= ' <strong>'.$gdpr_survey_settings['contact_email'].'</strong>. ';
        }
        elseif($gdpr_settings['email'] != ''){
            $result[6]['text'][1] .= ' <strong>'.$gdpr_settings['email'].'</strong>. ';
        }
        else{
            $sql = sisplet_query("SELECT email FROM users WHERE id = '$global_user_id'");
            $row = mysqli_fetch_array($sql); 
            $result[6]['text'][1] .= ' '.$row['email'].'. ';
        }

        $result[6]['text'][1] .= $lang['srv_gdpr_survey_gdpr_about_text7_3_2'];

        $result[6]['text'][2] = $lang['srv_gdpr_survey_gdpr_about_text7_4'];


        // OPOMBA
        if($gdpr_survey_settings['note'.$translation] != ''){
            $result[7]['heading'] = $lang['note'];
            $result[7]['text'][0] = '<strong>'.$gdpr_survey_settings['note'.$translation].'</strong>';
        }


        // Preklopimo nazaj jezik
        if($language_id_bck != '' && $language_id_bck != $lang['id']){
            $file = '../../lang/'.$language_id_bck.'.php';
            include($file);
        }


        return $result;
    }

    // Vrnemo celoten gdpr text za evidencov obliki array-a
    public static function getGDPREvidencaArray($ank_id){
        global $global_user_id;
        global $lang;

        $gdpr_settings = self::getUserSettings();
        $gdpr_survey_settings = self::getSurveySettings($ank_id);

        $translation = ($lang['id'] == '1') ? '_slo' : '_eng';

        $result = array(); 

        $result[0]['heading'] = 'I. '.$lang['srv_gdpr_survey_gdpr_evidenca_text1'];
        $result[0]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text1_1'];


        $result[1]['heading'] = 'II. '.$lang['srv_gdpr_survey_gdpr_evidenca_text2'];
        
        if($gdpr_settings['type'] == '1'){
            $result[1]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text2_1'].': ';
            $result[1]['text'][0] .= '<strong>'.$gdpr_settings['organization'].'</strong>';

            $result[1]['text'][1] = $lang['srv_gdpr_survey_gdpr_evidenca_text2_2'].': ';
            $result[1]['text'][1] .= '<strong>'.$gdpr_settings['address'].'</strong>';
            
            $result[1]['text'][2] = $lang['srv_gdpr_survey_gdpr_evidenca_text2_3'].': ';
            $result[1]['text'][2] .= '<strong>'.$gdpr_settings['organization_maticna'].'</strong>';
        }
        else{
            $result[1]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text2_1'].': ';
            $result[1]['text'][0] .= '<strong>'.$gdpr_settings['firstname'].' '.$gdpr_settings['lastname'].'</strong>';

            $result[1]['text'][1] = $lang['srv_gdpr_survey_gdpr_evidenca_text2_2'].': ';
            $result[1]['text'][1] .= '<strong>'.$gdpr_settings['address'].'</strong>';
        }        


        $result[2]['heading'] = 'III.'.$lang['srv_gdpr_survey_gdpr_evidenca_text3'];
        $result[2]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text3_1'];


        $result[3]['heading'] = 'IV. '.$lang['srv_gdpr_survey_gdpr_evidenca_text4'];
        $result[3]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text4_1'];


        $result[4]['heading'] = 'V. '.$lang['srv_gdpr_survey_gdpr_evidenca_text5'];
        $temp_text = '';
        if($gdpr_survey_settings['name'])
            $temp_text .= $lang['srv_gdpr_intro_name'].', ';
        if($gdpr_survey_settings['email'])
            $temp_text .= $lang['srv_gdpr_intro_email'].', ';
        if($gdpr_survey_settings['location'])
            $temp_text .= $lang['srv_gdpr_intro_location'].', ';
        if($gdpr_survey_settings['phone'])
            $temp_text .= $lang['srv_gdpr_intro_phone'].', ';
        if($gdpr_survey_settings['web'])
            $temp_text .= $lang['srv_gdpr_intro_web'].', ';
        if($gdpr_survey_settings['other'])
            $temp_text .= $lang['srv_gdpr_intro_other'].' - '.$gdpr_survey_settings['other_text'.$translation].', ';
        
        $result[4]['text'][0] = '<strong>'.substr(ucfirst(strtolower($temp_text)), 0,-2).'</strong>';


        $result[5]['heading'] = 'VI. '.$lang['srv_gdpr_survey_gdpr_evidenca_text6'];
        $result[5]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text6_1'];
        $result[5]['text'][1] = $lang['srv_gdpr_survey_gdpr_evidenca_text6_2'];


        $result[6]['heading'] = 'VII. '.$lang['srv_gdpr_survey_gdpr_evidenca_text7'];
        
        $result[6]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text7_1'].' ';
        if($gdpr_survey_settings['expire'] != '1')
            $result[6]['text'][0] .= '<strong>'.$lang['srv_gdpr_survey_gdpr_evidenca_text7_2'].'</strong>';
        else
            $result[6]['text'][0] .= '<strong>'.$gdpr_survey_settings['expire_text'.$translation].'</strong>.';


        $result[7]['heading'] = 'VIII. '.$lang['srv_gdpr_survey_gdpr_evidenca_text8'];
        $result[7]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text8_1'];
        $result[7]['text'][1] = $lang['srv_gdpr_survey_gdpr_evidenca_text8_2'];

        if($gdpr_survey_settings['other_users'] == '1')
            $result[7]['text'][2] = '<strong>'.ucfirst($gdpr_survey_settings['other_users_text'.$translation]).'</strong>';


        $result[8]['heading'] = 'IX. '.$lang['srv_gdpr_survey_gdpr_evidenca_text9'];
        
        if($gdpr_survey_settings['export'] == '1'){
            $result[8]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text9_22'].' ';
            $result[8]['text'][0] .= '<strong>'.$gdpr_survey_settings['export_country'.$translation].'</strong>';
            $result[8]['text'][0] .= $lang['srv_gdpr_survey_gdpr_evidenca_text9_23'].' ';
            $result[8]['text'][0] .= '<strong>'.$gdpr_survey_settings['export_user'.$translation].'</strong>';
            $result[8]['text'][0] .= $lang['srv_gdpr_survey_gdpr_evidenca_text9_24'].' ';
            $result[8]['text'][0] .= '<strong>'.$gdpr_survey_settings['export_legal'.$translation].'</strong>.';
        }
        else{
            $result[8]['text'][0] = '<strong>'.$lang['srv_gdpr_survey_gdpr_evidenca_text9_21'].'</strong>';
        }


        $result[9]['heading'] = 'X. '.$lang['srv_gdpr_survey_gdpr_evidenca_text10'];
        $result[9]['text'][0] = $lang['srv_gdpr_survey_gdpr_evidenca_text10_1'];


        // OPOMBA
        if($gdpr_survey_settings['note'.$translation] != ''){
            $result[10]['heading'] = 'XI. '.$lang['note'];
            $result[10]['text'][0] = '<strong>'.$gdpr_survey_settings['note'.$translation].'</strong>.';
        }


        return $result;
    }

    // Pretvorimo array v text za info oz. evidenco (html popup, textarea)
    public static function getGDPRTextFromArray($text_array, $type='html'){

        // Dolocimo line break glede na tip (html, pdf ali textarea)
        if($type == 'textarea')
            $br = '&#13;&#10;';
        else
            $br = '<br />';

        // Loop po posameznih sklopih
        foreach($text_array as $sklop){

            // Naslov sklopa
            $text .= '<strong>'.$sklop['heading'].'</strong>'.$br;

            // Loop po posameznih vrsticah
            foreach($sklop['text'] as $vrstica){

                $text .= $br.$vrstica.$br;
            }

            $text .= $br.$br;
        }

        if($type == 'textarea'){
            $text = str_replace('<strong>', '', $text);
            $text = str_replace('</strong>', '', $text);
        }

        return $text;
    }


    // Pridobimo avtorja raziskave
	public static function getResearchAuthor($ank_id, $gdpr_settings, $gdpr_survey_settings){
        global $global_user_id;

        $author = '';

        // Email avtorja - najprej se pogleda ce je nastavljen GDPR na anketi, potem se povlece splosnega iz GDPR na koncu pa avtor maila
        if($gdpr_survey_settings['contact_email'] != ''){
            $author = ' '.$gdpr_survey_settings['contact_email'];
        }
        elseif($gdpr_settings['email'] != ''){
            $author = ' '.$gdpr_settings['email'];
        }
        else{
            $sql = sisplet_query("SELECT email FROM users WHERE id = '$global_user_id'");
            $row = mysqli_fetch_array($sql);
            
            $author = ' '.$row['email'];
        }

        // Podjetje ce je nastavljeno v GDPR nastavitvah
        if($gdpr_settings['type'] == '1' && $gdpr_settings['organization'] != ''){
            $author .= ' ('.$gdpr_settings['organization'].').';
        }
        else{
            $author .= '.';
        }

		return $author;
    }
	

	// Funkcije ajaxa
	public function ajax() {
		global $lang;
		global $global_user_id;
		global $site_url;

		if (isset ($_POST['ank_id']))
			$ank_id = $_POST['ank_id'];

		if (isset ($_POST['what']))
			$what = $_POST['what'];
		if (isset ($_POST['value']))
			$value = $_POST['value'];


		// Urejanje gdpr nastavitve za userja
		if($_GET['a'] == 'gdpr_edit_user'){

			$error = array();

			$firstname = isset($_POST['firstname']) ? $_POST['firstname'] : '';
			$lastname = isset($_POST['lastname']) ? $_POST['lastname'] : '';
			$email = isset($_POST['email']) ? $_POST['email'] : '';
			$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
			
			$type = isset($_POST['type']) ? $_POST['type'] : '0';
            
            $has_dpo = isset($_POST['has_dpo']) ? $_POST['has_dpo'] : '0';
			
			$organization = isset($_POST['organization']) ? $_POST['organization'] : '';
			$organization_maticna = isset($_POST['organization_maticna']) ? $_POST['organization_maticna'] : '';
			//$organization_davcna = isset($_POST['organization_davcna']) ? $_POST['organization_davcna'] : '';
			$dpo_firstname = isset($_POST['dpo_firstname']) ? $_POST['dpo_firstname'] : '';
			$dpo_lastname = isset($_POST['dpo_lastname']) ? $_POST['dpo_lastname'] : '';
			$dpo_email = isset($_POST['dpo_email']) ? $_POST['dpo_email'] : '';
			$dpo_phone = isset($_POST['dpo_phone']) ? $_POST['dpo_phone'] : '';
			
			$address = isset($_POST['address']) ? $_POST['address'] : '';
			$country = isset($_POST['country']) ? $_POST['country'] : '';

			// Dodatno preverimo ce gre za veljavna maila
			if($email != '' && !validEmail($email)){
				$email = '';
				$error['email'] = 1;
			}
			if($dpo_email != '' && !validEmail($dpo_email)){
				$dpo_email = '';
				$error['dpo_email'] = 1;
			}

			$sql = sisplet_query("INSERT INTO srv_gdpr_user
										(usr_id,
											type,
											has_dpo,
											organization,
											organization_maticna,
											dpo_firstname,
											dpo_lastname,
											dpo_email,
											dpo_phone,
											firstname,
											lastname,
											email,
											phone,
											address,
											country)
									VALUES
										('".$global_user_id."', 
											'".$type."', 
											'".$has_dpo."', 
											'".$organization."', 
											'".$organization_maticna."', 
											'".$dpo_firstname."', 
											'".$dpo_lastname."', 
											'".$dpo_email."', 
											'".$dpo_phone."', 
											'".$firstname."', 
											'".$lastname."', 
											'".$email."', 
											'".$phone."', 
											'".$address."', 
											'".$country."')
									ON DUPLICATE KEY UPDATE
										type='".$type."', 
										has_dpo='".$has_dpo."', 
										organization='".$organization."', 
										organization_maticna='".$organization_maticna."', 
										dpo_firstname='".$dpo_firstname."', 
										dpo_lastname='".$dpo_lastname."', 
										dpo_email='".$dpo_email."', 
										dpo_phone='".$dpo_phone."', 
										firstname='".$firstname."', 
										lastname='".$lastname."', 
										email='".$email."', 
										phone='".$phone."', 
										address='".$address."', 
										country='".$country."'");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);

			self::displayGDPRUser($error);
		}

		// Prikaz ustrezne gdpr avtoritetec
		if($_GET['a'] == 'gdpr_edit_authority'){

			$country = isset($_POST['country']) ? $_POST['country'] : '';

			self::displayGDPRAuthority($country);
		}


		// Nastavljanje ankete da je gdpr
		elseif($_GET['a'] == 'gdpr_add_anketa'){

			if($ank_id != '' && $ank_id != '0'){
				if($value == '1')
					$sql = sisplet_query("INSERT INTO srv_gdpr_anketa (ank_id) VALUES ('".$ank_id."')");
				else
					$sql = sisplet_query("DELETE FROM srv_gdpr_anketa WHERE ank_id='".$ank_id."'");
			}

			self::displayGDPRSurveyList();
		}
		
		// Urejanje gdpr nastavitve za userja
		if($_GET['a'] == 'gdpr_edit_anketa'){

			if($ank_id != '' && $ank_id != '0'){

				$is_gdpr = isset($_POST['is_gdpr']) ? $_POST['is_gdpr'] : '0';

				// Vklopimo gdpr
				if($is_gdpr == '1'){
					
					$name = isset($_POST['name']) ? $_POST['name'] : '';
					$email = isset($_POST['email']) ? $_POST['email'] : '';
					$location = isset($_POST['location']) ? $_POST['location'] : '';
					$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
					$web = isset($_POST['web']) ? $_POST['web'] : '';
					$other = isset($_POST['other']) ? $_POST['other'] : '';
					$other_text_slo = isset($_POST['other_text_slo']) ? $_POST['other_text_slo'] : '';
					$other_text_eng = isset($_POST['other_text_eng']) ? $_POST['other_text_eng'] : '';
					
					$template_1ka = isset($_POST['1ka_template']) ? $_POST['1ka_template'] : '';
					
					$about = (isset($_POST['about'])) ? $_POST['about'] : '';
					
					$expire = isset($_POST['expire']) ? $_POST['expire'] : '';
					$expire_text_slo = isset($_POST['expire_text_slo']) ? $_POST['expire_text_slo'] : '';
					$expire_text_eng = isset($_POST['expire_text_eng']) ? $_POST['expire_text_eng'] : '';
					$other_users = isset($_POST['other_users']) ? $_POST['other_users'] : '';
					$other_users_text_slo = isset($_POST['other_users_text_slo']) ? $_POST['other_users_text_slo'] : '';
					$other_users_text_eng = isset($_POST['other_users_text_eng']) ? $_POST['other_users_text_eng'] : '';
					$export = isset($_POST['export']) ? $_POST['export'] : '';
					$export_country_slo = isset($_POST['export_country_slo']) ? $_POST['export_country_slo'] : '';
					$export_country_eng = isset($_POST['export_country_eng']) ? $_POST['export_country_eng'] : '';
					$export_user_slo = isset($_POST['export_user_slo']) ? $_POST['export_user_slo'] : '';
					$export_user_eng = isset($_POST['export_user_eng']) ? $_POST['export_user_eng'] : '';
					$export_legal_slo = isset($_POST['export_legal_slo']) ? $_POST['export_legal_slo'] : '';
					$export_legal_eng = isset($_POST['export_legal_eng']) ? $_POST['export_legal_eng'] : '';
					$authorized = isset($_POST['authorized']) ? $_POST['authorized'] : '';
					$contact_email = isset($_POST['contact_email']) ? $_POST['contact_email'] : '';
					$note_slo = isset($_POST['note_slo']) ? $_POST['note_slo'] : '';
					$note_eng = isset($_POST['note_eng']) ? $_POST['note_eng'] : '';

					$sql = sisplet_query("INSERT INTO srv_gdpr_anketa (
												ank_id, 
												1ka_template, 
												name, 
                                                email, 
												location, 
												phone, 
												web, 
												other, 
												other_text_slo, 
												other_text_eng, 
												about, 
												expire, 
												expire_text_slo, 
												expire_text_eng, 
												other_users, 
												other_users_text_slo, 
												other_users_text_eng, 
												export, 
												export_user_slo, 
												export_user_eng, 
												export_country_slo, 
												export_country_eng, 
												export_legal_slo, 
												export_legal_eng, 
												authorized, 
												contact_email,
                                                note_slo,
                                                note_eng
											)
											VALUES (
												'".$ank_id."', 
												'".$template_1ka."', 
												'".$name."', 
												'".$email."', 
												'".$location."', 
												'".$phone."', 
												'".$web."', 
												'".$other."', 
												'".$other_text_slo."',
												'".$other_text_eng."',
												'".$about."',
												'".$expire."',
												'".$expire_text_slo."',
												'".$expire_text_eng."',
												'".$other_users."',
												'".$other_users_text_slo."',
												'".$other_users_text_eng."',
												'".$export."',
												'".$export_user_slo."',
												'".$export_user_eng."',
												'".$export_country_slo."',
												'".$export_country_eng."',
												'".$export_legal_slo."',
												'".$export_legal_eng."',
												'".$authorized."',
												'".$contact_email."',
												'".$note_slo."',
												'".$note_eng."'
											)
											ON DUPLICATE KEY UPDATE
												1ka_template='".$template_1ka."', 
												name='".$name."', 
												email='".$email."', 
												location='".$location."', 
												phone='".$phone."', 
												web='".$web."', 
												other='".$other."', 
												other_text_slo='".$other_text_slo."', 
												other_text_eng='".$other_text_eng."', 
												about='".$about."', 
												expire='".$expire."', 
												expire_text_slo='".$expire_text_slo."', 
												expire_text_eng='".$expire_text_eng."', 
												other_users='".$other_users."', 
												other_users_text_slo='".$other_users_text_slo."', 
												other_users_text_eng='".$other_users_text_eng."', 
												export='".$export."', 
												export_user_slo='".$export_user_slo."', 
												export_user_eng='".$export_user_eng."', 
												export_country_slo='".$export_country_slo."', 
												export_country_eng='".$export_country_eng."', 
												export_legal_slo='".$export_legal_slo."', 
												export_legal_eng='".$export_legal_eng."', 
												authorized='".$authorized."', 
												contact_email='".$contact_email."',
												note_slo='".$note_slo."',
												note_eng='".$note_eng."'"
										);
					if (!$sql)
						echo mysqli_error($GLOBALS['connect_db']);
						
					// Dodatno prikazemo uvod in zakljucek ce se uporablja 1ka template
					if($template_1ka == '1'){				
						$sqlA = sisplet_query("UPDATE srv_anketa SET show_intro='1', show_concl='1' WHERE id='".$ank_id."'");
					}
				}
				// Izklopimo gdpr - pobrisemo nastavitve
				else{
					$sql = sisplet_query("DELETE FROM srv_gdpr_anketa WHERE ank_id='".$ank_id."'");
					if (!$sql)
						echo mysqli_error($GLOBALS['connect_db']);
				}
			}
		}
		
		// Prikaz preview-ja gdpr uvoda
		if($_GET['a'] == 'gdpr_preview_intro'){
			
			if($ank_id != '' && $ank_id != '0'){
				
                echo '<div id="preview_spremenljivka">';
                
                echo '<div class="popup_close"><a href="#" onClick="preview_spremenljivka_cancle(); return false;">✕</a></div>';

				echo '	<div class="spremenljivka">';
				
				// Naslov vprasanja
				echo '<div class="naslov">';
				$naslov = self::getSurveyIntro($ank_id);
				echo $naslov;
				echo '</div>';
				
				// Variabli "da" in "ne"
				echo '<div class="variable_holder clr">';
				echo '	<div class="variabla"><label for="intro_0"><input type="radio" id="intro_0" name="intro"> '.$lang['srv_gdpr_intro_no'].'</label></div>';
				echo '	<div class="variabla"><label for="intro_1"><input type="radio" id="intro_1" name="intro"> '.$lang['srv_gdpr_intro_yes'].'</label></div>';
				echo '</div>';
				
				echo '	</div>';
				
				// Gumba zapri
				//echo '<div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="preview_spremenljivka_cancle(); return false;"><span>Zapri</span></a></div>';
				
				echo '</div>';
			}
        }

        // Prikaz preview-ja gdpr izvoza
		if($_GET['a'] == 'gdpr_preview_export'){
			
			if($ank_id != '' && $ank_id != '0'){
                
                // Tip izvoza
                if (isset ($_POST['type']))
                    $type = $_POST['type'];


                echo '<div id="preview_gdpr_export" class="divPopUp">';

                echo '<div class="popup_close"><a href="#" onClick="preview_spremenljivka_cancle(); return false;">✕</a></div>';

                echo '<div class="content">';

                // Informacije dane posamezniku
                if($type == '1'){
                    
                    $text_array = self::getGDPRInfoArray($ank_id);
                    $text = self::getGDPRTextFromArray($text_array, $type='html');

                    // Naslov
                    echo '<h2 style="color">';
                    echo $lang['export_gdpr_individual'];
                    echo '</h2>';
                }
                // Evidenca dejavnosti obdelav
                else{

                    $text_array = self::getGDPREvidencaArray($ank_id);
                    $text = self::getGDPRTextFromArray($text_array, $type='html');
                    
                    // Naslov
                    echo '<h2>';
                    echo $lang['export_gdpr_activity'];
                    echo '</h2>';
                }

                echo $text;

                echo '</div>';
                
				echo '</div>';	
			}
        }
        

		// Zahteva je obdelana
		elseif($_GET['a'] == 'gdpr_request_done'){

			if (isset ($_POST['request_id'])){
				
				$request_id = $_POST['request_id'];		
				$sql = sisplet_query("UPDATE srv_gdpr_requests SET status='".$value."' WHERE id='".$request_id."'");
			}
			
			self::displayGDPRRequests();
		}
		// Zahteva je obdelana - v posamezni anketi
		elseif($_GET['a'] == 'gdpr_request_done_survey'){

			if (isset ($_POST['request_id']) && isset ($_POST['ank_id'])){
				
				$ank_id = $_POST['ank_id'];		
				$request_id = $_POST['request_id'];		
				$sql = sisplet_query("UPDATE srv_gdpr_requests SET status='".$value."' WHERE id='".$request_id."'");
			}
			
			self::displayGDPRSurveyRequests($ank_id);
		}
		
		// Komentar na zahtevo
		elseif($_GET['a'] == 'gdpr_request_comment'){

			if (isset ($_POST['request_id'])){
				
				$request_id = $_POST['request_id'];		
				$sql = sisplet_query("UPDATE srv_gdpr_requests SET comment='".$value."' WHERE id='".$request_id."'");
			}
			
			self::displayGDPRRequests();
		}
		// Komentaran zahtevo - v posamezni anketi
		elseif($_GET['a'] == 'gdpr_request_comment_survey'){

			if (isset ($_POST['request_id']) && isset ($_POST['ank_id'])){
				
				$ank_id = $_POST['ank_id'];		
				$request_id = $_POST['request_id'];		
				$sql = sisplet_query("UPDATE srv_gdpr_requests SET comment='".$value."' WHERE id='".$request_id."'");
			}
			
			self::displayGDPRSurveyRequests($ank_id);
		}
	}
}
