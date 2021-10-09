<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.xls.php');
		

class XlsIzvozAnalizaMultiCrosstab {

	var $anketa;						// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	
	public $multiCrosstabClass = null;		//crosstab class

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null){
		global $site_path;
		global $global_user_id;
		global $output;	
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			
			// create new XLS document
			$this->xls = new xls();
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		$_GET['a'] = A_ANALYSIS;
		
		//ustvarimo multicrosstabs objekt		
		$this->multiCrosstabClass = new SurveyMultiCrosstabs($anketa);
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
			$this->anketa['uid'] = $global_user_id;
			
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
			SurveyDataSettingProfiles::Init($this->anketa['id']);
		}
		else
			return false;
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	// SETTERS && GETTERS

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}
	
	function getFile($fileName)
	{
		$output = $this->createXls();
		$this->xls->display($fileName, $output);
	}


	function init()
	{
		return true;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function createXls(){
		global $site_path;
		global $lang;
		global $output;
		
		$convertTypes = array('charSet'	=> "windows-1250",
						 'delimit'	=> ";",
						 'newLine'	=> "\n",
						 'BOMchar'	=> "\xEF\xBB\xBF");
		
		$output = $convertTypes['BOMchar'];

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_multicrosstabs'].'</b></font></td></tr></table>';
		$output .= '<table border="0"><tr><td colspan="10"></td></tr></table>';
		
		// Napolnimo variable s katerimi lahko operiramo
		$this->multiCrosstabClass->getVariableList();
		
		// Izris tabele
		$this->displayTable();
		
		return $output;
	}	

	
	public function displayTable(){
		global $site_path;
		global $lang;
		global $output;
		
		// Napolnimo variable ki so ze izbrane
		$this->multiCrosstabClass->getSelectedVars();


		// Izpisemo naslov tabele
		$output .= '<table border="0"><tr><td colspan="10">';
		$output .= '<b>'.$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['title'].'</b>';
		$output .= '</td></tr></table>';
		
		
		$output .= '<table cellspacing="0" cellpadding="0" border="1">';

		
		// Imamo 2 nivoja
		if($this->multiCrosstabClass->colLevel2){
			
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			if($this->multiCrosstabClass->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->multiCrosstabClass->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			$output .= '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					$rowspan = count($var['sub']) > 0 ? '':' rowspan="2"';
					$colspan = ' colspan="'.$var['span'].'"';
					$output .= '<td align="center" '.$rowspan . $colspan.'>';
					
					$output .= $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
					
					$output .= '</td>';
				}
			}
			$output .= '</tr>';
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if($this->multiCrosstabClass->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->multiCrosstabClass->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			$output .= '<tr><td class="borderless" '.$colspan.'></td>';	
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){

					if(count($var['sub']) > 0){
						// Loop cez variable spremenljivke
						foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){

							$colspan = ' colspan="'.( $var['span'] / count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) ).'"';
							$output .= '<td align="center" '.$colspan.'>';
							
							$output .= $this->snippet($option, 25);			
							
							$output .= '</td>';
						}
					}
				}
			}
			$output .= '</tr>';
			
			// Izris vrstic za 2. nivo - 3. in 4. vrstica
		
			if($this->multiCrosstabClass->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->multiCrosstabClass->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			$output .= '<tr><td '.$colspan.'></td>';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					
					foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							foreach($parentVar['sub'] as $var){				

								$colspan = ' colspan="'.( count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) ).'"';
								$output .= '<td align="center" '.$colspan.'>';
								
								$output .= $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
								
								$output .= '</td>';
							}
						}
						else{
							$rowspan = ' rowspan="2"';
							$colspan = ' colspan="'.( $parentVar['span'] / count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) ).'"';
							$output .= '<td align="center" '.$rowspan . $colspan.'>';
							
							$output .= $this->snippet($option, 25);			
							
							$output .= '</td>';
						}
					}
				}
			}
			$output .= '</tr>';
			
			if($this->multiCrosstabClass->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->multiCrosstabClass->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			$output .= '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					
					foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							foreach($parentVar['sub'] as $var){				
								
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
									$output .= '<td align="center">';
									
									$output .= $this->snippet($suboption, 25);
									
									$output .= '</td>';
								}
							}
						}
					}
				}
			}
			$output .= '</tr>';
		}
		// Imamo samo 1 nivo
		else{
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			if($this->multiCrosstabClass->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->multiCrosstabClass->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			$output .= '<tr><td class="borderless" '.$colspan.'></td>';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					$colspan = ' colspan="'.($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2 ? $var['span']+1 : $var['span']).'"';
					$output .= '<td align="center" '.$colspan.'>';
					
					$output .= $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
					
					$output .= '</td>';
				}
			}
			// Nimamo nobene vertikalne spremenljivke in 2 horizontalni
			elseif($this->multiCrosstabClass->rowLevel2){
				$output .= '<td class="borderless"></td>';
			}

			// Izrisemo se zadnjo prazno navpicno celico vrstico	
			$output .= '</tr>';
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if($this->multiCrosstabClass->rowSpan == 0)
				$colspan = ' colspan="1"';
			elseif(!$this->multiCrosstabClass->rowLevel2)
				$colspan = ' colspan="2"';
			else
				$colspan = ' colspan="4"';			
			$output .= '<tr><td class="borderless" '.$colspan.'></td>';	
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){

					// Loop cez variable spremenljivke
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){

						$colspan = ' colspan="'.( $var['span'] / count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) ).'"';
						$output .= '<td align="center" '.$colspan.'>';
						
						$output .= $this->snippet($option, 25);			
						
						$output .= '</td>';
					}
					
					// Suma (ce jo imamo vklopljeno)
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2){						
						$output .= '<td align="center">';
						$output .= $lang['srv_analiza_crosstab_skupaj'];
						$output .= '</td>';
					}
				}
			}
			$output .= '</tr>';
		}

		
		
		// Izrisemo HORIZONTALNO izbrane variable
		if(count($this->multiCrosstabClass->selectedVars['hor'])){
		
			// Imamo 2 nivoja vrstic
			if($this->multiCrosstabClass->rowLevel2){
				foreach($this->multiCrosstabClass->selectedVars['hor'] as $parentVar){
					
					$cnt = 0;
					$order0 = 0;
					
					foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
					
						$cnt2 = 0;
					
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							foreach($parentVar['sub'] as $var){
							
								$cnt3 = 0;
						
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
															
									$output .= '<tr>';
									
									if($cnt == 0){
										$span = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) == 0 ? $parentVar['span']+(count($parentVar['sub'])*count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'])) : $parentVar['span'];
										$rowspan = ' rowspan="'.$span.'"';
										$output .= '<td align="center" '.$rowspan.'>';				
										
										$output .= $this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25);
										
										$output .= '</td>';
									}
									
									// Variabla
									if($cnt2 == 0){
										$span = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) == 0 ? $parentVar['span'] / count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) + count($parentVar['sub']) : $parentVar['span'] / count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']);
										$rowspan = ' rowspan="'.$span.'"';
										$output .= '<td align="center" '.$rowspan.'>';												
										$output .= $this->snippet($option, 25);			
										$output .= '</td>';
									}
									
									if($cnt3 == 0){
										$span = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) == 0 ? count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) + 1 : count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
										$rowspan = ' rowspan="'.$span.'"';
										$output .= '<td align="center" '.$rowspan.'>';				
										
										$output .= $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
										
										$output .= '</td>';
									}
									
									// Variabla 2
									$output .= '<td align="center">';												
									$output .= $this->snippet($suboption, 25);			
									$output .= '</td>';
									
									// Celice s podatki
									$this->displayDataCells($parentVar, $order0, $var, $cnt3);
									
									$output .= '</tr>';
										
									$cnt++;	
									$cnt2++;
									$cnt3++;
								}
								
								$order0++;
								
								// Izrisemo se sumo ce je vklopljena
								if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) == 0){
									
									$output .= '<tr>';
									
									$output .= '<td align="center">'.$lang['srv_analiza_crosstab_skupaj'].'</td>';
									
									$crosstabs = $this->multiCrosstabClass->crosstabData[$parentVar['spr'].'-'.$var['spr']];
								
									$keys1 = array_keys($crosstabs['options2']);
									$key = ceil($cnt / (count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])*count($parentVar['sub']))) - 1;
									$val = $keys1[$key];
								
									$this->displaySumsCell($parentVar, $var, $val, $orientation=0);
									
									$output .= '</tr>';
								}
							}
						}
						else{
							$output .= '<tr>';
							
							if($cnt == 0){
								$rowspan = ' rowspan="'.$parentVar['span'].'"';
								$output .= '<td align="center" '.$rowspan.' colspan="2">';				
								
								$output .= $this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25);
											
								$output .= '</td>';
							}
								
							// Variabla
							$rowspan = ' rowspan="'.( $parentVar['span'] / count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) ).'"';
							$output .= '<td align="center" '.$rowspan.' colspan="2">';												
							$output .= $this->snippet($option, 25);			
							$output .= '</td>';
								
							
							// Celice s podatki
							$this->displayDataCells($parentVar, $cnt);
							
							$output .= '</tr>';
							
							$cnt++;
						}
					}
				}
			}
			// Imamo samo 1 nivo vrstic
			else{
				foreach($this->multiCrosstabClass->selectedVars['hor'] as $var){
					
					$cnt = 0;
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
						$output .= '<tr>';
						
						if($cnt == 0){
							$rowspan = ' rowspan="'.($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) > 0 ? $var['span']+1 : $var['span']).'"';
							$output .= '<td align="center" '.$rowspan.'>';				
									
							$output .= $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
										
							$output .= '</td>';
						}
						
						// Variabla
						$output .= '<td align="center">';												
						$output .= $this->snippet($option, 25);			
						$output .= '</td>';
						
						// Celice s podatki
						$this->displayDataCells($var, $cnt);

						
						$output .= '</tr>';
						
						$cnt++;
					}
					
					// Vrstica za sumo (ce jo imamo vklopljeno)
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) > 0 && !$this->multiCrosstabClass->colLevel2){
						$output .= '<tr>';
						$output .= '<td align="center">'.$lang['srv_analiza_crosstab_skupaj'].'</td>';
						
						// Loop cez vse stolpce
						foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){
						
							// Loop cez variable trenutnega stolpca
							$cnt = 0;
							foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){
								
								$crosstabs = $this->multiCrosstabClass->crosstabData[$var['spr'].'-'.$spr2['spr']];
								
								$keys1 = array_keys($crosstabs['options1']);
								$val = $keys1[$cnt];
								
								$this->displaySumsCell($var, $spr2, $val, $orientation=1);
									
								$cnt++;
							}
							
							// Krizanje navpicne in vodoravne sume
							$this->displaySumsCell($var, $spr2, 0, $orientation=2);
						}
						
						$output .= '</tr>';
					}
				}
			}
		}
		
		
		$output .= '</table>';
	}
	
	// Izpis celic v vrstici s podatki
	function displayDataCells($spr1, $var1, $spr2='', $var2=''){
		global $output;

		// Ce nimamo nobenega krizanja izpisemo prazne
		if($spr2 == '' && $this->multiCrosstabClass->colSpan == 0){
			for($i=0; $i<$this->multiCrosstabClass->colSpan; $i++){
				$output .= '<td class="data"></td>';
			}
		}
	
		// Ce nimamo stolpcev - krizanje dveh vrstic
		elseif($spr2 != '' && $this->multiCrosstabClass->colSpan == 0){

			$spr1_temp = explode('-', $spr1['spr']);
			$grd = $this->multiCrosstabClass->variablesList[$spr1['spr']]['grd_id'];
			$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
			
			$spr2_temp = explode('-', $spr2['spr']);
			$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
			$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
			
			// Ce se nimamo izracunanih rezultatov jih izracunamo
			if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
				$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
			else{							
				$variables = array();
				$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
				$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
					
				$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);

				$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
			}

			$keys1 = array_keys($crosstabs['options1']);
			$val1 = $keys1[$var1];
			
			$keys2 = array_keys($crosstabs['options2']);
			$val2 = $keys2[$var2];

			$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
			$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
			$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
			$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
			
			$this->displayDataCell($crosstab, $percent, $avg, $delez);
		}
		
		// Krizanje 1 vrstice in 1 stolpca
		elseif($spr2 == '' && !$this->multiCrosstabClass->colLevel2){
		
			// Loop cez vse stolpce
			foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){

				$spr1_temp = explode('-', $spr1['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr1['spr']]['grd_id'];
				$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
				
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
				
				
				// Ce se nimamo izracunanih rezultatov jih izracunamo
				if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
					$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
				else{
					$variables = array();
					$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
					$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
					
					$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

					$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
				}

				$keys1 = array_keys($crosstabs['options1']);
				$val1 = $keys1[$var1];

				// Loop cez variable trenutnega stolpca
				$cnt = 0;
				foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){
				
					$keys2 = array_keys($crosstabs['options2']);
					$val2 = $keys2[$cnt];
		
					$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
					$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
					$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
					$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
					
					$this->displayDataCell($crosstab, $percent, $avg, $delez);
						
					$cnt++;
				}
				
				// Suma (ce jo imamo vklopljeno)
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1){						
					$this->displaySumsCell($spr1, $spr2, $val1, $orientation=0);
				}
			}
		}
		
		// Izpisemo vecnivojske podatke (krizanje 3 ali 4 spremenljivk)
		else{	
		
			// Nastavimo 1. vrsticno variablo
			$spr1_temp = explode('-', $spr1['spr']);
			$grd = $this->multiCrosstabClass->variablesList[$spr1['spr']]['grd_id'];
			$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
				
			// Krizanje 2 vrstic in 1 stolpca
			if(!$this->multiCrosstabClass->colLevel2){
			
				// Nastavimo 2. vrsticno variablo
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);			
			
				// Loop cez vse stolpce
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr3){
								
					$spr3_temp = explode('-', $spr3['spr']);
					$grd = $this->multiCrosstabClass->variablesList[$spr3['spr']]['grd_id'];
					$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);			
					
					// Ce se nimamo izracunanih rezultatov jih izracunamo
					if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
						$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
					else{
						$variables = array();
						$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
						$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
						$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
						
						$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

						$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
					}

					$keys1 = array_keys($crosstabs['options1']);
					$val1 = $keys1[$var1];
					
					$keys2 = array_keys($crosstabs['options2']);
					$val2 = $keys2[$var2];

					// Loop cez variable trenutnega stolpca
					$cnt = 0;
					foreach($this->multiCrosstabClass->variablesList[$spr3['spr']]['options'] as $var3){
					
						$keys3 = array_keys($crosstabs['options3']);
						$val3 = $keys3[$cnt];

						$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
						$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
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
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){
								
					$spr2_temp = explode('-', $spr2['spr']);
					$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
					$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);							

					// Loop cez variable 1. navpicne spremnljivke
					$cnt2 = 0;
					foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){	

						// Loop cez vse navpicne spremenljivke 2. nivoja - ce obstajajo							
						if(count($spr2['sub']) > 0){
							foreach($spr2['sub'] as $spr3){
								
								// Nastavimo navpicno spremenljivko 2. nivoja	
								$spr3_temp = explode('-', $spr3['spr']);
								$grd = $this->multiCrosstabClass->variablesList[$spr3['spr']]['grd_id'];
								$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);
								
								// Ce se nimamo izracunanih rezultatov jih izracunamo
								if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
								else{
									$variables = array();
									$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
									$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
									$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
									
									$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
								}

								$keys1 = array_keys($crosstabs['options1']);
								$val1 = $keys1[$var1];
								
								$keys2 = array_keys($crosstabs['options2']);
								$val2 = $keys2[$cnt2];

								// Loop cez variable spremenljivke 2. nivoja
								$cnt3 = 0;
								foreach($this->multiCrosstabClass->variablesList[$spr3['spr']]['options'] as $var3){
									
									$keys3 = array_keys($crosstabs['options3']);
									$val3 = $keys3[$cnt3];

									$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
									$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
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
							if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
							else{
								$variables = array();
								$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
								$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
								
								$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
							}

							$keys1 = array_keys($crosstabs['options1']);
							$val1 = $keys1[$var1];
							
							$keys2 = array_keys($crosstabs['options2']);
							$val2 = $keys2[$cnt2];

							$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
							$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
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
				$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
			
				// Loop cez vse stolpce 1. navpicne spremenljivke
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr3){
								
					$spr3_temp = explode('-', $spr3['spr']);
					$grd = $this->multiCrosstabClass->variablesList[$spr3['spr']]['grd_id'];
					$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);							

					// Loop cez variable 1. navpicne spremnljivke
					$cnt3 = 0;
					foreach($this->multiCrosstabClass->variablesList[$spr3['spr']]['options'] as $var3){	

						// Loop cez vse navpicne spremenljivke 2. nivoja								
						if(count($spr3['sub']) > 0){
							foreach($spr3['sub'] as $spr4){
								
								// Nastavimo navpicno spremenljivko 2. nivoja	
								$spr4_temp = explode('-', $spr4['spr']);
								$grd = $this->multiCrosstabClass->variablesList[$spr4['spr']]['grd_id'];
								$variabla4 = array('seq' => $spr4_temp[1], 'spr' => $spr4_temp[0], 'grd' => $grd);
								
								// Ce se nimamo izracunanih rezultatov jih izracunamo
								if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']]))
									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']];
								else{
									$variables = array();
									$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
									$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
									$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
									$variables[3] = array('seq' => $variabla4['seq'], 'spr' => $variabla4['spr'], 'grd' => $variabla4['grd']);
									
									$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']];
								}

								$keys1 = array_keys($crosstabs['options1']);
								$val1 = $keys1[$var1];
								
								$keys2 = array_keys($crosstabs['options2']);
								$val2 = $keys2[$var2];
								
								$keys3 = array_keys($crosstabs['options3']);
								$val3 = $keys3[$cnt3];

								// Loop cez variable spremenljivke 2. nivoja
								$cnt4 = 0;
								foreach($this->multiCrosstabClass->variablesList[$spr4['spr']]['options'] as $var4){
									
									$keys4 = array_keys($crosstabs['options4']);
									$val4 = $keys4[$cnt4];

									$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3][$val4])) ? $crosstabs['crosstab'][$val1][$val2][$val3][$val4] : 0;
									$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
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
							if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
							else{
								$variables = array();
								$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
								$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
								$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
								
								$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
							}

							$keys1 = array_keys($crosstabs['options1']);
							$val1 = $keys1[$var1];
							
							$keys2 = array_keys($crosstabs['options2']);
							$val2 = $keys2[$var2];
							
							$keys3 = array_keys($crosstabs['options3']);
							$val3 = $keys3[$cnt3];

							$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
							$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
							$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
							$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
						
							$this->displayDataCell($crosstab, $percent, $avg, $delez);
						}
						
						$cnt3++;
					}
				}	
			}
		}
	}
	
	// Izpis celic v vrstici s sumami ($orientation 0->vrstica, 1->stolpec, 2->skupaj)
	function displaySumsCell($spr1, $spr2, $val, $orientation){
		global $output;
		
		$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];

		$output .= '<td>';			
		$output .= '<table>';	
		
		// Celica s skupno sumo
		if($orientation == 2){		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$output .= '<tr><td align="center">';
				$output .= '<b>'.$crosstabs['sumaSkupna'].'</b>';
				$output .= '</td></tr>';
			}
			
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$output .= '<tr><td align="center">';
				$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$output .= '</td></tr>';
			}
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
				
				// Loop cez vse in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab']){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'] as $key1 => $row){	
						foreach($row as $key2 => $count){
							$tempAvg += $count * $crosstabs['avg'][$key1][$key2];
						}
					}
					$avg = $tempAvg / $crosstabs['sumaSkupna'];
				}
				
				$output .= '<tr><td align="center">';
				$output .= '<font color="blue">'.$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).'</font>';
				$output .= '</td></tr>';
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez']){	
					foreach($crosstabs['delez'] as $row){	
						foreach($row as $tempDelez){
							$delez += $tempDelez;
						}
					}
				}
				
				$output .= '<tr><td align="center">';
				$output .= '<font color="red">'.$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</font>';
				$output .= '</td></tr>';
			}
		}
		// Suma na koncu vrstice
		elseif($orientation == 0){
			// Izpisemo podatek
			if($crosstabs['sumaVrstica'][$val]){
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$output .= '<tr><td align="center">';
					$output .= '<b>'.$crosstabs['sumaVrstica'][$val].'</b>';
					$output .= '</td></tr>';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$output .= '<tr><td align="center">';
					$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$output .= '</td></tr>';
				}
			}
			else{
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$output .= '<tr><td align="center">';
					$output .= '<b>0</b>';
					$output .= '</td></tr>';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$output .= '<tr><td align="center">';
					$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$output .= '</td></tr>';
				}
			}
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
				
				// Loop cez vrstico in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab'][$val]){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'][$val] as $key => $count){	
						$tempAvg += $count * $crosstabs['avg'][$val][$key];
					}
					$avg = $tempAvg / $crosstabs['sumaVrstica'][$val];
				}
				
				$output .= '<tr><td align="center">';
				$output .= '<font color="blue">'.$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).'</font>';
				$output .= '</td></tr>';
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez'][$val]){	
					foreach($crosstabs['delez'][$val] as $tempDelez){	
						$delez += $tempDelez;
					}
				}
				
				$output .= '<tr><td align="center">';
				$output .= '<font color="red">'.$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</font>';
				$output .= '</td></tr>';
			}
		}
		// Suma za stolpce
		else{
			// Izpisemo podatek
			if(isset($crosstabs['sumaStolpec'][$val])){			
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$output .= '<tr><td align="center">';
					$output .= '<b>'.$crosstabs['sumaStolpec'][$val].'</b>';
					$output .= '</td></tr>';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$output .= '<tr><td align="center">';
					$output .= $this->formatNumber($this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$output .= '</td></tr>';
				}
			}
			else{
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$output .= '<tr><td align="center">';
					$output .= '<b>0</b>';
					$output .= '</td></tr>';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$output .= '<tr><td align="center">';
					$output .= $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$output .= '</td></tr>';
				}
			}
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
				
				// Loop cez vrstico in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab']){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'] as $key => $row){
						if($row[$val] > 0)
							$tempAvg += $row[$val] * $crosstabs['avg'][$key][$val];
					}
					$avg = $tempAvg / $crosstabs['sumaStolpec'][$val];
				}
				
				$output .= '<tr><td align="center">';
				$output .= '<font color="blue">'.$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).'</font>';
				$output .= '</td></tr>';
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez']){	
					foreach($crosstabs['delez'] as $tempDelez){	
						$delez += $tempDelez[$val];
					}
				}
				
				$output .= '<tr><td align="center">';
				$output .= '<font color="red">'.$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</font>';
				$output .= '</td></tr>';
			}
		}
					
		$output .= '</table>';			
		$output .= '</td>';
	}
	
	// Izpis celice z vrednostmi
	function displayDataCell($crosstab, $percent, $avg, $delez){
		global $output;
		
		$output .= '<td>';
		$output .= '<table>';
			
		if($crosstab > 0){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$output .= '<tr><td align="center">';
				$output .= $crosstab;
				$output .= '</td></tr>';
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$output .= '<tr><td align="center">';
				$output .= $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$output .= '</td></tr>';
			}
		}
		else{		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$output .= '<tr><td align="center">';
				$output .= '0';
				$output .= '</td></tr>';
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$output .= '<tr><td align="center">';
				$output .= $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$output .= '</td></tr>';
			}
		}
		
		// Povprecje
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			$output .= '<tr><td align="center">';
			$output .= '<font color="blue">'.$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).'</font>';
			$output .= '</td></tr>';
		}
		
		// Delez
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			$output .= '<tr><td align="center">';
			$output .= '<font color="red">'.$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</font>';
			$output .= '</td></tr>';
		}
		
		$output .= '</table>';
		$output .= '</td>';
	}

	
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text,$length=64,$tail="...")
	{
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
		return $text;
	}

	function setUserId($usrId) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

	function formatNumber($value, $digit=0, $sufix=""){
	
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		# polovimo decimalna mesta in vejice za tisočice
		$decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
		$thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			
		//$result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;
		$result = number_format($result, $digit, ',', '') . $sufix;

		// Preprecimo da bi se stevilo z decimalko pretvorilo v datum
		//if($sufix == "")
			//$result = '="'. $result.'"';
		
		return $result;
	}
}

?>