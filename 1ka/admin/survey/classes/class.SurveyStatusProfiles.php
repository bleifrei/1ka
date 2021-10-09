<?php

/** @author: Gorazd Veselič
 * 
 * 	@Desc: za upravljanje z profili statusov za podatke in izvoze
 * 
 */

session_start();
DEFINE (STR_DLMT, "|");

class SurveyStatusProfiles
{
	static private $sid = null;					# id ankete
	static private $uid = null;					# id userja
	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika
																		
	// lurker je mal poseben, ker je neodvisen od ostalih (user je npr. 6 in lurker)
	# lurker = 0 - obveezno ni lurker, lurker = 1 - obvezno je lurker, lurker = 2 - je ali ni lurker
	static private $allStatus = array('null',0,1,2,3,4,5,6,'lurker'); 			// Statusi anket katere štejemo kot ustrezne
	static private $appropriateStatus = array(6,5); 			// Statusi anket katere štejemo kot ustrezne
	static private $unAppropriateStatus = array(4,3,2,1,0); 	// Statusi anket katere štejemo kot neustrezne
	static private $unKnownStatus = array('null'); 					// Statusi anket katere štejemo kot neustrezne
	# za awk komando 
	static private $_AWK_FILTER_TEXT = array(6=>'6',5=>'5',4=>'4',3=>'3',2=>'2',1=>'^1',0=>'0','null'=>'-1'); # texti za statuse. 6=>'6',5=>'5',4=>'4',3=>'3',2=>'2',1=>'^1',0=>'0',null=>'-1' Pri 1 je ^pomemben, če ne lahko vzame tudi -1
	
	static private $allUserCount = 0;	#Koliko je vseh userjev
	static private $allValidCount = 0;	#Koliko je veljavnih userjev
	
	static private $survayDefaultUstrezni = 2;	#privzet ustrezni profil: 2-ustrezni (5,6) 3-končani(6)
	
	static function Init($sid, $uid = null) {
		# nastavimo surveyId
		self::setSId($sid);
		SurveyInfo :: getInstance()->SurveyInit(self::$sid);
		# nastavimo userja
		self::setGlobalUserId($uid);
		
		if ((int)SurveyInfo :: getInstance()->getSurveyColumn('defValidProfile') == 3) {
			self::$survayDefaultUstrezni = 3;
		}
		
		# preštejemo userje
		$str_all = "SELECT count(*) FROm srv_user WHERE ank_id='".self::$sid."' AND deleted='0' AND preview = '0'";
		if (self::$survayDefaultUstrezni == 2) {
			$str_valid = "SELECT count(*) FROM srv_user WHERE ank_id='".self::$sid."' AND last_status IN (5,6) AND lurker = 0 AND deleted='0' AND preview = '0' AND testdata='0'";
		} else {
			$str_valid = "SELECT count(*) FROM srv_user WHERE ank_id='".self::$sid."' AND last_status = '6' AND lurker = 0 AND deleted='0' AND preview = '0' AND testdata='0'";
		}
		$query_all = sisplet_query($str_all);
		$query_valid = sisplet_query($str_valid);
		list($all) = mysqli_fetch_row($query_all);
		list($valid) = mysqli_fetch_row($query_valid);
		self::$allUserCount = $all; 
		self::$allValidCount = $valid; 
		SurveyUserSetting :: getInstance()->Init(self::$sid, self::getGlobalUserId());
		self :: RefreshData();
	}

	static function getAllValidCount() {
		return self::$allValidCount;
	}
	static function getAllUserCount() {
		return self::$allUserCount;
	}
	
	
	static function RefreshData() {
		global $lang;
		
		$lang_admin = SurveyInfo :: getInstance()->getSurveyColumn('lang_admin');

		self::$profiles = array();
		# dodamo sejo če obstaja
		if (isset($_SESSION['statusProfile'])) {
			if ( $lang_admin != 1 ) {
				$_SESSION['statusProfile']['name'] = $lang['srv_temp_profile'];
			}
			self::$profiles[$_SESSION['statusProfile']['id']] = $_SESSION['statusProfile'];
		}
		# preberemo podatke vseh porfilov ki so na voljo in jih dodamo v array
		$stringSelect = "SELECT * FROM srv_status_profile WHERE uid='".self::getGlobalUserId()."' OR ank_id = '".self::$sid."' OR (uid = '0' AND `system`=1) ORDER BY id";
		$querySelect = sisplet_query($stringSelect);

		while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
			if ( $lang_admin != 1 && $rowSelect['system'] == 1 ) {
				# imamo sistemski profil v tujem jeziku popravimo tekste
				$rowSelect['name'] = $lang['srv_status_profile_system_'.$rowSelect['id']];					
			} 
			self::$profiles[$rowSelect['id']] = $rowSelect;
		}
		
		# ker si Vasja skoz nekaj zmišljuje glede imen sistemskih profilov (ki so zaenkrat v bazi) jih ročno prepišemo
		# pa še jezikovno tabelo lahko uporabimo, tak da je to boljše
		self::$profiles[1]['name'] = $lang['srv_status_profile_system_1'];
		self::$profiles[2]['name'] = $lang['srv_status_profile_system_2'];
		self::$profiles[3]['name'] = $lang['srv_status_profile_system_3'];
		# vsi statusi ni pomembno ali je lurker ali ne
		# pri ustreznih ne sme biti lurker in ne testni
		self::$profiles[1]['statuslurker'] = 2;
		self::$profiles[2]['statuslurker'] = 0;
		self::$profiles[3]['statuslurker'] = 0;
		
		self::$profiles[1]['statustestni'] = 2;
		self::$profiles[2]['statustestni'] = 0;
		self::$profiles[3]['statustestni'] = 0;
		
		// Uporabnost - vedno so vsi vklopljeni po defaultu
		self::$profiles[1]['statusnonusable'] = 1;
		self::$profiles[2]['statuspartusable'] = 1;
		self::$profiles[3]['statususable'] = 1;		
		
		# poiscemo privzet profil
		#določimo podstran
		if (isset($_POST['meta_akcija']) && $_POST['meta_akcija'] != '') {
			$_podstran = $_POST['meta_akcija'];
		} else if (isset($_POST['podstran']) && $_POST['podstran'] != '') {
			$_podstran = $_POST['podstran'];
		} else if (isset($_GET['a']) && $_GET['a'] != '') {
			$_podstran = $_GET['a'];
		} else {
			$_podstran = A_COLLECT_DATA;
		}
		
