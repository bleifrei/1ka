<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.pdfIzvozAnalizaSums.php');
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
class PdfIzvozAnalizaOpisne {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;	
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	
	var $current_loop = 'undefined';
	
	
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $sprID = null, $loop = null)
	{	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->spremenljivka = $sprID;
			
			SurveyAnalysis::Init($this->anketa['id']);
			SurveyAnalysis::$setUpJSAnaliza = false;
			
			// create new PDF document
			$this->pdf = new enka_TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa['id']);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
			
			SurveyZankaProfiles :: Init($this->anketa['id'], $global_user_id);
			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
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
		//$this->createFrontPage();
	   		
		$this->pdf->AddPage();
		
		$this->pdf->setFont('','B','11');
		$this->pdf->MultiCell(150, 5, $lang['export_analisys_desc'], 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);

		$this->pdf->SetDrawColor(128, 128, 128);
		$this->pdf->setFont('','','6');
									
		if ($this->headFileName !== null ) {
		
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();

			# če nimamo zank
			if(count(SurveyAnalysis::$_LOOPS) == 0){
			
				$this->displayTables();
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
					$this->pdf->setFont('','','6');
				
					$this->displayTables();
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
						$this->pdf->setFont('','','6');
						
						$this->displayTables();
					}
				}
			}

		} // end if else ($_headFileName == null) 			
	} 
	
	function displayTables(){
		global $site_path;
		global $lang;
		global $global_user_id;
	
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		# polovimo frekvence			
		SurveyAnalysis::getDescriptives();
	
		# izpišemo opisne statistike
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		
		$text = array();
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_variable']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_variable_text']);
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_m']);		
		$text[] = $this->encodeText($lang['srv_analiza_num_units']);			
		$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);			
		$text[] = $this->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_max']);
			
		$params = array('bold' => 'B', 'align2' => 'C');
		
		$this->pdf->setFont('','b','6');
		$this->tableRow($text, $params);
		$this->pdf->setFont('','','6');			
		
		# dodamo še kontrolo če kličemo iz displaySingleVar 
		if (isset($_spid) && $_spid !== null) {
			SurveyAnalysis::$_HEADERS = array($_spid => SurveyAnalysis::$_HEADERS[$_spid]);
		}
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
	
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm'
			 && ( count(SurveyAnalysis::$_FILTRED_VARIABLES) == 0 || (count(SurveyAnalysis::$_FILTRED_VARIABLES) > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ))
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES) 
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {

				$show_enota = false;
				# preverimo ali imamo samo eno variablo in če iammo enoto
				if ((int)$spremenljivka['enota'] != 0 || $spremenljivka['cnt_all'] > 1 ) {
					$show_enota = true;
				}
				
				# izpišemo glavno vrstico z podatki
				$_sequence  = null;
				# za enodimenzijske tipe izpišemo podatke kar v osnovni vrstici
				if (!$show_enota) {  
//				 	if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3  
//				 		|| $spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					self::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
				} else {
				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$show_enota = false;
				}
					self::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
					#zloopamo skozi variable
					$_sequence = null;
					$grd_cnt=0;
					if (count($spremenljivka['grids']) > 0)				 	
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						
						if (count($spremenljivka['grids']) > 1 && $grd_cnt !== 0 && $spremenljivka['tip'] != 6) {
							$grid['new_grid'] = true;
						}
						$grd_cnt++;
						# dodamo dodatne vrstice z albelami grida
						if (count ($grid['variables']) > 0)
						foreach ($grid['variables'] AS $vid => $variable ){
							# dodamo ostale vrstice
							$do_show = ($variable['other'] !=1 && ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || $spremenljivka['tip'] == 5 || $spremenljivka['tip'] == 8 )) 
								? false
								: true;
								if ($do_show) {
									self::displayDescriptivesVariablaRow($spremenljivka,$grid,$variable,$_css);
									
								}
							$grid['new_grid'] = false;
								
						}
					}
				 } //else: if (!$show_enota)
			 } // end if $spremenljivka['tip'] != 'm'
		} // end foreach  SurveyAnalysis::$_HEADERS
	}
	
		
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	function displayDescriptivesVariablaRow($spremenljivka,$grid,$variable=null) {
		global $lang;

		$_sequence = $variable['sequence'];	# id kolone z podatki
		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		$text = array();
			
		$text[] = $this->encodeText($variable['variable']);
		$text[] = $this->encodeText($variable['naslov']);
		
		#veljavno
		$text[] = $this->encodeText((int)$_desc['validCnt']);
		
		#ustrezno
		$text[] = $this->encodeText((int)$_desc['allCnt']);
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else if (isset($_desc['avg']) && $spremenljivka['tip'] == 2 && (int)$spremenljivka['skala'] == 1 )
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%'));
		else
			$text[] = $this->encodeText('');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = $this->encodeText('');
		
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
			
		$this->tableRow($text);
		
	}
	
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	function displayDescriptivesSpremenljivkaRow($spid,$spremenljivka,$show_enota,$_sequence = null) {
		global $lang;

		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		$text = array();
			
		$text[] = $this->encodeText($spremenljivka['variable']);
		$text[] = $this->encodeText($spremenljivka['naslov']);
		
		#veljavno
		$text[] = $this->encodeText((!$show_enota ? (int)$_desc['validCnt'] : ''));
		
		#ustrezno
		$text[] = $this->encodeText((!$show_enota ? (int)$_desc['allCnt'] : ''));
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else
			$text[] = $this->encodeText('');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = $this->encodeText('');
		
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
		
		$this->pdf->setFont('','b','6');
		$this->tableRow($text);
		$this->pdf->setFont('','','6');
	}

	
	
	
	function createFrontPage()
	{
// dodamo prvo stran
		$this->pdf->AddPage();
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 16);

		// dodamo naslov
  		$this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(128, 0, 0);
        $this->pdf->SetLineWidth(0.1);
		$this->pdf->Sety(100);
		$this->pdf->Cell(0, 16, $this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), 1, 1,'C', 1, 0,0);


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
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_active_from'].': '.SurveyInfo::getInstance()->getSurveyStartsDate(), 0, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_active_until'].': '.SurveyInfo::getInstance()->getSurveyExpireDate(), 0, 'L', 0, 1, 0 ,0, true);

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
	
	
	function tableRow($arrayText, $arrayParams=array()){
	
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
		
					
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 52);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = $this->getCellHeight($arrayText[1], 60);
		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(60, $height, $this->encodeText($arrayText[1]), 1, $arrayParams['align2'], 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[5]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[6]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[7]), 1, 'C', 0, 1, 0 ,0, true);
	}
	
	function getCellHeight($string, $width){
		
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