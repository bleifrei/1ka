<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');

	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor");
	define("M_ANALIZA_FREQUENCY", "frequency");

	define("FNT_FREESERIF", "freeserif");
	define("FNT_FREESANS", "freesans");
	define("FNT_HELVETICA", "helvetica");

	define("FNT_MAIN_TEXT", FNT_FREESANS);
	define("FNT_QUESTION_TEXT", FNT_FREESANS);
	define("FNT_HEADER_TEXT", FNT_FREESANS);

	define("FNT_MAIN_SIZE", 10);
	define("FNT_QUESTION_SIZE", 9);
	define("FNT_HEADER_SIZE", 10);

	define("RADIO_BTN_SIZE", 3);
	define("CHCK_BTN_SIZE", 3);
	define("LINE_BREAK", 6);

	define ('PDF_MARGIN_HEADER', 8);
	define ('PDF_MARGIN_FOOTER', 12);
	define ('PDF_MARGIN_TOP', 18);
	define ('PDF_MARGIN_BOTTOM', 18);
	define ('PDF_MARGIN_LEFT', 15);
	define ('PDF_MARGIN_RIGHT', 15);
	

/** Class za generacijo latex
 *
 * 
 *
 */
class AnalizaBreak extends LatexAnalysisElement {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $breakClass = null;			// break class
	public $crosstabClass = null;		// crosstab class
	
	var $spr = 0;			// spremenljivka za katero delamo razbitje
	var $seq;				// sekvenca
	
	var $break_percent;		// opcija za odstotke
	
	public $break_charts = 0;	// ali prikazujemo graf ali tabelo
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	protected $texNewLine = '\\\\ ';
	protected $export_format;
	protected $horizontalLineTex = "\\hline ";	
	protected $texBigSkip = '\bigskip';
	protected $spaceBetweenTables = ' \newline \vspace*{1 cm} \newline';
		

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'break'){
		global $site_path;
		global $global_user_id;
		// preverimo ali imamo stevilko ankete
		//if ( is_numeric($anketa) ){
		if ( is_numeric($anketa['id']) ){
			//$this->anketa['id'] = $anketa;
			$this->anketa = $anketa;
			$this->anketa['podstran'] = $podstran;
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		$_GET['a'] = A_ANALYSIS;
		
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData();
		
		#############################################
/* 		// ustvarimo break objekt
		$this->breakClass = new SurveyBreak($this->anketa['id']);
		$this->spr = $this->sessionData['break']['spr'];
		# poiščemo sekvenco
		$this->seq = $this->sessionData['break']['seq'];
		
		$this->break_percent = (isset($this->sessionData['break']['break_percent']) && $this->sessionData['break']['break_percent'] == false) ? false : true; */
		#############################################
		
		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) ){
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}
		else
			return false;
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	// SETTERS && GETTERS

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}
	function getFile($fileName='')
	{
		//Close and output PDF document		
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}


