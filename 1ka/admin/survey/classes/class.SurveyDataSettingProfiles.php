<?php
/**
 * Created on 17.02.2011
 *
 * @author: Gorazd Veselič
 */


session_start();

class SurveyDataSettingProfiles {

	static private $surveyId = null;
	static private $uId = null;

	static private $currentProfileId = 0;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika

	static private $analysisGoToArray = array(	'0'=>M_ANALYSIS_CHARTS, '1'=>M_ANALYSIS_SUMMARY);			# Kam gre analiza
	
	static private $seperators = array(	0=>array('decimal_point'=>'.', 'thousands'=>','),
										1=>array('decimal_point'=>',', 'thousands'=>'.'));			# Kako izpisujemo decimalke in tisočice
	static private $defaultSeperator = 0;															# privzete nastavitve
	
	static public $spr_type = array('showCategories'=>Array(1,2,3,6,16,17), 'showNumbers'=>array(7,18,20,22,25), 'showText'=>array(4,5,8,19,21,26,27));
	
	static public $textAnswersMore = array('0'=>'10','10'=>'30','30'=>'100','100'=>'300','300'=>'600','600'=>'900','900'=>'9999999');
	
	static function getSurveyId()				{ return self::$surveyId; }
	static function getGlobalUserId()			{ return self::$uId; }
	static function getCurentProfileId()		{ return self::$currentProfileId; }
	
	
	/** Inizializacija, poišče id privzetega profila in prebere vse profiel ki jih ima uporabnik na voljo
	 * 
	 * @param $_surveyId
	 */
	static function Init($_surveyId)
	{

		global $global_user_id, $lang;
		 
		if ($_surveyId && $global_user_id)
		{
			self::$surveyId = $_surveyId;
			self::$uId = $global_user_id;
		
			# inicializiramo datoteko z nastavitvami
			SurveyUserSetting :: getInstance()->Init(self::$surveyId, self::$uId);			

			# preverimo ali ima uporabnik nastavljen privzet profil
			$dsp = SurveyUserSetting :: getInstance()->getSettings('default_dataSettingProfile_profile');
			if ($dsp == -1 || $dsp > 0 ) {
				self::$currentProfileId = $dsp;
			} else {
				self::$currentProfileId = 0;
				self::SetDefaultProfile(0);
			}
			
			#dodamo profil iz seje
			if ( isset($_SESSION['dataSetting_profile'][self::$surveyId])) {
				#dodamo profil iz seje
				self::$profiles['-1'] = $_SESSION['dataSetting_profile'][self::$surveyId];
				# ime damo iz lang fajla
				self::$profiles['-1']['id'] = '-1';
					self::$profiles['-1']['name'] =$lang['srv_temp_profile'];
			} else if($dsp == -1 ) {
				// #seja ne obstaja zato privzet profil popravimo na 0
				$dsp = 0;
				self::$currentProfileId = 0;
				self::SetDefaultProfile(0);
			}
			#dodamo privzet sistemski profil
			self::$profiles['0'] = array('id'=>0,
										 'name'=>$lang['srv_default_profile1'],
										 'dsp_ndp' => NUM_DIGIT_PERCENT,	# stevilo digitalnih mest za odstotek
										 'dsp_nda' => NUM_DIGIT_AVERAGE,	# stevilo digitalnih mest za povprecje
										 'dsp_ndd' => NUM_DIGIT_DEVIATION,	# stevilo digitalnih mest za odklon
										 'dsp_res' => NUM_DIGIT_RESIDUAL,	# stevilo digitalnih mest za residual
										 'dsp_sep' => self::$defaultSeperator,	# privzet seperator
										 'crossChk0' => '1',	# izpis frekvenc
										 'crossChk1' => '0',	# izpis procentov po vrsticah 
										 'crossChk2' => '0',	# izpis procentov po stolpcih
										 'crossChk3' => '0',	# izpis skupnih procentov
										 'crossChkEC' => '0',	# izpis skupnih procentov
										 'crossChkRE' => '0',	# izpis skupnih procentov
										 'crossChkSR' => '0',	# izpis skupnih procentov
										 'crossChkAR' => '0',	# izpis skupnih procentov
										 'doColor' => '1',		# barvanje celic
										 'doValues' => '1',		# prikaz vrednosti
										 'showCategories' => '1',	# prikaz kategorij
										 'showOther' => '1',		# prikaz polj drugo
										 'showNumbers' => '1',		# prikaz števil
										 'showText' => '1',	# prikaz tekstovnih odgovorov
										 'chartNumbering' => '0',		# ostevilcevanje grafov
										 'chartFontSize' => '8',		# velikost fonta grafov
										 'chartFP' => '0',	# izpis prve strani pri izvozu grafov (PDF/RTF)
										 'chartTableAlign' => '0',	# default poravnava tabel pri grafih (0->sredinska, 1->leva)
										 'chartTableMore' => '0',	# prikaz vseh textovnih odg. v tabelah (0->ne prikazi vseh, 1->vsi)
										 'chartNumerusText' => '0',	# pozicija numerusa
										 'chartAvgText' => '1',		# prikaz povprecja
										 'chartPieZeros' => '0',	# prikaz nicelnih vrednosti v kroznih grafih
										 'hideEmpty' => '1',	# skrivanje spremenljivke, ki nima veljavnih vnosov
										 'hideAllSystem' => '0',	# skrivanje vseh sistemskih spremenljivk
										 'numOpenAnswers' => self::$textAnswersMore['10'],	# koliko odprtih odgovorov prikažemo privzeto
#										 'enableInspect' => '0',	# ali je Inspect vklopljen
										 'dataPdfType' => '0',				# način izpisa pdf - navaden, dolg, kratek pri izviozu podatkov v pdf/rtf
										 'exportDataNumbering' => '1',		# ostevilcevanje vprasanj pri izvozu ankete s podatki v pdf/rtf
										 'exportDataShowIf' => '1',			# prikaz if stavkov pri izvozu ankete s podatki v pdf/rtf
										 'exportDataFontSize' => '10',		# velikost fonta pri izvozu ankete spodatki v pdf/rtf
										 'exportDataShowRecnum' => '1',		# prikaz recnuma respondenta pri izvozu ankete s podatki v pdf/rtf
										 'exportDataPB' => '0',				# vsak respondent na svoji strani pri izvozu ankete s podatki v pdf/rtf
										 'exportDataSkipEmpty' => '0',		# izpusti vprasanja brez odgovora pri izvozu ankete s podatki v pdf/rtf
										 'exportDataSkipEmptySub' => '0',	# izpusti podvprasanja (multigridi) brez odgovora pri izvozu ankete s podatki v pdf/rtf
										 'exportDataLandscape' => '0',		# landscape postavitev pri izvozu ankete s podatki v pdf/rtf										 
										 'exportNumbering' => '1',			# ostevilcevanje vprasanj pri izvozu ankete v pdf/rtf
										 'exportShowIf' => '1',				# prikaz if stavkov pri izvozu ankete v pdf/rtf
										 'exportFontSize' => '10',			# velikost fonta pri izvozu ankete v pdf/rtf
										 'exportShowIntro' => '0',			# prikaz uvoda pri izvozu ankete v pdf/rtf
										 'dataShowIcons' => '1',	# ali prikazujemo ikone za urejanje pri podatkih
										 'analysisGoTo' => '1',	# Privzeto gremo na grafe
										 'analiza_legenda' => '0',	# Privzeto ne prikazujemo legende
										);

			# poiščemo še seznam vseh ostalih profilov uporabnika
			 
			$stringSelect = "SELECT * FROM srv_datasetting_profile WHERE uid = '".self::getGlobalUserId()."' || uid = '0' ORDER BY id";
			$querySelect = sisplet_query($stringSelect);
			if(mysqli_num_rows($querySelect) > 0){
				while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
					self::$profiles[$rowSelect['id']] = array(	'id'=>$rowSelect['id'],
																'name'=>$rowSelect['name'],
																'dsp_ndp' => $rowSelect['dsp_ndp'],
																'dsp_nda' => $rowSelect['dsp_nda'],
																'dsp_ndd' => $rowSelect['dsp_ndd'],
																'dsp_res' => $rowSelect['dsp_res'],
																'dsp_sep' => $rowSelect['dsp_sep'],
																'crossChk0' => 1, // $rowSelect['crossChk0'],
																'crossChk1' => $rowSelect['crossChk1'],
																'crossChk2' => $rowSelect['crossChk2'],
																'crossChk3' => $rowSelect['crossChk3'],
																'crossChkEC' => $rowSelect['crossChkEC'],
																'crossChkRE' => $rowSelect['crossChkRE'],
																'crossChkSR' => $rowSelect['crossChkSR'],
																'crossChkAR' => $rowSelect['crossChkAR'],
																'doColor' => $rowSelect['doColor'],
																'doValues' => $rowSelect['doValues'],
																'showCategories' => $rowSelect['showCategories'],
																'showOther' => $rowSelect['showOther'],
																'showNumbers' => $rowSelect['showNumbers'],
																'showText' => $rowSelect['showText'],
																'chartNumbering' => $rowSelect['chartNumbering'],
																'chartFontSize' => $rowSelect['chartFontSize'],
																'chartFP' => $rowSelect['chartFP'],
																'chartTableAlign' => $rowSelect['chartTableAlign'],
																'chartTableMore' => $rowSelect['chartTableMore'],
																'chartNumerusText' => $rowSelect['chartNumerusText'],
																'chartAvgText' => $rowSelect['chartAvgText'],
																'chartPieZeros' => $rowSelect['chartPieZeros'],
																'hideEmpty' => $rowSelect['hideEmpty'],
																'hideAllSystem' => $rowSelect['hideAllSystem'],
																'numOpenAnswers' => $rowSelect['numOpenAnswers'],
	#															'enableInspect' => $rowSelect['enableInspect'],
																'dataPdfType' => $rowSelect['dataPdfType'],
																'exportDataNumbering' => $rowSelect['exportDataNumbering'],
																'exportDataShowIf' => $rowSelect['exportDataShowIf'],
																'exportDataFontSize' => $rowSelect['exportDataFontSize'],
																'exportDataShowRecnum' => $rowSelect['exportDataShowRecnum'],
																'exportDataPB' => $rowSelect['exportDataPB'],
																'exportDataSkipEmpty' => $rowSelect['exportDataSkipEmpty'],
																'exportDataSkipEmptySub' => $rowSelect['exportDataSkipEmptySub'],
																'exportDataLandscape' => $rowSelect['exportDataLandscape'],
																'exportNumbering' => $rowSelect['exportNumbering'],
																'exportShowIf' => $rowSelect['exportShowIf'],
																'exportFontSize' => $rowSelect['exportFontSize'],
																'exportShowIntro' => $rowSelect['exportShowIntro'],
																'dataShowIcons' => $rowSelect['dataShowIcons'],
																'analysisGoTo' => $rowSelect['analysisGoTo'],
																'analiza_legenda' => $rowSelect['analiza_legenda'],
															);
				}
			}
			
			# preverimo ali profil obstaja
			if (!isset(self::$profiles[$dsp])) {
				# če profil ne obstaja ga nastavimo na 0
				$dsp = 0;
				self::SetDefaultProfile($dsp);
			}
			
			return true;
		} else {
			return false;
		}
		
	}
	
	/** Vrne podatke trenutno izbranega profofila
	 * 
	 */
	static function GetCurentProfileData() {
		return	self::$profiles[self::$currentProfileId]; 
	}

	/** Vrne podatke podanega profofila
	 * 
	 */
	static function GetProfileData($pid) {
		return	self::$profiles[$pid]; 
	}
	
	/** Pridobimo seznam vseh list uporabnika
	 *  v obliki arraya
	 */
	static function getProfiles() {
		return self::$profiles;
	}

	/** Funkcija vrne posamezno nastavitev 
	 * 
	 */
	static function getSetting($what = null) {

		switch ($what){
			case 'name' :
				return self :: $profiles[self::$currentProfileId]['name'];
			break;
			case 'NUM_DIGIT_PERCENT' :
				return self :: $profiles[self::$currentProfileId]['dsp_ndp'];
			break;
			case 'NUM_DIGIT_AVERAGE' :
				return self :: $profiles[self::$currentProfileId]['dsp_nda'];
			break;
			case 'NUM_DIGIT_DEVIATION' :
				return self :: $profiles[self::$currentProfileId]['dsp_ndd'];
			break;
			case 'NUM_DIGIT_RESIDUAL' :
				return self :: $profiles[self::$currentProfileId]['dsp_res'];
			break;
			case 'decimal_point' :
				return self::$seperators[self :: $profiles[self::$currentProfileId]['dsp_sep']]['decimal_point'];
			break;
			case 'thousands' :
				return self::$seperators[self :: $profiles[self::$currentProfileId]['dsp_sep']]['thousands'];
			break;
			case 'showOther' :
				return self::$profiles[self::$currentProfileId]['showOther'];
			break;
			case 'spr_types' :
				$result = array();
				#kategorije
				if (self::$profiles[self::$currentProfileId]['showCategories']) {
					$result = array_merge ($result,  self::$spr_type['showCategories']);	
				}
				#numbers
				if (self::$profiles[self::$currentProfileId]['showNumbers']) {
					$result = array_merge ($result,  self::$spr_type['showNumbers']);	
				}
				#text
				if (self::$profiles[self::$currentProfileId]['showText']) {
					$result = array_merge ($result,  self::$spr_type['showText']);	
				}
				return $result;
			break;
			case 'chartNumbering' :
				return self::$profiles[self::$currentProfileId]['chartNumbering'];
			break;
			case 'chartFontSize' :
				return self::$profiles[self::$currentProfileId]['chartFontSize'];
			break;
			case 'chartFP' :
				return self::$profiles[self::$currentProfileId]['chartFP'];
			break;
			case 'chartTableAlign' :
				return self::$profiles[self::$currentProfileId]['chartTableAlign'];
			break;
			case 'chartTableMore' :
				return self::$profiles[self::$currentProfileId]['chartTableMore'];
			break;
			case 'chartNumerusText' :
				return self::$profiles[self::$currentProfileId]['chartNumerusText'];
			break;
			case 'chartAvgText' :
				return self::$profiles[self::$currentProfileId]['chartAvgText'];
			break;
			case 'chartPieZeros' :
				return self::$profiles[self::$currentProfileId]['chartPieZeros'];
			break;
			case 'hideEmpty' :
				return self::$profiles[self::$currentProfileId]['hideEmpty'];
			break;
			case 'hideAllSystem' :
				return self::$profiles[self::$currentProfileId]['hideAllSystem'];
			break;		
			case 'numOpenAnswers' :
				return self::$profiles[self::$currentProfileId]['numOpenAnswers'];
			break;
#			case 'enableInspect' :
#				return self::$profiles[self::$currentProfileId]['enableInspect'];
#			break;
			case 'dataPdfType' :
				return self::$profiles[self::$currentProfileId]['dataPdfType'];
			break;
			case 'exportDataNumbering' :
				return self::$profiles[self::$currentProfileId]['exportDataNumbering'];
			break;
			case 'exportDataShowIf' :
				return self::$profiles[self::$currentProfileId]['exportDataShowIf'];
			break;
			case 'exportDataFontSize' :
				return self::$profiles[self::$currentProfileId]['exportDataFontSize'];
			break;
			case 'exportDataShowRecnum' :
				return self::$profiles[self::$currentProfileId]['exportDataShowRecnum'];
			break;
			case 'exportDataPB' :
				return self::$profiles[self::$currentProfileId]['exportDataPB'];
			break;
			case 'exportDataSkipEmpty' :
				return self::$profiles[self::$currentProfileId]['exportDataSkipEmpty'];
			break;
			case 'exportDataSkipEmptySub' :
				return self::$profiles[self::$currentProfileId]['exportDataSkipEmptySub'];
			break;
			case 'exportDataLandscape' :
				return self::$profiles[self::$currentProfileId]['exportDataLandscape'];
			break;
			case 'exportNumbering' :
				return self::$profiles[self::$currentProfileId]['exportNumbering'];
			break;
			case 'exportShowIf' :
				return self::$profiles[self::$currentProfileId]['exportShowIf'];
			break;
			case 'exportFontSize' :
				return self::$profiles[self::$currentProfileId]['exportFontSize'];
			break;
			case 'exportShowIntro' :
				return self::$profiles[self::$currentProfileId]['exportShowIntro'];
			break;
			case 'dataShowIcons' :
  				session_start();
    			$dataIcons_quick_view = (isset($_SESSION['sid_'.self::$surveyId]['dataIcons_quick_view']) && $_SESSION['sid_'.self::$surveyId]['dataIcons_quick_view'] == false) ? false : true;
    			$dataIcons_write = (isset($_SESSION['sid_'.self::$surveyId]['dataIcons_write']) && $_SESSION['sid_'.self::$surveyId]['dataIcons_write'] == true) ? true : false;
    			$dataIcons_edit = (isset($_SESSION['sid_'.self::$surveyId]['dataIcons_edit']) && $_SESSION['sid_'.self::$surveyId]['dataIcons_edit'] == true) ? true: false;
    			$dataIcons_labels = (isset($_SESSION['sid_'.self::$surveyId]['dataIcons_labels']) && $_SESSION['sid_'.self::$surveyId]['dataIcons_labels'] == true) ? true: false;
    			$dataIcons_multiple = (isset($_SESSION['sid_'.self::$surveyId]['dataIcons_multiple']) && $_SESSION['sid_'.self::$surveyId]['dataIcons_multiple'] == true) ? true: false;
    			return array('dataIcons_edit' => $dataIcons_edit, 'dataIcons_write'=>$dataIcons_write, 'dataIcons_quick_view'=>$dataIcons_quick_view, 'dataIcons_labels'=>$dataIcons_labels, 'dataIcons_multiple'=>$dataIcons_multiple);
				#return self::$profiles[self::$currentProfileId]['dataShowIcons'];
			break;
			case 'showCategories' :
				return self::$profiles[self::$currentProfileId]['showCategories'];
			break;
			case 'analysisGoTo' :
				return self::$analysisGoToArray[self::$profiles[self::$currentProfileId]['analysisGoTo']];
			break;
			case 'analiza_legenda' :
				return (int)self::$profiles[self::$currentProfileId]['analiza_legenda'] == 1;
			break;
			case 'showNumbers' :
				return self::$profiles[self::$currentProfileId]['showNumbers'];
			break;
			case 'showText' :
				return self::$profiles[self::$currentProfileId]['showText'];
			break;
			
			default:
				return self :: $profiles[self::$currentProfileId];
			break;
		}
	}
	
	/** Ponastavi id privzetega profila
	 * 
	 */
	static function SetDefaultProfile($pid) {
		self::$currentProfileId = $pid;
		$saved = SurveyUserSetting :: getInstance()->saveSettings('default_dataSettingProfile_profile',$pid);
	}

	static function SaveProfile($pid = 0) {	
		global $lang;
		
		if ($pid==0) 
			$pid = -1;
			
		# omejimo podatke
		$dsp_ndp = (isset($_POST['dsp_ndp']) && (int)$_POST['dsp_ndp'] >= 0) ? $_POST['dsp_ndp'] : NUM_DIGIT_PERCENT;
		$dsp_nda = (isset($_POST['dsp_nda']) && (int)$_POST['dsp_ndp'] >= 0) ? $_POST['dsp_nda'] : NUM_DIGIT_AVERAGE;
		$dsp_ndd = (isset($_POST['dsp_ndd']) && (int)$_POST['dsp_ndp'] >= 0)? $_POST['dsp_ndd'] : NUM_DIGIT_DEVIATION;
		$dsp_res = (isset($_POST['dsp_res']) && (int)$_POST['dsp_res'] >= 0)? $_POST['dsp_res'] : NUM_DIGIT_RESIDUAL;

		$dsp_sep = isset($_POST['dsp_sep']) ? $_POST['dsp_sep'] : self::$defaultSeperator;

		$crossChk0  = isset($_POST['crossChk0']) && $_POST['crossChk0']   == '1' ? '1' : '1'; // je vedno 1 
		$crossChk1  = isset($_POST['crossChk1']) && $_POST['crossChk1']   == '1' ? '1' : '0';

		# odstranimo sejo za procent po odstotkih če je nastavljena
		if (isset($_SESSION['crossChk1'])) {
			unset($_SESSION['crossChk1']);
		}
		
		$crossChk2  = isset($_POST['crossChk2']) && $_POST['crossChk2']   == '1' ? '1' : '0'; 
		$crossChk3  = isset($_POST['crossChk3']) && $_POST['crossChk3']   == '1' ? '1' : '0'; 
		$crossChkEC = isset($_POST['crossChkEC']) && $_POST['crossChkEC'] == '1' ? '1' : '0'; 
		$crossChkRE = isset($_POST['crossChkRE']) && $_POST['crossChkRE'] == '1' ? '1' : '0'; 
		$crossChkSR = isset($_POST['crossChkSR']) && $_POST['crossChkSR'] == '1' ? '1' : '0'; 
		$crossChkAR = isset($_POST['crossChkAR']) && $_POST['crossChkAR'] == '1' ? '1' : '0'; 
		$doColor    = isset($_POST['doColor']) && $_POST['doColor'] 	  == '1' ? '1' : '0'; 
		$doValues   	= isset($_POST['doValues']) && $_POST['doValues']	== '1' ? '1' : '0'; 
		$showCategories	= isset($_POST['showCategories']) && $_POST['showCategories']	== '1' ? '1' : '0'; 
		$showOther	    = isset($_POST['showOther']) && $_POST['showOther'] 	  		== '1' ? '1' : '0'; 
		$showNumbers    = isset($_POST['showNumbers']) && $_POST['showNumbers'] 	  	== '1' ? '1' : '0'; 
		$showText   	= isset($_POST['showText']) && $_POST['showText'] 	  			== '1' ? '1' : '0'; 
		$chartNumbering = isset($_POST['chartNumbering']) && $_POST['chartNumbering']	== '1' ? '1' : '0'; 
		$chartFontSize = isset($_POST['chartFontSize']) && (int)$_POST['chartFontSize'] > 0 ? (int)$_POST['chartFontSize'] : '0';
		$chartFP   		= isset($_POST['chartFP']) && $_POST['chartFP'] 	  	== '1' ? '1' : '0';
		$chartTableAlign = isset($_POST['chartTableAlign']) && $_POST['chartTableAlign'] 	  	== '1' ? '1' : '0';
		$chartTableMore 		= isset($_POST['chartTableMore']) && $_POST['chartTableMore'] 	  	== '1' ? '1' : '0';
		$chartNumerusText		= isset($_POST['chartNumerusText']) && (int)$_POST['chartNumerusText'] > 0 ? (int)$_POST['chartNumerusText'] : '0';
		$chartAvgText			= isset($_POST['chartAvgText']) && (int)$_POST['chartAvgText'] > 0 ? (int)$_POST['chartAvgText'] : '0';
		$chartPieZeros 			= isset($_POST['chartPieZeros']) && $_POST['chartPieZeros'] 	  	== '1' ? '1' : '0';
		$hideEmpty   			= isset($_POST['hideEmpty']) && $_POST['hideEmpty'] 	== '1' ? '1' : '0';
		$hideAllSystem   			= isset($_POST['hideAllSystem']) && $_POST['hideAllSystem'] 	== '1' ? '1' : '0';		
		$numOpenAnswers   		= isset($_POST['numOpenAnswers'])  && (int)$_POST['numOpenAnswers'] > 0 ? (int)$_POST['numOpenAnswers'] : self::$textAnswersMore['10'];
#		$enableInspect  		= isset($_POST['enableInspect']) && $_POST['enableInspect'] 	== '1' ? '1' : '0'; 
		$dataPdfType   			= isset($_POST['dataPdfType']) && (int)$_POST['dataPdfType'] > 0 ? (int)$_POST['dataPdfType'] : '0'; 
		$exportDataNumbering   	= isset($_POST['exportDataNumbering']) && $_POST['exportDataNumbering'] 	  	== '1' ? '1' : '0';
		$exportDataShowIf   	= isset($_POST['exportDataShowIf']) && $_POST['exportDataShowIf'] 	  	== '1' ? '1' : '0';
		$exportDataFontSize   	= isset($_POST['exportDataFontSize']) && (int)$_POST['exportDataFontSize'] > 0 ? (int)$_POST['exportDataFontSize'] : '0'; 
		$exportDataShowRecnum  	= isset($_POST['exportDataShowRecnum']) && $_POST['exportDataShowRecnum'] == '1' ? '1' : '0';
		$exportDataPB   		= isset($_POST['exportDataPB']) && $_POST['exportDataPB'] 	  	== '1' ? '1' : '0';
		$exportDataSkipEmpty  	= isset($_POST['exportDataSkipEmpty']) && $_POST['exportDataSkipEmpty'] 	  	== '1' ? '1' : '0';
		$exportDataSkipEmptySub	= isset($_POST['exportDataSkipEmptySub']) && $_POST['exportDataSkipEmptySub'] 	  	== '1' ? '1' : '0';
		$exportDataLandscape 	= isset($_POST['exportDataLandscape']) && $_POST['exportDataLandscape'] 	  	== '1' ? '1' : '0';
		$exportNumbering   		= isset($_POST['exportNumbering']) && $_POST['exportNumbering'] 	  	== '1' ? '1' : '0';
		$exportShowIf   		= isset($_POST['exportShowIf']) && $_POST['exportShowIf'] 	  	== '1' ? '1' : '0';
		$exportShowIntro   		= isset($_POST['exportShowIntro']) && $_POST['exportShowIntro'] 	  	== '1' ? '1' : '0';
		$exportFontSize   	= isset($_POST['exportFontSize']) && (int)$_POST['exportFontSize'] > 0 ? (int)$_POST['exportFontSize'] : '0'; 
		$dataShowIcons  = isset($_POST['dataShowIcons']) && $_POST['dataShowIcons'] == '1' ? '1' : '0'; 
		$analysisGoTo  = isset($_POST['analysisGoTo']) && (int)$_POST['analysisGoTo'] != 1 ? (int)$_POST['analysisGoTo'] : '1'; 
		$analiza_legenda  = isset($_POST['analiza_legenda']) && $_POST['analiza_legenda'] == '1' ? '1' : '0'; 
		
		$dsp_ndp = (int)$dsp_ndp > NUM_DIGIT_PERCENT_MAX ? NUM_DIGIT_PERCENT_MAX : $dsp_ndp;
		$dsp_nda = (int)$dsp_nda > NUM_DIGIT_AVERAGE_MAX ? NUM_DIGIT_AVERAGE_MAX : $dsp_nda;
		$dsp_ndd = (int)$dsp_ndd > NUM_DIGIT_DEVIATION_MAX ? NUM_DIGIT_DEVIATION_MAX : $dsp_ndd;
		$dsp_res = (int)$dsp_res > NUM_DIGIT_RESIDUAL_MAX ? NUM_DIGIT_RESIDUAL_MAX : $dsp_res;

		
		if ((int)$pid == 0 ) {
			# imamo privzet profil
			self :: SetDefaultProfile(0);
		} else if ((int)$pid > 0) {
			# shranimo v bazo
			
			$updateString = "UPDATE srv_datasetting_profile SET dsp_ndp = '".$dsp_ndp."', dsp_nda = '".$dsp_nda."', dsp_ndd = '".$dsp_ndd."',  dsp_res = '".$dsp_res."', dsp_sep = '".$dsp_sep.
			"', crossChk0 = '".$crossChk0."', crossChk1 = '".$crossChk1."', crossChk2 = '".$crossChk2."', crossChk3 = '".$crossChk3."', crossChkEC = '".$crossChkEC."', crossChkRE = '".$crossChkRE."', crossChkSR = '".$crossChkSR."', crossChkAR = '".$crossChkAR."',"
			." doColor = '".$doColor."', doValues = '".$doValues."', showCategories = '".$showCategories."', showOther = '".$showOther."', showNumbers = '".$showNumbers."', showText = '".$showText."',"
			." chartNumbering = '".$chartNumbering."', chartFontSize = '".$chartFontSize."', chartFP = '".$chartFP."', chartTableAlign = '".$chartTableAlign."', chartTableMore = '".$chartTableMore."', chartNumerusText = '".$chartNumerusText."', chartAvgText = '".$chartAvgText."', chartPieZeros = '".$chartPieZeros."', hideEmpty = '".$hideEmpty."', hideAllSystem = '".$hideAllSystem."', numOpenAnswers='".$numOpenAnswers
			#."', enableInspect='".$enableInspect
			."', dataPdfType='".$dataPdfType."', exportDataNumbering='".$exportDataNumbering."', exportDataShowIf='".$exportDataShowIf."', exportDataFontSize='".$exportDataFontSize."', exportDataShowRecnum='".$exportDataShowRecnum."', exportDataPB='".$exportDataPB."', exportDataSkipEmpty='".$exportDataSkipEmpty."', exportDataSkipEmptySub='".$exportDataSkipEmptySub."', exportDataLandscape='".$exportDataLandscape."'," 
			." exportNumbering='".$exportNumbering."', exportShowIf='".$exportShowIf."', exportFontSize='".$exportFontSize."', exportShowIntro='".$exportShowIntro."',"
			." dataShowIcons='".$dataShowIcons."', analysisGoTo='".$analysisGoTo."', analiza_legenda='".$analiza_legenda."' WHERE id = '".$pid."'";
			$updatequery = sisplet_query($updateString);
			if (!$updatequery) echo mysqli_error($GLOBALS['connect_db']);
        
			sisplet_query('COMMIT');
			# nastavimo privzet profil na trenutnega
			self :: SetDefaultProfile($pid);
			
		} else {

			# shranjujenmo v sejo
			#self::$profiles[$pid]['starts'] = $startDate;
			
#			#shranimo nastavljene variable
#			$InspectListVars = $_SESSION['dataSetting_profile'][self::$surveyId]['InspectListVars'];
			
			$_SESSION['dataSetting_profile'][self::$surveyId] = array('id'=>'-1',
				  	'name'=>$lang['srv_temp_profile'],
					'dsp_ndp' => $dsp_ndp,
					'dsp_nda' => $dsp_nda,
					'dsp_ndd' => $dsp_ndd,
					'dsp_res' => $dsp_res,
					'dsp_sep' => $dsp_sep,
					
					'crossChk0' => '1', // $crossChk0,
					'crossChk1' => $crossChk1,
					'crossChk2' => $crossChk2,
					'crossChk3' => $crossChk3,
					'crossChkEC' => $crossChkEC,
					'crossChkRE' => $crossChkRE,
					'crossChkSR' => $crossChkSR,
					'crossChkAR' => $crossChkAR,
					'doColor' => $doColor,
					'doValues' => $doValues,
					'showCategories' => $showCategories,
					'showOther' => $showOther,
					'showNumbers' => $showNumbers,
					'showText' => $showText,
					'chartNumbering' => $chartNumbering,
					'chartFontSize' => $chartFontSize,
					'chartFP' => $chartFP,
					'chartTableAlign' => $chartTableAlign,
					'chartTableMore' => $chartTableMore,
					'chartNumerusText' => $chartNumerusText,
					'chartAvgText' => $chartAvgText,
					'chartPieZeros' => $chartPieZeros,
					'hideEmpty' => $hideEmpty,
					'hideAllSystem' => $hideAllSystem,	
					'numOpenAnswers' => $numOpenAnswers,
#					'enableInspect' => $enableInspect,
#					'InspectListVars' => $InspectListVars,
					'dataPdfType' => $dataPdfType,
					'exportDataNumbering' => $exportDataNumbering,
					'exportDataShowIf' => $exportDataShowIf,
					'exportDataFontSize' => $exportDataFontSize,
					'exportDataShowRecnum' => $exportDataShowRecnum,
					'exportDataPB' => $exportDataPB,
					'exportDataSkipEmpty' => $exportDataSkipEmpty,
					'exportDataSkipEmptySub' => $exportDataSkipEmptySub,
					'exportDataLandscape' => $exportDataLandscape,
					'exportNumbering' => $exportNumbering,
					'exportShowIf' => $exportShowIf,
					'exportFontSize' => $exportFontSize,
					'exportShowIntro' => $exportShowIntro,
					'dataShowIcons' => $dataShowIcons,
					'analysisGoTo' => $analysisGoTo,
					'analiza_legenda' => $analiza_legenda,
					);
	
			self :: SetDefaultProfile(-1);
		}

		return $updatequery;
	}

	static function RenameProfile($pid = 0, $name = '') {

		if (isset($pid) && $pid > 0 && isset($name) && trim($name) != "") {
			// popravimo podatek za variables 
			$stringUpdate = "UPDATE srv_datasetting_profile SET name = '".$name."' WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
			sisplet_query('COMMIT');
			return $updated;
		} else {
			return -1;
		}
	}
	 	
	static function DeleteProfile($pid = 0) {
		self :: SetDefaultProfile('0');
		if (isset($pid) && $pid == -1) {
			unset($_SESSION['dataSetting_profile'][self::$surveyId] );
		} else  if (isset($pid) && $pid > 0) {
			// Izbrišemo profil in nastavimo privzetega 
			$stringUpdate = "DELETE FROM srv_datasetting_profile WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
			sisplet_query('COMMIT');
		}
		# nastavimo privzet profil
		self::SetDefaultProfile('0');
	}

	
	/** prikažemo dropdown z izbranim profilom in link do nastavitev profila
	 * 
	 * 
	 */
	static function DisplayLink($hideAdvanced = true) {
		global $lang;
		
		$css = (self::$currentProfileId == SDS_DEFAULT_PROFILE ? ' gray' : '');
		
		if ($hideAdvanced == false || self::$currentProfileId != SDS_DEFAULT_PROFILE) { 
			if ($_GET['a'] != 'data') {
				# v podatkih imamo nastavitve na prvem mestum zato ne rišemo spejsrja
				echo '<li class="space">&nbsp;</li>';
			}
			echo '<li>';
	        echo '<span class="as_link'.$css.'" id="dsp_link" title="' . $lang['srv_dsp_link_title'] . '">' . $lang['srv_dsp_link'] . '</span>'."\n";
	        echo '</li>';
	      
		}
	}
	
	/** prikažemo dropdown z izbranim profilom in link do nastavitev profila
	 * 
	 * 
	 */
	static function DisplayLinkDropdown() {
        $profiles = self :: getProfiles();
        $izbranProfil = self :: getCurentProfileId();
		echo '<select id="dsp_dropdown" name="dsp_dropdown" onchange="dataSettingProfileAction(\'change_profile\'); return false;" >'."\n";
		if (count($profiles) > 0){
			foreach ($profiles as $key => $value) {
				echo '<option' . ($izbranProfil == $value['id'] ? ' selected="selected"' : '') . ' value="' . $value['id'] . '">' . $value['name'] . '</option>'."\n";
			}
		}
		echo '</select>'."\n";
		
	}
	
	/** Funkcija prikaze izbor datuma
	 *  
	 */
	static function displayProfiles($current_pid = null) {
		global $lang;
        $_all_profiles = self::getProfiles();

        echo '<h2>'.$lang['srv_analiza_settings'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="dataSettingProfileAction(\'cancel\'); return false;">✕</a></div>';

        if ($current_pid == null) {
        	$current_pid = self::getCurentProfileId();
        }
        $currentFilterProfile = $_all_profiles[$current_pid];
        if ( self::$currentProfileId != SDS_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }
        
       	echo '<div id="dsp_profiles_left">';
       	echo '<span id="dsp_profiles_holder">';
		# zlistamo vse profile
       	echo '<span id="dsp_profiles" class="select">';
		if (count($_all_profiles)) {
			foreach ($_all_profiles as $id=>$profile) {
				
				echo '<div class="option' . ($current_pid == $id ? ' active' : '') . '" id="dataSetting_profile_' . $id . '" value="'.$id.'">';			
				
				echo $profile['name'];
				
				if($current_pid == $id){
					# sistemskega ne moremo izbrisati
					if ($current_pid != 0) {
						echo ' <a href="#" onclick="dataSettingProfileAction(\'show_delete\'); return false;" value="'.$lang['srv_delete_profile'].'"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'."\n";
					}
					# sistemskega in seje ne moremo preimenovati
					if ($current_pid > 0) {
						echo ' <a href="#" onclick="dataSettingProfileAction(\'show_rename\'); return false;" value="'.$lang['srv_rename_profile'].'"><span class="faicon edit floatRight spaceRight"></span></a>'."\n";
					}
				}	
				
				echo '</div>';	
			}
		}
		echo '</span>'; # dataSetting_profile
		echo '</span>'; # dsp_profiles_holder
				
		echo '</div>'; # dsp_profiles_left

		echo '<div id="dsp_profiles_right">'."\n";
		if ($current_pid == 0) {
			echo '<div id="dsp_note">';
			echo $lang['srv_change_default_profile'];
			echo '</div>'; // dataSetting_profile_note
			echo '<br class="clr" />'."\n";
		}	
		
		echo '<div id="dsp_content">';
		self::DisplayProfileData($current_pid);
		echo '</div>'; // dataSetting_profile_content
		
		echo '</div>'; // dataSetting_profile_right
		
		
		echo '<div id="dsp_button_holder">'."\n";
		if ((int)$current_pid <= 0 ) {
			if ((int)$current_pid == 0) {
				echo '<span class="floatRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="dataSettingProfileAction(\'run_profile\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
				echo '<span class="floatRight spaceRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			} else {
				echo '<span class="floatRight spaceRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="dataSettingProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			}
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'show_create\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
		} else  {
			echo '<span class="floatRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="dataSettingProfileAction(\'run_profile\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'show_create\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';		
		}
		echo '</div>'."\n"; // dsp_button_holder
		
		
		// cover Div
        echo '<div id="dsp_cover_div"></div>'."\n";
		
        // div za kreacijo novega
        echo '<div id="newProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="newProfileName" name="newProfileName" type="text" value="" size="45"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="dataSettingProfileAction(\'do_create\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'cancel_create\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
        
        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . $currentFilterProfile['name'] . '" size="45"  />'."\n";
        echo '<input id="renameProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="dataSettingProfileAction(\'do_rename\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'cancel_rename\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
                
        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . $currentFilterProfile['name'] . '</b>?'."\n";
        echo '<input id="deleteProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="dataSettingProfileAction(\'do_delete\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dataSettingProfileAction(\'cancel_delete\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";		
	}
	
	/** Funkcija prikaze osnovnih informacije profila
	 * 
	 */
	static function DisplayProfileData($current_pid=null) {
        global $lang;
        
		# podatki profila
		if ($current_pid == null) {
			$current_pid = self::$currentProfileId;
		}
		$cp = self::$profiles[$current_pid];

		echo '<fieldset>';
		echo '<legend>'.$lang['srv_results_filter_settings'].'</legend>';
		
		echo '<label><input id="showCategories" name="showCategories" type="checkbox" ' .
		 (($cp['showCategories']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_analiza_kategorialneSpremenljivke'];
        echo '</label>';
        
		echo '<label><input id="showOther" name="showOther" type="checkbox" ' .
			  (($cp['showOther']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
			  #(($cp['showCategories']) ? '' : ' disabled="disabled" ')
		echo  $lang['srv_analiza_ShowOthersText'] ;
        echo '</label>';
        
		echo '<label><input id="showNumbers" name="showNumbers" type="checkbox" ' .
		 (($cp['showNumbers']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_analiza_numericneSpremenljivke'];
        echo '</label>';
        
		echo '<label><input id="showText" name="showText" type="checkbox" ' .
		 (($cp['showText']) ? ' checked="checked" ' : ' ') . ' autocomplete="off"/>';
        echo '</label>';
        
        echo $lang['srv_analiza_textovneSpremenljivke'];
        
		echo '<br />'.$lang['srv_analiza_link'].': ';
		echo '<select id="analysisGoTo">';
		echo '<option value="0"'.((int)$cp['analysisGoTo'] == 0 ? ' selected="selected"' : '').'>'.$lang['srv_analiza_charts'].'</option>';
		echo '<option value="1"'.((int)$cp['analysisGoTo'] == 1 ? ' selected="selected"' : '').'>'.$lang['srv_sumarnik'].'</option>';
        echo '</select>';
        
        echo '<br/>';
        
		echo '<label>';	
		echo '<input id="analiza_legenda" name="analiza_legenda" type="checkbox" ' .(($cp['analiza_legenda'] == '1') ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_analiza_showLegend'];
		echo '</label>';

        echo '<br/>';
        
		echo '<label>';
		echo '<input id="hideEmpty" name="hideEmpty" type="checkbox" ' .
			  (($cp['hideEmpty']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo  $lang['srv_analiza_hideEmpty'] ;
        echo '</label>';
        
        echo '<br/>';
        
		echo '<label>';
		echo '<input id="hideAllSystem" name="hideAllSystem" type="checkbox" ' .
			  (($cp['hideAllSystem']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo  $lang['srv_analiza_hideAllEmpty'] ;
        echo '</label>';
        
        echo '<br/>';
        
		echo '<label>';
		echo $lang['srv_analiza_defAnsCnt'].": ";
		echo '<select id="numOpenAnswers" name="numOpenAnswers" autocomplete="off">';
		$lastElement = end(self::$textAnswersMore);
		
		foreach (self::$textAnswersMore AS $key => $values) {
			echo '<option'.((int)$cp['numOpenAnswers'] == $values ? ' selected="selected"' : '').' value="'.$values.'">';
			if ($values != $lastElement) {
				echo $values;
			} else {
				echo $lang['srv_all'];
			}
			echo '</option>';
		}
		echo '</select>';
        echo '</label>';
        
		echo '</fieldset>';
		
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_results_base_settings'].'</legend>';
		echo '<span class="dsp_sett_label">'.$lang['srv_results_num_digits'].'</span>';
		echo '&nbsp;&nbsp;<span clsss="dsp_sett_label">'.$lang['srv_results_for_percents'].':&nbsp;</span><input type="text" id="dsp_ndp" name="dsp_ndp" value="'.$cp['dsp_ndp'].'" size="2" autocomplete="off">';
		echo '&nbsp;&nbsp;<span class="dsp_sett_label">'.$lang['srv_results_for_average'].':&nbsp;</span><input type="text" id="dsp_nda" name="dsp_nda" value="'.$cp['dsp_nda'].'" size="2" autocomplete="off">';
		echo '&nbsp;&nbsp;<span class="dsp_sett_label">'.$lang['srv_results_for_deviation'].':&nbsp;</span><input type="text" id="dsp_ndd" name="dsp_ndd" value="'.$cp['dsp_ndd'].'" size="2" autocomplete="off">';
		
		echo '<br/>'.$lang['srv_results_decimal_sign'].': ';
		foreach (self::$seperators AS $skey => $seperators) {
			echo '<label>';
			echo '<input type="radio" id="radio_dsp_sep_'.$skey.'" name="radio_dsp_sep" value="'.$skey.'"'.($cp['dsp_sep'] == $skey ? ' checked="checked"' : '').' autocomplete="off">';
			echo self::formatNumber('1234.56',2,$seperators);
			echo '</label>';
		}
		echo '</fieldset>';

		
		// Nastavitve za crosstabe - prikazemo samo v crosstabih
		echo '<fieldset '.(isset($_POST['podstran']) && $_POST['podstran'] == 'crosstabs' ? '' : ' style="display:none;"').'>';
		echo '<legend>'.$lang['srv_results_crostabs_settings'].'</legend>';
		echo '<span class="dsp_sett_label">'.$lang['srv_results_num_digits'].'</span>';
		echo '&nbsp;&nbsp;<span clsss="dsp_sett_label">'.$lang['srv_results_for_residual'].':&nbsp;</span><input type="text" id="dsp_res" name="dsp_res" value="'.$cp['dsp_res'].'" size="2" autocomplete="off">';
		
		echo '<div class="crossCheckHolder">' ;
		echo '<div class="crossCheckHolder">' ;
//		echo '<input id="crossCheck0" name="crossCheck0" type="checkbox" ' . ($cp['crossChk0'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span name="spn_residual" class="ctbChck_sp0">' . $lang['srv_analiza_crosstab_frekvence'] . '</span><br />';
		echo '<label>';
		echo '<input id="crossCheck1" name="crossCheck1" type="checkbox" ' . ($cp['crossChk1'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_sp1" class="ctbChck_sp1">' . $lang['srv_analiza_crosstab_odstotek_vrstice'] . '</span><br />';
		echo '</label>';
		echo '<label>';
		echo '<input id="crossCheck2" name="crossCheck2" type="checkbox" ' . ($cp['crossChk2'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_sp2" class="ctbChck_sp2">' . $lang['srv_analiza_crosstab_odstotek_stolpci'] . '</span><br />';
		echo '</label>';
		echo '<label>';
		echo '<input id="crossCheck3" name="crossCheck3" type="checkbox" ' . ($cp['crossChk3'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_sp3" class="ctbChck_sp3">' . $lang['srv_analiza_crosstab_odstotek_skupni'] . '</span><br />';
		echo '</label>';
		echo '</div>';

		echo '<div class="crossCheckHolder">' ;
		echo '<label>';
		echo '<input id="crossCheckEC" name="crossCheckEC" type="checkbox" ' . ($cp['crossChkEC'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_EC" class="crossCheck_EC">' . $lang['srv_analiza_crosstab_expected_count'] . '</span><br />';
		echo '</label>';
		echo '<label>';
		echo '<input id="crossCheckRE" name="crossCheckRE" type="checkbox" ' . ($cp['crossChkRE'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_RE" class="crossCheck_RE">' . $lang['srv_analiza_crosstab_residual'] . '</span><br />';
		echo '</label>';
		echo '<label>';
		echo '<input id="crossCheckSR" name="crossCheckSR" type="checkbox" ' . ($cp['crossChkSR'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_SR" class="crossCheck_SR">' . $lang['srv_analiza_crosstab_stnd_residual'] . '</span><br />';
		echo '</label>';
		echo '<label>';
		echo '<input id="crossCheckAR" name="crossCheckAR" type="checkbox" ' . ($cp['crossChkAR'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span id="spn_residual_AR" class="crossCheck_AR">' . $lang['srv_analiza_crosstab_adjs_residual'] . '</span><br />';
		echo '</label>';
		echo '</div>';
		
		echo '<div class="crossCheckHolder">' ;
		echo '<label>';
		echo '<input id="crossCheckColor" name="crossCheckColor" type="checkbox" ' . ($cp['doColor'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span class="crossCheckColor">' . $lang['srv_analiza_crosstab_color_residual1'] . '</span><br />';
		echo '</label>';
		echo '<label>';
		echo '<input id="crossCheckValues" name="crossCheckValues" type="checkbox" ' . ($cp['doValues'] == true ? ' checked="checked" ' : '') . ' autocomplete="off"/><span class="crossCheckValues">' . $lang['srv_analiza_crosstab_doValues'] . '</span>';
		echo '</label>';
		/*
		echo '<table id="tbl_color_ersidual" class="residual">';
		echo '<tr><td>'.$lang['srv_analiza_crosstab_value'].'</td><th>+</th><th>-</th></tr>';
		echo '<tr><td>1.1 - 2.0</td><td class="rsdl_bck1">&nbsp;</td><td class="rsdl_bck4">&nbsp;</td></tr>';
		echo '<tr><td>2.1 - 3.0</td><td class="rsdl_bck2">&nbsp;</td><td class="rsdl_bck5">&nbsp;</td></tr>';
		echo '<tr><td>3.1 '.$lang['srv_analiza_crosstab_and_more'].'</td><td class="rsdl_bck3">&nbsp;</td><td class="rsdl_bck6">&nbsp;</td></tr>';
		echo '</table>';
*/
		echo '</div>';
		echo '</div>';
		echo '</fieldset>';
		
		
		// Nastavitve za grafe
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_results_charts_settings'].'</legend>';

		// default poravnava tabel
		echo $lang['srv_chart_table_defAlign'].': ';
		echo '<label>';
		echo '<input type="radio" id="chartTableAlign_0" name="chartTableAlign" value="0"'.($cp['chartTableAlign'] == 0 ? ' checked="checked"' : '').' autocomplete="off">'.$lang['srv_chart_table_defAlign_0'];
		echo '</label>';
		echo '<label>';
		echo '<input type="radio" id="chartTableAlign_1" name="chartTableAlign" value="1"'.($cp['chartTableAlign'] == 1 ? ' checked="checked"' : '').' autocomplete="off">'.$lang['srv_chart_table_defAlign_1'];
		echo '</label>';
		// velikost pisave v grafih
		echo '<br /><label>' . $lang['srv_export_font'] . ': </label>';
		echo '<select name="chartFontSize" id="chartFontSize" >';
		echo '	<option value="8"'.((int)$cp['chartFontSize'] == 8 ? ' selected="selected"' : '').'>8</option>';
		echo '	<option value="9"'.((int)$cp['chartFontSize'] == 9 ? ' selected="selected"' : '').'>9</option>';
		echo '	<option value="10"'.((int)$cp['chartFontSize'] == 10 ? ' selected="selected"' : '').'>10</option>';
		echo '	<option value="11"'.((int)$cp['chartFontSize'] == 11 ? ' selected="selected"' : '').'>11</option>';
		echo '	<option value="12"'.((int)$cp['chartFontSize'] == 12 ? ' selected="selected"' : '').'>12</option>';
		echo '</select>';
		
		// prikaz texta ob numerusu
		echo '<br /><label>' . $lang['srv_chart_numerusText'] . ': </label>';
		echo '<select name="chartNumerusText" id="chartNumerusText" >';
		echo '	<option value="0"'.((int)$cp['chartNumerusText'] == 0 ? ' selected="selected"' : '').'>' . $lang['srv_chart_numerusText_0'] . '</option>';
		echo '	<option value="1"'.((int)$cp['chartNumerusText'] == 1 ? ' selected="selected"' : '').'>' . $lang['srv_chart_numerusText_1'] . '</option>';
		echo '	<option value="2"'.((int)$cp['chartNumerusText'] == 2 ? ' selected="selected"' : '').'>' . $lang['srv_chart_numerusText_2'] . '</option>';
		echo '	<option value="3"'.((int)$cp['chartNumerusText'] == 3 ? ' selected="selected"' : '').'>' . $lang['srv_chart_numerusText_3'] . '</option>';
		echo '	<option value="4"'.((int)$cp['chartNumerusText'] == 4 ? ' selected="selected"' : '').'>' . $lang['without'] . '</option>';	
		echo '</select>';
		
		// prikaz povprecja
		echo '<br /><label>' . $lang['srv_chart_showAvg_long'] . ': </label>';
		echo '<select name="chartAvgText" id="chartAvgText" >';
		echo '	<option value="1"'.((int)$cp['chartAvgText'] == 1 ? ' selected="selected"' : '').'>' . $lang['yes'] . '</option>';
		echo '	<option value="0"'.((int)$cp['chartAvgText'] == 0 ? ' selected="selected"' : '').'>' . $lang['no'] . '</option>';
		echo '</select>';
		
		// stevilcenje vprasanj
		echo '<br />';
		echo '<label>';
		echo '<input id="chartNumbering" name="chartNumbering" type="checkbox" ' .
		 (($cp['chartNumbering']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_nastavitveStevilcenje'];
		echo '</label>';
		// uvodna stran v izvozu
		echo '<br />';	
		echo '<label>';
		echo '<input id="chartFP" name="chartFP" type="checkbox" ' .
			  (($cp['chartFP']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo  $lang['srv_chart_frontpage'] ;	
		echo '</label>';
		// prikaz textovnih odgovorov (vec)
		/*echo '<br />';
		echo '<label>';
		echo '<input id="chartTableMore" name="chartTableMore" type="checkbox" ' .
			  (($cp['chartTableMore']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo  $lang['srv_chart_table_more'] ;
		echo '</label>';*/
		// prikaz nicelnih vrednosti v kroznih grafih
		echo '<br />';
		echo '<label>';
		echo '<input id="chartPieZeros" name="chartPieZeros" type="checkbox" ' .
			  (($cp['chartPieZeros']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo  $lang['srv_chart_pieZeros'] ;
		echo '</label>';
		echo '</fieldset>';
		
#		echo '<label>'.$lang['srv_displaydata_showIcon'].'</label>';
#		echo '&nbsp;<input id="dataShowIcons0" name="dataShowIcons" type="radio" value="0"' .
#		 (($cp['dataShowIcons'] != 1) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
#		echo '<label for="dataShowIcons0">'.$lang['no'].'</label>';
#
#		echo '&nbsp;<input id="dataShowIcons1" name="dataShowIcons" type="radio" value="1"' .
#		 (($cp['dataShowIcons'] == 1) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
#		echo '<label for="dataShowIcons1">'.$lang['yes'].'</label>';		
#		echo '<br/>';
		
		
		#Nastavitve za izvoz podatkov - PRESTAVLJENO V SPLOSNE NASTAVITVE ANKETE
		/*echo '<fieldset style="width: 43%; float: left;">';
		echo '<legend>'.$lang['srv_export_results_settings'].'</legend>';
		
		echo '<label>' . $lang['srv_displaydata_type'] . ': </label>';
		echo '<select name="dataPdfType" id="dataPdfType" >';
		echo '	<option value="0"'.((int)$cp['dataPdfType'] == 0 ? ' selected="selected"' : '').'>' . $lang['srv_displaydata_type0'] . '</option>';
		echo '	<option value="1"'.((int)$cp['dataPdfType'] == 1 ? ' selected="selected"' : '').'>' . $lang['srv_displaydata_type1'] . '</option>';
		echo '	<option value="2"'.((int)$cp['dataPdfType'] == 2 ? ' selected="selected"' : '').'>' . $lang['srv_displaydata_type2'] . '</option>';
		echo '</select>';
		echo Help :: display('displaydata_pdftype');
		
		// ostevilcevanje vprasanj pri izvozih
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataNumbering" name="exportDataNumbering" type="checkbox" ' .
		 (($cp['exportDataNumbering']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_nastavitveStevilcenje'];
		echo '</label>';
		// prikaz recnumov respondentov pri izvozih
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataShowRecnum" name="exportDataShowRecnum" type="checkbox" ' .
		 (($cp['exportDataShowRecnum']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_show_recnum'];	
		echo '</label>';		
		// page break med posameznimi respondenti
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataPB" name="exportDataPB" type="checkbox" ' .
		 (($cp['exportDataPB']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_pagebreak'];
		echo '</label>';
		// izpusti vprasanja brez odgovora
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataSkipEmpty" name="exportDataSkipEmpty" type="checkbox" ' .
		 (($cp['exportDataSkipEmpty']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_skip_empty'];
		echo '</label>';
		// izpusti podvprasanja brez odgovora
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataSkipEmptySub" name="exportDataSkipEmptySub" type="checkbox" ' .
		 (($cp['exportDataSkipEmptySub']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_skip_empty_sub'];
		echo '</label>';
		// landscape postavitev izvoza - V DELU
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataLandscape" name="exportDataLandscape" type="checkbox" ' .
		 (($cp['exportDataLandscape']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_landscape'];
		echo '</label>';
		// prikaz pogojev pri izvozih
		echo '<br />';
		echo '<label>';
		echo '<input id="exportDataShowIf" name="exportDataShowIf" type="checkbox" ' .
		 (($cp['exportDataShowIf']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_if'];
		echo '</label>';	
		// velikost pisave v izvozih
		echo '<br /><label>' . $lang['srv_export_font'] . ': </label>';
		echo '<select name="exportDataFontSize" id="exportDataFontSize" >';
		echo '	<option value="8"'.((int)$cp['exportDataFontSize'] == 8 ? ' selected="selected"' : '').'>8</option>';
		echo '	<option value="10"'.((int)$cp['exportDataFontSize'] == 10 ? ' selected="selected"' : '').'>10</option>';
		echo '	<option value="12"'.((int)$cp['exportDataFontSize'] == 12 ? ' selected="selected"' : '').'>12</option>';
		echo '</select>';
		
		echo '</fieldset>';
		
		
		#Nastavitve za izvoz vprasalnika - PRESTAVLJENO V SPLOSNE NASTAVITVE ANKETE
		echo '<fieldset style="width: 43%; float: right; margin-bottom: 40px;">';
		echo '<legend>'.$lang['srv_export_survey_settings'].'</legend>';
		
		// ostevilcevanje vprasanj pri izvozih
		echo '<label>';
		echo '<input id="exportNumbering" name="exportNumbering" type="checkbox" ' .
		 (($cp['exportNumbering']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_nastavitveStevilcenje'];
		echo '</label>';
		// prikaz pogojev pri izvozih
		echo '<br />';
		echo '<label>';
		echo '<input id="exportShowIf" name="exportShowIf" type="checkbox" ' .
		 (($cp['exportShowIf']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_if'];
		echo '</label>';
		// velikost pisave v izvozih
		echo '<br /><label>' . $lang['srv_export_font'] . ': </label>';
		echo '<select name="exportFontSize" id="exportFontSize" >';
		echo '	<option value="8"'.((int)$cp['exportFontSize'] == 8 ? ' selected="selected"' : '').'>8</option>';
		echo '	<option value="10"'.((int)$cp['exportFontSize'] == 10 ? ' selected="selected"' : '').'>10</option>';
		echo '	<option value="12"'.((int)$cp['exportFontSize'] == 12 ? ' selected="selected"' : '').'>12</option>';
		echo '</select>';
		
		// prikaz uvoda pri izvozih
		echo '<br />';
		echo '<label>';
		echo '<input id="exportShowIntro" name="exportShowIntro" type="checkbox" ' .
		 (($cp['exportShowIntro']) ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_export_intro'];
		echo '</label>';
		echo '</fieldset>';*/
		
		//echo '<span class="clr"></span>';
	}
	
	public static function ajax() {
		switch ($_GET['a']) {
			case 'showProfile':
				self::displayProfiles($_POST['pid']);
				break;
			case 'createProfile':
				self::createNewProfile();
				break;
			case 'changeProfile':
				self::SetDefaultProfile($_POST['pid']);
				# odstranimo sejo za procent po odstotkih če je nastavljena
				if (isset($_SESSION['crossChk1'])) {
					unset($_SESSION['crossChk1']);
				}
				
				break;
			case 'renameProfile':
				self::RenameProfile($_POST['pid'], $_POST['name']);
				break;
			case 'deleteProfile':
				self::DeleteProfile($_POST['pid']);
				break;
			case 'saveProfile':
				self::SaveProfile($_POST['pid']);
				break;
			case 'saveSingleProfileSetting':
				self::SaveSingleProfileSetting();
				break;
			case 'saveResidualProfileSetting':
				self::saveResidualProfileSetting();
				break;
			case 'refreshDropdown':
				self::DisplayLinkDropdown();
				break;
/*
			case 'show_inspectListSpr':
				self::showInspectListSpr();
				break;
			case 'saveInspectListVars':
				self::saveInspectListVars();
				break;
			case 'displayInspectVars':
				self::displayInspectVars();
				break;
*/
			case 'removeKategoriesProfile':
				self::removeKategoriesProfile();
				break;
			case 'changeDataIcons':
				self::changeDataIcons();
				break;
			case 'changeDataIconsSettings':
				self::changeDataIconsSettings();
				break;
			case 'changeUsabilityIconsSettings':
				self::changeUsabilityIconsSettings();
				break;
			case 'changeParaAnalysisGraphSettings':
				self::changeParaAnalysisGraphSettings();
				break;
			default:
				print_r("<pre>");
				print_r($_POST);
				print_r($_GET);
			break;				
		}
	} 
	
	/** Kreira nov profil
	 * 
	 */
	public static function createNewProfile() {
		global $lang;
		
		if ($_POST['profileName'] == null || trim($_POST['profileName']) == '' ) {
			$_POST['profileName'] = $lang['srv_new_profile'];
		}
		
		$new_name = $_POST['profileName'];
		$dsp_ndp = NUM_DIGIT_PERCENT;
		$dsp_nda = NUM_DIGIT_AVERAGE;
		$dsp_ndd = NUM_DIGIT_DEVIATION;
		$dsp_res = NUM_DIGIT_RESIDUAL;

		$dsp_sep = self::$defaultSeperator;
		
		$crossChk0 = '1';
		$crossChk1 = '1'; 
		$crossChk2 = '0';
		$crossChk3 = '0';
		$crossChkEC = '0';
		$crossChkRE = '0';
		$crossChkSR = '0';
		$crossChkAR = '0';
		$doColor = '1';
		$doValues = '1';
		$showCategories = '1';
		$showOther = '1';
		$showNumbers = '1';
		$showText = '1';
		$chartNumbering = '0';
		$chartFontSize = '8';
		$chartFP = '0';
		$chartTableAlign = '0';
		$chartTableMore = '0';
		$chartNumerusText = '0';
		$chartAvgText = '1';
		$chartPieZeros = '0';
		$hideEmpty = '1';
		$hideAllSystem = '0';
		$numOpenAnswers = self::$textAnswersMore['10'];
#		$enableInspect = '0';
		$dataPdfType = '0';
		$exportDataNumbering = '1';
		$exportDataShowIf = '1';
		$exportDataFontSize = '10';
		$exportDataShowRecnum = '1';
		$exportDataPB = '0';
		$exportDataSkipEmpty = '0';
		$exportDataSkipEmptySub = '0';
		$exportDataLandscape = '0';
		$exportNumbering = '1';
		$exportShowIf = '1';
		$exportFontSize = '10';
		$exportShowIntro = '0';
		$dataShowIcons = '1';
		$analysisGoTo = '1';
		$analiza_legenda = '1';		

		# skreiramo profil z imenom in privzetimi nastavitvami
		$iStr = "INSERT INTO srv_datasetting_profile (id,uid,name,dsp_ndp,dsp_nda,dsp_ndd,dsp_res,dsp_sep,
		crossChk0,crossChk1,crossChk2,crossChk3,crossChkEC,crossChkRE,crossChkSR,crossChkAR,doColor,doValues,showCategories,showOther,showNumbers,showText,chartNumbering,chartFontSize,chartFP,chartTableAlign,chartTableMore,chartNumerusText,chartAvgText,chartPieZeros,hideEmpty,hideAllSystem,numOpenAnswers,dataPdfType,exportDataNumbering,exportDataShowIf,exportDataFontSize,exportDataShowRecnum,exportDataPB,exportDataSkipEmpty,exportDataSkipEmptySub,exportDataLandscape,exportNumbering,exportShowIf,exportFontSize,exportShowIntro,dataShowIcons,analysisGoTo,analiza_legenda)" #		enableInspect, 
		."VALUES (NULL,  '".self::getGlobalUserId()."', '".$new_name."', '".$dsp_ndp."', '".$dsp_nda."', '".$dsp_ndd."', '".$dsp_res."', '".$dsp_sep
		."', '".$crossChk0."', '".$crossChk1."', '".$crossChk2."', '".$crossChk3."', '".$crossChkEC."', '".$crossChkRE."', '".$crossChkSR."', '".$crossChkAR."', '".$doColor."', '".$doValues."'"
		.", '".$showCategories."', '".$showOther."', '".$showNumbers."', '".$showText."', '".$chartNumbering."', '".$chartFontSize."', '".$chartFP."', '".$chartTableAlign."', '".$chartTableMore."', '".$chartNumerusText."', '".$chartAvgText."', '".$chartPieZeros."', '".$hideEmpty."', '".$hideAllSystem."', '".$numOpenAnswers."', '".$dataPdfType."', '".$exportDataNumbering."', '".$exportDataShowIf."', '".$exportDataFontSize."', '".$exportDataShowRecnum."', '".$exportDataPB."', '".$exportDataSkipEmpty."', '".$exportDataSkipEmptySub."', '".$exportDataLandscape."'"
		.", '".$exportNumbering."', '".$exportShowIf."', '".$exportFontSize."', '".$exportShowIntro."', '".$dataShowIcons."', '".$analysisGoTo."', '".$analiza_legenda."')"; #
		$ins = sisplet_query($iStr);
		$id = mysqli_insert_id($GLOBALS['connect_db']);
		sisplet_query('COMMIT');
		if ($id > 0) {
			self :: SetDefaultProfile($id);
		} else {
			$id = 0;
			echo $iStr;
			self :: SetDefaultProfile(0);
		}
		echo $id;
		return $id;
	}
	
	static function formatNumber ($value, $digit = 0, $form=null) {

		if (is_array($form) && isset($form['decimal_point'])&& isset($form['thousands'])) {
			$decimal_point = $form['decimal_point'];
			$thousands = $form['thousands'];
		} else {
			$decimal_point = self::$seperators[0]['decimal_point'];
			$thousands = self::$seperators[0]['thousands'];
		}
		
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		$result = number_format($result, $digit, $decimal_point, $thousands);

		return $result;
	}

	static function getVariableTypeNote() {
		
    	if (SurveyDataSettingProfiles :: getSetting('showCategories') == 0
    		|| SurveyDataSettingProfiles :: getSetting('showNumbers') == 0
    		|| SurveyDataSettingProfiles :: getSetting('showOther') == 0
    		|| SurveyDataSettingProfiles :: getSetting('showText') == 0) {

    		echo '<div id="variableTypeNote">Spremenljivke tipa: <strong>';
    		if (self :: getSetting('showCategories') == 0) {
    			echo $prefix.'kategorije';
    			$prefix=', ';
    		}
    		if (self :: getSetting('showNumbers') == 0) {
    			echo $prefix.'števila';
    			$prefix=', ';
    		}
    		if (self :: getSetting('showOther') == 0) {
    			echo $prefix.'drugo';
    			$prefix=', ';
    		}
    		if (self :: getSetting('showText') == 0) {
    			echo $prefix.'besedilo';
    			$prefix=', ';
    		}
    		echo '</strong> niso prikazane. ';
    		echo '<span id="link_variableType_profile_setup" class="as_link">Nastavi</span>';
    		echo '&nbsp;<span id="link_variableType_profile_remove" class="as_link">Odstrani</span>';
    		echo '</div>';
    		return true;		
    	} else {
    		return false;
    	}
    }
    
    function removeKategoriesProfile() {

     	#$pid = $_POST['pid'];
     	$pid = self::$currentProfileId;
     	$p_data =self::GetProfileData($pid); 

     	if ($pid == 0 ) {
			# imamo privzet profil
		} else if ($pid > 0) {
			# shranimo v bazo
			$updateString = "UPDATE srv_datasetting_profile SET showCategories = '1', showOther = '1', showNumbers = '1', showText = '1'  WHERE id = '".$pid."'";
			$updatequery = sisplet_query($updateString);
			sisplet_query('COMMIT');
		} else {
			# shranjujenmo v sejo
			$_SESSION['dataSetting_profile'][self::$surveyId] = array('id'=>'-1',
					'showCategories' => 1,
					'showOther' => 1,
					'showNumbers' => 1,
					'showText' => 1,
					);
		}
    }
    
    static function changeDataIcons() {
    	session_start();
    	if (isset($_POST['dataIcons_quick_view']) && $_POST['dataIcons_quick_view'] == '1') {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_quick_view'] = true;
    	} else {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_quick_view'] = false;
    	}
    	if (isset($_POST['dataIcons_write']) && $_POST['dataIcons_write'] == '1') {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_write'] = true;
    	} else {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_write'] = false;
    	}
    	if (isset($_POST['dataIcons_edit']) && $_POST['dataIcons_edit'] == '1') {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_edit'] = true;
    	} else {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_edit'] = false;
    	}
    	if (isset($_POST['dataIcons_labels']) && $_POST['dataIcons_labels'] == '1') {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_labels'] = true;
    	} else {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_labels'] = false;
    	}
    	if (isset($_POST['dataIcons_multiple']) && $_POST['dataIcons_multiple'] == '1') {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_multiple'] = true;
    	} else {
    		$_SESSION['sid_'.self::$surveyId]['dataIcons_multiple'] = false;
    	}
    }
	
    // TODO!!! Spodnje funkcije bi bilo smoterno združit
	static function changeDataIconsSettings(){
		session_start();

		if (isset($_POST['dataIcons_settings'])){
			if($_POST['dataIcons_settings'] == '1') {
				$_SESSION['sid_'.self::$surveyId]['dataIcons_settings'] = true;
			} else {
				$_SESSION['sid_'.self::$surveyId]['dataIcons_settings'] = false;
			}
			session_commit();
		}
	}
	
	static function changeUsabilityIconsSettings(){
		session_start();

		if (isset($_POST['usabilityIcons_settings'])){
			if($_POST['usabilityIcons_settings'] == '1') {
				$_SESSION['sid_'.self::$surveyId]['usabilityIcons_settings'] = true;
			} else {
				$_SESSION['sid_'.self::$surveyId]['usabilityIcons_settings'] = false;
			}
			session_commit();
		}
	}
	
	static function changeParaAnalysisGraphSettings(){
		session_start();

		if (isset($_POST['paraAnalysisGraph_settings'])){
			if($_POST['paraAnalysisGraph_settings'] == '1') {
				$_SESSION['sid_'.self::$surveyId]['paraAnalysisGraph_settings'] = true;
			} else {
				$_SESSION['sid_'.self::$surveyId]['paraAnalysisGraph_settings'] = false;
			}
			session_commit();
				
		}
	}

	// Shranimo stevilo odprtih odgovorov (izven profilov)
	static function saveSingleProfileSetting() {	
		global $lang;
		
		$pid = (isset($_POST['pid'])) ? $_POST['pid'] : -1;
		
		if(isset($_POST['what']))
			$what = $_POST['what'];
		
		if(isset($_POST['value']))
			$value = $_POST['value'];
		
		# shranimo v bazo		
		if ((int)$pid > 0) {
			$updateString = "UPDATE srv_datasetting_profile SET ".$what."='".$value."' WHERE id='".$pid."'";
			$updatequery = sisplet_query($updateString);
			if (!$updatequery) echo mysqli_error($GLOBALS['connect_db']);
        
			sisplet_query('COMMIT');
			
			# nastavimo privzet profil na trenutnega
			self :: SetDefaultProfile($pid);			
		} 
		# shranjujenmo v sejo	
		else {
			// Nastavimo vrednosti default profila, ker drugace default vrednosti niso ok
			$_SESSION['dataSetting_profile'][self::$surveyId] = self::$profiles['0'];
			$_SESSION['dataSetting_profile'][self::$surveyId]['id'] = '-1';
			$_SESSION['dataSetting_profile'][self::$surveyId]['name'] = $lang['srv_temp_profile'];
			
			// Nastavimo se spremenjeno nastavitev
			$_SESSION['dataSetting_profile'][self::$surveyId][$what] = $value;
		
			self :: SetDefaultProfile(-1);
		}

		return $updatequery;
	}
	
	// Shranimo residuale za crosstabe
	static function saveResidualProfileSetting() {	
		global $lang;
		
		$pid = (isset($_POST['pid'])) ? $_POST['pid'] : -1;
		
		if(isset($_POST['value'])){
			$crossCheckEC = $_POST['value'];
			$crossCheckRE = $_POST['value'];
			$crossCheckSR = $_POST['value'];
			$crossCheckAR = $_POST['value'];
		}
		
		# shranimo v bazo		
		if ((int)$pid > 0) {
			$updateString = "UPDATE srv_datasetting_profile SET crossChkEC='".$crossCheckEC."', crossChkRE='".$crossCheckRE."', crossChkSR='".$crossCheckSR."', crossChkAR='".$crossCheckAR."' WHERE id='".$pid."'";
			$updatequery = sisplet_query($updateString);
			if (!$updatequery) echo mysqli_error($GLOBALS['connect_db']);
        
			sisplet_query('COMMIT');
			
			# nastavimo privzet profil na trenutnega
			self :: SetDefaultProfile($pid);			
		} 
		# shranjujenmo v sejo	
		else {
			// Nastavimo vrednosti default profila, ker drugace default vrednosti niso ok
			$_SESSION['dataSetting_profile'][self::$surveyId] = self::$profiles['0'];
			$_SESSION['dataSetting_profile'][self::$surveyId]['id'] = '-1';
			$_SESSION['dataSetting_profile'][self::$surveyId]['name'] = $lang['srv_temp_profile'];
			
			// Nastavimo se spremenjeno nastavitev
			$_SESSION['dataSetting_profile'][self::$surveyId]['crossChkEC'] = $crossCheckEC;
			$_SESSION['dataSetting_profile'][self::$surveyId]['crossChkRE'] = $crossCheckRE;
			$_SESSION['dataSetting_profile'][self::$surveyId]['crossChkSR'] = $crossCheckSR;
			$_SESSION['dataSetting_profile'][self::$surveyId]['crossChkAR'] = $crossCheckAR;
		
			self :: SetDefaultProfile(-1);
		}

		return $updatequery;
	}
}
?>