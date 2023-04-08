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
define("BREAK_OPTION_LIMIT", 15);

class SurveyBreak
{
	private $sid;										# id ankete
	private $db_table;									# katere tabele uporabljamo
	public $_HEADERS = array();							# shranimo podatke vseh variabel
	
	private $headFileName = null;						# pot do header fajla
	private $dataFileStatus = null;						# status data datoteke
	private $SDF = null;								# class za inkrementalno dodajanje fajlov

	public $variablesList = null; 					 	# Seznam vseh variabel nad katerimi lahko izvajamo break(zakeširamo)

	public $_CURRENT_STATUS_FILTER = ''; 				# filter po statusih, privzeto izvažamo 6 in 5

	public $_HAS_TEST_DATA = false;						# ali anketa vsebuje testne podatke
	
	public $break_percent = false;						# ali prikazujemo procente
	public $break_charts = 0;							# ali prikazujemo 0->tabele ali 1->grafe
	
	private $sessionData;							# podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...

	private $SurveyAnaliza;							# klas analiza
	private $SurveyCrosstab;						# klas Crosstab
	

	private $decimal_point = ',';
	private $thousands = '.';
	private $num_digit_average = NUM_DIGIT_AVERAGE;
	private $num_digit_percent = NUM_DIGIT_PERCENT;
	
