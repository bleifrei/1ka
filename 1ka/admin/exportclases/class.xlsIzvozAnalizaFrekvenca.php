<?php		

global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
class XlsIzvozAnalizaFrekvenca {		

	var $anketa;						// trenutna anketa
	var $pi=array('canCreate'=>false); 	// za shrambo parametrov in sporocil

	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $CID = null;							# class za inkrementalno dodajanje fajlov
	
	var $current_loop = 'undefined';
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $sprID = null, $loop = null)
	{
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_freq'].'</b></font></td></tr></table>';
									
		# preberemo header
		if ($this->headFileName !== null) {
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
			
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
	
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		# polovimo frekvence			
		SurveyAnalysis::getFrequencys();
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {

					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1: # radio - prikjaže navpično					
						case 2: #checkbox  če je dihotomna:
						case 3: # dropdown - prikjaže navpično					
						case 6: # multigrid
						case 4:	# text
						case 7:# variabla tipa »število«
						case 8:	# datum
						case 16: #multicheckbox če je dihotomna:
						case 17: #razvrščanje  če je ordinalna 
						case 18: # vsota 
						case 19: # multitext
						case 20: # multi number
						case 21: # besedilo* 
						case 22: # kalkulacija
                                                case 26: # lokacija
							self::frequencyVertical($spid);
						break;
						case 5:
							# nagovor
							//pdfIzvozAnalizaSums::sumNagovor($spid,'freq');
						break;
						
					}

				} 
					
			} // end if $spremenljivka['tip'] != 'm'

		} // end foreach SurveyAnalysis::$_HEADERS
	}
	
	
	/** Izriše frekvence v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function frequencyVertical($spid) {
		global $lang;
		global $output;

		$output .= '<table border="0"><tr><td></td></tr></table>';
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
				
		# tekst vprašanja
		$output .= '<table border="1">';
		# naslovna vrstica				
		$output .= '<tr>';
		$output .= '<td align="center"><b>'.$spremenljivka['variable'].'</b></td>';
		#odgovori								
		$output .= '<td colspan="'.(SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true ? '5' : '4').'"><b>'.$spremenljivka['naslov'].'</b>';
		$output .= '</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		#odgovori								
		$output .= '<td></td>';
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
		
		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

		# izpišemo vlejavne odgovore
		$_current_grid = null;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
			
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){

				$_sequence = $variable['sequence'];	# id kolone z podatki
				if (($variable['text'] != true && $variable['other'] != true) 
				|| (in_array($spremenljivka['tip'],array(4,8,21,22,26)))){
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
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					
					#po potrebi posortiramo podatke
					if ($spremenljivka['tip'] == 7 && is_array(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])) {
						ksort(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']); 
					}
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						# tekstovne odgovore posortiramo kronološko
						if ($spremenljivka['tip'] == 21 || $spremenljivka['tip'] == 4) {
							$_valid_answers = SurveyAnalysis :: sortTextValidAnswers($spid,$variable,SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
						} else {
							$_valid_answers = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'];
						}
						foreach ($_valid_answers AS $vkey => $vAnswer) {
							//if ($counter < $maxAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21,26))) { // text, number, datum, mtext, mnumber, text* 
										$options['isTextAnswer'] = true;
									} else {
										$options['isTextAnswer'] = false;
									}
									$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								}
							//}
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

	function outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$_options = array()) {
		global $lang;
		global $output;
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'doubleTop'	=>false,		# ali imamo novo grupa in nardimo dvojni rob
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$output .= '<tr>';
		
		$output .= '<td align="center">'.$variable['variable'].'</td>';
		
		$output .= '<td align="left">';
		// $output .= $grid['naslov'] . ' - ' .$variable['naslov'];
		$output .= $variable['naslov'];
		$output .= '</td>';
		
		$output .= '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		$output .= '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		if (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) {
			$output .= '<td class="anl_bb anl_br '.$css_bck.' anl_w70">&nbsp;</td>';
		}
		$output .= '<td class="anl_bb '.$css_bck.' anl_w70">&nbsp;</td>';
		$output .= '</tr>';
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
		$output .= '<td align="left">'.strip_tags($vkey, "<br>");
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
			//$output .= '<td class="anl_bl anl_ac anl_dash_bt anl_br anl_bb gray">&nbsp;</td>'; // $lang['srv_anl_appropriate']
			//$output .= '<td class="anl_al anl_dash_bt anl_br anl_bb red anl_ita'.$cssBck.'">'.$lang['srv_anl_suma2'].'</td>';
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
		$output .= '<td>'. $_variable['variable'] . '</td>';
		$output .= '<td colspan="5">';
		$output .= '<span>'.SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )</span>';
		
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