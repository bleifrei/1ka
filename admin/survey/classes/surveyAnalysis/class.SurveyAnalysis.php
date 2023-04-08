<?php
/**
 * @author 	Gorazd Veselič
 * @date		Juny 2010
 *
 * | -> \x7C
 * ` -> \x60
 * ' -> \x27
 * " -> \x22
 */

define("EXPORT_FOLDER", "admin/survey/SurveyData");

define('DATE_FORMAT', 'Y-m-d');
define("ALLOW_HIDE_ZERRO_REGULAR", false);				# omogočimo delovanje prikazovanja/skrivanja ničelnih vrednosti za navadne odgovore
define("ALLOW_HIDE_ZERRO_MISSING", true);				# omogočimo delovanje prikazovanja/skrivanja ničelnih vrednosti za missinge
define("AUTO_HIDE_ZERRO_VALUE", 20);						# nad koliko kategorij skrivamo ničelne vrednosti


# mejne vrednosti za barvanje residualov
define("RESIDUAL_COLOR_LIMIT1", 1.00);
define("RESIDUAL_COLOR_LIMIT2", 2.00);
define("RESIDUAL_COLOR_LIMIT3", 3.00);


DEFINE (STR_DLMT, '|');
DEFINE (NEW_LINE, "\n");
DEFINE (TMP_EXT, '.tmp');
DEFINE (DAT_EXT, '.dat');

class SurveyAnalysis {
	
	static public $inited = false; 							# ali smo razred inicializirali
	
	static public $sid;										# id ankete
	static public $folder = '';								# pot do folderja
	static private $headFileName = null;					# pot do header fajla
	static private $dataFileName = null;					# pot do data fajla
	static private $dataFileStatus = null;					# status data datoteke
	static private $dataFileUpdated = null;					# kdaj je bilo updejtano
	static private $noHeader = false;						# errorchecking - če header datoteka ne obstaja

	static private $survey = null;							# podatki ankete
	
	static public $podstran;								# podstran
	static public $db_table;								# katere tabele uporabljamo

	static public $_CURRENT_STATUS_FILTER = ''; 	# filter po statusih, privzeto izvažamo 6 in 5
	static public $_FILTRED_VARIABLES = array(); 			# filter po spremenljivkah
	static public $_FILTRED_TYPES = array(); 				# filter po tipih spremenljivk
	static public $_FILTRED_OTHER = array(); 				# filter za polja drugo

	static public $_SHOW_LEGENDA = false;		 			# ali izrisujemo legendo

	static public $_LOOPS = array();		 				# array z loopi
	static public $_CURRENT_LOOP = null;		 			# v kateri zanki smo

	static public $_tmp_file_prefix = null;					# predpona začasnih datotek za analizo
	static public $_tmp_file_ext = '.tmp';					# končnicazačasnih datotek za analizo

	static public $appropriateStatus = array(6,5); 			# Statusi anket katere štejemo kot ustrezne
	static public $unAppropriateStatus = array(4,3,2,1,0); 	# Statusi anket katere štejemo kot neustrezne
	static public $unKnownStatus = array('null'); 			# Statusi anket katere štejemo kot neustrezne

	static public $currentMissingProfile = 1; 				# Kateri Missing profil je izbran
	static public $missingProfileData = null; 				# Nastavitve trenutno izbranega manjkajočega profila
	//static public $currentZankaProfile = 0; 				# Kateri zanka profil je izbran
	static public $currentFilterProfile = 1; 				# Kateri IF profil je izbran
	
	
	static public $_PROFILE_ID_STATUS = null;
	static public $_PROFILE_ID_VARIABLE = null;
	static public $_PROFILE_ID_CONDITION = null;
	
	static public $printPreview = false;					# ali prikazujemo podatke kot print preview;
	

	static public $_HEADERS = array();						# shranimo podatke vseh variabel
	static public $_FREQUENCYS = array();					# v to variablo shranemo frekvence
	static public $_DESCRIPTIVES = array();					# v to variablo shranemo opisne statistike

	static public $_HAS_TEST_DATA = false;					# ali anketa vsebuje testne podatke

	static public $show_spid_div = true;					# ali prikazuje spremenljivke v posameznem divu ( pride prav pri ajaxu, ko loadamo v star div

	static public $setUpJSAnaliza = true;					# ali nastavimo __analiza = 1 v JS

	static public $crossTabClass = null;					# razred za crostabulacije

	static public $returnAsHtml = false;					# ali vrne rezultat analiz kot html ali ga izpiše
	static public $isArchive = false;						# nastavimo na true če smo v arhivu

	static public $enableInspect = true; 					# checkbox enableInspect

	static public $frequencyAddInvalid = true; 				# ali pri frekvencah dodajamo neveljavne

	static public $_forceShowEmpty = false; 				# vsili prikaz spremenljivke tudi če je prazna
	
	static public $hideEmptyValue = false; 					# Ali skrivamo prazne enote če je več kot 20 kategorij

	static public $publicAnalyse = false; 					# Ali je javna analaiza

	# CSS STILI
	static public $cssColors = array (
			'0_0' => 'anl_bck_0_0',
			'0_1' => 'anl_bck_0_1',
			'1_0' => 'anl_bck_1_0',
			'1_1' => 'anl_bck_1_1',
			'2_0' => 'anl_bck_2_0',
			'2_1' => 'anl_bck_2_1',
			'text_0' => 'anl_bck_text_0',
			'text_1' => 'anl_bck_text_1'

	);

