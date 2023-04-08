<?php
/**
* 
* 	Class, ki skrbi za posodabljanje in uvažanje 1ka baze
*
*/

class ImportDB{
	
	var $clean_db_file = '1ka_clean_27-7-2020.sql';		// Datoteka prazne baze za uvoz
	var $update_db_file = 'update2.sql';				// Datoteka prazne baze za uvoz
	var $excecute = false;								// Ali izvajamo update/import						
	var $version = '';									// Trenutna verzija 1ke
	
	
	function __construct () {
		
		// Ali izvajamo update/import	
		$this->excecute = (isset($_GET['excecute']) && $_GET['excecute'] == '1') ? true : false;
		
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
	
	
	public function display(){

		// Uvazamo celotno novo bazo
		if($this->version == ''){
			$this->displayInsert();
		}
		// Posodabljamo bazo na zadnjo verzijo
		else{
			$this->displayUpdate();
		}
	}
	
	// Prikazemo uvoz baze
	public function displayInsert(){
		global $site_url;
		
		echo '<h1>Uvoz nove 1ka MySql baze</h1>';
	
		// Izvajamo uvoz celotne baze
		if($this->excecute){
			
			// Uvozimo novo bazo
			$import = $this->prepareImportArray();
			$this->importCleanDB($import);
			
			// Ce je bilo vse ok potem izvedemo se posodobitev
			$sql = sisplet_query("SELECT * FROM misc WHERE what='version'");
			if($sql !== FALSE && mysqli_num_rows($sql) > 0){
				$row = mysqli_fetch_array($sql);
				$this->version = $row['value'];
				
				$update = $this->prepareUpdateArray();
				$this->updateDB($update['new_version'], $update['update_lines']);
				
				echo 'Postopek uvoza podatkovne baze je dokončan.';
				
				echo '<br /><br />';
				
				// Gumb za nazaj na frontpage
				echo '<a href="'.$site_url.'">Nazaj na prvo stran</a>';
			}
			// Uvoz error
			else{
				echo 'Prišlo je do napake pri uvažanju.';
			}
		}
		else{	
			echo 'Izvedel se bo uvoz celotne MySql baze. Postopek lahko traja nekaj minut!<br />';	
			
			echo '<br />';
				
			// Gumb za posodobitev
			echo '<a href="'.$site_url.'sql/import_db.php?excecute=1">Uvozi</a>';
		}
	}
	
	// Prikazemo update baze
	public function displayUpdate(){
		global $site_url;
		
		echo '<h1>Posodobitev 1ka MySql baze</h1>';
		
		$update = $this->prepareUpdateArray();
				
		// Izvajamo posodobitev baze
		if($this->excecute){
			$this->updateDB($update['new_version'], $update['update_lines']);
		}
		elseif(count($update['update_lines']) != 0){
			
			echo 'Trenutna verzija: <b>'.$this->version.'</b>';
			echo '<br />';
			echo 'Posodobitev na verzijo: <b>'.$update['new_version'].'</b>';
			echo '<br /><br />';
			echo 'Izvedla se bo posodobitev MySql baze!';
			echo '<br /><br />';
			
			foreach ($update['update_lines'] as $key => $update_line) {
				echo $update_line.'<br /><br />';
			}
			
			echo '<br />';
			
			// Gumb za posodobitev
			echo '<a href="'.$site_url.'sql/import_db.php?excecute=1">Posodobi</a>';
		}
		else{
			echo 'Nameščena je najnovejša različica MySql baze.';
			
			echo '<br /><br />';
			
			// Gumb za nazaj na frontpage
			echo '<a href="'.$site_url.'">Nazaj na prvo stran</a>';
		}
	}
	
	
	// Pripravimo vrstice za uvoz
	private function prepareImportArray(){
		
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
			echo 'Manjka sql datoteka za uvoz!';
		}
			
		return $import_lines;
	}
	
	// Uvoz nove prazne baze
	private function importCleanDB($import_lines){
		
		// Izvedemo uvoz po posameznih ukazih
		if(count($import_lines) > 0){		
			foreach ($import_lines as $key => $import_line) {			

				$sql = sisplet_query($import_line);	
						     
				if (!$sql){
					echo 'Uvoz vrstice:<br />'.$import_line.'<br />';
					echo 'Napaka pri uvozu: '.mysqli_error($GLOBALS['connect_db']);
					
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
	
	// Pripravimo vrstice za posodabljanje
	private function prepareUpdateArray(){
		
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
			echo 'Manjka sql datoteka za posodobitev!';
		}
			
		return array('new_version'=>$new_version, 'update_lines'=>$update_lines);
	}	

	// Izvedba popravkov od trenutne verzije naprej
	private function updateDB($new_version, $update_lines){
		
		// Izvedemo posodobitve
		if($new_version != '' && count($update_lines) > 0){
			
			foreach ($update_lines as $key => $update_line) {
				
				$sql = sisplet_query($update_line);
	            
				if (!$sql){
					echo 'Posodabljanje vrstice:<br />'.$update_line.'<br />';
					echo 'Napaka pri posodabljanju: '.mysqli_error($GLOBALS['connect_db']);
					
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