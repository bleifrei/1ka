<?php
	
/*
 *  Modul za beleženje naprednih parapodatkov med izpolnjevanjem ankete
 *
 */
 	
class SurveyAdvancedParadataLog {
	
	
	private static $instance = false;
	
	private $collectParadata = false;
	
	private $anketa = 0;
	private $session_id = 0;
	
	
	// Privatni construct, ki ga 1x poklice getInstance
	private function __construct () {
		
        if((isset($_GET['m']) && $_GET['m'] == 'quick_edit') || (isset($_GET['t']) && $_GET['t'] == 'postprocess'))
            return false;
            
		$anketa_hash = $_REQUEST['anketa'];		
		$this->anketa = getSurveyIdFromHash($anketa_hash);	
		
		if($this->anketa > 0){
			SurveyInfo::getInstance()->SurveyInit($this->anketa);
			$this->collectParadata = (SurveyInfo::getInstance()->checkSurveyModule('advanced_paradata')) ? true : false;
		}
	}
	
	// Vrne instanco classa - da mamo singleton
	public static function getInstance () {
		
		if (!self::$instance)
			self::$instance = new SurveyAdvancedParadataLog();
							
		return self::$instance;
	}
	
	// Vrne ce zbiramo napredne parapodatke
	public function paradataEnabled(){

        if((isset($_GET['m']) && $_GET['m'] == 'quick_edit') || (isset($_GET['t']) && $_GET['t'] == 'postprocess'))
            return false;

		return $this->collectParadata;
    }
    
    // Vrne ce zbiramo post time
	public function collectPostTime(){

        $collectPostTime = true;

        $sql = sisplet_query("SELECT collect_post_time FROM srv_advanced_paradata_settings WHERE ank_id='".$this->anketa."'");
		if(mysqli_num_rows($sql) > 0){	

            $row = mysqli_fetch_array($sql);
            
            if($row['collect_post_time'] == '0')
                $collectPostTime = false;
        }
        
        return $collectPostTime;
    }
	
	
	// Ustvarimo polje v bazi za session (vezan na load posamezne strani) in nastavimo session_id za js
	public function prepareLogging () {
				
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		
		// Vstavimo v bazo novo polje za session na strani
		$sql = sisplet_query("INSERT INTO srv_advanced_paradata_page (ank_id, load_time, user_agent) VALUES ('".$this->anketa."', NOW(3), '".$user_agent."')");
		
		if (!$sql){
			echo mysqli_error($GLOBALS['connect_db']);
		}
		else{
			// Nastavimo session_id
			$this->session_id = mysqli_insert_id($GLOBALS['connect_db']);
			
			// Nastavimo session_id se za JS
			echo '<script> var _session_id = '.$this->session_id.'; </script>';
		}
	}
	
	// Zapiše log v bazo
	public function logData ($event_type, $event, $data) {
		
		switch ($event_type) {
			
			case 'page':	
				$this->logDataPage($event, $data);		
				break;
			
			case 'question':	
				$this->logDataQuestion($event, $data);		
				break;
				
			case 'vrednost':	
				$this->logDataVrednost($event, $data);		
				break;
				
			case 'other':	
				$this->logDataOther($event, $data);		
                break;
                
            case 'movement':	
				$this->logDataMovement($event, $data);		
				break;
				
			case 'alert':	
				$this->logDataAlert($event, $data);		
				break;
		}
	}
	