	static public $textAnswersMore = array('0'=>'10','10'=>'30','30'=>'300','300'=>'600','600'=>'900','900'=>'100000');
	
	
	/**
	 * Inicializacija
	 *
	 * @param int $anketa
	 */
	static function Init( $anketa = null ) {
		global $surveySkin, $global_user_id, $site_path, $lang;
		
		self::$folder = $site_path . EXPORT_FOLDER.'/';
		
		if ((int)$anketa > 0) { # če je poadan anketa ID
		
			session_start();			
			self::$sid = $anketa;
			
			if (self::$inited  == false){
				
				SurveyAnalysisHelper::getInstance()->Init(self::$sid);				
				
				Common::deletePreviewData($anketa);
                
                
                // Poskrbimo za datoteko s podatki
                $SDF = SurveyDataFile::get_instance();
                $SDF->init($anketa);           
                $SDF->prepareFiles();  

				self::$headFileName = $SDF->getHeaderFileName();
				self::$dataFileName = $SDF->getDataFileName();
                self::$dataFileStatus = $SDF->getStatus();
                self::$dataFileUpdated = $SDF->getFileUpdated();

					
				if (isset($_GET['podstran'])) {
					self::$podstran = $_GET['podstran'];
				} else if (isset($_POST['podstran'])) {
					self::$podstran = $_POST['podstran'];
				} else if (isset($_GET['m'])) {
					self::$podstran = $_GET['m'];
				} else {
					self::$podstran = M_ANALYSIS_SUMMARY;
				}
					
				# če smo v crostabih, jih incializiramo
				if (self::$podstran == M_ANALYSIS_CROSSTAB) {
					self::$crossTabClass = new SurveyCrosstabs();
					self::$crossTabClass->Init(self::$sid);
				}					
				
				self::$_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/';
				# začasni folder kamor se shranjujejo začasni podatki
				self::$_tmp_file_prefix = 'analysis_'.self::$sid.'_'.self::$podstran.'_';
	
				# Inicializiramo in polovimo nastavitve missing profila
				SurveyStatusProfiles :: Init(self::$sid,$global_user_id);
				SurveyMissingProfiles :: Init(self::$sid,$global_user_id);
				SurveyVariablesProfiles :: Init(self::$sid);
				SurveyUserSetting :: getInstance()->Init(self::$sid, $global_user_id);

				SurveyConditionProfiles :: Init(self::$sid, $global_user_id);
				SurveyZankaProfiles :: Init(self::$sid, $global_user_id);
				SurveyTimeProfiles :: Init(self::$sid, $global_user_id);
				SurveyDataSettingProfiles :: Init(self::$sid);
					
				$smv = new SurveyMissingValues(self::$sid);
				$zs = new SurveyZoom(self::$sid);
					
				
				if ( self::$dataFileStatus == FILE_STATUS_NO_DATA || self::$dataFileStatus == FILE_STATUS_SRV_DELETED) {
					if (self::$podstran != M_ANALYSIS_ARCHIVE)
						Common::noDataAlert();
					
					return false;
				}
					
				SurveyInfo :: getInstance()->SurveyInit(self::$sid);
				self::$survey = SurveyInfo::getInstance()->getSurveyRow();
				
				UserSetting :: getInstance()->Init($global_user_id);
	
				if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
					self::$db_table = '_active';
				}
	
				# nastavimo vse filtre
				self::setUpFilter();
				self::$inited = true;
			}
		} else {
			die("Napaka!");
		}
	}

	/** Funkcija ki nastavi vse filtre
	 *
	 */
	static public function setUpFilter($pid=null) {
		if (self::$dataFileStatus == FILE_STATUS_SRV_DELETED) {
			return false;
		}
		 
		if (self::$headFileName !== null && self::$headFileName != '' && file_exists(self::$headFileName)) {
			self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));

			# odstranimo sistemske variable tipa email, ime, priimek, geslo
			self::removeSystemVariables();
	
			#
			# poiščemo kater profil uporablja uporabnik
			$_currentMissingProfile = SurveyUserSetting :: getInstance()->getSettings('default_missing_profile');
			self::$currentMissingProfile = (isset($_currentMissingProfile) ? $_currentMissingProfile : 1);
	
			# poiščemo kateri profil variabel imamo
			$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
			$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
			if ($dvp != $_currentVariableProfile) {
				SurveyUserSetting :: getInstance()->saveSettings('default_variable_profile', $_currentVariableProfile);
			}
			self::$_PROFILE_ID_VARIABLE = $_currentVariableProfile;
			
			# filtriranje po statusih
			self::$_CURRENT_STATUS_FILTER = SurveyStatusProfiles :: getStatusAsAWKString($pid);
	
			# filtriranje po časih
			$_time_profile_awk = SurveyTimeProfiles :: getFilterForAWK(self::$_HEADERS['unx_ins_date']['grids']['0']['variables']['0']['sequence']);
	
			# dodamo še ife
			SurveyConditionProfiles :: setHeader(self::$_HEADERS);
			$_condition_profile_AWK = SurveyConditionProfiles:: getAwkConditionString();
	
			# dodamo dodatne pogoje za GORENJE FILTRIRANJE
			if(Common::checkModule('gorenje')){
				$SAG = new SurveyAnalysisGorenje(self::$sid);
				$_gorenje_filter_AWK = $SAG->getAWKString(self::$_HEADERS);
				if($_gorenje_filter_AWK != ''){
					// Ce imamo oba samo pripnemo dodatne filtre
					if($_gorenje_filter_AWK != '' && $_condition_profile_AWK != '')
						$_condition_profile_AWK .= '&&'.$SAG->getAWKString(self::$_HEADERS);
					else
						$_condition_profile_AWK = $SAG->getAWKString(self::$_HEADERS);
				}
			}
			
			# dodamo še ife za inspect
			$SI = new SurveyInspect(self::$sid);
			$_inspect_condition_awk = $SI->generateAwkCondition();
	
			# dodamo še zoom
			$_zoom_condition = SurveyZoom::generateAwkCondition();
	
			# ali imamo filter na testne podatke
			#$filter_testdata = isset($_SESSION['testData'][self::$sid]['includeTestData']) && $_SESSION['testData'][self::$sid]['includeTestData'] == 'false';
			if (isset(self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence']) && (int)self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'] > 0) {
				$test_data_sequence = self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'];
				$filter_testdata = SurveyStatusProfiles :: getStatusTestAsAWKString($test_data_sequence);
			}
			
			# ali imamo filter na uporabnost
			if (isset(self::$_HEADERS['usability']['variables'][0]['sequence']) && (int)self::$_HEADERS['usability']['variables'][0]['sequence'] > 0) {
				$usability_data_sequence = self::$_HEADERS['usability']['variables'][0]['sequence'];
				$filter_usability = SurveyStatusProfiles :: getStatusUsableAsAWKString($usability_data_sequence);
			}
			
			if (($_condition_profile_AWK != "" && $_condition_profile_AWK != null )
					|| ($_inspect_condition_awk != "" && $_inspect_condition_awk != null)
					|| ($_time_profile_awk != "" && $_time_profile_awk != null)
					|| ($_zoom_condition != "" && $_zoom_condition != null)
					|| ($filter_testdata != null)
					|| ($filter_usability != null)) {
				self::$_CURRENT_STATUS_FILTER = '('.self::$_CURRENT_STATUS_FILTER;
					
				if ($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) {
					self::$_CURRENT_STATUS_FILTER .= ' && '.$_condition_profile_AWK;
				}
					
				if ($_inspect_condition_awk != "" && $_inspect_condition_awk != null ) {
					self::$_CURRENT_STATUS_FILTER .= ' && '.$_inspect_condition_awk;
				}
					
				if ($_time_profile_awk != "" && $_time_profile_awk != null) {
					self::$_CURRENT_STATUS_FILTER .= ' && '.$_time_profile_awk;
				}
					
				if ($_zoom_condition != "" && $_zoom_condition != null) {
					self::$_CURRENT_STATUS_FILTER .= ' && '.$_zoom_condition;
				}
					
				if ($filter_testdata != null ) {
					self::$_CURRENT_STATUS_FILTER .= '&&('.$filter_testdata.')';
					/*
					 $test_data_sequence = self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'];
					if ((int)$test_data_sequence > 0) {
					self::$_CURRENT_STATUS_FILTER .= '&&($'.$filter_testdata.')';
					}
					*/
				}
				
				if ($filter_usability != null ) {
					self::$_CURRENT_STATUS_FILTER .= '&&('.$filter_usability.')';
				}
					
				self::$_CURRENT_STATUS_FILTER .= ')';
			}
	
			# filtriranje po spremenljivkah
			self::$_FILTRED_VARIABLES = null;
			self::$_FILTRED_VARIABLES = SurveyVariablesProfiles :: getProfileVariables(null);

			# upoštevamo tudi filtriranje po tipu : kategorija, števila, besedilo
			self::$_FILTRED_TYPES = SurveyDataSettingProfiles :: getSetting('spr_types');
			self::$_FILTRED_OTHER = SurveyDataSettingProfiles :: getSetting('showOther');
			 
			# če smo radio enableInspect
			$SI = new SurveyInspect(self::$sid);
			self::$enableInspect = $SI->isInspectEnabled();
	
			# ali izrisujemo legendo
			self::$_SHOW_LEGENDA = (SurveyDataSettingProfiles :: getSetting('analiza_legenda') == true) ? true : false;
			 
			 
			if (self::$dataFileStatus >= 0) {
	
				if (isset(self::$_HEADERS['testdata'])) {
					self::$_HAS_TEST_DATA = true;
				}
			}
		}
		else
		{
			self::$noHeader = true;
		}
	}

	/** Prikazuje filtre
	 *
	 */
	static function DisplayFilters($hq=1) {
        global $lang;

        if (self::$dataFileStatus == FILE_STATUS_NO_DATA || self::$noHeader == true) {
			return false;
		}
		 
		if (self::$dataFileStatus != FILE_STATUS_SRV_DELETED) {
			      
            if (self::$setUpJSAnaliza == true) {
				echo '<script> window.onload = function() { __analiza = 1; } </script>';
			}
				
			echo '<iframe id="ifmcontentstoprint" style="height: 0px; width: 0px; position: absolute"></iframe>';

			
			# ali imamo testne podatke
			if (self::$_HAS_TEST_DATA) {
                # izrišemo bar za testne podatke
                $SSH = new SurveyStaticHtml(self::$sid);
				$SSH -> displayTestDataBar(true);
			}
    
            
			if (self::$podstran == M_ANALYSIS_DESCRIPTOR || self::$podstran == M_ANALYSIS_FREQUENCY || self::$podstran == M_ANALYSIS_SUMMARY || self::$podstran == M_ANALYSIS_CHARTS || self::$podstran == M_ANALYSIS_LINKS || self::$podstran == M_ANALYSIS_CREPORT) {
                
                echo '<div id="globalSetingsHolder">';

				# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
				SurveyZoom :: displayZoomConditions();

				echo '</div>'; # id="globalSetingsHolder"
				
				
				// Posebni filtri za Gorenje
				if(Common::checkModule('gorenje')){
					
					$SAG = new SurveyAnalysisGorenje(self::$sid);	
					
					if($SAG->hasSpremenljivke()){
						echo '<div id="gorenjeFiltersHolder">';
						$SAG->displayFilters();
						echo '</div>';
					}
				}
			}
		} 
		else {
			echo "Anketa je bila izbrisana! Prikaz podatkov ni mogoč!";
		}
	}

	/** Prikazuje podatke analize
	 *
	 */
	static function Display() {
		global $lang;
		
		if (self::$dataFileStatus == FILE_STATUS_NO_DATA || self::$dataFileStatus == -3 || self::$noHeader == true) {
			if (self::$podstran != M_ANALYSIS_ARCHIVE)
				return false;
		}
		# zakeširamo vsebino, in jo nato po potrebi zapišpemo v html

		ob_start();
		
		# če nismo v crostabih
		if (self::$podstran != M_ANALYSIS_CROSSTAB) 
		{
			# v arhivih ne izpisujemo
			if (self::$isArchive == false) 
			{
				echo '<div id="displayFilterNotes">';
				# če imamo filter zoom ga izpišemo
				SurveyZoom::getConditionString();
				# če imamo filter ifov ga izpišemo
				SurveyConditionProfiles:: getConditionString();
				# če imamo filter ifov za inspect ga izpišemo
				$SI = new SurveyInspect(self::$sid);
				$SI->getConditionString();
				# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
				SurveyTimeProfiles :: printIsDefaultProfile();
				# če imamo filter spremenljivk ga izpišemo
				SurveyVariablesProfiles:: getProfileString();
				# če imamo rekodiranje
				$SR = new SurveyRecoding(self::$sid);
				$SR -> getProfileString();
	
				SurveyDataSettingProfiles::getVariableTypeNote();
				echo '</div>';
			}

		}
		 
		if (self::$dataFileStatus == FILE_STATUS_OLD && self::$podstran != M_ANALYSIS_ARCHIVE) {
			echo "Posodobljeno: ".date("d.m.Y, H:i:s", strtotime(self::$dataFileUpdated));
		}
		 
		# krostabe naredimo
		if(self::$podstran == M_ANALYSIS_CROSSTAB ) {
			self::$crossTabClass->Display();
		} else {
			# polovimo nastavtve missing profila
			self::$missingProfileData = SurveyMissingProfiles::getProfile(self::$currentMissingProfile);
			if (self::$podstran != M_ANALYSIS_ARCHIVE) {
				self::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
			}
			if (!is_countable(self::$_LOOPS) || count(self::$_LOOPS) == 0) {
					
				# če nimamo zank
				switch (self::$podstran) {
					
					case M_ANALYSIS_SUMMARY :
						self::displaySums();
						break;
					case M_ANALYSIS_DESCRIPTOR :
						self::displayDescriptives();
						break;
					case M_ANALYSIS_FREQUENCY :
						self::displayFrequency();
						break;
					case M_ANALYSIS_ARCHIVE :
						self::displayAnalysisArchive();
						break;
					default :
						self::$podstran = M_ANALYSIS_SUMMARY;
						self::Display();
						break;
			}
			} else {
				$loop_cnt = 0;
				# če mamo zanke
				foreach ( self::$_LOOPS AS $loop) 
				{
					$loop_cnt++;
					$loop['cnt'] = $loop_cnt;
					self::$_CURRENT_LOOP = $loop;
					echo '<h2 data-loopId="'.self::$_CURRENT_LOOP['cnt'].'">'.$lang['srv_zanka_note'].$loop['text'].'</h2>';
					switch (self::$podstran) 
					{
						case M_ANALYSIS_SUMMARY :
							self::displaySums();
							break;
						case M_ANALYSIS_DESCRIPTOR :
							self::displayDescriptives();
							break;
						case M_ANALYSIS_FREQUENCY :
							self::displayFrequency();
							break;
						case M_ANALYSIS_ARCHIVE :
							self::displayAnalysisArchive();
							break;
						default :
							self::$podstran = M_ANALYSIS_SUMMARY;
							self::Display();
							break;
					}
						
				}
			}

		}
		#ob_flush(); flush();
		if (self::$returnAsHtml == false) {
			ob_flush(); flush();
			return;
		} else {
			$result = ob_get_clean();
			ob_flush(); flush();
			return $result;
		}
	}

	/** Izrišemo opisne
	 *
	 */
	static function displayDescriptives($_spid = null) {
		global $site_path, $lang;
		# preberemo header

		if (self::$headFileName !== null ) {
			#preberemo HEADERS iz datoteke
			self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));

		# odstranimo sistemske variable tipa email, ime, priimek, geslo
		self::removeSystemVariables();

		# polovimo frekvence
		self::getDescriptives();

		# izpišemo opisne statistike
		$vars_count = count(self::$_FILTRED_VARIABLES);
		$line_break = '';


		# dodamo še kontrolo če kličemo iz displaySingleVar
		if (isset($_spid) && $_spid !== null) {
			self::$_HEADERS = array($_spid => self::$_HEADERS[$_spid]);
		}
			
		# ali prikazujemo spremenljivke brez veljavnih odgovorov
		$show_spid = array();
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm'
			&& ( count(self::$_FILTRED_VARIABLES) == 0 || (count(self::$_FILTRED_VARIABLES) > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ))
				 && in_array($spremenljivka['tip'], self::$_FILTRED_TYPES) ){
			$only_valid = 0;

			$show_enota = false;
			# preverimo ali imamo samo eno variablo in če iammo enoto
			if ((int)$spremenljivka['enota'] != 0 || $spremenljivka['cnt_all'] > 1 ) {
				$show_enota = true;
			}

			# izpišemo glavno vrstico z podatki
			$_sequence  = null;
			# za enodimenzijske tipe izpišemo podatke kar v osnovni vrstici
			if (!$show_enota) {
				$variable = $spremenljivka['grids'][0]['variables'][0];
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_DESCRIPTIVES[$_sequence]['validCnt'];
			} else {
				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$show_enota = false;
				}
				#zloopamo skozi variable
				$_sequence = null;
				$grd_cnt=0;
				if (count($spremenljivka['grids']) > 0)
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						
					# dodamo dodatne vrstice z albelami grida
				if (count ($grid['variables']) > 0)
					foreach ($grid['variables'] AS $vid => $variable ){
					# dodamo ostale vrstice
				$do_show = ($variable['other'] !=1 && ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || $spremenljivka['tip'] == 5 || $spremenljivka['tip'] == 8 ))
				? false
				: true;
				if ($do_show) {
					$only_valid += (int)self::$_DESCRIPTIVES[$variable['sequence']]['validCnt'];
				}
				}
				}
			} //else: if (!$show_enota)

			if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
				$show_spid[$spid] = false;
			} else {
				$show_spid[$spid] = true;
			}

		}
		}
		echo '<table class="anl_tbl anl_ba" >';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb anl_w90">&nbsp;<span>'.'</span></td>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb anl_w110">' . $lang['srv_analiza_opisne_variable'] .'<span>'.'</span></td>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_variable_text'] .'<span>'.'</span></td>';
		if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_variable_type'] .'<span >'.'</span></td>';
			echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_variable_expression'] .'<span >'.'</span></td>';
			echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_variable_skala'] .'<span >'.'</span></td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_m'] .'<span >'.'</span></td>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_num_units'] .'<span >'.'</span></td>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_povprecje_odstotek'] .'<span >'.'</span></td>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $lang['srv_analiza_opisne_odklon'] .'<span >'.'</span></td>';
		echo '<td class="anl_br anl_ac anl_bck anl_variabla_line anl_bb" >' . $lang['srv_analiza_opisne_min'] .'<span >'.'</span></td>';
		echo '<td class=" anl_ac anl_bck anl_variabla_line anl_bb" >' . $lang['srv_analiza_opisne_max'] .'<span >'.'</span></td>';
		echo '</tr>';

		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			# dajemo v bufer, da da ne prikazujemo vprašanj brez veljavnih odgovorov če imamo tako nastavljeno
				
			# preverjamo ali je meta
			if ($show_spid[$spid] && $spremenljivka['tip'] != 'm'
			&& ( count(self::$_FILTRED_VARIABLES) == 0 || (count(self::$_FILTRED_VARIABLES) > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ))
				 && in_array($spremenljivka['tip'], self::$_FILTRED_TYPES) ){

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
						$var_cnt=0;
						# dodamo dodatne vrstice z albelami grida
						if (count ($grid['variables']) > 0)
							foreach ($grid['variables'] AS $vid => $variable ){
							# dodamo ostale vrstice
						$do_show = ($variable['other'] !=1 && ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || $spremenljivka['tip'] == 5 || $spremenljivka['tip'] == 8 ))
						? false
						: true;
						if ($do_show) {
							$variable['var_cnt'] = $var_cnt;
							self::displayDescriptivesVariablaRow($spremenljivka,$grid,$variable,$_css);

						}
						$grid['new_grid'] = false;
						$var_cnt++;
						}
					}
				} //else: if (!$show_enota)
			} // end if $spremenljivka['tip'] != 'm'
		} // end foreach  self::$_HEADERS
		echo '</table >';
		
		// Izrisemo ikone na dnu
		if ( (!isset($_spid) || $_spid == null) && (count(self::$_LOOPS) == 0 || self::$_CURRENT_LOOP['cnt'] == count(self::$_LOOPS)) && ($_GET['m'] != 'analysis_creport') )
			self::displayBottomSettings('desc');
		
		} // end if else ($_headFileName == null)

	}

	/** Izriše vrstico z opisnimi
	 *
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	static function displayDescriptivesVariablaRow($spremenljivka,$grid,$variable=null) {
		global $lang;

		$cssBack = $variable['other'] != 1 ? ' anl_bck_desc_2' : ' anl_bck_desc_3';
		$cssMove = $variable['other'] != 1 ? ' anl_tin' : ' anl_tin1';
		$cssBack .= (int)$grid['new_grid'] == 1 ? ' anl_bt ' : ' anl_bt_dot ';
		$_sequence = $variable['sequence'];	# id kolone z podatki
		if ($_sequence != null) {
			$_desc = self::$_DESCRIPTIVES[$_sequence];
		}

		# pokličemo objekt SpremenljivkaSkala
		$objectSkala = new SpremenljivkaSkala($spremenljivka['spr_id']);
		
		# če smo na začetku grida dodamo podatke podvprašanja
		if ($variable['var_cnt'] == 0 && in_array($spremenljivka['tip'],array(16,19,20) ) ) {
			echo '<tr>';
			echo '<td class="anl_bck anl_ac anl_br  link_no_decoration">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_variabla_sub anl_double_bt anl_bb">';
			echo $grid['variable'];
			echo '</td>';
			echo '<td class="anl_al anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb" colspan="'.(self::$_SHOW_LEGENDA ? '10' : '7').'">';
			echo $grid['naslov'];
			echo '</td>';
			/*
			 if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			}
			echo '<td class="anl_ac ss=anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td cla"anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			echo '<td class="anl_ac anl_br link_no_decoration anl_bck_desc_1 anl_double_bt anl_bb">&nbsp;</td>';
			*/
			echo '</tr>';
		}
		echo '<tr>';
		echo '<td class="anl_bck anl_ac anl_br  link_no_decoration">';
		echo '&nbsp;';
		echo '</td>';
		echo '<td class="'.$cssBack.' anl_ac anl_br link_no_decoration anl_variabla_sub">';
		echo $variable['variable'];
		echo '</td>';
		echo '<td class="' . $cssBack . $cssMove . ' anl_br">';
		//echo $grid['naslov'] . ' - ' .$variable['naslov'];
		echo $variable['naslov'];
		echo ($spremenljivka['enota'] == 1) ? ' - '.$variable['naslov2'] : '';
		echo '</td>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip = self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="' . $cssBack . ' anl_ac anl_br" title="'.$_tip.'">'.'&nbsp;'.'</td>';
			echo '<td class="' . $cssBack . ' anl_ac anl_br" title="'.$_tip.'">'.$_tip.'</td>';
			echo '<td class="' . $cssBack . ' anl_ac anl_br" title="'.$_oblika.'">' .$_oblika. '</td>';
		}
		#veljavno
		echo '<td class="' . $cssBack . ' anl_br anl_ac">'.(int)$_desc['validCnt'].'</td>';

		#ustrezno
		echo '<td class="' . $cssBack . ' anl_br anl_ac">'.(int)$_desc['allCnt'].'</td>';
		echo '<td class="' . $cssBack . ' anl_br anl_ac">';
		
		
		if ( isset($_desc['avg']) && (int)$objectSkala->getSkala() !== 1 ) {
			echo self::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		} else if (isset($_desc['avg']) && $spremenljivka['tip'] == 2 && (int)$objectSkala->getSkala() == 1 ) {
			echo self::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%');
		}
		echo '</td>';
		echo '<td class="' . $cssBack . ' anl_br anl_ac">';
		if (isset($_desc['div']) && (int)$objectSkala->getSkala() !== 1) {
			echo self::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
		}
		echo '</td>';
		echo '<td class="' . $cssBack . ' anl_br anl_ac">'.((int)$objectSkala->getSkala() !== 1 ? $_desc['min'] : '').'</td>';
		echo '<td class="' . $cssBack . ' anl_ac">'.((int)$objectSkala->getSkala() !== 1 ? $_desc['max'] : '').'</td>';

		echo '</tr>';

	}
	/** Izriše vrstico z opisnimi
	 *
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	static function displayDescriptivesSpremenljivkaRow($spid,$spremenljivka,$show_enota,$_sequence = null) {
		global $lang;
		$cssBack = " anl_bck_desc_1";
		if ($_sequence != null) {
			$_desc = self::$_DESCRIPTIVES[$_sequence];
		}
		
		# pokličemo objekt SpremenljivkaSkala
		$objectSkala = new SpremenljivkaSkala($spremenljivka['spr_id']);
		
		echo '<tr>';
		echo '<td class="anl_bck anl_ac anl_br anl_bt link_no_decoration">';
		self::showIcons($spid,$spremenljivka,'desc');
		echo '</td>';
		echo '<td class="'.$cssBack.' anl_ac anl_br anl_bt">';
		self::showVariable($spid,$spremenljivka['variable']);
		echo '</td>';
		echo '<td class="' . $cssBack . ' anl_br anl_bt">';
		echo ($spremenljivka['naslov']) . '</td>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="' . $cssBack . ' anl_ac anl_br anl_bt" title="'.$_tip.'">'.
					self::getSpremenljivkaLegenda($spremenljivka,'tip')
					.'</td>';
			echo '<td class="' . $cssBack . ' anl_ac anl_br anl_bt" title="'.$_tip.'">'.(!$show_enota ? $_tip : '&nbsp').'</td>';
			echo '<td class="' . $cssBack . ' anl_ac anl_br anl_bt" title="'.$_oblika.'">'.(!$show_enota ? $_oblika : '&nbsp;'). '</td>';
		}
		#veljavno
		echo '<td class="' . $cssBack . ' anl_br anl_ac anl_bt">'.(!$show_enota ? (int)$_desc['validCnt'] : '&nbsp;') .'</td>';
		#ustrezno
		echo '<td class="' . $cssBack . ' anl_br anl_ac anl_bt">'.(!$show_enota ? (int)$_desc['allCnt'] : '&nbsp;').'</td>';

		echo '<td class="' . $cssBack . ' anl_br anl_ac anl_bt">';
		if (isset($_desc['avg']) && (int)$objectSkala->getSkala() !== 1) {
			echo self::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		}
		echo '</td>';
		echo '<td class="' . $cssBack . ' anl_br anl_ac anl_bt">';
		if (isset($_desc['div']) && (int)$objectSkala->getSkala() !== 1) {
			echo self::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
		}
		echo '</td>';
		echo '<td class="' . $cssBack . ' anl_br anl_ac anl_bt">'.((int)$objectSkala->getSkala() !== 1 ? $_desc['min'] : '').'</td>';
		echo '<td class="' . $cssBack . ' anl_ac anl_bt">'.((int)$objectSkala->getSkala() !== 1 ? $_desc['max'] : '').'</td>';

		echo '</tr>';

	}
	/** Izrišemo fekvence
	 *
	 */
	static function displayFrequency($_spid = null) {
		global $site_path, $lang;
		# preberemo header
		if (self::$headFileName !== null ) {
			#preberemo HEADERS iz datoteke
			self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));

		# odstranimo sistemske variable tipa email, ime, priimek, geslo
		self::removeSystemVariables();
			
		# polovimo frekvence
		self::getFrequencys();
		$vars_count = count(self::$_FILTRED_VARIABLES);
		$line_break = '';
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			&& in_array($spremenljivka['tip'], self::$_FILTRED_TYPES ))
					&& (!isset($_spid) || (isset($_spid) && $_spid == $spid))) {
			# ali imamo sfiltrirano spremenljivko
			if ($vars_count == 0 || ($vars_count > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ) ) {
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
				case 25: # kvota
                                case 26: # lokacija
					self::frequencyVertical($spid);
					break;
				case 5:
					# nagovor
					self::sumNagovor($spid,'freq');
					break;
						
		}

		}

		} // end if $spremenljivka['tip'] != 'm'
		} // end foreach self::$_HEADERS

		// Izrisemo ikone na dnu
		if ( (!isset($_spid) || $_spid == null) && (count(self::$_LOOPS) == 0 || self::$_CURRENT_LOOP['cnt'] == count(self::$_LOOPS)) && ($_GET['m'] != 'analysis_creport') )
			self::displayBottomSettings('freq');
		
		} // end if else ($_headFileName == null)
	}

 /** Izriše frekvence v vertikalni obliki
  *
  * @param unknown_type $spid
  */
	static function frequencyVertical($spid) {
		global $lang;

		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
					}
			}
		}

		# odstranimo še možne nepotrebne zapise za multigride
		if ($spremenljivka['tip'] == 6 || $spremenljivka['tip'] == 16 ) {
			$allGrids = count($spremenljivka['grids']); 
			if ($allGrids > 0) {
				foreach ($spremenljivka['grids'] AS $gid => $grid) {
					$cntValidInGrid = 0;
					# dodamo dodatne vrstice z labelami grida
					if (count($grid['variables']) > 0 ) {
						foreach ($grid['variables'] AS $vid => $variable ){
							$_sequence = $variable['sequence'];	# id kolone z podatki
							$cntValidInGrid+= (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
						}
					}
					# preverjamo ali lahko prikazujemo podkategorije
					if ($allGrids < AUTO_HIDE_ZERRO_VALUE || (int)$cntValidInGrid > 0) {
						$gidsCanShow[$gid] = true;
					} else {
						$gidsCanShow[$gid] = false;
					}
				}
			} 
		}
		if (self::$hideEmptyValue == true || (is_countable(self::$_FREQUENCYS[$_sequence]['valid']) && count(self::$_FREQUENCYS[$_sequence]['valid']) > AUTO_HIDE_ZERRO_VALUE)) {
			foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $key => $valid) {
				if ((int)$valid['cnt'] == 0) {
					unset (self::$_FREQUENCYS[$_sequence]['valid'][$key]);
				}
			}
		}
		
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (self::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;

		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
		echo '<div id="freq_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_analiza_holder">';
		self::displaySpremenljivkaIcons($spid);

		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_br tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		self::showVariable($spid,$spremenljivka['variable']);
		echo '</td>';
		#odgovori
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA ? 7 : 5).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if($spremenljivka['tip'] == 2){
			echo ' <span class="anl_variabla_info">('.$lang['srv_info_checkbox'].')</span>';
		}
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
				
			if (!$inline_legenda) {
				echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>'; # .' / '.$_oblika
			}
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo'</td>';
		echo '</tr>';
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';
		self::showIcons($spid,$spremenljivka,'freq');
		echo '</td>';
		#odgovori

		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">'.$lang['srv_analiza_frekvence_titleAnswers'] . '</td>';
		if (self::$_SHOW_LEGENDA && $inline_legenda){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		echo '</tr>';
		// konec naslovne vrstice
		// zeleno vrstico prikažemo samo skupaj z legendo
		if (self::$_SHOW_LEGENDA && $inline_legenda && in_array($spremenljivka['tip'],array(1,4,8)) ) {
			$css_bck = 'anl_bck_0_0 ';
			echo '<tr >';
			echo '<td class="anl_bl anl_bb anl_br anl_al '.$css_bck.'link_no_decoration">&nbsp;</td>';
			echo '<td class="anl_bb anl_br anl_al '.$css_bck.'">&nbsp;</td>';

			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq anl_w90">'.$_tip.'</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq anl_w90">'.$_oblika.'</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
			if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
				echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
			}
			echo '<td class="anl_bb '.$css_bck.' anl_w70">&nbsp;</td>';
			echo '</tr>';
		}
		$_answersOther = array();

		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

		# izpišemo vlejavne odgovore
		$_current_grid = null;
		if (count($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
				
			# indikator da smo na prvi variabli
			$first_variable = true;
			
			# dodamo še kontrolo za prikaz mgridov in mcheckov za več kot 20 vrednosti
			if ((!is_array($gidsCanShow) && !isset($gidsCanShow[$gid])) 
					|| (is_array($gidsCanShow) && isset($gidsCanShow[$gid]) && $gidsCanShow[$gid]== true))  
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){

				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];

				if (($variable['text'] != true && $variable['other'] != true)
						|| (in_array($spremenljivka['tip'],array(4,8,21,22,25,26,27)))){
					# dodamo ime podvariable
					//if ($_variables_count > 1 && in_array($spremenljivka['tip'],array(2,6,7,16,17,18,19,20,21))) {
					if ($inline_legenda) {
						# ali rišemo dvojno črto med grupami
						if ( $_current_grid != $gid && $_current_grid !== null && $spremenljivka['tip'] != 6&& $spremenljivka['tip'] != 16) {
						$options['doubleTop'] = true;
					} else {
						$options['doubleTop'] = false;
					}
					if ($first_variable == true && $spremenljivka['tip'] == 16) {
						if ($_current_grid !== null) {
							$options['doubleTop'] = true;
						}
						self::outputSubGridVertical($spremenljivka,$variable,$grid,$spid,$options);
						$options['doubleTop'] = false;
					}
					$_current_grid = $gid;
					self::outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$options);
					}
					$counter = 0;
					$_kumulativa = 0;
						
						
					#po potrebi posortiramo podatke
					if ($spremenljivka['tip'] == 7 && is_array(self::$_FREQUENCYS[$_sequence]['valid'])) {
						ksort(self::$_FREQUENCYS[$_sequence]['valid']);
					}
					//self::$_FREQUENCYS[$_sequence]
					if (count(self::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						# tekstovne odgovore posortiramo kronološko
						if ($spremenljivka['tip'] == 21 || $spremenljivka['tip'] == 4) {
						$_valid_answers = self :: sortTextValidAnswers($spid,$variable,self::$_FREQUENCYS[$_sequence]['valid']);
					} else {
						$_valid_answers = self::$_FREQUENCYS[$_sequence]['valid'];
					}
					foreach ($_valid_answers AS $vkey => $vAnswer) {
						if ($counter < $num_show_records) {
							if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
								if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21,26,27))) { // text, number, datum, mtext, mnumber, text*, lokacija,heatmap
								$options['isTextAnswer'] = true;
							} else {
								$options['isTextAnswer'] = false;
							}
							$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
						}
					}
					# izpišemo sumo veljavnih
					$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(self::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (self::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
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
				$first_variable = false;
			}
		}

		echo '</table>';
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}
		echo '</div>';
		echo '<br />';
	}

	static function outputSubGridVertical($spremenljivka,$variable,$grid,$spid,$_options = array()) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
				'doubleTop'	=>false,		# ali imamo novo grupa in nardimo dvojni rob
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$css_bck = 'anl_bck_freq_2 ';
		echo '<tr'.($options['doubleTop'] ? ' class="anl_double_bt"' : '').'>';
		echo '<td class="anl_bl anl_bb anl_br anl_ac '.$css_bck.'anl_variabla_sub">';
		echo $grid['variable'];
		#echo $variable['variable'];
		echo '</td>';
		echo '<td class="anl_bb anl_br anl_al '.$css_bck.'">';
		// echo $grid['naslov'] . ' - ' .$variable['naslov'];
		echo $grid['naslov'];
		#echo $variable['naslov'];
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq">'.$_tip.'</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq">'.$_oblika.'</td>';
		}
		echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		}
		echo '<td class="anl_bb '.$css_bck.' anl_w70">&nbsp;</td>';
		echo '</tr>';
	}
	static function outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$_options = array()) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
				'doubleTop'	=>false,		# ali imamo novo grupa in nardimo dvojni rob
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$css_bck = 'anl_bck_freq_2 ';
		echo '<tr'.($options['doubleTop'] ? ' class="anl_double_bt"' : '').'>';
		echo '<td class="anl_bl anl_bb anl_br anl_ac '.$css_bck.'anl_variabla_sub">';
		echo $variable['variable'];
		echo '</td>';
		echo '<td class="anl_bb anl_br anl_al '.$css_bck.'">';
		// echo $grid['naslov'] . ' - ' .$variable['naslov'];
		echo $variable['naslov'];
		echo ($spremenljivka['enota'] == 1) ? ' - '.$variable['naslov2'] : '';
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq">'.$_tip.'</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq">'.$_oblika.'</td>';
		}
		echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		}
		echo '<td class="anl_bb '.$css_bck.' anl_w70">&nbsp;</td>';
		echo '</tr>';
	}

	/** izrišemo arhive analiz
	 *
	 */
	static function displayAnalysisArchive() {
		global $lang;

        
		if (self::$dataFileStatus != FILE_STATUS_NO_DATA && self::$dataFileStatus != -3 && self::$noHeader != true) {
			echo '<div id="div_archive_content">';
			SurveyAnalysisArchive :: Init(self::$sid);
			SurveyAnalysisArchive :: ListArchive();
			echo '</div>';
			
			echo '<br class="clr" />';
		}
		else{
			echo '<div id="div_archive_content">';
			
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_archive_analysis'].'</legend>';
			Common::noDataAlert();
			echo '</fieldset>';
			
			echo '</div>';
			
			echo '<br class="clr" />';
		}
	}

	/** Izrišemo sumarnik
	 *
	 */
	static function displaySums($_spid = null) {
		global $site_path;
		# preberemo header
		if (self::$headFileName === null ) {
			//			die ('<div>NAPAKA!!! Manjkajo datoteke s podatki. <a href="#" onClick="createCollectData();return false;">Kreiraj datoteke s podatki!</a></div>');
		} else {
			#preberemo HEADERS iz datoteke
			if (self::$headFileName == null) {
			echo "<br><b>Napaka</b>";
			die();
		}
		self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));
		# odstranimo sistemske variable tipa email, ime, priimek, geslo
		self::removeSystemVariables();
		#print_r("<pre>");
		#print_r(self::$_HEADERS);
		#print_r("</pre>");
		# polovimo frekvence
		self::getFrequencys();
		$vars_count = count(self::$_FILTRED_VARIABLES);
		$line_break = '';

		if (!empty(self::$_HEADERS))
			foreach (self::$_HEADERS AS $spid => $spremenljivka) {
				# preverjamo ali je meta
				if (
					($spremenljivka['tip'] != 'm'
					&& in_array($spremenljivka['tip'], self::$_FILTRED_TYPES )
					)
					&& (!isset($_spid) || (isset($_spid) && $_spid == $spid)) 
				)
				{
					# ali imamo sfiltrirano spremenljivko
					if ($vars_count == 0 || ($vars_count > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ) ) 
					{
						echo $line_break;
						#print_r($spremenljivka['tip']);
						# 	prikazujemo v odvisnosti od kategorije spremenljivke

				switch ($spremenljivka['tip']) {
					case 1:
						# radio - prikjaže navpično
						self::sumVertical($spid,'sums');

						break;
					case 2:
						#checkbox  če je dihotomna:
						//self::sumHorizontalCheckbox($spid);
						self::sumVerticalCheckbox($spid,'sums');
						break;
					case 3:
						# dropdown - prikjaže navpično
						self::sumVertical($spid,'sums');
						break;

					case 6:
						
						if ($spremenljivka['enota'] != 3) {
							# multigrid
							self::sumHorizontal($spid,'sums');
						} else {
							#imamo dvojni mgrid
							self::sumDoubleHorizontal($spid,'sums*');
						}
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
						# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
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
						# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
						self::sumNumberVertical($spid,'sums');
						break;
					case 20:

						# Če je v gridu le ene variabla naj bo default prikazan f* in ne SUMA
						if ($spremenljivka['grids'][0]['cnt_vars'] == 1 ) {
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumMultiNumberVertical($spid,'sums');
								
						} else {
								
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumMultiNumber($spid,'sums');
						}
						break;
					case 22:
					case 25:
						# kalkulacija
						self::sumNumberVertical($spid,'sums');
						break;
					case 26:
						# lokacija
						self::sumMultiText($spid,'sums');
						break;
					case 27:
						# heatmap
						self::sumMultiTextHeatMap($spid,'sums',true, true);
						
						break;
					case 5:
						# nagovor
						self::sumNagovor($spid,'sums');
						break;
					default:
						print_r("TODO: Sums for type:".$spremenljivka['tip']);
						break;
				}

			}

		} // end if $spremenljivka['tip'] != 'm'
		} // end foreach self::$_HEADERS
			
		// Izrisemo ikone na dnu
		if ( (!isset($_spid) || $_spid == null) && (!is_countable(self::$_LOOPS) || count(self::$_LOOPS) == 0 || self::$_CURRENT_LOOP['cnt'] == count(self::$_LOOPS)) && ($_GET['m'] != 'analysis_creport') )
			self::displayBottomSettings('sums');
		
		} // end if else ($_headFileName == null)
	}

	/** Izrišemo nov sumarnik za določene spremenljivke
	 *
	 */
	static function displaySumsNew($_spid = null) {
		global $site_path;
		# preberemo header
		if (self::$headFileName === null ) {
			//			die ('<div>NAPAKA!!! Manjkajo datoteke s podatki. <a href="#" onClick="createCollectData();return false;">Kreiraj datoteke s podatki!</a></div>');
		} else {

			#preberemo HEADERS iz datoteke
			self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));

			# odstranimo sistemske variable tipa email, ime, priimek, geslo
			self::removeSystemVariables();
				
			# polovimo frekvence
			self::getFrequencys();
			$vars_count = count(self::$_FILTRED_VARIABLES);
			$line_break = '';
			foreach (self::$_HEADERS AS $spid => $spremenljivka) {
				# preverjamo ali je meta
				if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], self::$_FILTRED_TYPES ))
						&& (!isset($_spid) || (isset($_spid) && $_spid == $spid))) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ) ) {
				echo $line_break;
				if (self :: $show_spid_div == true) {
					echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
				}
				
				self::displaySpremenljivkaIcons($spid);
					
				# 	prikazujemo v odvisnosti od kategorije spremenljivke
				switch ($spremenljivka['tip']) {
					case 1:
						# radio - prikjaže navpično
						self::sumHorizontal($spid,'sums*');
						break;
					case 2:
						#checkbox  če je dihotomna:
						#self::sumVerticalCheckbox($spid,'sums*');
						self::sumHorizontalCheckbox($spid,'sums*');
						break;
					case 3:
						# dropdown - prikjaže navpično
						self::sumVertical($spid,'sums*');
						break;
					case 6:
						# multigrid
						self::sumHorizontal($spid,'sums');
						/*
							if ($spremenljivka['enota'] != 3) {
						# multigrid
						self::sumHorizontal($spid,'sums');
						} else {
						#imamo dvojni mgrid
						self::sumDoubleHorizontal($spid,'sums*');
						}
						*/
						break;
					case 16:
						#multicheckbox če je dihotomna:
						self::sumVerticalCheckbox($spid,'sums*');
						break;
					case 17:
						#razvrščanje  če je ordinalna
						self::sumHorizontal($spid,'sums*');
						break;
					case 4:	# text
					case 8:	# datum
					case 19: # multitext
					case 21: # besedilo*
						# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
						self::sumTextVertical($spid,'sums*');
						break;
					case 7:
					case 18:
						# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
						self::sumNumberVertical($spid,'sums*');
						break;
					case 20:
						self::sumMultiNumberVertical($spid,'sums*');
						/*
							# Če je v gridu le ene variabla naj bo default prikazan f* in ne SUMA
						if ($spremenljivka['grids'][0]['cnt_vars'] == 1) {
						# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
						self::sumMultiNumberVertical($spid,'sums');
							
						} else {
						# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
						self::sumMultiNumber($spid,'sums');
						}
						*/
						break;
					case 26:
						# lokacija
						self::sumMultiText($spid,'sums');
						break;
					case 27:
						# heatmap
						self::sumMultiTextHeatMap($spid,'sums',true, true);
						break;
					case 5:
						# nagovor
						self::sumNagovor($spid,'sums*');
						break;
							
				}
				if (self :: $show_spid_div == true) {
					echo '</div>'; // id="sum_'.$keyGrupe.'">';
				}
				$line_break = "<br/>";

			}

			} // end if $spremenljivka['tip'] != 'm'
			} // end foreach self::$_HEADERS
		} // end if else ($_headFileName == null)
	}


	/** Izriše sumarnik v vertikalni obliki
	 *
	 * @param unknown_type $spid
	 */
	static function sumVertical($spid,$_from) {
		global $lang;

		$spremenljivka = self::$_HEADERS[$spid];
		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		$inline_legenda = false;

		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
			
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';		
		}
		
		self::displaySpremenljivkaIcons($spid);

		
		if (self::$hideEmptyValue == true || (is_countable(self::$_FREQUENCYS[$_sequence]['valid']) && count(self::$_FREQUENCYS[$_sequence]['valid']) > AUTO_HIDE_ZERRO_VALUE)) {
			foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $key => $valid) {
				if ((int)$valid['cnt'] == 0) {
					unset (self::$_FREQUENCYS[$_sequence]['valid'][$key]);
				}
			}
		}
		
		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_br tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		#odgovori
		$show_valid_percent = (self::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA ? (4+((int)$inline_legenda * 2)+$show_valid_percent) : (4+$show_valid_percent)).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';

		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
		}
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';

		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		#odgovori

		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">'.$lang['srv_analiza_frekvence_titleAnswers'] . '</td>';
		if (self::$_SHOW_LEGENDA && $inline_legenda){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		echo '</tr>';
		// konec naslovne vrstice
		// zeleno vrstico prikažemo samo skupaj z legendo
		if (self::$_SHOW_LEGENDA && false) {
			$css_bck = 'anl_bck_0_0 ';
			echo '<tr >';
			echo '<td class="anl_bl anl_bb anl_br anl_al '.$css_bck.'link_no_decoration">&nbsp;</td>';

			echo '<td class="anl_bb anl_br anl_al '.$css_bck.'">&nbsp;</td>';

			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq anl_w90">'.$_tip.'</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_ac anl_legend anl_legenda_freq anl_w90">'.$_oblika.'</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
			echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
			if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
				echo '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
			}
			echo '<td class="anl_bb '.$css_bck.' anl_w70">&nbsp;</td>';
			echo '</tr>';
		}
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
				$counter = 0;
				$_kumulativa = 0;
				//self::$_FREQUENCYS[$_sequence]
				if (count(self::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
						// za povprečje
						$xi = (int)$vkey;
						$fi = (int)$vAnswer['cnt'];

                        $sum_xi_fi += $xi * $fi;
                        $N += $fi;
                        
						if ($counter < $num_show_records) {
							if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
						}
						# za poznejše računannje odklona
						$_tmp_for_div[] = array('xi'=>$xi, 'fi'=>$fi, 'sequence'=>$_sequence);
					}
					# izpišemo sumo veljavnih
					$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);

				}
				if (count(self::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
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
			$xi = (int)$_tmp_div_data['xi'];
			$fi =  (int)$_tmp_div_data['fi'];
				
			$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
		}
		$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

		# izpišemo še odklon in povprečje
		if ($show_valid_percent == 1 && self::$_HEADERS[$spid]['skala'] != 1) {
			$css_bck = 'anl_bck';
			echo '<tr >';
			echo '<td class="cll_clps" style="font-size: 1px; height:2px; line-height:3px; border-right: 1px solid white;" colspan="'.(self::$_SHOW_LEGENDA ? 6+((int)$inline_legenda*2) : 6+((int)$inline_legenda*2)).'">&nbsp;</td>';
			echo '</tr>';
			echo '<tr >';
			echo '<td class="anl_br" colspan="'.(self::$_SHOW_LEGENDA ? 2+((int)$inline_legenda*2) : 2+((int)$inline_legenda*2)).'">&nbsp;</td>';
			echo '<td class="anl_bb anl_bt anl_br anl_p5 anl_ac anl_variabla_line '.$css_bck.'">'.$lang['srv_analiza_opisne_povprecje'].'</td>';
			echo '<td class="anl_bb anl_bt anl_br anl_ac '.$css_bck.'">'. self::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'').'</td>';
			echo '<td class="anl_bb anl_bt anl_br anl_p5 anl_ac anl_variabla_line '.$css_bck.'">'.$lang['srv_analiza_opisne_odklon'].'</td>';
			echo '<td class="anl_bb anl_bt anl_ac '.$css_bck.'">'.self::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'').'</td>';
			echo '</tr>';
		}
		echo '</table>';
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}
		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** Izriše sumarnik v horizontalni obliki za multi checbox
	 *
	 * @param unknown_type $spid - spremenljivka ID
	 */
	static function sumMultiHorizontalCheckbox($spid,$_from) {
		global $lang;

		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		$gidsCanShow=array();
		$allGrids = count($spremenljivka['grids']); 
		if ($allGrids > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				
				$cntValidInGrid = 0;
				# dodamo dodatne vrstice z labelami grida
				if (count($grid['variables']) > 0 ) {
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						#$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
						$only_valid += (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
						$cntValidInGrid+= (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					}
				}
				# preverjamo ali lahko prikazujemo podkategorije
				if ($allGrids < AUTO_HIDE_ZERRO_VALUE || (int)$cntValidInGrid > 0) {
					$gidsCanShow[$gid] = true;
				} else {
					$gidsCanShow[$gid] = false;
				}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = self::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-self::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		# tekst vprašanja

		$css_hide_enote = isset($_POST['navedbe']) && $_POST['navedbe'] == '1' ? ' displayNone' : '';
		$css_hide_navedbe = isset($_POST['navedbe']) && $_POST['navedbe'] == '1' ? '' : ' displayNone';
			
		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';	
		}
		
		self::displaySpremenljivkaIcons($spid);
	
		# odgovori
		echo '<div id="div_navedbe_1_'.$spid.'" class="'.$css_hide_enote.'">';
		echo '<table class="anl_tbl anl_ba tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_bck_desc_1">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_al anl_bck anl_bb anl_bck_desc_1" colspan="'. ($_clmn_cnt+(self::$_SHOW_LEGENDA ? 5 : 3)) .'">';
		echo '<span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type">&nbsp;&nbsp;<span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		//		echo '<span name="span_show_navedbe_1_'.$spid.'" class="span_navedbe"><a href="javascript:show_navedbe(\''.$spid.'\',\'3\');">&nbsp;(<span class="blue">'.$lang['srv_analiza_opisne_answers'].'&nbsp;</span>/<span class="blue">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '<span name="span_show_navedbe_2_'.$spid.'" class="span_navedbe'.$css_hide_enote.'"><a href="javascript:show_navedbe(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue" title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '<span name="span_show_navedbe_3_'.$spid.'" class="span_navedbe'.$css_hide_navedbe.'"><a href="javascript:show_navedbe(\''.$spid.'\',\'1\');">&nbsp;(<span title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue" title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb">';
		self::showIcons($spid,$spremenljivka,$_from, array('navedbe'=>false));
		echo '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line">';
		echo $lang['srv_analiza_opisne_subquestion'];
		echo '</td>';
		if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line" colspan="'.($_clmn_cnt).'">';
		echo $lang['srv_analiza_opisne_answers'].'&nbsp;';
		echo '<span id="img_analysis_f_p_1_'.$spid.'" class="img_analysis_f_p"><a href="javascript:show_single_percent(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue">f&nbsp;</span>/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '<span id="img_analysis_f_1_'.$spid.'" class="img_analysis_f displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'1\');">&nbsp;(<span class="blue">f&nbsp;</span>/&nbsp;%)</a></span>';
		echo '<span id="img_analysis_p_1_'.$spid.'" class="img_analysis_p displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'0\');">&nbsp;(f&nbsp;/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_valid'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_num_units'].'</td>';
		echo '</tr>';

		$bck_css = ' anl_bck_0_0';
		$_variables = self::$_HEADERS[$spid]['grids'][$gid]['variables'];
		echo '<tr>';
		echo '<td class="anl_bl anl_br anl_bb'.$bck_css .'">&nbsp;</td>';
		echo '<td class="anl_bl anl_br anl_bb'.$bck_css .'">&nbsp;</td>';
		if (self::$_SHOW_LEGENDA) {

			$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			echo '<td class="anl_bb anl_br anl_ac anl_legend anl_legenda_freq'.$bck_css.'">&nbsp;</td>'; //'.$_tip.'
			echo '<td class="anl_bb anl_br anl_ac anl_legend anl_legenda_freq'.$bck_css.'">&nbsp;</td>'; // '.$_oblika.'
		}
		if (count($_variables) > 0) {
			foreach ($_variables AS $vkey => $variable) {
				if ($variable['other'] != true) {
					echo '<td class="anl_bb anl_ac anl_dash_br'.$bck_css.'">' . $variable['naslov'].' ('.$variable['gr_id']. ') </td>';
				}
			}
		}
		//echo '<td class="anl_bb anl_ac anl_br red'.$bck_css.'">' . $lang['srv_anl_suma1'] . '</td>';
		echo '<td class="anl_bb anl_br anl_bl anl_ac'.$bck_css.'">'. $_valid_cnt .'</td>';
		echo '<td class="anl_bb anl_bl anl_br anl_ac'.$bck_css.'">'.$_approp_cnt. '</td>';
		echo '</tr>';
		foreach (self::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			if ($gidsCanShow[$gid]) {
				$_cnt = 0;
				# vodoravna vrstice s podatki
				$css_back = ' anl_bck_desc_2';
				echo '<tr>';
				echo '<td class="anl_br anl_bt anl_ac anl_variabla_sub'.$css_back.'">'.$grids['variable'].'</td>';
				echo '<td class="anl_br anl_bt'.$css_back.'">'.$grids['naslov'].'</td>';
				if (self::$_SHOW_LEGENDA) {
					$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
					$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
				
					echo '<td class="anl_br anl_bt'.$css_back.'">'.$_tip.'</td>';
					echo '<td class="anl_br anl_bt'.$css_back.'">'.$_oblika.'</td>';
				}
				
				$_arguments = 0;
				
				$_max_appropriate = 0;
				$_max_cnt = 0;
				// prikaz frekvenc
				if (count($grids['variables']) > 0)
					foreach ($grids['variables'] AS $vkey => $variable) {
					$_sequence = $variable['sequence'];
					$_valid = self::$_FREQUENCYS[$_sequence]['validCnt'];
					$_cnt = self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$_arguments += $_cnt;
				
					$_max_appropriate = max($_max_appropriate, (int)self::$_FREQUENCYS[$_sequence]['allCnt']);
					$_max_cnt = max ($_max_cnt, ((int)(self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)self::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
					if ($variable['other'] == true) {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
					}
				
					if ($variable['other'] != true) {
						echo '<td class="anl_bb anl_dash_br anl_ac cll_clps '.$css_back.'">';
						echo '<table class="fullWidth anl_ac tbl_clps">';
						echo '<tr id="'.$spid.'_'.$_sequence.'" name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
						echo '<td class="anl_ac' . (self::$enableInspect == true && (int)$_cnt > 0 ? ' mc_inspect' : '').'"'
						. (self::$enableInspect == true && (int)$_cnt > 0 ? ' vkey="1"' : '')
						.'" style="padding:5px 0px;">'.$_cnt.'</td>';
						echo '</tr>';
						echo '<tr name="single_sums_percent_'.$spid.'">';
						echo '<td style="padding:5px 0px;">';
				
						$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;
						echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
						echo '</td>';
						echo '</tr></table>';
				
						echo '</td>';
					}
				}
				# veljavno
				echo '<td class="anl_bt anl_ac anl_br anl_bl red'.$css_back.'">'.$_max_cnt.'</td>';
				#ustrezno
				echo '<td class="anl_bt anl_ac anl_br'.$css_back.'">'.$_max_appropriate.'</td>';
					
				echo '</tr>';					
			} 
		}
		echo '</table>';
		echo '</div>';

		# navedbe
		echo '<div id="div_navedbe_2_'.$spid.'" class="div_navedbe'.$css_hide_navedbe.'">';
		echo '<table class="anl_tbl anl_ba tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_bck_desc_1">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_al anl_bck anl_bb anl_bck_desc_1" colspan="'. ( $_clmn_cnt +(self::$_SHOW_LEGENDA ? 4 : 2)) .'">';
		echo '<span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type">&nbsp;&nbsp;<span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		//		echo '<span name="span_show_navedbe_1_'.$spid.'" class="span_navedbe"><a href="javascript:show_navedbe(\''.$spid.'\',\'3\');">&nbsp;(<span class="blue">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '<span name="span_show_navedbe_2_'.$spid.'" class="span_navedbe'.$css_hide_enote.'"><a href="javascript:show_navedbe(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue" title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '<span name="span_show_navedbe_3_'.$spid.'" class="span_navedbe'.$css_hide_navedbe.'"><a href="javascript:show_navedbe(\''.$spid.'\',\'1\');">&nbsp;(<span title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue" title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb">';
		self::showIcons($spid,$spremenljivka,$_from, array('navedbe'=>true));
		echo '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line">';
		echo $lang['srv_analiza_opisne_subquestion'];
		echo '</td>';
		if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line" colspan="'.($_clmn_cnt+1).'">';
		echo $lang['srv_analiza_opisne_arguments'].'&nbsp;';

		echo '<span id="img_analysis_f_p_2_'.$spid.'" class="img_analysis_f_p "><a href="javascript:show_single_percent(\''.$spid.'\',\'2\');">&nbsp(<span class="blue">f&nbsp;</span>/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '<span id="img_analysis_f_2_'.$spid.'" class="img_analysis_f displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'1\');">&nbsp(<span class="blue">f&nbsp;</span>/&nbsp;%)</a></span>';
		echo '<span id="img_analysis_p_2_'.$spid.'" class="img_analysis_p displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'0\');">&nbsp(f&nbsp;/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '</td>';
		echo '</tr>';

		$bck_css = ' anl_bck_0_0';
		$_variables = self::$_HEADERS[$spid]['grids'][$gid]['variables'];
		echo '<tr>';
		echo '<td class="anl_bl anl_br anl_bb'.$bck_css .'">&nbsp;</td>';
		echo '<td class="anl_bl anl_br anl_bb'.$bck_css .'">&nbsp;</td>';
		if (self::$_SHOW_LEGENDA) {

			$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			echo '<td class="anl_bb anl_br anl_ac anl_legend anl_legenda_freq'.$bck_css.'">&nbsp;</td>'; //'.$_tip.'
			echo '<td class="anl_bb anl_br anl_ac anl_legend anl_legenda_freq'.$bck_css.'">&nbsp;</td>'; // '.$_oblika.'
		}
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				echo '<td class="anl_bb anl_ac anl_dash_br'.$bck_css.'">' . $variable['naslov'].' ('.$variable['gr_id']. ') </td>';
			}
		}
		echo '<td class="anl_bb anl_ac anl_dash_br red'.$bck_css.'">' . $lang['srv_anl_suma1'] . '</td>';
		echo '</tr>';
		foreach (self::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			$_cnt = 0;
			# vodoravna vrstice s podatki
			$css_back = ' anl_bck_desc_2';
			echo '<tr>';

			echo '<td class="anl_br anl_bt'.$css_back.'">'.$grids['variable'].'</td>';
			echo '<td class="anl_br anl_bt'.$css_back.'">'.$grids['naslov'].'</td>';
			if (self::$_SHOW_LEGENDA) {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
				echo '<td class="anl_br anl_bt'.$css_back.'">'.$_tip.'</td>';
				echo '<td class="anl_br anl_bt'.$css_back.'">'.$_oblika.'</td>';
			}

			$_arguments = 0;

			$_max_appropriate = 0;
			$_max_cnt = 0;
			// prikaz frekvenc
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = self::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;

				$_max_appropriate = max($_max_appropriate, (int)self::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)self::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));

				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}

				if ($variable['other'] != true) {
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;
				}
			}
			foreach ($grids['variables'] AS $vkey => $variable) {
				if ($variable['other'] != true) {
					$_sequence = $variable['sequence'];
					$_cnt = self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					echo '<td class="anl_bb anl_dash_br anl_ac cll_clps '.$css_back.'">';

					echo '<table class="fullWidth anl_ac tbl_clps">';
					echo '<tr id="'.$spid.'_'.$_sequence.'" name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
					echo '<td class="anl_ac' . (self::$enableInspect == true && (int)$_cnt > 0 ? ' mc_inspect' : '').'"'
					. (self::$enableInspect == true && (int)$_cnt > 0 ? ' vkey="1"' : '')
					.' style="padding:5px 0px;">'.$_cnt.'</td>';
					echo '</tr>';
					echo '<tr name="single_sums_percent_'.$spid.'">';
					echo '<td style="padding:5px 0px;">';
					$_percent = ($_arguments > 0 ) ? $_cnt * 100 / $_arguments : 0;
					echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
					echo '</td>';
					echo '</tr></table>';

					echo '</td>';
				}
			}
			echo '<td class="anl_bb anl_ac anl_dash_br cll_clps '.$css_back.'">';
			echo '<table class="fullWidth anl_ac tbl_clps">';
			echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
			echo '<td class="anl_ac" style="padding:5px 0px;">'.$_arguments.'</td>';
			echo '</tr>';
			echo '<tr name="single_sums_percent_'.$spid.'">';
			echo '<td style="padding:5px 0px;">';
			$_percent = ($_arguments > 0 ) ? $_arguments * 100 / $_arguments : 0;
			echo self::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
			echo '</td>';
			echo '</tr></table>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

		echo '</div>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}
		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** Izriše sumarnik v horizontalni obliki za checbox
	 *
	 * @param unknown_type $spid - spremenljivka ID
	 */
	static function sumHorizontalCheckbox($spid,$_from) {
		global $lang;
		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}

		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = self::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-self::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		foreach (self::$_HEADERS[$spid]['grids'][$gid]['variables'] AS $vid => $variable) {
			$_sequence = $variable['sequence'];
				
			$_valid_cnt = max($_valid_cnt, self::$_FREQUENCYS[$_sequence]['validCnt']);
			$_approp_cnt = max($_approp_cnt, self::$_FREQUENCYS[$_sequence]['allCnt']);
			if ($variable['other'] == true) {
				$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
			}
		}
		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);
		
		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_bl tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_bck_desc_1">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_al anl_bck anl_bb anl_bck_desc_1" colspan="'. ($_clmn_cnt+(self::$_SHOW_LEGENDA ? 4 : 2)) .'">';
		echo '<span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line" colspan="'.$_clmn_cnt.'">'.$lang['srv_analiza_opisne_answers'];
		echo '<span id="img_analysis_f_p_1_'.$spid.'" class="img_analysis_f_p"><a href="javascript:show_single_percent(\''.$spid.'\',\'2\');">&nbsp(<span class="blue">f&nbsp;</span>/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '<span id="img_analysis_f_1_'.$spid.'" class="img_analysis_f displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'1\');">&nbsp(<span class="blue">f&nbsp;</span>/&nbsp;%)</a></span>';
		echo '<span id="img_analysis_p_1_'.$spid.'" class="img_analysis_p displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'0\');">&nbsp(f&nbsp;/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_valid'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_num_units'].'</td>';
		echo '</tr>';

		$bck_css = ' anl_bck_desc_2';
		$_variables = self::$_HEADERS[$spid]['grids'][$gid]['variables'];
		echo '<tr>';
		echo '<td class="anl_bl anl_br anl_bb'.$bck_css .'">&nbsp;</td>';
		if (self::$_SHOW_LEGENDA) {

			$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			echo '<td class="anl_bb anl_br anl_ac anl_legend anl_legenda_freq'.$bck_css.'">'.$_tip.'</td>';
			echo '<td class="anl_bb anl_br anl_ac anl_legend anl_legenda_freq'.$bck_css.'">'.$_oblika.'</td>';
		}
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				echo '<td class="anl_bb anl_ac anl_dash_br'.$bck_css.'">' . $variable['naslov'] . '</td>';
			}
		}
		echo '<td class="anl_bb anl_bl anl_br anl_ac'.$bck_css.'">&nbsp;</td>';
		echo '<td class="anl_bb anl_br anl_ac'.$bck_css.'">&nbsp;</td>';
		echo '</tr>';
		# vodoravna vrstice s podatki
		echo '<tr name="single_sums_percent_cnt_'.$spid.'">';
		echo '<td class="anl_br anl_bt anl_ar anl_ita gray anl_dash_bb" colspan="'.(self::$_SHOW_LEGENDA ? 3 : 1).'">'.$lang['srv_analiza_frekvence_titleFrekvenca'].'</td>';
		// prikaz frekvenc
		foreach ($_variables AS $vkey => $variable) {

			if ($variable['other'] != true) {
				$_sequence = $variable['sequence'];
				$cnt = self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				echo '<td class="anl_p5 anl_bt anl_ac anl_dash_br anl_dash_bb">'.$cnt.'</td>';
			}
		}
		echo '<td class="anl_bt anl_bl anl_ac anl_bb">'.$_valid_cnt.'</td>';
		echo '<td class="anl_bt anl_bl anl_ac anl_bb anl_br">'.$_approp_cnt.'</td>';
		echo '</tr>';

		// dodamo še veljavne procente
		echo '<tr name="single_sums_percent_'.$spid.'" >';
		echo '<td class="anl_br anl_dash_bb anl_ar anl_ita gray" colspan="'.(self::$_SHOW_LEGENDA ? 3 : 1).'">'.$lang['srv_analiza_frekvence_titleOdstotekVeljavni'].'</td>';
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$_sequence = $variable['sequence'];
				$cnt = self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$percent = ( $_valid_cnt > 0) ? 100*$cnt / $_valid_cnt : 0;
				echo '<td class="anl_p5 anl_ac anl_dash_br anl_dash_bb">'.self::formatNumber($percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
			}
		}
		echo '<td class="anl_bl">&nbsp;</td>';
		echo '<td>&nbsp;</td>';
		echo '</tr>';
		
		// dodamo še procente
		echo '<tr name="single_sums_percent_'.$spid.'"  >';
		echo '<td class="anl_br anl_ar anl_ita gray anl_bb" colspan="'.(self::$_SHOW_LEGENDA ? 3 : 1).'">'.$lang['srv_analiza_frekvence_titleOdstotekEnote'].'</td>';
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$_sequence = $variable['sequence'];
				$cnt = self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$percent = ( $_approp_cnt > 0) ? 100*$cnt / $_approp_cnt : 0;
				echo '<td class="anl_p5 anl_ac anl_dash_br anl_bb">'.self::formatNumber($percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
			}
		}
		echo '<td class="anl_bl">&nbsp;</td>';
		echo '<td>&nbsp;</td>';
		echo '</tr>';

		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}
		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	static function sumVerticalCheckbox($spid,$_from) {
		global $lang;

		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$all_categories_cnt = 0;
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 ) {
					foreach ($grid['variables'] AS $vid => $variable ){
						$all_categories_cnt++;
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
					}
				}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		$_answersOther = array();

		$inline_legenda = count ($spremenljivka['grids']) > 1;
		if ($variable['other'] != '1' && $variable['text'] != '1') {
			$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
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
				$_valid_cnt[$gid] = max($_valid_cnt[$gid], self::$_FREQUENCYS[$_sequence]['validCnt']);
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], self::$_FREQUENCYS[$_sequence]['allCnt']);
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				$_valid[$gid][$vid] = self::$_FREQUENCYS[$_sequence]['valid'];
				$_navedbe[$gid] += self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
			}
		}
		$veljavni_percent = ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16) ? true : false;

		$css_txt = 'anl_variabla_line';

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);

		echo '<div id="div_navedbe_1_'.$spid.'">';
		echo '<table class="anl_tbl anl_bt anl_br tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA && $inline_legenda ? 7+(int)$veljavni_percent : 5+(int)$veljavni_percent).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span> <span class="anl_variabla_info">('.$lang['srv_info_checkbox'].')</span>';
		if (self::$_SHOW_LEGENDA && !$inline_legenda) {
			echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
		}
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type">&nbsp;&nbsp;<span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '<span name="span_show_navedbe_2_'.$spid.'" class="span_navedbe"><a href="javascript:show_navedbe(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue" title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '<span name="span_show_navedbe_3_'.$spid.'" class="span_navedbe displayNone"><a href="javascript:show_navedbe(\''.$spid.'\',\'1\');">&nbsp;(<span title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue" title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '</td>';
		echo '</tr>';
		$css_txt = 'anl_variabla_line';

		echo '<tr>';
		echo '<td class="anl_p5 anl_bl anl_br anl_ac anl_bck anl_bb anl_w110 '.$css_txt.'">';
		self::showIcons($spid,$spremenljivka,$_from, array('navedbe'=>false));
		echo '</td>';
		echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb '.$css_txt.'" style="width:280px">'.$lang['srv_analiza_opisne_subquestion'].'</td>';
		if (self::$_SHOW_LEGENDA && $inline_legenda) {
			echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb anl_w70 '.$css_txt.'">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb anl_w110 '.$css_txt.'">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb anl_w70 '.$css_txt.'">'.$lang['srv_analiza_opisne_frequency'].'</td>';

		echo '<td class="anl_p5 anl_ac anl_dash_br anl_bck anl_bb anl_w70 '.$css_txt.'">'.$lang['srv_analiza_opisne_valid'].'</td>';
		echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb anl_w70 '.$css_txt.'">% - '.$lang['srv_analiza_opisne_valid'].'</td>';
		
		echo '<td class="anl_p5 anl_'.($veljavni_percent?'dash_':'').'br anl_ac anl_bck anl_bb anl_w70 '.$css_txt.'">'.$lang['srv_analiza_num_units_valid'].'</td>';
		if ($veljavni_percent) {
			echo '<td class="anl_p5  anl_ac anl_bck anl_bb anl_w70 '.$css_txt.'">% - '.$lang['srv_analiza_num_units_valid'].'</td>';
		}
		echo '</tr>';

		$cssBack = "anl_bck anl_variabla_line ";
			
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] as $gid => $grid) {
			$_max_valid = 0;
			$_max_appropriate = 0;
			if (count ($grid['variables']) > 0)
				foreach ($grid['variables'] AS $vid => $variable) {
					$_sequence = $variable['sequence'];
					#po potrebi prikažemo samo tiste ki imajo vrednosti
					if (($all_categories_cnt <= AUTO_HIDE_ZERRO_VALUE) || (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] > 0 )
					if ($variable['other'] != 1) {
					
							
						# dodamo labele podvprašanja
						if ($spremenljivka['tip'] == 16 && (($vid == 0 && $gid != 0) || ($vid == 0 && $gid == 0))) {
							$cssBack = "anl_bck_desc_2 ".($vid == 0 && $gid != 0 ? 'anl_double_bt ' : '');
							echo '<tr>';
							echo '<td class="'.$cssBack.'anl_bl anl_br anl_bb anl_ac anl_variabla_sub">'.$grid['variable'].'</td>';
							echo '<td class="'.$cssBack.'anl_br anl_bb" colspan="'.(self::$_SHOW_LEGENDA && $inline_legenda ? 7+(int)$veljavni_percent : 5+(int)$veljavni_percent ).'">'.$grid['naslov'].'</td>';
							echo '</tr>';
						}
						$cssBack = "anl_bck_desc_2 ";
						echo '<tr  id="'.$spid.'_'.$_sequence.'" name="valid_row_'.$_sequence.'" vkey="1">';
						echo '<td class="anl_p5 anl_tin1 '.$cssBack.'anl_bl anl_br anl_bb anl_ac anl_variabla_sub">'.$variable['variable'].'</td>';
						echo '<td class="anl_p5 anl_tin1 '.$cssBack.'anl_br anl_bb">'.$variable['naslov'].'</td>';
						if (self::$_SHOW_LEGENDA && $inline_legenda) {
							echo '<td class="anl_p5 '.$cssBack.'anl_br anl_bb anl_ac">'.$_tip.'</td>';
							echo '<td class="anl_p5 '.$cssBack.'anl_br anl_bb anl_ac">'.$_oblika.'</td>';
						}
						echo '<td class="anl_p5 anl_bb anl_ac anl_br '.$cssBack.( self::$enableInspect == true ? ' fr_inspect' : '').'"  >';
						echo (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
						echo '</td>';
						
						$_max_appropriate = max($_max_appropriate, (int)self::$_FREQUENCYS[$_sequence]['allCnt']);
						$_max_valid = max ($_max_valid, ((int)(self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)self::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
							
						# veljavno
						echo '<td class="anl_p5 anl_dash_br anl_ac anl_bb '.$cssBack.'">';
						echo (int)(self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)self::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
						echo '</td>';
						echo '<td class="anl_p5 anl_bb anl_br anl_ac '.$cssBack.'"  >';
						$_percent = (self::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
						echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
						echo '</td>';
						#ustrezno
						echo '<td class="anl_p5 anl_'.($veljavni_percent?'dash_':'').'br anl_ac anl_bb '.$cssBack.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['allCnt'];
						echo '</td>';
						# veljavno %
						if ($veljavni_percent) {

							$valid = (int)(self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)self::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
							$valid = (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
							$_percent = ($_max_appropriate > 0 ) ? 100*$valid / $_max_appropriate : 0;
							
							echo '<td class="anl_p5 anl_br anl_ac anl_bb '.$cssBack.'">';
							echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
							echo '</td>';
						}
						echo '</tr>';

					} else {
						# drugo
					}
			}
			$cssBack = " anl_bck_2 red";
			echo '<tr>';
			echo '<td class="anl_bl anl_br anl_bb anl_al anl_ita'.$cssBack.'" >&nbsp;</td>';
			echo '<td class="anl_p5 anl_tin1 anl_br anl_bl anl_bb anl_al anl_ita'.$cssBack.'" colspan="'.(self::$_SHOW_LEGENDA && $inline_legenda ? 3 : 1).'">'.$lang['srv_anl_suma_valid'].'</td>';
			echo '<td class="anl_bb anl_ac anl_br anl_ita'.$cssBack.'"  >&nbsp;</td>'; //.$_approp_cnt[$gid].
			echo '<td class="anl_p5 anl_ac anl_dash_br anl_bb anl_ita'.$cssBack.'">'.$_max_valid.'</td>';
			echo '<td class="anl_bb anl_br'.$cssBack.'">&nbsp;</td>'; //.self::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%')

			
			echo '<td class="anl_p5 anl_'.($veljavni_percent?'dash_':'').'br  anl_ac anl_bb anl_ita'.$cssBack.'">'.$_max_appropriate.'</td>'; //$lang['srv_anl_suma_entries']
			if ($veljavni_percent) {
				$_percent = ($_max_appropriate > 0 ) ? 100*$_max_valid / $_max_appropriate : 0;
				echo '<td class="anl_p5 anl_br anl_ac anl_bb anl_ita'.$cssBack.'">';
				echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
				echo '</td>';
			}
			echo '</tr>';
				
		}
		echo '</table>';
		echo '</div>'; // div_navedbe_1_'.$spid.'

		# še navedbe
		echo '<div id="div_navedbe_2_'.$spid.'" class="div_navedbe displayNone">';
		echo '<table class="anl_tbl anl_bt anl_br tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA && $inline_legenda? 5 : 3).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA && !$inline_legenda) {
			echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
		}
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type">&nbsp;&nbsp;<span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '<span name="span_show_navedbe_2_'.$spid.'" class="span_navedbe"><a href="javascript:show_navedbe(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue" title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '<span name="span_show_navedbe_3_'.$spid.'" class="span_navedbe displayNone"><a href="javascript:show_navedbe(\''.$spid.'\',\'1\');">&nbsp;(<span title="'.$lang['srv_enote_navedbe_1'].'">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue" title="'.$lang['srv_enote_navedbe_2'].'">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		echo '</td>';
		echo '</tr>';
		$css_txt = 'anl_variabla_line';
		echo '<tr>';
		echo '<td class="anl_p5 anl_bl anl_br anl_ac anl_bck anl_bb anl_w110 '.$css_txt.'">';
		self::showIcons($spid,$spremenljivka,$_from, array('navedbe'=>true));
		echo '</td>';
		echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb '.$css_txt.'" style="width:280px">'.$lang['srv_analiza_opisne_subquestion'].'</td>';
		if (self::$_SHOW_LEGENDA && $inline_legenda) {
			echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb anl_w70 '.$css_txt.'">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb anl_w110 '.$css_txt.'">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_p5 anl_dash_br anl_ac anl_bck anl_bb '.$css_txt.'">'.$lang['srv_analiza_opisne_frequency'].'</td>';
		echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb '.$css_txt.'">%</td>';
		echo '</td>';
		echo '</tr>';

		$cssBack = "anl_bck anl_variabla_line ";
			
		if (count ($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] as $gid => $grid) {
			if (count ($grid['variables']) > 0)
				foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				#po potrebi prikažemo samo tiste ki imajo vrednosti
				if (($all_categories_cnt <= AUTO_HIDE_ZERRO_VALUE) || (int)self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] > 0 )
				if ($variable['other'] != 1) {
						
					# dodamo labele podvprašanja
					if ($spremenljivka['tip'] == 16 && (($vid == 0 && $gid != 0) || ($vid == 0 && $gid == 0))) {
						$cssBack = 'anl_bck_desc_2'.($vid == 0 && $gid != 0 ? ' anl_double_bt ' : '');
						echo '<tr>';
						echo '<td class="anl_p5 '.$cssBack.' anl_bl anl_br anl_bb anl_ac anl_variabla_sub">'.$grid['variable'].'</td>';
						echo '<td class="anl_p5 '.$cssBack.' anl_br anl_bb"'.(self::$_SHOW_LEGENDA && $inline_legenda ? ' colspan="5"' : ' colspan="3"' ).'>'.$grid['naslov'].'</td>';
						echo '</tr>';
					}
					$cssBack = "anl_bck_desc_2 ";
					echo '<tr>';
					echo '<td class="anl_p5 anl_tin1 '.$cssBack.'anl_bl anl_br anl_bb anl_ac anl_variabla_sub">'.$variable['variable'].'</td>';
					echo '<td class="anl_p5 anl_tin1 '.$cssBack.'anl_br anl_bb">'.$variable['naslov'].'</td>';
					if (self::$_SHOW_LEGENDA && $inline_legenda) {
						echo '<td class="anl_p5 '.$cssBack.'anl_br anl_bb anl_ac">'.$_tip.'</td>';
						echo '<td class="anl_p5 '.$cssBack.'anl_br anl_bb anl_ac">'.$_oblika.'</td>';
					}
						
					echo '<td class="anl_p5 anl_dash_br anl_ac anl_bb '.$cssBack.'">'. self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'].'</td>';
					echo '<td class="anl_p5 anl_br anl_ac anl_bb '.$cssBack.'">';
					$_percent = ($_navedbe[$gid] > 0 ) ? 100*self::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / $_navedbe[$gid] : 0;
					echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
					echo '</td>';
					echo '</tr>';

				} else {
					# drugo
				}
			}
			$cssBack = " anl_bck_2 red";
			echo '<tr>';
			echo '<td class="anl_bl anl_br anl_bb anl_al anl_ita'.$cssBack.'" >&nbsp;</td>';
			echo '<td class="anl_p5 anl_tin1 anl_bl anl_br anl_bb anl_al anl_ita'.$cssBack.'" colspan="'.(self::$_SHOW_LEGENDA && $inline_legenda ? 3 : 1).'">'.$lang['srv_anl_suma_valid'].'</td>';
			echo '<td class="anl_p5 anl_dash_br anl_ac anl_bb anl_ita'.$cssBack.'">'.$_navedbe[$gid].'</td>';
			echo '<td class="anl_p5 anl_br anl_ac anl_bb anl_ita'.$cssBack.'">'.self::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
			echo '</tr>';
				
		}
		echo '</table>';
		echo '</div>'; // Konec div_navedbe_2_$spid

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}
		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}

	}

	/** Izriše sumarnik v horizontalni obliki za multigrid
	 *
	 * @param unknown_type $spid - spremenljivka ID
	 */
	static function sumHorizontal($spid,$_from) {
		global $lang;
		
		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
				}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		#$_invalidAnswers = self :: getInvalidAnswers (MISSING_TYPE_FREQUENCY);
		$_invalidAnswers = self :: getInvalidAnswers (MISSING_TYPE_DESCRIPTOR);
		#$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));

		# opcije nareedimo posebej, da po potrebi zajamemo tudi misinge
		$str_qry = "SELECT id, spr_id, REPLACE(REPLACE(REPLACE(naslov,'\n',' '),'\r','<br>'),'|',' ') as naslov, variable, other, part, REPLACE(REPLACE(REPLACE(naslov_graf,'\n',' '),'\r','<br>'),'|',' ') as naslov_graf, vrstni_red FROM srv_grid WHERE spr_id='".$spid."' ORDER BY vrstni_red";
		$qry = sisplet_query($str_qry);
		while ($row = mysqli_fetch_assoc($qry)) {
			if ($row['other'] != 0 && !isset($_invalidAnswers[$row['other']])) {
				# če prikazujemo misinge dodamo -99 kot mising
				$spremenljivka['options'][$row['other']] = $row['naslov'];
			}
		}

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

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);
		
		echo '<table class="anl_tbl anl_ba tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_bck_desc_1">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_al anl_bck anl_bb anl_bck_desc_1" colspan="'. ($_clmn_cnt+$add_fld+(self::$_SHOW_LEGENDA ? 5+$_sub_question_col : 3+$_sub_question_col)) .'">';
		echo '<span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		$css_txt = 'anl_variabla_line';

		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		if ($_sub_question_col) {
			echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb '.$css_txt.'" style="width:280px">'.$lang['srv_analiza_opisne_subquestion'].'</td>';
		}
		if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line" colspan="'.($_clmn_cnt+1).'">'.$lang['srv_analiza_opisne_answers'];
		echo '<span id="img_analysis_f_p_1_'.$spid.'" class="img_analysis_f_p"><a href="javascript:show_single_percent(\''.$spid.'\',\'2\');">&nbsp(<span class="blue">f&nbsp;</span>/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '<span id="img_analysis_f_1_'.$spid.'" class="img_analysis_f displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'1\');">&nbsp(<span class="blue">f&nbsp;</span>/&nbsp;%)</a></span>';
		echo '<span id="img_analysis_p_1_'.$spid.'" class="img_analysis_p displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'0\');">&nbsp(f&nbsp;/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_valid'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_num_units'].'</td>';
		if ($additional_field) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_povprecje'].'</td>';
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_odklon'].'</td>';
		}
		echo '</tr>';

		$cssBack = "anl_bck_0_0 ";
		$_variables = $grid['variables'];
		echo '<tr>';
		echo '<td class="anl_tin ' . $cssBack . 'anl_bl anl_br anl_bb">&nbsp;</td>';
		if ( $_sub_question_col ) {
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
		}
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">&nbsp;</td>'; //$_tip
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">&nbsp;</td>'; // $_oblika
		}
		
		//nastavitve iz baze za ureditev pravilnega izrisa analize za tabelo s trakom
		$row = Cache::srv_spremenljivka($spid);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0);
		$diferencial_trak_starting_num = ($spremenljivkaParams->get('diferencial_trak_starting_num') ? $spremenljivkaParams->get('diferencial_trak_starting_num') : 0);
		//nastavitve iz baze za ureditev pravilnega izrisa analize za tabelo s trakom
		
		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				if($diferencial_trak){	//ce je trak, je potrebno naslove stolpcev spremeniti v vrednosti na traku
					$_label = $diferencial_trak_starting_num;
					$diferencial_trak_starting_num++;
				}else{
					// misinge imamo zdruzene
					$_label =  $kategorija;
				}

				echo '<td class="' . $cssBack . ' anl_bb anl_ac anl_dash_br ">'.$_label.'</td>';
			}
		}

		echo '<td class="' . $cssBack . ' anl_bb anl_br anl_ac red anl_w70">'.$lang['srv_anl_suma1'].'</td>';
		echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
		echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
		if ($additional_field) {

			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
		}
		echo '</tr>';
		

		$part=null;
		#zlopamo skozi gride
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
		
			# za dvojne gride
			if ((int)$grid['part'] > 0) {
				if ($part == null || $part == $grid['part'] ) {
					$part_css = '';
				} else {
					$part_css = ' anl_double_bt ';
				}
				$part = $grid['part'];	
			} 
			else {
				$part_css = '';
			}
			
			$cssBack = "anl_bck_desc_2 ";
			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
				foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				#popotrebi izpisujemo samo veljavne
				if ((count($spremenljivka['grids']) <= AUTO_HIDE_ZERRO_VALUE || 
						(self::$_FREQUENCYS[$_sequence]['allCnt'] - self::$_FREQUENCYS[$_sequence]['invalidCnt']) > 0))
				if ($variable['other'] != true) {
					echo '<tr id="'.$spid.'_'.$_sequence.'"'.($part_css != '' ? ' class="'.$part_css.'"' : '').'>';
					if ($_sub_question_col) {
						echo '<td class="anl_tin1 ' . $cssBack . 'anl_bl anl_br anl_bb anl_ac anl_variabla_sub">';
						
						echo $variable['variable'];
						echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">';

						echo $variable['naslov'];

						// dodatek desne strani sem. diferenciala
						echo ($spremenljivka['enota'] == 1) ? ' - '.$variable['naslov2'] : '';
						echo '</td>';
					} else {
						echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
					}
					if (self::$_SHOW_LEGENDA) {
						echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">'.$_tip.'</td>';
						echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">'.$_oblika.'</td>';
					}
					# za odklon in povprečje
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					if (count($spremenljivka['options']) > 0) {
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							echo '<td class="anl_bb anl_dash_br anl_ac cll_clps ' . $cssBack . '">';
							echo '<table class="fullWidth anl_ac tbl_clps">';
							echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
							echo '<td class="anl_ac'.(self::$enableInspect == true && (int)self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] > 0 ? ' mg_inspect' : '').'"'
							.(self::$enableInspect == true && (int)self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] > 0 ? ' vkey="'.$key.'"' : '')
							.' style="padding:5px 0px;">'.self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'].'</td>';
							echo '</tr><tr name="single_sums_percent_'.$spid.'">';
							echo '<td style="padding:5px 0px;">';
							$_percent = (self::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
							echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
							echo '</td>';
							echo '</tr></table>';
							echo '</td>';
								
								
						}
					}
					// suma
					echo '<td class="anl_bb anl_br anl_ac cll_clps ' . $cssBack . '">';
					echo '<table class="fullWidth anl_ac tbl_clps">';
					echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
					echo '<td class="anl_ac red" style="padding:5px 0px;">'.((int)self::$_FREQUENCYS[$_sequence]['validCnt']).'</td>';
					echo '</tr><tr name="single_sums_percent_'.$spid.'">';
					echo '<td class="red" style="padding:5px 0px;">'.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
					echo '</tr></table>';
					echo '</td>';
					// zamenjano veljavni ustrezni
					echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac" >';
					echo (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
					echo '</td>';
					echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac" >'.(int)self::$_FREQUENCYS[$_sequence]['allCnt'].'</td>';
					if ($additional_field) { # za odklon in povprečje
						# povprečje
						$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
						#standardna diviacija
						$div = 0;
						$sum_pow_xi_fi_avg  = 0;
						if (count($spremenljivka['options']) > 0) {
							foreach ( $spremenljivka['options'] as $xi => $kategorija) {
								$fi = self::$_FREQUENCYS[$_sequence]['valid'][$xi]['cnt'];
								$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
							}
						}
						$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
						echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac" >';
						echo self::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						echo '</td>';
						echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac" >';
						echo self::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						echo '</td>';
					}
					echo '</tr>';
						
				} else {
					# immamo polje drugo
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** Izriše sumarnik v horizontalni obliki za dvojni multigrid
	 *
	 * @param unknown_type $spid - spremenljivka ID
	 */
	static function sumDoubleHorizontal($spid,$_from) {
		global $lang;
		
		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		$_answersOther = array();
		$_clmn_cnt = count($spremenljivka['options'])*2;

		# pri radiu in dropdown ne prikazujemo podvprašanj
		$_sub_question_col = 6;

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);
		
		echo '<table class="anl_tbl anl_ba tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_bck_desc_1">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_br anl_al anl_bck anl_bb anl_bck_desc_1" colspan="'. ($_clmn_cnt+2+$_sub_question_col+(self::$_SHOW_LEGENDA ? 2 : 0)) .'">';
		echo '<span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		$css_txt = 'anl_variabla_line';

		echo '<tr>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		if ($_sub_question_col) {
			echo '<td class="anl_p5 anl_br anl_ac anl_bck anl_bb '.$css_txt.'" style="width:280px">'.$lang['srv_analiza_opisne_subquestion'].'</td>';
		}
		if (self::$_SHOW_LEGENDA) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w110 anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_variabla_line" colspan="'.($_clmn_cnt+6).'">'.$lang['srv_analiza_opisne_answers'];
		echo '<span id="img_analysis_f_p_1_'.$spid.'" class="img_analysis_f_p"><a href="javascript:show_single_percent(\''.$spid.'\',\'2\');">&nbsp(<span class="blue">f&nbsp;</span>/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '<span id="img_analysis_f_1_'.$spid.'" class="img_analysis_f displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'1\');">&nbsp(<span class="blue">f&nbsp;</span>/&nbsp;%)</a></span>';
		echo '<span id="img_analysis_p_1_'.$spid.'" class="img_analysis_p displayNone"><a href="javascript:show_single_percent(\''.$spid.'\',\'0\');">&nbsp(f&nbsp;/<span class="blue">&nbsp;%</span>)</a></span>';
		echo '</td>';
		#št. enot
		echo '<td  class="anl_br anl_ac anl_bck anl_bb anl_variabla_line" >&nbsp;</td>';
		echo '</tr>';
		#naslovi podskupin
		$cssBack = "anl_bck_0_0 ";
		$_variables = $grid['variables'];
		echo '<tr>';
		echo '<td class="anl_tin ' . $cssBack . 'anl_bl anl_br anl_bb">&nbsp;</td>';
		if ( $_sub_question_col ) {
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
		}
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">&nbsp;</td>'; //$_tip
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">&nbsp;</td>'; // $_oblika
		}
		echo '<td class="' . $cssBack . ' anl_bb anl_ac anl_br" colspan="'.(count($spremenljivka['options'])+3).'">'.($spremenljivka['double'][1]['subtitle'] == '' ? $lang['srv_grid_subtitle_def'].' 1' : $spremenljivka['double'][1]['subtitle']).'</td>';
		echo '<td class="' . $cssBack . ' anl_bb anl_ac anl_br" colspan="'.(count($spremenljivka['options'])+3).'">'.($spremenljivka['double'][2]['subtitle'] == '' ? $lang['srv_grid_subtitle_def'].' 2' : $spremenljivka['double'][2]['subtitle']).'</td>';
		#št. enot
		echo '<td class="' . $cssBack . ' anl_bb anl_ac anl_br" >&nbsp;</td>';

		echo '</tr>';

		# naslovi variabel
		$cssBack = "anl_bck_0_0 ";
		$_variables = $grid['variables'];
		echo '<tr>';
		echo '<td class="anl_tin ' . $cssBack . 'anl_bl anl_br anl_bb">&nbsp;</td>';
		if ( $_sub_question_col ) {
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
		}
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">&nbsp;</td>'; //$_tip
			echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">&nbsp;</td>'; // $_oblika
		}
		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija;
				echo '<td class="' . $cssBack . ' anl_bb anl_ac anl_dash_br ">'.$_label.'</td>';
			}
		}
		echo '<td class="' . $cssBack . ' anl_bb anl_br anl_ac red anl_w70">'.$lang['srv_anl_suma1'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_povprecje'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_odklon'].'</td>';


		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija;
				echo '<td class="' . $cssBack . ' anl_bb anl_ac anl_dash_br ">'.$_label.'</td>';
			}
		}
		echo '<td class="' . $cssBack . ' anl_bb anl_br anl_ac red anl_w70">'.$lang['srv_anl_suma1'].'</td>';
		echo '<td class="' . $cssBack . ' anl_bb anl_br anl_ac anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_povprecje'].'</td>';
		echo '<td class="' . $cssBack . ' anl_bb anl_br anl_ac anl_w70 anl_variabla_line">'.$lang['srv_analiza_opisne_odklon'].'</td>';

		# št enot
		echo '<td class="' . $cssBack . ' anl_bb anl_br anl_ac anl_w70 anl_variabla_line">'.$lang['srv_analiza_num_units'].'</td>';

		echo '</tr>';
		#zloopamo skozi gride in nardimo
		$_tmp_table = array();
		$_part = 1;
		$cnt = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				if ( $_part == $grid['part'] ) {
					$cnt++;
				} else {
					$_part = $grid['part'];
					$cnt = 1;
				}
				# zloopamo skozi variable
				if (count($grid['variables']) > 0) {
					foreach ($grid['variables'] AS $vid => $variable ) {
							
						$_sequence = $variable['sequence'];
						if ($variable['other'] != true) {
							# za odklon in povprečje
							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							if (count($spremenljivka['options']) > 0) {
								foreach ( $spremenljivka['options'] as $key => $kategorija) {
									$xi = $key;
									$fi = self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
									$sum_xi_fi += $xi * $fi ;
									$N += $fi;

									$_percent = (self::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
									$_tmp_table[$grid['part']][$cnt]['variables'][] = array('key'=>$key, 'freq'=>self::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'],'percent'=>$_percent);
								}
							}
							$_tmp_table[$grid['part']][$cnt]['seq'] = $variable['seq'];
							$_tmp_table[$grid['part']][$cnt]['vr_id'] = $variable['vr_id'];
							$_tmp_table[$grid['part']][$cnt]['variable'] = substr($variable['variable'], 0, strrpos($variable['variable'], "_"));
							$_tmp_table[$grid['part']][$cnt]['naslov'] = $variable['naslov'];
							$_tmp_table[$grid['part']][$cnt]['suma'] = self::$_FREQUENCYS[$_sequence]['validCnt'];
							$_tmp_table[$grid['part']][$cnt]['allCnt'] = (int)self::$_FREQUENCYS[$_sequence]['allCnt'];

							# odklon
							$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
							#standardna diviacija
							$div = 0;
							$sum_pow_xi_fi_avg  = 0;
							if (count($spremenljivka['options']) > 0) {
								foreach ( $spremenljivka['options'] as $xi => $kategorija) {
									$fi = self::$_FREQUENCYS[$_sequence]['valid'][$xi]['cnt'];
									$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
								}
							}
							$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
								
							$_tmp_table[$grid['part']][$cnt]['avg'] = $avg;
							$_tmp_table[$grid['part']][$cnt]['div'] = $div;
						}	//end if ($variable['other'] != true)
					} // end foreach variables
				}
			}
		}



		#zlopamo skozi gride
		if (count($_tmp_table[1]) > 0) {
			foreach ($_tmp_table[1] AS $tkey => $grid) {
				$cssBack = "anl_bck_desc_2 ";
				echo '<tr id="'.$spid.'_'.$grid['vr_id'].'">';
				if ($_sub_question_col) {
					echo '<td class="anl_tin1 ' . $cssBack . 'anl_bl anl_br anl_bb anl_ac anl_variabla_sub">'.$grid['variable'].'</td>';
					echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">'.$grid['naslov'].'</td>';
				} else {
					echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb">&nbsp;</td>';
				}
				if (self::$_SHOW_LEGENDA) {
					echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">'.$_tip.'</td>';
					echo '<td class="anl_p5 ' . $cssBack . 'anl_br anl_bb anl_ac">'.$_oblika.'</td>';
				}

				# zloopamo skozi variable
				if (count($grid['variables']) > 0) {
					foreach ($grid['variables'] AS $vid => $variable ) {
						#mg_inspectž
						echo '<td class="anl_bb anl_dash_br anl_ac cll_clps '.$cssBack.'">';
						echo '<table class="fullWidth anl_ac tbl_clps">';
						echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
						echo '<td class="anl_ac'.(self::$enableInspect == true && (int)$variable['freq'] > 0 ? ' dmg_inspect' : '').'" style="padding:5px 0px;"'.(self::$enableInspect == true && (int)$variable['freq'] > 0 ? ' gid="'.$variable['key'].'_1"' : '').'>'.$variable['freq'].'</td>';
						echo '</tr><tr name="single_sums_percent_'.$spid.'">';
						echo '<td style="padding:5px 0px;">';
						echo self::formatNumber($variable['percent'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
						echo '</td>';
						echo '</tr></table>';
						echo '</td>';
					} // end foreach variables
				}	// end if (count($grid['variables']) > 0)
				// suma
				echo '<td class="anl_bb anl_br anl_ac cll_clps ' . $cssBack . '">';
				echo '<table class="fullWidth anl_ac tbl_clps">';
				echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
				echo '<td class="anl_ac red" style="padding:5px 0px;">'.(int)$grid['suma'].'</td>';
				echo '</tr><tr name="single_sums_percent_'.$spid.'">';
				echo '<td class="red" style="padding:5px 0px;">'.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
				echo '</tr></table>';
				echo '</td>';

				// povpreje
				echo '<td class="anl_bb anl_br anl_ac ' . $cssBack . '" >';
				echo self::formatNumber($grid['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				echo '</td>';

				// odklon
				echo '<td class="anl_bb anl_br anl_ac ' . $cssBack . '" >';
				echo self::formatNumber($grid['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				echo '</td>';

				# dodamo desni del grida
				$_right_grid = $_tmp_table[2][$tkey];
				if (count($_right_grid['variables']) > 0) {
					foreach ($_right_grid['variables'] AS $vid => $variable ) {
						echo '<td class="anl_bb anl_dash_br anl_ac cll_clps '.$cssBack.'">';
						#mg_inspect
						echo '<table class="fullWidth anl_ac tbl_clps">';
						echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
						echo '<td class="anl_ac'.(self::$enableInspect == true && (int)$variable['freq'] > 0 ? ' dmg_inspect' : '').'" style="padding:5px 0px;"'.(self::$enableInspect == true && (int)$variable['freq'] > 0 ? ' gid="'.$variable['key'].'_2"' : '').'>'.$variable['freq'].'</td>';
						echo '</tr><tr name="single_sums_percent_'.$spid.'">';
						echo '<td style="padding:5px 0px;">';
						echo self::formatNumber($variable['percent'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
						echo '</td>';
						echo '</tr></table>';
						echo '</td>';
					} // end foreach variables
				}	// end if (count($grid['variables']) > 0)
				// suma
				echo '<td class="anl_bb anl_br anl_ac cll_clps '.$cssBack.'">';
				echo '<table class="fullWidth anl_ac tbl_clps">';
				echo '<tr name="single_sums_percent_cnt_'.$spid.'" class="anl_dash_bb">';
				echo '<td class="anl_ac red" style="padding:5px 0px;">'.(int)$_right_grid['suma'].'</td>';
				echo '</tr><tr name="single_sums_percent_'.$spid.'">';
				echo '<td class="red" style="padding:5px 0px;">'.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
				echo '</tr></table>';
				echo '</td>';

				// povpreje
				echo '<td class="anl_bb anl_br anl_ac ' . $cssBack . '" >';
				echo self::formatNumber($_right_grid['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				echo '</td>';

				# odklon
				echo '<td class="anl_bb anl_br anl_ac ' . $cssBack . '" >';
				echo self::formatNumber($_right_grid['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				echo '</td>';

				# št enot
				echo '<td class="anl_bb anl_br anl_ac ' . $cssBack . '" >';
				echo $grid['allCnt'];
				echo '</td>';
				echo '</tr>';
			} // end foreach ($_tmp_table[1] AS $tkey => $grid)
		}
		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}

	}

	/** Izriše multi number odgovore. izpiše samo povprečja
	 *
	 * @param unknown_type $spid
	 */
	static function sumMultiNumber($spid,$_from) {
		global $lang;

		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);

		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_bb tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		#odgovori
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA ? 3+$_cols : 1+$_cols).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		#odgovori

		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">'.$lang['srv_analiza_opisne_subquestion'] . '</td>';
		if (self::$_SHOW_LEGENDA){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line" colspan="'.($_cols).'">'. $lang['srv_analiza_sums_average'] .'</td>';

		echo '</tr>';
		// konec naslovne vrstice

		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			echo '<tr>';
			echo '<td class="anl_bl anl_bb anl_bck">&nbsp;</td>';
			echo '<td class="anl_bl anl_br anl_bb anl_bck">&nbsp;</td>';

			if (self::$_SHOW_LEGENDA){
				echo '<td class="anl_br anl_bb anl_bck">&nbsp;</td>';
				echo '<td class="anl_br anl_bb anl_bck">&nbsp;</td>';
			}
			if (count($_row['variables']) > 0 )
				foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki

				if ($_col['other'] != true) {
					echo '<td class="anl_br anl_bb anl_bck anl_ac">';
					// echo $_col['variable'];
					echo $_col['naslov'];
					echo '</td>';
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			echo '</tr>';
			$_css_bck = 'anl_bck_desc_2 anl_ac anl_bt_dot ';
			$last = 0;
			//anl_bck_desc_2 anl_bl anl_br anl_variabla_sub
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);
				echo '<tr class="'.$_css_bck.'">';
				echo '<td class="anl_bl anl_br anl_variabla_sub">';
				echo $grid['variable'];
				echo '</td>';
				echo '<td class="anl_br anl_al">';
				echo $grid['naslov'];
				echo '</td>';
				if (self::$_SHOW_LEGENDA){
					echo '<td class="anl_br">'.$_tip.'</td>';
					echo '<td class="anl_br">'.$_oblika.'</td>';
				}

				if ($_variables_count > 0) {
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki

						if ($variable['other'] != true) {
							# tabela z navedbami
							echo '<td class="anl_at anl_br">';
						echo self::formatNumber(self::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						echo '</td>';
							
						}
					}
						
				}
				echo '</tr>';
			}
		}
		echo '</table>';

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** Izriše multi number odgovore. v Navpični obliki (podobno kot opisne)
	 *
	 * @param unknown_type $spid
	 */
	static function sumMultiNumberVertical($spid,$_from) {
		global $lang;
			
		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (self::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if ((int)$spremenljivka['enota'] == 0 && self::$_HEADERS[$spid]['cnt_all'] == 1) {
			$show_enota = false;
		}

		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count($grid['variables']) > 0)
				foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], self::$_FREQUENCYS[$_sequence]['allCnt']);

				# za povprečje
				$sum_xi_fi=0;
				$N = 0;
				$div=0;
				$min = null;
				$max = null;

				if (count(self::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {

						# popravimo morebitne . in -
						$fnkey = (float)$xi;

						if (is_numeric($xi) && is_numeric($fnkey) && trim($fnkey) != '') {
							$fi = $_validFreq['cnt'];
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;
								
							$min = $min != null ? min($min,$fnkey) : $fnkey;
							$max = $max != null ? max($max,$fnkey) : $fnkey;

						}
					}
				}
				# povprešje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;

				self::$_FREQUENCYS[$_sequence]['validAvg'] = $avg;
				self::$_FREQUENCYS[$_sequence]['validMin'] = $min;
				self::$_FREQUENCYS[$_sequence]['validMax'] = $max;

				#standardna diviacija
				$div = 0;
				$sum_pow_xi_fi_avg  = 0;
				if (count(self::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {
						$fi = $_validFreq['cnt'];
						$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
					}
				}
				self::$_FREQUENCYS[$_sequence]['validDiv'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

				#določimo še polja drugo za kasnejši prikaz
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		$isSingleGrid = ($spremenljivka['cnt_all'] == $spremenljivka['cnt_grids']) ? true : false;

		# če je cnt_all == cnt_grids pomeni da imamo samo 1 grid

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';		
		}
		
		self::displaySpremenljivkaIcons($spid);

		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_br anl_bb tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		#odgovori
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA ? 7+(int)$inline_legenda*2 : 7).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
				
			if (!$inline_legenda) {
				echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
			}
				
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck">';

		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';


		if ($show_enota) {
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">';
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				echo $lang['srv_analiza_opisne_subquestion'];
			} else {
				echo $lang['srv_analiza_opisne_variable_text'];
			}
			echo'</td>';
		} else { # če mamo number brez labele izrisujemo drugače
			echo '<td class="anl_br">';
			echo '&nbsp;';
			echo'</td>';
		}

		if (self::$_SHOW_LEGENDA && $inline_legenda){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line" >'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line" >'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_m'] . '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_num_units'] .  '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_povprecje'] . '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_odklon'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_min'] . '</td>';
		echo '<td class="anl_bck anl_ac anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_max'] . '</td>';
		echo '</tr>';

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

						$cssBrdr = (int)$grid['new_grid'] == 1 && $isSingleGrid == false? ' anl_double_bt' : ' anl_bt_dot';

						echo '<tr>';
						if (!$show_enota && $spremenljivka['tip'] == 7) {
							echo '<td style="border-bottom:1px solid white;">' ;
						} else {
							echo '<td class="anl_bck_desc_2 anl_ac anl_bl anl_br anl_variabla_sub'.$cssBrdr.'">' ;
						}
						echo $_css_double_line;
						# za number (7) ne prikazujemo variable
						if ($spremenljivka['tip'] != 7 ) {
							echo $variable['variable'];
						}
						echo '</td>' ;
						if (!$show_enota && $spremenljivka['tip'] == 7) {
							echo '<td style="border-bottom:1px solid white;">' ;
						} else {
							echo '<td class="anl_bck_desc_2 anl_al anl_br'.$cssBrdr.'">' ;
						}

						if ($show_enota) {
							# če ni enojni grid
							if ($isSingleGrid == false) {
							echo (count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov'];
						} else {
							# če je enojni, izpišemo labele variable
							echo $grid['naslov'];
						}
						} else {
							echo '&nbsp;';
						}
						echo '</td>' ;
						if (self::$_SHOW_LEGENDA && $inline_legenda) {
							if ($variable['other'] != '1' && $variable['text'] != '1') {
								$_tip = self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
								$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
							} else {
								$_tip =  $lang['srv_analiza_vrsta_bese'];
								$_oblika =  $lang['srv_analiza_oblika_nomi'];
							}
							echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'" title="'.$_tip.'">'.$_tip.'</td>';
							echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'" title="'.$_oblika.'">' .$_oblika. '</td>';
						}
						echo '<td class="anl_bck_desc_2 anl_ac anl_br anl_bl'.$cssBrdr.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo (int)$_approp_cnt[$gid];
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo self::formatNumber(self::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo self::formatNumber(self::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['validMin'];
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['validMax'];;
						echo '</td>';

						echo '</tr>';
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}

			}
		}
		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}
	/** Izriše number odgovore v vertikalni obliki
	 *
	 * @param unknown_type $spid
	 */
	static function sumNumberVertical($spid,$_from) {
		global $lang;
		$spremenljivka = self::$_HEADERS[$spid];


		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (self::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if (((int)$spremenljivka['enota'] == 0 && self::$_HEADERS[$spid]['cnt_all'] == 1) || $spremenljivka['tip'] == 22 || $spremenljivka['tip'] == 25) {
			$show_enota = false;
		}
		$sum_avg = 0;
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count($grid['variables']) > 0)
				foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], self::$_FREQUENCYS[$_sequence]['allCnt']);

				# za povprečje
				$sum_xi_fi=0;
				$N = 0;
				$div=0;

				$min = null;
				$max = null;
				if (count(self::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {

						# popravimo morebitne . in -
						$fnkey = (float)$xi;
							
						if (is_numeric($xi) && is_numeric($fnkey) && trim($fnkey) != '') {
							$fi = $_validFreq['cnt'];
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;
							$min = $min != null ? min($min,$fnkey) : $fnkey;
							$max = $max != null ? max($max,$fnkey) : $fnkey;
						}
					}
				}

				#povprečje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
				$sum_avg += $avg;
				self::$_FREQUENCYS[$_sequence]['validAvg'] = $avg;
				self::$_FREQUENCYS[$_sequence]['validMin'] = $min;
				self::$_FREQUENCYS[$_sequence]['validMax'] = $max;

				#standardna diviacija
				$div = 0;
				$sum_pow_xi_fi_avg  = 0;
				if (count(self::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {
						$fi = $_validFreq['cnt'];
						$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
					}
				}
				self::$_FREQUENCYS[$_sequence]['validDiv'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

				#določimo še polja drugo za kasnejši prikaz
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';	
		}
		
		self::displaySpremenljivkaIcons($spid);

		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_br anl_bb tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		$num_cols = 7 + ($spremenljivka['tip'] == 18 ? 1 : 0);
		#odgovori
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA ? $num_cols+(int)$inline_legenda*2 : $num_cols).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
				
			if (!$inline_legenda) {
				echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
			}
				
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';


		if ($show_enota == true) {
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">';
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				echo $lang['srv_analiza_opisne_subquestion'];
			} else {
				echo $lang['srv_analiza_opisne_variable_text'];
			}
			echo'</td>';
		} else { # če mamo number brez labele izrisujemo drugače
			echo '<td class="anl_br">';
			echo '&nbsp;';
			echo'</td>';
		}

		if (self::$_SHOW_LEGENDA && $inline_legenda){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line" >'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line" >'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_m'] . '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_num_units'] .  '</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_povprecje'] . '</td>';
		if ($spremenljivka['tip'] == 18) {
			echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">%</td>';
		}
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_odklon'].'</td>';
		echo '<td class="anl_br anl_ac anl_bck anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_min'] . '</td>';
		echo '<td class="anl_bck anl_ac anl_bb anl_w70 anl_legend anl_variabla_line">' . $lang['srv_analiza_opisne_max'] . '</td>';
		echo '</tr>';

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

						$cssBrdr = (int)$grid['new_grid'] == 1 ? ' anl_double_bt' : ' anl_bt_dot';

						echo '<tr>';
						if (!$show_enota && $spremenljivka['tip'] == 7) {
							echo '<td style="border-bottom:1px solid white;">' ;
						} else {
							echo '<td class="anl_bck_desc_2 anl_ac anl_bl anl_br anl_variabla_sub'.$cssBrdr.'">' ;
						}
						echo $_css_double_line;
						# za number (7) ne prikazujemo variable
						if ($spremenljivka['tip'] != 7 || ($show_enota == true && $spremenljivka['tip'] == 7 )) {
							if ($variable['variable'] == $spremenljivka['variable']) {
								echo $variable['variable'].'_1';
							} else {
								echo $variable['variable'];
							}
						}
						echo '</td>' ;
						if ((!$show_enota && $spremenljivka['tip'] == 7 ) || $spremenljivka['tip'] == 22 || $spremenljivka['tip'] == 25) {
							echo '<td style="border-bottom:1px solid white;">' ;
						} else {
							echo '<td class="anl_bck_desc_2 anl_al anl_br'.$cssBrdr.'">' ;
						}
						if ($show_enota) {
							echo (count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov'];
						} else {
							echo '&nbsp;';
						}
						echo '</td>' ;
						if (self::$_SHOW_LEGENDA && $inline_legenda) {
							if ($variable['other'] != '1' && $variable['text'] != '1') {
								$_tip = self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
								$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
							} else {
								$_tip =  $lang['srv_analiza_vrsta_bese'];
								$_oblika =  $lang['srv_analiza_oblika_nomi'];
							}
							echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'" title="'.$_tip.'">'.$_tip.'</td>';
							echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'" title="'.$_oblika.'">' .$_oblika. '</td>';
						}
						echo '<td class="anl_bck_desc_2 anl_ac anl_br anl_bl'.$cssBrdr.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo (int)$_approp_cnt[$gid];
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo self::formatNumber(self::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						echo '</td>';
						if ($spremenljivka['tip'] == 18) {
							$_percent = ($sum_avg > 0 ) ? 100 * self::$_FREQUENCYS[$_sequence]['validAvg'] / $sum_avg : 0;
							echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
							echo self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'%');
							echo '</td>';
						}
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo self::formatNumber(self::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						echo '</td>';
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['validMin'];
						echo '<td class="anl_bck_desc_2 anl_ac anl_br'.$cssBrdr.'">';
						echo (int)self::$_FREQUENCYS[$_sequence]['validMax'];;
						echo '</td>';

						echo '</tr>';
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}

			}
		}
		if ($spremenljivka['tip'] == 18) {
			$css_back = 'anl_bck_text_1 anl_bt';
			echo '<tr>';
			echo '<td class="'.$css_back.' anl_bl red">';
			echo $lang['srv_anl_suma1'];
			echo '</td>';
		echo '<td class="'.$css_back.'">&nbsp;</td>';
			
		if (self::$_SHOW_LEGENDA && $inline_legenda){
			echo '<td class="'.$css_back.'" >&nbsp;</td>';
			echo '<td class="'.$css_back.'" >&nbsp;</td>';
		}
		echo '<td class="'.$css_back.'" >&nbsp;</td>';
		echo '<td class="'.$css_back.'" >&nbsp;</td>';
		echo '<td class="'.$css_back.' anl_ac anl_bl anl_br" >';
		echo self::formatNumber($sum_avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
		echo '</td>';
		# skupna suma
		echo '<td class="'.$css_back.' anl_br anl_ac" >100%</td>';
		echo '<td class="'.$css_back.'" >&nbsp;</td>';
		echo '<td class="'.$css_back.'" >&nbsp;</td>';
		echo '<td class="'.$css_back.'" >&nbsp;</td>';
		echo '</tr>';
		}
		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** Izriše nagovor
	 *
	 */
	static function sumNagovor($spid,$_from) {
		global $lang;
		$spremenljivka = self::$_HEADERS[$spid];
		$_tip = self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
		$cssBack = "anl_bck_freq_1 ";

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';	
		}
		
		self::displaySpremenljivkaIcons($spid);
	
		echo '<table class="anl_tbl_inner anl_ba" >';
		echo '<tr>';
		echo '<td class="anl_p5 anl_br anl_ac anl_bck_desc_1 anl_bb anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		echo '<td class="anl_p5 anl_br anl_al anl_bck_desc_1 anl_bb"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 *
	 * @param unknown_type $spid
	 */
	//static function sumMultiText($spid,$_from, $lokacija=false) {
	static function sumMultiText($spid,$_from) {
		global $lang;			

		$spremenljivka = self::$_HEADERS[$spid];
					$lokacija=false;
					$heatmap=false;
					if($spremenljivka['tip'] == 26)
						$lokacija=true;
					else if ($spremenljivka['tip'] == 27)
						$heatmap=true;
						
		$anketa = self::$sid;

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
				}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
		
		//		$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

                //za tip lokacija (ne enota 3) se rabi user_id, ker se kasneje delajo linki
                $need_user_id = !($spremenljivka['tip'] != 26 || ($spremenljivka['tip'] == 26 && $spremenljivka['enota'] == 3));
		$_answers = self::getAnswers($spremenljivka, $num_show_records, $need_user_id);

		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);

		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_bb tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		#odgovori
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(!$lokacija ? (self::$_SHOW_LEGENDA ? 3+$_cols : 1+$_cols) : 3+$_cols).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		#odgovori

					if(!$lokacija)
						echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">'.$lang['srv_analiza_opisne_subquestion'] . '</td>';
		if (self::$_SHOW_LEGENDA){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
					if(!$lokacija){
						echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line" colspan="'.(!$lokacija ? $_cols : 3+$_cols).'">'. $lang['srv_analiza_opisne_arguments'] .'</td>';
						echo '</tr>';
					}
		// konec naslovne vrstice

		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
							if(!$lokacija){
								echo '<tr>';
								echo '<td class="anl_bl anl_bb anl_bck">&nbsp;</td>';
								echo '<td class="anl_bl anl_br anl_bb anl_bck">&nbsp;</td>';
							}

			if (self::$_SHOW_LEGENDA){
				echo '<td class="anl_br anl_bb anl_bck">&nbsp;</td>';
				echo '<td class="anl_br anl_bb anl_bck">&nbsp;</td>';
			}
			if (count($_row['variables'])>0)
				foreach ($_row['variables'] AS $rid => $_col ){

				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
					echo '<td class="anl_br anl_bb anl_bck anl_ac">';
					// echo $_col['variable'];
					echo $_col['naslov'];
					echo '</td>';
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			echo '</tr>';
			$_css_bck = 'anl_bck_desc_2 anl_ac anl_bt_dot ';
			$last = 0;
			//anl_bck_desc_2 anl_bl anl_br anl_variabla_sub
			foreach ($spremenljivka['grids'] AS $gid => $grid) {

				$_variables_count = count($grid['variables']);
				echo '<tr class="'.$_css_bck.'">';
				echo '<td class="anl_bl anl_br anl_variabla_sub">';
									if(!$lokacija)
										echo $grid['variable'];
									//else{
									else if ($lokacija && $heatmap == false){
										//echo $grid['naslov'].'<br>';//ni potrebno, ker je ze v glavi?
                                                                            	$sprid = explode('_',$spid);
										$loopid = $sprid[1];
										$sprid = $sprid[0];
                                                                                
                                                                                self::displayMapDataAll($spid);
									}
									elseif($heatmap){
										//echo $grid['naslov'].'<br>';//ni potrebno, ker je ze v glavi?
										$sprid = explode('_',$spid);
										$loopid = $sprid[1];
										$sprid = $sprid[0];
										SurveyUserSession::Init($anketa);											
										
										$heatmapId = 'heatmap'.$sprid;
										//echo $heatmapId;
										//SurveyChart::displayExportIcons($sprid);
										echo '<a class="fHeatMap" id="heatmap_'.$sprid.'" title="'.$lang['srv_view_data_on_map'].
											'" href="javascript:void(0);" onclick="passHeatMapData('.$sprid.', -1, '.$loopid.', '.$anketa.');">';

										echo 'Heatmap';
										echo '</a>';
									}
				echo '</td>';
									if(!$lokacija){
										echo '<td class="anl_br anl_al">';
										echo $grid['naslov'];
										echo '</td>';
									}
				if (self::$_SHOW_LEGENDA){
					echo '<td class="anl_br">'.$_tip.'</td>';
					echo '<td class="anl_br">'.$_oblika.'</td>';
				}

				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,self::$_FREQUENCYS[$_sequence]['validCnt']));
					}

					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					$moreBound = 3;
						
					foreach ($grid['variables'] AS $vid => $variable ){

						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							echo '<td class=" anl_at cll_clps">';
						echo '<table id="'.$spid.'_'.$_sequence.'" class="fullWidth anl_ac tbl_clps" style="vertical-align:top;">';
						#$_valid_cnt = count(self::$_FREQUENCYS[$_sequence]['valid']);
						$index=0;
						if (count($_valid_answers) > 0) {
							foreach ($_valid_answers AS $key => $answer) {
								$index++;
								$cssBck = ' '.self::$cssColors['0_' . ($index & 1)];
								$_ans = $answer[$_sequence];
								
								if($index <= $moreBound){
								//if($index < $moreBound){
									echo '<tr class="notmore">';
									echo '<td class="'.$cssBck.' anl_br anl_user_text'
									.($_ans != null && $_ans != '' && self::$enableInspect == true ? ' mt_inspect' : '')
									.'"'
									.($index == 1 && $_ans != null && $_ans != '' && self::$enableInspect == true ? ' vkey="'.$_ans.'"' : '').'>';
									# narišemo printereček za izpis posameznih textovnih odgovorov
									if ($index == 1) {
										//echo '<span class="anl_single_ans_ico as_link" onclick="showSpremenljivkaTextAnswersPopup(\''.$spid.'\',\''.$_sequence.'\'); return false;">';
										
                                                                            //TODO! zakomentiral, ker nima funkcije, ikona pa pokvarjena
                                                                            /*echo '<span class="anl_single_ans_ico as_link">';
										echo '&nbsp;';
										echo '</span>';*/
									}										
									if ($_ans != null && $_ans != '') {
                                                                            if(!($need_user_id && $lokacija))
                                                                                echo $_ans;
                                                                            else
                                                                                echo '<a class="fMap" title="'.$lang['srv_view_data_on_map'].
                                                                                        '" href="javascript:void(0);" onclick="passMapData('.$sprid.', '
                                                                                        .$key.', '.$loopid.', '.$anketa.');">'.$_ans.'</a>';
									} else {
										echo '&nbsp;';
									}

									if($index == $moreBound){
										#more - več
										echo '<br />';
										echo '&nbsp;';
										echo '<div class="srv_heatmap_info_more_'.$sprid.' as_link" onclick="$(\'.more_'.$sprid.', .srv_heatmap_info_more_'.$sprid.', .srv_objava_info_more2_'.$sprid.'\').toggle();">'.$lang['srv_more'].'</div>';
										#more - več - konec
									}
									echo '</td>';
									echo '</tr>';
								}
								else {
									echo '<tr class="more_'.$sprid.' displayNone" >';
									echo '<td class="'.$cssBck.' anl_br anl_user_text'
									.($_ans != null && $_ans != '' && self::$enableInspect == true ? ' mt_inspect' : '')
									.'"'
									.($index == 1 && $_ans != null && $_ans != '' && self::$enableInspect == true ? ' vkey="'.$_ans.'"' : '').'>';
									# narišemo printereček za izpis posameznih textovnih odgovorov
									if ($index == 1) {
										//echo '<span class="anl_single_ans_ico as_link" onclick="showSpremenljivkaTextAnswersPopup(\''.$spid.'\',\''.$_sequence.'\'); return false;">';
										
                                                                            //TODO! zakomentiral, ker nima funkcije, ikona pa pokvarjena
                                                                            /*echo '<span class="anl_single_ans_ico as_link">';
										echo '&nbsp;';
										echo '</span>';*/
									}										
									if ($_ans != null && $_ans != '') {
                                                                            if(!($need_user_id && $lokacija))
                                                                                echo $_ans;
                                                                            else
                                                                                echo '<a class="fMap" title="'.$lang['srv_view_data_on_map'].
                                                                                        '" href="javascript:void(0);" onclick="passMapData('.$sprid.', '
                                                                                        .$key.', '.$loopid.', '.$anketa.');">'.$_ans.'</a>';
									} else {
										echo '&nbsp;';
									}
									
									if($index == $_max_i){
										#less - manj
										echo '<br />';
										echo '&nbsp;';
										echo '<div class="srv_heatmap_info_more2_'.$sprid.' as_link" onclick="$(\'.more_'.$sprid.', .srv_heatmap_info_more_'.$sprid.', .srv_heatmap_info_more2_'.$sprid.' \').toggle();">'.$lang['srv_less'].'</div>';									
									}
									echo '</td>';
									echo '</tr>';
								}
							}
						}
						
						if ($_all_valid_answers_cnt > $index) {
							$index++;
							$cssBck = ' '.self::$cssColors['0_' . ($index & 1)];
							echo '<tr>';
							echo '<td class="'.$cssBck.' anl_br anl_user_text">';
							// Pri javni povezavi drugace izpisemo
							if(self::$printPreview == false)
								echo '<div id="valid_row_togle_more_'.$vid.'" class="floatRight blue pointer anl_more" onclick="showHidenTextTable(\''.$spid.'\', \''.$num_show_records.'\', \''.self::$_CURRENT_LOOP['cnt'].'\');return false;">'.$lang['srv_anl_more'].'</div>';
							else
								echo '<div id="valid_row_togle_more_'.$vid.'" class="floatRight anl_more">'.$lang['srv_anl_more'].'</div>';
							echo '</td>';
							echo '</tr>';
						}
						echo '</table>';

						echo '</td>';
						}
					}
					$last = $_max_i;
					
				}
				echo '</tr>';
			}
		}
		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}
        
        /**
         * 
         * @global type $lang
         * @param string $spid - [spremenljivka_id]_[loop_id]
         */
        private static function displayMapDataAll($spid){
            global $lang;

            $sprid = explode('_',$spid);
            $loopid = $sprid[1];
            $sprid = $sprid[0];

            $spremenljivka = Cache::srv_spremenljivka($sprid);
            $enota = $spremenljivka["enota"];

            //za choose location naredi isto, kot za vsak userja posebej - dobi direkt iz baze ne glede na filterje
            if($enota == 3){
                echo '<a class="fMap" title="'.$lang['srv_view_data_on_map'].
                        '" href="javascript:void(0);" onclick="passMapData('.$sprid.', -1, '.$loopid.', '.self::$sid.', \'mapData\');">';
                echo '<img src="img_0/Google_Maps_Icon.png" height="24" width="24" />';
                echo '</a>';
            }
            //prikaz glede na filterje
            else{
                echo '<a class="fMap" title="'.$lang['srv_view_data_on_map'].
                        '" href="javascript:void(0);" onclick="passMapData('.$sprid.', -1, '.$loopid.', '.self::$sid.', \'mapDataAll\');">';
                echo '<img src="img_0/Google_Maps_Icon.png" height="24" width="24" />';
                echo '</a>';
            }
        }


	/** Izriše tekstovne odgovore v vertikalni obliki
	 *
	 * @param unknown_type $spid
	 */
	static function sumTextVertical($spid,$_from) {
		global $lang;
		# dajemo v bufer, da da ne prikazujemo vprašanj brez veljavnih odgovorov če imamo tako nastavljeno
		$spremenljivka = self::$_HEADERS[$spid];

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
			}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (self::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;

		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();

		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
		}
		
		self::displaySpremenljivkaIcons($spid);
		
		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_br tbl_clps">';
		# naslovna vrstica
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
		echo self::showVariable($spid, $spremenljivka['variable']);
		echo '</td>';
		#odgovori
		echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(self::$_SHOW_LEGENDA ? 5+(int)$inline_legenda*2 : 5).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
		if (self::$_SHOW_LEGENDA) {
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip =  self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
				
			if (!$inline_legenda) {
				echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
			}
				
			echo '<div class="anl_variable_type"><span>'.$lang['srv_analiza_opisne_variable_type'].': </span>'.self::getSpremenljivkaLegenda($spremenljivka,'tip').'</div>';
		}
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		#variabla
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';
		self::showIcons($spid,$spremenljivka,$_from);
		echo '</td>';
		#odgovori

		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">'.$lang['srv_analiza_frekvence_titleAnswers'] . '</td>';
		if (self::$_SHOW_LEGENDA && $inline_legenda){
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		}
		echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		echo '</tr>';
		// konec naslovne vrstice
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

				$counter = 0;
				$_kumulativa = 0;
				//self::$_FREQUENCYS[$_sequence]
				if (count(self::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
					$_valid_answers = self :: sortTextValidAnswers($spid,$variable,self::$_FREQUENCYS[$_sequence]['valid']);

					foreach ($_valid_answers AS $vkey => $vAnswer) {
						if ($counter < $num_show_records || self::$isArchive) {
							if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
								$options['isTextAnswer']=true;
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
						}
					}
					# izpišemo sumo veljavnih
					$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
				}
				if (count(self::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
					foreach (self::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
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

		echo '</table>';
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}

	/** za multi grid tekstovne vrstice doda vrstico z labeliami grida
	 *
	 * @param $gkey
	 * @param $gAnswer
	 * @param $spid
	 * @param $_options
	 */
	static function outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$_options=array()) {
		echo '<tr id="'.$spid.'_'.$counter.'">';
		echo '<td class="anl_bck_freq_2 anl_bl anl_bb anl_br anl_ac anl_variabla_sub">';
		echo $variable['variable'];
		echo '</td>';
		echo '<td class="anl_bck_freq_2 anl_al anl_bb anl_br">';
		//echo ($grid['naslov'] != '' ? $grid['naslov']. '&nbsp;-&nbsp;' : '').$variable['naslov'];
		echo $variable['naslov'];
		echo '</td>';
		if (self::$_SHOW_LEGENDA) {
				
			$spremenljivka = self::$_HEADERS[$spid];
			if ($variable['other'] != '1' && $variable['text'] != '1') {
				$_tip = self::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
				$_oblika = self::getSpremenljivkaLegenda($spremenljivka,'skala');
			} else {
				global $lang;
				$_tip =  $lang['srv_analiza_vrsta_bese'];
				$_oblika =  $lang['srv_analiza_oblika_nomi'];
			}
				
			echo '<td class="anl_bck_freq_2 anl_ac anl_bb anl_br ">'.$_tip.'</td>';
			echo '<td class="anl_bck_freq_2 anl_ac anl_bb anl_br ">'.$_oblika.'</td>';
		}
		echo '<td class="anl_bck_freq_2 anl_bb anl_br">&nbsp;</td>';

		if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_bck_freq_2 anl_bb anl_br">&nbsp;</td>';
		}
		echo '<td class="anl_bck_freq_2 anl_bb anl_br">&nbsp;</td>';
		echo '<td class="anl_bck_freq_2 anl_bb anl_br">&nbsp;</td>';
		echo '</tr>';
		$counter++;
		return $counter;
	}

	static function outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,&$_kumulativa,$_options=array()) {
		global $lang;
		# opcije
			
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);

		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.self::$cssColors['0_' . ($counter & 1)];

		$_valid = (self::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid;

		# če smo v arhivih dodamovse odgovore vendar so nekateri skriti
		if ($counter >= $options['num_show_records'] && self::$isArchive) {
			$cssHide=' class="displayNone"';
		}
		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'"'.(self::$enableInspect == true && (int)$vAnswer['cnt'] > 0 ? ' vkey="'.$vkey.'"' : '').$cssHide.'>';
		echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
		echo '<td class="anl_br'.$cssBck.'">';
		echo '<div class="anl_user_text_more">'.$vkey.'</div>';
		echo (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true ) {
			echo '<td class="anl_ac anl_br'.$cssBck.'">&nbsp;</td>';
			echo '<td class="anl_ac anl_br'.$cssBck.'">&nbsp;</td>';
		}

		echo '<td class="anl_ac anl_br'.$cssBck.(self::$enableInspect == true && $options['isOtherAnswer']== false && (int)$vAnswer['cnt'] > 0 ? ' fr_inspect' : '').'">';
		echo (int)$vAnswer['cnt'];
		echo '</td>';
		echo '<td class="anl_ar anl_br'.$cssBck.' anl_pr10">';
		echo self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
		echo '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_br'.$cssBck.' anl_pr10">';
			echo self::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			echo '</td>';
		}
		echo '<td class="anl_ar'.$cssBck.' anl_pr10">';
		echo self::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');

		echo '</td>';
		echo '</tr>';

		# če mamo več
		if ( $counter+1 == $options['num_show_records'] && $options['num_show_records'] < count(self::$_FREQUENCYS[$_sequence]['valid'])) {
			if (self::$isArchive == false ) {
				echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'" >';
				echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
				echo '<td class="anl_br'.$cssBck.'">';
				// Pri javni povezavi drugace izpisemo
				if(self::$printPreview == false){
					echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatLeft blue pointer anl_more" onclick="showHidenTextTable(\''.$spid.'\', \''.$options['num_show_records'].'\', \''.self::$_CURRENT_LOOP['cnt'].'\');return false;">'.$lang['srv_anl_more'].'</div>';
					echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatRight blue pointer anl_more" onclick="showHidenTextTable(\''.$spid.'\', \''.$options['num_show_records'].'\', \''.self::$_CURRENT_LOOP['cnt'].'\');return false;">'.$lang['srv_anl_more'].'</div>';
				}
				else{
					echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatLeft anl_more">'.$lang['srv_anl_more'].'</div>';
					echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatRight anl_more">'.$lang['srv_anl_more'].'</div>';
				}
				echo '</td>';
				if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true ) {
					echo '<td class="anl_ac anl_br'.$cssBck.'">&nbsp;</td>';
					echo '<td class="anl_ac anl_br'.$cssBck.'">&nbsp;</td>';
				}
				echo '<td class="anl_ac anl_br'.$cssBck.'">'.'</td>';
				echo '<td class="anl_ar anl_br'.$cssBck.' anl_pr10">'.'</td>';
				if (self::$_HEADERS[$spid]['show_valid_percent']) {
					echo '<td class="anl_ar anl_br'.$cssBck.' anl_pr10">'.'</td>';
				}
				echo '<td class="anl_ar'.$cssBck.' anl_pr10">'.'</td>';
				echo '</tr>';
			} else {
				#v arhivie dodamo vse odgovore vendar so skriti
				echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'" >';
				echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
				echo '<td class="anl_br'.$cssBck.'">';
				echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatLeft blue pointer" onclick="$(this).parent().parent().parent().find(\'tr.displayNone\').removeClass(\'displayNone\');$(this).parent().parent().addClass(\'displayNone\');return false;">'.$lang['srv_anl_all'].'</div>';
				echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatRight blue pointer" onclick="$(this).parent().parent().parent().find(\'tr.displayNone\').removeClass(\'displayNone\');$(this).parent().parent().addClass(\'displayNone\');return false;">'.$lang['srv_anl_all'].'</div>';
				echo '</td>';
				if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true ) {
					echo '<td class="anl_ac anl_br'.$cssBck.'">&nbsp;</td>';
					echo '<td class="anl_ac anl_br'.$cssBck.'">&nbsp;</td>';
				}
				echo '<td class="anl_ac anl_br'.$cssBck.'">'.'</td>';
				echo '<td class="anl_ar anl_br'.$cssBck.' anl_pr10">'.'</td>';
				if (self::$_HEADERS[$spid]['show_valid_percent']) {
					echo '<td class="anl_ar anl_br'.$cssBck.' anl_pr10">'.'</td>';
				}
				echo '<td class="anl_ar'.$cssBck.' anl_pr10">'.'</td>';
				echo '</tr>';
			}
		}

		$counter++;
		return $counter;
	}

	static function outputSumaValidAnswerVertical($counter,$_sequence,$spid,$_options=array()) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		//		$cssBck = ' '.self::$cssColors['0_' . ($counter & 1)];		$_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*self::$_FREQUENCYS[$_sequence]['validCnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$cssBck = ' '.self::$cssColors['text_1'];

		$_brez_MV = ((int)self::$missingProfileData['display_mv_type'] === 0 ) ? TRUE : FALSE;
		$_hide_minus = ((int)self::$missingProfileData['display_mv_type'] === 2 ) ? TRUE : FALSE;
		$value =((int)self::$missingProfileData['display_mv_type'] === 0 ) ? 0 : 1;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');

		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		echo '<tr id="anl_click_missing_tr_'.$_sequence.$_sufix.'" class="'.($_brez_MV ? 'anl_bb' : 'anl_dash_red_bb').'">';
		echo '<td class="anl_bl anl_br anl_al gray anl_ti_20'.$cssBck.'">'.$lang['srv_anl_valid'];

		echo '<span id="click_missing_'.$_sequence.$_sufix.'" class="anl_click_missing gray'.($_brez_MV ? '' : ' displayNone').'" value="'.$value.'">&nbsp;&nbsp;<span class="faicon plus_orange icon-orange_hover_red folder_plusminus"></span></span>';
		echo '<span id="single_missing_title_'.$_sequence.$_sufix.'" class="anl_click_missing_hide gray'.($_brez_MV || $_hide_minus? ' displayNone' : '').'">&nbsp;&nbsp;<span class="faicon minus_orange icon-orange_hover_red folder_plusminus"></span></span>';
		echo '</td>';
					
		echo '<td class="anl_br anl_al anl_ita red'.$cssBck.'" >'.$lang['srv_anl_suma1'].'</td>'; 
					

		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<th class="anl_ita red anl_br anl_ac'.$cssBck.'">&nbsp;</th>';
			echo '<th class="anl_ita red anl_br anl_ac'.$cssBck.'">&nbsp;</th>';
		}
		echo '<td class="anl_ita red anl_br anl_ac'.$cssBck.'" >';

		echo self::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		echo '</td>';
		$_percent = self::$_FREQUENCYS[$_sequence]['allCnt'] > 0
		? 100 * self::$_FREQUENCYS[$_sequence]['validCnt'] / self::$_FREQUENCYS[$_sequence]['allCnt']
		: 0;
		echo '<td class="anl_ita red anl_br anl_ar'.$cssBck.' anl_pr10">' . self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_ita red anl_br anl_ar'.$cssBck.' anl_pr10">' . self::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		}
		echo '<td class="anl_ita red anl_ac'.$cssBck.'">&nbsp;</td>';
		echo '</tr>';
		//		$counter++;
		return $counter;

	}

	static function outputInvalidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array()) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.self::$cssColors['text_' . ($counter & 1)];
		$cssBck = ' '.self::$cssColors['0_' . ($counter & 1)];

		$_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (self::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		$_Z_MV = ((int)self::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;
		echo '<tr name="missing_detail_'.$_sequence.$_sufix.'"'.($_Z_MV ? '': ' class="displayNone"').'>';
		echo '<td class="anl_bl anl_br anl_ac gray" style="width:10px">&nbsp;</td>';
		echo '<td class="anl_br'.$cssBck.'">';
		echo '<div class="floatLeft"><div class="anl_tin2">'.'<span class="anl_user_text">' . $vkey . '</span>' . ' (' . $vAnswer['text'].')'.'</div></div>';
		echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.self::formatNumber($_invalid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</div>';
		echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>';
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<th class="anl_ac anl_br'.$cssBck.'">&nbsp;</th>';
			echo '<th class="anl_ac anl_br'.$cssBck.'">&nbsp;</th>';
		}
		echo '<td class="anl_ac anl_br'.$cssBck.'">';
		echo (int)$vAnswer['cnt'];
		echo '</td>';
		echo '<td class="anl_ar anl_br'.$cssBck.'">';
		echo self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
		echo '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_br anl_detail_percent anl_ita'.$cssBck.'">';
			echo '&nbsp;';
			echo '</td>';
		}
		echo '<td class="'.$cssBck.'" >';
		echo '&nbsp;';
		echo '</td>';
		echo '</tr>';
		$counter++;
		return $counter;
	}

	static function outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$_options = array()) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.self::$cssColors['text_' . ($counter & 1)];
		$cssBck = ' '.self::$cssColors['text_1'];
		$_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*self::$_FREQUENCYS[$_sequence]['invalidCnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)self::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		$_hide_minus = ((int)self::$missingProfileData['display_mv_type'] === 1 || (int)self::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		echo '<tr id="click_missing_1_'.$_sequence.$_sufix.'" class="anl_dash_red_bb'.($_brez_MV ?' displayNone' : '').'">';
		echo '<td class="anl_bl anl_al anl_br gray anl_ti_20'.$cssBck.'">';
		echo $lang['srv_anl_missing'];
		echo '</td>';
		echo '<td class="anl_br anl_ita red'.$cssBck.'" >';
		echo $lang['srv_analiza_manjkajocevrednosti'];
		// podrobno za missinge
		echo '<span id="single_missing_0'.$_sequence.$_sufix.'" class="printHide anl_ita anl_detail_percent'.($_hide_minus ? '' : ' displayNone').'">&nbsp;&nbsp;';
		echo '<a href="#single_missing_'.$_sequence.$_sufix.'" onclick="show_single_missing(\''.$_sequence.$_sufix.'\', 0);return false;" > ' ;
		//echo  $lang['srv_analiza_missingSpremenljivke'] ;
		echo  ' <span class="faicon plus_orange icon-orange_hover_red folder_plusminus"></span> </a>';
		echo '</span>';
		echo '<span id="single_missing_1'.$_sequence.$_sufix.'" class="printHide anl_ita anl_detail_percent'.($_hide_minus ? ' displayNone' : '').'">&nbsp;&nbsp;';
		echo '<a href="#single_missing_'.$_sequence.$_sufix.'" onclick="show_single_missing(\''.$_sequence.$_sufix.'\', 1);return false;" > ' ;
		// echo  $lang['srv_analiza_missingSpremenljivke'] ;
		echo  ' <span class="faicon minus_orange icon-orange_hover_red folder_plusminus"></span> </a>';
		echo '</span>';

		echo '<div id="single_missing_suma_'.$_sequence.$_sufix.'" class="floatRight anl_w50 anl_dash_bl anl_dash_bt  anl_ac anl_detail_percent displayNone">100.0%</div>';
		echo '<div id="single_missing_suma_freq_'.$_sequence.$_sufix.'" class="floatRight anl_w30 anl_dash_bt anl_ac anl_detail_percent displayNone">'.self::$_FREQUENCYS[$_sequence]['invalidCnt'].'</div>';
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<th class="anl_ac anl_br anl_ita red'.$cssBck.'">&nbsp;</th>';
			echo '<th class="anl_ac anl_br anl_ita red'.$cssBck.'">&nbsp;</th>';
		}

		echo '<td class="anl_ac anl_br anl_detail_cnt anl_ita red'.$cssBck.'">';
		$answer['cnt'] =  self::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? self::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
		echo (int)$answer['cnt'];
		echo '</td>';
		echo '<td class="anl_ar anl_br anl_ita red'.$cssBck.' anl_pr10">';
		echo self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
		echo '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_br anl_ita red'.$cssBck.' anl_pr10">';
			echo '<span id="single_missing_percent_'.$_sequence.$_sufix.'" class="'.($detail ? 'displayNone' : '' ).'">&nbsp;</span>';
			echo '</td>';
		}
		echo '<td class="anl_ar anl_ita red'.$cssBck.' anl_pr10">&nbsp;</td>';
		echo '</tr>';
		$counter++;
		return $counter;
	}

	static function outputSumaVertical($counter,$_sequence,$spid, $_options = array()) {
		global $lang;
		# opcije

		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		//		$cssBck = ' '.self::$cssColors['0_' .($counter & 1)];
		$cssBck = ' anl_bck_text_0';
		$_brez_MV = ((int)self::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		echo '<tr id="click_missing_suma_'.$_sequence.$_sufix.'"  class="'.($_brez_MV ? 'displayNone' : '').'">';
		//echo '<td class="anl_bl anl_ac anl_dash_bt anl_br anl_bb gray">&nbsp;</td>'; // $lang['srv_anl_appropriate']
		//echo '<td class="anl_al anl_dash_bt anl_br anl_bb red anl_ita'.$cssBck.'">'.$lang['srv_anl_suma2'].'</td>';
		echo '<td class="anl_bl anl_ac anl_dash_bt anl_bb red anl_ita'.$cssBck.'">'.$lang['srv_anl_suma2'].'</td>';
		echo '<td class="anl_dash_bt anl_br anl_bb'.$cssBck.'">&nbsp;</td>';

		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita'.$cssBck.'" >&nbsp;</td>';
			echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita'.$cssBck.'" >&nbsp;</td>';
		}
		echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.'" >' . (self::$_FREQUENCYS[$_sequence]['allCnt'] ? self::$_FREQUENCYS[$_sequence]['allCnt'] : 0) . '</td>';
		echo '<td class="anl_ar anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.' anl_pr10">' . self::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.' anl_pr10">&nbsp;</td>';
		}
		echo '<td class="anl_ac anl_dash_bt anl_bb anl_ita red'.$cssBck.'">&nbsp;</td>';
		echo '</tr>';

	}

	
	
	static function outputSumaValidAnswerHeatmap($counter,$_sequence,$spid,$_options=array(), $validHeatmapRegion) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.self::$cssColors['text_1'];

		$_brez_MV = ((int)self::$missingProfileData['display_mv_type'] === 0 ) ? TRUE : FALSE;
		$_hide_minus = ((int)self::$missingProfileData['display_mv_type'] === 2 ) ? TRUE : FALSE;
		$value =((int)self::$missingProfileData['display_mv_type'] === 0 ) ? 0 : 1;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');

		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		echo '<tr id="anl_click_missing_tr_'.$_sequence.$_sufix.'" class="'.($_brez_MV ? 'anl_bb' : 'anl_dash_red_bb').'">';
		echo '<td class="anl_bl anl_br anl_al gray anl_ti_20'.$cssBck.'">'.$lang['srv_anl_valid'];

		echo '<span id="click_missing_'.$_sequence.$_sufix.'" class="anl_click_missing gray'.($_brez_MV ? '' : ' displayNone').'" value="'.$value.'">&nbsp;&nbsp;<span class="faicon plus_orange icon-orange_hover_red folder_plusminus"></span></span>';
		echo '<span id="single_missing_title_'.$_sequence.$_sufix.'" class="anl_click_missing_hide gray'.($_brez_MV || $_hide_minus? ' displayNone' : '').'">&nbsp;&nbsp;<span class="faicon minus_orange icon-orange_hover_red folder_plusminus"></span></span>';
		echo '</td>';
		echo '<td class="anl_br anl_al anl_ita red'.$cssBck.'" >'.$lang['srv_anl_suma1'].'</td>'; 

					

		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<th class="anl_ita red anl_br anl_ac'.$cssBck.'">&nbsp;</th>';
			echo '<th class="anl_ita red anl_br anl_ac'.$cssBck.'">&nbsp;</th>';
		}
		
		//Veljavni - Skupaj
		echo '<td class="anl_ita red anl_br anl_ac'.$cssBck.'" >';
		echo $validHeatmapRegion;
		//echo self::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		echo '</td>';
		
		
