<?php
/** @author: Gorazd Veselič
 * 
 * 	@Desc: za upravljanje z profili missingov za analize

 *  @Date: 28.09.2010
 */

session_start();
define('SMP_DEFAULT_PROFILE', 1);

class SurveyMissingProfiles
{
	static private $sid = null;					# id ankete
	static private $uid = null;					# id userja
	static private $inited = false;				# ali so profili ze inicializirani
	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika
	static private $smv = null;					# manjkajoče vrednosti
	
																		
																		// lurker je mal poseben, ker je neodvisen od ostalih (user je npr. 6 in lurker)
//	static private $allStatus = array('null',0,1,2,3,4,5,6,'lurker'); 			// Statusi anket katere štejemo kot ustrezne
//	static private $appropriateStatus = array(6,5); 			// Statusi anket katere štejemo kot ustrezne
//	static private $unAppropriateStatus = array(4,3,2,1,0); 	// Statusi anket katere štejemo kot neustrezne
//	static private $unKnownStatus = array('null'); 					// Statusi anket katere štejemo kot neustrezne
//	# za awk komando 
//	static private $_AWK_FILTER_TEXT = array(6=>'6',5=>'5',4=>'4',3=>'3',2=>'2',1=>'^1',0=>'0','null'=>'-1'); # texti za statuse. 6=>'6',5=>'5',4=>'4',3=>'3',2=>'2',1=>'^1',0=>'0',null=>'-1' Pri 1 je ^pomemben, če ne lahko vzame tudi -1
	
	static function Init($sid, $uid = null) {
		# nastavimo surveyId
		self::setSId($sid);
		self::$smv = new SurveyMissingValues($sid); 
		self::$smv -> Init();
		# nastavimo userja
		self::setGlobalUserId($uid);
		SurveyUserSetting :: getInstance()->Init(self::$sid, self::getGlobalUserId());
		if (self::$inited == false) {
			self::$inited = self :: RefreshData();
		}
	}
	
	static function RefreshData() {
		
		self::$profiles = array();
		# dodamo sejo če obstaja
		
		if (isset($_SESSION['missingProfile'])) {
			self::$profiles[$_SESSION['missingProfile']['id']] = $_SESSION['missingProfile'];
		}
		# dodamo sistemske profile, skreiramo jih "on the fly"
		self :: addSystemProfiles();
		
		# preberemo podatke vseh porfilov ki so na voljo in jih dodamo v array
		$stringSelect = "SELECT * FROM srv_missing_profiles WHERE uid = '".self::getGlobalUserId()."' OR (uid = '0' AND `system` = 1) ORDER BY id";
		$querySelect = sisplet_query($stringSelect);

		if (mysqli_num_rows($querySelect)) {
			while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = $rowSelect;
			}
		}
		# poiscemo privzet profil
		self::$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_missing_profile');

		if (!self::$currentProfileId || self::$currentProfileId == 1)
			self::$currentProfileId = 1;

		# ce imamo nastavljen curent pid in profil z tem pid ne obstaja nastavomo na privzet profil 
		if (self::$currentProfileId != 1) {
			if (!isset(self::$profiles[self::$currentProfileId])) {
				self::$currentProfileId = 1;
				self::setDefaultProfileId(self::$currentProfileId);
			} 
		}
		
