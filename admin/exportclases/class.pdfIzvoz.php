<?php
/*
 * Created on 31.3.2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
//error_reporting(E_ALL);
set_time_limit(1800);


include_once('../../function.php');
require_once('../exportclases/class.enka.pdf.php');
require_once('../../vendor/autoload.php');


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
 * @desc: po novem je potrebno form elemente generirati ročno kot slike
 *
 */
class PdfIzvoz {

	var $anketa;// = array();			// trenutna anketa

	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $printPreview = false;	// ali kliče konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	
	var $currentHeight = 0;		// visina trenutnega vprasanja

	var $SUS; //SurveyUserSettng
	var $SA; //SurveyAnketa
	
	var $db_table = '';
	
	var $language = -1;		// Katero verzijo prevoda izvazamo
	
	var $type = 0;			// tip izpisa - 0->navaden, 1->iz prve strani, 2->s komentarji
	var $commentType = 1;	// tip izpisa komentarjev
	var $showIf = 0;		// izpis if-ov
	var $font = 10;			// velikost pisave
	var $numbering = 0; 	// ostevillcevanje vprasanj
	var $showIntro = 0; 	// prikaz uvoda
		

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $type = 0, $commentType = 1){
		global $site_path;
		global $global_user_id;
		global $site_url;
		global $lang;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;			
			$this->usrId = $_GET['usr_id'];

			$this->type = $type;
			$this->commentType = $commentType;
			
			// Po novem imamo globalne nastavitve
			SurveySetting::getInstance()->Init($anketa);
			$this->font = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_font_size');
			$this->showIf = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_show_if');
			$this->numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_numbering');
			$this->showIntro = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_show_intro');
						
			if(isset($_GET['language'])){
				$this->language = $_GET['language'];
				
				// Naložimo jezikovno datoteko
				$file = '../../lang/'.$this->language.'.php';
				include($file);
				$_SESSION['langX'] = $site_url .'lang/'.$this->language.'.php';
			}
			
			// create new PDF document
			$this->pdf = new enka_TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		{
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
			
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
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
		$this->pdf->Output($fileName, 'I');
	}


	function init()
	{

		// array used to define the language and charset of the pdf file to be generated
	    $language_meta = Array();
	    $language_meta['a_meta_charset'] = 'UTF-8';
	    $language_meta['a_meta_dir'] = 'ltr';
	    $language_meta['a_meta_language'] = 'sl';
	    $language_meta['w_page'] = 'stran';

		//set some language-dependent strings
	    $this->pdf->setLanguageArray($language_meta);

		//set margins
		$this->pdf->setPrintHeaderFirstPage(false);
		$this->pdf->setPrintFooterFirstPage(false);

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
		//$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()));
		if ($this->language != -1) {
			SurveySetting::getInstance()->Init($this->anketa['id']);
			$_lang = '_'.$this->language;
			$srv_novaanketa_kratkoime = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_novaanketa_kratkoime'.$_lang);
		}
		else{
			$srv_novaanketa_kratkoime = SurveyInfo::getInstance()->getSurveyAkronim();
		}
		$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText($srv_novaanketa_kratkoime));

		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		return true;
	}

	function createPdf()
	{
		// Izpis vprasanj s komentarji
		if($this->type == 2)
			$this->outputCommentaries();	
			
		// Izpis vprasalnika oz odgovorov enega respondenta
		else
			$this->outputSurvey();
	}

	function createFrontPage(){
		global $lang;
		
		// dodamo prvo stran
		$this->pdf->AddPage();
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 16);

		if ($this->language != -1) {
			SurveySetting::getInstance()->Init($this->anketa['id']);
			$_lang = '_'.$this->language;
			$srv_anketa_naslov = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_novaanketa_kratkoime'.$_lang);
		}
		else{
			$srv_anketa_naslov = SurveyInfo::getInstance()->getSurveyAkronim();
		}
		
		// dodamo naslov
  		$this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(128, 0, 0);
        $this->pdf->SetLineWidth(0.1);
		$this->pdf->Sety(100);

		if($this->allResults == 1){
			$this->pdf->Cell(0, 10, $this->encodeText($srv_anketa_naslov), 'TLR', 1,'C', 1, 0,0);
			$this->pdf->SetFont(FNT_MAIN_TEXT, '', 13);
			$this->pdf->Cell(0, 10, $this->encodeText($lang['export_firstpage_results']), 'BLR', 1,'C', 1, 0,0);
		}
		elseif($this->allResults == 2){
			$this->pdf->Cell(0, 10, $this->encodeText($srv_anketa_naslov), 'TLR', 1,'C', 1, 0,0);
			$this->pdf->SetFont(FNT_MAIN_TEXT, '', 13);
			$this->pdf->Cell(0, 10, $this->encodeText($lang['srv_testiranje_komentarji']), 'BLR', 1,'C', 1, 0,0);
		}
		else{
			$this->pdf->Cell(0, 16, $this->encodeText($srv_anketa_naslov), 1, 1,'C', 1, 0,0);
		}

		// dodamo info:
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 12);
		$this->currentStyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(128, 0, 0));
		$this->pdf->ln(30);
		//	$this->pdf->Write  (0, $this->encodeText("Info:"), '', 0, 'l', 1, 1);

		$this->drawLine();
		// avtorja, št vprašanj, datum kreiranja
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_shortname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), 0, 'L', 0, 1, 0 ,0, true);
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim())
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_longname'].': '.$this->encodeText($srv_anketa_naslov), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_qcount'].': '.SurveyInfo::getInstance()->getSurveyQuestionCount(), 0, 'L', 0, 1, 0 ,0, true);
		
		
		// Aktiviranost
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			$this->pdf->SetTextColor(0,150,0);
			$this->pdf->MultiCell(95, 5, $this->encodeText($lang['srv_anketa_active2']), 0, 'L', 0, 1, 0 ,0, true);
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				$this->pdf->SetTextColor(255,120,0);
				$this->pdf->MultiCell(95, 5, $this->encodeText($lang['srv_survey_non_active_notActivated']), 0, 'L', 0, 1, 0 ,0, true);
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				$this->pdf->SetTextColor(255,120,0);
				$this->pdf->MultiCell(95, 5, $this->encodeText($lang['srv_survey_non_active']), 0, 'L', 0, 1, 0 ,0, true);
			}
		}
		$this->pdf->SetTextColor(0);
		
		// Aktivnost	
		if( count($activity) > 0 ){
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_active_from'].': '.SurveyInfo::getInstance()->getSurveyStartsDate(), 0, 'L', 0, 0, 0 ,0, true);
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_active_until'].': '.SurveyInfo::getInstance()->getSurveyExpireDate(), 0, 'L', 0, 1, 0 ,0, true);
		}
		
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_author'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInsertName()), 0, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_edit'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyEditName()), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_date'].': '.SurveyInfo::getInstance()->getSurveyInsertDate(), 0, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_date'].': '.SurveyInfo::getInstance()->getSurveyEditDate(), 0, 'L', 0, 1, 0 ,0, true);
		if ( SurveyInfo::getInstance()->getSurveyInfo() )
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_desc'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInfo()), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		$this->pdf->SetFillColor(0, 0, 0);
	}
	
	
	// Izpis vprasalnika (z ali brez odgovorov)
	function outputSurvey(){
		global $lang;
		
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		// izpišemo prvo stran
		$this->createFrontPage();

		// Izpisemo vprasalnik
		$this->pdf->AddPage();

		// filtriramo spremenljivke glede na profil					
		SurveyVariablesProfiles :: Init($this->anketa['id']);
		
		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		
		$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);
		
		foreach ( $tmp_svp_pv as $vid => $variable) {
			$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
		}
		
		
		// ce obstaja intro izpisemo intro - pri izpisu vprasalnika brez odgovorov (ce smo na prvi strani moramo biti v razsirjenem nacinu)
		if( ($rowA['expanded'] != 0 || $this->type != 1) && $this->showIntro == 1 ){
			if ( SurveyInfo::getInstance()->getSurveyShowIntro() )
			{ 		
				$intro = (SurveyInfo::getInstance()->getSurveyIntro() == '') ? $lang['srv_intro'] : SurveyInfo::getInstance()->getSurveyIntro();
				
				// po potrebi prevedemo uvod 			
				$naslovIntro = $this->srv_language_intro();
				if ($naslovIntro != '') {
					$intro = $naslovIntro;
				}	
				
				$this->pdf->Write  (0, $this->encodeText($intro), '', 0, 'L', 1, 1);
				$this->pdf->Ln(LINE_BREAK);
				//$this->pdf->drawLine();
				$this->pdf->Ln(LINE_BREAK);
			}
		}

		$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' ORDER BY vrstni_red";
		$sqlGrupe = sisplet_query($sqlGrupeString);
		
		while ( $rowGrupe = mysqli_fetch_assoc( $sqlGrupe ) )
		{ // sprehodmo se skozi grupe ankete
			$this->grupa = $rowGrupe['id'];

			$zaporedna = 0;
			$sqlSpremenljivke = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
			while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke))
			{ // sprehodimo se skozi spremenljivke grupe
				$spremenljivka = $rowSpremenljivke['id'];
				if ( $this->checkSpremenljivka ($spremenljivka) /*|| $this->showIf == 1*/ )
				{ // lahko izrišemo spremenljivke

					// po potrebi obarvamo vprašanja
					$this->pdf->SetTextColor(0);
					if ($rowSpremenljivke['visible'] == 0)
					{
						if ($rowSpremenljivke['sistem'] == 1)
						{ 		// če je oboje = vijolčno
							$this->pdf->SetTextColor(128,0,128);
						}
						else
						{		// Če je skrito = rdeče
							$this->pdf->SetTextColor(255,0,0);
						}
					}
					else if ($rowSpremenljivke['sistem'] == 1)
					  $this->pdf->SetTextColor(0,0,255);


					// če imamo številčenje Type = 1 potem številčimo V1
					if (SurveyInfo::getInstance()->getSurveyCountType())
						$zaporedna++;
					$stevilcenje = ( SurveyInfo::getInstance()->getSurveyCountType() ) ?
					( ( SurveyInfo::getInstance()->getSurveyCountType() == 2 ) ? $rowSpremenljivke['variable'].") " : $zaporedna.") " ) : null;

					$this->pdf->SetTextColor(0,0,0);
					$this->pdf->SetDrawColor(0,0,0);
							
					$this->currentHeight = 0;
					
					// izpis skrcenega vprasalnika (samo pri izpisu iz urejanja)
					if($rowA['expanded'] == 0 && $this->type == 1){
						$this->outputVprasanjeCollapsed($rowSpremenljivke, $stevilcenje);
					}
					
					// izpis navadnega vprasalnika
					else{
						$this->outputVprasanje($rowSpremenljivke, $stevilcenje);
						$this->outputSpremenljivke($rowSpremenljivke);	
					}

						
					$this->pdf->Ln(LINE_BREAK);
				}
			}
		}

		// če izpisujemo grupo, ne izpisujemo zakljucka
		if ( !$this->getGrupa() ){
			if ( SurveyInfo::getInstance()->getSurveyShowConcl() && SurveyInfo::getInstance()->getSurveyConcl() )
			{		// ce obstaja footer izpisemo footer
				$this->pdf->Ln(LINE_BREAK);
				$this->pdf->drawLine();
				$this->pdf->Ln(LINE_BREAK);
				$this->pdf->Write  (0, $this->encodeText(SurveyInfo::getInstance()->getSurveyConcl()), '', 0, 'L', 1, 1);
			}
		}
	}
	
	// Izpis vprasanj s komentarji
	function outputCommentaries(){
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;
				
		$this->createFrontPage();

		$this->pdf->AddPage();
		

		$f = new Forum;
		$c = 0;
		
		$b = new Branching($this->anketa['id']);
		
		$rowi = SurveyInfo::getInstance()->getSurveyRow();
		
		SurveySetting::getInstance()->Init($this->anketa['id']);
		$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');
		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
		$question_note_view = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_view');
		$addfieldposition = SurveySetting::getInstance()->getSurveyMiscSetting('addfieldposition');
		$commentmarks = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks');
		$commentmarks_who = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks_who');
		
		$sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='".$this->anketa['id']."' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
		
		if ( mysqli_num_rows($sql) > 0 && ( (int)$question_resp_comment_viewadminonly + (int)$question_comment_viewadminonly ) > 0 ) {
			while ($row = mysqli_fetch_array($sql)) {

				$sql1 = sisplet_query("SELECT thread, note FROM srv_spremenljivka WHERE id = '$row[id]'");
				$row1 = mysqli_fetch_array($sql1);
				
				$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
				$tid = $row1['thread'];	
				
				$only_unresolved = " ";
				$only_unresolved2 = " ";
				
				if ($this->commentType == 1) $only_unresolved = " AND ocena <= 1 "; 
				if ($this->commentType == 1) $only_unresolved2 = " AND text2 <= 1 "; 
				
				if ($this->commentType == 2) $only_unresolved = " AND ocena = 0 "; 
				if ($this->commentType == 2) $only_unresolved2 = " AND text2 = 0 "; 
				
				if ($this->commentType == 3) $only_unresolved = " AND ocena = 1 "; 
				if ($this->commentType == 3) $only_unresolved2 = " AND text2 = 1 "; 
				
				if ($this->commentType == 4) $only_unresolved = " AND ocena = 2 "; 
				if ($this->commentType == 4) $only_unresolved2 = " AND text2 = 2 "; 
				
				if ($this->commentType == 5) $only_unresolved = " AND ocena = 3 "; 
				if ($this->commentType == 5) $only_unresolved2 = " AND text2 = 3 "; 
				
				
				$tema_vsebuje = substr($lang['srv_forum_intro'],0,10);		// da ne prikazujemo 1. default sporocila
				
				if ($admin_type <= $question_comment_viewadminonly) {	// vidi vse komentarje
					$sqlt = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved ORDER BY time $orderby, id $orderby");
				} elseif ($question_comment_viewauthor==1) {	// vidi samo svoje komentarje
					$sqlt = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");
				} else {												// ne vidi nobenih komentarjev
					$sqlt = sisplet_query("SELECT * FROM post WHERE 1=0");
				}
			
				$sql2 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_text".$this->db_table." WHERE spr_id='0' AND vre_id='$row[id]' $only_unresolved2");
				$row2 = mysqli_fetch_array($sql2);
		
				if ( mysqli_num_rows($sqlt) > 0 || $row2['count'] > 0 || $row1['note'] != '') {
					$c++;

					$this->currentHeight = 0;
					
					$this->outputVprasanje($row, null);
					$this->outputSpremenljivke($row);
		
					if ($admin_type <= $question_note_view || $question_note_view == '') {
						
						if ($row1['note'] != '') {
							$this->pdf->Ln(3);
							$this->pdf->setFont('','B', 10);
							$this->pdf->Write(0, $this->encodeText($lang['hour_comment']), '', 0, 'l', 1, 1);
							
							$this->pdf->Ln(3);
							$this->pdf->setFont('','', 10);
							$this->pdf->Write(0, $this->encodeText($row1['note']), '', 0, 'l', 1, 1);
						}
					}
										
					// komentarji na vprasanje
					if ($row1['thread'] > 0) {
						
						if (mysqli_num_rows($sqlt) > 0) {
							
							$this->pdf->Ln(3);
							$this->pdf->setFont('','B', 10);
							$this->pdf->Write  (0, $this->encodeText($lang['srv_admin_comment']), '', 0, 'l', 1, 1);

							$this->pdf->Ln(3);
							
							$i = 0;
							while ($rowt = mysqli_fetch_array($sqlt)) {

								$this->pdf->setFont('','B', 10);
								$this->pdf->Write  (0, $this->encodeText($f->user($rowt['uid'])), '', 0, 'l', 0, 1);	
								$this->pdf->setFont('','', 10);
								$this->pdf->Write  (0, $this->encodeText(' ('.$f->datetime1($rowt['time']).'):'), '', 0, 'l', 1, 1);
																
								// Popravimo vsebino ce imamo replike							
								$vsebina = iconv("iso-8859-2", "UTF-8", $rowt['vsebina']);
								$odgovori = explode("<blockquote style=\"margin-left:20px\">", $vsebina);								
								
								$this->pdf->MultiCell(100, 0, $this->encodeText($odgovori[0]),0,'L',0,1,0,0,true,0);
								
								unset($odgovori[0]);
								foreach($odgovori as $odgovor){
									
									$this->pdf->Ln(2);
									$this->pdf->setX($this->pdf->getX()+8);
									
									$odgovor = explode('<br />', $odgovor);
									$avtor = explode('</b> ', $odgovor[0]);
								
									$this->pdf->setFont('','B', 10);
									$this->pdf->Write(0, $this->encodeText($avtor[0]), '', 0, 'l', 0, 1);
									$this->pdf->setFont('','', 10);
									$this->pdf->Write(0, $this->encodeText($avtor[1]), '', 0, 'l', 1, 1);
									
									$this->pdf->setX($this->pdf->getX()+8);
									$this->pdf->MultiCell(92, 0, $this->encodeText($odgovor[1]),0,'L',0,1,0,0,true,0);
								}
								
								// Crta
								$this->pdf->MultiCell(100, 2, '',0,'L',0,1,0,0,true,0);
								$this->pdf->MultiCell(100, 2, '','T','L',0,1,0,0,true,0);
							}				
						}
					}
					
					// komentarji respondentov
					if ($row2['count'] > 0) {
						
						if ($admin_type <= $question_resp_comment_viewadminonly) {

							$this->pdf->Ln(3);
							$this->pdf->setFont('','B', 10);
							$this->pdf->Write  (0, $this->encodeText($lang['srv_repondent_comment']), '', 0, 'l', 1, 1);
							
							$this->pdf->Ln(3);
							
							if ($this->commentType == 1) $only_unresolved = " AND d.text2 <= 1 "; else $only_unresolved = " ";
							
							$sqlt = sisplet_query("SELECT d.*, u.time_edit FROM srv_data_text".$this->db_table." d, srv_user u WHERE d.spr_id='0' AND d.vre_id='$row[id]' AND u.id=d.usr_id $only_unresolved2 ORDER BY d.id ASC");
							if (!$sqlt) echo mysqli_error($GLOBALS['connect_db']);
							while ($rowt = mysqli_fetch_array($sqlt)) {
								
								$this->pdf->setFont('','', 10);
								$this->pdf->Write(0, $this->encodeText($f->datetime1($rowt['time_edit']).':'), '', 0, 'l', 1, 1);
								
								$this->pdf->MultiCell(100, 0, $this->encodeText($rowt['text']),0,'L',0,1,0,0,true,0);
								
								// Crta
								$this->pdf->MultiCell(100, 2, '',0,'L',0,1,0,0,true,0);
								$this->pdf->MultiCell(100, 2, '','T','L',0,1,0,0,true,0);
							}
						}
					}
					
					$this->pdf->Ln(LINE_BREAK);	
				}
			}
	
			/*if ($c == 0) {
				//echo $lang['srv_no_comments_solved'].'<br/>';
			}*/
			
		}
		
		else {
			//echo $lang['srv_no_comments'].'<br/>';			
		}	
	}

	// Izpis skrcenih vprasanj - v eni vrstici
	function outputVprasanjeCollapsed($spremenljivke, $zaporedna){	
		global $lang;
		
		$b = new Branching($this->anketa['id']);
		
		$sqlIf = sisplet_query("SELECT * FROM srv_branching WHERE element_spr='$spremenljivke[id]'");
		$rowIf = mysqli_fetch_array($sqlIf);
	
		// Izpisemo tekst vprasanja
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		
		// Zamik zaradi ifov
		$zamik = ( $b->level($spremenljivke['id'],0) > 0 ? (($b->level($spremenljivke['id'],0)-1)*10) : 0 );
		$this->pdf->setX($this->pdf->getX()+$zamik);
		
		$rowl = $this->srv_language_spremenljivka($spremenljivke);
		if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];
		
		//izpis if-ov pri vprasanju
		if(/*$this->showIf == 1*/ true){
						
			if ($rowIf['parent'] > 0){			
				$rowb = Cache::srv_if($rowIf['parent']);
				
				if ($rowb['tip'] == 0){
					$this->displayIf($rowIf['parent']);
					$this->pdf->setX($this->pdf->getX()+$zamik+10);
				}				
			}
		}
			
		// stevilcenje vprasanj
		$numberingText = '('.$spremenljivke['variable'].') ';
		
		$this->pdf->setFont('','B',$this->font);
		$this->pdf->SetTextColor(0,128,0);
		$this->pdf->Write  (0, $numberingText, '', 0, 'l', 0, 1);
		$this->pdf->SetTextColor(0,0,0);
		$this->pdf->Write  (0, $this->snippet($this->encodeText($spremenljivke['naslov']), 80), '', 0, 'l', 0, 1);
		$this->pdf->setFont('','I',$this->font);
		$this->pdf->SetTextColor(128,128,128);
		$this->pdf->Write  (0, $this->encodeText(' ( '.$lang['srv_vprasanje_tip_long_'.$spremenljivke['tip']].' )'), '', 0, 'l', 1, 1);
		$this->pdf->SetTextColor(0,0,0);
		
		// izpis pagebreaka
		if($b->pagebreak($spremenljivke['id'])){
			
			$this->currentStyle = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => '2,2', 'color' => array(128, 128, 128));
			$cy = $this->pdf->getY()+3;
			$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
		}
	}
	
	function outputVprasanje($spremenljivke, $zaporedna){	

		$rowl = $this->srv_language_spremenljivka($spremenljivke);
		if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];
		if (strip_tags($rowl['info']) != '') $spremenljivke['info'] = $rowl['info'];
		
		// Izpisemo tekst vprasanja
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		
		$pozicija_vprasanja = $this->pdf->getY();
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
        $rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 8);
		
		$linecount_vprasanja = $this->pdf->getNumLines($spremenljivke['naslov'], $this->pdf->getPageWidth());
		
		if($pozicija_vprasanja + $linecount_vprasanja*4.7 /*+ $visina*/ > 240)
		{	
			$this->pdf->AddPage('P');								
		}
		
		//izpis if-ov pri vprasanju
		if($this->showIf == 1){
			
			/*$sqlIf = sisplet_query("SELECT * FROM srv_branching WHERE element_spr='$spremenljivke[id]'");
			$rowIf = mysqli_fetch_array($sqlIf);
			
			if ($rowIf['parent'] > 0){			
				$rowb = Cache::srv_if($rowIf['parent']);
				
				if ($rowb['tip'] == 0)
					$this->displayIf($rowIf['parent']);
			}*/
			
			// Po novem izpisemo pred vsakim vprasanjem vse ife znotraj katerih se nahaja
			$b = new Branching($this->anketa['id']);
			$parents = $b->get_parents($spremenljivke['id']);

			$parents = explode('p_', $parents);
			foreach ($parents AS $key => $val) {
				if ( is_numeric(trim($val)) ) {
					$parents[$key] = (int)$val;
				} else {
					unset($parents[$key]);
				}
			}

			foreach ($parents AS $if) {
				$this->displayIf($if);
			}
		}
		
		// stevilcenje vprasanj
		$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';
		
		$this->pdf->setFont('','B',$this->font);

		if($spremenljivke['orientation']!=0){	//ce ni vodoravno ob vprasanju, pejdi v novo vrstico
			//$this->pdf->Write(0, $numberingText . $this->encodeText($spremenljivke['naslov']), '', 0, 'l', 1, 1);
			$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
			$this->pdf->WriteHTML($text, $ln=true, $fill=false, $reseth=true);
		
			// Izpisemo opombo, ce jo imamo
			if($spremenljivke['info'] != ''){			
				//$this->pdf->Ln(1);
				$this->pdf->setFont('','',$this->font-2);
				$this->pdf->SetTextColor(100,100,100);
				//$this->pdf->WriteHTML($spremenljivke['info']);
				$this->pdf->Write(0, $this->encodeText($spremenljivke['info']), '', 0, 'l', 1, 1);
				$this->pdf->setFont('','',$this->font);
				$this->pdf->SetTextColor(0);
			}
			
			//$this->pdf->setFont('','',$this->font);
			$this->pdf->Ln(LINE_BREAK);
		}else{	//ce je vodoravno ob vprasanju
			$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
			//$this->pdf->WriteHTML($text, $ln=true, $fill=false, $reseth=true);
			//$this->pdf->WriteHTML($text, $ln=false, $fill=false, $reseth=true);
			$this->pdf->writeHTMLCell(70,1,$x1,$y1,$text,0,0,0,1,'L',1);
		}
		$this->pdf->setFont('','',$this->font);
	}

	function outputSpremenljivke($spremenljivke)
	{
		switch ( $spremenljivke['tip'] )
		{
			case 1: //radio
			case 2: //check
			case 3: //select -> radio
			
				// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				
				$width = 180;
				
				//ce imamo prikaz v vec stoplcih
				$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
				$stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);		
				$checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);		
				
				if ($stolpci > 1 && $spremenljivke['orientation']==1) {
					//echo '<div style="float:left; width:'.(100/$stolpci).'%">';
					$kategorij = mysqli_num_rows($sqlVrednosti);
					$v_stolpcu = ceil($kategorij / $stolpci);
					
					$width = round(180 / $stolpci);
				}		
				
				$yStart = $this->pdf->GetY();
				$xStart = $this->pdf->GetX();
				$count = 0;
				$questionWidth = 85;
				$answerWidth = 33;
				$maxHeight = 0;
				$opombaAdded = 0;
				$maxPageWidth = $this->pdf->getPageWidth() - 30;
				$seznam = "";
				
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					$prop['full'] = ( isset($userAnswer[$rowVrednost['id']]) );
					
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}	
					
					$stringTitle = ($this->encodeTextHtml(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					//$stringTitle = (( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ));	//encodeText sem presaltal, tako, da sem lahko uporabljal writeHTMLCell(), saj drugace ni slik, ni teksta v novi vrstici, ipd.
					
					//popravimo lokacijo ce imamo postavitev v vec stolpcih
					if ( ($stolpci > 1) && ($spremenljivke['orientation']==1) ) {			
						$yPos = $count % $v_stolpcu;
						$xPos = floor($count / $v_stolpcu);
						$this->pdf->SetXY($this->pdf->GetX()+($width*$xPos), $yStart+(7*$yPos));				
					}
					
					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					if ( $spremenljivke['tip']  == 1 || $spremenljivke['tip'] == 3 )
					{
						if($spremenljivke['orientation']==1)
						{	//navpicno
							$this->pdf->RadioButton('vpr_'. $spremenljivke['id'], RADIO_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==7)
						{	//navpicno - tekst levo
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
							$this->pdf->writeHTMLCell(60,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
							//$this->checkLineHeight($height);
						}elseif($spremenljivke['orientation']==0)
						{	//vodoravno ob vprasanju
							$this->pdf->Cell(2, 5, '');
							$this->pdf->RadioButton('vpr_'. $spremenljivke['id'], RADIO_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==2)
						{	//vodoravno pod vprasanjem
							$this->pdf->Cell(2, 5, '');
							$this->pdf->RadioButton('vpr_'. $spremenljivke['id'], RADIO_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==6)
						{	//izberite s seznama
							if($count == 0){
								$this->pdf->Cell(2, 5, '');
								$y1=$this->pdf->GetY();
								$x1=$this->pdf->GetX();
							}
							$seznam .= $stringTitle.'<br>';
							//$this->currentHeight = ($this->currentHeight == 1) ? 2 : $this->currentHeight;
						}elseif($spremenljivke['orientation']==8)
						{	//povleci-spusti
							$this->pdf->Cell(2, 5, '');
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==10)	//image hot-spot
						{	
							if($count == 0){
								$this->pdf->Cell(2, 5, '');
								$y1=$this->pdf->GetY();
								$x1=$this->pdf->GetX();
							}
						}
						else
						{	//ce ni urejenega izrisa naj bo default oz. navpicno
							$this->pdf->RadioButton('vpr_'. $spremenljivke['id'], RADIO_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}
					}
					else if ( $spremenljivke['tip']  == 2 )
					{	
						if($spremenljivke['orientation']==1){	//navpicno
							$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
							$y=$this->pdf->GetY();
							$x=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==7){	//navpicno - tekst levo
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
							$this->pdf->writeHTMLCell(60,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
							//$this->checkLineHeight($height);
						}elseif($spremenljivke['orientation']==0){	//vodoravno ob vprasanju
							$this->pdf->Cell(2, 5, '');
							$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==2){	//vodoravno pod vprasanjem
							$this->pdf->Cell(2, 5, '');
							$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==6){	//izberite s seznama
							if($count == 0){
								$this->pdf->Cell(2, 5, '');
								$y1=$this->pdf->GetY();
								$x1=$this->pdf->GetX();
							}							
							$seznam .= $stringTitle.'<br>';
							//$this->currentHeight = ($this->currentHeight == 1) ? 2 : $this->currentHeight;
						}elseif($spremenljivke['orientation']==8)
						{	//povleci-spusti
							$this->pdf->Cell(2, 5, '');
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}elseif($spremenljivke['orientation']==10)	//image hot-spot
						{	
							if($count == 0){
								$this->pdf->Cell(2, 5, '');
								$y1=$this->pdf->GetY();
								$x1=$this->pdf->GetX();
							}
						}
						else
						{	//ce ni urejenega izrisa naj bo default oz. navpicno
							$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
							$y=$this->pdf->GetY();
							$x=$this->pdf->GetX();
						}
					}

						
					$this->pdf->Cell(4, 0, '');
										
					$this->currentHeight = $this->pdf->getNumLines($stringTitle, $width);
					
					if ( $spremenljivke['tip']  == 2 )
					{
						if($spremenljivke['orientation']==1){	//navpicno
							$y=$y-2;
							$x=$x+3;
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x,$y);
							$this->pdf->writeHTMLCell($width,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();						
							$this->checkLineHeight($height);
						}elseif($spremenljivke['orientation']==7){	//navpicno - tekst levo
							//$y1=$y1-1;
							//$x1=$x1+3;
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
							$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
							$this->checkLineHeight($height);
						}elseif($spremenljivke['orientation']==0){	//vodoravno ob vprasanju							
							$x1=$x1+3;
							$questionWidth = $questionWidth + $answerWidth;
							if($height > $maxHeight){
								$maxHeight = $height;
							}
							//$this->pdf->writeHTMLCell(40,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$this->pdf->writeHTMLCell($answerWidth,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
						}elseif($spremenljivke['orientation']==2){	//vodoravno pod vprasanjem
							//$y1=$y1-1;
							$x1=$x1+3;
							$questionWidth = $questionWidth + $answerWidth;
							if($height > $maxHeight){
								$maxHeight = $height;
							}
							//$this->pdf->writeHTMLCell(40,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$this->pdf->writeHTMLCell($answerWidth,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
						}elseif($spremenljivke['orientation']==8)
						{	//povleci-spusti
							$y1=$y1-1;
							$x1=$x1+3;
							$this->pdf->writeHTMLCell(70,1,$x1,$y1,$stringTitle,1,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
							//v prvi vrstici dodaj se drugi stolpec
							if($count == 0){
								$this->pdf->Cell(20, 0, '');
								$this->pdf->Cell(70, 7, '', 1);								
							}
							$this->checkLineHeight($height);
						}
						elseif($spremenljivke['orientation']!=6 && $spremenljivke['orientation']!=10){
							$y=$y-2;
							$x=$x+3;
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x,$y);
							$this->pdf->writeHTMLCell($width,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();						
							$this->checkLineHeight($height);
						}
					}
					elseif($spremenljivke['tip']  == 1)
					{
						if($spremenljivke['orientation']==1)
						{	//navpicno
							$y1=$y1-1;
							$x1=$x1+3;
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
							$this->pdf->writeHTMLCell($width,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
							$this->checkLineHeight($height);						
						}elseif($spremenljivke['orientation']==7)
						{	//navpicno - tekst levo
							//$y1=$y1-1;
							//$x1=$x1+3;
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
							$this->pdf->RadioButton('vpr_'. $spremenljivke['id'], RADIO_BTN_SIZE, $prop);
							$this->checkLineHeight($height);
						}elseif($spremenljivke['orientation']==0)
						{	//vodoravno ob vprasanju							
							$x1=$x1+3;
							$questionWidth = $questionWidth + $answerWidth;
							if($height > $maxHeight){
								$maxHeight = $height;
							}
							//$this->pdf->writeHTMLCell(40,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$this->pdf->writeHTMLCell($answerWidth,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
						}elseif($spremenljivke['orientation']==2)
						{	//vodoravno pod vprasanjem
							//$y1=$y1-1;
							$x1=$x1+3;
							$questionWidth = $questionWidth + $answerWidth;
							if($height > $maxHeight){
								$maxHeight = $height;
							}
							//$this->pdf->writeHTMLCell(40,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$this->pdf->writeHTMLCell($answerWidth,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
						}elseif($spremenljivke['orientation']==8)
						{	//povleci-spusti
							$y1=$y1-1;
							$x1=$x1+3;
							$this->pdf->writeHTMLCell(70,1,$x1,$y1,$stringTitle,1,0,0,1,'L',1);
							$height = $this->pdf->getLastH();
							//v prvi vrstici dodaj se drugi stolpec
							if($count == 0){
								$this->pdf->Cell(20, 0, '');
								$this->pdf->Cell(70, 7, '', 1);								
							}							
							$this->checkLineHeight($height);
						}
						elseif($spremenljivke['orientation']!=6 && $spremenljivke['orientation']!=10)
						{	//ce ni urejenega izrisa naj bo default oz. navpicno
							$y1=$y1-1;
							$x1=$x1+3;
							//$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
							$this->pdf->writeHTMLCell($width,1,$x1,$y1,$stringTitle,0,0,0,1,'L',1);
							$this->checkLineHeight($height);
						}
					}
					
					if($spremenljivke['orientation']!=0 && $spremenljivke['orientation']!=2 && $spremenljivke['orientation']!=6)
					{	//ce ni vodoravno ob vprasanju in ni vodoravno pod vprasanjem in ni seznam
						$this->currentHeight = ($this->currentHeight == 1) ? 2 : $this->currentHeight;
						$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
					}elseif($spremenljivke['orientation']==0 || $spremenljivke['orientation']==2)
					{	//ce je vodoravno ob vprasanju oz. vodoravno pod vprasanjem
 						if($questionWidth >= $maxPageWidth){	//ce je sirina vprasanja vecja od max sirine strani							

 							if($spremenljivke['info'] != '' && $opombaAdded == 0 && $spremenljivke['orientation']==0){	//ce je prisotna opomba in ni bila ze dodana @ vodoravno ob vprasanju
								//da dobimo, koliko vrstic je dolgo besedilo vprasanja
								$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';
								$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
								$linecount_vprasanja = $this->pdf->getNumLines($text, 70);														
								$this->checkLineHeight($linecount_vprasanja*6);
								$this->addOpomba($spremenljivke['info'], $maxHeight);
								$opombaAdded = 1;
								$this->pdf->setY($this->pdf->getY() - $linecount_vprasanja*6);	//dvigni y koordinato za visino, ki se je dodalo, da se je pravilno izrisala opomba
								$this->checkLineHeight($maxHeight);
							}else{
								$this->currentHeight = ($this->currentHeight == 1) ? 2 : $this->currentHeight;
								$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
								$this->checkLineHeight($maxHeight);
							}						
							$questionWidth = 70;	//vrni default sirino vprasanja, ker smo v novi vrstici
							if($spremenljivke['orientation']==0){								
								$this->pdf->Cell(70, 1, '');	//dodaj prazno celico, da se odgovori v naslednji vrstici zacnejo 70mm od zacetka vprasanja, kjer se konca besedilo vprasanja
							}
						}
					}
					$count++;
				}
 				if($spremenljivke['orientation']==0)
				{
					//$this->pdf->Ln(LINE_BREAK);
					if($opombaAdded == 0){
						//da dobimo, koliko vrstic je dolgo besedilo vprasanja
						$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';
						$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
						$linecount_vprasanja = $this->pdf->getNumLines($text, 70);														
						$this->checkLineHeight($linecount_vprasanja*6);
						//$this->pdf->Ln(LINE_BREAK);
						$this->addOpomba($spremenljivke['info'], $maxHeight);
						//$this->currentHeight = ($this->currentHeight == 1) ? 2 : $this->currentHeight;
						//$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
						$opombaAdded = 1;
						$this->checkLineHeight($maxHeight);
					}
				}
				if($spremenljivke['orientation']==6)
				{	//izberite s seznama
					$this->pdf->writeHTMLCell(100,1,$x1,$y1,$seznam,1,0,0,1,'L',1);
					$height = $this->pdf->getLastH();
					$this->checkLineHeight($height);
				}
				if($spremenljivke['orientation']==10)	//image hot-spot
				{
					$odgovor = "Image hot spot";
					$this->pdf->writeHTMLCell(100,50,$x1,$y1,$odgovor,1,0,0,1,'L',1);
					$height = $this->pdf->getLastH();
					$this->checkLineHeight($height);
				}
				
				//$this->pdf->Ln(LINE_BREAK);
				//$this->pdf->Ln(LINE_BREAK);
				
			break;

 			case 6: //multigrid
			case 16:// multicheckbox
			case 19:// multitext
			case 20:// multinumber

				//izris dvojnega multigrida
				if(($spremenljivke['tip'] == 6 || $spremenljivke['tip'] == 16) && $spremenljivke['enota'] == 3){			
					$this->displayDoubleGrid($spremenljivke);
					break;
				}
				
				$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
                $rowVrstic = mysqli_fetch_row($sqlVrstic);
				$visina = round(($rowVrstic[0]+2) * 8);
			
                $defw_full = 210;
                $defw_fc = 24; // sirina prve celice
                $defw_max = 35; // max sirina ostalih celic
				
                $sqlStVrednosti = sisplet_query("SELECT count(*) FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
                $rowStVrednost = mysqli_fetch_row($sqlStVrednosti);

                $kolon = $rowStVrednost[0]+1;
				// Ce imamo diferencial
				if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1){
					 $kolon++;
				}
				switch($kolon)
				{
					case 2:
						$defw_fc = 110;						 
					break;
					case 3:
						$defw_fc = 85;												 
					break;	
					case 4:
						$defw_fc = 60;						
					break;
					case 5:
						$defw_fc = 40;		
					break;
					case 6:
						$defw_fc = 35;						
					break;
					default:
					 $defw_fc = 24;					
				}			
                $w_oc = ( $defw_full - $defw_fc ) / $kolon;
				if ( $w_oc > $defw_max )
					$w_oc = $defw_max;

				$countVrednosti=0;
                $halfWidth = ($w_oc)/ 2;
		
				if($spremenljivke['enota'] != 10) //ce ni Image hot spot
				{
					//za izberite s seznama in povleci-spusti
					if($spremenljivke['enota'] == 6)
					{
						$headerTitles = "";
						$stringCellTitles = array();
					}elseif($spremenljivke['enota'] == 9)
					{
						//$headerTitles = array();
						$headerTitles = "";
						$stringCellTitles = "";
					}				
					
					//za izberite s seznama in povleci-spusti - konec
					
					// Prelom strani ce je kateri od naslovov gridov predolg
					$sqlVsehVrednsti = sisplet_query("SELECT naslov, id, variable, vrstni_red FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$linecount = 0;
					while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
					{			
						# priredimo naslov če prevajamo anketo
						$naslov = $this->srv_language_grid($spremenljivke['id'], $rowVsehVrednosti['id']);
						if ($naslov != '') {
							$rowVsehVrednosti['naslov'] = $naslov;
						}
					
						// če je naslov null izpišemo variable
						$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
						/*Zascita pred prepisovanjem na novo stran za zgornje parametre*/
						$pozicija_vrha = $this->pdf->getY();					
						
						$linecount = $this->pdf->getNumLines($stringHeader_title, $w_oc);
						$this->currentHeight = ($linecount > $this->currentHeight) ? $linecount : $this->currentHeight;

						if($pozicija_vrha + $linecount*4.7 > 250)
						{	
							$this->pdf->AddPage('P');						
							break;
						}
					}
					
					/*Izpis presledka na začetku*/
					$this->pdf->Cell($defw_fc, 0,'');
					
					// izišemo header celice
					$sqlVsehVrednsti = sisplet_query("SELECT naslov, id, variable, vrstni_red FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
					{
						# priredimo naslov če prevajamo anketo
						$naslov = $this->srv_language_grid($spremenljivke['id'], $rowVsehVrednosti['id']);
						if ($naslov != '') {
							$rowVsehVrednosti['naslov'] = $naslov;
						}
						
						// če je naslov null izpišemo variable
						$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
						
						if($spremenljivke['enota'] != 6 && $spremenljivke['enota'] != 9)
						{
						/*Izpis zgornje vrstice*/
						$this->pdf->MultiCell($w_oc, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
						}elseif($spremenljivke['enota'] == 6)	//izberite s seznama
						{
							$headerTitles .= $stringHeader_title.'<br>';
						}elseif($spremenljivke['enota'] == 9)	//povleci-spusti
						{
							//array_push($headerTitles, $stringHeader_title);
							$headerTitles .= $stringHeader_title.'<br><br>';
						}
						
						
					}

					$this->pdf->setY($this->pdf->getY() + $this->currentHeight*5);
					
					
					$row_count = 1;
					$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
					{
						// barva vrstice
						$row_color = $row_count%2;
						
						# po potrebi prevedemo naslov 			
						$naslov = $this->srv_language_vrednost($rowVrednost['id']);
						if ($naslov != '') {
							$rowVrednost['naslov'] = $naslov;
						}
						
						$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						$cellHeight1 = $this->getCellHeight($this->encodeText($stringCell_title), $defw_fc);
						
						//za izberite s seznama in povleci-spusti

						if($spremenljivke['enota'] == 6)	//izberite s seznama
						{
							$stringCellTitle = $this->encodeTextHtml(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ).'<br><br>';
							array_push($stringCellTitles, $stringCellTitle);
						}
						if($spremenljivke['enota'] == 9)	//povleci-spusti
						{
							$stringCellTitles .= $this->encodeTextHtml(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ).'<br><br>';
						}
						//za izberite s seznama in povleci-spusti - konec
						
						/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
						$pozicija_boka = $this->pdf->getY();
						
						// še dodamo textbox če je polj other
						$_txt = '';
										
						$stringCell_title .= $_txt.':'; 
											
						$linecount = $this->pdf->getNumLines($stringCell_title, $defw_fc);
						
						if($spremenljivke['enota'] != 6 && $spremenljivke['enota'] != 9)
						{
						
							if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1){
								$stringCell_title2 = $this->encodeText($rowVrednost['naslov2']);
								$linecount = ($this->pdf->getNumLines($stringCell_title2, $defw_fc) > $linecount) ? $this->pdf->getNumLines($stringCell_title2, $defw_fc) : $linecount;
							
								$cellHeight2 = $this->getCellHeight($this->encodeText($stringCell_title2), $defw_fc);
							}

							if($pozicija_boka + $linecount*4.7 > 250)
							{	
								$this->pdf->AddPage('P');
								$pozicija_boka = $this->pdf->getY();
							}
							
							// Izracun zacetka in konca (xy)
							$startY = $pozicija_boka;
							$endY = ($cellHeight2 > $cellHeight1) ? $pozicija_boka+$cellHeight2 : $pozicija_boka+$cellHeight1;
							$endY = ($endY - $startY > LINE_BREAK) ? $endY : $pozicija_boka+LINE_BREAK;
							$startX = $this->pdf->getX() + $defw_fc;	
							
							if($endY > 270)
							{	
								$this->pdf->AddPage('P');
								$pozicija_boka = $this->pdf->getY();
								$startY = $pozicija_boka;
								$endY = ($cellHeight2 > $cellHeight1) ? $pozicija_boka+$cellHeight2 : $pozicija_boka+$cellHeight1;
							}					
							
							// Vsaka druga vrstica ima sivo ozadje				
							$XX = $this->pdf->getX();
							$YY = $this->pdf->getY();
							$this->pdf->setXY(15, $startY);
							$this->pdf->SetFillColor(242,243,241);
							$this->pdf->MultiCell($defw_full-30, $endY-$startY, '',0,'C',$row_color,1,0,0,true,0);
							$this->pdf->SetFillColor(0);
							$this->pdf->setXY($XX, $YY);				
							$row_count++;				
							
							/*Izpis bočnega stolpca*/
							if($cellHeight2 > $cellHeight1)
								$this->pdf->SetXY($this->pdf->getX(), ($startY+$endY)/2 - ($cellHeight1)/2);
								
							$this->pdf->MultiCell($defw_fc, $cellHeight,$stringCell_title,0,'C',0,1,0,0,true,0);				
					
							$shtevec=0;
							$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");	
							while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
							{							
								$prop['full'] = false;
								$this->pdf->SetXY($startX + $shtevec*$w_oc, ($startY+$endY)/2 - (CHCK_BTN_SIZE)/2);
								$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
								if($spremenljivke['tip']==6) {
									$this->pdf->RadioButton('vpr_'. $rowVrednost['id'], RADIO_BTN_SIZE, $prop);
								}
								elseif($spremenljivke['tip']==16) {
									$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
								}
								else{
									$this->pdf->setXY($this->pdf->getX() - $w_oc/2, $startY+1);
									$this->pdf->SetFont(FNT_MAIN_TEXT, '', 8);
									//$this->pdf->SetTextColor(0,128,0);
									$this->pdf->SetTextColor(179,0,128);
									//$this->pdf->TextBoxes($w_oc,LINE_BREAK);	
									$this->pdf->SetFillColor(255,255,255);
									$this->pdf->MultiCell($w_oc-1, $endY-$startY-2,'',1,'C',1,1,0,0,true,0);
									$this->pdf->SetFillColor(0);
									$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
									$this->pdf->SetTextColor(0,0,0);
								}
								$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
								$shtevec++;
							}
							// Bocni stolpec na desni (ce imamo diferencial)
							if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1){
								if($cellHeight1 > $cellHeight2)
									$this->pdf->SetXY($this->pdf->getX(), ($startY+$endY)/2 - ($cellHeight2)/2);
								else
									$this->pdf->setXY($this->pdf->getX(), $startY);
			
								$this->pdf->MultiCell($defw_fc, $cellHeight2,$stringCell_title2,0,'C',0,1,0,0,true,0);
							}	
							
							$this->pdf->setY($endY);
						
						}
					}
					if($spremenljivke['enota'] == 6)
					{
						foreach ($stringCellTitles as $cellTitle)
						{
							$this->displaySeznam($headerTitles, $cellTitle);
						}
					}
					if($spremenljivke['enota'] == 9)
					{
						$this->displayDragDrop($headerTitles, $stringCellTitles);
					}
					
					
				}elseif($spremenljivke['enota'] == 10)
				{
					$odgovor = "Image hot spot";
					$this->displayImageHotSpotHeatmap($odgovor);
				}
		
			break;						
			case 24: // Mesan multigrid
				$this->displayGridMultiple($spremenljivke);
			break;
			case 4: //text
				$this->pdf->TextField('vpr_'. $spremenljivke['id'], 180, 18, array('multiline'=>true,'strokeColor'=>'dkGray'));
				$this->pdf->TextBox(180,18);
				$this->pdf->Ln(LINE_BREAK * 3);
			break;
			case 21: //besedilo*				
				$count = $spremenljivke['text_kosov'];
				$width = round(170/$count);
				$this->currentHeight = 4;
				
				if($spremenljivke['text_orientation'] == 1)
					$width -= 20;
				
				$array_others = array();
				
				$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while($rowVrednost = mysqli_fetch_array($sqlVrednost)){
					
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					// Ce ni other
					if( (int)$rowVrednost['other'] == 0 ) {					
						if($spremenljivke['text_orientation'] == 1){
							$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
							$this->pdf->MultiCell(20, 1,$stringHeader_title,0,'R',0,0,0,0,true,0);
							
							$linecount_vprasanja = $this->pdf->getNumLines($stringHeader_title, 20);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}

						$this->pdf->TextBoxes($width,18);
						$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;

						$this->pdf->setX($this->pdf->getX() + (10/$count)+$width);
					}
					else {
						// imamo polje drugo - ne vem, zavrnil...
						$array_others[$rowVrednost['id']] = array(
							'naslov'=>$rowVrednost['naslov'],
							'vrstni_red'=>$rowVrednost['vrstni_red'],
							'value'=>$text[$rowVrednost['vrstni_red']],
						);
						
					}
				}
				
				if($spremenljivke['text_orientation'] == 2){
					mysqli_data_seek($sqlVrednost, 0);
					$this->pdf->setY($this->pdf->getY() + 20);
					
					while($rowVrednost = mysqli_fetch_array($sqlVrednost)){
					
						# po potrebi prevedemo naslov 			
						$naslov = $this->srv_language_vrednost($rowVrednost['id']);
						if ($naslov != '') {
							$rowVrednost['naslov'] = $naslov;
						}
						
						// Ce ni other
						if( (int)$rowVrednost['other'] == 0 ) {	
							$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
							$this->pdf->MultiCell($width, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
							$this->pdf->setX($this->pdf->getX() + 10/$count);
							
							$linecount_vprasanja = $this->pdf->getNumLines($stringHeader_title, $width);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}
					}
				}
				
				$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
				//$this->pdf->Ln(LINE_BREAK * 3);
								
				// Izris polj drugo - ne vem...
				if (count($array_others) > 0) {
				
					$this->pdf->setY($this->pdf->getY() + 7);
					
					foreach ($array_others AS $oKey => $other) {
						
						$this->pdf->setX(25);				
						$this->pdf->CheckBox('', CHCK_BTN_SIZE);					
						$this->pdf->setXY($this->pdf->getX()+5,$this->pdf->getY() - 2);
						$this->pdf->MultiCell($width, 1,$other['naslov'],0,'L',0,1,0,0,true,0);
						$this->pdf->setY($this->pdf->getY() + 2);
					}
				}
			
			break;
			case 5: //label
//				$this->pdf->Ln(6);
			break;

			case 7: //number
				# z enoto na levi
				if($spremenljivke['enota'] == 1){
					# enota					
					$sqlVrednost = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
					$this->pdf->MultiCell(30, 5,$stringHeader_title,0,'R',0,0,0,0,true,0);

					$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 0, 0 ,0, true);
					
					//dodatno polje
					if($spremenljivke['size'] == 2){

						#enota
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						# po potrebi prevedemo naslov 			
						$naslov = $this->srv_language_vrednost($rowVrednost['id']);
						if ($naslov != '') {
							$rowVrednost['naslov'] = $naslov;
						}
						
						$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
						$this->pdf->MultiCell(30, 5,$stringHeader_title,0,'R',0,0,0,0,true,0);
						
						$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 1, 0 ,0, true);
					}
					else
						$this->pdf->Ln(LINE_BREAK);	
				# z enoto na desni
				} else if($spremenljivke['enota'] == 2){

					$this->pdf->MultiCell(30, 5, '', 1, 'R', 0, 0, 0 ,0, true);
					
					# enota
					$sqlVrednost = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
					$this->pdf->MultiCell(30, 5,$stringHeader_title,0,'L',0,0,0,0,true,0);
					
					//dodatno polje
					if($spremenljivke['size'] == 2){
					
						#enota
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						# po potrebi prevedemo naslov 			
						$naslov = $this->srv_language_vrednost($rowVrednost['id']);
						if ($naslov != '') {
							$rowVrednost['naslov'] = $naslov;
						}
						
						$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
						
						$this->pdf->MultiCell(30, 5, '', 1, 'R', 0, 0, 0 ,0, true);

						$this->pdf->MultiCell(30, 5,$stringHeader_title,0,'L',0,1,0,0,true,0);

					} else {
						$this->pdf->Ln(LINE_BREAK);
					}	
				}
				//brez enote
				else{
					$this->pdf->setX(20);

					$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 0, 0 ,0, true);
					
					//dodatno polje
					if($spremenljivke['size'] == 2){

						$this->pdf->setX($this->pdf->getX() + 5);
						$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 1, 0 ,0, true);
					}
					else
						$this->pdf->Ln(LINE_BREAK);	
				}
				
						
				// Izris polj drugo - ne vem...
				$this->pdf->Ln(LINE_BREAK);	
				$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' and other!='0' order BY vrstni_red");
				while($rowVrednost = mysqli_fetch_array($sqlVrednost)) {
				
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
				
					$this->pdf->setX(25);				
					$this->pdf->CheckBox('', CHCK_BTN_SIZE);					
					$this->pdf->setXY($this->pdf->getX()+5,$this->pdf->getY() - 2);
					$this->pdf->MultiCell($width, 1,$rowVrednost['naslov'],0,'L',0,1,0,0,true,0);
					$this->pdf->setY($this->pdf->getY() + 2);
				}

			break;
			case 8: //datum
				//$this->pdf->TextField('vpr_'. $spremenljivke['id'], 30, 5, array('multiline'=>true,'strokeColor'=>'dkGray'));
				$this->pdf->TextBox(50,LINE_BREAK);
				$this->pdf->Ln(12);
				
				// Izris polj drugo - ne vem...
				$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' and other!='0' order BY vrstni_red");
				while($rowVrednost = mysqli_fetch_array($sqlVrednost)) {
				
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					$this->pdf->setX(25);				
					$this->pdf->CheckBox('', CHCK_BTN_SIZE);					
					$this->pdf->setXY($this->pdf->getX()+5,$this->pdf->getY() - 2);
					$this->pdf->MultiCell($width, 1,$rowVrednost['naslov'],0,'L',0,1,0,0,true,0);
					$this->pdf->setY($this->pdf->getY() + 2);
				}
				
			break;
			case 17: //ranking
				
				// iz baze preberemo vse moznosti
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					$y=$this->pdf->GetY();
					$x=$this->pdf->GetX();
					
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$stringTitle = ($this->encodeTextHtml(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					//stetje stevila vrstic
					$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 90);
					$y=$y+1;
					
					//$this->pdf->MultiCell(90, 1, $stringTitle,1,'L',0,0,$x,$y);
					$this->pdf->writeHTMLCell(70,1,$x,$y,$stringTitle,1,0,0,1,'C',1);
					//$this->pdf->SetTextColor(0,128,0);
					$height = $this->pdf->getLastH();					
					$this->pdf->SetTextColor(179,0,128);

					//$this->pdf->MultiCell(8, 6, $rowAnswers['vrstni_red'],1,'L',0,0,$x+105,$y);
					$this->pdf->writeHTMLCell(70,1,$x+90,$y,"",1,0,0,1,'L',1);
					$this->pdf->SetTextColor(0,0,0);
					
					$this->checkLineHeight($height);
					/* 					
					$stevec=1;
					while($stevec<$stetje_vrstic)
					{
						$this->pdf->Ln(LINE_BREAK);
						$stevec++;
					} */
					//$this->pdf->Ln(LINE_BREAK);
				}
				$this->pdf->Ln(LINE_BREAK);

			break;
			case 18: //vsota
				
				$sum = 0;
				
				// iz baze preberemo vse moznosti
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{					
					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					$y=$this->pdf->GetY();
					$x=$this->pdf->GetX();

					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					//stetje stevila vrstic
					$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 90);
					$y=$y+1;
					
					$this->pdf->MultiCell(60, 1, $stringTitle,0,'R',0,0,$x,$y);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(20, 6, $rowAnswers['text'],1,'L',0,0,$x+65,$y);
					$this->pdf->SetTextColor(0,0,0);
					
					$sum += (int)$rowAnswers['text'];
					
					$stevec=1;
					while($stevec<$stetje_vrstic)
					{
						$this->pdf->Ln(LINE_BREAK);
						$stevec++;
					}
					$this->pdf->Ln(LINE_BREAK);
				}
				
				//izris crte
				$this->currentStyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(128, 128, 128));
				$cy = $this->pdf->getY()+2;
				$this->pdf->Line(15, $cy , 110, $cy , $this->currentStyle);
				
				//izris vsote
				$y=$y+10;
				$this->pdf->MultiCell(60, 1, $spremenljivke['vsota'],0,'R',0,0,$x,$y);
				$this->pdf->MultiCell(20, 6, $sum,1,'L',0,0,$x+65,$y);
				//omejitev vsote
				if($spremenljivke['vsota_limit'] != 0 && $spremenljivke['vsota_limit'] == $spremenljivke['vsota_min'])
					$limit = '('.$spremenljivke['vsota_min'].')';
				elseif($spremenljivke['vsota_limit'] != 0 && $spremenljivke['vsota_min'] != 0)
					$limit = '(min '.$spremenljivke['vsota_min'].', max '.$spremenljivke['vsota_limit'].')';
				elseif($spremenljivke['vsota_limit'] != 0)
					$limit = '(max '.$spremenljivke['vsota_limit'].')';
				elseif($spremenljivke['vsota_min'] != 0)
					$limit = '(min '.$spremenljivke['vsota_min'].')';
					
				if($limit != ''){
					$this->pdf->SetTextColor(255, 0, 0);
					$this->pdf->MultiCell(50, 6, $limit,0,'L',0,0,$x+86,$y);
				}
				
				$this->pdf->Ln(LINE_BREAK);
				
			break;
			
                        //lokacija
                        case 26:
                            $odgovor = "Google Maps";
                            $this->displayImageHotSpotHeatmap($odgovor);
                        break;
			case 27:	//heatmap
				$odgovor = "Heatmap";
				$this->displayImageHotSpotHeatmap($odgovor);
			break;
	
			// SN:
			case 9: //SN-imena, dodamo 5 polji za imena
				$this->pdf->TabledTextBox(1,5,array(cellWidth=>50));
			break;

			case 10: // SN - social support dve koloni po 5 celic
				$this->pdf->TabledTextBox(2,5);
			break;
			case 11: // SN - podvprasanje (podobno kot mgrid - z radio gumbki, 5 ponovitev)
			case 14: // AW - podvprasanje
				$sqlVrednosti = sisplet_query("SELECT naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				$rowHeaders = array();
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
				
					# po potrebi prevedemo naslov 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
				
					$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
                	$rowHeaders[] =$stringCell_title;
                }

				$this->pdf->TabledTextBox(5,sizeof($rowHeaders)+1, array('rowHeaders' => $rowHeaders, 'type' => 'radio', 'vLine' => 0, 'hLine' => 0, 'headerBox' => 1, 'spaceWidth' => 2));
			break;
			case 12: // SN - number
			case 15: // AW - number
				$this->pdf->TabledTextBox(5,$spremenljivke['size']+1, array('type' => 'box', 'vLine' => 0, 'hLine' => 0, 'headerBox' => 1, 'spaceWidth' => 2));
			break;
			case 13: // SN - povezave
				$this->pdf->TabledTextBox(5,5, array('type' => 'povezave', 'vLine' => 0, 'hLine' => 0, 'headerBox' => 1, 'spaceWidth' => 2));
			break;
		}
	}
	
	//izris dolgega nacina doubleGrid
	function displayDoubleGrid($spremenljivke) {
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
		$rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 6);
	
		$defw_full = 200;
		$defw_fc = 24; // sirina prve celice
		$defw_max = 35; // max sirina ostalih celic
		
		$sqlStVrednosti = sisplet_query("SELECT count(*) FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
		$rowStVrednost = mysqli_fetch_row($sqlStVrednosti);

		$kolon = $rowStVrednost[0]+1;
		
		$w_oc = ( $defw_full - $defw_fc ) / $kolon;
		if ( $w_oc > $defw_max )
			$w_oc = $defw_max;

		$countVrednosti=0;
		$halfWidth = ($w_oc)/ 2;

					
		//izpis naslovov posameznih delov grida
		$this->pdf->MultiCell($defw_fc, LINE_BREAK, '',0,'C',0,0);
		$this->pdf->MultiCell(80, LINE_BREAK, $spremenljivke['grid_subtitle1'],'B','C',0,0);
		$this->pdf->MultiCell(80, LINE_BREAK, $spremenljivke['grid_subtitle2'],'B','C',0,1);
		
		//izpis vmesne crte
		$this->pdf->Line($defw_fc+95, $this->pdf->getY()-6, $defw_fc+95, $this->pdf->getY()+$visina);
		
		/*Izpis presledka na začetku*/
		$this->pdf->Cell($defw_fc, 0,'');
		// izišemo header celice
		$sqlVsehVrednsti = sisplet_query("SELECT naslov, id, variable, vrstni_red FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY part, vrstni_red");
		$linecount = 0;
		$maxlinecount = 0;
		$linecount1 = 0;
		$maxlinecount1 = 0;

		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			# priredimo naslov če prevajamo anketo
			$naslov = $this->srv_language_grid($spremenljivke['id'], $rowVsehVrednosti['id']);
			if ($naslov != '') {
				$rowVsehVrednosti['naslov'] = $naslov;
			}
					
			// če je naslov null izpišemo variable
			$stringHeader_title = $this->encodeText( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
			/*Zascita pred prepisovanjem na novo stran za zgornje parametre*/
			$pozicija_vrha = $this->pdf->getY();
			//$linecount_vrha = $this->pdf->getNumLines($stringHeader_title, $w_oc);
			if($pozicija_vrha + $visina > 250)
			{	
				$this->pdf->AddPage('P');
				$this->pdf->Cell($defw_fc, 0,'');
			}
			/*Izpis zgornje vrstice*/
			$this->pdf->MultiCell($w_oc, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);				
			if($linecount_vrha>$maxlinecount) {$maxlinecount=$linecount_vrha;}
			$countVrednosti++;
		}
		$this->pdf->Ln(1.5 * LINE_BREAK);
		$i=0;
		while($i<($maxlinecount))
		{
			$this->pdf->Ln(LINE_BREAK);
			$i++;
		}

		$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			# po potrebi prevedemo naslov 			
			$naslov = $this->srv_language_vrednost($rowVrednost['id']);
			if ($naslov != '') {
				$rowVrednost['naslov'] = $naslov;
			}
		
			$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
			$pozicija_boka = $this->pdf->getY();
			$pozicija_bokaX = $this->pdf->getX();
			//$linecount_boka = $this->pdf->getNumLines($stringCell_title, $defw_fc);
			
			// še dodamo textbox če je polj other
			$_txt = '';
									
			$stringCell_title .= $_txt; 
			
			if($pozicija_boka + $visina > 250)
			{	
				$this->pdf->AddPage('P');
				$pozicija_boka = $this->pdf->getY();
			}
			
			/*Izpis bočnega stolpca*/
			$this->pdf->MultiCell($defw_fc, 0,$stringCell_title,0,'C',0,1,0,0,true,0);
			$startY = $this->pdf->getY();				
			$endY = $this->pdf->getY();				
			$startX = $this->pdf->getX();					
//					print_r($pozicija_boka. " : ". $startY."<br>");
			$linecount_boka--;
			$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY part, vrstni_red");
			$shtevec=0;
			$startX = $startX + $defw_fc;
			$startY = $pozicija_boka;
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
			{					
				$prop['full'] = ($rowVsehVrednosti['id'] == $userAnswer['grd_id']) ? true : false;
				$this->pdf->SetXY($startX + $shtevec*$w_oc , ($startY+$endY)/2 - (CHCK_BTN_SIZE+1)/2);
				$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
				if($spremenljivke['tip']==6) {
					$this->pdf->RadioButton('vpr_'. $rowVrednost['id'], RADIO_BTN_SIZE, $prop);
				}
				elseif($spremenljivke['tip']==16) {
					$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
				}
				else{
					$this->pdf->setX($this->pdf->getX() - $w_oc/2);
					$this->pdf->SetFont(FNT_MAIN_TEXT, '', 8);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					//$this->pdf->TextBoxes($w_oc,LINE_BREAK);	
					$this->pdf->MultiCell($w_oc-1, LINE_BREAK,$userAnswer['text'],1,'C',0,1,0,0,true,0);
					$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
					$this->pdf->SetTextColor(0,0,0);
				}
				$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
				$shtevec++;
			}
			$this->pdf->setY($endY);
		}
	}
	
	// izris mesanega multigrida
	function displayGridMultiple($spremenljivke){
		
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
		$rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 8);
	
		$defw_full = 210;
		$defw_fc = 24; // sirina prve celice
		$defw_max = 35; // max sirina ostalih celic
		
		$sqlStVrednosti = sisplet_query("SELECT count(*) FROM srv_grid g, srv_grid_multiple m WHERE m.spr_id=g.spr_id AND m.parent='".$spremenljivke['id']."'");
		$rowStVrednost = mysqli_fetch_array($sqlStVrednosti);
		
		$kolon = $rowStVrednost['count(*)'] + 1;

		switch($kolon)
		{
			case 2:
				$defw_fc = 110;						 
			break;
			case 3:
				$defw_fc = 85;												 
			break;	
			case 4:
				$defw_fc = 60;						
			break;
			case 5:
				$defw_fc = 40;		
			break;
			case 6:
				$defw_fc = 35;						
			break;
			default:
			 $defw_fc = 24;					
		}			
		$w_oc = ( $defw_full - $defw_fc ) / $kolon;
		if ( $w_oc > $defw_max )
			$w_oc = $defw_max;

		$countVrednosti=0;
		$halfWidth = ($w_oc)/ 2;

		$sqlM = sisplet_query("SELECT * FROM srv_grid_multiple WHERE parent='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$multiple = array();
		while ($rowM = mysqli_fetch_array($sqlM)) {
			$multiple[] = $rowM['spr_id'];
		}
		
		// Prelom strani ce je kateri od naslovov gridov predolg
		$sqlVsehVrednsti = sisplet_query("SELECT g.naslov, g.variable FROM srv_grid g, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND g.spr_id=m.spr_id");
		$sqlMultiple = sisplet_query("SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (".implode(',', $multiple).") ORDER BY m.vrstni_red, g.vrstni_red");
		$linecount = 0;
		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			# priredimo naslov če prevajamo anketo
			$rowMultiple = mysqli_fetch_array($sqlMultiple);
			$naslov = $this->srv_language_grid($rowMultiple['spr_id'], $rowMultiple['id']);
			if ($naslov != '') {
				$rowVsehVrednosti['naslov'] = $naslov;
			}
					
			// če je naslov null izpišemo variable
			$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
			/*Zascita pred prepisovanjem na novo stran za zgornje parametre*/
			$pozicija_vrha = $this->pdf->getY();					
			
			$linecount = $this->pdf->getNumLines($stringHeader_title, $w_oc);
			$this->currentHeight = ($linecount > $this->currentHeight) ? $linecount : $this->currentHeight;

			if($pozicija_vrha + $linecount*4.7 > 250)
			{	
				$this->pdf->AddPage('P');						
				break;
			}
		}
		
		/*Izpis presledka na začetku*/
		$this->pdf->Cell($defw_fc, 0,'');
		
		// izišemo header celice
		$sqlVsehVrednsti = sisplet_query("SELECT g.naslov,g.variable,m.vrstni_red FROM srv_grid g, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND g.spr_id=m.spr_id ORDER BY m.vrstni_red");
		$sqlMultiple = sisplet_query("SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (".implode(',', $multiple).") ORDER BY m.vrstni_red, g.vrstni_red");
		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			# priredimo naslov če prevajamo anketo
			$rowMultiple = mysqli_fetch_array($sqlMultiple);
			$naslov = $this->srv_language_grid($rowMultiple['spr_id'], $rowMultiple['id']);
			if ($naslov != '') {
				$rowVsehVrednosti['naslov'] = $naslov;
			}
					
			// če je naslov null izpišemo variable
			$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
			
			/*Izpis zgornje vrstice*/
			$this->pdf->MultiCell($w_oc, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
		}

		$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
		
		$row_count = 1;
		$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			// barva vrstice
			$row_color = $row_count%2;
			
			# po potrebi prevedemo naslov 			
			$naslov = $this->srv_language_vrednost($rowVrednost['id']);
			if ($naslov != '') {
				$rowVrednost['naslov'] = $naslov;
			}
			
			$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
			$pozicija_boka = $this->pdf->getY();
			$pozicija_bokaX = $this->pdf->getX();
			//$linecount_boka = $this->pdf->getNumLines($stringCell_title, $defw_fc);
			
			// še dodamo textbox če je polj other
			$_txt = '';
							
			$stringCell_title .= $_txt.':'; 
			
			$linecount = $this->pdf->getNumLines($stringCell_title, $defw_fc);
			
			if($pozicija_boka + $linecount*4.7 > 250)
			{	
				$this->pdf->AddPage('P');
				$pozicija_boka = $this->pdf->getY();
			}
			/*Izpis bočnega stolpca*/
			$this->pdf->MultiCell($defw_fc, 0,$stringCell_title,0,'C',0,1,0,0,true,0);
			$startY = $this->pdf->getY();				
			$endY = $this->pdf->getY();				
			$startX = $this->pdf->getX();					
//					print_r($pozicija_boka. " : ". $startY."<br>");
			$linecount_boka--;
			
			$shtevec=0;
			$startX = $startX + $defw_fc;
			$startY = $pozicija_boka;
			
			// Vsaka druga vrstica ima sivo ozadje				
			$XX = $this->pdf->getX();
			$YY = $this->pdf->getY();
			$this->pdf->setXY(15, $startY);
			$this->pdf->SetFillColor(242,243,241);
			$this->pdf->MultiCell($defw_full, $endY-$startY,'',0,'C',$row_color,1,0,0,true,0);
			$this->pdf->SetFillColor(0);					
			$this->pdf->setXY($XX, $YY);				
			$row_count++;
			
			$sqlVsehVrednsti = sisplet_query("SELECT g.id AS id, s.tip AS tip, m.vrstni_red AS vrstni_red FROM srv_grid g, srv_spremenljivka s, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id AND s.id=g.spr_id ORDER BY m.vrstni_red");
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
			{							
				$this->pdf->SetXY($startX + $shtevec*$w_oc , ($startY+$endY)/2 - (CHCK_BTN_SIZE/2));
				$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
				if($rowVsehVrednosti['tip']==6) {
					$this->pdf->SetXY($startX + $shtevec*$w_oc + $halfWidth, ($startY+$endY)/2 - (CHCK_BTN_SIZE*3/4));
					$this->pdf->RadioButton('vpr_'. $rowVrednost['id'], RADIO_BTN_SIZE, false);
				}
				elseif($rowVsehVrednosti['tip']==16) {
					$this->pdf->SetXY($startX + $shtevec*$w_oc + $halfWidth, ($startY+$endY)/2 - (CHCK_BTN_SIZE)/2);
					$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, false);
					//$this->pdf->setY($this->pdf->getY());
				}
				else{
					$this->pdf->SetXY($this->pdf->getX() - $w_oc/2, ($startY+$endY)/2 - (CHCK_BTN_SIZE));
					
					$this->pdf->SetFont(FNT_MAIN_TEXT, '', 8);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					//$this->pdf->TextBoxes($w_oc,LINE_BREAK);	
					$this->pdf->SetFillColor(255,255,255);
					$this->pdf->MultiCell($w_oc-1, LINE_BREAK,'',1,'C',1,1,0,0,true,0);
					$this->pdf->SetFillColor(0);
					$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
					$this->pdf->SetTextColor(0,0,0);
				}
				$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
				$shtevec++;
			}
			$this->pdf->setY($endY);
		}
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace("&scaron;","š",$text);
		return strip_tags($text);
		return $text;
	}
	
	function encodeTextHtml($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace("&scaron;","š",$text);
		//return strip_tags($text);
		return $text;
	}

