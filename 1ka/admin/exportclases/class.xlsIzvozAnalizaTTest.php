<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
	
/** Class za generacijo xls-ja
 *
 */
class XlsIzvozAnalizaTTest {

	var $anketa;									// trenutna anketa

	private $headFileName = null;					// pot do header fajla
	private $dataFileName = null;					// pot do data fajla
	private $dataFileStatus = null;					// status data datoteke
	
	public $ttestClass = null;		//ttest class
	
	var $ttestData1;
	var $ttestData2;

	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...

	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'ttest')
	{
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
            $this->sessionData = SurveyUserSession::getData('ttest');
            
            // ustvarimo ttest objekt
            $this->ttestClass = new SurveyTTest($this->anketa['id']);
            
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

		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['export_analisys_ttest'].'</b></font></td></tr></table>';

		if (count($this->sessionData['sub_conditions']) > 1 ) {
			$variables1 = $this->ttestClass->getSelectedVariables();
			if (count($variables1) > 0) {
				foreach ($variables1 AS $v_first) {
					$ttest = null;
					$ttest = $this->ttestClass->createTTest($v_first, $this->sessionData['sub_conditions']);

					$output .= '<table border="0"><tr><td></td></tr></table>';
					
					$this->displayTTestTable($ttest);
				}
			}
		}
		
		return $output;
	}	

	public function displayTTestTable($ttest) {
		global $lang;
		global $output;

		# preverimo ali imamo izbrano odvisno spremenljivko
		$spid1 = $this->sessionData['variabla'][0]['spr'];
		$seq1 = $this->sessionData['variabla'][0]['seq'];
		$grid1 = $this->sessionData['variabla'][0]['grd'];

		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {

			$spr_data_1 = $this->ttestClass->_HEADERS[$spid1];
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
			$spid2 = $this->sessionData['spr2'];
			$sprLabel2 =  trim(str_replace('&nbsp;','',$this->sessionData['label2']));
			$label1 = $this->ttestClass->getVariableLabels($this->sessionData['sub_conditions'][0]);
			$label2 = $this->ttestClass->getVariableLabels($this->sessionData['sub_conditions'][1]);
			$output .= '<table border="1">';
			$output .= '<tr>';
			#labele
			$output .= '<td align="center" rowspan="2">';
			$output .= '<b>'.$sprLabel2.'</b>';
			$output .= '</td>';
				
			$output .= '<td align="center" colspan="9">';
			$output .= '<b>'.$sprLabel1.'</b>';
			$output .= '</td>';
			$output .= '</tr>';
			$output .= '<tr>';
			#$output .= '<th align="center" colspan="2">&nbsp;</th>';
			#frekvenca
			$output .= '<th align="center" >n</th>';
			#povprečje
			$output .= '<th align="center"><span class="avg">x</span></th>';
			#varianca
			$output .= '<th align="center">s&#178;</th>';
			#standardna napaka
			$output .= '<th align="center">se(<span class="avg">x</span>)</th>';
			#margini
			$output .= '<th align="center">&#177;1,96&#215;se(<span class="avg">x</span>)</th>';
			#d
			$output .= '<th align="center">d</th>';
			#sed
			$output .= '<th align="center">se(d)</th>';
			#signifikanca
			$output .= '<th align="center">Sig.</th>';
			#ttest
			$output .= '<th align="center">t</th>';
			$output .= '</tr>';

			$output .= '<tr>';

			#labele
				
			$output .= '<td align="center">'.$label1.'</td>';
			#frekvenca
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[1]['n'],0).'</td>';
			#povprečje
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[1]['x'],3).'</td>';
			#varianca
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[1]['s2'],3).'</td>';
			#standardna napaka
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[1]['se'],3).'</td>';
			#margini
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[1]['margin'],3).'</td>';
			#d
			$output .= '<td align="center" rowspan="2">'.$this->ttestClass->formatNumber($ttest['d'],3).'</td>';
			#sed
			$output .= '<td align="center" rowspan="2">'.$this->ttestClass->formatNumber($ttest['sed'],3).'</td>';
			#signifikanca
			$output .= '<td align="center" rowspan="2">'.$this->ttestClass->formatNumber($ttest['sig'],3).'</td>';
			#ttest
			$output .= '<td align="center" rowspan="2">'.$this->ttestClass->formatNumber($ttest['t'],3).'</td>';
			$output .= '</tr>';

			$output .= '<tr>';
			#labele
			$output .= '<td align="center">'.$label2.'</td>';
			#frekvenca
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[2]['n'],0).'</td>';
			#povprečje
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[2]['x'],3).'</td>';
			#varianca
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[2]['s2'],3).'</td>';
			#standardna napaka
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[2]['se'],3).'</td>';
			#margini
			$output .= '<td align="center">'.$this->ttestClass->formatNumber($ttest[2]['margin'],3).'</td>';
			$output .= '</tr>';
			$output .= '</table>';
		}
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