<?php

define("TEMP_FOLDER", "admin/survey/modules/mod_360_1KA/temp");
define("SCRIPT_FOLDER", "admin/survey/modules/mod_360_1KA/R");
define("RESULTS_FOLDER", "admin/survey/modules/mod_360_1KA/results");

class Survey3601ka{

	var $anketa;				# id ankete
	var $db_table = '';	

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo, smo v status->ul evealvacija
		if ((int)$anketa > 0){
			$this->anketa = $anketa;

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		}
	}
	
	
	// Prikazemo vsa porocila
	public function displayReports(){
		global $lang;

		echo '<fieldset><legend>'.$lang['srv_360_reports'].'</legend>';
		echo '<ul>';
		
		// Poiščemo vprašanja z odnosom in identifikacijo ocenjevanca
		$sql = sisplet_query("SELECT s.id AS spr_id, s.variable AS variable, s.tip AS tip FROM srv_spremenljivka s, srv_grupa g 
										WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND (s.variable='odnos' OR s.variable='ime' OR s.variable='drugo')");
		while($row = mysqli_fetch_array($sql)){
			
			if($row['variable'] == 'odnos'){
			
			}
			elseif($row['variable'] == 'drugo'){
			
				// Loop po vseh odgovorih drugo (ocenjevanec) in jih zgrupiramo
				$sqlU = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$row['id']."'");
				while($rowU = mysqli_fetch_array($sqlU)){
					
				}
			}
		}
		
		
		echo '<li>';
		echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_360_1KA . '&amp;export=1">Izvozi poročilo</a>';
		echo '</li>';
		
		echo '</ul>';		
		echo '</fieldset>';
		
		// Izvažamo
		if(isset($_GET['export']) && $_GET['export'] == '1'){
			$this->executeExport();
		}
	}
	
	// Prikazemo nastavitve pri vklopu naprednega modula
	public function displaySettings(){
		global $lang;
		
		echo 'Dodatne nastavitve...';
	}	
	
	
	// Zgeneriramo pdf analizo
	public function executeExport(){
		global $site_path;
		global $lang;	
		global $admin_type;

		// Zgeneriramo zacasne csv datoteke
		$this->prepareCSV();
		
		// Poklicemo R skripto in zgeneriramo pdf
		$script = $site_path . SCRIPT_FOLDER . '/360_stopinj_1ka.R';
		$out = exec('Rscript '.$script.' 2>&1', $output, $return_var);
		
		// Testiranje - izpis errorjev
		if($admin_type == 0){
			echo '<div>';
			echo 'Rscript '.$script;
			//echo '<br />'.$out.'<br />';
			var_dump($output);
			echo '</div>';
		}

		// Pripravimo file za download
		if(file_exists($site_path . RESULTS_FOLDER . '/mod_360_CDI.pdf')){
		
			$file = $site_path . RESULTS_FOLDER . '/mod_360_CDI.pdf';
			
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename('mod_360_CDI.pdf'));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			
			ob_clean();
			flush();
			
			readfile($file);
		}
		
		// Na koncu pobrisemo zacasne datoteke
		$this->deleteTemp();
		
		// Ugasnemo skripto:)
		die();
	}
	
	// Pripravimo zacasne datoteke
	private function prepareCSV(){
		global $site_path;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
	
		$SDF = SurveyDataFile::get_instance();
		$SDF->init($this->anketa);
		$_headFileName = $SDF->getHeaderFileName();
		$_dataFileName = $SDF->getDataFileName();
		$_fileStatus = $SDF->getStatus();
		
		if ($_headFileName != null && $_headFileName != '') {
			$_HEADERS = unserialize(file_get_contents($_headFileName));
		} 
		else {
			echo 'Error! Empty file name!';
		}
	
		// Zaenkrat dopuscamo samo status 6 in brez lurkerjev
		//$status_filter = '('.STATUS_FIELD.' ~ /6|5/)&&('.LURKER_FIELD.'==0)';
		$status_filter = '('.STATUS_FIELD.'==6)&&('.LURKER_FIELD.'==0)';
		
		//$start_sequence = $_HEADERS['_settings']['dataSequence'];
		$start_sequence = 2;
		$end_sequence = $_HEADERS['_settings']['metaSequence']-1;
		
		$field_delimit = ';';
			
		// Filtriramo podatke po statusu in jih zapisemo v temp folder
		if (IS_WINDOWS) {
			//$command = 'awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$status_filter.' { print $0}" '.$_dataFileName.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat';
			$out = shell_exec('awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$status_filter.'" '.$_dataFileName.' | cut -d "|" -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat');
			
			# zamenjamo | z ;
			//exec('sed "s/|/\x22'.$field_delimit.'=\x22/g" '.$temp_folder.'/temp_data_'.$this->anketa.'.dat >> '.$temp_folder.'/temp_data_'.$this->anketa.'.csv');
		} 
		else {
			//$command = 'awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.' { print $0; }\' '.$_dataFileName.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat';
			$out = shell_exec('awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.'\' '.$_dataFileName.' | cut -d \'|\' -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat');
			
			# zamenjamo | z ;
			//exec('sed \'s/|/\x22'.$field_delimit.'=\x22/g\' '.$temp_folder.'/temp_data_'.$this->anketa.'.dat >> '.$temp_folder.'/temp_data_'.$this->anketa.'.csv');
		}
		
		
		// Ustvarimo koncni CSV
		if ($fd = fopen($temp_folder.'/temp_data_'.$this->anketa.'.dat', "r")) {
		
			//$fd2 = fopen($temp_folder.'/data_'.$this->anketa.'.csv', "w");
			$fd2 = fopen($temp_folder.'/test.csv', "w");
			
			//header('Content-Encoding: windows-1250');
			//header('Content-Type: application/csv charset=windows-1250');
			
			# naredimo header row
			foreach ($_HEADERS AS $spid => $spremenljivka) {
				if (count($spremenljivka['grids']) > 0) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						foreach ($grid['variables'] AS $vid => $variable ){
							if ($spremenljivka['tip'] !== 'sm' && !($variable['variable'] == 'uid' && $variable['naslov'] == 'User ID')){
								$output1 .= strip_tags($variable['variable']).$field_delimit;
								$output2 .= '"'.strip_tags($variable['naslov']).'"'.$field_delimit;
							}
						}
					}
				}
			}
			
			// Spremenimo encoding v windows-1250
			//$output1 = iconv("UTF-8","Windows-1250//TRANSLIT", $output1);
			//$output2 = iconv("UTF-8","Windows-1250//TRANSLIT", $output2);
			
			fwrite($fd2, $output1."\r\n");
			fwrite($fd2, $output2."\r\n");


			while ($line = fgets($fd)) {
															
				//fwrite($fd2, '="');
				//$line = str_replace(array("\r","\n","|"), array("","",'";="'), $line);
				$line = '"' . str_replace(array("\r","\n","\"","|"), array("","","",'";"'), $line) . '"';
				
				// Spremenimo encoding v windows-1250
				$line = iconv("UTF-8","Windows-1250//TRANSLIT", $line);
				//$line = str_replace(array("č","š","ž","Č","Š","Ž"), array("\v{c}","\v{s}","\v{z}","\v{C}","\v{S}","\v{Z}"), $line);

				fwrite($fd2, $line);
				//fwrite($fd2, '"');
				fwrite($fd2, "\r\n");
			}
			
			fclose($fd2);
		}
		fclose($fd);

		
		// Na koncu pobrisemo temp datoteke
		if (file_exists($temp_folder.'/temp_data_'.$this->anketa.'.dat')) {
			unlink($temp_folder.'/temp_data_'.$this->anketa.'.dat');
		}		
	}
	
	// Pobrisemo zacasne datoteke
	private function deleteTemp(){
		global $site_path;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
		
		if (file_exists($temp_folder.'/data_'.$this->anketa.'.csv')) {
			unlink($temp_folder.'/data_'.$this->anketa.'.csv');
		}
		
		// Pobrisemo zacasno CSV datoteko s podatki
		if (file_exists($temp_folder.'/test.csv')) {
			unlink($temp_folder.'/test.csv');
		}
		
		// Pobrisemo pdf grafe ki so bili vstavljeni v porocilo
		$files = glob($site_path . RESULTS_FOLDER . '/part-predmet-slike/*');
		foreach($files as $file){
			if(is_file($file))
				unlink($file);
		}
		
		// Pobrisemo še vse ostalo v rezultatih
		$files = glob($site_path . RESULTS_FOLDER . '/*');
		foreach($files as $file){
			if(is_file($file))
				unlink($file);
		}
	}
	
}