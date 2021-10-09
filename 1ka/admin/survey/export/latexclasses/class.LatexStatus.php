<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	//require_once('../exportclases/class.enka.pdf.php');
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor");
	define("M_ANALIZA_FREQUENCY", "frequency");

	define("FNT_FREESERIF", "freeserif");
	define("FNT_FREESANS", "freesans");
	define("FNT_HELVETICA", "helvetica");

	define("FNT_MAIN_TEXT", FNT_FREESANS);
	define("FNT_QUESTION_TEXT", FNT_FREESANS);
	define("FNT_HEADER_TEXT", FNT_FREESANS);

	define("FNT_MAIN_SIZE", 10);
	define("FNT_QUESTION_SIZE", 9);
	define("FNT_HEADER_SIZE", 10);

	define("RADIO_BTN_SIZE", 3);
	define("CHCK_BTN_SIZE", 3);
	define("LINE_BREAK", 6);

	define ('PDF_MARGIN_HEADER', 8);
	define ('PDF_MARGIN_FOOTER', 12);
	define ('PDF_MARGIN_TOP', 18);
	define ('PDF_MARGIN_BOTTOM', 18);
	define ('PDF_MARGIN_LEFT', 15);
	define ('PDF_MARGIN_RIGHT', 15);
	
	define ('FRAME_TEXT_WIDTH', 0.3);
	define ('FRAME_WIDTH', 233);
	define ('FRAME_HEIGTH', 330);
	define ('GRAPH_LINE_WIDTH', 0.15);
	define ('GRAPH_LINE_LENGTH_MAX', 3);
	

/** Class za generacijo pdf-a
 *
 * @desc: po novem je potrebno form elemente generirati ro�no kot slike
 *
 */
class LatexStatus {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	protected $texNewLine = '\\\\ ';
	protected $texBigSkip = '\bigskip';
	protected $texSmallSkip = '\smallskip';
	protected $horizontalLineTex = "\\hline ";
	
	
	public static $ss = null;		//SurveyStatistic class
	public static $sas = null;		//		$sas = new SurveyAdminSettings();class

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $ssData = null)
	{
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->anketa['podstran'] = $podstran;
			// create new PDF document
			//$this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
		
		$this->sas = new SurveyAdminSettings(0,$this->anketa['id']);
		//ustvarimo SurveyStatistic objekt in mu napolnimo variable
		$this->ss = new SurveyStatistic();
		$this->ss->Init($this->anketa['id'],true);
		/*
		
		$this->ss->realUsersByStatus_base = $ssData[0];

		$this->ss->type = $ssData[1];
		$this->ss->period = $ssData[2];
	*/	
		/* intervali se več ne pošiljajo preko get, ker se polovijo iz porfila 
		if($ssData[1] != 'undefined')
			$this->ss->startDate = $ssData[1];
		if($ssData[2] != 'undefined')
			$this->ss->endDate = $ssData[2];
		$this->ss->type = $ssData[3];
		$this->ss->period = $ssData[4];
		//$this->ss->isDefaultFilters = false; 
		*/
		
		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) )
		{
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}
		else
			return false;
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	// SETTERS && GETTERS

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}
	function getFile($fileName='')
	{
		//Close and output PDF document		
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}

	public function displayStatus() {
		global $lang;		
		$texStatus = '';
		
		// imamo vnose, prikažemo statistiko
		$this->ss->PrepareDateView();
		$this->ss->PrepareStatusView();
		
		//naslov izvoza
		//$texStatus .= $this->returnBold('Status').$this->texNewLine.$this->texNewLine;		
		$texStatus .= '\MakeUppercase{\huge \textbf{Status - '.$lang['srv_status_summary'].'}}'.$this->texBigSkip.$this->texNewLine;
		
		
		$texStatus .= '\begin{tableStatus}';	/*zacetek environmenta z manjsim fontom*/
		
		// zgornji boxi	#########################	
		$texStatus .= $this -> DisplayInfoView();
		
		//prostor med 1. in 2. okvirjem
		$texStatus .= ' \hspace*{0.02\textwidth}';
		
		$texStatus .= $this -> DisplayStatusView();
		
		//prostor med 2. in 3. okvirjem
		$texStatus .= ' \hspace*{0.02\textwidth}';
		
		$texStatus .= $this -> DisplayAnswerStateView();
		
		//prostor med zgornjimi in spodnjimi okvirji
		$texStatus .= $this->texNewLine;
		$texStatus .= $this->texNewLine;
		
		// zgornji boxi - konec #################
		
		// spodnji boxi #########################
		$texStatus .= $this -> DisplayReferalsView();
		
		//prostor med 1. in 2. okvirjem
		$texStatus .= ' \hspace*{0.02\textwidth}';
		
		$texStatus .= $this -> DisplayDateView();
		
		//prostor med 2. in 3. okvirjem
		$texStatus .= ' \hspace*{0.02\textwidth}';
		
		$texStatus .= $this -> DisplayPagesStateView();
		
		// spodnji boxi - konec #################
		
		$texStatus .= '\end{tableStatus}';	/*zakljucek environmenta z manjsim fontom*/
		
		return $texStatus;
	}
		
	/** Funkcija prikaze osnovnih informacij
	 * 
	 */
	function DisplayInfoView() {
		global $lang;
		global $site_url;
		
		$texStatusInfo = '';
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_statistic_info_title']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		//Priprava parametrov za tabelo s podatki o anketi
		//$steviloStolpcevParameterTabular = 3;
		$steviloStolpcevParameterTabular = 2;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
		$parameterTabular = '';
		$export_format = 'pdf';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				//$parameterTabular .= ($export_format == 'pdf' ? 'P' : 'l');
				$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
			}else{
				//$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X' : 'c');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
				$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
			}			
		}
		//Priprava parametrov za tabelo s podatki o anketi - konec
		
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$texStatusInfo .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		//Priprava parametrov za tabelo s podatki o anketi - konec

		//Priprava podatkov za izpis vrstic tabele

		//ime ankete
		$prvaVrstica = array();
		$prvaVrstica[] = $this->encodeText($lang['srv_info_name'].':');
		//$prvaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText(SurveyInfo::getSurveyTitle()).'} ';
		$prvaVrstica[] = $this->encodeText(SurveyInfo::getSurveyTitle());
		
		//katere napredne možnosti so vklopljene
		$row = SurveyInfo::getSurveyRow();
		$enabled_advanced = null;
		$prefix = '';
		if ($row['uporabnost'] == 1) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_4'];
			$prefix = ', '; 	
		}
		if ($row['user_from_cms'] == 1) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_5'];
			$prefix = ', ';	
		}
		if ($row['quiz'] == 1) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_6'];
			$prefix = ', ';
		}
		if ($row['phone'] == 1) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_7'];
			$prefix = ', ';
		}
		if ($row['social_network'] == 1) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_8'];
			$prefix = ', ';
		}
		
		//tip ankete
		$drugaVrstica = array();
		$drugaVrstica[] = $this->encodeText($lang['srv_info_type'].':');
		//$drugaVrstica[] = '\multicolumn{2}{l}{ '.$lang['srv_vrsta_survey_type_'.SurveyInfo::getSurveyType()] . ($enabled_advanced != null ? ' ('.$enabled_advanced.')' : '' ).'} ';
		$drugaVrstica[] = $lang['srv_vrsta_survey_type_'.SurveyInfo::getSurveyType()] . ($enabled_advanced != null ? ' ('.$enabled_advanced.')' : '' );
		
