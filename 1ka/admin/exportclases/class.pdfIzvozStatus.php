<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.enka.pdf.php');
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2, true); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2, true); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);

	define("FNT_FREESERIF", "freeserif", true);
	define("FNT_FREESANS", "freesans", true);
	define("FNT_HELVETICA", "helvetica", true);

	define("FNT_MAIN_TEXT", FNT_FREESANS, true);
	define("FNT_QUESTION_TEXT", FNT_FREESANS, true);
	define("FNT_HEADER_TEXT", FNT_FREESANS, true);

	define("FNT_MAIN_SIZE", 10, true);
	define("FNT_QUESTION_SIZE", 9, true);
	define("FNT_HEADER_SIZE", 10, true);

	define("RADIO_BTN_SIZE", 3, true);
	define("CHCK_BTN_SIZE", 3, true);
	define("LINE_BREAK", 6, true);

	define ('PDF_MARGIN_HEADER', 8);
	define ('PDF_MARGIN_FOOTER', 12);
	define ('PDF_MARGIN_TOP', 18);
	define ('PDF_MARGIN_BOTTOM', 18);
	define ('PDF_MARGIN_LEFT', 15);
	define ('PDF_MARGIN_RIGHT', 15);
	

/** Class za generacijo pdf-a
 *
 * @desc: po novem je potrebno form elemente generirati ro�no kot slike
 *
 */
