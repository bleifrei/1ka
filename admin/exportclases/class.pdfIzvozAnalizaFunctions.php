<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.enka.pdf.php');

class PdfIzvozAnalizaFunctions {

	public static $anketa;
	public static $user_id;
	public static $from;
	
	public static $exportClass;		// instanca razreda v katerem izrisujemo PDF
	public static $analizaClass;	// instanca razreda kjer imamo analize (crosstab, means, ttest...)
	
	private static $sessionData;	// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
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
					
					//izracun sirine ene celice
					if($cols == 1)
						$singleWidth = 110;
					elseif($cols == 2)
						$singleWidth = round( 150 / $cols );
					elseif($cols > 0)
						$singleWidth = round( 170 / $cols );
						
					/*if($cols > 0)
						$singleWidth = ($cols > 2) ? round( 170 / $cols ) : round( 150 / $cols );*/


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
					
					self::$exportClass->pdf->setFont('','B','6');
					
					/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($sub_q1), 170);
					$firstHeight = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );*/
					$firstHeight = self::getCellHeight(self::$exportClass->encodeText($sub_q1), $singleWidth * count($crosstabs['options1']));
					
					//prva vrstica
					self::$exportClass->pdf->MultiCell(25, $firstHeight, self::$exportClass->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell(20, $firstHeight, self::$exportClass->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell($singleWidth * count($crosstabs['options1']), $firstHeight, self::$exportClass->encodeText($sub_q1), 1, 'C', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell($singleWidth, $firstHeight, self::$exportClass->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);

					
					// Izracun visine vrstice z naslovi gridov
					$height = 0;
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							#ime variable
							$text = $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't') {
								$text .= ' ( '.$ckey1.' )';
							}								
							
							/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($text), $singleWidth);
							$height = ($height < 4.7 + ($linecount-1)*3.7) ? (4.7 + ($linecount-1)*3.7) : $height;*/
							$height = ($height > self::getCellHeight(self::$exportClass->encodeText($text), $singleWidth)) ? $height : self::getCellHeight(self::$exportClass->encodeText($text), $singleWidth);
						}
					}	
					
					//druga vrstica		
					self::$exportClass->pdf->setFont('','','6');
					self::$exportClass->pdf->MultiCell(25, $height, self::$exportClass->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell(20, $height, self::$exportClass->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
					
					if (count($crosstabs['options1']) > 0 ) {
						foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
							#ime variable
							$text = $crossVariabla['naslov'];
							# če ni tekstovni odgovor dodamo key
							if ($crossVariabla['type'] != 't') {
								$text .= ' ( '.$ckey1.' )';
							}								
							self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
						}
					}
						
					self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($lang['srv_analiza_crosstab_skupaj']), 1, 'C', 0, 1, 0 ,0, true);
					
					
					//izracun visine ene celice
					if($rowSpan == 1)
						$height = 8;
					elseif($rowSpan == 2)
						$height = 10;
					else
						$height = 15;
					
					//VMESNE VRSTICE
					$cntY = 1;
					if (count($crosstabs['options2']) > 0) {
						
						//POSAMEZNA VMESNA VRSTICA
						foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
							
							if( $cntY == 1 )
								$border = 'TLR';
							elseif( $cntY == $rows )
								$border = 'BLR';
							else
								$border = 'LR';			
							
							if ( $cntY == ceil($rows/2) || $rows == 1 ) {
								# ime variable
								self::$exportClass->pdf->setFont('','B','6');
								self::$exportClass->pdf->MultiCell(25, $height, self::$exportClass->encodeText($sub_q2), $border, 'C', 0, 0, 0 ,0, true);
								self::$exportClass->pdf->setFont('','','6');
							}
							else{
								self::$exportClass->pdf->MultiCell(25, $height, self::$exportClass->encodeText(''), $border, 'C', 0, 0, 0 ,0, true);
							}					
							$cntY++;	
							
							$text = $crossVariabla2['naslov'];
							$text = self::$exportClass->snippet(self::$exportClass->encodeText($text), 25);
							if ($crossVariabla2['type'] !== 't') {
								$text .= ' ('.$ckey2.')';
							}							
							self::$exportClass->pdf->MultiCell(20, $height, $text, 1, 'C', 0, 0, 0 ,0, true);

							//del vrstice z vsebino
							self::$exportClass->pdf->setFont('','','5');
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								
								$data = array();
								
								if (self::$analizaClass->crossChk0) {
									# frekvence crostabov
									$data[] = ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;						
								}
									
								if (self::$analizaClass->crossChk1) {
									#procent vrstica
									$data[] = self::$exportClass->formatNumber(self::$analizaClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
								}
								if (self::$analizaClass->crossChk2) {
									#procent stolpec
									$data[] =  self::$exportClass->formatNumber(self::$analizaClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
								}
								if (self::$analizaClass->crossChk3) {
									#procent skupni
									$data[] = self::$exportClass->formatNumber(self::$analizaClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
								}

								# residuali
								if (self::$analizaClass->crossChkEC) {
									$data[] = self::$exportClass->formatNumber($crosstabs['exC'][$ckey1][$ckey2], 3, '');
								}
								if (self::$analizaClass->crossChkRE) {
									$data[] = self::$exportClass->formatNumber($crosstabs['res'][$ckey1][$ckey2], 3, '');
								}
								if (self::$analizaClass->crossChkSR) {
									$data[] = self::$exportClass->formatNumber($crosstabs['stR'][$ckey1][$ckey2], 3, '');
								}
								if (self::$analizaClass->crossChkAR) {
									$data[] = self::$exportClass->formatNumber($crosstabs['adR'][$ckey1][$ckey2], 3, '');
								}					
								
								self::displayCrosstabsCell($data, $singleWidth, $rowSpan, self::$analizaClass->crossChk0, $numColumnPercent, $numColumnResidual, self::$exportClass);						
							}					
							
							//se zadnji stolpec - vedno risemo
							$data = array();
							
							if (self::$analizaClass->crossChk0) {
								# suma po vrsticah
								$data[] = (int)$crosstabs['sumaVrstica'][$ckey2];
							}
							if (self::$analizaClass->crossChk1 || self::$analizaClass->crossChk2 || self::$analizaClass->crossChk3) {
								# suma po vrsticah v procentih
								if (self::$analizaClass->crossChk1) {
									$data[] = self::$exportClass->formatNumber(100, 2, '%');
								}
								if (self::$analizaClass->crossChk2) {
									$data[] = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
								if (self::$analizaClass->crossChk3) {
									$data[] = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
							}
							
							self::displayCrosstabsCell($data, $singleWidth, $rowSpan, self::$analizaClass->crossChk0, $numColumnPercent, 0);
							self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);
							
							self::$exportClass->pdf->setFont('','','6');
						}
					}
							
					
					// skupni sestevki po stolpcih - ZADNJA VRSTICA
					//popravimo stevilo vrstic (brez residualov)
					if(self::$analizaClass->crossChkEC || self::$analizaClass->crossChkRE || self::$analizaClass->crossChkSR || self::$analizaClass->crossChkAR)
						$rowSpan--;
						
					//izracun visine ene celice
					if($rowSpan == 1)
						$height = 8;
					elseif($rowSpan == 2)
						$height = 10;
					else
						$height = 15;
						
					self::$exportClass->pdf->MultiCell(25, $height, self::$exportClass->encodeText(''), 'T', 'C', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell(20, $height, self::$exportClass->encodeText($lang['srv_analiza_crosstab_skupaj']), 1, 'C', 0, 0, 0 ,0, true);
					
					self::$exportClass->pdf->setFont('','','5');	
					
					if (count($crosstabs['options1']) > 0){
						
						foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
							
							$data = array();
							
							# prikazujemo eno od treh možnosti					
							if (self::$analizaClass->crossChk0) {
								# suma po stolpcih
								$data[] = (int)$crosstabs['sumaStolpec'][$ckey1];
							}					
							if (self::$analizaClass->crossChk1 || self::$analizaClass->crossChk2 || self::$analizaClass->crossChk3) {
								# suma po stolpcih v procentih
								if (self::$analizaClass->crossChk1) {
									$data[] = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
								if (self::$analizaClass->crossChk2) {
									$data[] = self::$exportClass->formatNumber(100, 2, '%');
								}
								if (self::$analizaClass->crossChk3)
								{
									$data[] = self::$exportClass->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
							}

							self::displayCrosstabsCell($data, $singleWidth, $rowSpan, self::$analizaClass->crossChk0, $numColumnPercent, 0);
						}
						
						# zadnja celica z skupno sumo
						$data = array();
						
						if (self::$analizaClass->crossChk0) {
							# skupna suma
							$data[] = (int)$crosstabs['sumaSkupna'];
						}
						if (self::$analizaClass->crossChk1 || self::$analizaClass->crossChk2 || self::$analizaClass->crossChk3) {
							# suma po stolpcih v procentih
							if (self::$analizaClass->crossChk1) {
								$data[] = self::$exportClass->formatNumber(100, 2, '%');
							}
							if (self::$analizaClass->crossChk2) {
								$data[] = self::$exportClass->formatNumber(100, 2, '%');
							}
							if (self::$analizaClass->crossChk3) {
								$data[] = self::$exportClass->formatNumber(100, 2, '%');
							}
						}
						
						self::displayCrosstabsCell($data, $singleWidth, $rowSpan, self::$analizaClass->crossChk0, $numColumnPercent, 0);
					}

					self::$exportClass->pdf->setFont('','','6');
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
		
		self::$exportClass->pdf->setDrawColor(128, 128, 128);
		
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
		self::$exportClass->pdf->setDrawColor(128, 128, 128);
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

		$size = getimagesize('pChart/Cache/'.$imgName);
		$ratio = ($size[0] / 800) < 1 ? 1 : ($size[0] / 800);
		$height = $size[1] / 5;

		if(self::$exportClass->pdf->getY() + ($height/$ratio) > 250)
		{	
			self::$exportClass->pdf->AddPage();
		}
	
		// Naslov posameznega grafa
		/*self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(10, 5,'', 0, 'C', 0, 0, 0 ,0, true);
		if($settings['type'] == 1 || $settings['type'] == 4){
			self::$exportClass->pdf->MultiCell(60, 5, self::$exportClass->encodeText(self::$exportClass->crosstabVars[0]), 0, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 5, self::$exportClass->encodeText('/'), 0, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(60, 5, self::$exportClass->encodeText(self::$exportClass->crosstabVars[1]), 0, 'C', 0, 1, 0 ,0, true);
		}
		else{
			self::$exportClass->pdf->MultiCell(140, 5, self::$exportClass->encodeText(self::$exportClass->crosstabVars[0]), 0, 'C', 0, 1, 0 ,0, true);
		}*/
		self::$exportClass->pdf->setFont('','','6');
		
		self::$exportClass->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
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
		
		// visina prve vrstice
		$firstHeight = 0;
		for ($i = 0; $i < $cols; $i++) {	
			
			$label1 = self::$analizaClass->getSpremenljivkaTitle($_means[$i]['v1']);
			
			/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($label1), $singleWidth*2);
			$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );
			$firstHeight = ($height > $firstHeight) ? $height : $firstHeight;*/
			$firstHeight = ($height > self::getCellHeight(self::$exportClass->encodeText($label1))) ? $height : self::getCellHeight(self::$exportClass->encodeText($label1), $singleWidth*2);
		}
		
		
		// prva vrstica
		self::$exportClass->pdf->setFont('','B','6');
		
		$label2 = self::$analizaClass->getSpremenljivkaTitle($_means[0]['v2']);
		self::$exportClass->pdf->MultiCell(80, $firstHeight, self::$exportClass->encodeText($label2), 'TLR', 'C', 0, 0, 0 ,0, true);
		
		for ($i = 0; $i < $cols; $i++) {

			$label1 = self::$analizaClass->getSpremenljivkaTitle($_means[$i]['v1']);
			self::$exportClass->pdf->MultiCell($singleWidth*2, $firstHeight, self::$exportClass->encodeText($label1), 1, 'C', 0, 0, 0 ,0, true);
		}
		self::$exportClass->pdf->MultiCell(1, $firstHeight, self::$exportClass->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
		
		self::$exportClass->pdf->setFont('','','6');
		
		// druga vrstica
		self::$exportClass->pdf->MultiCell(80, 7, self::$exportClass->encodeText(''), 'BLR', 'C', 0, 0, 0 ,0, true);
		
		for ($i = 0; $i < $cols; $i++) {

			self::$exportClass->pdf->MultiCell($singleWidth, 7, self::$exportClass->encodeText($lang['srv_means_label']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell($singleWidth, 7, self::$exportClass->encodeText($lang['srv_means_label4']), 1, 'C', 0, 0, 0 ,0, true);
		}
		self::$exportClass->pdf->MultiCell(1, 7, self::$exportClass->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);

		
		// vrstice s podatki
		if (count($_means[0]['options']) > 0) {
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {
								
				$variabla = $crossVariabla2['naslov'];
				# če ni tekstovni odgovor dodamo key
				if ($crossVariabla2['type'] !== 't' ) {
					if ($crossVariabla2['vr_id'] == null) {
						$variabla .= ' ( '.$ckey2.' )';
					} else {
						$variabla .= ' ( '.$crossVariabla2['vr_id'].' )';
					}
				}
				self::$exportClass->pdf->MultiCell(80, 7, self::$exportClass->encodeText($variabla), 1, 'C', 0, 0, 0 ,0, true);

				# celice z vsebino
				for ($i = 0; $i < $cols; $i++) {
					
					self::$exportClass->pdf->MultiCell($singleWidth, 7, self::$exportClass->encodeText(self::$analizaClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell($singleWidth, 7, self::$exportClass->encodeText((int)$_means[$i]['sumaVrstica'][$ckey2]), 1, 'C', 0, 0, 0 ,0, true);
				}
				self::$exportClass->pdf->MultiCell(1, 7, self::$exportClass->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
			}
		}
		
		// SKUPAJ
		self::$exportClass->pdf->MultiCell(80, 7, self::$exportClass->encodeText($lang['srv_means_label3']), 1, 'C', 0, 0, 0 ,0, true);

		for ($i = 0; $i < $cols; $i++) {

			self::$exportClass->pdf->MultiCell($singleWidth, 7, self::$exportClass->encodeText(self::$analizaClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell($singleWidth, 7, self::$exportClass->encodeText((int)$_means[$i]['sumaSkupna']), 1, 'C', 0, 0, 0 ,0, true);
		}
		self::$exportClass->pdf->MultiCell(1, 7, self::$exportClass->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
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

		$size = getimagesize('pChart/Cache/'.$imgName);
		$ratio = ($size[0] / 800) < 1 ? 1 : ($size[0] / 800);
		$height = $size[1] / 4;

		if(self::$exportClass->pdf->getY() + ($height/$ratio) > 250)
		{	
			self::$exportClass->pdf->AddPage();
		}
	
		self::$exportClass->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
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
			$sprLabel2 =  trim(str_replace('&nbsp;','',self::$sessionData['ttest'][self::$anketa]['label2']));
			$label1 = self::$analizaClass->getVariableLabels(self::$sessionData['ttest']['sub_conditions'][0]);
			$label2 = self::$analizaClass->getVariableLabels(self::$sessionData['ttest']['sub_conditions'][1]);
			
			
			// prva vrstica
			/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($sprLabel1), 160);
			$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );*/
			$height = self::getCellHeight(self::$exportClass->encodeText($sprLabel1), 160);
			
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(80, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(180, $height, self::$exportClass->encodeText($sprLabel1), 1, 'C', 0, 1, 0 ,0, true);		
			
				
			// druga vrstica
			/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($sprLabel2), 100);
			$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );*/
			$height = self::getCellHeight(self::$exportClass->encodeText($sprLabel2), 100);
			
			self::$exportClass->pdf->MultiCell(80, $height, self::$exportClass->encodeText($sprLabel2), 'BLR', 'C', 0, 0, 0 ,0, true);		
			
			self::$exportClass->pdf->setFont('','','6');
			
			self::$exportClass->pdf->MultiCell(20, $height, 'n', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 'x', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 's²', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 'se(x)', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, '±1,96×se(x)', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 'd', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 'se(d)', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 'Sig.', 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, $height, 't', 1, 'C', 0, 1, 0 ,0, true);


			// vrstici s podatki
			self::$exportClass->pdf->MultiCell(80, 7, self::$exportClass->encodeText($label1), 1, 'C', 0, 0, 0 ,0, true);		
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[1]['n'], 0), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[1]['x'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[1]['s2'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[1]['se'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[1]['margin'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, '', 'TLR', 'C', 0, 1, 0 ,0, true);
			
			self::$exportClass->pdf->MultiCell(80, 7, self::$exportClass->encodeText($label2), 1, 'C', 0, 0, 0 ,0, true);		
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[2]['n'], 0), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[2]['x'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[2]['s2'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[2]['se'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest[2]['margin'], 3), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest['d'], 3), 'BLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest['sed'], 3), 'BLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest['sig'], 3), 'BLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(20, 7, self::$exportClass->formatNumber($ttest['t'], 3), 'BLR', 'C', 0, 1, 0 ,0, true);
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

		$size = getimagesize('pChart/Cache/'.$imgName);
		$ratio = ($size[0] / 800) < 1 ? 1 : ($size[0] / 800);
		$height = $size[1] / 4;

		if(self::$exportClass->pdf->getY() + ($height/$ratio) > 250)
		{	
			self::$exportClass->pdf->AddPage();
		}	
	
		self::$exportClass->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);		
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
			
			$singleWidth = floor(200 / $colspan);
			
			
			// PRVA VRSTICA
			$height = self::getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')', $singleWidth*$colspan);
			
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(60, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell($singleWidth*$colspan, $height, self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
			
			
			// DRUGA VRSTICA
			$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
			$height = self::getCellHeight($text, $singleWidth*$colspan);

			// najprej loopamo da dobimo visino celice
			if ($tip != 1 && $tip != 3) {
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$text = $variable['naslov'].' ('.$variable['variable'].')';
						$height = (self::getCellHeight($text, $singleWidth) > $height) ? self::getCellHeight($text, $singleWidth) : $height;
					}
				}
			} 
			else if (count($spremenljivka['options']) < 15) {
				foreach ($spremenljivka['options'] AS $okey => $option) {
					$text = $option.' ('.$okey.')';
					$height = (self::getCellHeight($text, $singleWidth) > $height) ? self::getCellHeight($text, $singleWidth) : $height;
				}	
			}

			// se izrisemo celice...
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(60, $height, self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'BLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->setFont('','','6');
			
			if ($tip != 1 && $tip != 3) {
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($variable['naslov'].' ('.$variable['variable'].')'), 1, 'C', 0, 0, 0 ,0, true);
					}
				}
				self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	
			} 
			else if (count($spremenljivka['options']) < 15) {
				foreach ($spremenljivka['options'] AS $okey => $option) {
					self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($option.' ('.$okey.')'), 1, 'C', 0, 0, 0 ,0, true);
				}
				self::$exportClass->pdf->MultiCell($singleWidth, $height, 'povprečje', 1, 'C', 0, 1, 0 ,0, true);	
			}
			
			
			// VRSTICE S PODATKI
			foreach ($frequencys AS $fkey => $fkeyFrequency) {

				$height = self::getCellHeight($forSpremenljivka['options'][$fkey], 60);
				self::$exportClass->pdf->MultiCell(60, $height, self::$exportClass->encodeText($forSpremenljivka['options'][$fkey]), 1, 'C', 0, 0, 0 ,0, true);

				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						if ($variable['other'] != 1) {
							$sequence = $variable['sequence'];
							if (($tip == 1 || $tip == 3) && count($spremenljivka['options']) < 15) {
								foreach ($spremenljivka['options'] AS $okey => $option) {
									self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']), 1, 'C', 0, 0, 0 ,0, true);
								}
							}
							self::$exportClass->pdf->MultiCell($singleWidth, $height, SurveyAnalysis::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
						}
					}
				}
				self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	
			}
		}
		
		else {
			$rowspan = 2;
			$colspan = $spremenljivka['grids'][0]['cnt_vars'];
			$singleWidth = floor(200 / $colspan);
			# za multicheck razdelimo na grupe - skupine
			foreach ($frequencys AS $fkey => $frequency) {
				
				self::$exportClass->pdf->setFont('','B','6');
				self::$exportClass->pdf->MultiCell(200, 5, self::$exportClass->encodeText('Tabela za: ('.$forSpremenljivka['variable'].') = '.$forSpremenljivka['options'][$fkey]), 0, 'L', 0, 1, 0 ,0, true);
				
				
				$text = $spremenljivka['naslov'].' ('.$spremenljivka['variable'].')';
				$height = self::getCellHeight($text, 260);
				self::$exportClass->pdf->MultiCell(260, $height, self::$exportClass->encodeText($text), 1, 'C', 0, 1, 0 ,0, true);	

				self::$exportClass->pdf->setFont('','','6');
				
				
				foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {					
					$height = (self::getCellHeight($variable['naslov'], $singleWidth) > $height) ? self::getCellHeight($variable['naslov'], $singleWidth) : $height;
				}
				
				self::$exportClass->pdf->MultiCell(60, $height, '', 1, 'C', 0, 0, 0 ,0, true);
				foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {					
					self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($variable['naslov']), 1, 'C', 0, 0, 0 ,0, true);
				}
				self::$exportClass->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
				
				
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {

					$text = '('.$grid['variable'].') '.$grid['naslov'];
					$height = self::getCellHeight($text, 60);
					self::$exportClass->pdf->MultiCell(60, $height,  self::$exportClass->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);

					foreach ($grid['variables'] AS $vkey => $variable) {
						$sequence = $variable['sequence'];

						self::$exportClass->pdf->MultiCell($singleWidth, $height, SurveyAnalysis::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
					}
					self::$exportClass->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
				}
			}
		}
	}
	
	// Izrisemo BREAK NUMBER TABELO
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
			
			$singleWidth = floor(200 / $colspan);
			
			
			// PRVA VRSTICA
			$height = self::getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')', $singleWidth*$colspan);
			
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(60, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell($singleWidth*$colspan, $height, self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
			
			
			// DRUGA VRSTICA
			$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
			$height = self::getCellHeight($text, $singleWidth*$colspan);

			// najprej loopamo da dobimo visino celice
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					$text = $variable['naslov'].' ('.$variable['variable'].')';
					$height = (self::getCellHeight($text, $singleWidth) > $height) ? self::getCellHeight($text, $singleWidth) : $height;
				}
			}
				
			// se izrisemo celice...
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(60, $height, self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'LR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->setFont('','','6');
			
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($variable['naslov'].' ('.$variable['variable'].')'), 1, 'C', 0, 0, 0 ,0, true);
				}
			}
			self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	


			// TRETJA VRSTICA
			self::$exportClass->pdf->MultiCell(60, 5, '', 'BLR', 'C', 0, 0, 0 ,0, true);
			
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					self::$exportClass->pdf->MultiCell($singleWidth, 5, self::$exportClass->encodeText('Povprečje'), 1, 'C', 0, 0, 0 ,0, true);
				}
			}
			self::$exportClass->pdf->MultiCell(1, 5, '', 0, 'C', 0, 1, 0 ,0, true);	
			
			
			// VRSTICE S PODATKI
			foreach ($frequencys AS $fkey => $fkeyFrequency) {
				
				
				$height = self::getCellHeight($forSpremenljivka['options'][$fkey], 60);
				self::$exportClass->pdf->MultiCell(60, $height, self::$exportClass->encodeText($forSpremenljivka['options'][$fkey]), 1, 'C', 0, 0, 0 ,0, true);

				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						if ($variable['other'] != 1) {
							$sequence = $variable['sequence'];
							self::$exportClass->pdf->MultiCell($singleWidth, $height, SurveyAnalysis::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
						
							$totalMeans[$sequence] += (self::$analizaClass->getMeansFromKey($fkeyFrequency[$sequence])*(int)$frequencys[$fkey][$sequence]['validCnt']);
							$totalFreq[$sequence]+= (int)$frequencys[$fkey][$sequence]['validCnt'];
						}
					}
				}
				self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	
			}
					
			// dodamo še skupno sumo in povprečje
			self::$exportClass->pdf->MultiCell(60, 5, self::$exportClass->encodeText('Skupaj'), 1, 'C', 0, 0, 0 ,0, true);
			
			self::$exportClass->pdf->setFont('','B','6');
			foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				foreach ($grid['variables'] AS $vkey => $variable) {
					
					$sequence = $variable['sequence'];
					if ($variable['other'] != 1) {
						#povprečja
						$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
						self::$exportClass->pdf->MultiCell($singleWidth, $height, SurveyAnalysis::formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);
					}	
				}	
			}
			self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);			
			self::$exportClass->pdf->setFont('','','6');
		}
		
		else {
			$rowspan = 3;
			$colspan = count($spremenljivka['grids'][0]['variables']);
			$singleWidth = floor(200 / $colspan);
			
			
			# za multinumber izrisemo samo izbrano podtabelo
			$gkey = $spremenljivka['break_sub_table']['key'];
			$grid = $spremenljivka['grids'][$gkey];
			
	
			// PRVA VRSTICA
			$height = self::getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')', $singleWidth*$colspan);
			
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(60, $height, '', 'TLR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell($singleWidth*$colspan, $height, self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
			
			
			// DRUGA VRSTICA
			$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
			$height = self::getCellHeight($text, $singleWidth*$colspan);

			foreach ($grid['variables'] AS $vkey => $variable) {
				$text = $variable['naslov'].' ('.$variable['variable'].')';
				$height = (self::getCellHeight($text, $singleWidth) > $height) ? self::getCellHeight($text, $singleWidth) : $height;
			}
				
			// se izrisemo celice...
			self::$exportClass->pdf->setFont('','B','6');
			self::$exportClass->pdf->MultiCell(60, $height, self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'LR', 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->setFont('','','6');
			
			foreach ($grid['variables'] AS $vkey => $variable) {
				$text = $variable['naslov'].' ('.$variable['variable'].')';
				self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
			}
			self::$exportClass->pdf->MultiCell(1, $height, '', 0, 'C', 0, 1, 0 ,0, true);	


			// TRETJA VRSTICA
			self::$exportClass->pdf->MultiCell(60, 5, '', 'BLR', 'C', 0, 0, 0 ,0, true);
			
			foreach ($grid['variables'] AS $vkey => $variable) {
				self::$exportClass->pdf->MultiCell($singleWidth, 5, self::$exportClass->encodeText('Povprečje'), 1, 'C', 0, 0, 0 ,0, true);
			}
			self::$exportClass->pdf->MultiCell(1, 5, '', 0, 'C', 0, 1, 0 ,0, true);	
							
			
			// VRSTICE Z VSEBINO
			foreach ($forSpremenljivka['options'] AS $okey => $option) {
				
				$height = self::getCellHeight($option, 60);
				self::$exportClass->pdf->MultiCell(60, $height,  self::$exportClass->encodeText($option), 1, 'C', 0, 0, 0 ,0, true);
				
				foreach ($grid['variables'] AS $vkey => $variable) {
					$sequence = $variable['sequence'];
					
					#povprečje
					self::$exportClass->pdf->MultiCell($singleWidth, $height, SurveyAnalysis::formatNumber($means[$okey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);

					$totalMeans[$sequence] += ($means[$okey][$sequence]*(int)$frequencys[$okey][$sequence]['validCnt']);
					$totalFreq[$sequence]+= (int)$frequencys[$okey][$sequence]['validCnt'];	
				}
				self::$exportClass->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
			}
			
			// dodamo še skupno sumo in povprečje
			self::$exportClass->pdf->MultiCell(60, 5, self::$exportClass->encodeText('Skupaj'), 1, 'C', 0, 0, 0 ,0, true);
			
			self::$exportClass->pdf->setFont('','B','6');
			foreach ($grid['variables'] AS $vkey => $variable) {
				$sequence = $variable['sequence'];
				if ($variable['other'] != 1) {
						#povprečja
						$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
						self::$exportClass->pdf->MultiCell($singleWidth, 5, SurveyAnalysis::formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''), 1, 'C', 0, 0, 0 ,0, true);	
				}	
			}
			self::$exportClass->pdf->MultiCell(1, 5, '', 0, 'C', 0, 1, 0 ,0, true);			
			self::$exportClass->pdf->setFont('','','6');
		}
	}
	
	// Izrisemo BREAK TEXT TABELO
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

		$rowspan = 2;
		$colspan = count($spremenljivka['grids'][0]['variables']);
			
		$singleWidth = floor(200 / $colspan);
			
		# za multinumber izrisemo samo izbrano podtabelo
		$gkey = $spremenljivka['break_sub_table']['key'];
		$grid = $spremenljivka['grids'][$gkey];

		
		// PRVA VRSTICA
		$height1 = self::getCellHeight($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')', $singleWidth*$colspan);
		
		self::$exportClass->pdf->setFont('','B','6');
		self::$exportClass->pdf->MultiCell(60, $height1, '', 'TLR', 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell($singleWidth*$colspan, $height1, self::$exportClass->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'), 1, 'C', 0, 1, 0 ,0, true);
		
		
		// DRUGA VRSTICA
		$text = $forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')';
		$height2 = self::getCellHeight($text, $singleWidth*$colspan);

		foreach ($grid['variables'] AS $vkey => $variable) {
			$text = $variable['naslov'].' ('.$variable['variable'].')';
			$height2 = (self::getCellHeight($text, $singleWidth) > $height2) ? self::getCellHeight($text, $singleWidth) : $height2;
		}
			
		// se izrisemo celice...
		self::$exportClass->pdf->setFont('','B','6');
		self::$exportClass->pdf->setY(self::$exportClass->pdf->getY() - $height1);
		self::$exportClass->pdf->MultiCell(60, $height1+$height2, self::$exportClass->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'), 'BLR', 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->setXY(self::$exportClass->pdf->getX(), self::$exportClass->pdf->getY() + $height1);
		self::$exportClass->pdf->setFont('','','6');
		
		foreach ($grid['variables'] AS $vkey => $variable) {
			$text = $variable['naslov'].' ('.$variable['variable'].')';
			self::$exportClass->pdf->MultiCell($singleWidth, $height2, self::$exportClass->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
		}
		self::$exportClass->pdf->MultiCell(1, $height2, '', 0, 'C', 0, 1, 0 ,0, true);	

		
		// VRSTICE Z VSEBINO
		foreach ($forSpremenljivka['options'] AS $okey => $option) {
			
			// Izracunamo visino najvisje celice
			$height = self::getCellHeight($option, 60);
			foreach ($grid['variables'] AS $vkey => $variable) {
				$sequence = $variable['sequence'];
				$text = "";
				if (count($texts[$okey][$sequence]) > 0) {				
					$tempHeight = 0;
					foreach ($texts[$okey][$sequence] AS $ky => $units) {
						$text .= $units['text']."\n";
					}
					$text = substr($text,0,-2);
					$height = (self::getCellHeight($text, $singleWidth) > $height) ? self::getCellHeight($text, $singleWidth) : $height;
				}
			}
			
			self::$exportClass->pdf->MultiCell(60, $height,  self::$exportClass->encodeText($option), 1, 'C', 0, 0, 0 ,0, true);
			
			foreach ($grid['variables'] AS $vkey => $variable) {
				$sequence = $variable['sequence'];
				if (count($texts[$okey][$sequence]) > 0) {
					$text = "";
					foreach ($texts[$okey][$sequence] AS $ky => $units) {
						$text .= $units['text']."\n";
					}
					$text = substr($text,0,-2);
					self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($text), 1, 'C', 0, 0, 0 ,0, true);
				}
				else
					self::$exportClass->pdf->MultiCell($singleWidth, $height, '', 1, 'C', 0, 0, 0 ,0, true);
			}
			self::$exportClass->pdf->MultiCell(1, $height,'', 0, 'C', 0, 1, 0 ,0, true);
		}
	}
	
	// Izrisemo BREAK GRAF
	public static function displayBreakChart($forSpr,$frequencys,$spremenljivka){
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
		$height = $size[1] / 5;
	
		self::$exportClass->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);		
	}
	
	
	// Izrisujemo NAVADEN GRAF
	public static function displayChart($spid, $type=0, $fromCharts=false){
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
		
		
		// Izrisemo naslov (v creportu ne, ker imamo drugacne naslove)
		if($fromCharts){
		
			$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			
			if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
				$title .= ' (n = '.$DataSet->GetNumerus().')';
			}
			
			// Preverimo ce prebija slika stran
			if(isset(self::$sessionData['charts'][$spid])){
			
				if(count(SurveyAnalysis::$_LOOPS) == 0)
					$settings = self::$sessionData['charts'][$spid];
				else
					$settings = self::$sessionData['charts'][$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']];

				$imgName = $settings['name'];
				$size = getimagesize('pChart/Cache/'.$imgName);
				$ratio = ($size[0] / 800) < 1 ? 1 : ($size[0] / 800);
				$height = $size[1] / 5;

				if(self::$exportClass->pdf->getY() + ($height/$ratio) > 250)
				{	
					self::$exportClass->pdf->AddPage();
				}
			}
				
			self::$exportClass->pdf->setFont('','b','6');
			self::$exportClass->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
			if($spremenljivka['tip'] == 2){
				self::$exportClass->pdf->setFont('','','5');
				self::$exportClass->pdf->MultiCell(165, 1, $lang['srv_info_checkbox'], 0, 'C', 0, 1, 0 ,0, true);
			}
			self::$exportClass->pdf->setFont('','','6');
		}
	
		// IZRIS GRAFA		
		self::$exportClass->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
		
		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){				
					self::outputOtherAnswers($oAnswers);
				}
			}
		}
		
		
		// Dodamo space (v creportu ne, ker imamo drugacen izpis)
		if($fromCharts)
			self::$exportClass->pdf->setY(self::$exportClass->pdf->getY() + 10);
	}
	
	static function chartTableRow($arrayText, $arrayParams=array()){
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 90);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
			
		$fill = (isset($arrayParams['fill'])) ? $arrayParams['fill'] : 0;

		if($arrayParams['type'] == 1){
			self::$exportClass->pdf->MultiCell(19, $height, '', 0, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(100, $height, self::$exportClass->encodeText($arrayText[1]), 1, $arrayParams['align2'], $fill, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(27, $height, $arrayText[2], 1, 'C', $fill, 1, 0 ,0, true);
		}
		else{
			self::$exportClass->pdf->MultiCell(19, $height, '', 0, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(127, $height, self::$exportClass->encodeText($arrayText[1]), 1, $arrayParams['align2'], $fill, 1, 0 ,0, true);
		}
	}
	
	static function chartTableHeader($type=0){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '';
		$naslov[] = self::encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = self::encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = self::encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = self::encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = self::encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('border' => 'TB', 'bold' => 'B', 'align2' => 'C', 'type' => 1);		
		
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
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica
		self::sumsTableHeader();
		self::$exportClass->pdf->setFont('','','6');
		
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
					$counter = 0;
					$_kumulativa = 0;

					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							// za povprečje
							$xi = $vkey;
							$fi = $vAnswer['cnt'];
							
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;

							if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
								
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

		# izpišemo še odklon in povprečje
		if ($show_valid_percent == 1 && SurveyAnalysis::$_HEADERS[$spid]['skala'] != 1) {
			
			self::$exportClass->pdf->ln(1);
			
			$height = 5;
			
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(90, $height, self::$exportClass->encodeText(''), 0, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($lang['srv_analiza_opisne_povprecje']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($lang['srv_analiza_opisne_odklon']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 1, 0 ,0, true);
		
			/*$text = array();
			
			$text[] = '';
			$text[] = '';

			$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_povprecje']);
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($avg,NUM_DIGIT_AVERAGE,''));
			
			$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_odklon']);
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($div,NUM_DIGIT_AVERAGE,''));
			
			self::$exportClass->tableRow($text);*/
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
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
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(50, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(80, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_units']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(32, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);			
		
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
		
		self::$exportClass->pdf->setFont('','','6');
		//konec naslovnih vrstic
		
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] as $gid => $grid) {
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				if ($variable['other'] != 1) {
					$_sequence = $variable['sequence'];
					$cssBack = "anl_bck_desc_2 ".($vid == 0 && $gid != 0 ? 'anl_double_bt ' : '');
					
					$text = array();
		
					$text[] = self::$exportClass->encodeText($variable['variable']);
					$text[] = self::$exportClass->encodeText($variable['naslov']);				
					
					// Frekvence
					$text[] = self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
				
					// Veljavno
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

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
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

		self::$exportClass->pdf->setFont('','b','6');
		
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);
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
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		$text = array();
		
		$text[] = '';
		
		if ($show_enota) {
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']);;
			} else {
				$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_variable_text']);
			}
		} else {
			$text[] = '';
		}
		
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_m']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_num_units']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_odklon']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = self::$exportClass->encodeText($lang['srv_analiza_opisne_max']);

		self::sumsTableRowNumberVertical($text);
		
		self::$exportClass->pdf->setFont('','','6');
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
							$text[] = '';
					
						if ($show_enota) {
							$text[] = self::$exportClass->encodeText((count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov']);
						} else {
							$text[] = '';;
						}
						
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
						$text[] = (int)$_approp_cnt[$gid];
						$text[] = SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						$text[] = SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'];
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'];

						self::sumsTableRowNumberVertical($text);
						
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}
				
			}
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
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
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(30, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(72, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_answers']), 1, 'C', 0, 0, 0 ,0, true);
		
		if ($additional_field){
			self::$exportClass->pdf->MultiCell(15, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_valid']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(15, 5, self::$exportClass->encodeText($lang['srv_analiza_num_units']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(15, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_povprecje']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(15, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_odklon']), 1, 'C', 0, 1, 0 ,0, true);
		}
		else{
			self::$exportClass->pdf->MultiCell(30, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_valid']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(30, 5, self::$exportClass->encodeText($lang['srv_analiza_num_units']), 1, 'C', 0, 1, 0 ,0, true);
		}
		
		$_variables = $grid['variables'];
		
		//tretja vrstica
		$count = 0;
		$height_title = 0;
		$text = array();
		if (count($spremenljivka['options']) > 0) {
		
			$singleWidth = round(57 / count($spremenljivka['options']));
			
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 		
				$text[] = $_label;
				
				$height_title = ($height_title < self::getCellHeight($_label, $singleWidth)) ? self::getCellHeight($_label, $singleWidth) : $height_title;				
				$count++;
			}
		}	
		
		self::$exportClass->pdf->MultiCell(18, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(30, $height_title, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
		self::sumsDynamicCells($text, $count, 57, $height_title);
		
		self::$exportClass->pdf->MultiCell(15, $height_title, self::$exportClass->encodeText($lang['srv_anl_suma1']), 1, 'C', 0, 0, 0 ,0, true);
		if ($additional_field){
			self::$exportClass->pdf->MultiCell(15, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(15, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(15, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(15, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 1, 0 ,0, true);
		}
		else{
			self::$exportClass->pdf->MultiCell(30, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(30, $height_title, self::$exportClass->encodeText(''), 1, 'L', 0, 1, 0 ,0, true);
		}
		
		self::$exportClass->pdf->setFont('','','6');
		//konec naslovnih vrstic
		

		#zlopamo skozi gride 
		$podtabela = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				if ($variable['other'] != true) {

					// Ce gre za dvojno tabelo naredimo vrstico s naslovom podtabele
					if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
						
						// Če začnemo z drugo podtabelo izpišemo vrstico z naslovom
						if($podtabela != $grid['part']){
							
							$subtitle = $spremenljivka['double'][$grid['part']]['subtitle'];
							$subtitle = $subtitle == '' ? $lang['srv_grid_subtitle_def'].' '.$grid['part'] : $subtitle;
										
							self::$exportClass->pdf->setFont('','b','6');
							self::$exportClass->pdf->MultiCell(180, $height_title, self::$exportClass->encodeText($subtitle), 1, 'C', 0, 1, 0 ,0, true);
							self::$exportClass->pdf->setFont('','','6');
							
							$podtabela = $grid['part'];
						}
					}
				
					if($variable['naslov'] == '')
						$variable['naslov'] = '';
						
					/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($variable['naslov']), 30);
					$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
					$height = self::getCellHeight(self::$exportClass->encodeText($variable['naslov']), 30);
					$height = ($height < 8 ? 8 : $height);
					
					//ce smo na prelomu strani
					if( (self::$exportClass->pdf->getY() + $height) > 270){					
						self::$exportClass->drawLine();			
						self::$exportClass->pdf->AddPage('P');
						$arrayParams['border'] .= 'T';
					}
					
					self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($variable['variable']), 1, 'L', 0, 0, 0 ,0, true);
					self::$exportClass->pdf->MultiCell(30, $height, self::$exportClass->encodeText($variable['naslov']), 1, 'C', 0, 0, 0 ,0, true);
					
					# za odklon in povprečje				
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					
					$count = 0;
					$text = array();					
					if (count($spremenljivka['options']) > 0) {
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;  

							$text[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'].' ('.SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')';
													
							$count++;
						}
					}
					self::sumsDynamicCells($text, $count, 57, $height);
					
					// suma
					self::$exportClass->pdf->MultiCell(15, $height, self::$exportClass->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'].' ('.SurveyAnalysis::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')'), 1, 'C', 0, 0, 0 ,0, true);
					
					// zamenjano veljavni ustrezni
					if ($additional_field){
						self::$exportClass->pdf->MultiCell(15, $height, self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']), 1, 'C', 0, 0, 0 ,0, true);
						self::$exportClass->pdf->MultiCell(15, $height, self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']), 1, 'C', 0, 0, 0 ,0, true);
					}
					else{
						self::$exportClass->pdf->MultiCell(30, $height, self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']), 1, 'C', 0, 0, 0 ,0, true);
						self::$exportClass->pdf->MultiCell(30, $height, self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']), 1, 'C', 0, 1, 0 ,0, true);
					}
					
					# za odklon in povprečje
					if ($additional_field){
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
						
						self::$exportClass->pdf->MultiCell(15, $height, self::$exportClass->encodeText(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 0, 0 ,0, true);
						self::$exportClass->pdf->MultiCell(15, $height, self::$exportClass->encodeText(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')), 1, 'C', 0, 1, 0 ,0, true);				
					}
				} 
				else {
					# immamo polje drugo
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}	
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
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
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica	
		self::sumsTableHeader();		
		self::$exportClass->pdf->setFont('','','6');
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

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
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
		
			$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			
			self::$exportClass->pdf->setFont('','b','6');
			self::$exportClass->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
			if($spremenljivka['tip'] == 2){
				self::$exportClass->pdf->setFont('','','5');
				self::$exportClass->pdf->MultiCell(165, 1, $lang['srv_info_checkbox'], 0, 'C', 0, 1, 0 ,0, true);
			}
			self::$exportClass->pdf->setFont('','','6');
		}
		
		//prva vrstica
		if(self::$from != 'charts'){		
			self::$exportClass->pdf->setFont('','b','6');
			self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		}
		
		//druga vrstica
		if(self::$from == 'charts'){
			if(isset(self::$sessionData['charts'][$spid]))
				$type = self::$sessionData['charts'][$spid]['type'];
			else
				$type = 0;
		}			
		if(self::$from != 'charts' || (self::$from == 'charts' && $type == 1)){			
			self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(54, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(108, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);
		}

		
		self::$exportClass->pdf->setFont('','','6');
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$height = 0;
			
			$count = 0;
			$text = array();
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$text[] = $_col['naslov'];
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			
				$count++;
			}
			
			// Testiramo visino vrstice glede na najdaljsi text
			foreach ($text AS $string){
				$singleWidth = ($count > 0) ? round(108 / $count): 108;					
				$height = (self::getCellHeight($string, $singleWidth) > $height) ? self::getCellHeight($string, $singleWidth) : $height;
			}
			
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(54, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
			self::sumsDynamicCells($text, $count, 108, $height);
			self::$exportClass->pdf->ln($height);

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);	
				$height = 0;
								
				if ($_variables_count > 0) {
										
					$count = 0;
					$text = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) {
								$text2 = '';
								foreach ($_valid_answers AS $answer) {
									$_ans = $answer[$_sequence];

									if ($_ans != null && $_ans != '') {
										$text2 .= $_ans.', ';
									}
								}
								$text[] = substr($text2, 0, -2);
							}
							else {
								$text[] = '&nbsp;';
							}
							
							$count++;
						}
						
					}		
				}
				
				// Testiramo visino vrstice glede na najdaljsi text
				foreach ($text AS $string){
					$singleWidth = ($count > 0) ? round(108 / $count): 108;					
					$height = (self::getCellHeight($string, $singleWidth) > $height) ? self::getCellHeight($string, $singleWidth) : $height;
				}
				
				self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($grid['variable']), 1, 'C', 0, 0, 0 ,0, true);
				self::$exportClass->pdf->MultiCell(54, $height, self::$exportClass->encodeText($grid['naslov']), 1, 'C', 0, 0, 0 ,0, true);
				
				self::sumsDynamicCells($text, $count, 108, $height);	
				self::$exportClass->pdf->ln($height);
			}			
		}
		
		// Ce je vec odgovorov kot jih prikazemo izpisemo na dnu izpisanih/vseh
		if($_all_valid_answers_cnt > $maxAnswer)
			self::$exportClass->pdf->MultiCell(180, 5, self::$exportClass->encodeText($maxAnswer.' / '.$_all_valid_answers_cnt), 0, 'R', 0, 1, 0 ,0, true);
		

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
			
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					self::outputOtherAnswers($oAnswers);
				}
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
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);		
		
		//druga vrstica		
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(54, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(108, 5, self::$exportClass->encodeText($lang['srv_analiza_sums_average']), 1, 'C', 0, 1, 0 ,0, true);
		
		self::$exportClass->pdf->setFont('','','6');
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			self::$exportClass->pdf->MultiCell(18, 13, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(54, 13, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
			
			$count = 0;
			$text = array();
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$text[] = $_col['naslov'];
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			
				$count++;
			}
			self::sumsDynamicCells($text, $count, 108, 13);
			self::$exportClass->pdf->ln(5);

			$last = 0;

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($grid['variable']), 1, 'C', 0, 0, 0 ,0, true);
				self::$exportClass->pdf->MultiCell(54, 5, self::$exportClass->encodeText($grid['naslov']), 1, 'C', 0, 0, 0 ,0, true);
								
				
				if ($_variables_count > 0) {
					
					$count = 0;
					$text = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$text[] = SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						}
						$count++;
					}
					self::sumsDynamicCells($text, $count, 108, 5);	
					self::$exportClass->pdf->ln(5);
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
		
	
		//prva vrstica			
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);
		
		//druga vrstica		
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(54, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_answers']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_valid']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($lang['srv_analiza_num_units']), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(54, 5, self::$exportClass->encodeText($lang['srv_analiza_opisne_arguments']), 1, 'C', 0, 1, 0 ,0, true);
		
		self::$exportClass->pdf->setFont('','','6');
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		
		//tretja vrstica
		$count = 0;
		$height = 0;
		$text = array();
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$text[] = $variable['naslov'].' ('.$variable['gr_id']. ')';
				
				$singleWidth = round(54 / (count($_variables) + 1));
				$height = ($height < self::getCellHeight($variable['naslov'].' ('.$variable['gr_id']. ')', $singleWidth)) ? self::getCellHeight($variable['naslov'].' ('.$variable['gr_id']. ')', $singleWidth) : $height;
			}
			$count++;
		}
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);	
		
		self::sumsDynamicCells($text, $count, 54, $height);

		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
				
		$count = 0;
		$text = array();
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$text[] = $variable['naslov'].' ('.$variable['gr_id']. ')';
			}
			$count++;
		}
		self::sumsDynamicCells($text, $count, 44, $height);
		
		self::$exportClass->pdf->MultiCell(10, $height, self::$exportClass->encodeText($lang['srv_anl_suma1']), 1, 'C', 0, 1, 0 ,0, true);
		
		
		//vrstice s podatki
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			
			$_cnt = 0;
			$height = self::getCellHeight(self::$exportClass->encodeText($grids['naslov']), 18);
			$height = ($height < 8 ? 8 : $height);
			
			# vodoravna vrstice s podatki
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($grids['variable']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($grids['naslov']), 1, 'C', 0, 0, 0 ,0, true);

			$_arguments = 0;

			$_max_appropriate = 0;
			$_max_cnt = 0;
			// prikaz frekvenc
			$count = 0;
			$text = array();
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
					
					$text[] = $_cnt . ' (' . SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') . ')';
					$count++;
				}
				
			}
			self::sumsDynamicCells($text, $count, 54, $height);
			
			# veljavno 
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($_max_cnt), 1, 'C', 0, 0, 0 ,0, true);
			#ustrezno
			self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($_max_appropriate), 1, 'C', 0, 0, 0 ,0, true);
			
			
			$count = 0;
			$text = array();
			foreach ($grids['variables'] AS $vkey => $variable) {
				if ($variable['other'] != true) {
					$_sequence = $variable['sequence'];
					$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					
					$_percent = ($_arguments > 0 ) ? $_cnt * 100 / $_arguments : 0;  
					
					$text[] = $_cnt . ' (' . SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') . ')';
					$count++;
				}			
			}
			self::sumsDynamicCells($text, $count, 44, $height);

			self::$exportClass->pdf->MultiCell(10, $height, self::$exportClass->encodeText($_arguments), 1, 'C', 0, 1, 0 ,0, true);
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}

	static function sumsTableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '';
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('border' => 'TB', 'bold' => 'B', 'align2' => 'C');
		
		self::sumsTableRow($naslov, $params);	
	}	
	static function sumsTableRow($arrayText, $arrayParams=array()){
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 90);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(90, $height, self::$exportClass->encodeText($arrayText[1]), 1, $arrayParams['align2'], 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, $arrayText[2], 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[5]), 1, 'C', 0, 1, 0 ,0, true);
	}
	static function sumsTableRowVerticalCheckbox($arrayText, $arrayParams=array()){
	
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 54);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 54);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(50, $height, self::$exportClass->encodeText($arrayText[1]), 1, 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[5]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[6]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[7]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[8]), 1, 'C', 0, 1, 0 ,0, true);
	}
	static function sumsTableRowNumberVertical($arrayText, $arrayParams=array()){
	
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		for($i=2; $i<8; $i++){
			if( $arrayText[$i] == '' )
				$arrayText[$i] = '0';
		}
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 54);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 54);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}

		$arrayText[1] == '' ? $arrayParams['border'] = 0 : $arrayParams['border'] = 1;
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(54, $height, self::$exportClass->encodeText($arrayText[1]), $arrayParams['border'], 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[5]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[6]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[7]), 1, 'C', 0, 1, 0 ,0, true);
	}
	static function sumsTableRowHorizontal($arrayText, $arrayParams=array()){
			
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 30);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(30, $height, self::$exportClass->encodeText($arrayText[1]), 1, 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(78, $height, self::$exportClass->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[5]), 1, 'C', 0, 1, 0 ,0, true);
	}
	static function sumsTableRowMultiText($arrayText, $arrayParams=array()){
	
		if($arrayText[1] == '')
			$arrayText[1] = '';
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 30);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(30, $height, self::$exportClass->encodeText($arrayText[1]), 1, 'L', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(132, $height, self::$exportClass->encodeText($arrayText[2]), 1, 'C', 0, 1, 0 ,0, true);
	}		
	
	//izrisemo dinamicne celice (podamo sirino, stevilo celic in vsebino)
	static function sumsDynamicCells($arrayText, $count, $width, $height, $arrayParams=array()){

		if($count > 0){
			$singleWidth = round($width / $count);
			$lastWidth = $width - (($count-1)*$singleWidth);
		}
		else{
			$singleWidth = $width;
			$lastWidth = $width;
		}
				
		if($arrayText[0] == '')
			$arrayText[0] = '';
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 1 : $height = 4.7 + ($linecount-1)*3.3;*/
		
		for($i=0; $i<$count-1; $i++){
			if($arrayText[$i] == '')
				$arrayText[$i] = '';
		
			self::$exportClass->pdf->MultiCell($singleWidth, $height, self::$exportClass->encodeText($arrayText[$i]), 1, 'C', 0, 0, 0 ,0, true);
		}
		
		//zadnje polje izrisemo druge sirine ker se drugace zaradi zaokrozevanja tabela porusi	
		$lastWidth = ($lastWidth < 4) ? 4 : $lastWidth;		
		if($count > 0)
			self::$exportClass->pdf->MultiCell($lastWidth, $height, self::$exportClass->encodeText($arrayText[$count-1]), 1, 'C', 0, 0, 0 ,0, true);
		else
			self::$exportClass->pdf->MultiCell($lastWidth, $height, self::$exportClass->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);
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
		$text[] = self::$exportClass->encodeText((!$show_enota ? (int)$_desc['validCnt'] : ''));
		
		#ustrezno
		$text[] = self::$exportClass->encodeText((!$show_enota ? (int)$_desc['allCnt'] : ''));
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else
			$text[] = self::$exportClass->encodeText('');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = self::$exportClass->encodeText('');
		
		$text[] = self::$exportClass->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		$text[] = self::$exportClass->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
		
		self::$exportClass->pdf->setFont('','b','6');
		self::descTableRow($text);
		self::$exportClass->pdf->setFont('','','6');
	}

	static function descTableRow($arrayText, $arrayParams=array()){
	
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
		
					
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 52);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 60);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		}
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(60, $height, self::$exportClass->encodeText($arrayText[1]), 1, $arrayParams['align2'], 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[5]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[6]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(16, $height, self::$exportClass->encodeText($arrayText[7]), 1, 'C', 0, 1, 0 ,0, true);
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
		$num_show_records = SurveyAnalysis::getNumRecords();
		
		
		// Naslov tabele
		if($displayTitle){
		
			$stevilcenje = (self::$exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			
			self::$exportClass->pdf->setFont('','b','6');
			self::$exportClass->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
			if($spremenljivka['tip'] == 2){
				self::$exportClass->pdf->setFont('','','5');
				self::$exportClass->pdf->MultiCell(165, 1, $lang['srv_info_checkbox'], 0, 'C', 0, 1, 0 ,0, true);
			}
			self::$exportClass->pdf->setFont('','','6');
		}

		// tabela za graf
		if(self::$from == 'charts'){
		
			if(isset(self::$sessionData['charts'][$spid]))
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = array('type' => 0, 'show_legend' => 0);
		
			if($settings['type'] == 1)
				self::chartTableHeader();
			
			self::$exportClass->pdf->setFont('','','6');		
		}
		else{
			//prva vrstica
			self::$exportClass->pdf->setFont('','b','6');
			self::$exportClass->pdf->MultiCell(18, 5, self::$exportClass->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
			self::$exportClass->pdf->MultiCell(162, 5, self::$exportClass->encodeText($spremenljivka['naslov']), 1, 'C', 0, 1, 0 ,0, true);		
			
			//druga vrstica
			self::freqTableHeader();
			self::$exportClass->pdf->setFont('','','6');		
		}
		// konec naslovne vrstice
		
		
		$_answersOther = array();
		
		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# izpišemo vlejavne odgovore
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
						self::outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if ($vAnswer['cnt'] > 0 && $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
								if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21))) { // text, number, datum, mtext, mnumber, text* 
									$options['isTextAnswer'] = true;
									# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
									$options['textAnswerExceed'] = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								} else {
									$options['isTextAnswer'] = false;
									$options['textAnswerExceed'] = false;
								}
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

					// Dobimo stevilo vseh odgovorov (ce ne prikazemo vseh izpisemo na dnu)
					$maxCounter = (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > $maxCounter) ? count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) : $maxCounter;					
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}

		// Ce je vec odgovorov kot jih prikazemo izpisemo na dnu izpisanih/vseh
		if($maxCounter > $maxAnswer)
			self::$exportClass->pdf->MultiCell(146, 5, self::$exportClass->encodeText($maxAnswer.' / '.$maxCounter), 0, 'R', 0, 1, 0 ,0, true);
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					self::outputOtherAnswers($oAnswers);
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
		
		$text[] = '';
		$text[] = '';
		$text[] = '';
		$text[] = '';
		
		if(self::$from != 'charts'){
			self::freqTableRow($text);
		}
		else{
			if(isset(self::$sessionData['charts'][$spid]))
				$settings = self::$sessionData['charts'][$spid];
			else
				$settings = array('type' => 0, 'show_legend' => 0);
		
			$arrayParams = array('fill' => $fill, 'align2' => 'L', 'type' => $settings['type']);
			self::$exportClass->pdf->setFont('','B','6');
			self::chartTableRow($text, $arrayParams);
			self::$exportClass->pdf->setFont('','','6');
		}
	}
	
	static function freqTableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '';
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = self::$exportClass->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('border' => 'TB', 'bold' => 'B', 'align2' => 'C');
		
		self::freqTableRow($naslov, $params);	
	}	
	static function freqTableRow($arrayText, $params=array()){
			
		/*$linecount = self::$exportClass->pdf->getNumLines(self::$exportClass->encodeText($arrayText[1]), 60);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = self::getCellHeight(self::$exportClass->encodeText($arrayText[1]), 90);
		
		//ce smo na prelomu strani
		if( (self::$exportClass->pdf->getY() + $height) > 270){					
			self::$exportClass->drawLine();			
			self::$exportClass->pdf->AddPage('P');
			$params['border'] .= 'T';
		}
		
		if($params['align2'] != 'C')
			$params['align2'] = 'L';
		
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[0]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(90, $height, self::$exportClass->encodeText($arrayText[1]), 1, $params['align2'], 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[2]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[3]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[4]), 1, 'C', 0, 0, 0 ,0, true);
		self::$exportClass->pdf->MultiCell(18, $height, self::$exportClass->encodeText($arrayText[5]), 1, 'C', 0, 1, 0 ,0, true);
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
		
		$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid; 
				
		//if ($counter < TEXT_MAX_ANSWER_LIMIT) {
	 		$text[] = '';

			$addText = (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
			$text[] = self::$exportClass->encodeText('  '.$vkey.$addText);

			$text[] = (int)$vAnswer['cnt'];
			
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));

		/*} elseif ($counter == TEXT_MAX_ANSWER_LIMIT ) {
	 		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'">';
	 		echo '<td class="anl_bl anl_ac anl_br gray anl_dash_bt anl_dash_bb" colspan="'.(6+(int)SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']+((int)SurveyAnalysis::$_SHOW_LEGENDA*2)).'"> . . . Prikazujemo samo prvih '.TEXT_MAX_ANSWER_LIMIT.' veljavnih odgovorov!</td>';
			echo '</tr>';
		}*/
		
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

		$_brez_MV = ((int)SurveyAnalysis::$currentMissingProfile == 2) ? TRUE : FALSE;
		
		$_sufix = '';

		$text[] = self::$exportClass->encodeText($lang['srv_anl_valid']);
		$text[] = self::$exportClass->encodeText($lang['srv_anl_suma1']);
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0);
		
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		$text[] = '';

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
 
		$_sufix = '';
		
		$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;		
		if($_Z_MV){
			$text[] = '';
			
			$text[] = self::$exportClass->encodeText($vkey.' (' . $vAnswer['text'].')');
			//echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.SurveyAnalysis::formatNumber($_invalid, NUM_DIGIT_PERCENT, '%').'</div>'.NEW_LINE;
			//echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>'.NEW_LINE;
			
			$text[] = self::$exportClass->encodeText((int)$vAnswer['cnt']);

			$text[] = self::$exportClass->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = '';
			$text[] = '';
			
			if(self::$from == 'charts'){			
				//self::chartTableRow($text, $arrayParams);
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
				//self::chartTableRow($text, $arrayParams);
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

		self::$exportClass->pdf->setY(self::$exportClass->pdf->getY() + 5);
		
		// Naslov posameznega grafa
		/*$stevilcenje = (self::$exportClass->numbering == 1 ? $_variable['variable'].' - ' : '');
		$title = $stevilcenje . SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )';
		self::$exportClass->pdf->setFont('','b','6');
		self::$exportClass->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);
		self::$exportClass->pdf->setFont('','','6');*/

		$counter = 0;
		$_kumulativa = 0;
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		//$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
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
	

	
	static function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}
	
	static function getCellHeight($string, $width){
		
		// Star nacin
		//$linecount = self::$exportClass->pdf->getNumLines(self::encodeText($string), $width);
		//$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.5) );
		
		self::$exportClass->pdf->startTransaction();
		// get the number of lines calling you method
		$linecount = self::$exportClass->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
		// restore previous object
		self::$exportClass->pdf = self::$exportClass->pdf->rollbackTransaction();
			
		$height = ($linecount <= 1) ? 4.7 : $linecount * (self::$exportClass->pdf->getFontSize() * self::$exportClass->pdf->getCellHeightRatio()) + 2;
		
		return $height;
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