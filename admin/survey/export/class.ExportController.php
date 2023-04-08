<?php

/**
 *
 *	Class ki skrbi za izvedbo ustreznega izvoza (prenešeno iz izvoz.php in dopolnjeno)
 *
 *
 *	Tipi izvoza:
 *	'survey' - izvoz vprašalnika (ankete)
 *		- 'q_empty' - prazen vprašalnik
 *		- 'q_data' - vprašalnik z vnešenimi odgovori za 1 respondenta
 *		- 'q_data_all' - vprašalnik z vnešenimi odgovori za vse respondente
 *		- 'q_comment' - vprašalnik s komentarji
 *
 *	'analysis' - izvoz analiz
 *		- 'sums' - sumarnik
 *		- 'freq' - frekvence 
 *		- 'desc' - opisne statistike
 *		- 'chart' - grafi
 *		- 'crosstab' - crosstabi
 *		- 'multicrosstab' - multi-crosstabi
 *		- 'mean' - povprečja
 *		- 'ttest' - ttest
 *		- 'break' - razbitje
 *		- 'heatmap' - heatmap
 *
 *	'creport' - izvoz poročila po meri sestavljenega iz različnih elementov analiz (custom report)
 *
 *	'status' - izvoz statusov (dashboard pod "Status->povzetek")
 *
 *	'data' - izvoz podatkov 
 *		- 'full' - celotna tabela podatkov - potrebno prenesti iz SurveyExport - TO SE LAHKO PRENAŠA NAKNADNO
 *		- 'list' - kratek izpis podatkov - samo prvih 5 spremenljivk
 *
 *	'other' - poseben izvoz (Evoli, Hierarhija, MFDPS, MJU...) 
 *		- 'usable' - uporabni respondenti
 *		- 'text_analysis' - analiza besedil v anketi
 *		- 'speeder' - speeder index
 *		- 'edits_analysis' - analiza urejanja ankete
 *		- 'evoli' 
 *		- 'evoli_teammeter' 
 *		- 'hierarhija' 
 *		- 'mfdps'
 *		- 'adecco_360' - treba dodatno prenesti
 *		- '1ka_360' - treba dodatno prenesti
 *
 *	'gdpr' - izvoz gdpr porocil (ce je vklopljen gdpr v anketi)
 *		- 'individual' - podrobnosti o zbiranju osebnih podatkov
 *		- 'activity' - evidenca dejavnosti obdelav	
 *	
 *	Formati izvoza:
 *		- 'pdf'
 *		- 'rtf'
 *		- 'xls'
 *		- 'csv'
 *		- 'txt'
 *		- 'html'
 *		- 'spss_syntax'
 *		- 'spss_sav'
 *		- 'ppt'
 *
 *
 */


