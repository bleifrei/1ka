<?php

ob_start();

session_start();

include_once 'definition.php';
include_once '../../function.php';
include_once '../../vendor/autoload.php';

# error reporting
if (isDebug()){
#	error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', '1');
}

Common::start();

sisplet_query("BEGIN");

global $global_user_id;

$surveySkin = 0;


// Naložimo jezikovno datoteko
$anketa = $_REQUEST['anketa'];
$lang_admin = 0;
if ($anketa > 0) {
	$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
	$row = @mysqli_fetch_array($sql);
	$lang_admin = $row['lang_admin'];
}
if ($lang_admin == 0) {
	$sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
	$row = @mysqli_fetch_array($sql);
	$lang_admin = $row['lang'];
}
if ($lang_admin == 0) {
	$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
	$row = @mysqli_fetch_array($sql);
	$lang_admin = $row['value'];
}
if ($lang_admin == 0) $lang_admin = 2; // za vsak slucaj, ce ni v bazi

$file = '../../lang/'.$lang_admin.'.php';
include($file);
$_SESSION['langX'] = $site_url .'lang/'.$lang_admin.'.php';


// preverimo dostop
$result = sisplet_query ("SELECT value FROM misc WHERE what='SurveyDostop'");
list ($SurveyDostop) = mysqli_fetch_row ($result);

if ( (( ($admin_type <= $SurveyDostop && $SurveyDostop<3) || ($SurveyDostop==3) ) && ($admin_type>=0)) || $_GET['a']=='comment_manage' ) {
	// ok, bo slo naprej...
} 
else {
	header ('location: ' .$site_url .'admin/survey/index.php');
	die();
}


// Tracking za katero skupino funkcionalnosti gre (urejanje ankete, podatki, analize...)
$tracking_status = -1;


/**************** UREJANJE ****************/
if ($_GET['t'] == 'branching') {
    $tracking_status = 0;

	$b = new BranchingAjax($anketa);
	$b->ajax();
} 
elseif ($_GET['t'] == 'quota') {
    $tracking_status = 0;

	$SQ = new SurveyQuotas($anketa);
	$SQ->ajax();  
} 
elseif ($_GET['t'] == 'vprasanje') {
    $tracking_status = 0;

	$v = new Vprasanje();
	$v->ajax();
} 
elseif ($_GET['t'] == 'vprasanjeinline') {
    $tracking_status = 0;

	$v = new VprasanjeInline();
	$v->ajax();
} 
elseif ($_GET['t'] == 'prevajanje') {
    $tracking_status = 0;

	$p = new Prevajanje();
	$p->ajax();	
} 
elseif ($_GET['t'] == 'glasovanje') {
    $tracking_status = 0;

	$gl = new Glasovanje($anketa);
	$gl->ajax(); 	
} 
elseif ($_GET['t'] == 'missingValues') {
    $tracking_status = 0;

	$smv = new SurveyMissingValues($anketa);
	$smv->ajax();
} 
elseif ($_GET['t'] == 'quiz') {
    $tracking_status = 0;

	$sq = new SurveyQuiz($anketa);
	$sq -> ajax();
} 
elseif ($_GET['t'] == 'theme') {
    $tracking_status = 0;

	$diplayIframe = ($_POST['a'] == 'previewThemeIframe' ? false : true);		
	$st = new SurveyTheme($anketa,$diplayIframe); 
	$st->Ajax();
} 
elseif ($_GET['t'] == 'heatmapRadij') {
    $tracking_status = 0;

	$hmr = new SurveyHeatMapRadij();
	$hmr->ajax() ;
} 
elseif ($_GET['t'] == 'getheatmapradij') {
    $tracking_status = 0;

	$hmgr = new SurveyGetHeatMapRadij();
	$hmgr->ajax() ;
}
elseif ($_GET['t'] == 'getheatmapexporticons') {
    $tracking_status = 0;
    
	$hmei = new SurveyHeatMapExportIcons();
	$hmei->ajax() ;
}
elseif ($_GET['t'] == 'saveHeatmapImage') {
    
        $tracking_status = 0;
	$hmis = new SurveyHeatMapImageSave();
	$hmis->ajax() ;	

} 
elseif ($_GET['t'] == 'themeEditor') {
    $tracking_status = 0;

	$te = new SurveyThemeEditor($anketa, true);
	$te->ajax();
	
} 
elseif ($_GET['t'] == 'changeSurveyLock') {
    $tracking_status = 0;

	if (isset($_POST['name']) && $_POST['name'] == 'lockSurvey') {
		$locked = (int)$_POST['value'];
		$lockSurvey = sisplet_query("UPDATE srv_anketa SET locked='$locked' WHERE id = '$anketa'");
		UserSetting :: getInstance()->Init($global_user_id);
		UserSetting::getInstance()->setUserSetting('lockSurvey', $locked);
		UserSetting::getInstance()->saveUserSetting();
    }
    
	$sas = new SurveyAdminSettings();
	$sas->showLockSurvey();
} 
elseif ($_GET['t'] == 'SurveyConnect') {
    $tracking_status = 0;

	$sc = new SurveyConnect();
	$sc->ajax();
} 
elseif ($_GET['t'] == 'surveyCondition') {
    $tracking_status = 0;

	$scp = new SurveyCondition($anketa);
	$scp->ajax();
} 
elseif($_GET['t'] == 'checboxChangeTheme'){
    $tracking_status = 0;

    $checkbox = new SurveyTheme($anketa);
    $checkbox->Ajax();
} 
elseif($_GET['t'] == 'gdpr'){
    $tracking_status = 0;

	$gdpr = new GDPR();
	$gdpr->ajax();
} 
elseif ($_GET['t'] == 'skupine') {
    $tracking_status = 0;

	$ss = new SurveySkupine($anketa);
	$ss->ajax();
} 
/**************** UREJANJE - END ****************/


