<?php

//set_time_limit(1800);

include_once('definition.php');
include_once('../../function.php');
include_once('../../vendor/autoload.php');

global $site_path, $global_user_id, $admin_type, $lang;


# error reporting
if(isDebug()){
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', '1');
}
else{
	//error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
	error_reporting(0);
	ini_set('display_errors', '0');
}


/****** DEFINITIONS ******/
define("M_ANALIZA_DESCRIPTOR", "descriptor");
define("M_ANALIZA_FREQUENCY", "frequency");
define("M_ANALIZA_CROSSTAB", "crosstabs");
define("M_ANALIZA_STATISTICS", "statistics");
define("M_ANALIZA_SUMS", "sums");

/*PDF*/
define("A_REPORT_VPRASALNIK_PDF", "vprasalnik_pdf");
define("A_REPORT_PDF_RESULTS","pdf_results");
define("A_REPORT_PDF_COMMENT","pdf_comment");
define("M_REPORT_ANALIZA_PDF_FREKVENCA","frequency");
define("M_REPORT_ANALIZA_PDF_CROSSTAB_IZPIS","crosstabs_izpis");
define("M_REPORT_ANALIZA_PDF_MULTICROSSTAB_IZPIS","multicrosstabs_izpis");
define("M_REPORT_ANALIZA_PDF_MEAN_IZPIS","mean_izpis");
define("M_REPORT_ANALIZA_PDF_TTEST_IZPIS","ttest_izpis");
define("M_REPORT_ANALIZA_PDF_BREAK_IZPIS","break_izpis");
define("M_REPORT_ANALIZA_PDF_STAT","statistics");
define("M_REPORT_ANALIZA_PDF_CHARTS","charts");
define("M_REPORT_ANALIZA_PDF_SUMS","sums");
define("M_REPORT_ANALIZA_PDF_CREPORT","creport_pdf");
define("A_REPORT_PDF_STATUS","status");
define("A_REPORT_PDF_EDITS_ANALYSIS","editsAnalysis");
define("A_REPORT_PDF_LIST","list_pdf");
define("M_REPORT_PDF_EVOLI","pdf_evoli");
define("M_REPORT_PDF_TEAMMETER","pdf_teammeter");
define("M_REPORT_PDF_EMPLOYMETER","pdf_employmeter");
define("M_REPORT_PDF_MFDPS","pdf_mfpds");
define("M_REPORT_PDF_MJU","pdf_mju");
define("M_REPORT_PDF_MJU2","pdf_mju2");
define("M_REPORT_PDF_NIJZ","pdf_nijz");
define("M_REPORT_BORZA","borza_chart");
define("M_REPORT_PDF_HEATMAP_IMAGE","heatmap_image_pdf");
define("M_REPORT_HIERARHIJA_PDF_IZPIS", "hierarhija_pdf_izpis");

/*RTF*/
define("A_REPORT_VPRASALNIK_RTF", "vprasalnik_rtf");
define("A_REPORT_RTF_RESULTS", "rtf_results");
define("A_REPORT_RTF_COMMENT","rtf_comment");
define("M_REPORT_ANALIZA_RTF_FREKVENCA", "frequency_rtf");
define("M_REPORT_ANALIZA_RTF_CROSSTAB_IZPIS", "crosstabs_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_MULTICROSSTAB_IZPIS", "multicrosstabs_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_MEAN_IZPIS", "mean_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_TTEST_IZPIS", "ttest_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_BREAK_IZPIS", "break_izpis_rtf");
define("M_REPORT_ANALIZA_RTF_STAT", "statistics_rtf");
define("M_REPORT_ANALIZA_RTF_SUMS", "sums_rtf");
define("M_REPORT_ANALIZA_RTF_CHARTS", "charts_rtf");
define("M_REPORT_ANALIZA_RTF_CREPORT", "creport_rtf");
define("A_REPORT_RTF_LIST", "list_rtf");
define("M_REPORT_ANALIZA_RTF_HEATMAP_IMAGE","heatmap_image_rtf");

