<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.pdfIzvozAnalizaSums.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFrekvenca.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFunctions.php');
	require_once('../exportclases/class.enka.pdf.php');
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2, true); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2, true); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);

	define("FNT_FREESERIF", "freeserif", true);
	define("FNT_FREESANS", "freesans", true);
	define("FNT_HELVETICA", "helvetica", true);

	define("FNT_MAIN_TEXT", FNT_FREESANS, true);
	define("FNT_QUESTION_TEXT", FNT_FREESANS, true);
	define("FNT_HEADER_TEXT", FNT_FREESANS, true);

	define("FNT_MAIN_SIZE", 10, true);
	define("FNT_QUESTION_SIZE", 9, true);
	define("FNT_HEADER_SIZE", 10, true);

	define("RADIO_BTN_SIZE", 3, true);
	define("CHCK_BTN_SIZE", 3, true);
	define("LINE_BREAK", 6, true);

	define ('PDF_MARGIN_HEADER', 8);
	define ('PDF_MARGIN_FOOTER', 12);
	define ('PDF_MARGIN_TOP', 18);
	define ('PDF_MARGIN_BOTTOM', 18);
	define ('PDF_MARGIN_LEFT', 15);
	define ('PDF_MARGIN_RIGHT', 15);

/** 
 * @desc Class za generacijo pdf-a
 */
class PdfIzvozAnalizaCharts {

	var $anketa;					// trenutna anketa
	var $spremenljivka;					// trenutna spremenljivka

