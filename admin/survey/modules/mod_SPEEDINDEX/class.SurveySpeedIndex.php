<?php

define("TEMP_FOLDER", "admin/survey/modules/mod_SPEEDINDEX/temp");
define("SCRIPT_FOLDER", "admin/survey/modules/mod_SPEEDINDEX/R");
define("RESULTS_FOLDER", "admin/survey/modules/mod_SPEEDINDEX/results");

class SurveySpeedIndex{

	var $anketa;				# id ankete
	var $db_table = '';	

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo, smo v status->ul evealvacija
		if ((int)$anketa > 0){
			$this->anketa = $anketa;

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
				$this->db_table = '_active';
			}
		}
	}
	
	
	// Prikažemo stran
	public function displaySpeedIndex(){
		global $lang;
		
		// Izvedemo pripravo datoteke
		$this->executeExport();
		
		// Izrisemo tabelo
		$this->displaySpeedIndexTable();
	}
	
	// Prikazemo tabelo
	private function displaySpeedIndexTable(){
		global $site_path;
		global $lang;
		
		$result_folder = $site_path . RESULTS_FOLDER.'/';
			
		echo '<div id="speeder_table">';
		
		echo '<span class="bold">'.$lang['srv_speeder_index_text'].'</span>';
		
		// Legenda
		echo '<div class="speeder_leg">';
		echo '<span class="speeder_legend spaceLeft spaceRight" style="background-color:#ffffff;">'.$lang['srv_speeder_index_legend_0'].'</span>';
		echo '<span class="speeder_legend spaceLeft" style="background-color:#ffe8e8;">'.$lang['srv_speeder_index_legend_1'].'</span>';
		echo '</div>';

		echo '<table id="tbl_speeder">';
		
		if (($handle = fopen($result_folder."speederindex".$this->anketa.".csv", "r")) !== FALSE) {		
			// Loop po vrsticah
			$cnt=0;
			while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
				
				$status = ($row[1] == 1 ? 'speeder' : 'no_speeder');
				
				echo '<tr class="'.$status.'">';
				
				// Prva vrstica
				if($cnt == 0){
					foreach($row as $val){
                        echo '<th>';
                        
                        // Prevedemo kar na roko:)
                        if($lang['id'] == '2'){
                            if($val == 'Index hitrosti')
                                echo $lang['srv_speeder_index'];
                            else
                                echo str_replace("Stran", $lang['page'], $val);
                        }
                        else
                            echo $val;
						
						echo '</th>';				
					}					
				}
				// Vrstice s podatki
				else{
					foreach($row as $val){
						echo '<td>';
						echo $val;
						echo '</td>';				
					}
				}
				
				echo '</tr>';
				
				$cnt++;
			}
			fclose($handle);
		}
			
		echo '</table></div>';
	}	
	
	
	// Zgeneriramo pdf analizo
	public function executeExport(){
		global $site_path;
		global $lang;	
		global $admin_type;

		// Zgeneriramo zacasne csv datoteke
		$this->prepareCSV();

		// Poklicemo R skripto in zgeneriramo pdf
		$script = $site_path . SCRIPT_FOLDER . '/speeder_index.R';
		$out = exec('Rscript '.$script.' '.$this->anketa.' 2>&1', $output, $return_var);
		
		// Testiranje - izpis errorjev
		/*if($admin_type == 0){
			echo '<div>';
			echo 'Rscript '.$script;
			//echo '<br />'.$out.'<br />';
			var_dump($output);
			echo '</div>';
		}*/
	
		// Na koncu pobrisemo zacasne datoteke
		$this->deleteTemp();
	}	
	
	// Pripravi csv s podatki o casih po straneh
	public function prepareCSV(){
		global $site_path;
		global $lang;	
		global $admin_type;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
		
		$file_handler = fopen($temp_folder.'datum'.$this->anketa.'.csv',"w");
		
		
		// Prva vrstica
		$line_header = 'Id;Status;Lurker;Datum_0;';
		
		$grupe = array();
		$sql = sisplet_query("SELECT * FROM srv_grupa WHERE ank_id='".$this->anketa."' ORDER BY vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {
			$line_header .= 'Datum_'.$row['vrstni_red'].';';
			
			$grupe[$row['id']] = $row['vrstni_red'];
		}
		
		fwrite($file_handler, substr($line_header, 0, -1)."\r\n");		
		
		
		// Vrstice s podatki
		$sql = sisplet_query("SELECT id, recnum, last_status, lurker, time_insert FROM srv_user u
								WHERE ank_id='".$this->anketa."' AND preview='0' AND deleted='0'
								ORDER BY recnum ASC");
		while ($row = mysqli_fetch_array($sql)) {

			$line = $row['recnum'].';';
			$line .= $row['last_status'].';';
			$line .= $row['lurker'].';';
			$line .= $row['time_insert'].';';
			
			// Napolnimo case respondenta
			$user_grupe = array();
			$sqlG = sisplet_query("SELECT gru_id, time_edit FROM srv_user_grupa".$this->db_table."
								WHERE usr_id='".$row['id']."'");
			while ($rowG = mysqli_fetch_array($sqlG)) {
				$user_grupe[$rowG['gru_id']] = $rowG['time_edit'];
			}
			
			// Sprehodimo se po vseh straneh in zapisemo case v vrstico
			foreach($grupe as $gru_id => $vrstni_red){
				
				if(isset($user_grupe[$gru_id]))
					$line .= $user_grupe[$gru_id].';';
				else
					$line .= ';';
			}
			
			fwrite($file_handler, substr($line, 0, -1)."\r\n");	
		}
		
		
		fclose($file_handler);
	}	
	
	// Pobrisemo zacasne datoteke
	private function deleteTemp(){
		global $site_path;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
		
		// Pobrisemo zacasno CSV datoteko s podatki
		if (file_exists($temp_folder.'/datum'.$this->anketa.'.csv')) {
			unlink($temp_folder.'/datum'.$this->anketa.'.csv');
		}
	}
	
}