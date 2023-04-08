<?php
/**
 * Created on 4.5.2009
 *
 * @author: GOrazd Vesleič
 *
 * @desc: za nastavitve uporabnikov, za vsako anketo posebej
 */

 class SurveyUserSetting
{
	static private $instance;

	static private $surveyId = null;
	static private $userId = null;
	static private $inited = false;
	 static private $rowUserInit = null;

	protected function __construct() {}

	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveyUserSetting();
		}
		return self::$instance;
	}

	/** napolnimo podatke
	 *
	 */
	static function Init($_surveyId, $_userId)
	{
		if ($_surveyId && $_userId)
		{
			self::$surveyId = $_surveyId;
			self::$userId = $_userId;
			
			self::$inited=true;
			return true;
		}
		else
			return false;
	}

	static function getSurveyId()			{ return self::$surveyId; }
	static function getUserId()				{ return self::$userId; }

	static function useIfinReport()			{ // stara funkcija
		$use_if_in_report = self::getSettings('use_if_in_report');
		if ($use_if_in_report == null)
			$use_if_in_report = 1;
		return $use_if_in_report; }

	static function setShowPdfIf($set)		{ // stara funkcija
		$value = $set?1:0;
		self::saveSettings('use_if_in_report', $value);
	}


	static function getSettings($what) {
		$selectSql = "SELECT value FROM srv_user_setting_for_survey WHERE sid = '".self::$surveyId."' AND uid = '".self::$userId."' AND what = '".$what."'";
		$sqlUserSetting = sisplet_query($selectSql);
		$rowUserSetting = mysqli_fetch_assoc($sqlUserSetting);
		return $rowUserSetting['value'];
	}
	
	static function getAll() {
		$result = array();
		$selectSql = "SELECT what, value FROM srv_user_setting_for_survey WHERE sid = '".self::$surveyId."' AND uid = '".self::$userId."'";
		$sqlUserSetting = sisplet_query($selectSql);
        while (list($what,$value) =mysqli_fetch_row($sqlUserSetting))
        {
        	$result[] = array('what'=>$what,'value'=>$value);
        }
		return $result;
	}

	static function saveSettings($what, $value) {

		if ($what != null) {
	    	$insertString = "INSERT INTO srv_user_setting_for_survey  (sid, uid, what, value) VALUES " .
	    		"('".self::$surveyId."', '".self::$userId."', '".$what."', '".$value."') " .
	    		"ON DUPLICATE KEY UPDATE value='".$value."'";
	    	$insert = sisplet_query($insertString);
	    	sisplet_query('COMMIT');
		}

		return $insert;
	}
	static function removeSettings($what) {
		if ($what != null) {
	    	$deleteString = "DELETE FROM srv_user_setting_for_survey WHERE sid = '".self::$surveyId."' AND uid = '".self::$userId."' AND what = '".$what."'";
	    	$delete = sisplet_query($deleteString);
		}

		return $insert;
	}

	 static function getUserRow () {
		 global $global_user_id;

		 if (!self::$rowUserInit) {
			 $queryUserInit = sisplet_query("SELECT * FROM users WHERE id = '".$global_user_id."'");
			 self::$rowUserInit = mysqli_fetch_assoc($queryUserInit);
		 }

		 return self::$rowUserInit;
	 }

}
?>
