<?php

class SurveyUsableResp{

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
	
	private $sortField = 'recnum';						# Polje po katerem sortiramo tabelo
	private $sortType = 0;								# Nacin sortiranja (narascajoce/padajoce)
	
	
	function __construct($anketa, $generateDataFile=true){
		global $lang;
		global $global_user_id;

		if ((int)$anketa > 0){
		
			$this->anketa = $anketa;

			SurveyAnalysisHelper::getInstance()->Init($this->anketa);

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
            
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

			if (file_exists($this->headFileName) && $this->headFileName !== null && $this->headFileName != ''){
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
	

	
	function displayUsableRespTable(){
		global $lang;
				
		// Prikaz nastavitev
		$this->displayUsableSettings();
		
		# ali imamo testne podatke
		if ($this->_HAS_TEST_DATA){
            # izrišemo bar za testne podatke
            $SSH = new SurveyStaticHtml($this->anketa);
			$SSH -> displayTestDataBar(true);
		}
		
		// Izracunamo vse podatke
		$usability = $this->calculateData();
		$userData = $usability['data'];
		
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
		
					
		echo '<div id="usable_table"><table id="tbl_usable_respondents" '.($this->show_details==true && $this->show_calculations==true ? '' : ' style="width:100%"').'>';

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
		
		echo '<th class="recnum" rowspan="2" style="width:60px;"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=recnum&sortType='.$sortType.'">Recnum'./*$lang['recnum'].*/($this->sortField=='recnum' ? $arrow : '').'</a></th>';
		echo '<th class="all" rowspan="2">'.$lang['srv_usableResp_qcount'].'</th>';
		
		echo '<th class="data" colspan=4>'.$lang['srv_usableResp_exposed'].'</th>';
		
		echo '<th class="data" rowspan="2"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=breakoff&sortType='.$sortType.'">'.$lang['srv_usableResp_breakoff'].($this->sortField=='breakoff' ? $arrow : '').'</th>';
		
		echo '<th class="usable" colspan="2">'.$lang['srv_usableResp_usability'].'</th>';
		
		// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
		if ($this->show_details == true) {
			foreach ($this->_missings AS $value => $text){
				$cnt_miss++;
				echo "<th rowspan=\"2\" class=\"unusable\" title=\"".$lang['srv_usableResp_'.$text]."\" >{$value}<br/>(".$lang['srv_usableResp_'.$text].")</th>";
			}
			foreach ($this->_unsets AS $value => $text){
				$cnt_undefined++;
				echo "<th rowspan=\"2\" class=\"unusable\" title=\"".$lang['srv_usableResp_'.$text]."\">{$value}<br/>(".$lang['srv_usableResp_'.$text].")</th>";
			}
		}
		
		// ali prikazemo podrobne izracune
		if ($this->show_calculations == true) {
			echo '<th class="calculation" rowspan="2">UNL</th>';
			echo '<th class="calculation" rowspan="2">UML</th>';
			echo '<th class="calculation" rowspan="2">UCL</th>';
			echo '<th class="calculation" rowspan="2">UIL</th>';
			echo '<th class="calculation" rowspan="2">UAQ</th>';			
		}
		
		echo '</tr>';
		
		echo '<tr>';
		echo '<th class="data"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=valid&sortType='.$sortType.'">'.$lang['srv_anl_valid'].($this->sortField=='valid' ? $arrow : '').'</th>';
		echo '<th class="data"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=nonsubstantive&sortType='.$sortType.'">'.$lang['srv_usableResp_nonsubstantive'].($this->sortField=='nonsubstantive' ? $arrow : '').'</th>';
		echo '<th class="data"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=nonresponse&sortType='.$sortType.'">'.$lang['srv_usableResp_nonresponse'].($this->sortField=='nonresponse' ? $arrow : '').'</th>';
		echo '<th class="data"><span class="bold">'.$lang['srv_anl_suma1'].'</span></th>';
		
		echo '<th class="usable"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=usable&sortType='.$sortType.'">%'.($this->sortField=='usable' ? $arrow : '').'</a></th>';
		echo '<th class="usable status"><a href="index.php?anketa='.$this->anketa.'&a=usable_resp&sortField=status&sortType='.$sortType.'">Status'.($this->sortField=='status' ? $arrow : '').'</a></th>';
		echo '</tr>';
		

		// Izpis podatkov vsakega respondenta
		foreach($userData as $key => $user){
			
			// Obarvamo vrstico glede na status (belo, rumeno, rdece)
			if($user['status'] == 0)
				$css_usable = 'unusable';
			elseif($user['status'] == 1)
				$css_usable = 'partusable';
			else
				$css_usable = 'usable';
				
						
			// Prva vrstica z vrednostmi
			echo '<tr class="'.$data['css'].' '.$css_usable.'">';

			echo '<td rowspan="2" class="recnum"><a href="index.php?anketa='.$this->anketa.'&a=data&m=quick_edit&usr_id='.$user['usr_id'].'&quick_view=1">'.$user['recnum'].'</a></td>';
			
			// Vsi
			echo '<td rowspan="2" class="all">'.$user['all'].'</td>';
			
			// Ustrezni
			echo '<td class="data">'.$user['valid'].'</td>';
			
			// Non-substantive			
			echo '<td class="data">'.$user['nonsubstantive'].'</td>';
			
			// Non-response
			echo '<td class="data">'.$user['nonresponse'].'</td>';
			
			// Skupaj	
			echo '<td class="data sum bold">'.($user['valid']+$user['nonsubstantive']+$user['nonresponse']+$user['breakoff']).'</td>';
			
			// Breakoffs	
			echo '<td class="data breakoff">'.$user['breakoff'].'</td>';
			
			// Uporabni		
			echo '<td class="usable">'.$user['usable'].'</td>';
			echo '<td class="usable status" rowspan="2">'.$user['status'].'</td>';
			
			// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
			if ($this->show_details == true) {
				foreach ($this->_missings AS $value => $text){
					echo '<td class="unusable">'.$user[$value].'</td>';
				}
				foreach ($this->_unsets AS $value => $text){
					echo '<td class="unusable">'.$user[$value].'</td>';
				}
			}
			
			// ali prikazemo podrobne izracune
			if ($this->show_calculations == true) {				
				echo '<td class="calculation" rowspan="2">'.common::formatNumber($user['UNL']*100, 0, null, '%').'</td>';
				echo '<td class="calculation" rowspan="2">'.common::formatNumber($user['UML']*100, 0, null, '%').'</td>';
				echo '<td class="calculation" rowspan="2">'.common::formatNumber($user['UCL']*100, 0, null, '%').'</td>';
				echo '<td class="calculation" rowspan="2">'.common::formatNumber($user['UIL']*100, 0, null, '%').'</td>';
				echo '<td class="calculation" rowspan="2">'.common::formatNumber($user['UAQ']*100, 0, null, '%').'</td>';			
			}
			
			echo '</tr>';
			
			
			// Druga vrstica s procenti
			echo '<tr class="multiVariablesHeader '.$user['css'].' '.$css_usable.'">';

			// Ustrezni
			echo '<td class="data">'.common::formatNumber($user['validPercent'], 0, null, '%').'</td>';
				
			// Non-substantive			
			echo '<td class="data">'.common::formatNumber($user['nonsubstantivePercent'], 0, null, '%').'</td>';
			
			// Non-response
			echo '<td class="data">'.common::formatNumber($user['nonresponsePercent'], 0, null, '%').'</td>';
						
			// Skupaj	
			//echo '<td class="data bold">'.common::formatNumber(($user['validPercent']+$user['nonsubstantivePercent']+$user['nonresponsePercent']+$user['breakoffPercent']), 0, null, '%').'</td>';
			echo '<td class="data sum bold">'.common::formatNumber(100, 0, null, '%').'</td>';
	
			// Breakoffs	
			echo '<td class="data breakoff">'.common::formatNumber($user['breakoffPercent'], 0, null, '%').'</td>';
	
			// Uporabni		
			echo '<td class="usable">'.common::formatNumber($user['usablePercent'], 0, null, '%').'</td>';
			
			// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
			if ($this->show_details == true) {
				foreach ($this->_missings AS $value => $text){
					$val = $user[$value];
					$val = ($all > 0) ? ($val / $all * 100) : 0;
					echo '<td class="unusable">'.common::formatNumber($val, 0, null, '%').'</td>';
				}
				foreach ($this->_unsets AS $value => $text){
					$val = $user[$value];
					$val = ($all > 0) ? ($val / $all * 100) : 0;
					echo '<td class="unusable">'.common::formatNumber($val, 0, null, '%').'</td>';
				}
			}

			echo '</tr>';
		}
			
		echo '</table>';
		
		
		/*echo '<div class="usable_sum">';
		echo '<div class="usable_legend" style="background-color:#ffffff;"></div> '.$lang['srv_usableResp_usable'].': <span class="bold">'.$usability['usable'].'</span>';
		echo '<div class="usable_legend" style="background-color:#ffffe3;"></div> '.$lang['srv_usableResp_partusable'].': <span class="bold">'.$usability['partusable'].'</span> ';
		echo '<div class="usable_legend" style="background-color:#ffe8e8;"></div> '.$lang['srv_usableResp_unusable'].': <span class="bold">'.$usability['unusable'].'</span>';
		echo '</div>';*/	
		echo '<div class="usable_sum">';
		//echo '<span class="bold">'.$lang['srv_usableResp_usability'].': </span>';
		if($usability['all'] > 0){
			echo '<span class="usable_legend spaceLeft spaceRight" style="background-color:#ffffff;">'.$lang['srv_usableResp_usable_unit'].' - Status 2 ('.$this->top_usable_limit.'%-100%): <span class="bold">'.$usability['usable'].' ('.common::formatNumber($usability['usable']/$usability['all']*100, 0, null, '%').')</span></span>';
			echo '<span class="usable_legend spaceLeft spaceRight" style="background-color:#ffffe3;">'.$lang['srv_usableResp_partusable_unit'].' - Status 1 ('.$this->bottom_usable_limit.'%-'.$this->top_usable_limit.'%): <span class="bold">'.$usability['partusable'].' ('.common::formatNumber($usability['partusable']/$usability['all']*100, 0, null, '%').')</span></span>';
			echo '<span class="usable_legend spaceLeft" style="background-color:#ffe8e8;">'.$lang['srv_usableResp_unusable_unit'].' - Status 0 (0%-'.$this->bottom_usable_limit.'%): <span class="bold">'.$usability['unusable'].' ('.common::formatNumber($usability['unusable']/$usability['all']*100, 0, null, '%').')</span></span>';
		}
		else{
			echo '<span class="usable_legend spaceLeft spaceRight" style="background-color:#ffffff;">'.$lang['srv_usableResp_usable_unit'].' - Status 2 ('.$this->top_usable_limit.'%-100%): <span class="bold">'.$usability['usable'].' ('.common::formatNumber(0, 0, null, '%').')</span></span>';
			echo '<span class="usable_legend spaceLeft spaceRight" style="background-color:#ffffe3;">'.$lang['srv_usableResp_partusable_unit'].' - Status 1 ('.$this->bottom_usable_limit.'%-'.$this->top_usable_limit.'%): <span class="bold">'.$usability['partusable'].' ('.common::formatNumber(0, 0, null, '%').')</span></span>';
			echo '<span class="usable_legend spaceLeft" style="background-color:#ffe8e8;">'.$lang['srv_usableResp_unusable_unit'].' - Status 0 (0%-'.$this->bottom_usable_limit.'%): <span class="bold">'.$usability['unusable'].' ('.common::formatNumber(0, 0, null, '%').')</span></span>';
		}
		echo '</div>';
		
		echo '</div>';
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
		$tmp_file = $folder.'tmp_export_'.$this->anketa.'_usable_resp.php';

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
			$command .= ' | awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} { print \"$frequency[\",$1,\"]\",\"[\x27\",$4,\"\x27]\",\"=\",$5,\";\"} { print \"$frequency[\",$1,\"]\",\"[\"usr_id\"]\",\"=\",$3,\";\"} { print \"$frequency[\",$1,\"]\",\"[\"recnum\"]\",\"=\",$2,\";\"}" >> '.$tmp_file;		
		} 
		else {
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" \' {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} {{for (n in arr) {delete arr[n]}}} {delete arr} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[$i]++}} '.$status_filter.' {{for (n in arr) { print NR,$'.$recnum_sequence.',$1,n,arr[n]}}}\' '.$this->dataFileName;
			$command .= ' | sed \'s*\x27*`*g\' ';
			$command .= ' | awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} { print "$frequency[",$1,"]","[\x27",$4,"\x27]","=\x27",$5,"\x27;"} { print "$frequency[",$1,"]","[\"usr_id\"]","=\x27",$3,"\x27;"} { print "$frequency[",$1,"]","[\"recnum\"]","=\x27",$2,"\x27;"}\' >> '.$tmp_file;
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
				
		$this->sessionData['usable_resp'][$what] = $value;
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
}