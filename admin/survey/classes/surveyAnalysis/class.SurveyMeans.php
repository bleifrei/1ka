<?php
/** Class ki skrbi za povprečja - meanse
 *  December 2011
 * 
 * 
 * Enter description here ...
 * @author Gorazd_Veselic
 *
 */

define("EXPORT_FOLDER", "admin/survey/SurveyData");

class SurveyMeans{
    
	private $sid;										# id ankete
	private $db_table;									# katere tabele uporabljamo
	private $_HEADERS = array();						# shranimo podatke vseh variabel
	
	private $headFileName = null;						# pot do header fajla
	private $dataFileName = null;						# pot do data fajla
	private $dataFileStatus = null;						# status data datoteke
	private $SDF = null;								# class za inkrementalno dodajanje fajlov
	
	public $variabla1 = array('0'=> array('seq'=>'0','spr'=>'undefined', 'grd'=>'undefined')); # array drugih variable, kamor shranimo spr, grid_id, in sequenco
	public $variabla2 = array('0'=> array('seq'=>'0','spr'=>'undefined', 'grd'=>'undefined')); # array drugih variable, kamor shranimo spr, grid_id, in sequenco
	
	public $variablesList = null; 					 	# Seznam vseh variabel nad katerimi lahko izvajamo meanse (zakeširamo)
	
	public $_CURRENT_STATUS_FILTER = ''; 		# filter po statusih, privzeto izvažamo 6 in 5
	
	public $_HAS_TEST_DATA = false;						# ali anketa vsebuje testne podatke
	
	public $doValues = true; 							# checkbox Prikaži vrednosti	
	
	private $sessionData;							# podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
	public function __construct($sid) {
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
				
			$this->_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/';
			
			SurveyStatusProfiles::Init($this->sid);
			SurveyMissingProfiles :: Init($this->sid,$global_user_id);
			SurveyConditionProfiles :: Init($this->sid, $global_user_id);
			SurveyZankaProfiles :: Init($this->sid, $global_user_id);
			SurveyTimeProfiles :: Init($this->sid, $global_user_id);
			SurveyVariablesProfiles :: Init($this->sid);
				
			SurveyDataSettingProfiles :: Init($this->sid);

			
			#inicializiramo class za datoteke
			$this->SDF =  SurveyDataFile::get_instance();
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
			
			# nastavimo uporabniške nastavitve
			$this->readUserSettings();
			
		} else {
			echo 'Invalid Survey ID!';
			exit();
		}
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
	
	function readUserSettings() {
		$sdsp = SurveyDataSettingProfiles :: getSetting();
		$this->doValues = $sdsp['doValues'] == '1' ? true : false;
	}
		
	/** Prikazuje filtre
	 *
	 */
	function DisplayFilters() {
		if ($this->dataFileStatus == FILE_STATUS_SRV_DELETED || $this->dataFileStatus == FILE_STATUS_NO_DATA){
			return false;
		}
		
		global $lang;
		if ($this->setUpJSAnaliza == true) {
			echo '<script>
			        window.onload = function() {
		            __analiza = 1;
		            __tabele = 1;
		        }
		        </script>';
		}
		
		/*echo '<div id="dataOnlyValid">';
		SurveyStatusProfiles::displayOnlyValidCheckbox();
		echo '</div>';*/
		
				
		# izrišemo desne linke do posameznih nastavitev
		$SSH -> displayAnalizaRightOptions(M_ANALYSIS_MEANS);
	}
	