//use Export\Latexclasses\LatexDocument as LatexDocument;
//include('../../function.php');
include('../../vendor/autoload.php');
include('export_definitions.php');

 
class ExportController{
	
	
	var $anketa;				// ID ankete
	
	
	function __construct(){
		global $site_path, $global_user_id, $admin_type, $lang;
		
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

		// Nastavimo ID ankete
		$this->anketa = $_GET['anketa'];

		# PDF lahko vidijo vsi, ki kliknejo na link za kreacijo PDF-ja
		# zato, kadar uporabnik ni logiran v CMS za pdf nastavimo $global_user_id kateri je enak avtorju ankete
		if ($global_user_id === null || $global_user_id === 0) {
			$sql = sisplet_query("SELECT insert_uid FROM srv_anketa WHERE id='$this->anketa'");
			$row = mysqli_fetch_assoc($sql);
			$global_user_id = $row['insert_uid'];
		}

		// nastavimo jezik
		SurveyInfo::SurveyInit($this->anketa);
		$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');		
		//$file = '../../../lang/'.$lang_admin.'.php';
		$file = $site_path.'lang/'.$lang_admin.'.php';		
		include($file);
	}
	
	
	// Izvedemo export
	public function executeExport(){
        global $site_path;
        
		// Nastavimo tip, podtip in format izvoza
		$export_type = $this->getExportType();
		$export_format = $this->getExportFormat();
		$exportType = $export_type['export_type'];
		$subtype = $export_type['export_subtype'];
    	
        /* echo "anketa: ".$this->anketa."</br>";
		echo "subtype: ".$subtype."</br>";
		echo "exportType: ".$exportType."</br>";
		echo "export_format: ".$export_format."</br>"; */
		
		// Posebni izvozi, ki se izvajajo drugje (za npr. locene module - evoli, mfdps...)
		if( $exportType == 'other' && in_array($subtype, array('evoli', 'evoli_teammeter', 'mfdps', 'heirarhija', 'text_analysis', 'usable', 'speeder')) ){
			$this->exportOther($subtype);	
		}
		else{
			switch($export_format){
				case 'pdf':
				case 'rtf':
				case 'html':
					// Ustvarimo latex dokument					
					$document = new LatexDocument($this->anketa);
					$document->createDocument($export_type['export_type'], $export_type['export_subtype'], $export_format, $_GET['sprID']);
					break;
				
				case 'xml':
					// Ustvarimo xml dokument
					$document = new XmlDocument($this->anketa);					
					$document->createXmlDocument($export_type['export_type'], $export_type['export_subtype'], $export_format);
					break;
					
 				case 'xls':		// Mogoce lahko tudi xls po novem naredimo preko html-ja iz latexa? Html tabele se naceloma vredu izpisejo v xls...		
				
					//Nov nacin izvoza xls iz Latex datoteke
					//$document = new LatexDocument($this->anketa);
					//$document->createDocument($export_type['export_type'], $export_type['export_subtype'], $export_format, $_GET['sprID']);
					//Nov nacin izvoza xls iz Latex datoteke - konec
					
					//Star nacin izvoza xls
  					switch($subtype){
						case 'sums':
							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaSums.php';
							include ($path);
							$izvoz = new XlsIzvozAnalizaSums ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
						break;
						case 'desc':
 							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaStatistics.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaStatistics ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
						break;
						case 'freq':
 							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaFrekvenca.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaFrekvenca ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
						break;						
						case 'crosstab':
							//poberemo tabelo s podatki
 							$crossData1 = explode(",", $_GET['data1']);
							$crossData2 = explode(",", $_GET['data2']);
							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaCrosstab.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaCrosstab ($_GET['anketa'], $crossData1, $crossData2);
						break;						
						case 'multicrosstab':
 							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaMultiCrosstab.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaMultiCrosstab ($_GET['anketa']);
						break;						
						case 'mean':
 							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaMean.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaMean ($_GET['anketa']);
						break;						
						case 'ttest':
 							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaTTest.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaTTest ($_GET['anketa']);
						break;						
						case 'break':
 							$path = $site_path.'admin/exportclases/class.xlsIzvozAnalizaBreak.php';
							include ($path);							
							$izvoz = new XlsIzvozAnalizaBreak ($_GET['anketa']);
						break;
					}
					
					$izvoz->createXls();
					$izvoz->getFile('xls_analiza_'.$subtype.'_'.time().'.xls');
					//Star nacin izvoza xls - konec

					break;
					
				case 'ppt':
					switch($subtype){
						case 'chart':
							$path = $site_path.'admin/exportclases/class.pptIzvozAnalizaCharts.php';
							include ($path);
							$izvoz = new PptIzvozAnalizaCharts ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
							$izvoz->createPpt();
							$izvoz->getFile('ppt_analiza_'.$subtype.'_'.time().'.pptx');
						break;						
						case 'heatmap':
							$path = $site_path.'admin/exportclases/class.pptIzvozHeatmapImage.php';
							include ($path);
							$izvoz = new pptIzvozHeatmapImage ($_GET['anketa'], $_GET['sprID'], $_GET['loop']);
							$izvoz->createPpt();
							$izvoz->getFile('ppt_analiza_heatmap_image_'.$_GET['sprID'].'_'.time().'.ppt');
						break;
					}
					break;
				
				case 'csv':
				case 'txt':
				case 'spss_syntax':
				case 'spss_sav':			
					break;
			}
		}
	}
	
