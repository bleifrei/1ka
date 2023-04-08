<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
	
/** Class za generacijo xls-ja
 *
 */
class XlsIzvozAnalizaMean {

	var $anketa;									// trenutna anketa

	private $headFileName = null;					// pot do header fajla
	private $dataFileName = null;					// pot do data fajla
	private $dataFileStatus = null;					// status data datoteke
	
	public $meansClass = null;		//means class
	
	var $meanData1;
	var $meanData2;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
		

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'mean'){
		global $site_path;
		global $global_user_id;
		global $output;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){

			$this->anketa['id'] = $anketa;
			
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

            $_GET['a'] = A_ANALYSIS;
		
            // preberemo nastavitve iz baze (prej v sessionu) 
            SurveyUserSession::Init($this->anketa['id']);
            $this->sessionData = SurveyUserSession::getData('means');
            
            // ustvarimo means objekt
            $this->meansClass = new SurveyMeans($anketa);
                    
            if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
                $this->anketa['uid'] = $global_user_id;
                SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
            }
            else
                return false;

            return true;
		}
		else{
			return false;
		}
	}

	// SETTERS && GETTERS

	function getFile($fileName)
	{
		//Close and output rtf document
		$output = $this->createXls();
		$this->xls->display($fileName, $output);
	}


	function init()
	{
		return true;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function createXls()
	{
		global $site_path;
		global $lang;
		global $output;
					
		$convertTypes = array('charSet'	=> "windows-1250",
						 'delimit'	=> ";",
						 'newLine'	=> "\n",
						 'BOMchar'	=> "\xEF\xBB\xBF");
		
		$output = $convertTypes['BOMchar'];

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_means'].'</b></font></td></tr></table>';
		
		
		$means = array();
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		//$doNewLine = SurveyTimeProfiles :: printIsDefaultProfile(false);

		# če imamo filter ifov ga izpišemo
		//$doNewLine = SurveyConditionProfiles:: getConditionString($doNewLine );

		# če imamo filter spremenljivk ga izpišemo
		//$doNewLine = SurveyVariablesProfiles:: getProfileString($doNewLine , true) || $doNewLine;
		
		
		$this->meanData1 = $this->sessionData['means_variables']['variabla1'];
		$this->meanData2 = $this->sessionData['means_variables']['variabla2'];
			
		if ($this->meanData1 !== null && $this->meanData2 !== null) {
			$variables1 = $this->meanData2;
			$variables2 = $this->meanData1;
			$c1=0;
			$c2=0;
			if (is_array($variables2) && count($variables2) > 0) {
				foreach ($variables2 AS $v_second) {
					if (is_array($variables1) && count($variables1) > 0) {
						foreach ($variables1 AS $v_first) {
							$_means = $this->meansClass->createMeans($v_first, $v_second);
							if ($_means != null) {
								$means[$c1][$c2] = $_means;
							}
							$c2++;
						}
					}
					$c1++;
					$c2=0;
				}
			}
			
			if (is_array($means) && count($means) > 0) {
			
				foreach ($means AS $mean_sub_grup) {
					
					$output .= '<table border="0"><tr><td></td></tr></table>';
					
					$this->displayMeansTable($mean_sub_grup);
				}
			}
		}		
		
		return $output;
	}	

	public function displayMeansTable($_means) {
		global $lang;
		global $output;
		
		#število vratic in število kolon
		$cols = count($_means);
		# preberemo kr iz prvega loopa
		$rows = count($_means[0]['options']);

		# ali prikazujemo vrednosti variable pri spremenljivkah
		$show_variables_values = $this->meansClass->doValues;
		# izrišemo tabelo
		$output .= '<table border="1">';

		$output .= '<tr>';

		$label2 = $this->meansClass->getSpremenljivkaTitle($_means[0]['v2']);
		$label2 = $this->encodeText($label2);
			
		$output .= '<td align="center" rowspan="2">';
		$output .= '<b>'.$label2.'</b>';
		$output .= '</td>';
		
		for ($i = 0; $i < $cols; $i++) {
			$output .= '<td align="center" colspan="2" >';
			$label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);
			$label1 = $this->encodeText($label1);
			$output .= '<b>'.$label1.'</b>';
			$output .= '</td>';
		}
		$output .= '</tr>';
		$output .= '<tr>';
		
		for ($i = 0; $i < $cols; $i++) {
			#Povprečje
			$output .= '<td align="center">';
			$output .= $lang['srv_means_label'];
			$output .= '</td>';
			#odstotki						
			$output .= '<td align="center">'.$lang['srv_means_label4'].'</td>';
		}
		$output .= '</tr>';

		if (count($_means[0]['options']) > 0) {
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {
				$output .= '<tr>';
				
				
				$output .= '<td align="center">';
				$output .= $crossVariabla2['naslov'];
				# če ni tekstovni odgovor dodamo key
				if ($crossVariabla2['type'] !== 't' ) {
					if ($show_variables_values == true) {
						if ($crossVariabla2['vr_id'] == null) {
							$output .= '&nbsp;( '.$ckey2.' )';
						} else {
							$output .= '&nbsp;( '.$crossVariabla2['vr_id'].' )';
						}
					}
				}
				$output .= '</td>';
				# celice z vsebino
				for ($i = 0; $i < $cols; $i++) {
					$output .= '<td align="center" k1="'.$ckey1.'" k2="'.$ckey2.'" n1="'.$crossVariabla1['naslov'].'" n2="'.$crossVariabla2['naslov'].'" v1="'.$crossVariabla1['vr_id'].'" v2="'.$crossVariabla2['vr_id'].'">';
					$output .= $this->meansClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'));
					$output .= '</td>';
					$output .= '<td align="center">';
					$output .= (int)$_means[$i]['sumaVrstica'][$ckey2];
					$output .= '</td>';
				}
				$output .= '</tr>';
			}
		}
		$output .= '<tr>';
		$output .= '<td align="center">'.$lang['srv_means_label3'].'</td>';
		for ($i = 0; $i < $cols; $i++) {
			$output .= '<td align="center">';
			
			$output .= $this->meansClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'));
			$output .= '</td>';
			$output .= '<td align="center">';
			$output .= (int)$_means[$i]['sumaSkupna'];
			$output .= '</td>';
		}
		$output .= '</tr>';
		$output .= '</table>';		
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
		//$result = number_format($result, $digit, ',', '.').$sufix;
		$result = number_format($result, $digit, ',', '') . $sufix;
	
		// Preprecimo da bi se stevilo z decimalko pretvorilo v datum
		//$result = '="'. $result.'"';
	
		return $result;
	}
}

?>