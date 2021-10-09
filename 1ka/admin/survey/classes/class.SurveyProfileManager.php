<?php
/** @author: Gorazd Veselič
 *
 * 	@Desc: za upravljanje s profili
 *
 *
 *  @Date: 10.12.2012
 */

session_start();

DEFINE ('SPM_DEFAULT_PROFILE', -1);
class SurveyProfileManager {
	private $uid = null;			# id userja
	private $sid = null;			# id ankete

	private $currentProfileId = SPM_DEFAULT_PROFILE; 	# id trenutno izbranega profila
	private $ssp = null;
	private $svp = null;
	private $scp = null;
	private $stp = null;

	private $profileVariables = array(0=>'ssp',1=>'svp',2=>'scp',3=>'stp');

	private $profiles = array();


	function __construct($anketa = null) {
		if ((int)$anketa > 0) {
			$this->sid = $anketa;
		} else {
			$this->sid = $_REQUEST['anketa'];
		}

		global $global_user_id;
		$this->uid = $global_user_id;;

		#polovimo privzete profile
		$this->ssp = new SurveyStatusProfiles;
		$this->ssp -> Init($this->sid);
		$this->svp = new SurveyVariablesProfiles();
		$this->svp -> Init($this->sid, $this->uid, true);
		$this->scp = new SurveyConditionProfiles();
		$this->scp -> Init($this->sid, $this->uid);
		$this->stp = new SurveyTimeProfiles();
		$this->stp -> Init($this->sid, $this->uid);

		SurveyUserSetting :: getInstance()->Init($this->sid, $this->uid);
		$this -> currentProfileId = (int)SurveyUserSetting :: getInstance()->getSettings('default_profileManager_pid');

		$this->getProfiles();
	}

	function getCurentProfileId(){
		return $this->currentProfileId;
	}
	
	function ajax() {
		if (isset($_REQUEST['m']) && trim($_REQUEST['m']) != '') {
			$this->action($_REQUEST['m']);
		} else {
			echo 'Ajax error!';
			return 'Ajax error!';
		}
	}

	function action($action) {
		if ($action == 'displayProfiles') {
			$this->DisplayProfiles();
		} else if ($action == 'save') {
			$this->SaveProfile();
		} else if ($action == 'saveNew') {
			$_POST['pm_name'] = $_POST['newName'];
			$_POST['asNew'] = 'true';
			$this->SaveProfile();
		} else if ($action == 'changeProfile') {
			$this->ChangeProfile();
		} else if ($action == 'choose') {
			$this->ChooseProfile();
		} else if ($action == 'delete') {
			$this->DeleteProfile();
		} else {
			echo $_REQUEST;
		}
	}

