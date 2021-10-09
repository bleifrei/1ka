<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CrossRoad
 *
 * @author podkrizniku
 */

class CrossRoad {
    static function MainNavigation($anketa=null){
        $status = -1;
        # ajaxa se poslje skupaj z ajaxom, da ob updatu vemo kaksen 'a' je bil na originalni strani
        # (drugace se ob updatu z ajaxom informacija o 'a'ju zgubi)
        $get = $_GET['a'];
        if (isset ($_GET['ajaxa']))
            $get = $_GET['ajaxa'];
        if (trim($get) == '') {
            $get = A_BRANCHING;
        }
        
        //smo vezani na anketo
        if($anketa != null && $anketa > 0){
            switch ($get) {
                # status
                case A_REPORTI:
                    $first_action = NAVI_STATUS;
                    $second_action = NAVI_STATUS_OSNOVNI;
                    $status = 3;
                    break;
                case A_NONRESPONSE_GRAPH:
                case A_USABLE_RESP:
                case A_SPEEDER_INDEX:
                case A_TEXT_ANALYSIS:
                case A_GEOIP_LOCATION:
                case A_EDITS_ANALYSIS:
                case A_REMINDER_TRACKING:
                case A_UL_EVALVATION:
                case A_PARA_GRAPH:
                case 'langStatistic':
                case 'AAPOR':
                    $first_action = NAVI_STATUS;
                    $second_action = NAVI_STATUS_OSNOVNI;
                    $status = 4;
                    break;

                # urejanje
                case A_BRANCHING:
                case A_GLASOVANJE:
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_BRANCHING;
                    $status = 0;
                    break;

                case A_TESTIRANJE:
                    $first_action = NAVI_TESTIRANJE;
                    $second_action = M_TESTIRANJE_DIAGNOSTIKA;
                    if ($_GET['m'] == M_TESTIRANJE_VNOSI) {
                        $second_action = NAVI_TESTIRANJE_VNOSI;
                    }
                    if ($_GET['m'] == M_TESTIRANJE_PREDVIDENI) {
                        $second_action = NAVI_TESTIRANJE_PREDVIDENI;
                    }
                    if ($_GET['m'] == M_TESTIRANJE_CAS) {
                        $second_action = NAVI_TESTIRANJE_CAS;
                    }
                    $status = 4;
                    break;

                case A_KOMENTARJI:
                case A_KOMENTARJI_ANKETA:
                    $first_action = NAVI_TESTIRANJE;
                    $second_action = NAVI_TESTIRANJE_KOMENTARJI;
                    $status = 0;
                    break;

                case A_SETTINGS:
                case A_OSNOVNI_PODATKI:
                case A_FORMA:

                case A_COOKIE:
                case A_TRAJANJE:
                case A_DOSTOP:
                case A_MISSING:
                case A_METADATA:
                case A_MOBILESETTINGS:
                case A_JEZIK: # nastavitve jezik
                case A_UREJANJE: # nastavitve komentarjev
                case A_PRIKAZ: # nastavitve komentarjev
                case A_SKUPINE:
                case A_EXPORTSETTINGS:
                case A_GDPR:
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_ANKETA;
                    $status = 0;
                    break;

                case A_TEMA: # nastavitve prevajanje
                case 'theme-editor': # nastavitve prevajanje
                case 'edit_css': # nastavitve prevajanje
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_TEMA;
                    $status = 0;
                    break;

                case A_HIERARHIJA:
                    $first_action = NAVI_HIERARHIJA;
                    break;

                case A_PREVAJANJE: # nastavitve prevajanje
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_PREVAJANJE;
                    $status = 0;
                    break;

                case A_ALERT:
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_ANKETA;
                    $status = 0;
                    break;

                case A_NAGOVORI:
                case A_ARHIVI:
                case A_TRACKING:
                    $first_action = NAVI_UREJANJE;
                    $status = 0;
                    break;

                # objave, vabila
                case A_VABILA:
                    $first_action = NAVI_OBJAVA;
                    $status = 5;
                    break;

                case A_EMAIL:
                    $first_action = NAVI_OBJAVA;
                    $second_action = NAVI_OBJAVA;
                    $status = 5;
                    break;

                case 'invitations':
                    $first_action = NAVI_OBJAVA;
                    $second_action = 'invitations';
                    $status = 5;
                    break;

                # analize, podatki
                case A_ANALYSIS:
                    $first_action = NAVI_ANALYSIS;

                    $second_action = NAVI_STATISTIC_ANALYSIS;
                    if ($_GET['m'] == M_ANALYSIS_LINKS) {
                        $second_action = NAVI_ANALYSIS_LINKS;
                    }
                    $status = 2;
                    break;

                case A_COLLECT_DATA:
                    $first_action = NAVI_RESULTS;
                    $second_action = NAVI_DATA;
                    $status = 4;
                    break;

                #izvozi
                case A_COLLECT_DATA_EXPORT:
                    $first_action = NAVI_RESULTS;
                    $second_action = NAVI_DATA_EXPORT;
                    $status = 4;
                    if ($_GET['m'] == A_COLLECT_DATA_EXPORT_ALL) {
                        $first_action = NAVI_RESULTS;
                        $second_action = NAVI_ANALYSIS_LINKS;
                        $status = 2;
                    }
                    break;

                # dodatne nastavitve
                case A_ADVANCED:
                case A_UPORABNOST:
                case A_HIERARHIJA_SUPERADMIN:
                case A_KVIZ:
                case A_ADVANCED_PARADATA:
                case A_JSON_SURVEY_EXPORT:
                case A_VNOS:
                case A_SOCIAL_NETWORK:
                case A_CHAT:
                case A_PANEL:
                case A_SLIDESHOW:
                case A_360:
                case A_360_1KA:
                case A_MAZA:
                case A_WPN:
                case 'evoli':
                case 'evoli_teammeter':
                case 'evoli_quality_climate':
                case 'evoli_teamship_meter':
                case 'evoli_organizational_employeeship_meter':
                case 'evoli_employmeter':
                case 'mfdps':
                case 'borza':
                case 'mju':
                case 'excell_matrix':
                case 'fieldwork':
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_ANKETA;
                    $status = 0;
                    break;

                case A_TELEPHONE:
                case A_PHONE:
                case T_PHONE:
                    $first_action = NAVI_UREJANJE;
                    $second_action = NAVI_UREJANJE_ANKETA;
                    $status = 5;
                    break;

                case A_LANGUAGE_TECHNOLOGY:
                    $first_action = NAVI_TESTIRANJE;
                    $second_action = NAVI_TESTIRANJE_LANGUAGE_TECHNOLOGY;
                    $status = 4;
                    break;

                case A_LANGUAGE_TECHNOLOGY_OLD:
                    $first_action = NAVI_TESTIRANJE;
                    $second_action = NAVI_TESTIRANJE_LANGUAGE_TECHNOLOGY_OLD;
                    $status = 4;
                    break;

                default:
                    break;
            }

            //shrani tracking
            TrackingClass::update($anketa, $status);
            //vrni podatke o navigaciji nazaj v SurveyAdmin
            return array('first_action' => $first_action, 'second_action' => $second_action);   
        }
        //nismo vezani na anketo, tracking uporabnika
        else{
            TrackingClass::update_user();
        }
        
        
    }
}
