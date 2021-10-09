<?php
/**
* @author 	Peter Hrvatin
* @date		May 2012
*
*/


class SurveyCustomReport {
		
	private $ank_id;			// id ankete

	private $classInstance; 	// Instanca razreda za izris tabele (crosstab, mean, ttest)
	
	public $returnAsHtml = false;			// ali vrne rezultat analiz kot html ali ga izpiše
	public $isArchive = false;				// nastavimo na true če smo v arhivu
	public $publicCReport = false;          // ali smo preko public povezave
	
	public $creportProfile = 0;				// trenutno izbrani profil porocila
	public $creportAuthor = 0;				// trenutno izbrani avtor porocila ki se ga ureja

	public $expanded = 0;			// skrcen(0) ali razsirjen(1) nacin
	
	function __construct($anketa) {
		global $global_user_id, $site_path, $lang;

		if ((int)$anketa > 0) {		
			$this->ank_id = $anketa;
			
			// polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->ank_id);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}
			
			// Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->ank_id);
			SurveyUserSetting::getInstance()->Init($this->ank_id, $global_user_id);
				
			SurveyStatusProfiles::Init($this->ank_id);
			SurveyMissingProfiles :: Init($this->ank_id,$global_user_id);
			SurveyConditionProfiles :: Init($this->ank_id, $global_user_id);
			SurveyZankaProfiles :: Init($this->ank_id, $global_user_id);
			SurveyTimeProfiles :: Init($this->ank_id, $global_user_id);

			SurveyDataSettingProfiles :: Init($this->ank_id);

			#inicializiramo class za datoteke
			$SDF = SurveyDataFile::get_instance();
			$SDF->init($anketa);
			$this->headFileName = $SDF->getHeaderFileName();
			$this->dataFileName = $SDF->getDataFileName();
			$this->dataFileStatus = $SDF->getStatus();	

			
			SurveyAnalysis::Init($this->ank_id);
			
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);	
			#preberemo HEADERS iz datoteke
			SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));			
			# odstranimo sistemske variable tipa email, ime, priimek, geslo
			SurveyAnalysis::removeSystemVariables();			
			# polovimo frekvence			
			SurveyAnalysis::getFrequencys();
			
			$this->creportProfile = SurveyUserSetting :: getInstance()->getSettings('default_creport_profile');
			$this->creportProfile = isset($this->creportProfile) && $this->creportProfile != '' ? $this->creportProfile : 0;
			
			$this->creportAuthor = SurveyUserSetting :: getInstance()->getSettings('default_creport_author');
			$this->creportAuthor = isset($this->creportAuthor) && $this->creportAuthor != '' ? $this->creportAuthor : $global_user_id;
			
			$this->expanded = (isset($_GET['expanded'])) ? $_GET['expanded'] : 0;
		} 
		else {
			echo 'Invalid Survey ID!';
			exit();
		}
	}
	
	
	// Izrisemo izdelano porocilo
	function displayReport(){
		global $lang;	
		global $global_user_id;
		
		# zakeširamo vsebino, in jo nato po potrebi zapišpemo v html 
    	if ($this->returnAsHtml != false) {
			ob_start();
		}
				
		if ($this->isArchive == false && $this->publicCReport == false) {			
			
			echo '<input type="hidden" value="'.$this->expanded.'" id="creport_expanded" />';
			
			$this->displayProfilePopups();

			// Prva vrstica - seznam poročil
			echo '<div>';
			echo '<span class="pointer blue bold" style="font-size:14px;" onClick="showCReportProfiles();">'.$lang['srv_custom_report_list'].'</span>';
			echo ' <span id="creport_profile_setting_plus" class="pointer faicon add icon-as_link spaceLeft" title="'.$lang['srv_custom_report_create'].'" style="padding-bottom:1px;"></span>';
			echo '</div>';
			
			// Naslov
			echo '<h2 style="display:inline-block; color:#333;">';
			
			// Profil			
			if($this->creportProfile == 0){

				$what = 'creport_default_profile_name';
				$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->creportAuthor' AND what='$what'");		

				if(mysqli_num_rows($sql) == 0){
					$name = $lang['srv_custom_report_default'];
				}
				else{
					$row = mysqli_fetch_array($sql);		
					$name = $row['value'];
				}
			}
			else{
				$profile = $this->getProfile($this->creportProfile);				
				$name = $profile['name'];
			}
			echo '<span style="font-weight: normal;">';
			echo $lang['srv_custom_report_profile'].': <span class="bold pointer blue" onClick="showCReportProfiles();">"'.$name.'"</span>';
			echo '</span>';

			echo '</h2>';
			
			// Edit in add porocilo
			echo '<div  style="display:inline-block; line-height:10px;">';
			echo ' <span id="creport_profile_setting_edit" class="faicon edit icon-as_link spaceLeft" style="margin-bottom:1px;" title="'.$lang['srv_custom_report_edit'].'"></span>';

			// Osnova porocila (prazna, vsi grafi, vse frekvence...) - prikazemo samo ce je porocilo prazno
			if($this->checkEmpty($this->ank_id)){
				echo '<span style="font-size:14px; font-weight: normal; margin-left: 20px;">';
				echo $lang['srv_custom_report_base'].': ';
				
				echo '<select name="custom_report_base" id="custom_report_base" onChange="addCustomReportAllElementsAlert(this.value);">';
				echo '	<option value="0">'.$lang['srv_custom_report_base_0'].'</option>';
				echo '	<option value="1">'.$lang['srv_sumarnik'].'</option>';
				echo '	<option value="2">'.$lang['srv_frequency'].'</option>';
				echo '	<option value="3">'.$lang['srv_descriptor'].'</option>';
				echo '	<option value="4">'.$lang['srv_analiza_charts'].'</option>';
				echo '</select>';
				echo '</span>';
			}
			echo '</div>';
			
			// Stevilo vseh porocil
			$sqlC = sisplet_query("SELECT id FROM srv_custom_report_profiles WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id'");
			$report_count = mysqli_num_rows($sqlC);
			if($report_count > 0)
				echo '<div style="margin:-12px 0 20px 0; color:#555555; font-size:10px;">'.$lang['srv_custom_report_count'].': '.($report_count + 1).'</div>';
			
			
			// Preklop na skrcen/razsirjen pogled in predogled
			if ($this->isArchive == false && $this->publicCReport == false) {
				
				echo '<div id="custom_report_view">';
								
				if($this->expanded == 0)
					echo '<a href="index.php?anketa='.$this->ank_id.'&a=analysis&m=analysis_creport&expanded=1"><span class="faicon compress"></span> '.$lang['srv_custom_report_expanded_0'].'</a>';
				else
					echo '<a href="index.php?anketa='.$this->ank_id.'&a=analysis&m=analysis_creport&expanded=0"><span class="faicon expand"></span> '.$lang['srv_custom_report_expanded_1'].'</a>';		
				
				// Preview
				echo ' &nbsp;<a title="'.$lang['srv_custom_report_preview'].'" onClick="showCReportPreview(); return false;" href="#"><span class="faicon preview pointer"></span> '.$lang['srv_custom_report_preview_short'].'</a>';
				
				echo '</div>';
			}
			
		}		
		
		echo '<div id="custom_report_elements">';		
		

		
		// ce arhiviramo imamo razsirjene elemente
		$this->expanded = ($this->isArchive == false && $this->publicCReport == false) ? $this->expanded : 1;
		
		// Naslov porocila
		if ($this->isArchive == false && $this->publicCReport == false) {
			$this->displayTitle();
		}
		
		$sql = sisplet_query("SELECT * FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile' ORDER BY vrstni_red ASC");		
		$counter = mysqli_num_rows($sql);
		if($counter > 0){
				
			echo '<ul id="custom_report_sortable">';			
			
			if ($this->isArchive == false && $this->publicCReport == false) {	
				echo '<div class="report_element_separator '.($this->expanded == 1 ? 'expanded' : '').'">';			
				echo '<div class="add_element">';
				$this->addNewElement(-1);
				echo '</div>';
				echo '</div>';
			}
					
			// Loop po vseh dodanih elementih porocila
			while($row = mysqli_fetch_array($sql)){
				echo '<li id="sortable_report_element_'.$row['id'].'">';
				
				// Pagebreak
				if($row['type'] == '-1'){
					if ($this->isArchive == false && $this->publicCReport == false){
						$this->displayBreak($row);
					}
				}
				else{
					echo '<div class="report_element '.($this->expanded == 1 && $this->isArchive == false && $this->publicCReport == false ? ' active':'').'" id="report_element_'.$row['id'].'">';
					$this->displayReportElement($row['id'], $this->expanded);
					echo '</div>';
				}
				
				if ($this->isArchive == false && $this->publicCReport == false) {
					echo '<div class="report_element_separator '.($this->expanded == 1 ? 'expanded' : '').'" id="report_element_separator_'.$row['id'].'">';			
					if($row['vrstni_red'] < $counter){
						echo '<div class="add_element">';
						$this->addNewElement($row['id']);
						echo '</div>';
					}
					echo '</div>';
				}
				
				echo '</li>';
			}
			
			echo '</ul>';
		}
		else
			echo '<br /><br />';
		
		if ($this->isArchive == false && $this->publicCReport == false) {
			// Dodajanje novega porocila		
			echo '<br />';
			echo '<div class="add_element">';
			$this->addNewElement();
			echo '</div>';
		}
		
		echo '</div>';

		if ($this->isArchive == false && $this->publicCReport == false) {
			// izpisi na dnu
			$this->displayBottomSettings();
		}
		
		
		if ($this->returnAsHtml != false) {
			$result = ob_get_clean();
			ob_flush(); flush();
			return $result;
		}
	}
	
	// Izrisemo posamezen element porocila
	function displayReportElement($element_id, $expanded){
		global $lang;
		
		$sql = sisplet_query("SELECT * FROM srv_custom_report WHERE id='$element_id' AND ank_id='$this->ank_id' AND profile='$this->creportProfile'");
		$reportElement = mysqli_fetch_array($sql);

		switch($reportElement['type']){
			
			case '0':				
				$this->displayReportElementHead($reportElement, $expanded, $lang['srv_custom_report_select_type']);
				$this->displayReportElementSettings($reportElement, $expanded);				
				break;
				
			case '1':
				$this->displaySum($reportElement, $expanded);
				break;			
			case '2':
				$this->displayFreq($reportElement, $expanded);
				break;				
			case '3':
				$this->displayDesc($reportElement, $expanded);
				break;
			case '4':
				$this->displayChart($reportElement, $expanded);
				break;				
			case '5':
				$this->displayCrosstab($reportElement, $expanded);	
				break;				
			case '6':
				$this->displayMean($reportElement, $expanded);
				break;	
			case '7':
				$this->displayTTest($reportElement, $expanded);
				break;
			case '9':
				$this->displayRazbitje($reportElement, $expanded);
				break;
			case '10':
				$this->displayMulticrosstab($reportElement, $expanded);
				break;
			
			case '8':
				$this->displayText($reportElement, $expanded);
				break;
				
			default:
				break;
		}
		
		// Komentar pod elementom
		if($reportElement['type'] != 8)
			$this->displayReportElementText($reportElement, $expanded);
	}
	
	// Izpis nastavitev posameznega elementa (tip izpisa, izbira spremenljivk...)
	function displayReportElementSettings($reportElement, $expanded){
		global $lang;

		if ($this->isArchive == false && $this->publicCReport == false) {
			echo '<div class="report_element_settings" '.($expanded == 0 ? ' style="display:none;"' : '').'>';
			
			// Tip izpisa (sums, freq, opisne...)
			echo '<select name="report_element_type_'.$reportElement['id'].'" id="report_element_type_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'type\', this.value)">';
			
			// Ce ni izbrana
			if ( $reportElement['type'] == null || $reportElement['type'] == 0 ) {
				echo '<option value="0" selected="selected" >'. $lang['srv_custom_report_select_type'] . '</option>';
			}
			echo	'<option value="1" '.($reportElement['type'] == 1 ? 'selected="selected"' : '').'>'.$lang['srv_sumarnik'].'</option>';
			echo	'<option value="2" '.($reportElement['type'] == 2 ? 'selected="selected"' : '').'>'.$lang['srv_frequency'].'</option>';
			echo	'<option value="3" '.($reportElement['type'] == 3 ? 'selected="selected"' : '').'>'.$lang['srv_descriptor_short'].'</option>';
			echo	'<option value="4" '.($reportElement['type'] == 4 ? 'selected="selected"' : '').'>'.$lang['srv_chart'].'</option>';
			echo	'<option value="5" '.($reportElement['type'] == 5 ? 'selected="selected"' : '').'>'.$lang['srv_crosstabs'].'</option>';
			echo	'<option value="10" '.($reportElement['type'] == 10 ? 'selected="selected"' : '').'>'.$lang['srv_multicrosstab'].'</option>';
			echo	'<option value="6" '.($reportElement['type'] == 6 ? 'selected="selected"' : '').'>'.$lang['srv_means_label'].'</option>';
			echo	'<option value="7" '.($reportElement['type'] == 7 ? 'selected="selected"' : '').'>'.$lang['srv_ttest'].'</option>';
			echo	'<option value="9" '.($reportElement['type'] == 9 ? 'selected="selected"' : '').'>'.$lang['srv_break'].'</option>';		

			echo '</select>';

			
			// Izbira spremenljivke za enojne elemente
			if($reportElement['type'] > 0 && $reportElement['type'] < 5){
			
				// Izbira spremneljivke
				echo ' <select style="margin-left:20px;" name="report_element_spr_id_'.$reportElement['id'].'" id="report_element_spr_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr1\', this.value)">';		
				
				// Ce ni izbrana
				if ( $reportElement['spr1'] == null || $reportElement['spr1'] == '' ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_select_spr'] . '</option>';
				}
				# preberemo header
				foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
					# preverjamo ali je meta
					if (($spremenljivka['tip'] != 'm'
					 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
					 /*&& in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20) )*/) {
						
						# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore
						$only_valid = 0;
						if (count($spremenljivka['grids']) > 0) {
							foreach ($spremenljivka['grids'] AS $gid => $grid) {
								# dodamo dodatne vrstice z albelami grida
								if (count($grid['variables']) > 0 )
								foreach ($grid['variables'] AS $vid => $variable ){
								$_sequence = $variable['sequence'];	# id kolone z podatki
								$only_valid += (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
							}
							}
						}

						// Ce ja kaksen veljaven oz ce prikazujemo tudi prazne
						if(SurveyDataSettingProfiles :: getSetting('hideEmpty') != 1 || $only_valid > 0){
							$text = (strlen($spremenljivka['naslov']) > 60) ? substr($spremenljivka['naslov'], 0, 57).'...' : $spremenljivka['naslov'];
							$text = '('.$spremenljivka['variable'].') '.$text;
							echo	'<option value="'.$spid.'" '.($reportElement['spr1'] == $spid ? 'selected="selected"' : '').'>'.$text.'</option>';
						}
					}
				}
				echo '</select>';
			}
				
			// Nastavitve za CROSSTABE
			elseif($reportElement['type'] == 5){
				
				$variables = $this->classInstance->getVariableList();

				// Nastavljeni variabli
				$crossData1 = explode("-", $reportElement['spr1']);
				$crossData2 = explode("-", $reportElement['spr2']);
				
				// Izbira spremneljivke 1
				echo ' <select style="margin-left:20px;" name="report_element_spr_id_'.$reportElement['id'].'" id="report_element_spr_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr1\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr1'] == null || $reportElement['spr1'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_izberi_prvo'] . '</option>';
				}
				foreach ($variables as $variable) {
					$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					$value = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
					echo '<option value="'.$value.'" '
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($crossData1[0] == $variable['sequence'] && $crossData1[0] != null) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $text . '</option>';
				}
				
				echo '</select>';
				
				
				// Izbira spremneljivke 2
				echo ' <select name="report_element_spr2_id_'.$reportElement['id'].'" id="report_element_spr2_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr2\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr2'] == null || $reportElement['spr2'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_izberi_drugo'] . '</option>';
				}
				foreach ($variables as $variable) {
					$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					$value = $variable['sequence'].'-'.$variable['spr_id'].'-'.$variable['grd_id'];
					
					echo '<option value="'.$value.'" '
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($crossData2[0] == $variable['sequence'] && $crossData2[0] != null) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $text . '</option>';
				}
				
				echo '</select>';
				
				// Izbira podtipa (tabela / graf)
				echo ' <input style="margin-left:20px;" type="radio" value="0" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 0 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />Tabela ';
				echo '<input type="radio" value="1" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 1 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />Graf';
			}
			
			// Nastavitve za MULTICROSSTABE
			elseif($reportElement['type'] == 10){
				
				$mc_tables = $this->classInstance->getTables();
				
				if(count($mc_tables) == 0){
					echo '<span class="spaceLeft">'.$lang['srv_multicrosstab_exist'].'</span>';
				}
				else{
					// Izbira ze ustvarjene tabele
					echo ' <select style="margin-left:20px;" name="report_element_spr_id_'.$reportElement['id'].'" id="report_element_spr_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr1\', this.value)">';
					
					# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
					if ( $reportElement['spr1'] == null || $reportElement['spr1'] == 0 ) {
						echo '<option value="0" selected="selected" >'. $lang['srv_multicrosstab_select'] . '</option>';				
					}
					foreach($mc_tables as $table){
						echo '<option value="'.$table['id'].'" '.($reportElement['spr1'] == $table['id'] ? ' selected="selected"' : '').'>'.$table['name'].'</option>';	
					}
					
					echo '</select>';
				}
			}
			
			// Nastavitve za MEANS
			elseif($reportElement['type'] == 6){
			
				$variables1 = $this->classInstance->getVariableList(1);
				$variables2 = $this->classInstance->getVariableList(2);

				// Nastavljeni variabli
				$meanData1 = explode("-", $reportElement['spr1']);
				$meanData2 = explode("-", $reportElement['spr2']);
				
				// Izbira spremneljivke 1
				echo ' <select style="margin-left:20px;" name="report_element_spr_id_'.$reportElement['id'].'" id="report_element_spr_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr1\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr1'] == null || $reportElement['spr1'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_means_izberi_prvo'] . '</option>';
				}
				foreach ($variables1 as $variable) {
					$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					$value = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
					echo '<option value="'.$value.'" '
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($meanData1[0] == $variable['sequence'] && $meanData1[0] != null) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $text . '</option>';
				}
				
				echo '</select>';
				
				
				// Izbira spremneljivke 2
				echo ' <select name="report_element_spr2_id_'.$reportElement['id'].'" id="report_element_spr2_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr2\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr2'] == null || $reportElement['spr2'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_means_izberi_drugo'] . '</option>';
				}
				foreach ($variables2 as $variable) {
					$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					$value = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
					echo '<option value="'.$value.'" '
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($meanData2[0] == $variable['sequence'] && $meanData2[0] != null) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $text . '</option>';
				}
				
				echo '</select>';
				
				
				// Izbira podtipa (tabela / graf)
				echo ' <input style="margin-left:20px;" type="radio" value="0" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 0 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />Tabela ';
				echo '<input type="radio" value="1" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 1 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />Graf';
			}
			
			// Nastavitve za TTEST
			elseif($reportElement['type'] == 7){
			
				$numerus = $this->classInstance->getVariableList(1);
				$variables = $this->classInstance->getVariableList(2);
				//$selectedVar = $this->classInstance->getSelectedVariables();
				
				// Nastavljeni variabli
				$ttestData1 = explode("-", $reportElement['spr1']);
				$ttestData2 = explode("-", $reportElement['spr2']);
				
				// Izbira spremneljivke 1
				echo ' <select style="margin-left:20px;" name="report_element_spr_id_'.$reportElement['id'].'" id="report_element_spr_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr1\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr1'] == null || $reportElement['spr1'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_ttest_select1_option'] . '</option>';
				}
				foreach ($variables as $variable) {
					$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					$value = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
					echo '<option value="'.$value.'" '
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($ttestData1[0] == $variable['sequence'] && $ttestData1[0] != null) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $text . '</option>';
				}
				
				echo '</select>';
				
				
				// Izbira spremneljivke 2
				echo ' <select name="report_element_spr2_id_'.$reportElement['id'].'" id="report_element_spr2_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr2\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr2'] == null || $reportElement['spr2'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_ttest_select2_option'] . '</option>';
				}
				foreach ($numerus as $variable) {
					$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					$value = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
					echo '<option value="'.$value.'" '
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($ttestData2[0] == $variable['sequence'] && $ttestData2[0] != null) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $text . '</option>';
				}
				
				echo '</select>';
				
				
				// Izbira podtipa (tabela / graf)
				echo ' <input style="margin-left:20px;" type="radio" value="0" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 0 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />Tabela ';
				echo '<input type="radio" value="1" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 1 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />Graf';
			
				
				// Izbira dveh podvariabel za prvo variablo
				if($reportElement['spr1'] != null && $reportElement['spr1'] != 0)
					$this->displayTTestSubVar($reportElement);
			}
			
			// Nastavitve za BREAK
			elseif($reportElement['type'] == 9){

				// Nastavljeni variabli
				$breakData1 = explode("-", $reportElement['spr1']);
				$breakData2 = explode("-", $reportElement['spr2']);
					
					
				// Izbira spremneljivke 1
				$variables = $this->classInstance->getVariableList(2);

				echo ' <select style="margin-left:20px;" name="report_element_spr_id_'.$reportElement['id'].'" id="report_element_spr_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr1\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr1'] == null || $reportElement['spr1'] == 0){
					echo '<option value="0" selected="selected" >'. $lang['srv_break_select1_option'] . '</option>';
				}
				
				if (count($variables)) {
					foreach ($variables as $variable) {
						$value = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
						echo '<option value="'.$value.'"'
						. ((int)$variable['canChoose'] == 1 ? '' : ' disabled="disabled" ')
						. ($breakData1[0] == $variable['sequence'] && $breakData1[0] != null ? ' selected="selected"':''). '> ';
						
						$text = ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' )) . $variable['variableNaslov'];
						$text = (strlen($text) > 60) ? substr($text, 0, 57).'...' : $text;
						echo $text;
						
						echo '</option>';
					}
				}
				
				echo '</select>';
				
				
				// Izbira spremneljivke 2
				$variables = $this->getBreakDependentVariableList();
				
				echo ' <select name="report_element_spr2_id_'.$reportElement['id'].'" id="report_element_spr2_id_'.$reportElement['id'].'" onChange="editCustomReportElement(\''.$reportElement['id'].'\', \'spr2\', this.value)">';
				
				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $reportElement['spr2'] == null || $reportElement['spr2'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_break_select2_option'] . '</option>';
				}
				foreach ($variables as $variable) {
					
					// Ce ni ista kot prva izbrana
					if($variable['spr_id'] != $breakData1[1]){
						$text = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
						
						$value = $variable['sequence'].'-'.$variable['spr_id'].'-'.$variable['grd_id'];
						
						echo '<option value="'.$value.'" '
						. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
						. ( ($breakData2[0] == $variable['sequence'] && $breakData2[0] != null) ? ' selected="selected" ' : '')
						. '> '
						. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
						. $text . '</option>';
					}
				}
				
				echo '</select>';
				
				// Izbira podtipa (tabela / graf)
				echo ' <input style="margin-left:20px;" type="radio" value="0" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 0 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />'.$lang['srv_table'];
				echo ' <input type="radio" value="1" name="report_element_sub_type_'.$reportElement['id'].'" '.($reportElement['sub_type'] == 1 ? 'checked="checked"' : '').' onClick="editCustomReportElement(\''.$reportElement['id'].'\', \'sub_type\', this.value)" />'.$lang['srv_chart'];
			}
				
			echo '</div>';
		}
	}
	
	// Nastavitve na dnu
	function displayBottomSettings(){
		global $site_path;
		global $lang;
		
		echo '<div class="creport_bottom_settings">';
		
		echo '<a href="#" onclick="doArchiveCReport();" title="'.$lang['srv_analiza_arhiviraj_ttl'].'"><span class="faicon arhiv black very_large"></span></a>';
		echo '<a href="#" onclick="createArchiveCReportBeforeEmail();" title="'.$lang['srv_analiza_arhiviraj_email_ttl'] . '"><span class="faicon arhiv_mail black very_large"></span></a>';			
		
		echo '<a href="#" onClick="printAnaliza(\'CReport\'); return false;" title="'.$lang['hour_print2'].'"><span class="faicon print icon-grey_dark_link"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=creport_pdf&anketa=' . $this->ank_id) . '" target="_blank" title="'.$lang['PDF_Izpis'].'"><span class="faicon pdf black very_large"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=creport_rtf&anketa=' . $this->ank_id) . '" target="_blank" title="'.$lang['RTF_Izpis'].'"><span class="faicon rtf black very_large"></span></a>';	
		
		echo '</div>';
	}
	
	
	function displayTTestSubVar($reportElement){
		global $lang;
		
		echo '<div id="ttestVariablesSpan" style="margin: 10px 0 10px 80px;">';
		
		$ttestData1 = explode("-", $reportElement['spr1']);
		$spr = $ttestData1[1];
		$check1 = $ttestData1[3];
		$check2 = $ttestData1[4];
				
			
		# poiscemo pripadajoce variable
		$_spr_data = $this->classInstance->_HEADERS[$spr];
		echo $lang['srv_ttest_kategories_note'].' ('.$_spr_data['variable'].') '.$_spr_data['naslov'];
		echo '<br/>';
		switch ($_spr_data['tip']) {
			case 1:	#radio
			case 3:	#dropdown
			case 17:	#dropdown
				#nardimo inpute za vse opcije
				$sekvenca = $_spr_data['sequences'];
				foreach ($_spr_data['options'] as $value => $option) {
					$checked = ($check1 == $spr.'_'.$sekvenca.'_'.$value || $check2 == $spr.'_'.$sekvenca.'_'.$value) ? true : false;
					$disabled = ($check1 != null && $check2 != null && !$checked) ? ' disabled="disabled"' : '';
					
					echo '<label '.($disabled == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" class="subTtest_'.$reportElement['id'].'" type="checkbox" value="'.$spr.'_'.$sekvenca.'_'.$value.'" '.($checked ? ' checked="checked"' : '').' onchange="editCustomReportTTestVar(\''.$reportElement['id'].'\');" '.$disabled.' />('.$value.') - '.$option.'</label><br/>';
				}
				break;
				
			case 2:	#checkbox
				#nardimo inpute za vse opcije
				$option = '1';
				foreach ($_spr_data['grids'][0]['variables'] as $vid => $variable) {
					$checked = ($check1 == $spr.'_'.$variable['sequence'].'_'.$option || $check2 == $spr.'_'.$variable['sequence'].'_'.$option) ? true : false;
					$disabled = ($check1 != null && $check2 != null && !$checked) ? ' disabled="disabled"' : '';
					
					echo '<label '.($disabled == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" class="subTtest_'.$reportElement['id'].'" type="checkbox" value="'.$spr.'_'.$variable['sequence'].'_'.$option.'" '.($checked ? ' checked="checked"' : '').' onchange="editCustomReportTTestVar(\''.$reportElement['id'].'\');" '.$disabled.' />('.$variable['variable'].') - '.$variable['naslov'].'</label><br/>';
				}
				break;
				
			case 6:	#mgrid
				#nardimo inpute za vse opcije
				foreach ($_spr_data['options'] as $value => $option) {
					$sekvenca = $_spr_data['grids'][$value]['variables'][0]['sequence'];
					
					$checked = ($check1 == $spr.'_'.$sekvenca.'_'.$value || $check2 == $spr.'_'.$sekvenca.'_'.$value) ? true : false;
					$disabled = ($check1 != null && $check2 != null && !$checked) ? ' disabled="disabled"' : '';
					
					echo '<label '.($disabled == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" class="subTtest_'.$reportElement['id'].'" type="checkbox" value="'.$spr.'_'.$sekvenca.'_'.$value.'" '.($checked ? ' checked="checked"' : '').' onchange="editCustomReportTTestVar(\''.$reportElement['id'].'\');" '.$disabled.' />('.$value.') - '.$option.'</label><br/>';
				}
				break;
				
			case 16:	#mcheck
				#nardimo inpute za vse opcije
				# poi��emo pripadajo�o sekvenco
				#nardimo inpute za vse opcije
				$option = '1';
				foreach ($_spr_data['grids'][$grid]['variables'] as $vid => $variable) {
					$checked = ($check1 == $spr.'_'.$variable['sequence'].'_'.$option || $check2 == $spr.'_'.$variable['sequence'].'_'.$option) ? true : false;
					$disabled = ($check1 != null && $check2 != null && !$checked) ? ' disabled="disabled"' : '';
					
					echo '<label '.($disabled == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" class="subTtest_'.$reportElement['id'].'" type="checkbox" value="'.$spr.'_'.$variable['sequence'].'_'.$option.'" '.($checked ? ' checked="checked"' : '').' onchange="editCustomReportTTestVar(\''.$reportElement['id'].'\');" '.$disabled.' />('.$variable['variable'].') - '.$variable['naslov'].'</label><br/>';
				}
				break;

			default:
				if ((int)$_spr_data['tip'] > 0)
					echo'TODO for type:'.$_spr_data['tip'];
				break;
		}
		
		
		
		echo '</div>';
	}
	
	function displayReportElementHead($reportElement, $expanded, $title='&nbsp;'){
		global $lang;
		
		if ($this->isArchive == false && $this->publicCReport == false) {
			echo '<div class="report_element_head '.($reportElement['type'] == 8 ? ' text':'').' '.($expanded == 1 ? ' active':'').'">';
			
			// Popravimo naslov ce je textovni element
			if($reportElement['type'] == 8){
				$title = substr(strip_tags($reportElement['text']), 0, 30);
								
				if(strlen(strip_tags($reportElement['text'])) > 30)
					$title .= '...';
					
				$subtitle = '('.$lang['text'].')';
				$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
			}
			
			echo '<div class="report_element_title">';
			echo $title;
			echo '</div>';
				
				
			// Ikone za razsiritev, kopiranje, brisanje posameznega elementa
			echo '<div class="report_element_icons '.($expanded == 1 ? ' active' : '').'">';		
			
			// Print element
			echo ' <span class="faicon print_small icon-grey_dark_link" style="margin-left: 10px;" title="'.$lang['PRN_Izpis'].'" onClick="printCustomReportElement(\''.strip_tags($title).'\', \'report_element_'.$reportElement['id'].'\'); return false;"></span>';		
			// Kopiraj element
			echo ' <span class="faicon copy icon-grey_dark_link" style="margin-left: 10px;" title="'.$lang['srv_custom_report_copy'].'" onClick="copyCustomReportElement(\''.$reportElement['id'].'\');"></span>';
			// Brisi element
			echo ' <span class="faicon delete icon-grey_dark_link" style="margin-left: 10px;" title="'.$lang['srv_custom_report_delete'].'" onClick="deleteCustomReportElement(\''.$reportElement['id'].'\');"></span>';	

			// Uredi element
			/*if($reportElement['type'] != 8)
			if($expanded == 1)
				echo ' <span class="sprites pointer arrow_contract" style="margin-left: 10px;" title="'.$lang['srv_custom_report_contract'].'" onClick="expandCustomReportElement(\''.$reportElement['id'].'\');"></span>';
			else
				echo ' <span class="sprites pointer arrow_expand" style="margin-left: 10px;" title="'.$lang['srv_custom_report_expand'].'" onClick="expandCustomReportElement(\''.$reportElement['id'].'\');"></span>';
			*/
			
			echo '</div>';	
			

			echo '</div>';
		}
	}
	
	
	// Urejanje naslova porocila
	function displayTitle(){
		global $lang;
		global $global_user_id;
			
		echo '<div class="custom_report_title">';
		
		$what = 'creport_title_profile_'.$this->creportProfile;
		$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->creportAuthor' AND what='$what'");		

		if(mysqli_num_rows($sql) == 0){
			$titleString = $lang['export_analisys_creport'].': '.SurveyInfo::getInstance()->getSurveyTitle();
		}
		else{
			$row = mysqli_fetch_array($sql);		
			$titleString = $row['value'];
		}		 
	
		echo '<div class="creport_title_inline" contenteditable="true">';
		echo $titleString;	
		echo '</div>';
		
		echo '</div>';
	}
	
	// Urejanje dodatnega besedila za posamezen element
	function displayReportElementText($reportElement, $expanded){
		global $lang;
		
		echo '<div class="report_element_text report_element_comment" '.($expanded == 0 ? ' style="display:none;"' : '').'>';
			
		if($expanded == 1 && $this->isArchive == false && $this->publicCReport == false){
			echo '<span class="bold">'.$lang['srv_inv_archive_comment'].':</span>';
			
			echo '<span class="faicon edit2" title="'.$lang['srv_editor_title'].'" style="cursor:pointer; float:right; margin:20px 18px 0 0;" onclick="creport_load_editor(this); return false;"></span>';
	
			/*echo '<textarea style="width:100%; height:80px;" class="creport_textarea" el_id="'.$reportElement['id'].'" id="report_element_text_'.$reportElement['id'].'" onBlur="editCustomReportElement(\''.$reportElement['id'].'\', \'text\', this.value)">';
			echo $reportElement['text'];
			echo '</textarea>';*/
			
			echo '<div class="creport_text_inline" contenteditable="true" el_id="'.$reportElement['id'].'">';
			echo $reportElement['text'];	
			echo '</div>';
		}
		elseif($reportElement['text'] != ''){
			echo '<div class="creport_text_inline" '.($this->isArchive == false && $this->publicCReport == false ? ' contenteditable="true"':'').' el_id="'.$reportElement['id'].'">';
			echo $reportElement['text'];	
			echo '</div>';
		}				
		
		echo '</div>';
	}
	
	// Izrisemo sumarnik element
	function displaySum($reportElement, $expanded){
		global $lang;
		
		// Naslov
		$spr = SurveyAnalysis::$_HEADERS[$reportElement['spr1']];
		
		if($reportElement['spr1'] == '')
			$title = $lang['srv_select_spr'];
		else
			$title = $spr['variable'].' - '.$spr['naslov'];
			
		$subtitle = '('.$lang['srv_sumarnik'].')';		
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
						
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
				
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($expanded != 0)
			SurveyAnalysis::displaySums($reportElement['spr1']);

		echo '</div>';
	}
	
	// Izrisemo sumarnik element
	function displayFreq($reportElement, $expanded){
		global $lang;
			
		// Naslov
		$spr = SurveyAnalysis::$_HEADERS[$reportElement['spr1']];
		
		if($reportElement['spr1'] == '')
			$title = $lang['srv_select_spr'];
		else
			$title = $spr['variable'].' - '.$spr['naslov'];
			
		$subtitle = '('.$lang['srv_frequency'].')';
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($expanded != 0)
			SurveyAnalysis::displayFrequency($reportElement['spr1']);
				
		echo '</div>';
	}
	
	// Izrisemo sumarnik element
	function displayDesc($reportElement, $expanded){
		global $lang;	
		
		// Naslov
		$spr = SurveyAnalysis::$_HEADERS[$reportElement['spr1']];
		
		if($reportElement['spr1'] == '')
			$title = $lang['srv_select_spr'];
		else
			$title = $spr['variable'].' - '.$spr['naslov'];
			
		$subtitle = '('.$lang['srv_descriptor'].')';
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($expanded != 0){
		
			SurveyAnalysis::displayDescriptives($reportElement['spr1']);
					
			// Na novo napolnimo headers ker se resetira??
			SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		}
		
		echo '</div>';
	}
	
	// Izrisemo sumarnik element
	function displayChart($reportElement, $expanded){
		global $lang;	
		
		// Naslov
		$spr = SurveyAnalysis::$_HEADERS[$reportElement['spr1']];
		
		if($reportElement['spr1'] == '')
			$title = $lang['srv_select_spr'];
		else
			$title = $spr['variable'].' - '.$spr['naslov'];
			
		$subtitle = '('.$lang['srv_chart'].')';
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($expanded != 0){
		
			SurveyChart::Init($this->ank_id);
			
			if ($this->returnAsHtml != false) {
				SurveyChart::setUpReturnAsHtml(true);
				SurveyChart::setUpIsForArchive(true);
			}
			
			SurveyChart::displaySingle($reportElement['spr1']);
		}
		
		echo '</div>';
		
		echo '<script>charts_init();</script>';
	}
	
	//izrisemo crosstab element
	function displayCrosstab($reportElement, $expanded){
		global $lang;
		
		// Ustvarimo instanco razreda
		$this->classInstance = new SurveyCrosstabs();
		$this->classInstance->Init($this->ank_id);
		
		// Napolnimo podatke crosstabu
		$crossData1 = explode("-", $reportElement['spr1']);
		$crossData2 = explode("-", $reportElement['spr2']);
		
		$this->classInstance->setVariables($crossData1[0],$crossData1[1],$crossData1[2],$crossData2[0],$crossData2[1],$crossData2[2]);	
		
		// Naslov
		if($reportElement['spr1'] == '' || $reportElement['spr2'] == '')		
			$title = $lang['srv_select_spr'];			
		else{				
			$show_variables_values = true;
			
			$spr1 = $this->classInstance->_HEADERS[$crossData1[1]];
			$spr2 = $this->classInstance->_HEADERS[$crossData2[1]];
			
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
			
		$subtitle = '('.$lang['srv_crosstabs']. ($reportElement['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';

		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';
		
		if($reportElement['spr1'] != '' && $reportElement['spr2'] != '' && $expanded != 0){
			// Izrisemo tabelo
			if($reportElement['sub_type'] == 0){
				$this->classInstance->showChart = false;
				$this->classInstance->displayCrosstabsTable();
			}		
			// Izrisemo graf
			else{
				$tableChart = new SurveyTableChart($this->ank_id, $this->classInstance, 'crosstab');
				$tableChart->display();
			}
		}

		echo '</div>';
	}
	
	// Izrisemo multicrosstab tabelo
	function displayMulticrosstab($reportElement, $expanded){
		global $lang;
		global $global_user_id;
		
		// Ustvarimo instanco razreda
		$this->classInstance = new SurveyMultiCrosstabs($this->ank_id);
				
		// Naslov
		if($reportElement['spr1'] == '')
			$title = $lang['srv_select_spr'];
		else{
			// Trenutna aktivna tabela
			$sql = sisplet_query("SELECT * FROM srv_mc_table WHERE id='$reportElement[spr1]' AND ank_id='$this->ank_id' AND usr_id='$this->creportAuthor'");	
			$current_table = mysqli_fetch_array($sql);
			
			$title = $current_table['name'];
		}
			
		$subtitle = '('.$lang['srv_multicrosstabs'].')';		
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
						
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
				
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($expanded != 0 && $current_table != null){
			
			$this->classInstance->table_id = $current_table['id'];
			$this->classInstance->table_settings[$current_table['id']] = array(
				'title' 	=> $current_table['title'],
				'numerus' 	=> $current_table['numerus'],
				'percent' 	=> $current_table['percent'],
				'sums' 		=> $current_table['sums'],
				'navVsEno' 	=> $current_table['navVsEno'],
				'avgVar' 	=> $current_table['avgVar'],
				'delezVar' 	=> $current_table['delezVar'],
				'delez' 	=> $current_table['delez']
			);
			
			$this->classInstance->getVariableList();
			
			// Izris tabele
			echo '<div id="mc_table_holder_'.$current_table['id'].'" class="mc_table_holder">';
			$this->classInstance->displayTable();
			echo '</div>';
		}

		echo '</div>';
	}
	
	//izrisemo crosstab element
	function displayMean($reportElement, $expanded){
		global $lang;
		
		// Ustvarimo instanco razreda
		$this->classInstance = new SurveyMeans($this->ank_id);
		
		// Napolnimo podatke meansom
		$meanData1 = explode("-", $reportElement['spr2']);
		$v_first = array('seq' => $meanData1[0], 'spr' => $meanData1[1], 'grd' => $meanData1[2]);
		$meanData2 = explode("-", $reportElement['spr1']);
		$v_second = array('seq' => $meanData2[0], 'spr' => $meanData2[1], 'grd' => $meanData2[2]);

		$means[0] = $this->classInstance->createMeans($v_first, $v_second);

		// Nastavimo variable (potrebno za grafe
		$this->classInstance->variabla1[0] = $v_second;
		$this->classInstance->variabla2[0] = $v_first;
				
		// Naslov
		if($reportElement['spr1'] == '' || $reportElement['spr2'] == ''){		
			$title = $lang['srv_select_spr'];			
		}		
		else{				
			$label2 = strip_tags($this->classInstance->getSpremenljivkaTitle($means[0]['v1']));
			$label1 = strip_tags($this->classInstance->getSpremenljivkaTitle($means[0]['v2']));
			
			$title = $label1 . ' / ' . $label2;
		}
			
		$subtitle = '('.$lang['srv_means']. ($reportElement['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($reportElement['spr1'] != '' && $reportElement['spr2'] != '' && $expanded != 0){
			
			// Izrisemo tabelo
			if($reportElement['sub_type'] == 0){
				$this->classInstance->displayMeansTable($means);
			}		
			// Izrisemo graf
			else{
				$tableChart = new SurveyTableChart($this->ank_id, $this->classInstance, 'mean');
				$tableChart->display();
			}
		}

		echo '</div>';
	}
	
	//izrisemo ttest element
	function displayTTest($reportElement, $expanded){
		global $lang;

		// Ustvarimo instanco razreda
		$this->classInstance = new SurveyTTest($this->ank_id);
		
		// Naslov
		if($reportElement['spr1'] == '' || $reportElement['spr2'] == ''){		
			$title = $lang['srv_select_spr'];			
		}		
		else{				
			$label2 = strip_tags($this->getTTestLabel($reportElement['spr2']));
			$label1 = strip_tags($this->getTTestLabel($reportElement['spr1']));
			
			$title = $label1 . ' / ' . $label2;
		}
			
		$subtitle = '('.$lang['srv_ttest']. ($reportElement['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';

		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';
		
		if($expanded != 0){
		
			// Nastavimo session da lahko pravilno izrisemo tabelo/graf		  
			$ttestData1 = explode("-", $reportElement['spr1']);		
			$ttestData2 = explode("-", $reportElement['spr2']);
			
			if(count($ttestData1) == 5 && count($ttestData2) == 3){
				$dataArray = array();
				
				$dataArray['spr2'] = $ttestData1[1];
				$dataArray['grid2'] = $ttestData1[2];
				$dataArray['seq2'] = $ttestData1[0];
				$dataArray['label2'] = $label1;
				
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

				// Ustvarimo instanco razreda
				$this->classInstance = new SurveyTTest($this->ank_id);
				
				// Izrisemo tabelo
				if($reportElement['sub_type'] == 0){
					$variables1 = $this->classInstance->getSelectedVariables();
					foreach ($variables1 AS $v_first) {
						$ttest = $this->classInstance->createTTest($v_first, $sessionData['sub_conditions']);
						$this->classInstance->displayTtestTable($ttest);
					}
				}			
				// Izrisemo graf
				else{			
					$tableChart = new SurveyTableChart($this->ank_id, $this->classInstance, 'ttest');
					$tableChart->display();
				}
			}
		}
		
		echo '</div>';
	}

	function getTTestLabel($spr){

		$data = explode("-", $spr);
	
		$spid = $data[1];
		$seq = $data[0];
		$grid = $data[2];
	
		$spr_data = $this->classInstance->_HEADERS[$spid];
		if ($grid == 'undefined') {

			# imamp lahko ve� variabel
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
	
	function displayRazbitje($reportElement, $expanded){
		global $lang;
		
		// Ustvarimo instanco razreda
		$this->classInstance = new SurveyBreak($this->ank_id);
				
		// Naslov
		if($reportElement['spr1'] == '' || $reportElement['spr2'] == ''){		
			$title = $lang['srv_select_spr'];			
		}		
		else{
			$breakData1 = explode("-", $reportElement['spr1']);		
			$breakData2 = explode("-", $reportElement['spr2']);
			
			$label1 = '';
			$variables = $this->classInstance->getVariableList(2);			
			foreach ($variables as $variable) {

				if($breakData1[0] == $variable['sequence']){
					$label1 = ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' )) . $variable['variableNaslov'];
					$label1 = (strlen($label1) > 60) ? substr($label1, 0, 57).'...' : $label1;
					
					break;
				}
			}

			$label2 = '';
			$variables = $this->getBreakDependentVariableList();
			foreach ($variables as $variable) {
			
				if($breakData2[0] == $variable['sequence']){
					//$label2 = $variable['variableNaslov'];
					$label2 = (strlen($variable['variableNaslov']) > 60) ? substr($variable['variableNaslov'], 0, 57).'...' : $variable['variableNaslov'];
					
					break;
				}
			}
			
			$title = $label1 . ' / ' . $label2;
		}
			
		$subtitle = '('.$lang['srv_break']. ($reportElement['sub_type'] == 1 ? ' - '.$lang['srv_chart'] : '') .')';
		$title .= ' <span class="anl_ita">'.$subtitle.'</span>';	
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded, $title);
		
		// Nastavitve elementa
		$this->displayReportElementSettings($reportElement, $expanded);
		
		
		// Izpis tabele ali grafa za posamezen vnos
		echo '<div class="report_element_data" '.($expanded == 0 ? ' style="display:none;"' : '').'>';

		if($reportElement['spr1'] != '' && $reportElement['spr2'] != '' && $expanded != 0){

			# ali prikazujemo procente
			$this->classInstance->break_percent = true; /*isset($_SESSION['break_percent']) && (int)$_SESSION['break_percent'] == 0 ? false : true;*/
			
			$spr = $breakData1[1];

			# poiščemo pripadajoče variable
			$_spr_data = $this->classInstance->_HEADERS[$breakData1[1]];
			
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
			$frequencys = array();
			if (count($seqences) > 0) {
				foreach ($seqences as $seq) {
					
					if (count($options) > 0) {
						foreach ($options as $oKey => $option) {
							# zloopamo skozi variable
							$oKeyfrequencys = $this->classInstance->getAllFrequencys($oKey, $seq, $spr);
							if ($oKeyfrequencys != null) {
								$frequencys[$seq][$oKey] = $oKeyfrequencys;
							} 
						}
					}
				}
			}
			
			// Nastavimo odvisno spremenljivko
			$spr2 = $this->classInstance->_HEADERS[$breakData2[1]];
			$spr2['id'] = $breakData2[1];
			
			// Nastavimo se katero podtabelo izrisemo (sekvenca odvisne spr)
			$spr2['break_sub_table']['sequence'] = $breakData2[0];
			foreach ($spr2['grids'] AS $gkey => $grid) {				
				if($spr2['break_sub_table']['sequence'] == $grid['variables'][0]['sequence']){
					$spr2['break_sub_table']['key'] = $gkey;
					break;
				}
			}
			
			// Nastavimo se prvo spremenljivko in sekvenco (potrebno pri crosstabih ker se drugace to vlece iz SESSIONA)
			$spr2['creport_first_spr']['seq'] = $breakData1[0];
			$spr2['creport_first_spr']['spr'] = $breakData1[1];
							

			// Izrisemo tabelo
			if($reportElement['sub_type'] == 0){
				$this->classInstance->break_charts = 0;
				$this->classInstance->displayBreakSpremenljivka($spr,$frequencys,$spr2);		
			}
			// Izrisemo graf
			else{
				$this->classInstance->break_charts = 1;
				$this->classInstance->displayBreakSpremenljivka($spr,$frequencys,$spr2);
			}
		}

		echo '</div>';
	}
	
	/** funkcija vrne seznam primern variabel za crostabe
	 *
	 */
	function getBreakDependentVariableList() {

		$variablesList = array();
		
		# zloopamo skozi header in dodamo variable (potrebujemo posamezne sekvence)
		foreach ($this->classInstance->_HEADERS AS $skey => $spremenljivka) {
		
			$tip = $spremenljivka['tip'];
			
			$_dropdown_condition = (is_numeric($tip) && $tip != 5 && $tip != 8 && $tip != 9) ? true : false;	
			if ($_dropdown_condition) {	
			
				$cnt_all = (int)$spremenljivka['cnt_all'];
				if ( $cnt_all == '1' || in_array($tip, array(1,2,3,4,7,17,18,21,22,25)) || ($tip == 6 && $spremenljivka['enota'] == 2) ) {
					
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
	
	// Izrisemo page break med elementi
	function displayBreak($reportElement){
		global $lang;
		
		echo '<span class="report_element_pb" title="'.$lang['srv_rem_pagebreak'].'" onClick="deleteCustomReportElement(\''.$reportElement['id'].'\');">';
				
		echo '</span>';
	}
	
	// Izrisemo element z besedilom
	function displayText($reportElement, $expanded){
		global $lang;
		
		// Glava elementa (naslov, ikone...)		
		$this->displayReportElementHead($reportElement, $expanded);
				
		echo '<div class="report_element_text" style="padding-top:15px; '.($expanded == 0 ? ' display:none;' : '').'">';
			
		/*if($expanded == 1){
		
			echo '<span class="faicon edit2" title="'.$lang['srv_editor_title'].'" style="cursor:pointer; display:block; float:right;" onclick="creport_load_editor(this); return false;"></span>';
		
			echo '<textarea style="width:100%; height:80px;" class="creport_textarea" el_id="'.$reportElement['id'].'" id="report_element_text_'.$reportElement['id'].'" onBlur="editCustomReportElement(\''.$reportElement['id'].'\', \'text\', this.value)">';
			echo $reportElement['text'];
			echo '</textarea>';
		}*/
		//else{
		if ($this->isArchive == false && $this->publicCReport == false) {
			echo '<span class="faicon edit2" title="'.$lang['srv_editor_title'].'" style="cursor:pointer; display:block; float:right; margin:20px 18px 0 0;" onclick="creport_load_editor(this); return false;"></span>';
		}
	
		echo '<br /><div class="creport_text_inline" '.($this->isArchive == false && $this->publicCReport == false ? ' contenteditable="true"':'').' el_id="'.$reportElement['id'].'">';
		echo $reportElement['text'];	
		echo '</div>';
		//}		
		
		echo '</div>';
	}
	
	
	// Dodajanje elementa porocila
	function addNewElement($id=0){
		global $lang;
		
		// Dodajanje vmes in na zacetku
		if($id != 0){
			echo '<span class="pointer" title="'.$lang['srv_custom_report_add'].'" style="margin-left: 20px; " onClick="addEmptyCustomReportElement(\''.$id.'\');"><span class="faicon add small icon-blue_light"></span> '.$lang['srv_custom_report_add'].'</span>';
		
			echo '<span class="pointer" title="'.$lang['srv_custom_report_add_text'].'" style="margin-left: 20px;" onClick="addTextCustomReportElement(\''.$id.'\');"><span class="faicon add small icon-blue_dark"></span> '.$lang['srv_custom_report_add_text'].'</span>';		

			if($id > 0)
				echo '<span class="pointer" title="'.$lang['srv_add_pagebreak'].'" style="margin-left: 20px;" onClick="addPBCustomReportElement(\''.$id.'\');"><span class="faicon paragraph icon-blue"></span> '.$lang['srv_add_pagebreak'].'</span>';
		}
		
		// Dodajanje na dnu (brez page breaka)
		if($id == 0){
			echo '<span class="pointer" style="margin:-10px 0 10px 20px;" onClick="addEmptyCustomReportElement(\''.$id.'\');"><span class="faicon add small icon-blue_light" title="'.$lang['srv_custom_report_add'].'"></span> '.$lang['srv_custom_report_add'].'</span>';
	
			echo '<span class="pointer" style="margin:-10px 0 10px 20px;" onClick="addTextCustomReportElement(\''.$id.'\');"><span class="faicon add small icon-blue_dark" title="'.$lang['srv_custom_report_add_text'].'"></span> '.$lang['srv_custom_report_add_text'].'</span>';		
		}
	}
	
	public static function checkEmpty($ank_id){
		global $global_user_id;
		
		$creportProfile = SurveyUserSetting :: getInstance()->getSettings('default_creport_profile');
		$creportProfile = isset($creportProfile) ? $creportProfile : 0;
		
		$creportAuthor = SurveyUserSetting :: getInstance()->getSettings('default_creport_author');
		$creportAuthor = isset($creportAuthor) ? $creportAuthor : $global_user_id;
		
		// preverimo ce je ze kaksen element v porocilu
		$sql = sisplet_query("SELECT id FROM srv_custom_report WHERE ank_id='$ank_id' AND usr_id='$creportAuthor' AND profile='$creportProfile' ");				
		
		if(mysqli_num_rows($sql) > 0){	
			$empty = false;
		}
		else{
			$empty = true;
		}
			
		return $empty;
	}
	
	
	function setUpReturnAsHtml($returnAsHtml = false) {
   		$this->returnAsHtml = $returnAsHtml;					# ali vrne rezultat analiz kot html ali ga izpiše
		SurveyAnalysis::setUpReturnAsHtml($returnAsHtml);
    }
  
    function setUpIsForArchive($isArchive = false) {
    	$this->isArchive = $isArchive;					# nastavimo da smo v arhivu
		SurveyAnalysis::setUpIsForArchive($isArchive);
    }
	
	function setUpIsForPublic($publicCReport = false) {
    	$this->publicCReport = $publicCReport;
		
		SurveyAnalysis::$publicAnalyse = $publicCReport;
		SurveyAnalysis::$printPreview = $publicCReport;
		
		SurveyChart::$publicChart = $publicCReport;
    }
	
	
	function displaySettingsProfiles(){
		global $site_path;
		global $global_user_id;
		global $lang;

		$time_created = '';
		$time_edit = '';
		
		
		$what = 'creport_default_profile_name';
		$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$global_user_id' AND what='$what'");		
		if(mysqli_num_rows($sql) == 0){
			$default_name = $lang['srv_custom_report_default'];
		}
		else{
			$row = mysqli_fetch_array($sql);		
			$default_name = $row['value'];
		}

		$profile = $this->getProfile($this->creportProfile);			
		$name = $profile['name'];
		
		
		echo '<h2>'.$lang['srv_custom_report_profile_title'].'</h2>';		
						
		echo '<div id="creport_settings_profiles_left">';	
		
		// Prednastavljen profil
       	echo '<span id="creport_profiles" class="creport_profiles select">';

		echo '<div class="option'.($this->creportProfile == 0 && $this->creportAuthor == $global_user_id ? ' active' : '').'" id="creport_profile_0_'.$global_user_id.'" author="'.$global_user_id.'" value="0">'.$default_name.'</div>';	
		
		// Loop po lastnih porocilih
		$sql = sisplet_query("SELECT * FROM srv_custom_report_profiles WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id'");
		while($row = mysqli_fetch_array($sql)){
			echo '<div class="option'.($this->creportProfile == $row['id'] && $this->creportAuthor == $global_user_id ? ' active' : '').'" id="creport_profile_'.$row['id'].'_'.$global_user_id.'" author="'.$global_user_id.'" value="'.$row['id'].'">'.$row['name'].'</div>';
			
			// Preberemo cas kreiranja porocila
			if($this->creportProfile == $row['id'])
				$time_created = $row['time_created'];			
		}
		
		// Loop po deljenih porocilih drugih urednikov ankete
		$sqlA = sisplet_query("SELECT s.*, u.email FROM srv_custom_report_share s, users u WHERE ank_id='$this->ank_id' AND share_usr_id='$global_user_id' AND u.id=s.author_usr_id");
		while($rowA = mysqli_fetch_array($sqlA)){
			
			// Ce gre za osnovno porocilo ki ga ne more pobrisati
			if($rowA['profile_id'] == 0){
				
				// Dobimo ime osnovnega porocila
				$what = 'creport_default_profile_name';
				$sqlN = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='".$rowA['author_usr_id']."' AND what='$what'");		
				if(mysqli_num_rows($sqlN) == '0'){
					$default_name = $lang['srv_custom_report_default'];
				}
				else{
					$rowN = mysqli_fetch_array($sqlN);
					$default_name = ($rowN['value'] == '') ? $lang['srv_custom_report_default'] : $rowN['value'];
				}
		
				echo '<div class="option'.($this->creportProfile == 0 && $this->creportAuthor == $rowA['author_usr_id'] ? ' active' : '').'" id="creport_profile_0_'.$rowA['author_usr_id'].'" author="'.$rowA['author_usr_id'].'" value="0">';
				echo $default_name . ' ('.$rowA['email'].')';
				echo '</div>';
			}
			// Ce gre za dodatno porocilo ga imamo normalno v bazi
			else{
				$sql = sisplet_query("SELECT * FROM srv_custom_report_profiles WHERE ank_id='$this->ank_id' AND usr_id='".$rowA['author_usr_id']."'");
				while($row = mysqli_fetch_array($sql)){
					echo '<div class="option'.($this->creportProfile == $row['id'] && $this->creportAuthor == $rowA['author_usr_id'] ? ' active' : '').'" id="creport_profile_'.$row['id'].'_'.$rowA['author_usr_id'].'" author="'.$rowA['author_usr_id'].'" value="'.$row['id'].'">';
					echo $row['name'] . ' ('.$rowA['email'].')';
					echo '</div>';
					
					// Preberemo cas kreiranja porocila
					if($this->creportProfile == $row['id'])
						$time_created = $row['time_created'];			
				}
			}
		}
		
		echo '</span>';
				

		// Ce je izbran custom profil imamo na dnu gumba brisi in preimenuj, pri default pa samo preimenuj
		// Preimenuje, brise in share lahko samo za lastna porocila
		if($this->creportAuthor == $global_user_id){
			echo '<div style="float:left; margin-bottom:10px;">';
			echo '<a href="#" onclick="creport_profile_action(\'show_rename\'); return false;">'.$lang['srv_rename_profile'].'</a>'."\n";
			
			if($this->creportProfile > 0)
				echo '<br /><a href="#" onclick="creport_profile_action(\'show_delete\'); return false;">'.$lang['srv_delete_profile'].'</a>'."\n";
			
			// Deli poročilo z drugimi uredniki
			echo '<br /><a href="#" onclick="creport_profile_action(\'show_share\'); return false;">'.$lang['srv_custom_report_share'].'</a>'."\n";
			echo '</div>';
		}

				
		// Preberemo najkasneje editiran element (cas spreminjanja)
		$sqlt = sisplet_query("SELECT MAX(time_edit) FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile'");		
		if(mysqli_num_rows($sqlt) > 0){
			$rowt = mysqli_fetch_array($sqlt);
			$time_edit = $rowt['MAX(time_edit)'];
		}
		else
			$time_edit = $time_created;	
		
		// Cas kreirranja in urejanja profila
		echo '<div style="float:right; text-align:right;">';
		
		if($time_created != '' && $time_created != '0000-00-00 00:00:00'){
			$time_created = strtotime($time_created);
			echo $lang['srv_custom_report_time_created'].': <span class="bold">'.date("d.m.Y H:i", $time_created).'</span><br />';
		}
		if($time_edit != '' && $time_edit != '0000-00-00 00:00:00'){
			$time_edit = strtotime($time_edit);
			echo $lang['srv_custom_report_time_edited'].': <span class="bold">'.date("d.m.Y H:i", $time_edit).'</span>';
		}
		
		echo '</div>';
			
		echo '</div>';
		
		
		// Komentar profila
		echo '<div id="creport_settings_profiles_comment">';	
		
		$what = 'creport_comment_profile_'.$this->creportProfile;
		$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->creportAuthor' AND what='$what'");		
		if(mysqli_num_rows($sql) == 0){
			$comment = '';
		}
		else{
			$row = mysqli_fetch_array($sql);		
			$comment = $row['value'];
		}		 
		
		echo '<span class="bold clr">'.$lang['srv_inv_archive_comment'].':</span>';		
		// Komentar lahko popravlja samo avtor porocila
		if($this->creportAuthor == $global_user_id)
			echo '<textarea onBlur="creport_profile_comment(this.value)">'.$comment.'</textarea>';
		else
			echo '<span>'.$comment.'</span>';
				
		echo '</div>';
		
		
		// cover Div
        echo '<div id="dsp_cover_div"></div>'."\n";	
		
		echo '<span class="clr"></span>';

		echo '<div style="position:absolute; bottom:20px; right:20px;">';
		echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="use_creport_profile(); return false;"><span>'.$lang['srv_save_and_run_profile'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight" title="'.$lang['srv_custom_report_create'].'"><div class="buttonwrapper"><a class="ovalbutton" href="#" onclick="creport_profile_action(\'show_new\'); return false;"><span>'.$lang['srv_custom_report_create'] . '</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton" href="#" onclick="close_creport_profile(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';

		echo '</div>';
	}
	
	function displayProfilePopups(){
		global $lang;
		global $global_user_id;
		
		$profile = $this->getProfile($this->creportProfile);			
		$name = $profile['name'];
		
		// div za kreacijo novega
        echo '<div id="newCReportProfile">';
		echo '<div style="float:left; width:410px; text-align:right;">'.$lang['srv_custom_report_name'].': '."\n";
        echo '<input id="newCReportProfileName" name="newCReportProfileName" type="text" value="" size="50"  /></div>'."\n";
		echo '<div style="float:left; width:410px; text-align:right; padding:5px 0px;">'.$lang['srv_inv_archive_comment'].': '."\n";
		echo '<input id="newCReportProfileComment" name="newCReportProfileComment" type="text" value="" size="50"  /></div>'."\n";
        echo '<br /><br /><br /><br /><span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="creport_profile_action(\'new\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="creport_profile_action(\'cancel_new\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
		
		// div za preimenovanje
        echo '<div id="renameCReportProfile">'.$lang['srv_custom_report_name'].': '."\n";
        echo '<input id="renameCReportProfileName" name="renameCReportProfileName" type="text" size="45" />'."\n";
        echo '<input id="renameCReportProfileId" type="hidden" value="' . $this->creportProfile . '"  />'."\n";
        echo '<br /><br /><span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="creport_profile_action(\'rename\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="creport_profile_action(\'cancel_rename\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
		
		// div za brisanje
        echo '<div id="deleteCReportProfile">'.$lang['srv_custom_report_delete_confirm'].': <span id="deleteCReportProfileName" style="font-weight:bold;"></span>?'."\n";
        echo '<input id="deleteCReportProfileId" type="hidden" value="' . $this->creportProfile . '"  />'."\n";
        echo '<br /><br /><span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="creport_profile_action(\'delete\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="creport_profile_action(\'cancel_delete\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
		
		// div za deljenje z drugimi uredniki
        echo '<div id="shareCReportProfile">';
        echo '</div>'."\n";
	}
	
	public function getProfile($profile_id){
		global $global_user_id;
		
		$sql = sisplet_query("SELECT * FROM srv_custom_report_profiles WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$profile_id'");
		$profile = mysqli_fetch_array($sql);	

		return $profile;
	}

	public function getTitle(){
		global $global_user_id;
		global $lang;
		
		$what = 'creport_title_profile_'.$this->creportProfile;
		$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->creportAuthor' AND what='$what'");		

		if(mysqli_num_rows($sql) == 0){
			$titleString = $lang['export_analisys_creport'].': '.SurveyInfo::getInstance()->getSurveyTitle();
		}
		else{
			$row = mysqli_fetch_array($sql);		
			$titleString = $row['value'];
		}
		
		return $titleString;
	}
	
	
	public function displayPublicCReport($properties) {
        global $lang;
        global $site_url;
    
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
		
        $anketa = $this->ank_id;
		
		$this->creportProfile = $properties['creportProfile'];
		$this->creportAuthor = $properties['creportAuthor'];
		
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
        echo '    if(document.readyState=="complete"){';
        echo '        window.close()';
        echo '    }';
        echo '    else{';
        echo '        setTimeout("chkstate()",2000)';
        echo '    }';
        echo '}';
        echo 'function print_win(){';
        echo '    window.print();';
        echo '    chkstate();';
        echo '}';
        echo 'function close_win(){';
        echo '    window.close();';
        echo '}';
        echo '</script>';
        echo '</head>';
    
        echo '<body style="margin:5px; padding:5px;" >';

		$what = 'creport_title_profile_'.$this->creportProfile;
		$sql = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$anketa' AND uid='$this->creportAuthor' AND what='$what'");		
		if(mysqli_num_rows($sql) == 0){
			$titleString = $lang['srv_publc_creport_title_for'].SurveyInfo::getInstance()->getSurveyTitle();
		}
		else{
			$row = mysqli_fetch_array($sql);		
			$titleString = $row['value'];
		}	
        //echo '<h2>'.$lang['srv_publc_creport_title_for'] .$titleString.'</h2>';
        echo '<h2>'.$titleString.'</h2>';

        echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $anketa . '" />';
        echo '<div id="analiza_data">';

        # ponastavimo nastavitve- filter
        self::displayReport();
        echo '</div>';
            
        echo '<div id="navigationBottom" class="printHide">';   
        echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/printer.png" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';

        echo '<br class="clr" />';
        echo '</div>';
    
        echo '</body>';
        echo '</html>';
    }
	
	
	// Funkcije ajaxa
	public function ajax() {
		global $lang;
		global $global_user_id;
		global $site_url;
	
		$_GET['m'] = 'analysis_creport';
	
		if (isset ($_POST['anketa']))
			$this->ank_id = $_POST['anketa'];
		if (isset ($_POST['element_id']))
			$element_id = $_POST['element_id'];
				
		if (isset ($_POST['what']))
			$what = $_POST['what'];
		if (isset ($_POST['value']))
			$value = $_POST['value'];
				
		// Nastavimo se nacin (skrcen/razsirjen)		
		$this->expanded = (isset($_POST['expanded']) ? $_POST['expanded'] : 0);
		
		// Dodajanje praznega elementa v report
		if($_GET['a'] == 'add_empty_element'){
			
			// dodajanje vmes
			if($element_id > 0){
				$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$element_id' AND profile='$this->creportProfile'");

				$row = mysqli_fetch_assoc($sql);			
				$vrstni_red = $row['vrstni_red'] + 1;

				// Prestevilcimo elemente
				$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red+1 WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND vrstni_red>='$vrstni_red' AND profile='$this->creportProfile'");				

				$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '$vrstni_red', '$this->creportProfile', NOW())");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				$el_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			
			//dodajanje na zacetku
			elseif($element_id == -1){
				// Prestevilcimo elemente
				$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red+1 WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile'");				

				$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '1', '$this->creportProfile', NOW())");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				$el_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			
			// dodajanje na koncu
			else{	
				$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile' ORDER BY vrstni_red DESC");
				
				if(mysqli_num_rows($sql) > 0){
					$row = mysqli_fetch_assoc($sql);			
					$vrstni_red = $row['vrstni_red'] + 1;
				}
				else
					$vrstni_red = 1;

				$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '$vrstni_red', '$this->creportProfile', NOW())");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				$el_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			
			$this->displayReport();
			
			echo '<input type="hidden" el_id="'.$el_id.'" id="added_element" />';
		}
		
		// Dodajanje ze nastavljenega elementa v report (klik na zvezdico)
		if($_GET['a'] == 'add_element'){
			
			if (isset ($_POST['type']))
				$type = $_POST['type'];
			if (isset ($_POST['sub_type']))
				$sub_type = $_POST['sub_type'];
			if (isset ($_POST['spr1']))
				$spr1 = $_POST['spr1'];
			if (isset ($_POST['spr2']))
				$spr2 = $_POST['spr2'];
			if (isset ($_POST['insert']))
				$insert = $_POST['insert'];
			
					
			// Vstavljanje
			if($insert == 1){
				// Razberemo vrstni red
				$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile' ORDER BY vrstni_red DESC");				
				if(mysqli_num_rows($sql) > 0){
					$row = mysqli_fetch_assoc($sql);			
					$vrstni_red = $row['vrstni_red'] + 1;
				}
				else
					$vrstni_red = 1;
			
				$sql = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, type, sub_type, spr1, spr2, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '$vrstni_red', '$type', '$sub_type', '$spr1', '$spr2', '$this->creportProfile', NOW())");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			
				echo $vrstni_red;
			}
			
			// brisanje
			else{
				// Preberemo vrstni red	elementa, ki ga brisemo
				$sql = sisplet_query("SELECT id, vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND type='$type' AND sub_type='$sub_type' AND spr1='$spr1' AND spr2='$spr2' AND profile='$this->creportProfile'");				
				$row = mysqli_fetch_array($sql);
				$vrstni_red = $row['vrstni_red'];
				$element_id = $row['id'];
				
				$sql = sisplet_query("DELETE FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$element_id' AND profile='$this->creportProfile'");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
				
				// Prestevilcimo elemente
				$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red-1, time_edit=NOW() WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND vrstni_red>'$vrstni_red' AND profile='$this->creportProfile'");
				
				echo -1;
			}
		}
		
		// Brisanje elementa
		if($_GET['a'] == 'delete_element'){
			
			$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$element_id' AND profile='$this->creportProfile'");
			$row = mysqli_fetch_array($sql);
			$vrstni_red = $row['vrstni_red'];
			
			$sql = sisplet_query("DELETE FROM srv_custom_report WHERE ank_id='$this->ank_id' AND id='$element_id' AND profile='$this->creportProfile'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			
			// Prestevilcimo elemente
			$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red-1, time_edit=NOW() WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND vrstni_red>'$vrstni_red' AND profile='$this->creportProfile'");
			
			$this->displayReport();
		}
		
		// Editiranje elementa v reportu
		if($_GET['a'] == 'edit_element'){
		
			// Preklop na navaden tip (freq, sums, graf, desc)
			if($what == 'type' && $value < 5){
		
				$sql = sisplet_query("SELECT type FROM srv_custom_report WHERE ank_id='$this->ank_id' AND id='$element_id' AND profile='$this->creportProfile'");
				$row = mysqli_fetch_array($sql);
				
				// Ce preklapljamo iz crosstabov, meansov ali ttesta - resetiramo spremenljivke
				if($row['type'] > 4){
					$s = sisplet_query("UPDATE srv_custom_report SET $what='$value', spr1='', spr2='', time_edit=NOW() WHERE ank_id='$this->ank_id' AND id='$element_id' AND profile='$this->creportProfile'");		
				}
				else{
					$s = sisplet_query("UPDATE srv_custom_report SET $what='$value', time_edit=NOW() WHERE ank_id='$this->ank_id' AND id='$element_id' AND profile='$this->creportProfile'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				}
			}
			
			// Ce preklopimo na crosstabe, means ali ttest resetiramo vse spremenljivke
			elseif($what == 'type' && $value > 4){
				$s = sisplet_query("UPDATE srv_custom_report SET $what='$value', spr1='', spr2='', time_edit=NOW() WHERE ank_id='$this->ank_id' AND id='$element_id' AND profile='$this->creportProfile'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			
			// Ostali preklopi
			else{
				$s = sisplet_query("UPDATE srv_custom_report SET $what='$value', time_edit=NOW() WHERE ank_id='$this->ank_id' AND id='$element_id' AND profile='$this->creportProfile'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}

			
			$expanded = isset($_POST['expanded']) ? $_POST['expanded'] : 1;
			
			$this->displayReportElement($element_id, $expanded);
		}
		
		if($_GET['a'] == 'copy_element'){
			
			$sql = sisplet_query("SELECT * FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$element_id' AND profile='$this->creportProfile'");
			$row = mysqli_fetch_array($sql);
			
			// najprej prestevilcimo elemente ki so za kopiranim
			$sql2 = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red+1 WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND vrstni_red>'$row[vrstni_red]' AND profile='$this->creportProfile'");
			
			$vrstni_red = $row['vrstni_red'] + 1;
			
			$sqlInsert = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, type, sub_type, spr1, spr2, text, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '$vrstni_red', '$row[type]', '$row[sub_type]', '$row[spr1]', '$row[spr2]', '$row[text]', '$row[profile]', NOW())");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);			
			
		
			$this->displayReport();
		}
		
		// Sortiranje elementov
		if($_GET['a'] == 'change_order'){

			$sortable = $_POST['sortable'];
			$exploded = explode('&', $sortable);

			$i = 1;
			foreach ($exploded AS $key) {
				$key = str_replace('variabla_', '', $key);
				$explode = explode('[]=', $key);
				
				$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red = '$i', time_edit = NOW() WHERE id = '$explode[1]' AND profile='$this->creportProfile'");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
				
				$i++;
			}
		}
		
		// Alert pri dodajanju prvega
		if($_GET['a'] == 'first_alert'){
					
			echo $lang['srv_custom_report_first'];
			/*Ozna�eni element (tabela/graf) se je prenesel v poro�ilo po meri, kjer lahko prilagajate prikaz in zaporedje elementov*/
				
			echo '<a href="#" onClick="window.location = \''.$site_url.'admin/survey/index.php?anketa='.$this->ank_id.'&a=analysis&m=analysis_creport\';"><span style="display: block; padding-top: 10px;">'.$lang['srv_custom_report_link'].'</span></a>';	

			echo '<div class="buttons">';	
			
			//echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_blue" href="#" onClick="window.location = \''.$site_url.'admin/survey/index.php?anketa='.$this->ank_id.'&a=analysis&m=analysis_creport\';"><span>'.$lang['srv_custom_report'].'</span></a></div></span>';					
			echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton" href="#" onClick="$(\'#fade\').fadeOut(\'slow\'); $(\'#custom_report_alert\').fadeOut();"><span>'.$lang['srv_zapri'].'</span></a></div></span>';			
			
			echo '</div>';
		}
	
		// razsiritev / skrcenje elementa
		if($_GET['a'] == 'expand_element'){
			
			if (isset ($_POST['expanded']))
				$expanded = $_POST['expanded'];
			
			$this->displayReportElement($element_id, $expanded);
		}
		
		// Dodajanje text elementa v report
		if($_GET['a'] == 'add_text_element'){
			
			// dodajanje vmes
			if($element_id > 0){
				$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$element_id' AND profile='$this->creportProfile'");

				$row = mysqli_fetch_assoc($sql);			
				$vrstni_red = $row['vrstni_red'] + 1;

				// Prestevilcimo elemente
				$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red+1 WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND vrstni_red>='$vrstni_red' AND profile='$this->creportProfile'");				

				$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, type, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '8', '$vrstni_red', '$this->creportProfile', NOW())");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				$el_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			
			//dodajanje na zacetku
			elseif($element_id == -1){
				// Prestevilcimo elemente
				$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red+1 WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile'");				

				$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, type, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '8', '1', '$this->creportProfile', NOW())");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				$el_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			
			// dodajanje na koncu
			else{	
				$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile' ORDER BY vrstni_red DESC");
				
				if(mysqli_num_rows($sql) > 0){
					$row = mysqli_fetch_assoc($sql);			
					$vrstni_red = $row['vrstni_red'] + 1;
				}
				else
					$vrstni_red = 1;

				$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, type, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '8', '$vrstni_red', '$this->creportProfile', NOW())");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				$el_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			
			$this->displayReport();
			
			echo '<input type="hidden" el_id="'.$el_id.'" id="added_element" />';
		}
		
		// Dodajanje page breaka v report
		if($_GET['a'] == 'add_pb_element'){
			
			$sql = sisplet_query("SELECT vrstni_red FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND id='$element_id' AND profile='$this->creportProfile'");

			$row = mysqli_fetch_assoc($sql);			
			$vrstni_red = $row['vrstni_red'] + 1;

			// Prestevilcimo elemente
			$sql = sisplet_query("UPDATE srv_custom_report SET vrstni_red=vrstni_red+1 WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND vrstni_red>='$vrstni_red' AND profile='$this->creportProfile'");				

			$s = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, type, vrstni_red, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '-1', '$vrstni_red', '$this->creportProfile', NOW())");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
			$this->displayReport();
		}
		
		// Urejanje naslova porocila
		if($_GET['a'] == 'edit_title'){
			
			$what = 'creport_title_profile_'.$this->creportProfile;
			
			if($value != ''){
				$s = sisplet_query("INSERT INTO srv_user_setting_for_survey (sid, uid, what, value) VALUES ('$this->ank_id', '$this->creportAuthor', '$what', '$value') ON DUPLICATE KEY UPDATE value='$value'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			else{
				$s = sisplet_query("DELETE FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->creportAuthor' AND what='$what'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
		}
		
		// prikaz previewja
		if($_GET['a'] == 'report_preview'){

			echo '<h1 style="text-align: center; margin-top: 20px;">'.$lang['export_analisys_creport'].': '.SurveyInfo::getInstance()->getSurveyTitle().'</h1>';
		
			$this->setUpReturnAsHtml(false);
			$this->setUpIsForArchive(true);
		
			$this->expanded = 1;
		
			$this->displayReport();
			
			echo '<div id="navigationBottom" class="printHide">';
			echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="window.close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
			//echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="icons/icons/printer.png" alt="'.$lang['hour_print2'].'" vartical-align="middle" />'.$lang['hour_print2'].'</span></a></div></span>';
			echo '<div class="clr"></div>';
			echo '</div>';
		}
		
		// Alert pri dodajanju vseh elementov istega tipa v report
		if($_GET['a'] == 'all_elements_alert'){
					
			$type = (isset($_POST['type'])) ? $_POST['type'] : 0;
	
			echo '<div style="margin-bottom: 10px;">'.$lang['srv_custom_report_comments_alert'.$type].'</div>';
				
			echo '<div class="buttons">';	
			
			echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton" href="#" onClick="$(\'#fade\').fadeOut(\'slow\'); $(\'#custom_report_alert\').fadeOut();"><span>'.$lang['srv_custom_report_alert_no'].'</span></a></div></span>';					
			echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onClick="addCustomReportAllElements(\''.$type.'\');"><span>'.$lang['srv_custom_report_alert_yes'].'</span></a></div></span>';					
			
			echo '</div>';
		}
		
		// Dodajanje vseh elementov istega tipa v report
		if($_GET['a'] == 'all_elements_add'){
			
			$type = (isset($_POST['type'])) ? $_POST['type'] : 0;

			$sql = sisplet_query("SELECT MAX(vrstni_red) FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$this->creportAuthor' AND profile='$this->creportProfile'");
			if(mysqli_num_rows($sql) > 0){
				$row = mysqli_fetch_assoc($sql);					
				$vrstni_red = $row['MAX(vrstni_red)'] + 1;
			}
			else
				$vrstni_red = 1;
			
			
			// Vstavljamo BREAK
			if($type == 9){
				
				// Ustvarimo instanco breaka
				$this->classInstance = new SurveyBreak($this->ank_id);
			
				$spr1 = (isset($_POST['spr1'])) ? $_POST['spr1'] : 0;
				$spremenljivka =  explode("-", $spr1);
				
				$sub_type = (isset($_POST['sub_type'])) ? $_POST['sub_type'] : 0;

				// Loop po odvisnih spremenljivkah in variablah
				$variables = $this->getBreakDependentVariableList();
				foreach ($variables as $variable) {

					// Ce ne gre za disablan element in ne gre za isto spremenljivko kot spr1
					if((int)$variable['canChoose'] == 1 && $variable['spr_id'] != $spremenljivka[1]){						
						$spr2 = $variable['sequence'].'-'.$variable['spr_id'].'-undefined';
					
						// Vstavimo element v bazo
						$sqlInsert = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, type, sub_type, spr1, spr2, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '$vrstni_red', '9', '$sub_type', '$spr1', '$spr2', '$this->creportProfile', NOW())");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);	
						
						$vrstni_red++;
					}
				}
			}
			
			// Vstavljamo ostale osnove (sums, grafi, freq, desc)
			else{
				# preberemo header
				foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
					# preverjamo ali je meta
					if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )){
											
						# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore
						$only_valid = 0;
						if (count($spremenljivka['grids']) > 0) {
							foreach ($spremenljivka['grids'] AS $gid => $grid) {
								# dodamo dodatne vrstice z albelami grida
								if (count($grid['variables']) > 0 )
								foreach ($grid['variables'] AS $vid => $variable ){
									$_sequence = $variable['sequence'];	# id kolone z podatki
									$only_valid += (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
								}
							}
						}

						// Ce ja kaksen veljaven oz ce prikazujemo tudi prazne
						if(SurveyDataSettingProfiles :: getSetting('hideEmpty') != 1 || $only_valid > 0){
							$sqlInsert = sisplet_query("INSERT INTO srv_custom_report (ank_id, usr_id, vrstni_red, type, spr1, profile, time_edit) VALUES('$this->ank_id', '$this->creportAuthor', '$vrstni_red', '$type', '$spid', '$this->creportProfile', NOW())");
							if (!$s) echo mysqli_error($GLOBALS['connect_db']);

							$vrstni_red++;
						}
					}
				}
			}

			//$this->displayReport();
		}
		
		// Odpremo okno za izbiro/save profila
		if($_GET['a'] == 'creport_show_profiles') {
			
			$this->displaySettingsProfiles();
		}
		
		// Spreminjamo profil
		if($_GET['a'] == 'creport_change_profile') {
			
			if (isset ($_POST['id']))
				$id = $_POST['id'];
			
			if (isset ($_POST['author']))
				$author = $_POST['author'];
			
			$this->creportProfile = $id;
			$this->creportAuthor = $author;
			
			$this->displaySettingsProfiles();
		}
		
		// preimenujemo profil
		if($_GET['a'] == 'renameProfile') {
			
			if (isset ($_POST['id']))
				$id = $_POST['id'];
			if (isset ($_POST['name']))
				$name = $_POST['name'];
			
			
			// Default profil shranimo drugam ker ga ni v bazi (prevec komplikacij ker je bilo to naknadno delano)
			if($id == 0){
				$what = 'creport_default_profile_name';
				$sql = sisplet_query("INSERT INTO srv_user_setting_for_survey (sid, uid, what, value) VALUES ('$this->ank_id', '$global_user_id', '$what', '$name') ON DUPLICATE KEY UPDATE value='$name'");
			}
			else
				$sql = sisplet_query("UPDATE srv_custom_report_profiles SET name='$name' WHERE id='$id'");				
			
			$this->displaySettingsProfiles();
		}
		
		// pobrisemo profil
		if($_GET['a'] == 'deleteProfile') {
			
			if (isset ($_POST['id']))
				$id = $_POST['id'];
			
			$sql = sisplet_query("DELETE FROM srv_custom_report_profiles WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id' AND id='$id'");
			
			$sql = sisplet_query("DELETE FROM srv_custom_report WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id' AND profile='$id'");
			
			$this->displaySettingsProfiles();
		}
		
		// shranimo kot nov profil
		if($_GET['a'] == 'newProfile') {
			
			if (isset ($_POST['name']))
				$name = $_POST['name'];
			if (isset ($_POST['comment']))
				$comment = $_POST['comment'];
			
			$sql = sisplet_query("INSERT INTO srv_custom_report_profiles (ank_id, usr_id, name, time_created) VALUES('$this->ank_id', '$global_user_id', '$name', NOW())");
			$profile_id = mysqli_insert_id($GLOBALS['connect_db']);
			
			SurveyUserSetting :: getInstance()->saveSettings('default_creport_profile', $profile_id);
			$this->creportProfile = $profile_id;
			
			SurveyUserSetting :: getInstance()->saveSettings('default_creport_author', $global_user_id);
			$this->creportAuthor = $global_user_id;
					
			// Dodamo se komentar porocila
			$what = 'creport_comment_profile_'.$this->creportProfile;			
			if($comment != ''){
				$s = sisplet_query("INSERT INTO srv_user_setting_for_survey (sid, uid, what, value) VALUES ('$this->ank_id', '$global_user_id', '$what', '$comment') ON DUPLICATE KEY UPDATE value='$comment'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			else{
				$s = sisplet_query("DELETE FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$global_user_id' AND what='$what'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			
			//$this->displaySettingsProfiles();
		}
		
		// pozenemo izbran profil
		if($_GET['a'] == 'use_creport_profile') {
			
			if (isset ($_POST['id']))
				$id = $_POST['id'];
			
			if (isset ($_POST['author']))
				$author = $_POST['author'];
			
			SurveyUserSetting :: getInstance()->saveSettings('default_creport_profile', $id);
			SurveyUserSetting :: getInstance()->saveSettings('default_creport_author', $author);
			
			//$this->displayReport();
		}
		
		// urejanje komentarja profila
		if($_GET['a'] == 'edit_profile_comment') {
			
			$what = 'creport_comment_profile_'.$this->creportProfile;
			
			if($value != ''){
				$s = sisplet_query("INSERT INTO srv_user_setting_for_survey (sid, uid, what, value) VALUES ('$this->ank_id', '$global_user_id', '$what', '$value') ON DUPLICATE KEY UPDATE value='$value'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			else{
				$s = sisplet_query("DELETE FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$global_user_id' AND what='$what'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
		}
	
		// prikazemo deljenje profila z drugimi uredniki
		if($_GET['a'] == 'shareProfileShow') {
			
			echo '<span class="bold clr" style="display:block; margin-bottom:25px;">'.$lang['srv_custom_report_share_long'].':</span>'."\n";
			
			if (isset ($_POST['id'])){
				$id = $_POST['id'];
				
				// Loop cez vse urednike ankete z dostopom do analiz
				$d = new Dostop();
				$users = $d->getUsersDostop();
				foreach($users as $user){
					
					if($user['id'] != $global_user_id){
						$sql = sisplet_query("SELECT * FROM srv_custom_report_share WHERE ank_id='".$this->ank_id."' AND profile_id='".$id."' AND author_usr_id='".$global_user_id."' AND share_usr_id='".$user['id']."' LIMIT 1");
						$checked = (mysqli_num_rows($sql) > 0) ? ' checked="checked"' : '';
						
						echo '<input type="checkbox" '.$checked.' name="share_usr_id[]" id="share_usr_id'.$user['id'].'" value="'.$user['id'].'"> ';
						echo '<label for="share_usr_id'.$user['id'].'">'.$user['email'].'</label><br />';
					}
				}	  
			}
			
			echo '<input id="shareCReportProfileId" type="hidden" value="' . $id . '"  />'."\n";
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="creport_profile_action(\'share\'); return false;"><span>'.$lang['srv_custom_report_share'].'</span></a></span></span>'."\n";            
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="creport_profile_action(\'cancel_share\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
		}
	
		// delimo profil z drugimi uredniki
		if($_GET['a'] == 'shareProfile') {
			
			if (isset ($_POST['id']) && isset ($_POST['users'])){
				$id = $_POST['id'];
				$users = $_POST['users'];
				
				// Dodamo dostop ostalim urednikom za to porocilo
				foreach($users as $user_id){
					$sql = sisplet_query("INSERT INTO srv_custom_report_share 
											(ank_id, profile_id, author_usr_id, share_usr_id) 
											VALUES 
											('".$this->ank_id."', '".$id."', '".$global_user_id."', '".$user_id."')");	
				}
				
				// Pobrisemo dostop neoznacenim urednikom do tega porocila
				$sql = sisplet_query("DELETE FROM srv_custom_report_share 
										WHERE ank_id='".$this->ank_id."' AND profile_id='".$id."' AND author_usr_id='".$global_user_id."' AND share_usr_id NOT IN (".implode(',', $users).")");	
			}
			elseif(isset($_POST['id'])){
				$id = $_POST['id'];
				
				// Pobrisemo dostop vsem urednikom do tega porocila
				$sql = sisplet_query("DELETE FROM srv_custom_report_share 
										WHERE ank_id='".$this->ank_id."' AND profile_id='".$id."' AND author_usr_id='".$global_user_id."'");	
			}

			$this->displaySettingsProfiles();
		}
	}
}
?>