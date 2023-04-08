<?php
/**
 * Created on 19.03.2010
 *
 * @author: Gorazd Veselič
 */


define('SS_DATE_FORMAT', 'd.m.Y');				# format v katerem operiramo v tem klasu
define('SS_OUTPUT_DATE_FORMAT', 'Y-m-d'); 		# format v katerem vrne fumkcija GetStatisticDates()
define('SS_CALENDAR_DATE_FORMAT', '%d.%m.%Y');	# format prikaza koledarja
define('SS_DATE_FORMAT_SHORT', 'j.n.y');
define('SS_TIME_FORMAT_SHORT', 'G:i');

define('SS_INTERVAL_1_DAY', '1 day');			# previdno pri spremembi tekstov, ker gre za PHP veljavne stringe za računanje z datumi
define('SS_INTERVAL_2_DAY', '2 day');
define('SS_INTERVAL_5_DAY', '5 day');
define('SS_INTERVAL_7_DAY', '7 day');
define('SS_INTERVAL_14_DAY', '14 day');
define('SS_INTERVAL_1_MONTH', '1 month');
define('SS_INTERVAL_3_MONTH', '3 month');
define('SS_INTERVAL_6_MONTH', '6 month');

session_start();
class SurveyStatisticProfiles {

	static private $surveyId = null;
	static private $uId = null;

	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika

	static private $SS_ARRAYS = array(	SS_INTERVAL_1_DAY,
										SS_INTERVAL_2_DAY,
										SS_INTERVAL_5_DAY,
										SS_INTERVAL_7_DAY,
										SS_INTERVAL_14_DAY,
										SS_INTERVAL_1_MONTH,
										SS_INTERVAL_3_MONTH,
										SS_INTERVAL_6_MONTH); // array možnih intervalov za dropdown

	protected function __construct() { }

	final private function __clone() {}

	static function getSurveyId()				{ return self::$surveyId; }
	static function getGlobalUserId()			{ return self::$uId; }
	static function getCurentProfileId()		{ return self::$currentProfileId; }
	
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
			$dsp = SurveyUserSetting :: getInstance()->getSettings('default_statistic_profile');

			if ( $dsp == null || $dsp == 0 ) {
				# nastavimo privzet profil v clas
				$dsp = 0;

			}
			#dodamo profil iz seje
			if ( isset($_SESSION['statistic_profile'][self::$surveyId])) {
				#dodamo profil iz seje
				self::$profiles['-1'] = array('id'=>'-1',
				  	'name'=>$lang['srv_temp_profile'],
					'starts'=>$_SESSION['statistic_profile'][self::$surveyId]['starts'],
					'ends'=>$_SESSION['statistic_profile'][self::$surveyId]['ends'],
					'interval_txt'=>$_SESSION['statistic_profile'][self::$surveyId]['interval_txt']);
			}
			// ni v seji, nar3edimo privzeteka
			if ($dsp == -1 && !(isset($_SESSION['statistic_profile'][self::$surveyId]))) {
				$dsp = 0;
			}
			
			self::SetDefaultProfile($dsp);			
						
			#dodamo privzet profil
			# datum od, "ce ni podan vzamemo kreacijo ankete
			SurveyInfo :: getInstance()->SurveyInit(self::getSurveyId());

			$start_date = date(SS_DATE_FORMAT, strtotime(SurveyInfo::getInstance()->getSurveyInsertDate()));

			# datum do, "ce ni podan vzamemo danasnji dan
			$end_date = date(SS_DATE_FORMAT);// ce ne, 

			self::$profiles['0'] = array(	'id'=>0,
										  	'name'=>$lang['srv_default_profile'],
											'starts'=>$start_date,
											'ends'=>$end_date,
											'interval_txt'=>'');

			# poiščemo še seznam vseh ostalih profilov uporabnika
			 
			$stringSelect = "SELECT  id, name, DATE_FORMAT(starts,'".SS_CALENDAR_DATE_FORMAT."') AS starts, DATE_FORMAT(ends,'".SS_CALENDAR_DATE_FORMAT."') AS ends, interval_txt FROM  srv_statistic_profile WHERE uid = '".self::getGlobalUserId()."' || uid = '0' ORDER BY id";
			$querySelect = sisplet_query($stringSelect);

