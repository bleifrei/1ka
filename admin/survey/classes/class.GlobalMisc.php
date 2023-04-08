<?php

/**
* class z globalnimi nastavitvami/opcijami za vse surveye
*/

class GlobalMisc {
	
	static $getMisc = array();		// cache, ce veckrat dostopamo do istih podatkov
	
	static function getMisc($what) {
		
		if (isset(self::$getMisc[$what]))
			return self::$getMisc[$what];
			
		$sql = sisplet_query("SELECT * FROM srv_misc WHERE what = '$what'");
		$row = mysqli_fetch_array($sql);
		return self::$getMisc[$what] = $row['value'];
		
	}
	
	static function setMisc($what, $value) {
		
		$sql = sisplet_query("REPLACE INTO srv_misc (what, value) VALUES ('$what', '$value')");
		self::$getMisc[$what] = $value;
		
	}
	
}
	
?>