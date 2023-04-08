<?php

/**
* Ta class se vedno kliče iz SurveyAdmin
* 
* Vsebuje naj vse nastavitve v anketi in take zadeve, ki se ne uporabljajo pogosto (in se ne kličejo preko ajaxa) da ne smetijo v SurveyAdmin
* 
* Zaenkrat je še celotna kopija SurveyAdmin, treba je še pobrisat odvečne funkcije
* 
* @var mixed
*/


global $site_path;

if(session_id() == '') {
    session_start();
}

class SurveyAdminSettings {

	var $anketa; // trenutna anketa
	var $grupa; // trenutna grupa
	var $spremenljivka; // trenutna spremenljivka
	var $branching = 0; // pove, ce smo v branchingu
	var $stran;
	var $podstran;
	var $skin = 0;
	var $survey_type; // privzet tip je anketa na vecih straneh

	var $displayLinkIcons = false; // zaradi nenehnih sprememb je trenutno na false, se kasneje lahko doda v nastavitve
	var $displayLinkText = true; // zaradi nenehnih sprememb je trenutno na true, se kasneje lahko doda v nastavitve
	var $setting = null;

	var $db_table = '';
		
	var $icons_always_on = false;	# ali ima uporabnik nastavljeno da so ikone vedno vidne
	var $full_screen_edit = false;	# ali ima uporabnik nastavljeno da ureja vprašanja v fullscreen načinu

	/**
	 * @desc konstruktor
	 */
	function __construct($action = 0, $anketa = 0) {
		global $surveySkin, $site_url, $global_user_id;
		
		if (isset ($surveySkin))
			$this->skin = $surveySkin;
		else
			$this->skin = 0;
			
		if ((isset ($_REQUEST['anketa']) && $_REQUEST['anketa'] > 0) || (isset ($anketa) && $anketa > 0)) {
			$this->anketa = (isset ($anketa) && $anketa > 0) ? $anketa : $_REQUEST['anketa'];
		} 
        else {
			// nekje se uporablja tudi brez IDja ankete!!!
			//die("SAS: SID missing!");
		}

		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		SurveyInfo::getInstance()->resetSurveyData();
		
		$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();

		$this->survey_type = $this->getSurvey_type($this->anketa);

		$this->stran = $_GET['a'];

	}

	/*Globalne nastavitve
	 * 		Osnovni podatki
	 * 		Respondenti
	 * 		Design
	 * 		Obveščanje
	 * 		Piškotek
	 * 		Trajanje
	 * 		Komentarji
	 * 		Dostop
	 */

	function anketa_nastavitve_global() {//OSTANE
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;

		/* Globalne nastavitve ankete: veljajo za celoto anketo ne glede na uporabnika*/
		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<form name="settingsanketa_' . $row['id'] . '" action="ajax.php?a=editanketasettings" method="post" autocomplete="off">' . "\n\r";
		echo '	<input type="hidden" name="anketa" value="' . $this->anketa . '" />' . "\n\r";
		echo '	<input type="hidden" name="grupa" value="' . $this->grupa . '" />' . "\n\r";
		echo '  <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";
		echo '  <input type="hidden" name="submited" value="1" />' . "\n\r";
		/*Osnovni podatki*/

		if ($_GET['a'] == 'osn_pod' || $_GET['a'] == 'nastavitve') {

			/* OSNOVNI PODATKI */
			echo '<fieldset>';
			echo '<legend>' . $lang['srv_osnovniPodatki'] . '</legend>';

			echo '<span class="nastavitveSpan2" >' . $lang['srv_novaanketa_polnoime'] . ':&nbsp;</span>';
			echo '<input type="text" id="anketa_polnoIme" name="anketa_polnoIme" value="' . $row['naslov'] . '" style="width:300px" onblur="edit_anketa_naslov(\'' . $row['id'] . '\');" maxlength="'.ANKETA_NASLOV_MAXLENGTH.'" />&nbsp;<span id="anketa_polnoIme_chars">' . strlen($row['naslov']) . '/'.ANKETA_NASLOV_MAXLENGTH.'</span><br/>' . "\n\r";
			echo '<span class="nastavitveSpan2" >' . $lang['srv_novaanketa_kratkoime'] . ':&nbsp;</span>';
			echo '<input type="text" id="anketa_akronim" name="anketa_akronim" value="' . $row['akronim'] . '" style="width:300px" onblur="edit_anketa_akronim(\'' . $row['id'] . '\');"  maxlength="'.ANKETA_AKRONIM_MAXLENGTH.'" />&nbsp;<span id="anketa_akronim_chars">' . strlen($row['akronim']) . '/'.ANKETA_AKRONIM_MAXLENGTH.'</span><br/>' . "\n\r";
			echo '<span class="nastavitveSpan2" style="vertical-align:top;">' . $lang['srv_note'] . ':&nbsp;</span>';
			echo '<span><textarea rows="5" cols="20" id="anketa_note" name="anketa_note" onblur="edit_anketa_note(\'' . $row['id'] . '\');" maxlength="'.ANKETA_NOTE_MAXLENGTH.'">' . $row['intro_opomba'] . '</textarea></span>&nbsp;<span id="anketa_note_chars">' . strlen($row['intro_opomba']) . '/'.ANKETA_NOTE_MAXLENGTH.'</span><br/>' . "\n\r";
			echo '<span id="blank_note_edit"></span><br />';
			
			// Ce ima uporabnik mape, lahko izbere v katero mapo se anketa uvrsti
			UserSetting::getInstance()->Init($global_user_id);
			$show_folders = UserSetting::getInstance()->getUserSetting('survey_list_folders');
			
			$selected_folder = 0;
			$sqlFA = sisplet_query("SELECT folder FROM srv_mysurvey_anketa WHERE usr_id='".$global_user_id."' AND ank_id='".$this->anketa."'");
			if(mysqli_num_rows($sqlFA) > 0){
				$rowFA = mysqli_fetch_array($sqlFA);
				$selected_folder = $rowFA['folder'];
			}
			
			$sqlF = sisplet_query("SELECT id, naslov FROM srv_mysurvey_folder WHERE usr_id='".$global_user_id."' ORDER BY naslov ASC");	
			if($show_folders == 1 && mysqli_num_rows($sqlF) > 0){
				echo '<span class="nastavitveSpan2">' . $lang['srv_newSurvey_survey_new_folder'] . ':</span>';
				
				echo '<select name="anketa_folder" id="anketa_folder">';
				echo '<option value="0" '.($selected_folder == 0 ? ' selected="selected"' : '').'>'.$lang['srv_newSurvey_survey_new_folder_def'].'</option>';
				while($rowF = mysqli_fetch_array($sqlF)){
					echo '<option value="'.$rowF['id'].'" '.($rowF['id'] == $selected_folder ? ' selected="selected"' : '').'>'.$rowF['naslov'].'</option>';
				}
				echo '</select>';
				echo '<br />';
			}
			
			echo '</fieldset>';			
			
			echo '<br />';
					
			/* JEZIK */
			echo '<fieldset>';
			echo '<legend>' . $lang['lang'] . '</legend>';
			
			$lang_admin = $row['lang_admin'];
			$lang_resp = $row['lang_resp'];
			$lang_array = array();
			// Preberemo razpoložljive jezikovne datoteke
			if ($dir = opendir('../../lang')) {
				while (($file = readdir($dir)) !== false) {
					if ($file != '.' AND $file != '..') {
						if (is_numeric(substr($file, 0, strpos($file, '.')))) {
							$i = substr($file, 0, strpos($file, '.'));
							if ($i > 0) {
								$file = '../../lang/'.$i.'.php';
								@include($file);
								$lang_array[$i] = $lang['language'];
							}
						}
					}
				}
			}
			
			// nastavimo jezik nazaj
			if ($lang_admin > 0) {
				$file = '../../lang/'.$lang_admin.'.php';
				@include($file);
			}
			
			echo '<span class="nastavitveSpan3 bold">'.$lang['srv_language_admin_survey'].':</span>';
			ksort($lang_array);
			foreach ($lang_array AS $key => $val) {
				if ($key == 1 || $key == 2) {	
					echo '<input type="radio" value="'.$key.'" id="lll_'.$key.'" '.($key==$lang_admin?' checked':'').' name="lang_admin" style="margin-bottom:5px;">';
					echo '<label for="lll_'.$key.'">'.$val.'</label>&nbsp;';
				} 
			}
			
			echo '<br />';
			
			echo '<span class="nastavitveSpan3 bold">'.$lang['srv_language_respons_1'].':</span>';
			asort($lang_array);
			echo '&nbsp;<select name="lang_resp">';
			foreach ($lang_array AS $key => $val) {
				echo '<option value="'.$key.'" '.($key==$lang_resp?' selected':'').'>'.$val.'</option>'; 
			}
			echo '</select>';	
			
			echo '<br /><br />';
            
            echo '<span class="nastavitveSpan3 bold">'.$lang['srv_language_link2'].':</span>';

            // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
            $userAccess = UserAccess::getInstance($global_user_id);
            if($userAccess->checkUserAccess($what='prevajanje')){
                echo '&nbsp;<a href="index.php?anketa='.$this->anketa.'&a=prevajanje" title="'.$lang['srv_language_link'].'"><span class="bold">'.$lang['srv_language_link'].'</span></a>';
            }
            else{            
                echo '&nbsp;<a href="#" onClick=popupUserAccess(\'prevajanje\'); return false;" title="'.$lang['srv_language_link'].'" class="user_access_locked"><span class="bold">'.$lang['srv_language_link'].'</span></a>';
            }

			
			
			echo '<div style="float:right;">'.$lang['srv_language_mySurveys'].'</div>';
			
			echo '</fieldset>';
			
			echo '<br />';

			// Gumb shrani - vmes
			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'] . '</span></a></div></span>';
			echo '<div class="clr"></div>';
			
			echo '<br class="clr" />';
			
					
			/* INTERAKTIVNI ELEMENTI */
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_interaktivni_elementi'].'</legend>';
			
			SurveySetting::getInstance()->Init($this->anketa);
			$survey_privacy = SurveySetting::getInstance()->getSurveyMiscSetting('survey_privacy');
			$survey_hint = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hint'); if ($survey_hint == '') $survey_hint = 1;
			$survey_hide_title = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hide_title');			
			$survey_track_reminders = SurveySetting::getInstance()->getSurveyMiscSetting('survey_track_reminders'); if ($survey_track_reminders == '') $survey_track_reminders = 0;
			$display_backlink = SurveySetting::getInstance()->getSurveyMiscSetting('display_backlink');

			$multiple_pages = false;
			$sqlg = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id = '$this->anketa'");
			if (mysqli_num_rows($sqlg) > 1)
				$multiple_pages = true;

				
			// Indikator napredka	
			//echo '<br />';
			echo '<span class="nastavitveSpan3 bold">'.$lang['srv_te_progressbar'].' '.Help::display('srv_show_progressbar').':</span>';
			echo '<label><input type="radio" name="progressbar" value="1" '.($row['progressbar'] == 1 && $multiple_pages ? ' checked="checked"' : '').' '.(!$multiple_pages ? ' disabled="disabled"' : '').'>' . $lang['yes'] . '</label> ';
			echo '<label><input type="radio" name="progressbar" value="0" '.($row['progressbar'] == 0 || !$multiple_pages ? ' checked="checked"' : '').' '.(!$multiple_pages ? ' disabled="disabled"' : '').'>' . $lang['no1'] . '</label> ';
			
			// Naslov ankete za uporabnike
			echo '<br />';
			echo '<span class="nastavitveSpan3 bold">'.$lang['srv_show_title'].':</span>';
			echo '<label><input type="radio" name="survey_hide_title" value="0" '.($survey_hide_title == 0 ? ' checked="checked"' : '').'>' . $lang['yes'] . '</label> ';
			echo '<label><input type="radio" name="survey_hide_title" value="1" '.($survey_hide_title == 1 ? ' checked="checked"' : '').'>' . $lang['no1'] . '</label> ';
			
			// Politika zasebnosti
			echo '<br />';
			echo '<span class="nastavitveSpan3 bold"><label for="anketa_countType" >' . $lang['srv_privacy'] . ' '.Help::display('srv_privacy_setting').':</label></span>';
			echo '<label><input type="radio" name="privacy" value="0"' . ($survey_privacy == 0 ? ' checked="checked"' : '') . '>' . $lang['srv_privacy_0'] . '</label>' . "\n\r";
			echo '<label><input type="radio" name="privacy" value="1"' . ($survey_privacy == 1 ? ' checked="checked"' : '') . '>' . $lang['srv_privacy_1'] . '</label>' . "\n\r";
			echo '<label><input type="radio" name="privacy" value="2"' . ($survey_privacy == 2 ? ' checked="checked"' : '') . '>' . $lang['srv_privacy_2'] . '</label>' . "\n\r";		
			
			// Nadaljuj kasneje
			if($this->survey_type > 1){
				echo '<br />';
				echo '<span class="nastavitveSpan3 bold"><label>' . $lang['srv_show_continue_later'] . ' '.Help::display('srv_continue_later_setting').':</label></span>';
				echo '<label for="continue_later_1"><input type="radio" name="continue_later" value="1" id="continue_later_1"' . ($row['continue_later'] == 1 ? ' checked="checked"' : '') . '>' . $lang['yes'] . '</label>' . "\n\r";
				echo '<label for="continue_later_0"><input type="radio" name="continue_later" value="0" id="continue_later_0"' . ($row['continue_later'] == 0 ? ' checked="checked"' : '') . '>' . $lang['no1'] . '</label>' . "\n\r";
			}
			
			// Gumb nazaj
			echo '<br />';
			echo '<span class="nastavitveSpan3 bold">'.$lang['srv_slideshow_sett_back_button_lbl'].':</span>';
			echo '<label for="display_backlink_1"><input type="radio" name="display_backlink" id="display_backlink_1" '.($display_backlink!=='0'?' checked':'').' value="">'.$lang['yes'].'</label> ';
			echo '<label for="display_backlink_0"><input type="radio" name="display_backlink" id="display_backlink_0" '.($display_backlink==='0'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br /><br />';
						
			// Namig
			echo '<span class="nastavitveSpan3 bold"><label>' . $lang['srv_hint'] . ' '.Help::display('srv_namig_setting').':</label></span>';
			echo '<label><input type="radio" name="survey_hint" value="1"' . ($survey_hint == 1 ? ' checked="checked"' : '') . '>' . $lang['yes'] . '</label>' . "\n\r";
			echo '<label><input type="radio" name="survey_hint" value="0"' . ($survey_hint == 0 ? ' checked="checked"' : '') . '>' . $lang['no1'] . '</label>' . "\n\r";
			
			//belezenje reminderjev
			/*echo '<br /><span class="nastavitveSpan3 bold"><label>' . $lang['srv_reminder_tracking'] . ':</label></span>';
			echo '<label><input type="radio" name="survey_track_reminders" value="1"' . ($survey_track_reminders == 1 ? ' checked="checked"' : '') . '>' . $lang['yes'] . '</label>' . "\n\r";
			echo '<label><input type="radio" name="survey_track_reminders" value="0"' . ($survey_track_reminders == 0 ? ' checked="checked"' : '') . '>' . $lang['no1'] . '</label>' . "\n\r";*/				
			
			echo '<br />';
			
			echo '<span class="nastavitveSpan3 bold">'.$lang['srv_opozorilo_vprasanja'].':</span>&nbsp;
						<a href="#" onClick="popupAlertAll(\'soft\')">'.$lang['srv_soft_reminder_all'].'</a>,
						<a href="#" onClick="popupAlertAll(\'hard\')">'.$lang['srv_hard_reminder_all'].'</a>,
						<a href="#" onClick="popupAlertAll(\'no\')">'.$lang['srv_no_reminder_all'].'</a>';		
			echo '<br /><br />';
			
			// Napredni parapodatki
			if (($admin_type == 0 || $admin_type == 1) && $this->survey_type > 0) {
				echo '<span class="nastavitveSpan3 bold"><label>' . $lang['srv_parapodatki'] . ':</label></span>';
				echo '<label><input type="radio" name="parapodatki" value="1"' . ($row['parapodatki'] == 1 ? ' checked="checked"' : '') . '>' . $lang['yes'] . '</label>' . "\n\r";
				echo '<label><input type="radio" name="parapodatki" value="0"' . ($row['parapodatki'] == 0 ? ' checked="checked"' : '') . '>' . $lang['no1'] . '</label>' . "\n\r";
				
				echo '(Download: ';
				// Download tracking podatke
				echo '<a href="parapodatki.php?anketa='.$this->anketa.'&a=tracking" target="_blank">Editor data</a>, ';
				// Download parapodatke
				echo '<a href="parapodatki.php?anketa='.$this->anketa.'&a=parapodatki" target="_blank">Respondent data</a>, ';
				// Download vprasanja v anketi (srv_spremenljivka)
				echo '<a href="parapodatki.php?anketa='.$this->anketa.'&a=vprasanja" target="_blank">Survey questions</a>, ';
				// Download variable v vprasanjih (srv_vrednost)
				echo '<a href="parapodatki.php?anketa='.$this->anketa.'&a=items" target="_blank">Question items</a>';
				echo ') '.Help::display('srv_parapodatki');	
			}
			
			echo '<br />';
 
			// Arhiviranje vprasanj - samo admini in managerji
			if ($admin_type == 0 || $admin_type == 1) {
				echo '<span class="nastavitveSpan3 bold">'.$lang['srv_vprasanje_tracking'].' '.Help::display('srv_vprasanje_tracking_setting').':</span>';
				echo '<label for="vprasanje_tracking_1"><input type="radio" name="vprasanje_tracking" id="vprasanje_tracking_1" '.($row['vprasanje_tracking']==1?' checked':'').' value="1">'.$lang['srv_avtomatsko'].'</label>';
				//echo '<input type="radio" name="vprasanje_tracking" id="vprasanje_tracking_1" '.($row['vprasanje_tracking']==1?' checked':'').' value="1"><label for="vprasanje_tracking_1">'.$lang['srv_avtomatsko'].' ('.$lang['srv_loop_always'].')</label>';
				//echo '<input type="radio" name="vprasanje_tracking" id="vprasanje_tracking_3" '.($row['vprasanje_tracking']==3?' checked':'').' value="3"><label for="vprasanje_tracking_3">'.$lang['srv_avtomatsko'].' ('.$lang['srv_potrditev'].')</label>';
				echo '<label for="vprasanje_tracking_2"><input type="radio" name="vprasanje_tracking" id="vprasanje_tracking_2" '.($row['vprasanje_tracking']==2?' checked':'').' value="2">'.$lang['srv_rocno'].'</label>';
				echo '<label for="vprasanje_tracking_0"><input type="radio" name="vprasanje_tracking" id="vprasanje_tracking_0" '.($row['vprasanje_tracking']==0?' checked':'').' value="0">'.$lang['no'].'</label>';
			}
 
			echo '</fieldset>';
					
			/* ZAKLJUCEK (samo pri formi) */
			if($row['survey_type'] == 1){
				echo '<br />';
				echo '<fieldset>';
				echo '<legend>'.$lang['srv_end_label'].'</legend>';
				if ($row['url'] != '')
					$url = $row['url'];
				else
					$url = $site_url;
					
				echo '<span class="nastavitveSpan2" ><label for="anketa' . $row['id'] . '" >' . $lang['srv_concl_link'] . ':&nbsp;</label></span>';
				echo '<input type="radio" name="concl_link" value="0" '.($row['concl_link'] == 0 ? ' checked' : '').' onclick="$(\'#srv_concl_link_go\').hide()">'.$lang['srv_concl_link_close'].' <input type="radio" name="concl_link" value="1" '.($row['concl_link'] == 1 ? ' checked' : '').' onclick="$(\'#srv_concl_link_go\').show()">'.$lang['srv_concl_link_go'];

				echo '<div id="srv_concl_link_go" '.($row['concl_link'] == 0?' style="display:none"':'').'><span class="nastavitveSpan2" ><label for="anketa' . $row['id'] . '" >' . $lang['srv_url'] . ':&nbsp;</label></span>';
				echo '<input type="text" name="url" id="url_concl_sett" value="'.$url.'" style="width:200px"></div>';
				
				// Prikaz zakljucka
				echo '<br />';
				echo '<span class="nastavitveSpan2" ><label for="anketa' . $row['id'] . '" >' . $lang['srv_show_concl']. ':&nbsp;</label></span>';
				echo '<input type="radio" name="show_concl" value="0" '.(($row['show_concl'] == 0) ? ' checked="checked" ' : '').' onclick="$(\'#srv_concl_settings\').hide()" />'.$lang['no1'];
				echo '<input type="radio" name="show_concl" value="1" '.(($row['show_concl'] == 1) ? ' checked="checked" ' : '').' onclick="$(\'#srv_concl_settings\').show()" />'.$lang['yes'];		
				
				echo '<div id="srv_concl_settings" '.($row['show_concl'] == 0?' style="display:none"':'').'>';
				
				// Besedilo zakljucka
				$text = ($row['conclusion'] == '') ? $lang['srv_end'] : $row['conclusion'];
				echo '<span class="nastavitveSpan2" ><label for="anketa' . $row['id'] . '" >' . $lang['text'] . ':&nbsp;</label></span>';
				echo '<span><textarea rows="5" cols="20" id="conclusion" name="conclusion">' . $text . '</textarea></span><br/>' . "\n\r";
				
				// Gumb konec
				echo '<br />';
				echo '<span class="nastavitveSpan1" ><label for="anketa' . $row['id'] . '" >' . $lang['srv_concl_end_button_show'] . ':&nbsp;</label></span>';
				echo '<input type="radio" name="concl_end_button" value="0" '.(($row['concl_end_button'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'];
				echo '<input type="radio" name="concl_end_button" value="1" '.(($row['concl_end_button'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'];		
				
				// Gumb nazaj
				echo '<br />';
				echo '<span class="nastavitveSpan1" ><label for="anketa' . $row['id'] . '" >' . $lang['srv_concl_back_button_show'] . ':&nbsp;</label></span>';
				echo '<input type="radio" name="concl_back_button" value="0" '.(($row['concl_back_button'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'];
				echo '<input type="radio" name="concl_back_button" value="1" '.(($row['concl_back_button'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'];		
				
				echo '</div>';
				
				echo '</fieldset>';			
			}
						
			echo '<br />';
						
			/* KNJIZNICA */
			$sqlk = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='$global_user_id'");
			$moje = mysqli_num_rows($sqlk);
			$sqlk = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='0'");
			$javne = mysqli_num_rows($sqlk);
			
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_library'].'</legend>';
			if ($admin_type == 0 || $admin_type == 1) {
				echo '<span class="nastavitveSpan2" ><label>'.$lang['srv_javne_ankete'].':</label></span> <label><input type="radio" name="javne_ankete" value="0"'.($javne==0?' checked':'').' onchange="javascript:check_library();">'.$lang['no'].'</label> <label><input type="radio" name="javne_ankete" value="1"'.($javne==1?' checked':'').' onchange="javascript:check_library();">'.$lang['yes'].'</label>';
				echo '<br/>';
			}
			
			echo '<div id="moje_ankete">';
			echo '<span class="nastavitveSpan2"><label>'.$lang['srv_moje_ankete'].' '.Help::display('srv_moje_ankete_setting').':</label></span> <label><input type="radio" name="moje_ankete" value="0"'.($moje==0?' checked':'').'>'.$lang['no'].'</label> <label><input type="radio" name="moje_ankete" value="1"'.($moje==1?' checked':'').'>'.$lang['yes'].'</label>';
			echo '</div>';
			
			// zamakni
			echo '<br />';
			echo '<span class="nastavitveSpan2"><label>'.$lang['a_show'].'</label></span>'; 
			if ($row['flat'] == 0)
				echo '<span title="'.$lang['srv_flat_0'].'"><a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=flat&value=1"><span class="faicon flat_0"></span> '.$lang['srv_flat_0_short'].'</a></span> ';
			else
				echo '<span title="'.$lang['srv_flat_1'].'"><a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=flat&value=0"><span class="faicon flat_1"></span> '.$lang['srv_flat_0_short'].'</a></span> ';
			echo Help::display('srv_branching_flat');
			
			// odpri	
			if ($row['popup'] == 1)
				echo '<span class="spaceLeft" title="'.$lang['srv_popup_1'].'"><a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=popup&value=0"><span class="faicon popup_1"></span> '.$lang['srv_popup_1_short'].'</a></span> ';
			else
				echo '<span class="spaceLeft" title="'.$lang['srv_popup_0'].'"><a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=popup&value=1"><span class="faicon popup_0"></span> '.$lang['srv_popup_1_short'].'</a></span> ';
			echo Help::display('srv_branching_popup');
			
			echo '</fieldset>';
			
			?>
			<script>
			check_library();
			</script>
			<?
						
			/* STEVILCENJE */
			echo '<br/>';
			echo '<fieldset>';
			echo '<legend>' . $lang['srv_nastavitveStevilcenje'] . '</legend>';

			echo '<span class="nastavitveSpan2"><label for="anketa_countType">' . $lang['srv_nastavitveStevilcenjeType'] . ':</label></span>';
			echo '<label for="countType_0"><input type="radio" name="countType" value="0" id="countType_0" checked="checked" onclick="saveGlobalSetting(\'countType\')"/>' . $lang['srv_nastavitveStevilcenjeType0'] . '</label>' . "\n\r";
			echo '<label for="countType_1"><input type="radio" name="countType" value="1" id="countType_1" ' . ($row['countType'] == 1 ? ' checked="checked"' : '') . ' onclick="saveGlobalSetting(\'countType\')"/>' . $lang['srv_nastavitveStevilcenjeType1'] . '</label>' . "\n\r";
			echo '<label for="countType_2"><input type="radio" name="countType" value="2" id="countType_2" ' . ($row['countType'] == 2 ? ' checked="checked"' : '') . ' onclick="saveGlobalSetting(\'countType\')"/>' . $lang['srv_nastavitveStevilcenjeType2'] . '</label>' . "\n\r";
			echo '<label for="countType_3"><input type="radio" name="countType" value="3" id="countType_3" ' . ($row['countType'] == 3 ? ' checked="checked"' : '') . ' onclick="saveGlobalSetting(\'countType\')"/>' . $lang['srv_nastavitveStevilcenjeType3'] . '</label>' . "\n\r";
			
			echo '<br />';
			
			// Izklop prestevilcevanja
			$enumerate = SurveySetting::getInstance()->getSurveyMiscSetting('enumerate'); if ($enumerate == '') $enumerate = 1;
			echo '<span class="nastavitveSpan2"><label for="anketa_enumerate">'.$lang['srv_nastavitvePrestevilcevanje'].':</label></span>';
			echo '<label for="enumerate_1"><input type="radio" name="enumerate" id="enumerate_1" '.($enumerate == 1 ? ' checked' : '').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="enumerate_0"><input type="radio" name="enumerate" id="enumerate_0" '.($enumerate == 0 ? ' checked' : '').' value="0">'.$lang['no'].'</label> ';
			
			echo '</fieldset>';
						
			echo '<br />';
			
			/* JS TRACKING */
			if ($admin_type == 0 || $admin_type == 1) {
				echo '<fieldset><legend>'.$lang['srv_js_tracking'].'</legend>';
				//echo '<legend>' . $lang['srv_nastavitveStevilcenje'] . '</legend>';
				echo '<p><textarea name="js_tracking" cols="20" rows="5">'.$row['js_tracking'].'</textarea></p>';
				echo '<p style="color: gray">'.$lang['js_tracking_note'].'</p>';
				echo '</fieldset>';
			}
		}

		/* PISKOTEK */
		if ($_GET['a'] == A_PRIKAZ) {

			echo '<fieldset>';
			echo '<legend>' . $lang['srv_data_valid_units_settings'] . '</legend>';

			echo '<p>';
			echo '<span class="strong" >'.$lang['srv_prikaz_default_valid'].'</span>';
			echo '<label><input type="radio" name="defValidProfile" '.($row['defValidProfile']==2?' checked':'').' value="2">'.'(5,6) '.$lang['srv_data_valid_units'].'</label>';
			echo '<label><input type="radio" name="defValidProfile" '.($row['defValidProfile']==3?' checked':'').' value="3">'.'(6) '.$lang['srv_data_finished_units'].'</label>';
			echo '</p>';

            // Pri volitvah ne moremo prikazati datuma respondenta
            if(!SurveyInfo::getInstance()->checkSurveyModule('voting')){
                echo '<p>';
                echo '<span class="strong" >'.$lang['srv_prikaz_showItime'].'</span>';
                echo '<label><input type="radio" name="showItime" '.((int)$row['showItime']==0?' checked':'').' value="0">'.$lang['no1'].'</label>';
                echo '<label><input type="radio" name="showItime" '.((int)$row['showItime']==1?' checked':'').' value="1">'.$lang['yes'].'</label>';
                echo '</p>';
            }

			echo '<p>';
			echo '<span class="strong" >'.$lang['srv_prikaz_showLineNumber'].'</span>';
			echo '<label><input type="radio" name="showLineNumber" '.((int)$row['showLineNumber']==0?' checked':'').' value="0">'.$lang['no1'].'</label>';
			echo '<label><input type="radio" name="showLineNumber" '.((int)$row['showLineNumber']==1?' checked':'').' value="1">'.$lang['yes'].'</label>';
			echo '</p>';

			echo '</fieldset>';
		}

		/*Piskotek*/
		if ($_GET['a'] == 'piskot') {

            // Pri volitvah ne moremo popravljati nastavitev piskotka
            if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                
                echo '<fieldset style="position:relative">';
                echo '<legend>' . $lang['srv_cookie'] . '</legend>';
                echo '<span class="red bold">'.$lang['srv_voting_no_cookie'].'</span>';
                echo '</fieldset>';
                
                echo '</form>';

                return;
            }
            
            echo '<fieldset style="position:relative">';

            echo '<div id="cookie_alert" class="google_yellow">';
            echo '<span class="">'.$lang['srv_cookie_alert_title'].'</span>';
            echo '<span class="">'.$lang['srv_cookie_alert_1'].'</span>';
            echo '<span class="">'.$lang['srv_cookie_alert_2'].'</span>';
            echo '<span class="">'.$lang['srv_cookie_alert'].'</span>';
            echo '</div>';
            
            echo '<legend>' . $lang['srv_cookie'] . '</legend>';
            
            // Shrani piskotek za X casa
            echo '<span class="nastavitveSpan3 bold" ><label>' . $lang['srv_cookie'] . Help :: display('srv_cookie') .':</label></span>';
            echo '            <label for="cookie_-1"><input type="radio" name="cookie" value="-1" id="cookie_-1"' . ($row['cookie'] == -1 ? ' checked="checked"' : '') . ' onclick="checkcookie();" />' . $lang['srv_cookie_-1'] . '</label>' . "\n\r";
            echo '            <label for="cookie_0"><input type="radio" name="cookie" value="0" id="cookie_0"' . ($row['cookie'] == 0 ? ' checked="checked"' : '') . ' onclick="checkcookie();" />' . $lang['srv_cookie_0'] . '</label>' . "\n\r";
            echo '            <label for="cookie_1"><input type="radio" name="cookie" value="1" id="cookie_1"' . ($row['cookie'] == 1 ? ' checked="checked"' : '') . ' onclick="checkcookie();" />' . $lang['srv_cookie_1'] . '</label>' . "\n\r";
            echo '            <label for="cookie_2"><input type="radio" name="cookie" value="2" id="cookie_2"' . ($row['cookie'] == 2 ? ' checked="checked"' : '') . ' onclick="checkcookie();" />' . $lang['srv_cookie_2'] . '</label>' . "\n\r";
            echo '<br/>';
            
            // Ko se uporabnik vrne (zacne od zacetka/nadaljuje kjer je ostal)
            echo '<span class="nastavitveSpan3 bold" ><label>' . $lang['srv_cookie_return'] . Help :: display('srv_cookie_return') . ':</label></span>';
            echo '            <label for="cookie_return_0"><input type="radio" name="cookie_return" value="0" id="cookie_return_0"' . ($row['cookie_return'] == 0 ? ' checked="checked"' : '') . ' onclick="checkcookie();" />' . $lang['srv_cookie_return_start'] . '</label>' . "\n\r";
            echo '            <div class="no-cookie"><label for="cookie_return_1"><input type="radio" name="cookie_return" value="1" id="cookie_return_1"' . ($row['cookie_return'] == 1 ? ' checked="checked"' : '') . ' onclick="checkcookie();" />' . $lang['srv_cookie_return_middle'] . '</label></div>' . "\n\r";
            echo '<br>';
            
            // Ce je zakljucil lahko naknadno ureja svoje odgovore
            echo '<div class="no-cookie no-cookie-return"><span class="nastavitveSpan3 bold" ><label>' . $lang['srv_return_finished'] . Help :: display('srv_return_finished') . ':</label></span>';
            echo '            <label for="return_finished_1"><input type="radio" name="return_finished" value="1" id="return_finished_1"' . ($row['return_finished'] == 1 ? ' checked="checked"' : '') . ' />' . $lang['srv_return_finished_yes'] . '</label>' . "\n\r";
            echo '            <label for="return_finished_0"><input type="radio" name="return_finished" value="0" id="return_finished_0"' . ($row['return_finished'] == 0 ? ' checked="checked"' : '') . ' />' . $lang['srv_return_finished_no'] . '</label></div>' . "\n\r";
            echo '<br/>';
            
            // Nikoli ne more popravljati svojih odgovorov (tudi ce se npr. vrne na prejsnjo stran)
            echo '<div class="no-subsequent-answers"><span class="nastavitveSpan3 bold" ><label>' . $lang['srv_subsequent_answers'] . Help :: display('srv_subsequent_answers') . ':</label></span>';
            echo '            <label for="subsequent_answers_1"><input type="radio" name="subsequent_answers" value="1" id="subsequent_answers_1"' . ($row['subsequent_answers'] == 1 ? ' checked="checked"' : '') . ' />' . $lang['srv_subsequent_answers_yes'] . '</label>' . "\n\r";
            echo '            <label for="subsequent_answers_0"><input type="radio" name="subsequent_answers" value="0" id="subsequent_answers_0"' . ($row['subsequent_answers'] == 0 ? ' checked="checked"' : '') . ' />' . $lang['srv_subsequent_answers_no'] . '</label></div>' . "\n\r";
            echo '<br/>';
            
            // Ce ni sprejel piskotka lahko/ne more nadaljevati
            echo '<div class="no-cookie"><span class="nastavitveSpan3 bold" ><label>' . $lang['srv_cookie_continue'] . Help :: display('srv_cookie_continue') . ':</label></span>';
            echo '            <label for="cookie_continue_1"><input type="radio" name="cookie_continue" value="1" id="cookie_continue_1"' . ($row['cookie_continue'] == 1 ? ' checked="checked"' : '') . ' />' . $lang['srv_cookie_continue_yes'] . '</label>' . "\n\r";
            echo '            <label for="cookie_continue_0"><input type="radio" name="cookie_continue" value="0" id="cookie_continue_0"' . ($row['cookie_continue'] == 0 ? ' checked="checked"' : '') . ' />' . $lang['srv_cookie_continue_no'] . '</label></div>' . "\n\r";
            echo '<br/>';
            
            echo '<br/>';
            
            // Prepoznaj respondenta
            echo '<span class="nastavitveSpan3 bold" ><label>' . $lang['srv_user'] . Help :: display('srv_user_from_cms') . ':</label></span>';
            echo '            <label for="user_1"><input type="radio" name="user_from_cms" value="1" id="user_1"' . ($row['user_from_cms'] == 1 ? ' checked="checked"' : '') . ' onclick="javascript:checkcookie(); $(\'#user_1_email\').removeAttr(\'disabled\')" />' . $lang['srv_respondent'] . '</label>' . "\n\r";
            echo '            <label for="user_2"><input type="radio" name="user_from_cms" value="2" id="user_2"' . ($row['user_from_cms'] == 2 ? ' checked="checked"' : '') . ' onclick="javascript:checkcookie(); $(\'#user_1_email\').removeAttr(\'disabled\')" />' . $lang['srv_vnasalec'] . '</label>' . "\n\r";
            echo '            <label for="user_0"><input type="radio" name="user_from_cms" value="0" id="user_0"' . ($row['user_from_cms'] == 0 ? ' checked="checked"' : '') . ' onclick="javascript:checkcookie(); $(\'#user_1_email\').attr(\'disabled\', true); _user_from_cms(); " />' . $lang['no1'] . '</label>' . "\n\r";
            echo '<br/>';
            
            // Ob izpolnjevanju prikazi email
            echo '<div id="cms_email">';
            echo '  <span class="nastavitveSpan3 bold" >&nbsp;</span><label>' . $lang['srv_user_cms_show'] . Help :: display('srv_user_from_cms_email') . ':</label>';
            echo '            <label for="user_1_email"><input type="checkbox" name="user_from_cms_email" value="1" id="user_1_email"' . ($row['user_from_cms_email'] == 1 ? ' checked="checked"' : '') . ' '.($row['user_from_cms']>0?'':' disabled="true" ').'/>' . $lang['srv_user_cms_email'] . '</label>' . "\n\r";
            echo '</div>';
            
            echo '</fieldset>';
                                
            // Masovno vnasanje - modul Vnos
            echo '<fieldset id="vnos_modul" style="margin-top: 15px !important;"><legend>'.$lang['srv_vrsta_survey_type_5'].'</legend>';

            echo '  <p>'.$lang['srv_vnos_navodila'].'</p>';

            echo '  <span class="nastavitveSpan3 bold"><label>' . $lang['srv_mass_input']. Help :: display('srv_mass_insert') . ':</label></span>';
            echo '            <input type="radio" name="mass_insert" value="1" id="mass_insert_1"' . ($row['mass_insert'] == 1 ? ' checked="checked"' : '') . ' /><label for="mass_insert_1">' . $lang['srv_mass_input_1'] . '</label>' . "\n\r";
            echo '            <input type="radio" name="mass_insert" value="0" id="mass_insert_0"' . ($row['mass_insert'] == 0 ? ' checked="checked"' : '') . ' /><label for="mass_insert_0">' . $lang['srv_mass_input_0'] . '</label>' . "\n\r";

            echo '</fieldset>';
                        
            // For modul maza, show all cookie settings
            $isMaza = (SurveyInfo::checkSurveyModule('maza')) ? 1 : 0;
            
            ?> <script>
                
                function checkcookie () {

                    if ($('input[name=cookie]:checked').val() == '-1' && $('input[name=user_from_cms]:checked').val() == '0' && <?echo $row['user_base'];?> != 1 && <?echo $isMaza;?> != 1) {
                        $('input[name=cookie_return]').attr('disabled', true);
                        $('input[name=return_finished]').attr('disabled', true);
                        $('.no-cookie').css('visibility', 'hidden');
                    } 
                    else {
                        $('input[name=cookie_return]').attr('disabled', false);
                        $('input[name=return_finished]').attr('disabled', false);
                        $('.no-cookie').css('visibility', 'visible');
                    }
                    
                    if ( $('input[name=cookie_return]:checked').val() == 1 ) {
                        $('.no-cookie-return').css('visibility', 'hidden');
                    } 
                    else {
                        $('.no-cookie-return').css('visibility', 'visible');
                    }
                    
                    if ( $('input[name=user_from_cms]:checked').val() == 0 ) {
                        $('#cms_email').css('visibility', 'hidden');
                    } 
                    else {
                        $('#cms_email').css('visibility', 'visible');
                    }
                    
                    if ( $('input[name=user_from_cms]:checked').val() == 2 ) {
                        $('#vnos_modul').show();
                    } 
                    else {
                        $('#vnos_modul').hide();
                    }
                }

                checkcookie();
                cookie_alert();

            </script> <?

            $stringDostopAvtor = "SELECT count(*) as isAvtor FROM srv_dostop WHERE ank_id = '" . $this->anketa . "' AND (uid='" . $global_user_id . "' OR uid IN (SELECT user FROM srv_dostop_manage WHERE manager='$global_user_id' ))";
            $sqlDostopAvtor = sisplet_query($stringDostopAvtor);
            $rowDostopAvtor = mysqli_fetch_assoc($sqlDostopAvtor);
            $avtorRow = SurveyInfo::getInstance()->getSurveyRow();
                

            echo '<br/>';

            echo '<fieldset>';
            
            echo '<legend>' . $lang['access'] . '</legend>';
            echo '<span class="nastavitveSpan3" ><label for="odgovarja">' . $lang['srv_izpolnjujejo'] . Help :: display('srv_izpolnjujejo') .': </label></span>';
            echo '            <select name="odgovarja" id="odgovarja" onchange="javascript:_odgovarja();" class="spaceLeft">';
            echo '              <option value="4"' . ($row['odgovarja'] == 4 ? ' selected="selected"' : '') . '>' . $lang['forum_hour_all'] . '</option>';
            echo '              <option value="3"' . ($row['odgovarja'] == 3 ? ' selected="selected"' : '') . '>' . $lang['forum_registered'] . '</option>';
            echo '              <option value="2"' . ($row['odgovarja'] == 2 ? ' selected="selected"' : '') . '>' . $lang['forum_clan'] . '</option>';
            echo '              <option value="1"' . ($row['odgovarja'] == 1 ? ' selected="selected"' : '') . '>' . $lang['forum_manager'] . '</option>';
            echo '              <option value="0"' . ($row['odgovarja'] == 0 ? ' selected="selected"' : '') . '>' . $lang['forum_admin'] . '</option>';
            echo '            </select>';
            echo '<br />';
            echo '<script language="javascript">'."\n";
            echo '  function _user_from_cms() {'."\n";
            echo '    document.settingsanketa_' . $row['id'] . '.odgovarja.value = \'4\''."\n";
            echo '  }'."\n";
            echo '  function _odgovarja() {'."\n";
            echo '    if (document.settingsanketa_' . $row['id'] . '.odgovarja.value != \'4\' && document.settingsanketa_' . $row['id'] . '.user_from_cms[2].checked == true) {'."\n";
            echo '      document.settingsanketa_' . $row['id'] . '.user_from_cms[0].checked = true;'."\n";
            echo '    }'."\n";
            echo '}'."\n";
            echo '</script>'."\n";
            
            echo '<br/>';
            
            echo '<span class="nastavitveSpan3 bold" ><label>' . $lang['srv_block_ip'] . Help :: display('srv_block_ip') . ':</label></span>';
            echo '            <label for="block_ip_0"><input type="radio" name="block_ip" value="0" id="block_ip_0"' . ($row['block_ip'] == 0 ? ' checked="checked"' : '') . ' onChange="$(\'#block_ip_warning\').hide();" />' . $lang['no1'] . '</label>' . "\n\r";
            echo '            <label for="block_ip_10"><input type="radio" name="block_ip" value="10" id="block_ip_10"' . ($row['block_ip'] == 10 ? ' checked="checked"' : '') . ' onChange="$(\'#block_ip_warning\').show();" />10 min</label>' . "\n\r";
            echo '            <label for="block_ip_20"><input type="radio" name="block_ip" value="20" id="block_ip_20"' . ($row['block_ip'] == 20 ? ' checked="checked"' : '') . ' onChange="$(\'#block_ip_warning\').show();" />20 min</label>' . "\n\r";
            echo '            <label for="block_ip_60"><input type="radio" name="block_ip" value="60" id="block_ip_60"' . ($row['block_ip'] == 60 ? ' checked="checked"' : '') . ' onChange="$(\'#block_ip_warning\').show();" />60 min</label>' . "\n\r";
            echo '            <label for="block_ip_720"><input type="radio" name="block_ip" value="720" id="block_ip_720"' . ($row['block_ip'] == 720 ? ' checked="checked"' : '') . ' onChange="$(\'#block_ip_warning\').show();" />12 '.$lang['hour_hours2'].'</label>' . "\n\r";
            echo '            <label for="block_ip_1440"><input type="radio" name="block_ip" value="1440" id="block_ip_1440"' . ($row['block_ip'] == 1440 ? ' checked="checked"' : '') . ' onChange="$(\'#block_ip_warning\').show();" />24 '.$lang['hour_hours2'].'</label>' . "\n\r";
            echo '<br /><br /><span id="block_ip_warning" class="bold" style="margin-left:117px; '.($row['block_ip'] == 0 ? ' display:none;' : '').'">'.$lang['srv_block_ip_warning'].'</span>';             

            echo '</fieldset>';


            echo '<br/>';
            
            
            // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
            global $global_user_id;
            $userAccess = UserAccess::getInstance($global_user_id);

            // dodajanje gesel za anketo
            echo '<fieldset><legend>'.$lang['srv_password'].' '.Help::display('srv_dostop_password').'</legend>';

            if(!$userAccess->checkUserAccess($what='password')){
                $userAccess->displayNoAccess($what='password');
            }
            else{

                echo '<div id="password">';
                
                $ss = new SurveySkupine($this->anketa);
                $spr_id = $ss->hasSkupine(2);
                
                echo '<input type="hidden" id="skupine_spr_id" value="'.$spr_id.'"></input>';
                
                // Preprecimo submit na enter
                echo '<script>';
                ?>
                    $('form[name=settingsanketa_'+<?echo $this->anketa;?>+']').on('keyup keypress', function(e) {
                        var keyCode = e.keyCode || e.which;
                        if (keyCode === 13) { 
                            e.preventDefault();
                            return false;
                        }
                    });
                <?
                echo '</script>';
                
                // dodajanje gesel za anketo
                if($spr_id > 0){
                    $vrednosti = $ss->getVrednosti($spr_id);
                    if($vrednosti != 0){
                        foreach($vrednosti as $vrednost){
                            echo '<p>';
                            echo '<strong>'.$vrednost['naslov'].'</strong><span class="faicon delete_circle icon-orange_link spaceLeft" style="margin-bottom:1px;" onclick="delete_skupina(\'2\', \''.$vrednost['id'].'\');"></span>';
                            echo '</p>';
                        }
                    }
                }
                echo '<p class="add_skupina_button"><input type="text" name="skupina" autocomplete="off" onKeyUp="add_skupina_enter(\'2\', event);" /> <input type="button" value="'.$lang['add'].'" onclick="add_skupina(\'2\');" /></p>';
                
                echo '<span class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" href="#" onClick="display_add_passwords_mass();">'.$lang['srv_password_add_mass'].'</a></span>';

                echo '</div>';
            }
			
			echo '</fieldset>';		
		}

		/*Trajanje*/

		if ($_GET['a'] == 'trajanje') {
			echo '<div >';
			echo '<input type="hidden" value="' . $this->anketa . '" name="anketa" >';
			$this->DisplayNastavitveTrajanje();
			$this->DisplayNastavitveMaxGlasov();
			echo '</form>';
			echo '<br/>';
			if (isset($_GET['f'])) {
				switch ($_GET['f']) {
					case 'vabila_settings':
						$url =$site_url . 'admin/survey/index.php?anketa='.$this->anketa.'&a=vabila&m=settings';
						break;
				}
				if (isset($url)) {
					echo '<span class="buttonwrapper floatLeft spaceRight"><a class="ovalbutton ovalbutton_gray" href="'.$url.'"><span>'.$lang['back'] . '</span></a></span>';
				}
			}
			echo '<span class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" onclick="submitSurveyDuration();return false;" href="#"><span>';
			echo $lang['edit1337'] . '</span></a></span>';
			
			/*
			$http_referer = parse_url($_SERVER['HTTP_REFERER']); //If yes, parse referrer
			$referer_url = $http_referer['query'];
			if (preg_match('/anketa='.$this->anketa.'&a'.A_VABILA.'/', $referer_url)) {
				echo '<div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="'.$_SERVER['HTTP_REFERER'].'"><span>';
				echo $lang['srv_back_to_email'] . '</span></a></div>';
				echo '</div>';
			}
			*/
		}

		/*Respondenti*/

		if ($_GET['a'] == 'resp') {
			$this->respondenti_iz_baze($row);
		}

		/*Komentarji*/

		if ($_GET['a'] == 'urejanje') {
			// tukaj bom dodal še kontrolo na Avtorja ankete, tako da avtor lahko vedno spreminja urejanje (gorazd,1.9.2009)
			$stringDostopAvtor = "SELECT count(*) as isAvtor FROM srv_dostop WHERE ank_id = '" . $this->anketa . "' AND uid='" . $global_user_id . "'";
			$sqlDostopAvtor = sisplet_query($stringDostopAvtor);
			$rowDostopAvtor = mysqli_fetch_assoc($sqlDostopAvtor);
			if ($admin_type == 0 || $rowDostopAvtor['isAvtor']) {

				SurveySetting::getInstance()->Init($this->anketa);
				
				$survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment');
				$survey_comment_showalways = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_showalways');
				$question_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment');
				
				$survey_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewadminonly');
				$survey_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewauthor');
				$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
				$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');
				$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');
				$question_resp_comment_inicialke = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke');
				$question_resp_comment_inicialke_alert = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke_alert');

				$question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');
				$srv_qct = SurveySetting :: getInstance()->getSurveyMiscSetting('question_comment_text');
				
				$question_note_view = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_view');
				$question_note_write = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_write');
				
				$question_resp_comment_show_open = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_show_open');

				$survey_comment_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_resp');
				$survey_comment_showalways_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_showalways_resp');
				$survey_comment_viewadminonly_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewadminonly_resp');
				$survey_comment_viewauthor_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewauthor_resp');			
				
				$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
				$addfieldposition = SurveySetting::getInstance()->getSurveyMiscSetting('addfieldposition');
				$commentmarks = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks');
				$commentmarks_who = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks_who');
				$comment_history = SurveySetting::getInstance()->getSurveyMiscSetting('comment_history');
				
				$srvlang_srv_question_respondent_comment = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_'.'srv_question_respondent_comment'.'');
				if ($srvlang_srv_question_respondent_comment == '') $srvlang_srv_question_respondent_comment = $lang['srv_question_respondent_comment'];
				
				$preview_disableif = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disableif');
				$preview_disablealert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disablealert');
				$preview_displayifs = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayifs');
				$preview_displayvariables = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayvariables');
				$preview_hidecomment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hidecomment');				
				$preview_hide_survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hide_survey_comment');				
				$preview_survey_comment_showalways = SurveySetting::getInstance()->getSurveyMiscSetting('preview_survey_comment_showalways');				
				$preview_disable_test_insert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disable_test_insert');				
				
				if ( isset($_GET['show']) && $_GET['show']=='on_alert' ) {
					echo '<div class="comments_on_alert google_yellow">'.$lang['srv_comments_on_alert'].' <a href="ajax.php?anketa='.$this->anketa.'&a=comments_onoff&do=off">'.$lang['srv_off'].'.</a> '.$lang['srv_comments_on_alert2'].' <a href="https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/komentarji/?from1ka=1" target="_blank">'.$lang['srv_anl_more'].'</a></div>';
				}
				
				$css_width = '';
				if ($survey_comment != "") {
					$css_width = 'min-height:250px;width:45% !important;';
					$css_width2 = 'width:45% !important;';
				}
				
				echo '<fieldset style="float: left;'.$css_width.'"><legend>'.$lang['comments'].'</legend>';

				echo '<p><input type="checkbox" name="comments_default" id="comments_admin1" onclick="comments_admin_toggle(\'1\')" admin_on="false" /><label for="comments_admin1" style="font-weight:500; vertical-align:inherit"> '.$lang['srv_comments_admin_on1'].'</label><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;('.$lang['srv_comments_admin_note1'].')</p>';
				echo '<p><input type="checkbox" name="comments_resp2" id="comments_resp2" onclick="comments_resp_toggle(\'2\')" resp_on="false" /><label for="comments_resp2" style="font-weight:500; vertical-align:inherit"> '.$lang['srv_comments_resp_on2'].'</label><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;('.$lang['srv_comments_resp_note2'].')</p>';		
				echo '<p><input type="checkbox" name="comments_default" id="comments_admin2" onclick="comments_admin_toggle(\'2\')" admin_on="false" /><label for="comments_admin2" style="font-weight:500; vertical-align:inherit"> '.$lang['srv_comments_admin_on2'].'</label><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;('.$lang['srv_comments_admin_note2'].')</p>';
				echo '<p><input type="checkbox" name="comments_default" id="comments_resp" onclick="comments_resp_toggle(\'1\')" resp_on="false" /><label for="comments_resp" style="font-weight:500; vertical-align:inherit"> '.$lang['srv_comments_resp_on'].'</label><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;('.$lang['srv_comments_resp_note'].')</p>';		

				//echo '<input type="radio" name="comments_defalut" id="comments_default_on" disabled /> <a href="#" onclick="comments_default_on(); return false;">'.$lang['srv_comments_default_on'].'</a><br />';
				//echo '<input type="radio" name="comments_defalut" id="comments_on" disabled /> '.$lang['srv_comments_on'].'<br />';
				//echo '<input type="radio" name="comments_defalut" id="comments_default_off" disabled /> <a href="#" onclick="comments_default_off(); return false;">'.$lang['srv_comments_default_off'].'</a>';
				
				$d = new Dostop();
				
				$sqlc = sisplet_query("SELECT COUNT(s.id) AS count FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.thread > '0'");
				if (!$sqlc) echo mysqli_error($GLOBALS['connect_db']);
				$rowc = mysqli_fetch_array($sqlc);
				if ($rowc['count'] > 0 && $d->checkDostopSub('test')) {				

					echo '<p>';
					echo '<a href="'.$site_url . 'admin/survey/index.php?anketa=' . $row['id'] .'&a=komentarji" title="' . $lang['srv_view_comment'] . '" >';
					echo '<div class="fa-stack"><span class="faicon comments fa-stack-1x" icon-blue" title="'.$lang['srv_view_comment'].'"></span></div>';
					echo '&nbsp;'.$lang['srv_view_comment'];
					echo '</a>';
				}
				
				echo '</p>';

				
				echo '<p><a href="index.php?anketa='.$this->anketa.'&a=komentarji"><span class="bold">'.$lang['comments'].'</span></a></p>';
				echo '<p><a href="index.php?anketa='.$this->anketa.'&a=vabila"><span class="bold">'.$lang['srv_vabila'].'</span></a></p>';
				
                echo '<p><a href="#" onclick="$(\'#komentarji_napredno\').fadeToggle(); $(\'#komentarji_napredno_arrow\').toggleClass(\'arrow2_d\'); $(\'#komentarji_napredno_arrow\').toggleClass(\'arrow2_u\'); return false;">';
                echo '  <span class="bold">'.$lang['srv_detail_settings'].'&nbsp;</span>';
                echo '  <span id="komentarji_napredno_arrow" class="faicon arrow2_d"></span>';
                echo '</a></p>';
				
				echo '</fieldset>';
				
				
				?>
				<script>
					$(function() {
						if ( check_comments_admin(1) ) { 
							$('#comments_admin1').attr('admin_on', 'true');
							$('#comments_admin1').attr('checked', true);
						} else {
							$('#comments_admin1').attr('admin_on', 'false');
							$('#comments_admin1').attr('checked', false);
						}
						if ( check_comments_admin(2) ) {
							$('#comments_admin2').attr('admin_on', 'true');
							$('#comments_admin2').attr('checked', true);
						} else {
							$('#comments_admin2').attr('admin_on', 'false');
							$('#comments_admin2').attr('checked', false);
						}
						
						if ( check_comments_resp(1) ) {
							$('#comments_resp').attr('resp_on', 'true');
							$('#comments_resp').attr('checked', true);
						} else {
							$('#comments_resp').attr('resp_on', 'false');
							$('#comments_resp').attr('checked', false);
						}
						if ( check_comments_resp(2) ) {
							$('#comments_resp2').attr('resp_on', 'true');
							$('#comments_resp2').attr('checked', true);
						} else {
							$('#comments_resp2').attr('resp_on', 'false');
							$('#comments_resp2').attr('checked', false);
						}
						
						if ( ( !check_comments_admin() && !check_comments_admin_off() ) || ( !check_comments_resp() && !check_comments_resp_off() ) ) {
							$('#komentarji_napredno').show();
						}
						
					});
				</script>
				<?
				
				if ($survey_comment != "") {
					
					echo '<fieldset style="float:right;margin-left:13px !important;margin-right:0px !important;'.$css_width2.'"><legend>'.$lang['srv_admin_s_comments'].'</legend>';
					//echo '<form name="comment_send" action="ajax.php?a=comment_send&anketa='.$this->anketa.'">';
					echo '<textarea name="comment_send" style="width:50%; height:60px"></textarea>';
					
					echo '<p><input type="checkbox" id="srv_c_alert" name="srv_c_alert" checked value="1" /><label for="srv_c_alert"> '.$lang['srv_c_alert'].'</label></p>';
					echo '<p><input type="checkbox" id="srv_c_to_mail" name="srv_c_to_mail" value="1" onchange="$(\'#prejemniki\').toggle();" /><label for="srv_c_to_mail"> '.$lang['srv_c_to_mail'].'</label></p>';
					
					echo '<p id="prejemniki" style="display:none">';
					$sqlp = sisplet_query("SELECT u.name, u.surname, u.email FROM srv_dostop d, users u WHERE d.uid=u.id AND ank_id='$this->anketa'");
					while ($rowp = mysqli_fetch_array($sqlp)) {
						echo '&nbsp;&nbsp;&nbsp;&nbsp; <input type="checkbox" name="mails[]" value="'.$rowp['email'].'" checked="checked" id="'.$rowp['email'].'" /><label for="'.$rowp['email'].'"> '.$rowp['name'].' '.$rowp['surname'].' ('.$rowp['email'].')</label><br />';
					}
					echo '</p>';
					
					echo '
					<div class="buttonwrapper" style="float:left;">
					<a class="ovalbutton ovalbutton_orange btn_savesettings" onclick="document.settingsanketa_'.$this->anketa.'.submit(); return false;" href="#">
					'.$lang['add'].'
					</a>
					</div>';
					//echo '</form>';
					echo '</fieldset>';
					echo '<br />';
					
				}
				
				echo '<div id="komentarji_napredno" '.($_GET['advanced_expanded']==1 ? '' : ' style="display:none;"').'>';
				
				echo '<br class="clr" />';
				echo '<br />'; 

				echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
				echo $lang['edit1337'] . '</span></a></div></span>';
				echo '<div class="clr"></div>';
				echo '<br class="clr" />';
				
				echo '<fieldset class="wide"><legend>'.$lang['srv_preview_defaults'].'</legend>';
				
				echo '<span class="nastavitveSpan1"><label for="disableif">'.$lang['srv_disableif'].': </span><input type="hidden" name="preview_disableif" value=""><input type="checkbox" value="1" '.($preview_disableif==1?' checked':'').' name="preview_disableif" id="disableif">';
				echo ' </label><br>';
				echo '<span class="nastavitveSpan1"><label for="disablealert">'.$lang['srv_disablealert'].': </span><input type="hidden" name="preview_disablealert" value=""><input type="checkbox" value="1" '.($preview_disablealert==1?' checked':'').' name="preview_disablealert" id="disablealert">';
				echo ' </label><br>';
				echo '<span class="nastavitveSpan1"><label for="displayifs">'.$lang['srv_displayifs'].': </span><input type="hidden" name="preview_displayifs" value=""><input type="checkbox" value="1" '.($preview_displayifs==1?' checked':'').' name="preview_displayifs" id="displayifs">';
				echo ' </label><br>';
				echo '<span class="nastavitveSpan1"><label for="displayvariables">'.$lang['srv_displayvariables'].': </span><input type="hidden" name="preview_displayvariables" value=""><input type="checkbox" value="1" '.($preview_displayvariables==1?' checked':'').' name="preview_displayvariables" id="displayvariables">';
				echo ' </label><br>';		
				echo '<span class="nastavitveSpan1"><label for="hidecomment">'.$lang['srv_preview_comments2'].': </span><input type="hidden" name="preview_hidecomment" value=""><input type="checkbox" value="1" '.($preview_hidecomment==1?' checked':'').' name="preview_hidecomment" id="hidecomment">';
				echo ' </label><br>';
				echo '<span class="nastavitveSpan1"><label for="hidesurveycomment">'.$lang['srv_preview_hide_survey_comment'].': </span><input type="hidden" name="preview_hide_survey_comment" value=""><input type="checkbox" value="1" '.($preview_hide_survey_comment==1?' checked':'').' name="preview_hide_survey_comment" id="hidesurveycomment">';
				echo ' </label><br>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_preview_survey_comment_showalways'] . ':</label></span>';
				echo '<label for="preview_survey_comment_showalways_0"><input type="radio" name="preview_survey_comment_showalways" value="0" id="preview_survey_comment_showalways_0" ' . ($preview_survey_comment_showalways == 0 ? ' checked' : '') . '/>' . $lang['no'] . '</label> ';
				echo '<label for="preview_survey_comment_showalways_1"><input type="radio" name="preview_survey_comment_showalways" value="1" id="preview_survey_comment_showalways_1" ' . ($preview_survey_comment_showalways == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';			
				echo '<br>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_preview_disable_test_insert'] . ':</label></span>';
				echo '<label for="preview_disable_test_insert_1"><input type="radio" name="preview_disable_test_insert" value="1" id="preview_disable_test_insert_1" ' . ($preview_disable_test_insert == 1 ? ' checked' : '') . '/>' . $lang['no'] . '</label> ';			
				echo '<label for="preview_disable_test_insert_0"><input type="radio" name="preview_disable_test_insert" value="0" id="preview_disable_test_insert_0" ' . ($preview_disable_test_insert == 0 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
				echo '<br>';
				
				echo '</fieldset><br>';
				
				echo '<fieldset class="wide"><legend>'.$lang['srv_admin_s_comments'].'<span>'.$lang['srv_admin_s_comments_txt'].'</span></legend>';

				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_write'] . ':</label></span>';
				echo '<select name="survey_comment">';
				echo '<option value=""'.($survey_comment==''?' selected':'').'>'.$lang['srv_nihce'].'</option>';
				//echo '<option value="4"'.($survey_comment==4?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($survey_comment==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($survey_comment==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($survey_comment==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($survey_comment=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select>';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_view'] . ':</label></span>';
				echo '<select name="survey_comment_viewadminonly">';
				//echo '<option value="4"'.($survey_comment_viewadminonly==4?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($survey_comment_viewadminonly==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($survey_comment_viewadminonly==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($survey_comment_viewadminonly==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($survey_comment_viewadminonly=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select> ';
				echo $lang['srv_comments_viewauthor'];
				echo '<input type="hidden" name="survey_comment_viewauthor" value=""><input type="checkbox" name="survey_comment_viewauthor" value="1" '.($survey_comment_viewauthor==1?' checked':'').' />';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_survey_comment_show'] . ':</label></span>';
				echo '<label for="survey_comment_showalways_0"><input type="radio" name="survey_comment_showalways" value="0" id="survey_comment_showalways_0" ' . ($survey_comment_showalways == 0 ? ' checked' : '') . '/>' . $lang['no'] . '</label> ';
				echo '<label for="survey_comment_showalways_1"><input type="radio" name="survey_comment_showalways" value="1" id="survey_comment_showalways_1" ' . ($survey_comment_showalways == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
				echo '</fieldset>';
				echo '<br>';
				
				echo '<fieldset class="wide">';
				echo '<legend>' . $lang['srv_admin_q_notes'] . '<span>'.$lang['srv_admin_q_notes_txt'].'</span></legend>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_q_note_view'] . ':</label></span>';
				echo '<select name="question_note_view">';
				echo '<option value=""'.($question_note_view==''?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($question_note_view==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($question_note_view==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($question_note_view==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($question_note_view=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select>';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_q_note_write'] . ':</label></span>';
				echo '<select name="question_note_write">';
				echo '<option value=""'.($question_note_write==''?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($question_note_write==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($question_note_write==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($question_note_write==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($question_note_write=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select> ';
				echo '</fieldset>';
				echo '<br>';
			
			
				echo '<fieldset class="wide">';
				echo '<legend>' . $lang['srv_admin_q_comments'] . '<span>'.$lang['srv_admin_q_comments_txt'].'</span></legend>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_write'] . ':</label></span>';
				echo '<select name="question_comment">';
				echo '<option value=""'.($question_comment==''?' selected':'').'>'.$lang['srv_nihce'].'</option>';
				echo '<option value="4"'.($question_comment==4?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($question_comment==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($question_comment==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($question_comment==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($question_comment=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select>';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_view'] . ':</label></span>';
				echo '<select name="question_comment_viewadminonly">';
				echo '<option value="4"'.($question_comment_viewadminonly==4 || $question_comment_viewadminonly==''?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($question_comment_viewadminonly==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($question_comment_viewadminonly==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($question_comment_viewadminonly==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($question_comment_viewadminonly=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select> ';
				echo $lang['srv_comments_viewauthor'];
				echo '<input type="hidden" name="question_comment_viewauthor" value=""><input type="checkbox" name="question_comment_viewauthor" value="1" '.($question_comment_viewauthor==1?' checked':'').' />';
				echo '</fieldset>';
				echo '<br>';
				

				echo '<fieldset class="wide">';
				echo '<legend>' . $lang['srv_comments_respondents'] . '<span>'.$lang['srv_comments_respondents_txt'].'</span></legend>';
				
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_q_comment'] . ':</label></span>';
				echo '<label for="question_resp_comment_0"><input type="radio" name="question_resp_comment" value="0" id="question_resp_comment_0" ' . ($question_resp_comment == 0 ? ' checked' : '') . '/>' . $lang['no'] . '</label> ';
				echo '<label for="question_resp_comment_1"><input type="radio" name="question_resp_comment" value="1" id="question_resp_comment_1" ' . ($question_resp_comment == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_view'] . ':</label></span>';
				echo '<select name="question_resp_comment_viewadminonly">';
				//echo '<option value=""'.($question_resp_comment_viewadminonly==''?' selected':'').'>'.$lang['srv_nihce'].'</option>';
				echo '<option value="4"'.($question_resp_comment_viewadminonly==4 || $question_resp_comment_viewadminonly==''?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($question_resp_comment_viewadminonly==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($question_resp_comment_viewadminonly==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($question_resp_comment_viewadminonly==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($question_resp_comment_viewadminonly=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select>';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_show_open'] . ':</label></span>';
				echo '<label for="question_resp_comment_show_open_0"><input type="radio" name="question_resp_comment_show_open" value="" id="question_resp_comment_show_open_0" ' . ($question_resp_comment_show_open == '' ? ' checked' : '') . '/>' . $lang['forma_settings_open'] . '</label> ';
				echo '<label for="question_resp_comment_show_open_1"><input type="radio" name="question_resp_comment_show_open" value="1" id="question_resp_comment_show_open_1" ' . ($question_resp_comment_show_open == '1' ? ' checked' : '') . '/>' . $lang['forma_settings_closed'] . '</label> ';
				echo '<br>';
				echo '<span class="nastavitveSpan1"><label>' .$lang['text'].' "'. $lang['srv_question_respondent_comment'] . '":</label></span>';
				echo '<input type="text" name="srvlang_srv_question_respondent_comment" value="'.$srvlang_srv_question_respondent_comment.'" style="width:300px">';
				echo '<input type="hidden" name="extra_translations" value="1">';
				echo '</fieldset>';
				echo '<br>';
				
				echo '<fieldset class="wide">';
				echo '<legend>' . $lang['srv_comments_respondents'] . '<span> - '.$lang['srv_extra_settings'].'</span></legend>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_q_inicialke'] . ':</label></span>';
				echo '<label for="question_resp_comment_inicialke_0"><input type="radio" name="question_resp_comment_inicialke" value="0" id="question_resp_comment_inicialke_0" ' . ($question_resp_comment_inicialke == 0 ? ' checked' : '') . '/>' . $lang['no'] . '</label> ';
				echo '<label for="question_resp_comment_inicialke_1"><input type="radio" name="question_resp_comment_inicialke" value="1" id="question_resp_comment_inicialke_1" ' . ($question_resp_comment_inicialke == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_q_inicialke_alert'] . ':</label></span>';
				echo '<label for="question_resp_comment_inicialke_alert_0"><input type="radio" name="question_resp_comment_inicialke_alert" value="0" id="question_resp_comment_inicialke_alert_0" ' . ($question_resp_comment_inicialke_alert == 0 ? ' checked' : '') . '/>' . $lang['srv_reminder_off2'] . '</label> ';
				echo '<label for="question_resp_comment_inicialke_alert_1"><input type="radio" name="question_resp_comment_inicialke_alert" value="1" id="question_resp_comment_inicialke_alert_1" ' . ($question_resp_comment_inicialke_alert == 1 ? ' checked' : '') . '/>' . $lang['srv_reminder_soft2'] . '</label> ';
				echo '<label for="question_resp_comment_inicialke_alert_2"><input type="radio" name="question_resp_comment_inicialke_alert" value="2" id="question_resp_comment_inicialke_alert_2" ' . ($question_resp_comment_inicialke_alert == 2 ? ' checked' : '') . '/>' . $lang['srv_reminder_hard2'] . '</label> ';
				/*echo '<br/>';
				echo '<div class="nastavitveSpan1" style="height:auto; float:left;"><label>' . $lang['text'] . ':</label></div>';
				echo '<textarea id="sys_survey_misc_question_comment_text" name="question_comment_text" type="text" srv_survey_misc="true" maxlength="255">'.$srv_qct.'</textarea>';
				echo '&nbsp;<span id="sys_survey_misc_question_comment_text_chars">' . strlen($srv_qct) . '/250</span>' . "\n\r";*/
				echo '</fieldset>';
				echo '<br>';
				
				echo '<fieldset class="wide"><legend>'.$lang['srv_comments_respondents'].'<span>'.$lang['srv_resp_s_comments_txt'].'</span></legend>';

				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_write'] . ':</label></span>';
				echo '<select name="survey_comment_resp">';
				echo '<option value=""'.($survey_comment_resp==''?' selected':'').'>'.$lang['srv_nihce'].'</option>';
				echo '<option value="4"'.($survey_comment_resp==4?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($survey_comment_resp==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($survey_comment_resp==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($survey_comment_resp==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($survey_comment_resp=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select>';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_view'] . ':</label></span>';
				echo '<select name="survey_comment_viewadminonly_resp">';
				echo '<option value="4"'.($survey_comment_viewadminonly_resp==4?' selected':'').'>'.$lang['move_all'].'</option>';
				echo '<option value="3" '.($survey_comment_viewadminonly_resp==3 ?' selected':'').'>'.$lang['forum_registered'].'</option>';
				echo '<option value="2" '.($survey_comment_viewadminonly_resp==2 ?' selected':'').'>'.$lang['forum_clan'].'</option>';
				echo '<option value="1" '.($survey_comment_viewadminonly_resp==1 ?' selected':'').'>'.$lang['forum_manager'].'</option>';
				echo '<option value="0" '.($survey_comment_viewadminonly_resp=='0' ?' selected':'').'>'.$lang['forum_admin'].'</option>';
				echo '</select> ';
				echo $lang['srv_comments_viewauthor'];
				echo '<input type="hidden" name="survey_comment_viewauthor_resp" value=""><input type="checkbox" name="survey_comment_viewauthor_resp" value="1" '.($survey_comment_viewauthor_resp==1?' checked':'').' />';
				/*echo '<br />';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_survey_comment_show'] . ':</label></span>';
				echo '<input type="radio" name="survey_comment_showalways_resp" value="0" id="survey_comment_showalways_resp_0" ' . ($survey_comment_showalways_resp == 0 ? ' checked' : '') . '/><label for="survey_comment_showalways_resp_0">' . $lang['no'] . '</label> ';
				echo '<input type="radio" name="survey_comment_showalways_resp" value="1" id="survey_comment_showalways_resp_1" ' . ($survey_comment_showalways_resp == 1 ? ' checked' : '') . '/><label for="survey_comment_showalways_resp_1">' . $lang['yes'] . '</label> ';
				*/
				echo '</fieldset>';
				echo '<br />';
				
				echo '<fieldset class="wide">';
				echo '<legend>' . $lang['srv_settings_komentarji'] . '</legend>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['orderby'] . ':</label></span>';
				echo '<label for="sortpostorder_0"><input type="radio" name="sortpostorder" value="0" id="sortpostorder_0" ' . ($sortpostorder == 0 ? ' checked' : '') . '/>' . $lang['forum_asc'] . '</label> ';
				echo '<label for="sortpostorder_1"><input type="radio" name="sortpostorder" value="1" id="sortpostorder_1" ' . ($sortpostorder == 1 ? ' checked' : '') . '/>' . $lang['forum_desc'] . '</label> ';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_add_comment'] . ':</label></span>';
				echo '<label for="addfieldposition_0"><input type="radio" name="addfieldposition" value="0" id="addfieldposition_0" ' . ($addfieldposition == 0 ? ' checked' : '') . '/>' . $lang['srv_polozaj_bottom'] . '</label> ';
				echo '<label for="addfieldposition_1"><input type="radio" name="addfieldposition" value="1" id="addfieldposition_1" ' . ($addfieldposition == 1 ? ' checked' : '') . '/>' . $lang['srv_polozaj_top'] . '</label> ';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_marks'] . ':</label></span>';
				echo '<label for="commentmarks_0"><input type="radio" name="commentmarks" value="0" id="commentmarks_0" ' . ($commentmarks == 0 ? ' checked' : '') . '/>' . $lang['srv_comments_marks_0'] . '</label> ';
				echo '<label for="commentmarks_1"><input type="radio" name="commentmarks" value="1" id="commentmarks_1" ' . ($commentmarks == 1 ? ' checked' : '') . '/>' . $lang['srv_comments_marks_1'] . '</label> ';
				echo '<br/>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comments_marks_who'] . ':</label></span>';
				echo '<label for="commentmarks_who_0"><input type="radio" name="commentmarks_who" value="0" id="commentmarks_who_0" ' . ($commentmarks_who == 0 ? ' checked' : '') . '/>' . $lang['srv_comments_marks_who_1'] . '</label> ';
				echo '<label for="commentmarks_who_1"><input type="radio" name="commentmarks_who" value="1" id="commentmarks_who_1" ' . ($commentmarks_who == 1 ? ' checked' : '') . '/>' . $lang['srv_comments_marks_who_0'] . '</label> ';			
				echo '<br>';
				echo '<span class="nastavitveSpan1"><label>' . $lang['srv_comment_history'] . ':</label></span>';
				echo '<select name="comment_history">';
				echo '<option value="0" '.($comment_history=='0' || $comment_history=='' ? ' selected':'').'>'.$lang['srv_comment_history_1'].'</option>';
				echo '<option value="1" '.($comment_history==1 ?' selected':'').'>'.$lang['srv_comment_history_0'].'</option>';
				echo '<option value="2" '.($comment_history==2 ?' selected':'').'>'.$lang['srv_comment_history_2'].'</option>';
				echo '</select>';
				echo '<br/>';
				echo '</fieldset>';
				
				echo '<br />';
				echo '<fieldset><legend>' . $lang['srv_delete_comments'] . '</legend>';
				echo '<p><a href="#" onClick="delete_test_data();">'.$lang['srv_delete_comments3'].'</a> ('.$lang['srv_delete_comments_txt2'].')</p>';
				echo '</fieldset>';
				
				echo '</div>';	

				echo '<br class="clr" />';
			}
		}

		/*Dostop*/
		if ($_GET['a'] == 'dostop') {
			
			// tukaj bom dodal še kontrolo na Avtorja ankete, tako da avtor lahko vedno spreminja dostop (gorazd,1.9.2009)
			$stringDostopAvtor = "SELECT count(*) as isAvtor FROM srv_dostop WHERE ank_id = '" . $this->anketa . "' AND (uid='" . $global_user_id . "' OR uid IN (SELECT user FROM srv_dostop_manage WHERE manager='$global_user_id' ))";
			$sqlDostopAvtor = sisplet_query($stringDostopAvtor);
			$rowDostopAvtor = mysqli_fetch_assoc($sqlDostopAvtor);
			
			if ($admin_type <= $row['dostop'] || $rowDostopAvtor['isAvtor'] > 0) {
				
				echo '<fieldset><legend>' . $lang['srv_dostop_users'] . '' . Help :: display('srv_dostop_users'). '</legend>'."\n";

                if($admin_type == 0 || $admin_type == 1){
				    echo '<span id="dostop_active_show_1"><a href="#" onClick="dostopActiveShowAll(\'true\'); return false;">'.$lang['srv_dostop_show_all'].'</a></span>';
				    echo '<span id="dostop_active_show_2" class="displayNone"><a href="#" onClick="dostopActiveShowAll(\'false\'); return false;">'.$lang['srv_dostop_hide_all'].'</a></span>';
                }

				echo '<div id="dostop_users_list">';
				$this->display_dostop_users(0);
				echo '</div>';
				
				echo '</fieldset>';
							
                echo '<br class="clr" />';

				echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
				echo $lang['edit1337'] . '</span></a></div></span>';
				echo '<div class="clr"></div>';
				echo '<br class="clr" />';

				
				// Dodajanje uproabnikov preko e-maila
				echo '<fieldset><legend>'.$lang['srv_dostop_addusers'].'</legend>';
				echo '<div id="addmail">';
                     
                $this->display_add_survey_dostop();

				echo '</div>';			
				echo '</fieldset>';	
			}
		}

		/*Jezik*/
		if ($_GET['a'] == 'jezik') {
			global $admin_lang;
			
			$lang_admin = $row['lang_admin'];
			$lang_resp = $row['lang_resp'];
			
			$admin_lang = $lang;
			
			// ce ni default jezik, damo za osnovnega, default jezik
			global $resp_lang;
			$file = '../../lang/'.$row['lang_resp'].'.php';
			include($file);
			$resp_lang = $lang;
			//$lang_admin = $lang_resp;
			
			// nazaj na administrativnega
			$file = '../../lang/'.$lang_admin.'.php';
			include($file);
			
			
			echo '<fieldset class="wide"><legend>'.$lang['srv_extra_translations'].' ';
			
			if ($row['multilang'] == 1) {
				echo ' <select name="lang_id" onchange="window.location.href=\'index.php?anketa='.$this->anketa.'&a=jezik&lang_id=\'+this.value;">';
				
				$lang_id = (int)$_GET['lang_id'];
				if ($lang_id > 0)
					$lang_resp = $lang_id;
				
				$p = new Prevajanje($this->anketa);
				$p->dostop();
				$langs = $p->get_all_translation_langs();
				
				echo '<option value="" '.($lang_id==''?' selected':'').'>'.$resp_lang['language'].'</option>';
				
				foreach ($langs  AS $k => $l) {
					echo '<option value="'.$k.'" '.($lang_id==$k?' selected':'').'>'.$l.'</option>';
				}
				
				echo '</select>';
			}
			
			echo '</legend>';
			
			if ($row['multilang'] == 1 && $lang_id > 0) {
				//echo '<p><span style="font-size:10px"> <a href="'.SurveyInfo::getSurveyLink().'?language='.$lang_id.'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><img src="img_0/preview_red.png" /></a> <a href="'.SurveyInfo::getSurveyLink().'?language='.$lang_id.'" target="_blank">'.SurveyInfo::getSurveyLink().'?language='.$lang_id.'</a></p>';
			}
			echo '<p><a href="index.php?anketa='.$this->anketa.'&a=prevajanje">'.$lang['srv_info_language'].'</a></p>';
			
			echo '<div class="standardne_besede">';
			echo '<input type="hidden" name="extra_translations" value="1" />';			// da vemo, da nastavljamo ta besedila
			
			echo '<p><span class="nastavitveSpan1 textleft">&nbsp;</span><span class="nastavitveSpan1 textleft" style="min-width:500px">'.($lang_id>0?$lang['srv_multilang']:$lang['srv_language_respons_1']).':';
			
			
			$file = '../../lang/'.$lang_resp.'.php';
			include($file);
			echo ' '.$lang['language'].'</span><br></p>';
			
			// nazaj na administrativnega
			$file = '../../lang/'.$lang_admin.'.php';
			include($file);
			
			
			echo '<p>';
			
			echo '<span class="nastavitveSpan1 textleft">'.($lang_id>0?$lang['srv_language_respons_1'].': '.$resp_lang['language']:$lang['srv_language_admin'].': '.$lang['language']).'</span>';
			echo '<span class="nastavitveSpan1 textleft">'.$lang['srv_std_second'].'';
			echo '</span>';
			echo '<span class="bold">'.$lang['srv_std_translation'].' <a href="'.SurveyInfo::getSurveyLink().'&preview=on&language='.$lang_resp.'" target="_blank"><span class="faicon preview icon-as_link"></span></a></span>';
			
			echo '</p><hr>';
			
			echo '<p>';
			
			// jezik nastavimo na nastavitev za respondente, ker ta text dejansko nastavljamo
			$file = '../../lang/'.$lang_resp.'.php';
			include($file);

			// Pri gumbih ne prikazujemo editorja
			$this->extra_translation('srv_nextpage');
			$this->extra_translation('srv_nextpage_uvod');
			$this->extra_translation('srv_prevpage');
			$this->extra_translation('srv_lastpage');
			$this->extra_translation('srv_forma_send');
			$this->extra_translation('srv_potrdi');
			$this->extra_translation('srv_konec');
			
			$this->extra_translation('srv_remind_sum_hard', 1);
			$this->extra_translation('srv_remind_sum_soft', 1);
			$this->extra_translation('srv_remind_num_hard', 1);
			$this->extra_translation('srv_remind_num_soft', 1);
			$this->extra_translation('srv_remind_hard', 1);
			$this->extra_translation('srv_remind_soft', 1);
			$this->extra_translation('srv_remind_hard_-99', 1);
			$this->extra_translation('srv_remind_soft_-99', 1);
			$this->extra_translation('srv_remind_hard_-98', 1);
			$this->extra_translation('srv_remind_soft_-98', 1);
			$this->extra_translation('srv_remind_hard_-97', 1);
			$this->extra_translation('srv_remind_soft_-97', 1);
			$this->extra_translation('srv_remind_hard_multi', 1);
			$this->extra_translation('srv_remind_soft_multi', 1);
			$this->extra_translation('srv_remind_captcha_hard', 1);
			$this->extra_translation('srv_remind_captcha_soft', 1);
			$this->extra_translation('srv_remind_email_hard', 1);
			$this->extra_translation('srv_remind_email_soft', 1);
			$this->extra_translation('srv_alert_number_exists', 1);
			$this->extra_translation('srv_alert_number_toobig', 1);
			
			$this->extra_translation('srv_ranking_avaliable_categories', 1);
			$this->extra_translation('srv_ranking_ranked_categories', 1);
			$this->extra_translation('srv_question_respondent_comment', 1);
			$this->extra_translation('srv_continue_later', 1);
			$this->extra_translation('srv_continue_later_txt', 1);
			$this->extra_translation('srv_continue_later_email', 1);
			$this->extra_translation('srv_dropdown_select', 1);
			$this->extra_translation('srv_wrongcode', 1);
			$this->extra_translation('user_bye_textA', 1);
			
			$this->extra_translation('srv_survey_non_active', 1);
			$this->extra_translation('srv_survey_deleted', 1);
			$this->extra_translation('srv_survey_non_active_notActivated', 1);
			$this->extra_translation('srv_survey_non_active_notStarted', 1);
			$this->extra_translation('srv_survey_non_active_expired', 1);
			$this->extra_translation('srv_survey_non_active_voteLimit', 1);
			
						
			echo '</p>';
			
			// nastavimo jezik nazaj
			$file = '../../lang/'.$lang_admin.'.php';
			include($file);
			
			echo '<div>';
						
			echo '</fieldset>';
			

			echo '<br />';
			
			
			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'];
			echo '</span></a></div></span>';

			// Gumb za ponastavitev prevoda v bazi pobriše že nastavljene prevode za izbran jezik
            echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray btn_resetsettings" href="#" onclick="ponastavi_prevod(\''.$lang_id.'\')"><span>';
            echo $lang['reset_translation'];
            echo '</span></a></div></span>';


			echo '<a href="index.php?anketa='.$this->anketa.'&a=prevajanje" title="'.$lang['lang'].'"><span class="faicon language" style="display:inline-block; margin:2px 8px 0 25px;"></span>'.$lang['lang'].'</a>';
		}
		/*Forma*/
		if ($_GET['a'] == 'forma') {				
		}

		/*Nastavitve prikaza za mobilnike*/
		if ($_GET['a'] == 'mobile_settings') {

			SurveySetting::getInstance()->Init($this->anketa);
		
			echo '<fieldset class="wide">';
			
			echo '<legend>'.$lang['srv_mobile_settings_title'].'</legend>';
		
			// Prikaz slik pri mobilnikih (default da)
			$mobile_friendly = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_friendly');
			echo '<span class="nastavitveSpan2" >'.$lang['srv_settings_mobile_friendly'].':</span>';
			echo '<label for="mobile_friendly_1"><input type="radio" name="mobile_friendly" id="mobile_friendly_1" '.($mobile_friendly==='1'?' checked':'').' value="1" onClick="$(\'#mobile_settings_other\').show();">'.$lang['yes'].'</label> ';
			echo '<label for="mobile_friendly_0"><input type="radio" name="mobile_friendly" id="mobile_friendly_0" '.($mobile_friendly!=='1'?' checked':'').' value="0" onClick="$(\'#mobile_settings_other\').hide();">'.$lang['no'].'</label> ';


			$display = ($mobile_friendly == 1) ? '' : ' display: none;';
			echo '<div id="mobile_settings_other" style="margin-top: 10px; '.$display.'">';
			
			// Prikaz slik pri mobilnikih (default da)
			$hide_mobile_img = SurveySetting::getInstance()->getSurveyMiscSetting('hide_mobile_img');
			echo '<span class="nastavitveSpan2" >'.$lang['srv_settings_mobile_img'].':</span>';
			echo '<label for="hide_mobile_img_0"><input type="radio" name="hide_mobile_img" id="hide_mobile_img_0" '.($hide_mobile_img!=='1'?' checked':'').' value="0">'.$lang['yes'].'</label> ';
			echo '<label for="hide_mobile_img_1"><input type="radio" name="hide_mobile_img" id="hide_mobile_img_1" '.($hide_mobile_img==='1'?' checked':'').' value="1">'.$lang['no'].'</label> ';
			
			echo '<span class="clr"></span>';
			
			// Prilagoditev tabel pri mobilnikih
			$mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
			echo '<span class="nastavitveSpan2" >'.$lang['srv_settings_mobile_tables'].':</span>';
			echo '<label for="mobile_tables_1"><input type="radio" name="mobile_tables" id="mobile_tables_1" '.($mobile_tables==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="mobile_tables_2"><input type="radio" name="mobile_tables" id="mobile_tables_2" '.($mobile_tables==='2'?' checked':'').' value="2">'.$lang['srv_settings_mobile_tables_slide'].'</label> ';
			echo '<label for="mobile_tables_0"><input type="radio" name="mobile_tables" id="mobile_tables_0" '.($mobile_tables==='0'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '</div>';
			
			
			echo '</fieldset>';
		}
		
		/*Metapodatki (Parapodatki)*/
		if ($_GET['a'] == 'metadata') {

			SurveySetting::getInstance()->Init($this->anketa);
			$ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
			$ip_show = SurveySetting::getInstance()->getSurveyMiscSetting('survey_show_ip');
			$browser = SurveySetting::getInstance()->getSurveyMiscSetting('survey_browser');
			$referal = SurveySetting::getInstance()->getSurveyMiscSetting('survey_referal');
			$date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');


			echo '<fieldset class="wide">';
			echo '<legend>'.$lang['srv_sledenje'].'</legend>';
				
            // Preverimo ce je vklopljen modul za volitve - potem ne pustimo nobenih preklopov
            $voting_disabled = '';
            $voting_disabled_class = '';
            if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                $voting_disabled = ' disabled';
                $voting_disabled_class = ' class="gray"';

                echo '<p class="red">'.$lang['srv_voting_warning_paradata'].'</p>';	
            }
			
			echo '<p>'.$lang['srv_metadata_desc'].'</p>';	
				
			echo '<span class="nastavitveSpan1 wide"><label>'.$lang['srv_sledenje_browser'].':</label></span>';
            echo ' <label for="survey_browser_1" '.$voting_disabled_class.'><input type="radio" name="survey_browser" id="survey_browser_1" value="1"'.($browser==1?' checked':'').' '.$voting_disabled.'>'.$lang['no'].'</label>';
            echo ' <label for="survey_browser_0" '.$voting_disabled_class.'><input type="radio" name="survey_browser" id="survey_browser_0" value="0"'.($browser==0?' checked':'').' '.$voting_disabled.'>'.$lang['yes'].'</label><br class="clr"/>';
			
            echo '<span class="nastavitveSpan1 wide"><label>'.$lang['srv_sledenje_referal'].':</label></span>';
            echo ' <label for="survey_referal_1" '.$voting_disabled_class.'><input type="radio" name="survey_referal" id="survey_referal_1" value="1"'.($referal==1?' checked':'').' '.$voting_disabled.'>'.$lang['no'].'</label>';
            echo ' <label for="survey_referal_0" '.$voting_disabled_class.'><input type="radio" name="survey_referal" id="survey_referal_0" value="0"'.($referal==0?' checked':'').' '.$voting_disabled.'>'.$lang['yes'].'</label><br class="clr"/>';
			
            echo '<span class="nastavitveSpan1 wide"><label>'.$lang['srv_sledenje_date'].':</label></span>';
            echo ' <label for="survey_date_1" '.$voting_disabled_class.'><input type="radio" name="survey_date" id="survey_date_1" value="1"'.($date==1?' checked':'').' '.$voting_disabled.'>'.$lang['no'].'</label>';
            echo ' <label for="survey_date_0" '.$voting_disabled_class.'><input type="radio" name="survey_date" id="survey_date_0" value="0"'.($date==0?' checked':'').' '.$voting_disabled.'>'.$lang['yes'].'</label><br class="clr"/>';
	
			echo '</fieldset>';
	
	
			echo '<br />';
	
	
			echo '<fieldset>';

			echo '<legend>'.$lang['srv_sledenje_ip_title'].'</legend>';
			
			echo '<span class="nastavitveSpan1 wide"><label>'.$lang['srv_sledenje_ip'].':</label></span>';
            echo ' <label for="survey_ip_1" '.$voting_disabled_class.'><input type="radio" name="survey_ip" id="survey_ip_1" value="1"'.($ip==1?' checked':'').' '.$voting_disabled.'>'.$lang['no'].'</label>';
            echo ' <label for="survey_ip_0" '.$voting_disabled_class.'><input type="radio" name="survey_ip" id="survey_ip_0" value="0"'.($ip==0?' checked':'').' '.$voting_disabled.'>'.$lang['yes'].'</label>';
			
            if($ip == 0 && $ip_show != 1)
				echo '<div class="spaceLeft floatRight red" style="display:inline; width:520px;">'.$lang['srv_sledenje_ip_alert'].'</div>';
				
			echo '<br class="clr"/>';
			
			if($ip == 0 && ($admin_type == 0 || $admin_type == 1)){
				echo '<span class="nastavitveSpan1 wide"><label>'.$lang['srv_show_ip'].':</label></span>';
                echo ' <label for="survey_show_ip_0" '.$voting_disabled_class.'><input type="radio" name="survey_show_ip" id="survey_show_ip_0" value="0"'.($ip_show==0?' checked':'').' '.$voting_disabled.'>'.$lang['no'].'</label>';
                echo ' <label for="survey_show_ip_1" '.$voting_disabled_class.'><input type="radio" name="survey_show_ip" id="survey_show_ip_1" value="1"'.($ip_show==1?' checked':'').' '.$voting_disabled.'>'.$lang['yes'].'</label>';
				
                if($ip_show == 1)
					echo '<div class="spaceLeft floatRight red" style="display:inline; width:520px;">'.$lang['srv_show_ip_alert'].'</div>';
			}
	
			echo '</fieldset>';
				
			
			// Povezovanje identifikatorjev s podatki - samo za admine in ce so vklopljena email vabila
			if ($admin_type == 0 && SurveyInfo::getInstance()->checkSurveyModule('email')) {
				echo '<br />';
				echo '<fieldset class="wide">';
				echo '<legend>'.$lang['srv_sledenje_identifikatorji_title'].' '.Help::display('srv_email_with_data').'</legend>';
				
				echo '<span class="nastavitveSpan1 wide"><label>'.$lang['srv_sledenje_identifikatorji'].':</label></span>';
                echo ' <label for="show_email_0" '.$voting_disabled_class.'><input type="radio" name="show_email" id="show_email_0" value="0"'.($row['show_email']==0?' checked':'').' '.$voting_disabled.'>'.$lang['no'].'</label>';
                echo ' <label for="show_email_1" '.$voting_disabled_class.'><input type="radio" name="show_email" id="show_email_1" value="1"'.($row['show_email']==1?' checked':'').' '.$voting_disabled.'>'.$lang['yes'].'</label>';
				
                if($row['show_email'] == 1)
					echo '<div class="spaceLeft floatRight red" style="display:inline; width:520px;">'.$lang['srv_show_mail_with_data3'].'</div>';
					
				echo '</fieldset>';
			}
		}
		
		/* Nastavitve pdf/rtf izvozov */
		if ($_GET['a'] == 'export_settings') {

			SurveySetting::getInstance()->Init($this->anketa);
		
			// Nastavitve za izpis vprasalnika
			echo '<fieldset class="wide">';	
			echo '<legend>'.$lang['srv_export_survey_settings'].'</legend>';
			
			// Številčenje vprašanj (default da)
			$export_numbering = SurveySetting::getInstance()->getSurveyMiscSetting('export_numbering');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_nastavitveStevilcenje'].':</span>';
			echo '<label for="export_numbering_1"><input type="radio" name="export_numbering" id="export_numbering_1" '.($export_numbering==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_numbering_0"><input type="radio" name="export_numbering" id="export_numbering_0" '.($export_numbering!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br />';
			
			// Prikaz pogojev (default da)
			$export_show_if = SurveySetting::getInstance()->getSurveyMiscSetting('export_show_if');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_if'].':</span>';
			echo '<label for="export_show_if_1"><input type="radio" name="export_show_if" id="export_show_if_1" '.($export_show_if==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_show_if_0"><input type="radio" name="export_show_if" id="export_show_if_0" '.($export_show_if!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
								
			echo '<br />';

			// Prikazi uvoda (default ne)
			$export_show_intro = SurveySetting::getInstance()->getSurveyMiscSetting('export_show_intro');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_intro'].':</span>';
			echo '<label for="export_show_intro_1"><input type="radio" name="export_show_intro" id="export_show_intro_1" '.($export_show_intro==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_show_intro_0"><input type="radio" name="export_show_intro" id="export_show_intro_0" '.($export_show_intro!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';			
								
			echo '</fieldset>';
			
			
			echo '<br />';
			
			
			// Nastavitve za izpis odgovorov respondentov
			echo '<fieldset class="wide">';	
			echo '<legend>'.$lang['srv_export_results_settings'].'</legend>';

			// Tip izvoza (1->dolg oz. razsirjen, 2->kratek oz. skrcen)
			$export_data_type = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_type');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_displaydata_type'].':</span>';
			echo '<select name="export_data_type" id="export_data_type" >';
			echo '	<option value="2"'.((int)$export_data_type == 2 ? ' selected="selected"' : '').'>' . $lang['srv_displaydata_type2'] . '</option>';
			echo '	<option value="1"'.((int)$export_data_type == 1 ? ' selected="selected"' : '').'>' . $lang['srv_displaydata_type1'] . '</option>';
			//echo '	<option value="2"'.((int)$export_data_type == 2 ? ' selected="selected"' : '').'>' . $lang['srv_displaydata_type2'] . '</option>';
			echo '</select>';
			echo Help :: display('displaydata_pdftype');
			
			echo '<br />';
			
			// Številčenje vprašanj (default da)
			$export_data_numbering = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_numbering');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_nastavitveStevilcenje'].':</span>';
			echo '<label for="export_data_numbering_1"><input type="radio" name="export_data_numbering" id="export_data_numbering_1" '.($export_data_numbering==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_data_numbering_0"><input type="radio" name="export_data_numbering" id="export_data_numbering_0" '.($export_data_numbering!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br />';
			
			// Prikaz recnuma (default da)
			$export_data_show_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_show_recnum'].':</span>';
			echo '<label for="export_data_show_recnum_1"><input type="radio" name="export_data_show_recnum" id="export_data_show_recnum_1" '.($export_data_show_recnum==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_data_show_recnum_0"><input type="radio" name="export_data_show_recnum" id="export_data_show_recnum_0" '.($export_data_show_recnum!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br />';
			
			// Prikaz pogojev (default da)
			$export_data_show_if = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_if');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_if'].':</span>';
			echo '<label for="export_data_show_if_1"><input type="radio" name="export_data_show_if" id="export_data_show_if_1" '.($export_data_show_if==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_data_show_if_0"><input type="radio" name="export_data_show_if" id="export_data_show_if_0" '.($export_data_show_if!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br /><br />';
			
			// Page break med posameznimi respondenti (default ne)
			$export_data_PB = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_PB');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_pagebreak'].':</span>';
			echo '<label for="export_data_PB_1"><input type="radio" name="export_data_PB" id="export_data_PB_1" '.($export_data_PB==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_data_PB_0"><input type="radio" name="export_data_PB" id="export_data_PB_0" '.($export_data_PB!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br />';
			
			// Izpusti vprasanja brez odgovora (default ne)
			$export_data_skip_empty = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_skip_empty'].':</span>';
			echo '<label for="export_data_skip_empty_1"><input type="radio" name="export_data_skip_empty" id="export_data_skip_empty_1" '.($export_data_skip_empty==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_data_skip_empty_0"><input type="radio" name="export_data_skip_empty" id="export_data_skip_empty_0" '.($export_data_skip_empty!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br />';
			
			// Izpusti podvprasanja brez odgovora (default ne)
			$export_data_skip_empty_sub = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty_sub');
			echo '<span class="nastavitveSpan1" >'.$lang['srv_export_skip_empty_sub'].':</span>';
			echo '<label for="export_data_skip_empty_sub_1"><input type="radio" name="export_data_skip_empty_sub" id="export_data_skip_empty_sub_1" '.($export_data_skip_empty_sub==='1'?' checked':'').' value="1">'.$lang['yes'].'</label> ';
			echo '<label for="export_data_skip_empty_sub_0"><input type="radio" name="export_data_skip_empty_sub" id="export_data_skip_empty_sub_0" '.($export_data_skip_empty_sub!=='1'?' checked':'').' value="0">'.$lang['no'].'</label> ';
			
			echo '<br />';
				
			echo '</fieldset>';
		}
		
		/* Nastavitve GDPR */
		if ($_GET['a'] == A_GDPR) {

			$gdpr = new GDPR();
			$gdpr->displayGDPRSurvey($this->anketa);
		}
		
		
		if ($_GET['a'] != 'jezik' && $_GET['a'] != 'trajanje' && $_GET['a'] != A_GDPR && $_GET['a'] != 'dostop') {
			echo '<br class="clr" />';

			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'] . '</span></a></div></span>';
			echo '<div class="clr"></div>';
		}
		
		if ($_GET['s'] == '1') {
			echo '<div id="success_save"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
		
		echo '</form>';
	}
	
	function anketa_nastavitve_mail() {
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;

		/* Globalne nastavitve ankete: veljajo za celoto anketo ne glede na uporabnika*/
		$row = SurveyInfo::getInstance()->getSurveyRow();

		$http_referer = parse_url($_SERVER['HTTP_REFERER']); //If yes, parse referrer
		$referer_url = $http_referer['query'];
		$show_back_button = false;
		if(preg_match('/anketa='.$this->anketa.'&a='.A_INVITATIONS.'/', $referer_url) || $_GET['show_back'] == 'true')
			$show_back_button = true;
		
		echo '<fieldset><legend>'.$lang['srv_email_setting_title'].'</legend>';
		echo '<form name="settingsanketa_' . $row['id'] . '" action="ajax.php?a=editanketasettings&m='.A_MAILING. ($show_back_button ? '&show_back=true' : '').'" method="post" autocomplete="off">' . "\n\r";
		echo '	<input type="hidden" name="anketa" value="' . $this->anketa . '" />' . "\n\r";
				echo '  <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";
		echo '  <input type="hidden" name="submited" value="1" />' . "\n\r";
		
		$MA = new MailAdapter($this->anketa);
		
		echo '<span class="bold">'.$lang['srv_email_setting_select_server'].'</span>&nbsp;';
		echo '<label><input type="radio" name="SMTPMailMode" value="0" '.($MA->is1KA() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode1, #send_mail_mode2\').hide();$(\'#send_mail_mode0\').show();">';
        echo $lang['srv_email_setting_adapter0']. ' </label>';
        // Google smtp je viden samo starim, kjer je ze vklopljen
        if($MA->isGoogle()){
		    echo '<label><input type="radio" name="SMTPMailMode" value="1" '.($MA->isGoogle() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode0, #send_mail_mode2\').hide(); $(\'#send_mail_mode1\').show();">';
            echo $lang['srv_email_setting_adapter1'].' </label>';
        }
		echo '<label><input type="radio" name="SMTPMailMode" value="2" '.($MA->isSMTP() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode0, #send_mail_mode1\').hide(); $(\'#send_mail_mode2\').show();">';
		echo $lang['srv_email_setting_adapter2'].' </label>';
		echo Help :: display('srv_mail_mode');

		#1ka mail system
		$enkaSettings = $MA->get1KASettings();
		echo '<br>';
		echo '<br>';
		echo '<span class="bold">'.$lang['srv_email_setting_settings'].'</span><br>';
		echo '<div id="send_mail_mode0" '.(!$MA->is1KA() ? ' class="displayNone"' : '').'>';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<span>'.$enkaSettings['SMTPFrom'].'</span><input type="hidden" name="SMTPFrom0" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo0" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		echo '</div>';
		
		#GMAIL - Google
		$enkaSettings = $MA->getGoogleSettings();
		echo '<div id="send_mail_mode1" '.(!$MA->isGoogle() ? ' class="displayNone"' : '').'>';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<input type="text" name="SMTPFrom1" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo1" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		#Password
		echo '<p><label>'.$lang['srv_email_setting_password'].'<input type="password" name="SMTPPassword1" placeholder="'.$lang['srv_email_setting_password_placeholder'].'"></label>';
		echo '</p>';
		echo '</div>';

		#SMTP
		$enkaSettings = $MA->getSMTPSettings();
		echo '<div id="send_mail_mode2" '.(!$MA->isSMTP() ? ' class="displayNone"' : '').'>';
		# from - NICE
		echo '<p><label>'.$lang['srv_email_setting_from_nice'].'<input type="text" name="SMTPFromNice2" value="'.$enkaSettings['SMTPFromNice'].'"></label>';
		echo '</p>';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<input type="text" name="SMTPFrom2" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo2" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		#Username
		echo '<p><label>'.$lang['srv_email_setting_username'].'<input type="text" name="SMTPUsername2" value="'.$enkaSettings['SMTPUsername'].'" ></label>';
		echo '</p>';
		#Password
		echo '<p><label>'.$lang['srv_email_setting_password'].'<input type="password" name="SMTPPassword2" placeholder="'.$lang['srv_email_setting_password_placeholder'].'"></label>';
		echo '</p>';
		#autentikacija
		echo '<p>';
		echo $lang['srv_email_setting_autentication'];
		echo '<label><input type="radio" name="SMTPAuth2" value="0" '.((int)$enkaSettings['SMTPAuth'] != 1 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_no'].'</label>';
		echo '<label><input type="radio" name="SMTPAuth2" value="1" '.((int)$enkaSettings['SMTPAuth'] == 1 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_yes'].'</label>';
		echo '</p>';
		#Varnost SMTPSecure
		echo '<p>';
		echo $lang['srv_email_setting_encryption'];
		echo '<input type="radio" name="SMTPSecure2" value="0" '.((int)$enkaSettings['SMTPSecure'] == 0 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_none'].'</label>';
		echo '<label><input type="radio" name="SMTPSecure2" value="ssl" '.($enkaSettings['SMTPSecure'] == 'ssl' ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_ssl'].'</label>';
		echo '<label><input type="radio" name="SMTPSecure2" value="tls" '.($enkaSettings['SMTPSecure'] == 'tls' ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_tls'].'</label>';
		echo '</p>';
		#port
		echo '<p><label>'.$lang['srv_email_setting_port'].'<input type="number" min="0" max="65535" name="SMTPPort2" value="'.(int)$enkaSettings['SMTPPort'].'" ></label>';
		echo $lang['srv_email_setting_port_note'];
		echo '</p>';
		#host
		echo '<p><label>'.$lang['srv_email_setting_host'].'<input type="text" name="SMTPHost2" value="'.$enkaSettings['SMTPHost'].'" ></label>';
		echo '</p>';
		echo '</div>';
		
		echo '</form>';
		echo '</fieldset>';
		
		echo '<br class="clr" />';
		echo '<span id="send_mail_mode_test"  class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="showTestSurveySMTP(); return false;"><span>';
		echo $lang['srv_email_setting_btn_test'].'</span></a></div></span>';
		echo '<span class="floatLeft spaceRight" ><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
		echo $lang['srv_email_setting_btn_save'] . '</span></a></div></span>';
		
		if (preg_match('/anketa='.$this->anketa.'&a='.A_INVITATIONS.'/', $referer_url) || $show_back_button) {
				
			//echo '<div class="floatLeft spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="'.$_SERVER['HTTP_REFERER'].'"><span>';
			echo '<div class="floatLeft spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=invitations"><span>';
			echo $lang['srv_back_to_email'] . '</span></a></div>';
			echo '</div>';
		}
		
		echo '<br class="clr" />';
		
		if ($_GET['s'] == '1') {
			echo '<div id="success_save"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
	}

	/**
	* funkcija, ki prikaze polja za nastavitev ekstra prevodov
	* 
	*/
	function extra_translation ($text, $editor = 0) {
		global $lang;
		global $admin_lang;
		global $resp_lang;
		
		$lang_id = (int)$_GET['lang_id'];
		if ($lang_id > 0)
			$lang_id = '_'.$lang_id;
		else
			$lang_id = '';
			
		$value = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_'.$text.$lang_id);
		
		if ($value == '') $value = $lang[$text];

        $onclick = 'onclick="inline_jezik_edit(\'srvlang_'.$text.$lang_id.'\');"';

		// Popravimo text za naslednjo stran na uvodu
		$next_uvod = '';
		if($text == 'srv_nextpage_uvod')
			$next_uvod = $lang_id == '' ? $admin_lang['srv_nextpage_uvod_desc'] : $resp_lang['srv_nextpage_uvod_desc'];
		
		echo '<div class="standardna-beseda"><span class="nastavitveSpan1 gray textleft">'.($lang_id==''?$admin_lang[$text]:$resp_lang[$text]).' '.($text == 'srv_nextpage_uvod' ? '<span class="gray italic">('.$next_uvod.')</span>' : '').'</span> ';
		echo '<span class="nastavitveSpan1 textleft textItalic">'.$lang[$text].' </span>';
		echo '<div contentEditable="true" class="standardna-beseda-urejanje" name="srvlang_'.$text.$lang_id.'" id="srvlang_'.$text.$lang_id.'">'.$value.'</div>';
		
		if($editor == 1) 
			echo '<span class="faicon edit2 sb-edit"'.$onclick.' style="float:right; margin-top:1px; display:none;"></span>';
        
		echo '<textarea name="srvlang_'.$text.$lang_id.'" id="polje_srvlang_'.$text.$lang_id.'"  style="display:none;">'.$value.'</textarea>';
        
		echo '</div>';
	}
	
	function anketa_nice_links () {
		
		echo '<div id="anketa_edit">';
		
		$sql = sisplet_query("SELECT l.link, a.id, a.naslov FROM srv_nice_links l, srv_anketa a WHERE a.id=l.ank_id ORDER BY l.link ASC");
		
		while ($row = mysqli_fetch_array($sql)) {		
			echo '<p><strong style="display:inline-block; width:300px;">'.$row['link'].'</strong> <a href="index.php?anketa='.$row['id'].'&a=vabila&m=url">'.$row['naslov'].'</a></p>';
		}
		
		echo '</div>';		
	}
	
	/**
	* prikaze ankete z administrativnim dostopom za pomoč
	* 
	*/
	function anketa_admin () {
		global $lang;
		
		echo '<div id="anketa_edit">';
		
		$sql = sisplet_query("SELECT srv_anketa.id, srv_anketa.naslov, users.email FROM srv_anketa, users WHERE users.id=srv_anketa.insert_uid AND dostop_admin >= DATE(NOW()) ORDER BY edit_time DESC");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		while ($row = mysqli_fetch_array($sql)) {		
			echo '<p><strong style="display:inline-block; width:300px;"><a href="index.php?anketa='.$row['id'].'">'.$row['naslov'].'</a></strong> <span style="display:inline-block; width:300px;">('.$row['email'].')</span></p>';	
		}
		
		echo '</div>';	
	}
	
	/**
	* prikaze izbrisanje ankete
	* 
	*/
	function anketa_deleted () {
		global $lang;
		
		echo '<div id="anketa_edit">';
		
		$sql = sisplet_query("SELECT srv_anketa.id, srv_anketa.naslov, users.email FROM srv_anketa, users WHERE users.id=srv_anketa.insert_uid AND active='-1' ORDER BY edit_time DESC");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		while ($row = mysqli_fetch_array($sql)) {			
			echo '<p><strong style="display:inline-block; width:300px;"><a href="index.php?anketa='.$row['id'].'">'.$row['naslov'].'</a></strong> <span style="display:inline-block; width:300px;">('.$row['email'].')</span> <a href="#" onclick="anketa_restore(\''.$row['id'].'\'); return false;">'.$lang['srv_restore'].'</a></p>';		
		}
		
		echo '</div>';		
	}
	
	/**
	* prikaze izbrisanje podatke
	* 
	*/
	function data_deleted () {
		global $lang;
		
		echo '<div id="anketa_edit">';
		
		$sql = sisplet_query("SELECT a.id, a.naslov, users.email, COUNT(u.id) AS deleted FROM srv_anketa a, srv_user u, users WHERE u.deleted='1' AND u.ank_id=a.id AND users.id=a.insert_uid GROUP BY a.id ORDER BY edit_time DESC");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		while ($row = mysqli_fetch_array($sql)) {		
			echo '<p><strong style="display:inline-block; width:300px;"><a href="index.php?anketa='.$row['id'].'">'.$row['naslov'].'</a></strong> <span style="display:inline-block; width:300px;">('.$row['email'].')</span> <a href="#" onclick="data_restore(\''.$row['id'].'\'); return false;">'.$lang['srv_restore'].'</a> ('.$row['deleted'].')</p>';			
		}
		
		echo '</div>';	
	}

	// online urejanje CSS datoteke 
	function anketa_editcss() {
		$st = new SurveyTheme($this->anketa);
		$st->edit_css();
	}
	
	function anketa_vabila() {
		global $lang;	
		if ($_GET['m'] == '' || $_GET['m'] == 'settings') {
			$this->anketa_vabila_nastavitve();
		} elseif ($_GET['m'] == 'url') {
			$this->anketa_vabila_url();
		}
	}
	
	function anketa_vabila_nastavitve() {
		global $lang, $site_url, $global_user_id;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$d = new Dostop();
		
		echo '<table id="tbl_inv_setting" style="width:100%">';
		echo '<colgroup>';
		echo '<col style="width:30%;" valign="top"/>';
		echo '<col style="width:70%;" valign="top"/>';
		echo '</colgroup>';
		
		echo '<tr>';
		
		# če ni aktivna damo opozorilo
		echo '<td style="height: 50px;">';
		
		# Opozorilo o napakah
		$sd = new SurveyDiagnostics($this->anketa);
		$sd->doDiagnostics();
		$diagnostic = $sd->getDiagnostic();
		if (is_array($diagnostic) && count($diagnostic) > 0)
			echo '<div id="anketa_diagnostika_note2">'.$lang['srv_publication_survey_warnings'].' <a href="index.php?anketa=' . $this->anketa . '&amp;a='.A_TESTIRANJE.'" class="bold">>></a></div>';

		// Aktivacija ankete
		echo '<span id="anketa_aktivacija_note" '.($row['active']==0?' class="google_yellow"':'').'>';
		$this->anketa_aktivacija_note();
		echo'</span>';
		
		echo '</td>';
		
		# Povezave, lepi linki...
		echo '<td style="padding-left:15px;" rowspan="2">';
		
		# Linki, lepi linki
		$this->niceUrlSettings();

		echo '<br />';
		
		// Napredne URL povezave
		echo '<div style="background-color:#EFF2F7; padding:5px 10px;">';
		//echo '<span class="strong">'.$lang['srv_publication_advanced_url'].'</span>';
		echo '<div class="buttonwrapper" style="margin:10px 0; height:25px;"><a class="ovalbutton floatLeft" title="'.$lang['srv_publication_advanced_url'].'" href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_VABILA . '&m=url">'.$lang['srv_publication_advanced_url'].'</a></div>';
		echo '<p>'.$lang['srv_publication_advanced_url_text'].'</p>';
		echo '</div>';
		
		echo '<br />';
		
		// Individualizirana vabila
		echo '<div style="background-color:#EFF2F7; padding:5px 10px;">';
		//echo '<span class="strong">'.$lang['srv_publication_invitations'].'</span>';
		echo '<div class="buttonwrapper" style="margin:10px 0; height:25px;"><a class="ovalbutton floatLeft" title="'.$lang['srv_publication_invitations'].'" href="index.php?anketa=' . $this->anketa . '&amp;a=invitations">'.$lang['srv_publication_invitations'].'</a></div>';
		echo '<p>'.$lang['srv_publication_invitations_text'].'</p>';
		echo '</div>';
		
		echo '</td>';
		/* # Opozorilo o napakah
		echo '<td style="padding-left:15px;">';
		$sd = new SurveyDiagnostics($this->anketa);
		$sd->doDiagnostics();
		$diagnostic = $sd->getDiagnostic();
		if (is_array($diagnostic) && count($diagnostic) > 0) {
			echo '<span id="anketa_diagnostika_note">';
			$this->anketa_diagnostika_note($sd, $d->checkDostopSub('test'));
			echo'</span>';
		}
		echo '</td>';*/
		
		echo '</tr>';
		
		echo '<tr>';
		
		echo '<td>';
		echo '<input type="hidden" value="' . $this->anketa . '" name="anketa" >';		
		
		$base_url = $site_url.'admin/survey/index.php?anketa='.$this->anketa;
		# preberomo osnovne nastavitve 
		$row = SurveyInfo::getInstance()->getSurveyRow();
	
		echo '<fieldset><legend>'.($row['active']==0 ? $lang['srv_default_setting_unactive'] : $lang['srv_default_setting']).'</legend>';
			
		// Ce imamo dostop do zavihka urejanje
		if($d->checkDostopSub('edit')){
			
			# Trajanje
			$starts = explode('-',$row['starts']);
			$starts = $starts[2].'.'.$starts[1].'.'.$starts[0];
			$expire = explode('-',$row['expire']);
			$expire = $expire[2].'.'.$expire[1].'.'.$expire[0];
			echo '<p>'.$lang['srv_starts'].':<a href="'.$base_url.'&a='.A_TRAJANJE.'&f=vabila_settings" title="'.$lang['srv_info_duration'].'"><span class="qs_data as_link">'.$starts.'</span></a></p>';
			if ( $row['expire'] == PERMANENT_DATE ) {
				#trajna
				echo '<p>'.$lang['srv_trajna_anketa'].':<a href="'.$base_url.'&a='.A_TRAJANJE.'&f=vabila_settings" title="'.$lang['srv_trajna_anketa'].'"><span class="qs_data as_link">'.($row['expire'] == PERMANENT_DATE ? $lang['yes'] : $lang['no']).'</span></a></p>';
			} else {
				echo '<p>'.$lang['srv_expire'].':<a href="'.$base_url.'&a='.A_TRAJANJE.'&f=vabila_settings" title="'.$lang['srv_info_duration'].'"><span class="qs_data as_link">'.$expire.'</span></a></p>';
			}
				
			// Skin ankete
			if ($row['skin_profile'] == 0) {
				$skin_name = $row['skin'];
			} 
			else {
				$sqla = sisplet_query("SELECT name FROM srv_theme_profiles WHERE id = '".$row['skin_profile']."'");
				$rowa = mysqli_fetch_array($sqla);
				$skin_name = $rowa['name'];
			}
			//echo '<p>'.$lang['srv_themes'].':<a href="'.$base_url.'&a='.A_TEMA.'&f=vabila_settings" title="'.$lang['srv_themes'].'"><span class="qs_data as_link">'.$row['skin'].'</span></a></p>';
			echo '<p>'.$lang['srv_themes'].':<a href="'.$base_url.'&a='.A_TEMA.'" title="'.$lang['srv_themes'].'"><span class="qs_data as_link">'.$skin_name.'</span></a></p>';						
			
			# Jezik
			$lang_old = $lang;
			$lang_admin = (int)$row['lang_admin'];
			$lang_resp = (int)$row['lang_resp'];
			$lang_array = array();
			$lang_array[0] = $lang['srv_language_not_set'];
			// Preberemo razpoložljive jezikovne datoteke
			if ($dir = opendir('../../lang')) {
				while (($file = readdir($dir)) !== false) {
					if ($file != '.' AND $file != '..') {
						if (is_numeric(substr($file, 0, strpos($file, '.')))) {
							$i = substr($file, 0, strpos($file, '.'));
							$file = '../../lang/'.$i.'.php';
							if (file_exists($file)) {
								include($file);
								$lang_array[$i] = $lang['language'];
							}
						}
					}
				}
			}
			
			// nastavimo jezik nazaj
			/*$file = '../../lang/'.$lang_admin.'.php';
			if (file_exists($file)) {
				include($file);
			}*/
			$lang = $lang_old;
			$resp_change_lang = SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang');
			//echo '<p>'.$lang['srv_language_admin_1'].':</p>';
			echo '<p>'.$lang['srv_language_admin_0'].':<a href="'.$base_url.'&a='.A_JEZIK.'&f=vabila_settings" title="'.$lang['srv_language_admin_1'].'"><span class="qs_data as_link">'.$lang_array[$lang_admin].'</span></a> / <a href="'.$base_url.'&a='.A_JEZIK.'&f=vabila_settings" title="'.$lang['srv_language_respons_1'].'"><span class="qs_data as_link">'.$lang_array[$lang_resp].'</span></a></p>';

			#obveščanje
			
			// jezikovni linki
			$p = new Prevajanje($this->anketa);
			$p->dostop();
			$jeziki = $p->get_all_translation_langs();
			if (count($jeziki) > 0) {
				echo '<p>' . $lang['srv_trans_lang'] . ': ';
				$i = 0;
				foreach ($jeziki AS $key => $val) {
					if ($i++ != 0) echo ', ';
					echo '<a href="'.$link.'?anketa='.$this->anketa.'&a=prevajanje&lang_id='.$key.'" target="_blank">'.$val.'</a>';
				}
				echo '</p>';
			}		
			#piškotki
			echo '<p>'.$lang['srv_cookie'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_cookie'].'"><span class="qs_data as_link">'.$lang['srv_cookie_'.$row['cookie']].'</span></a></p>';
			echo '<p>'.$lang['srv_cookie_return'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_cookie_return'].'"><span class="qs_data as_link">'.($row['cookie_return'] == 0 ? $lang['srv_cookie_return_start'] : $lang['srv_cookie_return_middle']).'</span></a></p>';
			
			#more - več
			echo '<div id="srv_objava_info_more1" class="as_link" onclick="$(\'#srv_objava_info_more, #srv_objava_info_more1, #srv_objava_info_more2\').toggle();">'.$lang['srv_more'].'</div>';
			echo '<div id="srv_objava_info_more2" class="as_link displayNone" onclick="$(\'#srv_objava_info_more, #srv_objava_info_more1, #srv_objava_info_more2\').toggle();">'.$lang['srv_less'].'</div>';
			echo '<div id="srv_objava_info_more" class="displayNone">';
			
			if ($row['cookie'] > -1) {
				# če je piškotek dlje kot do konca nakete lahko izbere tudi druge možnosti
				echo '<p>'.$lang['srv_return_finished'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_return_finished'].'"><span class="qs_data as_link">'.($row['return_finished'] == 1 ? $lang['srv_return_finished_yes'] : $lang['srv_return_finished_no']).'</span></a></p>';
			} else {
				# ker je piškotek samo do konca ankete se ne more vrnit ali urejat
				echo '<p>'.$lang['srv_return_finished'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_return_finished'].'"><span class="qs_data as_link">'. $lang['srv_return_finished_no'] .'</span></a></p>';
			}
			
			echo '<p>'.$lang['srv_multilang'].':<a href="'.$base_url.'&a='.A_PREVAJANJE.'&f=vabila_settings" title="'.$lang['srv_multilang'].'"><span class="qs_data as_link">'.($row['multilang'] == 1 ? $lang['yes'] : $lang['no'] ).'</span></a></p>';
			
			echo '<p>'.$lang['srv_user'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_user'].'"><span class="qs_data as_link">';
			if ($row['user_from_cms'] == 1) {
				echo $lang['srv_respondent'];
			} elseif ($row['user_from_cms'] == 2) {
				echo $lang['srv_vnasalec'];
			} elseif ($row['user_from_cms'] == 0) {
				echo $lang['no1'];
			}
			echo '</span></a></p>';
			
			echo '<p>'.$lang['srv_block_ip'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_block_ip'].'"><span class="qs_data as_link">';
			if ($row['block_ip'] == 0) {
				echo $lang['no1'];
			} elseif ($row['block_ip'] == 10) {
				echo '10 min';
			} elseif ($row['block_ip'] == 20) {
				echo '20 min';
			} elseif ($row['block_ip'] == 60) {
				echo '60 min';
			} elseif ($row['block_ip'] == 720) {
				echo '12 '.$lang['hour_hours2'];
			} elseif ($row['block_ip'] == 1440) {
				echo '24 '.$lang['hour_hours2'];
			}
			echo '</a>';
			echo '</p>';
			
			
			# user from cms	
			if ($row['user_from_cms']>0) {
				echo '<p>'.$lang['srv_user_cms_show'].':<a href="'.$base_url.'&a='.A_COOKIE.'&f=vabila_settings" title="'.$lang['srv_user_cms_show'].'"><span class="qs_data as_link">'.($lang['srv_user_cms_email']).'</span></a></p>';
			} 
			echo '<p>'.$lang['srv_vote_limit'].':<a href="'.$base_url.'&a='.A_TRAJANJE.'&f=vabila_settings" title="'.$lang['srv_vote_limit'].'"><span class="qs_data as_link">'.($row['vote_limit'] == 0 ? $lang['no'] : $lang['yes']).'</span></a></p>';
			
			echo '<p>'.$lang['srv_vote_count'].':<a href="'.$base_url.'&a='.A_TRAJANJE.'&f=vabila_settings" title="'.$lang['srv_vote_count'].'"><span class="qs_data as_link">'.($row['vote_limit'] == 0 ? '/' : $row['vote_count']).'</span></a></p>';
			# Obveščanje
			// preberemo nastavitve alertov
			$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '".$this->anketa."'");
			if (mysqli_num_rows($sqlAlert) > 0) {
				$rowAlert = mysqli_fetch_assoc($sqlAlert);
			} else {
				SurveyAlert::getInstance()->Init($anketa, $global_user_id);
				$rowAlert = SurveyAlert::setDefaultAlertBeforeExpire();
			}
			
			$alert_finish = array();
			$alert_expire = array();
			$alert_delete = array();
			$alert_active = array();
			if ($rowAlert['finish_respondent'] == 1) {
				$alert_finish[] = $lang['srv_alert_respondent'];
			}
			if ($rowAlert['finish_respondent_cms'] == 1) {
				$alert_finish[] = $lang['srv_alert_respondent_cms'];
			}
			if ($rowAlert['finish_author'] == 1) {
				$alert_finish[] = $lang['srv_info_author'];
			}
			if ($rowAlert['finish_other'] == 1) {
				$alert_finish[] = $lang['email_prejemniki'];
			}
			if ($rowAlert['expire_author'] == 1) {
				$alert_expire[] = $lang['srv_info_author'];
			}
			if ($rowAlert['expire_other'] == 1) {
				$alert_expire[] = $lang['email_prejemniki'];
			}
			if ($rowAlert['delete_author'] == 1) {
				$alert_delete[] = $lang['srv_info_author'];
			}
			if ($rowAlert['delete_other'] == 1) {
				$alert_delete[] = $lang['email_prejemniki'];
			}
			if ($rowAlert['active_author'] == 1) {
				$alert_active[] = $lang['srv_info_author'];
			}
			if ($rowAlert['active_other'] == 1) {
				$alert_active[] = $lang['email_prejemniki'];
			}
			echo '<p>'.$lang['srv_alert_completed_2'].':<a href="'.$base_url.'&a='.A_ALERT.'&f=vabila_settings" title="'.$lang['srv_alert_completed_2'].'"><span class="qs_data as_link">'.(count($alert_finish) ? implode(',',$alert_finish) : $lang['no']).'</span></a></p>';
			echo '<p>'.$lang['srv_alert_expired_2'].':<a href="'.$base_url.'&a='.A_ALERT.'&f=vabila_settings" title="'.$lang['srv_alert_expired_2'].'"><span class="qs_data as_link">'.(count($alert_expire) ? implode(',',$alert_expire) : $lang['no']).'</span></a></p>';
			echo '<p>'.$lang['srv_alert_active_2'].':<a href="'.$base_url.'&a='.A_ALERT.'&f=vabila_settings" title="'.$lang['srv_alert_active_2'].'"><span class="qs_data as_link">'.(count($alert_active) ? implode(',',$alert_active) : $lang['no']).'</span></a></p>';
			echo '<p>'.$lang['srv_alert_delete_2'].':<a href="'.$base_url.'&a='.A_ALERT.'&f=vabila_settings" title="'.$lang['srv_alert_delete_2'].'"><span class="qs_data as_link">'.(count($alert_delete) ? implode(',',$alert_delete) : $lang['no']).'</span></a></p>';
			
			echo '<p>';
			echo '<a href="index.php?anketa=' . $this->anketa . '&a='.A_SETTINGS . '&f=vabila_settings" title="' . $lang['srv_nastavitve_ankete'] . '">';
			echo $lang['srv_nastavitve_ankete_all'].'</a>';
			echo '</p>';
			echo '</div>';
		}
		// Nimamo dostopa do zavihka urejanje - ni nobenih linkov
		else{
			# Trajanje
			$starts = explode('-',$row['starts']);
			$starts = $starts[2].'.'.$starts[1].'.'.$starts[0];
			$expire = explode('-',$row['expire']);
			$expire = $expire[2].'.'.$expire[1].'.'.$expire[0];
			echo '<p>'.$lang['srv_starts'].': '.$starts.'</p>';
			if ( $row['expire'] == PERMANENT_DATE ) {
				#trajna
				echo '<p>'.$lang['srv_trajna_anketa'].': '.($row['expire'] == PERMANENT_DATE ? $lang['yes'] : $lang['no']).'</p>';
			} else {
				echo '<p>'.$lang['srv_expire'].': '.$expire.'</p>';
			}
			
			echo '<p>'.$lang['srv_themes'].': '.$row['skin'].'</p>';
			
			# Jezik
			$lang_old = $lang;
			$lang_admin = (int)$row['lang_admin'];
			$lang_resp = (int)$row['lang_resp'];
			$lang_array = array();
			$lang_array[0] = $lang['srv_language_not_set'];
			// Preberemo razpoložljive jezikovne datoteke
			if ($dir = opendir('../../lang')) {
				while (($file = readdir($dir)) !== false) {
					if ($file != '.' AND $file != '..') {
						if (is_numeric(substr($file, 0, strpos($file, '.')))) {
							$i = substr($file, 0, strpos($file, '.'));
							$file = '../../lang/'.$i.'.php';
							if (file_exists($file)) {
								include($file);
								$lang_array[$i] = $lang['language'];
							}
						}
					}
				}
			}
			
			// nastavimo jezik nazaj
			/*$file = '../../lang/'.$lang_admin.'.php';
			if (file_exists($file)) {
				include($file);
			}*/
			$lang = $lang_old;
			$resp_change_lang = SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang');
			echo '<p>'.$lang['srv_language_admin_0'].': '.$lang_array[$lang_admin].' / '.$lang_array[$lang_resp].'</p>';

			#obveščanje
			
			// jezikovni linki
			$p = new Prevajanje($this->anketa);
			$jeziki = $p->get_all_translation_langs();
			if (count($jeziki) > 0) {
				echo '<p>' . $lang['srv_trans_lang'] . ': ';
				$i = 0;
				foreach ($jeziki AS $key => $val) {
					if ($i++ != 0) echo ', ';
					echo '<a href="'.$link.'?language='.$key.'&f=vabila_settings" target="_blank">'.$val.'</a>';
				}
				echo '</p>';
			}		
			#piškotki
			echo '<p>'.$lang['srv_cookie'].': '.$lang['srv_cookie_'.$row['cookie']].'</p>';
			echo '<p>'.$lang['srv_cookie_return'].': '.($row['cookie_return'] == 0 ? $lang['srv_cookie_return_start'] : $lang['srv_cookie_return_middle']).'</p>';
			
			#more - več
			echo '<div id="srv_objava_info_more1" class="as_link" onclick="$(\'#srv_objava_info_more, #srv_objava_info_more1, #srv_objava_info_more2\').toggle();">'.$lang['srv_more'].'</div>';
			echo '<div id="srv_objava_info_more2" class="as_link displayNone" onclick="$(\'#srv_objava_info_more, #srv_objava_info_more1, #srv_objava_info_more2\').toggle();">'.$lang['srv_less'].'</div>';
			echo '<div id="srv_objava_info_more" class="displayNone">';
			
			if ($row['cookie'] > -1) {
				# če je piškotek dlje kot do konca nakete lahko izbere tudi druge možnosti
				echo '<p>'.$lang['srv_return_finished'].': '.($row['return_finished'] == 1 ? $lang['srv_return_finished_yes'] : $lang['srv_return_finished_no']).'</p>';
			} else {
				# ker je piškotek samo do konca ankete se ne more vrnit ali urejat
				echo '<p>'.$lang['srv_return_finished'].': '. $lang['srv_return_finished_no'] .'</p>';
			}
			
			echo '<p>'.$lang['srv_multilang'].': '.($row['multilang'] == 1 ? $lang['yes'] : $lang['no'] ).'</p>';
			
			echo '<p>'.$lang['srv_user'].': ';
			if ($row['user_from_cms'] == 1) {
				echo $lang['srv_respondent'];
			} elseif ($row['user_from_cms'] == 2) {
				echo $lang['srv_vnasalec'];
			} elseif ($row['user_from_cms'] == 0) {
				echo $lang['no1'];
			}
			echo '</p>';
			
			echo '<p>'.$lang['srv_block_ip'].': ';
			if ($row['block_ip'] == 0) {
				echo $lang['no1'];
			} elseif ($row['block_ip'] == 10) {
				echo '10 min';
			} elseif ($row['block_ip'] == 20) {
				echo '20 min';
			} elseif ($row['block_ip'] == 60) {
				echo '60 min';
			} elseif ($row['block_ip'] == 720) {
				echo '12 '.$lang['hour_hours2'];
			} elseif ($row['block_ip'] == 1440) {
				echo '24 '.$lang['hour_hours2'];
			}
			echo '</p>';
			
			
			# user from cms	
			if ($row['user_from_cms']>0) {
				echo '<p>'.$lang['srv_user_cms_show'].': '.($lang['srv_user_cms_email']).'</p>';
			} 
			echo '<p>'.$lang['srv_vote_limit'].': '.($row['vote_limit'] == 0 ? $lang['no'] : $lang['yes']).'</p>';
			
			echo '<p>'.$lang['srv_vote_count'].': '.($row['vote_limit'] == 0 ? '/' : $row['vote_count']).'</p>';
			# Obveščanje
			// preberemo nastavitve alertov
			$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '".$this->anketa."'");
			if (mysqli_num_rows($sqlAlert) > 0) {
				$rowAlert = mysqli_fetch_assoc($sqlAlert);
			} else {
				SurveyAlert::getInstance()->Init($anketa, $global_user_id);
				$rowAlert = SurveyAlert::setDefaultAlertBeforeExpire();
			}
			
			$alert_finish = array();
			$alert_expire = array();
			$alert_delete = array();
			$alert_active = array();
			if ($rowAlert['finish_respondent'] == 1) {
				$alert_finish[] = $lang['srv_alert_respondent'];
			}
			if ($rowAlert['finish_respondent_cms'] == 1) {
				$alert_finish[] = $lang['srv_alert_respondent_cms'];
			}
			if ($rowAlert['finish_author'] == 1) {
				$alert_finish[] = $lang['srv_info_author'];
			}
			if ($rowAlert['finish_other'] == 1) {
				$alert_finish[] = $lang['email_prejemniki'];
			}
			if ($rowAlert['expire_author'] == 1) {
				$alert_expire[] = $lang['srv_info_author'];
			}
			if ($rowAlert['expire_other'] == 1) {
				$alert_expire[] = $lang['email_prejemniki'];
			}
			if ($rowAlert['delete_author'] == 1) {
				$alert_delete[] = $lang['srv_info_author'];
			}
			if ($rowAlert['delete_other'] == 1) {
				$alert_delete[] = $lang['email_prejemniki'];
			}
			if ($rowAlert['active_author'] == 1) {
				$alert_active[] = $lang['srv_info_author'];
			}
			if ($rowAlert['active_other'] == 1) {
				$alert_active[] = $lang['email_prejemniki'];
			}
			echo '<p>'.$lang['srv_alert_completed_2'].': '.(count($alert_finish) ? implode(',',$alert_finish) : $lang['no']).'</p>';
			echo '<p>'.$lang['srv_alert_expired_2'].': '.(count($alert_expire) ? implode(',',$alert_expire) : $lang['no']).'</p>';
			echo '<p>'.$lang['srv_alert_active_2'].': '.(count($alert_active) ? implode(',',$alert_active) : $lang['no']).'</p>';
			echo '<p>'.$lang['srv_alert_delete_2'].': '.(count($alert_delete) ? implode(',',$alert_delete) : $lang['no']).'</p>';
			
			echo '</div>';
		}
		
		echo '</fieldset>';
		echo '</td>';
		
		echo '<td>';
		echo '</td>';
		
		echo '</tr>';
		
		echo '</table>';
	}
	
	function niceUrlSettings() {
		global $lang, $site_url, $global_user_id;
		
		$p = new Prevajanje($this->anketa);
		$p->dostop();
		$lang_array = $p->get_all_translation_langs();
		
		$link = SurveyInfo::getSurveyLink();
		$preview_disableif = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disableif');
		$preview_disablealert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disablealert');
		$preview_displayifs = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayifs');
		$preview_displayvariables = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayvariables');
		$preview_hidecomment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hidecomment');
		$preview_options = ''.($preview_disableif==1?'&disableif=1':'').($preview_disablealert==1?'&disablealert=1':'').($preview_displayifs==1?'&displayifs=1':'').($preview_displayvariables==1?'&displayvariables=1':'').($preview_hidecomment==1?'&hidecomment=1':'').'';
		
		
		echo '<fieldset><legend>'.$lang['srv_publication_base_title'].'</legend>';
		
		// Predogled url
		echo '<div class="publish_url_holder">';	
		
		echo '<p style="margin: 2px 0;"><a href="' . $link . '&preview=on'.$preview_options.'" target="_blank" class="srv_icox spaceRight"><span class="faicon preview"></span> ' . $lang['srv_poglejanketo2'] . '</b></a>';
		echo '<span class="spaceLeft italic">('.$lang['srv_preview_text'].')</span>';
		echo '<p style="margin: 2px 0;">' . $lang['url'] . ': ' . $link . '&preview=on'.$preview_options.'';
		echo '<a href="#" onclick="CopyToClipboard(\''. $link . '&preview=on'.$preview_options.'\');" return false;" title="Kopiraj povezavo" class="srv_ico">'
			.'&nbsp;&nbsp'
			. '<span class="faicon copy"></span></a></p>';	
		
		echo '</div>';
		
		// Test url
		if($this->survey_type > 1){
			echo '<div class="publish_url_holder">';
			
			echo '<p style="margin: 2px 0;"><a href="' . $link . '&preview=on&testdata=on'.$preview_options.'" title="" target="_blank" class="srv_ico spaceRight"><span class="faicon test large"></span> ' . $lang['srv_survey_testdata2'] . '</b></a>';
			echo '<span class="spaceLeft italic">('.$lang['srv_testdata_text'].')</span></p>';
			echo '<p style="margin: 2px 0;">'.$lang['url'] . ': ' . $link . '&preview=on&testdata=on'.$preview_options;
			echo '<a href="#" onclick="CopyToClipboard(\''. $link . '&preview=on&testdata=on'.$preview_options.'\');" return false;" title="Kopiraj povezavo" class="srv_ico">'
			.'&nbsp;&nbsp'
			. '<span class="faicon copy"></span></a>';
			echo ' (<a href="#" id="popup-open" onclick="javascript:testiranje_preview_settings(); return false;">'.$lang['srv_testrianje_how'].'</a>)</p>';	
			
			echo '</div>';
		}
		
		// Navaden url
		echo '<div class="publish_url_holder">';
		
		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<p style="margin: 2px 0;"><a href="' . $link . '" target="_blank" class="srv_icox spaceRight"><span class="faicon edit_square large"></span> ' . $lang['srv_survey_real'] . '</b></a>';
		echo '<span class="spaceLeft italic">('.$lang['srv_survey_real_savedata'].')</span></p>';

        echo '<span class="'.($row['active']==1?'url_box_active':'').'" style="display:block;">' . $lang['url'] . ': &nbsp;';
		
		$p->include_lang($p->lang_resp);
		$base_lang_resp = $lang['language'];
		$p->include_base_lang();
		
		$link1 = $site_url.'a/'.$row['hash'];
		echo '<b><a href="'.$link1.'" target="_blank">'.$link1.'</a>'.(count($lang_array) > 0 ? ' - '.$base_lang_resp : '').'</b>';

		echo '<a href="#" onclick="CopyToClipboard(\''.$link1.'\');" return false;" title="Kopiraj povezavo" class="srv_ico">'
			.'&nbsp;&nbsp'
			. '<span class="faicon copy"></span></a>';

        // Zlistamo vse lepe url-je
        $sqll = sisplet_query("SELECT id, link FROM srv_nice_links WHERE ank_id = '$this->anketa' ORDER BY id ASC");
        while ($rowl = mysqli_fetch_assoc($sqll)) {

            $link_nice = $site_url . $rowl['link'];

            echo '<br/><span style="margin-left:35px; margin-top:5px; display:inline-block;" ><b>';
            echo '<a href="'.$link_nice.'" target="_blank">'.$link_nice.'</a>'.(count($lang_array) > 0 ? ' - '.$base_lang_resp : '').'</b></span>';


            //echo '<b><a href="'.$site_url.$rowl['link'].'" target="_blank">'.$site_url.$rowl['link'].'</a></b>';
            //echo '<a href="ajax.php?a=nice_url_remove&anketa='.$this->anketa.'&nice_url='.$rowl['id'].'" title="'.$lang['srv_copy_remove'].'"><img src="img_0/if_remove.png" /></a></b></span>';
        }

        // Imamo vec linkov za skupine
		$ss = new SurveySkupine($this->anketa);
		$spr_id = $ss->hasSkupine();	
		if($spr_id > 0){		
			$vrednosti = $ss->getVrednosti($spr_id);
			foreach($vrednosti as $vrednost){
				$link_skupine = isset($vrednost['nice_url']) ? $vrednost['nice_url'] : $vrednost['url'];
				echo '<br/><span style="margin-left:35px; margin-top:5px; display:inline-block;" ><b>';
				echo '<a href="'.$link_skupine.'" target="_blank">'.$link_skupine.'</a>'.(count($lang_array) > 0 ? ' - '.$base_lang_resp : '').' - '.$vrednost['naslov'].'</b></span>';
			}
		}
		
		// Imamo vec linkov za jezike
		if (count($lang_array) > 0) {
			foreach ($lang_array AS $lang_id => $lang_name) {
				echo '<br/><span style="margin-left:35px; margin-top:5px; display:inline-block;" ><b>';
				echo '<a href="'.$link.'?language='.$lang_id.'" target="_blank">'.$link.'?language='.$lang_id.'</a> - '.$lang_name.'</b></span>';
				
				if($spr_id > 0){
					foreach($vrednosti as $vrednost){
						$link_skupine = isset($vrednost['nice_url']) ? $vrednost['nice_url'] : $vrednost['url'];
						echo '<br/><span style="margin-left:35px; margin-top:5px; display:inline-block;" ><b>';
						echo '<a href="'.$link_skupine.'&language='.$lang_id.'" target="_blank">'.$link_skupine.'&language='.$lang_id.'</a> - '.$lang_name.' - '.$vrednost['naslov'].'</b></span>';
					}
				}
			}
		}
		
        echo '</span>';
        
        echo '</fieldset>';
        

        echo '<br />';

        
        // Okno za dodajanje lepega url-ja
        echo '<fieldset><legend>'.$lang['srv_nice_url'].'</legend>';
        
        // Zlistamo vse lepe url-je
        $sqll = sisplet_query("SELECT id, link FROM srv_nice_links WHERE ank_id = '$this->anketa' ORDER BY id ASC");
        while ($rowl = mysqli_fetch_assoc($sqll)) {

            echo '<span style="margin-top: 10px; display:inline-block;">';
            echo '<b><a href="'.$site_url.$rowl['link'].'" target="_blank">'.$site_url.$rowl['link'].'</a></b>';
            
            // Remove nice url
            echo '<a href="ajax.php?a=nice_url_remove&anketa='.$this->anketa.'&nice_url='.$rowl['id'].'" title="'.$lang['srv_copy_remove'].'"><span class="faicon delete_circle icon-orange_link spaceLeft"></span></a>';

            echo '<br />';
        }

        echo '<br />';

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess($what='nice_url')){
            $userAccess->displayNoAccess($what='nice_url');
        }
        else{
            // Gumb za dodajanje lepega linka
            //echo '<div class="" style="margin-top:5px;"><a href="#" onclick="$(\'#spn_nice_url\').toggle(); return false;">' . $lang['srv_nice_url'] . '</a>&nbsp;'.Help::display('srv_nice_url');
            echo '<div class="buttonwrapper"><a class="ovalbutton floatLeft" title="' . $lang['srv_nice_url_add'] . '" href="#" onclick="$(\'#spn_nice_url\').fadeToggle(); return false;">' . $lang['srv_nice_url_add'] . '</a></div>&nbsp;'.Help::display('srv_nice_url');
            

            echo '<br /><span id="spn_nice_url" '.(isset($_GET['error']) ? '' : 'style="display:none;"').'><br /><br />';
            
            echo $site_url.' <input type="text" name="nice_url" id="nice_url" value="" /> <input type="submit" value="'.$lang['add'].'" onclick="$.redirect(\'ajax.php?a=nice_url\', {anketa: '.$this->anketa.', nice_url: $(\'#nice_url\').val()}); return false;" />';

            echo '</span>';
            echo '</div>';
                
            if (isset($_GET['error'])) {
                
                // Prekratek lep url
                if(strlen($_GET['error']) <= 2)
                    echo '<br /><br /><span class="red"><b>'.$_GET['error'].'</b> '.$lang['srv_nice_url_short'].'</span>';
                // Predolg lep url
                elseif(strlen($_GET['error']) > 20)
                    echo '<br /><br /><span class="red"><b>'.$_GET['error'].'</b> '.$lang['srv_nice_url_long'].'</span>';
                // Ze obstaja
                else
                    echo '<br /><br /><span class="red"><b>'.$_GET['error'].'</b> '.$lang['srv_nice_url_taken'].'</span>';
            }

            echo '</div>';
        }

        echo '<br /><br />';
		
		echo '</fieldset>';
	}
	
	function anketa_vabila_url() {
		echo '<table width="100%">';
		echo '<tr style="">';
		
		echo '<td style="vertical-align:top;">';
		$this->displayInvSurveyEmbed();
		
		// Embed v popup je zaenkrat disablan zaradi cross domain omejitev browserjev
		//$this->displayInvSurveyPopup();
		echo '</td>';
		
		echo '<td style="width:45%; vertical-align:top;">';
		$this->displayInvSurveyLink();
		echo '</td>';
		
		echo '</tr>';
		echo '</table>';
	}
	
	function displayInvSurveyEmbed() {
		global $lang;
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_embed_title'].':</legend>';
		
		echo '<p><span onclick="$(\'#embed_js\').toggle(); $(\'#embed_js textarea\').click();" class="as_link">'.$lang['srv_embed_js'].Help :: display('srv_embed_js').'</span></p>';
		echo '<p id="embed_js" '.($_GET['js']!='open'?'style="display:none"':'').'><textarea id="ta" style="width: 99%; height:80px" onclick="this.select();" readonly>'.$this->getEmbed().'</textarea></p>';
			
		echo '<p><span onclick="$(\'#embed_js_fixed\').toggle(); $(\'#embed_js_fixed textarea\').click();" class="as_link">'.$lang['srv_embed_fixed'].Help :: display('srv_embed_fixed').'</span></p>';
		echo '<p id="embed_js_fixed" '.($_GET['js']!='open'?'style="display:none"':'').'><textarea style="width: 99%; height:80px" onclick="this.select();" readonly>'.$this->getEmbed(false).'</textarea></p>';
		
		
		echo '</fieldset>';
		
		if ($_GET['js'] == 'open') {
			?><script>
				$('#ta').click();
			</script><?
		}
	}
	function displayInvSurveyPopup() {
		global $lang;
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_popup_title'].':</legend>';
		
		echo '<p><span onclick="$(\'#popup\').toggle(); $(\'#popup textarea\').click();" class="as_link">'.$lang['srv_embed_js'].Help :: display('srv_popup_js').'</span></p>';
		echo '<p id="popup" '.($_GET['js']!='open'?'style="display:none"':'').'><textarea id="pop" style="width: 99%; height:80px" onclick="this.select();" readonly>'.$this->getPopup().'</textarea></p>';
		
		echo '</fieldset>';
		
	}
	function displayInvSurveyLink() {
		global $lang, $site_url, $admin_type;

		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<fieldset>';
		echo '<legend>' . $lang['srv_user_base_url'] . '</legend>';
		
		echo '<p><div class="as_link"><label onclick="$(\'#anketa_href\').toggle(); $(\'#anketa_href textarea\').click(); $(\'#space1\').toggle();" class="pointer">' . $lang['srv_anketa_href'] . ' </label></div>';
		echo '<div id="anketa_href" class="displayNone"><br>'.$lang['srv_anketa_href_text'].' <textarea style="width:99%; height:24px;" onclick="this.select();" readonly id="href">';
		echo '&lt;a href="'.SurveyInfo::getSurveyLink().'"&gt;'.$lang['srv_complete_survey'].'&lt;/a&gt;';
		echo '</textarea></div></p>';
		echo '<p><div class="as_link" ><label onclick="$(\'#anketa_href_count\').toggle(); $(\'#space2\').toggle(); $(\'#anketa_href_count textarea\').click();" class="pointer">' . $lang['srv_anketa_href_count'] . ' </label></div>';
		echo '<div id="anketa_href_count" class="displayNone"><br>'.$lang['srv_anketa_href_count_text'].' <textarea style="width:99%; height:48px" onclick="this.select();" readonly>';
		echo '&lt;a href="'.SurveyInfo::getSurveyLink().'"&gt;'.$lang['srv_complete_survey'].'&lt;/a&gt;&lt;img src="'.$site_url.'main/survey/view_count.php?a='.$this->anketa.'" style="display:none"/&gt;';
		echo '</textarea></div></p>';
		
		echo '</fieldset><fieldset>';
		
		// Prikaz QR kode
		$img = 'classes/phpqrcode/imgs/code'.$this->anketa.'.png';
		QRcode::png(SurveyInfo::getSurveyLink(), $img, 'L', 4, 2);

		echo '<div class="as_link"><label onclick="$(\'#anketa_qr_code\').toggle();" class="pointer" title="'.$lang['srv_qr_code'].'">' . $lang['srv_qr_code'] . ' </label></div>';
#		echo '<p>'.$lang['srv_qr_code'].':<br>';
		echo '<div id="anketa_qr_code" class="displayNone">';
		echo '<img src="'.$site_url.'admin/survey/'.$img.'">';
		echo '</div>';
		
		// Prikaz ikon za deljenje (FB, twitter...)
		echo '<p class="clr" style="margin-top:15px;"><span class="labelSpanWide"><label>'.$lang['srv_share'].': </label></span> ';
		
		?>
			<div class="addthis_toolbox addthis_default_style">
			
			<a class="addthis_button_facebook" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			<a class="addthis_button_gmail" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			
			<!-- <a class="addthis_button_preferred_1" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			<a class="addthis_button_preferred_2" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>-->
			
			<!-- 
			<a class="addthis_button_preferred_3" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			<a class="addthis_button_preferred_4" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			<a class="addthis_button_preferred_5" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			<a class="addthis_button_preferred_6" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			 -->
			 
			<span class="addthis_separator">|</span>
			<a href="https://www.addthis.com/bookmark.php?v=250" class="addthis_button_compact" addthis:url="<?=SurveyInfo::getSurveyLink()?>" addthis:title="<?=$row['akronim']?>"></a>
			</div>
			<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js"></script>
			
			<script type="text/javascript">
				var addthis_config = {
					data_track_clickback: false,
					services_exclude: 'print'
				}		
			</script>
			
		<?php
		
		echo '</p>';
		echo '</fieldset>';
	}
	/**
	* nastavitve za obveščanje na email
	* 
	*/
	function email_nastavitve ($show_fieldset = true) {
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;

		$row = SurveyInfo::getInstance()->getSurveyRow();
		
#		echo '<div id="anketa_edit">';
		
		// če ni aktivna damo opozorilo
			
		echo '<form name="settingsanketa_' . $row['id'] . '" action="ajax.php?a=editanketasettings&m=vabila" method="post" autocomplete="off">' . "\n\r";
		echo '<input type="hidden" name="submited" value="1" />' . "\n\r";
		echo '<div id="userCodeSettings1">';
		$this->respondenti_iz_baze($row,$show_fieldset);
		echo '</div>';
		
		if ($admin_type == 0) {
			if ($show_fieldset) {
				echo '<fieldset><legend>'.$lang['srv_show_mail_with_data'].'</legend>';
			} else {
				echo '<p class="strong">4.'.$lang['srv_show_mail_with_data'].'</p>';
			}
			echo '<span>'.$lang['srv_show_mail_with_data2'].': </span>';
			
			echo '<input type="radio" name="show_email"'.($row['show_email']==0?' checked':'').' value="0">'.$lang['no'].' ';
			echo '<input type="radio" name="show_email"'.($row['show_email']==1?' checked':'').' value="1">'.$lang['yes'].' ';
			echo '<p>* '.$lang['srv_show_mail_with_data3'].'</p>';
			if ($show_fieldset) {
				echo '</fieldset>';
			}
		}
		
		echo '</form>';
		
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;"><span>';
		//			echo '<img src="icons/icons/disk.png" alt="" vartical-align="middle" />';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';
		if ($_GET['s'] == '1') {
			echo '<div id="success_save"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
		
		#echo '</div>';
		
	}
	
	/**
	 * vrne kodo ankete, ki se jo uporabi za embed
	 *
	 */
	function getEmbed ($js = true) {
		global $site_url;
		
		
		$link = SurveyInfo::getSurveyLink();
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$iframe = '<iframe id="1ka" src="'.$link.'?e=1" height="500px" width="100%" scrolling="auto" frameborder="0"></iframe>';
		$javascript = '<script type="text/javascript">function r(){var a=window.location.hash.replace("#","");if(a.length==0)return;document.getElementById("1ka").style.height=a+"px";window.location.hash=""};window.setInterval("r()",100);'
		.'</script>';

		if ($js)
		return htmlentities($iframe.$javascript, ENT_QUOTES);
		else
		return htmlentities($iframe, ENT_QUOTES);
	}
	
	/**
	 * vrne kodo ankete, ki se jo uporabi za popup embed 
	 *
	 */
	function getPopup () {
		global $site_url;
		
		$link = SurveyInfo::getSurveyLink().'&popup=1';
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$javascript = '<script type="text/javascript">window.onload=function() {var body=document.getElementsByTagName("body")[0];var div=document.createElement("div");var iframe=document.createElement("iframe");div.setAttribute("id","popup_div");div.setAttribute("style","position:fixed; top:0; right:0; bottom:0; left:0; background:#000; opacity:0.5");iframe.setAttribute("id","popup_iframe");iframe.setAttribute("src","'.$link.'");iframe.setAttribute("style","position: fixed; top:10%; left:50%; margin-left:-400px; background:#fff; height:80%; width:800px;");iframe.setAttribute("scrolling","auto");iframe.setAttribute("frameborder","0");body.appendChild(div);body.appendChild(iframe)}</script>';
		// Dodatek ce bomo delali se naprej popup embed (cross domain problem)
		//if(window.addEventListener){window.addEventListener("message", function(e){if(e.data == "closePopup"){document.getElementById("popup_iframe").remove();document.getElementById("popup_div").remove();}});}

		return htmlentities($javascript, ENT_QUOTES);
	}
	
	/**
	 * @desc prika?e nastavitve alerta za formo
	 */
	function alert_nastavitve() {//OSTANE
		global $lang;
		global $site_url;
		global $admin_type;
		
		$anketa = $this->anketa;
		
		/* moznosti:
		 * 	'complete'		-> obvsetilo o izpolnjeni anketi (respondent, respondent iz cms, avtor + dostop, dodatn-emaili)
		 *  'delete'		-> obvestilo o izbrisani anketi (avtor + dostop, dodatni -emaili)
		 *  'active'		-> obvestilo o aktivnosti, neaktivnosti ankete (avtor + dostop, dodatni -emaili)
		 *  'expire'		-> obvestilo o izteku ankete (avtor + dostop, dodatni -emaili)
		 *  'email_server'	-> nastavitve mail streznika
		 */
		if ( isset($_GET['m']) && $_GET['m'] != "") {
			$tab = $_GET['m'];
        } 
        else {
			$tab = $_GET['m'] = 'complete';
        }
        
		// preberemo nastavitve alertov
		$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '$anketa'");
		if (!$sqlAlert)
		    echo mysqli_error($GLOBALS['connect_db']);
        
        if (mysqli_num_rows($sqlAlert) > 0) {
			$rowAlert = mysqli_fetch_array($sqlAlert);
        } 
        else {
			SurveyAlert::getInstance()->Init($anketa, $global_user_id);
			$rowAlert = SurveyAlert::setDefaultAlertBeforeExpire();
		}
		
		$days = $rowAlert['expire_days'];
		$sqlS = sisplet_query("SELECT id, expire, survey_type, insert_uid, DATE_SUB(expire,INTERVAL $days DAY) as newdate FROM srv_anketa WHERE id = '$anketa'");
		if (!$sqlS)	echo mysqli_error($GLOBALS['connect_db']);
		$rowS = mysqli_fetch_array($sqlS);
		$rowAlert['newdate'] = $rowS['newdate'];

		$sqlu = sisplet_query("SELECT email FROM users WHERE id = '$rowS[insert_uid]'");
		$rowu = mysqli_fetch_array($sqlu);
		$MailReply = $rowu['email'];
		
		$custom_alert = array();
		$sql_custom_alert = sisplet_query("SELECT uid, type FROM srv_alert_custom WHERE ank_id = '$this->anketa'");
		while ($row_custom_alert = mysqli_fetch_array($sql_custom_alert)) {
			$custom_alert[$row_custom_alert['type']][$row_custom_alert['uid']] = 1;
		}
		
		
		if ($tab == 'complete') {
			
			//echo '<h4>' . $lang['srv_alert_title'] . '</h4>'."\n";
			echo '  <form name="alertanketa_' . $anketa . '" action="ajax.php?a=editanketaalert&m='.$tab.'" method="post" autocomplete="off">' . "\n";
			echo '    <input type="hidden" name="anketa" value="' . $anketa . '" />' . "\n";
			echo '    <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n";
			echo '    <input type="hidden" name="m" value="' . $_GET['m'] . '" />' . "\n";
			echo '    <input type="hidden" name="submited" value="1" />' . "\n";
			
			
			echo '    <fieldset>'. "\n";
			echo '    <legend>' . $lang['srv_alert_prejemnik'] . '</legend>'. "\n";
				
            // respondent - ne prikazemo ce gre za glasovanje oz. volitve
            if($rowS['survey_type'] != 0 && !SurveyInfo::getInstance()->checkSurveyModule('voting')){
                echo '<p>';
                echo '<input type="checkbox" name="alert_finish_respondent" id="alert_finish_respondent" value="1" onChange="change_alert_respondent(\'finish_respondent\', $(this)); $(\'form[name=alertanketa_' . $anketa . ']\').submit(); return false;" ' . ($rowAlert['finish_respondent'] == 1 ? ' checked' : '') . '>';
                echo '<span id="label_alert_finish_respondent">';
                $this->display_alert_label('finish_respondent',($rowAlert['finish_respondent'] == 1));
                echo '</span>'. "\n";
                
                // Ce imamo vec prevodov omogocimo za vsak prevod svoj email
                $this->display_alert_label('finish_respondent_language',($rowAlert['finish_respondent'] == 1));
                echo '</p>';
            }

            // respondent iz cms ne prikazemo ce gre za volitve
            if(!SurveyInfo::getInstance()->checkSurveyModule('voting')){
                echo '<p><input type="checkbox" name="alert_finish_respondent_cms" id="alert_finish_respondent_cms" value="1" onChange="change_alert_respondent(\'finish_respondent_cms\', $(this)); chnage_alert_instruction($(this)); $(\'form[name=alertanketa_' . $anketa . ']\').submit(); return false;" ' . ($rowAlert['finish_respondent_cms'] == 1 ? ' checked' : '') . '>';
                echo '<span id="label_alert_finish_respondent_cms">';
                $this->display_alert_label('finish_respondent_cms',($rowAlert['finish_respondent_cms'] == 1));
                echo '</span></p>'. "\n";
            }

            // avtor ankete oz osebe z dostopom
            //echo '<p><input type="checkbox" name="alert_finish_author" id="alert_finish_author" value="1" onChange="change_alert_respondent(\'finish_author\', $(this)); $(\'form[name=alertanketa_' . $anketa . ']\').submit(); return false;"' . ($rowAlert['finish_author'] == 1 ? ' checked' : '') . '>';
            echo '<p><input type="checkbox" name="alert_finish_author" id="alert_finish_author" value="1" onChange="change_alert_respondent(\'finish_author\', $(this));"' . ($rowAlert['finish_author'] == 1 ? ' checked' : '') . '>';
            echo '<span id="label_alert_finish_author">';
            $this->display_alert_label('finish_author',($rowAlert['finish_author'] == 1));
            echo '</span></p>';

            // posebej navedeni maili
            echo '<p><input type="checkbox" name="alert_finish_other"  id="alert_finish_other"  value="1"' . (($rowAlert['finish_other'] == 1 || ($rowAlert['finish_other_emails'] && $rowAlert['finish_other'] != 0)) ? ' checked' : '') . ' onchange="toggleStatusAlertOtherCheckbox(\'finish_other\'); if ( ! $(this).attr(\'checked\') ) { $(\'form[name=alertanketa_' . $anketa . ']\').submit(); }"><label for="alert_finish_other">' . $lang['email_prejemniki'] . $lang['email_one_per_line'] . '</label>';
            echo ' <a href="#" onclick="alert_custom(\'other\', \'0\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
            echo ' <a href="#" onclick="alert_edit_if(\'4\'); return false;"><span class="faicon if_add" '.($rowAlert['finish_other_if']==0?'style=""':'').'></span></a> ';
            if ($rowAlert['finish_other_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($rowAlert['finish_other_if']); }		
            echo '</p>';
                
            echo '<p id="alert_holder_finish_other_emails" '.($rowAlert['finish_other'] == 0 ? 'class="displayNone"' : '' ).'>';
            echo '<label for="alert_finish_other_emails">' . $lang['email'] . ':</label>' .
            '<textarea name="alert_finish_other_emails" id="alert_finish_other_emails" style="height:100px" onblur="$(\'form[name=alertanketa_' . $anketa . ']\').submit();">' . $rowAlert['finish_other_emails'] . '</textarea>' .
            '</p>';
			
			echo '</fieldset>';
			
			
			echo '<br />';

			echo '<fieldset>';
			echo '<legend>' . $lang['srv_alert_oblika'] . '</legend>';
			echo '<div style="float:left; width:auto;">';
			echo '<p><label for="alert_finish_subject">' . $lang['subject'] . ': <input type="text" id="alert_finish_subject" name="alert_finish_subject" value="' . ($rowAlert['finish_subject'] ? $rowAlert['finish_subject'] : $lang['srv_alert_finish_subject']) . '" size="90"/></label></p>';
			echo '<p><label for="reply_to">'.$lang['srv_replay_to'].': <input type="text" id="reply_to" name="reply_to" value="' . ($rowAlert['reply_to'] ? $rowAlert['reply_to'] : $MailReply) . '" size="40"/></label></p>';

			if ($rowAlert['finish_text'] != '') {
				$text = $rowAlert['finish_text'];
            } 
            else {
                // Podpis
                $signature = Common::getEmailSignature();

				$text = nl2br($lang['srv_alert_finish_text'].$signature);
			}

			// prikaze editor za ne-spremenljivko (za karkoli druzga pac)
			echo '    <p><label for="alert_finish_text">' . $lang['text'] . ':</label>';
			echo '    <textarea name="alert_finish_text" id="alert_finish_text" rows="3" >' . $text . '</textarea>';
			echo '    </p>';
			echo '</div>';
			echo '<div style="float:left; width:auto; max-width:330px; margin-left:10px;">';
			echo '<div id="div_error">';
			echo $lang['srv_alert_instruction1'];
			// ta se skriva, potreben respondent iz CMS, da dobi NAME
			echo '<span id="alert_respondent_cms_instruction" class="'.( $rowAlert['finish_respondent_cms'] == 1 ? '' : 'displayNone').'">'.$lang['srv_alert_instruction2'].'</span>';
			echo $lang['srv_alert_instruction_survey'].'<br/>';
			echo $lang['srv_alert_instruction_date'].'<br/>';
			echo $lang['srv_alert_instruction_site'].'<br/>';
			echo $lang['srv_alert_instruction_url'].'<br/>';
			echo $lang['srv_alert_instruction_pdf'].'<br/>';
			echo $lang['srv_alert_instruction_rtf'].'<br/>';
			
			$row = SurveyInfo::getInstance()->getSurveyRow();
			# če imamo prepoznavanje uporabnik iz CMS, potem ponudimo tudi META_REFERAL_URL
			echo $lang['srv_alert_instruction_meta_referer_url'].'<br/>';
			echo $lang['srv_alert_instruction_system'];
			echo $lang['srv_alert_instruction_sample'];
			echo $lang['srv_alert_instruction_available'];
			
#			echo $lang['srv_alert_instruction3'];
			$sqlSistemske = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='$anketa' ORDER BY g.vrstni_red, s.vrstni_red");
			$prefix = "";
			while ($rowSistemske = mysqli_fetch_assoc($sqlSistemske)) {
				echo $prefix . '#' . $rowSistemske['variable'] . '#';
				$prefix = ", ";
			}
			if ($prefix == "") { // ni sistemskih spremenljivk
				echo '<p class="red">'.$lang['srv_alert_no_sys_var'].'</p>';
			}
			echo '</span>';
			echo '</div>';
			echo '</div>';
			echo '    </fieldset>';
		
			echo '<br />';
			
			echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.alertanketa_' . $rowS['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'] . '</span></a></div></span>';
			
			echo '<div class="clr"></div>';
			
			if ($_GET['s'] == '1') {
				echo '<div id="success_save"></div>';
				echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
			}
			echo '  </form>';
		} else if ($tab == 'expired') {
			global $site_path, $global_user_id;

			//echo '<h4>' . $lang['srv_alert_expired_title'] . '</h4>'."\n";
			echo '  <form name="alertanketa_' . $anketa . '" action="ajax.php?a=editanketaalert&m='.$tab.'" method="post" autocomplete="off">' . "\n";
			echo '    <input type="hidden" name="anketa" value="' . $anketa . '" />' . "\n";
			echo '    <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n";
			echo '    <input type="hidden" name="m" value="' . $_GET['m'] . '" />' . "\n";
			echo '    <fieldset>'. "\n";
			echo '    <legend>' . $lang['srv_alert_expired_time_title'] . '</legend>'. "\n";

			echo $lang['srv_alert_expire_days1'];
			echo '<input type="text" id="alert_expire_days" name="alert_expire_days" value="'.$rowAlert['expire_days'].'" size="3" >';
			echo $lang['srv_alert_expire_days2'];
			echo $lang['srv_alert_expire_expire_at'] . $rowS['expire'].'<span>'.$lang['at'].'00:00</span><br/>';
			echo $lang['srv_alert_expire_note_at'] . '<span id="calc_alert_expire">'.$rowAlert['newdate'].'</span><span>'.$lang['at'].'01:00</span><br/>';
			echo '    </fieldset>';

			echo '<br />';
			echo '    <fieldset>'. "\n";
			echo '    <legend>' . $lang['srv_alert_prejemnik'] . '</legend>'. "\n";
			echo '<p><input type="checkbox" name="alert_expire_author" id="alert_expire_author" value="1" onChange="change_alert_respondent(\'expire_author\', $(this));return false;"' . ($rowAlert['expire_author'] == 1 ? ' checked' : '') . '>';
			echo '<span id="label_alert_expire_author">';
			$this->display_alert_label('expire_author',($rowAlert['expire_author'] == 1));
			echo '</span></p>';
			echo '<p><input type="checkbox" name="alert_expire_other"  id="alert_expire_other"  value="1"' . (($rowAlert['expire_other'] == 1 || ($rowAlert['expire_other_emails'] && $rowAlert['expire_other'] != 0)) ? ' checked' : '') . ' onchange="toggleStatusAlertOtherCheckbox(\'expire_other\');"><label for="alert_expire_other">' . $lang['email_prejemniki'] . $lang['email_one_per_line'] . '</label></p>';
			echo '<p id="alert_holder_expire_other_emails" '.($rowAlert['expire_other'] == 0 ? 'class="displayNone"' : '' ).'>';
			echo '<label for="alert_expire_other_emails">' . $lang['email'] . ':</label>' .
			'    <textarea name="alert_expire_other_emails" id="alert_expire_other_emails" style="height:100px" >' . $rowAlert['expire_other_emails'] . '</textarea>' .
			'    </p>';
			echo '    </fieldset>';

			echo '</fieldset>';
			
			echo '<br />';
			echo '<fieldset>';
			echo '<legend>' . $lang['srv_alert_oblika'] . '</legend>';
			echo '<div style="float:left; width:auto;">';
			echo '<p><label for="subject">' . $lang['subject'] . ': <input type="text" id="alert_expire_subject" name="alert_expire_subject" value="' . ($rowAlert['expire_subject'] ? $rowAlert['expire_subject'] : $lang['srv_alert_expire_subject']) . '" size="90"/></label></p>';

			if ($rowAlert['expire_text'] != ''){
                $text = $rowAlert['expire_text'];
            }
			else{
                // Podpis
                $signature = Common::getEmailSignature();
           
                $text = nl2br($lang['srv_alert_expire_text'].$signature);
            }

			// prikaze editor za ne-spremenljivko (za karkoli druzga pac)
			echo '    <p><label for="alert_expire_text">' . $lang['text'] . ':</label>';
			echo '    <textarea name="alert_expire_text" id="alert_expire_text" rows="3" >' . $text . '</textarea>';
			echo '    </p>';
			echo '</div>';
			echo '<div style="float:left; width:auto; max-width:550px; margin-left:10px;">';
			echo '<div id="div_error">';
			echo $lang['srv_alert_instruction1'];
			echo $lang['srv_alert_instruction4'];

			echo '</div>';
			echo '</div>';
			echo '</fieldset>';
			echo '<br />';
			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.alertanketa_' . $rowS['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'] . '</span></a></div></span>';
			echo '<div class="clr"></div>';
			if ($_GET['s'] == '1') {
				echo '<div id="success_save"></div>';
				echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
			}
			echo '</form>';
		} else if ($tab == 'active') {
			//echo '<h4>' . $lang['srv_alert_active_title'] . '</h4>'."\n";
			echo '  <form name="alertanketa_' . $anketa . '" action="ajax.php?a=editanketaalert&m='.$tab.'" method="post" autocomplete="off">' . "\n";
			echo '    <input type="hidden" name="anketa" value="' . $anketa . '" />' . "\n";
			echo '    <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n";
			echo '    <input type="hidden" name="m" value="' . $_GET['m'] . '" />' . "\n";

			echo '<fieldset>'. "\n";
			echo '<legend>' . $lang['srv_alert_prejemnik'] . '</legend>'. "\n";
			echo '<p><input type="checkbox" name="alert_active_author" id="alert_active_author" value="1" onChange="change_alert_respondent(\'active_author\', $(this));return false;"' . ($rowAlert['active_author'] == 1 ? ' checked' : '') . '>';
			echo '<span id="label_alert_active_author">';
			$this->display_alert_label('active_author',($rowAlert['active_author'] == 1));
			echo '</span></p>';
			echo '<p><input type="checkbox" name="alert_active_other"  id="alert_active_other"  value="1"' . (($rowAlert['active_other'] == 1 || ($rowAlert['active_other_emails'] && $rowAlert['active_other'] != 0)) ? ' checked' : '') . ' onchange="toggleStatusAlertOtherCheckbox(\'active_other\');"><label for="alert_active_other">' . $lang['email_prejemniki'] . $lang['email_one_per_line'] . '</label></p>';
			echo '<p id="alert_holder_active_other_emails" '.($rowAlert['active_other'] == 0 ? 'class="displayNone"' : '' ).'>';
			echo '<label for="alert_active_other_emails">' . $lang['email'] . ':</label>';
			echo '<textarea name="alert_active_other_emails" id="alert_active_other_emails" style="height:100px" >' . $rowAlert['active_other_emails'] . '</textarea>' .
			'</p>';
			echo '</fieldset>';

			echo '</fieldset>';
			
			echo '<br />';
			echo '<fieldset>';
			echo '<legend>' . $lang['srv_alert_oblika'] . '</legend>';
			echo '<div style="float:left; width:auto;">';
			echo '<p>' . $lang['srv_alert_oblika_deactivate_note'] . '</p>';
			echo '<p><label for="subject">' . $lang['subject'] . ': ';
			echo '<input type="text" name="alert_active_subject0" id="alert_active_subject0" value="' . ($rowAlert['active_subject0'] ? $rowAlert['active_subject0'] : $lang['srv_alert_active_subject0']) . '" size="90"/></label></p>';

			if ($rowAlert['active_text0'] != '') {
				$text0 = $rowAlert['active_text0'];
            } 
            else {
                // Podpis
                $signature = Common::getEmailSignature();

				$text0 = nl2br($lang['srv_alert_active_text0'].$signature);
			}
			// prikaze editor za ne-spremenljivko (za karkoli druzga pac)
			echo '    <p><label for="alert_active_text0">' . $lang['text'] . ':</label>';
			echo '    <textarea name="alert_active_text0" id="alert_active_text0" rows="3" >' . $text0 . '</textarea>';
			echo '    </p>';

			echo '<br/>';
			echo '<p>' . $lang['srv_alert_oblika_activate_note'] . '</p>';
			echo '<p><label for="subject">' . $lang['subject'] . ': ';
			echo '<input type="text" name="alert_active_subject1" id="alert_active_subject1" value="' . ($rowAlert['active_subject1'] ? $rowAlert['active_subject1'] : $lang['srv_alert_active_subject1']) . '" size="90"/></label></p>';

			if ($rowAlert['active_text1'] != '') {
				$text1 = $rowAlert['active_text1'];
            } 
            else {
                // Podpis
                $signature = Common::getEmailSignature();

				$text1 = nl2br($lang['srv_alert_active_text1'].$signature);
			}
			
			echo '    <p><label for="alert_active_text1">' . $lang['text'] . ':</label>';
			echo '    <textarea name="alert_active_text1" id="alert_active_text1" rows="3" >' . $text1 . '</textarea>';
			echo '    </p>';

			echo '</div>';
			echo '<div style="float:left; width:auto; max-width:550px; margin-left:10px;">';
			echo '<div id="div_error">';
			echo $lang['srv_alert_instruction1'];
			echo $lang['srv_alert_instruction5'];
			echo '</div>';
			echo '</div>';
			echo '</fieldset>';
			echo '<br />';
			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.alertanketa_' . $rowS['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'] . '</span></a></div></span>';
			echo '<div class="clr"></div>';
			if ($_GET['s'] == '1') {
				echo '<div id="success_save"></div>';
				echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
			}
			echo '</form>';
		} else if ($tab == 'delete') {
			//echo '<h4>' . $lang['srv_alert_delete_title'] . '</h4>'."\n";
			echo '  <form name="alertanketa_' . $anketa . '" action="ajax.php?a=editanketaalert&m='.$tab.'" method="post" autocomplete="off">' . "\n";
			echo '    <input type="hidden" name="anketa" value="' . $anketa . '" />' . "\n";
			echo '    <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n";
			echo '    <input type="hidden" name="m" value="' . $_GET['m'] . '" />' . "\n";

			echo '<fieldset>'. "\n";
			echo '<legend>' . $lang['srv_alert_prejemnik'] . '</legend>'. "\n";
			echo '<p><input type="checkbox" name="alert_delete_author" id="alert_delete_author" value="1" onChange="change_alert_respondent(\'delete_author\', $(this));return false;"' . ($rowAlert['delete_author'] == 1 ? ' checked' : '') . '>';
			echo '<span id="label_alert_delete_author">';
			$this->display_alert_label('delete_author',($rowAlert['delete_author'] == 1));
			echo '</span></p>';
			echo '<p><input type="checkbox" name="alert_delete_other"  id="alert_delete_other"  value="1"' . (($rowAlert['delete_other'] == 1 || ($rowAlert['delete_other_emails'] && $rowAlert['delete_other'] != 0)) ? ' checked' : '') . ' onchange="toggleStatusAlertOtherCheckbox(\'delete_other\');"><label for="alert_delete_other">' . $lang['email_prejemniki'] . $lang['email_one_per_line'] . '</label></p>';
			echo '<p id="alert_holder_delete_other_emails" '.($rowAlert['delete_other'] == 0 ? 'class="displayNone"' : '' ).'>';
			echo '<label for="alert_delete_other_emails">' . $lang['email'] . ':</label>';
			echo '<textarea name="alert_delete_other_emails" id="alert_delete_other_emails" style="height:100px" >' . $rowAlert['delete_other_emails'] . '</textarea>';
			echo '</p>';
			echo '</fieldset>';

			echo '</fieldset>';
			
			echo '<br />';
			echo '<fieldset>';
			echo '<legend>' . $lang['srv_alert_oblika'] . '</legend>';
			echo '<div style="float:left; width:auto;">';
			echo '<p><label for="subject">' . $lang['subject'] . ': <input type="text" id="alert_delete_subject" name="alert_delete_subject" value="' . ($rowAlert['delete_subject'] ? $rowAlert['delete_subject'] : $lang['srv_alert_delete_subject']) . '" size="90"/></label></p>';

			if ($rowAlert['delete_text'] != '') {
				$text = $rowAlert['delete_text'];
            } 
            else {
                // Podpis
                $signature = Common::getEmailSignature();

				$text = nl2br($lang['srv_alert_delete_text'].$signature);
			}
			// prikaze editor za ne-spremenljivko (za karkoli druzga pac)
			echo '    <p><label for="alert_delete_text">' . $lang['text'] . ':</label>';
			echo '    <textarea name="alert_delete_text" id="alert_delete_text" rows="3" >' . $text . '</textarea>';
			echo '    </p>';
			echo '</div>';
			echo '<div style="float:left; width:auto; max-width:550px; margin-left:10px;">';
			echo '<div id="div_error">';
			echo $lang['srv_alert_instruction1'];
			echo $lang['srv_alert_instruction5'];
			echo '</div>';
			echo '</div>';
			echo '</fieldset>';
			echo '<br />';
			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.alertanketa_' . $rowS['id'] . '.submit(); return false;"><span>';
			echo $lang['edit1337'] . '</span></a></div></span>';
			echo '<div class="clr"></div>';
			if ($_GET['s'] == '1') {
				echo '<div id="success_save"></div>';
				echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
			}
			echo '</form>';

		} else if ($tab == 'email_server') {
			
			// Gorenje tega nima
			if (!Common::checkModule('gorenje') && $admin_type == '0')
				$this->viewServerSettings();
			
			
		} else {
			print_r($tab);
		}


		?>
		<script type="text/javascript">
			alleditors_remove ();
		if ($("#alert_finish_text").length)
			create_editor("alert_finish_text", false);
		if ($("#alert_expire_text").length)
			create_editor("alert_expire_text", false);
		if ($("#alert_active_text0").length)
			create_editor("alert_active_text0", false);
		if ($("#alert_active_text1").length)
			create_editor("alert_active_text1", false);
		if ($("#alert_delete_text").length)
			create_editor("alert_delete_text", false);

		$("#alert_expire_days").bind("keyup", function(e) {
			oldVal = this.value;
			newVal = this.value;
            if (this.value.match(/[^0-9 ]/g)) {
                newVal = this.value.replace(/[^0-9 ]/g, '');
                this.value = newVal; 
            };
            if (oldVal == newVal) // da ne postamo za vsako malenkost :)
              recalc_alert_expire(newVal);
        });
		
		</script>
		<?php
	}
	
	function viewServerSettings(){
		global $lang;
		global $admin_type;
		global $global_user_id;
		global $mysql_database_name;
	
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_user_base_email_server_settings'].'</legend>';
	
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		// Opozorilo, ce imamo vklopljena vabila, da gre za iste nastavitve
		$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');
		if($isEmail)
			echo '<p class="red bold">'.$lang['srv_email_server_settings_warning'].'</p>';
	
	
		echo '<form name="settingsanketa_' . $row['id'] . '" action="ajax.php?a=editanketasettings&m=email_server" method="post" autocomplete="off">' . "\n\r";
		echo '	<input type="hidden" name="anketa" value="' . $this->anketa . '" />' . "\n\r";
		echo '  <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";
		echo '  <input type="hidden" name="m" value="' . $_GET['m'] . '" />' . "\n";
		echo '  <input type="hidden" name="submited" value="1" />' . "\n\r";

		
		$MA = new MailAdapter($this->anketa);
		
		// Dostop za posiljanje mailov preko 1ka serverja
		$enabled1ka = ( $MA->is1KA() || (($admin_type == 0) && ($mysql_database_name == 'www1kasi' || $mysql_database_name == 'test1kasi' || $mysql_database_name == 'real1kasi' || $mysql_database_name == '1kaarnessi')) ) ? true : false;
		
		echo '<p>';
		echo '<span class="bold">'.$lang['srv_email_setting_select_server'].'</span>&nbsp;';
		echo '<label><input type="radio" name="SMTPMailMode" value="0" '.($MA->is1KA() ? 'checked ="checked" ' : '').' '.($enabled1ka ? '' : ' disabled="disabled"').' onclick="$(\'#send_mail_mode1, #send_mail_mode2\').hide();$(\'#send_mail_mode0\').show();">';
        echo $lang['srv_email_setting_adapter0']. ' </label>';
        // Google smtp je viden samo starim, kjer je ze vklopljen
        if($MA->isGoogle()){
		    echo '<label><input type="radio" name="SMTPMailMode" value="1" '.($MA->isGoogle() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode0, #send_mail_mode2\').hide(); $(\'#send_mail_mode1\').show();">';
            echo $lang['srv_email_setting_adapter1'].' </label>';
        }
		echo '<label><input type="radio" name="SMTPMailMode" value="2" '.($MA->isSMTP() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode0, #send_mail_mode1\').hide(); $(\'#send_mail_mode2\').show();">';
		echo $lang['srv_email_setting_adapter2'].' </label>';
		echo Help :: display('srv_mail_mode');
		echo '</p>';
		
		
		#1KA
		$enkaSettings = $MA->get1KASettings($raziskave=true);
		echo '<div id="send_mail_mode0" '.(!$MA->is1KA() ? ' class="displayNone"' : '').'>';
		echo '<span class="bold">'.$lang['srv_email_setting_settings'].'</span>';
		echo '<br />';	
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<span>'.$enkaSettings['SMTPFrom'].'</span><input type="hidden" name="SMTPFrom0" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo0" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		echo '</div>';
		
		#GMAIL - Google
		$enkaSettings = $MA->getGoogleSettings();
		echo '<div id="send_mail_mode1" '.(!$MA->isGoogle() ? ' class="displayNone"' : '').'>';
		echo '<span class="italic">'.$lang['srv_email_setting_adapter1_note'].'</span><br />';
		echo '<br /><span class="bold">'.$lang['srv_email_setting_settings'].'</span><br />';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<input type="text" name="SMTPFrom1" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo1" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		#Password
		echo '<p><label>'.$lang['srv_email_setting_password'].'<input type="password" name="SMTPPassword1" placeholder="'.$lang['srv_email_setting_password_placeholder'].'"></label>';
		echo '</p>';
		echo '</div>';

		#SMTP
		$enkaSettings = $MA->getSMTPSettings();
		echo '<div id="send_mail_mode2" '.(!$MA->isSMTP() ? ' class="displayNone"' : '').'>';
		echo '<span class="italic">'.$lang['srv_email_setting_adapter2_note'].'</span><br />';
		echo '<br /><span class="bold">'.$lang['srv_email_setting_settings'].'</span><br />';
		# from - NICE
		echo '<p><label>'.$lang['srv_email_setting_from_nice'].'<input type="text" name="SMTPFromNice2" value="'.$enkaSettings['SMTPFromNice'].'"></label>';
		echo '</p>';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<input type="text" name="SMTPFrom2" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo2" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		#Username
		echo '<p><label>'.$lang['srv_email_setting_username'].'<input type="text" name="SMTPUsername2" value="'.$enkaSettings['SMTPUsername'].'" ></label>';
		echo '</p>';
		#Password
		echo '<p><label>'.$lang['srv_email_setting_password'].'<input type="password" name="SMTPPassword2" placeholder="'.$lang['srv_email_setting_password_placeholder'].'"></label>';
		echo '</p>';
		#autentikacija
		echo '<p>';
		echo $lang['srv_email_setting_autentication'];
		echo '<label><input type="radio" name="SMTPAuth2" value="0" '.((int)$enkaSettings['SMTPAuth'] != 1 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_no'].'</label>';
		echo '<label><input type="radio" name="SMTPAuth2" value="1" '.((int)$enkaSettings['SMTPAuth'] == 1 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_yes'].'</label>';
		echo '</p>';
		#Varnost SMTPSecure
		echo '<p>';
		echo $lang['srv_email_setting_encryption'];
		echo '<label><input type="radio" name="SMTPSecure2" value="0" '.((int)$enkaSettings['SMTPSecure'] == 0 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_none'].'</label>';
		echo '<label><input type="radio" name="SMTPSecure2" value="ssl" '.($enkaSettings['SMTPSecure'] == 'ssl' ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_ssl'].'</label>';
		echo '<label><input type="radio" name="SMTPSecure2" value="tls" '.($enkaSettings['SMTPSecure'] == 'tls' ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_tls'].'</label>';
		echo '</p>';
		#port
		echo '<p><label>'.$lang['srv_email_setting_port'].'<input type="number" min="0" max="65535" name="SMTPPort2" value="'.(int)$enkaSettings['SMTPPort'].'" style="width:80px;"></label>';
		echo ' '.$lang['srv_email_setting_port_note'];
		echo '</p>';
		#host
		echo '<p><label>'.$lang['srv_email_setting_host'].'<input type="text" name="SMTPHost2" value="'.$enkaSettings['SMTPHost'].'" ></label>';
		echo '</p>';
                #delay 
		echo '<p><label>'.$lang['srv_email_setting_smtp_delay'].' '.Help::display('srv_inv_delay').': <select name="SMTPDelay2">'
                        /*. '<option value="0" '.($enkaSettings['SMTPDelay']=="0"?'selected="selected"':'') .'>0 </option>'
                        . '<option value="10000" '.($enkaSettings['SMTPDelay']=="10000"?'selected="selected"':'') .'>0.01 sec (max 100 / sec)</option>'
                        . '<option value="20000" '.($enkaSettings['SMTPDelay']=="20000"?'selected="selected"':'') .'>0.02 sec (max 50 / sec)</option>'
                        . '<option value="50000" '.($enkaSettings['SMTPDelay']=="50000"?'selected="selected"':'') .'>0.05 sec (max 20 / sec)</option>'
                        . '<option value="100000" '.($enkaSettings['SMTPDelay']=="100000"?'selected="selected"':'') .'>0.1 sec (max 10 / sec)</option>'
                        . '<option value="200000" '.($enkaSettings['SMTPDelay']=="200000"?'selected="selected"':'') .'>0.2 sec (max 5 / sec)</option>'*/
                        . '<option value="500000" '.($enkaSettings['SMTPDelay']=="500000"?'selected="selected"':'') .'>0.5 sec (max 2 / sec)</option>'
                        . '<option value="1000000" '.($enkaSettings['SMTPDelay']=="1000000"?'selected="selected"':'') .'>1 sec (max 1 / sec)</option>'
                        . '<option value="2000000" '.($enkaSettings['SMTPDelay']=="2000000"?'selected="selected"':'') .'>2 sec (max 30 / min)</option>'
                        . '<option value="4000000" '.($enkaSettings['SMTPDelay']=="4000000"?'selected="selected"':'') .'>4 sec (max 15 / min)</option>'
                        . '<option value="5000000" '.($enkaSettings['SMTPDelay']=="5000000"?'selected="selected"':'') .'>5 sec (max 12 / min)</option>'
                        . '<option value="10000000" '.($enkaSettings['SMTPDelay']=="10000000"?'selected="selected"':'') .'>10 sec (max 6 / min)</option>'
                        . '<option value="20000000" '.($enkaSettings['SMTPDelay']=="20000000"?'selected="selected"':'') .'>20 sec (max 3 / min)</option>'
                        . '<option value="30000000" '.($enkaSettings['SMTPDelay']=="30000000"?'selected="selected"':'') .'>30 sec (max 2 / min)</option>'
                        . '</select></label>';
		echo '</p>';

                echo '</div>';
		
		echo '</form>';
		
		echo '</fieldset>';
		
		
		echo '<br class="clr" />';

		
		// Gumb shrani
		echo '<span class="floatLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;">';
		echo $lang['srv_email_setting_btn_save'] . '</a></div></span>';
		
		// Gumb preveri nastavitve
		echo '<span id="send_mail_mode_test"  class="floatLeft spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="showTestSurveySMTP(); return false;">';
		echo $lang['srv_email_setting_btn_test'].'</a></div></span>';
		
		
		if ($_GET['s'] == '1') {
			echo '<div id="success_save" style="float:left; display:inline; margin: -2px 0 0 0;"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
	}
	

	/**
	 * @desc poslje maile userjem iz baze
	 */
	function usermailing() {//OSTANE
		global $lang;
		global $site_url;
		global $site_path;
		global $global_user_id;
		
		// preverimo aktivnost
		//$sql = sisplet_query("SELECT active FROM srv_anketa WHERE id = '$this->anketa'");
		//$row = mysqli_fetch_array($sql);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		// ce ne postamo ali ce anketa ni aktivna
		if (!isset ($_POST['mailto']) || ($row['active'] != 1)) {
			echo '<div id="" style="float:left; width:50%;">';
			echo '<div id="anketa_aktivacija_note" class="div_error">';
			$this->anketa_aktivacija_note();
			echo'</div>';

			echo '<fieldset style="padding:10px; border:1px solid gray;"><legend>' . $lang['srv_mailing'] . ':</legend>';
			echo '<form name="frm_mailto_preview" id="frm_mailto_preview" action="index.php?anketa=' . $this->anketa . '&a=email&m=usermailing" method="post" autocomplete="off">';
			echo '<p><input name="mailto" value="all" type="radio" checked="">' . $lang['srv_mailing_all'] . '</p>';
			echo '<p><input name="mailto" value="norsp" type="radio">' . $lang['srv_mailing_nonrsp'] . '</p>';
			echo '<p><input name="mailto" value="rsp" type="radio">' . $lang['srv_mailing_rsp'] . '</p>';

			echo '<p><input name="mailto" id="radio_mailto_status" value="status" type="radio">'.$lang['srv_mailing_all_with_status'].':</p>';

			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="0" type="checkbox">0 - ' . $lang['srv_userstatus_0'] . '</div></p>';
			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="1" type="checkbox">1 - ' . $lang['srv_userstatus_1'] . '</div></p>';
			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="2" type="checkbox">2 - ' . $lang['srv_userstatus_2'] . '</div></p>';
			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="3" type="checkbox">3 - ' . $lang['srv_userstatus_3'] . '</div></p>';
			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="4" type="checkbox">4 - ' . $lang['srv_userstatus_4'] . '</div></p>';
			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="5" type="checkbox">5 - ' . $lang['srv_userstatus_5'] . '</div></p>';
			echo '<p><div style="padding-left:150px"><input name="mailto_status[]" value="6" type="checkbox">6 - ' . $lang['srv_userstatus_6'] . '</div></p>';

			echo '<script type="text/javascript">';
			echo '$(document).ready(function() {';
			echo '$(\'[name="mailto_status[]"]\').bind("click", function () {change_mailto_status();});';
			echo '$(\'[name="mailto"]\').bind("click", function(el) { change_mailto_radio(); });';
			echo '});';
			echo '</script>';

			echo '<div id="btn_mailto_preview_holder">';
			$this->displayBtnMailtoPreview($row);
			echo '</div>';
			//            echo '<input type="submit">';

			echo '</form>';
			echo '</fieldset>';
			echo '</div>';
			echo '<div id="mailto_right" style="float:left; width:50%;">';
			$sa = new SurveyAdmin(1, $this->anketa);
			$sa->show_mailto_users('all', null);
			echo '</div>';
			echo '<div class="clr"></div>';

		} else { // pošljemo emaile

			$errorMsg = null;
			//v odvisnosti od statusa polovimo emaile
			$mailto_radio = $_POST['mailto'];
			$mailto_status = (isset ($_POST['mailto_status']) && count($_POST['mailto_status']) > 0) ? implode(",", $_POST['mailto_status']) : null;
			$sa = new SurveyAdmin(1, $this->anketa);
			$arrayMailtoSqlString = $sa->getMailtoSqlString($mailto_radio, $mailto_status);
			$errorMsg = $arrayMailtoSqlString['errorMsg'];
			$sqlString = $arrayMailtoSqlString['sqlString'];

			// preberemo tekst za trenutno anketo
			$subject = "";
			$text = "";
			$sql_userbase_email = sisplet_query("SELECT * FROM srv_userbase_setting WHERE ank_id = '$this->anketa'");
			if (mysqli_num_rows($sql_userbase_email) > 0) {
				// anketa že ima nastavljen text
				$row_userbase_email = mysqli_fetch_array($sql_userbase_email);
			} else {
				// anketa še nima nastavljenega teksta, preberemo privzetega (id=1) iz tabele srv_userbase_invitations
				$sql_userbase_invitations = sisplet_query("SELECT * FROM srv_userbase_invitations WHERE id = 1");
				$row_userbase_email = mysqli_fetch_array($sql_userbase_invitations);
			}
			
			if ($row_userbase_email['replyto'] == '') {
				$sqluu = sisplet_query("SELECT email FROM users WHERE id = '$global_user_id'");
				$rowuu = mysqli_fetch_array($sqluu);
				$row_userbase_email['replyto'] = $rowuu['email'];
			}

			// poiščemo sistemske spremenljivke iz vsebine
			preg_match_all( "/#(.*?)#/s", $row_userbase_email['text'], $sisVars);
			// poiščemo sistemske spremenljivke iz vsebine
			$sisVars =$sisVars[1]; 

			// Poiščemo še sistemske spremenljivke iz ankete
			$sqlSistemske = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->anketa."' ORDER BY g.vrstni_red, s.vrstni_red");
			if (mysqli_num_rows($sqlSistemske) > 0) {
				while ($rowSistemske = mysqli_fetch_assoc($sqlSistemske)) {
					if (!isset($sisVars[strtoupper($rowSistemske['variable'])]))
						$sisVars[] = strtoupper($rowSistemske['variable']);
				}
			}

			$sql_userbase = sisplet_query("SELECT MAX(b.tip) AS tip FROM srv_userbase b, srv_user u WHERE b.usr_id=u.id AND u.ank_id='$this->anketa'");
			if (!$sql_userbase)
			echo mysqli_error($GLOBALS['connect_db']);
			$row_userbase = mysqli_fetch_array($sql_userbase);
			$tip = $row_userbase['tip'] + 1;

			if ($errorMsg == null) {

				$sql = sisplet_query($sqlString);
				// preprečimo izisovanje warningov

				$warnings_msg = "";

				$send_success = array ();
				$send_errors = array ();
				//				ob_start();
				//				$htmlContent = ob_get_contents();

				//				ob_start();
				
				$cnt = 0;
				
				while ($row = mysqli_fetch_array($sql)) {

					// dodamo sistemske spremenljivke in poiščemo njihove vrednosti
					$userSysData = array();
					foreach ( $sisVars as $sysVar ) {

						$sqlUser = sisplet_query("SELECT d.text FROM srv_data_text".$this->db_table." d, srv_spremenljivka s , srv_grupa g
							                                    WHERE d.spr_id=s.id AND d.usr_id='" . $row['id'] . "' AND
							                                    s.variable = '".strtolower($sysVar)."' AND g.ank_id='" . $this->anketa . "' AND s.sistem = 1 AND s.gru_id=g.id
							                                    ");
						if (!$sqlUser)
						echo mysqli_error($GLOBALS['connect_db']);
						$rowUser = mysqli_fetch_assoc($sqlUser);
						if ($rowUser['text'] != null)
						$userSysData[strtolower($sysVar)] = $rowUser['text'];
					}
					$email = $userSysData['email'];

					if (trim($email) != '' && $email != null) {

						// shranimo komu in kdaj je kdo poslal mail
						sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('$row[id]', '$tip', NOW(), '" . $this->uid() . "')");

						$url = SurveyInfo::getSurveyLink() . '?code=' . $row['pass'] . '';
						if (trim($row['pass']) != '') {
							$unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $this->anketa . '&code=' . $row['pass'] . '';
						} else {
							$unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $this->anketa . '&email=' . trim($email) . '&uid='.$row['id'];
						}
						

						// zamenjamo sistemske vrednosti
						$content = $row_userbase_email['text'];
						// za staro verzijo
						$content = str_replace('[URL]', '#URL#', $content);
						$content = str_replace('[CODE]', '#CODE#', $content);
						$content = str_replace(array (
							'#URL#',
							'#CODE#',
						), array (
							'<a href="' . $url . '">' . $url . '</a>',
						$row['pass'],
						), $content);
						$content = str_replace('#UNSUBSCRIBE#', '<a href="'.$unsubscribe.'">'.$lang['user_bye_hl'].'</a>', $content);

						// poiščemo prestale variable katere je potrebno zamenjati v vsebini
						preg_match_all( "/#(.*?)#/s", $content, $toReplace);
						foreach ($toReplace[0] as $key => $seed) {
							$content = str_replace($toReplace[0][$key], $userSysData[strtolower($toReplace[1][$key])],$content);
						}

						$subject = $row_userbase_email['subject'];

						try
						{
							$MA = new MailAdapter($this->anketa, $type='alert');
							$MA->addRecipients($email);
							if ($cnt++ == 0)
							{ # en mail pošljemo tudi na enklikanketa
								$MA->addRecipients('enklikanketa@gmail.com');
							}
							$resultX = $MA->sendMail(stripslashes($content), $subject);
							
						}
						catch (Exception $e)
						{
						}

						if ($resultX) {
							$status = 1; // poslalo ok
							$send_success[] = $email;
						} else {
							$status = 2; // ni poslalo
							$send_errors[] = $email;
						}

						// nastavimo status
						sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('$row[id]', '$tip', '$status', NOW())");
						# laststatus updejtamo samo če je bil pred tem status 0 - email še ni poslan ali 2 -  napaka pri pošiljanju maila
						sisplet_query("UPDATE srv_user SET last_status = '$status' WHERE id = '$row[id]' AND last_status IN (0,2)");
						
					}
					// počistimo warninge
					//					ob_end_clean();

					//					echo $htmlContent;

				}
				echo '<b>Spodnje sporočilo:</b><br/><br/>' . $row_userbase_email['subject'] . ',<br/> ' . $row_userbase_email['text'] . '<br/>';
				if (count($send_success) > 0) {
					echo '<b>je bilo uspešno poslano na naslednje naslove:<br/></b>';
					foreach ($send_success as $email) {
						echo $email . ",<br/>";
					}
				}
				if (count($send_errors) > 0) {
					echo '<br/><b>ni bilo uspešno poslano. Pri pošiljanju na naslednje naslove je prišlo do napake:<br/></b>';
					foreach ($send_errors as $email) {
						echo $email . ",<br/>";
					}
				}
				//echo '<br/>Done';
				// izpipemo warninge na koncu

			} else {
				echo '<div id="div_error" class="red"><img src="icons/icons/error.png" alt="" vartical-align="middle" />' . $errorMsg . '</div>';
			}
		}
	}

	/**
	 * @desc prikaze tab Socialna omrezja
	 */
	function SN_Settings() {
		global $lang;
		global $site_url;
		global $site_path;

		echo '<fieldset>';
		echo '<legend >' . $lang['srv_splosna_navodila'] . '</legend>';

		echo '<p>'.$lang['srv_social_settings_text1'].'</p>';
		echo '<p>'.$lang['srv_social_settings_text2'].'</p>';
		echo '<p>'.$lang['srv_social_settings_text3'].'</p>';
		echo '<p>'.$lang['srv_social_settings_text4'].'</p>';
		echo '<p>'.$lang['srv_social_settings_text5'].' <a href="http://www.1ka.si/a/3510">http://www.1ka.si/a/3510</a>, </p>';
		echo '<p>'.$lang['srv_social_settings_text6'].' <a href="http://www.1ka.si/admin/survey/index.php?a=knjiznica">'.$lang['srv_library'].'</a> '.$lang['srv_social_settings_text7'].'</p>';
		echo '<p><a href="index.php?anketa='.$this->anketa.'">'.$lang['edit2'].'</a>'.$lang['srv_social_settings_text8'].'</p>';
		
		echo '</fieldset>';
		// Omrežja so sestavljane vprašanja. Začnejo se iz generatorja imen (name generator). S tem respondent (ego) navede objekte, prijatelje - alterje. , s katerim pridobimo imena pzanke in vprašanj. Druga kompnenta je zanka, ki za vse alterje določenega ega sproži enaka vprašanja. Dretja komponenta so vprašanja. Primer omrežja je tukaj, http://www.1ka.si/a/3510, vprašalnik pa najdemo v knjižnjic med  Primerov 1KA anket http://www.1ka.si/admin/survey/index.php?a=knjiznica
	}

	/**
	 * @desc prikaze tab arhivi
	 */
	function arhivi() {//OSTANE
		global $lang;
		
		echo '<fieldset style="width:100%">';
		echo '<legend >' . $lang['srv_questionnaire_archives'] . '</legend>';
		
		echo '<p style="margin-bottom:5px;"><span class="bold spaceRight" style="margin-top:5px;">' . $lang['srv_backup_label'] . '</span></p>';
		echo '<p style="margin-top:5px;margin-bottom:10px;">'.$lang['srv_note'].': <input class="" name="intro_opomba" id="intro_opomba" type="text" style="width:400px"></p>';
		
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="index.php?anketa=' . $this->anketa . '&a=backup_create" onclick="create_archive_survey(\'' . $this->anketa .'\',\'' . $lang['srv_wait_a_moment'] .'\'); return false;">';
		echo $lang['srv_backup_button'];
		echo '</a></div></span>';
		
		echo '<br /><br />';
		
		// Seznam ustvarjenih arhivov
		$sql = sisplet_query("SELECT a.id, a.naslov, a.intro_opomba, a.insert_time, a.edit_time, CONCAT(i.name, ' ', i.surname) AS insert_name, CONCAT(e.name, ' ', e.surname) AS edit_name FROM srv_anketa a, users i, users e WHERE a.insert_uid=i.id AND a.edit_uid=e.id AND a.backup = '$this->anketa' AND a.active>='0' ORDER BY a.insert_time DESC");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		if (mysqli_num_rows($sql) > 0)
			echo '<br /><strong>' . $lang['srv_backup_list'] . ':</strong>';
		
		while ($row = mysqli_fetch_array($sql)) {
			echo '<div style="margin: 5px 0 10px 10px;">';
			echo '<span class="bold"><a href="index.php?anketa=' . $row['id'] . '">' . $row['naslov'] . '</a></span> '.($row['intro_opomba']!='' ? ' - <i>'.$row['intro_opomba'].'</i>' : '');
			echo '<br />(' . $lang['sent_by'] . ': <b>' . $row['insert_name'].'</b> ' . datetime($row['insert_time']) . ', ' . $lang['edit_by'] . ': <b>' . $row['edit_name'].'</b> ' . datetime($row['edit_time']) . ')';
			echo '<br /><a href="#" onclick="javascript:anketa_delete(\'' . $row['id'] . '\', \'' . $lang['srv_anketadeleteconfirm'] . '\'); return false;">' . $lang['srv_survey_archives_delete_survey'] . '</a>, <a href="index.php?anketa=' . $row['id'] . '&a=backup_restore">' . $lang['srv_anketarestore'] . '</a>';
			echo '</div>';
		}
		
		echo '</fieldset>';
	}

	function arhivi_data() {
		global $lang;
		
		echo '<fieldset style="width:100%">';
		echo '<legend>' . $lang['srv_arhiv_data'] . '</legend>';
		
		echo '<p style="margin-bottom:5px;"><span class="bold spaceRight" style="margin-top:5px;">' . $lang['srv_backup_data_label'] . '</span></p>';
		
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="ajax.php?anketa=' . $this->anketa . '&a=backup_data" onclick="create_archive_survey_data(\'' . $this->anketa .'\',\'' . $lang['srv_wait_a_moment'] .'\'); return false;">';
		echo $lang['srv_backup_data_button'];
		echo '</a></div></span>';

		echo '<br /><br />';
		
		$backups = array();
		if ($handle = opendir( dirname(__FILE__) . '/SurveyBackup/' )) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != "..") {
		            $file = explode('-', $entry);
		            if ($file[0] == $this->anketa) {
						$backups[] = $entry;
		            }
		        }
		    }
		    closedir($handle);
		}	
		if (count($backups) > 0) {
			echo '<br /><span class="bold">' . $lang['srv_backup_data_list'] . ':</span>';
			foreach ($backups AS $file) {
				$e = explode('-', $file);
				$e[2] = str_replace('.1ka', '', $e[2]);
				echo '<br /><span style="padding-left:10px;">'.$e[1].' '.$e[2].' - <a href="ajax.php?anketa='.$this->anketa.'&a=backup_restore&filename='.$file.'">'.$lang['srv_anketarestoredata'].'</a></span>';
			}
			echo '<br /><br />';
		}
		
		echo '</fieldset>';
	}
	
	function arhivi_testdata() {
		global $lang;
		
		$str_testdata = "SELECT count(*) FROM srv_user WHERE ank_id='".$this->anketa."' AND (testdata='1' OR testdata='2')";
		$query_testdata = sisplet_query($str_testdata);
		list($testdata) = mysqli_fetch_row($query_testdata);
		
		$str_testdata_auto = "SELECT count(*), add_date, add_uid FROM srv_testdata_archive WHERE ank_id='".$this->anketa."' GROUP BY add_date";
		$query_testdata_auto = sisplet_query($str_testdata_auto);
		$auto_testdata = array();
		while (list($_cnt, $_date, $_uid) = mysqli_fetch_row($query_testdata_auto) ) {
			$testdata_auto+=$_cnt;
			$auto_testdata[] = $cnt;
		}
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_arhiv_testdata'].'</legend>';
		
		echo $lang['srv_archive_test_data_count'].(int)$testdata;
		if ($testdata_auto > 0) {
			echo $lang['srv_archive_test_data_auto'].(int)$testdata_auto;
		}
		
		echo '</fieldset>';
	}
	
	function arhivi_survey() {
		global $lang;
		
		// Uvoz/izvoz samo ankete - po novem je uvoz pri kreiranju ankete
		if($_GET['m'] == 'survey'){
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_survey_archives_ie_title'].'</legend>';
			
			echo '<p class="italic">'.$lang['srv_survey_archives_note_survey'].'</p>';
			
			// Izvoz
			echo '<p>';
			echo '<span class="bold">'.$lang['srv_survey_archives_export'].'</span>';	
			echo '<br />'.$lang['srv_survey_archives_export_text'];
	        echo '<span class="buttonwrapper floatLeft" style="margin-top:3px;" title="'.$lang['srv_survey_archives_export_save'].'"><a class="ovalbutton ovalbutton_orange" href="ajax.php?a=archive_download&anketa='.$this->anketa.'">'.$lang['srv_survey_archives_export_save'].'</a></span>';	
			echo '</p>';
			
			echo '<br /><br />';
			
			// Uvoz
			/*echo '<p style="margin-bottom:20px;">';
			echo '<span class="bold">'.$lang['srv_survey_archives_import'].'</span>';
			echo '<br />'.$lang['srv_survey_archives_import_text'];
	        echo '<br /><span class="buttonwrapper floatLeft" style="margin-top:3px;" title="'.$lang['srv_survey_archives_import_import'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#restore\').toggle(); return false;">'.$lang['srv_survey_archives_import_import'].'</a></span>';				
			echo '<br />';
			echo '<form style="display:none" id="restore" action="ajax.php?a=archive_restore" method="post" name="restorefrm" enctype="multipart/form-data" >
				  <span style="line-height:18px;">'.$lang['srv_arhiv_datoteka_save_txt2'].': 
				  <input type="hidden" name="has_data" value="0" />
				  <input type="file" name="restore" onchange="document.restorefrm.submit();" /></span>
				  <br /><span class="italic">'.$lang['srv_arhiv_datoteka_restore_help'].'</span>
				  </form>';
			echo '</p>';*/
				  
			echo '</fieldset>';
		}
		// Uvoz/izvoz ankete s podatki - po novem je uvoz pri kreiranju ankete
		else{
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_survey_archives_ie_data_title'].'</legend>';
			
			echo '<p class="italic">'.$lang['srv_survey_archives_note_survey_data'].'</p>';
			
			// Izvoz
			echo '<p>';
			echo '<span class="bold">'.$lang['srv_survey_archives_export'].'</span>';	
			echo '<br />'.$lang['srv_survey_archives_export_text'];
	        echo '<span class="buttonwrapper floatLeft" style="margin-top:3px;" title="'.$lang['srv_survey_archives_export_save'].'"><a class="ovalbutton ovalbutton_orange" href="ajax.php?a=archive_download&anketa='.$this->anketa.'&data=true">'.$lang['srv_survey_archives_export_save'].'</a></span>';	
			echo '</p>';
			
			echo '<br /><br />';
			
			// Uvoz
			/*echo '<p style="margin-bottom:20px;">';
			echo '<span class="bold">'.$lang['srv_survey_archives_import'].'</span>';
			echo '<br />'.$lang['srv_survey_archives_import_text'];
	        echo '<br /><span class="buttonwrapper floatLeft" style="margin-top:3px;" title="'.$lang['srv_survey_archives_import_import'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#restore_data\').toggle(); return false;">'.$lang['srv_survey_archives_import_import'].'</a></span>';				
			echo '<br />';
			echo '<form style="display:none" id="restore_data" action="ajax.php?a=archive_restore" method="post" name="restoredatafrm" enctype="multipart/form-data" >
				  <span style="line-height:18px;">'.$lang['srv_arhiv_datoteka_save_txt2'].': 
				  <input type="hidden" name="has_data" value="1" />
				  <input type="file" name="restore_data" onchange="document.restoredatafrm.submit();" /></span>
				  <br /><span class="italic">'.$lang['srv_arhiv_datoteka_restore_help'].'</span>
				  </form>';
			echo '</p>';*/
				  
			echo '</fieldset>';	
		}
	}
	
	// Preveri ce gre za prvo popravljanje podatkov in ce da, potem ustvari arhiv podatkov
	function checkFirstDataChange($inserted=false){
		global $connect_db;

		$sql = sisplet_query('SELECT count(*) AS cnt FROM srv_tracking'.$this->db_table.' WHERE ank_id=\''.$this->anketa.'\' 
								AND (`get` LIKE \'%edit_data%\'
									OR (`get` LIKE \'%a: "data", m: "quick_edit"%\' AND `get` LIKE \'%post: "1"%\')
									OR (`get` LIKE \'%a: "dataCopyRow"%\')
									OR (`get` LIKE \'%a: "dataDeleteMultipleRow"%\')
									OR (`get` LIKE \'%a: "dataDeleteRow"%\')
									OR (`get` LIKE \'%urejanje: "1"%\' AND status=\'4\'))
							ORDER BY datetime DESC');
		$row = mysqli_fetch_array($sql);

		// Naredimo arhiv podatkov
		if($row['cnt'] == 0 || ($inserted && $row['cnt'] == 1)){
			SurveyCopy::setSrcSurvey($this->anketa);
			SurveyCopy::setSrcConectDb($connect_db);
			SurveyCopy::saveArrayFile($data=true);
		}
	}

	/**
	 * @desc skopira anketo 
	 */
	function anketa_copy($anketa = 0) {//OSTANE
		
		// stara kopija kode je v classu class.SurveyCopy.php na dnu :)
		
		global $connect_db;

		if ($anketa > 0)
			$this->anketa = $anketa;
			
		$site = $_GET['site'];

		SurveyCopy :: setSrcSurvey($this->anketa);
		SurveyCopy :: setSrcConectDb($connect_db);
		SurveyCopy :: setDestSite($site);

		$new_anketa_id = SurveyCopy :: doCopy();

		
		$napake = SurveyCopy :: getErrors();
		if (count($napake) > 0)
			print_r($napake);

		if (!$new_anketa_id)
			die("Can not create new survey!");

		if (!$site || $site == 0)
			return $new_anketa_id;
		elseif ($site != -1)
			header("Refresh:1; url=index.php?anketa=$this->anketa&a=arhivi");
	}

	/**
	 * @desc kreira backup (skopira celotno anketo v novo)
	 */
	function backup_create($NoRedirect = false) {//OSTANE

		$anketa = $this->anketa_copy();

		sisplet_query("UPDATE srv_anketa SET backup='$this->anketa', active='0', naslov = CONCAT( naslov, ' ', DAY(NOW()), '.', MONTH(NOW()), '.', YEAR(NOW()) ), intro_opomba='{$_POST['intro_opomba']}' WHERE id='$anketa'");
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

                if ($NoRedirect == false) {
                    header("Refresh:1; url=index.php?anketa=$this->anketa&a=arhivi");
                    //header("Location: index.php?anketa=$this->anketa&a=arhivi");
                }
	}

	/**
	 * @desc kreira backup in da obvestilo o uspešnosti (skopira celotno anketo v novo)
	 */
	function backup_create_popup() {//OSTANE
		global $lang;
		$anketa = $this->anketa_copy();

		sisplet_query("UPDATE srv_anketa SET backup='$this->anketa', active='0', naslov = CONCAT( naslov, ' ', DAY(NOW()), '.', MONTH(NOW()), '.', YEAR(NOW()) ) WHERE id='$anketa'");
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

		if ($anketa > 0 || true) {
			echo $lang['srv_backup_create_popup_ok'];
		}
        echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_backup_create_popup_view'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="archivePopupView(); return false;"><span>'.$lang['srv_backup_create_popup_view'].'</span></a></span>';
        echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_backup_create_popup_close'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="archivePopupClose(); return false;"><span>'.$lang['srv_backup_create_popup_close'].'</span></a></span>';
		
	}

	/**
	 * @desc prenese arhivsko anketo v folderje
	 */
	function backup_restore() {//OSTANE

		$row = SurveyInfo::getInstance()->getSurveyRow();

		$active = 0;
		$backup = 0;

		$sql = sisplet_query("UPDATE srv_anketa SET active='$active', backup='$backup' WHERE id = '$this->anketa'");
		
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

		header("Location: index.php?anketa=$this->anketa");
	}
	
	/**
	* prikaze tab z opcijami za vnos
	*/
	function vnos () {
		global $lang;
				
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
		
		echo '<p>'.$lang['srv_vnos_navodila'].'</p>';
		
		/*echo '</fieldset>';
		
		echo '<br />';
		
		echo '<fieldset>';
		echo '<legend>' . $lang['srv_cookie'] . '</legend>';*/
		
		//prepoznaj uporabnika iz sispleta
		echo '<form name="settingsanketa_' . $row['id'] . '" action="ajax.php?a=editanketasettings" method="post" autocomplete="off">' . "\n\r";
		echo '	<input type="hidden" name="anketa" value="' . $this->anketa . '" />' . "\n\r";
		echo '	<input type="hidden" name="grupa" value="' . $this->grupa . '" />' . "\n\r";
		echo '  <input type="hidden" name="location" value="vnos" />' . "\n\r";
		echo '  <input type="hidden" name="submited" value="1" />' . "\n\r";
				
		echo '<span class="nastavitveSpan3 bold" ><label>' . $lang['srv_mass_input'] . ':</label></span>';
		echo '            <input type="radio" name="mass_insert" value="1" id="mass_insert_1"' . ($row['mass_insert'] == 1 ? ' checked="checked"' : '') . ' /><label for="mass_insert_1">' . $lang['srv_mass_input_1'] . '</label>' . "\n\r";
		echo '            <input type="radio" name="mass_insert" value="0" id="mass_insert_0"' . ($row['mass_insert'] == 0 ? ' checked="checked"' : '') . ' /><label for="mass_insert_0">' . $lang['srv_mass_input_0'] . '</label>' . "\n\r";
		
		echo '<br />';
		echo '<br />';
		
		echo '</form>';		
		echo '</fieldset>';
		
		echo '<br />';
		
		echo '<span class="floatLeft spaceRight">';
		echo '<div class="buttonwrapper">';
		echo '<a class="ovalbutton ovalbutton_orange btn_savesettings" onclick="document.settingsanketa_'.$row['id'].'.submit(); return false;" href="#">';
		echo '<span>'.$lang['edit1337'].'</span>';
		echo '</a>';
		echo '</div>';
		echo '</span>';
		
		echo '<br class="clr" />';
		
		if ($_GET['s'] == '1') {
			echo '<div id="success_save"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
	}
	
	/**
	 * @desc prikaze tab za uporabnost - nastavitve
	 */
	function uporabnost() {
		global $site_url;
		global $lang;

		SurveySetting::getInstance()->Init($this->anketa);

		if (count($_POST) > 0 && (isset($_POST['uporabnost_link']) || isset($_POST['uporabnost_razdeli']))) {
			$uporabnost_link = $_POST['uporabnost_link'];
			$uporabnost = $_POST['uporabnost'];
			sisplet_query("UPDATE srv_anketa SET uporabnost_link = '$uporabnost_link' WHERE id = '$this->anketa'");

			$sqlg = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id = '$this->anketa'");
			while ($rowg = mysqli_fetch_array($sqlg)) {
				if ( isset($_POST['uporabnost_link_'.$rowg['id']])) {

					SurveySetting::getInstance()->setSurveyMiscSetting('uporabnost_link_'.$rowg['id'], $_POST['uporabnost_link_'.$rowg['id']]);
				}
			}

			if (isset($_POST['uporabnost_razdeli'])) {
				SurveySetting::getInstance()->setSurveyMiscSetting('uporabnost_razdeli', $_POST['uporabnost_razdeli']);
			}

			// vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();
		}

		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
		echo '<form action="index.php?anketa=' . $this->anketa . '&a=uporabnost" name="settingsanketa_'.$this->anketa.'" method="post">';

		if ($row['uporabnost_link'] == '')
			$row['uporabnost_link'] = 'http://';

        echo '<p class="bold">'.$lang['srv_uporabnost_link'].'</p>';
        echo '<p class="red">'.$lang['srv_uporabnost_warning'].'</p>';
		echo '<p>Link: <input type="text" name="uporabnost_link" value="' . $row['uporabnost_link'] . '" style="width:300px"></p>';

		$uporabnost_razdeli = SurveySetting::getInstance()->getSurveyMiscSetting('uporabnost_razdeli');
		echo '<p>'.$lang['srv_uporabnost_razdeli'].': <input type="radio" name="uporabnost_razdeli" value="0" '.($uporabnost_razdeli!=1?' checked':'').'>'.$lang['srv_vodoravno'].' <input type="radio" name="uporabnost_razdeli" value="1" '.($uporabnost_razdeli==1?' checked':'').'>'.$lang['srv_navpicno'].' ('.$lang['srv_razdeli_dodatno'].')</p>';

		echo '</fieldset>';

		echo '<br />';

		echo '<fieldset><legend>'.$lang['srv_uporabnost_nadaljne'].'</legend>';

		echo '<p class="bold">'.$lang['srv_uporabnost_link_stran'].'</p>';

		$sqlg = sisplet_query("SELECT id, naslov FROM srv_grupa WHERE ank_id = '$this->anketa' ORDER BY vrstni_red ASC");
		while ($rowg = mysqli_fetch_array($sqlg)) {
			$link = SurveySetting::getInstance()->getSurveyMiscSetting('uporabnost_link_'.$rowg['id']);
			if ($link == '')
				$link = 'http://';
			echo '<p>'.$rowg['naslov'].': <input type="text" name="uporabnost_link_'.$rowg['id'].'" value="'.$link.'" style="width:300px"></p>';
		}

		echo '</fieldset>';

		echo '<br class="clr">';

		//echo '<p><input type="submit" value="' . $lang['edit'] . '"></p>';
		echo '<span class="floatLeft spaceRight">';
		echo '<div class="buttonwrapper">';
		echo '<a class="ovalbutton ovalbutton_orange btn_savesettings" onclick="document.settingsanketa_'.$this->anketa.'.submit(); return false;" href="#">';
		echo '<span>'.$lang['edit1337'].'</span>';
		echo '</a>';
		echo '</div>';
		echo '</span>';

		echo '<br class="clr">';
		echo '<br />';

		echo '</form>';

		/*echo '<fieldset><legend>'.$lang['srv_upora_dodatno'].'</legend>';
		echo '<p style="width:50%">' . $lang['srv_upora_text'] . '</p>';
		echo '<p><a href="#" onclick="javascript:$(\'#demo\').show(\'slow\'); return false;">'.$lang['srv_primer'].'</a></p>';
		echo '<p id="demo" style="display:none"><img src="img_0/uporabnost.png" /></p>';
		echo '</fieldset>';*/
	}
    
	/**
	 * @desc prikaze vnose v anketo
	 */
	function displayIzvozi() {
		global $lang, $site_url, $global_user_id;
		
		$sdf = SurveyDataFile::get_instance();
		$sdf->init($this->anketa);
		$headFileName   = $sdf->getHeaderFileName();
		$dataFileName 	= $sdf->getDataFileName();
		$dataFileStatus = $sdf->getStatus();
		
		if ($dataFileStatus== FILE_STATUS_NO_DATA 
			|| $dataFileStatus == FILE_STATUS_NO_FILE
			|| $dataFileStatus == FILE_STATUS_SRV_DELETED){
				Common::noDataAlert();
				return false;
    	}
    	if ($_GET['m'] == 'excel') {
   			echo '<form id="export_excel_form" target="_blank" action="ajax.php?t=export&anketa='.$this->anketa.'&a=doexport&m=excel" method="POST">';
    	} else if($_GET['m'] == 'excel_xls') {
   			echo '<form id="export_excel_xls_form" target="_blank" action="ajax.php?t=export&anketa='.$this->anketa.'&a=doexport&m=excel_xls" method="POST">';
    	} else if($_GET['m'] == 'txt') {
   			echo '<form id="export_txt_form" target="_blank" action="ajax.php?t=export&anketa='.$this->anketa.'&a=doexport&m=txt" method="POST">';
    	} else if($_GET['m'] == 'spss') {
   			echo '<form id="export_spss_form" target="_blank" action="ajax.php?t=export&anketa='.$this->anketa.'&a=doexport&m=spss" method="POST">';
    	} else if($_GET['m'] == 'sav') {
   			echo '<form id="export_sav_form" target="_blank" action="ajax.php?t=export&anketa='.$this->anketa.'&a=doexport&m=sav" method="POST">';
		}
    				   
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		echo '<div id="div_analiza_filtri_right" class="floatRight export">';
		echo '<ul>';

		if($_GET['m'] != 'export_PDF'){
			echo '<li>';
			echo '<span class="as_link gray" id="link_export_setting" onClick="$(\'#fade\').fadeTo(\'slow\', 1); $(\'#div_export_setting_show\').fadeIn(\'slow\'); return false;" title="' . $lang['srv_dsp_link'] . '">' . $lang['srv_dsp_link'] . '</span>';
			echo '</li>';
		}
		
        // profili statusov
        SurveyStatusProfiles :: Init($this->anketa);
		SurveyStatusProfiles :: DisplayLink(false);
		
        # div za profile variabel
        SurveyVariablesProfiles :: Init($this->anketa, $global_user_id);
		SurveyVariablesProfiles :: DisplayLink(false,false);
		SurveyConditionProfiles :: Init($this->anketa, $global_user_id);
		SurveyConditionProfiles::DisplayLink(false);

        # div za profile časov
		SurveyTimeProfiles :: Init($this->anketa, $global_user_id);
		SurveyTimeProfiles::DisplayLink(false,true);
		
		echo '</ul>';
		echo '</div>';
		

		if($_GET['m'] != 'export_PDF'){

			if(session_id() == '')
				session_start();
			
			echo '<div id="div_export_setting_show">';

			// Izvozi identifikatorje
			echo '<label><input type="radio" name="exportSetting" id="hiddenSystem" value="2"'.
				((isset($_SESSION['exportHiddenSystem']) && $_SESSION['exportHiddenSystem'] == true) ? ' checked="checked"' : '') .
				' onchange="exportChangeCheckbox(\'exportHiddenSystem\');"/>'.$lang['srv_export_hidden_system']
				.'</label>';
			echo Help::display('exportSettings');
			
			echo '<br />';
			
			// Izvozi podatke
			echo '<label><input type="radio" name="exportSetting" id="onlyData" value="0"'.
				((isset($_SESSION['exportOnlyData']) && $_SESSION['exportOnlyData'] == true) ? ' checked="checked"' : '') .
				' onchange="exportChangeCheckbox(\'exportOnlyData\');"/>'.$lang['srv_export_only_data']
				.'</label>';
			
			echo '<br />';

			// Izvozi podatke in parapodatke
			echo '<label><input type="radio" name="exportSetting" id="fullMeta" value="1"'.
				((isset($_SESSION['exportFullMeta']) && $_SESSION['exportFullMeta'] == true) ? ' checked="checked"' : '') .
				' onchange="exportChangeCheckbox(\'exportFullMeta\');"/>'.$lang['srv_export_full_meta']
				.'</label>';
			echo Help::display('srv_export_full_meta');
			
			echo '<br class="clr"/>';
			
			echo '<br />';
			
			echo '<div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#div_export_setting_show\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');return false;"><span>'.$lang['srv_zapri'] . '</span></a></div>';
			echo '</div>';
		}
		
		# v odvisnosti od $_GET['m'] prikazemo podstran
		if (!$_GET['m'] || $_GET['m'] == M_EXPORT_EXCEL) {
		
			echo '<fieldset><legend>'.$lang['srv_lnk_excel'].'</legend>';
		
			echo $lang['srv_izvoz_Excel_note'];	
			echo $lang['srv_izvoz_Excel_note_2'];
			
			echo '<p class="strong">'.$lang['srv_izvoz_Excel_settings'].'</p>';
			echo '<p>';
			echo '<label>'.$lang['srv_expor_excel_cell_delimiter'].'</label>';			
			echo '<label><input type="radio" name="export_delimit" id="export_delimit_semicolon" value="0" onchange="excelExportChangeDelimit(); return false;" checked="checked"/>'.$lang['srv_expor_excel_cell_delimiter1'].'</label>';
			echo '<label><input type="radio" name="export_delimit" id="export_delimit_coma" value="1" onchange="excelExportChangeDelimit(); return false;"/>'.$lang['srv_expor_excel_cell_delimiter2'].'</label>';
			echo '</p>';
			
			echo '<p>';
			echo '<div id="replace_export_delimit_semicolon">';
			echo '<span>';
			echo $lang['srv_export_replace1'].' <input type="text" value=";" name="replace_what0[]" id="replace_what_0" size="1">';			
			echo $lang['srv_export_replace2'].' <input type="text" value="," name="replace_with0[]" id="replace_with_0" size="1">';
			echo '</span>';			
			echo '</div>';
			echo '<div id="replace_export_delimit_comma" class="displayNone">';
			echo '<span>';
			echo $lang['srv_export_replace1'].' <input type="text" value="," name="replace_what1[]" id="replace_what_0" size="1">';			
			echo $lang['srv_export_replace2'].' <input type="text" value=";" name="replace_with1[]" id="replace_with_0" size="1">';
			echo '</span>';			
			echo '</div>';
			echo '</p>';

			echo '<p>';	
			echo '<label><input type="checkbox" name="export_labels" id="export_labels" checked="checked" value="1"/>'.$lang['srv_export_texts'].'</label>';
			echo '<p>';
			
			echo '</fieldset>';
			
		} elseif ($_GET['m'] == M_EXPORT_EXCEL_XLS) {
		
			echo '<fieldset><legend>'.$lang['srv_lnk_excel_xls'].'</legend>';
			echo $lang['srv_izvoz_Excel_xls_note'];	
			echo $lang['srv_izvoz_Excel_xls_note_2'];				
			echo '</fieldset>';
		} 
		elseif ($_GET['m'] == M_EXPORT_SPSS) {		
		
			echo '<fieldset><legend>'.$lang['srv_lnk_spss'].'</legend>';			
			echo $lang['srv_izvoz_SPSS_faq'];
			echo $lang['srv_izvoz_SPSS_note'];		
			echo '</fieldset>';
		} 
		elseif ($_GET['m'] == M_EXPORT_SAV) {	
		
			echo '<fieldset><legend>'.$lang['srv_lnk_sav'].'</legend>';	
			echo $lang['srv_izvoz_SAV_note'];			
			echo '</fieldset>';
		} 
		elseif ($_GET['m'] == M_EXPORT_TXT) {
		
			echo '<fieldset><legend>'.$lang['srv_lnk_txt'].'</legend>';
			echo $lang['srv_izvoz_txt_note'];	
			echo '</fieldset>';	
		} 
		elseif ($_GET['m'] == 'export_PDF') {
			
			$pageBreak = isset($_GET['pageBreak']) ? $_GET['pageBreak'] : 0;
			$type = isset($_GET['type']) ? $_GET['type'] : 0;
			$if = isset($_GET['if']) ? $_GET['if'] : 0;
			$font = isset($_GET['font']) ? $_GET['font'] : 12;
			
			echo '<span class="red bold">'.$lang['srv_export_questionnare_0'].'</span>';
			
			echo '<p>';
			echo $lang['srv_export_questionnare_1'].'<br /><br />';
			echo $lang['srv_export_questionnare_2'];
			echo '</p>';
			
			# PDF in RTF izvoz rezultatov
			echo '<fieldset>';
			echo '<legend >' . $lang['srv_lnk_PDF/RTF'] . '</legend>';
			echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=pdf_results&anketa=' . $this->anketa . '&pageBreak='.$pageBreak.'&type='.$type.'&if='.$if.'&font='.$font).'" target="_blank">' .
			'<span class="faicon pdf"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
			echo '<br/>';

			echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=rtf_results&anketa=' . $this->anketa . '&pageBreak='.$pageBreak.'&type='.$type.'&if='.$if.'&font='.$font).'" target="_blank">';
			echo '<span class="faicon rtf"></span>&nbsp;DOC - (Microsoft Word)</a>';
			echo '</fieldset>';
			
			//vsak resp na svoji strani
			echo '<fieldset>';
			echo '<legend >' . $lang['settings'] . '</legend>';
			echo '<span class="nastavitveSpan1" ><label>' . $lang['srv_export_pagebreak'] . ':</label></span>';
			echo '<input type="radio" name="export_pagebreak" value="1" id="export_pagebreak_1" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak=1&type='.$type.'&if='.$if.'&font='.$font.'\');" '.($pageBreak == 1 ? ' checked' : '').' /><label>' . $lang['yes'] . '</label>';
			echo '<input type="radio" name="export_pagebreak" value="0" id="export_pagebreak_0" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak=0&type='.$type.'&if='.$if.'&font='.$font.'\');" '.($pageBreak == 0 ? ' checked' : '').' /><label>' . $lang['no1'] . '</label>';
			
			//dolg/kratek izpis vprasanj v pdf
			echo '<br />';
			echo '<span class="nastavitveSpan1" ><label>' . $lang['srv_displaydata_type'] . ':</label></span>';
			echo '<input type="radio" name="type" value="0" id="type_0" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type=0&if='.$if.'&font='.$font.'\');" '.($type == 0 ? ' checked' : '').' /><label>' . $lang['srv_displaydata_type0'] . '</label>';
			echo '<input type="radio" name="type" value="1" id="type_1" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type=1&if='.$if.'&font='.$font.'\');" '.($type == 1 ? ' checked' : '').' /><label>' . $lang['srv_displaydata_type1'] . '</label>';
			echo '<input type="radio" name="type" value="2" id="type_2" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type=2&if='.$if.'&font='.$font.'\');" '.($type == 2 ? ' checked' : '').' /><label>' . $lang['srv_displaydata_type2'] . '</label>';
			
			//prikaz if-ov
			echo '<br />';
			echo '<span class="nastavitveSpan1" ><label>' . $lang['srv_export_if'] . ':</label></span>';
			echo '<input type="radio" name="if" value="1" id="if_1" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type='.$type.'&if=1&font='.$font.'\');" '.($if == 1 ? ' checked' : '').' /><label>' . $lang['yes'] . '</label>';
			echo '<input type="radio" name="if" value="0" id="if_0" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type='.$type.'&if=0&font='.$font.'\');" '.($if == 0 ? ' checked' : '').' /><label>' . $lang['no1'] . '</label>';

			//velikost fonta
			echo '<br />';
			echo '<span class="nastavitveSpan1" ><label>' . $lang['srv_export_font'] . ':</label></span>';
			echo '<select name="font" onchange="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type='.$type.'&if='.$if.'&font=\'+this.value);">';
			for($i=8; $i<16; $i+=2){
				echo '<option value="'.$i.'" '.($i==$font ? ' selected' : '').'>'.$i.'</option>';
			}
			echo '</select>';
			//echo '<input type="radio" name="font" value="1" id="font_1" onclick="vnos_redirect(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=export&m=export_PDF&pageBreak='.$pageBreak.'&type='.$type.'&if='.$if.'&font='.$font.'\');" '.($type == 1 ? ' checked' : '').' /><label>' . $lang['yes'] . '</label>';
			
			echo '</fieldset>';
		}
			
		echo '</form>';	
	}
	/**
	 * @desc prikaze vnose v anketo
	 * /
	function displayVnosi() {//OSTANE
		global $lang;
		$row = SurveyInfo::getInstance()->getSurveyRow();
	 	if ($_GET['m'] == 'SN_izvozi') {
			echo '<div id="anketa_edit" style="min-height: 160px;">' . "\n\r";

			//Excel izvozi
			echo '<fieldset class="izvozi">';
			echo '<legend>EXCEL IZVOZI</legend>';
			echo '<div class="floatLeft" style="width:400px;">';
			echo '<p><a href="exportexcel.php?anketa=' . $this->anketa . '"><span>' . $lang['srv_export'] . ' EXCEL za EGE' . '</span></a></p> ' . "\n\r";
			echo '<p><a href="exportexcel.php?anketa=' . $this->anketa . '&tip=SN"><span>' . $lang['srv_export'] . ' EXCEL za ALTERJE' . '</span></a></p> ' . "\n\r";
			echo '</div>';

			echo '<div class="floatLeft"  style="width:auto">';
			echo '<div id="div_error">';
			//			echo '<img src="icons/icons/error.png" alt="" vartical-align="middle" />';
			echo $lang['srv_izvoz_Excel_note'] . '</div>';
			echo '</div>';
			echo ' </fieldset>';
			echo ' <br/>';

			//SPSS izvozi
			echo '<fieldset class="izvozi">';
			echo '<legend>SPSS IZVOZI ZA EGE</legend>';
			//			echo '<p>Ker se pri nekaterih SPSS verzijah pri izvozu podatkov pojavljajo tezave, je treba izvoz datoteke s podatki opraviti v EXCELu, nato pa s spodnjimi SPSS datotekami s strukturami (sintaksami) podatke uvoziti v SPSS iz EXCELa.</p>';
			echo '<div class="floatLeft" style="width:400px;">';
			echo '<p>' . $lang['srv_export'] . ' SPSS: <a href="exportspss.php?anketa=' . $this->anketa . '">' . $lang['srv_structure'] . '</a> ' . $lang['srv_and'] . '
			                     <a href="exportspss.php?anketa=' . $this->anketa . '&amp;podatki=yes">' . $lang['srv_data'] . '</a> </p>
			            <p>' . $lang['srv_notext'] . ':
			                <a href="exportspss.php?anketa=' . $this->anketa . '&amp;notext=yes">' . $lang['srv_structure'] . '</a> ' . $lang['srv_and'] . '
			                <a href="exportspss.php?anketa=' . $this->anketa . '&amp;notext=yes&amp;podatki=yes">' . $lang['srv_data'] . '</a>
			            </p>' . "\n\r";
			echo '<p>' . $lang['srv_metapodatki'] . ' SPSS: <a href="exportspss.php?anketa=' . $this->anketa . '&amp;meta=yes">' . $lang['srv_structure'] . '</a> ' . $lang['srv_and'] . '
				                     <a href="exportspss.php?anketa=' . $this->anketa . '&amp;podatki=yes&amp;meta=yes">' . $lang['srv_data'] . '</a> </p>';
			echo '</div>';
			echo '<div class="floatLeft"  style="width:800px">';
			echo '<div id="div_error">';
			//			echo '<img src="icons/icons/error.png" alt="" vartical-align="middle" />';
			echo $lang['srv_izvoz_SPSS_note'] . '</div>';
			echo '</div>';
			echo ' </fieldset>';

			// EGO
			echo ' <br/>';
			echo '<fieldset class="izvozi">';
			echo '<legend>SPSS IZVOZI ZA ALTERJE</legend>';
			echo '</fieldset>';
		} else { // data iz baze
			echo '<div id="anketa_edit">' . "\n\r";
			$this->displayData();
			echo ' </div>';
		}
	}
*/
	/**
	 * @desc prikaze podatke v tabeli
	 */
	function displayData() {
		global $lang;
		global $site_url;

		//include_once ('DisplaySurveyData.php');
		$dsd = new DisplaySurveyData($this->anketa);
		$dsd->display();
	}

	/**
	 * @desc Vrne ID trenutnega uporabnika (ce ni prijavljen vrne 0)
	 */
	function uid() {
		global $global_user_id;

		return $global_user_id;
	}

	/**
	 * @desc Vrne vse uporabnike iz baze
	 */
	static function db_select_users() {
		return sisplet_query("SELECT name, surname, id, email FROM users ORDER BY name ASC");
	}

	/**
	 * @desc Vrne vse nepobrisane uporabnike iz baze
	 */
	private static function db_select_users_forLevel($anketa = null) {
		global $global_user_id, $admin_type;

		// tip admina:  0=>admin, 1=>manager, 2=>clan, 3=>user
		switch ( $admin_type ) {

            // admin vidi vse
			case 0: 
				return sisplet_query("SELECT name, surname, id, email FROM users WHERE status!='0' ORDER BY name ASC");
				break;

            // manager vidi ljudi pod sabo
			case 1: 	 
                if ($anketa === null)
                    return sisplet_query("SELECT a.name, a.surname, a.id, a.email FROM users a, srv_dostop_manage m WHERE a.status!='0' AND m.manager='" .$global_user_id ."' AND m.user=a.id");
                else
                    return sisplet_query("SELECT a.name, a.surname, a.id, a.email FROM users a, srv_dostop_manage m WHERE a.status!='0' AND m.manager='" .$global_user_id ."' AND m.user=a.id UNION SELECT u.name, u.surname, u.id, u.email FROM users u, srv_dostop d WHERE d.ank_id='$anketa' AND d.uid=u.id");
                break;
			 	
			case 2:
			case 3:
				// TODO // clani in userji lahko vidijo samo tiste ki so jim poslali maile in so se registrirali	
				// ce smo v urejanju nastavitve ankete vidijo vse, ki so dodeljeni anketi, da jim lahko nastavijo
				if ($anketa === null)
					return sisplet_query("SELECT name, surname, id, email FROM users WHERE 1 = 0");
				else
					return sisplet_query("SELECT u.name, u.surname, u.id, u.email FROM users u, srv_dostop d WHERE u.status!='0' AND d.ank_id='$anketa' AND d.uid=u.id");
				break;
        }
        
		return null;
	}

	function display_dostop_users($show_all=0){
		global $global_user_id, $admin_type, $lang;
		
		$avtorRow = SurveyInfo::getInstance()->getSurveyRow();
		
		// Prikazemo samo userje ki lahko urejajo anketo
		if($show_all == 0){

			echo '	<input type="hidden" name="dostop_edit" value="1" />' . "\n";
			
			$sql1 = sisplet_query("SELECT u.name, u.surname, u.id, u.email FROM users u, srv_dostop d WHERE d.ank_id='$this->anketa' AND d.uid=u.id");
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				// Da ga ne pocistimo ce je disablan (sam sebe ne more odstranit in avtorja se ne sme odstranit)
				if($avtorRow['insert_uid'] == $row1['id'] || $global_user_id == $row1['id'])
					echo '	<input type="hidden" name="uid[]" value="' . $row1['id'] . '" />' . "\n";
			
				echo '<div id="div_for_uid_' . $row1['id'] . '" name="dostop_active_uid" class="floatLeft dostop_for_uid">' . "\n";
				echo '<label nowrap for="uid_' . $row1['id'] . '" title="' . $row1['email'] . '">';
                                echo '<input type="checkbox" name="uid[]" value="' . $row1['id'] . '" id="uid_' . $row1['id'] . '" checked="checked" '.($avtorRow['insert_uid'] == $row1['id'] || $global_user_id == $row1['id'] ? ' disabled="disabled"' : '').' autocomplete="off"/>';
                                echo $row1['name'] . ($avtorRow['insert_uid'] == $row1['id'] ? ' (' . $lang['author'] . ')' : '') . '</label>' . "\n";
				echo ' <span class="faicon edit small icon-as_link" onclick="javascript:anketa_user_dostop(\''.$row1['id'].'\');"></span>';
				echo '</div>' . "\n";
			}
		}
		// Prikazemo vse userje, ki jih lahko uporabnig dodaja
		else{
			$sql1 = $this->db_select_users_forLevel($this->anketa);
			if ( mysqli_num_rows($sql1) > 0 ) {
				
				echo '<span id="dostop_active_show_1"><a href="#" onClick="dostopActiveShowAll(\'true\'); return false;">'.$lang['srv_dostop_show_all'].'</a></span>';
				echo '<span id="dostop_active_show_2" class="displayNone"><a href="#" onClick="dostopActiveShowAll(\'false\'); return false;">'.$lang['srv_dostop_hide_all'].'</a></span>';

				echo '	<input type="hidden" name="dostop_edit" value="1" />' . "\n";

				while ($row1 = mysqli_fetch_array($sql1)) {
					$sql2 = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='$this->anketa' AND uid='$row1[id]'");
					
					$checked = (mysqli_num_rows($sql2) > 0) ? ' checked="checked"' : '';
					
					// Da ga ne pocistimo ce je disablan (sam sebe ne more odstranit in avtorja se ne sme odstranit)
					if($avtorRow['insert_uid'] == $row1['id'] || $global_user_id == $row1['id'])
						echo '	<input type="hidden" name="uid[]" value="' . $row1['id'] . '" />' . "\n";

					echo '<div id="div_for_uid_' . $row1['id'] . '" name="dostop_active_uid" class="floatLeft dostop_for_uid'.$_css_hidden.'">' . "\n";
					echo '<label nowrap for="uid_' . $row1['id'] . '" title="' . $row1['email'] . '">';
                                        echo '<input type="checkbox" name="uid[]" value="' . $row1['id'] . '" id="uid_' . $row1['id'] . '" '.$checked.' '.($avtorRow['insert_uid'] == $row1['id'] || $global_user_id == $row1['id'] ? ' disabled="disabled"' : '').' autocomplete="off"/>' . "\n";
                                        echo $row1['name'] . ($avtorRow['insert_uid'] == $row1['id'] ? ' (' . $lang['author'] . ')' : '') . '</label>';
					if ($checked != '')
						echo ' <span class="faicon edit small icon-as_link" onclick="javascript:anketa_user_dostop(\''.$row1['id'].'\');"></span>';
					echo '</div>' . "\n";
				}
			}
		}
	}
	
    // Dodajanje uredniskega dostopa do ankete
    public function display_add_survey_dostop(){
        global $lang;
        global $admin_type;

        echo '<p class="bold">';

        // Admini in managerji lahko dodajo dostop komurkoli
        if($admin_type == 0 || $admin_type == 1){
            echo $lang['srv_dostop_adduserstxt_admin'].' '.AppSettings::getInstance()->getSetting('app_settings-app_name').'. ';
        }
        // Ostali uporabniki lahko dodajo dostop samo ze registriranim uporabnikom
        else{
            echo $lang['srv_dostop_adduserstxt'].' '.AppSettings::getInstance()->getSetting('app_settings-app_name').'! ';
        }

        // AAI ima poseben link na help
        if(isAAI()){
            echo $lang['srv_dostop_adduserstxt_aai_more'];
        }
        
        echo '</p>';

        echo '<input type="hidden" name="aktiven" value="1" >';
        
        echo '<p>';
        echo $lang['srv_dostop_adduserstxt_email'].'<br />';
        echo '<textarea name="addusers" id="addusers" style="height: 90px; margin-top: 5px;"></textarea>';
        echo '</p>';
        
        echo '<p>';
        echo '<label><input type="checkbox" id="addusers_note_checkbox" style="margin:-2px 0 0 0;" onClick="dostopNoteToggle();"> '.$lang['srv_dostop_adduserstxt_note'].'</label><br />';
        echo '<textarea name="addusers_note" id="addusers_note" style="height: 90px; margin-top: 5px; display: none;">'.$lang['srv_dostop_adduserstxt_note_text'].'</textarea>';
        echo '</p>';
        
        echo '<br class="clr" />';

        echo '<p>'.$lang['srv_dostop_adduserstxt_end'].'</p>';

        // Gumb za dodajanje in posiljanje
        echo '<br class="clr" />';

        echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="dostopAddAccess(); return false;">';
        echo $lang['srv_dostop_addusers_button'] . '</a></div></span>';
        echo '<div class="clr"></div>';
        echo '<br class="clr" />';
	}


	/**
	 * @desc Vrne podatke o uporabniku
	 */
	static function db_select_user($uid) {
		return sisplet_query("SELECT name, surname, id, email FROM users WHERE id='$uid'");
	}

	/** Preveri ali uporabnik ustreza minimalni zahtevi statusa
	 * 
	 * @param $minimum_role_request minimalna zahteva (lahko podamo kot array posamezno)
	 * @return true/false
	 */
	function user_role_cehck($minimum_role_request = U_ROLE_ADMIN) {
		global $admin_type;

		if (is_array($minimum_role_request) && count($minimum_role_request) > 0) { // ce podamo kot array preverimo za vsak zapis posebej
			foreach ($minimum_role_request as $role) {
				if ($admin_type == $role)
				return true;
			}
		} else {
			if ($admin_type <= $minimum_role_request)
			return true;
		}
		return false;
	}
	var $getSurvey_type = null;
	function getSurvey_type($sid) {
		if ($this->getSurvey_type != null)
		return $this->getSurvey_type;

		// polovimo tip ankete
		$str_survey_type = sisplet_query("SELECT survey_type FROM srv_anketa WHERE id = '" . $sid . "'");
		$row_survey_type = mysqli_fetch_assoc($str_survey_type);
		$this->getSurvey_type = $row_survey_type['survey_type'];
		return $this->getSurvey_type;
	}
	
	/**
	* TODO ???
	* 
	* @param mixed $what
	* @param mixed $isChecked
	* $forma - pri hitirh nastavitvah forme prikazemo nekje krajsi text
	*/
	function display_alert_label($what, $isChecked = false, $forma = false) {
		global $lang, $global_user_id;
		
		$custom_alert = array();
		$sql_custom_alert = sisplet_query("SELECT uid, type FROM srv_alert_custom WHERE ank_id = '$this->anketa'");
		while ($row_custom_alert = mysqli_fetch_array($sql_custom_alert)) {
			$custom_alert[$row_custom_alert['type']][$row_custom_alert['uid']] = 1;
		}
		
		switch ($what) {
			case 'finish_respondent_language': 	// respondent ki je zakljucil anketo v drugem jeziku (mu omogocimo nastavljanje custom maila za obvescanje)
				if ($isChecked) {
			
					$p = new Prevajanje($anketa);
					$p->dostop();
					$jeziki = $p->get_all_translation_langs();
					if(!empty($jeziki)){
					
						$row = SurveyInfo::getInstance()->getSurveyRow();
						echo '<br />';		
						
						foreach($jeziki as $key => $jezik){
							echo '<span class="clr" style="padding-left:20px; line-height:22px;">'.$lang['srv_alert_respondent'].' - '.$jezik;
							echo ' <a href="#" onclick="alert_custom(\'respondent_lang_'.$key.'\', \'0\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
							echo '</span>';
						}					
					}
				}
				break;
			
			case 'finish_respondent': // respondent ki je zakljucil anketo

				if ($isChecked) {
					// preverimo ali obszaja sistemska spremenljivka email če ne jo dodamo
					$sqlEmail = sisplet_query("SELECT s.sistem, s.variable, s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.variable='email' AND s.gru_id=g.id AND g.ank_id='$this->anketa'");
					$sqlIme = sisplet_query("SELECT s.sistem, s.variable, s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.variable='ime' AND s.gru_id=g.id AND g.ank_id='$this->anketa'");
					// ce sta dodani obe sistemski spremenljivki je fse ok
					$email_ok = $ime_ok = false;
					if ( mysqli_num_rows($sqlEmail) > 0 && mysqli_num_rows($sqlIme) > 0) {
						$email_ok = $ime_ok = true;
					} else {

						// manjka ena ali obe potrebni sistemski spremenljivki
						// email je nujen, zato ga dodamo avtomatsko
						if ( mysqli_num_rows($sqlEmail) == 0 ) {
							//dodamo email
							$sa = new SurveyAdmin(1, $this->anketa);
							if (in_array('email',$sa->alert_add_necessary_sysvar( array('email') , false))) {
								$email_ok = true;
							}
							// email v tem primeru spremenimo da je viden, ker gre za alert
							$sqlEmail = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.variable='email' AND s.gru_id=g.id AND g.ank_id='$this->anketa'");
							$rowEmail = mysqli_fetch_array($sqlEmail);
							sisplet_query("UPDATE srv_spremenljivka SET visible='1' WHERE id = '$rowEmail[id]'");
							// emailu po novem nastavimo preverjanje pravilnosti emaila in mehko opozorilo na to preverjanje
							$v = new Vprasanje($this->anketa);
							$v->spremenljivka = $rowEmail['id'];
							$v->set_email($reminder=1);
							
						} else {
							// email je ze dodan damo tekst za ok
							$email_ok = true;
						}

						// preverimo še za ime
						if ( mysqli_num_rows($sqlIme) == 0 ) {
								
						} else{
							$ime_ok = true;
						}
					}
						
					echo '<label for="alert_finish_respondent">'.$lang['srv_alert_respondent'].'</label>';
						
					if ($email_ok && $ime_ok) {
						echo $lang['srv_alert_respondent_note_ok_email_ime'];
						echo ' <img src="icons/icons/accept.png" alt="" vartical-align="middle" />' . "\n\r";
						
						echo ' <a href="#" onclick="alert_custom(\'respondent\', \'0\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
						$sql1 = sisplet_query("SELECT finish_respondent_if  FROM srv_alert WHERE ank_id='$this->anketa'");
						$row1 = mysqli_fetch_array($sql1);
						echo ' <a href="#" onclick="alert_edit_if(\'2\'); return false;"><span class="faicon if_add" '.($row1['finish_respondent_if']==0?'style=""':'').'></span></a> ';
						if ($row1['finish_respondent_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($row1['finish_respondent_if']); }
					
					} else {
						if ($ime_ok) {
							// pomeni da email ni ok! napaka
							echo $lang['srv_alert_respondent_note_notok_email'];
						} else {
							// email je ok, ime ni, uporabnika fprasamo ali hoce se ime
							echo $lang['srv_alert_respondent_note_ok_email'];
							
							echo ' <a href="#" onclick="alert_custom(\'respondent\', \'0\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
							$sql1 = sisplet_query("SELECT finish_respondent_if  FROM srv_alert WHERE ank_id='$this->anketa'");
							$row1 = mysqli_fetch_array($sql1);
							echo ' <a href="#" onclick="alert_edit_if(\'2\'); return false;"><span class="faicon if_add" '.($row1['finish_respondent_if']==0?'style=""':'').'></span></a> ';
							if ($row1['finish_respondent_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($row1['finish_respondent_if']); }
							
							if(!$forma){
								echo $lang['srv_alert_respondent_note_ime'];
								echo ' &nbsp;<a href="#" onClick="alert_add_necessary_sysvar(\'finish_respondent\', $(this)); return false;"><span class="faicon add icon-blue"></span> '.$lang['srv_alert_respondent_note_link'].'</a>' . "\n\r";
							}
						}
					}

					// preverimo sistemske nastavitve in spremenljivke ime

				} else { 
					echo '<label for="alert_finish_respondent">'.$lang['srv_alert_respondent'].'</label>';
				}
				break;

			case 'finish_respondent_cms': // respondent prepoznan iz CMS ko je izpolnil anketo
				
                //respondent iz cms
				echo '<label for="alert_finish_respondent_cms">'.$lang['srv_alert_respondent_cms'].'</label>';
					
				if ($isChecked) {
					// preverimo sistemske nastavitve in spremenljivke
					//$sqlCMS = sisplet_query("SELECT user_from_cms FROM srv_anketa WHERE id='$this->anketa'");
					//$rowCMS = mysqli_fetch_assoc($sqlCMS);
					$rowCMS = SurveyInfo::getInstance()->getSurveyRow();
					if ($rowCMS['user_from_cms'] > 0) {
						echo $lang['srv_alert_respondent_cms_note_ok'];
						echo ' <img src="icons/icons/accept.png" alt="" vartical-align="middle" />' . "\n\r";
						
						echo ' <a href="#" onclick="alert_custom(\'respondent_cms\', \'0\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
						
						$sql1 = sisplet_query("SELECT finish_respondent_cms_if  FROM srv_alert WHERE ank_id='$this->anketa'");
						$row1 = mysqli_fetch_array($sql1);
						echo ' <a href="#" onclick="alert_edit_if(\'3\'); return false;"><span class="faicon if_add" '.($row1['finish_respondent_cms_if']==0?'style=""':'').'></span></a> ';
						if ($row1['finish_respondent_cms_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($row1['finish_respondent_cms_if']); }
						
					} else {
						echo $lang['srv_alert_respondent_cms_note'];
						echo ' &nbsp;<a href="#" onClick="alert_change_user_from_cms(\'finish_respondent_cms\', $(this)); return false;"><span class="faicon add icon-blue"></span> '.$lang['srv_alert_respondent_cms_note_link'].'</a>' . "\n\r";
					}
				}
				break;
					
			case 'finish_author': // obveščanje ob izpolnjeni anketi
			case 'expire_author': // obveščanje ob poteku ankete
			case 'active_author': // obveščanje ob aktivaciji/deaktivaciej ankete
			case 'delete_author': // obveščanje ob izbrisu ankete

				// avtor ankete
				if($forma) 
					echo '<label for="alert_'.$what.'">'.$lang['srv_alert_author2'].'</label>'; 
				else 
					echo '<label for="alert_'.$what.'">'.$lang['srv_alert_author'].'</label>';
				
				if ($isChecked) {
					//$sql = sisplet_query("SELECT insert_uid, edit_uid FROM srv_anketa WHERE id='$this->anketa'");
					//$row = mysqli_fetch_assoc($sql);
					$b = null;
					
					$row = SurveyInfo::getInstance()->getSurveyRow();
					
					echo '<br/>';
					
					if ($what == 'finish_author')
						$db_field = 'alert_complete';
					else if ($what == 'expire_author')
						$db_field = 'alert_expire';
					else if ($what == 'active_author')
						$db_field = 'alert_active';
					else if ($what == 'delete_author')
						$db_field = 'alert_delete';
					
					// polovimo avtorja - novo kjer se ga lahko tudi izklopi (zaenkrat samo pri koncani anketi)
					if($what == 'finish_author'){
						$sqlAuthor = $this->db_select_user($row['insert_uid']);
						$rowAuthor = mysqli_fetch_array($sqlAuthor);
						$sql1 = sisplet_query("SELECT *, uid AS id FROM srv_dostop WHERE ank_id='$this->anketa' AND uid='".$row['insert_uid']."'");
						$row1 = mysqli_fetch_array($sql1);
						
						// Ce smo ravno z ajaxom vklopili obvescanje avtorja, ga tudi aktiviramo
						if(isset($_POST['checked']) && isset($_POST['what']) && $_POST['what']=='finish_author'){
							$checked = ($_POST['checked'] == true) ? ' checked="checked" ' : '';
						}
						else{
							$checked = ($row1[$db_field] == '1') ? ' checked="checked" ' : '';
						}

						echo '<span class="alert_authors"><input type="checkbox" name="alert_'.$what.'_uid[]" value="' . $row['insert_uid'] . '" id="alert_'.$what.'_uid_' . $row['insert_uid'] . '"' . $checked . ' autocomplete="off"/>' . "\n\r";
						echo '<label for="alert_'.$what.'_uid_' . $row['insert_uid'] . '" title="' . $rowAuthor['email'] . '">' . $rowAuthor['name'] . ' (' . $lang['author'] . ': '.$rowAuthor['email']. ')' . '</label>' . "\n\r";
						if ($what == 'finish_author') {
							echo ' <a href="#" onclick="alert_custom(\'author\', \''.$row['insert_uid'].'\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
							echo ' <a href="#" onclick="alert_edit_if(\'1\', \''.$row1['id'].'\'); return false;"><span class="faicon if_add" '.($row1['alert_complete_if']==0?'style=""':'').'></span></a> ';
							if ($row1['alert_complete_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($row1['alert_complete_if']); }
						}
						echo '</span>' . "\n\r";
					}
					// polovimo avtorja	pri ostalih obvestilih
					else{				
						$sqlAuthor = $this->db_select_user($row['insert_uid']);
						$rowAuthor = mysqli_fetch_array($sqlAuthor);
						$sql1 = sisplet_query("SELECT *, uid AS id FROM srv_dostop WHERE ank_id='$this->anketa' AND uid='".$row['insert_uid']."'");
						$row1 = mysqli_fetch_array($sql1);
						
						echo '<span class="alert_authors"><input type="checkbox" name="alert_'.$what.'_uid[]" value="' . $row['insert_uid'] . '" id="alert_'.$what.'_uid_' . $row['insert_uid'] . '" checked="checked" disabled="disabled" autocomplete="off"/>' . "\n\r";
						echo '<label for="alert_'.$what.'_uid_' . $row['insert_uid'] . '" title="' . $rowAuthor['email'] . '">' . $rowAuthor['name'] . ' (' . $lang['author'] . ': '.$rowAuthor['email']. ')' . '</label>' . "\n\r";
						if ($what == 'finish_author') {
							echo ' <a href="#" onclick="alert_custom(\'author\', \''.$row['insert_uid'].'\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
							echo ' <a href="#" onclick="alert_edit_if(\'1\', \''.$row1['id'].'\'); return false;"><span class="faicon if_add" '.($row1['alert_complete_if']==0?'style=""':'').'></span></a> ';
							if ($row1['alert_complete_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($row1['alert_complete_if']); }
						}
						echo '</span>' . "\n\r";
					}
					

					// polovimo ostale userje ki imajo dostop
					$sql1 = sisplet_query("SELECT u.id, u.name, u.surname, u.email, dostop.".$db_field.", dostop.alert_complete_if FROM users as u "
					." RIGHT JOIN (SELECT sd.uid, sd.".$db_field.", sd.alert_complete_if FROM srv_dostop as sd WHERE sd.ank_id='".$this->anketa."') AS dostop ON u.id = dostop.uid WHERE u.id != '".$row['insert_uid']."'");
					while ($row1 = mysqli_fetch_assoc($sql1)) {
						if ($row1['id']) { // se zgodi da je prazno za metauserje
							// avtor je vedno chekiran
							$checked = ( $row1[$db_field] == '1') ? ' checked="checked"' : '';
							echo '<span class="alert_authors"><input type="checkbox" name="alert_'.$what.'_uid[]" value="' . $row1['id'] . '" id="alert_'.$what.'_uid_' . $row1['id'] . '"' . $checked . ' autocomplete="off"/>' . "\n\r";
							echo '<label for="alert_'.$what.'_uid_' . $row1['id'] . '" title="' . $row1['email'] . '">' . $row1['name'] . ' ('.$row1['email'].')</label>' . "\n\r";
							if ($what == 'finish_author') {
								echo ' <a href="#" onclick="alert_custom(\'author\', \''.$row1['id'].'\'); return false;" title="'.$lang['srv_alert_custom'].'"><span class="faicon text_file_small"></span></a>';
								echo ' <a href="#" onclick="alert_edit_if(\'1\', \''.$row1['id'].'\'); return false;"><span class="faicon if_add" '.($row1['alert_complete_if']==0?'style=""':'').'></span></a> ';
								if ($row1['alert_complete_if']>0) { if ($b==null) $b = new Branching($this->anketa); $b->conditions_display($row1['alert_complete_if']); }
							}
							echo '</span>' . "\n\r";
						}
					}
				}
				break;
		}

	}

	
	/**
	* TODO ???
	* 
	* @param mixed $row
	*/
	function showUserCodeSettings($row = null) {
		global $lang;

		if ($row == null) {
			$row = SurveyInfo::getInstance()->getSurveyRow();
		}

		$disabled = true;
		$disabled2 = false;
		if (SurveyInfo::getInstance()->checkSurveyModule('email') || SurveyInfo::getInstance()->checkSurveyModule('phone')){
			$disabled = false;
		}

		if ($row['usercode_skip'] == 1) {
			$disabled2 = true;
		}

		#echo '<span class="nastavitveSpan" >&nbsp;</span>';
		echo '<span ' . ($disabled ? 'class="gray"' : '') . '>' . $lang['usercode_skip'] . Help::display('usercode_skip') . ':';
		echo '<input type="radio" name="usercode_skip" value="0" id="usercode_skip_0"' . ($row['usercode_skip'] == 0 ? ' checked="checked"' : '') . ($disabled ? ' disabled="disabled"' : '') . ' onChange="handleUserCodeSkipSetting();"/><label for="usercode_skip_0">' . $lang['no1'] . '</label>' . "\n\r";
		echo '<input type="radio" name="usercode_skip" value="1" id="usercode_skip_1"' . ($row['usercode_skip'] == 1 ? ' checked="checked"' : '') . ($disabled ? ' disabled="disabled"' : '') . ' onChange="handleUserCodeSkipSetting();"/><label for="usercode_skip_1">' . $lang['yes'] . '</label>' . "\n\r";
		echo '<input type="radio" name="usercode_skip" value="2" id="usercode_skip_2"' . ($row['usercode_skip'] == 2 ? ' checked="checked"' : '') . ($disabled ? ' disabled="disabled"' : '') . ' onChange="handleUserCodeSkipSetting();"/><label for="usercode_skip_2">' . $lang['srv_setting_onlyAuthor'] . '</label>' . "\n\r";
		echo '</span>';
		echo '<br />';
		echo '<br/>';
		#echo '<span class="nastavitveSpan" >&nbsp;</span>';
		echo '<span ' . ($disabled /*|| $disabled2*/ ? 'class="gray"' : '') . '>' . $lang['usercode_required'] . help::display('usercode_required') .  ': ';
		echo '<input type="radio" name="usercode_required" value="0" id="usercode_required_0"' . ($row['usercode_required'] == 0 ? ' checked="checked"' : '') . ($disabled /*|| $disabled2*/ ? ' disabled="disabled"' : '') . ' onChange="handleUserCodeRequiredSetting();"/><label for="usercode_required_0">' . $lang['no1'] . '</label>' . "\n\r";
		echo '<input type="radio" name="usercode_required" value="1" id="usercode_required_1"' . ($row['usercode_required'] == 1 ? ' checked="checked"' : '') . ($disabled /*|| $disabled2*/ ? ' disabled="disabled"' : '') . ' onChange="handleUserCodeRequiredSetting();"/><label for="usercode_required_1">' . $lang['yes'] . '</label>' . "\n\r";
		echo '</span>';
		echo '<br/>';
		echo '<div id="div_usercode_text"'.(/*$row['usercode_skip'] == 1 || */$row['usercode_required'] == 0 ? ' class="displayNone"' : '').'>';
		$nagovorText = ($row['usercode_text'] && $row['usercode_text'] != null && $row['usercode_text'] != "") ? $row['usercode_text'] : $lang['srv_basecode'];
		#echo '<span class="nastavitveSpan2" >&nbsp;</span>';
		echo '<span ' . ($disabled ? 'class="gray"' : '') . '>' . $lang['usercode_text'] . ': ';
		echo '            <textarea name="usercode_text" ' . ($disabled ? ' disabled="disabled"' : '') . '>' . $nagovorText . '</textarea>' . "\n\r";
		echo '</span>';
		echo '</div>';
	}
	/**
	* TODO ???
	* 
	* @param mixed $row
	*/
	function respondenti_iz_baze($row = null, $show_fieldset=true) {
		global $lang;
		global $admin_type;
		
		if ($row == null) {
			$row = SurveyInfo::getInstance()->getSurveyRow();
		}
		
		/* aktivnost vec ni pogoj za vklop email vabil:
		 * -         omogočiti aktiviranje emial zavihka, četudi je anketa neaktivna (preprečiti pa pošijanje emailov če je ankete neaktivna)
		*/

		if ($admin_type <= 1) {
			$_cssDisabled = '';
			$_disabled = '';
		} else {
			$_cssDisabled = ' gray';
			$_disabled = ' disabled="disabled"';
		}
		
		echo '<input type="hidden" name="anketa" value="' . $this->anketa . '" />' . "\n\r";
		echo '<input type="hidden" name="grupa" value="' . $this->grupa . '" />' . "\n\r";
		echo '<input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";

		if ($show_fieldset) {
			echo '<fieldset>';
			echo '<legend class="'.$_cssDisabled.'">' . $lang['srv_user_base_vabila'] . '</legend>';
		} else {
			echo '<p class="strong">3. ' . $lang['srv_user_base_vabila'] . '</p>';
		}

		if ($_cssDisabled == '' && $_disabled == '') {
			echo '<span class="'.$_cssDisabled.'" ><label>' . $lang['srv_user_base_email'] . ':</label></span>';
			echo '            <input type="radio" name="email" value="1" id="email_1"' . (SurveyInfo::getInstance()->checkSurveyModule('email') ? ' checked="checked"' : '') . ' onChange="//handleUserCodeSetting();" '.$_disabled.'/><label for="email_1" class="'.$_cssDisabled.'">' . $lang['yes'] . '</label>' . "\n\r";
			echo '            <input type="radio" name="email" value="0" id="email_0"' . (!SurveyInfo::getInstance()->checkSurveyModule('email') ? ' checked="checked"' : '') . ' onChange="//handleUserCodeSetting();" '.$_disabled.'/><label for="email_0" class="'.$_cssDisabled.'">' . $lang['no1'] . '</label>' . "\n\r";
			echo '<br/>';
		}

		// dodatne nastavitve za pošiljanje kode pri izpolnjevanju ankete
		if ($_GET['a'] == 'vabila' || $_GET['a'] == 'email' )  {
			echo '<div id="userCodeSettings">';
			$this->showUserCodeSettings($row);
			echo '</div>';
		}
		
		if ($admin_type > 1)
			echo ''.$lang['srv_user_base_user_note'].'';
		if ($show_fieldset) {
			echo '</fieldset>';
		}
	}
	/**
	* TODO ???
	* 
	*/
	function anketa_aktivacija_note() {
		global $lang;
		$row = SurveyInfo::getInstance()->getSurveyRow();
		if ($row['active'] == 0) {
			echo $lang['srv_url_survey_not_active'];
			echo '	<span id="vabila_anketa_aktivacija" class="link_no_decoration">' . "\n\r";
			echo '		<a href="#" onclick="anketa_active(\'' . $this->anketa . '\',\'' . $row['active'] . '\'); return false;" title="' . $lang['srv_anketa_noactive'] . '">';
			echo '      <span class="faicon star icon-orange_very_dark"></span>';
			echo '      <span >' . $lang['srv_anketa_setActive'] . '</span>';
			echo '      </a>' . "\n\r";
			echo '	</span>' . "\n\r";
		} else {
			echo $lang['srv_url_intro_active'];
			echo '	<span id="vabila_anketa_aktivacija" class="link_no_decoration">' . "\n\r";
			echo '		<a href="#" onclick="anketa_active(\'' . $this->anketa . '\',\'' . $row['active'] . '\'); return false;" title="' . $lang['srv_anketa_active'] . '">';
			echo '      <span class="faicon star_on"></span>';
			echo '      <span >' . $lang['srv_anketa_setNoActive'] . '</span>';
			echo '      </a>' . "\n\r";
			echo '	</span>' . "\n\r";
		}
	}
	function anketa_diagnostika_note($diagnostics,$show_link = false) {
		global $lang;
		$diagnostics->printNote($show_link);
			
	}
	
	
	/**
	 * @desc prikaze dropdown z nastavitvami ankete (globalne, za celo 1ko) -- Prva stran -> Nastavitve -> Sistemske nastavitve
     * Sistemske nastavitve: mora biti admin da ima dostop
	 */
	function anketa_nastavitve_system() {
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;


        // Ni admin - nima pravic
        if ($admin_type != 0) {

            echo '<div id="anketa_edit">';
            echo $lang['srv_settingsSystemNoRights'];
		    echo '</div>';	

            return;
        }


		echo '<div id="anketa_edit">';

        echo '<form name="settingsanketa" action="ajax.php?a=editanketasettings&m=system" method="post" autocomplete="off">';

        echo '  <input type="hidden" name="location" value="' . $_GET['a'] . '" />';
        echo '  <input type="hidden" name="submited" value="1" />';


        // SISTEMSKE NASTAVITVE (prej v settings_optional.php)
        echo '<fieldset><legend>'.$lang['as_basic'].'</legend>';
        AppSettings::getInstance()->displaySettingsGroup('basic');

        echo '<br />';

        // Kdo lahko ureja ankete
        echo '<span class="nastavitveSpan6"><label>' . $lang['SurveyDostop'] . ':</label></span>';

        $result = sisplet_query("SELECT value FROM misc WHERE what='SurveyDostop'");
        list ($SurveyDostop) = mysqli_fetch_row($result);

        echo '<select name="SurveyDostop">';
        echo '	<option value="0" '.($SurveyDostop=='0'?"SELECTED":"").'>'.$lang['forum_admin'].'</option>';
        echo '	<option value="1" '.($SurveyDostop=='1'?"SELECTED":"").'>'.$lang['forum_manager'].'</option>';
        echo '	<option value="2" '.($SurveyDostop=='2'?"SELECTED":"").'>'.$lang['forum_clan'].'</option>';
        echo '	<option value="3" '.($SurveyDostop=='3'?"SELECTED":"").'>'.$lang['forum_registered'].'</option>';
        echo '</select>';

        echo '<br />';

        // Default trajanje piskotka
        echo '<span class="nastavitveSpan6" ><label>' . $lang['SurveyCookie'] . ':</label></span>';

        $result = sisplet_query("SELECT value FROM misc WHERE what='SurveyCookie'");
        list ($SurveyCookie) = mysqli_fetch_row($result);

        echo '<select name="SurveyCookie">';
        echo '	<option value="-1" '.($SurveyCookie=='-1'?"SELECTED":"").'>'.$lang['without'].'</option>';
        echo '	<option value="0" '.($SurveyCookie=='0'?"SELECTED":"").'>'.$lang['srv_cookie_0'].'</option>';
        echo '	<option value="1" '.($SurveyCookie=='1'?"SELECTED":"").'>'.$lang['srv_cookie_1'].'</option>';
        echo '	<option value="2" '.($SurveyCookie=='2'?"SELECTED":"").'>'.$lang['srv_cookie_2'].'</option>';
        echo '</select>';

        echo '<br />';
        echo '</fieldset>';


        // INFO
        echo '<fieldset><legend>'.$lang['as_info'].'</legend>';
        AppSettings::getInstance()->displaySettingsGroup('info');
        echo '</fieldset>';


        // OMEJITVE
        echo '<fieldset><legend>'.$lang['as_limits'].'</legend>';
        AppSettings::getInstance()->displaySettingsGroup('limits');
        echo '</fieldset>';
        

        // SMTP NASTAVITVE
        echo '<fieldset><legend>'.$lang['as_smtp'].'</legend>';
        AppSettings::getInstance()->displaySettingsGroup('smtp');
        echo '</fieldset>';
        

        // MODULI
        echo '<fieldset><legend>'.$lang['as_modules'].'</legend>';
        AppSettings::getInstance()->displaySettingsGroup('modules');
        echo '</fieldset>';


        echo '<br />';

            
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_edithelp'] . '</legend>';
        
        echo '<span class="nastavitveSpan1" ><label>' . $lang['srv_edithelp'] . ' '.Help::display('srv_window_help').': </label></span>';
        Help :: edit_toggle();
        
        echo '</form>';
        echo '</fieldset>';

        
        // Missingi
        $smv = new SurveyMissingValues();
        $smv->SystemFilters();
        
        
        // save gumb
        echo '  <div class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa.submit();"><span>'.$lang['edit1337'] . '</span></a></div>';
        
        echo '<span class="clr"></span>';
        
        // div za prikaz uspešnosti shranjevanja
        if ($_GET['s'] == '1') {
            echo '<div id="success_save"></div>';
            echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
        }


		echo '</div>';		
	}

	/**
	* nastavitve predvidenih casov za komponente ankete in vprasanj iz katerih se nato racuna celotna dolzina vprasanj in ankete
	* 
	*/
	function anketa_nastavitve_predvidenicasi () {
		global $lang;
		
		echo '<div id="anketa_edit">';
		
		echo $lang['srv_predvidenicasi_help'];
		
		echo '<form name="timinganketa" method="post" action="ajax.php?a=editanketasettings&m=predvidenicasi">';
		
		echo '<fieldset><legend>'.$lang['srv_stran'].'</legend>';
		echo '<p>'.$lang['srv_timing_page'].': <input type="text" name="timing_stran" value="'.GlobalMisc::getMisc('timing_stran').'" /> s</p>';
		echo '</fieldset>';
		
		echo '<fieldset><legend>'.$lang['srv_vprasanja'].'</legend>';
		echo '<table style="width:100%">';
		echo '<tr><td></td><td>'.$lang['srv_timing_vprasanje'].'</td><td>'.$lang['srv_timing_kategorija'].'</td><td>'.$lang['srv_timing_kategorija_max'].'</td></tr>';
		for ($tip=1; $tip<= 21; $tip++) { if ($tip <= 8 || $tip >= 16) {
		
			echo '<tr><th align="left">'.$lang['srv_vprasanje_tip_'.$tip].'</th>';
			echo '<td><input type="text" name="timing_vprasanje_'.$tip.'" value="'.GlobalMisc::getMisc('timing_vprasanje_'.$tip).'" /> s</td>';
			
			if ($tip<=3 || $tip==6 || $tip==16 || $tip==17 || $tip==18 || $tip==19 || $tip==20)
				echo '<td><input type="text" name="timing_kategorija_'.$tip.'" value="'.GlobalMisc::getMisc('timing_kategorija_'.$tip).'" /> s</td>';
                        
                        if ($tip==3){
                                $kategorija_max = GlobalMisc::getMisc('timing_kategorija_max_'.$tip);
				echo '<td><input type="text" name="timing_kategorija_max_'.$tip.'" value="'.GlobalMisc::getMisc('timing_kategorija_max_'.$tip).'" /> s'
                                /*. '<select name="timing_kategorija_max_'.$tip.'" value="'.GlobalMisc::getMisc('timing_kategorija_max_'.$tip).'" >'
                                . '<option value="1" '. ($kategorija_max == 1 ? 'selected' : '') .'>1</option>'
                                . '<option value="2" '. ($kategorija_max == 2 ? 'selected' : '') .'>2</option>'
                                . '<option value="3" '. ($kategorija_max == 3 ? 'selected' : '') .'>3</option>'
                                . '<option value="5" '. ($kategorija_max == 5 ? 'selected' : '') .'>5</option>'
                                . '<option value="7" '. ($kategorija_max == 7 ? 'selected' : '') .'>7</option>'
                                . '<option value="10" '. ($kategorija_max == 10 ? 'selected' : '') .'>10</option>'
                                . '<option value="15" '. ($kategorija_max == 15 ? 'selected' : '') .'>15</option>'
                                . '<option value="20" '. ($kategorija_max == 20 ? 'selected' : '') .'>20</option>'
                                . '<option value="0" '. ($kategorija_max == 0 ? 'selected' : '') .'>'. $lang['all2'] .'</option>'
                                . '</select>'*/
                                . '</td>';
                        }
			
			echo '</tr>';
		
		} }
		echo '</table>';
		echo '</fieldset>';
		
		echo '<div class="buttonwrapper floatLeft spaceLeft">';
		echo '<a class="ovalbutton ovalbutton_orange btn_savesetting" onclick="document.timinganketa.submit();"><span>'.$lang['edit1337'].'</span></a>';
		echo '</div>';
		
		echo '<br />';
		
		echo '</form>';
		
		echo '</div>';
		
	}
	
	/** prikaze div da so nastavitve shranjene in ga nato skrije
	 * 
	 */
	function displaySuccessSave() {
		global $lang;
		echo $lang['srv_success_save'];
	}
	
	function tabTestiranje () {
        global $lang;
        
        // predvideni casi
		if ($_GET['m'] == 'predvidenicas') {	
			$this->testiranje_predvidenicas();
        } 
        // testni podatki
        elseif ($_GET['m'] == 'testnipodatki') {			
			$this->testiranje_testnipodatki();		
        } 
        // cas
        elseif ($_GET['m'] == M_TESTIRANJE_CAS) {		
			$this->testiranje_cas();;
        } 
        // cas
        elseif ($_GET['m'] == 'cas') {	
			$this->testiranje_cas();			
		}
	}
	
	/**
	* izracuna predvidene case po straneh glede na število in dolžino vprašanj
	* 
	*/
	function testiranje_predvidenicas($samo_izracunaj_skupini_cas=0) {
		global $lang;

		$expected_time = array();
                $expected_time_block = array();
                $block_labels_by_number = array();
		$expected_vprasanja = array();
		$verjetnost = array();
                $verjetnost_block = array();
                
                //from php 7.2 this helps to round numbers calculated in bcmod() - without it, it always rounds down to int
                bcscale(1);
		
		$sql = sisplet_query("SELECT introduction FROM srv_anketa WHERE id = '$this->anketa'");
		$row = mysqli_fetch_array($sql);
		
		// nagovor racunamo kot da gre za labelo
		$expected_vprasanja[0][0] = strlen(strip_tags($row['introduction'])) * GlobalMisc::getMisc('timing_vprasanje_5') / 100;
		$expected_vprasanja[0][1] = 1;
		$expected_vprasanja[0][2] = $lang['srv_vprasanje_tip_5'];
		$expected_vprasanja[0][3] = $lang['srv_intro_label'];
		
		$expected_time[0][0] = $expected_vprasanja[0][0] + GlobalMisc::getMisc('timing_stran');
		$expected_time[0][1] = $expected_time[0][0];
		
                $block_spr_data = $this->get_block_data_by_spr_id();
		
		$sql = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grupa g WHERE g.ank_id='$this->anketa' ORDER BY g.vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {
			
			$expected_time[$row['vrstni_red']][0] = 0;
			$expected_time[$row['vrstni_red']][1] = 0;
			
			$sql1 = sisplet_query("SELECT id, naslov FROM srv_spremenljivka WHERE gru_id='$row[id]' AND visible = '1'");
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				$expected_vprasanja[$row1['id']][0] = $this->vprasanje_predvideni_cas($row1['id']);
				$expected_vprasanja[$row1['id']][1] = $this->vprasanje_verjetnost($row1['id']);
				$expected_vprasanja[$row1['id']][2] = strip_tags($row1['naslov']);
				$expected_vprasanja[$row1['id']][3] = strip_tags($row['naslov']);
                                $expected_vprasanja[$row1['id']][4] = strip_tags($block_spr_data[$row1['id']]['label']);
                                    
				$expected_time[$row['vrstni_red']][0] += $expected_vprasanja[$row1['id']][0] * $expected_vprasanja[$row1['id']][1];	// dejanski
				$expected_time[$row['vrstni_red']][1] += $expected_vprasanja[$row1['id']][0];		// bruto - z vsemi vprasanji
				
				if ( $expected_vprasanja[$row1['id']][1] > $verjetnost[$row['vrstni_red']])
					$verjetnost[$row['vrstni_red']] = $expected_vprasanja[$row1['id']][1];
			}
			
			$expected_time[$row['vrstni_red']][0] += GlobalMisc::getMisc('timing_stran') * $verjetnost[$row['vrstni_red']];		// pri dejanskem trajanju strani upostevamo verjetnost najverjetnejsega vprasanja na strani (stran se pojavi z najvisjo verjetnostjo vseh vprasanj na strani)
			$expected_time[$row['vrstni_red']][1] += GlobalMisc::getMisc('timing_stran');
		}
                
                $sql = sisplet_query("SELECT * FROM srv_if as bl LEFT JOIN srv_branching as br ON br.parent = bl.id  WHERE bl.enabled='0' AND bl.tip='1' AND br.ank_id = '$this->anketa' ORDER BY bl.number ASC, br.vrstni_red ASC");
                $last_block_st = -1;
		while ($row = mysqli_fetch_array($sql)) {
                        $new_block = $last_block_st != $row['number'];
                        if($new_block){
                            $last_block_st = $row['number'];
                            $label = $row['label'] ? $row['label']: $lang['srv_blok'].' '.$last_block_st;
                            $block_labels_by_number[$last_block_st] = $label;
                            $expected_time_block[$last_block_st-1][0] = 0;
                            $expected_time_block[$last_block_st-1][1] = 0;
                        }
				
                        if(!$row['element_if']){
                            $expected_time_block[$last_block_st-1][0] += $expected_vprasanja[$row['element_spr']][0] * $expected_vprasanja[$row['element_spr']][1];	// dejanski
                            $expected_time_block[$last_block_st-1][1] += $expected_vprasanja[$row['element_spr']][0];		// bruto - z vsemi vprasanji
                            
                            if ( $expected_vprasanja[$row['element_spr']][1] > $verjetnost_block[$last_block_st-1])
                                $verjetnost_block[$last_block_st-1] = $expected_vprasanja[$row['element_spr']][1];
                        }
                        else{
                            $sql1 = sisplet_query("SELECT * FROM srv_branching WHERE parent='".$row['element_if']."' ORDER BY vrstni_red ASC");
                            while ($row1 = mysqli_fetch_array($sql1)) {
                                $expected_time_block[$last_block_st-1][0] += $expected_vprasanja[$row1['element_spr']][0] * $expected_vprasanja[$row1['element_spr']][1];	// dejanski
                                $expected_time_block[$last_block_st-1][1] += $expected_vprasanja[$row1['element_spr']][0];		// bruto - z vsemi vprasanji
                                $expected_vprasanja[$row1['element_spr']][4] = $label;

                                if ( $expected_vprasanja[$row1['element_spr']][1] > $verjetnost_block[$last_block_st-1])
                                    $verjetnost_block[$last_block_st-1] = $expected_vprasanja[$row1['element_spr']][1];
                            }
                        }
                                
                        if($new_block){
                            $expected_time_block[$last_block_st-1][0] += GlobalMisc::getMisc('timing_stran') * $verjetnost_block[$last_block_st-1];		// pri dejanskem trajanju strani upostevamo verjetnost najverjetnejsega vprasanja na strani (stran se pojavi z najvisjo verjetnostjo vseh vprasanj na strani)
                            $expected_time_block[$last_block_st-1][1] += GlobalMisc::getMisc('timing_stran');
                        }
		}

		// izpis za strani
		$max = 0;
		$total = 0;
		foreach ($expected_time AS $key => $val) {
			if ($val[1] > $max) $max = $val[1];
			$total += $val[0];
		}
		if ($max == 0) return;
		
		if ($samo_izracunaj_skupini_cas == 2) {
			return $total;
		}
		
		$skupni_cas = (bcdiv($total, 60, 0)>0?bcdiv($total, 60, 0).'min ':'').''.round(bcmod($total, 60), 0).'s';
		
		if ($samo_izracunaj_skupini_cas == 1)
			return $skupni_cas;
		
		
		echo '<div class="clr"></div>';
		
		echo '<fieldset><legend>'.$lang['srv_total_trajanje'].'</legend>';
		echo '<p>'.$lang['srv_dejansko_trajanje'].': <b>'.$skupni_cas.'</b></p>';
		echo '</fieldset>';
		
		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_casi_po_straneh'].'</legend>';
		echo '<table style="width:100%">';
		
		foreach ($expected_time AS $vrstni_red => $time) {
			$sql = sisplet_query("SELECT naslov FROM srv_grupa WHERE vrstni_red='$vrstni_red' AND ank_id = '$this->anketa'");
			$row = mysqli_fetch_array($sql);
			
			echo '<tr>';
			echo '<th style="text-align:left; padding: 0 20px 0 0" nowrap>'.($row['naslov']!=''?$row['naslov']:$lang['srv_intro_label']).'</th>';
			
			echo '<td style="width:100%">';
			echo '  <div class="graph_db" style="text-align: right; float: left; width: '.($time[0]/$max*85).'%">&nbsp;</div>';
			if ((($time[1]-$time[0])/$max*85) > 0)
				echo '  <div class="graph_lb" style="border-left: 0; text-align: right; float: left; width: '.(($time[1]-$time[0])/$max*85).'%">&nbsp;</div>';
			echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.($time[0]<60?round($time[0],1).'s ':round($time[0]/60,1).'min ').'<span style="color:gray">/ '.($time[1]<60?round($time[1],1).'s ':round($time[1]/60,1).'min ').'</span></span>';
			echo '</td>';
			
			echo '</tr>';
		}
		echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
		echo '<tr><td></td><th style="text-align:left; padding-right: 20px" nowrap>'.$lang['srv_anl_suma1'].': '.(bcdiv($total, 60, 0)>0?bcdiv($total, 60, 0).'min ':'').''.round(bcmod($total, 60), 0).'s</th></tr>';
                
		echo '</table>';
		
		echo '<p><div class="graph_db" style="float: left; width: 11px">&nbsp;</div><span style="float:left; margin:0 10px 0 5px"> - '.$lang['srv_neto_t_cas'].'</span>';
		echo '<div class="graph_lb" style="float: left; width: 11px">&nbsp;</div><span style="float:left; margin:0 10px 0 5px"> - '.$lang['srv_bruto_t_cas'].'</span></p>';
		
		echo '</fieldset>';
				
		// izpis za vprasanja
		$max = 0;
		$bruto_total = 0;
		$neto_total = 0;
		foreach ($expected_vprasanja AS $vpr) {
			if ($vpr[0] > $max) $max = $vpr[0];
			$bruto_total += $vpr[0];
			$neto_total += $vpr[0] * $vpr[1];
		}
		
		$prevstran = false;
		
		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_casi_po_vprasanjih_strani'].'</legend>';
		echo '<table style="width:100%">';
		echo '<tr><td></td><th>'.$lang['srv_bruto_v_cas'].'</th><th>'.$lang['srv_verjetnost_pojavitve'].'</th><th>'.$lang['srv_neto_v_cas'].'</th></tr>';
		foreach ($expected_vprasanja AS $vprasanje) {
			
			if (!$prevstran || $prevstran != $vprasanje[3]) {
				echo '<tr><th style="text-align:left; border-bottom:1px solid #E4E4F9; padding-top:10px" colspan="5">'.$vprasanje[3].'</th></tr>';
				$prevstran = $vprasanje[3];
			}
			
			$bruto = $vprasanje[0];
			$verjetnost = $vprasanje[1];
			$neto = $bruto * $verjetnost;
			
			echo '<tr><td align="left"><span title="'.$vprasanje[2].'">'.skrajsaj($vprasanje[2], 30).'</span></td><td align="center">'.round($bruto, 1).'s</td><td align="center">'.round($verjetnost*100, 2).'%</td><td align="center">'.round($neto, 1).'s</td>';
			echo '<td style="width:50%">';
			echo '  <div class="graph_db" style="text-align: right; float: left; width: '.($neto/$max*85).'%">&nbsp;</div>';
			if (($bruto-$neto)/$max*85 > 0)
				echo '  <div class="graph_lb" style="border-left:0; text-align: right; float: left; width: '.(($bruto-$neto)/$max*85).'%">&nbsp;</div>';
			echo '  <span style="display: block; margin: auto auto auto 5px; float: left; color: gray">'.round($neto, 1).'s / '.round($bruto, 1).'s</span>';
			echo '</td>';
			echo '</tr>';
			
		}
		echo '<tr><th></th><th>'.(bcdiv($bruto_total, 60, 0)>0?bcdiv($bruto_total, 60, 0).'min ':'').''.round(bcmod($bruto_total, 60), 0).'s</th><th></th><th>'.(bcdiv($neto_total, 60, 0)>0?bcdiv($neto_total, 60, 0).'min ':'').''.round(bcmod($neto_total, 60), 0).'s</th><tr>';
		echo '</table>';
		echo '</fieldset>';
                
                //CASI PO BLOKIH
                if($block_spr_data){
                    // izpis za bloke
                    $maxb = 0;
                    $totalb = 0;
                    foreach ($expected_time_block AS $key => $val) {
                            if ($val[1] > $maxb) $maxb = $val[1];
                            $totalb += $val[0];
                    }
                
                    echo '<br />';
                    echo '<fieldset><legend>'.$lang['srv_casi_po_blokih'].'</legend>';
                    echo '<table style="width:100%">';

                    foreach ($expected_time_block AS $vrstni_red => $time) {
                            echo '<tr>';
                            echo '<th style="text-align:left; padding: 0 20px 0 0" nowrap>'.$block_labels_by_number[$vrstni_red+1].'</th>';

                            echo '<td style="width:100%">';
                            echo '  <div class="graph_db" style="text-align: right; float: left; width: '.($time[0]/$maxb*85).'%">&nbsp;</div>';
                            if ((($time[1]-$time[0])/$maxb*85) > 0)
                                    echo '  <div class="graph_lb" style="border-left: 0; text-align: right; float: left; width: '.(($time[1]-$time[0])/$maxb*85).'%">&nbsp;</div>';
                            echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.($time[0]<60?round($time[0],1).'s ':round($time[0]/60,1).'min ').'<span style="color:gray">/ '.($time[1]<60?round($time[1],1).'s ':round($time[1]/60,1).'min ').'</span></span>';
                            echo '</td>';

                            echo '</tr>';
                    }
                    echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
                    echo '<tr><td></td><th style="text-align:left; padding-right: 20px" nowrap>'.$lang['srv_anl_suma1'].': '.(bcdiv($totalb, 60, 0)>0?bcdiv($totalb, 60, 0).'min ':'').''.round(bcmod($totalb, 60), 0).'s</th></tr>';

                    echo '</table>';

                    echo '<p><div class="graph_db" style="float: left; width: 11px">&nbsp;</div><span style="float:left; margin:0 10px 0 5px"> - '.$lang['srv_neto_t_cas'].'</span>';
                    echo '<div class="graph_lb" style="float: left; width: 11px">&nbsp;</div><span style="float:left; margin:0 10px 0 5px"> - '.$lang['srv_bruto_t_cas'].'</span></p>';

                    echo '</fieldset>';
                
                
                    // izpis za vprasanja po blokih
                    $max = 0;
                    $bruto_total = 0;
                    $neto_total = 0;
                    foreach ($expected_vprasanja AS $vpr) {
                        if($vpr[4]){
                            if ($vpr[0] > $max) $max = $vpr[0];
                            $bruto_total += $vpr[0];
                            $neto_total += $vpr[0] * $vpr[1];
                        }
                    }

                    $prevstran = false;

                    echo '<br />';

                    echo '<fieldset><legend>'.$lang['srv_casi_po_vprasanjih_bloki'].'</legend>';
                    echo '<table style="width:100%">';
                    echo '<tr><td></td><th>'.$lang['srv_bruto_v_cas'].'</th><th>'.$lang['srv_verjetnost_pojavitve'].'</th><th>'.$lang['srv_neto_v_cas'].'</th></tr>';
                    foreach ($expected_vprasanja AS $vprasanje) {
                        if($vprasanje[4]){
                            if (!$prevstran || $prevstran != $vprasanje[4]) {
                                    echo '<tr><th style="text-align:left; border-bottom:1px solid #E4E4F9; padding-top:10px" colspan="5">'.$vprasanje[4].'</th></tr>';
                                    $prevstran = $vprasanje[4];
                            }

                            $bruto = $vprasanje[0];
                            $verjetnost = $vprasanje[1];
                            $neto = $bruto * $verjetnost;

                            echo '<tr><td align="left"><span title="'.$vprasanje[2].'">'.skrajsaj($vprasanje[2], 30).'</span></td><td align="center">'.round($bruto, 1).'s</td><td align="center">'.round($verjetnost*100, 2).'%</td><td align="center">'.round($neto, 1).'s</td>';
                            echo '<td style="width:50%">';
                            echo '  <div class="graph_db" style="text-align: right; float: left; width: '.($neto/$max*85).'%">&nbsp;</div>';
                            if (($bruto-$neto)/$max*85 > 0)
                                    echo '  <div class="graph_lb" style="border-left:0; text-align: right; float: left; width: '.(($bruto-$neto)/$max*85).'%">&nbsp;</div>';
                            echo '  <span style="display: block; margin: auto auto auto 5px; float: left; color: gray">'.round($neto, 1).'s / '.round($bruto, 1).'s</span>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '<tr><th></th><th>'.(bcdiv($bruto_total, 60, 0)>0?bcdiv($bruto_total, 60, 0).'min ':'').''.round(bcmod($bruto_total, 60), 0).'s</th><th></th><th>'.(bcdiv($neto_total, 60, 0)>0?bcdiv($neto_total, 60, 0).'min ':'').''.round(bcmod($neto_total, 60), 0).'s</th><tr>';
                    echo '</table>';
                    echo '</fieldset>';
                }
	}
        
        /**
         * Dobi podatke o bloku za vsako spremenljivko, ali false, ce ni blokov
         */
        function get_block_data_by_spr_id(){
            global $lang;
            $data = array();
            $block_query = sisplet_query("SELECT * FROM srv_if as bl LEFT JOIN srv_branching as br ON br.parent = bl.id  WHERE bl.enabled='0' AND bl.tip='1' AND br.ank_id = '$this->anketa' ORDER BY br.vrstni_red ASC", 'array');
            if($block_query){
                foreach ($block_query as $row) {
                    $label = $row['label'] ? $row['label']: $lang['srv_blok'].' '.$row['number'];
                    $data[$row['element_spr']] = array('label' => $label);
                }
                return $data;
            }
            return false;
        }
	
	/**
	* oceni predvideni cas za vprasanje
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_predvideni_cas ($spremenljivka) {
		
		$sql1 = sisplet_query("SELECT id, naslov, tip FROM srv_spremenljivka WHERE id = '$spremenljivka'");
		$row1 = mysqli_fetch_array($sql1);
		
		$expected_time = strlen(strip_tags($row1['naslov'])) * GlobalMisc::getMisc('timing_vprasanje_'.$row1['tip']) / 100;
		
		// vprasanja, ki imajo tudi kategorije/vrednosti
		if ($row1['tip'] <= 3 || $row1['tip'] == 6 || $row1['tip'] == 16 || $row1['tip'] == 17 || $row1['tip'] == 18 || $row1['tip'] == 19 || $row1['tip'] == 20) {

			$sql2 = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='$row1[id]'");
                        //for those types we have max time option
                        if($row1['tip'] == 3){
                            while ($row2 = mysqli_fetch_array($sql2)) {	
                                    $expected_time_temp += strlen(strip_tags($row2['naslov'])) * GlobalMisc::getMisc('timing_kategorija_'.$row1['tip']) / 100;
                            }
                            //if time is greater than max time, use max time
                            $max_time = GlobalMisc::getMisc('timing_kategorija_max_'.$row1['tip']);
                            $expected_time += ($max_time > $expected_time_temp) ? $expected_time_temp : $max_time;
                        }
                        //types that doesnt have max time option
                        else{
                            while ($row2 = mysqli_fetch_array($sql2)) {	
                                $expected_time += strlen(strip_tags($row2['naslov'])) * GlobalMisc::getMisc('timing_kategorija_'.$row1['tip']) / 100;
                            }
                        }
		}
		
		return $expected_time;	
	}
	
	/**
	* oceni verjetnost prikaza vprasanja glede na pogoje, ki so mu nastavljeni
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_verjetnost ($spremenljivka) {
		
		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$spremenljivka'");
		$row = mysqli_fetch_array($sql);
		
		if ($row['parent'] == 0) return 1;	// vprasanje se vedno prikaze
		
		//echo $this->if_verjetnost($row['parent']).'<hr>';
		return $this->if_verjetnost($row['parent']);	
	}
	
	/**
	* oceni verjetnost da bo pogoj (if) izpolnjen
	* 
	* @param mixed $if
	*/
	function if_verjetnost ($if) {
		
		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_if = '$if'");
		if (mysqli_num_rows($sql) == 0) return 0;
		$row = mysqli_fetch_array($sql);
		
		// izracunamo se verjetnost parentov
		if ($row['parent'] > 0){
			
			// dodaten pogoj da nismo v deadlocku (zaradi bugov se znata v branchingu pojavit ifa, ki imata drug drugega za parenta)
			$sqlX = sisplet_query("SELECT parent, element_if FROM srv_branching WHERE parent='".$if."' AND element_if='".$row['parent']."'");
			if(mysqli_num_rows($sqlX) > 0){	
				return 0;
			}
			
			$parent = $this->if_verjetnost($row['parent']);
		}
		else
			$parent = 1;
		
		$sql = sisplet_query("SELECT tip FROM srv_if WHERE id = '$if'");
		$row = mysqli_fetch_array($sql);
		
		if ($row['tip'] == 1) return 1 * $parent;	// blok je vedno 'izpolnjen'
		
		$eval = ' $total = ';
		$i = 0;
		// racunanje verjetnosti za podani if
		$sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if' ORDER BY vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {
			
			$value = '';
			if(($value = $this->condition_verjetnost($row['id'])) !== false){
				
				if ($i++ != 0){
	                if ($row['conjunction'] == 0)
	                    $eval .= ' * ';
	                else
	                    $eval .= ' + ';
				}
				
	            for ($i=1; $i<=$row['left_bracket']; $i++)
	                $eval .= ' ( ';

	            $eval .= $value;

	            for ($i=1; $i<=$row['right_bracket']; $i++)
	                $eval .= ' ) ';
			}
		}
		$eval .= ';';

		if($eval != ' $total = ;')
			@eval($eval); //echo '--'.$eval.'--';
		else
			$total = 1;
			
		if ($total > 1) return 1 * $parent; else return $total * $parent;
	}
	
	/**
	* vrne verjetnost, da je izpolnjen condition (ena vrstica v IFu)
	* 
	* @param mixed $condition
	*/
	function condition_verjetnost ($condition) {
		
		$sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
		if (mysqli_num_rows($sql) == 0) return 0;
		$row = mysqli_fetch_array($sql);
		
		// obicne spremenljivke
		if ($row['spr_id'] > 0) {
            $row2 = Cache::srv_spremenljivka($row['spr_id']);
            
            // radio, checkbox, dropdown in multigrid
            if ($row2['tip'] <= 3 || $row2['tip'] == 6) {
                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                	
					$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[spr_id]'");
					$vse_vrednosti = mysqli_num_rows($sql1);

					$sql1 = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id = '$condition'");
					$izbrane_vrednosti = mysqli_num_rows($sql1);
					
   					if ($vse_vrednosti > 0)
                   		$p = $izbrane_vrednosti / $vse_vrednosti;
                   	else
                   		$p = 0;
                        
                    if ($row['operator'] == 0)
                    	return $p;
                    else
                    	return 1 - $p;
                    	
                // multigrid
                } elseif ($row['vre_id'] > 0) {
					
					$sql1 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='$row[spr_id]'");
					$vse_vrednosti = mysqli_num_rows($sql1);

					$sql1 = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id = '$condition'");
					$izbrane_vrednosti = mysqli_num_rows($sql1);
					
   					if ($vse_vrednosti > 0)
                   		$p = $izbrane_vrednosti / $vse_vrednosti;
                   	else
                   		$p = 0;
					
					if ($row['operator'] == 0)
                    	return $p;
                    else
                    	return 1 - $p;
                }

            // number in text
            } else {
				return 0.5;
            }
		
		// recnum
        } elseif ($row['spr_id'] == -1) {

            return 1 / $row['modul'];
            
        // calculations
        } elseif ($row['spr_id'] == -2) {

            return 0.5;
			
        // quotas
        } elseif ($row['spr_id'] == -3) {

            return 1;
        }
		
		return false;
	}
		
	/**
	* prikazuje povprecne case po straneh ipd....
	* 
	*/
	function testiranje_cas($samo_izracunaj_skupini_cas=0) {
		global $lang;
		global $global_user_id;
		global $admin_type;
		
		SurveyUserSetting :: getInstance()->Init($this->anketa, $global_user_id);

		// nastavitve iz popupa
		$rezanje = SurveyUserSetting::getInstance()->getSettings('rezanje');	if ($rezanje == '') $rezanje = 1;
		$rezanje_meja_sp = SurveyUserSetting::getInstance()->getSettings('rezanje_meja_sp');	if ($rezanje_meja_sp == '') $rezanje_meja_sp = 5;
		$rezanje_meja_zg = SurveyUserSetting::getInstance()->getSettings('rezanje_meja_zg');	if ($rezanje_meja_zg == '') $rezanje_meja_zg = 5;
		$rezanje_predvidena_sp = SurveyUserSetting::getInstance()->getSettings('rezanje_predvidena_sp');	if ($rezanje_predvidena_sp == '') $rezanje_predvidena_sp = 10;
		$rezanje_predvidena_zg = SurveyUserSetting::getInstance()->getSettings('rezanje_predvidena_zg');	if ($rezanje_predvidena_zg == '') $rezanje_predvidena_zg = 200;
		$rezanje_preskocene = SurveyUserSetting::getInstance()->getSettings('rezanje_preskocene');	if ($rezanje_preskocene == '') $rezanje_preskocene = 1;
		
		/* ++ Predvideni casi    */
		if ($_GET['predvideni'] == 1 || $rezanje == 1) {
			$expected_time = array();
			$expected_vprasanja = array();
			$verjetnost = array();
			
			$sql = sisplet_query("SELECT introduction FROM srv_anketa WHERE id = '$this->anketa'");
			$row = mysqli_fetch_array($sql);
		
			// nagovor racunamo kot da gre za labelo
			$expected_vprasanja[0][0] = strlen(strip_tags($row['introduction'])) * GlobalMisc::getMisc('timing_vprasanje_5') / 100;
			$expected_vprasanja[0][1] = 1;
			$expected_vprasanja[0][2] = $lang['srv_vprasanje_tip_5'];
			$expected_vprasanja[0][3] = $lang['srv_intro_label'];
			
			$expected_time[0][0] = $expected_vprasanja[0][0] + GlobalMisc::getMisc('timing_stran');
			$expected_time[0][1] = $expected_time[0][0];
			
			
			$sql = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grupa g WHERE g.ank_id='$this->anketa' ORDER BY g.vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {
				
				$expected_time[$row['vrstni_red']][0] = 0;
				$expected_time[$row['vrstni_red']][1] = 0;
				
				$sql1 = sisplet_query("SELECT id, naslov FROM srv_spremenljivka WHERE gru_id='$row[id]' AND visible='1'");
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					$expected_vprasanja[$row1['id']][0] = $this->vprasanje_predvideni_cas($row1['id']);
					$expected_vprasanja[$row1['id']][1] = $this->vprasanje_verjetnost($row1['id']);
					$expected_vprasanja[$row1['id']][2] = strip_tags($row1['naslov']);
					$expected_vprasanja[$row1['id']][3] = strip_tags($row['naslov']);
					
					$expected_time[$row['vrstni_red']][0] += $expected_vprasanja[$row1['id']][0] * $expected_vprasanja[$row1['id']][1];	// dejanski
					$expected_time[$row['vrstni_red']][1] += $expected_vprasanja[$row1['id']][0];		// bruto - z vsemi vprasanji
					
					if ( $expected_vprasanja[$row1['id']][1] > $verjetnost[$row['vrstni_red']])
						$verjetnost[$row['vrstni_red']] = $expected_vprasanja[$row1['id']][1];
				}
				
				$expected_time[$row['vrstni_red']][0] += GlobalMisc::getMisc('timing_stran') * $verjetnost[$row['vrstni_red']];		// pri dejanskem trajanju strani upostevamo verjetnost najverjetnejsega vprasanja na strani (stran se pojavi z najvisjo verjetnostjo vseh vprasanj na strani)
				$expected_time[$row['vrstni_red']][1] += GlobalMisc::getMisc('timing_stran');
				
			}
		}
		/* -- Predvideni casi    */
		
		// statusi		
		SurveyStatusCasi :: Init($this->anketa);
        $izbranStatusCasi = SurveyStatusCasi :: getCurentProfileId();
		$statusArray = SurveyStatusCasi::getStatusArray($izbranStatusCasi);
		
		$status = '';
		foreach ($statusArray AS $key => $val) {
			if ($key == 'statusnull' && $val == 1) $status .= ($status!=''?',':'') . '-1';
			if ($key == 'status0'    && $val == 1) $status .= ($status!=''?',':'') . '0';
			if ($key == 'status1'    && $val == 1) $status .= ($status!=''?',':'') . '1';
			if ($key == 'status2'    && $val == 1) $status .= ($status!=''?',':'') . '2';
			if ($key == 'status3'    && $val == 1) $status .= ($status!=''?',':'') . '3';
			if ($key == 'status4'    && $val == 1) $status .= ($status!=''?',':'') . '4';
			if ($key == 'status5'    && $val == 1) $status .= ($status!=''?',':'') . '5';
			if ($key == 'status6'    && $val == 1) $status .= ($status!=''?',':'') . '6';
			if ($key == 'statuslurker' && $val == 1) $lurker = ""; else $lurker = " AND lurker='0' ";
		}
		
		
        
		// preberemo vse timestampe za strani v anketi
		$sql = sisplet_query("SELECT ug.usr_id, UNIX_TIMESTAMP(ug.time_edit) AS time_edit_u, g.vrstni_red FROM srv_user_grupa".$this->db_table." ug, srv_grupa g, srv_user u WHERE ug.usr_id=u.id AND u.last_status IN ($status) $lurker AND ug.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY usr_id, gru_id");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) == 0) {
			if ($samo_izracunaj_skupini_cas == 1) {
				return null;
			} else {
				echo '<div style="padding: 5px;" class="clr">'.$lang['srv_analiza_no_entry'].'</div>';
			}
		}
		$user_grupa = array();
		while ($row = mysqli_fetch_array($sql)) {
			
			$user_grupa[$row['usr_id']][$row['vrstni_red']] = $row['time_edit_u'];
			
		}
		
		if (mysqli_num_rows($sql) == 0) {
			die();
		}
		
		
		// cache mysql-a
		$usrs_id = array();
		foreach ($user_grupa AS $usr_id => $val) {
			$usrs_id[] = $usr_id;
		}
		
		$cache_row = array();
		$sql_c = sisplet_query("SELECT id, recnum, time_insert, UNIX_TIMESTAMP(time_insert) AS time_insert_u FROM srv_user WHERE id IN (".implode(',', $usrs_id).")");
		if (!$sql_c) echo mysqli_error($GLOBALS['connect_db']);
		while ($row_c = mysqli_fetch_array($sql_c)) {
			$cache_row[ $row_c['id'] ] = $row_c;
		}
		
		$cache_row1 = array();
		$sql1_c = sisplet_query("SELECT usr_id, time_edit, UNIX_TIMESTAMP(time_edit) AS time_edit_u FROM srv_user_grupa".$this->db_table." WHERE usr_id IN (".implode(',', $usrs_id).") AND gru_id = '0'");
		if (!$sql1_c) echo mysqli_error($GLOBALS['connect_db']);
		while ($row1_c = mysqli_fetch_array($sql1_c)) {
			$cache_row1[ $row1_c['usr_id'] ] = $row1_c;
		}
		
		// izracunamo razlike v casih, da dobimo za vsakega userja koliko casa je bil na posamezni strani
		$casi = array();
		foreach ($user_grupa AS $usr_id => $val) {
			
			//$sql = sisplet_query("SELECT recnum, time_insert, UNIX_TIMESTAMP(time_insert) AS time_insert_u FROM srv_user WHERE id = '$usr_id'");
			//$row = mysqli_fetch_array($sql);
			$row = $cache_row[$usr_id];
			
			//$sql1 = sisplet_query("SELECT time_edit, UNIX_TIMESTAMP(time_edit) AS time_edit_u FROM srv_user_grupa".$this->db_table." WHERE usr_id = '$usr_id' AND gru_id = '0'");
			//$row1 = mysqli_fetch_array($sql1);
			$row1 = $cache_row1[$usr_id];
			
			//echo $row1['time_edit'].' ('.($row1['time_edit_u'] - strtotime($row1['time_edit'])).') - '.$row['time_insert'].' ('.($row['time_insert_u'] - strtotime($row['time_insert'])).')<br>';
			$prev = ($row1['time_edit'] != '' ? $row1['time_edit_u'] : $row['time_insert_u']);
			
			// nagovor
			//if ($row1['time_edit'] != '') $casi[0][$usr_id] = $this->diff($row1['time_edit'], $row['time_insert']);
			if ($row1['time_edit'] != '') $casi[0][$usr_id] = abs($row1['time_edit_u'] - $row['time_insert_u']);
			
			if ($row['recnum'] > 0) {	// zapisi brez recnuma ne pridejo v poštev, ker nimajo pravih časov
				foreach ($val AS $vrstni_red => $time_edit) {
					
					//$casi[$vrstni_red][$usr_id] = $this->diff($time_edit, $prev);
					$casi[$vrstni_red][$usr_id] = abs($time_edit - $prev);
					
					$prev = $time_edit;
					
				}
			}
		}
		
		// porezemo zgornjih in spodnjih 5% casov vsake strani
		//if (isset($_GET['truncate'])) $truncate = ((int)$_GET['truncate'])/100; else $truncate = 0.05;
		$spodnja = $rezanje_meja_sp / 100;
		$zgornja = $rezanje_meja_zg / 100;
		
		// REZANJE
		foreach ($casi AS $vrstni_red => $val1) {
			
			asort($casi[$vrstni_red]);
			
			$len = count($casi[$vrstni_red]);
			$odrezi_sp = (int) round ( $len * $spodnja , 0);
			$odrezi_zg = (int) round ( $len * $zgornja , 0);
			
			$i = 1;
			foreach ($casi[$vrstni_red] AS $key => $val2) {
				
				if ($rezanje == 0) {	// rezanje po zgornji in spodnji meji
					if ($i <= $odrezi_sp || $i > $len-$odrezi_zg) {
						unset($casi[$vrstni_red][$key]);
					}
				
				} else {				// rezanje glede na 10% in 200% predvidenih vrednosti
					if ($val2 < $expected_time[$vrstni_red][0]*$rezanje_predvidena_sp/100 || $val2 > $expected_time[$vrstni_red][0]*$rezanje_predvidena_zg/100) {
						unset($casi[$vrstni_red][$key]);
					}
				}
				
				$i++;
			}

		}
		
		//foreach ($casi AS $key => $val) { echo $key.': '; foreach ($val AS $k => $v) { echo $v.', '; } echo '<br>'; }
		
		
		// izracunamo povprecne case
		$sql = sisplet_query("SELECT MAX(vrstni_red) AS max FROM srv_grupa WHERE ank_id = '$this->anketa'");
		$row = mysqli_fetch_array($sql);
		
		$count = array();
		$count_bruto = array();
		$povprecni_casi = array();
		$povprecni_casi_bruto = array();
		$max_time = 0;
		for ($i=0; $i<=$row['max']; $i++) $povprecni_casi[$i] = 0;
		foreach ($casi AS $vrstni_red => $val) {
			
			// pogledamo za preskocene strani
			$preskocene = array();
			if ($rezanje_preskocene == 0) {
				$sqlp = sisplet_query("SELECT ug.usr_id FROM srv_user_grupa".$this->db_table." ug, srv_grupa g WHERE g.id=ug.gru_id AND g.vrstni_red='$vrstni_red' AND ug.preskocena='1'");
				while ($rowp = mysqli_fetch_array($sqlp)) {
					array_push($preskocene, $rowp['usr_id']);
				}
			}
			
			foreach ($casi[$vrstni_red] AS $usr_id => $time) {
				if (!in_array($usr_id, $preskocene)) {
					$povprecni_casi_bruto[$vrstni_red] += $time;			// bruto so kao brez upoštevanja strani ki so se preskocile (0s, 1s)
					$count_bruto[$vrstni_red] ++;							// to je dejansko trajanje strani, ce uporabnik pride nanjo 
				}
				if (!in_array($usr_id, $preskocene) || $rezanje_preskocene==1) {
					$povprecni_casi[$vrstni_red] += $time;						// neto je kao povprecno trajanje strani in uposteva tudi 0s, 1s ce se je preskocilo
					$count[$vrstni_red] ++;										// ta cas pride potem dejansko krajsi od bruto casa
				}
				if ($time > $max_time) $max_time = $time;
			}
		}
		
		foreach ($povprecni_casi AS $vrstni_red => $time) {
			if ($count[$vrstni_red] > 0)
				$povprecni_casi[$vrstni_red] = $time / $count[$vrstni_red];
		}
		
		foreach ($povprecni_casi_bruto AS $vrstni_red => $time) {
			if ($count_bruto[$vrstni_red] > 0)
				$povprecni_casi_bruto[$vrstni_red] = $time / $count_bruto[$vrstni_red];
		}
		
		$max = 0;
		$total = 0;
		$total_predvideni = 0;
		foreach ($povprecni_casi AS $key => $val) {
			if ($val > $max) $max = $val;
			$total += $val;
		}
		/*foreach ($povprecni_casi AS $key => $val) {
			if ($val > $max) $max = $val;
			//$total += $val;
		}*/
		if ($_GET['predvideni'] == 1) {
			if ($rezanje_preskocene == 1) {
				foreach ($expected_time AS $key => $val) {
					if ($val[0] > $max) $max = $val[0];
					$total_predvideni += $val[0];
				}
			} else {
				foreach ($expected_time AS $key => $val) {
					if ($val[1] > $max) $max = $val[1];
					$total_predvideni += $val[1];
				}
			}
		}
		
		if ($max == 0) return;
		
		if ($samo_izracunaj_skupini_cas == 1)
			return (bcdiv($total, 60, 0)>0?bcdiv($total, 60, 0).'min ':'').''.round(bcmod($total, 60), 0).'s';
		
		
		// izpis
		echo '<div class="clr"></div>';
		echo '<fieldset><legend>'.$lang['srv_dejanski_casi'].'</legend>';
		echo '<table style="width:100%" >';
		echo '<tr><td></td><td>';
		echo '<input type="checkbox" name="predvideni" id="predvideni" value="1" onclick="vnos_redirect(\'index.php?anketa='.$this->anketa.'&a=testiranje&m=cas&predvideni='.($_GET['predvideni']==1?'0':'1').'&pages='.$_GET['pages'].'&prikazi01='.$_GET['prikazi01'].'\');" '.($_GET['predvideni']==1?'checked':'').' /><label for="predvideni">'.$lang['srv_vkljuci_predvidene'].'</label>';
		echo '</td><td nowrap>'.$lang['srv_stevilo_enot'].'</td></tr>';
		
		foreach ($povprecni_casi AS $vrstni_red => $time) {
			
			$sql = sisplet_query("SELECT naslov FROM srv_grupa WHERE vrstni_red='$vrstni_red' AND ank_id = '$this->anketa'");
			$row = mysqli_fetch_array($sql);
			
			$bruto = $povprecni_casi_bruto[$vrstni_red];
			
			echo '<tr>';
			echo '<th style="text-align:left; padding-right:20px" nowrap>'.($row['naslov']!=''?$row['naslov']:$lang['srv_intro_label']).'</th>';
			
			echo '<td style="width:100%">';
			echo '  <div class="graph_db" style="text-align: right; float: left; width: '.($time/$max*85).'%">&nbsp;</div>';
			//if ($bruto-$time > 0)
			//echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.(($bruto-$time)/$max*85).'%; border-left:0px">&nbsp;</div>';
			echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.($time<60?round($time,1).'s ':round($time/60,1).'min ')./*'<span style="color:gray">/ '.($bruto<60?round($bruto,1).'s ':round($bruto/60,1).'min ').'</span>'.*/'</span>';
			echo '</td>';
			
			echo '<td style="text-align:center" nowrap>'.$count[$vrstni_red]./*' <span style="color:gray">/ '.$count_bruto[$vrstni_red].'</span>'.*/'</td>';
			
			echo '</tr>';
			
			if ($_GET['predvideni'] == 1) {
				if ($rezanje_preskocene == 1)
					$time = $expected_time[$vrstni_red][0];
				else
					$time = $expected_time[$vrstni_red][1];
				echo '<tr>';
				echo '<th style="text-align:left; padding-right: 20px; color:gray" nowrap>'.($row['naslov']!=''?$row['naslov']:$lang['srv_intro_label']).'</th>';
				
				echo '<td style="width:100%">';
				echo '  <div class="graph_'.($rezanje_preskocene==1?'lb':'lr').'" style="text-align: right; float: left; width: '.($time/$max*85).'%">&nbsp;</div>';
				//if ((($time[1]-$time[0])/$max*85) > 0)
				//	echo '  <div class="graph_lb" style="border-left: 0; text-align: right; float: left; width: '.(($time[1]-$time[0])/$max*85).'%">&nbsp;</div>';
				echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.($time<60?round($time,1).'s ':round($time/60,1).'min ')./*'<span style="color:gray">/ '.($time[1]<60?round($time[1],1).'s ':round($time[1]/60,1).'min ').'</span>'.*/'</span>';
				echo '</td>';
				echo '</tr>';
				echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
			}
		}
		if ($_GET['predvideni'] != 1)
			echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
		echo '<tr><td></td><th style="text-align:left">'.$lang['srv_anl_suma1'].': '.(bcdiv($total, 60, 0)>0?bcdiv($total, 60, 0).'min ':'').''.round(bcmod($total, 60), 0).'s';
		if ($_GET['predvideni'] == 1) echo ' / '.$lang['srv_skupaj_predvideni'].': '.(bcdiv($total_predvideni, 60, 0)>0?bcdiv($total_predvideni, 60, 0).'min ':'').''.round(bcmod($total_predvideni, 60), 0).'s';
		echo '</th></tr>';
		
		echo '</table>';
		
		if ($_GET['predvideni'] == 1) {
			echo '<p><div class="graph_db" style="float: left; width: 11px">&nbsp;</div><span style="float:left; margin:0 10px 0 5px"> - '.$lang['srv_neto_t_cas'].'</span>';
			echo '<div class="graph_'.($rezanje_preskocene==1?'lb':'lr').'" style="float: left; width: 11px">&nbsp;</div><span style="float:left; margin:0 10px 0 5px"> - '.$lang['srv_testiranje_predvidenicas'].'</span></p>';
		}
		
		echo '</fieldset>';
		
		if ($max_time > 1000 && $admin_type > 0) return; // pridejo prevelike tabele
		
		if ($_GET['prikazi01'] == 1)
			$t_min = 0;
		else
			$t_min = 2;
		
		// izpis histograma casov za vsako stran
		
		echo '<br /><fieldset><legend>'.$lang['srv_frekvencna_porazdelitev'].'</legend>';
		
		echo '<p>';
		echo ' <input type="checkbox" name="pages" id="pages" value="1" onclick="vnos_redirect(\'index.php?anketa='.$this->anketa.'&a=testiranje&m=cas&predvideni='.$_GET['predvideni'].'&prikazi01='.$_GET['prikazi01'].'&pages='.($_GET['pages']==1?'0':'1').'\');" '.($_GET['pages']==1?'checked':'').' /><label for="pages">'.$lang['srv_show_pages'].'</label>';
		echo '</p>';
		if ($rezanje_preskocene == 1) {
			echo '<p>';
			echo ' <input type="checkbox" name="prikazi01" id="prikazi01" value="1" onclick="vnos_redirect(\'index.php?anketa='.$this->anketa.'&a=testiranje&m=cas&predvideni='.$_GET['predvideni'].'&pages='.$_GET['pages'].'&prikazi01='.($_GET['prikazi01']==1?'0':'1').'\');" '.($_GET['prikazi01']==1?'checked':'').' /><label for="prikazi01">'.$lang['srv_prikazi01'].'</label>';
			echo '</p>';
		}
		
		// zdruzimo vse case po straneh na en graf
		if ($_GET['pages'] != '1') {
			$casi2 = array();
			$casi2[0] = array();
			foreach ($casi AS $key => $val) {
				foreach ($val AS $k => $v) {
					if (isset($casi2[0][$k]))
						$casi2[0][$k] += $v;
					else
						$casi2[0][$k] = $v;
					//array_push($casi2[0], $v);
				}
			}
			$casi = $casi2;
			$max_time = 0;
			foreach ($casi[0] AS $k => $v)
				if ($v > $max_time) $max_time = $v;
		}
		
		$minute = true;
		if ($minute) {	// minute
			foreach ($casi AS $k => $page) {
				foreach ($page AS $key => $val) {
					$casi[$k][$key] = (int) round($val / 60, 0);
				}
			}
			$max_time = (int) round($max_time / 60, 0);
		}
		
		foreach ($casi AS $key => $val) {
			
			if ($_GET['pages'] == '1') {
				$sql = sisplet_query("SELECT naslov FROM srv_grupa WHERE vrstni_red='$key' AND ank_id='$this->anketa'");
				$row = mysqli_fetch_array($sql);
				echo '<h2>'.($row['naslov']!=''?$row['naslov']:$lang['srv_intro_label']).'</h2>';
			}
			
			echo '<table style="width:100%; padding:0; margin: 0"><tr>';
			
			$histogram = array();
			for ($t=0; $t<=$max_time; $t++) $histogram[$t] = 0;
			foreach ($val AS $k => $v) {
				if ($v >= $t_min) $histogram[$v]++;
			}
			$max_stran = 0;
			$max_stran_time = 0;
			foreach ($histogram AS $k => $v) {
				if ($v > $max_stran) $max_stran = $v;
				if ($v > 0) $max_stran_time = $k;
			}
			
			
			if ($max_stran != 0) {
			
				for ($t=$t_min; $t<=$max_time; $t++) {
					
					echo '<td style="vertical-align:bottom; margin:0; padding:0; border:0">';
					
					echo '  <div style="background-color:#D8DFEA; border:1px solid transparent; text-align: right; height: '.($histogram[$t]/$max_stran*150).'px; width: 100%; margin:0; padding:0; min-height:1px" title="'.$t.($minute?'min':'s').': '.$histogram[$t].'"></div>';
					//echo '<span style="display:block; width: 100%; text-align:center">'.$t.'</span>';
					echo '</td>';				
				}		
			}
					
			echo '</tr><tr>';
			
			
			if ($max_time <= 20) {
				for ($t=$t_min; $t<=$max_time; $t++) {
					echo '<td>'.$t.($minute?'min':'s').'</td>';
				}
			} else {
				if ($t_min == 0)
					echo '<td colspan="10">0'.($minute?'min':'s').'</td>';
				else
					echo '<td colspan="8">2'.($minute?'min':'s').'</td>';
				
				for ($t=10; $t<=$max_time; $t+=10) {
					echo '<td colspan="10">'.$t.'</td>';
				}
			}
			
			echo '</tr></table>';
		
		}
		echo '</fieldset>';
		
	}
	
	/**
	* opcije za rezanje
	*/
	function show_rezanje_casi () {
		global $lang;
		global $global_user_id;
		
		SurveyUserSetting :: getInstance()->Init($this->anketa, $global_user_id);

		$rezanje = SurveyUserSetting::getInstance()->getSettings('rezanje');	if ($rezanje == '') $rezanje = 0;
		$rezanje_meja_sp = SurveyUserSetting::getInstance()->getSettings('rezanje_meja_sp');	if ($rezanje_meja_sp == '') $rezanje_meja_sp = 5;
		$rezanje_meja_zg = SurveyUserSetting::getInstance()->getSettings('rezanje_meja_zg');	if ($rezanje_meja_zg == '') $rezanje_meja_zg = 5;
		$rezanje_predvidena_sp = SurveyUserSetting::getInstance()->getSettings('rezanje_predvidena_sp');	if ($rezanje_predvidena_sp == '') $rezanje_predvidena_sp = 10;
		$rezanje_predvidena_zg = SurveyUserSetting::getInstance()->getSettings('rezanje_predvidena_zg');	if ($rezanje_predvidena_zg == '') $rezanje_predvidena_zg = 200;
		$rezanje_preskocene = SurveyUserSetting::getInstance()->getSettings('rezanje_preskocene');	if ($rezanje_preskocene == '') $rezanje_preskocene = 1;
		
		
		echo '<p><input type="radio" name="rezanje" value="0"'.($rezanje==0?' checked':'').'> '.$lang['srv_rezanje_0'].'</p>';
		echo '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$lang['srv_rezanje_meja_sp'].': <select name="rezanje_meja_sp">';
		echo '<option value="0"'.($rezanje_meja_sp==0?' selected':'').'>0%</option>';
		echo '<option value="1"'.($rezanje_meja_sp==1?' selected':'').'>1%</option>';
		echo '<option value="3"'.($rezanje_meja_sp==3?' selected':'').'>3%</option>';
		echo '<option value="5"'.($rezanje_meja_sp==5?' selected':'').'>5%</option>';
		echo '<option value="10"'.($rezanje_meja_sp==10?' selected':'').'>10%</option>';
		echo '<option value="20"'.($rezanje_meja_sp==20?' selected':'').'>20%</option>';
		echo '</select></p>';
		
		echo '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$lang['srv_rezanje_meja_zg'].': <select name="rezanje_meja_zg">';
		echo '<option value="0"'.($rezanje_meja_zg==0?' selected':'').'>0%</option>';
		echo '<option value="1"'.($rezanje_meja_zg==1?' selected':'').'>1%</option>';
		echo '<option value="3"'.($rezanje_meja_zg==3?' selected':'').'>3%</option>';
		echo '<option value="5"'.($rezanje_meja_zg==5?' selected':'').'>5%</option>';
		echo '<option value="10"'.($rezanje_meja_zg==10?' selected':'').'>10%</option>';
		echo '<option value="20"'.($rezanje_meja_zg==20?' selected':'').'>20%</option>';
		echo '</select></p>';
		
		echo '<p><input type="radio" name="rezanje" value="1"'.($rezanje==1?' checked':'').'> '.$lang['srv_rezanje_1'].'</p>';
		echo '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$lang['srv_rezanje_meja_sp'].': <select name="rezanje_predvidena_sp">';
		echo '<option value="0"'.($rezanje_predvidena_sp==0?' selected':'').'>0%</option>';
		echo '<option value="1"'.($rezanje_predvidena_sp==1?' selected':'').'>1%</option>';
		echo '<option value="3"'.($rezanje_predvidena_sp==3?' selected':'').'>3%</option>';
		echo '<option value="5"'.($rezanje_predvidena_sp==5?' selected':'').'>5%</option>';
		echo '<option value="10"'.($rezanje_predvidena_sp==10?' selected':'').'>10%</option>';
		echo '<option value="20"'.($rezanje_predvidena_sp==20?' selected':'').'>20%</option>';
		echo '</select> '.$lang['srv_rezanje_predvidenega'].'</p>';
		
		echo '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$lang['srv_rezanje_meja_zg'].': <select name="rezanje_predvidena_zg">';
		echo '<option value="100"'.($rezanje_predvidena_zg==100?' selected':'').'>100%</option>';
		echo '<option value="150"'.($rezanje_predvidena_zg==150?' selected':'').'>150%</option>';
		echo '<option value="200"'.($rezanje_predvidena_zg==200?' selected':'').'>200%</option>';
		echo '<option value="300"'.($rezanje_predvidena_zg==300?' selected':'').'>300%</option>';
		echo '<option value="500"'.($rezanje_predvidena_zg==500?' selected':'').'>500%</option>';
		echo '<option value="1000"'.($rezanje_predvidena_zg==1000?' selected':'').'>1000%</option>';
		echo '</select> '.$lang['srv_rezanje_predvidenega'].'</p>';
		
		echo '<p>&nbsp;</p>';
		echo '<p><input type="checkbox" name="rezanje_preskocene" value="1"'.($rezanje_preskocene==1?' checked':'').' onchange="javascript: if (this.checked == 1) { $(\'#preskocene_txt\').hide(); } else { $(\'#preskocene_txt\').show(); }"> '.$lang['srv_rezanje_preskocene'].'</p>';
		echo '<p id="preskocene_txt" '.($rezanje_preskocene==1?'style="display:none"':'').'>'.$lang['srv_rezanje_preskocene_txt'].'</p>';
		
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="statusCasiAction(\'run_rezanje\'); return false;"><span>'.$lang['srv_save_run_profile'].'</span></a></span></span>';
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="statusCasiAction(\'cancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';
		
	}
	
	/**
	* shrani nastavitve
	* 
	*/
	function save_rezanje_casi () {
		global $global_user_id;
		
		SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);

		SurveyUserSetting::getInstance()->saveSettings('rezanje', $_POST['rezanje']);
		SurveyUserSetting::getInstance()->saveSettings('rezanje_meja_sp', $_POST['rezanje_meja_sp']);
		SurveyUserSetting::getInstance()->saveSettings('rezanje_meja_zg', $_POST['rezanje_meja_zg']);
		SurveyUserSetting::getInstance()->saveSettings('rezanje_predvidena_sp', $_POST['rezanje_predvidena_sp']);
		SurveyUserSetting::getInstance()->saveSettings('rezanje_predvidena_zg', $_POST['rezanje_predvidena_zg']);
		SurveyUserSetting::getInstance()->saveSettings('rezanje_preskocene', $_POST['rezanje_preskocene']);
		
	}
	
	function testiranje_komentarji_links($comment_count){
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;
		
		
		if($_GET['a'] == 'komentarji_anketa'){
			
			// Gumb nazaj
			echo '<div class="switch_button '.($_GET['a'] == A_KOMENTARJI ? ' active' : '').'">';
			echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a='.A_KOMENTARJI.'" title="' . $lang['srv_testiranje_komentarji_title'] . '">' . $lang['srv_q_comments_back'] . '</a>';
			echo '</div>';
			
			echo '<span class="bold"> (';
			if($comment_count['question']['unresolved'] > 0) 
				echo '<span class="orange">';
			echo $comment_count['question']['unresolved'];
			if($comment_count['question']['unresolved'] > 0) 
				echo '</span>';
			echo '/'.$comment_count['question']['all'];
			echo ')</span>';
		}
		else{

			echo '<span id="comment_question_note">';
			
			echo $lang['srv_komentarji_imate'].' ';
			if($comment_count['question']['unresolved'] > 0) echo '<span class="red">';
			echo $this->string_format((int)$comment_count['question']['unresolved'], 'srv_cnt_komentarji');			
			if($comment_count['question']['unresolved'] > 0) echo '</span>';
			
			echo ' '.$lang['srv_komentarji_odskupno'].' ';
			echo $this->string_format((int)$comment_count['question']['all'], 'srv_cnt_komentar_na_vprs');
			
			echo '</span>';
			
			
			// Gumb splosni komentarji
			echo '<div class="switch_button '.($_GET['a'] == A_KOMENTARJI_ANKETA ? ' active' : '').'">';
			echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a='.A_KOMENTARJI_ANKETA.'" title="' . $lang['srv_testiranje_komentarji_anketa_title'] . '">' . $lang['srv_testiranje_komentarji_anketa_title'] . '</a>';
			echo '</div>';		
			
			echo '<span class="bold"> (';
			if($comment_count['survey_resp']['unresolved']+$comment_count['survey_admin']['unresolved'] > 0) 
				echo '<span class="orange">';
			echo ($comment_count['survey_resp']['unresolved']+$comment_count['survey_admin']['unresolved']);
			if($comment_count['survey_resp']['unresolved']+$comment_count['survey_admin']['unresolved'] > 0) 
				echo '</span>';
			echo '/'.($comment_count['survey_resp']['all']+$comment_count['survey_admin']['all']);
			echo ')</span>';		
		}
	}
	
	function string_format($cnt,$lang_root) {
		global $lang;
		
		$txt = '';
		//if ($cnt > 0) $txt .= '<span class="red">';
		
		if (isset($lang[$lang_root.'_'.$cnt])) {
			$txt .= $cnt.' '.$lang[$lang_root.'_'.$cnt];
		} else {
			$txt .= $cnt.' '.$lang[$lang_root.'_more'];
		}
		
		//if ($cnt > 0) $txt .= '</span>';
		
		return $txt;
	}
	
	/**
	* izpise komentarje na anketo - stara ki se ne uporablja
	* 
	*/
	function testiranje_komentarji_anketa_old () {
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;
		
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		
		SurveySetting::getInstance()->Init($this->anketa);
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');

		$f = new Forum;
		
		$sql = sisplet_query("SELECT * FROM post WHERE tid='$rowa[thread]'");
		
		echo '<div style="padding:0 20px">';
		echo '<p><b>'.$lang['srv_admin_s_comments'].'</b></p>';
		
		if (mysqli_num_rows($sql) > 0) {
			$rows = mysqli_num_rows($sql);
			if ($rows > 0) echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
		}
	
		$i=0;
		while ($row = mysqli_fetch_array($sql)) {
			
			if (($i != 0 && $sortpostorder==0) || ($i < $rows-1 && $sortpostorder==1)) {
				if ($row['ocena'] == 0) echo '<span style="color:black">';
					elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
					elseif ($row['ocena'] == 2) echo '<span style="color:lightgray">';
					elseif ($row['ocena'] == 3) echo '<span style="color:lightgray">';
					else echo '<span>';
				
				echo '<b>'.$f->user($row['uid']).'</b> ('.$f->datetime1($row['time']).'):';
				echo '<br/>'.$row['vsebina'].'<hr>';
				
				echo '</span>';
			}
			$i++;
		}
		
		echo '</div>';
		
	}
	
	/**
	* izpise komentarje na anketo
	* 
	*/
	function testiranje_komentarji_anketa () {
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;
		
		$b = new Branching($this->anketa);
		$f = new Forum;
		$d = new Dostop();
		
		SurveySetting::getInstance()->Init($this->anketa);
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
		$commentmarks = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks');
		$survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment');
		$survey_comment_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_resp');
		$comment_count = $this->testiranje_komentarji_count();
		
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		
		echo '<div id="placeholder" class="komentarji">';
		
		
		echo '<div id="branching" class="branching_new expanded komentarji">';
		
		
		echo '<span id="comment_question_note">';
				
		echo $lang['srv_komentarji_imate'].' ';
		if(($comment_count['survey_resp']['unresolved']+$comment_count['survey_admin']['unresolved']) > 0) echo '<span class="red">';
		echo $this->string_format((int)($comment_count['survey_resp']['unresolved']+$comment_count['survey_admin']['unresolved']), 'srv_cnt_komentarji');			
		if(($comment_count['survey_resp']['unresolved']+$comment_count['survey_admin']['unresolved']) > 0) echo '</span>';
		
		echo ' '.$lang['srv_komentarji_odskupno'].' ';
		echo $this->string_format((int)($comment_count['survey_resp']['all']+$comment_count['survey_admin']['all']), 'srv_cnt_komentarji_survey_od');
		echo '</span>';
		
		
		echo '&nbsp;<span class="tooltip clr spaceLeft">';
		echo '<a href="'.SurveyInfo::getSurveyLink().'&preview=on&testdata=on'.$preview_options.'" target="_blank" style="font-size:15px"><span class="faicon edit_square"></span> '.$lang['srv_survey_testdata'].'</a>';
		echo ' ('.SurveyInfo::getSurveyLink().'&preview=on&testdata=on'.$preview_options.') ';
		echo '<span class="expanded-tooltip bottom light" style="left: -20px;">';
		echo '<b>' . $lang['srv_survey_testdata2'] . ':</b> '.$lang['srv_testdata_text'].'';
		echo '<p>'.$lang['srv_preview_testdata_longtext'].'</p>';
		echo '<span class="arrow"></span>';
		echo '</span>';	// expanded-tooltip bottom
		echo '</span>'; // tooltip
		
		
		# VV: privzeto naj bodo samo nerešeni komentarji
		if (!isset($_GET['only_unresolved'])) {
			$_GET['only_unresolved'] = 1;
		}
		
		
		echo '<span style="float:left; width:auto; margin-top:20px; display:inline-block;">';
					
		# samo nerešeni komentarji			
		if ($commentmarks == 0) {
			echo '<label for="only_unresolved"><input type="checkbox" id="only_unresolved" onchange="window.location = \'index.php?anketa='.$this->anketa.'&a=komentarji_anketa&only_unresolved=\'+$(\'#only_unresolved:checked\').val()" value="1" '.($_GET['only_unresolved']==1?'checked':'').' />';
			echo $lang['srv_comments_unresolved'];
			echo '</label>';
		} else {
			echo $lang['move_show'].': <select id="only_unresolved" name="" onchange="window.location = \'index.php?anketa='.$this->anketa.'&a=komentarji_anketa&only_unresolved=\'+$(\'#only_unresolved\').val(); " >
								<option value="0"'.($_GET['only_unresolved']==0?' selected="selected"':'').'>'.$lang['all2'].'</option>
								<option value="1"'.($_GET['only_unresolved']==1?' selected="selected"':'').'>'.$lang['srv_comments_unresolved'].'</option>
								<option value="2"'.($_GET['only_unresolved']==2?' selected="selected"':'').'>'.$lang['srv_undecided'].'</option>
								<option value="3"'.($_GET['only_unresolved']==3?' selected="selected"':'').'>'.$lang['srv_todo'].'</option>
								<option value="4"'.($_GET['only_unresolved']==4?' selected="selected"':'').'>'.$lang['srv_done'].'</option>
								<option value="5"'.($_GET['only_unresolved']==5?' selected="selected"':'').'>'.$lang['srv_not_relevant'].'</option>
							</select>';
		}
		echo ' '.Help::display('srv_comments_only_unresolved').'</span>';
		
		$only_unresolved = " ";
		switch($_GET['only_unresolved']){
			case 1:
				$only_unresolved = " AND ocena <= '1' ";
				break;
			case 2:
				$only_unresolved = " AND ocena = '0' ";
				break;
			case 3:
				$only_unresolved = " AND ocena = '1' ";
				break;
			case 4:
				$only_unresolved = " AND ocena = '2' ";
				break;
			case 5:
				$only_unresolved = " AND ocena = '3' ";
				break;
			default:
				break;
		}

		
		echo '<span class="clr"></span>';
				
		// Splosni komentarji urednikov - levo
		echo '<div class="komentarji_anketa komentarji_ured">';		
		echo '<span class="komentarji_title">'.$lang['srv_comments_anketa_ured'].' ('.$comment_count['survey_admin']['unresolved'].'/'.$comment_count['survey_admin']['all'].')</span>';
		
		echo '<div style="padding: 20px 20px">';
		
		$sql = sisplet_query("SELECT * FROM post WHERE tid='$rowa[thread]' ".$only_unresolved." ");
		$rows = (mysqli_num_rows($sql) == 0) ? 0 : mysqli_num_rows($sql) - 1;
		if ($rows > 0){
			echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
			echo '<br />';
			
			$i=0;
			while ($row = mysqli_fetch_array($sql)) {
				
				if (($i != 0 && $sortpostorder==0) || ($i < $rows && $sortpostorder==1)) {
					if ($row['ocena'] == 0) echo '<span style="color:black">';
						elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
						elseif ($row['ocena'] == 2) echo '<span style="color:#999999">';
						elseif ($row['ocena'] == 3) echo '<span style="color:#999999">';
						else echo '<span>';
					
					echo '<b>'.$f->user($row['uid']).'</b> ('.$f->datetime1($row['time']).'):';
					
					echo '<div style="float:right">';
					if ($commentmarks == 1) {
						echo '	<select name="ocena'.$row['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: this.value, id: \''.$row['id'].'\', anketa: \''.$rowa['id'].'\'}, function () {window.location.reload();});">
									<option value="0"'.($row['ocena']==0?' selected="selected"':'').'>'.$lang['srv_undecided'].'</option>
									<option value="1"'.($row['ocena']==1?' selected="selected"':'').'>'.$lang['srv_todo'].'</option>
									<option value="2"'.($row['ocena']==2?' selected="selected"':'').'>'.$lang['srv_done'].'</option>
									<option value="3"'.($row['ocena']==3?' selected="selected"':'').'>'.$lang['srv_not_relevant'].'</option>
								</select>';
					} else {
						// Checkbox za "Koncano"			
						echo '<input type="checkbox" name="ocena_'.$row['id'].'" id="ocena_'.$row['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$row['id'].'\', anketa: \''.$rowa['id'].'\'}, function () {window.location.reload();});" value="2" '.($row['ocena'] >= 2?' checked':'').' />';
						echo '<label for="ocena_'.$row['id'].'">'.$lang['srv_done'].'</label>';				
					}
					echo '</div>';
					
					echo '<br/>'.$row['vsebina'].'<hr>';

					echo '</span>';
				}
				$i++;
			}	
		}
		// Nimamo komentarja
		else{
			// Ce so komentarji aktivirani
			if($survey_comment != ''){
				echo $lang['srv_no_comments_solved'];
			}
			else{
				echo $lang['srv_no_comments'];
			}
		}
		
		echo '</div>';
		
		// Dodajanje novega komentarja
		echo '<div id="survey_comment_0_4" style="display:none"></div>';
		echo '<p><a href="#" onclick="$(\'#comment_field_admin\').toggle(); return false;">'.$lang['srv_comments_add_comment'].'</a></p>';
		echo '<p id="comment_field_admin" style="display:none">';
		$ba = new BranchingAjax($this->anketa);
		$ba->add_comment_field(0, '1', '4', false);
		echo '</p>';
		
		echo '</div>';
		
		
		// Splosni komentarji respondentov - desno
		echo '<div class="komentarji_anketa komentarji_resp">';		
		echo '<span class="komentarji_title">'.$lang['srv_comments_anketa_resp'].' ('.$comment_count['survey_resp']['unresolved'].'/'.$comment_count['survey_resp']['all'].')</span>';
		
		echo '<div style="padding: 20px 20px">';
		
		$sql = sisplet_query("SELECT * FROM srv_comment_resp WHERE ank_id='$this->anketa' ".$only_unresolved." ORDER BY comment_time $orderby, id $orderby");
		if (mysqli_num_rows($sql) > 0) {

			echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
			echo '<br />';
			
			while ($row = mysqli_fetch_array($sql)) {
			
				if ($row['ocena'] == 0) echo '<span style="color:black">';
				elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
				elseif ($row['ocena'] == 2) echo '<span style="color:#999999">';
				elseif ($row['ocena'] == 3) echo '<span style="color:#999999">';
				else echo '<span>';
				
				$datetime = strtotime($row['comment_time']);
				$datetime = date("d.m G:i", $datetime);
				
				if($row['usr_id'] == 0){
					$user = $lang['guest'];
				}
				else{
					$sqlU = sisplet_query("SELECT name FROM users WHERE id='$row[usr_id]'");
					$rowU = mysqli_fetch_array($sqlU);
					
					$user = $rowU['name'];
				}
				
				echo '<b>'.$user.'</b> ('.$datetime.'):';
				
				echo '<div style="float:right">';
				if ($commentmarks == 1) {
					echo '	<select name="ocena'.$row['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'respondent_survey_comment\', ocena: this.value, id: \''.$row['id'].'\', anketa: \''.$rowa['id'].'\'}, function () {window.location.reload();});">
								<option value="0"'.($row['ocena']==0?' selected="selected"':'').'>'.$lang['srv_undecided'].'</option>
								<option value="1"'.($row['ocena']==1?' selected="selected"':'').'>'.$lang['srv_todo'].'</option>
								<option value="2"'.($row['ocena']==2?' selected="selected"':'').'>'.$lang['srv_done'].'</option>
								<option value="3"'.($row['ocena']==3?' selected="selected"':'').'>'.$lang['srv_not_relevant'].'</option>
							</select>';
				} else {
					// Checkbox za "Koncano"			
					echo '<input type="checkbox" name="ocena_'.$row['id'].'" id="ocena_'.$row['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'respondent_survey_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$row['id'].'\', anketa: \''.$rowa['id'].'\'}, function () {window.location.reload();});" value="2" '.($row['ocena'] >= 2?' checked':'').' />';
					echo '<label for="ocena_'.$row['id'].'">'.$lang['srv_done'].'</label>';		
				}
				echo '</div>';
				
				echo '<br/>'.$row['comment'].'<hr>';
				
				echo '</span>';
			}
		}
		// Nimamo komentarja
		else{
			// Ce so komentarji aktivirani
			if($survey_comment_resp != ''){
				echo $lang['srv_no_comments_solved'];
			}
			else{
				echo $lang['srv_no_comments'];
			}
		}
		
		echo '</div>';
		
		// Dodajanje novega komentarja
		echo '<div id="survey_comment_0_5" style="display:none"></div>';
		echo '<p><a href="#" onclick="$(\'#comment_field_resp\').toggle(); return false;">'.$lang['srv_comments_add_comment'].'</a></p>';
		echo '<p id="comment_field_resp" style="display:none">';
		$ba = new BranchingAjax($this->anketa);
		$ba->add_comment_field(0, '4', '5', false);
		echo '</p>';
		
		echo '</div>';
	
			
		echo '</div>';
		echo '</div>';		
	}
	
	function testiranje_komentarji_komentarji_na_anketo ($return = true) {
		
		$rowi = SurveyInfo::getInstance()->getSurveyRow();

		#komentarji na anketo
		# vsi komentarji na anketo
		$strta = "SELECT count(*) FROM post WHERE tid='".$rowi['thread']."' AND parent > 0";
		$sqlta = sisplet_query($strta);
		list($rowta) = mysqli_fetch_row($sqlta);

		# nerešeni komentarji: only_unresolved =>   ocena <= 1
		$strtu = "SELECT count(*) FROM post WHERE tid='".$rowi['thread']."' AND parent > 0 AND ocena <= 1 ";
		$sqltu = sisplet_query($strtu);
		list($rowtu) = mysqli_fetch_row($sqltu);
		
		if ($return)
			return '(<span class="lightRed">'.(int)$rowtu.'</span>/'.(int)$rowta.')';
		else
			return (int)$rowtu;
	}
	
	public function testiranje_komentarji_count () {
		
		$comment_count = array();
		
		$rowi = SurveyInfo::getInstance()->getSurveyRow();
		
		
		// KOMENTARJI NA ANKETO - UREDNIK
		# vsi komentarji na anketo
		$strta = "SELECT count(*) FROM post WHERE tid='".$rowi['thread']."' AND parent > 0";
		$sqlta = sisplet_query($strta);
		list($rowta) = mysqli_fetch_row($sqlta);

		# nerešeni komentarji: only_unresolved =>   ocena <= 1
		$strtu = "SELECT count(*) FROM post WHERE tid='".$rowi['thread']."' AND parent > 0 AND ocena <= 1 ";
		$sqltu = sisplet_query($strtu);
		list($rowtu) = mysqli_fetch_row($sqltu);
		//(int)$rowtu.'/'.(int)$rowta;
		$comment_count['survey_admin']['all'] = (int)$rowta;
		$comment_count['survey_admin']['unresolved'] = (int)$rowtu;
		
		
		// KOMENTARJI NA ANKETO - RESPONDENT
		# vsi komentarji na anketo
		$strta = "SELECT count(*) FROM srv_comment_resp WHERE ank_id='".$this->anketa."'";
		$sqlta = sisplet_query($strta);
		list($rowta) = mysqli_fetch_row($sqlta);

		# nerešeni komentarji: only_unresolved =>   ocena <= 1
		$strtu = "SELECT count(*) FROM srv_comment_resp WHERE ank_id='".$this->anketa."' AND ocena <= 1 ";
		$sqltu = sisplet_query($strtu);
		list($rowtu) = mysqli_fetch_row($sqltu);
		//(int)$rowtu.'/'.(int)$rowta;
		$comment_count['survey_resp']['all'] = (int)$rowta;
		$comment_count['survey_resp']['unresolved'] = (int)$rowtu;

		
		// KOMENTARJI NA VPRASANJE
		# naenkrat preberemo vse spremenljivke, da ne delamo queryja vsakic posebej
		$spremenljivke = Cache::cache_all_srv_spremenljivka($this->anketa, true);	
		$spr_id=array();
		$threads=array();
		if ( is_array($spremenljivke) && count($spremenljivke) > 0 ) {
		
			foreach ($spremenljivke as $id=>$value) {
				$spr_id[] = $id;
				
				if ((int)$value['thread'] > 0) {
					$threads[] = $value['thread'];
				}
			}
		}
		if (count($spr_id) > 0) {
			#preštejemo komentarje uporabnikov na vprašanja
			# srv_data_text where spr_id = 0 AND vre_id IN (id-ji spremenljivk)
			$strqr = "SELECT count(*) FROM srv_data_text".$this->db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$spr_id).")";
			$sqlqr = sisplet_query($strqr);
			list($rowqr) = mysqli_fetch_row($sqlqr);
			
			#končani komentarji respondentov
			#text2 = 2 => končan
			#text2 = 3 => nerelevantno
			$strqrf = "SELECT count(*) FROM srv_data_text".$this->db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$spr_id).") AND text2 IN (2,3)";
			$sqlqrf = sisplet_query($strqrf);
			list($rowqrf) = mysqli_fetch_row($sqlqrf);
			
			# preštejemo
			if (count($threads) > 0) {
				# vsi komentarji na anketo
				$strta = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads).") AND parent > 0";
				$sqlta = sisplet_query($strta);
				list($rowtqa) = mysqli_fetch_row($sqlta);
				# nerešeni komentarji: only_unresolved =>   ocena <= 1
				$strtu = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads).") AND parent > 0 AND ocena IN (2,3) ";
				$sqltu = sisplet_query($strtu);
				list($rowtqu) = mysqli_fetch_row($sqltu);
			}
		}
			
		#vsi
		//$all = (int)((int)$rowqr + (int)$rowtqa);
		#nerešeni
		//$unresolved = $all - (int)((int)$rowqrf + (int)$rowtqu);
		$comment_count['question']['all'] = (int)((int)$rowqr + (int)$rowtqa);
		$comment_count['question']['unresolved'] = $comment_count['question']['all'] - (int)((int)$rowqrf + (int)$rowtqu);
		
		// KOMENTARJI NA IF ALI BLOK
		# naenkrat preberemo vse ife in bloke, da ne delamo queryja vsakic posebej
		$ifi = Cache::cache_all_srv_if($this->anketa, true);	
		$if_id = array();
		$threads_if = array();
		if ( is_array($ifi) && count($ifi) > 0 ) {
		
			foreach ($ifi as $id=>$value) {
				$if_id[] = $id;
				
				if ((int)$value['thread'] > 0) {
					$threads_if[] = $value['thread'];
				}
			}
		}
		if (count($if_id) > 0) {
			
			#preštejemo komentarje uporabnikov na vprašanja
			# srv_data_text where if_id = 0 AND vre_id IN (id-ji spremenljivk)
			$strqr = "SELECT count(*) FROM srv_data_text".$this->db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$if_id).")";
			$sqlqr = sisplet_query($strqr);
			list($rowqr_if) = mysqli_fetch_row($sqlqr);
			
			#končani komentarji respondentov
			#text2 = 2 => končan
			#text2 = 3 => nerelevantno
			$strqrf = "SELECT count(*) FROM srv_data_text".$this->db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$if_id).") AND text2 IN (2,3)";
			$sqlqrf = sisplet_query($strqrf);
			list($rowqrf_if) = mysqli_fetch_row($sqlqrf);
			
			# preštejemo
			if (count($threads_if) > 0) {
				# vsi komentarji na anketo
				$strta = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads_if).") AND parent > 0";
				$sqlta = sisplet_query($strta);
				list($rowtqa_if) = mysqli_fetch_row($sqlta);
				# nerešeni komentarji: only_unresolved =>   ocena <= 1
				$strtu = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads_if).") AND parent > 0 AND ocena IN (2,3) ";
				$sqltu = sisplet_query($strtu);
				list($rowtqu_if) = mysqli_fetch_row($sqltu);
			}
		}
		
		$comment_count['question']['all'] += (int)((int)$rowqr_if + (int)$rowtqa_if);
		$comment_count['question']['unresolved'] += ((int)((int)$rowqr_if + (int)$rowtqa_if)) - ((int)((int)$rowqrf_if + (int)$rowtqu_if));
		
		
		return $comment_count;
	}
	
	/**
	 * $return pove a vrne text (true) ali samo številko (false)
	 */
	function testiranje_komentarji_komentarji_na_vprasanje ($return = true) {
			
		# naenkrat preberemo vse spremenljivke, da ne delamo queryja vsakic posebej
		$spremenljivke = Cache::cache_all_srv_spremenljivka($this->anketa, true);
			
		$spr_id=array();
		$threads=array();
		if ( is_array($spremenljivke) && count($spremenljivke) > 0 ) {
			foreach ($spremenljivke as $id=>$value) {
				$spr_id[] = $id;
				if ((int)$value['thread'] > 0) {
					$threads[] = $value['thread'];
				}
			}
		}
		if (count($spr_id) > 0) {
			#preštejemo komentarje uporabnikov na vprašanja
			# srv_data_text where spr_id = 0 AND vre_id IN (id-ji spremenljivk)
			$strqr = "SELECT count(*) FROM srv_data_text".$this->db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$spr_id).")";
			$sqlqr = sisplet_query($strqr);
			list($rowqr) = mysqli_fetch_row($sqlqr);
			
			#končani komentarji respondentov
			#text2 = 2 => končan
			#text2 = 3 => nerelevantno
			$strqrf = "SELECT count(*) FROM srv_data_text".$this->db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$spr_id).") AND text2 IN (2,3)";
			$sqlqrf = sisplet_query($strqrf);
			list($rowqrf) = mysqli_fetch_row($sqlqrf);
			
			# preštejemo
			if (count($threads) > 0) {
				# vsi komentarji na anketo
				$strta = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads).") AND parent > 0";
				$sqlta = sisplet_query($strta);
				list($rowtqa) = mysqli_fetch_row($sqlta);
				# nerešeni komentarji: only_unresolved =>   ocena <= 1
				$strtu = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads).") AND parent > 0 AND ocena IN (2,3) ";
				$sqltu = sisplet_query($strtu);
				list($rowtqu) = mysqli_fetch_row($sqltu);
			}
		}
			
		#vsi
		$all = (int)((int)$rowqr + (int)$rowtqa);
		# nerešeni
		$unresolved = $all - (int)((int)$rowqrf + (int)$rowtqu);
			
		if ($return)
			// '(<span class="lightRed">'.$unresolved.'</span>/'.$all.')';
			return $unresolved;
		else
			return $unresolved;
		
	}
	
	/**
	* izpise vprasanja, ki imajo komentarje in poleg tudi razprte komentarje
	* 
	*/
	function testiranje_komentarji () {
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;
		
		$lang_admin = $lang;
		
		//$sa = new SurveyAdmin(1, $this->anketa);
		include_once('../../main/survey/app/global_function.php');
		new \App\Controllers\SurveyController(true);
		save('forceShowSpremenljivka', true);

		$f = new Forum;
		$c = 0;
		
		$lang = $lang_admin;
		
		$b = new Branching($this->anketa);
		
		$d = new Dostop();
		
		$rowi = SurveyInfo::getInstance()->getSurveyRow();
		
		echo '<div id="placeholder" class="komentarji">';
		
		
        echo '<div id="branching" class="branching_new expanded komentarji">';
        
		SurveySetting::getInstance()->Init($this->anketa);
		$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');
		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
		$question_note_view = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_view');
		$addfieldposition = SurveySetting::getInstance()->getSurveyMiscSetting('addfieldposition');
		$commentmarks = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks');
		$commentmarks_who = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks_who');
		
		$question_comment_viewadminonly = ($question_comment_viewadminonly == '') ? 4 : $question_comment_viewadminonly;
		$question_resp_comment_viewadminonly = ($question_resp_comment_viewadminonly == '') ? 4 : $question_resp_comment_viewadminonly;;
		
		$comment_count = $this->testiranje_komentarji_count();
		
		
		echo '<span id="comment_question_note">';
				
		echo $lang['srv_komentarji_imate'].' ';
		if($comment_count['question']['unresolved'] > 0) echo '<span class="red">';
		echo $this->string_format((int)$comment_count['question']['unresolved'], 'srv_cnt_komentarji');			
		if($comment_count['question']['unresolved'] > 0) echo '</span>';
		
		echo ' '.$lang['srv_komentarji_odskupno'].' ';
		echo $this->string_format((int)$comment_count['question']['all'], 'srv_cnt_komentar_na_vprs_od');
		
		echo '</span>';
		
		
		echo '&nbsp;<span class="tooltip clr spaceLeft">';
		echo '<a href="'.SurveyInfo::getSurveyLink().'&preview=on&testdata=on'.$preview_options.'" target="_blank" style="font-size:15px"><span class="faicon edit_square"></span> '.$lang['srv_survey_testdata'].'</a>';
		echo ' ('.SurveyInfo::getSurveyLink().'&preview=on&testdata=on'.$preview_options.') ';
		echo '<span class="expanded-tooltip bottom light" style="left: -20px;">';
		echo '<b>' . $lang['srv_survey_testdata2'] . ':</b> '.$lang['srv_testdata_text'].'';
		echo '<p>'.$lang['srv_preview_testdata_longtext'].'</p>';
		echo '<span class="arrow"></span>';
		echo '</span>';	// expanded-tooltip bottom
		echo '</span>'; // tooltip
		
		
		# VV: privzeto naj bodo samo nerešeni komentarji
		if (!isset($_GET['only_unresolved'])) {
			$_GET['only_unresolved'] = 1;
		}
	
		
		$sqlf1 = sisplet_query("SELECT p.id FROM post p WHERE p.tid='$rowi[thread]' AND p.ocena='5'");
		while ($rowf1 = mysqli_fetch_array($sqlf1)) {
			$s = sisplet_query("SELECT * FROM views WHERE pid='$rowf1[id]' AND uid='$global_user_id'");
			if (mysqli_num_rows($s) == 0)
			$show_survey_comment = 1;
		}

		echo '<span style="float:left; width:auto; margin-top:20px; display:inline-block;">';
		
		// vsa vprasanja
		echo '<label for="all_questions" style="margin-right:40px"><input type="checkbox" id="all_questions" onchange="window.location = \'index.php?anketa='.$this->anketa.'&a=komentarji'.(isset($_GET['only_unresolved'])?'&only_unresolved='.$_GET['only_unresolved']:'').'&all_questions=\'+$(\'#all_questions:checked\').val()" value="1" '.($_GET['all_questions']==1?'checked':'').' />';
		echo $lang['srv_all_questions'];
		echo '</label>';
		
		# samo nerešeni komentarji			
		if ($commentmarks == 0) {
			echo '<label for="only_unresolved"><input type="checkbox" id="only_unresolved" onchange="window.location = \'index.php?anketa='.$this->anketa.'&a=komentarji'.(isset($_GET['all_questions'])?'&all_questions='.$_GET['all_questions']:'').'&only_unresolved=\'+$(\'#only_unresolved:checked\').val()" value="1" '.($_GET['only_unresolved']==1?'checked':'').' />';
			//echo $lang['srv_comments_unresolved'].' '.$this->testiranje_komentarji_komentarji_na_vprasanje();
			echo $lang['srv_comments_unresolved'];
			echo '</label>';
		} else {
			echo $lang['move_show'].': <select id="only_unresolved" name="" onchange="window.location = \'index.php?anketa='.$this->anketa.'&a=komentarji&only_unresolved=\'+$(\'#only_unresolved\').val(); " >
								<option value="0"'.($_GET['only_unresolved']==0?' selected="selected"':'').'>'.$lang['all2'].'</option>
								<option value="1"'.($_GET['only_unresolved']==1?' selected="selected"':'').'>'.$lang['srv_comments_unresolved'].'</option>
								<option value="2"'.($_GET['only_unresolved']==2?' selected="selected"':'').'>'.$lang['srv_undecided'].'</option>
								<option value="3"'.($_GET['only_unresolved']==3?' selected="selected"':'').'>'.$lang['srv_todo'].'</option>
								<option value="4"'.($_GET['only_unresolved']==4?' selected="selected"':'').'>'.$lang['srv_done'].'</option>
								<option value="5"'.($_GET['only_unresolved']==5?' selected="selected"':'').'>'.$lang['srv_not_relevant'].'</option>
							</select>';
		}
		echo ' '.Help::display('srv_comments_only_unresolved').'</span>';
		
		
		
		
		
		// Nov nacin kjer se sprehodimo cez branching, ker imamo lahko tudi komentarje na ife in bloke
		Common::getInstance()->Init($this->anketa);
		$branching_array = Common::getBranchingOrder();
		if (count($branching_array) > 0) {
				
			$view = 1;

			echo '<span class="floatLeft" style="width:100% !important;">';
			
			$b = new Branching($this->anketa);
			
			foreach($branching_array AS $element){
			
				// Gre za if ali blok
				if($element['if_id'] > 0){
					$if_id = $element['if_id'];
				
					$sql1 = sisplet_query("SELECT * FROM srv_if WHERE id = '$if_id'");
					$row1 = mysqli_fetch_array($sql1);
					
					$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
					$tid = $row1['thread'];	
					
					$only_unresolved = " ";
					$only_unresolved2 = " ";
					if ($_GET['only_unresolved'] == 1) $only_unresolved = " AND ocena <= 1 "; 
					if ($_GET['only_unresolved'] == 1) $only_unresolved2 = " AND text2 <= 1 "; 
					
					if ($_GET['only_unresolved'] == 2) $only_unresolved = " AND ocena = 0 "; 
					if ($_GET['only_unresolved'] == 2) $only_unresolved2 = " AND text2 = 0 "; 
					
					if ($_GET['only_unresolved'] == 3) $only_unresolved = " AND ocena = 1 "; 
					if ($_GET['only_unresolved'] == 3) $only_unresolved2 = " AND text2 = 1 "; 
					
					if ($_GET['only_unresolved'] == 4) $only_unresolved = " AND ocena = 2 "; 
					if ($_GET['only_unresolved'] == 4) $only_unresolved2 = " AND text2 = 2 "; 
					
					if ($_GET['only_unresolved'] == 5) $only_unresolved = " AND ocena = 3 "; 
					if ($_GET['only_unresolved'] == 5) $only_unresolved2 = " AND text2 = 3 "; 
					
					
					$tema_vsebuje = substr($lang['srv_forum_intro'],0,10);		// da ne prikazujemo 1. default sporocila
					
					if ($admin_type <= $question_comment_viewadminonly) {	// vidi vse komentarje
						$sqlt = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved ORDER BY time $orderby, id $orderby");
					} elseif ($question_comment_viewauthor==1) {	// vidi samo svoje komentarje
						$sqlt = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");
					} else {												// ne vidi nobenih komentarjev
						$sqlt = sisplet_query("SELECT * FROM post WHERE 1=0");
					}			
								
					if (($_GET['all_questions']=='1') OR (mysqli_num_rows($sqlt) > 0)) {
						$c++;
						
						echo '<div style="margin: 20px 0">';
						
						echo '<li id="branching_'.$if_id.'" class="spr">';
						echo '<div class="spremenljivka_content">';
						
						// Blok
						if($row1['tip'] == 1){
							echo '<div class="spremenljivka_settings" style="font-size:14px; padding:0 0 0 10px;">';
							echo '<span class="conditions_display"><strong class="clr_bl">BLOCK</strong> <span class="colorblock">('.$row1['number'].')</span>';
							echo '</div>';
						}
						// If
						else{
							echo '<div class="spremenljivka_settings" style="font-size:14px; padding:4px 0 0 10px;">';
							$b->conditions_display($if_id);
							echo '</div>';
						}				
						
						echo '</div>';
						echo '</li>';
												
						echo '<div style="width:40%; margin: 0 5% 2% 1%; float: left">';
						
						if ($addfieldposition == 1) {
							echo '<div id="survey_comment_'.$if_id.'_'.'4'.'" style="display:none"></div>';
							echo '<p><a href="#" onclick="$(\'#comment_field_'.$if_id.'\').toggle(); return false;">'.$lang['srv_comments_add_comment'].'</a></p>';
							echo '<p id="comment_field_'.$if_id.'" style="display:none">';
							$ba = new BranchingAjax($this->anketa);
							$ba->add_comment_field($if_id, '1', '4', false);
							echo '</p>';
						}
								
						// komentarji na vprasanje
						if ($row1['thread'] > 0) {
							
							if (mysqli_num_rows($sqlt) > 0) {
								
								echo '<p class="red"><b>'.$lang['srv_admin_comment'].'</b>';
								//$rowss = mysqli_num_rows($sql);
								//if ($rowss > 0) 
									echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
								echo '</p>';
						
								$i = 0;
								while ($rowt = mysqli_fetch_array($sqlt)) {
									if ($_GET['only_unresolved'] == 1) {
										if ($rowt['ocena'] == 0) echo '<span style="color:black">';
										elseif ($rowt['ocena'] == 1) echo '<span style="color:darkgreen">';
										elseif ($rowt['ocena'] == 2) echo '<span style="color:#999999">';
										elseif ($rowt['ocena'] == 3) echo '<span style="color:#999999">';
										else echo '<span>';
									} else {
										if ($rowt['ocena'] == 0) echo '<span style="color:#990000">';
										elseif ($rowt['ocena'] == 1) echo '<span style="color:darkgreen">';
										elseif ($rowt['ocena'] == 2) echo '<span style="color:black">';
										elseif ($rowt['ocena'] == 3) echo '<span style="color:black">';
										else echo '<span>';
									}
									
									echo '<b>'.$f->user($rowt['uid']).'</b> ('.$f->datetime1($rowt['time']).'):';
									
									if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
										
										echo '<div style="float:right; text-align:right">';
										
										if ($commentmarks == 1) {
											echo '	<select name="ocena" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: this.value, id: \''.$rowt['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});">
														<option value="0"'.($rowt['ocena']==0?' selected="selected"':'').'>'.$lang['srv_undecided'].'</option>
														<option value="1"'.($rowt['ocena']==1?' selected="selected"':'').'>'.$lang['srv_todo'].'</option>
														<option value="2"'.($rowt['ocena']==2?' selected="selected"':'').'>'.$lang['srv_done'].'</option>
														<option value="3"'.($rowt['ocena']==3?' selected="selected"':'').'>'.$lang['srv_not_relevant'].'</option>
													</select>';
										} else {
											echo '<input type="checkbox" name="ocena_'.$rowt['id'].'" id="ocena_'.$rowt['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$rowt['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});" value="2" '.($rowt['ocena'] >= 2?' checked':'').' /><label for="ocena_'.$rowt['id'].'">'.$lang['srv_done'].'</label>';
										}
										echo '	<br /><a href="javascript:comment_on_comment(\''.$rowt['id'].'\');">'.$lang['srv_comment_comment'].'</a>';
										echo '</div>';
									}
									
									echo '<br/>'.$rowt['vsebina'].'<span id="comment_on_comment_'.$rowt['id'].'"></span><hr>';
									echo '</span>';
								}				
							}
						}
						
						if ($addfieldposition == '' || $addfieldposition == 0) {
							echo '<div id="survey_comment_'.$spr_id.'_'.'4'.'" style="display:none"></div>';
							echo '<p><a href="#" onclick="$(\'#comment_field_'.$spr_id.'\').toggle(); return false;">'.$lang['srv_comments_add_comment'].'</a></p>';
							echo '<p id="comment_field_'.$spr_id.'" style="display:none">';
							$ba = new BranchingAjax($this->anketa);
							$ba->add_comment_field($spr_id, '1', '4', false);
							echo '</p>';
						}
						
						echo '</div>';
												
						echo '<div class="clr"></div>';
						echo '</div>';
					}
				}
				// Gre za navadno vprasanje
				else{
					$spr_id = $element['spr_id'];
				
					$sql1 = sisplet_query("SELECT thread, note FROM srv_spremenljivka WHERE id = '$spr_id'");
					$row1 = mysqli_fetch_array($sql1);
					
					$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
					$tid = $row1['thread'];	
					
					$only_unresolved = " ";
					$only_unresolved2 = " ";
					if ($_GET['only_unresolved'] == 1) $only_unresolved = " AND ocena <= 1 "; 
					if ($_GET['only_unresolved'] == 1) $only_unresolved2 = " AND text2 <= 1 "; 
					
					if ($_GET['only_unresolved'] == 2) $only_unresolved = " AND ocena = 0 "; 
					if ($_GET['only_unresolved'] == 2) $only_unresolved2 = " AND text2 = 0 "; 
					
					if ($_GET['only_unresolved'] == 3) $only_unresolved = " AND ocena = 1 "; 
					if ($_GET['only_unresolved'] == 3) $only_unresolved2 = " AND text2 = 1 "; 
					
					if ($_GET['only_unresolved'] == 4) $only_unresolved = " AND ocena = 2 "; 
					if ($_GET['only_unresolved'] == 4) $only_unresolved2 = " AND text2 = 2 "; 
					
					if ($_GET['only_unresolved'] == 5) $only_unresolved = " AND ocena = 3 "; 
					if ($_GET['only_unresolved'] == 5) $only_unresolved2 = " AND text2 = 3 "; 
					
					
					$tema_vsebuje = substr($lang['srv_forum_intro'],0,10);		// da ne prikazujemo 1. default sporocila
					
					if ($admin_type <= $question_comment_viewadminonly) {	// vidi vse komentarje
						$sqlt = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved ORDER BY time $orderby, id $orderby");
					} elseif ($question_comment_viewauthor==1) {	// vidi samo svoje komentarje
						$sqlt = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");
					} else {												// ne vidi nobenih komentarjev
						$sqlt = sisplet_query("SELECT * FROM post WHERE 1=0");
					}
				
					$sql2 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_text".$this->db_table." WHERE spr_id='0' AND vre_id='$spr_id' $only_unresolved2");
					$row2 = mysqli_fetch_array($sql2);
					
								
					if ( ($_GET['all_questions']=='1') OR ( mysqli_num_rows($sqlt) > 0 || $row2['count'] > 0 || $row1['note'] != '' ) )  {
						$c++;
						
						echo '<div style="margin: 20px 0">';
						
						echo '<li id="branching_'.$spr_id.'" class="spr">';
						$b->vprasanje($spr_id);
						echo '</li>';
						
						if ($admin_type <= $question_note_view || $question_note_view == '') {
							
							if ($row1['note'] != '') {
								echo '<div style="float:left; width:100%; margin-left: 1%">';
								echo '<p class="red"><b><a href="#" class="gray" onclick="$(\'.note-'.$spr_id.', .pl, .mn\').toggle(); return false;"><span class="pl">+</span><span class="mn" style="display:none">-</span> '.$lang['srv_note'].'</a></b></p>';
								echo '<p class="note-'.$spr_id.' displayNone">'.nl2br($row1['note']).'</p>';
								echo '</div>';
							}
						}
						
						echo '<div style="width:40%; margin: 0 5% 2% 1%; float: left">';
						
						if ($addfieldposition == 1) {
							echo '<div id="survey_comment_'.$spr_id.'_'.'4'.'" style="display:none"></div>';
							echo '<p><a href="#" onclick="$(\'#comment_field_'.$spr_id.'\').toggle(); return false;">'.$lang['srv_comments_add_comment'].'</a></p>';
							echo '<p id="comment_field_'.$spr_id.'" style="display:none">';
							$ba = new BranchingAjax($this->anketa);
							$ba->add_comment_field($spr_id, '1', '4', false);
							echo '</p>';
						}
								
						// komentarji na vprasanje
						if ($row1['thread'] > 0) {
							
							if (mysqli_num_rows($sqlt) > 0) {
								
								echo '<p class="red"><b>'.$lang['srv_admin_comment'].'</b>';
								//$rowss = mysqli_num_rows($sql);
								//if ($rowss > 0)
									echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
								echo '</p>';
						
								$i = 0;
								while ($rowt = mysqli_fetch_array($sqlt)) {
                                    if ($_GET['only_unresolved'] == 1) {
                                    if ($rowt['ocena'] == 0) echo '<span style="color:black">';
                                    elseif ($rowt['ocena'] == 1) echo '<span style="color:darkgreen">';
                                    elseif ($rowt['ocena'] == 2) echo '<span style="color:#999999">';
                                    elseif ($rowt['ocena'] == 3) echo '<span style="color:#999999">';
                                    else echo '<span>';
                                    } else {
                                        if ($rowt['ocena'] == 0) echo '<span style="color:#990000">';
                                        elseif ($rowt['ocena'] == 1) echo '<span style="color:darkgreen">';
                                        elseif ($rowt['ocena'] == 2) echo '<span style="color:black">';
                                        elseif ($rowt['ocena'] == 3) echo '<span style="color:black">';
                                        else echo '<span>';
                                    }
                                    
                                    echo '<b>'.$f->user($rowt['uid']).'</b> ('.$f->datetime1($rowt['time']).'):';
                                    
                                    if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
                                        
                                        echo '<div style="float:right; text-align:right">';
                                        
                                        if ($commentmarks == 1) {
                                            echo '	<select name="ocena" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: this.value, id: \''.$rowt['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});">
                                                        <option value="0"'.($rowt['ocena']==0?' selected="selected"':'').'>'.$lang['srv_undecided'].'</option>
                                                        <option value="1"'.($rowt['ocena']==1?' selected="selected"':'').'>'.$lang['srv_todo'].'</option>
                                                        <option value="2"'.($rowt['ocena']==2?' selected="selected"':'').'>'.$lang['srv_done'].'</option>
                                                        <option value="3"'.($rowt['ocena']==3?' selected="selected"':'').'>'.$lang['srv_not_relevant'].'</option>
                                                    </select>';
                                        } else {
                                            echo '<input type="checkbox" name="ocena_'.$rowt['id'].'" id="ocena_'.$rowt['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$rowt['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});" value="2" '.($rowt['ocena'] >= 2?' checked':'').' /><label for="ocena_'.$rowt['id'].'">'.$lang['srv_done'].'</label>';
                                        }
                                        echo '	<br /><a href="javascript:comment_on_comment(\''.$rowt['id'].'\');">'.$lang['srv_comment_comment'].'</a>';
                                        echo '</div>';
                                    }
                                    
                                    echo '<br/>'.$rowt['vsebina'].'<span id="comment_on_comment_'.$rowt['id'].'"></span><hr>';
                                    echo '</span>';
								}
							}
						}
						
						if ($addfieldposition == '' || $addfieldposition == 0) {
							echo '<div id="survey_comment_'.$spr_id.'_'.'4'.'" style="display:none"></div>';
							echo '<p><a href="#" onclick="$(\'#comment_field_'.$spr_id.'\').toggle(); return false;">'.$lang['srv_comments_add_comment'].'</a></p>';
							echo '<p id="comment_field_'.$spr_id.'" style="display:none">';
							$ba = new BranchingAjax($this->anketa);
							$ba->add_comment_field($spr_id, '1', '4', false);
							echo '</p>';
						}
						
						echo '</div>';
						
						// komentarji respondentov
						if ($row2['count'] > 0) {
							
							if ($admin_type <= $question_resp_comment_viewadminonly) {
								echo '<div style="width:40%; margin: 0 5% 0 1%; float: left">';
								echo '<p class="red"><b>'.$lang['srv_repondent_comment'].'</b></p>';
								
								if ($_GET['only_unresolved'] == 1) $only_unresolved = " AND d.text2 <= 1 "; else $only_unresolved = " ";
								
								$sqlt = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='0' AND vre_id='$spr_id' $only_unresolved2 ORDER BY id ASC");
								if (!$sqlt) echo mysqli_error($GLOBALS['connect_db']);
								while ($rowt = mysqli_fetch_array($sqlt)) {
									
									if ($rowt['text2'] == 0) echo '<span style="color:black">';
									elseif ($rowt['text2'] == 1) echo '<span style="color:darkgreen">';
									elseif ($rowt['text2'] == 2) echo '<span style="color:#999999">';
									elseif ($rowt['text2'] == 3) echo '<span style="color:#999999">';
									else echo '<span>';
									
									
									if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
										echo '<div style="float:right">';
										if ($commentmarks == 1) {
											echo '	<select name="ocena'.$rowt['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'respondent_comment\', text2: this.value, id: \''.$rowt['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});">
														<option value="0"'.($rowt['text2']==0?' selected':'').'>'.$lang['srv_undecided'].'</option>
														<option value="1"'.($rowt['text2']==1?' selected':'').'>'.$lang['srv_todo'].'</option>
														<option value="2"'.($rowt['text2']==2?' selected':'').'>'.$lang['srv_done'].'</option>
														<option value="3"'.($rowt['text2']==3?' selected':'').'>'.$lang['srv_not_relevant'].'</option>
													</select>';
										} else {
											echo '<input type="checkbox" name="ocena_'.$rowt['id'].'" id="ocena_'.$rowt['id'].'" onchange="$.post(\'ajax.php?a=comment_ocena\', {type: \'respondent_comment\', text2: (this.checked?\'2\':\'0\'), id: \''.$rowt['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});" value="2" '.($rowt['text2'] >= 2?' checked':'').' /><label for="ocena_'.$rowt['id'].'">'.$lang['srv_done'].'</label>';
										}
										echo '  </div>';
									}

									// Ce smo slucajno pobrisali testne vnose, nimamo casa vnosa komentarja
									$sqlTime = sisplet_query("SELECT time_edit FROM srv_user WHERE id='".$rowt['usr_id']."'");
									if(mysqli_num_rows($sqlTime) > 0){
										
										$rowTime = mysqli_fetch_array($sqlTime);
									
										if ( strpos($rowt['text'], '__DATE__') !== false ) {
											$rowt['text'] = str_replace('__DATE__', $f->datetime1($rowTime['time_edit']), $rowt['text']);
											echo ''.nl2br($rowt['text']).'<hr>';
										} 
										else {
											echo ''.$f->datetime1($rowTime['time_edit']).':<br>';
											echo ''.nl2br($rowt['text']).'<hr>';
										}
									}
									else{
										if ( strpos($rowt['text'], '__DATE__') !== false ) {
											$rowt['text'] = str_replace('__DATE__', '', $rowt['text']);
											echo ''.nl2br($rowt['text']).'<hr>';
										} 
										else {
											echo ''.nl2br($rowt['text']).'<hr>';
										}
									}
									
									echo '</span>';
									
								}
								echo '</div>';
							}
						}
						
						echo '<div class="clr"></div>';
						echo '</div>';
					}				
				}
			}
			
			echo '</span>';
			
			if ($c == 0) {
				echo '<div style="margin-top: 60px;">';
				echo $lang['srv_no_comments_solved'].'<br/>';
				#echo '<a href="index.php?anketa='.$this->anketa.'&a=urejanje">'.$lang['srv_settings_komentarji'].'</a>';
				echo '</div>';
			}
		}
		else {		
			echo '<div style="margin-top: 60px;">';
			echo $lang['srv_no_comments'].'<br/>';
			echo '</div>';	
		}		
		
		echo '</div>';	// branching	
		echo '<div id="vprasanje_float_editing"></div>';
		echo '</div>';	// placeholder
	}
	
	/**
	* odsteje dva datuma, $d1 - D2
	* 
	*/
	function diff ($d1, $d2) {
		//echo $d1.' '.$d2;
		$d1 = (is_string($d1) ? strtotime($d1) : $d1);
        $d2 = (is_string($d2) ? strtotime($d2) : $d2);
		//echo ' ('.$d1.' '.$d2.')<br>';
		$diff_secs = abs($d1 - $d2);
        
        return $diff_secs;
	}
	
	private $usr_id;
	/**
	* vnese izbrano stevilo testnih podatkov
	* 
	*/
	function testiranje_testnipodatki () {
		global $lang;
		
		if ($_POST['stevilo_vnosov'] > 0) {
			
			// Nastavitev da vstavljamo samo veljavne vnose
			$only_valid = isset($_POST['only_valid']) ? $_POST['only_valid'] : 0;
			
			if(session_id() == '') {session_start();}
			$_SESSION['progressBar'][$this->anketa]['status'] = 'ok';
			$_SESSION['progressBar'][$this->anketa]['total'] = (int)$_POST['stevilo_vnosov'];
			$_SESSION['progressBar'][$this->anketa]['current'] = 0;
			session_commit();
			
			SurveyInfo::getInstance()->SurveyInit($this->anketa);
			$rowa = SurveyInfo::getInstance()->getSurveyRow();
			if ($rowa['survey_type'] < 2) return;	// samo za anketo na več straneh in branching...
			
			$sql = sisplet_query("SELECT MAX(recnum) AS recnum FROM srv_user WHERE ank_id = '$this->anketa' AND preview='0'");
	        $row = mysqli_fetch_array($sql);
	        $recnum = $row['recnum'] + 1;
	        
			//$sql = sisplet_query("SELECT s.id, s.tip, s.size, s.ranking_k, s.design FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.visible='1' ORDER BY g.vrstni_red, s.vrstni_red");
			$sql = sisplet_query("SELECT s.id, s.tip, s.size, s.ranking_k, s.design, s.cela FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.visible='1' ORDER BY g.vrstni_red, s.vrstni_red");
	        
	        # zabeležimo id-je za arhiv testnih vnosov
	        
			$arrayTestni = array();
			for ($i=1; $i<=$_POST['stevilo_vnosov']; $i++) {
				
                session_start();
				$_SESSION['progressBar'][$this->anketa]['current'] = $i;
				session_commit();
				
				// izberemo random hash, ki se ni v bazi (to more bit, ker je index na fieldu cookie)
				do {
					$rand = md5(mt_rand(1, mt_getrandmax()).'@'.$_SERVER['REMOTE_ADDR']);
					$sql1 = sisplet_query("SELECT id FROM srv_user WHERE cookie = '$rand'");
				} while (mysqli_num_rows($sql1) > 0);
				
				$sql2 = sisplet_query("INSERT INTO srv_user (ank_id, preview, testdata, cookie, user_id, ip, time_insert, recnum, referer, last_status, lurker) VALUES ('$this->anketa', '0', '2', '$rand', '0', '$_SERVER[REMOTE_ADDR]', NOW(), '$recnum', '$_SERVER[HTTP_REFERER]', '6', '0')");
				if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
				$this->usr_id = mysqli_insert_id($GLOBALS['connect_db']);
				
				// Survey v zanki vsakič kreiramo znova zaradi IFov !!!
				include_once('../../main/survey/app/global_function.php');
				new \App\Controllers\SurveyController(true);
				save('usr_id', $this->usr_id);
				$s = \App\Controllers\CheckController::getInstance();

				$arrayTestni[] = $this->usr_id;
				mysqli_data_seek($sql, 0);
				while ($row = mysqli_fetch_array($sql)) {
					
					$srv_data_vrednost = "";
					$srv_data_grid = "";
					$srv_data_checkgrid = "";
					$srv_data_text = "";
					$srv_data_textgrid = "";
					$srv_data_rating = "";
                    $srv_data_map = "";
                    $srv_data_heatmap = "";
					
					if ($row['tip'] != 5) {
	
						// radio ali select
						if ( ($row['tip']==1 || $row['tip']==3) ) {
							
							$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id='$row[id]'");
							
							// Ce imamo samo veljavne vedno oznacimo enega
							if($only_valid == 1)
								$rand = rand(1, mysqli_num_rows($sql1));
							else
								$rand = rand(0, mysqli_num_rows($sql1));
							
							if ($rand > 0) {
								for ($j=1; $j<=$rand; $j++)
									$row1 = mysqli_fetch_array($sql1);
								$vrednost = $row1['id'];
							} else {
								$vrednost = 0;
							}
							
	                        if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
							    if ($vrednost > 0) {
							    	$srv_data_vrednost .= "('$row[id]', '$vrednost', '$this->usr_id'),";
	                                if ($row1['other'] == 1)
	                                    $srv_data_text .= "('$row[id]', '$vrednost', '".$this->randomString()."', '', '$this->usr_id'),";
	                            }
	                        } else {
	                            $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
	                        }

	                    // checkbox
						} elseif ($row['tip'] == 2) {
							
							
	                        if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
	                        	
	                        	unset($vrednost);
	                        	$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id='$row[id]'");
								
								$randX = 0;
								if($only_valid == 1)
									$randX = rand(1, mysqli_num_rows($sql1));
								
								$j=1;
								while ($row1 = mysqli_fetch_array($sql1)) {
									$rand = rand(-1, 1);
									
									if ($rand > 0){
										$vrednost[$row1['id']] = $row1['id'];
									}
									
									// Ce imamo samo veljavne vedno oznacimo enega
									if($randX == $j && $only_valid == 1){
										$vrednost[$row1['id']] = $row1['id'];
									}
									
									$j++;
								}
								
							    if ($vrednost) {
								    foreach ($vrednost AS $key => $val) {
									    if ($val > 0) {
										    $srv_data_vrednost .= "('$row[id]', '$val', '$this->usr_id'),";
	                                        if ($row1['other'] == 1)
	                                            $srv_data_text .= "('$row[id]', '$val', '".$this->randomString()."', '', '$this->usr_id'),";
	                                    }
	                                }
							    }
	                        } else {
	                            $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
	                        }					
						
	                        
						// multigrid
						} elseif ($row['tip'] == 6) {

							$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$row[id]'");
							while ($row1 = mysqli_fetch_array($sql1)) {

	                            if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
								    
								    $sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='$row[id]'");
									
									// Ce imamo samo veljavne vedno oznacimo enega
									if($only_valid == 1)
										$rand = rand(1, mysqli_num_rows($sql2));
									else
										$rand = rand(0, mysqli_num_rows($sql2));
								    
								    if ($rand > 0) {
										for ($j=1; $j<=$rand; $j++)
											$row2 = mysqli_fetch_array($sql2);
										$grid_id = $row2['id'];
								    } else {
										$grid_id = 0;
								    }
								    
								    if ($grid_id > 0) {
	                                    $srv_data_grid .= "('$row[id]', '$row1[id]', '$this->usr_id', '$grid_id'),";
	                                }
									
							        if ($row1['other'] == 1 && $grid_id > 0)
	                                    $srv_data_text .= "('$row[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";

	                            } else {
	                                $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
	                            }
	                        }
	                    
						// multicheckbox
						} elseif ($row['tip'] == 16) {

							$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$row[id]'");
							
							while ($row1 = mysqli_fetch_array($sql1)) {
								$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
								
								$randX = 0;
								if($only_valid == 1)
									$randX = rand(1, mysqli_num_rows($sql2));
								
								$j=1;
								while ($row2 = mysqli_fetch_array($sql2)) {
									if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
																			
										$rand = rand(-1, 1);
									    
									    if ($rand > 0) {
											$grid_id = $row2['id'];
									    } else {
											$grid_id = 0;
									    }
										
										// Ce imamo samo veljavne vedno oznacimo enega
										if($randX == $j && $only_valid == 1){
											$grid_id = $row2['id'];
										}
										
										$j++;
										
										if ($grid_id > 0) {
											$srv_data_checkgrid .= "('$row[id]', '$row1[id]', '$this->usr_id', '$grid_id'),";
										}
										
										if ($row1['other'] == 1 && $grid_id > 0)
											$srv_data_text .= "('$row[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";
											
									} else {
										$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
									}
								}												
							}
						}
	                	
	                	// multitext
						elseif ($row['tip'] == 19) {

							$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$row[id]'");
							
							while ($row1 = mysqli_fetch_array($sql1)) {				
								$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
								
								while ($row2 = mysqli_fetch_array($sql2)) {                          												
									if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
									
										// Ce imamo samo veljavne imamo vedno vrednost
										if($only_valid == 1)
											$value = $this->randomString();
										else
											$value = rand(0,1)==0 ? $this->randomString() : '';
										
										$grid_id = $row2['id'];

										if ($value != '') {
											$srv_data_textgrid .= "('$row[id]', '$row1[id]', '$this->usr_id', '$grid_id', '$value'),";
										}
										
										// vsebino text polja vnesemo v vsakem primeru
										if ($row1['other'] == 1 && $value != '')
											$srv_data_text .= "('$row[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";				
									}								
																
									else {
										$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
									}
								}												
							}
						}
                                                
                                                //Lokacija
                                                elseif($row['tip'] == 26){
                                                    //choose location
                                                    $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'", 'array');

                                                    //so vrednosti, se pravi je choose
                                                    if($sql1){
                                                        foreach($sql1 as $row1){	
                                                            if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
                                                                    // Ce imamo samo veljavne imamo vedno vrednost
                                                                    if($only_valid == 1)
                                                                            $vrednost = $this->randomString();
                                                                    else
                                                                            $vrednost = rand(0,1)==0 ? $this->randomString() : '';

                                                                    $srv_data_map .= "(" . $this->usr_id . ", '$row[id]', '$row1[id]', ". $this->anketa . ", '', '', '', '".
                                                                            ($vrednost != '' ? $vrednost : '-1')."', ''),";
                                                            } 
                                                            else {
                                                                    $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
                                                            }
                                                        }
                                                    }
                                                    //niso vrednosti, se pravi je moja ali multi lokacija
                                                    else{
                                                            if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
                                                                
                                                                $make_input = ($only_valid == 1 ? true : rand(0,1)==0);
                                                                if($make_input){
                                                                
                                                                        $lat = floatval(mt_rand(454000, 466500)/10000);
                                                                        $lng = floatval(mt_rand(136000, 163900)/10000);

                                                                        // Ce imamo samo veljavne imamo vedno vrednost
                                                                        if($only_valid == 1)
                                                                                $vrednost = $this->randomString();
                                                                        else
                                                                                $vrednost = rand(0,1)==0 ? $this->randomString() : '';

                                                                        $srv_data_map .= "(" . $this->usr_id . ", '$row[id]', '$row1[id]', ". $this->anketa . ", '$lat', '$lng', '[N/A]', '".
                                                                                ($vrednost != '' ? $vrednost : '-1')."', ''),";
                                                                }
                                                            } 
                                                            else {
                                                                    $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
                                                            }
                                                    }
                                                }
						//Heatmap
						elseif($row['tip'] == 27){
							//choose location
							$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'", 'array');

							//so vrednosti, se pravi je choose
							if($sql1){
								foreach($sql1 as $row1){	
									if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
											// Ce imamo samo veljavne imamo vedno vrednost
											if($only_valid == 1)
													$vrednost = $this->randomString();
											else
													$vrednost = rand(0,1)==0 ? $this->randomString() : '';

											$srv_data_heatmap .= "(" . $this->usr_id . ", '$row[id]', '$row1[id]', ". $this->anketa . ", '', '', '', '".
													($vrednost != '' ? $vrednost : '-1')."', ''),";
									} 
									else {
											$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
									}
								}
							}
							//niso vrednosti, se pravi je moja ali multi lokacija
							else{
									if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
										
										$make_input = ($only_valid == 1 ? true : rand(0,1)==0);
										if($make_input){
										
												$lat = floatval(mt_rand(454000, 466500)/10000);
												$lng = floatval(mt_rand(136000, 163900)/10000);

												// Ce imamo samo veljavne imamo vedno vrednost
												if($only_valid == 1)
														$vrednost = $this->randomString();
												else
														$vrednost = rand(0,1)==0 ? $this->randomString() : '';

												$srv_data_heatmap .= "(" . $this->usr_id . ", '$row[id]', '$row1[id]', ". $this->anketa . ", '$lat', '$lng', '[N/A]', '".
														($vrednost != '' ? $vrednost : '-1')."', ''),";
										}
									} 
									else {
											$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
									}
							}
						}
                                                
						// multinumber
						elseif ($row['tip'] == 20) {

							$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$row[id]'");
							
							while ($row1 = mysqli_fetch_array($sql1)) {				
								$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
								
								while ($row2 = mysqli_fetch_array($sql2)) {                          												
									if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
									
										// Ce imamo samo veljavne imamo vedno vrednost
										if($only_valid == 1)
											$value = $this->randomNumber();
										else
											$value = rand(0,1)==0 ? $this->randomNumber() : '';
										
										$grid_id = $row2['id'];

										if ($value != '') {
											$srv_data_textgrid .= "('$row[id]', '$row1[id]', '$this->usr_id', '$grid_id', '$value'),";
										}
										
										// vsebino text polja vnesemo v vsakem primeru
										if ($row1['other'] == 1 && $value != '')
											$srv_data_text .= "('$row[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";				
									}								
																
									else {
									 	$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
									}
								}												
							}
						}
						
						// textbox
						elseif ($row['tip'] == 4) {
							
							if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
								$vrednost = rand(0,1)==0 ? $this->randomString() : '';
								if ($vrednost != '')
									$srv_data_text .= "('$row[id]', '', '$vrednost', '', '$this->usr_id'),";
							} else {
		                        $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
		                    }
						}
						
						// textbox*
						elseif ($row['tip'] == 21) {

	                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
							while ($row1 = mysqli_fetch_array($sql1)) {	
							
								if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
									
									// Ce imamo samo veljavne imamo vedno vrednost
									if($only_valid == 1)
										$vrednost = $this->randomString();
									else
										$vrednost = rand(0,1)==0 ? $this->randomString() : '';
									
									if ($vrednost != '')
										$srv_data_text .= "('$row[id]', '$row1[id]', '$vrednost', '', '$this->usr_id'),";
								} 
								
								else {
									$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
								}
							}
						} 
						
						// number
						elseif ($row['tip'] == 7) {						
							#######	za ureditev avtomatskega vnosa glede na dolzino stevila
							if($row['ranking_k']==0){	//ce je stevilo
								$newLength = $row['cela'];								
 							}elseif($row['ranking_k']==1){	//ce je drsnik							
								$rowParams = Cache::srv_spremenljivka($row['id']);
								$spremenljivkaParams = new enkaParameters($rowParams['params']);
								$slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);
								$newLength = strlen((string)$slider_MaxNumLabel)-1;
							}
							####### za ureditev avtomatskega vnosa glede na dolzino stevila - konec
							
							if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {

								$ij=1;
								
								unset($vrednost);
								
								// Ce imamo samo veljavne imamo vedno vrednost
								if($only_valid == 1){
									//if ($row['size'] >= 1) $vrednost[0] = $this->randomNumber();
									if ($row['size'] >= 1) $vrednost[0] = $this->randomNumber($newLength);
									//if ($row['size'] >= 2) $vrednost[1] = $this->randomNumber(); 
									if ($row['size'] >= 2) $vrednost[1] = $this->randomNumber($newLength); 
								}	
								elseif (rand(0,1) == 0) {
									//if ($row['size'] >= 1) $vrednost[0] = $this->randomNumber();
									if ($row['size'] >= 1) $vrednost[0] = $this->randomNumber($newLength);
									//if ($row['size'] >= 2) $vrednost[1] = $this->randomNumber(); 
									if ($row['size'] >= 2) $vrednost[1] = $this->randomNumber($newLength); 
								}
								
								if (isset($vrednost)){

									$text = '';
									$text2 = '';
									
									foreach ($vrednost AS $key => $val) {
										if($ij==1){
											if ($val != '')
												$text = $val;
										}
										
										else{
											if ($val != '')
												$text2 = $val;
										}

										$ij++;
									}
									
									$srv_data_text .= "('$row[id]', '', '$text', '$text2', '$this->usr_id'),";
								}
							}
							else {
								$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
							}
						} 
						
						// compute
						elseif ($row['tip'] == 22) {

							if (true){
								
								$val = $s->checkCalculation(-$row['id']); 	// za spremenljivke je v srv_calculation, v cnd_id zapisan id spremenljivke kot minus (plus je za kalkulacije v ifih)

								if ($val != '')
									$srv_data_text .= "('$row[id]', '', '$val', '', '$this->usr_id'),";
							}
						}
					
						// 8_datum
						elseif ($row['tip'] == 8) {

	                        if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
							    
								// Ce imamo samo veljavne imamo vedno vrednost
								if($only_valid == 1)
									$vrednost = $this->randomDate();
								else
									$vrednost = rand(0,1)==0 ? $this->randomDate() : '';
							    
							    if ($vrednost != '')
								    $srv_data_text .= "('$row[id]', '', '$vrednost', '', '$this->usr_id'),";
								
	                        } else {
	                            $srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
	                        }

						}
						
						// ranking
						elseif ($row['tip'] == 17) {
	                        if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
								
								//cifre
								if($row['design'] == 1 or true){	// tukaj se pac vse generira tukaj
									
									$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
									$rows = mysqli_num_rows($sql1);
									if ($row['ranking_k'] > 0)	$rows = $row['ranking_k'];
									unset($array);
									
									// Ce imamo samo veljavne imamo vedno vrednosti
									if($only_valid == 1){
										if (rand(0,1) == 0) $rows = floor($rows/2);
										$array = range(1, $rows);
										shuffle($array);
									}
									elseif (rand(0,1) == 0) {
										if (rand(0,1) == 0) $rows = floor($rows/2);
										$array = range(1, $rows);
										shuffle($array);
									}
									
									while($row1 = mysqli_fetch_array($sql1)){
									
										if (count($array) > 0) {
											
											$vrednost = array_pop($array);
											if ($vrednost != '')
												$srv_data_rating .= "('$row[id]', '$row1[id]', '$this->usr_id', '$vrednost'),";
										}						
									}					
								}
								
								//n==k (sortable)
								else if($row['design'] == 2){							
								}							
								//n>k
								else if($row['design'] == 0){							
								}
													
							}
							else
								$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
						}
						
						// vsota
						elseif ($row['tip'] == 18) {
						
							$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
							while($row1 = mysqli_fetch_array($sql1)){
							
								if ($s->checkSpremenljivka($row['id'], $isTestData=true)){
									
									unset($vrednost);
									
									// Ce imamo samo veljavne imamo vedno vrednosti
									if($only_valid == 1)
										$vrednost = $this->randomNumber();
									elseif(rand(0,1) == 0) 
										$vrednost = $this->randomNumber();
									
									if (isset( $vrednost )) {
										
										if ($vrednost != '')
											$srv_data_text .= "('$row[id]', '$row1[id]', '$vrednost', '', '$this->usr_id'),";
									}
								}
								else
									$srv_data_vrednost .= "('$row[id]', '-2', '$this->usr_id'),";
							}					
						}
						
						// Kombinirana tabela
						elseif($row['tip'] == 24){
						
							// Loop cez podtabele kombinirane dabele
							$sqlC = sisplet_query("SELECT s.id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='$row[id]' AND m.spr_id=s.id ORDER BY m.vrstni_red");
							while ($rowC = mysqli_fetch_array($sqlC)) {
								
								// multigrid
								if ($rowC['tip'] == 6) {

									$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$rowC[id]'");
									while ($row1 = mysqli_fetch_array($sql1)) {

										if ($s->checkSpremenljivka($rowC['id'], $isTestData=true)) {
											
											$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='$rowC[id]'");
											
											// Ce imamo samo veljavne vedno oznacimo enega
											if($only_valid == 1)
												$rand = rand(1, mysqli_num_rows($sql2));
											else
												$rand = rand(0, mysqli_num_rows($sql2));
											
											if ($rand > 0) {
												for ($j=1; $j<=$rand; $j++)
													$row2 = mysqli_fetch_array($sql2);
												$grid_id = $row2['id'];
											} else {
												$grid_id = 0;
											}
											
											if ($grid_id > 0) {
												$srv_data_grid .= "('$rowC[id]', '$row1[id]', '$this->usr_id', '$grid_id'),";
											}
											
											if ($row1['other'] == 1 && $grid_id > 0)
												$srv_data_text .= "('$rowC[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";

										} else {
											$srv_data_vrednost .= "('$rowC[id]', '-2', '$this->usr_id'),";
										}
									}
								
								// multicheckbox
								} elseif ($rowC['tip'] == 16) {

									$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$rowC[id]'");
									
									while ($row1 = mysqli_fetch_array($sql1)) {
										$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$rowC[id]' ORDER BY vrstni_red");
										
										$randX = 0;
										if($only_valid == 1)
											$randX = rand(1, mysqli_num_rows($sql2));
										
										$j=1;
										while ($row2 = mysqli_fetch_array($sql2)) {
											if ($s->checkSpremenljivka($rowC['id'], $isTestData=true)) {
																					
												$rand = rand(-1, 1);
												
												if ($rand > 0) {
													$grid_id = $row2['id'];
												} else {
													$grid_id = 0;
												}
												
												// Ce imamo samo veljavne vedno oznacimo enega
												if($randX == $j && $only_valid == 1){
													$grid_id = $row2['id'];
												}
												
												$j++;
												
												if ($grid_id > 0) {
													$srv_data_checkgrid .= "('$rowC[id]', '$row1[id]', '$this->usr_id', '$grid_id'),";
												}
												
												if ($row1['other'] == 1 && $grid_id > 0)
													$srv_data_text .= "('$rowC[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";
													
											} else {
												$srv_data_vrednost .= "('$rowC[id]', '-2', '$this->usr_id'),";
											}
										}												
									}
									
								}
								
								// multitext
								elseif ($rowC['tip'] == 19) {

									$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$rowC[id]'");
									
									while ($row1 = mysqli_fetch_array($sql1)) {				
										$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$rowC[id]' ORDER BY vrstni_red");
										
										while ($row2 = mysqli_fetch_array($sql2)) {                          												
											if ($s->checkSpremenljivka($row['id'], $isTestData=true)) {
											
												// Ce imamo samo veljavne imamo vedno vrednost
												if($only_valid == 1)
													$value = $this->randomString();
												else
													$value = rand(0,1)==0 ? $this->randomString() : '';
												
												$grid_id = $row2['id'];

												if ($value != '') {
													$srv_data_textgrid .= "('$rowC[id]', '$row1[id]', '$this->usr_id', '$grid_id', '$value'),";
												}

												// vsebino text polja vnesemo v vsakem primeru
												if ($row1['other'] == 1 && $value != '')
													$srv_data_text .= "('$rowC[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";				
											}								
																		
											else {
												$srv_data_vrednost .= "('$rowC[id]', '-2', '$this->usr_id'),";
											}
										}												
									}
								}
								
								// multinumber
								elseif ($rowC['tip'] == 20) {

									$sql1 = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id = '$rowC[id]'");
									
									while ($row1 = mysqli_fetch_array($sql1)) {				
										$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$rowC[id]' ORDER BY vrstni_red");
										
										while ($row2 = mysqli_fetch_array($sql2)) {                          												
											if ($s->checkSpremenljivka($rowC['id'], $isTestData=true)) {
											
												// Ce imamo samo veljavne imamo vedno vrednost
												if($only_valid == 1)
													$value = $this->randomNumber();
												else
													$value = rand(0,1)==0 ? $this->randomNumber() : '';
												
												$grid_id = $row2['id'];

												if ($value != '') {
													$srv_data_textgrid .= "('$rowC[id]', '$row1[id]', '$this->usr_id', '$grid_id', '$value'),";
												}
												
												// vsebino text polja vnesemo v vsakem primeru
												if ($row1['other'] == 1 && $value != '')
													$srv_data_text .= "('$rowC[id]', '$row1[id]', '".$this->randomString()."', '', '$this->usr_id'),";				
											}								
																		
											else {
												$srv_data_vrednost .= "('$rowC[id]', '-2', '$this->usr_id'),";
											}
										}												
									}
								}
								
							}
						}
					}
					
					// vprasanja shranjujemo sproti, zaradi IFov !!!
					
					// odrezemo zadnjo vejico, ker smo jo dodajali kar povsod
					$srv_data_grid = substr($srv_data_grid, 0, -1);
					$srv_data_vrednost = substr($srv_data_vrednost, 0, -1);
					$srv_data_text = substr($srv_data_text, 0, -1);
					$srv_data_checkgrid = substr($srv_data_checkgrid, 0, -1);
					$srv_data_textgrid = substr($srv_data_textgrid, 0, -1);
					$srv_data_rating = substr($srv_data_rating, 0, -1);
                    $srv_data_map = substr($srv_data_map, 0, -1);
                    $srv_data_heatmap = substr($srv_data_heatmap, 0, -1);
								
					if ($srv_data_grid != '') {		$sq = sisplet_query("INSERT INTO srv_data_grid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES $srv_data_grid");		if (!$sq) echo 'err011: '.mysqli_error($GLOBALS['connect_db']); }
					if ($srv_data_vrednost != '') {	$sq = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES $srv_data_vrednost");		if (!$sq) echo 'err012: '.mysqli_error($GLOBALS['connect_db']); }
					if ($srv_data_text != '') {		$sq = sisplet_query("INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, text, text2, usr_id) VALUES $srv_data_text");						if (!$sq) echo 'err013: '.mysqli_error($GLOBALS['connect_db']); }
					if ($srv_data_checkgrid != ''){ $sq = sisplet_query("INSERT INTO srv_data_checkgrid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES $srv_data_checkgrid");				if (!$sq) echo 'err014: '.mysqli_error($GLOBALS['connect_db']); }
					if ($srv_data_textgrid != '') {	$sq = sisplet_query("INSERT INTO srv_data_textgrid".$this->db_table." (spr_id, vre_id, usr_id, grd_id, text) VALUES $srv_data_textgrid");			if (!$sq) echo 'err015: '.mysqli_error($GLOBALS['connect_db']); }
					if ($srv_data_rating != '') {	$sq = sisplet_query("INSERT INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red) VALUES $srv_data_rating");					if (!$sq) echo 'err016: '.mysqli_error($GLOBALS['connect_db']); }
                    if ($srv_data_map != '') {	$sq = sisplet_query("INSERT INTO srv_data_map (usr_id, spr_id, vre_id, ank_id, lat, lng, address, text, vrstni_red) VALUES $srv_data_map");					if (!$sq) echo 'err016: '.mysqli_error($GLOBALS['connect_db']); }                                        
					if ($srv_data_heatmap != '') {	$sq = sisplet_query("INSERT INTO srv_data_heatmap (usr_id, spr_id, vre_id, ank_id, lat, lng, address, text, vrstni_red) VALUES $srv_data_heatmap");					if (!$sq) echo 'err017: '.mysqli_error($GLOBALS['connect_db']); }                                        
				}
				
				$recnum++;
			}
			# zabeležimo kdaj so bili dodani testni vnosi
			if (count($arrayTestni)) {
				global $global_user_id;
				$ins_date = date ("Y-m-d H:m:s");
				
				$insert_qry = "INSERT INTO srv_testdata_archive (ank_id, add_date, add_uid, usr_id) VALUES ";
				$prefix = '';
				foreach ($arrayTestni AS $at_user_id) {
					$insert_qry .= $prefix."('".$this->anketa."', '$ins_date', '$global_user_id', '$at_user_id')";
					$prefix = ', ';
				}
				
				sisplet_query($insert_qry);
			}
			
			if(session_id() == '') {session_start();}
			$_SESSION['progressBar'][$this->anketa]['status'] = 'end';
			session_commit();
			unset($_SESSION['progressBar'][$this->anketa]);	// ce getCollectTimer ne prebere vec 'end' (se prehitro refresha), se tukaj odstranimo sejo
			
			header("Location: index.php?anketa=$this->anketa&a=testiranje&m=testnipodatki");
			
		} elseif ($_GET['delete_testdata'] == 1 || $_GET['delete_autogen_testdata'] == 1) {
			
			if($_GET['delete_autogen_testdata'] == 1)
				sisplet_query("DELETE FROM srv_user WHERE ank_id='$this->anketa' AND testdata='2'");
			else
				sisplet_query("DELETE FROM srv_user WHERE ank_id='$this->anketa' AND (testdata='1' OR testdata='2')");

			#datoteki z podatki moramo zgenerirati na novo
			sisplet_query("UPDATE srv_data_files SET head_file_time='0000-00-00', data_file_time='0000-00-00' WHERE sid='$this->anketa'");
			sisplet_query("COMMIT");
			
			header("Location: ".$_SERVER['HTTP_REFERER']);
			
		// izpis podatkov
		} else {
			
			$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_user WHERE ank_id='$this->anketa' AND (testdata='1' OR testdata='2')");
			$row = mysqli_fetch_array($sql);
			$total_rows = $row['count'];
			
			echo '<form name="" action="ajax.php?anketa='.$this->anketa.'&a=testiranje&m=testnipodatki" method="post" onsubmit="init_progressBar(true);">';
			echo '<p>';
			echo '<span class="spaceRight">'.$lang['srv_stevilo_vnosov'].': <input type="text" name="stevilo_vnosov" value="1" onkeyup="max_stevilo_vnosov();"> (max 1000) </span>';
			echo '<input type="hidden" name="only_valid" id="only_valid_0" value="0" />';
			echo '<span style="margin: 0 25px;"><label for="only_valid_1">'.$lang['srv_testni_samo_veljavni'].': <input type="checkbox" name="only_valid" id="only_valid_1" value="1"></label></span>';
			echo '<span class="spaceLeft"><input type="submit" name="" value="'.$lang['srv_dodaj_vnose'].'" /></span>';
			echo '</p>';
			echo '</form>';
			
			echo '<p>'.$lang['srv_testni_nagovor'].'</p>';
			
			echo '<a href="#" onClick="delete_test_data();">'.$lang['srv_delete_testdata'].'</a> ('.$total_rows.')';
			
			if ($total_rows > 0) {
				
				
				$prevpage = 0;
				
				$sql = sisplet_query("SELECT s.id, s.gru_id, s.tip, s.naslov, g.naslov AS pagename FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND s.visible='1' AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red, s.vrstni_red");
				while ($row = mysqli_fetch_array($sql)) {
					
					// labela in compute ne upostevamo
					if ($row['tip'] != 5 && $row['tip'] != 22) {
					
						if ($prevpage == 0 || $row['gru_id'] != $prevpage) {
							
							if ($prevpage > 0) {
								echo '</table>';
								echo '</fieldset>';
							}
							
							echo '<fieldset><legend>'.$row['pagename'].'</legend>';
							echo '<table style="width:100%">';
							
							$prevpage = $row['gru_id'];
						}
						
						echo '<tr><td style="width:20%; text-align:left" title="'.strip_tags($row['naslov']).'">'.skrajsaj(strip_tags($row['naslov']),20).'</td>';
						
						// radio ali select, checkbox, textbox, textbox*, number, datum
						if ( ($row['tip']==1 || $row['tip']==3 || $row['tip']==2 || $row['tip']==4 || $row['tip']==21 || $row['tip']==7 || $row['tip']==8) ) {
							
							$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_vrednost".$this->db_table." dv, srv_user u WHERE dv.usr_id=u.id AND (u.testdata='1' OR u.testdata='2') AND spr_id='$row[id]' AND vre_id='-2'");
							$rowc = mysqli_fetch_array($sqlc);
							$p = round(($total_rows-$rowc['count'])/$total_rows*100,2);
	                        
						// multigrid
						} elseif ($row['tip'] == 6) {

							$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
							while ($row1 = mysqli_fetch_array($sql1)) {

	                            $sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_grid".$this->db_table." dg, srv_user u WHERE dg.usr_id=u.id AND (u.testdata='1' OR u.testdata='2') AND spr_id='$row[id]' AND vre_id='$row1[id]' AND grd_id='-2'");
								$rowc = mysqli_fetch_array($sqlc);
								$p = round(($total_rows-$rowc['count'])/$total_rows*100,2);	
	                        }
	                    
						// multicheckbox
						} elseif ($row['tip'] == 16) {

							$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
							
							while ($row1 = mysqli_fetch_array($sql1)) {
								$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
								
								while ($row2 = mysqli_fetch_array($sql2)) {
									
									$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_checkgrid".$this->db_table." cg, srv_user u WHERE cg.usr_id=u.id AND (u.testdata='1' OR u.testdata='2') AND spr_id='$row[id]' AND vre_id='$row1[id]' AND grd_id='-2'");
									$rowc = mysqli_fetch_array($sqlc);
									$p = round(($total_rows-$rowc['count'])/$total_rows*100,2);
								}												
							}
							
						}
	                	
	                	// multitext, multinumber
						elseif ($row['tip'] == 19 || $row['tip'] == 20) {

							$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
							
							while ($row1 = mysqli_fetch_array($sql1)) {				
								$sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
								
								while ($row2 = mysqli_fetch_array($sql2)) {                          												
									
									$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_textgrid".$this->db_table." tg, srv_user u WHERE tg.usr_id=u.id AND (u.testdata='1' OR u.testdata='2') AND spr_id='$row[id]' AND vre_id='$row1[id]' AND grd_id='0' AND text='-2'");
									$rowc = mysqli_fetch_array($sqlc);
									$p = round(($total_rows-$rowc['count'])/$total_rows*100,2);
								}												
							}
							
						}
						
						// ranking, vsota
						elseif ($row['tip'] == 17 || $row['tip'] == 18) {
						
								$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
								while($row1 = mysqli_fetch_array($sql1)){
								
									$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_text".$this->db_table." dt, srv_user u WHERE dt.usr_id=u.id AND (u.testdata='1' OR u.testdata='2') AND spr_id='$row[id]' AND vre_id='$row1[id]' AND text='-2'");
									$rowc = mysqli_fetch_array($sqlc);
									$p = round(($total_rows-$rowc['count'])/$total_rows*100,2);
								}							
						}
						
						echo '<td>';
						echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($p*0.7).'%">&nbsp;</div>';
						echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$p.'% ('.($total_rows-$rowc['count']).')</span>';
						echo '</td>';
						
						echo '</tr>';
						
					}
				}
				
				echo '</table>';
				echo '</fieldset>';
			
				echo '<p>'.$lang['srv_testni_nakonec'].'</p>';
			}		
		}
	}
	
	/**
	* zgenerira random string za vpis v tekstovno polje
	* 
	*/
	function randomString ($length = 10, $chars = 'abcdefghijklmnopqrstuvwxyz') {
	    // Length of character list
	    $chars_length = (strlen($chars) - 1);

	    // Start our string
	    $string = $chars[mt_rand(0, $chars_length)];
	   
	    // Generate random string
	    for ($i = 1; $i < $length; $i = strlen($string))
	    {
	        // Grab a random character from our list
	        $r = $chars[mt_rand(0, $chars_length)];
	       
	        // Make sure the same two characters don't appear next to each other
	        if ($r != $string[$i - 1]) $string .=  $r;
	    }
	   
	    // Return the string
	    return $string;
	}
	
	function randomNumber ($length = 4, $chars = '0123456789') {
		return $this->randomString($length, $chars);
	}
	
	function randomDate ($startDate = '01.01.1950', $endDate = '') {
		if ($endDate == '') $endDate = date("d.m.Y");
	    $days = round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24));
	    $n = rand(0,$days);
	    return date("d.m.Y",strtotime("$startDate + $n days"));   
	}
	
	function displayBtnMailtoPreview($row) {
		global $lang;
		echo '<div class="floatLeft"><div class="buttonwrapper">';
		echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="'.( ($row['active'] != 1) ? 'genericAlertPopup(\'srv_anketa_noactive2\'); ' : 'preview_mailto_email(); ').'return false;">';
		echo '<span>';
		//'<img src="icons/icons/accept.png" alt="" vartical-align="middle" />'
		echo  $lang['srv_mailto_preview'] . '</span></a></div></div>';

	}
	
	function DisplayNastavitveTrajanje() {	
		global $lang;
		global $site_url;
		
		# vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();
		$row = SurveyInfo::getInstance()->getSurveyRow();

		$starts =  date('d.m.Y',strtotime($row['starts']));
		$_expire = explode('-',$row['expire']);
		$expire =  $_expire[2].'.'.$_expire[1].'.'.$_expire[0];
		
		echo '<fieldset><legend>'.$lang['srv_activate_duration_2'].' '.Help::display('srv_activity_quotas').'</legend>';
		echo '<p>';
		echo '<span class="duration_span">' . $lang['srv_activate_duration_manual_from'].'</span>';
		echo '<input id="startsManual1" type="text" name="durationStarts" value="' . $starts . '" disabled autocomplete="off"/>';
		echo '</p>';
		
		echo '<p>';
		echo '<span class="duration_span">' . $lang['srv_activate_duration_manual_to'].'</span>';
		echo '<input id="expireManual1" type="text" name="durationExpire" value="' . $expire . '" disabled autocomplete="off"/>';
		echo '</p>';
		
		echo '<p>';
		echo '<span class="duration_span">' . $lang['srv_trajna_anketa'].'</span>';
		echo '<input id="expirePermanent" type="checkbox" name="expirePermanent" value="1"'.($row['expire'] == PERMANENT_DATE ? ' checked="checked"' : '').' autocomplete="off" onchange="setExpirePermanent();"/>';
		echo '</p>';
		echo '</fieldset>';
		echo '</div>';
		
		#echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="anketa_activate_save(\''.$this->anketa.'\',\''.$folders.'\'); return false;"><span>' . $lang['srv_zapri'] . '</span></a></div></span>';
		
		echo '
			<script type="text/javascript">
				$(document).ready(function () {
                    datepicker("#startsManual1");
                    datepicker("#expireManual1");
				});
			</script>';	
	}
	
	function DisplayNastavitveMaxGlasov() {
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		//Nastavitev max stevila glasov
		echo '<br />';
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_vote_quotas'].' '.Help::display('srv_activity_quotas').'</legend>';
		echo '<p>';
		echo '<span class="duration_span" ><label>' . $lang['srv_vote_quotas_2'] . ':</label></span>';
		echo '<label for="vote_limit_0"><input type="radio" name="vote_limit" value="0" id="vote_limit_0"' . ($row['vote_limit'] == 0 ? ' checked="checked"' : '') . ' onClick="voteCountStatus(0)" />' . $lang['no1'] . '</label>';
		echo '<label for="vote_limit_1"><input type="radio" name="vote_limit" value="1" id="vote_limit_1"' . ($row['vote_limit'] == 1 ? ' checked="checked"' : '') . ' onClick="voteCountStatus(1)" />' . $lang['yes'] . '</label>';
		echo '<label for="vote_limit_2"><input type="radio" name="vote_limit" value="2" id="vote_limit_2"' . ($row['vote_limit'] == 2 ? ' checked="checked"' : '') . ' onClick="voteCountStatus(2)" />' . $lang['srv_data_only_valid'] . ' (status 5, 6) '.Help::display('srv_activity_quotas_valid').'</label>';
		
		echo '&nbsp;&nbsp;&nbsp;&nbsp;<span class="duration_span"><label for="anketa' . $row['id'] . '" >'.$lang['srv_vote_count'].': </span>';
		echo '<input type="text" id="vote_count" name="vote_count" ' . ($row['vote_limit'] == 0 ? ' disabled="disabled"' : '') . ' value="' . $row['vote_count'] . '" style="width:50px; margin-left: 5px;" maxlength="40" /></label>';
		echo '</p>';
		
		
		// Opozorilo, da je limit odgovorov presezen
		echo '<p class="vote_limit_warning" style="padding-left:10px; line-height:20px; '.($row['vote_limit'] == 0 ? ' display:none;' : '').'">';		
		echo $lang['srv_survey_voteLimit_warning'].':<br />';
		
		$srv_survey_non_active_voteLimit = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active_voteLimit');
		if ($srv_survey_non_active_voteLimit == '') $srv_survey_non_active_voteLimit = $lang['srv_survey_non_active_voteLimit'];				
		echo '<span class="italic spaceLeft">'.$srv_survey_non_active_voteLimit.'</span>';
		
		echo ' <a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=jezik">'.$lang['edit3'].'</a>';	
		echo '</p>';
		
		echo '</fieldset>';
	}
	
    /**
     * Uporabnik (administrator, manager) lahko dodajata nove uporabnike in jim dodelita dostop
     */
    public function dodajNovegaUporabnika(){
        global $admin_type;
        global $lang;

        // admini lahko dodajajo uporabnike, ki jih nato managirajo 
        if($admin_type != 0)
            return '';

        echo '<div id="dodajanjeNovega">';

        echo '<form class="manager_add_user" name="admin_add_user" action="ajax.php?t=dostop&a=add_new_user" method="post">';

        echo '<h3><b>'.$lang['srv_users_add_new_title'].'</b></h3>';

        echo '<p><label for="email">'.$lang['email'].':</label><input type="email" id="email" name="email"> '.(!empty($_GET['add']) && $_GET['error']=='email'?'<span class="red">'.$lang['srv_added_false'].'</span>':'').'</p>';
        echo '<p><label for="name">'.$lang['name'].':</label><input type="text" id="name" name="name"></p>';
        echo '<p><label for="surname">'.$lang['surname'].':</label><input type="text" id="surname" name="surname"></p>';
        echo '<p><label for="password">'.$lang['password'].':</label><input type="password" id="password" name="password"> '.(!empty($_GET['add']) &&  $_GET['error']=='pass'?'<span class="red">'.$lang['pass_doesnt_match'].'</span>':'').'</p>';
        echo '<p><label for="password2">'.$lang['again'].':</label><input type="password" id="password2" name="password2"></p>';
        echo '<p><label for="jezik">'.$lang['lang'].':</label>
                    <select id="jezik" name="jezik">
                        <option value="1" selected>'.$lang['srv_diagnostics_filter_lang_slo'].'</option>
                        <option value="2">'.$lang['srv_diagnostics_filter_lang_ang'].'</option>
                    </select>
                </p>';
                
        //echo '<p><button type="submit">'.$lang['add'].'</button></p>';
        echo '<p><div class="buttonwrapper floatLeft">';
        echo '  <a class="ovalbutton ovalbutton_orange" href="#" onclick="document.admin_add_user.submit();">'.$lang['create'].'</a>';
        echo '</div></p>';

        echo '</form>';

        echo '</div>';
    }

	/**
	 * Uporabnik (administrator, manager) lahko dodajata nove uporabnike in jim dodelita dostop
	 */
	public function dodeljeniUporabniki(){
	    global $admin_type;
	    global $lang;
	    global $global_user_id;

        // managerji in admini lahko dodajajo uporabnike, ki jih nato managirajo
        if( !($admin_type == 1 || $admin_type == 0) )
            return '';

        // Na virtualkah imajo managerji omejitev st. dodeljenih uporabnikov - ZAENKRAT JE TO ONEMOGOCENO, KASNEJE SE LAHKO OMEJI NA PAKET
        if(false && isVirtual() && $admin_type == 1){

            // Limit st. dodeljenih uporabnikov
            $managed_accounts_limit = 5;

            // Prestejemo dodeljene uporabnike
            $sql = sisplet_query("SELECT u.email
                                    FROM srv_dostop_manage m, users u
                                    WHERE m.manager='".$global_user_id."' AND u.id=m.user AND u.email NOT LIKE ('D3LMD-%') AND u.email NOT LIKE ('UNSU8MD-%')
                                ");

            $managed_accounts_count = mysqli_num_rows($sql);


            echo '<p class="bold" style="padding-left:0px;">';
            echo $lang['srv_users_add_assigned_max_1'].' <span class="red bold">'.$managed_accounts_limit.'</span> '.$lang['srv_users_add_assigned_max_2'];

            // Manager na virtualkah ima omejitev koliko uporabnikov lahko pregleduje
            if($managed_accounts_count >= $managed_accounts_limit){
                echo '<br /><br />';
                echo $lang['srv_users_add_assigned_max_reached'];
                echo '</p>';
                
                return;
            }
            elseif($managed_accounts_count > 0){             
                echo '<br /><br />';
                echo $lang['srv_users_add_assigned_current'].' <span class="red bold">'.$managed_accounts_count.' '.$lang['of'].' '.$managed_accounts_limit.'</span>';
            }

            echo '</p>';
        }

        echo '<div id="dodajanje">';

        // Dodajanje novih uporabnikov - ustvari racun, doda uporabnika pod pregled in mu poslje mail
        echo '<form class="manager_add_user" name="manager_add_user" action="ajax.php?t=dostop&a=manager_add_user" method="post">';
        echo '<h3><b>'.$lang['srv_users_add_assigned_title'].'</b></h3>';
        echo '<p><label for="email">'.$lang['email'].':</label><input type="email" id="email" name="email"> '.(empty($_GET['add']) && $_GET['error']=='email'?'<span class="red">'.$lang['srv_added_false'].'</span>':'').'</p>';
        echo '<p><label for="name">'.$lang['name'].':</label><input type="text" id="name" name="name"></p>';
        echo '<p><label for="surname">'.$lang['surname'].':</label><input type="text" id="surname" name="surname"></p>';
        echo '<p><label for="password">'.$lang['password'].':</label><input type="password" id="password" name="password"> '.(empty($_GET['add']) && $_GET['error']=='pass'?'<span class="red">'.$lang['pass_doesnt_match'].'</span>':'').'</p>';
        echo '<p><label for="password2">'.$lang['again'].':</label><input type="password" id="password2" name="password2"></p>';
        
        echo '<p><div class="buttonwrapper floatLeft">';
        echo '  <a class="ovalbutton ovalbutton_orange" href="#" onclick="document.manager_add_user.submit();">'.$lang['create_add'].'</a>';
        echo '</div></p>';

        echo '</form>';

        // admini si lahko dodajajo ze obstojece uporabnike
        if ($admin_type == 0) {

            echo '<br /><br /><br />';

            echo '<form class="manager_add_user" name="admin_add_dostop" action="ajax.php?t=dostop&a=admin_add_user" method="post">';

            echo '<h3><b>'.$lang['srv_manager_add_user2'].'</b></h3>';
            echo '<p><select name="uid" class="js-obstojeci-uporabniki-admin-ajax" style="width: 500px;"></select></p>';
            
            //echo '<p><button type="submit">'.$lang['add'].'</button></p>';
            echo '<p><div class="buttonwrapper floatLeft">';
            echo '  <a class="ovalbutton ovalbutton_orange" href="#" onclick="document.admin_add_dostop.submit();">'.$lang['add'].'</a>';
            echo '</div></p>';

            echo '</form>';
        }
        // Managerji lahko dodajajo samo uporabnike z dolocenimi emaili (če jim domeno posebej nastavi admin)
        // TODO: trenutno onemogočimo dodaja ostalih uporabnikov za managerje. Ko bo stvar vezana na domeno se jim bo omogočilo dodajanje samo domenskih
        /*elseif(false && $admin_type == 1){

            UserSetting :: getInstance()->Init($global_user_id);
            $emails = UserSetting :: getInstance()->getUserSetting('manage_domain');

            echo '<br><form class="manager_add_user" action="ajax.php?t=dostop&a=admin_add_user" method="post">';
            echo '<h3><b>'.sprintf($lang['srv_manager_add_user3'], $emails).'<br />'.$lang['srv_manager_add_user4'].'</b></h3>';
            echo '<p><input name="uemail" value="" style="width: 500px;" id="manager-email"><span id="manager-email-obvestilo"></span></p>';
            echo '<p><button type="submit" id="manager-email-submit" style="display:none;">'.$lang['add'].'</button></p>';

            echo '</form>';
        }*/

        echo '</div>';
    }

	/**
	 * Seznam vseh uporabnikov znotraj 1ke
	 */
	public function allUsersList(){
		global $lang;
		global $admin_type;

		echo '<table id="all_users_list" class="dataTable">';
        
        echo '<thead><tr>';
        echo '<th>' . $lang['srv_survey_list_users_name'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_email'] . '</th>';
        echo '<th>' . $lang['admin_type'] . '</th>';
        echo '<th>' . $lang['lang'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_aai'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_survey_count'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_survey_archive_count'] . '</th>';
        echo '<th>' . $lang['srv_manager_count'] . '</th>';
        echo '<th>' . $lang['srv_manager_count_manager'] . '</th>';
        echo '<th>' . $lang['users_gdpr_title'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_registred'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_last_login'] . '</th>';
        echo '<th style="max-width: 70px;">'.$lang['edit2'].'</th>';
        echo '</tr></thead>';
        
        echo '</table>';
        
        // Dodajanje uporabnikov
        echo '<div class="add_user">';

        // Admin lahko doda novega uporabnika v sistem (brez pregleda)
        if($admin_type == '0'){
            echo '<fieldset class="new_user"><legend>'.$lang['srv_users_add_new'].'</legend>';
            $this->dodajNovegaUporabnika();
            echo '</fieldset>';
        }

        echo '</div>';
    }
    
    /**
	 * Osnovni pregled uporabnikov za managerje in admine
	 */
	public function assignedUsersList(){
		global $lang;
		global $admin_type;
		global $global_user_id;
        
        $sqlU = sisplet_query("SELECT name, surname, email FROM users WHERE id='".$global_user_id."'");
        $rowU = mysqli_fetch_array($sqlU);


        // Naslov
        echo '<h2 style="margin-bottom:30px;">';

        if($admin_type == 0)
            echo $lang['administrator'];
        elseif($admin_type == 1)
            echo $lang['manager'];
        else
            echo $lang['user'];

        echo ': '.$rowU['name'].' '.$rowU['surname'].' ('.$rowU['email'].')';

        echo ' <a href="#" onclick="edit_user(\''.$global_user_id.'\'); return false;" title="Uredi"><i class="fa fa-pencil-alt link-moder"></i></a>';

        echo '</h2>';


        // Tabela
        echo '<fieldset style="max-width: 100% !important;"><legend>'.$lang['srv_users_assigned_title'].'</legend>';
        //echo '<h4 style="margin-bottom: 10px;">'.$lang['srv_users_assigned_title'].'</h4>';
        echo '<table id="my_users_list" class="dataTable">';

        echo '<thead><tr>';
        echo '<th>' . $lang['srv_survey_list_users_name'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_email'] . '</th>';
        echo '<th>' . $lang['admin_type'] . '</th>';
        echo '<th>' . $lang['lang'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_aai'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_survey_count'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_survey_archive_count'] . '</th>';
        echo '<th>' . $lang['users_gdpr_title'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_registred'] . '</th>';
        echo '<th>' . $lang['srv_survey_list_users_last_login'] . '</th>';
        echo '<th style="max-width: 70px;">'.$lang['edit2'].'</th>';
        echo '</tr></thead>';

        echo '</table>';
        echo '</fieldset>';


        // Dodajanje uporabnikov
        echo '<div class="add_user">';

        // Manager ali admin lahko doda novega uporabnika pod pregled
        echo '<fieldset class="assign_user"><legend>'.$lang['srv_users_add_assigned'].'</legend>';
		$this->dodeljeniUporabniki();
        echo '</fieldset>';

        echo '</div>';
	}

	/**
	 * Seznam vseh izbrisanih uporabnikov znotraj 1ke
	 */
	public function deletedUsersList(){
	    global  $lang; 

	    	echo '<table id="deleted_users_list" class="dataTable">';
                echo '<thead><tr>';
                    echo '<th>'.$lang['srv_survey_list_users_name'].'</th>';
                    echo '<th>'.$lang['srv_survey_list_users_email'].'</th>';
                    echo '<th>'.$lang['admin_type'].'</th>';
			        echo '<th>'.$lang['lang'].'</th>';
                    echo '<th>'.$lang['registered'].'</th>';
                echo '</tr></thead>';
			echo '</table>';

    }

	/**
	 * Seznam vseh odjavljenih uporabnikov
     * V bazi vsi uporabniki, ki so odjavljeni samo pridobijo status 0
	 */
    public function unsignedUsersList(){
	    global  $lang;

	    echo '<table id="unsigned_users_list" class="dataTable">';
	    echo '<thead><tr>';
	    echo '<th>'.$lang['srv_survey_list_users_name'].'</th>';
	    echo '<th>'.$lang['srv_survey_list_users_email'].'</th>';
	    echo '<th>'.$lang['admin_type'].'</th>';
	    echo '<th>'.$lang['lang'].'</th>';
	    echo '<th>'.$lang['registered'].'</th>';
	    echo '</tr></thead>';
	    echo '</table>';
    }

	/**
	 * Seznam vseh uporabnikov, ki so prejeli email in ga niso potrdili
	 */
    public function unconfirmedMailUsersList(){
	    global  $lang;

	    echo '<table id="unconfirmed_mail_user_list" class="dataTable">';
	    echo '<thead><tr>';
	    echo '<th>'.$lang['srv_survey_list_users_name'].'</th>';
	    echo '<th>'.$lang['srv_survey_list_users_email'].'</th>';
	    echo '<th>'.$lang['admin_type'].'</th>';
	    echo '<th>'.$lang['lang'].'</th>';
	    echo '<th>'.$lang['registered'].'</th>';
	    echo '<th style="width: 90px;">'.$lang['edit2'].'</th>';
	    echo '</tr></thead>';
	    echo '</table>';
    }

	/**
	 * Seznam uporabnikov, ki imajo dostop do SA modula
	 */
	public function SAuserListIndex(){
		global $lang, $global_user_id, $admin_type;

		if($admin_type > 0)
			return false;

		$sql_uporabniki = sisplet_query("SELECT id, u.name, u.surname, u.email, d.ustanova, d.aai_email, DATE_FORMAT(d.created_at, '%d.%m.%Y - %H:%i') AS created_at, d.updated_at FROM srv_hierarhija_dostop AS d LEFT JOIN users AS u ON u.id=d.user_id ORDER BY u.name", "obj");

		echo '<a href="#" onclick="dodeliSAdostopUporabniku()">Dodaj uporabniku SA dostop</a><br /><br />';

		if(empty($sql_uporabniki)){
			echo $lang['srv_hierarchy_users_access_no_data'];
			return false;
		}


		if(!empty($sql_uporabniki->name)) {
					$uporabniki[0] = $sql_uporabniki;
        }else{
		    $uporabniki = $sql_uporabniki;
        }

		echo '<table class="datatables" id="sa-users-table">';
		echo '<tr>';
		echo '<th class="text-left">'.$lang['srv_hierarchy_users_name'].'</th>';
		echo '<th>'.$lang['srv_hierarchy_users_email'].'</th>';
		echo '<th>'.$lang['srv_hierarchy_users_organization'].'</th>';
		echo '<th class="text-right">'.$lang['srv_hierarchy_users_created'].'</th>';
		echo '<th></th>';
		echo '</tr>';


		foreach($uporabniki as $uporabnik) {
			echo '<tr>';
			echo '<td class="text-left">'.$uporabnik->name .' '. $uporabnik->surname.'</td>';
			echo '<td>'.$uporabnik->email.'</td>';
			echo '<td>'.$uporabnik->ustanova .'</td>';
			echo '<td class="text-right">'.$uporabnik->created_at.'</td>';
            echo '<td class="akcija">';
                echo '<div>';
                    echo '<a href="#" onclick="preveriSAuporabnika(\''.$uporabnik->id.'\')">'.$lang['srv_dataIcons_quick_view'].'</a>';
                    echo '<a href="#"  onclick="urediSAuporabnika(\''.$uporabnik->id.'\')">'.$lang['srv_recode_edit'].'</a>';
                    echo '<a href="#"  onclick="izbrisiSAuporabnika(\''.$uporabnik->id.'\')">'.$lang['srv_recode_remove'].'</a>';
                echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

	}

	// Prikaz naprednih modulov - NOVO (v urejanje->nastavitve)
	function showAdvancedModules(){
		global $lang, $site_url, $global_user_id, $admin_type;

		# preberemo osnovne nastavitve 
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		# preberemo vklopljene module
		$modules = SurveyInfo::getSurveyModules();
		
		$disabled = '';
		$css_disabled = '';
		if (isset($modules['slideshow'])){
			$disabled = ' disabled="disabled"';
			$css_disabled = ' gray';
		}

		if ($_GET['a'] == 'uporabnost'){
		
			echo '<fieldset><legend>'.$lang['srv_uporabnost'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_uporabnost" name="uporabnost" value="1"'. (isset($modules['uporabnost']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'uporabnost\');" />';
			echo $lang['srv_vrsta_survey_type_4'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_4_3'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
		
			echo '<div id="globalSettingsInner">';
			if(isset($modules['uporabnost'])){
				$this->uporabnost();
			}
			echo '</div>';
		}
		elseif (($_GET['a'] == A_HIERARHIJA_SUPERADMIN) && Hierarhija\HierarhijaHelper::preveriDostop($this->anketa)){

			// Blok za vklop in izklop hierarhije skrijemo, če je hierarhija aktivna
			if(!SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
				echo '<fieldset><legend>' . $lang['srv_hierarchy'] . '</legend>';
				echo '<i class="' . $css_disabled . '">' . $lang['srv_hierarchy_description'] . '</i>';
				echo '<label class="strong' . $css_disabled . '"><input type="checkbox" id="advanced_module_hierarhija" name="hierarhija" value="1"' . (isset($modules['hierarhija']) ? ' checked="checked"' : '') . $disabled . ' onChange="preveriAnketoZaHierarhijo('.$this->anketa.');" />';
				echo $lang['srv_hierarchy_on'] . '</label>';

				echo '<div id="hierarhija-opcije-vklopa">';
				    echo '<h4>'.$lang['srv_hierarchy_intro_select_title'].':</h4>';
				    echo '<div id="error"></div>';
                    echo '<label class="strong' . $css_disabled . '"><input type="radio" id="obstojeca-anketa" name="izberi-anketo" value="obstojeca" checked="checked"/>'.$lang['srv_hierarchy_intro_option_current'].'</label>';
                    echo '<label class="strong' . $css_disabled . '"><input type="radio" id="prevzeta-anketa" name="izberi-anketo" onclick="pridobiKnjiznicoZaHierarhijo(\'privzeta\')" value="prevzeta" />'.$lang['srv_hierarchy_intro_option_default'].' <span id="hierarhija-prevzeta"></span></label>';
                    echo '<label class="strong' . $css_disabled . '"><input type="radio" name="izberi-anketo" value="knjiznica" onclick="pridobiKnjiznicoZaHierarhijo(\'vse\')"/>'.$lang['srv_hierarchy_intro_option_library'].'</label>';
                    echo '<div id="hierarhija-knjiznica">';
                    echo '</div>';
                    echo '<span class="floatLeft spaceRight" style="padding:15px 0;"><div class="buttonwrapper">';
                        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="potrdiIzbiroAnkete(); return false;" style="padding-right: 5px;>
                                <span style="color:#fff;">Vklopi modul</span>
                              </a>';
                    echo '</div></span>';
                echo '</div>';
				echo '</fieldset>';
				echo '<div id="globalSettingsInner" style="padding-top: 15px;">';
				echo '</div>';
			}else {

				$hierarhija = new \Hierarhija\Hierarhija($this->anketa);


				echo '<div id="hierarhija-container">';
                    echo '<div style="width:586px;">';
                        $hierarhija->displayHierarhijaNavigationSuperAdmin();
                    echo '</div>';

                    echo '<div id="globalSettingsInner" style="padding-top: 15px;">';

                    if($_GET['m'] == M_ADMIN_UREDI_SIFRANTE){

                        $hierarhija->hierarhijaSuperadminSifranti();

                    }elseif($_GET['m'] == M_ADMIN_UVOZ_SIFRANTOV){

                        $hierarhija->hierarhijaSuperadminUvoz();

                    }elseif($_GET['m'] == M_ADMIN_UPLOAD_LOGO){

                        $hierarhija->hierarhijaSuperadminUploadLogo();

                    }elseif($_GET['m'] == M_ADMIN_IZVOZ_SIFRANTOV){

                        $hierarhija->izvozSifrantov();

                    }elseif($_GET['m'] == M_ANALIZE){

                        if($_GET['r'] == 'custom'){
                            $HC = new \Hierarhija\HierarhijaPorocilaClass($this->anketa);
                            $HC->izvoz();
                        }else {
                            $HA = new HierarhijaAnalysis($this->anketa);
                            $HA->Display();
                        }

                    }elseif($_GET['m'] == M_HIERARHIJA_STATUS){

                        if($_GET['izvoz'] == 'status'){
                          // Izvoz tabele status
                          \Hierarhija\HierarhijaIzvoz::getInstance($this->anketa)->csvIzvozStatusa();
                        }else {
                          $hierarhija->statistikaHierjearhije();
                        }

                    }elseif($_GET['m'] == M_ADMIN_AKTIVACIJA){

                        $hierarhija->aktivacijaHierarhijeInAnkete();

                    }elseif($_GET['m'] == M_ADMIN_KOPIRANJE){

                        $hierarhija->kopiranjeHierarhijeInAnkete();

                    }elseif($_GET['m'] ==  M_UREDI_UPORABNIKE && $_GET['izvoz'] == 1) {
                        // za vse ostalo je ure uredi uporabnike - M_UREDI_UPORABNIKE
                        \Hierarhija\HierarhijaIzvoz::getInstance($this->anketa)->csvIzvozVsehUporabnikov();
                    }elseif($_GET['m'] ==  M_UREDI_UPORABNIKE && $_GET['izvoz'] == 'struktura-analiz') {
                        // za vse ostalo je uredi uporabnike - M_UREDI_UPORABNIKE
                        if(!empty($_GET['n']) && $_GET['n'] == 1){
                            \Hierarhija\HierarhijaIzvoz::getInstance($this->anketa)->csvIzvozStruktureZaObdelavo(false, true);
                        }else {
                            \Hierarhija\HierarhijaIzvoz::getInstance($this->anketa)->csvIzvozStruktureZaObdelavo();
                        }
                    }else{
                        $hierarhija->izberiDodajanjeUporabnikovNaHierarhijo();
                    }


                    echo '</div>';
				echo '</div>';
			}
		}
		elseif ($_GET['a'] == 'kviz'){
			
			echo '<fieldset><legend>'.$lang['srv_kviz'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_quiz" name="quiz" value="1" '. (isset($modules['quiz']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'quiz\');" />';
			echo $lang['srv_vrsta_survey_type_6'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_6_4'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
		
			echo '<div id="globalSettingsInner">';
			if(isset($modules['quiz'])){
				$sq = new SurveyQuiz($this->anketa);
				$sq->displaySettings();
			}
			echo '</div>';
		} 
        elseif ($_GET['a'] == 'voting'){
			
            // Ce so vabila ze vklopljena ne pustimo vklopa
            if(isset($modules['voting']) || (!isset($modules['voting']) && SurveyInfo::getInstance()->checkSurveyModule('email'))){
                $disabled = ' disabled="disabled"';
                $css_disabled = ' gray';
            }

			echo '<fieldset><legend>'.$lang['srv_voting'].'</legend>';
			
            echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_voting" name="voting" value="1" '. (isset($modules['voting']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'voting\');" />';
			echo $lang['srv_vrsta_survey_type_18'] . '</label>';
            echo '<br><i>'.$lang['srv_voting_info'].'</i>';

            // Opozorilo, da so vabila ze vklopljena in zato modula ni mogoce vklopiti
            if(!isset($modules['voting']) && SurveyInfo::getInstance()->checkSurveyModule('email')){
                echo '<br><br><i class="red bold">'.$lang['srv_voting_info_error'].'</i><br>';
            }
			
            echo '</fieldset>';
			
			echo '<br />';
		
			echo '<div id="globalSettingsInner">';
			if(isset($modules['voting'])){
				$sv = new SurveyVoting($this->anketa);
				$sv->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == 'advanced_paradata'){
			
			echo '<fieldset><legend>'.$lang['srv_advanced_paradata'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_advanced_paradata" name="advanced_paradata" value="1" '. (isset($modules['advanced_paradata']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'advanced_paradata\');" />';
			echo $lang['srv_vrsta_survey_type_16'] . '</label>';
			//echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_6_4'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
		
			echo '<div id="globalSettingsInner">';
			if(isset($modules['advanced_paradata'])){
				$sap = new SurveyAdvancedParadata($this->anketa);
				$sap->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == 'json_survey_export'){
			
			echo '<fieldset><legend>'.$lang['srv_json_survey_export'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_srv_json_survey_export" name="srv_json_survey_export" value="1" '. (isset($modules['srv_json_survey_export']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'srv_json_survey_export\');" />';
			echo $lang['srv_vrsta_survey_type_17'] . '</label>';			
			echo '</fieldset>';
			
			echo '<br />';
		
			echo '<div id="globalSettingsInner">';
			
			if(isset($modules['srv_json_survey_export'])){				
				$sjs = new SurveyJsonSurveyData($this->anketa);
				$sjs->displaySettings();
			}
			
			echo '</div>';
		} 		
		elseif ($_GET['a'] == 'slideshow'){
		
			echo '<fieldset><legend>'.$lang['srv_slideshow_fieldset_label'].'</legend>';
			echo '<label class="strong"><input type="checkbox" id="advanced_module_slideshow" name="slideshow" value="1" '. (isset($modules['slideshow']) ? ' checked="checked"' : '').' onChange="toggleAdvancedModule(\'slideshow\');" />';
			echo $lang['srv_vrsta_survey_type_9'] . '</label>';
			echo '<br/><i>'.$lang['srv_vrsta_survey_note_9_2'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			if(isset($modules['slideshow'])){
				$ss = new SurveySlideshow($this->anketa);
				$ss->ShowSlideshowSetings();
			}
			echo '</div>';
		} 
		elseif ($_GET['a'] == 'vnos') {
			
			echo '<fieldset><legend>'.$lang['srv_vnos'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_user_from_cms" name="user_from_cms" value="2" '. (($row['user_from_cms'] == 2 && $row['cookie'] == -1) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'user_from_cms\');" />';
			echo $lang['srv_vrsta_survey_type_5'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_5_3'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
		
			echo '<div id="globalSettingsInner">';
			if($row['user_from_cms'] == 2 && $row['cookie'] == -1){
				$this->vnos();
			}
			echo '</div>';
		} 
		elseif ($_GET['a'] == A_TELEPHONE){
		
			// Ce je anketar ne vidi teh nastavitev
			$isAnketar = Common::isUserAnketar($this->anketa, $global_user_id);
			if(!$isAnketar){
				
				if(isset($modules['phone'])){
					$sqlT = sisplet_query("SELECT count(*) AS cnt FROM srv_invitations_recipients WHERE ank_id='$this->anketa' AND deleted='0' AND phone!=''");
					$rowT = mysqli_fetch_array($sqlT);
					
					// Ce se nimamo nobene stevilke v bazi, pustimo da se lahko ugasne
					if($rowT['cnt'] == 0){
						echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_7'].' '.Help::display('srv_telephone_help').'</legend>';
						echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_phone" name="phone" value="1" '. (isset($modules['phone']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'phone\');" />';
						echo $lang['srv_vrsta_survey_type_7'] . '</label>';
						echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_7_3'].'</i>';
						echo '</fieldset>';
					}
					else{
						echo '<span class="blue" style="font-size:14px; font-weight:600;">'.$lang['srv_vrsta_survey_type_7'].'</span> '.Help::display('srv_telephone_help');
						echo '<br />';
					}
				}
				else{					
					echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_7'].' '.Help::display('srv_telephone_help').'</legend>';
					echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_phone" name="phone" value="1" '. (isset($modules['phone']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'phone\');" />';
					echo $lang['srv_vrsta_survey_type_7'] . '</label>';
					echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_7_3'].'</i>';
					echo '</fieldset>';
				}
				
				echo '<br />';
			}
			
			echo '<div id="globalSettingsInner">';
			if(isset($modules['phone'])){
				$ST = new SurveyTelephone($this->anketa);
				$ST->action($_GET['m']);
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == A_CHAT){
			global $site_path;
			
			echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_14'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_chat" name="chat" value="1" '. (isset($modules['chat']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'chat\');" />';
			echo $lang['srv_vrsta_survey_type_14'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_14_1'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			if(isset($modules['chat'])){
				$sc = new SurveyChat($this->anketa);
				$sc->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == A_PANEL){
			global $site_path;
			
			echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_15'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_panel" name="panel" value="1" '. (isset($modules['panel']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'panel\');" />';
			echo $lang['srv_vrsta_survey_type_15'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_15_1'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			if(isset($modules['panel'])){
				$sp = new SurveyPanel($this->anketa);
				$sp->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == A_FIELDWORK){
			global $site_path;
			
			// tole bom dopolnil po potrebi
			// 
			// Ce je anketar ne vidi teh nastavitev
			$isAnketar = Common::isUserAnketar($this->anketa, $global_user_id);
			if(!$isAnketar){
				// tole bom dopo
			}

			echo '<div id="globalSettingsInner">';

			$ST = new SurveyFieldwork($this->anketa);
			$ST->action($_GET['m']);
                        
			echo '</div>';
		}
                elseif ($_GET['a'] == A_MAZA){
			global $site_path;
                        
                        echo '<fieldset><legend>'.$lang['srv_maza'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_maza" name="maza" value="1" '. (isset($modules['maza']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'maza\');maza_on_off();" />';
			echo $lang['srv_maza'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_maza_note'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';

			echo '<div id="globalSettingsInner">';

                        if(isset($modules['maza'])){
                            $MS = new MAZA($this->anketa);
                            $MS ->display();
                        }
                        
			echo '</div>';
		}
                elseif ($_GET['a'] == A_WPN){
			global $site_path;
                        
                        echo '<fieldset><legend>'.$lang['srv_wpn'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_wpn" name="wpn" value="1" '. (isset($modules['wpn']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'wpn\');" />';
			echo $lang['srv_wpn'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_wpn_note'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';

			echo '<div id="globalSettingsInner">';

                        if(isset($modules['wpn'])){
                            $MS = new WPN($this->anketa);
                            $MS ->display();
                        }
                        
			echo '</div>';
		}
		elseif ($_GET['a'] == 'social_network'){
			if ($_GET['m'] == 'respondenti' || $_GET['m'] == "") {
				
				echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_8'].'</legend>';
				echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_social_network" name="social_network" value="1" '. (isset($modules['social_network']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'social_network\');" />';
				echo $lang['srv_vrsta_survey_type_8'] . '</label>';
				echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_8_3'].'</i>';
				echo '</fieldset>';
			
				echo '<br />';
					
				echo '<div id="globalSettingsInner">';
				// urejanje respondentov
				if(isset($modules['social_network'])){
					$this->SN_Settings();
				}
				echo '</div>';
			}
		}
		elseif ($_GET['a'] == A_360){
				
			echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_11'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_360_stopinj" name="360_stopinj" value="1" '. (isset($modules['360_stopinj']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'360_stopinj\');" />';
			echo $lang['srv_vrsta_survey_type_11'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_11_1'].'</i>';
			echo '</fieldset>';
		
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			// urejanje respondentov
			if(isset($modules['360_stopinj'])){
				$S360 = new Survey360($this->anketa);
				$S360->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == A_360_1KA){
				
			echo '<fieldset><legend>'.$lang['srv_vrsta_survey_type_12'].'</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_360_stopinj_1ka" name="360_stopinj_1ka" value="1" '. (isset($modules['360_stopinj_1ka']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'360_stopinj_1ka\');" />';
			echo $lang['srv_vrsta_survey_type_12'] . '</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_12_1'].'</i>';
			echo '</fieldset>';
		
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			// urejanje respondentov
			if(isset($modules['360_stopinj_1ka'])){
				$S360 = new Survey3601ka($this->anketa);
				$S360->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == 'evoli'){
				
			echo '<fieldset><legend>Evoli</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_evoli" name="evoli" value="1" '. (isset($modules['evoli']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'evoli\');" />';
			echo 'Evoli</label>';
			echo '<br/><i class="'.$css_disabled.'">Napredna poročila Evoli</i>';
			echo '</fieldset>';
			
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			echo '</div>';
		}
		elseif ($_GET['a'] == 'evoli_teammeter'){
				
			echo '<fieldset><legend>Evoli team meter</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_evoli_teammeter" name="evoli_teammeter" value="1" '. (isset($modules['evoli_teammeter']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'evoli_teammeter\');" />';
			echo 'Evoli team meter</label>';
			echo '<br /><i class="'.$css_disabled.'">Napredna poročila Evoli team meter</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			// urejanje respondentov
			if(isset($modules['evoli_teammeter'])){
				$evoliTM = new SurveyTeamMeter($this->anketa);
				$evoliTM->displaySettings();
			}
			echo '</div>';
        }
        elseif ($_GET['a'] == 'evoli_quality_climate'){
				
			echo '<fieldset><legend>Evoli quality climate</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_evoli_quality_climate" name="evoli_quality_climate" value="1" '. (isset($modules['evoli_quality_climate']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'evoli_quality_climate\');" />';
			echo 'Evoli quality climate</label>';
			echo '<br /><i class="'.$css_disabled.'">Napredna poročila Evoli quality climate</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			// urejanje respondentov
			if(isset($modules['evoli_quality_climate'])){
				$evoliTM = new SurveyTeamMeter($this->anketa);
				$evoliTM->displaySettings();
			}
			echo '</div>';
        }
        elseif ($_GET['a'] == 'evoli_teamship_meter'){
				
			echo '<fieldset><legend>Evoli teamship meter</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_evoli_teamship_meter" name="evoli_teamship_meter" value="1" '. (isset($modules['evoli_teamship_meter']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'evoli_teamship_meter\');" />';
			echo 'Evoli teamship meter</label>';
			echo '<br /><i class="'.$css_disabled.'">Napredna poročila Evoli teamship meter</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			// urejanje respondentov
			if(isset($modules['evoli_teamship_meter'])){
				$evoliTM = new SurveyTeamMeter($this->anketa);
				$evoliTM->displaySettings();
			}
			echo '</div>';
        }
        elseif ($_GET['a'] == 'evoli_organizational_employeeship_meter'){
				
			echo '<fieldset><legend>Evoli organizational employeeship meter</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_evoli_organizational_employeeship_meter" name="evoli_organizational_employeeship_meter" value="1" '. (isset($modules['evoli_organizational_employeeship_meter']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'evoli_organizational_employeeship_meter\');" />';
			echo 'Evoli organizational employeeship meter</label>';
			echo '<br /><i class="'.$css_disabled.'">Napredna poročila Evoli organizational employeeship meter</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			// urejanje respondentov
			if(isset($modules['evoli_organizational_employeeship_meter'])){
				$evoliTM = new SurveyTeamMeter($this->anketa);
				$evoliTM->displaySettings();
			}
			echo '</div>';
        }
        elseif ($_GET['a'] == 'evoli_employmeter'){
				
			echo '<fieldset><legend>Evoli employ meter</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_evoli_employmeter" name="evoli_employmeter" value="1" '. (isset($modules['evoli_employmeter']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'evoli_employmeter\');" />';
			echo 'Evoli employeeship meter</label>';
			echo '<br /><i class="'.$css_disabled.'">Napredna poročila Evoli employeeship meter</i>';
			echo '</fieldset>';
			
			echo '<br />';
			
			echo '<div id="globalSettingsInner">';
			echo '</div>';
		}
		elseif ($_GET['a'] == 'mfdps'){
				
			echo '<fieldset><legend>MFDPŠ</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_mfdps" name="mfdps" value="1" '. (isset($modules['mfdps']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'mfdps\');" />';
			echo 'MFDPŠ</label>';
			echo '<br/><i class="'.$css_disabled.'">Napredni izvozi MFDPŠ</i>';
			echo '</fieldset>';
			
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			echo '</div>';
		}
		elseif ($_GET['a'] == 'borza'){
				
			echo '<fieldset><legend>Borza</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_borza" name="borza" value="1" '. (isset($modules['borza']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'borza\');" />';
			echo 'Borza</label>';
			echo '<br/><i class="'.$css_disabled.'">Napredni izvozi Borza</i>';
			echo '</fieldset>';
			
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			echo '</div>';
		}
		elseif ($_GET['a'] == 'mju'){
				
			echo '<fieldset><legend>MJU</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_mju" name="mju" value="1" '. (isset($modules['mju']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'mju\');" />';
			echo 'MJU</label>';
			echo '<br/><i class="'.$css_disabled.'">Napredni izvozi MJU</i>';
			
			echo '</fieldset>';
			
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			if(isset($modules['mju'])){
				$sme = new SurveyMJUEnote($this->anketa);
				$sme->displaySettings();
			}
			echo '</div>';
		}
		elseif ($_GET['a'] == 'excell_matrix'){
				
			echo '<fieldset><legend>Excelleration matrix</legend>';
			echo '<label class="strong'.$css_disabled.'"><input type="checkbox" id="advanced_module_excell_matrix" name="excell_matrix" value="1" '. (isset($modules['excell_matrix']) ? ' checked="checked"' : '').$disabled.' onChange="toggleAdvancedModule(\'excell_matrix\');" />';
			echo 'Excelleration matrix</label>';
			echo '<br/><i class="'.$css_disabled.'">'.$lang['srv_vrsta_survey_note_16_1'].'</i>';
			echo '</fieldset>';
			
			echo '<br />';
				
			echo '<div id="globalSettingsInner">';
			echo '</div>';
		}
	}
	
	function formatNumber ($value, $digit = 0, $form=null) {
		# Kako izpisujemo decimalke in tisočice
		$default_seperators = array(	0=>array('decimal_point'=>'.', 'thousands'=>','),
										1=>array('decimal_point'=>',', 'thousands'=>'.'));		
	
		if (is_array($form) && isset($form['decimal_point'])&& isset($form['thousands'])) {
			$decimal_point = $form['decimal_point'];
			$thousands = $form['thousands'];
		} else {
			$decimal_point = $default_seperators['decimal_point'];
			$thousands = $default_seperators['thousands'];
		}
	
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		$result = number_format($result, $digit, $decimal_point, $thousands);
	
		return $result;
	}
	
	function globalUserSettings () {
		global $lang, $global_user_id, $admin_type;
		
		# polovimo nastavitve uporabnika
		UserSetting::getInstance()->Init($global_user_id);
		
		# ali zaklepamo anketo ob aktivaciji
		$lockSurvey = UserSetting::getInstance()->getUserSetting('lockSurvey');
		# ali je anketa aktivna ob aktivaciji
		$autoActiveSurvey = UserSetting::getInstance()->getUserSetting('autoActiveSurvey');
		# ali imamo star napreden vmesnik za moje ankete
		$advancedMySurveys = UserSetting::getInstance()->getUserSetting('advancedMySurveys');
		# ali imamo gumb za enklik kreiranje ankete
		$oneclickCreateMySurveys = UserSetting::getInstance()->getUserSetting('oneclickCreateMySurveys');
		
		# ali so komentarji aktivirani ob kreaciji ankete
		$activeComments = UserSetting::getInstance()->getUserSetting('activeComments');
		
		# uvod ob aktivaciji
		$showIntro = UserSetting::getInstance()->getUserSetting('showIntro');
		# zakljucek ob aktivaciji
		$showConcl = UserSetting::getInstance()->getUserSetting('showConcl');
		# ime za respondente ob aktivaciji
		$showSurveyTitle = UserSetting::getInstance()->getUserSetting('showSurveyTitle');

		# Prikaži bližnico za jezik v statusni vrstici
        $showLanguageShortcut = UserSetting::getInstance()->getUserSetting('showLanguageShortcut');
		
		
		echo '<div id="anketa_edit">';
		
		echo '<form name="settingsanketa" action="ajax.php?a=editanketasettings&m=global_user_settings" method="post" autocomplete="off">' . "\n\r";
		//echo '          <input type="hidden" name="anketa" value="' . $this->anketa . '" />' . "\n\r";
		//echo '          <input type="hidden" name="grupa" value="' . $this->grupa . '" />' . "\n\r";
		echo '          <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";
		echo '          <input type="hidden" name="submited" value="1" />' . "\n\r";
		
		echo '<fieldset><legend>'.$lang['srv_interface_settings'].'</legend>';
		
		// Jezik vmesnika
		$sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
		$row = mysqli_fetch_array($sql);
		$lang_admin = $row['lang'];
		echo '<span class="nastavitveSpan6">'.$lang['lang'] . ':</span><select name="language">';
		echo '<option value="1"'.($lang_admin == 1?' selected':'').'>Slovenščina</option>';
		echo '<option value="2"'.($lang_admin == 2?' selected':'').'>English</option>';
		echo '</select>';
		
		echo '<br />';

        // Napredni vmesnik (star design za moje ankete)
        echo '<label><span class="nastavitveSpan6">'.$lang['srv_settings_language_shortcut'].':</span>';
        echo '<input name="showLanguageShortcut" type="hidden" value="0">';
        echo '<input name="showLanguageShortcut" type="checkbox" value="1" '.($showLanguageShortcut == 1?' checked="checked"':'').'></label>';

        echo '<br />';

        // Prikaži ikono za jezik v navigacijski vrstici ankete, desno zgoraj
        echo '<label><span class="nastavitveSpan6">'.$lang['srv_lock_survey_when_activate'].' </span>';
        echo '<input name="lockSurvey" type="hidden" value="0">';
        echo '<input name="lockSurvey" type="checkbox" value="1" '.($lockSurvey == 1?' checked="checked"':'').'></label>';

        echo '<br />';
				
		// Opcija enklik ustvarjanja ankete (v mojih anketah)
		echo '<label><span class="nastavitveSpan6">'.$lang['srv_settings_oneClickCreate'].':</span>';
		echo '<input name="oneclickCreateMySurveys" type="hidden" value="0">';
		echo '<input name="oneclickCreateMySurveys" type="checkbox" value="1" '.($oneclickCreateMySurveys == 1?' checked="checked"':'').'></label>';

        echo '<br />';

        // Možnost prikaza SA ikone pri vseh anketah
        if($admin_type < 3) {
            $showSAicon = UserSetting::getInstance()->getUserSetting('showSAicon');

            echo '<label><span class="nastavitveSpan6">' . $lang['srv_settings_showSAicon'] . ':</span>';
            echo '<input name="showSAicon" type="hidden" value="0">';
            echo '<input name="showSAicon" type="checkbox" value="1" ' . ($showSAicon == 1 ? ' checked="checked"' : '') . '></label>';
        }
		
		echo '</fieldset>';
		
		
		echo '<fieldset><legend>'.$lang['srv_survey_settings'].'</legend>';
		
		// Aktivna anketa ob aktivaciji - TO PUSTIMO SAMO ADMINOM ZARADI GDPR OPOZORILA OB AKTIVACIJI
		if($admin_type == '0'){
			echo '<label><span class="nastavitveSpan6">'.$lang['srv_settings_autoActiveSurvey'].': </span>';
			echo '<input name="autoActiveSurvey" type="hidden" value="0">';
			echo '<input name="autoActiveSurvey" type="checkbox" value="1" '.($autoActiveSurvey == 1?' checked="checked"':'').'></label>';
			
			echo '<br />';
		}
		
		// Komentarji aktivirani ob kreaciji ankete
		echo '<label><span class="nastavitveSpan6">'.$lang['srv_settings_activeComments'].': </span>';
		echo '<input name="activeComments" type="hidden" value="0">';
		echo '<input name="activeComments" type="checkbox" value="1" '.($activeComments == 1?' checked="checked"':'').'></label>';

		echo '<br /><br />';
		
		// Uvod ob aktivaciji prikazan
		echo '<label><span class="nastavitveSpan6">'.$lang['srv_create_show_intro'].': </span>';
		echo '<input name="showIntro" type="hidden" value="0">';
		echo '<input name="showIntro" type="checkbox" value="1" '.($showIntro == 1?' checked="checked"':'').'></label>';

		echo '<br />';
		
		// Zakljucek ob aktivaciji prikazan
		echo '<label><span class="nastavitveSpan6">'.$lang['srv_create_show_concl'].': </span>';
		echo '<input name="showConcl" type="hidden" value="0">';
		echo '<input name="showConcl" type="checkbox" value="1" '.($showConcl == 1?' checked="checked"':'').'></label>';
	
		echo '<br />';
		
		// Ime ob aktivaciji prikazano za respondente
		echo '<label><span class="nastavitveSpan6">'.$lang['srv_create_show_title'].': </span>';
		echo '<input name="showSurveyTitle" type="hidden" value="0">';
		echo '<input name="showSurveyTitle" type="checkbox" value="1" '.($showSurveyTitle == 1?' checked="checked"':'').'></label>';


		echo '</fieldset>';
		
		echo '</form>';

		
		// API avtentikacija
		echo '<fieldset><legend>'.$lang['srv_api'].'</legend>';
		
		echo '<span class="nastavitveSpan6">'.$lang['srv_api_auth'].': </span>';
		echo '<a href="#" onClick="generate_API_key(); return false;">'.$lang['srv_api_auth2'].'</a>';
		echo '<br /><br />';

        echo $lang['additional_info_api'];
		
		echo '</fieldset>';
		
		
		// save gumb
		echo '  <div class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa.submit();"><span>'.$lang['edit1337'] . '</span></a></div>';
		
		echo '<span class="clr"></span>';
		
		// div za prikaz uspešnosti shranjevanja
		if ($_GET['s'] == '1') {
			echo '<div id="success_save"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
		
		echo '</div>';
	}
	
	function globalUserMyProfile () {
		global $lang, $global_user_id, $admin_type, $site_domain, $site_url;
		
		// podatki prijavljenega uporabnika
		$sql = sisplet_query("SELECT id, name, surname, email, type, gdpr_agree, last_login FROM users WHERE id = '$global_user_id'");
		$row = mysqli_fetch_array($sql);
		
		echo '<div id="anketa_edit">';
		
		echo '	<form name="settingsanketa" id="form_profile_user_settings" action="ajax.php?a=editanketasettings&m=global_user_myProfile" method="post" autocomplete="off">' . "\n\r";
		echo '		<input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";
		echo '		<input type="hidden" name="submited" value="1" />' . "\n\r";
		
		echo '		<fieldset><legend>'.$lang['edit_data'].'</legend>';
		
		echo '<div class="data"><span class="setting_title">'.$lang['logged_in_as'].': </span><span class="bold">'.$row['name'].' '.$row['surname'].'</span></div>';
		if($row['type'] == '0')
			$type = $lang['admin_admin'];
		elseif($row['type'] == '1')
			$type = $lang['admin_manager'];
		else
			$type = $lang['admin_narocnik'];
		echo '<div class="data"><span class="setting_title">'.$lang['your_status'].': </span><span class="bold">'.$type.'</span></div>';
        
        // Zadnja prijava
		echo '<div class="data"><span class="setting_title">'.$lang['srv_last_login'].': </span><span class="bold">'.date('j.n.Y', strtotime($row['last_login'])).' '.$lang['ob'].' '.date('H:i', strtotime($row['last_login'])).'</span></div>';
        
        
        // Trenutni paket funkcionalnosti
        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){

            echo '<br>';
            
            $sqlA = sisplet_query("SELECT ua.time_activate, ua.time_expire, uap.id AS package_id, uap.name AS package_name
                                    FROM user_access ua, user_access_paket uap 
                                    WHERE ua.usr_id='$global_user_id' AND uap.id=ua.package_id
                                ");

            $drupal_url = ($lang['id'] == '2') ? $site_url.'d/en/' : $site_url.'d/';

            // Ni nobenega paketa
            if(mysqli_num_rows($sqlA) == 0){
                $package_string = '1ka ('.$lang['srv_access_package_free'].') - <a href="'.$drupal_url.''.$lang['srv_narocila_buyurl'].'">'.$lang['srv_narocila_buy'].'</a>';
            }
            else{
                $rowA = mysqli_fetch_array($sqlA);

                // Ce ima paket 2 ali 3
                if($rowA['package_id'] == '2' || $rowA['package_id'] == '3'){

                    // Ce je paket ze potekel
                    if(strtotime($rowA['time_expire']) < time()){

                        $package_string = '<span class="red bold">';
                        $package_string .= $rowA['package_name'];
                        $package_string .= ' ('.$lang['srv_access_package_expire'].' '.date("d.m.Y", strtotime($rowA['time_expire'])).')';
                        $package_string .= '</span>';

                        $package_string .= ' - <a href="'.$drupal_url.'izvedi-nakup/'.$rowA['package_id'].'/podatki/">'.$lang['srv_narocila_extend'].'</a>';
                    }
                    else{
                        $package_string = $rowA['package_name'];
                        $package_string .= ' ('.$lang['srv_access_package_valid'].' '.date("d.m.Y", strtotime($rowA['time_expire'])).')';

                        $package_string .= ' - <a href="'.$drupal_url.'izvedi-nakup/'.$rowA['package_id'].'/podatki/">'.$lang['srv_narocila_extend'].'</a>';

                        $package_string .= '<br /><a href="'.$site_url.'admin/survey/index.php?a=narocila" style="line-height:24px;">'.$lang['srv_access_package_all'].'</a>'; 
                    }
                }
                else{
                    $package_string = $rowA['package_name'];
                    $package_string .= ' ('.$lang['srv_access_package_free'].')';
                    
                    $package_string .= ' - <a href="'.$drupal_url.''.$lang['srv_narocila_buyurl'].'">'.$lang['srv_narocila_buy'].'</a>';
                }
            }

            echo '<div class="data"><span class="setting_title">'.$lang['srv_access_package'].': </span><span class="bold">'.$package_string.'</span></div>';
        }

		
		echo '<br />';
		
        // AAI nima moznosti spreminjanja imena, priimka, emaila, gesla...
        if(isAAI()){
            echo '<span class="italic">'.$lang['srv_profil_aai_warning'].'</span>';
        }
        else{
            echo '		<div class="setting"><span class="setting_title">'.$lang['name'].':</span>';
            echo '		<input class="text " name="ime" placeholder="Ime" value="'.$row['name'].'" type="text"></div>';
                
            echo '		<div class="setting"><span class="setting_title">'.$lang['surname'].' :</span>';
            echo '		<input class="text " name="priimek" placeholder="Priimek" value="'.$row['surname'].'" type="text"></div>';

            echo '		<div class="setting"><span class="setting_title">'.$lang['email'].' : <span class="faicon add icon-blue pointer" id="klik-dodaj-email" deluminate_imagetype="png"></span></span>';
            echo '		<input class="text" disabled="disabled" value="'.$row['email'].'" type="text">';
            echo '		<input name="email2" value="'.$row['email'].'" type="hidden">';
            echo '      <span style="margin:0 6.5px">&nbsp;</span>';
            
            $alternativni_emaili = User::getInstance()->allEmails('brez primarnega');
           
            echo '<label for="active-master" '.(empty($alternativni_emaili) ? 'class="hidden"' : '').'><input class="text" name="active_email" value="master" id="active-master" type="radio" '.(User::getInstance()->primaryEmail() == $row['email'] ? 'checked="checked"' : '').'> '.$lang['login_email_subscription'].'</label>';
                if(!empty($alternativni_emaili)){
                    foreach($alternativni_emaili as $email) {
                        echo '<br/><span style="width:130px; float:left;">&nbsp;</span>';
                        echo '<input class="text" disabled="disabled" value="'.$email->email.'" type="text">';
                        echo '<span style="margin: 0 5px 10px;" onclick="izbrisiAlternativniEmail(\''.$email->id.'\')"><i class="fa fa-times link-sv-moder"></i></span>';
                        echo '<input class="text" value="'.($email->id).'" name="active_email" type="radio" '.($email->active == 1 ? 'checked="checked"' : '').'> <label for="active-master">'.$lang['login_email_subscription'].'</label>';
                    }
                }
            echo '</div><br />';

            echo '<div class="dodaj-alternativni-email" style="display: none;">';
                echo '<div class="vnos">';
                    echo '<span class="setting_title">'.$lang['login_alternative_emails'].' :</span>';
                    echo '<input class="text" id="alternativni-email" value="" type="text">';
                    echo '<span style="margin:0 8px">&nbsp;</span>';
                    echo '<a href="#" onclick="dodajAlternativniEmail()">'.$lang['srv_inv_btn_add_recipients_add'].'</a>';
                echo '</div>';
                echo '<br><div id="alternativno-obvestilo" style="font-style: italic;"></div>';
            echo '<br />';
            echo '</div>';

            // Ce je vklopljen modul gorenje, preverimo ce ima se default geslo in izpisemo opozorilo
            if (Common::checkModule('gorenje')){
                if(SurveyGorenje::checkGorenjePassword())
                    echo '<p class="red bold">'.$lang['gorenje_password_warning'].'</p>';
            }

            // Obveščanje
            echo '		<div class="setting"><span class="setting_title">'.$lang['password'].':</span>';
            echo '		<input class="text" name="geslo" placeholder="'.$lang['password'].'" id="p1" value="PRIMERZELODOLGEGAGESLA" onclick="document.getElementById(\'p1\').value=\'\';" type="password"></div>';
                    
            echo '		<div class="setting"><span class="setting_title">'.$lang['again'].':</span>';
            echo '		<input class="text" name="geslo2" placeholder="'.$lang['password'].'" id="p2" value="PRIMERZELODOLGEGAGESLA" onclick="document.getElementById(\'p2\').value=\'\';" type="password"></div>';
                    
            // Prejemanje obvestil
            $red_border = (isset($_GET['unsubscribe']) && $_GET['unsubscribe'] == '1') ? ' border:2px red solid; padding: 5px 10px;' : '';
            echo '		<div class="setting" style="height:auto; float:left; clear:both; margin: 15px 0; '.$red_border.'"><span class="setting_title">'.$lang['srv_gdpr_user_options'].': '.Help::display('srv_gdpr_user_options').'</span>';
            echo '		<label for="gdpr-agree-yes"><input type="radio" name="gdpr_agree" id="gdpr-agree-yes" value="1" '.($row['gdpr_agree'] == 1 ? 'checked="checked"' : null).'/>'.$lang['yes'].'</label>';
            echo '      <label for="gdpr-agree-no"><input type="radio" name="gdpr_agree" id="gdpr-agree-no" value="0" '.($row['gdpr_agree'] == 0 ? 'checked="checked"' : null).'/>'.$lang['no1'].'</label></div>';

            // Google 2 FA
            $user_option = User::option($global_user_id, 'google-2fa-secret');
            $user_option_validate = User::option($global_user_id, 'google-2fa-validation');
            echo '		<div class="setting" style="clear: both;"><span class="setting_title">'.$lang['google_2fa'].':  '.Help::display('srv_google_2fa_options').'</span>';
            echo '		<label for="google-2fa"><input type="checkbox" name="google-2fa" id="google-2fa" value="1" '.(! empty($user_option) ? 'checked="checked"' : '').' onclick="prikaziGoogle2faKodo()"/>'.$lang['yes'].'</label>';
            echo '     </div>';

            if (empty($user_option)) {
                $google2fa = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
                $googleSecret = $google2fa->generateSecret();

                $googleLink = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($row['email'], $googleSecret, $site_domain);

                //Prikaži QR kodo
                echo '<div class="settings-2fa-code" id="2fa-display" style="display: none;">';
                echo '<div>'.$lang['google_2fa_admin_enabled'].'<b>'.$googleSecret.'</b></div>';
                echo '<div style="padding-top: 10px;">'.$lang['google_2fa_admin_enabled_2'].'</div>';
                echo '<input type="hidden" name="google-2fa-secret" value="'.$googleSecret.'">';
                echo '<div><img style="border: 0; padding:10px" src="'.$googleLink.'"/></div>';
                echo '<div>Ko boste shranili nastavitve, bo nastavitev obveljala.</div>';
                echo '</div>';
            } elseif (! empty($user_option) && $user_option_validate == 'NOT') {
                echo '<div class="google-2fa-validate">';
                echo '<div style="padding:5px;">'.$lang['google_2fa_admin_validate'].'</div>';
                echo '<div style="display: block; clear: both;padding: 10px;"><input type="text" name="google-2fa-validate"></div>';
                echo '<div class="buttonwrapper floatLeft spaceLeft"><a href="#" class="ovalbutton btn_savesettings" onclick="ponastaviGoogle2fa()">'.$lang['google_2fa_admin_test_code_reset'].'</a></div>';
                echo '<div class="buttonwrapper floatLeft spaceLeft"><a href="#" class="ovalbutton btn_savesettings" onclick="aktivirajGoogle2fa()">'.$lang['google_2fa_admin_test_code'].'</a></div>';

                echo '<div id="google-2fa-bvestilo" style="font-style: italic;">'.$lang['google_2fa_user_error_code'].'</div>';
                echo '</div>';
            } else {
                echo '<div class="google-2fa-deactivate" id="2fa-display" style="display: none;">';
                echo '<div style="padding:5px;">'.$lang['google_2fa_admin_deactivate'].'</div>';
                echo '<div style="display: block; clear: both;padding: 10px;"><input type="text" name="google-2fa-deactivate"></div>';
                echo '<div class="buttonwrapper floatLeft spaceLeft"><a href="#" class="ovalbutton btn_savesettings" onclick="deaktivirajGoogle2fa()">'.$lang['google_2fa_admin_deactivat_code'].'</a></div>';
                echo '<div id="google-2fa-bvestilo" style="font-style: italic;">'.$lang['google_2fa_user_error_code'].'</div>';
                echo '</div>';
            }
        }
		
        echo '		</fieldset>';
		
		echo '	</form>';
		

		// Save gumb - ce ni AAI
        if(!isAAI()){

            echo '  <div class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_gray" href="#" onclick="izbrisi1kaRacun();"><span>'.$lang['delete_account'] . '</span></a></div>';
            echo '  <div class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="save1kaRacunSettings();"><span>'.$lang['edit1337'] . '</span></a></div>';

            echo '<span class="clr"></span>';
            
            // div za prikaz uspešnosti shranjevanja
            if ($_GET['s'] == '1') {
                echo '<div id="success_save"></div>';
                echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
            }

            echo '<br /><br />';
        }
		

		// Tabela aktivnosti (prijav)
		echo '<fieldset><legend>'.$lang['srv_login_tracking_title'].'</legend>';
		
		echo '<table class="login_tracking">';
		echo '<tr><th>IP</th><th>'.$lang['srv_login_tracking_date'].'</th><th>'.$lang['srv_login_tracking_time'].'</th></tr>';
		
		$result = sisplet_query ("SELECT IP, kdaj FROM user_login_tracker WHERE uid='".$global_user_id."' ORDER BY kdaj desc");
		if (mysqli_num_rows($result) == 0) {
			echo $lang['srv_login_tracking_noData'];
		}
		else {
			$count = 0;
			while ($row = mysqli_fetch_array ($result)) {
					echo '<tr '.($count >= 5 ? ' class="hide"' : '').'>';
					echo '<td>'.$row['IP'].'</td>';
					echo '<td>'.date('j.n.Y', strtotime($row['kdaj'])).'</td>';
					echo '<td>'.date('H:i', strtotime($row['kdaj'])).'</td>';
					echo '</tr>';
					
					$count++;
			}
		}
		echo '</table>';
		
		if(mysqli_num_rows($result) > 25){
			echo '<span class="login_tracking_more bold" onClick="$(\'table.login_tracking tr\').removeClass(\'hide\'); $(\'.login_tracking_more\').hide();">'.$lang['srv_invitation_nonActivated_more'].'</span>';
			echo '<br /><br />';
		}
		
		echo '</fieldset>';	
                
        UserTrackingClass::init()->userTrackingDisplay();
		
		echo '</div>';
	}
	
	
	function setGlobalUserSetting() {
		global $lang, $global_user_id;
		
		# polovimo nastavitve uporabnika
		UserSetting::getInstance()->Init($global_user_id);
		if (isset($_REQUEST['name']) && isset($_REQUEST['value'])) {
			
			$name = $_REQUEST['name'];
			$value = $_REQUEST['value'];
			
			UserSetting::getInstance()->setUserSetting($name, $value);
			UserSetting::getInstance()->saveUserSetting();
			
		} else {echo 'napaka';}
	}
	
	function showLockSurvey() {
		global $lang, $global_user_id, $admin_type;
        
        UserSetting::getInstance()->Init($global_user_id);
        
        # ali zaklepamo anketo ob aktivaciji
		$lockSurvey = (int)UserSetting::getInstance()->getUserSetting('lockSurvey');
		
		if ($admin_type == '0' || $admin_type == '1') {
			echo '<p>';
			echo '<label><input type="checkbox" onclick="changeSurveyLock(this)" '.($lockSurvey == 1 ? ' checked="checekd"' : '').'>';
			echo $lang['srv_survey_lock_note'];
			echo '</label>';
			echo '</p>';
        }
        
		if ((int)$lockSurvey > 0) {
			echo '<p class="small">'.$lang['srv_unlock_popup'].'</p>';
		}
	}
	
	function ajax_showTestSurveySMTP(){
        ob_start();
        
		global $lang, $global_user_id;
		global $admin_type;

		$error = false;
		$msg = null;
		$email_msg = $lang['srv_mail_test_smtp_test_success'];
		$email_subject = $lang['srv_mail_test_smtp_test'];
		
		$MA = new MailAdapter($this->anketa);
		
		$settings = $MA->getSettingsFromRequest($_REQUEST);
		$mailMode = $_REQUEST['SMTPMailMode'];

		
		if (isset ($_COOKIE['uid'])) {
			$email = base64_decode ($_COOKIE['uid']);
        } 
        else {
			$error = true;
			$msg = $lang['srv_mail_test_smtp_mail_detect_error'];
		}
		
		if (validEmail($email)){

			// preverimo password - ne sme bit prazen
			if (($mailMode == 1 || $mailMode == 2) && empty($settings['SMTPPassword'])){ # password 
				$error = true;
				$msg = $lang['srv_mail_test_smtp_password_error'];
			}
        } 
        else {
			$error = true;
			$msg = $lang['srv_mail_test_smtp_mail_detect_error'];	
        }
        
        // preverjanje je ok.. poizkusimo poslat testni email
		if ($error == false){ 
            
            $MA->addRecipients($email);
            
            $result = $MA->sendMailTest($email_msg, $email_subject, $mailMode, $settings);
            
            if ($result == false){
				$error = true;
				$msg = $lang['srv_mail_test_smtp_not_possible'];
			}
		}		
		
		// če imamo napake jo izpišemo
		if ($error == true){
			echo $lang['srv_mail_test_smtp_error'].': '. $msg;
		}
		else{
			echo $lang['srv_mail_test_smtp_sent'].': '.$email;
		}
		
		#vsebino shranimo v buffer
		$content = ob_get_clean();
		
		$popUp = new PopUp();
		#$popUp->setId('divSurveySmtp');
		$popUp->setHeaderText($lang['srv_mail_test_smtp']);
		
		#dodamo vsebino
		$popUp->setContent($content);
		
		# dodamo gumb Prekliči
		$button = new PopUpCancelButton();
		$button->setCaption($lang['srv_zapri'])->setTitle($lang['srv_zapri']);
		$popUp->addButton($button);
	
		echo $popUp;	
	}
}
?>