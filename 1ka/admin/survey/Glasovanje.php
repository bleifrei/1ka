<?php

global $site_path;

define('NEW_LINE', "\n");

class Glasovanje {

    var $anketa; // trenutna anketa

    /**
    * @desc konstruktor
    */
    function __construct ($anketa) {
		global $site_url, $global_user_id;
        
		$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
			
		UserSetting :: getInstance()->Init($global_user_id);
    }

    	/**
	* @desc prikaze vprasanje, uvod, zakljucek, statistiko v glasovanju
	*/
	function vprasanja() {
		global $lang;
		global $site_url;
		global $admin_type;

		$Branching = new Branching($this->anketa);

		// naenkrat preberemo vse spremenljivke, da ne delamo queryja vsakic posebej
		Cache::cache_all_srv_spremenljivka($this->anketa, true);
		// enako za srv_branching
		Cache::cache_all_srv_branching($this->anketa, true);
		// cachiramo tudi srv_if
		Cache::cache_all_srv_if($this->anketa);
		// cache vseh spremenljivk
		//$this->find_all_spremenljivka();
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		$Branching->survey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");

		echo '<ul class="first">';
		
		if($rowA['show_intro'] != 0){
			echo '<li id="-1" class="spr glasovanje">';
			$Branching->introduction_conclusion(-1);
			echo '</li>';
			
			echo '<li style="height:30px;"></li>';
		}		
		
		$spremenljivka = 0;

		$sqlGrupe = sisplet_query("SELECT id, naslov FROM srv_grupa g WHERE g.ank_id='$this->anketa' ORDER BY g.vrstni_red");
		$rowGrupe = mysqli_fetch_assoc($sqlGrupe);

		$grupa = $rowGrupe['id'];

		$sql = sisplet_query("SELECT id, stat FROM srv_spremenljivka WHERE gru_id='" . $rowGrupe['id'] . "' ORDER BY vrstni_red");
		$row = mysqli_fetch_array($sql);
		$this->vprasanje($row['id']);
		$spremenljivka = $row['id'];
		
		////////////////////// statistika /////////////////////
		$rowS = Cache::srv_spremenljivka($spremenljivka);
		if($rowS['stat'] > 0){
			
			echo '<li style="height:30px;"></li>';
			
			//echo '    <div id="spremenljivka_-3" class="spremenljivka" style="margin-top:15px">' . NEW_LINE;
			echo '<li id="-3" class="spr glasovanje">';
			$Branching->introduction_conclusion(-3);
			echo '</li>';
			//echo '    </div> <!-- /spremenljivka_-3 -->' . NEW_LINE;
		}
		//////////////////////////////////////////////////////
		echo '<li style="height:30px;"></li>';
		
		echo '<li id="-2" class="spr glasovanje">';
		$Branching->introduction_conclusion(-2);
		echo '</li>';
	
		echo '</ul>';
		
		//$Branching->showVprasalnikBottom();
	}
	
