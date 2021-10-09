<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
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
class PdfIzvozList {

	var $anketa;                            // trenutna anketa - array();

	var $pi = array('canCreate'=>false);    // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke

    
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $sprID = null)
	{	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){

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
			
			// Nastavimo da izpisujemo samo prvih 5 spremenljivk
            $_GET['spr_limit'] = 5;
            
			// Nastavimo da nikoli ne izpisemo vabila
            $_GET['email'] = 0;
            
			SurveyDataDisplay::Init($this->anketa['id']);			
		}
		else{
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
		$this->pdf->MultiCell(150, 5, $lang['export_list'], 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
									
		$this->displayTable();									
	} 
	
	
	function  displayTable(){
		global $site_path;
		global $lang;

		
		$folder = $site_path . EXPORT_FOLDER.'/';

		//polovimo podatke o nastavitvah trenutnega profila (missingi..)
		SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);

		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		SurveyDataDisplay::$_VARS[VAR_DATA] = 1;
		SurveyDataDisplay::$_VARS[VAR_SPR_LIMIT] = 5;
		SurveyDataDisplay::$_VARS[VAR_META] = 0;
		SurveyDataDisplay::$_VARS[VAR_EMAIL] = 0;
		SurveyDataDisplay::$_VARS[VAR_RELEVANCE] = 0;
		SurveyDataDisplay::$_VARS[VAR_EDIT] = 0;
		SurveyDataDisplay::$_VARS[VAR_PRINT] = 0;
		SurveyDataDisplay::$_VARS[VAR_MONITORING] = 0;

		if(SurveyDataDisplay::$_VARS['view_date'])
			SurveyDataDisplay::$_VARS[VAR_SPR_LIMIT]++;
		
		# ponastavimo nastavitve- filter
		SurveyDataDisplay::setUpFilter();
		
		
		// Prestejemo stevilo stolpcev za vsako spremenljivko
		$spr_cont = 0;
		$rowArray = array();
		$row_count = 0;
		// visine naslovnih vrstic
		$first_height = 0;
		$second_height = 0;
		$third_height = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$row_count ++;
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if($spr_cont < 5) {
												
						$rowArray[$spr_cont]['cnt_grd'] = count($spremenljivka['grids']);		
						$rowArray[$spr_cont]['cnt_var'] = count($spremenljivka['grids'][0]['variables']);
						
						$row_count += count($spremenljivka['grids'][0]['variables']) * count($spremenljivka['grids']);
					}
					$spr_cont++;
				}
			}
		}

		$max_width = 180;
		$single_width = floor($max_width / $row_count);
		$single_width = ($single_width < 7) ? 7 : $single_width;

		
		$this->pdf->setFont('','B','5');
		$this->pdf->setDrawColor(0, 0, 0, 255);
		
		// PRVA VRSTICA (naslovi spremenljivk)
		$spr_cont = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$width = $single_width;
			$this->pdf->MultiCell($width, 5, $this->snippet($this->encodeText($lang['srv_data_date']), $width-5), 1, 'L', 0, 0, 0 ,0, true);		
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]))) {
					
					// 	prikazemo samo prvih 5 spremenljivk
					if ($spr_cont < 5) {					
						$width = $single_width * $rowArray[$spr_cont]['cnt_var'] * $rowArray[$spr_cont]['cnt_grd'];
						$this->pdf->MultiCell($width, 5, $this->snippet($this->encodeText($spremenljivka['naslov']), $width-5), 1, 'L', 0, 0, 0 ,0, true);		
					}
					$spr_cont++;
				}
			}
		}
		$this->pdf->MultiCell(1, 5, '', 0, 'L', 0, 1, 0 ,0, true);
		
		// DRUGA VRSTICA (imena gridov)
		$spr_cont = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$width = $single_width;
			$this->pdf->MultiCell($width, 5, $this->snippet($this->encodeText($lang['srv_data_date']), $width-5), 1, 'L', 0, 0, 0 ,0, true);		
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if ($spr_cont < 5) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							$width = $single_width * $rowArray[$spr_cont]['cnt_var'];
							$this->pdf->MultiCell($width, 5, $this->snippet($this->encodeText($grid['naslov']), $width-5), 1, 'C', 0, 0, 0 ,0, true);	
						}
					}
					$spr_cont++;
				}
			}
		}
		$this->pdf->MultiCell(1, 5, '', 0, 'L', 0, 1, 0 ,0, true);

		// TRETJA VRSTICA (imena variabel)
		$spr_cont = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$width = $single_width;
			$this->pdf->MultiCell($width, 5, $this->snippet($this->encodeText($lang['srv_data_date']), $width-5), 1, 'L', 0, 0, 0 ,0, true);		
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if($spr_cont < 5) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							if (count ($grid['variables']) > 0) {
								foreach ($grid['variables'] AS $vid => $variable ){
									
									$text = $variable['naslov'];		
									if ($variable['other'] == 1)
										$text .= '&nbsp;(text)';
										
									$width = $single_width;
									$this->pdf->MultiCell($width, 5, $this->snippet($this->encodeText($text), $width-5), 1, 'C', 0, 0, 0 ,0, true);
								}
							}
						}
					}
					$spr_cont++;
				}
			}
		}
		$this->pdf->MultiCell(1, 5, '', 0, 'L', 0, 1, 0 ,0, true);
		
		
		$this->pdf->setFont('','','5');
		
		
		// Nastavimo stevilo izpisov - prikazemo vse
		$_REC_LIMIT = '';
		//$_REC_LIMIT = ' NR==1,NR==50';			
						
		$_command = '';
		#preberemo podatke
		// polovimo vrstice z statusom 5,6 in jih damo v začasno datoteko
		if (IS_WINDOWS) {
			$_command = 'gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.SurveyDataDisplay::$_CURRENT_STATUS_FILTER.' { print $0 }" '.$this->dataFileName;
		}
		else {
			$_command = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.SurveyDataDisplay::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.$this->dataFileName;
		}

		// paginacija po stolpcih (spremenljivkah)
		if (IS_WINDOWS) {
			$_command .= ' | cut -d "|" -f 1,'.SurveyDataDisplay::$_VARIABLE_FILTER;
		} else {
			$_command .= ' | cut -d \'|\' -f 1,'.SurveyDataDisplay::$_VARIABLE_FILTER;
		}

		if ($_REC_LIMIT != '') {
			#paginating
			if (IS_WINDOWS) {
				$_command .= ' | awk '.$_REC_LIMIT;
			} else {
				$_command .= ' | awk '.$_REC_LIMIT;
			}
		} else {
			#$file_sufix = 'filtred_spr_pagination';
		}

		// zamenjamo | z </td><td> - NI POTREBNO
		if (IS_WINDOWS) {
			//$_command .= ' | sed "s*'.STR_DLMT.'*'.STR_LESS_THEN.'/td'.STR_GREATER_THEN.STR_LESS_THEN.'td'.STR_GREATER_THEN.'*g" >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
			$_command .= ' >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
		} 
		else {
			//$_command .= ' | sed \'s*'.STR_DLMT.'*</td><td>*g\' >> '
			//.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;	
			$_command .= ' >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;	
		}

		if (IS_WINDOWS) {
			# ker so na WINsih težave z sortom, ga damo v bat fajl in izvedemo :D
			$file_handler = fopen($folder.'cmd_'.$this->anketa['id'].'_to_run.bat',"w");
			fwrite($file_handler,$_command);
			fclose($file_handler);
			$out_command = shell_exec($folder.'cmd_'.$this->anketa['id'].'_to_run.bat');
			unlink($folder.'cmd_'.$this->anketa['id'].'_to_run.bat');
		} else {
			$out_command = shell_exec($_command);
		}

		if (file_exists($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT)) {
			$f = fopen ($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT, 'r');

			while ($line = fgets ($f)) {

				$dataArray = array();
				$dataArray = explode('|', $line);
				
				// Ne upostevamo prve vrednosti (ID)
				array_shift($dataArray);

				foreach($dataArray as $key => $val){
					
					$break = ($spr_cont == 4 && $gid == $rowArray[$spr_cont]['cnt_grd']-1 && $vid == $rowArray[$spr_cont]['cnt_var']-1) ? 1 : 0;
					$width = $single_width;
					$this->pdf->MultiCell($width, 5, $this->encodeText($val), 1, 'C', 0, $break, 0 ,0, true);
				}
				$this->pdf->MultiCell(1, 5, '', 0, 'L', 0, 1, 0 ,0, true);
			}
		}
		
		if ($f) {
			fclose($f);
		}
		if (file_exists($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT)) {
			unlink($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT);
		}
	}
	
		
	// dodamo prvo stran
	function createFrontPage(){
		
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
}

?>