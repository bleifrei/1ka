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
 * 
 *
 */
class PdfIzvozAnalizaBreak {

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
		

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'break')
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
		$this->sessionData = SurveyUserSession::getData();
		
		// ustvarimo break objekt
		$this->breakClass = new SurveyBreak($this->anketa['id']);
		$this->spr = $this->sessionData['break']['spr'];
		# poiščemo sekvenco
		$this->seq = $this->sessionData['break']['seq'];
		
		$this->break_percent = (isset($this->sessionData['break']['break_percent']) && $this->sessionData['break']['break_percent'] == false) ? false : true;
		
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
		$this->pdf->MultiCell(150, 5, $lang['export_analisys_break'], 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(10);
		
		$this->pdf->setDrawColor(0, 0, 0, 255);
		$this->pdf->setFont('','','8');

		
		if ($this->spr != 0){
			# poiščemo pripadajoče variable
			$_spr_data = $this->breakClass->_HEADERS[$this->spr];
			
			# poiščemo opcije
			$options = $_spr_data['options'];
			
			# za vsako opcijo posebej izračunamo povprečja za vse spremenljivke
			$frequencys = null;
			if (count($options) > 0) {
				foreach ($options as $okey => $option) {
					
					# zloopamo skozi variable
					$okeyfrequencys = $this->breakClass->getAllFrequencys($okey, $this->seq, $this->spr);
					if ($okeyfrequencys != null) {
						if ($frequencys == null) {
							$frequencys = array();
						}
						$frequencys[$okey] = $okeyfrequencys;
					} 
				}
			}
			$this->displayBreak($this->spr,$frequencys);
		
		} else {
			$this->pdf->MultiCell(150, 5, $lang['srv_break_error_note_1'], 0, 'L', 0, 1, 0 ,0, true);
		}
	}	

	function displayBreak($forSpr, $frequencys) {
		
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		//SurveyTimeProfiles :: printIsDefaultProfile(false);
		
		# če imamo filter ifov ga izpišemo
		//SurveyConditionProfiles:: getConditionString();
		
		# če imamo filter spremenljivk ga izpišemo
		//SurveyVariablesProfiles:: getProfileString(true);
		//SurveyDataSettingProfiles :: getVariableTypeNote();
		
		# če rekodiranje
		//$SR = new SurveyRecoding($this->anketa);
		//$SR -> getProfileString();
		
		# filtriranje po spremenljivkah
		$_FILTRED_VARIABLES = SurveyVariablesProfiles::getProfileVariables(SurveyVariablesProfiles::checkDefaultProfile(), true);
		
		# ali prikazujemo tabele ali grafe
		$this->break_charts = (isset($this->sessionData['break']['break_show_charts']) && (int)$this->sessionData['break']['break_show_charts'] == 1) ? 1 : 0;
		
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
				
				$this->displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka);
			} else if ( is_numeric($tip) 
						&& (
								$tip == 4	#text
								|| $tip == 19	#multitext
								|| $tip == 21	#besedilo*
								|| $tip == 20	#multi numer*
						) && ( count($_FILTRED_VARIABLES) == 0 || (count($_FILTRED_VARIABLES) > 0 && isset($_FILTRED_VARIABLES[$skey]) ) )
						) {
				$this->displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka);
			} 
		}
	}
	
	function displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka) {
		$tip = $spremenljivka['tip'];
		$skala = $spremenljivka['skala'];
		if ($forSpr != $spremenljivka['id']) {
			switch ($tip) {
				# radio, dropdown
				case 1:
				case 3:
					$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
					break;
				#multigrid
				case 6:
					if ($spremenljivka['skala'] == 0) {
						$this->displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka);
					} else {
						$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
					}
					break;
				# checkbox
				case 2:
						$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
						break;
				#number
				case 7:
				#ranking
				case 17:
				#vsota
				case 18:
				#multinumber
				case 20:
					$this->displayBreakTableNumber($forSpr,$frequencys,$spremenljivka);
				break ;
				case 19:
					$this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
				break ;
				#multicheck
				case 16:
					$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
				case 4:
				
				case 21:
					# po novem besedilo izpisujemo v klasični tabeli
					$this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
					
					#$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
				break;
				default:
					$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
			}
		}
	}
	
	function displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka) {
		global $lang;
	
		// Ce izrisujemo graf
		if($this->break_charts == 1){
			$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'mgrid');
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
				
				
				// PRVA VRSTICA
				$height = $this->getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')', $singleWidth*$colspan);
				
				$this->pdf->setFont('','B','6');
				$this->pdf->MultiCell(60, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
				$this->pdf->MultiCell($singleWidth*$colspan, $height, $this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
				
				
				// DRUGA VRSTICA
				$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
				$height = $this->getCellHeight($text, $singleWidth*$colspan);

				// najprej loopamo da dobimo visino celice
				if ($tip != 1 && $tip != 3) {
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							$text = $variable['naslov'].' ('.$variable['variable'].')';
							$height = ($this->getCellHeight($text, $singleWidth) > $height) ? $this->getCellHeight($text, $singleWidth) : $height;
						}
					}
				} 
				else if (count($spremenljivka['options']) < 15) {
					foreach ($spremenljivka['options'] AS $okey => $option) {
						$text = $option.' ('.$okey.')';
						$height = ($this->getCellHeight($text, $singleWidth) > $height) ? $this->getCellHeight($text, $singleWidth) : $height;
					}	
				}

				// se izrisemo celice...
				$this->pdf->setFont('','B','6');
				$this->pdf->MultiCell(60, $height, $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'BLR', 'C', 0, 0, 0 ,0, true);
				$this->pdf->setFont('','','6');
				
				if ($tip != 1 && $tip != 3) {
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($variable['naslov'].' ('.$variable['variable'].')'), 1, 'C', 0, 0, 0 ,0, true);
						}
					}
					$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	
				} 
				else if (count($spremenljivka['options']) < 15) {
					foreach ($spremenljivka['options'] AS $okey => $option) {
						$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($option.' ('.$okey.')'), 1, 'C', 0, 0, 0 ,0, true);
					}
					$this->pdf->MultiCell($singleWidth, $height, 'povprečje', 1, 'C', 0, 1, 0 ,0, true);	
				}
				
				
				// VRSTICE S PODATKI
				foreach ($frequencys AS $fkey => $fkeyFrequency) {

					$height = $this->getCellHeight($forSpremenljivka['options'][$fkey], 60);
					$this->pdf->MultiCell(60, $height, $this->encodeText($forSpremenljivka['options'][$fkey]), 1, 'C', 0, 0, 0 ,0, true);

					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							if ($variable['other'] != 1) {
								$sequence = $variable['sequence'];
								if (($tip == 1 || $tip == 3) && count($spremenljivka['options']) < 15) {
									foreach ($spremenljivka['options'] AS $okey => $option) {
										$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']), 1, 'C', 0, 0, 0 ,0, true);
									}
								}
								$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
							}
						}
					}
					$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	
				}
				
				$this->pdf->ln(10);
			}
			
			else {
				$rowspan = 2;
				$colspan = $spremenljivka['grids'][0]['cnt_vars'];
				$singleWidth = floor(200 / $colspan);
				# za multicheck razdelimo na grupe - skupine
				foreach ($frequencys AS $fkey => $frequency) {
					
					$this->pdf->setFont('','B','6');
					$this->pdf->MultiCell(200, 5, $this->encodeText('Tabela za: ('.$forSpremenljivka['variable'].') = '.$forSpremenljivka['options'][$fkey]), 0, 'L', 0, 1, 0 ,0, true);
					
					
					$text = $spremenljivka['naslov'].' ('.$spremenljivka['variable'].')';
					$height = $this->getCellHeight($text, 260);
					$this->pdf->MultiCell(260, $height, $this->encodeText($text), 1, 'C', 0, 1, 0 ,0, true);	

					$this->pdf->setFont('','','6');
					
					
					foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {					
						$height = ($this->getCellHeight($variable['naslov'], $singleWidth) > $height) ? $this->getCellHeight($variable['naslov'], $singleWidth) : $height;
					}
					
					$this->pdf->MultiCell(60, $height, '', 1, 'C', 0, 0, 0 ,0, true);
					foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {					
						$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($variable['naslov']), 1, 'C', 0, 0, 0 ,0, true);
					}
					$this->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
					
					
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {

						$text = '('.$grid['variable'].') '.$grid['naslov'];
						$height = $this->getCellHeight($text, 60);
						$this->pdf->MultiCell(60, $height,  $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);

						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];

							$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
						}
						$this->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
					}
					$this->pdf->ln(10);
				}
			}
		}
	}
	
	function displayBreakTableNumber($forSpr,$frequencys,$spremenljivka) {
		global $lang;
	
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
				$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
			}
			
			// Multinumber graf
			else{
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				
					// Izrisujemo samo 1 graf v creportu
					if($_GET['m'] == 'analysis_creport'){
						
						if($spremenljivka['break_sub_table']['key'] == $gkey){
							$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
						}
					}
					
					// Izrisujemo vse zaporedne grafe
					else{
						$spremenljivka['break_sub_table']['key'] = $gkey;
						$spremenljivka['break_sub_table']['sequence'] = $grid['variables'][0]['sequence'];
					
						$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
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
				
				
				// PRVA VRSTICA
				$height = $this->getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')', $singleWidth*$colspan);
				
				$this->pdf->setFont('','B','6');
				$this->pdf->MultiCell(60, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
				$this->pdf->MultiCell($singleWidth*$colspan, $height, $this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
				
				
				// DRUGA VRSTICA
				$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
				$height = $this->getCellHeight($text, $singleWidth*$colspan);

				// najprej loopamo da dobimo visino celice
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$text = $variable['naslov'].' ('.$variable['variable'].')';
						$height = ($this->getCellHeight($text, $singleWidth) > $height) ? $this->getCellHeight($text, $singleWidth) : $height;
					}
				}
					
				// se izrisemo celice...
				$this->pdf->setFont('','B','6');
				$this->pdf->MultiCell(60, $height, $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'LR', 'C', 0, 0, 0 ,0, true);
				$this->pdf->setFont('','','6');
				
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($variable['naslov'].' ('.$variable['variable'].')'), 1, 'C', 0, 0, 0 ,0, true);
					}
				}
				$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	


				// TRETJA VRSTICA
				$this->pdf->MultiCell(60, 5, '', 'BLR', 'C', 0, 0, 0 ,0, true);
				
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$this->pdf->MultiCell($singleWidth, 5, $this->encodeText('Povprečje'), 1, 'C', 0, 0, 0 ,0, true);
					}
				}
				$this->pdf->MultiCell(1, 5, '', 0, 'C', 0, 1, 0 ,0, true);	
				
				
				// VRSTICE S PODATKI
				foreach ($frequencys AS $fkey => $fkeyFrequency) {

					$height = $this->getCellHeight($forSpremenljivka['options'][$fkey], 60);
					$this->pdf->MultiCell(60, $height, $this->encodeText($forSpremenljivka['options'][$fkey]), 1, 'C', 0, 0, 0 ,0, true);

					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							if ($variable['other'] != 1) {
								$sequence = $variable['sequence'];
								$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
							
								$totalMeans[$sequence] += ($this->breakClass->getMeansFromKey($fkeyFrequency[$sequence])*(int)$frequencys[$fkey][$sequence]['validCnt']);
								$totalFreq[$sequence]+= (int)$frequencys[$fkey][$sequence]['validCnt'];
							}
						}
					}
					$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	
				}
						
				// dodamo še skupno sumo in povprečje
				$this->pdf->MultiCell(60, 5, $this->encodeText('Skupaj'), 1, 'C', 0, 0, 0 ,0, true);
				
				$this->pdf->setFont('','B','6');
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
							#povprečja
							$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
							$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
						}	
					}	
				}
				$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);			
				$this->pdf->setFont('','','6');
				
				$this->pdf->ln(10);
			}
			
			else {
				$rowspan = 3;
				$colspan = count($spremenljivka['grids'][0]['variables']);
				$singleWidth = floor(200 / $colspan);
				# za multinumber razdelimo na grupe - skupine
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					
					$this->pdf->setFont('','B','6');
					$this->pdf->MultiCell(200, 5, $this->encodeText('Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'].' ('.$grid['variable'].')'), 0, 'L', 0, 1, 0 ,0, true);
					
			
					// PRVA VRSTICA
					$height = $this->getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')', $singleWidth*$colspan);
					
					$this->pdf->setFont('','B','6');
					$this->pdf->MultiCell(60, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell($singleWidth*$colspan, $height, $this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
					
					
					// DRUGA VRSTICA
					$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
					$height = $this->getCellHeight($text, $singleWidth*$colspan);

					foreach ($grid['variables'] AS $vkey => $variable) {
						$text = $variable['naslov'].' ('.$variable['variable'].')';
						$height = ($this->getCellHeight($text, $singleWidth) > $height) ? $this->getCellHeight($text, $singleWidth) : $height;
					}
						
					// se izrisemo celice...
					$this->pdf->setFont('','B','6');
					$this->pdf->MultiCell(60, $height, $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'LR', 'C', 0, 0, 0 ,0, true);
					$this->pdf->setFont('','','6');
					
					foreach ($grid['variables'] AS $vkey => $variable) {
						$text = $variable['naslov'].' ('.$variable['variable'].')';
						$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
					}
					$this->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	


					// TRETJA VRSTICA
					$this->pdf->MultiCell(60, 5, '', 'BLR', 'C', 0, 0, 0 ,0, true);
					
					foreach ($grid['variables'] AS $vkey => $variable) {
						$this->pdf->MultiCell($singleWidth, 5, $this->encodeText('Povprečje'), 1, 'C', 0, 0, 0 ,0, true);
					}
					$this->pdf->MultiCell(1, 5, '', 0, 'C', 0, 1, 0 ,0, true);	
									
					
					// VRSTICE Z VSEBINO
					foreach ($forSpremenljivka['options'] AS $okey => $option) {
						
						$height = $this->getCellHeight($option, 60);
						$this->pdf->MultiCell(60, $height,  $this->encodeText($option), 1, 'C', 0, 0, 0 ,0, true);
						
						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];
							
							#povprečje
							$this->pdf->MultiCell($singleWidth, $height, self::formatNumber($means[$okey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);

							$totalMeans[$sequence] += ($means[$okey][$sequence]*(int)$frequencys[$okey][$sequence]['validCnt']);
							$totalFreq[$sequence]+= (int)$frequencys[$okey][$sequence]['validCnt'];	
						}
						$this->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
					}
					
					// dodamo še skupno sumo in povprečje
					$this->pdf->MultiCell(60, 5, $this->encodeText('Skupaj'), 1, 'C', 0, 0, 0 ,0, true);
					
					$this->pdf->setFont('','B','6');
					foreach ($grid['variables'] AS $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
								#povprečja
								$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
								$this->pdf->MultiCell($singleWidth, 5, self::formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);	
						}	
					}
					$this->pdf->MultiCell(1, 5, '', 0, 'C', 0, 1, 0 ,0, true);			
					$this->pdf->setFont('','','6');

					
					$this->pdf->ln(10);
				}
			}
		}
	}
	
	function displayBreakTableText($forSpr,$frequencys,$spremenljivka) {
		global $lang;
	
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
			
		$singleWidth = floor(200 / $colspan);
			
			
		foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				
			$this->pdf->setFont('','B','6');
			$this->pdf->MultiCell(200, 5, $this->encodeText('Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'].' ('.$grid['variable'].')'), 0, 'L', 0, 1, 0 ,0, true);
			
	
			// PRVA VRSTICA
			$height1 = $this->getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')', $singleWidth*$colspan);
			
			$this->pdf->setFont('','B','6');
			$this->pdf->MultiCell(60, $height1, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell($singleWidth*$colspan, $height1, $this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
			
			
			// DRUGA VRSTICA
			$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
			$height2 = $this->getCellHeight($text, $singleWidth*$colspan);

			foreach ($grid['variables'] AS $vkey => $variable) {
				$text = $variable['naslov'].' ('.$variable['variable'].')';
				$height2 = ($this->getCellHeight($text, $singleWidth) > $height2) ? $this->getCellHeight($text, $singleWidth) : $height2;
			}
				
			// se izrisemo celice...
			$this->pdf->setFont('','B','6');
			$this->pdf->setY($this->pdf->getY() - $height1);
			$this->pdf->MultiCell(60, $height1+$height2, $this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'BLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->setXY($this->pdf->getX(), $this->pdf->getY() + $height1);
			$this->pdf->setFont('','','6');
			
			foreach ($grid['variables'] AS $vkey => $variable) {
				$text = $variable['naslov'].' ('.$variable['variable'].')';
				$this->pdf->MultiCell($singleWidth, $height2, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
			}
			$this->pdf->MultiCell(1, $height2, '', 0, 'C', 0, 1, 0 ,0, true);	

			
			// VRSTICE Z VSEBINO
			foreach ($forSpremenljivka['options'] AS $okey => $option) {
				
				// Izracunamo visino najvisje celice
				$height = $this->getCellHeight($option, 60);
				foreach ($grid['variables'] AS $vkey => $variable) {
					$sequence = $variable['sequence'];
					$text = "";
					if (count($texts[$okey][$sequence]) > 0) {				
						$tempHeight = 0;
						foreach ($texts[$okey][$sequence] AS $ky => $units) {
							$text .= $units['text']."\n";
						}
						$text = substr($text,0,-2);
						$height = ($this->getCellHeight($text, $singleWidth) > $height) ? $this->getCellHeight($text, $singleWidth) : $height;
					}
				}
				
				// Izrisemo vrstico
				$this->pdf->MultiCell(60, $height,  $this->encodeText($option), 1, 'C', 0, 0, 0 ,0, true);			
				foreach ($grid['variables'] AS $vkey => $variable) {
					$sequence = $variable['sequence'];
					if (count($texts[$okey][$sequence]) > 0) {
						$text = "";
						foreach ($texts[$okey][$sequence] AS $ky => $units) {
							$text .= $units['text']."\n";
						}
						$text = substr($text,0,-2);
						$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
					}
					else
						$this->pdf->MultiCell($singleWidth, $height, '', 1, 'C', 0, 0, 0 ,0, true);
				}
				$this->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
			}
			
			$this->pdf->ln(10);
		}	
	}
	
	function displayCrosstabs($forSpr,$frequencys,$spremenljivka) {
		global $lang;
		
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
		foreach ($spremenljivka['grids'] AS $gid => $grid) {		
			if (($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6) && $this->break_charts != 1) {
				
				$text = 'Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'];
				if ($spremenljivka['tip'] != 6) {
					$text .= ' ('.$grid['variable'].')';
				}
				$this->pdf->setFont('','B','6');
				$this->pdf->MultiCell(200, 5, $this->encodeText($text), 0, 'L', 0, 1, 0 ,0, true);
				$this->pdf->setFont('','','6');
			}
		
			$seq2 = $grid['variables'][0]['sequence'];
			$grd2 = $gid;
			
			$this->crosstabClass->setVariables($seq2,$spr2,$grd2,$seq1,$spr1,$grd1);
			
			if($this->break_charts == 1){
				$this->crosstabClass->fromBreak = false;
				$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'crosstab');
			}
			else{
				$this->displayCrosstabsTable();
			}
		}
	}
	
	function displayCrosstabsTable() {
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
					
					//izracun visine ene celice
					if($rowSpan == 1)
						$height = 8;
					elseif($rowSpan == 2)
						$height = 10;
					else
						$height = 15;

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
					
					$linecount = $this->pdf->getNumLines($this->encodeText($sub_q1), 170);
					$firstHeight = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );
					
					//prva vrstica
					$this->pdf->setFont('','B','6');
					$this->pdf->MultiCell(25, $firstHeight, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(20, $firstHeight, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell($singleWidth * count($crosstabs['options1']), $firstHeight, $this->encodeText($sub_q1), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell($singleWidth, $firstHeight, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);

					//druga vrstica		
					$this->pdf->setFont('','','6');
					$this->pdf->MultiCell(25, 7, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(20, 7, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							#ime variable
							$text = $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't') {
								$text .= ' ( '.$ckey1.' )';
							}
							$this->pdf->MultiCell($singleWidth, 7, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
						}
					}
						
					$this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_analiza_crosstab_skupaj']), 1, 'C', 0, 1, 0 ,0, true);
					
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
							if ($crossVariabla2['type'] !== 't') {
								$text .= ' ('.$ckey2.')';
							}					
							$this->pdf->MultiCell(20, $height, $this->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);

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
					$this->pdf->ln(20);
				}
			}
		}
	}
	
	function displayChart($forSpr,$frequencys,$spremenljivka,$type){
		global $lang;

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

		$size = getimagesize('pChart/Cache/'.$imgName);
		$height = $size[1] / 5;

		if($this->pdf->getY() + $height > 150)
		{	
			$this->pdf->AddPage();
		}
		else
			$this->pdf->setY($this->pdf->getY());
	
	
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
		
		$this->pdf->Ln(LINE_BREAK);
		
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(30, 5,'', 0, 'C', 0, 0, 0 ,0, true);

		$this->pdf->MultiCell(200, 5, $this->encodeText($title), 0, 'C', 0, 1, 0 ,0, true);
		if($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6){
			$this->pdf->MultiCell(30, 5,'', 0, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(200, 5, $this->encodeText($subtitle), 0, 'C', 0, 1, 0 ,0, true);
		}
		
		$this->pdf->setFont('','','6');
		
		$this->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=200, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
	
		$this->pdf->Ln(LINE_BREAK);
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