	function getProfiles() {
		global $lang;
		
		$this->profiles = array();
		# privzet profil
		$this->profiles[SPM_DEFAULT_PROFILE] = array (
				'id' => SPM_DEFAULT_PROFILE,
				'name' => $lang['srv_profileManager_profileName_without'],
				'comment' => $lang['srv_profileManager_profileComment_system'],
				'ssp' => (int)SurveyStatusProfiles :: getSystemDefaultProfile(),
				'svp' => SurveyVariablesProfiles :: getSystemDefaultProfile(),
				'scp' => SurveyConditionProfiles :: getSystemDefaultProfile(),
				'stp' =>SurveyTimeProfiles :: getSystemDefaultProfile()
		);

		# trenutne nastavitve
		$this->profiles[0] = array (
				'id' => 0,
				'name' => $lang['srv_profileManager_profileName_current'],
				'comment' => $lang['srv_profileManager_profileComment_system'],
				'ssp' => SurveyStatusProfiles :: getCurentProfileId(),
				'svp' => SurveyVariablesProfiles :: getCurentProfileId(),
				'scp' => SurveyConditionProfiles :: getCurentProfileId(),
				'stp' =>SurveyTimeProfiles :: getCurentProfileId()
		);

		# polovimo iz baze
		$str = "SELECT id, name, comment, ssp, svp, scp, stp FROM srv_profile_manager WHERE ank_id = '$this->sid'";
		$qry = sisplet_query($str);
		if (mysqli_num_rows($qry) > 0) {
			while (list($id,$name,$comment,$ssp,$svp,$scp,$stp) = mysqli_fetch_row($qry)) {
				# preverimo še obstoj posameznega profila
				$updateProfile=false;
				if (!$this->ssp->checkProfileExist((int)$ssp)) 
				{
					$ssp = (int)SurveyStatusProfiles::getSystemDefaultProfile();
					$updateProfile=true;
				}
				if (!$this->svp->checkProfileExist((int)$svp)) 
				{
					$svp = (int)SurveyVariablesProfiles::getSystemDefaultProfile();
					$updateProfile=true;
				}
				if (!$this->scp->checkProfileExist((int)$scp)) 
				{
					$scp = (int)SurveyConditionProfiles::getSystemDefaultProfile();
					$updateProfile=true;
				}
				if (!$this->stp->checkProfileExist((int)$stp)) 
				{
					$stp = (int)SurveyTimeProfiles::getSystemDefaultProfile();
					$updateProfile=true;
				}
				$this->profiles[$id] = array (
						'id' => $id,
						'name' => $name,
						'comment' => $comment,
						'ssp' => (int)$ssp,
						'svp' => (int)$svp,
						'scp' => (int)$scp, 
						'stp' => (int)$stp
				);
				#po potrebi updejtamo profil
				if($updateProfile == true) {
					$this->updateProfiles($id);
				}
			}
		}

		return $this->profiles;
	}

	function DisplayLink($hideAdvanced = true, $hideSeperator = false) {
		global $lang;
		$css = (
				(int)$this->currentProfileId == SPM_DEFAULT_PROFILE
				||  (int)$this->currentProfileId == 0
				? ' gray' 
				: '');
		if ($hideAdvanced == false || $izbranStatusProfile != SSP_DEFAULT_PROFILE) {
			if ($hideSeperator == false) {
				echo '<li class="space">&nbsp;</li>';
			}
			echo '<li>';
			echo '<span class="as_link'.$css.'" onclick="profileManager_displayProfiles(); return false;" title="' . $lang['srv_profileManager_link'] . '">' . $lang['srv_profileManager_link'] . '</span>';
			echo '</li>';

		}
	}

	function DisplayProfiles($pid = null) {
		global $lang;

		if ($pid === null) {
			$pid = $this -> currentProfileId;
		}

		$popUp = new PopUp();
		$popUp->setId('divProfileManager');
		$popUp->setHeaderText($lang['srv_profileManager_div_header']);

		#vsebino shranimo v buffer
		ob_start();
		echo '<form id="profileManager_form">';
		echo '<div class="floatLeft spaceRight" style="height:100%;">';
		echo '<div id="profileManager_holder">';
		echo '<div id="profileManager_profile" class="select">';
		foreach ($this->profiles as $key => $value) {
			echo '<div class="option' . ( $pid == $value['id'] ? ' active' : '') . '" id="profileManager_profile_' . $value['id'] . '" value="'.$value['id'].'">';
			
			echo $value['name'];
		
			if($pid > 0 && $pid == $value['id']){
				echo ' <a href="#" onclick="profileManager_delete(\''.$pid.'\'); return false;" value="'.$lang['srv_delete_profile'].'"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'."\n";
			}
			
			echo '</div>';
		}
		echo '</div>';

		echo '</div>';
		echo '</div>';
		$this->DisplayProfileData($pid);
		echo '</form>'; # profileManager_form

		// cover Div
		echo '<div id="profileManagerCoverDiv"></div>';
		
		// div za shranjevanje novega profila
		echo '<div id="newProfile">'.$lang['srv_missing_profile_name'].': ';
		
		echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />';
		
		$button = new PopUpButton($lang['srv_save_profile']);
		echo $button -> setFloat('right')
			->setButtonColor('orange')
			-> addAction('onClick','profileManager_saveNew(\''.$pid.'\'); return false;');
			
		$button = new PopUpButton($lang['srv_cancel']);
		echo $button -> setFloat('right')
			-> addAction('onClick','$(\'#newProfile\').hide(); $(\'#profileManagerCoverDiv\').fadeOut(); return false;');
			
		echo '</div>';
		
		
		$content = ob_get_clean();
		
		#dodamo vsebino
		$popUp->setContent($content);
	
		#dodamo gumb izberi profil
		$button = new PopUpButton($lang['srv_choose_profile']);
		$button -> setFloat('right')
				-> setButtonColor('orange')
				-> addAction('onClick','profileManager_choose(\''.$pid.'\'); return false;');
		$popUp->addButton($button);
		
		
		#dodamo gumb nov profil
		$button = new PopUpButton($lang['srv_new_profile_name']);
		$button -> setFloat('right')
				-> addAction('onClick','profileManager_newName(\''.$pid.'\'); return false;');
		
		$popUp->addButton($button);

		if ($pid > 0) {
			
			# dodamo gumb shrani
			$button = new PopUpButton($lang['srv_save_profile']);
			$button -> setFloat('right')
					-> addAction('onClick','profileManager_save(\''.$pid.'\',\'false\'); return false;');
			$popUp->addButton($button);
			
			# dodamo gumb izbriši
			/*$button = new PopUpButton($lang['srv_delete_profile']);
			$button -> setFloat('right')
					-> addAction('onClick','profileManager_delete(\''.$pid.'\'); return false;');
			$popUp->addButton($button);*/
		}
		
		# dodamo gumb Prekliči
		$button = new PopUpCancelButton();
		$button -> setFloat('right');
		$popUp->addButton($button);
		
		echo $popUp;
	}

