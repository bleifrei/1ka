<?php
/**
 * @author 	Gorazd Veselič
 * @date		December 2010
 *
 */

define("EXPORT_FOLDER", "admin/survey/SurveyData");
# mejne vrednosti za barvanje residualov
define("RESIDUAL_COLOR_LIMIT1", 1.00);
define("RESIDUAL_COLOR_LIMIT2", 2.00);
define("RESIDUAL_COLOR_LIMIT3", 3.00);
define("AUTO_HIDE_ZERRO_VALUE", 20);					# nad koliko kategorij skrivamo ničelne vrednosti

@session_start();

class SurveyCrosstabs {

	public $sid;									# id ankete
	public $folder = '';							# pot do folderja

	public $db_table;								# katere tabele uporabljamo

	public $inited = false; 						# ali smo razred inicializirali

	public $_HEADERS = array();						# shranimo podatke vseh variabel

	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $SDF = null;							# class za inkrementalno dodajanje fajlov
	
	# ali obstaja datoteka z podatki in ali je zadnja verzija
	public $setUpJSAnaliza = true;					# ali nastavimo __analiza = 1 v JS

	public $_HAS_TEST_DATA = false;					# ali anketa vsebuje testne podatke

	public $_CURRENT_STATUS_FILTER = ''; 	# filter po statusih, privzeto izvažamo 6 in 5
	public $currentMissingProfile = 1; 				# Kateri Missing profil je izbran
	public $missingProfileData = null; 				# Nastavitve trenutno izbranega manjkajočega profila

	public $_CURRENT_LOOP = null;					# trenutni loop

	# CHECKBOXI
	public $crossChk0 = true; 						# checkbox frekvence
	public $crossChk1 = false; 						# checkbox odstotek po vrsticah
	public $crossChk2 = false; 						# checkbox odstotek po stolpcih
	public $crossChk3 = false; 						# checkbox skupni odstotek

	public $crossChkEC = false; 					# checkbox pričakovana frekvenca
	public $crossChkRE = false; 					# checkbox rezidual
	public $crossChkSR = false; 					# checkbox standardni rezidual
	public $crossChkAR = false; 					# checkbox prilagojen rezidual
	public $doColor = true; 						# checkbox Obarvaj celice
	public $doValues = true; 						# checkbox Prikaži vrednosti
	
	public $enableInspect = true; 						# checkbox enableInspect

	public $variabla1 = array('0'=> array('seq'=>'0','spr'=>'undefined', 'grd'=>'undefined')); # array drugih variable, kamor shranimo spr, grid_id, in sequenco
	public $variabla2 = array('0'=> array('seq'=>'0','spr'=>'undefined', 'grd'=>'undefined')); # array drugih variable, kamor shranimo spr, grid_id, in sequenco

	public $crossNavVsEno = 1; 						# ali delamo po navedbah ali po enotah
	public $displayHi2 = true; 						# ali prikazujemo hi^2
	public $fromBreak = false; 						# ali delamo crosstab iz break-a
	public $showAverage = true; 					# ali prikazujemo povprečja - v kombinaciji z from break
	public $showBottomAverage = false; 				# ali prikazujemo povprečja - po stolpcih v zadnji vrstici	
	public $showChart = false; 						# ali prikazujemo graf pod tabelo
	
	private $sessionData;							# podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	

	/* Variable so definirane v obliki:
	 * '37507_0_0_0' = x_y_z_w
	 * 	-> x => spr_id
	 * 	-> y => loop id
	 * 	-> z => grid_id
	 * 	-> y => variable id
	 *
	 */
	public $variablesList = null; 					# Seznam vseh variabel nad katerimi lahko izvajamo crostabulacije (zakeširamo)

	/**
	 * Inicializacija
	 *
	 * @param int $anketa
	 */
	function Init( $anketa = null ) {
		global $global_user_id, $site_path;

		$this->folder = $site_path . EXPORT_FOLDER.'/';

		if ((int)$anketa > 0) { # če je poadan anketa ID
			$this->sid = $anketa;
		
			SurveyAnalysisHelper::getInstance()->Init($this->sid);
			
			#inicializiramo class za datoteke
			$this->SDF = SurveyDataFile::get_instance();
			$this->SDF->init($this->sid);
			$this->headFileName = $this->SDF->getHeaderFileName();
			$this->dataFileName = $this->SDF->getDataFileName();
			$this->dataFileStatus = $this->SDF->getStatus();
			
		
			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->sid);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}
			$this->_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/';

			# Inicializiramo in polovimo nastavitve missing profila
			SurveyStatusProfiles::Init($this->sid);
			SurveyUserSetting::getInstance()->Init($this->sid, $global_user_id);
				
			SurveyMissingProfiles :: Init($this->sid,$global_user_id);
			SurveyConditionProfiles :: Init($this->sid, $global_user_id);
			SurveyZankaProfiles :: Init($this->sid, $global_user_id);
			SurveyTimeProfiles :: Init($this->sid, $global_user_id);

