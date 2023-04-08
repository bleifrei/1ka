<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include_once '../../function.php';

include_once 'classes/class.DisplayCheck.php';
include_once 'classes/class.DisplaySettings.php';
include_once 'classes/class.ImportDB.php';
include_once 'classes/class.DisplayDatabase.php';
	
	
class Display{

	var $stran;			// stran na kateri se nahajamo
	
	var $lang_id = 1;	// izbran jezik
		

	function __construct(){
		global $admin_type;		
		global $site_url;		
		global $lang;		
        global $global_user_id;
        
		
		if(isset($_GET['step']))
			$this->stran = $_GET['step'];
				
		
		// Nastavimo jezik
		if(isset($_GET['lang_id']))
			$this->lang_id = $_GET['lang_id'];
		elseif(isset($_SESSION['lang_id']))
			$this->lang_id = $_SESSION['lang_id'];
		
		$_SESSION['lang_id'] = $this->lang_id;
		
		$file = '../../lang/'.$this->lang_id.'.php';
		include($file);
	}
	
        
    public function displayHead(){
        global $lang;
        global $site_url;

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
            
        echo '    <title>'.$lang['install_title'].'</title>';

        echo '    <!-- CSS -->';
        echo '    <link type="text/css" href="css/style.css" rel="stylesheet" />';
        echo '    <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css" rel="stylesheet" /">';
            
        echo '    <!-- JAVASCRIPT -->';
        echo '    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>';
        echo '    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
        echo '    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js"></script>';
            
        echo '    <script type="text/javascript" src="script/init.js"></script>';
        echo '    <script type="text/javascript" src="script/script.js"></script>';
            
        echo '    <!-- FAVICON -->';
        echo '    <link rel="shortcut icon" type="image/ico" href="../../favicon.ico" />';
    }


    // Izris zgornje vrstice z logotipom in menijem
	public function displayHeader(){
		global $lang;
        
        // Logo v glavi
        echo '<div class="logo ">';

        echo '  <a href="index.php">';
        echo '      <img src="../../public/img/logo/1ka_'.($this->lang_id != 1 ? 'eng' : 'slo').'.svg">';
        echo '  </a>';

        echo '</div>';
        

        // Navigacija
        echo '<nav>';

        // Hidden polje z lang_id-jem
        echo '	<input type="hidden" name="lang_id" value="'.$this->lang_id.'" />';
                
        // Preklop jezika
        echo '<span class="menu_item lang_switch">';
        $params = '?' . (isset($_GET['step']) ? 'step='.$_GET['step'] : '');
		if($this->lang_id == 1){
			echo '	<a href="index.php'.$params.'&lang_id=2">';
			echo '		<div class="flag eng"></div> <span>English</span>';
			echo '	</a>';
		}
		else{
			echo '	<a href="index.php'.$params.'&lang_id=1">';
			echo '		<div class="flag slo"></div> <span>Slovenščina</span>';
			echo '</a>';
		}
        echo '</span>';
		
        echo '</nav>';
	}
	
	// Izris vsebine
	public function displayMain(){
        global $lang;

        echo '<div class="main_content '.$this->stran.'">';

        //echo '<h1>'.$lang['install_title'].'</h1>';

        switch($this->stran){	
            
            case 'welcome':
                $this->displayWelcomePage();
            break;

            case 'check':
                $dc = new DisplayCheck();
                $dc->displayCheckPage();
            break;

            case 'settings':
                $ds = new DisplaySettings();
                $ds->displaySettingsPage();
            break;

            case 'database':
                $dd = new DisplayDatabase();
                $dd->displayDatabasePage();
            break;

            case 'finish':
                $this->displayFinishPage();
            break;

            default:
                $this->displayWelcomePage();
            break;
        }

        echo '</div>';
    }

    // Izris footerja
    public function displayFooter(){
        global $lang;
        global $site_url;

        // Stolpec 1
        echo '<div class="col">';
        echo '  <h2>'.$lang['simple_footer_about'].'</h2>';
        echo '  <span>'.$lang['simple_footer_about_1ka'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_general'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_privacy'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_cookies'].'</span>';
        echo '  <span>'.$lang['simple_footer_about_antispam'].'</span>';
        echo '  <div class="follow">'.$lang['simple_footer_about_follow'].': ';
        echo '      <a href="https://twitter.com/enklikanketa" target="_blank"><span class="icon twitter"></span></a>';
        echo '      <a href="https://www.facebook.com/1KA-123545614388521/" target="_blank"><span class="icon fb"></span></a>';
        echo '  </div>';
        echo '</div>';
        
        // Stolpec 2 - logotipi
        echo '<div class="col">';

        // Logotipa FDV in CDI
        echo '<div class="logo_holder">';
        echo '  <img src="'.$site_url.'/public/img/logo/fdv.png">';
        echo '  <img src="'.$site_url.'/public/img/logo/cdi_'.($this->lang_id != 1 ? 'eng' : 'slo').'.png">';
        echo '</div>';

        echo '</div>';

        // Stolpec 3
        echo '<div class="col">';

        echo '</div>';
    }
	
	
	// Izris prve welcome
	private function displayWelcomePage(){
		global $lang;
        
        echo '<h2>'.$lang['install_welcome_title'].'</h2>';

        echo '<p>'.$lang['install_welcome_text'].'</p>';

        // Next button
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=check"><input type="button" value="'.$lang['next1'].'"></a>';
        echo '</div>';
    }
    
    // Izris strani za preverjanje konfiguracije streznika, baze
	private function displayFinishPage(){
		global $lang;
        
        echo '<h2>'.$lang['install_finish_title'].'</h2>';
        
        echo '<p>'.$lang['install_finish_text'].'</p>';

        // Redirect na naslovnico
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=database"><input name="back" value="'.$lang['back'].'" type="button"></a>';
        echo '  <a href="/index.php"><input type="button" value="'.$lang['install_finish_redirect'].'"></a>';
        echo '</div>';
    }
}