/**************** ANALIZE ****************/	
elseif ($_GET['t'] == A_ANALYSIS) {
    $tracking_status = 2;

	$a = new SurveyAnalysis();
	$a->Init($anketa);
	$a->ajax();    
} 
elseif ($_GET['t'] == 'crosstab') {
    $tracking_status = 2;

	$sc = new SurveyCrosstabs();
	$sc -> Init($anketa);
	$sc -> ajax();
} 
elseif ($_GET['t'] == 'multicrosstabs') {
    $tracking_status = 2;

    $smc = new SurveyMultiCrosstabs($anketa);
	$smc -> ajax();
} 
elseif ($_GET['t'] == 'means') {
    $tracking_status = 2;

	$sm = new SurveyMeans($anketa);
	$sm -> ajax();
}
elseif ($_GET['t'] == 'ttest') {
    $tracking_status = 2;

	$stt = new SurveyTTest($anketa);
	$stt -> ajax();
} 
elseif ($_GET['t'] == 'table_chart') {
    $tracking_status = 2;

	$stc = new SurveyTableChart($anketa);
	$stc -> ajax();
} 
elseif ($_GET['t'] == 'charts') {
    $tracking_status = 2;

	$sc = new SurveyChart();
	$sc -> Init($anketa);
	$sc -> ajax();
} 
elseif ($_GET['t'] == 'zoom') {
    $tracking_status = 2;

	$sz = new SurveyZoom($anketa);
	$sz -> ajax();  
}
elseif ($_GET['t'] == 'break') {
    $tracking_status = 2;

	$sb = new SurveyBreak($anketa);
	$sb->ajax() ;
} 
elseif($_GET['t'] == 'analysisGorenje'){
    $tracking_status = 2;

	$SAG = new SurveyAnalysisGorenje($anketa);
	$SAG->ajax();
} 
elseif ($_GET['t'] == 'ParaAnalysis') {
    $tracking_status = 2;

	$spa = new SurveyParaAnalysis($anketa);
	$spa->ajax();
}
elseif ($_GET['t'] == 'custom_report') {
    $tracking_status = 2;
    
	$SCR = new SurveyCustomReport($anketa);
	$SCR -> ajax();
}	
/**************** ANALIZE - END ****************/


