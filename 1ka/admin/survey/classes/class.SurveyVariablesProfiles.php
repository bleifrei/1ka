<?php
/**
 * Created on 10.04.2013
 *
 * @author: Gorazd Veselič
 *
 * @desc: za shranjevanje in nalaganje profilov variabel za posamezno anketo
 *
 * Profil -1 je rezerviran za sejo
 * Profil 0 je rezerviran za privzet profil - Vse vrednosti
 *
 */
DEFINE ('SVP_DEFAULT_PROFILE', 0);
DEFINE (NEW_LINE, "\n");

class SurveyVariablesProfiles
{
	static private $sid = null; 						# id ankete
	static private $availableProfiles = array(); 		# array kamor shranimo vse doseglijive  profile
	static private $profiles = array(); 				# array kamor shranimo podatke o trenutnem profilu
	static private $currentId = SVP_DEFAULT_PROFILE;	# trenutno izbran profil
	static private $SDF = null;							# trenutno izbran profil
	
	
	/** Inicializacija
	 * $uid=null še je posledica starega classa Je potrebno povsod zamenjat Init funkcijo
	*/
	static function Init($sid) {
		global $global_user_id;
		
		self::$sid = $sid;

		#inicializiramo class za datoteke
		self::$SDF = SurveyDataFile::get_instance();
		self::$SDF->init($sid);
		
		#polovimo vse profile
		self::getProfiles();
		
		#inicaliziramo nastavitve uporabnika
		SurveyUserSetting :: getInstance()->Init($sid, $global_user_id);
		self::$currentId = self:: getCurentProfileId();
	}

	static function setCurrentProfileId($pid)
	{
		if (isset(self::$profiles[$pid])){
			self::$currentId = $pid;
		}
		else{
			self::$currentId = SVP_DEFAULT_PROFILE;
		}
	} 
	
	static function getProfiles() {
		global $lang;
		
		# če imamo sejo preberemo iz seje
		if ( isset($_SESSION['variables_profile'][self::$sid])) 
		{
			self::$profiles[-1] = $_SESSION['variables_profile'][self::$sid];
		}
		#dodamo profil vse variable
		$svp_av = self::$SDF->getSurveyVariables();
		$all_variables = array();
		if (count($svp_av) > 0)
		{
			foreach($svp_av AS $v_id => $seq) 
			{
				$all_variables[] = $v_id;
			}
		}
		$variables = serialize($all_variables);
		self::$profiles[SVP_DEFAULT_PROFILE] = array('id'=>SVP_DEFAULT_PROFILE, 'name'=>$lang['srv_all_vars'].' *', 'variables'=>$variables);

		# preberemo še profile iz baze
		$stringSelect = "SELECT id, name, variables FROM srv_variable_profiles WHERE sid = '".self::$sid."'";
		$sqlQuery = sisplet_query($stringSelect);
		while (list($id,$name,$variables) = mysqli_fetch_row($sqlQuery))
		{
			self::$profiles[$id] = array('id'=>$id,
										'name'=>$name,
										'variables'=>$variables);
		}
	}

	/** Trenutno izbran profil
	 *
	 */
	static function getCurentProfileId()	{
		
		# poiscemo privzet profil
		$_dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		self::$currentId = $_dvp;
		
		if ($_dvp == null || !isset(self::$profiles[self::$currentId])) {
			$_dvp = SVP_DEFAULT_PROFILE;
			self::$currentId = $_dvp;
			self::setDefaultProfile(self::$currentId);
		}
		
		return (int)self::$currentId;
	}

	static function getSystemDefaultProfile() {
		return (int)SVP_DEFAULT_PROFILE;
	}

	static function checkDefaultProfile($dvp=0) {
		// preverimo ali izbran privzet profil obstaja
		if ($dvp == -1) { //preverimo sejo
			if ( isset($_SESSION['variables_profile'][self::$sid]) ) {
				return $_SESSION['variables_profile'][self::$sid]['id'];
			} else { // morali bi imeti sejo pa je ni, zato nastavimo na privzetega (1)
				$dvp = SVP_DEFAULT_PROFILE;
			}
		}

		if ($dvp > 0 ) {
			$stringSelect = "SELECT id FROM srv_variable_profiles WHERE id = '".$dvp."'";
			$sqlSelect = sisplet_query($stringSelect);

			if (mysqli_num_rows($sqlSelect) > 0)  {// profil obstaja
				$rowSelect = mysqli_fetch_assoc($sqlSelect);
				return $rowSelect['id'];
			}
		}

		# ce ne izberemo osnovni profil
		return SVP_DEFAULT_PROFILE;
	}

