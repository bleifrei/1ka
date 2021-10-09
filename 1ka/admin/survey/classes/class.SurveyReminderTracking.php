<?php

class SurveyReminderTracking{

	private $anketa;										# id ankete
	private $db_table;									# katere tabele uporabljamo
	public $_HEADERS = array();							# shranimo podatke vseh variabel

	private $headFileName = null;						# pot do header fajla
	private $dataFileName = null;						# pot do data fajla
	private $dataFileStatus = null;						# status data datoteke

	public $variablesList = null; 					 	# Seznam vseh variabel nad katerimi lahko izvajamo (zakeširamo)

	public $_CURRENT_STATUS_FILTER = ''; 				# filter po statusih, privzeto izvažamo 6 in 5
	public $_PROFILE_ID_VARIABLE = ''; 					# filter po statusih, privzeto izvažamo 6 in 5

	public $_HAS_TEST_DATA = false;						# ali anketa vsebuje testne podatke

	private $sessionData;								# podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
	private $_missings = array();
	private $_unsets = array();

	private $displayEditIconsSettings = false;			# ali prikazujemo okno s checkboxi za nastavitve tabele s podatki	
	
	private $cols_with_value = array();					# kateri stolpci imajo vrednosti
	private $show_with_zero = false;					# Ali prikazujemo stolpce z vrednostmi 0
	private $show_details = false;						# Ali prikazujemo stolpce s podrobnimi vrednostmi (-1, -2...)
	private $show_calculations = false;					# Ali prikazujemo stolpce s podrobnimi izracuni (UML, UNL...)
	private $show_with_other = true;					# Ali prikazujemo vrstice "Drugo"
	private $show_with_text = true;						# Ali prikazujemo vrstice tipa "besedilo"
	
	public $bottom_usable_limit = 50;					# Spodnja meja za usable respondente (def. 50%)
	public $top_usable_limit = 80;						# Zgornja meja za usable respondente (def. 80%) - unusable (50-), partially usable (50-80), usable(80+)
	
	//private $sortField = 'recnum';						# Polje po katerem sortiramo tabelo
	private $sortField = '';						# Polje po katerem sortiramo tabelo
	private $sortType = 0;								# Nacin sortiranja (narascajoce/padajoce)
		
	
	function __construct($anketa, $generateDataFile=true){
		global $lang;

		if ((int)$anketa > 0){
		
			$this->anketa = $anketa;

			SurveyAnalysisHelper::getInstance()->Init($this->anketa);

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}
			$this->_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/';

			Common::deletePreviewData($this->anketa);
			            

            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa); 

            if($generateDataFile) 
                $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();

