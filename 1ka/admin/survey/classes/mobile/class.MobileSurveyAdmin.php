<?php

/*
 *  Class, ki skrbi za mobile izris admin vmesnika
 *
 */



class MobileSurveyAdmin{


    var $surveyAdminClass;


	function __construct($surveyAdminClass){
		global $site_url;

        $this->surveyAdminClass = $surveyAdminClass;
	}


    // Izris glave z menijem - znotraj ankete
    public function displayHeaderMobile(){

        echo '<div class="mobile_header '.($this->surveyAdminClass->anketa > 0 ? 'survey_edit' : 'survey_list').'">';

        // Ikona za meni
        $this->displayMenuIcon();  

        // Meni
        $this->displayMenu();       
        
        // Naslov ankete
        if($this->surveyAdminClass->anketa > 0){
            $this->displaySurveyTitle();
        }
        // Logo - enak kot na desktopu
        else{
            $this->displayLogo();
        }

        // Se inicializiramo zeynep jquery mobile menu
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


    // Izris menija
    private function displayMenu(){

        echo '<div class="mobile_menu first" data-menu-name="first">';

        // Izris uporabniških podatkov v dropdownu
        $this->displayMenuUser();

        // Meni znotraj ankete
        if($this->surveyAdminClass->anketa > 0){

            // Izris glavne navigacije v dropdownu
            $this->displayMenuSurveyNavigation();

            // Izris akcij za anketo (kopiraj, brisi...) v dropdownu
            $this->displayMenuSurveyActions();
        }
        // Meni v mojih anketah
        else{
            $this->displayMenuMySurveysNavigation();
        }

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
        global $lang, $admin_type, $app_settings;


        # naložimo razred z seznamom anket
        $SL = new SurveyList();
        $SLCount = $SL->countSurveys();
        $SLCountPhone = $SL->countPhoneSurveys();


        echo '<div class="mobile_menu_navigation">';

        echo '<ul>';


        // MOJE ANKETE
        $this->displayMenuItem($lang['srv_pregledovanje'], $url='index.php?a=pregledovanje');


        // TELEFONSKA ANKETA
		if ($SLCountPhone > 0 && $admin_type != '0') {
            $this->displayMenuItem($lang['srv_telephone_surveys'], $url='index.php?a=phoneSurveys');
		}
		
		
        // AKTIVNOST
		if ($SLCount > 0 && $admin_type == 0) {

            $submenu = array(
                array(
                    'title' => $lang['srv_ankete'], 
                    'url'   => 'index.php?a=diagnostics'
                ),
                array(
                    'title' => $lang['srv_weekly_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span_daily'
                ),
                array(
                    'title' => $lang['srv_monthly_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span_monthly'
                ),
                array(
                    'title' => $lang['srv_yearly_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span_yearly'
                ),
                array(
                    'title' => $lang['srv_all_diagnostics'], 
                    'url'   => 'index.php?a=diagnostics&t=time_span&uvoz=0&ustrezni=1&delnoustrezni=1&neustrezni=0'
                ),
                array(
                    'title' => $lang['srv_metapodatki'], 
                    'url'   => 'index.php?a=diagnostics&t=paradata'
                )
            );
    
            $this->displayMenuItemWithSubmenu($name='diagnostics', $lang['srv_diagnostics'], $submenu);
		}
		

		// UPORABNIKI
		if ($admin_type <= 1) {

            // Admini imajo pregled nad vsemi zavihki uporabnikov
            if ($admin_type == 0) {

                $submenu = array(
                    array(
                        'title' => $lang['n_users_list'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki'
                    ),
                    array(
                        'title' => $lang['n_users_list_all'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=all'
                    ),
                    array(
                        'title' => $lang['n_deleted_users'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=izbrisani'
                    ),
                    array(
                        'title' => $lang['n_unconfirmed_users'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=nepotrjeni'
                    ),
                    array(
                        'title' => $lang['n_unsigned_users'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=odjavljeni'
                    ),
                    array(
                        'title' => $lang['srv_hierarchy_users_access'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki&m=sa-modul'
                    ),
                );
            }
            
            // Manegerji imajo samo osnovni pregled svojih uporabnikov
            if ($admin_type == 1) {

                $submenu = array(
                    array(
                        'title' => $lang['n_users_list'], 
                        'url'   => 'index.php?a=diagnostics&t=uporabniki'
                    )
                );
            }

            $this->displayMenuItemWithSubmenu($name='uporabniki', $lang['hour_users'], $submenu);
		}


        // KNJIZNICA
        $submenu = array(
            array(
                'title' => $lang['srv_javna_knjiznica'], 
                'url'   => 'index.php?a=knjiznica'
            ),
            array(
                'title' => $lang['srv_moja_knjiznica'], 
                'url'   => 'index.php?a=knjiznica&t=moje_ankete'
            )
        );

        $this->displayMenuItemWithSubmenu($name='knjiznica', $lang['srv_library'], $submenu);

		
		// NASTAVITVE
        if ($admin_type == '0') {
            $submenu = array(
                array(
                    'title' => $lang['srv_settingsSystem'], 
                    'url'   => 'index.php?a=nastavitve&m=system'
                ),
                array(
                    'title' => $lang['srv_testiranje_predvidenicas'], 
                    'url'   => 'index.php?a=nastavitve&m=predvidenicasi'
                ),
                array(
                    'title' => $lang['srv_collectData'], 
                    'url'   => 'index.php?a=nastavitve&m=collectData'
                ),
                array(
                    'title' => $lang['srv_nice_url'], 
                    'url'   => 'index.php?a=nastavitve&m=nice_links'
                ),
                array(
                    'title' => $lang['srv_anketa_admin'], 
                    'url'   => 'index.php?a=nastavitve&m=anketa_admin'
                ),
                array(
                    'title' => $lang['srv_anketa_deleted'], 
                    'url'   => 'index.php?a=nastavitve&m=anketa_deleted'
                ),
                array(
                    'title' => $lang['srv_data_deleted'], 
                    'url'   => 'index.php?a=nastavitve&m=data_deleted'
                ),
                array(
                    'title' => $lang['srv_user_settings'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_settings'
                ),
                array(
                    'title' => $lang['edit_data'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_myProfile'
                ),
            );
        }
        else{
            $submenu = array(
                array(
                    'title' => $lang['srv_user_settings'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_settings'
                ),
                array(
                    'title' => $lang['edit_data'], 
                    'url'   => 'index.php?a=nastavitve&m=global_user_myProfile'
                ),
            );
        }

        $this->displayMenuItemWithSubmenu($name='nastavitve', $lang['settings'], $submenu);

		        
        // NAROCILA
        if($app_settings['commercial_packages']){
            
            // placila - samo admini
            if ($admin_type == '0') {

                $submenu = array(
                    array(
                        'title' => $lang['srv_narocila_my'], 
                        'url'   => 'index.php?a=narocila'
                    ),
                    array(
                        'title' => $lang['srv_placila'], 
                        'url'   => 'index.php?a=narocila&m=placila'
                    )
                );
    
                $this->displayMenuItemWithSubmenu($name='nastavitve', $lang['settings'], $submenu);
            }
            // moja narocila
            else{
                $this->displayMenuItem($lang['srv_narocila'], $url='index.php?a=narocila');
            }
        }
		

		// GDPR
        $request_counter = GDPR::countUserUnfinishedRequests();

        $submenu = array(
            array(
                'title' => $lang['srv_gdpr_user_settings'], 
                'url'   => 'index.php?a=gdpr'
            ),
            array(
                'title' => $lang['srv_gdpr_survey_list'], 
                'url'   => 'index.php?a=gdpr&m=placila'
            ),
            array(
                'title' => $lang['srv_gdpr_dpa'], 
                'url'   => 'index.php?a=gdpr'
            ),
            array(
                'title' => $lang['srv_gdpr_requests'].' ('.$request_counter.')', 
                'url'   => 'index.php?a=gdpr&m=gdpr_requests'
            )
        );

        // Vse zahteve za izbris - samo ADMINI
        if($admin_type == '0'){
            $submenu[] = array(
                'title' => $lang['srv_gdpr_requests_all'], 
                'url'   => 'index.php?a=gdpr&m=gdpr_requests_all'
            );
        }

        $this->displayMenuItemWithSubmenu($name='gdpr', 'GDPR', $submenu);
   

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
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_REPORTI
                    ),
                    array(
                        'title' => $lang['srv_metapodatki'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_PARA_GRAPH
                    )
                );

                $this->displayMenuItemWithSubmenu($name='dashboard', $lang['srv_navigation_status'], $submenu);
            }
        }


        // UREJANJE
        if ($d->checkDostopSub('edit') && $hierarhija_type < 5 && !$this->surveyAdminClass->isAnketar) {
            
            $submenu = array(
                array(
                    'title' => $lang['srv_editirajanketo2'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . ($this->surveyAdminClass->survey_type > 1 ? '&a=' . A_BRANCHING : '')
                ),
                array(
                    'title' => $lang['srv_nastavitve_ankete'] , 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_SETTINGS
                ),
                array(
                    'title' => $lang['srv_themes'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_TEMA
                ),
                array(
                    'title' => $lang['srv_analiza_arhiv'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_ARHIVI
                ),
            );

            $this->displayMenuItemWithSubmenu($name='edit', $lang['srv_vprasalnik'], $submenu);
        }


        # TESTIRANJE - ne prikazemo v glasovanju
        if ($this->surveyAdminClass->survey_type != 0 && $this->surveyAdminClass->survey_type != 1 && $d->checkDostopSub('test')) {     
            
            $submenu = array(
                array(
                    'title' => $lang['srv_testiranje_diagnostika'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_TESTIRANJE . '&m=' . M_TESTIRANJE_DIAGNOSTIKA
                ),
                array(
                    'title' => $lang['srv_testiranje_komentarji'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_KOMENTARJI
                ),
                array(
                    'title' => $lang['srv_testiranje_vnosi'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_TESTIRANJE . '&m=' . M_TESTIRANJE_VNOSI
                ),
            );

            $this->displayMenuItemWithSubmenu($name='test', $lang['srv_testiranje'], $submenu);
        }


        // OBJAVA
        if ($d->checkDostopSub('publish')) {
            
            $submenu = array(
                array(
                    'title' => $lang['srv_publication_base'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_VABILA . '&m=settings'
                ),
                array(
                    'title' => $lang['srv_publication_url'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_VABILA . '&m=url'
                ),
                array(
                    'title' => $lang['srv_inv_nav_invitations'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_INVITATIONS . '&m=settings'
                ),
            );

            $this->displayMenuItemWithSubmenu($name='publish', $lang['srv_vabila'], $submenu);
        }


        // PODATKI - ne prikazemo v glasovanju
        if ($this->surveyAdminClass->survey_type != 0 && $d->checkDostopSub('data')) {

            $submenu = array(
                array(
                    'title' => $lang['srv_link_data_browse'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_COLLECT_DATA
                ),
                array(
                    'title' => $lang['srv_data_navigation_calculate'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_COLLECT_DATA . '&m=calculation'
                ),
                array(
                    'title' => $lang['srv_data_navigation_import'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_COLLECT_DATA . '&m=append'
                )
            );

            if ($d->checkDostopSub('export')) {
                $submenu[] = array(
                    'title' => $lang['srv_export_tab'], 
                    'url'   => 'index.php?anketa=' . $this->surveyAdminClass->anketa . '&a=' . A_COLLECT_DATA_EXPORT
                );
            }

            $this->displayMenuItemWithSubmenu($name='data', $lang['srv_results'], $submenu);
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
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_SUMMARY
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_1'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_DESCRIPTOR
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_2'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_FREQUENCY
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_3'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_CROSSTAB
                            ),
                            array(
                                'title' => $lang['srv_multicrosstabs'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_MULTICROSSTABS
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_4'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_MEANS
                            ),
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_5'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_TTEST
                            ),            
                            array(
                                'title' => $lang['srv_analiza_arhiviraj_type_6'], 
                                'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_BREAK
                            )   
                        )               
                    ),
                    array(
                        'title' => $lang['srv_analiza_charts'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_CHARTS
                    ),
                    array(
                        'title' => $lang['srv_reporti'], 
                        'url'   => 'index.php?anketa='.$this->surveyAdminClass->anketa.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_LINKS
                    ),
                );
    
                $this->displayMenuItemWithSubmenu($name='analyse', $lang['srv_analiza'], $submenu);
            }
        }
        

        echo '</ul>';

        echo '</div>';
    }

    // Izris akcij za anketo (kopiraj, brisi...) v dropdownu
    private function displayMenuSurveyActions(){
        global $lang;

        echo '<div class="mobile_menu_actions">';

        # kopiranje
        echo '  <a href="#" onclick="anketa_copy_top(\'' . $this->surveyAdminClass->anketa . '\'); return false;" title="'.$lang['srv_anketacopy'].'" class="srv_ico">';
        echo '      <span class="faicon anketa_copy"></span> '.$lang['srv_anketacopy'];
        echo '  </a>';

        # brisanje
        echo '  <a href="#" onclick="anketa_delete(\'' . $this->surveyAdminClass->anketa . '\', \'' . $lang['srv_anketadeleteconfirm'] . '\'); return false;" title="' . $lang['srv_anketadelete'] . '" class="srv_ico">';
        echo '      <span class="faicon anketa_delete" title="'.$lang['srv_anketadelete'].'"></span> '.$lang['srv_anketadelete'];
        echo '  </a>';
        
        echo '</div>';
    }



    private function displayMenuItemWithSubmenu($name, $title, $submenu){
        global $lang;

        echo '<li class="has-submenu">';
        echo '  <a href="#" data-submenu="submenu_'.$name.'" title="'.$title.'">'.$title.'<span class="faicon arrow_back"></span></a>';
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
                $this->displayMenuItemWithSubmenu($submenu_item['name'], $submenu_item['title'], $submenu_item['submenu']);
            }
            else{
                $this->displayMenuItem($submenu_item['title'], $submenu_item['url']);
            }
        }
        echo '</ul>';

        echo '</div>';
    }
	
    private function displayMenuItem($title, $url){

        echo '<li>';
        echo '<a href="'.$url.'" title="'.$title.'">'.$title.'</a>';
        echo '</li>';
    }


    // Gumb za dodajanje vprasanja
    public static function displayAddQuestion($ank_id){
        global $lang;

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
