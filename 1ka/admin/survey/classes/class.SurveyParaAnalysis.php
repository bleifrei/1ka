<?php

class SurveyParaAnalysis{

	private $anketa;										# id ankete
	private $db_table;									# katere tabele uporabljamo
	public $_HEADERS = array();							# shranimo podatke vseh variabel

	private $headFileName = null;						# pot do header fajla
	private $dataFileName = null;						# pot do data fajla
	private $dataFileStatus = null;						# status data datoteke
	private $SDF = null;								# class za inkrementalno dodajanje fajlov

	public $variablesList = null; 					 	# Seznam vseh variabel nad katerimi lahko izvajamo (zakeširamo)

	public $_CURRENT_STATUS_FILTER = ''; 				# filter po statusih, privzeto izvažamo 6 in 5
	public $_PROFILE_ID_VARIABLE = ''; 					# filter po statusih, privzeto izvažamo 6 in 5

	public $_HAS_TEST_DATA = false;						# ali anketa vsebuje testne podatke

	private $sessionData;								# podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...


	private $decimal_point = ',';
	private $thousands = '.';
	private $num_digit_average = NUM_DIGIT_AVERAGE;
	private $num_digit_percent = NUM_DIGIT_PERCENT;
	private $_missings = array();
	private $_unsets = array();

	private $cols_with_value = array();					# kateri stolpci imajo vrednosti
	private $show_with_zero = false;						# Ali prikazujemo stolpce z vrednostmi 0
	private $show_with_other = true;					# Ali prikazujemo vrstice "Drugo"

	
	private $show_question_basic = true;				# Ali prikazujemo vprašanja v osnovnem načinu
	private $show_graph_basic = true;					# Ali prikazujemo graf v osnovnem načinu
	private $show_question_breaks = true;				# Ali prikazujemo vprašanja v prekinitvak
	private $show_graph_breaks = true;					# Ali prikazujemo graf v prekinitvah
	private $show_question_advanced = false;			# Ali prikazujemo vprašanja v naprednem načinu
	private $show_graph_advanced = false;				# Ali prikazujemo graf v naprednem načinu
	private $show_graph_breaks_type = 0;				# Kareri graf prikazujemo 0=>SP, 1=>SP bruto, 2=>SP neto
	
	private $show_categories = true;					# Ali prikazujemo kategorične spremenljivke
	private $show_numbers = true;						# Ali prikazujemo numeričnespremenljivke
	private $show_text = true;							# Ali prikazujemo textovne spremenljivke

	private $spr_type;
	
	function __construct($anketa) 
	{
		if ((int)$anketa > 0) 
		{
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
            $this->SDF = SurveyDataFile::get_instance();
            $this->SDF->init($this->anketa);           
            $this->SDF->prepareFiles();  

            $this->headFileName = $this->SDF->getHeaderFileName();
            $this->dataFileName = $this->SDF->getDataFileName();
            $this->dataFileStatus = $this->SDF->getStatus();


			# Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->anketa);
			SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);
			SurveyConditionProfiles :: Init($this->anketa, $global_user_id);
			SurveyTimeProfiles :: Init($this->anketa, $global_user_id);
			SurveyVariablesProfiles :: Init($this->anketa, $global_user_id);
			SurveyDataSettingProfiles :: Init($this->anketa);
			# polovimo decimalna mesta in vejice za tisočice
			$this->decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
			$this->thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			$this->num_digit_average = SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE');
			$this->num_digit_percent = SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT');

			$this->spr_type = SurveyDataSettingProfiles::$spr_type;
			
			// preberemo nastavitve iz baze (prej v sessionu)
			SurveyUserSession::Init($this->anketa);
			$this->sessionData = SurveyUserSession::getData();


			if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE 
					|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED)
			{
				Common::noDataAlert();
				exit();
			}

			if ($this->headFileName !== null && $this->headFileName != '') 
			{
				$this->_HEADERS = unserialize(file_get_contents($this->headFileName));
			}

			# nastavimo vse filtre
			$this->setUpFilter();