			if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_SRV_DELETED) {
				Common::noDataAlert();
				exit();
			}
			
			
			# Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->anketa);
			SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);
			SurveyConditionProfiles :: Init($this->anketa, $global_user_id);
			SurveyTimeProfiles :: Init($this->anketa, $global_user_id);
			SurveyVariablesProfiles :: Init($this->anketa, $global_user_id);
			SurveyDataSettingProfiles :: Init($this->anketa);


			// preberemo nastavitve iz baze (prej v sessionu)
			SurveyUserSession::Init($this->anketa);
			$this->sessionData = SurveyUserSession::getData();

			if(isset($_SESSION['sid_'.$this->anketa]['usabilityIcons_settings']))
				$this->displayEditIconsSettings = ($_SESSION['sid_'.$this->anketa]['usabilityIcons_settings']);

			if ($this->headFileName !== null && $this->headFileName != ''){
				$this->_HEADERS = unserialize(file_get_contents($this->headFileName));
			}

			# nastavimo vse filtre
			$this->setUpFilter();

			# nastavimo filtre uporabnika
			$this->setUserFilters();
			
			# nastavimo sortiranje
			if(isset($_GET['sortField']))
				$this->sortField = $_GET['sortField'];
			if(isset($_GET['sortType']))
				$this->sortType = $_GET['sortType'];
		} 
		else {	
			echo 'Invalid Survey ID!';
			exit();
		}
	}
	
	public function getMissings(){
		return $this->_missings;
	}
	
	public function getUnsets(){
		return $this->_unsets;
	}
	
	public function getColsWithValue(){
		return $this->cols_with_value;
	}
	
	public function showCalculations(){
		return $this->show_calculations;
	}
	
	public function showDetails(){
		return $this->show_details;
	}
	
	public function showWithZero(){
		return $this->show_with_zero;
	}
	

	
	function displayTable(){
		global $lang;
		
		# ali imamo testne podatke
		if ($this->_HAS_TEST_DATA){
            # izrišemo bar za testne podatke
            $SSH = new SurveyStaticHtml($this->anketa);
			$SSH -> displayTestDataBar(true);
		}
		
		// Izracunamo vse podatke
		$usability = $this->calculateData();	//podatki po respondentih
		$userData = $usability['data'];
				
		$vars = $this->calculateDataVars($userData[1]['usr_id']);	//podatki po spremenljivkah		

		
		if($_GET['m'] == A_REMINDER_TRACKING_RECNUM){	//ce je prikaz po respondentih
			$this->sortField = 'recnum';
        }
        else if($_GET['m'] == A_REMINDER_TRACKING_VAR){	//ce je prikaz po spremenljivkah
			$this->sortField = 'vars';
		}
		
		// Sortiramo podatke
		foreach ($userData as $key => $row) {
			$mid[$key]  = $row[$this->sortField];
		}
		if($this->sortType == 0)
			array_multisort($mid, SORT_ASC, $userData);
		else
			array_multisort($mid, SORT_DESC, $userData);
		
		
		# ali odstranimo stolpce kateri imajo same 0
		if ($this->show_with_zero == false) {
			# odstranimo missinge brez vrednosti
			foreach ($this->_missings AS $_key => $_missing) {
				if (!isset($this->cols_with_value[$_key]) || $this->cols_with_value[$_key] == false) {
					unset($this->_missings[$_key]);
				}
			}
			# odstranimo neveljavne brez vrednosti
			foreach ($this->_unsets AS $_key => $_unset) {
				if (!isset($this->cols_with_value[$_key]) || $this->cols_with_value[$_key] == false) {
					unset($this->_unsets[$_key]);
				}
			}
		}
		
					
		if($_GET['m'] == A_REMINDER_TRACKING_RECNUM){	//ce je prikaz po respondentih
			echo '<h2>'.$lang['srv_reminder_tracking_title_recnum'].'</h2>';
			$naslovStolpecMoznaOpozorila = $lang['srv_reminder_tracking_possible_errors_resp'];
        }
        else if($_GET['m'] == A_REMINDER_TRACKING_VAR){	//ce je prikaz po spremenljivkah
			echo '<h2>'.$lang['srv_reminder_tracking_title_vars'].'</h2>';
			$naslovStolpecMoznaOpozorila = $lang['srv_reminder_tracking_possible_errors_var'];
		}
		
		echo '<div id="table_reminder_tracking"><table class="reminder_tracking_table" '.($this->show_details==true && $this->show_calculations==true ? '' : ' style="width:100%"').'>';

		// Header row
		echo '<tr>';		
		
			if($this->sortType == 1){
				$sortType = 0;
				$arrow = ' <span class="faicon sort_ascending"></span>';
			}
			else{
				$sortType = 1;
				$arrow = ' <span class="faicon sort_descending"></span>';
			}
			
			if($_GET['m'] == A_REMINDER_TRACKING_RECNUM){	//ce je prikaz po respondentih
				echo '<th class="recnum" rowspan="2" style="width:60px;"><a href="index.php?anketa='.$this->anketa.'&a=reminder_tracking&m='.A_REMINDER_TRACKING_RECNUM.'&sortField=recnum&sortType='.$sortType.'">Recnum'./*$lang['recnum'].*/($this->sortField=='recnum' ? $arrow : '').'</a></th>';
				//$qualityRowSpan = count($userData) + 2;
				$qualityRowSpan = count($userData) + 3;
				$numOf = count($userData);
            }
            else if($_GET['m'] == A_REMINDER_TRACKING_VAR){	//ce je prikaz po spremenljivkah
				//echo '<th class="vars" rowspan="2" style="width:100px;"><a href="index.php?anketa='.$this->anketa.'&a=reminder_tracking&m='.A_REMINDER_TRACKING_VAR.'&sortField=vars&sortType='.$sortType.'">Spremenljivka'./*$lang['recnum'].*/($this->sortField=='vars' ? $arrow : '').'</a></th>';
				echo '<th class="vars" rowspan="2" style="width:100px;">Vprašanje</th>';
				//$qualityRowSpan = count($vars) + 2;
				$qualityRowSpan = count($vars) + 3;
				$numOf = count($vars);
			}
			
			//echo '<th class="usable" colspan="2">Opozorila obveznih vprašanj</th>';
			//Obvezna vprasanja
			echo '<th class="usable" colspan="2">'.$lang['srv_reminder_tracking_question'].'</th>';
			
			//echo '<th class="data" colspan=2>Num opozorila</th>';			
			//Vnos stevil
			echo '<th class="data" colspan=2>'.$lang['srv_reminder_tracking_num'].'</th>';			
			
			//echo '<th class="data" colspan=2>Sum opozorila</th>';
			//Vsota stevil
			echo '<th class="data" colspan=2>'.$lang['srv_reminder_tracking_sum'].'</th>';

			//echo '<th class="usable" colspan="2">Validacije</th>';
			//Validacije
			echo '<th class="usable" colspan="2">'.$lang['srv_reminder_tracking_validation'].'</th>';
			
			echo '<th class="sprozenaOpozorila" rowspan="2">'.$naslovStolpecMoznaOpozorila.'</th>';	//naslov stolpca "Število možnih opozoril"

			echo '<th class="sprozenaOpozorila" rowspan="2">'.$lang['srv_reminder_tracking_sum_of_errors'].'</th>';	//naslov stolpca "Vsota sprozenih opozoril"

			echo '<th class="sprozenaOpozorilaLine" rowspan="2">'.$lang['srv_reminder_tracking_activated_errors'].'</th>'; //naslov stolpca "Stevilo sprozenih opozoril"
			
			//$qualityRowSpan = count($userData) + 2;
			
			echo '<th class="sprozenaOpozorilaLine" rowspan="'.$qualityRowSpan.'">'.$lang['srv_reminder_tracking_quality'].' '.Help::display('srv_reminder_tracking_quality').'</th>';
			
		echo '</tr>';	// Header row - konec
		

		//Second title row
		echo '<tr>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_hard'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_soft'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_hard'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_soft'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_hard'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_soft'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_hard'].'</th>';
			echo '<th class="data">'.$lang['srv_reminder_tracking_soft'].'</th>';		
		echo '</tr>';	//Second title row - konec
		
		
		if($_GET['m'] == A_REMINDER_TRACKING_RECNUM)
		{	//ce je prikaz po respondentih
			$sprozenaOpozorilaAll = 0;

			$sumObveznihVprasanjHard = 0;
			$sumObveznihVprasanjSoft = 0;
			$sumNumAlertHard = 0;
			$sumNumAlertSoft = 0;
			$sumSumAlertHard = 0;
			$sumSumAlertSoft = 0;
			$sumValidationHard = 0;
			$sumValidationSoft = 0;
			
			// Izpis podatkov vsakega respondenta
			foreach($userData as $key => $user){
						
				// Prva vrstica z vrednostmi
				echo '<tr>';

					echo '<td rowspan="1" class="recnum"><a href="index.php?anketa='.$this->anketa.'&a=data&m=quick_edit&usr_id='.$user['usr_id'].'&quick_view=1">'.$user['recnum'].'</a></td>';
					
					$sprozenaOpozorila = 0;
					$steviloVsehMoznihOpozoril= 0;
					
					// Alerti obveznih vprasanj Hard
					$this->izrisPodatka($user['rowHardAlert']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowHardAlert'];
					if($user['rowHardAlert'] != 0){$sprozenaOpozorila++;}
					
					// Alerti obveznih vprasanj Soft
					$this->izrisPodatka($user['rowSoftAlert']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowSoftAlert'];
					if($user['rowSoftAlert'] != 0){$sprozenaOpozorila++;}
					
					// Num Alerti Hard
					$this->izrisPodatka($user['rowNumHard']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowNumHard'];
					if($user['rowNumHard'] != 0){$sprozenaOpozorila++;}
					
					// Num Alerti Soft
					$this->izrisPodatka($user['rowNumSoft']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowNumSoft'];
					if($user['rowNumSoft'] != 0){$sprozenaOpozorila++;}
					
					// Sum Alerti Hard
					$this->izrisPodatka($user['rowSumHard']);
					//$sprozenaOpozorila = $user['rowSumHard'];
					if($user['rowSumHard'] != 0){$sprozenaOpozorila++;}
					
					// Sum Alerti Soft
					$this->izrisPodatka($user['rowSumSoft']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowSumSoft'];
					if($user['rowSumSoft'] != 0){$sprozenaOpozorila++;}

					// Alerti Hard Validation
					$this->izrisPodatka($user['rowHardValidation']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowHardValidation'];
					if($user['rowHardValidation'] != 0){$sprozenaOpozorila++;}
					
					// Alerti Soft Validation
					$this->izrisPodatka($user['rowSoftValidation']);
					//$sprozenaOpozorila = $sprozenaOpozorila + $user['rowSoftValidation'];					
					if($user['rowSoftValidation'] != 0){$sprozenaOpozorila++;}
					
					
					//Izracun vsote sprozenih opozoril - po stolpcih
					//Alerti obveznih vprasanj
					$sumObveznihVprasanjHard = $sumObveznihVprasanjHard + $user['rowHardAlert'];
					$sumObveznihVprasanjSoft = $sumObveznihVprasanjSoft + $user['rowSoftAlert'];				
					//Num Alerti
					$sumNumAlertHard = $sumNumAlertHard + $user['rowNumHard'];
					$sumNumAlertSoft = $sumNumAlertSoft + $user['rowNumSoft'];
					//Sum Alerti
					$sumSumAlertHard = $sumSumAlertHard + $user['rowSumHard'];
					$sumSumAlertSoft = $sumSumAlertSoft + $user['rowSumSoft'];
					//Alerti validation
					$sumValidationHard = $sumValidationHard + $user['rowHardValidation'];
					$sumValidationSoft = $sumValidationSoft + $user['rowSoftValidation'];				
					//Izracun vsote sprozenih opozoril - po stolpcih - konec
					
					//Izracun stevila vseh moznih opozoril, ki ga potrebujemo za izracun kakovosti
					$steviloVsehMoznihOpozorilSumHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilSumHard');
					$steviloVsehMoznihOpozorilSumSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilSumSoft');
					$steviloVsehMoznihOpozorilNumHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilNumHard');
					$steviloVsehMoznihOpozorilNumSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilNumSoft');
					$steviloVsehMoznihOpozorilHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilHard');
					$steviloVsehMoznihOpozorilSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilSoft');
					$steviloVsehMoznihOpozorilValHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilValHard');
					$steviloVsehMoznihOpozorilValSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilValSoft');
					
					//$steviloVsehMoznihOpozoril = $steviloVsehMoznihOpozorilSumHard + $steviloVsehMoznihOpozorilSumSoft + $steviloVsehMoznihOpozorilNumHard + $steviloVsehMoznihOpozorilNumSoft + $steviloVsehMoznihOpozorilHard + $steviloVsehMoznihOpozorilSoft + $steviloVsehMoznihOpozorilValHard + $steviloVsehMoznihOpozorilValSoft;
					
					if($steviloVsehMoznihOpozorilSumHard != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilSumSoft != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilNumHard != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilNumSoft != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilHard != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilSoft != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilValHard != 0){$steviloVsehMoznihOpozoril++;}
					if($steviloVsehMoznihOpozorilValSoft != 0){$steviloVsehMoznihOpozoril++;}
					
					//Izracun stevila vseh moznih opozoril, ki ga potrebujemo za izracun kakovosti - konec
					
					
					//Stevilo moznih opozoril
					$steviloMoznihOpozorilPoResp = $steviloVsehMoznihOpozorilSumHard + $steviloVsehMoznihOpozorilSumSoft + $steviloVsehMoznihOpozorilNumHard + $steviloVsehMoznihOpozorilNumSoft + $steviloVsehMoznihOpozorilHard + $steviloVsehMoznihOpozorilSoft + $steviloVsehMoznihOpozorilValHard + $steviloVsehMoznihOpozorilValSoft;
					
					$this->izrisPodatka($steviloMoznihOpozorilPoResp, 1);
					
					//Stevilo moznih opozoril - konec
					
					
					//Izracun vsote sprozenih opozoril - po vrsticah				
					$vsotaSprozenihOpozoril = $user['rowHardAlert'] +
									  $user['rowSoftAlert'] + 
									  $user['rowNumHard'] + 
									  $user['rowNumSoft'] + 
									  $user['rowSumHard'] + 
									  $user['rowSumSoft'] + 
									  $user['rowHardValidation'] + 
									  $user['rowSoftValidation']
					;				
					//Izracun vsote sprozenih opozoril - po vrsticah - konec

					echo '<td class="usable bold">'.$vsotaSprozenihOpozoril;	//Vsota sprozenih opozoril				
					

					echo '<td class="sprozenaOpozorilaLine">'.$sprozenaOpozorila; //Stevilo sprozenih opozoril
					
/* 					//Izracun in prikazovanje kakovosti
					$kakovost = $this->izracunKakovosti(count($userData), $sprozenaOpozorila, $steviloVsehMoznihOpozoril);
					$kakovost = SurveyAnalysis::formatNumber($kakovost, 3,'');					
					echo '<td class="sprozenaOpozorila">'.$kakovost;
					//Izracun in prikazovanje kakovosti - konec 
					
					echo '</td>';*/
				
				echo '</tr>';
				$sprozenaOpozorilaAll = $sprozenaOpozorilaAll + $sprozenaOpozorila;
			}
		}
		else if($_GET['m'] == A_REMINDER_TRACKING_VAR)
		{	//ce je prikaz po spremenljivkah
			$sprozenaOpozorilaAll = 0;
			$sumObveznihVprasanjHard = 0;
			$sumObveznihVprasanjSoft = 0;
			$sumNumAlertHard = 0;
			$sumNumAlertSoft = 0;
			$sumSumAlertHard = 0;
			$sumSumAlertSoft = 0;
			$sumValidationHard = 0;
			$sumValidationSoft = 0;
			
			$varsData = array();
			
			// Izpis podatkov za vsako spremenljivko
			foreach($vars as $key => $var){
				// Prva vrstica z vrednostmi
				echo '<tr>';

					//echo '<td rowspan="1" class="vars"><a href="index.php?anketa='.$this->anketa.'&a=data&m=quick_edit&spr_id='.$var['spr_id'].'&quick_view=1">'.$var['spr_id'].'</a></td>';
					echo '<td rowspan="1" class="vars">'.$var['variable'].'</td>';
					//echo '<td rowspan="1" class="vars">'.$var['variable'].' '.count($vars).'</td>';
					
					$sprozenaOpozorila = 0;
					$steviloVsehMoznihOpozoril= 0;
					
					//Alerti obveznih vprasanj Hard
					$this->izrisPodatka($var['rowHardAlert']);
					if($var['rowHardAlert'] != 0){$sprozenaOpozorila++;}					
					
					// Alerti obveznih vprasanj Soft
					$this->izrisPodatka($var['rowSoftAlert']);
					if($var['rowSoftAlert'] != 0){$sprozenaOpozorila++;}
					
					// Num Alerti Hard
					$this->izrisPodatka($var['rowNumHard']);
					if($var['rowNumHard'] != 0){$sprozenaOpozorila++;}
					
					// Num Alerti Soft
					$this->izrisPodatka($var['rowNumSoft']);
					if($var['rowNumSoft'] != 0){$sprozenaOpozorila++;}
					
					// Sum Alerti Hard
					$this->izrisPodatka($var['rowSumHard']);
					if($var['rowSumHard'] != 0){$sprozenaOpozorila++;}
					
					// Sum Alerti Soft
					$this->izrisPodatka($var['rowSumSoft']);
					if($var['rowSumSoft'] != 0){$sprozenaOpozorila++;}
					
					// Alerti Hard Validation
					$this->izrisPodatka($var['rowHardValidation']);
					if($var['rowHardValidation'] != 0){$sprozenaOpozorila++;}

					// Alerti Soft Validation
					$this->izrisPodatka($var['rowSoftValidation']);
					if($var['rowSoftValidation'] != 0){$sprozenaOpozorila++;}
					
					//echo '<td class="sprozenaOpozorila">'.$sprozenaOpozorila;

				//echo '</tr>';
				
				//Izracun vsote sprozenih opozoril - po stolpcih
				//Alerti obveznih vprasanj
				$sumObveznihVprasanjHard = $sumObveznihVprasanjHard + $var['rowHardAlert'];
				$sumObveznihVprasanjSoft = $sumObveznihVprasanjSoft + $var['rowSoftAlert'];				
				//Num Alerti
				$sumNumAlertHard = $sumNumAlertHard + $var['rowNumHard'];
				$sumNumAlertSoft = $sumNumAlertSoft + $var['rowNumSoft'];
				//Sum Alerti
				$sumSumAlertHard = $sumSumAlertHard + $var['rowSumHard'];
				$sumSumAlertSoft = $sumSumAlertSoft + $var['rowSumSoft'];
				//Alerti validation
				$sumValidationHard = $sumValidationHard + $var['rowHardValidation'];
				$sumValidationSoft = $sumValidationSoft + $var['rowSoftValidation'];				
				//Izracun vsote sprozenih opozoril - po stolpcih - konec
				
				//Izracun stevila vseh moznih opozoril, ki ga potrebujemo za izracun kakovosti
				$steviloVsehMoznihOpozorilSumHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilSumHard');
				$steviloVsehMoznihOpozorilSumSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilSumSoft');
				$steviloVsehMoznihOpozorilNumHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilNumHard');
				$steviloVsehMoznihOpozorilNumSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilNumSoft');
				$steviloVsehMoznihOpozorilHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilHard');
				$steviloVsehMoznihOpozorilSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilSoft');
				$steviloVsehMoznihOpozorilValHard = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilValHard');
				$steviloVsehMoznihOpozorilValSoft = $this->steviloMoznihOpozoril($vars, 'steviloVsehMoznihOpozorilValSoft');				
				
				if($steviloVsehMoznihOpozorilSumHard != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilSumSoft != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilNumHard != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilNumSoft != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilHard != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilSoft != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilValHard != 0){$steviloVsehMoznihOpozoril++; }
				if($steviloVsehMoznihOpozorilValSoft != 0){$steviloVsehMoznihOpozoril++; }
					
				//Izracun stevila vseh moznih opozoril, ki ga potrebujemo za izracun kakovosti - konec
				
				
				//Izracun stevila moznih opozoril po var
				//echo '<script>console.log("za spremenljivko je: '.$var['steviloVsehMoznihOpozorilSoft'].' ")</script>';				
				$steviloMoznihOpozorilPoVar = 
					$var['steviloVsehMoznihOpozorilSumHard'] + 
					$var['steviloVsehMoznihOpozorilSumSoft'] +
					$var['steviloVsehMoznihOpozorilNumHard'] +
					$var['steviloVsehMoznihOpozorilNumSoft'] +
					$var['steviloVsehMoznihOpozorilHard'] +
					$var['steviloVsehMoznihOpozorilSoft'] +
					$var['steviloVsehMoznihOpozorilValHard'] +
					$var['steviloVsehMoznihOpozorilValSoft'];
				
				$this->izrisPodatka($steviloMoznihOpozorilPoVar, 1);				
				//Izracun stevila moznih opozoril po var - konec
				
				
				//Izracun vsote sprozenih opozoril - po vrsticah				
				$vsotaSprozenihOpozoril = $var['rowHardAlert'] +
								  $var['rowSoftAlert'] + 
								  $var['rowNumHard'] + 
								  $var['rowNumSoft'] + 
								  $var['rowSumHard'] + 
								  $var['rowSumSoft'] + 
								  $var['rowHardValidation'] + 
								  $var['rowSoftValidation']
				;				
				//Izracun vsote sprozenih opozoril - po vrsticah - konec

				echo '<td class="usable bold">'.$vsotaSprozenihOpozoril;	//Vsota sprozenih opozoril
				
				echo '<td class="sprozenaOpozorilaLine">'.$sprozenaOpozorila;	//Stevilo sprozenih opozoril
				
				echo '</tr>';
				
				$sprozenaOpozorilaAll = $sprozenaOpozorilaAll + $sprozenaOpozorila;
			}
		}
		
		
		//predzadnja vrstica preglednice, ki prikazuje sprozenih opozoril po stolpcih
		echo '<tr class="sum">';			
			//echo '<td> Možnih opozoril za celotno anketo</td>';
			//stevilo moznih opozoril po stolpih
			echo '<td>'.$lang['srv_reminder_tracking_sum_of_errors'].'</td>';
			//echo '<td> '.$this->anketa.'</td>';
			
			//1. stolpec - Vsota obveznih vprasanj Hard
			echo '<td> '.$sumObveznihVprasanjHard.'</td>';
			//1. stolpec - Vsota obveznih vprasanj Hard - konec
			
			//2. stolpec - Vsota obveznih vprasanj Soft
			echo '<td> '.$sumObveznihVprasanjSoft.'</td>';
			//2. stolpec - Vsota obveznih vprasanj Soft - konec
			
			//3. stolpec - Vsota Num opozorila Hard
			echo '<td> '.$sumNumAlertHard.'</td>';
			//3. stolpec - Vsota Num opozorila Hard - konec
			
			//4. stolpec - Vsota Num opozorila Soft
			echo '<td> '.$sumNumAlertSoft.'</td>';
			//4. stolpec - Vsota Num opozorila Soft - konec
			
			//5. stolpec - Vsota Sum opozorila Hard
			echo '<td> '.$sumSumAlertHard.'</td>';
			//5. stolpec - Vsota Sum opozorila Hard - konec
			
			//6. stolpec - Vsota Sum opozorila Soft			
			echo '<td> '.$sumSumAlertSoft.'</td>';
			//6. stolpec - Vsota Sum opozorila Soft - konec	
			
			//7. stolpec - Vsota Validacije Hard
			echo '<td> '.$sumValidationHard.'</td>';
			//7. stolpec - Vsota Validacije Hard - konec
			
			//8. stolpec - Vsota Validacije Soft
			echo '<td> '.$sumValidationSoft.'</td>';
			//8. stolpec - Vsota Validacije Soft - konec

/* 			//9. stolpec - Vsota vseh moznih opozoril
			echo '<td> '.$steviloVsehMoznihOpozoril.'</td>';
			//9. stolpec - Vsota vseh moznih opozoril - konec */
			
			//9. stolpec - Stevilo moznih opozoril po vrsticah
			//echo '<td> '.$steviloMoznihOpozorilPoVrsticah.'</td>';
			echo '<td> </td>';
			//9. stolpec - Stevilo moznih opozoril po vrsticah - konec */
			
			//10. stolpec - Stevilo moznih opozoril po vrsticah
			//echo '<td> '.$steviloMoznihOpozorilPoVrsticah.'</td>';
			echo '<td> </td>';
			//10. stolpec - Stevilo moznih opozoril po vrsticah - konec */
			
			//11. stolpec - Prikaz stevila vseh sprozenih opozoril in vseh moznih opozoril
			echo '<td class="sprozenaOpozorilaLine"> </td>';
			//11. stolpec - Prikaz stevila vseh sprozenih opozoril in vseh moznih opozoril - konec

		echo '</tr>';
		//predzadnja vrstica preglednice, ki prikazuje sprozenih opozoril po stolpcih - konec
		
		//zadnja vrstica preglednice, ki prikazuje stevilo vseh moznih opozoril
		echo '<tr class="sumSprozenih">';			
			//echo '<td> Možnih opozoril za celotno anketo</td>';
			//stevilo moznih opozoril po stolpih
			echo '<td>'.$lang['srv_reminder_tracking_possible_errors'].'</td>';
			//echo '<td> '.$this->anketa.'</td>';
			
			//1. stolpec - Opozorila obveznih vprasanj Hard
			echo '<td> '.$steviloVsehMoznihOpozorilHard.'</td>';
			//1. stolpec - Opozorila obveznih vprasanj Hard - konec
			
			//2. stolpec - Opozorila obveznih vprasanj Soft
			echo '<td> '.$steviloVsehMoznihOpozorilSoft.'</td>';
			//2. stolpec - Opozorila obveznih vprasanj Soft - konec
			
			//3. stolpec - Num opozorila Hard
			echo '<td> '.$steviloVsehMoznihOpozorilNumHard.'</td>';
			//3. stolpec - Num opozorila Hard - konec
			
			//4. stolpec - Num opozorila Soft
			echo '<td> '.$steviloVsehMoznihOpozorilNumSoft.'</td>';
			//4. stolpec - Num opozorila Soft - konec
			
			//5. stolpec - Sum opozorila Hard
			echo '<td> '.$steviloVsehMoznihOpozorilSumHard.'</td>';
			//5. stolpec - Sum opozorila Hard - konec
			
			//6. stolpec - Sum opozorila Soft			
			echo '<td> '.$steviloVsehMoznihOpozorilSumSoft.'</td>';
			//6. stolpec - Sum opozorila Soft - konec	
			
			//7. stolpec - Validacije Hard
			echo '<td> '.$steviloVsehMoznihOpozorilValHard.'</td>';
			//7. stolpec - Validacije Hard - konec
			
			//8. stolpec - Validacije Soft
			echo '<td> '.$steviloVsehMoznihOpozorilValSoft.'</td>';
			//8. stolpec - Validacije Soft - konec

/* 			//9. stolpec - Vsota vseh moznih opozoril
			echo '<td> '.$steviloVsehMoznihOpozoril.'</td>';
			//9. stolpec - Vsota vseh moznih opozoril - konec */
			
			//9. stolpec - Stevilo moznih opozoril po vrsticah
			//echo '<td> '.$steviloMoznihOpozorilPoVrsticah.'</td>';
			echo '<td> </td>';
			//9. stolpec - Stevilo moznih opozoril po vrsticah - konec */
			
			//10. stolpec - Vsota sprozenih
			echo '<td> </td>';
			//10. stolpec - Vsota sprozenih - konec
			
			//11. stolpec - Prikaz stevila vseh sprozenih opozoril in vseh moznih opozoril
			echo '<td > '.$sprozenaOpozorilaAll.'/'.$steviloVsehMoznihOpozoril.'</td>';
			//11. stolpec - Prikaz stevila vseh sprozenih opozoril in vseh moznih opozoril - konec
			
			//Izracun in prikazovanje kakovosti
			//$kakovost = $this->izracunKakovosti(count($userData), $sprozenaOpozorilaAll, $steviloVsehMoznihOpozoril);
			$kakovost = $this->izracunKakovosti($numOf, $sprozenaOpozorilaAll, $steviloVsehMoznihOpozoril);
			$kakovost = SurveyAnalysis::formatNumber($kakovost, 3,'');					
			echo '<td  class="sprozenaOpozorilaLine">'.$kakovost;
			echo '</td>';
			//Izracun in prikazovanje kakovosti - konec 
			
		echo '</tr>';
		//zadnja vrstica preglednice, ki prikazuje stevilo vseh moznih opozoril - konec
		echo '</table>';
		
		
		echo '</div>';
	}
	
	//metoda, ki skrbi za izbiro sloga podatka v preglednici
	private function izrisPodatka($podatek, $stolpec1=0){
		if ($podatek != 0){	//ce je podatek razlicen od 0 
			if($stolpec1){
				$redBg = '';
			}else{
				$redBg = ' redCell';
			}
			//$slog = 'data sum bold';	//naj bo slog bold
			//$slog = 'data sum bold red';	//naj bo slog bold in stevilke rdece barve
			$slog = 'data bold'.$redBg;	//naj bo slog bold in ozadje celice rdece barve, ce niso celice zadnjega stolpca
		}else{
			$slog = 'data';
		}		
		echo '<td class="'.$slog.'">'.$podatek.'</td>';		
	}
		
	function displayUsableSettings(){
		global $lang;
		
		// Div z nastavitvami ki se razpre
		echo '<div id="dataSettingsCheckboxes" '.($this->displayEditIconsSettings ? '' : ' style="display:none;"').'>';		
		echo '<div id="toggleDataCheckboxes2" onClick="toggleDataCheckboxes(\'usability\');"><span class="faicon close icon-orange" style="padding-bottom:2px;"></span> '.$lang['srv_data_settings_checkboxes2'].'</div>';

		
		echo '<div id="usable_respondents_settings">';
	
		echo $lang['srv_usableResp_limit'].': ';
	
		echo '<span class="spaceLeft spaceRight">'.$lang['srv_usableResp_bottom_limit'].': <input type="text" id="bottom_usable_limit" size="2" onblur="changeUsableRespSetting(this);" value="'.$this->bottom_usable_limit.'" />%</span>';
		echo '<span class="spaceLeft spaceRight">'.$lang['srv_usableResp_top_limit'].': <input type="text" id="top_usable_limit" size="2" onblur="changeUsableRespSetting(this);" value="'.$this->top_usable_limit.'" />%</span>';
	
		echo '<br />';		
		
		echo '<div style="margin-top:10px;">';
		echo $lang['srv_usableResp_show'].': ';
	
		// Prikaz neničelnih stolpcev
		echo '<label class="spaceLeft spaceRight">';
		echo '<input type="checkbox" id="show_with_zero" onclick="changeUsableRespSetting(this);" '.($this->show_with_zero == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_usableResp_showZero'];
		echo '</label>';
		
		// Prikaz podrobnosti
		echo '<label class="spaceLeft spaceRight">';
		echo '<input type="checkbox" id="show_details" onclick="changeUsableRespSetting(this);" '.($this->show_details == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_usableResp_showDetails'];
		echo '</label>';	
		
		// Prikaz podrobnih izracunov
		echo '<label class="spaceLeft">';
		echo '<input type="checkbox" id="show_calculations" onclick="changeUsableRespSetting(this);" '.($this->show_calculations == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_usableResp_showCalc'];
		echo '</label>';
		echo '</div>';
		
		echo '</div>';
		
		
		echo '</div>';
	}
	
	
	/** Funkcija ki nastavi vse filtre
	 *
	 */
	private function setUpFilter(){
		/*if ($this->dataFileStatus == FILE_STATUS_NO_DATA
				|| $this->dataFileStatus == FILE_STATUS_NO_FILE
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED)
		{
			return false;
		}*/

		# poiščemo kater profil uporablja uporabnik
		$_currentMissingProfile = SurveyUserSetting :: getInstance()->getSettings('default_missing_profile');
		$this->currentMissingProfile = (isset($_currentMissingProfile) ? $_currentMissingProfile : 1);

		# filtriranje po statusih
		$this->_CURRENT_STATUS_FILTER = SurveyStatusProfiles :: getStatusAsAWKString();

		# filtriranje po časih
		$_time_profile_awk = SurveyTimeProfiles :: getFilterForAWK($this->_HEADERS['unx_ins_date']['grids']['0']['variables']['0']['sequence']);

		# dodamo še ife

		SurveyConditionProfiles :: setHeader($this->_HEADERS);
		$_condition_profile_AWK = SurveyConditionProfiles:: getAwkConditionString();

		if (($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) 
				|| ($_time_profile_awk != "" && $_time_profile_awk != null)) 
		{
			$this->_CURRENT_STATUS_FILTER  = '('.$this->_CURRENT_STATUS_FILTER;
			if ($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) 
			{
				$this->_CURRENT_STATUS_FILTER .= ' && '.$_condition_profile_AWK;
			}
			if ($_time_profile_awk != "" && $_time_profile_awk != null) 
			{
				$this->_CURRENT_STATUS_FILTER .= ' && '.$_time_profile_awk;
			}
			$this->_CURRENT_STATUS_FILTER .= ')';
		}
		$status_filter = $this->_CURRENT_STATUS_FILTER;

		if ($this->dataFileStatus == FILE_STATUS_OK || $this->dataFileStatus == FILE_STATUS_OLD) 
		{
			if (isset($this->_HEADERS['testdata'])) 
			{
				$this->_HAS_TEST_DATA = true;
			}
		}
		
		$smv = new SurveyMissingValues($this->anketa);
		$smv -> Init();
		
		$smv_array = $smv->GetSurveyMissingValues($this->anketa);
		if (!empty($smv_array[1])){
			foreach ($smv_array[1] AS $_survey_missings)
			{
				$this->_missings[$_survey_missings['value']] = $_survey_missings['text'];
				
			}
		}
		if (!empty($smv_array[2])){
			foreach ($smv_array[2] AS $_survey_unsets)
			{
				$this->_unsets[$_survey_unsets['value']] = $_survey_unsets['text'];
			}
		}
	}

	private function setUserFilters(){
		# Nastavimo filtre variabel
		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		if ($dvp != $_currentVariableProfile) {
			SurveyUserSetting :: getInstance()->saveSettings('default_variable_profile', $_currentVariableProfile);
		}
		$this->_PROFILE_ID_VARIABLE = $_currentVariableProfile;

		# ali prikazujemo tudi stolpce z 0 vrednostmi
		if (isset($this->sessionData['usable_resp']['show_with_zero'])) {
			$this->show_with_zero = $this->sessionData['usable_resp']['show_with_zero'];
		} 
		
		# ali prikazujemo tudi stolpce z 0 vrednostmi
		if (isset($this->sessionData['usable_resp']['show_details'])) {
			$this->show_details = $this->sessionData['usable_resp']['show_details'];
		} 
		
		# ali prikazujemo tudi stolpce z izracuni
		if (isset($this->sessionData['usable_resp']['show_calculations'])) {
			$this->show_calculations = $this->sessionData['usable_resp']['show_calculations'];
		} 

		# ali prikazujemo vrstice "Drugo"
		$this->show_with_other = true;
		if (isset($this->sessionData['usable_resp']['show_with_other'])) {
			$this->show_with_other = $this->sessionData['usable_resp']['show_with_other'];
		}

		# ali prikazujemo vrstice tipa "besedilo"
		$this->show_with_text = true;
		if (isset($this->sessionData['usable_resp']['show_with_text'])) {
			$this->show_with_text = $this->sessionData['usable_resp']['show_with_text'];
		}
		
		# Spodnja in zgornja meja za usable
		if (isset($this->sessionData['usable_resp']['bottom_usable_limit'])) {
			$this->bottom_usable_limit = $this->sessionData['usable_resp']['bottom_usable_limit'];
		}
		# ali prikazujemo tudi stolpce z 0 vrednostmi
		if (isset($this->sessionData['usable_resp']['top_usable_limit'])) {
			$this->top_usable_limit = $this->sessionData['usable_resp']['top_usable_limit'];
		}
	}
	
	
	// Zgeneriramo datoteko in izracunamo frekvence (stevilo 1, -1, -2...) za vsako enoto
	private function collectData(){
		global $site_path;
		
		# polovimo frekvence statusov odgovorov za posameznega respondenta (stevilo -1, -2...)
		$folder = $site_path . EXPORT_FOLDER.'/';
		
        #array za imeni tmp fajlov, ki jih nato izbrišemo
        $tmp_file = $folder.'tmp_export_'.$this->anketa.'_reminder_tracking_recnum.php';
		
		# pobrišemo sfiltrirane podatke, ker jih več ne rabimo
		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
		
		$status_filter = $this->_CURRENT_STATUS_FILTER;

		# s katero sekvenco se začnejo podatki, da ne delamo po nepotrebnem za ostala polja
		$start_sequence = $this->_HEADERS['_settings']['dataSequence'];
		# s katero sekvenco se končajo podatki da ne delamo po nepotrebnem za ostala polja
		$end_sequence = $this->_HEADERS['_settings']['metaSequence']-1;
		# sekvenca recnuma
		$recnum_sequence = $this->_HEADERS['recnum']['sequences'];

		# naredimo datoteko  z frekvencami
		# za windows sisteme in za linux sisteme
		
		if (IS_WINDOWS ) {
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" "{{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}}  {{for (n in arr) {delete arr[n]}}} {delete arr} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[$i]++}} '.$status_filter.' {{for (n in arr) { print NR,$'.$recnum_sequence.',$1,n,arr[n]}}}" '.$this->dataFileName;
			$command .= ' | sed "s*\x27*`*g" ';
			$command .= ' | awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} { print \"$frequency[\",$1,\"]\",\"[\x27\",$4,\"\x27]\",\"=\",$5,\";\"} { print \"$frequency[\",$1,\"]\",\"[usr_id]\",\"=\",$3,\";\"} { print \"$frequency[\",$1,\"]\",\"[recnum]\",\"=\",$2,\";\"}" >> '.$tmp_file;
		} 

		else {
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" \' {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} {{for (n in arr) {delete arr[n]}}} {delete arr} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[$i]++}} '.$status_filter.' {{for (n in arr) { print NR,$'.$recnum_sequence.',$1,n,arr[n]}}}\' '.$this->dataFileName;
			$command .= ' | sed \'s*\x27*`*g\' ';
			$command .= ' | awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} { print "$frequency[",$1,"]","[\x27",$4,"\x27]","=\x27",$5,"\x27;"} { print "$frequency[",$1,"]","[usr_id]","=\x27",$3,"\x27;"} { print "$frequency[",$1,"]","[recnum]","=\x27",$2,"\x27;"}\' >> '.$tmp_file;
		}
		
		$file_handler = fopen($tmp_file,"w");
		fwrite($file_handler,"<?php\n");
		fclose($file_handler);
	
		$out = shell_exec($command);

		$file_handler = fopen($tmp_file,"a");
		fwrite($file_handler,'?>');
		fclose($file_handler);
		include($tmp_file);

		# pobrišemo sfiltrirane podatke, ker jih več ne rabimo
 		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}

		// Loop cez podatke da vidimo kateri stolpci so prazni
		foreach ($frequency AS $seq => $freqData) {

			if (empty($freqData)) { 
				continue;
			}
				
			foreach ($freqData AS $key => $cnt){
				if (is_numeric($key) && (isset($this->_missings[(int)$key]) || isset($this->_unsets[(int)$key])))
					$this->cols_with_value[(int)$key] += $cnt;
				elseif($key != 'recnum')
					$this->cols_with_value['valid'] += $cnt;
			}
		}

		return $frequency;
	}
	
	// Gremo cez enote in izracunamo uporabne vrednosti (uporabnost, breakoff...)
	public function calculateData(){
		
		// Preko datoteke preberemo frekvence za posamezne userje (1, -1, -2...)
		$data = $this->collectData();
		$ank_id = $this->anketa;
		$css = '';
		$usability = array();
		
		// Loop cez vse responente
		$counter = 1;
		foreach($data as $key1 => $frequencies){

			// Prestejemo vse in veljavne
			$all = 0;
			$valid = 0;
			$usable = 0;			// valid + nonsubstantive
			$nonsubstantive = 0;	// -97,-98...
			$nonresponse = 0;		// -1
			$preskok = 0;			// -2
			$breakoff = 0;			// -3
			$naknadno = 0;			// -4
			$status = 0;			// 0->unusable, 1->part. usable, 2->usable
			foreach($frequencies as $key => $cnt){

				if(!array_key_exists($key, $this->_missings) && !array_key_exists($key, $this->_unsets) && $key!==''){
					$valid += $cnt;
					$usable += $cnt;
				}
				
				if(array_key_exists($key, $this->_unsets)){
					$nonsubstantive += $cnt;
					$usable += $cnt;
				}
				
				if($key == -1)
					$nonresponse += $cnt;
					
				if($key == -3)
					$breakoff+= $cnt;
					
				if($key == -2)
					$preskok+= $cnt;
					
				if($key == -4)
					$naknadno+= $cnt;
				
				$all += $cnt;
			}
			
			// odstejemo polje recnum
			$all -= $frequencies['recnum']; 
			$valid -= $frequencies['recnum']; 
			$usable -= $frequencies['recnum']; 
			
			// odstejemo polje usr_id
			$all -= $frequencies['usr_id']; 
			$valid -= $frequencies['usr_id']; 
			$usable -= $frequencies['usr_id'];
			

/*   			echo '
				<script>
					console.log('.$frequencies['usr_id'].');
				</script>
			'; */
			//*************************** Pobiranje in stetje alertov po respondentih *****************************************************
			$user = $frequencies['usr_id'];
			//poberi stevilo hard in soft alertov za vsoto
			$sqlSumHard = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfSumHard FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND (a.type LIKE '%hard%' AND a.trigger_type = 'sum')");
			$sqlSumSoft = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfSumSoft FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND (a.type LIKE '%soft%' AND a.trigger_type = 'sum')");
			
			//poberi stevilo hard in soft alertov za stevilo
			$sqlNumHard = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfNumHard FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND (a.type LIKE '%hard%' AND a.trigger_type = 'num')");
			$sqlNumSoft = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfNumSoft FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND (a.type LIKE '%soft%' AND a.trigger_type = 'num')");
			
			//poberi stevilo hard in soft alertov
			$sqlHardAlert = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfHardAlert FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND trigger_type = 'har'");
			$sqlSoftAlert = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfSoftAlert FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND trigger_type = 'sof'");
			
			//poberi stevilo hard in soft validacij
			$sqlHardValidation = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfHardValidation FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND (a.type LIKE '%hard%' AND a.trigger_type = 'val')");
			$sqlSoftValidation = sisplet_query("SELECT COUNT(d.usr_id) AS NumberOfSoftValidation FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE d.usr_id = '$user' AND d.ank_id='$ank_id' AND (a.type LIKE '%soft%' AND a.trigger_type = 'val')");
			
			$rowSumHard = mysqli_fetch_assoc($sqlSumHard);
			$rowSumSoft = mysqli_fetch_assoc($sqlSumSoft);
			
			$rowNumHard = mysqli_fetch_assoc($sqlNumHard);
			$rowNumSoft = mysqli_fetch_assoc($sqlNumSoft);
			
			$rowHardAlert = mysqli_fetch_assoc($sqlHardAlert);
			$rowSoftAlert = mysqli_fetch_assoc($sqlSoftAlert);
			
			$rowHardValidation = mysqli_fetch_assoc($sqlHardValidation);
			$rowSoftValidation = mysqli_fetch_assoc($sqlSoftValidation);
			
			//************************************************************************************************************* po respondentih - konec

			//$validPercent = ($all > 0) ? ($valid / $all * 100) : 0;
			//$nonsubstantivePercent = ($valid + $nonsubstantive > 0) ? ($nonsubstantive / ($valid + $nonsubstantive) * 100) : 0;
			//$nonresponsePercent = ($valid + $nonsubstantive + $nonresponse > 0) ? ($nonresponse / ($valid + $nonsubstantive + $nonresponse) * 100) : 0;
			$validPercent = ($valid + $nonsubstantive + $nonresponse > 0) ? ($valid / ($valid + $nonsubstantive + $nonresponse) * 100) : 0;
			$nonsubstantivePercent = ($valid + $nonsubstantive + $nonresponse > 0) ? ($nonsubstantive / ($valid + $nonsubstantive + $nonresponse) * 100) : 0;
			$nonresponsePercent = ($valid + $nonsubstantive + $nonresponse > 0) ? ($nonresponse / ($valid + $nonsubstantive + $nonresponse) * 100) : 0;
			
			//$breakoffPercent = ($valid + $nonsubstantive + $nonresponse + $breakoff > 0) ? ($breakoff / ($valid + $nonsubstantive + $nonresponse + $breakoff) * 100) : 0;
			$breakoffPercent =  $breakoff / $all * 100;
			
			//$usablePercent = ($valid + $nonsubstantive + $nonresponse + $breakoff > 0) ? ($usable / ($valid + $nonsubstantive + $nonresponse + $breakoff) * 100) : 0;
			
			// Posebni izracuni
			$UNL = (($valid + $nonsubstantive + $nonresponse) > 0) ? $nonresponse / ($valid + $nonsubstantive + $nonresponse) : 0;	// Delez neodgovorov
			$UBL = ($all > 0) ? $breakoff / $all : 0;	// Delez prekinitev
			$UML = $UBL + (1 - $UBL) * $UNL;
			$UCL = 1 - $UML;	// Uporabnost
			$UIL = (($valid + $nonsubstantive + $nonresponse + $preskok) > 0) ? $preskok /($valid + $nonsubstantive + $nonresponse + $preskok) : 0;	// Delez preskokov
			$UAQ = ($all > 0) ? $naknadno / $all : 0;	// Delez naknadnih
			
			// Ce nimamo veljavnih in nevsebinskih je UCL vedno 0
			$UCL = ($valid + $nonsubstantive == 0) ? 0 : $UCL;
			
			$usablePercent = $UCL * 100;
			
			if($usablePercent < (int)$this->bottom_usable_limit){
				$css_usable = 'unusable';
				$status = 0;
				$usability['unusable']++;
			}
			elseif($usablePercent >= (int)$this->bottom_usable_limit && $usablePercent < (int)$this->top_usable_limit){
				$css_usable = 'partusable';
				$status = 1;
				$usability['partusable']++;
			}
			else{
				$css_usable = 'usable';
				$status = 2;
				$usability['usable']++;
			}
			$usability['all']++;
			
			// Nastavimo izracunane podatke za respondenta
			$usability['data'][$counter]['recnum'] = $frequencies['recnum'];
			$usability['data'][$counter]['usr_id'] = $frequencies['usr_id'];
			$usability['data'][$counter]['css'] = $css;
			$usability['data'][$counter]['status'] = $status;
			$usability['data'][$counter]['all'] = $all;
			$usability['data'][$counter]['valid'] = $valid;
			$usability['data'][$counter]['nonsubstantive'] = $nonsubstantive;
			$usability['data'][$counter]['nonresponse'] = $nonresponse;
			$usability['data'][$counter]['breakoff'] = $breakoff;
			$usability['data'][$counter]['usable'] = $usable;
			$usability['data'][$counter]['validPercent'] = $validPercent;
			$usability['data'][$counter]['usablePercent'] = $usablePercent;
			$usability['data'][$counter]['nonsubstantivePercent'] = $nonsubstantivePercent;
			$usability['data'][$counter]['nonresponsePercent'] = $nonresponsePercent;
			$usability['data'][$counter]['breakoffPercent'] = $breakoffPercent;
			$usability['data'][$counter]['UNL'] = $UNL;
			$usability['data'][$counter]['UML'] = $UML;
			$usability['data'][$counter]['UCL'] = $UCL;
			$usability['data'][$counter]['UIL'] = $UIL;
			$usability['data'][$counter]['UAQ'] = $UAQ;
			
			//************ Za alerte ************
 			$usability['data'][$counter]['rowSumHard'] = $rowSumHard['NumberOfSumHard'];
			$usability['data'][$counter]['rowSumSoft'] = $rowSumSoft['NumberOfSumSoft'];
			$usability['data'][$counter]['rowNumHard'] = $rowNumHard['NumberOfNumHard'];
			$usability['data'][$counter]['rowNumSoft'] = $rowNumSoft['NumberOfNumSoft'];
			$usability['data'][$counter]['rowHardAlert'] = $rowHardAlert['NumberOfHardAlert'];
			$usability['data'][$counter]['rowSoftAlert'] = $rowSoftAlert['NumberOfSoftAlert'];
 			$usability['data'][$counter]['rowHardValidation'] = $rowHardValidation['NumberOfHardValidation'];
			$usability['data'][$counter]['rowSoftValidation'] = $rowSoftValidation['NumberOfSoftValidation'];
			//***********************************

			// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
			foreach ($this->_missings AS $value => $text){
				$usability['data'][$counter][$value] = (isset($frequencies[$value]) ? $frequencies[$value] : '0');
			}
			foreach ($this->_unsets AS $value => $text){
				$usability['data'][$counter][$value] = (isset($frequencies[$value]) ? $frequencies[$value] : '0');
			}


			$css = ($css == 'colored') ? '' : 'colored';
			$counter++;
		}	

		return $usability;
	}
	
	
	private function calculateDataVars($usr_id){
		global $site_path;
		$usability_vars = array();

		//*************************** Pobiranje in stetje alertov po spremenljivkah *****************************************************

		$defaultProfile = SurveyStatusProfiles::getDefaultProfile();	//ce je 1, imamo "Vse enota", ce je 2, imamo "Ustrezne" enote	
		$ank_id = $this->anketa;
		
	
		$counter = 0;
		
		if($defaultProfile == 2){	//ce imamo "Ustrezne", najdi ne respondente
			$sql_lurkers_hard_alert = sisplet_query("SELECT id FROM srv_user WHERE ank_id = '$ank_id' and lurker = 1");//poberi podatki o lurker oz. ne respondentov
			while($row_lurkers_hard_alert = mysqli_fetch_array($sql_lurkers_hard_alert)){
/* 				echo'
					<script>
						console.log('.$row_lurkers_hard_alert['id'].');
					</script>				
				'; */
				$lurkers = $lurkers.' and usr_id !='.$row_lurkers_hard_alert['id'];
			}
/* 			echo'
				<script>
					console.log(\''.$lurkers.'\');
				</script>				
			'; */
		}

		$sql_spr_id = sisplet_query("SELECT element_spr FROM srv_branching WHERE ank_id = ".$ank_id); //poberi id spremenljivk		
		while($row_spr_id = mysqli_fetch_array($sql_spr_id)){
			
			$spr_id = $row_spr_id['element_spr'];
			
			if($spr_id){	//ce $spr_id ni nula

				$sql_spr_variable = sisplet_query("SELECT variable FROM srv_spremenljivka WHERE id=$spr_id");//poberi id spremenljivk
				$row_spr_variable = mysqli_fetch_assoc($sql_spr_variable);
				$usability_vars[$counter]['variable'] = $row_spr_variable['variable'];	
				
				//Hard alerts**************************
				$query_variable_hard_alert = "SELECT COUNT(a.trigger_id) AS NumOfHardAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND a.trigger_type='har' ";
				if($defaultProfile == 2){ //ce imamo "Ustrezne"	
					$query_variable_hard_alert = $query_variable_hard_alert.''.$lurkers;
				}
				$sql_variable_hard_alert = sisplet_query($query_variable_hard_alert);
				$row_variable_hard_alert = mysqli_fetch_assoc($sql_variable_hard_alert);
				$usability_vars[$counter]['rowHardAlert'] = $row_variable_hard_alert['NumOfHardAlerts'];
				
				$query_steviloVsehMoznihOpozorilHard = "SELECT COUNT(id) AS steviloVsehMoznihOpozorilHard FROM `srv_spremenljivka` WHERE id = '$spr_id' AND reminder = 2";	
				$sql_steviloVsehMoznihOpozorilHard = sisplet_query($query_steviloVsehMoznihOpozorilHard);
				$row_steviloVsehMoznihOpozorilHard = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilHard);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilHard'] = $row_steviloVsehMoznihOpozorilHard['steviloVsehMoznihOpozorilHard'];
				
				//Hard alerts - konec**************************
				
				//Soft alerts**************************
				$query_variable_soft_alert = "SELECT COUNT(a.trigger_id) AS NumOfSoftAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND a.trigger_type='sof' ";
				if($defaultProfile == 2){ //ce imamo "Ustrezne"	
					$query_variable_soft_alert = $query_variable_soft_alert.''.$lurkers;
				}		
				$sql_variable_soft_alert = sisplet_query($query_variable_soft_alert);			
				$row_variable_soft_alert = mysqli_fetch_assoc($sql_variable_soft_alert);
				$usability_vars[$counter]['rowSoftAlert'] = $row_variable_soft_alert['NumOfSoftAlerts'];
				
				$query_steviloVsehMoznihOpozorilSoft = "SELECT COUNT(id) AS steviloVsehMoznihOpozorilSoft FROM `srv_spremenljivka` WHERE id = '$spr_id' AND reminder = 1";	
				$sql_steviloVsehMoznihOpozorilSoft = sisplet_query($query_steviloVsehMoznihOpozorilSoft);
				$row_steviloVsehMoznihOpozorilSoft = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilSoft);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilSoft'] = $row_steviloVsehMoznihOpozorilSoft['steviloVsehMoznihOpozorilSoft'];
				//Soft alerts - konec**************************
				
				//Num Hard alerts**************************		
				$query_variable_num_hard_alert = "SELECT COUNT(a.trigger_id) AS NumOfNumHardAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND (a.type LIKE '%hard%' AND a.trigger_type = 'num') ";
				if($defaultProfile == 2){	//ce imamo "Ustrezne"
					$query_variable_num_hard_alert = $query_variable_num_hard_alert.''.$lurkers;
				}
				$sql_variable_num_hard_alert = sisplet_query($query_variable_num_hard_alert);
				$row_variable_num_hard_alert = mysqli_fetch_assoc($sql_variable_num_hard_alert);
				$usability_vars[$counter]['rowNumHard'] = $row_variable_num_hard_alert['NumOfNumHardAlerts'];
				
				$query_steviloVsehMoznihOpozorilNumHard = "SELECT COUNT(id) AS steviloVsehMoznihOpozorilNumHard FROM `srv_spremenljivka` WHERE id = '$spr_id' AND vsota_reminder = 2 AND tip = 7";	
				$sql_steviloVsehMoznihOpozorilNumHard = sisplet_query($query_steviloVsehMoznihOpozorilNumHard);
				$row_steviloVsehMoznihOpozorilNumHard = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilNumHard);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilNumHard'] = $row_steviloVsehMoznihOpozorilNumHard['steviloVsehMoznihOpozorilNumHard'];
				//Num Hard alerts - konec**************************
				
				//Num Soft alerts**************************
				$query_variable_num_soft_alert = "SELECT COUNT(a.trigger_id) AS NumOfNumSoftAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND (a.type LIKE '%soft%' AND a.trigger_type = 'num')  ";
				if($defaultProfile == 2){ //ce imamo "Ustrezne"	
					$query_variable_num_soft_alert = $query_variable_num_soft_alert.''.$lurkers;
				}		
				$sql_variable_num_soft_alert = sisplet_query($query_variable_num_soft_alert);	
				$row_variable_num_soft_alert = mysqli_fetch_assoc($sql_variable_num_soft_alert);
				$usability_vars[$counter]['rowNumSoft'] = $row_variable_num_soft_alert['NumOfNumSoftAlerts'];
				
				$query_steviloVsehMoznihOpozorilNumSoft = "SELECT COUNT(id) AS steviloVsehMoznihOpozorilNumSoft FROM `srv_spremenljivka` WHERE id = '$spr_id' AND vsota_reminder = 1 AND tip = 7";	
				$sql_steviloVsehMoznihOpozorilNumSoft = sisplet_query($query_steviloVsehMoznihOpozorilNumSoft);
				$row_steviloVsehMoznihOpozorilNumSoft = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilNumSoft);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilNumSoft'] = $row_steviloVsehMoznihOpozorilNumSoft['steviloVsehMoznihOpozorilNumSoft'];
				//Num Soft alerts - konec**************************
				
				//Sum Hard alerts**************************				
				$query_variable_sum_hard_alert = "SELECT COUNT(a.trigger_id) AS NumOfSumHardAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND (a.type LIKE '%hard%' AND a.trigger_type = 'sum') ";			
				if($defaultProfile == 2){	//ce imamo "Ustrezne"
					$query_variable_sum_hard_alert = $query_variable_sum_hard_alert.''.$lurkers;
				}
				$sql_variable_sum_hard_alert = sisplet_query($query_variable_sum_hard_alert);
				$row_variable_sum_hard_alert = mysqli_fetch_assoc($sql_variable_sum_hard_alert);
				$usability_vars[$counter]['rowSumHard'] = $row_variable_sum_hard_alert['NumOfSumHardAlerts'];
				
				$query_steviloVsehMoznihOpozorilSumHard = "SELECT COUNT(id) AS steviloVsehMoznihOpozorilSumHard FROM `srv_spremenljivka` WHERE id = '$spr_id' AND vsota_reminder = 2 AND tip = 18";	
				$sql_steviloVsehMoznihOpozorilSumHard = sisplet_query($query_steviloVsehMoznihOpozorilSumHard);
				$row_steviloVsehMoznihOpozorilSumHard = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilSumHard);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilSumHard'] = $row_steviloVsehMoznihOpozorilSumHard['steviloVsehMoznihOpozorilSumHard'];			
				//Sum Hard alerts - konec**************************
				
				//Sum Soft alerts**************************
				$query_variable_sum_soft_alert = "SELECT COUNT(a.trigger_id) AS NumOfSumSoftAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND (a.type LIKE '%soft%' AND a.trigger_type = 'sum') ";
				if($defaultProfile == 2){ //ce imamo "Ustrezne"	
					$query_variable_sum_soft_alert = $query_variable_sum_soft_alert.''.$lurkers;
				}		
				$sql_variable_sum_soft_alert = sisplet_query($query_variable_sum_soft_alert);
				$row_variable_sum_soft_alert = mysqli_fetch_assoc($sql_variable_sum_soft_alert);
				$usability_vars[$counter]['rowSumSoft'] = $row_variable_sum_soft_alert['NumOfSumSoftAlerts'];

				$query_steviloVsehMoznihOpozorilSumSoft = "SELECT COUNT(id) AS steviloVsehMoznihOpozorilSumSoft FROM `srv_spremenljivka` WHERE id = '$spr_id' AND vsota_reminder = 1 AND tip = 18";	
				$sql_steviloVsehMoznihOpozorilSumSoft = sisplet_query($query_steviloVsehMoznihOpozorilSumSoft);
				$row_steviloVsehMoznihOpozorilSumSoft = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilSumSoft);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilSumSoft'] = $row_steviloVsehMoznihOpozorilSumSoft['steviloVsehMoznihOpozorilSumSoft'];			
				//Sum Soft alerts - konec**************************
				
				//Val Hard alerts**************************			
				$query_variable_val_hard_alert = "SELECT COUNT(a.trigger_id) AS NumOfValHardAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND (a.type LIKE '%hard%' AND a.trigger_type = 'val') ";			
				if($defaultProfile == 2){	//ce imamo "Ustrezne"
					$query_variable_val_hard_alert = $query_variable_val_hard_alert.''.$lurkers;
				}
				$sql_variable_val_hard_alert = sisplet_query($query_variable_val_hard_alert);
				$row_variable_val_hard_alert = mysqli_fetch_assoc($sql_variable_val_hard_alert);
				$usability_vars[$counter]['rowHardValidation'] = $row_variable_val_hard_alert['NumOfValHardAlerts'];
				
				$query_steviloVsehMoznihOpozorilValHard = "SELECT COUNT(reminder) AS steviloVsehMoznihOpozorilValHard FROM `srv_validation` WHERE spr_id = '$spr_id' AND reminder = '2'";	
				$sql_steviloVsehMoznihOpozorilValHard = sisplet_query($query_steviloVsehMoznihOpozorilValHard);
				$row_steviloVsehMoznihOpozorilValHard = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilValHard);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilValHard'] = $row_steviloVsehMoznihOpozorilValHard['steviloVsehMoznihOpozorilValHard'];
				//Val Hard alerts - konec**************************
				
				//Val Soft alerts**************************
				$query_variable_val_soft_alert = "SELECT COUNT(a.trigger_id) AS NumOfValSoftAlerts FROM srv_advanced_paradata_alert a INNER JOIN srv_advanced_paradata_page d ON a.page_id=d.id WHERE a.trigger_id = '$spr_id' AND d.ank_id='$ank_id' AND (a.type LIKE '%soft%' AND a.trigger_type = 'val') ";
				if($defaultProfile == 2){ //ce imamo "Ustrezne"	
					$query_variable_val_soft_alert = $query_variable_val_soft_alert.''.$lurkers;
				}		
				$sql_variable_val_soft_alert = sisplet_query($query_variable_val_soft_alert);
				$row_variable_val_soft_alert = mysqli_fetch_assoc($sql_variable_val_soft_alert);
				$usability_vars[$counter]['rowSoftValidation'] = $row_variable_val_soft_alert['NumOfValSoftAlerts'];
				
				$query_steviloVsehMoznihOpozorilValSoft = "SELECT COUNT(reminder) AS steviloVsehMoznihOpozorilValSoft FROM `srv_validation` WHERE spr_id = '$spr_id' AND reminder = '1'";	
				$sql_steviloVsehMoznihOpozorilValSoft = sisplet_query($query_steviloVsehMoznihOpozorilValSoft);
				$row_steviloVsehMoznihOpozorilValSoft = mysqli_fetch_assoc($sql_steviloVsehMoznihOpozorilValSoft);
				$usability_vars[$counter]['steviloVsehMoznihOpozorilValSoft'] = $row_steviloVsehMoznihOpozorilValSoft['steviloVsehMoznihOpozorilValSoft'];
				//Val Soft alerts - konec**************************
				
				
	/* 			//Hard alerts
				$query_variable_hard_alert = "SELECT COUNT(spr_id_variable) AS NumOfHardAlerts FROM `srv_parapodatki` WHERE spr_id_variable = '$spr_id' AND what = 'hard alert' ";
				if($defaultProfile == 2){ //ce imamo "Ustrezne"	
					$query_variable_hard_alert = $query_variable_hard_alert.''.$lurkers;
				}
				$sql_variable_hard_alert = sisplet_query($query_variable_hard_alert);
				$row_variable_hard_alert = mysqli_fetch_assoc($sql_variable_hard_alert);
				$usability_vars[$counter]['rowHardAlert'] = $row_variable_hard_alert['NumOfHardAlerts'];
				//Hard alerts - konec */
				
				//za ureditev stevila vseh moznih opozoril
				//$query_steviloVsehmoznihOpozoril = "SELECT COUNT(spr_id_variable) AS NumOfHardAlerts FROM `srv_parapodatki` WHERE spr_id_variable = '$spr_id' AND what = 'hard alert' ";
				
				//za ureditev stevila vseh moznih opozoril - konec
				
				
	/* 			echo'
					<script>
						console.log('.$usability_vars[$counter]['steviloVsehMoznihOpozorilHard'].');
					</script>				
				'; */
				
				$counter = $counter + 1;
			}
		}		
		//************************************************************************************************************* po spremenljivkah - konec	
		//******belezenje stevila moznih oziroma narejenih opozoril iz strani urejevalca ankete
		
			
		//******belezenje stevila moznih oziroma narejenih opozoril iz strani urejevalca ankete - konec	 
		 
		return $usability_vars;
	}
	
	// Ali imamo zgenerirano datoteko ali ne
	public function hasDataFile(){
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE 
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED)
			return false;
		else
			return true;
	}
	
	public function setStatusFilter($status=''){
		
		$this->_CURRENT_STATUS_FILTER = $status;
	}
	
	function ajax(){
	
		$action = $_GET['a'];
		
		switch ($action){
			case 'changeSetting' :
				$this->ajax_change_settings();
				break;
		}
	}
	private function ajax_change_settings(){

		$value = $_POST['value'];
		$what = $_POST['what'];
				
		if($value == 'true')
			$value = true;
		elseif($value == 'false')
			$value = false;
				
		$this->sessionData['reminder_tracking'][$what] = $value;
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	private function steviloMoznihOpozoril($vars, $what){
		$steviloMoznihOpozoril = 0;
		foreach($vars as $key => $var){
			$steviloMoznihOpozoril = $steviloMoznihOpozoril + $var[$what];	
		}
		return $steviloMoznihOpozoril;		
	}
	
	//skrbi za racunanje kakovosti
	private function izracunKakovosti($steviloRespondentov, $sprozenaOpozorila, $steviloVsehMoznihOpozoril){

		$kakovost = 1 - ($sprozenaOpozorila/$steviloVsehMoznihOpozoril)/$steviloRespondentov;
		
		return $kakovost;		
	}
	
}