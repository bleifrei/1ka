<?php

define("AUTO_HIDE_ZERRO_VALUE", 20);					# nad koliko kategorij skrivamo ničelne vrednosti
define("EXPORT_FOLDER", "admin/survey/SurveyData");
define("R_FOLDER", "admin/survey/R");

class SurveyMultiCrosstabs {

	public $ank_id;									# id ankete

	public $table_id = 0;							# id tabele, ki jo trenutno izrisujemo
	public $table_settings = array();				# nastavitve za tabelo
	
	public $db_table;								# katere tabele uporabljamo
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
		
	public $_HEADERS = array();						# shranimo podatke vseh variabel
	
	public $_HAS_TEST_DATA = false;					# ali anketa vsebuje testne podatke

	public $_CURRENT_STATUS_FILTER = ''; 			# filter po statusih, privzeto izvažamo 6 in 5
	public $currentMissingProfile = 1; 				# Kateri Missing profil je izbran
	public $missingProfileData = null; 				# Nastavitve trenutno izbranega manjkajočega profila

	public $_CURRENT_LOOP = null;					# trenutni loop
	
	/* Variable so definirane v obliki:
	 * '37507_0_0_0' = x_y_z_w
	 * 	-> x => spr_id
	 * 	-> y => loop id
	 * 	-> z => grid_id
	 * 	-> y => variable id
	 *
	 */
	public $variablesList = null; 					# Seznam vseh variabel nad katerimi lahko izvajamo crostabulacije (zakeširamo)
	
	public $selectedVars = null; 					# Seznam izbranih variabel v tabeli	
	public $crosstabData = null;					# Izracunani podatki za izbrane spremenljivke
	public $crosstabClass = null;					# Instanca crosstab razreda (za racunanje)

	public $colSpan = 0;							# Celoten span stolpcev (stevilo vseh childov)
	public $rowSpan = 0;							# Celoten span vrstic (stevilo vseh childov)
	public $colLevel2 = false;						# Ali imamo v stolpcih kaksen 2. nivo
	public $rowLevel2 = false;						# Ali imamo v vrsticah kaksen 2. nivo
	public $fullColSpan = 0;						# Celoten span stolpcev (stevilo vseh childov) z sumami (ce jih imamo)
	
	public $isCheckbox = false;						# Ce je kaksen checkbox v tabeli - potem imamo opcijo navedbe/enote

	
	/**
	 * Inicializacija
	 *
	 * @param int $anketa
	 */
	public function __construct( $anketa = null ) {
		global $global_user_id, $site_path, $lang;

        // če je podan ID ankete
		if ((int)$anketa > 0) { 
            
            $this->ank_id = $anketa;

            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->ank_id);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
			
			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->ank_id);
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
			
			$this->_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/';
			
			SurveyStatusProfiles::Init($this->ank_id);
			SurveyMissingProfiles :: Init($this->ank_id, $global_user_id);

			SurveyConditionProfiles :: Init($this->ank_id, $global_user_id);
			SurveyZankaProfiles :: Init($this->ank_id, $global_user_id);
			SurveyTimeProfiles :: Init($this->ank_id, $global_user_id);
			SurveyDataSettingProfiles :: Init($this->ank_id);
			
			# nastavimo vse filtre
			$this->setUpFilter();
						
			SurveyUserSetting::getInstance()->Init($this->ank_id, $global_user_id);
			
