<?php

/**
 *
 * Class skrbi za pošiljanje mailov
 * @author Peter Hrvatin
 * 22.8.2019
 *
 */

class MailAdapter{

	private $ank_id = null;

    private $settings   = array();
	private $mailModes  = array(0=>'1ka', 1=>'google', 2=>'smtp');
    private $mailMode   = '1ka';	// 1ka, google, smtp
    private $recipients = array();
    
    /** Tip posiljanja
     *  "invitation"    - email vabilo
     *  "alert"         - email obvescanje (aktivacija ankete, zakljucena anketa, arhiv ankete...)
     *  "admin"         - email povezan z administracijo (alerti za admine)
     *  "account"       - email povezan z upravljanjem racuna (dodan dostop do anket...)
     *  "payments"      - email povezan s placili paketov (posiljanje racunov, predracunov...)
     */
	private $type = '';

    private $password_hash_key = '#&_ww.9$.1ka#"%o';

    private $phpMailerClass;

    /** Debugging
     *  0 = off (for production use)
     *  1 = client messages
     *  2 = client and server messages
     */
    private $phpMailerDebug = 0;


	public function __construct($anketa=null, $type=''){
		global $admin_type;
		global $mysql_database_name;
    
        // Nastavimo tip posiljanja
        $this->type = $type;

        // Inicializiramo phpMailer razred
        $this->prepareMailer();


        // Posiljanje iz ankete
		if ((int)$anketa > 0){ 
		
            $this->ank_id = $anketa;
           
            // Pripravimo nastavitve za posiljanje ankete
            $this->prepareSurveySettings();
        }
        // Splosno posiljanje (brez id-ja ankete)
		else{ 

            // Pripravimo splosne nastavitve aplikacije za posiljanje
            $this->prepareGeneralSettings();			
        }
    }
    


