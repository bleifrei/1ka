<?php

include_once 'definition.php';

define("TEMP_FOLDER", "admin/survey/modules/mod_vizualizacija/temp");
define("SCRIPT_FOLDER", "admin/survey/modules/mod_vizualizacija/R/app");

class SurveyVizualizacija{

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
        
    
    // Prikazemo vsebino zavihka v analizah
    public function display(){

        // Zenkrat iframe aplikacije na zunanjem strezniku
        //echo '<iframe src="https://1kadsa.shinyapps.io/vizual/" style="width:80%; height:800px; border:1px #c8e3f8 solid;"></iframe>';

        echo '<div id="shiny_iframe" style="width:80%; height:800px; border:1px #c8e3f8 solid;"></div>';
        echo '<script>
            window.onload = function() {
                var iframe = document.createElement(\'iframe\');
                iframe.src = "https://1kadsa.shinyapps.io/vizual/";
                $(\'#shiny_iframe\').html(iframe);
                
                $(iframe).css({        
                    "width": "100%",
                    "height": "100%"
                });
            };
        </script>';
        
        // Zgeneriramo zacasne csv datoteke
        $this->prepareCSV();
        
        //$this->execute();
    }
	
	// Odpremom popup z vizualizacijo
	public function execute(){
		global $site_path;
		global $site_url;
		global $lang;	
		global $admin_type;

		// Zgeneriramo zacasne csv datoteke
		$this->prepareCSV();
		
		// Poklicemo R skripto in zgeneriramo pdf
		$script = $site_path . SCRIPT_FOLDER . '/Visualize_df.R';
        $file_name = 'data_'.$this->anketa.'.csv';
			
		//$out = exec('Rscript '.$script.' '.$file_name.' 2>&1', $output, $return_var);
		
		// Testiranje - izpis errorjev
        /*echo '<div>';
        echo 'Rscript '.$script;
        //echo '<br />'.$out.'<br />';
        var_dump($output);
        echo '</div>';*/
		
		// Na koncu pobrisemo zacasne datoteke
		$this->deleteTemp();
		
		// Ugasnemo skripto:)
		die();
	}
	
	// Pripravimo zacasne datoteke
	private function prepareCSV(){
		global $site_path;
		
		$temp_folder = $site_path . TEMP_FOLDER.'/';
	
        
        // Poskrbimo za datoteko s podatki
        $SDF = SurveyDataFile::get_instance();
        $SDF->init($this->anketa);           
        $SDF->prepareFiles();  

        $_headFileName = $SDF->getHeaderFileName();
        $_dataFileName = $SDF->getDataFileName();
		
		if ($_headFileName != null && $_headFileName != '') {
			$_HEADERS = unserialize(file_get_contents($_headFileName));
		} 
		else {
			echo 'Error! Empty file name!';
		}
	
		// Zaenkrat dopuscamo samo status 6 in brez lurkerjev
		$status_filter = '('.STATUS_FIELD.' ~ /6|5/)&&('.LURKER_FIELD.'==0)';
		
		$start_sequence = 2;
		$end_sequence = $_HEADERS['_settings']['metaSequence']-1;
		
		$field_delimit = ';';
			
		// Filtriramo podatke po statusu in jih zapisemo v temp folder
		if (IS_WINDOWS) {
			$out = shell_exec('awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$status_filter.'" '.$_dataFileName.' | cut -d "|" -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat');
		} 
		else {
			$out = shell_exec('awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.'\' '.$_dataFileName.' | cut -d \'|\' -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'/temp_data_'.$this->anketa.'.dat');
		}
		
		
		// Ustvarimo koncni CSV
		if ($fd = fopen($temp_folder.'/temp_data_'.$this->anketa.'.dat', "r")) {
		
			//$fd2 = fopen($temp_folder.'/data_'.$this->anketa.'.csv', "w");
			$fd2 = fopen($temp_folder.'/data.csv', "w");
						
			$convertType = 1; // kateri tip konvertiranja uporabimo
			$convertTypes[1] = array('charSet'	=> 'windows-1250',
							 'delimit'	=> ';',
							 'newLine'	=> "\n",
							 'BOMchar'	=> "\xEF\xBB\xBF");
			# dodamo boomchar za utf-8
			fwrite($fd2, $convertTypes[$convertType]['BOMchar']);
						
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
						
			fwrite($fd2, $output1."\r\n");
			fwrite($fd2, $output2."\r\n");
			
			while ($line = fgets($fd)) {
				
				$temp = array();
				$temp = explode('|', $line);

                $line = '"' . str_replace(array("\r","\n","\"","|"), array("","","",'";"'), $line) . '"';
                
                // Spremenimo encoding v windows-1250
                //$line = iconv("UTF-8","Windows-1250//TRANSLIT", $line);
                
                fwrite($fd2, $line);
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
		
		// Pobrisemo zacasno CSV datoteko s podatki - UGASNEMO, KER VCASIH NE DELA:)
		if (file_exists($temp_folder.'/evoli.csv')) {
			unlink($temp_folder.'/evoli.csv');
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