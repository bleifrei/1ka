<?php
/**
 *
 * Tipi spremenljivk: (srv_spremenljivka -> tip)
 *    radio            	-> tip = 1
 *    checkbox        	-> tip = 2
 *    select            -> tip = 3
 *    text            	-> tip = 4		// ni vec v uporabi
 *    besedilo*        	-> tip = 21
 *    label            	-> tip = 5
 *    multigrid        	-> tip = 6
 *    multicheckbox   	-> tip = 16
 *    multitext        	-> tip = 19
 *    multinumber       -> tip = 20
 *    number            -> tip = 7
 *    compute           -> tip = 22		// samo v naprednejših anketah (ifi ali test anketa)
 *    quota            	-> tip = 25		// samo v naprednejših anketah (ifi ali test anketa)
 *    datum            	-> tip = 8
 *    ranking           -> tip = 17
 *    vsota            	-> tip = 18
 *    grid - multiple   -> tip = 24
 *    iz knjiznice    	-> tip = 23 	// podtip nam pove za tip vprasanja, ki ga poiscemo glede na variablo
 *    SN-imena        	-> tip = 9
 *    Lokacija    		-> tip = 26
 *    HeatMap    		-> tip = 27
 *
 *
 * Tipi anket: (srv_anketa -> survey_type)
 *  Glasovanja               -> survey_type = 0
 *  Forma                    -> survey_type = 1
 *  Navadna anketa  		 -> survey_type = 2 || survey_type = 3 (oboje enako - ostanek starih verzij)
 *
 *
 * Moduli anket: (srv_anketa_module)
 *  email (email vabila)
 *  phone (telefonska anketa)
 *  slideshow (prezentacija)
 *  social_network (socialna omrežja - generator imen)
 *  quiz (kviz s pravilnimi/napacnimi odgovori)
 *  voting (volitve z anonimnimi vabili)
 *  uporabnost (evalvacija strani - split screen)
 *  panel (povezovanje ankete s panelom - npr. Valicon, GFK...)
 *  360_stopinj (adecco)
 *  360_stopinj_1ka
 *  evoli
 *  evoli team meter
 *  evoli employeeship meter
 *  hierarhija
 *  mfdps
 *  borza
 *  mju
 *  excelleration matrix
 *  advanced paradata (zbiranje in izvoz naprednih parapodatkov)
 *  maza (mobilna aplikacija za anketirance - 1kapanel)
 *  wpn (web push notifications)
 **/

/**
 * KONSTANTE
 *
 */


// STARO
define("A_IZVOZI", "izvozi");

define("M_IZVOZI_EXCEL", "excel");
define("M_IZVOZI_SPSS", "spss");
define("M_IZVOZI_txt", "txt");

define("A_REPORT_VPRASALNIK_PDF", "vprasalnik_pdf");
define("A_REPORT_VPRASALNIK_RTF", "vprasalnik_rtf");

define("M_REPORT_TEXT", "text");
define("M_REPORT_GRAPHICAL", "graphical");
define("M_REPORT_TOTAL", "total");


// ali je enka še v fazi razvoja (za potrebe skrivanja navigacije,zavihkov,ipd...)
// skrite elemente prikaže samo administratorju
define("SRV_DEVELOPMENT_VERSION", true);

// tipi uporabnikov, (za kontrolo prikaza posameznih elementov) za preverjanje kličemo funkcijo user_role_cehck
define("U_ROLE_ADMIN", 0);
define("U_ROLE_MANAGER", 1);
define("U_ROLE_CLAN", 2);
define("U_ROLE_NAROCNIK", 3);

global $site_path;

class SurveyAdmin
{

    var $anketa; // trenutna anketa
    var $grupa; // trenutna grupa
    var $spremenljivka; // trenutna spremenljivka
    var $branching = 0; // pove, ce smo v branchingu
    var $stran;
    var $podstran;
    var $skin = 0;
    var $survey_type; // privzet tip je anketa na vecih straneh

    var $displayLinkIcons = false; // zaradi nenehnih sprememb je trenutno na false, se kasneje lahko doda v nastavitve
    var $displayLinkText = true; // zaradi nenehnih sprememb je trenutno na true, se kasneje lahko doda v nastavitve
    var $setting = null;

    var $db_table = '';

    var $icons_always_on = false;    # ali ima uporabnik nastavljeno da so ikone vedno vidne
    var $full_screen_edit = false;    # ali ima uporabnik nastavljeno da ureja vprašanja v fullscreen načinu
    var $isAnketar = false;            # Ali je uporabnik anketar ankete privzeto je ne

    /**
     * @desc konstruktor
     */
    function __construct($action = 0, $anketa = 0)
    {
        global $surveySkin, $site_url, $global_user_id;

        if (isset ($surveySkin))
            $this->skin = $surveySkin;
        else
            $this->skin = 0;

        // polovimo anketa ID
        if ($anketa != 0)
            $this->anketa = $anketa;
        elseif (isset ($_GET['anketa']))
            $this->anketa = $_GET['anketa'];
        elseif (isset ($_POST['anketa']))
            $this->anketa = $_POST['anketa'];

        # clearing E_NOTICE
        if (!isset($_GET['a'])) {
            $_GET['a'] = null;
        }
        if (!isset($_GET['m'])) {
            $_GET['m'] = null;
        }
        if (!isset($_GET['t'])) {
            $_GET['t'] = null;
        }
        if (!isset($_GET['mode'])) {
            $_GET['mode'] = null;
        }

        UserSetting:: getInstance()->Init($global_user_id);
        $this->icons_always_on = UserSetting:: getInstance()->getUserSetting('icons_always_on');
        $this->full_screen_edit = UserSetting:: getInstance()->getUserSetting('full_screen_edit');


        $this->isAnketar = Common::isUserAnketar($this->anketa, $global_user_id);

        SurveyInfo::getInstance()->SurveyInit($this->anketa);

        if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
            $this->db_table = '_active';

        $this->survey_type = $this->getSurvey_type($this->anketa);

        if ($_GET['a'] == 'branching' || $this->survey_type > 1)
            $this->branching = 1;

        if ($this->anketa > 0) {
			
            // preverimo ali anketa sploh obstaja
            if (!$this->checkAnketaExist()) {
                header('location: ' . $site_url . 'admin/survey/index.php');
            } 
			else {
                // preverimo userjev dostop
                //if ($this->checkDostop() || $this->checkDostopAktiven() || $_GET['a'] == A_ANALYSIS || $_GET['a'] == 'analiza' || $_GET['a'] == 'analizaReloadData' || $_GET['t'] == A_ANALYSIS || $_GET['a'] == A_REPORTI) {
                if ($this->checkDostop() && ($this->isAnketar || $this->checkDostopAktiven() || $_GET['a']==A_ANALYSIS || $_GET['a']=='analiza' || $_GET['a']=='analizaReloadData' || $_GET['t']==A_ANALYSIS || $_GET['a']==A_REPORTI)) {

                    // pasivne uporabnike preusmerimo na status tudi pri neaktivni anketi
                    if ($this->checkDostop() && !$this->checkDostopAktiven() && !isset($_GET['a'])) {
                        header('location: ' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&a=' . A_REPORTI);
                        die();
                    } 
					// ugotovimo ali je uporabnik telefonski anketar
                    else if ($this->isAnketar && $_GET['a'] != A_TELEPHONE) {
                        #če je anketar lahko samo kliče
                        header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_TELEPHONE . '&m=start_call');
                        exit();
                    }

                    // ok
                } 
				else {
					// pri ajax klicih ne sme naprej, da ne more pisat v bazo
                    header('location: ' . $site_url . 'admin/survey/');
                    die();
                }
            }
        }

        if ($action == 0) {
            if (isset ($_GET['anketa'])) {

                SurveyInfo:: getInstance()->SurveyInit($this->anketa);

                if (isset ($_GET['grupa'])) {
                    $this->grupa = $_GET['grupa'];
                } 
                elseif (!isset ($_GET['a'])) {
                    $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' ORDER BY vrstni_red LIMIT 1");
                    $row = mysqli_fetch_array($sql);
                    $this->grupa = $row['id'];
                }

                // meta podatki, ki jih beremo z JS
                echo '<form name="meta" action="" style="display:none">';

                echo '<input type="hidden" name="anketa" id="srv_meta_anketa_id" value="' . $this->anketa . '" />';
                echo '<input type="hidden" name="srv_site_url" id="srv_site_url" value="' . $site_url . '" />';
                echo '<input type="hidden" name="grupa"  id="srv_meta_grupa"  value="' . $this->grupa . '" />';
                echo '<input type="hidden" name="branching" id="srv_meta_branching" value="' . $this->branching . '" />';
                echo '<input type="hidden" name="podstran" id="srv_meta_podstran" value="' . $_GET['m'] . '" />';
                echo '<input type="hidden" name="akcija" id="srv_meta_akcija" value="' . $_GET['a'] . '" />';
                echo '<input type="hidden" name="full_screen_edit" id="srv_meta_full_screen_edit" value="' . ($this->full_screen_edit == 1 ? 1 : 0) . '" />';
                echo '<input type="hidden" name="editing_mode" id="editing_mode" value="1" />';

                // Ce imamo vklopljene komercialne pakete
                global $app_settings;
                if($app_settings['commercial_packages']){
                    $userAccess = UserAccess::getInstance($global_user_id);

                    // Ce gre za staro anketo nimamo omejitev
                    if($userAccess->isAnketaOld()){
                        $commercial_package = '-1';
                    }
                    else{
                        $commercial_package = $userAccess->getPackage();
                    }
                }
                else{
                    $commercial_package = '-1';
                }
                echo '<input type="hidden" name="commercial_package" id="commercial_package" value="'.$commercial_package.'" />';

                echo '</form>';

                ?>
                <script> var srv_site_url = '<?=$site_url?>'; </script><?
            }

            
        } 
        // tole je, ce se inicializira v branhingu z $action=-1 (pa mogoce/najbrz se kje), da se ne prikazujejo 2x te meta podatki in redirecta...
        else {
            if ($this->anketa == 0) 
                die();
        }

        $this->stran = $_GET['a'];
    }


    /**
     * @desc pohendla zadeve in prikaze ustrezne elemente ankete
     */
    function display(){
        global $site_url;
        global $global_user_id;
        global $lang;
        global $admin_type;
        global $site_domain;
        global $aai_instalacija;


        echo '<div id="main_holder">';


        /********************* GLAVA *********************/
        echo '<header>';

        // DESKTOP HEADER
        echo '<div class="desktop_header">';

        // Nastavitve zgoraj desno v headerju (search, help, profil...)
        $this->displayHeaderRight();

        // logotip
        $this->displayHeaderLogo();

        // Znotraj posamezne ankete
        if($this->anketa > 0){

            // Utripajoc napis "Demo anketa"
            $this->displayHeaderDemoSurvey();

            // Prikaze podatke o anketi in navigacijo - na vrhu (top bar)
            $this->displayHeaderAnketa();
        }
        // Seznam anket
        else{
            $this->displayHeaderSeznamAnket();
        }

        echo '</div>';

        // MOBILE HEADER
        echo '<div class="mobile_header">';

        $mobile_admin = new MobileSurveyAdmin($this);
        $mobile_admin->displayHeaderMobile();

        echo '</div>';

        echo '</header>';
        /********************* GLAVA - END *********************/


        /********************* MAIN *********************/
        echo '<div id="main">';

        // SEZNAM ANKET - Ce ni nastavljene ankete, potem prikazujemo seznam na prvi strani *****/ 
        if (!($this->anketa > 0)) {
            $this->displaySeznamAnket();
        }
        // ZNOTRAJ ANKETE
        else{
            echo '<div id="anketa">';  

            echo '<div id="anketa_edit" class="page_'.$_GET['a'].' subpage_'.$_GET['m'].' '.($this->survey_type == '1' ? 'forma' : '').' '.($this->survey_type == '0' ? 'glasovanje' : '').'">';
            $this->displayAnketa();
            echo '</div>';

            echo '</div>';
        }

        /***** SKRITI DIVI ZA POPUPE *****/ 
        $this->displayHiddenPopups();

        echo '</div>';
        /********************* MAIN - END *********************/
        

        /********************* FOOTER *********************/
        $this->displayFooter();
        /********************* FOOTER - END *********************/


        echo '</div> <!-- /main_holder -->';
    }


    // Prikazemo skrite dive za popupe
    private function displayHiddenPopups(){
        global $lang;


        // Predpregled tipa vprašanj - prikazujemo samo kadar smo v urejanju ankete
        $this->getTipPreviewHtml();


        // Loading ikona
        echo '  <div id="loading">';
        echo '      <span class="faicon spinner fa-spin spaceRight"></span> '.$lang['srv_saving'];
        echo '  </div> <!-- /loading -->';

        echo '  <div id="clipboard">';
        $this->clipboard_display();
        echo '  </div> <!-- /clipboard -->';

        
        echo '<div id="teststatus"></div>';


        // fade pri fullscreen urejanje spremenljivke
        echo '<div id="fade">';
        echo '<div class="popup_holder">';

        // div za setiranje trajanja ankete ob aktiviranju
        echo '<div id="surveyTrajanje" class="divPopUp">';
        echo '  <div id="surveyTrajanje_msg">&nbsp;</div>';
        echo '</div> <!-- /surveyTrajanje -->';

        // urejanje pogojev -- v tem pogledu se uporabi za urejanje vrednosti v editorju
        echo '<div id="div_condition_editing" class="divPopUp"></div>';

        echo '<div id="div_float_editing" class="divPopUp"></div>';

        // za dodajanje IFov v normalnem pogledu -- da se zapise sm not, kar se pac zapise - drugac ne dela naprej
        echo '<div id="branching" style="display:none"></div>';

        // fullscreen urejanje spremenljivke
        echo '<div id="fullscreen"  class="'.($_GET['a'] == A_ANALYSIS ? ' analiza' : '').'"></div>';

        // fullscreen urejanje vprasanja
        echo '<div id="vprasanje" class="divPopUp"></div>';

        // popup za urejanje vrednosti
        echo '<div id="vrednost_edit" class="divPopUp"></div>';


        // Generičen popup
        echo '<div id="popup_note" class="divPopUp"></div>';

        // urejanje calculation-ov
        echo '<div id="calculation" class="divPopUp"></div>';

        // urejanje kvote
        echo '<div id="quota" class="divPopUp"></div>';

        echo '<div id="alert_close_block" class="divPopUp"></div>';

        echo '<div id="div_status_values" class="divPopUp"></div>';

        // div za prikaz neprebranih sporocil
        echo '<div id="unread_notifications" class="divPopUp"></div>';
        
        // div za uvoz vprasanj iz texta
        echo '<div id="popup_import_from_text" class="divPopUp"></div>';

        // div za opozorilo, da funkcionalnost ni na voljo v paketu
        echo '<div id="popup_user_access" class="divPopUp"></div>';

        // alert za paste from Word
        echo '<div id="pasteFromWordAlert" class="divPopUp">';
        echo $lang['pasteFromWordAlert'];
        echo '</div>';


        // Alert
        echo '<div id="dropped_alert" class="divPopUp"></div>';

        // Preverjanje pravilnosti pogojev
        echo '<div id="check_pogoji" class="divPopUp"></div>';


        // ANALIZE
        # skrit div za izbor profilov nastavitev
        echo '<div id="dsp_div" class="divPopUp"></div>';

        # skrit div za izbor profilov nastavitev
        echo '<div id="zoom_div" class="divPopUp"></div>';

        # skrit div za izbor profilov nastavitev
        echo '<div id="inspect_div" class="divPopUp"></div>';

        # skrit div za izbor profilov zank
        echo '<div id="div_zanka_profiles" class="divPopUp"></div>';

        # skrit div za izbor if-profilov
        echo '<div id="div_condition_profiles" class="divPopUp"></div>';

        # skrit div za izbor manjkajočih vrednosti
        echo '<div id="div_missing_profiles" class="divPopUp"></div>';

        # skrit div za izbor profilov intervala
        echo '<div id="div_time_profiles" class="divPopUp"></div>';

        # skrit div za izbor skina grafov
        echo '<div id="div_chart_settings_profiles" class="divPopUp"></div>';

        //div za float edit grafov
        echo '<div id="chart_float_editing" class="divPopUp"></div>';

        //div za opozorilo pri vkljucevanju v report
        echo '<div id="custom_report_alert" class="divPopUp"></div>';

        // Skriti divi za profile
        echo '<div id="div_creport_settings_profiles" class="divPopUp"></div>';

        // Creport
        echo '<div id="div_mc_tables" class="divPopUp"></div>';



        // REKODIRANJE
        echo '<div id="question_recode" class="divPopUp"></div>';
        echo '<div id="question_recode_run_note" class="divPopUp"></div>';


        // NAROCILA in PLACILA
        echo '<div id="user_narocila_popup" class="user_narocila_popup divPopUp"></div>';
        echo '<div id="user_placila_popup" class="user_placila_popup divPopUp"></div>';
        

        echo '</div>';
        echo '</div>';
    }
    

    // Prikazemo podatke zgoraj desno v glavi (search, user, help)
    private function displayHeaderRight(){
        global $site_url;
        global $global_user_id;
        global $lang;


        // user navigacija
        echo '<div id="enka_nav">';
                

        // Gumb za nadgraditev paketa v mojih anketah (ce imamo vklopljene pakete in nimamo 3ka paketa)
        if($this->anketa == 0){
  
            global $app_settings;
            if($app_settings['commercial_packages'] == true){

                // Preverimo trenuten paket uporabnika
                $userAccess = UserAccess::getInstance($global_user_id);
                $current_package = $userAccess->getPackage();
                if($current_package != '3' && !$userAccess->userNotAuthor()){
                    
                    $drupal_url = ($lang['id'] == '2') ? $site_url.'d/en/' : $site_url.'d/';
                    $upgrade_url = $drupal_url.'izvedi-nakup/3/podatki';

                    $button_text = ($current_package == '2') ? $lang['srv_access_upgrade2'] : $lang['srv_access_upgrade'];

                    echo '<div class="upgrade_package">';
                    echo '<div class="buttonwrapper"><a class="ovalbutton ovalbutton_purple" href="'.$upgrade_url.'" target="_blank"><span>'.$button_text.'</span></a></div>';
                    echo '</div>';
                }
            }
        }


        // Search po zunanji lupini - preusmeri na drupalov search
        echo '<div id="search_holder">';

        if($lang['id'] != "1")
            $drupal_search_url = 'https://www.1ka.si/d/en/iskanje/';
        else
            $drupal_search_url = 'https://www.1ka.si/d/sl/iskanje/';
            
        echo '<form method="GET" id="1kasf" action="'.$drupal_search_url.'">';

        echo '<input type="hidden" id="drupal_search_url" name="drupal_search_url" value="'.$drupal_search_url.'" />';

        echo '<a href="#" onclick="showSearch();"><span class="faicon search pointer"></span></a> ';
        echo '<input id="searchSurvey" type="text" value="" placeholder="' . $lang['s_search_frontend'] . '" name="search" />';
        echo '<input type="button" style="display: none;" value="' . $lang['s_search'] . '" />';

        echo '</form>';

        echo '</div>';


        // Hitra pomoč - povezave na linke s pomočjo na www.1ka.si
        $subdomain = ($lang['id'] == "1") ? 'www' : 'english';
        $help_url = Common::getHelpUrl($subdomain, $this->first_action);
        echo '<div id="help_holder">';
        echo ' <a href="' . $help_url . '" title="' . $lang['srv_settings_help'] . '" target="_blank">';
        echo '<span class="faicon help2"></span>';
        echo '</a> ';
        echo '</div>';


        // povezava na fieldwork sync
        if ($this->anketa > 0) {

            // poglej če je tale ID ankete v srv_fieldwork
            $sql = sisplet_query("SELECT id FROM srv_fieldwork where sid_server='" . $this->anketa . "'");
            if (mysqli_num_rows($sql) > 0) {
                // nariši link.
                echo '<div id="fieldwork_holder">';
                
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_FIELDWORK . '" title="' . $lang['srv_vrsta_survey_type_13'] . '">';
                echo '<span class="sprites fieldwork"></span>';
                echo '</a> ';

                echo '</div>';
            }
        }


        // User profil
        $sql = $this->db_select_user($global_user_id);
        $row = mysqli_fetch_array($sql);

        $text = $row['name'] . ' ' . $row['surname'];
        $text = (strlen($text) > 25) ? substr($text, 0, 25) . '...' : $text;

        echo '<div id="xtradiv"><strong class="xtraname">'.$text.' <span class="faicon after sort_down_arrow"/></strong>';
        echo '<div id="xtradivSettings">';

        echo '<span class="xtraSetting"><a class="xtra" href="' . $site_url . 'admin/survey/index.php?a=nastavitve&m=global_user_myProfile"><span class="faicon user"></span>' . $lang['edit_data'] . '</a></span>';

        // Odjava na nov nacin preko frontend/api
        echo '<form name="odjava" id="form_odjava_desktop" method="post" action="'.$site_url.'frontend/api/api.php?action=logout">';
        echo '<span class="xtraSetting"><a class="xtra" href="#" onClick="$(\'#form_odjava_desktop\').submit();"><span class="faicon logout"></span>' . $lang['logout'] . '</a></span>';
        echo '</form>';

        echo '</div>';
        echo '</div>';

        
        echo '</div>';
    }

    // Prikazemo logo zgoraj levo
    public function displayHeaderLogo(){
        global $lang;
        global $site_url;

        echo '<div id="logo">';

        $logo_class = ($lang['id'] != "1") ? ' class="english"' : '';
        $su = ($site_url == "https://www.1ka.si/" && $lang['id'] != "1") ? "https://www.1ka.si/d/en/" : $site_url;
		
        echo '<a href="' . $su . '" title="' . $lang['srv_1cs'] . '" id="enka_logo" ' . $logo_class . '></a>';

        echo '</div>';
    }

    // Utripajoc napis "Demo anketa"
    private function displayHeaderDemoSurvey(){
        global $lang;

        $row = SurveyInfo::getInstance()->getSurveyRow();

        if ($row['invisible'] == 1 && !Dostop::isMetaAdmin()) {

            echo '<div id="invisible-layer"></div>';
            echo '<div id="invisible-close" onClick="window.close(); return false;"><span>' . $lang['srv_close_invisible'] . '</span></div>';
            
            ?> <script> $('#invisible-close span').effect("pulsate", {times: 3}, 2000); </script> <?
        }
    }

    // Prikaze podatke o anketi na vrhu
    private function displayHeaderAnketa(){
        global $lang;
        global $site_url;
    
        // Aktivacija ankete, preview...
        echo '<div id="anketa_active" class="newCss '.substr($lang['language'], 0, 3).'">';
        $this->anketa_active();
        echo '</div> <!-- /anketa_active -->';

        // Prvi nivo navigacije  
        $this->showMainNavigation();

        // Drugi nivo navigacije
        $this->secondNavigation();

        // Tretji nivo navigacije po potrebi glede na podstran
        $this->thirdNavigation();
    }

    /**
     * prikaze glavo v seznamu anket
     *
     */
    private function displayHeaderSeznamAnket(){
        global $lang, $site_url, $global_user_id, $admin_type, $site_domain;

        // Pobrisemo vse preview vnose
        Common::deletePreviewData($this->anketa);

        # naložimo razred z seznamom anket
        $SL = new SurveyList();
        $SLCount = $SL->countSurveys();
        $SLCountPhone = $SL->countPhoneSurveys();

        // Obvestilo da ima uporabnik neprebrano sporocilo
        $NO = new Notifications();
        $countMessages = $NO->countMessages();
		if ($countMessages > 0) {
            echo '<div id="new_notification_alert" onClick="showUnreadMessages();">';
            echo $lang['srv_notifications_alert'];
            echo '</div>';

			// Ce imamo vklopljen avtomatski prikaz sporcila (za pomembne zadeve), ga prikazemo po loadu
			if($NO->checkForceShow())
				echo '<script>$(document).ready(function(){showUnreadMessages();})</script>';
		}
		
		// GDPR popup za prejemanje obvestil - force ce ga se ni izpolnil - SAMO NA www.1ka.si, test.1ka.si in virtualkah
		if (($site_url == 'https://www.1ka.si/' || $site_url == 'http://test.1ka.si/' || $site_url == 'https://1ka.arnes.si/' || ($cookie_domain == '.1ka.si' && $virtual_domain == true)) 
				&& User::getInstance()->getSetting($setting='gdpr_agree') == '-1') {		
			
			// Avtomatsko prikazemo po loadu
			echo '<script>$(document).ready(function(){showGDPRMessage();})</script>';
		}
	
		
        echo '<div id="anketa_active" class="folders">';	
		
        echo '  <div id="topLine2">&nbsp;</div>';

        echo '  <div id="surveyNavigation">';
        $SL->display_tabs();
        echo '  </div>';

        echo '</div>';
        
        
        # smo v knjižnici
        $SL->display_sub_tabs();
    }


    // Priakz footerja
    private function displayFooter(){
        global $lang;
        global $app_settings;
        global $site_frontend;
        global $aai_instalacija;
        global $mysql_database_name;


        echo '<footer id="srv_footer">';
        

        // Leva stran footerja
        echo '<div class="footer_left">';
        
        // Custom footer
        if(isset($app_settings['footer_custom']) && $app_settings['footer_custom'] == 1){
            echo $app_settings['footer_text'];
        }
        // Default footer
        else{
            echo $lang['srv_footer_links'];

            if(isset($aai_instalacija) && $aai_instalacija == true){
                echo ' | <a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/pogosta-vprasanja-o-arnes-aai-prijavi-uporabi-orodja-1ka" target="_blank">'.$lang['aa4'].'</a>';
            }

            echo '<br />';

            // Verzijo izpišemo samo za admine
            if ($admin_type == 0) {   
                
                // Verzija 1ka
                $sqlVersion = sisplet_query("SELECT value FROM misc WHERE what='version'", "obj");
                if (!empty($sqlVersion)) {
                    echo $lang['srv_footer_1ka_version'].': ' . $sqlVersion->value . ' | ';
                }

                // Verzija Drupal
                if ($site_frontend == 'drupal') {
                    $sqlDrupal = sisplet_query("SELECT value FROM misc WHERE what='drupal version'", "obj");
                    if (!empty($sqlDrupal)) {
                        echo 'Drupal: ' . $sqlDrupal->value . ' | ';
                    }
                }
            }
        
            echo 'Copyright (©) 2002-'.date('Y').' '.$lang['srv_footer_copyright'];
        }

        echo '</div>';


        // Desna stran footerja - report a bug
        echo '<div id="reportabug" class="footer_right">';
        
        // www.1ka.si ima se link na go instrukcije
        if($mysql_database_name == 'real1kasi' || $mysql_database_name == 'test1kasi' || $mysql_database_name == 'test21kasi'){

            echo '<a href="#" onClick="consultingPopupOpen();"><span class="faicon external_link"></span> '.$lang['srv_svetovanje'].'</a>';
            echo '<br>';
        }

        // Posebej report buga za gorenje
        if (Common::checkModule('gorenje')){
            echo '<a href="https://helpdesk.gorenje.com/SubmitSR.jsp" target="_blank"><span class="faicon inline_comment"></span> '.$lang['srv_footer_reportabug'].'</a>';
        }
        elseif(isset($aai_instalacija) && $aai_instalacija == true){
            echo '<a href="https://www.1ka.si/help1KA" target="_blank"><span class="faicon inline_comment"></span> '.$lang['srv_footer_reportabug'].'</a>';
        }
        else{
            // Slovenski jezik
            if ($lang['id'] == 1)
                echo '<a href="https://www.1ka.si/a/72864?Q1=292032" target="_blank"><span class="faicon inline_comment"></span> '.$lang['srv_footer_reportabug'].'</a>';
            // Angleski jezik
            else
                echo '<a href="https://www.1ka.si/a/72864?Q1=292032&language=2" target="_blank"><span class="faicon inline_comment"></span> '.$lang['srv_footer_reportabug'].'</a>';
        }

        echo '</div>';
        
        
        echo '</footer>';
    }


    // Prikaze ime ankete, zvezdico in tiste linke spodi (ker se vse refresha z ajaxom)
    private function anketa_active() {
        global $lang;
        global $site_url;
        global $admin_type;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        // ajaxa se poslje skupaj z ajaxom, da ob updatu vemo kaksen 'a' je bil na originalni strani
        // (drugace se ob updatu z ajaxom informacija o 'a'ju zgubi)
        $get = $_GET['a'];

        if (isset ($_GET['ajaxa']))
            $get = $_GET['ajaxa'];

        SurveyInfo::getInstance()->SurveyInit($this->anketa);
        $row = SurveyInfo::getInstance()->getSurveyRow();

        SurveySetting::getInstance()->Init($this->anketa);

        if ($this->skin == 0) {

            // Top navigacija
            echo '<div id="topLine2" class="noMargin">';

            // aktivni ki lahko tudi urejajo
            if ($this->checkDostopAktiven()) {

                echo '<span id="anketa_naslov" class="anketa_img_nav">';

                if($hierarhija_type == 10){
                    echo '<a href="#" title="' . $lang['srv_anketarename'] . '" style="cursor:text !important;">' . $row['naslov'] . '</a>';
                }else{
                    echo '<a href="#" onclick="anketa_title_edit(\'' . $this->anketa . '\',\'1\'); return false;" title="' . $lang['srv_anketarename'] . '">' . $row['naslov'] . '</a>';
                }

                $this->request_help();

                $this->check_online_users();

                echo '</span>';
            } 
            // pasivni lahko samo gledajo
            else {

                echo '	<span id="anketa_naslov" class="anketa_img_nav">';
                echo '		' . $row['naslov'] . '';
                echo '	</span>';

                $link = SurveyInfo::getSurveyLink();
            }

            echo '<script type="text/javascript">';
            echo '$(document).ready(function() {';
            echo '$("#baseSurveyInfoImg").mouseover(function() {showInfoBox(\'show\',$(this)); return false;});';

            echo '$("#anketa_url").fadeOut(200).fadeIn(200)';
            echo '});';
            echo '</script>';


            // aktivacija, deaktivacija
            echo '<span id="anketa_activation"' . ($this->isAnketar == true ? ' class="visibility_hidden"' : '') . '>';
            $this->displayAktivnost();
            echo '</span>'; # id="anketa_activation"


            // Nastavitve ankete
            $d = new Dostop();
            if ($d->checkDostopSub('edit')) {

                if ($hierarhija_type == 10) {
                    echo ' <a href="#" title="' . $lang['srv_survey_settings'] . '" style="padding: 0 5px;cursor:text !important;">';
                } 
                else {
                    echo ' <a href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&a=nastavitve" title="' . $lang['srv_survey_settings'] . '" style="padding: 0 5px;">';
                }

                echo '<span class="faicon wheel_32 icon-orange_hover_red" style="margin-bottom:1px;"></span>';
                echo '</a> ';
            }


            // Gumb za nadgraditev paketa (ce imamo vklopljene pakete in nimamo 3ka paketa)
            global $app_settings;
            global $global_user_id;
            if($app_settings['commercial_packages'] == true){

                // Preverimo trenuten paket uporabnika
                $userAccess = UserAccess::getInstance($global_user_id);
                $current_package = $userAccess->getPackage();
                if($current_package != '3' && !$userAccess->userNotAuthor()){
                    
                    $drupal_url = ($lang['id'] == '2') ? $site_url.'d/en/' : $site_url.'d/';
                    $upgrade_url = $drupal_url.'izvedi-nakup/3/podatki';

                    $button_text = ($current_package == '2') ? $lang['srv_access_upgrade2'] : $lang['srv_access_upgrade'];

                    echo '<div class="upgrade_package">';
                    echo '<div class="buttonwrapper"><a class="ovalbutton ovalbutton_purple" href="'.$upgrade_url.'" target="_blank"><span>'.$button_text.'</span></a></div>';
                    echo '</div>';
                }
            }


            // email anketa
            $reloadSetting = ($_GET['a'] == "nastavitve") ? "'1'" : "'0'";

            echo '<span id="survey_comment_holder" style="visibility: hidden" spremenljivka="0" view="0" type="0">&nbsp;</span>';
            echo '	</div>';

            SurveySetting::getInstance()->Init($this->anketa);
            
            // komentar na anketo, ki je vedno viden
            if (SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_showalways') == 1 &&
                $admin_type <= SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment')
            ) {
                echo '   <script>  $(function() {  load_comment(\'#survey_comment_holder\', \'1\');  });  </script>';
            }
        }
    }

