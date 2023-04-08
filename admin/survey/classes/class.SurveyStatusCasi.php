<?php

class SurveyStatusCasi
{
	static private $sid = null;					# id ankete
	static private $uid = null;					# id userja
	static private $inited = false;				# ali so profili ze inicializirani
	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika
	
																		// lurker je mal poseben, ker je neodvisen od ostalih (user je npr. 6 in lurker)
	static private $allStatus = array('null',0,1,2,3,4,5,6,'lurker'); 			// Statusi anket katere štejemo kot ustrezne
	static private $appropriateStatus = array(6,5); 					// Statusi anket katere štejemo kot ustrezne
	static private $unAppropriateStatus = array(4,3,2,1,0); 			// Statusi anket katere štejemo kot neustrezne
	static private $unKnownStatus = array('null'); 						// Statusi anket katere štejemo kot neustrezne
	
	static function Init($sid, $uid = null) {
		# nastavimo surveyId
		self::setSId($sid);
		
		# nastavimo userja
		self::setGlobalUserId($uid);
		SurveyUserSetting :: getInstance()->Init(self::$sid, self::getGlobalUserId());
		if (self::$inited == false) {
			self::$inited = self :: RefreshData();
		}
	}
	
	static function RefreshData() {
		# preberemo podatke vseh porfilov ki so na voljo in jih dodamo v array
		$stringSelect = "SELECT * FROM srv_status_casi WHERE uid = '".self::getGlobalUserId()."' OR (uid = '0' AND `system` =1) ORDER BY id";
		$querySelect = sisplet_query($stringSelect);

		while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = $rowSelect;
		}
		# poiscemo privzet profil
		self::$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_status_casi');
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
		if ($pid == null ) {
			$pid = self::$currentProfileId;
		}
		
		echo '<div id="status_profile_holder">';
		self :: DisplayProfileOptions($pid);
		echo '</div>';
		echo '<div id="status_profile_data_holder">';
		echo '<div>';
		self :: DisplayProfileData($pid);
		echo '</div>';
		echo '<br><div class="floatRight">';

		
		# shrani - pozeni
		$run_lbl = ( $pid == 1 ) ? $lang['srv_run_profile'] : $lang['srv_save_run_profile'];
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="statusCasiAction(\'run\'); return false;"><span>'.$run_lbl.'</span></a></span></span>';

		# preklici
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="statusCasiAction(\'cancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';		
		# shrani kot nov profil
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="statusCasiAction(\'newName\'); return false;"><span>'.$lang['srv_save_new_profile'].'</span></a></span></span>';		
		echo '</div>';
		echo '</div>';
        
		// cover Div
        echo '<div id="statusProfileCoverDiv"></div>'."\n";
        