/**************** STATUS ****************/
elseif ($_GET['t'] == 'dashboard') {
    $tracking_status = 3;

	$ss = new SurveyStatistic();
	$ss -> Init($anketa);
	$ss -> ajax();
} 
/**************** STATUS - END ****************/


/**************** PODATKI ****************/
elseif ($_GET['t'] == 'postprocess') {
    $tracking_status = 4;

	$spp = new SurveyPostProcess($anketa);
	$spp->ajax();
} 
elseif ($_GET['t'] == 'advanced_paradata') {
    $tracking_status = 4;

	$sq = new SurveyAdvancedParadata($anketa);
	$sq -> ajax();
} 
elseif ($_GET['t'] == 'recode') {
    $tracking_status = 4;

	$SR = new SurveyRecoding($anketa);
	$SR -> Ajax();
} 
elseif ($_GET['t'] == 'displayData') {
    $tracking_status = 4;

	$dd = new SurveyDataDisplay($anketa);
	$dd->ajax() ;
} 
elseif ($_GET['t'] == 'dataFile') {
    $tracking_status = 4;
    
    $SDF = SurveyDataFile::get_instance();
    $SDF->init($anketa);
    $SDF->ajax();
} 
elseif ($_GET['t'] == 'mapData') {
    $tracking_status = 4;

	$md = new SurveyMapData();
	$md->mapData() ; 
} 
elseif ($_GET['t'] == 'mapDataAll') {
    $tracking_status = 4;

    $md = new SurveyMapData();
    $md->mapDataAll() ;
} 
elseif ($_GET['t'] == 'heatmapData') {
    $tracking_status = 4;

	$hmd = new SurveyHeatMap();
	$hmd->ajax() ;
} 
elseif ($_GET['t'] == 'heatmapBackgroundData') {
    $tracking_status = 4;

	$hmbd = new SurveyHeatMapBackground();
	$hmbd->ajax() ;
} 
elseif ($_GET['t'] == 'setDataView') {
    $tracking_status = 4;

	if ($_POST['what'] != null && trim($_POST['what']) != '' && is_string($_POST['what'])){
		
		# v nastavitev data_view_settings dodamo podnastavitev
		SurveySession::sessionStart($anketa);
		SurveySession::append('data_view_settings',$_POST['what'],$_POST['value']);
		#bolše bi bilo z user sessionom
		
		// Ce nastavljamo stevilo vprasanj ali stevilo spremenljivk resetiramo tudi stran na 1
		if($_POST['what'] == 'spr_limit')
			SurveySession::append('data_view_settings','spr_page','1');
		elseif($_POST['what'] == 'rec_on_page')	
			SurveySession::append('data_view_settings','cur_rec_page','1');
	}
} 
elseif ($_GET['t'] == 'surveyUsableResp') {
    $tracking_status = 4;

	$sur = new SurveyUsableResp($anketa);
	$sur->ajax();
} 
elseif($_GET['t'] == 'aaporCalculation'){
    $tracking_status = 4;

	$ss = new SurveyStatistic();
    $ss -> Init($anketa);

    if(!isset($_GET['m']))
        $ss->DisplayAaporFullCalculation();
    else if(isset($_GET['m']) && $_GET['m'] == 'priblizek'){
        $ss->DisplayAaporPriblizek();
    }
}
elseif ($_GET['t'] == 'appendMerge') {
    $tracking_status = 4;

	$am = new SurveyAppendMerge($anketa);
	$am->ajax();
} 
/**************** PODATKKI - END ****************/


