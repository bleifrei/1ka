<?php		

global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
class XlsIzvozAnalizaSums {		

	var $anketa;						// trenutna anketa
	var $pi=array('canCreate'=>false); 	// za shrambo parametrov in sporocil

	private $headFileName = null;					# pot do head fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	
	var $current_loop = 'undefined';
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $sprID = null, $loop = null){
		global $site_path;
		global $global_user_id;
		global $output;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){

			$this->anketa['id'] = $anketa;
			$this->spremenljivka = $sprID;
			
			SurveyAnalysis::Init($this->anketa['id']);
			SurveyAnalysis::$setUpJSAnaliza = false;
			
			// create new XLS document
			$this->xls = new xls();
			
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa['id']);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
			
			SurveyZankaProfiles :: Init($this->anketa['id'], $global_user_id);
			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
            $this->pi['canCreate'] = false;
            
			return false;
		}
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
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
	{
		return $this->anketa['id']; 
	}

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		//Close and output rtf document
//		$this->rtf->Output($fileName, 'I');
		$output = $this->createXls();
		$this->xls->display($fileName, $output);
	}

	function init()
	{
		return true;
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_sums'].'</b></font></td></tr></table>';
									
		# preberemo header
		if ($this->headFileName !== null) {
			
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();

			# če nimamo zank
			if(count(SurveyAnalysis::$_LOOPS) == 0){
				
				$this->displayTables();
			}
			else{
				// izrisemo samo eno tabelo iz enega loopa
				if($this->current_loop > 0){
					
					$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
					$loop['cnt'] = $this->current_loop;
					SurveyAnalysis::$_CURRENT_LOOP = $loop;
					
					// Izpisemo naslov zanke za skupino
					$output .= '<table border="0"><tr><td></td></tr></table>';
					$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['srv_zanka_note'].$loop['text'].'</b></font></td></tr></table>';
				
					$this->displayTables();
				}
				// Izrisemo vse tabele spremenljivka (iz vseh loopov)
				else{
					$loop_cnt = 0;
					# če mamo zanke
					foreach(SurveyAnalysis::$_LOOPS AS $loop) {
						$loop_cnt++;
						$loop['cnt'] = $loop_cnt;
						SurveyAnalysis::$_CURRENT_LOOP = $loop;
						
						// Izpisemo naslov zanke za skupino
						$output .= '<table border="0"><tr><td></td></tr></table>';
						$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['srv_zanka_note'].$loop['text'].'</b></font></td></tr></table>';
					
						$this->displayTables();
					}
				}
			}
		
		} // end if else ($_headFileName == null)
		
		return $output;
	}
			
	function displayTables(){
		global $site_path;
		global $lang;
		global $global_user_id;
		global $output;
	
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		# odstranimo sistemske variable tipa email, ime, priimek, geslo
		SurveyAnalysis::removeSystemVariables();
		
		SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
		
		# polovimo frekvence			
		SurveyAnalysis::getFrequencys();
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		$line_break = '';
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid)) 
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {

					$output .= '<table border="0"><tr><td></td></tr></table>';
				
					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1:
							# radio - prikjaže navpično
							self::sumVertical($spid,'sums');

						break;
						case 2:
							#checkbox  če je dihotomna:
							self::sumVerticalCheckbox($spid,'sums');
						break;
						case 3:
							# dropdown - prikjaže navpično					
							self::sumVertical($spid,'sums');
						break;
						case 6:
							if ($spremenljivka['enota'] != 3) {
								# multigrid
								self::sumHorizontal($spid,'sums');
							} else { 								
								#imamo dvojni mgrid
								self::sumDoubleHorizontal($spid,'sums*');
							}
						break;
						case 16:
							#multicheckbox če je dihotomna:
							self::sumMultiHorizontalCheckbox($spid,'sums');
						break;
						case 17:	
							#razvrščanje  če je ordinalna 
							self::sumHorizontal($spid,'sums');
						break;
						case 4:	# text
						case 8:	# datum
							# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
							self::sumTextVertical($spid,'sums');
						break;
						case 21: # besedilo*
							# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
							if ($spremenljivka['cnt_all'] == 1) {
								// če je enodimenzionalna prikažemo kot frekvence
								// predvsem zaradi vprašanj tipa: language, email... 
								self::sumTextVertical($spid,'sums');
							} else {
								self::sumMultiText($spid,'sums');
							}
						break;
						case 19: # multitext
							self::sumMultiText($spid,'sums');
						break;
						case 7:
						case 18:
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumNumberVertical($spid,'sums');
						break;
						case 20:
							# Če je v gridu le ene variabla naj bo default prikazan f* in ne SUMA
							if ($spremenljivka['grids'][0]['cnt_vars'] == 1) {
								# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
								self::sumMultiNumberVertical($spid,'sums');
								
							} else {
								# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
								self::sumMultiNumber($spid,'sums');
							}
						break;
						case 22:
							# kalkulacija
							self::sumNumberVertical($spid,'sums');
						break;
						case 5:
							# nagovor
							//self::sumNagovor($spid,'sums');
						break;
                                                case 26: //lokacija
							self::sumLokacija($spid,'sums');
						break;
					}
					
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
			#ob_flush(); flush();	
		} // end foreach self::$_HEADERS
	}
	
	
	/** Izriše sumarnik v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumVertical($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# dodamo opcijo kje izrisujemo legendo
		$inline_legenda = false;
				
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);
		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		#odgovori
		
		$show_valid_percent = (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;								
		$output .= '<td colspan="'. (4 + $show_valid_percent) .'"><span><b>'.$spremenljivka['naslov'].'</b></span>';
		$output .= '</td>';

		$output .= '</tr>';
		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '</td>';
		#odgovori								
		
		$output .= '<td>'.$lang['srv_analiza_frekvence_titleAnswers'] . '</td>';

		$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) {
			$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		}
		$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		$output .= '</tr>';				
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
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							// za povprečje
							$xi = $vkey;
							$fi = $vAnswer['cnt'];
							
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;
							//if ($counter < $maxAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								}
							//}
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
			$output .= '<tr>';
			$output .= '<td colspan="6"></td>';			
			$output .= '</tr>';
			$output .= '<tr >';
			$output .= '<td colspan="2"></td>';
			$output .= '<td align="center">'.$lang['srv_analiza_opisne_povprecje'].'</td>';
			$output .= '<td align="center">'. self::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'').'</td>';
			$output .= '<td align="center">'.$lang['srv_analiza_opisne_odklon'].'</td>';
			$output .= '<td align="center">'.self::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'').'</td>';
			$output .= '</tr>';
		}
		$output .= '</table>';
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}

	/** Izriše sumarnik v horizontalni obliki za multi checbox
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumMultiHorizontalCheckbox($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		# tekst vprašanja

		$css_hide_enote = isset($_POST['navedbe']) && $_POST['navedbe'] == '1' ? ' hidden' : ''; 
		$css_hide_navedbe = isset($_POST['navedbe']) && $_POST['navedbe'] == '1' ? '' : ' hidden'; 
		 
		# odgovori
		$output .= '<table border="1">';
		$output .= '<tr>';
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		$output .= '<td colspan="'. ($_clmn_cnt + 3) .'">';
		$output .= '<span><b>'.$spremenljivka['naslov'].'</b></span>';

//		$output .= '<span name="span_show_navedbe_1_'.$spid.'" class="span_navedbe"><a href="javascript:show_navedbe(\''.$spid.'\',\'3\');">&nbsp;(<span class="blue">'.$lang['srv_analiza_opisne_answers'].'&nbsp;</span>/<span class="blue">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
//		$output .= '<span name="span_show_navedbe_2_'.$spid.'" class="span_navedbe'.$css_hide_enote.'"><a href="javascript:show_navedbe(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span>&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
//		$output .= '<span name="span_show_navedbe_3_'.$spid.'" class="span_navedbe'.$css_hide_navedbe.'"><a href="javascript:show_navedbe(\''.$spid.'\',\'1\');">&nbsp;(<span>'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		$output .= '</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td>';
		$output .= '</td>';
		$output .= '<td>';
		$output .= $lang['srv_analiza_opisne_subquestion'];	
		$output .= '</td>';

		$output .= '<td align="center" colspan="'.($_clmn_cnt).'">';
		$output .= $lang['srv_analiza_opisne_answers'];
		$output .= '</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_valid'].'</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_num_units'].'</td>';
		$output .= '</tr>';
		
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		$output .= '<tr>';
		$output .= '<td></td>';
		$output .= '<td></td>';

		if (count($_variables) > 0) {
			foreach ($_variables AS $vkey => $variable) {
				if ($variable['other'] != true) {
					$output .= '<td align="center">' . $variable['naslov'].' ('.$variable['gr_id']. ') </td>';
				}
			}
		}
		//$output .= '<td>' . $lang['srv_anl_suma1'] . '</td>';
		$output .= '<td align="center">'. $_valid_cnt .'</td>';
		$output .= '<td align="center">'.$_approp_cnt. '</td>';
		$output .= '</tr>';
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			$_cnt = 0;
			# vodoravna vrstice s podatki
			$css_back = ' anl_bck_desc_2';
			$output .= '<tr>'; 
			$output .= '<td align="center" valign="top">'.$grids['variable'].'</td>';
			$output .= '<td valign="top">'.$grids['naslov'].'</td>';

			$_arguments = 0;

			$_max_appropriate = 0;
			$_max_cnt = 0;
			// prikaz frekvenc
			if (count($grids['variables']) > 0)
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
					$output .= '<td>';
					$output .= '<table>';
					$output .= '<tr>';
					$output .= '<td align="center">'.$_cnt.'</td>';
					$output .= '</tr>';
					$output .= '<tr>';
					$output .= '<td align="center">';
										
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;  
					$output .= self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
					$output .= '</td>';
					$output .= '</tr></table>';
				
					$output .= '</td>';
				}
			}
			# veljavno 
			$output .= '<td align="center" valign="top">'.$_max_cnt.'</td>';
			#ustrezno
			$output .= '<td align="center" valign="top">'.$_max_appropriate.'</td>';
			
			$output .= '</tr>';
		}
		$output .= '</table>';
			
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
	
	function sumVerticalCheckbox($spid,$_from) {
		global $lang;
		global $output;
		
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
		
		$output .= '<table border="1">';
		$output .= '<tr>';
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		$output .= '<td colspan="8"><span><b>'.$spremenljivka['naslov'].'</b></span>';

		//$output .= '<span name="span_show_navedbe_2_'.$spid.'" class="span_navedbe"><a href="javascript:show_navedbe(\''.$spid.'\',\'2\');">&nbsp;(<span class="blue">'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span>&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		//$output .= '<span name="span_show_navedbe_3_'.$spid.'" class="span_navedbe hidden"><a href="javascript:show_navedbe(\''.$spid.'\',\'1\');">&nbsp;(<span>'.$lang['srv_analiza_opisne_units'].'&nbsp;</span>/<span class="blue">&nbsp;'.$lang['srv_analiza_opisne_arguments'].'</span>)</a></span>';
		$output .= '</td>';
		$output .= '</tr>';

		
		$output .= '<tr>';
		$output .= '<td align="center">';
		$output .= '</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_subquestion'].'</td>';

		$output .= '<td align="center" colspan="5">'.$lang['srv_analiza_opisne_units'].'</td>';

		$output .= '<td align="center" colspan="2">'.$lang['srv_analiza_opisne_arguments'].'</td>';
		$output .= '</tr>';
		
		
		$output .= '<tr>';
		$output .= '<td align="center"></td>';
		$output .= '<td align="center"></td>';
		
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_frequency'].'</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_valid'].'</td>';
		$output .= '<td align="center">% - '.$lang['srv_analiza_opisne_valid'].'</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_num_units_valid'].'</td>';
		$output .= '<td align="center">% - '.$lang['srv_analiza_num_units_valid'].'</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_frequency'].'</td>';
		$output .= '<td align="center">%</td>';
		$output .= '</tr>';
		
		
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] as $gid => $grid) {
			$_max_valid = 0;
			$_max_appropriate = 0;
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				if ($variable['other'] != 1) {
					$_sequence = $variable['sequence'];
					
					$output .= '<tr>';
					$output .= '<td align="center">'.$variable['variable'].'</td>';
					$output .= '<td>'.$variable['naslov'].'</td>';

					// Frekvence
					$output .= '<td align="center">';
					$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$output .= '</td>';
					
					// Veljavni
					$output .= '<td align="center">';
					$output .= (int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);				
					$output .= '</td>';
					
					// Veljavni procent
					$output .= '<td align="center">';
					$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0; 
					$output .= self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
					$output .= '</td>';
					
					$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					$_max_valid = max ($_max_valid, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
					
					// Ustrezni
					$output .= '<td align="center">';
					$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];

					// % Ustrezni
					$output .= '<td align="center">';
					$valid = (int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
					$valid = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$_percent = ($_max_appropriate > 0 ) ? 100*$valid / $_max_appropriate : 0;
					
					$output .= self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
					$output .= '</td>';
					
					// Navedbe
					$output .= '<td align="center">';
					$output .= SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$output .= '</td>';
					
					$output .= '<td align="center">';
					$_percent = ($_navedbe[$gid] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / $_navedbe[$gid] : 0;
					$output .= self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
					$output .= '</td>';
					
					
					$output .= '</tr>';
					
				} else {
					# drugo 
				}
			}
			
			$output .= '<tr>';
			
			$output .= '<td></td>';
			$output .= '<td colspan="1">'.$lang['srv_anl_suma_valid'].'</td>';
			
			$output .= '<td></td>';
			$output .= '<td align="center">'.$_max_valid.'</td>';
			$output .= '<td></td>';

			$output .= '<td align="center">'.$_max_appropriate.'</td>';
			$output .= '<td></td>';
			
			$output .= '<td align="center">'.$_navedbe[$gid].'</td>';
			$output .= '<td align="center">'.SurveyAnalysis::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';

			
			$output .= '</tr>';
			
		}
		$output .= '</table>';
		
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
	function sumHorizontal($spid,$_from) {
		global $lang;
		global $output;
		
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
		
		$output .= '<table border="1">';
		$output .= '<tr>';
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		$output .= '<td colspan="'. (3 + $_sub_question_col) .'">';
		$output .= '<span><b>'.$spremenljivka['naslov'].'</b></span>';

		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td align="center">';	
		$output .= '</td>';
		if ($_sub_question_col) {
			$output .= '<td align="center">'.$lang['srv_analiza_opisne_subquestion'].'</td>';
		}

		$output .= '<td align="center" colspan="'. ($_clmn_cnt + 1) .'">'.$lang['srv_analiza_opisne_answers'];
		//$output .= '<span id="img_analysis_f_p_1_'.$spid.'" class="img_analysis_f_p"><a href="javascript:show_single_percent(\''.$spid.'\',\'2\');">&nbsp(<span class="blue">f&nbsp;</span>/<span class="blue">&nbsp;%</span>)</a></span>';
		//$output .= '<span id="img_analysis_f_1_'.$spid.'" class="img_analysis_f hidden"><a href="javascript:show_single_percent(\''.$spid.'\',\'1\');">&nbsp(<span class="blue">f&nbsp;</span>/&nbsp;%)</a></span>';
		//$output .= '<span id="img_analysis_p_1_'.$spid.'" class="img_analysis_p hidden"><a href="javascript:show_single_percent(\''.$spid.'\',\'0\');">&nbsp(f&nbsp;/<span class="blue">&nbsp;%</span>)</a></span>';
		$output .= '</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_valid'].'</td>';
		$output .= '<td align="center">'.$lang['srv_analiza_num_units'].'</td>';
		if ($additional_field) {
			$output .= '<td align="center">'.$lang['srv_analiza_opisne_povprecje'].'</td>';
			$output .= '<td align="center">'.$lang['srv_analiza_opisne_odklon'].'</td>';
		}
		$output .= '</tr>';

		$_variables = $grid['variables'];
		$output .= '<tr>';
		$output .= '<td></td>';
		if ( $_sub_question_col ) {
			$output .= '<td></td>';
		}
		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 
				$output .= '<td align="center">'.$_label.'</td>';
			}
		}
				
		$output .= '<td align="center">'.$lang['srv_anl_suma1'].'</td>';
		$output .= '<td></td>';
		$output .= '<td></td>';
		if ($additional_field) {

			$output .= '<td></td>';
			$output .= '<td></td>';
		}
		$output .= '</tr>';

		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {		

			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				if ($variable['other'] != true) {

					$output .= '<tr>';
					if ($_sub_question_col) {
						$output .= '<td align="center" valign="top">';
						$output .= $variable['variable'].'</td>';
						$output .= '<td valign="top">';
						
						$output .= $variable['naslov'].'</td>';
					} else {
						$output .= '<td></td>';
					}

					# za odklon in povprečje				
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					if (count($spremenljivka['options']) > 0) {									
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							$output .= '<td>';
							$output .= '<table>';
							$output .= '<tr>';
							$output .= '<td align="center">'.SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'].'</td>';
							$output .= '</tr><tr>';
							$output .= '<td align="center">';
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;  
							$output .= self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
							$output .= '</td>';
							$output .= '</tr></table>';
							$output .= '</td>';
						}
					} 
					// suma
					$output .= '<td>';
					$output .= '<table>';
					$output .= '<tr>'; 
					$output .= '<td align="center">'.(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'].'</td>';
					$output .= '</tr><tr>';
					$output .= '<td align="center">'.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
					$output .= '</tr></table>';
					$output .= '</td>';
					// zamenjano veljavni ustrezni
					$output .= '<td align="center" valign="top">';
					$output .= SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
					$output .= '</td>';
					$output .= '<td align="center" valign="top">'.SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'].'</td>';
					if ($additional_field) { # za odklon in povprečje
						# povprečje
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
						$output .= '<td align="center" valign="top">';
						$output .= self::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''); 						
						$output .= '</td>';
						$output .= '<td align="center" valign="top">';
						$output .= self::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						$output .= '</td>';
					}
					$output .= '</tr>';
					
				} else {
					# immamo polje drugo
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}	
		}
		$output .= '</table>';
		
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
	function sumDoubleHorizontal($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();
		$_clmn_cnt = count($spremenljivka['options'])*2;

		# pri radiu in dropdown ne prikazujemo podvprašanj
		$_sub_question_col = 6;


		$output .= '<table border="1">';
		
		$output .= '<tr>';
		$output .= '<td>';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		$output .= '<td colspan="'.(2 + (2*count($spremenljivka['options'])) + $_sub_question_col).'">';
		$output .= '<span><b>'.$spremenljivka['naslov'].'</b></span>';

		$output .= '</td>';
		$output .= '</tr>';

		
		$output .= '<tr>';
		$output .= '<td>';
		$output .= '</td>';

		$output .= '<td>'.$lang['srv_analiza_opisne_subquestion'].'</td>';

		#naslovi podskupin
		$_variables = $grid['variables'];
		$output .= '<td align="center" colspan="'.(count($spremenljivka['options']) + 3).'">'.($spremenljivka['double'][1]['subtitle'] == '' ? $lang['srv_grid_subtitle_def'].' 1' : $spremenljivka['double'][1]['subtitle']).'</td>';
		$output .= '<td align="center" colspan="'.(count($spremenljivka['options']) + 3).'">'.($spremenljivka['double'][2]['subtitle'] == '' ? $lang['srv_grid_subtitle_def'].' 2' : $spremenljivka['double'][2]['subtitle']).'</td>';
		
		#št. enot
		$output .= '<td></td>';
				
		$output .= '</tr>';
		
		
		# naslovi variabel
		$_variables = $grid['variables'];
		$output .= '<tr>';
		$output .= '<td></td>';

		$output .= '<td></td>';

		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 
				$output .= '<td>'.$_label.'</td>';
			}
		}
		$output .= '<td>'.$lang['srv_anl_suma1'].'</td>';
		$output .= '<td>'.$lang['srv_analiza_opisne_povprecje'].'</td>';
		$output .= '<td>'.$lang['srv_analiza_opisne_odklon'].'</td>';
		
				
		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 
				$output .= '<td>'.$_label.'</td>';
			}
		}
		$output .= '<td>'.$lang['srv_anl_suma1'].'</td>';				
		$output .= '<td>'.$lang['srv_analiza_opisne_povprecje'].'</td>';
		$output .= '<td>'.$lang['srv_analiza_opisne_odklon'].'</td>';

		# št enot
		$output .= '<td>'.$lang['srv_analiza_num_units'].'</td>';

		$output .= '</tr>';
		
		
		#zloopamo skozi gride in nardimo 
		$_tmp_table = array();
		$_part = 1;
		$cnt = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				if ( $_part == $grid['part'] ) {
					$cnt++;
				} else {
					$_part = $grid['part'];  
					$cnt = 1;
				}
				# zloopamo skozi variable
				if (count($grid['variables']) > 0) {
					foreach ($grid['variables'] AS $vid => $variable ) {
						$_sequence = $variable['sequence'];
						if ($variable['other'] != true) {
							# za odklon in povprečje
							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							if (count($spremenljivka['options']) > 0) {
								foreach ( $spremenljivka['options'] as $key => $kategorija) {

									$xi = $key;
									$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
									$sum_xi_fi += $xi * $fi ;
									$N += $fi;

									$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
									$_tmp_table[$grid['part']][$cnt]['variables'][] = array('freq'=>SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'],'percent'=>$_percent);
								}
							}
							
							$_tmp_table[$grid['part']][$cnt]['variable'] = $variable['variable'];
							$_tmp_table[$grid['part']][$cnt]['naslov'] = $variable['naslov'];
							$_tmp_table[$grid['part']][$cnt]['suma'] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
							$_tmp_table[$grid['part']][$cnt]['allCnt'] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
	
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
							
							$_tmp_table[$grid['part']][$cnt]['avg'] = $avg;
							$_tmp_table[$grid['part']][$cnt]['div'] = $div;
						}	//end if ($variable['other'] != true)					
					} // end foreach variables
				}
			}
		}
		
		
		
		#zlopamo skozi gride 
		if (count($_tmp_table[1]) > 0) {
			foreach ($_tmp_table[1] AS $tkey => $grid) {
				$cssBack = "anl_bck_desc_2 ";
	
				$output .= '<tr>';

				$output .= '<td>'.$grid['variable'].'</td>';
				$output .= '<td>'.$grid['naslov'].'</td>';
				
				# zloopamo skozi variable
				if (count($grid['variables']) > 0) {
					foreach ($grid['variables'] AS $vid => $variable ) {
						$output .= '<td>';
						$output .= '<table>';
						$output .= '<tr>';
						$output .= '<td>'.$variable['freq'].'</td>';
						$output .= '</tr><tr>';
						$output .= '<td>';
						$output .= self::formatNumber($variable['percent'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
						$output .= '</td>';
						$output .= '</tr></table>';
						$output .= '</td>';
					} // end foreach variables	
				}	// end if (count($grid['variables']) > 0) 
				// suma
				$output .= '<td>';
				$output .= '<table>';
				$output .= '<tr>'; 
				$output .= '<td>'.(int)$grid['suma'].'</td>';
				$output .= '</tr><tr>';
				$output .= '<td>'.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
				$output .= '</tr></table>';
				$output .= '</td>';

				// povpreje
				$output .= '<td>';
				$output .= self::formatNumber($grid['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				$output .= '</td>';
				
				// odklon
				$output .= '<td>';
				$output .= self::formatNumber($grid['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				$output .= '</td>';
				
				# dodamo desni del grida
				$_right_grid = $_tmp_table[2][$tkey];
				if (count($_right_grid['variables']) > 0) {
					foreach ($_right_grid['variables'] AS $vid => $variable ) {
						$output .= '<td>';
						$output .= '<table>';
						$output .= '<tr>';
						$output .= '<td>'.$variable['freq'].'</td>';
						$output .= '</tr><tr>';
						$output .= '<td>';
						$output .= self::formatNumber($variable['percent'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
						$output .= '</td>';
						$output .= '</tr></table>';
						$output .= '</td>';
					} // end foreach variables	
				}	// end if (count($grid['variables']) > 0) 
				// suma
				$output .= '<td>';
				$output .= '<table>';
				$output .= '<tr>'; 
				$output .= '<td>'.(int)$_right_grid['suma'].'</td>';
				$output .= '</tr><tr>';
				$output .= '<td>'.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').'</td>';
				$output .= '</tr></table>';
				$output .= '</td>';

				// povpreje
				$output .= '<td>';
				$output .= self::formatNumber($_right_grid['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				$output .= '</td>';
				
				# odklon
				$output .= '<td>';
				$output .= self::formatNumber($_right_grid['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
				$output .= '</td>';
				
				# št enot
				$output .= '<td>';
				$output .= $grid['allCnt'];
				$output .= '</td>';
				$output .= '</tr>';
			} // end foreach ($_tmp_table[1] AS $tkey => $grid)
		}
		$output .= '</table>';
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}

	/** Izriše multi number odgovore. izpiše samo povprečja
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiNumber($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		#odgovori								
		$output .= '<td colspan="'. (1 + $_cols) .'"><span><b>'.$spremenljivka['naslov'].'</b></span>';

		$output .= '</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '</td>';
		#odgovori								
		
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_subquestion'] . '</td>';

		$output .= '<td align="center" colspan="'.($_cols).'">'. $lang['srv_analiza_sums_average'] .'</td>';

		$output .= '</tr>';				
		// konec naslovne vrstice
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			$output .= '<tr>';
			$output .= '<td></td>';
			$output .= '<td></td>';

			if (count($_row['variables']) > 0 )
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
					$output .= '<td align="center">';
					// $output .= $_col['variable'];
					$output .= $_col['naslov'];
					$output .= '</td>';
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			$output .= '</tr>';

			$last = 0;
			//anl_bck_desc_2 anl_bl anl_br anl_variabla_sub 
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				$output .= '<tr>';
				$output .= '<td align="center">';

				$output .= $grid['variable'];
				$output .= '</td>';
				$output .= '<td>';
				$output .= $grid['naslov'];
				$output .= '</td>';
				
				if ($_variables_count > 0) {
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$output .= '<td align="center">';
							$output .= self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
							$output .= '</td>';
						}
					}
					
				}
				$output .= '</tr>';
			}
		}
		$output .= '</table>';	
	}
	
	/** Izriše multi number odgovore. v Navpični obliki (podobno kot opisne)
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiNumberVertical($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

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
						
						# popravimo morebitne . in -
						$fnkey = (float)$xi; 
						
						if (is_numeric($xi) && is_numeric($fnkey) && trim($fnkey) != '') {
							$fi = $_validFreq['cnt'];
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;
							
							$min = $min != null ? min($min,$fnkey) : $fnkey;
							$max = $max != null ? max($max,$fnkey) : $fnkey;
						}
					}
				}
				# povprešje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
				
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
		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		#odgovori								
		$output .= '<td colspan="7"><span><b>'.$spremenljivka['naslov'].'</b></span>';

		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '</td>';
 
		
		if ($show_enota) {
			$output .= '<td>';
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$output .= $lang['srv_analiza_opisne_subquestion'];
			} else {
				$output .= $lang['srv_analiza_opisne_variable_text'];
			}
			$output .='</td>';
		} else { # če mamo number brez labele izrisujemo drugače
			$output .= '<td>';
			$output .='</td>';
		}
		
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_m'] . '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_num_units'] .  '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_povprecje'] . '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_odklon'].'</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_min'] . '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_max'] . '</td>';
		$output .= '</tr>';

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

						$cssBrdr = (int)$grid['new_grid'] == 1 ? ' anl_double_bt' : ' anl_bt_dot';
						
						$output .= '<tr>';
						if (!$show_enota && $spremenljivka['tip'] == 7) {
							$output .= '<td>' ;
						} else {
							$output .= '<td>' ;
						}
						$output .= $_css_double_line;
						# za number (7) ne prikazujemo variable
						if ($spremenljivka['tip'] != 7 ) {
							$output .= $variable['variable'];
						}
						$output .= '</td>' ;
						if (!$show_enota && $spremenljivka['tip'] == 7) {
							$output .= '<td>' ;
						} else {
							$output .= '<td>' ;
						}
						if ($show_enota) {
							$output .= (count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov'];
						}
						$output .= '</td>' ;

						$output .= '<td>';
						$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
						$output .= '</td>';
						$output .= '<td>';
						$output .= (int)$_approp_cnt[$gid];
						$output .= '</td>';
						$output .= '<td>';
						$output .= self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''); 		
						$output .= '</td>';
						$output .= '<td>';
						$output .= self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						$output .= '</td>';
						$output .= '<td>';
						$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin']; 
						$output .= '<td>';
						$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'];; 
						$output .= '</td>';
						
						$output .= '</tr>';
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}
				
			}
		}
		$output .= '</table>';
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
	
	/** Izriše number odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumNumberVertical($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if (((int)$spremenljivka['enota'] == 0 && SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1) || $spremenljivka['tip'] == 22) {
			$show_enota = false;
		}
		$sum_avg = 0;
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
						
						# popravimo morebitne . in -
						$fnkey = (float)$xi;
						 
						if (is_numeric($xi) && is_numeric($fnkey) && trim($fnkey) != '') {
							$fi = $_validFreq['cnt'];
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;
							$min = $min != null ? min($min,$fnkey) : $fnkey;
							$max = $max != null ? max($max,$fnkey) : $fnkey;
						}
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
		
		
		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		$num_cols = 7 + ($spremenljivka['tip'] == 18 ? 1 : 0);
		#odgovori								
		$output .= '<td colspan="'. $num_cols .'"><b>'.$spremenljivka['naslov'].'</b>';

		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '</td>';
		
		if ($show_enota == true) {
			$output .= '<td align="center">';
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$output .= $lang['srv_analiza_opisne_subquestion'];
			} else {
				$output .= $lang['srv_analiza_opisne_variable_text'];
			}
			$output .='</td>';
		} else { # če mamo number brez labele izrisujemo drugače
			$output .= '<td>';
			$output .='</td>';
		}
		
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_m'] . '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_num_units'] .  '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_povprecje'] . '</td>';
		if ($spremenljivka['tip'] == 18) { 
			$output .= '<td align="center">%</td>';
		}
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_odklon'].'</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_min'] . '</td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_max'] . '</td>';
		$output .= '</tr>';

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

						$cssBrdr = (int)$grid['new_grid'] == 1 ? ' anl_double_bt' : ' anl_bt_dot';
						
						$output .= '<tr>';
						
						$output .= '<td align="center">' ;

						$output .= $_css_double_line;
						# za number (7) ne prikazujemo variable
						if ($spremenljivka['tip'] != 7 || ($show_enota == true && $spremenljivka['tip'] == 7 )) {
							if ($variable['variable'] == $spremenljivka['variable']) {
								$output .= $variable['variable'].'_1';
							} else {
								$output .= $variable['variable'];
							}
						}
						$output .= '</td>' ;
						$output .= '<td>' ;

						if ($show_enota) {
							$output .= (count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov'];
						}
						$output .= '</td>' ;

						$output .= '<td align="center">';
						$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
						$output .= '</td>';
						$output .= '<td align="center">';
						$output .= (int)$_approp_cnt[$gid];
						$output .= '</td>';
						$output .= '<td align="center">';
						$output .= self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''); 		
						$output .= '</td>';
						if ($spremenljivka['tip'] == 18) { 
							$_percent = ($sum_avg > 0 ) ? 100 * SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'] / $sum_avg : 0;
							$output .= '<td align="center">';
							$output .= self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'%');
							$output .= '</td>';
						}
						$output .= '<td align="center">';
						$output .= self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						$output .= '</td>';
						$output .= '<td align="center">';
						$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin']; 
						$output .= '<td align="center">';
						$output .= (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'];; 
						$output .= '</td>';
						
						$output .= '</tr>';
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}
				
			}
		}
		if ($spremenljivka['tip'] == 18) {
			$css_back = 'anl_bck_text_1 anl_bt';
			$output .= '<tr>';
			$output .= '<td>';
			$output .= $lang['srv_anl_suma1'];
			$output .= '</td>';
	 		$output .= '<td>&nbsp;</td>';
			
			$output .= '<td >&nbsp;</td>';
			$output .= '<td>&nbsp;</td>';
			$output .= '<td>';
			$output .= self::formatNumber($sum_avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
			$output .= '</td>';
			# skupna suma 
			$output .= '<td>100%</td>';
			$output .= '<td></td>';
			$output .= '<td></td>';
			$output .= '<td></td>';
			$output .= '</tr>';
		}
		$output .= '</table>';
		
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
	function sumNagovor($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_tip = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala'); 

		$output .= '<table border="1">';
		$output .= '<tr>';
		$output .= '<td>';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		$output .= '<td><b>'.$spremenljivka['naslov'].'</b>';

		$output .= '</td>';
		$output .= '</tr>';
		$output .= '</table>';

	}
	
	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiText($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$maxAnswer);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		#odgovori			

		$output .= '<td colspan="'. (1 + $_cols) .'"><b>'.$spremenljivka['naslov'].'</b>';

		$output .= '</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '</td>';
		#odgovori								
		
		$output .= '<td align="center">'.$lang['srv_analiza_opisne_subquestion'] . '</td>';

		$output .= '<td align="center" colspan="'.($_cols).'">'. $lang['srv_analiza_opisne_arguments'] .'</td>';

		$output .= '</tr>';				
		// konec naslovne vrstice
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			$output .= '<tr>';
			$output .= '<td></td>';
			$output .= '<td></td>';

			if (count($_row['variables'])>0)
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
					$output .= '<td align="center">';
					// $output .= $_col['variable'];
					$output .= $_col['naslov'];
					$output .= '</td>';
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			$output .= '</tr>';

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				$output .= '<tr>';
				$output .= '<td align="center" valign="top">';

				$output .= $grid['variable'];
				$output .= '</td>';
				$output .= '<td valign="top">';
				$output .= $grid['naslov'];
				$output .= '</td>';
				
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$output .= '<td>';
							$output .= '<table>';
							#$_valid_cnt = count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
							$index=0;
							if (count($_valid_answers) > 0) { 
								foreach ($_valid_answers AS $answer) {

									$index++;
									$_ans = $answer[$_sequence];
									$output .= '<tr>';
									$output .= '<td align="center">';
									if ($_ans != null && $_ans != '') {
										$output .= $_ans;
									}
									$output .= '</td>';
									$output .= '</tr>';
								}
							}

							$output .= '</table>';

							$output .= '</td>';
						}
					}
				}
				$output .= '</tr>';
			}
		}
		$output .= '</table>';
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
        
        /** Izriše lookacijske odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumLokacija($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
                $enota = $spremenljivka['enota'];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$maxAnswer);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		#odgovori			

		$output .= '<td colspan="'. ($_cols) .'"><b>'.$spremenljivka['naslov'].'</b>';

		$output .= '</td>';
		$output .= '</tr>';			
		// konec naslovne vrstice
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			$output .= '<tr>';
			$output .= '<td></td>';

			if (count($_row['variables'])>0)
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
					$output .= '<td align="center">';
					$output .= $_col['naslov'];
					$output .= '</td>';
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			$output .= '</tr>';

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				$output .= '<tr>';
				$output .= '<td>';
				$output .= '</td>';
				
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$output .= '<td>';
							$output .= '<table '. (($enota != 3) ? 'border="1"' : '') .'>';
							#$_valid_cnt = count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
							$index=0;
							if (count($_valid_answers) > 0) { 
								foreach ($_valid_answers AS $answer) {

									$index++;
									$_ans = $answer[$_sequence];
									$output .= '<tr>';
									$output .= '<td align="center">';
									if ($_ans != null && $_ans != '') {
                                                                            $_ans = html_entity_decode($_ans, ENT_NOQUOTES, 'UTF-8');
                                                                            $_ans = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$_ans);
                                                                            $output .= strip_tags($_ans, "<br>");
									}
									$output .= '</td>';
									$output .= '</tr>';
								}
							}

							$output .= '</table>';

							$output .= '</td>';
						}
					}
				}
				$output .= '</tr>';
			}
		}
		$output .= '</table>';
	}
	
	/** Izriše tekstovne odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumTextVertical($spid,$_from) {
		global $lang;
		global $output;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		#variabla
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b>';
		$output .= '</td>';
		#odgovori		
		$show_valid_percent = (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;
		$output .= '<td colspan="'. (4 + $show_valid_percent) .'"><b>'.$spremenljivka['naslov'].'</b>';

		$output .= '</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		#variabla
		$output .= '<td>';
		$output .= '</td>';
		#odgovori								
		
		$output .= '<td align="center">'.$lang['srv_analiza_frekvence_titleAnswers'] . '</td>';

		$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) {
			$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		}
		$output .= '<td align="center">'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		$output .= '</tr>';				
		// konec naslovne vrstice
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
						SurveyAnalysis::outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$options);
					}
				
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {

						$_valid_answers = SurveyAnalysis :: sortTextValidAnswers($spid,$variable,SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
						
						foreach ($_valid_answers AS $vkey => $vAnswer) {
							if ($counter < $maxAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									$options['isTextAnswer']=true;
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
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}

		$output .= '</table>';
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
			}
		}
	}
	

	function outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,&$_kumulativa,$_options=array()) {
		global $lang;
		global $output;
		
		# opcije
			
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid; 
		
 		$output .= '<tr>';
		$output .= '<td></td>';
		$output .= '<td align="left">'.$vkey;
		$output .= (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
		$output .= '</td>';	

		$output .= '<td align="center">';
		$output .= (int)$vAnswer['cnt'];
		$output .= '</td>';
		$output .= '<td align="center">';
		$output .= self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
		$output .= '</td>';
		if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']) {
			$output .= '<td align="center">';
			$output .= self::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			$output .= '</td>';
		}
		$output .= '<td align="center">';
		$output .= self::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');

		$output .= '</td align="center">';
		$output .= '</tr>';
		$counter++;
		return $counter;
	}
	
	function outputSumaValidAnswerVertical($counter,$_sequence,$spid,$_options=array()) {
		global $lang;
		global $output;
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$output .= '<tr>';
		$output .= '<td align="center">'.$lang['srv_anl_valid'].'</td>';
		$output .= '<td>'.$lang['srv_anl_suma1'].'</td>';

		$output .= '<td align="center">';

		$output .= SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0; 
		$output .= '</td>';
		$_percent = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0
			? 100 * SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']
			: 0;   
		$output .= '<td align="center">' . self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) {
			$output .= '<td align="center">' . self::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
		}
		$output .= '<td align="center"></td>';
		$output .= '</tr>';
//		$counter++;
		return $counter;
		
	}
	
	function outputInvalidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array()) {
		global $lang;	
		global $output;
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
				
		$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;
		if($_Z_MV){
			$output .= '<tr>';
			$output .= '<td></td>';
			$output .= '<td>';
			$output .= $vkey . ' (' . $vAnswer['text'].')';
			//$output .= self::formatNumber($_invalid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			//$output .= $vAnswer['cnt'];
			$output .= '</td>';

			$output .= '<td align="center">';
			$output .= (int)$vAnswer['cnt'];
			$output .= '</td>';
			$output .= '<td align="center">';
			$output .= self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			$output .= '</td>';
			if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']) {
				$output .= '<td align="center">';
				$output .= '</td>';
			}
			$output .= '<td align="center">';	
			$output .= '</td>';
			$output .= '</tr>';
		}
		
		$counter++;
		return $counter;
	}
	
	function outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$_options = array()) {
		global $lang;
		global $output;
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		
		$_sufix = (SurveyAnalysis::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine		
		if (isset(SurveyAnalysis::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.SurveyAnalysis::$_CURRENT_LOOP['cnt'].$_sufix;
		}
		
		if(!$_brez_MV){
			$output .= '<tr>';
			
			$output .= '<td align="center">'.$lang['srv_anl_missing'].'</td>';
			
			$output .= '<td>';
			$output .= $lang['srv_analiza_manjkajocevrednosti'];
			//$output .= '100.0%';
			//$output .= SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'];
			$output .= '</td>';	

			$output .= '<td align="center">';
			$answer['cnt'] =  SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
			$output .= (int)$answer['cnt'];
			$output .= '</td>';
			$output .= '<td align="center">';
			$output .= self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			$output .= '</td>';
			if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']) {
				$output .= '<td align="center">';
				$output .= '</td>';
			}
			$output .= '<td align="center"></td>';
			$output .= '</tr>';
		}
		
		$counter++;
		return $counter;
	}
	
	function outputSumaVertical($counter,$_sequence,$spid, $_options = array()) {
		global $lang;
		global $output;
		
		# opcije	

		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		
		if(!$_brez_MV){
			$output .= '<tr>';
			//$output .= '<td>&nbsp;</td>'; // $lang['srv_anl_appropriate']
			//$output .= '<td>'.$lang['srv_anl_suma2'].'</td>';
			$output .= '<td align="center">'.$lang['srv_anl_suma2'].'</td>';
			$output .= '<td></td>';
			
			$output .= '<td align="center">' . (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0) . '</td>';
			$output .= '<td align="center">' . SurveyAnalysis::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%') . '</td>';
			if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']) {
				$output .= '<td align="center"></td>';
			}
			$output .= '<td align="center"></td>';
			$output .= '</tr>';
		}
	}
	
	/** izpišemo tabelo z tekstovnimi odgovori drugo
	 * 
	 * @param $skey
	 * @param $oAnswers
	 * @param $spid
	 */
	function outputOtherAnswers($oAnswers) {
		global $lang;
		global $output;

		$output .= '<table border="0"><tr><td></td></tr></table>';
		
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];
		
		$output .= '<table border="1">';
		$output .= '<tr>';
		$output .= '<td><b>'. $_variable['variable'] . '</b></td>';
		$output .= '<td colspan="5">';
		$output .= '<span><b>'.SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )</b></span>';
		
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td>';
		$output .= '</td>';

		$output .= '<td>'. $lang['srv_analiza_frekvence_titleAnswers'] .'</td>';

		$output .= '<td>'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';
		$output .= '<td>'. $lang['srv_analiza_frekvence_titleOdstotek'] .'</td>';
		$output .= '<td>'. $lang['srv_analiza_frekvence_titleVeljavni'] .'</td>';
		$output .= '<td>'. $lang['srv_analiza_frekvence_titleKumulativa'] .'</td>';
		$output .= '</tr>';				
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

		$output .= '</table>';
	}
	
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
	
		$stringIn = array("&#269;","&#353;","&#273;","&#263;","&#382;","&#268;","&#352;","&#272;","&#262;","&#381;","&nbsp;");
		$stringOut = array("č","š","đ","ć","ž","Č","Š","Đ","Ć","Ž"," ");
	
		//$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace($stringIn, $stringOut, $text);
		return $text;
	}
	
	function enkaEncode($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return strip_tags($text);
	}	
	
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
		//$result = number_format($result, $digit, '.', ',') . $sufix;
		$result = number_format($result, $digit, ',', '') . $sufix;

		// Preprecimo da bi se stevilo z decimalko pretvorilo v datum
		//$result = '="'. $result.'"';
		
		return $result;
	}
	
}

?>