    // Pripravimo nastavitve posiljanja za doloceno anketo
    private function prepareSurveySettings(){
        global $admin_type;
        global $mysql_database_name;
        global $email_server_settings;
        global $email_server_fromSurvey;


        // Polovimo nastavitve ce obstajajo v bazi
        SurveySetting::getInstance()->Init($this->ank_id);
        $mail1kaSavedConfig = unserialize(SurveySetting::getInstance()->getSurveyMiscSetting('send_mail_config'));
        $this->settings = is_array($mail1kaSavedConfig) ? $mail1kaSavedConfig : array();


        // Nastavitve imamo ze shranjene v bazi - samo nastavimo ustrezen "mode"
        if(!empty($this->settings)){
           
            // Nastavimo "mode" posiljanja (1ka, gmail ali smtp)
            $mailMode = (int)$this->settings['SMTPMailMode'];
            
            if ($mailMode === 2){
                $this->mailMode = 'smtp';
            }
            else if ($mailMode === 1){
                $this->mailMode = 'google';
            }
            else{
                $this->mailMode = '1ka';
            }

			
            // Password dekodiramo
            if (isset($this->settings['1ka']['SMTPPassword'])){ 
                $this->settings['1ka']['SMTPPassword'] = $this->decryptPassword($this->settings['1ka']['SMTPPassword']);
            }
            if (isset($this->settings['google']['SMTPPassword'])){ 
                $this->settings['google']['SMTPPassword'] = $this->decryptPassword($this->settings['google']['SMTPPassword']);
            }
            if (isset($this->settings['smtp']['SMTPPassword'])){ 
                $this->settings['smtp']['SMTPPassword'] = $this->decryptPassword($this->settings['smtp']['SMTPPassword']);
            }
			
			
			// Pri 1ka nastavitvah lahko nastavljamo samo reply to, vse ostalo je veedno default
			if($this->settings['1ka']['SMTPReplyTo'] == '')
				$this->settings['1ka']['SMTPReplyTo'] = $email_server_settings['SMTPReplyTo'];
			
			$this->settings['1ka']['SMTPFrom'] = $email_server_settings['SMTPFrom'];
            $this->settings['1ka']['SMTPFromNice'] = $email_server_settings['SMTPFromNice'];    
            $this->settings['1ka']['SMTPHost'] = $email_server_settings['SMTPHost'];
            $this->settings['1ka']['SMTPPort'] = $email_server_settings['SMTPPort'];

            if(isset($email_server_settings['SMTPAuth']) && $email_server_settings['SMTPAuth'] == 1){
                $this->settings['1ka']['SMTPAuth'] = $email_server_settings['SMTPAuth'];
                $this->settings['1ka']['SMTPUsername'] = $email_server_settings['SMTPUsername'];
                $this->settings['1ka']['SMTPPassword'] = $email_server_settings['SMTPPassword'];
            }

            if(isset($email_server_settings['SMTPSecure']))
                $this->settings['1ka']['SMTPSecure'] = $email_server_settings['SMTPSecure'];

            // Pri google smtp je username vedno email
            if($this->mailMode == 'google')
                $this->settings['google']['SMTPUsername'] = $this->settings['google']['SMTPFrom'];
            
			// ce posiljamo mail vabila in smo na www.1ka.si oz. virutalkah in smo admin - posiljamo preko sekundarnega maila (raziskave@1ka.si)
			if($this->type == 'invitation' && $admin_type == 0 && isset($email_server_settings['secondary_mail'])){
				$this->settings['1ka']['SMTPFrom'] = $email_server_settings['secondary_mail']['SMTPFrom'];
				$this->settings['1ka']['SMTPFromNice'] = $email_server_settings['secondary_mail']['SMTPFromNice'];
				$this->settings['1ka']['SMTPReplyTo'] = $email_server_settings['secondary_mail']['SMTPReplyTo'];
				$this->settings['1ka']['SMTPUsername'] = $email_server_settings['secondary_mail']['SMTPUsername'];
				$this->settings['1ka']['SMTPPassword'] = $email_server_settings['secondary_mail']['SMTPPassword'];
            }

            // Nastavimo default delay
            if(!isset($this->settings['1ka']['SMTPDelay']) || $this->settings['1ka']['SMTPDelay'] == '' || $this->settings['1ka']['SMTPDelay'] == '0')
                $this->settings['1ka']['SMTPDelay'] = 500000;
        }
        // Nimamo se nicesar v bazi - nastavimo default nastavitve
        else{
            
            // Nastavimo 1ka smtp
            $this->settings['1ka']['SMTPFrom'] = $email_server_settings['SMTPFrom'];
            $this->settings['1ka']['SMTPFromNice'] = $email_server_settings['SMTPFromNice'];
            $this->settings['1ka']['SMTPReplyTo'] = $email_server_settings['SMTPReplyTo'];
            $this->settings['1ka']['SMTPHost'] = $email_server_settings['SMTPHost'];
            $this->settings['1ka']['SMTPPort'] = $email_server_settings['SMTPPort'];

            if(isset($email_server_settings['SMTPAuth']) && $email_server_settings['SMTPAuth'] == 1){
                $this->settings['1ka']['SMTPAuth'] = $email_server_settings['SMTPAuth'];
                $this->settings['1ka']['SMTPUsername'] = $email_server_settings['SMTPUsername'];
                $this->settings['1ka']['SMTPPassword'] = $email_server_settings['SMTPPassword'];
            }

            if(isset($email_server_settings['SMTPSecure']))
                $this->settings['1ka']['SMTPSecure'] = $email_server_settings['SMTPSecure'];

            // Nastavimo default delay
            $this->settings['1ka']['SMTPDelay'] = 500000;

            // Nastavimo gmail smtp
            $this->settings['google']['SMTPHost'] = 'smtp.gmail.com';
            $this->settings['google']['SMTPPort'] = '587';
            $this->settings['google']['SMTPSecure'] = 'tls';
            $this->settings['google']['SMTPAuth'] = 1;

            // Nastavimo default delay
            $this->settings['google']['SMTPDelay'] = 500000;

            
            // Ce imamo nastavljeno, da se za posiljanje iz ankete uporabi isti smtp streznik kot za generalno posiljanje
            if($email_server_fromSurvey){
                $this->prepareGeneralSettings();
            }
            else{

                // ce posiljamo mail vabila (default razlicno za admine in ostale)
                if($this->type == 'invitation'){

                    // Pri vabilih je default 1ka streznik samo na www.1ka.si in to samo za admine
                    if($admin_type == 0 && isset($email_server_settings['secondary_mail'])){
                        $this->mailMode = '1ka';
                        $this->settings['1ka']['SMTPFrom'] = $email_server_settings['secondary_mail']['SMTPFrom'];
                        $this->settings['1ka']['SMTPFromNice'] = $email_server_settings['secondary_mail']['SMTPFromNice'];
                        $this->settings['1ka']['SMTPReplyTo'] = $email_server_settings['secondary_mail']['SMTPReplyTo'];
                        $this->settings['1ka']['SMTPUsername'] = $email_server_settings['secondary_mail']['SMTPUsername'];
                        $this->settings['1ka']['SMTPPassword'] = $email_server_settings['secondary_mail']['SMTPPassword'];
                    }
                    // Drugace je potrebno nastaviti smtp
                    else{
                        $this->mailMode = 'smtp';
                    }
                }
                // Ce ne gre za vabila se uporabi kar 1ka streznik
                else{
                    //$this->prepareGeneralSettings();
                    $this->mailMode = '1ka';
                }
            }            
        }   
    }

