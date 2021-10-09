<?php
/**
* @author 	Gorazd Veselič
* @date		Julij 2010
* @desc		Namenjen merjenju časa izvajanja posameznih funkcij 
* 
*/

class Timer {
	
	static private $timer = array(); 						# timer
	static private $_timer_output = true;					# ali timer izpiše output

	static private $_decimals = 7;							# Število decimalk
	static private $_decimals_delimit = ',';				# decimalno ločilo

		/** Starta tajmer
	 * 
	 */
	static public function StartTimer($grups=0) {
		# v header dodomo userid
		$mtime = explode(" ",microtime());
		self::$timer[$grups]['start'] = $mtime[1] + $mtime[0];
	}

	/** Vrne čas izvajanja skripte
	 * 
	 */
	static public function GetTimer($grups=0) {
		global $lang;
		# v header dodomo userid
		$mtime = explode(" ",microtime());
		self::$timer[$grups]['end'] = $mtime[1] + $mtime[0];
		if (self::$_timer_output == true && isset(self::$timer[$grups]['start'] )) {
			 $_time = number_format((self::$timer[$grups]['end']-self::$timer[$grups]['start']), self::$_decimals , self::$_decimals_delimit, ' ');
			printf($lang['srv_timer_output'],$grups,$_time);
		}
		return number_format((self::$timer[$grups]['end']-self::$timer[$grups]['start']), self::$_decimals , self::$_decimals_delimit, ' ');
	}

	/**
	 * 
	 * @param unknown_type $out
	 */
	static public function setTimerOutput($out=true) {
		self::$_timer_output = $out;
		return $out;
	}
   
}