	function __construct($sid) {
		if ((int)$sid > 0) {
			$this->sid = $sid;

			SurveyAnalysisHelper::getInstance()->Init($this->sid);
				
			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->sid);
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();

			$this->_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/';
				
			#inicializiramo class za datoteke
			$this->SDF = SurveyDataFile::get_instance();
			$this->SDF->init($this->sid);
			$this->headFileName = $this->SDF->getHeaderFileName();
			$this->dataFileStatus = $this->SDF->getStatus();
			
			# Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->sid);
			SurveyUserSetting::getInstance()->Init($this->sid, $global_user_id);
				
			SurveyStatusProfiles::Init($this->sid);
			SurveyMissingProfiles :: Init($this->sid,$global_user_id);
			SurveyConditionProfiles :: Init($this->sid, $global_user_id);
			SurveyZankaProfiles :: Init($this->sid, $global_user_id);
			SurveyTimeProfiles :: Init($this->sid, $global_user_id);
			SurveyVariablesProfiles :: Init($this->sid, $global_user_id);
			
			SurveyDataSettingProfiles :: Init($this->sid);
			# polovimo decimalna mesta in vejice za tisočice
			$this->decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
			$this->thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			$this->num_digit_average = SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE');
			$this->num_digit_percent = SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT');
				
			
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

			$this->SurveyAnaliza = new SurveyAnalysis();
			$this->SurveyAnaliza->Init($this->sid);
			
			$this->SurveyCrosstab = new SurveyCrosstabs();
			$this->SurveyCrosstab->Init($this->sid);
				
			# nastavimo vse filtre
			$this->setUpFilter();

		} else {
			echo 'Invalid Survey ID!';
			exit();
		}
	}

	function ajax() {
		
		# spremenljivko in sekvenco shranimo v session
		if (isset($_POST['spr'])) {
			$this->sessionData['break']['spr'] = $_POST['spr'];
			if (isset($_POST['seq'])) {
				$this->sessionData['break']['seq'] = $_POST['seq'];
			}
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);

		# izvedemo akcijo
		switch ($_GET['a']) {
			case 'spremenljivkaChange':
				$this->doBreakForSpremenljivka();
			break;
			case 'change_break_percent':
				$this->setSessionPercent();
				break;
			break;
			case 'change_break_charts':
				$this->setSessionCharts();
				break;
			break;
			default:
				print_r("<pre>");
				print_r($_GET);
				print_r($_POST);
				print_r("</pre>");
				break;
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

		# ali prikazujemo procente
		$this->break_percent = (isset($this->sessionData['break']['break_percent']) && (int)$this->sessionData['break']['break_percent'] == 1) ? true : false;
		
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

	function Display() {
		global $lang;
	
		# ali imamo testne podatke
		if ($this->_HAS_TEST_DATA) {
            # izrišemo bar za testne podatke	
            $SSH = new SurveyStaticHtml($this->sid);
			$SSH -> displayTestDataBar(true);
		}	
		
		/*echo '<div id="dataOnlyValid">';
		SurveyStatusProfiles::displayOnlyValidCheckbox();
		echo '</div>';*/		
		
		# ali prikazujemo tabele ali grafe
		$this->break_charts = (isset($this->sessionData['break']['break_show_charts']) && (int)$this->sessionData['break']['break_show_charts'] == 1) ? 1 : 0;
		
		//$this->DisplayLinks();
		//$this->DisplayFilters();
		
		echo '<div id="div_break_data">';
		$this->displayData();
		echo '</div>'; #id="div_break_data"
		
	}


	function DisplayLinks() {
		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->sid);
		$SSH -> displayAnalizaSubNavigation();
	}

		
	/** Prikazuje filtre
	 *
	*/
	function DisplayFilters() {
		global $lang;
		
		if ($this->dataFileStatus == FILE_STATUS_SRV_DELETED || $this->dataFileStatus == FILE_STATUS_NO_DATA){
			return false;
		}
		

		if ($this->setUpJSAnaliza == true) {
			echo '<script>
			        window.onload = function() {
		            __analiza = 1;
		            __tabele = 1;
		        }
		        </script>';
		}

		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->sid);
		# izrišemo desne linke do posameznih nastavitev
		$SSH -> displayAnalizaRightOptions(M_ANALYSIS_BREAK);
	}
	
	function displayData() {
		global $lang;
		echo '<div id="break_variables">';
		$variables = $this->getVariableList(2);
		
		echo '<span id="breakSpremenljivkaSpan" class="floatLeft spaceRight">';
		echo $lang['srv_break_label1'];
		echo '<br />';
		echo '<select id="breakSpremenljivka" name="breakSpremenljivka" onchange="breakSpremenljivkaChange();" autocomplete="off">';
		echo '<option value="0" selected="selected" >'. $lang['srv_break_select1_option'] . '</option>';
		if (count($variables)) {
			foreach ($variables as $variable) {
				echo '<option value="'.$variable['spr_id'].'"'
				. ( isset($variable['sequence']) ? ' seq="'.$variable['sequence'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				.($this->sessionData['break']['seq'] == $variable['sequence'] && (int)$variable['canChoose'] == 1 ? ' selected="selected"':'')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				. $variable['variableNaslov'] . '</option>';
			}
		}
		echo '</select>'; # name="breakSpremenljivka"
		echo '</span>';

		echo '<span id="div_crossCheck" class="floatLeft spaceLeft" style="margin-top:14px;">' ;
		$this->displayLinePercent();
		$this->displayLineCharts(); // V DELU...		
		echo '</span>';
		
		echo '<br class="clr" />';
		echo '</div>'; # id="break_variables"
		if (isset($this->sessionData['break']['spr']) && (int)$this->sessionData['break']['spr'] > 0 
				&& isset($this->sessionData['break']['seq']) && (int)$this->sessionData['break']['seq'] > 0) {
			echo '<div id="breakResults" >';
			$this->doBreakForSpremenljivka();
			echo '</div>'; # id="breakResults"
		} else {
			echo '<div id="breakResults" />';
		}	
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
			{
				#drugi dropdown
				# tekstovnih in numeričnih tipov ne dodajamo
				$_dropdown_condition = is_numeric($tip) 
					#&& $tip != 2# checkbox - 21.11.2012 baje mormo checkboxe p
													&& $tip != 4	#text
													&& $tip != 5	#label
													&& $tip != 7	#number
													&& $tip != 8	#datum
													&& $tip != 9	#SN-imena
													&& $tip != 16	#multicheck
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

	function doBreakForSpremenljivka() {
		global $lang;
		
		# ali prikazujemo procente		
		$this->break_percent = isset($this->sessionData['break']['break_percent']) && (int)$this->sessionData['break']['break_percent'] == 1 ? true : false;
		$this->break_charts = isset($this->sessionData['break']['break_show_charts']) && (int)$this->sessionData['break']['break_show_charts'] == 1 ? 1 : 0;
		
		if (isset($this->sessionData['break']['spr']) && $this->sessionData['break']['spr'] != 0){
			$_spr = explode('_',$this->sessionData['break']['spr']);
			$spr = $this->sessionData['break']['spr'];
			# poiščemo pripadajoče variable
			$_spr_data = $this->_HEADERS[$this->sessionData['break']['spr']];
			
			# poiščemo sekvenco
			$sekvenca = $this->sessionData['break']['seq'];
			
			# poiščemo opcije
			$opcije = $_spr_data['options'];
			
			# izrisemo ikone za izvoz pdf/rtf
			$this->displayExport($spr, $sekvenca);
				
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
							$oKeyfrequencys = $this->getAllFrequencys($oKey, $seq, $spr);
							if ($oKeyfrequencys != null) {
								$frequencys[$seq][$oKey] = $oKeyfrequencys;
							} 
						}
					}
				}
			}
			$this->displayBreak($spr,$frequencys);
		
		} else {
			echo '<br class="clr">';
			echo '<p class="red strong">'.$lang['srv_break_error_note_1'].'</p>';
		}
	}
	
	/** za posamezno opcijo izračunamo frekvence za vse spremenljivke
	 * 
	 * @param unknown_type $oKey
	 * @param unknown_type $seq
	 */
	function getAllFrequencys($oKey, $seq, $spr) {
		$result = null;
		# sestavimo dodaten awk filter
		$awk_filter = $this->getAwkFilter($oKey, $seq, $spr);
		if ($awk_filter != null) {
			# pridobimo frekvence
			$this->SurveyAnaliza->setUpFilter();
			$this->SurveyAnaliza->frequencyAddInvalid(false);
			$result = $this->SurveyAnaliza->getFrequencys($awk_filter);
		}
		return $result;
	}

	function getAwkFilter($oKey, $seq, $spr) {
		$result = null;
		$_spr_data = $this->_HEADERS[$spr];
		$tip = $_spr_data['tip'];
		
		$result = '$'.$seq.'=='.$oKey;
		return $result;
		
	}
	
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
			
		$result = number_format($result, $digit, $this->decimal_point, $this->thousands) . $sufix;
	
		return $result;
	}
	
	function displayBreak($forSpr, $frequencys) {
		
		
		ob_start(); // outer buffer
		# če imamo filter spremenljivk ga izpišemo
		echo '<br/>';
		
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		SurveyTimeProfiles :: printIsDefaultProfile(false);
		
		# če imamo filter ifov ga izpišemo
		SurveyConditionProfiles:: getConditionString();
		
		# če imamo filter spremenljivk ga izpišemo
		SurveyVariablesProfiles:: getProfileString(true);
		SurveyDataSettingProfiles :: getVariableTypeNote();
		
		# filtriranje po spremenljivkah
		$_FILTRED_VARIABLES = SurveyVariablesProfiles::getProfileVariables(SurveyVariablesProfiles::checkDefaultProfile(), true);
		foreach ($this->_HEADERS AS $skey => $spremenljivka) {
				
			if ((int)$spremenljivka['hide_system'] == 1 && in_array($spremenljivka['variable'],array('email','ime','priimek','telefon','naziv','drugo'))) {
				continue;
			}
			$spremenljivka['id'] = $skey;
			$tip = $spremenljivka['tip'];
			if ( is_numeric($tip) 
						&& $tip != 4	#text
						&& $tip != 5	#label
						&& $tip != 8	#datum
						&& $tip != 9	#SN-imena
						&& $tip != 19	#multitext
						&& $tip != 21	#besedilo*
			&& ( count($_FILTRED_VARIABLES) == 0 || (count($_FILTRED_VARIABLES) > 0 && isset($_FILTRED_VARIABLES[$skey]) ))
						) {
				
				$this->displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka);
			} else if ( is_numeric($tip) 
						&& (
								$tip == 4	#text
								|| $tip == 19	#multitext
								|| $tip == 21	#besedilo*
								|| $tip == 20	#multi numer*
						) && ( count($_FILTRED_VARIABLES) == 0 || (count($_FILTRED_VARIABLES) > 0 && isset($_FILTRED_VARIABLES[$skey]) ) )
						) {
				$this->displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka);
			}
				
			ob_end_flush();
		}
		
		// Izpisemo nastavitve na dnu (izvozi, arhiv, vabila...)
		$this->displayBottomSettings();
	}
	
	function displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka) {
		
		$tip = $spremenljivka['tip'];
		
		if ($forSpr != $spremenljivka['id']) {
			
			switch ($tip) {
				# radio, dropdown
				case 1:
				case 3:
					$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
					break;
				
				#multigrid
				case 6:
					$skala = Common::getSpremenljivkaSkala($spremenljivka['id']);
					if ($skala == 0) {
						$this->displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka);
					} 
					else {
						$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
					}
					break;
				
				# checkbox
				case 2:
						$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
						break;
				#number
				case 7:
				#ranking
				case 17:
				#vsota
				case 18:
				#multinumber
				case 20:
					$this->displayBreakTableNumber($forSpr,$frequencys,$spremenljivka);
				break ;
				
				case 19:
					$this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
				break ;
				#multicheck
				case 16:
					$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
				break;
				
				case 4:	
				case 21:
					# po novem besedilo izpisujemo v klasični tabeli
					$this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
					
					#$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
				break;
				
				default:
					$this->displayCrosstabTable($forSpr,$frequencys,$spremenljivka);
				break;
			}
		}
	}
	
	function displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka){
		#mgrid - 16:
		
		// Ce izrisujemo graf
		if($this->break_charts == 1){
			$tableChart = new SurveyTableChart($this->sid, $this, 'break');
			$tableChart->setBreakVariables($forSpr,$frequencys,$spremenljivka);
			$tableChart->display();
		}
		
		// Ce izrisujemo tabelo
		else{
			
			$keysCount = count($frequencys);
			$sequences = explode('_',$spremenljivka['sequences']);
			$forSpremenljivka = $this->_HEADERS[$forSpr];
			$tip = $spremenljivka['tip'];
			
			# izračunamo povprečja za posamezne sekvence
			$means = array();
			$totalMeans = array();
			$totalFreq = array();
			foreach ($frequencys AS $fkey => $options) {
				foreach ($options AS $oKey => $option) {
					foreach ($sequences AS $sequence) {
						$txt = $this->getMeansFromKey($option[$sequence]);
						if ($txt) {
							$cnt[$fkey]++;
							$means[$fkey][$oKey][$sequence] = $txt;
						}
					}
				}
			}
						
			# ce imamo vec kot 20 kategorij,izpisujemo samo tiste ki imajo vrednosti
			$displayAll = (count($options) > 20) ? false : true;
				
			echo '<div id="'.$spremenljivka['id'].'" class="breakTableDiv">';
			if (isset($spremenljivka['double']) && $spremenljivka['double'] > 1) {
				$doubleGridParts = $spremenljivka['double'];
				$multiply = 1;
				$isDoubleGrid = true;
			} else {
				$doubleGridParts[1]['subtitle'] = '';
				$multiply = 2;
				$isDoubleGrid = false;
			}

			
			# če imamo dvojno tabelo
			
			$rowspan = ' rowspan="3"';
			$colspan = ' colspan="'.($multiply*count($sequences)).'"';

			foreach ($doubleGridParts AS $part => $doubleGridTitle) {
				echo '<br/>';
				# če ni multicheck in multi grid
				echo '<table>';
				echo '<tr>';
				echo '<th'.$rowspan.'>';
				echo '<span class="anl_variabla">';
				echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $forSpr . '\'); return false;">';
				echo $forSpremenljivka['naslov'];
				echo '('.$forSpremenljivka['variable'].')';
				echo '</a>';
				echo '</span>';
					
				echo '</th>';
				echo '<th'.$colspan.'>';
				echo '<span class="anl_variabla">';
				echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $spremenljivka['id'] . '\'); return false;">';
				echo $spremenljivka['naslov'];
				echo '('.$spremenljivka['variable'].')';
				echo '</a>';
				if (isset ($doubleGridTitle['subtitle'])) {
					echo ' - '.$doubleGridTitle['subtitle'];
				}
				echo '</span>';
				echo '</th>';
				echo '</tr>';
					
				echo '<tr>';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					if ($isDoubleGrid == false || ($isDoubleGrid == true && $grid['part'] == $part)) {
						foreach ($grid['variables'] AS $vkey => $variable) {
	
							echo '<th class="sub" colspan="2">';
							echo $variable['naslov'];
							echo '('.$variable['variable'].')';
							echo '</th>';
						}
					}
				}
				echo '</tr>';
				echo '<tr>';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					if ($isDoubleGrid == false || ($isDoubleGrid == true && $grid['part'] == $part)) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							echo '<th class="sub">Povprečje'.$lang[''];
							echo '</th>';
							echo '<th class="sub red">Št. enot'.$lang[''];
							echo '</th>';
						}
					}
				}
				echo '</tr>';
				$cnt=0;
				foreach ($frequencys AS $fkey => $fkeyFrequency) {
					$cbxLabel = $forSpremenljivka['grids'][0]['variables'][$cnt]['naslov'];
					$cnt++;
					foreach ($options AS $oKey => $option) {
						if ($means[$fkey][$oKey] != null || $displayAll) {
							echo '<tr>';
							echo '<td'.$break_percentRowSpan.' class="rsdl_bck_variable1">';
							if ($forSpremenljivka['tip'] == 2) {
								echo $cbxLabel;
							} else {
								echo $forSpremenljivka['options'][$oKey];
							}
							echo '</td>';
							$css = '';
							foreach ($spremenljivka['grids'] AS $gkey => $grid) {
								if ($isDoubleGrid == false || ($isDoubleGrid == true && $grid['part'] == $part)) {
									foreach ($grid['variables'] AS $vkey => $variable) {
										$sequence = $variable['sequence'];
										if ($variable['other'] != 1) {
											#povprečja
											echo '<td'.$css.$break_percentRowSpan.'>';
											echo $this->formatNumber($means[$fkey][$oKey][$sequence],$this->num_digit_average,'');
											echo '</td>';
											# enote
											echo '<td class="red strong">';
											echo (int)$frequencys[$fkey][$oKey][$sequence]['validCnt'];
											echo '</td>';
											$totalMeans[$sequence] += ($means[$fkey][$oKey][$sequence]*(int)$frequencys[$fkey][$oKey][$sequence]['validCnt']);
											$totalFreq[$sequence]+= (int)$frequencys[$fkey][$oKey][$sequence]['validCnt'];
										}
							
									}
								}
							}
							echo '</tr>';
						}
					}
				}
				#dodamo še skupno sumo in povprečje
				echo '<tr>';
				echo '<td class="rsdl_bck_variable1 red">';
				echo 'Skupaj';
				echo '</td>';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					if ($isDoubleGrid == false || ($isDoubleGrid == true && $grid['part'] == $part)) {
						foreach ($grid['variables'] AS $vkey => $variable) {
								
							$sequence = $variable['sequence'];
							if ($variable['other'] != 1) {
								#povprečja
								echo '<td class="red strong">';
								$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
								echo $this->formatNumber($totalMean ,$this->num_digit_average,'');
								echo '</td>';
								# enote
								echo '<td class="red strong">';
								echo (int)$totalFreq[$sequence];
								echo '</td>';
							}
								
						}
					}
				}
				echo '</tr>';
				echo '</table>';//$forSpremenljivka['grids'][0]['variables']
						
			}
			// Zvezdica za vkljucitev v porocilo
			$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
			$spr2 = $spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
			SurveyAnalysisHelper::getInstance()->addCustomReportElement($type=9, $sub_type=0, $spr1, $spr2);
			echo '</div>';
		}
	}
	
	function displayBreakTableNumber($forSpr,$frequencys,$spremenljivka){
		#number - 7:
		#ranking - 17:
		#vsota - 18:
		#multinumber - 20:
		
		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = $this->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$means = array();
		$totalMeans = array();
		$totalFreq = array();
		foreach ($frequencys AS $fkey => $options) {
			foreach ($options AS $oKey => $option) {
				foreach ($sequences AS $sequence) {
					$txt = $this->getMeansFromKey($option[$sequence]);
					if ($txt) {
						$means[$fkey][$oKey][$sequence] = $txt;
					}
				}
			}
		}
		# ce imamo vec kot 20 kategorij,izpisujemo samo tiste ki imajo vrednosti
		$displayAll = (count($options) > 20) ? false : true;
	
		// Ce izrisujemo graf
		if($this->break_charts == 1){
		
			// Number, vsota, ranking graf
			if($tip != 20 ){
				$tableChart = new SurveyTableChart($this->sid, $this, 'break');
				$tableChart->setBreakVariables($forSpr,$frequencys,$spremenljivka);
				$tableChart->display();
			}
			
			// Multinumber graf
			else{
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				
					// Izrisujemo samo 1 graf v creportu
					if($_GET['m'] == 'analysis_creport'){
						
						if($spremenljivka['break_sub_table']['key'] == $gkey){
							$tableChart = new SurveyTableChart($this->sid, $this, 'break');
							$tableChart->setBreakVariables($forSpr,$frequencys,$spremenljivka);
							$tableChart->display();
						}
					}
					
					// Izrisujemo vse zaporedne grafe
					else{
						$spremenljivka['break_sub_table']['key'] = $gkey;
						$spremenljivka['break_sub_table']['sequence'] = $grid['variables'][0]['sequence'];
					
						$tableChart = new SurveyTableChart($this->sid, $this, 'break');
						$tableChart->setBreakVariables($forSpr,$frequencys,$spremenljivka);
						$tableChart->display();
					}
				}
			}

		}
		// Ce izrisujemo tabelo
		else{
				
			echo '<div id="'.$spremenljivka['id'].'" class="breakTableDiv">';
			echo '<br/>';
			# za multi number naredimo po skupinah
			if ($tip != 20 ) {
				$rowspan = ' rowspan="3"';
				$colspan = ' colspan="'.(2*count($sequences)).'"';

				# ali prikazujemo procente
				if ((int)$this->break_percent > 0) {
					$break_percentRowSpan = ' rowspan="2"'; 
				}
				# če ni multicheck in multi grid
				echo '<table>';
				echo '<tr>';
				echo '<th'.$rowspan.'>';
				echo '<span class="anl_variabla">';
				echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $forSpr . '\'); return false;">';
				echo $forSpremenljivka['naslov'];
				echo '('.$forSpremenljivka['variable'].')';
				echo '</a>';
				echo '</span>';
				
				echo '</th>';
				echo '<th'.$colspan.'>';
				echo '<span class="anl_variabla">';
				echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $spremenljivka['id'] . '\'); return false;">';
				echo $spremenljivka['naslov'];
				echo '('.$spremenljivka['variable'].')';
				echo '</a>';
				echo '</span>';
				echo '</th>';
				echo '</tr>';
				
				echo '<tr>';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						echo '<th class="sub" colspan="2">';
						echo $variable['naslov'];
						echo '('.$variable['variable'].')';
						echo '</th>';
					}
				}
				echo '</tr>';
				echo '<tr>';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						echo '<th class="sub">Povprečje'.$lang[''];
						echo '</th>';
						echo '<th class="sub red">Št. enot'.$lang[''];
						echo '</th>';
					}
				}
				echo '</tr>';
				
				foreach ($frequencys AS $fkey => $fkeyFrequency) {
 
					foreach ($options AS $oKey => $option) {
						if ($displayAll || $means[$fkey][$oKey] != null) {
							echo '<tr>';
							echo '<td'.$break_percentRowSpan.' class="rsdl_bck_variable1">';
							echo $forSpremenljivka['options'][$oKey];
							echo '</td>';
							$css = '';
							foreach ($spremenljivka['grids'] AS $gkey => $grid) {
								foreach ($grid['variables'] AS $vkey => $variable) {
									$sequence = $variable['sequence'];
									if ($variable['other'] != 1) {
										#povprečja
										echo '<td'.$css.$break_percentRowSpan.'>';
										echo $this->formatNumber($means[$fkey][$oKey][$sequence],$this->num_digit_average,'');
										#echo $this->formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
										echo '</td>';
										# enote
										echo '<td class="red strong">';
										echo (int)$frequencys[$fkey][$oKey][$sequence]['validCnt'];
										#echo (int)$frequencys[$fkey][$sequence]['validCnt'];
										echo '</td>';
										#$totalMeans[$sequence] += ($this->getMeansFromKey($fkeyFrequency[$sequence])*(int)$frequencys[$fkey][$sequence]['validCnt']);
										#$totalFreq[$sequence]+= (int)$frequencys[$fkey][$sequence]['validCnt'];
										$totalMeans[$sequence] += ($means[$fkey][$oKey][$sequence]*(int)$frequencys[$fkey][$oKey][$sequence]['validCnt']);
										$totalFreq[$sequence]+= (int)$frequencys[$fkey][$oKey][$sequence]['validCnt'];
									}
									
								}
								
							}
							echo '</tr>';
							if ((int)$this->break_percent) {
								echo '<tr>';
								foreach ($spremenljivka['grids'] AS $gkey => $grid) {
									foreach ($grid['variables'] AS $vkey => $variable) {
										if ($variable['other'] != 1) {
											$sequence = $variable['sequence'];
											echo '<td class="">';
											#echo (int)$frequencys[$fkey][$sequence]['validCnt'];
											$percent = 0;
											if ($frequencys[$fkey][$sequence]['validCnt'] > 0 ) {
												$percent = 100;
											}
											echo $this->formatNumber($percent,$this->num_digit_percent,'%');
											echo '</td>';
										}
									}
										
								}
								echo '</tr>';
							}
						}	
					}
				}
				#dodamo še skupno sumo in povprečje
				echo '<tr>';
				echo '<td class="rsdl_bck_variable1">';
				echo $lang[''].'Skupaj';
				echo '</td>';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
							#povprečja
							echo '<td class="red strong">';
							$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
							echo $this->formatNumber($totalMean ,$this->num_digit_average,'');
							echo '</td>';
							# enote
							echo '<td class="red strong">';
							echo (int)$totalFreq[$sequence];
							echo '</td>';
						}
						
					}
					
				}
				echo '</tr>';
				echo '</table>';
				
				
				// Zvezdica za vkljucitev v porocilo
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
				
				#xxxxx
				SurveyAnalysisHelper::getInstance()->addCustomReportElement($type=9, $sub_type=0, $spr1, $spr2);
			
			
			} else if ($tip == 20){
				# za multi number razdelimo na grupe - skupine
				$rowspan = ' rowspan="3"';
				$colspan = ' colspan="'.(2*count($spremenljivka['grids'][0]['variables'])).'"';
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					
					// Ce smo v porocilu po meri in ni prava tabela jo preskocimo
					if(isset($spremenljivka['break_sub_table']['key']) && $spremenljivka['break_sub_table']['key'] != $gkey){
						continue;
					}
					
					// Ce smo v porocilu po meri in je prava tabelo jo izpisemo brez naslova
					if(!isset($spremenljivka['break_sub_table']['sequence'])){
						echo '<br/><b>'.$lang['srv_break_table_for'];
						echo $spremenljivka['naslov'].' (';
						echo $spremenljivka['variable'].') = ';
						echo $grid['naslov'];
						echo ' ('.$grid['variable'].')';
						echo '</b>';
					}
					
					echo '<table>';
					#labele
					echo '<tr>';
					echo '<th'.$rowspan.'>';
					echo '<span class="anl_variabla">';
					echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $forSpr . '\'); return false;">';
					echo $forSpremenljivka['naslov'];
					echo '('.$forSpremenljivka['variable'].')';
					echo '</a>';
					echo '</span>';
					echo '</th>';
					
					echo '<th'.$colspan.'>';
					echo '<span class="anl_variabla">';
					echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $spremenljivka['id'] . '\'); return false;">';
					echo $spremenljivka['naslov']. ' - ';
					echo $grid['naslov'];
					echo '('.$grid['variable'].')';
					echo '</a>';
					echo '</span>';
					echo '</th>';
					echo'</tr>';
					#labele
					echo '<tr>';
					foreach ($grid['variables'] AS $vkey => $variable) {
						echo '<th class="sub" colspan="2">';
						echo $variable['naslov'];
						echo '('.$variable['variable'].')';
						echo '</th>';
					}
					echo '</tr>';
					echo '<tr>';
					foreach ($grid['variables'] AS $vkey => $variable) {
						echo '<th class="sub">Povprečje'.$lang[''];
						echo '</th>';
						echo '<th class="sub red">Št. enot'.$lang[''];
						echo '</th>';
					}
					echo '</tr>';
					$cnt=0;
					foreach ($frequencys AS $fkey => $fkeyFrequency) {
						$cbxLabel = $forSpremenljivka['grids'][0]['variables'][$cnt]['naslov'];
						$cnt++;
						foreach ($forSpremenljivka['options'] AS $oKey => $option) {
							if ($displayAll || $means[$fkey][$oKey] != null) {
								# če je osnova checkbox vzamemo samo tam ko je 1
								if(($forSpremenljivka['tip'] == 2 && $option == 1) || $forSpremenljivka['tip'] != 2 ) { 
									echo '<tr>';
									echo '<td'.$break_percentRowSpan.' class="rsdl_bck_variable1">';
									if ($forSpremenljivka['tip'] == 2) {
										echo $cbxLabel;
									} else {
										echo $forSpremenljivka['options'][$oKey];
									}
									
									#echo ' ('.$oKey.')';
									echo '</td>';
									foreach ($grid['variables'] AS $vkey => $variable) {
										$sequence = $variable['sequence'];
										#povprečje
										echo '<td>';
										echo $this->formatNumber($means[$fkey][$oKey][$sequence],$this->num_digit_average,'');
										echo '</td>';
										# enote
										echo '<td class="red strong">';
										echo (int)$frequencys[$fkey][$oKey][$sequence]['validCnt'];
										echo '</td>';
										$totalMeans[$sequence] += ($means[$fkey][$oKey][$sequence]*(int)$frequencys[$fkey][$oKey][$sequence]['validCnt']);
										$totalFreq[$sequence]+= (int)$frequencys[$fkey][$oKey][$sequence]['validCnt'];
										
									}
									echo '</tr>';
								}
							}
						}
					}
					#dodamo še skupno sumo in povprečje
					echo '<tr>';
					echo '<td class="rsdl_bck_variable1">';
					echo 'Skupaj';
					echo '</td>';
					foreach ($grid['variables'] AS $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
							#povprečja
							echo '<td class="red strong">';
							$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
							echo $this->formatNumber($totalMean ,$this->num_digit_average,'');
							echo '</td>';
							# enote
							echo '<td class="red strong">';
							echo (int)$totalFreq[$sequence];
							echo '</td>';
						}
					}
					echo '</tr>';
					echo '</table>';
					
					
					// Zvezdica za vkljucitev v porocilo
					$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
					$spr2 = $grid['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
					SurveyAnalysisHelper::getInstance()->addCustomReportElement($type=9, $sub_type=0, $spr1, $spr2);
			
					echo '<br/>';
				}
				
			}
			echo '</div>';
		}
		
	}
	
	function displayBreakTableText($forSpr,$frequencys,$spremenljivka){
		#text - 21:
		#multi text - 19:
		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = $this->_HEADERS[$forSpr];
		
		$tip = $spremenljivka['tip'];
		# izračunamo povprečja za posamezne sekvence
		$texts = array();
		$totalMeans = array();
		$totalFreq = array();
		$forSequences = array();
		$cnt = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			$forSequences[] = $fkey;
			foreach ($forSpremenljivka['options'] AS $oKey => $option) {
				foreach ($sequences AS $sequence) {
					$txt = $this->getTextFromKey($fkeyFrequency[$oKey][$sequence]);
					if ($txt) {
						$cnt[$fkey]++;
						$texts[$fkey][$oKey][$sequence] = $txt;
					}
				}
			}
		}

		# če imamo več kot 20 kategorij,izpisujemo samo tiste ki imajo vrednosti
		$displayAll = (count($forSpremenljivka['options']) > 20) ? false : true;
		
		echo '<div id="'.$spremenljivka['id'].'" class="breakTableDiv">';
		echo '<br/>';
			# za multi text razdelimo na grupe - skupine
			$rowspan = ' rowspan="2"';
			$colspan = ' colspan="'.(count($spremenljivka['grids'][0]['variables'])).'"';
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
								
				// Ce smo v porocilu po meri in ni prava tabela jo preskocimo
				if(isset($spremenljivka['break_sub_table']['sequence']) && $spremenljivka['break_sub_table']['key'] != $gkey){
					continue;
				}
				
				// Ce smo v porocilu po meri in je prava tabelo jo izpisemo brez naslova
				if(!isset($spremenljivka['break_sub_table']['sequence'])){					
					echo '<br/>';				
					if($tip != '21'){
						echo '<b>';
						echo $lang['srv_break_table_for'];
						echo $spremenljivka['naslov'].' (';
						echo $spremenljivka['variable'].') = ';
						echo $grid['naslov'];
						echo ' ('.$grid['variable'].')';
						echo '</b>';
					}
				}
				
				echo '<table>';
				#labele
				echo '<tr>';
				echo '<th'.$rowspan.'>';
				echo '<span class="anl_variabla">';
				echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $forSpr . '\'); return false;">';
				echo $forSpremenljivka['naslov'];
				echo '('.$forSpremenljivka['variable'].')';
				echo '</a>';
				echo '</span>';
				echo '</th>';
				
				echo '<th'.$colspan.'>';
				echo '<span class="anl_variabla">';
				echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $spremenljivka['id'] . '\'); return false;">';
				echo $spremenljivka['naslov']. ' - ';
				echo $grid['naslov'];
				echo '('.$grid['variable'].')';
				echo '</a>';
				echo '</span>';
				echo '</th>';
				echo'</tr>';
				#labele
				echo '<tr>';
				foreach ($grid['variables'] AS $vkey => $variable) {
					echo '<th class="sub" >';
					echo $variable['naslov'];
					echo '('.$variable['variable'].')';
					echo '('.$variable['sequence'].')';
					echo '</th>';
				}
				echo '</tr>';
				$cntCbx= 0;
				foreach ($forSequences AS $fKey => $forSequence) {
					$cbxLabel = $forSpremenljivka['grids'][0]['variables'][$cntCbx]['naslov'];
					$cntCbx++;
					foreach ($forSpremenljivka['options'] AS $oKey => $option) {
						if ($displayAll || $texts[$forSequence][$oKey] != null) { 
							if(($forSpremenljivka['tip'] == 2 && $option == 1) || $forSpremenljivka['tip'] != 2 ) {
								echo '<tr>';
								echo '<td'.$break_percentRowSpan.' class="rsdl_bck_variable1">';
								if ($forSpremenljivka['tip'] == 2) {
									echo $cbxLabel;
								} else {
									echo $forSpremenljivka['options'][$oKey];
								}
								echo '</td>';
								foreach ($grid['variables'] AS $vkey => $variable) {
									$sequence = $variable['sequence'];
									#povprečje
									echo '<td class="anl_at cll_clps" style="vertical-align:top;">';
									if (count($texts[$forSequence][$oKey][$sequence]) > 0) {
										$cnt=1;
										$count = count($texts[$forSequence][$oKey][$sequence]);
										foreach ($texts[$forSequence][$oKey][$sequence] AS $ky => $units) {
											echo '<div class="'.($cnt<=$count && $cnt>1?'anl_bt_dot ':'').('').'"style="line-height: 150%; padding:3px;">';
											echo $units['text'];
											echo '</div>';
											$cnt++;
										}
									}
									
									
									#echo $this->formatNumber($texts[$oKey][$sequence],$this->num_digit_average,'');
									echo '</td>';
									
								}
								echo '</tr>';
							}
						}
					}
				}
				echo '</table>';
				
				
				// Zvezdica za vkljucitev v porocilo
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $grid['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
				SurveyAnalysisHelper::getInstance()->addCustomReportElement($type=9, $sub_type=0, $spr1, $spr2);
				
				echo '<br/>';
			}
			
		echo '</div>';
		
	}

	function displayCrosstabTable($forSpr,$frequencys,$spremenljivka){
	
		#polovimo sekvence in spremenljivke
		// Ce smo v creportu imamo nastavljeno prvo spremenljivko posebej (ne v sessionu)
		if(isset($spremenljivka['break_sub_table']['sequence'])){
			$spr1 = $spremenljivka['creport_first_spr']['spr'];
			$seq1 = $spremenljivka['creport_first_spr']['seq'];
		}
		else{
			$spr1 = $this->sessionData['break']['spr'];
			$seq1 = $this->sessionData['break']['seq'];
		}
		$grd1 = 'undefined';
		foreach ($this->_HEADERS[$spr1]['grids'] AS $gid => $grid) {
			foreach ($grid['variables'] AS $vkey => $vrednost) {
				if ($vrednost['sequence'] == $seq1) {
					$grd1 = $gid;
				}
			}
		}
		
		$this->SurveyCrosstab->displayHi2 = false;
		$this->SurveyCrosstab->fromBreak = true;
		$this->SurveyCrosstab->showAverage = $this->break_percent;
		if ($spremenljivka['tip'] == 2 
				|| $spremenljivka['tip'] == 16
				|| $spremenljivka['tip'] == 4 
				|| $spremenljivka['tip'] == 19 
				|| $spremenljivka['tip'] == 21 
				|| ($spremenljivka['tip'] == 6 && $spremenljivka['skala'] != 0)
				|| ($spremenljivka['tip'] == 1 && $spremenljivka['skala'] != 0)){
			$this->SurveyCrosstab->showAverage = false;
		}
		$spr2 = $spremenljivka['id'];
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
						
			// Ce smo v porocilu po meri in ni prava tabela jo preskocimo
			if(isset($spremenljivka['break_sub_table']['sequence']) && $spremenljivka['break_sub_table']['key'] != $gid){
				continue;
			}
			
			// Ce smo v porocilu po meri in je prava tabelo jo izpisemo brez naslova
			if(!isset($spremenljivka['break_sub_table']['sequence']) && ($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6) && $this->break_charts != 1){	
				echo '<br/><b>'.$lang['srv_break_table_for'];
				echo $spremenljivka['naslov'].' (';
				echo $spremenljivka['variable'].') = ';
				echo $grid['naslov'];
				if ($spremenljivka['tip'] != 6) {
					echo ' ('.$grid['variable'].')';
				}
				echo '</b>';
			}
			
			$seq2 = $grid['variables'][0]['sequence'];
			$grd2 = $gid;
			$this->SurveyCrosstab->setColor(false);
			# ali rišemo povprečje po stolpcih v zadnji vrstici
			if ($spremenljivka['tip'] == 1 && $spremenljivka['skala'] == 0 ) {
				$this->SurveyCrosstab->showBottomAverage (true);
			}
					
			if($this->break_charts == 1){
				$this->SurveyCrosstab->setVariables($seq1,$spr1,'undefined'/*$grd1*/,$seq2,$spr2,$grd2);
				$this->SurveyCrosstab->fromBreak = false;
				$tableChart = new SurveyTableChart($this->sid, $this->SurveyCrosstab, 'break');
				$tableChart->break_crosstab = 1;
				$tableChart->display();
			}
			else{
				$this->SurveyCrosstab->setVariables($seq1,$spr1,$grd1,$seq2,$spr2,$grd2);
				$this->SurveyCrosstab->displayCrosstabsTable();
			}
			
			echo '<br/>';
		}

	}
	function getMeansFromKey($frequencys) {
		$sum = 0;
		if (count($frequencys['valid']) > 0) {
			foreach ($frequencys['valid'] AS $fkey => $tmp) {
			
				$sum += (int)$fkey * (int)$tmp['cnt'];
			}
		}
		$mean = (int)$frequencys['validCnt'] > 0 ? (int)$sum / (int)$frequencys['validCnt'] : 0;
		return $mean;
	}
	
	function getTextFromKey($frequencys) {
		$texts = array();
		if (count($frequencys['valid']) > 0) {
			foreach ($frequencys['valid'] AS $fkey => $tmp) {
					$texts[] = $tmp;
			}
		}
		return $texts;
	}
	
	function DisplayLink($hideAdvanced = true) {
		global $lang;
		if ($_GET['m'] == M_ANALYSIS_BREAK) {
			$css = ' black';
		} else {
			$css = ' gray';
		} 
		
		if ($hideAdvanced == false) {
			echo '<li>';
			echo '<span class="as_link'.$css.'" title="' . $lang['srv_break'] . '"><a class="gray" href="index.php?anketa='.$this->sid.'&a=analysis&m=break">' . $lang['srv_break'] . '</a></span>'."\n";
			echo '</li>';
			echo '<li class="space">&nbsp;</li>';
		}
	}

	
	// Izvoz pdf in rtf
	function displayExport ($spr, $seq) {

		if ((int)$spr > 0 && (int)$seq > 0) {
			$href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=break_izpis&anketa='.$this->sid);
			$href_rtf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=break_izpis_rtf&anketa='.$this->sid);
			$href_xls = makeEncodedIzvozUrlString('izvoz.php?b=export&m=break_izpis_xls&anketa='.$this->sid);
			echo '<script>';
			# nastavimopravilne linke
			echo '$("#secondNavigation_links a#breakDoPdf").attr("href", "'.$href_pdf.'");';
			echo '$("#secondNavigation_links a#breakDoRtf").attr("href", "'.$href_rtf.'");';
			echo '$("#secondNavigation_links a#breakDoXls").attr("href", "'.$href_xls.'");';
			# prikažemo linke
			echo '$("#hover_export_icon").removeClass("hidden");';
			echo '$("#secondNavigation_links a").removeClass("hidden");';
			echo '</script>';
		}
	}

	// Nastavitve na dnu
	function displayBottomSettings(){
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
		
		echo '<a href="#" onClick="addCustomReportAllElementsAlert(9);" title="'.$lang['srv_custom_report_comments_add_hover'].'" class="'.(!$userAccess->checkUserAccess('analysis_analysis_creport') ? 'user_access_locked' : '').'" user-access="analysis_analysis_creport" style="margin-right: 40px;"><span class="spaceRight faicon comments_creport" ></span><span class="bold">'.$lang['srv_custom_report_comments_add'].'</span></a>';
		
		echo '<a href="#" onClick="printAnaliza(\'Break\'); return false;"'.$lan_print.' class="srv_ico"><span class="faicon print icon-grey_dark_link"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=break_izpis&anketa=' . $this->sid) . '" target="_blank"'.$lan_pdf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon pdf black very_large"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=break_izpis_rtf&anketa=' . $this->sid) . '" target="_blank"'.$lan_rtf.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon rtf black very_large"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=break_izpis_xls&anketa=' . $this->sid) . '" target="_blank"'.$lan_xls.' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="faicon xls black very_large"></span></a>';								
		
		echo '<a href="#" onclick="doArchiveBreak();" title="'.$lang['srv_analiza_arhiviraj_ttl'].'" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv black very_large"></span></a>';
		echo '<a href="#" onclick="createArchiveBreakBeforeEmail();" title="'.$lang['srv_analiza_arhiviraj_email_ttl'] . '" class="'.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="faicon arhiv_mail black very_large"></span></a>';			

        echo '</div>';
        
        // Javascript s katerim povozimo urlje za izvoze, ki niso na voljo v paketu
        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){
            echo '<script> userAccessExport(); </script>';
        }
	}
	
	function setSessionPercent() {

		if (isset($_POST['break_percent'])) {
			$this->sessionData['break']['break_percent'] = ($_POST['break_percent'] == 'true') ? true : false;
			$this->break_percent = $this->sessionData['break']['break_percent']; 
		}

		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		$this->break_percent = isset($this->sessionData['break']['break_percent']) && (int)$this->sessionData['break']['break_percent'] == 1 ? true : false;
	}
	
	function setSessionCharts() {

		if (isset($_POST['break_charts'])) {
			$this->sessionData['break']['break_show_charts'] = ($_POST['break_charts'] == 1) ? 1 : 0;
			$this->break_charts = $this->sessionData['break']['break_show_charts']; 
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		$this->break_charts = isset($this->sessionData['break']['break_show_charts']) && (int)$this->sessionData['break']['break_show_charts'] == 1 ? 1 : 0;
	}
	
	function displayLinePercent() {
		global $lang;
		echo '<label><input id="break_percent" name="break_percent" onchange="change_break_percent();" type="checkbox" ' . ((int)$this->break_percent == 1 ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo $lang['srv_analiza_crosstab_odstotek_vrstice_short'].'</label>';
	}
	
	function displayLineCharts() {
		global $lang;
		
		echo '<span class="spaceLeft">';
		
		echo ' <label for="break_charts_0"><input type="radio" value="0" name="break_charts" id="break_charts_0" '.((int)$this->break_charts == 0 ? ' checked="checked" ' : '').' onClick="change_break_charts(this.value)" />'.$lang['srv_tables'].'</label>';
		echo ' <label for="break_charts_1"><input type="radio" value="1" name="break_charts" id="break_charts_1" '.((int)$this->break_charts == 1 ? ' checked="checked" ' : '').' onClick="change_break_charts(this.value)" />'.$lang['srv_charts'].'</label>';
		
		echo '</span>';
	}
}