    // Pripravimo nastavitve splosnega posiljanja v aplikaciji glede na nastavitve v settings_optional.php
    private function prepareGeneralSettings(){
        global $email_server_settings;
        global $mysql_database_name;
      
        $this->mailMode = 'smtp';
        $this->settings['SMTPMailMode'] = 2;

        $this->settings['smtp'] = array(
            'SMTPFrom'      => $email_server_settings['SMTPFrom'],
            'SMTPFromNice'  => $email_server_settings['SMTPFromNice'],
            'SMTPReplyTo'   => $email_server_settings['SMTPReplyTo'],

            'SMTPHost'   => $email_server_settings['SMTPHost'],
            'SMTPPort'   => $email_server_settings['SMTPPort']
        );

        if(isset($email_server_settings['SMTPAuth']) && $email_server_settings['SMTPAuth'] == 1){
            $this->settings['smtp']['SMTPAuth'] = $email_server_settings['SMTPAuth'];
            $this->settings['smtp']['SMTPUsername'] = $email_server_settings['SMTPUsername'];
            $this->settings['smtp']['SMTPPassword'] = $email_server_settings['SMTPPassword'];
        }

        if(isset($email_server_settings['SMTPSecure']))
            $this->settings['smtp']['SMTPSecure'] = $email_server_settings['SMTPSecure'];

        // ce posiljamo v povezavi s placili (racuni, predracuni...) - posiljamo preko tretjega maila (invoice@1ka.si)
        if($this->type == 'payments' && isset($email_server_settings['payments_mail']) && $mysql_database_name == 'real1kasi'){
            $this->settings['smtp']['SMTPFrom'] = $email_server_settings['payments_mail']['SMTPFrom'];
            $this->settings['smtp']['SMTPFromNice'] = $email_server_settings['payments_mail']['SMTPFromNice'];
            $this->settings['smtp']['SMTPReplyTo'] = $email_server_settings['payments_mail']['SMTPReplyTo'];
            $this->settings['smtp']['SMTPUsername'] = $email_server_settings['payments_mail']['SMTPUsername'];
            $this->settings['smtp']['SMTPPassword'] = $email_server_settings['payments_mail']['SMTPPassword'];
        }

        // Nastavimo default delay
        $this->settings['smtp']['SMTPDelay'] = 500000;
    }



	public function is1KA () {
		return $this->mailMode == '1ka';
	}
	public function isGoogle () {
		return $this->mailMode == 'google';
	}
	public function isSMTP () {
		return $this->mailMode == 'smtp';
    }
    
    // Vrnemo nastavitve posiljanja za dolocen mode
	public function getSettings($mailModeString = null){

        // Pogledamo za kateri "mode" pridobivamo nastavitve
		if ($mailModeString == null){
			$mailModeString = $this->getMailMode($asString=true);
        }      
        $result = $this->settings[$mailModeString];
        
        if(isset($result) && is_array($result))
            return $result;
        else
            return array();
    }

    // Vrnemo nastavitve posiljanja na podlagi requesta
    public function getSettingsFromRequest($request){

        $settings = array();
        
        $mode = $request['SMTPMailMode'];

		foreach ($request AS $pkey => $pvalue){

			// if starts with SMTP && END WITH $_REQUEST['send_mail_mode']
			if (!strncmp($pkey, "SMTP", strlen("SMTP"))	&& substr($pkey, -strlen($mode))===$mode){
				$settings[rtrim($pkey, "{$mode}")] = $pvalue;
			}
		}
        $settings['SMTPMailMode'] = $mode;
        
		return $settings;
	}
    
    // Vrnemo nastavitev from (email)
    public function getMailFrom(){
		$s = $this->getSettings();
		return $s['SMTPFrom'];
	}

    // Vrnemo nastavitev from (ime)
	public function getMailFromNice(){
		$s = $this->getSettings();
		return $s['SMTPFromNice'];
	}