    // Prikazemo prvi nivo navigacije in nastavimo stran in podstran na katerem se nahajamo
    private function showMainNavigation(){
        global $lang, $site_url, $admin_type;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        $row = SurveyInfo::getInstance()->getSurveyRow();
        SurveyInfo:: getInstance()->SurveyInit($this->anketa);

		$modules = SurveyInfo::getSurveyModules();

        # vse tri nivoje akcij pohendlamo tukaj, da bo lažje ob kakih spremnjanjih

        # prvi in drugi nivo
        $navigationArray = CrossRoad::MainNavigation($this->anketa, true);
        $this->first_action = $navigationArray['first_action'];
        $this->second_action = $navigationArray['second_action'];
        
        $css_status = 'off';
        $css_urejanje = 'off';
        $css_testiranje = 'off';
        $css_objava = 'off';
        $css_data = 'off';
        $css_analysis = 'off';
        $separatorli = '<li class="separator">&nbsp;</li>';

        if ($this->first_action == NAVI_STATUS) {
            $css_status = 'on';
        }
        if ($this->first_action == NAVI_UREJANJE) {
            $css_urejanje = 'on';
        }
        if ($this->first_action == NAVI_TESTIRANJE) {
            $css_testiranje = 'on';
        }
        if ($this->first_action == NAVI_OBJAVA) {
            $css_objava = 'on';
        }
        if ($this->first_action == NAVI_RESULTS && $_GET['m'] != 'monitoring') {
            $css_data = 'on';
        }

        if ($this->first_action == NAVI_ANALYSIS) {
            $css_analysis = 'on';
        }

        $d = new Dostop();

        echo '<div id="surveyNavigation">';

        echo '<div id="firstNavigation" >';

        echo '<div id="mojeAnketeLink">';
        echo '<a class="left-1ka" href="index.php?a=pregledovanje" title="' . $lang['srv_pregledovanje'] . ' (' . strtolower($lang['srv_create_survey']) . ', ' . strtolower($lang['srv_library']) . ')">'/*<span class="sprites moje_ankete_off"></span>*/.'<span class="library_link">' . $lang['srv_pregledovanje'] . '</span></a>';
        echo '</div>';


        echo '<ol class="left-side right-space' . ($this->isAnketar == true ? ' visibility_hidden' : '') . '">';

        if ($this->skin == 0 /*&& $this->checkDostopAktiven()*/ && $this->isAnketar == false) {
            echo $separatorli;
            echo '<li>';

            if ($d->checkDostopSub('dashboard'))
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_REPORTI . '" title="' . $lang['srv_navigation_status'] . '">';

            echo '<div id="status_link_' . $css_status . '">';

            if ($d->checkDostopSub('dashboard')) {
                echo '<span id="baseSurveyInfoImg" class="tooltip anketa_img_nav">';
                echo '<span class="faicon info icon-inline '.($css_status == 'on' ? 'icon-orange' : 'icon-white').'"></span>';
                echo '<span class="expanded-tooltip bottom light">';
                echo '<span id="surveyInfo_msg"></span>';
                echo '<span class="arrow"></span>';
                echo '</span>';    // expanded-tooltip bottom
                echo '</span>';
            }

            echo '<span class="status_link">' . $lang['srv_navigation_status'] . '</span>';

            echo '</div>';

            if ($d->checkDostopSub('dashboard'))
                echo '</a>';

            echo '</li>';
        }

        echo $separatorli;
        echo '<li>';
        if ($d->checkDostopSub('edit') && $hierarhija_type < 5) {
            echo '<a href="index.php?anketa=' . $this->anketa . ($this->survey_type > 1 ? '&a=' . A_BRANCHING : '') . '" title="' . $lang['srv_vprasalnik'] . '">';
        }
        echo '<div class="left-' . $css_urejanje . '">&nbsp;</div>';
        echo '<div class="step-' . $css_urejanje . '">' . $lang['srv_vprasalnik'] . '</div>';
        //echo $css_urejanjeRight;
        if ($d->checkDostopSub('edit') && $hierarhija_type < 5) {
            echo '</a>';
        }
        echo '</li>';

        # testiranje - ne prikazemo v glasovanju
        if ($this->survey_type != 0 && $this->survey_type != 1) {
            echo $separatorli;
            echo '<li>';
            if ($d->checkDostopSub('test')) {
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TESTIRANJE . '" title="' . $lang['srv_testiranje'] . '">';
            }
            echo '<div class="step-' . $css_testiranje . '">' . $lang['srv_testiranje'] . '</div>';
            //echo $css_testiranjeRight;
            if ($d->checkDostopSub('test')) {
                echo '</a>';
            }

            echo '</li>';
        }

        # če ni manager mora iti na vabila
        if (SurveyInfo::getInstance()->checkSurveyModule('email') && $this->user_role_cehck(U_ROLE_MANAGER) == true) {
			$link = 'invitations';
        }
		else {
            $link = A_VABILA;
        }

        echo $separatorli;
        echo '<li>';
        if ($d->checkDostopSub('publish')) {
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . $link . '" title="' . $lang['srv_vabila'] . '">';
        }
        echo '<div class="step-' . $css_objava . '">' . $lang['srv_vabila'] . '</div>';
        //echo $css_objavaRight;
        if ($d->checkDostopSub('publish')) {
            echo '</a>';
        }
        echo '</li>';


        // Podatki - ne prikazemo v glasovanju
        if ($this->survey_type != 0) {
            echo $separatorli;
            echo '<li>';
            if ($d->checkDostopSub('data')) {
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COLLECT_DATA . '" title="' . $lang['srv_results'] . '">';
            }
            echo '<div class="step-' . $css_data . '">' . $lang['srv_results'] . '</div>';
            //echo $css_dataRight;
            if ($d->checkDostopSub('data')) {
                echo '</a>';
            }
            echo '</li>';
        }
        # če je manj kot 20 variabel naj gre default na graf
        $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
        [$varcount] = mysqli_fetch_array($sql);


        if ($varcount < 20) {
            SurveyDataSettingProfiles:: Init($this->anketa);
            $goto = SurveyDataSettingProfiles::getSetting('analysisGoTo');
            $_goto_m = '&m=' . $goto;
        } else {
            $_goto_m = '&m=' . M_ANALYSIS_SUMMARY;
        }
        echo $separatorli;
        echo '<li>';

        if ($d->checkDostopSub('analyse') && !isset($modules['hierarhija'])) {
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . $_goto_m . '" title="' . $lang['srv_analiza'] . '">';

        } 
        elseif ($d->checkDostopSub('analyse') && isset($modules['hierarhija'])) {
            echo '<a href="#" title="' . $lang['srv_analiza_hierarchy'] . '">';
        }
        echo '<div class="step-' . $css_analysis . '">' . $lang['srv_analiza'] . '</div>';

        if ($d->checkDostopSub('analyse')) {
            echo '</a>';
        }
        echo '</li>';

        echo '</ol>';


        $d = new Dostop();
        if ($d->checkDostopAktiven()) {
            echo '<ol class="quick_settings' . ($this->isAnketar == true ? ' visibility_hidden' : '') . '">';

            # Komentarji
            SurveySetting::getInstance()->Init($this->anketa);
            $survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment');
            $question_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment');
            $question_note_view = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_view');
            $question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');

            $sas = new SurveyAdminSettings();

            // V kolikor je vklopljena hierarhija in imamo gor splošne uporabnike, potem nastavitev ne prikazujemo
            $hierarhija_prikaz = true;
            if(SurveyInfo::getInstance()->checkSurveyModule('hierarhija') && $hierarhija_type == 10)
                $hierarhija_prikaz = false;

            #ikonco za komentarje prikazujemo po potrebi
            if ($this->survey_type > 1 && $hierarhija_prikaz) {

                global $global_user_id;
                $userAccess = UserAccess::getInstance($global_user_id);

                if ($survey_comment != '' || $question_comment != '' || /*$question_note_view != '' ||*/
                    $question_resp_comment == 1 || $sas->testiranje_komentarji_komentarji_na_vprasanje(false) > 0
                ) {
                    echo '<li>';
                    echo '<div id="quick_comments_link" class="newCss">';
                    
                    if($userAccess->checkUserAccess('komentarji')){
                        if ($sas->testiranje_komentarji_komentarji_na_vprasanje() > 0)
                            echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='.$row['id'].'&a=komentarji" title="' . $lang['srv_view_comment'] . '" ><div class="fa-stack"><span class="faicon comments_num icon-orange fa-stack-1x" title="' . $lang['srv_view_comment'] . '"><strong class="fa-stack-1x">' . $sas->testiranje_komentarji_komentarji_na_vprasanje() . '</strong></span></div></a>';
                        else
                            echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='.$row['id'].'&a=komentarji" title="' . $lang['srv_view_comment'] . '" ><div class="fa-stack"><span class="faicon comments fa-stack-1x icon-orange" title="' . $lang['srv_view_comment'] . '"></span></div></a>';
                    }
                    else{
                        if ($sas->testiranje_komentarji_komentarji_na_vprasanje() > 0)
                            echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='. $row['id'].'&a=urejanje" title="' . $lang['srv_view_comment'] . '" ><div class="fa-stack"><span class="faicon comments_num icon-orange fa-stack-1x user_access_locked" title="' . $lang['srv_view_comment'] . '"><strong class="fa-stack-1x">' . $sas->testiranje_komentarji_komentarji_na_vprasanje() . '</strong></span></div></a>';
                        else
                            echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='.$row['id'].'&a=urejanje" title="' . $lang['srv_view_comment'] . '" ><div class="fa-stack"><span class="faicon comments fa-stack-1x icon-orange user_access_locked" title="' . $lang['srv_view_comment'] . '"></span></div></a>';
                    }

                    echo '</div>';
                    echo '</li>';
                } 
                // ce ni komentarjev, potem prikazemo link do nastavitev komentarjev
                else {    
                    echo '<li>';
                    echo '<div id="quick_comments_link" class="newCss">';

                    // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
                    
                    if($userAccess->checkUserAccess('komentarji'))
                        echo '<a href="' . $site_url . 'admin/survey/ajax.php?anketa='.$row['id'].'&a=comments_onoff&do=on" title="' . $lang['srv_preview_comments'] . '"><div class="fa-stack"><span class="faicon comments fa-stack-1x icon-blue" title="' . $lang['srv_preview_comments'] . '">';
                    else
                        echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='.$row['id'].'&a=urejanje" title="' . $lang['srv_preview_comments'] . '"><div class="fa-stack"><span class="faicon comments fa-stack-1x icon-blue user_access_locked" title="' . $lang['srv_preview_comments'] . '">';

                    echo '</span></div></a>';

                    echo '</div>';
                    echo '</li>';
                }

                if (($admin_type <= $survey_comment && $survey_comment != '') || $sas->testiranje_komentarji_komentarji_na_anketo(false) > 0) {
                    echo '<li>';
                    echo '<div id="quick_comments_link" class="newCss">';
                    $this->survey_icon_add_comment();
                    echo '</div>';
                    echo '</li>';
                }
            }

            // ikonco za jezik prikazujemo po potrebi
            if ($row['multilang'] == 1) {
                $p = new Prevajanje($this->anketa);
                global $lang1;

                echo '<li style="margin: 10px 0 0 0;">';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=prevajanje" class="srv_ico" title="' . $lang['lang'] . ': ' . $lang['lang_short'] . ' | ' . $lang1['lang_short'] . $lang_more . '">';
                echo '<span class="faicon language icon-as_link"></span>';
                echo '</a>';
                echo '</li>';
            }
            elseif (UserSetting::getInstance()->getUserSetting('showLanguageShortcut')) {
                // Ikona za vklop jezika, če je v globalnih nastavitvah vklopljena opcija
                echo '<li style="margin: 10px 0 0 0;">';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=prevajanje" class="srv_ico" title="' . $lang['lang'] . '">';
                echo '<span class="faicon language icon-grey_normal pointer"></span>';
                echo '</a>';
                echo '</li>';
            }

            // Ikonca ce je izklopljeno prilagajanje za mobitel (mobile friendly)
            $mobile_friendly = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_friendly');
            if ($mobile_friendly == 0) {
                echo '<li style="margin: 10px 0 0 0;">';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=mobile_settings" class="srv_ico" title="' . $lang['srv_settings_mobile_friendly_off'] . '">';
                echo '<span class="faicon mobile_off icon-as_link"></span>';
                echo '</a>';
                echo '</li>';
            }

            echo '</ol>';
        }


        # zavhiki dodatnih nastavitev
        echo '<ol class="smaller right-side">';

        # MAZA - mobilna aplikacija za anketirance
        if (isset($modules['maza'])) {
            $css = ($this->first_action == A_MAZA) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_MAZA . '" title="' . $lang['srv_maza'] . '">';
            echo '<span class="module_icon maza"></span>';
            echo '</a>';
            echo '</li>';
        }
        # MAZA - mobilna aplikacija za anketirance
        if (isset($modules['wpn'])) {
            $css = ($this->first_action == A_WPN) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_WPN . '" title="' . $lang['srv_wpn'] . '">';
            echo '<span class="module_icon wpn"></span>';
            echo '</a>';
            echo '</li>';
        }
        # telefon
        if (isset($modules['phone'])) {
            $css = ($this->first_action == NAVI_PHONE) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            # če je navadni user in anketar
            if ($this->isAnketar) {
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TELEPHONE . '&m=start_call" title="' . $lang['phone'] . '">';
            } else {
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TELEPHONE . '" title="' . $lang['phone'] . '">';
            }
            echo '<span class="module_icon telephone"></span>';
            echo '</a>';
            echo '</li>';
        }
        # slideshow
        if (isset($modules['slideshow'])) {
            $css = ($this->first_action == NAVI_SLIDESHOW) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_SLIDESHOW . '" title="' . $lang['srv_vrsta_survey_type_9'] . '">';
            echo '<span class="module_icon slideshow"></span>';
            echo '</a>';
            echo '</li>';
        }
        # evalvacija
        if (isset($modules['uporabnost'])) {
            $css = ($this->first_action == NAVI_UPORABNOST) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_UPORABNOST . '" title="' . $lang['srv_uporabnost'] . '">';
            echo '<span class="module_icon evalvation"></span>';
            echo '</a>';
            echo '</li>';
        }
        # vnos
        if ($row['user_from_cms'] >= 1) {
            $css = ($this->first_action == NAVI_VNOS) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COOKIE . '" title="' . $lang['srv_vnos'] . '">';
            echo '<span class="module_icon vnos"></span>';
            echo '</a>';
            echo '</li>';
        }
        # socialna omrezja
        if (isset($modules['social_network'])) {
            $css = ($this->first_action == NAVI_VNOS) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_SOCIAL_NETWORK . '" title="' . $lang['srv_vrsta_survey_type_8'] . '">';
            echo '<span class="module_icon social"></span>';
            echo '</a>';
            echo '</li>';
        }
		# kviz
        if (isset($modules['quiz'])) {
            $css = ($this->first_action == A_KVIZ) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_KVIZ . '" title="' . $lang['srv_vrsta_survey_type_6'] . '">';
            echo '<span class="module_icon quiz"></span>';
            echo '</a>';
            echo '</li>';
        }
        # volitve
        if (isset($modules['voting'])) {
            $css = ($this->first_action == A_VOTING) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_VOTING . '" title="' . $lang['srv_vrsta_survey_type_18'] . '">';
            echo '<span class="module_icon voting"></span>';
            echo '</a>';
            echo '</li>';
        }
		# napredni parapodatki
        if (isset($modules['advanced_paradata'])) {
            $css = ($this->first_action == A_ADVANCED_PARADATA) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ADVANCED_PARADATA . '" title="' . $lang['srv_vrsta_survey_type_16'] . '">';
            echo '<span class="module_icon advanced_paradata"></span>';
            echo '</a>';
            echo '</li>';
        }
		# excelleration matrix
        if (isset($modules['excell_matrix'])) {
            $css = ($this->first_action == 'excell_matrix') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=excell_matrix" title="Excelleration matrix">';
            echo '<span class="module_icon excell_matrix"></span>';
            echo '</a>';
            echo '</li>';
        }
		# chat
        if (isset($modules['chat'])) {
            $css = ($this->first_action == A_CHAT) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_CHAT . '" title="' . $lang['srv_vrsta_survey_type_14'] . '">';
            echo '<span class="module_icon chat"></span>';
            echo '</a>';
            echo '</li>';
        }
		# panel
        if (isset($modules['panel'])) {
            $css = ($this->first_action == A_PANEL) ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_PANEL . '" title="' . $lang['srv_vrsta_survey_type_15'] . '">';
            echo '<span class="module_icon panel"></span>';
            echo '</a>';
            echo '</li>';
        }
		# evoli
        if (isset($modules['evoli'])) {
            $css = ($this->first_action == 'evoli') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli" title="Evoli">';
            echo '<span class="module_icon evoli"></span>';
            echo '</a>';
            echo '</li>';
        }
		# evoli teammeter
        if (isset($modules['evoli_teammeter'])) {
            $css = ($this->first_action == 'evoli_teammeter') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_teammeter" title="Evoli team meter">';
            echo '<span class="module_icon evoli_teammeter"></span>';
            echo '</a>';
            echo '</li>';
        }
        # evoli_quality_climate
        if (isset($modules['evoli_quality_climate'])) {
            $css = ($this->first_action == 'evoli_quality_climate') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_quality_climate" title="Evoli quality climate">';
            echo '<span class="module_icon evoli_quality_climate"></span>';
            echo '</a>';
            echo '</li>';
        }
        # evoli_teamship_meter
        if (isset($modules['evoli_teamship_meter'])) {
            $css = ($this->first_action == 'evoli_teamship_meter') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_teamship_meter" title="Evoli teamship meter">';
            echo '<span class="module_icon evoli_teamship_meter"></span>';
            echo '</a>';
            echo '</li>';
        }
        # evoli_organizational_employeeship_meter
        if (isset($modules['evoli_organizational_employeeship_meter'])) {
            $css = ($this->first_action == 'evoli_organizational_employeeship_meter') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_organizational_employeeship_meter" title="Evoli organizational employeeship meter">';
            echo '<span class="module_icon evoli_organizational_employeeship_meter"></span>';
            echo '</a>';
            echo '</li>';
        }
        # evoli employmeter
        if (isset($modules['evoli_employmeter'])) {
            $css = ($this->first_action == 'evoli_employmeter') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_employmeter" title="Evoli employeeship meter">';
            echo '<span class="module_icon evoli_employmeter"></span>';
            echo '</a>';
            echo '</li>';
        }
		# mfdps
        if (isset($modules['mfdps'])) {
            $css = ($this->first_action == 'mfdps') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=mfdps" title="MFDPŠ">';
            echo '<span class="module_icon mfdps"></span>';
            echo '</a>';
            echo '</li>';
        }
		# borza
        if (isset($modules['borza'])) {
            $css = ($this->first_action == 'borza') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=borza" title="BORZA">';
            echo '<span class="module_icon borza"></span>';
            echo '</a>';
            echo '</li>';
        }
		# mju
        if (isset($modules['mju'])) {
            $css = ($this->first_action == 'mju') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=mju" title="MJU">';
            echo '<span class="module_icon mju"></span>';
            echo '</a>';
            echo '</li>';
        }
		# 360
        if (isset($modules['360_stopinj'])) {
            $css = ($this->first_action == '360_stopinj') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=360_stopinj" title="' . $lang['srv_360_reports'] . '">';
            echo '<span class="module_icon degrees"></span>';
            echo '</a>';
            echo '</li>';
        }
		# 360 1ka
        if (isset($modules['360_stopinj_1ka'])) {
            $css = ($this->first_action == '360_stopinj_1ka') ? 'on' : 'off';

            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=360_stopinj_1ka" title="' . $lang['srv_360_reports'] . '">';
            echo '<span class="module_icon degrees_1ka"></span>';
            echo '</a>';
            echo '</li>';
        }
		# GDPR
		$gdpr = new GDPR();
        if (true /*$gdpr->isGDPRSurvey($this->anketa)*/) {
			
            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=gdpr_settings" title="' . $lang['srv_gdpr'] . '">';
            echo '<span class="module_icon gdpr '.($gdpr->isGDPRSurvey($this->anketa) ? 'active' : '').'"></span>';
            echo '</a>';
            echo '</li>';
        }
        # SA - hierarhija
        if (isset($modules['hierarhija'])) {
            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
                if($modules['hierarhija'] == 1) {
                    echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . (($hierarhija_type < 5) ? A_HIERARHIJA_SUPERADMIN  : A_HIERARHIJA) . '&amp;m='.(($hierarhija_type < 5) ? M_ADMIN_UREDI_SIFRANTE : M_UREDI_UPORABNIKE).'" title="' . $lang['srv_hierarchy_link'] . '">';
                }elseif($modules['hierarhija'] == 2){
                    echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . (($hierarhija_type < 5) ? A_HIERARHIJA_SUPERADMIN  : A_HIERARHIJA) . '&amp;m='.M_HIERARHIJA_STATUS.'" title="' . $lang['srv_hierarchy_link'] . '">';
                }

                // V kolikor ima hierarhija ime potem to tudi izpišemo
                $ime_hierarhije = (new \Hierarhija\Model\HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'aktivna_hierarhija_ime', null, null, false);
                if(!empty($ime_hierarhije)) {
                    if(strlen($ime_hierarhije)){
                        $ime_hierarhije = substr($ime_hierarhije, 0, 30).'...';
                    }


                    echo '<span style="padding-right: 10px;font-size:16px;font-weight: normal;" class="oranzna">' . $ime_hierarhije . '</span>';
                }

                echo '<span class="module_icon sa-hierarhija"></span>';
                echo '</a>';
            echo '</li>';
        }
        elseif(UserSetting::getInstance()->getUserSetting('showSAicon') && $admin_type < 3){
            echo '<li class="space">&nbsp;</li>';

            echo '<li>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' .  A_HIERARHIJA_SUPERADMIN  . '&amp;m='.M_HIERARHIJA_STATUS.'" title="' . $lang['srv_hierarchy'] . '">';
            echo '<span class="module_icon sa-hierarhija"></span>';
            echo '</a>';
            echo '</li>';
        }


        echo '</ol>';


        echo '</div>'; # id="firstNavigation"
        
        echo '</div>'; # id="surveyNavigation"
    }