		if ( (($_podstran !== A_COLLECT_DATA && $_podstran !== A_COLLECT_DATA_EXPORT && $_podstran !== 'para_graph') || (isset($_GET['b']) && $_GET['b'] == 'export')) && $_podstran !== 'usable_resp' && $_podstran !== 'reminder_tracking') {
			$_podstran = A_ANALYSIS;
		}
		
		// Pri izvozu podatkov uporabimo isti profil kot pri ostalih podatkih
		if ($_podstran == A_COLLECT_DATA_EXPORT) {
			$_podstran = A_COLLECT_DATA;
		}
		
		# če smo v vpogledu pohandlammo posebej
		if (($_podstran == A_COLLECT_DATA && $_GET['m'] == 'quick_edit') || $_POST['podstran'] == 'quick_edit') {
			$_podstran = 'vpogled';
		}
		self::$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_status_profile_'.$_podstran);
		
		if (!self::$currentProfileId) {
			
			#self::$currentProfileId = 2;
			self::$currentProfileId = self::$survayDefaultUstrezni; # je lahko 2 ali 3
		}
		# ustrezni je lahko samo če so kakšni ustrezni zapisi v bazi ob pogoju da pa neustrezni obstajajao
		if (self::$allValidCount == 0 && self::$allUserCount > 0) {
			if (self::$currentProfileId == 2 || self::$currentProfileId == 3 ) {
				# privzeto nastavimo na vsi
				self::$currentProfileId = 1;
			}
		}	
		
