<?php

/** Pomožen class
 *
 * Enter description here ...
 * @author veselicg
 *
 */
class SurveyStaticHtml
{
    private $sid = null;


    public function __construct($anketa){
        global $global_user_id;

        $this->sid = $anketa;

        SurveyUserSetting:: getInstance()->Init($anketa, $global_user_id);
    }


    # Nastavitve multicrosstab tabele
    public function displayMulticrosstabSettings(){
        global $lang;
        global $global_user_id;

        $this->table_id = SurveyUserSetting:: getInstance()->getSettings('default_mc_table');

        if (isset($this->table_id) && $this->table_id != '')
            $sql = sisplet_query("SELECT name FROM srv_mc_table WHERE id='$this->table_id' AND ank_id='$this->sid' AND usr_id='$global_user_id'");
        else
            $sql = sisplet_query("SELECT name FROM srv_mc_table WHERE ank_id='$this->sid' AND usr_id='$global_user_id' ORDER BY time_created ASC");
        $row = mysqli_fetch_array($sql);

        echo '<div class="mc_settings_links">';
        echo '<ul>';

        // Izbiro tabele (popup) in dodajanje
        echo '<li>';
        echo '<span onClick="show_mc_tables();">' . $lang['srv_table'] . ': <span class="bold">' . $row['name'] . '</span></span>';
        echo '<span id="mc_tables_plus" class="pointer spaceLeft faicon add icon-blue" title="' . $lang['srv_multicrosstabs_tables_add'] . '"></span></span>';
        echo '</li>';

        echo '<li class="space">&nbsp;</li>';

        // Nastavitve tabele (popup)
        echo '<li>';
        echo '<span class="gray" onClick="showMCSettings();">' . $lang['srv_multicrosstabs_settings'] . '</span>';
        echo '</li>';

        echo '</ul>';
        echo '</div>';
    }

    function displayAnalizaPreview()
    {
        global $lang;
        echo '<div id="srv_analiza_preview_div" class="displayNone;">';
        echo '<div class="top-left"></div>';
        echo '<div class="top-right"></div>';
        echo '<div class="inside">';

        echo '<div id="srv_analiza_preview_sub_1" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_1'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledSumarnik.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_1

        echo '<div id="srv_analiza_preview_sub_2" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_2'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledOpisne.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_2

        echo '<div id="srv_analiza_preview_sub_3" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_3'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledFrekvence.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_3

        echo '<div id="srv_analiza_preview_sub_4" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_4'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledTabele.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_4

        echo '<div id="srv_analiza_preview_sub_5" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_5'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledPovprecja.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_5


        echo '<div id="srv_analiza_preview_sub_6" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_6'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledTTest.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_6

        echo '<div id="srv_analiza_preview_sub_7" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_7'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledRazbitje.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_7

        echo '<div id="srv_analiza_preview_sub_8" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_8'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledMultitabele.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_8

        echo '<div id="srv_analiza_preview_sub_9" class="srv_analiza_preview_sub hidden">';
        echo '<span class="red">' . $lang['srv_analize_preview_sample_choose'] . '</span><br/>';
        echo '<div id="srv_preview_analiza">';
        echo '<span class="large">' . $lang['srv_analize_preview_sample'] . '</span>';
        echo '<span class="large">' . $lang['srv_analize_preview_9'] . '</span>';
        echo $lang['srv_analysys_perview_sample'];
        include 'staticHtml/AnalizaPredogledNeodgovori.html';
        echo '</div>'; //srv_preview_analiza
        echo '</div>'; //srv_analiza_preview_sub_9

        echo '</div>'; // inside
        echo '<div class="bottom-left"></div>';
        echo '<div class="bottom-right"></div>';
        echo '</div>'; // srv_analiza_preview_div
    }