/*XLS*/
define("M_REPORT_ANALIZA_XLS_STAT", "statistics_xls");
define("M_REPORT_ANALIZA_XLS_FREKVENCA", "frequency_xls");
define("M_REPORT_ANALIZA_XLS_CROSSTAB_IZPIS", "crosstabs_izpis_xls");
define("M_REPORT_ANALIZA_XLS_MULTICROSSTAB_IZPIS", "multicrosstabs_izpis_xls");
define("M_REPORT_ANALIZA_XLS_SUMS", "sums_xls");
define("M_REPORT_ANALIZA_XLS_MEAN_IZPIS", "mean_izpis_xls");
define("M_REPORT_ANALIZA_XLS_TTEST_IZPIS", "ttest_izpis_xls");
define("M_REPORT_ANALIZA_XLS_BREAK_IZPIS", "break_izpis_xls");
define("A_REPORT_XLS_LIST", "list_xls");
define("A_REPORT_XLS_USABLE", "usable_xls");
define("A_REPORT_XLS_SPEEDER", "speeder_xls");
define("A_REPORT_XLS_TEXT_ANALYSIS", "text_analysis_xls");
define("A_REPORT_CSV_TEXT_ANALYSIS", "text_analysis_csv");
define("M_REPORT_CSV_MAZA_USERS", "maza_csv");
define("M_REPORT_CSV_ADVANCED_PARADATA", "advanced_paradata_csv");

/*JSON*/
define("M_REPORT_JSON_SURVEY_EXPORT", "json_survey");

define("A_LANGUAGE_TECHNOLOGY_XLS", "lt_excel");

/*PPT*/
define("M_REPORT_ANALIZA_PPT_CHARTS", "charts_ppt");
define("M_REPORT_ANALIZA_PPT_HEATMAP_IMAGE","heatmap_image_ppt");

/*IMAGE*/
define("M_REPORT_ANALIZA_HEATMAP_IMAGE", "heatmap_image");



# če pridemo iz rekodiranga urlja
if (!isset($_GET['anketa']) && isset($_GET['dc']) && $_GET['dc'] != null) {
	$data = $_GET['dc'];
	$data = base64_decode($data);
	$data = unserialize($data);
	foreach ($data AS $get_param => $value) {
		if ($get_param != null && $get_param != '' && $value != null && $value != '' && $value != 'undefined') {
			$_GET[$get_param] = $value;
		}
	}
}

$anketa = $_GET['anketa'];

# PDF lahko vidijo vsi, ki kliknejo na link za kreacijo PDF-ja
# zato, kadar uporabnik ni logiran v CMS za pdf nastavimo $global_user_id kateri je enak avtorju ankete
if ($global_user_id === null || $global_user_id === 0) {
	$sql = sisplet_query("SELECT insert_uid FROM srv_anketa WHERE id='$anketa'");
	$row = mysqli_fetch_assoc($sql);
	$global_user_id = $row['insert_uid'];
}

SurveyInfo::SurveyInit($anketa);
$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
// nastavimo jezik
$file = '../../lang/'.$lang_admin.'.php';
include($file);



/****** PREVERIMO CE IZVAJAMO NOV ALI STAR EXPORT ******/
// GDPR so vedno novi izvozi
if(in_array($_GET['a'], array('pdf_gdpr_individual', 'pdf_gdpr_activity', 'rtf_gdpr_individual', 'rtf_gdpr_activity'))){
    $export_type = 'new';
}
// Posebna porocila, ki so vedno stari izvozi
elseif(in_array($_GET['m'], array('pdf_teammeter','pdf_employmeter', 'pdf_evoli', 'maza_csv', 'borza_csv', 'pdf_mju', 'pdf_mju2', 'advanced_paradata_csv', 'json_survey', 'pdf_nijz'))){
    $export_type = 'old';
}
// Ce imamo vklopljeno nastavitev za nove izvoze
elseif(AppSettings::getInstance()->getSetting('app_settings-export_type') == 'new'){
    $export_type = 'new';
}
// Ce imamo vklopljeno nastavitev za nove izvoze samo za admine
elseif(AppSettings::getInstance()->getSetting('app_settings-export_type') == 'new_admin' && $admin_type == 0){ 
    $export_type = 'new';
}
else{
    $export_type = 'old';
}
    

