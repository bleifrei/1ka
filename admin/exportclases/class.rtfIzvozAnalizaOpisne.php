<?php

require_once("class.enka.rtf.php");
require_once('../../vendor/autoload.php');
include_once('../exportclases/class.rtfIzvozAnalizaFunctions.php');
include_once('../../function.php');
include_once('../survey/definition.php');


define("FNT_TIMES", "Times New Roman", true);
define("FNT_ARIAL", "Arial", true);

define("FNT_MAIN_TEXT", FNT_TIMES, true);
define("FNT_QUESTION_TEXT", FNT_TIMES, true);
define("FNT_HEADER_TEXT", FNT_TIMES, true);

define("FNT_MAIN_SIZE", 12, true);
define("FNT_QUESTION_SIZE", 10, true);
define("FNT_HEADER_SIZE", 10, true);


class RtfIzvozAnalizaOpisne {

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
		
		$this->rtf->draw_title($lang['export_analisys_desc']);
		$this->rtf->new_line(1);
		
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		
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
		SurveyAnalysis::getDescriptives();
		
		# izpi�emo opisne statistike
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		
		//prva vrstica
		$this->tableHeader();
		
		# dodamo �e kontrolo �e kli�emo iz displaySingleVar 
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
				# preverimo ali imamo samo eno variablo in �e iammo enoto
				if ((int)$spremenljivka['enota'] != 0 || $spremenljivka['cnt_all'] > 1 ) {
					$show_enota = true;
				}
				
				# izpi�emo glavno vrstico z podatki
				$_sequence  = null;
				# za enodimenzijske tipe izpi�emo podatke kar v osnovni vrstici
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

		$this->tableEnd();
	}
	
		
	/** Izri�e vrstico z opisnimi
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
			$text[] = $this->encodeText('&nbsp; ');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = $this->encodeText('&nbsp; ');
		
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
			
		$this->tableRow($text);
		
	}
	
	/** Izri�e vrstico z opisnimi
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
		$text[] = $this->encodeText((!$show_enota ? (int)$_desc['validCnt'] : '&nbsp; '));
		
		#ustrezno
		$text[] = $this->encodeText((!$show_enota ? (int)$_desc['allCnt'] : '&nbsp; '));
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else
			$text[] = $this->encodeText('&nbsp; ');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = $this->encodeText('&nbsp; ');
			
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
			
		$params = array('bold' => 'B');
		
		$this->tableRow($text, $params);
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

	function enkaEncode($text){ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return strip_tags($text);
	}	
	
	function encodeText($text){ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		return strip_tags($text);
	}
	
	/* Skrajsa niz in doda ... nakoncu
	 *  snippet(phrase,[max length],[phrase tail])
	 *  snippetgreedy(phrase,[max length before next space],[phrase tail])
	 *
	 * iz: http://snipplr.com/view/9520/php-substring-without-breaking-words/
	 */
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

	function setGrupa($grupa) {$this->grupa = $grupa;}
	function getGrupa() {return $this->grupa;}
	function setUserId($usrId) {$this->usrId = $usrId;}
	function getUserId() {return ($this->usrId)?$this->usrId:false;}
	function setDisplayFrontPage($display) {$this->pi['displayFrontPage'] = $display;}
	function getDisplayFrontPage() {return ($this->pi['displayFrontPage'] == true || $this->pi['displayFrontPage'] == 1);}

	
	function tableHeader(){	
		global $lang;
		
		$this->rtf->MyRTF .= "{\par";		
				
		$text = array();
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_variable']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_variable_text']);
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_m']);		
		$text[] = $this->encodeText($lang['srv_analiza_num_units']);			
		$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);			
		$text[] = $this->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_max']);
					
		$params = array('borderT' => 1, 'borderB' => 1, 'bold' => 'B', 'align2' => 'C');
		
		$this->tableRow($text, $params);	
	}
	
	function tableRow($arrayText, $arrayParams=0){
	
		$defw_full = 10300;
		$defw_part = 1100;
		$defw_part2 = 2600;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->enkaEncode($arrayText[1]). $align . '\cell';
			
		for($i=0; $i<6; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( ($i+2) * $defw_part + $defw_part2);
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
	}
	
	function tableEnd(){
	
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}
	
}


?>
