<?php
/**
 * Created on 13.01.2011
 *
 * @author: Gorazd Veselič
 */



define('STP_INTERVAL_1_DAY', '1 day');			# previdno pri spremembi tekstov, ker gre za PHP veljavne stringe za računanje z datumi
define('STP_INTERVAL_2_DAY', '2 day');
define('STP_INTERVAL_5_DAY', '5 day');
define('STP_INTERVAL_7_DAY', '7 day');
define('STP_INTERVAL_14_DAY', '14 day');
define('STP_INTERVAL_1_MONTH', '1 month');
define('STP_INTERVAL_3_MONTH', '3 month');
define('STP_INTERVAL_6_MONTH', '6 month');


define('STP_DEFAULT_PROFILE', 0);

class SurveyTimeProfiles {

	static private $surveyId = null;
	static private $uId = null;

	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika
	
	static private $start_date = null;			# začetek ankete
	static private $end_date = null;			# konec ankete

	static private $STP_ARRAYS = array(	STP_INTERVAL_1_DAY,
										STP_INTERVAL_2_DAY,
										STP_INTERVAL_5_DAY,
										STP_INTERVAL_7_DAY,
										STP_INTERVAL_14_DAY,
										STP_INTERVAL_1_MONTH,
										STP_INTERVAL_3_MONTH,
										STP_INTERVAL_6_MONTH); // array možnih intervalov za dropdown

	static function getSurveyId()				{ return self::$surveyId; }
	static function getGlobalUserId()			{ return self::$uId; }
	static function getCurentProfileId()		{ return (int)self::$currentProfileId; }
	
