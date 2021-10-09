<?php

/*
 *  Modul za kviz
 * 
 *
 */


class SurveyQuiz{

	var $anketa;				# id ankete

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
	}
	
	
	// Nastavitve kviza (prikaz rezultatov, grafa...)
	public function displaySettings(){
		global $lang;
    			
				
		// Posebne opcije in navodile za anketo kviz		
		/*echo '<fieldset><legend>'.$lang['srv_kviz_navodila_1'].'</legend>';
		echo '<p>'.$lang['srv_kviz_navodila_2'].'</p>';
		echo '<p>'.$lang['srv_kviz_navodila_3'].'</p>';
		echo '<p>'.$lang['srv_kviz_navodila_4'].'</p>';
		echo '<p>'.$lang['srv_kviz_navodila_6'].' '.Help::display('DataPiping').'</p>';
		echo '<p>'.$lang['srv_kviz_navodila_7'].'</p>';
		echo '</fieldset>';*/

		
		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
		
		// Pridobimo trenutne nastavitve
		$settings = $this->getSettings();		
		
		// Prikaz rezultatov v zakljucku
		echo '<span class="nastavitveSpan1" >'.$lang['srv_quiz_results'].':</span>';
		echo '<label for="quiz_results_0"><input type="radio" name="quiz_results" id="quiz_results_0" value="0" '.(($settings['results'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'].'</label>';
		echo '<label for="quiz_results_1"><input type="radio" name="quiz_results" id="quiz_results_1" value="1" '.(($settings['results'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'].'</label>';	
		
		echo '<br />';
		
		// Prikaz grafa rezultatov v zakljucku
		echo '<span class="nastavitveSpan1" >'.$lang['srv_quiz_results_chart'].':</span>';
		echo '<label for="quiz_results_chart_0"><input type="radio" name="quiz_results_chart" id="quiz_results_chart_0" value="0" '.(($settings['results_chart'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'].'</label>';
		echo '<label for="quiz_results_chart_1"><input type="radio" name="quiz_results_chart" id="quiz_results_chart_1" value="1" '.(($settings['results_chart'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'].'</label>';	
		
		echo '<br /><br />';

		echo '</fieldset>';
		
		
		// Gumb shrani
		echo '<br class="clr" />';
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="quiz_save_settings(); return false;"><span>';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '<div id="success_save"></div>';	
	}
	
	
	// Pridobimo trenutne nastavitve kviza za anketo
	public function getSettings(){
		
		$settings = array();
		
		// Default vrednosti
		$settings['results'] = '1';
		$settings['results_chart'] = '0';
		
		$sql = sisplet_query("SELECT * FROM srv_quiz_settings WHERE ank_id='".$this->anketa."'");
		if(mysqli_num_rows($sql) > 0){	
			$row = mysqli_fetch_array($sql);
			
			$settings['results'] = $row['results'];
			$settings['results_chart'] = $row['results_chart'];
		}
		
		return $settings;
	}
	
	
	public function ajax() {
		
		if(isset($_GET['a']) && $_GET['a'] == 'save_settings'){
			
			$results = isset($_POST['results']) ? $_POST['results'] : '';
			$results_chart = isset($_POST['results_chart']) ? $_POST['results_chart'] : '0';
			
			$sql = sisplet_query("INSERT INTO srv_quiz_settings 
									(ank_id, results, results_chart) VALUES ('".$this->anketa."', '".$results."', '".$results_chart."') 
									ON DUPLICATE KEY UPDATE results='".$results."', results_chart='".$results_chart."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		}

	}
}