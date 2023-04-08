<?php

/*
 *  Class, ki skrbi za mobile izris admin vmesnika
 *
 */



class MobileSurveyAdmin{


    var $surveyAdminClass;
    var $first_action;
    var $second_action;
    var $third_action;


	function __construct($surveyAdminClass){
		global $site_url;

        $this->surveyAdminClass = $surveyAdminClass;

        $navigationArray = CrossRoad::MainNavigation($this->surveyAdminClass->anketa, true);
        $this->first_action = $navigationArray['first_action'];
        $this->second_action = $navigationArray['second_action'];
        $this->third_action = $navigationArray['third_action'];

	}

    // Izris glave z menijem - znotraj ankete
    public function displayHeaderMobile(){

        echo '<div class="mobile_header '.($this->surveyAdminClass->anketa > 0 ? 'survey_edit' : 'survey_list').'">';

        // Ikona za meni
        $this->displayMenuIcon();  

        // Meni
        $this->displayMenu();       
        
        // Naslov ankete + slider za nastavitve
        if($this->surveyAdminClass->anketa > 0){

            // Naslov ankete na sredini
            $this->displaySurveyTitle();

            // Ikona za nastavitve
            $this->displaySurveySettingsIcon();

            // Div holder za nastavitve
            $this->displayMenuSurveySettings();
        }
        // Logo - enak kot na desktopu
        else{
            $this->displayLogo();
        }

        // Se inicializiramo zeynep jquery mobile menu in settings meni na desni
        echo '<script> mobile_init(); </script>';
        
        echo '</div>';
    }


    // Prikazemo mobile logo
    private function displayLogo(){
        global $lang;
        global $site_url;

        echo '<div class="mobile_logo">';

        $logo_class = ($lang['id'] != "1") ? ' class="english"' : '';
        $su = ($site_url == "https://www.1ka.si/" && $lang['id'] != "1") ? "https://www.1ka.si/d/en/" : $site_url;
		
        echo '<a href="' . $su . '" title="' . $lang['srv_1cs'] . '" id="enka_logo" ' . $logo_class . '></a>';

        echo '</div>';
    }

    private function displaySurveyTitle(){

        SurveyInfo::getInstance()->SurveyInit($this->surveyAdminClass->anketa);
        $row = SurveyInfo::getInstance()->getSurveyRow();

        echo '<div class="mobile_survey_title">'.$row['naslov'].'</div>';
    }

    private function displayMenuIcon(){

        echo '<div class="mobile_menu_icon mobile_menu_open">';
        echo '  <span class="faicon bars"></span>';
        echo '</div>';

        echo '<div class="mobile_menu_icon mobile_menu_close">';
        echo '  <span>✕</span>';
        echo '</div>';
    }

    private function displaySurveySettingsIcon(){

        echo '<div class="mobile_settings_icon mobile_settings_open">';
        echo '  <span class="faicon wheel_32"></span>';
        echo '</div>';
        
        echo '<div class="mobile_settings_icon mobile_settings_close">';
        echo '  <span>✕</span>';
        echo '</div>';
    }


    // Izris menija
    private function displayMenu(){

        echo '<div class="mobile_menu first" data-menu-name="first">';

        // Izris uporabniških podatkov v dropdownu
        $this->displayMenuUser();

        // Meni znotraj ankete
        if($this->surveyAdminClass->anketa > 0){

            // Izris glavne navigacije v dropdownu
            $this->displayMenuSurveyNavigation();
        }
        // Meni v mojih anketah
        else{
            $this->displayMenuMySurveysNavigation();
        }

        echo '</div>';

    }

        
    // Izris menija za nastavitve v urejanju ankete
    private function displayMenuSurveySettings(){
        global $lang;
        global $admin_type;

        echo '<div class="mobile_settings">';

        echo '<div class="mobile_settings_content">';

        $row = SurveyInfo::getInstance()->getSurveyRow();

        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);


        // prikaz gumbov za vklop in odklepanje ankete
        $d = new Dostop();
        if ($d->checkDostopAktiven()) {

            # anketa je aktivna
            if (SurveyInfo::getSurveyColumn('active') == 1) {
                
                # V kolikor gre za hierarhijo in uporabnik ni administrator hierarhije
                if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')){
                    if ($hierarhija_type == 1) {
                        echo '<a href="index.php?anketa=' . $this->surveyAdminClass->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_AKTIVACIJA . '" class="srv_ico" title="' . $lang['srv_anketa_noactive'] . '">';
                    } 
                    else{
                        echo '<a href="#" class="srv_ico" title="' . $lang['srv_anketa_active'] . '" style="cursor:text !important;">';
                    }
                }
                else {
                    echo '<a href="#" class="srv_ico" onclick="anketa_active(\'' . $this->surveyAdminClass->anketa . '\',\'' . $row['active'] . '\'); return false;" title="' . $lang['srv_anketa_active'] . '">';
                }

                echo '  <div class="setting_icon"><div id="srv_active" class="switch_anketa anketa_on"><span class="switch_anketa_content">ON</span></div></div>';
                echo '  <div class="setting_text">'.$lang['srv_anketa_active'].'</div>';
                
                echo '</a>';
            } 
            else {
                $anketa_active = " mobile_settings_close(function(){ anketa_active('" . $this->surveyAdminClass->anketa . "','" . $row['active'] . "'); }); ";

                // Preden anketo aktiviramo preverimo, če gre tudi za izgradnjo hierarhije in če anketa še ni bila aktivirana
                if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')){
                    if ($hierarhija_type == 1) {
                        echo '<a href="index.php?anketa=' . $this->surveyAdminClass->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_AKTIVACIJA . '" class="srv_ico" title="' . $lang['srv_anketa_noactive'] . '">';
                    } 
                    else{
                        echo '<a href="#" class="srv_ico" title="' . $lang['srv_anketa_noactive'] . '">';
                    }
                }
                else {
                    echo '<a href="#" class="srv_ico" onclick="' . $anketa_active . ' return false;" title="' . $lang['srv_anketa_noactive'] . '">';
                }
 