	/** Inizializacija, poišče id privzetega profila in prebere vse profiel ki jih ima uporabnik na voljo
	 * 
	 * @param $_surveyId
	 */
	static function Init($_surveyId)
	{

		global $global_user_id, $lang;
		 
		if ($_surveyId && $global_user_id)
		{
			self::$surveyId = $_surveyId;
			self::$uId = $global_user_id;
		
			# inicializiramo datoteko z nastavitvami
			SurveyUserSetting :: getInstance()->Init(self::$surveyId, self::$uId);			
			# preverimo ali ima uporabnik nastavljen privzet profil
			$dsp = SurveyUserSetting :: getInstance()->getSettings('default_time_profile');

			if ( $dsp == null ) {
				# nastavimo privzet profil v clas
				$dsp = 0;
			}
			
			#dodamo profil iz seje
			if ( (int)$dsp == -1 ) {
				if ( isset($_SESSION['time_profile'][self::$surveyId])) {
					#dodamo profil iz seje
					self::$profiles['-1'] = array('id'=>'-1',
					  	'name'=>$lang['srv_temp_profile'],
						'type'=>$_SESSION['time_profile'][self::$surveyId]['type'],
						'starts'=>$_SESSION['time_profile'][self::$surveyId]['starts'],
						'ends'=>$_SESSION['time_profile'][self::$surveyId]['ends'],
						'interval_txt'=>$_SESSION['time_profile'][self::$surveyId]['interval_txt']);
					$dsp = -1;
					
				} else {
					// ni v seji, naredimo privzetega
					$dsp = 0;
				}
			}

			# če mamo spremembo shranimo
			self::SetDefaultProfileId((int)$dsp);
			
			#dodamo privzet profil
			# datum od, "ce ni podan vzamemo kreacijo ankete
			SurveyInfo :: getInstance()->SurveyInit(self::getSurveyId());

			self::$start_date = date(STP_DATE_FORMAT, strtotime(SurveyInfo::getInstance()->getSurveyInsertDate()));

			# datum do, "ce ni podan vzamemo danasnji dan
			self::$end_date = date(STP_DATE_FORMAT);// ce ne, 

			# dodamo sistemski profil
			self::$profiles['0'] = array(	'id'=>0,
											'type'=>0,
										  	'name'=>$lang['srv_default_profile'],
											'starts'=>self::$start_date,
											'ends'=>self::$end_date,
											'interval_txt'=>'');

			# poiščemo še seznam vseh ostalih profilov uporabnika
			$stringSelect = "SELECT  id, name, type, DATE_FORMAT(starts,'".STP_CALENDAR_DATE_FORMAT."') AS starts, DATE_FORMAT(ends,'".STP_CALENDAR_DATE_FORMAT."') AS ends, interval_txt FROM  srv_time_profile WHERE uid = '".self::getGlobalUserId()."' || uid = '0' ORDER BY id";
			$querySelect = sisplet_query($stringSelect);

			while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = array(	'id'=>$rowSelect['id'],
											  	'name'=>$rowSelect['name'],
											  	'type'=>$rowSelect['type'],
												'starts'=>$rowSelect['starts'],
												'ends'=>$rowSelect['ends'],
												'interval_txt'=>$rowSelect['interval_txt']);
			}
			
			# nastavimo id profil klassa na izbran
			self::$currentProfileId = (int)$dsp;
			
			return true;
		} else { 
			return false;
		}
	}
	
	static function getSystemDefaultProfile() {
		return (int)STP_DEFAULT_PROFILE;
	}
	
	/** Vrne podatke trenutno izbranega profofila
	 * 
	 */
	static function GetCurentProfileData() {
		return	self::$profiles[self::$currentProfileId]; 
	}

	/** Vrne podatke podanega profofila
	 * 
	 */
	static function GetProfileData($pid) {
		return	self::$profiles[$pid]; 
	}

	public function getProfileName($pid) {
		return self::$profiles[$pid]['name'];
	}
	
	/** Vrne array z start date in end date
	 * 
	 */		
	static function GetDates($forceDefaultFilter = false) {
		if ($forceDefaultFilter == false) {
			$_profile_data = self :: GetCurentProfileData();
			# ali imam o privzete datume filtra
			$is_default_dates = ((int)$_profile_data['id'] == 0 ? true : false);
		} else {
			# zaradi možnosti masovnega zbiranja vsilimo privzet datum
			$_profile_data = self::$profiles[0];
			# ali imam o privzete datume filtra
			$is_default_dates = true;
		}
		
		# nastavimo start date in end date
		if ($_profile_data['interval_txt'] != '') {
			# ce imamo nastavljen datum preko intervala
			$end_date = date(STP_OUTPUT_DATE_FORMAT);
			$start_date = date(STP_OUTPUT_DATE_FORMAT,strtotime(date(STP_OUTPUT_DATE_FORMAT, strtotime($end_date)) . ' - '.$_profile_data['interval_txt']));

		} else if ($_profile_data['starts'] != '' && $_profile_data['ends'] != '') {
			# imamo podana oba datuma
			$start_date = date(STP_OUTPUT_DATE_FORMAT,strtotime($_profile_data['starts']));
			$end_date = date(STP_OUTPUT_DATE_FORMAT,strtotime($_profile_data['ends']));
		} else {
			# napaka vzamemo datum kreacije ankete in današnji datum
			$start_date = date(STP_OUTPUT_DATE_FORMAT,strtotime(SurveyInfo::getInstance()->getSurveyInsertDate()));
			$end_date = date(STP_OUTPUT_DATE_FORMAT);;

		}
		# končni datum po potrebi zmanjšamo na današnji datum
		if (strtotime($end_date) > strtotime(date(STP_OUTPUT_DATE_FORMAT))) { 
			$end_date = date(STP_OUTPUT_DATE_FORMAT);
		}  
		return array('start_date'=>$start_date, 'end_date'=>$end_date, 'is_default_dates' => $is_default_dates);		
	}
	
	/** Pridobimo seznam vseh list uporabnika
	 *  v obliki arraya
	 */
	static function getProfiles() {
		return self::$profiles;
	}
	
	/* Vrne ID in ime trenutno izbranega profila
	*
	*/
	function getCurentProfile() {
		return array('id'=>self::$currentProfileId,'name'=>self::$profiles[self::$currentProfileId]['name']);
	}

	/** Ponastavi id privzetega profila
	 * 
	 */
	static function SetDefaultProfile($pid) {
		self::SetDefaultProfileId($pid);
	}
	
	static function SetDefaultProfileId($pid) {
	
		self::$currentProfileId = (int)$pid;
		
		$saved = SurveyUserSetting :: getInstance()->saveSettings('default_time_profile',(int)$pid);
	}
	
	static function ChooseProfile($pid) {
		# če smo izbrali drug profil resetiramo še profil profilov na trenutne nastavitve
		SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', '0');
	
		self::SetDefaultProfileId((int)$pid);
		self::$currentProfileId = (int)$pid;
		
	}
	
	/** 
	 * 
	 */
	static function SaveProfile($pid,$type,$startDate,$endDate,$stat_interval) {
		global $lang;
		if ((int)$pid == 0 ) {
			# imamo privzet profil
			self :: ChooseProfile((int)$pid);
			$updated = true;
		} else if ((int)$pid > 0) {
			# shranimo v bazo
			if ((int)$type == 0) { # $type = '0';
				# shranjujemo od - do
				$stat_interval = '';
				$_startDate = date(STP_OUTPUT_DATE_FORMAT, strtotime($startDate));
				$_endDate = date(STP_OUTPUT_DATE_FORMAT, strtotime($endDate));
				
				$update = "UPDATE srv_time_profile SET starts = '".$_startDate."', ends='".$_endDate."', type='".$type."', interval_txt = '' WHERE id = '".$pid."'";
			} else { # $type = '1';
				# shranjujemo interval
				$startDate = '';
				$endDate = '';
				$type = '1';
				$update = "UPDATE srv_time_profile SET starts = '0000-00-00 00:00:00', ends='0000-00-00 00:00:00', type='".$type."', interval_txt = '".$stat_interval."' WHERE id = '".$pid."'";
			}
			
			$updated = sisplet_query($update);
			# ce je bili updejt ok  posodobimo se vrednost v profilu
			if ($updated) {
				self::$profiles[$pid]['type'] = $type;
				self::$profiles[$pid]['starts'] = $startDate;
				self::$profiles[$pid]['ends'] = $endDate;
				self::$profiles[$pid]['interval_txt'] = $stat_interval;
			}
			
			# nastavimo privzet profil na trenutnega
			self :: ChooseProfile((int)$pid);
			
		} else {
			# shranjujenmo v sejo
			$_SESSION['time_profile'][self::$surveyId] = array('id'=>'-1',
				  	'name'=>$lang['srv_temp_profile']);
			
			if ((int)$type == 0) { # $type = '0';
				# shranjujemo od - do
				$_SESSION['time_profile'][self::$surveyId]['type'] = '0';
				$_SESSION['time_profile'][self::$surveyId]['starts'] = date(STP_OUTPUT_DATE_FORMAT, strtotime($startDate));
				$_SESSION['time_profile'][self::$surveyId]['ends'] = date(STP_OUTPUT_DATE_FORMAT, strtotime($endDate));
				unset($_SESSION['time_profile'][self::$surveyId]['interval_txt']);
			} else {
				$_SESSION['time_profile'][self::$surveyId]['type'] = '1';
				$_SESSION['time_profile'][self::$surveyId]['interval_txt'] = $stat_interval;
				unset($_SESSION['time_profile'][self::$surveyId]['starts']);
				unset($_SESSION['time_profile'][self::$surveyId]['ends']);
			} 
			self::$profiles[$pid] = $_SESSION['time_profile'][self::$surveyId]; 
			
			$updated = true;
			self :: ChooseProfile((int)$pid);
				
		}
		return $updated;
	}

	static function RenameProfile($pid, $name) {

		if (isset($pid) && $pid > 0 && isset($name) && trim($name) != "") {
			// popravimo podatek za variables 
			$stringUpdate = "UPDATE srv_time_profile SET name = '".$name."' WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
			return $updated;
		} else {
			return -1;
		}
	}
	 	
	static function DeleteProfile($pid = 0) {

		if (isset($pid) && $pid == -1) {
			unset($_SESSION['time_profile'][self::$surveyId] );
		} else  if (isset($pid) && $pid > 0) {
			// Izbrišemo profil in nastavimo privzetega 
			$stringUpdate = "DELETE FROM srv_time_profile WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
		}
		# nastavimo privzet profil
		self::ChooseProfile('0');
	}

	/** Funkcija kreira nov profil
	 *  
	 */
	function createProfile($type,$startDate,$endDate,$stat_interval,$name=null) {
		global $lang;
		if ($name == null || trim($name) == '' ) {
			$name = $lang['srv_new_profile'];
		}

		if ($type == '0') {
			# shranjujemo od - do
			$startDate = date(STP_OUTPUT_DATE_FORMAT, strtotime($startDate));
			$endDate = date(STP_OUTPUT_DATE_FORMAT, strtotime($endDate));
			$stat_interval = '';
		} else {
			# shranjujemo interval
			$startDate = '0000-00-00';
			$endDate = '0000-00-00';
			$type = '1';
		}

		$iStr = "INSERT INTO srv_time_profile (id,uid,name,type,starts,ends,interval_txt)".
		" VALUES (NULL, '".self::$uId."', '".$name."', '".$type."', '".$startDate."', '".$endDate."', '".$stat_interval."')";
		
		$ins = sisplet_query($iStr);
		$id = mysqli_insert_id($GLOBALS['connect_db']);
		
		if ($id > 0) {
			self :: ChooseProfile($id);
		} else {
			self :: ChooseProfile(0);
		}

		return;
	}
	
	/** prikažemo dropdown z izbranim profilom in link do nastavitev profila
	 * 
	 * 
	 */
	static function DisplayLink($hideAdvanced = true, $showseperator = true) {
		global $lang;

        $profiles = self :: getProfiles();
        $izbranProfil = self :: getCurentProfileId();
        
        $css = ($izbranProfil == STP_DEFAULT_PROFILE ? ' gray' : '');
        if ($hideAdvanced == false || $izbranProfil != STP_DEFAULT_PROFILE) {
        	if ($showseperator == true) {
        		echo '<li class="space">&nbsp;</li>';
        	}
        	echo '<li>';
        	echo '<span class="as_link'.$css.'" id="link_time_profile" title="' . $lang['srv_time_profile_link_title'] . '" onClick="timeProfileAction(\'showProfiles\');">' . $lang['srv_time_profile_link'] . '</span>';
        	echo '</li>';
        	
        }
	}
	
	
	/** Funkcija prikaze izbor datuma
	 *  
	 */
	static function displayProfiles($current_pid = null) {
		global $lang;
        $_all_profiles = self::getProfiles();

		// Naslov
        echo '<h2>'.$lang['srv_obdobje_settings'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="timeProfileAction(\'cancel\'); return false;">✕</a></div>';
		
        if ($current_pid == null) {
        	$current_pid = self::getCurentProfileId();
        }
        $currentFilterProfile = $_all_profiles[$current_pid];

        if ( $current_pid != STP_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }
        
		echo '<div class="time_profile_left_right floatLeft">';
       	echo '<div class="time_profile_holder">';
		# zlistamo vse profile

       	echo '<div id="time_profile" class="select">';
		
		if (count($_all_profiles)) {
			foreach ($_all_profiles as $id=>$profile) {
				
				echo '<div class="option' . ($current_pid == $id ? ' active' : '') . '" id="time_profile_' . $id . '" value="'.$id.'">';

				echo $profile['name'];
				
				if($current_pid == $id){
					# privzetega profila ne moremo ne zbrisat ne preimenovat
					if ($current_pid != 0) {
						echo '<a href="#" title="'.$lang['srv_delete_profile'].'" onclick="timeProfileAction(\'show_delete\'); return false;"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>';
					}
					if ($current_pid > 0) {
						echo '<a href="#" title="'.$lang['srv_rename_profile'].'" onclick="timeProfileAction(\'show_rename\'); return false;"><span class="faicon edit floatRight spaceRight"></span></a>';
					}
				}
				
				echo '</div>';	
			}
		}
		echo '	</div>'; // time_profile
		echo '</div>'; //time_profile_holder

		echo '</div>'; //time_profile_left

		
		echo '<div class="time_profile_left_right floatRight">';
		echo '<div id="time_profile_content">';
		self::DisplayProfileData($current_pid);
		echo '</div>'; // time_profile_content
		echo '</div>'; // time_profile_right
		
		
		echo '<div class="time_profile_button_right_holder floatRight">';
		if ($current_pid == 0) {
			echo '<span class="floatRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="timeProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
#			echo '<span class="floatRight spaceRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'run_profile\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'show_create\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
		} else if ($current_pid == -1) {
			echo '<span class="floatRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="timeProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'show_create\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
		} else  {
			echo '<span class="floatRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="timeProfileAction(\'run_profile\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'show_create\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
			
		}
		echo '</div>'; // time_profile_button_right_holder
		
		
		// cover Div
        echo '<div id="timeProfileCoverDiv"></div>';
		
        // div za kreacijo novega
        echo '<div id="newProfileDiv">'.$lang['srv_missing_profile_name'].': ';
        echo '<input id="newProfileName" name="newProfileName" type="text" value="" size="50"  />';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="timeProfileAction(\'do_create\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>';            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'cancel_create\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';
        echo '</div>';
        
        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': ';
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . $currentFilterProfile['name'] . '" size="50"  />';
        echo '<input id="renameProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="timeProfileAction(\'do_rename\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>';            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'cancel_rename\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';
        echo '</div>';
                
        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . $currentFilterProfile['name'] . '</b>?';
        echo '<input id="deleteProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="timeProfileAction(\'do_delete\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="timeProfileAction(\'cancel_delete\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';            
        echo '</div>';		
    }
    
	/** Funkcija prikaze osnovnih informacije profila
	 * 
	 */
	static function DisplayProfileData($current_pid) {
		global $lang;
		# podatki profila
		$p_data = self::GetProfileData($current_pid);

		#kateri nacin imamo ali type (od - do) ali inervalsko (zadnjih....)
		# ce je podan string interval imamo intervalno
		$type = $p_data['type'];
		if ( $type == '0') {
			$time = '';
		} else {
			$p_data['starts'] = date(STP_DATE_FORMAT,strtotime(SurveyInfo::getInstance()->getSurveyInsertDate()));
			$p_data['ends'] = date(STP_DATE_FORMAT);
			$time = $p_data['interval_txt'];
		}

		echo '<input type="radio" name="type" id="time_date_type" value="0" '.($type == '0' ? ' checked="checked"' : '').' autocomplete="off"><label> ' . $lang['srv_time_profile_from'] . ': </label>';
		echo '<input id="startDate" type="text" name="startDate" value="' . $p_data['starts'] . '" onclick="changeTimeProfileType();" readonly="true" '.' autocomplete="off"/>&nbsp;';
		echo '<span class="faicon calendar_icon icon-as_link" id="starts_img"></span>';
		echo '<label> ' . $lang['srv_time_profile_to'] . ': </label>';
		echo '<input id="endDate" type="text" name="endDate" value="' . $p_data['ends'] . '" onclick="changeTimeProfileType();" readonly="true" '.'cautocomplete="off"/>&nbsp;';
		echo '<span class="faicon calendar_icon icon-as_link" id="expire_img"></span>' . "\n" ;
		echo '<br />';
		echo '<p><input type="radio"  name="type" id="time_date_interval" value="1" '.($type == '0' ? '' : ' checked="checked"').' autocomplete="off">'.$lang['srv_statistic_period_label'].':';
		echo '<select name="stat_interval" id="stat_interval" onclick="changeTimeProfileType(\'interval\');" '.'autocomplete="off">';
		echo '<option value="" selected="true">'.$lang['srv_time_profile_choose_interval'].'</option>';
		foreach (self::$STP_ARRAYS as $INTERVAL) {
			echo '<option value="'.$INTERVAL.'"' . ($time == $INTERVAL ? ' selected' : '') . '>'.$lang['srv_diagnostics_'.$INTERVAL].'</option>';
		}
			echo '</select>';
		echo '</p>';
		
		echo '<script type="text/javascript">';
		# za profil id=0 (privzet profil ne pustimo spreminjat
			echo 
			'    Calendar.setup({' . "\n" .
			'        inputField  : "startDate",' . "\n" .
			'        ifFormat    : "'.STP_CALENDAR_DATE_FORMAT.'",' . "\n" .
			'        button      : "starts_img",' . "\n" .
			'        singleClick : true,' . "\n" .
			'        onUpdate    : changeTimeProfileType' . "\n" .
			'    });' . "\n" .
			'    Calendar.setup({' . "\n" .
			'        inputField  : "endDate",' . "\n" .
			'        ifFormat    : "'.STP_CALENDAR_DATE_FORMAT.'",' . "\n" .
			'        button      : "expire_img",' . "\n" .
			'        singleClick : true,' . "\n" .
			'        onUpdate    : changeTimeProfileType' . "\n" .
			'    })';

		echo '</script>';

	}
	
	public static function ajax() {
		switch ($_GET['a']) {
			case 'showProfile':
				self::displayProfiles($_POST['pid']);
				break;
			case 'createProfile':
				self::createNewProfile();
				break;
			case 'changeProfile':
				self::ChooseProfile($_POST['pid']);
				break;
			case 'renameProfile':
				self::RenameProfile($_POST['pid'], $_POST['name']);
				break;
			case 'deleteProfile':
				self::DeleteProfile($_POST['pid']);
				break;
			case 'saveProfile':
				self::SaveProfile($_POST['pid'],$_POST['type'],$_POST['startDate'],$_POST['endDate'],$_POST['stat_interval']);
				break;
			default:
				print_r("<pre>");
				print_r($_POST);
				print_r($_GET);
			break;				
		}
	} 
	
	/** Kreira nov profil z datumom od začetka ankete do danes
	 * 
	 */
	public static function createNewProfile() {
		global $lang;
		
		if ($_POST['profileName'] == null || trim($_POST['profileName']) == '' ) {
			$_POST['profileName'] = $lang['srv_new_profile'];
		}

		$type = '0';;
		$stat_interval = '';
			 
		$startdate = date(STP_OUTPUT_DATE_FORMAT, strtotime(self::$start_date)); 
		$enddate = date(STP_OUTPUT_DATE_FORMAT, strtotime(self::$end_date)); 
			
		$iStr = "INSERT INTO srv_time_profile (id,uid,name,type,starts,ends,interval_txt)".
		" VALUES (NULL, '".self::getGlobalUserId()."', '".$_POST['profileName']."', '".$type."', '".$startdate."', '".$enddate."', '".$stat_interval."')";
		
		$ins = sisplet_query($iStr);
		$id = mysqli_insert_id($GLOBALS['connect_db']);
		
		if ($id > 0) {
			self::ChooseProfile($id);
		} else {
			$id = 0;
			self::ChooseProfile($id);
		}
		
		return $id;
	}
	
	/** Vrne filter v obliki stringa primernega za uporabo filtriranja z AWK  
	 * 
	 */
	public static function getFilterForAWK ($sequenca = null) {
		# če manjka sekvenca mamo napako in ne delamo filtra
		if ($sequenca == null || $sequenca == '') {
			return '';
		}
		
		$_profile = self::GetCurentProfileData();
		#za privzet porfil id = 0 ne delamo časovnih omejitev
		if ($_profile['id'] == '0') {
			return '';
		} else {
			# odvisno od tipa profila pripravimo omejitve datuma
			$set=false;
			
			if ($_profile['type'] == '0') {
				$result = '(';
				$prefix = '';
				# imamo range od - do
				# spremenimo oba datuma v unixtime
				
				$startUnixDate = date("U", strtotime($_profile['starts']));
				$endUnixDate = date("U", strtotime($_profile['ends']));

				if ((int)$startUnixDate > 0 ) {
					$result .= '$'.$sequenca. ' > ' . $startUnixDate;
					$prefix = ' && ';
					$set=true;
				}
				if ((int)$endUnixDate > 0 ) {
					# + 86 400 seconds = one day
					$result .= $prefix. '$'.$sequenca. ' < ' . ($endUnixDate+ 86400) ;
					$set=true;
				}
				$result .= ')';
			
			} else {
				#imamo interval zadnjih XXX dni
				
				$date = date("Y-m-d");// current date
				$unix_date = strtotime(date("Y-m-d", strtotime($date)) . " -".$_profile['interval_txt']);
 				
				if ((int)$unix_date > 0 && $_profile['interval_txt'] != null && $_profile['interval_txt'] =! '') {
					$result =  '($'.$sequenca. ' > ' . $unix_date.')';
					$set=true;
				}
			}
			if ($set == true) {
				return $result;
			}
		}
		
		
	} 
	
	/** Izpišemo opozorilo če ni privzet profil
	 * 
	 */
	static function printIsDefaultProfile() {
		global $lang;
		if (self::$currentProfileId != 0) {
			$cp_data = self::GetCurentProfileData(); 
			echo '<div id="timeProfileDafaultNote">';
			
			# odvisno od tipa profila izpišemo ali obdobje ali interval
			if ($cp_data['type'] == 0) {
				# obdobje: od - do
				echo $lang['srv_time_profile_filter_dates'];
				echo date(STP_DATE_FORMAT, strtotime($cp_data['starts']));	
				echo $lang['srv_time_profile_filter_dates_2'];
				echo date(STP_DATE_FORMAT, strtotime($cp_data['ends']));	
			} else {
				# interval: zadnjih x dni
				echo $lang['srv_time_profile_filter_period'];
				echo ($lang['srv_diagnostics_'.$cp_data['interval_txt']]);
			}
			
			echo '&nbsp;&nbsp;&nbsp;';
			echo '<span class="as_link" id="link_time_profile_edit">'.$lang['srv_profile_edit'].'</span>';
			echo '&nbsp;&nbsp;';
			echo '<span class="as_link" id="link_time_profile_remove">'.$lang['srv_profile_remove'].'</span>';			
			echo '</div>';
			echo '<br class="clr"/>';

			return true;
			
		} else {
			return false;
		}
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
}
?>