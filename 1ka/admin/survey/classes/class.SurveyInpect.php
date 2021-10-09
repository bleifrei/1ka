<?php
/**
 * @author 	Gorazd Veselič
 * @date		November 2011
 *
 */

define('SI_DEFAULT_PROFILE', false);
define('SI_GOTO_ANALIZE', 0);
define('SI_GOTO_VPOGLED', 1);
define('SI_GOTO_PODATKI', 2);

class SurveyInspect {

	public $sid;									# id ankete
	public $_HEADERS = array();						# shranimo podatke vseh variabel

	function __construct($anketa) {

		global $global_user_id;

		$this->sid = $anketa;


		# Inicializiramo in polovimo nastavitve missing profila
		SurveyStatusProfiles::Init($this->sid);
		SurveyUserSetting::getInstance()->Init($anketa, $global_user_id);

		#inicializiramo class za datoteke
		SurveyDataFile::get_instance();
		SurveyDataFile::get_instance()->init($anketa);
		$headFileName = SurveyDataFile::get_instance()->getHeaderFileName();
		$dataFileStatus = SurveyDataFile::get_instance()->getStatus();
		

		# polovimo header datoteko
		if ($dataFileStatus == FILE_STATUS_NO_DATA
				|| $dataFileStatus == FILE_STATUS_NO_FILE
				|| $dataFileStatus == FILE_STATUS_SRV_DELETED){
			return false;
		}
		if ($headFileName !== null && $headFileName != '') {
			$this->_HEADERS = unserialize(file_get_contents($headFileName));
		}
		
		session_start();
		#nastavimo inspect
		if (isset($_SESSION['enableInspect']) && $_SESSION['enableInspect'] == true) {
			$this->enableInspect = true;
		} else {
			$this->enableInspect = false;
		}
		
		#nastavimo GOTO (analize,vpogled,podatki)
		if (isset($_SESSION['inspect_goto'])) {
			$this->inspect_goto = (int)$_SESSION['inspect_goto'];
		} else {
			$this->inspect_goto = 0;
		}

	}

	public function isInspectEnabled() {
		return $this->enableInspect;
	}

	public function whereToGo() {

		$inspect_goto_array = array( SI_GOTO_ANALIZE => '&a=analysis&m=sumarnik',
				SI_GOTO_VPOGLED => '&a=data&m=quick_edit',
				SI_GOTO_PODATKI => '&a=data');

		return ($inspect_goto_array[$this->inspect_goto]);
	}

	public function ajax() {

		switch ($_GET['a']) {
			case 'analizaPrepareInspect':
				$this->PrepareInspectAnaliza();
				break;
			case 'changeSessionInspect':
				$this->changeSessionInspect();
				break;
			case 'showInspectSettings':
				$this->showInspectSettings();
				break;
			case 'show_inspectListSpr':
				$this->showInspectListSpr();
				break;
			case 'saveInspectListVars':
				$this->saveInspectListVars();
				break;
			case 'saveSettings':
				$this->saveSettings();
				break;
			case 'displayInspectVars':
				$this->displayInspectVars();
				break;
			case 'removeInspect':
				$this->removeInspect();
				break;
			default:
				print_r("<PRE>");
				print_r($_POST);
				print_r($_GET);
				print_r("<PRE>");
				break;
		}
	}

