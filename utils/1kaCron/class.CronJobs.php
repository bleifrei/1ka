<?php

/**
* 
*	Class, ki skrbi za vse cronjobe, ki se izvajajo na anketah
* 
*/


class CronJobs {
	
	
	function __construct () {

	}
	
	
	/**
	* izvede ustrezno akcijo
	* 
	*/
	public function executeAction($action = '') {
		
		// Izvedemo ustrezno akcijo	
		if(method_exists('CronJobs', $action) && $action != '')
			$this->$action();
		else
			echo 'Method '.$action.' does not exist!';
	}
	
	
	// Brisanje pobrisanih anket (anekte, ki so bile pobrisane pred vec kot 3 mesecih - prej je bilo samo 1 mesec)
	private function surveyDeleteFromDB(){
	
		// Loop po pobrisanih anketah, ki so bile nazadnje urejane vec kot 1 mesec nazaj
		$sql = sisplet_query("SELECT * FROM srv_anketa WHERE active='-1' AND edit_time < NOW() - INTERVAL 3 MONTH");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = new SurveyAdmin();
		
		while ($row = mysqli_fetch_array($sql)) {
			$s->anketa_delete_from_db($row['id']);
		}
	}
	
	
	// Brisanje pobrisanih podatkov anket (podatki, ki so bili pobrisani pred vec kot 3 meseci)
	private function userDeleteFromDB(){

		$sql = sisplet_query("DELETE FROM srv_user WHERE deleted='1' AND time_edit < NOW() - INTERVAL 3 MONTH");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
	}
	
	// Brisanje podatkov za "ping test" (pingdom) anketo (da ne polni baze brez potrebe)
	private function userPingdomDeleteFromDB(){
		global $mysql_database_name;
		global $site_domain;
		
		// Array vseh anket, ki se uporabljajo za monitoring (pingdom, uptime robot)
		$uptime_surveys = array(
			'www.1ka.si' => array(
				'142750',		// cloud (www.1ka.si)
				'10347'			// cloud (www.1ka.si) - stara
			),
			'izobrazevanja.safe.si' => array(
				'122358',		// tus (virtualke)
				'10347'			// tus (virtualke) - stara
			),
			'vist.1ka.si' => array(
				'356'			// tus (vist.1ka.si)
			),
			'gov-ankete.si' => array(
				'24'			// tus (gov-ankete.si)
			),
			'tools.evoli.si' => array(
				'73'			// tus (tools.evoli.si)
			),
			'test.1ka.si' => array(
				'6066' 			// nero (test.1ka.si)
			)
		);
		
		// Ce imamo nastavljen streznik - za virtualke uporabimo kar izobrazevanja.safe.si
		if(in_array($site_domain, array('www.1ka.si', 'izobrazevanja.safe.si', 'vist.1ka.si', 'tools.evoli.si', 'test.1ka.si'))){
						
			// Loop po vseh monitoring anketah na strezniku
			foreach($uptime_surveys[$site_domain] as $ank_id){
								
				$sqlPingTest = sisplet_query("DELETE FROM srv_user WHERE ank_id='".$ank_id."'");
				if (!$sqlPingTest) echo mysqli_error($GLOBALS['connect_db']);
			}
		}
	}
	
	
	// Kesiranje grafov
	private function cacheCharts(){

		// Kesriamo grafe za anketo, ki je bila urejana manj kot 10 dni nazaj - NE DELA ZARADI STATICNIH RAZREDOV
		/*$expire_time = 20;
	
		// loop cez vse ankete za katere urejamo cache
		$sql = sisplet_query("SELECT id FROM srv_anketa WHERE active!='-1' AND edit_time >= NOW() - INTERVAL ".$expire_time." DAY");
		while($row = mysqli_fetch_assoc($sql)){

			echo $row['id'].':<br />';
						
			SurveyChart::Init($row['id']);
			SurveyChart::createCache($charts_num = 5);
			
			if (SurveyChart::$returnChartAsHtml == false) {
				flush(); 
				ob_flush();
			}
			
			echo '<br /><br />';
		}*/
		
		// Na koncu pobrisemo vse stare grafe - ki so bili ustvarjeni vec kot 30 dni nazaj
		SurveyChart::clearCache(30);
	}
	