		# ce imamo nastavljen curent pid in profil z tem pid ne obstaja nastavomo na privzet profil 
		if (self::$currentProfileId != 1) {
			if (!isset(self::$profiles[self::$currentProfileId])) {
				self::$currentProfileId = 1;
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
	
	public static function getSystemDefaultProfile() {
		$sysDefProf = self::$survayDefaultUstrezni; # je lahko 2 ali 3

		# ustrezni je lahko samo če so kakšni ustrezni zapisi v bazi ob pogoju da pa neustrezni obstajajao
		if (self::$allValidCount == 0 && self::$allUserCount > 0) {
			if ($sysDefProf == 2 || $sysDefProf == 3 ) {
			# privzeto nastavimo na vsi
				$sysDefProf = 1;
			}
		}
		return (int)$sysDefProf;
	}
	
	public static function getDefaultProfile() {
		return self::$currentProfileId;
	}
	
	static function DisplayProfile( $pid = null) {
		global $lang;
		
		if ($pid == null ) {
			$pid = self::$currentProfileId;
		}
		
		$popUp = new PopUp();
		$popUp->setId('divStatusProfile');
        $popUp->setHeaderText($lang['srv_status_settings']);
        		
		#vsebino shranimo v buffer
		ob_start();
		if ( self::$currentProfileId != SSP_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }

        echo '<div class="popup_close"><a href="#" onClick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\'); return false;">✕</a></div>';

		echo ' <div id="status_profile_holder">';
		self :: DisplayProfileOptions($pid);
		echo ' </div>';

		echo ' <div id="status_profile_data_holder">';
		self :: DisplayProfileData($pid);
		echo ' <br class="clr" />';
		echo ' </div>';
                
		// cover Div
        echo '<div id="statusProfileCoverDiv"></div>';
        
        // div za shranjevanje novega profila
        echo '<div id="newProfile">'.$lang['srv_missing_profile_name'].': ';
        echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />';
        $button = new PopUpButton($lang['srv_save_profile']);
        echo $button -> setFloat('right')
        		->setButtonColor('orange')
        		-> addAction('onClick','statusProfileAction(\'newSave\'); return false;');
        $button = new PopUpButton($lang['srv_cancel']);
        echo $button -> setFloat('right')
        		-> addAction('onClick','statusProfileAction(\'newCancel\'); return false;');
        echo '</div>';

        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': ';
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . self::$profiles[$pid]['name'] . '" size="45"  />';
        echo '<input id="renameProfileId" type="hidden" value="' . $pid . '"  />';
        $button = new PopUpButton($lang['srv_rename_profile_yes']);
        echo $button -> setFloat('right')
        		->setButtonColor('orange')
        		-> addAction('onClick','statusProfileAction(\'renameProfile\'); return false;');
        
        $button = new PopUpButton($lang['srv_cancel']);
        echo $button -> setFloat('right')
        		-> addAction('onClick','statusProfileAction(\'renameCancel\'); return false;');
        echo '</div>';

        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . self::$profiles[$pid]['name'] . '</b>?';
        echo '<input id="deleteProfileId" type="hidden" value="' . $pid . '"  />';

        $button = new PopUpButton($lang['srv_delete_profile_yes']);
        echo $button -> setFloat('right')
        		->setButtonColor('orange')
        		-> addAction('onClick','statusProfileAction(\'deleteConfirm\'); return false;');
        
        $button = new PopUpButton($lang['srv_cancel']);
        echo $button -> setFloat('right')
        		-> addAction('onClick','statusProfileAction(\'deleteCancel\'); return false;');
        
        echo '</div>';
        
        $content = ob_get_clean();
        
        #dodamo vsebino
        $popUp->setContent($content);

       /* 
        if ($pid < 0) { #Imamo sejo, lahko poženemo samo kot sejo
        	$run_lbl = $lang['srv_run_as_session_profile'];
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="statusProfileAction(\'runSession\'); return false;"><span>'.$run_lbl.'</span></a></span></span>';
        } else {
        	# shrani - pozeni
        	$run_lbl = $lang['srv_run_profile'];
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick=""><span>'.$run_lbl.'</span></a></span></span>';
        	$run_lbl = $lang['srv_run_as_session_profile'];
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="statusProfileAction(\'runSession\'); return false;"><span>'.$run_lbl.'</span></a></span></span>';
        
        }
        */
        $button = new PopUpButton($lang['srv_choose_profile']);
        $button -> setFloat('right')
        	->setButtonColor('orange')
        	-> addAction('onClick','statusProfileAction(\'choose\'); return false;');
        $popUp->addButton($button);
        
        #dodamo gumb shrani
        if (self::$profiles[$pid]['system'] != 1 && $pid != -1) 
        {
	        $button = new PopUpButton($lang['srv_save_profile']);
	        $button -> setFloat('right')
	        	-> addAction('onClick','statusProfileAction(\'save\'); return false;');
	        $popUp->addButton($button);
        }
        
        $button = new PopUpButton($lang['srv_new_profile_name']);
        $button -> setFloat('right')
        -> addAction('onClick','statusProfileAction(\'newName\'); return false;');
        $popUp->addButton($button);
        
		# dodamo gumb Prekliči
		$button = new PopUpCancelButton();
		$button -> setFloat('right');
        $popUp->addButton($button);
		
        echo $popUp;
	}

	static function DisplayProfileData($pid) {
		global $lang;
		$curentProfileData = self :: $profiles[$pid]; 
//		echo '<div id="status_profile_notes" >help?'.'</div>';
		
		echo '<div id="missingProfileFieldsetHolder" >';
		echo '<form name="" id="" autocomplete="off">';
		echo '<fieldset class="statusProfileFieldset">';
		echo '<legend>' . $lang['srv_missing_profile_title4'] . '</legend>';

		$cnt = 1;
		
		$disabled = $curentProfileData['system'] == 1 ? true : false; 

		# preverimo kaere statuse disejblamo na podlagi izbire načina kreiranja datoteke (samo 5,6 / vsi satusi)
		list($collect_all_status) = mysqli_fetch_row(sisplet_query("SELECT collect_all_status FROM srv_data_files WHERE sid = '".self::$sid."'"));

		echo '<table><tr>';
		// dodamo veljavne
		foreach (self::$appropriateStatus as $index) {
			if ($cnt&1) { 
				echo '</tr><tr>';
			}
			echo '<td style="width:50%">';
			echo '<label><input name="srv_userstatus[]" type="checkbox" id="' . $index . '"' .
			 ($curentProfileData['status'.$index] == 1 ? ' checked="checked"' : '') .
			 ($disabled || ( (int)$collect_all_status == 0 && $index < 5  )  ? ' disabled="true"' : '' ). '/>';
			echo '<span'.($disabled || ( (int)$collect_all_status == 0 && $index < 5  )  ? ' class="gray"' : '' ).'>'.$lang['srv_userstatus_' . $index]. ' ('.$index.')</span>';
			echo '</label></td>';
			$cnt++;
		}
		// dodamo neveljavne
		foreach (self::$unAppropriateStatus as $index) {
			if ($cnt&1) 
				echo '</tr><tr>';
			echo '<td style="width:50%">';
			echo '<label><input name="srv_userstatus[]" type="checkbox" id="' . $index . '"' .
			 ($curentProfileData['status'.$index] == 1 ? ' checked="checked"' : '') . 
			 ($disabled  || ( (int)$collect_all_status == 0 && $index < 5  ) ? ' disabled="true"' : '' ) . '/>';
			//echo $lang['srv_userstatus_' . $index]. " (".$index.")";
			echo '<span'.($disabled || ( (int)$collect_all_status == 0 && $index < 5  )  ? ' class="gray"' : '' ).'>'.$lang['srv_userstatus_' . $index]. ' ('.$index.')</span>';
			echo '</label></td>';
			$cnt++;
		}
		// dodamo null
		foreach (self::$unKnownStatus as $index) {

			if ($cnt&1) 
				echo '</tr><tr>';
			echo '<td style="width:50%">';
			echo '<label><input name="srv_userstatus[]" type="checkbox" id="' . $index . '"' .
			 ($curentProfileData['status'.$index] == 1 ? ' checked="checked"' : '') .
			 ($disabled  || ( (int)$collect_all_status == 0 && $index < 5  ) ? ' disabled="true"' : '' ) . '/>';
			//echo $lang['srv_userstatus_' . $index]. " (".$index.")";
			echo '<span'.($disabled || ( (int)$collect_all_status == 0 && $index < 5  )  ? ' class="gray"' : '' ).'>'.$lang['srv_userstatus_' . $index]. ' ('.$index.')</span>';
			echo '</label></td>';
			$cnt++;
		}
		
		echo '</tr></table>';
		
		echo '</fieldset>';
		
		
		if ($disabled ) {
			$html_disabled = ' disabled="true"';
			$css_disabled = ' class="gray"';
		}
		
		
		echo '<fieldset class="statusProfileFieldset">';
		echo '<legend>' . $lang['srv_invalid_units'] . '</legend>';
		
		// lurkerji
		# lurker = 0 - obveezno ni lurker, lurker = 1 - obvezno je lurker, lurker = 2 - je ali ni lurker
		#echo '<hr><label><input type="checkbox" name="srv_userstatus[]" id="lurker" '.($curentProfileData['statuslurker'] == 1 ? ' checked="checked"' : '') . ($disabled ? ' disabled="true"' : '' ) . '> '.$lang['srv_lurkers'].'</label>';
		echo '<label'.$css_disabled.'>'.$lang['srv_lurkers'].'</label>';
		echo '<label'.$css_disabled.'><input type="radio" name="srv_us_lurker" value="0" '.((int)$curentProfileData['statuslurker'] == 0 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['no'].'</label>';
		echo '<label'.$css_disabled.'><input type="radio" name="srv_us_lurker" value="1" '.((int)$curentProfileData['statuslurker'] == 1 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['srv_only_empty'].'</label>';
		echo '<label'.$css_disabled.'><input type="radio" name="srv_us_lurker" value="2" '.((int)$curentProfileData['statuslurker'] == 2 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['srv_also'].'</label>';
		echo '<br/><label class="small">'.$lang['srv_lurkers_subnote'].'</label>';
		
		// testni vnosi
		# testni = 0 - obveezno ni testni, testni = 1 - obvezno je tesni, testni = 2 - je ali ni testni
		echo '<br/><br/><label'.$css_disabled.'>'.$lang['srv_testni_vnos'].'</label>';
		echo '<label'.$css_disabled.'><input type="radio" name="srv_us_testni" value="0" '.((int)$curentProfileData['statustestni'] == 0 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['no'].'</label>';
		echo '<label'.$css_disabled.'><input type="radio" name="srv_us_testni" value="1" '.((int)$curentProfileData['statustestni'] == 1 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['srv_only_test'].'</label>';
		echo '<label'.$css_disabled.'><input type="radio" name="srv_us_testni" value="2" '.((int)$curentProfileData['statustestni'] == 2 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['srv_also'].'</label>';

		echo '</fieldset>';
		
		
		// Filter na uporabnost
		echo '<fieldset class="statusProfileFieldset">';
		echo '<legend>' . $lang['srv_usableResp_usable_unit'] . '</legend>';
		
		// 2->uporabni, 1->delno uporabni, 0->neuporabni
		echo '<label'.$css_disabled.'><input type="checkbox" name="srv_us_nonusable" value="1" '.((int)$curentProfileData['statusnonusable'] == 1 ? ' checked="checked"' : '') .$html_disabled. '> '.$lang['srv_usableResp_unusable'].'</label>';
		echo '<span class="spaceLeft"></span><label'.$css_disabled.'><input type="checkbox" name="srv_us_partusable" value="1" '.((int)$curentProfileData['statuspartusable'] == 1 ? ' checked="checked"' : '') .$html_disabled. '>'.$lang['srv_usableResp_partusable'].'</label>';
		echo '<span class="spaceLeft"></span><label'.$css_disabled.'><input type="checkbox" name="srv_us_usable" value="1" '.((int)$curentProfileData['statususable'] == 1 ? ' checked="checked"' : '') .$html_disabled. '>'.$lang['srv_usableResp_usable'].'</label>';
		
		
		echo '</fieldset>';
		echo '</form>';
		echo '</div>';	
	}
	
	static function DisplayProfileOptions($pid) {
		global $lang;
		
		$_sql_string = "SELECT collect_all_status FROM srv_data_files WHERE sid = '".self::$sid."'";
		$_sql_qry = sisplet_query($_sql_string);
		$_sql_row = mysqli_fetch_assoc($_sql_qry);
		
		echo '<div id="status_profile" class="select">';
		foreach ( self::$profiles as $key => $profile ) {
			if ($key != 1 || ($key == 1 && (int)$_sql_row['collect_all_status'] > 0 )) {
				
				echo '<div id="status_profile_'.$profile['id'].'" class="option' . ($profile['id'] == $pid ? ' active' : '') . '" value="'.$profile['id'].'" '.($profile['id'] == $pid ? '' : '  onclick="show_status_profile_data(' . $profile['id'] . '); return false;"').'>';

				echo $profile['name'];

				if($profile['id'] == $pid){
					#dodamo gumb izbriši
					if (self::$profiles[$pid]['system'] != 1 ){
						echo ' <a href="#" onclick="statusProfileAction(\'deleteAsk\'); return false;" value="'.$lang['srv_delete_profile'].'"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'."\n";
					}
					#dodamo gumb preimenuj
					if (self::$profiles[$pid]['system'] != 1 && $pid != -1){
						echo ' <a href="#" onclick="statusProfileAction(\'renameAsk\'); return false;" value="'.$lang['srv_rename_profile'].'"><span class="faicon edit floatRight spaceRight"></span></a>'."\n";
					}
				}
				
				echo '</div>';
			}       
		}
		echo '</div>';
		
		echo '<script>';
		echo '$(function() {';
		echo 'scrollToProfile("#status_profile_'.$pid.'");';
		echo '});';
		echo '</script>';
	}
	
	/** klici ajax funkcij
	 * 
	 */
	static function ajax() {
		$pid = $_POST['pid'];
		switch ($_GET['a']) {
			case 'displayProfile' :
				if (isset($pid) && $pid != null) 
				{
					self :: setCurentProfileId($pid);
				}
				self :: DisplayProfile($pid);
			break;
			case 'chooseProfile' :
				self :: setDefaultProfileId($pid);
			break;
			case 'saveProfile':
				self :: saveProfile($pid);
			break;
			case 'run_status_profile':
				self :: saveProfile($pid);
				self :: setDefaultProfileId($pid);
			break;
			case 'save_status_profile':
				$new_id = self :: saveNewProfile($pid);
				self :: setDefaultProfileId($new_id);
				echo $new_id;
			break;
			case 'deleteProfile':
				self :: DeleteProfile($pid);
			break;
			case 'renameProfile':
				self :: renameProfile($pid);
				
			break;
			case 'showColectDataSetting':
				self :: showColectDataSetting();
			break;
			case 'saveCollectDataSetting':
				self :: saveCollectDataSetting();
			break;
			case 'changeOnlyValidRadio':
				self :: changeOnlyValidRadio();
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
		return (int)self::$currentProfileId;
	}
	
	public function getProfileName($pid) {
		return self::$profiles[$pid]['name'];
	}
	
	/* Vrne ID in ime trenutno izbranega profila
	 * 
	 */
	function getCurentProfile() {
		return array('id'=>self::$currentProfileId,'name'=>self::$profiles[self::$currentProfileId]['name']);
	}
	/**
	 * 
	 * @param unknown_type $pid
	 */
	static function setCurentProfileId($pid) {
		# preverimo ali profil obstaja
		if (!isset(self::$profiles[$pid]))
		{	# če ne obstaja damo privzeto ustrezne
			$pid = 2;
		}
		# ce je seja, preverimo ali obstaja cene nardimo privzetega 1
		if ($pid == -1) {
			if (!(isset($_SESSION['statusProfile']['-1'])))
				$pid = 1;
		} else if (!$pid) {
			$pid = 1;
		}
		
		return self::$currentProfileId = $pid;
	}

	static function setDefaultProfileId($pid=null, $podstran = null) {
		# ce je seja, preverimo ali obstaja cene nardimo privzetega 1
		if ($pid == -1) {
			if (!(isset($_SESSION['statusProfile']))) {
				$pid = null;
			}
		} 
		if ($podstran == null ) {
			if ( isset($_POST['meta_akcija']) ) {
				$_action = $_POST['meta_akcija'];
			} else if (isset($_POST['podstran']) ) {
				$_action = $_POST['podstran'];
			} else if (isset($_POST['a']) ) {
				$_action = $_POST['a'];
			} else if (isset($_GET['a']) ) {
				$_action = $_GET['a'];
			} else {
				$_action = 'data';
			}
		} else {
			$_action = $podstran;
		}		
		
		// Pri izvozu podatkov uporabimo isti profil kot pri ostalih podatkih
		if ($_action == A_COLLECT_DATA_EXPORT) {
			$_action = A_COLLECT_DATA;
		}
		
		# če smo v vpogledu pohandlammo posebej
		if (($_action == A_COLLECT_DATA && $_GET['m'] == 'quick_edit') || $_POST['podstran'] == 'quick_edit') {
			$_action = 'vpogled';
		}
		
		# če je $pid == null odvisno od akcije nastavimo privzet profil
		# v podatkih = 1 v analizah = 2
		if ($pid == null || (int)$pid==0) {
			/* po novem je privzeto vedno le ustrezni statusi
			if ($_action == 'data') {
				$pid = 1;
			} else {
				$pid = 2;
			}
			*/
			$pid = 2;
		}
		
		# če lovimo samo ustrezne, potem ne morem o izbratz vsi statusi				
		$_sql_string = "SELECT collect_all_status FROM srv_data_files WHERE sid = '".self::$sid."'";
		$_sql_qry = sisplet_query($_sql_string);
		list($collect_all_status) = mysqli_fetch_row($_sql_qry);
		if ($pid == 1 && (int)$collect_all_status == 0) {
			$pid = 2;
		}
		
		# ustrezni je lahko samo če so kakšni ustrezni zapisi v bazi ob pogoju da pa neustrezni obstajajao
		if ($pid == 2 && self::$allValidCount == 0 && self::$allUserCount > 0) {
			$pid = 1;
		}
		
		# če smo izbrali drug profil resetiramo še profil profilov na trenutne nastavitve
		SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', '0');
		
		SurveyUserSetting :: getInstance()->saveSettings('default_status_profile_'.$_action, $pid);
		
		
		self::$currentProfileId = $pid;
		return true; 
	}
	
	static function getStatusAsArrayString() {

		$mpds =  self::getStatusArray(self::$currentProfileId);

		$result = array();
		
		if ($mpds) {
			foreach ( self::$appropriateStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1)
		       		$result[$index] = $index;
			}
			foreach ( self::$unAppropriateStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1)
		       		$result[$index] = $index;
			}
			foreach ( self::$unKnownStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1)
		       		$result[$index] = $index;
			}
			# lurker = 0 - obveezno ni lurker, lurker = 1 - obvezno je lurker, lurker = 2 - je ali ni lurker
			if (isset($mpds['statuslurker']) && ((int)$mpds['statuslurker'] == 0 || (int)$mpds['statuslurker'] == 1)) {
				$result['lurker'] = 'lurker';
			}
			# testni = 0 - obveezno ni testni, testni = 1 - obvezno je testni, testni = 2 - je ali ni testni
			if (isset($mpds['statustestni']) && ((int)$mpds['statustestni'] == 0 || (int)$mpds['statustestni'] == 1)) {
				$result['testni'] = 'testni';
			}
			# Uporabnost
			if (isset($mpds['statusnonusable']) && $mpds['statusnonusable'] == 1) {
				$result['nonusable'] = 1;
			}
			if (isset($mpds['statuspartusable']) && $mpds['statuspartusable'] == 1) {
				$result['partusable'] = 1;
			}
			if (isset($mpds['statususable']) && $mpds['statususable'] == 1) {
				$result['usable'] = 1;
			}
		}

		return $result;
	}
	static public function getStatusAsQueryString($pid = null) {
		if ($pid == null || (int)$pid <= 0 ) {
			$mpds =  self::getStatusArray(self::$currentProfileId);
		} else {
			$mpds =  self::getStatusArray((int)$pid);
		}
		$result = '';
		
		if ($mpds) {
			$result .= ' AND last_status IN (';
			foreach ( self::$appropriateStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1) {
					$result .= $prefix.$index;
					$prefix = ', ';
				}
			}
			foreach ( self::$unAppropriateStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1) {
					$result .= $prefix.$index;
					$prefix = ', ';
				}
			}
			foreach ( self::$unKnownStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1) {
					$result .= $prefix.$index;
					$prefix = ', ';
						
				}
			}
			$result .= ')';
			# lurker = 0 - obveezno ni lurker, lurker = 1 - obvezno je lurker, lurker = 2 - je ali ni lurker
			if (isset($mpds['statuslurker']) && (int)$mpds['statuslurker'] == 1) {
				$result .= ' AND lurker = 1';
			} else if (isset($mpds['statuslurker']) && (int)$mpds['statuslurker'] == 0) {
				$result .= ' AND lurker = 0';
			} else {
				# lurker ni pogoj
			}
			# testni = 0 - obveezno ni testni, testni = 1 - obvezno je testni, testni = 2 - je ali ni testni
			if (isset($mpds['statustestni']) && (int)$mpds['statustestni'] == 1) {
				$result .= ' AND (testdata = 1 OR testdata = 2)';
			} else if (isset($mpds['statustestni']) && (int)$mpds['statustestni'] == 0) {
				$result .= ' AND testdata = 0';
			} else {
				# testdata ni pogoj
			}
		}

		return $result;
	}
	static function getStatusArray($pid=null) {
		if ($pid == null) {
			$pid = self::$currentProfileId;
		}
		$mpd =  self::$profiles[$pid];
		return $mpd;
	}

	static function getStatusAsAWKString($pid=null) {
		if ($pid==null){
			$pid=self::$currentProfileId;
		}
		$mpds =  self::getStatusArray($pid);
		$result = array();
		if ($mpds) {
			foreach ( self::$appropriateStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1 ) {
		       		$result[$index] = self::$_AWK_FILTER_TEXT[$index];
				}
			}
			foreach ( self::$unAppropriateStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1 ) {
		       		$result[$index] = self::$_AWK_FILTER_TEXT[$index];
				}
			}
			foreach ( self::$unKnownStatus as $index) {
				if (isset($mpds['status'.$index]) && $mpds['status'.$index] == 1 ) {
		       		$result[$index] = self::$_AWK_FILTER_TEXT[$index];
				}
			}
		}

		 
		if (count($result) > 0) {
			$forReturn = STATUS_FIELD."~/".implode(STR_DLMT,$result)."/";
		} else {
			$forReturn = STATUS_FIELD."~/*/";
		}

		# ali dodamo tudi lurkerje 
		if (isset($mpds['statuslurker']) && (int)$mpds['statuslurker'] == 0 ) {
			$forReturn = '('.$forReturn.')&&('.LURKER_FIELD.'==0)';
		} else if (isset($mpds['statuslurker']) && $mpds['statuslurker'] == 1 ) {
			$forReturn = '('.$forReturn.')&&('.LURKER_FIELD.'==1)';
		} else {
			$forReturn = '('.$forReturn.')';
		}
		return $forReturn;
	}
	
	static function getStatusTestAsAWKString($test_data_sequence) {
		$forReturn = null;
		$mpds =  self::getStatusArray(self::$currentProfileId);
		if ($mpds && (int)$test_data_sequence > 0) {
			# ali dodamo tudi testne
			if (isset($mpds['statustestni']) && (int)$mpds['statustestni'] == 0 ) {
				$forReturn = '$'.$test_data_sequence.'==0';
			} else if (isset($mpds['statustestni']) && $mpds['statustestni'] == 1 ) {
				$forReturn = '$'.$test_data_sequence.'==1';
			} else {
				$forReturn = null;
			}
		}
		return $forReturn;
	}
	
	static function getStatusUsableAsAWKString($usable_data_sequence) {
		
		$forReturn = null;

		$mpds =  self::getStatusArray(self::$currentProfileId);
		$result = array();	
		if ($mpds && (int)$usable_data_sequence > 0) {
			if(isset($mpds['statusnonusable']) && (int)$mpds['statusnonusable'] == 1)
				$result[] = '0';
				
			if(isset($mpds['statuspartusable']) && $mpds['statuspartusable'] == 1)
				$result[] = '1';
			
			if(isset($mpds['statususable']) && $mpds['statususable'] == 1)
				$result[] = '2';
		}
		
		 
		if (count($result) > 0) {
			$forReturn = '$'.$usable_data_sequence."~/".implode(STR_DLMT,$result)."/";
		} else {
			$forReturn = '$'.$usable_data_sequence."~/*/";
		}
		

		return $forReturn;
	}
	
	/** Shranimo v obstoječ profil
	 * 
	 * @param unknown_type $pid
	 * @param unknown_type $name
	 * @param unknown_type $status
	 */
	static function saveProfile($pid) {
		global $lang;
		$insert_id = 0;
		
		$status = $_POST['status'];
		
		if (isset($pid) && $pid != null && isset($status) && $status != null) {
			if ($pid == 1) { # ce mamo privzet profil ga ne shranjujemo
				return 1;
			}
			# ce imamo session pozenemo kot sejo
			if ($pid == -1) {
				$statusi = explode(',',$status);
				$_SESSION['statusProfile'] = array('id'=>'-1','uid'=>0,'name'=>$lang['srv_temp_profile'],'system'=>0);
				foreach (self::$allStatus as $_status) {
					$_SESSION['statusProfile']['status'.$_status] = (in_array((string)$_status,$statusi) ? '1' : '0');
				}
				# lurker:
				$_SESSION['statusProfile']['statuslurker'] = ''.(int)$_POST['lurker'];
				# testni:
				$_SESSION['statusProfile']['statustestni'] = ''.(int)$_POST['testni'];
				
				# uporabnost:
				$_SESSION['statusProfile']['statusnonusable'] = ''.(int)$_POST['nonusable'];
				$_SESSION['statusProfile']['statuspartusable'] = ''.(int)$_POST['partusable'];
				$_SESSION['statusProfile']['statususable'] = ''.(int)$_POST['usable'];
				
				return -1;				
			} 
			# imamo podatke, updejtamo profil v bazi

			$statusi = explode(',',$status);
			if (count(self::$allStatus) > 0 ) {
				
				$updateString = "UPDATE srv_status_profile SET ";
				$prefix = '';
				foreach (self::$allStatus as $_status) {
					$updateString .= $prefix . 'status'.$_status. ' = '.(in_array((string)$_status,$statusi) ? '1' : '0');			
					$prefix =', ';
				}
				# lurker:
				$updateString .= $prefix . 'statuslurker'. ' = '.''.(int)$_POST['lurker'];
				# testni:
				$updateString .= $prefix . 'statustestni'. ' = '.''.(int)$_POST['testni'];
				
				# uporabnost:
				$updateString .= $prefix . 'statusnonusable'. ' = '.''.(int)$_POST['nonusable'];
				$updateString .= $prefix . 'statuspartusable'. ' = '.''.(int)$_POST['partusable'];
				$updateString .= $prefix . 'statususable'. ' = '.''.(int)$_POST['usable'];
				
				$updateString .= " WHERE id = '".$pid."'"; 
				
			}
			$queryUpdate = sisplet_query($updateString) 
				or die(mysqli_error($GLOBALS['connect_db']));

			return $pid;	
		}
		
	}
	
	/** Shranimo kot nov profil
	 * 
	 * @param unknown_type $pid
	 * @param unknown_type $name
	 * @param unknown_type $status
	 */
	static function saveNewProfile($pid) {
		$name = $_POST['name'];
		$status = $_POST['status'];
		$insert_id = 0;
		if (isset($pid) && $pid != null && isset($name) && $name != null && isset($status) && $status != null) {
			# imamo podatke, vstavimo nov profil v bazo
			#id 	uid 	name 	system 	statusnull 	status0 	status1 	status2 	status3 	status4 	status5 	status6
			
			$statusi = explode(',',$status);
			$str_lbl = '';
			$str_vle = '';
			foreach ($statusi as $_status) {
				$str_lbl .= ', status'.$_status;
				$str_vle .= ', 1';
			}
			$insertString = "INSERT INTO srv_status_profile (uid,ank_id,name,system".$str_lbl.",statuslurker,statustestni,statusnonusable,statuspartusable,statususable) 
							VALUES ('".self::getGlobalUserId()."', '".self::$sid."', '".$name."', 0".$str_vle.",'".(int)$_POST['lurker']."','".(int)$_POST['testni']."','".(int)$_POST['nonusable']."','".(int)$_POST['partusable']."','".(int)$_POST['usable']."')";
			$queryInsert = sisplet_query($insertString) 
				or die(mysqli_error($GLOBALS['connect_db']).$insertString);
			$insert_id = mysqli_insert_id($GLOBALS['connect_db']);	
		}
		return $insert_id;
	}
	
	static function DeleteProfile($pid) {
		if ($pid == -1) {
			unset($_SESSION['statusProfile']);
		}
		# izbrišemo lahko samo nesistemske profile
		if (self::$profiles[$pid]['system'] != 1) {
			$sqlDelete = sisplet_query("DELETE FROM srv_status_profile WHERE id = '$pid' AND `system` != '1'");		
		} 
		self::setDefaultProfileId();
		
#		meta_akcija	analysis
	}
	
	static function renameProfile($pid) 
	{
		$name = trim($_POST['name']);
		
		if (!empty($name))
		{
			
			if ($pid == -1) 
			{	# preimenujemo v seji
				$_SESSION['statusProfile']['name'] = $name;
			}

			# preimenujemo lahko samo nesistemske profile
			if (self::$profiles[$pid]['system'] != 1) 
			{
				$sqlRename = sisplet_query("UPDATE srv_status_profile SET name='$name' WHERE id = '$pid' AND `system` != '1'");		
			}
		} 
	}
		
	static function DisplayLink($hideAdvanced = true, $hideSeperator = false) {
		global $lang;
		// profili statusov
        $statusProfiles = self :: getProfiles();
        $izbranStatusProfile = self :: getCurentProfileId();
		
        $css = ($izbranStatusProfile == SSP_DEFAULT_PROFILE ? ' gray' : '');
        if ($hideAdvanced == false || $izbranStatusProfile != SSP_DEFAULT_PROFILE) {
        	if ($hideSeperator == false) {
        		echo '<li class="space">&nbsp;</li>';
        	}
        	echo '<li>';
	        echo '<span class="as_link'.$css.'" onclick="show_status_profile();return false;" title="' . $lang['srv_statusi'] . '">' . $lang['srv_statusi'] . '</span>';
	        echo '</li>';
	        	
        }		
	}
	
	public static function FileGeneratingSetting($hideAdvanced = true) {
		global $lang, $admin_type;
		
		$_sql_string = "SELECT collect_all_status FROM srv_data_files WHERE sid = '".self::$sid."'";
		$_sql_qry = sisplet_query($_sql_string);
		$_sql_row = mysqli_fetch_assoc($_sql_qry);
				
		echo '<li class="space">&nbsp;</li>';
		echo '<li>';
        $css = ((int)$_sql_row['collect_all_status'] > 0 ? ' gray' : '');
        echo '<span class="as_link'.$css.'" onclick="showColectDataSetting(); return false;" title="' . $lang['srv_collect_data_setting_note'] . '">' . $lang['srv_collect_data_setting_note'] . '</span>';
        echo '</li>';
        
		return;
	}
	
	static function showColectDataSetting() {
		global $lang, $admin_type;
		
		$_sql_string = "SELECT collect_all_status FROM srv_data_files WHERE sid = '".self::$sid."'";
		$_sql_qry = sisplet_query($_sql_string);
		list($collect_all_status) = mysqli_fetch_row($_sql_qry);

		$str_qry_cnt_user = "SELECT count(*) FROM srv_user AS u WHERE u.ank_id = '".self::$sid."'";
		$_qry_cnt_user = sisplet_query($str_qry_cnt_user);
		list($all_user_cnt) = mysqli_fetch_row($_qry_cnt_user);
		
		#vsebino shranimo v buffer
		$content = '';
				
		# kadar imamo uporabnikov več kakor ONLY_VALID_LIMIT (5000) ne dovolimo nastaviti na vse statuse, zato dodamo obvestilo
		# le admin lahko nastavi da generira tudi nad 5000 takrat nastavimo collect_all_status = 2
		if ($all_user_cnt > ONLY_VALID_LIMIT && (int)$admin_type > 1)
		{
			$content .= '<span class="red strong">';
			$content .= 'Anketa vsebuje več kakor '.ONLY_VALID_LIMIT.' respondentov, zato je dovoljen prikaz le ustreznih!';
			$content .= '</span><br/><br/>';
			$content .= '<span class="gray">'.$lang['srv_collect_data_setting_note'] . Help :: display('srv_collect_data_setting').'</span>';
			$content .= '<label class="gray">';
			$content .= '<input type="checkbox" id="collect_all_status" name="collect_all_status" '.((int)$collect_all_status > 0 ? '' : ' checked' ).' autocomplete="off" disabled="disabled">';
			$content .= $lang['srv_collect_all_status_0'].'</label>';
		}
		else
		{
			$content .= '<span '.($admin_type <= 1 ? '' : ' class="gray"').'>'.$lang['srv_collect_data_setting_note'] . Help :: display('srv_collect_data_setting').'</span>';
			$content .= '<label'.($admin_type <= 1 ? '' : ' class="gray"').'>';
			$content .= '<input type="checkbox" id="collect_all_status" name="collect_all_status" '.((int)$collect_all_status > 0 ? '' : ' checked').' autocomplete="off"'.($admin_type <= 1 ? '' : ' disabled="disabled"').'>';
			$content .= $lang['srv_collect_all_status_0'].'</label>';
		}
		$content .= '<br class="clr">';
		$content .= '<br class="clr">';
		$content .= '<span class="bold as_link" onclick="deleteSurveyDataFile(\''.$lang['srv_deleteSurveyDataFile_confirm'].'\');" title="'.$lang['srv_deleteSurveyDataFile_link'].'">'.$lang['srv_deleteSurveyDataFile_link'].'</span>';
				 
		
		$popUp = new PopUp();
		$popUp->setId('div_data_file');
		$popUp -> setHeaderText($lang['srv_file_settings']);
		
		#dodamo vsebino
		$popUp -> setContent($content);	

		#dodamo gumb izberi profil
		$button = new PopUpButton($lang['srv_save_profile']);
		$button -> setFloat('right')
				-> setButtonColor('orange')
				-> addAction('onClick','changeColectDataStatus(); return false;');
		$popUp->addButton($button);
		
		# dodamo gumb Prekliči
		$button = new PopUpCancelButton();
		$button -> setFloat('right');
		$popUp->addButton($button);

		echo $popUp;
	}


	static function savecollectdatasetting() {
		global $admin_type;
		
        // Najprej pobrišemo vse datoteke
        $SDF = SurveyDataFile::get_instance();
        $SDF->init(self::$sid);
        $SDF->clearFiles();
		
		$_collect_all_status = (isset($_POST['collect_all_status']) && (int)$_POST['collect_all_status'] == 1 ) ? '1' : '0';
		if ($admin_type == '0' && $_collect_all_status == '1') {
			$_collect_all_status = '2';
		}
		
		// Ce imamo socialna omrezja, zacasno vedno generiramo samo ustrezne
		if(SurveyInfo::getInstance()->checkSurveyModule('social_network')){
			$_collect_all_status = '0';
		}
		
		// če smo zbrali samo statuse ustrezno, popravimo profile da ni izbran profil vsi statusi
		if ((int)$_collect_all_status == 0) {
			// Ce smo izbrali drug profil resetiramo še profil profilov na trenutne nastavitve
			SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', '0');
				
			SurveyUserSetting :: getInstance()->saveSettings('default_status_profile_data', 2);
			SurveyUserSetting :: getInstance()->saveSettings('default_status_profile_analysis', 2);
		}
	}
	
	static function getProfilesValues()
	{
		$_sql_string = "SELECT collect_all_status FROM srv_data_files WHERE sid = '".self::$sid."'";
		$_sql_qry = sisplet_query($_sql_string);
		list($collect_all_status) = mysqli_fetch_row($_sql_qry);
		
		
		return array('all_status'=>(int)$collect_all_status);
	}
	
	static function getActiveProfileUserCount()
	{
		$pid = self::$currentProfileId;
		if ($pid == 1)
		{
			return (int)self::$allUserCount;
		}
		else if( $pid == 2)
		{
			return (int)self::$allValidCount;
		}
		else
		{
			# preštejemo userje
			$str_all = "SELECT count(*) FROm srv_user WHERE ank_id='".self::$sid."' AND deleted='0' AND preview = '0' ".self::getStatusAsQueryString($pid);
			$query_all = sisplet_query($str_all);
			list($all) = mysqli_fetch_row($query_all);
			return $all;
		}	
	}

	static function displayOnlyValidCheckbox(){
		global $lang;
		
		$pid = self::$currentProfileId;
		
		$values = self::getProfilesValues();
		$collect_all_status = $values['all_status'];
		
		// če ni ustreznih uporabnikov je privzeto vsi
		if (self::$allValidCount == 0) {
			
            $pid = 1;
			$disabledValid = ' disabled="disabled"';
			$disabledGray = ' gray';
		} 
        else if (self::$allValidCount > 0 && $collect_all_status == 0) {
			
            /*if ( $pid == 1 ) {
				#$pid = 2;
				$pid = self::$survayDefaultUstrezni;
			}*/

            if((int)self::$allUserCount > 1000){
			    $disabledAll = ' disabled="disabled"';
			    $disabledAllGray = ' gray';
            }
		} 

		echo '<label class="middle'.$disabledAllGray.'">';
		echo '<input type="radio" id="statusAllUnit" name="statusOnlyValid" value="1"'.($pid == 1?' checked="checked"':'').$disabledAll.' onchange="changeOnlyValidRadio();" autocomplete="off">';
		echo $lang['srv_data_all_units'].'&nbsp;('.(int)self::$allUserCount.')';
		echo '</label>';
		echo '&nbsp;';
		
		if (self::$survayDefaultUstrezni == 2) {
			echo '<label class="middle'.$disabledGray .'">';
			echo '<input type="radio" id="statusValidUnit" name="statusOnlyValid" value="2"'.($pid==2?' checked="checked"':'').$disabledValid.' onchange="changeOnlyValidRadio();" autocomplete="off">';
			echo $lang['srv_data_valid_units'].'&nbsp;('.(int)self::$allValidCount.')';
			echo '</label>';
		} 
        else {
			echo '<label class="middle'.$disabledGray .'">';
			echo '<input type="radio" id="statusValidUnit" name="statusOnlyValid" value="3"'.($pid==3?' checked="checked"':'').$disabledValid.' onchange="changeOnlyValidRadio();" autocomplete="off">';
			echo $lang['srv_data_finished_units'].'&nbsp;('.(int)self::$allValidCount.')';	
		}
	
		echo '&nbsp;'.Help::display('srv_data_only_valid');
	}
	
	static function changeOnlyValidRadio() {
		if (isset($_POST['checked']) && (int)$_POST['checked'] > 0) {
			$dpid = (int)$_POST['checked'];
		} else {
			#$dpid = 2;
			$dpid = (int)self::$survayDefaultUstrezni;
		}
		self::setDefaultProfileId($dpid);
		
	}
	
	/** preveri obstoj profila in vrne enak id če obstaja, če ne vrne id privzetega profila
	 * 
	 * @param unknown_type $pid
	 * @return unknown
	 */
	function checkProfileExist($pid)
	{
		if (isset(self::$profiles[$pid]))
		{
			return true;
		}
		return false;
	}
	
	static function getFiltersData(){
		global $lang;
		$pid = self::$currentProfileId;

		$pid = 1;
		$txt = $lang['srv_data_all_units'];
		$userCount = (int)self::$allUserCount;
		
		$pid =2;
		$txt = $lang['srv_data_valid_units'];
		$srv_data_status_units = (int)self::$allValidCount;

		$pid = $pid;
		$txt = $lang['srv_data_status_units'];
		
		$str_all = "SELECT count(*) FROm srv_user WHERE ank_id='".self::$sid."' AND deleted='0' AND preview = '0' ".self::getStatusAsQueryString($pid);
		$query_all = sisplet_query($str_all);
		list($all) = mysqli_fetch_row($query_all);
		$srv_data_status_units = $all;
			
	}
	
	public static function usabilitySettings(){
		
		$customUsabilitySettings = false;
		
		$mpds =  self::getStatusArray(self::$currentProfileId);	
		if($mpds) {
			if((isset($mpds['statusnonusable']) && $mpds['statusnonusable'] == 0) 
				|| (isset($mpds['statuspartusable']) && $mpds['statuspartusable'] == 0)
				|| (isset($mpds['statususable']) && $mpds['statususable'] == 0)){
				
				$customUsabilitySettings = true;
			}
		}
		
		// Pogledamo ce je kjerkoli vklopljen profil z uporabnostjo (drugace pride do konflikta, ce je recimo vklopljen v analizah in izklopljen v podatkih)
		$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_status_profile_'.A_COLLECT_DATA);
		if($currentProfileId != self::$currentProfileId){
			$mpds =  self::getStatusArray($currentProfileId);	
			if($mpds) {
				if((isset($mpds['statusnonusable']) && $mpds['statusnonusable'] == 0) 
					|| (isset($mpds['statuspartusable']) && $mpds['statuspartusable'] == 0)
					|| (isset($mpds['statususable']) && $mpds['statususable'] == 0)){
					
					$customUsabilitySettings = true;
				}
			}
		}
		$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_status_profile_'.A_ANALYSIS);		
		if($currentProfileId != self::$currentProfileId){
			$mpds =  self::getStatusArray($currentProfileId);	
			if($mpds) {
				if((isset($mpds['statusnonusable']) && $mpds['statusnonusable'] == 0) 
					|| (isset($mpds['statuspartusable']) && $mpds['statuspartusable'] == 0)
					|| (isset($mpds['statususable']) && $mpds['statususable'] == 0)){
					
					$customUsabilitySettings = true;
				}
			}
		}
		
		return $customUsabilitySettings;
	}
}
?>