	function PrepareInspectAnaliza() {
		global $global_user_id, $lang;

		# nastavimo filter variable
		#izluščimo spr_id
		$spr_data = explode('_',$_POST['spr_data']);
		$spr = $spr_data[0];
		$sequence = $spr_data[2];
		$counter = $spr_data[3];
		$_spr = $this->_HEADERS[$spr.'_0'];
		# nastavimo v sejo od kod smo prišli v inspect:
		session_start();
		if ((isset($_POST['from_podstran']) && trim($_POST['from_podstran']) != '')) {
			$_SESSION['inspectFromPodstran'][$this->sid] = $_POST['from_podstran'];
		} else {
			unset($_SESSION['inspectFromPodstran'][$this->sid]);
		}
		session_commit();
		# naredimo inspect profil za variable
		$variables = $spr.'_0';
		$var_array[] = $variables;
		if ($this->isInspectEnabled()) {
			$_add_vars = $_SESSION['dataSetting_profile'][$this->sid]['InspectListVars'];
			if (isset($_add_vars) && is_array($_add_vars) && count($_add_vars) > 0) {
				foreach ($_add_vars AS $add_var) {
					$variables .= ','.$add_var.'_0';
					$var_array[] = $add_var.'_0';
				}
			}
		}

		$svp = new SurveyVariablesProfiles();
		$svp -> Init($this->sid);
		$svp-> setProfileInspect($var_array);

		# if id za inspect shranimo v nastavitev ankete SurveyUserSetting -> inspect_if_id (če ne obstaja skreiramo novega)
		# dodamo tudi kot profil pogojev (če ne obstaja skreiramo novega)

		#preverimo ali obstaja zapis v SurveyUserSetting->inspect_if_id
		$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
		# preverimo dejanski obstoj ifa (srv_if) če ne skreiramo novega
		if ((int)$if_id > 0) {
			$chks1 = "SELECT id FROM srv_if WHERE id='$if_id'";
			$chkq1 = sisplet_query($chks1);
			# dodamo še k profilu če ne obstaja
			if (mysqli_num_rows($chkq1) == 0) {
				$if_id = null;
				SurveyUserSetting :: getInstance()->removeSettings('inspect_if_id');
			}
		}

		if ( (int)$if_id == 0 || $if_id == null) {
			# if še ne obstaja, skreiramo novga
			$newIfString = "INSERT INTO srv_if (id) VALUES ('')";
			$sql = sisplet_query($newIfString);
			#			if (!$sql) echo '<br> -1';
				
			$if_id = mysqli_insert_id($GLOBALS['connect_db']);
			sisplet_query("COMMIT");
			# shranimo pogoj kot privzet pogoj z ainspect
			SurveyUserSetting :: getInstance()->saveSettings('inspect_if_id',(int)$if_id);
		}
		if ((int)$if_id > 0) {
			# dodamo ifa za obe variabli
			# ne brišemo starih pogojev, da omogočimo gnezdenje
			#$delStr = "DELETE FROM srv_condition WHERE if_id = '$if_id'";
			#sisplet_query($delStr);

			# poiščemo vrednosti za oba vprašanja
			$condition = $this->createSubCondition($_POST['vkey'],$if_id,$spr,$_spr,$sequence);
			sisplet_query("COMMIT");
				
			# pogoj dodamo še v srv_condition_profile vendar ga ne nastavimo kot privzetega
			$chk_if_str = "SELECT id FROM srv_condition_profiles WHERE sid='".$this->sid."' AND uid = '".$global_user_id."' AND type='inspect'";
			$chk_if_qry = sisplet_query($chk_if_str);
			$_tmp_name = $lang['srv_inspect_temp_profile'];
			if (mysqli_num_rows($chk_if_qry) > 0) {
				# if že obstaja popravimo morebitne podatke
				$str = "UPDATE srv_condition_profiles SET name = '$_tmp_name', if_id='$if_id'";
				$sql = sisplet_query($str);
			} else {
				#vstavimo nov profil pogojev - inspect
				$str = "INSERT INTO srv_condition_profiles (sid, uid, name, if_id, type ) VALUES ('".$this->sid."', '".$global_user_id."', '$_tmp_name', '$if_id', 'inspect')"
				. " ON DUPLICATE KEY UPDATE name='$_tmp_name', if_id='$if_id'";
				$sql = sisplet_query($str);
			}
			sisplet_query("COMMIT");
		}
		echo $this->whereToGo();
		return $this->whereToGo();

	}