	function getPidData($pid = null) {
		if ($pid === null) {
			$pid = $this -> currentProfileId;
		}

		$result = $this->profiles[$pid];
		# pridobimo imena profilov
		foreach ($this->profileVariables AS $pvKey => $profileVariable) {
			$sub_pid = $this->profiles[$pid][$profileVariable];
			
			$subName = $this->{$profileVariable}->getProfileName($sub_pid);
			$result[$profileVariable] = array('id'=>$sub_pid, 'name'=>$subName);
		}

		return $result;
	}

	function SaveProfile() {
		global $lang;
		$pid = (int)$_POST['pid'];
		$name = isset($_POST['pm_name']) && trim($_POST['pm_name']) != '' ? $_POST['pm_name'] : $lang['srv_profileManager_profileName_new'];

		$comment = $_POST['pm_comment'];
		$asNew = $_POST['asNew'] == 'true' ? true : false;

		$data_fields = array();
		$data_variables = array();
		foreach ($this->profileVariables AS $pvKey => $profileVariable) {
			$data[] = $_POST['pm_profile_'.$profileVariable];
			if (isset($_POST['pm_profile_'.$profileVariable])) {
				$data_fields[] = $profileVariable;
				$data_variables[] = $_POST['pm_profile_'.$profileVariable];
			}
		}

		# ali updejtamo obstoječ profil
		if ($asNew == false) {
			# preverimo ali profil s tem ID-jem že obstaja ali ga samo shranmimo
			$str = "SELECT count(*) FROM srv_profile_manager WHERE id = '$pid'";
		$qry = sisplet_query($str);
		list($count) = mysqli_fetch_row($qry);
		if ($count > 0) {
			# popravimo star profil
			$strUpdate = "UPDATE srv_profile_manager SET name='$name', comment='$comment' WHERE id='$pid'";
			$qryUpdate = sisplet_query($strUpdate);
			sisplet_query('COMMIT');
			$this->profiles = $this->getProfiles();
			$this->DisplayProfiles($pid);
			return;
		}
		}

		# v nov profil
		if (count($data_fields) > 0 && count($data_variables) > 0) {
			$str = "INSERT INTO srv_profile_manager (id,ank_id,name,comment,"
			.implode(',',$data_fields)
			.") VALUES (NULL,'$this->sid','$name','$comment',"
			.implode(',',$data_variables)
			. ")";
			$qry = sisplet_query($str);
			$new_id = mysqli_insert_id($GLOBALS['connect_db']);
			sisplet_query('COMMIT');
			$this->profiles = $this->getProfiles();
			$this->DisplayProfiles($new_id);
		}
	}
	