	// Izvozi, ki se izvedejo drugje v locenih modulih (npr. evoli, mfdps, hierarhija...)
	private function exportOther($subtype=''){
		global $site_path;
		switch($subtype){
			
			case 'evoli':
				$usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0; 
				$evoli = new SurveyEvoli($_GET['anketa']);
				$evoli->executeExport($usr_id);
				break;
				
			case 'evoli_teammeter':
				$skupina = (isset($_GET['skupina']) && $_GET['skupina'] > 0) ? $_GET['skupina'] : 0; 
				$evoli_tm = new SurveyTeamMeter($_GET['anketa']);
				$evoli_tm->executeExport($skupina);
				break;
				
			case 'mfdps':
				$usr_id = (isset($_GET['usr_id']) && $_GET['usr_id'] > 0) ? $_GET['usr_id'] : 0; 
				$evoli = new SurveyMFDPS($_GET['anketa']);
				$evoli->executePDFExport($usr_id);
				break;
				
			case 'hierarhija':
				//include ($site_path.'admin/survey/modules/mod_hierarhija/class/HierarhijaIzvozAnalize.php');
				$izvoz = new \Hierarhija\HierarhijaIzvozAnalize($_GET['anketa']);
				$izvoz->createPdf();

				$izvoz->getFile('pdf_analiza1'.time().'.pdf');
				break;
				
			case 'text_analysis':
				//include ($site_path.'admin/survey/classes/class.SurveyTextAnalysis.php');
				$STA = new SurveyTextAnalysis($_GET['anketa']);
				$STA->exportCSVTable($_GET['type']);
				break;
			
			case 'usable':
				$path = $site_path.'admin/exportclases/class.xlsIzvozUsable.php';
				include ($path);
				$izvoz = new XlsIzvozUsable ($_GET['anketa']);
				$izvoz->createXls();
				$izvoz->getFile('xls_usable'.time().'.xls');
				break;

			case 'speeder':
				include ($site_path.'admin/exportclases/class.xlsIzvozSpeeder.php');
				$izvoz = new XlsIzvozSpeeder ($_GET['anketa']);
				$izvoz->createXls();
				$izvoz->getFile('xls_speeder'.time().'.xls');
					break;
		}			
	}
	
	
		
