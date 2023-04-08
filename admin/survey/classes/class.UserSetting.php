<?php
/**
 * Created on 12.6.2009
 *
 * @author: Gorazd Vesleič
 *
 * @desc: za uporabnikove nastavitve prikaza vmesnika, 
 * 		  nastavlja globalne nastavitve za vse ankete. 
 * 		  Uporablja tabelo: srv_user_setting
 * 
 *   -- usr_id						# user ID
 *  # za izpis folderjev
 *   -- survey_list_order			# vrstni red stolpcev v Direktoriju
 *   -- survey_list_order_by		# sortiranje po stolpcu
 *   -- survey_list_rows_per_page	# koliko zapisov na stran (25)
 *   -- survey_list_visible			# kateri stolpci so vidni
 *   -- survey_list_widths			# širine posameznih stolpcev
 * 
 *  # vizualizacija
 *   -- icons_always_on				# ali so ikonice vedno vidne
 *   -- full_screen_edit			# ali privzeto editira vprašanja v full screen načinu
 *   
 */

 class UserSetting
{
	static private $instance;				# instanca razreda

	static private $userId = null;			# user ID
	static private $inited = false;			# ali je razred inizializiran
	static private $user_setting = array(); # array z uporabniškimi nastavitvami;
	static private $changed = array(); 		# katera polja so bila spremenjena, uporabimo pri vpisovanju v bazo

	protected function __construct() {}

	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new UserSetting();
		}
		return self::$instance;
	}

	/** napolnimo podatke
	 *
	 */
	static function Init($_userId = null)
	{
		global $global_user_id;
		if (self::$inited) {
			return true;
		} else {
			if ($_userId == null) { // ce slucajno ni user_id-ja
				$_userId = $global_user_id;
			}
			if ($_userId != null) {
				self::$userId = $_userId;
				$selectSql = "SELECT * FROM srv_user_setting WHERE usr_id = '".self::$userId."'";
				$sqlUserSetting = sisplet_query($selectSql);	
				if ( mysqli_num_rows( $sqlUserSetting ) > 0 ) {
					self::$user_setting = mysqli_fetch_assoc($sqlUserSetting);
					self::$inited=true;
					return true;
				} else {

					// uporabnik se nima svojih nastavitev. preberemo iz sistemskih
					// TODO
										
					//zaenkrat kar direkt nastavimo privzete nastavitve
					self::$user_setting['icons_always_on'] = 0;
					self::$user_setting['full_screen_edit'] = 0;
					
					self::$user_setting['autoActiveSurvey'] = 0;
					self::$user_setting['lockSurvey'] = 1;
					
					self::$user_setting['activeComments'] = 0;
					
					self::$user_setting['showIntro'] = 1;
					self::$user_setting['showConcl'] = 1;
					self::$user_setting['showSurveyTitle'] = 1;	

					self::$inited=true;
					return true;
				}
			}
			else {
				return false;
			}
				
		}
	}

	static function getUserId()				{ return self::$userId; }
	static function getUserSetting($what)	{ 
		return self::$user_setting[$what];
	}

	/** @desc ponastavi nastavitev in shrani v bazo
	 *
	 */
	static function setUserSetting($what, $value) {
		if (isset($what) && isset($value)) {
			self::$changed[$what] = $value;
			self::$user_setting[$what] = $value;
		}
		else
			return false;
	}

	/** @desc v bazi popravimo vse spremenjene zapise zapis
	 *
	 */
	static function saveUserSetting()
	{
		if (self::$inited && is_countable(self::$changed) && count(self::$changed) > 0 ) {

			$str_insert_fields = 'usr_id';
			$str_insert_values = "'".self::$userId."'";
			$str_update_text = '';
			$str_update_prefix = '';
			
			foreach (self::$changed as $what => $value) {
				if (isset(self::$user_setting[$what])) {
					$str_insert_fields .=  ', '.$what;
					$str_insert_values .=  ", '".self::$user_setting[$what]."'";
					$str_update_text	.= $str_update_prefix . $what."='".self::$user_setting[$what]."'";
					$str_update_prefix = ', ';

				}	
				unset(self::$changed[$what]);			
			}

			// sestavimo mysql insert string
			$insertString = 'INSERT INTO srv_user_setting ('.$str_insert_fields.') VALUES ('.$str_insert_values.') ON DUPLICATE KEY UPDATE '.$str_update_text;
			self::$changed = array();
		    $insert = sisplet_query($insertString);
	    	return mysqli_affected_rows($GLOBALS['connect_db']);
		} else { // manjkajo podatki za vpis v bazo
			return false;
		}
	}
}
?>