/* 	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	} */


	function displayBreak($forSpr=null, $forSeq=null, $frequencys=null, $breakClass=null, $break_charts=null, $export_format='') {	
		
		$this->breakClass = $breakClass;
		$this->break_charts = $break_charts;
		$this->seq = $forSeq;
		$this->spr = $forSpr;
		$this->export_format = $export_format;
		
		$tabela = '';
		
		// če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		//SurveyTimeProfiles :: printIsDefaultProfile(false);
		
		// če imamo filter ifov ga izpišemo
		//SurveyConditionProfiles:: getConditionString();
		
		// če imamo filter spremenljivk ga izpišemo
		//SurveyVariablesProfiles:: getProfileString(true);
		//SurveyDataSettingProfiles :: getVariableTypeNote();
		
		// če rekodiranje
		//$SR = new SurveyRecoding($this->anketa);
		//$SR -> getProfileString();
		
		// filtriranje po spremenljivkah
		$_FILTRED_VARIABLES = SurveyVariablesProfiles::getProfileVariables(SurveyVariablesProfiles::checkDefaultProfile(), true);
		
 		foreach ($this->breakClass->_HEADERS AS $skey => $spremenljivka) {			
			$spremenljivka['id'] = $skey;
			$tip = $spremenljivka['tip'];

 			if ( is_numeric($tip) 
						&& $tip != 4	#text
						&& $tip != 5	#label
						&& $tip != 8	#datum
						&& $tip != 9	#SN-imena
						&& $tip != 19	#multitext
						&& $tip != 21	#besedilo*
			&& ( count($_FILTRED_VARIABLES) == 0 || (count($_FILTRED_VARIABLES) > 0 && isset($_FILTRED_VARIABLES[$skey]) ))
						) {
				
				$tabela .= $this->displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka);
			} else if ( is_numeric($tip) 
						&& (
								$tip == 4	#text
								|| $tip == 19	#multitext
								|| $tip == 21	#besedilo*
								|| $tip == 20	#multi numer*
						) && ( count($_FILTRED_VARIABLES) == 0 || (count($_FILTRED_VARIABLES) > 0 && isset($_FILTRED_VARIABLES[$skey]) ) )
						) {
				$tabela .= $this->displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka);
			}
		}
		return $tabela;
	}
	
	function displayBreakSpremenljivka($forSpr=null,$frequencys=null,$spremenljivka=null) {
		$tip = $spremenljivka['tip'];
		$skala = $spremenljivka['skala'];
		$tabela = '';
		
		if ($forSpr != $spremenljivka['id']) {
			switch ($tip) {
				# radio, dropdown
				case 1:
				case 3:
					$tabela .= $this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
				#multigrid
				case 6:
					if ($skala == 0) {
						$tabela .= $this->displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka);
					} else {
						$tabela .= $this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
					}
				break;
				# checkbox
				case 2:
					$tabela .= $this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
				#number
				case 7:
				#ranking
				case 17:
				#vsota
				case 18:
				#multinumber
				case 20:
					$tabela .= $this->displayBreakTableNumber($forSpr,$frequencys,$spremenljivka);
				break ;
				case 19:
					$tabela .= $this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
				break ;
				#multicheck
				case 16:
					$tabela .= $this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
				case 4:				
				case 21:
					# po novem besedilo izpisujemo v klasični tabeli
					$tabela .= $this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
				break;
				default:
					$tabela .= $this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
			}
		}
		return $tabela;
	}
	
	function displayBreakTableMgrid($forSpr=null,$frequencys=null,$spremenljivka=null, $creport=false, $ank_id=null, $export_format=null) {
		global $lang;
		$tabela = '';
		$brezHline = $this->getBrezHline($this->export_format);
		if($creport){
			$breakClass =  new SurveyBreak($ank_id);
			$this->breakClass = $breakClass;
			$this->export_format = $export_format;
		}
		//echo "displayBreakTableMgrid funckija</br>";
		//echo "tip vprašanja: ".$spremenljivka['tip']."</br>";
		// Ce izrisujemo graf//
		if($this->break_charts == 1){
			$tabela .= $this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'mgrid');			
		}		
		// Ce izrisujemo tabelo
		else{
			
			$keysCount = count($frequencys);
			$sequences = explode('_',$spremenljivka['sequences']);			
			$forSpremenljivka = $this->breakClass->_HEADERS[$forSpr];
			$tip = $spremenljivka['tip'];
			
			# izračunamo povprečja za posamezne sekvence
			$means = array();
			foreach ($frequencys AS $fkey => $fkeyFrequency) {				
				foreach ($sequences AS $sequence) {					
					$means[$fkey][$sequence] = $this->breakClass->getMeansFromKey($frequencys[$fkey][$sequence]);
				}
			}	
		
			if ($tip != 16 && $tip != 20) {
				if ($tip == 1 || $tip == 3) {
					if (count($spremenljivka['options']) < 15) {
						$rowspan = 2;
						$colspan = count($spremenljivka['options'])+1;
					} else {
						$rowspan = 1;
						$colspan = 1;
					}
				} else {
					$rowspan = 2;
					$colspan = count($sequences);
				}
				
				$singleWidth = floor(200 / $colspan);
				
				$steviloPodstolpcev = $colspan;

				#preverjanje velikosti tabele
				$mejaZaVelikeTabele = 5;
				$velikostTabele = $steviloPodstolpcev;
				if($velikostTabele > $mejaZaVelikeTabele){	//ce imamo veliko tabelo, jo je potrebno razbiti na vec tabel, ker drugace je presiroka
					//echo "tabela je prevelika, ima ".($velikostTabele)." stolpcev</br>";
					$presirokaTabela = 1;
					$steviloTabelCelih = intval($velikostTabele / $mejaZaVelikeTabele);
					$steviloTabelMod = $velikostTabele % $mejaZaVelikeTabele;
					$delnaTabela = 0;
					if($steviloTabelMod != 0){
						$delnaTabela = 1;
					}
					$steviloTabel = $steviloTabelCelih + $delnaTabela;

/* 					echo "stevilo podtabel celih ".($steviloTabelCelih)." </br>";
					echo "stevilo podtabel mod ".($steviloTabelMod)." </br>";
					echo "stevilo podtabel ".($steviloTabel)." </br>"; */
				}else{
					$presirokaTabela = 0;
				}
				#preverjanje velikosti tabele - konec
				
				if($presirokaTabela == 0){	//ce tabela ni presiroka
					//Priprava parametrov za tabelo
					$steviloStolpcevParameterTabular = $steviloPodstolpcev+1;
					$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
					//echo "steviloOstalihStolpcev v funkciji: ".$steviloOstalihStolpcev."</br>";
					$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
					$parameterTabular = '|';
					
					//echo "tukaj";
					for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
						//ce je prvi stolpec
						if($i == 0){
							//$parameterTabular .= ($this->export_format == 'pdf' ? 'P|' : 'l|');
							$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
						}else{
							//$parameterTabular .= ($this->export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
							//$parameterTabular .= ($this->export_format == 'pdf' ? 'C|' : 'c|');
							$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');						
						}			
					}
					//Priprava parametrov za tabelo - konec
					
					//zacetek latex tabele z obrobo	za prvo tabelo	
					$pdfTable = 'tabularx';
					$rtfTable = 'tabular';
					$pdfTableWidth = 1;
					$rtfTableWidth = 1;

					$tabela .= $this->StartLatexTable($this->export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
					if($this->export_format != 'xls'){
						$tabela .= $this->horizontalLineTex; /*obroba*/
					}
					//zacetek latex tabele z obrobo za prvo tabelo - konec
					
					// PRVA VRSTICA
					$prvaVrstica = array();
					$prvaVrstica[] = $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')');
					//$prvaVrstica[] = $this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')');
					//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')').'}';
					//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).')').'}';
					if($this->export_format == 'pdf'){
						$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).')').'}';
					}elseif($this->export_format == 'rtf'){
						$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')').'}';
					}
					
					
					// DRUGA IN TRETJA VRSTICA
					$drugaVrstica = array();
					$drugaVrstica[]='';
					$tretjaVrstica = array();
					$tretjaVrstica[] = '';				
					//echo "tip: ".$tip."</br>";
					
					if ($tip != 1 && $tip != 3) {
						foreach ($spremenljivka['grids'] AS $gkey => $grid) {
							foreach ($grid['variables'] AS $vkey => $variable) {
								$text = $this->encodeText($variable['naslov'].' ('.$variable['variable'].')');
								$drugaVrstica[]=$text;
								$tretjaVrstica[] = $this->encodeText($lang['srv_analiza_crosstab_average']);
							}
						}
					}
					else if (count($spremenljivka['options']) < 15) {
						//echo "options :".count($spremenljivka['options'])."</br>";
						foreach ($spremenljivka['options'] AS $okey => $option) {
							//$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($option.' ('.$okey.')'), 1, 'C', 0, 0, 0 ,0, true);
							$text = $this->encodeText($option.' ('.$okey.')');
							$drugaVrstica[]=$text;
							$tretjaVrstica[] = $this->encodeText($lang['srv_analiza_crosstab_average']);
						}
						//$this->pdf->MultiCell($singleWidth, $height, 'povprečje', 1, 'C', 0, 1, 0 ,0, true);	
					}

					//Izpis vrstic tabele ##################
					$tabela .= $this->tableRow($prvaVrstica,1);	//izpis prve vrstice
					if($this->export_format != 'xls'){				
						$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
					}
					
					$tabela .= $this->tableRow($drugaVrstica,1);	//izpis druge vrstice
					if($this->export_format != 'xls'){
						$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
					}

					$tabela .= $this->tableRow($tretjaVrstica, $brezHline);	//izpis tretje vrstice				
					
					// VRSTICE S PODATKI
					foreach ($frequencys AS $fkey => $fkeyFrequency) {
						$podatkiVrstica = array();
						$podatkiVrstica[]=$this->encodeText($forSpremenljivka['options'][$fkey]);	//naslov horizontalne vrstice
						foreach ($spremenljivka['grids'] AS $gkey => $grid) {
							foreach ($grid['variables'] AS $vkey => $variable) {
								if ($variable['other'] != 1) {
									$sequence = $variable['sequence'];
									if (($tip == 1 || $tip == 3) && count($spremenljivka['options']) < 15) {
										foreach ($spremenljivka['options'] AS $okey => $option) {
											//$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']), 1, 'C', 0, 0, 0 ,0, true);
											$podatkiVrstica[]=$this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']);
											//echo "podatkiVrstica 1 :".$this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt'])."</br>";
										}
									}
									$podatkiVrstica[]=$this->formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
									//echo "podatki v vrstici: ".$this->formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')."</br>";
								}
							}
						}
						$tabela .= $this->tableRow($podatkiVrstica, $brezHline);	//izpis vrstice s podatki
					}
					//Izpis vrstic tabele - konec ##################
					
					/*zakljucek latex tabele*/
					$tabela .= ($this->export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
					/*zaljucek latex tabele - konec */
					//echo "tabela :".$tabela."</br>";
				}elseif($presirokaTabela == 1){	//ce tabela je presiroka
					$izpisaneCeleTabele = 0;
					$indeksPodatkov = 0;
					$indeksPodatkovOld = 0;
					$indeksPodatkov1 = 0;
					$indeksPodatkovOld1 = 0;
					for($p=0; $p<$steviloTabel; $p++){	//ustvarjanje podtabel
						$indeksPodatkov1 = $indeksPodatkov;
						$indeksPodatkovOld1 = $indeksPodatkovOld;
						//Priprava parametrov za tabelo
						if($izpisaneCeleTabele < $steviloTabelCelih){
							$steviloStolpcevParameterTabular = $mejaZaVelikeTabele+1;
							$steviloPodstolpcev = $steviloStolpcevParameterTabular;
						}else{
							$steviloStolpcevParameterTabular = $steviloTabelMod+1;
							$steviloPodstolpcev = $steviloStolpcevParameterTabular;
						}
						$izpisaneCeleTabele++;	//vecanje indeksa za belezenja stevila izpisaih celih tabel, takih, ki so velike 5 + 1 stolpcev						
						$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
						//echo "steviloOstalihStolpcev v funkciji: ".$steviloOstalihStolpcev."</br>";
						$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
						$parameterTabular = '|';
						
						//echo "tukaj";
						for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
							//ce je prvi stolpec
							if($i == 0){
								//$parameterTabular .= ($this->export_format == 'pdf' ? 'P|' : 'l|');
								$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
							}else{
								//$parameterTabular .= ($this->export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
								//$parameterTabular .= ($this->export_format == 'pdf' ? 'C|' : 'c|');
								$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');						
							}			
						}
						//echo "parametri za tabelo: ".$parameterTabular."</br>";
						//Priprava parametrov za tabelo - konec

						//zacetek latex tabele z obrobo	za prvo tabelo	
						$pdfTable = 'tabularx';
						$rtfTable = 'tabular';
						$pdfTableWidth = 1;
						$rtfTableWidth = 1;

						$tabela .= $this->StartLatexTable($this->export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
						if($this->export_format != 'xls'){
							$tabela .= $this->horizontalLineTex; /*obroba*/
						}
						//zacetek latex tabele z obrobo za prvo tabelo - konec
						
						// PRVA VRSTICA
						$prvaVrstica = array();
						$prvaVrstica[] = $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')');
						if($this->export_format == 'pdf'){
							$prvaVrstica[] = '\multicolumn{'.($steviloPodstolpcev-1).'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).')').'}';
						}elseif($this->export_format == 'rtf'){
							$prvaVrstica[] = '\multicolumn{'.($steviloPodstolpcev-1).'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')').'}';
						}						
						
						// DRUGA IN TRETJA VRSTICA
						$drugaVrstica = array();
						$drugaVrstica[]='';
						$tretjaVrstica = array();
						$tretjaVrstica[] = '';				
						//echo "tip: ".$tip."</br>";
						//echo "indeks podatkov prej: ".$indeksPodatkov."</br>";
						
						if ($tip != 1 && $tip != 3) {
							for($s=$indeksPodatkov; $s<($steviloPodstolpcev-1+$indeksPodatkovOld); $s++){
								$grid = $spremenljivka['grids'][$s];
								$text = $this->encodeText($grid['variables'][0]['naslov'].' ('.$grid['variables'][0]['variable'].')');
								//echo "grid podatek: ".$text."</br>";
								$drugaVrstica[] = $text;
								$tretjaVrstica[] = $this->encodeText($lang['srv_analiza_crosstab_average']);
								$indeksPodatkov = $s;
							}
							$indeksPodatkov = $indeksPodatkov + 1;
							$indeksPodatkovOld = $indeksPodatkov;
						}
						else if (count($spremenljivka['options']) < 15) {		//TO-DO: preureditev foreach v for, sem naredil isto kot v starih izvozih, vendar ne razumem, kdaj se to sprozi
							//echo "options :".count($spremenljivka['options'])."</br>";
							//echo "znotraj </br>";
							foreach ($spremenljivka['options'] AS $okey => $option) {
								//$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($option.' ('.$okey.')'), 1, 'C', 0, 0, 0 ,0, true);
								$text = $this->encodeText($option.' ('.$okey.')');
								$drugaVrstica[]=$text;
								$tretjaVrstica[] = $this->encodeText($lang['srv_analiza_crosstab_average']);
							}
							//$this->pdf->MultiCell($singleWidth, $height, 'povprečje', 1, 'C', 0, 1, 0 ,0, true);	
						}

						//Izpis vrstic tabele ##################
						$tabela .= $this->tableRow($prvaVrstica,1);	//izpis prve vrstice
						if($this->export_format != 'xls'){				
							$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
						}
						
						$tabela .= $this->tableRow($drugaVrstica,1);	//izpis druge vrstice
						if($this->export_format != 'xls'){
							$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
						}

						$tabela .= $this->tableRow($tretjaVrstica, $brezHline);	//izpis tretje vrstice				
						//echo "tabela: ".$tabela."</br>";
						
						// VRSTICE S PODATKI
						//print_r($frequencys);
						//echo "vrstice: ".count($frequencys)."</br>";
						//foreach ($frequencys AS $fkey => $fkeyFrequency) {
						$steviloVrsticSPodatki = count($frequencys);

						for($fkey=1; $fkey<=($steviloVrsticSPodatki); $fkey++){	//izpis vsake vrstice posebej
							//echo "indeks freq: ".$fkey."</br>";
							$podatkiVrstica = array();
							$podatkiVrstica[] = $this->encodeText($forSpremenljivka['options'][$fkey]);	//naslov horizontalne vrstice
							//echo "debug text: ".$this->encodeText($forSpremenljivka['options'][$fkey])."</br>";							
							
							//foreach ($spremenljivka['grids'] AS $gkey => $grid) {
							for($s1=$indeksPodatkov1; $s1<($steviloPodstolpcev-1+$indeksPodatkovOld1); $s1++){
								$grid = $spremenljivka['grids'][$s1];
								$variable = $grid['variables'][0];
								if ($variable['other'] != 1) {
									$sequence = $variable['sequence'];
									//echo "sdvsdv </br>";
									/* 	if (($tip == 1 || $tip == 3) && count($spremenljivka['options']) < 15) {
										foreach ($spremenljivka['options'] AS $okey => $option) {
											$podatkiVrstica[] = $this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']);
											//echo "podatki Vrstica 1 :".$this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt'])."</br>";
										}
									} */
									$podatkiVrstica[] = $this->formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
									
									//echo "podatki v vrstici: ".$this->formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')."</br>";
								}
							}
							//print_r($podatkiVrstica);
							$tabela .= $this->tableRow($podatkiVrstica, $brezHline);	//izpis vrstice s podatki
							//echo "indeks podatkov: ".$indeksPodatkov1."</br>";
							//echo "limit for zanke ".(5+$indeksPodatkovOld)." </br>";
							//echo "konec vrstice </br>";
						}
						//Izpis vrstic tabele - konec ##################
						
						/*zakljucek latex tabele*/
						$tabela .= ($this->export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
						/*zaljucek latex tabele - konec */
						//echo "tabela :".$tabela."</br>";

					}


				}
			}else
			{
				$rowspan = 2;
				$colspan = $spremenljivka['grids'][0]['cnt_vars'];
				$singleWidth = floor(200 / $colspan);
				//echo "colspan spodaj: ".$colspan."</br>";
				
				# za multicheck razdelimo na grupe - skupine
				foreach ($frequencys AS $fkey => $frequency) {
					
/* 					$this->pdf->setFont('','B','6');
					$this->pdf->MultiCell(200, 5, $this->encodeText('Tabela za: ('.$forSpremenljivka['variable'].') = '.$forSpremenljivka['options'][$fkey]), 0, 'L', 0, 1, 0 ,0, true); */
					
					
					$text = $spremenljivka['naslov'].' ('.$spremenljivka['variable'].')';
					$height = $this->getCellHeight($text, 260);
					//$this->pdf->MultiCell(260, $height, $this->encodeText($text), 1, 'C', 0, 1, 0 ,0, true);	

					//$this->pdf->setFont('','','6');
					
					
					foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {					
						//$height = ($this->getCellHeight($variable['naslov'], $singleWidth) > $height) ? $this->getCellHeight($variable['naslov'], $singleWidth) : $height;
					}
					
					//$this->pdf->MultiCell(60, $height, '', 1, 'C', 0, 0, 0 ,0, true);
					foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {					
						//$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($variable['naslov']), 1, 'C', 0, 0, 0 ,0, true);
					}
					//$this->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
					
					
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {

						$text = '('.$grid['variable'].') '.$grid['naslov'];
/* 						$height = $this->getCellHeight($text, 60);
						$this->pdf->MultiCell(60, $height,  $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true); */

						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];

							//$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
						}
						//$this->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
					}
					//$this->pdf->ln(10);
				}
			}
		}		
		return $tabela;
	}
	
	function displayBreakTableNumber($forSpr=null,$frequencys=null,$spremenljivka=null, $creport=false, $ank_id=null, $export_format=null) {
		global $lang;
		$tabela = '';
		$brezHline = $this->getBrezHline($this->export_format);
		
		if($creport){
			$breakClass =  new SurveyBreak($ank_id);
			$this->breakClass = $breakClass;
		}
		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = $this->breakClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$means = array();
		$totalMeans = array();
		$totalFreq = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$means[$fkey][$sequence] = $this->breakClass->getMeansFromKey($frequencys[$fkey][$sequence]);
			}
		}
		
		// Ce izrisujemo graf
		if($this->break_charts == 1){
		
			// Number, vsota, ranking graf
			if($tip != 20 ){
				//$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
				$tabela .= $this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
			}
			
			// Multinumber graf
			else{
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				
					// Izrisujemo samo 1 graf v creportu
					if($_GET['m'] == 'analysis_creport'){
						
						if($spremenljivka['break_sub_table']['key'] == $gkey){
							//$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
							$tabela .= $this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
						}
					}
					
					// Izrisujemo vse zaporedne grafe
					else{
						$spremenljivka['break_sub_table']['key'] = $gkey;
						$spremenljivka['break_sub_table']['sequence'] = $grid['variables'][0]['sequence'];
						$tabela .= $this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
					}
				}
			}
		}
		
		// Izrisujemo tabelo
		else{
			# za multi number naredimo po skupinah
			if ($tip != 20) {
			
				$rowspan = 3;
				$colspan = count($sequences);				
				$singleWidth = floor(200 / $colspan);
				
				//Priprava podatkov za prve 3 vrstice tabele
				// DRUGA VRSTICA	prva, ker potrebujemo stevilo elementov v drugi vrstici, da pripravimo izpis prve vrstice in tabele
				$drugaVrstica = array();
				$drugaVrstica[] = '';				
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$drugaVrstica[] = $this->encodeText($variable['naslov'].' ('.$variable['variable'].')');
					}
				}				
				
				// PRVA VRSTICA
				$prvaVrstica = array();
				$prvaVrstica[] = $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')');
				//$prvaVrstica[] = $this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')');	//\multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{X|}
				$steviloPodstolpcev = count($drugaVrstica) - 1;
				//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')').'}';
				//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}
				//{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}
				//{'.$this->encodeText($this->snippet($spremenljivka['naslov']).'('.$this->snippet($spremenljivka['variable']).')').'}';
				if($this->export_format == 'pdf'){
					$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}
					{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}
					{'.$this->encodeText($this->snippet($spremenljivka['naslov']).'('.$this->snippet($spremenljivka['variable']).')').'}';
				}elseif($this->export_format == 'rtf'){
					$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')').'}';
				}
				
				// TRETJA VRSTICA
				$tretjaVrstica = array();
				$tretjaVrstica[] = '';				
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$tretjaVrstica[] = $this->encodeText($lang['srv_analiza_crosstab_average']);
					}
				}
				
				//Priprava podatkov za prve 3 vrstice tabele - konec
				
				//Priprava parametrov za tabelo
				//$steviloStolpcevParameterTabular = 2;
				$steviloStolpcevParameterTabular = count($drugaVrstica);
				$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
				$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
				$parameterTabular = '|';
				
				for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
					//ce je prvi stolpec
					if($i == 0){
						//$parameterTabular .= ($this->export_format == 'pdf' ? 'P|' : 'l|');
						$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
					}else{
						//$parameterTabular .= ($this->export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
						//$parameterTabular .= ($this->export_format == 'pdf' ? 'C|' : 'c|');
						$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
					}			
				}
				//Priprava parametrov za tabelo - konec
				
				//zacetek latex tabele z obrobo	za prvo tabelo	
				$pdfTable = 'tabularx';
				$rtfTable = 'tabular';
				$pdfTableWidth = 1;
				$rtfTableWidth = 1;

				$tabela .= $this->StartLatexTable($this->export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
				if($this->export_format != 'xls'){
					$tabela .= $this->horizontalLineTex; /*obroba*/
				}
				//zacetek latex tabele z obrobo za prvo tabelo - konec

				
				//Izpis vrstic tabele
				$tabela .= $this->tableRow($prvaVrstica,1);	//izpis prve vrstice
				if($this->export_format != 'xls'){				
					$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
				}
				
				$tabela .= $this->tableRow($drugaVrstica,1);	//izpis druge vrstice
				if($this->export_format != 'xls'){				
					$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
				}

				$tabela .= $this->tableRow($tretjaVrstica, $brezHline);	//izpis tretje vrstice
				
				//VRSTICE S PODATKI - priprava in izpis podatkov
				foreach ($frequencys AS $fkey => $fkeyFrequency) {
					$podatkiVrstica = array();
					$podatkiVrstica[]=$this->encodeText($forSpremenljivka['options'][$fkey]);
					
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							if ($variable['other'] != 1) {
								$sequence = $variable['sequence'];
								$podatkiVrstica[]=$this->formatNumber($means[$fkey][$sequence]);
								$totalMeans[$sequence] += ($this->breakClass->getMeansFromKey($fkeyFrequency[$sequence])*(int)$frequencys[$fkey][$sequence]['validCnt']);
								$totalFreq[$sequence]+= (int)$frequencys[$fkey][$sequence]['validCnt'];
							}
						}
					}
					
					$tabela .= $this->tableRow($podatkiVrstica, $brezHline);	//izpis vrstice s podatki
				}
				//VRSTICE S PODATKI - priprava in izpis podatkov - konec

				// dodamo še skupno sumo in povprečje
				$sumaVrstica = array();
				$sumaVrstica[]=$this->encodeText($lang['srv_analiza_crosstab_skupaj']);
				
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
							#povprečja
							$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
							$sumaVrstica[]=$this->formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						}	
					}	
				}

				$tabela .= $this->tableRow($sumaVrstica, $brezHline);	//izpis vrstice s sumo
				
				//Izpis vrstic tabele - konec

				/*zakljucek latex tabele*/
				$tabela .= ($this->export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
				/*zaljucek latex tabele - konec */
			}
			
			else {
				$rowspan = 3;
				$colspan = count($spremenljivka['grids'][0]['variables']);
				$steviloPodstolpcev = $colspan;
				$singleWidth = floor(200 / $colspan);
				# za multinumber razdelimo na grupe - skupine
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {					
					
					//izpis opisnega besedila pred tabelo
					$tabela .= $this->encodeText('Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'].' ('.$grid['variable'].')');
					//$tabela .= $this->texNewLine;
					
					//Priprava parametrov za tabelo
					$steviloStolpcevParameterTabular = $steviloPodstolpcev+1;
					$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
					$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
					$parameterTabular = '|';
					
					for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
						//ce je prvi stolpec
						if($i == 0){
							//$parameterTabular .= ($this->export_format == 'pdf' ? 'P|' : 'l|');
							$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
						}else{
							//$parameterTabular .= ($this->export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
							$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
						}			
					}
					//Priprava parametrov za tabelo - konec
					
					//zacetek latex tabele z obrobo	za prvo tabelo	
					$pdfTable = 'tabularx';
					$rtfTable = 'tabular';
					$pdfTableWidth = 1;
					$rtfTableWidth = 1;

					$tabela .= $this->StartLatexTable($this->export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
					if($this->export_format != 'xls'){
						$tabela .= $this->horizontalLineTex; /*obroba*/
					}
					//zacetek latex tabele z obrobo za prvo tabelo - konec
			
					// PRVA VRSTICA
					$prvaVrstica = array();
					$prvaVrstica[] = $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')');
					//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')').'}';
					//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).') - '.$grid['naslov'].' ('.$grid['variable'].')').'}';
					if($this->export_format == 'pdf'){
						$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).') - '.$grid['naslov'].' ('.$grid['variable'].')').'}';
					}elseif($this->export_format == 'rtf'){
						$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')').'}';
					}
					
					// DRUGA VRSTICA
					$drugaVrstica = array();
					$drugaVrstica[]='';
					foreach ($grid['variables'] AS $vkey => $variable) {
						$text = $this->encodeText($variable['naslov'].' ('.$variable['variable'].')');
						$drugaVrstica[]=$text;
					}
					
					// TRETJA VRSTICA
					$tretjaVrstica = array();
					$tretjaVrstica[] = '';					
					foreach ($grid['variables'] AS $vkey => $variable) {
						$tretjaVrstica[] = $this->encodeText($lang['srv_analiza_crosstab_average']);
					}

					//Izpis vrstic tabele
					$tabela .= $this->tableRow($prvaVrstica,1);	//izpis prve vrstice
					if($this->export_format != 'xls'){					
						$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
					}
					
					$tabela .= $this->tableRow($drugaVrstica,1);	//izpis druge vrstice
					if($this->export_format != 'xls'){					
						$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
					}

					$tabela .= $this->tableRow($tretjaVrstica, $brezHline);	//izpis tretje vrstice			

					// VRSTICE Z VSEBINO
					foreach ($forSpremenljivka['options'] AS $okey => $option) {
						$podatkiVrstica = array();
						$podatkiVrstica[]=$this->encodeText($option);
						//$height = $this->getCellHeight($option, 60);
						//$this->pdf->MultiCell(60, $height,  $this->encodeText($option), 1, 'C', 0, 0, 0 ,0, true);
						
						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];							
							#povprečje
							//$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($means[$okey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
							$podatkiVrstica[]=$this->formatNumber($means[$okey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');

							$totalMeans[$sequence] += ($means[$okey][$sequence]*(int)$frequencys[$okey][$sequence]['validCnt']);
							$totalFreq[$sequence]+= (int)$frequencys[$okey][$sequence]['validCnt'];	
						}
						
						$tabela .= $this->tableRow($podatkiVrstica, $brezHline);	//izpis vrstice s podatki						
					}
					
					// dodamo še skupno sumo in povprečje
					$sumaVrstica = array();
					$sumaVrstica[]=$this->encodeText($lang['srv_analiza_crosstab_skupaj']);
					
					foreach ($grid['variables'] AS $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
								#povprečja
								$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
								$sumaVrstica[]=$this->formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
								
						}	
					}
					$tabela .= $this->tableRow($sumaVrstica, $brezHline);	//izpis vrstice s sumo
					
					//Izpis vrstic tabele - konec
					
					/*zakljucek latex tabele*/
					$tabela .= ($this->export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
					/*zaljucek latex tabele - konec */
				}
			}
		}
		return $tabela;
	}
	
	function displayBreakTableText($forSpr=null,$frequencys=null,$spremenljivka=null, $creport=false, $ank_id=null, $export_format=null) {
		global $lang;		
		$tabela = '';
		$brezHline = $this->getBrezHline($this->export_format);
		if($creport){
			$breakClass =  new SurveyBreak($ank_id);
			$this->breakClass = $breakClass;
		}
		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = $this->breakClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$texts = array();
		$forSequences = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$texts[$fkey][$sequence] = $this->breakClass->getTextFromKey($fkeyFrequency[$sequence]);
			}
		}		
		
		$rowspan = 2;
		$colspan = count($spremenljivka['grids'][0]['variables']);
		
		$steviloPodstolpcev = $colspan;
		
		$singleWidth = floor(200 / $colspan);
			
			
		foreach ($spremenljivka['grids'] AS $gkey => $grid) {
			
			//izpis opisnega besedila pred tabelo
			$tabela .= $this->encodeText('Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'].' ('.$grid['variable'].')');
			
			//Priprava parametrov za tabelo
			$steviloStolpcevParameterTabular = $steviloPodstolpcev+1;
			$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
			$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
			$parameterTabular = '|';
			
			for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
				//ce je prvi stolpec
				if($i == 0){
					//$parameterTabular .= ($this->export_format == 'pdf' ? 'P|' : 'l|');
					$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
				}else{
					//$parameterTabular .= ($this->export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
					//$parameterTabular .= ($this->export_format == 'pdf' ? 'C|' : 'c|');
					$parameterTabular .= ($this->export_format == 'pdf' ? 'X|' : 'l|');
					
				}			
			}
			//Priprava parametrov za tabelo - konec
			
			//zacetek latex tabele z obrobo	za prvo tabelo	
			$pdfTable = 'tabularx';
			$rtfTable = 'tabular';
			$pdfTableWidth = 1;
			$rtfTableWidth = 1;

			$tabela .= $this->StartLatexTable($this->export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
			
			if($this->export_format != 'xls'){
				$tabela .= $this->horizontalLineTex; /*obroba*/
			}
			//zacetek latex tabele z obrobo za prvo tabelo - konec
	
			// PRVA VRSTICA
			$prvaVrstica = array();
			$prvaVrstica[] = $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')');
			//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')').'}';
			//$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).') - '.$this->snippet($grid['naslov']).' ('.$this->snippet($grid['variable']).')').'}';
			if($this->export_format == 'pdf'){
				$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{>{\hsize=\dimexpr '.($steviloPodstolpcev).'\hsize + '.($steviloPodstolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($this->snippet($spremenljivka['naslov']).' ('.$this->snippet($spremenljivka['variable']).') - '.$this->snippet($grid['naslov']).' ('.$this->snippet($grid['variable']).')').'}';
			}elseif($this->export_format == 'rtf'){
				$prvaVrstica[] = '\multicolumn{'.$steviloPodstolpcev.'}{c|}{'.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')').'}';
			}
			
			// DRUGA VRSTICA
			$drugaVrstica = array();
			$drugaVrstica[]='';
			foreach ($grid['variables'] AS $vkey => $variable) {
				$text = $this->encodeText($variable['naslov'].' ('.$variable['variable'].')');
				$drugaVrstica[]=$text;
			}
			
			//Izpis vrstic tabele
			$tabela .= $this->tableRow($prvaVrstica,1);	//izpis prve vrstice
			if($this->export_format != 'xls'){			
				$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."}"; //izpis prekinjene horizontalne crte
			}
			
			$tabela .= $this->tableRow($drugaVrstica, $brezHline);	//izpis druge vrstice
			
			
			// VRSTICE Z VSEBINO
			foreach ($forSpremenljivka['options'] AS $okey => $option) {
				$podatkiVrstica = array();
				$podatkiVrstica[]=$this->encodeText($option);
			
				// Izrisemo vrstico
				//$this->pdf->MultiCell(60, $height,  $this->encodeText($option), 1, 'C', 0, 0, 0 ,0, true);			
				foreach ($grid['variables'] AS $vkey => $variable) {
					$sequence = $variable['sequence'];
					if (count($texts[$okey][$sequence]) > 0) {
						$text = "";
						foreach ($texts[$okey][$sequence] AS $ky => $units) {
							//$text .= $units['text']."\n";
							$text .= $units['text']."; ";
						}
						$text = substr($text,0,-2);
						$podatkiVrstica[]=$this->encodeText($text);
						//$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
					}
					else{
						//$this->pdf->MultiCell($singleWidth, $height, '', 1, 'C', 0, 0, 0 ,0, true);
						$podatkiVrstica[]='';
					}
				}
				$tabela .= $this->tableRow($podatkiVrstica, $brezHline);	//izpis vrstice s podatki
			}			
			//Izpis vrstic tabele - konec
			
			/*zakljucek latex tabele*/
			$tabela .= ($this->export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			/*zaljucek latex tabele - konec */
		}
		return $tabela;
	}
	
	function displayCrosstabs($forSpr=null,$frequencys=null,$spremenljivka=null) {
		global $lang;
		$tabela = '';
		//echo "funkcija displayCrosstabs </br>";
		//print_r($spremenljivka['grids'] );
		//ustvarimo crosstab objekt
		$this->crosstabClass = new SurveyCrosstabs();
		$this->crosstabClass->Init($this->anketa['id']);
		
		$spr1 = $this->spr;
		$seq1 = $this->seq;
		$grd1 = 'undefined';
			
		foreach ($this->breakClass->_HEADERS[$spr1]['grids'] AS $gid => $grid) {			
			foreach ($grid['variables'] AS $vkey => $vrednost) {
				if ($vrednost['sequence'] == $seq1) {
					$grd1 = $gid;
				}
			}
		}
		
		$spr2 = $spremenljivka['id'];
		//echo "spremenljivka, ki jo gledam: ".$spremenljivka['tip']." </br>";
		foreach ($spremenljivka['grids'] AS $gid => $grid) {		
			if (($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6) && $this->break_charts != 1) {
				
				$text = 'Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'];
				
				if ($spremenljivka['tip'] != 6) {
					$text .= ' ('.$grid['variable'].')';
				}
				$tabela .= $this->encodeText($text);
			}
			
			$seq2 = $grid['variables'][0]['sequence'];
			$grd2 = $gid;
			
			$this->crosstabClass->setVariables($seq2,$spr2,$grd2,$seq1,$spr1,$grd1);			
			
			if($this->break_charts == 1){
				$this->crosstabClass->fromBreak = false;
				$tabela .= $this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'crosstab');
			}
			else{				
				$tabela .= $this->displayCrosstabsTable();
			}
		}
		return $tabela;
	}
	
	
	function displayCrosstabsTable() {
		global $lang;
		$tabela = '';
		$crosstab = new AnalizaCrosstab($this->anketa);
		$tabela .= $crosstab->showCrosstabsTable($this->crosstabClass, $this->export_format);		
		return $tabela;
	}
	
	function displayChart($forSpr=null,$frequencys=null,$spremenljivka=null,$type=null){
		global $lang;
		$texImg = '';
		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		if($type == 'crosstab'){
			$chartID = implode('_', $this->crosstabClass->variabla1[0]);
			$chartID .= '_'.$this->crosstabClass->variabla2[0]['seq'].'_'.$this->crosstabClass->variabla2[0]['spr'].'_undefined';
			$chartID .= '_counter_0'/*.$this->crosstabClass->counter*/;			

			$settings = $this->sessionData['crosstab_charts'][$chartID];
		}		
		else{
			if($spremenljivka['tip'] == 20){
				// Preberemo za kateri grid izrisujemo tabelo
				$gkey = $spremenljivka['break_sub_table']['key'];
				
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $spremenljivka['grids'][$gkey]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
			}
			else{
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
			}
			
			$chartID = $spr1.'_'.$spr2;

			$settings = $this->sessionData['break_charts'][$chartID];
		}

		$imgName = $settings['name'];
		if($imgName){
			copy('pChart/Cache/'.$imgName,'pChart/Cache/'.$imgName.'.png');
		}

		// Naslov posameznega grafa
		$title = $spremenljivka['naslov'] . ' ('.$spremenljivka['variable'].')';
		if($spremenljivka['tip'] == 20){		
			$grid = $spremenljivka['grids'][$gkey];
			$subtitle = $grid['naslov'] . ' ('.$grid['variable'].')';
		}
		elseif($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6){
			foreach ($spremenljivka['grids'] AS $gid => $grid) {	
				if($this->crosstabClass->variabla1[0]['seq'] == $grid['variables'][0]['sequence']){
					$subtitle = $grid['naslov'];
					if ($spremenljivka['tip'] != 6) {
						$subtitle .= ' ('.$grid['variable'].')';
					}
					break;
				}
			}
		}
		
		$boldedTitle = $this->returnBold($this->encodeText($title)).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico
		$boldedSubTitle = '';
		if($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6){
			$boldedSubTitle = $this->returnBold($this->encodeText($subtitle)).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico			
		}
		
		if($imgName){
			$texImageOnly = " \\includegraphics[scale=0.75]{".$imgName."} ";	//latex za sliko
		}else{
			$texImageOnly = $lang['srv_export_no_chart'];
		}
		
		$texImage .= $this->returnCentered($boldedTitle.$boldedSubTitle.$texImageOnly); //vrni sredinsko poravnana naslov in slika
		
		return $texImage;
	}
	
	
	/*
		prikazemo posamezno celico s podatki
		$data - vsebina (array)
		$width - sirina celice
		$numRows - stevilo vrstic (1, 2 ali 3)
		frekvence - da ali ne
		$numColumnPercent - stevilo stolpcev pri procentih (1, 2 ali 3)
		$numColumnResidual - stevilo stolpcev pri procentih (1, 2, 3 ali 4)
	*/
	function displayCell($data=null, $width=null, $numRows=null, $frekvence=null, $numColumnPercent=null, $numColumnResidual=null){
		
		$height = ($numRows == 1 ? 8 : 5);
		$fullHeight = ($height == 8 ? $height : $numRows*$height);
		$i=0;
		
		//preberemo pozicijo - zacetek celice
		$y = $this->pdf->GetY();
		$x = $this->pdf->GetX();
		
		$this->pdf->setDrawColor(170, 170, 170);
		
		//izrisemo frekvence
		if($frekvence == 1){
			$this->pdf->MultiCell($width, $height, $this->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		if($numColumnPercent > 0){
			
			$this->pdf->setX($x);
			$singleWidth = round($width/$numColumnPercent);
			
			for($j=1; $j<$numColumnPercent; $j++){
				$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($data[$i]), 1, 'C', 0, 0, 0 ,0, true);				
				$i++;
			}
			$this->pdf->MultiCell($width - (($numColumnPercent-1)*$singleWidth), $height, $this->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		if($numColumnResidual > 0){
			
			$this->pdf->setX($x);
			$singleWidth = round($width/$numColumnResidual);
			
			for($j=1; $j<$numColumnResidual; $j++){
				$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($data[$i]), 1, 'C', 0, 0, 0 ,0, true);				
				$i++;
			}
			$this->pdf->MultiCell($width - (($numColumnResidual-1)*$singleWidth), $height, $this->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		//zaradi preglednosti narisemo okvir celotne celice
		$this->pdf->setDrawColor(0, 0, 0, 255);
		$this->pdf->SetXY($x, $y);
		$this->pdf->MultiCell($width, $fullHeight, '', 1, 'C', 0, 1, 0 ,0, true);		
		
		//na koncu nastavimo pozicijo na pravo mesto
		$this->pdf->SetXY($x+$width, $y);	
	}
	
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text=null,$length=64,$tail="...")
	{
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length)
		{
			for($i=1;$text[$length-$i]!=" ";$i++)
			{
				if($i == $length)
				{
					return substr($text,0,$length) . $tail;
				}
			}
		$text = substr($text,0,$length-$i+1) . $tail;
		}
		return $text;
	}

	function drawLine()
	{
		$cy = $this->pdf->getY();
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
	}

	function setUserId($usrId=null) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

/* 	function formatNumber($value,$digit=0,$sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	} */

	function getCellHeight($string=null, $width=null){
				
		$this->pdf->startTransaction();
		// get the number of lines calling you method
		$linecount = $this->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();
			
		$height = ($linecount <= 1) ? 4.7 : $linecount * ($this->pdf->getFontSize() * $this->pdf->getCellHeightRatio()) + 2;
		
		return $height;
	}
}

?>