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

			// Preveri ce moramo po registraciji vrec na kak URL
			$rt = sisplet_query ("SELECT value FROM misc WHERE what='AfterReg'");
			$rxx = mysqli_fetch_row ($rt);

			if (strlen ($rxx[0]) > 3){
				$rxx[0] = str_replace ($originating_domain, $keep_domain, $rxx[0]);
				header ('location: ' .$rxx[0] .'?&l=1');
			}
			else 
				header ('location: /index.php');
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

		// Preveri ce moramo po registraciji vrec na kak URL
		$rt = sisplet_query ("SELECT value FROM misc WHERE what='AfterReg'");
		$rxx = mysqli_fetch_row ($rt);

		if (strlen ($rxx[0]) > 3){
			$rxx[0] = str_replace ($originating_domain, $keep_domain, $rxx[0]);
			header ('location: ' .$rxx[0] .'?&l=1');
		}
		else 
			header ('location: /index.php');
	}
	
	function GoogleLogin () {
		
		require_once ('../function/JWT.php');
		
		global $google_login_client_id;
		global $google_login_client_secret;
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
						'client_id' => $google_login_client_id,
						'client_secret' => $google_login_client_secret,
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
						'client_id' => $google_login_client_id,
						'client_secret' => $google_login_client_secret,
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

				if ($r[2] == "2" || $r[2] == "6") 
				{
					setcookie ("P", time(), time()+$LifeTime, '/', $cookie_domain);
				}

				$this->ZePrijavljen = true;

                                if (isset ($_POST['l']) && $_POST['l']!='')
				{
					header ('location: ' .$site_url .str_replace (base64_decode($_POST['l']), $site_url, ""));
				}

		
				// Preveri ce moramo po registraciji vrec na kak URL
				$rt = sisplet_query ("SELECT value FROM misc WHERE what='AfterReg'");
				$rxx = mysqli_fetch_row ($rt);

				if (strlen ($rxx[0]) > 3)
				{
					$rxx[0] = str_replace ($originating_domain, $keep_domain, $rxx[0]);
					header ('location: ' .$rxx[0] .'?&l=1');
				} else {
					$CheckCasovnice = sisplet_query ("SELECT * FROM misc WHERE what='TimeTables' AND value='1'");
					if (mysqli_num_rows ($CheckCasovnice) != 0)
					{
						if (!isset ($_GET['l']))	header('Location: ' .$site_url .'index.php?fl=13');
						else				header('Location: ' .base64_decode($_GET['l']) .'&l=1');
					}
					else
					{
						if (!isset ($_GET['l']))	header('Location: ' .$site_url .'?l=1');
						else				header('Location: ' .base64_decode($_GET['l']) .'?&l=1');
					}
				}
			}
			else
			{
				// Password prompt
				header ('location: ' .$site_url .'index.php?fl=8&lact=20&em=' .$this->email .(isset ($_GET['l'])?'&l=' .$_GET['l']:''));
				die();
			}
		}
		else
		{
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


	function FBLogin() {
		global $facebook_appid;
		global $facebook_appsecret;
		global $cookie_path;

        if ($r = file_get_contents ("https://graph.facebook.com/v2.9/oauth/access_token?client_id=" .$facebook_appid ."&redirect_uri=https://www.1ka.si/fb_login.php&client_secret=" .$facebook_appsecret ."&code=" .$_GET['code'])) {
            $at = json_decode ($r);

            $user = json_decode(file_get_contents('https://graph.facebook.com/me?fields=email,first_name,last_name&access_token=' .$at->{'access_token'}));

            if (!isset ($user->email) && isset ($user->name)) {
                    $user->email = str_replace(" ", ".", $user->first_name ."." .$user->last_name) ."@facebook.com";
            }

            $old_email = str_replace(" ", ".", $user->first_name ."." .$user->last_name) ."@facebook.com";
            $old_email = str_replace (array(" ","č","ć","Č","Ć","ž","Ž","š","Š","đ","Đ"), array(".","c","c","C","C","z","Z","s","S","d","D"), $old_email);

            // preveri email, ce ga imas v bazi:
            if (isset ($user->email) && $user->email!='') {
                $result = sisplet_query ("select u.name, u.surname, f.id, u.id, u.pass FROM users u, fb_users f WHERE u.id=f.uid AND u.email='" .str_replace ("'", '', $user->email) ."'");
                if (mysqli_num_rows ($result)==0) {
                    $result2 = sisplet_query ("select u.id FROM users u LEFT JOIN fb_users f on (u.id=f.uid) where u.email='" .str_replace ("'", '', $old_email) ."'");
                    if (mysqli_num_rows ($result2)>0) {
                        $r2 = mysqli_fetch_row ($result2);

                        $result3 = sisplet_query ("SELECT id FROM users WHERE email='" .$user->email ."'");
                        if (mysqli_num_rows ($result3) > 0) {
                            $real_id = mysqli_fetch_row ($result3);

                            // moramo popravljati IDje in jebat ježa
                            // iz "pravega" skopiram geslo na "fb", "fb" popravim v pravega in pravega dizejblam. In iz pravega vse srv_dpstop popravim na "fb"
                            sisplet_query ("UPDATE users a, users b SET a.pass=b.pass WHERE a.email='" .str_replace ("'", '', $old_email) ."' AND b.email='" .str_replace ("'", '', $user->email) ."'");
                            sisplet_query ("UPDATE users SET email=CONCAT('D3LMD-' , email) WHERE email='" .str_replace ("'", '', $user->email) ."'");

                            if ($real_id[0] > 0 && $r2[0] > 0) {
                                sisplet_query ("UPDATE srv_dostop SET uid=" .$r2[0] ." WHERE uid=" .$real_id[0]);
                            }
                        }
                        sisplet_query ("UPDATE users SET email='" .str_replace ("'", '', $user->email) ."' WHERE id='" .$r2[0] ."'");

                    }

                }
                $result = sisplet_query ("select u.name, u.surname, IF(ISNULL(f.id),'0',f.id), u.id, u.pass FROM users u LEFT JOIN fb_users f on (u.id=f.uid) where u.email='" .str_replace ("'", '', $user->email) ."'");


				// je noter, preveri ce je v FB (podatki, podatki!)
				if (mysqli_num_rows ($result)>0) {
					
					$r = mysqli_fetch_row ($result);
					
					if ($r[2]!='0') {
						// samo prijavi
						$this->EncPass = $r[4];
						$this->email = str_replace (" ", ".", $user->email);

						$this->Login();
					} 
					else {
						// dodaj FB podatke in prijavi
						if (isset ($user->first_name)) $fn = $user->first_name;
						else $fn = $r[0];

						if (isset ($user->last_name)) $ln = $user->last_name;
						else $ln = $r[1];

						if (isset ($user->gender)) $gn = $user->gender;
						else $gn = '';
						
						if (isset ($user->profile_link)) $pl = $user->profile_link;
						else $pl = '';
						
						if (isset ($user->timezone)) $tz =  $user->timezone;
						else $tz = '';
						
						sisplet_query ("INSERT INTO fb_users (uid, first_name, last_name, gender, timezone, profile_link) VALUES ('" .$r[3] ."', '" .$fn ."', '" .$ln ."', '" .$gn ."', '" .$tz ."', '" .$pl ."')");

						// Prijaviga :)
						$this->EncPass = $r[4];
						$this->email = $user->email;

						$this->Login();

					}
				} 
				else {
					// registriraj, dodaj FB podatke in prijavi
					// dodaj FB podatke in prijavi
					if (isset ($user->first_name)) $fn = $user->first_name;
					else $fn = str_replace (" ", ".", $r[0]);

					if (isset ($user->last_name)) $ln = $user->last_name;
					else $ln = $r[1];

					if (isset ($user->gender)) $gn = $user->gender;
					else $gn = '';

					if (isset ($user->profile_link)) $pl = $user->profile_link;
					else $pl = '';

					if (isset ($user->timezone)) $tz =  $user->timezone;
					else $tz = '';

					// geslo med 00000 in zzzzz
					$this->pass = base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36); 
					$this->EncPass = base64_encode((hash('SHA256', $this->pass .$pass_salt)));
					$this->email = str_replace (array(" ","č","ć","Č","Ć","ž","Ž","š","Š","đ","Đ"), array(".","c","c","C","C","z","Z","s","S","d","D"), $user->email);

					//sisplet_query ("INSERT INTO users (name, surname, email, pass, when_reg) VALUES ('" .iconv('utf-8', 'iso-8859-2//TRANSLIT', $fn) ."', '" .iconv('utf-8', 'iso-8859-2//TRANSLIT',$ln) ."', '" .iconv('utf-8', 'iso-8859-2//TRANSLIT',$this->email) ."', '" .$this->EncPass ."', NOW())");
					sisplet_query ("INSERT INTO users (name, surname, email, pass, when_reg) VALUES ('" . $fn ."', '" . $ln ."', '" .iconv('utf-8', 'iso-8859-2//TRANSLIT',$this->email) ."', '" .$this->EncPass ."', NOW())");
					$uid = mysqli_insert_id($GLOBALS['connect_db']);

					//sisplet_query ("INSERT INTO fb_users (uid, first_name, last_name, gender, timezone, profile_link) VALUES ('" .$uid ."', '" .iconv('utf-8', 'iso-8859-2//TRANSLIT',$fn) ."', '" .iconv('utf-8', 'iso-8859-2//TRANSLIT',$ln) ."', '" .$gn ."', '" .$tz ."', '" .$pl ."')");
					sisplet_query ("INSERT INTO fb_users (uid, first_name, last_name, gender, timezone, profile_link) VALUES ('" .$uid ."', '" . $fn ."', '" . $ln ."', '" .$gn ."', '" .$tz ."', '" .$pl ."')");

					// prijavi
					$this->Login();
				}
			}
		}
	}
}