	// Nastavimo tip izvoza
	private function getExportType(){
		global $site_path, $global_user_id, $admin_type, $lang;
		
		switch ( $_GET['a'] ) {
			case A_REPORT_VPRASALNIK_PDF:
			case A_REPORT_VPRASALNIK_RTF:
				$export_type = 'survey';
				$export_subtype = 'q_empty';
				break;
				
			case A_REPORT_VPRASALNIK_XML:
				$export_type = 'survey';
				$export_subtype = 'q_empty_xml';
				break;

			case A_REPORT_PDF_RESULTS:	
			case A_REPORT_RTF_RESULTS:	
				$export_type = 'survey';
				if(isset($_GET['usr_id']))
					$export_subtype = 'q_data'; 		// če je definiran $_GET['usr_id'] izpisemo samo njegove odgovore
				else
					$export_subtype = 'q_data_all';	// če ni usr_id definiran vprasalnike z odgovori vseh respondentov enega za drugim
				break;

			case A_REPORT_PDF_COMMENT:
			case A_REPORT_RTF_COMMENT:
				$export_type = 'survey';
				$export_subtype = 'q_comment';
				break;
			
			case A_REPORT_PDF_STATUS:
				$export_type = 'status';
				$export_subtype = 'status';
				break;
				
			case A_REPORT_PDF_EDITS_ANALYSIS:
				$export_type = 'other';
				$export_subtype = 'edits_analysis';
				break;
				
			case A_REPORT_PDF_LIST:
			case A_REPORT_RTF_LIST:
			case A_REPORT_XLS_LIST:
				$export_type = 'data';
				$export_subtype = 'list';
                break;
            
            case A_GDPR_PDF_INDIVIDUAL:
            case A_GDPR_RTF_INDIVIDUAL:
                $export_type = 'gdpr';
                $export_subtype = 'individual';
                break;
            
            case A_GDPR_PDF_ACTIVITY:
            case A_GDPR_RTF_ACTIVITY:
                $export_type = 'gdpr';
                $export_subtype = 'activity';
                break;
		}	
		switch ( $_GET['m'] ) {
			
			// Kakšna je razlika med tema dvema izvozoma??
			//case M_REPORT_ANALIZA_HEATMAP_IMAGE:
			//case M_REPORT_PDF_HEATMAP_IMAGE:				
			case M_REPORT_ANALIZA_PPT_HEATMAP_IMAGE:
				$export_type = 'analysis';
				$export_subtype = 'heatmap';
				break;
				
			case M_REPORT_ANALIZA_HEATMAP_IMAGE:
				$export_type = 'analysis';
				$export_subtype = 'heatmap_image';
				break;
				
			case M_REPORT_PDF_HEATMAP_IMAGE:
			case M_REPORT_ANALIZA_RTF_HEATMAP_IMAGE:
				$export_type = 'analysis';
				$export_subtype = 'heatmap_image_pdf';
				break;

			case M_REPORT_ANALIZA_PDF_SUMS:
			case M_REPORT_ANALIZA_RTF_SUMS:
			case M_REPORT_ANALIZA_XLS_SUMS:
				$export_type = 'analysis';
				$export_subtype = 'sums';
				break;
			
			case M_REPORT_ANALIZA_PDF_FREKVENCA:
			case M_REPORT_ANALIZA_RTF_FREKVENCA:
			case M_REPORT_ANALIZA_XLS_FREKVENCA:
				$export_type = 'analysis';
				$export_subtype = 'freq';
				break;
			
			case M_REPORT_ANALIZA_PDF_STAT:
			case M_REPORT_ANALIZA_RTF_STAT:
			case M_REPORT_ANALIZA_XLS_STAT:
				$export_type = 'analysis';
				$export_subtype = 'desc';
				break;
			
			case M_REPORT_ANALIZA_PDF_CHARTS:
			case M_REPORT_ANALIZA_RTF_CHARTS:
			case M_REPORT_ANALIZA_PPT_CHARTS:
				$export_type = 'analysis';
				$export_subtype = 'chart';
				break;
			
			case M_REPORT_ANALIZA_PDF_CROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_RTF_CROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_XLS_CROSSTAB_IZPIS:
				$export_type = 'analysis';
				$export_subtype = 'crosstab';
				break;
			
			case M_REPORT_ANALIZA_PDF_MULTICROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_RTF_MULTICROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_XLS_MULTICROSSTAB_IZPIS:
				$export_type = 'analysis';
				$export_subtype = 'multicrosstab';
				break;
			
			case M_REPORT_ANALIZA_PDF_MEAN_IZPIS:
			case M_REPORT_ANALIZA_RTF_MEAN_IZPIS:
			case M_REPORT_ANALIZA_XLS_MEAN_IZPIS:
				$export_type = 'analysis';
				$export_subtype = 'mean';
				break;
			
			case M_REPORT_ANALIZA_PDF_TTEST_IZPIS:
			case M_REPORT_ANALIZA_RTF_TTEST_IZPIS:
			case M_REPORT_ANALIZA_XLS_TTEST_IZPIS:
				$export_type = 'analysis';
				$export_subtype = 'ttest';
				break;
			
			case M_REPORT_ANALIZA_PDF_BREAK_IZPIS:
			case M_REPORT_ANALIZA_RTF_BREAK_IZPIS:
			case M_REPORT_ANALIZA_XLS_BREAK_IZPIS:
				$export_type = 'analysis';
				$export_subtype = 'break';
				break;
			
			case M_REPORT_ANALIZA_PDF_CREPORT:
			case M_REPORT_ANALIZA_RTF_CREPORT:
				$export_type = 'analysis';
				$export_subtype = 'creport';
				break;
								
			
			case M_REPORT_PDF_EVOLI:
				$export_type = 'other';
				$export_subtype = 'evoli';
				break;
			case M_REPORT_PDF_TEAMMETER:
				$export_type = 'other';
				$export_subtype = 'evoli_teammeter';
				break;
			case M_REPORT_PDF_MFDPS:
				$export_type = 'other';
				$export_subtype = 'mfdps';
				break;
			case M_REPORT_HIERARHIJA_PDF_IZPIS:
				$export_type = 'other';
				$export_subtype = 'hierarhija';
				break;
			case A_REPORT_XLS_USABLE:
				$export_type = 'other';
				$export_subtype = 'usable';
				break;
			case A_REPORT_XLS_SPEEDER:
				$export_type = 'other';
				$export_subtype = 'speeder';
				break;
			case A_REPORT_XLS_TEXT_ANALYSIS:
			case A_REPORT_CSV_TEXT_ANALYSIS:
				$export_type = 'other';
				$export_subtype = 'text_analysis';
				break;
		}
		
		return array('export_type'=>$export_type, 'export_subtype'=>$export_subtype);
	}
	
