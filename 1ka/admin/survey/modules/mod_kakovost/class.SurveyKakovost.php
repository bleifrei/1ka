<?php

define("TEMP_FOLDER", "admin/survey/modules/mod_kakovost/temp");
define("SCRIPT_FOLDER", "admin/survey/modules/mod_kakovost/R");
define("RESULTS_FOLDER", "admin/survey/modules/mod_kakovost/results");

class SurveyKakovost{

	var $anketa;				# id ankete
	var $db_table = '';	
	
	private $displayEditIconsSettings = false;			# ali prikazujemo okno s checkboxi za nastavitve tabele s podatki	
	
	private $cols_with_value = array();					# kateri stolpci imajo vrednosti
	private $show_with_zero = false;					# Ali prikazujemo stolpce z vrednostmi 0
	private $show_details = false;						# Ali prikazujemo stolpce s podrobnimi vrednostmi (-1, -2...)
	private $show_calculations = false;					# Ali prikazujemo stolpce s podrobnimi izracuni (UML, UNL...)
	private $show_with_other = true;					# Ali prikazujemo vrstice "Drugo"
	private $show_with_text = true;						# Ali prikazujemo vrstice tipa "besedilo"
	
	public $bottom_usable_limit = 50;					# Spodnja meja za usable respondente (def. 50%)
	public $top_usable_limit = 80;						# Zgornja meja za usable respondente (def. 80%) - unusable (50-), partially usable (50-80), usable(80+)
	
	public $_HEADERS = array();							# shranimo podatke vseh variabel
	private $headFileName = null;						# pot do header fajla
	private $dataFileName = null;						# pot do data fajla
	private $dataFileStatus = null;						# status data datoteke
	private $SDF = null;								# class za inkrementalno dodajanje fajlov

	public $variablesList = null; 					 	# Seznam vseh variabel nad katerimi lahko izvajamo (zakeširamo)

	public $_CURRENT_STATUS_FILTER = ''; 				# filter po statusih, privzeto izvažamo 6 in 5
	public $_PROFILE_ID_VARIABLE = ''; 					# filter po statusih, privzeto izvažamo 6 in 5

	public $_HAS_TEST_DATA = false;						# ali anketa vsebuje testne podatke
	
	private $usability = array();						# array z vsemi podatki
	
	private $sortField = 'recnum';						# Polje po katerem sortiramo tabelo
	private $sortType = 0;								# Nacin sortiranja (narascajoce/padajoce)

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo, smo v status->ul evealvacija
		if ((int)$anketa > 0){

			$this->anketa = $anketa;

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}
			
			SurveyAnalysisHelper::getInstance()->Init($this->anketa);

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

			if ( $this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_SRV_DELETED) {
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
	}
	
	
	// Prikažemo stran
	public function displayKakovost(){
		global $lang;

		// Prikaz nastavitev
		$this->displayKakovostSettings();
		
		// Izvedemo pripravo datoteke
		$this->prepareData();
		
		// Napolnimo podatke v array
		$this->fillData();
		
		// Izrisemo tabelo
		$this->displayKakovostTable();
		
		// Na koncu pobrisemo zacasne datoteke
		//$this->deleteTemp();
	}
	