			SurveyDataSettingProfiles :: Init($this->sid);
			
			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->sid);
			$this->sessionData = SurveyUserSession::getData();	
				
			# nastavimo vse filtre
			$this->setUpFilter();

			# nastavimo uporabniške nastavitve
			$this->readUserSettings();
				
		} else {
			die("Napaka!");
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
		
		if ($this->headFileName !== null && $this->headFileName != '') {
			$this->_HEADERS = unserialize(file_get_contents($this->headFileName));
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


	/** funkcija vrne seznam primern variabel za crostabe
	 *
	 */
	function getVariableList() {
		if (isset($this->variablesList) && is_array($this->variablesList) && count($this->variablesList) > 0) {
			return $this->variablesList;
		} else {
			# pobrišemo array()
			$this->variablesList = array();
			# zloopamo skozi header in dodamo variable (potrebujemo posamezne sekvence)
			foreach ($this->_HEADERS AS $skey => $spremenljivka) {
				if ((int)$spremenljivka['hide_system'] == 1 && in_array($spremenljivka['variable'],array('email','ime','priimek','telefon','naziv','drugo'))) {
					continue;
				}
				
				$tip = $spremenljivka['tip'];
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
				) {
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
	}


	/** Prikazuje filtre
	 *
	 */
	function DisplayFilters() {
		if ($this->dataFileStatus == FILE_STATUS_SRV_DELETED || $this->dataFileStatus == FILE_STATUS_NO_DATA){
			return false;
		}

		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->sid);
		# izrišemo desne linke do posameznih nastavitev
		$SSH -> displayAnalizaRightOptions(M_ANALYSIS_CROSSTAB);
		
		if ($this->setUpJSAnaliza == true) {
			echo '<script>
					        window.onload = function() {
				            __analiza = 1;
				            __tabele = 1;
				        }
				        </script>';
		}
	}

	function DisplayLinks() {
		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->sid);
		$SSH -> displayAnalizaSubNavigation();
	}
	
	/** Prikazuje podatke analize
	 *
	 */
	function Display() {
		# preberemo prednastavljene variable iz seje, če obstajajo
		$this->presetVariables();
	
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA
		|| $this->dataFileStatus == FILE_STATUS_NO_FILE
		|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED){
			return false;
		}

		global $lang;

		# polovimo nastavtve missing profila
		//$this->missingProfileData = SurveyMissingProfiles::getProfile($this->currentMissingProfile);

		echo '<div id="crosstab_drobdowns">';
		$resultIsCheckbox = $this->DisplayDropdows();
		echo '</div>';
		echo '<div id="div_crossCheck" class="floatLeft spaceLeft">' ;
		$this->displayLinePercent();
		$this->displayResidual();
		$this->displayShowChart();
		if ( $resultIsCheckbox['is_check']) {
			echo '<div id="crossNavedbeVsENote">';
			echo '<input type="radio" name="crossNavVsEno" id="crossNavVsEno0" vlaue="0" '.($this->crossNavVsEno == 0 ? ' checked="checked" ' : '' ).' onchange="change_crosstab(); return false;" autocomplete="off">'.
					'<label for="crossNavVsEno0">'.$lang['srv_analiza_crosstab_navedbe'].'</label>';
			echo '<input type="radio" name="crossNavVsEno" id="crossNavVsEno1" vlaue="1" '.($this->crossNavVsEno == 1? ' checked="checked" ' : '' ).' onchange="change_crosstab(); return false;" autocomplete="off">'.
					'<label for="crossNavVsEno1">'.$lang['srv_analiza_crosstab_enote'].'</label>';
			echo '</div>';
		}
		echo '</div>';
		
		
		$this->displayExport();
		$this->displayCrosstabCheckboxes();
		echo '<div id="crosstab_table">';
		$this->displayCrosstabsTables();
		echo '</div>';
	}

	function DisplayDropdows() {
		global $lang;
		$variables = $this->getVariableList();
		$multiple = true;

		echo '<div id="crossLeftHolder" >';

		# iz header datoteke preberemo spremenljivke
		#js: $("#crosstab_variable_1, #crosstab_variable_2").live('click', function() {})
		if (count($this->variabla1) > 0) {
			$br=null;
			echo $lang['srv_crosstab_label1'].'<br/>';
			if ((int)$this->variabla1['0']['seq'] > 0) {
				echo '<span class="pointer" id="crosstab_add_new" onclick="add_new_variable(\'1\');"><span class="faicon add small icon-as_link" title=""></span></span>';
			}
				
			foreach($this->variabla1 AS $_key => $variabla1) {
				echo $_br;
				echo '<span id="v1_'.$_key.'">';
				
				echo '<select name="crosstab_variable_1" id="crosstab_variable_1" onchange="change_crosstab(); return false;" autocomplete="off">';

				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ( $variabla1['seq'] == null || $variabla1['seq'] == 0 ) {
					echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_izberi_prvo'] . '</option>';
				}
				foreach ($variables as $variable) {
					echo '<option value="' . $variable['sequence'] . '" spr_id="'.$variable['spr_id'].'" '
					. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( ($variabla1['seq'] > 0 &&$variabla1['seq'] == $variable['sequence']) ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $variable['variableNaslov'] . '</option>';

				}

				echo '</select>';
				if (count($this->variabla1) > 1) {
					echo '<span class="pointer" id="crosstab_remove" onclick="crs_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
				} else {
					#echo '<span class="space_crosstab_new">&nbsp;</span>';
				}

				$_br = '<br/><span class="space_crosstab_new">&nbsp;</span>';
				echo '</span>';
			}
			$_br = null;
		}

		echo '</div>';
		echo '<div id="crossImgHolder">';
		echo '<br/>';
		if ($this->isSelectedBothVariables()) {
			echo '<span class="faicon replace icon-as_link" title="'.$lang['srv_replace'].'" onclick="change_crosstab(\'rotate\');return false;" />';
		} else {
			echo '<span class="faicon replace icon-grey_normal" title="'.$lang['srv_replace'].'" />';
		}
		echo '</div>';

		echo '<div id="crossRightHolder">';
		echo $lang['srv_crosstab_label2'].'<br/>';
		# za vsako novo spremenljivko 2 nardimo svoj select
		if (count($this->variabla2) > 0) {
			if ((int)$this->variabla1['0']['seq'] > 0) {
				echo '<span class="pointer" id="crosstab_add_new" onclick="add_new_variable(\'2\');"><span class="faicon add small icon-as_link" title="'.'"></span></span>';
			}
				
			foreach($this->variabla2 AS $_key => $variabla2) {
				echo $_br;
				echo '<span id="v2_'.$_key.'">';
			
				echo '<select name="crosstab_variable_2" id="crosstab_variable_2" onchange="change_crosstab(); return false;" autocomplete="off"'
				. ((int)$this->variabla1['0']['seq'] > 0 ? '' : ' disabled="disabled" ')
				.'>';

				# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
				if ((int)$this->variabla1['0']['seq'] == 0) {
					echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_najprej_prvo'] . '</option>';
				} else {
					# če druga variabla ni izbrana dodamo tekst za izbiro druge variable
					if ($variabla2['seq'] == null || $variabla2['seq'] == 0) {
						echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_izberi_drugo'] . '</option>';
					}
				}
					
				foreach ($variables as $variable) {
					echo '<option value="' . $variable['sequence'] . '" spr_id="'.$variable['spr_id'].'" '
					. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
					. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
					. ( $variabla2['seq'] > 0 && $variabla2['seq'] == $variable['sequence'] ? ' selected="selected" ' : '')
					. '> '
					. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
					. $variable['variableNaslov'] .'</option>';

				}
				echo '</select>';
				if (count($this->variabla2) > 1) {
					echo '<span class="pointer" id="crosstab_remove" onclick="crs_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
				} else {
					echo '<span class="space_crosstab_new">&nbsp;</span>';
				}

				$_br = '<br/><span class="space_crosstab_new">&nbsp;</span>';
				echo '</span>';
			}
		}
		echo '</div>';

		# če je katera od variabel checkbox, ponudimo možnodt izbire ali po enotah ali po navedbah
		$is_check = false;
		if (count($this->variabla2) > 0) {
			foreach ($this->variabla2 AS $key => $var) {
				$spr_tip = $this->_HEADERS[$var['spr']]['tip'];
				if ( $spr_tip == 2 || $spr_tip == 16 ) {
						$is_check = true;
				}
			}
		}
		if (count($this->variabla1) > 0 && $is_check == false ) { # če še ni bil checkbox
			foreach ($this->variabla1 AS $key => $var) {
				$spr_tip = $this->_HEADERS[$var['spr']]['tip'];
				if ( $spr_tip == 2 || $spr_tip == 16 ) {
						$is_check = true;
				}
			}
		}
		
		return array("is_check" => $is_check);
	}

	function addNewVariable() {
		global $lang;
		$variables = $this->getVariableList();
		$multiple = true;
			
		$which = $_POST['which'];


		if ($which == '1') {
			echo '<br/>';
			echo '<span class="space_crosstab_new">&nbsp;</span>';
			echo '<select name="crosstab_variable_1" id="crosstab_variable_1" onchange="change_crosstab(); return false;" autocomplete="off">';
			# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
			if ( $variabla1['seq'] == null || $variabla1['seq'] == 0 ) {
				echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_izberi_more'] . '</option>';
			}
				
			foreach ($variables as $variable) {
				echo '<option value="' . $variable['sequence'] . '" spr_id="'.$variable['spr_id'].'" '
				. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				. $variable['variableNaslov'] .$variable['sequence']. '</option>';

			}
			echo '</select>';
			echo '<span class="pointer" id="crosstab_remove" onclick="crs_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
				
		} else {
			echo '<br/>';
			echo '<span class="space_crosstab_new">&nbsp;</span>';
			echo '<select name="crosstab_variable_'.$which.'" id="crosstab_variable_'.$which.'" onchange="change_crosstab(); return false;" autocomplete="off"'
			.'>';
				
			# ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
			if ((int)$this->variabla1['0']['seq'] > 0) {
				echo '<option value="0" selected="selected" >'. $lang['srv_analiza_crosstab_najprej_prvo'] . '</option>';
			} else {
				# če druga variabla ni izbrana dodamo tekst za izbiro druge variable
				echo '<option value="0" selected="selected">'. $lang['srv_analiza_crosstab_izberi_more'] . '</option>';
			}

			foreach ($variables as $variable) {
				echo '<option value="' . $variable['sequence'] . '" spr_id="'.$variable['spr_id'].'" '
				. ( isset($variable['grd_id']) ? ' grd_id="'.$variable['grd_id'].'" ' : '')
				. (( (int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
				. '> '
				. ( (int)$variable['sub'] == 0 ? '' : ( (int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;' ))
				. $variable['variableNaslov'] .$variable['sequence']. '</option>';

			}
			echo '</select>';
			echo '<span class="pointer" id="crosstab_remove" onclick="crs_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
		}
	}
	/**
	 * @desc prikaze tabele za crosstab
	 */
	function displayCrosstabCheckboxes () {
		global $lang;

		echo '<div id="div_color_residual_legend" '.($this->isSelectedBothVariables() && $this->doColor ? '' : ' class="hidden"').'>' ;
		
		echo '<span id="span_color_residual_legend" class="floatLeft">';
		echo '<span id="span_color_residual_legend1" class="floatLeft">';
		echo '<span class="floatLeft">';
		echo '<label>'.$lang['srv_analiza_crosstab_adjs_residual_short'].'</label>';
		echo '</span>';
		echo '<span class="floatLeft">';
		echo '<table id="tbl_color_residual_legend" >';
		echo '<tr>';
		echo '<td style="width:15px !important; text-align: center !important; font-weight: bold !important;">-</td>';
		echo '<td class="rsdl_bck6" title="'.$lang['srv_crosstab_residual_1'].'">&nbsp;</td>';
		echo '<td class="rsdl_bck5" title="'.$lang['srv_crosstab_residual_2'].'">&nbsp;</td>';
		echo '<td class="rsdl_bck4" title="'.$lang['srv_crosstab_residual_3'].'">&nbsp;</td>';
		echo '<td class="rsdl_bck1" title="'.$lang['srv_crosstab_residual_4'].'">&nbsp;</td>';
		echo '<td class="rsdl_bck2" title="'.$lang['srv_crosstab_residual_5'].'">&nbsp;</td>';
		echo '<td class="rsdl_bck3" title="'.$lang['srv_crosstab_residual_6'].'">&nbsp;</td>';
		echo '<td style="width:15px !important; text-align: center !important; font-weight: bold !important;">+</td>';
		//echo '<td style="width:40px !important; text-align: center !important;"><span id="span_rsdl_legend_togle" class="as_link">'.$lang['srv_more'].'</span></td>';
		echo '</tr>';
		echo '</table>';
		echo '</span>';
		echo '<span class="floatLeft" style="padding-top:2px;">';
		echo Help :: display('srv_crosstab_residual');
		echo '</span>';
		
		echo '</span>';
		
		echo '<span id="span_color_residual_legend2" class="floatLeft displayNone">';
		echo '<span class="floatLeft">';
		echo '<label></label>';
		echo '</span>';
		echo '<span class="floatLeft">';
		echo '<table id="tbl_color_residual" class="residual">';
		echo '<tr><td>'.$lang['srv_analiza_crosstab_adjs_residual_long'].':&nbsp;&nbsp;&nbsp;&nbsp;</td><th>+</th><th>-</th></tr>';
		echo '<tr><td class="anl_al">&nbsp;&nbsp;'.$lang['srv_crosstab_residual_3_0'].'</td><td class="rsdl_bck1 anl_dash_ba" title="'.$lang['srv_crosstab_residual_4'].'">&nbsp;</td><td class="rsdl_bck4 anl_dash_bt anl_dash_br anl_dash_bb" title="'.$lang['srv_crosstab_residual_3'].'">&nbsp;</td></tr>';
		echo '<tr><td class="anl_al">&nbsp;&nbsp;'.$lang['srv_crosstab_residual_2_0'].'</td><td class="rsdl_bck2 anl_dash_bl anl_dash_bb" title="'.$lang['srv_crosstab_residual_5'].'">&nbsp;</td><td class="rsdl_bck5 anl_dash_br anl_dash_bb" title="'.$lang['srv_crosstab_residual_2'].'">&nbsp;</td></tr>';
		echo '<tr><td class="anl_al">&nbsp;&nbsp;'.$lang['srv_crosstab_residual_1_0'].'</td><td class="rsdl_bck3 anl_dash_bl anl_dash_bb" title="'.$lang['srv_crosstab_residual_6'].'">&nbsp;</td><td class="rsdl_bck6 anl_dash_br anl_dash_bb" title="'.$lang['srv_crosstab_residual_1'].'">&nbsp;</td></tr>';
		echo '</table>';
		//echo '<span class="residual_link"><a href="http://www.1ka.si/db/19/308/Pogosta%20vprasanja/Kaj_pomenijo_residuali/?&p1=226&p2=735&p3=789&p4=0&p5=0&id=789&from1ka=1" target="_blank">'.$lang['srv_residual_link_faq'].'</a></span>';
		echo '</span>';
		echo '<span id="span_rsdl_legend_togle" class="floatLeft spaceLeft as_link">'.$lang['srv_less'].'</span>';
		echo '<span class="floatLeft spaceLeft" style="padding-top:0px;">';
		echo Help :: display('srv_crosstab_residual');
		echo '</span>';		

		echo '</span>';
			
		
		echo '<br />';
		echo '<span id="span_color_residual_legend3" class="floatLeft '.($this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR == 4 ? '' : ' displayNone"').'" style="margin-top:10px; padding-left:18px;">';
		echo '<span class="floatLeft">';
		echo '<label>'.$lang['srv_analiza_crosstab_residuals'].'</label>';
		echo '</span>';
		echo '<span class="floatLeft">';
		echo '<table id="tbl_color_residual_legend" >';
		echo '<tr>';
		echo '<td style="width:10px !important; text-align: center !important; font-weight: bold !important;"></td>';
		echo '<td class="crossCheck_EC" title="'.$lang['srv_analiza_crosstab_expected_count'].'">&nbsp;</td>';
		echo '<td class="crossCheck_RE" title="'.$lang['srv_analiza_crosstab_residual'].'">&nbsp;</td>';
		echo '<td class="crossCheck_SR" title="'.$lang['srv_analiza_crosstab_stnd_residual'].'">&nbsp;</td>';
		echo '<td class="crossCheck_AR" title="'.$lang['srv_analiza_crosstab_adjs_residual'].'">&nbsp;</td>';
		echo '<td style="width:10px !important; text-align: center !important; font-weight: bold !important;"></td>';
		echo '</tr>';
		echo '</table>';
		echo '</span>';
		echo '<span class="floatLeft" style="padding-top:2px;">';
		echo Help :: display('srv_crosstab_residual2');
		echo '</span>';
		
		echo '</span>';
	
		
		echo '</span>';
		
		echo '</div>';
		echo '<br class="clr"/>';
	}

	public function displayCrosstabsTables() {
		
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		SurveyTimeProfiles :: printIsDefaultProfile();

		# če imamo filter ifov ga izpišemo
		SurveyConditionProfiles:: getConditionString();

		# če imamo filter spremenljivk ga izpišemo
		SurveyVariablesProfiles:: getProfileString(true);
		
		# če imamo rekodiranje
		$SR = new SurveyRecoding($this->sid);
		$SR -> getProfileString();
		
		# preverimo ali imamo izbrano tretjo variablo
		if ( $this->variabla_third == null || (int)$this->variabla_third == 0) {
			# tretja variabla ni izbrana, nardimo navadne loope če obstajajo
			$this->_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
		} else {
			# tretja variabla je izbrana, zamenjamo loope z tretjo variablo
			$this->_LOOPS = SurveyZankaProfiles::setLoopsForCrostabs($this->variabla_third);
		}

		if (count($this->_LOOPS) > 0) {
			# če mamo zanke
			foreach ( $this->_LOOPS AS $loop) {

				$this->_CURRENT_LOOP = $loop;
				echo '<h2>'.$lang['srv_zanka_note'].$loop['text'].'</h2>';
				$this->displayCrosstabsTable();
				echo '<br/>';
			}
		} else {
			$this->displayCrosstabsTable();
		}
	}

	public function displayCrosstabsTable() {
		global $lang;
		global $admin_type;

		if ($this->getSelectedVariables(1) !== null && $this->getSelectedVariables(2) !== null) {
			$variables1 = $this->getSelectedVariables(2);
			$variables2 = $this->getSelectedVariables(1);
			$counter = 0;
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {

					$crosstabs = null;
					$crosstabs_value = null;
						
					$crosstabs = $this->createCrostabulation($v_first, $v_second);

					$crosstabs_value = $crosstabs['crosstab'];
						
					# podatki spremenljivk
					$spr1 = $this->_HEADERS[$v_first['spr']];
					$spr2 = $this->_HEADERS[$v_second['spr']];

					$grid1 = $spr1['grids'][$v_first['grd']];
					$grid2 = $spr2['grids'][$v_second['grd']];
						
					#število vratic in število kolon
					$cols = count($crosstabs['options1']);
					$rows = count($crosstabs['options2']);

					# ali prikazujemo vrednosti variable pri spremenljivkah
					$show_variables_values = $this->doValues;

					# nastavitve oblike
					if (($this->crossChk1 || $this->crossChk2 || $this->crossChk3) && ($this->crossChkEC || $this->crossChkRE || $this->crossChkSR || $this->crossChkAR)) {
						# dodamo procente in residuale
						$rowSpan = 3;
						$numColumnPercent = $this->crossChk1 + $this->crossChk2 + $this->crossChk3;
						$numColumnResidual = $this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR;
						$tblColumn = max($numColumnPercent,$numColumnResidual);
					} else if ($this->crossChk1 || $this->crossChk2 || $this->crossChk3) {
						# imamo samo procente
						$rowSpan = 2;
						$numColumnPercent = $this->crossChk1 + $this->crossChk2 + $this->crossChk3;
						$numColumnResidual = 1;
						$tblColumn = $numColumnPercent;
					} else if ($this->crossChkEC || $this->crossChkRE || $this->crossChkSR || $this->crossChkAR) {
						# imamo samo residuale
						$rowSpan = 2;
						$numColumnPercent = 1;
						$numColumnResidual = $this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR;
						$tblColumn = $numColumnResidual;
					} else {
						#prikazujemo samo podatke
						$rowSpan = 1;
						$numColumnPercent = 1;
						$numColumnResidual = 1;
						$tblColumn = 1;
					}

					# za multicheckboxe popravimo naslov, na podtip
					$sub_q1 = null;
					$sub_q2 = null;
					if ($spr1['tip'] == '6' || $spr1['tip'] == '7' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '18' || $spr1['tip'] == '19' || $spr1['tip'] == '20' || $spr1['tip'] == '21' ) {
						foreach ($spr1['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_first['seq']) {
									$sub_q1 = '<span class="anl_variabla'.$sccFloat.'">';
									$sub_q1 .= '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $v_first['spr'] . '\'); return false;">';
									$sub_q1 .= strip_tags($spr1['naslov']);
									if ($show_variables_values == true ) {
										$sub_q1 .= '<span class="anl_variabla'.$sccFloat.'">';
										
										$sub_q1 .= '&nbsp;('.strip_tags($spr1['variable']).')';
										
										$sub_q1 .= '</span>';
									}
									if ($spr1['tip'] == '16') {
										$sub_q1 .= '<br/>' . strip_tags($grid1['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid1['variable']) . ')' : '');
									} else {
										$sub_q1 .= '<br/>' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
									$sub_q1 .= '</a>';
									$sub_q1 .=  '</span>' . NEW_LINE;
								}
							}
						}
					}
					if ($sub_q1 == null) {
						$sub_q1 = '<span class="anl_variabla'.$sccFloat.'">';
						$sub_q1 .= '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $v_first['spr'] . '\'); return false;">';
						$sub_q1 .=  strip_tags($spr1['naslov']);
						$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
						$sub_q1 .=  '</a>';
						$sub_q1 .=  '</span>' . NEW_LINE;
					}
					if ($spr2['tip'] == '6' || $spr2['tip'] == '7' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '18' || $spr2['tip'] == '19' || $spr2['tip'] == '20' || $spr2['tip'] == '21') {
						foreach ($spr2['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_second['seq']) {
									$sub_q2 = '<span class="anl_variabla'.$sccFloat.'">';
									$sub_q2 .= '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopupiza(\'' . $v_second['spr'] . '\'); return false;">';
									$sub_q2 .= strip_tags($spr2['naslov']);
									if ($show_variables_values == true) {
										$sub_q2 .= '<span class="anl_variabla'.$sccFloat.'">';

										$sub_q2 .= '&nbsp;('.strip_tags($spr2['variable']).')';
										
										$sub_q2 .= '</span>';
									}
									if ($spr2['tip'] == '16') {
										$sub_q2.= '<br/>' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid2['variable']) . ')' : '');
									} else {
										$sub_q2.= '<br/>' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
									$sub_q2 .= '</a>';
									$sub_q2 .= '</span>' . NEW_LINE;
								}
							}
						}
					}
					if ($sub_q2 == null) {
						$sub_q2 = '<span class="anl_variabla'.$sccFloat.'">';
						$sub_q2 .= '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $v_second['spr'] . '\'); return false;">';
						$sub_q2 .= strip_tags($spr2['naslov']);
						$sub_q2 .= ($show_variables_values == true ? '&nbsp;('.strip_tags($spr2['variable']).')' : '');
						$sub_q2 .= '</a>';
						$sub_q2 .= '</span>' . NEW_LINE;
					}
					# izrišemo tabelo
					# najprej izrišemo naslovne vrstice
					#echo '<table class="anl_tbl_crosstab fullWidth fullHeight">';
					echo $_br.'<br/>';
#					$_br = '<br/>';
#					echo '<div class="floatLeft">'.$sub_q1. '</div><div class="floatLeft spaceLeft spaceRight"> ==&gt; </div><div class="floatLeft">'. $sub_q2.'</div><br class="clr" />';

					#Zadnja kolona: Če imamo vodoravno checkboxe in gledamo enote, potem kolono s summo malo razmaknemo
					if ( $crosstabs['isCheckbox']['spr1'] == true && $this->crossNavVsEno == true) {
						$addVerticalSpace = 1;
					} else {
						$addVerticalSpace = 0;
					}
						
					# hi2
					if ($this->displayHi2 == true) {
						echo '&#x3A7;<sup>2</sup> = ';
						echo $this->formatNumber($crosstabs['hi2'], 3, '');
					}
					
					echo '<table class="anl_tbl_crosstab" style="padding:0px; margin:0px; margin-top:10px;"'
					. ' sq1="'.$v_first['seq'].'" sp1="'.$v_first['spr'].'" gd1="'.$v_first['grd'].'" sq2="'.$v_second['seq'].'" sp2="'.$v_second['spr'].'" gd2="'.$v_second['grd'].'" >';
					
					if ($this->fromBreak == false) {
						echo '<colgroup>';
						#echo '<col style="width:auto; min-width:150px;" />';
						echo '<col style="width:auto; min-width:100px;" />';
						if (count($crosstabs['options1']) > 0 ) {
							$_width_percent = round(100 / count($crosstabs['options1'],2));
							foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
								echo '<col style="width:'.$_width_percent.'%;" />';
							}
						}
						if ($addVerticalSpace == 1) {
							echo '<col style="width:10px;" />';
						}
						echo '<col style="width:auto;" />';
						echo '</colgroup>';
					}
					echo '<tr>';
					echo '<td class="anl_bt anl_bl anl_ac rsdl_bck_title ctbCll"  rowspan="2" >';
					#if ($cntY == 1) {
						# ime variable
						#echo '<td rowspan="' . $rows . '">';
					echo $sub_q2;
					#echo '</td>';
					#}
					echo '</td>';
					echo '<td class="anl_bt anl_bl anl_ac rsdl_bck_title ctbCll" colspan="' . $cols . '" >';
					echo $sub_q1;
					echo '</td>';
					
					echo '<td class="anl_bl">&nbsp;</td>';
					if ($this->fromBreak == true && $this->showAverage == true) {
						# če smo v break-u dodamo še povprečja
						echo '<td class="">&nbsp;</td>';
					}
					echo '</tr>';
					echo '<tr>';
					$col_cnt=0;
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							$col_cnt++;
							#ime variable
							//$css_backX = 'rsdl_bck_variable'.($col_cnt & 1);
							$css_backX = ' rsdl_bck_variable1';
							echo '<td class="anl_bt anl_bl anl_ac'.$css_backX.' ctbCll" >';
							echo  $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't' && $show_variables_values == true) {
								if ($crossVariabla['vr_id'] == null  ) {
									echo '<br/> ( '.$ckey1.' )';
								} else {
									echo '<br/> ( '.$crossVariabla['vr_id'].' )';
								}
							}
							echo '</td>';
						}
					}
					$col_cnt++;
					//$css_backX = 'rsdl_bck_variable'.($col_cnt & 1);
					if ($addVerticalSpace == 1) {
						echo '<td class="anl_bl">&nbsp;</td>';
					}
					$css_backX = ' rsdl_bck_variable1';
						
					echo '<td class="anl_bl anl_bt anl_br anl_ac red anl_ita anl_bck_text_0'.$css_backX.' ctbCll">' . $lang['srv_analiza_crosstab_skupaj'] . '</td>';
					if ($this->fromBreak == true && $this->showAverage == true) {
						# če smo v break-u dodamo še povprečja
						echo '<td class="anl_bl anl_bt anl_br anl_ac anl_ita anl_bck_text_0'.$css_backX.' ctbCll">' . $lang['srv_analiza_crosstab_average'] . '</td>';
							
					}
					echo '</tr>';

					$cntY = 0;
					if (count($crosstabs['options2']) > 0) {
						foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
							$cntY++;
							echo '<tr>';
								
							
							//$css_backY = 'rsdl_bck_variable'.($cntY & 1);
							$css_backY = ' rsdl_bck_variable1';
								
							echo '<td class="anl_bt anl_bl anl_ac'.$css_backY.' ctbCll">';
								
							echo $crossVariabla2['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla2['type'] !== 't' && $show_variables_values == true ) {
								if ($crossVariabla2['vr_id'] == null) {
									echo '<br/> ( '.$ckey2.' )';
								} else {
									echo '<br/> ( '.$crossVariabla2['vr_id'].' )';
								}

							}
							echo '</td>';

							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								echo '<td class="ct_in_cell'.($this->enableInspect && ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? ' ct_inspect' : '').'" k1="'.$ckey1.'" k2="'.$ckey2.'" n1="'.$crossVariabla1['naslov'].'" n2="'.$crossVariabla2['naslov'].'" v1="'.$crossVariabla1['vr_id'].'" v2="'.$crossVariabla2['vr_id'].'">';
								# celica z vebino
								{
									# prikazujemo eno ali več od: frekvenc, odstotkov, residualov
									echo '<table class="ct_in_tbl">';
									if ($this->crossChk0) {
										# izpišemo frekvence crostabov
										echo '<tr>';
										echo '<td class="anl_ac '.($crosstabs['color'][$ckey1][$ckey2]).' ctbCll">';
										echo ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
										#								.$crossTab[$crossVariabla1[cell_id]][$ckey2]
										#

										echo '</td>';
										echo '</tr>';
									}
										
									if ($this->crossChk1 || $this->crossChk2 || $this->crossChk3) {
										# sirina celice v %
										if ( ($this->crossChk1 + $this->crossChk2 + $this->crossChk3) == 3 )
										$css_width = ' ctb_w33p';
										elseif (($this->crossChk1 + $this->crossChk2 + $this->crossChk3) == 2 )
										$css_width = ' ctb_w50p';
										else
										$css_width = '';
										$css_bt = ( $this->crossChk0 ) ? 'anl_dash_bt' : '';
										# izpisemo procente
										echo '<tr>';
										echo '<td class="'.$css_bt.'">';

										echo '<table class="anl_tbl_crosstab fullWidth fullHeight" style="padding:0px; margin:0px;">';
										echo '<tr>';
										$col=0;
											
										if ($this->crossChk1) {
											#procent vrstica
											$col++;

											$css_color = ($this->doColor == 'true') ? 'ctbChck_sp1' : 'ctbChck_sp0';
											$css_br = $numColumnPercent > $col ? ' anl_dash_br' : '';
											echo '<td class="'.$css_color.$css_br.$css_width.' ctbCll">';
											echo $this->formatNumber($this->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
											echo '</td>';
										}
										if ($this->crossChk2) {
											#procent stolpec
											$col++;
											$css_br = $numColumnPercent > $col ? ' anl_dash_br' : '';
											$css_color = ($this->doColor == 'true') ? 'ctbChck_sp2' : 'ctbChck_sp0';
											echo '<td class="'.$css_color.$css_br.$css_width.' ctbCll">';

											echo $this->formatNumber($this->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
											echo '</td>';
										}
										if ($this->crossChk3) {
											#procent skupni
											$col++;
											$css_br = $numColumnPercent > $col ? ' anl_dash_br' : '';
											$css_color = ($this->doColor == 'true') ? 'ctbChck_sp3' : 'ctbChck_sp0';
											echo '<td'.$css_br.$css_width.' class="'.$css_color.$css_br.' ctbCll">';

											echo $this->formatNumber($this->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
											echo '</td>';
										}
										echo '</tr>';
										echo '</table>';

										echo '</td>';
										echo '</tr>';
									}
									# izpisemo residuale
									if ($this->crossChkEC || $this->crossChkRE || $this->crossChkSR || $this->crossChkAR) {
										# sirina celice v %
										if ( ($this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR) == 4 )
										$css_width = ' ctb_w25p';
										elseif ( ($this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR) == 3 )
										$css_width = ' ctb_w33p';
										elseif ( ($this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR) == 2 )
										$css_width = ' ctb_w50p';
										else
										$css_width = '';
										$css_bt = ( $this->crossChk0 || ($this->crossChk1 && $this->crossChk2 && $this->crossChk3)) ? 'anl_dash_bt' : '';
										echo '<tr>';

										echo '<td class="'.$css_bt.'" style="padding:0px 0px;">';
										echo '<table class="anl_tbl_crosstab fullWidth fullHeight" style="padding:0px; margin:0px;">';
										echo '<tr>';
										$col=0;
											
										if ($this->crossChkEC) {
											$col++;
											$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
											$css_color = ($this->doColor == 'true') ? 'crossCheck_EC' : 'ctbChck_sp0';
											echo '<td class="'.$css_color.$css_br.$css_width.' ctbCll">';
											echo $this->formatNumber($crosstabs['exC'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
											echo '</td>';
										}
										if ($this->crossChkRE) {
											$col++;
											$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
											$css_color = ($this->doColor == 'true') ? 'crossCheck_RE' : 'ctbChck_sp0';
											echo '<td class="'.$css_color.$css_br.$css_width.' ctbCll">';
											echo $this->formatNumber($crosstabs['res'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
											echo '</td>';
										}
										if ($this->crossChkSR) {
											$col++;
											$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
											$css_color = ($this->doColor == 'true') ? 'crossCheck_SR' : 'ctbChck_sp0';
											echo '<td class="'.$css_color.$css_br.$css_width.' ctbCll">';
											echo $this->formatNumber($crosstabs['stR'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
											echo '</td>';
										}
										if ($this->crossChkAR) {
											$col++;
											$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
											$css_color = ($this->doColor == 'true') ? 'crossCheck_AR' : 'ctbChck_sp0';
											echo '<td class="'.$css_color.$css_br.$css_width.' ctbCll">';
											echo $this->formatNumber($crosstabs['adR'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
											echo '</td>';
										}
										echo '</tr>';
										echo '</table>';
										echo '</td>';
										echo '</tr>';
									}
									echo '</table>';
								}
								# konec celice z vsebino
								echo '</td>';
							}
							# če mamo checkboxe in sumo malo razmaknemo
							if ($addVerticalSpace == 1) {
								echo '<td class="anl_bl">&nbsp;</td>';
							}
							
							// vedno rišemo zadnji stolpec.
							echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0 anl_bb" >';
							echo '<table class="anl_tbl_crosstab fullWidth fullHeight" style="padding:0px; margin:0px;">';
							if ($this->crossChk0) {
								echo '<tr>';
								echo '<td class="anl_ac ctbCll crostabSuma"  colspan="' . ( $this->crossChk1 + $this->crossChk2 + $this->crossChk3 ).'">';
								# suma po vrsticah
								echo (int)$crosstabs['sumaVrstica'][$ckey2];
								echo '</td>';
								echo '</tr>';
							}
							if ($this->crossChk1 || $this->crossChk2 || $this->crossChk3) {
								if (($this->crossChk1 + $this->crossChk2 + $this->crossChk3) == 3) {
									$css_width = ' ctb_w33p';
								} else if (($this->crossChk1 + $this->crossChk2 + $this->crossChk3) == 2) {
									$css_width = ' ctb_w50p';
								} else {
									$css_width = '';
								}
								$css_bt = ( $this->crossChk0 ) ? ' anl_dash_bt' : '';
								# suma po vrsticah v procentih
								echo '<tr>';
								if ($this->crossChk1) {
									$css_color = ($this->doColor == 'true') ? ' ctbChck_sp1' : 'ctbChck_sp0';
									echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.$css_width.'">';
									echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									echo '</td>';
								}
								if ($this->crossChk2) {
									$css_color = ($this->doColor == 'true') ? ' ctbChck_sp2' : 'ctbChck_sp0';
									$css_border = ($this->crossChk1 ? ' anl_dash_bl ' : '');
									echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.$css_border.$css_width.'">';
									echo $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									echo '</td>';
								}
								if ($this->crossChk3) {
									$css_color = ($this->doColor == 'true') ? ' ctbChck_sp3' : 'ctbChck_sp0';
									$css_border = ($this->crossChk1 || $this->crossChk2 ? ' anl_dash_bl ' : '');
									echo '<td class="anl_ac'.$css_color.$css_bt.$css_border.$css_width.' ctbCll">';
									echo $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									echo '</td>';
								}
								echo '</tr>';
							}
								
							echo '</table>';
								
							echo '</td>';
							if ($this->fromBreak == true && $this->showAverage == true) {
								# če smo v break dodamo še povprečja
								echo '<td class="anl_ac anl_bl anl_bt anl_br anl_bb rsdl_bck_variable1" >';
								echo $this->formatNumber( $crosstabs['avgVrstica'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'), '');
								echo '</td>';
							}
							echo '</tr>';
						}
					}
					#Zadnja vrstica. Če imamo navpično checkboxe in gledamo enote, potem vrstico z summo malo razmaknemo
					$cssBT = 'anl_bt';
					if ( $crosstabs['isCheckbox']['spr2'] == true && $this->crossNavVsEno == true) {
						echo '<tr>';
						echo '<td class="'.$cssBT.'">&nbsp;</th>';
						echo '<td class="'.$cssBT.'">&nbsp;</th>';
						echo '<td class="'.$cssBT.'" colspan="'.count($crosstabs['options1']).'">&nbsp;</th>';
						if ($addVerticalSpace == 1) {
							echo '<td class="">&nbsp;</td>';
						}
						
						echo '<td class="'.$cssBT.'">&nbsp;</th>';
						echo '</tr>';
						$cssBT = '';
					}
					
					$cntY++;
					echo '<tr>';
					$css_backY = ' rsdl_bck_variable1';
					echo '<td class="anl_bb anl_bt anl_bl anl_ac red anl_ita anl_bck_text_0'.$css_backY.' ctbCll">' . $lang['srv_analiza_crosstab_skupaj'] . '</td>';
					// skupni sestevki po stolpcih
					if (count($crosstabs['options1']) > 0)
					foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
						echo '<td class="anl_ac anl_bb anl_bt anl_bl rsdl_bck0" >';
						{
							# prikazujemo eno od treh možnosti
							echo '<table class="anl_tbl_crosstab fullWidth fullHeight" style="padding:0px; margin:0px;">';
							if ($this->crossChk0) {
								echo '<tr>';
								echo '<td class="anl_ac ctbCll crostabSuma" colspan="'.($this->crossChk1 + $this->crossChk2 + $this->crossChk3).'">';
								# suma po stolpcih
								echo (int)$crosstabs['sumaStolpec'][$ckey1];
								echo '</td>';
								echo '</tr>';
							}
							if ($this->crossChk1 || $this->crossChk2 || $this->crossChk3) {
								# suma po stolpcih v procentih
								$css_bt = ($this->crossChk0) ? ' anl_dash_bt' : '';
								echo '<tr>';
								if ($this->crossChk1) {
									$css_color = ($this->doColor == 'true') ? ' ctbChck_sp1' : 'ctbChck_sp0';
									echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.'">';
									echo $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									echo '</td>';
								}
								if ($this->crossChk2) {
									$css_color = ($this->doColor == 'true') ? ' ctbChck_sp2' : 'ctbChck_sp0';
									echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.($this->crossChk1 ? ' anl_dash_bl' : '').'">';
									echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									echo '</td>';
								}
								if ($this->crossChk3)
								{
									$css_color = ($this->doColor == 'true') ? ' ctbChck_sp3' : 'ctbChck_sp0';
									echo '<td class="anl_ac'.$css_color.$css_bt.($this->crossChk2 ? ' anl_dash_bl' : '').' ctbCll">';
									echo $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									echo '</td>';
								}
								echo '</tr>';
							}
							echo '</table>';
						}
						echo '</td>';
					}
					# če mamo checkboxe in sumo malo razmaknemo
					if ($addVerticalSpace == 1) {
						echo '<td class="anl_bl">&nbsp;</td>';
					}
					
					# zadnja celica z skupno sumo
					echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0">';
					{
						echo '<table class="anl_tbl_crosstab fullWidth fullHeight" style="padding:0px; margin:0px;">';
						if ($this->crossChk0) {
							echo '<tr>';
							echo '<td class="anl_ac ctbCll crostabSuma" colspan="'.($this->crossChk1 + $this->crossChk2 + $this->crossChk3).'">';
							# skupna suma
							echo (int)$crosstabs['sumaSkupna'];
							echo '</td>';
							echo '</tr>';
						}
						if ($this->crossChk1 || $this->crossChk2 || $this->crossChk3) {
							# suma po stolpcih v procentih
							$css_bt = ($this->crossChk0) ? ' anl_dash_bt' : '';
							echo '<tr>';
							if ($this->crossChk1) {
								$css_color = ($this->doColor == 'true') ? ' ctbChck_sp1' : 'ctbChck_sp0';
								$css_border = ($this->crossChk2 || $this->crossChk3) ? ' anl_dash_br' : '';
								echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.$css_border.'">';
								echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
								echo '</td>';
							}
							if ($this->crossChk2) {
								$css_color = ($this->doColor == 'true') ? ' ctbChck_sp2' : 'ctbChck_sp0';
								$css_border = ($this->crossChk3) ? ' anl_dash_br' : '';
								echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.$css_border.'">';
								echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
								echo '</td>';
							}
							if ($this->crossChk3) {
								$css_color = ($this->doColor == 'true') ? ' ctbChck_sp3' : 'ctbChck_sp0';
								echo '<td class="anl_ac ctbCll'.$css_color.$css_bt.'">';
								echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
								echo '</td>';
							}
							echo '</tr>';
						}
						echo '</table>';
					}
					echo '</td>';
					if ($this->fromBreak == true && $this->showAverage == true) {
						# če smo v break dodamo še povprečja
						echo '<td class="anl_bl anl_bt" >';
						echo '&nbsp;';
						echo '</td>';
					}
					echo '</tr>';
					#xxx
					#zadnja vrstica z povprečji - iz break
					if ($this->showBottomAverage == true && $crosstabs['isCheckbox']['spr2'] == false) {
						echo '<tr>';
						$css_backY = ' rsdl_bck_variable1';
						echo '<td class="anl_bb anl_bt anl_bl anl_br anl_ac anl_ita anl_bck_text_0'.$css_backY.' ctbCll">' . $lang['srv_analiza_crosstab_average'] . '</td>';
						// skupni sestevki po stolpcih
						if (count($crosstabs['options1']) > 0) {
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								echo '<td class="anl_ac anl_bb anl_bt anl_br rsdl_bck_variable1" >';
								echo $this->formatNumber( $crosstabs['avgStolpec'][$ckey1], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'), '');
								echo '</td>';
							}
						}
						# če mamo checkboxe in sumo malo razmaknemo
						if ($addVerticalSpace == 1) {
							echo '<td>&nbsp;</td>';
						}
						
						# zadnja celica z skupno sumo
						echo '<td>&nbsp;</td>';
						if ($this->fromBreak == true && $this->showAverage == true) {
							# če smo v break dodamo še povprečja
							echo '<td>&nbsp;</td>';
						}
						echo '</tr>';
					}
					echo '</table>';

					// Zvezdica za vkljucitev v porocilo
					$spr2 = $v_first['seq'].'-'.$v_first['spr'].'-'.$v_first['grd'];
					$spr1 = $v_second['seq'].'-'.$v_second['spr'].'-'.$v_second['grd'];
					
					SurveyAnalysisHelper::getInstance()->addCustomReportElement($type=5, $sub_type=0, $spr1, $spr2);
					#SurveyAnalysis::addCustomReportElement($type=5, $sub_type=0, $spr1, $spr2);
					
					// Izrisemo graf za tabelo
					if($this->showChart && !$this->fromBreak){
						$tableChart = new SurveyTableChart($this->sid, $this, 'crosstab', $counter);
						$tableChart->display();
					}
					
					$counter++;
				}
			}
		} else {
			# crostab variables not set
			echo $lang['srv_crosstab_note0'];
			#print_r("Crosstab variables not set!");
		}
	}

	/**
	 * @desc prikaze izvoz za PDF/RTF
	 */
	function displayExport () {
		# z javascriptom prikažemo ikonce za arhiviranje, emaijlanje arhivov, pdf, rtf, excel...
		if ($this->isSelectedBothVariables()) {
			$data1 = '';
			$data2 = '';
			
			foreach($this->variabla1 as $var1){
				$data1 .= implode(',', array_values($var1)).',';
			}
			$data1 = substr($data1, 0, -1);
			
			foreach($this->variabla2 as $var2){
				$data2 .= implode(',', array_values($var2)).',';
			}
			$data2 = substr($data2, 0, -1);
			
			$href_print = makeEncodedIzvozUrlString('izvoz.php?b=export&m=crosstabs_izpis&anketa=' . $this->sid . '&data1='.$data1.'&data2='.$data2);
			$href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=crosstabs_izpis&anketa=' . $this->sid . '&data1='.$data1.'&data2='.$data2);
			$href_rtf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=crosstabs_izpis_rtf&anketa=' . $this->sid . '&data1='.$data1.'&data2='.$data2);
			$href_xls = makeEncodedIzvozUrlString('izvoz.php?b=export&m=crosstabs_izpis_xls&anketa=' . $this->sid . '&data1='.$data1.'&data2='.$data2);
			echo '<script>';
			# nastavimopravilne linke
			echo '$("#secondNavigation_links a#crosstabDoPdf").attr("href", "'.$href_pdf.'");';
			echo '$("#secondNavigation_links a#crosstabDoRtf").attr("href", "'.$href_rtf.'");';
			echo '$("#secondNavigation_links a#crosstabDoXls").attr("href", "'.$href_xls.'");';
			# prikažemo linke
			echo '$("#hover_export_icon").removeClass("hidden");';
			echo '$("#secondNavigation_links a").removeClass("hidden");';
			echo '</script>';
				
		}
		
	}

	/** kadar kličemo iz Break, ali pri radio grupi dodamo še povprečje po stolpcih
	 * 
	 * @param unknown_type $showBottomAverage 
	 */
	function showBottomAverage ($showBottomAverage  = false) {
		$this->showBottomAverage  = $showBottomAverage ;
	}
	/**
	 * @desc nastavimo spremenljivke/variable za prikaz pdf/rtf
	 */
	function setVariables($seq1, $spr1, $grd1, $seq2, $spr2, $grd2){

		$this->variabla1[0]['seq'] = $seq1;
		$this->variabla1[0]['spr'] = $spr1;
		$this->variabla1[0]['grd'] = $grd1;

		$this->variabla2[0]['seq'] = $seq2;
		$this->variabla2[0]['spr'] = $spr2;
		$this->variabla2[0]['grd'] = $grd2;
	}


	public function createCrostabulation($v_first, $v_second) {
		global $site_path;
		$folder = $site_path . EXPORT_FOLDER.'/';
		
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
					if (count($spr1['grids'][0]['variables']) > 0)
					foreach ($spr1['grids'][0]['variables'] AS $_vkey =>$_variable) {
						if ((int)$_variable['text'] != 1) {
							$sekvences1[] = $_variable['sequence'];
						}
					} else {
						$sekvences1 = explode('_',$spr1['sequences']);
					}
				}
				if ($spr1['tip'] == 16) {

					foreach ($grid1['variables'] AS $_variables) {
						
						$sekvences1[] = $_variables['sequence'];
					}
						
					#$sekvences1 = explode('_',$this->_HEADERS[$v_first['spr']]['sequences']);
				}
			} else {
				$sekvences1[] = $sequence1;
			}

			if ($spr2['tip'] == 2 || $spr2['tip'] == 16) {
				$spr_2_checkbox = true;
				if ($spr2['tip'] == 2 ) {
					if (count($this->_HEADERS[$v_second['spr']]['grids'][0]['variables']) > 0)
						foreach ($this->_HEADERS[$v_second['spr']]['grids'][0]['variables'] AS $_vkey =>$_variable) {
						if ((int)$_variable['text'] != 1) {
							$sekvences2[] = $_variable['sequence'];
						}
							
					} else {
						$sekvences2 = explode('_',$this->_HEADERS[$v_second['spr']]['sequences']);
					}
						
				}
				if ($spr2['tip'] == 16) {
					foreach ($grid2['variables'] AS $_variables) {
						$sekvences2[] = $_variables['sequence'];
					}
					#$sekvences2 = explode('_',$this->_HEADERS[$v_second['spr']]['sequences']);
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
			$tmp_file = $folder . 'tmp_crosstab_'.$this->sid.'.TMP';
			$file_handler = fopen($tmp_file,"w");
			fwrite($file_handler,"<?php\n");
			fclose($file_handler);
			if (count($sekvences1)>0)
			foreach ($sekvences1 AS $sequence1) {
				if (count($sekvences2)>0)
				foreach ($sekvences2 AS $sequence2) {
					#skreira variable: $crosstab, $cvar1, $cvar2
						
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
						#$command = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$_status_filter.' { print \"$crosstab[\x27\",$'.$sequence1.',\"\x27][\x27\",$'.$sequence2.',\"\x27]++; $options1[\x27\",$'.$sequence1.',\"\x27]++; $options2[\x27\",$'.$sequence2.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
						$command = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$additional_filter.' { print \"$crosstab[\x27\",'.$_seq_1_text.',\"\x27][\x27\",'.$_seq_2_text.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
					} else {
						#$command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$_status_filter.' { print "$crosstab[\x27",$'.$sequence1.',"\x27][\x27",$'.$sequence2.',"\x27]++; $options1[\x27",$'.$sequence1.',"\x27]++; $options2[\x27",$'.$sequence2.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
						$command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$additional_filter.' { print "$crosstab[\x27",'.$_seq_1_text.',"\x27][\x27",'.$_seq_2_text.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
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
			
			#ker z awk ne gre nardit tolower zaradi šumnikov, nardimo ročno v loopu
			$caseInsensitiveCrosstab = array();
			if (count($crosstab) > 0) {
				foreach ($crosstab as $fkey => $fvalue) {
					$fkey = mb_strtolower($fkey,'UTF-8');
					if (count($fvalue) > 0) {
						foreach ($fvalue as $skey => $svalue) {
							$skey = mb_strtolower($skey,'UTF-8');
							$caseInsensitiveCrosstab[$fkey][$skey] += $svalue;
						}
					}
				}
			}
			$crosstab = $caseInsensitiveCrosstab;
			# poiščemo pripadajočo spremenljivko
			$var_options1 = $this->_HEADERS[$v_first['spr']]['options'];
			$var_options2 = $this->_HEADERS[$v_second['spr']]['options'];

			# inicializacija
			$_all_options1 = array();
			$_all_options2 = array();
			$sumaStolpec = array();
			$sumaVrstica = array();
			$sumaSkupna = 0;
				
			# najprej poiščemo (združimo) vse opcije ki so definirane kot opcije spremenljivke in vse ki so v crosstabih
			if (count($var_options1) > 0 && $spr_1_checkbox !== true ) {
				foreach ($var_options1 as $okey => $opt) {
					$_all_options1[$okey] = array('naslov'=>$opt, 'cnt'=>$options1[$okey], 'type'=>'o');
				}
			}
			if (count($var_options2) > 0 && $spr_2_checkbox !== true) {
				foreach ($var_options2 as $okey => $opt) {
					$_all_options2[$okey] = array('naslov'=>$opt, 'cnt'=>$options2[$okey], 'type'=>'o');
				}
			}
			# za checkboxe dodamo posebej vse opcije
			if ($spr_1_checkbox == true ) {
				if ($spr1['tip'] == 2 ) {
					$grid1 =$this->_HEADERS[$v_first['spr']]['grids']['0'];
				}

				foreach ($grid1['variables'] As $vkey => $variable) {
					if ($variable['other'] != 1) {
						$_all_options1[$variable['sequence']] = array('naslov'=>$variable['naslov'], 'cnt'=>0, 'type'=>'o', 'vr_id'=> $variable['variable']);
					}
				}
			}
				
			if ($spr_2_checkbox == true ) {
				if ($spr2['tip'] == 2 ) {
					$grid2 =$this->_HEADERS[$v_second['spr']]['grids']['0'];
				}

				foreach ($grid2['variables'] As $vkey => $variable) {
					if ($variable['other'] != 1) {
						$_all_options2[$variable['sequence']] = array('naslov'=>$variable['naslov'], 'cnt'=>0, 'type'=>'o', 'vr_id'=> $variable['variable']);
					}
				}
			}
				
			# dodamo odgovore iz baze ki niso missingi
			if (count($crosstab) > 0 ) {
				foreach ($crosstab AS $_kvar1=>$_var1) {
					# missingov ne dodajamo še zdaj, da ohranimo pravilen vrstni red
					if (!isset($_allMissing_answers[$_kvar1]) && !isset($_all_options1[$_kvar1])) {
						$_all_options1[$_kvar1] = array('naslov'=>$_kvar1, 'cnt'=>($_all_options1[$_kvar1]['cnt']+1), 'type'=>'t');
					}
						
					foreach ($_var1 AS $_kvar2=>$_var2) {
						if (!isset($_allMissing_answers[$_kvar1]) || (isset($_allMissing_answers[$_kvar1]) && isset($_pageMissing_answers[$_kvar1]))) {
							$sumaStolpec[$_kvar1] += $_var2;
						}
						if (!isset($_allMissing_answers[$_kvar2]) || (isset($_allMissing_answers[$_kvar2]) && isset($_pageMissing_answers[$_kvar2]))) {
							$sumaVrstica[$_kvar2] += $_var2;
						}
						# missingov ne dodajamo še zdaj, da ohranimo pravilen vrstni red
						if (!isset($_allMissing_answers[$_kvar2]) && !isset($_all_options2[$_kvar2])) {
							$_all_options2[$_kvar2] = array('naslov'=>$_kvar2, 'cnt'=>($_all_options1[$_kvar2]['cnt']+1), 'type'=>'t');
						}

					}
				}
			}
			/*
			 # dodamo še missinge, samo tiste ki so izbrani z profilom
			 if (count($_pageMissing_answers) > 0 ) {
				foreach ($_pageMissing_answers as $mkey => $missing ) {
				if ( $spr_1_checkbox !== true) {
				$_all_options1[$mkey] = array('naslov'=>$missing['text'], 'cnt'=>(int)$options1[$mkey], 'type'=>'m');
				}
				if ( $spr_2_checkbox !== true ) {
				$_all_options2[$mkey] = array('naslov'=>$missing['text'], 'cnt'=>(int)$options2[$mkey], 'type'=>'m');
				}
				}
				}
				*/
			# dodamo še missinge, samo tiste ki so izbrani z profilom
			foreach ($_allMissing_answers AS $miskey => $_missing) {
				if (!isset($_pageMissing_answers[$miskey])) {
					if ( $spr_1_checkbox !== true) {
						$_all_options1[$miskey] = array('naslov'=>$_missing, 'cnt'=>(int)$options1[$miskey], 'type'=>'m');
					}
					if ( $spr_2_checkbox !== true ) {
						$_all_options2[$miskey] = array('naslov'=>$_missing, 'cnt'=>(int)$options2[$miskey], 'type'=>'m');
					}
				}
			}
			$sumaSkupna = max(array_sum($sumaStolpec), array_sum($sumaVrstica));

			# če lovimo po enotah, moramo skupne enote za vsako kolono(vrstico) izračunati posebej - POPRAVLJENO KER TO MORA VERJETNO NAREDIT SAMO CE STA OBE SPR CHECKBOXA??
			//if ($this->crossNavVsEno == 1) {
			if ($spr_1_checkbox == true && $spr_2_checkbox == true && $this->crossNavVsEno == 1) {
				$sumaSkupna = 0;
				$sumaStolpec = array();
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
				$tmp_file = $folder . 'tmp_crosstab_'.$this->sid.'.TMP';


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
							$command_1 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$chckbox_filter1.$spr2_addFilter.' { print \"$sumaStolpec[\x27\",'.$_seq_1_text.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
						} else {
							$command_1 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$chckbox_filter1.$spr2_addFilter.' { print "$sumaStolpec[\x27",'.$_seq_1_text.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
						}
						$out = shell_exec($command_1);
					}
				}
				#nato še za vrstice
				if (count($sekvences2)>0) {
					foreach ($sekvences2 AS $sequence2) {
						if ($spr_2_checkbox == true) {
							$_seq_2_text = ''.$sequence2;
							# pri checkboxih lovimo samo tiste ki so 1
							$chckbox_filter2 = ' && ($'.$sequence2.' == 1)';
						} else {
							$_seq_2_text = '$'.$sequence2;
						}

						if (IS_WINDOWS) {
							$command_2 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$chckbox_filter2.$spr1_addFilter.' { print \"$sumaVrstica[\x27\",'.$_seq_2_text.',\"\x27]++;\"}" '.$this->dataFileName.' >> '.$tmp_file;
						} else {
							$command_2 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$chckbox_filter2.$spr1_addFilter.' { print "$sumaVrstica[\x27",'.$_seq_2_text.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
						}
						$out = shell_exec($command_2);
					}
				}
				
				$file_handler = fopen($tmp_file,"a");
				fwrite($file_handler,'?>');
				fclose($file_handler);
				include($tmp_file);
				if (file_exists($tmp_file)) {
					unlink($tmp_file);
				}
			}

			$CSS_COLOR0 = ' rsdl_bck0';
			$CSS_COLOR1 = ' rsdl_bck1';
			$CSS_COLOR2 = ' rsdl_bck2';
			$CSS_COLOR3 = ' rsdl_bck3';
			$CSS_COLOR4 = ' rsdl_bck4';
			$CSS_COLOR5 = ' rsdl_bck5';
			$CSS_COLOR6 = ' rsdl_bck6';
				
			# izracunamo se pricakovano vrednost, in residuale
			# exC - expected count (pricakovana vrednost)
			$exC = array();
			# res - residual
			$res = array();
			# stR - standardized residual
			$stR = array();
			# adR - adjusted residual
			$adR = array();
			#color - array kjer shranjujemo barvo celice v odvisnosti od adjusted residuala
			$color = array();
			#pocprečje vrstice
			$sum_avgi = array();
			#pocprečje stolpca
			$sum_avgj = array();
						
			$_w = 0;
			$_fij = 0;
			$_ri = 0;
			$_cj = 0;
			$_exC = 0;
			$_res = 0;
			$_stR = 0;
			$_ri_div_w = 0;
			$_cj_div_w = 0;
			$_sqrt_part = 0;
			$_adR = 0;
			$_limit = 0;

			$cnt1 = count($_all_options1);
			$cnt2 = count($_all_options2);
			
			# gremo skozi vsako celico
			if ($cnt1 > 0 && $cnt2) {
				foreach ($_all_options1 as $ckey1 => $crossVariabla1) {
					if($sumaStolpec[$ckey1]!=null) {					
						foreach ($_all_options2 as $ckey2 => $crossVariabla2) {
							if($sumaVrstica[$ckey2]!=null) {
								# skupna suma
								$_w = $sumaSkupna;
								#frekvenca celice
								$_fij = $crosstab[$ckey1][$ckey2];
								#suma vrstice
								$_ri = $sumaVrstica[$ckey2];
								#suma stolpca
								$_cj =  $sumaStolpec[$ckey1];
			
								# povprečje vrstice
								$sum_avgi[$ckey2] += ((int)$_ri != 0) ? ((int)$_fij * (int)$ckey1 / (int)$_ri) : 0;
			
								# povprečje stolpcev
								$sum_avgj[$ckey1] += ((int)$_cj != 0) ? ((int)$_fij * (int)$ckey2 / (int)$_cj) : 0;
								
								# exC - expected count (pricakovana vrednost
								$_exC = ($_w > 0 ) ? ((  $_ri * $_cj) / $_w) : 0;
								$exC[$ckey1][$ckey2] = $_exC;
								# res - residual
								$_res = $_fij - $_exC;
								$res[$ckey1][$ckey2] = $_res;
								# stR - standardized residual
								$_stR = ($_exC != 0) ? $_res / sqrt($_exC) : 0;
								$stR[$ckey1][$ckey2] = $_stR;
								# adR - adjusted residual
								$_ri_div_w = ($_w != 0) ? ($_ri / $_w) : 0;
								$_cj_div_w = ($_w != 0) ? ($_cj / $_w) : 0;
								$_sqrt_part = $_exC * (1 - $_ri_div_w ) * (1 - $_cj_div_w );
								$_adR = ($_sqrt_part != 0) ? $_res / sqrt($_sqrt_part ) : 0;
			
								$adR[$ckey1][$ckey2] = $_adR;
								#privzeto je belo
								$color[$ckey1][$ckey2] = $CSS_COLOR0;
								# katera vrednost nam je limit (prilagojen residual)
								$_limit = $_adR;
								if ($this->doColor == 'true') {
									# če imamo barvanje residualov še pobarvamo v odvisnosti od prilagojenega residuala
									if (abs($_limit) >= RESIDUAL_COLOR_LIMIT1)
									$color[$ckey1][$ckey2] = $_limit > 0 ? $CSS_COLOR1 : $CSS_COLOR4;
									if (abs($_limit) >= RESIDUAL_COLOR_LIMIT2)
									$color[$ckey1][$ckey2] = $_limit > 0 ? $CSS_COLOR2 : $CSS_COLOR5;
									if (abs($_limit) >= RESIDUAL_COLOR_LIMIT3)
									$color[$ckey1][$ckey2] = $_limit > 0 ? $CSS_COLOR3 : $CSS_COLOR6;
								}
							} else {
								if ($cnt2 > AUTO_HIDE_ZERRO_VALUE) {
									unset($_all_options2[$ckey2]);
								}
							}
						}
					} else {
						if ($cnt1 > AUTO_HIDE_ZERRO_VALUE) {
							unset($_all_options1[$ckey1]);
						}
					}
				}
			}			
			
			# izračunamo še hi^2
			$hi2 = 0;
			if ($cnt1 > 0 && $cnt2)
			foreach ($_all_options1 as $ckey1 => $crossVariabla1) {
				foreach ($_all_options2 as $ckey2 => $crossVariabla2) {
					$fr = (float)$crosstab[$ckey1][$ckey2];
					$exp = (float)$exC[$ckey1][$ckey2];
					if ($exp != 0) {
						$hi2 += pow(($fr - $exp),2) / $exp;
					}
					#dejanska frekvenca

				}
			}
			return(array(
				'crosstab'	 =>	$crosstab,	# krostabulacije - frekvence
				'options1'	 =>	$_all_options1,	# vse opcije za variablo 1
				'options2'	 =>	$_all_options2,	# vse opcije za variablo 2
				'exC' 		 =>	$exC,	# pričakovana vrednost
				'res' 		 =>	$res, 	# res - residual
				'stR'		 =>	$stR,	# stR - standardized residual
				'adR'		 =>	$adR,	# adR - adjusted residual
				'color'		 =>	$color,	#color - array kjer shranjujemo barvo celice v odvisnosti od adjusted residuala
				'sumaStolpec'=>	$sumaStolpec,	#
				'sumaVrstica'=>	$sumaVrstica,	#
				'sumaSkupna' =>	$sumaSkupna,	#
				'avgVrstica' =>	$sum_avgi,		# povprečje vrstice
				'avgStolpec' =>	$sum_avgj,		# povprečje stolpca
				'hi2'		 =>	$hi2,			# hi kvadrat
				'isCheckbox' => array('spr1'=>(boolean)$spr_1_checkbox,'spr2'=>(boolean)$spr_2_checkbox)
			));

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
			
		$result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;

		return $result;
	}

	function getCrossTabPercentage ($sum, $value) {
		$result = 0;
		if ($value ) {
			$result = (int)$sum == 0 ? 0 : $value / $sum * 100;
		}

		return $result;
	}

	function setSessionPercent() {
	
		if (isset($_POST['crossChk1'])) {
			$this->sessionData['crosstabs']['crossChk1'] = ($_POST['crossChk1'] == 'true');
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}

	function setSessionColor() {

		if (isset($_POST['doColor'])) {
			$this->sessionData['crosstabs']['doColor'] = ($_POST['doColor'] == 'true');
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	function setShowChart() {

		if (isset($_POST['showChart'])) {
			$this->sessionData['crosstab_charts']['showChart'] = $_POST['showChart'] == 'true';
		}
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	public function setColor($doColor = true) {
		if ($doColor == true) {
			$this->doColor = true;
		} else {
			$this->doColor = false;
		}
	}

	/** Funkcije ki skrbijo za ajax del
	 *
	 */
	public function ajax() {
		if ( isset($_POST['sequence1']) && count($_POST['sequence1']) > 0 ) {
			$i=0;
			if ( count($_POST['sequence1']) > 0 ){
				foreach ($_POST['sequence1'] AS $_seq1) {
					$this->variabla1[$i]['seq'] = $_seq1;
					$i++;
				}
			}
		}
		
		if ( isset($_POST['spr1']) && count($_POST['spr1']) > 0 ) {
			$i=0;
			if ( count($_POST['spr1']) > 0 ){
				foreach ($_POST['spr1'] AS $_spr1) {
					$this->variabla1[$i]['spr'] = $_spr1;
					$i++;
				}
			}
		}
		if ( isset($_POST['grid1']) && count($_POST['grid1']) > 0 ) {
			$i=0;
			if ( count($_POST['grid1']) > 0 ){
				foreach ($_POST['grid1'] AS $_grd1) {
					$this->variabla1[$i]['grd'] = $_grd1;
					$i++;
				}
			}
		}

		if ( isset($_POST['sequence2']) && count($_POST['sequence2']) > 0 ) {
			$i=0;
			if ( count($_POST['sequence2']) > 0 ){
				foreach ($_POST['sequence2'] AS $_seq2) {
					$this->variabla2[$i]['seq'] = $_seq2;
					$i++;
				}
			}
		}
		if ( isset($_POST['spr2']) && count($_POST['spr2']) > 0 ) {
			$i=0;
			if ( count($_POST['spr2']) > 0 ){
				foreach ($_POST['spr2'] AS $_spr2) {
					$this->variabla2[$i]['spr'] = $_spr2;
					$i++;
				}
			}
		}
		if ( isset($_POST['grid2']) && count($_POST['grid2']) > 0 ) {
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
			$this->sessionData['crosstabs']['crosstab_variables']['variabla1'] =  $this->variabla1;
		}
		if (isset($this->variabla2) && count($this->variabla2) > 0) {
			$this->sessionData['crosstabs']['crosstab_variables']['variabla2'] =  $this->variabla2;
		}
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		if (isset($_POST['crossNavVsEno']) &&  $_POST['crossNavVsEno'] != '' ) {
			$this->crossNavVsEno = $_POST['crossNavVsEno'] !== 'undefined' ? $_POST['crossNavVsEno'] : 0;
		}

		
		$this->readUserSettings();
		switch ($_GET['a']) {
			case 'changeDropdown':
				$this->DisplayDropdows();
				break;
			case 'change':
				$this->Display();
				break;
			case 'change_cb':
				$this->displayCrosstabsTables();
				break;
			case 'change_cb_percent':
				$this->setSessionPercent();
				break;
			case 'change_cb_color':
				$this->setSessionColor();
				break;
			case 'add_new_variable':
				$this->addNewVariable();
				break;
			case 'prepareInspect':
				$this->prepareInspect();
				break;
			case 'changeSessionInspect':
				$this->changeSessionInspect();
				break;
			case 'change_show_chart':
				$this->setShowChart();
				break;
			default:
				print_r("<pre>");
				print_r($_GET);
				print_r($_POST);
				print_r("</pre>");
				break;
		}

	}

	function readUserSettings() {
		@session_start();
		$sdsp = SurveyDataSettingProfiles :: getSetting();

		$this->crossChk0 = $sdsp['crossChk0'] == '1' ? true : false;

		# če smo checkbox za odstotke po vrsticah nastavili preko seje (posebej) prevzamemo vrednost
		if (isset($this->sessionData['crosstabs']['crossChk1'])) {
			$this->crossChk1 = $this->sessionData['crosstabs']['crossChk1'];
		} else {
			# če ne preberemo iz profila
			$this->crossChk1 = $sdsp['crossChk1'] == '1' ? true : false;
		}

		# če smo checkbox za barvanje nastavili preko seje (posebej) prevzamemo vrednost
		if (isset($this->sessionData['crosstabs']['doColor'])) {
			$this->doColor = $this->sessionData['crosstabs']['doColor'];
		} else {
			# če ne preberemo iz profila
			$this->doColor = $sdsp['doColor'] == '1' ? true : false;
		}
		
		# če smo radio enableInspect
		if ($sdsp['enableInspect'] == '1' || (isset($_SESSION['enableInspect']) && $_SESSION['enableInspect'] == true)) {
			$this->enableInspect = true;
		} else {
			# če ne preberemo iz profila
			$this->enableInspect = false;
		}

		$this->doValues = $sdsp['doValues'] == '1' ? true : false;

		$this->crossChk2 = $sdsp['crossChk2'] == '1' ? true : false;
		$this->crossChk3 = $sdsp['crossChk3'] == '1' ? true : false;
		$this->crossChkEC = $sdsp['crossChkEC'] == '1' ? true : false;
		$this->crossChkRE = $sdsp['crossChkRE'] == '1' ? true : false;
		$this->crossChkSR = $sdsp['crossChkSR'] == '1' ? true : false;
		$this->crossChkAR = $sdsp['crossChkAR'] == '1' ? true : false;
		
		$this->showChart = isset($this->sessionData['crosstab_charts']['showChart']) ? $this->sessionData['crosstab_charts']['showChart'] : false;
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

	function prepareInspect() {
	
		global $global_user_id, $lang;
	
		# nastavimo v sejo od kod smo prišli v inspect:
		@session_start();
		if ((isset($_POST['from_podstran']) && trim($_POST['from_podstran']) != '')) {
			$_SESSION['inspectFromPodstran'][$this->sid] = $_POST['from_podstran'];
		} else {
			unset($_SESSION['inspectFromPodstran'][$this->sid]);
		}
		@session_commit();
		
		# naredimo filter po spremenljivkah
		$variables = $_POST['sp1'].','.$_POST['sp2'];
		if (SurveyDataSettingProfiles :: getSetting('enableInspect')) {
			$_add_vars = $_SESSION['dataSetting_profile'][$this->sid]['InspectListVars'];
			if (isset($_add_vars) && is_array($_add_vars) && count($_add_vars) > 0) {
				foreach ($_add_vars AS $add_var) {
					$variables .= ','.$add_var.'_0';
				}
			}
		}
		$svp = new SurveyVariablesProfiles();
		$svp -> Init($this->sid,$global_user_id);
		$svp-> setProfileInspect($variables);
		
		$_spr1 = $this->_HEADERS[$_POST['sp1']];
		$_spr2 = $this->_HEADERS[$_POST['sp2']];

		# nastaviti moramo dva filtra  - pogojev in spremenljivk
		$spr1 = explode('_',$_POST['sp1']);
		$spr1 = $spr1[0];
		$spr2 = explode('_',$_POST['sp2']);
		$spr2 = $spr2[0];

		
		# if id za inspect shranimo v nastavitev ankete SurveyUserSetting -> inspect_if_id (če ne obstaja skreiramo novega)
		# dodamo tudi kot profil pogojev (če ne obstaja skreiramo novega)
		
		#preverimo ali obstaja zapis v SurveyUserSetting->inspect_if_id
		$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
		# preverimo dejanski obstoj ifa (srv_if) če ne skreiramo novega
		if ((int)$if_id > 0) {
			$chks1 = "SELECT * FROM srv_if WHERE id='$if_id'";
			$chkq1 = sisplet_query($chks1);
			# dodamo še k profilu če ne obstaja
			if (mysqli_num_rows($chkq1) == 0) {
				$if_id = null;
				SurveyUserSetting :: getInstance()->removeSettings('inspect_if_id');
			}
		}
		
		if ( (int)$if_id == 0 || $if_id == null) {
			# if še ne obstaja, skreiramo novga
			$sql = sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
			#			if (!$sql) echo '<br> -1';
			
			$if_id = mysqli_insert_id($GLOBALS['connect_db']);
			sisplet_query("COMMIT");
			# shranimo pogoj kot privzet pogoj z ainspect
			SurveyUserSetting :: getInstance()->saveSettings('inspect_if_id',(int)$if_id);
		}
		
		
		if ((int)$if_id > 0) {
			# dodamo ifa za obe variabli

			# ne brišemo starih pogojev, da omogočimo gnezdenje
			#$delStr = "DELETE FROM srv_condition WHERE if_id = '$if_id'";
			#sisplet_query($delStr);

			# poiščemo vrednosti za oba vprašanja
			$condition1 = $this->createSubCondition(1,$if_id,$spr1,$_spr1);
			$condition2 = $this->createSubCondition(2,$if_id,$spr2,$_spr2);

			sisplet_query("COMMIT");
	
			# pogoj dodamo še v srv_condition_profile vendar ga ne nastavimo kot privzetega
			$chk_if_str = "SELECT * FROM srv_condition_profiles WHERE sid='".$this->sid."' AND uid = '".$global_user_id."' AND type='inspect'";
			$chk_if_qry = sisplet_query($chk_if_str);
			$_tmp_name = $lang['srv_inspect_temp_profile'];
			if (mysqli_num_rows($chk_if_qry) > 0) {
				# if že obstaja popravimo morebitne podatke
				$str = "UPDATE srv_condition_profiles SET name = '$_tmp_name', if_id='$if_id'";
			$sql = sisplet_query($str);
			} else {
				#vstavimo nov profil pogojev - inspect
				$str = "INSERT INTO srv_condition_profiles (sid, uid, name, if_id, type ) VALUES ('".$this->sid."', '".$global_user_id."', '$_tmp_name', '$if_id', 'inspect')"
			. " ON DUPLICATE KEY UPDATE name='$_tmp_name', if_id='$if_id'";
			$sql = sisplet_query($str);
			}
			sisplet_query("COMMIT");
		}

		if (isset($_SESSION['inspect_goto'])) {
			$inspect_goto = (int)$_SESSION['inspect_goto'];
		} else {
			$inspect_goto = (int)0;
		}
		$inspect_goto_array = array( 0 => '&a=analysis&m=sumarnik',
							 		 1 => '&a=data&m=quick_edit',
							 		 2 => '&a=data');
		
		echo $inspect_goto_array[$inspect_goto];
		return ($inspect_goto_array[$inspect_goto]);
	}

	function createSubCondition($vrstn_red,$if_id,$sid,$spr) {
		$tip = $spr['tip'];

		# 1. Radio 
		# 3. Dropdown
		# 2. Select - checkbox
		if ($tip == '1' || $tip == '3' || $tip == '2') {
			#radio in dropdown
			if ($tip == '1' || $tip == '3') {
				#s pomočjo k preberemo vrstni red
				$sql_string = "SELECT id FROM srv_vrednost WHERE spr_id='$sid' AND variable = '".$_POST['k'.$vrstn_red]."'";
				$sql_query = sisplet_query($sql_string);
				if (mysqli_num_rows($sql_query) == 1 ) {
					$sql_row = mysqli_fetch_assoc($sql_query);
					$vred_id = $sql_row['id'];
				}
			}
			#select
			if ($tip == '2' ) {
				$vred_id=null;
				# če je čekbox poiščemo vred_id za sekvenco k
				foreach ($spr[grids] as $gkey=>$grid) {
					foreach ($grid[variables] as $vkey=>$variable) {
						if ($variable['sequence'] == $_POST['k'.$vrstn_red]) {
							$vred_id = $variable['vr_id'];
						}
					}
				}
			}
			if ($vred_id != null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red) VALUES ('$if_id', '$sid', '$vrstn_red')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red'";
				$sql = sisplet_query($istr);
				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);

				if ((int)$vred_id > 0 || (int)$cond_id > 0) {
					$istr = "INSERT INTO srv_condition_vre (cond_id, vre_id) VALUES ('$cond_id', '$vred_id')";
					$sql = sisplet_query($istr);
					if (!$sql)  {
						echo '<br>-4 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
					
				}
				return $cond_id;
			}
		}
				
		# 7. Number
		if ($tip == '7' ) {
			$text=$_POST['k'.$vrstn_red];
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			$seq = $_POST['sq'.$vrstn_red];
			$vrstn_red = $vrstn_red-1;
			foreach ($spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $seq) { 
						$grid_id = $vkey;
					}
				}
			}
			if ($grid_id !== null) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, grd_id, text) VALUES ('$if_id', '$sid', '$vrstn_red', '$grid_id', '$text')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red', grd_id='$grid_id', text='$text'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				return $cond_id;
			}
		}
		# 21. besedilo
		# 18. vsota
		if ($tip == '21' || $tip == '18') {
			
			$text=$_POST['k'.$vrstn_red];
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			$seq = $_POST['sq'.$vrstn_red];
			
			foreach ($spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $seq) { 
						$vred_id = $variable['vr_id'];
					}
				}
			}
			
			if ($vred_id !== null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id, text) VALUES ('$if_id', '$sid', '$vrstn_red', '$vred_id', '$text')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red', vre_id='$vred_id', text='$text'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				return $cond_id;
			}
		}
		# 16. multi checkbox
		if ($tip == '16' ) {
			$vred_id=null;

			# sekvenca je podana pod k
			$seq = $_POST['k'.$vrstn_red];
			
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			foreach ($spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $seq) { 
						$vred_id = $variable['vr_id'];
						$grid_id = $variable['gr_id'];
					}
				}
			}
			
			if ($vred_id !== null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id) VALUES ('$if_id', '$sid', '$vrstn_red', '$vred_id')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red', vre_id='$vred_id'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);

				#dodamo še v srv_grid
				if ($cond_id > 0 && $grid_id > 0) {
					$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$grid_id."')";
					$sql = sisplet_query($istr);
					if (!$sql)  {
						echo '<br>-4 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
					
				} else {
						echo '<br>-5 :: ';
				}
				return $cond_id;
			}
		}
		# 6. multi radio
		if ($tip == '6' ) {
			$vred_id=null;
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			$seq = $_POST['sq'.$vrstn_red];
			foreach ($spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $seq) { 
						$vred_id = $variable['vr_id'];
					}
				}
			}
			
			if ($vred_id !== null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id) VALUES ('$if_id', '$sid', '$vrstn_red', '$vred_id')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red', vre_id='$vred_id'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);

				#dodamo še v srv_grid
				if ($cond_id > 0) {
					$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$_POST['k'.$vrstn_red]."')";
					$sql = sisplet_query($istr);
					if (!$sql)  {
						echo '<br>-4 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
					
				} else {
						echo '<br>-5 :: ';
				}
				return $cond_id;
			}
		}
		# 17. razvrščanje ranking
		if ($tip == '17' ) {
			
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			$seq = $_POST['sq'.$vrstn_red];
			
			foreach ($spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $seq) { 
						$vred_id = $variable['vr_id'];
					}
				}
			}
			
			if ($vred_id !== null && (int)$vred_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id) VALUES ('$if_id', '$sid', '$vrstn_red', '$vred_id')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red', vre_id='$vred_id'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				$grid_id = $_POST['k'.$vrstn_red];
				#dodamo še v srv_grid
				if ($cond_id > 0 && $grid_id > 0) {
					$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$grid_id."')";
					$sql = sisplet_query($istr);
					if (!$sql)  {
						echo '<br>-4 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
				
				} else {
						echo '<br>-5 :: ';
				}
				return $cond_id;
			}	
		}
		# 19. multi text
		# 20. multi number
		if ($tip == '19' || $tip == '20') {
			$text=$_POST['k'.$vrstn_red];
			#pogledamo za katero vrednost iščemo s pomočjo sekvence
			$seq = $_POST['sq'.$vrstn_red];
			
			foreach ($spr['grids'] AS $gkey=> $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					if ($variable['sequence'] == $seq) { 
						$vred_id = $variable['vr_id'];
						$grid_id = $variable['gr_id'];
					}
				}
			}
			
			if ($vred_id !== null && (int)$vred_id > 0 && $grid_id > 0) {
				$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red, vre_id, grd_id, text) VALUES ('$if_id', '$sid', '$vrstn_red', '$vred_id', '$grid_id', '$text')"
				. " ON DUPLICATE KEY UPDATE spr_id='$sid', vrstni_red = '$vrstn_red', grd_id='$grid_id', text='$text'";
				$sql = sisplet_query($istr);

				if (!$sql)  {
					echo '<br>-3 :: '.$istr;
					echo mysqli_error($GLOBALS['connect_db']);
				}
				$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				return $cond_id;
			}
		}

		return null;
	}
	
	function changeSessionInspect() {
		@session_start();
		#Zamenjamo sejo		
		if (isset($_SESSION['enableInspect']) && $_SESSION['enableInspect'] == true) {
			unset($_SESSION['enableInspect']);
		} else {
			$_SESSION['enableInspect'] = true;
		}
		@session_commit();
		
		#nastavimo inspect
		$sdsp = SurveyDataSettingProfiles :: getSetting();
		if ($sdsp['enableInspect'] == '1' || (isset($_SESSION['enableInspect']) && $_SESSION['enableInspect'] == true)) {
			$this->enableInspect = true;
		} else {
			# če ne preberemo iz profila
			$this->enableInspect = false;
		}
		
		$this->displaySessionInspectCheckbox();
	}
	function displaySessionInspectCheckbox() {
		global $lang;
		echo '<input type="checkbox" id="session_inspect" '.($this->enableInspect == true ? ' checked="checekd"' : '').' onClick="changeSessionInspect();">'.$lang['srv_inspect_setting'];
		echo Help :: display('srv_crosstab_inspect');
	}	


	function displayLinePercent() {
		global $lang;
		
		echo '<input id="crossCheck1" name="crossCheck1" onchange="change_crosstab_percent();" type="checkbox" ' . ($this->crossChk1 == true ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo '<label for="crossCheck1" id="spn_residual_sp1" class="ctbChck_sp1">' . $lang['srv_analiza_crosstab_odstotek_vrstice_short'].'</label>';
		echo '<input id="crossDoColor" name="crossDoColor" onchange="change_crosstab_color();" type="checkbox" ' . ($this->doColor == true ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo '<label for="crossDoColor" id="spn_residual_sp1" class="ctbChck_sp1">' . $lang['srv_analiza_crosstab_color'].'</label>';
	}
	
	function displayResidual(){
		global $lang;

		$selected = ($this->crossChkEC + $this->crossChkRE + $this->crossChkSR + $this->crossChkAR == 4) ? true : false;
			
		echo '<input id="crossResiduals" name="crossResiduals" onchange="saveResidualProfileSetting(\''.SurveyDataSettingProfiles::getCurentProfileId().'\', this.checked); return false;" type="checkbox" ' . ($selected ? ' checked="checked" ' : '') . ' autocomplete="off"/>';
		echo '<label for="crossResiduals" id="crossResiduals" class="show_residual">' . $lang['srv_analiza_crosstab_residuals'].'</label>';
	}
	
	function displayShowChart() {
		global $lang;
		
		echo '<input id="showChart" name="showChart" onchange="showTableChart(\'crosstab\');" type="checkbox" ' . ($this->showChart == true ? ' checked="checked" ' : '') . ' />';
		echo '<label for="showChart" id="showChart" class="showChart">'.$lang['srv_show_charts'].'</label>';
	}
	
	function presetVariables() {
		# preberemo prednastavljene variable iz seje, če obstajajo
		if (isset($this->sessionData['crosstabs']['crosstab_variables']['variabla1']) && count($this->sessionData['crosstabs']['crosstab_variables']['variabla1']) > 0) {
			$this->variabla1 = $this->sessionData['crosstabs']['crosstab_variables']['variabla1'];
		} 
		if (isset($this->sessionData['crosstabs']['crosstab_variables']['variabla2']) && count($this->sessionData['crosstabs']['crosstab_variables']['variabla2']) > 0) {
			$this->variabla2 = $this->sessionData['crosstabs']['crosstab_variables']['variabla2'];
		} 
	} 
}
?>