                echo '  <div class="setting_icon"><div id="srv_inactive" class="switch_anketa anketa_off"><span class="switch_anketa_content">OFF</span></div></div>';
                echo '  <div class="setting_text">'.$lang['srv_anketa_noactive'].'</div>';

                echo '</a>';
            }

            // Ce ima uporabnik prepreceno moznost odklepanja ankete, anketo ima vedno zaklenjeno če je vklopljena hierarhija
            $prevent_unlock = (SurveyInfo::getSurveyModules('hierarhija') == 2 || $d->checkDostopSub('lock') && $row['locked'] == 1 && ($admin_type != 0 && $admin_type != 1)) ? 1 : 0;
            if ($prevent_unlock == 1) {

                echo '<input type="hidden" name="prevent_unlock" id="prevent_unlock" value="1">';

                echo '<a class="anketa_img_nav" title="' . $lang['srv_anketa_locked_close'] . '">';
                echo '  <div class="setting_icon"><span class="faicon lock_close"></span></div>';
                echo '  <div class="setting_text">'.$lang['srv_anketa_locked_close'].'</div>';
                echo '</a>';
            } 
            else {
                # zaklepanje
                if ($hierarhija_type == 10) {
                    echo '<a href="#" class="anketa_img_nav" title="' . $lang['srv_anketa_locked_' . $row['locked']] . '" style="cursor:text !important;">';
                } 
                else {
                    echo '<a class="anketa_img_nav" href="javascript:anketa_lock(\'' . $this->surveyAdminClass->anketa . '\', \'' . ($row['locked'] == 0 ? '1' : '0') . '\', \''.$row['mobile_created'].'\');" title="' . $lang['srv_anketa_locked_' . $row['locked']] . '">';
                }
                echo '  <div class="setting_icon"><span class="faicon lock' . ($row['locked'] == 0 ? '_open' : '_close') . '"></span></div>';
                echo '  <div class="setting_text">'.$lang['srv_anketa_locked_' . $row['locked']].'</div>';
                echo '</a>';
            }


            // Izris akcij za anketo (kopiraj, brisi...) v dropdownu
            # kopiranje
            echo '  <a href="#" onclick="anketa_copy_top(\'' . $this->surveyAdminClass->anketa . '\'); return false;" title="'.$lang['srv_anketacopy'].'" class="srv_ico">';
            echo '      <div class="setting_icon bottom"><span class="faicon anketa_copy"></span></div>';
            echo '      <div class="setting_text">'.$lang['srv_anketacopy'].'</div>';
            echo '  </a>';

            # brisanje
            echo '  <a href="#" onclick="anketa_delete(\'' . $this->surveyAdminClass->anketa . '\', \'' . $lang['srv_anketadeleteconfirm'] . '\'); return false;" title="' . $lang['srv_anketadelete'] . '" class="srv_ico">';
            echo '      <div class="setting_icon bottom"><span class="faicon anketa_delete" title="'.$lang['srv_anketadelete'].'"></span></div>';
            echo '      <div class="setting_text">'.$lang['srv_anketadelete'].'</div>';
            echo '  </a>';
        }

        echo '</div>';

        echo '</div>';
    }

    // Izris uporabniških podatkov v dropdownu
    private function displayMenuUser(){
        global $lang, $global_user_id, $site_url;


        $sql = $this->surveyAdminClass->db_select_user($global_user_id);
        $row = mysqli_fetch_array($sql);

        $user_name = $row['name'] . ' ' . $row['surname'];
        $user_name = (strlen($user_name) > 25) ? substr($user_name, 0, 25) . '...' : $user_name;

        $user_email = '<br><span class="email">'.$row['email'].'</span>';


        echo '<div class="mobile_menu_user">';

        echo '<ul>';

        echo '  <li class="has-submenu">';
        echo '      <a href="#" data-submenu="submenu_user" title="'.$user_name.'"><span class="faicon arrow_back"></span>'.$user_name.$user_email.'</a>';
        echo '  </li>';

        // Podmeni
        echo '  <div id="submenu_user" class="submenu">';


        // Podmeni header
        echo '      <div class="submenu-header" data-submenu-close="submenu_user">';
        echo '          <a href="#"><span class="faicon arrow_back"></span></a>';
        echo '          <label>'.$user_name.'</label>';
        echo '  </div>';


        // Podmeni vsebina
        echo '      <div class="submenu_user_content">';
        
        echo '          <div><a href="'.$site_url.'admin/survey/index.php?a=nastavitve&m=global_user_myProfile"><span class="faicon user"></span>' . $lang['edit_data'] . '</a></div>';

        // Odjava na nov nacin preko frontend/api
        echo '          <div><form name="odjava" id="form_odjava" method="post" action="'.$site_url.'frontend/api/api.php?action=logout">';
        echo '              <span class="as_link" onClick="$(\'#form_odjava\').submit();"><span class="faicon logout"></span>' . $lang['logout'] . '</span>';
        echo '          </form></div>';

        echo '      </div>';


        echo '  </div>';

        echo '</ul>';
        
        echo '</div>';
    }

    // Izris glavne navigacije v mojih anketah
    private function displayMenuMySurveysNavigation(){
        global $lang, $admin_type;


        # naložimo razred z seznamom anket
        $SL = new SurveyList();
        $SLCount = $SL->countSurveys();
        $SLCountPhone = $SL->countPhoneSurveys();


        echo '<div class="mobile_menu_navigation">';

        echo '<ul>';


        // MOJE ANKETE
        $this->displayMenuItem($lang['srv_pregledovanje'], $url='index.php?a=pregledovanje', (!isset($_GET['a']) && !isset($_GET['anketa'])) || ($_GET['a'] == 'pregledovanje') ? 'active': '');


        // TELEFONSKA ANKETA
		if ($SLCountPhone > 0 && $admin_type != '0') {
            $this->displayMenuItem($lang['srv_telephone_surveys'], $url='index.php?a=phoneSurveys', ($_GET['a'] == 'phoneSurveys') ? 'active': '');
		}
		
		
        // AKTIVNOST
		if ($SLCount > 0 && $admin_type == 0) {

            $submenu = array(
                array(
                    'title' => $lang['srv_ankete'], 
                    'url'   => 'index.php?a=diagnostics',
                    'active' => ($_GET['a'] == 'diagnostics' && !isset ($_GET['t']) ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_weekly_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span_daily',
                    'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span_daily' ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_monthly_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span_monthly',
                    'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span_monthly' ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_yearly_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span_yearly',
                    'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span_yearly' ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_all_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span&uvoz=0&ustrezni=1&delnoustrezni=1&neustrezni=0',
                    'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'time_span' ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_metapodatki'], 
                    'url'   => 'index.php?a=diagnostics&t=paradata',
                    'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'paradata' ? 'active' : '')
                )
            );
    
            $this->displayMenuItemWithSubmenu($name='diagnostics', $lang['srv_diagnostics'], $submenu, ($_GET['a'] == 'diagnostics' && $_GET['t'] != 'uporabniki') ? 'active' : '');
		}
		

		// UPORABNIKI
		if ($admin_type <= 1) {

            // Admini imajo pregled nad vsemi zavihki uporabnikov
            if ($admin_type == 0) {

                $submenu = array(
                    array(
                        'title' => $lang['n_users_list'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && !isset($_GET['m'])? 'active' : '')
                    ),
                    array(
                        'title' => $lang['n_users_list_all'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=all',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && $_GET['m'] == 'all' ? 'active' : '')

                    ),
                    array(
                        'title' => $lang['n_deleted_users'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=izbrisani',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && $_GET['m'] == 'izbrisani' ? 'active' : '')

                    ),
                    array(
                        'title' => $lang['n_unconfirmed_users'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=nepotrjeni',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && $_GET['m'] == 'nepotrjeni' ? 'active' : '')

                    ),
                    array(
                        'title' => $lang['n_unsigned_users'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=odjavljeni',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && $_GET['m'] == 'odjavljeni' ? 'active' : '')

                    ),
                    array(
                        'title' => $lang['srv_hierarchy_users_access'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=sa-modul',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && $_GET['m'] == 'sa-modul' ? 'active' : '')

                    ),
                );
            }
            
            // Manegerji imajo samo osnovni pregled svojih uporabnikov
            if ($admin_type == 1) {

                $submenu = array(
                    array(
                        'title' => $lang['n_users_list'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki',
                        'active' => ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' && !isset($_GET['m']) ? 'active' : '')

                    )
                );
            }

            $this->displayMenuItemWithSubmenu($name='uporabniki', $lang['hour_users'], $submenu, ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki' ? 'active' : ''));
		}


        // KNJIZNICA
        $submenu = array(
            array(
                'title' => $lang['srv_javna_knjiznica'], 
                'url'   => 'index.php?a=knjiznica',
                'active' => ($_GET['a'] == 'knjiznica' && !isset($_GET['t']) ? 'active' : '')

            ),
            array(
                'title' => $lang['srv_moja_knjiznica'], 
                'url'   => 'index.php?a=knjiznica&t=moje_ankete',
                'active' => ($_GET['a'] == 'knjiznica' && $_GET['t'] == 'moje_ankete' ? 'active' : '')

            )
        );

        $this->displayMenuItemWithSubmenu($name='knjiznica', $lang['srv_library'], $submenu, ($_GET['a'] == 'knjiznica' ? 'active' : ''));

		
		// NASTAVITVE
        if ($admin_type == '0') {
            $submenu = array(
                array(
                    'title' => $lang['srv_settingsSystem'], 
                    'url'   => 'index.php?a=nastavitve&m=system',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'system' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_testiranje_predvidenicas'], 
                    'url'   => 'index.php?a=nastavitve&m=predvidenicasi',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'predvidenicasi' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_collectData'], 
                    'url'   => 'index.php?a=nastavitve&m=collectData',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'collectData' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_nice_url'], 
                    'url'   => 'index.php?a=nastavitve&m=nice_links',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'nice_links' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_anketa_admin'], 
                    'url'   => 'index.php?a=nastavitve&m=anketa_admin',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'anketa_admin' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_anketa_deleted'], 
                    'url'   => 'index.php?a=nastavitve&m=anketa_deleted',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'anketa_deleted' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_data_deleted'], 
                    'url'   => 'index.php?a=nastavitve&m=data_deleted',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'data_deleted' ? 'active' : '')

                ),
                array(
                    'title' => $lang['srv_user_settings'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_settings',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'global_user_settings' ? 'active' : '')

                ),
                array(
                    'title' => $lang['edit_data'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_myProfile',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'global_user_myProfile' ? 'active' : '')

                ),
            );
        }
        else{
            $submenu = array(
                array(
                    'title' => $lang['srv_user_settings'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_settings',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'global_user_settings' ? 'active' : '')

                ),
                array(
                    'title' => $lang['edit_data'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_myProfile',
                'active' => ($_GET['a'] == 'nastavitve' && $_GET['m'] == 'global_user_myProfile' ? 'active' : '')

                ),
            );
        }

        $this->displayMenuItemWithSubmenu($name='nastavitve', $lang['settings'], $submenu, ($_GET['a'] == 'nastavitve' ? 'active' : ''));

		        
        // NAROCILA
        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){
            
            // placila - samo admini
            if ($admin_type == '0') {

                $submenu = array(
                    array(
                        'title' => $lang['srv_narocila_my'], 
                        'url'   => 'index.php?a=narocila',
                        'active' => ($_GET['a'] == 'narocila' && !isset($_GET['m']) ? 'active' : '')

                    ),
                    array(
                        'title' => $lang['srv_placila'], 
                        'url'   => 'index.php?a=narocila&m=placila',
                'active' => ($_GET['a'] == 'narocila' && $_GET['m'] == 'placila' ? 'active' : '')

                    )
                );
    
                $this->displayMenuItemWithSubmenu($name='narocila', $lang['srv_narocila'], $submenu, $_GET['a'] == 'narocila' ? 'active' : '');
            }
            // moja narocila
            else{
                $this->displayMenuItem($lang['srv_narocila'], $url='index.php?a=narocila', $_GET['a'] == 'narocila' && !isset($_GET['m']) ? 'active' : '');
            }
        }
		

		// GDPR
        $request_counter = GDPR::countUserUnfinishedRequests();

        $submenu = array(
            array(
                'title' => $lang['srv_gdpr_user_settings'], 
                'url'   => 'index.php?a=gdpr',
                'active' => ($_GET['a'] == 'gdpr' && !isset($_GET['m']) ? 'active' : '')

            ),
            array(
                'title' => $lang['srv_gdpr_survey_list'], 
                'url'   => 'index.php?a=gdpr&m=gdpr_survey_list',
                'active' => ($_GET['a'] == 'gdpr' && $_GET['m'] == 'gdpr_survey_list' ? 'active' : '')

            ),
            array(
                'title' => $lang['srv_gdpr_dpa'], 
                'url'   => 'index.php?a=gdpr&m=gdpr_dpa',
                'active' => ($_GET['a'] == 'gdpr' && $_GET['m'] == 'gdpr_dpa' ? 'active' : '')

            ),
            array(
                'title' => $lang['srv_gdpr_requests'].' ('.$request_counter.')', 
                'url'   => 'index.php?a=gdpr&m=gdpr_requests',
                'active' => ($_GET['a'] == 'gdpr' && $_GET['m'] == 'gdpr_requests' ? 'active' : '')

            )
        );

        // Vse zahteve za izbris - samo ADMINI
        if($admin_type == '0'){
            $submenu[] = array(
                'title' => $lang['srv_gdpr_requests_all'], 
                'url'   => 'index.php?a=gdpr&m=gdpr_requests_all',
                'active' => ($_GET['a'] == 'gdpr' && $_GET['m'] == 'gdpr_requests_all' ? 'active' : '')

            );
        }

        $this->displayMenuItemWithSubmenu($name='gdpr', 'GDPR', $submenu, ($_GET['a'] == 'gdpr' ? 'active' : ''));
   

        echo '</ul>';

        echo '</div>';
    }

    // Izris glavne navigacije v anketi
    private function displayMenuSurveyNavigation(){
        global $lang;


        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->surveyAdminClass->anketa]['type']) ? $_SESSION['hierarhija'][$this->surveyAdminClass->anketa]['type'] : null);

        $row = SurveyInfo::getInstance()->getSurveyRow();
        SurveyInfo:: getInstance()->SurveyInit($this->surveyAdminClass->anketa);

		$modules = SurveyInfo::getSurveyModules();
        $d = new Dostop();


        echo '<div class="mobile_menu_navigation first" data-menu-name="first">';

        echo '<ul>';


        // MOJE ANKETE
        echo '<li>';
        echo '<a class="left-1ka" href="index.php?a=pregledovanje" title="' . $lang['srv_pregledovanje'] . ' (' . strtolower($lang['srv_create_survey']) . ', ' . strtolower($lang['srv_library']) . ')">' . $lang['srv_pregledovanje'] . '</a>';
        echo '</li>';


        // STATUS
        if ($this->surveyAdminClass->skin == 0 && $this->surveyAdminClass->isAnketar == false && $d->checkDostopSub('dashboard')) {

            if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                $this->displayMenuItem($lang['srv_status_summary'], $url='index.php?anketa='.$this->anketa.'&a='.A_REPORTI);
            }
            else{
                $submenu = array(
                    array(
                        'title' => $lang['srv_status_summary'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_REPORTI,
                        'active' => ($_GET['a'] == A_REPORTI ? 'active' : '')
                    ),
                    array(
                        'title' => $lang['srv_metapodatki'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_PARA_GRAPH,
                        'active' => ($_GET['a'] == A_PARA_GRAPH ? 'active' : '')
                    )
                );

                $this->displayMenuItemWithSubmenu($name='dashboard', $lang['srv_navigation_status'], $submenu, ($this->first_action == NAVI_STATUS
                || $this->first_action == 'para_graph'
                || $this->first_action == 'nonresponse_graph'
                || $this->first_action == 'AAPOR'
                || $this->first_action == 'langStatistic'
                || $this->first_action == 'usable_resp'
                || $this->first_action == 'speeder_index'
                || $this->first_action == 'reminder_tracking'
                || $this->first_action == 'status_advanced') ? 'active' : '');
            }
        }


        // UREJANJE
        if ($d->checkDostopSub('edit') && $hierarhija_type < 5 && !$this->surveyAdminClass->isAnketar) {
            
            $submenu = array(
                array(
                    'title' => $lang['srv_editirajanketo2'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . ($this->surveyAdminClass->survey_type > 1 ? '&a=' . A_BRANCHING : ''),
                    'active' => ($this->second_action == NAVI_UREJANJE_BRANCHING ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_nastavitve_ankete'] , 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_SETTINGS,
                    'active' => ($this->second_action == NAVI_UREJANJE_ANKETA ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_themes'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_TEMA,
                    'active' => ($this->second_action == NAVI_UREJANJE_TEMA ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_archive'], 
                    'name' => 'edit_submenu', 
                    'submenu' => array(
                        array(
                            'title' => $lang['srv_archive_survey'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ARHIVI,
                            'active' => ($_GET['a'] == A_ARHIVI && $_GET['m'] == '' ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['srv_survey_archives_ie_title'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ARHIVI.'&m=survey',
                            'active' => ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'survey' ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['srv_survey_archives_ie_data_title'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ARHIVI.'&m=survey_data',
                            'active' => ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'survey_data' ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['srv_survey_archives_tracking_survey'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_TRACKING,
                            'active' => ($_GET['a'] == A_TRACKING && $_GET['m'] == '' ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['srv_survey_archives_tracking_data'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_TRACKING.'&m=tracking_data',
                            'active' => ($_GET['a'] == A_TRACKING && $_GET['m'] == 'tracking_data' ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['srv_survey_archives_tracking_append'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_TRACKING.'&appendMerge=1',
                            'active' => ($_GET['a'] == A_TRACKING && $_GET['appendMerge'] == '1' ? 'active' : '')
                        )
                    ),
                    'active' => ((($_GET['a'] == A_ARHIVI || $_GET['a'] == A_TRACKING) && $_GET['m'] != 'data') ? 'active' : '') 
                )
            );

            $this->displayMenuItemWithSubmenu($name='edit', $lang['srv_vprasalnik'], $submenu, ($this->first_action == NAVI_UREJANJE && $_GET['m'] != 'data' ? 'active' : ''));
        }


        # TESTIRANJE - ne prikazemo v glasovanju
        if ($this->surveyAdminClass->survey_type != 0 && $this->surveyAdminClass->survey_type != 1 && $d->checkDostopSub('test')) {     
            
            $submenu = array(
                array(
                    'title' => $lang['srv_testiranje_diagnostika'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_TESTIRANJE . '&m=' . M_TESTIRANJE_DIAGNOSTIKA,
                    'active' => ($this->second_action == M_TESTIRANJE_DIAGNOSTIKA ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_testiranje_komentarji'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_KOMENTARJI,
                    'active' => ($this->second_action == NAVI_TESTIRANJE_KOMENTARJI || $this->second_action == NAVI_TESTIRANJE_KOMENTARJI_ANKETA ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_testiranje_vnosi'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_TESTIRANJE . '&m=' . M_TESTIRANJE_VNOSI,
                    'active' => ($this->second_action == NAVI_TESTIRANJE_VNOSI ? 'active' : '')
                ),
            );

            $this->displayMenuItemWithSubmenu($name='test', $lang['srv_testiranje'], $submenu, ($this->first_action == NAVI_TESTIRANJE  ? 'active' : ''));
        }


        // OBJAVA
        if ($d->checkDostopSub('publish')) {
            
            $submenu = array(
                array(
                    'title' => $lang['srv_publication_base'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_VABILA . '&m=settings',
                    'active' => ($_GET['a'] == A_VABILA && ($_GET['m'] == '' || $_GET['m'] == 'settings') ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_publication_url'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_VABILA . '&m=url',
                    'active' => ($_GET['a'] == A_VABILA && $_GET['m'] == 'url' ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_inv_nav_invitations'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_INVITATIONS . '&m=settings',
                    'active' => ($_GET['a'] == A_INVITATIONS && $_GET['m'] != 'view_archive' ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_archive'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_INVITATIONS . '&m=view_archive',
                    'active' => ($_GET['a'] == A_INVITATIONS && $_GET['m'] == 'view_archive' ? 'active' : '')
                ),
            );

            $this->displayMenuItemWithSubmenu($name='publish', $lang['srv_vabila'], $submenu, ($this->first_action == NAVI_OBJAVA  ? 'active' : ''));
        }


        // PODATKI - ne prikazemo v glasovanju
        if ($this->surveyAdminClass->survey_type != 0 && $d->checkDostopSub('data')) {

            $submenu = array(
                array(
                    'title' => $lang['srv_link_data_browse'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_COLLECT_DATA,
                    'active' => (($_GET['m'] == '' && $_GET['a'] == A_COLLECT_DATA) ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_data_navigation_calculate'], 
                    'name' => 'calculation_submenu', 
                    'submenu' => array(
                        array(
                            'title' => $lang['navigation_NAVI_DATA_CALC_CALCULATION'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA.'&m='.M_COLLECT_DATA_CALCULATION,
                            'active' => ($_GET['a'] == A_COLLECT_DATA && $_GET['m'] == M_COLLECT_DATA_CALCULATION ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_CALC_CODING'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA.'&m='.M_COLLECT_DATA_CODING,
                            'active' => ($_GET['a'] == A_COLLECT_DATA && $_GET['m'] == M_COLLECT_DATA_CODING? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_CALC_CODING_AUTO'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA.'&m='.M_COLLECT_DATA_CODING_AUTO,
                            'active' => ($_GET['a'] == A_COLLECT_DATA && $_GET['m'] == M_COLLECT_DATA_CODING_AUTO ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_CALC_RECODING'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA.'&m='.M_COLLECT_DATA_RECODING,
                            'active' => ($_GET['a'] == A_COLLECT_DATA && $_GET['m'] == M_COLLECT_DATA_RECODING ? 'active' : '')
                        ),
                    ),
                    'active' => ($_GET['m'] == M_COLLECT_DATA_CALCULATION || $_GET['m'] == M_COLLECT_DATA_CODING || $_GET['m'] == M_COLLECT_DATA_CODING_AUTO || $_GET['m'] == M_COLLECT_DATA_RECODING || $_GET['m'] == M_COLLECT_DATA_RECODING_DASHBOARD ? 'active' : '')
                ),
                array(
                    'title' => $lang['srv_data_navigation_import'], 
                    'name' => 'import_submenu', 
                    'submenu' => array(
                        array(
                            'title' => $lang['navigation_NAVI_DATA_IMPORT_APPEND'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA.'&m=append',
                            'active' => ($_GET['a'] == A_COLLECT_DATA && $_GET['m'] == 'append' ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_IMPORT_MERGE'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA.'&m=merge',
                            'active' => ($_GET['a'] == A_COLLECT_DATA && $_GET['m'] == 'merge'? 'active' : '')
                        )
                    ),
                    'active' => ($_GET['m'] == 'merge' || $_GET['m'] == 'append' ? 'active' : '')
                )
            );

            if ($d->checkDostopSub('export')) {
                
                $submenu[] = array(
                    'title' => $lang['srv_export_tab'], 
                    'name' => 'export_submenu', 
                    'submenu' => array(
                        array(
                            'title' => $lang['navigation_NAVI_DATA_EXPORT_SPSS'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA_EXPORT.'&m='.M_EXPORT_SPSS,
                            'active' => ($_GET['a'] == A_COLLECT_DATA_EXPORT && $_GET['m'] == M_EXPORT_SPSS ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_EXPORT_SAV'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA_EXPORT.'&m='.M_EXPORT_SAV,
                            'active' => ($_GET['a'] == A_COLLECT_DATA_EXPORT && $_GET['m'] == M_EXPORT_SAV ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_EXPORT_EXCEL_XLS'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA_EXPORT.'&m='.M_EXPORT_EXCEL_XLS,
                            'active' => ($_GET['a'] == A_COLLECT_DATA_EXPORT && $_GET['m'] == M_EXPORT_EXCEL_XLS ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_EXPORT_EXCEL'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA_EXPORT.'&m='.M_EXPORT_EXCEL,
                            'active' => ($_GET['a'] == A_COLLECT_DATA_EXPORT && $_GET['m'] == M_EXPORT_EXCEL ? 'active' : '')
                        ),
                        array(
                            'title' => $lang['navigation_NAVI_DATA_EXPORT_TXT'], 
                            'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_COLLECT_DATA_EXPORT.'&m='.M_EXPORT_TXT,
                            'active' => ($_GET['a'] == A_COLLECT_DATA_EXPORT && $_GET['m'] == M_EXPORT_TXT ? 'active' : '')
                        ),  
                    ),
                    'active' => ($_GET['a'] == A_COLLECT_DATA_EXPORT ? 'active' : '')
                );
            }

            if ($d->checkDostopSub('edit')) {
                $submenu[] = array(
                    'title' => $lang['srv_archive'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_ARHIVI . '&m=data',
                    'active' => ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'data' ? 'active' : '')
                );
            }

            $this->displayMenuItemWithSubmenu($name='data', $lang['srv_results'], $submenu, ($this->first_action == NAVI_RESULTS || ($this->first_action == NAVI_UREJANJE && $_GET['m'] == 'data') ? 'active' : ''));
        }


        // ANALIZA
        if ($d->checkDostopSub('analyse')) {

            // Hierarhija
            if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
                $this->displayMenuItem($lang['srv_stat_analiza'], $url='index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_MEANS_HIERARHY);
            }
            // Navadne analize
            else{
                $submenu = array(
                    array(
                        'title' => $lang['srv_stat_analiza'], 
                        'name' => 'analyse_submenu', 
                        'submenu' => array(
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_0'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_SUMMARY,
                                'active' => ($_GET['m'] == M_ANALYSIS_SUMMARY ? 'active' : '')
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_1'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_DESCRIPTOR,
                                'active' => ($_GET['m'] == M_ANALYSIS_DESCRIPTOR ? 'active' : '')
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_2'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_FREQUENCY,
                                'active' => ($_GET['m'] == M_ANALYSIS_FREQUENCY ? 'active' : '')
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_3'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_CROSSTAB,
                                'active' => ($_GET['m'] == M_ANALYSIS_CROSSTAB ? 'active' : ''),
                            ),
                            array(
                                'title' => $lang['srv_multicrosstabs'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_MULTICROSSTABS,
                                'active' => ($_GET['m'] == M_ANALYSIS_MULTICROSSTABS ? 'active' : '')
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_4'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_MEANS,
                                'active' => ($_GET['m'] == M_ANALYSIS_MEANS ? 'active' : '')
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_5'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_TTEST,
                                'active' => ($_GET['m'] == M_ANALYSIS_TTEST ? 'active' : '')
                            ),            
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_6'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_BREAK,
                                'active' => ($_GET['m'] == M_ANALYSIS_BREAK ? 'active' : '')
                            )   
                        ),
                        'active' => (($_GET['a'] == 'analysis' && $_GET['m'] != 'charts' && $_GET['m'] != 'analysis_links' && $_GET['m'] != 'anal_arch') ? 'active' : '')               
                    ),

                    array(
                        'title' => $lang['srv_analiza_charts'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_CHARTS,
                        'active' => ($_GET['m'] == M_ANALYSIS_CHARTS ? 'active' : '')
                    ),
                    array(
                        'title' => $lang['srv_reporti'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_LINKS,
                        'active' => ($_GET['m'] == M_ANALYSIS_CREPORT || $this->second_action == NAVI_ANALYSIS_LINKS ? 'active' : '')
                    ),
                    array(
                        'title' => $lang['srv_archive'], 
                        'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_ANALYSIS . '&m=anal_arch',
                        'active' => ($_GET['a'] == A_ANALYSIS && $_GET['m'] == 'anal_arch' ? 'active' : '')
                    )
                );
    
                $this->displayMenuItemWithSubmenu($name='analyse', $lang['srv_analiza'], $submenu, ($this->first_action == NAVI_ANALYSIS ? 'active' : ''));
            }
        }
        

        echo '</ul>';

        echo '</div>';
    }


    private function displayMenuItemWithSubmenu($name, $title, $submenu, $active=""){
        global $lang;

        echo '<li class="has-submenu">';
        echo '  <a href="#" class="'.$active.'" data-submenu="submenu_'.$name.'" title="'.$title.'">'.$title.'<span class="faicon arrow_back"></span></a>';
        echo '</li>';

        // Podmeni
        echo '<div id="submenu_'.$name.'" class="submenu">';

        
        // Podmeni header
        echo '<div class="submenu-header" data-submenu-close="submenu_'.$name.'">';

        // Nazaj
        echo '  <a href="#"><span class="faicon arrow_back"></span></a>';

        // Label
	    echo '<label>'.$title.'</label>';

        echo '</div>';

        
        // Vsebina podmenija
        echo '<ul>';
        foreach($submenu as $submenu_item){

            // Dodaten podmeni
            if(isset($submenu_item['name'])){
                //$this->displaySubmenuItem($submenu_item['name'], $submenu_item['title'], $submenu_item['submenu']);
                $this->displayMenuItemWithSubmenu($submenu_item['name'], $submenu_item['title'], $submenu_item['submenu'], $submenu_item['active']);
            }
            else{
                $this->displayMenuItem($submenu_item['title'], $submenu_item['url'], $submenu_item['active']);
            }
        }
        echo '</ul>';

        echo '</div>';
    }
	
    private function displayMenuItem($title, $url, $active=""){

        echo '<li>';
        echo '<a class="'.$active.'" href="'.$url.'" title="'.$title.'">'.$title.'</a>';
        echo '</li>';
    }


    // Gumb za dodajanje vprasanja
    public static function displayAddQuestion($ank_id){
        global $lang;

        $row = SurveyInfo::getInstance()->getSurveyRow();

        // Anketa je zaklenjena
        if($row['locked'] == 1){
            echo '<div class="mobile_add_question bottom">';
            echo '  <span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="return false;">';
            echo '      <span class="faicon lock_close"></span> ';
            echo '  </a></span>';
            echo '</div>';

            return;
        }

        echo '<div class="mobile_add_question bottom">';
        echo '  <span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="mobile_add_question_popup(); return false;">';
        echo '      <span class="plus">+</span> ';
        echo '      <span>'.$lang['srv_mobile_add_question'].'</span>';
        echo '  </a></span>';
        echo '</div>';


        // Popup za dodajanje vprašanja
        echo '<div class="mobile_add_question_popup">';

        echo '  <div class="mobile_add_question_item" onClick="mobile_add_question(\'1\');"><span class="faicon radio_32"></span> <span class="item_text">'.$lang['srv_vprasanje_tip_1'].'</span></div>';
        echo '  <div class="mobile_add_question_item" onClick="mobile_add_question(\'2\');"><span class="faicon check_32"></span> <span class="item_text">'.$lang['srv_vprasanje_tip_2'].'</span></div>';
        echo '  <div class="mobile_add_question_item" onClick="mobile_add_question(\'21\');"><span class="faicon abc_32"></span> <span class="item_text">'.$lang['srv_vprasanje_tip_21'].'</span></div>';
        echo '  <div class="mobile_add_question_item" onClick="mobile_add_question(\'7\');"><span class="faicon number_32"></span> <span class="item_text">'.$lang['srv_vprasanje_tip_7'].'</span></div>';
        echo '  <div class="mobile_add_question_item" onClick="mobile_add_question(\'5\');"><span class="faicon nagovor"></span> <span class="item_text">'.$lang['srv_vprasanje_tip_5'].'</span></div>';
        echo '  <div class="mobile_add_question_item" onClick="mobile_add_question(\'6\');"><span class="faicon matrix_32"></span> <span class="item_text">'.$lang['srv_vprasanje_tip_6'].'</span></div>';

        echo '  <span class="buttonwrapper mobile_add_question_button">';
        echo '      <a class="ovalbutton ovalbutton_orange" href="#" onclick="mobile_add_question_popup_close(); return false;"><span>Zapri</span></a>';
        echo '  </span>';

        echo '</div>';
    }

    // Div ko se nimamo nobenega vprasanja v anketi
    public static function displayNoQuestions($ank_id){
        global $lang;

        // Skrijemo spodnji gumb
        echo '<style>.mobile_add_question.bottom{display: none;}</style>';

        echo '<div class="mobile_add_question center">';
        echo '  <span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="mobile_add_question_popup(); return false;">';
        echo '      <span class="plus">+</span> ';
        echo '      <span>'.$lang['srv_mobile_add_question'].'</span>';
        echo '  </a></span>';
        echo '</div>';
    }

    // Div za dodajanje kategorije v vprasanje
    public static function displayAddQuestionCategory($ank_id, $spr_id, $tip){
        global $lang;

        echo '<div class="add-variable-mobile">';
        echo '  <a href="#" onclick="vrednost_new_mobile(\''.$spr_id.'\', \''.$tip.'\'); return false;" title="'.$lang['srv_novavrednost'].'"><span class="faicon add small"></span> '.$lang['srv_novavrednost'].'</a>';
        echo '</div>';
    }
}