	/** updejtamo izbrane podprofile pri posameznem glevnem profilu
	 * 
	 * @param (int) $pid
	 */
	function updateProfiles($pid) {
		# popravimo star profil
		$strUpdate = "UPDATE srv_profile_manager SET"
		. " ssp = '".$this->profiles[$pid]['ssp']."'"
		. ", svp = '".$this->profiles[$pid]['svp']."'"
		. ", scp = '".$this->profiles[$pid]['scp']."'"
		. ", stp = '".$this->profiles[$pid]['stp']."'"
		. " WHERE id='$pid'";
		$qryUpdate = sisplet_query($strUpdate);
		sisplet_query('COMMIT');
	}

	function ChangeProfile() {
		$pid = (int)$_REQUEST['pid'];
		$this->DisplayProfiles($pid);
	}

	function DisplayProfileData($pid=null) {
		global $lang;

		if ($pid === null) {
			$pid = $this -> currentProfileId;
		}

		echo '<div style="background-color:#EFF2F7; padding: 5px; float:right; height:160px;">';
		
		echo '<div class="floatRight">';
		$pidData = $this->getPidData($pid);
		echo '<table id="tbl_profileManager_pdofileData">';
		echo '<tr>';
		echo '<th>'.$lang['srv_profileManager_profileChoosen'].'</th>';
		echo '<th>'.$lang['srv_profileManager_profileChoosen_name'].'</th>';
		echo '</tr>';
		foreach ($this->profileVariables AS $pvKey => $profileVariable) {
			$pvProfile = $pidData[$profileVariable];
			echo '<tr>';
			echo '<td>'.$lang['srv_profileManager_profileName_'.$profileVariable].'</td>';
			echo '<td><input type="hidden" name="pm_profile_'.$profileVariable.'" value="'.$pvProfile['id'].'">'.$pvProfile['name'].'</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '</div>';

		echo '<div class="floatRight spaceRight">';
		echo '<table><tr>';
		echo '<td style="vertical-align:top;"><label>'.$lang['srv_profileManager_name'].'</label></td>';
		if (trim($pidData['name']) == '') {
			$placeholder = ' placeholder="'.$lang['srv_profileManager_profileName_new'].'"';
		}
		if ($pid <= 0) {
			$disabled = ' disabled="disabled"';
		}
		echo '<td style="vertical-align:top;">';
		echo '<input type="text" style="width:250px;" name="pm_name"'.$placeholder.$disabled.' value="'.$pidData['name'].'"/>';
		echo '</td>';
		echo '</tr><tr>';
		echo '<td style="vertical-align:top;"><label>'.$lang['srv_profileManager_comment'].'</label></td>';
		echo '<td style="vertical-align:top;"><textarea name="pm_comment"'.$disabled.' style="width:250px; height:80px;">'.$pidData['comment'].'</textarea></td>';
		echo '</tr></table>';
		echo '</div>';
	
		echo '</div>';
	}

	function DeleteProfile() {
		$pid = (int)$_REQUEST['pid'];
		if ($pid > 0) {
			# popravimo star profil
			$strUpdate = "DELETE FROM srv_profile_manager WHERE id='$pid' AND ank_id='$this->sid'";
			$qryUpdate = sisplet_query($strUpdate);
			sisplet_query('COMMIT');
			$this->profiles = $this->getProfiles();
		}
		$this->DisplayProfiles((int)SPM_DEFAULT_PROFILE);
	}

	function ChooseProfile() {
		$pid = (int)$_REQUEST['pid'];

		#zloopamo skozi profile in ponastavimo nastavitve
		foreach ($this->profileVariables AS $pvKey => $profileVariable) {
			$sub_pid = $this->profiles[$pid][$profileVariable];
			$this->{$profileVariable}->setDefaultProfileId($sub_pid);
		}
		SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', $pid);
	}
}
?>