        // div za shranjevanje novega profila
        echo '<div id="newProfile">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="statusCasiAction(\'newSave\'); return false;"><span>'.$lang['srv_save_profile'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="showHideNewMissingProfile(\'false\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
/*
        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . self::$profiles[$pid]['name'] . '" size="45"  />'."\n";
        echo '<input id="renameProfileId" type="hidden" value="' . $pid . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="(\'deleteCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="(); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
*/
        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . self::$profiles[$pid]['name'] . '</b>?'."\n";
        echo '<input id="deleteProfileId" type="hidden" value="' . $pid . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="statusCasiAction(\'deleteCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="statusCasiAction(\'deleteConfirm\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
        
	}

	static function DisplayProfileData($pid) {
		global $lang;
		$curentProfileData = self :: $profiles[$pid]; 
//		echo '<div id="status_profile_notes" >help?'.'</div>';
		echo '<div id="statusProfileFieldsetHolder" >';
		echo '<fieldset id="missingProfileFieldset">'."\n";
		echo '<legend>' . $lang['srv_missing_profile_title4'] . '</legend>'."\n";
		echo '<form name="" id="" autocomplete="off">'."\n";

		$cnt = 1;
		echo '<table><tr>';
		// dodamo veljavne
		foreach (self::$appropriateStatus as $index) {
			if ($cnt&1) 
				echo '</tr><tr>';
			echo '<td style="width:50%">'."\n";
			echo '<label><input name="srv_userstatus[]" type="checkbox" id="' . $index . '"' .
			 ($curentProfileData['status'.$index] == 1 ? ' checked="checked"' : '') . '/>'."\n";
			echo $lang['srv_userstatus_' . $index]. " (".$index.")";
			echo '</label></td>'."\n";
			$cnt++;
		}
		// dodamo neveljavne
		foreach (self::$unAppropriateStatus as $index) {
			if ($cnt&1) 
				echo '</tr><tr>';
			echo '<td style="width:50%">'."\n";
			echo '<label><input name="srv_userstatus[]" type="checkbox" id="' . $index . '"' .
			 ($curentProfileData['status'.$index] == 1 ? ' checked="checked"' : '') . '/>'."\n";
			echo $lang['srv_userstatus_' . $index]. " (".$index.")";
			echo '</label></td>'."\n";
			$cnt++;
		}
		// dodamo null
		foreach (self::$unKnownStatus as $index) {

			if ($cnt&1) 
				echo '</tr><tr>';
			echo '<td style="width:50%">'."\n";
			echo '<label><input name="srv_userstatus[]" type="checkbox" id="' . $index . '"' .
			 ($curentProfileData['status'.$index] == 1 ? ' checked="checked"' : '') . '/>'."\n";
			echo $lang['srv_userstatus_' . $index]. " (".$index.")";
			echo '</label></td>'."\n";
			$cnt++;
		}
		
		echo '</tr></table>';
		
		// lurkerji
		echo '<hr><label><input type="checkbox" name="srv_userstatus[]" id="lurker" '.($curentProfileData['statuslurker'] == 1 ? ' checked="checked"' : '') . '> '.$lang['srv_lurkers'].'</label>';
		
		echo '<div class="clr"></div>'."\n";
		echo '</form>'."\n";
		echo '</fieldset>'."\n";
		echo '</div>'."\n";
				
	}
	
	static function DisplayProfileOptions($pid) {
		global $lang;
		echo '<div id="status_casi" class="select">';
		foreach ( self::$profiles as $key => $profile ) {
			echo '<div id="status_profile_'.$profile['id'].'" class="option' . ($profile['id'] == $pid ? ' active' : '') . '" value="' . $profile['id'] . '">' . $profile['name'] . '</div>';       
		}
		echo '</div>';
		echo '<div id="status_profile_links" class="link_no_decoration">';
		if ($pid != 1)
			echo '<a href="#" onclick="statusCasiAction(\'deleteAsk\'); return false;">'.$lang['srv_delete_profile'].'</a><br/>'."\n";		
		echo '</div>';
		echo '<script>';
		echo '$(function() {';
		echo 'scrollToProfile("#status_profile_'.$pid.'");';
		echo '});';
		echo '</script>';
		
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
		return self::$currentProfileId;
	}
	/**
	 * 
	 * @param unknown_type $pid
	 */
	static function setCurentProfileId($pid) {
		if ($pid < 1)
			$pid = 1;
		return self::$currentProfileId = $pid;
	}

	static function setDefaultProfileId($pid) {
		if (!$pid)
			$pid = 1;

		SurveyUserSetting :: getInstance()->saveSettings('default_status_casi', $pid);
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
			if (isset($mpds['statuslurker']) && $mpds['statuslurker'] == 1)
				$result['lurker'] = 'lurker';
		}

		return $result;
	}
	static function getStatusArray($pid) {
		$mpd =  self::$profiles[$pid];
		return $mpd;
	}
	
	/** Shranimo v obstoječ profil
	 * 
	 * @param unknown_type $pid
	 * @param unknown_type $name
	 * @param unknown_type $status
	 */
	static function saveProfile($pid,$status) {
		$insert_id = 0;
		if (isset($pid) && $pid != null && isset($status) && $status != null) {
			if ($pid == 1) { # ce mamo privzet profil ga ne shranjujemo
				return 1;
			}
			# imamo podatke, updejtamo profil v bazi

			$statusi = explode(',',$status);
			if (count(self::$allStatus) > 0 ) {
				
				$updateString = "UPDATE srv_status_casi SET ";
				$prefix = '';
				foreach (self::$allStatus as $_status) {
					$updateString .= $prefix . 'status'.$_status. ' = '.(in_array((string)$_status,$statusi) ? '1' : '0');				
					$prefix =', ';
				}
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
	static function saveNewProfile($pid,$name,$status) {
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
			$insertString = "INSERT INTO srv_status_casi (uid,name,system".$str_lbl.") VALUES ('".self::getGlobalUserId()."', '".$name."', 0".$str_vle.")";
			$queryInsert = sisplet_query($insertString) 
				or die(mysqli_error($GLOBALS['connect_db']));
			$insert_id = mysqli_insert_id($GLOBALS['connect_db']);	
		}
		return $insert_id;
	}
	
	static function Delete($pid) {
		if ($pid != 1) {
			$sqlDelete = sisplet_query("DELETE FROM srv_status_casi WHERE id = '$pid' AND `system` != '1'");
			print_r("DELETE FROM srv_status_casi WHERE id = '$pid' AND `system` != '1'");		
		} 
	}
	
}

?>