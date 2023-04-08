<?php

class Profile {

    var $ZePrijavljen;
    var $ime;
    var $priimek;
    var $email;
    var $geslo;
    var $EncPass;

    var $LoggingIn;

	function __construct() {
		global $admin_type;
		global $lang;
		global $site_path;

		global $mysql_username;
		global $mysql_password;
		global $mysql_server;
		global $mysql_database_name;

		global $cookie_domain;

		// AAI prijava na vrh.
		if ($admin_type==-1 && isset ($_SERVER['Shib-Session-Index']) && isset ($_SERVER['eduPersonPrincipalName']) && isset ($_SERVER['mail']) && isset ($_SERVER['givenName']) && isset ($_SERVER['sn']) && $_SERVER['mail']!='') {
			// se prijavljam preko eduroam!
			$this->eduroamLogin();
		}

		if ($admin_type > -1) {

			$this->ZePrijavljen = true;
			$this->LoggingIn = false;

			$sql = sisplet_query ("SELECT name, surname, lang FROM users WHERE email='" .base64_decode ($_COOKIE['uid']) ."'");
			if ($r = mysqli_fetch_row ($sql)) {
				$this->ime =	$r[0];
				$this->priimek = $r[1];

				if (is_numeric ($r[2]) && $r[2] != "0" && $r[2]!=$lang['id']) {
					unset ($lang);
					include ($site_path .'lang/' .$r[2] .'.php');
				}

				$this->ime = CleanXSS ($this->ime);
				$this->priimek = CleanXSS ($this->priimek);
			} else {
				mysqli_select_db($GLOBALS['connect_db'],"meta");

				$sql = sisplet_query ("SELECT ime, priimek FROM administratorji WHERE email='" .base64_decode ($_COOKIE['uid']) ."'");
				$r = mysqli_fetch_row ($sql);
				$this->ime = $r[0];
				$this->priimek = $r[0];

				mysqli_select_db($GLOBALS['connect_db'],$mysql_database_name);
			}
		}

		else {
			if (isset ($_POST['mail']))	$this->email = strtolower ($_POST['mail']);
			if (isset ($_GET['mail']))	$this->email = strtolower ($_GET['mail']);
			if (isset ($_POST['pass']))	$this->pass = $_POST['pass'];

			$this->email = CleanXSS ($this->email);
			$this->pass = CleanXSS ($this->pass);

			$this->LoggingIn = true;
		}
	}

	
	function eduroamAnotherServerLogin() {
		global $pass_salt;
		global $cookie_domain;
		global $originating_domain;
		global $keep_domain;  
		
		// Popravimo string iz geta, ker ima nekje + namesto space
		$repaired_string = str_replace(' ', '+', $_GET['s']);
		
		// malo manj varno, ampak bo OK.
		$klobasa = base64_decode($repaired_string);
		
		
		// Dobimo array parametrov iz get-a
		$data = explode ("|", $klobasa);
		
		// Pridobimo maile - mozno da jih je vec, potem vzamemo prvega
		$mails = explode(";", $data[0]);
		sort($mails);
		$mail = $mails[0];
		
		$ime = $data[1];
		$priimek = $data[2];
		
		$njegova = $data[3];
		$moja = $data[4];		


		// Preverimo ce ima veljaven token (najprej pobrisemo stare)
		sisplet_query ("DELETE FROM aai_prenosi WHERE timestamp < (UNIX_TIMESTAMP() - 600);");
		$res = sisplet_query ("SELECT * FROM aai_prenosi WHERE moja='" .$moja ."' AND njegova='" .$njegova ."'");   
		
		if (mysqli_num_rows ($res) > 0) {
		
			$pass = base64_encode((hash('SHA256', "e5zhbWRTEGW&u375ejsznrtztjhdtz%WZ&" .$pass_salt)));

			// Preverimo ce obstaja user v bazi
			$result = sisplet_query ("SELECT pass, id FROM users WHERE email='" .$mail ."'");
			if (mysqli_num_rows ($result) == 0) {

				// dodaj ga v bazo
				$pass = base64_encode(hash('SHA256', "e5zhbWRTEGW&u375ejsznrtztjhdtz%WZ&" .$pass_salt));
                sisplet_query ("INSERT INTO users  (email, name, surname, type, pass, eduroam, when_reg) VALUES ('$mail', '$ime', '$priimek', '3', '" .$pass ."', '1', NOW())");
                
                // Pridobimo id dodanega userja
                $user_id = mysqli_insert_id($GLOBALS['connect_db']);
			}            
			else {
				// potegni geslo in mu daj kuki
				$r = mysqli_fetch_row ($result);
                
                $pass = $r[0];
                $user_id = $r[1];
			}

			$result = sisplet_query ("SELECT value FROM misc WHERE what='CookieLife'");
			$row = mysqli_fetch_row ($result);
			$LifeTime = $row[0];

			// Zlogiramo login
			sisplet_query ("UPDATE users SET last_login=NOW() WHERE id='".$user_id."'");
			
			// določi še, od kje se je prijavil
			$hostname="";
			$headers = apache_request_headers();
			if (array_key_exists('X-Forwarded-For', $headers)){
				$hostname=$headers['X-Forwarded-For'];
			} else {
				$hostname=$_SERVER["REMOTE_ADDR"];
			}

			sisplet_query ("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('".$user_id."', '" .$hostname ."', NOW())");

			setcookie ("uid", base64_encode($mail), time()+$LifeTime, '/', $cookie_domain);
			setcookie ("secret", $pass, time()+$LifeTime, '/', $cookie_domain);
			// moram vedeti, da je AAI!
			setcookie ("aai", '1', '/', $cookie_domain);

			$this->ZePrijavljen = true;

            // Moramo po registraciji vrec na kak URL
            $rxx = str_replace ($originating_domain, $keep_domain, '/admin/survey/');
            header ('location: '.$rxx.'?&l=1');
		}
		else 
			header ('location: /index.php'); 
	}
	
