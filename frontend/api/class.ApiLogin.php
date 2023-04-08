<?php

/**
 *
 *  Class ki vsebuje funkcije APIJA (prijava, registracija v 1ko)
 *
 */

class ApiLogin
{

    var $ime;

    var $priimek;

    var $email;

    var $pass;

    var $prijava = '';

    var $EncPass;

    var $page_urls = [];  // Url-ji za podstrani - to se bo verjetno nastavljalo v settings.php


    function __construct()
    {
        global $site_url;
        global $admin_type;
        global $site_frontend;
        global $site_path;
        global $site_domain;
        global $cookie_domain;


		// Overridi za virtualne domene na TUS strezniku
		if(isVirtual()){
			if (getenv('apache_site_path') != '') $site_url = getenv('apache_site_url');
			if (getenv('apache_site_path') != '') $site_path = getenv('apache_site_path');
			if (getenv('apache_site_domain') != '') $site_domain = getenv('apache_site_domain');
			if (getenv('apache_keep_domain') != '') $cookie_domain = getenv('apache_keep_domain');
		}


        // DRUPAL
        if ($site_frontend == 'drupal') {
            $url = $site_url.'d/';
            if (!empty($_POST['jezik'])) {
                $url = $site_url.'d/'.$_POST['jezik'].'/';
            }

            if (!empty($_GET['prijava']) && $_GET['prijava'] == 1) {
                $this->prijava = '_login';
            }

            // Url-ji za podstrani
            $urls_array = [
                // Preusmerimo če reCaptcha ni vključena in je robot izpolnil registracijo
              'page_robot_redirect' => $url,

              'page_main' => $url,
                // Osnovna stran
              'page_main_login' => $url.'prijava/',
                // Vrnemo na osnovne spletno stran za prijavo
              'page_login' => $url.'?a=login',
                // Stran namenjena logiranju
              'page_login_login' => $url.'prijava/?a=login',
                // Stran namenjena logiranju
              'page_login_noEmail' => $url.'?a=login_noEmail',
                // Stran na katero preusmerimo ce pri loginu vnese napacen oz. neobstojec email
              'page_login_noEmail_login' => $url.'prijava/index.php?a=login_noEmail',
                //Preusmei na stran, kje je obrazec za prijavo

                //Google 2FA
                'page_login_2fa' => $url.'?a=login_2fa',

                // Če je uporabnik bannan
              'page_user_ban' => $url.'?a=user_ban',
              'page_user_ban_login' => $url.'prijava/index.php?a=user_ban',

                // Registracija
              'page_register' => $url.'registracija/?',
                // 1. korak registracije - stran s formo za registracijo
              'page_register_confirm' => $url.'registracija/confirm/?',
                // 2. korak registracije - stran kamor je preusmerjen uporabnik po vnosu podatkov za registracijo (kjer pregleda vnešene podatke če so vsi ok)
              'page_register_emailSend' => $url.'registracija/send/?',
                // 3 .korak registracije - stran kamor je preusmerjen ko potrdi podatke - izpiše se mu obvestilo, da bo prejel potrditveni mail
              'page_register_activate' => $site_url.'admin/survey/',
                // 4. korak registracije - stran kamor ga preusmeri, ko klikne na url za potrditev registracije v mailu (opcijsko - lahko se ga preusmeri tudi na osnovno stran)

              'page_add_second_email' => $url.'?a=add_second_email',

              'page_reset_password' => $url.'obnovitev-gesla/?a=reset_password',
                // Sprememba gesla
              'page_reset_password_activate' => $url.'?a=reset_password_activate'
                // Potrditev spremembe gesla
            ];

        } else {
            // Simple
            $urls_array = [
              'page_main' => $site_url.'index.php',
                // Osnovna stran
              'page_login' => $site_url.'index.php?a=login',
                // Stran namenjena logiranju
              'page_login_noEmail' => $site_url.'index.php?a=login_noEmail',
                //Google 2FA
              'page_login_2fa' => $site_url.'index.php?a=login_2fa',
                // Stran na katero preusmerimo ce pri loginu vnese napacen oz. neobstojec email
              'page_user_ban' => $site_url.'index.php?a=user_ban',
              'page_register' => $site_url.'index.php?a=register',
                // 1. korak registracije - stran s formo za registracijo
              'page_register_confirm' => $site_url.'index.php?a=register_confirm',
                // 2. korak registracije - stran kamor je preusmerjen uporabnik po vnosu podatkov za registracijo (kjer pregleda vnešene podatke če so vsi ok)
              'page_register_emailSend' => $site_url.'index.php?a=register_email',
                // 3 .korak registracije - stran kamor je preusmerjen ko potrdi podatke - izpiše se mu obvestilo, da bo prejel potrditveni mail
              'page_register_activate' => $site_url.'index.php',
                // 4. korak registracije - stran kamor ga preusmeri, ko klikne na url za potrditev registracije v mailu (opcijsko - lahko se ga preusmeri tudi na osnovno stran)

              'page_add_second_email' => $site_url.'index.php?a=add_second_email',

              'page_unregister' => $site_url.'index.php?a=unregister',
                // Stran namenjena "odregistraciji uporabnika
              'page_unregister_confirm' => $site_url.'index.php?a=unregister_confirm',
                // Potrditev odregistracije
              'page_reset_password' => $site_url.'index.php?a=reset_password',
                // Sprememba gesla
              'page_reset_password_activate' => $site_url.'index.php?a=reset_password_activate'
                // Potrditev spremembe gesla
            ];
        }


        // Nastavimo url-je
        $this->setUrls($urls_array);

        // Preverimo ce smo logirani (in ustrezno nastavimo piskotke)
        $admin_type = $this->checkLogin();
    }

    // Nastavimo vse podstrani potrebne za delovanje (registracija, login, odregistracija, pozabljeno geslo...) -
    public function setUrls($urls_array)
    {

        $this->page_urls = $urls_array;
    }


    // Izvedemo akcijo

    public function checkLogin()
    {
        global $admin_type;    // tip admina: 0:admin, 1:manager, 2:clan, 3:user
        global $global_user_id;
        global $mysql_database_name;
        global $pass_salt;
        global $is_meta;
        global $cookie_domain;

        $is_meta = 0;
        $global_user_id = 0;
        $admin_type = 3;
        $cookie_pass = $_COOKIE['secret'];


        // UID je v resnici base64 od emaila, ker sicer odpove meta!!!
        // najprej testiram meto, potem sele userje.
        if (isset ($_COOKIE['uid']) && !empty($_COOKIE['g2fa'])) {
            $user_email = base64_decode($_COOKIE['uid']);

            $db_meta_exists = mysqli_select_db($GLOBALS['connect_db'], "meta");
            if ($db_meta_exists) {
                $result = sisplet_query("SELECT geslo, aid, 0 as type FROM administratorji WHERE email='$user_email'");
            }

            // NI META
            if (!$result || mysqli_num_rows($result) == 0) {
                mysqli_select_db($GLOBALS['connect_db'], $mysql_database_name);
                $meta = 0;

                $result = sisplet_query("SELECT pass, id, type FROM users WHERE email='$user_email'");
                if (!$result || mysqli_num_rows($result) == 0) {
                    // najprej poradiraij cookije!
                    setcookie('uid', "", time() - 3600, $cookie_domain);
                    setcookie('secret', "", time() - 3600, $cookie_domain);

                    if (substr_count($cookie_domain, ".") > 1) {
                        $nd = substr($cookie_domain,strpos($cookie_domain, ".") + 1);

                        setcookie('uid', "", time() - 3600, $nd);
                        setcookie('secret', "", time() - 3600, $nd);
                    }

                    return -1;
                } else {
                    $r = mysqli_fetch_row($result);

                    if ($cookie_pass != $r[0]) {
                        // najprej poradiraij cookije!
                        setcookie('uid', "", time() - 3600, $cookie_domain);
                        setcookie('secret', "", time() - 3600, $cookie_domain);

                        if (substr_count($cookie_domain, ".") > 1) {
                            $nd = substr($cookie_domain,
                              strpos($cookie_domain, ".") + 1);

                            setcookie('uid', "", time() - 3600, $nd);
                            setcookie('secret', "", time() - 3600, $nd);
                        }

                        return -1;
                    } else {
                        $admin_type = $r[2];
                        $global_user_id = $r[1];

                        return $r[2];
                    }
                }

            } // JE META
            else {
                $r = mysqli_fetch_row($result);

                if ($cookie_pass == base64_encode((hash('SHA256', base64_decode($r[0]).$pass_salt)))) {

                    $is_meta = 1;
                    $admin_type = "0";

                    mysqli_select_db($GLOBALS['connect_db'],
                      $mysql_database_name);

                    $result = sisplet_query("SELECT pass, id, type FROM users WHERE email='$user_email'");
                    if (mysqli_num_rows($result) > 0) {
                        $r = mysqli_fetch_row($result);
                        $global_user_id = $r[1];
                    }

                    return 0;
                } else {
                    mysqli_select_db($GLOBALS['connect_db'],
                      $mysql_database_name);
                    // Obstaja tudi primer ko je IN meta IN navaden- in se je pac prijavil kot navaden user


                    $result = sisplet_query("SELECT pass, id, type FROM users WHERE email='$user_email'");
                    if (!$result || mysqli_num_rows($result) == 0) {
                        return -1;
                    } else {
                        $r = mysqli_fetch_row($result);

                        if ($cookie_pass != $r[0]) {
                            // najprej poradiraij cookije!
                            setcookie('uid', "", time() - 3600, $cookie_domain);
                            setcookie('secret', "", time() - 3600,
                              $cookie_domain);

                            if (substr_count($cookie_domain, ".") > 1) {
                                $nd = substr($cookie_domain,
                                  strpos($cookie_domain, ".") + 1);

                                setcookie('uid', "", time() - 3600, $nd);
                                setcookie('secret', "", time() - 3600, $nd);
                            }

                            return -1;
                        } else {
                            $admin_type = $r[2];
                            $global_user_id = $r[1];

                            return $r[2];
                        }
                    }
                }
            }
        } // Ni prijavljen
        else {
            $admin_type = -1;

            return -1;
        }
    }


