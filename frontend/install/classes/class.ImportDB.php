<?php
/**
* 
* 	Class, ki skrbi za posodabljanje in uvažanje 1ka baze
*
*/

class ImportDB{
	
	var $clean_db_file = '../../sql/1ka_clean_27-7-2020.sql';	// Datoteka prazne baze za uvoz
	var $update_db_file = '../../sql/update2.sql';		        // Datoteka prazne baze za uvoz

    var $version = '';									// Trenutna verzija 1ke
	
	
	function __construct () {
				
		// Pogledamo ce je baza ze uvozena
		$sql = sisplet_query("SELECT * FROM misc WHERE what='version'");
		
		// Baza je ze uvozena - pogledamo verzijo
		if($sql !== FALSE && mysqli_num_rows($sql) > 0){
			$row = mysqli_fetch_array($sql);
			$this->version = $row['value'];
		}
	}
    
    
	// Pogledamo ce je baza uvozena
	public function checkDBEmpty(){
		
		return ($this->version == '') ? true : false;
	}
	
	// Pogledamo ce je baza posodobljena na najnovejso verzijo
	public function checkDBUpdated(){
		
		$update = $this->prepareUpdateArray();

		return (count($update['update_lines']) != 0) ? true : false;
    }
    
    // Vrnemo vrstice in verzijo za updatanje
	public function getDBUpdateLines(){
		
        $update = $this->prepareUpdateArray();
        
        $update['current_version'] = $this->version;

		return $update;
	}
        
    
    // Izvajamo uvoz celotne baze
    public function executeImport(){
        global $site_url;
        global $lang;

        // Pripravimo vrstice za uvoz
        $import = $this->prepareImportArray();

        // Uvozimo novo bazo
        $this->importCleanDB($import);
        
        // Ce je bilo vse ok potem izvedemo se posodobitev
        $sql = sisplet_query("SELECT * FROM misc WHERE what='version'");
        if($sql !== FALSE && mysqli_num_rows($sql) > 0){

            $row = mysqli_fetch_array($sql);
            $this->version = $row['value'];
            
            // Pripravimo vrstice za posodobitev
            $update = $this->prepareUpdateArray();

            // Izvedemo posodobitev
            $this->updateDB($update['new_version'], $update['update_lines']);
            
            echo $lang['install_database_import_complete'];
        }
        // Uvoz error
        else{
            echo $lang['install_database_import_error'];
        }
    }

	// Pripravimo vrstice za uvoz
	private function prepareImportArray(){
        global $lang;

		$import_lines = array();
		$query = '';
		
		$handle = fopen($this->clean_db_file, "r");
		if ($handle) {
		    while (($line = fgets($handle)) !== false){
				
				// Trimamo odvecne presledke
				$line = trim($line);
				
				// Shranimo vrstico za update
				if($line != '' && substr($line, 0, 1) != '#' && substr($line, 0, 2) != '--' && substr($line, 0, 2) != '//' && substr($line, 0, 2) != '/*'){
					
					// Ce je vrstica zakljucena s ; dodamo query v array
					if(substr($line, -1) == ';' || substr($line, 0, 22) == 'INSERT INTO `srv_help`'){
						
						$query .= $line;
						
						$import_lines[] = $query;
						$query = '';
					}
					// Ukaz je v vecih vrsticah - samo pripnemo string
					else{
						$query .= $line;
					}
				}
		    }
	
		    fclose($handle);
			
			// Se dodatno dodamo recnum funkcijo
			$import_lines[] = "CREATE FUNCTION MAX_RECNUM (aid INT(11))	RETURNS INT(11)	DETERMINISTIC BEGIN	DECLARE max INT(11); SELECT MAX(recnum) INTO max FROM srv_user WHERE ank_id = aid AND preview='0'; IF max IS NULL THEN SET max = '0'; END IF; RETURN max+1;	END;";
		} 
		else {
			echo $lang['install_database_sql_import_missing'];
		}
			
		return $import_lines;
	}
	
	// Uvoz nove prazne baze po vrsticah
	private function importCleanDB($import_lines){
        global $lang;

		// Izvedemo uvoz po posameznih ukazih
		if(count($import_lines) > 0){		
			foreach ($import_lines as $key => $import_line) {			

				$sql = sisplet_query($import_line);	
						     
				if (!$sql){
					echo $lang['install_database_import_line'].':<br />'.$import_line.'<br />';
					echo $lang['install_database_import_line_error'].': '.mysqli_error($GLOBALS['connect_db']);
					
					echo '<br /><br />';
				}
				/*else{
					echo 'Uvoz vrstice:<br />'.$import_line.'<br />';
					echo 'OK';
					
					echo '<br /><br />';
				}*/
				
				flush();
			}	
		}
	}
    
    
    // Izvajamo update celotne baze
    public function executeUpdate(){
        global $lang;

        // Pipravimo vrstice za posodobitev
        $update = $this->prepareUpdateArray();
        
        // Izvedemo update
        $this->updateDB($update['new_version'], $update['update_lines']);
        
        echo $lang['install_database_update_complete'];
    }

	// Pripravimo vrstice za posodabljanje
	private function prepareUpdateArray(){
        global $lang;
        
		$new_version = '';
		$update_lines = array();
		$update = false;
		$query = '';
		
		$handle = fopen($this->update_db_file, "r");
		if ($handle) {
		    while (($line = fgets($handle)) !== false){
				
				// Trimamo odvecne presledke
				$line = trim($line);
				
				// Shranimo vrstico za update
				if($update && $line != '' && substr($line, 0, 1) != '#'){
					
					// Ce je vrstica zakljucena s ; dodamo query v array
					if(substr($line, -1) == ';'){
						
						$query .= $line;
	
						// Pogledamo ce gre za vrstico verzije in jo shranimo
						if(strpos($query, ' WHERE what="version"') !== false){
							if (preg_match("/^update misc set value='(.*)' where what=/i", $query, $matches)) {
								$new_version = $matches[1];
							}
						}
						
						$update_lines[] = $query;
						$query = '';
					}
					// Ukaz je v vecih vrsticah - samo pripnemo string
					else{
						$query .= $line;
					}
				}
				
				// Ko pridemo do vrstice za trenutno verzijo shranimo vse nadaljne vrstice za update
				if(strpos($line, $this->version) !== false)			
					$update = true;
		    }
	
		    fclose($handle);		
		} 
		else {
			echo $lang['install_database_sql_update_missing'];
		}
			
		return array('new_version'=>$new_version, 'update_lines'=>$update_lines);
	}	

	// Izvedba popravkov od trenutne verzije naprej po vrsticah
	private function updateDB($new_version, $update_lines){
        global $lang;
        
		// Izvedemo posodobitve
		if($new_version != '' && count($update_lines) > 0){
			
			foreach ($update_lines as $key => $update_line) {
				
				$sql = sisplet_query($update_line);
	            
				if (!$sql){
					echo $lang['install_database_update_line'].':<br />'.$update_line.'<br />';
					echo $lang['install_database_update_error'].': '.mysqli_error($GLOBALS['connect_db']);
					
					echo '<br /><br />';
				}
				/*else{
					echo 'Posodabljanje vrstice:<br />'.$update_line.'<br />';
					echo 'OK';
					
					echo '<br /><br />';
				}*/

				flush();
			}
		}
	}
}

?>