/* 		//vprašanj, variabel
		$tretjaVrstica = array();
		//$tretjaVrstica[] = $this->encodeText($lang['srv_info_questions1'].': ').$this->encodeText(SurveyInfo::getSurveyQuestionCount());
		$tretjaVrstica[] = $this->encodeText($lang['srv_info_questions1'].': ');
		$tretjaVrstica[] = $this->encodeText(SurveyInfo::getSurveyQuestionCount());		
		//$tretjaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_info_variables'].': ').$this->encodeText(SurveyInfo::getSurveyVariableCount()).'} ';
		$tretjaVrstica[] = $this->encodeText($lang['srv_info_variables'].': ').$this->encodeText(SurveyInfo::getSurveyVariableCount()); */
		
		//vprašanj
		$tretjaVrsticaA = array();
		//$tretjaVrstica[] = $this->encodeText($lang['srv_info_questions1'].': ').$this->encodeText(SurveyInfo::getSurveyQuestionCount());
		$tretjaVrsticaA[] = $this->encodeText($lang['srv_info_questions1'].': ');
		$tretjaVrsticaA[] = $this->encodeText(SurveyInfo::getSurveyQuestionCount());		
		
		//variabel
		$tretjaVrsticaB = array();		
		$tretjaVrsticaB[] = $this->encodeText($lang['srv_info_variables'].': ');
		$tretjaVrsticaB[] = $this->encodeText(SurveyInfo::getSurveyVariableCount());		
		
/* 		//uporabnikov, odgovorov
		$cetrtaVrstica = array();
		$cetrtaVrstica[] = $this->encodeText($lang['srv_analiza_stUporabnikov'].':');
		$cetrtaVrstica[] = $this->encodeText(SurveyInfo::getSurveyAnswersCount());
		$cetrtaVrstica[] = $this->encodeText($lang['srv_info_answers_valid'].': ').$this->encodeText(SurveyInfo::getSurveyApropriateAnswersCount()); */
		
		//uporabnikov
		$cetrtaVrsticaA = array();
		$cetrtaVrsticaA[] = $this->encodeText($lang['srv_analiza_stUporabnikov'].':');
		$cetrtaVrsticaA[] = $this->encodeText(SurveyInfo::getSurveyAnswersCount());
		
		//odgovorov
		$cetrtaVrsticaB = array();
		$cetrtaVrsticaB[] = $this->encodeText($lang['srv_info_answers_valid'].': ');
		$cetrtaVrsticaB[] = $this->encodeText(SurveyInfo::getSurveyApropriateAnswersCount());
	
		//jezik ankete
		$petaVrstica = array();
		$petaVrstica[] = $this->encodeText($lang['srv_info_language'].':');
		//$petaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText(SurveyInfo::getRespondentLanguage()).'} ';		
		$petaVrstica[] = $this->encodeText(SurveyInfo::getRespondentLanguage());
		
		//avtor
		$sestaVrstica = array();
		$sestaVrstica[] = $this->encodeText($lang['srv_info_creator'].':');
		$text = '';
		$text .= SurveyInfo::getSurveyInsertName();
		if (SurveyInfo::getSurveyInsertDate() && SurveyInfo::getSurveyInsertDate() != "00.00.0000")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyInsertDate(),DATE_FORMAT_SHORT);
		if (SurveyInfo::getSurveyInsertTime() && SurveyInfo::getSurveyInsertTime() != "00:00:00")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyInsertTime(),TIME_FORMAT_SHORT);

		//$sestaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($text).'} ';		
		$sestaVrstica[] = $this->encodeText($text);		
		
		//spreminjal
		$sedmaVrstica = array();
		$sedmaVrstica[] = $this->encodeText($lang['srv_info_modify'].':');
		$text = '';
		$text .= SurveyInfo::getSurveyEditName();
		if (SurveyInfo::getSurveyEditDate() && SurveyInfo::getSurveyEditDate() != "00.00.0000")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyEditDate(),DATE_FORMAT_SHORT);
		if (SurveyInfo::getSurveyEditTime() && SurveyInfo::getSurveyEditTime() != "00:00:00")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyEditTime(),TIME_FORMAT_SHORT);

		//$sedmaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($text).'} ';
		$sedmaVrstica[] = $this->encodeText($text);
		
		//dostop, Kdo razen avtorja ima dostop
		$dostop = SurveyInfo::getSurveyAccessUsers();
		if ($dostop) {
			//$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_access'].':'), 0, 0, 'L', 0);
			$osmaVrstica = array();
			$osmaVrstica[] = $this->encodeText($lang['srv_info_access'].':');
			$prefix='';
			foreach ( $dostop as $user) {
				$prefix .= $user['name'].'; ';
			}
			$prefix = substr($prefix, 0, -2);
			//$osmaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($prefix).'} ';
			$osmaVrstica[] = $this->encodeText($prefix);
		}
		
		//aktivnost
		$devetaVrstica = array();
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		$devetaVrstica[] = $this->encodeText($lang['srv_displaydata_status'].':');
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			//$devetaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_anketa_active2']).'} ';
			$devetaVrstica[] = $this->encodeText($lang['srv_anketa_active2']);
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				//$devetaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_survey_non_active_notActivated1']).'} ';
				$devetaVrstica[] = $this->encodeText($lang['srv_survey_non_active_notActivated1']);
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				//$devetaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_survey_non_active1']).'} ';
				$devetaVrstica[] = $this->encodeText($lang['srv_survey_non_active1']);
			}
		}

		//trajanje: datumi aktivnosti
		if ( count($activity) > 0 ) {
			$desetaVrstica = array();			
			$desetaVrstica[] = $this->encodeText($lang['srv_info_activity'].':');
			$prefix = '';
			foreach ($activity as $active) {
				$_starts = explode('-',$active['starts']);
				$_expire = explode('-',$active['expire']);

				$prefix .= $_starts[2].'.'.$_starts[1].'.'.$_starts[0].'-'.$_expire[2].'.'.$_expire[1].'.'.$_expire[0];
				$prefix .= '; ';
			}
			//$desetaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($prefix).'} ';
			$desetaVrstica[] = $this->encodeText($prefix);
		}
		
		# predviceni cas trajanja enkete
		$skupni_cas = $this->sas->testiranje_cas(1);
		$skupni_predvideni_cas = $this->sas->testiranje_predvidenicas(1);	
		
		$d = new Dostop();
		
		//predviceni cas trajanja enkete
		$enajstaVrstica = array();
		$enajstaVrstica[] = $this->encodeText($lang['srv_info_duration'].':');		
		$text = '';
		$text .= ($skupni_cas != '') ? $skupni_cas.', ' : '';
		$text .= $lang['srv_predvideno'].': '.$skupni_predvideni_cas;
		//$enajstaVrstica[] = '\multicolumn{2}{l}{ '.$this->encodeText($text).'} ';
		$enajstaVrstica[] = $this->encodeText($text);
		
		
		//VNOSI - prvi / zadnji vnos
		$prvi_vnos_date = SurveyInfo::getSurveyFirstEntryDate();
		$prvi_vnos_time = SurveyInfo::getSurveyFirstEntryTime();
		$zadnji_vnos_date = SurveyInfo::getSurveyLastEntryDate();
		$zadnji_vnos_time = SurveyInfo::getSurveyLastEntryTime();
		$dvanajstaVrstica = array();
		$dvanajstaVrsticaA = array();
		if ($prvi_vnos_date != null) {
			$dvanajstaVrstica[] = $this->encodeText($lang['srv_info_first_entry'].':');
			$text = '';
			$text .= $this->ss->dateFormat($prvi_vnos_date,DATE_FORMAT_SHORT);
			$text .= $prvi_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->ss->dateFormat($prvi_vnos_time,TIME_FORMAT_SHORT)) : '';
			$dvanajstaVrstica[] = $this->encodeText($text);
		}else{
			$dvanajstaVrstica[] = '';
			$dvanajstaVrstica[] = '';
		}
		if ($zadnji_vnos_date != null) {
			$dvanajstaVrsticaA[] = $this->encodeText($lang['srv_info_last_entry'].':');
			$text = '';
			$text .= $this->ss->dateFormat($zadnji_vnos_date,DATE_FORMAT_SHORT);
			$text .= $zadnji_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->ss->dateFormat($zadnji_vnos_time,TIME_FORMAT_SHORT)) : '';
			//$dvanajstaVrsticaA[] = $this->encodeText($lang['srv_info_last_entry'].': '.$this->encodeText($text));
			$dvanajstaVrsticaA[] = $this->encodeText($text);
		}else{
			$dvanajstaVrsticaA[] = '';
		}
		
		// Komentarji
		$SD = new SurveyDiagnostics($this->anketa['id']);
		$comments = $SD->testComments();
		
		list($commentsAll,$commentsUnresolved,$commentsQuestionAll,$commentsQuestionUnresolved,$commentsUser,$commentsUserFinished) = $comments;		
		
		$commentsUserUnresolved = $commentsUser - $commentsUserFinished;
		$komentarji = 0;
		if ((	(int)$commentsAll
				+(int)$commentsUnresolved
				+(int)$commentsQuestionAll
				+(int)$commentsQuestionUnresolved
				+(int)$commentsUser
				+(int)$commentsUserFinished
				) > 0 ) {
			
			$trinajstaVrsticaA = array();
			$trinajstaVrsticaB = array();
			$trinajstaVrsticaC = array();
			
			$trinajstaVrsticaA[] = $this->encodeText($lang['srv_diagnostic_4_element_0'].':');
			//$trinajstaVrsticaA[] = '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_diagnostic_4_element_1'].': '.(int)$commentsAll.' / '.(int)$commentsUnresolved).'} ';
			$trinajstaVrsticaA[] = $this->encodeText($lang['srv_diagnostic_4_element_1'].': '.(int)$commentsAll.' / '.(int)$commentsUnresolved);

			$trinajstaVrsticaB[] = '';
			//$trinajstaVrsticaB[] = '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_diagnostic_4_element_6'].': '.(int)$commentsQuestionAll.' / '.(int)$commentsQuestionUnresolved).'} ';
			$trinajstaVrsticaB[] = $this->encodeText($lang['srv_diagnostic_4_element_6'].': '.(int)$commentsQuestionAll.' / '.(int)$commentsQuestionUnresolved);
			
			$trinajstaVrsticaC[] = '';
			//$trinajstaVrsticaC[] =  '\multicolumn{2}{l}{ '.$this->encodeText($lang['srv_diagnostic_4_element_7'].': '.(int)$commentsUser.' / '.(int)$commentsUserUnresolved).'} ';
			$trinajstaVrsticaC[] = $this->encodeText($lang['srv_diagnostic_4_element_7'].': '.(int)$commentsUser.' / '.(int)$commentsUserUnresolved);

			$komentarji = 1;
		}
		
		//Priprava podatkov za izpis vrstic tabele - konec
		
		//Izpis vrstic tabele s podatki
		$texStatusInfo .= $this->tableRow($prvaVrstica, 1);
		$texStatusInfo .= $this->tableRow($drugaVrstica, 1);
		//$texStatusInfo .= $this->tableRow($tretjaVrstica, 1);
		$texStatusInfo .= $this->tableRow($tretjaVrsticaA, 1);
		$texStatusInfo .= $this->tableRow($tretjaVrsticaB, 1);
		//$texStatusInfo .= $this->tableRow($cetrtaVrstica, 1);
		$texStatusInfo .= $this->tableRow($cetrtaVrsticaA, 1);
		$texStatusInfo .= $this->tableRow($cetrtaVrsticaB, 1);
		$texStatusInfo .= $this->tableRow($petaVrstica, 1);
		$texStatusInfo .= $this->tableRow($sestaVrstica, 1);
		$texStatusInfo .= $this->tableRow($sedmaVrstica, 1);
		if ($dostop) {
			$texStatusInfo .= $this->tableRow($osmaVrstica, 1);
		}
		$texStatusInfo .= $this->tableRow($devetaVrstica, 1);
		if ( count($activity) > 0 ) {
			$texStatusInfo .= $this->tableRow($desetaVrstica, 1);
		}
		$texStatusInfo .= $this->tableRow($enajstaVrstica, 1);