	function createSubCondition($vrednost,$if_id,$spr,$_spr,$sequence) {
		$tip = $_spr['tip'];
		# 1. Radio
		# 3. Dropdown
		# 2. Select - checkbox
		if ($tip == '1' || $tip == '3' || $tip == '2') 
		{
			#radio in dropdown
			if ($tip == '1' || $tip == '3') 
			{
				#s pomočjo k preberemo stni red
				$sql_string = "SELECT id FROM srv_vrednost WHERE spr_id='$spr' AND variable = '".$vrednost."'";
				$sql_query = sisplet_query($sql_string);
				if (mysqli_num_rows($sql_query) == 1 ) 
				{
					$sql_row = mysqli_fetch_assoc($sql_query);
					$vred_id = $sql_row['id'];
				}
			}
			#select
			if ($tip == '2' ) 
			{
				$vred_id=null;
				# če je čekbox poiščemo vred_id za sekvenco k
				foreach ($_spr['grids'] as $gkey=>$grid) 
				{
					foreach ($grid['variables'] as $vkey=>$variable) 
					{
						if ($variable['sequence'] == $sequence) 
						{
							$vred_id = $variable['vr_id'];
						}
					}
				}
			}

		if ($vred_id != null && (int)$vred_id > 0) {
			if ($tip == '2' && $vrednost == 0) {
				$_operator_str = ', operator';
				$_operator_val = ", '1'";
				$_operator_repl = ", operator = '1'";
			}
			$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red".$_operator_str.") VALUES ('$if_id', '$spr', '1'".$_operator_val.")"
			. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1'".$_operator_repl;
			$sql = sisplet_query($istr);
			if (!$sql)  {
				echo '<br>-3 :: '.$istr;
				echo mysqli_error($GLOBALS['connect_db']);
			}
			$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
			if ((int)$vred_id > 0 || (int)$cond_id > 0) {
				$istr = "INSERT INTO srv_condition_vre (cond_id, vre_id) VALUES ('$cond_id', '$vred_id')";
				$sql = sisplet_query($istr);
				if (!$sql)  {
					echo '<br>-4 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
					
			}
			return $cond_id;
		}
		}
		# 6. multi radio
		if ($tip == '6' ) {
			# če je dvojni grid potem posebej polovimo vrednosti
			list($enota) = mysqli_fetch_row(sisplet_query("SELECT enota FROM srv_spremenljivka WHERE id='$spr'"));
		if($enota != 3) {
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			foreach ($_spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $sequence) {
						$vred_id = $variable['vr_id'];
					}
				}
			}
			$sql_string = "SELECT id FROM srv_grid WHERE spr_id='$spr' AND (variable = '".$vrednost."' OR other = '".$vrednost."')";
			$sql_query = sisplet_query($sql_string);
			if (mysqli_num_rows($sql_query) == 1 ) {
				$sql_row = mysqli_fetch_assoc($sql_query);
				$vrednost_id = $sql_row['id'];
			}
		} else {
			$vred_id = $sequence;
			# za dvojni grid moramo id polovit s pomočjo part
			$_tmp = explode('_',$vrednost);
			$vrednost = $_tmp[0];
			$part = $_tmp[1];
			$sql_string = "SELECT id FROM srv_grid WHERE spr_id='$spr' AND (variable = '".$vrednost."' OR other = '".$vrednost."') AND part = '$part'";
			$sql_query = sisplet_query($sql_string);
			if (mysqli_num_rows($sql_query) == 1 ) {
				$sql_row = mysqli_fetch_assoc($sql_query);
				$vrednost_id = $sql_row['id'];
			}
		}

			
		if ($vred_id !== null && (int)$vred_id > 0) {
			$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id) VALUES ('$if_id', '$spr', '1', '$vred_id')"
			. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1', vre_id='$vred_id'";
			$sql = sisplet_query($istr);

			if (!$sql)  {
				echo '<br>-3 :: '.$istr;
				echo mysqli_error($GLOBALS['connect_db']);
			}
			$cond_id = mysqli_insert_id($GLOBALS['connect_db']);