    // Preveri ce je user ze logiran v 1ko in nastavi globalne spremenljivke in cookie (kopirano iz function.php)

    public function executeAction($params, $data)
    {
        global $site_url;
        global $global_user_id;
        global $lang;
        global $cookie_domain;


        // Nastavimo prejete podatke
        if (isset($data['ime'])) {
            $this->ime = $data['ime'];
        }
        if (isset($data['priimek'])) {
            $this->priimek = $data['priimek'];
        }
        if (isset($data['email'])) {
            $this->email = trim($data['email']);
        }
        if (isset($data['pass'])) {
            $this->pass = $data['pass'];
        }

        if (!isset($params['action'])) {
            $response = 'Napaka! Manjkajo parametri!';
        } else {
            switch ($params['action']) {

                // Login userja
                case 'login':
                    $response = $this->userLogin();
                    break;

                // Login userja
                case 'login_2fa':
                    $response = $this->userLogin2fa();
                    break;

                // Login userja z google racunom
                case 'login_google':
                    if(!empty($_POST['remember']) && $_POST['remember'] == 1) {
                        setcookie('remember-me', '1', time() + 31536000, '/', $cookie_domain);
                    }

                    $response = $this->userLoginGoogle();
                    break;

                // Login userja s FB racunom
                case 'login_facebook':
                    if(!empty($_POST['remember']) && $_POST['remember'] == 1) {
                        setcookie('remember-me', '1', time() + 31536000, '/', $cookie_domain);
                    }

                    $response = $this->userLoginFacebook();
                    break;

                // Login userja z AAI racunom
                case 'login_AAI':
                    if(!empty($_POST['remember']) && $_POST['remember'] == 1) {
                        setcookie('remember-me', '1', time() + 31536000, '/', $cookie_domain);
                    }

                    $response = $this->userLoginAAI();
                    break;


                // Logout userja
                case 'logout':
                    $response = $this->userLogout();
                    break;


                // Registracija userja - prvi vnos podatkov s preverjanjem
                case 'register':
                    $response = $this->userRegister();
                    break;

                // Registracija userja - potrditev podatkov in posiljanje potrditvenega maila
                case 'register_confirm':
                    $response = $this->userRegisterConfirm();
                    break;

                // Registracija userja - potrditev registracije (aktivacija) po prejetju potrditvenega maila
                case 'register_activate':
                    $response = $this->userRegisterActivate();
                    break;

                // Dodajanje alternativnega emaila
                case 'activate_second_email':
                    $response = $this->userActivateAlternativEmail();
                    break;


                // Odregistracija userja - preverjanje ce se res zeli odjaviti
                //				case 'unregister':
                //					$response = $this->userUnregister();
                //				break;

                // Odregistracija userja - potrditev in dejanska odjava
                case 'unregister_confirm':
                    $response = $this->userUnregisterConfirm();
                    break;


                // Reset passworda userja
                case 'reset_password':
                    $response = $this->userResetPassword();
                    break;

                // Potrditev reseta passworda userja
                case 'reset_password_activate':
                    $response = $this->userResetPasswordActivate();
                    break;
            }
        }


        echo $response;
    }


    // Prijavi userja v 1ko - (kopirano iz ProfileClass.php)

    private function userLogin()
    {
        global $mysql_database_name;
        global $site_url;
        global $lang;
        global $pass_salt;
        global $cookie_domain;
        global $originating_domain;
        global $keep_domain;


        // Ce imamo vklopljeno blokiranje dostopa do admina glede na ip
        $admin_allow_only_ip = AppSettings::getInstance()->getSetting('app_limits-admin_allow_only_ip');
        if($admin_allow_only_ip !== false && !empty($admin_allow_only_ip)){

            $ip = $_SERVER['REMOTE_ADDR'];

            // Preverimo ip - ce se ne ujema ne pustimo logina
            if(!in_array($ip, $admin_allow_only_ip)){
                header('location: '.$this->page_urls['page_login'.$this->prijava]);
                die();
            }
        }
        
        $mini = $this->email.$this->pass;
        for ($Stevec = 0; $Stevec < strlen($mini); $Stevec++) {
            $mini = str_replace("'", "", $mini);
        }

        $result = sisplet_query("SELECT value FROM misc WHERE what='CookieLife'");
        $row = mysqli_fetch_row($result);
        $LifeTime = $row[0];

        // Cookie "remember-me" nastavimo pri FB, Google in AAi prijavi in tukaj preverjamo, če je nastavljena ta opcija
        if ((isset($_POST['remember']) && $_POST['remember'] == "1") || (isset($_COOKIE['remember-me']) && $_COOKIE['remember-me'] == 1)) {
            $LifeTime = 3600 * 24 * 365;
        } else {
            $LifeTime = $LifeTime;
        }

        // Preverimo ce obstaja uporabnik s tem emailom
        $user_id = User::findByEmail($this->email);
        if (!empty($user_id)) {
            $sql = sisplet_query("SELECT type, pass, status, id, name, surname, email FROM users WHERE id='".$user_id."'");
            $r = mysqli_fetch_assoc($sql);

            // BAN
            if ($r['status'] == 0) {
                header('Location: '.$this->page_urls['page_user_ban'.$this->prijava].'&error=user_ban&email='.$this->email);
                die();
            }

            $user_lang = 1;
            if (!empty($_POST['jezik']) && $_POST['jezik'] == 'en') {
                $user_lang = 2;
            }

            // Preverimo ce je password ok
            if (base64_encode((hash('SHA256', $this->pass.$pass_salt))) == $r['pass'] || $this->EncPass == $r['pass']) {
                
                // Zabelezimo datum prijave
                sisplet_query("UPDATE users SET last_login=NOW(), lang='".$user_lang."' WHERE id='".$r['id']."'");

                // določi še, od kje se je prijavil
                $hostname = "";
                $headers = apache_request_headers();
                if (array_key_exists('X-Forwarded-For', $headers)) {
                    $hostname = $headers['X-Forwarded-For'];
                } else {
                    $hostname = $_SERVER["REMOTE_ADDR"];
                }            
                sisplet_query("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('".$r['id']."', '".$hostname."', NOW())");
                
         
                // Ustvarimo login cookie
                setcookie("uid", base64_encode($r['email']), time() + $LifeTime, '/', $cookie_domain);

                //Preverimo če gre za Google 2FA
                $user_2fa_enabled = User::option($r['id'], 'google-2fa-validation');
                if(!empty($user_2fa_enabled) && $user_2fa_enabled != 'NOT'){
                    setcookie("g2fa", base64_encode($user_2fa_enabled), time() + $LifeTime, '/', $cookie_domain);
                    header('location: '.$this->page_urls['page_login_2fa']);
                    die();
                }

                // Ustvarimo piškotek še z  imenom in geslom
                setcookie("unam", base64_encode($r['name'].' '.$r['surname']),time() + $LifeTime, '/', $cookie_domain);
                setcookie("secret", $r['pass'], time() + $LifeTime, '/',         $cookie_domain);
         

                if ($r['status'] == "2" || $r['status'] == "6") {
                    setcookie("P", time(), time() + $LifeTime, '/', $cookie_domain);
                    header('location: '.$this->page_urls['page_login'.$this->prijava].'&email='.$this->email.'&error=password');
                    die();
                }
            } 
            else {
                // Password prompt
                header('location: '.$this->page_urls['page_login'.$this->prijava].'&email='.$this->email.'&error=password');
                die();
            }
        } 
        else {
            // Preverimo, če je sploh vpisal email
            if (validEmail($this->email)) {
                // Emaila ni v bazi
                header('location: '.$this->page_urls['page_login_noEmail'.$this->prijava].'&email='.$this->email);
            } else {
                // Ni vpisana prava oblika maila
                header('location: '.$this->page_urls['page_login_noEmail'.$this->prijava].'&email='.$this->email);
            }
            die();
        }

        //Vkolikor smo ga prijavili in želi kupip paket, vrnemo nazaj na Drupal
        if(isset($_COOKIE['nakup'])){
            if($user_lang == 1){
                header('location: '.$site_url.'d/izvedi-nakup/'.$_COOKIE['paket'].'/podatki');
            } else{
                header('location: '.$site_url.'d/en/purchase/'.$_COOKIE['paket'].'/package');
            }
            die();
        }

        // Vse je ok - prijavljenega preusmerimo na moje ankete
        header('location: '.$site_url.'admin/survey/index.php?lang='.$user_lang);
        die();
    }