	// Kesiranje podatkovnih datotek
	private function cacheData(){
		global $site_url;
		global $site_path;
	
		// Po novem je to preneseno sem iz admin/survey/prepareDataIncremental.php
		ob_start();
		session_start();	
		
		
		$SL = new SurveyLog();
		$SL->addMessage(SurveyLog::INFO, "- - - - - - - - - - - - - - - - -");
		$SL->addMessage(SurveyLog::INFO, "Klic masovnega generiranja - start");
		
			
		$interval = 'INTERVAL 1 MONTH';
		
		# polovimo ankete ki so ble spremenjene v zadnjih treh mesecih
		$ids = array();
		$sql = "SELECT id AS ank_id " . 
				"FROM srv_anketa " .
				"WHERE active IN (0,1) " . 
				"AND (insert_time > DATE_SUB(NOW(), {$interval}) " .
				"	OR edit_time > DATE_SUB(NOW(), {$interval})) " .
			"UNION " .
				"SELECT ank_id " . 
				"FROM srv_user " . 
				"WHERE  preview = '0' " . 
				"AND deleted='0' " . 
				"AND ank_id > 0 " . 
				"AND (time_insert > DATE_SUB(NOW(), {$interval}) " .
				"	OR time_edit > DATE_SUB(NOW(), {$interval}))";
		$qry1 = sisplet_query($sql);
		while ($row = mysqli_fetch_assoc($qry1)) {
			$ids[] = $row['ank_id'];
		};
		

		$SL->addMessage(SurveyLog::INFO, "Klic masovnega generiranja - start");
		$SL->addMessage(SurveyLog::INFO, $sql);
		
		if (count($ids) > 0) {
			$SL->addMessage(SurveyLog::INFO, "St. anket: ".(int)count($ids));
			
			foreach ($ids AS $ank_id) {

				if ($ank_id > 0) {
                    
                    $SDF = SurveyDataFile::get_instance();
                    $SDF->init($ank_id);           
                    $SDF->prepareFiles(); 
				}
			}
		}
			
		$SL->addMessage(SurveyLog::INFO, "Klic masovnega generiranja - stop");
		$SL->write();
		
		ob_flush();
	}


    // Posiljanje obvestil o ??zakljucenih?? anketah - TODO
	private function surveySendAlert(){
		global $lang;
		global $site_path;
		global $site_url;
		global $global_user_id;
		global $admin_type;
		global $mysql_database_name;
		
		
		// Shranimo izbrano bazo
		$oldDb = $mysql_database_name;

		// Preklopimo na bazo za crontab
		mysqli_select_db($GLOBALS['connect_db'], 'surveycrontab');
		
		// Preberemo zapise na katere moramo poslati emaile 
		$today = date("Y-m-d");
		$qry = sisplet_query("SELECT * FROM srv_alert WHERE send_date <= '".$today."' AND status = 0");
		if (mysqli_num_rows($qry) > 0 ) {

			while ($row = mysqli_fetch_assoc($qry)) {
				$emails = array();
				$emails = explode(",",$row['emails']);
                    				
				// preprečimo večkratno pošiljanje na iste naslove
				array_unique($emails);
				$spisek = "";
				$send_success = $send_errors = array();
				$subject = iconv('utf-8', 'iso-8859-2', $row['subject']);
				
				// posljemo maile, vseeno preverimo ali imamo kak zapis 
				if (count($emails)) {
					foreach ($emails AS $email) {
						$email = trim($email);
						if (strlen ($email) > 1 && strpos ($spisek, $email)===false || strlen ($spisek) == 0) {

                            try{
                                $MA = new MailAdapter();

                                $MA->setMailFrom($row['MailFrom']);
                                $MA->setMailReplyTo($row['MailReply']);
                                $MA->addRecipients($email);
                                    
                                $resultX = $MA->sendMail(stripslashes($row['text']), $subject);

                                $spisek .= $email ."|";
                            }
                            catch (Exception $e){
                            }

                            if ($resultX) {
                                $send_success[] = $email;
                            } 
                            else {
                                $send_errors[] = $email;
                            }
						}
					}
				
					// popravimo zapis v crontab tabeli
					mysqli_select_db($GLOBALS['connect_db'], 'surveycrontab');
					$sqlUpdate = sisplet_query("UPDATE srv_alert SET status = 1, emails_success='".implode(",",$send_success)."', emails_failed='".implode(",",$send_errors)."' WHERE id = '".$row['id']."'");
				}
			}		
		} 
		else
			echo 'Nothing to send';
			 

		// Vrnemo nazaj na staro bazo
		mysqli_select_db($GLOBALS['connect_db'], $oldDb);
    }
    
    // Posljemo mail adminom, da si spremenijo geslo - TODO
	private function adminResetPassword(){
		global $site_url;
		global $site_path;
	

    }
    
    // Posljemo mail metaadminom in avtorju ankete 5 let po aktivaciji, da naj pobrisejo podatke - TODO
	private function gdprMailExpired(){
		global $site_url;
		global $site_path;
	

	}
        
    // Posljemo vsem MAZA aplikacijam pretecene/deaktivirane ankete
	private function mazaCheckExpiredSurveys(){
            $SL = new SurveyList();
            $SL -> checkSurveyExpire();
    }

}

?>