	// Prikazemo tabelo
	private function displayKakovostTable(){
		global $site_path;
		global $lang;
		global $admin_type;
		
		echo '<div id="usable_table">';
		
		echo '<table id="tbl_usable_respondents">';
		
		
		// NASLOVNE VRSTICE
		if($this->sortType == 1){
			$sortType = 0;
			$arrow = ' <span class="faicon sort_ascending"></span>';
		}
		else{
			$sortType = 1;
			$arrow = ' <span class="faicon sort_descending"></span>';
		}
		
		if($admin_type == '0' || $admin_type == '1')
			echo '<th class="all" rowspan="2">User ID</th>';
		
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
				

		// VRSTICE S PODATKI
		foreach($this->usability['data'] as $user){		
						
			// Prva vrstica z vrednostmi
			echo '<tr class="'.$user['css'].'">';

			if($admin_type == '0' || $admin_type == '1'){
				
				$sql = sisplet_query("SELECT id	FROM srv_user WHERE ank_id='".$this->anketa."' AND recnum='".$user['recnum']."'");
				$row = mysqli_fetch_array($sql);
				
				echo '<td rowspan="2" class="all">'.$row['id'].'</td>';
			}
				
			
			echo '<td rowspan="2" class="recnum">'.$user['recnum'].'</td>';
			
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
				echo '<td class="unusable">'.$user['-1'].'</td>';
				echo '<td class="unusable">'.$user['-2'].'</td>';
				echo '<td class="unusable">'.$user['-3'].'</td>';
				echo '<td class="unusable">'.$user['-4'].'</td>';
				echo '<td class="unusable">'.$user['-5'].'</td>';
				echo '<td class="unusable">'.$user['-97'].'</td>';
				echo '<td class="unusable">'.$user['-98'].'</td>';
				echo '<td class="unusable">'.$user['-99'].'</td>';
			}
			
			// ali prikazemo podrobne izracune
			if ($this->show_calculations == true) {				
				echo '<td class="calculation" rowspan="2">'.$user['UNL'].'</td>';
				echo '<td class="calculation" rowspan="2">'.$user['UML'].'</td>';
				echo '<td class="calculation" rowspan="2">'.$user['UCL'].'</td>';
				echo '<td class="calculation" rowspan="2">'.$user['UIL'].'</td>';
				echo '<td class="calculation" rowspan="2">'.$user['UAQ'].'</td>';			
			}
			
			echo '</tr>';
			
			
			// Druga vrstica s procenti
			echo '<tr class="multiVariablesHeader '.$user['css'].' '.$css_usable.'">';

			// Ustrezni
			echo '<td class="data">'.$user['validPercent'].'</td>';
				
			// Non-substantive			
			echo '<td class="data">'.$user['nonsubstantivePercent'].'</td>';
			
			// Non-response
			echo '<td class="data">'.$user['nonresponsePercent'].'</td>';
						
			// Skupaj	
			echo '<td class="data sum bold">100%</td>';
	
			// Breakoffs	
			echo '<td class="data breakoff">'.$user['breakoffPercent'].'</td>';
	
			// Uporabni		
			echo '<td class="usable">'.$user['usablePercent'].'</td>';
			
			// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
			if ($this->show_details == true) {
				echo '<td class="unusable">'.$user['-1_percent'].'</td>';
				echo '<td class="unusable">'.$user['-2_percent'].'</td>';
				echo '<td class="unusable">'.$user['-3_percent'].'</td>';
				echo '<td class="unusable">'.$user['-4_percent'].'</td>';
				echo '<td class="unusable">'.$user['-5_percent'].'</td>';
				echo '<td class="unusable">'.$user['-97_percent'].'</td>';
				echo '<td class="unusable">'.$user['-98_percent'].'</td>';
				echo '<td class="unusable">'.$user['-99_percent'].'</td>';
			}

			echo '</tr>';
		}
				
			
		echo '</table>';
		
		if($this->usability['all'] > 0){
            echo '<div class="usable_sum">';
            
            //echo '<span class="bold">'.$lang['srv_usableResp_usability'].': </span>';
            echo '<span class="usable_legend spaceLeft spaceRight" style="background-color:#ffffff;">'.$lang['srv_usableResp_usable_unit'].' - Status 2 ('.$this->top_usable_limit.'%-100%): <span class="bold">'.$this->usability['usable'].' ('.common::formatNumber($this->usability['usable']/$this->usability['all']*100, 0, null, '%').')</span></span>';
            echo '<span class="usable_legend spaceLeft spaceRight" style="background-color:#ffffe3;">'.$lang['srv_usableResp_partusable_unit'].' - Status 1 ('.$this->bottom_usable_limit.'%-'.$this->top_usable_limit.'%): <span class="bold">'.$this->usability['partusable'].' ('.common::formatNumber($this->usability['partusable']/$this->usability['all']*100, 0, null, '%').')</span></span>';
            echo '<span class="usable_legend spaceLeft" style="background-color:#ffe8e8;">'.$lang['srv_usableResp_unusable_unit'].' - Status 0 (0%-'.$this->bottom_usable_limit.'%): <span class="bold">'.$this->usability['unusable'].' ('.common::formatNumber($this->usability['unusable']/$this->usability['all']*100, 0, null, '%').')</span></span>';
            
            echo '</div>';
        }
		
