<?php		

global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
class XlsIzvozUsable {		

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
			
			$_POST['podstran'] = 'usable_resp';
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
		if (SurveyInfo::getInstance()->SurveyInit($this->anketa['id'])){
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}
		else
            return false;
            
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	function getAnketa(){
		return $this->anketa['id']; 
	}

	function checkCreate(){
		return $this->pi['canCreate'];
	}

	function getFile($fileName){
		$output = $this->createXls();
		$this->xls->display($fileName, $output);
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['srv_usable_respondents'].'</b></font></td></tr></table>';
									
		
		$this->displayTable();	

		
		return $output;
	}
	
	
	function  displayTable(){
		global $site_path;
		global $lang;
		global $output;						
		
		$SUR = new SurveyUsableResp($this->anketa['id'], $generateDataFile=false);
		$usability = $SUR->calculateData();
		
		
		# ali odstranimo stolpce kateri imajo same 0
		$cols_with_value = $SUR->getColsWithValue();
		$_missings = $SUR->getMissings();
		$_unsets = $SUR->getUnsets();
		if ($SUR->showWithZero() == false) {
			# odstranimo missinge brez vrednosti
			foreach ($SUR->getMissings() AS $_key => $_missing) {
				if (!isset($cols_with_value[$_key]) || $cols_with_value[$_key] == false) {
					unset($_missings[$_key]);
				}
			}
			# odstranimo neveljavne brez vrednosti
			foreach ($SUR->getUnsets() AS $_key => $_unset) {
				if (!isset($cols_with_value[$_key]) || $cols_with_value[$_key] == false) {
					unset($_unsets[$_key]);
				}
			}
		}
		
		
		$output .= '<table border="1" cellpadding="0" cellspacing="0">';
		
		// Header rows
		$output .= '<thead>';
		$output .= '<tr>';
		
		$output .= '<th rowspan="2">Recnum</th>';
		$output .= '<th rowspan="2">'.$lang['srv_usableResp_qcount'].'</th>';
		
		$output .= '<th colspan=4>'.$lang['srv_usableResp_exposed'].'</th>';
		
		$output .= '<th rowspan="2">'.$lang['srv_usableResp_breakoff'].'</th>';
		
		$output .= '<th colspan="2">'.$lang['srv_usableResp_usability'].'</th>';
		
		// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
		if ($SUR->showDetails() == true) {
			foreach ($_missings AS $value => $text){
				$cnt_miss++;
				$output .= "<th rowspan=\"2\">{$value}<br/>(".$lang['srv_usableResp_'.$text].")</th>";
			}
			foreach ($_unsets AS $value => $text){
				$cnt_undefined++;
				$output .= "<th rowspan=\"2\">{$value}<br/>(".$lang['srv_usableResp_'.$text].")</th>";
			}
		}
		
		// ali prikazemo podrobne izracune
		if ($SUR->showCalculations() == true) {
			$output .= '<th rowspan="2">UNL</th>';
			$output .= '<th rowspan="2">UML</th>';
			$output .= '<th rowspan="2">UCL</th>';
			$output .= '<th rowspan="2">UIL</th>';
			$output .= '<th rowspan="2">UAQ</th>';			
		}
		$output .= '</tr>';
		
		$output .= '<tr>';
		$output .= '<th>'.$lang['srv_anl_valid'].'</th>';
		$output .= '<th>'.$lang['srv_usableResp_nonsubstantive'].'</th>';
		$output .= '<th>'.$lang['srv_usableResp_nonresponse'].'</th>';
		$output .= '<th>'.$lang['srv_anl_suma1'].'</th>';
		
		$output .= '<th>%</th>';
		$output .= '<th>Status</th>';
		$output .= '</tr>';
		$output .= '</thead>';

		
		// Data rows
		$output .= '<tbody>';
		
		// Izpis podatkov vsakega respondenta
		$userData = $usability['data'];
		foreach($userData as $key => $user){
			
			// Obarvamo vrstico glede na status (belo, rumeno, rdece)
			if($user['status'] == 0)
				$css_usable = 'unusable';
			elseif($user['status'] == 1)
				$css_usable = 'partusable';
			else
				$css_usable = 'usable';
				
						
			// Prva vrstica z vrednostmi
			$output .= '<tr>';

			$output .= '<td rowspan="2">'.$user['recnum'].'</td>';
			
			// Vsi
			$output .= '<td rowspan="2">'.$user['all'].'</td>';
			
			// Ustrezni
			$output .= '<td>'.$user['valid'].'</td>';
			
			// Non-substantive			
			$output .= '<td>'.$user['nonsubstantive'].'</td>';
			
			// Non-response
			$output .= '<td>'.$user['nonresponse'].'</td>';
			
			// Skupaj	
			$output .= '<td>'.($user['valid']+$user['nonsubstantive']+$user['nonresponse']+$user['breakoff']).'</td>';
			
			// Breakoffs	
			$output .= '<td>'.$user['breakoff'].'</td>';
			
			// Uporabni		
			$output .= '<td>'.$user['usable'].'</td>';
			$output .= '<td rowspan="2">'.$user['status'].'</td>';
			
			// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
			if ($SUR->showDetails() == true) {
				foreach ($_missings AS $value => $text){
					$output .= '<td>'.$user[$value].'</td>';
				}
				foreach ($_unsets AS $value => $text){
					$output .= '<td>'.$user[$value].'</td>';
				}
			}
			
			// ali prikazemo podrobne izracune
			if ($SUR->showCalculations() == true) {				
				$output .= '<td rowspan="2">'.common::formatNumber($user['UNL']*100, 0, null, '%').'</td>';
				$output .= '<td rowspan="2">'.common::formatNumber($user['UML']*100, 0, null, '%').'</td>';
				$output .= '<td rowspan="2">'.common::formatNumber($user['UCL']*100, 0, null, '%').'</td>';
				$output .= '<td rowspan="2">'.common::formatNumber($user['UIL']*100, 0, null, '%').'</td>';
				$output .= '<td rowspan="2">'.common::formatNumber($user['UAQ']*100, 0, null, '%').'</td>';			
			}
			
			$output .= '</tr>';
			
			
			// Druga vrstica s procenti
			$output .= '<tr>';

			// Ustrezni
			$output .= '<td>'.common::formatNumber($user['validPercent'], 0, null, '%').'</td>';
				
			// Non-substantive			
			$output .= '<td>'.common::formatNumber($user['nonsubstantivePercent'], 0, null, '%').'</td>';
			
			// Non-response
			$output .= '<td>'.common::formatNumber($user['nonresponsePercent'], 0, null, '%').'</td>';
						
			// Skupaj	
			$output .= '<td>'.common::formatNumber(100, 0, null, '%').'</td>';
	
			// Breakoffs	
			$output .= '<td>'.common::formatNumber($user['breakoffPercent'], 0, null, '%').'</td>';
	
			// Uporabni		
			$output .= '<td class="usable">'.common::formatNumber($user['usablePercent'], 0, null, '%').'</td>';
			
			// ali odstranimo vse stolpce s podrobnimi vrednostmi (-1, -2...)
			if ($SUR->showDetails() == true) {
				foreach ($_missings AS $value => $text){
					$val = $user[$value];
					$val = ($all > 0) ? ($val / $all * 100) : 0;
					$output .= '<td>'.common::formatNumber($val, 0, null, '%').'</td>';
				}
				foreach ($_unsets AS $value => $text){
					$val = $user[$value];
					$val = ($all > 0) ? ($val / $all * 100) : 0;
					$output .= '<td>'.common::formatNumber($val, 0, null, '%').'</td>';
				}
			}

			$output .= '</tr>';
		}
		
		$output .= '</tbody>';
		
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