<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
/* 	include_once('../exportclases/class.pdfIzvozAnalizaSums.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFrekvenca.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFunctions.php');
	require_once('../exportclases/class.enka.pdf.php'); */
	
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

/** 
 * @desc Class za generacijo latex
 */
class AnalizaCharts extends LatexAnalysisElement {

	var $anketa;					// trenutna anketa
	var $spremenljivka;					// trenutna spremenljivka

	var $headFileName = null;		// pot do header fajla
	
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	
	var $skin;
	var $numbering;
	var $frontpage;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	var $current_loop = 'undefined';
	
	protected $export_format;
	
	
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $export_format='', $sprID = null, $loop = null){	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa['id']) ){

			$this->anketa = $anketa;
			$this->spremenljivka = $sprID;
			$this->export_format = $export_format;
			
			SurveyChart::Init($this->anketa['id']);
			
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa['id']);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();

			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->anketa['id']);
			$this->sessionData = SurveyUserSession::getData('charts');
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}


		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) ){
            SurveyUserSetting::getInstance()->Init($this->anketa['id'], $global_user_id);
            
			$this->skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');
			$this->numbering = SurveyDataSettingProfiles :: getSetting('chartNumbering');
			$this->frontpage = SurveyDataSettingProfiles :: getSetting('chartFP');
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
		
	function displayCharts(){
		global $site_path;
		global $lang;
		
		$chart = '';
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		# polovimo frekvence			
		SurveyAnalysis::getFrequencys();
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();

		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);

		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			// preverjamo ali je meta			
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				 
				// ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {

					// Ce imamo radio tip in manj kot 5 variabel po defaultu prikazemo piechart
					$vars = count($spremenljivka['options']);
					$type = 0;
					if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $vars < 5 )
						$type = 2;
						
					//ce imamo nominalno spremenljivko ali ce je samo 1 variabla nimamo povprecij
					if($spremenljivka['tip'] == 6 && ($spremenljivka['cnt_all'] == 1 || $spremenljivka['skala'] == 1) && $type == 0 )
						$type = 2;
						
						
					if($spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 19 || $spremenljivka['tip'] == 21 || $spremenljivka['tip'] == 22){
						
						$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');

						// ce imamo vklopljeno nastavitev prikaz tabel med grafi (default)
						if($spremenljivka['tip'] == 19){
						
							$_answers = SurveyAnalysis::getAnswers($spremenljivka,10);
							
							// Preverimo ce je prazna in ne izpisujemo praznih
							if($_answers['validCnt'] != 0 || $hideEmpty != 1){								
								//izpis naslova/podnaslova tabele
								$stevilcenje = ($exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
								$title = $stevilcenje . $spremenljivka['naslov'];
								$boldedTitle = $this->returnBold($this->encodeText($title)).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico
								if($spremenljivka['tip'] == 2){
									$boldedSubTitle = $lang['srv_info_checkbox'];
								}
								
								$chart .= $this->returnCentered($boldedTitle.$boldedSubTitle, $this->export_format); //vrni sredinsko poravnana naslov in podnaslova
								//izpis naslova/podnaslova tabele - konec
								
								$sums = new AnalizaSums($this->anketa);								
								$chart .= $sums->sumMultiText($spid, 'sums', $this->export_format);
							}
						}					
						else{						
							$emptyData = false;
							if($hideEmpty == 1){
								
								$emptyData = true;										
								if (count($spremenljivka['grids']) > 0){
									foreach ($spremenljivka['grids'] AS $gid => $grid) {
										
										$_variables_count = count($grid['variables']);
										
										if ($_variables_count > 0 )
										foreach ($grid['variables'] AS $vid => $variable ){
											$_sequence = $variable['sequence'];	# id kolone z podatki
											
											if(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0)
												$emptyData = false;
										}
									}
								}
							}
							
							// Preverimo ce je prazna in ne izpisujemo praznih
							if($emptyData == false || $hideEmpty != 1){
								//pdfIzvozAnalizaFunctions::frequencyVertical($spid, $displayTitle=true);
								//izpis naslova/podnaslova tabele
								$stevilcenje = ($exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
								$title = $stevilcenje . $spremenljivka['naslov'];
								$boldedTitle = $this->returnBold($this->encodeText($title)).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico
								if($spremenljivka['tip'] == 2){
									$boldedSubTitle = $lang['srv_info_checkbox'];
								}														
								$chart .= $this->returnCentered($boldedTitle.$boldedSubTitle, $this->export_format); //vrni sredinsko poravnana naslov in podnaslova
								//izpis naslova/podnaslova tabele - konec
								
								$freq = new AnalizaFreq($this->anketa);								
								$chart .= $freq->frequencyVertical($spid, $this->export_format);
							}
						}		
					}
					elseif( in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20)) ){												
						// Prikazemo posamezen graf						
						$chart .= $this->displayChartsInLatex($spid, $type, $fromCharts=true, $this->anketa, $from='charts', $this, $this->export_format);
					}
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
			
		} // end foreach self::$_HEADERS
		return $chart;
	}
	
	function setUserId($usrId=null) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}
	
	// vrnemo string za prvi in zadnji vnos
	function getEntryDates(){
		global $lang;
		
		$prvi_vnos_date = SurveyInfo::getSurveyFirstEntryDate();
		$prvi_vnos_time = SurveyInfo::getSurveyFirstEntryTime();
		$zadnji_vnos_date = SurveyInfo::getSurveyLastEntryDate();
		$zadnji_vnos_time = SurveyInfo::getSurveyLastEntryTime();
		
		if ($prvi_vnos_date != null) {
			$first = $this->dateFormat($prvi_vnos_date,'j.n.y');
			$first .= $prvi_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->dateFormat($prvi_vnos_time,'G:i')) : '';
		}
		if ($zadnji_vnos_date != null) {
			$last = $this->dateFormat($zadnji_vnos_date,'j.n.y');
			$last .= $zadnji_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->dateFormat($zadnji_vnos_time,'G:i')) : '';
		}
		
		$text = $lang['srv_setting_collectdata_datetime'].$first.' '.$lang['s_to'].' '.$last;
		
		return $text;
	}
	
	function dateFormat($input=null, $format=null) {
		if ($input != '..') {		
			return date($format,strtotime($input));
		} else {
			return '';
		}
	}
	
	function formatNumber($value=null, $digit=0, $sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}

}

?>