			#dodamo še v srv_grid
			if ($cond_id > 0) {
				$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$vrednost_id."')";
				$sql = sisplet_query($istr);
				if (!$sql)  {
					echo '<br>-4 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
					
			} else {
				echo '<br>-5 :: ';
			}
			return $cond_id;
		}
		}
		# 7. Number
		if ($tip == '7' ) {
				
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			foreach ($_spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $sequence) {
						$grid_id = $vkey;
					}
				}
			}

			if ($grid_id !== null) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, grd_id, text) VALUES ('$if_id', '$spr', '1', '$grid_id', '$vrednost')"
				. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1', grd_id='$grid_id', text='$vrednost'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				return $cond_id;
			}
		}
		# 16. multi checkbox
		if ($tip == '16' ) {
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			foreach ($_spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $sequence) {
						$vred_id = $variable['vr_id'];
						$grid_id = $variable['gr_id'];
					}
				}
			}
			if ($vrednost == 0) {
				$_operator_str = ', operator';
				$_operator_val = ", '1'";
				$_operator_repl = ", operator = '1'";
			}
				
			if ($vred_id !== null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id".$_operator_str.") VALUES ('$if_id', '$spr', '1', '$vred_id'".$_operator_val.")"
				. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1', vre_id='$vred_id'".$_operator_repl;
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);

				#dodamo še v srv_grid
				if ($cond_id > 0 && $grid_id > 0) {
					$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$grid_id."')";
					$sql = sisplet_query($istr);
					if (!$sql)  {
						echo '<br>-4 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
						
				} else {
					echo '<br>-5 :: ';
				}
				return $cond_id;
			}
		}
		# 17. razvrščanje ranking
		if ($tip == '17' ) {
				
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			foreach ($_spr['grids'] AS $gkey=> $grid) {
			foreach ($grid['variables'] AS $vkey => $variable) {
				if ($variable['sequence'] == $sequence) {
					$vred_id = $variable['vr_id'];

				}
			}
		}
		if ($vred_id !== null && (int)$vred_id > 0) {
			$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id) VALUES ('$if_id', '$spr', '1', '$vred_id')"
			. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1', vre_id='$vred_id'";
			$sql = sisplet_query($istr);

			if (!$sql)  {
				echo '<br>-3 :: '.$istr;
				echo mysqli_error($GLOBALS['connect_db']);
			}
			$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
			$grid_id = $_spr['options'][$vrednost];
			#dodamo še v srv_grid
			if ($cond_id > 0 && $grid_id > 0) {
				$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$grid_id."')";
				$sql = sisplet_query($istr);
				if (!$sql)  {
					echo '<br>-4 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}

			} else {
				echo '<br>-5 :: ';
			}
			return $cond_id;
		}
		}
		# 21. besedilo
		# 18. vsota
		if ($tip == '21' || $tip == '18') {
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
				
			foreach ($_spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $sequence) {
						$vred_id = $variable['vr_id'];
					}
				}
			}
				
			if ($vred_id !== null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id, text) VALUES ('$if_id', '$spr', '1', '$vred_id', '$vrednost')"
				. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1', vre_id='$vred_id', text='$vrednost'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				return $cond_id;
			}
		}

		# 19. multi text
		# 20. multi number
		if ($tip == '19' || $tip == '20') {
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			foreach ($_spr['grids'] AS $gkey=> $grid) {
			foreach ($grid['variables'] AS $vkey => $variable) {
				if ($variable['sequence'] == $sequence) {
					$vred_id = $variable['vr_id'];
					$grid_id = $variable['gr_id'];
				}
			}
		}
			
		if ($vred_id !== null && (int)$vred_id > 0 && $grid_id > 0) {
			$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id, grd_id, text) VALUES ('$if_id', '$spr', '1', '$vred_id', '$grid_id', '$vrednost')"
			. " ON DUPLICATE KEY UPDATE spr_id='$spr', vrstni_red = '1', grd_id='$grid_id', text='$vrednost'";
			$sql = sisplet_query($istr);

			if (!$sql)  {
				echo '<br>-3 :: '.$istr;
				echo mysqli_error($GLOBALS['connect_db']);
			}
			$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
			return $cond_id;
		}
		}

		return null;
	}
	function changeSessionInspect() {
		session_start();
		#Zamenjamo sejo
		if (isset($_SESSION['enableInspect']) && $_SESSION['enableInspect'] == true) {
			unset($_SESSION['enableInspect']);
		} else {
			$_SESSION['enableInspect'] = true;
		}
		session_commit();

		#nastavimo inspect
		if (isset($_SESSION['enableInspect']) && $_SESSION['enableInspect'] == true) {
			$this->enableInspect = true;
		} else {
			# če ne preberemo iz profila
			$this->enableInspect = false;
		}
			
		$this->displaySessionInspectCheckbox((isset($_POST['isAnaliza']) && (int)$_POST['isAnaliza'] == 1) ? true : false);
	}

	function DisplayLink($hideAdvanced = true) {
		global $lang;

		$css = ($this->enableInspect == SI_DEFAULT_PROFILE ? ' gray' : '');
		if ($hideAdvanced == false || $this->enableInspect != SI_DEFAULT_PROFILE) {
			echo '<li class="space">&nbsp;</li>';
			echo '<li>';
			echo '<span class="as_link'.$css.'" id="link_inspect" title="' . $lang['srv_inspect_setting'] . '" onClick="show_inspect_settings();">' . $lang['srv_inspect_setting'] . '</span>'."\n";
			echo '</li>';
			 
		}
	}
	function displaySessionInspectCheckbox($isAnaliza=false) {
		global $lang;
		if ($isAnaliza == true) {
			echo '<input type="checkbox" id="session_inspect" '.($this->enableInspect == true ? ' checked="checekd"' : '').' onClick="changeSessionInspectAnaliza();">'.$lang['srv_inspect_setting'];
		} else {
			echo '<input type="checkbox" id="session_inspect" '.($this->enableInspect == true ? ' checked="checekd"' : '').' onClick="changeSessionInspect();">'.$lang['srv_inspect_setting'];
		}

		echo Help :: display('srv_crosstab_inspect');
	}

	function showInspectSettings() {
		global $lang;

        echo '<div class="popup_close"><a href="#" onClick="inspectCloseSettings(); return false;">✕</a></div>';

		// Naslov
		echo '<h2>'.$lang['srv_inspect_setting'].'</h2>';
		
		if ( $this->enableInspect != SI_DEFAULT_PROFILE ) {
			echo '<div id="not_default_setting">';
			echo $lang['srv_not_default_setting'];
			echo '</div><br class="clr displayNone">';
		}

		# Nastavitve za Inspect
		echo '<p>'.$lang['srv_inspect_setting_link'].'</p>';
		echo '<p>';
		echo '<label>'.$lang['srv_inspect_setting_enabled'].'</label>';
		echo '&nbsp;<input id="enableInspect0" name="enableInspect" type="radio" value="0"' .
				(($this->enableInspect == false) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo '<label for="enableInspect0">'.$lang['no'].'</label>';

		echo '&nbsp;<input id="enableInspect1" name="enableInspect" type="radio" value="1"' .
				(($this->enableInspect == true) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo '<label for="enableInspect1">'.$lang['yes'].'</label>';
		echo Help :: display('srv_crosstab_inspect');
		echo '</p>';
		echo '<p>';
		echo '<div>';
		echo '<span class="floatLeft">'.$lang['srv_inspect_goto_note'].'</span>&nbsp;';
		echo '<span class="floatLeft">';
		echo '<label><input type="radio" name="inspectGoto" id="inspectGoto_'.SI_GOTO_ANALIZE.'" value="'.SI_GOTO_ANALIZE.'"'.($this->inspect_goto == SI_GOTO_ANALIZE ? ' checked="checked"' : '').' onchange="inspectRadioChange();return false;" autocomplete="off"/>'.$lang['srv_inspect_goto_'.SI_GOTO_ANALIZE].'</label><br/>';
		echo '<label><input type="radio" name="inspectGoto" id="inspectGoto_'.SI_GOTO_VPOGLED.'" value="'.SI_GOTO_VPOGLED.'"'.($this->inspect_goto == SI_GOTO_VPOGLED ? ' checked="checked"' : '').' onchange="inspectRadioChange();return false;" autocomplete="off"/>'.$lang['srv_inspect_goto_'.SI_GOTO_VPOGLED].'</label> <br/>';
		echo '<label><input type="radio" name="inspectGoto" id="inspectGoto_'.SI_GOTO_PODATKI.'" value="'.SI_GOTO_PODATKI.'"'.($this->inspect_goto == SI_GOTO_PODATKI ? ' checked="checked"' : '').' onchange="inspectRadioChange();return false;" autocomplete="off"/>'.$lang['srv_inspect_goto_'.SI_GOTO_PODATKI].'</label>';
		echo '</span>';
		echo '</div>';
		echo'</p>';
		echo'<br class="clr">';
		echo '<p>';
		echo '<div id="inspectListDiv" '.($this->inspect_goto != SI_GOTO_PODATKI ? ' class="displayNone"' : '').'>';
		echo '<span>'.$lang['srv_inspect_setting_show_variables'].'</span>';
		echo '<span id="inspectListSpr" class="as_link">';
		$this->displayInspectVars();
		echo '</span>';
		echo '</div>';
		echo '</p>';

		echo '<span class="floatRight" title="'.$lang['srv_save_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inspectSaveSettings(); return false;"><span>'.$lang['srv_save_profile'] . '</span></a></div></span>';
		echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inspectCloseSettings(); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';

		// cover Div
		echo '<div id="inspect_cover_div"></div>'."\n";

	}

	function displayInspectVars() {
		global $lang;
		$vars = $_SESSION['dataSetting_profile'][$this->sid]['InspectListVars'];

		if (is_array($vars) && count($vars) > 0 ) {
			$stringSelect = "SELECT variable FROM srv_spremenljivka WHERE id IN (".implode(',',$_SESSION['dataSetting_profile'][$this->sid]['InspectListVars']).") ORDER BY vrstni_red";
				
			$querySelect = sisplet_query($stringSelect);
			$prefix = '&nbsp;';
			while ( list($variable) = mysqli_fetch_row($querySelect) ) {
				echo $prefix.$variable;
				$prefix = ', ';
			}
		} else {
			echo $lang['srv_inspect_no_variables'];
		}
	}

	function showInspectListSpr() {

		global $lang, $site_url;
		$all_spr = Cache::cache_all_srv_spremenljivka($this->sid);
		$vars = $_SESSION['dataSetting_profile'][$this->sid]['InspectListVars'];
		echo '<div id="dsp_inspect_cover">';
		echo '<div id="dsp_inspect_spr_select">';

		echo '<span>'.$lang['srv_inspect_choose'].'</span>';
		foreach ($all_spr AS $id => $spremenljivka) {
			echo '<div class="dsp_inspect_var">';
			echo '<input name="dsp_inspect_vars" id="dsp_inspect_var_'.$spremenljivka['id'].'" value="'.$spremenljivka['id'].'" type="checkbox"'
			.(is_array($vars) && in_array($spremenljivka['id'],$vars) ? ' checked' : '').'>';
			echo '<label for="dsp_inspect_var_'.$spremenljivka['id'].'">'.$spremenljivka['variable'].' - '.strip_tags($spremenljivka['naslov']).'</label>';
			echo '</div>';
		}

		echo '</div>';
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span id="dsp_inspect_cancel" class="floatLeft spaceLeft buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#"><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<span id="dsp_inspect_save" class="floatRight spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#"><span>'.$lang['srv_zapri'].'</span></a></span>';
		echo '<div class="clr" />';
		echo '</div>';

		#echo '</div>';
		echo '</div>';
	}

	function saveInspectListVars() {
		if (count($_POST['vars']) > 0 ) {
			$_SESSION['dataSetting_profile'][$this->sid]['InspectListVars'] = $_POST['vars'];
		} else {
			unset($_SESSION['dataSetting_profile'][$this->sid]['InspectListVars']);
		}
	}

	function saveSettings() {
		if (isset($_POST['enableInspect']) && $_POST['enableInspect'] == 1 ) {
			$this->enableInspect = true;
			$_SESSION['enableInspect'] = true;
		} else {
			unset($_SESSION['enableInspect']);
			$this->enableInspect = false;
		}

		$this->inspectGoto = (int)$_POST['inspectGoto'];
		$_SESSION['inspect_goto'] = $this->inspectGoto;
	}


	function getConditionString() {
		global $lang;
		#preverimi ali imamo nastavljen pogoj za inspect
		$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
		if ($if_id > 0) {
			ob_start();
			$b = new Branching($this->sid);
				
			$b->display_if_label($if_id);
			#$condition_label = mysqli_escape_string(ob_get_contents());
			$condition_label = ob_get_contents();
			ob_end_clean();

			if ( $if_id > 0 && $condition_label != '') {

				echo '<div id="conditionProfileNote">';
				echo '<span class="floatLeft">'.$lang['srv_profile_data_is_filtred_zoom'].'</span>';
				echo '<span class="floatLeft spaceLeft">'.$condition_label.'</span>';
				// ali imamo napake v ifu
				#TODO
				#	if ((int)self::$profiles[self :: $currentProfileId]['condition_error'] != 0) {
				#		echo '<br>';
				#		echo '<span style="border:1px solid #009D91; background-color: #34D0B6; padding:5px; width:auto;"><img src="img_0/error.png" /> ';
				#		echo '<span class="red strong">'.$lang['srv_profile_condition_has_error'].'</span>';
				#		echo '</span>';
				#	}
				#
				session_start();
				global $site_url;
				if (isset($_SESSION['inspectFromPodstran'][$this->sid])) {
					$inspect_comeFrom = '\''.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a=analysis&m='.$_SESSION['inspectFromPodstran'][$this->sid].'\'';
					unset($_SESSION['inspectFromPodstran'][$this->sid]);
				} else {
					$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
					$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
					$inspect_comeFrom = '\''.$pageURL.'\'';
				}
				echo '<span class="as_link spaceLeft" id="link_condition_edit">'.$lang['srv_profile_edit'].'</span>';
				echo '<span class="as_link spaceLeft" onclick="window.location=\'index.php?anketa='.$this->sid.'&a=data&m=quick_edit&quick_view=1\'">'.$lang['srv_zoom_link_whoisthis'].'</span>';
				echo '<span class="as_link spaceLeft" onclick="doZoomFromInspect();return false">Segmentiraj'.$lang[''].'</span>';
				echo '<span class="as_link spaceLeft" onclick="inspectRemoveCondition('.$inspect_comeFrom.');">'.$lang['srv_profile_remove'].'</span>';
				echo '</div>';
				echo '<br class="clr" />';
				return true;
			}
		}
		return false;
	}

	function removeInspect() {
		#preverimi ali imamo nastavljen pogoj za inspect
		$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
		if ($if_id > 0) {
			# odstranimo pogoj, srv_if
			$delStr = "DELETE FROM srv_if WHERE if = '$if_id'";
			sisplet_query($delStr);

			# odstranimo condition profil: srv_condition profile
			$delStr = "DELETE FROM srv_condition_profiles WHERE sid='".$this->sid."' AND type='inspect' AND if_id = '$if_id'";
			sisplet_query($delStr);
				
			#odstranimo zapis za inspect
			SurveyUserSetting :: getInstance()->removeSettings('inspect_if_id');
		}
	}

	function generateAwkCondition() {
		global $global_user_id;

		#preverimi ali imamo nastavljen pogoj za inspect
		$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
		if ($if_id > 0) {
			SurveyConditionProfiles :: Init($this->sid, $global_user_id);
			return SurveyConditionProfiles:: generateAwkCondition($if_id);
		} else {
			return null;
		}
	}

	public function getInspectVariables() {
		global $global_user_id;
		$vars = array();
		#preverimi ali imamo nastavljen pogoj za inspect
		$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
		if ($if_id > 0) {
			$sql = sisplet_query("SELECT spr_id, vre_id FROM srv_condition WHERE if_id = '$if_id'");
			while (list($spr_id, $vre_id) = mysqli_fetch_row($sql)) {
				if ((int)$vre_id > 0) {
					$vars[] = $spr_id.'_'.$vre_id;
				} else {
					$vars[] = $spr_id;
				}
			}
		}
		return $vars;
	}
}