		echo '</div>';
	}	
	
	private function displayKakovostSettings(){
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
		/*echo '<label class="spaceLeft spaceRight">';
		echo '<input type="checkbox" id="show_with_zero" onclick="changeUsableRespSetting(this);" '.($this->show_with_zero == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_usableResp_showZero'];
		echo '</label>';*/
		
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
	
	
	// Zgeneriramo pdf analizo
	private function prepareData(){
		global $site_path;
		global $lang;	
		global $admin_type;

		// Zgeneriramo zacasne csv datoteke
		$this->prepareDataCSV();
		$this->prepareQuestionCSV();
		$this->prepareItemCSV();

		// Poklicemo R skripto in zgeneriramo pdf
		$script = $site_path . SCRIPT_FOLDER . '/kakovost.R';
		$out = exec('Rscript '.$script.' '.$this->anketa.' 2>&1', $output, $return_var);
		
		// Testiranje - izpis errorjev
		if($admin_type == 0){
			echo '<div style="display:none;">';
			echo 'Rscript '.$script;
			//echo '<br />'.$out.'<br />';
			var_dump($output);
			echo '</div>';
		}
	}	
	
	// Napolnimo podatke v array
	private function fillData(){
		global $site_path;
		global $lang;
		
		$result_folder = $site_path . RESULTS_FOLDER.'/';
		
		if (($handle = fopen($result_folder."usability_".$this->anketa.".csv", "r")) !== FALSE) {		
			
			// Loop po vrsticah
			$cnt = 0;
			while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
		
				if($cnt == 0)
					$row = fgetcsv($handle, 1000, ';');
				
				// Preberemo se drugo vrstico, ker so v parih
				$row2 = fgetcsv($handle, 1000, ';');
		
		
				// Obarvamo vrstico glede na status (belo, rumeno, rdece)
				if($row2[7] < (int)$this->bottom_usable_limit){
					$css_usable = 'unusable';
					$status = 0;
					$this->usability['unusable']++;
				}
				elseif($row2[7] >= (int)$this->bottom_usable_limit && $row2[7] < (int)$this->top_usable_limit){
					$css_usable = 'partusable';
					$status = 1;
					$this->usability['partusable']++;
				}
				else{
					$css_usable = 'usable';
					$status = 2;
					$this->usability['usable']++;
				}
				$this->usability['all']++;
				
		
				// Nastavimo izracunane podatke za respondenta
				$this->usability['data'][$cnt]['recnum'] = $row[0];
				//$this->usability['data'][$cnt]['usr_id'] = $row['usr_id'];
				$this->usability['data'][$cnt]['css'] = $css_usable;
				$this->usability['data'][$cnt]['status'] = $status;
				
				$this->usability['data'][$cnt]['all'] = $row[1];
				
				$this->usability['data'][$cnt]['valid'] = $row[2];
				$this->usability['data'][$cnt]['nonsubstantive'] = $row[3];
				$this->usability['data'][$cnt]['nonresponse'] = $row[4];

				$this->usability['data'][$cnt]['validPercent'] = $row2[2];		
				$this->usability['data'][$cnt]['nonsubstantivePercent'] = $row2[3];
				$this->usability['data'][$cnt]['nonresponsePercent'] = $row2[4];
				
				$this->usability['data'][$cnt]['breakoff'] = $row[6];
				$this->usability['data'][$cnt]['breakoffPercent'] = $row2[6];
				
				$this->usability['data'][$cnt]['usable'] = $row[7];
				$this->usability['data'][$cnt]['usablePercent'] = $row2[7];
				
				$this->usability['data'][$cnt]['UNL'] = $row2[17];
				$this->usability['data'][$cnt]['UML'] = $row2[18];
				$this->usability['data'][$cnt]['UCL'] = $row2[19];
				$this->usability['data'][$cnt]['UIL'] = $row2[20];
				$this->usability['data'][$cnt]['UAQ'] = $row2[21];
				
				$this->usability['data'][$cnt]['-1'] = $row[9];
				$this->usability['data'][$cnt]['-1_percent'] = $row2[9];
				$this->usability['data'][$cnt]['-2'] = $row[10];
				$this->usability['data'][$cnt]['-2_percent'] = $row2[10];
				$this->usability['data'][$cnt]['-3'] = $row[11];
				$this->usability['data'][$cnt]['-3_percent'] = $row2[11];
				$this->usability['data'][$cnt]['-4'] = $row[12];
				$this->usability['data'][$cnt]['-4_percent'] = $row2[12];
				$this->usability['data'][$cnt]['-5'] = $row[13];
				$this->usability['data'][$cnt]['-5_percent'] = $row2[13];
				$this->usability['data'][$cnt]['-97'] = $row[14];
				$this->usability['data'][$cnt]['-97_percent'] = $row2[14];
				$this->usability['data'][$cnt]['-98'] = $row[15];
				$this->usability['data'][$cnt]['-98_percent'] = $row2[15];
				$this->usability['data'][$cnt]['-99'] = $row[16];
				$this->usability['data'][$cnt]['-99_percent'] = $row2[16];

				$cnt++;
			}
		}
		
		// Sortiramo podatke
		foreach ($this->usability['data'] as $key => $row) {
			$mid[$key]  = $row[$this->sortField];
		}
		if($this->sortType == 0)
			array_multisort($mid, SORT_ASC, $this->usability['data']);
		else
			array_multisort($mid, SORT_DESC, $this->usability['data']);
		
		
		# ali odstranimo stolpce kateri imajo same 0
		/*if ($this->show_with_zero == false) {
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
		}*/
	}
	
	
	// Pripravi csv s podatki
	private function prepareDataCSV(){
		global $site_path;
		global $lang;	
		global $admin_type;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
	
		$SDF = SurveyDataFile::get_instance();
		$SDF->init($this->anketa);
		$_headFileName = $SDF->getHeaderFileName();
		$_dataFileName = $SDF->getDataFileName();
		$_fileStatus = $SDF->getStatus();
		
		if ($_headFileName != null && $_headFileName != '') {
			$_HEADERS = unserialize(file_get_contents($_headFileName));
		} 
		else {
			echo 'Error! Empty file name!';
		}
	
		// Zaenkrat dopuscamo samo status 6 in brez lurkerjev
		if($admin_type == '0')
			$status_filter = '('.STATUS_FIELD.' ~ /6|5/)&&('.LURKER_FIELD.'==0)';
		else
			$status_filter = '('.STATUS_FIELD.'==6)&&('.LURKER_FIELD.'==0)';
		
		//$start_sequence = $_HEADERS['_settings']['dataSequence'];
		$start_sequence = 2;
		$end_sequence = $_HEADERS['_settings']['metaSequence'] + $_HEADERS['meta']['cnt_all'];
		
		$field_delimit = ';';
			
		// Filtriramo podatke po statusu in jih zapisemo v temp folder
		if (IS_WINDOWS) {
			//$command = 'awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$status_filter.' { print $0}" '.$_dataFileName.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat';
			$out = shell_exec('awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$status_filter.'" '.$_dataFileName.' | cut -d "|" -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat');
		} 
		else {
			//$command = 'awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.' { print $0; }\' '.$_dataFileName.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat';
			$out = shell_exec('awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.'\' '.$_dataFileName.' | cut -d \'|\' -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat');
		}
		
		
		// Ustvarimo koncni CSV
		if ($fd = fopen($temp_folder.'/temp_data_'.$this->anketa.'.dat', "r")) {
		
			$fd2 = fopen($temp_folder.'/data_'.$this->anketa.'.csv', "w");
			
			# naredimo header row
			foreach ($_HEADERS AS $spid => $spremenljivka) {
				if (isset($spremenljivka['grids']) && count($spremenljivka['grids']) > 0) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						foreach ($grid['variables'] AS $vid => $variable ){
							if (!($variable['variable'] == 'uid' && $variable['naslov'] == 'User ID')){
								$output1 .= strip_tags($variable['variable']).$field_delimit;
								//$output2 .= '"'.strip_tags($variable['naslov']).'"'.$field_delimit;
							}
						}
					}
				}
			}
			
			// Pobrisemo zadnji ; ce obstaja
			$output1 = rtrim($output1, ";");
			
			// Zapisemo header row
			fwrite($fd2, $output1."\r\n");
			//fwrite($fd2, $output2."\r\n");


			while ($line = fgets($fd)) {
															
				//fwrite($fd2, '="');
				//$line = str_replace(array("\r","\n","|"), array("","",'";="'), $line);
				$line = '"' . str_replace(array("\r","\n","\"","|"), array("","","",'";"'), $line) . '"';
				
				// Spremenimo encoding v windows-1250
				$line = iconv("UTF-8","Windows-1250//TRANSLIT", $line);
				//$line = str_replace(array("č","š","ž","Č","Š","Ž"), array("\v{c}","\v{s}","\v{z}","\v{C}","\v{S}","\v{Z}"), $line);

				fwrite($fd2, $line);
				//fwrite($fd2, '"');
				fwrite($fd2, "\r\n");
			}
			
			fclose($fd2);
		}
		fclose($fd);

		
		// Na koncu pobrisemo temp datoteke
		if (file_exists($temp_folder.'/temp_data_'.$this->anketa.'.dat')) {
			unlink($temp_folder.'/temp_data_'.$this->anketa.'.dat');
		}
	}	
	
	// Pripravi csv z vprasanji
	private function prepareQuestionCSV(){
		global $site_path;
		global $lang;	
		global $admin_type;
		
		define('delimiter', ';');

		$temp_folder = $site_path . TEMP_FOLDER.'/';
	
		$fd = fopen($temp_folder.'/questions_'.$this->anketa.'.csv', "w");
		

		// Prva vrstica
		$output = 'ID SURVEY'.delimiter;
		$output .= 'ID QUESTION'.delimiter;
		$output .= 'ID PAGE'.delimiter;
		$output .= 'QUESTION NUMBER'.delimiter;
		
		$output .= 'variable'.delimiter;
		$output .= 'tip'.delimiter;
		$output .= 'vrstni_red'.delimiter;
		$output .= 'size'.delimiter;
		$output .= 'visible'.delimiter;
		$output .= 'params'.delimiter;

		$output .= 'char_count'.delimiter;
		
		fwrite($fd, $output."\r\n");
		
			
		// Vrstice s podatki
		$sql = sisplet_query("SELECT s.id, s.gru_id, s.variable, s.tip, s.vrstni_red, s.size, s.visible, s.params, s.naslov
								FROM srv_spremenljivka s, srv_grupa g 
								WHERE s.gru_id=g.id AND g.ank_id='".$this->anketa."' 
								ORDER BY g.vrstni_red, s.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			
			$i = 0;
			
			while ($row = mysqli_fetch_array($sql)) {
				
				$i++;
				
				$line = '';
				
				$line .= $this->anketa.delimiter;
				$line .= $row['id'].delimiter;
				$line .= $row['gru_id'].delimiter;
				$line .= $i.delimiter;
				
				$line .= $row['variable'].delimiter;
				$line .= $row['tip'].delimiter;
				$line .= $row['vrstni_red'].delimiter;
				$line .= $row['size'].delimiter;
				$line .= $row['visible'].delimiter;

				$line .= str_replace("\n", '', str_replace(delimiter, '', $row['params']) ).delimiter;

                $naslov_clean = iconv("UTF-8","Windows-1250//TRANSLIT", $row['naslov']);
                $naslov_clean = trim(strip_tags($naslov_clean));
                $line .= strlen($naslov_clean).delimiter;
	
				fwrite($fd, $line."\r\n");
			}
		}
		

		fclose($fd);
	}
	
	// Pripravi csv z itemi
	private function prepareItemCSV(){
		global $site_path;
		global $lang;	
		global $admin_type;
		
		define('delimiter', ';');

		$temp_folder = $site_path . TEMP_FOLDER.'/';
	
		$fd = fopen($temp_folder.'/items_'.$this->anketa.'.csv', "w");
	

		// Prva vrstica
		$output = '';
		$output .= 'ID SURVEY'.delimiter;
		$output .= 'ID QUESTION'.delimiter;
		$output .= 'ID ITEM'.delimiter;

        $output .= 'variable'.delimiter;
		$output .= 'variable_custom'.delimiter;
		$output .= 'vrstni_red'.delimiter;

		$output .= 'char_count'.delimiter;

		fwrite($fd, $output."\r\n");

		// Vrstice s podatki
		$sql = sisplet_query("SELECT v.id, v.spr_id, v.variable, v.variable_custom, v.vrstni_red, v.naslov
								FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g 
								WHERE v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".$this->anketa."' 
								ORDER BY g.vrstni_red, s.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
						
			while ($row = mysqli_fetch_array($sql)) {
				
				$line = '';
				
				$line .= $this->anketa.delimiter;
				$line .= $row['spr_id'].delimiter;
				$line .= $row['id'].delimiter;

				$line .= str_replace("\n", '', str_replace(delimiter, '', $row['variable']) ).delimiter;
				$line .= $row['variable_custom'].delimiter;
				$line .= $row['vrstni_red'].delimiter;

                $naslov_clean = iconv("UTF-8","Windows-1250//TRANSLIT", $row['naslov']);
                $naslov_clean = trim(strip_tags($naslov_clean));
                $line .= strlen($naslov_clean).delimiter;
				
				fwrite($fd, $line."\r\n");
			}
		}
		

		fclose($fd);
	}
	
	
	// Pobrisemo zacasne datoteke
	private function deleteTemp(){
		global $site_path;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
		$result_folder = $site_path . RESULTS_FOLDER.'/';
		
		// Pobrisemo zacasno CSV datoteko s podatki
		if (file_exists($temp_folder.'/data_'.$this->anketa.'.csv')) {
			unlink($temp_folder.'/data_'.$this->anketa.'.csv');
		}
		
		// Pobrisemo zacasno CSV datoteko z vprasanji
		if (file_exists($temp_folder.'/questions_'.$this->anketa.'.csv')) {
			unlink($temp_folder.'/questions_'.$this->anketa.'.csv');
		}
		
		// Pobrisemo zacasno CSV datoteko z itemi
		if (file_exists($temp_folder.'/items_'.$this->anketa.'.csv')) {
			unlink($temp_folder.'/items_'.$this->anketa.'.csv');
		}
		
		// Pobrisemo CSV datoteko z rezultati
		if (file_exists($result_folder.'/usability_'.$this->anketa.'.csv')) {
			unlink($result_folder.'/usability_'.$this->anketa.'.csv');
		}
	}


	private function parentIf($anketa, $element) {
		$sql = sisplet_query("SELECT tip FROM srv_if WHERE id = '$element'");
		$row = mysqli_fetch_array($sql);
		
		if ($row['tip'] == 0) return $element;
		
		$sql1 = sisplet_query("SELECT parent FROM srv_branching WHERE ank_id='$anketa' AND element_if = '$element'");
		$row1 = mysqli_fetch_array($sql1);
		
		return parentIf($anketa, $row1['parent']);	
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
	
	// Ali imamo zgenerirano datoteko ali ne
	private function hasDataFile(){
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE 
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED)
			return false;
		else
			return true;
	}
	
	private function setStatusFilter($status=''){
		
		$this->_CURRENT_STATUS_FILTER = $status;
	}
	
	
	
}