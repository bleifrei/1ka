<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.enka.rtf.php');

class RtfIzvozAnalizaFunctions {

	public static $anketa;
	public static $user_id;
	public static $from;
	
	public static $exportClass;	// instanca razreda v katerem izrisujemo PDF
	public static $analizaClass;	// instanca razreda kjer imamo analize (crosstab, means, ttest...)
	
	private static $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
	static function init($anketa, $exportClass, $from='sums', $analizaClass=null){
		global $global_user_id;
		
		self::$anketa = $anketa;
		self::$user_id = $global_user_id;
		
		self::$from = $from;
		
		self::$exportClass = $exportClass;
		
		if($analizaClass != null)
			self::$analizaClass = $analizaClass;
			
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($anketa);
		self::$sessionData = SurveyUserSession::getData();
	}

	
	// Izrisemo CROSSTAB TABELO
	public static function displayCrosstabsTable() {
		global $lang;
		
		if (self::$analizaClass->getSelectedVariables(1) !== null && self::$analizaClass->getSelectedVariables(2) !== null) {
			$variables2 = self::$analizaClass->getSelectedVariables(1);
			$variables1 = self::$analizaClass->getSelectedVariables(2);
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {
					
					$crosstabs = null;
					$crosstabs_value = null;
					
					$crosstabs = self::$analizaClass->createCrostabulation($v_first, $v_second);
					$crosstabs_value = $crosstabs['crosstab'];

					# podatki spremenljivk
					$spr1 = self::$analizaClass->_HEADERS[$v_first['spr']];
					$spr2 = self::$analizaClass->_HEADERS[$v_second['spr']];
		
					$grid1 = $spr1['grids'][$v_first['grd']];
					$grid2 = $spr2['grids'][$v_second['grd']];
					
					#število vratic in število kolon
					$cols = count($crosstabs['options1']);
					$rows = count($crosstabs['options2']);
		
					# ali prikazujemo vrednosti variable pri spremenljivkah
					$show_variables_values = self::$analizaClass->doValues;

					# nastavitve oblike
					if ((self::$analizaClass->crossChk1 || self::$analizaClass->crossChk2 || self::$analizaClass->crossChk3) && (self::$analizaClass->crossChkEC || self::$analizaClass->crossChkRE || self::$analizaClass->crossChkSR || self::$analizaClass->crossChkAR)) {
						# dodamo procente in residuale
						$rowSpan = 3;
						$numColumnPercent = self::$analizaClass->crossChk1 + self::$analizaClass->crossChk2 + self::$analizaClass->crossChk3;
						$numColumnResidual = self::$analizaClass->crossChkEC + self::$analizaClass->crossChkRE + self::$analizaClass->crossChkSR + self::$analizaClass->crossChkAR;
						$tblColumn = max($numColumnPercent,$numColumnResidual);
					} else if (self::$analizaClass->crossChk1 || self::$analizaClass->crossChk2 || self::$analizaClass->crossChk3) { 
						# imamo samo procente
						$rowSpan = 2;
						$numColumnPercent = self::$analizaClass->crossChk1 + self::$analizaClass->crossChk2 + self::$analizaClass->crossChk3;
						$numColumnResidual = 0;
						$tblColumn = $numColumnPercent;
					} else if (self::$analizaClass->crossChkEC || self::$analizaClass->crossChkRE || self::$analizaClass->crossChkSR || self::$analizaClass->crossChkAR) {
						# imamo samo residuale
						$rowSpan = 2;
						$numColumnPercent = 0;
						$numColumnResidual = self::$analizaClass->crossChkEC + self::$analizaClass->crossChkRE + self::$analizaClass->crossChkSR + self::$analizaClass->crossChkAR;
						$tblColumn = $numColumnResidual;
					} else {
						#prikazujemo samo podatke
						$rowSpan = 1;
						$numColumnPercent = 0;
						$numColumnResidual = 0;
						$tblColumn = 1;
					}
					
					//nastavitve tabele - (sirine celic, border...)
					$defw_full = 10500;
					$defw_part = 1500;
					
					if($cols == 1)
						$defw_part2 = 5000;
					elseif($cols == 2)
						$defw_part2 = 7500;
					else
						$defw_part2 = 8500;
						
					//izracun sirine ene celice
					$singleWidth = floor( $defw_part2 / ($cols) );	

					$borderB = '\clbrdrb\brdrs\brdrw10';
					$borderT = '\clbrdrt\brdrs\brdrw10';
					$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
					$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
					//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
					$bold = '\b';			
						
						
					# izri�emo tabelo
					self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
					self::$exportClass->rtf->MyRTF .= "{\par";
					
					# najprej izri�emo NASLOVNE VRSTICE					
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
										$sub_q2.= ', ' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid2['variable']) . ')' : '');
									} else {
										$sub_q2.= ', ' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}							
							}		
						}
					}
					if ($sub_q2 == null) {
						$sub_q2 .= strip_tags($spr2['naslov']);
						$sub_q2 .= ($show_variables_values == true ? '&nbsp;('.strip_tags($spr2['variable']).')' : '');
					}
					
					//self::$exportClass->crosstabVars = array($sub_q1, $sub_q2);
					
					//prva vrstica
					$tableHeader = '\trowd\trql\trrh400';
					
					$table = '\clvertalc\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';			
					$table .= '\clvertalc\cellx'.( 2 * $defw_part );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
					
					$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $defw_part2 );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($sub_q1). '\qc\cell';
					$table .= '\clvertalc\cellx'.( 2 * $defw_part + $defw_part2 + $singleWidth );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
					
					$tableEnd .= '\pard\intbl\row';
					
					self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
								
					//druga vrstica		
					$tableHeader = '\trowd\trql\trrh400';
					
					$table = '\clvertalc\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';			
					$table .= '\clvertalc\cellx'.( 2 * $defw_part );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
					
					
					if (count($crosstabs['options1']) > 0 ) {
						$i=1;
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							#ime variable
							$text = $crossVariabla['naslov'];
							# �e ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't') {
								$text .= ' ( '.$ckey1.' )';
							}	
							$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
							$tableEnd .= '\pard\intbl'.$bold.' '. self::$exportClass->encodeText($text). '\qc\cell';

							$i++;
						}
					}

					
					$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $defw_part2 + $singleWidth );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($lang['srv_analiza_crosstab_skupaj']). '\qc\cell';
					
					$tableEnd .= '\pard\intbl\row';
					
					self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					

					
					//VMESNE VRSTICE
					$cntY = 0;
					if (count($crosstabs['options2']) > 0) {
						
						//POSAMEZNA VMESNA VRSTICA
						foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
							$cntY++;					
			
							
							for($j=1; $j<=$rowSpan; $j++){
								
								$tableHeader = '\trowd\trql\trrh400';
						
								if( $cntY == 1 && $j == 1 ){
									$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
									$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($sub_q2). '\qc\cell';	
								}
								else{
									$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
									$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
								}		
									

									
								$text = $crossVariabla2['naslov'];
								if ($crossVariabla2['type'] !== 't') {
									$text .= ' ('.$ckey2.')';
								}											
								if($j == 1){
									$table .= '\clvertalc'.$borderLR.$borderT.'\cellx'.( 2 * $defw_part );	
									$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
								}
								else{
									$table .= '\clvertalc'.$borderLR.'\cellx'.( 2 * $defw_part );	
									$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
								}
								
								
								
								//del vrstice z vsebino
								$bold = '\b0';
								$i=1;
								foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
										
									//FREKVENCE
									if( $j == 1 ){							
										$text = ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
									}
									
									//PROCENTI
									elseif( $j == 2 && $numColumnPercent > 0 ){
										$x = 1;
										if (self::$analizaClass->crossChk1) {
											#procent vrstica
											$text = self::$exportClass->formatNumber(self::$analizaClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
										
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
											
											$x++;
										}
										if (self::$analizaClass->crossChk2) {
											#procent stolpec
											$text =  self::$exportClass->formatNumber(self::$analizaClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
										
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if (self::$analizaClass->crossChk3) {
											#procent skupni
											$text = self::$exportClass->formatNumber(self::$analizaClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
										
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
											$x++;
										}
									}
									
									//RESIDUALI
									else{	
										$x = 1;
										if (self::$analizaClass->crossChkEC) {
											$text = self::$exportClass->formatNumber($crosstabs['exC'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if (self::$analizaClass->crossChkRE) {
											$text = self::$exportClass->formatNumber($crosstabs['res'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if (self::$analizaClass->crossChkSR) {
											$text = self::$exportClass->formatNumber($crosstabs['stR'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if (self::$analizaClass->crossChkAR) {
											$text = self::$exportClass->formatNumber($crosstabs['adR'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
											$x++;
										}
									}
									
									$i++;
								}
													
								//SKUPAJ
								if( $j == 1){
									$bold = '\b';
									
									$text = (int)$crosstabs['sumaVrstica'][$ckey2];
									
									$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $defw_part2 + $singleWidth );	
									$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
									
									$bold = '\b0';
								}
								elseif( $j == 2 && $numColumnPercent > 0 ){
									$x = 1;
									# suma po vrsticah v procentih
									if (self::$analizaClass->crossChk1) {
										$text = self::$exportClass->formatNumber(100, 2, '%');
										
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if (self::$analizaClass->crossChk2) {
										$text = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if (self::$analizaClass->crossChk3) {
										$text = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
										$x++;
									}
								}
								else{
									$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
									$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
								}

								$tableEnd .= '\pard\intbl\row';					
								self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
								
								$bold = '\b';
							}
						}
					}
					$cntY++;
								
					
					// skupni sestevki po stolpcih - ZADNJA VRSTICA
					//popravimo row span (residualov ne upostevamo)
					$rowSpan = $numColumnResidual > 0 ? $rowSpan-1 : $rowSpan;
					
					for($j=1; $j<=$rowSpan; $j++){
						
						$tableHeader = '\trowd\trql\trrh400';		
						
						if($j == 1){
							$table = '\clvertalc'.$borderT.'\cellx'.( $defw_part );	
							$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
						
							$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part );	
							$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($lang['srv_analiza_crosstab_skupaj']). '\qc\cell';
						}
						else{
							$table = '\clvertalc\cellx'.( $defw_part );	
							$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
						
							$table .= '\clvertalc'.$borderLR.$borderB.'\cellx'.( 2 * $defw_part );	
							$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
						}
						
						if (count($crosstabs['options1']) > 0){
							
							$i=1;
							$bold = '\b0';					
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {			

								if($j == 1){
									$bold = '\b';
									
									$text = (int)$crosstabs['sumaStolpec'][$ckey1];					
									$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
									$tableEnd .= '\pard\intbl'.$bold.' '. self::$exportClass->encodeText($text). '\qc\cell';
									
									$bold = '\b0';
								}		

								else{
									$x = 1;
									# suma po stolpcih v procentih
									if (self::$analizaClass->crossChk1) {
										$text = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if (self::$analizaClass->crossChk2) {
										$text = self::$exportClass->formatNumber(100, 2, '%');
										
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if (self::$analizaClass->crossChk3)
									{
										$text = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
										$x++;
									}
								}
								
								$i++;
							}
						}
						
						
						# skupna suma
						if($j == 1){
							$bold = '\b';
						
							$text = (int)$crosstabs['sumaSkupna'];					
							$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $defw_part2 + $singleWidth );	
							$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
						
							$bold = '\b0';
						}

						else{
							$x = 1;
							# suma po stolpcih v procentih
							if (self::$analizaClass->crossChk1) {
								$text = self::$exportClass->formatNumber(100, 2, '%');
								
								$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
								$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
								$x++;
							}
							if (self::$analizaClass->crossChk2) {
								$text = self::$exportClass->formatNumber(100, 2, '%');
								
								$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
								$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
								$x++;
							}
							if (self::$analizaClass->crossChk3) {
								$text = self::$exportClass->formatNumber(100, 2, '%');
								
								$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
								$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($text). '\qc\cell';
										
								$x++;
							}
						}
						
						$bold = '\b';				
						$tableEnd .= '\pard\intbl\row';
					
						self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					}
					
					//konec tabele
					self::$exportClass->rtf->MyRTF .= "}";
				}
			}
		}
	}	
	/*
		prikazemo posamezno celico s podatki
		$data - vsebina (array)
		$width - sirina celice
		$numRows - stevilo vrstic (1, 2 ali 3)
		frekvence - da ali ne
		$numColumnPercent - stevilo stolpcev pri procentih (1, 2 ali 3)
		$numColumnResidual - stevilo stolpcev pri procentih (1, 2, 3 ali 4)
	*/
	static function displayCrosstabsCell($data, $width, $numRows, $frekvence, $numColumnPercent, $numColumnResidual){
	
		$height = ($numRows == 1 ? 8 : 5);
		$fullHeight = ($height == 8 ? $height : $numRows*$height);
		$i=0;
		
		//preberemo pozicijo - zacetek celice
		$y = self::$exportClass->pdf->GetY();
		$x = self::$exportClass->pdf->GetX();
		
		self::$exportClass->pdf->setDrawColor(170, 170, 170);
		
		//izrisemo frekvence
		if($frekvence == 1){
			self::$exportClass->pdf->MultiCell($width, $height, self::$exportClass->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		if($numColumnPercent > 0){
			
			self::$exportClass->pdf->setX($x);
			$singleWidth = round($width/$numColumnPercent);
			
			for($j=1; $j<$numColumnPercent; $j++){
				self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($data[$i]), 1, 'C', 0, 0, 0 ,0, true);				
				$i++;
			}
			self::$exportClass->pdf->MultiCell($width - (($numColumnPercent-1)*$singleWidth), $height, self::$exportClass->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		if($numColumnResidual > 0){
			
			self::$exportClass->pdf->setX($x);
			$singleWidth = round($width/$numColumnResidual);
			
			for($j=1; $j<$numColumnResidual; $j++){
				self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($data[$i]), 1, 'C', 0, 0, 0 ,0, true);				
				$i++;
			}
			self::$exportClass->pdf->MultiCell($width - (($numColumnResidual-1)*$singleWidth), $height, self::$exportClass->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		//zaradi preglednosti narisemo okvir celotne celice
		self::$exportClass->pdf->setDrawColor(0, 0, 0, 255);
		self::$exportClass->pdf->SetXY($x, $y);
		self::$exportClass->pdf->MultiCell($width, $fullHeight, '', 1, 'C', 0, 1, 0 ,0, true);		
		
		//na koncu nastavimo pozicijo na pravo mesto
		self::$exportClass->pdf->SetXY($x+$width, $y);	
	}
	
	// Izrisemo CROSSTAB GRAF
	public static function displayCrosstabChart(){
		global $lang;

		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		$chartID = implode('_', self::$analizaClass->variabla1[0]).'_'.implode('_', self::$analizaClass->variabla2[0]);
		$chartID .= '_counter_'.(!self::$exportClass->counter ? 0 : self::$exportClass->counter);
		
		$settings = self::$sessionData['crosstab_charts'][$chartID];
		$imgName = $settings['name'];

		
		// IZRIS GRAFA
		//self::$exportClass->rtf->new_page();
		//self::$exportClass->rtf->new_line(5);
		
		// Naslov posameznega grafa
		/*self::$exportClass->rtf->set_font("Arial Black", 8);
		
		if($settings['type'] == 1 || $settings['type'] == 4)		
			$title = self::$exportClass->rtf->bold(1) .self::$exportClass->crosstabVars[0].' / '.self::$exportClass->crosstabVars[1] . self::$exportClass->rtf->bold(0);
		else
			$title = self::$exportClass->rtf->bold(1) .self::$exportClass->crosstabVars[0] . self::$exportClass->rtf->bold(0);
			
		self::$exportClass->rtf->add_text(self::$exportClass->encodeText($title), 'center');
		self::$exportClass->rtf->new_line();	*/
		
		self::$exportClass->rtf->set_font("Times New Roman", 10);
		
		//$scale = 100;
		$size = getimagesize('pChart/Cache/'.$imgName);
		if($size[0] == 800)
			$scale = 75;
		elseif($size[0] == 2400)
			$scale = 50;
		else
			$scale = 100;
		
		self::$exportClass->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
	}
	
	
	// Izrisemo MEANS TABELO
	public static function displayMeansTable($_means) {
		global $lang;
		
		#število vratic in število kolon
		$cols = count($_means);
		# preberemo kr iz prvega loopa
		$rows = count($_means[0]['options']);

		// sirina ene celice
		$singleWidth = round( 180 / $cols / 2 );

		//nastavitve tabele - (sirine celic, border...)
		$defw_full = 10500;
		$defw_part = 5700;
		$defw_part2 = 8500;
		//$defw_part3 = 8500;
		//izracun sirine ene celice
		$singleWidth = floor( $defw_part2 / ($cols*2) );	

		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';
		$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		
		// zacetek tabele
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
		self::$exportClass->rtf->MyRTF .= "{\par";
		
		
		// prva vrstica
		$tableHeader = '\trowd\trql\trrh400';
				
		$label2 = self::$analizaClass->getSpremenljivkaTitle($_means[0]['v2']);
		$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($label2). '\qc\cell';					
				
		for ($i = 0; $i < $cols; $i++) {

			$label1 = self::$analizaClass->getSpremenljivkaTitle($_means[$i]['v1']);

			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($label1). '\qc\cell';
		}	
		
		$tableEnd .= '\pard\intbl\row';
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		// druga vrstica
		$tableHeader = '\trowd\trql\trrh400';
					
		$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';			

		for ($i = 0; $i < $cols; $i++) {

			$label1 = self::$analizaClass->getSpremenljivkaTitle($_means[$i]['v1']);

			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (((($i+1) * 2) - 1) * $singleWidth) );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($lang['srv_means_label']). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($lang['srv_means_label4']). '\qc\cell';
		}	
		
		$tableEnd .= '\pard\intbl\row';
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);


		// vrstice s podatki
		if (count($_means[0]['options']) > 0) {
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$variabla = $crossVariabla2['naslov'];
				# če ni tekstovni odgovor dodamo key
				if ($crossVariabla2['type'] !== 't' ) {
					if ($crossVariabla2['vr_id'] == null) {
						$variabla .= ' ( '.$ckey2.' )';
					} else {
						$variabla .= ' ( '.$crossVariabla2['vr_id'].' )';
					}
				}
				$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($variabla). '\b0\qc\cell';

				# celice z vsebino
				for ($i = 0; $i < $cols; $i++) {

					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (((($i+1) * 2) - 1) * $singleWidth) );	
					$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText(self::$analizaClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))). '\qc\cell';
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
					$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText((int)$_means[$i]['sumaVrstica'][$ckey2]). '\qc\cell';
				}
				
				$tableEnd .= '\pard\intbl\row';
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			}
		}
		
		// SKUPAJ
		$tableHeader = '\trowd\trql\trrh400';
			
		$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($lang['srv_means_label3']). '\b0\qc\cell';

		for ($i = 0; $i < $cols; $i++) {

			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (((($i+1) * 2) - 1) * $singleWidth) );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText(self::$analizaClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText((int)$_means[$i]['sumaSkupna']). '\qc\cell';
		}

		$tableEnd .= '\pard\intbl\row';
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		// konec tabele
		self::$exportClass->rtf->MyRTF .= "}";
	}
	
	// Izrisemo MEANS GRAF
	public static function displayMeanChart($counter){
		global $lang;

		$variables1 = self::$exportClass->meanData1;
		$variables2 = self::$exportClass->meanData2;
		
		$pos1 = floor($counter / count($variables2));
		$pos2 = $counter % count($variables2);
		
		$chartID = implode('_', $variables1[$pos1]).'_'.implode('_', $variables2[$pos2]);
		$chartID .= '_counter_'.$counter;


		$settings = self::$sessionData['mean_charts'][$chartID];
		$imgName = $settings['name'];

		// IZRIS GRAFA		
		//$scale = 100;
		$size = getimagesize('pChart/Cache/'.$imgName);
		if($size[0] == 800)
			$scale = 75;
		elseif($size[0] == 2400)
			$scale = 50;
		else
			$scale = 100;
		
		self::$exportClass->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
		
		self::$exportClass->rtf->new_line();
	}
	
	
	// Izrisemo TTEST TABELO
	public static function displayTTestTable($ttest) {
		global $lang;
		
		# preverimo ali imamo izbrano odvisno spremenljivko
		$spid1 = self::$sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = self::$sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = self::$sessionData['ttest']['variabla'][0]['grd'];
		
		
		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {
			
			$spr_data_1 = self::$analizaClass->_HEADERS[$spid1];
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
			$spid2 = self::$sessionData['ttest']['spr2'];
			$sprLabel2 =  trim(str_replace('&nbsp;','',self::$sessionData['ttest']['label2']));
			$label1 = self::$analizaClass->getVariableLabels(self::$sessionData['ttest']['sub_conditions'][0]);
			$label2 = self::$analizaClass->getVariableLabels(self::$sessionData['ttest']['sub_conditions'][1]);
			
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
			$bold = '\b';
			
			//nastavitve tabele - (sirine celic, border...)
			$defw_full = 13500;
			$defw_part = 5000;
			$defw_part2 = 9000;
			$defw_part3 = 1000;

			
			// zacetek tabele
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
			self::$exportClass->rtf->MyRTF .= "{\par";		
			
			
			// prva vrstica
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($sprLabel2). '\qc\cell';			
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($sprLabel1). '\qc\cell';		
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
			
				
			// druga vrstica
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';			
				
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText('n'). '\qc\cell';	
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 2*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('x'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 3*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('s^2'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 4*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('se(x)'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 5*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('±1,96×se(x)'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 6*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('d'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 7*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('se(d)'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 8*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('Sig.'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 9*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('t'). '\qc\cell';
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			

			// vrstici s podatki
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($label1). '\qc\cell';			
				
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->formatNumber($ttest[1]['n'], 0). '\qc\cell';	
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 2*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[1]['x'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 3*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[1]['s2'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 4*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[1]['se'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 5*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[1]['margin'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 6*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest['d'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 7*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest['sed'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 8*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest['sig'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 9*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest['t'], 3). '\qc\cell';
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			

			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($label2). '\qc\cell';			
				
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->formatNumber($ttest[2]['n'], 0). '\qc\cell';	
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 2*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[2]['x'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 3*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[2]['s2'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 4*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[2]['se'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 5*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->formatNumber($ttest[2]['margin'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 6*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 7*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 8*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 9*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// konec tabele
			self::$exportClass->rtf->MyRTF .= "}";
		}	
	}
	
	// Izrisemo TTEST GRAF
	public static function displayTTestChart(){
		global $lang;

		$tableChart = new SurveyTableChart(self::$anketa, self::$analizaClass, 'ttest');
		$tableChart->setTTestChartSession();
		
		$spid1 = self::$sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = self::$sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = self::$sessionData['ttest']['variabla'][0]['grd'];
		$sub1 = self::$sessionData['ttest']['sub_conditions'][0];
		$sub2 = self::$sessionData['ttest']['sub_conditions'][1];
		$chartID = $sub1.'_'.$sub2.'_'.$spid1.'_'.$seq1.'_'.$grid1;
	
		$settings = self::$sessionData['ttest_charts'][$chartID];
		$imgName = $settings['name'];

		// IZRIS GRAFA		
		//$scale = 100;
		$size = getimagesize('pChart/Cache/'.$imgName);
		if($size[0] == 800)
			$scale = 75;
		elseif($size[0] == 2400)
			$scale = 50;
		else
			$scale = 100;
		
		self::$exportClass->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
		
		self::$exportClass->rtf->new_line();
	}
	
	
	// Izrisemo BREAK MULTIGRID TABELO
	static function displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka) {
		global $lang;
		
		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = self::$analizaClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$means = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$means[$fkey][$sequence] = self::$analizaClass->getMeansFromKey($frequencys[$fkey][$sequence]);
			}
		}
		
		if ($tip != 16 && $tip != 20) {
			if ($tip == 1 || $tip == 3) {
				if (count($spremenljivka['options']) < 15) {
					$rowspan = 2;
					$colspan = count($spremenljivka['options'])+1;
				} else {
					$rowspan = 1;
					$colspan = 1;
				}
			} else {
				$rowspan = 2;
				$colspan = count($sequences);
			}
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
			$bold = '\b';
			
			//nastavitve tabele - (sirine celic, border...)
			$defw_full = 13500;
			$defw_part = 5500;
			$defw_part2 = 8000;
			$defw_part3 = floor(8000 / $colspan);

			
			// zacetek tabele
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
			self::$exportClass->rtf->MyRTF .= "{\par";	
			
			
			// PRVA VRSTICA	
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'). '\qc\cell';		
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
			
			
			// DRUGA VRSTICA
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
			
			$i=1;
			if ($tip != 1 && $tip != 3) {
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
						$i++;
					}
				}
			} 
			else if (count($spremenljivka['options']) < 15) {
				foreach ($spremenljivka['options'] AS $okey => $option) {
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText($option.' ('.$okey.')'). '\qc\cell';
					$i++;
				}
				$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
				$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText('povprečje'). '\qc\cell';	
			}
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// VRSTICE S PODATKI
			foreach ($frequencys AS $fkey => $fkeyFrequency) {

				$tableHeader = '\trowd\trql\trrh400';
					
				$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl '.self::$exportClass->encodeText($forSpremenljivka['options'][$fkey]). '\qc\cell';	
				
				$i=1;
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						if ($variable['other'] != 1) {
							$sequence = $variable['sequence'];
							if (($tip == 1 || $tip == 3) && count($spremenljivka['options']) < 15) {
								foreach ($spremenljivka['options'] AS $okey => $option) {
									$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
									$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']). '\qc\cell';
									$i++;
								}
							}
							$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
							$tableEnd .= '\pard\intbl '.SurveyAnalysis::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
							$i++;
						}
					}
				}
				$tableEnd .= '\pard\intbl\row';
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			}
			
			// konec tabele
			self::$exportClass->rtf->MyRTF .= "}";
			self::$exportClass->rtf->new_line(3);
		}
		
		else {
			$rowspan = 2;
			$colspan = $spremenljivka['grids'][0]['cnt_vars'];
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$bold = '\b';
			
			//nastavitve tabele - (sirine celic, border...)
			$defw_full = 13500;
			$defw_part = 5500;
			$defw_part2 = 8000;
			$defw_part3 = floor(8000 / $colspan);

						
			# za multicheck razdelimo na grupe - skupine
			foreach ($frequencys AS $fkey => $frequency) {
				
				// zacetek tabele
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
				self::$exportClass->rtf->MyRTF .= "{\par";	
				
				
				// 1. vrstica
				$tableHeader = '\trowd\trql\trrh400';					
				$table = '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
				$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'). '\qc\cell';			
				$tableEnd .= '\pard\intbl\row';
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
				
				
				//2. vrstica
				$tableHeader = '\trowd\trql\trrh400';					
				$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText(''). '\qc\cell';			

				$i=1;
				foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {	
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText($variable['naslov']). '\qc\cell';
					$i++;
				}
				
				$tableEnd .= '\pard\intbl\row';
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
				
				
				// vrstice s podatki
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {

					$tableHeader = '\trowd\trql\trrh400';					
					$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl '.self::$exportClass->encodeText('('.$grid['variable'].') '.$grid['naslov']). '\qc\cell';	

					$i=1;
					foreach ($grid['variables'] AS $vkey => $variable) {
						$sequence = $variable['sequence'];

						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl '.SurveyAnalysis::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
						$i++;
					}
					
					$tableEnd .= '\pard\intbl\row';
					self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
				}

				// konec tabele
				self::$exportClass->rtf->MyRTF .= "}";
				self::$exportClass->rtf->new_line(3);
			}
		}
	}
	
	// Izrisemo BREAK MULTIGRID TABELO
	static function displayBreakTableNumber($forSpr,$frequencys,$spremenljivka) {
		global $lang;

		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = self::$analizaClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$means = array();
		$totalMeans = array();
		$totalFreq = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$means[$fkey][$sequence] = self::$analizaClass->getMeansFromKey($frequencys[$fkey][$sequence]);
			}
		}
		

		# za multi number naredimo po skupinah
		if ($tip != 20) {	

			$rowspan = 3;
			$colspan = count($sequences);
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
			$bold = '\b';
			
			//nastavitve tabele - (sirine celic, border...)
			$defw_full = 13500;
			$defw_part = 5500;
			$defw_part2 = 8000;
			$defw_part3 = floor(8000 / $colspan);

			
			// zacetek tabele
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
			self::$exportClass->rtf->MyRTF .= "{\par";	
			
			
			// PRVA VRSTICA	
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'). '\qc\cell';		
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
			
			
			// DRUGA VRSTICA
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
			
			$i=1;
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
					$i++;
				}
			}
		
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// TRETJA VRSTICA
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
			
			$i=1;
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText('Povprečje'). '\qc\cell';
					$i++;
				}
			}
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// VRSTICE S PODATKI
			foreach ($frequencys AS $fkey => $fkeyFrequency) {

				$tableHeader = '\trowd\trql\trrh400';
					
				$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl '.self::$exportClass->encodeText($forSpremenljivka['options'][$fkey]). '\qc\cell';	
				
				$i=1;
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						if ($variable['other'] != 1) {
							$sequence = $variable['sequence'];
							$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
							$tableEnd .= '\pard\intbl '.SurveyAnalysis::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
							
							$totalMeans[$sequence] += (self::$analizaClass->getMeansFromKey($fkeyFrequency[$sequence])*(int)$frequencys[$fkey][$sequence]['validCnt']);
							$totalFreq[$sequence]+= (int)$frequencys[$fkey][$sequence]['validCnt'];
							
							$i++;
						}
					}
				}
				$tableEnd .= '\pard\intbl\row';
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			}
			
			
			// dodamo še skupno sumo in povprečje
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl '.self::$exportClass->encodeText('Skupaj'). '\qc\cell';	
			
			$i=1;
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					
					$sequence = $variable['sequence'];
					if ($variable['other'] != 1) {
						#povprečja
						$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
					
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b '.SurveyAnalysis::formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\b0\qc\cell';
						
						$i++;
					}	
				}	
			}
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// konec tabele
			self::$exportClass->rtf->MyRTF .= "}";
			self::$exportClass->rtf->new_line(3);
		}
		
		else {
			$rowspan = 3;
			$colspan = count($spremenljivka['grids'][0]['variables']);
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$bold = '\b';
			
			//nastavitve tabele - (sirine celic, border...)
			$defw_full = 13500;
			$defw_part = 5500;
			$defw_part2 = 8000;
			$defw_part3 = floor(8000 / $colspan);

						
			# za multinumber izrisemo samo izbrano podtabelo
			$gkey = $spremenljivka['break_sub_table']['key'];
			$grid = $spremenljivka['grids'][$gkey];
				

			// zacetek tabele
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
			self::$exportClass->rtf->MyRTF .= "{\par";	
			
			// PRVA VRSTICA	
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'). '\qc\cell';		
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
			
			
			// DRUGA VRSTICA
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
			
			$i=1;
			foreach ($grid['variables'] AS $vkey => $variable) {
				$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
				$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
				$i++;
			}
		
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// TRETJA VRSTICA
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
			
			$i=1;
			foreach ($grid['variables'] AS $vkey => $variable) {
				$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
				$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText('Povprečje'). '\qc\cell';
				$i++;
			}
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// vrstice s podatki
			foreach ($forSpremenljivka['options'] AS $okey => $option) {

				$tableHeader = '\trowd\trql\trrh400';					
				$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl '.self::$exportClass->encodeText($option). '\qc\cell';	

				$i=1;
				foreach ($grid['variables'] AS $vkey => $variable) {
					$sequence = $variable['sequence'];

					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl '.SurveyAnalysis::formatNumber($means[$okey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
					
					$totalMeans[$sequence] += ($means[$okey][$sequence]*(int)$frequencys[$okey][$sequence]['validCnt']);
					$totalFreq[$sequence]+= (int)$frequencys[$okey][$sequence]['validCnt'];	
					
					$i++;
				}
				
				$tableEnd .= '\pard\intbl\row';
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
			}
			
			// dodamo še skupno sumo in povprečje
			$tableHeader = '\trowd\trql\trrh400';					
			$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl '.self::$exportClass->encodeText('Skupaj'). '\qc\cell';	

			$i=1;
			foreach ($grid['variables'] AS $vkey => $variable) {
				$sequence = $variable['sequence'];

				if ($variable['other'] != 1) {
						#povprečja
						$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b '.SurveyAnalysis::formatNumber($totalMean,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\b0\qc\cell';
						
						$i++;
				}					
			}
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
			

			// konec tabele
			self::$exportClass->rtf->MyRTF .= "}";
			self::$exportClass->rtf->new_line(3);
		}
	}
	
	// Izrisemo BREAK MULTIGRID TABELO
	static function displayBreakTableText($forSpr,$frequencys,$spremenljivka) {
		global $lang;

		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = self::$analizaClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$texts = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$texts[$fkey][$sequence] = self::$analizaClass->getTextFromKey($fkeyFrequency[$sequence]);
			}
		}
		
		$rowspan = 3;
		$colspan = count($spremenljivka['grids'][0]['variables']);
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';
		$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$bold = '\b';
		
		//nastavitve tabele - (sirine celic, border...)
		$defw_full = 13500;
		$defw_part = 5500;
		$defw_part2 = 8000;
		$defw_part3 = floor(8000 / $colspan);

			
		# za multinumber izrisemo samo izbrano podtabelo
		$gkey = $spremenljivka['break_sub_table']['key'];
		$grid = $spremenljivka['grids'][$gkey];


		// zacetek tabele
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(16);
		self::$exportClass->rtf->MyRTF .= "{\par";	
		
		// PRVA VRSTICA	
		$tableHeader = '\trowd\trql\trrh400';
					
		$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
		$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'). '\qc\cell';		
		$tableEnd .= '\pard\intbl\row';
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
		
		
		// DRUGA VRSTICA
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->encodeText('&nbsp; '). '\qc\cell';	
		
		$i=1;
		foreach ($grid['variables'] AS $vkey => $variable) {
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.self::$exportClass->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
			$i++;
		}
	
		$tableEnd .= '\pard\intbl\row';
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					
		
		// vrstice s podatki
		foreach ($forSpremenljivka['options'] AS $okey => $option) {

			$tableHeader = '\trowd\trql\trrh400';					
			$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl '.self::$exportClass->encodeText($option). '\qc\cell';	

			$i=1;
			foreach ($grid['variables'] AS $vkey => $variable) {
				$sequence = $variable['sequence'];
				
				if (count($texts[$okey][$sequence]) > 0) {
					$text = '';
					foreach ($texts[$okey][$sequence] AS $ky => $units) {
						$text .= ' '.$units['text'].'\\line\n';
					}
					$text = substr($text,0,-7);

					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText($text). '\qc\cell';
				}
				else{
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl '.self::$exportClass->encodeText(''). '\qc\cell';
				}
				
				$i++;
			}
			
			$tableEnd .= '\pard\intbl\row';
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
		}
					

		// konec tabele
		self::$exportClass->rtf->MyRTF .= "}";
		self::$exportClass->rtf->new_line(3);
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->_font_size(24);
	}
	
	// Izrisemo BREAK GRAF
	static function displayBreakChart($forSpr,$frequencys,$spremenljivka){
		global $lang;

		if($spremenljivka['tip'] == 20){
			// Preberemo za kateri grid izrisujemo tabelo
			$gkey = $spremenljivka['break_sub_table']['key'];
			
			$spr1 = self::$sessionData['break']['seq'].'-'. self::$sessionData['break']['spr'].'-undefined';
			$spr2 = $spremenljivka['grids'][$gkey]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
		}
		else{
			$spr1 = self::$sessionData['break']['seq'].'-'. self::$sessionData['break']['spr'].'-undefined';
			$spr2 = $spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
		}
		
		$chartID = $spr1.'_'.$spr2;
		
		$settings = self::$sessionData['break_charts'][$chartID];
		
		$imgName = $settings['name'];

		$size = getimagesize('pChart/Cache/'.$imgName);
		if($size[0] == 800)
			$scale = 75;
		elseif($size[0] == 2400)
			$scale = 50;
		else
			$scale = 100;
		
		self::$exportClass->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');	
	}	
	
	
	// Izrisujemo NAVADEN GRAF
	public static function displayChart($spid, $type=0, $displayTitle=false){
		global $site_path;
		global $lang;
						
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
	
		// ce ga imamo v sessionu ga preberemo
		if(isset(self::$sessionData['charts'][$spid])){
				
			// Napolnimo podatke za graf - rabimo za cache
			if(count(SurveyAnalysis::$_LOOPS) == 0)
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = self::$sessionData['charts'][$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']];
			
			$DataSet = SurveyChart::getDataSet($spid, $settings);
			
			// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
			if($DataSet == 0){
				//self::displayEmptyWarning($spid);
				return;
			}
			
			// preberemo ime slike iz sessiona
			$imgName = $settings['name'];
		}
		// ce ga nimamo v sessionu
		else{
			// Napolnimo podatke za graf - rabimo za cache
			$settings = SurveyChart::getDefaultSettings();
			
			$DataSet = SurveyChart::getDataSet($spid, $settings);
			
			// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
			if($DataSet == 0){
				//self::displayEmptyWarning($spid);
				return;
			}
			
			$ID = SurveyChart::generateChartId($spid, $settings, $DataSet->GetNumerus());
			
			$Cache = new pCache('pChart/Cache/');
			$Cache = new pCache('../survey/pChart/Cache/');
			$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		}
	
		$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $stevilcenje . $spremenljivka['naslov'];
		$TITLE = self::$exportClass->rtf->bold(1) . $title . self::$exportClass->rtf->bold(0);
		
		if($displayTitle){
			
			self::$exportClass->rtf->new_line(3);
		
			$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			$TITLE = self::$exportClass->rtf->bold(1) . $title . self::$exportClass->rtf->bold(0);	
			self::$exportClass->rtf->set_font("Arial Black", 8);
			self::$exportClass->rtf->add_text($TITLE, 'center');
			
			if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
				self::$exportClass->rtf->set_font("Arial", 8);
				self::$exportClass->rtf->add_text('(n = '.$DataSet->GetNumerus().')', 'center');
				$TITLE .= '(n = '.$DataSet->GetNumerus().')';
			}
			
			self::$exportClass->rtf->new_line();
			if($spremenljivka['tip'] == 2){
				self::$exportClass->rtf->set_font("Arial", 7);
				self::$exportClass->rtf->add_text($lang['srv_info_checkbox'], 'center');
				self::$exportClass->rtf->new_line();
			}
		}
			
		// IZRIS GRAFA		
		$size = getimagesize('pChart/Cache/'.$imgName);
		if($size[0] == 800)
			$scale = 75;
		elseif($size[0] == 2400)
			$scale = 50;
		else
			$scale = 100;
		self::$exportClass->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
	
		self::$exportClass->rtf->new_line();
		
		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
			
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					self::outputOtherAnswers($oAnswers);
					self::tableEnd();
				}
			}
		}	
	}
	
	static function chartTableRow($arrayText, $arrayParams=array()){
			
		$defw_full = 9500;
		$defw_part = 7700;
		$defw_part2 = 1800;

		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		//$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');
		$borderT = '\clbrdrt\brdrs\brdrw10';
		$align = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
		
		
		if($arrayParams['type'] == 1){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->enkaEncode($arrayText[1]) .$align.'\cell';
				
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[2]),20,'...').'\qc\cell';
		}
		else{
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->enkaEncode($arrayText[1]) .$align.'\cell';
		}
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	
	static function chartTableHeader(){	
		global $lang;
		
		self::$exportClass->rtf->MyRTF .= "{\par";
		
		$naslov = array();
		$naslov[] = '&nbsp; ';
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('borderB' => 1, 'borderT' => 1, 'bold' => 'B', 'align2' => 'C', 'type' => 1);
		
		self::chartTableRow($naslov, $params);	
	}
	
	// Izriše sumarnik v vertikalni obliki
	static function sumVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# dodamo opcijo kje izrisujemo legendo
		$inline_legenda = false;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);
		
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		self::sumsTableHeader();
		
		$show_valid_percent = (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;											
		// konec naslovne vrstice

		
		$_answersOther = array();
		$sum_xi_fi=0;
		$N = 0;
									
		$_tmp_for_div = array();
		# izpišemo vlejavne odgovore
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			# dodamo dodatne vrstice z albelami grida
			if (count($grid['variables']) > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['text'] != true && $variable['other'] != true) {
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;

					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							// za povprečje
							$xi = $vkey;
							$fi = $vAnswer['cnt'];
							
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;

							if ($vAnswer['cnt'] > 0 /*&& $counter < $maxAnswer*/ || true) { # izpisujemo samo tiste ki nisno 0
								
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
							# za poznejše računannje odklona
							$_tmp_for_div[] = array('xi'=>$xi, 'fi'=>$fi, 'sequence'=>$_sequence); 
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}		
		}

		# odklon
		$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
		#standardna diviacija
		$div = 0;
		$sum_pow_xi_fi_avg  = 0;
		foreach ( $_tmp_for_div as $tkey => $_tmp_div_data) {
			$xi = $_tmp_div_data['xi'];
			$fi =  $_tmp_div_data['fi'];
			
			$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
		}
		$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

		self::$exportClass->rtf->MyRTF .= "}";
		
		# izpišemo še odklon in povprečje
		if ($show_valid_percent == 1 && SurveyAnalysis::$_HEADERS[$spid]['skala'] != 1) {
			
			self::$exportClass->rtf->MyRTF .= "{\par";
			
			$defw_full = 10300;
			$defw_part0 = 1100;
			$defw_part = 1300;
			$defw_part2 = 4000;
			
			$bold = '\b0';
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';		
			
			$tableHeader = '\trowd\trql\trrh400';
			
			$table .= '\clvertalc\cellx'.( $defw_part0 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . '\qc\cell';
			
			$table .= '\clvertalc\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . '\cell';
				
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_povprecje']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2*$defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';	
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3*$defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_odklon']),20,'...').'\qc\cell';	
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4*$defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';	
			
			$tableEnd .= '\pard\intbl\row';
			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			self::tableEnd();
		}		

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				self::tableEnd();
			}		
		}
	}

	static function sumVerticalCheckbox($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		$inline_legenda = count ($spremenljivka['grids']) > 1;
		if ($variable['other'] != '1' && $variable['text'] != '1') {
			$_tip =  SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala');
		} else {
			$_tip =  $lang['srv_analiza_vrsta_bese'];
			$_oblika =  $lang['srv_analiza_oblika_nomi'];
		}
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0) 
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_valid_cnt[$gid] = max($_valid_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				$_valid[$gid][$vid] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'];
				$_navedbe[$gid] += SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
			}
		}
			
		
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1000;
		$defw_part2 = 2400;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 5 * $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_units']),20,'...') . $align . '\qc\cell';

		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 7 * $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_arguments']),20,'...') . $align . '\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		//tretja vrstica
		$text = array();
		
		$text[] = '';
		$text[] = '';
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_valid']);
		$text[] = self::$exportClass->encodeText('% - '.$lang['srv_analiza_opisne_valid']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_num_units_valid']);
		$text[] = self::$exportClass->encodeText('% - '.$lang['srv_analiza_num_units_valid']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = self::$exportClass->encodeText('%');

		self::sumsTableRowVerticalCheckbox($text);
		//konec naslovnih vrstic
		
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] as $gid => $grid) {
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				if ($variable['other'] != 1) {
					$_sequence = $variable['sequence'];
					
					$text = array();
		
					$text[] = self::$exportClass->encodeText($variable['variable']);
					$text[] = self::$exportClass->encodeText($variable['naslov']);				
					
					// Frekvence
					$text[] = self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					// Veljavni
					$text[] = self::$exportClass->encodeText((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']));
					
					// Procent veljavni
					$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0; 
					$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					$_max_valid = max ($_max_valid, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
					
					// Ustrezni
					$text[] = self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					
					// % Ustrezni
					$valid = (int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
					$valid = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$_percent = ($_max_appropriate > 0 ) ? 100*$valid / $_max_appropriate : 0;
					$text[] =  self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));			
					
					$text[] =  self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					$_percent = ($_navedbe[$gid] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / $_navedbe[$gid] : 0;
					$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					self::sumsTableRowVerticalCheckbox($text);
											
				} else {
					# drugo 
				}
			}
			
			$text = array();
			
			$text[] = '';
			
			$text[] = self::$exportClass->encodeText($lang['srv_anl_suma_valid']);
			
			$text[] = '';
			
			$text[] = self::$exportClass->encodeText($_max_valid);
			$text[] = '';			
			
			$text[] = self::$exportClass->encodeText($_max_appropriate);	
			$text[] = '';
			
			$text[] = self::$exportClass->encodeText($_navedbe[$gid]);
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));						
			
			self::sumsTableRowVerticalCheckbox($text);
		}
		self::tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				self::tableEnd();
			}	
		}		
	}
	
	/** Izriše nagovor
	 * 
	 */
	static function sumNagovor($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_tip = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala'); 

		self::tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		self::tableEnd();
	}
	
	/** Izriše number odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	static function sumNumberVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if ((int)$spremenljivka['enota'] == 0 && SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1) {
			$show_enota = false;
		}
		
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				
				# za povprečje				
				$sum_xi_fi=0;
				$N = 0;
				$div=0;
				$min = null;
				$max = null;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {

						$fi = $_validFreq['cnt'];
						$sum_xi_fi += $xi * $fi ;
						$N += $fi;
						$min = $min != null ? min($min,$xi) : $xi;
						$max = max($max,$xi);
					}
				}
				
				#povprečje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
				$sum_avg += $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'] = $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'] = $min;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'] = $max;

				#standardna diviacija
				$div = 0;
				$sum_pow_xi_fi_avg  = 0;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {
						$fi = $_validFreq['cnt'];
						$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
					}
				}
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
				
				#določimo še polja drugo za kasnejši prikaz
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}		
		
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica		
		$text = array();
		
		$text[] = '&nbsp; ';
		
		if ($show_enota) {
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']);;
			} else {
				$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_variable_text']);
			}
		} else {
			$text[] = '&nbsp; ';
		}
		
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_m']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_num_units']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_odklon']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_max']);

		$params = array('bold' => 'B');
		
		self::sumsTableRowNumberVertical($text, $params);
		//konec naslovnih vrstic

		$_answersOther = array();
		$_grupa_cnt = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			if (count($spremenljivka['grids']) > 1 && $_grupa_cnt !== 0 && $spremenljivka['tip'] != 6) {
				$grid['new_grid'] = true;
			}

			$_grupa_cnt ++;
			if (count($grid['variables']) > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) {
						$_sequence = $variable['sequence'];
			
						$text = array();
		
						if ($spremenljivka['tip'] != 7 ) {
							$text[] = self::$exportClass->encodeText($variable['variable']);
						}
						else
							$text[] = '&nbsp; ';
						
						if ($show_enota) {
							$text[] = self::$exportClass->encodeText((count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov']);
						} else {
							$text[] = '&nbsp; ';;
						}
						
						$text[] = self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
						$text[] = self::$exportClass->encodeText((int)$_approp_cnt[$gid]);
						$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
						$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
						$text[] = self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin']);
						$text[] = self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax']);

						self::sumsTableRowNumberVertical($text);
						
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}
				
			}
		}
		self::tableEnd();
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				self::tableEnd();
			}		
		}
	}

	/** Izriše sumarnik v horizontalni obliki za multigrid
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	static function sumHorizontal($spid,$_from) {
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();
		$_clmn_cnt = count($spremenljivka['options']);

		# pri razvrščanju dodamo dva polja za povprečje in odklon
		$additional_field = false;
		$add_fld = 0;
		
		if ($spremenljivka['tip'] == 17 || $spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || ($spremenljivka['tip'] == 6 && $spremenljivka['skala'] != 1)) {
			$additional_field = true;
			$add_fld = 2;
		}

		# pri radiu in dropdown ne prikazujemo podvprašanj
		$_sub_question_col = 1;
		if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
			$_sub_question_col  = 0;
		}

		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);		
		
		//druga vrstica	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 750;
		$defw_part2 = 1800;
		$defw_part3 = 4600;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_answers']),20,'...') . $align . '\qc\cell';
		
		if ($additional_field){			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_valid']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_num_units']),20,'...').'\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_povprecje']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_odklon']),20,'...').'\qc\cell';
		}
		else{
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_valid']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_num_units']),20,'...').'\qc\cell';
		}
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					
		$_variables = $grid['variables'];
		
		//tretja vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 750;
		$defw_part2 = 1800;
		$defw_part3 = 4600;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '';
		$bold = '\b0';
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
			
		$defw_dynamic = round($defw_part3 / ($_clmn_cnt+1));	
		$count = 1;
		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 
				
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
				$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_label),20,'...').'\qc\cell';
				
				$count++;
			}
		}

		//suma
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_anl_suma1']),20,'...').'\qc\cell';
		
		if ($additional_field){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
		}
		else{
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
		}
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		//konec naslovnih vrstic
		

		#zlopamo skozi gride 
		$podtabela = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$cssBack = "anl_bck_desc_2 ";
			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				if ($variable['other'] != true) {

					$defw_full = 10300;
					$defw_part0 = 900;
					$defw_part = 750;
					$defw_part2 = 1800;
					$defw_part3 = 4600;
					
					//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
					$borderB = '\clbrdrb\brdrs\brdrw10';
					$borderT = '';		
					$align = '';
					$bold = '';
					
					// Ce gre za dvojno tabelo naredimo vrstico s naslovom podtabele
					if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
						
						// Če začnemo z drugo podtabelo izpišemo vrstico z naslovom
						if($podtabela != $grid['part']){
							
							$subtitle = $spremenljivka['double'][$grid['part']]['subtitle'];
							$subtitle = $subtitle == '' ? $lang['srv_grid_subtitle_def'].' '.$grid['part'] : $subtitle;
																			
							$tableHeader = '\trowd\trql\trrh400';
									
							$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
							$tableEnd = '\pard\intbl '.self::$exportClass->enkaEncode($subtitle) . $align . '\qc\cell';
							
							$tableEnd .= '\pard\intbl\row';
							
							self::$exportClass->rtf->MyRTF .= self::$exportClass->enkaEncode($tableHeader.$table.$tableEnd);
							
							$podtabela = $grid['part'];
						}
					}
					
					$tableHeader = '\trowd\trql\trrh400';
					
					$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
					$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($variable['variable']),20,'...') . $align . '\qc\cell';
					
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
					$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($variable['naslov']),20,'...') . $align . '\cell';
						
					
					# za odklon in povprečje				
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					
					$defw_dynamic = round($defw_part3 / ($_clmn_cnt+1));	
					$count = 1;
					if (count($spremenljivka['options']) > 0) {	
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;  
							
							$text = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] .' ('. SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') .')';
							
							$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
							$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($text),20,'...').'\qc\cell';
							
							$count++;
						}
					}
					//suma
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3);	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'].' ('.SurveyAnalysis::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')'),20,'...').'\qc\cell';
					
					if ($additional_field){						
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']),20,'...').'\qc\cell';
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']),20,'...').'\qc\cell';

						# odklon
						$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
						#standardna diviacija
						$div = 0;
						$sum_pow_xi_fi_avg  = 0;
						if (count($spremenljivka['options']) > 0) {
							foreach ( $spremenljivka['options'] as $xi => $kategorija) {
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$xi]['cnt'];
								$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
							}
						}
						$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
						
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';
					}
					else{
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']),20,'...').'\qc\cell';
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']),20,'...').'\qc\cell';
					}
					
					$tableEnd .= '\pard\intbl\row';
					
					self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);								
					
				} else {
					# immamo polje drugo
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}	
		}
		self::tableEnd();
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				self::tableEnd();
			}			
		}
	}
	
	/** Izriše tekstovne odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	static function sumTextVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);
			
		
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica	
		self::sumsTableHeader();		
		//konec naslovnih vrstic
		
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']); 
			if ($_variables_count > 0)
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['other'] != true) {
					# dodamo dodatne vrstice z labelami grida
					if ($_variables_count > 1) {
						self::outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if ($vAnswer['cnt'] > 0 && $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
								# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
								$textAnswerExceed = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								$options['isTextAnswer']=true;
								$options['textAnswerExceed'] = $textAnswerExceed;
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		self::tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				self::tableEnd();
			}		
		}
	}
	
	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	static function sumMultiText($spid,$_from,$displayTitle=false) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$maxAnswer);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		
		// Naslov tabele
		if($displayTitle){
			self::$exportClass->rtf->new_line(3);
			
			$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			$TITLE = self::$exportClass->rtf->bold(1) . $title . self::$exportClass->rtf->bold(0);	
				
			self::$exportClass->rtf->set_font("Arial Black", 8);
			self::$exportClass->rtf->add_text($TITLE, 'center');
			self::$exportClass->rtf->new_line();
			if($spremenljivka['tip'] == 2){
				self::$exportClass->rtf->set_font("Arial", 7);
				self::$exportClass->rtf->add_text($lang['srv_info_checkbox'], 'center');
				self::$exportClass->rtf->new_line();
			}
		}
		
		//prva vrstica
		if(self::$from != 'charts'){
			self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		}
		else{
			self::$exportClass->rtf->MyRTF .= "{\par";
		}
		
		//druga vrstica
		if(self::$from == 'charts'){
			if(isset(self::$sessionData['charts'][$spid]))
				$type = self::$sessionData['charts'][$spid]['type'];
			else
				$type = 0;
		}			
		if(self::$from != 'charts' || (self::$from == 'charts' && $type == 1)){			
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2600;
			
			//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';		
			$align = '';
			$bold = '\b';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
				
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_arguments']),20,'...').'\qc\cell';
					
			$tableEnd .= '\pard\intbl\row';
			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2600;
			$defw_part3 = 6800;
			
			//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';		
			$align = '';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
				
			$var_count = count($_row['variables']);
			$defw_dynamic = round($defw_part3 / $var_count);	
			$count = 1;
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
				
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_col['naslov']),20,'...').'\qc\cell';
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}			
							
				$count++;
			}
					
			$tableEnd .= '\pard\intbl\row';			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
								
			
			//podatkovne vrstice
			$count = 1;
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$defw_full = 10300;
				$defw_part0 = 900;
				$defw_part2 = 2600;
				$defw_part3 = 6800;
				
				//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
				$borderB = '\clbrdrb\brdrs\brdrw10';
				$borderT = '';		
				$align = '';
				$bold = '';
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
				$tableEnd = '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grid['variable']),20,'...') . $align . '\qc\cell';
				
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
				$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grid['naslov']),20,'...') . $align . '\cell';
								
				if ($_variables_count > 0) {
					
					$defw_dynamic = round($defw_part3 / $_variables_count);	
					$count = 1;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) { 
								$text = '';
								foreach ($_valid_answers AS $answer) {
									
									$_ans = $answer[$_sequence];

									if ($_ans != null && $_ans != '') {
										$text .=  $_ans.', \line ';
									}
								}
								
								$text = substr($text, 0, -8);
								$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
								$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->enkaEncode($text).'\qc\cell';
							}
							else {
								
								$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
								$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp;'),20,'...').'\qc\cell';
							}
							
							$count++;
						}
					}
				}
				
				$tableEnd .= '\pard\intbl\row';			
				self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			}			
		}
		
		// Ce je vec odgovorov kot jih prikazemo izpisemo na dnu izpisanih/vseh
		if($_all_valid_answers_cnt > $maxAnswer){	
			$tableHeader = '\trowd\trql\trrh400';				
			$table = '\clvertalc\cellx'.( 10300 );	
			$tableEnd = '\pard\intbl '.self::$exportClass->encodeText($maxAnswer.' / '.$_all_valid_answers_cnt). '\qr\cell';			
			$tableEnd .= '\pard\intbl\row';			
			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		self::tableEnd();
		

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
			
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					self::outputOtherAnswers($oAnswers);
					self::tableEnd();
				}
			}	
		}
	}
	
	/** Izriše sumarnik v horizontalni obliki za multi checbox
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	static function sumMultiHorizontalCheckbox($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		# tekst vprašanja
		
	
		/////////////////PRVA TABELA////////////////
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2200;
		$defw_part3 = 5000;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_answers']),20,'...') . $align . '\qc\cell';
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_valid']),20,'...').'\qc\cell';
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_num_units']),20,'...').'\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		//tretja vrstica
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2200;
		$defw_part3 = 5000;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b0';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
		
		$defw_dynamic = round($defw_part3 / count($_variables) );
		$count = 1;			
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
				$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($variable['naslov'].' ('.$variable['gr_id']. ')'),20,'...').'\qc\cell';
			}
			$count++;
		}
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		//podatkovne vrstice
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			$_cnt = 0;
			
			# vodoravna vrstice s podatki
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part = 1100;
			$defw_part2 = 2200;
			$defw_part3 = 5000;
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '\ql';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grids['variable']),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grids['naslov']),20,'...') . $align . '\cell';

			$_arguments = 0;
			$_max_appropriate = 0;
			$_max_cnt = 0;
			
			// prikaz frekvenc
			$defw_dynamic = round($defw_part3 / count($grids['variables']) );
			$count = 1;	
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;
				
				$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}
		
				if ($variable['other'] != true) {					
										
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;
					
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_cnt.' ('.SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'). ')'),20,'...').'\qc\cell';
				}
								
				$count++;
			}
			
			# veljavno 
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_max_cnt),20,'...').'\qc\cell';
			#ustrezno
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_max_appropriate),20,'...').'\qc\cell';
					
			$tableEnd .= '\pard\intbl\row';
			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		self::tableEnd();	
		/////////////////KONEC PRVE TABELE////////////////
		
	
		
		////////////DRUGA TABELA///////////////////
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_arguments']),20,'...') . $align . '\qc\cell';
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		//tretja vrstica
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b0';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
		
		$defw_dynamic = round($defw_part3 / (count($_variables)+1) );
		$count = 1;			
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
				$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($variable['naslov'].' ('.$variable['gr_id']. ')'),20,'...').'\qc\cell';
			}
			$count++;
		}
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_anl_suma1']),20,'...').'\qc\cell';
			
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		//vrstice s podatki
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			$_cnt = 0;
			
			# vodoravna vrstice s podatki
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2200;
			$defw_part3 = 7200;
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '\ql';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grids['variable']),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grids['naslov']),20,'...') . $align . '\cell';
			
			$_arguments = 0;
			$_max_appropriate = 0;
			$_max_cnt = 0;			
		
			// prikaz frekvenc
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;
				
				$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}
		
				if ($variable['other'] != true) {
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;  
				}
			}

			$defw_dynamic = round($defw_part3 / (count($_variables)+1) );
			$count = 1;				
			foreach ($grids['variables'] AS $vkey => $variable) {				
				if ($variable['other'] != true) {
					$_sequence = $variable['sequence'];
					$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					
					$_percent = ($_arguments > 0 ) ? $_cnt * 100 / $_arguments : 0;  
					
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_cnt.' ('.SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'). ')'),20,'...').'\qc\cell';

					$count++;
				}
			}
			

			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_arguments),20,'...').'\qc\cell';
				
			$tableEnd .= '\pard\intbl\row';
			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		self::tableEnd();
		///////////KONEC DRUGE TABELE//////////////
		
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				self::tableEnd();
			}			
		}
	}
	
	/** Izriše multi number odgovore. izpiše samo povprečja
	 * 
	 * @param unknown_type $spid
	 */
	static function sumMultiNumber($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		
		//prva vrstica			
		self::sumsTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($lang['srv_analiza_sums_average']),20,'...') . '\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2200;
			$defw_part3 = 7200;
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '\ql';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode('&nbsp; '),20,'...') . '\cell';
					
			$defw_dynamic = round($defw_part3 / count($_row['variables']) );
			$count = 1;			
			foreach ( $_row['variables'] AS $rid => $_col ) {
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($_col['naslov']),20,'...').'\qc\cell';
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				
				$count++;
			}
			
			$tableEnd .= '\pard\intbl\row';			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);

			
			$last = 0;

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$defw_full = 10300;
				$defw_part0 = 900;
				$defw_part2 = 2200;
				$defw_part3 = 7200;
				
				$borderB = '\clbrdrb\brdrs\brdrw10';
				$borderT = '';		
				$align = '\ql';
				$bold = '\b0';
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
				$tableEnd = '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grid['variable']),20,'...') . '\qc\cell';
				
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
				$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($grid['naslov']),20,'...') . '\cell';						
				
				if ($_variables_count > 0) {
					
					$defw_dynamic = round($defw_part3 / $_variables_count );
					$count = 1;	
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
							$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode(SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';
						}
						$count++;
					}
					$tableEnd .= '\pard\intbl\row';			
					self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				}	
			}
		}
		self::tableEnd();
	}
	
	static function sumsTableFirstLine($field1, $field2){
		global $lang;
		
		$defw_full = 10300;
		$defw_part = 900;
		$defw_part2 = 9400;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';		
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		self::$exportClass->rtf->MyRTF .= "{\par";
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($field1),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($field2),20,'...') . '\ql\cell';
					
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	static function sumsTableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '&nbsp; ';
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('borderB' => 1, 'bold' => 'B', 'align2' => 'C');
		
		self::sumsTableRow($naslov, $params);	
	}	
	static function sumsTableRow($arrayText, $arrayParams=0){
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1300;
		$defw_part2 = 4200;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align2 = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->enkaEncode($arrayText[1]) . $align2 . '\cell';
			
		for($i=0; $i<4; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	static function sumsTableRowVerticalCheckbox($arrayText, $arrayParams=0){
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1000;
		$defw_part2 = 2400;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
			
		for($i=0; $i<7; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	static function sumsTableRowNumberVertical($arrayText, $arrayParams=0){	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2800;
		
		//$borderB = ($arrayParams['borderB'] == 1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT'] == 1 ? '\clbrdrt\brdrs\brdrw10' : '');
		$borderS = '\clbrdrl\brdrs\brdrw10';
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		if($arrayText[1] == '&nbsp; '){
			$borderB = '';
			$borderT = '';
			$borderS = '';
		}
			
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT. $borderS . $borderB . $borderS. '\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
			
		for($i=0; $i<6; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	static function sumsTableRowHorizontal($arrayText, $arrayParams=0){
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2200;
		$defw_part3 = 3900;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[2]),20,'...') . $align . '\qc\cell';
			
		for($i=0; $i<3; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[$i+3]),20,'...').'\qc\cell';
		}
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	static function sumsTableRowMultiText($arrayText, $arrayParams=0){
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[2]),20,'...') . $align . '\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}		

	
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	public static function displayDescriptivesVariablaRow($spremenljivka,$grid,$variable=null) {
		global $lang;

		$_sequence = $variable['sequence'];	# id kolone z podatki
		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		$text = array();
			
		$text[] = self::$exportClass->encodeText($variable['variable']);
		$text[] = self::$exportClass->encodeText($variable['naslov']);
		
		#veljavno
		$text[] = self::$exportClass->encodeText((int)$_desc['validCnt']);
		
		#ustrezno
		$text[] = self::$exportClass->encodeText((int)$_desc['allCnt']);
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else if (isset($_desc['avg']) && $spremenljivka['tip'] == 2 && (int)$spremenljivka['skala'] == 1 )
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%'));
		else
			$text[] = self::$exportClass->encodeText('');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = self::$exportClass->encodeText('');
		
		$text[] = self::$exportClass->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = self::$exportClass->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
			
		self::descTableRow($text);	
	}
	
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	public static function displayDescriptivesSpremenljivkaRow($spid,$spremenljivka,$show_enota,$_sequence = null) {
		global $lang;

		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		$text = array();
			
		$text[] = self::$exportClass->encodeText($spremenljivka['variable']);
		$text[] = self::$exportClass->encodeText($spremenljivka['naslov']);
		
		#veljavno
		$text[] = self::$exportClass->encodeText((!$show_enota ? (int)$_desc['validCnt'] : '&nbsp; '));
		
		#ustrezno
		$text[] = self::$exportClass->encodeText((!$show_enota ? (int)$_desc['allCnt'] : '&nbsp; '));
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else
			$text[] = self::$exportClass->encodeText('&nbsp; ');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = self::$exportClass->encodeText('&nbsp; ');
			
		$text[] = self::$exportClass->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = self::$exportClass->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
			
		$params = array('bold' => 'B');
		
		self::descTableRow($text, $params);
	}

	static function descTableHeader(){
		global $lang;
		
		self::$exportClass->rtf->MyRTF .= "{\par";		
				
		$text = array();
		
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_variable']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_variable_text']);
		
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_m']);		
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_num_units']);			
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_odklon']);			
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_max']);
					
		$params = array('borderT' => 1, 'borderB' => 1, 'bold' => 'B', 'align2' => 'C');
		
		self::descTableRow($text, $params);	
	}
	static function descTableRow($arrayText, $arrayParams=array()){
	
		$defw_full = 10300;
		$defw_part = 1100;
		$defw_part2 = 2600;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->enkaEncode($arrayText[1]). $align . '\cell';
			
		for($i=0; $i<6; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( ($i+2) * $defw_part + $defw_part2);
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}

	
	/** Izriše frekvence v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	public static function frequencyVertical($spid, $displayTitle=false) {
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		
		# koliko zapisov prikažemo naenkrat
		$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
		
		
		// Naslov tabele
		if($displayTitle){
		
			self::$exportClass->rtf->new_line(3);
			
			$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			$TITLE = self::$exportClass->rtf->bold(1) . $title . self::$exportClass->rtf->bold(0);	
				
			self::$exportClass->rtf->set_font("Arial Black", 8);
			self::$exportClass->rtf->add_text($TITLE, 'center');
			self::$exportClass->rtf->new_line();
			if($spremenljivka['tip'] == 2){
				self::$exportClass->rtf->set_font("Arial", 7);
				self::$exportClass->rtf->add_text($lang['srv_info_checkbox'], 'center');
				self::$exportClass->rtf->new_line();
			}
		}
		
		if(self::$from == 'charts'){
		
			if(isset(self::$sessionData['charts'][$spid]))
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = array('type' => 0, 'show_legend' => 0);
		
			if($settings['type'] == 1)
				self::chartTableHeader();
			else
				self::$exportClass->rtf->MyRTF .= "{\par";
		}
		else{
			//prva vrstica			
			self::freqTableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
			
			//druga vrstica
			self::freqTableHeader();
		}
		// konec naslovne vrstice
		
		
		$_answersOther = array();
		
		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# izpišemo vlejavne odgovore
		$_current_grid = null;
		$maxCounter = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
			
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){

				$_sequence = $variable['sequence'];	# id kolone z podatki
				if (($variable['text'] != true && $variable['other'] != true) 
				|| (in_array($spremenljivka['tip'],array(4,8,21,22)))){
					# dodamo ime podvariable
					//if ($_variables_count > 1 && in_array($spremenljivka['tip'],array(2,6,7,16,17,18,19,20,21))) {
					if ($inline_legenda) {
						# ali rišemo dvojno črto med grupami
						if ( $_current_grid != $gid && $_current_grid !== null && $spremenljivka['tip'] != 6) {
							$options['doubleTop'] = true;
							$_current_grid = $gid;
						} else {
							$options['doubleTop'] = false;
							$_current_grid = $gid;
						}
						self::outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$options);
					}
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							if ($counter < $maxAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21))) { // text, number, datum, mtext, mnumber, text* 
										$options['isTextAnswer'] = true;
									} else {
										$options['isTextAnswer'] = false;
									}
									$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								}
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				
					// Dobimo stevilo vseh odgovorov (ce ne prikazemo vseh izpisemo na dnu)
					$maxCounter = (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > $maxCounter) ? count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) : $maxCounter;					
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		
		// Ce je vec odgovorov kot jih prikazemo izpisemo na dnu izpisanih/vseh
		if($maxCounter > $maxAnswer){			
			if(self::$from == 'charts'){	
				$tableHeader = '\trowd\trql\trrh400';
				$table .= '\clvertalc\cellx'.( 9500 );	
				$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->encodeText($maxAnswer.' / '.$maxCounter) .'\qr\cell';		
				$tableEnd .= '\pard\intbl\row';
			}
			else{
				$tableHeader = '\trowd\trql\trrh400';
				$table .= '\clvertalc\cellx'.( 8700 );	
				$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->encodeText($maxAnswer.' / '.$maxCounter) .'\qr\cell';		
				$tableEnd .= '\pard\intbl\row';
			}	
			
			self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		self::tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
			
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					self::outputOtherAnswers($oAnswers);
					self::tableEnd();
				}
			}
		}
	}
	
	public static function outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$_options = array()) {
		global $lang;
		
		$text = array();
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$text[] = self::$exportClass->encodeText($variable['variable']);
		
		$text[] = self::$exportClass->encodeText($variable['naslov']);
		
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';

		if(self::$from != 'charts')
			self::freqTableRow($text);
		else{
			if(isset(self::$sessionData['charts'][$spid]))
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = array('type' => 0, 'show_legend' => 0);

			$arrayParams = array('fill' => $fill, 'bold' => 'B', 'align2' => 'L', 'type' => $settings['type']);
			self::chartTableRow($text, $arrayParams);
		}
	}
	
	static function freqTableFirstLine($field1, $field2){
		global $lang;
		
		$defw_full = 10300;
		$defw_part = 900;
		$defw_part2 = 9400;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';		
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		self::$exportClass->rtf->MyRTF .= "{\par";
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($field1),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($field2),20,'...') . '\cell';
					
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
	}
	static function freqTableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '&nbsp; ';
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('borderB' => 1, 'bold' => 'B', 'align2' => 'C');
		
		self::freqTableRow($naslov, $params);	
	}
	static function freqTableRow($arrayText, $arrayParams=array()){
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1300;
		$defw_part2 = 4200;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[1]),20,'...') .$align.'\cell';
			
		for($i=0; $i<4; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.self::$exportClass->snippet(self::$exportClass->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
		$tableEnd .= '\pard\intbl\row';
		
		self::$exportClass->rtf->MyRTF .= self::$exportClass->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
	
	
	static function outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,&$_kumulativa,$_options=array()) {
		global $lang;
		
		$text = array();
		
		# opcije		
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.SurveyAnalysis::$cssColors['0_' . ($counter & 1)];

		$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid; 
		
		# ali presegamo število prikazanih vrstic, takrat v zadnji prikazani dodamo link več.. ostale vrstice pa skrijemo
		if ($options['textAnswerExceed'] == true) {
			if ($counter == TEXT_ANSWER_LIMIT ) {
				# link za več
				$show_more = '<div id="valid_row_togle_'.$_sequence.'" class="floatRight blue pointer" onclick="showHidenTextRow(\''.$_sequence.'\');return false;">(več...)</div>'.NEW_LINE;
			} elseif ($counter > TEXT_ANSWER_LIMIT ) {
				$hide_row = ' hidden';
				$_exceed = true;
			}			
		}
		
		
		$text[] = '&nbsp; ';

		$addText = (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
		$text[] = self::$exportClass->encodeText($vkey.$addText);

		$text[] = self::$exportClass->encodeText((int)$vAnswer['cnt']);
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		if(self::$from == 'charts'){		
		
			if(isset(self::$sessionData['charts'][$spid]))
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = array('type' => 0, 'show_legend' => 0);
		
			$align = $settings['show_legend'] == 0 ? 'C' : 'L';
			$arrayParams = array('fill' => $fill, 'align2' => $align, 'type' => $settings['type']);

			self::chartTableRow($text, $arrayParams);
		}
		else
			self::sumsTableRow($text);
		
		$counter++;
		return $counter;
	}
	
	static function outputSumaValidAnswerVertical($counter,$_sequence,$spid,$_options=array()) {
		global $lang;
		
		$text = array();	
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$currentMissingProfile === 2) ? TRUE : FALSE;
		
		$_sufix = '';

		$text[] = self::$exportClass->encodeText($lang['srv_anl_valid']);
		$text[] = self::$exportClass->encodeText($lang['srv_anl_suma1']);
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0);
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		$text[] = '&nbsp; ';

		if(self::$from == 'charts'){
			if(isset(self::$sessionData['charts'][$spid]))
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = array('type' => 0, 'show_legend' => 0);
		
			$arrayParams = array('fill' => $fill, 'align2' => 'L', 'type' => $settings['type']);
			
			if($settings['type'] == 1)
				self::chartTableRow($text, $arrayParams);
		}
		else
			self::sumsTableRow($text);
		
		$counter++;
		return $counter;		
	}
	
	static function outputInvalidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array()) {
		global $lang;	
		
		$text = array();
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
 
		$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;		
		if($_Z_MV){	
			$text[] = '&nbsp; ';
			
			$text[] = self::$exportClass->encodeText($vkey.' (' . $vAnswer['text'].')');
			//echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.SurveyAnalysis::formatNumber($_invalid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').'</div>'.NEW_LINE;
			//echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>'.NEW_LINE;
			
			$text[] = self::$exportClass->encodeText((int)$vAnswer['cnt']);

			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = '&nbsp; ';
			$text[] = '&nbsp; ';
			
			if(self::$from == 'charts'){
				//self::chartTableRow($text);
			}
			else
				self::sumsTableRow($text);
		}
		
		$counter++;
		return $counter;
	}
	
	static function outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$_options = array()) {
		global $lang;
			
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.SurveyAnalysis::$cssColors['text_' . ($counter & 1)];
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		if(!$_brez_MV){
			$text = array();
			
			$text[] = self::$exportClass->encodeText($lang['srv_anl_missing']);	
			
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt']);			
			
			$answer['cnt'] =  SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
			$text[] = self::$exportClass->encodeText((int)$answer['cnt']);
			
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '';
			$text[] = '';
			
			if(self::$from == 'charts'){
				//self::chartTableRow($text);
			}
			else
				self::sumsTableRow($text);
		}
			
		$counter++;
		return $counter;
	}
	
	static function outputSumaVertical($counter,$_sequence,$spid, $_options = array()) {
		global $lang;
		
		# opcije			
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$cssBck = ' '.SurveyAnalysis::$cssColors['0_' .($counter & 1)];

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		if(!$_brez_MV){
		
			$text = array();
		
			$text[] = '';
			$text[] = self::$exportClass->encodeText($lang['srv_anl_suma2']);
			$text[] = self::$exportClass->encodeText((SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0));	
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '';	
			$text[] = '';
			
			if(self::$from == 'charts'){
				//self::chartTableRow($text, $arrayParams);
			}
			else
				self::sumsTableRow($text);
		}	
	}

	/** izpišemo tabelo z tekstovnimi odgovori drugo
	 * 
	 * @param $skey
	 * @param $oAnswers
	 * @param $spid
	 */
	public static function outputOtherAnswers($oAnswers) {
		global $lang;
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];
		
		
		if(self::$from == 'charts'){
			self::chartTableHeader();
		}
		else{
			//prva vrstica			
			self::sumsTableFirstLine($_variable['variable'], SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )');	
			
			//druga vrstica
			self::sumsTableHeader();
		}
		// konec naslovne vrstice				

		$counter = 1;
		$_kumulativa = 0;
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
	}
	
	/** za multi grid tekstovne vrstice doda vrstico z labeliami grida
	 * 
	 * @param $gkey
	 * @param $gAnswer
	 * @param $spid
	 * @param $_options
	 */
	public static function outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$_options=array()) {
 		
		$text = array();
					
		$text[] = self::$exportClass->encodeText($variable['variable']);
		$text[] = self::$exportClass->encodeText(($grid['naslov'] != '' ? $grid['naslov']. '&nbsp;-&nbsp;' : '').$variable['naslov']);
		
		$text[] = '';
		$text[] = '';
		$text[] = '';
		$text[] = '';
		
		self::sumsTableRow($text);
		
		$counter++;
		return $counter;	
	}
	
	
	
	static function tableEnd(){	
		self::$exportClass->rtf->MyRTF .= "}";
		self::$exportClass->rtf->new_line(1);
	}
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	static function snippet($text,$length=64,$tail="...")
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
	
}

?>