    // Vrnemo reply-to nastavitev
	public function getMailReplyTo(){
		$s = $this->getSettings();
		return $s['SMTPReplyTo'];
	}

	
    // Vrnemo nastavitev mode-a posiljanja (1ka, google ali smtp)
	public function getMailMode($asString=false){

		if ($asString)
			return $this->mailMode;
		else
			return (int)array_search($this->mailMode, $this->mailModes);
	}

	
    // Vrnemo nastavitve za dolocen mode
	public function get1KASettings(){
	
		$result = $this->settings['1ka'];
        
        if(isset($result) && is_array($result))
            return $result;
        else
            return array();
	}

	public function getGoogleSettings(){
        
        $result = $this->settings['google'];
                
        if(isset($result) && is_array($result))
            return $result;
        else
            return array();
	}

	public function getSMTPSettings(){
        
        $result = $this->settings['smtp'];
        
        if(isset($result) && is_array($result))
            return $result;
        else
            return array();
	}

    

    // Nastavimo nastavitve za dolocen "mode" in jih shranimo v bazo
	public function setSettings($mode, $settings){
        
        foreach ($settings AS $key => $value){

			if ( $key == 'SMTPMailMode' ){
				$this->settings[$key] = $value;
            }
            
			// geslo shranimo samo če ni null
			else if ( $key != 'SMTPPassword' || ($key == 'SMTPPassword' && !empty($value)) ){
                                
				$this->settings[$this->mailModes[(int)$mode]][$key] = $value;
			}
		}
		
		$this->saveSettings();
	}

    // Shranimo nastavitve v bazo
	private function saveSettings(){
        
        $settings = $this->settings;

        // Passworde zakodiramo pred shranjevanjem v bazo
        if (isset($settings['1ka']['SMTPPassword'])){ 
            $settings['1ka']['SMTPPassword'] = $this->encryptPassword($settings['1ka']['SMTPPassword']);
        }
        if (isset($settings['google']['SMTPPassword'])){ 
            $settings['google']['SMTPPassword'] = $this->encryptPassword($settings['google']['SMTPPassword']);
        }
        if (isset($settings['smtp']['SMTPPassword'])){ 
            $settings['smtp']['SMTPPassword'] = $this->encryptPassword($settings['smtp']['SMTPPassword']);
        }

        $c = mysqli_real_escape_string($GLOBALS['connect_db'], serialize($settings));
		$succ = SurveySetting::getInstance()->setSurveyMiscSetting('send_mail_config', $c);
    }
    
    // Nastavimo reply to mail
	public function setMailReplyTo($reply_to){
        
        if($this->validEmail($reply_to)){
			$s = $this->getSettings();
			$s['SMTPReplyTo'] = $reply_to;
			
			$this->setSettings((int)$this->settings['SMTPMailMode'], $s);
		}
	}

    // Nastavimo from ime
	public function setMailFromNice($from_nice){

        $s = $this->getSettings();
        $s['SMTPFromNice'] = $from_nice;
        
        $this->setSettings((int)$this->settings['SMTPMailMode'], $s);
	}

    // Nastavimo from email
	public function setMailFrom($from){

		if($this->validEmail($from)){
			$s = $this->getSettings();
			$s['SMTPFrom'] = $from;
			
			$this->setSettings((int)$this->settings['SMTPMailMode'], $s);
		}
    }
    

    // Dodamo respondenta emaila
	public function addRecipients($recipient){

        // Ce imamo vec prejemnikov
        if(is_array($recipient)){

            foreach($recipient as $email){
                if ($this->validEmail($email))
			        $this->recipients[] = $email;
            }
        }
        else{
            if ($this->validEmail($recipient))
			    $this->recipients[] = $recipient;
        }
    }
    
    // Dodamo attachment
	public function addAttachment($file, $file_name){

        $this->phpMailerClass->addStringAttachment($file, $file_name);
	}



    // Inicializiramo phpmailer razred in nastavimo splosne nastavitve
    private function prepareMailer(){

        // Inicializiramo razred
        $this->phpMailerClass = new PHPMailer\PHPMailer\PHPMailer();
       
        // Nastavimo se debugging
        $this->phpMailerClass->SMTPDebug = $this->phpMailerDebug;


        // UTF8 encoding
		$this->phpMailerClass->CharSet = 'UTF-8';
			
		// Highest priority - Email priority (1 = High, 3 = Normal, 5 = low)
		$this->phpMailerClass->Priority = 3;
		
		// 8-bit encoding
		//$this->phpMailerClass->Encoding = '8bit';
		
		// RFC 2822 Compliant for Max 998 characters per line
		$this->phpMailerClass->WordWrap = 900;
        
        //$this->phpMailerClass->Helo = $settings["ServerHostname"];


        // Vedno posiljamo preko smtp
        $this->phpMailerClass->isSMTP(); 
    }