	// Nastavimo format izvoza
	private function getExportFormat(){
		global $site_path, $global_user_id, $admin_type, $lang;
		
		switch ( $_GET['a'] ) {
			case A_REPORT_VPRASALNIK_PDF:
			case A_REPORT_PDF_RESULTS:					
			case A_REPORT_PDF_COMMENT:
			case A_REPORT_PDF_STATUS:
			case A_REPORT_PDF_EDITS_ANALYSIS:
            case A_REPORT_PDF_LIST:
            case A_GDPR_PDF_INDIVIDUAL:
            case A_GDPR_PDF_ACTIVITY:
				$export_format = 'pdf';
				break;
				
			case A_REPORT_RTF_COMMENT:
			case A_REPORT_VPRASALNIK_RTF:
			case A_REPORT_RTF_RESULTS:
            case A_REPORT_RTF_LIST:
            case A_GDPR_RTF_INDIVIDUAL:
            case A_GDPR_RTF_ACTIVITY:
				$export_format = 'rtf';
				break;
				
			case A_REPORT_XLS_LIST:
				$export_format = 'xls';
				break;
			
			case A_REPORT_VPRASALNIK_XML:
				$export_format = 'xml';
				break;
		}	
		switch ( $_GET['m'] ) {
			
			// Kakšna je razlika med tema dvema izvozoma??
			case M_REPORT_ANALIZA_HEATMAP_IMAGE:		
			case M_REPORT_PDF_HEATMAP_IMAGE:		

			case M_REPORT_ANALIZA_PDF_FREKVENCA:
			case M_REPORT_ANALIZA_PDF_CROSSTAB_IZPIS:					
			case M_REPORT_ANALIZA_PDF_MULTICROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_PDF_MEAN_IZPIS:
			case M_REPORT_ANALIZA_PDF_TTEST_IZPIS:
			case M_REPORT_ANALIZA_PDF_BREAK_IZPIS:
			case M_REPORT_ANALIZA_PDF_STAT:
			case M_REPORT_ANALIZA_PDF_SUMS:
			case M_REPORT_ANALIZA_PDF_CHARTS:
			case M_REPORT_ANALIZA_PDF_CREPORT:
			case M_REPORT_PDF_EVOLI:
			case M_REPORT_PDF_TEAMMETER:
			case M_REPORT_PDF_MFDPS:
			case M_REPORT_HIERARHIJA_PDF_IZPIS:
				$export_format = 'pdf';
				break;
	
			case M_REPORT_ANALIZA_RTF_STAT:
			case M_REPORT_ANALIZA_RTF_SUMS:
			case M_REPORT_ANALIZA_RTF_FREKVENCA:
			case M_REPORT_ANALIZA_RTF_CROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_RTF_MULTICROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_RTF_MEAN_IZPIS:
			case M_REPORT_ANALIZA_RTF_TTEST_IZPIS:
			case M_REPORT_ANALIZA_RTF_BREAK_IZPIS:
			case M_REPORT_ANALIZA_RTF_CHARTS:
			case M_REPORT_ANALIZA_RTF_CREPORT:
			case M_REPORT_ANALIZA_RTF_HEATMAP_IMAGE:
				$export_format = 'rtf';
				break;

			case M_REPORT_ANALIZA_XLS_FREKVENCA:
			case M_REPORT_ANALIZA_XLS_CROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_XLS_MULTICROSSTAB_IZPIS:
			case M_REPORT_ANALIZA_XLS_STAT:
			case M_REPORT_ANALIZA_XLS_SUMS:
			case M_REPORT_ANALIZA_XLS_MEAN_IZPIS:
			case M_REPORT_ANALIZA_XLS_TTEST_IZPIS:
			case M_REPORT_ANALIZA_XLS_BREAK_IZPIS:
			case A_REPORT_XLS_USABLE:
			case A_REPORT_XLS_SPEEDER:
			case A_REPORT_XLS_TEXT_ANALYSIS:
				$export_format = 'xls';
				break;
				
			case A_REPORT_CSV_TEXT_ANALYSIS:
				$export_format = 'csv';
				break;

			case M_REPORT_ANALIZA_PPT_CHARTS:
			case M_REPORT_ANALIZA_PPT_HEATMAP_IMAGE:
				$export_format = 'ppt';
				break;
		}
		
		return $export_format;
	}
	
}