<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.rtfIzvozAnalizaFunctions.php');
	require_once("class.enka.rtf.php");

	define("FNT_TIMES", "Times New Roman", true);
	define("FNT_ARIAL", "Arial", true);

	define("FNT_MAIN_TEXT", FNT_TIMES, true);
	define("FNT_QUESTION_TEXT", FNT_TIMES, true);
	define("FNT_HEADER_TEXT", FNT_TIMES, true);

	define("FNT_MAIN_SIZE", 12, true);
	define("FNT_QUESTION_SIZE", 10, true);
	define("FNT_HEADER_SIZE", 10, true);
	
	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogo�imo delovanje prikazovanja/skrivanja ni�elnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogo�imo delovanje prikazovanja/skrivanja ni�elnih vnosti za missinge


/** Class za generacijo rtf-a
 */
class RtfIzvozAnalizaCrosstab {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;

	public static $crosstabClass = null;		//crosstab class
	
	var $crossData1;
	var $crossData2;
	
	var $crosstabVars;
	var $counter;

	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...

	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $crossData1, $crossData2)
	{
		global $site_path;
		global $global_user_id;
		
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;

			// create new RTF document
			$this->rtf = new enka_RTF(true);
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
		$_GET['a'] = A_ANALYSIS;
		
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData('crosstab_charts');
		
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
		
		//$this->crosstabClass->setVariables($crossData2[0],$crossData2[1],$crossData2[2],$crossData1[0],$crossData1[1],$crossData1[2]);
		
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

	function getAnketa()
	{ return $this->anketa['id']; }

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		$this->rtf->display($fileName = "analiza.rtf",true);
	}

	function init()
	{
		global $lang;
		
		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'left');
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'right');
		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right');
		$this->rtf->set_default_font(FNT_TIMES, FNT_MAIN_SIZE);
		return true;
	}

	function createRtf()
	{
		global $site_path;
		global $lang;

		// izpisemo prvo stran
		//$this->createFrontPage();
		
		$this->rtf->draw_title($lang['export_analisys_crosstabs']);
		$this->rtf->new_line(1);
		
		# polovimo nastavtve missing profila
		//SurveyConditionProfiles:: getConditionString();
		
		$this->crosstabClass->_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
		if (count($this->crosstabClass->_LOOPS) > 0) {
			# ce mamo zanke
			foreach ( $this->crosstabClass->_LOOPS AS $loop) {

				$this->crosstabClass->_CURRENT_LOOP = $loop;
				$this->displayCrosstabsTable();
			}
		} else {
			// loopamo cez vse izbrane variable in izrisemo vse tabele
			$addPage = false;
			$this->counter = 0;
			for($j=0; $j<sizeof($this->crossData2); $j++){
				for($i=0; $i<sizeof($this->crossData1); $i++){

					if($addPage){
						$this->rtf->new_page();
						$this->rtf->new_line(3);
					}
					else
						$addPage = true;
				
					$this->crosstabClass->setVariables($this->crossData2[$j][0],$this->crossData2[$j][1],$this->crossData2[$j][2],$this->crossData1[$i][0],$this->crossData1[$i][1],$this->crossData1[$i][2]);
				
					$this->displayCrosstabsTable();
					
					$this->counter++;
				}
			}	
		} 			
	}

	public function displayCrosstabsTable() {
		global $lang;
		
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
					
					//nastavitve tabele - (sirine celic, border...)
					$defw_full = 10500;
					$defw_part = 1500;
					$defw_part2 = ($cols > 2) ? 9000 : 7500;
					//izracun sirine ene celice
					$singleWidth = floor( $defw_part2 / ($cols) );	

					$borderB = '\clbrdrb\brdrs\brdrw10';
					$borderT = '\clbrdrt\brdrs\brdrw10';
					$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
					$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
					//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
					$bold = '\b';			
						
						
					# izri�emo tabelo
					$this->rtf->MyRTF .= $this->rtf->_font_size(16);
					$this->rtf->MyRTF .= "{\par";
					
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
					
					$this->crosstabVars = array($sub_q1, $sub_q2);
					
					//prva vrstica
					$tableHeader = '\trowd\trql\trrh400';
					
					$table = '\clvertalc\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';			
					$table .= '\clvertalc\cellx'.( 2 * $defw_part );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
					
					$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $defw_part2 );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($sub_q1). '\qc\cell';
					$table .= '\clvertalc\cellx'.( 2 * $defw_part + $defw_part2 + $singleWidth );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
					
					$tableEnd .= '\pard\intbl\row';
					
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
								
					//druga vrstica		
					$tableHeader = '\trowd\trql\trrh400';
					
					$table = '\clvertalc\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';			
					$table .= '\clvertalc\cellx'.( 2 * $defw_part );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
					
					
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
							$tableEnd .= '\pard\intbl'.$bold.' '. $this->encodeText($text). '\qc\cell';

							$i++;
						}
					}

					
					$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $defw_part2 + $singleWidth );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($lang['srv_analiza_crosstab_skupaj']). '\qc\cell';
					
					$tableEnd .= '\pard\intbl\row';
					
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					

					
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
									$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($sub_q2). '\qc\cell';	
								}
								else{
									$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
									$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
								}		
									

									
								$text = $crossVariabla2['naslov'];
								if ($crossVariabla2['type'] !== 't') {
									$text .= ' ('.$ckey2.')';
								}											
								if($j == 1){
									$table .= '\clvertalc'.$borderLR.$borderT.'\cellx'.( 2 * $defw_part );	
									$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
								}
								else{
									$table .= '\clvertalc'.$borderLR.'\cellx'.( 2 * $defw_part );	
									$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
								}
								
								
								
								//del vrstice z vsebino
								$bold = '\b0';
								$i=1;
								foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
										
									//FREKVENCE
									if( $j == 1 ){							
										$text = ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
									}
									
									//PROCENTI
									elseif( $j == 2 && $numColumnPercent > 0 ){
										$x = 1;
										if ($this->crosstabClass->crossChk1) {
											#procent vrstica
											$text = $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
										
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
											
											$x++;
										}
										if ($this->crosstabClass->crossChk2) {
											#procent stolpec
											$text =  $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
										
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if ($this->crosstabClass->crossChk3) {
											#procent skupni
											$text = $this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), 2, '%');
										
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
											$x++;
										}
									}
									
									//RESIDUALI
									else{	
										$x = 1;
										if ($this->crosstabClass->crossChkEC) {
											$text = $this->formatNumber($crosstabs['exC'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if ($this->crosstabClass->crossChkRE) {
											$text = $this->formatNumber($crosstabs['res'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if ($this->crosstabClass->crossChkSR) {
											$text = $this->formatNumber($crosstabs['stR'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
											$x++;
										}
										if ($this->crosstabClass->crossChkAR) {
											$text = $this->formatNumber($crosstabs['adR'][$ckey1][$ckey2], 3, '');
											
											$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnResidual));	
											$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
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
									$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
									
									$bold = '\b0';
								}
								elseif( $j == 2 && $numColumnPercent > 0 ){
									$x = 1;
									# suma po vrsticah v procentih
									if ($this->crosstabClass->crossChk1) {
										$text = $this->formatNumber(100, 2, '%');
										
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if ($this->crosstabClass->crossChk2) {
										$text = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if ($this->crosstabClass->crossChk3) {
										$text = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth +  $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
										$x++;
									}
								}
								else{
									$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
									$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
								}

								$tableEnd .= '\pard\intbl\row';					
								$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
								
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
							$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
						
							$table .= '\clvertalc'.$borderLR.$borderT.'\cellx'.( 2 * $defw_part );	
							$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($lang['srv_analiza_crosstab_skupaj']). '\qc\cell';
						}
						else{
							$table = '\clvertalc\cellx'.( $defw_part );	
							$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
						
							$table .= '\clvertalc'.$borderLR.$borderB.'\cellx'.( 2 * $defw_part );	
							$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
						}
						
						if (count($crosstabs['options1']) > 0){
							
							$i=1;
							$bold = '\b0';					
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {			

								if($j == 1){
									$bold = '\b';
									
									$text = (int)$crosstabs['sumaStolpec'][$ckey1];					
									$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + $i * $singleWidth );	
									$tableEnd .= '\pard\intbl'.$bold.' '. $this->encodeText($text). '\qc\cell';
									
									$bold = '\b0';
								}		

								else{
									$x = 1;
									# suma po stolpcih v procentih
									if ($this->crosstabClass->crossChk1) {
										$text = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if ($this->crosstabClass->crossChk2) {
										$text = $this->formatNumber(100, 2, '%');
										
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
										$x++;
									}
									if ($this->crosstabClass->crossChk3)
									{
										$text = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
									
										$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
										$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
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
							$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
						
							$bold = '\b0';
						}

						else{
							$x = 1;
							# suma po stolpcih v procentih
							if ($this->crosstabClass->crossChk1) {
								$text = $this->formatNumber(100, 2, '%');
								
								$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
								$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
								$x++;
							}
							if ($this->crosstabClass->crossChk2) {
								$text = $this->formatNumber(100, 2, '%');
								
								$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
								$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
								$x++;
							}
							if ($this->crosstabClass->crossChk3) {
								$text = $this->formatNumber(100, 2, '%');
								
								$table .= '\clvertalc'.$border.'\cellx'.( 2 * $defw_part + ($i-1) * $singleWidth + $x * round($singleWidth/$numColumnPercent));	
								$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($text). '\qc\cell';
										
								$x++;
							}
						}
						
						$bold = '\b';				
						$tableEnd .= '\pard\intbl\row';
					
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					}
					
					
					
						
					/*	
					$this->pdf->MultiCell(25, $height, $this->encodeText('&nbsp; '), 'T', 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(20, $height, $this->encodeText($lang['srv_analiza_crosstab_skupaj']), 1, 'C', 0, 0, 0 ,0, true);
					
					$this->pdf->setFont('','','5');
							
					if (count($crosstabs['options1']) > 0){
						foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
							
							$data = array();
							
							# prikazujemo eno od treh mo�nosti					
							if ($this->crosstabClass->crossChk0) {
								# suma po stolpcih
								$data[] = (int)$crosstabs['sumaStolpec'][$ckey1];
							}					
							if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
								# suma po stolpcih v procentih
								if ($this->crosstabClass->crossChk1) {
									$data[] = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
								if ($this->crosstabClass->crossChk2) {
									$data[] = $this->formatNumber(100, 2, '%');
								}
								if ($this->crosstabClass->crossChk3)
								{
									$data[] = $this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%');
								}
							}

							$this->displayCell($data, $singleWidth, $rowSpan, $this->crosstabClass->crossChk0, $numColumnPercent, 0);
						}
						
						# zadnja celica z skupno sumo
						$data = array();
						
						if ($this->crosstabClass->crossChk0) {
							# skupna suma
							$data[] = (int)$crosstabs['sumaSkupna'];
						}
						if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
							# suma po stolpcih v procentih
							if ($this->crosstabClass->crossChk1) {
								$data[] = $this->formatNumber(100, 2, '%');
							}
							if ($this->crosstabClass->crossChk2) {
								$data[] = $this->formatNumber(100, 2, '%');
							}
							if ($this->crosstabClass->crossChk3) {
								$data[] = $this->formatNumber(100, 2, '%');
							}
						}
						
						$this->displayCell($data, $singleWidth, $rowSpan, $this->crosstabClass->crossChk0, $numColumnPercent, 0);
					}*/

					//konec tabele
					$this->rtf->MyRTF .= "}";
					$this->rtf->new_line(1);
					
					
					// Izris grafa (ce je vklopljena nastavitev)
					if($this->sessionData['showChart'] == '1'){
						$this->displayChart();
					}
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
	function displayCell($data, $width, $numRows, $frekvence, $numColumnPercent, $numColumnResidual){
		
		$height = ($numRows == 1 ? 8 : 5);
		$fullHeight = ($height == 8 ? $height : $numRows*$height);
		$i=0;
		
		//preberemo pozicijo - zacetek celice
		$y = $this->pdf->GetY();
		$x = $this->pdf->GetX();
		
		$this->pdf->setDrawColor(170, 170, 170);
		
		//izrisemo frekvence
		if($frekvence == 1){
			$this->pdf->MultiCell($width, $height, $this->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		if($numColumnPercent > 0){
			
			$this->pdf->setX($x);
			$singleWidth = round($width/$numColumnPercent);
			
			for($j=1; $j<$numColumnPercent; $j++){
				$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($data[$i]), 1, 'C', 0, 0, 0 ,0, true);				
				$i++;
			}
			$this->pdf->MultiCell($width - (($numColumnPercent-1)*$singleWidth), $height, $this->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		if($numColumnResidual > 0){
			
			$this->pdf->setX($x);
			$singleWidth = round($width/$numColumnResidual);
			
			for($j=1; $j<$numColumnResidual; $j++){
				$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($data[$i]), 1, 'C', 0, 0, 0 ,0, true);				
				$i++;
			}
			$this->pdf->MultiCell($width - (($numColumnResidual-1)*$singleWidth), $height, $this->encodeText($data[$i]), 1, 'C', 0, 1, 0 ,0, true);
			$i++;
		}
		
		//zaradi preglednosti narisemo okvir celotne celice
		$this->pdf->setDrawColor(0, 0, 0, 255);
		$this->pdf->SetXY($x, $y);
		$this->pdf->MultiCell($width, $fullHeight, '', 1, 'C', 0, 1, 0 ,0, true);		
		
		//na koncu nastavimo pozicijo na pravo mesto
		$this->pdf->SetXY($x+$width, $y);	
	}
	
	function displayChart(){
		global $lang;

		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		$chartID = implode('_', $this->crosstabClass->variabla1[0]).'_'.implode('_', $this->crosstabClass->variabla2[0]);
		$chartID .= '_counter_'.$this->counter;
		
		$settings = $this->sessionData[$chartID];
		$imgName = $settings['name'];

		
		// IZRIS GRAFA
		$this->rtf->new_page();
		$this->rtf->new_line(5);
		
		// Naslov posameznega grafa
		$this->rtf->set_font("Arial Black", 8);
		
		if($settings['type'] == 1 || $settings['type'] == 4)		
			$title = $this->rtf->bold(1) .$this->crosstabVars[0].' / '.$this->crosstabVars[1] . $this->rtf->bold(0);
		else
			$title = $this->rtf->bold(1) .$this->crosstabVars[0] . $this->rtf->bold(0);
			
		$this->rtf->add_text($this->encodeText($title), 'center');
		$this->rtf->new_line();	
		
		$this->rtf->set_font("Times New Roman", 10);
		
		$scale = 100;
		
		$this->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
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
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		return strip_tags($text);
	}
}

?>
