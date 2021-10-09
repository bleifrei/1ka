<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.enka.pdf.php');
	
	/** Include path **/
	set_include_path('../exportclases/');

	/** PHPPowerPoint */
	include '../exportclases/PHPPowerPoint.php';

	/** PHPPowerPoint_IOFactory */
	include '../exportclases/PHPPowerPoint/IOFactory.php';
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2, true); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2, true); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);


/** 
 * @desc Class za generacijo ppt-ja
 */
class pptIzvozHeatmapImage {

	var $anketa;					// trenutna anketa
	var $spremenljivka;					// trenutna spremenljivka

	var $headFileName = null;		// pot do header fajla
	var $dataFileName = null;		// pot do data fajla
	var $dataFileStatus = null;		// status data datoteke
	
	var $ppt;
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
		if ( is_numeric($anketa) )
		{
			$this->anketa = $anketa;
			$this->spremenljivka = $sprID;
			
			SurveyAnalysis::Init($this->anketa);
			SurveyAnalysis::$setUpJSAnaliza = false;
			
			SurveyChart::Init($this->anketa);
			
			// create new PPT document
			$this->ppt = new PHPPowerPoint();
			
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
			return false;
		}

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa) )
		{
			SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);
			$this->skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');
			$this->numbering = SurveyDataSettingProfiles :: getSetting('chartNumbering');
			$this->frontpage = SurveyDataSettingProfiles :: getSetting('chartFP');
		}
		else
			return false;

		return true;
	}
	
	function getFile($fileName)
	{
		//Close and output PDF document
		ob_end_clean();
		
		$objWriter = PHPPowerPoint_IOFactory::createWriter($this->ppt, 'PowerPoint2007');
		
		header("Pragma: no-cache");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");;
		header("Content-Disposition: attachment;filename=".$fileName);
		ob_clean();
		flush();
		
		$objWriter->save('php://output');
		
		//readfile(str_replace('.php', '.pptx', __FILE__));
		
		exit;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		return strip_tags($text);
	}

	function createPpt(){		
		global $site_path;
		global $lang;		

		$this->ppt->getProperties()->setCreator("1ka");
		$this->ppt->getProperties()->setLastModifiedBy("1ka");
		$this->ppt->getProperties()->setTitle("PPT Izvoz");
		$this->ppt->getProperties()->setSubject("PPT Izvoz");
		$this->ppt->getProperties()->setDescription("PPT Izvoz grafov");
		$this->ppt->getProperties()->setKeywords("office 2007 openxml php");
		$this->ppt->getProperties()->setCategory("PPT Izvoz grafov");

		$this->ppt->removeSlideByIndex(0);
		
		
		// izpisemo prvo stran
		if($this->frontpage == 1){
			$this->createFrontPage();
		}

			
		# preberemo header
		if ($this->headFileName !== null) {
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
		
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();

			# če nimamo zank
			if(count(SurveyAnalysis::$_LOOPS) == 0){
				$this->displayImage($this->spremenljivka);
				//$this->displayCharts();
			}
			else{
				// izrisemo samo eno tabelo iz enega loopa
				if($this->current_loop > 0){
					
					$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
					$loop['cnt'] = $this->current_loop;
					SurveyAnalysis::$_CURRENT_LOOP = $loop;
					
					//$this->displayCharts();
					$this->displayImage($this->spremenljivka);
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
						/*$currentSlide = $this->ppt->createSlide();
						$shape = $currentSlide->createRichTextShape();
						$shape->setHeight(30);
						$shape->setWidth(600);
						$shape->setOffsetX(20);
						$shape->setOffsetY(90);
						//$shape->getAlignment()->setHorizontal( PHPPowerPoint_Style_Alignment::HORIZONTAL_CENTER );
						$textRun = $shape->createTextRun($lang['srv_zanka_note'].$loop['text']);
						$textRun->getFont()->setBold(true);
						$textRun->getFont()->setSize(12);
						$textRun->getFont()->setColor( new PHPPowerPoint_Style_Color( 'FF000000' ) );*/

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
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {
				
					// Ce imamo radio tip in manj kot 5 variabel po defaultu prikazemo piechart
					$vars = count($spremenljivka['options']);
					$type = 0;
					if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $vars < 5 )
						$type = 2;
						
					//ce imamo nominalno spremenljivko ali ce je samo 1 variabla nimamo povprecij
					if($spremenljivka['tip'] == 6 && ($spremenljivka['cnt_all'] == 1 || $spremenljivka['skala'] == 1) && $type == 0 )
						$type = 2;
				
					/*if($spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 19 || $spremenljivka['tip'] == 21 || $spremenljivka['tip'] == 22){
						// ce imamo vklopljeno nastavitev prikaz tabel med grafi (default)
						if($spremenljivka['tip'] == 19)
							self::sumMultiText($spid, 'sums');
						else
							self::frequencyVertical($spid);
					}*/
					if( in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20)) ){
						// Prikazemo posamezen graf
						$this->displayChart($spid, $type);
					}
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
			
		} // end foreach self::$_HEADERS
	}
	
	// Vstavimo graf
	function displayImage($spid, $type=0, $sort=0, $value_type=0){
		global $site_path;
		global $lang;
		global $site_url;
						
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		$image = $site_path.'main/survey/uploads/heatmap'.$spid.'.png';	//mora biti local path in ne url
		
		$stevilcenje = ($this->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $stevilcenje . $spremenljivka['naslov'];
		
/* 		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			$title .= ' (n = '.$DataSet->GetNumerus().')';
		} */
		
		// IZRIS GRAFA	
		$currentSlide = $this->ppt->createSlide();

		// slika
		$shape = $currentSlide->createDrawingShape();
		$shape->setName('Chart');
		$shape->setDescription($title);
		$shape->setPath($image);
		$shape->setWidth(800);
		//$shape->setHeight(400);
		$shape->setOffsetX(75);
		$shape->setOffsetY(130);
				
		// naslov
		$shape = $currentSlide->createRichTextShape();
		$shape->setHeight(80);
		$shape->setWidth(600);
		$shape->setOffsetX(170);
		$shape->setOffsetY(40);
		$shape->getAlignment()->setHorizontal( PHPPowerPoint_Style_Alignment::HORIZONTAL_CENTER );
		$textRun = $shape->createTextRun($title);
		$textRun->getFont()->setBold(true);
		$textRun->getFont()->setSize(16);
		$textRun->getFont()->setColor( new PHPPowerPoint_Style_Color( 'FF000000' ) );
		
		# izpišemo še tekstovne odgovore za polja drugo
		/*$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}*/
	}	
	
	// dodamo prvo stran
	function createFrontPage(){
		global $lang;
			
		$currentSlide = $this->ppt->createSlide();
		
		$shape = $currentSlide->createRichTextShape();

		$shape->setWidth(600);
		$shape->setOffsetX(170);
		$shape->setOffsetY(200);
		$shape->getAlignment()->setHorizontal( PHPPowerPoint_Style_Alignment::HORIZONTAL_CENTER );
		$textRun = $shape->createTextRun(SurveyInfo::getInstance()->getSurveyTitle());
		$textRun->getFont()->setBold(true);
		$textRun->getFont()->setSize(38);
		$textRun->getFont()->setColor( new PHPPowerPoint_Style_Color( 'FF000000' ) );
		
		$shape->createBreak();
		
		$textRun = $shape->createTextRun($lang['srv_analiza_charts']);
		$textRun->getFont()->setBold(true);
		$textRun->getFont()->setSize(22);
		$textRun->getFont()->setColor( new PHPPowerPoint_Style_Color( 'FF000000' ) );
		
		$shape->createBreak();
		$shape->createBreak();
		
		$textRun = $shape->createTextRun($this->getEntryDates());
		$textRun->getFont()->setBold(false);
		$textRun->getFont()->setSize(14);
		$textRun->getFont()->setColor( new PHPPowerPoint_Style_Color( 'FF000000' ) );		
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

	/** Izriše frekvence v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function frequencyVertical($spid) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		
		# koliko zapisov prikažemo naenkrat
		//$num_show_records = SurveyAnalysis::getNumRecords();
		$chartTableMore = SurveyDataSettingProfiles :: getSetting('chartTableMore');
		$num_show_records = ($chartTableMore == 0) ? 10 : 1000;
		
		// ce imamo prazno in ne prikazujemo praznih tabel
		$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
		if($hideEmpty == 1){
			
			$emptyData = true;
		
			if (count($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);
				
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					
					if(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0)
						$emptyData = false;
				}
			}
		
			if($emptyData){
				self::displayEmptyWarning($spid);
				return;
			}
		}
		
		$this->pdf->ln(5);
		
		$this->pdf->setFont('','b','6');
		$stevilcenje = ($this->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $stevilcenje . $spremenljivka['naslov'];
		$this->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
		
		//prva vrstica			
		/*$this->pdf->setFont('','b','6');		
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'C', 0, 1, 0 ,0, true);*/
		
		//druga vrstica
		self::tableHeader();
		$this->pdf->setFont('','','6');		
		// konec naslovne vrstice
		
		
		$_answersOther = array();
		
		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# izpišemo vlejavne odgovore
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
		
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){
				
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if (($variable['text'] != true && $variable['other'] != true) 
				|| (in_array($spremenljivka['tip'],array(4,8,21)))){
					# dodamo ime podvariable
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							if ($counter < $num_show_records) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21))) { // text, number, datum, mtext, mnumber, text* 
										$options['isTextAnswer'] = true;
										# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
										$options['textAnswerExceed'] = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
									} else {
										$options['isTextAnswer'] = false;
										$options['textAnswerExceed'] = false;
									}
									$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								}
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				//pdfIzvozAnalizaSums::outputOtherAnswers($oAnswers);
			}
		}
		
		$this->pdf->ln(5);
	}
	
	
	function outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,&$_kumulativa,$_options=array()) {
		global $lang;
		
		$text = array();
		
		# opcije		
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.SurveyAnalysis::$cssColors['0_' . ($counter & 1)];
		$fill = ($counter % 2 == 1) ? 1 : 0;

		$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid; 
		
		# ali presegamo število prikazanih vrstic, takrat v zadnji prikazani dodamo link več.. ostale vrstice pa skrijemo
		if ($options['textAnswerExceed'] == true) {
			if ($counter == TEXT_ANSWER_LIMIT ) {
				# link za več
				$show_more = '<div id="valid_row_togle_'.$_sequence.'" class="floatRight blue pointer" onclick="showHidenTextRow(\''.$_sequence.'\');return false;">(več...)</div>'.NEW_LINE;
			} elseif ($counter > TEXT_ANSWER_LIMIT ) {
				$hide_row = ' hidden';
				$_exceed = true;
			}			
		}
		
		//if ($counter < TEXT_MAX_ANSWER_LIMIT) {
	 		$text[] = '';

			$addText = (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
			$text[] = $this->encodeText('  '.$vkey.$addText);

			$text[] = (int)$vAnswer['cnt'];
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, NUM_DIGIT_PERCENT, '%'));
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_valid, NUM_DIGIT_PERCENT, '%'));
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_kumulativa, NUM_DIGIT_PERCENT, '%'));

		/*} elseif ($counter == TEXT_MAX_ANSWER_LIMIT ) {
	 		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'">';
	 		echo '<td class="anl_bl anl_ac anl_br gray anl_dash_bt anl_dash_bb" colspan="'.(6+(int)SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']+((int)SurveyAnalysis::$_SHOW_LEGENDA*2)).'"> . . . Prikazujemo samo prvih '.TEXT_MAX_ANSWER_LIMIT.' veljavnih odgovorov!</td>';
			echo '</tr>';
		}*/
		
		$arrayParams = array('fill' => $fill, 'align2' => 'C');
		
		self::tableRow($text, $arrayParams);
		
		$counter++;
		return $counter;
	}
	
	function outputSumaValidAnswerVertical($counter,$_sequence,$spid,$_options=array()) {
		global $lang;
		
		$text = array();	
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$currentMissingProfile == 2) ? TRUE : FALSE;
		
		$_sufix = '';

		$text[] = $this->encodeText($lang['srv_anl_valid']);
		$text[] = $this->encodeText($lang['srv_anl_suma1']);
		
		$text[] = $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0);
		
		$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, NUM_DIGIT_PERCENT, '%'));
		$text[] = $this->encodeText(SurveyAnalysis::formatNumber(100, NUM_DIGIT_PERCENT, '%'));
		
		$text[] = '';

		
		self::tableRow($text);
		
		$counter++;
		return $counter;		
	}
	
	function outputInvalidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array()) {
		global $lang;	
		
		$text = array();
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
 
		$_sufix = '';
		
		$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;		
		if($_Z_MV){
			$text[] = '';
			
			$text[] = $this->encodeText($vkey.' (' . $vAnswer['text'].')');
			//echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.SurveyAnalysis::formatNumber($_invalid, NUM_DIGIT_PERCENT, '%').'</div>'.NEW_LINE;
			//echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>'.NEW_LINE;
			
			$text[] = $this->encodeText((int)$vAnswer['cnt']);

			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, NUM_DIGIT_PERCENT, '%'));
			
			$text[] = '';
			$text[] = '';
			
			$arrayParams = array('align2' => 'C');
			
			$this->tableRow($text);
		}
		
		$counter++;
		return $counter;
	}
	
	function outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$_options = array()) {
		global $lang;
			
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.SurveyAnalysis::$cssColors['text_' . ($counter & 1)];
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		if(!$_brez_MV){
			$text = array();
			
			$text[] = $this->encodeText($lang['srv_anl_missing']);	
			
			$text[] = $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt']);			
			
			$answer['cnt'] =  SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
			$text[] = $this->encodeText((int)$answer['cnt']);
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, NUM_DIGIT_PERCENT, '%'));
			$text[] = '';
			$text[] = '';
			
			$this->tableRow($text);
		}
			
		$counter++;
		return $counter;
	}
	
	function outputSumaVertical($counter,$_sequence,$spid, $_options = array()) {
		global $lang;
		
		# opcije			
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$cssBck = ' '.SurveyAnalysis::$cssColors['0_' .($counter & 1)];

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		if(!$_brez_MV){
		
			$text = array();
		
			$text[] = '';
			$text[] = $this->encodeText($lang['srv_anl_suma2']);
			$text[] = $this->encodeText((SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0));	
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber('100', NUM_DIGIT_PERCENT, '%'));
			$text[] = '';	
			$text[] = '';
			
			$this->tableRow($text);
		}	
	}

	/** izpišemo tabelo z tekstovnimi odgovori drugo
	 * 
	 * @param $skey
	 * @param $oAnswers
	 * @param $spid
	 */
	function outputOtherAnswers($oAnswers) {
		global $lang;
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];

		
		// Naslov posameznega grafa
		$stevilcenje = ($this->numbering == 1 ? $_variable['variable'].' - ' : '');
		$title = $stevilcenje . SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )';
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
		$this->pdf->setFont('','','6');

		$counter = 0;
		$_kumulativa = 0;
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		//$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		
		$this->pdf->setY($this->pdf->getY() + 5);
	}

	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiText($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# koliko zapisov prikažemo naenkrat
		//$num_show_records = SurveyAnalysis::getNumRecords();
		$chartTableMore = SurveyDataSettingProfiles :: getSetting('chartTableMore');
		$num_show_records = ($chartTableMore == 0) ? 10 : 1000;
		
		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		// ce imamo prazno in de prikazujemo praznih tabel
		$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
		if($_answers['validCnt'] == 0 && $hideEmpty == 1){
			self::displayEmptyWarning($spid);
			return;
		}
		
		$this->pdf->ln(5);
		
		$this->pdf->setFont('','b','6');
		$stevilcenje = ($this->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $stevilcenje . $spremenljivka['naslov'];
		$this->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
		

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
	
		//prva vrstica		
		$this->pdf->MultiCell(19, 5, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(37, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(90, 5, $this->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$this->pdf->MultiCell(19, 5, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(37, 14, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
			$count = 0;
			$text = array();
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$text[] = $_col['naslov'];
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			
				$count++;
			}
			$this->dynamicCells($text, $count, 90, 14);
			$this->pdf->ln(5);

			$last = 0;
			
			$height = 9;
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$this->pdf->MultiCell(19, 5, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
				$this->pdf->MultiCell(37, $height, $this->encodeText($grid['naslov']), 1, 'C', 0, 0, 0 ,0, true);
								
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					
					$count = 0;
					$text = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {							

							# tabela z navedbami
							$index=0;
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) {
								$text2 = '';
								foreach ($_valid_answers AS $answer) {
									$index++;

									$_ans = $answer[$_sequence];

									if ($_ans != null && $_ans != '') {
										$text2 .= $_ans.', ';
									}
								}
								$text[] = substr($text2, 0, -2);
							}
							else {
								$text[] = '&nbsp;';
							}
							
							$count++;
						}
						
					}
					$last = $_max_i;			
				}
				$this->dynamicCells($text, $count, 90, $height);	
				$this->pdf->ln($height);			
			}			
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
		
		$this->pdf->ln(5);
	}
	
	// Izpis opozorila ce ni vnesenih podatkov in ne prikazujemo grafa
	function displayEmptyWarning($spid){
		
		/*$spremenljivka = SurveyAnalysis::$_HEADERS[$spid]; 
		
		// Naslov posameznega grafa
		$this->pdf->setFont('','b','6');
		$this->pdf->MultiCell(165, 5, 'Graf '.$spremenljivka['variable'].' nima veljavnih podatkov!', 0, 'C', 0, 1, 0 ,0, true);
		$this->pdf->setFont('','','6');*/
	}
	
	
	function tableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '';
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('border' => 'TB', 'bold' => 'B', 'align2' => 'C');
		
		self::tableRow($naslov, $params);	
	}
	
	function tableRow($arrayText, $arrayParams=array()){
			
		$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;
		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
			
		$fill = (isset($arrayParams['fill'])) ? $arrayParams['fill'] : 0;

		$this->pdf->MultiCell(19, $height, /*$this->encodeText($arrayText[0])*/'', 0, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(100, $height, $this->encodeText($arrayText[1]), 1, $arrayParams['align2'], $fill, 0, 0 ,0, true);
		$this->pdf->MultiCell(27, $height, $arrayText[2], 1, 'C', $fill, 1, 0 ,0, true);
		/*$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[5]), 1, 'C', 0, 1, 0 ,0, true);*/
	}

	//izrisemo dinamicne celice (podamo sirino, stevilo celic in vsebino)
	function dynamicCells($arrayText, $count, $width, $height, $arrayParams=array()){
			
		if($count > 0){
			$singleWidth = round($width / $count);
			$lastWidth = $width - (($count-1)*$singleWidth);
		}
		else{
			$singleWidth = $width;
			$lastWidth = $width;
		}
				
		if($arrayText[0] == '')
			$arrayText[0] = '';
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 1 : $height = 4.7 + ($linecount-1)*3.3;*/
		
		for($i=0; $i<$count-1; $i++){
			if($arrayText[$i] == '')
				$arrayText[$i] = '';
		
			$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($arrayText[$i]), 1, 'C', 0, 0, 0 ,0, true);
		}
		
		//zadnje polje izrisemo druge sirine ker se drugace zaradi zaokrozevanja tabela porusi	
		$lastWidth = ($lastWidth < 4) ? 4 : $lastWidth;		
		if($count > 0)
			$this->pdf->MultiCell($lastWidth, $height, $this->encodeText($arrayText[$count-1]), 1, 'C', 0, 0, 0 ,0, true);
		else
			$this->pdf->MultiCell($lastWidth, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
	}
}

?>