class PdfIzvozStatus {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public static $ss = null;		//SurveyStatistic class
	public static $sas = null;		//		$sas = new SurveyAdminSettings();class

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $ssData)
	{
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->anketa['podstran'] = $podstran;
			// create new PDF document
			$this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
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
	function getFile($fileName)
	{
		//Close and output PDF document		
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}


	function init()
	{
		global $lang;
		
		// array used to define the language and charset of the pdf file to be generated
		$language_meta = Array();
		$language_meta['a_meta_charset'] = 'UTF-8';
		$language_meta['a_meta_dir'] = 'ltr';
		$language_meta['a_meta_language'] = 'sl';
		$language_meta['w_page'] = $lang['page'];

		//set some language-dependent strings
	    $this->pdf->setLanguageArray($language_meta);

		//set margins
		$this->pdf->setPrintHeaderFirstPage(true);
		$this->pdf->setPrintFooterFirstPage(true);
		$this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set header and footer fonts
		$this->pdf->setHeaderFont(Array(FNT_HEADER_TEXT, "I", FNT_HEADER_SIZE));
		$this->pdf->setFooterFont(Array(FNT_HEADER_TEXT, 'I', FNT_HEADER_SIZE));


		// set document information
		$this->pdf->SetAuthor('An Order Form');
		$this->pdf->SetTitle('An Order');
		$this->pdf->SetSubject('An Order');

		// set default header data
		$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()));

		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$this->pdf->SetFont(FNT_MAIN_TEXT, '', FNT_MAIN_SIZE);
		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		return true;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function createPdf()
	{
		global $site_path;
		global $lang;
			   		
		// izpisemo prvo stran
		//$this->createFrontPage();
	   		
		$this->pdf->AddPage();
		
		$this->pdf->setFont('','B','11');
		$this->pdf->MultiCell(150, 5, 'Status', 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
		
		$this->pdf->setDrawColor(0, 0, 0, 255);
		$this->pdf->setFont('','','6');
		
		$this->display();
	}	

	public function display() {
		global $lang;		
		
		// imamo vnose, prikažemo statistiko
		$this->ss->PrepareDateView();
		$this->ss->PrepareStatusView();

		$this->pdf->setDrawColor(200, 200, 200);
		$this->pdf->setFillColor(150, 220, 150);
		
		// zgornji boxi
		$this -> DisplayInfoView();
		$this -> DisplayStatusView();
		$this -> DisplayAnswerStateView();
		
		// spodnji boxi
		$this -> DisplayReferalsView();
		$this -> DisplayDateView();
		$this -> DisplayPagesStateView();
	}	
	
	
	
	/** Funkcija prikaze osnovnih informacij
	 * 
	 */
	function DisplayInfoView() {
		global $lang;
		global $site_url;

		$X = 10;
		$Y = 25;
		$height = 80;
		$this->pdf->setXY($X, $Y);		
		
		//izrisemo okvir
		$this->pdf->setFont('','B','8');
		$this->pdf->SetTextColor(180, 0, 0);
		$this->pdf->MultiCell(90, $height, $this->encodeText($lang['srv_statistic_info_title']), 1, 'L', 0, 0, 0 ,0, true);
		
		$this->pdf->setXY($X, $Y+8);
		$this->pdf->setFont('','','6');
		$this->pdf->SetTextColor(0, 0, 0);
		
		//ime ankete
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_name'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(70, 3, $this->encodeText(SurveyInfo::getSurveyTitle()), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
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
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_type'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_vrsta_survey_type_'.SurveyInfo::getSurveyType()] . ($enabled_advanced != null ? ' ('.$enabled_advanced.')' : '' )), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		//vprašanj, variabel
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_questions'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(15, 3, $this->encodeText(SurveyInfo::getSurveyQuestionCount()), 0, 0, 'L', 0);
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_variables'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(15, 3, $this->encodeText(SurveyInfo::getSurveyVariableCount()), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		//uporabnikov, odgovorov
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_analiza_stUporabnikov'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(15, 3, $this->encodeText(SurveyInfo::getSurveyAnswersCount()), 0, 0, 'L', 0);
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_answers_valid'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(15, 3, $this->encodeText(SurveyInfo::getSurveyApropriateAnswersCount()), 0, 1, 'L', 0);
		$this->pdf->setX($X);
	
		//jezik ankete
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_language'].':'), 0, 0, 'L', 0);
		$this->pdf->Cell(70, 3, $this->encodeText(SurveyInfo::getRespondentLanguage()), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		//avtor
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_creator'].':'), 0, 0, 'L', 0);
		$text = '';
		$text .= SurveyInfo::getSurveyInsertName();
		if (SurveyInfo::getSurveyInsertDate() && SurveyInfo::getSurveyInsertDate() != "00.00.0000")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyInsertDate(),DATE_FORMAT_SHORT);
		if (SurveyInfo::getSurveyInsertTime() && SurveyInfo::getSurveyInsertTime() != "00:00:00")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyInsertTime(),TIME_FORMAT_SHORT);
		$this->pdf->Cell(70, 3, $this->encodeText($text), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		//spreminjal
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_modify'].':'), 0, 0, 'L', 0);
		$text = '';
		$text .= SurveyInfo::getSurveyEditName();
		if (SurveyInfo::getSurveyEditDate() && SurveyInfo::getSurveyEditDate() != "00.00.0000")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyEditDate(),DATE_FORMAT_SHORT);
		if (SurveyInfo::getSurveyEditTime() && SurveyInfo::getSurveyEditTime() != "00:00:00")
			$text .= SurveyInfo::getDateTimeSeperator() . $this->ss->dateFormat(SurveyInfo::getSurveyEditTime(),TIME_FORMAT_SHORT);
		$this->pdf->Cell(70, 3, $this->encodeText($text), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		//dostop, Kdo razen avtorja ima dostop
		$dostop = SurveyInfo::getSurveyAccessUsers();
		if ($dostop) {
			$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_access'].':'), 0, 0, 'L', 0);
			$prefix='';
			foreach ( $dostop as $user) {
				$prefix .= $user['name'].'; ';
			}
			$prefix = substr($prefix, 0, -2);
			$this->pdf->Cell(70, 3, $this->encodeText($prefix), 0, 1, 'L', 0);
			$this->pdf->setX($X);
		}
		
		//aktivnost
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_displaydata_status'].':'), 0, 0, 'L', 0);
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			$this->pdf->SetTextColor(0,150,0);
			$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_anketa_active2']), 0, 1, 'L', 0);
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				$this->pdf->SetTextColor(255,120,0);
				$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_survey_non_active_notActivated']), 0, 1, 'L', 0);
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				$this->pdf->SetTextColor(255,120,0);
				$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_survey_non_active']), 0, 1, 'L', 0);
			}
		}
		$this->pdf->SetTextColor(0);
		$this->pdf->setX($X);
		
		//trajanje: datumi aktivnosti
		if ( count($activity) > 0 ) {
			$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_activity'].':'), 0, 0, 'L', 0);
			$prefix = '';
			foreach ($activity as $active) {
				$_starts = explode('-',$active['starts']);
				$_expire = explode('-',$active['expire']);

				$prefix .= $_starts[2].'.'.$_starts[1].'.'.$_starts[0].'-'.$_expire[2].'.'.$_expire[1].'.'.$_expire[0];
				$prefix .= '; ';
			}
			$this->pdf->Cell(70, 3, $this->encodeText($prefix), 0, 1, 'L', 0);
			$this->pdf->setX($X);
		}

		
		# predviceni cas trajanja enkete
		$skupni_cas = $this->sas->testiranje_cas(1);
		$skupni_predvideni_cas = $this->sas->testiranje_predvidenicas(1);	
		
		$d = new Dostop();
		
		//predviceni cas trajanja enkete
		$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_duration'].':'), 0, 0, 'L', 0);		
		$text = '';
		$text .= ($skupni_cas != '') ? $skupni_cas.', ' : '';
		$text .= $lang['srv_predvideno'].': '.$skupni_predvideni_cas;	
		$this->pdf->Cell(70, 3, $this->encodeText($text), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		//VNOSI - pvi / zadnji vnos
		$prvi_vnos_date = SurveyInfo::getSurveyFirstEntryDate();
		$prvi_vnos_time = SurveyInfo::getSurveyFirstEntryTime();
		$zadnji_vnos_date = SurveyInfo::getSurveyLastEntryDate();
		$zadnji_vnos_time = SurveyInfo::getSurveyLastEntryTime();
		if ($prvi_vnos_date != null) {
			$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_first_entry'].':'), 0, 0, 'L', 0);

			$text = '';
			$text .= $this->ss->dateFormat($prvi_vnos_date,DATE_FORMAT_SHORT);
			$text .= $prvi_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->ss->dateFormat($prvi_vnos_time,TIME_FORMAT_SHORT)) : '';
			$this->pdf->Cell(15, 3, $this->encodeText($text), 0, 0, 'L', 0);
		}
		if ($zadnji_vnos_date != null) {
			$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_info_last_entry'].':'), 0, 0, 'L', 0);

			$text = '';
			$text .= $this->ss->dateFormat($zadnji_vnos_date,DATE_FORMAT_SHORT);
			$text .= $zadnji_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->ss->dateFormat($zadnji_vnos_time,TIME_FORMAT_SHORT)) : '';
			$this->pdf->Cell(15, 3, $this->encodeText($text), 0, 0, 'L', 0);
		}
		$this->pdf->Cell(0, 0, '', 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		// Komentarji
		$SD = new SurveyDiagnostics($this->anketa['id']);
		$comments = $SD->testComments();
		
		list($commentsAll,$commentsUnresolved,$commentsQuestionAll,$commentsQuestionUnresolved,$commentsUser,$commentsUserFinished) = $comments;		
		
		$commentsUserUnresolved = $commentsUser - $commentsUserFinished;
		if ((	(int)$commentsAll
				+(int)$commentsUnresolved
				+(int)$commentsQuestionAll
				+(int)$commentsQuestionUnresolved
				+(int)$commentsUser
				+(int)$commentsUserFinished
				) > 0 ) {
			
			$this->pdf->Cell(20, 3, $this->encodeText($lang['srv_diagnostic_4_element_0'].':'), 0, 0, 'L', 0);
			$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_diagnostic_4_element_1'].': '.(int)$commentsAll.' / '.(int)$commentsUnresolved), 0, 1, 'L', 0);		
			$this->pdf->setX($X);
			
			$this->pdf->Cell(20, 3, $this->encodeText(''), 0, 0, 'L', 0);
			$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_diagnostic_4_element_6'].': '.(int)$commentsQuestionAll.' / '.(int)$commentsQuestionUnresolved), 0, 1, 'L', 0);
			$this->pdf->setX($X);
			
			$this->pdf->Cell(20, 3, $this->encodeText(''), 0, 0, 'L', 0);
			$this->pdf->Cell(70, 3, $this->encodeText($lang['srv_diagnostic_4_element_7'].': '.(int)$commentsUser.' / '.(int)$commentsUserUnresolved), 0, 1, 'L', 0);
			$this->pdf->setX($X);
		}
	}

	/** Funkcija prikaže statuse
	 * 
	 */
	 function DisplayStatusView() {
	 	global $lang;
		
		$X = 105;
		$Y = 25;	
		$height	= 80;
		$this->pdf->setXY($X, $Y);
				
		//izrisemo okvir
		$this->pdf->setFont('','B','8');
		$this->pdf->SetTextColor(180, 0, 0);
		$this->pdf->MultiCell(90, $height, $this->encodeText($lang['srv_statistic_status_title']), 1, 'L', 0, 0, 0 ,0, true);
		
		$this->pdf->setXY($X, $Y+8);
		$this->pdf->setFont('','','6');
		$this->pdf->SetTextColor(0, 0, 0);
		
		$cntValid = 0; // da vemo ali izpisemo skupne
		$cntNonValid = 0; // da vemo ali izpisemo skupne
		
		foreach ($this->ss->appropriateStatus as $status) {
			if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['valid'][$status] == 0)) {// da ne delamo po neporebnem
		 		$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :'), 0, 0, 'L', 0);
				$this->pdf->Cell(45, 0, $this->encodeText($this->ss->userByStatus['valid'][$status]), 0, 1, 'L', 0);
				
				$cntValid++;
				$this->pdf->setX($X);
			}
		}
		
		// vsota vlejavnih
		if ($cntValid > 0 || !$this->ss->hideNullValues_status) {
			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_valid']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['valid']), 'T', 1, 'L', 0);
			
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6');
		} 
			
		// izpišemo še neveljavne
		foreach ($this->ss->unAppropriateStatus as $status) {
			if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['nonvalid'][$status] == 0)) {// da ne delamo po neporebnem
				$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :'), 0, 0, 'L', 0);
				$this->pdf->Cell(45, 0, $this->encodeText($this->ss->userByStatus['nonvalid'][$status]), 0, 1, 'L', 0);
				
				$cntNonValid++;
				$this->pdf->setX($X);
			}
		}
		// se status null (neznan status)
		if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['nonvalid'][-1] == 0)) {// da ne delamo po neporebnem
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_null']), 0, 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText(isset($this->ss->userByStatus['nonvalid'][-1]) ? $this->ss->userByStatus['nonvalid'][-1] : '0'), 0, 1, 'L', 0);
			
			$cntNonValid++;
			$this->pdf->setX($X);
		}
		
		// vsota nevlejavnih 
		if ($cntNonValid > 0 || !$this->ss->hideNullValues_status) {		
			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_nonvalid']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['nonvalid']), 'T', 1, 'L', 0);
			
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6');
		}
		$this->pdf->setFont('','B','6');
		$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum']), 'T', 0, 'L', 0);
		$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['valid']+$this->ss->cntUserByStatus['nonvalid']), 'T', 1, 'L', 0);
		$this->pdf->setFont('','','6');
		
		$this->pdf->setX($X);	
		
		# preštejemo še neposlana vabila
		$str = "SELECT count(*) FROM srv_invitations_recipients WHERE ank_id='".$this->anketa['id']."' AND sent='0' AND deleted='0'";
		$qry = sisplet_query($str);
		list($cntUnsent) = mysqli_fetch_row($qry);
		$this->ss->userByStatus['invitation'][0] = (int)$cntUnsent; 
		
		# še email vabila
		foreach ($this->ss->invitationStatus as $status) 
		{
			if (!($this->ss->hideNullValues_status && $this->ss->userByStatus['invitation'][$status] == 0)) 
			{// da ne delamo po neporebnem
				//echo '<span class="dashboard_status_span">' . $lang['srv_userstatus_'.$status] . ' ('.$status.') :</span>' . $this->ss->userByStatus['invitation'][$status].'<br/>';
				$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_userstatus_'.$status] . ' ('.$status.') :'), 0, 0, 'L', 0);
				$this->pdf->Cell(45, 0, $this->encodeText($this->ss->userByStatus['invitation'][$status]), 0, 1, 'L', 0);

				$cntInvitation++;
				$this->pdf->setX($X);
			}
		}
	
		// vsota emaili
		if ($cntInvitation > 0 || !$this->ss->hideNullValues_status) {
			//echo '<div class="anl_dash_bt full strong"><span class="dashboard_status_span">'.$lang['srv_statistic_redirection_sum_invitation'].'</span>'.($this->cntUserByStatus['invitation']).'<br/></div><br/>';
			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_invitation']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($this->ss->cntUserByStatus['invitation']), 'T', 1, 'L', 0);
			
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6');
		}
		
		// testni podatki
		if ((int)$this->ss->testDataCount > 0) {
			$this->pdf->setFont('','B','6');
			
			$this->pdf->Cell(90, 6, '', 'B', 1, 'L', 0);
			$this->pdf->setX($X);
			
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_test']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText((int)$this->ss->testDataCount), 'T', 1, 'L', 0);
			
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6');
		}
		
		// Skupaj enot
		SurveySetting::getInstance()->setSID($this->anketa);
		$view_count = SurveySetting::getInstance()->getSurveyMiscSetting('view_count'); if ($view_count == "") $view_count = 0;
		
		if ($view_count > 0 || !$this->ss->hideNullValues_status){
			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(45, 0, $this->encodeText($lang['srv_statistic_redirection_sum_view']), 'T', 0, 'L', 0);
			$this->pdf->Cell(45, 0, $this->encodeText($view_count), 'T', 1, 'L', 0);
			
			$this->pdf->setX($X);
			$this->pdf->setFont('','','6');
		}
	 }
	
	/** Funkcija prikaže statuse odgovorov
	 * 
	 */
	 function DisplayAnswerStateView() {
	 	global $lang;
		
		$X = 200;
		$Y = 25;
		$height	= 80;
		$this->pdf->setXY($X, $Y);	
		
		//izrisemo okvir
		$this->pdf->setFont('','B','8');
		$this->pdf->SetTextColor(180, 0, 0);
		$this->pdf->MultiCell(90, $height, $this->encodeText($lang['srv_statistic_answer_state_title']), 1, 'L', 0, 0, 0 ,0, true);
		
		$this->pdf->setXY($X, $Y+8);
		$this->pdf->setFont('','B','6');
		$this->pdf->SetTextColor(0, 0, 0);
		
		
		$order = array('3ll','4ll','5ll',5,6);

	 	$this->pdf->Cell(40, 0, $this->encodeText($lang['srv_statistic_answer_state_status']), 'B', 0, 'L', 0);
		$this->pdf->Cell(25, 0, $this->encodeText($lang['srv_statistic_answer_state_frequency']), 'BLR', 0, 'C', 0);
		$this->pdf->Cell(25, 0, $this->encodeText($lang['srv_statistic_answer_state_percent']), 'B', 1, 'C', 0);
		$this->pdf->setX($X);
		
		$this->pdf->setFont('','','6');
	 	foreach ($order as $key) {			
			$this->pdf->Cell(40, 0, $this->encodeText($lang['srv_userstatus_'.$key]), 0, 0, 'L', 0);
			
			#frekvenca
			$this->pdf->Cell(25, 0, $this->encodeText($this->ss->realUsersByStatus[$key]['cnt'] > 0 ? $this->ss->realUsersByStatus[$key]['cnt'] : '0'), 'LR', 0, 'C', 0);
			#procenti
			$this->pdf->Cell(25, 0, $this->encodeText( ((float)$this->ss->realUsersByStatus[$key]['percent'] > 1.0) ? '--' : $this->formatNumber($this->ss->realUsersByStatus[$key]['percent']*100,NUM_DIGIT_PERCENT,'%') ), 0, 1, 'C', 0);
			$this->pdf->setX($X);
	 	}
	 }
	 
	 
	/** Funkcija za prikaz referalov
	 * 
	 */
	 function DisplayReferalsView() {
	 	global $lang;
	 	global $admin_type;
		
		$height = 80;
		$X = 10;
		$Y = $height+30;
		
		$this->pdf->setXY($X, $Y);		
		
		//izrisemo okvir
		$this->pdf->setFont('','B','8');
		$this->pdf->SetTextColor(180, 0, 0);
		$this->pdf->MultiCell(90, $height, $this->encodeText($lang['srv_statistic_redirection_title']), 1, 'L', 0, 0, 0 ,0, true);
		
		$this->pdf->setXY($X, $Y+8);
		$this->pdf->setFont('','B','6');
		$this->pdf->SetTextColor(0, 0, 0);
				
		// izrisemo graf
		if ( ( $this->ss->cntValidRedirections + $this->ss->cntNonValidRedirections ) > 0) {
			$maxValue = $this->ss->maxRedirection * GRAPH_REDUCE;
			$value_sum = 0;
			
			$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_site']), 0, 0, 'L', 0);
			$this->pdf->Cell(70, 0, $this->encodeText($lang['srv_statistic_redirection_click']), 0, 1, 'R', 0);
			$this->pdf->setX($X);
			
			$this->pdf->Cell(90, 3, $this->encodeText(''), 'T', 1, 'L', 0);
			$this->pdf->setX($X);

			$this->pdf->setFont('','','6');
			if (count($this->ss->userRedirections["valid"])) {
				$lineCount = 0;
				foreach ($this->ss->userRedirections["valid"] as $key => $value) {
					
					// zaenkrat je max stevilo vrstic 13 - drugace prebije na drugo stran
					if($lineCount == 13){
						$this->pdf->Cell(90, 0, ' ...', 0, 1, 'L', 0);
						$this->pdf->setY($this->pdf->getY() + 1);
						$this->pdf->setX($X);
						break;
					}
				
					$this->pdf->Cell(20, 0, $this->encodeText($key), 0, 0, 'L', 0);
					
					$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
					$width = ($width == 0) ? 0.1 : $width;
					$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
					$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
					
					$this->pdf->setY($this->pdf->getY() + 1);
					$this->pdf->setX($X);					
					$value_sum += $value;
					
					$lineCount++;
				}
			}
			
			// dodamo še direktni link
			if ($this->ss->userRedirections["direct"] > 0) {
				$value = $this->ss->userRedirections["direct"];
				$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_direct']), 0, 0, 'L', 0);
				
				$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
				$width = ($width == 0) ? 0.1 : $width;
				$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
				$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
				
				$this->pdf->setY($this->pdf->getY() + 1);				
				$this->pdf->setX($X);
				$value_sum += $value;
			}	
			
			// dodamo še email klik
			if ($this->ss->userRedirections["email"] > 0) {
				$value = $this->ss->userRedirections["email"];
				$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_email']), 0, 0, 'L', 0);
				
				$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
				$width = ($width == 0) ? 0.1 : $width;
				$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
				$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
				
				$this->pdf->setY($this->pdf->getY() + 1);
				$this->pdf->setX($X);
				$value_sum += $value;
			}
			
			// dodamo sumo
			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_sum_clicked']), 'T', 0, 'L', 0);
			$this->pdf->Cell(70, 0, $this->encodeText($value_sum), 'T', 1, 'L', 0);
			$this->pdf->setFont('','','6');
			$this->pdf->setY($this->pdf->getY() + 3);
			$this->pdf->setX($X);
			
			// dodamo se neveljavne
			$value_sum_nonvalid = 0;
			for ($key = 2; $key >= 0; $key--) {
				$value = $this->ss->userRedirections["$key"];
				if ($value > 0) {
					$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_'.$key]), 0, 0, 'L', 0);

					$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
					$width = ($width == 0) ? 0.1 : $width;
					$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
					$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
					
					$this->pdf->setY($this->pdf->getY() + 1);
					$this->pdf->setX($X);
					$value_sum_nonvalid += $value;
				}
			}
			// dodamo sumo
			if ($value_sum_nonvalid > 0 ) {
				$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_sum_nonvalid']), 0, 0, 'L', 0);
				$this->pdf->Cell(70, 0, $this->encodeText($value_sum_nonvalid), 0, 1, 'L', 0);
				
				$this->pdf->setY($this->pdf->getY() + 3);
				$this->pdf->setX($X);
			}
			if (!($value_sum_nonvalid == 0 || $value_sum == 0 )) {
				$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_sum']), 0, 0, 'L', 0);
				$this->pdf->Cell(70, 0, $this->encodeText($value_sum+$value_sum_nonvalid), 0, 1, 'L', 0);
				
				$this->pdf->setY($this->pdf->getY() + 3);
				$this->pdf->setX($X);
			}
		} 
		else {
			$this->pdf->Cell(90, 0, $this->encodeText($lang['srv_statistic_show_no_referals']), 0, 1, 'L', 0);
			$this->pdf->setX($X);
		}
		

		$this->pdf->setFont('','B','6');
		$this->pdf->Cell(90, 0, $this->encodeText($lang['srv_count_ip_list'].': '.count($this->ss->ip_list)), 0, 1, 'L', 0);
		$this->pdf->setX($X);
		
		$this->pdf->setFont('','','6');
		if ($admin_type==0 && count($this->ss->ip_list) > 0) {
			
			$this->pdf->Cell(90, 0, $this->encodeText($lang['srv_detail_ip_list']), 0, 1, 'L', 0);
			$this->pdf->setX($X);

			foreach($this->ss->ip_list AS $key => $val) {
				$this->pdf->Cell(20, 0, $this->encodeText($val), 0, 0, 'L', 0);
				$this->pdf->Cell(70, 0, $this->encodeText($key), 0, 1, 'L', 0);
				$this->pdf->setX($X);
			}
		}
	 }

	 /** Funkcija prikaze statistike
	 * 
	 */
	function DisplayDateView() { 	
		global $lang;
		
		$height = 80;
		$X = 105;
		$Y = $height+30;
		
		$this->pdf->setXY($X, $Y);		
		
		//izrisemo okvir
		$this->pdf->setFont('','B','8');
		$this->pdf->SetTextColor(180, 0, 0);
		$this->pdf->MultiCell(90, $height, $this->encodeText($lang['srv_statistic_timeline_title']), 1, 'L', 0, 0, 0 ,0, true);
		
		$this->pdf->setXY($X, $Y+8);
		$this->pdf->setFont('','','6');
		$this->pdf->SetTextColor(0, 0, 0);		
		
		$this->ss->maxValue *= GRAPH_REDUCE;
		$cnt=0;
		
		if ($this->ss->arrayRange) {
			$lineCount = 0;
			foreach ($this->ss->arrayRange as $key => $value) {				
			
				// zaenkrat je max stevilo vrstic 19 - drugace prebije na drugo stran
				if($lineCount == 19){
					$this->pdf->Cell(90, 0, ' ...', 0, 1, 'L', 0);
					$this->pdf->setY($this->pdf->getY() + 1);
					$this->pdf->setX($X);
					break;
				}
				
				$label = $this->ss->formatStatsString($key, $this->ss->period);
				
				$this->pdf->Cell(20, 0, $this->encodeText($label), 0, 0, 'L', 0);
					
				$width = ($this->ss->maxValue && $value) ? (round($value / $this->ss->maxValue * 60, 0)) : 0.1;
				$width = ($width == 0) ? 0.1 : $width;
				$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
				
				$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
				
				$this->pdf->setY($this->pdf->getY() + 1);
				$this->pdf->setX($X);
				$cnt+=$value;
				
				$lineCount++;	
			}
			
			// dodamo sumo
			$this->pdf->setFont('','B','6');
			$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_redirection_sum'].':'), 'T', 0, 'L', 0);
			$this->pdf->Cell(70, 0, $this->encodeText($cnt), 'T', 1, 'L', 0);
			$this->pdf->setFont('','','6');			
		} 
		else {
			$this->pdf->Cell(90, 0, $this->encodeText($lang['srv_no_data']), 0, 1, 'L', 0);	
		}		
	}
	 
	/** Funkcija za prikaz klikov po straneh
	 * 
	 */
	 function DisplayPagesStateView() {
	 	global $lang;
	 	
		$height = 80;
		$X = 200;
		$Y = $height+30;
		
		$this->pdf->setXY($X, $Y);		
		
		//izrisemo okvir
		$this->pdf->setFont('','B','8');
		$this->pdf->SetTextColor(180, 0, 0);
		$this->pdf->MultiCell(90, $height, $this->encodeText($lang['srv_statistic_pages_state_title']), 1, 'L', 0, 0, 0 ,0, true);
		
		$this->pdf->setXY($X, $Y+8);
		$this->pdf->setFont('','','6');
		$this->pdf->SetTextColor(0, 0, 0);
		
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
		
		
		$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_statistic_answer_state_status']), 0, 0, 'L', 0);
		$this->pdf->Cell(70, 0, $this->encodeText($lang['srv_statistic_redirection_click']), 0, 1, 'R', 0);
		$this->pdf->setX($X);
		
		$this->pdf->Cell(90, 3, $this->encodeText(''), 'T', 1, 'L', 0);
		$this->pdf->setX($X);

		# status 3 - "Klik na anketo"
		$value = $this->ss->realUsersByStatus['3ll']['cnt'];
		$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_3']), 0, 0, 'L', 0);
					
		$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;		
		$width = ($width == 0) ? 0.1 : $width;		
		$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
		$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
					
		$this->pdf->setY($this->pdf->getY() + 1);
		$this->pdf->setX($X);
		
		# status 4 - "Klik na prvo stran"
		$value = $this->ss->realUsersByStatus['4ll']['cnt'];
		$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_4']), 0, 0, 'L', 0);
					
		$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
		$width = ($width == 0) ? 0.1 : $width;
		$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
		$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
					
		$this->pdf->setY($this->pdf->getY() + 1);
		$this->pdf->setX($X);
		
		# status 5 - "Za&#269;el izpolnjevati",
		$value = $this->ss->realUsersByStatus[5]['cnt'];
		$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_5']), 0, 0, 'L', 0);
					
		$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
		$width = ($width == 0) ? 0.1 : $width;
		$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
		$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
					
		$this->pdf->setY($this->pdf->getY() + 1);
		$this->pdf->setX($X);
		
		#strani
		$this->pdf->Cell(90, 3, $this->encodeText(''), 'T', 1, 'L', 0);
		$this->pdf->setX($X);
		
		$lineCount = 0;
		foreach ($pages as $key => $page) {
		
			// zaenkrat je max vrstic 9 - drugace prebije na drugo stran
			if($lineCount == 9){
				$this->pdf->Cell(90, 0, ' ...', 0, 1, 'L', 0);
				$this->pdf->setY($this->pdf->getY() + 1);
				$this->pdf->setX($X);
				break;
			}
		
			$value = $page['cnt'];
			$this->pdf->Cell(20, 0, $this->encodeText($page['naslov']), 0, 0, 'L', 0);
					
			$width = ($maxValue && $value) ? (round($value / $maxValue * 60, 0)) : 0.1;
			$width = ($width == 0) ? 0.1 : $width;			
			$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
						
			$this->pdf->Cell(10, 0, $this->encodeText($value), 0, 1, 'L', 0);
						
			$this->pdf->setY($this->pdf->getY() + 1);
			$this->pdf->setX($X);
			
			$lineCount++;
		}
		
		#strani
		$this->pdf->Cell(90, 3, $this->encodeText(''), 'T', 1, 'L', 0);
		$this->pdf->setX($X);
		
				
		# status 6 - "Koncal",
		$value6 = $this->ss->realUsersByStatus[6]['cnt'];
		$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_6']), 0, 0, 'L', 0);
					
		$width = ($maxValue && $value6) ? (round($value6 / $maxValue * 60, 0)) : 0.1;
		$width = ($width == 0) ? 0.1 : $width;
		$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
					
		$this->pdf->Cell(10, 0, $this->encodeText($value6), 0, 1, 'L', 0);
					
		$this->pdf->setY($this->pdf->getY() + 1);
		$this->pdf->setX($X);

		#če imamo lurkerje 6l dodamo skupaj konačal anketo (to je 6 + 6l) in nato še koliko jih je samo s statusom 6 (končal anketo)
		# status 6l - "Koncal - lurker", izpišemo samo če obstajajo 6l
		$lurkerjev = $this->ss->realUsersByStatus['6ll']['cnt'] - $value6;
		if ($lurkerjev > 0) {
			$valueall = $this->ss->realUsersByStatus['6ll']['cnt'] ;
			
			# končal s tem da je lurker (6l)
			$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_6l']), 0, 0, 'L', 0);

			$width = ($maxValue && $lurkerjev) ? (round($lurkerjev / $maxValue * 60, 0)) : 0.1;
			$width = ($width == 0) ? 0.1 : $width;
			$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
						
			$this->pdf->Cell(10, 0, $this->encodeText($lurkerjev), 0, 1, 'L', 0);
					
			$this->pdf->setY($this->pdf->getY() + 1);
			$this->pdf->setX($X);
			
			#črta
			$this->pdf->Cell(90, 3, $this->encodeText(''), 'T', 1, 'L', 0);
			$this->pdf->setX($X);
			
			# končal ne glede na to ali je lurker
			$this->pdf->Cell(20, 0, $this->encodeText($lang['srv_userstatus_all']), 0, 0, 'L', 0);

			$width = ($maxValue && $valueall) ? (round($valueall / $maxValue * 60, 0)) : 0.1;
			$width = ($width == 0) ? 0.1 : $width;
			$this->pdf->Cell($width, 0, $this->encodeText(''), 1, 0, 'L', 1);
			$this->pdf->Cell(10, 0, $this->encodeText($valueall), 0, 1, 'L', 0);							
		}
	}
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text,$length=64,$tail="...")
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

	function setUserId($usrId) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

	function formatNumber($value,$digit=0,$sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
}

?>