/* 		if ($prvi_vnos_date != null || $zadnji_vnos_date != null) {
			$texStatusInfo .= $this->tableRow($dvanajstaVrstica, 1);
		} */
		if ($prvi_vnos_date != null) {
			$texStatusInfo .= $this->tableRow($dvanajstaVrstica, 1);
		}
		if ($zadnji_vnos_date != null) {
			$texStatusInfo .= $this->tableRow($dvanajstaVrsticaA, 1);
		}
		if($komentarji){
			$texStatusInfo .= $this->tableRow($trinajstaVrsticaA, 1);
			$texStatusInfo .= $this->tableRow($trinajstaVrsticaB, 1);
			$texStatusInfo .= $this->tableRow($trinajstaVrsticaC, 1);
		}
		
	
		//zaljucek latex tabele s podatki
		$texStatusInfo .= "\\end{".$pdfTable."}";
		//zaljucek latex tabele s podatki - konec	
		
		//izpis tabele v okvir
		$texText = $this->FrameText($title.$texStatusInfo);
		
		//echo $texStatusInfo;
		//return $texStatusInfo;
		return $texText;
	}

	/** Funkcija prikaže statuse
	 * 
	 */
	 function DisplayStatusView() {
	 	global $lang;
		
		$texStatusView = '';
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_statistic_status_title1']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		//Priprava parametrov za tabelo s podatki o anketi
		$steviloStolpcevParameterTabular = 2;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
		$parameterTabular = '';
		$export_format = 'pdf';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');		
		}
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$texStatusView .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		//Priprava parametrov za tabelo s podatki o anketi - konec
		
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic
		
		$cntValid = 0; // da vemo ali izpisemo skupne
		$cntNonValid = 0; // da vemo ali izpisemo skupne
		
		foreach ($this->ss->appropriateStatus as $status) {
			$vrsticaA = array();
			if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['valid'][$status] == 0)) {// da ne delamo po neporebnem				
				$vrsticaA[] = $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :');				
				$vrsticaA[] = $this->encodeText($this->ss->userByStatus['valid'][$status]);
				$texStatusView .= $this->tableRow($vrsticaA,1);
				$cntValid++;
			}
		}
		
		// vsota vlejavnih
		if ($cntValid > 0 || !$this->ss->hideNullValues_status) {
			$vrsticaB = array();
/* 			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_valid']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['valid']), 'T', 1, 'L', 0);
			
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6'); */
			$texStatusView .= $this->horizontalLineTex;
			$vrsticaB[] = $this->encodeText($lang['srv_statistic_redirection_sum_valid']);
			$vrsticaB[] = $this->encodeText($this->ss->cntUserByStatus['valid']);
			$texStatusView .= $this->tableRow($vrsticaB,1);
			$texStatusView .= $this->texNewLine;
		} 
			
		// izpišemo še neveljavne
		foreach ($this->ss->unAppropriateStatus as $status) {
			$vrsticaC = array();
			if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['nonvalid'][$status] == 0)) {// da ne delamo po neporebnem
				//$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :'), 0, 0, 'L', 0);
				//$this->pdf->Cell(45, 0, $this->encodeText($this->ss->userByStatus['nonvalid'][$status]), 0, 1, 'L', 0);
				$vrsticaC[] = $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :');
				$vrsticaC[] = $this->encodeText($this->ss->userByStatus['nonvalid'][$status]);
				$texStatusView .= $this->tableRow($vrsticaC,1);
				$cntNonValid++;
				//$this->pdf->setX($X);
			}
		}
		// se status null (neznan status)
		if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['nonvalid'][-1] == 0)) {// da ne delamo po neporebnem
			$vrsticaD = array();
			//$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_null']), 0, 0, 'L', 0);
			//$this->pdf->Cell(45, 0, $this->encodeText(isset($this->ss->userByStatus['nonvalid'][-1]) ? $this->ss->userByStatus['nonvalid'][-1] : '0'), 0, 1, 'L', 0);
			
			//$texStatusView .= $this->horizontalLineTex;
			$vrsticaD[] = $this->encodeText($lang['srv_userstatus_null']);
			$vrsticaD[] = $this->encodeText(isset($this->ss->userByStatus['nonvalid'][-1]) ? $this->ss->userByStatus['nonvalid'][-1] : '0');
			$texStatusView .= $this->tableRow($vrsticaD,1);
			//$texStatusView .= $this->texNewLine;
			$cntNonValid++;
			//$this->pdf->setX($X);
		}
		
		// vsota nevlejavnih 
		if ($cntNonValid > 0 || !$this->ss->hideNullValues_status) {
			$vrsticaE = array();			
/* 			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_nonvalid']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['nonvalid']), 'T', 1, 'L', 0);
			
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6'); */

			$vrsticaE[] = $this->encodeText($lang['srv_statistic_redirection_sum_nonvalid']);
			$vrsticaE[] = $this->encodeText($this->ss->cntUserByStatus['nonvalid']);
			$texStatusView .= $this->horizontalLineTex;
			$texStatusView .= $this->tableRow($vrsticaE,1);
			$texStatusView .= $this->texNewLine;
		}