// popravek, FB sprememba...
function get_facebook_cookie($app_id, $app_secret) {
	if ($_COOKIE['fbsr_' . $app_id] != '') {
		return get_new_facebook_cookie($app_id, $app_secret);
	} else {
		return get_old_facebook_cookie($app_id, $app_secret);
	}
}

function get_old_facebook_cookie($app_id, $app_secret) {
	$args = array();
	parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
	ksort($args);
	$payload = '';
	foreach ($args as $key => $value) {
		if ($key != 'sig') {
			$payload .= $key . '=' . $value;
		}
	}
	if (md5($payload . $app_secret) != $args['sig']) {
		return array();
	}
	return $args;   
}

function get_new_facebook_cookie($app_id, $app_secret) {
	$signed_request = parse_signed_request($_COOKIE['fbsr_' . $app_id], $app_secret);
	// $signed_request should now have most of the old elements
    
	$signed_request[uid] = $signed_request[user_id]; // for compatibility 

	if (!is_null($signed_request)) {
		// the cookie is valid/signed correctly
		// lets change "code" into an "access_token"
		$access_token_response = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=$app_id&redirect_uri=&client_secret=$app_secret&code=$signed_request[code]");
		parse_str($access_token_response);
		$signed_request[access_token] = $access_token;
		$signed_request[expires] = time() + $expires;
	}
	return $signed_request;
}


function parse_signed_request($signed_request, $secret) {
	list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

	// decode the data
	$sig = base64_url_decode($encoded_sig);
	$data = json_decode(base64_url_decode($payload), true);

	if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
		error_log('Unknown algorithm. Expected HMAC-SHA256');
		return null;
	}

	// check sig
	$expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
	if ($sig !== $expected_sig) {
		error_log('Bad Signed JSON signature!');
		return null;
	}

	return $data;
}

function base64_url_decode($input) {
	return base64_decode(strtr($input, '-_', '+/'));
}