		# ce ne obstajajo podatki za cpid damo error
		if (!isset(self::$profiles[self::$currentProfileId])) {
			die("Profile data is missing!");
			return false;
		} else {
			return true;
		}

	}
	static function DisplayProfile( $pid = null) {
		global $lang;
		
        echo '<h2>'.$lang['srv_missing_settings'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="missingProfileAction(\'cancle\'); return false;">✕</a></div>';
		
		if ($pid == null ) {
			$pid = self::$currentProfileId;
		}
	
		if ( self::$currentProfileId != SMP_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }
		
		
		echo ' <div id="missing_profile_holder">';
		self :: DisplayProfileOptions($pid);
		echo ' </div>';
		
		
		echo ' <div id="missing_profile_data_holder">';
		self :: DisplayProfileData($pid);		
		echo ' </div>';

		
		// GUMBI
		echo '<div style="margin: 15px 0 0 0; float:right;">';
		# shrani kot seja
		if (self::$profiles[$pid]['system'] == 1 || $pid == -1) {
			$run_lbl = $lang['srv_run_as_session_profile'];
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="missingProfileAction(\'runSession\'); return false;"><span>'.$run_lbl.'</span></a></span></span>';
		} else {
			# shrani - pozeni
			$run_lbl = $lang['srv_run_profile'];
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="missingProfileAction(\'run\'); return false;"><span>'.$run_lbl.'</span></a></span></span>';
		}
		# shrani kot nov profil
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="missingProfileAction(\'newName\'); return false;"><span>'.$lang['srv_create_new_profile'].'</span></a></span></span>';		
		# preklici - zapri
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="missingProfileAction(\'cancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';		
		echo '</div>';
		
		
		// cover Div
        echo '<div id="missingProfileCoverDiv"></div>'."\n";
        
        // div za shranjevanje novega profila
        echo '<div id="newProfile">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="missingProfileAction(\'newSave\'); return false;"><span>'.$lang['srv_save_profile'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="missingProfileAction(\'newCancle\');; return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";

        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . self::$profiles[$pid]['name'] . '" size="45"  />'."\n";
        echo '<input id="renameProfileId" type="hidden" value="' . $pid . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="missingProfileAction(\'rename\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="missingProfileAction(\'renameCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";

        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . self::$profiles[$pid]['name'] . '</b>?'."\n";
        echo '<input id="deleteProfileId" type="hidden" value="' . $pid . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="missingProfileAction(\'deleteCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="missingProfileAction(\'deleteConfirm\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";


	}

	static function DisplayProfileData($pid) {
		global $lang;
		$curentProfileData = self :: getProfileValues($pid); 
		
		$_tpes_array = array(MISSING_TYPE_DESCRIPTOR=>$lang['srv_analiza_arhiviraj_type_'.MISSING_TYPE_DESCRIPTOR],MISSING_TYPE_FREQUENCY=>$lang['srv_analiza_arhiviraj_type_'.MISSING_TYPE_FREQUENCY],MISSING_TYPE_CROSSTAB=>$lang['srv_analiza_arhiviraj_type_'.MISSING_TYPE_CROSSTAB]);
		$_sys_missings = self::$smv->GetMissingValuesForSurvey();
		$_sys_unset = self::$smv->GetUnsetValuesForSurvey();
		echo '<div id="infoMissingProfile">' . $lang['srv_missing_profile_title1'] . '</div >';
		echo '<div id="missingProfileFieldsetHolder1">'.NEW_LINE;
		
		echo '<fieldset id="missingProfileFieldset">'.NEW_LINE;
		echo '<legend>' . $lang['srv_missing_profile_title2'] . '</legend>'.NEW_LINE;
		foreach ($_tpes_array AS $tkey => $tlabel) {
			echo '<div style="float:left; width:100px; text-align:center;">'. $tlabel .'</div>'.NEW_LINE;
		}
		echo '<div class="clr"></div>'.NEW_LINE;
		echo '<form name="" id="" autocomplete="off">'.NEW_LINE;
		# loop sozi sistemske missinge
		foreach ($_sys_unset  as $mkey => $mvalue) {
			# loop skozi tipe
			foreach ($_tpes_array AS $tkey => $tlabel) {
				echo '<div style="float:left; width:100px; text-align:center;">'.NEW_LINE;
				echo '<input name="profile_value[]" type="checkbox" id="mv_'.$tkey.'_'.$mkey . '"' .
				(isset($curentProfileData[$tkey][$mkey]) ? ' checked' : '').
				'/>'.NEW_LINE;
				echo '</div>'.NEW_LINE;
			}
			echo '<div style="float:left; width:150px;">'.NEW_LINE;
			echo '(' . $mkey . ") " . $mvalue;
			echo '</div>'.NEW_LINE;
			echo '<div class="clr"></div>'.NEW_LINE;
		}
		echo '</form>'.NEW_LINE;
		echo '</fieldset>'.NEW_LINE;
		
		echo '<fieldset id="missingProfileFieldset">'.NEW_LINE;
		echo '<legend>' . $lang['srv_missing_profile_title3'] . '</legend>'.NEW_LINE;
		foreach ($_tpes_array AS $tkey => $tlabel) {
			echo '<div style="float:left; width:100px; text-align:center;">'. $tlabel .'</div>'.NEW_LINE;
		}
		echo '<div class="clr"></div>'.NEW_LINE;
		echo '<form name="" id="" autocomplete="off">'.NEW_LINE;
		# loop sozi sistemske missinge
		foreach ($_sys_missings  as $mkey => $mvalue) {
			# loop skozi tipe
			foreach ($_tpes_array AS $tkey => $tlabel) {
				echo '<div style="float:left; width:100px; text-align:center;">'.NEW_LINE;
				echo '<input name="profile_value[]" type="checkbox" id="mv_'.$tkey.'_'.$mkey . '"' .
				(isset($curentProfileData[$tkey][$mkey]) ? ' checked' : '').
				'/>'.NEW_LINE;
				echo '</div>'.NEW_LINE;
			}
			echo '<div style="float:left; width:150px;">'.NEW_LINE;
			echo '(' . $mkey . ") " . $mvalue;
			echo '</div>'.NEW_LINE;
			echo '<div class="clr"></div>'.NEW_LINE;
		}
		echo '</form>'.NEW_LINE;
		echo '</fieldset>'.NEW_LINE;
		echo '</div>'.NEW_LINE;

		echo '<div id="missingProfileFieldsetHolder1">'.NEW_LINE;
		echo '<fieldset id="missingProfileFieldset">';
		echo '<legend>'.$lang['srv_missing_profile_title5'].'</legend>';
		# prikažemo še radio gumbe za način prikaza MV
		
		$radio_selected = self::$profiles[$pid]['display_mv_type'];
		echo '<div style="margin-bottom:8px;">';
		echo '<input name="display_mv_type" id="display_mv_type_0" type="radio" value="0"'.($radio_selected == 0 ? ' checked' : '').'><label for="display_mv_type_0">'.$lang['srv_missing_profile_display_radio0'].'</label>';
		echo '&nbsp;&nbsp;<input name="display_mv_type" id="display_mv_type_1" type="radio" value="1"'.($radio_selected == 1 ? ' checked' : '').'><label for="display_mv_type_1">'.$lang['srv_missing_profile_display_radio1'].'</label>';
		echo '&nbsp;&nbsp;<input name="display_mv_type" id="display_mv_type_2" type="radio" value="2"'.($radio_selected == 2 ? ' checked' : '').'><label for="display_mv_type_2">'.$lang['srv_missing_profile_display_radio2'].'</label>';
		echo '</div>';
		echo '<div style=" width:auto; text-align:left;">'.NEW_LINE;
		echo '<input name="show_zerro" id="show_zerro" type="checkbox" ' .
		 (self::$profiles[$pid]['show_zerro'] == 1 ? ' checked="checked"' : '') . ' autocomplete="off"/>'.NEW_LINE;
		echo $lang['srv_missing_profile_other_show_zerro'];
		echo '</div>'.NEW_LINE;
		echo '<div style=" width:auto; text-align:left;">'.NEW_LINE;
		echo '<input name="merge_missing" id="merge_missing" type="checkbox" ' .
		 (self::$profiles[$pid]['merge_missing'] == 1 ?  ' checked="checked"' : '') . ' autocomplete="off"/>'.NEW_LINE;
		echo $lang['srv_missing_profile_other_merge_missing'];
		echo '</div>';
		echo ' <div class="clr"></div>';
		echo '</fieldset>';
		echo '</div>'.NEW_LINE;
	}
	
	static function DisplayProfileOptions($pid) {
		global $lang;
		
		echo '<div id="missing_profile" class="select">';	
		foreach ( self::$profiles as $key => $profile ) {
			
			echo '<div id="missing_profile_'.$profile['id'].'" class="option' . ($profile['id'] == $pid ? ' active' : '') . '" value="' . $profile['id'] . '">';

			echo $profile['name'];

			if($profile['id'] == $pid){
				if (self::$profiles[$pid]['system'] != 1 ) {
					echo '<a href="#" title="'.$lang['srv_delete_profile'].'" onclick="missingProfileAction(\'deleteAsk\'); return false;"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'."\n";
				}
				if (self::$profiles[$pid]['system'] != 1 && $pid != -1) {		
					echo '<a href="#" title="'.$lang['srv_rename_profile'].'" onclick="missingProfileAction(\'renameAsk\'); return false;"><span class="faicon edit floatRight spaceRight"></span></a>'."\n";
				}
			}
			
			echo '</div>';	
		}
		echo '</div>';

		echo '<script>';
		echo '$(function() {';
		echo 'scrollToProfile("#missing_profile_'.$pid.'");';
		echo '});';
		echo '</script>';

	}
	
	/** klici ajax funkcij
	 * 
	 */
	static function ajax() {
		
		$pid = $_POST['pid'];
		switch ($_GET['a']) {
			case 'change_profile' :
				self :: setDefaultProfileId($pid);
			break;
				
			break;
			case 'show_profile' :
				self :: DisplayProfile($pid);
			break;
			case 'run_profile' :
				self :: SaveProfile($pid);
				self :: setDefaultProfileId($pid);
			break;
			case 'delete_profile':
				self :: DeleteProfile($pid);
				self :: setDefaultProfileId(1);
			break;
			case 'save_profile':
				$new_id = self :: saveNewProfile();
				self :: setDefaultProfileId($new_id);
				echo $new_id;
			break;
			case 'rename_profile':
				$_rename = self :: RenameProfile($pid);
				echo $_rename;
			break;
			
			default:
				echo 'ERROR! Missing function for action: '.$_GET['a'].'!';
			break;
		}
	}

	/** getProfiles
	 * 
	 */
	static function getProfiles() {
		return self::$profiles;	
	}

	static public function getProfile($pid) {
		return self::$profiles[$pid];	
	}
	
	/** setSurveyId
	 * 
	 */
	static function setSId($surveyId) {
		self::$sid = $surveyId;
	}
	
	/** setGlobalUserId
	 * 
	 */
	static function setGlobalUserId($uid = null) {
		if ($uid == null) {
			global $global_user_id;
			self::$uid = $global_user_id;
		} else {
			self::$uid = $uid;
		}
	}
	
	/** getGlobalUserId
	 * 
	 */
	static function getGlobalUserId() {
		return self::$uid;
	}
	static function getCurentProfileId() {
		return self::$currentProfileId;
	}
	/**
	 * 
	 * @param unknown_type $pid
	 */
	static function setCurentProfileId($pid) {
		# ce je seja, preverimo ali obstaja cene nardimo privzetega 1
		if ($pid == -1) {
			if (!(isset($_SESSION['missingProfile']['-1'])))
				$pid = 1;
		} else if (!$pid) {
			$pid = 1;
		}
		
		return self::$currentProfileId = $pid;
	}

	static function setDefaultProfileId($pid) {
		# ce je seja, preverimo ali obstaja cene nardimo privzetega 1
		if ($pid == -1) {
			if (!(isset($_SESSION['missingProfile']))) {
				$pid = 1;
			}
		} else if (!$pid) {
			$pid = 1;
		}

		SurveyUserSetting :: getInstance()->saveSettings('default_missing_profile', $pid);
		self::$currentProfileId = $pid;
		return true; 
	}
	
	static function SaveProfile($pid) {
		global $lang;
		$insert_id = 0;
		$missing_values = $_POST['missing_values'];
		$display_mv_type = isset($_POST['display_mv_type']) ? $_POST['display_mv_type'] : 0;
		
		if (isset($pid) && $pid != null) {
			# ce mamo sistemski profil ga ne shranjujemo
			$checkSelect = "SELECT * as cnt FROM srv_missing_profiles WHERE id = '".$pid."' and `system` = 1"; 
			$checkQry = sisplet_query($checkSelect);
			if (mysqli_num_rows($checkQry) > 0) {
				return $pid;
			}
			
			$show_zerro = (isset($_POST['show_zerro']) && $_POST['show_zerro'] == 'true') ? 1 : 0;
			$merge_missing = (isset($_POST['merge_missing']) && $_POST['merge_missing'] == 'true') ? 1 : 0;
			
			# ce imamo session pozenemo kot sejo
			if ($pid == -1) {
				$missing_values = explode(',',$missing_values);
				$_SESSION['missingProfile'] = array('id'=>'-1','uid'=>0,'name'=>$lang['srv_temp_profile'],'system'=>0, 'display_mv_type'=>$display_mv_type, 'show_zerro'=>$show_zerro, 'merge_missing'=>$merge_missing);
				# pobrišemo predhodne nastavitve
				unset($_SESSION['missingProfile']['values']);
				if (count($missing_values) > 0) {
					foreach ($missing_values as $_missing_value) {
						$_missing_value = substr($_missing_value,3);
						list($type,$key) = explode('_',$_missing_value);
						$_SESSION['missingProfile']['values'][$type][$key] = true;
					}
				}
				return -1;				
			}
			# imamo podatke, updejtamo profil v bazi (profili z id 1,2,3 so sitemski)
			if ($pid > 3) {
				# shranimo morebitno spremembo nastavitve display_mv_type
				$updateString = "UPDATE srv_missing_profiles SET display_mv_type='".$display_mv_type."', show_zerro='".$show_zerro."', merge_missing='".$merge_missing."' WHERE id='".$pid."' AND `system` = 0";
				$updatequery = sisplet_query($updateString);
				
				# najprej pobrišemo stare podatke za ta profil
				$stringDelete = "DELETE FROM srv_missing_profiles_values WHERE missing_pid ='".$pid."'";
				$deleteQuery = sisplet_query($stringDelete);
				
				# shranimo nove vrednosti
				$missing_values = explode(',',$missing_values);
				if (count($missing_values ) > 0) {
					$insertString = "INSERT INTO srv_missing_profiles_values (missing_pid, missing_value, type) VALUES";
					$prefix = '';

					foreach ($missing_values as $_missing_value) {
						$_missing_value = substr($_missing_value,3);
						list($type,$key) = explode('_',$_missing_value);
						$insertString .= $prefix . " (".$pid.", ".$key.", ".$type.")";
						$prefix = ',';
					}
					$queryInsert = sisplet_query($insertString) 
						or die(mysqli_error($GLOBALS['connect_db']));
				}
			}
			return $pid;	
		}
		
	}

	static function saveNewProfile() {
		
		$name = $_POST['name'];
		$missing_values = $_POST['missing_values'];
		$display_mv_type = isset($_POST['display_mv_type']) ? $_POST['display_mv_type'] : 0;
		
		$insert_id = 0;
		if (isset($name) && $name != null ) {
			# imamo podatke, vstavimo nov profil v bazo
			$show_zerro = (isset($_POST['show_zerro']) && $_POST['show_zerro'] == 'true') ? 1 : 0;
			$merge_missing = (isset($_POST['merge_missing']) && $_POST['merge_missing'] == 'true') ? 1 : 0;
			
			$insertString = "INSERT INTO srv_missing_profiles (uid,name,system,display_mv_type,show_zerro,merge_missing) VALUES ('".self::getGlobalUserId()."', '".$name."', 0, '".$display_mv_type."', '".$show_zerro."', '".$merge_missing."')";
			$queryInsert = sisplet_query($insertString);
			$insert_id = mysqli_insert_id($GLOBALS['connect_db']);
			if ($insert_id > 0) {
				# če je insert id <= 3 ga popravimo da je večji od 3.
				if ($insert_id <= 3) {
					$selectId = "SELECT max(id) FROM srv_missing_profiles";
					list($maxInseredId) = mysqli_fetch_row(sisplet_query($selectId));
					$newInsert_id = max(4,$maxInseredId);
					$updateString = "UPDATE srv_missing_profiles SET id='".$newInsert_id."' WHERE id='".$insert_id."'";
					$updatequery = sisplet_query($updateString);
					$insert_id = $newInsert_id;
				}
				
				self::$profiles[$insert_id] = array('id'=>$insert_id,'uid'=>self::getGlobalUserId(),'name'=>$name,'system'=>0);
				
				# shranimo nove vrednosti
				$missing_values = explode(',',$missing_values);
				if (count($missing_values ) > 0) {
					$insertString = "INSERT INTO srv_missing_profiles_values (missing_pid, missing_value, type) VALUES";
					$prefix = '';

					foreach ($missing_values as $_missing_value) {
						$_missing_value = substr($_missing_value,3);
						list($type,$key) = explode('_',$_missing_value);
						$insertString .= $prefix . " (".$insert_id.", ".$key.", ".$type.")";
						$prefix = ',';
					}
					$queryInsert = sisplet_query($insertString);
				}
				
			} else {
				
			}
		}
		return $insert_id;
	}
	
	static function RenameProfile($pid) {
		$name = $_POST['name'];
		if (isset($pid) && $pid != null && isset($name) && $name != null ) {
			$updateString = "UPDATE srv_missing_profiles SET name = '".$name."' WHERE id = ".$pid;
			$updated = sisplet_query($updateString);
			if ($updated) {
				return 0;
			} else {
				return mysqli_error($GLOBALS['connect_db']);
			}
		} else {
			if (!isset($pid) || $pid == null || trim($pid) == '') {
				return 'Invalid profile Id';
			} else  if (!isset($name) || $name == null || trim($name) == '') {
				return 'Invalid profile name';
			} else {
				return 'Error!';
			}
		}

	}
	
	static function DeleteProfile($pid) {
		if ($pid == -1) {
			unset($_SESSION['missingProfile']);
		}
		# izbrišemo lahko samo nesistemske profile
		if (self::$profiles[$pid]['system'] != 1) {
			# zaradi ključev se avtomatsko pobriše tudi: srv_missing_profiles_values
			$sqlDelete = sisplet_query("DELETE FROM srv_missing_profiles WHERE id = '$pid' AND `system` != '1'");		
		} 
	}
		
	static function DisplayLink($hideAdvanced = true) {
		global $lang;
		// profili missingov
        $missingProfiles = self :: getProfiles();
        $izbranMissingProfile = self :: getCurentProfileId();
        
        $css = ($izbranMissingProfile == SMP_DEFAULT_PROFILE ? ' gray' : '');
        if ($hideAdvanced == false || $izbranMissingProfile != SMP_DEFAULT_PROFILE ) {
        	echo '<li class="space">&nbsp;</li>';
        	echo '<li>';
        	echo '<span class="as_link'.$css.'" id="link_missing_profile" title="' . $lang['srv_analiza_setup_profile'] . '" onClick="show_missing_profiles();">' . $lang['srv_analiza_setup_profile'] . '</span>'."\n";
        	echo '</li>';
        	
        }
	}

	/** Ustvarimo tri sistemske profile ustvarimo jih navidezno
	 * 
	 * Brez manjkajočih vrednosti - ne prikazuje manjkajočih vrednosti (display_mv_type = 0)
	 * Skupaj MV - prikaže samo skupaj manjkajoče vrednosti (display_mv_type = 1)
	 * Podrobno MV - prikaže razširjeno manjkajoče vrednosti (display_mv_type = 2)
	 */
	static function addSystemProfiles() {
		global $lang;
		
		# skreiramo 3 sistemske manjkajoče profile
		for ( $i = 1; $i <= 3; $i++ ) {
			self::$profiles[$i] = array('id'=>$i,'uid'=>self::getGlobalUserId(),'name'=>$lang['srv_missing_profiles_profile'.$i.'_lbl'],'system'=>1, 'display_mv_type'=>(int)$i-1);
		}
	}
	
	/** Vrenmo podatke o izbranem profilu v obliki arraya
	 * 
	 * @param unknown_type $pid
	 */
	static function getProfileValues($pid) {
		$result = array();

		if ($pid == -1) {
			# beremo iz seje
			if (isset($_SESSION['missingProfile'])) {
				$result = $_SESSION['missingProfile']['values'];
				return $result;
			} else {
				$pid = 1;
			}
		}
		# če je sistemski profil preberemo iz avtomatsko skreiranih
		if ( ($pid < 3 && $pid >= 0) || (isset(self::$profiles[$pid]) && self::$profiles[$pid]['system'] == 1)) {
			# imamo sistemski profil, preberemo podatke ki so bili skreirani "on the fly"
			#najprej poiščemo sistemske missinge
			$_sys_missings = self::$smv->GetMissingValuesForSurvey();
			$_sys_unset = self::$smv->GetUnsetValuesForSurvey(array(2,3));
			
			# sistemske missinge (-1,-2,-3,-4) dodamo k vsem profilom
			if (count($_sys_missings) > 0) {
				foreach($_sys_missings AS $key => $value) {
					$result[MISSING_TYPE_DESCRIPTOR][$key] = true;
					$result[MISSING_TYPE_FREQUENCY][$key] = true;
					$result[MISSING_TYPE_CROSSTAB][$key] = true;
				}
				 
			}
			# neopredeljene vrednosti  (-99,-98,-97) dodamo samo k opisnim in krostabulacijam
			if (count($_sys_unset) > 0) {
				foreach($_sys_unset AS $key => $value) {
					$result[MISSING_TYPE_DESCRIPTOR][$key] = true;
					$result[MISSING_TYPE_CROSSTAB][$key] = true;
				}
				 
			}
		} else {
			$selectString = "SELECT missing_value, type FROM srv_missing_profiles_values WHERE missing_pid = '".$pid."'";
			$querySelect = sisplet_query($selectString);
			if (mysqli_num_rows($querySelect)) {
				while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
					$result[$rowSelect['type']][$rowSelect['missing_value']] = true;
				}
			}
		} 

		return $result;
	}
	
	/*
	 * type = 1 => opisne
	 * type = 2 => frekvence
	 */
	static function GetMissingValuesForAnalysis($type,$pid=null) {
		$result = array();
	    
		#poiščemo sistemske missinge, z labele
		
		$_survey_missings = self::$smv->GetMissingValuesForSurvey(array(1,2,3));
		
		if ($pid == null)
		{
			$pid = self::$currentProfileId;
		}
		$curentProfileData = self :: getProfileValues($pid);
		if (count($curentProfileData[$type]) > 0) {
		    foreach ($curentProfileData[$type] AS $key => $is_set) {
		    	$result[$key] = isset($_survey_missings[$key]) ? $_survey_missings[$key] : $key;  
		    }
		}
		return $result;
		
	}
}

?>