/**************** OBJAVA ****************/
elseif ($_GET['t'] == 'telephone') {
    $tracking_status = 5;

    $tp = new SurveyTelephone($anketa);
    $tp->ajax();
} 
elseif ($_GET['t'] == 'invitations') {
    $tracking_status = 5;

	$SIN = new SurveyInvitationsNew($anketa);
	$SIN -> ajax();
} 
elseif ($_GET['t'] == 'notifications') {
    $tracking_status = 5;

	$NO = new Notifications();
	$NO->ajax();			
} 
elseif ($_GET['t'] == 'simpleMailInvitation') {
    $tracking_status = 5;

	$SSMI = new SurveySimpleMailInvitation($anketa);
	$SSMI -> ajax();
} 
elseif ($_GET['t'] == 'surveyBaseSetting') {
    $tracking_status = 5;

	$SBS = new SurveyBaseSetting($anketa);
	$SBS -> ajax();
}
elseif ($_GET['t'] == 'getSiteUrl') {
    $tracking_status = 5;

	$hmii = new GetSiteUrl();
	$hmii->ajax() ;	
} 
elseif($_GET['t'] == 'WPN'){
    if($_GET['a'] == 'wpn_send_notification'){
        $tracking_status = 5;

        $WPN = new WPN($anketa);
        $WPN -> sendWebPushNotificationsToAll();
    }
} 
elseif ($_GET['t'] == 'SurveyUrlLinks') {
    $tracking_status = 5;

	$sul = new SurveyUrlLinks($anketa);
	$sul->ajax();
}	
/**************** OBJAVA - END ****************/

        
/**************** HIERARHIJA ****************/  
elseif ($_GET['t'] == 'hierarhy-means') {
    $tracking_status = 6;

	$sm = new HierarhijaAnalysis($anketa);
	$sm -> ajax();
        
} 
elseif($_GET['t'] == 'hierarhija-ajax'){
    $tracking_status = 6;

	$hierarhija = new \Hierarhija\HierarhijaAjax($anketa);
	$hierarhija->ajax();
} 
elseif ($_GET['t'] == 'sa-uporabniki') {
    $tracking_status = 6;

	if(!class_exists('Hierarhija\Ajax\AjaxHierarhijaDostopUporabnikovClass'))
		return redirect('/admin/survey/');

	$hierarhija_dostop = new Hierarhija\Ajax\AjaxHierarhijaDostopUporabnikovClass();

	if($_GET['a'] == 'add') {
		$hierarhija_dostop->save();
    }
    elseif($_GET['a'] == 'check'){
        $hierarhija_dostop->checkUserEmail();
    }
    elseif($_GET['a'] == 'delete'){
		$hierarhija_dostop->delete();
    }
    elseif($_GET['a'] == 'edit') {
		$user_id = (!empty($_POST['id']) ? $_POST['id'] : null);
		$hierarhija_dostop->popupNew($user_id);
    }
    elseif($_GET['a'] == 'update') {
		$hierarhija_dostop->update();
    }
    elseif($_GET['a'] == 'show') {
		$hierarhija_dostop->show();
    }
    else {
		$hierarhija_dostop->popupNew();
	}
}
/**************** HIERARHIJA - END ****************/


/**************** UPORABNIK ****************/
elseif ($_GET['t'] == 'surveyList') {
	$SL = new SurveyList();
	$SL->Ajax();    
} 
elseif ($_GET['t'] == 'library') {
    $l = new Library();
    $l->ajax();
} 
elseif ($_GET['t'] == 'help') {
    $h = new Help();
    $h->ajax();
} 
elseif ($_GET['t'] == 'globalUserSettings') {
	$sas = new SurveyAdminSettings();
	$sas->setGlobalUserSetting();
} 
elseif($_GET['t'] == 'userAccess'){
    $ua = UserAccess::getInstance($global_user_id);
	$ua->ajax();
} 
elseif($_GET['t'] == 'userNarocila'){
    $UN = new UserNarocila();
    $UN->ajax();
} 
elseif($_GET['t'] == 'userPlacila'){
    $UP = new UserPlacila();
    $UP->ajax();
} 
elseif ($_GET['t'] == 'newSurvey') {
	$ns = new NewSurvey();
	$ns->ajax();
} 
elseif ($_GET['a'] == 'user_tracking') {
	if($_GET['d'] == 'download'){
                return UserTrackingClass::init()->csvExport();
        }
} 
/**************** UPORABNIK - END ****************/


