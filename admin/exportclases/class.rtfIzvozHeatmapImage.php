<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
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


class rtfIzvozHeatmapImage {

	var $anketa;			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;
	
	var $headFileName = null;		// pot do header fajla
	var $dataFileName = null;		// pot do data fajla
	var $dataFileStatus = null;		// status data datoteke
	
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
			
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
			
			SurveyZankaProfiles :: Init($this->anketa, $global_user_id);
			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
			
			SurveyAnalysis::Init($this->anketa);
			SurveyAnalysis::$setUpJSAnaliza = false;
						
			# inicializiramo grafe
			SurveyChart::Init($this->anketa);

			// create new RTF document
			$this->rtf = new enka_RTF();

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

	function getAnketa()
	{ return $this->anketa; }

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		//Close and output rtf document
//		$this->rtf->Output($fileName, 'I');
		//$this->rtf->display($fileName = "analiza.rtf",true);
		$this->rtf->display($fileName = 'rtf_analiza_heatmap_image_'.$this->spremenljivka.'.rtf',true);
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
		if($this->frontpage == 1)
			$this->createFrontPage();
		else{
			//$this->rtf->draw_title('ANALIZA - Grafi');
			$this->rtf->set_font("Arial Black", 15);
			$TITLE = $this->rtf->bold(1) . $this->rtf->underline(1) . $lang['export_analysis_heatmap_image'] . $this->rtf->underline(0) . $this->rtf->bold(0);
			$this->rtf->new_line();
			$this->rtf->add_text($TITLE, 'left');
			$this->rtf->new_line();
								 
			// Datum zbiranja podatkov
			$this->rtf->TextCell($this->getEntryDates(), array('width' => 9500, 'height' => 1,
			 'align' => 'left', 'valign' => 'top' , 'border' => '','colorF' => "0" ));	 
		}
			
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		
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
		} // end if else ($_headFileName == null)	
		
	}
	
	function displayImage($spremenljivka){
		global $site_url;
		$image = $site_url.'main/survey/uploads/heatmap'.$spremenljivka.'.png';
		$size = getimagesize($image);
		if($size[0] == 800)
			$scale = 75;
		elseif($size[0] == 2400)
			$scale = 50;
		else
			$scale = 100;
		$this->rtf->add_image($image, $scale, 'center');
		
	}
	
	function createFrontPage()
	{
		global $lang;
		
		$this->rtf->new_line(10);
		$this->rtf->TextCell($this->rtf->bold(1).$this->enkaEncode( SurveyInfo::getInstance()->getSurveyTitle()).$this->rtf->bold(0).'\\line\n '.$lang['srv_analiza_charts'], array('width' => 9500, 'height' => 3,
		 'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
		 'colorF' => "0", 'colorB' => "0" ) );
		 
		// Datum zbiranja podatkov
		$text = 
		$this->rtf->TextCell($this->getEntryDates(), array('width' => 9500, 'height' => 1,
		 'align' => 'center', 'valign' => 'bottom' , 'border' => '','colorF' => "0" ));
		 
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
		
		// Aktiviranost
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			$imenaTable[] = array($this->rtf->color(11).$this->enkaEncode($lang['srv_anketa_active2']).$this->rtf->color(0), "");
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				$imenaTable[] = array($this->rtf->color(17).$this->enkaEncode($lang['srv_survey_non_active_notActivated']).$this->rtf->color(0), "");
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				$imenaTable[] = array($this->rtf->color(17).$this->enkaEncode($lang['srv_survey_non_active']).$this->rtf->color(0), "");
			}
		}
		
		// Aktivnost	
		if( count($activity) > 0 ){
			$imenaTable[] = array($lang['export_firstpage_active_from'].': '.SurveyInfo::getInstance()->getSurveyStartsDate(), $lang['export_firstpage_active_until'].': '.SurveyInfo::getInstance()->getSurveyExpireDate());
		}
		
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
	
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
		$result = number_format($result, $digit, '.', ',') . $sufix;

		return $result;
	}


	// Izpis opozorila ce ni vnesenih podatkov in ne prikazujemo grafa
	function displayEmptyWarning($spid){
		
		/*$spremenljivka = SurveyAnalysis::$_HEADERS[$spid]; 
		
		// Naslov posameznega grafa
		$this->rtf->set_font("Arial Black", 8);
		
		$this->rtf->new_line();
		$this->rtf->add_text('Graf '.$spremenljivka['variable'].' nima veljavnih podatkov!', 'center');
		$this->rtf->new_line();*/
	}
	
	function displayTitle($spid){
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		$this->rtf->new_line(3);
		
		$stevilcenje = ($this->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $stevilcenje . $spremenljivka['naslov'];
		$TITLE = $this->rtf->bold(1) . $title . $this->rtf->bold(0);	
			
		// IZRIS GRAFA
		$this->rtf->set_font("Arial Black", 8);
		$this->rtf->add_text($TITLE, 'center');
		$this->rtf->new_line();
		if($spremenljivka['tip'] == 2){
			$this->rtf->set_font("Arial", 7);
			$this->rtf->add_text($lang['srv_info_checkbox'], 'center');
			$this->rtf->new_line();
		}
	}	
}


?>