	var $headFileName = null;		// pot do header fajla
	var $dataFileName = null;		// pot do data fajla
	var $dataFileStatus = null;		// status data datoteke
	var $CID = null;				// class za inkrementalno dodajanje fajlov
	
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	
	var $skin;
	var $numbering;
	var $frontpage;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	var $current_loop = 'undefined';
	
	
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $sprID = null, $loop = null)
	{	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){

			$this->anketa = $anketa;
			$this->spremenljivka = $sprID;
			
			SurveyAnalysis::Init($this->anketa);
			SurveyAnalysis::$setUpJSAnaliza = false;
			
			SurveyChart::Init($this->anketa);
			
			// create new PDF document
			$this->pdf = new enka_TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
	
			SurveyZankaProfiles :: Init($this->anketa, $global_user_id);
			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
			
			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->anketa);
			$this->sessionData = SurveyUserSession::getData('charts');
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa) && $this->init())
		{
			SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);
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
	function getFile($fileName)
	{
		//Close and output PDF document
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}


	function init()
	{
		global $lang;
		
		// array used to define the language and charset of the pdf file to be generated
		$language_meta = Array();
		$language_meta['a_meta_charset'] = 'UTF-8';
		$language_meta['a_meta_dir'] = 'ltr';
		$language_meta['a_meta_language'] = 'sl';
		$language_meta['w_page'] = $lang['page'];

		//set some language-dependent strings
	    $this->pdf->setLanguageArray($language_meta);

		//set margins
		$this->pdf->setPrintHeaderFirstPage(true);
		$this->pdf->setPrintFooterFirstPage(true);
		$this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set header and footer fonts
		$this->pdf->setHeaderFont(Array(FNT_HEADER_TEXT, "I", FNT_HEADER_SIZE));
		$this->pdf->setFooterFont(Array(FNT_HEADER_TEXT, 'I', FNT_HEADER_SIZE));


		// set document information
		$this->pdf->SetAuthor('An Order Form');
		$this->pdf->SetTitle('An Order');
		$this->pdf->SetSubject('An Order');

		// set default header data
		$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()));
		
		//nastavimo datum za footer
		$today = date("d.m.y"); 		
		$this->pdf->SetFooterDate($today);

		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$this->pdf->SetFont(FNT_MAIN_TEXT, '', FNT_MAIN_SIZE);
		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		return true;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		return strip_tags($text);
	}

	function createPdf(){		
		global $site_path;
		global $lang;
			
		// izpisemo prvo stran
		if($this->frontpage == 1)
			$this->createFrontPage();
	   		
		$this->pdf->AddPage();
		
		if($this->frontpage != 1){
			$this->pdf->setFont('','B','11');
			$this->pdf->MultiCell(150, 5, $lang['export_analisys_charts'], 0, 'L', 0, 1, 0 ,0, true);
			$this->pdf->setFont('','','7');
			//Datum zbiranja podatkov
			$this->pdf->MultiCell(0, 5, $this->getEntryDates(), 0, 'L', 0, 1, 0 ,0, true);
			$this->pdf->ln(5);
		}

		$this->pdf->SetDrawColor(200, 200, 200);
		$this->pdf->setFont('','B','6');
									

		# preberemo header
		if ($this->headFileName !== null) {
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
		
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();

			# če nimamo zank
			if(count(SurveyAnalysis::$_LOOPS) == 0){
			
				$this->displayCharts();
			}
			else{
				// izrisemo samo eno tabelo iz enega loopa
				if($this->current_loop > 0){
					
					$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
					$loop['cnt'] = $this->current_loop;
					SurveyAnalysis::$_CURRENT_LOOP = $loop;
					
					// Izpisemo naslov zanke za skupino
					$this->pdf->setFont('','B','10');
					$this->pdf->ln(5);
					$this->pdf->MultiCell(200, 5, $this->encodeText($lang['srv_zanka_note'].$loop['text']), 0, 'L', 0, 1, 0 ,0, true);
					$this->pdf->ln(5);
					$this->pdf->setFont('','','6');	
					
					$this->displayCharts();
				}
				// Izrisemo vse tabele spremenljivka (iz vseh loopov)
				else{
					$loop_cnt = 0;
					# če mamo zanke
					foreach(SurveyAnalysis::$_LOOPS AS $loop) {
						$loop_cnt++;
						$loop['cnt'] = $loop_cnt;
						SurveyAnalysis::$_CURRENT_LOOP = $loop;
						
						// Izpisemo naslov zanke za skupino
						$this->pdf->setFont('','B','10');
						$this->pdf->ln(5);
						$this->pdf->MultiCell(200, 5, $this->encodeText($lang['srv_zanka_note'].$loop['text']), 0, 'L', 0, 1, 0 ,0, true);
						$this->pdf->ln(5);
						$this->pdf->setFont('','','6');	
						
						$this->displayCharts();
					}
				}
			}
			
		} // end if else ($_headFileName == null)
		
	} 
	
	function displayCharts(){
		global $site_path;
		global $lang;
		
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		# polovimo frekvence			
		SurveyAnalysis::getFrequencys();
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
	
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {
				
					$this->pdf->SetFillColor(250, 250, 250);
					
					pdfIzvozAnalizaFunctions::init($this->anketa, $this, $from='charts');
				
					// Ce imamo radio tip in manj kot 5 variabel po defaultu prikazemo piechart
					$vars = count($spremenljivka['options']);
					$type = 0;
					if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $vars < 5 )
						$type = 2;
						
					//ce imamo nominalno spremenljivko ali ce je samo 1 variabla nimamo povprecij
					if($spremenljivka['tip'] == 6 && ($spremenljivka['cnt_all'] == 1 || $spremenljivka['skala'] == 1) && $type == 0 )
						$type = 2;
						
						
					if($spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 19 || $spremenljivka['tip'] == 21 || $spremenljivka['tip'] == 22){
						
						//$this->displayTitle($spid);
													
						$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
									
						// ce imamo vklopljeno nastavitev prikaz tabel med grafi (default)
						if($spremenljivka['tip'] == 19){
						
							$_answers = SurveyAnalysis::getAnswers($spremenljivka,10);
							
							// Preverimo ce je prazna in ne izpisujemo praznih
							if($_answers['validCnt'] != 0 || $hideEmpty != 1){
								pdfIzvozAnalizaFunctions::sumMultiText($spid, 'sums', $displayTitle=true);
								$this->pdf->setY($this->pdf->getY() + 10);
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
								pdfIzvozAnalizaFunctions::frequencyVertical($spid, $displayTitle=true);								
								$this->pdf->setY($this->pdf->getY() + 10);
							}
						}		
					}
					elseif( in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20)) ){
						
						//$this->displayTitle($spid);
						
						// Prikazemo posamezen graf
						pdfIzvozAnalizaFunctions::displayChart($spid, $type, $fromCharts=true);
						//$this->pdf->setY($this->pdf->getY() + 10);
					}
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
			
		} // end foreach self::$_HEADERS
	}
	
	
	// dodamo prvo stran
	function createFrontPage()
	{
		global $lang;
		
		$this->pdf->AddPage();
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 16);

		// dodamo naslov
  		$this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(128, 0, 0);
        $this->pdf->SetLineWidth(0.1);
		$this->pdf->Sety(100);
		$this->pdf->Cell(0, 10, $this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), 'TLR', 1,'C', 1, 0,0);
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 13);
		$this->pdf->Cell(0, 10, $this->encodeText($lang['srv_analiza_charts']), 'BLR', 1,'C', 1, 0,0);

		// Datum zbiranja podatkov
		$this->pdf->MultiCell(0, 5, $this->getEntryDates(), 0, 'C', 0, 1, 0 ,0, true);


		// dodamo info:
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 12);
		$this->currentStyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(128, 0, 0));
		$this->pdf->ln(30);
		//	$this->pdf->Write  (0, $this->encodeText("Info:"), '', 0, 'l', 1, 1);

		$this->drawLine();
		// avtorja, št vprašanj, datum kreiranja
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_shortname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 0, 'L', 0, 1, 0 ,0, true);
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim())
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_longname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_qcount'].': '.SurveyInfo::getInstance()->getSurveyQuestionCount(), 0, 'L', 0, 1, 0 ,0, true);
		
		// Aktiviranost
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			$this->pdf->SetTextColor(0,150,0);
			$this->pdf->MultiCell(95, 5, $this->encodeText($lang['srv_anketa_active2']), 0, 'L', 0, 1, 0 ,0, true);
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				$this->pdf->SetTextColor(255,120,0);
				$this->pdf->MultiCell(95, 5, $this->encodeText($lang['srv_survey_non_active_notActivated']), 0, 'L', 0, 1, 0 ,0, true);
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				$this->pdf->SetTextColor(255,120,0);
				$this->pdf->MultiCell(95, 5, $this->encodeText($lang['srv_survey_non_active']), 0, 'L', 0, 1, 0 ,0, true);
			}
		}
		$this->pdf->SetTextColor(0);
		
		// Aktivnost	
		if( count($activity) > 0 ){
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_active_from'].': '.SurveyInfo::getInstance()->getSurveyStartsDate(), 0, 'L', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_active_until'].': '.SurveyInfo::getInstance()->getSurveyExpireDate(), 0, 'L', 0, 1, 0 ,0, true);
		}
		
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_author'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInsertName()), 0, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_edit'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyEditName()), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_date'].': '.SurveyInfo::getInstance()->getSurveyInsertDate(), 0, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_date'].': '.SurveyInfo::getInstance()->getSurveyEditDate(), 0, 'L', 0, 1, 0 ,0, true);
		if ( SurveyInfo::getInstance()->getSurveyInfo() )
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_desc'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInfo()), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', FNT_MAIN_SIZE);
		$this->pdf->SetFillColor(0, 0, 0);
	}

	
	function setUserId($usrId) {$this->anketa['uid'] = $usrId;}
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
	
	function dateFormat($input, $format) {
		if ($input != '..') {		
			return date($format,strtotime($input));
		} else {
			return '';
		}
	}
	
	function formatNumber($value,$digit=0,$sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}

	function drawLine()
	{
		$cy = $this->pdf->getY();		
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
	}

	// Izpis opozorila ce ni vnesenih podatkov in ne prikazujemo grafa
	function displayEmptyWarning($spid){
		
		/*$spremenljivka = SurveyAnalysis::$_HEADERS[$spid]; 
		
		// Naslov posameznega grafa
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(165, 5, 'Graf '.$spremenljivka['variable'].' nima veljavnih podatkov!', 0, 'C', 0, 1, 0 ,0, true);
		$this->pdf->setFont('','','6');*/
	}
	
	function displayTitle($spid){
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		$stevilcenje = ($this->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $stevilcenje . $spremenljivka['naslov'];
		
		/*if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			$title .= '(n = '.$DataSet->GetNumerus();
		}*/	
		
		// Preverimo ce prebija slika stran
		if(isset($this->sessionData[$spid])){
			$settings = $this->sessionData[$spid];		
			$imgName = $settings['name'];
			$size = getimagesize('pChart/Cache/'.$imgName);
			$height = $size[1] / 5;
			
			if($this->pdf->getY() + $height > 250)
			{	
				$this->pdf->AddPage();
			}
		}
		
		
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
		if($spremenljivka['tip'] == 2){
			$this->pdf->setFont('','','5');
			$this->pdf->MultiCell(165, 1, $lang['srv_info_checkbox'], 0, 'C', 0, 1, 0 ,0, true);
		}
		$this->pdf->setFont('','','6');
	}	
}

?>