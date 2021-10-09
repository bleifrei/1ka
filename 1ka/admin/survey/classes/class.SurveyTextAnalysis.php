<?php

class SurveyTextAnalysis{

	private $anketa;										# id ankete
	private $db_table;									# katere tabele uporabljamo
	
	
	function __construct($anketa){
		global $lang;
		
		if ((int)$anketa > 0){
		
			$this->anketa = $anketa;

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}
		} 
		else {	
			echo 'Invalid Survey ID!';
			exit();
		}
	}
	
	
	function displayTable(){
		global $lang;

		$data = $this->getData();

		
		// Tabela z vsotami stevil znakov - po straneh in vprasanjih
		echo '<h2>'.$lang['srv_text_analysis_title1'].'</h2>';
		echo '<table class="text_analysis_table">';

		// Header row
		echo '<tr>';
		
		echo '<th style="width:120px;">'.$lang['srv_text_analysis_page'].'</th>';
		echo '<th style="width:120px;">'.$lang['srv_text_analysis_question'].'</th>';
		
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_charCount'].'</th>';
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_charCountBlank'].'</th>';
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_charCountHTML'].'</th>';
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_wordCount'].'</th>';
	
		echo '</tr>';	

		// Loop po straneh v anketi
		foreach($data['grupe'] as $gru_id => $grupa){
			
			// Loop po vprasanjih na strani
			foreach($grupa['spremenljivke'] as $spr_id => $spremenljivka){
			
				echo '<tr>';

				echo '<td>'.$grupa['naslov'].'</td>';
				echo '<td>'.$spremenljivka['variable'].'</td>';
				
				echo '<td>'.$spremenljivka['sum_char_count'].'</td>';
				echo '<td>'.$spremenljivka['sum_char_count_noBlank'].'</td>';
				echo '<td>'.$spremenljivka['sum_char_count_html'].'</td>';
				echo '<td>'.$spremenljivka['sum_word_count'].'</td>';

				echo '</tr>';
			}		
			
			// Vsota znakov na strani
			echo '<tr class="sum">';

			echo '<td>'.$grupa['naslov'].'</td>';
			echo '<td>'.$lang['srv_text_analysis_sum'].'</td>';
			
			echo '<td>'.$grupa['sum_char_count'].'</td>';
			echo '<td>'.$grupa['sum_char_count_noBlank'].'</td>';
			echo '<td>'.$grupa['sum_char_count_html'].'</td>';
			echo '<td>'.$grupa['sum_word_count'].'</td>';

			echo '</tr>';
		}
		
		// Vsota znakov v anketi
		echo '<tr class="sum">';

		echo '<td></td>';
		echo '<td>'.$lang['srv_text_analysis_sumSurvey'].'</td>';
		
		echo '<td>'.$data['sum_char_count'].'</td>';
		echo '<td>'.$data['sum_char_count_noBlank'].'</td>';
		echo '<td>'.$data['sum_char_count_html'].'</td>';
		echo '<td>'.$data['sum_word_count'].'</td>';

		echo '</tr>';
			
		echo '</table>';
		
		
		// Tabela s podrobnostmi - stevilo znakov po posameznih vprasnajih in vrednostih
		echo '<h2>'.$lang['srv_text_analysis_title2'].'</h2>';
		echo '<table class="text_analysis_table">';

		// Header row
		echo '<tr>';
		
		echo '<th style="width:120px;">'.$lang['srv_text_analysis_page'].'</th>';
		echo '<th style="width:120px;">'.$lang['srv_text_analysis_question'].'</th>';
		echo '<th style="width:120px;">'.$lang['srv_text_analysis_value'].'</th>';
		echo '<th>'.$lang['srv_text_analysis_text'].'</th>';
		
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_charCount'].'</th>';
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_charCountBlank'].'</th>';
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_charCountHTML'].'</th>';
		echo '<th style="width:130px;">'.$lang['srv_text_analysis_wordCount'].'</th>';
	
		echo '</tr>';	

		// Loop po straneh v anketi
		foreach($data['grupe'] as $gru_id => $grupa){
			
			// Loop po vprasanjih na strani
			foreach($grupa['spremenljivke'] as $spr_id => $spremenljivka){
			
				echo '<tr class="colored">';

				echo '<td>'.$grupa['naslov'].'</td>';
				echo '<td>'.$spremenljivka['variable'].'</td>';
				echo '<td></td>';
				
				$naslov = strip_tags($spremenljivka['naslov']);
				$naslov = ($spremenljivka['char_count'] > 50) ? substr($naslov, 0, 47).'...' : $naslov; 
				echo '<td>'.$naslov.'</td>';
				
				echo '<td>'.$spremenljivka['char_count'].'</td>';
				echo '<td>'.$spremenljivka['char_count_noBlank'].'</td>';
				echo '<td>'.$spremenljivka['char_count_html'].'</td>';
				echo '<td>'.$spremenljivka['word_count'].'</td>';

				echo '</tr>';
			
				// Loop po vrednostih v vprasanju
				foreach($spremenljivka['vrednosti'] as $vre_id => $vrednost){
					
					echo '<tr>';
					
					echo '<td>'.$grupa['naslov'].'</td>';
					echo '<td>'.$spremenljivka['variable'].'</td>';
					echo '<td>'.$vrednost['variable'].'</td>';
					
					$naslov = strip_tags($vrednost['naslov']);
					$naslov = ($vrednost['char_count'] > 50) ? substr($naslov, 0, 47).'...' : $naslov; 
					echo '<td>'.$naslov.'</td>';
					
					echo '<td>'.$vrednost['char_count'].'</td>';
					echo '<td>'.$vrednost['char_count_noBlank'].'</td>';
					echo '<td>'.$vrednost['char_count_html'].'</td>';
					echo '<td>'.$vrednost['word_count'].'</td>';
					
					echo '</tr>';
				}
			}		
		}
			
		echo '</table>';		
	}
	
	// Preracunamo vse potrebne podatke
	function getData(){
		
		$data = array();
		
		$data['sum_char_count'] = 0;
		$data['sum_char_count_noBlank'] = 0;
		$data['sum_char_count_html'] = 0;
		$data['sum_word_count'] = 0;
	
		// Loop cez vse vrednosti v vprasanjih na straneh v anketi
		$sqlGrupa = sisplet_query("SELECT id, ank_id, naslov, vrstni_red FROM srv_grupa WHERE ank_id='$this->anketa' ORDER BY vrstni_red ASC");
		while($rowGrupa = mysqli_fetch_array($sqlGrupa)){
			
			$data['grupe'][$rowGrupa['id']]['naslov'] = $rowGrupa['naslov'];
			
			$data['grupe'][$rowGrupa['id']]['sum_char_count'] = 0;
			$data['grupe'][$rowGrupa['id']]['sum_char_count_noBlank'] = 0;
			$data['grupe'][$rowGrupa['id']]['sum_char_count_html'] = 0;
			$data['grupe'][$rowGrupa['id']]['sum_word_count'] = 0;
			
			$sqlSpr = sisplet_query("SELECT id, naslov, variable, vrstni_red FROM srv_spremenljivka WHERE gru_id='".$rowGrupa['id']."' ORDER BY vrstni_red ASC");
			while($rowSpr = mysqli_fetch_array($sqlSpr)){
				
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['variable'] = $rowSpr['variable'];
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['naslov'] = $rowSpr['naslov'];
				
				$naslov = utf8_decode(html_entity_decode($rowSpr['naslov'], ENT_COMPAT, 'utf-8'));
				
				// Stevilo znakov za naslov vprasanja
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['char_count'] = strlen(strip_tags($naslov));
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['char_count_noBlank'] = strlen(strip_tags(str_replace(" ","",$naslov)));
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['char_count_html'] = strlen($naslov);
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['word_count'] = count(explode(" ", strip_tags($naslov)));
				
				// Stevilo vseh znakov v vprasanju (se sesteva z znaki v vrednostih)
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_char_count'] = strlen(strip_tags($naslov));
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_char_count_noBlank'] = strlen(strip_tags(str_replace(" ","",$naslov)));
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_char_count_html'] = strlen($naslov);
				$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_word_count'] = count(explode(" ", strip_tags($naslov)));
				
				// Stevilo vseh znakov na strani (se sesteva z znaki v vrednostih)
				$data['grupe'][$rowGrupa['id']]['sum_char_count'] += strlen(strip_tags($naslov));
				$data['grupe'][$rowGrupa['id']]['sum_char_count_noBlank'] += strlen(strip_tags(str_replace(" ","",$naslov)));
				$data['grupe'][$rowGrupa['id']]['sum_char_count_html'] += strlen($naslov);
				$data['grupe'][$rowGrupa['id']]['sum_word_count'] += count(explode(" ", strip_tags($naslov)));
				
				// Stevilo vseh znakov v anketi (se sesteva z znaki v vrednostih)
				$data['sum_char_count'] += strlen(strip_tags($naslov));
				$data['sum_char_count_noBlank'] += strlen(strip_tags(str_replace(" ","",$naslov)));
				$data['sum_char_count_html'] += strlen($naslov);
				$data['sum_word_count'] += count(explode(" ", strip_tags($naslov)));
				
				$sqlVre = sisplet_query("SELECT id, naslov, variable, vrstni_red FROM srv_vrednost WHERE spr_id='".$rowSpr['id']."' ORDER BY vrstni_red ASC");
				while($rowVre = mysqli_fetch_array($sqlVre)){
					
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['vrednosti'][$rowVre['id']]['variable'] = $rowVre['variable'];
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['vrednosti'][$rowVre['id']]['naslov'] = $rowVre['naslov'];
					
					$naslov = utf8_decode(html_entity_decode($rowVre['naslov'], ENT_COMPAT, 'utf-8'));
					
					// Stevilo znakov za vrednost
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['vrednosti'][$rowVre['id']]['char_count'] = strlen(strip_tags($naslov));
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['vrednosti'][$rowVre['id']]['char_count_noBlank'] = strlen(strip_tags(str_replace(" ","",$naslov)));
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['vrednosti'][$rowVre['id']]['char_count_html'] = strlen($naslov);
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['vrednosti'][$rowVre['id']]['word_count'] = count(explode(" ", strip_tags($naslov)));
					
					// Stevilo vseh znakov v vprasanju (se sesteva z znaki v vrednostih)
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_char_count'] += strlen(strip_tags($naslov));
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_char_count_noBlank'] += strlen(strip_tags(str_replace(" ","",$naslov)));
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_char_count_html'] += strlen($naslov);
					$data['grupe'][$rowGrupa['id']]['spremenljivke'][$rowSpr['id']]['sum_word_count'] += count(explode(" ", strip_tags($naslov)));
					
					// Stevilo vseh znakov na strani (se sesteva z znaki v vrednostih)
					$data['grupe'][$rowGrupa['id']]['sum_char_count'] += strlen(strip_tags($naslov));
					$data['grupe'][$rowGrupa['id']]['sum_char_count_noBlank'] += strlen(strip_tags(str_replace(" ","",$naslov)));
					$data['grupe'][$rowGrupa['id']]['sum_char_count_html'] += strlen($naslov);
					$data['grupe'][$rowGrupa['id']]['sum_word_count'] += count(explode(" ", strip_tags($naslov)));
					
					// Stevilo vseh znakov v anketi (se sesteva z znaki v vrednostih)
					$data['sum_char_count'] += strlen(strip_tags($naslov));
					$data['sum_char_count_noBlank'] += strlen(strip_tags(str_replace(" ","",$naslov)));
					$data['sum_char_count_html'] += strlen($naslov);
					$data['sum_word_count'] += count(explode(" ", strip_tags($naslov)));
				}
			}
		}
		
		return $data;
	}
	

	function exportCSVTable($table=1){
		global $lang;

		$data = $this->getData();
		
		$csvArray = array();
				
		// Izpisemo prvo tabelo (z vsotami po vprasanjih in straneh)
		if($table == 1){

			// Header row
			$csvRow = array();
			
			$csvRow[] = $lang['srv_text_analysis_page'];
			$csvRow[] = $lang['srv_text_analysis_question'];
			
			$csvRow[] = $lang['srv_text_analysis_charCount'];
			$csvRow[] = strip_tags($lang['srv_text_analysis_charCountBlank']);
			$csvRow[] = strip_tags($lang['srv_text_analysis_charCountHTML']);
			$csvRow[] = $lang['srv_text_analysis_wordCount'];
			
			$csvArray[] = $csvRow;
		
			// Loop po straneh v anketi
			foreach($data['grupe'] as $gru_id => $grupa){
				
				// Loop po vprasanjih na strani
				foreach($grupa['spremenljivke'] as $spr_id => $spremenljivka){
				
					$csvRow = array();
				
					$csvRow[] = $grupa['naslov'];
					$csvRow[] = $spremenljivka['variable'];
					
					$csvRow[] = $spremenljivka['sum_char_count'];
					$csvRow[] = $spremenljivka['sum_char_count_noBlank'];
					$csvRow[] = $spremenljivka['sum_char_count_html'];
					$csvRow[] = $spremenljivka['sum_word_count'];

					$csvArray[] = $csvRow;
				}		
				
				// Vsota znakov na strani
				$csvRow = array();
				
				$csvRow[] = $grupa['naslov'];
				$csvRow[] = $lang['srv_text_analysis_sum'];
				
				$csvRow[] = $grupa['sum_char_count'];
				$csvRow[] = $grupa['sum_char_count_noBlank'];
				$csvRow[] = $grupa['sum_char_count_html'];
				$csvRow[] = $grupa['sum_word_count'];

				$csvArray[] = $csvRow;
			}
			
			// Vsota znakov v anketi
			$csvRow = array();
			
			$csvRow[] = ' ';
			$csvRow[] = $lang['srv_text_analysis_sumSurvey'];
			
			$csvRow[] = $data['sum_char_count'];
			$csvRow[] = $data['sum_char_count_noBlank'];
			$csvRow[] = $data['sum_char_count_html'];
			$csvRow[] = $data['sum_word_count'];

			$csvArray[] = $csvRow;		
		}
		// Izpisemo drugo tabelo (posamezno stevilo znakov po vprasanju in variabli)
		else{
		
			// Header row	
			$csvRow = array();
			
			$csvRow[] = $lang['srv_text_analysis_page'];
			$csvRow[] = $lang['srv_text_analysis_question'];
			$csvRow[] = $lang['srv_text_analysis_value'];
			$csvRow[] = $lang['srv_text_analysis_text'];
			
			$csvRow[] = $lang['srv_text_analysis_charCount'];
			$csvRow[] = strip_tags($lang['srv_text_analysis_charCountBlank']);
			$csvRow[] = strip_tags($lang['srv_text_analysis_charCountHTML']);
			$csvRow[] = $lang['srv_text_analysis_wordCount'];
		
			$csvArray[] = $csvRow;

			// Loop po straneh v anketi
			foreach($data['grupe'] as $gru_id => $grupa){
				
				// Loop po vprasanjih na strani
				foreach($grupa['spremenljivke'] as $spr_id => $spremenljivka){

					$csvRow = array();
				
					$csvRow[] = $grupa['naslov'];
					$csvRow[] = $spremenljivka['variable'];
					$csvRow[] = ' ';
					
					$naslov = strip_tags($spremenljivka['naslov']);
					$naslov = ($spremenljivka['char_count'] > 50) ? substr($naslov, 0, 47).'...' : $naslov; 
					$csvRow[] = $naslov;
					
					$csvRow[] = $spremenljivka['char_count'];
					$csvRow[] = $spremenljivka['char_count_noBlank'];
					$csvRow[] = $spremenljivka['char_count_html'];
					$csvRow[] = $spremenljivka['word_count'];

					$csvArray[] = $csvRow;
				
					// Loop po vrednostih v vprasanju
					foreach($spremenljivka['vrednosti'] as $vre_id => $vrednost){
									
						$csvRow = array();
									
						$csvRow[] = $grupa['naslov'];
						$csvRow[] = $spremenljivka['variable'];
						$csvRow[] = $vrednost['variable'];
						
						$naslov = strip_tags($vrednost['naslov']);
						$naslov = ($vrednost['char_count'] > 50) ? substr($naslov, 0, 47).'...' : $naslov; 
						$csvRow[] = $naslov;
						
						$csvRow[] = $vrednost['char_count'];
						$csvRow[] = $vrednost['char_count_noBlank'];
						$csvRow[] = $vrednost['char_count_html'];
						$csvRow[] = $vrednost['word_count'];
						
						$csvArray[] = $csvRow;
					}
				}		
			}
		}
		
		// Izvozimo CSV
		$fp = fopen('php://output', 'w');

		header('Content-Type: application/csv charset=windows-1250');
		header('Content-Disposition: attachement; filename="textAnalysis_'.$this->anketa.'.csv";');
				
		foreach ($csvArray as $row) {
			fputcsv($fp, $row, ';', ' ');
		}

		fclose($fp);
	}
}