/* 		$this->pdf->setFont('','B','6');
		$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum']), 'T', 0, 'L', 0);
		$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['valid']+$this->ss->cntUserByStatus['nonvalid']), 'T', 1, 'L', 0);
		$this->pdf->setFont('','','6');		
		$this->pdf->setX($X);	 */	
		$texStatusView .= $this->horizontalLineTex;
		$vrsticaF = array();
		$vrsticaF[] = $this->encodeText($lang['srv_statistic_redirection_sum']);
		if(($this->encodeText($this->ss->cntUserByStatus['valid']+$this->ss->cntUserByStatus['nonvalid']))){
			$vrsticaF[] = $this->encodeText($this->ss->cntUserByStatus['valid']+$this->ss->cntUserByStatus['nonvalid']);
		}else{
			$vrsticaF[] = 0;
		}		
		$texStatusView .= $this->tableRow($vrsticaF,1);
		$texStatusView .= $this->texNewLine;
		
		# preštejemo še neposlana vabila
		$str = "SELECT count(*) FROM srv_invitations_recipients WHERE ank_id='".$this->anketa['id']."' AND sent='0' AND deleted='0'";
		$qry = sisplet_query($str);
		list($cntUnsent) = mysqli_fetch_row($qry);
		$this->ss->userByStatus['invitation'][0] = (int)$cntUnsent; 
		
		# še email vabila
		foreach ($this->ss->invitationStatus as $status){
			$vrsticaG = array();
			if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['invitation'][$status] == 0)){// da ne delamo po neporebnem
				//$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :'), 0, 0, 'L', 0);
				//$this->pdf->Cell(45, 0, $this->encodeText($this->ss->userByStatus['invitation'][$status]), 0, 1, 'L', 0);
				$vrsticaG[] = $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :');
				$vrsticaG[] = $this->encodeText($this->ss->userByStatus['invitation'][$status]);
				$texStatusView .= $this->tableRow($vrsticaG,1);
				$cntInvitation++;
			}
		}		
	
		// vsota emaili
		if ($cntInvitation > 0 || !$this->ss->hideNullValues_status) {
			$vrsticaH = array();
/* 			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_invitation']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['invitation']), 'T', 1, 'L', 0);
			
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6'); */
			$vrsticaH[] = $this->encodeText($lang['srv_statistic_redirection_sum_invitation']);
			$vrsticaH[] = $this->encodeText($this->ss->cntUserByStatus['invitation']);
			$texStatusView .= $this->horizontalLineTex;
			$texStatusView .= $this->tableRow($vrsticaH,1);
			$texStatusView .= $this->texNewLine;
		}
		
		// testni podatki
		if ((int)$this->ss->testDataCount > 0) {
			$vrsticaI = array();
/* 			$this->pdf->setFont('','B','6');
			
			$this->pdf->Cell(90, 6, '', 'B', 1, 'L', 0);
			$this->pdf->setX($X);
			
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_test']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText((int)$this->ss->testDataCount), 'T', 1, 'L', 0);
			
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6'); */

			$vrsticaI[] = $this->encodeText($lang['srv_statistic_redirection_test']);
			$vrsticaI[] =  $this->encodeText((int)$this->ss->testDataCount);
			$texStatusView .= $this->horizontalLineTex;
			$texStatusView .= $this->tableRow($vrsticaI,1);
			$texStatusView .= $this->texNewLine;			
		}
		
		// Skupaj enot
		SurveySetting::getInstance()->setSID($this->anketa);
		$view_count = SurveySetting::getInstance()->getSurveyMiscSetting('view_count'); if ($view_count == "") $view_count = 0;
		
		if ($view_count > 0 || !$this->ss->hideNullValues_status){
			$vrsticaJ = array();
/* 			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_view']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($view_count), 'T', 1, 'L', 0);
			
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6'); */
			$vrsticaJ[] = $this->encodeText($lang['srv_statistic_redirection_sum_view']);
			$vrsticaJ[] = $this->encodeText($view_count);
			$texStatusView .= $this->horizontalLineTex;
			$texStatusView .= $this->tableRow($vrsticaJ,1);
			$texStatusView .= $this->texNewLine;	
		}
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic - konec
		
		//zaljucek latex tabele s podatki
		$texStatusView .= "\\end{".$pdfTable."}";
		//zaljucek latex tabele s podatki - konec	
		
		//izpis tabele v okvir
		$texText = $this->FrameText($title.$texStatusView);
		return $texText;
	 }
	
	/** Funkcija prikaže statuse odgovorov
	 * 
	 */
	 function DisplayAnswerStateView() {
	 	global $lang;
		
		$texAnswerStateView = '';
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_statistic_answer_state_title']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		//Priprava parametrov za tabelo s podatki o anketi
		$steviloStolpcevParameterTabular = 3;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
		$parameterTabular = '';
		$export_format = 'pdf';
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
			}else{
				$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');
			}
		}		
		
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$texAnswerStateView .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);
		
		//Priprava parametrov za tabelo s podatki o anketi - konec
		
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic
		
		//prva vrstica
		$prvaVrstica = array();
		$prvaVrstica[] = $this->encodeText($lang['srv_statistic_answer_state_status']);
		$prvaVrstica[] = $this->encodeText($lang['srv_statistic_answer_state_frequency']);
		$prvaVrstica[] = $this->encodeText($lang['srv_statistic_answer_state_percent']);
		$texAnswerStateView .= $this->tableRow($prvaVrstica,1);
		$texAnswerStateView .= $this->horizontalLineTex;
		
		
		$order = array('3ll','4ll','5ll',5,6);
		
	 	foreach ($order as $key) {
 			$vrstica2N = array();
			$vrstica2N[] = $this->encodeText($lang['srv_userstatus_'.$key]);
			$vrstica2N[] = $this->encodeText($this->ss->realUsersByStatus[$key]['cnt'] > 0 ? $this->ss->realUsersByStatus[$key]['cnt'] : '0');
			$vrstica2N[] = $this->encodeText( ((float)$this->ss->realUsersByStatus[$key]['percent'] > 1.0) ? '--' : $this->formatNumber($this->ss->realUsersByStatus[$key]['percent']*100,NUM_DIGIT_PERCENT,'%') );
			$texAnswerStateView .= $this->tableRow($vrstica2N,1);
	 	}
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic - konec
		
		//zaljucek latex tabele s podatki
		$texAnswerStateView .= "\\end{".$pdfTable."}";
		//zaljucek latex tabele s podatki - konec	
		
		//izpis tabele v okvir
		$texText = $this->FrameText($title.$texAnswerStateView);
		return $texText;
	 }
	 
	 
	/** Funkcija za prikaz referalov
	 * 
	 */
	function DisplayReferalsView() {
	 	global $lang;
	 	global $admin_type;
		
		$texReferalsView = '';
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_statistic_redirection_title']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);

		// izrisemo graf
		if ( ( $this->ss->cntValidRedirections + $this->ss->cntNonValidRedirections ) > 0) {
			$maxValue = $this->ss->maxRedirection * GRAPH_REDUCE;
			$value_sum = 0;
			
			//Priprava parametrov za tabelo s podatki o anketi
			$steviloStolpcevParameterTabular = 2;
			$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
			$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
			$parameterTabular = '';
			$export_format = 'pdf';
			//$parameterTabular = '|';
			
			for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
				$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
			}		
			
			$pdfTable = 'tabularx';
			$rtfTable = 'tabular';
			$pdfTableWidth = 1;
			$rtfTableWidth = 1;
			
			$texReferalsView .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);
			
			//Priprava parametrov za tabelo s podatki o anketi - konec
			
			
			//Priprava podatkov za izpis vrstic tabele in izpis vrstic
			
			//naslovna vrstica
			$naslovnaVrstica = array();
			$naslovnaVrstica[] = $this->encodeText($lang['srv_statistic_redirection_site']);
			$naslovnaVrstica[] = $this->encodeText($lang['srv_statistic_redirection_click']);
			$texReferalsView .= $this->tableRow($naslovnaVrstica);
			
			//pridobitev skupnega stevila klikov $value_sum za izris grafov
			if (count($this->ss->userRedirections["valid"])) {
				$lineCount = 0;
				foreach ($this->ss->userRedirections["valid"] as $key => $value) {
					$value_sum += $value;					
					$lineCount++;
				}
			}			
			// dodamo še direktni link
			if ($this->ss->userRedirections["direct"] > 0) {
				$value = $this->ss->userRedirections["direct"];
				$value_sum += $value;
			}			
			// dodamo še email klik
			if ($this->ss->userRedirections["email"] > 0) {
				$value = $this->ss->userRedirections["email"];
				$value_sum += $value;
			}			
			//pridobitev skupnega stevila klikov za izris grafov - konec
			
			if (count($this->ss->userRedirections["valid"])) {
				foreach ($this->ss->userRedirections["valid"] as $key => $value) {
					$vmesnaVrsticaA = array();	
					$vmesnaVrsticaA[] = $this->encodeText($key);
					if($this->encodeText($value)){	//ce vrednost ni nula
						$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$value_sum)*$this->encodeText($value);
						$vmesnaVrsticaA[] = $this->drawGraphLatex($graphLineLength, $this->encodeText($value));
					}else{
						$vmesnaVrsticaA[] = 0;
					}					
					$texReferalsView .= $this->tableRow($vmesnaVrsticaA,1);
				}
			}
			
			// dodamo še direktni link
			if ($this->ss->userRedirections["direct"] > 0) {
				$value = $this->ss->userRedirections["direct"];
				$vmesnaVrsticaB = array();	
				$vmesnaVrsticaB[] = $this->encodeText($lang['srv_statistic_redirection_direct']);
				if($this->encodeText($value)){	//ce vrednost ni nula
					$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$value_sum)*$this->encodeText($value);
					$vmesnaVrsticaB[] = $this->drawGraphLatex($graphLineLength, $this->encodeText($value));
				}else{
					$vmesnaVrsticaB[] = 0;
				}					
				$texReferalsView .= $this->tableRow($vmesnaVrsticaB,1);
			}	
			
			// dodamo še email klik
			if ($this->ss->userRedirections["email"] > 0) {
				$value = $this->ss->userRedirections["email"];
				$vmesnaVrsticaC = array();	
				$vmesnaVrsticaC[] = $this->encodeText($lang['srv_statistic_redirection_email']);
				if($this->encodeText($value)){	//ce vrednost ni nula
					$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$value_sum)*$this->encodeText($value);
					$vmesnaVrsticaC[] = $this->drawGraphLatex($graphLineLength, $this->encodeText($value));
				}else{
					$vmesnaVrsticaC[] = 0;
				}					
				$texReferalsView .= $this->tableRow($vmesnaVrsticaC,1);
			}
			
			// dodamo sumo
			$texReferalsView .= $this->horizontalLineTex;
			$vrsticaSuma = array();
			$vrsticaSuma[] = $this->encodeText($lang['srv_statistic_redirection_sum_clicked']);
			$vrsticaSuma[] = $this->encodeText($value_sum);
			$texReferalsView .= $this->tableRow($vrsticaSuma,1);
			
			// dodamo se neveljavne *******************************************
			//pridobitev skupnega stevila klikov $value_sum_nonvalid za izris grafov
			$value_sum_nonvalid = 0;
			for ($key = 2; $key >= 0; $key--) {
				$value = $this->ss->userRedirections["$key"];
				if ($value > 0) {
					$value_sum_nonvalid += $value;
				}
			}
			//pridobitev skupnega stevila klikov $value_sum_nonvalid za izris grafov - konec			

			for ($key = 2; $key >= 0; $key--) {
				$value = $this->ss->userRedirections["$key"];
				if ($value > 0) {
					$vrsticaNeveljavni = array();
					$vrsticaNeveljavni[] = $this->encodeText($lang['srv_userstatus_'.$key]);
					if($this->encodeText($value)){	//ce vrednost ni nula
						$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$value_sum)*$this->encodeText($value);
						$vrsticaNeveljavni[] = $this->drawGraphLatex($graphLineLength, $this->encodeText($value));
					}else{
						$vrsticaNeveljavni[] = 0;
					}					
					$texReferalsView .= $this->tableRow($vrsticaNeveljavni,1);
				}
			}
			// dodamo sumo
			if ($value_sum_nonvalid > 0 ) {
				$texReferalsView .= $this->horizontalLineTex;
				$vrsticaSumaNeveljavni = array();
				$vrsticaSumaNeveljavni[] = $this->encodeText($lang['srv_statistic_redirection_sum_nonvalid']);
				$vrsticaSumaNeveljavni[] = $this->encodeText($value_sum_nonvalid);
				$texReferalsView .= $this->tableRow($vrsticaSumaNeveljavni,1);
			}
			if (!($value_sum_nonvalid == 0 || $value_sum == 0 )) {
				$texReferalsView .= $this->horizontalLineTex;
				$vrsticaSumaNeveljavni = array();
				$vrsticaSumaNeveljavni[] = $this->encodeText($lang['srv_statistic_redirection_sum']);
				$vrsticaSumaNeveljavni[] = $this->encodeText($value_sum+$value_sum_nonvalid);
				$texReferalsView .= $this->tableRow($vrsticaSumaNeveljavni,1);
			}
			// dodamo se neveljavne - konec *******************************************
			
			//zaljucek latex tabele s podatki
			$texReferalsView .= "\\end{".$pdfTable."}";
			//zaljucek latex tabele s podatki - konec	
		}
		else {
			$texReferalsView .= $this->encodeText($lang['srv_statistic_show_no_referals']).$this->texNewLine;
			
		}
		
		//stevilo razlicnih IP stevilk
		$texReferalsView .= $this->texBigSkip.' ';
		//$texReferalsView .= $this->texNewLine;		
		$texReferalsView .= $this->encodeText($lang['srv_count_ip_list'].': '.count($this->ss->ip_list));
		
		if ($admin_type==0 && count($this->ss->ip_list) > 0) {
			$texReferalsView .= ' '.$this->texBigSkip;
			$texReferalsView .= $this->texNewLine;
			$titleTextIP = $this->encodeText($lang['srv_detail_ip_list']);
			$titleIP = $this->returnBoldAndRed($titleTextIP);
			$texReferalsView .= $this->returnBoldAndRed($titleIP).$this->texNewLine;
			
			//Priprava parametrov za tabelo s podatki o anketi
			$steviloStolpcevParameterTabular = 2;
			$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
			$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
			$parameterTabular = '';
			$export_format = 'pdf';
			
			for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
				$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
			}		
			
			$pdfTable = 'tabularx';
			$rtfTable = 'tabular';
			$pdfTableWidth = 1;
			$rtfTableWidth = 1;
			
			$texReferalsView .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);
			
			//Priprava parametrov za tabelo s podatki o anketi - konec			

			//Izpis vrstic
			foreach($this->ss->ip_list AS $key => $val) {
				$vrsticaIP = array();
				$vrsticaIP[] = $this->encodeText($val);
				$vrsticaIP[] = $this->encodeText($key);
				$texReferalsView .= $this->tableRow($vrsticaIP,1);
			}
			
			//zaljucek latex tabele s podatki IP
			$texReferalsView .= "\\end{".$pdfTable."}";
			//zaljucek latex tabele s podatki IP - konec
		}
		
		//izpis tabele in beedila v okvir
		$texText = $this->FrameText($title.$texReferalsView);
		
		return $texText;
	}

	 /** Funkcija prikaze statistike
	 * 
	 */
	function DisplayDateView() {	
		global $lang;
		
		$texDateView = '';
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_statistic_timeline_title']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);

		$this->ss->maxValue *= GRAPH_REDUCE;
		$cnt=0;
		
		if ($this->ss->arrayRange) {
			$lineCount = 0;
			
			//Priprava parametrov za tabelo s podatki o anketi
			$steviloStolpcevParameterTabular = 2;
			$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
			$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
			$parameterTabular = '';
			$export_format = 'pdf';
			
			for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
				if($i == 0){
					$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize=.40\hsize \centering\arraybackslash}X' : 'l');
				}else{
					$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
				}
			}		
			
			$pdfTable = 'tabularx';
			$rtfTable = 'tabular';
			$pdfTableWidth = 1;
			$rtfTableWidth = 1;
			
			$texDateView .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);
			
			//Priprava parametrov za tabelo s podatki o anketi - konec
			
			//pridobitev skupnega stevila enot $cnt za izris grafov
			foreach ($this->ss->arrayRange as $key => $value) {
				$cnt+=$value;				
			}
			//pridobitev skupnega stevila enot $cnt za izris grafov - konec
			
			
			//Priprava podatkov za izpis vrstic tabele in izpis vrstic			
			foreach ($this->ss->arrayRange as $key => $value) {			
				$label = $this->ss->formatStatsString($key, $this->ss->period);
				$vmesnaVrstica = array();	
				$vmesnaVrstica[] = $this->encodeText($label);
				//if($this->encodeText($value)){	//ce vrednost ni nula
				if($value){	//ce vrednost ni nula					
					$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$cnt)*$this->encodeText($value);
					//$vmesnaVrstica[] = $this->drawGraphLatex($graphLineLength, $this->encodeText($value));
					$vmesnaVrstica[] = $this->drawGraphLatex($graphLineLength, $value);
				}else{
					$vmesnaVrstica[] = 0;
				}					
				$texDateView .= $this->tableRow($vmesnaVrstica,1);
			}
			//Priprava podatkov za izpis vrstic tabele in izpis vrstic - konec
			
			// dodamo sumo
			$texDateView .= $this->horizontalLineTex;
			$vrsticaSuma = array();
			$vrsticaSuma[] = $this->encodeText($lang['srv_statistic_redirection_sum']);
			$vrsticaSuma[] = $this->encodeText($cnt);
			$texDateView .= $this->tableRow($vrsticaSuma,1);
			
			//zaljucek latex tabele s podatki
			$texDateView .= "\\end{".$pdfTable."}";
			//zaljucek latex tabele s podatki - konec			
		} 
		else {
			$texDateView .= $this->encodeText($lang['srv_no_data']).$this->texNewLine;
		}
		
		//izpis tabele in beedila v okvir
		$texText = $this->FrameText($title.$texDateView);
		
		return $texText;
	}
	 
	/** Funkcija za prikaz klikov po straneh
	 * 
	 */
	 function DisplayPagesStateView() {
	 	global $lang;
		
		$texPagesStateView = '';

		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_statistic_pages_state_title']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		//ali lovimo samo strani ki niso bile preskočene
	 	$grupa_jump = "AND ug.preskocena = 0 ";
	 			
		$sql = "SELECT g.id, g.naslov, COUNT(ug.usr_id) cnt FROM srv_grupa g".
				" LEFT JOIN (SELECT * FROM srv_user_grupa".$this->ss->db_table." ug WHERE". 
				" ug.time_edit BETWEEN '".$this->ss->startDate."' AND '".$this->ss->endDate."' + INTERVAL 1 DAY ".$grupa_jump.") as ug ON g.id = ug.gru_id".
		 		" WHERE g.ank_id = '".$this->ss->getSurveyId()."' GROUP BY g.id ORDER BY g.vrstni_red";
	 	
	 	$qry = sisplet_query($sql);
	 	$pages=array();
	 	$maxValue = 0;
	 	while ($row = mysqli_fetch_assoc($qry)) {
	 		$pages[$row['id']] = array('naslov'=>$row['naslov'],'cnt'=>$row['cnt']);
	 		$maxValue = max($maxValue, $row['cnt']);
	 	}

 	 	$maxValue = max($maxValue, $this->ss->realUsersByStatus['3ll']['cnt']);		
	 	$maxValue = $maxValue * GRAPH_REDUCE;
		
		//Priprava parametrov za tabelo s podatki o anketi
		$steviloStolpcevParameterTabular = 2;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
		$parameterTabular = '';
		$export_format = 'pdf';
		//$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
		}		
		
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$texPagesStateView .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);
		
		//Priprava parametrov za tabelo s podatki o anketi - konec
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic		
		//naslovna vrstica
		$naslovnaVrstica = array();
		$naslovnaVrstica[] = $this->encodeText($lang['srv_statistic_answer_state_status']);
		$naslovnaVrstica[] = $this->encodeText($lang['srv_statistic_redirection_click']);
		$texPagesStateView .= $this->tableRow($naslovnaVrstica);

		# status 3 - "Klik na anketo"
		$value = $this->ss->realUsersByStatus['3ll']['cnt'];
		$texPagesStateView .= $this->displayStatusLine($this->encodeText($lang['srv_userstatus_3']), $this->encodeText($value), $maxValue);
		
		# status 4 - "Klik na prvo stran"
		$value = $this->ss->realUsersByStatus['4ll']['cnt'];
		$texPagesStateView .= $this->displayStatusLine($this->encodeText($lang['srv_userstatus_4']), $this->encodeText($value), $maxValue);
		
		# status 5 - "Zacel izpolnjevati",
		$value = $this->ss->realUsersByStatus[5]['cnt'];
		$texPagesStateView .= $this->displayStatusLine($this->encodeText($lang['srv_userstatus_5']), $this->encodeText($value), $maxValue);
				
		$texPagesStateView .= $this->horizontalLineTex;	//horizontalna crta
		$texPagesStateView .= $this->texNewLine;	//prazna crta
		
		#strani
		foreach ($pages as $key => $page) {
			$value = $page['cnt'];
			$texPagesStateView .= $this->displayStatusLine($this->encodeText($page['naslov']), $this->encodeText($value), $maxValue);
		}
		
		$texPagesStateView .= $this->horizontalLineTex;	//horizontalna crta
		$texPagesStateView .= $this->texNewLine;	//prazna crta

		# status 6 - "Koncal",
		$value6 = $this->ss->realUsersByStatus[6]['cnt'];
		$texPagesStateView .= $this->displayStatusLine($this->encodeText($lang['srv_userstatus_6']), $this->encodeText($value6), $maxValue);

		#če imamo lurkerje 6l dodamo skupaj konačal anketo (to je 6 + 6l) in nato še koliko jih je samo s statusom 6 (končal anketo)
		# status 6l - "Koncal - lurker", izpišemo samo če obstajajo 6l
		$lurkerjev = $this->ss->realUsersByStatus['6ll']['cnt'] - $value6;
		if ($lurkerjev > 0) {
			$valueall = $this->ss->realUsersByStatus['6ll']['cnt'] ;
			
			# končal s tem da je lurker (6l)
			$texPagesStateView .= $this->displayStatusLine($this->encodeText($lang['srv_userstatus_6l']), $this->encodeText($lurkerjev), $maxValue);
			
			#črta
			$texPagesStateView .= $this->horizontalLineTex;	//horizontalna crta
			
			# končal ne glede na to ali je lurker
			$texPagesStateView .= $this->displayStatusLine($this->encodeText($lang['srv_userstatus_all']), $this->encodeText($valueall), $maxValue);
		}
		
		//zaljucek latex tabele s podatki
		$texPagesStateView .= "\\end{".$pdfTable."}";
		//zaljucek latex tabele s podatki - konec	
		
		//izpis tabele in beedila v okvir
		$texText = $this->FrameText($title.$texPagesStateView);
		return $texText;
	}
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text='', $length=64, $tail="...")
	{
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length)
		{
			for($i=1;$text[$length-$i]!=" ";$i++)
			{
				if($i == $length)
				{
					return substr($text,0,$length) . $tail;
				}
			}
		$text = substr($text,0,$length-$i+1) . $tail;
		}
		return $text;
	}

	function drawLine()
	{
		$cy = $this->pdf->getY();
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
	}

	function setUserId($usrId=null) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

	function formatNumber($value=null, $digit=0, $sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
	
		#moja funkcija encodeText
	function encodeText($text=''){
		// popravimo sumnike ce je potrebno
		//$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		//$text = str_replace("&scaron;","š",$text);
		//echo "Encoding ".$text."</br>";
		if($text == ''){	//ce ni teksta, vrni se
			return;			
		}
		$textOrig = $text;
		$findme = '<br />';
		$findmeLength = strlen($findme);
		$findImg = '<img';
		$findImgLength = strlen($findImg);
		
		$pos = strpos($text, $findme);
		$posImg = strpos($text, $findImg);
		
		//ureditev izrisa slike
		if($posImg !== false){
			$numOfImgs = substr_count($text, $findImg);	//stevilo '<br />' v tekstu
			$posImg = strpos($text, $findImg);
			$textPrej = '';
			$textPotem = '';				
			for($i=0; $i<$numOfImgs; $i++){					
				$posImg = strpos($text, $findImg);
				$textPrej = substr($text, 0, $posImg);	//tekst do img
				$textPotem = substr($text, $posImg);	//tekst po img, z vkljuceno hmlt kodo z img
				$posImgEnd = strpos($textPotem, '/>');	//pozicija, kjer se konca html koda za img
				$textPotem = substr($textPotem, $posImgEnd+strlen('/>'));	//tekst od konca html kode za img dalje
				
				$text = $textPrej.' '.PIC_SIZE_ANS."{".$this->getImageName($text, 0, '<img')."}".' '.$textPotem;
				//$text2Return = $textPrej.' '.PIC_SIZE_ANS."{".$this->getImageName($text2Return, 0, 'img')."}".' '.$textPotem;				
			}
			
			//pred ureditvijo posebnih karakterjev, odstrani del teksta s kodo za sliko, da se ne pojavijo tezave zaradi imena datoteke od slike
			$findImgCode = '\includegraphics';
			$posOfImgCode = strpos($text, $findImgCode);
			//echo $posOfImgCode."</br>";
			$textToImgCode = substr($text, 0, $posOfImgCode);	//tekst do $findImgCode
			//echo $textToImgCode."</br>";
			$textFromImgCode = substr($text, $posOfImgCode);	//tekst po $findImgCode
			//echo $textFromImgCode."</br>";
			$findImgCodeEnd = '}';
			//$posOfImgCodeEnd = strpos($text,  $findImgCodeEnd);
			$posOfImgCodeEnd = strpos($textFromImgCode, $findImgCodeEnd);
			//echo $posOfImgCodeEnd."</br>";
			$textAfterImgCode = substr($textFromImgCode, $posOfImgCodeEnd+1);	//tekst po $findImgCodeEnd
			//echo $textAfterImgCode."</br>";
			$textOfImgCode = substr($text, $posOfImgCode, $posOfImgCodeEnd+1);
			//echo $textOfImgCode."</br>";
			
			$text = $textToImgCode.$textAfterImgCode;
			
			//pred ureditvijo posebnih karakterjev, odstrani del teksta s kodo za sliko, da se ne pojavijo tezave zaradi imena datoteke od slike - konec
		}
		//ureditev izrisa slike - konec	
		
		//ureditev posebnih karakterjev za Latex	http://www.cespedes.org/blog/85/how-to-escape-latex-special-characters, https://en.wikibooks.org/wiki/LaTeX/Special_Characters#Other_symbols
		$text = str_replace('\\','\textbackslash{} ',$text);
		//$text = str_replace('{','\{',$text);		
		//$text = str_replace('}','\}',$text);	
		$text = str_replace('$','\$ ',$text);
		$text = str_replace('#','\# ',$text);
		$text = str_replace('%','\% ',$text);		
		$text = str_replace('€','\euro',$text);		
		$text = str_replace('^','\textasciicircum{} ',$text);		
		//$text = str_replace('_','\_ ',$text);	
		$text = str_replace('_','\_',$text);	
		$text = str_replace('~','\textasciitilde{} ',$text);		
		$text = str_replace('&amp;','\&',$text);
		$text = str_replace('&','\&',$text);
		//$text = str_replace('&lt;','\textless ',$text);
		$text = str_replace('&lt;','\textless',$text);
		//$text = str_replace('&gt;','\textgreater ',$text);
		$text = str_replace('&gt;','\textgreater',$text);
		$text = str_replace('&nbsp;',' ',$text);
		//ureditev posebnih karakterjev za Latex - konec
		
		//po ureditvi posebnih karakterjev, dodati del teksta s kodo za sliko, ce je slika prisotna
		if($posImg !== false){
			$text = substr_replace($text, $textOfImgCode, $posOfImgCode, 0);
		}
		//po ureditvi posebnih karakterjev, dodati del teksta s kodo za sliko, ce je slika prisotna		

 		if($pos === false && $posImg === false) {	//v tekstu ni br in img
			//return $text;
/* 			echo "encode pred strip: ".$text."</br>";
			echo "encode po strip: ".strip_tags($text)."</br>";			
			return strip_tags($text); */
		}else {	//v tekstu sta prisotna br ali img
			$text2Return = '';	//tekst ki bo vrnjen
			
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico
			if($pos !== false){
				$pos = strpos($text, $findme);
				$numOfBr = substr_count($text, $findme);	//stevilo '<br />' v tekstu
				for($i=0; $i<$numOfBr; $i++){
					if($i == 0){	//ce je prvi najdeni '<br />'
						$textPrej = substr($text, 0, $pos);
						$textPotem = substr($text, $pos+$findmeLength);
						if($i == $numOfBr-1){
							$text2Return .= $textPrej.' \break '.$textPotem;
						}else{
							$text2Return .= $textPrej.' \break ';
						}
					}else{	//drugace
						$pos = strpos($textPotem, $findme);
						$textPrej = substr($textPotem, 0, $pos);
						$textPotem = substr($textPotem, $pos+$findmeLength);
						if($i == $numOfBr-1){
							$text2Return .= $textPrej.' \break '.$textPotem;
						}else{
							$text2Return .= $textPrej.' \break ';
						}
					}
				}
				$text = $text2Return;
			}			
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico - konec
/* 			echo "encode pred strip: ".$text."</br>";
			echo "encode po strip: ".strip_tags($text)."</br>";
			return strip_tags($text);	//vrni tekst brez html tag-ov */
		}
		
		//preveri, ce je url v besedilu (http:// ... ) in uredi Latex izpis le-tega
		$findHttp = 'http://';
		$findHttps = 'https://';
		$posHttp = strpos($text, $findHttp);
		$posHttps = strpos($text, $findHttps);
		
 		if($posHttp !== false || $posHttps !== false) {	//v imamo URL naslov
			$space = ' ';
			if($posHttp !== false){
				$text = substr_replace($text, $space, ($posHttp+7), 0);
			}elseif($posHttps !== false){
				$text = substr_replace($text, $space, ($posHttps+8), 0);
			}
		}
		//preveri, ce je url v besedilu (http:// ... ) in uredi Latex izpis le-tega - konec

		return strip_tags($text); //vrni tekst brez html tag-ov
	}
	
	function returnBold($text=''){
		$boldedText = '';
		$boldedText .= '\textbf{'.$text.'}';
		return $boldedText;
	}
	
	function returnBoldAndRed($text=''){
		//$this->naslovnicaUkaz .= ' {\\textcolor{red}{'.$lang['srv_survey_non_active1'].'}} \\\\';
		$tex = '';
		$tex .= ' {\\textcolor{red}{'.$text.'}} ';
		return $tex;
	}
	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele ##################################################################################
	#argumenti 1. export_format, 2. parametri tabele, 3. tip tabele za pdf, 4. tip tabele za rtf, 5. sirina pdf tabele (delez sirine strani), 6. sirina rtf tabele (delez sirine strani)
	function StartLatexTable($export_format='', $parameterTabular='', $pdfTable='', $rtfTable='', $pdfTableWidth=null, $rtfTableWidth=null){
		$tex = '';
		//$tex .= '\keepXColumns';
 		if($export_format == 'pdf'){
			$tex .= '\begin{'.$pdfTable.'}';
			if($pdfTable=='tabularx'){
				//$tex .= '{'.$pdfTableWidth.'\textwidth}';
				$tex .= '{\hsize}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}elseif($export_format == 'rtf'){
			$tex .= '\begin{'.$rtfTable.'}';
			if($rtfTable=='tabular*'){
				$tex .= '{'.$pdfTableWidth.'\textwidth}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}	
		return $tex;
	}	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele - konec ##########################################################################
	
	//omogoca izpis okvirja z dolocene sirine in visine s tekstom dolocene sirine
	function FrameText($text=''){
		$framedText = '';
		//$framedText .= '\framebox('.FRAME_WIDTH.','.FRAME_HEIGTH.'){ \parbox[t]{'.FRAME_TEXT_WIDTH.'\textwidth}{'.$text.'} }';
		$framedText .= '\framebox('.FRAME_WIDTH.','.FRAME_HEIGTH.')[t]{ \parbox[t]{'.FRAME_TEXT_WIDTH.'\textwidth}{'.$this->texSmallSkip.$text.'} }';		
		return $framedText;		
	}
		
	function tableRow($arrayText=[], $brezHline=0){
		$tableRow = '';
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = 1; //$height = $this->getCellHeight($this->encodeText($arrayText[1]), 90);

		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
				//echo "velikost polja s tekstom: ".count($arrayText)."</br>";
		
		for($i=0;$i<count($arrayText);$i++){
			//echo "array text: ".$arrayText[$i]."</br>";
			$text = $arrayText[$i];
			if($i==0){
				$tableRow .= $text;
			}else{
				$tableRow .= ' & '.$text;
			}
		}

		$tableRow .= $this->texNewLine;	/*nova vrstica*/
		
		if (!$brezHline) {	//dodaj se horizontal line, ce je to potrebno (po navadi vse povsod razen npr. za tabelo s st. odklonom in povprecjem)
			$tableRow .= $this->horizontalLineTex; /*obroba*/
		}
		
		//echo "Vrstica tabele: ".$tableRow."</br>";
		
		return $tableRow;
	}
	
	//funkcija, ki skrbi za izris grafa ustrezne dolzine
	function drawGraphLatex($graphLineLength=null, $value=null){
		$texGraph = '';	
		$texGraph .= '\begin{tikzpicture} \fill[crtaGraf] (0,0) -- ('.$graphLineLength.',0) -- ('.$graphLineLength.','.GRAPH_LINE_WIDTH.') -- (0,'.GRAPH_LINE_WIDTH.') -- (0,0); \end{tikzpicture} '.$value;		
		return $texGraph;		
	}
	
	function displayStatusLine($text='', $value=null, $maxValue=null){
		$texStatusLine = '';
		$vrsticaPodatki = array();
		$vrsticaPodatki[] = $text;
		if($value){	//ce vrednost ni nula
			$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$maxValue)*$value;
			$vrsticaPodatki[] = $this->drawGraphLatex($graphLineLength, $value);
		}else{
			$vrsticaPodatki[] = 0;
		}					
		$texStatusLine .= $this->tableRow($vrsticaPodatki,1);		
		
		return $texStatusLine;
	}
	
}


?>