<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
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
	

/** Class za generacijo pdf-a
 *
 * @desc: po novem je potrebno form elemente generirati ro�no kot slike
 *
 */
class PdfIzvozAnalizaCrosstab {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $crosstabClass = null;		//crosstab class
	
	var $crossData1;
	var $crossData2;
	
	var $crosstabVars;
	var $counter;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $crossData1, $crossData2, $podstran = M_ANALIZA_CROSSTAB)
	{
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->anketa['podstran'] = $podstran;
			// create new PDF document
			$this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
		$this->sessionData = SurveyUserSession::getData('crosstab_charts');
		
		//ustvarimo crosstab objekt in mu napolnimo variable (var1, var2, checkboxi)		
		$this->crosstabClass = new SurveyCrosstabs();
		$this->crosstabClass->Init($anketa);

		for($i=0; $i<sizeof($crossData1)/3; $i++){
			$index = $i * 3;
			$this->crossData1[$i] = array($crossData1[$index],$crossData1[$index+1],$crossData1[$index+2]);
		}
		for($i=0; $i<sizeof($crossData2)/3; $i++){
			$index = $i * 3;
			$this->crossData2[$i] = array($crossData2[$index],$crossData2[$index+1],$crossData2[$index+2]);
		}		
		
		//$this->crosstabClass->setVariables($crossData2[0],$crossData2[1],$crossData2[2],$crossData1[0],$crossData1[1],$crossData1[2]);
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		{
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
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function createPdf()
	{
		global $site_path;
		global $lang;
		
		// izpisemo prvo stran
		//$this->createFrontPage();
	   		
		$this->pdf->AddPage();
		
		$this->pdf->setFont('','B','11');
		$this->pdf->MultiCell(150, 5, $lang['export_analisys_crosstabs'], 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
		
		$this->pdf->setDrawColor(128, 128, 128);
		$this->pdf->setFont('','','6');

		# polovimo nastavtve missing profila
		//SurveyConditionProfiles:: getConditionString();
		
		$this->crosstabClass->_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
		if (count($this->crosstabClass->_LOOPS) > 0) {
			# če mamo zanke
			foreach ( $this->crosstabClass->_LOOPS AS $loop) {

				$this->crosstabClass->_CURRENT_LOOP = $loop;
				$this->displayCrosstabsTable();
			}
		} else {
		
			// loopamo cez vse izbrane variable in izrisemo vse tabele
			$addPage = false;
			$this->counter = 0;
			for($j=0; $j<sizeof($this->crossData2); $j++){
				for($i=0; $i<sizeof($this->crossData1); $i++){

					if($addPage)
						$this->pdf->AddPage();
					else
						$addPage = true;
					
					$this->pdf->ln(5);
					$this->crosstabClass->setVariables($this->crossData2[$j][0],$this->crossData2[$j][1],$this->crossData2[$j][2],$this->crossData1[$i][0],$this->crossData1[$i][1],$this->crossData1[$i][2]);
				
					$this->displayCrosstabsTable();
					
					$this->counter++;
				}
			}		
		} 	
		
	}	

	public function displayCrosstabsTable() {
		global $lang;
		
		if ($this->crosstabClass->getSelectedVariables(1) !== null && $this->crosstabClass->getSelectedVariables(2) !== null) {
			$variables1 = $this->crosstabClass->getSelectedVariables(1);
			$variables2 = $this->crosstabClass->getSelectedVariables(2);
			
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {
					
					$crosstabs = null;
					$crosstabs_value = null;
					
					$crosstabs = $this->crosstabClass->createCrostabulation($v_first, $v_second);
					$crosstabs_value = $crosstabs['crosstab'];

					# podatki spremenljivk
					$spr1 = $this->crosstabClass->_HEADERS[$v_first['spr']];
					$spr2 = $this->crosstabClass->_HEADERS[$v_second['spr']];
		
					$grid1 = $spr1['grids'][$v_first['grd']];
					$grid2 = $spr2['grids'][$v_second['grd']];
					
					#število vratic in število kolon
					$cols = count($crosstabs['options1']);
					$rows = count($crosstabs['options2']);
		
					# ali prikazujemo vrednosti variable pri spremenljivkah
					$show_variables_values = $this->crosstabClass->doValues;
	
					# nastavitve oblike
					if (($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) && ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR)) {
						# dodamo procente in residuale
						$rowSpan = 3;
						$numColumnPercent = $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3;
						$numColumnResidual = $this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR;
						$tblColumn = max($numColumnPercent,$numColumnResidual);
					} else if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) { 
						# imamo samo procente
						$rowSpan = 2;
						$numColumnPercent = $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3;
						$numColumnResidual = 0;
						$tblColumn = $numColumnPercent;
					} else if ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR) {
						# imamo samo residuale
						$rowSpan = 2;
						$numColumnPercent = 0;
						$numColumnResidual = $this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR;
						$tblColumn = $numColumnResidual;
					} else {
						#prikazujemo samo podatke
						$rowSpan = 1;
						$numColumnPercent = 0;
						$numColumnResidual = 0;
						$tblColumn = 1;
					}
					
					//izracun sirine ene celice
					if($cols > 0)
						$singleWidth = ($cols > 2) ? round( 170 / $cols ) : round( 150 / $cols );
					
					# izrišemo tabelo
					
					# najprej izrišemo naslovne vrstice		
					# za multicheckboxe popravimo naslov, na podtip
					$sub_q1 = null;
					if ($spr1['tip'] == '6' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '19' || $spr1['tip'] == '20') {
						foreach ($spr1['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_first['seq']) {
									$sub_q1 = strip_tags($spr1['naslov']);
									if ($show_variables_values == true ) {
										$sub_q1 .= '&nbsp;('.strip_tags($spr1['variable']).')';
									}
									if ($spr1['tip'] == '16') {
										$sub_q1 .= ', ' . strip_tags($grid1['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid1['variable']) . ')' : '');
									} else {
										$sub_q1 .= ', ' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}
							}		
						}
					}
					if ($sub_q1 == null) {
						$sub_q1 .=  strip_tags($spr1['naslov']);
						$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
					}
					
					$sub_q2 = null;
					if ($spr2['tip'] == '6' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '19' || $spr2['tip'] == '20') {
						foreach ($spr2['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_second['seq']) {
									$sub_q2 = strip_tags($spr2['naslov']);
									if ($show_variables_values == true) {
										$sub_q2 .= '&nbsp;('.strip_tags($spr2['variable']).')';
									}
									if ($spr2['tip'] == '16') {
										$sub_q2.= ', ' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid2['variable']) . ')' : '');
									} else {
										$sub_q2.= ', ' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}							
							}		
						}
					}
					if ($sub_q2 == null) {
						$sub_q2 .= strip_tags($spr2['naslov']);
						$sub_q2 .= ($show_variables_values == true ? '&nbsp;('.strip_tags($spr2['variable']).')' : '');
					}
					
					$this->crosstabVars = array($sub_q1, $sub_q2);
					
					$this->pdf->setFont('','B','6');
					
					/*$linecount = $this->pdf->getNumLines($this->encodeText($sub_q1), 170);
					$firstHeight = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );*/
					$firstHeight = $this->getCellHeight($this->encodeText($sub_q1), $singleWidth * count($crosstabs['options1']));
					
					// prva vrstica
					$this->pdf->MultiCell(25, $firstHeight, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(20, $firstHeight, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell($singleWidth * count($crosstabs['options1']), $firstHeight, $this->encodeText($sub_q1), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell($singleWidth, $firstHeight, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);

					
					// Izracun visine vrstice z naslovi gridov
					$height = 0;
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							#ime variable
							$text = $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't') {
								$text .= ' ( '.$ckey1.' )';
							}								
							
							/*$linecount = $this->pdf->getNumLines($this->encodeText($text), $singleWidth);
							$height = ($height < 4.7 + ($linecount-1)*3.7) ? (4.7 + ($linecount-1)*3.7) : $height;*/
							$height = ($height > $this->getCellHeight($this->encodeText($text), $singleWidth)) ? $height : $this->getCellHeight($this->encodeText($text), $singleWidth);
						}
					}		
					
					// druga vrstica		
					$this->pdf->setFont('','','6');
					$this->pdf->MultiCell(25, $height, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(20, $height, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							#ime variable
							$text = $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't') {
								$text .= ' ( '.$ckey1.' )';
							}								
							$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
						}
					}
						
					$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($lang['srv_analiza_crosstab_skupaj']), 1, 'C', 0, 1, 0 ,0, true);
					
					//izracun visine ene celice
					if($rowSpan == 1)
						$height = 8;
					elseif($rowSpan == 2)
						$height = 10;
					else
						$height = 15;
					
					//VMESNE VRSTICE
					$cntY = 1;
					if (count($crosstabs['options2']) > 0) {
						
						//POSAMEZNA VMESNA VRSTICA
						foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
							
							if( $cntY == 1 )
								$border = 'TLR';
							elseif( $cntY == $rows )
								$border = 'BLR';
							else
								$border = 'LR';			
							
							if ( $cntY == ceil($rows/2) || $rows == 1 ) {
								# ime variable
								$this->pdf->setFont('','B','6');
								$this->pdf->MultiCell(25, $height, $this->encodeText($sub_q2), $border, 'C', 0, 0, 0 ,0, true);
								$this->pdf->setFont('','','6');
							}
							else{
								$this->pdf->MultiCell(25, $height, $this->encodeText(''), $border, 'C', 0, 0, 0 ,0, true);
							}					
							$cntY++;	
							
							$text = $crossVariabla2['naslov'];
							$text = $this->snippet($this->encodeText($text), 25);
							if ($crossVariabla2['type'] !== 't') {					
								$text .= ' ('.$ckey2.')';
							}
							$this->pdf->MultiCell(20, $height, $text, 1, 'C', 0, 0, 0 ,0, true);

							//del vrstice z vsebino
							$this->pdf->setFont('','','5');
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								
								$data = array();
								
								if ($this->crosstabClass->crossChk0) {
									# frekvence crostabov
									$data[] = ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;						
								}
									
								if ($this->crosstabClass->crossChk1) {
									#procent vrstica
									$data[] = $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
								}
								if ($this->crosstabClass->crossChk2) {
									#procent stolpec
									$data[] =  $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
								}
								if ($this->crosstabClass->crossChk3) {
									#procent skupni
									$data[] = $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
								}

								# residuali
								if ($this->crosstabClass->crossChkEC) {
									$data[] = $this->formatNumber($crosstabs['exC'][$ckey1][$ckey2], 3, '');
								}
								if ($this->crosstabClass->crossChkRE) {
									$data[] = $this->formatNumber($crosstabs['res'][$ckey1][$ckey2], 3, '');
								}
								if ($this->crosstabClass->crossChkSR) {
									$data[] = $this->formatNumber($crosstabs['stR'][$ckey1][$ckey2], 3, '');
								}
								if ($this->crosstabClass->crossChkAR) {
									$data[] = $this->formatNumber($crosstabs['adR'][$ckey1][$ckey2], 3, '');
								}					
								
								$this->displayCell($data, $singleWidth, $rowSpan, $this->crosstabClass->crossChk0, $numColumnPercent, $numColumnResidual);						
							}					
							
							//se zadnji stolpec - vedno risemo
							$data = array();
							
							if ($this->crosstabClass->crossChk0) {
								# suma po vrsticah
								$data[] = (int)$crosstabs['sumaVrstica'][$ckey2];
							}
							if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
								# suma po vrsticah v procentih
								if ($this->crosstabClass->crossChk1) {
									$data[] = $this->formatNumber(100, 2, '%');
								}
								if ($this->crosstabClass->crossChk2) {
									$data[] = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
								if ($this->crosstabClass->crossChk3) {
									$data[] = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
							}
							
							$this->displayCell($data, $singleWidth, $rowSpan, $this->crosstabClass->crossChk0, $numColumnPercent, 0);
							$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);
							
							$this->pdf->setFont('','','6');
						}
					}
							
					
					// skupni sestevki po stolpcih - ZADNJA VRSTICA
					//popravimo stevilo vrstic (brez residualov)
					if($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR)
						$rowSpan--;
						
					//izracun visine ene celice
					if($rowSpan == 1)
						$height = 8;
					elseif($rowSpan == 2)
						$height = 10;
					else
						$height = 15;
						
					$this->pdf->MultiCell(25, $height, $this->encodeText(''), 'T', 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(20, $height, $this->encodeText($lang['srv_analiza_crosstab_skupaj']), 1, 'C', 0, 0, 0 ,0, true);
					
					$this->pdf->setFont('','','5');	
					
					if (count($crosstabs['options1']) > 0){
						
						foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
							
							$data = array();
							
							# prikazujemo eno od treh možnosti					
							if ($this->crosstabClass->crossChk0) {
								# suma po stolpcih
								$data[] = (int)$crosstabs['sumaStolpec'][$ckey1];
							}					
							if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
								# suma po stolpcih v procentih
								if ($this->crosstabClass->crossChk1) {
									$data[] = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
								if ($this->crosstabClass->crossChk2) {
									$data[] = $this->formatNumber(100, 2, '%');
								}
								if ($this->crosstabClass->crossChk3)
								{
									$data[] = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
							}

							$this->displayCell($data, $singleWidth, $rowSpan, $this->crosstabClass->crossChk0, $numColumnPercent, 0);
						}
						
						# zadnja celica z skupno sumo
						$data = array();
						
						if ($this->crosstabClass->crossChk0) {
							# skupna suma
							$data[] = (int)$crosstabs['sumaSkupna'];
						}
						if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
							# suma po stolpcih v procentih
							if ($this->crosstabClass->crossChk1) {
								$data[] = $this->formatNumber(100, 2, '%');
							}
							if ($this->crosstabClass->crossChk2) {
								$data[] = $this->formatNumber(100, 2, '%');
							}
							if ($this->crosstabClass->crossChk3) {
								$data[] = $this->formatNumber(100, 2, '%');
							}
						}
						
						$this->displayCell($data, $singleWidth, $rowSpan, $this->crosstabClass->crossChk0, $numColumnPercent, 0);
					}

					$this->pdf->setFont('','','6');
					$this->pdf->ln(5);
					
					
					// Izris grafa (ce je vklopljena nastavitev)
					if($this->sessionData['showChart'] == '1'){
						$this->displayChart();
					}
				}
			}
		}
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
	function displayCell($data, $width, $numRows, $frekvence, $numColumnPercent, $numColumnResidual){
		
		$height = ($numRows == 1 ? 8 : 5);
		$fullHeight = ($height == 8 ? $height : $numRows*$height);
		$i=0;
		
		//preberemo pozicijo - zacetek celice
		$y = $this->pdf->GetY();
		$x = $this->pdf->GetX();
		
		$this->pdf->setDrawColor(128, 128, 128);
		
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
		$this->pdf->setDrawColor(128, 128, 128);
		$this->pdf->SetXY($x, $y);
		$this->pdf->MultiCell($width, $fullHeight, '', 1, 'C', 0, 1, 0 ,0, true);		
		
		//na koncu nastavimo pozicijo na pravo mesto
		$this->pdf->SetXY($x+$width, $y);	
	}
	
	function displayChart(){
		global $lang;

		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		$chartID = implode('_', $this->crosstabClass->variabla1[0]).'_'.implode('_', $this->crosstabClass->variabla2[0]);
		$chartID .= '_counter_'.$this->counter;
		
		$settings = $this->sessionData[$chartID];
		$imgName = $settings['name'];

		$size = getimagesize('pChart/Cache/'.$imgName);
		$height = $size[1] / 5;

		if($this->pdf->getY() + $height > 250)
		{	
			$this->pdf->AddPage();
		}
		else
			$this->pdf->setY($this->pdf->getY() + 15);
	
	
		// Naslov posameznega grafa
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(30, 5,'', 0, 'C', 0, 0, 0 ,0, true);
		if($settings['type'] == 1 || $settings['type'] == 4){
			$this->pdf->MultiCell(90, 5, $this->encodeText($this->crosstabVars[0]), 0, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 5, $this->encodeText('/'), 0, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(90, 5, $this->encodeText($this->crosstabVars[1]), 0, 'C', 0, 1, 0 ,0, true);
		}
		else{
			$this->pdf->MultiCell(200, 5, $this->encodeText($this->crosstabVars[0]), 0, 'C', 0, 1, 0 ,0, true);
		}
		$this->pdf->setFont('','','6');
		
		$this->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=200, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
	}
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text,$length=64,$tail="...")
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