	/**
	 *
	 * Enter description here ...
	 * @param $pid 				- profil ID
	 * @param $ignoreInspect	- ce je profil inspect, in ga moramo ignorirat potem vrnemo prazn array
	 */
	static function getProfileVariables($pid = null, $ignoreInspect=false)
	{
		if ($pid === null)
		{
			$pid = self::getCurentProfileId();
		}
		
		if ($ignoreInspect == true) 
		{
			return array();
		}

		# ce profil ni inspect ali ce ga ne ignoriramone vrnemo variable
		$variables = unserialize(self::$profiles[$pid]['variables']);
		if (is_array($variables)) 
		{
			$result=array();
			if (count($variables) > 0)
			{
				foreach ($variables AS $key => $variable)
				{
					$result[$variable] = $variable;
				}
			}
			return $result;
		}
		else 
		{
			return array();
		}
		return array();
	}

	
	public function getProfileName($pid) {
		return self::$profiles[$pid]['name'];
	}
	
	/**
	 *
	 * @param $ignoreInspect	- ce je profil inspect, in ga moramo ignorirat
	 */
	static function DisplayLink($ignoreInspect=false,$hideAdvanced = true) {
		global $lang;
		$izbranProfil = self::checkDefaultProfile(self::$currentId);

		$css = ($izbranProfil == SVP_DEFAULT_PROFILE ? ' gray' : '');

		if ($hideAdvanced == false || $css != ' gray') {
			echo '<li class="space">&nbsp;</li>';
			echo '<li>';
			echo '<span class="as_link'.$css.'"  onclick="displayVariableProfile();" title="'.$lang['srv_filtri'].'">'.$lang['srv_filtri'].'</span>';
			echo '</li>';

		}
	}

	/**
	 * @param $ignoreInspect	- ce je profil inspect, in ga moramo ignorirat potem vrnemo prazn array
	 */
	static function getProfileString($ignoreInspect = false) {
		global $lang;

		$pid = self::checkDefaultProfile(self::$currentId);
		if ($ignoreInspect == true) {
			return;
		}

		$svp_pv = self::getProfileVariables($pid);
		$svp_av = self::$SDF->getSurveyVariables();

		if (count($svp_pv) > 0 && count($svp_pv) != count($svp_av)) {
			$vars = array();
			foreach ($svp_pv AS $vkey => $variable) {
				$variable_data = self::$SDF->getHeaderVariable($variable);
				$vars[] = $variable_data['variable'];
			}
				
			$variable_label = implode(', ',$vars);
			echo '<div id="variableProfileNote">';
			echo '<span class="floatLeft">'.$lang['srv_profile_variables_is_filtred'].'</span>';
			echo '<span class="floatLeft">'.$variable_label.'</span>';

			echo '<span class="as_link spaceLeft" onclick="displayVariableProfile();">'.$lang['srv_profile_edit'].'</span>';
			echo '<span class="as_link spaceLeft" onclick="removeVariableProfile();">'.$lang['srv_profile_remove'].'</span>';
			echo '</div>';
			echo '<br class="clr" />';
			return true;
		}
		return false;
	}

	static function chooseProfile($pid){

		# če smo izbrali drug profil resetiramo še profil profilov na trenutne nastavitve
		SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', '0');
		
		if(isset(self::$profiles[$pid])) 
		{
			SurveyUserSetting :: getInstance()->saveSettings('default_variable_profile', $pid);
		}
		else
		{
			SurveyUserSetting :: getInstance()->saveSettings('default_variable_profile', SVP_DEFAULT_PROFILE);
		}
	}
	