	function eduroamLogin() {
		global $pass_salt;
		global $cookie_domain;
		global $originating_domain;
		global $keep_domain;
		
		$mail = $_SERVER['mail'];
		$ime = $_SERVER['givenName'];
		$priimek = $_SERVER['sn'];
		$pass = base64_encode((hash('SHA256', "e5zhbWRTEGW&u375ejsznrtztjhdtz%WZ&" .$pass_salt)));

		$result = sisplet_query ("SELECT pass, id FROM users WHERE email='" .$mail ."'");
		if (mysqli_num_rows ($result) == 0) {
			// dodaj ga v bazo
			$pass = base64_encode((hash('SHA256', "e5zhbWRTEGW&u375ejsznrtztjhdtz%WZ&" .$pass_salt)));
            sisplet_query ("INSERT INTO users (email, name, surname, type, pass, eduroam) VALUES ('$mail', '$ime', '$priimek', '3', '" .$pass ."', '1')");
            
            // Pridobimo id dodanega userja
            $user_id = mysqli_insert_id($GLOBALS['connect_db']);
		}            
		else {
			// potegni geslo in mu daj kuki
            $r = mysqli_fetch_row ($result);
            
			$pass = $r[0];
			$user_id = $r[1];
		}

		$result = sisplet_query ("SELECT value FROM misc WHERE what='CookieLife'");
		$row = mysqli_fetch_row ($result);
		$LifeTime = $row[0];
		
		sisplet_query ("UPDATE users SET last_login=NOW() WHERE id='" .$user_id ."'");
		// določi še, od kje se je prijavil
		
		$hostname="";
		$headers = apache_request_headers();
		if (array_key_exists('X-Forwarded-For', $headers)){
			$hostname=$headers['X-Forwarded-For'];
		} else {
			$hostname=$_SERVER["REMOTE_ADDR"];
		}
		
		sisplet_query ("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('" .$user_id ."', '" .$hostname ."', NOW())");

		setcookie ("uid", base64_encode($mail), time()+$LifeTime, '/', $cookie_domain);
		setcookie ("secret", $pass, time()+$LifeTime, '/', $cookie_domain);
        setcookie("unam", base64_encode($ime.' '.$priimek),time() + $LifeTime, '/', $cookie_domain);

        // moram vedeti, da je AAI!
        setcookie("aai", '1', time() + $LifeTime, '/', $cookie_domain);

        // Piškotek za cca. 10 let, da mu naslednjić ponudimo prijavno
        setcookie('external-login', '1', time()+280000000, '/', $cookie_domain);

		$this->ZePrijavljen = true;

        // Moramo po registraciji vrec na kak URL
        $rxx = str_replace ($originating_domain, $keep_domain, '/admin/survey/');
        header ('location: '.$rxx.'?&l=1');
	}
	
