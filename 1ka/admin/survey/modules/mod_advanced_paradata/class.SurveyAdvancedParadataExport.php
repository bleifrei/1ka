<?php

/*
 *  Modul za pripravo izvoza naprednih parapodatkov
 *
 */


class SurveyAdvancedParadataExport {

    var $anketa;			// ID ankete
    
    var $limit = 100;       // Max vrstic pri izpisu

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
	}
	
	
	// Izvozimo ustrezno tabelo v csv
	public function exportTable($table_name='srv_advanced_paradata_page'){
		global $site_path;
    
        ini_set('memory_limit', '4048M');

		// Dobimo naslove stolpcev
		$header = $this->getHeader($table_name);
		
		
		// Pripravimo datoteko za izvoz
		$file = $site_path.'admin/survey/modules/mod_advanced_paradata/temp/'.$table_name.'_'.$this->anketa.'.csv';
		$fd = fopen($file, "w");

		$convertTypes = array('charSet'	=> 'windows-1250',
							'delimit'	=> ',',
							'newLine'	=> "\n",
							'BOMchar'	=> "\xEF\xBB\xBF");
		# dodamo boomchar za utf-8
		fwrite($fd, $convertTypes['BOMchar']);
		
		// Zapisemo header row
		$header_line = '';
		foreach($header as $col){
			$header_line .= $col.',';
		}
		$header_line = substr($header_line, 0, -1);
		fwrite($fd, $header_line."\r\n");

        // Zapisemo vsako vrstico posebej
        // Dobimo vrstice s podatki
		switch($table_name){

			case 'srv_advanced_paradata_question':
				$data = $this->writeQuestionParadata($fd, $header);
				break;
			
			case 'srv_advanced_paradata_vrednost':
				$data = $this->writeVrednostParadata($fd, $header);
				break;

			case 'srv_advanced_paradata_other':
				$data = $this->writeOtherParadata($fd, $header);
                break;
                
            case 'srv_advanced_paradata_movement':
				$data = $this->writeMovementParadata($fd, $header);
                break;
                			
			case 'srv_advanced_paradata_alert':
				$data = $this->writeAlertParadata($fd, $header);
				break;

			default:
				$data = $this->writePageParadata($fd, $header);
				break;
		}
					
		fclose($fd);


		// Pripravimo file za download
		if(file_exists($file)){
			
			header('Content-Description: File Transfer');
			//header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename(''.$table_name.'_'.$this->anketa.'.csv'.''));
			
			header("Content-type: text/x-csv; charset=utf-8");
			//header("Content-type: text/csv");
			
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
		if (file_exists($file)) {
			unlink($file);
		}
		
		// Ugasnemo skripto:)
		die();
	}


	// Izpisemo tabelo parapodatkov vezanih na strani
	public function displayPageTable(){
		global $lang;
		
		$data = $this->getPageParadata();
		
		echo '<table class="advanced_paradata_table page_paradata styled_table">';
			
		echo '<tr>';
		echo '<th>ID</th>';
		echo '<th>User ID</th>';
		echo '<th>Page ID</th>';
		echo '<th>Recnum</th>';
		echo '<th>Load time</th>';
		echo '<th>Post time</th>';
		echo '<th>User Agent</th>';
		echo '<th>Device pixel ratio</th>';
		echo '<th>width x height</th>';
		echo '<th>availWidth x availHeight</th>';
		echo '<th>jQuery windowWidth x windowHeight</th>';
		echo '<th>jQuery documentWidth x documentHeight</th>';
		echo '<th>Language</th>';
		echo '</tr>';
		
		foreach($data as $row){
			
			echo '<tr>';
			
			echo '<td>'.$row['id'].'</td>';
			echo '<td>'.$row['usr_id'].'</td>';
			echo '<td>'.$row['gru_id'].'</td>';
			echo '<td>'.$row['recnum'].'</td>';
			echo '<td>'.$row['load_time'].'</td>';
			echo '<td>'.$row['post_time'].'</td>';
			echo '<td>'.$row['user_agent'].'</td>';
			echo '<td>'.$row['devicePixelRatio'].'</td>';
			echo '<td>'.$row['width'].'px X '.$row['height'].'px</td>';
			echo '<td>'.$row['availWidth'].'px X '.$row['availHeight'].'px</td>';
			echo '<td>'.$row['jquery_windowW'].'px X '.$row['jquery_windowH'].'px</td>';
			echo '<td>'.$row['jquery_documentW'].'px X '.$row['jquery_documentH'].'px</td>';
			echo '<td>'.$row['language'].'</td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
	}
	// Izpisemo tabelo parapodatkov vezanih na vprasanja
	public function displayQuestionTable(){
		
		$data = $this->getQuestionParadata();
		
		echo '<table class="advanced_paradata_table question_paradata styled_table">';
			
		echo '<tr>';
		echo '<th>Page session ID</th>';
		echo '<th>Question ID</th>';
		echo '<th>Order</th>';
		echo '</tr>';
		
		foreach($data as $row){
			
			echo '<tr>';
			
			echo '<td>'.$row['page_id'].'</td>';
			echo '<td>'.$row['spr_id'].'</td>';
			echo '<td>'.$row['vre_order'].'</td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	// Izpisemo tabelo parapodatkov vezanih na vredosti
	public function displayVrednostTable(){
		
		$data = $this->getVrednostParadata();
		
		echo '<table class="advanced_paradata_table vrednost_paradata styled_table">';
			
		echo '<tr>';
		echo '<th>Page session ID</th>';
		echo '<th>Question ID</th>';
		echo '<th>Vrednost ID</th>';
		echo '<th>Time</th>';
		echo '<th>Event</th>';
		echo '<th>Value</th>';
		echo '</tr>';
		
		foreach($data as $row){
			
			echo '<tr>';
			
			echo '<td>'.$row['page_id'].'</td>';
			echo '<td>'.$row['spr_id'].'</td>';
			echo '<td>'.$row['vre_id'].'</td>';
			echo '<td>'.$row['time'].'</td>';
			echo '<td>'.$row['event'].'</td>';
			echo '<td>'.$row['value'].'</td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	// Izpisemo tabelo ostalih parapodatkov
	public function displayOtherTable(){
		
		$data = $this->getOtherParadata();
		
		echo '<table class="advanced_paradata_table other_paradata styled_table">';
			
		echo '<tr>';
		echo '<th>Page session ID</th>';
		echo '<th>Time</th>';
		echo '<th>Event</th>';
		echo '<th>Value</th>';
		echo '<th>Position</th>';
		echo '<th>Element type</th>';
		echo '<th>Element id</th>';
		echo '<th>Element class</th>';
		echo '</tr>';
		
		foreach($data as $row){
			
			echo '<tr>';
			
			echo '<td>'.$row['page_id'].'</td>';
			echo '<td>'.$row['time'].'</td>';
			echo '<td>'.$row['event'].'</td>';
			echo '<td>'.$row['value'].'</td>';
			echo '<td>X: '.$row['pos_x'].', Y: '.$row['pos_y'].'</td>';
			echo '<td>'.$row['div_type'].'</td>';
			echo '<td>'.$row['div_id'].'</td>';
			echo '<td>'.$row['div_class'].'</td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
    }
    
    // Izpisemo tabelo premikov miske
	public function displayMovementTable(){
		
		$data = $this->getMovementParadata();
		
		echo '<table class="advanced_paradata_table movement_paradata styled_table">';
			
		echo '<tr>';
		echo '<th>Page session ID</th>';
		echo '<th>Time start</th>';
		echo '<th>Time end</th>';
		echo '<th>Position start</th>';
		echo '<th>Position end</th>';
		echo '<th>Distance traveled</th>';
		echo '</tr>';
		
		foreach($data as $row){
			
			echo '<tr>';
			
			echo '<td>'.$row['page_id'].'</td>';
			echo '<td>'.$row['time_start'].'</td>';
			echo '<td>'.$row['time_end'].'</td>';
			echo '<td>X: '.$row['pos_x_start'].', Y: '.$row['pos_y_start'].'</td>';
			echo '<td>X: '.$row['pos_x_end'].', Y: '.$row['pos_y_end'].'</td>';
			echo '<td>'.$row['distance'].'</td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	// Izpisemo tabelo ostalih parapodatkov
	public function displayAlertTable(){
		
		$data = $this->getAlertParadata();
		
		echo '<table class="advanced_paradata_table alert_paradata styled_table">';
			
		echo '<tr>';
		echo '<th>Page session ID</th>';
		echo '<th>Display time</th>';
		echo '<th>Close time</th>';
		echo '<th>Type</th>';
		echo '<th>Trigger ID</th>';
		echo '<th>Trigger type</th>';
		echo '<th>Ignorable</th>';
		echo '<th>Alert text</th>';
		echo '<th>User action</th>';
		echo '</tr>';
		
		foreach($data as $row){
			
			echo '<tr>';
			
			echo '<td>'.$row['page_id'].'</td>';
			echo '<td>'.$row['time_display'].'</td>';
			echo '<td>'.$row['time_close'].'</td>';
			echo '<td>'.$row['type'].'</td>';
			echo '<td>'.$row['trigger_id'].'</td>';
			echo '<td>'.$row['trigger_type'].'</td>';
			echo '<td>'.$row['ignorable'].'</td>';
			echo '<td>'.$row['text'].'</td>';
			echo '<td>'.$row['action'].'</td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	
	// Pridobimo naslove parapodatkov vezane na strani
	private function getHeader($table_name='srv_advanced_paradata_page'){

		$header = array();
		
		$sql = sisplet_query("SHOW columns FROM ".$table_name."");
		while($row = mysqli_fetch_array($sql)){
			$header[] = $row['Field'];
		}

		return $header;
	}

	// Pridobimo parapodatke vezane na strani
	private function getPageParadata($all=false){

		$data = array();
        
        $limit = $all ? '' : ' LIMIT '.$this->limit;

		$sql = sisplet_query("SELECT * FROM srv_advanced_paradata_page 
                                WHERE ank_id='".$this->anketa."' 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
			$data[] = $row;
		}
		
		return $data;
	}

	// Pridobimo parapodatke vezane na vprasanja
	private function getQuestionParadata($all=false){
		
        $data = array();
        
        $limit = $all ? '' : ' LIMIT '.$this->limit;
		
		$sql = sisplet_query("SELECT q.* FROM srv_advanced_paradata_question q, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND q.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
			$data[] = $row;
		}
		
		return $data;
	}
	
	// Pridobimo parapodatke vezane na vrednosti v vprasanju
	private function getVrednostParadata($all=false){
		
        $data = array();
        
        $limit = $all ? '' : ' LIMIT '.$this->limit;
		
		$sql = sisplet_query("SELECT v.* FROM srv_advanced_paradata_vrednost v, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND v.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
			$data[] = $row;
		}
		
		return $data;
	}
	
	// Pridobimo ostale parapodatke
	private function getOtherParadata($all=false){
		
		$data = array();
        
        $limit = $all ? '' : ' LIMIT '.$this->limit;

		$sql = sisplet_query("SELECT o.* FROM srv_advanced_paradata_other o, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND o.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
			$data[] = $row;
		}
		
		return $data;
    }
    
    // Pridobimo parapodatke premikov miske
	private function getMovementParadata($all=false){
		
		$data = array();
        
        $limit = $all ? '' : ' LIMIT '.$this->limit;

		$sql = sisplet_query("SELECT m.* FROM srv_advanced_paradata_movement m, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND m.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
			$data[] = $row;
		}
		
		return $data;
	}
	
	// Pridobimo parapodatke alertov
	private function getAlertParadata($all=false){
		
		$data = array();
        
        $limit = $all ? '' : ' LIMIT '.$this->limit;

		$sql = sisplet_query("SELECT a.* FROM srv_advanced_paradata_alert a, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND a.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
			$data[] = $row;
		}
		
		return $data;
    }
    

    // Zapisemo v datoteko parapodatke vezane na strani
	private function writePageParadata($fd, $header){

		$sql = sisplet_query("SELECT * FROM srv_advanced_paradata_page 
                                WHERE ank_id='".$this->anketa."' 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
  
            $data_line = '';
            foreach($header as $col){
                $data_line .= '\''.$row[$col].'\',';
            }
            $data_line = substr($data_line, 0, -1);
            
            fwrite($fd, $data_line."\r\n");
		}
	}

	// Zapisemo v datoteko parapodatke vezane na vprasanja
	private function writeQuestionParadata($fd, $header){

		$sql = sisplet_query("SELECT q.* FROM srv_advanced_paradata_question q, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND q.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
            
            $data_line = '';
            foreach($header as $col){
                $data_line .= '\''.$row[$col].'\',';
            }
            $data_line = substr($data_line, 0, -1);
            
            fwrite($fd, $data_line."\r\n");
		}
	}
	
	// Zapisemo v datoteko parapodatke vezane na vrednosti v vprasanju
	private function writeVrednostParadata($fd, $header){
		
		$sql = sisplet_query("SELECT v.* FROM srv_advanced_paradata_vrednost v, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND v.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
            
            $data_line = '';
            foreach($header as $col){
                $data_line .= '\''.$row[$col].'\',';
            }
            $data_line = substr($data_line, 0, -1);
            
            fwrite($fd, $data_line."\r\n");
		}
	}
	
	// Zapisemo v datoteko ostale parapodatke
	private function writeOtherParadata($fd, $header){
		
		$sql = sisplet_query("SELECT o.* FROM srv_advanced_paradata_other o, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND o.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
            
            $data_line = '';
            foreach($header as $col){
                $data_line .= '\''.$row[$col].'\',';
            }
            $data_line = substr($data_line, 0, -1);
            
            fwrite($fd, $data_line."\r\n");
		}
    }
    
    // Zapisemo v datoteko parapodatke premikov miske
	private function writeMovementParadata($fd, $header){
		
		$sql = sisplet_query("SELECT m.* FROM srv_advanced_paradata_movement m, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND m.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
            
            $data_line = '';
            foreach($header as $col){
                $data_line .= '\''.$row[$col].'\',';
            }
            $data_line = substr($data_line, 0, -1);
            
            fwrite($fd, $data_line."\r\n");
		}
	}
	
	// Zapisemo v datoteko parapodatke alertov
	private function writeAlertParadata($fd, $header){
		
		$sql = sisplet_query("SELECT a.* FROM srv_advanced_paradata_alert a, srv_advanced_paradata_page p 
								WHERE p.ank_id='".$this->anketa."' AND a.page_id=p.id 
                                ORDER BY id DESC ".$limit."");
		while($row = mysqli_fetch_array($sql)){
            
            $data_line = '';
            foreach($header as $col){
                $data_line .= '\''.$row[$col].'\',';
            }
            $data_line = substr($data_line, 0, -1);
            
            fwrite($fd, $data_line."\r\n");
		}
	}
}