    // Prijava z Google 2 FA
    private function userLogin2fa()
    {
        global $site_url, $cookie_domain;

        $email = null;
        if(!empty($_COOKIE['uid'])){
            $email = base64_decode($_COOKIE['uid']);
        }

        $user_id = User::findByEmail($email);
        $user= sisplet_query("SELECT type, pass, status, name, surname, email FROM users WHERE id='".$user_id."'", "obj");

        $secret = User::option($user_id, 'google-2fa-secret');
        if(!empty($secret) && $_POST['google_2fa_number']){
            $google2fa = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

            $user_lang = 1;
            if (!empty($_POST['jezik']) && $_POST['jezik'] == 'en') {
                $user_lang = 2;
            }

            // 2FA je bila uspešna
            if ($google2fa->checkCode($secret, $_POST['google_2fa_number'])) {
                $result = sisplet_query("SELECT value FROM misc WHERE what='CookieLife'", "obj");
                $LifeTime = $result->value;

                // Ustvarimo piškotek še z  imenom in geslom
                setcookie("unam", base64_encode($user->name.' '.$user->surname),time() + $LifeTime, '/', $cookie_domain);
                setcookie("secret", $user->pass, time() + $LifeTime, '/',         $cookie_domain);


                // Zabelezimo datum prijave
                sisplet_query("UPDATE users SET last_login=NOW() WHERE id='".$user_id."'");

                // določi še, od kje se je prijavil
                $hostname = "";
                $headers = apache_request_headers();
                if (array_key_exists('X-Forwarded-For', $headers)) {
                    $hostname = $headers['X-Forwarded-For'];
                } else {
                    $hostname = $_SERVER["REMOTE_ADDR"];
                }
                sisplet_query("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('".$user_id."', '".$hostname."', NOW())");
                

                // Vse je ok - prijavljenega preusmerimo na moje ankete
                header('location: '.$site_url.'admin/survey/index.php?lang='.$user_lang);
                die();
            }

            // Vse neuspešne poskuse ali napačen email
            header('location: '. $this->page_urls['page_login_2fa'].'&error=2fa');
            die();
        }

        // Vse je ok - prijavljenega preusmerimo na moje ankete
        header('location: '.$site_url);
        die();
    }