	function DisplayLinks() {
		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->sid);
		$SSH -> displayAnalizaSubNavigation();
	}
	
	function ajax() {
		#nastavimo variable če so postane
		$this->setPostVars();
		# izvedemo akcijo
		switch ($_GET['a']) {
			case 'changeDropdown':
				$this->displayDropdowns();
				break;
			case 'change':
				$this->displayData();
				break;
			case 'add_new_variable':
				$this->addNewVariable();
				break;
			case 'changeMeansSubSetting':
				$this->changeMeansSubSetting();
				break;
			case 'changeMeansShowChart':
				$this->changeMeansShowChart();
				break;
			default:
				print_r("<pre>");
				print_r($_GET);
				print_r($_POST);
				break;
		}

	}

	function Display() {
		global $lang;
		
		# ali imamo testne podatke
		if ($this->_HAS_TEST_DATA) {
            # izrišemo bar za testne podatke
            $SSH = new SurveyStaticHtml($this->sid);
			$SSH -> displayTestDataBar(true);
		}
		
		# preberemo prednastavljene variable iz seje, če obstajajo
		$this->presetVariables();
		
		//$this->DisplayLinks();	
		//$this->DisplayFilters();
		
		echo '<div id="div_means_dropdowns">';
		$this->displayDropdowns();
		echo '</div>'; #id="div_means_dropdowns"
		
		echo '<div id="div_means_data">';
		$this->displayData();
		echo '</div>'; #id="div_means_data"
	}
	
	function displayDropdowns() {
		global $lang;
		$variables1 = $this->getVariableList(1);
		$variables2 = $this->getVariableList(2);

		echo '<div id="meansLeftDropdowns" >';
		if ((int)$this->variabla1['0']['seq'] > 0) {
			echo '<span class="pointer space_means_new" >&nbsp;</span>';
		}
		echo $lang['srv_means_label1'];		
		echo '<br />';		
		# iz header datoteke preberemo spremenljivke
		#js: $("#means_variable_1, #means_variable_2").live('click', function() {})
		if (count($this->variabla1) > 0) {
			$br=null;
			if ((int)$this->variabla1['0']['seq'] > 0) {
				echo '<span class="pointer" id="means_add_new" onclick="means_add_new_variable(\'1\');"><span class="faicon add small icon-as_link" title=""></span></span>';
			}
				
			foreach($this->variabla1 AS $_key => $variabla1) {
				echo $_br;
				echo '<span id="v1_'.$_key.'">';

				echo '<select name="means_variable_1" id="means_variable_1" onchange="change_means(); return false;" autocomplete="off">';

				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $variabla1['seq'] == null || $variabla1['seq'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_means_izberi_prvo'].'</option>';
				}
				foreach ($variables1 as $variable) {
					echo '<option value="'.$variable['sequence'].'" spr_id="'.$variable['spr_id'].'" '
					. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($variabla1['seq'] > 0 &&$variabla1['seq'] == $variable['sequence']) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $variable['variableNaslov'].'</option>';

				}

				echo '</select>';
				if (count($this->variabla1) > 1) {
					echo '<span class="pointer" id="means_remove" onclick="means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
				} else {
					#echo '<span class="space_means_new">&nbsp;</span>';
				}

				$_br = '<br/><span class="space_means_new">&nbsp;</span>';
				echo '</span>';
			}
			$_br = null;
		}

		echo '</div>';

		echo '<div id="meansRightDropdowns">';
		if ((int)$this->variabla1['0']['seq'] > 0) {
			echo '<span class="pointer space_means_new" >&nbsp;</span>';
		}
		echo $lang['srv_means_label2'];
		echo '<br />';		
		
		
		# za vsako novo spremenljivko 2 nardimo svoj select
		if (count($this->variabla2) > 0) {
			if ((int)$this->variabla1['0']['seq'] > 0) {
				echo '<span class="pointer" id="means_add_new" onclick="means_add_new_variable(\'2\');"><span class="faicon add small icon-as_link" title="'.'"></span></span>';
			}
				
			foreach($this->variabla2 AS $_key => $variabla2) {
				echo $_br;
				echo '<span id="v2_'.$_key.'">';
				echo '<select name="means_variable_2" id="means_variable_2" onchange="change_means(); return false;" autocomplete="off"'
				. ((int)$this->variabla1['0']['seq'] > 0 ? '' : ' disabled="disabled" ')
				.'>';

				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ((int)$this->variabla1['0']['seq'] == 0) {
					echo '<option value="0" selected="selected" >'. $lang['srv_means_najprej_prvo'].'</option>';
				} else {
					# če druga variabla ni izbrana dodamo tekst za izbiro druge variable
					if ($variabla2['seq'] == null || $variabla2['seq'] == 0) {
						echo '<option value="0" selected="selected" >'. $lang['srv_means_izberi_drugo'].'</option>';
					}
				}
					
				foreach ($variables2 as $variable) {
					echo '<option value="'.$variable['sequence'].'" spr_id="'.$variable['spr_id'].'" '
					. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( $variabla2['seq'] > 0 && $variabla2['seq'] == $variable['sequence'] ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $variable['variableNaslov'] .'</option>';

				}
				echo '</select>';
				if (count($this->variabla2) > 1) {
					echo '<span class="pointer" id="means_remove" onclick="means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
				} else {
					echo '<span class="space_means_new">&nbsp;</span>';
				}

				$_br = '<br/><span class="space_means_new">&nbsp;</span>';
				echo '</span>';
			}
		}
		echo '</div>';
		
		echo '<span id="meansSubSetting" class="floatLeft spaceLeft">';
		if (count($this->variabla2) > 1) {	
			echo '<label><input id="chkMeansSeperate" type="checkbox" onchange="changeMeansSubSetting();" '.($this->sessionData['means']['meansSeperateTables']==true?' checked="checked"':'' ).'> '.$lang['srv_means_setting_1'].'</label>';
			
			echo '<br /><span id="spanMeansJoinPercentage"'.($this->sessionData['means']['meansSeperateTables']!=true?'':' class="displayNone"').'><label><input id="chkMeansJoinPercentage" type="checkbox" onchange="changeMeansSubSetting();" '.($this->sessionData['means']['meansJoinPercentage']==true?' checked="checked"':'' ).'> '.$lang['srv_means_setting_2'].'</label></span>';
		}
		echo '<br /><label><input id="showChart" type="checkbox" onchange="showTableChart(\'mean\');" '.($this->sessionData['mean_charts']['showChart']==true?' checked="checked"':'' ).'> '.$lang['srv_show_charts'].'</label>';
		echo '</span>';
		
		echo '<br class="clr"/>';	

		// Ikone za izvoz (so tukaj da se refreshajo ob ajax klicu)
		$this->displayExport();
	}
	
	function displayData() {
		global $lang;
		global $admin_type;
		
		$br='';
		$means = array();
		
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		SurveyTimeProfiles :: printIsDefaultProfile(false);

		# če imamo filter ifov ga izpišemo
		SurveyConditionProfiles:: getConditionString();

		# če imamo filter spremenljivk ga izpišemo
		SurveyVariablesProfiles:: getProfileString($doNewLine , true);

		# če imamo rekodiranje
		$SR = new SurveyRecoding($this->sid);
		$SR -> getProfileString();
		
		if ($this->getSelectedVariables(1) !== null && $this->getSelectedVariables(2) !== null) {
			$variables1 = $this->getSelectedVariables(2);
			$variables2 = $this->getSelectedVariables(1);
			$c1=0;
			$c2=0;
			
			# odvisno ok checkboxa prikazujemo druge variable v isti tabeli ali v svoji
			if ($this->sessionData['means']['meansSeperateTables'] == true ) {
				#prikazujemo ločeno
				if (is_array($variables2) && count($variables2) > 0) {
					foreach ($variables2 AS $v_second) {
						if (is_array($variables1) && count($variables1) > 0) {
							foreach ($variables1 AS $v_first) {
								$_means = $this->createMeans($v_first, $v_second);
								if ($_means != null) {
									$means[$c1][0] = $_means;
								}
								$c1++;
							}
						}
					}
				}
			} else {
				#prikazujemo v isti tabeli
				if (is_array($variables2) && count($variables2) > 0) {
					foreach ($variables2 AS $v_second) {
						if (is_array($variables1) && count($variables1) > 0) {
							foreach ($variables1 AS $v_first) {
								$_means = $this->createMeans($v_first, $v_second);
								if ($_means != null) {
									$means[$c1][$c2] = $_means;
								}
								$c2++;
							}
						}
						$c1++;
						$c2=0;
					}
				}
			}

			if (is_array($means) && count($means) > 0) {
				$counter=0;
				foreach ($means AS $mean_sub_grup) {
					echo($br);
					$this->displayMeansTable($mean_sub_grup);
					$br='<br />';

					// Zvezdica za vkljucitev v porocilo
					$spr2 = $mean_sub_grup[0]['v1']['seq'].'-'.$mean_sub_grup[0]['v1']['spr'].'-'.$mean_sub_grup[0]['v1']['grd'];
					$spr1 = $mean_sub_grup[0]['v2']['seq'].'-'.$mean_sub_grup[0]['v2']['spr'].'-'.$mean_sub_grup[0]['v2']['grd'];
					SurveyAnalysis::Init($this->sid);
					SurveyAnalysis::addCustomReportElement($type=6, $sub_type=0, $spr1, $spr2);

					// Izrisemo graf za tabelo - zaenkrat samo admin
					if($this->sessionData['mean_charts']['showChart'] && $_GET['m'] != 'analysis_creport'){
						$tableChart = new SurveyTableChart($this->sid, $this, 'mean', $counter);
						$tableChart->display();
					}
					
					$counter++;
				}
			}

		} else {
			# dropdowni niso izbrani
		}
	}
	
	// Izvoz pdf in rtf
	function displayExport () {

		if ($this->isSelectedBothVariables()) {
			$vars1 = $this->getSelectedVariables(1);
			$vars2 = $this->getSelectedVariables(2);
			
			$data1 = '';
			$data2 = '';
			
			foreach($vars1 as $var1){
				$data1 .= implode(',', array_values($var1)).',';
			}
			$data1 = substr($data1, 0, -1);
			
			foreach($vars2 as $var2){
				$data2 .= implode(',', array_values($var2)).',';
			}
			$data2 = substr($data2, 0, -1);
		
			
			$href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=mean_izpis&anketa=' . $this->sid);
			$href_rtf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=mean_izpis_rtf&anketa=' . $this->sid);
			$href_xls = makeEncodedIzvozUrlString('izvoz.php?b=export&m=mean_izpis_xls&anketa=' . $this->sid);
			echo '<script>';
			# nastavimopravilne linke
			echo '$("#secondNavigation_links a#meansDoPdf").attr("href", "'.$href_pdf.'");';
			echo '$("#secondNavigation_links a#meansDoRtf").attr("href", "'.$href_rtf.'");';
			echo '$("#secondNavigation_links a#meansDoXls").attr("href", "'.$href_xls.'");';
			# prikažemo linke
			echo '$("#hover_export_icon").removeClass("hidden");';
			echo '$("#secondNavigation_links a").removeClass("hidden");';
			echo '</script>';
		}
	}
	
	function setPostVars() {
		if ( isset($_POST['sequence1']) && count($_POST['sequence1']) > 0 ) {
			$i=0;
			if (is_array($_POST['sequence1']) && count($_POST['sequence1']) > 0 ){
				foreach ($_POST['sequence1'] AS $_seq1) {
					$this->variabla1[$i]['seq'] = $_seq1;
					$i++;
				}
			}
		}
		if ( isset($_POST['spr1']) && count($_POST['spr1']) > 0 ) {
			$i=0;
			if (is_array($_POST['spr1']) && count($_POST['spr1']) > 0 ){
				foreach ($_POST['spr1'] AS $_spr1) {
					$this->variabla1[$i]['spr'] = $_spr1;
					$i++;
				}
			}
		}
		if ( isset($_POST['grid1']) && count($_POST['grid1']) > 0 ) {
			$i=0;
			if ( is_array($_POST['grid1']) &&count($_POST['grid1']) > 0 ){
				foreach ($_POST['grid1'] AS $_grd1) {
					$this->variabla1[$i]['grd'] = $_grd1;
					$i++;
				}
			}
		}

		if ( isset($_POST['sequence2']) && count($_POST['sequence2']) > 0 ) {
			$i=0;
			
			if (is_array($_POST['sequence2']) && count($_POST['sequence2']) > 0 ){
				
				foreach ($_POST['sequence2'] AS $_seq2) {
					$this->variabla2[$i]['seq'] = $_seq2;
					$i++;
				}
			}
		}
		if ( isset($_POST['spr2']) && count($_POST['spr2']) > 0 ) {
			$i=0;
			if ( is_array($_POST['spr2']) && count($_POST['spr2']) > 0 ){
				foreach ($_POST['spr2'] AS $_spr2) {
					$this->variabla2[$i]['spr'] = $_spr2;
					$i++;
				}
			}
		}
		if ( isset($_POST['grid2']) && is_array($_POST['grid2']) && count($_POST['grid2']) > 0 ) {
			$i=0;
			if ( count($_POST['grid2']) > 0 ){
				foreach ($_POST['grid2'] AS $_grd2) {
					$this->variabla2[$i]['grd'] = $_grd2;
					$i++;
				}
			}
		}
		
		# variable shranimo v sejo, da jih obdržimo tudi če spreminjamo nastavitve ali razne filtre analiz
		if (isset($this->variabla1) && count($this->variabla1) > 0) {
			$this->sessionData['means']['means_variables']['variabla1'] =  $this->variabla1;
		}
		if (isset($this->variabla2) && count($this->variabla2) > 0) {
			$this->sessionData['means']['means_variables']['variabla2'] =  $this->variabla2;
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);	
	}
	
	/** funkcija vrne seznam primern variabel za meanse
	 *
	 */
	function getVariableList($dropdown) {
		if (isset($this->variablesList[$dropdown]) && is_array($this->variablesList[$dropdown]) && count($this->variablesList[$dropdown]) > 0) {
			return $this->variablesList[$dropdown];
		} else {
			# pobrišemo array()
			$this->variablesList = array();
			# zloopamo skozi header in dodamo variable (potrebujemo posamezne sekvence)
			foreach ($this->_HEADERS AS $skey => $spremenljivka) {
				if ((int)$spremenljivka['hide_system'] == 1 && in_array($spremenljivka['variable'],array('email','ime','priimek','telefon','naziv','drugo'))) {
					continue;
				}
					
				$tip = $spremenljivka['tip'];

				
				$skala = (int)$spremenljivka['skala'];
				# pri drugi, analizirani variabli morajo biti numerične ali ordinalne, v ostalem pa nič)
				# skala - 0 Ordinalna
				# skala - 1 Nominalna
				$_dropdown_condition = $dropdown == 1 
									|| ($dropdown == 2 
										&& ($skala == 0  	# ordinalna 
											|| $tip == 7	# number 
											|| $tip == 18	# vsota 
											|| $tip == 20))	# multi number
					? true : false;

				
				if (is_numeric($tip)
				# tekstovnih tipov ne dodajamo

				&& $tip != 4	#text
				&& $tip != 5	#label
				#&& $tip != 7	#number
				#&& $tip != 8	#datum
				&& $tip != 9	#SN-imena
				#&& $tip != 18	#vsota
				#&& $tip != 19	#multitext
				#&& $tip != 20	#multinumber
				#&& $tip != 21	#besedilo*
				&& $tip != 22	#compute
				&& $tip != 25	#kvota
				&& $_dropdown_condition	# ali ustreza pogoju za meanse
				) {
					
					$cnt_all = (int)$spremenljivka['cnt_all'];
					# radio in select in checkbox
					if ($cnt_all == '1' || $tip == 1 || $tip == 3 || $tip == 2) {
						
						
						# pri tipu radio ali select dodamo tisto variablo ki ni polje "drugo"
						if (($tip == 1 || $tip == 3 )) {
							if (count($spremenljivka['grids']) == 1 ) {
								# če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
								$grid = $spremenljivka['grids'][0];
								if (count ($grid['variables']) > 0) {
									foreach ($grid['variables'] AS $vid => $variable ){
										if ($variable['other'] != 1) {
											# imampo samo eno sekvenco grids[0]variables[0]
											$this->variablesList[$dropdown][] = array(
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
						} else if ($skala == 1 || true) { # ta pogoj skala == 1 je malo sumljiv. ne vem več zakaj je tako

							# imampo samo eno sekvenco grids[0]variables[0]
							$this->variablesList[$dropdown][] = array(
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
							$this->variablesList[$dropdown][] = array(
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
											$this->variablesList[$dropdown][] = array(
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
										$this->variablesList[$dropdown][] = array(
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
										$this->variablesList[$dropdown][] = array(
											'tip'=>$tip,
											'variableNaslov'=>'('.$grid['variable'].')&nbsp;'.strip_tags($grid['naslov']),
											'canChoose'=>false,
											'sub'=>$sub);
									}
									if (count ($grid['variables']) > 0) {
										$sub++;
										foreach ($grid['variables'] AS $vid => $variable ){
											if ($variable['other'] != 1) {
												$this->variablesList[$dropdown][] = array(
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

			return $this->variablesList[$dropdown];
		}
	}
	
	function isSelectedBothVariables() {
		$selected1 = false;
		$selected2 = false;
		if (count($this->variabla1)) {
			foreach ($this->variabla1 AS $var1) {
				if ((int)$var1['seq'] > 0) {
					$selected1 = true;
				}
			}
		}
		if (count($this->variabla2)) {
			foreach ($this->variabla2 AS $var2) {
				if ((int)$var2['seq'] > 0) {
					$selected2 = true;
				}
			}
		}

		return ($selected1 && $selected2);
	}
	

	function getSelectedVariables($which = 1) {
		$selected = array();
		if ($which == 1) {
			if (count($this->variabla1) > 0 ) {
				foreach ($this->variabla1 AS $var1) {
					if ((int)$var1['seq'] > 0) {
						$selected[] = $var1;
					}
				}
			}
		} else {
			if (count($this->variabla2) > 0 ) {
				foreach ($this->variabla2 AS $var2) {
					if ((int)$var2['seq'] > 0) {
						$selected[] = $var2;
					}
				}
			}
		}

		return count($selected) > 0 ? $selected : null;
	}
	
	public function createMeans($v_first, $v_second) {
		global $site_path;
		$folder = $site_path.EXPORT_FOLDER.'/';

		if ($this->dataFileName != '' && file_exists($this->dataFileName)) {

			$spr1 = $this->_HEADERS[$v_first['spr']];
			$spr2 = $this->_HEADERS[$v_second['spr']];

			$grid1 = $spr1['grids'][$v_first['grd']];
			$grid2 = $spr2['grids'][$v_second['grd']];
				
			$sequence1 =  $v_first['seq'];
			$sequence2 = $v_second['seq'];
							
			# za checkboxe gledamo samo odgovore ki so bili 1 in za vse opcije
			$sekvences1 = array();
			$sekvences2 = array();
			$spr_1_checkbox = false;
			$spr_2_checkbox = false;

			if ($spr1['tip'] == 2 || $spr1['tip'] == 16) {
				$spr_1_checkbox = true;
				if ($spr1['tip'] == 2) {
					$sekvences1 = explode('_',$spr1['sequences']);
				}
				if ($spr1['tip'] == 16) {

					foreach ($grid1['variables'] AS $_variables) {
						$sekvences1[] = $_variables['sequence'];
					}
				}
			} else {
				$sekvences1[] = $sequence1;
			}

			if ($spr2['tip'] == 2 || $spr2['tip'] == 16) {
				$spr_2_checkbox = true;
				if ($spr2['tip'] == 2 ) {
					$sekvences2 = explode('_',$this->_HEADERS[$v_second['spr']]['sequences']);
				}
				if ($spr2['tip'] == 16) {
					foreach ($grid2['variables'] AS $_variables) {
						$sekvences2[] = $_variables['sequence'];
					}
				}
			} else {
				$sekvences2[] = $sequence2;
			}
				
			# pogoji so že dodani v _CURRENT_STATUS_FILTER
				
			# dodamo filter za loop-e
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
			$_pageMissing_answers = $this->getInvalidAnswers (MISSING_TYPE_CROSSTAB);
			# polovimo obe sequenci
			$tmp_file = $folder.'tmp_means_'.$this->sid.'.tmp';

			$file_handler = fopen($tmp_file,"w");
			fwrite($file_handler,"<?php\n");
			fclose($file_handler);
			if (count($sekvences1)>0)
			foreach ($sekvences1 AS $sequence1) {
				if (count($sekvences2)>0)
				foreach ($sekvences2 AS $sequence2) {
					#skreira variable: $meansArray
						
					$additional_filter = '';
					if ($spr_1_checkbox == true) {
						$_seq_1_text = ''.$sequence1;

						# pri checkboxih gledamo samo kjer je 1 ( ne more bit missing)
						$additional_filter = ' && ($'.$sequence1.' == 1)';
					} else {
						$_seq_1_text = '$'.$sequence1;

						# dodamo še pogoj za missinge
						foreach ($_pageMissing_answers AS $m_key1 => $missing1) {
							$additional_filter .= ' && ($'.$sequence1.' != '.$m_key1.')';
						}
					}
						
					if ($spr_2_checkbox == true) {
						$_seq_2_text = ''.$sequence2;

						# pri checkboxih gledamo samo kjer je 1 ( ne more bit missing)
						$additional_filter .= ' && ($'.$sequence2.' == 1)';
					} else {
						$_seq_2_text = '$'.$sequence2;

						# dodamo še pogoj za missinge
						foreach ($_pageMissing_answers AS $m_key2 => $missing2) {
							$additional_filter .= ' && ($'.$sequence2.' != '.$m_key2.')';
						}
					}

					if (IS_WINDOWS) {
						$command = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$additional_filter.' { print \"$meansArray[\x27\",'.$_seq_2_text.',\"\x27][\x27\",'.$_seq_1_text.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
					} else {
						$command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$additional_filter.' { print "$meansArray[\x27",'.$_seq_2_text.',"\x27][\x27",'.$_seq_1_text.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
					}

					$out = shell_exec($command);
				}

			}
			
			$file_handler = fopen($tmp_file,"a");
			fwrite($file_handler,'?>');
			fclose($file_handler);
			include($tmp_file);

			if (file_exists($tmp_file)) {
				unlink($tmp_file);
			}

			# izračunamo povprečja
			$means = array();
			$_tmp_sumaMeans = 0;
			if(is_array($meansArray) && count($meansArray) > 0) {
				foreach ($meansArray AS $f_key => $first) {
					$tmp_sum = 0;
					$tmp_cnt = 0;
					foreach ($first AS $s_key => $second) {
						# preverimo da je vse numeric
						if (is_numeric($s_key) && is_numeric($second)) {
							$tmp_sum = $tmp_sum + ($s_key*$second);
							$tmp_cnt = $tmp_cnt + $second;
							
						}
					}
					$_tmp_sumaMeans += $tmp_sum;
					$key = $f_key;
					if ($tmp_cnt != 0) {
						$means[$key] = bcdiv($tmp_sum, $tmp_cnt, 3);
					} else {
						$means[$key] = bcdiv(0,1, 3);
					}
				}
			}
			# inicializacija
			$_all_options = array();
			$sumaVrstica = array();
			$sumaSkupna = 0;
			$sumaMeans = 0;
			
			# poiščemo pripadajočo spremenljivko
			$var_options = $this->_HEADERS[$v_second['spr']]['options'];

				
			# najprej poiščemo (združimo) vse opcije ki so definirane kot opcije spremenljivke in vse ki so v meansih
			if (count($var_options) > 0 && $spr_2_checkbox !== true) {
				foreach ($var_options as $okey => $opt) {
					$_all_options[$okey] = array('naslov'=>$opt, 'type'=>'o');
				}
			}

			# za checkboxe dodamo posebej vse opcije
			if ($spr_2_checkbox == true ) {
				if ($spr2['tip'] == 2 ) {
					$grid2 =$this->_HEADERS[$v_second['spr']]['grids']['0'];
				}

				foreach ($grid2['variables'] As $vkey => $variable) {
					if ($variable['other'] != 1) {
						$_all_options[$variable['sequence']] = array('naslov'=>$variable['naslov'], 'type'=>'o', 'vr_id'=> $variable['variable']);
					}
				}
			}
				
			# dodamo odgovore iz baze ki niso missingi
			if (count($meansArray) > 0 ) {
				foreach ($meansArray AS $_kvar1=>$_var1) {
					# missingov ne dodajamo še zdaj, da ohranimo pravilen vrstni red
					foreach ($_var1 AS $_kvar2=>$_var2) {
						if (!isset($_allMissing_answers[$_kvar1]) || (isset($_allMissing_answers[$_kvar1]) && isset($_pageMissing_answers[$_kvar1]))) {
							$sumaVrstica[$_kvar1] += $_var2;
						}
					}
					# missingov ne dodajamo še zdaj, da ohranimo pravilen vrstni red
					if (!isset($_allMissing_answers[$_kvar1]) && !isset($_all_options[$_kvar1])) {
						$_all_options[$_kvar1] = array('naslov'=>$_kvar1, 'type'=>'t');
					}
					
				}
			}
			# dodamo še missinge, samo tiste ki so izbrani z profilom
			foreach ($_allMissing_answers AS $miskey => $_missing) {
				if (!isset($_pageMissing_answers[$miskey])) {
					if ( $spr_2_checkbox !== true ) {
						$_all_options[$miskey] = array('naslov'=>$_missing, 'type'=>'m');
					}
				}
			}
			$sumaSkupna = array_sum($sumaVrstica);
			$sumaMeans = ($sumaSkupna > 0) ? $_tmp_sumaMeans / $sumaSkupna : 0;

			# če lovimo po enotah, moramo skupne enote za vsako kolono(vrstico) izračunati posebej
			if ($this->crossNavVsEno == 1) {
				$sumaSkupna = 0;
				$sumaVrstica = array();

				# sestavimo filtre za posamezno variablo da ni missing
				if (count($sekvences1)>0) {
					$spr1_addFilter = '';

					foreach ($sekvences1 AS $sequence1) {
						# dodamo še pogoj za missinge
						foreach ($_pageMissing_answers AS $m_key1 => $missing1) {
							$spr1_addFilter .= ' && ($'.$sequence1.' != '.$m_key1.')';
						}
					}
				}
				if (count($sekvences2)>0) {
					$spr2_addFilter = '';

					foreach ($sekvences2 AS $sequence2) {
						# dodamo še pogoj za missinge
						foreach ($_pageMissing_answers AS $m_key2 => $missing2) {
							$spr2_addFilter .= ' && ($'.$sequence2.' != '.$m_key2.')';
						}
					}
				}

				# polovimo obe sequenci
				$tmp_file = $folder.'tmp_means_'.$this->sid.'.TMP';


				$file_handler = fopen($tmp_file,"w");
				fwrite($file_handler,"<?php\n");

				fclose($file_handler);

				# preštejemo vse veljavne enote (nobena vrednost ne sme bit missing)
				if (IS_WINDOWS) {
					$command_all = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$spr1_addFilter.$spr2_addFilter.' { print \"$sumaSkupna++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
				} else {
					$command_all = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$spr1_addFilter. $spr2_addFilter.' { print "$sumaSkupna++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
				}

				$out_all = shell_exec($command_all);


				#za vsako variablo polovimo število enot
				#najprej za stolpce
				if (count($sekvences1)>0) {
					foreach ($sekvences1 AS $sequence1) {
						if ($spr_1_checkbox == true) {
							$_seq_1_text = ''.$sequence1;
							# pri checkboxih lovimo samo tiste ki so 1
							$chckbox_filter1 = ' && ($'.$sequence1.' == 1)';
						} else {
							$_seq_1_text = '$'.$sequence1;
						}

						if (IS_WINDOWS) {
							$command_1 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$chckbox_filter1.$spr2_addFilter.' { print \"$sumaVrstica[\x27\",'.$_seq_1_text.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
						} else {
							$command_1 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$chckbox_filter1.$spr2_addFilter.' { print "$sumaVrstica[\x27",'.$_seq_1_text.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
						}
						$out = shell_exec($command_1);
					}
				}
			}


			
			$meansArr['v1'] 	 	= $v_first;	# prva variabla
			$meansArr['v2'] 	 	=  $v_second;	# druga variabla
			$meansArr['result'] 	 = $means;	# povprečja
			$meansArr['options']	 =	$_all_options;	# vse opcije za variablo 2
			$meansArr['sumaVrstica'] =	$sumaVrstica;	#
			$meansArr['sumaSkupna']  =	$sumaSkupna;	#
			$meansArr['sumaMeans']  =	$sumaMeans;	#
			return $meansArr;
		}
	}
	
	function displayMeansTable($_means) {
		global $lang;
			
		#število vratic in število kolon
		$cols = count($_means);
		# preberemo kr iz prvega loopa
		$rows = count($_means[0]['options']);

		# ali prikazujemo vrednosti variable pri spremenljivkah
		$show_variables_values = $this->doValues;
		
		$showSingleUnits = $this->sessionData['means']['meansJoinPercentage']==true && $this->sessionData['means']['meansSeperateTables'] == false;
		
		# izrišemo tabelo
		echo '<table class="anl_tbl_crosstab fullWidth" style="margin-top:10px;">';
		echo '<colgroup>';
		echo '<col style="width:auto; min-width:30px;" />';
		echo '<col style="width:auto; min-width:30px; " />';
		for ($i = 0; $i < $cols; $i++) {
			echo '<col style="width:auto; min-width:30px;" />';
			if ($showSingleUnits == false) {
				echo '<col style="width:auto; min-width:30px;" />';
			}
		}
		if ($showSingleUnits == true) {
			echo '<col style="width:auto; min-width:30px;" />';
		}
		echo '</colgroup>';

		echo '<tr>';
		#echo '<td>xx&nbsp;</td>';
		# ime variable
		# teksti labele:
		$label2 = $this->getSpremenljivkaTitle($_means[0]['v2']);
		if ($showSingleUnits == false) {
			$span = ' colspan="2"';
		}
		echo '<td class="anl_bt anl_bl anl_ac rsdl_bck_title ctbCll" rowspan="2">';
		echo $label2;
		echo '</td>';
		
		for ($i = 0; $i < $cols; $i++) {
			echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_title ctbCll"'.$span.'>';
			$label1 = $this->getSpremenljivkaTitle($_means[$i]['v1']);
			echo $label1;
			echo '</td>';
		}
		if ($showSingleUnits == true) {
			echo '<td class="anl_bl ">&nbsp;</td>';
		}
		echo '</tr>';
		echo '<tr>';
		
		for ($i = 0; $i < $cols; $i++) {
			#Povprečje
			echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_variable1 ctbCll" >';
			echo $lang['srv_means_label'];
			echo '</td>';
			#enote
			if ($showSingleUnits == false) {
				echo '<td class="anl_bl anl_bt anl_br anl_ac red anl_ita anl_bck_text_0 rsdl_bck_variable1 ctbCll">'.$lang['srv_means_label4'].'</td>';
			}
		}
		if ($showSingleUnits == true) {
			echo '<td class="anl_bl anl_bt anl_br anl_ac red anl_ita anl_bck_text_0 rsdl_bck_variable1 ctbCll">'.$lang['srv_means_label4'].'</td>';
		}
		
		echo '</tr>';

		if (count($_means[0]['options']) > 0) {
			
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {
				$units_per_row = 0;
				echo '<tr>';
				echo '<td class="anl_bt anl_bl anl_ac rsdl_bck_variable1 ctbCll">';
				echo $crossVariabla2['naslov'];
				# če ni tekstovni odgovor dodamo key
				if ($crossVariabla2['type'] !== 't' ) {
					if ($show_variables_values == true) {
						if ($crossVariabla2['vr_id'] == null) {
							echo '&nbsp;( '.$ckey2.' )';
						} else {
							echo '&nbsp;( '.$crossVariabla2['vr_id'].' )';
						}
					}
				}
				echo '</td>';
				# celice z vsebino
				for ($i = 0; $i < $cols; $i++) {
					echo '<td class="ct_in_cell anl_br'.'" k1="'.$ckey1.'" k2="'.$ckey2.'" n1="'.$crossVariabla1['naslov'].'" n2="'.$crossVariabla2['naslov'].'" v1="'.$crossVariabla1['vr_id'].'" v2="'.$crossVariabla2['vr_id'].'">';
					echo $this->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'));
					echo '</td>';
					if ($showSingleUnits == false) {
						echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0 crostabSuma">';
						echo (int)$_means[$i]['sumaVrstica'][$ckey2];
						echo '</td>';
					} else {
						$units_per_row = max($units_per_row,(int)$_means[$i]['sumaVrstica'][$ckey2]);
					}
				}
				if ($showSingleUnits == true) {
					echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0 crostabSuma">';
					echo $units_per_row;
					echo '</tr>';
				}
				echo '</tr>';
				$max_units += $units_per_row;
			}
		}
		echo '<tr>';
		echo '<td class="anl_bb anl_bt anl_bl anl_ac red anl_ita anl_bck_text_0 rsdl_bck_variable1 ctbCll">'.$lang['srv_means_label3'].'</td>';
		for ($i = 0; $i < $cols; $i++) {
			echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
			
			echo $this->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'));
			echo '</td>';
			if ($showSingleUnits == false) {
				echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
				echo (int)$_means[$i]['sumaSkupna'];
				echo '</td>';
			}
		}
		if ($showSingleUnits == true) {
			echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
			echo $max_units;
			echo '</tr>';
		}
		
		echo '</tr>';
		echo '</table>';
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
	
	function addNewVariable() {
		global $lang;
		$which = $_POST['which'];
		$variables = $this->getVariableList($which);
		$multiple = true;
			

		if ($which == '1') {
			echo '<br/>';
			echo '<span class="space_means_new">&nbsp;</span>';
			echo '<select name="means_variable_'.$which.'" id="means_variable_'.$which.'" onchange="change_means(); return false;" autocomplete="off"'
			.'>';
			# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
			if ( $variabla1['seq'] == null || $variabla1['seq'] == 0 ) {
				echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_izberi_more'].'</option>';
			}
				
			foreach ($variables as $variable) {
				echo '<option value="'.$variable['sequence'].'" spr_id="'.$variable['spr_id'].'" '
				. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				. $variable['variableNaslov'] . '</option>';

			}
			echo '</select>';
			echo '<span class="pointer" id="means_remove" onclick="means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
				
		} else {
			# which = 2
			echo '<br/>';
			echo '<span class="space_means_new">&nbsp;</span>';
			echo '<select name="means_variable_'.$which.'" id="means_variable_'.$which.'" onchange="change_means(); return false;" autocomplete="off"'
			.'>';
				
			# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
			if ((int)$this->variabla1['0']['seq'] > 0) {
				echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_najprej_prvo'].'</option>';
			} else {
				# če druga variabla ni izbrana dodamo tekst za izbiro druge variable
				echo '<option value="0" selected="selected">'. $lang['srv_analiza_crosstab_izberi_more'].'</option>';
			}

			foreach ($variables as $variable) {
				echo '<option value="'.$variable['sequence'].'" spr_id="'.$variable['spr_id'].'" '
				. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				. $variable['variableNaslov'] . '</option>';

			}
			echo '</select>';
			echo '<span class="pointer" id="means_remove" onclick="means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
		}
	}
	
	function getSpremenljivkaTitle($v_first) {
		global $lang;
		# podatki spremenljivk
		$spremenljivka_id = $v_first['spr'];
		$grid_id = $v_first['grd'];
		$sekvenca = $v_first['seq'];
		
		$spremenljivka = $this->_HEADERS[$spremenljivka_id];
		$grid = $spremenljivka['grids'][$grid_id];
		
		
		# za multicheckboxe popravimo naslov, na podtip
		$labela = null;
		if ($spremenljivka['tip'] == '6' || $spremenljivka['tip'] == '7' || $spremenljivka['tip'] == '16' || $spremenljivka['tip'] == '17' || $spremenljivka['tip'] == '18' || $spremenljivka['tip'] == '19' || $spremenljivka['tip'] == '20' || $spremenljivka['tip'] == '21' ) {
			foreach ($spremenljivka['grids'] AS $grids) {
				foreach ($grids['variables'] AS $variable) {
					if ($variable['sequence'] == $sekvenca) {
						$labela .= '<span class="anl_variabla">';
						$labela .= '<a href="/" title="'.$lang['srv_predogled_spremenljivka'].'" onclick="showspremenljivkaSingleVarPopup(\''.$spremenljivka_id.'\'); return false;">';
						$labela .= strip_tags($spremenljivka['naslov']);
						if ($show_variables_values == true) { 
							$labela .= '&nbsp;('.strip_tags($spremenljivka['variable']).')';
						}
						$labela .= '</a>';
						$labela .= '</span>';

						if ($spremenljivka['tip'] == '16') {
							if (strip_tags($grid['naslov']) != $lang['srv_new_text']) {
								$labela .= '<br/>'.strip_tags($grid['naslov']);
							}
							$labela .= '&nbsp;('.strip_tags($grid['variable']).')' ;
						} else {
							if (strip_tags($variable['naslov']) != $lang['srv_new_text']) {
								$labela .= '<br/>'.strip_tags($variable['naslov']);
							}
							if ($show_variables_values == true) {
								$labela .= '&nbsp;('.strip_tags($variable['variable']).')';
							}
						}
						
					}
				}
			}
		}
		if ($labela == null) {
			$labela = '<span class="anl_variabla">';
			$labela .= '<a href="/" title="'.$lang['srv_predogled_spremenljivka'].'" onclick="showspremenljivkaSingleVarPopup(\''.$spremenljivka_id.'\'); return false;">';
			$labela .=  strip_tags($spremenljivka['naslov']);
			if ($show_variables_values == true) {
				$labela .=  '&nbsp;('.strip_tags($spremenljivka['variable']).')';
			}
			$labela .=  '</a>';
			$labela .=  '</span>'.NEW_LINE;
		}
		return $labela;
	}
	
	function changeMeansSubSetting() {
		$this->sessionData['means']['meansSeperateTables'] = ($_POST['chkMeansSeperate'] == 1);
		$this->sessionData['means']['meansJoinPercentage'] = ($_POST['chkMeansJoinPercentage'] == 1);
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	function changeMeansShowChart() {
		$this->sessionData['mean_charts']['showChart'] = ($_POST['showChart'] == 'true');
		$this->sessionData['means']['meansSeperateTables'] = ($_POST['showChart'] == 'true') ? true : $this->sessionData['means']['meansSeperateTables'];
		$this->sessionData['means']['meansJoinPercentage'] = ($_POST['showChart'] == 'true') ? true : $this->sessionData['means']['meansJoinPercentage'];
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	function presetVariables() {
		# preberemo prednastavljene variable iz seje, če obstajajo
		if (isset($this->sessionData['means']['means_variables']['variabla1']) && count($this->sessionData['means']['means_variables']['variabla1']) > 0) {
			$this->variabla1 = $this->sessionData['means']['means_variables']['variabla1'];
		}
		if (isset($this->sessionData['means']['means_variables']['variabla2']) && count($this->sessionData['means']['means_variables']['variabla2']) > 0) {
			$this->variabla2 = $this->sessionData['means']['means_variables']['variabla2'];
		}
	}
}