<?php 
/** Za hendlanje "javnih" povezav do podatkov...
 * 
 */
ob_start();
header('Cache-Control: no-cache');
header('Pragma: no-cache');

include_once 'definition.php';

include_once '../../function.php';


include_once('../../vendor/autoload.php');


global $admin_type;	// tip admina: 0:admin, 1:manager, 2:clan, 3- user
global $global_user_id;
global $mysql_database_name;
global $pass_salt;
global $is_meta;
global $cookie_domain;
global $lang;
global $site_url;

if ($admin_type < 0) {
	$admin_type = 3;
}
if ((int)$global_user_id == 0) {
	// očitno zadeva ne deluje če je $global_user_id == 0; 
	$global_user_id = -1;
}

$anketa = (int)$_GET['anketa'];
$hash = $_GET['urlhash'];


// Nastavimo ustrezen jezik (da je enak kot jezik za urejanje ankete)
$lang_admin = 0;
if ($anketa > 0) {
	$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
	$row = @mysqli_fetch_array($sql);
	$lang_admin = $row['lang_admin'];
}
if ($lang_admin == 0) {
	//$sql = sisplet_query("SELECT * FROM misc WHERE what = 'SurveyLang_admin'");
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

// Naložimo jezikovno datoteko
$file = '../../lang/'.$lang_admin.'.php';
include($file);
$_SESSION['langX'] = $site_url .'lang/'.$lang_admin.'.php';


if ((int)$anketa > 0){ 

	$hashUrl = new HashUrl($anketa);
	
	if ($hashUrl->hashExists($hash)) {
            $properties = $hashUrl -> getProperties($hash);
            $anketa = $properties['anketa'];

            //refresh every 15s if set
            if($hashUrl -> IsHashRefresh($hash)){
                header('Refresh: 15; URL="'.$site_url.'podatki/'.$anketa.'/'.$hash.'/' );
            }
            
            //check if access password is set and set session values
            if($hashUrl -> IsHashAccessPass($hash)){
                session_start();
                $hashUrl -> checkHashlinkAccessSessionValues($hash);
            }
            
            //access password not needed or access already granted
            if(!$hashUrl -> IsHashAccessPass($hash) || ($hashUrl -> IsHashAccessPass($hash) && isset($_SESSION['hashlink_access'][$hash]) && $_SESSION['hashlink_access'][$hash] == '1')){
                if (isset($properties['a'])) {
                    $_GET['a'] = $properties['a'];
                }

                        $action = $properties['a'];
                if (isset($properties['m'])) {
                    $_GET['m'] = $properties['m'];

                    if ($_GET['m'] == M_ANALYSIS_CHARTS) {
                        $action = M_ANALYSIS_CHARTS;
                    }

                                if ($_GET['m'] == M_ANALYSIS_CREPORT) {
                        $action = M_ANALYSIS_CREPORT;
                    }
                }

                    $podstran = $properties['m'];

                    switch ($action) {			
                case 'data':
                    $sd = new SurveyDataDisplay($anketa);
                    $sd::displayPublicData($properties);
                break;

                case M_ANALYSIS_CHARTS:
                    $sc = new SurveyChart();
                    $sc::Init($anketa);
                    $sc::$publicChart = true;
                    $sc::displayPublicChart($properties);
                break;

                            case M_ANALYSIS_CREPORT:
                    $scr = new SurveyCustomReport($anketa);
                    $scr->setUpIsForPublic(true);
                    $scr->displayPublicCReport($properties);
                break;

                            case 'analysis':
                                    $sda = new SurveyAnalysis();
                                    $sda::Init($anketa);
                                    $sda::$publicAnalyse = true;
                                    $sda::displayPublicAnalysis($properties);
                            break;

                            default:
                                            echo 'Error!';
                            break;
                    }
            }
            //access password needed
            else {
                $hashUrl -> HashlinkAccessPasswordForm($hash);
            }
        }
	// Ajax - moramo nastavit userja kot avtorja, drugace ne izvede ajaxa
	elseif($_GET['a'] == 'get_variable_labels' || $_GET['a'] == 'getDataStatusTitles'){
		
		// id nastavimo na avtorja da se ajax ustrezno izvede do konca
		$sql = sisplet_query("SELECT insert_uid FROM srv_anketa WHERE id='$anketa'");
		$row = mysqli_fetch_assoc($sql);
		$global_user_id = $row['insert_uid'];
		
		$s = new SurveyAdminAjax($action=-1);
		$s->ajax();
	}
	else {
		echo $lang['srv_urlLinks_invalid_hash'];
	}
}
else {
	echo $lang['srv_urlLinks_invalid_sid'];
}

ob_end_flush();
