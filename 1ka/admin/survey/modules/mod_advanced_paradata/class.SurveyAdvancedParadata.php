<?php

/*
 *  Modul za pregledovanje in urejanje nastavitev naprednih parapodatkov
 *
 */


class SurveyAdvancedParadata {

	var $anketa;				# id ankete

	
	function __construct($anketa){

		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
	}
	
	
	public function displaySettings(){
		global $lang;
        
        $settings = $this->getSettings();

		echo '<fieldset><legend>'.$lang['settings'].'</legend>';

        // Belezenje post time-a
        echo '<span class="nastavitveSpan1" >'.$lang['srv_advanced_paradata_collect_post_time'].':</span>';
        echo '<label for="collect_post_time_0"><input type="radio" name="collect_post_time" id="collect_post_time_0" value="0" '.(($settings['collect_post_time'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'].'</label>';
        echo '<label for="collect_post_time_1"><input type="radio" name="collect_post_time" id="collect_post_time_1" value="1" '.(($settings['collect_post_time'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'].'</label>';	

        echo '<br />';

		echo '</fieldset>';
		
		
		// Gumb shrani
		echo '<br class="clr" />';
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="advanced_paradata_save_settings(); return false;"><span>';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '<div id="success_save"></div>';
		
        echo '<br />';
        
		
		// Prikazemo parapodatke
		$this->displayParadata();
    }
    
    // Pridobimo trenutne nastavitve za anketo
	public function getSettings(){
		
		$settings = array();
		
		// Default vrednosti
		$settings['collect_post_time'] = '1';
		
		$sql = sisplet_query("SELECT * FROM srv_advanced_paradata_settings WHERE ank_id='".$this->anketa."'");
		if(mysqli_num_rows($sql) > 0){	
			$row = mysqli_fetch_array($sql);
			
			$settings['collect_post_time'] = $row['collect_post_time'];
		}
		
		return $settings;
    }

	
	private function displayParadata(){
		global $lang;
		global $site_url;
		
		$sape = new SurveyAdvancedParadataExport($this->anketa);
		
		echo '<fieldset><legend>'.$lang['srv_results'].'</legend>';
		
		// Opcija za brisanje loga
		echo '<p>';
		echo '  <a href="#" onClick="advanced_paradata_data_delete(); return false;">Delete all data</a>';
		echo '</p>';		
			
		// Po sejah po straneh
		echo '<span class="bold">Seja na strani</span>';
		$href_csv = 'izvoz.php?m=advanced_paradata_csv&table=srv_advanced_paradata_page&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft">(<a href="'.$href_csv.'">CSV izvoz</a>)</span>';
		$sape->displayPageTable();
		
		// Po vprasanjih
		echo '<span class="bold">Vprašanja</span>';
		$href_csv = 'izvoz.php?m=advanced_paradata_csv&table=srv_advanced_paradata_question&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft">(<a href="'.$href_csv.'">CSV izvoz</a>)</span>';
		$sape->displayQuestionTable();

		// Po vrednostih
		echo '<span class="bold">Vrednosti</span>';
		$href_csv = 'izvoz.php?m=advanced_paradata_csv&table=srv_advanced_paradata_vrednost&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft">(<a href="'.$href_csv.'">CSV izvoz</a>)</span>';
		$sape->displayVrednostTable();
		
		// Ostalo
		echo '<span class="bold">Ostalo</span>';
		$href_csv = 'izvoz.php?m=advanced_paradata_csv&table=srv_advanced_paradata_other&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft">(<a href="'.$href_csv.'">CSV izvoz</a>)</span>';
        $sape->displayOtherTable();
        
        // Premiki miske
		echo '<span class="bold">Premiki miške</span>';
		$href_csv = 'izvoz.php?m=advanced_paradata_csv&table=srv_advanced_paradata_movement&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft">(<a href="'.$href_csv.'">CSV izvoz</a>)</span>';
		$sape->displayMovementTable();
		
		// Alerti
		echo '<span class="bold">Alerti</span>';
		$href_csv = 'izvoz.php?m=advanced_paradata_csv&table=srv_advanced_paradata_alert&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft">(<a href="'.$href_csv.'">CSV izvoz</a>)</span>';
		$sape->displayAlertTable();
			
		echo '</fieldset>';	
	}
	
	
	public function ajax() {
		
		// Brisanje logov
		if (isset($_GET['a']) && $_GET['a'] == 'logDataDelete') {
			
			$sql = sisplet_query("DELETE FROM srv_advanced_paradata_page WHERE ank_id='".$this->anketa."'");
        }
        
        // Shranjevanje nastavitev
        if(isset($_GET['a']) && $_GET['a'] == 'save_settings'){
			
			$collect_post_time = isset($_POST['collect_post_time']) ? $_POST['collect_post_time'] : '1';
			
			$sql = sisplet_query("INSERT INTO srv_advanced_paradata_settings
									(ank_id, collect_post_time) VALUES ('".$this->anketa."', '".$collect_post_time."') 
									ON DUPLICATE KEY UPDATE collect_post_time='".$collect_post_time."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
}