<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.xls.php');
		

class XlsIzvozAnalizaCrosstab {

	var $anketa;						// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	
	public $crosstabClass = null;		//crosstab class
	
	var $crossData1;
	var $crossData2;

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $crossData1, $crossData2, $podstran = M_ANALIZA_CROSSTAB)
	{
		global $site_path;
		global $global_user_id;
		global $output;	
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->anketa['podstran'] = $podstran;
			
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
		
		//ustvarimo crosstab objekt in mu napolnimo variable (var1, var2, checkboxi)		
		$this->crosstabClass = new SurveyCrosstabs();
		$this->crosstabClass->Init($anketa);

		for($i=0; $i<sizeof($crossData1)/3; $i++){
			$index = $i * 3;
			$this->crossData1[$i] = array($crossData1[$index],$crossData1[$index+1],$crossData1[$index+2]);
		}
		for($i=0; $i<sizeof($crossData2)/3; $i++){
			$index = $i * 3;
			$this->crossData2[$i] = array($crossData2[$index],$crossData2[$index+1],$crossData2[$index+2]);
		}	
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		{
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
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

	function createXls()
	{
		global $site_path;
		global $lang;
		global $output;
					
		$convertTypes = array('charSet'	=> "windows-1250",
						 'delimit'	=> ";",
						 'newLine'	=> "\n",
						 'BOMchar'	=> "\xEF\xBB\xBF");
		
		$output = $convertTypes['BOMchar'];

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_crosstabs'].'</b></font></td></tr></table>';


		# polovimo nastavtve missing profila
		//SurveyConditionProfiles:: getConditionString();
		
		$this->crosstabClass->_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
		if (count($this->crosstabClass->_LOOPS) > 0) {
			# če mamo zanke
			foreach ( $this->crosstabClass->_LOOPS AS $loop) {

				$this->crosstabClass->_CURRENT_LOOP = $loop;
				$this->displayCrosstabsTable();
			}
		} else {
		
			// loopamo cez vse izbrane variable in izrisemo vse tabele
			for($j=0; $j<sizeof($this->crossData2); $j++){
				for($i=0; $i<sizeof($this->crossData1); $i++){					
					$this->crosstabClass->setVariables($this->crossData2[$j][0],$this->crossData2[$j][1],$this->crossData2[$j][2],$this->crossData1[$i][0],$this->crossData1[$i][1],$this->crossData1[$i][2]);
				
					$this->displayCrosstabsTable();	
				}
			}		
		} 
		
		return $output;
	}	

	public function displayCrosstabsTable() {
		global $lang;
		global $output;

		if ($this->crosstabClass->getSelectedVariables(1) !== null && $this->crosstabClass->getSelectedVariables(2) !== null) {
			$variables1 = $this->crosstabClass->getSelectedVariables(1);
			$variables2 = $this->crosstabClass->getSelectedVariables(2);
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {
					
					$crosstabs = null;
					$crosstabs_value = null;
					
					$crosstabs = $this->crosstabClass->createCrostabulation($v_first, $v_second);
					$crosstabs_value = $crosstabs['crosstab'];

					# podatki spremenljivk
					$spr1 = $this->crosstabClass->_HEADERS[$v_first['spr']];
					$spr2 = $this->crosstabClass->_HEADERS[$v_second['spr']];
		
					$grid1 = $spr1['grids'][$v_first['grd']];
					$grid2 = $spr2['grids'][$v_second['grd']];
					
					#število vratic in število kolon
					$cols = count($crosstabs['options1']);
					$rows = count($crosstabs['options2']);
		
					# ali prikazujemo vrednosti variable pri spremenljivkah
					$show_variables_values = $this->crosstabClass->doValues;
	
					# nastavitve oblike
					if (($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) && ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR)) {
						# dodamo procente in residuale
						$rowSpan = 3;
						$numColumnPercent = $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3;
						$numColumnResidual = $this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR;
						$tblColumn = max($numColumnPercent,$numColumnResidual);
					} else if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) { 
						# imamo samo procente
						$rowSpan = 2;
						$numColumnPercent = $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3;
						$numColumnResidual = 0;
						$tblColumn = $numColumnPercent;
					} else if ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR) {
						# imamo samo residuale
						$rowSpan = 2;
						$numColumnPercent = 0;
						$numColumnResidual = $this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR;
						$tblColumn = $numColumnResidual;
					} else {
						#prikazujemo samo podatke
						$rowSpan = 1;
						$numColumnPercent = 0;
						$numColumnResidual = 0;
						$tblColumn = 1;
					}
					
					//izracun sirine ene celice
					$singleWidth = round( 170 / $cols );		
					
					//izracun visine ene celice
					if($rowSpan == 1)
						$height = 8;
					elseif($rowSpan == 2)
						$height = 10;
					else
						$height = 15;

					# izrišemo tabelo
					
					# najprej izrišemo naslovne vrstice		
					# za multicheckboxe popravimo naslov, na podtip
					$sub_q1 = null;
					if ($spr1['tip'] == '6' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '19' || $spr1['tip'] == '20') {
						foreach ($spr1['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_first['seq']) {
									$sub_q1 = strip_tags($spr1['naslov']);
									if ($show_variables_values == true ) {
										$sub_q1 .= '&nbsp;('.strip_tags($spr1['variable']).')';
									}
									if ($spr1['tip'] == '16') {
										$sub_q1 .= ', ' . strip_tags($grid1['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid1['variable']) . ')' : '');
									} else {
										$sub_q1 .= ', ' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}
							}		
						}
					}
					if ($sub_q1 == null) {
						$sub_q1 .=  strip_tags($spr1['naslov']);
						$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
					}
					
					$sub_q2 = null;
					if ($spr2['tip'] == '6' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '19' || $spr2['tip'] == '20') {
						foreach ($spr2['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_second['seq']) {
									$sub_q2 = strip_tags($spr2['naslov']);
									if ($show_variables_values == true) {
										$sub_q2 .= '&nbsp;('.strip_tags($spr2['variable']).')';
									}
									if ($spr2['tip'] == '16') {
										$sub_q2.= ', <br style="mso-data-placement:same-cell;" />' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid2['variable']) . ')' : '');
									} else {
										$sub_q2.= ', <br style="mso-data-placement:same-cell;" />' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}							
							}		
						}
					}
					if ($sub_q2 == null) {
						$sub_q2 .= strip_tags($spr2['naslov']);
						$sub_q2 .= ($show_variables_values == true ? '&nbsp;('.strip_tags($spr2['variable']).')' : '');
					}
					
					$output .= '<table border="0"><tr><td></td></tr></table>';					
					
					# izrišemo tabelo
					# najprej izrišemo naslovne vrstice
					$output .= '<table border="1">';
					
					/*$output .= '<colgroup>';
					$output .= '<col style="width:auto; min-width:150px;" />';
					$output .= '<col style="width:auto; min-width:150px;" />';
					if (count($crosstabs['options1']) > 0 ) {
						$_width_percent = round(100 / count($crosstabs['options1'],2));
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							$output .= '<col style="width:'.$_width_percent.'%;" />';
						}
					
					}
					$output .= '<col style="width:auto;" />';
					$output .= '</colgroup>';*/
					
					$output .= '<tr><td></td><td></td>';
					/*$output .= '<td>&#x3A7;<sup>2</sup> = ';
					$output .= $this->formatNumber($crosstabs['hi2'], 3, '');
					$output .= '</td>';*/
					$output .= '<td align="center" colspan="' . $cols . '">';
 					$output .= '<b>'.$sub_q1.'</b>';
					$output .= '</td>';
					$output .= '<td>&nbsp;</td>';
					$output .= '</tr>';
					$output .= '<tr><td></td><td></td>';
					$col_cnt=0;
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							$col_cnt++;
							#ime variable
							$output .= '<td align="center">';
							$output .=  $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't' && $show_variables_values == true) {
								if ($crossVariabla['vr_id'] == null  ) {
									$output .= '<br style="mso-data-placement:same-cell;" /> ( '.$ckey1.' )';
								} else {
									$output .= '<br style="mso-data-placement:same-cell;" /> ( '.$crossVariabla['vr_id'].' )';
								}
							}
							$output .= '</td>';
						}
					}
					$col_cnt++;
					
					$output .= '<td align="center">' . $lang['srv_analiza_crosstab_skupaj'] . '</td>';
					$output .= '</tr>';
		
					$cntY = 0;
					if (count($crosstabs['options2']) > 0) {
						foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
							$cntY++;
							$output .= '<tr>';
			
							if ($cntY == 1) {
								# ime variable
								$output .= '<td align="center" rowspan="' . $rows . '" valign="top">';
								$output .= '<b>'.$sub_q2.'</b>';
								$output .= '</td>';
							}
							//$css_backY = 'rsdl_bck_variable'.($cntY & 1);
							$css_backY = ' rsdl_bck_variable1';
							
							$output .= '<td align="center">';
							
							$output .= $crossVariabla2['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla2['type'] !== 't' && $show_variables_values == true ) {
								if ($crossVariabla2['vr_id'] == null) {
									$output .= '<br style="mso-data-placement:same-cell;" /> ( '.$ckey2.' )';
								} else {
									$output .= '<br style="mso-data-placement:same-cell;" /> ( '.$crossVariabla2['vr_id'].' )';
								}
		
							}
							$output .= '</td>';
		
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								$output .= '<td align="center">';
								# celica z vebino
								{
									# prikazujemo eno ali več od: frekvenc, odstotkov, residualov
									$output .= '<table>';
									if ($this->crosstabClass->crossChk0) {
										# izpišemo frekvence crostabov
										$output .= '<tr>';
										$output .= '<td align="center">';
										$output .= ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;						
		#								.$crossTab[$crossVariabla1[cell_id]][$ckey2]
		#								
		
										$output .= '</td>';
										$output .= '</tr>';
									}
									
									if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
										# sirina celice v %
										if ( ($this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3) == 3 )
											$css_width = ' ctb_w33p';
										elseif (($this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3) == 2 )
											$css_width = ' ctb_w50p';
										else
											$css_width = ''; 
										$css_bt = ( $this->crosstabClass->crossChk0 ) ? 'anl_dash_bt' : '';
										# izpisemo procente
										$output .= '<tr>';
										$output .= '<td align="center">';
		
											$output .= '<table>';
											$output .= '<tr>';
											$col=0;
			
											if ($this->crosstabClass->crossChk1) {
												#procent vrstica
												$col++;
												
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'ctbChck_sp1' : '';
												$css_br = $numColumnPercent > $col ? ' anl_dash_br' : '';
												$output .= '<td align="center">';
												$output .= $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
												$output .= '</td>';
											}
											if ($this->crosstabClass->crossChk2) {
												#procent stolpec
												$col++;
												$css_br = $numColumnPercent > $col ? ' anl_dash_br' : '';
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'ctbChck_sp2' : '';
												$output .= '<td align="center">';
		
												$output .= $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
												$output .= '</td>';
											}
											if ($this->crosstabClass->crossChk3) {
												#procent skupni
												$col++;
												$css_br = $numColumnPercent > $col ? ' anl_dash_br' : '';
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'ctbChck_sp3' : '';
												$output .= '<td align="center">';
		
												$output .= $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
												$output .= '</td>';
											}
											$output .= '</tr>';
											$output .= '</table>';
		
										$output .= '</td>';
										$output .= '</tr>';
									}
									# izpisemo residuale
									if ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR) {
										# sirina celice v %
										if ( ($this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR) == 4 )
											$css_width = ' ctb_w25p';
										elseif ( ($this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR) == 3 )
											$css_width = ' ctb_w33p';
										elseif ( ($this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR) == 2 )
											$css_width = ' ctb_w50p'; 
										else
											$css_width = '';
										$css_bt = ( $this->crosstabClass->crossChk0 || ($this->crosstabClass->crossChk1 && $this->crosstabClass->crossChk2 && $this->crosstabClass->crossChk3)) ? 'anl_dash_bt' : '';
										$output .= '<tr>';
		
										$output .= '<td align="center">';
											$output .= '<table>';
											$output .= '<tr>';
											$col=0;
											
											if ($this->crosstabClass->crossChkEC) {
												$col++;
												$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'crossCheck_EC' : '';
												$output .= '<td align="center">';
												$output .= $this->formatNumber($crosstabs['exC'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
												$output .= '</td>';
											}
											if ($this->crosstabClass->crossChkRE) {
												$col++;
												$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'crossCheck_RE' : '';
												$output .= '<td align="center">';
												$output .= $this->formatNumber($crosstabs['res'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
												$output .= '</td>';
											}
											if ($this->crosstabClass->crossChkSR) {
												$col++;
												$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'crossCheck_SR' : '';
												$output .= '<td align="center">';
												$output .= $this->formatNumber($crosstabs['stR'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
												$output .= '</td>';
											}
											if ($this->crosstabClass->crossChkAR) {
												$col++;
												$css_br = $numColumnResidual > $col ? ' anl_dash_br' : '';
												$css_color = ($this->crosstabClass->doColor == 'true') ? 'crossCheck_AR' : '';
												$output .= '<td align="center">';
												$output .= $this->formatNumber($crosstabs['adR'][$ckey1][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'), '');
												$output .= '</td>';
											}
											$output .= '</tr>';
											$output .= '</table>';
										$output .= '</td>';
										$output .= '</tr>';
									}
									$output .= '</table>';
								}
								# konec celice z vsebino
								$output .= '</td>';
							}
			
							// vedno rišemo zadnji stolpec.
							$output .= '<td align="center" >';
								$output .= '<table>';
								if ($this->crosstabClass->crossChk0) {
									$output .= '<tr>';
									$output .= '<td align="center" colspan="' . ( $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3 ).'">';
									# suma po vrsticah
									$output .= (int)$crosstabs['sumaVrstica'][$ckey2];
									$output .= '</td>';
									$output .= '</tr>';
								}
								if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
									if (($this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3) == 3) {
										$css_width = ' ctb_w33p';
									} else if (($this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3) == 2) {
										$css_width = ' ctb_w50p';
									} else {
										$css_width = '';
									}
									$css_bt = ( $this->crosstabClass->crossChk0 ) ? ' anl_dash_bt' : '';
									# suma po vrsticah v procentih
									$output .= '<tr>';
									if ($this->crosstabClass->crossChk1) {
										$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp1' : '';
										$output .= '<td align="center">';
										$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
										$output .= '</td>';
									}
									if ($this->crosstabClass->crossChk2) {
										$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp2' : '';
										$css_border = ($this->crosstabClass->crossChk1 ? ' anl_dash_bl ' : '');
										$output .= '<td align="center">';
										$output .= $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
										$output .= '</td>';
									}
									if ($this->crosstabClass->crossChk3) {
										$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp3' : '';
										$css_border = ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 ? ' anl_dash_bl ' : '');
										$output .= '<td align="center">';
										$output .= $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
										$output .= '</td>';
									}
									$output .= '</tr>';
								}
							
								$output .= '</table>';
			
							$output .= '</td>';
							$output .= '</tr>';
						}
					}
					$cntY++;
					$output .= '<tr>';
		
					$output .= '<td align="center">&nbsp;</th>';
					//$css_backY = 'rsdl_bck_variable'.($cntY & 1);
					$css_backY = ' rsdl_bck_variable1';
					$output .= '<td align="center">' . $lang['srv_analiza_crosstab_skupaj'] . '</td>';
					// skupni sestevki po stolpcih
					if (count($crosstabs['options1']) > 0)
						foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
							$output .= '<td align="center" >';
							{ 
								# prikazujemo eno od treh možnosti
								$output .= '<table>';
								if ($this->crosstabClass->crossChk0) {
									$output .= '<tr>';
									$output .= '<td align="center" colspan="'.($this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3).'">';
									# suma po stolpcih
									$output .= (int)$crosstabs['sumaStolpec'][$ckey1];
									$output .= '</td>';
									$output .= '</tr>';
								}					
								if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
									# suma po stolpcih v procentih
									$css_bt = ($this->crosstabClass->crossChk0) ? ' anl_dash_bt' : '';
									$output .= '<tr>';
									if ($this->crosstabClass->crossChk1) {
										$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp1' : '';
										$output .= '<td align="center">';
										$output .= $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
										$output .= '</td>';
									}
									if ($this->crosstabClass->crossChk2) {
										$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp2' : '';
										$output .= '<td align="center">';
										$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
										$output .= '</td>';
									}
									if ($this->crosstabClass->crossChk3)
									{
										$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp3' : '';
										$output .= '<td align="center">';
										$output .= $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
										$output .= '</td>';
									}
									$output .= '</tr>';
								}				
								$output .= '</table>';
							}
							$output .= '</td>';
						}
						# zadnja celica z skupno sumo
						$output .= '<td align="center">';
						{ 
							$output .= '<table>';
							if ($this->crosstabClass->crossChk0) {
								$output .= '<tr>';
								$output .= '<td align="center" colspan="'.($this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3).'">';
								# skupna suma
								$output .= (int)$crosstabs['sumaSkupna'];
								$output .= '</td>';
								$output .= '</tr>';
							}
							if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
								# suma po stolpcih v procentih
								$css_bt = ($this->crosstabClass->crossChk0) ? ' anl_dash_bt' : '';
								$output .= '<tr>';
								if ($this->crosstabClass->crossChk1) {
									$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp1' : '';
									$css_border = ($this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) ? ' anl_dash_br' : '';
									$output .= '<td align="center">';
									$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									$output .= '</td>';
								}
								if ($this->crosstabClass->crossChk2) {
									$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp2' : '';
									$css_border = ($this->crosstabClass->crossChk3) ? ' anl_dash_br' : '';
									$output .= '<td align="center">';
									$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									$output .= '</td>';
								}
								if ($this->crosstabClass->crossChk3) {
									$css_color = ($this->crosstabClass->doColor == 'true') ? ' ctbChck_sp3' : '';
									$output .= '<td align="center">';
									$output .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
									$output .= '</td>';
								}
								$output .= '</tr>';
							}				
							$output .= '</table>';
						}
					$output .= '</td>';
		
					$output .= '</tr>';
					$output .= '</table>';
				
				}
			}
		}
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

	function formatNumber($value,$digit=0,$sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		//$result = number_format($result, $digit, ',', '.').$sufix;
		$result = number_format($result, $digit, ',', '') . $sufix;
	
		// Preprecimo da bi se stevilo z decimalko pretvorilo v datum
		//$result = '="'. $result.'"';
	
		return $result;
	}
}

?>