	static function ajax() {

		$pid = $_POST['pid'];

		if ($_POST['podstran'] == 'monitoring') {
			self::$monitoring = true; self::refreshAvailableProfiles();
		};

		switch ($_GET['a']) {
			case 'displayProfile':
				self::displayProfiles($_POST['pid']);

				break;
			case 'changeProfile':
				self::displayProfiles($_POST['pid']);
				break;
			case 'chooseProfile':
				self::chooseProfile($_POST['pid']);
				break;
			case 'saveProfile':
				self::saveProfile();
				break;
			case 'saveNewProfile':
				$new_id = self :: newProfileVariables();
				break;
			case 'renameProfile':
				$updated = self::renameVariableProfile($_POST['pid'],$_POST['name']);
			break;
			case 'deleteProfile':
				self::deleteVariableProfile($_POST['pid']);
			break;
		}
	}


	static function displayProfiles ($cvp = null) {
		global $lang;

		$popUp = new PopUp();
		$popUp->setId('div_variable_profiles');
		$popUp->setHeaderText($lang['srv_spremenljivke_settings']);

		#vsebino shranimo v buffer
		ob_start();

        echo '<div class="popup_close"><a href="#" onClick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\'); return false;">✕</a></div>';

		if ($cvp == null)
			$cvp = self::$currentId;
		$svp_ap = self::$profiles;
		$svp_pv = self :: getProfileVariables($cvp);
		$svp_av = self::$SDF->getSurveyVariables();

		if ( self::$currentId != SVP_DEFAULT_PROFILE ) {
			echo '<div id="not_default_setting">';
			echo $lang['srv_not_default_setting'];
			echo '</div>';
			echo '<br class="clr" />';
		}

		echo '<div class="variable_profile_holder">';
		echo '	<div id="variable_profile" class="select">';
		foreach ($svp_ap as $key => $value) {
		
			echo '<div class="option'.($cvp==$value['id']?' active':'').'" value="'.$value['id'].'" '.($cvp==$value['id'] ? '' : ' onclick="changeVariableProfile(\''.$value['id'].'\');"').'>';
			
			echo $value['name'];
			
			if($cvp == $value['id']){
				// izbriši
				if ((int)$cvp != 0){
					echo ' <a href="#" onclick="variableProfileAction(\'deleteAsk\'); return false;" value="'.$lang['srv_delete_profile'].'"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'."\n";
				}
				// preimenuj
				if ((int)$cvp != 0){
					echo ' <a href="#" onclick="variableProfileAction(\'renameAsk\'); return false;" value="'.$lang['srv_rename_profile'].'"><span class="faicon edit icon-as_link floatRight spaceRight"></span></a>'."\n";
				}
			}
			
			echo '</div>';
		}
		echo '	</div>';
		
		// izberi / odznaci vse
		echo '<div style="position:absolute; bottom:20px; left:20px;">';
		echo '<a href="#" onClick="variableProfileSelectAll(1); return false;">'.$lang['srv_select_all'].'</a>';
		echo ' / <a href="#" onClick="variableProfileSelectAll(0); return false;">'.$lang['srv_deselect_all'].'</a>';
		echo '</div>';
		
		echo '</div>';
		

		echo '<div id="vp_list">';

		$empty = count($svp_pv) == 0;
		echo '<ul id="vp_list_ul" class="left">';
		if (count($svp_av) > 0)
		{
			foreach($svp_av as $key => $variabla)
			{
				$_name = self::$SDF->getVariableName($key);
				$checked = ($empty || in_array($key, $svp_pv));
				echo '<li'.($checked?' class="selected"':'').'>';
				echo '<label>';
				echo '<input type="checkbox" '.($checked?' checked="checekd"':'').' name="vp_list_li" value="'.$key.'" id="variable_'.$key.'" onchange="variableProfileCheckboxChange(this);">'.limitString($_name);
				echo '</label></li>';
			}
		}
		echo '</ul>';

		echo '</div>';
		// cover Div
		echo '<div id="variableProfileCoverDiv"></div>';

		// div za shranjevanje novega profila
		echo '<div id="newProfile">'.$lang['srv_missing_profile_name'].': ';
		echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />';
		$button = new PopUpButton($lang['srv_save_profile']);
		echo $button -> setFloat('right')
		->setButtonColor('orange')
		-> addAction('onClick','variableProfileAction(\'newSave\'); return false;');
		$button = new PopUpButton($lang['srv_cancel']);
		echo $button -> setFloat('right')
		-> addAction('onClick','variableProfileAction(\'newCancel\'); return false;');
		echo '</div>';

		// div za preimenovanje
		echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': ';
		echo '<input id="renameProfileName" name="renameProfileName" type="text" value="'.$svp_ap[$cvp]['name'].'" size="45"  />';
		echo '<input id="renameProfileId" type="hidden" value="'.$pid.'"  />';
		$button = new PopUpButton($lang['srv_rename_profile_yes']);
		echo $button -> setFloat('right')
		->setButtonColor('orange')
		-> addAction('onClick','variableProfileAction(\'renameProfile\'); return false;');

		$button = new PopUpButton($lang['srv_cancel']);
		echo $button -> setFloat('right')
		-> addAction('onClick','variableProfileAction(\'renameCancel\'); return false;');
		echo '</div>';

		// div za brisanje
		echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>'.$svp_ap[$cvp]['name'].'</b>?';
		echo '<input id="deleteProfileId" type="hidden" value="'.$pid.'"  />';

		$button = new PopUpButton($lang['srv_delete_profile_yes']);
		echo $button -> setFloat('right')
		->setButtonColor('orange')
		-> addAction('onClick','variableProfileAction(\'deleteConfirm\'); return false;');

		$button = new PopUpButton($lang['srv_cancel']);
		echo $button -> setFloat('right')
		-> addAction('onClick','variableProfileAction(\'deleteCancel\'); return false;');
		echo '</div>';

		$content = ob_get_clean();
		#dodamo vsebino
		$popUp->setContent($content);

		# gumb izberi
		$button = new PopUpButton($lang['srv_choose_profile']);
		$button -> setFloat('right')
		->setButtonColor('orange')
		-> addAction('onClick','variableProfileAction(\'choose\'); return false;');
		$popUp->addButton($button);

		# gumb shrani
		if ((int)$cvp != 0) 
		{
			$button = new PopUpButton($lang['srv_save_profile']);
			$button -> setFloat('right')
				->setButtonColor('gray')
				-> addAction('onClick','variableProfileAction(\'save\'); return false;');
			$popUp->addButton($button);
		}
		
		# gumb shrani kot now
		$button = new PopUpButton($lang['srv_new_profile_name']);
		$button -> setFloat('right')
			-> addAction('onClick','variableProfileAction(\'newName\'); return false;');
		$popUp->addButton($button);
		
		# dodamo gumb Preklici
		$button = new PopUpCancelButton();
		$button -> setFloat('right');
		$popUp->addButton($button);


		echo $popUp;
	}


