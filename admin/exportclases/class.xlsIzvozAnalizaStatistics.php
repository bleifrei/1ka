<?php		

global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
class XlsIzvozAnalizaStatistics {		

	var $anketa;						// trenutna anketa
	var $pi=array('canCreate'=>false); 	// za shrambo parametrov in sporocil

	private $headFileName = null;					# pot do header fajla
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_desc'].'</b></font></td></tr></table>';
		$output .= '<table border="0"><tr><td></td></tr></table>';
		
		if ($this->headFileName !== null ) {
			
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
	
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));

		# odstranimo sistemske variable tipa email, ime, priimek, geslo
		SurveyAnalysis::removeSystemVariables();

		# polovimo frekvence			
		SurveyAnalysis::getDescriptives();

		# izpišemo opisne statistike
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		$line_break = '';
		$output .= '<table border="1">';
		
		$output .= '<tr>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_variable'] .'<span>'.'</span></td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_variable_text'] .'<span>'.'</span></td>';

		$output .= '<td align="center">' . $lang['srv_analiza_opisne_m'] .'<span >'.'</span></td>';
		$output .= '<td align="center">' . $lang['srv_analiza_num_units'] .'<span >'.'</span></td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_povprecje_odstotek'] .'<span >'.'</span></td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_odklon'] .'<span >'.'</span></td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_min'] .'<span >'.'</span></td>';
		$output .= '<td align="center">' . $lang['srv_analiza_opisne_max'] .'<span >'.'</span></td>';
		$output .= '</tr>';
		
		# dodamo še kontrolo če kličemo iz displaySingleVar 
		if (isset($_spid) && $_spid !== null) {
			SurveyAnalysis::$_HEADERS = array($_spid => SurveyAnalysis::$_HEADERS[$_spid]);
		}

		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm'
			 && ( count(SurveyAnalysis::$_FILTRED_VARIABLES) == 0 || (count(SurveyAnalysis::$_FILTRED_VARIABLES) > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ))
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES) 
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ){

				$show_enota = false;
				# preverimo ali imamo samo eno variablo in če iammo enoto
				if ((int)$spremenljivka['enota'] != 0 || $spremenljivka['cnt_all'] > 1 ) {
					$show_enota = true;
				}
				
				# izpišemo glavno vrstico z podatki
				$_sequence  = null;
				# za enodimenzijske tipe izpišemo podatke kar v osnovni vrstici
				if (!$show_enota) {  
//				 	if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3  
//				 		|| $spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					self::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
				} else {
				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$show_enota = false;
				}
					self::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
					#zloopamo skozi variable
					$_sequence = null;
					$grd_cnt=0;
					if (count($spremenljivka['grids']) > 0)				 	
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						
						if (count($spremenljivka['grids']) > 1 && $grd_cnt !== 0 && $spremenljivka['tip'] != 6) {
							$grid['new_grid'] = true;
						}
						$grd_cnt++;
						$var_cnt=0;
						# dodamo dodatne vrstice z albelami grida
						if (count ($grid['variables']) > 0)
						foreach ($grid['variables'] AS $vid => $variable ){
							# dodamo ostale vrstice
							$do_show = ($variable['other'] !=1 && ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || $spremenljivka['tip'] == 5 || $spremenljivka['tip'] == 8 )) 
								? false
								: true;
								if ($do_show) {
									$variable['var_cnt'] = $var_cnt;
									self::displayDescriptivesVariablaRow($spremenljivka,$grid,$variable,$_css);
								}
							$grid['new_grid'] = false;
							$var_cnt++;
						}
					}
				 } //else: if (!$show_enota)
			 } // end if $spremenljivka['tip'] != 'm'
			 #ob_flush(); flush();
		} // end foreach  SurveyAnalysis::$_HEADERS
		$output .= '</table >';
	}
	
	
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	function displayDescriptivesVariablaRow($spremenljivka,$grid,$variable=null) {
		global $lang;
		global $output;
		
		$cssBack = $variable['other'] != 1 ? ' anl_bck_desc_2' : ' anl_bck_desc_3';
		$cssMove = $variable['other'] != 1 ? ' anl_tin' : ' anl_tin1';
		$cssBack .= (int)$grid['new_grid'] == 1 ? ' anl_bt ' : ' anl_bt_dot ';
		$_sequence = $variable['sequence'];	# id kolone z podatki
		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		# če smo na začetku grida dodamo podatke podvprašanja
		if ($variable['var_cnt'] == 0 && in_array($spremenljivka['tip'],array(16,19,20) ) ) {
			$output .= '<tr>';
			$output .= '<td align="center">';
			$output .= $grid['variable'];
			$output .= '</td>';
			$output .= '<td colspan="7">';
			$output .= $grid['naslov'];
			$output .= '</td>';

			$output .= '</tr>';
		}
		$output .= '<tr>';
		$output .= '<td align="center">';
		$output .= $variable['variable'];
		$output .= '</td>';
		$output .= '<td align="left">';
		//$output .= $grid['naslov'] . ' - ' .$variable['naslov'];
		$output .= $variable['naslov'];
		$output .= '</td>';

		#veljavno
		$output .= '<td align="center">'.(int)$_desc['validCnt'].'</td>';
		#ustrezno
		$output .= '<td align="center">'.(int)$_desc['allCnt'].'</td>';
		$output .= '<td align="center">';
		if ( isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1 ) { 
			$output .= self::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		} else if (isset($_desc['avg']) && $spremenljivka['tip'] == 2 && (int)$spremenljivka['skala'] == 1 ) {
			$output .= self::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%');
		}
		$output .= '</td>';
		$output .= '<td align="center">';
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1) {
			$output .= self::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
		}
		$output .= '</td>';
		$output .= '<td align="center">'.((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '').'</td>';
		$output .= '<td align="center">'.((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '').'</td>';

		$output .= '</tr>';

	}
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	function displayDescriptivesSpremenljivkaRow($spid,$spremenljivka,$show_enota,$_sequence = null) {
		global $lang;
		global $output;
		
		$cssBack = " anl_bck_desc_1";
		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		$output .= '<tr>';
		$output .= '<td align="center">';
		$output .= '<b>'.$spremenljivka['variable'].'</b></td>';
		$output .= '<td>';
		$output .= '<b>'.($spremenljivka['naslov']) . '</b></td>';

		#veljavno
		$output .= '<td align="center">'.(!$show_enota ? (int)$_desc['validCnt'] : '&nbsp;') .'</td>';
		#ustrezno
		$output .= '<td align="center">'.(!$show_enota ? (int)$_desc['allCnt'] : '&nbsp;').'</td>';
		
		$output .= '<td align="center">';
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1) {
			$output .= self::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		}
		$output .= '</td>';
		$output .= '<td align="center">';
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1) {
			$output .= self::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
		}
		$output .= '</td>';
		$output .= '<td align="center">'.((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '').'</td>';
		$output .= '<td align="center">'.((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '').'</td>';

		$output .= '</tr>';
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