			# Ce ne obstaja nobena tabela jo ustvarimo
			$sql = sisplet_query("SELECT id FROM srv_mc_table WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id'");
			if(mysqli_num_rows($sql) == 0){
				$name = $lang['srv_table'].' 1';
				sisplet_query("INSERT INTO srv_mc_table (ank_id, usr_id, time_created, name) VALUES('$this->ank_id', '$global_user_id', NOW(), '$name')");		
				$table_id = mysqli_insert_id($GLOBALS['connect_db']);
				
				$this->table_id = $table_id;
				SurveyUserSetting :: getInstance()->saveSettings('default_mc_table', $table_id);
				
				$this->table_settings[$this->table_id] = array(
					'title' 	=> '',
					'numerus' 	=> 1,
					'percent' 	=> 0,
					'sums' 		=> 0,
					'navVsEno' 	=> 1,
					'avgVar' 	=> '',
					'delezVar' 	=> '',
					'delez' 	=> ''
				);
			}
			else{
				$this->table_id = SurveyUserSetting :: getInstance()->getSettings('default_mc_table');
				
				// Preberemo nastavitve trenutno izbrane tabele
				if(isset($this->table_id) && $this->table_id != ''){
					$sql = sisplet_query("SELECT * FROM srv_mc_table WHERE id='$this->table_id' AND ank_id='$this->ank_id' AND usr_id='$global_user_id'");
				}
				else{
					$sql = sisplet_query("SELECT * FROM srv_mc_table WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id' ORDER BY time_created ASC");
				}
				$row = mysqli_fetch_array($sql);
				$this->table_id = $row['id'];
				$this->table_settings[$this->table_id] = array(
					'title' 	=> $row['title'],
					'numerus' 	=> $row['numerus'],
					'percent' 	=> $row['percent'],
					'sums' 		=> $row['sums'],
					'navVsEno' 	=> $row['navVsEno'],
					'avgVar' 	=> $row['avgVar'],
					'delezVar' 	=> $row['delezVar'],
					'delez' 	=> $row['delez']
				);
			}
		} 
		else {
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
	
	function display () {
		global $lang;
		global $global_user_id;
				
		// Napolnimo variable s katerimi lahko operiramo
		$this->getVariableList();

		//$this->displayLinks();
		$this->displayFilters();
		
		$this->displayExport();


		echo '<div id="mc_holder">';
		
		
		// Div s spremenljivkami za drag - zaenkrat samo radio, checkbox, dropdown, multigrid, multicheckbox - ZAENKRAT BREZ CHECKBOXOV (2,16)!
		echo '<div id="spr_list"><ul>';		
		foreach($this->variablesList AS $spr){
			if($spr['canChoose'] && in_array($spr['tip'], array(1,3,6))){
				
				echo '<li class="draggable mc_draggable" id="'.$spr['spr_id'].'-'.$spr['sequence'].'">';		
				echo '<span class="strong">'.$spr['variable'].'</span> - '.$this->snippet($spr['naslov'], 25);			
				echo '</li>';
			}
		}
		echo '</ul></div>';	
		
			
		// Izris diva za izbiro tabele
		$this->displayMCTablesPopups();
		
		// Izris diva za nastavitve tabele
		echo '<div id="mc_table_settings" class="mc_table_settings">';
		$this->displayTableSettings();
		echo '</div>';

		// Naslov tabele
		echo '<div id="title_'.$this->table_id.'" class="mc_table_title">';
		$this->displayTableTitle($this->table_settings[$this->table_id]['title']);
		echo '</div>';
		
		// Izris tabele
		echo '<div id="mc_table_holder_'.$this->table_id.'" class="mc_table_holder">';
		$this->displayTable();		
		echo '</div>';

		echo '</div>';	
	}
	
	// Prikaze tabelo s podatki
	public function displayTable(){
		global $lang;
		global $site_url;
	
		// Napolnimo variable ki so ze izbrane
		$this->getSelectedVars();

		echo '<table id="'.$this->table_id.'" cellspacing="0" cellpadding="0" class="mc_table">';

		
		// Imamo 2 nivoja
		if($this->colLevel2){
			
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			if($this->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			echo '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->selectedVars['ver'])){
				foreach($this->selectedVars['ver'] as $var){
					
					$rowspan = count($var['sub']) > 0 ? '':' rowspan="2"';
					$colspan = ' colspan="'.$var['span'].'"';
					echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="undefined" class="spr vertical droppable full" '.$rowspan . $colspan.'>';
					
					echo $this->snippet($this->variablesList[$var['spr']]['naslov'], 25);
					
					// Gumb za brisanje
					echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';
					
					echo '</td>';
				}
			}
			// Izrisemo se zadnjo prazno navpicno celico vrstico
			echo '<td id="undefined" class="spr vertical droppable empty" rowspan="4">'.$lang['srv_multicrosstabs_add'].'</td>';		
			echo '</tr>';
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if($this->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			echo '<tr><td class="borderless" '.$colspan.'></td>';	
			if(count($this->selectedVars['ver'])){
				foreach($this->selectedVars['ver'] as $var){

					if(count($var['sub']) > 0){
						// Loop cez variable spremenljivke
						foreach($this->variablesList[$var['spr']]['options'] as $option){

							$colspan = ' colspan="'.( $var['span'] / count($this->variablesList[$var['spr']]['options']) ).'"';
							echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="undefined" class="var vertical full" '.$colspan.'>';
							
							echo $this->snippet($option, 25);			
							
							echo '</td>';
						}
					}
				}
			}
			echo '</tr>';
			
			// Izris vrstic za 2. nivo - 3. in 4. vrstica
		
			if($this->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			echo '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->selectedVars['ver'])){
				foreach($this->selectedVars['ver'] as $parentVar){
					
					foreach($this->variablesList[$parentVar['spr']]['options'] as $option){
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							foreach($parentVar['sub'] as $var){				

								$colspan = ' colspan="'.( count($this->variablesList[$var['spr']]['options']) ).'"';
								echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="'.$parentVar['vrstni_red'].'" class="spr vertical full"  '.$colspan.'>';
								
								echo $this->snippet($this->variablesList[$var['spr']]['naslov'], 25);
								
								// Gumb za brisanje
								echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';
								
								echo '</td>';
							}
						}
						else{
							$rowspan = ' rowspan="2"';
							$colspan = ' colspan="'.( $parentVar['span'] / count($this->variablesList[$parentVar['spr']]['options']) ).'"';
							echo '<td id="'.$parentVar['vrstni_red'].'" spr_id="'.$parentVar['spr'].'" parent="undefined" class="var vertical full" '.$rowspan . $colspan.'>';
							
							echo $this->snippet($option, 25);			
							
							echo '</td>';
						}
					}
				}
			}
			echo '</tr>';
			
			if($this->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			echo '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->selectedVars['ver'])){
				foreach($this->selectedVars['ver'] as $parentVar){
					
					foreach($this->variablesList[$parentVar['spr']]['options'] as $option){
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							foreach($parentVar['sub'] as $var){				
								
								foreach($this->variablesList[$var['spr']]['options'] as $suboption){
									echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="'.$parentVar['vrstni_red'].'" class="var vertical full">';
									
									echo $this->snippet($suboption, 25);
									
									// Gumb za brisanje
									echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';
									
									echo '</td>';
								}
							}
						}
					}
				}
			}
			echo '</tr>';
		}
		// Imamo samo 1 nivo
		else{
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			if($this->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			echo '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->selectedVars['ver'])){
				foreach($this->selectedVars['ver'] as $var){
					
					$colspan = ' colspan="'.($this->table_settings[$this->table_id]['sums'] == 1 && !$this->rowLevel2 ? $var['span']+1 : $var['span']).'"';
					echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="undefined" class="spr vertical droppable full" '.$colspan.'>';
					
					echo $this->snippet($this->variablesList[$var['spr']]['naslov'], 25);
					
					// Gumb za brisanje
					echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';
					
					echo '</td>';
				}
			}
			// Nimamo nobene vertikalne spremenljivke in 2 horizontalni
			elseif($this->rowLevel2){
				echo '<td class="borderless"></td>';
			}

			// Izrisemo se zadnjo prazno navpicno celico vrstico
			echo '<td id="undefined" class="spr vertical droppable empty" rowspan="2">'.$lang['srv_multicrosstabs_add'].'</td>';		
			echo '</tr>';
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if($this->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			echo '<tr><td class="borderless" '.$colspan.'></td>';	
			if(count($this->selectedVars['ver'])){
				foreach($this->selectedVars['ver'] as $var){

					// Loop cez variable spremenljivke
					foreach($this->variablesList[$var['spr']]['options'] as $option){

						$colspan = ' colspan="'.( $var['span'] / count($this->variablesList[$var['spr']]['options']) ).'"';
						echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="undefined" class="var vertical full" '.$colspan.'>';
						
						echo $this->snippet($option, 25);			
						
						echo '</td>';
					}
					
					// Suma (ce jo imamo vklopljeno)
					if($this->table_settings[$this->table_id]['sums'] == 1 && !$this->rowLevel2){						
						echo '<td class="var sums">';
						echo $lang['srv_analiza_crosstab_skupaj'];
						echo '</td>';
					}
				}
			}
			echo '</tr>';
		}

		
		
		// Izrisemo HORIZONTALNO izbrane variable
		if(count($this->selectedVars['hor'])){
		
			// Imamo 2 nivoja vrstic
			if($this->rowLevel2){
				foreach($this->selectedVars['hor'] as $parentVar){
					
					$cnt = 0;
					$order0 = 0;
					
					foreach($this->variablesList[$parentVar['spr']]['options'] as $option){
					
						$cnt2 = 0;
					
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							foreach($parentVar['sub'] as $var){
							
								$cnt3 = 0;
						
								foreach($this->variablesList[$var['spr']]['options'] as $suboption){
															
									echo '<tr>';
									
									if($cnt == 0){
										$span = $this->table_settings[$this->table_id]['sums'] == 1 && count($this->selectedVars['ver']) == 0 ? $parentVar['span']+(count($parentVar['sub'])*count($this->variablesList[$parentVar['spr']]['options'])) : $parentVar['span'];
										$rowspan = ' rowspan="'.$span.'"';
										echo '<td id="'.$parentVar['vrstni_red'].'" spr_id="'.$parentVar['spr'].'" parent="undefined" class="spr horizontal droppable full" '.$rowspan.'>';				
										
										echo $this->snippet($this->variablesList[$parentVar['spr']]['naslov'], 25);
										
										// Gumb za brisanje
										echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';				
										echo '</td>';
									}
									
									// Variabla
									if($cnt2 == 0){
										$span = $this->table_settings[$this->table_id]['sums'] == 1 && count($this->selectedVars['ver']) == 0 ? $parentVar['span'] / count($this->variablesList[$parentVar['spr']]['options']) + count($parentVar['sub']) : $parentVar['span'] / count($this->variablesList[$parentVar['spr']]['options']);
										$rowspan = ' rowspan="'.$span.'"';
										echo '<td class="var horizontal full" '.$rowspan.'>';												
										echo $this->snippet($option, 25);			
										echo '</td>';
									}
									
									if($cnt3 == 0){
										$span = $this->table_settings[$this->table_id]['sums'] == 1 && count($this->selectedVars['ver']) == 0 ? count($this->variablesList[$var['spr']]['options']) + 1 : count($this->variablesList[$var['spr']]['options']);
										$rowspan = ' rowspan="'.$span.'"';
										echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="'.$parentVar['vrstni_red'].'" class="spr horizontal full" '.$rowspan.'>';				
										
										echo $this->snippet($this->variablesList[$var['spr']]['naslov'], 25);
										
										// Gumb za brisanje
										echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';				
										echo '</td>';
									}
									
									// Variabla 2
									echo '<td class="var horizontal full">';												
									echo $this->snippet($suboption, 25);			
									echo '</td>';
									
									// Celice s podatki
									$this->displayDataCells($parentVar, $order0, $var, $cnt3);
									
									echo '<td class="empty"></td>';
									
									echo '</tr>';
										
									$cnt++;	
									$cnt2++;
									$cnt3++;
								}
								
								$order0++;
								
								// Izrisemo se sumo ce je vklopljena
								if($this->table_settings[$this->table_id]['sums'] == 1 && count($this->selectedVars['ver']) == 0){
									
									echo '<tr>';
									
									echo '<td class="var sums">'.$lang['srv_analiza_crosstab_skupaj'].'</td>';
									
									$crosstabs = $this->crosstabData[$parentVar['spr'].'-'.$var['spr']];
								
									$keys1 = array_keys($crosstabs['options2']);
									$key = ceil($cnt / (count($this->variablesList[$var['spr']]['options'])*count($parentVar['sub']))) - 1;
									$val = $keys1[$key];
								
									$this->displaySumsCell($parentVar, $var, $val, $orientation=0);
									
									echo '<td class="empty"></td>';
									
									echo '</tr>';
								}
							}
						}
						else{
							echo '<tr>';
							
							if($cnt == 0){
								$rowspan = ' rowspan="'.$parentVar['span'].'"';
								echo '<td id="'.$parentVar['vrstni_red'].'" spr_id="'.$parentVar['spr'].'" parent="undefined" class="spr horizontal droppable full" '.$rowspan.' colspan="2">';				
								
								echo $this->snippet($this->variablesList[$parentVar['spr']]['naslov'], 25);
								
								// Gumb za brisanje
								echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';				
								echo '</td>';
							}
								
							// Variabla
							$rowspan = ' rowspan="'.( $parentVar['span'] / count($this->variablesList[$parentVar['spr']]['options']) ).'"';
							echo '<td class="var horizontal full" '.$rowspan.' colspan="2">';												
							echo $this->snippet($option, 25);			
							echo '</td>';
								
							
							// Celice s podatki
							$this->displayDataCells($parentVar, $cnt);
							
							echo '<td class="empty"></td>';
							
							echo '</tr>';
							
							$cnt++;
						}
					}
				}
			}
			// Imamo samo 1 nivo vrstic
			else{
				foreach($this->selectedVars['hor'] as $var){
					
					$cnt = 0;
					foreach($this->variablesList[$var['spr']]['options'] as $option){
						echo '<tr>';
						
						if($cnt == 0){
							$rowspan = ' rowspan="'.($this->table_settings[$this->table_id]['sums'] == 1 && count($this->selectedVars['ver']) > 0 && !$this->colLevel2 ? $var['span']+1 : $var['span']).'"';
							echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="undefined" class="spr horizontal droppable full" '.$rowspan.'>';				
									
							echo $this->snippet($this->variablesList[$var['spr']]['naslov'], 25);
							
							// Gumb za brisanje
							echo '<div class="delete_var" onclick="deleteVariable(this);"></div>';				
							echo '</td>';
						}
						
						// Variabla
						echo '<td id="'.$var['vrstni_red'].'" spr_id="'.$var['spr'].'" parent="undefined" class="var horizontal full">';												
						echo $this->snippet($option, 25);			
						echo '</td>';
						
						// Celice s podatki
						$this->displayDataCells($var, $cnt);
						
						
						echo '<td class="empty"></td>';
						
						echo '</tr>';
						
						$cnt++;
					}
					
					// Vrstica za sumo (ce jo imamo vklopljeno)
					if($this->table_settings[$this->table_id]['sums'] == 1 && count($this->selectedVars['ver']) > 0 && !$this->colLevel2){
						echo '<tr>';
						echo '<td class="var sums">'.$lang['srv_analiza_crosstab_skupaj'].'</td>';
						
						// Loop cez vse stolpce
						foreach($this->selectedVars['ver'] as $spr2){
						
							// Loop cez variable trenutnega stolpca
							$cnt = 0;
							foreach($this->variablesList[$spr2['spr']]['options'] as $var2){
								
								$crosstabs = $this->crosstabData[$var['spr'].'-'.$spr2['spr']];
								
								$keys1 = array_keys($crosstabs['options1']);
								$val = $keys1[$cnt];
								
								$this->displaySumsCell($var, $spr2, $val, $orientation=1);
									
								$cnt++;
							}
							
							// Krizanje navpicne in vodoravne sume
							$this->displaySumsCell($var, $spr2, 0, $orientation=2);
						}

						echo '<td class="empty"></td>';
						
						echo '</tr>';
					}
				}
			}
		}
		
		
		// Izrisemo se zadnjo prazno vodoravno vrstico
		echo '<tr class="last">';
		
		if($this->rowSpan == 0)
			$colspan = ' colspan="1"';
		elseif(!$this->rowLevel2)
			$colspan = ' colspan="2"';
		else
			$colspan = ' colspan="4"';	
		echo '<td id="undefined" class="spr horizontal droppable empty" '.$colspan.'>';
		echo $lang['srv_multicrosstabs_add'];
		echo '</td>';
		
		for($i=0; $i<=$this->colSpan; $i++){
			echo '<td class="empty"></td>';
		}
		
		// Dodatne prazne celice ce imamo sumo
		if($this->table_settings[$this->table_id]['sums'] == 1 && ((!$this->colLevel2 && !$this->rowLevel2) || count($this->selectedVars['ver']) == 0)){
			for($i=0; $i<count($this->selectedVars['ver']); $i++){
				echo '<td class="empty"></td>';
			}
			
			if(count($this->selectedVars['ver']) == 0 && $this->rowLevel2)
				echo '<td class="empty"></td>';
		}
		
		echo '</tr>';
		
		
		echo '</table>';
		
		
		
		echo '<div class="mc_table_bottom_settings">';
		
		// Izrisemo legendo
		$this->displayLegend();
		
				
		// Ce smo v custom reportu tega ne izpisemo
		if($_GET['m'] != 'analysis_creport'){
		
			// Zvezdica za vkljucitev v porocilo	
			SurveyAnalysisHelper::getInstance()->addCustomReportElement($type=10, $sub_type=0, $spr1=$this->table_id);
		
		
			echo '<script type="text/javascript">';
			
			// Nastavimo droppable (drugace po ajaxu ne dela)
			echo '$(function(){createDroppable();});';	
			
			// Nastavimo gumb za brisanje spremenljivke
			echo '$(".mc_table tr td.spr").mouseover(function(){$(this).find(".delete_var").show();});';
			echo '$(".mc_table tr td.spr").mouseout(function(){$(this).find(".delete_var").hide();});';
			
			echo '</script>';
		}
		
		echo '</div>';
	}
	
	// Izpis celic v vrstici s podatki
	function displayDataCells($spr1, $var1, $spr2='', $var2=''){
	
		// Ce nimamo nobenega krizanja izpisemo prazne
		if($spr2 == '' && $this->colSpan == 0){
			for($i=0; $i<$this->colSpan; $i++){
				echo '<td class="data"></td>';
			}
		}
	
		// Ce nimamo stolpcev - krizanje dveh vrstic
		elseif($spr2 != '' && $this->colSpan == 0){

			$spr1_temp = explode('-', $spr1['spr']);
			$grd = $this->variablesList[$spr1['spr']]['grd_id'];
			$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
			
			$spr2_temp = explode('-', $spr2['spr']);
			$grd = $this->variablesList[$spr2['spr']]['grd_id'];
			$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
			
			// Ce se nimamo izracunanih rezultatov jih izracunamo
			if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
				$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
			else{							
				$variables = array();
				$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
				$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
					
				$this->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->createCrostabulation($variables);

				$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
			}

			//$var1 = floor(($var1) / (count($this->variablesList[$spr2['spr']]['options'])*count($spr1['sub'])));
			$keys1 = array_keys($crosstabs['options1']);
			$val1 = $keys1[$var1];
			
			$keys2 = array_keys($crosstabs['options2']);
			$val2 = $keys2[$var2];

			$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
			$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
			$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
			$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
			
			$this->displayDataCell($crosstab, $percent, $avg, $delez);
		}
		
		// Krizanje 1 vrstice in 1 stolpca
		elseif($spr2 == '' && !$this->colLevel2){
		
			// Loop cez vse stolpce
			foreach($this->selectedVars['ver'] as $spr2){

				$spr1_temp = explode('-', $spr1['spr']);
				$grd = $this->variablesList[$spr1['spr']]['grd_id'];
				$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
				
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
				
				
				// Ce se nimamo izracunanih rezultatov jih izracunamo
				if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
					$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
				else{
					$variables = array();
					$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
					$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
					
					$this->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->createCrostabulation($variables);	

					$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
				}

				$keys1 = array_keys($crosstabs['options1']);
				$val1 = $keys1[$var1];

				// Loop cez variable trenutnega stolpca
				$cnt = 0;
				foreach($this->variablesList[$spr2['spr']]['options'] as $var2){
				
					$keys2 = array_keys($crosstabs['options2']);
					$val2 = $keys2[$cnt];
		
					$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
					$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
					$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
					$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
					
					$this->displayDataCell($crosstab, $percent, $avg, $delez);
						
					$cnt++;
				}
				
				// Suma (ce jo imamo vklopljeno)
				if($this->table_settings[$this->table_id]['sums'] == 1 && !$this->rowLevel2){						
					$this->displaySumsCell($spr1, $spr2, $val1, $orientation=0);
				}
			}
		}
		
		// Izpisemo vecnivojske podatke (krizanje 3 ali 4 spremenljivk)
		else{	
		
			// Nastavimo 1. vrsticno variablo
			$spr1_temp = explode('-', $spr1['spr']);
			$grd = $this->variablesList[$spr1['spr']]['grd_id'];
			$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
				
			// Krizanje 2 vrstic in 1 stolpca
			if(!$this->colLevel2){
			
				// Nastavimo 2. vrsticno variablo
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);			
			
				// Loop cez vse stolpce
				foreach($this->selectedVars['ver'] as $spr3){
								
					$spr3_temp = explode('-', $spr3['spr']);
					$grd = $this->variablesList[$spr3['spr']]['grd_id'];
					$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);			
					
					// Ce se nimamo izracunanih rezultatov jih izracunamo
					if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
						$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
					else{
						$variables = array();
						$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
						$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
						$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
						
						$this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->createCrostabulation($variables);	

						$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
					}

					$keys1 = array_keys($crosstabs['options1']);
					$val1 = $keys1[$var1];
					
					$keys2 = array_keys($crosstabs['options2']);
					$val2 = $keys2[$var2];

					// Loop cez variable trenutnega stolpca
					$cnt = 0;
					foreach($this->variablesList[$spr3['spr']]['options'] as $var3){
					
						$keys3 = array_keys($crosstabs['options3']);
						$val3 = $keys3[$cnt];

						$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
						$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
						$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
						$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
					
						$this->displayDataCell($crosstab, $percent, $avg, $delez);
							
						$cnt++;
					}
				}
			}
			
			// Krizanje 1 vrstice in 2 stolpcev
			elseif($spr2 == ''){
							
				// Loop cez vse stolpce 1. navpicne spremenljivke
				foreach($this->selectedVars['ver'] as $spr2){
								
					$spr2_temp = explode('-', $spr2['spr']);
					$grd = $this->variablesList[$spr2['spr']]['grd_id'];
					$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);							

					// Loop cez variable 1. navpicne spremnljivke
					$cnt2 = 0;
					foreach($this->variablesList[$spr2['spr']]['options'] as $var2){	

						// Loop cez vse navpicne spremenljivke 2. nivoja - ce obstajajo							
						if(count($spr2['sub']) > 0){
							foreach($spr2['sub'] as $spr3){
								
								// Nastavimo navpicno spremenljivko 2. nivoja	
								$spr3_temp = explode('-', $spr3['spr']);
								$grd = $this->variablesList[$spr3['spr']]['grd_id'];
								$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);
								
								// Ce se nimamo izracunanih rezultatov jih izracunamo
								if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
									$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
								else{
									$variables = array();
									$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
									$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
									$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
									
									$this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->createCrostabulation($variables);	

									$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
								}

								$keys1 = array_keys($crosstabs['options1']);
								$val1 = $keys1[$var1];
								
								$keys2 = array_keys($crosstabs['options2']);
								$val2 = $keys2[$cnt2];

								// Loop cez variable spremenljivke 2. nivoja
								$cnt3 = 0;
								foreach($this->variablesList[$spr3['spr']]['options'] as $var3){
									
									$keys3 = array_keys($crosstabs['options3']);
									$val3 = $keys3[$cnt3];

									$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
									$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
									$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
									$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
								
									$this->displayDataCell($crosstab, $percent, $avg, $delez);
																			
									$cnt3++;
								}
							}
						}
						// 1 nivojska spremenljivka v stolpcu
						else{
							// Ce se nimamo izracunanih rezultatov jih izracunamo
							if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
								$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
							else{
								$variables = array();
								$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
								$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
								
								$this->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->createCrostabulation($variables);	

								$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
							}

							$keys1 = array_keys($crosstabs['options1']);
							$val1 = $keys1[$var1];
							
							$keys2 = array_keys($crosstabs['options2']);
							$val2 = $keys2[$cnt2];

							$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
							$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
							$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
							$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
						
							$this->displayDataCell($crosstab, $percent, $avg, $delez);						
						}
						
						$cnt2++;
					}
				}
			}

			
			
			// Krizanje 2 vrstic in 2 stolpcev
			else{
			
				// Nastavimo 2. vrsticno variablo
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
			
				// Loop cez vse stolpce 1. navpicne spremenljivke
				foreach($this->selectedVars['ver'] as $spr3){
								
					$spr3_temp = explode('-', $spr3['spr']);
					$grd = $this->variablesList[$spr3['spr']]['grd_id'];
					$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);							

					// Loop cez variable 1. navpicne spremnljivke
					$cnt3 = 0;
					foreach($this->variablesList[$spr3['spr']]['options'] as $var3){	

						// Loop cez vse navpicne spremenljivke 2. nivoja								
						if(count($spr3['sub']) > 0){
							foreach($spr3['sub'] as $spr4){
								
								// Nastavimo navpicno spremenljivko 2. nivoja	
								$spr4_temp = explode('-', $spr4['spr']);
								$grd = $this->variablesList[$spr4['spr']]['grd_id'];
								$variabla4 = array('seq' => $spr4_temp[1], 'spr' => $spr4_temp[0], 'grd' => $grd);
								
								// Ce se nimamo izracunanih rezultatov jih izracunamo
								if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']]))
									$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']];
								else{
									$variables = array();
									$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
									$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
									$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
									$variables[3] = array('seq' => $variabla4['seq'], 'spr' => $variabla4['spr'], 'grd' => $variabla4['grd']);
									
									$this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']] = $this->createCrostabulation($variables);	

									$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']];
								}

								$keys1 = array_keys($crosstabs['options1']);
								$val1 = $keys1[$var1];
								
								$keys2 = array_keys($crosstabs['options2']);
								$val2 = $keys2[$var2];
								
								$keys3 = array_keys($crosstabs['options3']);
								$val3 = $keys3[$cnt3];

								// Loop cez variable spremenljivke 2. nivoja
								$cnt4 = 0;
								foreach($this->variablesList[$spr4['spr']]['options'] as $var4){
									
									$keys4 = array_keys($crosstabs['options4']);
									$val4 = $keys4[$cnt4];

									$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3][$val4])) ? $crosstabs['crosstab'][$val1][$val2][$val3][$val4] : 0;
									$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
									$avg = (isset($crosstabs['avg'][$val1][$val2][$val3][$val4])) ? $crosstabs['avg'][$val1][$val2][$val3][$val4] : 0;
									$delez = (isset($crosstabs['delez'][$val1][$val2][$val3][$val4])) ? $crosstabs['delez'][$val1][$val2][$val3][$val4] : 0;
								
									$this->displayDataCell($crosstab, $percent, $avg, $delez);
										
									$cnt4++;
								}
							}
						}
						// 1 nivo navpicne spremenljivke
						else{							
							// Ce se nimamo izracunanih rezultatov jih izracunamo
							if(isset($this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
								$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
							else{
								$variables = array();
								$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
								$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
								$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
								
								$this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->createCrostabulation($variables);	

								$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
							}

							$keys1 = array_keys($crosstabs['options1']);
							$val1 = $keys1[$var1];
							
							$keys2 = array_keys($crosstabs['options2']);
							$val2 = $keys2[$var2];
							
							$keys3 = array_keys($crosstabs['options3']);
							$val3 = $keys3[$cnt3];

							$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
							$percent = ($crosstab > 0) ? $this->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
							$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
							$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
						
							$this->displayDataCell($crosstab, $percent, $avg, $delez);
						}
						
						$cnt3++;
					}
				}	
			}
			
			
			// Loop cez vse stolpce
			/*for($i=0; $i<$this->colSpan; $i++){
				echo '<td class="data"></td>';
			}*/
		}
	}
	
	// Izpis celic v vrstici s sumami ($orientation 0->vrstica, 1->stolpec, 2->skupaj)
	function displaySumsCell($spr1, $spr2, $val, $orientation){
		
		$crosstabs = $this->crosstabData[$spr1['spr'].'-'.$spr2['spr']];

		echo '<td class="sums data">';				
		echo '<table class="mc_inner_cell">';

		// Celica s skupno sumo
		if($orientation == 2){		
			// Numerus
			if($this->table_settings[$this->table_id]['numerus'] == 1){
				echo '<tr><td class="bold white">';
				echo $crosstabs['sumaSkupna'];
				echo '</td></tr>';
			}
			
			// Procenti
			if($this->table_settings[$this->table_id]['percent'] == 1){
				echo '<tr><td class="white">';
				echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				echo '</td></tr>';
			}
			
			
			// Povprecje
			if($this->table_settings[$this->table_id]['avgVar'] > 0){
			
				// Loop cez vse in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab']){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'] as $key1 => $row){	
						foreach($row as $key2 => $count){
							$tempAvg += $count * $crosstabs['avg'][$key1][$key2];
						}
					}
					$avg = ($crosstabs['sumaSkupna'] > 0) ? $tempAvg / $crosstabs['sumaSkupna'] : 0;
				}
				
				echo '<tr><td class="blue">';
				echo $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
				echo '</td></tr>';
			}
			
			// Delez
			if($this->table_settings[$this->table_id]['delezVar'] > 0){
			
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez']){	
					foreach($crosstabs['delez'] as $row){	
						foreach($row as $tempDelez){
							$delez += $tempDelez;
						}
					}
				}
				
				echo '<tr><td class="red">';
				echo $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				echo '</td></tr>';
			}
		}
		
		// Suma na koncu vrstice
		elseif($orientation == 0){
			// Izpisemo podatek
			if($crosstabs['sumaVrstica'][$val]){	
				// Numerus
				if($this->table_settings[$this->table_id]['numerus'] == 1){
					echo '<tr><td class="bold white">';
					echo $crosstabs['sumaVrstica'][$val];
					echo '</td></tr>';
				}
				// Procenti
				if($this->table_settings[$this->table_id]['percent'] == 1){
					echo '<tr><td class="white">';
					echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					echo '</td></tr>';
				}
			}
			else{		
				// Numerus
				if($this->table_settings[$this->table_id]['numerus'] == 1){
					echo '<tr><td class="bold white">';
					echo '0';
					echo '</td></tr>';
				}
				// Procenti
				if($this->table_settings[$this->table_id]['percent'] == 1){
					echo '<tr><td class="white">';
					echo $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					echo '</td></tr>';
				}
				
			}
			
			// Povprecje
			if($this->table_settings[$this->table_id]['avgVar'] > 0){
			
				// Loop cez vrstico in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab'][$val]){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'][$val] as $key => $count){	
						$tempAvg += $count * $crosstabs['avg'][$val][$key];
					}
					$avg = ($crosstabs['sumaVrstica'][$val] > 0) ? $tempAvg / $crosstabs['sumaVrstica'][$val] : 0;
				}
				
				echo '<tr><td class="blue">';
				echo $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
				echo '</td></tr>';
			}
			
			// Delez
			if($this->table_settings[$this->table_id]['delezVar'] > 0){
			
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez'][$val]){	
					foreach($crosstabs['delez'][$val] as $tempDelez){	
						$delez += $tempDelez;
					}
				}
				
				echo '<tr><td class="red">';
				echo $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				echo '</td></tr>';
			}
		}
		
		// Suma za stolpce
		else{
			// Izpisemo podatek
			if(isset($crosstabs['sumaStolpec'][$val])){	
				// Numerus
				if($this->table_settings[$this->table_id]['numerus'] == 1){
					echo '<tr><td class="bold white">';
					echo $crosstabs['sumaStolpec'][$val];
					echo '</td></tr>';
				}
				// Procenti
				if($this->table_settings[$this->table_id]['percent'] == 1){
					echo '<tr><td class="white">';
					echo $this->formatNumber($this->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					echo '</td></tr>';
				}
			}
			else{		
				// Numerus
				if($this->table_settings[$this->table_id]['numerus'] == 1){
					echo '<tr><td class="bold white">';
					echo '0';
					echo '</td></tr>';
				}
				// Procenti
				if($this->table_settings[$this->table_id]['percent'] == 1){
					echo '<tr><td class="white">';
					echo $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					echo '</td></tr>';
				}
			}
			
			// Povprecje
			if($this->table_settings[$this->table_id]['avgVar'] > 0){
			
				// Loop cez vrstico in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab']){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'] as $key => $row){
						if($row[$val] > 0)
							$tempAvg += $row[$val] * $crosstabs['avg'][$key][$val];
					}
					$avg = ($crosstabs['sumaStolpec'][$val] > 0) ? $tempAvg / $crosstabs['sumaStolpec'][$val] : 0;
				}
				
				echo '<tr><td class="blue">';
				echo $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
				echo '</td></tr>';
			}
			
			// Delez
			if($this->table_settings[$this->table_id]['delezVar'] > 0){
			
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez']){	
					foreach($crosstabs['delez'] as $tempDelez){	
						$delez += $tempDelez[$val];
					}
				}
				
				echo '<tr><td class="red">';
				echo $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				echo '</td></tr>';
			}
		}
		
		echo '</table>';				
		echo '</td>';
	}
	
	// Izpis celice z vrednostmi
	function displayDataCell($crosstab, $percent, $avg, $delez){
		
		echo '<td class="data">';
		echo '<table class="mc_inner_cell">';
			
		if($crosstab > 0){
		
			// Numerus
			if($this->table_settings[$this->table_id]['numerus'] == 1){
				echo '<tr><td class="white">';
				echo $crosstab;
				echo '</td></tr>';
			}
			// Procenti
			if($this->table_settings[$this->table_id]['percent'] == 1){
				echo '<tr><td class="white">';
				echo $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				echo '</td></tr>';
			}
		}
		else{		
			// Numerus
			if($this->table_settings[$this->table_id]['numerus'] == 1){
				echo '<tr><td class="white">';
				echo '0';
				echo '</td></tr>';
			}
			// Procenti
			if($this->table_settings[$this->table_id]['percent'] == 1){
				echo '<tr><td class="white">';
				echo $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				echo '</td></tr>';
			}
		}
		
		// Povprecje
		if($this->table_settings[$this->table_id]['avgVar'] > 0){
			echo '<tr><td class="blue">';
			echo $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
			echo '</td></tr>';
		}
		
		// Delez
		if($this->table_settings[$this->table_id]['delezVar'] > 0){
			echo '<tr><td class="red">';
			//echo $this->formatNumber($delez, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
			echo $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			echo '</td></tr>';
		}
		
		echo '</table>';
		echo '</td>';
	}
	
	
	function displayTableTitle($title){
		global $lang;
			
		$titleString = ($title == '') ? $lang['srv_table'] : $title;
	
		echo '<div class="multicrosstab_title_inline" contenteditable="true">';
		echo $titleString;	
		echo '</div>';
	}
	
	// Izpisemo nastavitve za tabelo (procenti, numerus, navedbe/enote...)
	function displayTableSettings(){
		global $lang;
		
		echo '<h2>'.$lang['srv_multicrosstabs_settings'].'</h2>';
		
		/*echo '<span class="clr"><input type="checkbox" id="numerus_'.$this->table_id.'" '.($this->table_settings[$this->table_id]['numerus'] == 1 ? ' checked="checked"':'').' onclick="changeMCSettings(\''.$this->table_id.'\', \'numerus\');" /><label for="numerus_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_numerus'].'</label></span>';
		echo '<span class="clr"><input type="checkbox" id="percent_'.$this->table_id.'" '.($this->table_settings[$this->table_id]['percent'] == 1 ? ' checked="checked"':'').' onclick="changeMCSettings(\''.$this->table_id.'\', \'percent\');" /><label for="percent_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_percent'].'</label></span>';
		//echo '<span class="clr"><input type="checkbox" id="avg_'.$this->table_id.'" '.($this->table_settings[$this->table_id]['avg'] == 1 ? ' checked="checked"':'').' onclick="changeMCSettings(\''.$this->table_id.'\', \'avg\');" /><label for="avg_'.$this->table_id.'"> Povprečje</label></span>';
	
		echo '<span class="clr"><input type="checkbox" id="sums_'.$this->table_id.'" '.($this->table_settings[$this->table_id]['sums'] == 1 ? ' checked="checked"':'').' onclick="changeMCSettings(\''.$this->table_id.'\', \'sums\');" /><label for="sums_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_sum'].'</label></span>';
	
		if($this->isCheckbox){
			echo '<span class="clr" style="line-height: 20px;">';
			echo '<label for="navVsEno0_'.$this->table_id.'"><input type="radio" id="navVsEno0_'.$this->table_id.'" name="navVsEno" '.($this->table_settings[$this->table_id]['navVsEno'] == 0 ? ' checked="checked"':'').' value="0" onclick="changeMCSettings(\''.$this->table_id.'\', \'navVsEno\');" />'.$lang['srv_analiza_crosstab_navedbe'].'</label>';
			echo ' <label for="navVsEno1_'.$this->table_id.'"><input type="radio" id="navVsEno1_'.$this->table_id.'" name="navVsEno" '.($this->table_settings[$this->table_id]['navVsEno'] == 1 ? ' checked="checked"':'').' value="1" onclick="changeMCSettings(\''.$this->table_id.'\', \'navVsEno\');" />'.$lang['srv_analiza_crosstab_enote'].'</label>';
			echo '</span>';
		}*/
		
		echo '<form name="mc_settings" method="post">';
		
		echo '<input type="hidden" name="anketa" value="'.$this->ank_id.'" />';
		echo '<input type="hidden" name="table_id" value="'.$this->table_id.'" />';
		
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_multicrosstabs_settings_val'].'</legend>';
		
		// Prikaz numerusa
		echo '<span class="clr"><input type="checkbox" id="numerus_'.$this->table_id.'" name="numerus" '.($this->table_settings[$this->table_id]['numerus'] == 1 ? ' checked="checked"':'').' value="1" /><label for="numerus_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_numerus'].'</label></span>';
		
		// Prikaz procentov
		echo '<span class="clr"><input type="checkbox" id="percent_'.$this->table_id.'" name="percent" '.($this->table_settings[$this->table_id]['percent'] == 1 ? ' checked="checked"':'').' value="1" /><label for="percent_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_percent'].'</label></span>';	
		
		// Prikaz vsot
		echo '<span class="clr"><input type="checkbox" id="sums_'.$this->table_id.'" name="sums" '.($this->table_settings[$this->table_id]['sums'] == 1 ? ' checked="checked"':'').' value="1" /><label for="sums_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_sum'].'</label></span>';
				
		// Navedbe / enote
		/*if($this->isCheckbox){
			echo '<span class="clr" style="line-height: 20px;">';
			echo '<label for="navVsEno0_'.$this->table_id.'"><input type="radio" id="navVsEno0_'.$this->table_id.'" name="navVsEno" '.($this->table_settings[$this->table_id]['navVsEno'] == 0 ? ' checked="checked"':'').' value="0" />'.$lang['srv_analiza_crosstab_navedbe'].'</label>';
			echo ' <label for="navVsEno1_'.$this->table_id.'"><input type="radio" id="navVsEno1_'.$this->table_id.'" name="navVsEno" '.($this->table_settings[$this->table_id]['navVsEno'] == 1 ? ' checked="checked"':'').' value="1" />'.$lang['srv_analiza_crosstab_enote'].'</label>';
			echo '</span>';
		}*/
		
		echo '</fieldset>';
		
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_multicrosstabs_settings_avg'].'</legend>';
		
		// Variabla za racunanje povprecja - numeric in ordinal (radio, dropdown, mg)
		$checked = $this->table_settings[$this->table_id]['avgVar'] == '' ? false : true;
		echo '<span class="clr"><input type="checkbox" id="avgSetting_'.$this->table_id.'" name="avgSetting" '.($checked ? ' checked="checked"':'').' onClick="toggleMCSetting(\'avgVar\');" /><label for="avgSetting_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_avg'].'</label>';	
		echo '<span id="avgVar" class="spaceLeft" '.($checked ? '' : ' style="display: none;"').'><select id="avgVar_'.$this->table_id.'" name="avgVar">';	
		echo '<option value="">'.$lang['srv_select_spr'].'...</option>';
		foreach($this->variablesList AS $spr){
			if( $spr['canChoose'] && ($spr['tip'] == 7 || (in_array($spr['tip'], array(1,3,6)) && $spr['skala'] == 0)) ){			
				echo '<option value="'.$spr['spr_id'].'-'.$spr['sequence'].'" '.($this->table_settings[$this->table_id]['avgVar'] == $spr['spr_id'].'-'.$spr['sequence'] ? ' selected="selected"' : '').'>('.$spr['variable'].') '.$this->snippet($spr['naslov'], 25).'</option>';
			}
		}
		echo '</select></span>';
		echo '</span>';
		
		echo '</fieldset>';
		
		
		echo '<fieldset>';
		echo '<legend>'.$lang['srv_multicrosstabs_settings_del'].'</legend>';
		
		// Variabla za racunanje deleza - ordinal in nominal (radio, dropdown, mg, po novem tudi checkbox, multicheckbox)
		$checked = $this->table_settings[$this->table_id]['delezVar'] == '' ? false : true;
		echo '<span class="clr"><input type="checkbox" id="delezSetting_'.$this->table_id.'" name="delezSetting" '.($checked ? ' checked="checked"':'').' onClick="toggleMCSetting(\'delezVar\');" /><label for="delezSetting_'.$this->table_id.'"> '.$lang['srv_multicrosstabs_delez'].'</label>';	
		
		echo '<span id="delezVar" class="spaceLeft" '.($checked ? '' : ' style="display: none;"').'><select id="delezVar_'.$this->table_id.'" name="delezVar" onChange="setDelez(this.value);">';
		echo '<option value="">'.$lang['srv_select_spr'].'...</option>';
		foreach($this->variablesList AS $spr){
			if($spr['canChoose'] && in_array($spr['tip'], array(1,3,6,2,16))){			
				echo '<option value="'.$spr['spr_id'].'-'.$spr['sequence'].'" '.($this->table_settings[$this->table_id]['delezVar'] == $spr['spr_id'].'-'.$spr['sequence'] ? ' selected="selected"' : '').'>('.$spr['variable'].') '.$this->snippet($spr['naslov'], 25).'</option>';
			}
		}
		echo '</select></span>';
		echo '</span>';
		
		echo '<div id="delez" '.($checked ? '' : ' style="display: none;"').'>';
		$this->displayDelez($this->table_settings[$this->table_id]['delezVar']);
		echo '</div>';
		
		echo '</fieldset>';
		
		
		echo '</form>';
		
		
		// Gumbi na dnu
		echo '<div id="mcSettingsButtons">';
		
		echo '<span class="buttonwrapper spaceRight floatLeft">';
		echo '<a class="ovalbutton ovalbutton_gray" onclick="closeMCSettings(\''.$this->table_id.'\');">';
		echo '<span>'.$lang['srv_zapri'].'</span>';
		echo '</a>';
		echo '</span>';
		
		echo '<span class="buttonwrapper spaceRight spaceLeft floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" onclick="saveMCSettings(\''.$this->table_id.'\');">';
		echo '<span>'.$lang['srv_potrdi'].'</span>';
		echo '</a>';
        echo '</span>';		
	
		echo '</div>';	
	}
	
	// Prikazemo opcije variable (checkboxe) za delez
	function displayDelez($var){
		
		// Ce imamo nastavljeno variablo za delez prikazemo vse njene opcije
		if($var != ''){	
			$delez = unserialize($this->table_settings[$this->table_id]['delez']);
			
			$cnt = 0;
			foreach($this->variablesList[$var]['options'] as $option){
				
				if($this->table_settings[$this->table_id]['delezVar'] == $var)
					$val = $delez[$cnt];
				else
					$val = 0;

				echo '<span class="clr">';
				echo '<input type="checkbox" id="delez_'.$cnt.'" name="delez_'.$cnt.'" value="1" '.($val == 1 ? ' checked="checked"' : '').' /><label for="delez_'.$cnt.'"> '.$option.'</label>';
				echo '</span>';
				
				$cnt++;
			}
		}
	}
	
	
	// Prikazuje filtre
	function displayFilters() {
        
		if ($this->dataFileStatus == FILE_STATUS_SRV_DELETED || $this->dataFileStatus == FILE_STATUS_NO_DATA){
			return false;
		}

        # nastavitve tabele multicrosstab
        $SSH = new SurveyStaticHtml($this->ank_id);
		$SSH -> displayMulticrosstabSettings();
	}
	
	// Prikaze dropdown z linki
	function displayLinks() {
		# izrišemo navigacijo za analize
		$SSH = new SurveyStaticHtml($this->ank_id);
		$SSH -> displayAnalizaSubNavigation();
	}
	
	// Prikaze izvoz za PDF/RTF
	function displayExport () {
		
		$href_print = makeEncodedIzvozUrlString('izvoz.php?b=export&m=multicrosstabs_izpis&anketa='.$this->ank_id);
		$href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=multicrosstabs_izpis&anketa='.$this->ank_id);
		$href_rtf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=multicrosstabs_izpis_rtf&anketa='.$this->ank_id);
		$href_xls = makeEncodedIzvozUrlString('izvoz.php?b=export&m=multicrosstabs_izpis_xls&anketa='.$this->ank_id);
		
		echo '<script>';
		# nastavimopravilne linke
		echo '$("#secondNavigation_links a#multicrosstabDoPdf").attr("href", "'.$href_pdf.'");';
		echo '$("#secondNavigation_links a#multicrosstabDoRtf").attr("href", "'.$href_rtf.'");';
		echo '$("#secondNavigation_links a#multicrosstabDoXls").attr("href", "'.$href_xls.'");';
        # prikažemo linke
		echo '$("#hover_export_icon a").removeClass("hidden");';
		echo '$("#secondNavigation_links a").removeClass("hidden");';
		echo '</script>';
	}
	
	// Prikazemo legendo (povprecje, delez)
	function displayLegend(){
		global $lang;
		
		if($this->table_settings[$this->table_id]['avgVar'] > 0 || $this->table_settings[$this->table_id]['delezVar'] > 0){
			echo '<div class="mc_table_legend">';
			
			// Povprecje
			if($this->table_settings[$this->table_id]['avgVar'] > 0){
				echo '<span class="clr"><span class="blue">'.$lang['srv_multicrosstabs_avg'].': </span>'.$this->variablesList[$this->table_settings[$this->table_id]['avgVar']]['variable'].'</span>';
			}
			
			// Delez
			if($this->table_settings[$this->table_id]['delezVar'] > 0){
				echo '<span class="red">'.$lang['srv_multicrosstabs_delez'].': </span>'.$this->variablesList[$this->table_settings[$this->table_id]['delezVar']]['variable'];

				$delez = unserialize($this->table_settings[$this->table_id]['delez']);
				$string = '';
				$cnt = 1;
				foreach($delez as $val){
					if($val == 1)
						$string .= $cnt.', ';
					
					$cnt++;
				}	
				echo ' ('.substr($string, 0, -2).')';
				
			}
			
			echo '</div>';
		}
	}
	
	
	// funkcija vrne seznam variabel za drag
	public function getVariableList() {
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
										
											// Napolnimo variable
											$options = $spremenljivka['options'];
								
											# imamo samo eno sekvenco grids[0]variables[0]
											$this->variablesList[$skey.'-'.$spremenljivka['grids'][0]['variables'][$vid]['sequence']] = array(
												'tip'=>$tip,
												'spr_id'=>$skey,
												'grd_id'=>'undefined',
												'sequence'=>$spremenljivka['grids'][0]['variables'][$vid]['sequence'],
												'naslov'=>strip_tags($spremenljivka['naslov']),
												'variable'=>$spremenljivka['variable'],
												'canChoose'=>true,
												'sub'=>0,
												/*'cnt'=>count($spremenljivka['options']),
												'options'=>$spremenljivka['options']);*/
												'options'=>$options);
										}
									}
								}
							}
						} else {
							
							// Napolnimo variable
							$options = array();
							foreach($spremenljivka['grids'][0]['variables'] as $key => $var){
								if(!$var['other'])
									$options[($key+1)] = $var['naslov'];
							}
							
							# imamo samo eno sekvenco grids[0]variables[0]
							$this->variablesList[$skey.'-'.$spremenljivka['grids'][0]['variables'][0]['sequence']] = array(
								'tip'=>$tip,
								'spr_id'=>$skey,
								'grd_id'=>'undefined',
								'sequence'=>$spremenljivka['grids'][0]['variables'][0]['sequence'],
								'naslov'=>strip_tags($spremenljivka['naslov']),
								'variable'=>$spremenljivka['variable'],
								'canChoose'=>true,
								'sub'=>0,
								/*'cnt'=>count($spremenljivka['grids'][0]));var_dump($spremenljivka['grids'][0]['variables']);*/
								'options'=>$options);
						}
					} else if ($cnt_all > 1){
						# imamo več skupin ali podskupin, zato zlopamo skozi gride in variable
						if (count($spremenljivka['grids']) > 0 ) {
							$this->variablesList[$skey] = array(
								'tip'=>$tip,							
								'naslov'=>strip_tags($spremenljivka['naslov']),
								'variable'=>$spremenljivka['variable'],
								'canChoose'=>false,
								'sub'=>0);
							# ali imamo en grid, ali več (ranking, vsota, text(vec kosov), number(vec kosov))
							if (count($spremenljivka['grids']) == 1 ) {
								# če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
								$grid = $spremenljivka['grids'][0];
								if (count ($grid['variables']) > 0) {
									foreach ($grid['variables'] AS $vid => $variable ){
										if ($variable['other'] != 1) {
											
											// Napolnimo variable
											$options = array();
											foreach($spremenljivka['grids'][0]['variables'] as $key => $var){
												if(!$var['other'])
													$options[($key+1)] = $var['naslov'];
											}
																					
											$this->variablesList[$skey.'-'.$variable['sequence']] = array(
												'tip'=>$tip,
												'spr_id'=>$skey,
												'grd_id'=>'undefined',
												'sequence'=>$variable['sequence'],
												'naslov'=>strip_tags($variable['naslov']),
												'variable'=>$variable['variable'],
												'canChoose'=>true,
												'sub'=>1,
												/*'cnt'=>$spremenljivka['cnt_all']);*/
												'options'=>$options);
										}
									}
								}

							} 
							# Imamo multicheckbox
							else if($tip == 16 || $tip == 18) {
								
								foreach($spremenljivka['grids'] AS $gid => $grid) {
									$sub = 0;
									if ($grid['variable'] != '') {
									
										// Napolnimo variable
										$options = array();
										foreach($spremenljivka['grids'][0]['variables'] as $key => $var){
											if(!$var['other'])
												$options[($key+1)] = $var['naslov'];
										}
									
										$sub++;
										$this->variablesList[$skey.'-'.$grid['variables'][0]['sequence']] = array(
											'tip'=>$tip,
											'spr_id'=>$skey,
											'grd_id'=>$gid,
											'sequence'=>$grid['variables'][0]['sequence'],
											'naslov'=>strip_tags($grid['naslov']),
											'variable'=>$grid['variable'],
											'canChoose'=>true,
											'sub'=>1,
											/*'cnt'=>count($grid['variables']));*/
											'options'=>$options);
									}
								}
							} 
							# imamo več gridov - multigrid, multitext, multinumber
							else {
								
								foreach($spremenljivka['grids'] AS $gid => $grid) {
									$sub = 0;
									if ($grid['variable'] != '') {
										$sub++;
										$this->variablesList[$skey] = array(
											'tip'=>$tip,
											'naslov'=>strip_tags($grid['naslov']),
											'variable'=>$grid['variable'],
											'canChoose'=>false,
											'sub'=>$sub);
									}
									if (count ($grid['variables']) > 0) {
										$sub++;
										foreach ($grid['variables'] AS $vid => $variable ){
											if ($variable['other'] != 1) {
												
												// Napolnimo variable
												$options = array();
												if($spremenljivka['tip'] == 6){													
													$options = $spremenljivka['options'];
												}
												else{
													foreach($spremenljivka['grids'][0]['variables'] as $key => $var){
														if(!$var['other'])
															$options[($key+1)] = $var['naslov'];
													}
												}

												$this->variablesList[$skey.'-'.$variable['sequence']] = array(
													'tip'=>$tip,
													'spr_id'=>$skey,
													'grd_id'=>'undefined',
													'sequence'=>$variable['sequence'],
													'naslov'=>strip_tags($variable['naslov']),
													'variable'=>$variable['variable'],
													'canChoose'=>true,
													'sub'=>$sub,
													/*'cnt'=>count($spremenljivka['options']));*/
													'options'=>$options);
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
	
	// funkcija vrne izbrane variable v arrayu
	public function getSelectedVars() {

		// Najprej napolnimo prvi nivo
		$sql = sisplet_query("SELECT * FROM srv_mc_element WHERE table_id='$this->table_id' AND parent='' ORDER BY vrstni_red");
		while($row = mysqli_fetch_array($sql)){
			
			$colSpan = 0;
			$rowSpan = 0;
			
			// Horizontalne spremenljivke
			if($row['position'] == '0'){
			
				$this->selectedVars['hor'][$row['vrstni_red']] = $row;
				
				$sql2 = sisplet_query("SELECT * FROM srv_mc_element WHERE table_id='$this->table_id' AND parent='$row[vrstni_red]' AND position='0'");
				// Ce ni 2.nivoja
				if(mysqli_num_rows($sql2) == 0){
					$rowSpan = count($this->variablesList[$row['spr']]['options']);					
					$this->selectedVars['hor'][$row['vrstni_red']]['span'] = $rowSpan;
					
					if($this->variablesList[$row['spr']]['tip'] == 2 || $this->variablesList[$row['spr']]['tip'] == 16)
						$this->isCheckbox = true;
				}
				// Napolnimo se 2.nivo
				else{
					while($row2 = mysqli_fetch_array($sql2)){
						$this->selectedVars['hor'][$row['vrstni_red']]['sub'][$row2['vrstni_red']] = $row2;
						
						$rowSpan += count($this->variablesList[$row['spr']]['options'])*count($this->variablesList[$row2['spr']]['options']);				
						$this->selectedVars['hor'][$row['vrstni_red']]['sub'][$row2['vrstni_red']]['span'] = count($this->variablesList[$row2['spr']]['options']);
						
						$this->rowLevel2 = true;
						
						if($this->variablesList[$row2['spr']]['tip'] == 2 || $this->variablesList[$row2['spr']]['tip'] == 16)
							$this->isCheckbox = true;
					}
					
					$this->selectedVars['hor'][$row['vrstni_red']]['span'] = $rowSpan;
				}

				$this->rowSpan += $rowSpan;
			}
			// Vertikalne spremenljivke
			else{
			
				$this->selectedVars['ver'][$row['vrstni_red']] = $row;
				
				$sql2 = sisplet_query("SELECT * FROM srv_mc_element WHERE table_id='$this->table_id' AND parent='$row[vrstni_red]' AND position='1'");
				// Ce ni 2.nivoja
				if(mysqli_num_rows($sql2) == 0){					
					$colSpan = count($this->variablesList[$row['spr']]['options']);
					$fullColSpan = $colSpan;
					$this->selectedVars['ver'][$row['vrstni_red']]['span'] = $colSpan;
					
					if($this->variablesList[$row['spr']]['tip'] == 2 || $this->variablesList[$row['spr']]['tip'] == 16)
						$this->isCheckbox = true;
						
					if($this->table_settings[$this->table_id]['sums'] == 1)
						$fullColSpan++;
				}
				// Napolnimo se 2.nivo
				else{
					while($row2 = mysqli_fetch_array($sql2)){						
						$this->selectedVars['ver'][$row['vrstni_red']]['sub'][$row2['vrstni_red']] = $row2;
						
						$colSpan += count($this->variablesList[$row['spr']]['options'])*count($this->variablesList[$row2['spr']]['options']);
						$fullColSpan += count($this->variablesList[$row['spr']]['options'])*count($this->variablesList[$row2['spr']]['options']);
						$this->selectedVars['ver'][$row['vrstni_red']]['sub'][$row2['vrstni_red']]['span'] = count($this->variablesList[$row2['spr']]['options']);
						
						$this->colLevel2 = true;
						
						if($this->variablesList[$row2['spr']]['tip'] == 2 || $this->variablesList[$row2['spr']]['tip'] == 16)
							$this->isCheckbox = true;
					}
					
					$this->selectedVars['ver'][$row['vrstni_red']]['span'] = $colSpan;
				}
				
				$this->colSpan += $colSpan;
				$this->fullColSpan += $fullColSpan;
			}
		}

		//echo 'Cols:'.$this->colSpan.'_Rows:'.$this->rowSpan.' ';
		//var_dump($this->selectedVars['hor']);
	}

	
	// Izvedemo izracune crosstabulacij
	public function createCrostabulation($variables) {
		global $site_path;
		
		$folder = $site_path . EXPORT_FOLDER.'/';
		$R_folder = $site_path . R_FOLDER.'/';
		
		if ($this->dataFileName != '' && file_exists($this->dataFileName)){		
		
			$spr = array();
			$grid = array();
			$sekvence = array();
			$var_options = array();
			$_all_options = array();
			foreach($variables as $key => $variable){
				
				$spr[$key] = $this->_HEADERS[$variables[$key]['spr']];
				$grid[$key] = $spr[$key]['grids'][$variables[$key]['grd']];
				$sekvenca =  $variables[$key]['seq'];
				
				$spr_checkbox = false;
				
								
				# za checkboxe gledamo samo odgovore ki so bili 1 in za vse opcije
				if ($spr[$key]['tip'] == 2 || $spr[$key]['tip'] == 16) {
					
					$spr_checkbox = true;
					
					if ($spr[$key]['tip'] == 2) {
						if (count($spr[$key]['grids'][0]['variables']) > 0)
						foreach ($spr[$key]['grids'][0]['variables'] AS $_vkey =>$_variable) {
							if ((int)$_variable['text'] != 1) {
								$sekvence[$key][] = $_variable['sequence'];
							}
						} else {
							$sekvence[$key] = explode('_',$spr[$key]['sequences']);
						}
					}
					if ($spr1['tip'] == 16) {

						foreach ($grid[$key]['variables'] AS $_variables) {
							
							$sekvence[$key][] = $_variables['sequence'];
						}					
					}
				} else {
					$sekvence[$key][] = $sekvenca;
				}

				
				# poiščemo pripadajočo spremenljivko
				$var_options[$key] = $this->_HEADERS[$variable['spr']]['options'];
					
				# najprej poiščemo (združimo) vse opcije ki so definirane kot opcije spremenljivke in vse ki so v crosstabih
				if (count($var_options[$key]) > 0 && $spr_checkbox !== true ) {
					foreach ($var_options[$key] as $okey => $opt) {
						$_all_options[$key][$okey] = array('naslov'=>$opt, 'cnt'=>null, 'type'=>'o');
					}
				}

				# za checkboxe dodamo posebej vse opcije
				if ($spr_checkbox == true ) {
					if ($spr[$key]['tip'] == 2 ) {
						$grid[$key] = $this->_HEADERS[$variable['spr']]['grids']['0'];
					}

					foreach ($grid[$key]['variables'] As $vkey => $var) {
						if ($var['other'] != 1) {
							$_all_options[$key][$var['sequence']] = array('naslov'=>$var['naslov'], 'cnt'=>null, 'type'=>'o', 'vr_id'=> $var['variable']);
						}
					}
				}							
			}
			
			
			// Nastavimo string s katerim filtriramo datoteko za prave stolpce
			foreach($sekvence as $sekvenca){
				if(count($sekvenca) > 1){
					foreach($sekvenca as $grd){
						$crosstabVars .= '$'.$grd.',';
					}
				}
				else
					$crosstabVars .= '$'.$sekvenca[0].',';
			}
			
			// Ce imamo racunanje povprecja
			$avgVar = 0;
			if($this->table_settings[$this->table_id]['avgVar'] != ''){			
				
				$avg = explode('-',$this->table_settings[$this->table_id]['avgVar']);
				$crosstabVars .= '$'.$avg[1].',';
				
				$avgVar = 1;
			}
			
			// Ce imamo racunanje deleza
			if($this->table_settings[$this->table_id]['delezVar'] != ''){
				
				$delezVar = explode('-',$this->table_settings[$this->table_id]['delezVar']);
				
				// Ce imamo delez za checkbox
				if($this->variablesList[$this->table_settings[$this->table_id]['delezVar']]['tip'] == 2 || $this->variablesList[$this->table_settings[$this->table_id]['delezVar']]['tip'] == 16){
					$delez = unserialize($this->table_settings[$this->table_id]['delez']);
					$i = 0;
					foreach($delez as $val){
						
						if($val == 1){
							$stolpec = (int)$delezVar[1] + $i;
							$crosstabVars .= '$'.$stolpec.',';
						}
						
						$i++;
					}
					$delez = -1;
				}
				else{
					$crosstabVars .= '$'.$delezVar[1].',';
					$delez = unserialize($this->table_settings[$this->table_id]['delez']);
				}
			}
			
			$crosstabVars = substr($crosstabVars, 0, -1);
			
			// Ce se nimamo datoteke s pripravljenimi podatki jo ustvarimo
			$tmp_file = $R_folder . '/TempData/crosstab_data.tmp';	
			if (!file_exists($tmp_file)) {
				$this->prepareDataFile($crosstabVars);
			}
			
			
			// Inicializiramo R in pozenemo skripto za crosstabulacije
			$R = new SurveyAnalysisR($this->ank_id);
			$crosstabs = $R->createMultiCrosstabulation($sekvence, $avgVar, $delez);							
			
			
			$crosstabs['options1'] = $_all_options[0];
			$crosstabs['options2'] = $_all_options[1];
			if(isset($_all_options[2]))
				$crosstabs['options3'] = $_all_options[2];
			if(isset($_all_options[3]))
				$crosstabs['options4'] = $_all_options[3];
				
			$crosstabs['isCheckbox'] = $this->isCheckbox;
			
			
			// Testiranje...
			/*echo '<div style="width: 800px; position: absolute;top:0;left:0; background-color: #eeffff;">';		
			var_dump($crosstabs['sumaVrstica']);
			var_dump($crosstabs['sumaStolpec']);
			var_dump($crosstabs['sumaSkupna']);
			echo '</div>';*/
			
			
			// Na koncu pobrisemo zacasen file s podatki
			$this->deleteDataFile();
			
			
			return $crosstabs;
		}
	}
	

	// Pripravimo file iz katerega preberemo podatke in izvedemo crosstabulacije
	public function prepareDataFile($cols){
		global $site_path;
		
		$folder = $site_path . EXPORT_FOLDER.'/';
		$R_folder = $site_path . R_FOLDER.'/';
		
		# pogoji so že dodani v _CURRENT_STATUS_FILTER
		$status_filter = $this->_CURRENT_STATUS_FILTER;
			
		# dodamo status filter za vse sekvence checkbox-a da so == 1
		if ($additional_status_filter != null) {
			$status_filter .= $additional_status_filter;
		}

		# odstranimo vse zapise, kjer katerakoli od variabel vsebuje missing
		$_allMissing_answers =  SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
		$_pageMissing_answers = $this->getInvalidAnswers(MISSING_TYPE_CROSSTAB);
			

		// File kamor zapisemo filtrirane podatke
		$tmp_file = $R_folder . '/TempData/crosstab_data.tmp';		
		
		# polovimo obe sekvenci
		/*if (count($sekvences1)>0)
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
					$command = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} '.$status_filter.$additional_filter.' { print $0 }" '.$this->dataFileName.' >> '.$tmp_file;
				} else {
					#$command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$_status_filter.' { print "$crosstab[\x27",$'.$sequence1.',"\x27][\x27",$'.$sequence2.',"\x27]++; $options1[\x27",$'.$sequence1.',"\x27]++; $options2[\x27",$'.$sequence2.',"\x27]++;"}\' '.$this->dataFileName.' >> '.$tmp_file;
					$command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} '.$status_filter.$additional_filter.' { print $0 }\' '.$this->dataFileName.' >> '.$tmp_file;
				}

				$out = shell_exec($command);
			}

		}*/

		
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
	public function deleteDataFile(){
		global $site_path;
		
		$R_folder = $site_path . R_FOLDER.'/';
		$tmp_file = $R_folder . '/TempData/crosstab_data.tmp';	
		
		// Na koncu pobrisemo zacasen file s podatki
		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
	}
	
	
	// Prikaze izbiro med vsemi tabelami
	function displayMCTables(){
		global $site_path;
		global $global_user_id;
		global $lang;

		// Trenutna aktivna tabela
		$sql = sisplet_query("SELECT * FROM srv_mc_table WHERE id='$this->table_id' AND ank_id='$this->ank_id' AND usr_id='$global_user_id'");	
		$current_table = mysqli_fetch_array($sql);
		
		echo '<h2>'.$lang['srv_multicrosstabs_tables'].'</h2>';		
				
				
		echo '<div id="mc_tables_left">';	
		
		// Prednastavljen profil
       	echo '<span id="mc_tables" class="mc_tables select">';
		
		$mc_tables = $this->getTables();
		foreach($mc_tables as $table){
			echo '<div class="option'.($this->table_id == $table['id'] ? ' active' : '').'" id="mc_table_'.$table['id'].'" value="'.$table['id'].'">'.$table['name'].'</div>';	
		}
		
		echo '</span>';
				

		// Na dnu imamo gumba brisi in preimenuj
		echo '<div style="float:left;">';
		echo '<a href="#" onclick="mc_table_action(\'show_rename\'); return false;">'.$lang['srv_multicrosstabs_tables_rename'].'</a><br/>'."\n";
		echo '<a href="#" onclick="mc_table_action(\'show_delete\'); return false;">'.$lang['srv_multicrosstabs_tables_delete'].'</a>'."\n";
		echo '</div>';

		
		// Cas kreirranja tabele
		echo '<div style="float:right; text-align:right;">';
		$time_created = strtotime($current_table['time_created']);
		echo $lang['srv_multicrosstabs_tables_time'].': <span class="bold">'.date("d.m.Y H:i", $time_created).'</span><br />';
		echo '</div>';
			
		echo '</div>';
			
		
		// cover Div
        echo '<div id="dsp_cover_div"></div>'."\n";	
		
		echo '<span class="clr"></span>';

		echo '<div style="position:absolute; bottom:15px; right:15px;">';
		
		//echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="save_creport_profile(); return false;"><span>'.$lang['save'].'</span></a></div></span>';	
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="use_mc_table(); return false;"><span>'.$lang['srv_multicrosstabs_tables_use'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight" title="'.$lang['srv_multicrosstabs_tables_add'].'"><div class="buttonwrapper"><a class="ovalbutton" href="#" onclick="mc_table_action(\'show_new\'); return false;"><span>'.$lang['srv_multicrosstabs_tables_add'] . '</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton" href="#" onclick="close_mc_tables(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
	
		echo '</div>';
	}
	
	function displayMCTablesPopups(){
		global $lang;
		
		// div za kreacijo novega
        echo '<div id="newMCTable">';

        echo '<h2>'.$lang['srv_new_table'].'</h2>';
        
		echo '<div style="float:left; width:400px; text-align:right;">'.$lang['srv_multicrosstabs_tables_name'].': '."\n";
        echo '<input id="newMCTableName" name="newMCTableName" type="text" value="" size="50"  /></div>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="mc_table_action(\'new\'); return false;"><span>'.$lang['save'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="mc_table_action(\'cancel_new\'); return false;"><span>'.$lang['srv_zapri'].'</span></a></span></span>'."\n";
   		echo '<div class="floatRight clr" style="padding: 15px 5px 15px 0;"><a href="#" onClick="mc_table_action(\'goto_archive\');">'.$lang['srv_analiza_arhiv'].'</a></div>';
		echo '</div>'."\n";
		
		// div za preimenovanje
        echo '<div id="renameMCTable">'.$lang['srv_multicrosstabs_tables_name'].': '."\n";
        echo '<input id="renameMCTableName" name="renameMCTableName" type="text" size="45" />'."\n";
        echo '<input id="renameMCTableId" type="hidden" value="' . $this->table_id . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="mc_table_action(\'rename\'); return false;"><span>'.$lang['srv_multicrosstabs_tables_rename_short'].'</span></a></span></span>'."\n";            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="mc_table_action(\'cancel_rename\'); return false;"><span>'.$lang['srv_zapri'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
		
		// div za brisanje
        echo '<div id="deleteMCTable">'.$lang['srv_multicrosstabs_tables_delete_confirm'].': <span id="deleteMCTableName" style="font-weight:bold;"></span>?'."\n";
        echo '<input id="deleteMCTableId" type="hidden" value="' . $this->table_id . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="mc_table_action(\'delete\'); return false;"><span>'.$lang['srv_multicrosstabs_tables_delete_short'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="mc_table_action(\'cancel_delete\'); return false;"><span>'.$lang['srv_zapri'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
	}
	
	// Vrnemo vse tabele uporabnika
	public function getTables(){
		global $global_user_id;
		
		$mc_tables = array();
		
		$sql = sisplet_query("SELECT * FROM srv_mc_table WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id' ORDER BY time_created ASC");
		while($row = mysqli_fetch_array($sql)){
			$mc_tables[] = $row;
		}

		return $mc_tables;
	}
	

	function ajax(){
		global $lang;
		global $global_user_id;

		$this->getVariableList();
		
		if (isset ($_POST['anketa']))
			$this->ank_id = $_POST['anketa'];
		
		if (isset($_POST['table_id'])) 
			$this->table_id = $_POST['table_id'];	


		if ($_GET['a'] == 'add_variable') {
			
			if (isset($_POST['spr'])) $spr = $_POST['spr'];
			if (isset($_POST['parent'])) $parent = $_POST['parent'];			
			if (isset($_POST['position'])) $position = $_POST['position'];
			
			// Dobimo vrstni red
			$sql = sisplet_query("SELECT COUNT(id) AS cnt FROM srv_mc_element WHERE table_id='$this->table_id' AND parent='$parent' AND position='$position'");
			$row = mysqli_fetch_array($sql);
			$vrstni_red = $row['cnt'];
			
			sisplet_query("INSERT INTO srv_mc_element (table_id, spr, parent, vrstni_red, position) VALUES('$this->table_id', '$spr', '$parent', '$vrstni_red', '$position')");
		
			$this->displayTable();
		}
		
		if ($_GET['a'] == 'remove_variable') {
						
			if (isset($_POST['vrstni_red'])) $vrstni_red = $_POST['vrstni_red'];	
			if (isset($_POST['position'])) $position = $_POST['position'];
			if (isset($_POST['parent'])) $parent = $_POST['parent'];
			
			// Ce brisemo element na 1. nivoju
			if($parent == 'undefined'){
				// Pobrisemo element
				sisplet_query("DELETE FROM srv_mc_element WHERE table_id='$this->table_id' AND position='$position' AND vrstni_red='$vrstni_red' AND parent=''");
				
				// Pobrisemo se vse childe
				sisplet_query("DELETE FROM srv_mc_element WHERE table_id='$this->table_id' AND position='$position' AND parent='$vrstni_red'");
				
				// Popravimo vrstni red ostalih
				sisplet_query("UPDATE srv_mc_element SET vrstni_red=vrstni_red-1 WHERE table_id='$this->table_id' AND position='$position' AND vrstni_red>'$vrstni_red' AND parent=''");
			
				// Popravimo parente pri childih
				sisplet_query("UPDATE srv_mc_element SET parent=parent-1 WHERE table_id='$this->table_id' AND position='$position' AND parent>'$vrstni_red' AND parent!=''");
			}
			// Brisemo element na 2. nivoju
			else{
				// Pobrisemo element
				sisplet_query("DELETE FROM srv_mc_element WHERE table_id='$this->table_id' AND position='$position' AND vrstni_red='$vrstni_red' AND parent='$parent'");
				
				// Popravimo vrstni red ostalih childov
				sisplet_query("UPDATE srv_mc_element SET vrstni_red=vrstni_red-1 WHERE table_id='$this->table_id' AND position='$position' AND vrstni_red>'$vrstni_red' AND parent='$parent'");
			}
			
		
			$this->displayTable();
		}

		if ($_GET['a'] == 'change_settings') {
			
			if (isset($_POST['what'])) $what = $_POST['what'];	
			if (isset($_POST['value'])) $value = $_POST['value'];
			
			$this->table_settings[$this->table_id][$what] = $value;
			sisplet_query("UPDATE srv_mc_table SET $what='$value' WHERE table_id='$this->table_id'");

			
			$this->displayTable();
		}
		
		if ($_GET['a'] == 'edit_title') {

			$value = isset($_POST['value']) ? $_POST['value'] : '';

			sisplet_query("UPDATE srv_mc_table SET title='$value' WHERE id='$this->table_id'");
		}
				
		if ($_GET['a'] == 'save_settings') {
			
			$this->table_settings[$this->table_id]['numerus'] = (isset($_POST['numerus'])) ? $_POST['numerus'] : 0;
			$this->table_settings[$this->table_id]['percent'] = (isset($_POST['percent'])) ? $_POST['percent'] : 0;
			$this->table_settings[$this->table_id]['sums'] = (isset($_POST['sums'])) ? $_POST['sums'] : 0;
			
			$this->table_settings[$this->table_id]['navVsEno'] = (isset($_POST['navVsEno'])) ? $_POST['navVsEno'] : 0;
			
			$this->table_settings[$this->table_id]['avgVar'] = (isset($_POST['avgVar']) && isset($_POST['avgSetting'])) ? $_POST['avgVar'] : '';
			
			$this->table_settings[$this->table_id]['delezVar'] = (isset($_POST['delezVar']) && isset($_POST['delezSetting'])) ? $_POST['delezVar'] : '';
			// Ce imamo nastavljeno variablo za delez loopamo cez njene opcije in pogledamo katere so checkane
			if($this->table_settings[$this->table_id]['delezVar'] != ''){		
				$delez = array();		
				$cnt = 0;
				foreach($this->variablesList[$this->table_settings[$this->table_id]['delezVar']]['options'] as $option){				
					$val = (isset($_POST['delez_'.$cnt])) ? $_POST['delez_'.$cnt] : 0;
					$delez[$cnt] = $val;					
					$cnt++;
				}				
				$this->table_settings[$this->table_id]['delez'] = serialize($delez);
			}
			else{
				$this->table_settings[$this->table_id]['delez'] = '';
			}
			
			
			$sql = sisplet_query("UPDATE srv_mc_table SET 
				numerus='".$this->table_settings[$this->table_id]['numerus']."', 
				percent='".$this->table_settings[$this->table_id]['percent']."', 
				sums='".$this->table_settings[$this->table_id]['sums']."', 
				navVsEno='".$this->table_settings[$this->table_id]['navVsEno']."', 
				avgVar='".$this->table_settings[$this->table_id]['avgVar']."', 
				delezVar='".$this->table_settings[$this->table_id]['delezVar']."', 
				delez='".$this->table_settings[$this->table_id]['delez']."' 
				WHERE id='".$this->table_id."'");
			if(!$sql) echo mysqli_error($GLOBALS['connect_db']);
			$this->displayTable();
		}
		
		if ($_GET['a'] == 'set_delez') {
			
			//$this->table_settings[$this->table_id]['delezVar'] = (isset($_POST['delezVar'])) ? $_POST['delezVar'] : '';
			$delezVar = (isset($_POST['delezVar'])) ? $_POST['delezVar'] : '';
			
			$this->displayDelez($delezVar);
		}
		
		if ($_GET['a'] == 'mc_show_tables'){
			
			$this->displayMCTables();
		}
		
		if ($_GET['a'] == 'use_mc_table'){
			
			$value = isset($_POST['value']) ? $_POST['value'] : $this->table_id;			
			SurveyUserSetting :: getInstance()->saveSettings('default_mc_table', $value);
		}
		
		if ($_GET['a'] == 'rename_table'){
			
			$id = isset($_POST['id']) ? $_POST['id'] : '';
			$name = isset($_POST['name']) ? $_POST['name'] : '';
			sisplet_query("UPDATE srv_mc_table SET name='$name' WHERE id='$id' AND ank_id='$this->ank_id' AND usr_id='$global_user_id'");
			
			$this->displayMCTables();
		}
		
		if ($_GET['a'] == 'delete_table'){
			
			$id = isset($_POST['id']) ? $_POST['id'] : '';
			sisplet_query("DELETE FROM srv_mc_table WHERE id='$id' AND ank_id='$this->ank_id' AND usr_id='$global_user_id'");
			
			// Preklopimo na prvo tabelo
			$sql = sisplet_query("SELECT id FROM srv_mc_table WHERE ank_id='$this->ank_id' AND usr_id='$global_user_id' ORDER BY time_created ASC");
			$row = mysqli_fetch_array($sql);

			$this->table_id = $row['id'];
			SurveyUserSetting :: getInstance()->saveSettings('default_mc_table', $row['id']);
			
			$this->displayMCTables();
		}
		
		if ($_GET['a'] == 'new_table'){
		
			$name = isset($_POST['name']) ? $_POST['name'] : '';		
			sisplet_query("INSERT INTO srv_mc_table (ank_id, usr_id, time_created, name) VALUES('$this->ank_id', '$global_user_id', NOW(), '$name')");
			$table_id = mysqli_insert_id($GLOBALS['connect_db']);
			
			$this->table_id = $table_id;
			SurveyUserSetting :: getInstance()->saveSettings('default_mc_table', $table_id);
		}
		
		if ($_GET['a'] == 'mc_change_table'){
			
			$id = isset($_POST['id']) ? $_POST['id'] : $this->table_id;			
			$this->table_id = $id;
			
			$this->displayMCTables();
		}	
	}

	
	// Skrajsa tekst in doda '...' na koncu
	function snippet($text, $length=64, $tail="..."){
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length)
		{
			for($i=1;$text[$length-$i]!=" ";$i++)
			{
				if($i == $length)
				{
					return substr($text,0,$length) . $tail;
				}
			}
			$text = substr($text,0,$length-$i+1) . $tail;
		}
		return strip_tags($text);
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
	
	
}

?>