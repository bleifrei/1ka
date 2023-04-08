<?php

/*
 * Created on 20.5.2009
 *
 */

define("DEF_ALERT_IN_DAYS", 3);	# koliko dni prez izteko aktivira alert avtomatsko

class SurveyAlert
{

	static private $instance; 								// instanca razreda (razred kreiramo samo enkrat)
	static private $userId = null; 							// user id
	static private $surveyId = null; 						// id ankete
	static private $data = array();

	// konstrutor
	protected function __construct() {}
	// kloniranje
	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveyAlert();
		}
		return self::$instance;
	}

	/** Inicializacija
	 *
	 */
	static function Init($sid, $uid) {
		self::setSurveyId($sid); 
		self::setUserId($uid);
		
		self::loadDataFromDB();
	}
	// GETTERS & SETTERS
	static function getSurveyId()					{ return self::$surveyId; }
	static function setSurveyId($sid)				{ self::$surveyId = $sid; }
	static function getUserId()						{ return self::$userId; }
	static function setUserId($uid)					{ self::$userId = $uid; }
	
	/** Naloži podatke iz baze v klass
	 * 
	 */
	static function loadDataFromDB() {
		
		// podatki obveščanja
		$sqlA = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '".self::getSurveyId()."'");
		$sqlD = sisplet_query("SELECT d.*, u.name, u.surname, u.email FROM srv_dostop AS d " .
				"RIGHT JOIN (SELECT u.id, u.name, u.surname, u.email FROM users as u) AS u ON d.uid = u.id WHERE d.ank_id = '".self::getSurveyId()."'");
		$rowA = mysqli_fetch_assoc($sqlA);

		$result=array();

		$ostala_sinhronizirana_polja = array('finish_respondent', 'finish_respondent_cms', 'finish_author', 'finish_other', 'finish_other_emails', 'finish_text', 'finish_subject', 'expire_days', 'expire_author', 'expire_other', 'expire_other_emails', 'expire_text', 'expire_subject', 'delete_author', 'delete_other', 'delete_other_emails', 'delete_text', 'delete_subject', 'active_author', 'active_other', 'active_other_emails', 'active_text0', 'active_subject0', 'active_text1', 'active_subject1');
		foreach ($ostala_sinhronizirana_polja as $polje) {
			$result[$polje] = $rowA[$polje];
		}

		// dodamo še podatke iz dostopa
		while ($rowD = mysqli_fetch_assoc($sqlD)) {
			$result['dostop'][] = $rowD; 
		} 

		// Še nekateri podatki ankete		
		$catch_expire_date = isset($result['expire_days']) && $result['expire_days'] != '0000-00-00' ? ", DATE_SUB(expire,INTERVAL ".$result['expire_days']." DAY) as alert_date" : ""; 
		$sqlS = sisplet_query("SELECT active, naslov, insert_uid, expire ".$catch_expire_date." FROM srv_anketa  WHERE id = '".self::getSurveyId()."'");

		$rowS = mysqli_fetch_assoc($sqlS);
		$result['expire'] = $rowS['expire']; 		
		$result['survey_naslov'] = $rowS['naslov']; 		
		$result['survey_active'] = $rowS['active'];
		$result['author_uid'] = $rowS['insert_uid'];
		$result['alert_date'] = $rowS['alert_date'];

		self::$data = array();
		self::$data = $result;

		return $result; 		
	}

	/** prepareSendExpireAlerts()
	 *  Funkcija v bazo surveycrontab (tabela: srv_alert) shrani podatke za pošiljanje emailov
	 *  Pošiljanje emailov se izvjaja z razredom CronJobs ki 
	 *  se s pomočjo crontaba zaganja vsak dan samodejno.
	 */
	static function prepareSendExpireAlerts() {
		global $site_url, $lang, $mysql_database_name, $app_settings;
        
        // napolnimo tabelo srv_alert v bazi surveycrontab
		$sqlSurvey = sisplet_query("SELECT active FROM srv_anketa WHERE id='".self::getSurveyId()."'");
		$rowSurvey = mysqli_fetch_assoc($sqlSurvey);
		
		$oldDb = $mysql_database_name; 
		
		# ce je anketa aktivna, dodamo zapise v bazo, če je anketa neaktivna pa jih odstranimo
		if ($rowSurvey['active'] == 1) {
			#anketa je aktivna
							
			// poiscemo e-maile od avtorja in userjev v dostopu
			$emails = "";
			$prefix = "";
			if (self::$data['expire_author']) {
				$sqlAuthor = sisplet_query("SELECT name, surname, id, email FROM users WHERE id='".self::$data['author_uid']."'");
				$rowAuthor = mysqli_fetch_assoc($sqlAuthor);
				if ($rowAuthor['email'] != "" ) {
					$emails .= $prefix.$rowAuthor['email'];
					$prefix = ",";		
				}	
				if (self::$data['dostop']) 
				foreach (self::$data['dostop'] as $user) {
					if ($user['alert_expire'] == 1) {
						$emails.=$prefix.$user['email'];
						$prefix = ",";
					}
				}
			}
			if (self::$data['expire_other']) {
				foreach (explode("\n",self::$data['expire_other_emails']) as $other) {
					$emails.=$prefix.trim($other);
					$prefix = ",";
				}
			}
	
			// pripravimo vsebino e-maila
			$pdf_url = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
						serialize(
									array('a'=>'pdf_results',
										  'anketa'=>self::$surveyId,
										  'usr_id'=>self::$data['author_uid'],
										  'type'=>'0')));
			
			$rtf_url = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
					serialize(
							array(	'a'=>'rtf_results',
									'anketa'=>self::$surveyId,
									'b'=>'export',
									'usr_id'=>self::$data['author_uid'],
									)));
            
            // Custom podpis
            $signature = Common::getEmailSignature();

			$text = ( self::$data['expire_text'] != '' ) ? self::$data['expire_text'] : nl2br($lang['srv_alert_expire_text'].$signature);
			$text = str_replace(
				array(
					'[SURVEY]',
					'[DATE]',
					'[SITE]',
					'[DAYS]',
					'[URL]',
        			'[PDF]',
        			'[RTF]',
					'[DURATION]'),
				array(
					self::$data['survey_naslov'],
					date('r'),
					$site_url.'main/survey/index.php?anketa='.self::getSurveyId(),
					self::$data['expire_days'],
					'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'">'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'</a>',
        			'<a href="'.$pdf_url.'">'.$pdf_url.'</a>',
        			'<a href="'.$rtf_url.'">'.$rtf_url.'</a>',
					'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'&amp;a=trajanje">'.$lang['srv_activate_duration'].'</a>')
				, $text);
	
			$subject = ( self::$data['expire_subject'] != '' ) ? self::$data['expire_subject'] : $lang['srv_alert_expire_subject'];
			$subject = str_replace(
				array(
					'[SURVEY]',
					'[DATE]',
					'[SITE]',
					'[DAYS]',
					'[URL]',
        			'[PDF]',
        			'[RTF]',
					'[DURATION]'), 
				array(
					self::$data['survey_naslov'],
					date('r'),
					$site_url.'main/survey/index.php?anketa='.self::getSurveyId(), 
					self::$data['expire_days'],
					'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'">'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'</a>',
        			'<a href="'.$pdf_url.'">'.$pdf_url.'</a>',
        			'<a href="'.$rtf_url.'">'.$rtf_url.'</a>',
					'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'&amp;a=trajanje">'.$lang['srv_activate_duration'].'</a>')
				,$subject);
	
			// pripravimo mail
		    Common::getInstance()->Init(self::getSurveyId());
		    $MailFrom = Common::getInstance()->getFromEmail();
		    $MailReply = Common::getInstance()->getReplyToEmail();
			if ($emails != "" && $text != "" && $subject != "" && self::$data['alert_date'] != "") {
                
                // izberemo bazo srvcrontab
				$db = mysqli_select_db($GLOBALS['connect_db'],'surveycrontab');
				//or die($lang['srv_alert_database_error']);
                
                if ($db) {
					# najprej pobrišemo stare vrendosti, ker se alerti niso spremenili ob trajni anketi
					$del = sisplet_query("DELETE FROM srv_alert WHERE sid='".self::getSurveyId()."'");
					// najprej preverimo ali imamo za obstojeco anketo se kaj v crontabu (status = 0) in popravimo na status = 2 (spremenjen)
					// vsaka anketa lahko ima samo en zapis z statusom 0 (se ni slo v crontab)
					#$sqlUpdateOld = sisplet_query("UPDATE srv_alert SET status = 2 WHERE dbname = '".$oldDb."' AND sid = '".self::getSurveyId()."' AND status = 0");
					
					// dodamo zapis v crontab
					
					# trajnih anket ne dodajamo
					if (self::$data['expire'] != '2099-01-01') {
						$sqlInsertString = "INSERT INTO srv_alert (dbname, sid, emails, text, subject, send_date, status, MailFrom, MailReply) " .
								"VALUES ('".$oldDb."', '".self::getSurveyId()."', '".$emails."', '".$text."', '".$subject."', '".self::$data['alert_date']."', 0, '".$MailFrom."', '".$MailReply."')";
						$sqlInsert = sisplet_query($sqlInsertString);
					}
                }
                
                // uporabimo spet staro bazo
				mysqli_select_db($GLOBALS['connect_db'],$oldDb);
			} 
			
				
		} else {
			# ankata je deaktivirana, vse alerte ki so še aktivni spremenimo v status 3
			$db = mysqli_select_db($GLOBALS['connect_db'],'surveycrontab');
            
            if ($db) {
				//	or die($lang['srv_alert_database_error']);
				# nastavimo status na 3 - sprememba aktivnosti ankete
				$sqlUpdateOld = sisplet_query("UPDATE srv_alert SET status = 3 WHERE dbname = '".$oldDb."' AND sid = '".self::getSurveyId()."' AND status = 0");
            }
            
            // uporabimo spet staro bazo
			mysqli_select_db($GLOBALS['connect_db'],$oldDb);
		}
		sisplet_query("COMMIT");
	}
	
	/** sendMailActive()
	 * Funkcija pošlje emaile ob spremembi aktivnosti ankete
	 */
	static function sendMailActive() {
        global $lang, $site_url, $site_path, $app_settings;
        
		// poiščemo vse email naslove
		// poiscemo e-maile od avtorja in userjev v dostopu
		$emails = array();
		if (self::$data['active_author']) {
			$sqlAuthor = sisplet_query("SELECT name, surname, id, email FROM users WHERE id='".self::$data['author_uid']."'");
			$rowAuthor = mysqli_fetch_assoc($sqlAuthor);
			if ($rowAuthor['email'] != "" ) {
				$emails[] =$rowAuthor['email'];
			}	
			foreach (self::$data['dostop'] as $user) {
				if ($user['alert_active'] == 1) {
					$emails[] = $user['email'];
				}
			}
		}
		if (self::$data['active_other']) {
			foreach (explode("\n",self::$data['active_other_emails']) as $other) {
				$emails[] = trim($other);
			}
		}
    
        // Podpis
        $signature = Common::getEmailSignature();

		// odvisno od statuse izberemo text in subject
		$text = ( self::$data['survey_active'] == 1 )
			? (( self::$data['active_text1'] != '' ) ? self::$data['active_text1'] : nl2br($lang['srv_alert_active_text1'].$signature)) 
			: (( self::$data['active_text0'] != '' ) ? self::$data['active_text0'] : nl2br($lang['srv_alert_active_text0'].$signature));

		$subject = ( self::$data['survey_active'] == 1 )
			? (( self::$data['active_subject1'] != '' ) ? self::$data['active_subject1'] : $lang['srv_alert_active_subject1']) 
			: (( self::$data['active_subject0'] != '' ) ? self::$data['active_subject0'] : $lang['srv_alert_active_subject0']);

		$pdf_url = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
		serialize(
				array('a'=>'pdf_results',
						'anketa'=>self::$surveyId,
						'usr_id'=>self::$data['author_uid'],
						'type'=>'0')));
		
		$rtf_url = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'a'=>'rtf_results',
								'anketa'=>self::$surveyId,
								'b'=>'export',
								'usr_id'=>self::$data['author_uid'],
						)));
		$text = str_replace(
			array(
				'[SURVEY]',
				'[DATE]',
				'[SITE]',
				'[DAYS]',
				'[URL]',
        		'[PDF]', 
        		'[RTF]'), 
			array(
				self::$data['survey_naslov'],
				date('r'),
				$site_url.'main/survey/index.php?anketa='.self::getSurveyId(), 
				self::$data['expire_days'],
				'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'">'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'</a>',
        		'<a href="'.$pdf_url.'">'.$pdf_url.'</a>',
        		'<a href="'.$rtf_url.'">'.$rtf_url.'</a>'),
			$text);
			
		$subject = str_replace(
			array(
				'[SURVEY]',
				'[DATE]',
				'[SITE]',
				'[DAYS]',
				'[URL]',
        		'[PDF]', 
        		'[RTF]'), 
			array(
				self::$data['survey_naslov'],
				date('r'),
				$site_url.'main/survey/index.php?anketa='.self::getSurveyId(), 
				self::$data['expire_days'],
				'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'">'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'</a>',
        		'<a href="'.$pdf_url.'">'.$pdf_url.'</a>', 
        		'<a href="'.$rtf_url.'">'.$rtf_url.'</a>'), 
			$subject);

	    		    
		// preprečimo večkratno pošiljanje na iste naslove
		array_unique($emails);
		$spisek = "";
		$send_success = $send_errors = array();
	    

	    // posljemo maile
	    foreach ($emails AS $email) {
	    	$email = trim($email);
		    if (strlen ($email) > 1 && strpos ($spisek, $email)===false || strlen ($spisek) == 0) {

		    	try
		    	{
		    		$MA = new MailAdapter(self::getSurveyId(), $type='alert');
	    			$MA->addRecipients($email);
	    			$resultX = $MA->sendMail($text, $subject);
		    	}
		    	catch (Exception $e)
		    	{
		    	}
		    			    
			    $spisek .= $email ."|";
				if ($resultX) {
					$send_success[] = $email;
				} else {
					$send_errors[] = $email;
				}
		    }
	    }
	}	
	
	/** sendMailDelete()
	 * Funkcija pošlje emaile ob izbrisu ankete
	 */
	static function sendMailDelete() {
		global $lang, $site_url, $site_path, $app_settings;

		// poiščemo vse email naslove
		// poiscemo e-maile od avtorja in userjev v dostopu
		$emails = array();
		if (self::$data['delete_author']) {
			$sqlAuthor = sisplet_query("SELECT name, surname, id, email FROM users WHERE id='".self::$data['author_uid']."'");
			$rowAuthor = mysqli_fetch_assoc($sqlAuthor);
			if ($rowAuthor['email'] != "" ) {
				$emails[] =$rowAuthor['email'];
			}	
			foreach (self::$data['dostop'] as $user) {
				if ($user['alert_delete'] == 1) {
					$emails[] = $user['email'];
				}
			}
		}
		if (self::$data['delete_other']) {
			foreach (explode("\n",self::$data['delete_other_emails']) as $other) {
				$emails[] = trim($other);
			}
		}
    
        // Custom podpis
        $signature = Common::getEmailSignature();

		// odvisno od statuse izberemo text in subject
		$text = ( self::$data['delete_text'] != '' ) ? self::$data['delete_text'] : nl2br($lang['srv_alert_delete_text'].$signature); 

		$subject = ( self::$data['delete_subject'] != '' ) ? self::$data['delete_subject'] : $lang['srv_alert_delete_subject']; 
		
		$pdf_url = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array('a'=>'pdf_results',
								'anketa'=>self::$surveyId,
								'usr_id'=>self::$data['author_uid'],
								'type'=>'0')));
		
		$rtf_url = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'a'=>'rtf_results',
								'anketa'=>self::$surveyId,
								'b'=>'export',
								'usr_id'=>self::$data['author_uid']
						)));
		
		$text = str_replace(
			array(
				'[SURVEY]',
				'[DATE]',
				'[SITE]',
				'[DAYS]',
				'[URL]',
				'[PDF]', 
				'[RTF]'), 
			array(
				self::$data['survey_naslov'],
				date('r'),
				$site_url.'main/survey/index.php?anketa='.self::getSurveyId(), 
				self::$data['expire_days'],
				'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'">'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'</a>',
        		'<a href="'.$pdf_url.'">'.$pdf_url.'</a>',
        		'<a href="'.$rtf_url.'">'.$rtf_url.'</a>'),
			$text);
		
			$subject = str_replace(
				array(
					'[SURVEY]',
					'[DATE]',
					'[SITE]',
					'[DAYS]',
					'[URL]',
					'[PDF]', 
					'[RTF]'), 
				array(
					self::$data['survey_naslov'],
					date('r'),
					$site_url.'main/survey/index.php?anketa='.self::getSurveyId(), 
					self::$data['expire_days'],
					'<a href="'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'">'.$site_url.'admin/survey/index.php?anketa='.self::getSurveyId().'</a>',
        			'<a href="'.$pdf_url.'">'.$pdf_url.'</a>',
        			'<a href="'.$rtf_url.'">'.$rtf_url.'</a>'),
				$subject);

		// preprečimo večkratno pošiljanje na iste naslove
		array_unique($emails);
		$spisek = "";
		$send_success = $send_errors = array();
	    
	    // posljemo maile
	    foreach ($emails AS $email) {
	    	$email = trim($email);
		    if (strlen ($email) > 1 && strpos ($spisek, $email)===false || strlen ($spisek) == 0) {

		    	try{
		    		$MA = new MailAdapter(self::getSurveyId(), $type='alert');
	    			$MA->addRecipients($email);
	    			$resultX = $MA->sendMail($text, $subject);
		    	}
		    	catch (Exception $e)
		    	{
		    	}
		    	
                $spisek .= $email ."|";
                
				if ($resultX) {
					$send_success[] = $email;
                } 
                else {
					$send_errors[] = $email;
				}
		    }
	    }
	}
	
	static function setDefaultAlertBeforeExpire() {
		global $lang, $site_url, $site_path, $app_settings;
		
		$turn_on_alert = false;
		
		# vseeno prvo preverimo ali ni zapisa za alert že v bazi
		$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '".self::getSurveyId()."'");
		if (mysqli_num_rows($sqlAlert) > 0) {
			# zapis je ze v bazi in ga zato ne popravljamo
			$rowAlert = mysqli_fetch_array($sqlAlert);
			
			if($rowAlert['expire_subject'] == '')
				$turn_on_alert = true;
		}
		else{
			$turn_on_alert = true;
		}
		
		if($turn_on_alert) {

            // Custom podpis
            $signature = Common::getEmailSignature();

			# zapisa še ni v bazi, zato dodamo nov alert 3 dni pred koncem
			$alert_expire_author = 1;
			$alert_expire_other = 0; 
			$alert_expire_other_emails = '';
			$alert_expire_text = nl2br($lang['srv_alert_expire_text'].$signature);
			$alert_expire_subject = $lang['srv_alert_expire_subject'];
	
			$mySqlInsert = sisplet_query("INSERT INTO srv_alert (ank_id, expire_days, expire_author, expire_other, expire_other_emails, expire_subject, expire_text) VALUES " .
			"('".self::getSurveyId()."', '".DEF_ALERT_IN_DAYS."', '$alert_expire_author', '$alert_expire_other', '$alert_expire_other_emails', '$alert_expire_subject', '$alert_expire_text') " .
			"ON DUPLICATE KEY UPDATE expire_days = '".DEF_ALERT_IN_DAYS."', expire_author = '$alert_expire_author', expire_other = '$alert_expire_other', expire_other_emails='$alert_expire_other_emails', expire_subject='$alert_expire_subject', expire_text='$alert_expire_text'");
			if (!$mySqlInsert)
				echo mysqli_error($GLOBALS['connect_db']);
		}
		
		#osvežimo podatke
		self::loadDataFromDB();
		self::prepareSendExpireAlerts();
		
		$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '".self::getSurveyId()."'");
		
		$rowAlert = mysqli_fetch_array($sqlAlert);
		
		return $rowAlert;
	}
	
	// Nastavimo obvescanje pri aktivaciji (default ob kreiranju ankete)
	static function setDefaultAlertActivation() {
		global $lang;
        global $global_user_id;
        global $app_settings;
		
		$anketa = self::getSurveyId();
		
		$alert_active_author = 1;
		$alert_active_other = 0;

        // Custom podpis 
        $signature = Common::getEmailSignature();

		$alert_active_subject0 = $lang['srv_alert_active_subject0'];
		$alert_active_text0 = nl2br($lang['srv_alert_active_text0'].$signature);
		$alert_active_subject1 = $lang['srv_alert_active_subject1'];
		$alert_active_text1 = nl2br($lang['srv_alert_active_text1'].$signature);
		
		$mySqlInsert = sisplet_query("INSERT INTO srv_alert (ank_id, active_author, active_other, active_other_emails, active_subject0, active_text0, active_subject1, active_text1) VALUES " .
		"('$anketa', '$alert_active_author', '$alert_active_other', '$alert_active_other_emails', '$alert_active_subject0', '$alert_active_text0', '$alert_active_subject1', '$alert_active_text1') " .
		"ON DUPLICATE KEY UPDATE active_author = '$alert_active_author', active_other = '$alert_active_other', active_other_emails='$alert_active_other_emails', active_subject0='$alert_active_subject0', active_text0='$alert_active_text0', active_subject1='$alert_active_subject1', active_text1='$alert_active_text1'");

		if (!$mySqlInsert)
			echo mysqli_error($GLOBALS['connect_db']);

		// ponastavimo alert_admin
		// najprej vse stare zapise postavimo na 0 nato pa setiramo na 1 kjer je potrebno
		$mysqlUpdate = sisplet_query("UPDATE srv_dostop SET alert_active='0' WHERE ank_id = '$anketa'");
		$sqlInsertUpdate = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, alert_active) VALUES ('$anketa', '$global_user_id', 1) ON DUPLICATE KEY UPDATE alert_active=1");
		if (!$sqlInsertUpdate)
			echo mysqli_error($GLOBALS['connect_db']);
	}
		
}
?>