    public function displayArchiveNavigation($showDiv = true)
    {
        global $lang, $admin_type, $global_user_id;

        $d = new Dostop();

        $sa = new SurveyAdmin();
        $this->survey_type = $sa->getSurvey_type($this->sid);

        echo '<div id="globalSetingsLinks" class="archive">';
        echo '<ul class="">';

        # arhivi vprasalnika
        if ($d->checkDostopSub('edit')) {
            echo '<li' . ($_GET['a'] == A_ARHIVI && $_GET['m'] != 'data' && $_GET['m'] != 'changes' && $_GET['m'] != 'survey' && $_GET['m'] != 'survey_data' && $_GET['m'] != 'testdata' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . $_js_links[1] . '>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ARHIVI . '" title="' . $lang['srv_questionnaire_archives'] . '"><span>' . $lang['srv_questionnaire_archives'] . '</span></a>';
            echo '</li>';
        }

        # arhivi podatkov
        if ($d->checkDostopSub('edit') && $this->survey_type > 0) {
            echo '<li' . ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'data' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . $_js_links[1] . '>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ARHIVI . '&m=data" title="' . $lang['srv_arhiv_data'] . '"><span>' . $lang['srv_arhiv_data'] . '</span></a>';
            echo '</li>';
        }

        # arhivi objave
        if ($d->checkDostopSub('publish')) {
            echo '<li' . ($_GET['a'] == A_INVITATIONS && $_GET['m'] == 'view_archive' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . $_js_links[2] . '>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_INVITATIONS . '&m=view_archive' . '" title="' . $lang['srv_archive_invitation'] . '"><span>' . $lang['srv_archive_invitation'] . '</span></a>';
            echo '</li>';
        }

        # arhivi analiz
        if ($d->checkDostopSub('analyse')) {
            echo '<li' . ($_GET['a'] == A_ANALYSIS && $_GET['m'] == M_ANALYSIS_ARCHIVE ? ' class="highlightLineTab"' : ' class="nonhighlight"') . $_js_links[3] . '>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_ARCHIVE . '" title="' . $lang['srv_archive_analysis'] . '"><span>' . $lang['srv_archive_analysis'] . '</span></a>';
            echo '</li>';
        }

        # uvoz/izvoz ankete ali ankete s podatki
        if ($d->checkDostopSub('edit')) {
            echo '<li' . ($_GET['a'] == A_ARHIVI && ($_GET['m'] == 'survey' || $_GET['m'] == 'survey_data') ? ' class="navi_tracking highlightLineTab"' : ' class="navi_tracking nonhighlight"') . $_js_links[1] . '>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ARHIVI . '&m=survey" title="' . $lang['srv_survey_archives'] . '"><span>' . $lang['srv_survey_archives'] . '</span></a>';
            echo '</li>';

            if ($_GET['a'] == A_ARHIVI && ($_GET['m'] == 'survey' || $_GET['m'] == 'survey_data')) {
                echo '<ul id="sub_navi_tracking">';

                // Uvoz/izvoz ankete
                echo '<li ' . ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'survey' && $_GET['appendMerge'] != '1' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ARHIVI . '&m=survey" title="' . $lang['srv_survey_archives_ie'] . '"><span>' . $lang['srv_survey_archives_ie'] . '</span></a>';
                echo '</li>';

                // Uvoz/izvoz ankete in podatkov
                echo '<li ' . ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'survey_data' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ARHIVI . '&m=survey_data" title="' . $lang['srv_survey_archives_ie_data'] . '"><span>' . $lang['srv_survey_archives_ie_data'] . '</span></a>';
                echo '</li>';

                echo '</ul>';
            }
        }

        # arhivi testnih vnosov
        if ($this->survey_type > 1) {
            $str_testdata = "SELECT count(*) FROM srv_user WHERE ank_id='" . $this->sid . "' AND (testdata='1' OR testdata='2') AND deleted='0'";
            $query_testdata = sisplet_query($str_testdata);
            list($testdata) = mysqli_fetch_row($query_testdata);
            if ((int)$testdata > 0) {
                echo '<li' . ($_GET['a'] == A_ARHIVI && $_GET['m'] == 'testdata' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ARHIVI . '&m=testdata" title="' . $lang['srv_arhiv_testdata'] . '"><span>' . $lang['srv_arhiv_testdata'] . '</span></a>';
                echo '</li>';
            }
        }

        # arhivi sprememb
        if ($d->checkDostopSub('edit')) {
            echo '<li' . ($_GET['a'] == A_TRACKING || $_GET['a'] == A_TRACKING_HIERARHIJA  ? ' class="navi_tracking highlightLineTab"' : ' class="navi_tracking nonhighlight"') . '>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_TRACKING . '" title="' . $lang['srv_survey_archives_tracking'] . '"><span>' . $lang['srv_survey_archives_tracking'] . '</span></a>';
            echo '</li>';

            if ($_GET['a'] == A_TRACKING || $_GET['a'] == A_TRACKING_HIERARHIJA) {
                echo '<ul id="sub_navi_tracking">';

                // Vse spremembe ankete
                echo '<li ' . ($_GET['a'] == A_TRACKING && $_GET['m'] != 'tracking_data' && $_GET['appendMerge'] != '1' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_TRACKING . '" title="' . $lang['srv_survey_archives_tracking_survey'] . '"><span>' . $lang['srv_survey_archives_tracking_survey'] . '</span></a>';
                echo '</li>';

                $hierarhija = false;
                if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
                    $uporabnik = sisplet_query("SELECT type FROM srv_hierarhija_users WHERE anketa_id='".$this->sid."' AND user_id='".$global_user_id."'", "obj");
                    if (!empty($uporabnik) && $uporabnik->type == 1)
                        $hierarhija = true;
                }

                if ($hierarhija) {

                    // Vsi podatki o gradnji hierarhije, šifrantov in ostalega
                    echo '<li ' . ($_GET['a'] == A_TRACKING_HIERARHIJA && $_GET['m'] == 'hierarhija' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                    echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_TRACKING_HIERARHIJA. '&m=hierarhija" title="' . $lang['srv_survey_archives_tracking_hierarchy_structure'] . '"><span>' . $lang['srv_survey_archives_tracking_hierarchy_structure'] . '</span></a>';
                    echo '</li>';

                    // Vse spremembe pri dodajanju udeležencev
                    echo '<li ' . ($_GET['a'] == A_TRACKING_HIERARHIJA && $_GET['m'] == 'udelezenci' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                    echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_TRACKING_HIERARHIJA . '&m=udelezenci" title="' . $lang['srv_survey_archives_tracking_hierarchy_users'] . '"><span>' . $lang['srv_survey_archives_tracking_hierarchy_users'] . '</span></a>';
                    echo '</li>';
                }

                // Spremembe na podatkih
                echo '<li ' . ($_GET['a'] == A_TRACKING && $_GET['m'] == 'tracking_data' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_TRACKING . '&m=tracking_data" title="' . $lang['srv_survey_archives_tracking_data'] . '"><span>' . $lang['srv_survey_archives_tracking_data'] . '</span></a>';
                echo '</li>';

                // Append/Merge (uvozi)
                echo '<li ' . ($_GET['a'] == A_TRACKING && $_GET['appendMerge'] == '1' ? ' class="highlightLineTab"' : ' class="nonhighlight"') . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_TRACKING . '&appendMerge=1" title="' . $lang['srv_survey_archives_tracking_append'] . '"><span>' . $lang['srv_survey_archives_tracking_append'] . '</span></a>';
                echo '</li>';

                echo '</ul>';
            }
        }



        echo '</ul>';
        echo '</div>';
    }

    public function displayTestDataBar($showAnalizeCheckbox = false)
    {
        global $lang;


        $str_testdata = "SELECT count(*) FROM srv_user WHERE ank_id='" . $this->sid . "' AND (testdata='1' OR testdata='2') AND deleted='0'";
        $query_testdata = sisplet_query($str_testdata);
        list($testdata) = mysqli_fetch_row($query_testdata);

        $str_autogen_testdata = "SELECT count(*) FROM srv_user WHERE ank_id='" . $this->sid . "' AND testdata='2' AND deleted='0'";
        $query_autogen_testdata = sisplet_query($str_autogen_testdata);
        list($autogen_testdata) = mysqli_fetch_row($query_autogen_testdata);

        echo '<div class="display_data_test_data_note"><span class="faicon warning icon-orange spaceRight"></span> ';

        echo $lang['srv_testni_podatki_alert'] . ' <a href="#" onClick="delete_test_data();">' . $lang['srv_delete_testdata'] . '</a>';
        echo ' (' . $testdata . '). ';
        if ($autogen_testdata > 0) {
            echo $lang['srv_autogen_testni_podatki_alert'] . ' <a href="index.php?anketa=' . $this->sid . '&a=testiranje&m=testnipodatki&delete_autogen_testdata=1">' . $lang['srv_delete_autogen_testdata'] . '</a>';
            echo ' (' . $autogen_testdata . '). ';
        }

        if ($showAnalizeCheckbox == true && false) {
            #		print_r("<pre>");
            #		print_r($_SESSION);
            session_start();
            $checked = (isset($_SESSION['testData'][$this->sid]['includeTestData']) && $_SESSION['testData'][$this->sid]['includeTestData'] == 'false') ? '' : ' checked="checked"';
            echo '&nbsp;<label><input id="cnx_include_test_data" type="checkbox"' . $checked . ' onchange="surveyAnalisysIncludeTestData();" autocomplete="off">V analizah upoštevaj tudi testne vnose.';
            echo '</label>';
            session_commit();
        }
        echo '</div>';
    }


    /* Nastavitve na vrhu pri analizah in podatkih - NOVO
    *	Podstrani: 	data, export, quick_edit, variables
                    sumarnik, descriptor, frequency, crosstabs, ttest, means, nonresponses,
                    charts,
                    analysis_creport, analysis_links
                    ocena_trajanja, dejanski_casi,
                    komentarji, komentarji_anketa,
                    status,
                    tema, theme_editor
    */
    public function displayTopSettings($podstran){
        global $lang, $admin_type, $site_url, $global_user_id;

        // Ce nimamo podatkov ponekod tega potem ne prikazujemo
        $SDF = SurveyDataFile::get_instance();
        $SDF->init($this->sid);
        $data_file_status = $SDF->getStatus();
  
        if( in_array($data_file_status, array(FILE_STATUS_SRV_DELETED, FILE_STATUS_NO_DATA))
            && in_array($podstran, array(
                'para_analysis_graph', 'para_graph', 'usable_resp', 'status_advanced',
                'data', 'quick_edit', 'variables', 'export', 
                'sumarnik', 'descriptor', 'frequency', 'crosstabs', 'ttest', 'means', 'break', 'multicrosstabs', 'charts', 'analysis_links'
            )) ){

            return;
        }

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
        

        echo '<div id="topSettingsHolder" class="'.$podstran.'">';
        
        $analiza = false;
        if (in_array($podstran, array('sumarnik', 'descriptor', 'frequency', 'crosstabs', 'ttest', 'means', 'break', 'multicrosstabs', 'nonresponses'))) {
            $analiza = true;
        }

        $borderLeft = '';

        // Navigacija analiz - ANALIZE
        if ($analiza) {
            echo '<div id="analizaSubNav">';
            $this->displayAnalizaSubNavigation();
            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        }


        // Preklop med porocilom po meri in navadnimi porocili
        if ($podstran == 'analysis_creport' || $podstran == 'analysis_links') {
            echo '<div id="additional_navigation">';

            // Link na navadna porocila
            echo '<a href="index.php?anketa=' . $this->sid . '&a=analysis&m=analysis_links"><span ' . ($podstran == 'analysis_links' ? ' class="active"' : '') . '>' . $lang['srv_standard_report'] . '</span></a>';

            // Link na porocilo po meri
            echo '<a href="index.php?anketa=' . $this->sid . '&a=analysis&m=analysis_creport"><span ' . ($podstran == 'analysis_creport' ? ' class="active"' : '') . '>' . $lang['srv_custom_report'] . '</span></a>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } 
        // Preklop na vpogled, hitri seznam, spremenljivke (podatki)
        elseif ($podstran == 'data') {
            echo '<div id="additional_navigation">';

            // Link na vpogled
            echo '<a href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->sid . '&a=' . A_COLLECT_DATA . '&m=quick_edit&quick_view=1"><span>' . $lang['srv_lnk_vpogled'] . '</span></a>';

            // Link na spremenljivke
            echo '<a href="' . $site_url . 'admin/survey/index.php?anketa=' . $this->sid . '&a=' . A_COLLECT_DATA . '&m=' . M_COLLECT_DATA_VARIABLE_VIEW . '"><span>' . $lang['srv_lnk_pregled_variabel'] . '</span></a>';

            // Link na hitri seznam
            echo '<a href="#" onClick="displayDataPrintPreview();"><span style="padding-right:0px !important;">' . $lang['srv_data_print_preview_link'] . '</span></a>';
            echo '<span style="margin-right:40px;">' . Help:: display('srv_data_print_preview') . '</span>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } 
        // Link nazaj na podatke - vpogled
        elseif ($podstran == 'quick_edit') {
            echo '<div id="additional_navigation">';

            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_COLLECT_DATA . '"><span class="faicon arrow_back" title="' . $lang['srv_lnk_back_to_data'] . '"></span></a>';
            echo '<a href="#"><span class="active">' . $lang['srv_data_title_quick_view'] . '</span></a>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } 
        // Link nazaj na podatke - Spremenljivke
        elseif ($podstran == 'variables') {
            echo '<div id="additional_navigation">';

            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_COLLECT_DATA . '"><span class="faicon arrow_back" title="' . $lang['srv_lnk_back_to_data'] . '"></span></a>';
            echo '<a href="#"><span class="active">' . $lang['srv_data_title_variable_view'] . '</span></a>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } 
        // Link nazaj na diagnostiko - Ocenjevanje trajanja
        elseif ($podstran == 'ocena_trajanja') {
            echo '<div id="additional_navigation">';

            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . M_TESTIRANJE_PREDVIDENI . '" title="' . $lang['srv_testiranje_diagnostika_base'] . '"><span class="active">' . $lang['srv_testiranje_predvidenicas'] . '</span></a>';
            echo '<a href="#"></a>';
            
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . M_TESTIRANJE_CAS . '" title="' . $lang['srv_testiranje_diagnostika_base'] . '"><span>' . $lang['srv_testiranje_cas'] . '</span></a>';
            echo '<a href="#"></a>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } 
        // Link nazaj na diagnostiko - Dejanski casi
        elseif ($podstran == 'dejanski_casi') {
            echo '<div id="additional_navigation">';
            
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . M_TESTIRANJE_PREDVIDENI . '" title="' . $lang['srv_testiranje_predvidenicas'] . '"><span>' . $lang['srv_testiranje_predvidenicas'] . '</span></a>';
            echo '<a href="#"></a>';

            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=' . A_TESTIRANJE . '&amp;m=' . M_TESTIRANJE_CAS . '" title="' . $lang['srv_testiranje_cas'] . '"><span class="active">' . $lang['srv_testiranje_cas'] . '</span></a>';
            echo '<a href="#"></a>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } 
        // Link nazaj na komentarje
        elseif ($podstran == 'komentarji' || $podstran == 'komentarji_anketa') {

            // Prestejemo komentarje (nereseni/vsi)
            $sas = new SurveyAdminSettings();
            $comment_count = $sas->testiranje_komentarji_count();

            echo '<div id="additional_navigation">';

            //Komentarji na vprasanja
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=' . A_KOMENTARJI_ANKETA . '" title="' . $lang['srv_testiranje_komentarji_anketa_title'] . '">';
            echo '<span ' . ($_GET['a'] == A_KOMENTARJI_ANKETA ? 'class="active"' : '') . '>';
            echo $lang['srv_testiranje_komentarji_anketa_title'];
            echo '</span>';
            echo '</a>';

            echo '<span class="bold" style="margin-right: 50px;"> (';
            if ($comment_count['survey_resp']['unresolved'] + $comment_count['survey_admin']['unresolved'] > 0)
                echo '<span class="orange">';
            echo($comment_count['survey_resp']['unresolved'] + $comment_count['survey_admin']['unresolved']);
            if ($comment_count['survey_resp']['unresolved'] + $comment_count['survey_admin']['unresolved'] > 0)
                echo '</span>';
            echo '/' . ($comment_count['survey_resp']['all'] + $comment_count['survey_admin']['all']);
            echo ')</span>';

            // Komentarji na anketo
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=' . A_KOMENTARJI . '" title="' . $lang['srv_testiranje_komentarji_title'] . '">';
            echo '<span ' . ($_GET['a'] == A_KOMENTARJI ? ' class="active"' : '') . '>';
            echo $lang['srv_testiranje_komentarji_title'];
            echo '</span>';
            echo '</a>';

            echo '<span class="bold"> (';
            if ($comment_count['question']['unresolved'] > 0)
                echo '<span class="orange">';
            echo $comment_count['question']['unresolved'];
            if ($comment_count['question']['unresolved'] > 0)
                echo '</span>';
            echo '/' . $comment_count['question']['all'];
            echo ')</span>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } elseif ($podstran == 'theme-editor') {
            echo '<div id="additional_navigation">';

			$mobile = (isset($_GET['mobile']) && $_GET['mobile'] == '1') ? '&mobile=1' : '';
			
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=tema'.$mobile.'" title="' . $lang['srv_themes_select'] . '"><span class="faicon arrow_back"></span></a>';
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=theme-editor&profile='. $_GET['profile'] . $mobile.'" title="' . $lang['srv_themes_mod'] . '"><span ' . ($_GET['a'] == 'theme-editor' && $_GET['t'] != 'css' && $_GET['t'] != 'upload' ? ' class="active"' : '') . '>' . $lang['srv_themes_mod'] . '</span></a>';
            echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=theme-editor&t=css&profile='. $_GET['profile'] . $mobile.'" title="' . $lang['srv_themes_edit'] . '"><span ' . ($_GET['a'] == 'theme-editor' && $_GET['t'] == 'css' ? ' class="active"' : '') . '>' . $lang['srv_themes_edit'] . '</span></a>';
            
			// Za mobilno temo zaenkrat nimamo uploada css-ja
			if($mobile == '')
				echo '<a href="index.php?anketa=' . $this->sid . '&amp;a=theme-editor&t=upload&profile='. $_GET['profile'] . $mobile.'" title="' . $lang['srv_themes_upload_css'] . '"><span ' . ($_GET['a'] == 'theme-editor' && $_GET['t'] == 'upload' ? ' class="active"' : '') . '>' . $lang['srv_themes_upload_css'] . '</span></a>';

            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        } // Link nazaj na podatke - Spremenljivke
        elseif ($podstran == 'para_analysis_graph') {

            // Info o neodgovorih
            echo '<div id="nonresponse_info">';
            echo $lang['srv_para_graph_text2'];
            echo '</div>';
            $borderLeft = ' class="borderLeft"';

            echo '<div id="additional_navigation" ' . $borderLeft . ' style="padding-left: 40px;">';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_NONRESPONSE_GRAPH . '"><span ' . (!isset($_GET['m']) || $_GET['m'] == '' ? 'class="active"' : '') . '>' . $lang['srv_para_label_variables'] . '</span></a>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_NONRESPONSE_GRAPH . '&m=breaks"><span ' . ($_GET['m'] === 'breaks' ? 'class="active"' : '') . '>' . $lang['srv_para_label_breaks'] . '</span></a>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_NONRESPONSE_GRAPH . '&m=advanced"><span ' . ($_GET['m'] === 'advanced' ? 'class="active"' : '') . '>' . $lang['srv_para_label_details'] . '</span></a>';
            echo '</div>';
            $borderLeft = ' class="borderLeft"';
        } 
        elseif ($podstran == 'aapor') {
            echo '<div id="additional_navigation">';

            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . NAVI_AAPOR . '&m=aapor1"><span>' . $lang['srv_lnk_AAPOR1'] . '</span></a>';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . NAVI_AAPOR . '&m=aapor2"><span>' . $lang['srv_lnk_AAPOR2'] . '</span></a>';
            echo '</div>';
        } 
        elseif ($podstran == 'diagnostics') {
            echo '<div id="additional_navigation">';
            echo '<a href="index.php?anketa=' . $this->sid . '&a=' . NAVI_AAPOR . '&m=aapor1"><span>' . $lang['srv_lnk_AAPOR1'] . '</span></a>';
            echo '<div id="toggleDataCheckboxes" ' . $borderLeft . ' onClick="toggleDataCheckboxes(\'paraAnalysisGraph\');"><span class="faicon ' . ($arrow == 1 ? ' dropup_blue' : 'dropdown_blue') . '"></span> ' . $lang['srv_data_settings_checkboxes'] . '</div>';
            echo '</div>';

        }

        // Nastavitve tabele (checkboxi) - PODATKI
        if ($podstran == 'para_analysis_graph') {
            $arrow = (isset($_SESSION['sid_' . $this->sid]['paraAnalysisGraph_settings'])) ? $_SESSION['sid_' . $this->sid]['paraAnalysisGraph_settings'] : 0;
            echo '<div id="toggleDataCheckboxes" ' . $borderLeft . ' onClick="toggleDataCheckboxes(\'paraAnalysisGraph\');"><span class="faicon ' . ($arrow == 1 ? ' dropup_blue' : 'dropdown_blue') . '"></span> ' . $lang['srv_data_settings_checkboxes'] . '</div>';
        }

        // Info o uporabnih enotah
        if ($podstran == 'usable_resp') {
            echo '<div id="usable_info">';
            echo $lang['srv_usableResp_text'];
            echo '</div>';

            $borderLeft = ' class="borderLeft"';
        }

        // Nastavitve tabele za UPORABNOST
        if ($podstran == 'usable_resp') {
            $arrow = (isset($_SESSION['sid_' . $this->sid]['usabilityIcons_settings'])) ? $_SESSION['sid_' . $this->sid]['usabilityIcons_settings'] : 0;
            echo '<div id="toggleDataCheckboxes" ' . $borderLeft . ' onClick="toggleDataCheckboxes(\'usability\');"><span class="faicon ' . ($arrow == 1 ? ' dropup_blue' : 'dropdown_blue') . '"></span> ' . $lang['srv_data_settings_checkboxes'] . '</div>';
        }

        // Radio status (vsi, ustrezni...)
        if ($analiza || in_array($podstran, array('data', 'export', 'charts', 'analysis_creport', 'analysis_links', 'para_graph', 'reminder_tracking', 'heatmap'))) {
            echo '<div id="dataOnlyValid" ' . $borderLeft . '>';
            SurveyStatusProfiles::displayOnlyValidCheckbox();
            echo '</div>';
        }

        if ($podstran == 'reminder_tracking') {
            echo '<div id="additional_navigation">';

            // Link na porocila z recnum
            echo '<a href="index.php?anketa=' . $this->sid . '&a=reminder_tracking&m=recnum"><span>' . $lang['srv_reminder_tracking_report_recnum'] . '</span></a>';

            // Link na porocila s spremenljivkami
            echo '<a href="index.php?anketa=' . $this->sid . '&a=reminder_tracking&m=vars"><span>' . $lang['srv_reminder_tracking_report_vprasanja'] . '</span></a>';

            echo '</div>';
        }

        // Nastavitve na desni
        if ($analiza || in_array($podstran, array('data', 'export', 'charts', 'analysis_creport', 'analysis_links', 'dejanski_casi', 'para_analysis_graph', 'heatmap'))) {

            $active_filter = $this->filteredData($podstran);

            echo '<div id="analiza_right_options_holder">';

            if ($analiza || in_array($podstran, array('charts'))) {

                // Nastavitev stevila odgovorov (odprtih) - po novem prestavljeno ven
                echo '<div id="analiza_right_options3" class="spaceRight">';
                echo $lang['srv_analiza_defAnsCnt_short'] . ': ';
                echo '<select id="numOpenAnswers" name="numOpenAnswers" autocomplete="off" onChange="saveSingleProfileSetting(\'' . SurveyDataSettingProfiles::getCurentProfileId() . '\', \'numOpenAnswers\', this.value); return false;">';
                $lastElement = end(SurveyDataSettingProfiles::$textAnswersMore);
                $cp = SurveyDataSettingProfiles::GetCurentProfileData();
                foreach (SurveyDataSettingProfiles::$textAnswersMore AS $key => $values) {
                    echo '<option' . ((int)$cp['numOpenAnswers'] == $values ? ' selected="selected"' : '') . ' value="' . $values . '">';
                    if ($values != $lastElement) {
                        echo $values;
                    } else {
                        echo $lang['srv_all'];
                    }
                    echo '</option>';
                }
                echo '</select>';
                echo '</div>';

                // Nastavitve za filtre (po spr, zoom, statusi...)
                echo '<div title="' . $lang['settings'] . '" id="analiza_right_options2" class="spaceRight spaceLeft">';
                echo '<span id="filters_span2" class="faicon wheel_32 pointer icon-as_link"></span>';
                $this->displayAnalizaRightOptions2($podstran);
                echo '</div>';
            } 
            elseif ($podstran == 'data' || $podstran == 'export') {
                // Ikona za ponovno generiranje datoteke
                echo '<span title="' . $lang['srv_deleteSurveyDataFile_link'] . '" class="faicon refresh icon-as_link pointer spaceRight spaceLeft" onClick="changeColectDataStatus(); return false;"></span>';
            }

            echo '<div title="' . $lang['filters'] . '" id="analiza_right_options" '.(!$userAccess->checkUserAccess($what='filters') ? 'class="user_access_locked"' : '').'>';
            echo '<span id="filters_span" class="faicon filter pointer"></span>';
            $this->displayAnalizaRightOptions($podstran);
            echo '</div>';
            echo Help::display('srv_data_filter');

            echo '</div>';
        } 
        // Link na nastavitve komentarjev
        elseif ($podstran == 'komentarji' || $podstran == 'komentarji_anketa') {

            $d = new Dostop();

            # nastavitve komentarjev
            if ($d->checkDostopSub('edit')) {
                echo '<div id="analiza_right_options_holder">';
                echo '<div title="' . $lang['settings'] . '" id="analiza_right_options">';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=urejanje" title="' . $lang['srv_settings_komentarji1'] . '">';
                echo '<span class="faicon wheel_32 pointer icon-as_link"></span>';
                //echo '<span id="filters_span" class="bold pointer">'.$lang['settings'].'</span>';
                echo '</a>';
                echo '</div>';
                echo '</div>';
            }
        } 
        // Nastavitve statusa
        elseif ($podstran == 'status') {

            $ss = new SurveyStatistic();
            $ss->Init($this->sid);

            echo '<div id="div_status_filtri_right" class="floatRight">';

            echo '<div id="dashboardEmailInvitationFilter" style="position:absolute; right:20px; top:46px;">';
            echo $ss->emailInvitationFilter($this->emailInvitation);
            echo '</div>';

            echo '<ul>';
            # če imamo vabila
            $row = SurveyInfo::getSurveyRow();

            if ($row['email'] || $row['user_base']) {
                if ($ss->cnt_all == $ss->cnt_email) {
                    echo '<li>';
                    # filter za emaile
                    echo $lang['srv_statistic_email_invitation'];
                    echo '<select id="filter_email_status" name="filter_email_status" onchange="statisticRefreshAllBoxes(\'invitation\'); return false;" >';
                    echo '<option value="0" disabled="disabled">' . $lang['srv_statistic_email_invitation_all'] . '</option>';
                    echo '<option value="1" selected="selected">' . $lang['srv_statistic_email_invitation_only_email'] . '</option>';
                    echo '<option value="2" disabled="disabled">' . $lang['srv_statistic_email_invitation_no_email'] . '</option>';
                    echo '</select>';
                    echo '</li>';
                } else {
                    echo '<li>';
                    # filter za emaile
                    echo $lang['srv_statistic_email_invitation'];
                    echo '<select id="filter_email_status" name="filter_email_status" onchange="statisticRefreshAllBoxes(\'invitation\'); return false;" >';
                    echo '<option value="0"' . ($ss->emailInvitation == 0 ? ' selected="selected"' : '') . '>' . $lang['srv_statistic_email_invitation_all'] . '</option>';
                    echo '<option value="1"' . ($ss->emailInvitation == 1 ? ' selected="selected"' : '') . '>' . $lang['srv_statistic_email_invitation_only_email'] . '</option>';
                    echo '<option value="2"' . ($ss->emailInvitation == 2 ? ' selected="selected"' : '') . '>' . $lang['srv_statistic_email_invitation_no_email'] . '</option>';
                    echo '</select>';
                    echo '</li>';
                }
            }
            echo '<li>';
            # filter za čase
            $TimeProfileData = SurveyTimeProfiles:: GetDates();
            $separator = ($row['email'] || $row['user_base']) ? true : false;
            SurveyTimeProfiles::DisplayLink(false, $separator);
            echo '</li>';
            echo '</ul>';

            echo '</div>';
        }


        echo '</div>';
    }

    public function displayAnalizaSubNavigation($showDiv = true)
    {
        global $lang, $admin_type, $global_user_id;

        $_js_links = array();

        UserSetting:: getInstance()->Init($global_user_id);
        $show_analiza_preview = (int)UserSetting:: getInstance()->getUserSetting('showAnalizaPreview') == 1 ? true : false;
        if ($show_analiza_preview == true) {
            for ($i = 1; $i <= 9; $i++) {
                $_js_links[$i] = ' onmouseover="show_anl_prev(' . $i . '); return false;" onmouseout="hide_anl_prev(); return false"';
            }

        }
        if ($_GET['m'] != M_ANALYSIS_CHARTS && $_GET['m'] != M_ANALYSIS_LINKS && $_GET['m'] != M_ANALYSIS_CREPORT) {
            if (true) {
                echo '<span class="srv_statistic_menu">' . $lang['srv_statistic_menu'] . Help::display('srv_menu_statistic') . '&nbsp;</span>';
                echo '<div id="globalSetingsLinks" class="analiza">';
            }
            if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
                echo '<ul class="analizaSubNavigation">';
                echo '<li' . ($_GET['m'] == M_ANALYSIS_MEANS_HIERARHY ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[5] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_MEANS_HIERARHY . '" title="' . $lang['srv_means'] . '"><span>' . $lang['srv_means'] . '</span></a>';
                echo '</li>';
                echo '</ul>';

            } else {
                echo '<ul class="analizaSubNavigation">';
                echo '<li' . ($_GET['m'] == M_ANALYSIS_SUMMARY ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[1] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_SUMMARY . '" title="' . $lang['srv_sumarnik'] . '"><span>' . $lang['srv_sumarnik'] . '</span></a>';
                echo '</li>';
                # opisne
                echo '<li' . ($_GET['m'] == M_ANALYSIS_DESCRIPTOR ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[2] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_DESCRIPTOR . '" title="' . $lang['srv_descriptor'] . '"><span>' . $lang['srv_descriptor_short'] . '</span></a>';
                echo '</li>';
                # frekvence
                echo '<li' . ($_GET['m'] == M_ANALYSIS_FREQUENCY ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[3] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_FREQUENCY . '" title="' . $lang['srv_frequency'] . '"><span>' . $lang['srv_frequency'] . '</span></a>';
                echo '</li>';
                # crostabs
                echo '<li' . ($_GET['m'] == M_ANALYSIS_CROSSTAB ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[4] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_CROSSTAB . '" title="' . $lang['srv_crosstabs'] . '"><span>' . $lang['srv_crosstabs'] . '</span></a>';
                echo '</li>';
                # multicrostabs
                echo '<li' . ($_GET['m'] == M_ANALYSIS_MULTICROSSTABS ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[8] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_MULTICROSSTABS . '" title="' . $lang['srv_multicrosstabs'] . '"><span>' . $lang['srv_multicrosstabs'] . '</span></a>';
                echo '</li>';
                # povprečaj
                echo '<li' . ($_GET['m'] == M_ANALYSIS_MEANS ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[5] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_MEANS . '" title="' . $lang['srv_means'] . '"><span>' . $lang['srv_means'] . '</span></a>';
                echo '</li>';
                # ttest
                if ($admin_type == 0) {
                    echo '<li' . ($_GET['m'] == M_ANALYSIS_TTEST ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[6] . '>';
                    echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_TTEST . '" title="' . $lang['srv_ttest'] . '"><span>' . $lang['srv_ttest'] . '</span></a>';
                    echo '</li>';
                }
                # break
                echo '<li' . ($_GET['m'] == M_ANALYSIS_BREAK ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"') . $_js_links[7] . '>';
                echo '<a href="index.php?anketa=' . $this->sid . '&a=' . A_ANALYSIS . '&m=' . M_ANALYSIS_BREAK . '" title="' . $lang['srv_break'] . '"><span>' . $lang['srv_break'] . '</span></a>';
                echo '</li>';
                # para statistike - so pod status??
                /*if ($admin_type === '0') {

                    echo '<li'.($_GET['m']==M_ANALYSIS_PARA ? ' class="highlightLineTab"' : ' class="nonhighlight displayNone"').$_js_links[9].'>';
                    echo '<a href="index.php?anketa='.$this->sid.'&a='.A_ANALYSIS.'&m='.M_ANALYSIS_PARA.'" title="'.$lang['srv_para_neodgovori'].'"><span>'.$lang['srv_para_neodgovori'].'</span></a>';
                    echo '</li>';
                }	*/
                # predogled
                echo '<li class="previewCheck displayNone">';
                echo '<label><input type="checkbox" id="cbx_shoq_analiza_preview" onchange="change_analiza_preview();"' . ($show_analiza_preview == true ? ' checked=checked' : '') . '>' . $lang['srv_preview'] . '</label>';
                echo '</li>';
                echo '</ul>';
            }
            if ($showDiv) {
                echo '</div>';
            }
            ?>
            <script>
                function show_anl_prev(tip) {
                    $("#srv_analiza_preview_div").show();
                    //var tip = parseInt($(event.target).parent().attr('anl_prv'));


                    if (tip > 0) {
                        // 	skrijemo ostale previev-e
                        $('.srv_analiza_preview_sub').addClass('hidden');
                        // prikažemo ustrezen predogled
                        $('#srv_analiza_preview_sub_' + tip).removeClass('hidden');
                    }
                }
                function hide_anl_prev() {
                    $("#srv_analiza_preview_div").hide();
                }
                // mousever preview vprasanja
                //$('#globalSetingsLinks ul li a').bind('mouseover', function (event) {

                //}).bind('mouseout', function (event) {

                //});
            </script>
            <?php

        }
        $this->displayAnalizaPreview();
    }

    public function displayAnalizaRightOptions($podstran, $onlyLinks = false){
        global $lang, $admin_type, $global_user_id;

        $userAccess = UserAccess::getInstance($global_user_id);     

        $allowShow = array();

        #dovoljenja za prikaz določenih nastavitev
        $allowShow[M_ANALYSIS_SUMMARY] =
        $allowShow[M_ANALYSIS_DESCRIPTOR] =
        $allowShow[M_ANALYSIS_FREQUENCY] =
        $allowShow[M_ANALYSIS_CHARTS] =
        $allowShow[M_ANALYSIS_LINKS] =
        $allowShow[M_ANALYSIS_CREPORT] = array(
            'AS_SETTINGS',
            'AS_SEGMENTS',
            'AS_ZOOM',
            'AS_LOOPS',
            'AS_BREAK',
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');

        $allowShow[M_ANALYSIS_CROSSTAB] =
        $allowShow[M_ANALYSIS_MULTICROSSTABS] =
        $allowShow[M_ANALYSIS_MEANS_HIERARHY] =
        $allowShow[M_ANALYSIS_MEANS] = array(
            'AS_SETTINGS',
            'AS_ZOOM',
            'AS_LOOPS',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');
        $allowShow[M_ANALYSIS_TTEST] = array(
            'AS_SETTINGS',
            'AS_CONDITIONS',
            'AS_TIME',
            'AS_STATUS');
        $allowShow[M_ANALYSIS_BREAK] = array(
            'AS_SETTINGS',
            'AS_ZOOM',
            'AS_LOOPS',
            'AS_BREAK',
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');

        $allowShow[M_ANALYSIS_NONRESPONSES] =
        $allowShow[M_ANALYSIS_PARA] = array(
            'AS_SETTINGS',
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');

        $allowShow['para_analysis_graph'] = array(
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
        );

        session_start();
        $hideAdvanced = (isset($_SESSION['AnalysisAdvancedLinks'][$this->sid]) && $_SESSION['AnalysisAdvancedLinks'][$this->sid] == true) ? true : false;

        if ($podstran == 'data' || $podstran == 'export' || $podstran == 'quick_edit') {
            echo '<div id="div_analiza_filtri_right" class="floatRight">';
            echo '<ul>';

            if ($podstran == 'export') {
                echo '<li>';
                echo '<span class="as_link" id="link_export_setting" onClick="$(\'#fade\').fadeTo(\'slow\', 1);$(\'#div_export_setting_show\').fadeIn(\'slow\'); return false;" title="' . $lang['srv_dsp_link'] . '">' . $lang['srv_dsp_link'] . '</span>';
                echo '</li>';
            }

            # filter za nastavitve
            # div za filtre statusov
            SurveyStatusProfiles:: DisplayLink(false, false);

            # filter za spremenljivke - variable
            SurveyVariablesProfiles::DisplayLink(false, false);
            #filter za ife - pogoje
            SurveyConditionProfiles::DisplayLink(false);
            # filter za čase
            SurveyTimeProfiles::DisplayLink(false);
            # generiranje datoteke s podatki - dodana lastna ikona za generiranje datoteke
            //SurveyStatusProfiles :: FileGeneratingSetting(false);

            echo '</ul>';
            echo '</div>'; # id="div_analiza_filtri_right" class="floatRight"
        } 
        elseif ($podstran == 'dejanski_casi') {
            echo '<div id="div_analiza_filtri_right">';

            SurveyStatusCasi:: Init($this->sid);

            SurveyUserSetting:: getInstance()->Init($this->sid, $global_user_id);

            // nastavitve iz popupa
            $rezanje = SurveyUserSetting::getInstance()->getSettings('rezanje');
            if ($rezanje == '') $rezanje = 1;
            $rezanje_meja_sp = SurveyUserSetting::getInstance()->getSettings('rezanje_meja_sp');
            if ($rezanje_meja_sp == '') $rezanje_meja_sp = 5;
            $rezanje_meja_zg = SurveyUserSetting::getInstance()->getSettings('rezanje_meja_zg');
            if ($rezanje_meja_zg == '') $rezanje_meja_zg = 5;
            $rezanje_predvidena_sp = SurveyUserSetting::getInstance()->getSettings('rezanje_predvidena_sp');
            if ($rezanje_predvidena_sp == '') $rezanje_predvidena_sp = 10;
            $rezanje_predvidena_zg = SurveyUserSetting::getInstance()->getSettings('rezanje_predvidena_zg');
            if ($rezanje_predvidena_zg == '') $rezanje_predvidena_zg = 200;

            // profili rezanja
            $statusCasi = SurveyStatusCasi:: getProfiles();
            echo '<div>' . "\n";
            echo '<a href="#" onclick="vnosi_show_rezanje_casi(); return false;" id="link_rezanje_casi" title="' . $lang['srv_rezanje'] . '">' . $lang['srv_rezanje'] . '</a><br/>';

            echo '<span id="div_vnosi_status_profile_dropdownd" style="font-size:10px">';

            if ($rezanje == 0) {
                echo '(' . $lang['srv_rezanje_meja_sp'] . ': ' . $rezanje_meja_sp . '%, ' . $lang['srv_rezanje_meja_zg'] . ': ' . $rezanje_meja_zg . '%)';

            } else {
                echo '(' . $rezanje_predvidena_sp . '% ' . $lang['srv_and'] . ' ' . $rezanje_predvidena_zg . '% ' . $lang['srv_rezanje_predvidenega'] . ')';
            }

            echo '</span>';
            echo '</div>';

            // profili statusov
            $statusCasi = SurveyStatusCasi:: getProfiles();
            echo '<div style="margin-top: 15px;">';
            echo '<span class="as_link" id="link_status_casi" title="' . $lang['srv_statusi'] . '">' . $lang['srv_statusi'] . ': </span>';
            echo '<span id="div_vnosi_status_profile_dropdown">';
            echo '<select id="vnosi_current_status_casi" name="vnosi_current_status_casi" onchange="statusCasiAction(\'change\'); return false;" >';
            foreach ($statusCasi as $key => $value) {
                echo '		<option' . ($izbranStatusCasi == $value['id'] ? ' selected="selected"' : '') . ' value="' . $value['id'] . '">' . $value['name'] . '</option>';
            }
            echo '</select>';
            echo '</span>';
            echo '</div>';

            echo '</div>';
        } 
        else {
            if ($onlyLinks == false) {
                echo '<div id="div_analiza_filtri_right" class="analiza">';
            }

            echo '<ul>';

            if (in_array('AS_SEGMENTS', $allowShow[$podstran])) {
                # zoom
                SurveyZoom::DisplayLink($hideAdvanced);
            }
            if (in_array('AS_ZOOM', $allowShow[$podstran])) {
                # inspect
                $SI = new SurveyInspect($this->sid);
                $SI->DisplayLink($hideAdvanced);
            }
            if (in_array('AS_LOOPS', $allowShow[$podstran])) {
                # filter za zanke
                SurveyZankaProfiles::DisplayLink($hideAdvanced);
            }
            if (in_array('AS_VARIABLES', $allowShow[$podstran])) {
                # div za profile variabel
                SurveyVariablesProfiles::DisplayLink(true, $hideAdvanced);
            }
            if (in_array('AS_CONDITIONS', $allowShow[$podstran])) {
                # filter za  pogoje - ifi
                SurveyConditionProfiles::DisplayLink($hideAdvanced);
            }
            if (in_array('AS_MISSINGS', $allowShow[$podstran])) {
                # profili missingov
                SurveyMissingProfiles::DisplayLink($hideAdvanced);
            }
            if (in_array('AS_TIME', $allowShow[$podstran])) {
                # filter za čase
                SurveyTimeProfiles::DisplayLink($hideAdvanced);
            }
            if (in_array('AS_STATUS', $allowShow[$podstran])) {
                # div za filtre statusov
                SurveyStatusProfiles::DisplayLink($hideAdvanced);
            }
            echo '</ul>';

            if ($onlyLinks == false) {
                echo '</div>';
            }
        }

        // Javascript s katerim povozimo urlje za izvoze, ki niso na voljo v paketu
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess($what='filters')){
            echo '<script> userAccessFilters(); </script>';
        }
    }

    public function displayAnalizaRightOptions2($podstran, $onlyLinks = false)
    {
        global $lang, $admin_type, $global_user_id;

        $allowShow = array();

        #dovoljenja za prikaz določenih nastavitev
        $allowShow[M_ANALYSIS_SUMMARY] =
        $allowShow[M_ANALYSIS_DESCRIPTOR] =
        $allowShow[M_ANALYSIS_FREQUENCY] =
        $allowShow[M_ANALYSIS_CHARTS] =
        $allowShow[M_ANALYSIS_LINKS] =
        $allowShow[M_ANALYSIS_CREPORT] = array(
            'AS_SETTINGS',
            'AS_SEGMENTS',
            'AS_ZOOM',
            'AS_LOOPS',
            'AS_BREAK',
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');

        $allowShow[M_ANALYSIS_CROSSTAB] =
        $allowShow[M_ANALYSIS_MULTICROSSTABS] =
        $allowShow[M_ANALYSIS_MEANS_HIERARHY] =
        $allowShow[M_ANALYSIS_MEANS] = array(
            'AS_SETTINGS',
            'AS_ZOOM',
            'AS_LOOPS',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');
        $allowShow[M_ANALYSIS_TTEST] = array(
            'AS_SETTINGS',
            'AS_CONDITIONS',
            'AS_TIME',
            'AS_STATUS');
        $allowShow[M_ANALYSIS_BREAK] = array(
            'AS_SETTINGS',
            'AS_ZOOM',
            'AS_LOOPS',
            'AS_BREAK',
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');

        $allowShow[M_ANALYSIS_NONRESPONSES] =
        $allowShow[M_ANALYSIS_PARA] = array(
            'AS_SETTINGS',
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
            'AS_TIME',
            'AS_STATUS');

        $allowShow['para_analysis_graph'] = array(
            'AS_VARIABLES',
            'AS_CONDITIONS',
            'AS_MISSINGS',
        );

        session_start();
        $hideAdvanced = (isset($_SESSION['AnalysisAdvancedLinks'][$this->sid]) && $_SESSION['AnalysisAdvancedLinks'][$this->sid] == true) ? true : false;

        if ($onlyLinks == false) {
            echo '<div id="div_analiza_filtri_right2" class="analiza">';
        }

        echo '<ul>';

        if ($podstran == 'charts') {
            // nastavitve za grafe (hq, barva)
            $this->displayChartOptions();
        }

        if (in_array('AS_SETTINGS', $allowShow[$podstran])) {

            # filter za nastavitve
            SurveyDataSettingProfiles::DisplayLink($hideAdvanced);
        }

        echo '</ul>';

        if ($onlyLinks == false) {
            echo '</div>';
        }
    }

    public function displayChartOptions()
    {
        global $lang, $admin_type;

        // Nastavitev HQ grafov
        echo '<li><label>';
        echo $lang['srv_chart_hq'] . ': ';
        echo '<input type="checkbox" name="chart_hq" id="chart_hq" onClick="changeChartHq(this)" ' . (SurveyChart::$quality == 3 ? ' checked="checked"' : '') . '>';
        echo '</label></li>';


        // Nastavitev skina grafov
        $skin = SurveyUserSetting:: getInstance()->getSettings('default_chart_profile_skin');
        $skin = isset($skin) ? $skin : '1ka';

        // ce je custom skin
        if (is_numeric($skin)) {
            $skin = SurveyChart::getCustomSkin($skin);
            $name = $skin['name'];
        } else {
            switch ($skin) {
                // 1ka skin
                case '1ka':
                    $name = $lang['srv_chart_skin_1ka'];
                    break;

                // zivahen skin
                case 'lively':
                    $name = $lang['srv_chart_skin_0'];
                    break;

                // blag skin
                case 'mild':
                    $name = $lang['srv_chart_skin_1'];
                    break;

                // Office skin
                case 'office':
                    $name = $lang['srv_chart_skin_6'];
                    break;

                // Pastel skin
                case 'pastel':
                    $name = $lang['srv_chart_skin_7'];
                    break;

                // zelen skin
                case 'green':
                    $name = $lang['srv_chart_skin_2'];
                    break;

                // moder skin
                case 'blue':
                    $name = $lang['srv_chart_skin_3'];
                    break;

                // rdeč skin
                case 'red':
                    $name = $lang['srv_chart_skin_4'];
                    break;

                // skin za vec kot 5 moznosti
                case 'multi':
                    $name = $lang['srv_chart_skin_5'];
                    break;
            }
        }

        if ($hideAdvanced == false) {

            echo '<li>';
            echo '<span class="as_link" id="link_chart_color" title="' . $lang['srv_chart_skin'] . '">' . $lang['srv_chart_skin'] . ': <span style="font-weight: 500;">' . $name . '</span></span>';
            echo '</li>';
        }

        // Separator
        /*echo '<li style="border-bottom:1px #0C377A dashed;">';
        echo '</li>';
        echo '<li>';
        echo '</li>';*/
    }


    // Ugotovimo ce so podatki kako filtrirani
    function filteredData($podstran)
    {

        if ($podstran == 'status') {

            if (SurveyTimeProfiles::getCurentProfileId() != STP_DEFAULT_PROFILE)
                return true;
        } else if (in_array($podstran, array('sumarnik', 'descriptor', 'frequency', 'crosstabs', 'ttest', 'means', 'break', 'multicrosstabs', 'nonresponses'))) {

            if (SurveyDataSettingProfiles::getCurentProfileId() != SDS_DEFAULT_PROFILE)
                return true;

            if (SurveyZoom::getCurentProfileId() != 0 && $podstran != 'status')
                return true;

            $SI = new SurveyInspect($this->sid);
            if ($SI->isInspectEnabled() && $podstran != 'status')
                return true;

            if (SurveyVariablesProfiles::getCurentProfileId() != SVP_DEFAULT_PROFILE)
                return true;

            if (SurveyConditionProfiles::getCurentProfileId() != SCP_DEFAULT_PROFILE)
                return true;

            if (SurveyMissingProfiles::getCurentProfileId() != SMP_DEFAULT_PROFILE)
                return true;

            if (SurveyTimeProfiles::getCurentProfileId() != STP_DEFAULT_PROFILE)
                return true;

            if (SurveyStatusProfiles::getCurentProfileId() != SSP_DEFAULT_PROFILE)
                return true;
        } else if (in_array($podstran, array('data', 'export', 'quick_edit'))) {

            $SPM = new SurveyProfileManager($this->sid);
            if ($SPM->getCurentProfileId() != SSP_DEFAULT_PROFILE && (int)$SPM->getCurentProfileId() != 0 && (int)$SPM->getCurentProfileId() != -1)
                return true;

            if (SurveyVariablesProfiles::getCurentProfileId() != SVP_DEFAULT_PROFILE)
                return true;

            if (SurveyConditionProfiles::getCurentProfileId() != SCP_DEFAULT_PROFILE)
                return true;

            if (SurveyTimeProfiles::getCurentProfileId() != STP_DEFAULT_PROFILE)
                return true;

            if (SurveyStatusProfiles::getCurentProfileId() != SSP_DEFAULT_PROFILE)
                return true;
        }

        return false;
    }
}

?>