/* 			$_percent = self::$_FREQUENCYS[$_sequence]['allCnt'] > 0
		? 100 * self::$_FREQUENCYS[$_sequence]['validCnt'] / self::$_FREQUENCYS[$_sequence]['allCnt']
		: 0;
		echo '<td class="anl_ita red anl_br anl_ar'.$cssBck.' anl_pr10">' . self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent'] == true) {
			echo '<td class="anl_ita red anl_br anl_ar'.$cssBck.' anl_pr10">' . self::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		}
		echo '<td class="anl_ita red anl_ac'.$cssBck.'">&nbsp;</td>'; */
		echo '</tr>';
		//		$counter++;
		return $counter;

	}
	
	static function outputInvalidAnswerHeatmap($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array(), $manjkajoci) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.self::$cssColors['text_' . ($counter & 1)];
		$cssBck = ' '.self::$cssColors['0_' . ($counter & 1)];

		$_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (self::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		$_Z_MV = ((int)self::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;
		echo '<tr name="missing_detail_'.$_sequence.$_sufix.'"'.($_Z_MV ? '': ' class="displayNone"').'>';
		echo '<td class="anl_bl anl_br anl_ac gray" style="width:10px">&nbsp;</td>';
		echo '<td class="anl_br'.$cssBck.'">';
		echo '<div class="floatLeft"><div class="anl_tin2">'.'<span class="anl_user_text">' . $vkey . '</span>' . ' (' . $vAnswer['text'].')'.'</div></div>';
		echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.self::formatNumber($_invalid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</div>';
		echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>';
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<th class="anl_ac anl_br'.$cssBck.'">&nbsp;</th>';
			echo '<th class="anl_ac anl_br'.$cssBck.'">&nbsp;</th>';
		}
		echo '<td class="anl_ac anl_br'.$cssBck.'">';
		echo (int)$vAnswer['cnt'];
		echo '</td>';
		echo '<td class="anl_ar anl_br'.$cssBck.'">';
		echo self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
		echo '</td>';
		if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_br anl_detail_percent anl_ita'.$cssBck.'">';
			echo '&nbsp;';
			echo '</td>';
		}
		echo '<td class="'.$cssBck.'" >';
		echo '&nbsp;';
		echo '</td>';
		echo '</tr>';
		$counter++;
		return $counter;
	}
	
	static function outputSumaInvalidAnswerHeatmap($counter,$_sequence,$spid,$_options = array(), $manjkajoci) {
		global $lang;
		# opcije
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.self::$cssColors['text_' . ($counter & 1)];
		$cssBck = ' '.self::$cssColors['text_1'];
		$_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*self::$_FREQUENCYS[$_sequence]['invalidCnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)self::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		$_hide_minus = ((int)self::$missingProfileData['display_mv_type'] === 1 || (int)self::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		echo '<tr id="click_missing_1_'.$_sequence.$_sufix.'" class="anl_dash_red_bb'.($_brez_MV ?' displayNone' : '').'">';
		
		echo '<td class="anl_bl anl_al anl_br gray anl_ti_20'.$cssBck.'">';
		echo $lang['srv_anl_missing'];
		echo '</td>';
		
		echo '<td class="anl_br anl_ita red'.$cssBck.'" >';
		echo $lang['srv_analiza_manjkajocevrednosti'];
		
		// podrobno za missinge
		echo '<span id="single_missing_0'.$_sequence.$_sufix.'" class="printHide anl_ita anl_detail_percent'.($_hide_minus ? '' : ' displayNone').'">&nbsp;&nbsp;';
		echo '<a href="#single_missing_'.$_sequence.$_sufix.'" onclick="show_single_missing(\''.$_sequence.$_sufix.'\', 0);return false;" > ' ;
		//echo  $lang['srv_analiza_missingSpremenljivke'] ;
		echo  ' <span class="faicon plus_orange icon-orange_hover_red folder_plusminus"></span> </a>';
		echo '</span>';
		echo '<span id="single_missing_1'.$_sequence.$_sufix.'" class="printHide anl_ita anl_detail_percent'.($_hide_minus ? ' displayNone' : '').'">&nbsp;&nbsp;';
		echo '<a href="#single_missing_'.$_sequence.$_sufix.'" onclick="show_single_missing(\''.$_sequence.$_sufix.'\', 1);return false;" > ' ;
		// echo  $lang['srv_analiza_missingSpremenljivke'] ;
		echo  ' <span class="faicon minus_orange icon-orange_hover_red folder_plusminus"></span> </a>';
		echo '</span>';

		echo '<div id="single_missing_suma_'.$_sequence.$_sufix.'" class="floatRight anl_w50 anl_dash_bl anl_dash_bt  anl_ac anl_detail_percent displayNone">100.0%</div>';
		//echo '<div id="single_missing_suma_freq_'.$_sequence.$_sufix.'" class="floatRight anl_w30 anl_dash_bt anl_ac anl_detail_percent displayNone">'.self::$_FREQUENCYS[$_sequence]['invalidCnt'].'</div>';
		echo '<div id="single_missing_suma_freq_'.$_sequence.$_sufix.'" class="floatRight anl_w30 anl_dash_bt anl_ac anl_detail_percent displayNone">'.$manjkajoci.'</div>';
		echo '</td>';
		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<th class="anl_ac anl_br anl_ita red'.$cssBck.'">&nbsp;</th>';
			echo '<th class="anl_ac anl_br anl_ita red'.$cssBck.'">&nbsp;</th>';
		}

		//Mankajoci - Skupaj
		echo '<td class="anl_ac anl_br anl_detail_cnt anl_ita red'.$cssBck.'">';
			echo $manjkajoci;
		//$answer['cnt'] =  self::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? self::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;			
		//echo (int)$answer['cnt'];
		echo '</td>';
		
		//stolpec "Veljavni kliki"
/* 			echo '<td class="anl_ar anl_br anl_ita red'.$cssBck.' anl_pr10">';
		echo self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
		echo '</td>';
		*/
		
/* 			if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_br anl_ita red'.$cssBck.' anl_pr10">';
			echo '<span id="single_missing_percent_'.$_sequence.$_sufix.'" class="'.($detail ? 'displayNone' : '' ).'">&nbsp;</span>';
			echo '</td>';
		} */
		
		echo '<td class="anl_ar anl_ita red'.$cssBck.' anl_pr10">&nbsp;</td>';
		echo '</tr>';
		$counter++;
		return $counter;
	}

	static function outputSumaHeatmap($counter,$_sequence,$spid, $_options = array(), $ustrezniHeatmapRegion) {
		global $lang;
		# opcije

		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
				'isOtherAnswer' => false, 	# ali je odgovor Drugo
				'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		//		$cssBck = ' '.self::$cssColors['0_' .($counter & 1)];
		$cssBck = ' anl_bck_text_0';
		$_brez_MV = ((int)self::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;

		$_sufix = (self::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine
		if (isset(self::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.self::$_CURRENT_LOOP['cnt'].$_sufix;
		}

		echo '<tr id="click_missing_suma_'.$_sequence.$_sufix.'"  class="'.($_brez_MV ? 'displayNone' : '').'">';
		//echo '<td class="anl_bl anl_ac anl_dash_bt anl_br anl_bb gray">&nbsp;</td>'; // $lang['srv_anl_appropriate']
		//echo '<td class="anl_al anl_dash_bt anl_br anl_bb red anl_ita'.$cssBck.'">'.$lang['srv_anl_suma2'].'</td>';
		echo '<td class="anl_bl anl_ac anl_dash_bt anl_bb red anl_ita'.$cssBck.'">'.$lang['srv_anl_suma2'].'</td>';
		echo '<td class="anl_dash_bt anl_br anl_bb'.$cssBck.'">&nbsp;</td>';

		if (self::$_SHOW_LEGENDA  && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
			echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita'.$cssBck.'" >&nbsp;</td>';
			echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita'.$cssBck.'" >&nbsp;</td>';
		}
		
		//SKUPAJ
		echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.'" >' .$ustrezniHeatmapRegion. '</td>';
		
		//echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.'" >' . (self::$_FREQUENCYS[$_sequence]['allCnt'] ? self::$_FREQUENCYS[$_sequence]['allCnt'] : 0) . '</td>';
		
		//echo '<td class="anl_ar anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.' anl_pr10">' . self::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		/*if (self::$_HEADERS[$spid]['show_valid_percent']) {
			echo '<td class="anl_ar anl_dash_bt anl_br anl_bb anl_ita red'.$cssBck.' anl_pr10">&nbsp;</td>';
		} */
		//echo '<td class="anl_ac anl_dash_bt anl_bb anl_ita red'.$cssBck.'">&nbsp;</td>';
		echo '</tr>';

	}
	
	/** izpišemo tabelo z tekstovnimi odgovori drugo
	 *
	 * @param $skey
	 * @param $oAnswers
	 * @param $spid
	 */
	static function outputOtherAnswers($oAnswers) {
		global $lang;
		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
			
		$spid = $oAnswers['spid'];
		$_variable = self::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = self::$_FREQUENCYS[$_variable['sequence']];

		echo '<table class="anl_tbl anl_bt anl_bl anl_br tbl_clps">';
		echo '<tr>';
		echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_desc_1 anl_w110 anl_variabla_sub" >'. $_variable['variable'] . '</td>';
		echo '<td class="anl_bl anl_br anl_bb anl_al anl_bck_desc_1" colspan="'.(self::$_SHOW_LEGENDA && false ? 7 : 5).'">';
		echo '<span class="anl_variabla_label">'.self::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )</span>';
		if (self::$_SHOW_LEGENDA) {
			$_tip =  $lang['srv_analiza_vrsta_bese'];
			$_oblika =  $lang['srv_analiza_oblika_nomi'];
			echo '<div class="floatRight"><span>&nbsp;('.$_tip.')</span>'.'</div>';
		}
		if (self::$_SHOW_LEGENDA) {
			echo self::getSpremenljivkaLegenda(0,'tip');
		}

		echo '</td>';
		echo '</tr>';
		$css_txt = 'anl_variabla_line';
		echo '<tr>';
		echo '<td class="anl_bb anl_bl anl_br anl_ac anl_bck anl_w110"><span class="anl_variabla">';
		//self::showIcons($spid,$spremenljivka,$_from);
		echo '</span></td>';

		echo '<td class="anl_bb anl_br anl_ac anl_bck '.$css_txt.'">'. $lang['srv_analiza_frekvence_titleAnswers'] .'</td>';
		if (self::$_SHOW_LEGENDA && false){
			echo '<td class="anl_bb anl_br anl_ac anl_bck anl_w70 '.$css_txt.'">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
			echo '<td class="anl_bb anl_br anl_ac anl_bck anl_w70 '.$css_txt.'">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
		}
		echo '<td class="anl_bb anl_br anl_ac anl_bck anl_w70 '.$css_txt.'">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		echo '<td class="anl_bb anl_br anl_ac anl_bck anl_w70 '.$css_txt.'">'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		echo '<td class="anl_bb anl_br anl_ac anl_bck anl_w70 '.$css_txt.'">'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		echo '<td class="anl_bb anl_br anl_ac anl_bck anl_w70 '.$css_txt.'">'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		echo '</tr>';
		// konec naslovne vrstice
		if (self::$_SHOW_LEGENDA && false){
			$cssBck = 'anl_bck ';
			echo '<tr>';
			echo '<td class="anl_tin anl_bl anl_bb anl_br anl_al '.$cssBck.'link_no_decoration">&nbsp;</td>';
			echo '<td class="anl_bb anl_br anl_al '.$cssBck.'">'.'</td>';

			$_tip =  $lang['srv_analiza_vrsta_bese'];
			$_oblika =  $lang['srv_analiza_oblika_nomi'];

			echo '<td class="anl_bb anl_br '.$cssBck.'anl_ac anl_w90">'.$_tip.'</td>';
			echo '<td class="anl_bb anl_br '.$cssBck.'anl_ac anl_w90 ">'.$_oblika.'</td>';

			echo '<td class="anl_bb anl_br '.$cssBck.' anl_w70">&nbsp;</td>';
			echo '<td class="anl_bb anl_br '.$cssBck.' anl_w70">&nbsp;</td>';
			echo '<td class="anl_bb anl_br '.$cssBck.' anl_w70">&nbsp;</td>';
			echo '<td class="anl_bb '.$cssBck.' anl_w70">&nbsp;</td>';
			echo '</tr>';
		}
		$counter = 1;
		$_kumulativa = 0;
		if ( is_countable(self::$_FREQUENCYS[$_sequence]['valid']) && count(self::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
			foreach (self::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					if ($counter < $num_show_records) {
						$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true,'num_show_records' => $num_show_records));
					}
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(self::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (self::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));

		echo '</table>';
	}

	static public function showVariable($spid,$variable,$_options= array()) {
		global $lang;
		# globalne nastavitve funkcije
		$options = array('prev'=>true,'align'=>'center');
		#ponastavimo uporabniške funkcije
		if (count($_options) > 0) {
			foreach ($_options as $okey => $option) {
				$options[$okey] = $option;
			}
		}

		$sccFloat = ($options['align'] == 'center')
		? ''
		: ( $options['align'] == 'left'
				? ' floatLeft'
				: ' floatRight');
			
		echo '<span class="spaceLeft anl_variabla'.$sccFloat.'">';
		if (self::$isArchive == false && self::$printPreview == false){
			echo '<a href="#" onclick="showspremenljivkaSingleVarPopup(\''.$spid.'\'); return false;">';
			#echo '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="(\'' . $spid . '\'); return false;">'
			echo $variable;
			echo '</a>';
		}
		else{
			echo $variable;
		}
		echo '</span>';
	}


	static public function showIcons($spid, $spremenljivka, $_from = 'freq', $additional=array()) {
		global $lang;
		
		$sccFloat = 'taCenter ';
		#kateri skin
		$skin = 0;
		$options = array('sums'=>true,'sums*'=>true,'desc'=>true,'freq'=>true,'sums_spec'=>false);
		$from_navedbe = (isset($additional['navedbe']) && $additional['navedbe'] == true) ? true : false;
		$showReport = (isset($additional['noReport']) && $additional['noReport'] == true) ? false: true;
		$showChart = (isset($additional['showChart']) && $additional['showChart'] == false) ? false: true;
		$printIcon = (isset($additional['printIcon']) && $additional['printIcon'] == true) ? true: false;
		
		if ($_from == 'para') {
			$showReport = false;
		}
		
		switch ($_from) {
			case 'freq':
			case 'para':
			case 'charts':
				switch ($spremenljivka['tip']) {
					case 1: # radio - prikjaže navpično
						if ($spremenljivka['show_valid_percent'] == true && $spremenljivka['skala'] != 1) {
							# če za ordinalno prikazujemo povprečje in st. oddklon
							$options['sums'] = true;
							$options['sums*'] = true;
						} else {
							# za nominalno ne prikazujemo povprečje in st. oddklon, zato je F == F*
							$options['sums'] = false;
							$options['sums*'] = false;
						}
						break;
					case 2: #checkbox  če je dihotomna:
						break;
					case 3: # dropdown - prikjaže navpično
						break;
					case 6: # multigrid
						$options['sums*'] = false;
						if ( $spremenljivka['enota'] == 3 ) {
							$options['sums_spec'] = true;
						}
						break;
					case 7:  # variabla tipa »število«
						$options['sums*'] = false;
						break;
					case 8: # datum
						$options['sums'] = false;
						$options['sums*'] = false;
						break;
					case 16: #multicheckbox če je dihotomna:
						break;
					case 17: #razvrščanje  če je ordinalna
						$options['sums'] = false;
						break;
					case 18:  # vsota
						$options['sums*'] = false;
						break;
					case 19: # multitext
						$options['sums*'] = false;
						break;
					case 20: # multi number

						break;
					case 4: # text
					case 21: # besedilo*
						if ($spremenljivka['cnt_all'] == 1) {
							// če je enodimenzionalna prikažemo kot frekvence
							// predvsem zaradi vprašanj tipa: language, email...
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						break;
					 case 26: # lokacija
						if ($spremenljivka['cnt_all'] == 1) {
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						break;
					case 27: # heatmap
						if ($spremenljivka['cnt_all'] == 1) {
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						break;
					case 22: # kalkulacija
					case 25: # kvota
						$options['sums*'] = false;
						break; # kalkulacija
				}
				$export = ($_from == 'charts') ? 'charts' : 'frequency';
				break;
			case 'desc':
				switch ($spremenljivka['tip']) {
					case 1: # radio - prikjaže navpično
						if ($spremenljivka['skala'] == 1) {
							$options['sums'] = false;
							$options['sums*'] = false;
						}
						break;
					case 2: #checkbox  če je dihotomna:
						break;
					case 3: # dropdown - prikjaže navpično
						break;
					case 6: # multigrid
						$options['sums*'] = false;
						if ( $spremenljivka['enota'] == 3 ) {
							$options['sums_spec'] = true;
						}
						break;
					case 7:  # variabla tipa »število«
						$options['sums*'] = false;
						break;
					case 8: # datum
						$options['sums'] = false;
						$options['sums*'] = false;
						break;
					case 16: #multicheckbox če je dihotomna:
						break;
					case 17: #razvrščanje  če je ordinalna
						$options['sums'] = false;
						break;
					case 18:  # vsota
						$options['sums*'] = false;
						break;
					case 19: # multitext
						$options['sums*'] = false;
						break;
					case 20:  # multi number
						break;
					case 4:	# text
					case 21: # besedilo*
						if ($spremenljivka['cnt_all'] == 1) {
							// če je enodimenzionalna prikažemo kot frekvence
							// predvsem zaradi vprašanj tipa: language, email...
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						break;
					case 26: # Lokacija
						if ($spremenljivka['cnt_all'] == 1) {
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						break;
					case 27: # heatmap
						if ($spremenljivka['cnt_all'] == 1) {
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						break;
					case 22: # kalkulacija
					case 25: # kvota
						$options['sums*'] = false;
						break; # kalkulacija
				}
				$export = 'statistics';
				break;
			case 'sums':
				switch ($spremenljivka['tip']) {
					case 1: # radio - prikjaže navpično
						if ($spremenljivka['skala'] == 1) {
							$options['sums'] = false;
							$options['sums*'] = false;
							$_from = 'freq';
						} else {
							$_from = 'sums*';
						}
						break;
					case 2: #checkbox  če je dihotomna
						$_from = 'sums*';
						break;
					case 3: # dropdown - prikjaže navpično
						break;
					case 6: # multigrid
						$options['sums*'] = false;
						if ( $spremenljivka['enota'] == 3 ) {
							$options['sums_spec'] = true;
							$_from = 'sums*';
						}
						break;
					case 7:  # variabla tipa »število«
						$options['sums*'] = false;
						break;
					case 8: # datum
						$options['sums'] = false;
						$options['sums*'] = false;
						$_from = 'freq';
						break;

					case 16: #multicheckbox če je dihotomna
						break;
					case 17: #razvrščanje  če je ordinalna
						$options['sums'] = false;
						$_from = 'sums*';
						break;
					case 18:  # vsota
						$options['sums*'] = false;
						break;
					case 19: # multitext
						$options['sums*'] = false;
						break;
					case 20: # multi number
						break;
					case 21: # besedilo*
						$_from = 'sums';
						if ($spremenljivka['cnt_all'] == 1) {
							// če je enodimenzionalna prikažemo kot frekvence
							// predvsem zaradi vprašanj tipa: language, email...
							$options['sums'] = false;
							$_from = 'freq';
						}

						$options['sums*'] = false;
						break;
					case 26: # lokacija
						$_from = 'sums';
						if ($spremenljivka['cnt_all'] == 1) {	
							$options['sums'] = false;
							$_from = 'freq';
						}

						$options['sums*'] = false;
						break;
					case 27: # heatmap
						$_from = 'sums';
						if ($spremenljivka['cnt_all'] == 1) {	
							$options['sums'] = false;
							$_from = 'freq';
						}

						$options['sums*'] = false;
						break;
					case 4:	# text
						$options['sums'] = false;
						$options['sums*'] = false;
						$_from = 'freq';
						break;
					case 22: # kalkulacija
					case 25: # kvota
						$options['sums*'] = false;
						break; # kalkulacija
				}
				$export = 'sums';
				break;
			case 'sums*':
				switch ($spremenljivka['tip']) {
					case 1: # radio - prikjaže navpično
						if ($spremenljivka['skala'] == 1) {
							$options['sums'] = false;
							$options['sums*'] = false;
							$_from = 'freq';
						} else {
							$_from = 'sums';
						}
						break;
					case 2:  #checkbox  če je dihotomna:
						$_from = 'sums';
						break;
					case 3: # dropdown - prikjaže navpično
						break;
					case 6: # multigrid
						$options['sums*'] = false;
						if ( $spremenljivka['enota'] == 3 ) {
							$options['sums_spec'] = true;
							$_from = 'sums';
						}
						break;
					case 7:  # variabla tipa »število«
						$options['sums*'] = false;
						$_from = 'sums';
						break;
					case 8: # datum
						$options['sums'] = false;
						$options['sums*'] = false;
						break;
							
					case 16: #multicheckbox če je dihotomna:
						break;
					case 17: #razvrščanje  če je ordinalna
						$options['sums'] = false;
						break;
					case 18:  # vsota
						$options['sums*'] = false;
						$_from = 'sums';
						break;
					case 19: # multitext
						$options['sums*'] = false;
						break;
					case 20: # multi number
						break;
					case 4:	# text
						$options['sums'] = false;
						$options['sums*'] = false;
						$_from = 'freq';
						break;
					case 21: # besedilo*
						if ($spremenljivka['cnt_all'] == 1) {
							// če je enodimenzionalna prikažemo kot frekvence
							// predvsem zaradi vprašanj tipa: language, email...
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						$_from = 'freq';
						break;
					case 26: # lokacija
						if ($spremenljivka['cnt_all'] == 1) {
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						$_from = 'freq';
						break;
					case 27: # heatmap
						if ($spremenljivka['cnt_all'] == 1) {
							$options['sums'] = false;
						}
						$options['sums*'] = false;
						$_from = 'freq';
						break;
					case 22: # kalkulacija
					case 25: # kvota
						$options['sums*'] = false;
						$_from = 'sums';
						break; # kalkulacija
				}
				$export = 'sums';
				break;
			case 'none':
				break;
		}
		
		// Javna povezava nima teh ikon
		if ($printIcon == false && self::$printPreview == false) {
			
			echo '<span class="'.$sccFloat.'printHide iconHide">';

			if ($options['sums'] == true) {
				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3 ) {
					echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_SUMMARY_NEW.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				} else {
					echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_SUMMARY.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				}
					
				echo '<span class="faicon an_sigma large '.($_from == 'sums' ? '' : 'icon-blue_soft_link').'" title="' . $lang['srv_analysis_icon_sumary'] . '"></span> ';
				echo '</a>';
			}

			if ($options['sums_spec'] == true) {
				echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_SUMMARY_NEW.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				echo '<span class="faicon an_sigmax large '.($_from == 'sums*' ? '' : 'icon-blue_soft_link').'" title="' . $lang['srv_analysis_icon_frequency*'] . '"></span> ';
				echo '</a>';
			}

			if ($options['sums*'] == true) {
				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3) {
					echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_SUMMARY.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				}else {
					echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_SUMMARY_NEW.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				}
				echo '<span class="faicon an_freqx large '.($_from == 'sums*' ? '' : 'icon-blue_soft_link').'" title="' . $lang['srv_analysis_icon_frequency*'] . '"></span> ';
				echo '</a>';
			}
			if ($options['desc'] == true) {
				echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_DESCRIPTOR.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				echo '<span class="faicon an_stat large '.($_from == 'desc' ? '' : 'icon-blue_soft_link').'" title="' . $lang['srv_analysis_icon_descriptor'] . '"></span> ';
				echo '</a>';
			}
			if ($options['freq'] == true) {
				echo '<a href="#" onclick="showAnalizaSingleVarPopup(\''.$spid.'\',\''.M_ANALYSIS_FREQUENCY.'\',\''.$from_navedbe.'\',\''.self::$_CURRENT_LOOP['cnt'].'\'); return false;">';
				echo '<span class="faicon an_freq large '.($_from == 'freq' ? '' : 'icon-blue_soft_link').'" title="' . $lang['srv_analysis_icon_frequency'] . '"></span> ';
				echo '</a>';
			}

			// Ikona za prikaz grafa
			if($showChart == true && in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20,22)) && $_from != 'charts'){
				echo '<a href="#" onclick="showAnalizaSingleChartPopup(\''.$spid.'\',\''.M_ANALYSIS_CHARTS.'\'); return false;">';
				echo '<span class="faicon an_chart_bar icon-blue_soft_link" title="' . $lang['6'] . '"></span> ';
				echo '</a>';
			}

			// Ikona za vkljucitev v porocilo
			switch ($_from) {
				case 'sums':
				case 'sums*':
					$type=1;
					break;
				case 'freq':
					$type=2;
					break;
				case 'desc':
					$type=3;
					break;
				case 'charts':
					$type=4;
					break;
			}
			if ($showReport == true) {
				SurveyAnalysisHelper::getInstance()->addCustomReportElement($type, $sub_type=0, $spid);
			}


			echo '</span>'; 
		} else {
			
		}
	}



	/** polovi opisne za vse spremenljivke
	 *
	 */
	static public function getDescriptives() {
		global $site_path;
		$folder = $site_path . EXPORT_FOLDER.'/';
		#array za imeni tmp fajlov, ki jih nato izbrišemo
		$tmp_files = array(	'filtred'=>$folder . 'tmp_export_'.self::$sid.'_filtred'.TMP_EXT,
				'filtred1'=>$folder . 'tmp_export_'.self::$sid.'_filtred1'.TMP_EXT,
				'frequency'=>$folder . 'tmp_export_'.self::$sid.'_freqency'.'.php',
				'frequency1'=>$folder . 'tmp_export_'.self::$sid.'_freqency1'.'.php');

		# naredimo datoteko  z frekvencami
		# za windows sisteme in za linux sisteme

		# dodamo filter za loop-e
		if (isset(self::$_CURRENT_LOOP['filter']) && self::$_CURRENT_LOOP['filter'] != '') {
			$status_filter = self::$_CURRENT_STATUS_FILTER.' && '.self::$_CURRENT_LOOP['filter'];
		} else {
			$status_filter = self::$_CURRENT_STATUS_FILTER;
		}

		# s katero sekvenco se začnejo podatki, da ne delamo po nepotrebnem za ostala polja
		$start_sequence = (isset(self::$_HEADERS['_settings']['dataSequence']) && (int)self::$_HEADERS['_settings']['dataSequence'] > 0 )
		? (int)self::$_HEADERS['_settings']['dataSequence']
		: 8;

		if (IS_WINDOWS) {
			# sfiltriramo statuse
			# $cmdLn1 = 'awk -F"|" "BEGIN {{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '.$status_filter.' {for (i=4;i<=NF;i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}" '.self::$dataFileName. ' > '.$tmp_files['filtred'];
			# odstranimo '
			# $cmdLn2 = 'sed "s*\x27*`*g" '.$tmp_files['filtred'].' > '.$tmp_files['filtred1'];
			# v loopu naredimo frekvence za vsa polja razen za prva 3 ki so tako unikatna
			# $cmdLn3 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} { print \"$frequency[\",$1,\"]\",\"[\x27\",$2,\"\x27]\",\"=\x27\",$3,\"\x27;\"}" '.$tmp_files['filtred1'].' >> '.$tmp_files['frequency'];
				
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" "BEGIN {{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '.$status_filter.' {for (i='.$start_sequence.';i<=NF;i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}" '.self::$dataFileName;
			$command .= ' | sed "s*\x27*`*g"';
			$command .= ' | awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} { print \"$frequency[\",$1,\"]\",\"[\x27\",$2,\"\x27]\",\"=\x27\",$3,\"\x27;\"}"  >> '.$tmp_files['frequency'];
		} else {
			#$cmdLn1 = 'awk -F"|" \'BEGIN {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} '.$status_filter.' {for (i=4;i<=NF;i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}\' '.self::$dataFileName. ' > '.$tmp_files['filtred'];
			#$cmdLn2 = 'sed \'s*\x27*`*g\' '.$tmp_files['filtred'].' > '.$tmp_files['filtred1'];
			#$cmdLn3 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} { print "$frequency[",$1,"]","[\x27",$2,"\x27]","=\x27",$3,"\x27;"}\' '.$tmp_files['filtred1'].' >> '.$tmp_files['frequency'];

			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" \'BEGIN {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} '.$status_filter.' {for (i='.$start_sequence.';i<=NF;i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}\' '.self::$dataFileName;
			$command .= ' | sed \'s*\x27*`*g\'';
			$command .= ' | awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} { print "$frequency[",$1,"]","[\x27",$2,"\x27]","=\x27",$3,"\x27;"}\' >> '.$tmp_files['frequency'];
		}

		#$out1 = shell_exec($cmdLn1);
		#$out2 = shell_exec($cmdLn2);
		$file_handler = fopen($tmp_files['frequency'],"w");
		fwrite($file_handler,"<?php\n");
		fclose($file_handler);
		#$out3 = shell_exec($cmdLn3);

		$out = shell_exec($command);

		$file_handler = fopen($tmp_files['frequency'],"a");
		fwrite($file_handler,'?>');
		fclose($file_handler);
		include($tmp_files['frequency']);


		if (file_exists($tmp_files['frequency'])) {
			unlink($tmp_files['frequency']);
		}

		if ($_GET['debug'] == 1) {
			print_r("<pre>");
			print_r("cl:".$command);
			print_r("<br>Out".$out);
			print_r("</pre>");
		}

		# inicializiramo
		self::$_DESCRIPTIVES = array();

		# kateri odgovori so z profilom nastavljeni kot manjkajoči
		# se dodelijo k missing values in se ne upoštevajo pri povprečju
		$_invalidAnswers = self :: getInvalidAnswers (MISSING_TYPE_DESCRIPTOR);
		$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
		# izračunamo vse kar rabimo pri opisnih
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			# kadar imamo pri spremenljvki missinge, tudi prikazujemmo veljavne procente najprej damo na false
			# preverjamo da ni meta variabla
			$vars_count = count(self::$_FILTRED_VARIABLES);
		# frekvence delamo samo za izbrane variable
		if ($spremenljivka['tip'] != 'm'
				# filter po id spremenljivki
				&& ($vars_count == 0 || ($vars_count > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ) )
				# filter po tipu (kategorije, besedila, number, drugo)
				&& in_array($spremenljivka['tip'], self::$_FILTRED_TYPES ) )
		{ // if != m

			if (count($spremenljivka['grids'])>0) {
				foreach ($spremenljivka['grids'] AS $gid => $grid) {
					if (count($grid['variables']) > 0) {
						foreach ($grid['variables'] AS $vid => $variable ){
							$_sequence = $variable['sequence'];	# id kolone z podatki
							$_freq = $frequency[$_sequence];
							$min = null;
							$max = null;

							$_tmp_div = array();
							self::$_DESCRIPTIVES[$_sequence]['sum_xi_fi'] = null;
								
							#najprej odstranimo neveljavne, vse kaj ostane je veljavno
							foreach ($_invalidAnswers AS $ikey =>$iAnswer) {
								if (isset($_freq[$ikey])) {
									self::$_DESCRIPTIVES[$_sequence]['invalidCnt'] += $_freq[$ikey];
									self::$_DESCRIPTIVES[$_sequence]['allCnt'] += $_freq[$ikey];

									unset($_freq[$ikey]);

								}
							}
							# poiščemo minimum in maximum in povprečje

							# opcijske odgovore dodamo samo vprašanjem ki niso tipa other in text
							# zloopamo skozi vse opcije in jih dodamo k veljavnim
							if ($variable['text'] != true && $variable['other'] != true && count($spremenljivka['options']) > 0) {
								if (count($_freq) >  0) {

									foreach($_freq AS $fKey => $fCnt) {
										$flKey = (float)$fKey;

										if (is_numeric($flKey) && trim($flkey) != '' ) {

											self::$_DESCRIPTIVES[$_sequence]['validCnt'] += $fCnt;
											self::$_DESCRIPTIVES[$_sequence]['allCnt'] += $fCnt;

											$min = $min === null ? $flKey : min($min,$flKey) ;
											$max = $max === null ? $flKey : max($max,$flKey) ;
											self::$_DESCRIPTIVES[$_sequence]['sum_xi_fi'] += $flKey * $fCnt;
											#vrednosti si shranimo za računanje divergence
											$_tmp_div[$flKey] = $fCnt;
											unset($_freq[$fKey]);
										} else if (is_numeric($fKey) ) {
											self::$_DESCRIPTIVES[$_sequence]['validCnt'] += $fCnt;
											self::$_DESCRIPTIVES[$_sequence]['allCnt'] += $fCnt;

											$min = $min === null ? $fKey : min($min,$fKey) ;
											$max = $max === null ? $fKey : max($max,$fKey) ;
											self::$_DESCRIPTIVES[$_sequence]['sum_xi_fi'] += $fKey * $fCnt;
											#vrednosti si shranimo za računanje divergence
											$_tmp_div[$fKey] = $fCnt;
											unset($_freq[$fKey]);
												
										}
									}
								}
							}

							#porihtamo še numerične in datumske spremenljivke
							if (($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8 || $spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 18)
								&& ($variable['text'] != true && $variable['other'] != true)) {
								if (count($_freq) > 0) {
									foreach ($_freq AS $nkey => $nCnt) {
										$fnkey = (float)$nkey; # popravimo morebitne .
											
										if (is_numeric($nkey) && is_numeric($fnkey) && trim($fnkey) != '') {
											self::$_DESCRIPTIVES[$_sequence]['validCnt'] += $nCnt;
											self::$_DESCRIPTIVES[$_sequence]['allCnt'] += $nCnt;

											$min = $min != null ? min($min,$fnkey) : $fnkey;
											$max = $max != null ? max($max,$fnkey) : $fnkey;
											self::$_DESCRIPTIVES[$_sequence]['sum_xi_fi'] += $fnkey * $nCnt;
											#vrednosti si shranimo za računanje divergence
											$_tmp_div[$fnkey] = $nCnt;
											unset($_freq[$nkey]);
										}
									}
								}

							}
							# lahko bi še za datum

							# vse kaj ostane so textovni odgovori ali pa opcijski z nenumeričnim ključem
							if (count($_freq) > 0) {
								foreach ($_freq AS $tkey => $tCnt) {
									if (isset($_allMissing_answers[$tkey])) {
										$text = $_allMissing_answers[$tkey];
									} else {
										$text = $tkey;
									}
									self::$_DESCRIPTIVES[$_sequence]['valid'][$tkey] = array('text'=>$text,'cnt'=>$tCnt);

									# samo prištejemo veljavne
									self::$_DESCRIPTIVES[$_sequence]['validCnt'] += $tCnt;
									self::$_DESCRIPTIVES[$_sequence]['allCnt'] += $tCnt;
									unset($_freq[$tkey]);
								}
							}

							# minimum in maximum
							self::$_DESCRIPTIVES[$_sequence]['min'] = $min;
							self::$_DESCRIPTIVES[$_sequence]['max'] = $max;
							# povprečje

							if (isset(self::$_DESCRIPTIVES[$_sequence]['sum_xi_fi'])) {
								self::$_DESCRIPTIVES[$_sequence]['avg'] = self::$_DESCRIPTIVES[$_sequence]['validCnt'] > 0 ? self::$_DESCRIPTIVES[$_sequence]['sum_xi_fi'] / self::$_DESCRIPTIVES[$_sequence]['validCnt'] : 0;
							}
							#standardna diviacija
							if (isset (self::$_DESCRIPTIVES[$_sequence]['avg'])) {
								$N = self::$_DESCRIPTIVES[$_sequence]['validCnt'];
								$avg = self::$_DESCRIPTIVES[$_sequence]['avg'];
								$div = 0;
								$sum_pow_xi_fi_avg  = 0;
								foreach ($_tmp_div as $xi => $fi) {
									$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
								}
								self::$_DESCRIPTIVES[$_sequence]['div'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
							}
						}
					}
				}
			}
		} // end if tip != m
		} // end foreach
	}

	/** polovi frekvence za vse spremenljivke
	 *
	 */
	static public function getFrequencys($awk_filter = null) {
		global $site_path;
		$folder = $site_path . EXPORT_FOLDER.'/';

		# pobrišemo morebitne stare vrednosti
		self::$_FREQUENCYS = array();

		#array za imeni tmp fajlov, ki jih nato izbrišemo
		$tmp_files = array(	'frequency'=>$folder . 'tmp_export_'.self::$sid.'_freqency'.'.php');

			
		# dodamo filter za loop-e
		if (isset(self::$_CURRENT_LOOP['filter']) && self::$_CURRENT_LOOP['filter'] != '') {
			$status_filter = self::$_CURRENT_STATUS_FILTER.' && '.self::$_CURRENT_LOOP['filter'];
		} else {
			$status_filter = self::$_CURRENT_STATUS_FILTER;
		}

		# dodamo še dodaten awk filter če je nastavljen - (za break)
		if ($awk_filter != null) {
			$status_filter = '('.$status_filter.'&&'.$awk_filter.')';
		}

		# s katero sekvenco se začnejo podatki, da ne delamo po nepotrebnem za ostala polja
		$start_sequence = (isset(self::$_HEADERS['_settings']['dataSequence']) && (int)self::$_HEADERS['_settings']['dataSequence'] > 0 )
		? (int)self::$_HEADERS['_settings']['dataSequence']
		: 8;

		# s katero sekvenco se končajo podatki da ne delamo po nepotrebnem za ostala polja
		$end_sequence = $start_sequence;
		if (!empty(self::$_HEADERS))
		{
			foreach (self::$_HEADERS AS $skey => $spremenljivka) 
			{
				$tip = $spremenljivka['tip'];

				if (is_numeric($tip)) {

					if (count($spremenljivka['grids'] ) > 0) {

						foreach ($spremenljivka['grids'] as $gid => $grid ){

							if (is_countable($grid['variables']) && count($grid['variables']) > 0) {
                                
                                foreach ($grid['variables'] as $vid => $variable ){
									$end_sequence = max($end_sequence, (int)$variable['sequence']);
								}
							}
						}
					}
				}
			}
		}


		# naredimo datoteko  z frekvencami
		# za windows sisteme in za linux sisteme
		if (IS_WINDOWS ) {
			# TEST z LINUX načinom
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" "BEGIN {{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}" '.self::$dataFileName;
			$command .= ' | sed "s*\x27*`*g" ';
			$command .= ' | awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} { print \"$frequency[\",$1,\"]\",\"[\x27\",$2,\"\x27]\",\"=\x27\",$3,\"\x27;\"}" >> '.$tmp_files['frequency'];
		} 
		else {
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" \'BEGIN {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}\' '.self::$dataFileName;
			$command .= ' | sed \'s*\x27*`*g\' ';
			$command .= ' | awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} { print "$frequency[",$1,"]","[\x27",$2,"\x27]","=\x27",$3,"\x27;"}\' >> '.$tmp_files['frequency'];
		}
		
		$file_handler = fopen($tmp_files['frequency'],"w");
		fwrite($file_handler,"<?php\n");
		fclose($file_handler);

		$out = shell_exec($command);

		$file_handler = fopen($tmp_files['frequency'],"a");
		fwrite($file_handler,'?>');
		fclose($file_handler);
		include($tmp_files['frequency']);


		# pobrišemo sfiltrirane podatke, ker jih več ne rabimo
		if (file_exists($tmp_files['frequency'])) {
			unlink($tmp_files['frequency']);
		}


		# kateri odgovori so z profilom nastavljeni kot manjkajoči
		# se dodelijo k missing values in se ne upoštevajo pri povprečju
		# dodano se za break, ker drugace so bila v breaku negativna povprecja. Mozno da to ne bo ok za nekatere primere breaka??
		if (self::$podstran == M_ANALYSIS_BREAK || self::$podstran == M_ANALYSIS_SUMMARY || self::$podstran == M_ANALYSIS_SUMMARY_NEW || self::$podstran == 'sums' || self::$podstran == 'sums_rtf' || self::$podstran == 'sums_xls') {
			$_invalidAnswers = self :: getInvalidAnswers (MISSING_TYPE_DESCRIPTOR);
		} else {
			$_invalidAnswers = self :: getInvalidAnswers (MISSING_TYPE_FREQUENCY);
		}
		
		$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
		# izračunamo vse frekvence oziroma vse kar rabimo pri analizah
		if(!empty(self::$_HEADERS))
		foreach (self::$_HEADERS AS $spid => $spremenljivka) 
		{
			# kadar imamo pri spremenljvki missinge, tudi prikazujemmo veljavne procente najprej damo na false
			self::$_HEADERS[$spid]['show_valid_percent'] = false;
			# preverjamo da ni meta variabla
			$vars_count = count(self::$_FILTRED_VARIABLES);
			# frekvence delamo samo za izbrane variable
			if ($spremenljivka['tip'] != 'm'
				# filter po id spremenljivki
				&& ($vars_count == 0 || ($vars_count > 0 && isset(self::$_FILTRED_VARIABLES[$spid]) ) )
				# filter po tipu (kategorije, besedila, number, drugo)
				&& in_array($spremenljivka['tip'], self::$_FILTRED_TYPES ) ) {
				

				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
					self::$_HEADERS[$spid]['show_valid_percent'] = true;
				}
				if (count ($spremenljivka['grids']) > 0)
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
					if (count ($grid['variables']) > 0)	
						foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_freq = $frequency[$_sequence];
                        
                        #najprej dodamo neveljavne, vse kaj ostane je veljavno
						foreach ($_invalidAnswers AS $ikey =>$iAnswer) {
							if (self::$frequencyAddInvalid) {
								self::$_FREQUENCYS[$_sequence]['invalid'][$ikey] = $iAnswer;
							}
							if (isset($_freq[$ikey])) {
								if (self::$frequencyAddInvalid) {
									self::$_FREQUENCYS[$_sequence]['invalid'][$ikey]['cnt'] = $_freq[$ikey];
									self::$_FREQUENCYS[$_sequence]['invalidCnt'] += $_freq[$ikey];
									self::$_FREQUENCYS[$_sequence]['allCnt'] += $_freq[$ikey];
								}
								unset($_freq[$ikey]);
                                
                                # kadar imamo pri spremenljvki missinge, tudi prikazujemmo veljavne procente
								self::$_HEADERS[$spid]['show_valid_percent'] = true;
							}
						}
						# opcijske odgovore dodamo samo vprašanjem ki niso tipa other in text
						# zloopamo skozi vse opcije in jih dodamo k veljavnim
						if ($variable['text'] != true && $variable['other'] != true && is_countable($spremenljivka['options']) && count($spremenljivka['options']) > 0) {
							foreach ($spremenljivka['options'] AS $okey => $oAnswer) {
                                
                                self::$_FREQUENCYS[$_sequence]['valid'][$okey]['text'] = $oAnswer;
								self::$_FREQUENCYS[$_sequence]['valid'][$okey]['text_graf'] = $spremenljivka['options_graf'][$okey];
								self::$_FREQUENCYS[$_sequence]['valid'][$okey]['cnt'] = 0;
                                
                                if (isset($_freq[$okey])) {
									self::$_FREQUENCYS[$_sequence]['valid'][$okey]['cnt'] = $_freq[$okey];
									self::$_FREQUENCYS[$_sequence]['validCnt'] += $_freq[$okey];
									self::$_FREQUENCYS[$_sequence]['allCnt'] += $_freq[$okey];
                                    
                                    unset($_freq[$okey]);
								}
									
							}
						}

						# vse kaj ostane so textovni ali numerični odgovori
						if (is_countable($_freq) && count($_freq) > 0) {
							$_ifreq = array();
							# nardimo case-insensitive
							foreach ($_freq AS $tkey => $tCnt) {
															//if($spremenljivka['tip'] != 26)
															if($spremenljivka['tip'] != 26 && $spremenljivka['tip'] != 27)
								$tkey = mb_strtolower($tkey,'UTF-8');
															$_ifreq[$tkey] += $tCnt;
                            }
                            
                            $_average = array();
                            
                            if(is_countable($spremenljivka['options']))
                                $i = count($spremenljivka['options']) + 1;
                            else
                                $i = 0;
                            
							foreach ($_ifreq AS $tkey => $tCnt) {

								# preverimo ali je slučanjo odgovor missing vendar je določen kot veljavni odgovor
								if (isset($_allMissing_answers[$tkey])) {
								    $text = $tkey . ' '.$_allMissing_answers[$tkey];
                                } 
                                else {
									$text = $tkey;
								}

								self::$_FREQUENCYS[$_sequence]['valid'][$tkey] = array('text'=>$text,'cnt'=>$tCnt,'text_graf'=>$spremenljivka['options_graf'][$i]);
								self::$_FREQUENCYS[$_sequence]['validCnt'] += $tCnt;
								self::$_FREQUENCYS[$_sequence]['allCnt'] += $tCnt;
																//Uros dodal, ker se drugace pri radio ne ve, kaksen je text opcije drugo
																if($variable['other'])
																	self::$_FREQUENCYS[$_sequence]['valid'][$tkey]['other'] = $variable['naslov'];

								# povprečje
								if (is_numeric($tkey)) {
									$_average['product'] = $_average['product'] + ($tkey * $tCnt);
									$_average['cnt'] = $_average['cnt'] +  $tCnt;
								}
								unset($_freq[$tkey]);
								
								$i++;
							}
							self::$_FREQUENCYS[$_sequence]['average'] = ($_average['cnt'] > 0) ? $_average['product'] / $_average['cnt'] : $_average['cnt'];					
						}
						unset($frequency[$_sequence]);
					}
				}
			} // end if tip != m
		}
        unset($frequency);
        
		return self::$_FREQUENCYS;
	}

	static function frequencyAddInvalid($doAdd = true) {
		self::$frequencyAddInvalid = $doAdd;
	}
	/** polovi dejanske odgovore za spremenljivko
	 * @param $need_user_id - ali se rabi tudi vrniti user_id?
	 */
	static public function getAnswers($spremenljivka, $limit=10, $need_user_id = false) {
		global $site_path;

		$folder = $site_path . EXPORT_FOLDER.'/';
		$result = array();
		$sequences = array();
		$sequenc_filter = array();
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				if (count ($grid['variables']) > 0) {
					foreach ($grid['variables'] AS $vid => $variable ) {
						$sequences[] = '$'.$variable['sequence'];
						if (IS_WINDOWS ) {
							$sequenc_filter[] = '\''.$variable['sequence'].'\'=\x3E\",\"\x27\",$'.$variable['sequence'].',\"\x27';
						} else {
							$sequenc_filter[] = '\''.$variable['sequence'].'\'=\x3E","\x27",$'.$variable['sequence'].',"\x27';
						}
					}
				}
			}
		}

		# pobrišemo morebitne stare vrednosti
		#array za imeni tmp fajlov, ki jih nato izbrišemo
		$tmp_files = array(	'filtred'=>$folder . 'tmp_export_'.self::$sid.'_filtred'.TMP_EXT,
				'filtred1'=>$folder . 'tmp_export_'.self::$sid.'_filtred1'.TMP_EXT,
				'filtred_pagination'=>$folder . 'tmp_export_'.self::$sid.'_pagination'.TMP_EXT,
				'answers'=>$folder . 'tmp_export_'.self::$sid.'_answers'.'.php');

		# dodamo filter za loop-e
		if (isset(self::$_CURRENT_LOOP['filter']) && self::$_CURRENT_LOOP['filter'] != '') {
			$status_filter = self::$_CURRENT_STATUS_FILTER.' && '.self::$_CURRENT_LOOP['filter'];
		} else {
			$status_filter = self::$_CURRENT_STATUS_FILTER;
		}

		// Limit po novem omejimo z filtriranjem array-a
		//$_REC_LIMIT = ' NR==1,NR=='.$limit;

                //za tip lokacija (ne enota 3) se rabi user_id, ker se kasneje delajo linki
                $array_key = $need_user_id ? '{x=$1}' : '{x++}';
                
		# naredimo datoteko  z frekvencami
		# za windows sisteme in za linux sisteme
		if (IS_WINDOWS ) {
			# TEST z LINUX načinom
			# $cmdLn1 = 'awk -F"|" "BEGIN {{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '.$status_filter.' { print $0}" '.self::$dataFileName. ' > '.$tmp_files['filtred'];
			# $cmdLn2 = 'sed "s*\x27*`*g" '.$tmp_files['filtred'].' > '.$tmp_files['filtred1'];
			# $cmdLn4 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} {x++} { print \"$answers[\",x,\"]=array('.implode(',',$sequenc_filter).');\"}" '.$tmp_files['filtred1'].' >> '.$tmp_files['answers'];

			$command = 'awk -F"|" "BEGIN {{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '.$status_filter.' { print $0}" '.self::$dataFileName;
                        $command .= ' | sed "s*\x27*`*g"';
                        $command .= ' | awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$array_key.' { print \"$answers[\",x,\"]=array('.implode(',',$sequenc_filter).');\"}" >> '.$tmp_files['answers'];
		} else {
			# $cmdLn1 = 'awk -F"|" \'BEGIN {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} '.$status_filter.' {print $0}\' '.self::$dataFileName. ' > '.$tmp_files['filtred'];
			# $cmdLn2 = 'sed \'s*\x27*`*g\' '.$tmp_files['filtred'].' > '.$tmp_files['filtred1'];
			# $cmdLn4 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} {x++} { print "$answers[",x,"]=array('.implode(',',$sequenc_filter).');"}\' '.$tmp_files['filtred1'].' >> '.$tmp_files['answers'];

			$command = 'awk -F"|" \'BEGIN {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} '.$status_filter.' {print $0}\' '.self::$dataFileName;
			$command .= ' | sed \'s*\x27*`*g\'';
			$command .= ' | awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$array_key.' { print "$answers[",x,"]=array('.implode(',',$sequenc_filter).');"}\' >> '.$tmp_files['answers'];
                }
                
		#$out1 = shell_exec($cmdLn1);
		#$out2 = shell_exec($cmdLn2);
		$file_handler = fopen($tmp_files['answers'],"w");
		fwrite($file_handler,"<?php\n");
		fclose($file_handler);
		// limit po novem omejimo z filtriranjem arraya
		#$out3 = shell_exec($cmdLn3);
		#$out4 = shell_exec($cmdLn4);
		$out = shell_exec($command);

		$file_handler = fopen($tmp_files['answers'],"a");
		fwrite($file_handler,'?>');
		fclose($file_handler);
		include($tmp_files['answers']);//tukaj se deklarira spremenljivka $answers


		if (file_exists($tmp_files['answers'])) {
			unlink($tmp_files['answers']);
		}

		# PREFILTRIRAMO PODATKE PO POTREBI ODSTRANIMO MANJKAJOČE VREDNOSTI
		# zloopamo skozi celotne odgovore in odstranimo tiste ki so missingi

		# kateri odgovori so z profilom nastavljeni kot manjkajoči
		# se dodelijo k missing values in se ne upoštevajo pri povprečju
		$_invalidAnswers = self :: getInvalidAnswers (MISSING_TYPE_FREQUENCY);
		$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
		$_result_answers = array();
		$_result_answers['validCnt'] = 0;

		if (count($answers) > 0) {
			foreach ($answers AS $akey => $answer) {
				$cnt++;
				$all_invalid = true; # ali je vse neveljavno
				foreach ($answer AS $seq => $value) {
					# preverimo ali je kateri odgovor od userja vlejaven
					if (!isset($_allMissing_answers[$value]) && !isset($_invalidAnswers[$value])) {
					$all_invalid = false;
				}
				# ločimo odgovore na veljavne in nevlejavne
				}

				if ($all_invalid == FALSE) {
					# imamo vsaj en veljaven odgovor
					if ($limit == -1 || $_result_answers['validCnt'] < $limit) {
                                            //ce smo nastavili $need_user_id, vkljucimo user_id kot key odgovorov v valid array
                                            if(!$need_user_id)
                                                $_result_answers['valid'][] = $answer;
                                            else
                                                $_result_answers['valid'][$akey] = $answer; 
				}
				# koliko je vseh veljavnih
				$_result_answers['validCnt']++;
				} else {
					# vsi odgovori so neveljavni
					$_result_answers['invalid'][] = $answer;
					$_result_answers['invalidCnt']++;
				}
				$_result_answers['allCnt']++;
			}
		}
		return $_result_answers;
	}

	public static function showspremenljivkaSingleVarPopup($id) {
		global $lang;
		
		self::$_forceShowEmpty = true;
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			
		$anketa = $_REQUEST['anketa'];
		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="minify/g=jsnew"></script>';
		echo '<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		#  vse elemente forem
		echo "<script>"."\n";
		echo "$(document).ready(function(){ $('#div_analiza_single_var input:[type=radio], #div_analiza_single_var input:[type=checkbox], #div_analiza_single_var input:[type=text], #div_analiza_single_var select, #div_analiza_single_var textarea').attr('disabled',true); })"."\n";
		echo "</script>";
		echo '</head>';

		echo '<body onBlur="window.close()" style="margin:5px; padding:5px;">';
		echo '<input type="hidden" name="podstran" id="srv_meta_podstran" value="' . $zaPodstran . '" />';
		echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $_REQUEST['anketa'] . '" />';
		$id = $_POST['id'];
		$spremenljivka = self::$_HEADERS[$id];
		$_tip = self::getSpremenljivkaLegenda($spremenljivka,'tip');
		$zaPodstran = $_POST['zaPodstran'];
		
		$legend = Cache::spremenljivkaLegenda($id);
		
		echo '<span class="spaceRight">';
		self::showIcons($id,$spremenljivka,'desc');
		echo '</span>';
		echo '<span class="spaceRight">'.$lang['srv_analiza_opisne_variable_type'].': ';
		echo $_tip.' ';
		echo '('.$legend['izrazanje'].' - '.$legend['lestvica'].')';
		echo ' </span>';
		echo '<br class="clr"/>';
		echo '<br class="clr"/>';
		echo '<div id="div_analiza_single_var" class="container"> ';
		self:: showPreviewSpremenljivka($id);
		echo '</div>';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_win(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="icons/icons/printer.png" alt="'.$lang['hour_print2'].'" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '</div>';
		echo '</body>';
		echo '</html>';
	}

	public static function showSpremenljivkaTextAnswersPopup($id,$seq) {
		global $lang;
		self::$_forceShowEmpty = true;
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

		$anketa = $_REQUEST['anketa'];

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="minify/g=jsnew"></script>';
		echo '<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';

		echo '<body onBlur="window.close()" style="margin:5px; padding:5px;">';

		echo '<input type="hidden" name="podstran" id="srv_meta_podstran" value="' . $zaPodstran . '" />';
		echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $_REQUEST['anketa'] . '" />';
		echo '<div id="div_analiza_single_var" class="container">';
		$id = $_POST['id'];
		$seq = $_POST['seq'];
		$zaPodstran = $_POST['zaPodstran'];
		$spremenljivka = self::$_HEADERS[$id];
		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
		
		$num_show_records = 9999999;
		# poiščemo navedbe textovne spremenljivke tako kot v grafih
		$_answers = self::getAnswers($spremenljivka,$num_show_records);
		if (count($_answers['valid']) > 0) {
			echo '<table class="anl_tbl anl_bl anl_bt tbl_clps">';
			foreach ($_answers['valid'] AS $vkey => $valid) {
				$_valid = $valid[$seq];


				echo '<tr><td class="anl_bck_0_1 anl_br anl_bb anl_user_text">';
				echo $_valid;
				echo '</td></tr>';
			}
			echo '</table>';
			echo '<br />';
		}
		echo '</div>';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_win(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="icons/icons/printer.png" alt="'.$lang['hour_print2'].'" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '</div>';
		echo '</body>';
		echo '</html>';
	}

	/** Prikaže opsine, frekvence, sumarnik, za samo eno variablo
	 *
	 * @param unknown_type $id
	 */
	public static function DisplaySingleVarPopup ($id,$zaPodstran) 
	{
		global $site_url, $lang;
		self::$_forceShowEmpty = true;
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		
		$anketa = $_REQUEST['anketa'];

		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="minify/g=jsnew"></script>';
		echo '<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;} .chart_settings {display: none;} .chart_holder{width: 800px;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';

		#echo '<body onBlur="window.close()" style="margin:5px; padding:5px;">';
		echo '<body style="margin:5px; padding:5px;">';
		echo '<input type="hidden" name="podstran" id="srv_meta_podstran" value="' . $zaPodstran . '" />';
		echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $_REQUEST['anketa'] . '" />';
		
		echo '<div id="div_analiza_single_var" class="container">';
		$id = $_POST['id'];
		$zaPodstran = $_POST['zaPodstran'];

		# polovimo nastavtve missing profila
		self::$missingProfileData = SurveyMissingProfiles::getProfile(self::$currentMissingProfile);
		if (self::$podstran != M_ANALYSIS_ARCHIVE) 
		{
			self::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
		}
		if (!is_array(self::$_LOOPS))
		{
			self::$_LOOPS[] = array('filter'=>null,'text'=>null);
		}

		$loop_cnt = 0;
		# ce mamo zanke
		foreach ( self::$_LOOPS AS $loop) 
		{
			if ($loop['filter'] != null)
			{
				$loop_cnt++;
				$loop['cnt'] = $loop_cnt;
				self::$_CURRENT_LOOP = $loop;
				if ((int)$loop_cnt == (int)$_POST['loop'])
				{
				
					echo '<h2 data-loopId="'.self::$_CURRENT_LOOP['cnt'].'">'.$lang['srv_zanka_note'].$loop['text'].'</h2>';
				}
			}
			if ((int)$loop_cnt == (int)$_POST['loop'])
			{
				switch ($zaPodstran)
				{
					case M_ANALYSIS_SUMMARY_NEW :
						self::displaySumsNew($id);
						$export = 'sums';
						break;
					case M_ANALYSIS_SUMMARY :
						self::displaySums($id);
						$export = 'sums';
						break;
					case M_ANALYSIS_DESCRIPTOR :
						self::displayDescriptives($id);
						$export = 'statistics';
						break;
					case M_ANALYSIS_FREQUENCY :
						self::displayFrequency($id);
						$export = 'frequency';
						break;
					case M_ANALYSIS_CHARTS :
						$chartClass = new SurveyChart();
						$chartClass->Init($anketa);
						$chartClass->displaySingle($id);
						$export = 'charts';
						break;
				}
				//Izvoz v PDF/RTF
				$loop_exp = (isset(self::$_CURRENT_LOOP)) ? self::$_CURRENT_LOOP['cnt'] : 'undefined';
				$_url1 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
						serialize(
								array(	'b'=>'export',
										'm'=>$export,
										'anketa'=>$anketa,
										'sprID'=>$id,
										'loop'=>$loop_exp)));
				$_url2 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
						serialize(
								array(	'b'=>'export',
										'm'=>$export.'_rtf',
										'anketa'=>$anketa,
										'sprID'=>$id,
										'loop'=>$loop_exp)));
				$_url3 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
						serialize(
								array(	'b'=>'export',
										'm'=>$export.'_xls',
										'anketa'=>$anketa,
										'sprID'=>$id,
										'loop'=>$loop_exp)));
				
				echo '<div id="single_export" class="printHide">';
				echo '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf"></span></a>';
				echo '&nbsp;&nbsp;<a href="'.$_url2.'" target="_blank"><span class="faicon rtf"></span>&nbsp;</a>';
				//if($export == 'frequency')
				if($zaPodstran  != M_ANALYSIS_CHARTS )
				{
					echo '&nbsp;&nbsp;<a href="'.$_url3.'" target="_blank"><span class="faicon xls"></span>&nbsp;</a>';
				}
			}
			
		}
					
		echo '</div>';
		echo '<div class="clr"></div>';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_win(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="icons/icons/printer.png" alt="'.$lang['hour_print2'].'" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '</div>';
		echo '</body>';
		echo '</html>';
	}

	/** Sestavi array nepravilnih odgovorov
	 *
	 */
	static function getInvalidAnswers($type) {
		$result = array();
		$missingValuesForAnalysis = SurveyMissingProfiles :: GetMissingValuesForAnalysis($type);

		foreach ($missingValuesForAnalysis AS $k => $answer) {
			$result[$k] = array('text'=>$answer,'cnt'=>0);
		}
		return $result;
	}


	static function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		# polovimo decimalna mesta in vejice za tisočice

		$decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
		$thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			
		$result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;

		return $result;
	}

	static function getSpremenljivkaLegenda ($spremenljivka, $what='') {
		
		$legenda = Cache::spremenljivkaLegenda($spremenljivka['spr_id']);
		
		return $legenda[$what];
	}


	/** razdelek za Ajax klice
	 *
	 */
	public function ajax() {
		if (isset ($_POST['corssVar1']))
			$corssVar1 = $_POST['corssVar1'];
		if (isset ($_POST['corssVar2']))
			$corssVar2 = $_POST['corssVar2'];
		if (isset ($_POST['corssZanka']))
			$corssZanka = $_POST['corssZanka'];
		if (isset ($_POST['crossChk0']))
			$crossChk0 = $_POST['crossChk0'];
		if (isset ($_POST['crossChk1']))
			$crossChk1 = $_POST['crossChk1'];
		if (isset ($_POST['crossChk2']))
			$crossChk2 = $_POST['crossChk2'];
		if (isset ($_POST['crossChk3']))
			$crossChk3 = $_POST['crossChk3'];
		if (isset ($_POST['crossChkEC']))
			$crossChkEC = $_POST['crossChkEC'];
		if (isset ($_POST['crossChkRE']))
			$crossChkRE = $_POST['crossChkRE'];
		if (isset ($_POST['crossChkSR']))
			$crossChkSR = $_POST['crossChkSR'];
		if (isset ($_POST['crossChkAR']))
			$crossChkAR = $_POST['crossChkAR'];
		if (isset ($_POST['doColor']))
			$doColor = $_POST['doColor'];

		switch ($_GET['a']) {
			case 'loadMissingProfile' :
				self :: loadMissingProfile();
				break;
			case 'reloadData' :
				self :: Display();
				break;
			case 'showAnalizaSingleVarPopup' :
				self :: DisplaySingleVarPopup($_POST['id'],$_POST['zaPodstran']);
				break;
			case 'showspremenljivkaSingleVarPopup' :
				self :: showspremenljivkaSingleVarPopup($_POST['id']);
				break;
			case 'showSpremenljivkaTextAnswersPopup' :
				self :: showSpremenljivkaTextAnswersPopup($_POST['id'],$_POST['seq']);
				break;
			case 'show_crostabs_dropdowns' :
				self :: displayDropdowns($corssVar1, $corssVar2, $corssZanka);
				break;
			case 'show_crostabs_table' :
				self :: displayCrosstabsTable($corssVar1, $corssVar2, $corssZanka, $crossChk0, $crossChk1, $crossChk2, $crossChk3, $crossChkEC, $crossChkRE, $crossChkSR, $crossChkAR, $doColor);
				break;
			case 'preview_spremenljivka' :
				self:: showPreviewSpremenljivka($_POST['spremenljivka']);
				break;
			case 'printPreview_spremenljivka' :
				self:: printPreviewSpremenljivka($_POST['id']);
				break;
			case 'toggleAnalysisAdvanced' :
				self:: toggleAnalysisAdvanced();
				break;
			case 'changeAnalizaPreview' :
				self:: changeAnalizaPreview();
				break;
			case 'show_spid_more_table' :
				self:: show_sum_more_table();
				break;
			case 'changeSpremenljivkaLestvica' :
				self:: changeSpremenljivkaLestvica();
				break;
			default:
				echo 'Error! (class: SurveyAnalysis->ajax() - missing action)';
				break;
		}
	}
	/** izpiše linke - povezave do pdf ,rtf datotek vprašalnika in analiz
	 *
	 */
	public static function DisplayReportsLinks() {
		global $lang, $global_user_id;
		
		if (self::$dataFileStatus == FILE_STATUS_NO_DATA || self::$dataFileStatus == -3 || self::$noHeader == true) {
			return false;
		}
		
		SurveyUserSetting :: getInstance()->Init(self::$sid, $global_user_id);

		SurveyAnalysis::DisplayFilters();
		
		/*
		// Link na navadna porocila
		echo '<div id="custom_report_switch" class="creport"><a href="index.php?anketa='.self::$sid.'&a=analysis&m=analysis_links"><span>'.$lang['srv_standard_report'].'</span></a></div>';	
		// Link na porocilo po meri
		echo '<div id="custom_report_switch"><a href="index.php?anketa='.self::$sid.'&a=analysis&m=analysis_creport"><span>'.$lang['srv_custom_report'].'</span></a></div>';
		*/

		echo '<table class="analysis_reports"><tr>';

		// ANALIZE
		echo '<td>';
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_analiza'].'</legend>';

		# linki - analize sumarnik
		echo '<span class="subtitle">' . $lang['srv_sumarnik'] . '</span>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums&anketa='.self::$sid).'" target="_blank">' .
				'<span class="faicon pdf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums_rtf&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon rtf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;DOC - (Microsoft Word)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums_xls&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon xls" title="' . $lang['srv_reporti'] . '"></span>&nbsp;XLS - (Microsoft Excel)</a>';

		# linki - analize opisne statistike
		echo '<span class="subtitle">' . $lang['srv_descriptor'] . '</span>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon pdf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics_rtf&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon rtf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;DOC - (Microsoft Word)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics_xls&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon xls" title="' . $lang['srv_reporti'] . '"></span>&nbsp;XLS - (Microsoft Excel)</a>';

		# linki - analize frekvence
		echo '<span class="subtitle">' . $lang['srv_frequency'] . '</span>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon pdf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency_rtf&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon rtf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;DOC - (Microsoft Word)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency_xls&anketa=' . self::$sid).'" target="_blank">' .
				'<span class="faicon xls" title="' . $lang['srv_reporti'] . '"></span>&nbsp;XLS - (Microsoft Excel)</a>';

		echo '</fieldset>';

		// VPRASALNIK
		echo '</td><td>';
		echo '<fieldset style="padding-top: 10px;">';
		echo '<legend>'.$lang['srv_analysis_links_survey'].'</legend>';

		# linki - vprašalnik
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=' . A_REPORT_VPRASALNIK_PDF . '&anketa=' . self::$sid) . '" target="_blank">' .
		'<span class="faicon pdf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=' . A_REPORT_VPRASALNIK_RTF . '&anketa=' . self::$sid) . '" target="_blank">' .
				'<span class="faicon rtf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;DOC - (Microsoft Word)</a>';

		echo '</fieldset>';

		// IZPIS
		echo '</td><td>';
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_statistic'].'</legend>';

		# linki - vpogled
		echo '<span class="subtitle">' . $lang['srv_analysis_links_vpogled'] . '</span>';
		echo '<a href="index.php?anketa='.self::$sid.'&a=data&m=quick_edit&quick_view=1" >' .
				'<span title="' . $lang['srv_link_data_view'] . '"></span>' . $lang['srv_link_data_view'] . '</a>';

		# linki - izpis vseh odgovorov
		echo '<span class="subtitle">' . $lang['srv_analysis_links_allAnswers'] . '</span>';
		echo '<span class="clr">' . $lang['srv_analysis_links_allAnswers_note'] . '</span><br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=pdf_results&anketa=' . self::$sid) . '" target="_blank">' .
				'<span class="faicon pdf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;PDF - (Adobe Acrobat)</a>';
		echo '<br/>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=rtf_results&anketa=' . self::$sid) . '" target="_blank">' .
				'<span class="faicon rtf" title="' . $lang['srv_reporti'] . '"></span>&nbsp;DOC - (Microsoft Word)</a>';

		echo '</fieldset>';
		echo '</td>';

		echo '</tr></table>';
	}

	private static function printAnalizaSingleVar() {
		global $lang;
		
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		
		$anketa = $_REQUEST['anketa'];
		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="minify/g=jsnew"></script>';
		echo '<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';

		echo '<body onBlur="window.close()" style="margin:5px; padding:5px;">';
		echo '<div class="container"> ';
		$id = $_POST['id'];
		$zaPodstran = $_POST['zaPodstran'];
		switch ($zaPodstran) {
			case M_ANALYSIS_SUMMARY_NEW :
				self::displaySumsNew($id);
				break;
			case M_ANALYSIS_SUMMARY :
				self::displaySums($id);
				break;
			case M_ANALYSIS_DESCRIPTOR :
				self::displayDescriptives($id);
				break;
			case M_ANALYSIS_FREQUENCY :
				self::displayFrequency($id);
				break;
		}
		echo '</div>';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_win(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="print_win(); return false;"><span><img src="icons/icons/printer.png" alt="'.$lang['hour_print2'].'" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<br class="clr"/>';
		echo '</div>';
		echo '</body>';
		echo '</html>';

	}

	function showPreviewSpremenljivka($spremenljivka) {
		global $lang, $site_path;
		
		SurveyInfo :: getInstance()->SurveyInit($anketa);

		$offset = 0;
		$zaporedna = 0;
		$count_type = SurveyInfo :: getInstance()->getSurveyCountType();

		if ($count_type) {

			// Preštejemo koliko vprašanj je bilo do sedaj
			$sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = (SELECT gru_id FROM srv_spremenljivka WHERE id = '" . $spremenljivka . "')");
			$rowg = mysqli_fetch_assoc($sqlg);
			$vrstni_red = $rowg['vrstni_red'];

			$sqlCountPast = sisplet_query("SELECT count(*) as cnt FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . self :: $sid . "' AND s.gru_id=g.id AND g.vrstni_red < '$vrstni_red' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
			$rowCount = mysqli_fetch_assoc($sqlCountPast);
			$offset = $rowCount['cnt'];

			// poiscemo vprasanja / spremenljivke
			$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id=(SELECT gru_id FROM srv_spremenljivka WHERE id = '" . $spremenljivka . "') AND visible='1' ORDER BY vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {
				if ($row['id'] == $spremenljivka) {
					$zaporedna++;
					break;
				}
			}
		}

		echo '<div id="preview_spremenljivka">';

        include_once('../../main/survey/app/global_function.php');
        new \App\Controllers\SurveyController(true);

		if (isset($_POST['lang_id'])) {
			save('lang_id', (int)$_POST['lang_id']);
		}
		echo '  <div  id="spremenljivka_preview">';
		if ( $spremenljivka == -1 ) {
            \App\Controllers\BodyController::getInstance()->displayIntroduction();
		}
		elseif ( $spremenljivka == -2 ) {
            \App\Controllers\BodyController::getInstance()->displayKonec();
		}
		elseif ( $spremenljivka == -3 ) {
            \App\Controllers\StatisticController::displayStatistika();
		}
		else {
            save('forceShowSpremenljivka', true);
            \App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($spremenljivka, $offset, $zaporedna);
		}
		echo '  </div>';
		echo '<div class="clr"></div>';

		echo '</div>';


	}

	function printPreviewSpremenljivka($spremenljivka) {
		global $lang;
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			
		$anketa = $_REQUEST['anketa'];
		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="minify/g=jsnew"></script>';
		echo '<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';
		echo '<body onBlur="window.close()" style="margin:5px; padding:5px;" >';

		global $lang, $site_path;

		SurveyInfo :: getInstance()->SurveyInit($anketa);

		$offset = 0;
		$zaporedna = 0;
		$count_type = SurveyInfo :: getInstance()->getSurveyCountType();

		if ($count_type) {

			// Preštejemo koliko vprašanj je bilo do sedaj
			$sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = (SELECT gru_id FROM srv_spremenljivka WHERE id = '" . $spremenljivka . "')");
			$rowg = mysqli_fetch_assoc($sqlg);
			$vrstni_red = $rowg['vrstni_red'];

			$sqlCountPast = sisplet_query("SELECT count(*) as cnt FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . self :: $sid . "' AND s.gru_id=g.id AND g.vrstni_red < '$vrstni_red' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
			$rowCount = mysqli_fetch_assoc($sqlCountPast);
			$offset = $rowCount['cnt'];

			// poiscemo vprasanja / spremenljivke
			$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id=(SELECT gru_id FROM srv_spremenljivka WHERE id = '" . $spremenljivka . "') AND visible='1' ORDER BY vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {
				if ($row['id'] == $spremenljivka) {
					$zaporedna++;
					break;
				}
			}
		}


        include_once('../../main/survey/app/global_function.php');
        new \App\Controllers\SurveyController(true);

		if (isset($_POST['lang_id'])) {
			save('lang_id', (int)$_POST['lang_id']);
		}
		echo '<div id="spremenljivka_preview" class="container">';
		if ( $spremenljivka == -1 ) {
            \App\Controllers\BodyController::getInstance()->displayIntroduction();
		}
		elseif ( $spremenljivka == -2 ) {
            \App\Controllers\BodyController::getInstance()->displayKonec();
		}
		elseif ( $spremenljivka == -3 ) {
            \App\Controllers\StatisticController::displayStatistika();
		} else {
            save('forceShowSpremenljivka', true);
            \App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($_GET['spremenljivka']);
		}

		echo '</div>';//  id="spremenljivka_preview"
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_win(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="print_win(); return false;"><span><img src="icons/icons/printer.png" alt="'.$lang['hour_print2'].'" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<br class="clr"/>';
		echo '</div>';
		echo '</body>';
		echo '</html>';

	}

	static function show_sum_more_table() {
		self :: $show_spid_div = false;
		self::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
		if (count(self::$_LOOPS) == 0 ) {
			if ($_POST['podstran'] == M_ANALYSIS_SUMMARY) {
				self :: displaySums($_POST['spid']);
			} else if ($_POST['podstran'] == M_ANALYSIS_SUMMARY_NEW) {
				self :: displaySumsNew($_POST['spid']);
			} else if ($_POST['podstran'] == M_ANALYSIS_FREQUENCY) {
				self :: displayFrequency($_POST['spid']);
			}
		} else {
			# če mamo zanke
			$loop_cnt = 0;
			foreach ( self::$_LOOPS AS $loop) {
				$loop_cnt++;
				$loop['cnt'] = $loop_cnt;
				self::$_CURRENT_LOOP = $loop;
				
				if ($loop['cnt'] == $_POST['loop_id']) {
					if ($_POST['podstran'] == M_ANALYSIS_SUMMARY) {
						self :: displaySums($_POST['spid']);
					} else if ($_POST['podstran'] == M_ANALYSIS_SUMMARY_NEW) {
						self :: displaySumsNew($_POST['spid']);
					} else if ($_POST['podstran'] == M_ANALYSIS_FREQUENCY) {
						self :: displayFrequency($_POST['spid']);
					}
				}
			}	
		}
		
		echo '<script type="text/javascript" charset="utf-8">
				analiza_init ();
			</script>';
	}

	static function  getNumRecords() {
		if (isset($_POST['num_records']) && (int)$_POST['num_records'] > 0) {
			$result = (int)self::$textAnswersMore[$_POST['num_records']];
		} else {
			$result = (int)SurveyDataSettingProfiles :: getSetting('numOpenAnswers');
		}
		return $result;
	}


	/** @desc: Prikaže vsebino diva za izbiro filtriranja
	 *
	 */
	function showFilterProfiles ($pid = -1) {
		global $lang;
		 
		// profili za filtriranje
		echo '<div style="float:left; width:auto; text-align: center;">';
		 
		echo '<span class="as_link" id="link_filter_profile" title="'.$lang['srv_analiza_filter'].'">'.$lang['srv_analiza_filter'].'</span><br />';
		SurveyFilterProfiles::Init(self::$sid, $global_user_id);
		$current_filter_profiles = SurveyFilterProfiles::getCurrentProfile();
		$available_filter_profiles = SurveyFilterProfiles::getAvailableProfiles();
		 
		echo '<span id="div_analiza_filter_profile_dropdown">';
		echo '<select id="analiza_current_filter_profile" name="analize_current_filter_profile" onchange="changeFilterProfileDropdown();">';
		foreach ($available_filter_profiles AS $key => $val) {
			echo '  <option value="'.$val['id'].'"'.($val['id']==$current_filter_profiles['id']?' selected="selected"':'').'>'.$val['name'].'</option>';
		}
		echo '</select>';
		echo '</span>';
		 
		echo '</div>';
	}

	/**
	 *
	 * # odstranimo sistemske variable tipa email, ime, priimek, geslo oz. ce imamo vklopljeno nastavitev da skrivamo vse sistemske skrijemo vse sistem == 1
	 */
	static function removeSystemVariables() {
		if (!empty(self::$_HEADERS))
		{
			foreach (self::$_HEADERS AS $skey => $spremenljivka) {
				if ((int)$spremenljivka['hide_system'] == 1 && in_array($spremenljivka['variable'],array('email','ime','priimek','telefon','naziv','drugo'))) {
					unset(self::$_HEADERS[$skey]);
				}		
				else if ((int)$spremenljivka['sistem'] == 1 && SurveyDataSettingProfiles :: getSetting('hideAllSystem') == 1) {
					unset(self::$_HEADERS[$skey]);
				}
			}
		}
	}

	/*
	 * posortiramo veljavne odgovore kronološko, po datumu
	*
	*/
	static function sortTextValidAnswers($_spid,$variable,$answers) {
		if (is_string($answers)) {
			$answers = mb_strtolower($answers,'UTF-8');
		}

		# Polovimo kronološki potek odgovorov
		$spid = explode('_',$_spid);
		$spid = $spid[0];
		$result = array();
		 
		$string = "SELECT distinct TRIM(REPLACE(REPLACE(REPLACE(sdt.text,'\n',' '),'\r',' '),'|',' ')) as text FROM srv_data_text".self::$db_table." AS sdt JOIN srv_user AS u ON sdt.usr_id = u.id WHERE sdt.spr_id = '".$spid."' AND sdt.vre_id = '".$variable['vr_id']."' ORDER BY u.time_insert ASC";
		 
		$sql = sisplet_query($string);
		while ( list($text) = mysqli_fetch_row($sql) ) {
			$text = mb_strtolower($text,'UTF-8');
			$text = str_replace('\'','`',addslashes(strip_tags($text)));
			if (isset($answers[$text])) {
				$result[$text] = $answers[$text];
			}
		}
		
		return $result;
	}

	static function setUpReturnAsHtml($returnAsHtml = false) {
		self::$returnAsHtml = $returnAsHtml;					# ali vrne rezultat analiz kot html ali ga izpiše
	}
	static function setUpIsForArchive($isArchive = false) {
		self::$isArchive = $isArchive;					# nastavimo da smo v arhivu
	}
	static function setForceShowEmpty($_forceShowEmpty = false) {
		self::$_forceShowEmpty = $_forceShowEmpty;
	}


	function showChartColorProfiles(){
		global $lang;
		$skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');
		echo '<span style="display: inline; font-weight: bold;">'.$lang['srv_chart_skin_long'].'</span><br/>';
		echo '<span style="font-size: 10px; font-style: italic;">'.$lang['srv_chart_skin_info'].'</span><br/>';
		echo '<select id="chart_skin" name="chart_skin" onchange="changeChartGlobalSettings(\'skin\', this.value); return false;" >';
		echo '<option' . ($skin == 0 ? ' selected="selected"' : '') . ' value="0">'.$lang['srv_chart_skin_0'].'</option>';
		echo '<option' . ($skin == 1 ? ' selected="selected"' : '') . ' value="1">'.$lang['srv_chart_skin_1'].'</option>';
		echo '<option' . ($skin == 6 ? ' selected="selected"' : '') . ' value="6">'.$lang['srv_chart_skin_6'].'</option>';
		echo '<option' . ($skin == 2 ? ' selected="selected"' : '') . ' value="2">'.$lang['srv_chart_skin_2'].'</option>';
		echo '<option' . ($skin == 3 ? ' selected="selected"' : '') . ' value="3">'.$lang['srv_chart_skin_3'].'</option>';
		echo '<option' . ($skin == 4 ? ' selected="selected"' : '') . ' value="4">'.$lang['srv_chart_skin_4'].'</option>';
		echo '<option' . ($skin == 5 ? ' selected="selected"' : '') . ' value="5">'.$lang['srv_chart_skin_5'].'</option>';
		echo '</select>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_chartColor(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
	}

	function toggleAnalysisAdvanced() {
		 
		session_start();
		$_SESSION['AnalysisAdvancedLinks'][self::$sid] = ($_POST['what'] == 1) ? true : false;
		$SSH = new SurveyStaticHtml(self::$sid);
		 
		# izrišemo desne linke do posameznih nastavitev
		$SSH -> displayAnalizaRightOptions($_POST['podstran'],true);

	}

	function changeAnalizaPreview() {
		global $global_user_id;
		UserSetting :: getInstance()->Init($global_user_id);
		UserSetting:: getInstance()->setUserSetting('showAnalizaPreview', (int)$_POST['value'] );
		UserSetting:: getInstance()->saveUserSetting();
		$SSH = new SurveyStaticHtml(self::$sid);
		$SSH -> displayAnalizaSubNavigation(false);
		 
	}

	static function addCustomReportElement($type, $sub_type, $spr1, $spr2=''){
		SurveyAnalysisHelper::getInstance()->addCustomReportElement($type, $sub_type=0, $spid);
	}
	
	// Nastavitve na dnu
	static function displayBottomSettings($page){
		global $site_path;
        global $lang;
        global $global_user_id;
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);   
		
		$lan_print = ' title="'.$lang['PRN_Izpis'].'"';
		$lan_pdf = ' title="'.$lang['PDF_Izpis'].'"';
		$lan_rtf = ' title="'.$lang['RTF_Izpis'].'"';
		$lan_xls = ' title="'.$lang['XLS_Izpis'].'"';
		
		echo '<div class="analysis_bottom_settings printHide">';
		
		// Pri javni povezavi nimamo tega
		if(self::$printPreview == false){
			// Nastavitve na dnu pri sumarniku
			if($page == 'sums'){
									
				echo '<a href="#" onClick="addCustomReportAllElementsAlert(1);" title="'.$lang['srv_custom_report_comments_add_hover'].'" class="'.(!$userAccess->checkUserAccess('analysis_analysis_creport') ? 'user_access_locked' : '').'" user-access="analysis_analysis_creport" style="margin-right: 40px;"><span class="spaceRight faicon comments_creport" ></span><span class="bold">'.$lang['srv_custom_report_comments_add'].'</span></a>';
				
				echo '<a href="#" onClick="printAnaliza(\'Sumarnik\'); return false;"'.$lan_print.' class="srv_ico"><span class="faicon print icon-grey_dark_link"></span></a>';
                
                echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums&anketa=' . self::$sid) . '" target="_blank"'.$lan_pdf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon pdf black very_large"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums_rtf&anketa=' . self::$sid) . '" target="_blank"'.$lan_rtf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon rtf black very_large"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums_xls&anketa=' . self::$sid) . '" target="_blank"'.$lan_xls.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon xls black very_large"></span></a>';								
				
				echo '<a href="#" onclick="doArchiveAnaliza();" title="'.$lang['srv_analiza_arhiviraj_ttl'].'" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv black very_large"></span></a>';
				echo '<a href="#" onclick="createArchiveBeforeEmail();" title="'.$lang['srv_analiza_arhiviraj_email_ttl'] . '" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv_mail black very_large"></span></a>';			
			}	
			
			// Nastavitve na dnu pri frekvencah
			elseif($page == 'freq'){
				
				echo '<a href="#" onClick="addCustomReportAllElementsAlert(2);" title="'.$lang['srv_custom_report_comments_add_hover'].'" class="'.(!$userAccess->checkUserAccess('analysis_analysis_creport') ? 'user_access_locked' : '').'" user-access="analysis_analysis_creport" style="margin-right: 40px;"><span class="spaceRight faicon comments_creport" ></span><span class="bold">'.$lang['srv_custom_report_comments_add'].'</span></a>';
				
				echo '<a href="#" onClick="printAnaliza(\'Frekvence\'); return false;"'.$lan_print.' class="srv_ico"><span class="faicon print icon-grey_dark_link"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency&anketa=' . self::$sid) . '" target="_blank"'.$lan_pdf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon pdf black very_large"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency_rtf&anketa=' . self::$sid) . '" target="_blank"'.$lan_rtf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon rtf black very_large"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency_xls&anketa=' . self::$sid) . '" target="_blank"'.$lan_xls.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon xls black very_large"></span></a>';
				
				echo '<a href="#" onclick="doArchiveAnaliza();" title="'.$lang['srv_analiza_arhiviraj_ttl'].'" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv black very_large"></span></a>';
				echo '<a href="#" onclick="createArchiveBeforeEmail();" title="'.$lang['srv_analiza_arhiviraj_email_ttl'] . '" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv_mail black very_large"></span></a>';						
			}
			
			// Nastavitve na dnu pri opisnih statistikah
			else{
				
				echo '<a href="#" onClick="addCustomReportAllElementsAlert(3);" title="'.$lang['srv_custom_report_comments_add_hover'].'" class="'.(!$userAccess->checkUserAccess('analysis_analysis_creport') ? 'user_access_locked' : '').'" user-access="analysis_analysis_creport" style="margin-right: 40px;"><span class="spaceRight faicon comments_creport" ></span><span class="bold">'.$lang['srv_custom_report_comments_add'].'</span></a>';
				
				echo '<a href="#" onClick="printAnaliza(\'Opisne statistike\'); return false;"'.$lan_print.' class="srv_ico"><span class="faicon print icon-grey_dark_link"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics&anketa=' . self::$sid) . '" target="_blank"'.$lan_pdf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon pdf black very_large"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics_rtf&anketa=' . self::$sid) . '" target="_blank"'.$lan_rtf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon rtf black very_large"></span></a>';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics_xls&anketa=' . self::$sid) . '" target="_blank"'.$lan_xls.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon xls black very_large"></span></a>';				
				
				echo '<a href="#" onclick="doArchiveAnaliza();" title="'.$lang['srv_analiza_arhiviraj_ttl'].'" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv black very_large"></span></a>';
				echo '<a href="#" onclick="createArchiveBeforeEmail();" title="'.$lang['srv_analiza_arhiviraj_email_ttl'] . '" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv_mail black very_large"></span></a>';						
			}
		}
		
        echo '</div>';	
        
        // Javascript s katerim povozimo urlje za izvoze, ki niso na voljo v paketu
        global $app_settings;
        if($app_settings['commercial_packages'] == true){
            echo '<script> userAccessExport(); </script>';
        }
	}
		
	static function displayQuickIcons($id) {
		global $site_url;
		global $global_user_id;

		$return = '<span class="" style="">';
		$anketa = self::$sid;
		switch (self::$podstran) {

			case M_ANALYSIS_SUMMARY_NEW :
				$export = 'sums';
                break;
                
			case M_ANALYSIS_SUMMARY :
				$export = 'sums';
                break;
                
			case M_ANALYSIS_DESCRIPTOR :
				$export = 'statistics';
                break;
                
			case M_ANALYSIS_FREQUENCY :
				$export = 'frequency';
                break;
                
			case M_ANALYSIS_CHARTS :
				$export = 'charts';
				break;
		}
		
		$loop = (isset(self::$_CURRENT_LOOP)) ? self::$_CURRENT_LOOP['cnt'] : 'undefined';
		
		//Izvoz v PDF/RTF
		$_url1 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'm'=>$export,
								'anketa'=>$anketa,
								'sprID'=>$id,
								'loop'=>$loop)));
		$_url2 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'm'=>$export.'_rtf',
								'anketa'=>$anketa,
								'sprID'=>$id,
								'loop'=>$loop)));
		$_url3 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'm'=>$export.'_xls',
								'anketa'=>$anketa,
								'sprID'=>$id,
								'loop'=>$loop)));
		
        $return = '<span class="faicon print_small icon-grey_dark_link" onclick="printCurrentAnalysis(\''.$id.'\');"></span>&nbsp;&nbsp';
        
        $userAccess = UserAccess::getInstance($global_user_id);
        if($userAccess->checkUserAccess($what='data_export')){
            $return .= '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf"></span></a>';
            $return .= '&nbsp;&nbsp;<a href="'.$_url2.'" target="_blank"><span class="faicon rtf"></span></a>';
            
            if(self::$podstran  != M_ANALYSIS_CHARTS ) {
                $return .= '&nbsp;&nbsp;<a href="'.$_url3.'" target="_blank"><span class="faicon xls"></span></a>';
            }
        }
        else{
            $return .= '<a href="#" onClick="popupUserAccess(\'analysis_export\');"><span class="faicon pdf user_access_locked"></span></a>';
            $return .= '&nbsp;&nbsp;<a href="#" onClick="popupUserAccess(\'analysis_export\');"><span class="faicon rtf user_access_locked"></span></a>';
            
            if(self::$podstran  != M_ANALYSIS_CHARTS ) {
                $return .= '&nbsp;&nbsp;<a href="#" onClick="popupUserAccess(\'analysis_export\');"><span class="faicon xls user_access_locked"></span></a>';
            }
        }
		
        $return .= '</span>';
        
		return $return;
	}
	
	function changeSpremenljivkaLestvica() {

		#shranimo nastavitve
		$spremenljivka = $_POST['spid'];
		$skala = $_POST['skala'];
		
		# popravimo skalo spremenljivke
		# skala - 0 Ordinalna
		# skala - 1 Nominalna
		if ( isset($skala) && (int)$spremenljivka) {
			$sql = sisplet_query("UPDATE srv_spremenljivka SET skala='".$skala."' WHERE id='$spremenljivka'");
			#Common::updateEditStamp();
			# popravimo v header datoteki
			self::$_HEADERS[$spremenljivka]['skala'] = $skala;
			file_put_contents(self::$headFileName, serialize(self::$_HEADERS));
		}
	
	}
	
	static function displaySpremenljivkaIcons($spid) {
		
		if (self::$isArchive == false){
			echo '<div class="div_analiza_icons">'.self::displayQuickIcons($spid).'</div>';
			
			// Javna povezava nima js preklopov
			if(self::$printPreview == false)
				self::displayQuickScale($spid);
		}
	}
	
	static function displayQuickScale($spid) {
		global $lang;

		$spr_id = self::$_HEADERS[$spid]['spr_id'];
		
		# pokličemo objekt SpremenljivkaSkala
		$objectSkala = new SpremenljivkaSkala($spr_id);
		
		if ($objectSkala->canChangeSkala()) {
			echo '<div class="div_analiza_scale">';
			if ($objectSkala->is(SpremenljivkaSkala::ORD)) {
				echo '<a href="#" onclick="changeSpremenljivkaLestvica(\''.$spid.'\',\''.SpremenljivkaSkala::NOM.'\'); return false;">';
				echo '<span class="strong" title="'.$lang['srv_skala_long_'.SpremenljivkaSkala::ORD].'">';
				echo $lang['srv_skala_'.SpremenljivkaSkala::ORD];
				echo '</span>';
				echo ' / ';
				echo '<span title="'.$lang['srv_skala_long_'.SpremenljivkaSkala::NOM].'">';
				echo $lang['srv_skala_'.SpremenljivkaSkala::NOM];
				echo '</span>';
				echo '</a>';
			}
			if ($objectSkala->is(SpremenljivkaSkala::NOM)) {
				echo '<a href="#" onclick="changeSpremenljivkaLestvica(\''.$spid.'\',\''.SpremenljivkaSkala::ORD.'\'); return false;">';
				echo '<span title="'.$lang['srv_skala_long_'.SpremenljivkaSkala::ORD].'">';
				echo $lang['srv_skala_'.SpremenljivkaSkala::ORD];
				echo '</span>';
				echo ' / ';
				echo '<span class="strong" title="'.$lang['srv_skala_long_'.SpremenljivkaSkala::NOM].'">';
				echo $lang['srv_skala_'.SpremenljivkaSkala::NOM];
				echo '</span>';
				echo '</a>';
			}
			echo '</div>';
		} else {
			echo '<div class="div_analiza_scale">';
			echo '<span title="'.$lang['srv_skala_long_'.$objectSkala->getSkala()].'">';
			echo $lang['srv_skala_'.$objectSkala->getSkala()];
			echo '</span>';
			echo '</div>';
		}
	}

	static function displayPublicAnalysis($properties = array()) {
		global $lang;
		global $site_url;
	
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		
		$anketa = self::$sid;
		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}
		
		self::Init($anketa);
		
		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="'.$site_url.'admin/survey/script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="'.$site_url.'admin/survey/minify/g=jsnew"></script>';
		echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';
	
		echo '<body style="margin:5px; padding:5px;" >';
		echo '<h2>'.$lang['srv_publc_analysis_title_for'].self::$survey['naslov'].'</h2>';

		echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $anketa . '" />';
		echo '<div id="analiza_data">';
	
		if (isset($properties['profile_id_variable']))
		{
			self::$_PROFILE_ID_VARIABLE = $properties['profile_id_variable'];
				
			SurveyVariablesProfiles::setCurrentProfileId(self::$_PROFILE_ID_VARIABLE);
		}

		if (isset($properties['profile_id_condition']))
		{
			self::$_PROFILE_ID_CONDITION = $properties['profile_id_condition'];
				
			SurveyConditionProfiles::setCurrentProfileId(self::$_PROFILE_ID_CONDITION);
		}
	
	
		self::$printPreview = true;
		
		# ponastavimo nastavitve- filter
		self::Display();
		echo '</div>';
			
		echo '<div id="navigationBottom" class="printHide">';
	
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/printer.png" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<span  class="spaceRight floatRight printHide" style="margin-top:6px;">';
		echo '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url2.'" target="_blank"><span class="faicon rtf"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url3.'" target="_blank"><span class="faicon xls"></span></a>';
		echo '</span>';
	
		echo '<br class="clr" />';
		echo '</div>';
	
		echo '</body>';
		echo '</html>';
	}
	
	static function heatmapGraph($spid,$_from, $lokacija=false, $heatmap=false) {
		global $lang;			

		$spremenljivka = self::$_HEADERS[$spid];
		$anketa = self::$sid;

		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];
				}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
		
		//		$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

		$_answers = self::getAnswers($spremenljivka,$num_show_records);

		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
			//self::displaySpremenljivkaIcons($spid);
		}
		
		//echo '<div class="heatmapGrapshContainer" style=" width: 800px; text-align:center; margin-left:auto; margin-right:auto;">';
		echo '<div class="heatmapGrapshContainer" style=" width: 600px; text-align:center; margin-left:auto; margin-right:auto;">';
			# tekst vprašanja
			echo '<table class="anl_tbl anl_bt anl_bb tbl_clps">';
			# naslovna vrstica
			echo '<tr>';
			#variabla
			echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
			//echo self::showVariable($spid, $spremenljivka['variable']);
			echo $spremenljivka['variable'].' - '.$spremenljivka['naslov'];
			echo '</td>';

			echo '</tr>';
			echo '<tr>';
			#variabla

			// konec naslovne vrstice

			$_answersOther = array();
			$_grids_count = count($spremenljivka['grids']);
			if ($_grids_count > 0) {

				$_css_bck = 'anl_bck_desc_2 anl_ac anl_bt_dot ';
				$last = 0;
				//anl_bck_desc_2 anl_bl anl_br anl_variabla_sub
				foreach ($spremenljivka['grids'] AS $gid => $grid) {

					$_variables_count = count($grid['variables']);
					echo '<tr class="'.$_css_bck.'">';
					echo '<td class="anl_bl anl_br anl_variabla_sub">';
					if($heatmap){
						//echo $grid['naslov'].'<br>';//ni potrebno, ker je ze v glavi?
						$sprid = explode('_',$spid);
						$loopid = $sprid[1];
						$sprid = $sprid[0];
						SurveyUserSession::Init($anketa);											
						
						$heatmapId = 'heatmap'.$sprid;
						//echo $heatmapId;

						echo '<a class="fHeatMap" id="heatmap_'.$sprid.'" title="'.$lang['srv_view_data_on_map'].
							'" href="javascript:void(0);" onclick="passHeatMapData('.$sprid.', -1, '.$loopid.', '.$anketa.');">';
						//echo '<img src="img_0/Google_Maps_Icon.png" alt="Smiley face" height="24" width="24" />';
						echo 'Heatmap';
						echo '</a>';
					}
					echo '</td>';


					echo '</tr>';
				}
			}
			echo '</table>';
		echo '</div>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}
	
	// ikone za izvoz za heatmap porocila
	static function displayExportIcons4Heatmap($spid, $anketa){
		global $site_path;
		global $lang;
		
		$spremenljivka = self::$_HEADERS[$spid];
		
		$loop = (isset(self::$_CURRENT_LOOP)) ? self::$_CURRENT_LOOP['cnt'] : 'undefined';
		
		// linki
		echo '<div class="chart_setting_exportLinks">'.$lang['srv_export_as'].': ';
		
		//Izvoz heatmap slike
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=heatmap_image&anketa='.$anketa.'&sprID='.$spid.'&loop='.$loop).'" target="_blank" onclick="exportHeatmapAsImage(\''.$spid.'\');" class="srv_ico" title="'.$lang['heatMapGenerateImage'].'"><span class="sprites heatmapImageSave"></span></a>';
		
		//Izvoz heatmap slike v pdf
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=heatmap_image_pdf&anketa='.$anketa.'&sprID='.$spid.'&loop='.$loop) . '" target="_blank" onclick="exportHeatmapAsImage(\''.$spid.'\');" title="'.$lang['PDF_Izpis'].'" class="srv_ico"><span class="faicon pdf"></span></a>';
		
		//Izvoz heatmap slike v rtf
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=heatmap_image_rtf&anketa='.$anketa.'&sprID='.$spid.'&loop='.$loop) . '" target="_blank" onclick="exportHeatmapAsImage(\''.$spid.'\');" title="'.$lang['RTF_Izpis'].'" class="srv_ico"><span class="faicon rtf"></span></a>';
		
		//Izvoz heatmap slike v ppt
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=heatmap_image_ppt&anketa='.$anketa.'&sprID='.$spid.'&loop='.$loop) . '" target="_blank" onclick="exportHeatmapAsImage(\''.$spid.'\');" title="'.$lang['PPT_Izpis'].'" class="srv_ico"><span class="faicon ppt"></span></a>';

		echo '</div>';
	}
	
	/** Izriše tekstovne odgovore kot tabelo za heatmap
	 *
	 * @param unknown_type $spid
	 */
	static function sumMultiTextHeatMap($spid,$_from, $lokacija=false, $heatmap=false) {
		global $lang;
		
		$RegionPresent = true;
		$spremenljivka = self::$_HEADERS[$spid];
		$anketa = self::$sid;
			
		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$only_valid += (int)self::$_FREQUENCYS[$_sequence]['validCnt'];				
				}
			}
		}
		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && self::$_forceShowEmpty == false) {
			return;
		}

		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$num_show_records = self::getNumRecords();
		
		//		$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

 		$_answers = self::getAnswers($spremenljivka,$num_show_records);

		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];

		if (self :: $show_spid_div == true) {
			echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';	
		}
		
		self::displaySpremenljivkaIcons($spid);

		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_bb tbl_clps">';	//zacetek tabele
		# 1. vrstica - naslovna vrstica
		echo '<tr>';
			#variabla
			echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
				echo self::showVariable($spid, $spremenljivka['variable']);
			echo '</td>';
			#odgovori
			//echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="'.(!$lokacija ? (self::$_SHOW_LEGENDA ? 3+$_cols : 1+$_cols) : 3+$_cols).'"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';
			echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="6"><span class="anl_variabla_label">'.$spremenljivka['naslov'].'</span>';

			echo '</td>';
		echo '</tr>';
		//konec 1. vrstice
		
		//2. vrstica - prikazovanje povezave do heatmap
 		echo '<tr>';	
			#variabla
			echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';
				//self::showIcons($spid,$spremenljivka,$_from);	//za enkrat skrijem ikone za izvoze in druge moznosti
			echo '</td>'; 			
 			echo '<td class="anl_br anl_bb anl_ac" colspan="6">';
				//echo $grid['naslov'].'<br>';//ni potrebno, ker je ze v glavi?
				$sprid = explode('_',$spid);
				$loopid = $sprid[1];
				$sprid = $sprid[0];
				SurveyUserSession::Init($anketa);											
				
				$heatmapId = 'heatmap'.$sprid;
				//echo $heatmapId;
				//SurveyChart::displayExportIcons($sprid);
				echo '<a class="fHeatMap" id="heatmap_'.$sprid.'" title="'.$lang['srv_view_data_heatmap'].
						'" href="javascript:void(0);" onclick="passHeatMapData('.$sprid.', -1, '.$loopid.', '.$anketa.');">';
					//echo '<img src="img_0/Google_Maps_Icon.png" alt="Smiley face" height="24" width="24" />';
				echo 'Heatmap ';
				echo '</a>';

			echo '</td>';
		echo '</tr>';
		//konec - 2. vrstice
		
		//Koordinate
		//naslovna vrstica za koordinate
		echo '<tr>';
			echo '<td class="anl_bl anl_br anl_bck anl_bb anl_ac" colspan="7">';
				echo '<b>'.$lang['srv_analiza_heatmap_clicked_coords'].'</b>';
			echo '</td>';		
		echo '</tr>';
		//naslovna vrstica za koordinate - konec
		//vrstica s podnaslovi celic
		echo '<tr>';
			echo '<td class="anl_variabla_line anl_bl anl_br anl_bb anl_bck anl_ac">';
			echo $lang['coordinates'];
			echo '</td>';
			
			echo '<td class="anl_variabla_line anl_bl anl_br anl_bb anl_bck anl_ac anl_w70">';
			echo $lang['srv_analiza_opisne_valid_heatmap'];
			echo '</td>';
			
			echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac anl_w70">';
			echo $lang['srv_analiza_num_units_valid_heatmap'];
			echo '</td>';
			
			echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac anl_w70">';
			echo $lang['srv_means_label'];
			echo '</td>';
			
			echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac anl_w70">';
			echo $lang['srv_analiza_opisne_odklon'];
			echo '</td>';
			
			echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac anl_w70">';
			echo $lang['srv_analiza_opisne_min'];
			echo '</td>';
			
			echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac anl_w70">';
			echo $lang['srv_analiza_opisne_max'];
			echo '</td>';
		echo '</tr>';
		//vrstica s podnaslovi celic - konec
		
		//vrstica za x
		echo '<tr>';
			//1. stolpcev z imenom koordinate
			echo '<td class="anl_bl anl_br anl_bb anl_ac">';
			echo 'x';
			echo '</td>';
			//1. stolpcev z imenom koordinate - konec
			
			//2. stolpec - Veljavni
			$validHeatmapRegion = self::validHeatmapRegion($spremenljivka['grids'], $spid, $_valid_answers);
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$validHeatmapRegion.'</td>';
			//2. stolpec - Veljavni - konec
			
			//3. stolpec - Ustrezni
			$ustrezniHeatmapRegion = self::ustrezniHeatmapRegion($spid, $_valid_answers, $_sequence); //vsi mozni kliki
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$ustrezniHeatmapRegion.'</td>';
			//3. stolpec - Ustrezni - konec
			
			//4. stolpec - Povprecje
			$povprecjeHeatmapClicksX = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'povprecje'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$povprecjeHeatmapClicksX.'</td>';			
			//4. stolpec - Povprecje - konec

			//5. stolpec - Standardni odklon
			$stdevHeatmapClicksX = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'stdev'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$stdevHeatmapClicksX.'</td>';			
			//5. stolpec - Standardni odklon - konec	

			//6. stolpec - Minimum
			$minHeatmapClicksX = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'min'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$minHeatmapClicksX.'</td>';			
			//6. stolpec - Minimum - konec
			
			//7. stolpec - Max
			$maxHeatmapClicksX = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'max'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$maxHeatmapClicksX.'</td>';			
			//7. stolpec - Max - konec			
			
		echo '</tr>';
		//vrstica za x - konec
		
		//vrstica za y
		echo '<tr>';
			//1. stolpcev z imenom koordinate
			echo '<td class="anl_bl anl_br anl_bb anl_ac">';
			echo 'y';
			echo '</td>';
			//1. stolpcev z imenom koordinate - konec
			
			//2. stolpec - Veljavni
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$validHeatmapRegion.'</td>';
			//2. stolpec - Veljavni - konec
			
			//3. stolpec - Ustrezni
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$ustrezniHeatmapRegion.'</td>';
			//3. stolpec - Ustrezni - konec

			//4. stolpec - Povprecje
			$povprecjeHeatmapClicksY = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'povprecje'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$povprecjeHeatmapClicksY.'</td>';			
			//4. stolpec - Povprecje - konec
			
			//5. stolpec - Standardni odklon
			$stdevHeatmapClicksY = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'stdev'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$stdevHeatmapClicksY.'</td>';			
			//5. stolpec - Standardni odklon - konec

			//6. stolpec - Minimum
			$minHeatmapClicksY = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'min'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$minHeatmapClicksY.'</td>';			
			//6. stolpec - Minimum - konec
			
			//7. stolpec - Max
			$maxHeatmapClicksY = self::formatNumber(self::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'max'),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$maxHeatmapClicksY.'</td>';			
			//7. stolpec - Max - konec
		
		echo '</tr>';
		//vrstica za y - konec
		
		//Koordinate - konec		
		
		//preveri, ali je prisotno kaksno obmocje, nadaljuj izris tabele
		$RegionPresent = self::HeatmapRegionPresence($spremenljivka['grids'], $spid, $_valid_answers);
		//preveri, ali je prisotno kaksno obmocje, nadaljuj izris tabele - konec
		
		if($RegionPresent){	//ce imamo obmocja
			//3. vrstica - naslovna za obmocja
			echo '<tr>';			
				echo '<td class="anl_bl anl_br anl_bck anl_bb anl_ac" colspan="7">';
					echo '<b>'.$lang['srv_analiza_heatmap_clicked_regions'].'</b>';
				echo '</td>';
			echo '</tr>';
			//konec - 3. vrstice

			$_answersOther = array();
			$_grids_count = count($spremenljivka['grids']);
			$_css_bck = 'anl_bck_desc_2 anl_ac anl_bt_dot ';
			$last = 0;
			
			if ($_grids_count > 0) {			
				$_row = $spremenljivka['grids'][0];
				$indeks = 0;
				//$veljavnaSkupnaFreq = 0;
				if (count($_row['variables'])>0)
					foreach ($_row['variables'] AS $rid => $_col ){

					$_sequence = $_col['sequence'];	# id kolone z podatki
					if ($_col['other'] != true) {
						echo '<tr>';					
						if($indeks == 0)	//4. vrstica, naslovna vrstica
						{
							echo '<td class="anl_variabla_line anl_bl anl_br anl_bb anl_bck anl_ac" colspan="2">';
							echo $lang['srv_hot_spot_regions_menu'];
							echo '</td>';
							
							echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac">';
							echo $lang['srv_analiza_opisne_frequency_heatmap'];
							echo '</td>';
							
							echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac">';
							echo $lang['srv_analiza_opisne_valid_heatmap'];
							echo '</td>';
							
							echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac">';
							echo '% - '.$lang['srv_analiza_opisne_valid_heatmap'];
							echo '</td>';
							
							echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac">';
							echo $lang['srv_analiza_num_units_valid_heatmap'];
							echo '</td>';
							
							echo '<td class="anl_variabla_line anl_br anl_bb anl_bck anl_ac">';
							echo '% - '.$lang['srv_analiza_num_units_valid_heatmap'];
							echo '</td>';
						}else	//od 5. vrstice dalje, kjer so po vrsticah obmocja in njihovi podatki
						{
							//1. stolpcev z imenom obmocja
							echo '<td class="anl_bl anl_br anl_bb anl_ac" colspan="2">';
							echo $_col['naslov'];
							echo '</td>';
							//1. stolpcev z imenom obmocja - konec
							
							//2. stolpec - Frekvenca
							$freqHeatmapRegion = self::freqHeatmapRegion($spremenljivka['grids'], $spid, $_valid_answers, $indeks);
							$veljavnaSkupnaFreq = $veljavnaSkupnaFreq + $freqHeatmapRegion;
							echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$freqHeatmapRegion.'</td>';
							//2. stolpec - Frekvenca - konec
							
							//3. stolpec - Veljavni
							//$validHeatmapRegion = self::validHeatmapRegion($spremenljivka['grids'], $spid, $_valid_answers);
							echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$validHeatmapRegion.'</td>';
							//3. stolpec - Veljavni - konec
							
							//4. stolpec - % Veljavni
							$_procentValidHeatmapRegion = ($validHeatmapRegion > 0 ) ? 100*$freqHeatmapRegion / $validHeatmapRegion : 0;
							$_procentValidHeatmapRegion = self::formatNumber($_procentValidHeatmapRegion, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
							echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$_procentValidHeatmapRegion.'</td>';
							//4. stolpec - % Veljavni - konec
							
							//5. stolpec - Ustrezni
							$ustrezniHeatmapRegion = self::ustrezniHeatmapRegion($spid, $_valid_answers, $_sequence); //vsi mozni kliki
							echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$ustrezniHeatmapRegion.'</td>';
							//5. stolpec - Ustrezni - konec
							
							//6. stolpec - % Ustrezni
							$_procentUstrezniHeatmapRegion = ($ustrezniHeatmapRegion > 0 ) ? 100*$freqHeatmapRegion / $ustrezniHeatmapRegion : 0;
							$_procentUstrezniHeatmapRegion = self::formatNumber($_procentUstrezniHeatmapRegion, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
							echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$_procentUstrezniHeatmapRegion.'</td>';
							//6. stolpec - % Ustrezni - konec							
						}
						
						//echo '</td>';
						echo '</tr>';
		
						//*********** Izris veljavnih in manjkajocih vrednosti
						if($indeks != 0)	//ce ni naslovna vrsticam je potrebno dodati se dodatne poglede veljavnih in manjkajocih vrednosti
						{
							echo '<tr>';
								$counter = 0;
								$options['isTextAnswer'] = false;
								$manjkajoci = $ustrezniHeatmapRegion - $validHeatmapRegion;
								$counter = self::outputSumaValidAnswerHeatmap($counter,$_sequence,$spid,$options, $validHeatmapRegion);

  								if (count(self::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
									foreach (self::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
  										if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki niso 0
												$counter = self::outputInvalidAnswerHeatmap($counter,$ikey,$iAnswer,$_sequence,$spid,$options, $manjkajoci);
										} 
									}
									# izpišemo sumo veljavnih
									$counter = self::outputSumaInvalidAnswerHeatmap($counter,$_sequence,$spid,$options, $manjkajoci);
								}
								#izpišemo še skupno sumo
								$counter = self::outputSumaHeatmap($counter,$_sequence,$spid,$options, $ustrezniHeatmapRegion);							
							echo '</tr>';
							$veljavnaSkupnaFreq = 0;
						}
						//*********** Izris veljavnih in manjkajocih vrednosti - konec
						
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$indeks++;
				}				
			}
		}
		echo '</table>';

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		}

		if (self :: $show_spid_div == true) {
			echo '</div>';
			echo '<br/>';
		}
	}
	
	static function freqHeatmapRegion($spremenljivkaGrids, $spid, $_valid_answers, $indeks, $export=0){
		$steviloPodatkov = count($_valid_answers);
		$freqHeatMapRegion = 0;
		foreach ($spremenljivkaGrids AS $gid => $grid) 
		{
			$_variables_count = count($grid['variables']);
			if ($_variables_count > 0) 
			{
				# preštejemo max vrstic na grupo
				$_max_i = 0;
				//$numObmocij = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_i = max($_max_i,min($num_show_records,self::$_FREQUENCYS[$_sequence]['validCnt']));
					//$numObmocij++;
				}
				
				$indeksZaObmocja = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) 
					{
						#$_valid_cnt = count(self::$_FREQUENCYS[$_sequence]['valid']);
						
						if (count($_valid_answers) > 0) {
							
							foreach ($_valid_answers AS $answer) {
								$_ans = $answer[$_sequence];

								//if ($_ans != null && $_ans != '' && $indeksZaObmocja == count($_valid_answers)+$indeks) {
								if ($_ans != null && $_ans != '' && $indeksZaObmocja >= count($_valid_answers)*$indeks && $steviloPodatkov != 0) 
								{
									$freqHeatMapRegion = $freqHeatMapRegion + $_ans;
									$steviloPodatkov--;
								}
								else {
									if($export==0){
										echo '&nbsp;';
									}									
								}
								$indeksZaObmocja++;
							}
						}							
					}
					
				}
				
			}						
		}
		return $freqHeatMapRegion;
	}
	
	static function validHeatmapRegion($spremenljivkaGrids, $spid, $_valid_answers, $export=0){
		$validHeatmapRegion = 0;
		foreach ($spremenljivkaGrids AS $gid => $grid) 
		{
			$_variables_count = count($grid['variables']);
			if ($_variables_count > 0) 
			{
				//$numObmocij = 0;
				$brs = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{					
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) 
					{
						#$_valid_cnt = count(self::$_FREQUENCYS[$_sequence]['valid']);
						if (count($_valid_answers) > 0) {
							//echo '<script>console.log("count($_valid_answers):'.count($_valid_answers).'"); </script>';
							//$brs = 0;
							foreach ($_valid_answers AS $answer) {
								$_ans = $answer[$_sequence];
								$vejice = substr_count($_ans, ",");
								if ($_ans != null && $_ans != '' && $vejice != 0) 
								{
									//echo '<script>console.log("$_ans:'.$_ans.'"); </script>';
									$brs = $brs + substr_count($_ans, "<br>");
									//echo '<script>console.log("<br>s in $_ans:'.$brs.'"); </script>';
								}
								else {
									if($export == 0){
										echo '&nbsp;';
									}
								}
							}
						}							
					}					
				}				
			}						
		}
		$validHeatmapRegion = $brs;
		//echo '<script>console.log("validHeatmapRegion konec:'.$validHeatmapRegion.'"); </script>';
		return $validHeatmapRegion;
	}
	
	static function ustrezniHeatmapRegion($spid, $_valid_answers, $_sequence){
		$row = Cache::srv_spremenljivka($spid);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$heatmap_num_clicks = ($spremenljivkaParams->get('heatmap_num_clicks') ? $spremenljivkaParams->get('heatmap_num_clicks') : 1);		
		return self::$_FREQUENCYS[$_sequence]['validCnt'] * $heatmap_num_clicks;	//vrni vse mozne klike = stev. odgovorov * stev. moznih klikov
	}
	
	static function HeatmapRegionPresence($spremenljivkaGrids, $spid, $_valid_answers){		
		$HeatmapRegionPresence = false;
		foreach ($spremenljivkaGrids AS $gid => $grid) 
		{
			$_variables_count = count($grid['variables']);
			if ($_variables_count > 0) 
			{
				# preštejemo max vrstic na grupo
				$_max_i = 0;
				//$numObmocij = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_i = max($_max_i,min($num_show_records,self::$_FREQUENCYS[$_sequence]['validCnt']));
					//$numObmocij++;
				}
				
				$indeksZaObmocja = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) 
					{						
						if (count($_valid_answers) > 0) {
							
							foreach ($_valid_answers AS $answer) {
								$_ans = $answer[$_sequence];
								if ($_ans != null && $_ans != '' && $indeksZaObmocja >= count($_valid_answers)) 
								{
									$HeatmapRegionPresence = true;
								}
								$indeksZaObmocja++;
							}
						}							
					}
					
				}
				
			}						
		}
		return $HeatmapRegionPresence;
	}

	static function heatmapClicksCalc($spremenljivkaGrids, $spid, $_valid_answers, $coords, $veljavnikliki, $what, $export=0){
		$heatmapClicksCalc = 0;
		$stdevCoordsArray = array();
		$minCoords = 0;
		$maxCoords = 0;
		foreach ($spremenljivkaGrids AS $gid => $grid) 
		{
			$_variables_count = count($grid['variables']);
			if ($_variables_count > 0) 
			{
				# preštejemo max vrstic na grupo
				$_max_i = 0;
				//$numObmocij = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_i = max($_max_i,min($num_show_records,self::$_FREQUENCYS[$_sequence]['validCnt']));
					//$numObmocij++;
				}
				
				$indeksZaObmocja = 0;
				foreach ($grid['variables'] AS $vid => $variable )
				{
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) 
					{
						#$_valid_cnt = count(self::$_FREQUENCYS[$_sequence]['valid']);
						
						if (count($_valid_answers) > 0) {
							
							foreach ($_valid_answers AS $answer) {
								$_ans = $answer[$_sequence];
								if ($_ans != null && $_ans != '' && $_ans >= 0 && $indeksZaObmocja < count($_valid_answers)) 
								{
									//$validHeatmapRegion = $validHeatmapRegion + $_ans;
									//echo '<td>'.$_ans.'</td>';
									$_ans = substr($_ans, 4);	//odstrani <br> iz zacetka koordinat
									$coordinates = explode('<br>',$_ans);
									foreach($coordinates AS $key => $coordinate){
										$coordinate = explode(',',$coordinate);
										foreach($coordinate AS $coordskey => $subcoords)
										{
											if($coords == 'x' && ($coordskey == 0 || $coordskey%2 == 0) )
											{
												array_push($stdevCoordsArray, $subcoords);
											}else if($coords == 'y' && ($coordskey != 0 || $coordskey%2 != 0) )
											{
												array_push($stdevCoordsArray, $subcoords);
											}									
										}											
									}									
								}
								else {
									if($export == 0){
										echo '&nbsp;';
									}									
								}
								$indeksZaObmocja++;
							}
						}							
					}					
				}				
			}						
		}
		
		if($what == 'povprecje')
		{
			$heatmapClicksCalc = array_sum($stdevCoordsArray) / count($stdevCoordsArray);
		}else if($what == 'stdev')
		{
			if(is_array($stdevCoordsArray)){
				$mean = array_sum($stdevCoordsArray) / count($stdevCoordsArray);
				foreach($stdevCoordsArray as $key => $num) $devs[$key] = pow($num - $mean, 2);
				if(count($devs) != 1)
				$heatmapClicksCalc = sqrt(array_sum($devs) / (count($devs) - 1));
			}
		}else if($what == 'min')
		{
			$heatmapClicksCalc = min($stdevCoordsArray);
		}else if($what == 'max')
		{
			$heatmapClicksCalc = max($stdevCoordsArray);
		}
				
		return $heatmapClicksCalc;
	}
	
}
?>