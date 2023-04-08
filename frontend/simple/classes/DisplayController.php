<?php

include_once '../../function.php';
include_once '../../vendor/autoload.php';
include_once '../../sql/class.ImportDB.php';
	
	
class DisplayController{

	var $stran;			// stran na kateri se nahajamo
	var $podstran;		// podstran na kateri se nahajamo
	
	var $lang_id = 1;	// izbran jezik
	
	var $root = '';		// Za kasneje ce bomo vklopili rewrite
	

	function __construct(){
		global $admin_type;		
		global $site_url;		
		global $lang;		
        global $global_user_id;
        
        // Ce smo ze logirani vedno preusmerimo na moje ankete
        if($global_user_id != '' && $global_user_id > 0){
            header ('location: '.$site_url.'admin/survey/index.php');
			die();
        }
		
		if(isset($_GET['a']))
			$this->stran = $_GET['a'];
		
		if(isset($_GET['b']))
			$this->podstran = $_GET['b'];
		
		
		// Nastavimo jezik
		if(isset($_GET['lang_id']))
			$this->lang_id = $_GET['lang_id'];
		elseif(isset($_SESSION['lang_id']))
			$this->lang_id = $_SESSION['lang_id'];
		
		$_SESSION['langX'] = $site_url .'lang/'.$this->lang_id.'.php';
		$_SESSION['lang_id'] = $this->lang_id;
		
		$file = '../../lang/'.$this->lang_id.'.php';
		include($file);
	}
	
        
    public function displayHead(){
        global $site_url;
        global $lang;


        // Google analytics za AAI
        if(isAAI()){
            echo '<!-- Global site tag (gtag.js) - Google Analytics -->
                        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-141542153-2"></script>
                        <script>
                            window.dataLayer = window.dataLayer || [];
                            function gtag(){dataLayer.push(arguments);}
                            gtag(\'js\', new Date());
                            
                            gtag(\'config\', \'UA-141542153-2\');
                        </script>';
        }


        echo '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '    <meta charset="utf-8">';
        
        echo '    <meta name = "viewport" content = "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">';

        echo '    <meta name="keywords" content="web survey software, internet survey, online survey, web questionaires">';
        echo '    <meta name="keywords" content="spletne ankete, spletna anketa, spletno anketiranje, internetne ankete, slovenščina, slovenski jezik, software, softver, programska oprema, orodje za spletne ankete, internetno anketiranje, online vprašalniki, ankete po internetu, internet, internetne ankete, anketa" lang="si">';
        echo '    <meta name="description" content="1KA je orodje za spletne ankete, hkrati pa je tudi on-line platforma (gostitelj), na kateri se lahko spletna anketa brezplačno izdela.">';
        echo '    <meta name="abstract" content="1KA je orodje za spletne ankete">';
        echo '    <meta name="author" content="CDI, FDV">';
        echo '    <meta name="publisher" content="">';
        echo '    <meta name="copyright" content="CDI, FDV">';
        echo '    <meta name="audience" content="splošna populacija">';
        echo '    <meta name="page-topic" content="spletne aplikacije">';
        echo '    <meta name="revisit-after" content="7">';
            
        // Custom header title
        if(AppSettings::getInstance()->getSetting('app_settings-head_title_custom')){
            echo '<title>'.AppSettings::getInstance()->getSetting('app_settings-head_title_text').'</title>' . "\n";
        }
        // Default header title
        else{
            echo '<title>'.$lang['1ka_surveys'].'</title>' . "\n";
        }

        echo '    <!-- CSS -->';
        echo '    <link type="text/css" href="'.$site_url.'frontend/simple/css/style.css" rel="stylesheet" />';
        echo '    <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css" rel="stylesheet" /">';
            
        echo '    <!-- JAVASCRIPT -->';
        echo '    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>';
        echo '    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
        echo '    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js"></script>';
            
        echo '    <script type="text/javascript" src="'.$site_url.'frontend/simple/script/init.js"></script>';
        echo '    <script type="text/javascript" src="'.$site_url.'frontend/simple/script/script.js"></script>';
            
        echo '    <!-- FAVICON -->';
        echo '    <link rel="shortcut icon" type="image/ico" href="'.$site_url.'/favicon.ico" />';
    }


    // Izris zgornje vrstice z logotipom in menijem
	public function displayHeader(){
		global $site_url;
		global $lang;
        

        // Logo v glavi
        echo '<div class="logo ">';

        echo '  <a href="'.$site_url.$this->root.'index.php">';
        echo '      <img src="'.$site_url.'/public/img/logo/1ka_'.($this->lang_id != 1 ? 'eng' : 'slo').'.svg">';
        if(isAAI()){
            echo '      <img src="'.$site_url.'/public/img/logo/arnes_logo.png" style="margin-left:30px;">';
        }
        echo '  </a>';

        echo '</div>';
        

        // Navigacija
        echo '<nav>';

        // Hidden polje z lang_id-jem
        echo '	<input type="hidden" name="lang_id" value="'.$this->lang_id.'" />';
        
		// GDPR zahtevek je prisoten samo na virtualkah in na lastno zahtevo
		echo '<span class="menu_item '.($this->stran == 'gdpr' ? 'active' : '').'">';
		echo '  <a href="index.php?a=gdpr">'.$lang['srv_gdpr_drupal_tab'].'</a>';
		echo '</span>';
        
        // Preklop jezika
        echo '<span class="menu_item lang_switch">';
        $params = '?' . (isset($_GET['a']) ? 'a='.$_GET['a'].'&' : '') . (isset($_GET['b']) ? 'b='.$_GET['b'].'&' : '');
		if($this->lang_id == 1){
			echo '	<a href="'.$site_url.$this->root.'index.php'.$params.'lang_id=2">';
			echo '		<div class="flag eng"></div> <span>English</span>';
			echo '	</a>';
		}
		else{
			echo '	<a href="'.$site_url.$this->root.'index.php'.$params.'lang_id=1">';
			echo '		<div class="flag slo"></div> <span>Slovenščina</span>';
			echo '</a>';
		}
        echo '</span>';
		
        echo '</nav>';
	}
	
	// Izris vsebine
	public function displayMain(){
                        
        switch($this->stran){	

            /*case 'login':
                $this->displayLoginPage();
            break;*/	

            /*case 'login_noEmail':
                $this->displayLoginNoEmailPage();
            break;*/

            case 'login_2fa':
                $this->displayLogin2faPage();
                break;
              
            /*case 'register':
                if(!isVirtual())
                    $this->displayRegisterPage();
                else
                    $this->displayFrontPage();
            break;*/

            case 'register_confirm':
                if(!isVirtual())
                    $this->displayRegisterPageConfirm();
                else
                    $this->displayFrontPage();
            break;	

            case 'register_email':
                if(!isVirtual())
                    $this->displayRegisterPageEmail();
                else
                    $this->displayFrontPage();
            break;
            
            case 'unregister':
                if(!isVirtual())
                    $this->displayUnregisterPage();
                else
                    $this->displayFrontPage();
            break;

            case 'unregister_confirm':
                if(!isVirtual())
                    $this->displayUnregisterPageConfirm();
                else
                    $this->displayFrontPage();
            break;
        
            case 'reset_password':
                $this->displayResetPasswordPage();
            break;

            case 'reset_password_activate':
                $this->displayResetPasswordPageActivate();
            break;
            
            case 'gdpr':
                $this->displayGDPRPage();
            break;

            default:
                $this->displayFrontPage();
            break;
        }
    }

		
    
    // Izris footerja
    public function displayFooter(){
        global $lang;


        // Stolpec 1
        echo '<div class="col">';
        echo '  <h2>'.$lang['simple_footer_about'].'</h2>';
        echo '  <span>'.$lang['simple_footer_about_1ka'].'</span>';
        if(isAAI())
            echo '  <span>'.$lang['simple_footer_about_faq'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_general'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_privacy'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_cookies'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_antispam'].'</span>';
        echo '  <div class="follow">'.$lang['simple_footer_about_follow'].': ';
        echo '      <a href="https://twitter.com/enklikanketa" target="_blank"><span class="icon twitter"></span></a>';
        echo '      <a href="https://www.facebook.com/1KA-123545614388521/" target="_blank"><span class="icon fb"></span></a>';
        echo '  </div>';
        echo '</div>';
        
        
        // Stolpec 2
        echo '<div class="col">';
        echo '  <h2>'.$lang['simple_footer_company'].'</h2>';
        echo '  <span class="semi-bold">'.AppSettings::getInstance()->getSetting('app_settings-owner').'</span>';
        echo '  <span><a href="mailto:'.AppSettings::getInstance()->getSetting('app_settings-admin_email').'">'.AppSettings::getInstance()->getSetting('app_settings-admin_email').'</a></span>';
        echo '  <span><a href="'.AppSettings::getInstance()->getSetting('app_settings-owner_website').'" target="_blank">'.AppSettings::getInstance()->getSetting('app_settings-owner_website').'</a></span>';
        echo '</div>';


        // Stolpec 3 - logotipi
        echo '<div class="col">';

        // Logotipa FDV in CDI - samo pri virtualkah
        if(isVirtual() || isAAI()){
            echo '<div class="logo_holder">';
            echo '  <img src="'.$site_url.'/public/img/logo/fdv.png">';
            echo '  <img src="'.$site_url.'/public/img/logo/cdi_'.($this->lang_id != 1 ? 'eng' : 'slo').'.png">';
            echo '</div>';
        }

        echo '</div>';
    }
	
	
	// Izris prve strani
	private function displayFrontPage(){
        
        // AAI
        if(isAAI())
            $this->displayFrontPageFormAAI();
        else
            $this->displayFrontPageForm();
    }

    // Izris okna na prvi strani
    private function displayFrontPageForm(){
        global $lang;
		global $site_url;


        echo '<div class="app_title">'.AppSettings::getInstance()->getSetting('app_settings-app_name').'</div>';


        // WHITE BOX FOR LOGIN / REGISTRATION
		echo '<div class="landing_page_window">';      

        // Tabs - samo pri lastni instalaciji, pri virtualkah nimamo registracije
        if(isVirtual()){
            echo '	<div class="tabs">';
            echo '	    <div class="tab full_width">'.$lang['login_short'].'</div>';
            echo '	</div>';
        }
        else{
            echo '	<div class="tabs">';
            echo '	    <div class="tab '.(isset($_GET['a']) && $_GET['a'] == 'register' ? '' : 'active').'" onClick="switchLoginRegistration(this);">'.$lang['login_short'].'</div>';
            echo '	    <div class="tab '.(!isset($_GET['a']) || $_GET['a'] != 'register' ? '' : 'active').'" onClick="switchLoginRegistration(this);">'.$lang['nu_register'].'</div>';
            echo '	</div>';
        }
        
        // SKB ima blokirano prijavo za vse ipje razen svojega
        $ip = $_SERVER['REMOTE_ADDR'];
        $admin_allow_only_ip = AppSettings::getInstance()->getSetting('app_limits-admin_allow_only_ip');
        if($admin_allow_only_ip !== false 
            && !empty($admin_allow_only_ip) 
            && !in_array($ip, $admin_allow_only_ip)
        ){
            echo '<div style="padding: 50px; line-height: 30px; text-align: center; font-weight: 600;">Prijava v aplikacijo iz obstoječega IP naslova ('.$ip.') ni mogoča!</div>';
        }
        else{
            // LOGIN
            echo '	<div id="login_holder" '.(isset($_GET['a']) && $_GET['a'] == 'register' ? '' : 'class="active"').'>';
            $this->displayFrontPageLogin();		
            echo '  </div>';

            // REGISTRATION
            echo '  <div id="registration_holder"  '.(!isset($_GET['a']) || $_GET['a'] != 'register' ? '' : 'class="active"').'>';
            $this->displayFrontPageRegistration();
            echo '  </div>';
        }
	
        echo '</div>';
        

        // APP SUBTITLE
        echo '<div class="app_subtitle">';
        if(isVirtual())
            echo $lang['app_virtual_domain'];
        else
            echo $lang['app_installation'];
        echo '</div>';
    }

    // Izris okna na prvi strani - AAI
    private function displayFrontPageFormAAI(){
        global $lang;
		global $site_url;


        // WHITE BOX FOR LOGIN / REGISTRATION
        echo '<div class="landing_page_window">';      

        // APP TITLE - aai
        echo '<div class="app_title" style="text-transform: initial;">'.AppSettings::getInstance()->getSetting('app_settings-app_name').'</div>';

        // AAI logo
        //echo '<div class="arnes_logo"><img src="'.$site_url.'/public/img/logo/arnes_logo.png"></div>';

        // AAI text
        echo '	<div class="tabs">';
        echo '	    <div class="tab full_width">'.$lang['app_aai_installation_text'].'</div>';
        echo '	</div>';
                    
        // AAI login/register
		echo '  <a href="'.$site_url.'/aai"><input type="button" name="aai-login" title="'.$lang['aaiPopupTitle'].'" value="'.$lang['aaiPopupTitle'].'"></a>';
        
        echo '</div>';


        // APP SUBTITLE
        /*echo '<div class="app_subtitle">';
        echo $lang['app_aai_installation'];
        echo '</div>';*/
    }

    // Izris okna za login na prvi strani
    private function displayFrontPageLogin(){
        global $lang;
		global $site_url;

        if(isset($_GET['a']) && $_GET['a'] == 'register'){
            $email = '';
            $error = '';
        }
        else{
            $email = (isset($_GET['email'])) ? $_GET['email'] : '';

            $error = '';
            if(isset($_GET['a']) && $_GET['a'] == 'login_noEmail'){
                $error = 'email';
            }
            elseif(isset($_GET['error']) && $_GET['error'] == 'password'){
                $error = 'password';
            }
        }


        // Forma za vpis
		echo '<form name="login_1" id="login_form" class="login_form" action="'.$site_url.'/frontend/api/api.php?action=login" method="post">';
        
        // Email
        echo '  <label for="email" '.($error == 'email' ? 'class="red"': '').'>'.$lang['email'].'</label>';
        echo '  <input id="em" '.($error == 'email' ? 'class="red"': '').' name="email" value="'.$email.'" size="30" placeholder="E-mail" onblur="CheckEmailFP();" type="text">';

        // Password
        echo '  <label for="pass" '.($error == 'password' ? 'class="red"': '').'>'.$lang['password'].'</label>';
        echo '  <input '.($error == 'password' ? 'class="red"': '').' name="pass" placeholder="'.$lang['login_password'].'" type="password">';

        // Error text
        if($error != ''){
            echo '  <div class="error_holder">';

            if($error == 'email' && $email == '')
                echo $lang['cms_error_missing_email'];
            elseif($error == 'email')
                echo $lang['cms_error_wrong_email'];
            elseif($error == 'password')
                echo $lang['cms_error_password'];

            echo '  </div>';
        }

        // Lost pass
        echo '  <div class="lostpass"><a class="RegLastPage" href="#" onclick="LostPassword(\''.$lang['please_insert_email'].'\');">'.$lang['forgot_password'].'</a></div>';
        
        // Submit
		echo '  <input name="submit" title="'.$lang['login'].'" value="'.$lang['next1'].'" type="submit">';	
        
        echo '</form>';	
    }

    // Izris okna za registracijo na prvi strani
    private function displayFrontPageRegistration(){
        global $lang;
		global $site_url;
        
        if(!isset($_GET['a']) || $_GET['a'] != 'register'){
            $email = '';
            $ime = '';
            $error = array();
        }
        else{
            $email = (isset($_GET['email'])) ? $_GET['email'] : '';
            $ime = (isset($_GET['ime'])) ? $_GET['ime'] : '';

            if(isset($_GET['invalid_email']) && $_GET['invalid_email'] == '1'){
                $error['email'] = '1';
            }
            if(isset($_GET['existing_email']) && $_GET['existing_email'] == '1'){
                $error['email'] = '1';
            }
            if(isset($_GET['missing_ime']) && $_GET['missing_ime'] == '1'){
                $error['ime'] = '1';
            }
            if(isset($_GET['pass_complex']) && $_GET['pass_complex'] == '1'){
                $error['password'] = '1';
            }
            if(isset($_GET['pass_mismatch']) && $_GET['pass_mismatch'] == '1'){
                $error['password'] = '1';
            }
            if(isset($_GET['missing_agree']) && $_GET['missing_agree'] == '1'){
                $error['agree'] = '1';
            }
        }
        

        echo '<form name="register" id="register_form" class="register_form" action="'.$site_url.'frontend/api/api.php?action=register" method="post">';
        
        echo '  <span class="subtitle">'.$lang['cms_register_user_text'].'</span>';

		// Email
		echo '  <label for="email" '.(isset($error['email']) ? 'class="red"' : '').'>'.$lang['email'].':</label>';
		echo '  <input class="regfield '.(isset($error['email']) ? 'red' : '').'" id="email" name="email" value="'.$email.'" placeholder="'.$lang['email'].'" type="text">';
		
		// Ime
		echo '  <label for="ime" '.(isset($error['ime']) ? 'class="red"' : '').'>'.$lang['cms_register_user_nickname'].':</label>';
		echo '  <input class="regfield '.(isset($error['ime']) ? 'red' : '').'" id="ime" name="ime" value="'.$ime.'" placeholder="'.$lang['cms_register_user_nickname'].'" type="text">';
				
		// RECAPTCHA
		if(AppSettings::getInstance()->getSetting('google-secret_captcha') !== false && AppSettings::getInstance()->getSetting('google-recaptcha_sitekey') !== false)
			echo '  <div class="g-recaptcha" data-sitekey="'.AppSettings::getInstance()->getSetting('google-recaptcha_sitekey').'" '.(isset($_GET['invalid_recaptcha']) ? ' style="border:1px red solid"' : '').'></div>';	
		
		// Geslo
        echo '  <label for="p1" '.(isset($error['password']) ? 'class="red"' : '').'>'.$lang['login_password'].':</label>';
        echo '  <input id="p1" class="text '.(isset($error['password']) ? 'red' : '').'" value="" name="geslo" placeholder="'.$lang['password'].'" type="password">';
        
        // Geslo2
        echo '  <label for="p2" '.(isset($error['password']) ? 'class="red"' : '').'>'.$lang['cms_register_user_repeat_password'].':</label>';
        echo '  <input id="p2" class="text '.(isset($error['password']) ? 'red' : '').'" value="" name="geslo2" placeholder="'.$lang['cms_register_user_repeat_password'].'" type="password">';

		// Strinjam se s pogoji
        //echo '				<input id="IAgree" type="hidden" name="agree" value="1">';
        $terms_url = ($lang['id'] == '1') ? 'https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka' : 'https://www.1ka.si/d/en/about/terms-of-use';
		echo '  <div class="form_row agreement '.(isset($_GET['missing_agree']) ? ' red' : '').'">';
		echo '      <input id="IAgree" type="checkbox" name="agree" value="1"><label for="IAgree">';
		echo        '<div class="checkbox_text_holder"> '.str_replace('TERMS_URL', $terms_url, $lang['cms_register_user_terms_of_use']).'</label>';
		echo '      <br /><span class="red italic">*'.$lang['cms_register_mandatory_field'].'</span></div>';
        echo '  </div>';
        
        // Error text
        if(!empty($error)){
            echo '  <div class="error_holder">';

            foreach($error as $error_key => $error_type){

                if($error_key == 'email' && $email == '')
                    echo $lang['cms_error_missing_email'].'<br />';
                elseif($error_key == 'email' && $_GET['existing_email'] == '1')
                    echo $lang['srv_added_false'].'<br />';
                elseif($error_key == 'email')
                    echo $lang['cms_error_wrong_email'].'<br />';
                    
                if($error_key == 'ime')
                    echo $lang['cms_error_user_field_empty'].'<br />';

                if($error_key == 'password' && $_GET['pass_complex'] == '1')
                    echo $lang['password_err_complex'].'<br />'; 
                
                if($error_key == 'password' && $_GET['pass_mismatch'] == '1')
                    echo $lang['ent_pass_doesnt_match'].'<br />'; 

                if($error_key == 'agree')
                    echo $lang['MustAgree'].'<br />'; 
            }

            echo '  </div>';
        }
        
        // Submit
		echo '  <input name="submit" value="'.$lang['next1'].'" class="regfield" type="submit">';
		
		echo '</form>';
    }
	
	
	// Izris strani za prijavo
	private function displayLoginPage(){
		global $lang;
		global $site_url;
				
		echo '<div class="login_holder">';					
					
		// Prijava
		echo '		<h1>'.$lang['login_for_existing2'].'</h1>';		
		echo '		<form name="login_2" id="login_2" action="'.$site_url.'frontend/api/api.php?action=login" method="post">';
		
		// Ce je email v getu pomeni da se je zmotil pri passwordu
		if(isset($_GET['email']) && $_GET['email'] != ''){

            $email = $_GET['email'];
            echo $lang['hello'].' <span class="bold">'.$email.'</span>';
			
			echo '			<input id="em" name="email" value="'.$email.'" type="hidden"><br /><br />';
			
			// Warning za napacno geslo
			echo '<p><span class="red italic">'.$lang['wrong_password2'].'</span></p>';
		}
		else{
            echo '          <div class="form_row"><span class="label"><label for="email">'.$lang['email'].':</label></span>';
            echo '		        <input class="regfield" id="em" name="email" value="" placeholder="'.$lang['login_email'].'" type="text">';
            echo '          </div>';
		}		
        
        // Geslo
        echo '          <div class="form_row"><span class="label"><label for="pass">'.$lang['password'].':</label></span>';
        echo '		        <input class="regfield" name="pass" value="" placeholder="'.$lang['password'].'" type="password">';
        echo '          </div>';
        
        // Pozabljeno geslo
        echo '          <div class="form_row">';
		echo '			    <span class="lostpass"><a class="RegLastPage" href="#" onclick="LostPassword(\''.$lang['please_insert_email'].'\');">'.$lang['forgot_password'].'</a></span>';
        echo '          </div>';

        // Zapomni si me
        echo '          <div class="form_row">';
		echo '			    <input name="remember" id="remember_me" value="1" type="checkbox"> <label for="remember_me">'.$lang['remember_me2'].'</label>';
        echo '          </div>';

        echo '			<input name="submit" value="'.$lang['login'].'" class="regfield" type="submit">';
        echo '			<span class="spaceLeft">'.str_replace('#URL#', $site_url.$this->root.'index.php?a=register', $lang['cms_login_registration_link']).'</span>';
        
		echo '		</form>';
		
		echo '</div>';
	}

    // Izris strani za prijavo
    private function displayLogin2faPage(){
        global $lang;
        global $site_url;



        echo '<div class="login_holder">';

        // Prijava
        echo '	<div class="login_element login">';
        echo '		<h1>'.$lang['google_2fa'].'</h1>';
        echo '		<form name="login_2" id="login_2" action="'.$site_url.'frontend/api/api.php?action=login_2fa" method="post">';

        if(!empty($_GET['error'])){
            echo '<p class="red">'.$lang['google_2fa_user_error_code'].'</p>';
        }

        // Ce je email v getu pomeni da se je zmotil pri passwordu
        echo '			<input class="regfield '.(!empty($_GET['error']) ? 'red' : '').'" style="margin:5px 0 5px 13px;" name="google_2fa_number" placeholder="'.$lang['google_2fa_user_code'].'" type="text">';

        echo '			<br />';
        echo '			<br />';

        echo '			<input name="submit" value="'.$lang['google_2fa_user_send'].'" class="regfield" type="submit">';
        echo '		</form>';
        echo '	</div>';

        echo '</div>';
    }
	
	// Izris strani z opozorilom da mail za login ne obstaja
	private function displayLoginNoEmailPage(){
		global $lang;
		global $site_url;
		
		$email = (isset($_GET['email'])) ? $_GET['email'] : '';
		
		echo '<div class="login_holder">';
		echo '	<h1>'.$lang['error'].'</h1>';	
		echo '	<p>'.$lang['e_login_invalid'].'</p>';	
		echo '	<p><span class="bold">'.$email.'</span></p>';	
		
		echo '	<input onclick="location.href=\''.$site_url.$this->root.'index.php\'" name="retry" value="'.$lang['e_login_retry'].'" type="button">';
		echo '	<input onclick="location.href=\''.$site_url.$this->root.'index.php?a=register\'" name="register" style="margin-left:10px;" value="'.$lang['e_login_register'].'" type="button">';
		echo '</div>';
	}
	
	
	// Izris strani za registracijo
	private function displayRegisterPage(){
		global $lang;
		global $site_url;
		
		// Pogledamo ce imamo kaksen error v GET-u
		$error = false;
		if(isset($_GET['missing_email']) || isset($_GET['invalid_email']) || isset($_GET['existing_email']) 
			|| isset($_GET['missing_ime']) || isset($_GET['existing_ime']) 
			|| isset($_GET['pass_mismatch'])
			|| isset($_GET['pass_complex'])
			|| isset($_GET['invalid_recaptcha'])
			|| isset($_GET['missing_agree'])){
				
			$error = true;
		}
			
		// Pogledamo ce imamo poslane podatke preko GET-a
		$email = (isset($_GET['email'])) ? $_GET['email'] : '';
		$ime = (isset($_GET['ime'])) ? $_GET['ime'] : '';
		
		
		echo '<div class="register_holder">';	
		
		if($error)
			echo '		<h1>'.$lang['e_nu_could_not'].'</h1>';
		else
			echo '		<h1>'.$lang['register_new_user'].'</h1>';
		
		echo '		<span class="subtitle">'.$lang['cms_register_user_text'].'</span>';
		
		echo '		<form name="register" id="register" action="'.$site_url.'frontend/api/api.php?action=register" method="post">';
		
		// Email
		echo '			<div class="form_row '.(isset($_GET['missing_email']) || isset($_GET['invalid_email']) || isset($_GET['existing_email']) ? ' red' : '').'"><span class="label"><label for="email">'.$lang['email'].':</label></span>';
		echo '			<input class="regfield" id="email" name="email" value="'.$email.'" placeholder="'.$lang['email'].'" type="text"></div>';
		
		// Ime
		echo '			<div class="form_row '.(isset($_GET['missing_ime']) || isset($_GET['existing_ime']) ? ' red' : '').'"><span class="label"><label for="ime">'.$lang['cms_register_user_nickname'].':</label></span>';
		echo '			<input class="regfield" id="ime" name="ime" value="'.$ime.'" placeholder="'.$lang['cms_register_user_nickname'].'" type="text"></div>';
				
		// RECAPTCHA
		if(AppSettings::getInstance()->getSetting('google-secret_captcha') !== false && AppSettings::getInstance()->getSetting('google-recaptcha_sitekey') !== false)
			echo '<div class="g-recaptcha" data-sitekey="'.AppSettings::getInstance()->getSetting('google-recaptcha_sitekey').'" '.(isset($_GET['invalid_recaptcha']) ? ' style="border:1px red solid"' : '').'></div>';	
		
		// Geslo
        echo '			<div class="form_row '.(isset($_GET['pass_mismatch']) || isset($_GET['pass_complex']) ? ' red' : '').'"><span class="label"><label for="geslo">'.$lang['login_password'].':</label></span>';
        echo '              <input id="p1" class="text " value="" name="geslo" placeholder="'.$lang['password'].'" type="password">';
        echo '          </div>';

        // Geslo 2 
        echo '			<div class="form_row '.(isset($_GET['pass_mismatch']) || isset($_GET['pass_complex']) ? ' red' : '').'"><span class="label"><label for="geslo2">'.$lang['cms_register_user_repeat_password'].':</label></span>';
		echo '			    <input id="p2" class="text " value="" name="geslo2" placeholder="'.$lang['cms_register_user_repeat_password'].'" type="password">';
        echo '          </div>';

        if(isset($_GET['pass_complex']))
        echo '			<span class="red italic">'.$lang['password_err_complex'].'</span><br /><br />';

        // Strinjam se s pogoji
        //echo '				<input id="IAgree" type="hidden" name="agree" value="1">';
		$terms_url = ($lang['id'] == '1') ? 'https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka' : 'https://www.1ka.si/d/en/about/terms-of-use';
		echo '			<div class="form_row agreement '.(isset($_GET['missing_agree']) ? ' red' : '').'">';
		echo '				<input id="IAgree" type="checkbox" name="agree" value="1"><label for="IAgree">';
		echo 				'<div class="checkbox_text_holder"> '.str_replace('TERMS_URL', $terms_url, $lang['cms_register_user_terms_of_use']).'</label>';
		echo '				<br /><span class="red italic">*'.$lang['cms_register_mandatory_field'].'</span></div>';
		echo '			</div>';
		
		// Strinjam se s posiljanjem mailov (gdpr)
		/*echo '			<div class="form_row gdpr-agree">';
		echo '				<input id="gdpr-agree" type="checkbox" name="gdpr-agree" value="1">';
		echo '				<div class="checkbox_text_holder"><label for="gdpr-agree"> '.$lang['cms_register_gdpr_agree'].'</label><br /><span class="as_link bold clr" onClick="$(\'#checkbox_explain_text_holder\').toggle(); return false;">'.$lang['more2'].' >></span></div>';
		echo '				<div id="checkbox_explain_text_holder" class="checkbox_explain_text_holder"> '.$lang['cms_register_gdpr_agree_explain'].'</div>';
		echo '			</div>';*/
		
		echo '			<input name="submit" value="'.$lang['next1'].'" class="regfield" type="submit">';
		echo '			<span class="have_account spaceLeft">'.str_replace('#URL#', $site_url.$this->root.'index.php?a=login', $lang['cms_register_login_link']).'</span>';
		
		echo '		</form>';
			
		echo '</div>';
	}
	
	// Izris strani za registracijo - po vnosu podatkov
	private function displayRegisterPageConfirm(){
		global $lang;
		global $site_url;
		
		$email = (isset($_POST['email'])) ? $_POST['email'] : '';
		$ime = (isset($_POST['ime'])) ? $_POST['ime'] : '';
		$geslo = (isset($_POST['geslo'])) ? $_POST['geslo'] : '';	
		$gdpr_agree = (isset($_POST['gdpr-agree'])) ? $_POST['gdpr-agree'] : '0';	

		echo '<div class="register_holder">';	
		
		echo '	<h1>'.$lang['register_new_user'].'</h1>';
		
		echo '	<div class="confirm_text">'.$lang['check_login_data'].'</div>';
		
		
		echo '		<form name="register" id="register" action="'.$site_url.'frontend/api/api.php?action=register_confirm" method="post">';
		
		// Hidden polja potrebna za registracijo	
		echo '			<input name="email" value="'.$email.'" type="hidden">';
		echo '			<input name="ime" value="'.$ime.'" type="hidden">';
		echo '			<input name="geslo" value="'.$geslo.'" type="hidden">';
		echo '			<input name="geslo2" value="'.$geslo.'" type="hidden">';
		echo '			<input name="gdpr-agree" value="'.$gdpr_agree.'" type="hidden">';
		echo '			<input name="language" value="'.$lang['id'].'" type="hidden">';
		
		// Url za nazaj na urejanje vnesenih podatkov
		$url_edit = $site_url.$this->root.'index.php?a=register&email='.$email.'&ime='.$ime;
		
		// Email
		echo '			<p><span class="label edit"><label for="email">'.$lang['login_email'].':</label></span>';
		echo '			<a href="'.$url_edit.'">'.$email.'</a></p>';			
		// Ime
		echo '			<p><span class="label edit"><label for="ime">'.$lang['login_name'].':</label></span>';
		echo '			<a href="'.$url_edit.'">'.$ime.'</a></p>';
		// Geslo
		echo '			<p><span class="label edit"><label for="pass">'.$lang['password'].':</label></span>';
		echo '			<a href="'.$url_edit.'">'.($geslo == '' ? $lang['no1'] : $lang['yes']).'</a></p>';
		
		echo '			<br /><input name="submit" value="'.$lang['next1'].'" class="regfield" type="submit"><br />';
		
		echo '		</form>';
		
		echo '</div>';
	}
	
	// Izris strani za registracijo - po poslanem potrditvenem mailu
	private function displayRegisterPageEmail(){
		global $lang;
		global $site_url;
		global $site_url;
		
		// Ce nimamo poslanega emaila preusmerimo nazaj na prvo stran registracije
		if(empty($_GET['e'])){
			header ('location: '.$site_url.$this->root.'index.php?a=register');
			die();
		}
		else{
			$email = base64_decode(urldecode($_GET['e']));
		}
		
		echo '<div class="register_holder">';
		
		echo '	<h1>'.$lang['user_confirm_h'].'</h1>';
		
		// Ce iammo vklopljeno potrjevanje s strani admina je text drugacen
		if (AppSettings::getInstance()->getSetting('confirm_registration') === true)
			echo '	<p>'.str_replace("SFMAIL", $email, $lang['user_confirm_p_admin']).'</p>';	
		else
			echo '	<p>'.str_replace("SFMAIL", $email, $lang['user_confirm_p']).'</p>';	
        
        // Gumb nazaj na naslovnico
        echo '  <br />';
        echo '  <a href="'.$site_url.$this->root.'index.php?a=register"><input type="button" value="'.$lang['install_finish_redirect'].'"></a>';

		echo '</div>';
	}

	
	// Izris strani za odregistracijo
	private function displayUnregisterPage(){
		global $lang;
		global $site_url;

		if (isset($_GET['email'])){
			$email = strtolower($_GET['email']);
		}
		else{
			header ('location: '.$site_url.$this->root.'index.php');
			die();
		}				
				
		echo '<div class="register_holder">';		

		echo '	<p>'.$lang['unregister_confirm'].'</p>';
		echo '	<br /><br />';
		echo '	<a href="'.$site_url.$this->root.'index.php">'.$lang['no1'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '	<a href="'.$site_url.'frontend/api/api.php?action=unregister_confirm&email='.$email.'">'.$lang['yes'].'</a>';
						
		echo '</div>';
	}
	
	// Izris strani po potrditvi odregistracije
	private function displayUnregisterPageConfirm(){
		global $lang;
		global $site_url;

		echo '<div class="register_holder">';	
		
		echo '	<h1>'.$lang['unregister_ok'].'</h1>';
		echo '	<p>'.$lang['unregister_ok_text'].'</p>';
		echo '	<p><a href="'.$site_url.'">'.$lang['e_back_to_fp'].'</a></p>';
		
		echo '</div>';
	}


	// Izris strani za reset gesla
	private function displayResetPasswordPage(){
		global $lang;
		global $site_url;

		if (isset($_GET['email'])){
			$email = strtolower($_GET['email']);
		}
		else{
			header ('location: '.$site_url.$this->root.'index.php');
			die();
		}				
				
        echo '<div class="register_holder">';		
        
        echo '	<h1>'.$lang['forgotten_password'].'</h1>';

		echo '  <p>'.$lang['lp_sent'].' <span class="semibold">'.$email.'</span>.</p>';
        echo '  <p><input onclick="document.location.href=\''.$site_url.'\'" value="'.$lang['back'].'" type="button"></p>';	
        
		echo '</div>';	
	}
	
	// Izris strani po aktivaciji resetiranega gesla
	private function displayResetPasswordPageActivate(){
		global $lang;
		global $site_url;
		
		
		// Izpisemo da smo uspesno aktivirali novo geslo
		if(isset($_GET['success']) && $_GET['success'] == '1'){
			
			echo '<div class="register_holder">';		
			
            echo '<h1>'.$lang['forgotten_password'].'</h1>';
            
            echo '<p>'.$lang['lp_activate_activation'].' '.$lang['has_been_successful'].'</p>';  
			echo '<p>'.$lang['you_can_change_pass_anytime'].'</p>';
			echo '<p><a class="RegLastPage" href="'.$site_url.'">'.$lang['to_front'].'</a></p>';		
			
			echo '</div>';	
		}
		// Ce so vneseni napacni podatki za aktivacijo
		elseif(isset($_GET['error']) && $_GET['error'] == '1'){
			
			echo '<div class="register_holder">';
                
            echo '<h1>'.$lang['forgotten_password'].'</h1>';

			echo '<p><strong>' .$lang['lp_activate_error'] .'</strong></p>';

			echo '<form name="aktivacija" method="post" action="'.$site_url.'frontend/api/api.php?action=reset_password_activate">';
            echo '	<input type="hidden" name="code" value="<?=$code?>" />';
            
            echo '  <div class="form_row">';
            echo '      <div class="label"><label for="email">'.$lang['lp_activate_email'].'</label></div><input type="text" name="email" id="email" />';
            echo '  </div>';

            echo '  <div class="form_row">';
            echo '      <div class="label"><label for="pass">'.$lang['lp_activate_pass'].'</label></div><input type="text" name="pass" id="pass" />';
            echo '  </div>';

			echo '	<input type="submit" value="'.$lang['lp_activate_activate'].'" style="width: 220px;" />';
			echo '</form>';
			
			echo '</div>';
		}
		// Drugace izpisemo formo za vnos vseh podatkov (novo geslo, email)
		else{
			if (isset($_GET['code']) && $_GET['code'] != ""){
				$code = $_GET['code'];

				echo '<div class="register_holder">';
				
                echo '<h1>'.$lang['forgotten_password'].'</h1>';
                
				echo '<p>'.$lang['lp_activate_p'].'</p>';

				echo '<form name="aktivacija" method="post" action="'.$site_url.'frontend/api/api.php?action=reset_password_activate">';
                echo '	<input type="hidden" name="code" value="'.$code.'" />';
                
                echo '  <div class="form_row">';
                echo '      <div class="label"><label for="email">'.$lang['lp_activate_email'].'</label></div><input type="text" name="email" id="email" />';
                echo '  </div>';

                echo '  <div class="form_row">';
                echo '      <div class="label"><label for="pass">'.$lang['lp_activate_pass'].'</label></div><input type="text" name="pass" id="pass" />';
                echo '  </div>';

                echo '<br>';

                echo '	<input type="submit" value="'.$lang['lp_activate_activate'].'" style="width: 220px;" />';
				echo '</form>';
				
				echo '</div>';
			}
			else{
                echo '<div class="register_holder">';		
                echo '<h1>'.$lang['forgotten_password'].'</h1>';		
				echo '<p>'.$lang['srv_wrongcode'].'</p>';			
				echo '</div>';
			}
		}
	}


	// Izris strani za prosnjo za izbris (GDPR)
	private function displayGDPRPage(){
		global $lang;
		global $site_url;
		
		// Ce imamo kaksen error
		$error = array();	
		
		echo '<div id="gdpr_holder" class="register_holder gdpr">';	
		
		GDPR::displayGDPRRequestForm();
		
		echo '</div>';
	}


    // Cookie notice
    public function displayCookieNotice(){
        global $lang;
        global $cookie_domain;

        if(!isAAI()){
            return;
        }

        if(isset($_COOKIE['simple_frontend_cookie'])){
            return;
        }

        echo '<div class="cookie_notice">';

        echo '  <div class="left">';
        echo '    <p class="bold">'.$lang['simple_cookie_1'].'</p>';
        echo '    <p>'.$lang['simple_cookie_2'].'</p>';
        echo '  </div>';

        echo '  <div class="right">';
        echo '    <button onClick="cookie_confirm();">'.$lang['simple_cookie_button'].'</button>';
        echo '  </div>';

        echo '</div>';
    }

    // Confirm cookie
    public function cookieConfirm(){

        // Set cookie for 90 days
        setcookie("simple_frontend_cookie", "1", time() + (60*60*24*90), "/"); 
    }
}