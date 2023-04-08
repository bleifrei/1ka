<?php

/**
* 
*  Inline nacin urejanja vprasanja
* 
*/

class VprasanjeInline {
	
	var $anketa;                // trenutna anketa
	var $spremenljivka;			// spremenljivka ki jo urejamo
	
	var $db_table = '';
	var $expanded = 0;
	
	/**
	* konstruktor
	* 
	* @param mixed $anketa
	* @return Vprasanje
	*/
	function __construct ($anketa = 0) {
		
		if (isset ($_GET['anketa']))
			$this->anketa = $_GET['anketa'];
		elseif (isset ($_POST['anketa'])) 
			$this->anketa = $_POST['anketa'];
		elseif ($anketa != 0) 
			$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);

		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
		if (SurveyInfo::getInstance()->getSurveyColumn('expanded') == 1)
			$this->expanded = 1;
	}
	
	
	/**
	* pohendla ajax klice za vprasanje
	* 
	*/
	function ajax () {
		
		if (isset($_POST['spremenljivka'])) $this->spremenljivka = $_POST['spremenljivka'];
		
		// genericna resitev za vse nadaljne
		$ajax = 'ajax_' . $_GET['a'];
		
		if ( method_exists('VprasanjeInline', $ajax) )
			$this->$ajax();
		else
			echo 'method '.$ajax.' does not exist';
	}
	
	function ajax_inline_vrednost_naslov_save () {
		Common::updateEditStamp();

		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$naslov = $_POST['naslov'];
		$lang_id = $_POST['lang_id'];
		
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($naslov, -4) == '<br>') {
			$naslov = substr($naslov, 0, -4);
		}
		
		$vrednost = $_POST['vrednost'];
		$v = explode('_', $vrednost);
		$vrednost = $v[0];
		$vrednost2 = $v[1];	// naslov2 za diferencial

		$purifier = New Purifier();
        $naslov = $purifier->purify_DB($naslov);

		if ( ! $vrednost > 0 ) return;
		
		if ($vrednost2 == '2')
			$_naslov = 'naslov2';
		else
			$_naslov = 'naslov';
		
		
		// Navadno popravljanje (ne prevajanje)
		if ($lang_id == 0) {
			$s = sisplet_query("UPDATE srv_vrednost SET $_naslov='$naslov' WHERE id = '$vrednost'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		} 
		// Prevajamo za drug jezik
		else {		
			// Semanticni diferencial posebej obravnavamo (naslov ali naslov2)
			//if($row['tip'] == 6 && $row['enota'] == 1){
			if($row['tip'] == 6 && ($row['enota'] == 1 || $row['enota'] == 4)){	//diferencial, one against another
				// Ce popravljamo naslov2
				if($vrednost2 == '2'){
					$s = sisplet_query("INSERT INTO srv_language_vrednost 
										(ank_id, vre_id, lang_id, naslov2) VALUES ('$this->anketa', '$vrednost', '$lang_id', '$naslov')
										ON DUPLICATE KEY UPDATE naslov2='$naslov'");
				}
				else{
					$s = sisplet_query("INSERT INTO srv_language_vrednost 
										(ank_id, vre_id, lang_id, naslov) VALUES ('$this->anketa', '$vrednost', '$lang_id', '$naslov')
										ON DUPLICATE KEY UPDATE naslov='$naslov'");
				}
			}
			else{
				if ($naslov != '')
					$s = sisplet_query("REPLACE INTO srv_language_vrednost (ank_id, vre_id, lang_id, naslov) VALUES ('$this->anketa', '$vrednost', '$lang_id', '$naslov')");
				else
					$s = sisplet_query("DELETE FROM srv_language_vrednost WHERE ank_id='$this->anketa' AND vre_id='$vrednost' AND lang_id='$lang_id'");				
			}
		}
		
		
		if ($row['tip'] == 24) {
			$v = new Vprasanje($this->anketa);
			$v->repare_grid_multiple($this->spremenljivka);
		}
		
		echo mysql_real_unescape_string($naslov);
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_hotspot_vrednost_save () {
		Common::updateEditStamp();
		
		$naslov = $_POST['naslov'];
		$lang_id = $_POST['lang_id'];
		
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($naslov, -4) == '<br>') {
			$naslov = substr($naslov, 0, -4);
		}
		
		$vrednost = $_POST['vrednost'];
		$v = explode('_', $vrednost);
		$vrednost = $v[0];
		$vrednost2 = $v[1];	// naslov2 za diferencial
		
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
		
		if ( ! $vrednost > 0 ) return;
		
		if ($vrednost2 == '2')
			$_naslov = 'naslov2';
		else
			$_naslov = 'naslov';
		
		if ($lang_id == 0) {
			//$s = sisplet_query("UPDATE srv_vrednost SET $_naslov='$naslov' WHERE id = '$vrednost'");
			$s = sisplet_query("UPDATE srv_vrednost SET naslov='$naslov' WHERE id = '$vrednost'");
			$sR = sisplet_query("UPDATE srv_hotspot_regions SET region_name='$naslov' WHERE vre_id = '$vrednost'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		} else {
			if ($naslov != '')
				$s = sisplet_query("REPLACE INTO srv_language_vrednost (ank_id, vre_id, lang_id, naslov) VALUES ('$this->anketa', '$vrednost', '$lang_id', '$naslov')");
			else
				$s = sisplet_query("DELETE FROM srv_language_vrednost WHERE ank_id='$this->anketa' AND vre_id='$vrednost' AND lang_id='$lang_id'");
		}
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		if ($row['tip'] == 24) {
			$v = new Vprasanje($this->anketa);
			$v->repare_grid_multiple($this->spremenljivka);
		}
		
		echo mysql_real_unescape_string($naslov);
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_grid_naslov_save () {
		Common::updateEditStamp();
		
		$naslov = $_POST['naslov'];
		$lang_id = $_POST['lang_id'];
		
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
    	
		$grid = $_POST['grid'];
		
		if ($lang_id == 0) {
			$s = sisplet_query("UPDATE srv_grid SET naslov='$naslov' WHERE id='$grid' AND spr_id='$this->spremenljivka'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
			// dvojni gridi
			$row = Cache::srv_spremenljivka($this->spremenljivka);
			if ($row['enota'] == 3) {
				$sql = sisplet_query("SELECT id, spr_id FROM srv_grid WHERE spr_id='$this->spremenljivka' AND other!=0 AND part=1");
				$s = sisplet_query("UPDATE srv_grid SET naslov='$naslov' WHERE id='".($grid + $row['grids'] + mysqli_num_rows($sql))."' AND spr_id='$this->spremenljivka'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
        }
        else {
			
			//if ($naslov != '')
				$s = sisplet_query("INSERT INTO srv_language_grid 
                                        (ank_id, spr_id, grd_id, lang_id, naslov) 
                                        VALUES 
                                        ('$this->anketa', '$this->spremenljivka', '$grid', '$lang_id', '$naslov')
                                    ON DUPLICATE KEY UPDATE naslov='$naslov'");
			//else
			//	$s = sisplet_query("DELETE FROM srv_language_grid WHERE ank_id='$this->anketa' AND spr_id='$this->spremenljivka' AND grd_id='$grid' AND lang_id='$lang_id'");*/
		}
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_grid_variable_save () {
		Common::updateEditStamp();
		
		$variable = $_POST['variable'];
		
		$grid = $_POST['grid'];
		
		$s = sisplet_query("UPDATE srv_grid SET variable='$variable' WHERE id='$grid' AND spr_id='$this->spremenljivka'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		/*// dvojni gridi
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		if ($row['enota'] == 3) {
			$s = sisplet_query("UPDATE srv_grid SET naslov='$naslov' WHERE id='".($grid + $row['grids'])."' AND spr_id='$this->spremenljivka'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}*/
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_grid_subtitle_save () {
		Common::updateEditStamp();
		
		$subtitle = $_POST['subtitle'];		
		$value = $_POST['value'];
		$lang_id = $_POST['lang_id'];
		$grid_id = $_POST['grid_id'];
		
		$purifier = New Purifier();
    	$subtitle = $purifier->purify_DB($subtitle);
		

		// Navadno popravljanje (ne prevajanje)
		if ($lang_id == 0) {
			$s = sisplet_query("UPDATE srv_spremenljivka SET $subtitle='$value' WHERE id='$this->spremenljivka'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		} 
		// Prevajamo podnaslove za drug jezik
		else{
			//if ($value != '')
				$s = sisplet_query("INSERT INTO srv_language_grid 
                                        (ank_id, spr_id, grd_id, lang_id, podnaslov) 
                                        VALUES 
                                        ('$this->anketa', '$this->spremenljivka', '$grid_id', '$lang_id', '$value')
                                    ON DUPLICATE KEY UPDATE podnaslov='$value'");
			/*else
				$s = sisplet_query("DELETE FROM srv_language_grid WHERE ank_id='$this->anketa' AND spr_id='$this->spremenljivka' AND grd_id='$grid_id' AND lang_id='$lang_id'");*/
		}

		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_vrednost_new () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		global $lang;
		
		$naslov = '';
		//$other = $_POST['other'];
		//$mv = $_POST['mv'];

		$v = new Vprasanje($this->anketa);
		$v->spremenljivka = $this->spremenljivka;
		$vrednost = $v->vrednost_new($naslov /*, $other, $mv*/);
		
		Common::prestevilci($this->spremenljivka);
		
		//$b = new Branching($this->anketa);
		//$b->vprasanje($this->spremenljivka);
		
		echo $vrednost;
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_vrednost_vrstni_red () {
		Common::updateEditStamp();
		
		$sortable = $_POST['sortable'];
		print_r($sortable);
		$exploded = explode('&', $sortable);

		$i = 1;
		foreach ($exploded AS $key) {
			$key = str_replace('variabla_', '', $key);
			$explode = explode('[]=', $key);
			$sql = sisplet_query("UPDATE srv_vrednost SET vrstni_red = '$i' WHERE id = '$explode[1]'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			$i++;
		}

		Common::prestevilci($this->spremenljivka);
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_info_save() {
		Common::updateEditStamp();
		
		$info = $_POST['info'];
		$lang_id = $_POST['lang_id'];
		
		// Po�istimo opombo
		$info = trim(strip_tags($info));
		
		if ($lang_id == 0) {
			sisplet_query("UPDATE srv_spremenljivka SET info='$info' WHERE id = '$this->spremenljivka'");
		} 
		else {			
			//if ($info != '')
			sisplet_query("INSERT INTO srv_language_spremenljivka (ank_id, spr_id, lang_id, info) VALUES ('$this->anketa', '$this->spremenljivka', '$lang_id', '$info') ON DUPLICATE KEY UPDATE info='$info'");
			//else
			//	sisplet_query("DELETE FROM srv_language_spremenljivka WHERE ank_id='$this->anketa' AND spr_id='$this->spremenljivka' AND lang_id='$lang_id'");		
		}
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_variable_save() {
		Common::updateEditStamp();
		
		$variable = $_POST['variable'];
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		if ( in_array($row['variable'], array('email','telefon','ime','priimek','naziv','drugo')) && $row['sistem']==1 ) {
				
			// tukaj ne pustimo spremeniti
			
		} else {	
			if ($variable != $row['variable'])
				sisplet_query("UPDATE srv_spremenljivka SET variable='$variable', variable_custom='1' WHERE id = '$this->spremenljivka'");
		}
		
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_vrednost_variable_save () {
		Common::updateEditStamp();
		
		$variable = $_POST['variable'];
		$vre_id = $_POST['vre_id'];
		
		$sql = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$vre_id'");
		$row = mysqli_fetch_array($sql);
		
		if ($row['variable'] != $variable) {
			sisplet_query("UPDATE srv_vrednost SET variable='$variable', variable_custom='1' WHERE id = '$vre_id'");
		}	
	}
	
	function ajax_inline_label_save () {
		Common::updateEditStamp();
		
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);				
		
		$purifier = New Purifier();    	
		$Label = $purifier->purify_DB($_POST['label']);
		//$Label = $_POST['label'];
		$TipLabele = $_POST['tiplabele'];
		//error_log("TipLabele: ".$TipLabele);
		$lang_id = $_POST['lang_id'];
		
		$TrenutnaLabela = ($spremenljivkaParams->get($TipLabele) ? $spremenljivkaParams->get($TipLabele) : '');
		//error_log("TrenutnaLabela: ".$TrenutnaLabela);
		
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($Label, -4) == '<br>') {
			$Label = substr($Label, 0, -4);
		}
		
		if ($lang_id == 0) {
			$sql = sisplet_query("SELECT params FROM srv_spremenljivka WHERE id = '$this->spremenljivka'"); //poberi trenutne params iz baze
			$row = mysqli_fetch_array($sql);

			if($TrenutnaLabela != ''){
				$TipLabelepart1 = strchr($row['params'], $TipLabele."="); //iz stringa, kjer so vsi params izlusci le string od TipLabele dalje
				//print_r (explode("\n",$row['params'])); //explode(separator,string,limit)
				$newTipLabele = $TipLabele.'='.$Label;	//formiraj nov tip labele z labelo
				$explodedparams = explode("\n",$row['params']); //spremeni string v array
				for($i=0; $i<=sizeof($explodedparams);  $i++){	//za vse elemente array-a
					//echo 'Sem v for zanki';
					if((strstr($explodedparams[$i], $TipLabele.'=') != '')){ //ce element array-a vsebuje besedilo z ustreznim tipom labele //strstr(string,search,before_search)
						//echo 'Smo dobili string na indeksu: '.$i;
						$indeks = $i;	//zabele�i indeks
						//echo 'Indeks1: '.$indeks;
						//$explodedparams[$i] = $newTipLabele;	//povozi ustrezen element array-a z novim tipom labele
						//echo 'Exploded: '.$explodedparams[$i];
					}					
				}			
				
				//echo 'Indeks2: '.$indeks;
				$explodedparams[$indeks] = $newTipLabele;	//povozi ustrezen element array-a z novim tipom labele
				//print_r ($explodedparams);
				$newparams = implode("\n",$explodedparams);	//zdruzi array ponovno v string
				//echo 'New params: '.$newparams;
				$s = sisplet_query("UPDATE srv_spremenljivka SET params = '$newparams' WHERE id='$this->spremenljivka'"); //posodobi params
			}
			elseif ($TrenutnaLabela == ''){
				$s = sisplet_query("UPDATE srv_spremenljivka SET params = CONCAT( srv_spremenljivka.params , '\n$TipLabele=$Label ') WHERE id='$this->spremenljivka'"); //k obstoje�im params dodaj �e naslednje
				//echo $Label;
				//print_r (explode("\n",$row['params'])); //explode(separator,string,limit)
			}
		}else{
			if($TipLabele == 'MinLabel'){
				$grid = 1;
			}elseif($TipLabele =='MaxLabel'){
				$grid = 2;
			}else{
				$grid = 0;
			}
			
			if ($TrenutnaLabela != ''){
				$sString = "REPLACE INTO srv_language_slider (ank_id, spr_id, label_id, lang_id, label) VALUES ('$this->anketa', '$this->spremenljivka', '$grid', '$lang_id', '$Label')";
			}else{
				$sString = "DELETE FROM srv_language_slider WHERE ank_id='$this->anketa' AND spr_id='$this->spremenljivka' AND label_id='$grid' AND lang_id='$lang_id'";
			}
			
			if($grid == 0){	//ce je prevod custom opisnih label
				$sString = "REPLACE INTO srv_language_slider (ank_id, spr_id, label_id, lang_id, label) VALUES ('$this->anketa', '$this->spremenljivka', '$grid', '$lang_id', '$Label')";
			}			
			//error_log($sString);
			$s = sisplet_query($sString);
		}

		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		Vprasanje::vprasanje_tracking();

	}

	function ajax_inline_nadnaslov_save () {
		Common::updateEditStamp();

		$anketa = $_POST['anketa'];
		$naslov = $_POST['label'];
		$lang_id = $_POST['lang_id'];
		
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
    	
		$grid = $_POST['grid'];
		
 		if ($lang_id != 0) {
			if ($naslov != ''){
				$s = sisplet_query("REPLACE INTO srv_language_grid (ank_id, spr_id, grd_id, lang_id, naslov) VALUES ('$anketa', '$this->spremenljivka', '$grid', '$lang_id', '$naslov')");
				//$s = sisplet_query("INSERT INTO srv_language_grid (ank_id, spr_id, grd_id, lang_id, naslov) VALUES ('$anketa', '$this->spremenljivka', '$grid', '$lang_id', '$naslov')");
			}
			else{
				$s = sisplet_query("DELETE FROM srv_language_grid WHERE ank_id='$anketa' AND spr_id='$this->spremenljivka' AND grd_id='$grid' AND lang_id='$lang_id'");
			}

		}elseif($lang_id == 0){
			$row = Cache::srv_spremenljivka($this->spremenljivka);
			$spremenljivkaParams = new enkaParameters($row['params']);				
			
			$Label = $_POST['label'];
			$TipLabele = $_POST['tiplabele'];
			
			$TrenutnaLabela = ($spremenljivkaParams->get($TipLabele) ? $spremenljivkaParams->get($TipLabele) : '');
			
			// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
			if (substr($Label, -4) == '<br>') {
				$Label = substr($Label, 0, -4);
			}
		

			$sql = sisplet_query("SELECT params FROM srv_spremenljivka WHERE id = '$this->spremenljivka'"); //poberi trenutne params iz baze
			$row = mysqli_fetch_array($sql);
				
				
			if($TrenutnaLabela != ''){
				//echo "Update labele";
				$TipLabelepart1 = strchr($row['params'], $TipLabele."="); //iz stringa, kjer so vsi params izlusci le string od TipLabele dalje
				//print_r (explode("\n",$row['params'])); //explode(separator,string,limit)
				$newTipLabele = $TipLabele.'='.$Label;	//formiraj nov tip labele z labelo
				$explodedparams = explode("\n",$row['params']); //spremeni string v array
				for($i=0; $i<=sizeof($explodedparams);  $i++){	//za vse elemente array-a
					//echo 'Sem v for zanki';
					if((strstr($explodedparams[$i], $TipLabele.'=') != '')){ //ce element array-a vsebuje besedilo z ustreznim tipom labele //strstr(string,search,before_search)
						//echo 'Smo dobili string na indeksu: '.$i;
						$indeks = $i;	//zabele�i indeks
						//echo 'Indeks1: '.$indeks;
						//$explodedparams[$i] = $newTipLabele;	//povozi ustrezen element array-a z novim tipom labele
						//echo 'Exploded: '.$explodedparams[$i];
					}					
				}			
				
				//echo 'Indeks2: '.$indeks;
				$explodedparams[$indeks] = $newTipLabele;	//povozi ustrezen element array-a z novim tipom labele
				//print_r ($explodedparams);
				$newparams = implode("\n",$explodedparams);	//zdruzi array ponovno v string
				//echo 'New params: '.$newparams;
				$s = sisplet_query("UPDATE srv_spremenljivka SET params = '$newparams' WHERE id='$this->spremenljivka'"); //posodobi params
			}
			elseif ($TrenutnaLabela == ''){
				$s = sisplet_query("UPDATE srv_spremenljivka SET params = CONCAT( srv_spremenljivka.params , '\n$TipLabele=$Label ') WHERE id='$this->spremenljivka'"); //k obstoje�im params dodaj �e naslednje
				//echo $Label;
				//print_r (explode("\n",$row['params'])); //explode(separator,string,limit)
				//echo "Trenutna labela je prazna";
			}
		}
		
/* 		error_log($anketa);
		error_log($grid);
		error_log($naslov); */
		
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
		Vprasanje::vprasanje_tracking();
	}
	
	
	function ajax_inline_labele_podrocij_save () {
		Common::updateEditStamp();
		
		#############################
		$anketa = $_POST['anketa'];
		$naslov = $_POST['naslov'];
		$lang_id = $_POST['lang_id'];
		
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
    	
		$grid = $_POST['grid'];
		
		############################
		
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);				
		
		$Label = $_POST['label'];
		$TipLabele = $_POST['tiplabele'];
		
		$TrenutnaLabela = ($spremenljivkaParams->get($TipLabele) ? $spremenljivkaParams->get($TipLabele) : '');
		
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($Label, -4) == '<br>') {
			$Label = substr($Label, 0, -4);
		}
	

		$sql = sisplet_query("SELECT params FROM srv_spremenljivka WHERE id = '$this->spremenljivka'"); //poberi trenutne params iz baze
		$row = mysqli_fetch_array($sql);
			
			
		if($TrenutnaLabela != ''){
			//echo "Update labele";
			$TipLabelepart1 = strchr($row['params'], $TipLabele."="); //iz stringa, kjer so vsi params izlusci le string od TipLabele dalje
			//print_r (explode("\n",$row['params'])); //explode(separator,string,limit)
			$newTipLabele = $TipLabele.'='.$Label;	//formiraj nov tip labele z labelo
			$explodedparams = explode("\n",$row['params']); //spremeni string v array
			for($i=0; $i<=sizeof($explodedparams);  $i++){	//za vse elemente array-a
				//echo 'Sem v for zanki';
				if((strstr($explodedparams[$i], $TipLabele.'=') != '')){ //ce element array-a vsebuje besedilo z ustreznim tipom labele //strstr(string,search,before_search)
					//echo 'Smo dobili string na indeksu: '.$i;
					$indeks = $i;	//zabele�i indeks
					//echo 'Indeks1: '.$indeks;
					//$explodedparams[$i] = $newTipLabele;	//povozi ustrezen element array-a z novim tipom labele
					//echo 'Exploded: '.$explodedparams[$i];
				}					
			}			
			
			//echo 'Indeks2: '.$indeks;
			$explodedparams[$indeks] = $newTipLabele;	//povozi ustrezen element array-a z novim tipom labele
			//print_r ($explodedparams);
			$newparams = implode("\n",$explodedparams);	//zdruzi array ponovno v string
			//echo 'New params: '.$newparams;
			$s = sisplet_query("UPDATE srv_spremenljivka SET params = '$newparams' WHERE id='$this->spremenljivka'"); //posodobi params
		}
		elseif ($TrenutnaLabela == ''){
			$s = sisplet_query("UPDATE srv_spremenljivka SET params = CONCAT( srv_spremenljivka.params , '\n$TipLabele=$Label ') WHERE id='$this->spremenljivka'"); //k obstoje�im params dodaj �e naslednje
			//echo $Label;
			//print_r (explode("\n",$row['params'])); //explode(separator,string,limit)
			//echo "Trenutna labela je prazna";
		}
		
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
		Vprasanje::vprasanje_tracking();

	}
	
	
	function ajax_inline_opisne_labele_save () {
		Common::updateEditStamp();
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);

		$Label = $_POST['label'];
		$TipLabele = $_POST['tiplabele'];
		
		$TrenutnaLabela = ($spremenljivkaParams->get($TipLabele) ? $spremenljivkaParams->get($TipLabele) : '');
		
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($Label, -4) == '<br>') {
			$Label = substr($Label, 0, -4);
		}
	
/*  		error_log("Label: ".$Label);
		error_log("TipLabele: ".$TipLabele);
		error_log("spr: ".$this->spremenljivka);	 */	

		$spremenljivkaParams->set($TipLabele, $Label);
		
		
		$params = $spremenljivkaParams->getString();
		$update .= " params = '$params' ";
		
		$sqlString = "UPDATE srv_spremenljivka SET $update WHERE id = '$this->spremenljivka'";
		//error_log($sqlString);
        sisplet_query($sqlString);		
		
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);

		Vprasanje::vprasanje_tracking();

	}
	
	function ajax_inline_variabla_vsota_save () {
		Common::updateEditStamp();
		
		$vsota = $_POST['inline_variabla_vsota'];
		
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($vsota, -4) == '<br>') {
			$vsota = substr($vsota, 0, -4);
		}

		$s = sisplet_query("UPDATE srv_spremenljivka SET vsota='$vsota' WHERE id='$this->spremenljivka'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
		Vprasanje::vprasanje_tracking();
	}
	
	function ajax_inline_hotspot_delete_region () {
		Common::updateEditStamp();
		
		$spr_id = $_POST['spr_id'];
		$vre_id = $_POST['vre_id'];
		$region_index = $_POST['region_index'];

		//zbrisi podatke o obstojecem obmocju
		$s = sisplet_query("DELETE FROM srv_hotspot_regions WHERE spr_id='$spr_id' AND region_index='$region_index'");
		$v = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id' AND id='$vre_id'");		
		Common::repareVrednost($spr_id);
	}
	
	function ajax_inline_hotspot_update_region () {
		Common::updateEditStamp();
		
		$spr_id = $_POST['spr_id'];
		//$vre_id = $_POST['vre_id'];
		
		//posodobi podatke o obstojecih obmocjih po brisanju obmocja
		$sqlv = sisplet_query("SELECT id, variable, vrstni_red FROM srv_vrednost WHERE spr_id = $spr_id");
		while($rowv = mysqli_fetch_array($sqlv)){
			$vre_id_V = $rowv['id'];
			$variable = $rowv['variable'];
			$vrstni_red = $rowv['vrstni_red'];
			//echo $variable;
			$sqlR = sisplet_query("UPDATE srv_hotspot_regions SET variable = '$variable', vrstni_red = '$vrstni_red' WHERE spr_id='$spr_id' AND vre_id = $vre_id_V");
		}

		//$s = sisplet_query("DELETE FROM srv_hotspot_regions WHERE spr_id='$spr_id' AND region_index='$region_index'");
		//$v = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id' AND id='$vre_id'");		
	}
}
	
?>