<?php
/**
 * Created on 6.4.2010
 *
 * @author: Gorazd Veselič
 *
 * @desc: za shranjevanje in nalaganje profilov izvozov
 *
 * funkcije:
 * 
 * 
 */
session_start();
class SurveyExportProfiles
{
	static private $instance; 								// instanca razreda (razred kreiramo samo enkrat)
	static private $userId = null; 							// user id
	static private $surveyId = null; 						// id ankete

	static private $availableProfiles = array(); 			// array kamor shranimo vse doseglijive  profile
	static private $profilesData = array(); 				// array kamor shranimo podatke o trenutnem profilu

	protected function __construct() {}

	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveyVariablesProfiles();
		}
		return self::$instance;
	}

	/** Inicializacija
	 *
	 */
	static function Init($sid, $uid, $doRefresh=true) {
		session_start();
		self::setSurveyId($sid); 
		self::setUserId($uid);
		self::checkBaseProfileExisit();
		if ($doRefresh==true) {
			//poiščemo vse dosegljive profile
			self::refreshAvailableProfiles();
		}		
	}


	static function getSurveyId()					{ return self::$surveyId; }
	static function setSurveyId($sid)				{ self::$surveyId = $sid; }
	static function getUserId()						{ return self::$userId; }
	static function setUserId($uid)					{ self::$userId = $uid; }

	static function getAvailableProfiles()	{ return self::$availableProfiles; }

	static function checkBaseProfileExisit() {
		global $lang;
		
		$selectCheckProfile = "SELECT id FROM srv_variable_profiles WHERE id='1' AND sid = '" . self::getSurveyId() . "' AND uid = '" . self::getUserId() . "'";
		$sqlCheckProfile = sisplet_query($selectCheckProfile);
        
		// če ne obstaja noben profil, kreiramo novega sistemskega - Vse spremenljivke.
        if (!mysqli_num_rows($sqlCheckProfile)) {
			$stringInsert = "INSERT INTO srv_variable_profiles (id, sid, uid, name, system) " .
			"VALUES ('1', '" . self::getSurveyID() . "', '" . self::getUserID() . "','".$lang['srv_variable_profile_new_all']."', '1')";
			sisplet_query($stringInsert) or die(mysqli_error($GLOBALS['connect_db']));		
        }
		
        return 1;
	}
	
	static function refreshAvailableProfiles() {
		// počistimo
		self::$availableProfiles = array();
		// dodamo profil iz seje če obstaja
		$sid = self::getSurveyId();
		if ( isset($_SESSION['variables_profile'][$sid])) {
				self::$availableProfiles[0] = array( 'id'	 => $_SESSION['variables_profile'][$sid]['id'], 
													 'name'	 => $_SESSION['variables_profile'][$sid]['name'],
													 'system'=> $_SESSION['variables_profile'][$sid]['system'],
													 'variables'=> $_SESSION['variables_profile'][$sid]['variables']);
		}

		$selectSqlProfile = "SELECT * FROM srv_variable_profiles WHERE sid = '" . self::getSurveyId() . "' AND uid = '" . self::getUserId() . "' ORDER BY id";
		$sqlProfileSetting = sisplet_query($selectSqlProfile);
        while ($rowProfileSetting = mysqli_fetch_assoc($sqlProfileSetting)) {
        	self::$availableProfiles[$rowProfileSetting['id']] = $rowProfileSetting;
        }
	}


	static function getProfileData($pid) {
		// preverimo ali smo v razredu že lovili podatke za ta profil, potem jih preberemo čene jih osvežimo
		if ( isset( self::$profilesData[$pid] ) ) {
			return self::$profilesData[$pid];
		} else {
			$result = self::refreshProfileData($pid);
			// rezultat si zapomnimo
			self::$profilesData[$pid] = $result;
			return self::$profilesData[$pid];
		}
	}
	

	static function refreshProfileData($pid) {
		// preberemo iz seje če obstaja zapis, čene preberemo privzet zapis (id=1)
		if ($pid == 0) { // če je seja vrnemo sejo
			// preberemo iz seje če obstaja zapis, čene preberemo privzet zapis (id=1)
			if ( isset($_SESSION['variables_profile'][self::getSurveyId()]) ) {
				return $_SESSION['variables_profile'][self::getSurveyId()];
			} else
				$pid = 1;	
		}

		// polovimo podatke iz baze
		// prvo polovimo nastavitve profila (id,name, system, variables)
		$selectSqlProfile = "SELECT * FROM srv_variable_profiles WHERE id = '".$pid."' AND sid = '". self::getSurveyID()."' AND uid='". self::getUserID()."'";
		$sqlProfileSetting = sisplet_query($selectSqlProfile);
		$rowProfileSetting = mysqli_fetch_assoc($sqlProfileSetting);
		$result = array( 'id'		=> $rowProfileSetting['id'],
						 'name'		=> $rowProfileSetting['name'],
						 'system'	=> $rowProfileSetting['system'],
						 'variables'=> array());
		// variable vrnemo kot array $key == $value;
		foreach (explode(',',$rowProfileSetting['variables']) as $vriabla ){

			if ($vriabla)
				$result['variables'][$vriabla] = $vriabla;
		}
		// string iz baze za variables razbijemo v array
		return $result;
	}

	static function getProfileVariables($pid) {

		$_pd = self::getProfileData($pid);
		return $_pd['variables']; 		
	}
	static function setProfileVariables($pid, $variables) {
		if ($pid == 0) {
			$sid = self::getSurveyId();
			// nastavimo kot sejo
			if ( !isset($_SESSION['variables_profile'][$sid]) ) {
				global $lang;
				//kreiramo začasin profil
				$_SESSION['variables_profile'][$sid]['id'] = '0';
				$_SESSION['variables_profile'][$sid]['name'] = $lang['srv_missing_profile_temp'];
				$_SESSION['variables_profile'][$sid]['system'] = '0';
			}
			// počistimo stare vrednosti
			$_SESSION['variables_profile'][$sid]['variables'] = array();
			foreach (explode(',',$variables) as $vriabla ){
				if ($vriabla)
					$_SESSION['variables_profile'][$sid]['variables'][$vriabla] = $vriabla;
			}
			// dodoamo še v class	
			self::$availableProfiles[0] = array( 'id'	 => $_SESSION['variables_profile'][$sid]['id'], 
												 'name'	 => $_SESSION['variables_profile'][$sid]['name'],
												 'system'=> $_SESSION['variables_profile'][$sid]['system'],
												 'variables'=> $_SESSION['variables_profile'][$sid]['variables']);
		} else {

			$updateString = "UPDATE srv_variable_profiles SET variables = '" . $variables . "' WHERE id = '" . $pid . "' AND sid = '". self::getSurveyID()."' AND uid='". self::getUserID()."'";

			$sqlupdate = sisplet_query($updateString) or die(mysqli_error($GLOBALS['connect_db']));
			self::$availableProfiles[$pid]['variables'] = array();
			foreach (explode(',',$variables) as $vriabla ){
				if ($vriabla)
					self::$availableProfiles[$pid]['variables'][$vriabla] = $vriabla;
			}
		}
	}

	function getAvailableVariables ($assArray = false) {
		$variablesFilter = array ();
		$sqlSpremenljivkeAnkete = sisplet_query("SELECT s.tip, s.naslov, s.variable, s.id, s.textfield, s.textfield_label FROM srv_grupa g, srv_spremenljivka s WHERE g.ank_id='".self::getSurveyID()."' AND g.id=s.gru_id ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
		while ($rowSpremenljivkeAnkete = mysqli_fetch_assoc($sqlSpremenljivkeAnkete)) {
			$value = $rowSpremenljivkeAnkete['id'];
			$text = strip_tags("(" . $rowSpremenljivkeAnkete['variable'] . ") - " . $rowSpremenljivkeAnkete['naslov']);
			if ($assArray)
				$variablesFilter[] = $value;
			else
				$variablesFilter[] = array (
					'value' => $value,
					'text' => $text
				);
		}
		return $variablesFilter;
	}

	static function checkDefaultProfile($dvp) {
		// preverimo ali izbran privzet profil obstaja
		if ($dvp == 0) { //preverimo sejo
			if ( isset($_SESSION['variables_profile'][self::getSurveyId()]) ) {
				return $_SESSION['variables_profile'][self::getSurveyId()]['id'];
			} else { // morali bi imeti sejo pa je ni, zato nastavimo na privzetega (1) 
				$dvp = 1;
			}
		} 
		
		if ($dvp > 0 ) {
			$stringSelect = "SELECT id FROM srv_variable_profiles WHERE id = '" . $dvp . "'";			
			$sqlSelect = sisplet_query($stringSelect);
			 
			if (mysqli_num_rows($sqlSelect) > 0)  {// profil obstaja
				$rowSelect = mysqli_fetch_assoc($sqlSelect);
				return $rowSelect['id']; 
			}
		}

		// če ne izberemo osnovni profil
		return self::checkBaseProfileExisit();
	}
		
	static function newProfileVariables($profileName=null, $data) {
		global $global_user_id, $lang;
		$profileId = -1;
		$numrows = -1;

		// ime profila preverima ali obstaja
		if (!$profileName || $profileName == null || $profileName == "")
			$profileName = $lang['srv_new_profile_name'];

		do { // preverimo ali ime že obstaja
			$selectSqlProfile = "SELECT * FROM srv_variable_profiles WHERE name = '" . $profileName . "' AND sid = '" . self::getSurveyID() . "' AND uid = '" . self::getUserID() . "'";
			$sqlProfileSetting = sisplet_query($selectSqlProfile);
			$numrows = mysqli_num_rows($sqlProfileSetting);
			if ($numrows != 0) { // ime že obstaja zgeneriramo novo
				srand(time());
				$profileName .= rand(0, 9);
			}
		} while ($numrows != 0);

		// poiščemo zadnji id
		$selectProfileId = "SELECT max(id) as last_id FROM srv_variable_profiles WHERE sid = '" . self::getSurveyID() . "' AND uid = '" . self::getUserID() . "'";
		$sqlProfileId = sisplet_query($selectProfileId);
		$rowProfileId = mysqli_fetch_assoc($sqlProfileId);
		$profileId = $rowProfileId['last_id']+1;
		$stringInsert = "INSERT INTO srv_variable_profiles (id, sid, uid, name, system, variables) " .
			"VALUES ('".$profileId."', '" . self::getSurveyID() . "', '" . self::getUserID() . "', '" . $profileName . "', '0', '".$data."')";
		sisplet_query($stringInsert);
		$insertId = mysqli_insert_id($GLOBALS['connect_db']);
		if ($insertId > 1) {
			$profileId = $insertId;
		}
		return $profileId;

	}
	
	function deleteVariableProfile($profileId) {
		if ($profileId == 0 ) { // seja -
			unset($_SESSION['variables_profile'][self::getSurveyId()]);
		} else {
			$deleteString = "DELETE FROM srv_variable_profiles WHERE id = '" . $profileId . "' AND `system` != '1'";
			$sqlDelete = sisplet_query($deleteString);
		}
		
		return self::checkBaseProfileExisit();		
	}	
	function renameVariableProfile($profileId, $newProfileName) {
		global $lang;
		$sqlInsert = -1;
		if ( $profileId != null && $profileId != "" && $profileId > 1) {
			if ( $newProfileName == null || $newProfileName == "" ) {
				$newProfileName = $lang['srv_new_profile_name'];
			}

			$numrows = -1;
			do { // preverimo ali ime že obstaja 
				$selectSqlProfile = "SELECT * FROM srv_variable_profiles WHERE name = '" . $newProfileName . "' AND sid = '" . self::getSurveyID() . "' AND uid = '" . self::getUserID() . "'";
				$sqlProfileSetting = sisplet_query($selectSqlProfile);

				$numrows = mysqli_num_rows($sqlProfileSetting);
				if ($numrows != 0) { // ime že obstaja zgeneriramo novo
					srand(time());
					$newProfileName .= rand(0, 9);
				}
			} while ($numrows != 0);
			
			$updateString = "UPDATE srv_variable_profiles SET name = '" . $newProfileName . "' WHERE id = '" . $profileId . "'";
			$sqlInsert = sisplet_query($updateString);
		}			
		return $sqlInsert;
	}	
}
?>