/**************** UNKNOWN, NAPREDNI MODULI ****************/
elseif ($_GET['t'] == 'profileManager') {
    $tracking_status = -1;

    $spm = new SurveyProfileManager();
    $spm->ajax();
} 
elseif ($_GET['t'] == 'SurveyReminderTracking') {
    $tracking_status = -1;

	$sur = new SurveyReminderTracking($anketa);
	$sur->ajax();
} 
elseif ($_GET['t'] == 'inspect') {
    $tracking_status = -1;

	$SI = new SurveyInspect($anketa);
	$SI -> ajax();
} 
elseif ($_GET['t'] == 'dostop') {
    $tracking_status = -1;

	$d = new Dostop();
	$d->ajax();
} 
elseif ($_GET['t'] == 'missingProfiles') {
    $tracking_status = -1;

	$smp = new SurveyMissingProfiles();
	$smp->Init($anketa);
	$smp->ajax();
} 
elseif ($_GET['t'] == 'statusProfile') {
    $tracking_status = -1;

	$ssp = new SurveyStatusProfiles();
	$ssp -> Init($anketa);
	$ssp->ajax();
} 
elseif ($_GET['t'] == 'timeProfile') {
    global $global_user_id;
    
    $tracking_status = -1;

	$tp = new SurveyTimeProfiles();
	$tp -> Init($anketa,$global_user_id);
	$tp -> ajax();
} 
elseif ($_GET['t'] == 'dataSettingProfile') {
    global $global_user_id;
    
    $tracking_status = -1;

	$dsp = new SurveyDataSettingProfiles();
	$dsp -> Init($anketa,$global_user_id);
	$dsp -> ajax();
} 
elseif ($_GET['t'] == 'variableProfile') {
    $tracking_status = -1;

	$svp = new SurveyVariablesProfiles();
	$svp -> Init($anketa,$global_user_id);
	$svp->ajax();
} 
elseif ($_GET['t'] == 'export') {
    $tracking_status = -1;

	$se = new SurveyExport();
	$se -> Init($anketa);
	$se -> ajax();
} 
elseif ($_GET['t'] == 'conditionProfile') {
    $tracking_status = -1;

	$scp = new SurveyConditionProfiles();
	$scp -> Init($anketa,$global_user_id);
	$scp -> ajax();
} 
elseif ($_GET['t'] == 'zankaProfile') {
    $tracking_status = -1;

	$szp = new SurveyZankaProfiles();
	$szp -> Init($anketa,$global_user_id);
	$szp -> ajax();
} 
elseif ($_GET['t'] == 'slideshow') {
    $tracking_status = -1;

	$ss = new SurveySlideshow($anketa);
	$ss -> ajax();
} 
elseif ($_GET['t'] == 'chat') {
    $tracking_status = -1;

	$sc = new SurveyChat($anketa);
	$sc -> ajax();
} 
elseif ($_GET['t'] == 'panel') {
    $tracking_status = -1;

	$sp = new SurveyPanel($anketa);
	$sp -> ajax();
}
elseif ($_GET['t'] == 'showTestSurveySMTP') {
    $tracking_status = -1;

	$sas = new SurveyAdminSettings();
	$sas->ajax_showTestSurveySMTP();
} 
elseif ($_GET['t'] == 'evalvacija') {
    $tracking_status = -1;

	// UL EVALVACIJA
	$eval = new Evalvacija($anketa);
	$eval->ajax();
} 
elseif ($_GET['t'] == 'evoliTM') {
    $tracking_status = -1;

	// Evoli TeamMeter
	$evoliTM = new SurveyTeamMeter($anketa);
	$evoliTM->ajax();
} 
/**************** UNKNOWN, NAPREDNI MODULI - END ****************/


