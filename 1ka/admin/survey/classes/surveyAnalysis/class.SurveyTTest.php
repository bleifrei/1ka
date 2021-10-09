<?php
/** Class ki skrbi za T-test
 *  December 2011
 *
 *
 * Enter description here ...
 * @author Gorazd_Veselic
 *
 */

define("EXPORT_FOLDER", "admin/survey/SurveyData");
define("BC_PRECISION", 20);
define("R_FOLDER", "admin/survey/R");

class SurveyTTest
{
	private $sid;										# id ankete
	private $db_table;									# katere tabele uporabljamo
	public $_HEADERS = array();						# shranimo podatke vseh variabel

	private $headFileName = null;						# pot do header fajla
	private $dataFileName = null;						# pot do data fajla
	private $dataFileStatus = null;						# status data datoteke
	private $SDF = null;							# class za osnovne funkcije data fajla

	public $variablesList = null; 						# Seznam vseh variabel nad katerimi lahko izvajamo crostabulacije (zakeširamo)
	
	public $showChart = false; 						# ali prikazujemo graf pod tabelo
	
	private $sessionData;							# podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	

	function __construct($sid) {

		if ((int)$sid > 0) {
			$this->sid = $sid;

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->sid);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}

				
			# Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->sid);
			SurveyUserSetting::getInstance()->Init($this->sid, $global_user_id);

			SurveyStatusProfiles :: Init($this->sid);
			SurveyMissingProfiles :: Init($this->sid,$global_user_id);
			SurveyConditionProfiles :: Init($this->sid, $global_user_id);
			SurveyZankaProfiles :: Init($this->sid, $global_user_id);
			SurveyTimeProfiles :: Init($this->sid, $global_user_id);		
			SurveyVariablesProfiles :: Init($this->sid);

			SurveyDataSettingProfiles :: Init($this->sid);