	function GoogleLogin () {
		
		require_once ('../function/JWT.php');

		global $site_url;
		global $lang;
		global $proxy;
		
		$oauth2_code = $_GET['code'];
		$discovery = json_decode(file_get_contents('https://accounts.google.com/.well-known/openid-configuration'));
		
		if ($proxy != "") {
			$ctx = stream_context_create(array(
				'http' => array(
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => 'POST',
					'content' => http_build_query(array(
						'client_id' => AppSettings::getInstance()->getSetting('google-login_client_id'),
						'client_secret' => AppSettings::getInstance()->getSetting('google-login_client_secret'),
						'code' => $oauth2_code,
						'grant_type' => 'authorization_code',
						'redirect_uri' => $site_url .'utils/google-oauth2.php',
						'openid.realm' => $site_url,
					)),
					'proxy' => 'tcp://' .$proxy,
				),
			));
			
		}
		else {
			$ctx = stream_context_create(array(
				'http' => array(
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => 'POST',
					'content' => http_build_query(array(
						'client_id' => AppSettings::getInstance()->getSetting('google-login_client_id'),
						'client_secret' => AppSettings::getInstance()->getSetting('google-login_client_secret'),
						'code' => $oauth2_code,
						'grant_type' => 'authorization_code',
						'redirect_uri' => $site_url .'utils/google-oauth2.php',
						'openid.realm' => $site_url,
					)),
				),
			));
			
		}
		
		$resp = file_get_contents($discovery->token_endpoint, false, $ctx);
		if (!$resp) {
			// $http_response_header here got magically populated by file_get_contents(), surprise
			echo '<h1>' .$lang['oid_auth_rejected'] .'</h1>';
			echo '<p>' .$lang['google_auth_rejected'] .'</p>';

			echo '<ul><li>' .$lang['oid_maybe_you_rejected'] .'<a href="' .$site_url .'index.php?fl=8&lact=40">' .$lang['try_again'] .'</a></li><li>' .$lang['oid_maybe_local1'] .'<a href="' .$site_url .'index.php?fl=8&lact=20">' .$lang['oid_maybe_local2'] .'</a></li></ul>';
		}
		$resp = json_decode($resp);
		$access_token = $resp->access_token;
		$id_token = $resp->id_token;

		// Skip JWT verification: we got it directly from Google via https, nothing could go wrong.
		$id_payload = JWT::decode($resp->id_token, null, false);
		if (!$id_payload->sub) {
			echo '<h1>' .$lang['oid_auth_rejected'] .'</h1>';
			echo '<p>' .$lang['google_auth_rejected'] .'</p>';

			echo '<ul><li>' .$lang['oid_maybe_you_rejected'] .'<a href="' .$site_url .'index.php?fl=8&lact=40">' .$lang['try_again'] .'</a></li><li>' .$lang['oid_maybe_local1'] .'<a href="' .$site_url .'index.php?fl=8&lact=20">' .$lang['oid_maybe_local2'] .'</a></li></ul>';
		}

		$user_id = 'google+' . $id_payload->sub;
		$user_email = $id_payload->email;

		if ($user_email != '' && $user_id != '') {
			$this->email = $user_email;
			
			$res = sisplet_query ("SELECT pass FROM users WHERE email='" .$user_email ."'");
			
			// Je noter, ga samo prijavim...
			if (mysqli_num_rows ($res) > 0) {
				$r = mysqli_fetch_row ($res);

				$this->EncPass = $r[0];
					
				$this->Login();
			}
			// Ni se registriran, ga je potrebno dodati na prijavno formo
			else {
				// geslo med 00000 in zzzzz
				$this->pass = base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36); 
				$this->EncPass = base64_encode((hash('SHA256', $this->pass .$pass_salt)));
				$this->email = $user_email;
							$fn = explode ("@", $user_email);
							
				sisplet_query ("INSERT INTO users 
								(name, surname, email, pass, lang, when_reg) 
								VALUES 
								('".$fn[0]."', '', '".$user_email."', '".$this->EncPass."', '".(isset ($_GET['regFromEnglish']) && $_GET['regFromEnglish']=="1"?'2':'1')."', NOW())");
				$uid = mysqli_insert_id($GLOBALS['connect_db']);

				sisplet_query ("INSERT INTO oid_users (uid) VALUES ('$uid')");

				// prijavi
				$this->Login();
			}
		}
	}
	
        
	function Login () {
		global $mysql_database_name;
		global $site_url;
		global $lang;
		global $pass_salt;

		global $cookie_domain;

		global $originating_domain;
		global $keep_domain;


		// if ($this->LoggingIn == true) {
		$mini = $this->email .$this->pass;

		for ($Stevec=0; $Stevec<strlen ($mini); $Stevec++)
		{
			$mini = str_replace ("'", "", $mini);
		}

		$result = sisplet_query ("SELECT value FROM misc WHERE what='CookieLife'");
		$row = mysqli_fetch_row ($result);
		$LifeTime = $row[0];

        if ((isset($_POST['remember']) && $_POST['remember'] == "1") || (isset($_COOKIE['remember-me']) && $_COOKIE['remember-me'] == 1)) {
            $LifeTime = 3600 * 24 * 365;
        } else {
            $LifeTime = $LifeTime;
        }

		$sql = sisplet_query("SELECT type, pass, status, id, name, surname, email FROM users WHERE email='" .$this->email ."'");
		if (mysqli_num_rows($sql) > 0)
		{
			$r = mysqli_fetch_row ($sql);

			// BAN
			if ($r[2] == 0)
			{
				header('Location: ' .$site_url .'index.php?fl=8&lact=12&ban=1&em=' .$this->email);
				die();
			}

			if (base64_encode((hash('SHA256', $this->pass .$pass_salt))) == $r[1] || $this->EncPass == $r[1])
			{

				sisplet_query ("UPDATE users SET last_login=NOW() WHERE id='" .$r[3] ."'");


                // določi še, od kje se je prijavil

                $hostname="";
                $headers = apache_request_headers();
                if (array_key_exists('X-Forwarded-For', $headers)){
                    $hostname=$headers['X-Forwarded-For'];
                } else {
                    $hostname=$_SERVER["REMOTE_ADDR"];
                }

                sisplet_query ("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('" .$r[3] ."', '" .$hostname ."', NOW())");

                                
				setcookie ("uid", base64_encode($this->email), time()+$LifeTime, '/', $cookie_domain);
                setcookie("unam", base64_encode($r[4].' '.$r[5]),time() + $LifeTime, '/', $cookie_domain);
				setcookie ("secret", $r[1], time()+$LifeTime, '/', $cookie_domain);

				if ($r[2] == "2" || $r[2] == "6"){
					setcookie ("P", time(), time()+$LifeTime, '/', $cookie_domain);
				}

				$this->ZePrijavljen = true;

                if (isset ($_POST['l']) && $_POST['l']!=''){
					header ('location: ' .$site_url .str_replace (base64_decode($_POST['l']), $site_url, ""));
				}

				// Moramo po registraciji vrec na kak URL
				$rxx = str_replace ($originating_domain, $keep_domain, '/admin/survey/');
				header ('location: '.$rxx.'?&l=1');
			}
			else{
				// Password prompt
				header ('location: ' .$site_url .'index.php?fl=8&lact=20&em=' .$this->email .(isset ($_GET['l'])?'&l=' .$_GET['l']:''));
				die();
			}
		}
		else{
			// Ni kul mail
			header ('location: ' .$site_url .'index.php?fl=8&lact=10&em=' .$this->email .(isset ($_GET['l'])?'&l=' .$_GET['l']:''));
			die();
		}
	}

