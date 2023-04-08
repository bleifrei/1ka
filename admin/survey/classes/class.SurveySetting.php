<?php
/*
 * Created on 2.6.2009
 *
 */

class SurveySetting
{
	static private $instance;


	// SurveyId
	static private $sid = null;

	static private $mySqlResult = null;
	static private $mySqlErrNo = null;

	// konstrutor
	protected function __construct() {}
	// kloniranje
	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveySetting();
		}
		return self::$instance;
	}

	/** inicializacija */

	static function Init( $_surveyId = null)
	{
		if ( $_surveyId )
			{ self::$sid = $_surveyId; }
	}

	// nastavimo nov Survey Id
	static function setSID( $_surveyId = null )
	{
		if ( $_surveyId )
			{ self::$sid = $_surveyId; }
	}


	/**
	 * @desc polovimo nastavitev za posamezno anketo če obstaja,
	 * če ne uporabimo nastavitev sistema
	 */
	private $getSurveyMiscSetting = array();
	function getSurveyMiscSetting($what=null)
	{
		# če že imamo polovljene nastavitve iz baze jih vrnemo direkt
		if (isset($this->getSurveyMiscSetting[$what])) {
			return $this->getSurveyMiscSetting[$what];
		}
		
		if (is_string($what))
		{
			$stringSelect = "SELECT value FROM srv_survey_misc WHERE sid='".self::$sid ."' AND what = '".$what."'";
			$sqlSelect = sisplet_query($stringSelect);
            if (mysqli_num_rows($sqlSelect) > 0)
            {
            	$rowSelect = mysqli_fetch_assoc($sqlSelect);
            	$result = $rowSelect['value'];
            }
            else
        	{
        		global $site_path;
        		//require_once($site_path.'admin/survey/classes/class.Setting.php');
				Setting::getInstance()->Init();
				$result = Setting::getInstance()->getSysMiscSetting($what);
        	}
		}
		
		$this->getSurveyMiscSetting[$what] = $result;
		return $this->getSurveyMiscSetting[$what];
	}
	/**
	 * @desc shranimo nastavitev survey sistema
	 */
	function setSurveyMiscSetting($what=null, $value=null)
	{
		if (self::$sid ) // rabimo sid
		{
			if ( $what )				// pustimo, da je value 0 ali prazen
			{
				if ( is_string($what) )
				{
					$stringInsert = "INSERT INTO srv_survey_misc (sid, what, value) VALUES ('".self::$sid."', '".$what."', '".$value."') ON DUPLICATE KEY UPDATE value = '".$value."'";
					$sqlInsert = sisplet_query($stringInsert);
					sisplet_query("COMMIT");
					return mysqli_affected_rows($GLOBALS['connect_db']);
				}
				else
					return false;
			}
			else
				return false;

		}
		else
			return false;
	}
	
	function removeSurveyMiscSetting ($what = null) {
		
		if (self::$sid) {	// rabimo sid
			
			if ( $what ) {				// pustimo, da je value 0 ali prazen
			
				if ( is_string($what) ) {
					
					$stringInsert = "DELETE FROM srv_survey_misc WHERE sid = '".self::$sid."' AND what = '".$what."'";
					$sqlInsert = sisplet_query($stringInsert);
					return mysqli_affected_rows($GLOBALS['connect_db']);
					
				} 
			}
		}
		
		return false;
			
	}
}

?>