    // Prijavi userja v 1ko z Google racunom (kopirano iz ProfileClass.php) - PRETESTIRATI
    private function userLoginGoogle()
    {
        require_once('../../function/JWT.php');

        global $site_url;
        global $lang;
        global $proxy;

        $oauth2_code = $_GET['code'];
        $discovery = json_decode(file_get_contents('https://accounts.google.com/.well-known/openid-configuration'));

        if ($proxy != "") {
            $ctx = stream_context_create([
              'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query([
                  'client_id' => AppSettings::getInstance()->getSetting('google-login_client_id'),
                  'client_secret' => AppSettings::getInstance()->getSetting('google-login_client_secret'),
                  'code' => $oauth2_code,
                  'grant_type' => 'authorization_code',
                  'redirect_uri' => $site_url.'frontend/api/google-oauth2.php',
                  'openid.realm' => $site_url,
                ]),
                'proxy' => 'tcp://'.$proxy,
              ],
            ]);

        } else {
            $ctx = stream_context_create([
              'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query([
                  'client_id' => AppSettings::getInstance()->getSetting('google-login_client_id'),
                  'client_secret' => AppSettings::getInstance()->getSetting('google-login_client_secret'),
                  'code' => $oauth2_code,
                  'grant_type' => 'authorization_code',
                  'redirect_uri' => $site_url.'frontend/api/google-oauth2.php',
                  'openid.realm' => $site_url,
                ]),
              ],
            ]);
        }


        $resp = file_get_contents($discovery->token_endpoint, false, $ctx);

        if (!$resp) {
            // $http_response_header here got magically populated by file_get_contents(), surprise
            echo '<h1>'.$lang['oid_auth_rejected'].'</h1>';
            echo '<p>'.$lang['google_auth_rejected'].'</p>';

            echo '<ul><li>'.$lang['oid_maybe_you_rejected'].'<a href="'.$site_url.'index.php">'.$lang['try_again'].'</a></li><li>'.$lang['oid_maybe_local1'].'<a href="'.$site_url.'index.php">'.$lang['oid_maybe_local2'].'</a></li></ul>';
        }

        $resp = json_decode($resp);
        $access_token = $resp->access_token;
        $id_token = $resp->id_token;

        // Skip JWT verification: we got it directly from Google via https, nothing could go wrong.
        $id_payload = JWT::decode($resp->id_token, null, false);

        if (!$id_payload->sub) {
            echo '<h1>'.$lang['oid_auth_rejected'].'</h1>';
            echo '<p>'.$lang['google_auth_rejected'].'</p>';

            echo '<ul><li>'.$lang['oid_maybe_you_rejected'].'<a href="'.$site_url.'index.php">'.$lang['try_again'].'</a></li><li>'.$lang['oid_maybe_local1'].'<a href="'.$site_url.'index.php">'.$lang['oid_maybe_local2'].'</a></li></ul>';
        }

        $user_id = 'google+'.$id_payload->sub;
        $user_email = $id_payload->email;

        if ($user_email != '' && $user_id != '') {
            $this->email = $user_email;


            $user_id_1ka = User::findByEmail($user_email);
            // Je noter, ga samo prijavim...
            if (!empty($user_id_1ka)) {
                $res = sisplet_query("SELECT pass FROM users WHERE id='".$user_id_1ka."'");
                $r = mysqli_fetch_row($res);

                $this->EncPass = $r[0];

                $this->userLogin();
            } // Ni se registriran, ga je potrebno dodati na prijavno formo
            else {
                // geslo med 00000 in zzzzz
                $this->pass = base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36);
                $this->EncPass = base64_encode((hash('SHA256', $this->pass.$pass_salt)));
                $this->email = $user_email;

                $fn = explode("@", $user_email);

                sisplet_query("INSERT INTO users (name, surname, email, pass, lang, when_reg) VALUES ('".$fn[0]."', '', '".$user_email."', '".$this->EncPass."', '".(isset ($_GET['regFromEnglish']) && $_GET['regFromEnglish'] == "1" ? '2' : '1')."', NOW())");
                $uid = mysqli_insert_id($GLOBALS['connect_db']);

                sisplet_query("INSERT INTO oid_users (uid) VALUES ('$uid')");

                // Piškotek za cca. 10 let, da mu naslednjić ponudimo prijavno
                global $cookie_domain;
                setcookie('external-login', '1', time()+280000000, '/', $cookie_domain);

                // prijavi
                $this->userLogin();
            }
        }
    }

    // Prijavi userja v 1ko z FB racunom (kopirano iz ProfileClass.php) - PRETESTIRATI
    private function userLoginFacebook()
    {
        global $cookie_path;

        if ($r = file_get_contents("https://graph.facebook.com/v2.9/oauth/access_token?client_id=".AppSettings::getInstance()->getSetting('facebook-appid')."&redirect_uri=https://www.1ka.si/frontend/api/fb_login.php&client_secret=".AppSettings::getInstance()->getSetting('facebook-appsecret')."&code=".$_GET['code'])) {

            $at = json_decode($r);
            $user = json_decode(file_get_contents('https://graph.facebook.com/me?fields=email,first_name,last_name&access_token='.$at->{'access_token'}));

            if (!isset ($user->email) && isset ($user->name)) {
                $user->email = str_replace(" ", ".",
                    $user->first_name.".".$user->last_name)."@facebook.com";
            }

            $old_email = str_replace(" ", ".", $user->first_name.".".$user->last_name)."@facebook.com";
            $old_email = str_replace([" ", "č", "ć", "Č", "Ć", "ž", "Ž", "š", "Š", "đ", "Đ"], [".", "c", "c", "C", "C", "z", "Z", "s", "S", "d", "D"], $old_email);

            // preveri email, ce ga imas v bazi:
            if (isset ($user->email) && $user->email != '') {

                $obstaja_user_id = User::findByEmail(str_replace("'",'', $user->email));

                $result = sisplet_query("select u.name, u.surname, f.id, u.id, u.pass FROM users u, fb_users f WHERE u.id=f.uid AND u.id='".$obstaja_user_id."'");

                if (mysqli_num_rows($result) == 0) {

                    $obstaja_user_id_old_email = User::findByEmail(str_replace("'",'', $old_email));
                    $result2 = sisplet_query("select u.id FROM users u LEFT JOIN fb_users f on (u.id=f.uid) where u.id='".$obstaja_user_id_old_email."'");
                    if (mysqli_num_rows($result2) > 0) {

                        $r2 = mysqli_fetch_row($result2);

                        $real_id = User::findByEmail($user->email);
                        if (!empty($real_id)) {

                            // moramo popravljati IDje in jebat ježa
                            // iz "pravega" skopiram geslo na "fb", "fb" popravim v pravega in pravega dizejblam. In iz pravega vse srv_dpstop popravim na "fb"
                            sisplet_query("UPDATE users a, users b SET a.pass=b.pass WHERE a.email='".str_replace("'",
                                '',
                                $old_email)."' AND b.email='".str_replace("'",
                                '', $user->email)."'");
                            sisplet_query("UPDATE users SET email=CONCAT('D3LMD-' , email) WHERE email='".str_replace("'",
                                '', $user->email)."'");

                            if ($real_id[0] > 0 && $r2[0] > 0) {
                                sisplet_query("UPDATE srv_dostop SET uid=".$r2[0]." WHERE uid=".$real_id[0]);
                            }
                        }
                        sisplet_query("UPDATE users SET email='".str_replace("'",
                            '', $user->email)."' WHERE id='".$r2[0]."'");
                    }
                }

                $result = sisplet_query("select u.name, u.surname, IF(ISNULL(f.id),'0',f.id), u.id, u.pass FROM users u LEFT JOIN fb_users f on (u.id=f.uid) where u.id='".$obstaja_user_id."'");

                // je noter, preveri ce je v FB (podatki, podatki!)
                if (mysqli_num_rows($result) > 0) {

                    $r = mysqli_fetch_row($result);

                    if ($r[2] != '0') {
                        // samo prijavi
                        $this->EncPass = $r[4];
                        $this->email = str_replace(" ", ".", $user->email);

                        $this->userLogin();
                    } else {
                        // dodaj FB podatke in prijavi
                        if (isset ($user->first_name)) {
                            $fn = $user->first_name;
                        } else {
                            $fn = $r[0];
                        }

                        if (isset ($user->last_name)) {
                            $ln = $user->last_name;
                        } else {
                            $ln = $r[1];
                        }

                        if (isset ($user->gender)) {
                            $gn = $user->gender;
                        } else {
                            $gn = '';
                        }

                        if (isset ($user->profile_link)) {
                            $pl = $user->profile_link;
                        } else {
                            $pl = '';
                        }

                        if (isset ($user->timezone)) {
                            $tz = $user->timezone;
                        } else {
                            $tz = '';
                        }

                        sisplet_query("INSERT INTO fb_users (uid, first_name, last_name, gender, timezone, profile_link) VALUES ('".$r[3]."', '".$fn."', '".$ln."', '".$gn."', '".$tz."', '".$pl."')");

                        // Prijaviga :)
                        $this->EncPass = $r[4];
                        $this->email = $user->email;

                        $this->userLogin();
                    }
                } else {
                    // registriraj, dodaj FB podatke in prijavi
                    // dodaj FB podatke in prijavi
                    if (isset ($user->first_name)) {
                        $fn = $user->first_name;
                    } else {
                        $fn = str_replace(" ", ".", $r[0]);
                    }

                    if (isset ($user->last_name)) {
                        $ln = $user->last_name;
                    } else {
                        $ln = $r[1];
                    }

                    if (isset ($user->gender)) {
                        $gn = $user->gender;
                    } else {
                        $gn = '';
                    }

                    if (isset ($user->profile_link)) {
                        $pl = $user->profile_link;
                    } else {
                        $pl = '';
                    }

                    if (isset ($user->timezone)) {
                        $tz = $user->timezone;
                    } else {
                        $tz = '';
                    }

                    // geslo med 00000 in zzzzz
                    $this->pass = base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36);
                    $this->EncPass = base64_encode((hash('SHA256', $this->pass.$pass_salt)));
                    $this->email = str_replace([" ", "č", "ć", "Č", "Ć", "ž", "Ž", "š", "Š", "đ", "Đ"], [".", "c", "c", "C", "C", "z", "Z", "s", "S", "d", "D"], $user->email);

                    sisplet_query("INSERT INTO users (name, surname, email, pass, when_reg) VALUES ('".$fn."', '".$ln."', '".iconv('utf-8', 'iso-8859-2//TRANSLIT', $this->email)."', '".$this->EncPass."', NOW())");
                    $uid = mysqli_insert_id($GLOBALS['connect_db']);

                    sisplet_query("INSERT INTO fb_users (uid, first_name, last_name, gender, timezone, profile_link) VALUES ('".$uid."', '".$fn."', '".$ln."', '".$gn."', '".$tz."', '".$pl."')");

                    // Piškotek za cca. 10 let, da mu naslednjić ponudimo prijavno
                    global $cookie_domain;
                    setcookie('external-login', '1', time()+280000000, '/', $cookie_domain);

                    // prijavi
                    $this->userLogin();
                }
            }
        }
    }

    // Prijavi userja v 1ko preko AAI racuna (kopirano iz ProfileClass.php - eduroamAnotherServerLogin()) - PRETESTIRATI
    function userLoginAAI()
    {
        global $pass_salt;
        global $cookie_domain;
        global $site_url;

        // Popravimo string iz geta, ker ima nekje + namesto space
        $repaired_string = str_replace(' ', '+', $_GET['s']);

        // malo manj varno, ampak bo OK.
        $klobasa = base64_decode($repaired_string);


        // Dobimo array parametrov iz get-a
        $data = explode("|", $klobasa);

        // Pridobimo maile - mozno da jih je vec, potem vzamemo prvega
        $mails = explode(";", $data[0]);
        sort($mails);
        $mail = $mails[0];
        
        // Pridobimo aai (shibboleth) "uuid"
        $aai_id = $data[1];

        $ime = $data[2];
        $priimek = $data[3];

        $njegova = $data[4];
        $moja = $data[5];


        // Preverimo ce ima veljaven token (najprej pobrisemo stare)
        sisplet_query("DELETE FROM aai_prenosi WHERE timestamp < (UNIX_TIMESTAMP() - 600);");
        $res = sisplet_query("SELECT * FROM aai_prenosi WHERE moja='".$moja."' AND njegova='".$njegova."'");

        if (mysqli_num_rows($res) > 0) {

            $pass = base64_encode((hash('SHA256', "e5zhbWRTEGW&u375ejsznrtztjhdtz%WZ&".$pass_salt)));

            // Preverimo ce obstaja user v bazi
            $user_id_1ka = User::findByEmail_AAI($mail, $aai_id);

            if (empty($user_id_1ka)) {
                       
                // Nastavimo pass
                $pass = base64_encode(hash('SHA256', "e5zhbWRTEGW&u375ejsznrtztjhdtz%WZ&".$pass_salt));
                
                // dodaj ga v bazo
                sisplet_query("INSERT INTO users (email, aai_id, name, surname, type, pass, eduroam, when_reg) VALUES ('$mail', '$aai_id', '$ime', '$priimek', '3', '".$pass."', '1', NOW())");

                // Pridobimo id dodanega userja
                $user_id = mysqli_insert_id($GLOBALS['connect_db']);
            } 
            else {

                // potegni geslo in mu daj kuki
                $result = sisplet_query("SELECT pass, email FROM users WHERE id='".$user_id_1ka."'");    
                $r = mysqli_fetch_row($result);
                
                $pass = $r[0];
                $mail = $r[1];
                $user_id = $user_id_1ka;
            }

            $result = sisplet_query("SELECT value FROM misc WHERE what='CookieLife'");
            $row = mysqli_fetch_row($result);
            $LifeTime = $row[0];

            // Zlogiramo login
            sisplet_query("UPDATE users SET last_login=NOW() WHERE id='".$user_id."'");

            // določi še, od kje se je prijavil
            $hostname = "";
            $headers = apache_request_headers();
            if (array_key_exists('X-Forwarded-For', $headers)) {
                $hostname = $headers['X-Forwarded-For'];
            } else {
                $hostname = $_SERVER["REMOTE_ADDR"];
            }
            sisplet_query("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('".$user_id."', '".$hostname."', NOW())");

            setcookie("uid", base64_encode($mail), time() + $LifeTime, '/', $cookie_domain);
            setcookie("secret", $pass, time() + $LifeTime, '/', $cookie_domain);
            setcookie("unam", base64_encode($ime.' '.$priimek),time() + $LifeTime, '/', $cookie_domain);


            // moram vedeti, da je AAI!
            setcookie("aai", '1', time() + $LifeTime, '/', $cookie_domain);

            // Piškotek za cca. 10 let, da mu naslednjić ponudimo prijavno
            setcookie('external-login', '1', time()+280000000, '/', $cookie_domain);

            // Vse je ok - prijavljenega preusmerimo na moje ankete
            header('location: '.$site_url.'admin/survey/index.php?l=1');
            die();
        } 
        else {
            header('location: '.$site_url);
            die();
        }
    }

    // Odjavi userja iz 1ke (kopirano iz ProfileClass.php)
    private function userLogout(){
        global $site_url;
        global $cookie_domain;
        global $global_user_id;

        setcookie('uid', '', time() - 3600, '/', $cookie_domain);
        setcookie('unam', '', time() - 3600, '/', $cookie_domain);
        setcookie('secret', '', time() - 3600, '/', $cookie_domain);
        setcookie('ME', '', time() - 3600, '/', $cookie_domain);
        setcookie('P', '', time() - 3600, '/', $cookie_domain);
        setcookie("AN", '', time() - 3600, '/', $cookie_domain);
        setcookie("AS", '', time() - 3600, '/', $cookie_domain);
        setcookie("AT", '', time() - 3600, '/', $cookie_domain);

        setcookie("DP", $p, time() - 3600 * 24 * 365, "/", $cookie_domain);
        setcookie("DC", $p, time() - 3600 * 24 * 365, "/", $cookie_domain);
        setcookie("DI", $p, time() - 3600 * 24 * 365, "/", $cookie_domain);
        setcookie("SO", $p, time() - 3600 * 24 * 365, "/", $cookie_domain);
        setcookie("SPO", $p, time() - 3600 * 24 * 365, "/", $cookie_domain);
        setcookie("SL", $p, time() - 3600 * 24 * 365, "/", $cookie_domain);


        // pobrisi se naddomeno! (www.1ka.si naj pobrise se 1ka.si)
        if (substr_count($cookie_domain, ".") > 1) {
            $nd = substr($cookie_domain, strpos($cookie_domain, ".") + 1);

            setcookie('uid', '', time() - 3600, '/', $nd);
            setcookie('unam', '', time() - 3600, '/', $nd);
            setcookie('secret', '', time() - 3600, '/', $nd);
            setcookie('ME', '', time() - 3600, '/', $nd);
            setcookie('P', '', time() - 3600, '/', $nd);
            setcookie("AN", '', time() - 3600, '/', $nd);
            setcookie("AS", '', time() - 3600, '/', $nd);
            setcookie("AT", '', time() - 3600, '/', $nd);

            setcookie("DP", $p, time() - 3600 * 24 * 365, "/", $nd);
            setcookie("DC", $p, time() - 3600 * 24 * 365, "/", $nd);
            setcookie("DI", $p, time() - 3600 * 24 * 365, "/", $nd);
            setcookie("SO", $p, time() - 3600 * 24 * 365, "/", $nd);
            setcookie("SPO", $p, time() - 3600 * 24 * 365, "/", $nd);
            setcookie("SL", $p, time() - 3600 * 24 * 365, "/", $nd);
        }

        // Ce gre za arnes aai odjavo odjavimo posebej
        if (isAAI()){
            setcookie("aai", '', time() - 3600, '/', $cookie_domain);
            header('location: '.$site_url.'/logout_AAI.php?return='.$site_url);
            die();
        }

        header('Location:'.$site_url);
    }


    // Registrira userja v 1ko - vnos podatkov
    private function userRegister()
    {
        $error = [];

        $email = (isset($_POST['email'])) ? $_POST['email'] : '';
        $ime = (isset($_POST['ime'])) ? $_POST['ime'] : '';
        $geslo = (isset($_POST['geslo'])) ? $_POST['geslo'] : '';
        $geslo2 = (isset($_POST['geslo2'])) ? $_POST['geslo2'] : '';
        $agree = (isset($_POST['agree'])) ? $_POST['agree'] : '0';
        $gdprAgree = (isset($_POST['gdpr-agree'])) ? $_POST['gdpr-agree'] : '0';
        $ajaxKlic = (isset($_POST['ajax'])) ? $_POST['ajax'] : '0'; // Če izvajamo registracjo preko drupala, ker se pošlje post request preko ajaxa

        $varnostno_polje = (isset($_POST['varnostno-polje'])) ? $_POST['varnostno-polje'] : false;
        if (!empty($varnostno_polje)) {
            header('Location: '.$this->page_urls['page_robot_redirect']);
            die();
        }


        // Preverimo ReCaptcha
        if (AppSettings::getInstance()->getSetting('google-secret_captcha') !== false) {
            $recaptchaResponse = $_POST['g-recaptcha-response'];
            $requestReCaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".AppSettings::getInstance()->getSetting('google-secret_captcha')."&response=".$recaptchaResponse);

            if (!strstr($requestReCaptcha, "true")) {
                $error['invalid_recaptcha'] = '1';
            }
        }


        // Preverimo ce imamo vse podatke
        if ($email == '') {
            $error['missing_email'] = '1';
        }
        if ($ime == '') {
            $error['missing_ime'] = '1';
        }
        if ($agree == '0') {
            $error['missing_agree'] = '1';
        }

        // Preverimo ce je email ok
        if (!validEmail($email)) {
            $error['invalid_email'] = '1';
        }

        // Preverimo ce sta gesla enaka
        if ($geslo != $geslo2) {
            $error['pass_mismatch'] = '1';
        }

        // Preverimo ce je geslo dovolj kompleksno
        if (!complexPassword($geslo)) {
            $error['pass_complex'] = '1';
        }

        // Preverimo ce ze obstaja ime in vrnemo predlog za novo
        $sql = sisplet_query("SELECT * from users WHERE name='".$ime."'");
        if (mysqli_num_rows($sql) > 0) {

            $error['existing_ime'] = '1';
            $najdu = 0;
            $add = 0;

            if($ime != ''){
                do {
                    $add++;
                    $sqln = sisplet_query("SELECT * from users WHERE name='".str_replace("'",
                        "", $ime).$add."'");
                    if (mysqli_num_rows($sqln) == 0) {
                        $najdu = 1;
                    }

                } while ($najdu = 0);

                // Novo ime ki ga predlagamo
                $ime = $ime.$add;

                $error['new_username'] = $ime;
            }
        }

        // Preverimo ce ze obstaja email
        if (!unikatenEmail($email)) {
            $error['existing_email'] = '1';
        }


        // Nekaj ni ok - posljemo na isto stran z errorji v GET-u
        if (!empty($error)) {

            if($ajaxKlic){
               echo json_encode($this->preveriNapake($error));
               die();
            }

            // Ime in email posljemo nazaj v urlju
            $params = 'email='.$email.'&ime='.$ime.'&gdpr='.$gdprAgree.'&';

            // Errorje tudi posljemo preko GET-a
            foreach ($error as $key => $val) {
                $params .= $key.'='.$val.'&';
            }
            $params = substr($params, 0, -1);

            header('Location: '.$this->page_urls['page_register'].'&'.$params);
            die();
        } // Vse je ok - preusmerimo na potrditveno stran
        else {

            if($ajaxKlic){
                echo json_encode([
                    'success' => '1'
                ]);
                die();
            }

            // Hidden form, ki ga z js potem postamo naprej (da prenesemo vnesene podatke na naslednjo stran)
            echo '<form name="register" action="'.$this->page_urls['page_register_confirm'].'" method="post">';
            echo '	<input type="hidden" name="email" value="'.$email.'" />';
            echo '	<input type="hidden" name="ime" value="'.$ime.'" />';
            echo '	<input type="hidden" name="gdpr-agree" value="'.$gdprAgree.'" />';
            //echo '	<input type="hidden" name="geslo" value="'.base64_encode($geslo).'" />';
            echo '	<input type="hidden" name="geslo" value="'.$geslo.'" />';
            echo '</form>';

            // Z js potem postamo na naslednjo stran
            echo '<script type="text/javascript">';
            echo '	document.register.submit();';
            echo '</script>';

            /*header('Location: '.$this->page_urls['page_register_confirm']);
            @smalc.s;*/
        }
    }


    private function preveriNapake($parametri)
    {

        // Nastavimo jezik
        $language = 1;
        if(isset($_POST['language'])){
          $language = $_POST['language'];
        }
        elseif(isset($_POST['jezik'])){
          $language = ($_POST['jezik'] == 'en' ? 2 : 1);
        }

        if(is_numeric($language)){
          include_once('../../lang/'.$language.'.php');
        }

        $napaka = [];

        // Napaka pri emailu in opozorilo
        $error_email = FALSE;
        if (!empty($parametri['missing_email']) && $parametri['missing_email'] == 1) {

            $error_email = TRUE;
            $napaka[] = $lang['cms_error_missing_email'];

        } elseif (!empty($parametri['invalid_email']) && $parametri['invalid_email'] == 1) {

            $error_email = TRUE;
            $napaka[] = $lang['cms_error_email'];

        } elseif (!empty($parametri['existing_email']) && $parametri['existing_email'] == 1) {

            $error_email = TRUE;
            $napaka[] = str_replace("RESTORE_PASSWORD",
                "/frontend/api/api.php?action=reset_password&email=" . $parametri['email'],
                $lang['cms_error_email_took']);

        }

        // Napaka pri imenu
        $error_ime = FALSE;
        if (!empty($parametri['missing_ime']) && $parametri['missing_ime'] == 1) {

            $error_ime = TRUE;
            $napaka[] = $lang['cms_error_user_field_empty'];

        } elseif (!empty($parametri['existing_ime']) && $parametri['existing_ime'] == 1) {

            $error_ime = TRUE;
            $napaka[] = $lang['cms_error_user_took'];
            $new_username = $parametri['new_username'];

        }

        // Recaptcha error
        $error_recaptcha = FALSE;
        if (!empty($parametri['invalid_recaptcha']) && $parametri['invalid_recaptcha'] == 1) {

            $error_recaptcha = TRUE;
            $napaka[] = $lang['cms_error_recaptcha'];

        }

        // Napaka pri napačno vpisanih geslih
        $error_geslo = FALSE;
        if (!empty($parametri['pass_mismatch']) && $parametri['pass_mismatch'] == 1) {

            $error_geslo = TRUE;
            $napaka[] = $lang['cms_error_password_incorrect'];
        }
        // Geslo ni dovolj kompleksno
        if (!$error_geslo && !empty($parametri['pass_complex']) && $parametri['pass_complex'] == 1) {

            $error_geslo = TRUE;
            $napaka[] = $lang['password_err_complex'];
        }

        return [
            'napaka' => $napaka,
            'error_geslo' => $error_geslo,
            'error_ime' => $error_ime,
            'error_email' => $error_email,
            'error_recaptcha' => $error_recaptcha,
            'new_username' => $new_username ?? ''
        ];
    }

    // Registrira userja v 1ko - potrditev podatkov za registracijo (vnos userja v bazo v tabelo users_to_be) in posiljanje potrditvenega maila
    private function userRegisterConfirm()
    {
        global $site_url;
        global $site_path;
        global $site_domain;
        global $pass_salt;
        global $lang;


        $email = (isset($_POST['email']) ? $_POST['email'] : '');
        $ime = (isset($_POST['ime']) ? $_POST['ime'] : '');
        //$geslo = (isset($_POST['geslo']) ? base64_decode($_POST['geslo']) : '');
        $geslo = (isset($_POST['geslo']) ? $_POST['geslo'] : '');
        $gdprAgree = (isset($_POST['gdpr-agree']) ?  $_POST['gdpr-agree'] : 0);
        $ajax = (isset($_POST['ajax']) ? $_POST['ajax'] : 0); // če je Drupal ajax request


        // Nastavimo jezik
        $language = 1;
        if(isset($_POST['language'])){
            $language = $_POST['language'];
        }
        elseif(isset($_POST['jezik'])){
            $language = ($_POST['jezik'] == 'en' ? 2 : 1);
        }

        if(is_numeric($language)){
            include_once('../../lang/'.$language.'.php');
        }


        $kdaj = date('Y-m-d');

        $g = base64_encode($geslo);

        if (strlen($ime) < 1) {
            $afna = strpos($email, "@");
            $ime = substr($email, 0, $afna);
        }

        $priimek = '';

		// Ce imamo vklopljeno potrjevanje urednika aplikacije ga potrdi admin
        if (AppSettings::getInstance()->getSetting('confirm_registration') === true)
            $status = 2;
		else
            $status = 1;
		 // Zakaj je bilo prej vedno status 2? Ker to pomeni, da ni aktiviran in se ne more prijaviti!
		 //$status = 2;

        // Email potrjevanje - vedno aktivirano
        // naredi link za aktivacijo
        $code = base64_encode((hash('SHA256', time().$pass_salt.$email.$ime)));

        // Vstavimo novega userja v users_to_be kjer caka na aktivacijo
        $result = sisplet_query("INSERT INTO users_to_be 
									(type, email, name, surname, pass, status, gdpr_agree, when_reg, came_from, timecode, code, lang) 
									VALUES 
									('3', '".$email."', '".$ime."', '".$priimek."', '".$g."',  '".$status."', '".$gdprAgree."','".$kdaj."', '0', '".time()."', '".$code."', '".$language."')
								");
        $id = mysqli_insert_id($GLOBALS['connect_db']);

        
		// Sestavimo mail z aktivacijsko kodo
		$Content = $lang['confirm_user_mail'];
    
        // Podpis
        $signature = Common::getEmailSignature();
        $Content .= $signature;

        // Text ignorirajte sporocilo
        $Content .= $lang['confirm_user_mail_ignore'];

		// Ce gre slucajno za virutalko
		$Subject = (isVirtual()) ? $lang['confirm_user_mail_subject_virtual'] : $lang['confirm_user_mail_subject'];	
		
		// Ce mora admin potrditi dobi email admin in ne uporabnik!
		if(AppSettings::getInstance()->getSetting('confirm_registration') === true){

			// Popravimo besedilo emaila
	        $Content = $lang['confirm_user_mail_admin'];	        
		}
        
        $PageName = AppSettings::getInstance()->getSetting('app_settings-app_name');

		$ZaMail = '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.0 Transitional//EN">'.'<html><head>  <title>'.$Subject.'</title><meta content="text/html; charset=utf-8" http-equiv=Content-type></head><body>';

		// Besedilo v lang dilu je potrebno popravit, ker nimamo vec cel kup parametrov
		$Content = str_replace("SFMAIL", $email, $Content);
		$Content = str_replace("SFNAME", $ime.' '.$priimek, $Content);
		$Content = str_replace("SFPASS", "( ".strtolower($lang['srv_hidden_text'])." )", $Content);
		$Content = str_replace("SFPAGENAME", $PageName, $Content);

		$Content = str_replace("SFACTIVATEIN", '<a href="'.$site_url.'frontend/api/api.php?action=register_activate&amp;code='.$code.'&amp;id='.$id.'">', $Content);
		$Content = str_replace("SFACTIVATEOUT", '</a>', $Content);
		$Content = str_replace("SFEND", '</a>', $Content);

        $Subject = str_replace("SFPAGENAME", $PageName, $Subject);
        
		// Ce gre slucajno za virutalko
		if(isVirtual())
			$Subject = str_replace("SFVIRTUALNAME", $site_domain, $Subject);	



		$ZaMail .= $Content;
		$ZaMail .= "</body></html>";

		// Za testiranje brez posiljanja maila
		if(isDebug()) {
			echo $ZaMail; 
			die();
		}

        // Posljemo mail z linkom za aktivacijo racuna
        try{
            $MA = new MailAdapter(null, 'account');
                
            // Ce mora admin potrditi, posljemo njemu mail
            if(AppSettings::getInstance()->getSetting('confirm_registration') === true){
                $confirm_registration_admin = AppSettings::getInstance()->getSetting('confirm_registration_admin');

                if(is_array($confirm_registration_admin)){
                    // Mail posljemo vsem nastavljenim adminom
                    foreach($confirm_registration_admin as $admin_email){
                        $MA->addRecipients($admin_email);
                        $result = $MA->sendMail($ZaMail, $Subject);
                    }
                }
                else{
                    $MA->addRecipients($confirm_registration_admin);
                    $result = $MA->sendMail($ZaMail, $Subject);
                }
            }
            else{
                $MA->addRecipients($email);
                $result = $MA->sendMail($ZaMail, $Subject);
            }
        }
        catch (Exception $e){
        }


		if($ajax){
			echo json_encode([
				'success' => 1
			]);
			die();
		}


        // Preko GET parametra pošljemo email za prikaz sporočilo, kam je bil poslan email za aktivacijo registracije
        $email = urlencode(base64_encode($email));

        // redirect po uspešni registraciji in poslanem emailu
        header('location: '.$this->page_urls['page_register_emailSend'].'&e='.$email);
    }

    // Po poslanem mailu po registraciji, user klikne na url in ga aktiviramo (kopiramo iz tabele users_to_be v tabelo users)
    private function userRegisterActivate()
    {
        global $lang;
        global $site_url;
        global $site_path;
        global $site_domain;
        global $pass_salt;
        global $cookie_domain;


        if (!isset ($_GET['code'])) {
            echo $lang['reg_confirm_error'];
        } 
        else {

            $code = $_GET['code'];
            $id = $_GET['id'];

            $result = sisplet_query("SELECT type, email, name, surname, pass, status, gdpr_agree, when_reg, came_from, lang
										FROM users_to_be 
										WHERE code='".$code."' AND id='".$id."'");
            if (mysqli_num_rows($result) > 0) {

                $r = mysqli_fetch_assoc($result);
                $geslo2 = base64_decode($r['pass']);
                $g = base64_encode((hash('SHA256', base64_decode($r['pass']).$pass_salt)));

                sisplet_query("INSERT INTO users 
								(type, email, name, surname, pass, status, gdpr_agree, when_reg, came_from, lang) 
								VALUES 
								('".$r['type']."', '".$r['email']."', '".$r['name']."', '".$r['surname']."', '".$g."', '".$r['status']."', '".$r['gdpr_agree']."', '".$r['when_reg']."', '".$r['came_from']."', '".$r['lang']."')");
                sisplet_query("DELETE FROM users_to_be WHERE id='$id'");

                $email = $r['email'];
                $pass = $r['pass'];
                $ime = $r['name'];

                $PageName = AppSettings::getInstance()->getSetting('app_settings-app_name');

                include_once('../../lang/'.$r['lang'].'.php');
                $Content = $lang['confirm_user_content'];
                $Subject = $lang['confirm_user_subject'];

                // Ce je ga moramo po registraciji odobriti dobi drugacno sporocilo
                if (AppSettings::getInstance()->getSetting('confirm_registration') === true){
                    $UserContent = $lang['register_user_banned_content'];
                }
                else{
                    $UserContent = $lang['register_user_content'];
                }

                // Podpis
                $signature = Common::getEmailSignature();
                $UserContent .= $signature;

                $UserContent .= $lang['register_user_content_edit'];
				
                $change = '<a href="'.$site_url.'admin/survey/index.php?a=nastavitve&m=global_user_myProfile">';
                $out = '<a href="'.$this->page_urls['page_unregister'].'?email='.$email.'">';

				// Ce gre slucajno za virtualko
                $Subject = (isVirtual()) ? $lang['register_user_subject_virtual'] : $lang['register_user_subject'];

                $UserContent = str_replace("SFNAME", $ime, $UserContent);
                $UserContent = str_replace("SFMAIL", $email, $UserContent);
                $UserContent = str_replace("SFWITH", $email, $UserContent);
                $UserContent = str_replace("SFPAGENAME", $PageName, $UserContent);
                $UserContent = str_replace("SFCHANGE", $change, $UserContent);
                $UserContent = str_replace("SFOUT", $out, $UserContent);
                $UserContent = str_replace("SFEND", '</a>', $UserContent);
                
				$Subject = str_replace("SFPAGENAME", $PageName, $Subject);
				// Ce gre slucajno za virtualko
				if(isVirtual())
					$Subject = str_replace("SFVIRTUALNAME", $site_domain, $Subject);

                if ($geslo2 == "") {
                    $UserContent = str_replace("SFPASS", "( ".$lang['without']." ) ", $UserContent);
                } else {
                    $UserContent = str_replace("SFPASS", "( ".strtolower($lang['srv_hidden_text'])." )", $UserContent);
                }
                if ($ime == "") {
                    $UserContent = str_replace("SFNAME", $lang['mr_or_mrs'], $UserContent);
                } else {
                    $UserContent = str_replace("SFNAME", $ime, $UserContent);
                }

                $UserContent = str_replace("SFWITH", $emailZaNaprej, $UserContent);

                $ZaMail = '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.0 Transitional//EN">'.'<html><head><title>'.$Subject.'</title><meta content="text/html; charset=utf-8" http-equiv=Content-type></head><body>';

                $ZaMail .= $UserContent;

                if(isDebug()){
                    echo $ZaMail;
                    die();
                }

                if (strpos($email, "@") && strpos($email, "@") != 0) {

                    try{
                        $MA = new MailAdapter(null, 'account');
            
                        $MA->addRecipients($email);

                        $result = $MA->sendMail($ZaMail, $Subject);
                    }
                    catch (Exception $e){
                    }            
                }

				// Ce imamo vklopljeno potrjevanje urednika aplikacije je to izvedel admin in ne prijavljamo
		        if (AppSettings::getInstance()->getSetting('confirm_registration') !== true){

                    // določi še, od kje se je prijavil
                    $hostname = "";
                    $headers = apache_request_headers();
                    if (array_key_exists('X-Forwarded-For', $headers)) {
                        $hostname = $headers['X-Forwarded-For'];
                    } else {
                        $hostname = $_SERVER["REMOTE_ADDR"];
                    }

                    // Dobimo id userja s tem emailom
                    $user_id = User::findByEmail($email);

                    // Zabelezimo datum prijave
                    sisplet_query("INSERT INTO user_login_tracker (uid, IP, kdaj) VALUES ('".$user_id."', '".$hostname."', NOW())");
                    sisplet_query("UPDATE users SET last_login=NOW() WHERE id='".$user_id."'");

                	setcookie('uid', base64_encode($email), time() + 360000000, '/', $cookie_domain);
                    setcookie("unam", base64_encode($r['name'].' '.$r['surname']),time() + 360000000, '/', $cookie_domain);
                	setcookie('secret', $g, time() + 360000000, '/', $cookie_domain);
					
					// redirect po potrditvi maila.
	                header('location: '.$this->page_urls['page_register_activate']);
				}
				else{
					echo $lang['user_confirm_p_admin_success'];
				}
            }
        }
    }

    private function userActivateAlternativEmail()
    {
        global $lang;

        if (!isset ($_GET['enc'])) {
            echo $lang['alternative_email_confirm_error'];
        } else {
            parse_str(base64_decode($_GET['enc']), $param);

            $poizvedba = "SELECT email, user_id FROM users_to_be WHERE code='".$param['code']."' AND id='".$param['id']."'";

            global $global_user_id;
            if(!empty($global_user_id))
                $poizvedba .= " AND user_id='".$global_user_id."'";

            $result = sisplet_query($poizvedba, "obj");


            if (!empty($result) && validEmail($result->email)) {
                $vpis = User::getInstance($result->user_id)->insertAlternativeEmail($result->email);

                if($vpis) {
                    sisplet_query("DELETE FROM users_to_be WHERE id='".$param['id']."'");


                    $uporabnik = sisplet_query("SELECT email, pass FROM users WHERE id='".$result->user_id."'",
                      "obj");

                    setcookie('uid', base64_encode($uporabnik->email),
                      time() + 360000000, '/', $cookie_domain);
                    setcookie('secret', $uporabnik->pass, time() + 360000000,
                      '/', $cookie_domain);
                }

                header('location: '.$this->page_urls['page_register_activate']);
            }else {
                header('location: '.$this->page_urls['page_main']);
            }
        }

    }


    // Po kliku na odjava v mailu uporabnika odregistriramo - PRETESTIRATI
    private function userUnregisterConfirm()
    {
        global $site_url;
        global $site_path;
        global $lang;
        global $global_user_id;
        global $cookie_domain;

        $email = $global_user_id;

        if (isset($_GET['email'])) {
            $email = strtolower($_GET['email']);

            if (is_numeric($email)) {
                $result = sisplet_query("SELECT email FROM users WHERE id='$email'");
                $r = mysqli_fetch_row($result);

                $email = $r[0];
            }
        }


        $ByeEmail = '<p>Spoštovani,</p><p>Uspešno ste se odjavili iz spletnega mesta www.1ka.si.</p><p>Veseli nas, da ste preizkusili orodje 1ka.</p><p>SFPAGENAME ekipa</p>';
        $ByeEmailSubject = 'Uspešna odjava';

        $result = sisplet_query("SELECT name FROM users WHERE email='$email'");
        list ($ime) = mysqli_fetch_row($result);

        $PageName = AppSettings::getInstance()->getSetting('app_settings-app_name');

        $ByeEmail = str_replace("SFPAGENAME", $PageName, $ByeEmail);
        if (strlen($ime) > 2) {
            $ByeEmail = str_replace("SFNAME", $ime, $ByeEmail);
        } 
        else {
            $ByeEmail = str_replace("SFNAME", $lang[mr_or_mrs], $ByeEmail);
        }

        $ByeEmailSubject = str_replace("SFPAGENAME", $PageName, $ByeEmailSubject);    
        if (strlen($ime) > 2) {
            $ByeEmailSubject = str_replace("SFNAME", $ime, $ByeEmailSubject);
        } 
        else {
            $ByeEmailSubject = str_replace("SFNAME", $lang['mr_or_mrs'],
              $ByeEmailSubject);
        }


        $result = sisplet_query("UPDATE users SET email=CONCAT('UNSU8MD-', UNIX_TIMESTAMP(), email) WHERE email='$email'");
        setcookie('uid', '', time() - 3600, '/', $cookie_domain);
        setcookie('secret', '', time() - 3600, '/', $cookie_domain);

        if (substr_count($cookie_domain, ".") > 1) {
            $nd = substr($cookie_domain, strpos($cookie_domain, ".") + 1);

            setcookie('uid', '', time() - 3600, '/', $nd);
            setcookie('secret', '', time() - 3600, '/', $nd);
        }


        if (strpos($email, "@") && strpos($email, "@") != 0) {

            // Poslemo mail za uspesno odregistracijo
            try{
                $MA = new MailAdapter(null, 'account');

                $MA->addRecipients($email);

                $result = $MA->sendMail($ByeEmail, $ByeEmailSubject);
            }
            catch (Exception $e){
            }     

            // Se obvestilo za admina
            try{
                $MA = new MailAdapter(null, 'account');

                $MA->addRecipients($From);

                $ByeEmail2 = $_lang['ByeNoteToAdmin'].$PageName." ".$email;

                $result = $MA->sendMail($ByeEmail2, $lang['ByeNoteToAdminSubject']);
            }
            catch (Exception $e){
            }     


            // Preusmerimo na stran potrditve
            header('location: '.$this->page_urls['page_unregister_confirm']);
        }
    }


    // Resetira geslo userja (kopirano iz ProfileClass.php) - PRETESTIRATI
    private function userResetPassword()
    {
        global $lang;
        global $site_url;
        global $pass_salt;
        global $site_path;
        global $site_domain;
        global $cookie_domain;

        if (isset ($_GET['email']) || isset ($_POST['email'])) {

            if (isset ($_GET['email'])) {
                $email = strtolower($_GET['email']);
            }
            if (isset ($_POST['email'])) {
                $email = strtolower($_POST['email']);
            }

            $email = CleanXSS($email);

            // Ali gre za ajax klic
            $ajaxKlic = false;
            if(!empty($_POST['ajax'])){
                $ajaxKlic = true;

                if($_POST['lang'] == 'en' || $_POST['jezik'] == 'en'){
                    include('../../lang/2.php');
                }
                else {
                    include('../../lang/1.php');
                }
            }
            // Za simple frontend nastavimo jezik
            elseif(isset($_GET['lang_id']) && is_numeric($_GET['lang_id'])){
                include('../../lang/'.$_GET['lang_id'].'.php');
            }

            // Ce emaila ni v bazi
            $user_id_1ka = User::findByEmail($email);
            if (empty($user_id_1ka)) {

                if($ajaxKlic){
                    echo json_encode([
                      'type' => 'error',
                      'text' => $lang['cms_error_no_email']
                    ]);
                }else {
                    header('location: '.$this->page_urls['page_login_noEmail'.$this->prijava].'&email='.$email);
                }
                die();
            } else {
                $result = sisplet_query("SELECT name, pass, surname FROM users WHERE id='".$user_id_1ka."'");
                list ($ime, $geslo, $priimek) = mysqli_fetch_row($result);
            }

            // Novo geslo sestavis iz dveh nakljucnih besed + stevilke
            include_once($site_path.'lang/words_'.$lang['language_short'].'.php');

            $geslo = strtolower($words[rand(0, 999)].rand(0, 9).$words[rand(0, 999)]);

            // passhint je parameter v linku ki ga skombiniras skupaj z emailom in mu potem aktiviras novo geslo
            $passhint = base64_encode((hash('SHA256', time().$pass_salt)));

            $chk = sisplet_query("SELECT id FROm users WHERE email='$email' AND UNIX_TIMESTAMP(NOW())-LastLP>600");
            if (mysqli_num_rows($chk) > 0) {
                $result = sisplet_query("UPDATE users SET LastLP=UNIX_TIMESTAMP(NOW()), lost_password='".base64_encode((hash(SHA256, $geslo.$pass_salt)))."', lost_password_code='$passhint' WHERE email='$email'");

				// Ce gre slucajno za virtualko
                $Subject = (isVirtual()) ? $lang['lost_pass_subject_virtual'] : $lang['lost_pass_subject'];

                $Content = $lang['lost_pass_mail'];

                $PageName = AppSettings::getInstance()->getSetting('app_settings-app_name');

                $ZaMail = '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.0 Transitional//EN">'.'<html><head>  <title>'.$Subject.'</title><meta content="text/html; charset=utf-8" http-equiv=Content-type></head><body>';

                $change = '<a href="'.$site_url.'admin/survey/index.php?a=nastavitve&m=global_user_myProfile">';
                $out = '<a href="'.$this->page_urls['page_unregister'].'&email='.$email.'">';

                $Content = str_replace("SFMAIL", $email, $Content);
                $Content = str_replace("SFNAME", $ime.' '.$priimek, $Content);
                $Content = str_replace("SFPASS", $geslo, $Content);
                $Content = str_replace("SFPAGENAME", $PageName, $Content);
                $Content = str_replace("SFACTIVATEIN",
                  '<a href="'.$this->page_urls['page_reset_password_activate'].'&code='.$passhint. ($ajaxKlic ? '#aktivacija-gesla' : null).'">',
                  $Content);
                $Content = str_replace("SFACTIVATEOUT", '</a>', $Content);
                $Content = str_replace("SFCHANGE", $change, $Content);
                $Content = str_replace("SFOUT", $out, $Content);
                $Content = str_replace("SFEND", '</a>', $Content);
                
				$Subject = str_replace("SFPAGENAME", $PageName, $Subject);
                
                // Ce gre slucajno za virtualko
				if(isVirtual())
					$Subject = str_replace("SFVIRTUALNAME", $site_domain, $Subject);

                if ($LoginWith == 1) {
                    $Content = str_replace("SFWITH", $email, $Content);
                } 
                else {
                    $Content = str_replace("SFWITH", $ime, $Content);
                }

                // Podpis
                $signature = Common::getEmailSignature();
                $Content .= $signature;

                $ZaMail .= $Content;
                $ZaMail .= "</body></html>";

                if(isDebug()){
                    echo $ZaMail;
                    die();
                }

                try{
                    $MA = new MailAdapter(null, 'account');
        
                    $MA->addRecipients($email);
    
                    $result = $MA->sendMail($ZaMail, $Subject);
                }
                catch (Exception $e){
                }   
            }

            if($ajaxKlic){
                echo json_encode([
                  'type' => 'success',
                  'text' => $lang['lp_sent'].'.'
                ]);
            }else {
                // Preusmerimo na stran potrditve
                header('location: '.$this->page_urls['page_reset_password'].'&email='.$email);
            }

        } else {
            header('location: '.$this->page_urls['page_login_noEmail'.$this->prijava].'&email='.$email);
        }
    }

    // Aktivira resetirano geslo userja (kopirano iz ProfileClass.php) - PRETESTIRATI
    private function userResetPasswordActivate()
    {
        global $lang;
        global $site_url;
        global $pass_salt;
        global $cookie_domain;

        $ajaxKlic = (!empty($_POST['ajax']) ? true : false);

        if (isset($_POST['code']) && isset($_POST['email']) && isset($_POST['pass'])) {

            $code = $_POST['code'];
            $email = strtolower($_POST['email']);
            $email = CleanXSS($_POST['email']);
            $pass = $_POST['pass'];
            $pass = CleanXSS($_POST['pass']);
            $pass = base64_encode((hash('SHA256', $pass.$pass_salt)));

            $result = sisplet_query("SELECT id, name, surname FROM users WHERE email='$email' AND lost_password='$pass' AND lost_password_code='$code'");
            if (mysqli_num_rows($result) > 0) {

                $r = mysqli_fetch_row($result);
                $result = sisplet_query("UPDATE users SET pass='$pass', lost_password='', lost_password_code='' WHERE id='".$r[0]."'");

                // kukiji
                $result = sisplet_query("SELECT value FROM misc WHERE what='CookieLife'");
                $row = mysqli_fetch_row($result);
                $LifeTime = $row[0];

                setcookie("uid", base64_encode($email), time() + $LifeTime, '/', $cookie_domain);
                setcookie("secret", $pass, time() + $LifeTime, '/',   $cookie_domain);
                setcookie("unam", base64_encode($r[1].' '.$r[2]),time() + $LifeTime, '/', $cookie_domain);

                if($ajaxKlic){
                    echo json_encode([
                        'type' => 'success',
                        'text' =>  $lang['you_can_change_pass_anytime'],
                        'action' => $site_url.'/admin/survey/'
                    ]);

                    die();
                }else {
                    // Preusmerimo na stran zahvale za spremembo gesla
                    header('location: ' . $this->page_urls['page_reset_password_activate'] . '&success=1');
                }
            } else {
                if($ajaxKlic){
                    echo json_encode([
                        'type' => 'error',
                        'text' =>  $lang['cms_activation_link_expired_text']
                    ]);

                    die();
                }else {
                    // Preusmerimo nazaj na formo zaradi napake
                    header('location: ' . $this->page_urls['page_reset_password_activate'] . '&error=2');
                }
            }
        } else {
            // Preusmerimo nazaj na formo zaradi napake
            header('location: '.$this->page_urls['page_reset_password_activate'].'&code='.$code.'&error=1');
        }
    }
}