/****** IZVEDEMO NOV EXPORT ******/
if($export_type == 'new'){

    //error_reporting(E_ALL ^ E_NOTICE);
    //ini_set('display_errors', '1');

    ini_set('memory_limit', '1024M');
    ini_set('max_input_time', 480);
    ini_set('max_input_time', 8000);

	$export = new ExportController();
	$export->executeExport();

	die();
}


/****** IZVEDEMO STAR EXPORT ******/
# uporabnik ima dostop do ankete, preverimo še ali lahko pogleda userja
switch ( $_GET['a'] ) {
	case A_REPORT_VPRASALNIK_PDF:
		include($site_path.'admin/exportclases/class.pdfIzvoz.php');	
		
		$type = ($_GET['type'] == 1) ? 1 : 0;
		$izvoz = new PdfIzvoz ($_GET['anketa'], $type);
		$izvoz->setDisplayFrontPage(true);
		$izvoz->createPdf();
		
		$izvoz->getFile('pdf_vprasalnik'.time().'.pdf');
		break;

	case A_REPORT_VPRASALNIK_RTF:
		include($site_path.'admin/exportclases/class.rtfIzvoz.php');
		
		$type = ($_GET['type'] == 1) ? 1 : 0;
		$izvoz = new RtfIzvoz ($_GET['anketa'], $type);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_vprasalnik.rtf');
		break;
	
	case A_REPORT_RTF_RESULTS:
		include($site_path.'admin/exportclases/class.rtfIzvozResults.php');
		//include($site_path.'admin/exportclases/class.rtfIzvoz.php');
		//$izvoz = new RtfIzvozResults ($_GET['anketa']);			
		$izvoz = new RtfIzvozResults ($_GET['anketa'], $_GET['pdf_usr_type'], $_GET['pdf_usr_id']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
		break;
	
	case A_REPORT_PDF_RESULTS:
		include ($site_path.'admin/exportclases/class.pdfIzvozResults.php');
		//include($site_path.'admin/exportclases/class.pdfIzvoz.php');
		//$izvoz = new PdfIzvozResults ($_GET['anketa']);
		$izvoz = new PdfIzvozResults ($_GET['anketa'], $_GET['pdf_usr_type'], $_GET['pdf_usr_id']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;
			
	case A_REPORT_PDF_COMMENT:
		include($site_path.'admin/exportclases/class.pdfIzvoz.php');
		
		$commentType = (isset($_GET['only_unresolved'])) ? $_GET['only_unresolved'] : 1;
		$izvoz = new PdfIzvoz ($_GET['anketa'], $allResults=2, $commentType);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;
			
	case A_REPORT_RTF_COMMENT:
		include($site_path.'admin/exportclases/class.rtfIzvoz.php');
		
		$commentType = (isset($_GET['only_unresolved'])) ? $_GET['only_unresolved'] : 1;
		$izvoz = new RtfIzvoz ($_GET['anketa'], $allResults=2, $commentType);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;
			
	case A_REPORT_PDF_STATUS:		
		//poberemo tabelo s podatki
		$ssData = explode(",", $_GET['data']);
		
		include ($site_path.'admin/exportclases/class.pdfIzvozStatus.php');
		$izvoz = new PdfIzvozStatus ($_GET['anketa'], $ssData);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_status'.time().'.pdf');
			break;
                    
        case A_REPORT_PDF_EDITS_ANALYSIS:
		include ($site_path.'admin/exportclases/class.pdfIzvozEditsAnalysis.php');
		$izvoz = new PdfIzvozEditsAnalysis ($_GET['anketa'], $ssData);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_EditsAnalysis'.time().'.pdf');
			break;
			
	case A_REPORT_PDF_LIST:			
		include ($site_path.'admin/exportclases/class.pdfIzvozList.php');
		$izvoz = new PdfIzvozList ($_GET['anketa']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_status'.time().'.pdf');
			break;
			
	case A_REPORT_RTF_LIST:					
		include ($site_path.'admin/exportclases/class.rtfIzvozList.php');
		$izvoz = new RtfIzvozList ($_GET['anketa']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;
			
	case A_REPORT_XLS_LIST:					
		include ($site_path.'admin/exportclases/class.xlsIzvozList.php');
		$izvoz = new XlsIzvozList ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza.xls');
			break;
	//////////////XLS//////////////////////
	case A_LANGUAGE_TECHNOLOGY_XLS:

		global $site_path;
		try {
			
				
			$folder = $site_path . EXPORT_FOLDER.'/';
			$filename = $folder . "lt_" . $_GET['file'] . '.xlsx';
			if (!file_exists($filename)) {
				die("ERROR! Can't locate exported file!");
			}

			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Length: ' . filesize($filename));
			header('Content-Disposition: attachment; filename="languageTechnology.xlsx"');
			readfile($filename);
		} catch (Exception $e) {
			print_r("ERROR! Can't download exported file!");
		}
		if (file_exists($filename)) {		
			unlink($filename);
		}
		break;
		
}
	
switch ( $_GET['m'] ) {

	//////////////////HEATMAP IMAGE/////////
	case M_REPORT_ANALIZA_HEATMAP_IMAGE:
			include ($site_path.'admin/exportclases/class.imageIzvozHeatmap.php');	
			$izvoz = new imageIzvozHeatmap ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
			$izvoz->getFile('heatmap'.$_GET['sprID'].'.png');
		break;
	
	////////////////////////////////////////
	
	//////////////////EVOLI/////////////////
	case M_REPORT_PDF_EVOLI:
	
        $usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0; 
        $anketa = (isset($_GET['anketa']) && $_GET['anketa'] > 0) ? $_GET['anketa'] : 0; 
        
		$evoli = new SurveyEvoli($anketa);
		$evoli->executeExport($usr_id);
			break;
			
	case M_REPORT_PDF_TEAMMETER:
	
		$skupina = (isset($_GET['skupina']) && $_GET['skupina'] > 0) ? $_GET['skupina'] : 0; 
		$anketa = (isset($_GET['ank_id']) && $_GET['ank_id'] > 0) ? $_GET['ank_id'] : 0; 
		$anketa = ($anketa == 0 && isset($_GET['anketa'])) ? $_GET['anketa'] : $anketa; 
		
		$evoli_tm = new SurveyTeamMeter($anketa);
		$evoli_tm->executeExport($skupina);
            break;
            
    case M_REPORT_PDF_EMPLOYMETER:

        $usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0;
        $anketa = (isset($_GET['anketa']) && $_GET['anketa'] > 0) ? $_GET['anketa'] : 0; 

        $evoli_em = new SurveyEmployMeter($anketa);
        $evoli_em->executeExport($usr_id);
            break;
	/////////////////////////////////////
			
	//////////////////MJU/////////////////
	case M_REPORT_PDF_MJU:
		
		$type = (isset($_GET['type'])) ? $_GET['type'] : 1;
		$enota = (isset($_GET['enota'])) ? $_GET['enota'] : 0;
		
		$sme = new SurveyMJUEnote($_GET['anketa']);
		$sme->executeExport($type, $enota);
			break;
    /////////////////////////////////////
    
    //////////////////MJU2/////////////////
	case M_REPORT_PDF_MJU2:
		
		$type = (isset($_GET['type'])) ? $_GET['type'] : 1;
		$enota = (isset($_GET['enota'])) ? $_GET['enota'] : 0;
		
		$sme = new SurveyMJUEnote($_GET['anketa']);
		$sme->executeExport2($type, $enota);
			break;
	/////////////////////////////////////

    //////////////////NIJZ/////////////////
	case M_REPORT_PDF_NIJZ:
		
		$usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0;
        $anketa = (isset($_GET['anketa']) && $_GET['anketa'] > 0) ? $_GET['anketa'] : 0; 

        $nijz = new SurveyNIJZ($anketa, $usr_id, $nijz_type='2');
        $nijz->createReport2();
			break;
	/////////////////////////////////////
			
	//////////////////MFDPS/////////////////
	case M_REPORT_PDF_MFDPS:
	
		$usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0; 
		$mfdps = new SurveyMFDPS($_GET['anketa']);
		$mfdps->executePDFExport($usr_id);
			break;
	/////////////////////////////////////
	
	//////////////////BORZA/////////////////
	case M_REPORT_BORZA:
	
		$usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0; 
		$borza = new SurveyBORZA($_GET['anketa']);
		$borza->executeChartExport($usr_id);
			break;
	/////////////////////////////////////


    ///////////// HIERARHIJA /////////////
    case M_REPORT_HIERARHIJA_PDF_IZPIS:
        include ($site_path.'admin/survey/modules/mod_hierarhija/class/HierarhijaIzvozAnalize.php');
        $izvoz = new \Hierarhija\HierarhijaIzvozAnalize($_GET['anketa']);
        $izvoz->createPdf();

        $izvoz->getFile('pdf_analiza1'.time().'.pdf');
        break;
    /////////////////////////////////////

	//////////////////PDF/////////////////
	case M_REPORT_ANALIZA_PDF_FREKVENCA:
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaFrekvenca.php');
		$izvoz = new PdfIzvozAnalizaFrekvenca ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza.pdf');
			break;	
	
	case M_REPORT_ANALIZA_PDF_CROSSTAB_IZPIS:
		
		//poberemo tabelo s podatki
		$crossData1 = explode(",", $_GET['data1']);
		$crossData2 = explode(",", $_GET['data2']);
		
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaCrosstab.php');
		$izvoz = new PdfIzvozAnalizaCrosstab ($_GET['anketa'], $crossData1, $crossData2);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;	
			
	case M_REPORT_ANALIZA_PDF_MULTICROSSTAB_IZPIS:
					
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaMultiCrosstab.php');
		$izvoz = new PdfIzvozAnalizaMultiCrosstab($_GET['anketa']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;
			
	case M_REPORT_ANALIZA_PDF_MEAN_IZPIS:
		
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaMean.php');
		$izvoz = new PdfIzvozAnalizaMean ($_GET['anketa']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;
			
	case M_REPORT_ANALIZA_PDF_TTEST_IZPIS:

		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaTTest.php');
		$izvoz = new PdfIzvozAnalizaTTest ($_GET['anketa']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;
			
	case M_REPORT_ANALIZA_PDF_BREAK_IZPIS:

		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaBreak.php');
		$izvoz = new PdfIzvozAnalizaBreak ($_GET['anketa']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza'.time().'.pdf');
			break;
			
	case M_REPORT_ANALIZA_PDF_STAT:
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaOpisne.php');
		$izvoz = new PdfIzvozAnalizaOpisne ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza.pdf');
			break;	

	case M_REPORT_ANALIZA_PDF_SUMS:
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaSums.php');
		$izvoz = new PdfIzvozAnalizaSums ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza.pdf');
			break;
			
	case M_REPORT_ANALIZA_PDF_CHARTS:
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaCharts.php');
		$izvoz = new PdfIzvozAnalizaCharts ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza.pdf');
			break;
			
	case M_REPORT_ANALIZA_PDF_CREPORT:
		include ($site_path.'admin/exportclases/class.pdfIzvozAnalizaCReport.php');
		$izvoz = new PdfIzvozAnalizaCReport ($_GET['anketa']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_analiza.pdf');
			break;
			
	case M_REPORT_PDF_HEATMAP_IMAGE:
		include ($site_path.'admin/exportclases/class.pdfIzvozHeatmapImage.php');		
		$izvoz = new PdfIzvozHeatmapImage ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPdf();

		$izvoz->getFile('pdf_heatmap_image_'.$_GET['sprID'].'.pdf');
		break;
	
	/////////////////////////////////////		
	
	
	/////////////////RTF//////////////////
	case M_REPORT_ANALIZA_RTF_STAT:
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaOpisne.php');
		$izvoz = new RtfIzvozAnalizaOpisne ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_SUMS:
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaSums.php');
		$izvoz = new RtfIzvozAnalizaSums ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;
	
	case M_REPORT_ANALIZA_RTF_FREKVENCA:
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaFrekvenca.php');
		$izvoz = new RtfIzvozAnalizaFrekvenca ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;	
	
	case M_REPORT_ANALIZA_RTF_CROSSTAB_IZPIS:
		//poberemo tabelo s podatki
		$crossData1 = explode(",", $_GET['data1']);
		$crossData2 = explode(",", $_GET['data2']);
		
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaCrosstab.php');
		$izvoz = new RtfIzvozAnalizaCrosstab ($_GET['anketa'], $crossData1, $crossData2);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza'.time().'.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_MULTICROSSTAB_IZPIS:
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaMultiCrosstab.php');
		$izvoz = new RtfIzvozAnalizaMultiCrosstab ($_GET['anketa']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza'.time().'.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_MEAN_IZPIS:
		
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaMean.php');
		$izvoz = new RtfIzvozAnalizaMean ($_GET['anketa']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza'.time().'.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_TTEST_IZPIS:
		
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaTTest.php');
		$izvoz = new RtfIzvozAnalizaTTest ($_GET['anketa']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza'.time().'.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_BREAK_IZPIS:
		
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaBreak.php');
		$izvoz = new RtfIzvozAnalizaBreak ($_GET['anketa']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza'.time().'.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_CHARTS:
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaCharts.php');
		$izvoz = new RtfIzvozAnalizaCharts ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_CREPORT:
		include ($site_path.'admin/exportclases/class.rtfIzvozAnalizaCReport.php');
		$izvoz = new RtfIzvozAnalizaCReport ($_GET['anketa']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza.rtf');
			break;
			
	case M_REPORT_ANALIZA_RTF_HEATMAP_IMAGE:
		include ($site_path.'admin/exportclases/class.rtfIzvozHeatmapImage.php');
		$izvoz = new rtfIzvozHeatmapImage ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createRtf();

		$izvoz->getFile('rtf_analiza_heatmap_image_'.$_GET['sprID'].'.rtf');
			break;
	/////////////////////////////////////////
	
	
	/////////////////XLS/////////////////////
	case M_REPORT_ANALIZA_XLS_FREKVENCA:
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaFrekvenca.php');
		$izvoz = new XlsIzvozAnalizaFrekvenca ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza.xls');
			break;	
	
	case M_REPORT_ANALIZA_XLS_CROSSTAB_IZPIS:
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaCrosstab.php');

		//poberemo tabelo s podatki
		$crossData1 = explode(",", $_GET['data1']);
		$crossData2 = explode(",", $_GET['data2']);
		
		$izvoz = new XlsIzvozAnalizaCrosstab ($_GET['anketa'], $crossData1, $crossData2);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza.xls');
			break;
			
	case M_REPORT_ANALIZA_XLS_MULTICROSSTAB_IZPIS:
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaMultiCrosstab.php');
		$izvoz = new XlsIzvozAnalizaMultiCrosstab ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza.xls');
			break;
	
	case M_REPORT_ANALIZA_XLS_STAT:
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaStatistics.php');
		$izvoz = new XlsIzvozAnalizaStatistics ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza.xls');
			break;
	
	case M_REPORT_ANALIZA_XLS_SUMS:
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaSums.php');
		$izvoz = new XlsIzvozAnalizaSums ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza.xls');
			break;
			
	case M_REPORT_ANALIZA_XLS_MEAN_IZPIS:
		
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaMean.php');
		$izvoz = new XlsIzvozAnalizaMean ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza'.time().'.xls');
			break;
			
	case M_REPORT_ANALIZA_XLS_TTEST_IZPIS:
		
		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaTTest.php');
		$izvoz = new XlsIzvozAnalizaTTest ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza'.time().'.xls');
			break;
			
	case M_REPORT_ANALIZA_XLS_BREAK_IZPIS:

		include ($site_path.'admin/exportclases/class.xlsIzvozAnalizaBreak.php');
		$izvoz = new XlsIzvozAnalizaBreak ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_analiza'.time().'.xls');
			break;
	
	case A_REPORT_XLS_USABLE:

		include ($site_path.'admin/exportclases/class.xlsIzvozUsable.php');
		$izvoz = new XlsIzvozUsable ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_usable'.time().'.xls');
			break;
			
	case A_REPORT_XLS_SPEEDER:

		include ($site_path.'admin/exportclases/class.xlsIzvozSpeeder.php');
		$izvoz = new XlsIzvozSpeeder ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_speeder'.time().'.xls');
			break;
	
	case A_REPORT_XLS_TEXT_ANALYSIS:

		include ($site_path.'admin/exportclases/class.xlsIzvozTextAnalysis.php');
		$izvoz = new XlsIzvozTextAnalysis ($_GET['anketa']);
		$izvoz->createXls();

		$izvoz->getFile('xls_text_analysis'.time().'.xls');
			break;		
		
	case A_REPORT_CSV_TEXT_ANALYSIS:

		//include ($site_path.'admin/survey/classes/class.SurveyTextAnalysis.php');
		
		$STA = new SurveyTextAnalysis($_GET['anketa']);
		$STA->exportCSVTable($_GET['type']);
			break;		
                    
    case M_REPORT_CSV_MAZA_USERS:
		
		//include ($site_path.'admin/survey/modules/mod_MAZA/class.MAZAExport.php');
		
		$ME = new MAZAExport($_GET['anketa']);
		switch($_GET['a']){
			case 'ident_export':
				$ME->exportCSVIdentifiers();
				break;
                        /*case 'inactive_identifiers':
				$ME->exportCSVInactiveIdentifiers();
				break;
			case 'active_identifiers':
				$ME->exportCSVActiveIdentifiers();
				break;*/
			case 'tracking_locations':
				$ME->exportCSVTrackingLocations();
				break;
			case 'tracking_ar':
				$ME->exportCSVTrackingAR();
				break;
                        case 'entry_locations':
				$ME->exportCSVEntryLocations();
				break;
                        case 'triggered_geofences':
                                $ME->exportCSVTriggeredGeofences();
                                break;
                        case 'triggered_geofences_answers':
                                $ME->exportCSVTriggeredGeofencesAnswered();
                                break;
                        case 'geofences':
                                $ME->exportCSVGeofences();
                                break;
                        case 'alarm_respondents':
                                $ME->exportCSVAlarmRespondets();
                                break;
		}
		break;
	
	case M_REPORT_CSV_ADVANCED_PARADATA:

		if(isset($_GET['table'])){
			$table = $_GET['table'];

			$sape = new SurveyAdvancedParadataExport($_GET['anketa']);
			$sape->exportTable($table);
		}
		break;			
	//////////////////////////////////////////
	
	
	/////////////////JSON/////////////////////
 	case M_REPORT_JSON_SURVEY_EXPORT:
		$sjse = new SurveyJsonSurveyData($_GET['anketa']);
		$sjse->OutputJsonFile();
		break;	
	//////////////////////////////////////////
	
	
	/////////////////PPT//////////////////////
	case M_REPORT_ANALIZA_PPT_CHARTS:
		include ($site_path.'admin/exportclases/class.pptIzvozAnalizaCharts.php');
		$izvoz = new PptIzvozAnalizaCharts ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPpt();

		$izvoz->getFile('ppt_analiza.pptx');
			break;
			
	case M_REPORT_ANALIZA_PPT_HEATMAP_IMAGE:
		include ($site_path.'admin/exportclases/class.pptIzvozHeatmapImage.php');
		$izvoz = new pptIzvozHeatmapImage ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
		$izvoz->createPpt();

		$izvoz->getFile('ppt_analiza_heatmap_image_'.$_GET['sprID'].'.ppt');
			break;
}