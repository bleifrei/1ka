<?php		

global $site_path;
	
include_once('../../function.php');
include_once('../survey/definition.php');
include_once('../exportclases/class.xls.php');	

define("RESULTS_FOLDER", "admin/survey/modules/mod_SPEEDINDEX/results", true);
	
class XlsIzvozSpeeder {		

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
			
			$_POST['podstran'] = 'speeder_resp';
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['srv_speeder_index'].'</b></font></td></tr></table>';

		$this->displayTable();	
	
		return $output;
	}
	
	
	function  displayTable(){
		global $site_path;
		global $lang;
		global $output;						
		
		$result_folder = $site_path . RESULTS_FOLDER.'/';
		
		
		$output .= '<table border="1" cellpadding="0" cellspacing="0">';

		// Izpis podatkov vsakega respondenta
		if (($handle = fopen($result_folder."speederindex".$this->anketa['id'].".csv", "r")) !== FALSE) {		
			
			$output .= '<tbody>';
			
			// Loop po vrsticah
			$cnt=0;
			while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {				
				
				$output .= '<tr>';
				
				// Prva vrstica
				if($cnt == 0){
					foreach($row as $val){
						$output .= '<th>';
						$output .= $val;
						$output .= '</th>';				
					}					
				}
				// Vrstice s podatki
				else{
					foreach($row as $val){
						$output .= '<td>';
						$output .= $val;
						$output .= '</td>';				
					}
				}
				
				$output .= '</tr>';
				
				$cnt++;
			}
			fclose($handle);
			
			$output .= '</tbody>';
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