	function Logout ($gohome='1') {
		global $site_url;
		global $cookie_domain;
		global $global_user_id;

		setcookie ('uid', '', time()-3600, '/', $cookie_domain);
		setcookie ('secret', '', time()-3600, '/', $cookie_domain);
		setcookie ('ME', '', time()-3600, '/', $cookie_domain);
		setcookie ('P', '', time()-3600, '/', $cookie_domain);
		setcookie ("AN", '', time()-3600, '/', $cookie_domain);
		setcookie ("AS", '', time()-3600, '/', $cookie_domain);
		setcookie ("AT", '', time()-3600, '/', $cookie_domain);
		
		setcookie("DP", $p, time()-3600*24*365, "/", $cookie_domain);
		setcookie("DC", $p, time()-3600*24*365, "/", $cookie_domain);
		setcookie("DI", $p, time()-3600*24*365, "/", $cookie_domain);
		setcookie("SO", $p, time()-3600*24*365, "/", $cookie_domain);
		setcookie("SPO", $p, time()-3600*24*365, "/", $cookie_domain);
		setcookie("SL", $p, time()-3600*24*365, "/", $cookie_domain);
		

		// pobrisi se naddomeno! (www.1ka.si naj pobrise se 1ka.si)
		if (substr_count ($cookie_domain, ".") > 1) {
			$nd = substr ($cookie_domain, strpos ($cookie_domain, ".")+1);

			setcookie ('uid', '', time()-3600, '/', $nd);
			setcookie ('secret', '', time()-3600, '/', $nd);
			setcookie ('ME', '', time()-3600, '/', $nd);
			setcookie ('P', '', time()-3600, '/', $nd);
			setcookie ("AN", '', time()-3600, '/', $nd);
			setcookie ("AS", '', time()-3600, '/', $nd);
			setcookie ("AT", '', time()-3600, '/', $nd);
		
			setcookie("DP", $p, time()-3600*24*365, "/", $nd);
			setcookie("DC", $p, time()-3600*24*365, "/", $nd);
			setcookie("DI", $p, time()-3600*24*365, "/", $nd);
			setcookie("SO", $p, time()-3600*24*365, "/", $nd);
			setcookie("SPO", $p, time()-3600*24*365, "/", $nd);
			setcookie("SL", $p, time()-3600*24*365, "/", $nd);
		}

        if (isset($_COOKIE['aai']) && !empty ($_COOKIE['aai']) && $_COOKIE['aai']=="1") {
            setcookie ("aai", '', time()-3600, '/', $cookie_domain);
            header ('location: https://aai.1ka.si/Shibboleth.sso/Logout?return=https://www.1ka.si');
            die();
        }                
		
		// Preusmerimo na domaco stran
		if ($gohome=='1') 	
			header('Location:' .$site_url);
    }

}