	/**
    * @desc prikaze levi meni s hitrimi nastavitvami
    */
    function display_glasovanje_settings ($displayExtra = 0) {
        global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;

		echo '<div class="header_holder">'.NEW_LINE;
		echo '<div class="header_content">'.NEW_LINE;
		echo '    <div class="header_left">' . $lang['srv_glasovanja_settings'] . Help :: display('srv_type_glasovanje') . '</div>' . NEW_LINE;
		echo '<div class="clr"></div>';
		echo '</div>';
		echo '</div>';

		$sql2 = sisplet_query("SELECT * FROM srv_glasovanje WHERE ank_id='$this->anketa'");
		$row2 = mysqli_fetch_array($sql2);

		$row = Cache::srv_spremenljivka($row2['spr_id']);
		
		$rowA = SurveyInfo::getInstance()->getSurveyRow();

		//Vkljucenost ankete (embeddana ali samostojna)
		echo '<fieldset>';
		echo '<legend>' . $lang['glasovanja_embed'] . '</legend>';
		echo '<label for="glasovanja_embed_0" class="pointer"><input type="radio" name="glasovanja_embed" value="0" id="glasovanja_embed_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'embed\')" ' . ($row2['embed'] == 0 ? ' checked' : '') . '/>' . $lang['glasovanja_embed_off'] . '</label><br /> ';
		echo '<label for="glasovanja_embed_1" class="pointer"><input type="radio" name="glasovanja_embed" value="1" id="glasovanja_embed_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'embed\')" ' . ($row2['embed'] == 1 ? ' checked' : '') . '/>' . $lang['glasovanja_embed_on'];
                if($row2['embed'] != 0)
			echo ' (<a href="index.php?anketa=' . $this->anketa . '&a=vabila&m=url&js=open">' . $lang['srv_embed_link'] . '</a>)';
                echo '</label> ';
		echo '</fieldset>';


		//Izbira spola ob resevanju
		echo '<fieldset>';
		echo '<legend>' . $lang['glasovanja_spol'] . '</legend>';
		//echo '<span class="nastavitveSpan4"><label>' . $lang['srv_alert_respondent'] . ':</label></span>';
		echo '<label for="glasovanja_spol_1" class="pointer"><input type="radio" name="glasovanja_spol" value="1" id="glasovanja_spol_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'spol\')" ' . ($row2['spol'] == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
		echo '<label for="glasovanja_spol_0" class="pointer"><input type="radio" name="glasovanja_spol" value="0" id="glasovanja_spol_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'spol\')" ' . ($row2['spol'] == 0 ? ' checked' : '') . '/>' . $lang['no1'] . '</label> ';
		echo '</fieldset>';


		//Prikaz dodatnih strani
		echo '<fieldset>';
		echo '<legend>' . $lang['glasovanja_strani'] . '</legend>';

		echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_strani_intro'] . ':</label></span>';
		echo '<label for="glasovanja_intro_1" class="pointer"><input type="radio" name="glasovanja_intro" value="1" id="glasovanja_intro_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_intro\')" ' . ($rowA['show_intro'] == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
		echo '<label for="glasovanja_intro_0" class="pointer"><input type="radio" name="glasovanja_intro" value="0" id="glasovanja_intro_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_intro\')" ' . ($rowA['show_intro'] == 0 ? ' checked' : '') . '/>' . $lang['no1'] . '</label><br /> ';

		echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_strani_outro'] . ':</label></span>';
		echo '<label for="glasovanja_concl_1" class="pointer"><input type="radio" name="glasovanja_concl" value="1" id="glasovanja_concl_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_concl\')" ' . ($rowA['show_concl'] == 1 ? ' checked' : '') . '/>' . $lang['glasovanja_strani_outro_show'] . '</label> ';
		echo '<label for="glasovanja_concl_0" class="pointer"><input type="radio" name="glasovanja_concl" value="0" id="glasovanja_concl_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_concl\')" ' . ($rowA['show_concl'] == 0 ? ' checked' : '') . '/>' . $lang['glasovanja_strani_outro_hide'] . '</label> ';
		//echo '<input type="radio" name="glasovanja_concl" value="-1" id="glasovanja_concl_-1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_concl\')" ' . ($rowA['show_concl'] == -1 ? ' checked' : '') . '/><label for="glasovanja_concl_-1" class="pointer">' . $lang['glasovanja_strani_outro_hide'] . '</label> ';
		// tega ni treba vec
		/*if($rowA['show_concl'] != 1)
			echo '<a href="#" onclick="vprasanje_fullscreen(\'-2\')">(' . $lang['edit3'] . ')</a>';*/
		
		echo '</fieldset>';

		
		// Prikaz naslova...
		echo '<fieldset>';
		echo '<legend>' . $lang['glasovanja_naslov'] . '</legend>';
		
		// prikaz naslova ankete
		SurveySetting::getInstance()->Init($this->anketa);
		$survey_hide_title = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hide_title');			
		echo '<span class="nastavitveSpan4" style="width: 140px;"><label>' . $lang['glasovanja_results_survey_title'] . ':</label></span>';
		echo '<label><input type="radio" name="survey_hide_title" value="0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'survey_hide_title\')" '.($survey_hide_title == 0 ? ' checked="checked"' : '').'>' . $lang['yes'] . '</label> ';
		echo '<label><input type="radio" name="survey_hide_title" value="1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'survey_hide_title\')" '.($survey_hide_title == 1 ? ' checked="checked"' : '').'>' . $lang['no1'] . '</label><br />';
		/*echo '<input type="radio" name="glasovanja_survey_title" value="0" id="glasovanja_survey_title_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'akronim\')" ' . ($rowA['akronim'] == ' ' ? ' checked' : '') . '/><label for="glasovanja_survey_title_0" class="pointer">' . $lang['no1'] . '</label> ';
		echo '<input type="radio" name="glasovanja_survey_title" value="1" id="glasovanja_survey_title_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'akronim\')" ' . ($rowA['akronim'] == '' ? ' checked' : '') . '/><label for="glasovanja_survey_title_1" class="pointer">' . $lang['yes'] . '</label> ';
		echo '<input type="radio" name="glasovanja_survey_title" value="2" id="glasovanja_survey_title_2" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'akronim\')" ' . ($rowA['akronim'] == $lang['poll'] ? ' checked' : '') . '/><label for="glasovanja_survey_title_2" class="pointer">"' . $lang['poll'] . '"</label><br /> ';*/
		
		//anketa v arhivu - prikaz arhiva
		echo '<span class="nastavitveSpan4" style="width: 140px;"><label>' . $lang['glasovanja_results_archive'] . ':</label></span>';
		echo '<label for="stat_archive_1" class="pointer"><input type="radio" name="stat_archive" value="1" id="stat_archive_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat_archive\')" ' . ($row2['stat_archive'] == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
		echo '<label for="stat_archive_0" class="pointer"><input type="radio" name="stat_archive" value="0" id="stat_archive_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat_archive\')" ' . ($row2['stat_archive'] == 0 ? ' checked' : '') . '/>' . $lang['no1'] . '</label><br /> ';
		//echo '<input type="checkbox" name="stat_archive" value="1" id="stat_archive" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this, \'stat_archive\')" ' . ($row2['stat_archive'] == 1 ? ' checked' : '') . '/><label for="stat_archive" class="pointer"></label><br /> ';	

		echo '</fieldset>';
		
		
		//Prikaz statistike - nastavitve		
		echo '<fieldset>';
		echo '<legend>' . $lang['glasovanja_results'] . '</legend>';
		
		//prikaz statistike
		echo '<span class="nastavitveSpan4" style="width: 100%;"><label>' . $lang['srv_stat_on'] . ':</label></span><br />';
		echo '<label for="show_stat_1" class="pointer"><input type="radio" name="show_stat" value="1" id="show_stat_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat\')" ' . ($row['stat'] == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label> ';
		echo '<label for="show_stat_0" class="pointer"><input type="radio" name="show_stat" value="0" id="show_stat_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat\')" ' . ($row['stat'] == 0 ? ' checked' : '') . '/>' . $lang['no1'] . '</label> ';
		echo '<label for="show_stat_2" class="pointer"><input type="radio" name="show_stat" value="2" id="show_stat_2" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat\')" ' . ($row['stat'] == 2 ? ' checked' : '') . '/>' . $lang['glasovanja_results_admin'] . '</label><br /> ';
	
		if($row['stat'] > 0){
			//prikaz stevila glasov, v procentih in z grafom
			echo '<span class="nastavitveSpan5" style="width: 100%;"><label>' . $lang['glasovanja_results_type'] . ':</label></span><br />';
			echo '<label for="glasovanja_results" class="pointer"><input type="checkbox" name="glasovanja_results" value="1" id="glasovanja_results" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this, \'show_results\')" ' . ($row2['show_results'] == 1 ? ' checked' : '') . '/>' . $lang['glasovanja_results_count'] . '</label> ';
			echo '<label for="glasovanja_percent" class="pointer"><input type="checkbox" name="glasovanja_percent" value="1" id="glasovanja_percent" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this, \'show_percent\')" ' . ($row2['show_percent'] == 1 ? ' checked' : '') . '/>' . $lang['glasovanja_results_percent'] . '</label> ';
			echo '<label for="glasovanja_graph" class="pointer"><input type="checkbox" name="glasovanja_graph" value="1" id="glasovanja_graph" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this, \'show_graph\')" ' . ($row2['show_graph'] == 1 ? ' checked' : '') . '/>' . $lang['glasovanja_results_graph'] . '</label><br /> ';

			//prikaz stevila glasov
			echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_results_allcount'] . ':</label></span>';
			echo '<label for="glasovanja_count_0" class="pointer"><input type="radio" name="glasovanja_count" value="0" id="glasovanja_count_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat_count\')" ' . ($row2['stat_count'] == 0 ? ' checked' : '') . '/>' . $lang['no1'] . '</label> ';
			echo '<label for="glasovanja_count_1" class="pointer"><input type="radio" name="glasovanja_count" value="1" id="glasovanja_count_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat_count\')" ' . ($row2['stat_count'] == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label><br /> ';

			//prikaz casa glasovanja
			echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_results_time'] . ':</label></span>';
			echo '<label for="glasovanja_time_0" class="pointer"><input type="radio" name="glasovanja_time" value="0" id="glasovanja_time_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat_time\')" ' . ($row2['stat_time'] == 0 ? ' checked' : '') . '/>' . $lang['no1'] . '</label> ';
			echo '<label for="glasovanja_time_1" class="pointer"><input type="radio" name="glasovanja_time" value="1" id="glasovanja_time_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'stat_time\')" ' . ($row2['stat_time'] == 1 ? ' checked' : '') . '/>' . $lang['yes'] . '</label><br /> ';

			//prikaz naslova vprasanja
			echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_results_title'] . ':</label></span>';
			echo '<label for="glasovanja_title_1" class="pointer"><input type="radio" name="glasovanja_title" value="1" id="glasovanja_title_1" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_title\')" ' . ($row2['show_title'] == 1 ? ' checked' : '') . '/>' . $lang['no1'] . '</label> ';
			echo '<label for="glasovanja_title_0" class="pointer"><input type="radio" name="glasovanja_title" value="0" id="glasovanja_title_0" onClick="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'show_title\')" ' . ($row2['show_title'] == 0 ? ' checked' : '') . '/>' . $lang['yes'] . '</label><br /> ';
		}
		echo '</fieldset>';

		
		// SPODNJE EXTRA NASTAVITVE
		echo '<div id="glas_extra_settings" '.($displayExtra==0 ? ' style="display: none;"' : '').' >';

			//PISKOTEK
			echo '    <fieldset>'. NEW_LINE;
			echo '    <legend>' . $lang['cookie'] . '</legend>'. NEW_LINE;

			//piskotek shrani
			echo '<span class="nastavitveSpan4" style="width: 100%;"><label>' . $lang['srv_cookie'] . ':</label></span><br />';
			echo '<label for="cookie_-1" class="pointer"><input type="radio" name="cookie" value="-1" id="cookie_-1" onClick="edit_glasovanje(\'' . $rowA['spr_id'] . '\', this.value, \'cookie\')" ' . ($rowA['cookie'] == -1 ? ' checked' : '') . '/>' . $lang['a_without'] . '</label> ';
			echo '<label for="cookie_1" class="pointer"><input type="radio" name="cookie" value="1" id="cookie_1" onClick="edit_glasovanje(\'' . $rowA['spr_id'] . '\', this.value, \'cookie\')" ' . ($rowA['cookie'] == 1 ? ' checked' : '') . '/>' . $lang['srv_cookie_1'] . '</label><br /> ';

			echo '    </fieldset>';

			//TRAJANJE
			echo '    <fieldset>'. NEW_LINE;
			echo '    <legend>' . $lang['duration'] . '</legend>'. NEW_LINE;

			echo '<span class="nastavitveSpan4" style="width: 100%;"><label>' . $lang['srv_starts'] . ':</label></span><br/>';
			echo '<input id="starts" type="text" name="starts" value="' . $rowA['starts'] . '" onBlur="edit_glasovanje(\'' . $rowA['spr_id'] . '\', this.value, \'starts\')" />
			<span class="faicon calendar_icon icon-as_link" id="starts_img"></span>
			<script type="text/javascript">
			Calendar.setup({
			inputField  : "starts",
			ifFormat    : "%Y-%m-%d",
			button      : "starts_img",
			singleClick : true,

			onUpdate : function() {
			edit_glasovanje(\'' . $rowA['spr_id'] . '\', document.getElementById("starts").value, \'starts\');
			}
			});
			</script>
			';
			echo '<br/>';
			echo '<span class="nastavitveSpan4" style="width: 100%;"><label>' . $lang['srv_expire'] . ':</label></span><br/>';
			echo '<input id="expire" type="text" name="expire" value="' . $rowA['expire'] . '" onBlur="edit_glasovanje(\'' . $rowA['spr_id'] . '\', this.value, \'expire\')" />
			<span class="faicon calendar_icon icon-as_link" id="expire_img"></span>
			<script type="text/javascript">
				Calendar.setup({
					inputField  : "expire",
					ifFormat    : "%Y-%m-%d",
					button      : "expire_img",
					singleClick : true,
					onUpdate : function() {
						edit_glasovanje(\'' . $rowA['spr_id'] . '\', document.getElementById("expire").value, \'expire\');
					}
				});
			</script>
			';
			echo '<br/>';

			echo '    </fieldset>';


			//Izbira skina za glasovanje				

			echo '<fieldset>';
			echo '<legend>' . $lang['srv_themes'] . '</legend>';

			//izbira skina
			echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_theme'] . ':</label></span>';

			$dir = opendir($site_path . 'main/survey/skins/');

			echo '<select name="skin_anketa" id="skin_anketa" onChange="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'skin_anketa\')">' . NEW_LINE;
			while ($file = readdir($dir)) {
				if ($file != '.' && $file != '..' && $file != '.svn' && strtolower(substr($file, -4, 4)) == '.css')
					echo '            <option value="' . substr($file, 0, -4) . '"' . ($rowA['skin'] == substr($file, 0, -4) ? ' selected="selected"' : '') . '>' . substr($file, 0, -4) . '</option>' . NEW_LINE;
				elseif ($file != '.' && $file != '..' && $file != '.svn' && strtolower(substr($file, -4, 4)) != '.css') {
					if (is_file($site_path . 'main/survey/skins/' . $file . '/' . $file . '.css')) {
						echo '            <option value="' . $file . '"' . ($rowA['skin'] == $file ? ' selected="selected"' : '') . '>' . $file . '</option>' . NEW_LINE;
					}
				}
			}
			echo '</select><br>' . NEW_LINE;
			
			
			//izbira skina za statistiko
			echo '<span class="nastavitveSpan4"><label>' . $lang['glasovanja_stat_theme'] . ':</label></span>';

			$dir = opendir($site_path . 'main/survey/skins/glasovanje/');

			echo '<select name="skin" id="skin" onChange="edit_glasovanje(\'' . $row2['spr_id'] . '\', this.value, \'skin\')">' . NEW_LINE;
			while ($file = readdir($dir)) {
				if ($file != '.' && $file != '..' && $file != '.svn' && strtolower(substr($file, -4, 4)) == '.css')
					echo '            <option value="' . substr($file, 0, -4) . '"' . ($row2['skin'] == substr($file, 0, -4) ? ' selected="selected"' : '') . '>' . substr($file, 0, -4) . '</option>' . NEW_LINE;
				elseif ($file != '.' && $file != '..' && $file != '.svn' && strtolower(substr($file, -4, 4)) != '.css') {
					if (is_file($site_path . 'main/survey/skins/' . $file . '/' . $file . '.css')) {
						echo '            <option value="' . $file . '"' . ($row2['skin'] == $file ? ' selected="selected"' : '') . '>' . $file . '</option>' . NEW_LINE;
					}
				}
			}
			echo '</select>' . NEW_LINE;
			
			echo '</fieldset>';
			
		echo '</div>';
	
		// gumb VEC
		if($displayExtra==1){
			echo '<span class="more" style="display:none;"><a href="#" onClick="glas_extra_settings();">'.$lang['srv_more'].'</a></span>';
			echo '<span class="less"><a href="#" onClick="glas_extra_settings();">'.$lang['srv_less'].'</a></span>';
		}
		else{
			echo '<span class="more"><a href="#" onClick="glas_extra_settings();">'.$lang['srv_more'].'</a></span>';			
			echo '<span class="less" style="display:none;"><a href="#" onClick="glas_extra_settings();">'.$lang['srv_less'].'</a></span>';
		}
    }
	
	/**
	* @desc prikaze vprasanje
	*/
	function vprasanje($spremenljivka) {
		global $lang;

		$Branching = new Branching($this->anketa);
		
		echo '<li id="branching_'.$spremenljivka.'" class="spr">';

		$Branching->vprasanje($spremenljivka);

		echo '</li>';
	}
	
	
	/**
    * @desc prikaze statistiko
    */
    function edit_statistika ($editmode = 0) {
        global $lang;
        global $site_path, $site_url;

		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$id = -3;
		
		$text = $row['statistics'];
		
		echo '      <div id="spremenljivka_content_'.$id.'" class="spremenljivka_content'.($editmode==1?' active':'').'" spr_id="'.$id.'" '.($editmode==0?'onclick="editmode_introconcl(\''.$id.'\');"':'').'>'."\n\r";
        if ($editmode == 0) {
			// <-- Zgornja vrstica pri editiranju vprasanj ---
			echo '<div class="spremenljivka_settings spremenljivka_settings movable" title="'.$lang['edit3'].'">';
			echo '<div style="float:left;width:auto;">';
			// variabla
			echo '<div class="variable_name" id="div_variable_'.$id.'">';
	        echo $lang['srv_statistic_label'];
			echo '</div>'."\n\r";
			echo '</div>';

			// prikažemo nastavitve vprasanja
			$fullscreen = ( isset($_POST['fullscreen']) && $_POST['fullscreen'] != 'undefined') ? (int)$_POST['fullscreen'] : false;  
	        echo '<div id="spr_settings_intro_concl" >'."\n\r";

			echo '</div>';
	
			/*if (!$fullscreen && false) {
			// right spremenljivka icon menu	
				echo '      <div class="editmenu" onClick="return false;">'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_editirajspremenljivko'].'" onclick="'.($editmode==0?'':'normalmode_introconcl(\''.$id.'\'); ').' return false;"><img src="img_'.$this->skin.'/edit.png" alt="'.$lang['srv_editirajspremenljivko'].'" /></a></span>'."\n\r";
				echo '        <span><a href="#"><img spr_id="'.$id.'" id="img_fscreen" src="icons/icons/arrow_out.png" alt="'.$lang['srv_editirajspremenljivko_fs'].'" /></a></span>'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_predogled_spremenljivka'].'" onclick="intro_concl_preview(\''.$id.'\'); return false;"><img src="img_'.$this->skin.'/preview_green.png" alt="'.$lang['srv_predogled_spremenljivka'].'" /></a></span>'."\n\r";
				echo '      </div> <!-- /editmenu -->'."\n\r";
				echo '<script>';
				echo '$(document).ready(function() {';
				echo '  $("#img_fscreen").click(function(e) { intro_concl_fullscreeen($(this).attr("spr_id"), \'2\'); e.stopPropagation();});';
				echo '  $("#img_preview").click(function(e) { intro_concl_preview($(this).attr("spr_id")); e.stopPropagation();});';
				echo '});';
				echo '</script>';
	
			}	*/	
			echo '<div class="clr"></div>';
			echo '</div>';
			// --- Zgornja vrstica pri editiranju vprasanj --> 

			// <-- Editor teksta vprasanja --- 
			echo '<div class="spremenljivka_tekst_form">';
	        echo '<div class="naslov naslov_inline" contenteditable="'.(!$this->locked?'true':'false').'" spr_id="'.$id.'" tabindex="1" '.(strpos($text, $selectall)!==false?' default="1"':'').'>'.$text.'</div>';
			echo '<div class="clr"></div>';        
			// opomba
			if ($opomba != '') {
				echo '<table style="margin-top:5px; width:100%"><tr>';
				echo '<td style="width:120px;">'.$lang['note'].' ('.$lang['srv_internal'].'):</td>';
				echo '<td >';
				echo '<span>'.$opomba.'</span>';
				echo '</td>';
				echo '</tr></table>';
			}
			echo '</div>';

			/*echo '<div class="clr"></div>';*/

        } else { // urejanje uvoda,zakljucka 

			// <-- Zgornja vrstica pri editiranju vprasanj ---
			echo '<div class="spremenljivka_settings spremenljivka_settings_active">';
			echo '<div style="float:left;width:auto;">';
			// variabla
			echo '<div class="variable_name" id="div_variable_'.$id.'">';
	        echo $lang['srv_statistic_label'];
			echo '</div>'."\n\r";
			echo '</div>';
	
			// prikažemo nastavitve vprasanja
			$fullscreen = ( isset($_POST['fullscreen']) && $_POST['fullscreen'] != 'undefined') ? (int)$_POST['fullscreen'] : false;  
	        echo '<div id="spr_settings_intro_concl" >'."\n\r";
	        echo ' <span id="visible_introconcl_'.$id.'" class="extra_opt">';
			//$this->introconcl_visible($id);
	        echo ' </span>'."\n\r";
			echo '</div>';

			if (!$fullscreen) {
			// right spremenljivka icon menu	
				echo '      <div class="editmenu" onClick="return false;">'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_preglejspremenljivko'].'" onclick="'.($editmode==0?'edit':'normal').'mode_introconcl(\''.$id.'\',\''.$editmode.'\'); return false;"><img src="img_'.$this->skin.'/palete_green.png" alt="'.$lang['srv_preglejspremenljivko'].'" /></a></span>'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_editirajspremenljivko_fs'].'" onclick="intro_concl_fullscreeen(\''.$id.'\', \'2\');  return false;"><img src="icons/icons/arrow_out.png" alt="'.$lang['srv_editirajspremenljivko_fs'].'" /></a></span>'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_predogled_spremenljivka'].'" onclick="intro_concl_preview(\''.$id.'\'); return false;"><img src="img_'.$this->skin.'/preview_green.png" alt="'.$lang['srv_predogled_spremenljivka'].'" /></a></span>'."\n\r";
				echo '      </div> <!-- /editmenu -->'."\n\r";
			}		
			echo '<div class="clr"></div>';
			echo '</div>';
			// --- Zgornja vrstica pri editiranju vprasanj --> 

	        echo '      <form name="editintro_'.substr($id, 1, 1).'" action="" method="post">'."\n\r";
			// <-- Editor teksta vprasanja --- 

			echo '<div class="spremenljivka_tekst_form">';

			echo '<div id="editor_display_' . $id. '" class="editor_display" >';
			echo '<div class="editor_display_small pointer lightRed" onclick="editor_display(\'' . $id . '\'); $(this).parent().hide();" style="width:auto;" title="'.$lang['srv_editor'].'">';
			//echo '<img src="img_' . $this->skin . '/settings.png" />';
			echo $lang['srv_editor'] . '<span style="font-size: large;">&nbsp;&#187;</span>';
			echo'</div>';
			echo '</div>';
			echo '<textarea name="naslov_' . $id . '" class="texteditor naslov" id="naslov_' . $id . '" >' . $text . '</textarea>';
			echo '<div class="clr"></div>';

			// opomba
			echo '<table style="margin-top:5px; width:100%"><tr>';
			echo '<td style="width:120px;">'.$lang['note'].' ('.$lang['srv_internal'].'):</td>';
			echo '<td >';
			echo '<textarea name="opomba" id="opomba_'.$id.'" class="texteditor info" >'.$opomba.'</textarea>';
			echo '</td>';
			echo '</tr></table>';

			echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
			echo '$(document).ready(function() {' .
			'  $("#naslov_' . $id . '").bind("blur", {}, function(e) {' .
			'    editor_save(\''.$id.'\'); return false;  ' .
			'  });' .
			'  $("#opomba_'.$id.'").bind("blur", {}, function(e) {' .
			'    editor_save(\''.$id.'\'); return false;  ' .
			'  });' .
			'});';
			echo '</script>';
			
			echo '</div>';

			echo '</form>';


			echo '<div class="save_button">';
			echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper" id="save_button_'.$id.'" ><a class="ovalbutton ovalbutton_orange" href="#" onclick="normalmode_introconcl(\''.$id.'\',\''.$editmode.'\',\''.$fullscreen.'\'); return false;"><span>';
			//echo '<img src="icons/icons/accept.png" alt="" vartical-align="middle" />';
			echo $lang['srv_potrdi'].'</span></a></div></span>';
			echo '</div>';
			echo '<div class="clr"></div>';
        }
        echo '      </div> <!-- /spremenljivka_content_'.$id.' -->'."\n\r";
		
		
		
		
		
/*
		//if ($row['statistics'] == '') {
		//	$text = $lang['results'];
		//} else {
		//	$text = $row['statistics'];
		//}
		$text = $row['statistics'];

        echo '      <div id="spremenljivka_content_'.$id.'" class="spremenljivka_content'.($editmode==1?' active':'').'" spr_id="'.$id.'" '.($editmode==0?'onclick="editmode_introconcl(\''.$id.'\');"':'').'>'."\n\r";

        if ($editmode == 0) {
			// <-- Zgornja vrstica pri editiranju vprasanj ---
			echo '<div class="spremenljivka_settings spremenljivka_settings_active">';
			echo '<div style="float:left;width:auto;">';
			// variabla
			echo '<div class="variable_name" id="div_variable_'.$id.'">';
			echo $lang['srv_statistics_edit'];
			echo '</div>'."\n\r";
			echo '</div>';		
			echo '<div class="clr"></div>';
			echo '</div>';
			// --- Zgornja vrstica pri editiranju vprasanj --> 

			// <-- Editor teksta vprasanja --- 
			echo '<div class="spremenljivka_tekst_form">';

			echo '<span>'.$text.'</span>';
			echo '<div class="clr"></div>';        
						
			echo '</div>';
					
			// --- Editor teksta vprasanja --> 



			echo '<div class="clr"></div>';

        } 
		else {

			// <-- Zgornja vrstica pri editiranju vprasanj ---
			echo '<div class="spremenljivka_settings spremenljivka_settings_active">';
			echo '<div style="float:left;width:auto;">';
			// variabla
			echo '<div class="variable_name" id="div_variable_-3">';
			echo $lang['srv_statistics_edit'];
			echo '</div>'."\n\r";
			echo '</div>';	
			echo '<div class="clr"></div>';
			echo '</div>';
			// --- Zgornja vrstica pri editiranju vprasanj --> 

			echo '      <form name="editintro_'.substr($id, 1, 1).'" action="" method="post">'."\n\r";
			// <-- Editor teksta vprasanja --- 
			echo '<div class="spremenljivka_tekst_form">';
			echo '<div id="editor_display_'.$id.'" class="editor_display" onclick="editor_display(\''.$id.'\'); $(this).hide();"><small>Editor</small></div>';
			echo '<textarea name="naslov_'.$id.'" class="texteditor naslov" id="naslov_'.$id.'" >'.$text.'</textarea>';
			echo '<div class="clr"></div>';        
						
			echo '</div>';
			echo '</form>';
					
			// --- Editor teksta vprasanja --> 

			echo '<div class="save_button">';
			echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper" id="save_button_'.$id.'" ><a class="ovalbutton ovalbutton_orange" href="#" onclick="normalmode_introconcl(\''.$id.'\',\''.$editmode.'\',\''.$fullscreen.'\'); return false;"><span>';
			echo $lang['srv_potrdi'].'</span></a></div></span>';
			echo '</div>';
			echo '<div class="clr"></div>';
        }

        echo '      </div> <!-- /spremenljivka_content_'.$id.' -->'."\n\r";
*/
    }


    /**
    * @desc pohendla ajax requeste
    */
    function ajax () {
		global $lang;
		global $site_url;
      
		if (isset ($_POST['results']))
			$results = $_POST['results']; 
			
		if (isset ($_POST['spremenljivka']))
			$spremenljivka = $_POST['spremenljivka'];
			
		if (isset ($_POST['what']))
			$what = $_POST['what'];
		
		$displayExtra = (isset($_POST['displayExtra'])) ? $_POST['displayExtra'] : 0;
		
		if ($_GET['a'] == 'glasovanje_settings') {
			
			if($what == 'stat'){
				sisplet_query("UPDATE srv_spremenljivka SET stat = '$results' WHERE id = '$spremenljivka'");
				
				// ce vklopimo statistiko, izklopimo zakljucek
				if($results != 0)
					sisplet_query("UPDATE srv_anketa SET show_concl='0' WHERE id = '$this->anketa'");
				else
					sisplet_query("UPDATE srv_anketa SET show_concl='1' WHERE id = '$this->anketa'");
			}

			elseif($what == 'show_intro' || $what == 'show_concl' || $what == 'cookie' || $what == 'user_from_cms' || $what == 'block_ip' || $what == 'starts' || $what == 'expire' || $what == 'vote_limit' || $what == 'vote_count' || $what == 'countType' || $what == 'progressbar') {
				sisplet_query("UPDATE srv_anketa SET $what = '$results' WHERE id = '$this->anketa'");
			}
			
			elseif($what == 'survey_hide_title') {
				SurveySetting::getInstance()->Init($this->anketa);
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_hide_title', $results);
			}

			elseif($what == 'finish_author' || $what == 'finish_respondent_cms' || $what == 'finish_other' | $what == 'finish_other_emails') {
				sisplet_query("INSERT INTO srv_alert (ank_id, $what) VALUES ('$this->anketa', '$results')
				ON DUPLICATE KEY UPDATE $what = '$results' ");

				//sisplet_query("UPDATE srv_alert SET $what = '$results' WHERE ank_id = '$this->anketa'");
			}
			
			elseif($what == 'akronim') {
				if($results == 0)
					sisplet_query("UPDATE srv_anketa SET $what = ' ' WHERE id = '$this->anketa'");
				elseif($results == 1)
					sisplet_query("UPDATE srv_anketa SET $what = '' WHERE id = '$this->anketa'");
				else
					sisplet_query("UPDATE srv_anketa SET $what = '$lang[poll]' WHERE id = '$this->anketa'");
			}
			
			elseif($what == 'skin_anketa') {
				sisplet_query("UPDATE srv_anketa SET skin = '$results' WHERE id = '$this->anketa'");
			}

			elseif($what == 'embed'){
				sisplet_query("UPDATE srv_glasovanje SET $what = '$results' WHERE spr_id = '$spremenljivka'");
				
				$rowS = Cache::srv_spremenljivka($spremenljivka);				
				if($results == 1 && $rowS['stat'] == 0){
					// updatamo skin, ne prikazemo gumba konec, vklopimo zakljucek
					$url = SurveyInfo::getSurveyLink();
					sisplet_query("UPDATE srv_anketa SET skin='Embed', concl_link='0', url='', concl_end_button='0', concl_back_button='0', show_concl='1' WHERE id = '$this->anketa'");
				}
				elseif($results == 1 && $rowS['stat'] > 0){
					sisplet_query("UPDATE srv_anketa SET skin='Embed', concl_link='0', url='', concl_end_button='0', concl_back_button='0' WHERE id = '$this->anketa'");
				}
				else{
					sisplet_query("UPDATE srv_anketa SET skin='Default', concl_end_button='1', concl_back_button='1' WHERE id = '$this->anketa'");
				}
			}
			
			else{
				sisplet_query("UPDATE srv_glasovanje SET $what = '$results' WHERE spr_id = '$spremenljivka'");			
			}

			if($what == 'spol'){
				//ustvarimo vprasanje za spol
				if($results == 1){

					$sqlGrupe = sisplet_query("SELECT id, naslov FROM srv_grupa g WHERE g.ank_id='$this->anketa' ORDER BY g.vrstni_red");
					$rowGrupe = mysqli_fetch_assoc($sqlGrupe);

					$grupa = $rowGrupe['id'];

					//ustvarimo v bazi novo spremenljivko
					$b = new Branching($this->anketa);
					$spr_id = $b->nova_spremenljivka($grupa, 1, 1);

					$sqlSpr = sisplet_query("UPDATE srv_spremenljivka SET size='2', naslov='Spol', vrstni_red='2' WHERE id='$spr_id'");

					//dodamo 2 vrednosti (moski in zenska)
					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id'");
					$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, other) VALUES ('', '$spr_id', 'Moški', 'M', '1', '')");
					$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, other) VALUES ('', '$spr_id', 'Ženska', 'Ž', '2', '')");
				}
				//zbrisemo vprasanje za spol
				else{
					$sqlS = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.vrstni_red='2'");
					$rowS = mysqli_fetch_array($sqlS);

					$spr_id = $rowS['id'];

					//pobrisemo iz baze spremenljivko
					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id'");
					$sql = sisplet_query("DELETE FROM srv_spremenljivka WHERE id='$spr_id'");
				}
			}

			// Vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();
			
			$this->display_glasovanje_settings($displayExtra);
		}
		
		else if ($_GET['a'] == 'glasovanje_vprasanja') {

			$this->vprasanja();
		}
	}
}

?>