	// Zabelezimo dogodek na nivoju strani
	private function logDataPage($event, $data){

		$update = '';
		
		// Nastavimo katere parametre updatamo
		switch($event){
			case 'load_page':
				$update = " gru_id = '".$data['page']."', 
							usr_id = '".$data['usr_id']."', 
							recnum = '".$data['recnum']."', 
							language = '".$data['language']."', 
							load_time = '".$data['timestamp']."', 
							devicePixelRatio = '".$data['data']['devicePixelRatio']."', 
							width = '".$data['data']['width']."', 
							height = '".$data['data']['height']."', 
							availWidth = '".$data['data']['availWidth']."', 
							availHeight = '".$data['data']['availHeight']."', 
							jquery_windowW = '".$data['data']['jquery_windowW']."', 
							jquery_windowH = '".$data['data']['jquery_windowH']."', 
							jquery_documentW = '".$data['data']['jquery_documentW']."', 
							jquery_documentH = '".$data['data']['jquery_documentH']."'";
				break;
				
			case 'unload_page':
				$update = " post_time='".$data['timestamp']."' ";
				break;
		}	
		
		$sql = sisplet_query("UPDATE srv_advanced_paradata_page SET ".$update." WHERE id='$this->session_id'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		/*var_dump($data);
		echo "UPDATE srv_advanced_paradata_page SET ".$update." WHERE id='$this->session_id'";*/
	}
	
	// Zabelezimo dogodek na nivoju vprasanja
	private function logDataQuestion($event, $data){
		
		// Preverimo, ce gre ya vprasanje v ifu - potem se preveri da se zapise samo 1x
		$sqlU = sisplet_query("SELECT p.id
								FROM srv_advanced_paradata_page p, srv_advanced_paradata_question q
								WHERE p.usr_id='".$data['usr_id']."' AND p.id=q.page_id AND q.spr_id='".$data['data']['spr_id']."'
							");

		// Ce se nimamo vnosa za vprasanje in userja zapisemo
		if(mysqli_num_rows($sqlU) == 0){
		
			$sql = sisplet_query("INSERT INTO srv_advanced_paradata_question 
								(page_id, spr_id, vre_order) 
								VALUES 
								('".$this->session_id."', '".$data['data']['spr_id']."', '".$data['data']['vre_order']."')");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
	
	// Zabelezimo dogodek na nivoju vrednosti vprasanja
	private function logDataVrednost($event, $data){

		$value = isset($data['data']['value']) ? $data['data']['value'] : '';
			
		$sql = sisplet_query("INSERT INTO srv_advanced_paradata_vrednost 
								(page_id, spr_id, vre_id, time, event, value) 
								VALUES 
								('".$this->session_id."', '".$data['data']['spr_id']."', '".$data['data']['vre_id']."', '".$data['timestamp']."', '".$event."', '".$value."')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);	
	}
	
	// Zabelezimo ostale dogodke
	private function logDataOther($event, $data){
		
		$value = isset($data['data']['value']) ? $data['data']['value'] : '';
		$pos_x = isset($data['data']['pos_x']) ? $data['data']['pos_x'] : '';
		$pos_y = isset($data['data']['pos_y']) ? $data['data']['pos_y'] : '';
		$div_type = isset($data['data']['div_type']) ? $data['data']['div_type'] : '';
		$div_id = isset($data['data']['div_id']) ? $data['data']['div_id'] : '';
		$div_class = isset($data['data']['div_class']) ? $data['data']['div_class'] : '';
		
		$sql = sisplet_query("INSERT INTO srv_advanced_paradata_other 
								(page_id, time, event, value, pos_x, pos_y, div_type, div_id, div_class) 
								VALUES 
								('".$this->session_id."', '".$data['timestamp']."', '".$event."', '".$value."', '".$pos_x."', '".$pos_y."', '".$div_type."', '".$div_id."', '".$div_class."')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
	}
    
    // Zabelezimo premike miske
	private function logDataMovement($event, $data){
        
        $time_start_raw = mysqli_real_escape_string($GLOBALS['connect_db'], $data['data']['time_start']);
        $time_start = date("Y-m-d H:i:s", $time_start_raw/1000).'.'.substr($time_start_raw, -3);	

        $time_end_raw = mysqli_real_escape_string($GLOBALS['connect_db'], $data['data']['time_end']);
        $time_end = date("Y-m-d H:i:s", $time_end_raw/1000).'.'.substr($time_end_raw, -3);	
        
		$pos_x_start = isset($data['data']['pos_x_start']) ? $data['data']['pos_x_start'] : '';
		$pos_y_start = isset($data['data']['pos_y_start']) ? $data['data']['pos_y_start'] : '';
		$pos_x_end = isset($data['data']['pos_x_end']) ? $data['data']['pos_x_end'] : '';
        $pos_y_end = isset($data['data']['pos_y_end']) ? $data['data']['pos_y_end'] : '';
        $distance = isset($data['data']['distance']) ? $data['data']['distance'] : '';
		
		$sql = sisplet_query("INSERT INTO srv_advanced_paradata_movement
								(page_id, time_start, time_end, pos_x_start, pos_y_start, pos_x_end, pos_y_end, distance) 
								VALUES 
								('".$this->session_id."', '".$time_start."', '".$time_end."', '".$pos_x_start."', '".$pos_y_start."', '".$pos_x_end."', '".$pos_y_end."', '".$distance."')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
    }
    
	// Zabelezimo alerte
	private function logDataAlert($event, $data){
		
		$type = isset($data['data']['type']) ? $data['data']['type'] : '';
		$trigger_id = isset($data['data']['trigger_id']) ? $data['data']['trigger_id'] : 0;
		$trigger_type = isset($data['data']['trigger_type']) ? $data['data']['trigger_type'] : '';
		$ignorable = isset($data['data']['ignorable']) ? $data['data']['ignorable'] : 0;
		$text = isset($data['data']['text']) ? $data['data']['text'] : '';
		$action = isset($data['data']['action']) ? $data['data']['action'] : '';
		
		$timestamp_display_raw = isset($data['data']['time_display']) ? $data['data']['time_display'] : '';
		$timestamp_display = date("Y-m-d H:i:s", $timestamp_display_raw/1000).'.'.substr($timestamp_display_raw, -3);	

		$sql = sisplet_query("INSERT INTO srv_advanced_paradata_alert 
								(page_id, time_display, time_close, type, trigger_id, trigger_type, ignorable, text, action) 
								VALUES 
								('".$this->session_id."', '".$timestamp_display."', '".$data['timestamp']."', '".$type."', '".$trigger_id."', '".$trigger_type."', '".$ignorable."', '".$text."', '".$action."')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
	}
	
	
	// Izpise link na javascript datoteko v header htmlja
	public function linkJavaScript() {
		global $site_url;
        
		// Osnovni js za belezenje parapodatkov
        echo '  <script src="'.$site_url.'admin/survey/modules/mod_advanced_paradata/js/advanced_paradata.js"></script>'."\n";
        
        // Belezenje post tima (upocasni prehode cez strani)
        if($this->collectPostTime())
		    echo '  <script src="'.$site_url.'admin/survey/modules/mod_advanced_paradata/js/advanced_paradata_postTime.js"></script>'."\n";
		
		// JS za belezenje alertov
		echo '  <script src="'.$site_url.'admin/survey/modules/mod_advanced_paradata/js/sledenjeOpozoril.js"></script>'."\n";
	}
	
	// Izpise trenutno grupo v JS
	public function displayGrupa ($grupa) {
			
		echo '<script> var srv_meta_grupa_id = '.$grupa.'; </script>';
	}
	
	
	// Ajax klici
	public function ajax() {

		if ($_GET['a'] == 'logData') {
			$this->ajax_logData();
		}
	}
	
	// Logiranje eventa
	private function ajax_logData () {

		$this->session_id = $_POST['session_id'];
		
		$event_type = mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['event_type']);
		$event = mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['event']);
		
		$timestamp_raw = mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['timestamp']);
		$timestamp = date("Y-m-d H:i:s", $timestamp_raw/1000).'.'.substr($timestamp_raw, -3);	
		
		$data_array = array(
			'page' 	=> mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['page']),
			'usr_id' 	=> mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['usr_id']),
			'recnum' 	=> mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['recnum']),
			'language' 	=> mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['language']),
			'timestamp' => $timestamp,
			'data' 		=> $_POST['data']
		);

		var_dump($_POST);

		$this->logData($event_type, $event, $data_array);
	}
}
	
?>