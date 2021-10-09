<?php
	
	
class DisplayDatabase{


	function __construct(){

	}
	
     
    // Izris strani za preverjanje konfiguracije streznika, baze
	public function displayDatabasePage(){
		global $lang;
        
        echo '<h2>'.$lang['install_database_title'].'</h2>';

        $import = new ImportDB();

        // Baza je prazna
        if($import->checkDBEmpty()){
            $this->displayInsert();
        }
        // Baza ni updatana
        elseif($import->checkDBUpdated()){
            $update = $import->getDBUpdateLines();
            $this->displayUpdate($update);
        }
        // Ok - zadnja verzija baze
        else{
            $this->displayOK();
        }
    }


    // Prikazemo ce je vse ok
	private function displayOK(){
        global $lang;
        
        echo '<p>'.$lang['install_database_ok'].'</p>';

        // Next button
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=settings"><input name="back" value="'.$lang['back'].'" type="button"></a>';
        echo '  <a href="index.php?step=finish"><input type="button" value="'.$lang['next1'].'"></a>';
        echo '</div>';  
    }

    // Prikazemo uvoz celotne baze
	private function displayInsert(){
        global $site_url;
        global $lang;
        
        echo '<p>'.$lang['install_database_import'].'</p>';
 
        echo '<p>'.$lang['install_database_import_progress'].'</p>';	
        

        // Div kamor izpisemo response po uvazanju
        echo '<div id="db_response"></div>';


        // Next button
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=settings"><input name="back" value="'.$lang['back'].'" type="button"></a>';
        echo '  <a href="#" onClick="databaseImport();"><input type="button" value="'.$lang['install_database_button_import'].'"></a>';
        echo '</div>'; 


        // Se popup okna
        echo '<div id="fade"></div>';
        echo '<div id="popup"> '.$lang['install_database_import_progress'].'</div>';
	}
	
	// Prikazemo update baze
	public function displayUpdate($update){
        global $site_url;
        global $lang;
        global $debug;
		global $admin_type;
        
        echo '<p>'.$lang['install_database_update'].'</p>';
        
        echo '<p>'.$lang['install_database_version'].': <b>'.$update['current_version'].'</b></p>';

        echo '<p>'.$lang['install_database_version_update'].': <b>'.$update['new_version'].'</b></p>';
        
        // Ce smo admin ali v debugu izpisemo tudi vrstice za update
        if($admin_type == '0' || $debug == '1'){

            foreach ($update['update_lines'] as $key => $update_line) {
                echo $update_line.'<br /><br />';
            }
        }
        

        // Div kamor izpisemo response po uvazanju
        echo '<div id="db_response"></div>';


        // Next button
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=settings"><input name="back" value="'.$lang['back'].'" type="button"></a>';
        echo '  <a href="#" onClick="databaseUpdate();"><input type="button" value="'.$lang['install_database_button_update'].'"></a>';
        echo '</div>'; 


        // Se popup okna
        echo '<div id="fade"></div>';
        echo '<div id="popup"> '.$lang['install_database_update_progress'].'</div>';
    }
}