/**************** MAZA ****************/
elseif ($_GET['t'] == 'MAZA') {

    if(isset($_GET['a'])){

        $anketa = $anketa;

        if($_GET['a'] == 'maza_send_notification'){
            $tracking_status = 5;
            $maza = new MAZA($anketa);
            $maza->ajax_sendNotification();
        }

        if($_GET['a'] == 'maza_send_notification_pwa'){
            $tracking_status = 5;
            $WPN = new WPN($anketa);
            $WPN -> sendWebPushNotificationsToAll();
        }
        else if($_GET['a'] == 'maza_on_off'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_maza_on_off();
        }
        else if($_GET['a'] == 'maza_cancel_alarm'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_maza_cancel_alarm();
        }
        else if($_GET['a'] == 'maza_generate_users'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_maza_generate_users();
        }
        else if($_GET['a'] == 'maza_survey_description'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_maza_survey_description();
        }
        else if($_GET['a'] == 'changeRepeatBy'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_changeRepeatBy();
        }
        else if($_GET['a'] == 'changeTimeInDay'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_changeTimeInDay();
        }
        else if($_GET['a'] == 'changeDayInWeek'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_changeDayInWeek();
        }
        else if($_GET['a'] == 'changeEveryWhichDay'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_changeEveryWhichDay();
        }
        else if($_GET['a'] == 'maza_save_repeater'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_saveRepeater();
        }
        else if($_GET['a'] == 'cancelRepeater'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_cancel_repeater();
        }
        else if($_GET['a'] == 'insert_geofence'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_insert_geofence();
        }
        else if($_GET['a'] == 'update_geofence'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_update_geofence();
        }
        else if($_GET['a'] == 'update_geofence_name'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_update_geofence_name();
        }
        else if($_GET['a'] == 'delete_geofence'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_delete_geofence();
        }
        else if($_GET['a'] == 'get_all_geofences'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->ajax_get_all_geofences();
        }
        else if($_GET['a'] == 'maza_cancel_geofencing'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_cancel_geofencing();
        }
        else if($_GET['a'] == 'maza_run_geofences'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_run_geofences();
        }
        else if($_GET['a'] == 'maza_cancel_entry'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_cancel_entry();
        }
        else if($_GET['a'] == 'maza_run_entry'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_run_entry();
        }
        else if($_GET['a'] == 'maza_run_activity'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_run_activity();
        }
        else if($_GET['a'] == 'maza_cancel_activity'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_cancel_activity();
        }
        else if($_GET['a'] == 'maza_run_tracking'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_run_tracking();
        }
        else if($_GET['a'] == 'maza_cancel_tracking'){
            $tracking_status = 0;
            $maza = new MAZA($anketa);
            $maza->maza_cancel_tracking();
        }
    }
} 
/**************** MAZA -END ****************/


/**************** DEFAULT ****************/
else {
    $tracking_status = -1;
	$s = new SurveyAdminAjax();
	$s->ajax();
}


// Shranimo tracking
if($anketa != null && $anketa > 0){
    TrackingClass::update($anketa, $status);
}
//nismo vezani na anketo, tracking uporabnika
else{
    TrackingClass::update_user();
}


// izpisemo buffer pred zapisovanjem vSSPVUCT tracking in commitom (da je kao hitrejse)
ob_flush();

Common::stop();

Common::checkStruktura();

// TODO neko globalno preverjanje za errorje. da se ob napaki naredi rollback in ohranimo konsistentnost
if (true)
	sisplet_query("COMMIT");
else
	sisplet_query("ROLLBACK");


?>
