<?php

/**
* Singleton class s seznamom za demografijo. Array vsebuje IDje podtipov in imena pripadajočih variabel
*/

class Demografija {
	
	static $instance = false;
	private $seznamcek = array();
	
	/**
	* Poskrbi za singleton..
	* 
	*/
	public static function getInstance () {
		if (!self::$instance)
			self::$instance = new Demografija();
			
		return self::$instance;
	}
	
	/**
	* V konstruktorju sestavimo array z variablami, ki se uporabljajo v demografiji
	* 
	*/
	private function __construct () {
		
		// starost
		$this->seznamcek['starost'][] = 'XSTAR1a2';
		$this->seznamcek['starost'][] = 'XSTAR2a4';
		$this->seznamcek['starost'][] = 'XSTAR3ac7';
		$this->seznamcek['starost'][] = 'XSTAR3ac7';
		$this->seznamcek['starost'][] = 'XSTAR4ac5b';
		$this->seznamcek['starost'][] = 'XSTAR1b2';
		$this->seznamcek['starost'][] = 'XSTAR2b3';
		$this->seznamcek['starost'][] = 'XSTAR3b5';
		$this->seznamcek['starost'][] = 'XSTAR4b7';
		$this->seznamcek['starost'][] = 'XSTAR1c2';
		$this->seznamcek['starost'][] = 'XSTAR2c3';
		$this->seznamcek['starost'][] = 'XSTAR1d6';
		$this->seznamcek['starost'][] = 'XSTAR2d13';
		$this->seznamcek['starost'][] = 'XSTAR3d19';
		$this->seznamcek['starost'][] = 'XSTAR006';
		$this->seznamcek['starost'][] = 'XSTARletni';
		$this->seznamcek['starost'][] = 'XSTARleta';
		$this->seznamcek['starost'][] = 'XLETNICA';
		$this->seznamcek['starost'][] = 'XDATUMROJ';
		
		// zakonski stan
		$this->seznamcek['stan'][] = 'XZST1surs4';
		$this->seznamcek['stan'][] = 'XZST2a5';
		$this->seznamcek['stan'][] = 'XZST3sjm5';
		$this->seznamcek['stan'][] = 'XZST4a5';
		$this->seznamcek['stan'][] = 'XZST5val6';
		$this->seznamcek['stan'][] = 'XZST6a7';
		
		// izobrazba
		$this->seznamcek['izobrazba'][] = 'XIZ9vris11';
		$this->seznamcek['izobrazba'][] = 'XIZ7a9';
		$this->seznamcek['izobrazba'][] = 'XIZ5a7';
		$this->seznamcek['izobrazba'][] = 'XIZ4a4';
		$this->seznamcek['izobrazba'][] = 'XIZ3a3';
		$this->seznamcek['izobrazba'][] = 'XIZ1a2';
		$this->seznamcek['izobrazba'][] = 'XIZ8surs9';
		$this->seznamcek['izobrazba'][] = 'XIZ2surs3';
		$this->seznamcek['izobrazba'][] = 'XIZ10ess12';
		$this->seznamcek['izobrazba'][] = 'XIZ6sjm8';
		
		// status
		$this->seznamcek['status'][] = 'XDS1sjm2';
		$this->seznamcek['status'][] = 'XDS2a4';
		$this->seznamcek['status'][] = 'XDS3a7';
		$this->seznamcek['status'][] = 'XDS4ess10';
		$this->seznamcek['status'][] = 'XDS5a14';
		$this->seznamcek['status'][] = 'XDS6sjm14';
		$this->seznamcek['status'][] = 'XDS7ris16';
		$this->seznamcek['status'][] = 'XDS8val18';
		
		// spol
		$this->seznamcek['spol'][] = 'XSPOL';
		
		// podjetja
		$this->seznamcek['podjetja'][] = 'XPODRUZ';
		$this->seznamcek['podjetja'][] = 'XPODJPRIH';
		$this->seznamcek['podjetja'][] = 'XPODJZAPOSL';
		//$this->seznamcek['podjetja'][] = 'XAKADEM';
		//$this->seznamcek['podjetja'][] = 'XZAPOSLDEJAV';
		
		// lokacija
		$this->seznamcek['lokacija'][] = 'XLOKACEVROPA';
		$this->seznamcek['lokacija'][] = 'XLOKACREGs';
		$this->seznamcek['lokacija'][] = 'XLOKACREGk';
		$this->seznamcek['lokacija'][] = 'XLOKACUE';
		$this->seznamcek['lokacija'][] = 'XLOKACTN6';
		$this->seznamcek['lokacija'][] = 'XVELNASELJE';
		$this->seznamcek['lokacija'][] = 'XLOKACTN5';
		$this->seznamcek['lokacija'][] = 'XLOKACOB';
		$this->seznamcek['lokacija'][] = 'XVELNASsjm';
		$this->seznamcek['lokacija'][] = 'XTIPKSsjm';
		
	}
	
	/**
	* preveri ce je podana variabla podanega tipa
	* 
	*/
	function isDemografijaTip ($variabla, $tip) {
		if ( in_array($variabla, $this->seznamcek[$tip]) )
			return true;
			
		return false;
	}
	
	/**
	* Preveri ce je podana variabla v seznamu demografij
	* 
	*/
	function isDemografija ($variabla) {
		
		foreach ($this->seznamcek AS $key => $val)
			if ( $this->isDemografijaTip($variabla, $key) ) return true;
		
		return false;
	}
	
	/**
	* Vrne tip demografije za podano variablo
	* 
	*/
	function getDemografijaTip ($variabla) {
		
		foreach ($this->seznamcek AS $key => $val)
			if ( $this->isDemografijaTip($variabla, $key) ) return $key;
		
		return false;
	}
	
	/**
	* Vrne ID spremenljivke v knjiznici za podano variablo
	* 
	*/
	function getSpremenljivkaID ($variable) {
		
		$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE variable='$variable' AND sistem='1' AND gru_id='-1' LIMIT 1");
		$row = mysqli_fetch_array($sql);
		
		return $row['id'];
	}
	
	/**
	* Vrne seznam vseh demografij istega tipa, kot je podana variabla. Uporabi se pri spremembi vprasanja - ker prikazujemo samo istega tipa
	* 
	*/
	function getSeznam ($variabla) {
		
		$tip = $this->getDemografijaTip($variabla);
		if (!$tip) return false;
		
		return $this->seznamcek[$tip];
		
	}
	
}

?>