	static function setProfileVariables($pid, $variables)
	{
		global $lang;
		# če je pid < 1 ga shranimo v sejo
		if ($pid < 1)
		{
			$pid = -1;
			session_start();
		
			# nastavimo kot sejo
			$_SESSION['variables_profile'][self::$sid] = array(
					'id' => "$pid",
					'name' => $lang['srv_missing_profile_temp'],
					'variables' => $variables);
		
					self::$profiles[$pid] = $_SESSION['variables_profile'][self::$sid];
					session_commit();
					
		} 
		else 
		{
			$updateString = "UPDATE srv_variable_profiles SET variables = '".$variables."' WHERE id='".$pid."' AND sid = '". self::$sid."'";
			$sqlupdate = sisplet_query($updateString) or die(mysqli_error($GLOBALS['connect_db']));
			self::$profiles[$pid]['variables'] = $variables;
		}
		
		//echo $pid;
		return $pid;
	}
	
	function saveProfile()
	{
		global $lang;

		$pid = $_POST['pid'];
		$strArray = explode("&",$_POST['vp_list_li']);

		$variables = array();
		foreach ($strArray as $item)
		{
			$array = explode("=", $item);
			$variables[] = $array[1];
		}
		$variables = serialize($variables);
		
		return self::setProfileVariables($pid, $variables);
	}
	
	
	static function newProfileVariables() {
		global $lang;
		
		$profileId = -1;
		$numrows = -1;
		
		$profileName = $_POST['name'];
		if (empty($profileName))
		{
			$profileName = $lang['srv_new_profile_name'];
		}
		
		# ime profila preverima ali obstaja
		do 
		{ # preverimo ali ime že obstaja
			$selectSqlProfile = "SELECT id FROM srv_variable_profiles WHERE name = '".$profileName."' AND sid = '".self::$sid."'";
			$sqlProfileSetting = sisplet_query($selectSqlProfile);
			$numrows = mysqli_num_rows($sqlProfileSetting);
			if ($numrows != 0) 
			{ # ime že obstaja zgeneriramo novo
				srand(time());
				$profileName .= rand(0, 9);
			}
		} 
		while ($numrows != 0);
		
		$strArray = explode("&",$_POST['vp_list_li']);
		$variables = array();
		foreach ($strArray as $item) {
			$array = explode("=", $item);
			$variables[] = $array[1];
		}
		$variables = serialize($variables);

		$stringInsert = "INSERT INTO srv_variable_profiles (sid,name,variables) " .
						"VALUES ('".self::$sid."','".$profileName."','".$variables."')";
		sisplet_query($stringInsert);
		$insertId = mysqli_insert_id($GLOBALS['connect_db']);

		# osvežimo profile
		self::getProfiles();
		
		#nastavimo privzetega
		self::chooseProfile($insertId);
		
		echo $insertId;
		
		return $insertId;
	
	}
	