    // Posljemo mail
    public function sendMail($email_msg, $email_subject){
	    global $mysql_database_name;

        // Nastavimo ustrezen "mode"
		$mailModeString = $this->mailMode;
        
        // Dobimo nastavitve iz baze
		$settings = $this->getSettings($mailModeString);


        // Nastavimo mail server
        $this->phpMailerClass->Host = $settings["SMTPHost"];

        // Nastavimo SMTP port
        $this->phpMailerClass->Port = $settings["SMTPPort"];

        // Nastavimo ssl / tls
        $this->phpMailerClass->SMTPSecure = $settings['SMTPSecure'];

        // Nastavimo ce se uporablja SMTP avtentikacijo
        if($settings["SMTPAuth"] == 1)
            $this->phpMailerClass->SMTPAuth = true;

        // Nastavimo username za SMTP avtentikacijo
        $this->phpMailerClass->Username = $settings["SMTPUsername"];

        // Nastavimo password za SMTP avtentikacijo
        $this->phpMailerClass->Password = $settings["SMTPPassword"];


        // Posebej vklopimo, ker drugace sisplet smtp ne deluje!
        if($this->phpMailerClass->Host == 'mail.sisplet.org'){
            $this->phpMailerClass->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false
                )
            );
        }


        // Kdo posilja
		if(isset($settings["SMTPFromNice"]) && $settings["SMTPFromNice"] != "") {
			$this->phpMailerClass->SetFrom($settings["SMTPFrom"], $settings["SMTPFromNice"]);
		}
		else{
			$this->phpMailerClass->SetFrom($settings["SMTPFrom"]);
		}
		
		// Reply-to naslov
		$this->phpMailerClass->AddReplyTo($settings["SMTPReplyTo"]);
        
        // Subject
        $this->phpMailerClass->Subject = $email_subject;
       
        // Vsebina maila
        $this->prepareEmailDesign($email_msg);


        // Loop cez prejemnike in posiljanje
        if (!empty($this->recipients)){

            // Loop cez vse prejemnike
            foreach ($this->recipients AS $recipient){
                $this->phpMailerClass->AddAddress($recipient);
            }       
            
            // Posljemo mail
            $success =  $this->phpMailerClass->send();

			
			// Logiramo posiljanje
			$SL = new SurveyLog();

			// Napaka
			if (!$success) {

				if((int)$this->ank_id > 0)
					$SL->addMessage(SurveyLog::MAILER, "NAPAKA pri pošiljanju emaila iz ankete ".$this->ank_id." na naslove ".implode(",", $this->recipients)."! ".$this->phpMailerClass->ErrorInfo);
				else
					$SL->addMessage(SurveyLog::MAILER, "NAPAKA pri pošiljanju emaila na naslove ".implode(",", $this->recipients)."! ".$this->phpMailerClass->ErrorInfo);
				
                if($this->phpMailerDebug > 0)
					echo "<br />Mailer Error: " . $this->phpMailerClass->ErrorInfo.'<br /><br />';
			} 
			// Uspesno posiljanje
			else {

				if((int)$this->ank_id > 0)
					$SL->addMessage(SurveyLog::MAILER, "USPEŠNO pošiljanje emaila iz ankete ".$this->ank_id." na naslove ".implode(",", $this->recipients));
				else
					$SL->addMessage(SurveyLog::MAILER, "USPEŠNO pošiljanje emaila na naslove ".implode(",", $this->recipients));
							
                if($this->phpMailerDebug > 0)
					echo "Message sent!<br /><br />";
            }
            
            $SL->write();
                        
						
            // Dodamo pavzo po pošiljanju ce je nastavljena - default je vedno 2 / sekundo
            $delay = (isset($settings['SMTPDelay']) && intval($settings['SMTPDelay']) > 0) ? $settings['SMTPDelay'] : 500000;
            if($delay > 0){
                usleep ($delay);
            }

            return $success;
        }
    }

    // Posljemo testni mail pri testiranju nastavitev streznika
    public function sendMailTest($email_msg, $email_subject, $mailMode=null, $settings=null){
	    global $mysql_database_name;

        // Nastavimo ustrezen "mode"
		$mailModeString = $this->mailModes[$mailMode];


        // Ce gre za gmail ali 1ka napolnimo default podatke
        if(!isset($settings["SMTPHost"]))
            $settings["SMTPHost"] = $this->settings[$mailModeString]["SMTPHost"];
        if(!isset($settings["SMTPPort"]))
            $settings["SMTPPort"] = $this->settings[$mailModeString]["SMTPPort"];
        if(!isset($settings["SMTPSecure"]))
            $settings["SMTPSecure"] = $this->settings[$mailModeString]["SMTPSecure"];
        if(!isset($settings["SMTPAuth"]))
            $settings["SMTPAuth"] = $this->settings[$mailModeString]["SMTPAuth"];

        if(!isset($settings["SMTPUsername"]))
            $settings["SMTPUsername"] = $this->settings[$mailModeString]["SMTPUsername"];
        if(!isset($settings["SMTPPassword"]))
            $settings["SMTPPassword"] = $this->settings[$mailModeString]["SMTPPassword"];

        // Pri google smtp je username vedno email
		if($mailModeString == 'google')
            $settings['SMTPUsername'] = $settings['SMTPFrom'];


        // Nastavimo mail server
        $this->phpMailerClass->Host = $settings["SMTPHost"];

        // Nastavimo SMTP port
        $this->phpMailerClass->Port = $settings["SMTPPort"];

        // Nastavimo ssl / tls
        $this->phpMailerClass->SMTPSecure = $settings['SMTPSecure'];

        // Nastavimo ce se uporablja SMTP avtentikacijo
        if($settings["SMTPAuth"] == 1)
            $this->phpMailerClass->SMTPAuth = true;

        // Nastavimo username za SMTP avtentikacijo
        $this->phpMailerClass->Username = $settings["SMTPUsername"];

        // Nastavimo password za SMTP avtentikacijo
        $this->phpMailerClass->Password = $settings["SMTPPassword"];


        // Posebej vklopimo, ker drugace sisplet smtp ne deluje!
        if($this->phpMailerClass->Host == 'mail.sisplet.org'){
            $this->phpMailerClass->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false
                )
            );
        }


        // Kdo posilja
		if(isset($settings["SMTPFromNice"]) && $settings["SMTPFromNice"] != "") {
			$this->phpMailerClass->SetFrom($settings["SMTPFrom"], $settings["SMTPFromNice"]);
		}
		else{
			$this->phpMailerClass->SetFrom($settings["SMTPFrom"]);
		}
	
		// Reply-to naslov
		$this->phpMailerClass->AddReplyTo($settings["SMTPReplyTo"]);
        
        // Subject
        $this->phpMailerClass->Subject = $email_subject;
       
        // Vsebina maila
        $this->prepareEmailDesign($email_msg);


        // Loop cez prejemnike in posiljanje
        if (!empty($this->recipients)){

            // Loop cez vse prejemnike
            foreach ($this->recipients AS $recipient){
                $this->phpMailerClass->AddAddress($recipient);
            }    
            
            // Posljemo mail
            $success =  $this->phpMailerClass->send();


            // Logiramo posiljanje
			$SL = new SurveyLog();

			// Napaka
			if (!$success) {
				
				if((int)$this->ank_id > 0)
					$SL->addMessage(SurveyLog::MAILER, "NAPAKA pri pošiljanju pošiljanje testnega emaila na naslov ".implode(",", $this->recipients)."! ".$this->phpMailerClass->ErrorInfo);
				
				//if($this->phpMailerDebug > 0)
					echo "<br />Mailer Error: " . $this->phpMailerClass->ErrorInfo.'<br /><br />';
			} 
			// Uspesno posiljanje
			else {

				$SL->addMessage(SurveyLog::MAILER, "USPEŠNO pošiljanje testnega emaila na naslov ".implode(",", $this->recipients));
							
				if($this->phpMailerDebug > 0)
					echo "Message sent!<br /><br />";
            }
            
            $SL->write();


            return $success;
        }
    }

    // Pripravimo design emaila
    private function prepareEmailDesign($content, $heading='', $image='', $button=''){
        global $lang, $app_settings, $site_domain;

        // V nekaterih primerih ne designeramo maila
        if(!in_array($this->type, array('account', 'payments')) || !in_array($site_domain, array('localhost', 'www.1ka.si', 'test.1ka.si', 'test2.1ka.si'))){
            $this->phpMailerClass->msgHTML($content);
            return;
        }

        // Najprej pocistimo signature
        $signature = Common::getEmailSignature();
        $content = str_replace($signature, "", $content);

        // Logo
        $logo_src = ($lang['id'] == '1') ? 'https://www.1ka.si/public/img/logo/1ka_slo.png' : 'https://www.1ka.si/public/img/logo/1ka_eng.png';

        // Naslov
        //$heading = 'Naslovček';
        $heading_html = ($heading != '') ? '<tr><td style="color: #153643; font-family: Montserrat,sans-serif;"><h1 style="font-size: 24px; margin: 0;">'.$heading.'</h1></td></tr>' : '';

        // Vsebina
        $text = $content;
        $text_html = ($text != '') ? '<tr><td style="color: #153643; font-family: Montserrat,sans-serif; font-size: 16px; line-height: 24px; padding: 20px 0 30px 0;"><p style="margin: 0;">'.$text.'</p></td></tr>' : '';

        // Slika
        //$image = '<img src="https://www.go-tel.si/upload/relevantna-slika.png" style="display: block;" />';
        $image_html = ($image != '') ? '<tr><td align="center"><img src="'.$image.'" style="display: block;" /></td></tr>' : '';
        
        // Gumb
        //$button = array('url'=>'https://1ka.si', 'text'=>'Gumbek');
        $button_html = ($button != '') ? '<tr><td align="center"><a href="'.$button['url'].'" style="text-decoration:none"><p style="font-family: Montserrat,sans-serif; font-size: 18px; padding: 20px 0 20px 0;text-align:center;background-color:#1e88e5;color:white;">'.$button['text'].'</p></a></td></tr>' : '';
        
        
        $designed_content = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" lang="en-GB">
            
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title>1ka sporočilo</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                    
                    <style type="text/css">
                        a[x-apple-data-detectors] {color: inherit !important;}

                        a{
                            color:#1e88e5;
                            text-decoration:none;
                            transition:0.2s;
                        }
                        a:hover{
                            color: #4ca0ea;
                        }

                        @media only screen and (max-width: 600px){
                            table.white_holder{
                                border-collapse:collapse;
                                width:100%;
                            }
                            .content img{
                                width:100%;
                            }
                        }
                    </style> 
                </head>

                <body style="margin: 0; padding: 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#1e88e5;" bgcolor="#1e88e5"><tr><td>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        <td style="padding: 30px 15px 30px 15px;">
                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="570" class="white_holder">
                            <tr>
                                <td align="center" bgcolor="white" style="padding: 30px 0 30px 0;">
                                <img src="'.$logo_src.'" alt="header" width="100" style="display: block;" />
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor="#ffffff" style="padding: 20px 30px 40px 30px;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="content">
                                    
                                    <!-- NASLOV -->
                                    '.$heading_html.'

                                    <!-- SLIKA -->
                                    '.$image_html.'

                                    <!-- VSEBINA -->
                                    '.$text_html.'
                                    
                                    <!-- GUMB -->
                                    '.$button_html.'
                                    
                                    <!-- PODPIS -->
                                    <tr>
                                        <td style="color: #153643; font-family: Montserrat,sans-serif; font-size: 16px; line-height: 24px; padding: 20px 0 0 0;">
                                            <p style="margin: 0;">'.$lang['srv_1ka_mail_signature_bye'].'</p>
                                        </td>
                                    </tr>
                                    
                                </table>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor="#f7f7f7" style="padding: 30px 30px;color:#ababab;">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                                    <tr>
                                    <td style="color: #828282; font-family: Montserrat,sans-serif; font-size: 13px; line-height: 24px;">
                                        <p style="margin: 0 0 25px 0;">'.$lang['email_template_footer'].'</p>
                                    </td>
                                    </tr>
                                    <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                        <tr>
                                            <td style="width:40px;" width="40">
                                            <a href="https://www.facebook.com/1KAenklikanketa/" target="_blank">
                                                <img src="https://www.1ka.si/public/img/social/fb_blue.png" alt="Facebook" height="35" style="display: block;" border="0" />
                                            </a>
                                            </td>
                                            <td style="background-color:#f7f7f7;width:20px;" width="20">&nbsp;</td>
                                            <td style="width:40px;" width="40">
                                            <a href="https://www.youtube.com/channel/UCWhsQe9qIjGpbD0-TCdPg7Q" target="_blank">
                                                <img src="https://www.1ka.si/public/img/social/yt.png" alt="Youtube" height="35" style="display: block;" border="0" />
                                            </a>
                                            </td>
                                            <td style="background-color:#f7f7f7;width:20px;" width="20" >&nbsp;</td>
                                            <td style="width:40px;" width="40">
                                            <a href="https://twitter.com/enklikanketa" target="_blank">
                                                <img src="https://www.1ka.si/public/img/social/twitter_blue.png" alt="Twitter" height="35" style="display: block;" border="0" />
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                    </tr>
                                </table>
                                </td>
                            </tr>
                            </table> <!-- &reg; 1KA -->
                            <p style="color: #efefef; font-family: Montserrat,sans-serif; font-size: 12px; line-height: 24px; padding: 5px 0 30px 0;text-align:center">
                                '.$lang['email_template_footer2_recipient'].' <a href="mailto:'.$this->recipients[0].'" style="color: #efefef;">'.$this->recipients[0].'</a>.
                                <br>
                                '.$lang['email_template_footer2_unsubscribe'].'
                            </p>
                        </td>
                        </tr>
                    </table>
                </td></tr></table>
                </body>

            </html>
        ';

        
        /*echo $designed_content;
        die();*/

        $this->phpMailerClass->msgHTML($designed_content);
    }

    

    // Preveri ce je mail veljaven
	private function validEmail($email = null){
		return Common::getInstance()->validEmail($email);
	}

    // Enkripcija gesla za mail streznik
	private function encryptPassword($password){
        
        // Kateri php modul uporabljamo (mcrypt ali openssl) - kasneje se bo vse preneslo na openssl
        $php_encrypt_module = 'openssl';

        // Star modul mcrypt, ki ni vec kompatibilen s php7.3
        if($php_encrypt_module == 'mcrypt'){
            
            $iv_size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_ECB);
            $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
            $encryptedPassword = mcrypt_encrypt(MCRYPT_CAST_256, $this->password_hash_key , $password, MCRYPT_MODE_ECB, $iv);
            
            return $encryptedPassword;
        }
        // Prehod iz mcrypt na openssl - NI KOMPATIBILNO ZA NAZAJ! - DODATNO SE BASE_ENCODE
        else{
            
            
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encryptedPassword = openssl_encrypt($password, 'AES-256-CBC', $this->password_hash_key, 0, $iv);
            
            return base64_encode($encryptedPassword . '::' . $iv);

            // Prehod iz mcrypt na openssl - NI KOMPATIBILNO ZA NAZAJ! - star openssl za gorenje
            /*$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encryptedPassword = openssl_encrypt($password, 'AES-256-CBC', $this->password_hash_key, 0, $iv);
            
            return $encryptedPassword . '::' . $iv;*/
        }
	}

    // Dekripcija gesla za mail streznik
	private function decryptPassword($encryptedPassword){
        
        // Kateri php modul uporabljamo (mcrypt ali openssl) - kasneje se bo vse preneslo na openssl
        $php_encrypt_module = 'openssl';

        // Star modul mcrypt, ki ni vec kompatibilen s php7.3
        if($php_encrypt_module == 'mcrypt'){
            
            $iv_size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_ECB);
            $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
            $decryptedPassword = mcrypt_decrypt(MCRYPT_CAST_256, $this->password_hash_key , $encryptedPassword, MCRYPT_MODE_ECB, $iv);
            
            return $decryptedPassword;
        }
        // Prehod iz mcrypt na openssl - NI KOMPATIBILNO ZA NAZAJ! - DODATNO SE BASE_ENCODE
        else{

            // Prehod iz mcrypt na openssl - NI KOMPATIBILNO ZA NAZAJ! - DODATNO SE BASE_DECODE
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            list($encrypted_data, $iv) = explode('::', base64_decode($encryptedPassword), 2);
            $decryptedPassword = openssl_decrypt($encrypted_data, 'AES-256-CBC', $this->password_hash_key, 0, $iv);

            return $decryptedPassword;

            // Prehod iz mcrypt na openssl - NI KOMPATIBILNO ZA NAZAJ! - star openssl za gorenje
            /*$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            list($encrypted_data, $iv) = explode('::', $encryptedPassword, 2);
            $decryptedPassword = openssl_decrypt($encrypted_data, 'AES-256-CBC', $this->password_hash_key, 0, $iv);

            return $decryptedPassword;*/
        }
    }
    
}