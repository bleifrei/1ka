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
class PdfIzvozAnalizaTTest {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $ttestClass = null;		//ttest class
	
	var $ttestVars;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...


	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'ttest')
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
		
		// ustvarimo ttest objekt
		$this->ttestClass = new SurveyTTest($anketa);
		
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
		$this->pdf->MultiCell(150, 5, $lang['export_analisys_ttest'], 0, 'L', 0, 1, 0 ,0, true);
		
		$this->pdf->setDrawColor(128, 128, 128);
		$this->pdf->setFont('','','8');


		if (count($this->sessionData['ttest']['sub_conditions']) > 1 ) {
			$variables1 = $this->ttestClass->getSelectedVariables();
			if (count($variables1) > 0) {
				foreach ($variables1 AS $v_first) {
					$ttest = null;
					$ttest = $this->ttestClass->createTTest($v_first, $this->sessionData['ttest']['sub_conditions']);

					$this->pdf->ln(10);
					
					$this->displayTTestTable($ttest);
					
					// Izrisemo graf za tabelo - zaenkrat samo admin
					if(isset($this->sessionData['ttest_charts']['showChart']) && $this->sessionData['ttest_charts']['showChart'] == true){
						$this->displayChart();
					}
				}
			}
		}
	}	

	public function displayTTestTable($ttest) {
		global $lang;
				
		# preverimo ali imamo izbrano odvisno spremenljivko
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		

		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {
			
			$spr_data_1 = $this->ttestClass->_HEADERS[$spid1];
			if ($grid1 == 'undefined') {

				# imamp lahko več variabel
				$seq = $seq1;
				foreach ($spr_data_1['grids'] as $gkey => $grid ) {
						
					foreach ($grid['variables'] as $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($sequence == $seq) {
							$sprLabel1 = '('.$variable['variable'].') '. $variable['naslov'];
						}
					}
				}
			} else {
				# imamo subgrid
				$sprLabel1 = '('.$spr_data_1['grids'][$grid1]['variable'].') '. $spr_data_1['grids'][$grid1]['naslov'];
			}
				
			# polovio labele
			$spid2 = $this->sessionData['ttest']['spr2'];
			$sprLabel2 =  trim(str_replace('&nbsp;','',$this->sessionData['ttest']['label2']));
			$label1 = $this->ttestClass->getVariableLabels($this->sessionData['ttest']['sub_conditions'][0]);
			$label2 = $this->ttestClass->getVariableLabels($this->sessionData['ttest']['sub_conditions'][1]);
			
			$this->ttestVars = array($sprLabel1, $sprLabel2);
			
			// prva vrstica
			$linecount = $this->pdf->getNumLines($this->encodeText($sprLabel1), 160);
			$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );
			
			$this->pdf->setFont('','B','6');
			$this->pdf->MultiCell(80, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(180, $height, $this->encodeText($sprLabel1), 1, 'C', 0, 1, 0 ,0, true);		
			
				
			// druga vrstica
			$linecount = $this->pdf->getNumLines($this->encodeText($sprLabel2), 100);
			$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );
			
			$this->pdf->MultiCell(80, $height, $this->encodeText($sprLabel2), 'BLR', 'C', 0, 0, 0 ,0, true);		
			
			$this->pdf->setFont('','','6');
			
			$this->pdf->MultiCell(20, $height, 'n', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 'x', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 's²', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 'se(x)', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, '±1,96×se(x)', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 'd', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 'se(d)', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 'Sig.', 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, $height, 't', 1, 'C', 0, 1, 0 ,0, true);


			// vrstici s podatki
			$this->pdf->MultiCell(80, 7, $this->encodeText($label1), 1, 'C', 0, 0, 0 ,0, true);		
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[1]['n'], 0), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[1]['x'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[1]['s2'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[1]['se'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[1]['margin'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 1, 0 ,0, true);
			
			$this->pdf->MultiCell(80, 7, $this->encodeText($label2), 1, 'C', 0, 0, 0 ,0, true);		
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[2]['n'], 0), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[2]['x'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[2]['s2'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[2]['se'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest[2]['margin'], 3), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest['d'], 3), 'BLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest['sed'], 3), 'BLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest['sig'], 3), 'BLR', 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(20, 7, $this->formatNumber($ttest['t'], 3), 'BLR', 'C', 0, 1, 0 ,0, true);
		}		
	}
	
	function displayChart(){
		global $lang;
		
		$tableChart = new SurveyTableChart($this->anketa['id'], $this->ttestClass, 'ttest');
		$tableChart->setTTestChartSession();
		
		// updatamo session iz baze
		$this->sessionData = SurveyUserSession::getData();
		
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
		$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
		$chartID = $sub1.'_'.$sub2.'_'.$spid1.'_'.$seq1.'_'.$grid1;
	
		$settings = $this->sessionData['ttest_charts'][$chartID];
		$imgName = $settings['name'];

		$size = getimagesize('pChart/Cache/'.$imgName);
		$height = $size[1] / 4;

		if($this->pdf->getY() + $height > 250)
		{	
			$this->pdf->AddPage();
		}
		else
			$this->pdf->setY($this->pdf->getY() + 15);
			
		// Naslov posameznega grafa
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(260, 5, $lang['srv_chart_ttest_title'].':', 0, 'C', 0, 1, 0 ,0, true);
		
		$this->pdf->MultiCell(30, 5,'', 0, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(90, 5, $this->encodeText($this->ttestVars[0]), 0, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(20, 5, $this->encodeText('/'), 0, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(90, 5, $this->encodeText($this->ttestVars[1]), 0, 'C', 0, 1, 0 ,0, true);

		$this->pdf->setFont('','','6');	
	
	
		$this->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=200, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
		
		
		$this->pdf->setY($this->pdf->getY() + 5);
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