	function deleteVariableProfile($pid) {
		if ($pid < 0 ) 
		{ // seja -
			session_start();
			unset($_SESSION['variables_profile'][self::$sid]);
			unset(self::$profiles[$pid]);
			session_commit();
		} 
		else if( $pid > 0) 
		{
			$deleteString = "DELETE FROM srv_variable_profiles WHERE id='".$pid."' AND sid = '". self::$sid."'";
			$sqlDelete = sisplet_query($deleteString);
			unset(self::$profiles[$pid]);
		}
		$pid = SVP_DEFAULT_PROFILE;
		
		return self::chooseProfile($pid);
	}
	
	
	function renameVariableProfile($profileId, $newProfileName) {
		global $lang;
		
		$sqlInsert = -1;
		if ( !empty($profileId) && (int)$profileId > 0) 
		{
			if ( $newProfileName == null || $newProfileName == "" ) 
			{
				$newProfileName = $lang['srv_new_profile_name'];
			}
	
			$numrows = -1;
			do { // preverimo ali ime že obstaja
				$selectSqlProfile = "SELECT id FROM srv_variable_profiles WHERE name = '".$newProfileName."' AND sid = '".self::$sid."'";
				$sqlProfileSetting = sisplet_query($selectSqlProfile);
	
				$numrows = mysqli_num_rows($sqlProfileSetting);
				if ($numrows != 0) { // ime že obstaja zgeneriramo novo
					srand(time());
					$newProfileName .= rand(0, 9);
				}
			} while ($numrows != 0);
				
			$updateString = "UPDATE srv_variable_profiles SET name = '".$newProfileName."' WHERE id = '".$profileId."' AND sid = '".self::$sid."'";
			$sqlInsert = sisplet_query($updateString);
		}
		
		return $sqlInsert;
	}
	
	/** Nastavimo profil kot zacasen inspect
	 *
	 * Enter description here ...
	 * @param $variables
	 */
	function setProfileInspect($variables) {
		global $lang;
	
		# nastavimo kot sejo
		$pid = -1;
		
		if (is_array($variables))
		{
			$variables = serialize($variables);
		}
		
		self::$profiles[$pid] = array('id'=>$pid, 'name'=>$lang['srv_inspect_temp_profile'], 'variables'=>$variables);
		session_start();
		$_SESSION['variables_profile'][self::$sid] = self::$profiles[$pid];
		session_commit();
		# dodoamo še v class
		self::setDefaultProfile($pid);
	}
	
	/** old class compatibility **/
	static function setDefaultProfile($pid) {
		self::chooseProfile($pid);
	}
	/** old class compatibility **/
	static function setDefaultProfileId($pid) {
		self::chooseProfile($pid);
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

function limitString($input, $limit = 100) {
	
	// Return early if the string is already shorter than the limit
	if(strlen($input) < $limit) {
		return $input;
	}

	$regex = "/(.{1,$limit})\b/";
	preg_match($regex, $input, $matches);
	
	return $matches[1].'...';
}

?>