    // Prikazemo drugi nivo navigacije
    private function secondNavigation(){
        global $lang, $site_url, $admin_type;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        # ajaxa se poslje skupaj z ajaxom, da ob updatu vemo kaksen 'a' je bil na originalni strani
        # (drugace se ob updatu z ajaxom informacija o 'a'ju zgubi)
        $get = $_GET['a'];
        if (isset ($_GET['ajaxa']))
            $get = $_GET['ajaxa'];
        if (trim($get) == '') {
            $get = A_BRANCHING;
        }
        //***  druga vrstica navigacije  ***//
        echo '<div id="secondNavigation" class="clr subpage_' . $get . '">';

        # podzavihek: urejanje ankete
        if ($this->first_action == NAVI_UREJANJE) {

            echo '<ul class="secondNavigation ' . ($this->isAnketar == true ? ' visibility_hidden' : '') . '">';

			#zavihek vprasalnik *
            echo '<li>';
            echo '<a class="no-img side-left' . ($this->second_action == NAVI_UREJANJE_BRANCHING ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . ($this->survey_type > 1 ? '&a=' . A_BRANCHING : '') . '" title="' . $lang['srv_editirajanketo2'] . '">';
            echo $lang['srv_editirajanketo2'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';

            #zavihek urejanje akete
            echo '<li>';
            echo '<a class="no-img' . ($this->second_action == NAVI_UREJANJE_ANKETA ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&a=' . A_SETTINGS . '" title="' . $lang['srv_nastavitve_ankete'] . '">';
            echo $lang['srv_nastavitve_ankete'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';

            # zavihek oblika
            echo '<li>';
            echo '<a class="no-img side-right' . ($this->second_action == NAVI_UREJANJE_TEMA ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TEMA . '" title="' . $lang['srv_themes'] . '">';
            echo $lang['srv_themes'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';

            echo '</ul>';

            if (!$this->isAnketar) {
                echo '<ul class="secondNavigationArchive">';
                # link arhivi
                echo '<li class="' . ($get == A_ARHIVI || $get == A_TRACKING ? ' aactive' : '') . '">';
                echo '<a class="' . ($get == A_ARHIVI || $get == A_TRACKING ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ARHIVI . '" title="' . $lang['srv_arhivi'] . '">';
                //echo $lang['srv_arhivi'];
                echo /*'<span class="sprites archive"></span>' .*/ $lang['srv_analiza_arhiv'];
                echo '</a>';
                echo '</li>';
                echo '</ul>';
            }
        }

        # podzavihek: testiranje
        if ($this->first_action == NAVI_TESTIRANJE) {
            //$tab = $_GET['m'];
            echo '<ul class="secondNavigation">';
            echo '<li>';
            echo '<a class="no-img side-left' . ($this->second_action == M_TESTIRANJE_DIAGNOSTIKA ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . M_TESTIRANJE_DIAGNOSTIKA . '" title="' . $lang['srv_testiranje_diagnostika'] . '">';
            echo $lang['srv_testiranje_diagnostika'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';

             # zavihek trajanje
            echo '<li>';
            echo '<a class="no-img' . ($this->second_action == NAVI_TESTIRANJE_PREDVIDENI || $this->second_action == NAVI_TESTIRANJE_CAS ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . A_TRAJANJE_PREDVIDENI . '" title="' . $lang['srv_testiranje_trajanje'] . '">';
            echo $lang['srv_testiranje_trajanje'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';
            
            # zavihek komentarji
            echo '<li>';
            echo '<a class="no-img' . ($this->second_action == NAVI_TESTIRANJE_KOMENTARJI || $this->second_action == NAVI_TESTIRANJE_KOMENTARJI_ANKETA ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_KOMENTARJI . '" title="' . $lang['srv_testiranje_komentarji'] . '">';
            echo $lang['srv_testiranje_komentarji'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';
            echo '<li>';
            echo '<a class="no-img side-right' . ($this->second_action == NAVI_TESTIRANJE_VNOSI ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . M_TESTIRANJE_VNOSI . '" title="' . $lang['srv_testiranje_vnosi'] . '">';
            echo $lang['srv_testiranje_vnosi'] . '</a>';
            echo '</li>';

            echo '</ul>';

            // Ce imamo testne vnose prikazemo povezavo na arhiv testnih vnosov
            if ($this->survey_type > 1) {
                $str_testdata = "SELECT count(*) FROM srv_user WHERE ank_id='" . $this->anketa . "' AND (testdata='1' OR testdata='2') AND deleted='0'";
                $query_testdata = sisplet_query($str_testdata);
                [$testdata] = mysqli_fetch_row($query_testdata);

                if ((int)$testdata > 0) {
                    echo '<ul class="secondNavigationArchive">';
                    # link arhivi
                    echo '<li>';
                    echo '<a class="' . ($get == A_ARHIVI || $get == A_TRACKING ? ' active' : '') . '"'
                        . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ARHIVI . '&m=testdata" title="' . $lang['srv_arhivi'] . '">';
                    echo /*'<span class="sprites archive"></span>' .*/ $lang['srv_analiza_arhiv'];
                    echo '</a>';
                    echo '</li>';
                    echo '</ul>';
                }
            }
        }

        # podzavihek: email-vabila, objava
        if ($this->first_action == NAVI_OBJAVA) {

            $tab = $_GET['m'];
            $get = $_GET['m'];

            echo '<ul class="secondNavigation">';

            #((($tab == 'url' || ($row['email'] != 1 && !$tab && $get!='email')) && ($get != 'invitations'))
            echo '<li>';
            echo '<a class="no-img side-left' . ($_GET['a'] == A_VABILA && ($_GET['m'] == '' || $_GET['m'] == 'settings') ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_VABILA . '&m=settings" title="' . $lang['srv_publication_base'] . '">';
            echo $lang['srv_publication_base'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';
            echo '<li>';
            echo '<a class="no-img side' . ($_GET['a'] == A_VABILA && $_GET['m'] == 'url' ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_VABILA . '&m=url" title="' . $lang['srv_publication_url'] . '">';
            echo $lang['srv_publication_url'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';
            echo '<li>';
            echo '<a class="no-img side-right' . ($_GET['a'] == A_INVITATIONS && $_GET['m'] != 'view_archive' ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_INVITATIONS . '" title="' . $lang['srv_inv_nav_invitations'] . '">';
            echo $lang['srv_inv_nav_invitations'] . '</a>';
            echo '</li>';

            echo '</ul>';

            echo '<ul class="secondNavigationArchive">';
            # link arhivi
            echo '<li>';
            echo '<a class="' . ($get == A_ARHIVI || $get == A_TRACKING || $_GET['m'] == 'view_archive' ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=invitations&m=view_archive" title="' . $lang['srv_arhivi'] . '">';
            //echo $lang['srv_arhivi'];
            echo /*'<span class="sprites archive"></span>' .*/ $lang['srv_analiza_arhiv'];
            echo '</a>';
            echo '</li>';
            echo '</ul>';

        }
        # podzavihek: analize
        if ($this->first_action == NAVI_ANALYSIS) {
            echo '<ul class="secondNavigation">';

            # ZDRUŽIMO STATISTIKE
            # osnovne
            echo '<li>';
            echo '<a class="no-img side-left' . ($_GET['m'] == M_ANALYSIS_SUMMARY
                || $_GET['m'] == M_ANALYSIS_FREQUENCY
                || $_GET['m'] == M_ANALYSIS_DESCRIPTOR
                || $_GET['m'] == M_ANALYSIS_CROSSTAB
                || $_GET['m'] == M_ANALYSIS_MEANS
                || $_GET['m'] == M_ANALYSIS_TTEST
                || $_GET['m'] == M_ANALYSIS_BREAK
                || $_GET['m'] == M_ANALYSIS_PARA
                || $_GET['m'] == M_ANALYSIS_MULTICROSSTABS
                || $_GET['m'] == M_ANALYSIS_MEANS_HIERARHY
				|| $_GET['m'] == M_ANALYSIS_HEATMAP
                    ? ' active' : '') . '"';
            if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
                echo ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_MEANS_HIERARHY . '" title="' . $lang['srv_stat_analiza'] . '">';
            } else {
                echo ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_SUMMARY . '" title="' . $lang['srv_stat_analiza'] . '">';
            }
            echo $lang['srv_stat_analiza'] . '</a>';
            echo '</li>';


            echo '<li class="space"></li>';


            if (!SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {

				# zavihek GRAFI
                echo '<li>';
                echo '<a class="no-img ' . ($_GET['m'] == M_ANALYSIS_CHARTS ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_CHARTS . '" title="' . $lang['srv_analiza_charts'] . '">';
                echo $lang['srv_analiza_charts'] . '</a>';
                echo '</li>';

                echo '<li class="space"></li>';

				// zavihek POROCILA
                // Link na navadna porocila
                if (SurveyCustomReport::checkEmpty($this->anketa)) {
                    echo '<li>';
                    echo '<a class="no-img side-right ' . ($_GET['m'] == M_ANALYSIS_CREPORT || $this->second_action == NAVI_ANALYSIS_LINKS ? ' active' : '') . '"'
                        . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_LINKS . '" title="' . $lang['srv_reporti'] . '">';
                    echo $lang['srv_reporti'] . '</a>';
                    echo '</li>';
                } // Link na porocilo po meri (ce ni prazno)
                else {
                    echo '<li>';
                    echo '<a class="no-img side-right' . ($_GET['m'] == M_ANALYSIS_CREPORT || $this->second_action == NAVI_ANALYSIS_LINKS ? ' active' : '') . '"'
                        . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_CREPORT . '" title="' . $lang['srv_reporti'] . '">';
                    echo $lang['srv_reporti'] . '</a>';
                    echo '</li>';
                }

                // zavihek vizualizacija - zaenkrat samo admini
				if ($admin_type === '0') {
					echo '<li class="space"></li>';

					echo '<li>';
					echo '<a class="no-img ' . ($_GET['m'] == M_ANALYSIS_VIZUALIZACIJA ? ' active' : '') . '"'
						. ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_VIZUALIZACIJA . '" title="' . $lang['srv_vizualizacija'] . '">';
					echo $lang['srv_vizualizacija'] . '</a>';
					echo '</li>';
				}

				// zavihek 360 STOPINJSKA POROCILA
				if (SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')) {
					echo '<li class="space"></li>';

					echo '<li>';
					echo '<a class="no-img ' . ($_GET['m'] == M_ANALYSIS_360 ? ' active' : '') . '"'
						. ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_360 . '" title="' . $lang['srv_360_stopinj'] . '">';
					echo $lang['srv_360_report'] . '</a>';
					echo '</li>';
				}

				// zavihek 360 STOPINJSKA POROCILA 1KA
				if (SurveyInfo::getInstance()->checkSurveyModule('360_stopinj_1ka')) {
					echo '<li class="space"></li>';

					echo '<li>';
					echo '<a class="no-img ' . ($_GET['m'] == M_ANALYSIS_360_1KA ? ' active' : '') . '"'
						. ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_360_1KA . '" title="' . $lang['srv_360_stopinj'] . '">';
					echo $lang['srv_360_report'] . '</a>';
					echo '</li>';
				}

				//$row = Cache::srv_spremenljivka($spremenljivka);

            }

            echo '</ul>';

            echo '<ul class="secondNavigationArchive">';
            # link arhivi
            echo '<li>';
            echo '<a class="no-img ' . ($_GET['m'] == M_ANALYSIS_ARCHIVE ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&amp;m=' . M_ANALYSIS_ARCHIVE . '" title="' . $lang['srv_analiza_arhiv'] . '">';
            echo /*'<span class="sprites archive"></span>' .*/ $lang['srv_analiza_arhiv'];
            echo '</a>';
            echo '</li>';

            echo '</ul>';
        }

        # podzavihek: rezultati
        if ($this->first_action == NAVI_RESULTS) {
            if ($_GET['m'] != 'monitoring') {

                echo '<ul class="secondNavigation">';

                // podatki
                echo '<li>';
                echo '<a class="no-img side-left' . ((($_GET['m'] == 'view' || $_GET['m'] == '' || $_GET['m'] == M_COLLECT_DATA_QUICKEDIT || $_GET['m'] == M_COLLECT_DATA_VARIABLE_VIEW || $_GET['m'] == M_COLLECT_DATA_QUICKEDIT) && $_GET['a'] != A_COLLECT_DATA_EXPORT) ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COLLECT_DATA . '" title="' . $lang['srv_link_data_browse'] . '">';
                echo $lang['srv_link_data_browse'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';

                // izracuni
                echo '<li>';
                echo '<a class="no-img' . ($_GET['m'] == M_COLLECT_DATA_CALCULATION || $_GET['m'] == M_COLLECT_DATA_CODING || $_GET['m'] == 'coding_auto' || $_GET['m'] == M_COLLECT_DATA_RECODING || $_GET['m'] == M_COLLECT_DATA_RECODING_DASHBOARD ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COLLECT_DATA . '&amp;m=calculation" title="' . $lang['srv_data_navigation_calculate'] . '">';
                echo $lang['srv_data_navigation_calculate'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';

                // uvoz
                echo '<li>';
                echo '<a class="no-img' . ($_GET['m'] == 'append' || $_GET['m'] == 'merge' || $_GET['m'] == 'upload_xls' || $_GET['m'] == 'append_xls' ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COLLECT_DATA . '&amp;m=append" title="' . $lang['srv_data_navigation_import'] . '">';
                echo $lang['srv_data_navigation_import'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';

                $d = new Dostop();
                # izvozi
                if ($d->checkDostopSub('export')) {

                    echo '<li>';
                    echo '<a class="no-img side-right' . ($this->second_action == NAVI_DATA_EXPORT ? ' active' : '') . '"'
                        . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COLLECT_DATA_EXPORT . '" title="' . $lang['srv_export_tab'] . '">';
                    echo $lang['srv_export_tab'] . '</a>';
                    echo '</li>';
                }

                echo '</ul>';

                if ($d->checkDostopSub('edit')) {
                    echo '<ul class="secondNavigationArchive">';
                    # link arhivi
                    echo '<li>';
                    echo '<a class="no-img ' . ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'data' ? ' active' : '') . '"'
                        . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ARHIVI . '&amp;m=data" title="' . $lang['srv_arhiv_data'] . '">';
                    //echo $lang['srv_analiza_arhiv'];
                    echo /*'<span class="sprites archive"></span>' .*/ $lang['srv_analiza_arhiv'];
                    echo '</a>';
                    echo '</li>';

                    echo '</ul>';
                }

            }
        }
        # podzavihek: napredne možnosti
        if ($this->first_action == NAVI_ADVANCED) {
            # preštejemo katere module imamo. če imamo samo nastavitve prikažemo kot samostojn zavihek

            $row = SurveyInfo::getInstance()->getSurveyRow();
			$modules = SurveyInfo::getSurveyModules();
            $cnt_modules = (int)count($modules) + (int)($row['user_from_cms'] == 2 && $row['cookie'] == -1);

            echo '<ul class="secondNavigation">';

            # nastavitve
            echo '<li>';
            echo '<a class="no-img' . ($cnt_modules > 0 ? ' side-left' : ' single') . ($_GET['a'] == A_ADVANCED ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ADVANCED . '" title="' . $lang['srv_moduli'] . '">';
            echo $lang['srv_moduli_setings'] . '</a>';
            echo '</li>';

            # dodamo posamezne module po potrebi
            #uporabnost
            if (isset($modules['uporabnost'])) {
                $_active = ($_GET['a'] == A_UPORABNOST) ? ' active' : '';
                $_right = (($row['user_from_cms'] == 2 && $row['cookie'] == -1)
                    || isset($modules['quiz'])
                    || isset($modules['social_network'])
                    || isset($modules['slideshow'])) ? '' : ' side-right';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img' . $_right . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_UPORABNOST . '" title="' . $lang['srv_uporabnost'] . '">' . $lang['srv_uporabnost'] . '</a></li>';
            }
            # vnos
            if ($row['user_from_cms'] == 2 && $row['cookie'] == -1) {
                $_active = ($_GET['a'] == A_VNOS) ? ' active' : '';
                $_right = (isset($modules['quiz'])
                    || isset($modules['social_network'])
                    || isset($modules['slideshow'])) ? '' : ' side-right';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img' . $_right . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_VNOS . '" title="' . $lang['srv_vnos'] . '">' . $lang['srv_vnos'] . '</a></li>';
            }
            #kviz
            if (isset($modules['quiz'])) {
                $_active = ($_GET['a'] == A_KVIZ) ? ' active' : '';
                $_right = (isset($modules['social_network'])
                    || isset($modules['slideshow'])) ? '' : ' side-right';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img' . $_right . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_KVIZ . '" title="' . $lang['srv_kviz'] . '">' . $lang['srv_kviz'] . '</a></li>';
            }
            #volitve
            if (isset($modules['voting'])) {
                $_active = ($_GET['a'] == A_VOTING) ? ' active' : '';
                $_right = (isset($modules['social_network'])
                    || isset($modules['slideshow'])) ? '' : ' side-right';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img' . $_right . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_VOTING . '" title="' . $lang['srv_voting'] . '">' . $lang['srv_voting'] . '</a></li>';
            }
			#napredni parapodatki
            if (isset($modules['advanced_paradata'])) {
                $_active = ($_GET['a'] == A_ADVANCED_PARADATA) ? ' active' : '';
                $_right = (isset($modules['advanced_paradata'])
                    || isset($modules['advanced_paradata'])) ? '' : ' side-right';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img' . $_right . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_ADVANCED_PARADATA . '" title="' . $lang['srv_advanced_paradata'] . '">' . $lang['srv_advanced_paradata'] . '</a></li>';
            }
            # SN
            if (isset($modules['social_network'])) {
                $_active = ($_GET['a'] == A_SOCIAL_NETWORK) ? ' active' : '';
                $_right = (isset($modules['slideshow'])) ? '' : ' side-right';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img' . $_right . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_SOCIAL_NETWORK . '" title="' . $lang['srv_vrsta_survey_type_8'] . '">' . $lang['srv_vrsta_survey_type_8'] . '</a></li>';
            }
            #slideshow
            if (isset($modules['slideshow'])) {
                $_active = ($_GET['a'] == A_SLIDESHOW) ? ' active' : '';
                echo '<li class="space"></li>';
                echo '<li><a class="no-img side-right' . $_active . '" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=' . A_SLIDESHOW . '" title="' . $lang['srv_vrsta_survey_type_9'] . '">' . $lang['srv_vrsta_survey_type_9'] . '</a></li>';
            }

            echo '</ul>';
        }

        # podzavihek: status
        if ($this->first_action == NAVI_STATUS
			|| $this->first_action == 'para_graph'
			|| $this->first_action == 'nonresponse_graph'
			|| $this->first_action == 'AAPOR'
			|| $this->first_action == 'langStatistic'
			|| $this->first_action == 'usable_resp'
			|| $this->first_action == 'speeder_index'
			|| $this->first_action == 'reminder_tracking') {

            echo '<ul class="secondNavigation">';

            # dashboard
            echo '<li>';
            echo '<a class="no-img single' . ($_GET['a'] == A_REPORTI ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&a=' . A_REPORTI . '" title="' . $lang['srv_status_osnovni'] . '">';
            echo $lang['srv_status_summary'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';

            # parapodatki (browser, os, js...) - volitve imajo to ugasnjeno
            if(!SurveyInfo::getInstance()->checkSurveyModule('voting')) {
                echo '<li>';
                echo '<a class="no-img' . ($_GET['a'] == A_PARA_GRAPH ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_PARA_GRAPH . '" title="' . $lang['srv_metapodatki'] . '">';
                echo $lang['srv_metapodatki'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

            # neodgovori in uporabnost enot
            //if ($admin_type === '0') {
            # non-responses
            echo '<li>';
            echo '<a class="no-img' . ($_GET['a'] == A_NONRESPONSE_GRAPH ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_NONRESPONSE_GRAPH . '" title="' . $lang['srv_para_neodgovori'] . '">';
            echo $lang['srv_para_neodgovori'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';
            //}

            # usable respondents
            echo '<li>';
            echo '<a class="no-img' . ($_GET['a'] == A_USABLE_RESP ? ' active' : '') . '"'
                . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_USABLE_RESP . '" title="' . $lang['srv_usable_respondents'] . '">';
            echo $lang['srv_usable_respondents'] . '</a>';
            echo '</li>';
            echo '<li class="space"></li>';

            # kakovost resp - V DELU - ZAENKRAT SAMO ADMINI
			if ($admin_type === '0') {
				echo '<li>';
				echo '<a class="no-img' . ($_GET['a'] == A_KAKOVOST_RESP ? ' active' : '') . '"'
					. ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_KAKOVOST_RESP . '" title="' . $lang['srv_kakovost'] . '">';
				echo $lang['srv_kakovost'] . '</a>';
				echo '</li>';
				echo '<li class="space"></li>';
			}

			# speeder index - V DELU - ZAENKRAT SAMO ADMINI
			if ($admin_type === '0') {
				echo '<li>';
				echo '<a class="no-img' . ($_GET['a'] == A_SPEEDER_INDEX ? ' active' : '') . '"'
					. ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_SPEEDER_INDEX . '" title="' . $lang['srv_speeder_index'] . '">';
				echo $lang['srv_speeder_index'] . '</a>';
				echo '</li>';
				echo '<li class="space"></li>';
			}

            # text analysis
            if ($admin_type === '0' || $admin_type === '1') {
                echo '<li>';
                echo '<a class="no-img' . ($_GET['a'] == A_TEXT_ANALYSIS ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TEXT_ANALYSIS . '" title="' . $lang['srv_text_analysis'] . '">';
                echo $lang['srv_text_analysis'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

			# IP analiza lokacij - gorenje ima to ugasnjeno, volitve imajo tudi ugasnjeno
            if (!Common::checkModule('gorenje') && !SurveyInfo::getInstance()->checkSurveyModule('voting')) {
                echo '<li>';
                echo '<a class="no-img' . ($_GET['a'] == A_GEOIP_LOCATION ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_GEOIP_LOCATION . '" title="' . $lang['srv_geoip_location'] . '">';
                echo $lang['srv_geoip_location'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

            # Analize urejanja - V DELU - ZAENKRAT SAMO ADMINI
            if ($admin_type === '0') {
                echo '<li>';
                echo '<a class="no-img' . ($_GET['a'] == A_EDITS_ANALYSIS ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_EDITS_ANALYSIS . '" title="' . $lang['srv_edits_analysis'] . '">';
                echo $lang['srv_edits_analysis'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

			# reminder tracking - pokazi, ce je admin in so vklopljeni napredni parapodatki
            $survey_track_reminders = SurveySetting::getInstance()->getSurveyMiscSetting('survey_track_reminders');
            if ($survey_track_reminders == '') $survey_track_reminders = 0;    
            if (($admin_type === '0' || $admin_type === '1') && SurveyInfo::getInstance()->checkSurveyModule('advanced_paradata')) {    
                echo '<li>';
				echo '<a class="no-img' . ($_GET['a'] == A_REMINDER_TRACKING ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_REMINDER_TRACKING . '&m='. A_REMINDER_TRACKING_RECNUM .'" title="' . $lang['srv_reminder_tracking'] . '">';
                echo $lang['srv_reminder_tracking'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

            # ul evalvacija
            if (Common::checkModule('evalvacija') == '1') {
                echo '<li>';
                echo '<a class="no-img' . ($_GET['a'] == A_UL_EVALVATION ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_UL_EVALVATION . '" title="UL evalvacije">';
                echo 'UL evalvacije</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

            # AAPOR
            #aapor naj bo viden samo če so vabila
            if (SurveyInfo::getSurveyColumn('user_base') == 1 || SurveyInfo::getInstance()->checkSurveyModule('email')) {
                echo '<li>';
                echo '<a class="no-img' . ($_GET['a'] == 'AAPOR' ? ' active' : '') . '"'
                    . ' href="index.php?anketa=' . $this->anketa . '&amp;a=AAPOR&m=aapor1" title="' . $lang['srv_aapor'] . '">';
                echo $lang['srv_aapor'] . '</a>';
                echo '</li>';
                echo '<li class="space"></li>';
            }

            # langStatistic
            #langStatistic naj bo viden samo če imamo različne jezike in nimamo volitev
            if (!Common::checkModule('gorenje') && !SurveyInfo::getInstance()->checkSurveyModule('voting')) {
                
                $qry_string = "SELECT language FROM srv_user WHERE ank_id = '" . $this->anketa . "' AND preview = '0' AND deleted='0' group by language";
                $qry = (sisplet_query($qry_string));
                $cntLang = mysqli_num_rows($qry);

                if ($cntLang > 1) {
                    echo '<li>';
                    echo '<a class="no-img' . ($_GET['a'] == 'langStatistic' ? ' active' : '') . '"'
                        . ' href="index.php?anketa=' . $this->anketa . '&amp;a=langStatistic" title="' . $lang['srv_languages_statistics'] . '">';
                    echo $lang['srv_languages_statistics'] . '</a>';
                    echo '</li>';
                    echo '<li class="space"></li>';
                }
            }

            echo '</ul>';
        }

        # še sinle elementi za posebne linke:
        #quicksettings
        if ($_GET['a'] == 'quicksettings') {
            echo '<ul class="secondNavigation">';
            echo '<li><a class="no-img single active" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&a=' . A_QUICK_SETTINGS . '" title="' . $lang['srv_settings_quick'] . '">' . $lang['srv_settings_quick'] . '</a></li>';
            echo '</ul>';
        }


        if ($_GET['a'] == 'data' && $_GET['m'] == 'monitoring') {
            echo '<ul class="secondNavigation">';
            echo '<li><a class="no-img single active" href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&amp;a=data&m=monitoring" title="' . $lang['srv_monitoring'] . '">' . $lang['srv_monitoring'] . '</a></li>';
            echo '</ul>';
        }


        # Ikonce za pdf rtf word
        if($hierarhija_type < 5) {

			echo '<div id="secondNavigation_links">';
            # Ikonce za delete, copy ....
            $d = new Dostop();
            if ($d->checkDostopAktiven()) {
                $this->displaySecondNavigationLinks(1);
            }
            $this->displaySecondNavigationLinks(0);
            echo '</div>';

            echo '</div>'; #<div class="secondNavigation" >
        }
    }

    // Prikazemo dodaten tretji nivo navigacije po potrebi
    private function thirdNavigation(){
        global $global_user_id;

        $podstran = '';

        if($_GET['a'] == 'theme-editor'){
            $podstran = 'theme-editor';
        }
        elseif($_GET['a'] == A_COLLECT_DATA_EXPORT){
            $podstran = 'export';
        }
        elseif($_GET['a'] == A_HIERARHIJA && ($_GET['m'] == M_ANALIZE)){
            $podstran = 'means';
        }
        // Zavihki TESTIRANJE
        elseif($_GET['a'] == 'testiranje' && $_GET['m'] == 'predvidenicas'){
            $podstran = 'ocena_trajanja';
        }
        elseif($_GET['a'] == 'testiranje' && $_GET['m'] == 'cas'){
            $podstran = 'dejanski_casi';
        }
        elseif($_GET['a'] == 'komentarji_anketa'){
            $podstran = 'komentarji_anketa';
        }
        elseif($_GET['a'] == 'komentarji'){
            $podstran = 'komentarji';
        }
        // Zavihki PODATKI
        elseif($_GET['a'] == 'data'){

            if(!isset($_GET['m']) || $_GET['m'] == 'view'){
                $podstran = 'data';
            }
            elseif($_GET['m'] == 'quick_edit'){
                $podstran = 'quick_edit';
            }  
            elseif($_GET['m'] == 'variables'){
                $podstran = 'variables';
            }
        } 
        // Zavihki ANALIZA
        elseif($_GET['a'] == 'analysis' && $_GET['m'] != 'anal_arch' && $_GET['m'] != 'vizualizacija'){

            if (isset($_GET['podstran'])) {
                $podstran = $_GET['podstran'];
            } 
            else if (isset($_POST['podstran'])) {
                $podstran = $_POST['podstran'];
            } 
            else if (isset($_GET['m'])) {
                $podstran = $_GET['m'];
            } 
            else {
                $podstran = M_ANALYSIS_SUMMARY;
            }
        }
        // Zavihki STATUS
        elseif($_GET['a'] == 'reporti'){
            $podstran = 'status';
        }
        elseif($_GET['a'] == 'usable_resp'){
            $podstran = 'usable_resp';
        }
        elseif($_GET['a'] == 'AAPOR'){
            $podstran = 'aapor';
        }
        elseif($_GET['a'] == 'reminder_tracking'){
            $podstran = 'reminder_tracking';
        }
        elseif($_GET['a'] == 'para_graph'){
            $podstran = 'para_graph';
        }
        elseif($_GET['a'] == 'nonresponse_graph'){
            $podstran = 'para_analysis_graph';
        }

        // Izrisemo ustrezen meni, ce je prisoten
        if($podstran != ''){
            SurveyStatusProfiles::Init($this->anketa, $global_user_id);

            $SSH = new SurveyStaticHtml($this->anketa);
            $SSH->displayTopSettings($podstran);
        }
    }

    // Pohendla prikazovanje vsebine ankete
    private function displayAnketa(){
        global $global_user_id;    
        
        // Prikazemo meni na levi po potrebi glede na podstran
        $this->displayLeftMenu();
  
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess()){

            $userAccess->displayNoAccess();
            return;
        }

        // Prikazemo glavni del urejanja ankete    
        $this->displayAnketaMain();     
    }

    // Prikazemo glavni del urejanja ankete
    private function displayAnketaMain(){
        
        echo '<div class="anketa_edit_main">';

        // Ustrezno redirectamo anketo na ustrezno stran
        if ($_GET['a'] == 'redirectLink') {

            // Preverimo, če gre za anketo, ki vsebuje hierarhijo
            unset($_SESSION['hierarhija'][$this->anketa]);

            if(SurveyInfo::checkSurveyModule('hierarhija', $this->anketa))
                $_SESSION['hierarhija'][$this->anketa]['type'] = \Hierarhija\HierarhijaHelper::preveriTipHierarhije($this->anketa);

            $this->redirectLink();
        }
        // Kreira arhiv
        elseif ($_GET['a'] == 'backup_create') {
            $sas = new SurveyAdminSettings();
            $sas->backup_create();
        } 
        // Skopira anketo na drugo stran
        elseif ($_GET['a'] == 'anketa_copy') {
            $sas = new SurveyAdminSettings();
            $sas->anketa_copy();
        } 
        // Restore ankete
        elseif ($_GET['a'] == 'backup_restore') {
            $sas = new SurveyAdminSettings();
            $sas->backup_restore();
        }  
        // Prikazemo arhive
        elseif(in_array($_GET['a'], ['arhivi', 'tracking', 'tracking-hierarhija'])){
            $this->displayAnketaTabArhiv();    
        }
        // Prikazemo vsebino glede na zavihek
        else{
            switch($this->first_action){

                // Zavihek status
                case NAVI_STATUS:
                    $this->displayAnketaTabStatus();
                break;
    
                // Zavihek urejanje
                case NAVI_UREJANJE:
                    $this->displayAnketaTabUrejanje();
                break;
    
                // Zavihek testiranje
                case NAVI_TESTIRANJE:
                    $this->displayAnketaTabTestiranje();
                break;
    
                // Zavihek objava
                case NAVI_OBJAVA:
                    $this->displayAnketaTabObjava();
                break;
    
                // Zavihek podatki
                case NAVI_RESULTS:
                    $this->displayAnketaTabPodatki();
                break;
    
                // Zavihek analize
                case NAVI_ANALYSIS:
                    $this->displayAnketaTabAnalize();
                break;
    
                // Zavihek hierarhija
                case NAVI_HIERARHIJA:
                    $this->displayAnketaTabHierarhija();
                break;
    
                default:
                break;
            }
        }

        echo '</div>';
    }

    // Prikazemo levi meni po potrebi
    private function displayLeftMenu(){

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        // Levi meni v nastavitvah ankete
        if ($_GET['a'] == 'nastavitve'
                || $_GET['a'] == 'urejanje'
                || $_GET['a'] == 'alert'
                || $_GET['a'] == 'dostop'
                || $_GET['a'] == 'jezik'
                || $_GET['a'] == 'osn_pod'
                || $_GET['a'] == 'piskot'
                || $_GET['a'] == 'trajanje'
                || $_GET['a'] == 'forma'
                || $_GET['a'] == 'metadata'
                || $_GET['a'] == 'mobile_settings'
                || $_GET['a'] == A_PRIKAZ
                || $_GET['a'] == A_MISSING
                || $_GET['a'] == A_SKUPINE
                || $_GET['a'] == A_EXPORTSETTINGS
                || $_GET['a'] == A_GDPR
                || $_GET['a'] == 'uporabnost'
                || ($_GET['a'] == 'hierarhija_superadmin' && $hierarhija_type < 5)
                || $_GET['a'] == 'kviz'
                || $_GET['a'] == 'voting'
                || $_GET['a'] == 'slideshow'
                || $_GET['a'] == 'vnos'
                || $_GET['a'] == A_TELEPHONE
                || $_GET['a'] == A_CHAT
                || $_GET['a'] == A_PANEL
                || $_GET['a'] == A_FIELDWORK
                || $_GET['a'] == A_MAZA
                || $_GET['a'] == A_WPN
                || $_GET['a'] == 'social_network'
                || $_GET['a'] == A_360
                || $_GET['a'] == A_360_1KA
                || $_GET['a'] == 'evoli'
                || $_GET['a'] == 'evoli_teammeter'
                || $_GET['a'] == 'evoli_quality_climate'
                || $_GET['a'] == 'evoli_teamship_meter'
                || $_GET['a'] == 'evoli_organizational_employeeship_meter'
                || $_GET['a'] == 'evoli_employmeter'
                || $_GET['a'] == 'mfdps'
                || $_GET['a'] == 'borza'
                || $_GET['a'] == 'mju'
                || $_GET['a'] == 'excell_matrix'
                || $_GET['a'] == 'advanced_paradata'
                || $_GET['a'] == 'json_survey_export'
            ){

            echo '<div class="anketa_edit_left" '.($this->isAnketar ? ' style="display:none;"' : '').'>';

            echo '<div id="globalSetingsLinks" class="globalSetingsLinks baseSettings">';
            $this->showGlobalSettingsLinks();
            echo '</div>';

            if ($this->survey_type > 1) {
                echo '<div id="globalSetingsLinks" class="globalSetingsLinks advancedModules" '.($this->isAnketar ? ' style="display:none;"' : '').'>';
                $this->showAdvancedModulesLinks();
                echo '</div>';
            }

            echo '<div id="globalSetingsLinks" class="globalSetingsLinks aditionalSettings" '.($this->isAnketar ? ' style="display:none;"' : '').'>';
            $this->showAdditionalSettingsLinks();
            echo '</div>';

            echo '</div>';
        }
        // Uvoz podatkov levi meni
        elseif ($_GET['a'] == A_COLLECT_DATA && in_array($_GET['m'], ['append', 'merge', 'upload_xls', 'append_xls'])) {

            echo '<div class="anketa_edit_left">';

            echo '<div id="globalSetingsLinks" class="globalSetingsLinks dataImport">';
            $this->showImportLinks();
            echo '</div>';

            echo '</div>';
        } 
        // Kalkulacija podatkov levi meni
        elseif ($_GET['a'] == A_COLLECT_DATA && in_array($_GET['m'], ['calculation', 'coding_auto', 'coding', M_COLLECT_DATA_RECODING])) {

            echo '<div class="anketa_edit_left">';

            echo '<div id="globalSetingsLinks" class="globalSetingsLinks dataCalculate">';
            $this->showcalculationsLinks();
            echo '</div>';

            echo '</div>';
        } 
        // Izvoz podatkov levi meni
        elseif ($_GET['a'] == A_COLLECT_DATA_EXPORT) {
            echo '<div class="anketa_edit_left">';

            echo '<div id="globalSetingsLinks" class="globalSetingsLinks dataExport">';
            $this->showExportLinks();
            echo '</div>';

            echo '</div>';
        } 
        // Arhiv levi meni
        elseif(in_array($_GET['a'], ['arhivi', 'tracking', 'tracking-hierarhija']) || in_array($_GET['m'], ['anal_arch', 'view_archive'])){

            echo '<div class="anketa_edit_left">';

            echo '<div id="globalSetingsLinks" class="globalSetingsLinks archive">';
            $SSH = new SurveyStaticHtml($this->anketa);
            $SSH->displayArchiveNavigation();
            echo '</div>';

            echo '</div>';
        }
    }

    private function displayAnketaTabStatus(){
        
        // Osnovni statusi
        if ($_GET['a'] == A_REPORTI) { 
            Common::deletePreviewData($this->anketa);

            $ss = new SurveyStatistic();
            $ss->Init($this->anketa);

            echo '	<div id="surveyStatistic">';
            $ss->Display();
            echo '	</div>';
        } 
        // prikaze reporte
        elseif ($_GET['a'] == 'AAPOR') { 
            $ss = new SurveyStatistic();
            $ss->Init($this->anketa);

            echo '	<div id="surveyStatistic">';
            $ss->DisplayAaporCalculations();
            echo '	</div>';
        } 
        // prikaze grafe neodgovorov
        elseif ($_GET['a'] == A_NONRESPONSE_GRAPH) { 
            echo '	<div id="surveyNonresponse">';
            $SPA = new SurveyParaAnalysis($this->anketa);
            $SPA->DisplayGraph();
            echo '	</div>';
        } 
        // prikaze stevilo neodgovorov za posamezne respondente
        elseif ($_GET['a'] == A_USABLE_RESP) { 
            echo '	<div id="surveyUsableResp">';
            $SUR = new SurveyUporabnost($this->anketa);
            $SUR->displayUporabnost();
            echo '	</div>';
        } 
        // prikaze modul kakovost
        elseif ($_GET['a'] == A_KAKOVOST_RESP) { 
            echo '	<div id="surveyKakovostResp">';
            $SUR = new SurveyKakovost($this->anketa);
            $SUR->displayKakovost();
            echo '	</div>';
        } 
        // Prikaze analizo hitrosti respondenta
        elseif ($_GET['a'] == A_SPEEDER_INDEX) { 
            echo '	<div id="surveySpeederIndex">';
            $SUR = new SurveySpeedIndex($this->anketa);
            $SUR->displaySpeedIndex();
            echo '	</div>';
        } 
        // prikaze stevilo znakov v anketi, stevilo besed...
        elseif ($_GET['a'] == A_TEXT_ANALYSIS) { 
            echo '	<div id="surveyTextAnalysis">';
            $STA = new SurveyTextAnalysis($this->anketa);
            $STA->displayTable();
            echo '	</div>';
        } 
        // analize editiranja
        elseif ($_GET['a'] == A_EDITS_ANALYSIS) { 
            $sea = new SurveyEditsAnalysis($this->anketa);
            echo '<div id="surveyEditsAnalysis">';
            $sea->displayTable();
            echo '</div>';
        }
        // prikaze analizo lokacij na podlagi ip stevilk
        elseif ($_GET['a'] == A_GEOIP_LOCATION) { 
            echo '	<div id="surveyGeoIPLocation">';
            $STA = new SurveyGeoIP($this->anketa);
            $STA->displayData();
            echo '	</div>';
        } 
        // prikaze analizo opozoril
        elseif ($_GET['a'] == A_REMINDER_TRACKING) { 
            echo '	<div id="surveyReminderTracking">';
            $SRT = new SurveyReminderTracking($this->anketa);
            $SRT->displayTable();
            echo '	</div>';
        } 
        // prikaze analizo anket za evalvacijo (ul)
        elseif ($_GET['a'] == A_UL_EVALVATION) { 
            $EVAL = new Evalvacija($this->anketa);
            $EVAL->displayStats();
        }
        // prikaze grafe parapodatkov (js, device type, browser...)
        elseif ($_GET['a'] == A_PARA_GRAPH) { 
            echo '	<div id="surveyParaGraph">';
            $SPG = new SurveyParaGraph($this->anketa, $skipInit = true);
            $SPG->DisplayParaGraph($this->anketa);
            echo '	</div>';
        }
        // prikaze reporte
        elseif ($_GET['a'] == 'langStatistic') { 
            $ss = new SurveyStatistic();
            $ss->Init($this->anketa);
            echo '	<div id="surveyStatistic">';
            $ss->DisplayLangStatistic();
            echo '	</div>';
        } 
    }

    private function displayAnketaTabUrejanje(){

        // Prikaze branching
        if ($_GET['a'] == 'branching' || !isset($_GET['a']) || $_GET['a'] == '') {
            $bn = new Branching($this->anketa);
            $bn->display_new();

            return;
        }

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        echo '<div id="globalSetingsList" '.(($_GET['a'] != 'prevajanje' && $_GET['a'] != 'prevajanje2' && $_GET['a'] != A_TEMA && $_GET['a'] != 'theme-editor') ? '' : ' class="full_width"').'>';
         
        // Prikaze nastavitve
        if ($_GET['a'] == 'nastavitve'
            || $_GET['a'] == 'urejanje'
            || $_GET['a'] == 'dostop'
            || $_GET['a'] == 'jezik'
            || $_GET['a'] == 'osn_pod'
            || $_GET['a'] == 'piskot'
            || $_GET['a'] == 'trajanje'
            || $_GET['a'] == 'forma'
            || $_GET['a'] == 'metadata'
            || $_GET['a'] == 'mobile_settings'
            || $_GET['a'] == A_PRIKAZ
            || $_GET['a'] == A_EXPORTSETTINGS
            || $_GET['a'] == A_GDPR
        ) {     
            $sas = new SurveyAdminSettings();
            $sas->anketa_nastavitve_global();    
        }
        elseif ($_GET['a'] == 'prevajanje' || $_GET['a'] == 'prevajanje2') {
            $p = new Prevajanje($this->anketa);
            $p->prevajaj();
        } 
        elseif ($_GET['a'] == 'tema') {
            $st = new SurveyTheme($this->anketa);
            $st->displayGroup();
        } 
        elseif ($_GET['a'] == 'theme-editor') {
            $ste = new SurveyTheme($this->anketa);
            $ste->displayEditing();
        } 
        // nastavitve manjkajočih vrednosti za anketos
        elseif ($_GET['a'] == A_MISSING) { 
            $smv = new SurveyMissingValues($this->anketa);
            $smv->displayMissingForSurvey();
        } 
        // skupine respondentov
        elseif ($_GET['a'] == A_SKUPINE) { 
            $ss = new SurveySkupine($this->anketa);
            $ss->displayEdit();
        } 
        // prikaze nastavitve za obvescanje
        elseif ($_GET['a'] == 'alert') {
            $sas = new SurveyAdminSettings();
            $sas->alert_nastavitve();
        }
        // Prikaze urejanje teme
        elseif ($_GET['a'] == 'edit_css') {
            $sas = new SurveyAdminSettings();
            $sas->anketa_editcss();
        } 
        // Napredni moduli
        elseif ($_GET['a'] == 'uporabnost'
            || ($_GET['a'] == 'hierarhija_superadmin' && $hierarhija_type < 5)
            || $_GET['a'] == 'kviz'
            || $_GET['a'] == 'voting'
            || $_GET['a'] == 'slideshow'
            || $_GET['a'] == 'vnos'
            || $_GET['a'] == A_TELEPHONE
            || $_GET['a'] == A_CHAT
            || $_GET['a'] == A_PANEL
            || $_GET['a'] == A_FIELDWORK
            || $_GET['a'] == A_MAZA
            || $_GET['a'] == A_WPN
            || $_GET['a'] == 'social_network'
            || $_GET['a'] == A_360
            || $_GET['a'] == A_360_1KA
            || $_GET['a'] == 'evoli'
            || $_GET['a'] == 'evoli_teammeter'
            || $_GET['a'] == 'evoli_quality_climate'
            || $_GET['a'] == 'evoli_teamship_meter'
            || $_GET['a'] == 'evoli_organizational_employeeship_meter'
            || $_GET['a'] == 'evoli_employmeter'
            || $_GET['a'] == 'mfdps'
            || $_GET['a'] == 'borza'
            || $_GET['a'] == 'mju'
            || $_GET['a'] == 'excell_matrix'
            || $_GET['a'] == 'advanced_paradata'
            || $_GET['a'] == 'json_survey_export'
        ) {
            $sas = new SurveyAdminSettings();
            $sas->showAdvancedModules();
        } 
        
        echo '<br class="clr">';
        
        echo '</div>'; 
    }

    private function displayAnketaTabTestiranje(){
        if ($_GET['a'] == 'komentarji') {
            $sas = new SurveyAdminSettings();
            $sas->testiranje_komentarji();
        } 
        elseif ($_GET['a'] == 'komentarji_anketa') {
            $sas = new SurveyAdminSettings();
            $sas->testiranje_komentarji_anketa();
        }
        elseif (trim($_GET['m']) == '' || $_GET['m'] == 'diagnostika') {
            $sd = new SurveyDiagnostics($this->anketa);
            $sd->doDiagnostics();
            $sd->displayDiagnostic();
        } 
        elseif ($_GET['m'] == 'predvidenicas' || $_GET['m'] == 'cas') {
            $sas = new SurveyAdminSettings();
            $sas->tabTestiranje();
        } 
        elseif($_GET['a'] == A_TESTIRANJE) {
            $sas = new SurveyAdminSettings();
            $sas->tabTestiranje();
        } 
    }

    private function displayAnketaTabObjava(){

        if ($_GET['a'] == A_VABILA) {
            echo '<div id="vabila">';
            $sas = new SurveyAdminSettings();
            $sas->anketa_vabila();
            echo '</div>';
        } 
        elseif ($_GET['a'] == A_INVITATIONS) {
            $SI = new SurveyInvitationsNew($this->anketa);
            $SI->action($_GET['m']);
        }
    }

    private function displayAnketaTabPodatki(){
        
        // Izvoz podatkov
        if ($_GET['a'] == A_COLLECT_DATA_EXPORT) {

            if ($_GET['m'] == 'excel_xls_mfdps') {
                $mfdps = new SurveyMFDPS($this->anketa);

                if (isset($_GET['n']) && $_GET['n'] == 'izv')
                    $mfdps->executeExportIzvajalci();
                else
                    $mfdps->executeExportPredmeti();
            } 
            elseif ($_GET['m'] == 'excel_xls_mju') {
                $mju = new SurveyMJU($this->anketa);
                $mju->executeExport();
            } 
            else {

                echo '<div id="globalSetingsList">';
                Common::deletePreviewData($this->anketa);
                $sas = new SurveyAdminSettings();
                $sas->displayIzvozi();
                echo '</div>';

                echo '<br class="clr">';
            }
        } 
        elseif ($_GET['m'] == '' || $_GET['m'] == 'view' || $_GET['m'] == 'edit' || $_GET['m'] == 'print' || $_GET['m'] == 'monitoring') {
            Common::deletePreviewData($this->anketa);

            echo '<div id="analiza_data">';

            $SDS = new SurveyDataDisplay($this->anketa);
            $SDS->displayFilters();
            $SDS->displayVnosiHTML();

            echo '</div>'; // div_analiza_data
        } 
        elseif ($_GET['m'] == M_COLLECT_DATA_VARIABLE_VIEW) {

            $vv = VariableView::instance();
            $vv->init($this->anketa);
            $vv->displayVariables();
        } 
        elseif ($_GET['m'] == 'calculation') {
            $spp = new SurveyPostProcess($this->anketa);
            $spp->displayTab();
        } 
        elseif ($_GET['m'] == 'coding_auto') {
            $spp = new SurveyPostProcess($this->anketa);
            $spp->displayCodingAuto();
        } 
        elseif ($_GET['m'] == 'coding') {
            $spp = new SurveyPostProcess($this->anketa);
            $spp->displayCoding();
        } 
        elseif ($_GET['m'] == M_COLLECT_DATA_RECODING) {

            $SR = new SurveyRecoding($this->anketa);
            $SR->DisplaySettings();
        } 
        elseif ($_GET['m'] == 'quick_edit') {
            Common::deletePreviewData($this->anketa);

            echo '<div id="analiza_data" class="quick_edit_container">';
            $SDS = new SurveyDataDisplay($this->anketa);
            $SDS->displayQuickEditFilters();
            $SDS->displayQuickEdit();
            echo '</div>'; // div_analiza_data
        } 
        elseif ($_GET['m'] == 'append' || $_GET['m'] == 'merge') {

            $spp = new SurveyAppendMerge($this->anketa);
            $spp->display($_GET['m'] == 'merge' ? true : false);
        } 
        elseif ($_GET['m'] == 'upload_xls') {

            $spp = new SurveyAppendMerge($this->anketa);
            $spp->upload_xls();
        } 
        elseif ($_GET['m'] == 'append_xls') {

            $spp = new SurveyAppendMerge($this->anketa);
            $spp->append_xls();
        } 
        elseif ($_GET['m'] == 'evoli') {

            // Posebno PDF porocilo za Evoli
            // Dobimo usr_id za katerega pripravljamo porocilo
            $usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0;
            $evoli = new SurveyEvoli($this->anketa);
            $evoli->executeExport($usr_id);
        } 
        elseif ($_GET['m'] == 'evoli_employmeter') {

            // Posebno PDF porocilo za Evoli
            // Dobimo usr_id za katerega pripravljamo porocilo
            $usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0;
            $em = new SurveyEmployMeter($this->anketa);
            $em->executeExport($usr_id);
        } 
        elseif ($_GET['m'] == 'mfdps') {

            // Posebno PDF porocilo za MFDPS
            // Dobimo usr_id za katerega pripravljamo porocilo
            $usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0;
            $mfdps = new SurveyMFDPS($this->anketa);
            $mfdps->executePDFExport($usr_id);
        } 
        elseif ($_GET['m'] == 'borza') {

            // Posebni grafi za BORZA
            // Dobimo usr_id za katerega pripravljamo porocilo
            $usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0;
            $borza = new SurveyBORZA($this->anketa);
            $borza->executeChartExport($usr_id);
        }
    }

    private function displayAnketaTabAnalize(){

        $this->podstran = isset($_GET['m']) ? $_GET['m'] : M_ANALYSIS_STATISTICS;

        // Povprečje pri hierarhiji in onemogočene ostale možnosti
        if ($this->podstran == M_ANALYSIS_MEANS_HIERARHY || SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
            
            SurveyAnalysis::Init($this->anketa);

            $HA = new HierarhijaAnalysis($this->anketa);
            $HA->Display();
        }
        // V kolikor gre za običajno analizo potem prikažemo vse opcije
        else {
            switch ($this->podstran) {
                
                case M_ANALYSIS_LINKS : // linki za vprašalnik
                    SurveyAnalysis::Init($this->anketa);
                    SurveyAnalysis::DisplayReportsLinks();
                    break;
                
                case M_ANALYSIS_CREPORT : // porocilo po meri

                    SurveyAnalysis::Init($this->anketa);
                    SurveyAnalysis::DisplayFilters();
                    echo '<div id="anketa_custom_report">';
                    $SCM = new SurveyCustomReport($this->anketa);
                    $SCM->displayReport();
                    echo '</div>';

                    break;

                case M_ANALYSIS_MEANS:
                    SurveyAnalysis::Init($this->anketa);

                    $SM = new SurveyMeans($this->anketa);
                    $SM->Display();

                    break;

                case M_ANALYSIS_TTEST :
                    SurveyAnalysis::Init($this->anketa);

                    $STT = new SurveyTTest($this->anketa);
                    $STT->Display();

                    break;

                case M_ANALYSIS_BREAK :
                    $SB = new SurveyBreak($this->anketa);
                    $SB->Display();

                    break;

                case M_ANALYSIS_PARA :
                    $SPA = new SurveyParaAnalysis($this->anketa);
                    $SPA->Display();

                    break;

                case M_ANALYSIS_CHARTS :
                    SurveyChart::Init($this->anketa);
                    SurveyChart::display();

                    break;

                case M_ANALYSIS_MULTICROSSTABS :
                    $SMC = new SurveyMultiCrosstabs($this->anketa);
                    $SMC->display();

                    break;

                // Vizualizacija (R modul)
                case M_ANALYSIS_VIZUALIZACIJA :
                    $sv = new SurveyVizualizacija($this->anketa);
                    $sv->display();

                    break;

                // 360 stopinjske analize (adecco)
                case M_ANALYSIS_360 :
                    $S360 = new Survey360($this->anketa);
                    $S360->displayReports();

                    break;

                // 360 stopinjske analize (1ka)
                case M_ANALYSIS_360_1KA :
                    $S360 = new Survey3601ka($this->anketa);
                    $S360->displayReports();

                    break;

                // HEATMAP
                case M_ANALYSIS_HEATMAP :
                    SurveyHeatMap::Init($this->anketa);
                    SurveyHeatMap::display($this->spremenljivka);

                    break;

                default:
                    if (isset($_GET['podstran'])) {
                        $podstran = $_GET['podstran'];
                    } else if (isset($_POST['podstran'])) {
                        $podstran = $_POST['podstran'];
                    } else if (isset($_GET['m'])) {
                        $podstran = $_GET['m'];
                    } else {
                        $podstran = M_ANALYSIS_SUMMARY;
                    }

                    SurveyAnalysis::Init($this->anketa);
                    SurveyAnalysis::DisplayFilters();
                    if ($_GET['m'] == M_ANALYSIS_CROSSTAB) {
                        echo '<br class="clr"/>';
                    }
                    echo '<div id="div_analiza_data" class="' . $podstran . '">';
                    SurveyAnalysis::Display();
                    echo '</div>'; // div_analiza_data

                    break;
            }
        }
    }

    private function displayAnketaTabArhiv(){

        echo '<div id="div_archive_content" ' . (in_array($_GET['a'], ['tracking', 'tracking-hierarhija'])  ? ' class="tracking"' : '') . '>';
        
        $sas = new SurveyAdminSettings();
        
        if ($_GET['m'] == 'data') {
            $sas->arhivi_data();

        } 
        else if ($_GET['m'] == 'testdata') {
            $sas->arhivi_testdata();

        } 
        else if ($_GET['m'] == 'survey' || $_GET['m'] == 'survey_data') {
            $sas->arhivi_survey();

        } 
        else if ($_GET['a'] == 'tracking') {
            if($_GET['d'] == 'download')
                return TrackingClass::init()->filter([20,21,22])->csvExport();

            TrackingClass::init()->filter([20,21,22])->trackingDisplay();

        } 
        else if ($_GET['a'] == 'tracking-hierarhija') {
            if($_GET['m'] == 'udelezenci') {
                if ($_GET['d'] == 'download')
                    return TrackingClass::init()->filter(22, true)->csvExport();

                return TrackingClass::init()->filter(22, true)->trackingDisplay();
            }

            if ($_GET['d'] == 'download')
                return TrackingClass::init()->filter([20,21], true)->csvExport();

            return TrackingClass::init()->filter([20,21], true)->trackingDisplay();

        } 
        else {
            $sas->arhivi();
        }

        echo '<br class="clr" />';
        echo '</div>';          
    }

    private function displayAnketaTabHierarhija(){

        echo '<div id="div_archive_navigation" style="width:75%;font-weight: bold;font-size:14px;">';
        $hir_nav = new \Hierarhija\Hierarhija($this->anketa);
        $hir_nav->displayHierarhijaNavigation();
        echo '</div>';


        echo '<div id="hierarhija-container" style="clear: both;">';

        $hierarhija = new \Hierarhija\Hierarhija($this->anketa);

        // m=uredi-sifrante
        if ($_GET['m'] == M_ADMIN_UREDI_SIFRANTE) {

            $hierarhija->hierarhijaSuperadminSifranti();
        } 
        elseif ($_GET['m'] == M_ADMIN_UVOZ_SIFRANTOV) {

            $hierarhija->hierarhijaSuperadminUvoz();
        } 
        elseif ($_GET['m'] == M_ANALIZE) {

            // V kolikor gre za poročila po meri
            if($_GET['r'] == 'custom'){
                $HC = new \Hierarhija\HierarhijaPorocilaClass($this->anketa);
                $HC->izvoz();
            }
            else{
                $HA = new HierarhijaAnalysis($this->anketa);
                $HA->Display();
            }
        } 
        elseif ($_GET['m'] == M_ADMIN_AKTIVACIJA) {

            $hierarhija->aktivacijaHierarhijeInAnkete();
        } 
        elseif ($_GET['m'] == M_HIERARHIJA_STATUS) {
            $hierarhija->statistikaHierjearhije();
        } 
        elseif($_GET['m'] ==  M_UREDI_UPORABNIKE && $_GET['izvoz'] == 1) {
            // za vse ostalo je uredi uporabnike - M_UREDI_UPORABNIKE
            \Hierarhija\HierarhijaIzvoz::getInstance($this->anketa)->csvIzvozVsehUporabnikov();
        } 
        else {
            // za vse ostalo je ure uredi uporabnike - M_UREDI_UPORABNIKE
            $hierarhija->izberiDodajanjeUporabnikovNaHierarhijo();
        }

        echo '</div>';

        echo '<br class="clr" />';
    }


    function showExportLinks()
    {
        global $lang;
        global $site_url;
        global $site_path;
        global $admin_type;
        global $global_user_id;

        if (trim($_GET['m']) == '') {
            $_GET['m'] = M_EXPORT_SPSS;
        }

        echo '<ul>';

        # SPSS
        echo '<li ' . ($_GET['m'] == M_EXPORT_SPSS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA_EXPORT . '&m=' . M_EXPORT_SPSS . '" title="' . $lang['srv_lnk_spss'] . '"><span>' . $lang['srv_lnk_spss'] . '</span></a></li> ';

        # SPSS SAV
        echo '<li ' . ($_GET['m'] == M_EXPORT_SAV ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA_EXPORT . '&m=' . M_EXPORT_SAV . '" title="' . $lang['srv_lnk_sav'] . '"><span>' . $lang['srv_lnk_sav'] . '</span></a></li> ';

        # EXCEL - XLS
        echo '<li ' . ($_GET['m'] == M_EXPORT_EXCEL_XLS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA_EXPORT . '&m=' . M_EXPORT_EXCEL_XLS . '" title="' . $lang['srv_lnk_excel_xls'] . '"><span>' . $lang['srv_lnk_excel_xls'] . '</span></a></li> ';

        # EXCEL - CSV
        echo '<li ' . ($_GET['m'] == M_EXPORT_EXCEL ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA_EXPORT . '&m=' . M_EXPORT_EXCEL . '" title="' . $lang['srv_lnk_excel'] . '"><span>' . $lang['srv_lnk_excel'] . '</span></a></li> ';

        # TXT
        echo '<li ' . ($_GET['m'] == M_EXPORT_TXT ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA_EXPORT . '&m=' . M_EXPORT_TXT . '" title="' . $lang['srv_lnk_txt'] . '"><span>' . $lang['srv_lnk_txt'] . '</span></a></li> ';

        echo '</ul>';
    }

    function showcalculationsLinks(){
        global $lang;
        global $site_url;
        global $site_path;
        global $admin_type;
        global $global_user_id;

        echo '<ul>';
        # kalkulacija - nove spremenljivke
        echo '<li ' . ($_GET['m'] == M_COLLECT_DATA_CALCULATION ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA . '&m=' . M_COLLECT_DATA_CALCULATION . '" title="' . $lang['srv_data_subnavigation_calculaion'] . '"><span>' . $lang['srv_data_subnavigation_calculaion'] . '</span></a></li> ';

        # kodiranje - coding
        echo '<li ' . ($_GET['m'] == 'coding_auto' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA . '&m=coding_auto" title="' . $lang['srv_auto_coding'] . '"><span>' . $lang['srv_auto_coding'] . '</span></a></li> ';

        # kodiranje - coding
        echo '<li ' . ($_GET['m'] == 'coding' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA . '&m=coding" title="' . $lang['srv_hand_coding'] . '"><span>' . $lang['srv_hand_coding'] . '</span></a></li> ';

        # rekodiranje - recoding
        echo '<li ' . ($_GET['m'] == M_COLLECT_DATA_RECODING || $_GET['m'] == M_COLLECT_DATA_RECODING_DASHBOARD ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA . '&m=' . M_COLLECT_DATA_RECODING . '" title="' . $lang['srv_data_subnavigation_recode'] . '"><span>' . $lang['srv_data_subnavigation_recode'] . '</span></a></li> ';

        echo '</ul>';
    }

    function showImportLinks(){
        global $lang;
        global $site_url;
        global $site_path;
        global $admin_type;
        global $global_user_id;

        echo '<ul>';

        # append
        echo '<li ' . ($_GET['m'] == 'append' || $_GET['m'] == 'upload_xls' || $_GET['m'] == 'append_xls' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA . '&m=append" title="' . $lang['srv_data_subnavigation_append'] . '"><span>' . $lang['srv_data_subnavigation_append'] . '</span></a></li> ';

        # merge
        echo '<li ' . ($_GET['m'] == 'merge' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA . '&m=merge" title="' . $lang['srv_data_subnavigation_merge'] . '"><span>' . $lang['srv_data_subnavigation_merge'] . '</span></a></li> ';

        echo '</ul>';
    }

    function showGlobalSettingsLinks(){
        global $lang;
        global $site_url;
        global $site_path;
        global $admin_type;
        global $global_user_id;

        $get = $_GET['a'];

        $d = new Dostop();

        echo '<h2>' . $lang['srv_survey_settings'] . '</h2>';

        echo '<ul>';

        # zavihek osnovni podatki
        echo '<li ' . ($get == A_SETTINGS || $get == A_OSNOVNI_PODATKI ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_OSNOVNI_PODATKI . '" title="' . $lang['srv_osnovniPodatki2'] . '"><span>' . $lang['srv_osnovniPodatki2'] . '</span></a></li> ';

        # prikaz pri mobilnikih
        echo '<li ' . ($get == A_MOBILESETTINGS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_MOBILESETTINGS . '" title="' . $lang['srv_mobile_settings'] . '"><span>' . $lang['srv_mobile_settings'] . '</span></a></li> ';

        # tema
        # echo '<li ' . ($get == A_TEMA || $get == 'edit_css' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        # echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a='.A_TEMA.'" title="' . $lang['srv_themes'] . '"><span>' . $lang['srv_themes'] . '</span></a></li> ';

        # prevajanje - jezik (standardne besede)
        echo '<li ' . ($get == A_JEZIK ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_JEZIK . '" title="' . $lang['srv_standardne_besede'] . '"><span>' . $lang['srv_standardne_besede'] . '</span></a></li> ';

        # zavihek dostop -> uredniki
        echo '<li ' . ($get == A_DOSTOP ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_DOSTOP . '" title="' . $lang['srv_global_settnig_access_admin'] . '"><span>' . $lang['srv_global_settnig_access_admin'] . '</span></a></li> ';

        # zavihek piškotek -> dostop respondenti
        echo '<li ' . ($get == A_COOKIE ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_COOKIE . '" title="' . $lang['srv_global_settnig_access_respondents'] . '"><span>' . $lang['srv_global_settnig_access_respondents'] . '</span></a></li> ';

        # zavihek obveščanje
        echo '<li class="nonhighlight">';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ALERT . '" title="' . $lang['srv_alert_link'] . '"><span ' . ($get == A_ALERT ? ' class="extended"' : '') . '>' . $lang['srv_alert_link'] . '</span></a>';
        $tab = (!$_GET['m']) ? 'complete' : $_GET['m'];
        #echo '<h2>'.$lang['srv_notification_settings'].'</h2>';
        echo '</li> ';
        echo '<ul id="sub_navi_alert"' . ($get == A_ALERT ? '' : ' class="displayNone"') . '>';
        echo '<li ' . ($get == A_ALERT && (!$tab || $tab == 'complete') ? ' class="highlightLineTab "' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=alert&amp;m=complete" title="' . $lang['srv_alert_completed'] . '"><span>' . $lang['srv_alert_completed'] . '</span></a></li> ';
        echo '<li ' . (($tab == 'expired') ? ' class="highlightLineTab "' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=alert&amp;m=expired" title="' . $lang['srv_alert_expired'] . '"><span>' . $lang['srv_alert_expired'] . '</span></a></li> ';
        echo '<li ' . (($tab == 'active') ? ' class="highlightLineTab "' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=alert&amp;m=active" title="' . $lang['srv_alert_active'] . '"><span>' . $lang['srv_alert_active'] . '</span></a></li> ';
        echo '<li ' . (($tab == 'delete') ? ' class="highlightLineTab "' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=alert&amp;m=delete" title="' . $lang['srv_alert_delete'] . '"><span>' . $lang['srv_alert_delete'] . '</span></a></li> ';
		// Gorenje tega nima, po novem to vidijo samo admini, ostali posiljajo preko default
		if (!Common::checkModule('gorenje') && $admin_type == '0'){
			echo '<li ' . (($tab == 'email_server') ? ' class="highlightLineTab "' : ' class="nonhighlight"') . '>';
			echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=alert&amp;m=email_server" title="' . $lang['srv_user_base_email_server_settings'] . '"><span>' . $lang['srv_user_base_email_server_settings'] . '</span></a></li> ';
		}		
		echo '</ul>';

        # zavihek trajanje
        echo '<li ' . ($get == A_TRAJANJE ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TRAJANJE . '" title="' . $lang['srv_settings_activity'] . '"><span>' . $lang['srv_settings_activity'] . '</span></a></li> ';

        # skupine
        echo '<li ' . ($get == A_SKUPINE ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_SKUPINE . '" title="' . $lang['srv_skupine'] . '"><span>' . $lang['srv_skupine'] . '</span></a></li> ';

        // Zavihek nastavitve komentarjev
        if ($this->survey_type > 1) {
            echo '<li ' . (($_GET['a'] == 'urejanje') ? ' class="highlightLineTab "' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=urejanje" title="' . $lang['srv_settings_komentarji'] . '"><span>' . $lang['srv_settings_komentarji'] . '</span></a></li> ';
        }

        if ($this->survey_type > 0) {
            // zavihek prikaz podatkov
            echo '<li ' . ($get == A_PRIKAZ ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_PRIKAZ . '" title="' . $lang['srv_prikaz_nastavitve'] . '"><span>' . $lang['srv_prikaz_nastavitve'] . '</span></a></li> ';

            # zavihek metapodatki
            echo '<li ' . ($get == A_METADATA ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_METADATA . '" title="' . $lang['srv_metadata'] . '"><span>' . $lang['srv_metadata'] . '</span></a></li> ';

            # zavihek manjkajoče vrednosti
            echo '<li ' . ($get == A_MISSING || $_GET['t'] == 'missingValues' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_MISSING . '" title="' . $lang['srv_missing_values'] . '"><span>' . $lang['srv_missing_values'] . '</span></a></li> ';
        }

        # PDF/RTF izvozi
        echo '<li ' . ($get == A_EXPORTSETTINGS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_EXPORTSETTINGS . '" title="' . $lang['srv_export_settings'] . '"><span>' . $lang['srv_export_settings'] . '</span></a></li> ';

        # GDPR
        echo '<li ' . ($get == A_GDPR ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_GDPR . '" title="' . $lang['srv_gdpr'] . '"><span>' . $lang['srv_gdpr'] . '</span></a></li> ';

        echo '</ul>';
    }

    function showAdditionalSettingsLinks()
    {
        global $lang;
        global $site_url;
        global $site_path;
        global $admin_type;
        global $global_user_id;

        $get = $_GET['a'];

        $d = new Dostop();

        echo '<h2>' . $lang['set_links'] . '</h2>';

        echo '<ul>';

        # tema
        echo '<li ' . ($get == A_TEMA || $get == 'edit_css' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TEMA . '" title="' . $lang['srv_themes'] . '"><span>' . $lang['srv_themes'] . '</span></a></li> ';

        # jezik
        echo '<li ' . ($get == A_PREVAJANJE ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_PREVAJANJE . '" title="' . $lang['lang'] . '"><span>' . $lang['lang'] . '</span></a></li> ';

        # arhivi
        echo '<li ' . ($get == A_ARHIVI || $get == A_TRACKING ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ARHIVI . '" title="' . $lang['srv_arhivi'] . '"><span>' . $lang['srv_arhivi'] . '</span></a></li> ';

        # Objava
        if ($d->checkDostopSub('publish')) {
            echo '<li ' . ($get == A_VABILA ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_VABILA . '" title="' . $lang['srv_vabila'] . '"><span>' . $lang['srv_vabila'] . '</span></a></li> ';
        }

        # HIERARHIJA
        if ($d->checkDostopSub('analyse') && !SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
            echo '<li ' . ($get == A_ANALYSIS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&m=sumarnik" title="' . $lang['srv_analiza'] . '"><span>' . $lang['srv_analiza'] . '</span></a></li> ';
        }

        if ($d->checkDostopSub('analyse') && SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
            echo '<li ' . ($get == A_ANALYSIS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_MEANS_HIERARHY . '" title="' . $lang['srv_analiza'] . '"><span>' . $lang['srv_analiza'] . '</span></a></li> ';
        }

        if ($this->user_role_cehck(U_ROLE_ADMIN)) {
            # Sistemske nastavitve
            echo '<li class="nonhighlight">';
            echo '<a href="index.php?a=nastavitve&m=system" title="' . $lang['srv_settingsSystem'] . '"><span>' . $lang['srv_settingsSystem'] . '</span></a></li> ';

            # Nastavitve uporabnika
            echo '<li class="nonhighlight">';
            echo '<a href="index.php?a=nastavitve&m=global_user_settings" title="' . $lang['srv_user_settings'] . '"><span>' . $lang['srv_user_settings'] . '</span></a></li> ';
        }

        echo '</ul>';
    }

    // Linki za napredne module
    private function showAdvancedModulesLinks()
    {
        global $lang;
        global $site_url;
        global $site_path;
        global $admin_type;
        global $global_user_id;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        $get = $_GET['a'];

        $row = SurveyInfo::getInstance()->getSurveyRow();

        $d = new Dostop();
        if ($d->checkDostopAktiven()) {

            $userAccess = UserAccess::getInstance($global_user_id);
            
            echo '<h2>' . $lang['srv_moduli'] . '</h2>';

            echo '<ul>';

            # Evalvacija
            echo '<li ' . ($get == A_UPORABNOST ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_UPORABNOST . '" title="' . $lang['srv_vrsta_survey_type_4'] . '" '.(!$userAccess->checkUserAccess($what='uporabnost') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_4'] . '</span></a></li> ';

            # Samoevalvacija hirarhija - hierarhija_superadmin
            //$row_user se zacasno uporabi tudi za modul MAZA
            $row_user = SurveyUserSetting::getInstance()->getUserRow();

            if (\Hierarhija\HierarhijaHelper::preveriDostop($this->anketa)) {
                echo '<li ' . ($get == A_HIERARHIJA_SUPERADMIN ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';

                if(SurveyInfo::getSurveyModules('hierarhija') > 1) {
                    echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&m='.M_HIERARHIJA_STATUS.'" title="' . $lang['srv_vrsta_survey_type_10'] . '"><span>' . $lang['srv_vrsta_survey_type_10'] . '</span></a></li> ';
                }else{
                    echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&m='.M_ADMIN_UREDI_SIFRANTE.'" title="' . $lang['srv_vrsta_survey_type_10'] . '"><span>' . $lang['srv_vrsta_survey_type_10'] . '</span></a></li> ';
                }
            }

            # Vnos vprasalnikov - premaknjeno kar v nastavitve -> dostop uredniki
            /*echo '<li ' . ($get == A_VNOS ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_VNOS . '" title="' . $lang['srv_vrsta_survey_type_5'] . '" '.(!$userAccess->checkUserAccess($what='vnos') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_5'] . '</span></a></li> ';*/

            # Kviz
            echo '<li ' . ($get == A_KVIZ ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
			echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a='.A_KVIZ.'" title="' . $lang['srv_vrsta_survey_type_6'] . '" '.(!$userAccess->checkUserAccess($what='kviz') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_6'] . '</span></a></li> ';

            # Volitve
            if ($admin_type == 0) {
                echo '<li ' . ($get == A_VOTING ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a='.A_VOTING.'" title="' . $lang['srv_vrsta_survey_type_18'] . '" '.(!$userAccess->checkUserAccess($what='voting') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_18'] . '</span></a></li> ';
            }

            # Socialna omrezja
            echo '<li ' . ($get == A_SOCIAL_NETWORK ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_SOCIAL_NETWORK . '" title="' . $lang['srv_vrsta_survey_type_8'] . '" '.(!$userAccess->checkUserAccess($what='social_network') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_8'] . '</span></a></li> ';

            # Prezentacija
            echo '<li ' . ($get == A_SLIDESHOW ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_SLIDESHOW . '" title="' . $lang['srv_vrsta_survey_type_9'] . '" '.(!$userAccess->checkUserAccess($what='slideshow') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_9'] . '</span></a></li> ';

            # Telefonska anketa
            echo '<li ' . ($get == A_TELEPHONE ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_TELEPHONE . '" title="' . $lang['srv_vrsta_survey_type_7'] . '" '.(!$userAccess->checkUserAccess($what='telephone') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_7'] . '</span></a></li> ';

			# Chat
			echo '<li ' . ($get == A_CHAT ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
			echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_CHAT . '" title="' . $lang['srv_vrsta_survey_type_14'] . '" '.(!$userAccess->checkUserAccess($what='chat') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_14'] . '</span></a></li> ';

			# Panel
            echo '<li ' . ($get == A_PANEL ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_PANEL . '" title="' . $lang['srv_vrsta_survey_type_15'] . '" '.(!$userAccess->checkUserAccess($what='panel') ? 'class="user_access_locked"' : '').'><span>' . $lang['srv_vrsta_survey_type_15'] . '</span></a></li> ';
			
			# Napredni parapodatki - samo admini oz. ce je vklopljen
			if ($admin_type == 0 || SurveyInfo::getInstance()->checkSurveyModule('advanced_paradata')) {
				echo '<li ' . ($get == A_ADVANCED_PARADATA ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
				echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_ADVANCED_PARADATA . '" title="' . $lang['srv_vrsta_survey_type_16'] . '"><span>' . $lang['srv_vrsta_survey_type_16'] . '</span></a></li> ';
			}
			
			# JSON izvoz ankete - samo admini oz. ce je vklopljen
			if ($admin_type == 0 || SurveyInfo::getInstance()->checkSurveyModule('json_survey_export')) {
				echo '<li ' . ($get == A_JSON_SURVEY_EXPORT ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
				echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_JSON_SURVEY_EXPORT . '" title="' . $lang['srv_vrsta_survey_type_17'] . '"><span>' . $lang['srv_vrsta_survey_type_17'] . '</span></a></li> ';
			}
			
            # Tablice, laptopi
            if ($admin_type == 0 || $admin_type == 1) {
                echo '<li ' . ($get == A_FIELDWORK ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_FIELDWORK . '" title="' . $lang['srv_vrsta_survey_type_13'] . '"><span>' . $lang['srv_vrsta_survey_type_13'] .' (beta)' . '</span></a></li> ';
            }
            
            # Aplikacija za anketirance
            //zaenkrat omeji dostop na localhost, in uros ter nejc na test in www
            $user_dostop = (($global_user_id == 1045 && $row_user['email'] == 'admin') || 
                    ($global_user_id == 12903 && $row_user['email'] == 'uros.podkriznik@gmail.com') || 
                    ($global_user_id == 864 && $row_user['email'] == 'uros.podkriznik@gmail.com') ||
                    ($global_user_id == 1073 && $row_user['email'] == 'nejc.berzelak@fdv.uni-lj.si') ||
                    ($global_user_id == 836 && $row_user['email'] == 'nejc.berzelak@fdv.uni-lj.si'));
            if (Common::checkModule('maza') && $user_dostop) {
                echo '<li ' . ($get == A_MAZA ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_MAZA . '" title="' . $lang['srv_maza'] . '"><span>' . $lang['srv_maza'] . '</span></a></li> ';
            }
            
            # 360 web push notifications - zaenkrat samo admini
            if (Common::checkModule('wpn') && $admin_type == 0) {
                echo '<li ' . ($get == A_WPN ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_WPN . '" title="' . $lang['srv_wpn'] . '"><span>' . $lang['srv_wpn'] . '</span></a></li> ';
            }

            # 360 stopinj
            if (Common::checkModule('360')) {
                echo '<li ' . ($get == A_360 ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_360 . '" title="' . $lang['srv_vrsta_survey_type_11'] . '"><span>' . $lang['srv_vrsta_survey_type_11'] . '</span></a></li> ';
            }

			 # 360 stopinj 1ka - zaenkrat samo admini
            if (Common::checkModule('360_1ka') && $admin_type == 0) {
                echo '<li ' . ($get == A_360_1KA ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_360_1KA . '" title="' . $lang['srv_vrsta_survey_type_12'] . '"><span>' . $lang['srv_vrsta_survey_type_12'] . '</span></a></li> ';
            }

			# evoli
            if (Common::checkModule('evoli')) {
                echo '<li ' . ($get == 'evoli' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli" title="Evoli"><span>Evoli</span></a></li> ';
            }

			# evoli - teammeter
            if (Common::checkModule('evoli_teammeter')) {
                echo '<li ' . ($get == 'evoli_teammeter' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_teammeter" title="Evoli team meter"><span>Evoli team meter</span></a></li> ';
            }

            # evoli - evoli_quality_climate
            if (Common::checkModule('evoli_quality_climate')) {
                echo '<li ' . ($get == 'evoli_quality_climate' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_quality_climate" title="Evoli quality climate"><span>Evoli quality climate</span></a></li> ';
            }

            # evoli - evoli_teamship_meter
            if (Common::checkModule('evoli_teamship_meter')) {
                echo '<li ' . ($get == 'evoli_teamship_meter' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_teamship_meter" title="Evoli teamship meter"><span>Evoli teamship meter</span></a></li> ';
            }

            # evoli - evoli_organizational_employeeship_meter
            if (Common::checkModule('evoli_organizational_employeeship_meter')) {
                echo '<li ' . ($get == 'evoli_organizational_employeeship_meter' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_organizational_employeeship_meter" title="Evoli organizational employeeship meter"><span>Evoli organizational employeeship meter</span></a></li> ';
            }

            # evoli - employmeter
            if (Common::checkModule('evoli_employmeter')) {
                echo '<li ' . ($get == 'evoli_employmeter' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=evoli_employmeter" title="Evoli employeeship meter"><span>Evoli employeeship meter</span></a></li> ';
            }

			# mfdps
            if (Common::checkModule('mfdps')) {
                echo '<li ' . ($get == 'mfdps' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=mfdps" title="MFDPS"><span>MFDPŠ</span></a></li> ';
            }
			
			# borza
            if (Common::checkModule('borza') && $admin_type == 0) {
                echo '<li ' . ($get == 'borza' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=borza" title="BORZA"><span>Borza</span></a></li> ';
            }

			# mju - vsi, ker je to samo na njihovi instalaciji
            if (Common::checkModule('mju')) {
                echo '<li ' . ($get == 'mju' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=mju" title="MJU"><span>MJU</span></a></li> ';
            }
			
			# excelleration matrix - zaenkrat samo admini
            if (Common::checkModule('excell_matrix') && $admin_type == 0) {
                echo '<li ' . ($get == 'excell_matrix' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=excell_matrix" title="Excelleration matrix"><span>Excelleration matrix</span></a></li> ';
            }

            echo '</ul>';
        }
    }

    /**
     * prikaze seznam anket in polje za dodajanje na prvi strani
     *
     */
    private function displaySeznamAnket(){
        global $lang, $site_url, $global_user_id, $admin_type, $site_domain;


        # naložimo razred z seznamom anket
        $SL = new SurveyList();
        $SLCount = $SL->countSurveys();
        $SLCountPhone = $SL->countPhoneSurveys();


        // VSEBINA POSAMEZNEGA TABA PRI MOJIH ANKETAH 
        echo '<div id="moje_ankete_edit" class="page_'.$_GET['a'].' subpage_'.$_GET['t'].' '.(isset($_GET['b']) ? 'subpage_b_'.$_GET['b'] : '').' '.($SLCount == 0 ? 'page_ustvari_anketo' : '').'">';

        // izpis pregledovanja
        if ((!isset($_GET['a']) && !isset($_GET['anketa'])) || ($_GET['a'] == 'pregledovanje')) { 
            
            if ($SLCount > 0) {
                echo '<div id="survey_list">';
                $SL->getSurveys();			
                echo '</div>';
            }
			else {
                $newSurvey = new NewSurvey();

                if (isset($_GET['b']) && $_GET['b'] == 'new_survey'){
					echo '<div id="new_anketa_div">';
				    $newSurvey->displayNewSurveyPage();
					echo '</div>';
				}
                else{
					echo '<div id="survey_list">';
                    $newSurvey->displayNoSurveySequence();
					echo '</div>';
				}
            }
        }

        // Izpis okna za ustvarjanje ankete (enako kot ce nimamo nobene ankete)
        if ($_GET['a'] == 'ustvari_anketo') {
            echo '<div id="new_anketa_div">';
			$newSurvey = new NewSurvey();
            $newSurvey->displayNewSurveyPage();
            echo '</div>';
        }

        // izpis pregledovanja
        if ($_GET['a'] == 'phoneSurveys') { 
            if ($SLCountPhone > 0) {
                echo '<div id="survey_list">';
                $SL->getSurveys();
                echo '</div>';
            } 
            else {
                echo '<div id="new_anketa_div">';
				$newSurvey = new NewSurvey();
                $newSurvey->displayNewSurveyPage();
                echo '</div>';
            }
        }

        // izpis diagnostike
        if ($_GET['a'] == 'diagnostics' && !isset ($_GET['t'])) { 
            echo '<div id="anketa_diagnostics">';
            $sa = new SurveyAktivnost();
            $sa->diagnostics();
            echo '</div>';
        }

        // izpis uporabnikov
        if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki') { 
            
            $sas = new SurveyAdminSettings();
            
            echo '<div id="survey_list" class="users_list_box">';

	        if($_GET['m'] == 'sa-modul') {
	            $sas->SAuserListIndex();
            }
            elseif($_GET['m'] == 'izbrisani'){
                $sas->deletedUsersList();
            }
            elseif($_GET['m'] == 'nepotrjeni'){
                $sas->unconfirmedMailUsersList();
            }
            elseif($_GET['m'] == 'odjavljeni'){
                $sas->unsignedUsersList();
            }
            elseif($_GET['m'] == 'all'){
	            $sas->allUsersList();
            }
            else {
	            $sas->assignedUsersList();
            }

            echo '</div>';
        }

        if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span') {
            $sa = new SurveyAktivnost();
            echo '<div id="survey_list" class="survey_list_box">';
            $sa->diagnostics_time_span();
            echo '</div>';
        }

        if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span_daily') {
            $sa = new SurveyAktivnost();
            echo '<div id="survey_list" class="survey_list_box">';
            $sa->diagnostics_time_span_daily();
            echo '</div>';
        }

        if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span_monthly') {
            $sa = new SurveyAktivnost();
            echo '<div id="survey_list" class="survey_list_box">';
            $sa->diagnostics_time_span_monthly();
            echo '</div>';
        }

        if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span_yearly') {
            $sa = new SurveyAktivnost();
            echo '<div id="survey_list" class="survey_list_box">';
            $sa->diagnostics_time_span_yearly();
            echo '</div>';
        }

		if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'paradata') {
            $sa = new SurveyAktivnost();
            echo '<div id="survey_list" class="survey_list_box">';
            $sa->diagnostics_paradata();
            echo '</div>';
        }

        // izpis nastavitev
        if ($_GET['a'] == 'nastavitve') { 

            echo '<div id="nastavitve">';

            if (($_GET['m'] == 'system' || $_GET['m'] == '')) {
                $sas = new SurveyAdminSettings();
                $sas->anketa_nastavitve_system();
            }
            if ($_GET['m'] == 'predvidenicasi' && $this->user_role_cehck(U_ROLE_ADMIN)) {
                $sas = new SurveyAdminSettings();
                $sas->anketa_nastavitve_predvidenicasi();
            }
            if ($_GET['m'] == 'global') {
                $sas = new SurveyAdminSettings();
                $sas->anketa_nastavitve_global();
            }
            if ($_GET['m'] == 'nice_links' && $this->user_role_cehck(U_ROLE_ADMIN)) {
                $sas = new SurveyAdminSettings();
                $sas->anketa_nice_links();
            }
            if ($_GET['m'] == 'anketa_admin' && $this->user_role_cehck(U_ROLE_ADMIN)) {
                $sas = new SurveyAdminSettings();
                $sas->anketa_admin();
            }
            if ($_GET['m'] == 'anketa_deleted' && $this->user_role_cehck(U_ROLE_ADMIN)) {
                $sas = new SurveyAdminSettings();
                $sas->anketa_deleted();
            }
            if ($_GET['m'] == 'data_deleted' && $this->user_role_cehck(U_ROLE_ADMIN)) {
                $sas = new SurveyAdminSettings();
                $sas->data_deleted();
            }
            if ($_GET['m'] == 'global_user_settings') {
                $sas = new SurveyAdminSettings();
                $sas->globalUserSettings();
            }
			if ($_GET['m'] == 'global_user_myProfile') {
                $sas = new SurveyAdminSettings();
                $sas->globalUserMyProfile();
            }
            echo '</div>';
        }

        if ($_GET['a'] == 'knjiznica') {
            
            if (!isset ($_GET['t'])) { // zavihek sistemske ankete
                $_tab = 2;
                $_prva = 1;
            } 
            else if ($_GET['t'] == 'moje_ankete') { // zavihek moje ankete
                $_tab = 3;
                $_prva = 1;
            }

            $f = new Library(array('tab' => $_tab, 'prva' => $_prva));
            
            echo '<div id="anketa_knjiznica">';
            
            echo '<div id="libraryInner">';
            $f->display_folders();
            echo '</div>';
            
            echo '</div>';
        }

        // izpis obvestil
        if ($_GET['a'] == 'obvestila') { 
            echo '<div id="notifications">';
            $NO = new Notifications();
            $NO->display();
            echo '</div>';
        }

        // izpis obvestil
        if ($_GET['a'] == 'narocila') { 

            if($admin_type == 0 && isset($_GET['m']) && $_GET['m'] == 'placila'){
                echo '<div id="placila">';
                $UP = new UserPlacila();
                $UP->displayPlacila();
                echo '</div>';	
            } 
			else{	
                echo '<div id="narocila">';

                $UN = new UserNarocila();
                if($admin_type == 0)
                    $UN->displayNarocilaTableAdmin();
                else
                    $UN->displayNarocila();

                echo '</div>';		
            }   
        }
        
        // nastavitve za gdpr
		if ($_GET['a'] == 'gdpr') { 

			echo '<div id="gdpr_nastavitve">';
		
			if (!isset($_GET['m']) || $_GET['m'] == 'gdpr_user') {
                $gdpr = new GDPR();
                $gdpr->displayGDPRUser();
            } 
			elseif ($_GET['m'] == 'gdpr_survey_list') {	
                $gdpr = new GDPR();
                $gdpr->displayGDPRSurveyList();			
            } 
			elseif ($_GET['m'] == 'gdpr_requests') {
                $gdpr = new GDPR();
                $gdpr->displayGDPRRequests();
            }
			elseif ($_GET['m'] == 'gdpr_requests_all') {	
				if($admin_type == 0){
					$gdpr = new GDPR();
					$gdpr->displayGDPRRequestsAll();	
				}
            } 
            elseif ($_GET['m'] == 'gdpr_dpa') {
                $gdpr = new GDPR();
                $gdpr->displayGDPRDPA();
            }
			
			echo '</div>';
        }

        // nastavitve, izvozi... za UL evalvacije
        if ($_GET['a'] == 'ul_evalvation') { 

            if (!isset($_GET['t']) || $_GET['t'] == 'export') {
                echo '  <div id="ul_exports">';
                $EVAL = new Evalvacija();
                $EVAL->displayExport();
                echo '  </div>';
            } 
            elseif ($_GET['t'] == 'import') {
                echo '  <div id="ul_imports">';
                $EVAL = new Evalvacija();
                $EVAL->displayImport();
                echo '  </div>';
            } 
            elseif ($_GET['t'] == 'emailing') {
                echo '  <div id="ul_emailing">';
                $EVAL = new Evalvacija();
                $EVAL->displayEmailing();
                echo '  </div>';
            } 
            elseif ($_GET['t'] == 'test') {
                echo '  <div id="ul_test">';
                $EVAL = new Evalvacija();
                $EVAL->displayTestSurveys();
                echo '  </div>';
            } 
            elseif ($_GET['t'] == 'gc') {
                echo '  <div id="ul_gc">';
                $GC = new GC();
                $GC->displayGC();
                echo '  </div>';
            }
        }

        // Konec moje_ankete_edit
        echo '</div>';
    }



    /**
     * vrne kodo ankete, ki se jo uporabi za embed
     *
     */
    function getEmbed($js = true)
    {
        global $site_url;

        //return '&lt;iframe id="1ka" src="'.$site_url.'main/survey/index.php?anketa='.$this->anketa.'" scrolling="auto" frameborder="0" width="100%"&gt;&lt;/iframe&gt;&lt;script type="text/javascript"&gt;function r(){var a=window.location.hash.replace("#","");if(a.length==0)return;document.getElementById("1ka").style.height=a+"px";window.location.hash=""};window.setInterval(\\\'r()\\\',100);&lt;/script&gt;';
        $iframe = '<iframe id="1ka" src="' . SurveyInfo::getSurveyLink() . '" height="400px" width="100%" scrolling="auto" frameborder="0"></iframe>';
        $javascript = '<script type="text/javascript">function r(){var a=window.location.hash.replace("#","");if(a.length==0)return;document.getElementById("1ka").style.height=a+"px";window.location.hash=""};window.setInterval("r()",100);</script>';

        if ($js)
            return htmlentities($iframe . $javascript, ENT_QUOTES);
        else
            return htmlentities($iframe, ENT_QUOTES);
    }

    
    function check_online_users(){
        global $global_user_id;
        global $lang;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        $sqlx = sisplet_query("SELECT uid FROM srv_dostop WHERE ank_id = '$this->anketa'");
        if (mysqli_num_rows($sqlx) <= 1) return;

        $sql = sisplet_query("SELECT DISTINCT user FROM srv_tracking".$this->db_table." WHERE ank_id='$this->anketa' AND user != '$global_user_id' AND datetime > NOW() - INTERVAL 15 MINUTE");
        if (!$sql) return;
        if (mysqli_num_rows($sql) > 0 && $hierarhija_type < 5) {

            echo '<div class="active-alert">';

            echo '<span class="tooltip active-editors">';

            echo '<a href="#" onclick="return false;">';
            echo '<span class="square green"></span>';
            echo ' <span class="active-editors">' . $lang['srv_users_viewing'] . ': <b>' . (mysqli_num_rows($sql) + 1) . '</b></span>';
            echo '</a>';


            if(is_null($hierarhija_type) || $hierarhija_type == 1){
              echo '<span class="expanded-tooltip bottom" id="request_help_content">';

              echo '<b>' . $lang['srv_users_viewing2'] . '</b><p>';
              while ($row = mysqli_fetch_array($sql)) {
                $sql1 = sisplet_query("SELECT name, surname, email FROM users WHERE id = '$row[user]'");
                if ($row1 = mysqli_fetch_array($sql1)) {
                  echo '<a href="mailto:' . $row1['email'] . '" target="_blank">' . $row1['email'] . '</a> (' . $row1['name'] . ' ' . $row1['surname'] . ')<br>';
                }
              }
              echo '</p>';

              echo '<span class="arrow"></span>';
              echo '</span>';    // expanded-tooltip bottom
            }
            echo '</span>'; // request-help

            echo '</div>';
        }
    }

    function request_help(){
        global $lang;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        if ($hierarhija_type == 10) {
            echo '<span class="faicon users icon-as_link" style="margin-left: 10px;"></span>';
            return '';
        }

        echo '<span class="tooltip request-help">';

        echo '<a href="#" onclick="return false;" title="' . $lang['srv_request_help'] . '">';
        echo '<span class="faicon users icon-as_link"></span>';
        echo '</a>';

        echo '<span class="expanded-tooltip bottom light" id="request_help_content">';

        $this->request_help_content();

        echo '</span>';    // expanded-tooltip bottom
        echo '</span>'; // request-help

    }

    function request_help_content(){
        global $lang;

        $row = SurveyInfo::getInstance()->getSurveyRow();

        echo '<b>' . $lang['srv_dostopmail_1'] . '</b>';

        $d = new Dostop();

        $users = $d->getDostop();

        if (is_countable($users[2]) && count($users[2]) > 0) {
            echo '<p><b>' . $lang['srv_users'] . ':</b> ';
            if ($d->checkDostopAktiven()) echo ' (<a href="index.php?anketa=' . $this->anketa . '&a=dostop">' . $lang['srv_add_edit'] . '</a>)';
            echo '<br>';

            foreach ($users[2] AS $user) {
                echo ' - ' . $user['email'] . '<br>';
            }
            echo '</p>';
        }

        if (is_countable($users[1]) && count($users[1]) > 0) {
            echo '<p><b>' . $lang['managers'] . ':</b>';
            if ($d->checkDostopAktiven()) echo ' (<a href="index.php?anketa=' . $this->anketa . '&a=dostop">' . $lang['srv_add_edit'] . '</a>)';
            echo '<br>';

            foreach ($users[1] AS $user) {
                echo ' - ' . $user['email'] . '<br>';
            }
            echo '</p>';
        }

        echo '<p><b>' . $lang['srv_request_help_txt3'] . '</b> ' . Help::display('help-centre') . '<br>';
        if ($users[0] !== false) {
            echo $lang['srv_request_help_txt4'] . ' ' . date('j.n.Y', strtotime($users[0])) . ' (<a href="#" onclick="dostop_admin(1); return false;">' . $lang['hour_remove'] . '</a>)';
        } else {
            echo '<a href="#" onclick="dostop_admin(); return false;">' . $lang['srv_dostop_admin'] . '</a>';
        }
        echo '</p>';

        echo '<span class="arrow"></span>';
    }

    function displaySecondNavigationLinks($navigation = 0)
    {
        global $lang, $site_url;
        global $global_user_id;

        $row = SurveyInfo::getInstance()->getSurveyRow();

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);

        if ($navigation == 0) {
            # stran status (reporti) ali urejanje
            if ($this->first_action == NAVI_UREJANJE || $_GET['a'] == '' || $_GET['a'] == A_REPORTI) {

                if ($_GET['a'] == '' || $_GET['a'] == 'branching') {
					
                    if ($row['toolbox'] <= 2) {
                        $row['toolbox'] == 1 ? $preklop = 3 : $preklop = 4;
                        echo '<a href="#" title="' . $lang['srv_library'] . '" class="srv_ico" onclick="change_mode(\'toolbox\', \'' . $preklop . '\'); return false;">';
                        echo '<span class="faicon library"></span>';
                        echo '</a>';
                    } else {
                        $row['toolbox'] == 3 ? $preklop = 1 : $preklop = 2;
                        echo '<a href="#" title="' . $lang['srv_library'] . '" class="srv_ico" onclick="if ( $(\'#toolbox_library\').css(\'display\') == \'none\' ) { close_all_editing(); $(\'#toolbox_library\').show(); } else { change_mode(\'toolboxback\', \'' . $preklop . '\'); } return false;">';
                        echo '<span class="faicon library"></span>';
                        echo '</a>';
                    }
                }
				
                $p = new Prevajanje($this->anketa);
                global $lang1;

                $lang_more = '';
                $sqll = sisplet_query("SELECT dostop FROM srv_dostop WHERE ank_id='$this->anketa' AND uid='$global_user_id'");
                $rowl = mysqli_fetch_array($sqll);
                $dostop = explode(',', $rowl['dostop']);
                if (!in_array('edit', $dostop)) {
                    $sqll = sisplet_query("SELECT lang_id FROM srv_dostop_language WHERE ank_id='$this->anketa' AND uid='$global_user_id'");
                    if (mysqli_num_rows($sqll) == 1) {
                        $rowl = mysqli_fetch_array($sqll);
                        $p->include_lang($rowl['lang_id']);
                        $lang_more = ' | ' . $lang['lang_short'];
                        $p->include_base_lang();
                    }
                }
                if ($lang_more == '' && isset($_GET['lang_id'])) {
                    $p->include_lang((int)$_GET['lang_id']);
                    $lang_more = ' | ' . $lang['lang_short'];
                    $p->include_base_lang();
                }
            }

            if (($_GET['a'] == A_COLLECT_DATA || $_GET['a'] == A_USABLE_RESP || $_GET['a'] == A_KAKOVOST_RESP || $_GET['a'] == A_SPEEDER_INDEX || $_GET['a'] == A_REMINDER_TRACKING || $_GET['a'] == A_TEXT_ANALYSIS || $_GET['a'] == A_EDITS_ANALYSIS || $_GET['a'] == A_ANALYSIS) && $_GET['m'] != 'analysis_links' && $_GET['m'] != 'anal_arch')
                $this->displayExportHover($navigation);

        } else if ($navigation == 1) {
            
			# stran status (reporti) ali urejanje
            if ($this->first_action == NAVI_UREJANJE || $_GET['a'] == '' || $_GET['a'] == 'reporti') {
                
				// V kolikor imamo hierarhijo potem je tudi možnost kopiranja ankete in hierarhije
                if($_GET['a'] == A_HIERARHIJA_SUPERADMIN)
                    echo '<a href="#" onclick="anketa_copy_top(\'' . $this->anketa . '\', \'1\'); return false;" title="' . $lang['srv_hierarchy_copy_all'] . '" class="srv_ico" style="display:block;float:left;"><span class="icon copy-all"></span></a>';

				// Uvoz iz besedila
				if($this->second_action == NAVI_UREJANJE_BRANCHING || $_GET['a'] == ''){
                 
                    if($userAccess->checkUserAccess($what='ustvari_anketo_from_text'))
                        echo '<a href="#" onclick="popupImportAnketaFromText(); return false;" title="' . $lang['srv_newSurvey_survey_from_text'] . '" class="srv_ico"><span class="faicon import"></span></a>';
                    else
                        echo '<a href="#" onclick="popupUserAccess(\'ustvari_anketo_from_text\'); return false;" title="' . $lang['srv_newSurvey_survey_from_text'] . '" class="srv_ico"><span class="faicon import user_access_locked"></span></a>';
                }
				
                # kopiranje
                echo '<a href="#" onclick="anketa_copy_top(\'' . $this->anketa . '\'); return false;" title="' . $lang['srv_anketacopy'] . '" class="srv_ico"><span class="faicon anketa_copy"></span></a>';

                # brisanje
                echo '<a href="#" onclick="anketa_delete(\'' . $this->anketa . '\', \'' . $lang['srv_anketadeleteconfirm'] . '\'); return false;" title="' . $lang['srv_anketadelete'] . '" class="srv_ico"><span class="faicon anketa_delete" title="' . $lang['srv_anketadelete'] . '"></span></a>';

                if ($this->second_action == NAVI_UREJANJE_BRANCHING || $_GET['a'] == 'reporti' || ($_GET['a'] == A_HIERARHIJA_SUPERADMIN && $_GET['m'] == 'analize'))
                    $this->displayExportHover($navigation);
            }
            // Pri komentarjih imamo izvoz pdf/rtf
            if ($_GET['a'] == 'komentarji') {

                $this->displayExportHover($navigation);
            }
        }
    }

    // Ikona in hover div za izvoz
    function displayExportHover($navigation){
        global $lang, $site_url, $global_user_id, $admin_type;

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);       

        $hidden_icon = (in_array($_GET['m'], array(M_ANALYSIS_CROSSTAB, M_ANALYSIS_MULTICROSSTABS, M_ANALYSIS_MEANS, M_ANALYSIS_TTEST, M_ANALYSIS_BREAK))) ? 'hidden' : '';
        echo '<a href="#" class="srv_ico '.$hidden_icon.'" id="hover_export_icon" title="' . $lang['srv_export'] . '">';
        echo '<span class="faicon export"></span>';
        echo '</a>';

        echo '<div id="hover_export">';

        if ($navigation == 0) {

            $lan_archive = ' title="' . $lang['srv_analiza_arhiviraj_ttl'] . '"';
            $lan_archive_send = ' title="' . $lang['srv_analiza_arhiviraj_email_ttl'] . '"';
            $lan_print = ' title="' . $lang['PRN_Izpis'] . '"';
            $lan_pdf = ' title="' . $lang['PDF_Izpis'] . '"';
            $lan_rtf = ' title="' . $lang['RTF_Izpis'] . '"';
            $lan_xls = ' title="' . $lang['XLS_Izpis'] . '"';
            $lan_ppt = ' title="' . $lang['PPT_Izpis'] . '"';

            if ($_GET['m'] == M_ANALYSIS_DESCRIPTOR) {
                echo '<a href="#" onclick="showSurveyUrlLinks(\'' . A_ANALYSIS . '\',\'' . M_ANALYSIS_DESCRIPTOR . '\');" class="srv_ico '.(!$userAccess->checkUserAccess('public_link') ? 'user_access_locked' : '').'"  user-access="public_link" title="' . $lang['srv_export_hover_public2'] . '"><span class="hover_export_icon"><span class="faicon data_link very_large"></span></span>' . $lang['srv_export_hover_public'] . '</a>';
                echo '<a href="#" onclick="doArchiveAnaliza();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Opisne statistike\'); return false;"' . $lan_print . ' class="srv_ico"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics&anketa=' . $this->anketa) . '" target="_blank"' . $lan_pdf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics_rtf&anketa=' . $this->anketa) . '" target="_blank"' . $lan_rtf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=statistics_xls&anketa=' . $this->anketa) . '" target="_blank"' . $lan_xls . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_FREQUENCY) {
                echo '<a href="#" onclick="showSurveyUrlLinks(\'' . A_ANALYSIS . '\',\'' . M_ANALYSIS_FREQUENCY . '\');" class="srv_ico '.(!$userAccess->checkUserAccess('public_link') ? 'user_access_locked' : '').'" user-access="public_link" title="' . $lang['srv_export_hover_public2'] . '"><span class="hover_export_icon"><span class="faicon data_link very_large"></span></span>' . $lang['srv_export_hover_public'] . '</a>';
                echo '<a href="#" onclick="doArchiveAnaliza();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Frekvence\'); return false;"' . $lan_print . ' class="srv_ico"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency&anketa=' . $this->anketa) . '" target="_blank"' . $lan_pdf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency_rtf&anketa=' . $this->anketa) . '" target="_blank"' . $lan_rtf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=frequency_xls&anketa=' . $this->anketa) . '" target="_blank"' . $lan_xls . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_SUMMARY) {
                echo '<a href="#" onclick="showSurveyUrlLinks(\'' . A_ANALYSIS . '\',\'' . M_ANALYSIS_SUMMARY . '\');" class="srv_ico '.(!$userAccess->checkUserAccess('public_link') ? 'user_access_locked' : '').'" user-access="public_link" title="' . $lang['srv_export_hover_public2'] . '"><span class="hover_export_icon"><span class="faicon data_link very_large"></span></span>' . $lang['srv_export_hover_public'] . '</a>';
                echo '<a href="#" onclick="doArchiveAnaliza();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Sumarnik\'); return false;"' . $lan_print . ' class="srv_ico"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums&anketa=' . $this->anketa) . '" target="_blank"' . $lan_pdf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums_rtf&anketa=' . $this->anketa) . '" target="_blank"' . $lan_rtf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=sums_xls&anketa=' . $this->anketa) . '" target="_blank"' . $lan_xls . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_export') ? 'user_access_locked' : '').'" user-access="analysis_export"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_CROSSTAB) {
                echo '<a href="#" onclick="doArchiveCrosstab();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveCrosstabBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Crosstab\'); return false;"' . $lan_print . ' class="srv_ico hidden"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="#" id="crosstabDoPdf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_crosstabs') ? 'user_access_locked' : '').' hidden" user-access="analysis_crosstabs"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="#" id="crosstabDoRtf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_crosstabs') ? 'user_access_locked' : '').' hidden" user-access="analysis_crosstabs"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="#" id="crosstabDoXls" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_crosstabs') ? 'user_access_locked' : '').' hidden" user-access="analysis_crosstabs"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_MULTICROSSTABS) {
                echo '<a href="#" onClick="printAnaliza(\'MultiCrosstab\'); return false;"' . $lan_print . ' class="srv_ico hidden"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="#" id="multicrosstabDoPdf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_multicrosstabs') ? 'user_access_locked' : '').' hidden" user-access="analysis_multicrosstabs"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="#" id="multicrosstabDoRtf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_multicrosstabs') ? 'user_access_locked' : '').' hidden" user-access="analysis_multicrosstabs"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="#" id="multicrosstabDoXls" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_multicrosstabs') ? 'user_access_locked' : '').' hidden" user-access="analysis_multicrosstabs"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_CHARTS) {
                echo '<a href="#" onclick="showSurveyUrlLinks(\'' . A_ANALYSIS . '\',\'' . M_ANALYSIS_CHARTS . '\');" class="srv_ico '.(!$userAccess->checkUserAccess('public_link') ? 'user_access_locked' : '').'" user-access="public_link" title="' . $lang['srv_export_hover_public2'] . '"><span class="hover_export_icon"><span class="faicon data_link very_large"></span></span>' . $lang['srv_export_hover_public'] . '</a>';
                echo '<a href="#" onclick="doArchiveChart();" title="' . $lang['srv_analiza_arhiviraj_ttl'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveChartBeforeEmail();" title="' . $lang['srv_analiza_arhiviraj_email_ttl'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Grafi\'); return false;"' . $lan_print . ' class="srv_ico"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=charts&anketa=' . $this->anketa) . '" target="_blank"' . $lan_pdf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_charts') ? 'user_access_locked' : '').'" user-access="analysis_charts"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=charts_rtf&anketa=' . $this->anketa) . '" target="_blank"' . $lan_rtf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_charts') ? 'user_access_locked' : '').'" user-access="analysis_charts"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=charts_ppt&anketa=' . $this->anketa) . '" target="_blank"' . $lan_ppt . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_charts') ? 'user_access_locked' : '').'" user-access="analysis_charts"><span class="hover_export_icon"><span class="sprites ppt_large"></span></span>' . $lang['srv_export_hover_ppt'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_MEANS) {
                echo '<a href="#" onclick="doArchiveMeans();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveMeansBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Means\'); return false;"' . $lan_print . ' class="srv_ico hidden"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="#" id="meansDoPdf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_means') ? 'user_access_locked' : '').' hidden" user-access="analysis_means"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="#" id="meansDoRtf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_means') ? 'user_access_locked' : '').' hidden" user-access="analysis_means"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="#" id="meansDoXls" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_means') ? 'user_access_locked' : '').' hidden" user-access="analysis_means"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_TTEST) {
                echo '<a href="#" onclick="doArchiveTTest();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveTTestBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'TTest\'); return false;"' . $lan_print . ' class="srv_ico hidden"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="#" id="ttestDoPdf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_ttest') ? 'user_access_locked' : '').' hidden" user-access="analysis_ttest"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="#" id="ttestDoRtf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_ttest') ? 'user_access_locked' : '').' hidden" user-access="analysis_ttest"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="#" id="ttestDoXls" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_ttest') ? 'user_access_locked' : '').' hidden" user-access="analysis_ttest"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_BREAK) {
                echo '<a href="#" onclick="doArchiveBreak();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveBreakBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').' hidden" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'Break\'); return false;"' . $lan_print . ' class="srv_ico hidden"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="#" id="breakDoPdf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_break') ? 'user_access_locked' : '').' hidden" user-access="analysis_break"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="#" id="breakDoRtf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_break') ? 'user_access_locked' : '').' hidden" user-access="analysis_break"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                echo '<a href="#" id="breakDoXls" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('analysis_break') ? 'user_access_locked' : '').' hidden" user-access="analysis_break"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['m'] == M_ANALYSIS_CREPORT) {
				echo '<a href="#" onclick="showSurveyUrlLinks(\'' . A_ANALYSIS . '\',\'' . M_ANALYSIS_CREPORT . '\');" class="srv_ico '.(!$userAccess->checkUserAccess('public_link') ? 'user_access_locked' : '').'" user-access="public_link" title="' . $lang['srv_export_hover_public2'] . '"><span class="hover_export_icon"><span class="faicon data_link very_large"></span></span>' . $lang['srv_export_hover_public'] . '</a>';
                echo '<a href="#" onclick="doArchiveCReport();"' . $lan_archive . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv black very_large"></span></span>' . $lang['srv_export_hover_archive'] . '</a>';
                echo '<a href="#" onclick="createArchiveCReportBeforeEmail();"' . $lan_archive_send . ' class="srv_ico '.(!$userAccess->checkUserAccess('archive') ? 'user_access_locked' : '').'" user-access="archive"><span class="hover_export_icon"><span class="faicon arhiv_mail black very_large"></span></span>' . $lang['srv_export_hover_archive_mail'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'CReport\'); return false;"' . $lan_print . ' class="srv_ico"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=creport_pdf&anketa=' . $this->anketa) . '" target="_blank"' . $lan_pdf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_analysis_creport') ? 'user_access_locked' : '').'" user-access="analysis_analysis_creport"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=creport_rtf&anketa=' . $this->anketa) . '" target="_blank"' . $lan_rtf . ' class="srv_ico '.(!$userAccess->checkUserAccess('analysis_analysis_creport') ? 'user_access_locked' : '').'" user-access="analysis_analysis_creport"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
            } 
            else if ($_GET['a'] == A_COLLECT_DATA) {
                echo '<a href="#" onclick="showSurveyUrlLinks(\'' . A_COLLECT_DATA . '\',\'\');" class="srv_ico '.(!$userAccess->checkUserAccess('public_link') ? 'user_access_locked' : '').'" user-access="public_link" title="' . $lang['srv_export_hover_public2'] . '"><span class="hover_export_icon"><span class="faicon data_link very_large"></span></span>' . $lang['srv_export_hover_public'] . '</a>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=export&m=spss" class="srv_ico '.(!$userAccess->checkUserAccess('data_export') ? 'user_access_locked' : '').'" user-access="data_export" title="' . $lang['srv_export_spss'] . '"><span class="hover_export_icon"><span class="basic-icon spss very_large"></span></span>' . $lang['srv_export_hover_spss'] . '</a>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=export&m=excel_xls" class="srv_ico '.(!$userAccess->checkUserAccess('data_export') ? 'user_access_locked' : '').'" user-access="data_export" title="' . $lang['srv_export_excel'] . '"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=export&m=txt" class="srv_ico '.(!$userAccess->checkUserAccess('data_export') ? 'user_access_locked' : '').'" user-access="data_export" title="' . $lang['srv_export_txt'] . '"><span class="hover_export_icon"><span class="faicon text_file"></span></span>' . $lang['srv_export_hover_txt'] . '</a>';
                
                // poseben excel izvoz za mfdps
				if(SurveyInfo::getInstance()->checkSurveyModule('mfdps')){
					echo '<a href="index.php?anketa=' . $this->anketa . '&a=export&m=excel_xls_mfdps&n=pred" class="srv_ico" title="' . $lang['srv_export_excel'] . ' MFDPŠ (predmeti)"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>MFDPŠ - predmeti</a>';
					echo '<a href="index.php?anketa=' . $this->anketa . '&a=export&m=excel_xls_mfdps&n=izv" class="srv_ico" title="' . $lang['srv_export_excel'] . ' MFDPŠ (izvajalci)"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>MFDPŠ - izvajalci</a>';
                }
                
				// poseben excel izvoz za mju
				if(SurveyInfo::getInstance()->checkSurveyModule('mju')){
					echo '<a href="index.php?anketa=' . $this->anketa . '&a=export&m=excel_xls_mju" class="srv_ico" title="' . $lang['srv_export_excel'] . ' MJU"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>MJU seminarji</a>';
				}
            } 
            else if ($_GET['a'] == A_USABLE_RESP) {
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=usable_xls&anketa=' . $this->anketa) . '" ' . $lan_xls . ' target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('usable_resp') ? 'user_access_locked' : '').'" user-access="usable_resp"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['a'] == A_SPEEDER_INDEX) {
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=speeder_xls&anketa=' . $this->anketa) . '" ' . $lan_xls . ' target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('speeder_index') ? 'user_access_locked' : '').'" user-access="speeder_index"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            } 
            else if ($_GET['a'] == A_TEXT_ANALYSIS) {
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=text_analysis_xls&anketa=' . $this->anketa) . '" ' . $lan_xls . ' target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('text_analysis') ? 'user_access_locked' : '').'" user-access="text_analysis"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=text_analysis_csv&anketa=' . $this->anketa) . '&type=1" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('text_analysis') ? 'user_access_locked' : '').'" user-access="text_analysis"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_lnk_excel'] . ' (' . $lang['srv_table'] . ' 1)</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=text_analysis_csv&anketa=' . $this->anketa) . '&type=0" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('text_analysis') ? 'user_access_locked' : '').'" user-access="text_analysis"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_lnk_excel'] . ' (' . $lang['srv_table'] . ' 2)</a>';
            } 
            else if ($_GET['a'] == A_EDITS_ANALYSIS) {
                echo '<a href="#" onClick="printEditsAnalysisPDF(); return false;" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('edits_analysis') ? 'user_access_locked' : '').'" user-access="edits_analysis"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="#" onClick="printAnaliza(\'EditsAnalysis\'); return false;" class="srv_ico '.(!$userAccess->checkUserAccess('edits_analysis') ? 'user_access_locked' : '').'" user-access="edits_analysis"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
            } 
            else if ($_GET['a'] == A_REMINDER_TRACKING) {
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=usable_xls&anketa=' . $this->anketa) . '" ' . $lan_xls . ' target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('nonresponse_graph') ? 'user_access_locked' : '').'" user-access="nonresponse_graph"><span class="hover_export_icon"><span class="faicon xls black very_large"></span></span>' . $lang['srv_export_hover_xls'] . '</a>';
            }
        } 
        else {
            if ($this->first_action == NAVI_UREJANJE || $_GET['a'] == '' || $_GET['a'] == 'reporti') {
                if ($_GET['a'] == '' || $_GET['a'] == 'branching') {
                    echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?a=vprasalnik_pdf&anketa=' . $this->anketa . '&type=1') . '" target="_blank" title="' . $lang['PDF_Izpis'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                    echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?a=vprasalnik_rtf&anketa=' . $this->anketa . '&type=1') . '" target="_blank" title="' . $lang['RTF_Izpis'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
					
					//za enkrat samo za admine
					if($admin_type == 0){
						echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?a=vprasalnik_xml&anketa=' . $this->anketa . '&type=1') . '" target="_blank" title="' . $lang['XML_Izpis'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon xml black very_large"></span></span>' . $lang['srv_export_hover_xml'] . '</a>';
					}
                    
					if ($row['multilang'] == 1 && $full_view) {
                        echo '<a href="index.php?anketa=' . $this->anketa . '&a=prevajanje" title="' . $lang['srv_prevajanje'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="sprites book"></span></span></a>';
                    }
                }

                if ($_GET['a'] == 'reporti') {
                    echo '<a href="#" onClick="printStatusPDF(); return false;" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                    echo '<a href="#" onClick="printAnaliza(\'Status\'); return false;" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                }

                if ($_GET['a'] == A_HIERARHIJA_SUPERADMIN) {
                    echo '<a href="#" onClick="printElement(\'Analize\'); return false;"' . $lan_print . ' class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').' hidden"><span class="hover_export_icon"><span class="faicon print"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                    echo '<a href="#" id="meansDoPdf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').' hidden"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                    echo '<a href="#" id="meansDoRtf" target="_blank" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').' hidden"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
                }
            } 
            elseif ($_GET['a'] == 'komentarji') {
                $commentType = (isset($_GET['only_unresolved'])) ? $_GET['only_unresolved'] : 1;
                $commentType = ($commentType == 'undefined') ? 0 : $commentType;

                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?a=pdf_comment&anketa=' . $this->anketa . '&only_unresolved=' . $commentType) . '" target="_blank" title="' . $lang['PDF_Izpis'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon pdf black very_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?a=rtf_comment&anketa=' . $this->anketa . '&only_unresolved=' . $commentType) . '" target="_blank" title="' . $lang['RTF_Izpis'] . '" class="srv_ico '.(!$userAccess->checkUserAccess('export') ? 'user_access_locked' : '').'"><span class="hover_export_icon"><span class="faicon rtf black very_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
            }
        }

        echo '</div>';


        // Javascript s katerim povozimo urlje za izvoze, ki niso na voljo v paketu
        global $app_settings;
        if($app_settings['commercial_packages'] == true){
            echo '<script> userAccessExport(); </script>';
        }
    }

    function survey_icon_add_comment(){
        global $lang, $site_url, $admin_type, $global_user_id;

        SurveyInfo::getInstance()->SurveyInit($this->anketa);
        $row = SurveyInfo::getInstance()->getSurveyRow();

        SurveySetting::getInstance()->Init($this->anketa);

        $survey_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewadminonly');
        $survey_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewauthor');
        $sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
        $view = 1;

        $sas = new SurveyAdminSettings();
        $survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment');
        
        $userAccess = UserAccess::getInstance($global_user_id);            

        if (($admin_type <= $survey_comment && $survey_comment != '') or $sas->testiranje_komentarji_komentarji_na_anketo(false) > 0) {
            $show_survey_comment = $_GET['show_survey_comment'];

            $comment_count = $sas->testiranje_komentarji_count();
            $comment_count_text = ($comment_count['survey_admin']['unresolved'] + $comment_count['survey_resp']['unresolved']);

            if (($row['forum'] == 0 || $row['thread'] == 0) && $comment_count_text == '0') {
            
                if($userAccess->checkUserAccess('komentarji')){
                    echo '<a href="#" onclick="return false;" class="surveycomment srv_ico" id="surveycomment_0_' . $view . '" type="0" view="' . $view . '" spremenljivka="0">';
                    echo '  <div class="fa-stack"><span class="faicon comments fa-stack-1x icon-blue" title="' . $lang['srv_survey_general_comment'] . '"></div></span>';
                    echo '</a>';
                }
                else{
                    echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='. $row['id'].'&a=urejanje" class="surveycomment srv_ico" id="surveycomment_0_' . $view . '" type="0" view="' . $view . '" spremenljivka="0">';
                    echo '  <div class="fa-stack"><span class="faicon comments fa-stack-1x icon-blue user_access_locked" title="' . $lang['srv_survey_general_comment'] . '"></div></span>';
                    echo '</a>';
                }                
            } 
            else {

                $sqlf = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread]'");
                $rowf = mysqli_fetch_array($sqlf);
                $rowf['count']--; //zaradi 1. avtomatskega posta

                // Shranimo naslov za js qtip box
                $comment_qip_title = $lang['srv_comments_anketa_ured'] .
                    ' (' . $comment_count['survey_admin']['unresolved'] . '/' . $comment_count['survey_admin']['all'] . ')'
                    . '<div class=\'comment_qtip_title_secondLine\'>' . $lang['srv_comments_anketa_resp']
                    . ' (' . $comment_count['survey_resp']['unresolved'] . '/' . $comment_count['survey_resp']['all'] . ')</div>';
                echo '<input type="hidden" id="comment_qtip_title" value="' . $comment_qip_title . '" />';


                if($userAccess->checkUserAccess('komentarji')){
                    echo '<a href="#" onclick="return false;" class="surveycomment srv_ico" id="surveycomment_0_' . $view . '" type="0" view="' . $view . '" spremenljivka="0">';
                    
                    echo '<div class="fa-stack"><span class="faicon comments_num icon-blue fa-stack-1x" title="' . $lang['srv_survey_general_comment'] . '"><strong class="fa-stack-1x">' . $comment_count_text . '</strong></span></div>';

                    // ali prikazemo okno odprto - je dodan tak admin komentar
                    $sqlf1 = sisplet_query("SELECT id FROM post p WHERE p.tid='$row[thread]' AND p.ocena='5'");
                    while ($rowf1 = mysqli_fetch_array($sqlf1)) {
                        $s = sisplet_query("SELECT * FROM views WHERE pid='$rowf1[id]' AND uid='$global_user_id'");
                        
                        if (mysqli_num_rows($s) == 0)
                            $show_survey_comment = 1;
                    }

                    echo '</a>';
                }
                else{
                    echo '<a href="' . $site_url . 'admin/survey/index.php?anketa='. $row['id'].'&a=urejanje" class="surveycomment srv_ico" id="surveycomment_0_' . $view . '" type="0" view="' . $view . '" spremenljivka="0">';
                    echo '  <div class="fa-stack"><span class="faicon comments_num icon-blue fa-stack-1x user_access_locked" title="' . $lang['srv_survey_general_comment'] . '"><strong class="fa-stack-1x">' . $comment_count_text . '</strong></span></div>';
                    echo '</a>';
                }
            }

            if($userAccess->checkUserAccess('komentarji'))
                echo '<script>  $(function() {  load_comment(\'#surveycomment_0_' . $view . '\'' . ($show_survey_comment == '1' ? ', \'2\'' : '') . ');  });</script>';
        }
    }

    
    /**
     * @desc uploada skin
     */
    function upload_skin()
    {
        global $site_path;
        global $lang;
        global $global_user_id;

        if (isset ($_FILES['fajl']['name'])) {

            $mini = $_FILES['fajl']['name'];

            // skin
            if ((strtolower(substr($mini, -4, 4)) == '.css' || (strtolower(substr($mini, -4, 4)) == '.zip'))
                && strpos(strtolower($mini), ".exe") === false
                && strpos(strtolower($mini), ".bat") === false
                && strpos(strtolower($mini), ".com") === false
                && strpos(strtolower($mini), ".vbs") === false
                && strpos(strtolower($mini), ".pl") === false
                && strpos(strtolower($mini), ".php") === false
            ) {


                $sql = sisplet_query("SELECT usr_id, skin FROM srv_theme_profiles WHERE id = '" . $_GET['profile'] . "'");
                $row = mysqli_fetch_array($sql);
                $user_id = $row['usr_id'];

                // ce ima svojo temo, jo zbrisemo
                if (strpos($row['skin'], $user_id . '_') !== false) {
                    $dir = $site_path . 'main/survey/skins/';
                    unlink($dir . $row['skin'] . '.css');
                }

                // Odzipaj ga
                if (strtolower(substr($mini, -4, 4)) == '.zip') {
                    if (!is_file($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4)))
                        mkdir($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4));

                    $file = $site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/' . $user_id . '_' . $_FILES['fajl']['name'];
                    move_uploaded_file($_FILES['fajl']['tmp_name'], $file);

                    exec('unzip -d ' . $site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . ' ' . $file);
                    copy($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/' . substr($_FILES['fajl']['name'], 0, -4) . ".css", $site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . ".css");

                    // malo kvazi varnosti

                    unlink($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/*.php');
                    unlink($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/*.exe');
                    unlink($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/*.pl');
                    unlink($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/*.bat');
                    unlink($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/*.vbs');
                    unlink($site_path . 'main/survey/skins/' . $user_id . '_' . substr($_FILES['fajl']['name'], 0, -4) . '/*.py');
                } else {

                    $file = $site_path . 'main/survey/skins/' . $user_id . '_' . $_FILES['fajl']['name'];
                    move_uploaded_file($_FILES['fajl']['tmp_name'], $file);
                }


                $s = sisplet_query("UPDATE srv_theme_profiles SET skin='" . str_replace('.css', '', $user_id . '_' . $mini) . "' WHERE id = '" . $_GET['profile'] . "'");
                if (!$s) echo mysqli_error($GLOBALS['connect_db']);

                header('Location: index.php?anketa=' . $this->anketa . '&a=theme-editor&t=css&profile=' . $_GET['profile']);

                // slika
            } elseif ((strtolower(substr($mini, -4, 4)) == '.jpg' || (strtolower(substr($mini, -4, 4)) == '.jpeg') || (strtolower(substr($mini, -4, 4)) == '.gif') || (strtolower(substr($mini, -4, 4)) == '.png'))
                && strpos(strtolower($mini), ".exe") === false
                && strpos(strtolower($mini), ".bat") === false
                && strpos(strtolower($mini), ".com") === false
                && strpos(strtolower($mini), ".vbs") === false
                && strpos(strtolower($mini), ".pl") === false
                && strpos(strtolower($mini), ".php") === false
            ) {

                if ($_GET['logo'] == 1) {

                    $replace = array(' ', '+');
                    $logo = 'logo_' . $this->anketa . '_' . str_replace($replace, '_', $_FILES['fajl']['name']) . '';
                    
                    $file = $site_path . 'main/survey/uploads/' . $logo;
                    $fileExt = pathinfo($_FILES['fajl']['name'], PATHINFO_EXTENSION);
              
                    [$width, $height] = getimagesize($_FILES['fajl']['tmp_name']);

                    // If logo too large resize uploaded logo to max 150px height
                    if($height > 150){
                        $new_h = 150;
                        $resize_percent = $new_h / $height;
                        $new_w = $width * $resize_percent;

                        switch($fileExt){

                            case 'jpg':
                            case 'jpeg':
                                $resourceType = imagecreatefromjpeg($_FILES['fajl']['tmp_name']); 
                                $imageLayer = imagecreatetruecolor($new_w, $new_h);
                                imagecopyresampled($imageLayer, $resourceType, 0,0,0,0, $new_w, $new_h, $width, $height);
                                imagejpeg($imageLayer, $_FILES['fajl']['tmp_name']);
                            break;

                            case 'png':
                                $resourceType = imagecreatefrompng($_FILES['fajl']['tmp_name']); 
                                $imageLayer = imagecreatetruecolor($new_w, $new_h);
                                imagecopyresampled($imageLayer, $resourceType, 0,0,0,0, $new_w, $new_h, $width, $height);
                                imagepng($imageLayer, $_FILES['fajl']['tmp_name']);
                            break;

                            case 'gif':
                                $resourceType = imagecreatefromgif($_FILES['fajl']['tmp_name']); 
                                $imageLayer = imagecreatetruecolor($new_w, $new_h);
                                imagecopyresampled($imageLayer, $resourceType, 0,0,0,0, $new_w, $new_h, $width, $height);
                                imagegif($imageLayer, $_FILES['fajl']['tmp_name']);
                            break;
                        }
                    }

                    move_uploaded_file($_FILES['fajl']['tmp_name'], $file);

                    sisplet_query("UPDATE srv_theme_profiles SET logo = '$logo' WHERE id = '" . $_GET['profile'] . "'");

                    header('Location: index.php?anketa=' . $this->anketa . '&a=theme-editor&profile=' . $_GET['profile']);

                } 
                else {

                    $file = $site_path . 'main/survey/uploads/' . $this->uid() . '_' . $_FILES['fajl']['name'];
                    move_uploaded_file($_FILES['fajl']['tmp_name'], $file);

                    header('Location: index.php?anketa=' . $this->anketa . '&a=theme-editor&t=css&profile=' . $_GET['profile']);
                }

            } else {
                echo '
				<script language="javascript">
				alert(\'' . $lang['srv_filealert'] . '\');
				</script>
				';

            }
        }
    }

    /**
     * ankete najprej ne zbrisemo zares, ampak samo oznacimo, da je bila izbrisana
     *
     * uporablja se tudi v API
     *
     * @param mixed $anketa
     */
    function anketa_delete($anketa)
    {
        global $site_path, $global_user_id;

        if (!$anketa) return;

        // zbrisemo zakesiran query v seji
        if (session_id() == '') {
            session_start();
        }
        unset($_SESSION['query']);
        unset($_SESSION['result']);

        // pošiljanje obvestil ob izbrisu ankete
        SurveyAlert::getInstance()->Init($anketa, $global_user_id);
        SurveyAlert::getInstance()->sendMailDelete();

        $s = sisplet_query("UPDATE srv_anketa SET active='-1', edit_time=NOW() WHERE id = '$anketa'");

        Common::RemoveNiceUrl($anketa);

        if (!$s) echo mysqli_error($GLOBALS['connect_db']);

    }

    /** brisanje anket
     *
     * @param $anketa
     * @return unknown_type
     *
     */
    function anketa_delete_from_db($anketa)
    {
        global $site_path, $global_user_id;

        if (!$anketa) return;

        // zbrisemo zakesiran query v seji
        if (session_id() == '') {
            session_start();
        }
        unset($_SESSION['query']);
        unset($_SESSION['result']);

        // pošiljanje obvestil ob izbrisu ankete
        //SurveyAlert::getInstance()->Init($anketa, $global_user_id);
        //SurveyAlert::getInstance()->sendMailDelete();

        $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id = '$anketa'");
        while ($row = mysqli_fetch_array($sql)) {
            $sql1 = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$row[id]'");
            while ($row1 = mysqli_fetch_array($sql1)) {
                $sql2 = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id = '$row1[id]'");
            }
            $sql2 = sisplet_query("DELETE FROM srv_spremenljivka WHERE gru_id = '$row[id]'");

            # tabela srv_user_grupa
            $sql2 = sisplet_query("DELETE FROM srv_user_grupa WHERE gru_id = '$row[id]'");
            # tabela srv_user_grupa_active
            $sql2 = sisplet_query("DELETE FROM srv_user_grupa" . $this->db_table . " WHERE gru_id = '$row[id]'");
        }
        $sql2 = sisplet_query("DELETE FROM srv_grupa WHERE ank_id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_alert WHERE ank_id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_dostop WHERE ank_id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_user WHERE ank_id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_anketa WHERE backup = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_anketa WHERE id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_tracking".$this->db_table." WHERE id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_survey_misc WHERE sid = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_variable_profiles WHERE sid = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_glasovanje WHERE ank_id = '$anketa'");
        $sql2 = sisplet_query("DELETE FROM srv_survey_misc WHERE sid = '$anketa'");
        # Pobrisemo srv_condition_vre
        $sql2 = sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id IN (SELECT id FROM srv_condition WHERE if_id IN (SELECT element_if FROM srv_branching WHERE ank_id = '$anketa' AND element_if > 0))");
        #Pobrisemo srv_condition_grid
        $sql2 = sisplet_query("DELETE FROM srv_condition_grid WHERE cond_id IN (SELECT id FROM srv_condition WHERE if_id IN (SELECT element_if FROM srv_branching WHERE ank_id = '$anketa' AND element_if > 0));");
        #Pobrisemo srv_calculation
        $sql2 = sisplet_query("DELETE FROM srv_calculation WHERE cnd_id IN (SELECT id FROM srv_condition WHERE if_id IN (SELECT element_if FROM srv_branching WHERE ank_id = '$anketa' AND element_if > 0));");
        #pobrisemo srv_condition
        $sql2 = sisplet_query("DELETE FROM srv_condition WHERE if_id IN (SELECT element_if FROM srv_branching WHERE ank_id = '$anketa' AND element_if > 0);");
        #pobrisemo srv_if
        $sql2 = sisplet_query("DELETE FROM srv_if WHERE id IN (SELECT element_if FROM srv_branching WHERE ank_id = '$anketa' AND element_if > 0);");
        # sedaj lahko pobrisemo tudi branching
        $sql2 = sisplet_query("DELETE FROM srv_branching WHERE ank_id = '$anketa'");

        Common::RemoveNiceUrl($anketa);


        // kaj pa tabele:
        //srv_grid
        //	srv_call_....
        //  srv_data_.... // vsi podatki
        //  srv_userbase_setting
        //  srv_user_setting_for_survey

    }

    /**
     * @desc popravimo cas in userja popravka
     * ta funkcija je skopirana v common. tole pustimo, da ne bojo kaksne napake...
     */
    function updateEditStamp()
    {

        Common::updateEditStamp();

    }

    /**
     * @desc ustvari novo anketo
     */
    function nova_anketa($naslov = null, $intro_opomba = '', $akronim = null, $survey_type = 2, $skin = '1kaBlue')
    {

        global $lang;
        global $site_url;
        global $global_user_id;

        // zbrisemo zakesiran query v seji
        if (session_id() == '') {
            session_start();
        }
        unset($_SESSION['query']);
        unset($_SESSION['result']);

        $sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
        $row = mysqli_fetch_array($sql);
        $lang_admin = $row['lang'];

        $res = sisplet_query("SELECT value FROM misc WHERE what='SurveyCookie'");
        [$SurveyCookie] = mysqli_fetch_row($res);

        $text = '';
        $url = $site_url;

        #če naslov ni podan ali če je uporabnik pusti nespremenjen input box za ime ankete (==> Ime ankete) zgeneriramo novo ime
        if ($naslov == null || $naslov == $lang['srv_novaanketa_polnoime']) {
            $naslov = 'Test ' . rand(100, 999);
        }

        if ($akronim == null || $akronim == $lang['srv_novaanketa_ime_respondenti']) {
            $akronim = $naslov;
        }

        $starts = $_POST['starts'] ? "'" . $_POST['starts'] . "'" : "NOW()";
        $expire = $_POST['expire'] ? "'" . $_POST['expire'] . "'" : "NOW() + INTERVAL 3 MONTH  ";

        // Nastavimo jezik - admin in response jezik je vedno enak nastavitvi, ki jo ima uporabnik a default
        $lang_admin = ((int)$lang_admin > 0) ? $lang_admin : 1;
        $lang_resp = $lang_admin;

        # ali ima uporabnik nastavljeno da je anketa privzeto aktivna:
        $autoActiveSurvey = (int)UserSetting::getInstance()->getUserSetting('autoActiveSurvey');

        # ali ima uporabnik nastavljeno da so komentarji privzeto aktivirani
        $activeComments = (int)UserSetting::getInstance()->getUserSetting('activeComments');

        # ali ima uporabnik nastavljeno da je uvod privzeto skrit
        $showIntro = (int)UserSetting::getInstance()->getUserSetting('showIntro');
        # ali ima uporabnik nastavljeno da je zakljucek privzeto skrit
        $showConcl = (int)UserSetting::getInstance()->getUserSetting('showConcl');
        # ali ima uporabnik nastavljeno da je naslov ankete privzeto skrit
        $showSurveyTitle = (int)UserSetting::getInstance()->getUserSetting('showSurveyTitle');

		// Nastavimo se mobilni skin glede na osnovnega
		$mobile_skin = 'MobileBlue';
		if(in_array($skin, array('1kaBlue', '1kaRed', '1kaOrange', '1kaGreen', '1kaPurple', '1kaBlack'))){
			$mobile_skin = str_replace('1ka', 'Mobile', $skin);
		}
		elseif(in_array($skin, array('Uni', 'Fdv', 'Cdi'))){
			$mobile_skin = 'Mobile'.$skin;
		}

        $sql = sisplet_query("INSERT INTO srv_anketa (id, naslov, akronim, db_table, starts, expire, dostop, insert_uid, insert_time, edit_uid, edit_time, cookie, text, url, intro_opomba, show_intro, show_concl, survey_type, lang_admin, lang_resp, active, skin, mobile_skin) " .
            "VALUES ('', '$naslov', '$akronim', '1', $starts, $expire, '0', '$global_user_id', NOW(), '$global_user_id', NOW(), '$SurveyCookie', '$text', '$url', '$intro_opomba', '$showIntro', '$showConcl', '$survey_type', '$lang_admin', '$lang_resp', '$autoActiveSurvey', '$skin', '$mobile_skin')");
        if (!$sql) {
            $error = mysqli_error($GLOBALS['connect_db']);
        }
        $anketa = mysqli_insert_id($GLOBALS['connect_db']);


		// Dodan pogoj ce pride do problema pri ustvarjanju (ker ank_id==0 zacikla zadevo)
		if($anketa != 0){

			// Updatamo srv_activity, ce je anketa aktivna - drugace se ne zabelezi ok ko se deaktivira
			if ($autoActiveSurvey == 1) {
				$activity_insert_string = "INSERT INTO srv_activity (sid, starts, expire, uid) VALUES('" . $anketa . "', $starts, $expire, '" . $global_user_id . "' );";
				$sql_insert = sisplet_query($activity_insert_string);
			}

			// vnesemo tudi 1. grupo aka page
			$sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$lang[srv_stran] 1', '1')");

			//ce se nimamo vprasanja v glasovanju ga ustvarimo
			if ($survey_type == 0) {
				$sqlGrupe = sisplet_query("SELECT id, naslov FROM srv_grupa g WHERE g.ank_id='$anketa' ORDER BY g.vrstni_red");
				$rowGrupe = mysqli_fetch_assoc($sqlGrupe);

				$grupa = $rowGrupe['id'];

				$b = new Branching($this->anketa);
				$spr_id = $b->nova_spremenljivka($grupa, 1, 1);

				//napolnimo bazo srv_glasovanje
				$sqlG = sisplet_query("INSERT INTO srv_glasovanje (ank_id, spr_id) VALUES ('$anketa', '$spr_id')");

				//napolnimo vrednosti
				Vprasanje::change_tip($spr_id, 1);

				//napolnimo vrednosti vprasanja
				$values = "";
				for ($i = 1; $i <= $row['size']; $i++) {
					if ($values != "") $values .= ",";
					$values .= " ('$spremenljivka', '$i', '$i') ";
				}
				$sql1 = sisplet_query("INSERT INTO srv_vrednost (spr_id, variable, vrstni_red) VALUES $values");

				//popravljanje default nastavitev - stat=0, show_intro=0
				$sqlSpr = sisplet_query("UPDATE srv_spremenljivka SET stat = '0' WHERE id = '$spr_id'");
				$sqlAnk = sisplet_query("UPDATE srv_anketa SET show_intro = '0', show_concl = '0', progressbar = '0', countType = '0', akronim = ' ' WHERE id = '$anketa'");
				// vsilimo refresh podatkov
				SurveyInfo:: getInstance()->resetSurveyData();
			}

			//popravljanje default nastavitev pri formi - show_intro=0, show_concl=0, trajanje->neomejeno
			if ($survey_type == 1) {
				$sqlAnk = sisplet_query("UPDATE srv_anketa SET show_intro = '0', show_concl = '0', expire = '" . PERMANENT_DATE . "' WHERE id = '$anketa'");
			}

			// Popravimo default prikazovanje naslova ankete za respondente
			if ($showSurveyTitle == 0) {
				SurveySetting::getInstance()->Init($anketa);
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_hide_title', 1);
			}

			// Updatamo nastavitev za komentarje (ce so po defaultu vklopljeni)
			if ($activeComments == 1) {
				SurveySetting::getInstance()->Init($anketa);

				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment', 3);
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly', 3);

				SurveySetting::getInstance()->setSurveyMiscSetting('question_note_view', 3);
				SurveySetting::getInstance()->setSurveyMiscSetting('question_note_write', 0);

				SurveySetting::getInstance()->setSurveyMiscSetting('question_comment', 3);
				SurveySetting::getInstance()->setSurveyMiscSetting('question_comment_viewadminonly', 3);

				SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment', 1);
				SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_viewadminonly', 3);

				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_resp', 4);
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly_resp', 4);
			}

			// dodamo se uporabnika v dostop
			$uid = $this->uid();
			$sql = sisplet_query("INSERT INTO srv_dostop (ank_id, uid) VALUES ('$anketa', '$uid')");

			// Nastavimo obvescanje pri aktivaciji (default ob kreiranju ankete)
			SurveyAlert::getInstance()->Init($anketa, $global_user_id);
			SurveyAlert::setDefaultAlertActivation();
			
			// Nastavimo obvescanje pri poteku ankete (default ob kreiranju ankete)
			SurveyAlert::setDefaultAlertBeforeExpire();
			
			// uporabniku dodamo anketo se v knjiznico "moje ankete"
			// torej uporabniku ne bomo avtomatsko dodali ankete v knjiznico "moje ankete"
			//$sqlk = sisplet_query("SELECT * FROM srv_library_folder WHERE uid='$uid' AND tip='1' AND parent='0'");
			//$rowk = mysqli_fetch_array($sqlk);
			//sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$anketa', '$uid', '$rowk[id]')");

			return $anketa;
		}
		else{
			echo 'Napaka pri ustvarjanju ankete!';
			die();
		}
    }

    /**
     * @desc vpise novo spremenljivko v bazo (lahko je skopirana)
     */
    function nova_spremenljivka($grupa, $grupa_vrstni_red, $vrstni_red, $kuki = 0){

		// ce se slucajno se kje klice
        $b = new Branching($this->anketa);

        return $b->nova_spremenljivka($grupa, $grupa_vrstni_red, $vrstni_red, $kuki);
    }


    /**
     * preveri, ce v branchingu lahko zbrisemo spremenljivko (da ni v kaksnem pogoju)
     *
     * @param mixed $spremenljivka
     */
    function check_spremenljivka_delete($spremenljivka)
    {

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE spr_id='$spremenljivka'");
        if (mysqli_num_rows($sql) > 0)
            return false;

        return true;
    }

    /**
     * zbrise spremenljivko
     *
     * @param mixed $spremenljivka
     */
    function brisi_spremenljivko($spremenljivka)
    {

        if ($spremenljivka > 0) {

            $rowg = Cache::srv_spremenljivka($spremenljivka);

            // pri brisanju multiple grid vprasanja, moramo pobrisate tudi vse child spremenljivke (ker kljuci niso nastavljeni)
            if ($rowg['tip'] == 24) {
                $sqld = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE parent='$spremenljivka'");
                while ($rowd = mysqli_fetch_array($sqld)) {
                    sisplet_query("DELETE FROM srv_spremenljivka WHERE id='$rowd[spr_id]'");
                }
            }

            // Poiscemo ce imamo kaksen pogoj na posamezni vrednosti in ga pobrisemo (drugace ostane vezava na pogoj)
            $sqlC = sisplet_query("SELECT if_id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND if_id>'0'");
            while ($rowC = mysqli_fetch_array($sqlC)) {

                $if = $rowC['if_id'];

                $sqlCV = sisplet_query("SELECT id FROM srv_condition WHERE if_id = '$if'");
                while ($rowCV = mysqli_fetch_array($sqlCV))
                    sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$rowCV[id]'");

                sisplet_query("DELETE FROM srv_condition WHERE if_id = '$if'");
                sisplet_query("DELETE FROM srv_if WHERE id = '$if'");
            }

            $sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka'");
            $sql = sisplet_query("DELETE FROM srv_grid WHERE spr_id='$spremenljivka'");
            $sql = sisplet_query("DELETE FROM srv_spremenljivka WHERE id='$spremenljivka'");


			// Prej je bilo tako in je bila težava pri brisanju iz api-ja, ker gru_id ni bil definiran
            $grupa = $rowg['gru_id'];
            $this->repareSpremenljivka($grupa);


            $sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$spremenljivka'");
            $row = mysqli_fetch_array($sql);
            sisplet_query("DELETE FROM srv_branching WHERE element_spr = '$spremenljivka'");

            $b = new Branching($this->anketa);

            $b->repare_branching($row['parent']);

            $b->repare_vrstni_red();

            $b->trim_grupe();
        }
    }

    /**
     * @desc preveri, ce ze obstaja variabla s takim imenom - ob rocnem spremninjanju imena
     */
    function check_spremenljivka_variable($spremenljivka, $variable)
    {
        global $lang;

        if ($this->anketa > 0) {
            $sql_check = sisplet_query("SELECT id FROM srv_spremenljivka s, srv_grupa g WHERE s.id!='$spremenljivka' AND s.variable='$variable' AND g.ank_id='$this->anketa' AND g.id=s.gru_id");
            if (!$sql_check)
                echo mysqli_error($GLOBALS['connect_db']);
            if (mysqli_num_rows($sql_check) > 0) {
                echo $lang['srv_variable_error'];
            }
        }
    }

    /**
     * @desc prikaze nas clipboard
     */
    function clipboard_display($spremenljivka = 0, $if = 0)
    {
        global $lang;

        return; // tega ne rabimo vec

        $cut = $_POST['cut'];
        if ($cut == 1)
            setcookie('srv_cut_' . $this->anketa, '1');
        else
            setcookie('srv_cut_' . $this->anketa, '', time() - 3600);

        if ($spremenljivka > 0)
            setcookie('srv_clipboard_' . $this->anketa, $spremenljivka);
        elseif ($if > 0) setcookie('srv_clipboard_' .
            $this->anketa, 'if_' . $if);
        elseif ($_COOKIE['srv_clipboard_' . $this->anketa] > 0 && $spremenljivka != -1)
            $spremenljivka = $_COOKIE['srv_clipboard_' . $this->anketa];
        elseif (substr($_COOKIE['srv_clipboard_' . $this->anketa], 3) > 0 && $spremenljivka != -1)
            $if = substr($_COOKIE['srv_clipboard_' . $this->anketa], 3);

        // prikazemo spremenljivko
        if ($spremenljivka > 0) {
            $row = Cache::srv_spremenljivka($spremenljivka);

            echo '<p>' . $lang['srv_copied_spr'] . ': <img src="img_' . $this->skin . '/add smaller.png" alt="" style="position:relative; top:5px"/></p>';
            echo '<p><strong>' . strip_tags($row['naslov']) . '</strong> (' . $row['variable'] . ')</p>';
        }

        // prikazemo if
        if ($if > 0) {
            $sql = sisplet_query("SELECT tip, label FROM srv_if WHERE id = '$if'");
            $row = mysqli_fetch_array($sql);
            $b = new Branching($this->anketa);

            if ($row['tip'] == 0) {
                echo '<p>' . $lang['srv_copied_if'] . ': <img src="img_' . $this->skin . '/if.png" alt="" style="position:relative; top:5px; left:3px;" /></p>';
                echo '<p>' . $b->conditions_display($if) . '</p>';
            } else {
                echo '<p>' . $lang['srv_copied_block'] . ': <img src="img_' . $this->skin . '/b.png" alt="" style="position:relative; top:5px; left:3px;" /></p>';
                echo '<p><em>' . $row['label'] . '</em></p>';
            }
        }

        if ($spremenljivka > 0 || $if > 0) {
            echo '<p style="text-align:right"><a href="#" onclick="copy_remove(); return false;">' . $lang['srv_copy_remove'] . '</a></p>';

            echo '<script language="javascript">';
            echo '  $("#clipboard").fadeIn(); ';
            echo '</script>';
        }

        // zbrisemo iz clipboarda (kukija)
        if ($spremenljivka == -1) {
            setcookie('srv_clipboard_' . $this->anketa, '', time() - 3600);
            setcookie('srv_cut_' . $this->anketa, '', time() - 3600);

            echo '<script language="javascript">';
            echo '  $("#clipboard").fadeOut(); ';
            echo '</script>';
        }

    }

    /**
     * @desc prestevilci variable vseh vprasanj v anketi
     */
    function prestevilci($spremenljivka = 0, $all = false)
    {

        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->prestevilci($spremenljivka, $all);

    }

    /**
     * @desc prestevilci ife
     */
    function prestevilci_if($parent = 0, & $number = 1)
    {

        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->prestevilci_if($parent, $number);

    }

    /**
     * @desc popravi celotno anketo
     */
    function repareAnketa($anketa = 0)
    {
        if ($anketa == 0)
            $anketa = $this->anketa;

        Common::repareAnketa($anketa);
    }

    /**
     * @desc popravi vrstni red v tabeli srv_grupa
     */
    function repareGrupa($anketa)
    {
        Common::repareGrupa($anketa);
    }

    /**
     * @desc popravi vrstni red v tabeli srv_spremenljivka
     */
    function repareSpremenljivka($grupa)
    {
        Common::repareSpremenljivka($grupa);
    }

    /**
     * @desc popravi vrstni red v tabeli srv_vrednost
     */
    function repareVrednost($spremenljivka)
    {
        Common::repareVrednost($spremenljivka);
    }

    /**
     * @desc preveri pravice trenutnega userja za urejanje ankete
     */
    function checkDostop($anketa = 0)
    {
        $d = new Dostop();
        return $d->checkDostop($anketa);
    }

    /**
     * preveri nivo dostopa za uporabnika (ce je aktiven ali pasiven)
     */
    function checkDostopAktiven($anketa = 0)
    {
        $d = new Dostop();
        return $d->checkDostopAktiven($anketa);
    }

    /**
     * @desc Vrne ID trenutnega uporabnika (ce ni prijavljen vrne 0)
     */
    function uid()
    {
        global $global_user_id;

        return $global_user_id;
    }

    /**
     * @desc Vrne vse uporabnike iz baze
     */
    static function db_select_users()
    {
        return sisplet_query("SELECT name, surname, id, email FROM users ORDER BY name ASC");
    }

    /**
     * @desc Vrne podatke o uporabniku
     */
    static function db_select_user($uid)
    {
        return sisplet_query("SELECT * FROM users WHERE id='$uid'");
    }

    /**
     * TODO ???
     *
     * @param mixed $spremenljivka
     */
    function addMissingGrids($spremenljivka)
    {
        $row = Cache::srv_spremenljivka($spremenljivka);
        $maxGrids = $row['grids'];

        // najprej pobrišemo polja 99,98,97 (na koncu jih spet dodamo)
        $deleteString = "DELETE FROM srv_grid WHERE spr_id='" . $spremenljivka . "' AND (id IN (99,98,97))";
        $sqlD = sisplet_query($deleteString);

        $sqlGrids = sisplet_query("SELECT id, vrstni_red FROM srv_grid WHERE spr_id='$spremenljivka' ORDER BY id");
        $countGrids = mysqli_num_rows($sqlGrids);

        // če imamo v gridu več spremenljivk kot jih rabimo jih pobrišemo
        if ($countGrids > $maxGrids) {
            $deleteString = "DELETE FROM srv_grid WHERE spr_id='" . $spremenljivka . "' AND id > $maxGrids";
            $deleteQuery = sisplet_query($deleteString);
        }

        // dodamo manjkajoče spremenljivke
        if ($countGrids < $maxGrids) {
            for ($i = $countGrids + 1; $i <= $maxGrids; $i++) {
                $rowG = mysqli_fetch_array($sqlGrids);
                if ($rowG['vrstni_red'] != $i) {
                    //nastavimo id na najvecji v vprasanju
                    $sqlID = sisplet_query("SELECT MAX(id) FROM srv_grid WHERE spr_id='$spremenljivka' ");
                    $rowID = mysqli_fetch_array($sqlID);
                    $newId = $rowID['MAX(id)'] + 1;

                    $insertString = "INSERT INTO srv_grid (id, spr_id, vrstni_red, variable) VALUES ('$newId', '$spremenljivka', '$i', '$i')";
                    $sqlInsert = sisplet_query($insertString);
                }
            }
        }

        $this->repareVrednost($spremenljivka);

        // nato samo še dodamo sistemske če je potrebno
        $_otherStatus = array(99 => "-99", 98 => "-98", 97 => "-97");
        $_otherStatusFields = array(
            99 => 'undecided',
            98 => 'rejected',
            97 => 'inappropriate'
        );
        $_otherStatusDefaults = array(
            99 => 'Ne vem',
            98 => 'Zavrnil',
            97 => 'Neustrezno'
        );
        $_updateState = "";

        foreach ($_otherStatus as $status => $statusVariable) {
            // dodamo samo če je čekirano polje v spremenljivki
            if ($row[$_otherStatusFields[$status]] == 1) {
                $sqlUD = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='$spremenljivka' AND vrstni_red='" . $status . "'");
                $rowUD = mysqli_fetch_array($sqlUD);

                if ($rowUD == FALSE) {
                    $sqlUD2 = sisplet_query("SELECT MAX(vrstni_red) FROM srv_grid WHERE spr_id='$spremenljivka' ");
                    $rowUD2 = mysqli_fetch_array($sqlUD2);

                    //nastavimo id na najvecji v vprasanju
                    $id = $rowUD2['MAX(vrstni_red)'] + 1;

                    //vnesemo polje (ne vem, zavrnil, neustrezno) v bazo (default vrednosti: NE VEM, vrstni_red 99, variable 99
                    $sqlUD3String = "INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable) " .
                        "VALUES ('$status', '$spremenljivka', '" . $_otherStatusDefaults[$status] . "', '" . $status . "', '" . $statusVariable . "')";
                    $sqlUD3 = sisplet_query($sqlUD3String);

                }

            }
        }
    }

    /**
     * TODO ???
     *
     * @param mixed $spremenljivka
     */
    function getSpremenljivkaZaporedna($spremenljivka)
    {
        $rowSpr = Cache::srv_spremenljivka($spremenljivka);

        // Preštejemo koliko vprašanj je bilo do sedaj na prejšnih straneh
        $sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='" . $rowSpr['gru_id'] . "'");
        $rowg = mysqli_fetch_assoc($sqlg);
        $vrstni_red = $rowg['vrstni_red'];

        $sqlCountPast = sisplet_query("SELECT count(*) as cnt FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND g.vrstni_red < '$vrstni_red' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
        $rowCount = mysqli_fetch_assoc($sqlCountPast);
        $offset = $rowCount['cnt'];

        // preštejemo katera premenljivka je trenutna
        $stringCountPast = "SELECT count(*) as cnt FROM srv_spremenljivka WHERE gru_id = '" . $rowSpr['gru_id'] . "' AND vrstni_red <= '" . $rowSpr['vrstni_red'] . "' ORDER BY vrstni_red ASC";
        $sqlCountPast = sisplet_query($stringCountPast);
        $rowCountPast = mysqli_fetch_assoc($sqlCountPast);
        return $offset + $rowCountPast['cnt'];
    }

    /**
     * @desc Če je anketa aktivna, preverimo da ni slučajno potekel čas aktivnosti,
     * če je, jo deaktiviramo
     */
    function checkSurveyActive($anketa = null)
    {
        // pretecena anketa, kontroliramo datum na: starts in expire
        sisplet_query("UPDATE srv_anketa SET active = '0' WHERE id='" . ($anketa ? $anketa : $this->anketa) . "' AND active = '1' AND expire < CURDATE()");

        SurveyInfo:: getInstance()->SurveyInit($this->anketa);
        // vsilimo refresh podatkov
        SurveyInfo:: getInstance()->resetSurveyData();

        $sqls = sisplet_query("SELECT active FROM srv_anketa WHERE id='" . ($anketa ? $anketa : $this->anketa) . "'");
        $rows = mysqli_fetch_assoc($sqls);
        return $rows['active'];
    }

    /**
     * preview
     *
     */
    private function getTipPreviewHtml(){
        global $lang;
        global $global_user_id;


        // Predpregled tipa vprašanj - prikazujemo samo kadar smo v urejanju ankete
        if (!$this->anketa > 0)
            return;

        if ( ($_GET['a'] != '' || !isset($_GET['anketa'])) && $_GET['a'] != 'branching' )
            return;


        echo '<div id="tip_preview">';

        echo '<div class="top-left"></div><div class="top-right"></div><div class="inside">';


        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);

        // tip 9999 - IF
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9999" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_toolbox_if'];      
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='if')){
            $userAccess->displayNoAccessText($what='if');
        }
        echo '</div>';

        // tip 9998 - Block
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9998" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_toolbox_block'];
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='block')){
            $userAccess->displayNoAccessText($what='block');
        }
        echo '</div>';

        // tip 9997 - Loop
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9997" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_toolbox_loop'];
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='loop')){
            $userAccess->displayNoAccessText($what='loop');
        }
        echo '</div>';


        // tip 1_1 - radio
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: radio.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '</div>';
        echo '<div class="tip_sample_option"><input type="radio" checked />' . $lang['srv_tip_sample_t1_o1'] . '</div>';
        echo '<div class="tip_sample_option"><input type="radio" />' . $lang['srv_tip_sample_t1_o2'] . '</div>';
        echo '<div class="tip_sample_option"><input type="radio" />' . $lang['srv_tip_sample_t1_o3'] . '</div>';
        echo '</div>';
        echo '</div>';
		
		// tip 1_10 - gdpr
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_10" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: radio.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_gdpr_intro_title'] . '</div><br />';
        echo '<div class="tip_sample_text">' . $lang['srv_gdpr_intro'] . '.<br />'.$lang['srv_gdpr_intro4'].'</div>';
        echo '<div class="tip_sample_option"><input type="radio" checked />' . $lang['srv_gdpr_intro_no'] . '</div>';
        echo '<div class="tip_sample_option"><input type="radio" />' . $lang['srv_gdpr_intro_yes'] . '</div>';
        echo '</div>';
        echo '</div>';

        // tip 1 - radio (horizontalno)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_1" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: radio.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '&nbsp;<span style="font-weight: normal;"><input type="radio" checked />' . $lang['srv_tip_sample_t1_o1'] . '&nbsp;<input type="radio" />' . $lang['srv_tip_sample_t1_o2'] . '</span></div>';
        echo '</div>';
        echo '</div>';

        // tip 1_2 - radio (horizontalno - nova vrstica)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: radio.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '</div>';
        echo '<div class="tip_sample_option"><input type="radio" />' . $lang['srv_tip_sample_t1_o1'] . '&nbsp;<input type="radio" checked />' . $lang['srv_tip_sample_t1_o2'] . '&nbsp;<input type="radio" />' . $lang['srv_tip_sample_t1_o3'] . '</div>';
        echo '</div>';
        echo '</div>';

        // tip 1_5 - radio potrditev
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_5" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: radio.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1_5'] . '</div>';
        echo '<div class="tip_sample_option gray"><input type="button" disabled value="' . $lang['srv_potrdi'] . '"></div>';
        echo '</div>';
        echo '</div>';

        // tip 1_6 - select box
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_6" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: radio.</div>';
        //echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t3'] . '</div>';
        echo '<div class="tip_sample_option"><select size="3"><option>' . $lang['srv_tip_sample_t3_oc'] . '</option><option>' . $lang['srv_tip_sample_t3_oc'] . '</option><option>' . $lang['srv_tip_sample_t3_oc'] . '</option></select></div>';
        echo '</div>';
        echo '</div>';

        // tip 1_8 - Drag-drop
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_8" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        //echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '</div>';

        echo '<div style="float: left; width: 150px; height: 110px; border-right: 1px black solid;">';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t1_o1'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t1_o2'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t1_o3'] . '</div></div>';
        echo '</div>';

        echo '<div style="float: left; width: 150px; height: 110px; margin-left: 30px;">';
        echo '<div class="tip_sample_option"><div class="dragdrop_preview_frame"></div></div>';
        echo '</div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 1_9 - custom radio picture
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_9" class="tip_preview_sub">';
            echo '<div class="tip_sample">';

                echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '</div>';
                // star
                echo '<div style="clear: both;padding-bottom:10px;">';
                for ($i = 1; $i < 4; $i++) {
                    echo '<div class="variabla custom_radio"><label><input type="radio"><span class="enka-custom-radio star"></span><div class="custom_radio_answer">(' . $i . ')</div></label> </div>';
                }
                echo '</div>';

                // thumb
                echo '<div style="clear: both;padding-bottom:10px;">';
                for ($i = 1; $i < 4; $i++) {
                    echo '<div class="variabla custom_radio"><label><input type="radio"><span class="enka-custom-radio  thumb"></span><div class="custom_radio_answer">(' . $i . ')</div></label> </div>';
                }
                echo '</div>';

                //smiley
                echo '<div style="clear: both;padding-bottom:10px;">';
                for ($i = 1; $i < 4; $i++) {
                    echo '<div class="variabla custom_radio"><label><input type="radio"><span class="enka-custom-radio smiley"></span><div class="custom_radio_answer">(' . $i . ')</div></label> </div>';
                }
                echo '</div>';

                // heart
                echo '<div style="clear: both;padding-bottom:10px;">';
                for ($i = 1; $i < 4; $i++) {
                    echo '<div class="variabla custom_radio"><label><input type="radio"><span class="enka-custom-radio heart"></span><div class="custom_radio_answer">(' . $i . ')</div></label> </div>';
                }
                echo '</div>';

                // flag
                echo '<div style="clear: both;padding-bottom:10px;">';
                for ($i = 1; $i < 4; $i++) {
                    echo '<div class="variabla custom_radio"><label><input type="radio"><span class="enka-custom-radio flag"></span><div class="custom_radio_answer">(' . $i . ')</div></label> </div>';
                }
                echo '</div>';

                // user
                echo '<div style="clear: both;">';
                for ($i = 1; $i < 4; $i++) {
                    echo '<div class="variabla custom_radio"><label><input type="radio"><span class="enka-custom-radio user"></span><div class="custom_radio_answer">(' . $i . ')</div></label> </div>';
                }
                echo '</div>';

            echo '</div>';
        echo '</div>';

        // tip 1_10 - Image HotSpot
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_10" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        //echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_hotspot_preview_text_radio'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/hotspot.png" /></div>';
        echo '</div>';
        echo '</div>';

        // tip 1_11 - Vizualna anlaogna skala
        echo '<div name="tip_preview_sub" id="tip_preview_sub_1_11" class="tip_preview_sub">';
            echo '<div class="tip_sample">';
            echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '</div>';

                echo '<div style="clear: both;padding-bottom:10px;">';
                for ($i = 1; $i < 7; $i++) {
                    echo '<div class="variabla custom_radio visual-radio-scale" style="padding: 0 5px;">
                                    <label>
                                        <input type="radio">
                                        <span class="enka-vizualna-skala siv-7'.$i.'"></span>
                                        <div class="custom_radio_answer">('.$i.')</div>
                                    </label>
                              </div>';
                    }
                echo '</div>';

            echo '</div>';
        echo '</div>';


        // tip 2 - checkbox
        echo '<div name="tip_preview_sub" id="tip_preview_sub_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: checkbox.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t2'] . '</div>';
        echo '<div class="tip_sample_option"><input type="checkbox" checked="checked"/>' . $lang['srv_tip_sample_t2_o1'] . '</div>';
        echo '<div class="tip_sample_option"><input type="checkbox" />' . $lang['srv_tip_sample_t2_o2'] . '</div>';
        echo '<div class="tip_sample_option"><input type="checkbox" checked="checked"/>' . $lang['srv_tip_sample_t2_o3'] . '</div>';
        echo '</div>';
        echo '</div>';

        // tip 2_1 - check(horizontalno)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_2_1" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t2'] . '&nbsp;<span style="font-weight: normal;"><input type="checkbox" checked="checked"/>' . $lang['srv_tip_sample_t2_o2'] . '&nbsp;<input type="checkbox" checked="checked"/>' . $lang['srv_tip_sample_t2_o3'] . '</span></div>';
        echo '</div>';
        echo '</div>';

        // tip 2_2 - check (horizontalno - nova vrstica)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_2_2" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t2'] . '</div>';
        echo '<div class="tip_sample_option"><input type="checkbox" checked="checked"/>' . $lang['srv_tip_sample_t2_o1'] . '&nbsp;<input type="checkbox" />' . $lang['srv_tip_sample_t2_o2'] . '&nbsp;<input type="checkbox" checked="checked"/>' . $lang['srv_tip_sample_t2_o3'] . '</div>';
        echo '</div>';
        echo '</div>';

        // tip 2_8 - Drag-drop
        echo '<div name="tip_preview_sub" id="tip_preview_sub_2_8" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t1'] . '</div>';

        echo '<div style="float: left; width: 150px; height: 110px; border-right: 1px black solid;">';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t1_o1'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t1_o2'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t1_o3'] . '</div></div>';
        echo '</div>';

        echo '<div style="float: left; width: 150px; height: 110px; margin-left: 30px;">';
        echo '<div class="tip_sample_option"><div class="dragdrop_preview_frame"></div></div>';
        echo '</div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 2_10 - Image HotSpot
        echo '<div name="tip_preview_sub" id="tip_preview_sub_2_10" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        //echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_hotspot_preview_text_checkbox'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/hotspot.png" /></div>';
        echo '</div>';
        echo '</div>';

        // tip 3 - select
        echo '<div name="tip_preview_sub" id="tip_preview_sub_3" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: select.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t3'] . '</div>';
        echo '<div class="tip_sample_option"><select><option>' . $lang['srv_tip_sample_t3_oc'] . '</option></select></div>';
        echo '</div>';
        echo '</div>';

        // tip 21 - besedilo*
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21'] . '</div>';
        echo '<div class="tip_sample_option"><textarea style="width:150px; height:12px">abc</textarea></div>';
        echo '</div>';
        echo '</div>';

        // tip 21_1 - cpatcha
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_1" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21_1_o1'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/captcha.jpg" /></div>';
        echo '<div class="tip_sample_option"><input type="text" value="VZHVP" /></div>';
        echo '</div>';
        echo '</div>';

        // tip 21_2 - email
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21_2_o1'] . '</div>';
        echo '<div class="tip_sample_option">(' . $lang['srv_email_example'] . ')</div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<p style="font-size:smaller; color:gray;">' . $lang['srv_email_example_txt'] . '</p>';
        echo '</div>';
        echo '</div>';

        // tip 21_3 - url
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_3" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21_3_o1'] . '</div>';
        echo '<div class="tip_sample_option">(' . $lang['srv_url_example'] . ')</div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '</div>';
        echo '</div>';

        // tip 21_4 - upload
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_4" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21_4_o1'] . '</div>';
        echo '<div class="tip_sample_option"><input type="file" /></div>';
        echo '</div>';
        echo '</div>';

        // tip 21_5 - textbox box
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_5" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21'] . '</div>';
        echo '<div class="tip_sample_option"><textarea style="width:250px; height:36px">abc</textarea></div>';
        echo '</div>';
        echo '</div>';

		// tip 21_6 - elektronski podpis
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_6" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21_6'] . '</div>';
        echo '<div class="tip_sample_option"><div style="width:250px; height:50px; border: 1px grey solid; background-color: white;"></div></div>';
		echo '<div class="clr"></div>';
		echo '<input type="button" value="'.$lang['srv_signature_clear'].'" style="margin: 5px;" />';
		echo '<br />'.$lang['srv_signature_name'].' <input type="text" />';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_signature')){
            $userAccess->displayNoAccessText($what='question_type_signature');
        }
        echo '</div>';

        // tip 21_7 - fotografija
        echo '<div name="tip_preview_sub" id="tip_preview_sub_21_7" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t21_7'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/webcam_record.png" height="64"/></div>';
        echo '</div>';
        echo '</div>';

        // tip 5 - label
        echo '<div name="tip_preview_sub" id="tip_preview_sub_5" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t5'] . '</div>';
        echo '</div>';
        echo '</div>';

		// tip 5_2 - nagovor za aktivacijo chata
        echo '<div name="tip_preview_sub" id="tip_preview_sub_5_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: text.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_chat_question_text'] . '</div>';
        echo '<div class="tip_sample_option"><div class="tawk-chat-activation button" style="padding:6px 0px;">'.$lang['srv_chat_turn_on'].'</div></div>';
        echo '</div>';
        echo '</div>';

        // tip 26 - lokacija
        echo '<div name="tip_preview_sub" id="tip_preview_sub_26" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t26_1'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/mojalokacija.png" /></div>';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_location')){
            $userAccess->displayNoAccessText($what='question_type_location');
        }
        echo '</div>';

        // tip 26_2 - multi lokacija
        echo '<div name="tip_preview_sub" id="tip_preview_sub_26_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t26_2'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/lokacija.png" /></div>';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_location')){
            $userAccess->displayNoAccessText($what='question_type_location');
        }
        echo '</div>';

        // tip 26_1 - moja lokacija
        echo '<div name="tip_preview_sub" id="tip_preview_sub_26_1" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t26_1'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/mojalokacija.png" /></div>';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_location')){
            $userAccess->displayNoAccessText($what='question_type_location');
        }
        echo '</div>';

        // tip 6 - multigrid
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6'] . '</div>';
        echo '<div class="tip_sample_option"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t6_o1'] . '</span><span>' . $lang['srv_tip_sample_t6_o2'] . '</span> <span>' . $lang['srv_tip_sample_t6_o3'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v1'] . '</div><span><input type="radio" name="a" /></span><span><input type="radio" checked name="a" /></span><span><input type="radio" name="a" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v2'] . '</div><span><input type="radio" name="b" /></span><span><input type="radio" /></span><span><input type="radio" name="b" checked /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v3'] . '</div><span><input type="radio" name="c" /></span><span><input type="radio" /></span><span><input type="radio" name="c" checked /></span></div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_1 - multigrid (semanticni diferencial)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_1" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t61'] . ':</div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span>1</span><span>2</span><span>3</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div style="text-align: right;">' . $lang['srv_tip_sample_t61_v1x'] . '</div><span><input type="radio" name="d" /></span><span><input type="radio" name="d" /></span><span><input type="radio"name="d"  /></span>' . $lang['srv_tip_sample_t61_v1y'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div style="text-align: right;">' . $lang['srv_tip_sample_t61_v2x'] . '</div><span><input type="radio" name="e" /></span><span><input type="radio" name="e" /></span><span><input type="radio" name="e" /></span>' . $lang['srv_tip_sample_t61_v2y'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div style="text-align: right;">' . $lang['srv_tip_sample_t61_v3x'] . '</div><span><input type="radio" name="f" /></span><span><input type="radio" name="f" checked /></span><span><input type="radio" /></span>' . $lang['srv_tip_sample_t61_v3y'] . '</div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_2 - multigrid (dropdown)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6'] . '</div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v1'] . '</div><span><select><option>' . $lang['srv_tip_sample_t6_o1'] . '</option></select></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v2'] . '</div><span><select><option>' . $lang['srv_tip_sample_t6_o1'] . '</option></select></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v3'] . '</div><span><select><option>' . $lang['srv_tip_sample_t6_o1'] . '</option></select></span></div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_3 - multigrid (double grid)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_3" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6'] . '</div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span></span><span>' . $lang['srv_tip_sample_t6_v1'] . '</span><span style="border-right: 1px black solid;"></span><span></span><span>' . $lang['srv_tip_sample_t6_v3'] . '</span><span></span></div>';
        echo '<div style="clear:left"></div>';

        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t6_o1'] . '</span><span>' . $lang['srv_tip_sample_t6_o2'] . '</span><span style="border-right: 1px black solid;">' . $lang['srv_tip_sample_t6_o3'] . '</span><span>&nbsp;' . $lang['srv_tip_sample_t6_o1'] . '</span><span>' . $lang['srv_tip_sample_t6_o2'] . '</span><span>' . $lang['srv_tip_sample_t6_o3'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>www.xxx.si</div><span><input type="radio" /></span><span><input type="radio" /></span><span style="border-right: 1px black solid;"><input type="radio" name="g" checked /></span><span><input type="radio" name="l" checked /></span><span><input type="radio" /></span><span><input type="radio" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>www.yyy.si</div><span><input type="radio" /></span><span><input type="radio" name="j" checked /></span><span style="border-right: 1px black solid;"><input type="radio" /></span><span><input type="radio" /></span><span><input type="radio" name="h" checked /></span><span><input type="radio" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>www.zzz.si</div><span><input type="radio" /></span><span><input type="radio" name="i" checked /></span><span style="border-right: 1px black solid;"><input type="radio" /></span><span><input type="radio" /></span><span><input type="radio" /></span><span><input type="radio" name="k" checked /></span></div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';


        // tip 6_4 - multigrid (one against another)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_4" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: one against another.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6_4'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div style="text-align: right;">' . $lang['srv_tip_sample_t6_4_v1'] . '</div><span><input type="radio" name="l" checked/></span><span>' . $lang['srv_tip_sample_t6_4_vmes'] . '</span><span><input type="radio"name="l"  /></span>' . $lang['srv_tip_sample_t6_4_v2'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div style="text-align: right;">' . $lang['srv_tip_sample_t6_4_v1'] . '</div><span><input type="radio" name="m" /></span><span>' . $lang['srv_tip_sample_t6_4_vmes'] . '</span><span><input type="radio" name="m" checked/></span>' . $lang['srv_tip_sample_t6_4_v3'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div style="text-align: right;">' . $lang['srv_tip_sample_t6_4_v3'] . '</div><span><input type="radio" name="n" checked/></span><span>' . $lang['srv_tip_sample_t6_4_vmes'] . '</span><span><input type="radio" name="n" /></span>' . $lang['srv_tip_sample_t6_4_v2'] . '</div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_5 - multigrid (max diff)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_5" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: max diff.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6_5'] . '</div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t6_5_c1'] . '</span><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t6_5_c2'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span><input type="radio" name="o" checked/></span><div style="text-align: center;">' . $lang['srv_tip_sample_t6_5_v1'] . '</div><span><input type="radio" name="p" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span><input type="radio" name="o" /></span><div style="text-align: center;">' . $lang['srv_tip_sample_t6_5_v2'] . '</div><span><input type="radio" name="p" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span><input type="radio" name="o" /></span><div style="text-align: center;">' . $lang['srv_tip_sample_t6_5_v3'] . '</div><span><input type="radio" name="p" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option2"><div>&nbsp;</div><span><input type="radio" name="o" /></span><div style="text-align: center;">' . $lang['srv_tip_sample_t6_5_v4'] . '</div><span><input type="radio" name="p" checked/></span></div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_6 - multigrid (select box)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_6" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6_6'] . '</div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_6_v1'] . '</div><span><select multiple=""><option>' . $lang['srv_tip_sample_t6_6_o1a'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o2a'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o3a'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o4a'] . '</option></select></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_6_v2'] . '</div><span><select multiple=""><option>' . $lang['srv_tip_sample_t6_6_o1b'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o2b'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o3b'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o4b'] . '</option></select></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_6_v3'] . '</div><span><select multiple=""><option>' . $lang['srv_tip_sample_t6_6_o1c'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o2c'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o3c'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o4c'] . '</option></select></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_6_v4'] . '</div><span><select multiple=""><option>' . $lang['srv_tip_sample_t6_6_o1d'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o2d'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o3d'] . '</option><option>' . $lang['srv_tip_sample_t6_6_o4d'] . '</option></select></span></div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_8 - multigrid (Tabela Da/Ne)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_8" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6'] . '</div>';
        echo '<div class="tip_sample_option"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t6_8_o1'] . '</span><span>' . $lang['srv_tip_sample_t6_8_o2'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v1'] . '</div><span><input type="radio" checked name="a" /></span><span><input type="radio" name="a" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v2'] . '</div><span><input type="radio" /></span><span><input type="radio" name="b" checked /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t6_v3'] . '</div><span><input type="radio" /></span><span><input type="radio" name="c" checked /></span></div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

        // tip 6_9 - Drag-drop
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_9" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>'; //$lang['srv_new_question_icon']
        echo '<span>' . $lang['srv_new_question'] . '</span>';

        echo '<div class="tip_sample">';

        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t6'] . '</div>';

        echo '<div style="float: left; width: 200px; height: 150px; border-right: 1px black solid;">';
        echo '<div class="tip_sample_option"></div><br /><br />';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t6_v1'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t6_v2'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t6_v3'] . '</div></div>';
        echo '</div>';

        echo '<div style="float: left; width: 200px; height: 180px; margin-left: 20px;">';
        echo '<div class="tip_sample_option">';
        echo '<ul style="list-style-type: none;">';
        //echo '<div>'.$lang['srv_tip_sample_t6_o1'].'</div><div>tralrarla</div><div>'.$lang['srv_tip_sample_t6_o2'].'</div><div>'.$lang['srv_tip_sample_t6_o3'].'</div><br />';
        echo '<li >
											<div class="dragdrop_preview_frame_grid_title">' . $lang['srv_tip_sample_t6_o1'] . '</div>
										</li>';    //izpis "naslova" okvirja
        echo '<li>
											<div class="dragdrop_preview_frame_grid"></div>
										</li>' . "\n";    //izpis okvirja
        echo '<li >
											<div class="dragdrop_preview_frame_grid_title">' . $lang['srv_tip_sample_t6_o2'] . '</div>
										</li>';    //izpis "naslova" okvirja
        echo '<li>
											<div class="dragdrop_preview_frame_grid"></div>
										</li>' . "\n";    //izpis okvirja
        echo '<li >
											<div class="dragdrop_preview_frame_grid_title">' . $lang['srv_tip_sample_t6_o3'] . '</div>
										</li>';    //izpis "naslova" okvirja
        echo '<li>
											<div class="dragdrop_preview_frame_grid"></div>
										</li>' . "\n";    //izpis okvirja
        echo '</ul>';
        echo '</div>';
        echo '</div>';

        echo '<div class="clr"></div>';

        echo '</div>';
        echo '</div>';

		// tip 6_10 - Image HotSpot
        echo '<div name="tip_preview_sub" id="tip_preview_sub_6_10" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: label.</div>';
        //echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_hotspot_preview_text_radio_grid'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_0/hotspot.png" /></div>';
        echo '</div>';
        echo '</div>';


        // tip 16 - multicheckbox
        echo '<div name="tip_preview_sub" id="tip_preview_sub_16" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multicheckbox.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t16'] . '</div>';
        echo '<div class="tip_sample_option"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t16_o1'] . '</span><span>' . $lang['srv_tip_sample_t16_o2'] . '</span> <span>' . $lang['srv_tip_sample_t16_o3'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t16_v1'] . '</div><span><input type="checkbox" checked /></span><span><input type="checkbox" /></span><span><input type="checkbox" checked /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t16_v2'] . '</div><span><input type="checkbox" checked /></span><span><input type="checkbox" /></span><span><input type="checkbox" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t16_v3'] . '</div><span><input type="checkbox" /></span><span><input type="checkbox" checked /></span><span><input type="checkbox" checked /></span></div>';
        echo '<div class="clr"></div>';
        echo '</div>';
        echo '</div>';

        // tip 19 - multitext
        echo '<div name="tip_preview_sub" id="tip_preview_sub_19" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multitext.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t19'] . '</div>';
        echo '<div class="tip_sample_option"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t19_o1'] . '</span><span>' . $lang['srv_tip_sample_t19_o2'] . '</span><span>' . $lang['srv_tip_sample_t19_o3'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t19_v1'] . '</div><span><input type="text" size="10" value="abc" /></span><span><input type="text" size="10" value="abc" /></span><span><input type="text" size="10" value="abc" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t19_v2'] . '</div><span><input type="text" size="10" value="abc" /></span><span><input type="text" size="10" value="abc" /></span><span><input type="text" size="10" value="abc" /></span></div>';
        echo '<div class="clr"></div>';
        echo '</div>';
        echo '</div>';

        // tip 20 - multinumber
        echo '<div name="tip_preview_sub" id="tip_preview_sub_20" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multinumber.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t20'] . '</div>';
        echo '<div class="tip_sample_option"><div>&nbsp;</div><span>' . $lang['srv_tip_sample_t20_o1'] . '</span><span>' . $lang['srv_tip_sample_t20_o2'] . '</span><span>' . $lang['srv_tip_sample_t20_o3'] . '</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t20_v1'] . '</div><span><input type="text" size="10" value="123" /></span><span><input type="text" size="10" value="123" /></span><span><input type="text" size="10" value="123" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t20_v2'] . '</div><span><input type="text" size="10" value="123" /></span><span><input type="text" size="10" value="123" /></span><span><input type="text" size="10" value="123" /></span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><div>' . $lang['srv_tip_sample_t20_v3'] . '</div><span><input type="text" size="10" value="123" /></span><span><input type="text" size="10" value="123" /></span><span><input type="text" size="10" value="123" /></span></div>';
        echo '<div class="clr"></div>';
        echo '</div>';
        echo '</div>';

        // tip 7 - number
        echo '<div name="tip_preview_sub" id="tip_preview_sub_7" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: number.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t7'] . '</div>';
        echo '<div class="tip_sample_option">' . $lang['srv_tip_sample_t7_o1'] . '<input type="text" value="123" /></div>';
        echo '</div>';
        echo '</div>';

		// tip 7_2 - slider
        echo '<div name="tip_preview_sub" id="tip_preview_sub_7_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: number.</div>';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t7_2'] . '</div>';
        echo '<div class="tip_sample_option"><img src="img_new/slider.png" height="40"/></div>';
        echo '</div>';
        echo '</div>';

        // tip 8 - datum
        echo '<div name="tip_preview_sub" id="tip_preview_sub_8" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: datum.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t8'] . '</div>';
        echo '<div class="tip_sample_option"><input type="text" size="20"/><span id="starts_img" class="sprites calendar" style="float:none; margin-bottom:0"></span></div>';
        echo '</div>';
        echo '</div>';

        // tip 17 - Razvrščanje prestavljanje (default)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_17" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t17'] . '</div>';

        echo '<div style="float: left; width: 150px; height: 110px; border-right: 1px black solid;">';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t17_o1'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t17_o2'] . '</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t17_o3'] . '</div></div>';
        echo '</div>';

        echo '<div style="float: left; width: 150px; height: 110px; margin-left: 30px;">';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview_frame">1</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview_frame">2</div></div>';
        echo '<div class="tip_sample_option"><div class="razvrscanje_preview_frame">3</div></div>';
        echo '</div>';
        echo '<div class="clr"></div>';

        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_ranking')){
            $userAccess->displayNoAccessText($what='question_type_ranking');
        }
        echo '</div>';

        // tip 171 - Razvrščanje ostevilcevanje (design = 1)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_17_1" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t171'] . '</div>';

        echo '<div class="tip_sample_option" style="margin-top: 5px;"><input type="text" size="1"/>' . $lang['srv_tip_sample_t171_o1'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option" style="margin-top: 5px;"><input type="text" size="1"/>' . $lang['srv_tip_sample_t171_o2'] . '</div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option" style="margin-top: 5px;"><input type="text" size="1"/>' . $lang['srv_tip_sample_t171_o3'] . '</div>';
        echo '<div style="clear:left"></div>';

        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_ranking')){
            $userAccess->displayNoAccessText($what='question_type_ranking');
        }
        echo '</div>';

        // tip 172 - Razvrščanje premikanje (design = 2)
        echo '<div name="tip_preview_sub" id="tip_preview_sub_17_2" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: multigrid.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t172'] . '</div>';
        echo '<div class="tip_sample_option"><span>&nbsp;</span><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t172_o1'] . '</div><span>&nbsp;</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><span>&nbsp;</span><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t172_o2'] . '</div><span>&nbsp;</span></div>';
        echo '<div style="clear:left"></div>';
        echo '<div class="tip_sample_option"><span>&nbsp;</span><div class="razvrscanje_preview">' . $lang['srv_tip_sample_t172_o3'] . '</div><span>&nbsp;</span></div>';
        echo '<div class="clr"></div>';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_ranking')){
            $userAccess->displayNoAccessText($what='question_type_ranking');
        }
        echo '</div>';

        // tip 18 - vsota
        echo '<div name="tip_preview_sub" id="tip_preview_sub_18" class="tip_preview_sub">';
        //        echo '<div>Primer tipa vprašanj: vsota.</div>';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample" style="text-align: right;">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t18'] . '</div>';
        echo '<div class="tip_sample_option">' . $lang['srv_tip_sample_t18_o1'] . '<input type="text" size="8" style="margin-bottom: 3px;" value="9" /></div>';
        echo '<div class="tip_sample_option">' . $lang['srv_tip_sample_t18_o2'] . '<input type="text" size="8" style="margin-bottom: 3px;" value="10" /></div>';
        echo '<div class="tip_sample_option">' . $lang['srv_tip_sample_t18_o3'] . '<input type="text" size="8" style="margin-bottom: 3px;" value="5" /></div>';
        echo '<div class="tip_sample_option" style="border-top: 1px black solid;">' . $lang['srv_tip_sample_t18_o4'] . '<input type="text" size="8" style="margin-top: 3px;" value="24" /></div>';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_sum')){
            $userAccess->displayNoAccessText($what='question_type_sum');
        }
        echo '</div>';

        // tip 24 - Kombinirana tabela
        echo '<div name="tip_preview_sub" id="tip_preview_sub_24" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_survey_table_multiple'];
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_multitable')){
            $userAccess->displayNoAccessText($what='question_type_multitable');
        }
        echo '</div>';

        // tip 27 - Heatmap
        echo '<div name="tip_preview_sub" id="tip_preview_sub_27" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_vprasanje_heatmap'];
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_heatmap')){
            $userAccess->displayNoAccessText($what='question_type_heatmap');
        }
        echo '</div>';

        // tip 22 - Kalkulacija
        echo '<div name="tip_preview_sub" id="tip_preview_sub_22" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_vprasanje_tip_long_22'];
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_calculation')){
            $userAccess->displayNoAccessText($what='question_type_calculation');
        }
        echo '</div>';

        // tip 25 - Kvota
        echo '<div name="tip_preview_sub" id="tip_preview_sub_25" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question_icon'] . '</span>';
        echo '<div class="tip_sample">';
        echo $lang['srv_vprasanje_tip_long_25'];
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='question_type_quota')){
            $userAccess->displayNoAccessText($what='question_type_quota');
        }
        echo '</div>';

        // tip 9 - SN-imena
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t9'] . ':</div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div><span class="faicon add small icon-blue"></span>' . $lang['srv_add_field'] . '</div>';
        echo '</div>';
        if(!$userAccess->checkUserAccess($what='social_network')){
            $userAccess->displayNoAccessText($what='social_network');
        }
        echo '</div>';

        // tip 9_1 - SN-imena - fiksno st. polj
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9_1" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t9'] . ':</div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '</div>';
        echo '</div>';

        // tip 9_2 - SN-imena - 1 textbox
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9_2" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t9'] . ':</div>';
        echo '<div class="tip_sample_option"><textarea rows="10"></textarea></div>';
        echo '</div>';
        echo '</div>';

        // tip 9_3 - SN-imena - vnos stevila polj
        echo '<div name="tip_preview_sub" id="tip_preview_sub_9_3" class="tip_preview_sub">';
        echo '<span>' . $lang['srv_new_question'] . '</span>';
        echo '<div class="tip_sample">';
        echo '<div class="tip_sample_text">' . $lang['srv_tip_sample_t9'] . ':</div>';
        echo '<div class="tip_sample_option">' . $lang['srv_design_count'] . ': <input type="text" size="4" style="margin-bottom: 3px;" value="3" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '<div class="tip_sample_option"><input type="text" /></div>';
        echo '</div>';
        echo '</div>';

        // demografska vprasanja
        include_once('../../main/survey/app/global_function.php');
        $Survey = new \App\Controllers\SurveyController(true);
        save('forceShowSpremenljivka', true);

        $dem = Array('XSPOL', 'XSTAR2a4', 'XZST1surs4', 'XDS2a4', 'XIZ1a2', 'XLOKACREGk', 'XPODJPRIH');

        foreach ($dem AS $key) {

            $id = Demografija::getInstance()->getSpremenljivkaID($key);

            if ($id > 0) {
                echo '<div name="tip_preview_sub" id="tip_preview_sub_' . $id . '" class="tip_preview_sub">';
                echo '<span>' . $lang['srv_new_question'] . '</span>';
                echo '<div class="tip_sample">';

                     \App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($id);

                echo '</div>';
                echo '</div>';
            }
        }


        echo '</div><div class="bottom-left"></div><div class="bottom-right"></div>';

        echo '</div>'; // tip_preview
    }

    /**
     * porihtana funkcija, da poklice SurveyRespondents::checkSystemVariables(), ki je zadolzena za dodajanje sistemskih spremenljivk
     *
     * @param mixed $phone
     * @param mixed $email
     */
    function createUserbaseSystemVariable($phone, $email, $language = 0)
    {
        $user_base = 0;
        $cookie = -1;

        $rowb = SurveyInfo::getInstance()->getSurveyRow();

        $phone = (int)(SurveyInfo::getInstance()->checkSurveyModule('phone') || (int)$phone == 1);
        $email = (int)(SurveyInfo::getInstance()->checkSurveyModule('email') || (int)$email == 1);

        $variables = array();
        if ($phone == 1) {
            array_push($variables, "telefon");
            $user_base = 1;
        }
        if ($email == 1) {
            array_push($variables, "email");
            $user_base = 1;
        }
        if ($language == 1) {
            array_push($variables, "language");
        }

        SurveyRespondents:: getInstance()->Init($this->anketa);
        SurveyRespondents:: checkSystemVariables($variables);

        if ($rowb['user_base'] != $user_base || (int)SurveyInfo::getInstance()->checkSurveyModule('phone') != $phone || (int)SurveyInfo::getInstance()->checkSurveyModule('email') != $email) {// nastavimo še userbase
            // v userbase vedno prikazujemo uvod
            if ($user_base == 1)
                $intro = " '1' ";
            else
                $intro = " show_intro ";
            $sql = sisplet_query("UPDATE srv_anketa SET  user_base='$user_base', phone='$phone', email='$email', show_intro=$intro WHERE id='" . $this->anketa . "'");
            // vsilimo refresh podatkov
            SurveyInfo:: getInstance()->resetSurveyData();
        }

        return SurveyInfo::getInstance()->getSurveyRow();
    }

    /**
     * TODO ***
     *
     * @param mixed $curent_id
     * @param mixed $errorMsg
     */
    function show_email_invitation_templates($curent_id = 1, $errorMsg = null)
    {
        global $lang;
        echo '<fieldset style="border:1px solid gray; padding:10px;">';
        echo '<legend>Predloge:</legend>';

        echo '<div class="email_invitations_holder">';
        echo '	<div id="email_invitations" class="select">';
        $sql_email_invitations_profiles = sisplet_query("SELECT id, name FROM srv_userbase_invitations");
        while ($row_email_invitations_profiles = mysqli_fetch_assoc($sql_email_invitations_profiles)) {
            echo '<div class="option' . ($row_email_invitations_profiles['id'] == $curent_id ? ' active' : '') . '" value="' . $row_email_invitations_profiles['id'] . '">' . $row_email_invitations_profiles['name'] . '</div>';
        }
        echo '	</div>';
        echo '</div>';

        echo '<div id="email_invitations_values" >';
        $this->show_email_invitation_values($curent_id, $errorMsg);
        echo '</div>';
        echo '<div class="clr"></div>';
        echo '</fieldset>';
        echo '<script type="text/javascript">';
        echo '$(document).ready(function() {' .
            '  $("#email_invitations .option").click(function() {' .
            '  $("#email_invitations .option").each(function () {' .
            '    $(this).removeClass("active"); });' .
            '    $(this).addClass("active");' .
            '    change_email_invitations_template($(this).attr(\'value\'));' .
            '	});';
        echo '});';
        echo '</script>';
    }

    /**
     * TODO ????
     *
     * @param mixed $curent_id
     * @param mixed $errorMsg
     */
    function show_email_invitation_values($curent_id = 1, $errorMsg = null)
    {
        global $lang;
        $sql_email_invitations_profiles = sisplet_query("SELECT name, subject, text FROM srv_userbase_invitations WHERE id = '" . $curent_id . "'");
        $row_email_invitations_profiles = mysqli_fetch_assoc($sql_email_invitations_profiles);
        $temp_name = $row_email_invitations_profiles['name'];
        $temp_subject = $row_email_invitations_profiles['subject'];
        $temp_text = $row_email_invitations_profiles['text'];
        if ($errorMsg != null) {
            echo '<div id="div_error" class="red"><img src="icons/icons/error.png" alt="" vartical-align="middle" />' . $errorMsg . '</div>';
        }

        echo '<div style="margin-bottom:10px;">';
        echo '<p> <span class="labelSpan" >Ime:</span>';
        echo '<input type="text" disabled="disabled" value="' . $temp_name . '" style="width:285px;"></p>';
        echo '<p> <span class="labelSpan" >Zadeva:</span>';
        echo '<input type="text" id="email_invitation_value_subject" disabled="disabled" value="' . $temp_subject . '" style="width:285px;"></p>';
        echo '<p> <span class="labelSpan" >' . $lang['text'] . ':</span>';
        echo '<div id="email_invitation_value_text">' . $temp_text . '</div></p>';
        echo '</div>';
        echo '<div class="clr"></div>';
        echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="email_invitation_use_template(\'' . $curent_id . '\'); return false;"><span><img src="icons/icons/cog_back.png" alt="" vartical-align="middle" />uporabi predlogo</span></a></div></span>';
        if ($curent_id > 1) {
            $confirmDelete = "Ali ste prepričani da želite izbristai predlogo: " . $temp_name . "?";
            echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_blue" href="#" onclick="edit_email_invitations(\'' . $curent_id . '\'); return false;"><span><img src="icons/icons/cog_edit.png" alt="" vartical-align="middle" />uredi predlogo</span></a></div></span>';
            echo '  <span class="floatLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_red" href="#" onclick="email_invitation_delete_template(\'' . $curent_id . '\',\'' . $confirmDelete . '\'); return false;"><span><img id="email_save" src="icons/icons/cog_delete.png" alt="" vartical-align="middle" />izbriši predlogo</span></a></div></span>';
        }
        echo '	<div class="clr"></div>';

    }

    /**
     * TODO ???
     *
     * @param mixed $curent_id
     * @param mixed $errorMsg
     */
    function show_userbase_respondents_lists($curent_id = null, $errorMsg = null)
    {
        echo '<fieldset><legend>Liste respondentov' . '</legend>';
        $sql_lists = sisplet_query("SELECT id FROM srv_userbase_respondents_lists");
        $numRows = mysqli_num_rows($sql_lists);
        //		print_r(SurveyRespondents :: getInstance() ->getSurveyId());
        //		print_r(SurveyRespondents :: getInstance() ->getGlobalUserId());
        //		print_r(SurveyRespondents :: getInstance() ->getCurentProfileId());
        //		print_r(SurveyRespondents :: getInstance() ->getProfiles());
        if ($numRows == 0) {
            echo '<div id="div_error" class="red"><img src="icons/icons/error.png" alt="" vartical-align="middle" />' . ($errorMsg != null ? $errorMsg . '<br/>' : '') . 'Ni shranjenih list respondentov!</div>';
        } else {
            echo '<div class="respondents_list_holder">';
            echo '	<div id="respondents_list" class="select">';
            $sql_email_invitations_profiles = sisplet_query("SELECT id, name FROM srv_userbase_respondents_lists");
            while ($row_email_invitations_profiles = mysqli_fetch_assoc($sql_email_invitations_profiles)) {
                echo '<div class="option' . ($row_email_invitations_profiles['id'] == $curent_id ? ' active' : '') . '" value="' . $row_email_invitations_profiles['id'] . '">' . $row_email_invitations_profiles['name'] . '</div>';
            }
            echo '	</div>';
            echo '</div>';

            echo '<div id="respondents_list_values" >';
            $this->show_userbase_list_respondents($curent_id, $errorMsg);
            echo '</div>';
            echo '<div class="clr"></div>';
            echo '</fieldset>';
            echo '<script type="text/javascript">';
            echo '$(document).ready(function() {' .
                '  $("#respondents_list .option").click(function() {' .
                '  $("#respondents_list .option").each(function () {' .
                '    $(this).removeClass("active"); });' .
                '    $(this).addClass("active");' .
                '    change_respondent_list($(this).attr(\'value\'));' .
                '	});';
            echo '});';
            echo '</script>';

        }
        echo '</fieldset>';

    }

    /**
     * TODO ???
     *
     * @param mixed $id
     * @param mixed $errorMsg
     */
    function show_userbase_list_respondents($id = null, $errorMsg = null)
    {
        global $lang;
        if ($errorMsg != null) {
            echo '<div id="div_error" class="red"><img src="icons/icons/error.png" alt="" vartical-align="middle" />' . $errorMsg . '</div>';
        }
        // preberemo ime liste in sistemske spremenljivke
        $sqlLista = sisplet_query("SELECT name, variables FROM srv_userbase_respondents_lists WHERE id = '" . $id . "'");
        $rowLista = mysqli_fetch_assoc($sqlLista);

        // preberemo respondente
        $_respondenti = array();
        $sqlRespondenti = sisplet_query("SELECT line FROM srv_userbase_respondents WHERE list_id = '" . $id . "'");
        while ($row_respondenti = mysqli_fetch_assoc($sqlRespondenti)) {
            $_respondenti[] = $row_respondenti['line'];
        }
        if ($_respondenti)
            $respondenti = implode("<br/>", $_respondenti);

        echo '<div style="margin-bottom:10px;">';
        echo '<p> <span class="labelSpanWide" >' . $lang['srv_userbase_list_name'] . ':</span>';
        echo '<input type="text" disabled="disabled" value="' . $rowLista['name'] . '" style="width:225px;"></p>';
        echo '<p> <span class="labelSpanWide" >' . $lang['srv_userbase_variables'] . ':</span>';
        echo '<input type="text" id="email_invitation_value_subject" disabled="disabled" value="' . $rowLista['variables'] . '" style="width:225px;"></p>';
        echo '<p> <span class="labelSpanWide" >' . $lang['srv_userbase_respondents'] . ':</span>';
        echo '<div id="respondents_list_value_text">' . $respondenti . '</div></p>';
        echo '</div>';
        echo '<div class="clr"></div>';

        if ($id > 0) {
            echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="respondents_list_add(\'' . $id . '\'); return false;"><span><img src="icons/icons/book_previous.png" alt="" vartical-align="middle" />' . $lang['srv_userbase_add_list'] . '</span></a></div></span>';
            $confirmDelete = $lang['srv_userbase_confirm_delete_list'] . $rowLista['name'] . "?";
            echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_blue" href="#" onclick="show_edit_email_respondents(\'' . $id . '\'); return false;"><span><img src="icons/icons/book_edit.png" alt="" vartical-align="middle" />' . $lang['srv_userbase_edit_list'] . '</span></a></div></span>';
            echo '  <span class="floatLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_red" href="#" onclick="delete_respondents_list(\'' . $id . '\',\'' . $confirmDelete . '\'); return false;"><span><img src="icons/icons/book_delete.png" alt="" vartical-align="middle" />' . $lang['srv_userbase_delete_list'] . '</span></a></div></span>';
            echo '	<div class="clr"></div>';
        }

    }

    /**
     * TODO ???
     *
     * @param mixed $mailto_radio
     * @param mixed $mailto_status
     */
    function show_mailto_users($mailto_radio, $mailto_status = null)
    {
        global $lang;

        $arrayMailtoSqlString = $this->getMailtoSqlString($mailto_radio, $mailto_status);
        $errorMsg = $arrayMailtoSqlString['errorMsg'];
        $sqlString = $arrayMailtoSqlString['sqlString'];

        echo '	<fieldset style="padding:10px;margin-left:10px; border:1px solid gray;"><legend>' . $lang['srv_mail_to_user_list'] . ':</legend>';
        if ($errorMsg == null) {
            $sqlUsers = sisplet_query($sqlString);
            if (mysqli_num_rows($sqlUsers) > 0) {
                while ($rowUsers = mysqli_fetch_array($sqlUsers)) {

                    $sqlUser = sisplet_query("SELECT d.text FROM srv_data_text".$this->db_table." d, srv_spremenljivka s , srv_grupa g " .
                        " WHERE d.spr_id=s.id AND d.usr_id='" . $rowUsers['id'] . "' AND " .
                        " s.variable = 'email' AND g.ank_id='" . $this->anketa . "' AND s.gru_id=g.id
					");
                    if (!$sqlUser) echo mysqli_error($GLOBALS['connect_db']);
                    $rowUser = mysqli_fetch_array($sqlUser);

                    if ($rowUser['text'] != "" && $rowUser['text'] != NULL) {
                        echo '<p>' . $rowUser['text'] . ' (status: ' . $rowUsers['status'] . ' - ' . $lang['srv_userstatus_' . $rowUsers['status']] . ')</p>';
                    } else {
                        echo '<p><span class="gray">' . $lang['srv_respondent_email_missing'] . '</span> (status: ' . $rowUsers['status'] . ' - ' . $lang['srv_userstatus_' . $rowUsers['status']] . ')</p>';
                    }
                }
            } else {
                $errorMsg = $lang['srv_mail_to_user_no_data'];
            }
        }

        if ($errorMsg != null) {
            echo '<div id="div_error" class="red"><img src="icons/icons/error.png" alt="" vartical-align="middle" />' . $errorMsg . '</div>';
        }
        echo '</fieldset>';
    }

    /** Odpre okno za predogled poslanega e-maila obveščanja z pripadajočim seznamom uporabnikov
     *
     * @param mixed $mailto_radio
     * @param mixed $mailto_status
     */
    function preview_mailto_email($mailto_radio, $mailto_status)
    {
        global $site_url, $lang;

        // preberemo vsebino sporočila
        $sql_userbase = sisplet_query("SELECT * FROM srv_userbase_setting WHERE ank_id = '$this->anketa'");
        if (mysqli_num_rows($sql_userbase) > 0) {
            // anketa že ima nastavljen text
            $row_userbase = mysqli_fetch_assoc($sql_userbase);
        } else {
            // anketa še nima nastavljenega teksta, preberemo privzetega (id=1) iz tabele srv_userbase_invitations
            $sql_userbase_invitations = sisplet_query("SELECT * FROM srv_userbase_invitations WHERE id = 1");
            $row_userbase = mysqli_fetch_assoc($sql_userbase_invitations);
        }

        // poiščemo sistemske spremenljivke iz vsebine
        preg_match_all("/#(.*?)#/s", $row_userbase['text'], $sisVars);
        $sisVars = $sisVars[1];

        // Poiščemo še sistemske spremenljivke iz ankete
        $sqlSistemske = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='" . $this->anketa . "' ORDER BY g.vrstni_red, s.vrstni_red");
        if (mysqli_num_rows($sqlSistemske) > 0) {
            while ($rowSistemske = mysqli_fetch_assoc($sqlSistemske)) {
                if (!isset($sisVars[strtoupper($rowSistemske['variable'])]))
                    $sisVars[] = strtoupper($rowSistemske['variable']);
            }
        }

        // preberemo prejemnike
        $arrayMailtoSqlString = $this->getMailtoSqlString($mailto_radio, $mailto_status);
        $errorMsg = $arrayMailtoSqlString['errorMsg'];
        $sqlString = $arrayMailtoSqlString['sqlString'];


        $usrArray = array();
        if ($errorMsg == null) {
            $sqlUsers = sisplet_query($sqlString);
            if (mysqli_num_rows($sqlUsers) > 0) {

                //while ($rowUsers = mysqli_fetch_array($sqlUsers)) {
                // naredimo samo za prvega userja
                while ($rowUsers = mysqli_fetch_array($sqlUsers)) {
                    # ali imamo ustrezne sistemske spremenljivke (predvsem e-mail)
                    $valid_user = false;
                    $tmpUser = array();
                    $tmpUser['cookie'] = $rowUsers['cookie'];
                    $tmpUser['pass'] = $rowUsers['pass'];
                    $tmpUser ['status'] = $rowUsers['status'];
                    $tmpUser ['label'] = $lang['srv_userstatus_' . $rowUsers['status']];
                    // dodamo sistemske spremenljivke in poiščemo njihove vrednosti
                    foreach ($sisVars as $sysVar) {

                        $sqlUser = sisplet_query("SELECT d.text FROM srv_data_text".$this->db_table." d, srv_spremenljivka s , srv_grupa g
						WHERE d.spr_id=s.id AND d.usr_id='" . $rowUsers['id'] . "' AND
						s.variable = '" . strtolower($sysVar) . "' AND g.ank_id='" . $this->anketa . "' AND s.sistem = 1 AND s.gru_id=g.id
						");
                        if (!$sqlUser)
                            echo mysqli_error($GLOBALS['connect_db']);
                        $rowUser = mysqli_fetch_assoc($sqlUser);
                        if ($rowUser['text'] != null && $rowUser['text'] != '') {
                            $tmpUser[strtolower($sysVar)] = $rowUser['text'];
                        }

                        # če mamo email in je vnešen je uporabnik veljaven
                        if (strtolower($sysVar) == 'email' && $rowUser['text'] != null && $rowUser['text'] != '') {
                            $valid_user = true;
                        }
                    }

                    if ($valid_user) {
                        $usrArray[$rowUsers['id']] = $tmpUser;
                    }
                }
            } else {
                $errorMsg = "Ni uporabnikov ki ustrezajo izbranim pogojem!";
            }
        }

        $frstUser = current($usrArray);
        // cookie, email poberemo od prvega uporabnika

        $url = SurveyInfo::getSurveyLink() . '?code=' . $frstUser['pass'] . '';
        $unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $this->anketa . '&code=' . $frstUser['pass'] . '';

        // zamenjamo sistemske vrednosti
        $content = $row_userbase['text'];
        // za staro verzijo
        $content = str_replace('[URL]', '#URL#', $content);
        $content = str_replace('[CODE]', '#CODE#', $content);
        $content = str_replace(array(
            '#URL#',
            '#CODE#'
        ), array(
            '<span style="color:blue;">' . $url . '</span>',
            $frstUser['pass']
        ), $content);
        $content = str_replace('#UNSUBSCRIBE#', '<a href="' . $unsubscribe . '">' . $lang['user_bye_hl'] . '</a>', $content);

        // poiščemo prestale variable katere je potrebno zamenjati
        preg_match_all("/#(.*?)#/s", $content, $toReplace);
        foreach ($toReplace[0] as $key => $seed) {
            $content = str_replace($toReplace[0][$key], $frstUser[strtolower($toReplace[1][$key])], $content);
        }

        $subject = $row_userbase['subject'];

        // izpišemo vsebino
        echo '<div style="border:1px solid #990000; background:#FFF; padding:10px;">';

        echo '<div style="float:right; width:200px; height:300px;">';
        echo 'Prejemniki:';
        echo '<div style="width:100%; height:300px; border: 1px solid gray; overflow: auto; ">';
        $brdr_top = "";
        foreach ($usrArray as $user) {
            echo '<div style="padding:1px; height:15px; border-bottom:1px solid silver;">' . $user['email'] . '</div>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div style="float:left; width:480px; height:16px;">';
        echo 'Naslov:';
        echo '</div>';
        echo '<div style="float:left; width:480px; border: 1px solid blue; overflow: visible; height: 16px; padding: 3px 2px;">';
        echo $subject;
        echo '</div>';

        echo '<div style="float:left; width:480px; height:16px; margin-top:10px;">';
        echo 'Vsebina:';
        echo '</div>';
        echo '<div style="float:left; width:480px; height: 244px; border: 1px solid blue; overflow: auto; padding: 2px">';
        echo $content;
        echo '</div>';

        //text samo pri previewju v formi (hitro posiljanje mailov)
        if ($_GET['a'] == 'form_send_email') {
            echo '<div style="float:left; width:480px; margin-top:10px; color: red;">';
            echo 'S potrditvijo boste zgornje vabilo poslali. Če bi želeli spreminjati nagovor, pojdite v napredne opcije.';
            echo '</div>';
        }

        echo '<div class="clr"></div>';
        echo '<div style="padding-top:10px;">';
        echo '  <span class="floatLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="preview_mailto_email_cancle(); return false;"><span>Prekliči</span></a></div></span>';
        echo '  <span class="floatLeft spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="preview_mailto_email_submit(); return false;"><span>Pošlji e-maile</span></a></div></span>';

        echo '<div class="clr"></div>';
        echo '</div>';

        echo '<div>';
    }

    /**
     * TODO ???
     *
     * @param mixed $mailto_radio
     * @param mixed $mailto_status
     * @return mixed
     */
    function getMailtoSqlString($mailto_radio, $mailto_status = null)
    {

        $sqlString = null;
        $errorMsg = null;

        //v odvisnosti od statusa polovimo emaile in jih zlistamo na levi strani
        if ($mailto_radio == 'all') {
            $sqlString = "SELECT id, last_status as status, cookie, pass FROM srv_user WHERE ank_id = '" . $this->anketa . "' AND unsubscribed='0'";
        } elseif ($mailto_radio == 'norsp') {
            $sqlString = "SELECT st.status, usr_tbl.* FROM srv_userstatus AS st LEFT JOIN ( SELECT max(s.datetime) as statusdatetime, u.* FROM srv_user as u" .
                " LEFT JOIN srv_userstatus AS s ON u.id = s.usr_id AND u.unsubscribed='0' WHERE u.ank_id = '" . $this->anketa . "'  AND recnum = '0' GROUP BY s.usr_id) AS usr_tbl ON st.usr_id = usr_tbl.id " .
                " WHERE usr_tbl.statusdatetime = st.datetime ORDER BY st.status";
        } elseif ($mailto_radio == 'rsp') {
            $sqlString = "SELECT st.status, usr_tbl.* FROM srv_userstatus AS st LEFT JOIN ( SELECT max(s.datetime) as statusdatetime, u.* FROM srv_user as u" .
                " LEFT JOIN srv_userstatus AS s ON u.id = s.usr_id AND u.unsubscribed='0' WHERE u.ank_id = '" . $this->anketa . "'  AND recnum > '0' GROUP BY s.usr_id) AS usr_tbl ON st.usr_id = usr_tbl.id " .
                " WHERE usr_tbl.statusdatetime = st.datetime ORDER BY st.status";
        } elseif ($mailto_radio == 'status') {
            if (!isset ($mailto_status) || $mailto_status == null || $mailto_status == "") {
                $errorMsg = "Status ni izbran!";
            } else {
                // nardimo string statusov
                $sqlString = "SELECT id, last_status as status, cookie, pass FROM srv_user WHERE ank_id = '" . $this->anketa . "' AND unsubscribed='0' AND last_status IN (" . $mailto_status . ") ORDER BY last_status";
            }
        } else {
            $errorMsg = "Napaka!";
        }
        return array(
            'sqlString' => $sqlString,
            'errorMsg' => $errorMsg
        );
    }

    /** Preveri ali uporabnik ustreza minimalni zahtevi statusa
     *
     * @param $minimum_role_request minimalna zahteva (lahko podamo kot array posamezno)
     * @return true/false
     */
    function user_role_cehck($minimum_role_request = U_ROLE_ADMIN)
    {
        global $admin_type;
        if (is_array($minimum_role_request) && count($minimum_role_request) > 0) { // ce podamo kot array preverimo za vsak zapis posebej
            foreach ($minimum_role_request as $role) {
                if ($admin_type == $role)
                    return true;
            }
        } else {
            if ($admin_type <= $minimum_role_request)
                return true;
        }
        return false;
    }

    var $getSurvey_type = null;

    function getSurvey_type($sid)
    {
        if ($this->getSurvey_type != null)
            return $this->getSurvey_type;

        // polovimo tip ankete
        SurveyInfo::getInstance()->SurveyInit($sid);
        $this->getSurvey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");
        return $this->getSurvey_type;
    }

    /**
     * prikaze infobox
     *
     */
    function displayInfoBox()
    {
        // klicemo iz SurveyInfo
        SurveyInfo::getInstance()->SurveyInit($this->anketa);
        SurveyInfo::getInstance()->DisplayInfoBox();
    }

    /**
     * TODO ????
     *
     * @param mixed $grupa
     * @param mixed $editmode
     */
    function showEditPageDiv($grupa, $editmode = false){
        global $lang;

        if (!$editmode && SurveyInfo::getInstance()->checkSurveyModule('uporabnost')) {
            SurveySetting::getInstance()->Init($this->anketa);
            $link = SurveySetting::getInstance()->getSurveyMiscSetting('uporabnost_link_' . $grupa);
            if (strlen($link) > 7)
                echo ', Link: ' . $link;
        }

        echo '<span id="page_edit_' . $grupa . '" class="page_edit" editmode="' . $editmode . '">';
        if ($editmode) {

            //polovimo ime grupe
            $sql = sisplet_query("SELECT id, naslov FROM srv_grupa WHERE id = '$grupa'");
            $row = mysqli_fetch_array($sql);

            echo '          <input type="text" id="naslov_' . $grupa . '" name="naslov" value="' . $row['naslov'] . '" onblur="save_edit_grupa(\'' . $row['id'] . '\', $(this).val());" />';

            if (SurveyInfo::getInstance()->checkSurveyModule('uporabnost')) {
                SurveySetting::getInstance()->Init($this->anketa);
                $link = SurveySetting::getInstance()->getSurveyMiscSetting('uporabnost_link_' . $grupa);
                if ($link == '') $link = 'http://';
                echo ' Link:          <input style="width:300px" type="text" id="uporabnost_link_' . $grupa . '" name="uporabnost_link" value="' . $link . '" onblur="save_edit_uporabnost_link(\'' . $row['id'] . '\', $(this).val());" />';
            }
        }
        echo '<a href="#" title="' . $lang['srv_editirajgrupo'] . '" onclick="editmode_grupa(\'' . $grupa . '\',1); return false;"><img src="img_' . $this->skin . '/edit_gray.png" alt="' . $lang['srv_editirajgrupo'] . '" /></a>';
        echo '<a href="#" title="' . $lang['srv_brisigrupo'] . '" onclick="brisi_grupo(\'' . $grupa . '\', \'' . $lang['srv_brisigrupoconfirm'] . '\'); return false;"><img src="img_' . $this->skin . '/delete_gray.png" alt="' . $lang['srv_brisigrupo'] . '" /></a>';
        echo '</span>';

    }

    /**
     * TODO ???
     *
     * @param mixed $anketa
     */
    private static $checkAnketaExist = array();

    function checkAnketaExist($anketa = 0)
    {
        if ($anketa == 0)
            $anketa = $this->anketa;

        if (isset(self::$checkAnketaExist[$anketa]))
            return self::$checkAnketaExist[$anketa];

        $sqlString = "SELECT count(*) FROM srv_anketa WHERE active >= '0' AND id = '" . $anketa . "'";
        $sqlQuery = sisplet_query($sqlString);
        $sqlRow = mysqli_fetch_array($sqlQuery);
        self::$checkAnketaExist[$anketa] = ($sqlRow[0] > 0) ? true : false;
        return self::$checkAnketaExist[$anketa];
    }


    /**
     * TODO ???
     *
     * @param mixed $needed
     */
    function alert_add_necessary_sysvar($needed = array(), $updateUserBase = false)
    {
        global $lang;

        if (is_array($needed) && count($needed) > 0) {
            $needed = $needed;
        } else {
            $needed = array('email', 'ime');
        }
        SurveyRespondents::Init($this->anketa);
        SurveyRespondents::checkSystemVariables($needed, $updateUserBase);

        return $needed;
    }

    /**
     * TODO ???
     *
     */
    function alert_change_user_from_cms()
    {
        global $lang;
        // nastavimo respondent iz cms
        echo $lang['srv_alert_respondent_cms'];
        $mysqlUpdate = sisplet_query("UPDATE srv_anketa SET user_from_cms = '1' WHERE id='" . $this->anketa . "'");
        // vsilimo refresh podatkov
        SurveyInfo:: getInstance()->resetSurveyData();

        if (!$mysqlUpdate) {

            echo mysqli_error($GLOBALS['connect_db']);
        } else {
            echo $lang['srv_alert_respondent_cms_note_ok'];
            echo '<img src="icons/icons/accept.png" alt="" vartical-align="middle" />';
        }
    }

    /**
     * TODO ???
     *
     */
    function anketa_aktivacija_note()
    {
        global $lang;
        $row = SurveyInfo::getInstance()->getSurveyRow();
        if ($row['active'] == 0) {
            echo $lang['srv_url_survey_not_active'];
            echo '	<span id="vabila_anketa_aktivacija" class="link_no_decoration">';
            echo '		<a href="#" onclick="anketa_active(\'' . $this->anketa . '\',\'' . $row['active'] . '\'); return false;" title="' . $lang['srv_anketa_noactive'] . '">';
            echo '      <span class="faicon star_off"></span>';
            echo '      <span >' . $lang['srv_anketa_setActive'] . '</span>';
            echo '      </a>';
            echo '	</span>';
        } else {
            echo $lang['srv_url_intro_active'];
            echo '	<span id="vabila_anketa_aktivacija" class="link_no_decoration">';
            echo '		<a href="#" onclick="anketa_active(\'' . $this->anketa . '\',\'' . $row['active'] . '\'); return false;" title="' . $lang['srv_anketa_active'] . '">';
            echo '      <span class="faicon star_on"></span>';
            echo '      <span >' . $lang['srv_anketa_setNoActive'] . '</span>';
            echo '      </a>';
            echo '	</span>';
        }
    }


    /** prikaze div da so nastavitve shranjene in ga nato skrije
     *
     */
    function displaySuccessSave()
    {
        global $lang;
        echo $lang['srv_success_save'];
    }


    /** pravilno redirekta admin url ankete
     *  če je anketa aktivna gre na dashboard
     *  če anketa ni aktivna gre na:
     *   - v primeru da še ni bila kativirana gre na urejanje
     *   - v primeru da je bila aktivirana (je pretekla) gre na dashboard
     */
    function redirectLink()
    {
        global $site_url;

        # ugotovimo ali je uporabnik telefonski anketar
        if ($this->isAnketar) {
            #če je anketar lahko samo kliče
            header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_TELEPHONE . '&m=start_call');
            exit();
        }

        // v kolikor je aktivna hierarhija preusmerimo uporabnika na status od hierarhije
        if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
            $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);
            // v kolikor je uporabnik admin, ga preusmerimo na dostop za administratorje
            if($hierarhija_type < 5){
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_HIERARHIJA_SUPERADMIN . '&m='.M_HIERARHIJA_STATUS);
                exit();
            }

            // vse ostale uporabnike preusmerimo na običajni pogled hierarhije
            header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_HIERARHIJA. '&m='.M_HIERARHIJA_STATUS);
            exit();
        }

        # če nima dostopa do statusa ali urejanja je potreben redirekt kam drugam
        $d = new Dostop();
        if (!$d->checkDostopSub('edit') || !$d->checkDostopSub('dashboard')) {

            // Po prioriteti vrstni red strani kamor preusmerimo ce ima uporabnik dostop
            if ($d->checkDostopSub('edit')) {
                header('Location: index.php?anketa=' . $this->anketa);
                die();
            } elseif ($d->checkDostopSub('dashboard')) {
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_REPORTI);
                die();
            } elseif ($d->checkDostopSub('test')) {
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_TESTIRANJE);
                die();
            } elseif ($d->checkDostopSub('publish')) {
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_VABILA);
                die();
            } elseif ($d->checkDostopSub('data')) {
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_COLLECT_DATA);
                die();
            } elseif ($d->checkDostopSub('analyse')) {
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_ANALYSIS);
                die();
            } else {
                header('location: ' . $site_url . 'admin/survey/');
                die();
            }
        }

        # ugotovimo status ankete
        SurveyInfo::getInstance()->SurveyInit($this->anketa);
        if (SurveyInfo::getSurveyColumn('active') == 1) {
            # anketa je aktivna, gremo na dashboard
            header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_REPORTI);
            exit();
        } 
        else {
            # preverimo ali je bila anketa že aktivirana
            $activity = SurveyInfo:: getSurveyActivity();
            $_last_active = end($activity);
            if (isset($_last_active) && $_last_active != null) {
                # anketa je že bila aktivirana in je potekla gremo na dashboard
                header('Location: index.php?anketa=' . $this->anketa . '&a=' . A_REPORTI);
                exit();
            } else {
                # anketa še ni bila aktivirana gremo na urejanje
                header('Location: index.php?anketa=' . $this->anketa);
                exit();
            }
        }

        die();
    }

    function displayAktivnost()
    {
        global $lang, $site_url, $admin_type;

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        SurveyInfo::getInstance()->SurveyInit($this->anketa);
        $row = SurveyInfo::getInstance()->getSurveyRow();
        SurveySetting::getInstance()->Init($this->anketa);

        if ($row['active'] == 1) // preverimo če je čas aktivacije potekel potem anketo deaktiviramo
            $row['active'] = $this->checkSurveyActive();

        $link = SurveyInfo::getSurveyLink();

        $activity = SurveyInfo:: getSurveyActivity();
        $_last_active = end($activity);

        $preview_disableif = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disableif');
        $preview_disablealert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disablealert');
        $preview_displayifs = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayifs');
        $preview_displayvariables = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayvariables');
        $preview_hidecomment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hidecomment');
        $preview_options = '' . ($preview_disableif == 1 ? '&disableif=1' : '') . ($preview_disablealert == 1 ? '&disablealert=1' : '') . ($preview_displayifs == 1 ? '&displayifs=1' : '') . ($preview_displayvariables == 1 ? '&displayvariables=1' : '') . ($preview_hidecomment == 1 ? '&hidecomment=1' : '') . '';


        // Predogled in testiranje (ikona monitor)
        echo '<span class="tooltip borderLeft monitor" style="padding-left:7px;">';
        echo ' <a href="' . $link . '&preview=on' . $preview_options . '" target="_blank"><span class="faicon monitor" style="margin-right:7px;"></span></a> ';
        echo '<span id="tooltip_preview_content" class="expanded-tooltip bottom light">';

        echo '<b>' . $lang['srv_monitor_toolbox_title'] . '</b>';

        echo '<p>';

        // Ce imamo izklopljene mobilne prilagoditve ne pustimo preview-ja na mobile, ker itak ne prikaze scalano
        $mobile_friendly = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_friendly');
        if($mobile_friendly != '0'){ 
            echo '<b><a href="' . $link . '&preview=on' . $preview_options . '" target="_blank">' . $lang['srv_preview'] . ' PC</a>';
            echo ' &nbsp;(<a href="' . $link . '&preview=on&mobile=1' . $preview_options . '" target="_blank">' . $lang['srv_preview_mobile'] . '</a>, ';
            echo '<a href="' . $link . '&preview=on&mobile=2' . $preview_options . '" target="_blank">' . $lang['srv_preview_tablet'] . '</a>)</b>';
        }
        else{
            echo '<b><a href="' . $link . '&preview=on' . $preview_options . '" target="_blank">' . $lang['srv_preview'] . ' PC</a></b>';
        }

        echo '<br />(' . $lang['srv_monitor_toolbox_preview'] . ')';

        echo '</p>';

        // V formi in glasovanju nimamo testnih vnosov
        if ($this->survey_type != 0 && $this->survey_type != 1) {
            echo '<a href="' . $link . '&preview=on&testdata=on' . $preview_options . '" title="" target="_blank">';
            echo '<b>' . $lang['srv_survey_testdata'] . '</b>';
            echo '</a>';

            echo '<br />(' . $lang['srv_monitor_toolbox_test'] . ')';
        }
        echo '<br /><br />';

        echo '<span class="arrow"></span>';

        echo '</span>';    // expanded-tooltip bottom
        echo '</span>'; // request-help

        # url ankete
        echo '<span id="anketa_url" class="anketa_url borderLeft">';
        if ($row['active'] == 1 && !SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
            echo '<a href="' . $link . '" title="' . $lang['srv_urlankete'] . '" target="_blank">';
            //echo '<span class="sprites anketa_link"></span>&nbsp;
            echo $link;
            echo '</a>';

        } elseif (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
            echo '<a href="'.$site_url.'sa" target="_blank">'.$site_url.'sa</a>';
        } else {
            echo $link;
        }
        echo '</span>&nbsp;&nbsp;';

        $d = new Dostop();
        if ($d->checkDostopAktiven()) {

            echo '<span class="borderLeft">';

            if (SurveyInfo::getSurveyColumn('active') == 1) {
                # anketa je aktivna
                # V kolikor gre za hierarhijo in uporabnik ni administrator hierarhije
                if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')){
                    if ($hierarhija_type == 1) {
                        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_AKTIVACIJA . '" class="srv_ico" title="' . $lang['srv_anketa_noactive'] . '">';
                    } else{
                        echo '<a href="#" class="srv_ico" title="' . $lang['srv_anketa_active'] . '" style="cursor:text !important;">';
                    }
                }else {
                    echo '<a href="#" class="srv_ico" onclick="anketa_active(\'' . $this->anketa . '\',\'' . $row['active'] . '\'); return false;" title="' . $lang['srv_anketa_active'] . '">';
                }

                echo '<div id="srv_active" class="switch_anketa anketa_on"><span class="switch_anketa_content">ON</span></div>';
                
                echo '</a>';
            } else {
                $anketa_active = "anketa_active('" . $this->anketa . "','" . $row['active'] . "'); ";

                //Preden anketo aktiviramo preverimo, če gre tudi za izgradnjo hierarhije in če anketa še ni bila aktivirana
                if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')){
                    if ($hierarhija_type == 1) {
                        echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_AKTIVACIJA . '" class="srv_ico" title="' . $lang['srv_anketa_noactive'] . '">';
                    } else{
                        echo '<a href="#" class="srv_ico" title="' . $lang['srv_anketa_noactive'] . '">';
                    }
                }else {
                    echo '<a href="#" class="srv_ico" onclick="' . $anketa_active . ' return false;" title="' . $lang['srv_anketa_noactive'] . '">';
                }

                if ((int)$_last_active > 0) {
                    # anketa je zaključena
					echo '<div id="srv_inactive" class="switch_anketa anketa_off"><span class="switch_anketa_content">OFF</span></div>';
                } else {
                    # anketa je neaktivna
					echo '<div id="srv_inactive" class="switch_anketa anketa_off"><span class="switch_anketa_content">OFF</span></div>';
                }

                echo '</a>';
            }

            // Ce ima uporabnik prepreceno moznost odklepanja ankete, anketo ima vedno zaklenjeno če je vklopljena hierarhija
            $prevent_unlock = (SurveyInfo::getSurveyModules('hierarhija') == 2 || $d->checkDostopSub('lock') && $row['locked'] == 1 && ($admin_type != 0 && $admin_type != 1)) ? 1 : 0;
            if ($prevent_unlock == 1) {
                echo '<input type="hidden" name="prevent_unlock" id="prevent_unlock" value="1">';
                echo '<a class="anketa_img_nav" title="' . $lang['srv_anketa_locked_close'] . '">';
                echo '<span class="faicon lock_close"></span>';
                echo '</a>';
            } else {
                # zaklepanje
                //echo '<a class="anketa_img_nav" href="javascript:anketa_lock(\''.$this->anketa.'\', \''.($row['locked']==0?'1':'0').'\');" title="'.$lang['srv_anketa_locked_'.$row['locked']].'"><img style="margin-left: 5px;" src="img_0/lock'.($row['locked']==0?'_open':'').'.png" /></a>';
                if ($hierarhija_type == 10) {
                    echo '<a href="#" class="anketa_img_nav" title="' . $lang['srv_anketa_locked_' . $row['locked']] . '" style="cursor:text !important;">';
                } else {
                    echo '<a class="anketa_img_nav" href="javascript:anketa_lock(\'' . $this->anketa . '\', \'' . ($row['locked'] == 0 ? '1' : '0') . '\', \''.$row['mobile_created'].'\');" title="' . $lang['srv_anketa_locked_' . $row['locked']] . '">';
                }
                echo '<span class="faicon lock' . ($row['locked'] == 0 ? '_open' : '_close') . '"></span>';
                echo '</a>';
            }

            echo '</span>';
        }


        # Objava na FB, twitter, .... share pac
        if ($row['active'] == 1 && false) {
            # NE PRIKAZUJEMO IKONIC
            $sqlu = sisplet_query("SELECT email FROM users WHERE id='" . $this->uid() . "'");
            $rowu = mysqli_fetch_array($sqlu);
            if ($rowu['email'] == '') {
                $sqlm = sisplet_query("SELECT value FROM misc WHERE what = 'AlertFrom'");
                $rowm = mysqli_fetch_array($sqlm);
                $rowu['email'] = $rowm['value'];
            }
            ?><span style="display: inline-block; margin-left:25px;">
            <span class="anketa_img_icons" style="margin-top: 3px;">
			<a href="mailto:<?= $rowu['email'] ?>?body=<?= urlencode(SurveyInfo::getSurveyLink()) ?>?subject=<?= $row['naslov'] ?>"
               target="_blank" title="<?= $lang['srv_add_to_mail'] ?>"><span class="sprites email"></span></a>
			</span>
			<span class="anketa_img_icons">
			<a href="http://www.facebook.com/share.php?u=<?= urlencode(SurveyInfo::getSurveyLink()) ?>" target="_blank"
               title="<?= $lang['srv_add_to_fb'] ?>"><span class="sprites facebook"></span></a>
			</span>
			<span class="anketa_img_icons">
			<a href="http://twitter.com/share?url=<?= urlencode(SurveyInfo::getSurveyLink()) ?>?text=<?= $row['naslov'] ?>"
               target="_blank" title="<?= $lang['srv_add_to_tw'] ?>"><span class="sprites twitter"></span></a>
			</span>
            </span>
            <?php
        }
    }

    function surveyAutoActivate()
    {
        global $global_user_id;
        # preverimo ali je anketa že bila aktivirana
        $str = "SELECT * FROM srv_activity WHERE sid =" . $this->anketa;
        $qry = sisplet_query($str);
        if (mysqli_num_rows($qry) == 0) {
            # če anketa še ni bila kativirana jo aktiviramo za tri mesece
            $row = SurveyInfo::getInstance()->getSurveyRow();
            #(3) čim klikne na OBJAVA naj se zadeva tudi ze kar  aktivira (kot bi kliknil na AKTIVIRAJ (vendar brez popupa in dajte daafulut trajanje na 3 miesece.
            if ($row['active'] != 1) {
                $uString = "UPDATE srv_anketa SET active = '1', starts=NOW(), expire = date_add(NOW(), INTERVAL 3 MONTH) WHERE id='" . $this->anketa . "'";
                $s = sisplet_query($uString);
                if (!$s) echo mysqli_error($GLOBALS['connect_db']);

                #updejtamo še stv_activity
                $uString = "INSERT INTO srv_activity (sid, starts, expire, uid) VALUES ('" . $this->anketa . "', NOW(), date_add(NOW(), INTERVAL 3 MONTH), '" . $global_user_id . "')";
                $s = sisplet_query($uString);
                if (!$s) echo mysqli_error($GLOBALS['connect_db']);

                SurveyInfo:: getInstance()->SurveyInit($this->anketa);
                # vsilimo refresh podatkov
                SurveyInfo:: getInstance()->resetSurveyData();
            }
        }
    }

}

?>
