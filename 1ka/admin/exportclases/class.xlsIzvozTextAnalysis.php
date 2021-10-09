<?php		

global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.xls.php');	
	
class XlsIzvozTextAnalysis {		

	var $anketa;							// trenutna anketa
	var $pi = array('canCreate'=>false); 	// za shrambo parametrov in sporocil
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null){
		global $site_path;
		global $global_user_id;
		global $output;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){
			
			$this->anketa['id'] = $anketa;
			
			// create new XLS document
			$this->xls = new xls();
						
			$_POST['podstran'] = 'text_analysis';
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
		if (SurveyInfo::getInstance()->SurveyInit($this->anketa['id'])){
			$this->anketa['uid'] = $global_user_id;
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
		
		// Dobimo podatke
		$STA = new SurveyTextAnalysis($this->anketa['id']);
		$data = $STA->getData();
		
		// Tabela vsot znakov po straneh
		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['srv_text_analysis_title1'].'</b></font></td></tr></table>';						
		$this->displayGrupaTable($data);	

		$output .= '<table border="0"><tr><td></td></tr></table>';
		$output .= '<table border="0"><tr><td colspan="10"><font size="3"><b>'.$lang['srv_text_analysis_title2'].'</b></font></td></tr></table>';	
		
		// Tabela znakov po vprasanjih in vrednostih
		$this->displaySprTable($data);		
		
		return $output;
	}
	
	function  displayGrupaTable($data){
		global $site_path;
		global $lang;
		global $output;		
		
		$output .= '<table border="1" cellpadding="0" cellspacing="0">';
		
		// Header row
		$output .= '<tr>';
		
		$output .= '<th style="width:120px;">'.$lang['srv_text_analysis_page'].'</th>';
		$output .= '<th style="width:120px;">'.$lang['srv_text_analysis_question'].'</th>';
		
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_charCount'].'</th>';
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_charCountBlank'].'</th>';
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_charCountHTML'].'</th>';
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_wordCount'].'</th>';
	
		$output .= '</tr>';	

		// Loop po straneh v anketi
		foreach($data['grupe'] as $gru_id => $grupa){
			
			// Loop po vprasanjih na strani
			foreach($grupa['spremenljivke'] as $spr_id => $spremenljivka){
			
				$output .= '<tr>';

				$output .= '<td align="center">'.$grupa['naslov'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['variable'].'</td>';
				
				$output .= '<td align="center">'.$spremenljivka['sum_char_count'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['sum_char_count_noBlank'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['sum_char_count_html'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['sum_word_count'].'</td>';

				$output .= '</tr>';
			}		
			
			// Vsota znakov na strani
			$output .= '<tr class="sum">';

			$output .= '<td align="center">'.$grupa['naslov'].'</td>';
			$output .= '<td align="center">'.$lang['srv_text_analysis_sum'].'</td>';
			
			$output .= '<td align="center">'.$grupa['sum_char_count'].'</td>';
			$output .= '<td align="center">'.$grupa['sum_char_count_noBlank'].'</td>';
			$output .= '<td align="center">'.$grupa['sum_char_count_html'].'</td>';
			$output .= '<td align="center">'.$grupa['sum_word_count'].'</td>';

			$output .= '</tr>';
		}
		
		// Vsota znakov v anketi
		$output .= '<tr class="sum">';

		$output .= '<td align="center"></td>';
		$output .= '<td align="center">'.$lang['srv_text_analysis_sumSurvey'].'</td>';
		
		$output .= '<td align="center">'.$data['sum_char_count'].'</td>';
		$output .= '<td align="center">'.$data['sum_char_count_noBlank'].'</td>';
		$output .= '<td align="center">'.$data['sum_char_count_html'].'</td>';
		$output .= '<td align="center">'.$data['sum_word_count'].'</td>';

		$output .= '</tr>';
			
		$output .= '</table>';
	}
	
	function  displaySprTable($data){
		global $site_path;
		global $lang;
		global $output;						
		
		$output .= '<table border="1" cellpadding="0" cellspacing="0">';
		
		// Header row
		$output .= '<tr>';
		
		$output .= '<th style="width:120px;">'.$lang['srv_text_analysis_page'].'</th>';
		$output .= '<th style="width:120px;">'.$lang['srv_text_analysis_question'].'</th>';
		$output .= '<th style="width:120px;">'.$lang['srv_text_analysis_value'].'</th>';
		$output .= '<th>'.$lang['srv_text_analysis_text'].'</th>';
		
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_charCount'].'</th>';
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_charCountBlank'].'</th>';
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_charCountHTML'].'</th>';
		$output .= '<th style="width:130px;">'.$lang['srv_text_analysis_wordCount'].'</th>';
	
		$output .= '</tr>';	

		// Loop po straneh v anketi
		foreach($data['grupe'] as $gru_id => $grupa){
			
			// Loop po vprasanjih na strani
			foreach($grupa['spremenljivke'] as $spr_id => $spremenljivka){
			
				$output .= '<tr class="colored">';

				$output .= '<td align="center">'.$grupa['naslov'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['variable'].'</td>';
				$output .= '<td></td>';
				
				$naslov = strip_tags($spremenljivka['naslov']);
				$naslov = ($spremenljivka['char_count'] > 50) ? substr($naslov, 0, 47).'...' : $naslov; 
				$output .= '<td>'.$naslov.'</td>';
				
				$output .= '<td align="center">'.$spremenljivka['char_count'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['char_count_noBlank'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['char_count_html'].'</td>';
				$output .= '<td align="center">'.$spremenljivka['word_count'].'</td>';

				$output .= '</tr>';
			
				// Loop po vrednostih v vprasanju
				foreach($spremenljivka['vrednosti'] as $vre_id => $vrednost){
					
					$output .= '<tr>';
					
					$output .= '<td align="center">'.$grupa['naslov'].'</td>';
					$output .= '<td align="center">'.$spremenljivka['variable'].'</td>';
					$output .= '<td align="center">'.$vrednost['variable'].'</td>';
					
					$naslov = strip_tags($vrednost['naslov']);
					$naslov = ($vrednost['char_count'] > 50) ? substr($naslov, 0, 47).'...' : $naslov; 
					$output .= '<td>'.$naslov.'</td>';
					
					$output .= '<td align="center">'.$vrednost['char_count'].'</td>';
					$output .= '<td align="center">'.$vrednost['char_count_noBlank'].'</td>';
					$output .= '<td align="center">'.$vrednost['char_count_html'].'</td>';
					$output .= '<td align="center">'.$vrednost['word_count'].'</td>';
					
					$output .= '</tr>';
				}
			}		
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