/* Skrajsa niz in doda ... nakoncu
 *  snippet(phrase,[max length],[phrase tail])
 *  snippetgreedy(phrase,[max length before next space],[phrase tail])
 *
 * iz: http://snipplr.com/view/9520/php-substring-without-breaking-words/
 */

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

	function setGrupa($grupa) {$this->grupa = $grupa;}
	function getGrupa() {return $this->grupa;}
	function setUserId($usrId) {$this->usrId = $usrId;}
	function getUserId() {return ($this->usrId)?$this->usrId:false;}
	function setDisplayFrontPage($display) {$this->pi['displayFrontPage'] = $display;}
	function getDisplayFrontPage() {return ($this->pi['displayFrontPage'] == true || $this->pi['displayFrontPage'] == 1);}
/// To bo najbolše dat v en class


    /**
    * @desc preveri ali so na trenutni grupi prikazana vprasanja (zaradi branchinga)
    */
    function checkGrupa () {

        $sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id = '".$this->grupa."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            if ($this->checkSpremenljivka($row['id']))
                return true;
        }
        return false;
    }


    /**
    * @desc preveri ali je spremenljivka vidna (zaradi branchinga)
    */
    function checkSpremenljivka ($spremenljivka) {

        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '".$spremenljivka."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        if ($row['visible'] == 0) return false;

        $sql1 = sisplet_query("SELECT * FROM srv_branching WHERE element_spr = '".$spremenljivka."'");
        if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
        $row1 = mysqli_fetch_array($sql1);

        /*if (!$this->checkIf($row1['parent']))
            return false;*/

        return true;
    }

    /**
    * @desc preveri ali se elementi v podanem IFu prikazejo ali ne
    */
    function checkIf ($if) {
        if ($if == 0) return true;

        // preverimo po strukturi navzgor
        $sql = sisplet_query("SELECT * FROM srv_branching WHERE element_if = '".$if."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);
        if (!$this->checkIf($row['parent'])) return false;

        // ce je IF oznacen kot blok, potem se vedno prikaze
        $sql = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);
        if ($row['tip'] == 1) return true;


        $eval = "if (";

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $i = 0;
        // zgeneriramo pogoje z oklepaji, ki jih potem spustimo skozi eval
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['conjunction'] == 0)
                    $eval .= ' && ';
                else
                    $eval .= ' || ';

            if ($row['negation'] == 1)
                $eval .= ' ! ';

            for ($i=1; $i<=$row['left_bracket']; $i++)
                $eval .= ' ( ';

            if ($this->checkCondition($row[id]))
                $eval .= ' true ';
            else
                $eval .= ' false ';

            for ($i=1; $i<=$row['right_bracket']; $i++)
                $eval .= ' ) ';

        }
        $eval .= ") return true; else return false; ";

        // ne glih best practice, ampak takle mamo...
        return eval($eval);

    }

    /**
    * @desc preveri podani condition
    */
    function checkCondition ($condition) {

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        // obicne spremenljivke
        if ($row['spr_id'] > 0) {
            $sql2 = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '$row[spr_id]'");
            $row2 = mysqli_fetch_array($sql2);


            // radio, checkbox, dropdown in multigrid
            if ($row2['tip'] <= 3 || $row2['tip'] == 6) {

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $sql3 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_data_vrednost".$this->db_table." v
                                         WHERE c.cond_id='$condition' AND c.vre_id=v.vre_id
                                         AND v.spr_id='$row[spr_id]' AND usr_id='".$this->getUserId()."'");
                    if ($row['operator'] == 0 && mysqli_num_rows($sql3) == 0)
                        return false;
                    elseif ($row['operator'] == 1 && mysqli_num_rows($sql3) > 0)
                        return false;
                // multigrid
                } elseif ($row['vre_id'] > 0) {
                    $sql3 = sisplet_query("SELECT * FROM srv_condition_grid c, srv_data_grid".$this->db_table." d
                                         WHERE c.cond_id='$condition' AND d.spr_id='$row[spr_id]'
                                         AND c.grd_id=d.grd_id AND d.usr_id='".$this->getUserId()."'");
                    if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                    if ($row['operator'] == 0 && !mysqli_num_rows($sql3) > 0)
                        return false;
                    elseif ($row['operator'] == 1 && !mysqli_num_rows($sql3) == 0)
                        return false;
                }

            // number in text
            } else {

                $sql3 = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='$row[spr_id]' AND usr_id='".$this->getUserId()."'");
                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                $row3 = mysqli_fetch_array($sql3);

                if ($row['operator'] == 0 && !($row3['text'] == $row['text']))
                    return false;
                elseif ($row['operator'] == 1 && !($row3['text'] != $row['text']))
                    return false;
                elseif ($row['operator'] == 2 && !($row3['text'] < $row['text']))
                    return false;
                elseif ($row['operator'] == 3 && !($row3['text'] <= $row['text']))
                    return false;
                elseif ($row['operator'] == 4 && !($row3['text'] > $row['text']))
                    return false;
                elseif ($row['operator'] == 5 && !($row3['text'] >= $row['text']))
                    return false;

            }

        // recnum
        } elseif ($row['spr_id'] == -1) {

            $sqlu = sisplet_query("SELECT * FROM srv_user WHERE id = '".$this->getUserId()."'");
            $rowu = mysqli_fetch_array($sqlu);

            if (!($rowu['recnum'] % $row['modul'] == $row['ostanek']))
                return false;

        }

        return true;
    }

    /**
    * @desc poisce naslednjo stran - grupo, 0 pomeni konec
    */
    function findNextGrupa() {

        //vrstni red trenutne grupe
		if ($this->grupa > 0) {
            $sql = sisplet_query("SELECT * FROM srv_grupa WHERE id = '".$this->grupa."'");
            $row = mysqli_fetch_array($sql);
            $vrstni_red = $row['vrstni_red'];
        } else {
            $vrstni_red = 0;
        }

        $sql = sisplet_query("SELECT * FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' AND vrstni_red>'".$vrstni_red."' ORDER BY vrstni_red ASC LIMIT 1");

        // naslednja stran
        if (mysqli_num_rows($sql) > 0) {
				$row = mysqli_fetch_array($sql);
				return $row['id'];
		}

		// konec
		else {
            return 0;
        }
    }

	function displayIf($if){
		global $lang;
		
    	$sql_if = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
    	$row_if = mysqli_fetch_array($sql_if);

        // Blok
		if($row_if['tip'] == 1)
			$output = strtoupper($lang['srv_block']).' ';
		// Loop
		elseif($row_if['tip'] == 2)
			$output = strtoupper($lang['srv_loop']).' ';
		// IF
		else
			$output = 'IF ';
      
		$sql_if = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
		$row_if = mysqli_fetch_array($sql_if);
		$output .= '('.$row_if['number'].') ';

        $sql = Cache::srv_condition($if);
        
        $bracket = 0;
        $i = 0;
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['conjunction'] == 0)
                    $output .= ' and ';
                else
                    $output .= ' or ';

            if ($row['negation'] == 1)
                $output .= ' NOT ';

            for ($i=1; $i<=$row['left_bracket']; $i++)
				$output .=  ' ( ';

            // obicajne spremenljivke
            if ($row['spr_id'] > 0) {

				$row2 = Cache::srv_spremenljivka($row['spr_id']);
				
                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Cache::srv_spremenljivka($row['spr_id']);
                // multigrid
                } elseif ($row['vre_id'] > 0) {
                    $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    $row1 = mysqli_fetch_array($sql1);
                } else
                    $row1 = null;

                $output .= $row1['variable'];

                // radio, checkbox, dropdown in multigrid
                if (($row2['tip'] <= 3 || $row2['tip'] == 6) && ($row['spr_id'] || $row['vre_id'])) {

                    if ($row['operator'] == 0)
                        $output .= ' = ';
                    else
                        $output .= ' != ';

                    $output .= '[';

                    // obicne spremenljivke
                    if ($row['vre_id'] == 0) {
                        $sql2 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    // multigrid
                    } elseif ($row['vre_id'] > 0) {
                        $sql2 = sisplet_query("SELECT g.* FROM srv_condition_grid c, srv_grid g WHERE c.cond_id='$row[id]' AND c.grd_id=g.id AND g.spr_id='$row[spr_id]'");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    }

                    $output .= ']';

                // textbox in nubmer mata drugacne pogoje in opcije
                } elseif ($row2['tip'] == 4 || $row2['tip'] == 21 || $row2['tip'] == 7 || $row2['tip'] == 22) {

                    if ($row['operator'] == 0)
                        $output .= ' = ';
                    elseif ($row['operator'] == 1)
                        $output .= ' <> ';
                    elseif ($row['operator'] == 2)
                        $output .= ' < ';
                    elseif ($row['operator'] == 3)
                        $output .= ' <= ';
                    elseif ($row['operator'] == 4)
                        $output .= ' > ';
                    elseif ($row['operator'] == 5)
                        $output .= ' >= ';

                    $output .= '\''.$row['text'].'\'';

                }

            // recnum
            } elseif ($row['spr_id'] == -1) {

                $output .= 'mod(recnum, '.$row['modul'].') = '.$row['ostanek'];

			} 

            for ($i=1; $i<=$row['right_bracket']; $i++)
				$output .= ' ) ';
        }
        
        if ($row_if['label'] != '') {
	        $output .= ' (';
	        $output .= ' '.$row_if['label'].' ';
	        $output .= ') ';      		
        }
		
		$this->pdf->SetTextColor(0,0,150);
		$this->pdf->setFont('','B',$this->font);
		$this->pdf->MultiCell(90, 1, $this->encodeText($output),0,'L',0,1,0,0);
		$this->pdf->SetTextColor(0,0,0);
		$this->pdf->setFont('','',$this->font);
	}
	
	function getCellHeight($string, $width){
		
		$this->pdf->startTransaction();
		// get the number of lines calling you method
		$linecount = $this->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();
			
		$height = ($linecount <= 1) ? 7 : $linecount * ($this->pdf->getFontSize() * $this->pdf->getCellHeightRatio()) + 2;
		
		return $height;
	}
	
	
	 /**
	 * prevod za srv_spremenljivka
	 */
	 function srv_language_spremenljivka ($spremenljivka) {
		 
		 if ($this->language != -1) {
			$sqll = sisplet_query("SELECT * FROM srv_language_spremenljivka WHERE ank_id='".$this->anketa['id']."' AND spr_id='".$spremenljivka['id']."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			return $rowl;
		 }
		
		return false;
	 }
	 
	 /**
	 * vrne prevod za srv_vrednost
	 * 
	 * @param mixed $vrednost
	 */
	 function srv_language_vrednost ($vrednost) {

		 if ($this->language != -1) {	
			$sqll = sisplet_query("SELECT * FROM srv_language_vrednost WHERE ank_id='".$this->anketa['id']."' AND vre_id='".$vrednost."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			if ($rowl['naslov'] != '') return $rowl['naslov'];
		 }
		 
		 return false;	 
	 }
	 
	 /**
	 * vrne prevod za srv_grid
	 * 
	 * @param mixed $vrednost
	 */
	 function srv_language_grid ($spremenljivka, $grid) {
	 	 
		 if ($this->language != -1) {
			$sqll = sisplet_query("SELECT * FROM srv_language_grid WHERE ank_id='".$this->anketa['id']."' AND spr_id='".$spremenljivka."' AND grd_id='".$grid."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			if ($rowl['naslov'] != '') return $rowl['naslov'];	
		 }
		 
		 return false;		 
	 }
	 
	/**
	* vrne prevod za uvod
	* 
	*/
	function srv_language_intro () {

		// Prevedemo uvod ce je slucajno potrebno
		if ($this->language != -1) {
			$sql1 = sisplet_query("SELECT naslov FROM srv_language_spremenljivka WHERE ank_id='".$this->anketa['id']."' AND spr_id='-1' AND lang_id='".$this->language."'");
			$row1 = mysqli_fetch_array($sql1);
			
			if ($row1['naslov'] != '') 
				return strip_tags($row1['naslov']);
		}
		
		return false;
	}
	 
	 
	function checkLineHeight($height){
		//echo '<script>console.log('.$height.'); </script>';
		if ($height >= 10){	// ce je visina vecja od 10 mm, ker trenutno je enota v mm
			$this->pdf->MultiCell($width, $height, "");	//dodaj vrstico ustrezne visine
		}
	}
	
	function addOpomba($info, $maxHeight){
		// Izpisemo opombo, ce jo imamo
		if($info != ''){			
			//$this->pdf->Ln(1);
			$this->pdf->setFont('','',$this->font-2);
			$this->pdf->SetTextColor(100,100,100);
			//$this->pdf->WriteHTML($spremenljivke['info']);
			//$this->pdf->Write(0, $this->encodeText($info), '', 0, 'l', 1, 1);
			$this->pdf->Cell(50,0,$this->encodeText($info),0,0,'L',0);
			$this->pdf->setFont('','',$this->font);
			$this->pdf->SetTextColor(0);
		}
		//$this->checkLineHeight($maxHeight);
		//$this->pdf->setFont('','',$this->font);
		$this->pdf->Ln(LINE_BREAK);
	}
	
	function displayImageHotSpotHeatmap($odgovor)
	{
		$this->pdf->Cell(2, 5, '');
		$y1=$this->pdf->GetY();
		$x1=$this->pdf->GetX();
		$this->pdf->writeHTMLCell(100,50,$x1,$y1,$odgovor,1,0,0,1,'L',1);
		$height = $this->pdf->getLastH();
		$this->checkLineHeight($height);
		$this->pdf->Ln(LINE_BREAK);
	}
	
	function displaySeznam($headerTitles, $cellTitle)
	{
		$this->pdf->Cell(2, 5, '');
		$y1=$this->pdf->GetY();
		$x1=$this->pdf->GetX();
		
		$this->pdf->writeHTMLCell(100,50,$x1,$y1,$cellTitle,0,0,0,1,'L',1);	//izris levega dela		
		$height1 = $this->pdf->getLastH();	//visina levega dela
		
		$this->pdf->writeHTMLCell(40,0,$x1+100,$y1,$headerTitles,1,0,0,1,'L',1); //izris desnega dela
		$height2 = $this->pdf->getLastH();	//visina desnega dela
		
		if($height1 > $height2)
		{
			$this->checkLineHeight($height1);
		}else{
			$this->checkLineHeight($height2);
		}		
	}
	
	function displayDragDrop($headerTitles, $cellTitles)
	{
		$this->pdf->Cell(2, 3, '');
		$y1=$this->pdf->GetY();
		$x1=$this->pdf->GetX();
		$this->pdf->writeHTMLCell(120,5,$x1,$y1-15,"Drag and drop",1,0,0,1,'L',1);	//izris levega dela
		$this->pdf->writeHTMLCell(100,50,$x1,$y1,$cellTitles,0,0,0,1,'L',1);	//izris levega dela		
		$height1 = $this->pdf->getLastH();	//visina levega dela
		
		
		$this->pdf->writeHTMLCell(40,0,$x1+100,$y1,$headerTitles,0,0,0,1,'L',1); //izris desnega dela
		//$height2 = $this->pdf->getLastH();	//visina desnega dela
		
		if($height1 > $height2)
		{
			$this->checkLineHeight($height1);
		}else{
			$this->checkLineHeight($height2);
		}
		
	}
	
}


?>