			# nastavimo filtre uporabnika
			$this->setUserFilters();

		} 
		else 
		{
			echo 'Invalid Survey ID!';
			exit();
		}
	}
	
	/** Funkcija ki nastavi vse filtre
	 *
	 */
	private function setUpFilter() 
	{
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA
				|| $this->dataFileStatus == FILE_STATUS_NO_FILE
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED)
		{
			return false;
		}

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

	private function setUserFilters()
	{
		# Nastavimo filtre variabel
		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		if ($dvp != $_currentVariableProfile) {
			SurveyUserSetting :: getInstance()->saveSettings('default_variable_profile', $_currentVariableProfile);
		}
		$this->_PROFILE_ID_VARIABLE = $_currentVariableProfile;

		# ali prikazujemo tudi stolpce z 0 vrednostmi
		if (isset($this->sessionData['para_analysis']['show_with_zero'])) {
			$this->show_with_zero = $this->sessionData['para_analysis']['show_with_zero'];
		} 

		# ali prikazujemo vrstice "Drugo"
		if (isset($this->sessionData['para_analysis']['show_with_other'])) {
			$this->show_with_other = $this->sessionData['para_analysis']['show_with_other'];
		}
/*
		# ali prikazujemo vrstice tipa "besedilo"
		if (isset($this->sessionData['para_analysis']['show_with_text'])) {
			$this->show_with_text = $this->sessionData['para_analysis']['show_with_text'];
		}
*/
	 
		# Ali prikazujemo vprašanja v osnovnem nacinu
		if (isset($this->sessionData['para_analysis']['show_question_basic'])) {
			$this->show_question_basic = $this->sessionData['para_analysis']['show_question_basic'];
		}
		# Ali prikazujemo vprašanja v preinitvah
		if (isset($this->sessionData['para_analysis']['show_question_breaks'])) {
			$this->show_question_breaks = $this->sessionData['para_analysis']['show_question_breaks'];
		}
		# Ali prikazujemo vprašanja v naprednem nacinu
		if (isset($this->sessionData['para_analysis']['show_question_advanced'])) {
			$this->show_question_advanced = $this->sessionData['para_analysis']['show_question_advanced'];
		}
		# Ali prikazujemo graf v osnovnem nacinu
		if (isset($this->sessionData['para_analysis']['show_graph_basic'])) {
			$this->show_graph_basic = $this->sessionData['para_analysis']['show_graph_basic'];
		}
		# Ali prikazujemo graf v prekinitvah
		if (isset($this->sessionData['para_analysis']['show_graph_breaks'])) {
			$this->show_graph_breaks = $this->sessionData['para_analysis']['show_graph_breaks'];
		}
		# Ali prikazujemo graf v naprednem nacinu
		if (isset($this->sessionData['para_analysis']['show_graph_advanced'])) {
			$this->show_graph_advanced = $this->sessionData['para_analysis']['show_graph_advanced'];
		}		
		# Kateri tip grafa prikazujemo v prekinitvah 
		if (isset($this->sessionData['para_analysis']['show_graph_breaks_type']) && in_array((int)$this->sessionData['para_analysis']['show_graph_breaks_type'], array(0,1,2))) {
			$this->show_graph_breaks_type = $this->sessionData['para_analysis']['show_graph_breaks_type'];
		}

		# Ali prikazujemo kategorije, numerične, besedila
		if (isset($this->sessionData['para_analysis']['show_categories'])) {
			$this->show_categories = $this->sessionData['para_analysis']['show_categories'];
		}		
		if (isset($this->sessionData['para_analysis']['show_numbers'])) {
			$this->show_numbers = $this->sessionData['para_analysis']['show_numbers'];
		}		
		if (isset($this->sessionData['para_analysis']['show_text'])) {
			$this->show_text = $this->sessionData['para_analysis']['show_text'];
		}		

	}

	function Display() {
		global $lang;
		
				
		# ali imamo testne podatke
		if ($this->_HAS_TEST_DATA){
            # izrišemo bar za testne podatke
            $SSH = new SurveyStaticHtml($this->anketa);
			$SSH -> displayTestDataBar(true);
		}
		
		//$this->DisplayLinks();

		echo '<div id="displayFilterNotes">';
		# če imamo filter zoom ga izpišemo
		SurveyZoom::getConditionString();
		# če imamo filter ifov ga izpišemo
		SurveyConditionProfiles:: getConditionString();
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		SurveyTimeProfiles :: printIsDefaultProfile();
		# če imamo filter spremenljivk ga izpišemo
		SurveyVariablesProfiles:: getProfileString();
		
		SurveyDataSettingProfiles :: getVariableTypeNote($doNewLine );
		echo '</div>';
		
		echo '<div class="clr">&nbsp;</div>';
					
		
		$this->displayData();
	}
	
	
	function DisplayGraph() 
	{
		global $lang;
		$paraType = 'basic';
		
		if (isset($_GET['m']) && $_GET['m'] == 'advanced') {
			$paraType = 'advanced';
		}
		if (isset($_GET['m']) && $_GET['m'] == 'breaks') {
			$paraType = 'breaks';
		}
		
		$showSettings = false;
		if (isset($_SESSION['sid_'.$this->anketa]['paraAnalysisGraph_settings']) && $_SESSION['sid_'.$this->anketa]['paraAnalysisGraph_settings'] == true) {

			$showSettings = true;
		}
		
		echo '<div id="dataSettingsCheckboxes" class="paraAnalysisGraph" '.($showSettings ? '' : ' style="display:none;"').'>';
		echo '<div id="toggleDataCheckboxes2" onClick="toggleDataCheckboxes(\'paraAnalysisGraph\');"><span class="faicon close icon-orange" style="padding-bottom:2px;"></span> '.$lang['srv_para_close_settings'].'</div>';
		
		echo '<table id="para_settings"><tr><th>Spremenljivke</th><th rowspan="2" class="anl_bl spacer"></th><th>Prekinitve</th><th rowspan="2" class="anl_bl spacer"></th><th>Podrobno</th></tr>';
		echo '<tr><td>';
		# spremenljivke
		echo '<div class="floatLeft">';
		echo '<label>';
		echo '<input type="checkbox" id="show_question_basic" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_question_basic == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_para_show_question_basic'];
		echo '</label>';
		echo '<br/>';
		echo '<label>';
		echo '<input type="checkbox" id="show_graph_basic" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_graph_basic == true ? ' checked="checked"' : '').' autocomplete="off" >';
		echo $lang['srv_para_show_show_graph_basic'];
		echo '</label>';
		echo '</div>';
		# end:spremenljivke
		echo '</td><td>';
		# prekinitve
		echo '<div class="floatLeft">';
		echo '<label>';
		echo '<input type="checkbox" id="show_question_breaks" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_question_breaks == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_para_show_question_breaks'];
		echo '</label>';
		echo '<br/>';
		echo '<label>';
		echo '<input type="checkbox" id="show_graph_breaks" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_graph_breaks == true ? ' checked="checked"' : '').' autocomplete="off" >';
		echo $lang['srv_para_show_show_graph_breaks'];
		echo '</label>';
		echo '<br/>';
		echo '<label>'.$lang['srv_para_graph_type'].':';
		echo '<select id="show_graph_breaks_type" onchange="changeParaAnalysisSelect(this);" autocomplete="off">';
		echo '<option value="0"'.((int)$this->show_graph_breaks_type == 0 ? ' selected="selected"' : '').'>'.$lang['srv_para_graph_type0'].'</option>';
		echo '<option value="1"'.((int)$this->show_graph_breaks_type == 1 ? ' selected="selected"' : '').'>'.$lang['srv_para_graph_type1'].'</option>';
		echo '<option value="2"'.((int)$this->show_graph_breaks_type == 2 ? ' selected="selected"' : '').'>'.$lang['srv_para_graph_type2'].'</option>';
		echo '</select>';
		echo '</label>';
		echo '</div>';
		
		# end: prekinitve
		echo '</td><td>';
		# podrobno
		echo '<div class="floatLeft">';
		echo '<label>';
		echo '<input type="checkbox" id="show_with_zero" onclick="changeParaAnalysisCbx(this,true);" '.($this->show_with_zero == false ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_para_only_valid'];
		echo '</label>';
		echo '<br/>';
		echo '<label>';
		echo '<input type="checkbox" id="show_with_other" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_with_other == true ? ' checked="checked"' : '').' autocomplete="off" >';
		echo $lang['srv_para_show_rows_other'];
		echo '</label>';
		echo '</div>';
		echo '<div class="floatLeft spaceLeftBig anl_bl">';
		echo '<label>';
		echo '<input type="checkbox" id="show_categories" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_categories == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_analiza_kategorialneSpremenljivke'];
		echo '</label>';
		echo '<br/>';
		echo '<label>';
		echo '<input type="checkbox" id="show_numbers" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_numbers == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_analiza_numericneSpremenljivke'];
		echo '</label>';
		echo '<br/>';
		echo '<label>';
		echo '<input type="checkbox" id="show_text" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_text == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_analiza_textovneSpremenljivke'];
		echo '</label>';
		echo '</div>';
			
		echo '<div class="floatLeft spaceLeftBig">';
		echo '<label>';
		echo '<input type="checkbox" id="show_question_advanced" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_question_advanced == true ? ' checked="checked"' : '').' autocomplete="off">';
		echo $lang['srv_para_show_question_advanced'];
		echo '</label>';
		echo '<br/>';
		echo '<label>';
		echo '<input type="checkbox" id="show_graph_advanced" onclick="changeParaAnalysisCbx(this,false);" '.($this->show_graph_advanced == true ? ' checked="checked"' : '').' autocomplete="off" >';
		echo $lang['srv_para_show_show_graph_advanced'];
		echo '</label>';
		echo '</div>';
		
		# end:podrobno
		echo '</td></tr></table>';
		
		if ($paraType == 'basic') {
		}
		if ($paraType == 'advanced') {
		}
		
		echo '<div class="clr"></div>';
		
		echo '</div>'; // konec diva zapiranje nastavitev
		

		echo '<div id="div_para_data">';
		
		echo '<div id="displayFilterNotes">';
		# če imamo filter ifov ga izpišemo
		SurveyConditionProfiles:: getConditionString();
		# če imamo filter spremenljivk ga izpišemo
		SurveyVariablesProfiles:: getProfileString();
		echo '</div>';
		

		if ($paraType ==  'basic') {
			$this->displayGraphDataBasic();
		}
		if ($paraType ==  'advanced') {
			$this->displayGraphDataAdvanced();
		} 
		if ($paraType ==  'breaks') {
			$this->displayGraphDataBreaks();
		} 
		echo '</div>'; #id="div_pra_data"

	}

		
	function DisplayLinks() 
	{
		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->anketa);
		$SSH -> displayAnalizaSubNavigation();
	}
	

	/** Prikazuje filtre
	 *
	 */
	function DisplayFilters() 
	{
		global $lang;

		if ($this->dataFileStatus == FILE_STATUS_SRV_DELETED || $this->dataFileStatus == FILE_STATUS_NO_DATA)
		{
			return false;
		}

		if ($this->setUpJSAnaliza == true) 
		{
			echo '<script>
			window.onload = function() {
			__analiza = 1;
			}
			</script>';
		}

		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->anketa);
		# izrišemo desne linke do posameznih nastavitev
		$SSH -> displayAnalizaRightOptions(M_ANALYSIS_PARA);
		
	}
	
	function displayGraphDataBreaks() {
		global $lang;
		$pageUrl = "index.php?anketa=" . $this->anketa . "&a=nonresponse_graph&m=breaks";
		$showGraph = $this->show_graph_breaks;
		$showQuestion = $this->show_question_breaks;
		$showGraphType = $this->show_graph_breaks_type;
		
		// prestejemo vprasanja, če jih je več kot 1 ne moreš na glasovanja
		$sqlQ = sisplet_query("SELECT g.id as g_id, g.naslov, s.id as spr_id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red, s.vrstni_red");

		
		$pages = array();
		$sprPage = array();
		while ($row = mysqli_fetch_assoc($sqlQ)) {
			$sprPage[$row['spr_id']] = $row['g_id'];
			if (!isset($pages[$row['g_id']])) {
				$pages[$row['g_id']] = $row['naslov']; 
			}
		}
		$headerVariablesId = $this->getVariables();
		
		list($data, $show_delta) = $this->collectData();
		
		$cnt_missing = 0;
		$cnt_undefined = 0;
		$valid = SurveyStatusProfiles::getAllValidCount();
		$all = SurveyStatusProfiles::getAllUserCount();
		
		$percent_all = SurveyStatusProfiles::getActiveProfileUserCount();
		$sspid = SurveyStatusProfiles::getCurentProfileId();
		$isNotStandardProfile = ($sspid != 1 && $sspid != 2);
		$added_colspan = 5+1*$isNotStandardProfile;
		$showPercent = true;
		
		// sort bo treba dodelat.. glede na to da se ns, ns_neto, ns_bruto izmenično prikazujejo v grafu
/*
		$sort_type = SORT_ASC;
		if (isset($_REQUEST['sort_type']) 
				&& ((int)$_REQUEST['sort_type'] == SORT_DESC || (int)$_REQUEST['sort_type'] == SORT_ASC)) {
			$sort_type = (int)$_REQUEST['sort_type'];
		}
		if ($sort_type != SORT_DESC) {
			$sort_type = SORT_ASC;
		}
		$sort_type_sprite = $sort_type == SORT_ASC ? 'sort_ascending' : 'sort_descending';
		
		$sort_field = 'variable';
		if (isset($_REQUEST['sort'])) {
			$sort_field = $_REQUEST['sort'];
		}
		
		if ($sort_field != 'ns') {
			$sort_field = 'variable';
		}
		
		// če ni po spremenljivki
		if ($sort_field != 'variable') {
			#do sort
			#sort_seq=10&sort_type=sort_asc
			$sort = array();
			foreach ($rows as $key => $row) {
				if ($sort_field == 'ns') {
					$sort[$key] = $row['ns'];
				}  else {
					$sort[$key] = $row['variable'];
				}
			}
			
			array_multisort($sort, $sort_type, $rows);
		} else {
			
			if ($sort_type != SORT_ASC ) {
				$rows = array_reverse($rows);
			}
			
		}
		
		// če imamo privzeto sortiranje izrisujemo še strani
		$showPages = false;
		if (count($pages) > 1 && $sort_field == 'variable') {
			if ($sort_type == SORT_ASC) {
				
			} else {
				$sprPage = array_reverse($sprPage, true);
			}
			$showPages = true; 
		}
*/
		// ko bo rešen sort odstrani še tole spodaj
		$showPages = true;
		
		$this->displayFormula();
		
		$rows = $data;

		$baseColspan = 6;
		
		echo '<table id="tbl_para_analitics" class="graph'.($showGraph ? ' showGraph' : '').'">';
		echo '<tr class="persist-header">';
		echo '<th class="anl_w50 pointer" ';
		if ($sort_field == 'variable') {
			if ($sort_type == SORT_DESC) {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=v&sort_type=" . SORT_ASC . "')\"";
			} else {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=v&sort_type=" . SORT_DESC . "')\"";
			}
		} else {
			echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=v&sort_type=" . SORT_ASC . "')\"";
		}
		echo '>';
		echo '<span class="floatLeft pointer">' . $lang['srv_para_variable'] . '</span>';
		if ($sort_field == 'variable') {
			echo '<span class="floatRight faicon '.$sort_type_sprite.'">&nbsp;</span>';
		}
		echo'</th>';
		if ($showQuestion) {
			echo '<th class="anl_w200" >'.$lang['srv_para_question'].'</th>';
		}
		echo '<th class="anl_w50" title="'.$lang['srv_para_breaks'].'">'.$lang['srv_para_breaks_short'].'</th>';
		if ($show_delta) {
			echo '<th class="anl_w50" title="'.$lang['srv_para_breaks_delta'].'">'.$lang['srv_para_breaks_delta_short'].'</th>';
		}

/*
 * 		echo '<th class="anl_w70" title="'.$lang['srv_para_breaks_value'].'"';
		if ($sort_field == 'ns') {
			if ($sort_type == SORT_DESC) {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=ns&sort_type=" . SORT_ASC . "')\"";
			} else {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=ns&sort_type=" . SORT_DESC . "')\"";
			}
		} else {
			echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=ns&sort_type=" . SORT_ASC . "')\"";
		}
		
		echo '>';
		echo '<span>' . $lang['srv_para_breaks_short']. Help :: display('srv_item_nonresponse') . '</span>';
		if ($sort_field == 'ns') {
			echo '<span class="floatRight faicon '.$sort_type_sprite.'">&nbsp;</span>';
		}
*/
		echo '<th class="anl_w50" title="'.$lang['srv_para_breaks_value'].'">';
		echo '<span>' . $lang['srv_para_breaks_value_short'].  '</span>';
		echo '</th>';
		echo '<th class="anl_w50" title="'.$lang['srv_para_breaks_value_bruto'].'">';
		echo '<span>' . $lang['srv_para_breaks_value_bruto_short'].'</span>';
		echo '</th>';
		echo '<th class="anl_w50" title="'.$lang['srv_para_breaks_value_neto'].'">';
		echo '<span>' . $lang['srv_para_breaks_value_neto_short'].'</span>';
		echo '</th>';
#		echo '<th class="anl_w70 pointer" title="'.$lang['srv_para_breaks_value_bruto'].'">';
#		echo '<span>' . $lang['srv_para_breaks_value_bruto_short'].  '</span>';
#		echo '</th>';
#		echo '<th class="anl_w75 pointer" title="'.$lang['srv_para_breaks_value_neto'].'">';
#		echo '<span>' . $lang['srv_para_breaks_value_neto_short'].  '</span>';
#		echo '</th>';
		if ($showGraph) {
			echo '<th class="anl_bb" title="' . $lang['srv_para_breaks_graph_title'] . '">' . $lang['srv_para_breaks_graph_title'] . '</th>';
		}
		echo '</tr>';

		if ($showPages) {
			$oldPage = reset($sprPage);
			$newPage = $oldPage;
			echo '<tr>';
			echo '<td colspan="' . ($baseColspan + (int)$showQuestion + (int)$showGraph). '" class=" para_page_break">
			<span>' . $pages[$newPage] . '</span></td>';
			//echo '<td class="empty_cell"></td>';
			echo '</tr>';
			
		}		
		foreach ($rows AS $row) {
			if ($showPages) {
				$newPage = $sprPage[$row['sid']];
				if ($newPage != $oldPage) {
					$oldPage = $newPage;
					echo '<tr>';
					echo '<td colspan="' . ($baseColspan + (int)$showQuestion + (int)$showGraph). '" class=" para_page_break">
					<span>' . $pages[$newPage] . '</span></td>';
					//echo '<td class="empty_cell"></td>';
					echo '</tr>';
				}
			}
				
			echo '<tr class="'.$css_sublcass.'">';
			echo '<td >';
			echo '<span class="anl_variabla">';
			echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $row['spid'] . '\'); return false;" href="#">' . $row['variable'] . '</a>';
			echo '</span>';
			echo '</td>';
			if ($showQuestion) {
				echo '<td>';
				echo (strlen($row['naslov']) > 40 ? substr($row['naslov'],0,40)."..." : $row['naslov']);
				echo '</td>';
			}
			$val = 0;
			if (isset($row['values'][-3])) {
				$val = $row['values'][-3];
			}
			echo '<td>'.$val.'</td>';
			if ($show_delta) {
				echo '<td>'.$row['delta'].'</td>';
			}
			echo '<td>'.common::formatNumber($row['sp']*100,1).'</td>';
			echo '<td>'.common::formatNumber($row['sp_bruto']*100,1).'</td>';
			echo '<td>'.common::formatNumber($row['sp_neto']*100,1).'</td>';

			#echo '<td>'.common::formatNumber($value,2).'</td>';
			if ($showGraph) {
				$width = $row['sp'];
				if ((int)$showGraphType == 1) {
					$width = $row['sp_bruto'];
				} elseif ((int)$showGraphType == 2) {
					$width = $row['sp_neto'];
				} 
				
				echo '<td class="empty_cell">';
				if ($row['sp'] > 0) {
					echo '<div class="para_analitics_bar" style="'.'width:'.($width*100).'%; text-align:right; padding-right:5px; color:green;"></div>';
				} else {
					echo '<div class="para_analitics_bar null_value" style="'.'width:1px"></div>';
				}
				echo '</td>';
			}
			echo '</tr>';
			
		}
		echo '</table>';
		#SurveyAnalysisHelper::getInstance()->displayMissingLegend();

		$this->displayLink();
		echo '<br class="clr" />';
	}
	
	
	function displayGraphDataBasic() {
		global $lang;
		
		$pageUrl = "index.php?anketa=" . $this->anketa . "&a=nonresponse_graph";
		
		$showGraph = $this->show_graph_basic;
		$showQuestion = $this->show_question_basic;
		
		
		$headerVariablesId = $this->getVariables();
		
		list($data, $show_delta) = $this->collectData();
		// delta naj se ne prikazuje pri Spremenljivke
		$show_delta = false;
		
		$cnt_missing = 0;
		$cnt_undefined = 0;
		$valid = SurveyStatusProfiles::getAllValidCount();
		$all = SurveyStatusProfiles::getAllUserCount();
		
		$percent_all = SurveyStatusProfiles::getActiveProfileUserCount();
		$sspid = SurveyStatusProfiles::getCurentProfileId();
		$isNotStandardProfile = ($sspid != 1 && $sspid != 2);
		$added_colspan = 5+1*$isNotStandardProfile;
		$showPercent = true;

		$rows = $data;
		
		/*
		# pripravimo podatke
		$rows = array();
		foreach ($headerVariablesId AS $headerVariableId) {
			$spr = $this->_HEADERS[$headerVariableId];
			if (empty($spr['grids'])) {
				continue;
			}
			$grids = $spr['grids'];
			foreach ($grids AS $gid => $grid) {

				$variables = $grid['variables'];
				if (empty($variables)) {
					continue;
				}
				foreach ($variables AS $vid => $variable) {
					$data_seq = array();
					$tip = $spr['tip'];
					$seq = $variable['sequence'];
						
					if (!isset($data[$seq])) {
						continue;
					} 
						
					$data_seq = $data[$seq];
					$_value = 0;
					$exposed = 0;
					
					if (isset($data_seq[-1]) && (int)$data_seq[-1] > 0) {
						$valid = isset($data_seq['valid']) ? (int)$data_seq['valid'] : 0;
						// vsi nevsebinski -99, 98,97,96,95…
						$non_conceptual_sum = 0;
						foreach(array_keys($this->_unsets) AS $non_conceptual) {
							if (isset($data_seq[$non_conceptual])) {
								$non_conceptual_sum += $data_seq[$non_conceptual];
							}
						}
						$exposed = ($data_seq[-1] + $valid + $non_conceptual_sum);
						if ($exposed > 0 && $data_seq[-1] > 0) {
							$_value = $data_seq[-1] / $exposed ;
						}
					}
					
					$_variable = $variable['variable'];
					$_naslov = $variable['naslov'];
					if (count($variables) > 1  && in_array($tip, array(2,18,17))) {
						$_variable = $spr['variable'];
						$_naslov = $spr['naslov'];
					}
					if ($tip == 16 ) {
						$_variable = $grid['variable'];
						$_naslov = $grid['naslov'];
						
					}
						
					$row_data = array(
							't'=>$spr['tip'], 
							'v'=>$_variable, 
							'n'=>$_naslov, 
							'w'=>$_value, 
							'h'=>$headerVariableId, 
							'e' => $exposed,
							'd'=>$data_seq['delta']
							);
					$rows[] = $row_data; 
					if (in_array($tip, array(2,18,17)) || $tip == 16) {
						break;
					}
				}
			}
		}
		*/
		$sort_type = SORT_ASC;
		if (isset($_REQUEST['sort_type']) 
				&& ((int)$_REQUEST['sort_type'] == SORT_DESC || (int)$_REQUEST['sort_type'] == SORT_ASC)) {
			$sort_type = (int)$_REQUEST['sort_type'];
		}
		if ($sort_type != SORT_DESC) {
			$sort_type = SORT_ASC;
		}
		$sort_type_sprite = $sort_type == SORT_ASC ? 'sort_ascending' : 'sort_descending';
		
		$sort_field = 'variable';
		if (isset($_REQUEST['sort'])) {
			$sort_field = $_REQUEST['sort'];
		}
		
		if ($sort_field != 'ns') {
			$sort_field = 'variable';
		}
		
		// če ni po spremenljivki
		if ($sort_field != 'variable') {
			#do sort
			#sort_seq=10&sort_type=sort_asc
			$sort = array();
			foreach ($rows as $key => $row) {
				if ($sort_field == 'ns') {
					$sort[$key] = $row['ns'];
				}  else {
					$sort[$key] = $row['variable'];
				}
			}
			
			array_multisort($sort, $sort_type, $rows);
		} else {
			
			if ($sort_type != SORT_ASC ) {
				$rows = array_reverse($rows);
			}
			
		}
		
		$this->displayFormula();
		
		echo '<table id="tbl_para_analitics" class="graph'.($showGraph ? ' showGraph' : '').'">';
		echo '<tr class="persist-header">';
		echo '<th class="pointer" ';
		if ($sort_field == 'variable') {
			if ($sort_type == SORT_DESC) {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=variable&sort_type=" . SORT_ASC . "')\"";
			} else {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=variable&sort_type=" . SORT_DESC . "')\"";
			}
		} else {
			echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=variable&sort_type=" . SORT_ASC . "')\"";
		}
		echo '>';
		echo '<span class="floatLeft pointer">' . $lang['srv_para_variable'] . '</span>';
		if ($sort_field == 'variable') {
			echo '<span class="floatRight sprites '.$sort_type_sprite.'">&nbsp;</span>';
		}
		echo'</th>';
		if ($showQuestion) {
			echo '<th>'.$lang['srv_para_question'].'</th>';
		}
		if ($show_delta) {
			echo '<th class="anl_w50" title="'.$lang[''].'">delta</th>';
		}
		echo '<th class="anl_w50 pointer" title="'.$lang['srv_para_unaswered'].'"';
		if ($sort_field == 'ns') {
			if ($sort_type == SORT_DESC) {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=ns&sort_type=" . SORT_ASC . "')\"";
			} else {
				echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=ns&sort_type=" . SORT_DESC . "')\"";
			}
		} else {
			echo " onclick=\"window.location.assign('" . $pageUrl . "&sort=ns&sort_type=" . SORT_ASC . "')\"";
		}
		echo '>';
		echo '<span>' . $lang['srv_para_unaswered_short'] . ' %</span>'; // . Help :: display('srv_item_nonresponse')
		if ($sort_field == 'ns') {
			echo '<span class="floatRight sprites '.$sort_type_sprite.'">&nbsp;</span>';
		}
		echo '</th>';
		if ($showGraph) {
			echo '<th class="anl_bb" title="' . $lang['srv_para_unaswered_graph_title'] . '">' . $lang['srv_para_unaswered_graph_title'] . '</th>';
		}
		echo '</tr>';
		
		foreach ($rows AS $row) {
			echo '<tr class="'.$css_sublcass.'">';
			echo '<td >';
			echo '<span class="anl_variabla">';
			echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $row['spid'] . '\'); return false;" href="#">' . $row['variable'] . '</a>';
			echo '</span>';
			echo '</td>';
			if ($showQuestion) {
				echo '<td>';
				echo (strlen($row['naslov']) > 40 ? substr($row['naslov'],0,40)."..." : $row['naslov']);
				echo '</td>';
			}
			if ($show_delta) {
				echo '<td>'.$row['delta'].'</td>';
			}
			echo '<td>'.common::formatNumber($row['ns']*100,1).'</td>';
			#echo '<td>'.common::formatNumber($value,2).'</td>';
			if ($showGraph) {
				echo '<td class="empty_cell">';
				if ($row['ns'] > 0) {
					echo '<div class="para_analitics_bar" style="'.'width:'.($row['ns']*100).'%; text-align:right; padding-right:5px; color:green;"></div>';
				} else {
					echo '<div class="para_analitics_bar null_value" style="'.'width:1px"></div>';
				}
				echo '</td>';
			}
			echo '</tr>';
			
		}
		echo '</table>';
		#SurveyAnalysisHelper::getInstance()->displayMissingLegend();

		$this->displayLink();
		echo '<br class="clr" />';
	}
	
	function displayGraphDataAdvanced( ) {
		global $lang;
		global $admin_type;
		
		$showGraph = $this->show_graph_advanced;
		$showQuestion = $this->show_question_advanced;
		
		$headerVariablesId = $this->getVariables();

		list($data, $show_delta) = $this->collectData();
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

		$cnt_missing = 0;
		$cnt_undefined = 0;

		$valid = SurveyStatusProfiles::getAllValidCount();
		$all = SurveyStatusProfiles::getAllUserCount();

		$percent_all = SurveyStatusProfiles::getActiveProfileUserCount();
		$sspid = SurveyStatusProfiles::getCurentProfileId();
		$isNotStandardProfile = ($sspid != 1 && $sspid != 2);
		$added_colspan = 5+1+$show_delta+1*$isNotStandardProfile;
		$showPercent = true;
 
		
		$this->displayFormula();
		
		
		echo '<table id="tbl_para_analitics" class="persist-area">';
		echo '<tr class="persist-header">';
		
		// Dodatna stolpca zaradi izvoza za Katjo Lozar
		if($admin_type == '0'){
			echo '<th style="width:30px;">ID VPRASANJA</th>';
			echo '<th style="width:30px;">ID VARIABLE</th>';
		}
		
		echo '<th>'.$lang['srv_para_variable'].'</th>';
		if ($showQuestion) {
			echo '<th>'.$lang['srv_para_question'].'</th>';
		}
		echo '<th >'.$lang['srv_para_valid'].'</th>';
		foreach ($this->_missings AS $value => $text) {
			$cnt_miss++;
			echo "<th class=\"anl_w50\" title=\"".$lang['srv_mv_'.$text]."\" >{$value}<br/>(".$lang['srv_mv_'.$text].")</th>";
		}
		foreach ($this->_unsets AS $value => $text)
		{
			$cnt_undefined++;
			echo "<th class=\"anl_w50\" title=\"".$lang['srv_mv_'.$text]."\">{$value}<br/>(".$lang['srv_mv_'.$text].")</th>";
		}
		if ($show_delta) {
			echo "<th class=\"anl_w50\" title=\"".$lang['']."delta\">".$lang['']."&Delta;</th>";
		}
		echo "<th class=\"anl_w50\" title=\"".$lang['srv_para_nonconceptual']."\">".$lang['srv_para_nonconceptual']."</th>";
		echo "<th class=\"anl_w50\" title=\"".$lang['srv_para_approp']."\">".$lang['srv_para_approp']."</th>";
		echo "<th class=\"anl_w50\" title=\"".$lang['srv_para_all_units']."\">".$lang['srv_para_all_units']."</th>";
		if ($isNotStandardProfile)
		{
			echo "<th class=\"anl_w50\" title=\"".$lang['srv_para_status']."\">".$lang['srv_para_status']."</th>";
		}
		echo "<th class=\"anl_w50\" title=\"".$lang['srv_para_unaswered']."\">".$lang['srv_para_unaswered_short']. Help :: display('srv_item_nonresponse')."</th>";
		if ($showGraph) {
			echo '<th class="anl_bb">' . $lang['srv_para_unaswered_graph_title'] . '</th>';
		}

		echo '</tr>';
		foreach ($headerVariablesId AS $key => $headerVariableId)
		{
			$spr = $this->_HEADERS[$headerVariableId];
			# ali preskočimo kategorije, numerične, besedilo
			if (($this->show_categories == false && in_array($spr['tip'], $this->spr_type['showCategories']))
				|| ($this->show_numbers == false && in_array($spr['tip'], $this->spr_type['showNumbers']))
				|| ($this->show_text == false && in_array($spr['tip'], $this->spr_type['showText']))) {
			#		if ($this->show_with_text == false && in_array($spr['tip'], array(5, 4, 19, 21))) {
				continue;
			}

			if (!empty($spr['grids'])) {				
				$grids = $spr['grids'];
				if (count($grids) > 1) {
					# če imamo več grup dodamo header vrstico
					echo '<tr class="multiGroupHeader">';
					
					if($admin_type == '0'){
						echo '<td>'.$spr['spr_id'].'</td>';	
						echo '<td></td>';	
					}
					
					if ($showQuestion) {
						echo '<td class="showQuestion">';
						echo '<span class="anl_variabla">';
						echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $spr['variable'] . '</a>';
						echo '</span>';
						echo '</td>';
						echo '<td colspan="'.(count($this->_missings)+count($this->_unsets)+$added_colspan).'">';
						echo $spr['naslov'];
						echo '</td>';
					} else {
						echo '<td colspan="'.(count($this->_missings)+count($this->_unsets)+$added_colspan).'">';
						echo '<span class="anl_variabla">';
						echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $spr['variable'] . '</a>';
						echo '</span>';
						echo '</td>';
					}
					if ($showGraph) {
						echo '<td class="empty_cell30"></td>';
					}
					echo '</tr>';
				}
				else {
					$css_GridSublcass = '';
				}

				foreach ($grids AS $gid => $grid)
				{
					$variables = $grid['variables'];

					if (!empty($variables)) {
						if (count($variables) > 1 ) {
							
							# če imamo več variabel dodamo header vrstico, vrstice variabel pa obarvamo svetleje
							$css_sublcass = 'subVar';
							if (in_array($spr['tip'], array(2,18,17))) {	
								#razvššanje nima zapisa v grupi ampak preberemo iz spremenljivke
								
								echo '<tr class="multiGroupHeader">';
								
								if($admin_type == '0'){
									echo '<td>'.$spr['spr_id'].'</td>';	
									echo '<td></td>';	
								}
								
								if ($showQuestion) {
									echo '<td class="showQuestion">';
									echo '<span class="anl_variabla">';
									echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $spr['variable'] . '</a>';
									echo '</span>';
									echo '</td>';
									echo '<td colspan="'.(count($this->_missings)+count($this->_unsets)+$added_colspan ).'">';
									echo $spr['naslov'];
									echo '</td>';
								} else {
									echo '<td colspan="'.(count($this->_missings)+count($this->_unsets)+$added_colspan ).'">';
									echo '<span class="anl_variabla">';
									echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $spr['variable'] . '</a>';
									echo '</span>';
									echo '</td>';
								}
								if ($showGraph) {
									echo '<td class="empty_cell30"></td>';
								}
								echo '</tr>';

							}
							else
							{ #dodamo header za grupo
								echo '<tr class="multiVariablesHeader">';
								
								if($admin_type == '0'){
									echo '<td>'.$spr['spr_id'].'</td>';	
									echo '<td></td>';	
								}
								
								if ($showQuestion) {
									echo '<td class="showQuestion">';
									if (count($grids) > 1) {
										echo '<span class="anl_variabla">';
										echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $grid['variable'] . '</a>';
										echo '</span>';
									} else {
										echo '<span class="anl_variabla">';
										echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $spr['variable'] . '</a>';
										echo '</span>';
									}
									echo '</td>';
									echo '<td colspan="'.(count($this->_missings)+count($this->_unsets)+$added_colspan).'">';
									echo $grid['naslov'];
									echo '</td>';
								} else {
									echo '<td colspan="'.(count($this->_missings)+count($this->_unsets)+$added_colspan).'">';
									if (count($grids) > 1) {
										echo '<span class="anl_variabla">';
										echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $grid['variable'] . '</a>';
										echo '</span>';
									} else {
										echo '<span class="anl_variabla">';
										echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $spr['variable'] . '</a>';
										echo '</span>';
									}
									echo '</td>';
								}
								if ($showGraph) {
									echo '<td class="empty_cell30"></td>';
								}
								echo '</tr>';
							}
						}
						else {
							$css_sublcass = '';
						}
						foreach ($variables AS $vid => $variable)
						{
							// če ne prikazuemo polji "Drugo"
							if ($this->show_with_other == false &&  $variable['other']) {
								continue;
							}

							$seq = $variable['sequence'];
							$data_seq = $data[$seq];
/*
							$valid = isset($data_seq['valid']) ? (int)$data_seq['valid'] : 0;
							$nonresponse = 0;
							$exposed  = 0;
							$non_conceptual_sum = 0;
							if (isset($data_seq[-1]) && (int)$data_seq[-1] > 0) {
								// vsi nevsebinski -99, 98,97,96,95…
								$non_conceptual_sum = 0;
								foreach(array_keys($this->_unsets) AS $non_conceptual) {
									if (isset($data_seq[$non_conceptual])) {
										$non_conceptual_sum += $data_seq[$non_conceptual];
									}
								}
								$exposed = ($data_seq[-1] + $valid + $non_conceptual_sum);
								if ($data_seq[-1] > 0 && $exposed  > 0 ) {
									$nonresponse = $data_seq[-1] / $exposed;
								}
							}
							$delta  = $data_seq['delta'];
*/
							echo '<tr class="'.$css_sublcass.'">';
							
							if($admin_type == '0'){
								echo '<td rowspan="'.($showPercent*2).'">'.$spr['spr_id'].'</td>';	
								echo '<td rowspan="'.($showPercent*2).'">'.$variable['vr_id'].'</td>';	
							}
							
							//echo '<td style="border-top:0px none !important;border-bottom:0px none !important;">&nbsp;</td>';
								
							echo '<td rowspan="'.($showPercent*2).'">';
							echo '<span class="anl_variabla">';
							echo '<a onclick="showspremenljivkaSingleVarPopup(\'' . $key . '\'); return false;" href="#">' . $variable['variable'] . '</a>';
							echo '</span>';
							echo '</td>';
							if ($showQuestion) {
								echo '<td class="showQuestion" rowspan="'.($showPercent*2).'">';
								echo $variable['naslov'];
								echo '</td>';
							}
							echo '<td>';
							echo (int)$data_seq['veljavni'];
							echo '</td>';
							foreach ($this->_missings AS $value => $text) {
								$missing = 0;
								if (isset($data_seq['values'][$value])) {
									$missing = $data_seq['values'][$value];
								}
								echo '<td>'.(int)$missing.'</td>';
							}
							foreach ($this->_unsets AS $value => $text) {
								$unset = 0;
								if (isset($data_seq['values'][$value])) {
									$unset = $data_seq['values'][$value];
								}
								echo '<td>'.(int)$unset.'</td>';
							}
							if ($show_delta) {
								echo "<td rowspan=\"".($showPercent*2)."\">{$data_seq['delta']}</td>";
							}
							echo "<td rowspan=\"".($showPercent*2)."\">{$data_seq['prikazani']}</td>";
							echo "<td rowspan=\"".($showPercent*2)."\">{$data_seq['veljavni']}</td>";
							echo "<td rowspan=\"".($showPercent*2)."\">{$all}</td>";
							if ($isNotStandardProfile)
							{
								echo "<td rowspan=\"".($showPercent*2)."\">{$percent_all}</td>";
							}
							echo "<td rowspan=\"".($showPercent*2)."\">" . common::formatNumber($data_seq['ns'],2) . "</td>";
							if ($showGraph) {
								echo "<td  rowspan=\"".($showPercent*2)."\" class=\"empty_cell30\">";
								if ($data_seq['ns'] > 0) {
									echo '<div class="para_analitics_bar" style="'.'width:'.($data_seq['ns']*100).'%; text-align:right; padding-right:5px; color:green;"></div>';
								} else {
									echo '<div class="para_analitics_bar null_value" style="'.'width:1px"></div>';
								}
								echo '</td>';
							}
							echo '</tr>';

							if ($showPercent)
							{
								echo '<tr class="'.$css_sublcass.' percent">';
								echo '<td>';
								$val = 0;
								if ($percent_all > 0) {
									$val = $data[$seq]['veljavni'] / $percent_all * 100;
								}
								echo common::formatNumber($val,0,null,'%') ;
								echo '</td>';
								foreach ($this->_missings AS $value => $text) {
									$val = 0;
									if ($percent_all > 0 && isset($data[$seq]['values'][$value])) {
										$val = ($data[$seq]['values'][$value] / $percent_all * 100);
									}
									echo '<td>'.common::formatNumber($val,0,null,'%').'</td>';
								}
								foreach ($this->_unsets AS $value => $text)
								{
									$val = 0;
									if ($percent_all > 0 && isset($data[$seq]['values'][$value])) {
										$val = ($data[$seq]['values'][$value] / $percent_all * 100);
									}
									echo '<td>'.common::formatNumber($val,0,null,'%').'</td>';
								}
								echo '</tr>';
							}
						}
					}
				}
			}
		}
		echo '</table>';
		SurveyAnalysisHelper::getInstance()->displayMissingLegend();

		echo '<br class="clr" />';

	}
	
	function getVariables()
	{
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($this->_PROFILE_ID_VARIABLE );
		
		# če je $svp_pv = null potem prikazujemo vse variable
		# oziroma če je sistemski dodamo tudi vse, ker drugače lahko filter skrije telefon in email
		if (empty($tmp_svp_pv)) 
		{
			$_sv = $this->SDF->getSurveyVariables();
			if (count($_sv) > 0) 
			{
				foreach ( $_sv as $vid => $variable) 
				{
					$tmp_svp_pv[$vid] = $vid;
				}
			}
		}
		$svp_pv = array();
		if (!empty($tmp_svp_pv))
		{
			foreach ($tmp_svp_pv AS $_svp_pv) 
			{
				$svp_pv[$_svp_pv] = $_svp_pv;
			}
		}
		return $svp_pv;
	}
	
	public function collectData() {
		# polovimo 2 skupini podatkov 1. ustrezni (ko niso misisingi) 2. missinge)
		global $site_path;
		$folder = $site_path . EXPORT_FOLDER.'/';
		
		#array za imeni tmp fajlov, ki jih nato izbrišemo
		$tmp_file = $folder.'tmp_export_'.$this->anketa.'_para_data.php';

		# pobrišemo sfiltrirane podatke, ker jih več ne rabimo
		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
		
		$status_filter = $this->_CURRENT_STATUS_FILTER;

		# s katero sekvenco se začnejo podatki, da ne delamo po nepotrebnem za ostala polja
		$start_sequence = $this->_HEADERS['_settings']['dataSequence'];
		# s katero sekvenco se končajo podatki da ne delamo po nepotrebnem za ostala polja
		$end_sequence = $this->_HEADERS['_settings']['metaSequence']-1;
		
		# naredimo datoteko  z frekvencami
		# za windows sisteme in za linux sisteme
		if (IS_WINDOWS ) {
			# TEST z LINUX načinom
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" "BEGIN {{OFS=\"\x7C\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}" '.$this->dataFileName;
			$command .= ' | sed "s*\x27*`*g" ';
			$command .= ' | awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} { print \"$frequency[\",$1,\"]\",\"[\x27\",$2,\"\x27]\",\"=\",$3,\";\"}" >> '.$tmp_file;
		} else {
			# združimo v eno vrstico da bo strežnik bol srečen
			$command = 'awk -F"|" \'BEGIN {{OFS="|"} {ORS="\n"} {FS="|"} {SUBSEP="|"}} '.$status_filter.' {for (i='.$start_sequence.';i<='.$end_sequence.';i++) { arr[i,$i]++}} END {{for (n in arr) { print n,arr[n]}}}\' '.$this->dataFileName;
			$command .= ' | sed \'s*\x27*`*g\' ';
			$command .= ' | awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} { print "$frequency[",$1,"]","[\x27",$2,"\x27]","=\x27",$3,"\x27;"}\' >> '.$tmp_file;
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
	
		# zloopamo po frekvencah in polovimo pravilne
		$result = array();
		$hasDelta = false;
		
		$neodgovor_spremenljivke = array();
		$this->cols_with_value = array();
		if (empty($frequency)) { 
			return array('data'=>$result, 'hasDelta'=>$hasDelta);	
		}

		$old_break = 0;
		//loop skozi header variable da imamo sort po seq vprašanj
		$headerVariablesId = $this->getVariables();
		$delta = 0;
		
		foreach ($headerVariablesId AS $headerVariableId) {
			$spr = $this->_HEADERS[$headerVariableId];
			if (empty($spr['grids'])) {
				continue;
			}
			$grids = $spr['grids'];
			foreach ($grids AS $gid => $grid) {
				$variables = $grid['variables'];
				if (empty($variables)) {
					continue;
				}
				foreach ($variables AS $vid => $variable) {
					$seq = $variable['sequence'];
					if (!isset($frequency[$seq]) || empty($frequency[$seq])) {
						continue;
					}
					// loop skozi odgovore spremenljivke
					$freqData = $frequency[$seq];
					
					$vseEnote = 0;
					$veljavni = 0;
					$prikazani = 0;
					$nevsebinski = 0;
					$neodgovori = 0;
					$ustrezni = 0;
					$neustrezni = 0;
					$neodgovorSpremenljivke = 0;
					$stopnjaPrekinitve = 0;
					$stopnjaPrekinitveNeto = 0;
					$stopnjaPrekinitveBruto = 0;
					$delta = 0;			
					$values = array();		
					foreach ($freqData AS $key => $cnt) {

						$vseEnote += $cnt;
						if (is_numeric($key) && (isset($this->_missings[(int)$key]) || isset($this->_unsets[(int)$key]))) {
							// shranimo vrednosti -1 ... -99 za prikaz v tabeli podrobno
							$values[(int)$key] += $cnt;
							
							$this->cols_with_value[(int)$key] += $cnt;
							if (isset($this->_unsets[$key])) {

								$nevsebinski += $cnt;
							} elseif (isset($this->_missings[(int)$key])) {

								$neodgovori += $cnt;
								# -1 je bil prikazan pa ni bil odgovorjen
								if ($key == -1) {
									$prikazani += $cnt;
								}
								if ($key == -3) {
									$hasDelta = true;
									$delta = (int)$cnt - (int)$old_break;
									$old_break = $cnt;
								}
								if ($key == -5) {
									$neustrezni += $cnt;
								}
							}
						
						} else {

							$this->cols_with_value['valid'] += $cnt;
							$veljavni += $cnt;
						}
					}
					
					$prikazani += $nevsebinski + $veljavni;

					#neodgovorSpremenljivke so: -1  / PRIKAZANO  (veljavne + (-1)  -99.....-90)
					$ns = 0;
					if (isset($values[-1]) && $values[-1] > 0) {
						$ns = $values[-1] / ($prikazani); // -1 je v prikazanih
					}
					# prekinitve
					$ustrezni = $vseEnote;
					if (isset($values[-5])) {
						$ustrezni = $ustrezni - $values[-5];
					}
						
					$prekinitve = 0;
					if (isset($values[-3])) {
						$prekinitve = $values[-3];
					}
					
					#prekinitve so: -3  /(-3) +  PRIKAZANO  (veljavne + (-1)  -99.....-90)
					$sp = 0; 
					if (($prikazani + $prekinitve) > 0) {
						$sp = $prekinitve / ($prikazani + $prekinitve);
					}
						
					#neto so DELTA/(-3+PRIKAZANO)
					$neto_sp = 0;
					if (($prikazani + $prekinitve) > 0) {
						$neto_sp = $delta / ($prikazani + $prekinitve);
					}
						
					#bruto pa so (-3)/vse ustrezne enote (torej -3, -2, -1, -4, veljavni, -90...) vse razen empty
					$bruto_sp = 0;
					if ($ustrezni > 0) {
						$bruto_sp = $prekinitve / $ustrezni;
					}

					$_variable = $variable['variable'];
					$_naslov = $variable['naslov'];
					if (count($variables) > 1  && in_array($tip, array(2,18,17))) {
						$_variable = $spr['variable'];
						$_naslov = $spr['naslov'];
					}
					if ($tip == 16 ) {
						$_variable = $grid['variable'];
						$_naslov = $grid['naslov'];
					}
					
					$result[$seq]['spid'] = $headerVariableId;
					$result[$seq]['sid'] = $spr['spr_id'];
					$result[$seq]['variable'] = $_variable;
					$result[$seq]['naslov'] = $_naslov;
						
					$result[$seq]['vse_enote'] = $vseEnote;
					$result[$seq]['veljavni'] = $veljavni;
					$result[$seq]['prikazani'] = $prikazani;
					$result[$seq]['ustrezni'] = $ustrezni;
					#$result[$seq]['nevsebinski'] = $nevsebinski;
					#$result[$seq]['neodgovori'] = $neodgovori;
					$result[$seq]['delta'] = $delta;
					$result[$seq]['values'] = $values;

					$result[$seq]['ns'] = $ns ;
					$result[$seq]['sp'] = $sp ;
					$result[$seq]['sp_neto'] = $neto_sp ;
					$result[$seq]['sp_bruto'] = $bruto_sp ;
						
				}
			}
		}
		
		return array($result, $hasDelta);	
	}
	
	function ajax()
	{
		$action = $_GET['a'];
		switch ($action)
		{
			case 'setCbx' :
				$this->setCbx();
			break;
			case 'setValue' :
				$this->setValue();
			break;
		}
	}
	function setCbx() {
		$value = $_REQUEST['value'];
		if ($value == 'false') {
			$value = false;
		}
		if ($value == 'true') {
			$value = true;
		}
		
		$what = $_POST['what'];
		
		$this->sessionData['para_analysis'][$what] = $value;
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
	}
	function setValue() {
		$value = $_REQUEST['value'];
		$what = $_POST['what'];
		$this->sessionData['para_analysis'][$what] = $value;
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	function displayLink() {
#		global $lang;
#		echo '<a href="index.php?anketa=' . $this->anketa .'&a=analysis&m=para">' . $lang['srv_napredno'] . '</a>';
	} 
	function displayFormula() {
		return false;
		global $lang;
		echo '<table><tr><td rowspan="2">' . $lang['srv_para_unaswered'] . ' (' . $lang['srv_para_unaswered_short'] . ')' . ' = </td><td class="anl_bb anl_ac">(-1)</td></tr><tr><td class="anl_ac">(' . $lang['srv_para_valid'] . ') + (-1) + (-97) + (-98) + (-99)</td></tr><table>';
	}	 
}