			#inicializiramo class za datoteke
			$this->SDF = SurveyDataFile::get_instance();
			$this->SDF->init($this->sid);
			$this->headFileName = $this->SDF->getHeaderFileName();
			$this->dataFileName = $this->SDF->getDataFileName();
			$this->dataFileStatus = $this->SDF->getStatus();
				
			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->sid);
			$this->sessionData = SurveyUserSession::getData();				
				
			if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE || $this->dataFileStatus == FILE_STATUS_SRV_DELETED){
				Common::noDataAlert();
				exit();
			}
				
			if ($this->headFileName !== null && $this->headFileName != '') {
				$this->_HEADERS = unserialize(file_get_contents($this->headFileName));
			}
				
			# nastavimo vse filtre
			$this->setUpFilter();
				
		} else {
			echo 'Invalid Survey ID!';
			exit();
		}
	}

	function ajax() {

		if ( isset($_POST['spr2'])) {
			# če imamo novo spremenljivko, pobrišemo staro sejo
			if (isset($this->sessionData['ttest']['spr2']) && $this->sessionData['ttest']['spr2'] != $_POST['spr2']) {
				$this->sessionData['ttest'] = null;
				unset($this->sessionData['ttest']);
			}
			$this->sessionData['ttest']['spr2'] = $_POST['spr2'];
		}
		if ( isset($_POST['grid2'])) {
			$this->sessionData['ttest']['grid2'] = $_POST['grid2'];
		}
		if ( isset($_POST['seq2'])) {
			$this->sessionData['ttest']['seq2'] = $_POST['seq2'];
		}
		if ( isset($_POST['label2'])) {
			$this->sessionData['ttest']['label2'] = $_POST['label2'];
		}
		if ( isset($_POST['sub_conditions'])) {
			$this->sessionData['ttest']['sub_conditions'] = $_POST['sub_conditions'];
		}

		if ( isset($_POST['seq'])) {
			$i=0;
			if (count($_POST['seq']) > 0) {
				foreach ($_POST['seq'] AS $_seq1) {
					$this->sessionData['ttest']['variabla'][$i]['seq'] = $_seq1;
					$i++;
				}
			}
		}
		if ( isset($_POST['spr'])) {
			$i=0;
			if (count($_POST['spr']) > 0) {
				foreach ($_POST['spr'] AS $_spr1) {
					$this->sessionData['ttest']['variabla'][$i]['spr'] = $_spr1;
					$i++;
				}
			}
		}
		if ( isset($_POST['grd'])) {
			$i=0;
			if (count($_POST['grd']) > 0) {
				foreach ($_POST['grd'] AS $_grd1) {
					$this->sessionData['ttest']['variabla'][$i]['grd'] = $_grd1;
					$i++;
				}
			}
		}
		
		if ( isset($_POST['showChart'])) {
			$this->sessionData['ttest_charts']['showChart'] = ($_POST['showChart'] == 'true');
		}

		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);

		# izvedemo akcijo
		switch ($_GET['a']) {
			case 'spremenljivkaChange':
				$this->spremenljivkaChange();
				break;
			case 'variableChange':
				$this->variableChange();
				break;
			default:
				break;
		}
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);		
	}

	function Display() {
	
		# ali imamo testne podatke
		if ($this->_HAS_TEST_DATA) {
			# izrišemo bar za testne podatke	
            $SSH -> displayTestDataBar(true);
            $SSH = new SurveyStaticHtml($this->sid);
		}
		
		/*echo '<div id="dataOnlyValid">';
		SurveyStatusProfiles::displayOnlyValidCheckbox();
		echo '</div>';*/
			
		//$this->DisplayLinks();
		
		echo '<div id="ttest_variables">';
		$this->DisplayVariables();
		echo '</div>'; # id="ttest_variables"
		echo '<br class="clr">';
		echo '<div id="ttestResults">';
		$this->variableChange();
		echo '</div>'; # id="ttestResults"
	}

	// Izvoz pdf in rtf
	function displayExport () {
		
		$variables2 = $this->getSelectedVariables() ;
		if (is_array($variables2) && count($variables2)>0 ) {

			$href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=ttest_izpis&anketa=' . $this->sid);
			$href_rtf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=ttest_izpis_rtf&anketa=' . $this->sid);
			$href_xls = makeEncodedIzvozUrlString('izvoz.php?b=export&m=ttest_izpis_xls&anketa=' . $this->sid);
				
			echo '<script>';
			# nastavimopravilne linke
			echo '$("#secondNavigation_links a#ttestDoPdf").attr("href", "'.$href_pdf.'");';
			echo '$("#secondNavigation_links a#ttestDoRtf").attr("href", "'.$href_rtf.'");';
			echo '$("#secondNavigation_links a#ttestDoXls").attr("href", "'.$href_xls.'");';
			# prikažemo linke
			echo '$("#hover_export_icon").removeClass("hidden");';
			echo '$("#secondNavigation_links a").removeClass("hidden");';
			echo '</script>';
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}

	function DisplayLinks() {
		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->sid);
		//$SSH -> displayAnalizaSubNavigation();

		# izrišemo desne linke do posameznih nastavitev
		$SSH -> displayAnalizaRightOptions(M_ANALYSIS_TTEST);
	}


	/** Funkcija ki nastavi vse filtre
	 *
	 */
	private function setUpFilter() {
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA
				|| $this->dataFileStatus == FILE_STATUS_NO_FILE
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED){
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

		if (($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) || ($_time_profile_awk != "" && $_time_profile_awk != null)) {
			$this->_CURRENT_STATUS_FILTER  = '('.$this->_CURRENT_STATUS_FILTER;
			if ($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) {
				$this->_CURRENT_STATUS_FILTER .= ' && '.$_condition_profile_AWK;
			}
			if ($_time_profile_awk != "" && $_time_profile_awk != null) {
				$this->_CURRENT_STATUS_FILTER .= ' && '.$_time_profile_awk;
			}
			$this->_CURRENT_STATUS_FILTER .= ')';
		}
			
		$status_filter = $this->_CURRENT_STATUS_FILTER;

		if ($this->dataFileStatus == FILE_STATUS_OK || $this->dataFileStatus == FILE_STATUS_OLD) {

			if (isset($this->_HEADERS['testdata'])) {
				$this->_HAS_TEST_DATA = true;
			}
		}
	}

	function DisplayVariables() {
		global $lang;

		$numerus = $this->getVariableList(1);
		$selectedVar = $this->getSelectedVariables();

		$variables = $this->getVariableList(2);

		echo '<span id="ttestSpremenljivkaSpan" class="floatLeft">';
		echo $lang['srv_ttest_label1'];
		echo '<br />';

		echo '<select id="ttestSpremenljivka" name="ttestSpremenljivka" onchange="ttestSpremenljivkaChange();" autocomplete="off">';
		echo '<option value="0" selected="selected" >'. $lang['srv_ttest_select1_option'] . '</option>';
		if (count($variables)) {
			foreach ($variables as $variable) {
				echo '<option value="'.$variable['spr_id'].'"'
				. ( isset($variable['grd_id']) ? ' grid="'.$variable['grd_id'].'" ' : '')
				. ( isset($variable['vr_id']) ? ' vred="'.$variable['vr_id'].'" ' : '')
				. ( isset($variable['sequence']) ? ' seq2="'.$variable['sequence'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				. ( $variable['spr_id'] != '' && $variable['spr_id'] == $this->sessionData['ttest']['spr2'] && $variable['sequence'] == $this->sessionData['ttest']['seq2'] ? ' selected="selected"':'')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				.$variable['variableNaslov'] . '</option>';
			}
		}
		echo '</select>'; # name="ttestSpremenljivka"
		echo '<br class="clr"/><br/>';
		#.(count($this->sessionData['ttest']['sub_conditions']) == 2?'':'class="active"').
		$_active = $this->checkSubConditionsActive();
		if ($this->sessionData['ttest']['spr2'] > 0) {
			echo '<div id="ttestVariablesSpan"'.($_active < 2 ? ' class="active"' : '').'>';
			$this->spremenljivkaChange();
			echo '</div>';
		} else {
			echo '<div id="ttestVariablesSpan" style="display:none" '.($_active < 2 ? ' class="active"' : '').'></div>';
		}
		echo '</span>';

		$cntSubConditionsActive = $this->checkSubConditionsActive();
		echo '<span class="floatLeft spaceRight">&nbsp;</span>';
		echo '<span id="ttestNumerusSpan" class="floatLeft'.($cntSubConditionsActive == 2 ? '' : ' gray').'">'; #gray
		echo $lang['srv_ttest_label2'];
		echo '<br />';
		echo '<select id="ttestNumerus" name="ttestNumerus" onchange="ttestVariableChange();" autocomplete="off" '.($cntSubConditionsActive == 2 ? '' : ' disabled="disabled"').'>'; # 
		echo '<option value="0" selected="selected" >'. $lang['srv_ttest_select2_option'] . '</option>';
		if (count($numerus)) {
			foreach ($numerus as $variable) {
				echo '<option value="'.$variable['spr_id'].'"'
				. ( isset($variable['grd_id']) ? ' grd="'.$variable['grd_id'].'" ' : '')
				. ( isset($variable['vr_id']) ? ' vrd="'.$variable['vr_id'].'" ' : '')
				. ( isset($variable['sequence']) ? ' seq="'.$variable['sequence'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				. ( $variable['spr_id'] != '' && $variable['spr_id'] == $selectedVar[0]['spr'] && $variable['sequence'] == $selectedVar[0]['seq']?' selected="selected"':'')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				. $variable['variableNaslov'] . '</option>';
			}
		}
		echo '</select>'; # name="ttestSpremenljivka"		
		echo '</span>';
		
		echo '<br /><span style="margin-left: 30px;">';
		echo '<label><input id="showChart" type="checkbox" onchange="showTableChart(\'ttest\');" '.($this->sessionData['ttest_charts']['showChart']==true?' checked="checked"':'' ).'>'.$lang['srv_show_chart'].'</label>';
		echo '</span>';
		echo '<span style="margin-left: 30px;"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/ttest?from1ka=1" target="_blank">';
		echo $lang['srv_ttest_interpretacija_note'];
		echo '</a></span>';
		echo Help::display('srv_ttest_interpretation');
		
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}

	/** funkcija vrne seznam primern variabel za crostabe
	 *
	 */
	function getVariableList($witch) {
		# pobrišemo array()
		$this->variablesList = array();
		# zloopamo skozi header in dodamo variable (potrebujemo posamezne sekvence)
		foreach ($this->_HEADERS AS $skey => $spremenljivka) {
			$tip = $spremenljivka['tip'];
			if ($witch == 1) {
				# prvi drop down, morajo biti numerične ali ordinalne
				# skala - 0 Ordinalna
				# skala - 1 Nominalna
	
				$skala = isset($spremenljivka['skala']) ? $spremenljivka['skala'] : 1;
	
				# če radio nimajo podane skale jo damo na 0
				$skala = ($skala == -1 && ($tip == 6 || $tip == 16) ) ? 0 : $skala ;
	
				$_dropdown_condition = is_numeric($tip) && ((int)$skala === 0  	# ordinalna
						|| $tip == 7	# number
						|| $tip == 18	# vsota
						|| $tip == 20)	# multi number
						? true : false;
			} else {
				#drugi dropdown
				# tekstovnih in numeričnih tipov ne dodajamo
				$_dropdown_condition = is_numeric($tip) && $tip != 4	#text
				&& $tip != 5	#label
				&& $tip != 7	#number
				&& $tip != 8	#datum
				&& $tip != 9	#SN-imena
				&& $tip != 18	#vsota
				&& $tip != 19	#multitext
				&& $tip != 20	#multinumber
				&& $tip != 21	#besedilo*
				&& $tip != 22	#compute
				&& $tip != 25	#kvota
				? true : false;
			}
		if ($_dropdown_condition) {
			$cnt_all = (int)$spremenljivka['cnt_all'];
			# radio in select in checkbox
			if ($cnt_all == '1' || $tip == 1 || $tip == 3 || $tip == 2) {
				# pri tipu radio ali select dodamo tisto variablo ki ni polje "drugo"
				if ($tip == 1 || $tip == 3 ) {
				if (count($spremenljivka['grids']) == 1 ) {
					# če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
					$grid = $spremenljivka['grids'][0];
					if (count ($grid['variables']) > 0) {
						foreach ($grid['variables'] AS $vid => $variable ){
							if ($variable['other'] != 1) {
								# imampo samo eno sekvenco grids[0]variables[0]
								$this->variablesList[] = array(
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
			} else if ($tip == 2){
				if ($witch == 1) {
					#pri checkboxu ponudimo vsako podvariablo posebej
					$this->variablesList[] = array(
							'tip'=>$tip,
							'variableNaslov'=>'('.$spremenljivka['variable'].')&nbsp;'.strip_tags($spremenljivka['naslov']),
							'canChoose'=>false,
							'sub'=>0);
					# imampo samo eno sekvenco grids[0]
					if (count ($spremenljivka['grids'][0]['variables']) > 0) {
						
					foreach ($spremenljivka['grids'][0]['variables'] AS $vid => $variable ){
						if ($variable['other'] != 1) {
							$this->variablesList[] = array(
									'tip'=>$tip,
									'spr_id'=>$skey,
									'vr_id'=>$vid,
									'sequence'=>$variable['sequence'],
									'variableNaslov'=>'('.$variable['variable'].')&nbsp;'.strip_tags($variable['naslov']),
									'canChoose'=>true,
									'sub'=>1);
						}
					}
				}

				} else {
					# imampo samo eno sekvenco grids[0]variables[0]
					$this->variablesList[] = array(
							'tip'=>$tip,
							'spr_id'=>$skey,
							'sequence'=>$spremenljivka['grids'][0]['variables'][0]['sequence'],
							'variableNaslov'=>'('.$spremenljivka['variable'].')&nbsp;'.strip_tags($spremenljivka['naslov']),
							'canChoose'=>true,
							'sub'=>0);
				}

			} else {
				# imampo samo eno sekvenco grids[0]variables[0]
				$this->variablesList[] = array(
						'tip'=>$tip,
						'spr_id'=>$skey,
						'sequence'=>$spremenljivka['grids'][0]['variables'][0]['sequence'],
						'variableNaslov'=>'('.$spremenljivka['variable'].')&nbsp;'.strip_tags($spremenljivka['naslov']),
						'canChoose'=>true,
						'sub'=>0);
			}

			} else if ($cnt_all > 1){
				# imamo več skupin ali podskupin, zato zlopamo skozi gride in variable
				if (count($spremenljivka['grids']) > 0 ) {
				$this->variablesList[] = array(
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
							$this->variablesList[] = array(
									'tip'=>$tip,
									'spr_id'=>$skey,
									'sequence'=>$variable['sequence'],
									'variableNaslov'=>'('.$variable['variable'].')&nbsp;'.strip_tags($variable['naslov']),
									'canChoose'=>true,
									'sub'=>1);
						}
					}
				}

				} else if($tip == 16||$tip == 18) {
					# imamo multicheckbox
					foreach($spremenljivka['grids'] AS $gid => $grid) {
					$sub = 0;
					if ($grid['variable'] != '') {
						$sub++;
						$this->variablesList[] = array(
								'tip'=>$tip,
								'spr_id'=>$skey,
								'grd_id'=>$gid,
								'sequence'=>$grid['variables'][0]['sequence'],
								'variableNaslov'=>'('.$grid['variable'].')&nbsp;'.strip_tags($grid['naslov']),
								'canChoose'=>true,
								'sub'=>1);
					}
				}
				} else {
					# imamo več gridov - tabele
					foreach($spremenljivka['grids'] AS $gid => $grid) {
					$sub = 0;
					if ($grid['variable'] != '') {
						$sub++;
						$this->variablesList[] = array(
								'tip'=>$tip,
								'variableNaslov'=>'('.$grid['variable'].')&nbsp;'.strip_tags($grid['naslov']),
								'canChoose'=>false,
								'sub'=>$sub);
					}
					if (count ($grid['variables']) > 0) {
						$sub++;
						foreach ($grid['variables'] AS $vid => $variable ){
							if ($variable['other'] != 1) {
								$this->variablesList[] = array(
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
				}

			}
			}
		}
	}

	return $this->variablesList;
	}


	function spremenljivkaChange() {
		global $lang;

		if (isset($this->sessionData['ttest']['spr2']) && $this->sessionData['ttest']['spr2'] != 0){
			$_spr = explode('_',$this->sessionData['ttest']['spr2']);
			#$spr = $_spr[0];
			$spr = $this->sessionData['ttest']['spr2'];
				
			if (isset($this->sessionData['ttest']['grid2'])){
				$grid = $this->sessionData['ttest']['grid2'];
			}
			# poiščemo pripadajoče variable
			$_spr_data = $this->_HEADERS[$this->sessionData['ttest']['spr2']];
			echo $lang['srv_ttest_kategories_note'].' ('.$_spr_data['variable'].') '.$_spr_data['naslov'];
			echo '<br/>';
			switch ($_spr_data['tip']) {
				case 1:	#radio
				case 3:	#dropdown
				case 17:	#dropdown
					#nardimo inpute za vse opcije
					$sekvenca = $_spr_data['sequences'];
					foreach ($_spr_data['options'] as $value => $option) {
						echo '<label '.($this->checkboxSubCondition($spr.'_'.$sekvenca.'_'.$value) == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" type="checkbox" value="'.$spr.'_'.$sekvenca.'_'.$value.'" onchange="ttestVariableChange();"'.$this->checkboxSubCondition($spr.'_'.$sekvenca.'_'.$value).'/>('.$value.') - '.$option.'</label><br/>';
					}
					break;
				case 2:	#checkbox
					#nardimo inpute za vse opcije
					$option = '1';
					foreach ($_spr_data['grids'][0]['variables'] as $vid => $variable) {
						echo '<label '.($this->checkboxSubCondition($spr.'_'.$variable['sequence'].'_'.$option) == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" type="checkbox" value="'.$spr.'_'.$variable['sequence'].'_'.$option. '" onchange="ttestVariableChange();"'.$this->checkboxSubCondition($spr.'_'.$variable['sequence'].'_'.$option).'/>('.$variable['variable'].') - '.$variable['naslov'].'</label><br/>';
					}
					break;
				case 6:	#mgrid
					#nardimo inpute za vse opcije
					$sekvenca =	$this->sessionData['ttest']['seq2'];
					foreach ($_spr_data['options'] as $value => $option) {
						//$sekvenca = $_spr_data['grids'][$value]['variables'][0]['sequence'];
						echo '<label '.($this->checkboxSubCondition($spr.'_'.$sekvenca.'_'.$value) == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" type="checkbox" value="'.$spr.'_'.$sekvenca.'_'.$value.'" onchange="ttestVariableChange();"'.$this->checkboxSubCondition($spr.'_'.$sekvenca.'_'.$value).'/>('.$value.') - '.$option.'</label><br/>';
					}
					break;
				case 16:	#mcheck
					#nardimo inpute za vse opcije
					# poiščemo pripadajočo sekvenco
					#nardimo inpute za vse opcije
					$option = '1';
					foreach ($_spr_data['grids'][$grid]['variables'] as $vid => $variable) {
						echo '<label '.($this->checkboxSubCondition($spr.'_'.$variable['sequence'].'_'.$option) == ' disabled="disabled"' ? 'class="gray"' : '').'><input name="subTtest" type="checkbox" value="'.$spr.'_'.$variable['sequence'].'_'.$option.'" onchange="ttestVariableChange();"'.$this->checkboxSubCondition($spr.'_'.$variable['sequence'].'_'.$option).'/>('.$variable['variable'].') - '.$variable['naslov'].'</label><br/>';
					}
					break;

				default:
					if ((int)$_spr_data['tip'] > 0)
						echo'TODO for type:'.$_spr_data['tip'];
					break;
			}
		} else if ($this->sessionData['ttest']['spr2'] == 0){


			echo $lang['srv_ttest_select1_option'].'!';
			echo '<br/>';
				
				
		}

		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}

	function variableChange() {
		global $admin_type;

		if (count($this->sessionData['ttest']['sub_conditions']) > 1 ) {
			$variables1 = $this->getSelectedVariables();
			if (count($variables1) > 0) {
				// ikone za izvoz
				$this->displayExport();
				foreach ($variables1 AS $v_first) {

					$ttest = null;
					/*$ttest = $this->createTTestOld($v_first, $this->sessionData['ttest']['sub_conditions']);
					$this->displayTtestTable($ttest);*/
					$ttest = $this->createTTest($v_first, $this->sessionData['ttest']['sub_conditions']);
					$this->displayTtestTable($ttest);
					
					// Zvezdica za vkljucitev v porocilo
					$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
					$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
					$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
					$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
					$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
					
					$spid2 = $this->sessionData['ttest']['spr2'];
					$seq2 = $this->sessionData['ttest']['seq2'];
					$grid2 = $this->sessionData['ttest']['grid2'];
					
					$spr1 = $seq2.'-'.$spid2.'-'.$grid2.'-'.$sub1.'-'.$sub2;
					$spr2 = $seq1.'-'.$spid1.'-'.$grid1;
					SurveyAnalysis::addCustomReportElement($type=7, $sub_type=0, $spr1, $spr2);
					
					// Izrisemo graf za tabelo
					if(isset($this->sessionData['ttest_charts']['showChart']) && $this->sessionData['ttest_charts']['showChart'] == true){
						$tableChart = new SurveyTableChart($this->sid, $this, 'ttest');
						$tableChart->display();
					}
				}
			}
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}

	function getSelectedVariables() {

		$selected = array();
		if (count($this->sessionData['ttest']['variabla']) > 0 ) {
			foreach ($this->sessionData['ttest']['variabla'] AS $var1) {
				if ((int)$var1['seq'] > 0) {
					$selected[] = $var1;
				}
			}
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		return count($selected) > 0 ? $selected : null;
	}

	
	function createTTest($v_first, $sub_vars) {
		global $site_path;

		$folder = $site_path . EXPORT_FOLDER.'/';
		$R_folder = $site_path . R_FOLDER.'/';

		if ($this->dataFileName != '' && file_exists($this->dataFileName)) {

			// Nastavimo stolpce za katere izvajamo ttest		
			$ttestVars = '';
			$sub_conditions = array();
			$i=1;
			foreach ($sub_vars as $sub_condition) {
				if ($i < 3) {
					$_tmp = explode('_',$sub_condition);
					$ttestVars .= '$'.$_tmp[2].',';	
					$sub_conditions[] = $_tmp[3];
					$i++;
				}
			}
	
			$ttestVars .= '$'.$v_first['seq'].',';		
			$ttestVars = substr($ttestVars, 0, -1);

			// Ce se nimamo datoteke s pripravljenimi podatki jo ustvarimo
			$tmp_file = $R_folder . '/TempData/crosstab_data.tmp';	
			if (!file_exists($tmp_file)) {
				$this->prepareDataFile($ttestVars);
			}
			

			// Inicializiramo R in pozenemo skripto za crosstabulacije
			$R = new SurveyAnalysisR($this->sid);
			$result = $R->createTTest($sub_conditions);
			
			// Na koncu pobrisemo zacasen file s podatki
			$this->deleteDataFile();
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		return $result;
	}
	
	function createTTestOld($v_first, $sub_vars) {
		global $site_path;

		$folder = $site_path . EXPORT_FOLDER.'/';

		if ($this->dataFileName != '' && file_exists($this->dataFileName)) {

			$spr1 = $this->_HEADERS[$v_first['spr']];
			$grid1 = $spr1['grids'][$v_first['grd']];
			$sequence1 =  $v_first['seq'];

			# za checkboxe gledamo samo odgovore ki so bili 1 in za vse opcije
			$sekvences1 = array();
			$spr_1_checkbox = false;

			if ($spr1['tip'] == 2 || $spr1['tip'] == 16) {

				$spr_1_checkbox = true;
				if (isset($sequence1) && (int)$sequence1 > 0) {
					$sekvences1[] = (int)$sequence1;
				} else {
					if ($spr1['tip'] == 2) {

						$sekvences1 = explode('_',$spr1['sequences']);
					}
					if ($spr1['tip'] == 16) {

						foreach ($grid1['variables'] AS $_variables) {
							$sekvences1[] = $_variables['sequence'];
						}
					}
				}
			} else {
				$sekvences1[] = $sequence1;
			}
			# pogoji so že dodani v _CURRENT_STATUS_FILTER
			# dodamo filter za loop-e
			if (isset($this->_CURRENT_LOOP['filter']) && $this->_CURRENT_LOOP['filter'] != '') {
				$status_filter = $this->_CURRENT_STATUS_FILTER.' && '.$this->_CURRENT_LOOP['filter'];
			} else {
				$status_filter = $this->_CURRENT_STATUS_FILTER;
			}
				
			# nastavimo subfiltre za drugo variablo
			$sub_conditions = array();
			$i=1;
			foreach ($sub_vars as $sub_condition) {
				if ($i < 3) {
					$_tmp = explode('_',$sub_condition);
					$sub_conditions[$i] = ' && ($'.$_tmp[2].' == '.$_tmp[3].')';
					$i++;
				}
			}
				

			# dodamo status filter za vse sekvence checkbox-a da so == 1
			if ($additional_status_filter != null) {
				$status_filter .= $additional_status_filter;
			}

			# odstranimo vse zapise, kjer katerakoli od variabel vsebuje missing
			$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
			$_pageMissing_answers = $this->getInvalidAnswers (MISSING_TYPE_CROSSTAB);

			# polovimo obe sequenci
			$tmp_file = $folder . 'tmp_ttest_'.$this->sid.'.TMP';
			$file_handler = fopen($tmp_file,"w");
			fwrite($file_handler,"<?php\n");
			fclose($file_handler);
			if (count($sekvences1)>0){
				foreach ($sekvences1 AS $sequence1) {
					if (count($sub_conditions) > 1) {
						foreach ($sub_conditions as $subkey =>$sub_condition) {

							#skreira variable: $ttest, $cvar1, $cvar2

							$additional_filter = '';
							if ($spr_1_checkbox == true) {
								$_seq_1_text = ''.$sequence1;
									
								# pri checkboxih gledamo samo kjer je 1 ( ne more bit missing)
								$additional_filter = ' && ($'.$sequence1.' == 1)';
							} else {
								$_seq_1_text = '$'.$sequence1;
									
								# dodamo še pogoj za missinge
								foreach ($_pageMissing_answers AS $m_key1 => $missing1) {
									#$additional_filter .= ' && ($'.$sequence1.' != '.$m_key1.')';
								}
							}

							if (IS_WINDOWS) {
								$command1 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$sub_condition.$additional_filter.' { print \"$ttest[\x27\",'.$subkey.',\"\x27][\x27\",'.$_seq_1_text.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
							} else {
								$command1 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$sub_condition.$additional_filter.' { print "$ttest[\x27\",'.$subkey.',\"\x27][\x27",'.$_seq_1_text.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
							}
								
							$out = shell_exec($command1);
						}
					}
				}
			}
			$file_handler = fopen($tmp_file,"a");
			fwrite($file_handler,'?>');
			fclose($file_handler);
			include($tmp_file);
			if (file_exists($tmp_file)) {
				unlink($tmp_file);
			}
				
			# naredimo izračune
			#najprej izračunamo frekvenco in povprečje
			# zloopamo preko posamezneka pod pogoja
			$result = array();

			$cnt = 0;
			if (count($ttest) > 0) {
				foreach ($ttest AS $subkey => $_ttests) {
					$cnt++;
					# zloopamo preko frekvenc in nardimo izračune
					$sum_all = 0;
					$n = 0;
					if(count($_ttests) > 0) {
						foreach ($_ttests AS $value => $freq) {
							#$n = bcadd($n,$freq,BC_PRECISION);
							#$sum_all = bcadd($sum_all,bcmul($value,$freq,BC_PRECISION),BC_PRECISION);
							$n += $freq;
							$sum_all += $value * $freq;
						}
					}
					#n = frekvenca
					#$x = ($n <> 0)
					#		? bcdiv($sum_all,$n,BC_PRECISION)
					#		: 0;
						
					$x = ($n <> 0)
					? $sum_all / $n
					: 0;
						
					$result[$cnt] = array('n'=>$n, 'x'=>$x);

					# izračunamo še standardno diviacijo
					$sum_pow_xi_fi_avg = 0;
					if(count($_ttests) > 0) {
						foreach ($_ttests AS $value => $freq) {

							$xi = $value;
							$fi = $freq;
							#$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
							#$sum_pow_xi_fi_avg = bcadd($sum_pow_xi_fi_avg, bcmul(bcpow(bcsub($xi,$x,BC_PRECISION),2,BC_PRECISION),$fi,BC_PRECISION));
							$sum_pow_xi_fi_avg += pow( ($xi - $x), 2 ) * $fi ;
						}
					}
					#varianca
					#$s2 = (($n - 1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($n -1)) : 0;
					#$s2 = (bcsub($n, 1, BC_PRECISION) <> 0)
					#		? bcsqrt( bcdiv($sum_pow_xi_fi_avg, bcsub($n, 1, BC_PRECISION), BC_PRECISION), BC_PRECISION)
					#		: 0;
					$s2 = ( $n - 1 <> 0 )
					? sqrt( $sum_pow_xi_fi_avg / ($n - 1) )
					: 0;
					$result[$cnt]['s2'] = $s2;
					# standardna napaka
					#se = s2 / sqrt(n)
					#$se = $n > 0
					#		? bcdiv($s2, bcsqrt($n, BC_PRECISION), BC_PRECISION)
					#		: 0;
					$se = $n > 0
					? $s2 / sqrt($n)
					: 0;
						
					$result[$cnt]['se'] = $se;
					#se2 = *se^2
					#$se2 = bcpow($se, 2, BC_PRECISION);
					$se2 = pow($se, 2);
					$result[$cnt]['se2'] = $se2;
						
					#margini => 1,96*ee
					#$margin = bcmul(1.96, $se, BC_PRECISION);
					$margin = 1.96 * $se;
					$result[$cnt]['margin'] = $margin;
				}
			}
			#razlika povprečij => $d = x1 -x2
			#$d = bcsub($result[1]['x'], $result[2]['x'], BC_PRECISION);
			$d = $result[1]['x'] -  $result[2]['x'];
			$result['d'] = $d;
				
			#sed : std. error difference
			#$sed = bcsqrt( bcadd($result[1]['se2'], $result[2]['se2'], BC_PRECISION), BC_PRECISION  );
			$sed = sqrt( $result[1]['se2'] + $result[2]['se2']);
			$result['sed'] = $sed;
				
			#ttest => t = d / sed
			#$t = ($sed <> 0)
			#			? bcdiv($d, $sed, BC_PRECISION)
			#			: 0;
			$t = ($sed <> 0)
			? $d / $sed
			: 0;
			$result['t'] = $t;
				
		}
	
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		return $result;
	}
	
	// Pripravimo file iz katerega preberemo podatke in izvedemo ttest
	function prepareDataFile($cols){
		global $site_path;
		
		$folder = $site_path . EXPORT_FOLDER.'/';
		$R_folder = $site_path . R_FOLDER.'/';
		
		
		if (isset($this->_CURRENT_LOOP['filter']) && $this->_CURRENT_LOOP['filter'] != '') {
			$status_filter = $this->_CURRENT_STATUS_FILTER.' && '.$this->_CURRENT_LOOP['filter'];
		} else {
			$status_filter = $this->_CURRENT_STATUS_FILTER;
		}
			
		# dodamo status filter za vse sekvence checkbox-a da so == 1
		if ($additional_status_filter != null) {
			$status_filter .= $additional_status_filter;
		}

		# odstranimo vse zapise, kjer katerakoli od variabel vsebuje missing
		$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
		$_pageMissing_answers = $this->getInvalidAnswers(MISSING_TYPE_CROSSTAB);
			

		// File kamor zapisemo filtrirane podatke
		$tmp_file = $R_folder . '/TempData/ttest_data.tmp';		


		// Filtriramo podatke po statusu in loopih in jih zapisemo v temp folder R-ja
		if (IS_WINDOWS) {
			$command = 'awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$status_filter.' { print '.$cols.' }" '.$this->dataFileName.' >> '.$tmp_file;
		} else {
			$command = 'awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.' { print '.$cols.'; }\' '.$this->dataFileName.' >> '.$tmp_file;
		}

		$out = shell_exec($command);
		
		return $out;
	}
	
	// Pobrisemo zacasen file s podatki
	function deleteDataFile(){
		global $site_path;
		
		$R_folder = $site_path . R_FOLDER.'/';
		$tmp_file = $R_folder . '/TempData/ttest_data.tmp';	
		
		// Na koncu pobrisemo zacasen file s podatki
		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
	}
	
	
	/** Sestavi array nepravilnih odgovorov
	 *
	 */
	function getInvalidAnswers($type) {
		$result = array();
		$missingValuesForAnalysis = SurveyMissingProfiles :: GetMissingValuesForAnalysis($type);

		foreach ($missingValuesForAnalysis AS $k => $answer) {
			$result[$k] = array('text'=>$answer,'cnt'=>0);
		}
		return $result;
	}

	function displayTtestTable($ttest) {
		global $lang;

		# preverimo ali imamo izbrano odvisno spremenljivko
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];

		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {
			if ($this->isArchive == false) {
				echo '<div id="displayFilterNotes">';
				# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
				SurveyTimeProfiles :: printIsDefaultProfile();
				# če imamo filter ifov ga izpišemo
				SurveyConditionProfiles:: getConditionString($doNewLine );
				# če imamo filter spremenljivk ga izpišemo
				SurveyVariablesProfiles:: getProfileString($doNewLine, true);
				SurveyDataSettingProfiles :: getVariableTypeNote($doNewLine );
				# če rekodiranje
				$SR = new SurveyRecoding($this->sid);
				$SR -> getProfileString();
				echo '</div>';
					
				echo '<br class="clr" />';
			}
			$spr_data_1 = $this->_HEADERS[$spid1];
			if ($grid1 == 'undefined') {

				# imamp lahko več variabel
				$seq = $seq1;
				foreach ($spr_data_1['grids'] as $gkey => $grid ) {
						
					foreach ($grid['variables'] as $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($sequence == $seq) {
							$sprLabel1 = '('.$variable['variable'].') '. $variable['naslov'];
						}
					}
				}
			} else {
				# imamo subgrid
				$sprLabel1 = '('.$spr_data_1['grids'][$grid1]['variable'].') '. $spr_data_1['grids'][$grid1]['naslov'];
			}
				
			# polovio labele
			$spid2 = $this->sessionData['ttest']['spr2'];
			$sprLabel2 =  trim($this->sessionData['ttest']['label2']);
			$label1 = $this->getVariableLabels($this->sessionData['ttest']['sub_conditions'][0]);
			$label2 = $this->getVariableLabels($this->sessionData['ttest']['sub_conditions'][1]);
			echo '<table border="0" class="ttestTable">';
			echo '<tr>';
			#labele
			echo '<td class="lightGreen" rowspan="2" >';
			echo '<span class="anl_variabla">';
			echo '<a href="#" onclick="showspremenljivkaSingleVarPopup(\''.$spid2.'\'); return false;">';
			echo $sprLabel2.'</a>';
			echo '</span>';
			echo '</td>';
				
			echo '<td class="lightGreen" colspan="9">';
			echo '<span class="anl_variabla">';
			echo '<a href="#" onclick="showspremenljivkaSingleVarPopup(\''.$spid1.'\'); return false;">';
			echo $sprLabel1.'</a>';
			echo '</span>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			#echo '<th colspan="2">&nbsp;</th>';
			#frekvenca
			echo '<th >n</th>';
			#povprečje
			echo '<th><span class="avg">x</span></th>';
			#varianca
			echo '<th>s&#178;</th>';
			#standardna napaka
			echo '<th>se(<span class="avg">x</span>)</th>';
			#margini
			echo '<th>&#177;1,96&#215;se(<span class="avg">x</span>)</th>';
			#d
			echo '<th>d</th>';
			#sed
			echo '<th>se(d)</th>';
			#signifikanca
			echo '<th>Sig.</th>';
			#ttest
			echo '<th>t</th>';
			echo '</tr>';

			echo '<tr>';

			#labele
				
			echo '<td class="lightGreen">'.$label1.'</td>';
			#frekvenca
			echo '<td>'.$this->formatNumber($ttest[1]['n'],0).'</td>';
			#povprečje
			echo '<td>'.$this->formatNumber($ttest[1]['x'],3).'</td>';
			#varianca
			echo '<td>'.$this->formatNumber($ttest[1]['s2'],3).'</td>';
			#standardna napaka
			echo '<td>'.$this->formatNumber($ttest[1]['se'],3).'</td>';
			#margini
			echo '<td>'.$this->formatNumber($ttest[1]['margin'],3).'</td>';
			#d
			echo '<td rowspan="2">'.$this->formatNumber($ttest['d'],3).'</td>';
			#sed
			echo '<td rowspan="2">'.$this->formatNumber($ttest['sed'],3).'</td>';
			#sig
			echo '<td rowspan="2">'.$this->formatNumber($ttest['sig'],3).'</td>';
			#ttest
			echo '<td rowspan="2">'.$this->formatNumber($ttest['t'],3).'</td>';
			echo '</tr>';

			echo '<tr>';
			#labele
			echo '<td class="lightGreen">'.$label2.'</td>';
			#frekvenca
			echo '<td>'.$this->formatNumber($ttest[2]['n'],0).'</td>';
			#povprečje
			echo '<td>'.$this->formatNumber($ttest[2]['x'],3).'</td>';
			#varianca
			echo '<td>'.$this->formatNumber($ttest[2]['s2'],3).'</td>';
			#standardna napaka
			echo '<td>'.$this->formatNumber($ttest[2]['se'],3).'</td>';
			#margini
			echo '<td>'.$this->formatNumber($ttest[2]['margin'],3).'</td>';
			echo '</tr>';
			echo '</table>';
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}

	/** Naredimo formatiran izpis
	 *
	 * @param $value
	 * @param $digit
	 * @param $sufix
	 */

	static function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		# polovimo decimalna mesta in vejice za tisočice

		$decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
		$thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			
		$result = number_format($result, $digit, $decimal_point, $thousands).$sufix;

		return $result;
	}

	function getVariableLabels($sub_conditions) {
		$_tmp = explode('_',$sub_conditions);
		$spr = $this->_HEADERS[$_tmp[0].'_'.$_tmp[1]];
		switch ($spr['tip']) {
			case 1:	#radio
			case 3:	#dropdown
				$label = $spr['options'][$_tmp[3]];
				break;
			case 2:	#checkbox
				foreach ($spr['grids'][0]['variables'] as $vkey => $variable) {
					if($variable['sequence'] == $_tmp[2]) {
						$label = '('.$variable['variable'].') - '.$variable['naslov'];
					}
				}
				break;
			case 6:	#mgrid
				$label = $spr['options'][$_tmp[3]];
				break;
			case 16:	#mcheck
				$label = $spr['options'][$_tmp[3]];
				break;
			default:
				$label =  'TODO: getVariableLabels for type:'.$spr['tip'];
				break;
		}
		return $label;
	}

	function checkboxSubCondition($checkCondition) {

		$cnt = $this->checkSubConditionsActive();
		$sub_Conditions = $this->sessionData['ttest']['sub_conditions'];
		if (is_array($sub_Conditions) && count($sub_Conditions) > 0) {
			foreach ($sub_Conditions AS $sub_condition) {
				if ($sub_condition == $checkCondition) {
					return ' checked="checked"';
				}
			}
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		return $cnt == 2 ? ' disabled="disabled"' : null;
	}

	function checkSubConditionsActive() {

		$cnt = 0;
		
		$needle = $this->sessionData['ttest']['spr2'];
		$length = strlen($needle);
		$sub_Conditions = $this->sessionData['ttest']['sub_conditions'];
		if (is_array($sub_Conditions) && count($sub_Conditions) > 0) {
			foreach ($sub_Conditions AS $haystack) {
				$cnt += (int)(substr($haystack, 0, $length) === $needle);
			}
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		return $cnt;			
	}
}