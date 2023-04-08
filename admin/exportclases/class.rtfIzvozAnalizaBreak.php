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
class RtfIzvozAnalizaBreak {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;

	public $breakClass = null;			// break class
	public $crosstabClass = null;		// crosstab class
	
	var $spr = 0;			// spremenljivka za katero delamo razbitje
	var $seq;				// sekvenca
	
	var $break_percent;		// opcija za odstotke
	public $break_charts = 0;	// ali prikazujemo graf ali tabelo
	
	var $firstElement = true;

	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...

	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null)
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
		$this->sessionData = SurveyUserSession::getData();
		
		// ustvarimo break objekt
		$this->breakClass = new SurveyBreak($anketa);
		$this->spr = $this->sessionData['break']['spr'];
		# poiščemo sekvenco
		$this->seq = $this->sessionData['break']['seq'];
		
		$this->break_percent = (isset($this->sessionData['break_percent']) && $this->sessionData['break_percent'] == false) ? false : true;
		
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
		
		$this->rtf->draw_title($lang['export_analisys_break']);
		$this->rtf->new_line(2);
		
		if ($this->spr != 0){
			# poiščemo pripadajoče variable
			$_spr_data = $this->breakClass->_HEADERS[$this->spr];
			
			# poiščemo opcije
			$options = $_spr_data['options'];
			
			# za vsako opcijo posebej izračunamo povprečja za vse spremenljivke
			$frequencys = null;
			if (count($options) > 0) {
				foreach ($options as $okey => $option) {
					
					# zloopamo skozi variable
					$okeyfrequencys = $this->breakClass->getAllFrequencys($okey, $this->seq, $this->spr);
					if ($okeyfrequencys != null) {
						if ($frequencys == null) {
							$frequencys = array();
						}
						$frequencys[$okey] = $okeyfrequencys;
					} 
				}
			}
			$this->displayBreak($this->spr,$frequencys);
		
		} else {
			$this->rtf->MyRTF .= $lang['srv_break_error_note_1'];
		}
	}

	function displayBreak($forSpr, $frequencys) {
		
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		//SurveyTimeProfiles :: printIsDefaultProfile(false);
		
		# če imamo filter ifov ga izpišemo
		//SurveyConditionProfiles:: getConditionString();
		
		# če imamo filter spremenljivk ga izpišemo
		//SurveyVariablesProfiles:: getProfileString(true);
		//SurveyDataSettingProfiles :: getVariableTypeNote();
		
		# če rekodiranje
		//$SR = new SurveyRecoding($this->anketa);
		//$SR -> getProfileString();
		
		# filtriranje po spremenljivkah
		$_FILTRED_VARIABLES = SurveyVariablesProfiles::getProfileVariables(SurveyVariablesProfiles::checkDefaultProfile(), true);
		
		# ali prikazujemo tabele ali grafe
		$this->break_charts = (isset($this->sessionData['break']['break_show_charts']) && (int)$this->sessionData['break']['break_show_charts'] == 1) ? 1 : 0;

		
		foreach ($this->breakClass->_HEADERS AS $skey => $spremenljivka) {
		
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
		}
	}
	
	function displayBreakSpremenljivka($forSpr,$frequencys,$spremenljivka) {
		$tip = $spremenljivka['tip'];
		$skala = $spremenljivka['skala'];
		if ($forSpr != $spremenljivka['id']) {
			switch ($tip) {
				# radio, dropdown
				case 1:
				case 3:
					$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
					break;
				#multigrid
				case 6:
					if ($spremenljivka['skala'] == 0) {
						$this->displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka);
					} else {
						$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
					}
					break;
				# checkbox
				case 2:
						$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
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
					$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
				case 4:
				
				case 21:
					# po novem besedilo izpisujemo v klasični tabeli
					$this->displayBreakTableText($forSpr,$frequencys,$spremenljivka);
					
					#$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
				default:
					$this->displayCrosstabs($forSpr,$frequencys,$spremenljivka);
				break;
			}
		
			$this->firstElement = false;
		}
	}
	
	
	function displayBreakTableMgrid($forSpr,$frequencys,$spremenljivka) {
		global $lang;

		// Ce izrisujemo graf
		if($this->break_charts == 1){
			$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'mgrid');
		}
		
		// Ce izrisujemo tabelo
		else{
		
			$keysCount = count($frequencys);
			$sequences = explode('_',$spremenljivka['sequences']);
			$forSpremenljivka = $this->breakClass->_HEADERS[$forSpr];
			$tip = $spremenljivka['tip'];
			
			# izračunamo povprečja za posamezne sekvence
			$means = array();
			foreach ($frequencys AS $fkey => $fkeyFrequency) {
				foreach ($sequences AS $sequence) {
					$means[$fkey][$sequence] = $this->breakClass->getMeansFromKey($frequencys[$fkey][$sequence]);
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
				$this->rtf->MyRTF .= $this->rtf->_font_size(16);
				$this->rtf->MyRTF .= "{\par";	
				
				
				// PRVA VRSTICA	
				$tableHeader = '\trowd\trql\trrh400';
							
				$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
				$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
				$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'). '\qc\cell';		
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
				
				
				// DRUGA VRSTICA
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
				
				$i=1;
				if ($tip != 1 && $tip != 3) {
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
							$tableEnd .= '\pard\intbl\b0 '.$this->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
							$i++;
						}
					}
				} 
				else if (count($spremenljivka['options']) < 15) {
					foreach ($spremenljivka['options'] AS $okey => $option) {
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl '.$this->encodeText($option.' ('.$okey.')'). '\qc\cell';
						$i++;
					}
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
					$tableEnd .= '\pard\intbl '.$this->encodeText('povprečje'). '\qc\cell';	
				}
				
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				
				
				// VRSTICE S PODATKI
				foreach ($frequencys AS $fkey => $fkeyFrequency) {

					$tableHeader = '\trowd\trql\trrh400';
						
					$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl '.$this->encodeText($forSpremenljivka['options'][$fkey]). '\qc\cell';	
					
					$i=1;
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							if ($variable['other'] != 1) {
								$sequence = $variable['sequence'];
								if (($tip == 1 || $tip == 3) && count($spremenljivka['options']) < 15) {
									foreach ($spremenljivka['options'] AS $okey => $option) {
										$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
										$tableEnd .= '\pard\intbl '.$this->encodeText($frequencys[$fkey][$sequence]['valid'][$okey]['cnt']). '\qc\cell';
										$i++;
									}
								}
								$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
								$tableEnd .= '\pard\intbl '.self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
								$i++;
							}
						}
					}
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				}
				
				// konec tabele
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(3);
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
					
					$this->rtf->MyRTF .= '\b '.$this->encodeText('Tabela za: ('.$forSpremenljivka['variable'].') = '.$forSpremenljivka['options'][$fkey]). '\b0';
					$this->rtf->new_line(1);
					
					// zacetek tabele
					$this->rtf->MyRTF .= $this->rtf->_font_size(16);
					$this->rtf->MyRTF .= "{\par";	
					
					
					// 1. vrstica
					$tableHeader = '\trowd\trql\trrh400';					
					$table = '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'). '\qc\cell';			
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					
					
					//2. vrstica
					$tableHeader = '\trowd\trql\trrh400';					
					$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText(''). '\qc\cell';			

					$i=1;
					foreach ($spremenljivka['grids'][0]['variables'] AS $vkey => $variable) {	
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b0 '.$this->encodeText($variable['naslov']). '\qc\cell';
						$i++;
					}
					
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					
					
					// vrstice s podatki
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {

						$tableHeader = '\trowd\trql\trrh400';					
						$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
						$tableEnd = '\pard\intbl '.$this->encodeText('('.$grid['variable'].') '.$grid['naslov']). '\qc\cell';	

						$i=1;
						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];

							$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
							$tableEnd .= '\pard\intbl '.self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
							$i++;
						}
						
						$tableEnd .= '\pard\intbl\row';
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					}

					// konec tabele
					$this->rtf->MyRTF .= "}";
					$this->rtf->new_line(3);
				}
			}
		}
	}
	
	function displayBreakTableNumber($forSpr,$frequencys,$spremenljivka) {
		global $lang;

		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = $this->breakClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$means = array();
		$totalMeans = array();
		$totalFreq = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$means[$fkey][$sequence] = $this->breakClass->getMeansFromKey($frequencys[$fkey][$sequence]);
			}
		}
		
		
		// Ce izrisujemo graf
		if($this->break_charts == 1){
		
			// Number, vsota, ranking graf
			if($tip != 20 ){
				$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
			}
			
			// Multinumber graf
			else{
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
				
					// Izrisujemo samo 1 graf v creportu
					if($_GET['m'] == 'analysis_creport'){
						
						if($spremenljivka['break_sub_table']['key'] == $gkey){
							$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
						}
					}
					
					// Izrisujemo vse zaporedne grafe
					else{
						$spremenljivka['break_sub_table']['key'] = $gkey;
						$spremenljivka['break_sub_table']['sequence'] = $grid['variables'][0]['sequence'];
					
						$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'number');
					}
				}
			}
		}
		
		// Izrisujemo tabelo
		else{
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
				$this->rtf->MyRTF .= $this->rtf->_font_size(16);
				$this->rtf->MyRTF .= "{\par";	
				
				
				// PRVA VRSTICA	
				$tableHeader = '\trowd\trql\trrh400';
							
				$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
				$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
				$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].')'). '\qc\cell';		
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
				
				
				// DRUGA VRSTICA
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
				
				$i=1;
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b0 '.$this->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
						$i++;
					}
				}
			
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				
				
				// TRETJA VRSTICA
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
				
				$i=1;
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b0 '.$this->encodeText('Povprečje'). '\qc\cell';
						$i++;
					}
				}
				
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				
				
				// VRSTICE S PODATKI
				foreach ($frequencys AS $fkey => $fkeyFrequency) {

					$tableHeader = '\trowd\trql\trrh400';
						
					$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl '.$this->encodeText($forSpremenljivka['options'][$fkey]). '\qc\cell';	
					
					$i=1;
					foreach ($spremenljivka['grids'] AS $gkey => $grid) {
						foreach ($grid['variables'] AS $vkey => $variable) {
							if ($variable['other'] != 1) {
								$sequence = $variable['sequence'];
								$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
								$tableEnd .= '\pard\intbl '.self::formatNumber($means[$fkey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
								
								$totalMeans[$sequence] += ($this->breakClass->getMeansFromKey($fkeyFrequency[$sequence])*(int)$frequencys[$fkey][$sequence]['validCnt']);
								$totalFreq[$sequence]+= (int)$frequencys[$fkey][$sequence]['validCnt'];
								
								$i++;
							}
						}
					}
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				}
				
				
				// dodamo še skupno sumo in povprečje
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl '.$this->encodeText('Skupaj'). '\qc\cell';	
				
				$i=1;
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					foreach ($grid['variables'] AS $vkey => $variable) {
						
						$sequence = $variable['sequence'];
						if ($variable['other'] != 1) {
							#povprečja
							$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
						
							$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
							$tableEnd .= '\pard\intbl\b '.self::formatNumber($totalMean ,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\b0\qc\cell';
							
							$i++;
						}	
					}	
				}
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				
				
				// konec tabele
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(3);
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

							
				# za multicheck razdelimo na grupe - skupine
				foreach ($spremenljivka['grids'] AS $gkey => $grid) {
					
					$this->rtf->MyRTF .= '\b '.$this->encodeText('Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'].' ('.$grid['variable'].')'). '\b0';
					$this->rtf->new_line(1);
					
					
					// zacetek tabele
					$this->rtf->MyRTF .= $this->rtf->_font_size(16);
					$this->rtf->MyRTF .= "{\par";	
					
					// PRVA VRSTICA	
					$tableHeader = '\trowd\trql\trrh400';
								
					$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'). '\qc\cell';		
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
					
					
					// DRUGA VRSTICA
					$tableHeader = '\trowd\trql\trrh400';
							
					$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
					
					$i=1;
					foreach ($grid['variables'] AS $vkey => $variable) {
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b0 '.$this->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
						$i++;
					}
				
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					
					
					// TRETJA VRSTICA
					$tableHeader = '\trowd\trql\trrh400';
							
					$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
					
					$i=1;
					foreach ($grid['variables'] AS $vkey => $variable) {
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl\b0 '.$this->encodeText('Povprečje'). '\qc\cell';
						$i++;
					}
					
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					
					
					// vrstice s podatki
					foreach ($forSpremenljivka['options'] AS $okey => $option) {

						$tableHeader = '\trowd\trql\trrh400';					
						$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
						$tableEnd = '\pard\intbl '.$this->encodeText($option). '\qc\cell';	

						$i=1;
						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];

							$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
							$tableEnd .= '\pard\intbl '.self::formatNumber($means[$okey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\qc\cell';
							
							$totalMeans[$sequence] += ($means[$okey][$sequence]*(int)$frequencys[$okey][$sequence]['validCnt']);
							$totalFreq[$sequence]+= (int)$frequencys[$okey][$sequence]['validCnt'];	
							
							$i++;
						}
						
						$tableEnd .= '\pard\intbl\row';
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					}
					
					// dodamo še skupno sumo in povprečje
					$tableHeader = '\trowd\trql\trrh400';					
					$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
					$tableEnd = '\pard\intbl '.$this->encodeText('Skupaj'). '\qc\cell';	

					$i=1;
					foreach ($grid['variables'] AS $vkey => $variable) {
						$sequence = $variable['sequence'];

						if ($variable['other'] != 1) {
								#povprečja
								$totalMean =  $totalFreq[$sequence] > 0 ? $totalMeans[$sequence] / $totalFreq[$sequence] : 0;
								$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
								$tableEnd .= '\pard\intbl\b '.self::formatNumber($totalMean,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''). '\b0\qc\cell';
								
								$i++;
						}					
					}
					
					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
					

					// konec tabele
					$this->rtf->MyRTF .= "}";
					$this->rtf->new_line(3);
				}
			}
		}
	}
		
	function displayBreakTableText($forSpr,$frequencys,$spremenljivka) {
		global $lang;

		$keysCount = count($frequencys);
		$sequences = explode('_',$spremenljivka['sequences']);
		$forSpremenljivka = $this->breakClass->_HEADERS[$forSpr];
		$tip = $spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$texts = array();
		foreach ($frequencys AS $fkey => $fkeyFrequency) {
			foreach ($sequences AS $sequence) {
				$texts[$fkey][$sequence] = $this->breakClass->getTextFromKey($fkeyFrequency[$sequence]);
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

					
		# za multicheck razdelimo na grupe - skupine
		foreach ($spremenljivka['grids'] AS $gkey => $grid) {
			
			$this->rtf->MyRTF .= '\b '.$this->encodeText('Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'].' ('.$grid['variable'].')'). '\b0';
			$this->rtf->new_line(1);
			
			
			// zacetek tabele
			$this->rtf->MyRTF .= $this->rtf->_font_size(16);
			$this->rtf->MyRTF .= "{\par";	
			
			// PRVA VRSTICA	
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($forSpremenljivka['naslov'].' ('.$forSpremenljivka['variable'].')'). '\qc\cell';			
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($spremenljivka['naslov'].' ('.$spremenljivka['variable'].') - '.$grid['naslov'].' ('.$grid['variable'].')'). '\qc\cell';		
			$tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
			
			
			// DRUGA VRSTICA
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
			
			$i=1;
			foreach ($grid['variables'] AS $vkey => $variable) {
				$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
				$tableEnd .= '\pard\intbl\b0 '.$this->encodeText($variable['naslov'].' ('.$variable['variable'].')'). '\qc\cell';
				$i++;
			}
		
			$tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
						
			
			// vrstice s podatki
			foreach ($forSpremenljivka['options'] AS $okey => $option) {

				$tableHeader = '\trowd\trql\trrh400';					
				$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl '.$this->encodeText($option). '\qc\cell';	

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
						$tableEnd .= '\pard\intbl '.$this->encodeText($text). '\qc\cell';
					}
					else{
						$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $i*$defw_part3 );	
						$tableEnd .= '\pard\intbl '.$this->encodeText(''). '\qc\cell';
					}
					
					$i++;
				}
				
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
			}
						

			// konec tabele
			$this->rtf->MyRTF .= "}";
			$this->rtf->new_line(3);
			
			$this->rtf->MyRTF .= $this->rtf->_font_size(24);
		}
	}
		
	function displayCrosstabs($forSpr,$frequencys,$spremenljivka) {
		global $lang;

		//ustvarimo crosstab objekt
		$this->crosstabClass = new SurveyCrosstabs();
		$this->crosstabClass->Init($this->anketa['id']);
		
		$spr1 = $this->spr;
		$seq1 = $this->seq;
		$grd1 = 'undefined';
		foreach ($this->breakClass->_HEADERS[$spr1]['grids'] AS $gid => $grid) {
			foreach ($grid['variables'] AS $vkey => $vrednost) {
				if ($vrednost['sequence'] == $seq1) {
					$grd1 = $gid;
				}
			}
		}
		
		$spr2 = $spremenljivka['id'];
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			if (($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6) && $this->break_charts != 1) {
				
				$text = 'Tabela za: '.$spremenljivka['naslov'].' ('.$spremenljivka['variable'].') = '.$grid['naslov'];
				if ($spremenljivka['tip'] != 6) {
					$text .= ' ('.$grid['variable'].')';
				}
				$this->rtf->MyRTF .= '\b '.$this->encodeText($text). '\b0';
				$this->rtf->new_line(1);
			}
			
			$seq2 = $grid['variables'][0]['sequence'];
			$grd2 = $gid;

			$this->crosstabClass->setVariables($seq2,$spr2,$grd2,$seq1,$spr1,$grd1);
			
			if($this->break_charts == 1){
				$this->crosstabClass->fromBreak = false;
				$this->displayChart($forSpr,$frequencys,$spremenljivka,$type = 'crosstab');
			}
			else{
				$this->displayCrosstabsTable();
			}
		}
	}
	
	function displayCrosstabsTable() {
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
				
					//konec tabele
					$this->rtf->MyRTF .= "}";
					$this->rtf->new_line(3);
				}
			}
		}
	}
	
	function displayChart($forSpr,$frequencys,$spremenljivka,$type){
		global $lang;

		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		if($type == 'crosstab'){
			$chartID = implode('_', $this->crosstabClass->variabla1[0]);
			$chartID .= '_'.$this->crosstabClass->variabla2[0]['seq'].'_'.$this->crosstabClass->variabla2[0]['spr'].'_undefined';
			$chartID .= '_counter_0'/*.$this->crosstabClass->counter*/;	
			
			$settings = $this->sessionData['crosstab_charts'][$chartID];
		}
		
		else{
			if($spremenljivka['tip'] == 20){
				// Preberemo za kateri grid izrisujemo tabelo
				$gkey = $spremenljivka['break_sub_table']['key'];
				
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $spremenljivka['grids'][$gkey]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
			}
			else{
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$spremenljivka['id'].'-undefined';
			}
			
			$chartID = $spr1.'_'.$spr2;
			
			$settings = $this->sessionData['break_charts'][$chartID];
		}
		
		$imgName = $settings['name'];

		
		// IZRIS GRAFA
		if(!$this->firstElement){
			$this->rtf->new_page();
			$this->rtf->new_line(2);
		}
		
		// Naslov posameznega grafa
		$title = $spremenljivka['naslov'] . ' ('.$spremenljivka['variable'].')';
		if($spremenljivka['tip'] == 20){		
			$grid = $spremenljivka['grids'][$gkey];
			$subtitle = $grid['naslov'] . ' ('.$grid['variable'].')';
		}
		elseif($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6){
			foreach ($spremenljivka['grids'] AS $gid => $grid) {	
				if($this->crosstabClass->variabla1[0]['seq'] == $grid['variables'][0]['sequence']){
					$subtitle = $grid['naslov'];
					if ($spremenljivka['tip'] != 6) {
						$subtitle .= ' ('.$grid['variable'].')';
					}
					break;
				}
			}
		}
		
		$this->rtf->add_text('\b '.$this->encodeText($title).' \b0', 'center');
		$this->rtf->new_line();	
		if($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 6){
			$this->rtf->add_text('\b '.$this->encodeText($subtitle).' \b0', 'center');
			$this->rtf->new_line();	
		}
		
		$scale = 100;
		
		$this->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
		
		$this->rtf->new_line(2);	
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
