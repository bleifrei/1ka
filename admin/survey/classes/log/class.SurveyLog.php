<?php 

/**
 *
 *	Logiranje v 1ki
 *	
 *	 Logi se nahajajo v direktoriju logs/
 *	 Spororcila se zapisujejo v datoteko Y-m-d.log
 *
 */
 
class SurveyLog {
	

	// Log message types
	const ERROR 	= 'ERROR';
	const DEBUG 	= 'DEBUG';
	const INFO  	= 'INFO';
	const MAILER 	= 'MAILER';
	const IZVOZ 	= 'IZVOZ';
	const PAYMENT 	= 'PLACILO';
	
	
	private $messages = array();
	

	public function __construct(){
		global $site_path;
		
		define('LOG_FOLDER', $site_path.'logs/');
		
		if (!is_dir(LOG_FOLDER) OR !is_writable(LOG_FOLDER)){
			throw new Exception('Directory '.LOG_FOLDER.' must be writable');
		}
	}
	


	// Dodamo sporocilo (vrstico), ki se zapise v log
	public function addMessage($type, $message){

		// Display the time in the current locale timezone
		$time = date('Y-m-d H:i:s');

        // Popravimo, da nimamo čšž-jev
        $message = str_replace("č", "c", $message);
        $message = str_replace("š", "s", $message);
        $message = str_replace("ž", "z", $message);
        $message = str_replace("Č", "C", $message);
        $message = str_replace("Š", "S", $message);
        $message = str_replace("Ž", "Z", $message);

		$this->messages[] = array(
			'time' => $time,
			'type' => $type,
			'body' => $message,
		);
	}


	// Zapisemo sporocila v log file
	public function write(){
		
		// Nimamo sporocil - ne naredimo nicesar
		if (empty($this->messages)){
			return;
		}

		
		// Ime loga
		$filename = LOG_FOLDER.date('Y-m-d').'.log';

		// Ce dnevni log file se ne obstaja ga ustvarimo in nastavimo pravice
		if (!file_exists($filename)){
			
			// Create the log file
			file_put_contents($filename, 'Loging by class.SurveyLog.php'.PHP_EOL);

			// Allow anyone to write to log files
			//chmod($filename, 0666);
		}

		// Set the log line format
		$format = 'time --- type: body';

		
		// Loop cez vsa sporocila in zapis v file
		foreach ($this->messages as $message){
			
			file_put_contents($filename, PHP_EOL.strtr($format, $message), FILE_APPEND);
		}
		
		
		// Resetiramo array s sporocili
		$this->messages = array();
	}
	
}