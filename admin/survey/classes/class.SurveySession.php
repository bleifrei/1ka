<?php 
/** Shranjujemo nastavitve ankete. Za vsako anketo unikatno.
 * 
 * @author veselicg
 *
 */

/** Ker za statičen klas ne moremo narediti destructor funkcije,
 *  si pomagamo s lastno destruktor instanco, 
 *  katera pokliče destruct funkcijo našega statičnega razreda
 * 
 * @author veselicg
 *
 */
class SurveySessionDestructor
{
	/** pokličemo destruktor funkcijo
 	 * 
	 */
	public function __destruct()
	{
		SurveySession::destruct();
	}
}

class SurveySession {
	
	private static $destructorInstance;
	private static $anketa = null;
	private static $data = array();
	private static $updated = false;
	
	
	static function sessionStart($anketa = null) {
		if (null === self::$destructorInstance)
			self::$destructorInstance = new SurveySessionDestructor();
		
		if ($anketa == null || (int)$anketa == 0 || !is_numeric($anketa)) 
		{
			throw new Exception('Survey ID is mandatory for SurveySession!');
		}
		self::$anketa = $anketa;
		
		# preberemo vse nastavitve za to anketo
		$sql = sisplet_query("SELECT what,value FROM srv_survey_session WHERE ank_id='".self::$anketa."'");
		while (list($what,$value) = mysqli_fetch_row($sql)) 
		{
			self::$data[$what] = unserialize($value); 
		}
	}

	static function get($what=null) {
		if (self::$anketa == null) 
		{
			throw new Exception('Survey ID is mandatory for SurveySession!');
			return null;
		}
		
		if ($what == null) 
		{
			return self::$data;
		} 
		else if (isset(self::$data[$what])) 
		{
			return self::$data[$what];
		} 
		else
		{
			return null;
		} 
		return null;
	}
	
	static function set($what,$value) {
		if (self::$anketa == null)
		{
			throw new Exception('Survey ID is mandatory for SurveySession!');
			return null;
		}
		if ($what == null) 
		{
			throw new Exception('Variable \'what\' is mandatory for SurveySession!');
			return null;
		}
		if (!is_string($what)) 
		{
			throw new Exception('Variable \'what\' must be string!');
			return null;
		}
		
		# če je vse ok setiramo vrednost
		self::$data[$what] = $value;
		self::$updated = true;
		
		return true;
	}
	
	static public function remove($what)
	{
		if (self::$anketa == null)
		{
			throw new Exception('Survey ID is mandatory for SurveySession!');
			return null;
		}
		if ($what == null) 
		{
			throw new Exception('Variable \'what\' is mandatory for remove()!');
			return null;
		}
		if (!is_string($what)) 
		{
			throw new Exception('Variable \'what\' must be string!');
			return null;
		}
		
		if (isset(self::$data[$what])) {
			unset (self::$data[$what]);
			
			#pobrišemo še iz baze
			$deleteString = "DELETE FROM srv_survey_session WHERE ank_id = '".self::$anketa."' AND what='$what'";
			$query = sisplet_query($deleteString);
			self::$updated = true;
		}
		return null;
	}
	
	/** ob ukinitvi klassa shranimo vse vrednosti
	 * 
	 */
	static public function destruct()
	{
		if (self::$anketa == null)
		{
			throw new Exception('Survey ID is mandatory for SurveySession!');
			return null;
		}
		
		// v destructu več nimamo connect_db resoursa
		global $connect_db;
		if ($connect_db == null) {
			// poizkusimo še 1x
			global $mysql_server, $mysql_username, $mysql_password, $mysql_database_name;
			if (!$connect_db = mysqli_connect($mysql_server, $mysql_username, $mysql_password, $mysql_database_name)) {
				die ('Please try again later [ERR: DB])');
			}
		}		
		
		# pripravimo string za shranjevanje
		if (count(self::$data) > 0 && self::$updated) {
			$insertStringArray = array();
			foreach (self::$data AS $what => $value) {
				$insertStringArray[] = "('".self::$anketa."', '".$what."', '".serialize($value)."')";
			}
			$insertString = "INSERT INTO srv_survey_session (ank_id,what,value) VALUES ".implode(', ',$insertStringArray);
			$insertString .=" ON DUPLICATE KEY UPDATE value = VALUES(value)";
			$query = sisplet_query($insertString, $connect_db);
		}
	}
	
	static function append($where,$what,$value)
	{
		if (self::$anketa == null)
		{
			throw new Exception('Survey ID is mandatory for SurveySession!');
			return null;
		}
		if ($what == null)
		{
			throw new Exception('Variable \'what\' is mandatory for remove()!');
			return null;
		}
		if (!is_string($what))
		{
			throw new Exception('Variable \'what\' must be string!');
			return null;
		}
		self::$updated = true;
		self::$data[$where][$what] = $value; 
	}
}