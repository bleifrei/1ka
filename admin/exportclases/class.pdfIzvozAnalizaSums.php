<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
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
class PdfIzvozAnalizaSums {

	var $anketa;				// trenutna anketa (array)
	var $spremenljivka;		// trenutna spremenljivka
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	
	var $current_loop = 'undefined';
	
	static public $_FILTRED_OTHER = array(); 				# filter za polja drugo
	
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
	{ 			
		// popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		
		return strip_tags($text);
	}

	function createPdf()
	{		
		global $site_path;
		global $lang;
		global $global_user_id;
			   		
		// izpisemo prvo stran
		//$this->createFrontPage();
	   		
		$this->pdf->AddPage();
		
		$this->pdf->setFont('','B','11');
		$this->pdf->MultiCell(150, 5, $lang['export_analisys_sums'], 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
		
		$this->pdf->SetDrawColor(128, 128, 128);
		$this->pdf->setFont('','','6');									
								
		# preberemo header
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE || $this->dataFileStatus == FILE_STATUS_SRV_DELETED) {
		
			$this->pdf->MultiCell(150, 5, 'NAPAKA!!! Manjkajo datoteke s podatki.', 0, 'L', 0, 1, 0 ,0, true);
			
		} else {

			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
			
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
		SurveyAnalysis::getFrequencys();
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		$line_break = '';
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm')
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 && (($global_user_id === 0 || $global_user_id === null) || in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES ) )
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {
					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1:
							# radio - prikaže navpično					
							self::sumVertical($spid,'sums');
						break;
						
						case 2:
							#checkbox  če je dihotomna:
							self::sumVerticalCheckbox($spid,'sums');
						break;
						
						case 3:
							# dropdown - prikjaže navpično					
							self::sumVertical($spid,'sums');
						break;
						
						case 6:
							# multigrid
							self::sumHorizontal($spid,'sums');
						break;
						
						case 16:
							#multicheckbox če je dihotomna:
							self::sumMultiHorizontalCheckbox($spid,'sums');
						break;
						
						case 17:
							#razvrščanje  če je ordinalna 
							self::sumHorizontal($spid,'sums');
						break;
						
						case 4:	# text
						case 8:	# datum
							self::sumTextVertical($spid,'sums');
						break;
						
						case 21: # besedilo*
							# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
							if ($spremenljivka['cnt_all'] == 1) {
								// če je enodimenzionalna prikažemo kot frekvence
								// predvsem zaradi vprašanj tipa: language, email... 
								self::sumTextVertical($spid,'sums');
							} else {
								self::sumMultiText($spid,'sums');
							}
						break;
						
						case 19: # multitext
							self::sumMultiText($spid,'sums');
						break;
						
						case 7:
						case 18:
						case 22:
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumNumberVertical($spid,'sums');
						break;
						
						case 20:
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumMultiNumber($spid,'sums');
						break;
						
						case 5:
							# nagovor
							self::sumNagovor($spid,'sums');
						break;	
                                            
                                                case 26: # lokacija
                                                        self::sumLokacija($spid,'sums');
						break;
					}
					
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
				
		} // end foreach self::$_HEADERS	
	}
	

	/** Izriše sumarnik v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumVertical($spid,$_from) {
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# dodamo opcijo kje izrisujemo legendo
		$inline_legenda = false;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica
		$this->tableHeader();
		$this->pdf->setFont('','','6');
		
		$show_valid_percent = (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;											
		// konec naslovne vrstice

		
		$_answersOther = array();
		$sum_xi_fi=0;
		$N = 0;
									
		$_tmp_for_div = array();
		# izpišemo vlejavne odgovore
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			# dodamo dodatne vrstice z albelami grida
			if (count($grid['variables']) > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['text'] != true && $variable['other'] != true) {
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;

					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							// za povprečje
							$xi = $vkey;
							$fi = $vAnswer['cnt'];
							
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;

							if ($vAnswer['cnt'] > 0 /*&& $counter < $maxAnswer*/ || true) {	# izpisujemo samo tiste ki nisno 0
								
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
							# za poznejše računannje odklona
							$_tmp_for_div[] = array('xi'=>$xi, 'fi'=>$fi, 'sequence'=>$_sequence); 
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

		# odklon
		$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
		#standardna diviacija
		$div = 0;
		$sum_pow_xi_fi_avg  = 0;
		foreach ( $_tmp_for_div as $tkey => $_tmp_div_data) {
			$xi = $_tmp_div_data['xi'];
			$fi =  $_tmp_div_data['fi'];
			
			$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
		}
		$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

		# izpišemo še odklon in povprečje
		if ($show_valid_percent == 1 && SurveyAnalysis::$_HEADERS[$spid]['skala'] != 1) {
			
			$this->pdf->ln(1);
			
			$height = 5;
			
			$this->pdf->MultiCell(18, $height, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(90, $height, $this->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(18, $height, $this->encodeText($lang['srv_analiza_opisne_povprecje']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(18, $height, $this->encodeText(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(18, $height, $this->encodeText($lang['srv_analiza_opisne_odklon']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(18, $height, $this->encodeText(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 1, 0 ,0, true);
		
			/*$text = array();
			
			$text[] = '';
			$text[] = '';

			$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje']);
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($avg,NUM_DIGIT_AVERAGE,''));
			
			$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($div,NUM_DIGIT_AVERAGE,''));
			
			$this->tableRow($text);*/
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}

	function sumVerticalCheckbox($spid,$_from) {
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		$inline_legenda = count ($spremenljivka['grids']) > 1;
		if ($variable['other'] != '1' && $variable['text'] != '1') {
			$_tip =  SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala');
		} else {
			$_tip =  $lang['srv_analiza_vrsta_bese'];
			$_oblika =  $lang['srv_analiza_oblika_nomi'];
		}
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0) 
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_valid_cnt[$gid] = max($_valid_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				$_valid[$gid][$vid] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'];
				$_navedbe[$gid] += SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
			}
		}
			
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		$this->pdf->MultiCell(18, 5, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(50, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(80, 5, $this->encodeText($lang['srv_analiza_opisne_units']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(32, 5, $this->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);	
		
		//tretja vrstica
		$text = array();
		
		$text[] = '';
		$text[] = '';
		$text[] = $this->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_valid']);
		$text[] = $this->encodeText('% - '.$lang['srv_analiza_opisne_valid']);
		$text[] = $this->encodeText($lang['srv_analiza_num_units_valid']);
		$text[] = $this->encodeText('% - '.$lang['srv_analiza_num_units_valid']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = $this->encodeText('%');

		$this->tableRowVerticalCheckbox($text);
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic
		
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] as $gid => $grid) {
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				if ($variable['other'] != 1) {
					$_sequence = $variable['sequence'];
					$cssBack = "anl_bck_desc_2 ".($vid == 0 && $gid != 0 ? 'anl_double_bt ' : '');
					
					$text = array();
		
					$text[] = $this->encodeText($variable['variable']);
					$text[] = $this->encodeText($variable['naslov']);				
					
					// Frekvence
					$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					// Veljavni
					$text[] = $this->encodeText((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']));

					// Procent - veljavni
					$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0; 
					$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					$_max_valid = max ($_max_valid, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
					
					// Ustrezni
					$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					// % Ustrezni
					$valid = (int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
					$valid = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$_percent = ($_max_appropriate > 0 ) ? 100*$valid / $_max_appropriate : 0;
					$text[] =  $this->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					
					$text[] =  $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					$_percent = ($_navedbe[$gid] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / $_navedbe[$gid] : 0;
					$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					$this->tableRowVerticalCheckbox($text);
											
				} else {
					# drugo 
				}
			}
			
			$text = array();
			
			$text[] = '';
			
			$text[] = $this->encodeText($lang['srv_anl_suma_valid']);
			
			$text[] = '';
			
			$text[] = $this->encodeText($_max_valid);
			$text[] = '';			
			
			$text[] = $this->encodeText($_max_appropriate);	
			$text[] = '';
			
			$text[] = $this->encodeText($_navedbe[$gid]);
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
						
			$this->tableRowVerticalCheckbox($text);
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}		
	}
	
	/** Izriše nagovor
	 * 
	 */
	function sumNagovor($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_tip = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala'); 

		$this->pdf->setFont('','b','6');
		
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);
	}
	
	/** Izriše number odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumNumberVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if ((int)$spremenljivka['enota'] == 0 && SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1) {
			$show_enota = false;
		}
		
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				
				# za povprečje				
				$sum_xi_fi=0;
				$N = 0;
				$div=0;
				$min = null;
				$max = null;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {

						$fi = $_validFreq['cnt'];
						$sum_xi_fi += $xi * $fi ;
						$N += $fi;
						$min = $min != null ? min($min,$xi) : $xi;
						$max = max($max,$xi);
					}
				}

				#povprečje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
				$sum_avg += $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'] = $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'] = $min;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'] = $max;
				
				#standardna diviacija
				$div = 0;
				$sum_pow_xi_fi_avg  = 0;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {
						$fi = $_validFreq['cnt'];
						$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
					}
				}
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
				
				#določimo še polja drugo za kasnejši prikaz
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}		
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		$text = array();
		
		$text[] = '';
		
		if ($show_enota) {
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$text[] = $this->encodeText($lang['srv_analiza_opisne_subquestion']);;
			} else {
				$text[] = $this->encodeText($lang['srv_analiza_opisne_variable_text']);
			}
		} else {
			$text[] = '';
		}
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_m']);
		$text[] = $this->encodeText($lang['srv_analiza_num_units']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_max']);

		$this->tableRowNumberVertical($text);
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic

		$_answersOther = array();
		$_grupa_cnt = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			if (count($spremenljivka['grids']) > 1 && $_grupa_cnt !== 0 && $spremenljivka['tip'] != 6) {
				$grid['new_grid'] = true;
			}

			$_grupa_cnt ++;
			if (count($grid['variables']) > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) {
						$_sequence = $variable['sequence'];
			
						$text = array();
		
						if ($spremenljivka['tip'] != 7 ) {
							$text[] = $this->encodeText($variable['variable']);
						}
						else
							$text[] = '';
					
						if ($show_enota) {
							$text[] = $this->encodeText((count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov']);
						} else {
							$text[] = '';;
						}
						
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
						$text[] = (int)$_approp_cnt[$gid];
						$text[] = SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						$text[] = SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'];
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'];

						$this->tableRowNumberVertical($text);
						
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}
				
			}
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}

	/** Izriše sumarnik v horizontalni obliki za multigrid
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumHorizontal($spid,$_from) {

		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();
		$_clmn_cnt = count($spremenljivka['options']);

		# pri razvrščanju dodamo dva polja za povprečje in odklon
		$additional_field = false;
		$add_fld = 0;
		
		if ($spremenljivka['tip'] == 17 || $spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || ($spremenljivka['tip'] == 6 && $spremenljivka['skala'] != 1)) {
			$additional_field = true;
			$add_fld = 2;
		}

		# pri radiu in dropdown ne prikazujemo podvprašanj
		$_sub_question_col = 1;
		if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
			$_sub_question_col  = 0;
		}

		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		$this->pdf->MultiCell(18, 5, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(30, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(72, 5, $this->encodeText($lang['srv_analiza_opisne_answers']), 1, 'C', 0, 0, 0 ,0, true);
		
		if ($additional_field){
			$this->pdf->MultiCell(15, 5, $this->encodeText($lang['srv_analiza_opisne_valid']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(15, 5, $this->encodeText($lang['srv_analiza_num_units']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(15, 5, $this->encodeText($lang['srv_analiza_opisne_povprecje']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(15, 5, $this->encodeText($lang['srv_analiza_opisne_odklon']), 1, 'C', 0, 1, 0 ,0, true);
		}
		else{
			$this->pdf->MultiCell(30, 5, $this->encodeText($lang['srv_analiza_opisne_valid']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(30, 5, $this->encodeText($lang['srv_analiza_num_units']), 1, 'C', 0, 1, 0 ,0, true);
		}
		
		//tretja vrstica		
		$text = array();
		$count = 0;
		$height_title = 0;
		if (count($spremenljivka['options']) > 0) {
		
			$singleWidth = round(57 / count($spremenljivka['options']));
			
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 		
				$text[] = $_label;
						
				$height_title = ($height_title < $this->getCellHeight($_label, $singleWidth)) ? $this->getCellHeight($_label, $singleWidth) : $height_title;
				$count++;
			}
		}		
		
		$this->pdf->MultiCell(18, $height_title, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(30, $height_title, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		
		$this->dynamicCells($text, $count, 57, $height_title);
		
		$this->pdf->MultiCell(15, $height_title, $this->encodeText($lang['srv_anl_suma1']), 1, 'C', 0, 0, 0 ,0, true);
		if ($additional_field){
			$this->pdf->MultiCell(15, $height_title, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(15, $height_title, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(15, $height_title, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(15, $height_title, $this->encodeText(''), 1, 'L', 0, 1, 0 ,0, true);
		}
		else{
			$this->pdf->MultiCell(30, $height_title, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(30, $height_title, $this->encodeText(''), 1, 'L', 0, 1, 0 ,0, true);
		}
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic
		

		#zlopamo skozi gride 
		$podtabela = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				if ($variable['other'] != true) {

					// Ce gre za dvojno tabelo naredimo vrstico s naslovom podtabele
					if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
						
						// Če začnemo z drugo podtabelo izpišemo vrstico z naslovom
						if($podtabela != $grid['part']){
							
							$subtitle = $spremenljivka['double'][$grid['part']]['subtitle'];
							$subtitle = $subtitle == '' ? $lang['srv_grid_subtitle_def'].' '.$grid['part'] : $subtitle;
										
							$this->pdf->setFont('','b','6');
							$this->pdf->MultiCell(180, $height_title, $this->encodeText($subtitle), 1, 'C', 0, 1, 0 ,0, true);
							$this->pdf->setFont('','','6');
							
							$podtabela = $grid['part'];
						}
					}
				
					if($variable['naslov'] == '')
						$variable['naslov'] = '';
						
					/*$linecount = $this->pdf->getNumLines($this->encodeText($variable['naslov']), 30);
					$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
					$height = $this->getCellHeight($this->encodeText($variable['naslov']), 30);
					$height = ($height < 8 ? 8 : $height);
					
					//ce smo na prelomu strani
					if( ($this->pdf->getY() + $height) > 270){					
						$this->drawLine();			
						$this->pdf->AddPage('P');
						$arrayParams['border'] .= 'T';
					}
					
					$this->pdf->MultiCell(18, $height, $this->encodeText($variable['variable']), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(30, $height, $this->encodeText($variable['naslov']), 1, 'C', 0, 0, 0 ,0, true);
					
					# za odklon in povprečje				
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					
					$count = 0;
					$text = array();					
					if (count($spremenljivka['options']) > 0) {
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;  

							$text[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'].' ('.SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')';
													
							$count++;
						}
					}
					$this->dynamicCells($text, $count, 57, $height);
					
					// suma
					$this->pdf->MultiCell(15, $height, $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'].' ('.SurveyAnalysis::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')'), 1, 'C', 0, 0, 0 ,0, true);
					
					// zamenjano veljavni ustrezni
					if ($additional_field){
						$this->pdf->MultiCell(15, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']), 1, 'C', 0, 0, 0 ,0, true);
						$this->pdf->MultiCell(15, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']), 1, 'C', 0, 0, 0 ,0, true);
					}
					else{
						$this->pdf->MultiCell(30, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']), 1, 'C', 0, 0, 0 ,0, true);
						$this->pdf->MultiCell(30, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']), 1, 'C', 0, 1, 0 ,0, true);
					}
					
					# za odklon in povprečje
					if ($additional_field){
						# odklon
						$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
						#standardna diviacija
						$div = 0;
						$sum_pow_xi_fi_avg  = 0;
						if (count($spremenljivka['options']) > 0) {
							foreach ( $spremenljivka['options'] as $xi => $kategorija) {
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$xi]['cnt'];
								$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
							}
						}
						$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
						
						$this->pdf->MultiCell(15, $height, $this->encodeText(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 0, 0 ,0, true);
						$this->pdf->MultiCell(15, $height, $this->encodeText(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 1, 0 ,0, true);				
					}
				} 
				else {
					# immamo polje drugo
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}	
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
	
	/** Izriše tekstovne odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumTextVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);
			
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica	
		$this->tableHeader();		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic
		
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']); 
			if ($_variables_count > 0)
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['other'] != true) {
					# dodamo dodatne vrstice z labelami grida
					if ($_variables_count > 1) {
						self::outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if (/*$vAnswer['cnt'] > 0 &&*/ $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
								# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
								$textAnswerExceed = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								$options['isTextAnswer']=true;
								$options['textAnswerExceed'] = $textAnswerExceed;
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
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
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
        
        /** Izriše lokacijske odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumLokacija($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
                $enota = $spremenljivka['enota'];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];
	
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$height = 0;
		
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
			
			// Testiramo visino vrstice glede na najdaljsi text
			foreach ($text AS $string){
				$singleWidth = ($count > 0) ? round(162 / $count): 162;					
				$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
			}
			
			$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
			$this->dynamicCells($text, $count, 162, $height);
			$this->pdf->ln($height);

			$last = 0;
						
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);		
				$height = 0;
								
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
                                        
                                        $answers = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$index=0;
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) {
								$text2 = '(';
								foreach ($_valid_answers AS $answer) {

									$_ans = $answer[$_sequence];
                                                                        if($enota != 3)
                                                                            $_ans = str_replace("<br>","), (",$_ans);

									if ($_ans != null && $_ans != '') {
                                                                            if($enota == 3)
										$text2 .= $_ans."), (";
                                                                            else
                                                                                $answers[$count][$index]='('.$this->encodeText($_ans).')';
									}
                                                                        
                                                                    $index++;
								}
                                                                if($enota == 3)
                                                                    $text[] = substr($text2, 0, -3);
							}
							else {
								$text[] = '&nbsp;';
							}
							
							$count++;
						}
						
					}
					$last = $_max_i;			
				}
                                
                                if($enota != 3){
                                    for($i=0; $i<sizeof($answers[0]); $i++){
                                        $row = array();
                                        for($j=0; $j<$count; $j++){
                                            // Testiramo visino vrstice glede na najdaljsi text
                                            $singleWidth = ($count > 0) ? round(162 / $count): 162;					
                                            $height = ($this->getCellHeight($answers[$j][$i], $singleWidth) > $height) ? $this->getCellHeight($answers[$j][$i], $singleWidth) : $height;
                                            $row[$j] = $answers[$j][$i];
                                        }

                                        $this->sumLokacijaRowOutput($row, $count, $height, $grid['variable']);
                                    }
                                }
                                else{
                                    // Testiramo visino vrstice glede na najdaljsi text
                                    foreach ($text AS $string){
                                            $singleWidth = ($count > 0) ? round(162 / $count): 162;					
                                            $height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
                                    }
                                    $this->sumLokacijaRowOutput($text, $count, $height, $grid['variable']);
                                }
			}			
		}
	}
        
        /**
         * Izrise vrstico prilagojeno za lokacijo
         * 
         * @param type $text - array odgovorov
         * @param type $count - st variabel/stolpcev
         * @param type $height - izracunana najvisja visina celice v vrstici
         * @param type $variable - array variabel/stolpcev
         */
	function sumLokacijaRowOutput($text, $count, $height, $variable) {
            $this->pdf->MultiCell(18, $height, $this->encodeText($variable), 1, 'C', 0, 0, 0 ,0, true);
            $this->dynamicCells($text, $count, 162, $height);	
            $this->pdf->ln($height);
	}
	
	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiText($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# pogledamo koliko je max št odgovorov pri posameznem podvprašanju
/*		$_max_answers = array();
		$_max_answers_cnt = 0;
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			
			$_variables_count = count($grid['variables']);				
			if ($_variables_count > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_answers[$gid][$vid] = count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
					$_max_answers_cnt = max( $_max_answers_cnt, count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) ); 
				}
			}
		}
*/		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();
		//$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		$this->pdf->MultiCell(18, 5, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(54, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(108, 5, $this->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$height = 0;
		
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
			
			// Testiramo visino vrstice glede na najdaljsi text
			foreach ($text AS $string){
				$singleWidth = ($count > 0) ? round(108 / $count): 108;					
				$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
			}
			
			$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(54, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
			$this->dynamicCells($text, $count, 108, $height);
			$this->pdf->ln($height);

			$last = 0;
						
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);		
				$height = 0;
								
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
				
				// Testiramo visino vrstice glede na najdaljsi text
				foreach ($text AS $string){
					$singleWidth = ($count > 0) ? round(108 / $count): 108;					
					$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
				}
				
				$this->pdf->MultiCell(18, $height, $this->encodeText($grid['variable']), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->MultiCell(54, $height, $this->encodeText($grid['naslov']), 1, 'C', 0, 0, 0 ,0, true);
				
				$this->dynamicCells($text, $count, 108, $height);	
				$this->pdf->ln($height);			
			}			
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
                
	/** Izriše multi number odgovore. izpiše samo povprečja
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiNumber($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		$this->pdf->MultiCell(18, 5, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(54, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(108, 5, $this->encodeText($lang['srv_analiza_sums_average']), 1, 'C', 0, 1, 0 ,0, true);
		
		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$this->pdf->MultiCell(18, 13, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(54, 13, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
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
			$this->dynamicCells($text, $count, 108, 13);
			$this->pdf->ln(5);

			$last = 0;

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$this->pdf->MultiCell(18, 5, $this->encodeText($grid['variable']), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->MultiCell(54, 5, $this->encodeText($grid['naslov']), 1, 'C', 0, 0, 0 ,0, true);
								
				
				if ($_variables_count > 0) {
					
					$count = 0;
					$text = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$text[] = SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						}
						$count++;
					}
					$this->dynamicCells($text, $count, 108, 5);	
					$this->pdf->ln(5);
				}	
			}
		}	
	}
	
	
	/** Izriše sumarnik v horizontalni obliki za multi checbox
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumMultiHorizontalCheckbox($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		# tekst vprašanja
		
	
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);
		
		//druga vrstica		
		$this->pdf->MultiCell(18, 5, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(54, 5, $this->encodeText($lang['srv_analiza_opisne_answers']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, 5, $this->encodeText($lang['srv_analiza_opisne_valid']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, 5, $this->encodeText($lang['srv_analiza_num_units']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(54, 5, $this->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);
		
		$this->pdf->setFont('','','6');
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		
		//tretja vrstica		
		$count = 0;
		$height = 0;
		$text = array();
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$text[] = $variable['naslov'].' ('.$variable['gr_id']. ')';
				
				$singleWidth = round(54 / (count($_variables) + 1));
				$height = ($height < $this->getCellHeight($variable['naslov'].' ('.$variable['gr_id']. ')', $singleWidth)) ? $this->getCellHeight($variable['naslov'].' ('.$variable['gr_id']. ')', $singleWidth) : $height;
			}
	
			$count++;
		}
		
		$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		
		$this->dynamicCells($text, $count, 54, $height);

		$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
				
		$count = 0;
		$text = array();
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$text[] = $variable['naslov'].' ('.$variable['gr_id']. ')';
			}
			$count++;
		}
		$this->dynamicCells($text, $count, 44, $height);
		
		$this->pdf->MultiCell(10, $height, $this->encodeText($lang['srv_anl_suma1']), 1, 'C', 0, 1, 0 ,0, true);
		
		
		//vrstice s podatki
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			
			$_cnt = 0;
			$height = $this->getCellHeight($this->encodeText($grids['naslov']), 18);
			$height = ($height < 8 ? 8 : $height);
			
			# vodoravna vrstice s podatki
			$this->pdf->MultiCell(18, $height, $this->encodeText($grids['variable']), 1, 'C', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(18, $height, $this->encodeText($grids['naslov']), 1, 'C', 0, 0, 0 ,0, true);

			$_arguments = 0;

			$_max_appropriate = 0;
			$_max_cnt = 0;
			// prikaz frekvenc
			$count = 0;
			$text = array();
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;
				
				$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}
		
				if ($variable['other'] != true) {
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0; 
					
					$text[] = $_cnt . ' (' . SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') . ')';
					$count++;
				}
				
			}
			$this->dynamicCells($text, $count, 54, $height);
			
			# veljavno 
			$this->pdf->MultiCell(18, $height, $this->encodeText($_max_cnt), 1, 'C', 0, 0, 0 ,0, true);
			#ustrezno
			$this->pdf->MultiCell(18, $height, $this->encodeText($_max_appropriate), 1, 'C', 0, 0, 0 ,0, true);
			
			
			$count = 0;
			$text = array();
			foreach ($grids['variables'] AS $vkey => $variable) {
				if ($variable['other'] != true) {
					$_sequence = $variable['sequence'];
					$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					
					$_percent = ($_arguments > 0 ) ? $_cnt * 100 / $_arguments : 0;  
					
					$text[] = $_cnt . ' (' . SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') . ')';
					$count++;
				}			
			}
			$this->dynamicCells($text, $count, 44, $height);
		
			$this->pdf->MultiCell(10, $height, $this->encodeText($_arguments), 1, 'C', 0, 1, 0 ,0, true);
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
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
		$cssBck = ' '.SurveyAnalysis::$cssColors['0_' . ($counter & 1)];

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
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));

		/*} elseif ($counter == TEXT_MAX_ANSWER_LIMIT ) {
	 		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'">';
	 		echo '<td class="anl_bl anl_ac anl_br gray anl_dash_bt anl_dash_bb" colspan="'.(6+(int)SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']+((int)SurveyAnalysis::$_SHOW_LEGENDA*2)).'"> . . . Prikazujemo samo prvih '.TEXT_MAX_ANSWER_LIMIT.' veljavnih odgovorov!</td>';
			echo '</tr>';
		}*/
		
		self::tableRow($text);
		
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
		
		$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		$text[] = $this->encodeText(SurveyAnalysis::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
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

			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = '';
			$text[] = '';
			
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
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
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
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '';	
			$text[] = '';
			
			$this->tableRow($text);
		}

		
	}

	function outputOtherAnswers($oAnswers) {
		global $lang;
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];
		
		//prva vrstica			
		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, $height, $this->encodeText($_variable['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, $height, $this->encodeText(SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )'), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica
		$this->tableHeader();
		$this->pdf->setFont('','','6');		
		// konec naslovne vrstice				

		$counter = 1;
		$_kumulativa = 0;
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
	}
	
	/** za multi grid tekstovne vrstice doda vrstico z labeliami grida
	 * 
	 * @param $gkey
	 * @param $gAnswer
	 * @param $spid
	 * @param $_options
	 */
	function outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$_options=array()) {
 		
		$text = array();
					
		$text[] = $this->encodeText($variable['variable']);
		$text[] = $this->encodeText(($grid['naslov'] != '' ? $grid['naslov']. '&nbsp;-&nbsp;' : '').$variable['naslov']);
		
		$text[] = '';
		$text[] = '';
		$text[] = '';
		$text[] = '';
		
		$this->tableRow($text);
		
		$counter++;
		return $counter;	
	}
	
	 
	 

	function createFrontPage(){
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

	function formatNumber($value,$digit=0,$sufix=""){
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
	
	function drawLine(){
		$cy = $this->pdf->getY();
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
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
		
		$this->tableRow($naslov, $params);	
	}
	
	function tableRow($arrayText, $arrayParams=array()){
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = $this->getCellHeight($this->encodeText($arrayText[1]), 90);
		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
		
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(90, $height, $this->encodeText($arrayText[1]), 1, $arrayParams['align2'], 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $arrayText[2], 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[5]), 1, 'C', 0, 1, 0 ,0, true);
	}

	function tableRowVerticalCheckbox($arrayText, $arrayParams=array()){
	
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 54);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = $this->getCellHeight($this->encodeText($arrayText[1]), 54);
		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(50, $height, $this->encodeText($arrayText[1]), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[5]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[6]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[7]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(16, $height, $this->encodeText($arrayText[8]), 1, 'C', 0, 1, 0 ,0, true);
	}

	function tableRowNumberVertical($arrayText, $arrayParams=array()){
	
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		for($i=2; $i<8; $i++){
			if( $arrayText[$i] == '' )
				$arrayText[$i] = '0';
		}
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 54);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = $this->getCellHeight($this->encodeText($arrayText[1]), 54);
		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}

		$arrayText[1] == '' ? $arrayParams['border'] = 0 : $arrayParams['border'] = 1;
		
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(54, $height, $this->encodeText($arrayText[1]), $arrayParams['border'], 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[5]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[6]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[7]), 1, 'C', 0, 1, 0 ,0, true);
	}

	function tableRowHorizontal($arrayText, $arrayParams=array()){
			
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = $this->getCellHeight($this->encodeText($arrayText[1]), 30);

		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(30, $height, $this->encodeText($arrayText[1]), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(78, $height, $this->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[5]), 1, 'C', 0, 1, 0 ,0, true);
	}

	function tableRowMultiText($arrayText, $arrayParams=array()){
	
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = $this->getCellHeight($this->encodeText($arrayText[1]), 30);
		
		//ce smo na prelomu strani
		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		$this->pdf->MultiCell(18, $height, $this->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(30, $height, $this->encodeText($arrayText[1]), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(132, $height, $this->encodeText($arrayText[2]), 1, 'C', 0, 1, 0 ,0, true);
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

	function getCellHeight($string, $width){
		
		// Star nacin
		//$linecount = $this->pdf->getNumLines($this->encodeText($string), $width);
		//$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.5) );
		
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