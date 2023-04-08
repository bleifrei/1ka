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


class RtfIzvozAnalizaCReport {

	var $ank_id;					// trenutna anketa
	var $usr_id;					// trenutni user

	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	
	var $creportProfile = 0;			// Izbran profil porocila
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...


	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null)
	{
		global $site_path;
		global $global_user_id;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->ank_id = $anketa;
			
			SurveyAnalysis::Init($this->ank_id);
			SurveyAnalysis::$setUpJSAnaliza = false;
			
			// Nastavimo pravi profil porocila
			$this->creportProfile = SurveyUserSetting :: getInstance()->getSettings('default_creport_profile');
			$this->creportProfile = isset($this->creportProfile) ? $this->creportProfile : 0;
				
			// Testiramo kako je obrnjen dokument (ce vsebuje crosstabe, means ali ttest je lanscape)
			$orientation = ($this->landscapeTest()) ? true : false; 
			
			// create new RTF document
			$this->rtf = new enka_RTF($orientation);
		
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->ank_id);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
			
			// Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->ank_id);

			SurveyMissingProfiles :: Init($this->ank_id,$global_user_id);
			SurveyConditionProfiles :: Init($this->ank_id, $global_user_id);
			SurveyZankaProfiles :: Init($this->ank_id, $global_user_id);
			SurveyTimeProfiles :: Init($this->ank_id, $global_user_id);

			SurveyDataSettingProfiles :: Init($this->ank_id);
			
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);	
			#preberemo HEADERS iz datoteke
			SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));			
			# odstranimo sistemske variable tipa email, ime, priimek, geslo
			SurveyAnalysis::removeSystemVariables();			
			# polovimo frekvence			
			SurveyAnalysis::getFrequencys();
			
			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->ank_id);
			$this->sessionData = SurveyUserSession::getData();
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		if ( SurveyInfo::getInstance()->SurveyInit($this->ank_id) && $this->init())
		{
			$this->usr_id = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->ank_id, $this->usr_id);
		}
		else
			return false;
				
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

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
		
		$what = 'creport_title_profile_'.$this->creportProfile;
		$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->usr_id' AND what='$what'");		

		if(mysqli_num_rows($sql) == 0){
			$titleString = $lang['export_analisys_creport'].': '.SurveyInfo::getInstance()->getSurveyTitle();
		}
		else{
			$row = mysqli_fetch_array($sql);		
			$titleString = $row['value'];
		}
		
		$this->rtf->draw_title($this->encodeText($titleString));
		
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		
		$sql = sisplet_query("SELECT * FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->usr_id' AND profile='$this->creportProfile' ORDER BY vrstni_red ASC");		
		if(mysqli_num_rows($sql) > 0){			
			
			// Loop po vseh dodanih elementih porocila
			while($row = mysqli_fetch_array($sql)){
		
				switch($row['type']){
					
					// sumarnik
					case '1':
						// naslov elementa
						$this->displayTitle($row);
				
						$this->displaySum($row);
						
						// Komentar elementa
						$this->displayComment($row['text']);
						
						break;
					
					// frekvence
					case '2':
						// naslov elementa
						$this->displayTitle($row);
						
						$this->displayFreq($row);
						
						// Komentar elementa
						$this->displayComment($row['text']);
						
						break;
					
					// opisne
					case '3':
						// naslov elementa
						$this->displayTitle($row);
						
						$this->displayDesc($row);
						
						// Komentar elementa
						$this->displayComment($row['text']);
						
						break;
					
					// grafi
					case '4':
						// naslov elementa
						$this->displayTitle($row);
						
						$this->displayChart($row);
						
						// Komentar elementa
						$this->displayComment($row['text']);
						
						break;
					
					// crosstab
					case '5':
						// naslov elementa
						$this->displayTitle($row);
						
						// tabela
						if($row['sub_type'] == '0')
							$this->displayCrosstab($row);
						// graf
						else
							$this->displayCrosstabChart($row);				
						
						// Komentar elementa
						$this->displayComment($row['text']);
						
						break;
					
					// mean
					case '6':
						// naslov elementa
						$this->displayTitle($row);
						
						// tabela
						if($row['sub_type'] == '0')
							$this->displayMean($row);
						// graf	
						else
							$this->displayMeanChart($row);			

						// Komentar elementa
						$this->displayComment($row['text']);
						
						break;
					
					// ttest
					case '7':
						// naslov elementa
						$this->displayTitle($row);
						
						// tabela
						if($row['sub_type'] == '0')
							$this->displayTTest($row);
						// graf
						else
							$this->displayTTestChart($row);
							
						// Komentar elementa
						$this->displayComment($row['text']);
				
						break;
						
					// text
					case '8':
						$this->displayText($row['text']);
				
						break;
						
					// break
					case '9':
						// naslov elementa
						$this->displayTitle($row);
						
						// tabela
						if($row['sub_type'] == '0')
							$this->displayBreak($row);
						// graf
						else
							$this->displayBreakChart($row);
							
						// Komentar elementa
						$this->displayComment($row['text']);
				
						break;
					
					// page break
					case '-1':
						$this->rtf->new_page();
						
						break;
				}
			}
		}
	}

	
	// Izpisemo sumarnik element
	function displaySum($element){
		
		$spid = $element['spr1'];
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# preverjamo ali je meta
		if (($spremenljivka['tip'] != 'm')
		 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
		 && (($global_user_id === 0 || $global_user_id === null) || in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES ) )) {
			
			# ali imamo sfiltrirano spremenljivko
			//if (isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) {

				rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='sums');
				
				# 	prikazujemo v odvisnosti od kategorije spremenljivke
				switch ($spremenljivka['tip']) {
					case 1:
						# radio - prikaže navpično					
						rtfIzvozAnalizaFunctions::sumVertical($spid,'sums');
					break;
					
					case 2:
						#checkbox  če je dihotomna:
						rtfIzvozAnalizaFunctions::sumVerticalCheckbox($spid,'sums');
					break;
					
					case 3:
						# dropdown - prikjaže navpično					
						rtfIzvozAnalizaFunctions::sumVertical($spid,'sums');
					break;
					
					case 6:
						# multigrid
						rtfIzvozAnalizaFunctions::sumHorizontal($spid,'sums');
					break;
					
					case 16:
						#multicheckbox če je dihotomna:
						rtfIzvozAnalizaFunctions::sumMultiHorizontalCheckbox($spid,'sums');
					break;
					
					case 17:
						#razvrščanje  če je ordinalna 
						rtfIzvozAnalizaFunctions::sumHorizontal($spid,'sums');
					break;
					
					case 4:	# text
					case 8:	# datum
						rtfIzvozAnalizaFunctions::sumTextVertical($spid,'sums');
					break;
					
					case 21: # besedilo*
						# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
						if ($spremenljivka['cnt_all'] == 1) {
							// če je enodimenzionalna prikažemo kot frekvence
							// predvsem zaradi vprašanj tipa: language, email... 
							rtfIzvozAnalizaFunctions::sumTextVertical($spid,'sums');
						} else {
							rtfIzvozAnalizaFunctions::sumMultiText($spid,'sums');
						}
					break;
					
					case 19: # multitext
						rtfIzvozAnalizaFunctions::sumMultiText($spid,'sums');
					break;
					
					case 7:
					case 18:
					case 22:
						# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
						rtfIzvozAnalizaFunctions::sumNumberVertical($spid,'sums');
					break;
					
					case 20:
						# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
						rtfIzvozAnalizaFunctions::sumMultiNumber($spid,'sums');
					break;
					
					case 5:
						# nagovor
						rtfIzvozAnalizaFunctions::sumNagovor($spid,'sums');
					break;							
				}
				
			//} 
				
		} // end if $spremenljivka['tip'] != 'm'
	}
	
	// Izpisemo frekvence element
	function displayFreq($element){
		
		$spid = $element['spr1'];
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# preverjamo ali je meta
		if (($spremenljivka['tip'] != 'm')
		 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
		 && (($global_user_id === 0 || $global_user_id === null) || in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES ) )) {
			
			# ali imamo sfiltrirano spremenljivko
			//if (isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) {

				rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='freq');
				
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
						rtfIzvozAnalizaFunctions::frequencyVertical($spid);
					break;
					case 5:
						# nagovor
						rtfIzvozAnalizaFunctions::sumNagovor($spid,'freq');
					break;
				}
			//} 
				
		} // end if $spremenljivka['tip'] != 'm'
	}
	
	// Izpisemo opisne stat element
	function displayDesc($element){
		global $lang;
		
		# polovimo frekvence			
		SurveyAnalysis::getDescriptives();
	
		$spid = $element['spr1'];
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
	
		rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='desc');
				
		rtfIzvozAnalizaFunctions::descTableHeader();	
	
		# preverjamo ali je meta
		if ($spremenljivka['tip'] != 'm'
		 && ( count(SurveyAnalysis::$_FILTRED_VARIABLES) == 0 || (count(SurveyAnalysis::$_FILTRED_VARIABLES) > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ))
		 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)) {

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
				rtfIzvozAnalizaFunctions::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
			} else {
			if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
				$variable = $spremenljivka['grids'][0]['variables'][0];
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$show_enota = false;
			}
				rtfIzvozAnalizaFunctions::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
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
								rtfIzvozAnalizaFunctions::displayDescriptivesVariablaRow($spremenljivka,$grid,$variable);
								
							}
						$grid['new_grid'] = false;
							
					}
				}
			 } //else: if (!$show_enota)
			 
			 rtfIzvozAnalizaFunctions::tableEnd();
			 
		 } // end if $spremenljivka['tip'] != 'm'	
	}
	
	// Izpisemo graf element
	function displayChart($element){
		
		$spid = $element['spr1'];
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# preverjamo ali je meta
		if (($spremenljivka['tip'] != 'm'
		 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
		 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))) {
			# ali imamo sfiltrirano spremenljivko
			//if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {
			
				// Ce imamo radio tip in manj kot 5 variabel po defaultu prikazemo piechart
				$vars = count($spremenljivka['options']);
				$type = 0;
				if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $vars < 5 )
					$type = 2;
					
				//ce imamo nominalno spremenljivko ali ce je samo 1 variabla nimamo povprecij
				if($spremenljivka['tip'] == 6 && ($spremenljivka['cnt_all'] == 1 || $spremenljivka['skala'] == 1) && $type == 0 )
					$type = 2;
			
				rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='charts');
			
				if($spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 19 || $spremenljivka['tip'] == 21 || $spremenljivka['tip'] == 22){
					// ce imamo vklopljeno nastavitev prikaz tabel med grafi (default)
					if($spremenljivka['tip'] == 19)
						rtfIzvozAnalizaFunctions::sumMultiText($spid, 'sums');
					else
						rtfIzvozAnalizaFunctions::frequencyVertical($spid);
				}
				elseif( in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20)) ){
					// Prikazemo posamezen graf
					rtfIzvozAnalizaFunctions::displayChart($spid, $type);
				}
			//}
				
		} // end if $spremenljivka['tip'] != 'm'
	}
	
	// Izpisemo crosstab tabelo
	function displayCrosstab($element){
		global $lang;
		
		// Napolnimo podatke crosstabu
		$crossData1 = explode("-", $element['spr1']);
		$crossData2 = explode("-", $element['spr2']);
		
		$crosstabClass = new SurveyCrosstabs();
		$crosstabClass->Init($this->ank_id);
		
		$crosstabClass->setVariables($crossData1[0],$crossData1[1],$crossData1[2],$crossData2[0],$crossData2[1],$crossData2[2]);	
		
		// Naslov
		if($element['spr1'] == '' || $element['spr2'] == '')		
			$title = $lang['srv_select_spr'];			
		else{				
			$show_variables_values = true;
			
			$spr1 = $crosstabClass->_HEADERS[$crossData1[1]];
			$spr2 = $crosstabClass->_HEADERS[$crossData2[1]];
			
			# za multicheckboxe popravimo naslov, na podtip
			$sub_q1 = null;
			$sub_q2 = null;
			if ($spr1['tip'] == '6' || $spr1['tip'] == '7' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '18' || $spr1['tip'] == '19' || $spr1['tip'] == '20' || $spr1['tip'] == '21' ) {
				foreach ($spr1['grids'] AS $grid) {
					foreach ($grid['variables'] AS $variable) {
						if ($variable['sequence'] == $v_first['seq']) {
							$sub_q1 .= strip_tags($spr1['naslov']);
							if ($show_variables_values == true ) {
								$sub_q1 .= ' ('.strip_tags($spr1['variable']).')';
							}
							if ($spr1['tip'] == '16') {
								$sub_q1 .= strip_tags($grid1['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid1['variable']) . ')' : '');
							} else {
								$sub_q1 .= strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
							}
						}
					}
				}
			}
			if ($sub_q1 == null) {
				$sub_q1 .=  strip_tags($spr1['naslov']);
				$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
			}
			if ($spr2['tip'] == '6' || $spr2['tip'] == '7' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '18' || $spr2['tip'] == '19' || $spr2['tip'] == '20' || $spr2['tip'] == '21') {
				foreach ($spr2['grids'] AS $grid) {
					foreach ($grid['variables'] AS $variable) {
						if ($variable['sequence'] == $v_second['seq']) {
							$sub_q2 .= strip_tags($spr2['naslov']);
							if ($show_variables_values == true) {
								$sub_q2 .= ' ('.strip_tags($spr2['variable']).')';
							}
							if ($spr2['tip'] == '16') {
								$sub_q2.= strip_tags($grid2['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid2['variable']) . ')' : '');
							} else {
								$sub_q2.= strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
							}
						}
					}
				}
			}
			if ($sub_q2 == null) {
				$sub_q2 .= strip_tags($spr2['naslov']);
				$sub_q2 .= ($show_variables_values == true ? ' ('.strip_tags($spr2['variable']).')' : '');
			}
					
			$title = $sub_q1 . ' / ' . $sub_q2;
		}
			
		$subtitle = '('.$lang['srv_crosstabs']. ($element['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';

		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='crosstab', $crosstabClass);
		rtfIzvozAnalizaFunctions::displayCrosstabsTable();
	}
	
	// Izpisemo crosstab graf
	function displayCrosstabChart($element){
		// Napolnimo podatke crosstabu
		$crossData1 = explode("-", $element['spr2']);
		$crossData2 = explode("-", $element['spr1']);
		
		$crosstabClass = new SurveyCrosstabs();
		$crosstabClass->Init($this->ank_id);
		
		
		// Ce iz breaka klicemo graf za crosstab tabelo moramo popravit grid
		if($element['type'] == '9'){
			foreach ($crosstabClass->_HEADERS[$crossData1[1]]['grids'] AS $gid => $grid) {
				foreach ($grid['variables'] AS $vkey => $vrednost) {
					if ($vrednost['sequence'] == $crossData1[0]) {
						$crossData1[2] = $gid;
					}
				}
			}
		}
		
		$crosstabClass->setVariables($crossData1[0],$crossData1[1],$crossData1[2],$crossData2[0],$crossData2[1],$crossData2[2]);	
		
		// Naslov
		if($element['spr1'] == '' || $element['spr2'] == '')		
			$title = $lang['srv_select_spr'];			
		else{				
			$show_variables_values = true;
			
			$spr1 = $crosstabClass->_HEADERS[$crossData1[1]];
			$spr2 = $crosstabClass->_HEADERS[$crossData2[1]];
			
			# za multicheckboxe popravimo naslov, na podtip
			$sub_q1 = null;
			$sub_q2 = null;
			if ($spr1['tip'] == '6' || $spr1['tip'] == '7' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '18' || $spr1['tip'] == '19' || $spr1['tip'] == '20' || $spr1['tip'] == '21' ) {
				foreach ($spr1['grids'] AS $grid) {
					foreach ($grid['variables'] AS $variable) {
						if ($variable['sequence'] == $v_first['seq']) {
							$sub_q1 .= strip_tags($spr1['naslov']);
							if ($show_variables_values == true ) {
								$sub_q1 .= ' ('.strip_tags($spr1['variable']).')';
							}
							if ($spr1['tip'] == '16') {
								$sub_q1 .= strip_tags($grid1['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid1['variable']) . ')' : '');
							} else {
								$sub_q1 .= strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
							}
						}
					}
				}
			}
			if ($sub_q1 == null) {
				$sub_q1 .=  strip_tags($spr1['naslov']);
				$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
			}
			if ($spr2['tip'] == '6' || $spr2['tip'] == '7' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '18' || $spr2['tip'] == '19' || $spr2['tip'] == '20' || $spr2['tip'] == '21') {
				foreach ($spr2['grids'] AS $grid) {
					foreach ($grid['variables'] AS $variable) {
						if ($variable['sequence'] == $v_second['seq']) {
							$sub_q2 .= strip_tags($spr2['naslov']);
							if ($show_variables_values == true) {
								$sub_q2 .= ' ('.strip_tags($spr2['variable']).')';
							}
							if ($spr2['tip'] == '16') {
								$sub_q2.= strip_tags($grid2['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid2['variable']) . ')' : '');
							} else {
								$sub_q2.= strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
							}
						}
					}
				}
			}
			if ($sub_q2 == null) {
				$sub_q2 .= strip_tags($spr2['naslov']);
				$sub_q2 .= ($show_variables_values == true ? ' ('.strip_tags($spr2['variable']).')' : '');
			}
					
			$title = $sub_q1 . ' / ' . $sub_q2;
		}
			
		$subtitle = '('.$lang['srv_crosstabs']. ($element['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';

		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		$this->crosstabVars = array($sub_q1, $sub_q2);
		
		rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='crosstab_chart', $crosstabClass);
		rtfIzvozAnalizaFunctions::displayCrosstabChart();
	}
	
	// Izpisemo mean tabelo
	function displayMean($element){

		// ustvarimo means objekt
		$meansClass = new SurveyMeans($this->ank_id);
		
		// Napolnimo podatke crosstabu
		$meanData1 = explode("-", $element['spr2']);
		$meanData2 = explode("-", $element['spr1']);

		$v_first = array('seq' => $meanData1[0], 'spr' => $meanData1[1], 'grd' => $meanData1[2]);
		$v_second =  array('seq' => $meanData2[0], 'spr' => $meanData2[1], 'grd' => $meanData2[2]);

		$_means[0] = $meansClass->createMeans($v_first, $v_second);
			
		rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='mean', $meansClass);
		rtfIzvozAnalizaFunctions::displayMeansTable($_means);
	}
	
	// Izpisemo mean graf
	function displayMeanChart($element){
		
		// ustvarimo means objekt
		$meansClass = new SurveyMeans($this->ank_id);
		
		// Napolnimo podatke crosstabu
		$meanData1 = explode("-", $element['spr2']);
		$meanData2 = explode("-", $element['spr1']);

		$v_first = array('seq' => $meanData1[0], 'spr' => $meanData1[1], 'grd' => $meanData1[2]);
		$v_second =  array('seq' => $meanData2[0], 'spr' => $meanData2[1], 'grd' => $meanData2[2]);

		$this->meanData2[0] = $v_first;
		$this->meanData1[0] = $v_second;
		
		$_means[0] = $meansClass->createMeans($v_first, $v_second);
			
		rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='mean_chart', $meansClass);
		rtfIzvozAnalizaFunctions::displayMeanChart(0);
	}
	
	// Izpisemo ttest tabelo
	function displayTTest($element){
		
		// ustvarimo ttest objekt
		$ttestClass = new SurveyTTest($this->ank_id);
		
		// Nastavimo session da lahko pravilno izrisemo tabelo/graf		  
		$ttestData1 = explode("-", $element['spr1']);		
		$ttestData2 = explode("-", $element['spr2']);
		
		$dataArray = array();
			
		$dataArray['spr2'] = $ttestData1[1];
		$dataArray['grid2'] = $ttestData1[2];
		$dataArray['seq2'] = $ttestData1[0];
		$dataArray['label2'] = strip_tags($this->getTTestLabel($element['spr1'], $ttestClass));

		$dataArray['sub_conditions'][0] = $ttestData1[3];
		$dataArray['sub_conditions'][1] = $ttestData1[4];
		
		$dataArray['variabla'][0]['seq'] = $ttestData2[0];
		$dataArray['variabla'][0]['spr'] = $ttestData2[1];
		$dataArray['variabla'][0]['grd'] = $ttestData2[2];
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::Init($this->ank_id);
		$sessionData = SurveyUserSession::getData('ttest');	
		$sessionData = $dataArray;
		SurveyUserSession::saveData($sessionData, 'ttest');

		// ustvarimo ttest objekt
		$ttestClass = new SurveyTTest($this->ank_id);
		
		if (count($this->sessionData['ttest']['sub_conditions']) > 1 ) {
			$variables1 = $ttestClass->getSelectedVariables();
			if (count($variables1) > 0) {
				foreach ($variables1 AS $v_first) {
					$ttest = null;
					$ttest = $ttestClass->createTTest($v_first, $this->sessionData['ttest']['sub_conditions']);
					
					rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='ttest', $ttestClass);
					rtfIzvozAnalizaFunctions::displayTTestTable($ttest);
				}
			}
		}
	}
	
	// Izpisemo ttest graf
	function displayTTestChart($element){
		
		// ustvarimo ttest objekt
		$ttestClass = new SurveyTTest($this->ank_id);
		
		// Nastavimo session da lahko pravilno izrisemo tabelo/graf		  
		$ttestData1 = explode("-", $element['spr1']);		
		$ttestData2 = explode("-", $element['spr2']);
		
		$dataArray = array();
			
		$dataArray['spr2'] = $ttestData1[1];
		$dataArray['grid2'] = $ttestData1[2];
		$dataArray['seq2'] = $ttestData1[0];
		$dataArray['label2'] = strip_tags($this->getTTestLabel($element['spr1'], $ttestClass));

		$dataArray['sub_conditions'][0] = $ttestData1[3];
		$dataArray['sub_conditions'][1] = $ttestData1[4];
		
		$dataArray['variabla'][0]['seq'] = $ttestData2[0];
		$dataArray['variabla'][0]['spr'] = $ttestData2[1];
		$dataArray['variabla'][0]['grd'] = $ttestData2[2];
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::Init($this->ank_id);
		$sessionData = SurveyUserSession::getData('ttest');	
		$sessionData = $dataArray;
		SurveyUserSession::saveData($sessionData, 'ttest');

		// ustvarimo ttest objekt
		$ttestClass = new SurveyTTest($this->ank_id);
		
		if (count($this->sessionData['ttest']['sub_conditions']) > 1 ) {
			$variables1 = $ttestClass->getSelectedVariables();
			if (count($variables1) > 0) {
				foreach ($variables1 AS $v_first) {
				
					rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='ttest_chart', $ttestClass);
					rtfIzvozAnalizaFunctions::displayTTestChart();
				}
			}
		}
	}
	
	// Izpisemo crosstab tabelo
	function displayBreak($element){
		global $lang;
		
		// Napolnimo podatke breaku
		$breakData1 = explode("-", $element['spr1']);
		$breakData2 = explode("-", $element['spr2']);
		
		$breakClass =  new SurveyBreak($this->ank_id);
		
		$forSpr = $breakData1[1];
		$_spr_data = $breakClass->_HEADERS[$forSpr];
		
		# poiščemo sekvenco
		$sekvenca = $breakData1[0];
		
		# poiščemo opcije
		$opcije = $_spr_data['options'];
					
		if ((int)$_spr_data['tip'] != 2) {
			$seqences[] = $sekvenca;
			$options = $opcije;
		} else {
			# za checkboxe imamo več sekvenc
			$seqences = explode('_',$_spr_data['sequences']);
			$options[1] = $opcije[1];
		}
		
		# za vsako opcijo posebej izračunamo povprečja za vse spremenljivke
		/*$frequencys = array();
		if (count($seqences) > 0) {
			foreach ($seqences as $seq) {
				
				if (count($options) > 0) {
					foreach ($options as $oKey => $option) {
						# zloopamo skozi variable
						$oKeyfrequencys = $breakClass->getAllFrequencys($oKey, $seq, $forSpr);
						if ($oKeyfrequencys != null) {
							$frequencys[$seq][$oKey] = $oKeyfrequencys;
						} 
					}
				}
			}
		}*/
		$frequencys = null;
		if (count($options) > 0) {
			foreach ($options as $okey => $option) {
				
				# zloopamo skozi variable
				$okeyfrequencys = $breakClass->getAllFrequencys($okey, $sekvenca, $forSpr);
				if ($okeyfrequencys != null) {
					if ($frequencys == null) {
						$frequencys = array();
					}
					$frequencys[$okey] = $okeyfrequencys;
				} 
			}
		}

		$spremenljivka = $breakClass->_HEADERS[$breakData2[1]];
		$spremenljivka['id'] = $breakData2[1];
				
		$tip = $spremenljivka['tip'];
		$skala = $spremenljivka['skala'];
			
			
		// Izrisujemo tabelo ki ni crosstab
		if( ($tip == 6 && $skala == 0) || in_array($tip, array(4,7,17,18,19,20,21)) ){
			
			rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='break', $breakClass);
			
			if($tip == 6 && $skala == 0)
				rtfIzvozAnalizaFunctions::displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka);
				
			elseif($tip == 4 || $tip == 19 || $tip == 21){
				// Nastavimo se katero podtabelo izrisemo (sekvenca odvisne spr)
				$spremenljivka['break_sub_table']['sequence'] = $breakData2[0];
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {				
					if($spremenljivka['break_sub_table']['sequence'] == $grid['variables'][0]['sequence']){
						$spremenljivka['break_sub_table']['key'] = $gkey;
						break;
					}
				}

				rtfIzvozAnalizaFunctions::displayBreakTableText($forSpr,$frequencys,$spremenljivka);
			}
			
			else{
				// Nastavimo se katero podtabelo izrisemo (sekvenca odvisne spr)
				$spremenljivka['break_sub_table']['sequence'] = $breakData2[0];
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {				
					if($spremenljivka['break_sub_table']['sequence'] == $grid['variables'][0]['sequence']){
						$spremenljivka['break_sub_table']['key'] = $gkey;
						break;
					}
				}

				rtfIzvozAnalizaFunctions::displayBreakTableNumber($forSpr,$frequencys,$spremenljivka);
			}
		}
		
		// Izrisujemo crosstab
		else{
			$this->displayCrosstab($element);		
		}
	}
	
	// Izpisemo crosstab graf
	function displayBreakChart($element){
		// Napolnimo podatke breaku
		$breakData1 = explode("-", $element['spr1']);
		$breakData2 = explode("-", $element['spr2']);
		
		$breakClass =  new SurveyBreak($this->ank_id);
		
		$forSpr = $breakData1[1];
		$_spr_data = $breakClass->_HEADERS[$forSpr];
		
		# poiščemo sekvenco
		$sekvenca = $breakData1[0];
		
		# poiščemo opcije
		$opcije = $_spr_data['options'];
					
		if ((int)$_spr_data['tip'] != 2) {
			$seqences[] = $sekvenca;
			$options = $opcije;
		} else {
			# za checkboxe imamo več sekvenc
			$seqences = explode('_',$_spr_data['sequences']);
			$options[1] = $opcije[1];
		}
		
		$frequencys = null;
		if (count($options) > 0) {
			foreach ($options as $okey => $option) {
				
				# zloopamo skozi variable
				$okeyfrequencys = $breakClass->getAllFrequencys($okey, $sekvenca, $forSpr);
				if ($okeyfrequencys != null) {
					if ($frequencys == null) {
						$frequencys = array();
					}
					$frequencys[$okey] = $okeyfrequencys;
				} 
			}
		}

		$spremenljivka = $breakClass->_HEADERS[$breakData2[1]];
		$spremenljivka['id'] = $breakData2[1];
				
		$tip = $spremenljivka['tip'];
		$skala = $spremenljivka['skala'];
			
		
		// Pri textovnih tipih vedno izrisemo tabelo
		if($tip == 4 || $tip == 21 || $tip == 19){
			// Nastavimo se katero podtabelo izrisemo (sekvenca odvisne spr)
			$spremenljivka['break_sub_table']['sequence'] = $breakData2[0];
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {				
				if($spremenljivka['break_sub_table']['sequence'] == $grid['variables'][0]['sequence']){
					$spremenljivka['break_sub_table']['key'] = $gkey;
					break;
				}
			}

			rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='break', $breakClass);
			rtfIzvozAnalizaFunctions::displayBreakTableText($forSpr,$frequencys,$spremenljivka);
		}
		
		// Izrisujemo graf ki ni crosstab
		elseif( ($tip == 6 && $skala == 0) || in_array($tip, array(7,17,18,20)) ){
			
			rtfIzvozAnalizaFunctions::init($this->ank_id, $this, $from='break', $breakClass);
			
			if($tip == 6 && $skala == 0)
				rtfIzvozAnalizaFunctions::displayBreakChart($forSpr,$frequencys,$spremenljivka);
							
			else{
				// Nastavimo se katero podtabelo izrisemo (sekvenca odvisne spr)
				$spremenljivka['break_sub_table']['sequence'] = $breakData2[0];
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {				
					if($spremenljivka['break_sub_table']['sequence'] == $grid['variables'][0]['sequence']){
						$spremenljivka['break_sub_table']['key'] = $gkey;
						break;
					}
				}

				rtfIzvozAnalizaFunctions::displayBreakChart($forSpr,$frequencys,$spremenljivka);
			}
		}
		
		// Izrisujemo crosstab
		else{
			$this->displayCrosstabChart($element);		
		}
	}
	
	// Izpisemo element z besedilom elementa
	function displayText($text){
		global $lang;

		// pretvorimo html text v rtf
		$text = $this->rtf->HTMLtoRTF($text);
		
		$this->rtf->TextCell($this->enkaEncode($text), array('width' => 10000, 'height' => 1,
			'align' => 'justify', 'valign' => 'bottom' , 'border' => '','colorF' => "0" ) );
		
		$this->rtf->new_line(1);
	}
	
	
	// Izpisemo naslov elementa
	function displayTitle($element){
		global $lang;
		
		// sumarnik
		if($element['type'] == '1'){
			$spr = SurveyAnalysis::$_HEADERS[$element['spr1']];
			
			if($element['spr1'] == '')
				$title = $lang['srv_select_spr'];
			else
				$title = $spr['variable'].' - '.$spr['naslov'];
				
			$subtitle = ' ('.$lang['srv_sumarnik'].')';
		}
		
		// freq
		elseif($element['type'] == '2'){
			$spr = SurveyAnalysis::$_HEADERS[$element['spr1']];
		
			if($element['spr1'] == '')
				$title = $lang['srv_select_spr'];
			else
				$title = $spr['variable'].' - '.$spr['naslov'];
				
			$subtitle = ' ('.$lang['srv_frequency'].')';
		}
		
		// desc
		elseif($element['type'] == '3'){
			$spr = SurveyAnalysis::$_HEADERS[$element['spr1']];
		
			if($element['spr1'] == '')
				$title = $lang['srv_select_spr'];
			else
				$title = $spr['variable'].' - '.$spr['naslov'];
				
			$subtitle = ' ('.$lang['srv_descriptor'].')';
		}
		
		// chart
		elseif($element['type'] == '4'){
			$spr = SurveyAnalysis::$_HEADERS[$element['spr1']];
		
			if($element['spr1'] == '')
				$title = $lang['srv_select_spr'];
			else
				$title = $spr['variable'].' - '.$spr['naslov'];
				
			$subtitle = ' ('.$lang['srv_chart'].')';
		}
		
		// crosstab
		elseif($element['type'] == '5'){
			
			// Napolnimo podatke crosstabu
			$crossData1 = explode("-", $element['spr1']);
			$crossData2 = explode("-", $element['spr2']);
			
			$crosstabClass = new SurveyCrosstabs();
			$crosstabClass->Init($this->ank_id);
			
			$crosstabClass->setVariables($crossData1[0],$crossData1[1],$crossData1[2],$crossData2[0],$crossData2[1],$crossData2[2]);	
			
			// Naslov
			if($element['spr1'] == '' || $element['spr2'] == '')		
				$title = $lang['srv_select_spr'];			
			else{				
				$show_variables_values = true;
				
				$spr1 = $crosstabClass->_HEADERS[$crossData1[1]];
				$spr2 = $crosstabClass->_HEADERS[$crossData2[1]];
				
				# za multicheckboxe popravimo naslov, na podtip
				$sub_q1 = null;
				$sub_q2 = null;
				if ($spr1['tip'] == '6' || $spr1['tip'] == '7' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '18' || $spr1['tip'] == '19' || $spr1['tip'] == '20' || $spr1['tip'] == '21' ) {
					foreach ($spr1['grids'] AS $grid) {
						foreach ($grid['variables'] AS $variable) {
							if ($variable['sequence'] == $v_first['seq']) {
								$sub_q1 .= strip_tags($spr1['naslov']);
								if ($show_variables_values == true ) {
									$sub_q1 .= ' ('.strip_tags($spr1['variable']).')';
								}
								if ($spr1['tip'] == '16') {
									$sub_q1 .= '<br />'. strip_tags($grid1['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid1['variable']) . ')' : '');
								} else {
									$sub_q1 .= '<br />' . strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
								}
							}
						}
					}
				}
				if ($sub_q1 == null) {
					$sub_q1 .=  strip_tags($spr1['naslov']);
					$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
				}
				if ($spr2['tip'] == '6' || $spr2['tip'] == '7' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '18' || $spr2['tip'] == '19' || $spr2['tip'] == '20' || $spr2['tip'] == '21') {
					foreach ($spr2['grids'] AS $grid) {
						foreach ($grid['variables'] AS $variable) {
							if ($variable['sequence'] == $v_second['seq']) {
								$sub_q2 .= strip_tags($spr2['naslov']);
								if ($show_variables_values == true) {
									$sub_q2 .= ' ('.strip_tags($spr2['variable']).')';
								}
								if ($spr2['tip'] == '16') {
									$sub_q2.= '<br />' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid2['variable']) . ')' : '');
								} else {
									$sub_q2.= '<br />' . strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
								}
							}
						}
					}
				}
				if ($sub_q2 == null) {
					$sub_q2 .= strip_tags($spr2['naslov']);
					$sub_q2 .= ($show_variables_values == true ? ' ('.strip_tags($spr2['variable']).')' : '');
				}
						
				$title = $sub_q1 . ' / ' . $sub_q2;
			}
				
			$subtitle = ' ('.$lang['srv_crosstabs']. ($element['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';
		}
		
		// mean
		elseif($element['type'] == '6'){
			
			// ustvarimo means objekt
			$meansClass = new SurveyMeans($this->ank_id);
			
			// Napolnimo podatke crosstabu
			$meanData1 = explode("-", $element['spr2']);
			$meanData2 = explode("-", $element['spr1']);

			$v_first = array('seq' => $meanData1[0], 'spr' => $meanData1[1], 'grd' => $meanData1[2]);
			$v_second =  array('seq' => $meanData2[0], 'spr' => $meanData2[1], 'grd' => $meanData2[2]);

			$means[0] = $meansClass->createMeans($v_first, $v_second);

			// Nastavimo variable (potrebno za grafe
			$meansClass->variabla1[0] = $v_second;
			$meansClass->variabla2[0] = $v_first;
					
			// Naslov
			if($element['spr1'] == '' || $element['spr2'] == ''){		
				$title = $lang['srv_select_spr'];			
			}		
			else{				
				$label2 = strip_tags($meansClass->getSpremenljivkaTitle($means[0]['v1']));
				$label1 = strip_tags($meansClass->getSpremenljivkaTitle($means[0]['v2']));
				
				$title = $label1 . ' / ' . $label2;
			}
				
			$subtitle = ' ('.$lang['srv_means']. ($element['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';
		}
		
		// ttest
		elseif($element['type'] == '7'){
			
			// ustvarimo ttest objekt
			$ttestClass = new SurveyTTest($this->ank_id);
			
			// Naslov
			if($element['spr1'] == '' || $element['spr2'] == ''){		
				$title = $lang['srv_select_spr'];			
			}		
			else{				
				$label2 = strip_tags($this->getTTestLabel($element['spr2'], $ttestClass));
				$label1 = strip_tags($this->getTTestLabel($element['spr1'], $ttestClass));
				
				$title = $label1 . ' / ' . $label2;
			}
				
			$subtitle = ' ('.$lang['srv_ttest']. ($element['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';
		}
		
		// break
		elseif($element['type'] == '9'){
			
			// ustvarimo ttest objekt
			$breakClass = new SurveyBreak($this->ank_id);

			$breakData1 = explode("-", $element['spr1']);		
			$breakData2 = explode("-", $element['spr2']);
			
			
			$label1 = '';
			$variables = $breakClass->getVariableList(2);			
			foreach ($variables as $variable) {

				if($breakData1[0] == $variable['sequence']){
					$label1 = ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' )) . $variable['variableNaslov'];
					$label1 = (strlen($label1) > 60) ? substr($label1, 0, 57).'...' : $label1;
					
					break;
				}
			}

			$label2 = '';
			$variables = $this->getBreakDependentVariableList($breakClass);
			foreach ($variables as $variable) {
			
				if($breakData2[0] == $variable['sequence']){
					//$label2 = $variable['variableNaslov'];
					$label2 = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					break;
				}
			}
			
			$title = $label1 . ' / ' . $label2;
			$subtitle = '('.$lang['srv_break']. ($element['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';
		}
		
		
		$this->rtf->new_line(3);
		
		$this->rtf->set_font("Arial Black", 9);

		$this->rtf->add_text($this->encodeText($title), 'center');
		$this->rtf->set_font("Arial", 9);
		$this->rtf->add_text($this->encodeText($subtitle), 'center');
		$this->rtf->new_line();
	}
	
	function getTTestLabel($spr, $ttestClass){

		$data = explode("-", $spr);
	
		$spid = $data[1];
		$seq = $data[0];
		$grid = $data[2];
	
		$spr_data = $ttestClass->_HEADERS[$spid];
		if ($grid == 'undefined') {

			# imamp lahko več variabel
			foreach ($spr_data['grids'] as $gkey => $grid ) {
					
				foreach ($grid['variables'] as $vkey => $variable) {
					$sequence = $variable['sequence'];
					if ($sequence == $seq) {
						$sprLabel = '('.$variable['variable'].') '. $variable['naslov'];
					}
				}
			}
		} else {
			# imamo subgrid
			$sprLabel = '('.$spr_data['grids'][$grid]['variable'].') '. $spr_data['grids'][$grid]['naslov'];
		}
		
		return $sprLabel;
	}
	
	/** funkcija vrne seznam primern variabel za break
	 *
	 */
	function getBreakDependentVariableList($breakClass) {

		$variablesList = array();
		
		# zloopamo skozi header in dodamo variable (potrebujemo posamezne sekvence)
		foreach ($breakClass->_HEADERS AS $skey => $spremenljivka) {
		
			$tip = $spremenljivka['tip'];
			
			$_dropdown_condition = (is_numeric($tip) && $tip != 5 && $tip != 8 && $tip != 9) ? true : false;	
			if ($_dropdown_condition) {	
			
				$cnt_all = (int)$spremenljivka['cnt_all'];
				if ( $cnt_all == '1' || in_array($tip, array(1,2,3,4,7,17,18,21,22)) || ($tip == 6 && $spremenljivka['enota'] == 2) ) {
					
					# pri tipu radio ali select dodamo tisto variablo ki ni polje "drugo"
					if ($tip == 1 || $tip == 3 ) {
						if (count($spremenljivka['grids']) == 1 ) {
							# če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
							$grid = $spremenljivka['grids'][0];
							if (count ($grid['variables']) > 0) {
								foreach ($grid['variables'] AS $vid => $variable ){
									if ($variable['other'] != 1) {
										# imampo samo eno sekvenco grids[0]variables[0]
										$variablesList[] = array(
											'tip'=>$tip,
											'spr_id'=>$skey,
											'sequence'=>$spremenljivka['grids'][0]['variables'][$vid]['sequence'],
											'variableNaslov'=>'('.$spremenljivka['variable'].')&nbsp;'.strip_tags($spremenljivka['naslov']),
											'canChoose'=>true,
											'sub'=>0);
											
									}
								}
							}
						}
					} 
					
					else {
						# imampo samo eno sekvenco grids[0]variables[0]
						$variablesList[] = array(
							'tip'=>$tip,
							'spr_id'=>$skey,
							'sequence'=>$spremenljivka['grids'][0]['variables'][0]['sequence'],
							'variableNaslov'=>'('.$spremenljivka['variable'].')&nbsp;'.strip_tags($spremenljivka['naslov']),
							'canChoose'=>true,
							'sub'=>0);
					}
				} 
				else if ($cnt_all > 1){
					# imamo več skupin ali podskupin, zato zlopamo skozi gride in variable
					if (count($spremenljivka['grids']) > 0 ) {
						$variablesList[] = array(
							'tip'=>$tip,
							
							'variableNaslov'=>'('.$spremenljivka['variable'].')&nbsp;'.strip_tags($spremenljivka['naslov']),
							'canChoose'=>false,
							'sub'=>0);
						# ali imamo en grid, ali več (tabele
						if (count($spremenljivka['grids']) == 1 ) {
							# če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
							$grid = $spremenljivka['grids'][0];
							if (count ($grid['variables']) > 0) {
								foreach ($grid['variables'] AS $vid => $variable ){
									if ($variable['other'] != 1) {
										$variablesList[] = array(
											'tip'=>$tip,
											'spr_id'=>$skey,
											'sequence'=>$variable['sequence'],
											'variableNaslov'=>'('.$variable['variable'].')&nbsp;'.strip_tags($variable['naslov']),
											'canChoose'=>true,
											'sub'=>1);
									}
								}
							}

						} elseif($tip == 6) {
							# imamo več gridov - tabele
							foreach($spremenljivka['grids'] AS $gid => $grid) {
								$sub = 0;
								if ($grid['variable'] != '') {
									$sub++;
									$variablesList[] = array(
										'tip'=>$tip,
										'variableNaslov'=>'('.$grid['variable'].')&nbsp;'.strip_tags($grid['naslov']),
										'canChoose'=>false,
										'sub'=>$sub);
								}
								if (count ($grid['variables']) > 0) {
									$sub++;
									foreach ($grid['variables'] AS $vid => $variable ){
										if ($variable['other'] != 1) {
											$variablesList[] = array(
												'tip'=>$tip,
												'spr_id'=>$skey,
												'sequence'=>$variable['sequence'],
												'variableNaslov'=>'('.$variable['variable'].')&nbsp;'.strip_tags($variable['naslov']),
												'canChoose'=>true,
												'sub'=>$sub);
										}
									}
								}
							}
						} else {
							foreach($spremenljivka['grids'] AS $gid => $grid) {
								$sub = 0;
								if ($grid['variable'] != '') {
									$sub++;
									$variablesList[] = array(
										'tip'=>$tip,
										'spr_id'=>$skey,
										'grd_id'=>$gid,
										'sequence'=>$grid['variables'][0]['sequence'],
										'variableNaslov'=>'('.$grid['variable'].')&nbsp;'.strip_tags($grid['naslov']),
										'canChoose'=>true,
										'sub'=>1);
								}
							}
						}
					}
				}
			}
		}

		return $variablesList;
	}
	
	
	// Izpisemo komentar elementa
	function displayComment($text){
		global $lang;
		
		if($text != ''){

			// pretvorimo html text v rtf
			$text = $this->rtf->HTMLtoRTF($text);
		
			$this->rtf->TextCell($this->enkaEncode($text), array('width' => 10000, 'height' => 1,
				'align' => 'left', 'valign' => 'bottom' , 'border' => '','colorF' => "0" ) );
			
			$this->rtf->new_line(1);
		}
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
		
		// preverimo text za img tage in jih zamenjamo z ustrezno sliko
		$pattern = '/<img[^>]+src[\\s=\'"]';
		$pattern .= '+([^"\'>\\s]+)/is';
		if(preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)){
			
			// relativna pot
			if(substr($match[1][0], 0, 1) == '/')
				$image = $site_url.$match[1][0];
			else
				$image = $match[1][0];
				
			$file = @file_get_contents($image);
			
				
			// Resizamo sliko na pravo velikost
			$imgSize = getimagesize($image);
			$origHeight = $imgSize[1];
			$origWidth = $imgSize[0];
			
			// Dobimo nastavljeno visino slike
			$pattern = '/<img[^>]+height[\\s=\'"]';
			$pattern .= '+([^"\'>\\s]+)/is';
			preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE);
			$height = round($match[1][0] / $origHeight) * 100;
			
			// Dobimo nastavljeno sirino slike
			$pattern = '/<img[^>]+width[\\s=\'"]';
			$pattern .= '+([^"\'>\\s]+)/is';
			preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE);
			$width = round($match[1][0] / $origWidth) * 100;
			
			
			$result .= "{";
			$result .= "\\pict\\jpegblip\\picscalex".$width."\\picscaley".$height."\\bliptag132000428 ";
			$result .= trim(bin2hex($file));
			$result .= "\n}\n";
	
			$text = preg_replace("/<img[^>]+\>/i", $result, $text); 
		}
		
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return strip_tags($text);
	}	
	
	function encodeText($text){ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		return strip_tags($text);
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

	function setDisplayFrontPage($display) {$this->pi['displayFrontPage'] = $display;}
	function getDisplayFrontPage() {return ($this->pi['displayFrontPage'] == true || $this->pi['displayFrontPage'] == 1);}

	// Ce imamo v porocilu tabelo crosstab ali ttest ali means imamo landscape orientacijo
	function landscapeTest(){
		global $global_user_id;

		$sql = sisplet_query("SELECT * FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id' AND profile='$this->creportProfile' AND sub_type='0' AND (type='5' OR type='6' OR type='7' OR type='9')");		
		
		if(mysqli_num_rows($sql) > 0)
			return true;
		else
			return false;
	}
	
	
}


?>
