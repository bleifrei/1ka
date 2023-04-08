<?php		

global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
class XlsIzvozList {		

	var $anketa;						// trenutna anketa
	var $pi=array('canCreate'=>false); 	// za shrambo parametrov in sporocil

	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke

    
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $sprID = null){
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
			
			// Nastavimo da izpisujemo samo prvih 5 spremenljivk
            $_GET['spr_limit'] = 'all';
            
			// Nastavimo da nikoli ne izpisemo vabila
			$_GET['email'] = 0;
			SurveyDataDisplay::Init($this->anketa['id']);
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_list'].'</b></font></td></tr></table>';
									
		
		$this->displayTable();	

		
		return $output;
	}
	
	
	function  displayTable(){
		global $site_path;
		global $lang;
		global $output;
		
		
		$folder = $site_path . EXPORT_FOLDER.'/';

		//polovimo podatke o nastavitvah trenutnega profila (missingi..)
		SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);

		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		SurveyDataDisplay::$_VARS[VAR_DATA] = 1;
		SurveyDataDisplay::$_VARS[VAR_SPR_LIMIT] = 5;
		SurveyDataDisplay::$_VARS[VAR_META] = 0;
		SurveyDataDisplay::$_VARS[VAR_EMAIL] = 0;
		SurveyDataDisplay::$_VARS[VAR_RELEVANCE] = 0;
		SurveyDataDisplay::$_VARS[VAR_EDIT] = 0;
		SurveyDataDisplay::$_VARS[VAR_PRINT] = 0;
		SurveyDataDisplay::$_VARS[VAR_MONITORING] = 0;

		if(SurveyDataDisplay::$_VARS['view_date'])
			SurveyDataDisplay::$_VARS[VAR_SPR_LIMIT]++;
		
		# ponastavimo nastavitve- filter
		SurveyDataDisplay::setUpFilter();
		
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
						
		
		$output .= '<table border="1" cellpadding="0" cellspacing="0">';
		$output .= '<thead>';
		$output .= '<tr>';

		
		// Vrstica z naslovi spremenljivk
		if(SurveyDataDisplay::$_VARS['view_date']){
			$output .= '<th>';
			$output .= $lang['srv_data_date'];
			$output .= '</th>';
		}	
		$spr_cont = 0;
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]))) {
					
					// 	prikazemo samo prvih 5 spremenljivk
					if ($spr_cont < 5) {
						$output .= '<th colspan="'.$spremenljivka['cnt_all'].'">';
						$output .= $spremenljivka['naslov'];
						$output .= '</th>';
					}
					$spr_cont++;
				}
			}
		}
		
		$output .= '</tr><tr>';

		// Vrstica imeni variabel
		if(SurveyDataDisplay::$_VARS['view_date']){
			$output .= '<th>';
			$output .= $lang['srv_data_date'];
			$output .= '</th>';
		}
		$spr_cont = 0;
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if ($spr_cont < 5) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							$output .= '<th colspan="'.$grid['cnt_vars'].'">';
							$output .= $grid['naslov'];
							$output .= '</th>';
						}
					}
					$spr_cont++;
				}
			}
		}
		$output .= '</tr><tr>';
		

		if(SurveyDataDisplay::$_VARS['view_date']){
			$output .= '<th>';
			$output .= $lang['srv_data_date'];
			$output .= '</th>';
		}
		$spr_cont = 0;
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if($spr_cont < 5) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							if (count ($grid['variables']) > 0) {
								foreach ($grid['variables'] AS $vid => $variable ){
									
									$output .= '<th>';

									$output .= $variable['naslov'];
									
									if ($variable['other'] == 1)
										$output .= '&nbsp;(text)';

									$output .= '</th>';
								}
							}
						}
					}
					$spr_cont++;
				}
			}
		}
		$output .='</tr>';
		$output .= '</thead>';

		
		// Nastavimo stevilo izpisov - prikazemo vse
		$_REC_LIMIT = '';
		//$_REC_LIMIT = ' NR==1,NR==50';			
		
		
		$_command = '';
		#preberemo podatke
		// polovimo vrstice z statusom 5,6 in jih damo v začasno datoteko
		if (IS_WINDOWS) {
			$_command = 'gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.SurveyDataDisplay::$_CURRENT_STATUS_FILTER.' { print $0 }" '.$this->dataFileName;
		}
		else {
			$_command = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.SurveyDataDisplay::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.$this->dataFileName;
		}

		// paginacija po stolpcih (spremenljivkah)
		if (IS_WINDOWS) {
			$_command .= ' | cut -d "|" -f 1,'.SurveyDataDisplay::$_VARIABLE_FILTER;
		} else {
			$_command .= ' | cut -d \'|\' -f 1,'.SurveyDataDisplay::$_VARIABLE_FILTER;
		}

		if ($_REC_LIMIT != '') {
			#paginating
			if (IS_WINDOWS) {
				$_command .= ' | awk '.$_REC_LIMIT;
			} else {
				$_command .= ' | awk '.$_REC_LIMIT;
			}
		} else {
			#$file_sufix = 'filtred_spr_pagination';
		}

		// zamenjamo | z </td><td> - NI POTREBNO
		if (IS_WINDOWS) {
			//$_command .= ' | sed "s*'.STR_DLMT.'*'.STR_LESS_THEN.'/td'.STR_GREATER_THEN.STR_LESS_THEN.'td'.STR_GREATER_THEN.'*g" >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
			$_command .= ' >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
		} 
		else {
			//$_command .= ' | sed \'s*'.STR_DLMT.'*</td><td>*g\' >> '
			//.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;	
			$_command .= ' >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;	
		}

		if (IS_WINDOWS) {
			# ker so na WINsih težave z sortom, ga damo v bat fajl in izvedemo :D
			$file_handler = fopen($folder.'cmd_'.$this->anketa['id'].'_to_run.bat',"w");
			fwrite($file_handler,$_command);
			fclose($file_handler);
			$out_command = shell_exec($folder.'cmd_'.$this->anketa['id'].'_to_run.bat');
			unlink($folder.'cmd_'.$this->anketa['id'].'_to_run.bat');
		} else {
			$out_command = shell_exec($_command);
		}

		$output .= '<tbody>';
		if (file_exists($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT)) {
			$f = fopen ($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT, 'r');

			while ($line = fgets ($f)) {
				$output .= '<tr>';

				$dataArray = array();
				$dataArray = explode('|', $line);
				
				// Ne upostevamo prve vrednosti (ID)
				array_shift($dataArray);

				foreach($dataArray as $key => $val){
					$output .= '<td>'.$val.'</td>';
				}
				
				$output .= '</tr>';
			}
		} else {
			$output .= 'File does not exist (err.No.1)! :'.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
		}
		$output .= '</tbody>';
		
		if ($f) {
			fclose($f);
		}
		if (file_exists($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT)) {
			unlink($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT);
		}
		
		
		$output .= '</table>';
	}
	
	
	function encodeText($text)
	{ 
		// popravimo sumnike ce je potrebno
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

		return $result;
	}
	
}

?>