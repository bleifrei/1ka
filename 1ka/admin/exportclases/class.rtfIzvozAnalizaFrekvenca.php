<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.rtfIzvozAnalizaSums.php');
	include_once('../exportclases/class.rtfIzvozAnalizaFunctions.php');
	require_once('../exportclases/class.enka.rtf.php');

	define("FNT_TIMES", "Times New Roman", true);
	define("FNT_ARIAL", "Arial", true);

	define("FNT_MAIN_TEXT", FNT_TIMES, true);
	define("FNT_QUESTION_TEXT", FNT_TIMES, true);
	define("FNT_HEADER_TEXT", FNT_TIMES, true);

	define("FNT_MAIN_SIZE", 12, true);
	define("FNT_QUESTION_SIZE", 10, true);
	define("FNT_HEADER_SIZE", 10, true);
	
	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge


class RtfIzvozAnalizaFrekvenca {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $CID = null;							# class za inkrementalno dodajanje fajlov
	
	var $current_loop = 'undefined';
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $sprID = null, $loop = null){
		global $site_path;
		global $global_user_id;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){

			$this->anketa['id'] = $anketa;
			$this->spremenljivka = $sprID;
			
			SurveyAnalysis::Init($this->anketa['id']);
			SurveyAnalysis::$setUpJSAnaliza = false;

			// create new RTF document
			$this->rtf = new enka_RTF();
			
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

	function getAnketa()
	{ return $this->anketa['id']; }

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		//Close and output rtf document
//		$this->rtf->Output($fileName, 'I');
		$this->rtf->display($fileName = "analiza.rtf",true);
	}

	function init()
	{
		global $lang;
		
		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'left');
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'right');
		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right');
		$this->rtf->set_default_font(FNT_TIMES, FNT_MAIN_SIZE);
		return true;
	}

	function createRtf()
	{
		global $site_path;
		global $lang;
	   		
		// izpisemo prvo stran
		//$this->createFrontPage();
		
		$this->rtf->draw_title($lang['export_analisys_freq']);
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		
		# preberemo header
		if ($this->headFileName !== null) {
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
					$this->rtf->new_line(1);
					$this->rtf->set_font("Arial Black", 9);
					$this->rtf->add_text($this->enkaEncode($lang['srv_zanka_note'].$loop['text']), 'left');
					$this->rtf->set_font("Arial", 8);
					$this->rtf->new_line();
				
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
						$this->rtf->new_line(1);
						$this->rtf->set_font("Arial Black", 9);
						$this->rtf->add_text($this->enkaEncode($lang['srv_zanka_note'].$loop['text']), 'left');
						$this->rtf->set_font("Arial", 8);
						$this->rtf->new_line();
						
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
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {

					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1: # radio - prikjaže navpično					
						case 2: #checkbox  če je dihotomna:
						case 3: # dropdown - prikjaže navpično					
						case 6: # multigrid
						case 4:	# text
						case 7:# variabla tipa »število«
						case 8:	# datum
						case 16: #multicheckbox če je dihotomna:
						case 17: #razvrščanje  če je ordinalna 
						case 18: # vsota 
						case 19: # multitext
						case 20: # multi number
						case 21: # besedilo* 
						case 22: # kalkulacija
							self::frequencyVertical($spid);
						break;
						case 5:
							# nagovor
							rtfIzvozAnalizaSums::sumNagovor($spid,'freq');
						break;
						
					}

				} 
					
			} // end if $spremenljivka['tip'] != 'm'
				
		} // end foreach SurveyAnalysis::$_HEADERS
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
		
		//prva vrstica			
		self::tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica
		self::tableHeader();	
		// konec naslovne vrstice
		
		
		$_answersOther = array();
		
		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# izpišemo vlejavne odgovore
		$_current_grid = null;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
			
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){

				$_sequence = $variable['sequence'];	# id kolone z podatki
				if (($variable['text'] != true && $variable['other'] != true) 
				|| (in_array($spremenljivka['tip'],array(4,8,21,22)))){
					# dodamo ime podvariable
					//if ($_variables_count > 1 && in_array($spremenljivka['tip'],array(2,6,7,16,17,18,19,20,21))) {
					if ($inline_legenda) {
						# ali rišemo dvojno črto med grupami
						if ( $_current_grid != $gid && $_current_grid !== null && $spremenljivka['tip'] != 6) {
							$options['doubleTop'] = true;
							$_current_grid = $gid;
						} else {
							$options['doubleTop'] = false;
							$_current_grid = $gid;
						}
						self::outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							if ($counter < $maxAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21))) { // text, number, datum, mtext, mnumber, text* 
										$options['isTextAnswer'] = true;
									} else {
										$options['isTextAnswer'] = false;
									}
									$counter = rtfIzvozAnalizaSums::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								}
							}
						}
						# izpišemo sumo veljavnih
						$counter = rtfIzvozAnalizaSums::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = rtfIzvozAnalizaSums::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = rtfIzvozAnalizaSums::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = rtfIzvozAnalizaSums::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		self::tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				rtfIzvozAnalizaSums::outputOtherAnswers($oAnswers);
			$this->tableEnd();
			}
		}
	}
		
	function outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$_options = array()) {
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
		
		$text[] = $this->encodeText($variable['variable']);
		
		$text[] = $this->encodeText($variable['naslov']);
		
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		
		self::tableRow($text);
	}
	
	
	function createFrontPage()
	{
		global $lang;
		
		$this->rtf->new_line(10);
		$this->rtf->TextCell($this->encodeText( SurveyInfo::getInstance()->getSurveyTitle()), array('width' => 9500, 'height' => 3,
		 'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
		 'colorF' => "0", 'colorB' => "0" ) );
		$this->rtf->new_line(3);
		// dodamo info:
		$this->rtf->TextCell("", array('width' => 9500, 'height' => 1,
		 'align' => 'left', 'valign' => 'bottom' , 'border' => array('bottom'),'colorF' => "0" ) );

		$infoTable = array();

		$imenaTable = array();
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim() )
			$imenaTable[] = array($lang['export_firstpage_shortname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), "");
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim() )
			$imenaTable[] = array($lang['export_firstpage_longname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), "");
			
		$imenaTable[] = array($lang['export_firstpage_qcount'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyQuestionCount()), "" );
		$imenaTable[] = array($lang['export_firstpage_active_from'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyStartsDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_active_until'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyExpireDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_author'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInsertName()),"" );
		$imenaTable[] = array($lang['export_firstpage_edit'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyEditName()),"" );
		$imenaTable[] = array($lang['export_firstpage_date'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInsertDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_date'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyEditDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_desc'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInfo()),"" );			
		$this->rtf->TableFromArray( array( 4600, 4600 ), $imenaTable);

		$this->rtf->new_page();
	}

	function enkaEncode($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return strip_tags($text);
	}	
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function snippet($text,$length=64,$tail="...")
	{
		/*$text = trim($text);
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
		}*/
		return $text;
	}

	function setGrupa($grupa) {$this->grupa = $grupa;}
	function getGrupa() {return $this->grupa;}
	function setUserId($usrId) {$this->usrId = $usrId;}
	function getUserId() {return ($this->usrId)?$this->usrId:false;}
	function setDisplayFrontPage($display) {$this->pi['displayFrontPage'] = $display;}
	function getDisplayFrontPage() {return ($this->pi['displayFrontPage'] == true || $this->pi['displayFrontPage'] == 1);}


	function tableFirstLine($field1, $field2){
		global $lang;
		
		$defw_full = 10300;
		$defw_part = 900;
		$defw_part2 = 9400;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';		
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		$this->rtf->MyRTF .= "{\par";
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($field1),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($field2),20,'...') . '\cell';
					
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
	}
	
	function tableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '&nbsp; ';
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('borderB' => 1, 'bold' => 'B', 'align2' => 'C');
		
		self::tableRow($naslov, $params);	
	}
	
	function tableRow($arrayText, $arrayParams=array()){
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1300;
		$defw_part2 = 4200;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') .$align.'\cell';
			
		for($i=0; $i<4; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	
		
	function tableEnd(){
	
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}
		
	
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
		$result = number_format($result, $digit, '.', ',') . $sufix;

		return $result;
	}
	
}


?>