			while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = array(	'id'=>$rowSelect['id'],
											  	'name'=>$rowSelect['name'],
												'starts'=>$rowSelect['starts'],
												'ends'=>$rowSelect['ends'],
												'interval_txt'=>$rowSelect['interval_txt']);
			}
			return true;
		} else 
			return false;
		
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

	/** Vrne array z start date in end date
	 * 
	 */		
	static function GetStatisticDates() {
		$_profile_data = self :: GetCurentProfileData();
		
		# ali imam o privzete datume filtra
		$is_default_dates = (int)($_profile_data['id'] === 0);

		# nastavimo start date in end date
		if ($_profile_data['interval_txt'] != '') {
			# ce imamo nastavljen datum preko intervala
			$end_date = date(SS_OUTPUT_DATE_FORMAT);
			$start_date = date(SS_OUTPUT_DATE_FORMAT,strtotime(date(SS_OUTPUT_DATE_FORMAT, strtotime($end_date)) . ' - '.$_profile_data['interval_txt']));

		} else if ($_profile_data['starts'] != '' && $_profile_data['ends'] != '') {
			# imamo podana oba datuma
			$start_date = date(SS_OUTPUT_DATE_FORMAT,strtotime($_profile_data['starts']));
			$end_date = date(SS_OUTPUT_DATE_FORMAT,strtotime($_profile_data['ends']));
		} else {
			# napaka vzamemo datum kreacije ankete in današnji datum
			$start_date = date(SS_OUTPUT_DATE_FORMAT,strtotime(SurveyInfo::getInstance()->getSurveyInsertDate()));
			$end_date = date(SS_OUTPUT_DATE_FORMAT);;

		}
		# končni datum po potrebi zmanjšamo na današnji datum
		if (strtotime($end_date) > strtotime(date(SS_OUTPUT_DATE_FORMAT))) { 
			$end_date = strtotime(date(SS_OUTPUT_DATE_FORMAT));
		}  
		return array('start_date'=>$start_date, 'end_date'=>$end_date, 'is_default_dates' => $is_default_dates);		
	}
	
	/** Pridobimo seznam vseh list uporabnika
	 *  v obliki arraya
	 */
	static function getProfiles() {
		return self::$profiles;
	}

	/** Ponastavi id privzetega profila
	 * 
	 */
	static function SetDefaultProfile($pid) {
		self::$currentProfileId = $pid;
		$saved = SurveyUserSetting :: getInstance()->saveSettings('default_statistic_profile',$pid);
		
	}
	/** 
	 * 
	 */
	static function RunStatisticProfile($pid,$timeline,$startDate,$endDate,$stat_interval, $asSession) {
		if ($pid == 0 && ($asSession == false || $asSession == 'false' )) {
			# imamo privzet profil
			self :: SetDefaultProfile(0);

		} else if ($pid > 0 && ($asSession == false || $asSession == 'false' )) {
			# shranimo v bazo
			//sisplet_query("UPDATE srv_statistic_profile SET timeline,startDate,endDate,stat_interval  WHERE id = '".$pid."'");
			if ($timeline == 'true') {
				# shranjujemo od - do
				$stat_interval = '';
				$update = "UPDATE srv_statistic_profile SET starts = '".$startDate."', ends='".$endDate."', interval_txt = '' WHERE id = '".$pid."'";
			} else {
				# shranjujemo interval
				$startDate = '';
				$endDate = '';
				$update = "UPDATE srv_statistic_profile SET starts = '0000-00-00 00:00:00', ends='0000-00-00 00:00:00', interval_txt = '".$stat_interval."' WHERE id = '".$pid."'";
			}
			
			$updated = sisplet_query($update);
			# ce je bili updejt ok  posodobimo se vrednost v profilu
			if ($updated) {
				self::$profiles[$pid]['starts'] = $startDate;
				self::$profiles[$pid]['ends'] = $endDate;
				self::$profiles[$pid]['interval_txt'] = $stat_interval;
			}
			
			# nastavimo privzet profil na trenutnega
			self :: SetDefaultProfile($pid);
			
		} else {
			# shranjujenmo v sejo
			if ($timeline == 'true') {
				# shranjujemo od - do
				$stat_interval = '';
			} else {
				# shranjujemo interval
				$startDate = '';
				$endDate = '';
			}
			
			if ($timeline == 'true') {
				self::$profiles[$pid]['starts'] = $startDate;
				self::$profiles[$pid]['ends'] = $endDate;
			} else {
				self::$profiles[$pid]['interval_txt'] = $stat_interval;
			} 
			
			$_SESSION['statistic_profile'][self::$surveyId] = array('id'=>'-1',
				  	'name'=>$lang['srv_temp_profile'],
					'starts'=>$startDate,
					'ends'=>$endDate,
					'interval_txt'=>$stat_interval);
	
			self :: SetDefaultProfile(-1);
				
		}
		return $updated;
	}

	static function RenameProfile($pid, $name) {

		if (isset($pid) && $pid > 0 && isset($name) && trim($name) != "") {
			// popravimo podatek za variables 
			$stringUpdate = "UPDATE srv_statistic_profile SET name = '".$name."' WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
			return $updated;
		} else {
			return -1;
		}
	}
	 	
	static function DeleteProfile($pid = 0) {
		self :: SetDefaultProfile('0');
		if (isset($pid) && $pid == -1) {
			unset($_SESSION['statistic_profile'][self::$surveyId] );
		} else  if (isset($pid) && $pid > 0) {
			// Izbrišemo profil in nastavimo privzetega 
			$stringUpdate = "DELETE FROM srv_statistic_profile WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
		}
	}

	/** Funkcija kreira nov profil
	 *  
	 */
	function createStatisticProfile($timeline,$startDate,$endDate,$stat_interval,$name=null) {
		global $lang;
		if ($name == null || trim($name) == '' ) {
			$name = $lang['srv_new_profile'];
		}

		if ($timeline == 'true') {
			# shranjujemo od - do
			$startDate = date(SS_OUTPUT_DATE_FORMAT, strtotime($startDate));
			$endDate = date(SS_OUTPUT_DATE_FORMAT, strtotime($endDate));
			$stat_interval = '';
		} else {
			# shranjujemo interval
			$startDate = '0000-00-00';
			$endDate = '0000-00-00';
		}

		$iStr = "INSERT INTO srv_statistic_profile (id,uid,name,starts,ends,interval_txt)".
		" VALUES (NULL, '".self::$uId."', '".$name."', '".$startDate."', '".$endDate."', '".$stat_interval."')";
		
		$ins = sisplet_query($iStr);
		$id = mysqli_insert_id($GLOBALS['connect_db']);
		
		if ($id > 0) {
			self :: SetDefaultProfile($id);
		} else {
			self :: SetDefaultProfile(0);
		}

		return;
	}
	
	/** Funkcija prikaze izbor datuma
	 *  
	 */
	function displayDateFilters($current_pid = null) {
		global $lang;
        $_all_profiles = SurveyStatisticProfiles::getProfiles();


        if ($current_pid == null) {
        	$current_pid = SurveyStatisticProfiles::getCurentProfileId();
        }
		echo '<div class="statistic_profile_left_right floatLeft">'."\n";
       	echo '<div class="statistic_profile_holder">'."\n";
		# zlistamo vse profile
		echo '<div id="statistic_profile" class="select">'."\n";
		if (count($_all_profiles)) {
			foreach ($_all_profiles as $id=>$profile) {
				echo '<div class="option' . ($current_pid == $id ? ' active' : '') . '" id="statistic_profile_' . $id . '">' . $profile['name'] .'</div>'."\n";	
			}
		}
		echo '	</div>'."\n"; // statistic_profile
		echo '</div>'."\n"; //statistic_profile_holder
		echo '<br class="clr" />';
		# privzetega profila ne moremo ne zbrisat ne preimenovat
        echo '<div class="statistic_profile_button_left_holder link_no_decoration">'."\n";
        if ($current_pid > 0) {
        	echo '<a href="#" onclick="showHideRenameStatisticProfile(\'true\'); return false;">'.$lang['srv_rename_profile'].'</a><br/>'."\n";
        }
        if ($current_pid != 0) {
			echo '<a href="#" onclick="showHideDeleteStatisticProfile(\'true\'); return false;">'.$lang['srv_delete_profile'].'</a>'."\n";
		}
        echo '</div>'."\n"; // statistic_profile_button_left_holder

		echo '</div>'."\n"; //statistic_profile_left

		echo '<div class="statistic_profile_left_right floatRight">'."\n";
		echo '<div id="statistic_profile_content">';
		self::DisplayProfileData($current_pid);
		echo '</div>'; // statistic_profile_content
		echo '<br class="clr" />'."\n";
		if ($current_pid == 0) {
			echo '<div class="statistic_profile_note">';
			echo $lang['srv_change_default_profile'];
			echo '</div>'; // statistic_profile_note
		}	
		echo '<br class="clr" />';
		echo '<div class="statistic_profile_button_right_holder floatRight">'."\n";
		if ($current_pid == 0) {
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="run_statistic_interval_filter(\'false\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="showHideCreateStatisticProfile(\'true\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="close_statistic_interval_filter(); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
		} else if ($current_pid == -1) {
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="run_statistic_interval_filter(\'true\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="showHideCreateStatisticProfile(\'true\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="close_statistic_interval_filter(); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
		} else  {
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="run_statistic_interval_filter(\'false\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
//			echo '<span class="floatRight spaceRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="run_statistic_interval_filter(\'true\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="showHideCreateStatisticProfile(\'true\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="close_statistic_interval_filter(); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
			
		}
		echo '</div>'."\n"; // statistic_profile_button_right_holder
		echo '</div>'; // statistic_profile_right
		// cover Div
        echo '<div id="statisticProfileCoverDiv"></div>'."\n";
		
        // div za kreacijo novega
        echo '<div id="newProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="newProfileName" name="newProfileName" type="text" value="" size="45"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="create_new_statistic_interval_filter(); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="showHideCreateStatisticProfile(\'false\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
        
        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . $currentFilterProfile['name'] . '" size="45"  />'."\n";
        echo '<input id="renameProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="renameStatisticProfile(); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="showHideRenameStatisticProfile(\'false\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
                
        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . $currentFilterProfile['name'] . '</b>?'."\n";
        echo '<input id="deleteProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="deleteStatisticProfile(); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="showHideDeleteStatisticProfile(\'false\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
		
				
	}
	/** Funkcija prikaze osnovnih informacije profila
	 * 
	 */
	function DisplayProfileData($current_pid) {
		global $lang;
		# podatki profila
		$p_data = SurveyStatisticProfiles::GetProfileData($current_pid);

		#kateri nacin imamo ali timeline (od - do) ali inervalsko (zadnjih....)
		# ce je podan string interval imamo intervalno
		if ( $p_data['interval_txt'] != null || trim($p_data['interval_txt'] != '')) {
			$timeline = false;
			$time = $p_data['interval_txt'];
			$p_data['starts'] = date(SS_DATE_FORMAT,strtotime(SurveyInfo::getInstance()->getSurveyInsertDate()));
			$p_data['ends'] = date(SS_DATE_FORMAT);
		} else {
			$timeline = true;
			$time = '';
		}
		
		echo '<input type="radio" name="timeline" id="statistic_date_timeline" value="true" '.($timeline ? ' checked="checked"' : '').($current_pid == 0 ? ' disabled="disabled"':'').' autocomplete="off"><label> ' . $lang['srv_statistic_from'] . ': </label>'."\n";
		echo '<input id="startDate" type="text" name="startDate" value="' . $p_data['starts'] . '" onclick="changeStatisticDate();" readonly="true" '.($current_pid == 0 ? ' disabled="disabled"':'').' autocomplete="off"/>&nbsp;';
		echo '<span class="faicon calendar_icon icon-as_link" id="starts_img"></span>' . "\n";
		echo '<label> ' . $lang['srv_statistic_to'] . ': </label>'."\n";
		echo '<input id="endDate" type="text" name="endDate" value="' . $p_data['ends'] . '" onclick="changeStatisticDate();" readonly="true" '.($current_pid == 0 ? ' disabled="disabled"':'').'cautocomplete="off"/>&nbsp;';
		echo '<span class="faicon calendar_icon icon-as_link" id="expire_img"></span>' . "\n" ;
		echo '<br />';
		echo '<p><input type="radio"  name="timeline" id="statistic_date_interval" value="false" '.($timeline ? '' : ' checked="checked"').($current_pid == 0 ? ' disabled="disabled"':'').' autocomplete="off">'.$lang['srv_statistic_period_label'].':';
		echo '<select name="stat_interval" id="stat_interval" onclick="changeStatisticDate(\'interval\');" '.($current_pid == 0 ? ' disabled="disabled"':'').'autocomplete="off">';
		echo '<option value="" selected="true">'.$lang['srv_statistic_choose_interval'].'</option>';
		foreach (self::$SS_ARRAYS as $INTERVAL) {
			echo '<option value="'.$INTERVAL.'"' . ($time == $INTERVAL ? ' selected' : '') . '>'.$lang['srv_diagnostics_'.$INTERVAL].'</option>';
		}
			echo '</select>' . "\n";
		echo '</p>' . "\n";
		
		echo '<script type="text/javascript">' . "\n";
		# za profil id=0 (privzet profil ne pustimo spreminjat
		if ($current_pid != 0 ) {
			echo 
			'    Calendar.setup({' . "\n" .
			'        inputField  : "startDate",' . "\n" .
			'        ifFormat    : "'.SS_CALENDAR_DATE_FORMAT.'",' . "\n" .
			'        button      : "starts_img",' . "\n" .
			'        singleClick : true,' . "\n" .
			'        onUpdate    : changeStatisticDate' . "\n\r" .
			'    });' . "\n" .
			'    Calendar.setup({' . "\n" .
			'        inputField  : "endDate",' . "\n" .
			'        ifFormat    : "'.SS_CALENDAR_DATE_FORMAT.'",' . "\n" .
			'        button      : "expire_img",' . "\n" .
			'        singleClick : true,' . "\n" .
			'        onUpdate    : changeStatisticDate' . "\n\r" .
			'    })' . "\n";
		}
		echo '</script>' . "\n";

	}
}
?>
