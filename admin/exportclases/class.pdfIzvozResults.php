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
require_once('../survey/definition.php');


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
class PdfIzvozResults {

	var $anketa;// = array();			// trenutna anketa

	var $grupa = null;					// trenutna grupa

	var $spremenljivka;					// trenutna spremenljivka
	var $printPreview = false;			// ali kliče konstruktor
	var $pi=array('canCreate'=>false); 	// za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	
	var $currentHeight = 0;		// visina trenutnega vprasanja

	var $SUS; //SurveyUserSettng
	var $SA; //SurveyAnketa
	
	var $db_table = '';
	
	var $type = 0;			// nacin izpisa vprasanj - kratek -> 0, dolg -> 1, 2 -> zelo kratek
	var $pageBreak = 0;		// vsak respondent na svoji strani
	var $showIf = 0;		// izpis if-ov
	var $font = 10;			// velikost pisave
	var $numbering = 0; 	// ostevillcevanje vprasanj
	var $showRecnum = 1; 	// prikaz recnuma
	var $skipEmpty = 0; 	// izpusti vprasanja brez odgovora
	var $skipEmptySub = 0; 	// izpusti podvprasanja brez odgovora
	var $landscape = 0; 	// landscape izpis
		
	var $loop_id = null;	// id trenutnega loopa ce jih imamo	
	
	var $usr_type = null;		// tip userja (null->iz vmesnika, author->avtor iz maila, respondent->respondent iz maila, other->other iz maila)
	var $usr_id = null;			// id userja ki je odgovarjal (na katerega so vezani podatki)
	var $resp_id = null;		// id userja na katerega so vezane nastavitve ankete (filtriranje spremenljivk...)
	
	var $admin_type = -1;		// tip userja ki odpira pdf (posebej nastavimo ker global admin_type ne dela iz mailov)

	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $usr_type = null, $usr_id = null)
	{		
		global $site_path;
		global $global_user_id;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;			

			$this->usr_id = $_GET['usr_id'];
			
			// Ce prihajamo iz maila imamo nastavljen usr_type in usr_id
			if($usr_type != null && $usr_id != null){				
				$this->usr_type = $usr_type;			

				//$this->resp_id = $usr_id;	
				// Ajda hoce da ce dobi po mailu pdf mora bit isti kot ga dobi respondent
				$this->resp_id = $_GET['usr_id'];
			}
			// Drugace prihajamo normalno iz podatkov (usr_id je avtorjev -> $global_user_id)
			else{
				$this->resp_id = $global_user_id;
			}	

			// Nastavimo admin type
			$sqlU = sisplet_query("SELECT type FROM users WHERE id='".$this->resp_id."'");
			if($rowU = mysqli_fetch_array($sqlU))
				$this->admin_type = $rowU['type'];

				
			// Po novem imamo globalne nastavitve
			SurveySetting::getInstance()->Init($anketa);
			$this->type = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_type');
			$this->font = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_font_size');
			$this->showIf = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_if');
			$this->numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_numbering');
			$this->pageBreak = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_PB');
			$this->showRecnum = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum');
			$this->skipEmpty = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty');
			$this->skipEmptySub = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty_sub');
			$this->landscape = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_landscape');
			
			
			SurveyStatusProfiles::Init($anketa);

			// create new PDF document
			$orientation = ($this->landscape == 1) ? 'L' : 'P'; 
			$this->pdf = new enka_TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		{
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $global_user_id);
			
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
		if(!$this->getUserId()){
			$this->pdf->setPrintHeaderFirstPage(false);
			$this->pdf->setPrintFooterFirstPage(false);
		}
		else{
			$this->pdf->setPrintHeaderFirstPage(true);
			$this->pdf->setPrintFooterFirstPage(true);
		}
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
		if($this->getUserId()){
			$sqlu = sisplet_query("SELECT * FROM srv_user WHERE id = '".$this->getUserId()."'");
			$rowu = mysqli_fetch_array($sqlu);
			
			$rightTitle = ($this->showRecnum == 1) ? SurveyInfo::getInstance()->getSurveyAkronim().' (recnum '.$rowu['recnum'].')' : SurveyInfo::getInstance()->getSurveyAkronim();			
			$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText($rightTitle));
			
			//nastavimo datum za footer
			$this->pdf->SetFooterDate($rowu['time_edit']);
		}
		else
			$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()));

		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		return true;
	}

	function createPdf()
	{
		// Izpis vseh odgovorov (vsi respondenti -> max 300)
		if(!$this->getUserId())	
			$this->outputAllResults();	

		// Izpis odgovorov enega respondenta
		else
			$this->outputSurvey();
	}

	function createFrontPage()
	{
		global $lang;
		
		// dodamo prvo stran
		$this->pdf->AddPage();
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', 16);

		// dodamo naslov
  		$this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(128, 0, 0);
        $this->pdf->SetLineWidth(0.1);
		$this->pdf->Sety(100);

		if(!$this->getUserId()){
			$this->pdf->Cell(0, 10, $this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), 'TLR', 1,'C', 1, 0,0);
			$this->pdf->SetFont(FNT_MAIN_TEXT, '', 13);
			$this->pdf->Cell(0, 10, $this->encodeText($lang['export_firstpage_results']), 'BLR', 1,'C', 1, 0,0);
		}
		else{
			$this->pdf->Cell(0, 16, $this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), 1, 1,'C', 1, 0,0);
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
			$this->pdf->MultiCell(95, 5, $lang['export_firstpage_longname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->MultiCell(95, 5, $lang['export_firstpage_qcount'].': '.SurveyInfo::getInstance()->getSurveyQuestionCount(), 0, 'L', 0, 1, 0 ,0, true);
		
		/// Aktiviranost
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
	
	// Izpis vseh userjev, ki so odgovorili
	function outputAllResults(){		
		global $lang;
		
		//$izbranStatusProfile = SurveyUserSetting :: getInstance()->getSettings('default_status_profile_export');	
		$izbranStatusProfile = SurveyStatusProfiles :: getStatusAsQueryString();
		
		$sqlu = sisplet_query("SELECT * FROM srv_user WHERE ank_id = '".$this->anketa['id']."' ".$izbranStatusProfile." AND deleted='0' AND preview='0' ORDER BY recnum");

		//ce imamo vec kot 300 anketirancev ne izpisemo
		$count = mysqli_num_rows($sqlu);
		if( $count > 300 ){
			$this->pdf->AddPage();
				
			$this->pdf->setFont('','B','15');
			$this->pdf->MultiCell(150, 5, 'NAPAKA!', 0, 'L', 0, 1, 0 ,0, true);
			$this->pdf->MultiCell(150, 5, 'Izpis ni možen zaradi prevelikega števila odgovorov ('.$count.')', 0, 'L', 0, 1, 0 ,0, true);
			$this->pdf->ln(5);
		}		
		else{
			// izpišemo prvo stran
			$this->createFrontPage();
			if($this->pageBreak == 0)
				$this->pdf->AddPage();
		
			while( $rowu = mysqli_fetch_array($sqlu) ){
								
				//izpis statusa
				switch($rowu['last_status']){
					case '0':
						$status = $lang['srv_userstatus_0'];
						break;
					case '1':
						$status = $lang['srv_userstatus_1'];
						break;
					case '2':
						$status = $lang['srv_userstatus_2'];
						break;
					case '3':
						$status = $lang['srv_userstatus_3'];
						break;
					case '4':
						$status = $lang['srv_userstatus_4'];
						break;
					case '5':
						$status = $lang['srv_userstatus_5'];
						break;
					case '6':
						$status = $lang['srv_userstatus_6'];
						$status .= ($rowu['lurker'] == '1') ? ' - lurker' : '';
						break;
				}
								
				if($this->pageBreak == 1)
					$this->pdf->AddPage();
				
				$this->pdf->setFont('','B', 14);
		
				if($this->showRecnum == 1)
					$this->pdf->MultiCell(150, 5, 'Recnum '.$rowu['recnum'].' (status '.$rowu['last_status'].' - '.$status.')', 0, 'L', 0, 1, 0 ,0, true);
				else
					$this->pdf->MultiCell(150, 5, 'Status '.$rowu['last_status'].' - '.$status, 0, 'L', 0, 1, 0 ,0, true);
				
				$this->pdf->ln(5);
				
				//izpis posameznega userja
				$this->usr_id = $rowu['id'];
				$this->outputUser();
			}
		}
	}
	
	// Izpis vprasalnika z odgovori
	function outputSurvey(){
		global $lang;
		
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		// izpišemo prvo stran
		if (false)
			$this->createFrontPage();

		// Izpisemo vprasalnik
		$this->pdf->AddPage();

		// filtriramo spremenljivke glede na profil - SAMO CE NE PRIHAJAMO IZ MAILA!
		$tmp_svp_pv = array();
		if($this->usr_type == null){
			SurveyVariablesProfiles :: Init($this->anketa['id']);
			
			$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
			$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
			
			$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);
			
			foreach ( $tmp_svp_pv as $vid => $variable) {
				$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
			}
		}
		
		// ce obstaja intro izpisemo intro - pri izpisu vprasalnika brez odgovorov (ce smo na prvi strani moramo biti v razsirjenem nacinu)
		/*if(($rowA['expanded'] == 1 && $this->allResults == 3) || (!$this->getUserId() && $this->allResults == 0)){
			if ( SurveyInfo::getInstance()->getSurveyShowIntro() )
			{ 		
				$intro = (SurveyInfo::getInstance()->getSurveyIntro() == '') ? $lang['srv_intro'] : SurveyInfo::getInstance()->getSurveyIntro();
				
				$this->pdf->Write  (0, $this->encodeText(SurveyInfo::getInstance()->getSurveyIntro()), '', 0, 'L', 1, 1);
				$this->pdf->Ln(LINE_BREAK);
				//$this->pdf->drawLine();
				$this->pdf->Ln(LINE_BREAK);
			}
		}*/

		
		if ( $this->getGrupa() )
		{
			$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' AND id = '".$this->getGrupa()."' ORDER BY vrstni_red";
		}
		else
		{
			$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' ORDER BY vrstni_red";
		}
		$sqlGrupe = sisplet_query($sqlGrupeString);
		while ( $rowGrupe = mysqli_fetch_assoc( $sqlGrupe ) ){	// sprehodmo se skozi grupe ankete 
			
			$this->grupa = $rowGrupe['id'];
			
			// Pogledamo prvo spremenljivko v grupi ce je v loopu
			$sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
			$row = mysqli_fetch_array($sql);

			// ce je ima loop za parenta
			$if_id = $this->find_parent_loop($row['id']);
			if ($if_id > 0){
				$sql1 = sisplet_query("SELECT * FROM srv_loop WHERE if_id = '$if_id'");
				$row1 = mysqli_fetch_array($sql1);
				$this->loop_id = $this->findNextLoopId($row1['if_id']);
				
				$if = Cache::srv_if($if_id);
				$loop_title = $if['label'];
				
				// gremo cez vse spremenljivke v trenutnem loopu
				while($this->loop_id != null){
				
					// Izrisemo naslov loopa
					$this->pdf->SetTextColor(0,0,200);
					$this->pdf->SetDrawColor(0,0,200);
					$this->pdf->setFont('','B',$this->font);
					
					$this->pdf->Write (0, $this->encodeText($this->dataPiping($loop_title)), '', 0, 'L', 1, 1);
					
					$this->pdf->SetTextColor(0,0,200);
					$this->pdf->SetDrawColor(0,0,200);
					$this->pdf->setFont('','',$this->font);
					

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


							$this->pdf->SetTextColor(0,0,0);
							$this->pdf->SetDrawColor(0,0,0);
							
										
							// Izpis vprasalnika z rezultati
							
							// Ce imamo kombinirano tabelo pogledamo ce prikazujemo katero od podtabel
							if($rowSpremenljivke['tip'] == 24){
								
								$subGrids = array();
								$showGridMultiple = false;
								
								// Loop po podskupinah gridov					
								$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivka."' AND m.spr_id=s.id");
								while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){					
									if(in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0){
										$showGridMultiple = true;
										break;
									}
								}
							}
							// ce je nastavljen profil s filtriranimi spremenljivkami
							if(in_array($spremenljivka,$tmp_svp_pv) || count($tmp_svp_pv) == 0 || ($rowSpremenljivke['tip'] == 24 && $showGridMultiple) || $rowSpremenljivke['tip'] == 5){
								
								$this->currentHeight = 0;
								
								// NAVADEN IZPIS rezultatov spremenljivke - kratek samo pri radio, checkbox, multiradio, multicheckbox, besedilo	
								//if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16,21,7,8)) ){
								if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16,21,7,8,27)) ){
									if($rowSpremenljivke['tip'] < 4)
										$this->outputVprasanjeValues($rowSpremenljivke);
									else
										$this->outputVprasanje($rowSpremenljivke);
										
									$this->outputSpremenljivkeValues($rowSpremenljivke);						
								}
								
								// KRATEK IZPIS rezultatov spremenljivke
								elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
									$this->outputVprasanjeValues($rowSpremenljivke);
									$this->outputSpremenljivkeValues($rowSpremenljivke);
								}	
								
								// DOLG IZPIS rezultatov
								else{
									$this->outputVprasanje($rowSpremenljivke);
									$this->outputSpremenljivke($rowSpremenljivke);	
								}
								
								$this->pdf->Ln(LINE_BREAK);
							}											
						}
					}
								
					$this->loop_id = $this->findNextLoopId();					
				}
			}
			// Navadne spremenljivke ki niso v loopu
			else{
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


						$this->pdf->SetTextColor(0,0,0);
						$this->pdf->SetDrawColor(0,0,0);
						
									
						// Izpis vprasalnika z rezultati
						
						// Ce imamo kombinirano tabelo pogledamo ce prikazujemo katero od podtabel
						if($rowSpremenljivke['tip'] == 24){
							
							$subGrids = array();
							$showGridMultiple = false;
							
							// Loop po podskupinah gridov					
							$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivka."' AND m.spr_id=s.id");
							while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){					
								if(in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0){
									$showGridMultiple = true;
									break;
								}
							}
						}
						// ce je nastavljen profil s filtriranimi spremenljivkami
						if(in_array($spremenljivka,$tmp_svp_pv) || count($tmp_svp_pv) == 0 || $rowSpremenljivke['tip'] == 5 || ($rowSpremenljivke['tip'] == 24 && $showGridMultiple)){
														
							$this->currentHeight = 0;
							
							// NAVADEN IZPIS rezultatov spremenljivke - kratek samo pri radio, checkbox, multiradio, multicheckbox, besedilo	
							if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16,21,7,8)) ){
								if($rowSpremenljivke['tip'] < 4)
									$this->outputVprasanjeValues($rowSpremenljivke);
								else
									$this->outputVprasanje($rowSpremenljivke);
									
								$this->outputSpremenljivkeValues($rowSpremenljivke);						
							}
							
							// KRATEK IZPIS rezultatov spremenljivke
							elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
								$this->outputVprasanjeValues($rowSpremenljivke);
								$this->outputSpremenljivkeValues($rowSpremenljivke);
							}	
							
							// DOLG IZPIS rezultatov
							else{
								$this->outputVprasanje($rowSpremenljivke);
								$this->outputSpremenljivke($rowSpremenljivke);	
							}
							
							$this->pdf->Ln(LINE_BREAK);
						}
					}
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
	

	function outputVprasanje($spremenljivke){		
	
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		// Izpisemo tekst vprasanja
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		
		$pozicija_vprasanja = $this->pdf->getY();
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
        $rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 8);
		
		$naslov = $this->dataPiping($spremenljivke['naslov']);
		
		$linecount_vprasanja = $this->pdf->getNumLines($naslov, $this->pdf->getPageWidth());
		
		if($pozicija_vprasanja + $linecount_vprasanja*4.7 /*+ $visina*/ > 240/$expand_width)
		{	
			$this->pdf->AddPage();								
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
		
		$this->pdf->Write  (0, $numberingText . $this->encodeText($naslov), '', 0, 'l', 1, 1);
		/*$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
		$this->pdf->WriteHTML($text);*/
		
		$this->pdf->setFont('','',$this->font);
		
		if($spremenljivke['tip'] != 5)
			$this->pdf->Ln(LINE_BREAK);
	}

	function outputSpremenljivke($spremenljivke){
		global $site_url;
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
	
		// Ce je spremenljivka v loopu
		$loop_id = $this->loop_id == null ? " IS NULL" : " = '".$this->loop_id."'";
	
		switch ( $spremenljivke['tip'] )
		{
			case 1: //radio
			case 2: //check
			case 3: //select -> radio

				if ($this->getUserId())
				{
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spremenljivke[id]' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					while ($rowAnswers = mysqli_fetch_assoc($sqlUserAnswer))
					$userAnswer[$rowAnswers['vre_id']] = $rowAnswers['vre_id'];
				}
			
				// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				
				$width = 180*$expand_width;
				
				//ce imamo prikaz v vec stoplcih
				$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
				$stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);		
				$checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);		
				
				if ($stolpci > 1 && $spremenljivke['orientation']==1) {
					//echo '<div style="float:left; width:'.(100/$stolpci).'%">';
					$kategorij = mysqli_num_rows($sqlVrednosti);
					$v_stolpcu = ceil($kategorij / $stolpci);
					
					$width = round(180*$expand_width / $stolpci);
				}		
				
				$yStart = $this->pdf->GetY();
				$xStart = $this->pdf->GetX();
				$count = 0;
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					$prop['full'] = ( isset($userAnswer[$rowVrednost['id']]) );

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
							$this->pdf->RadioButton('vpr_'. $spremenljivke['id'], RADIO_BTN_SIZE, $prop);
							$y1=$this->pdf->GetY();
							$x1=$this->pdf->GetX();
						}
					else if ( $spremenljivke['tip']  == 2 )
						{
							$this->pdf->CheckBox('vpr_'. $spremenljivke['id'].'_'.$rowVrednost['id'], CHCK_BTN_SIZE, $prop);
							$y=$this->pdf->GetY();
							$x=$this->pdf->GetX();
						}

					$this->pdf->Cell(4, 0, '');
					$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					
					// še dodamo textbox če je polj other				
					if ($rowVrednost['other'] == 1 && $this->getUserId())
					{
						$_txt = '';
						
						$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
						$row4 = mysqli_fetch_assoc($sqlOtherText);
						$_txt = $row4['text'];

						$stringTitle .= ' '.$_txt;
					}
					
					$this->currentHeight = $this->pdf->getNumLines($stringTitle, $width);
					
					if ( $spremenljivke['tip']  == 2 )
					{
						$y=$y-2;
						$x=$x+3;
						$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x,$y);
					}
					else
					{
						$y1=$y1-1;
						$x1=$x1+3;
						$this->pdf->MultiCell($width, 1, $stringTitle,0,'L',0,0,$x1,$y1);
					}
					
					$this->currentHeight = ($this->currentHeight == 1) ? 2 : $this->currentHeight;
					$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
					
					$count++;
				}
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
			
                $defw_full = 210*$expand_width;
                $defw_fc = 24*$expand_width; // sirina prve celice
                $defw_max = 35*$expand_width; // max sirina ostalih celic
				
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
						$defw_fc = 110*$expand_width;						 
					break;
					case 3:
						$defw_fc = 85*$expand_width;												 
					break;	
					case 4:
						$defw_fc = 60*$expand_width;						
					break;
					case 5:
						$defw_fc = 40*$expand_width;		
					break;
					case 6:
						$defw_fc = 35*$expand_width;						
					break;
					default:
					 $defw_fc = 24*$expand_width;					
				}			
                $w_oc = ( $defw_full - $defw_fc ) / $kolon;
				if ( $w_oc > $defw_max )
					$w_oc = $defw_max;

				$countVrednosti=0;
                $halfWidth = ($w_oc)/ 2;

	
				// Prelom strani ce je kateri od naslovov gridov predolg
				$sqlVsehVrednsti = sisplet_query("SELECT naslov, variable,vrstni_red FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				$linecount = 0;
				$break = false;
                while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
                {
                	// če je naslov null izpišemo variable
                	$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
					/*Zascita pred prepisovanjem na novo stran za zgornje parametre*/
					$pozicija_vrha = $this->pdf->getY();					
					
					$linecount = $this->pdf->getNumLines($stringHeader_title, $w_oc);
					$this->currentHeight = ($linecount > $this->currentHeight) ? $linecount : $this->currentHeight;
					
					if(!$break && $pozicija_vrha + $linecount*4.7 > 250/$expand_width)
					{	
						$this->pdf->AddPage();
						$break = true;
					}
                }
				
				/*Izpis presledka na začetku*/
                $this->pdf->Cell($defw_fc, 0,'');
				
				// izišemo header celice
				$sqlVsehVrednsti = sisplet_query("SELECT naslov, variable,vrstni_red FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
                {
                	// če je naslov null izpišemo variable
                	$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
					
					/*Izpis zgornje vrstice*/
					$this->pdf->MultiCell($w_oc, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
                }

				$this->pdf->setY($this->pdf->getY() + $this->currentHeight*6);
				
				$row_count = 1;
				$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
                {	
					$skipRow = false;
					
					// Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici
					if($this->skipEmptySub == 1){
						
						$skipRow = true;
						if($spremenljivke['tip'] == 6)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
						elseif($spremenljivke['tip'] == 16)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
						else
							$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");

						if(mysqli_num_rows($sqlUserAnswer) > 0)
							$skipRow = false;
					}
					
					
					if($skipRow == false){
					
						// barva vrstice
						$row_color = $row_count%2;
						
						$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
						$pozicija_boka = $this->pdf->getY();
						$pozicija_bokaX = $this->pdf->getX();
						
						// še dodamo textbox če je polj other
						$_txt = '';
						if ( $rowVrednost['other'] == 1 && $this->getUserId() )
						{
							$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
							$row4 = mysqli_fetch_assoc($sqlOtherText);
							$_txt = ' '.$row4['text'];
						}										
						$stringCell_title .= $_txt.':'; 
						
						$firstHeight = 0;
						
						$linecount = $this->pdf->getNumLines($stringCell_title, $defw_fc);
						
						$cellHeight1 = 0;
						$cellHeight2 = 0;
						if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1){
							$stringCell_title2 = $this->encodeText($rowVrednost['naslov2']);
							$linecount = ($this->pdf->getNumLines($stringCell_title2, $defw_fc) > $linecount) ? $this->pdf->getNumLines($stringCell_title2, $defw_fc) : $linecount;
						
							$cellHeight1 = $this->getCellHeight($this->encodeText($stringCell_title), $defw_fc);
							$cellHeight2 = $this->getCellHeight($this->encodeText($stringCell_title2), $defw_fc);
							
							$endY = ($cellHeight2 > $cellHeight1) ? $pozicija_boka+$cellHeight2 : $pozicija_boka+$cellHeight1;
							$endY = ($endY - $startY > LINE_BREAK) ? $endY : $pozicija_boka+LINE_BREAK;
							$startX = $this->pdf->getX() + $defw_fc;
						}
						
						if($pozicija_boka + $linecount*4.7 > 250/$expand_width)
						{	
							$this->pdf->AddPage();
							$pozicija_boka = $this->pdf->getY();
						}
						$firstHeight = $linecount;
												
						if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1){
							$endY = ($cellHeight2 > $cellHeight1) ? $pozicija_boka+$cellHeight2 : $pozicija_boka+$cellHeight1;
							$endY = ($endY - $startY > LINE_BREAK) ? $endY : $pozicija_boka+LINE_BREAK;
						}
						
						$startX = $this->pdf->getX() + $defw_fc;
						$startY = $pozicija_boka;
						
						/*Izpis bočnega stolpca*/	
						if($cellHeight2 > $cellHeight1)
								$this->pdf->SetXY($this->pdf->getX(), ($startY+$endY)/2 - ($cellHeight1)/2);						
						$this->pdf->MultiCell($defw_fc, $cellHeight1, $stringCell_title,0,'C',0,1,0,0,true,0);	
						
						if($spremenljivke['tip'] != 6 || $spremenljivke['enota'] != 1){
							$endY = $this->pdf->getY();
						}
						
						$shtevec = 0;

						// Pri multitext in multinumber loopamo cez vrstico da dobimo visino najvisje celice
						if($spremenljivke['tip'] == 19 || $spremenljivke['tip'] == 20){
				
							$height = 0;
							
							$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
							while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
								// poiščemo kaj je odgovoril uporabnik:
								if ($this->getGrupa())
								{
									$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");					
									$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								}
								
								$linecount = $this->pdf->getNumLines($userAnswer['text'], $w_oc);
								$height = ($linecount*4.2 > $height) ? $linecount*4.2 : $height;
							}	
							
							if($height > 5){				
								$endY += $height - LINE_BREAK;
							}
							else{
								$height = LINE_BREAK;
							}
							$height = ($height > $firstHeight*4.7) ? $height : $firstHeight*4.7;
						}
		
						// Vsaka druga vrstica ima sivo ozadje				
						$XX = $this->pdf->getX();
						$YY = $this->pdf->getY();
						$this->pdf->setXY(15, $startY);
						$this->pdf->SetFillColor(242,243,241);
						$this->pdf->MultiCell($defw_full, $endY-$startY,'',0,'C',$row_color,1,0,0,true,0);
						$this->pdf->SetFillColor(0);					
						$this->pdf->setXY($XX, $YY);				
						$row_count++;
						
						// Loopamo cez vrstice in izrisujemo celice
						$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
						while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){					
							// poiščemo kaj je odgovoril uporabnik:
							if ($this->getGrupa())
							{
								if($spremenljivke['tip'] == 6)
									$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
								elseif($spremenljivke['tip'] == 16)
									$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
								else
									$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
								
								$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
							}
							
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
								$this->pdf->SetXY($this->pdf->getX() - $w_oc/2, $startY);							
								//$this->pdf->setX($this->pdf->getX() - $w_oc/2);
								$this->pdf->SetFont(FNT_MAIN_TEXT, '', 8);
								//$this->pdf->SetTextColor(0,128,0);
								$this->pdf->SetTextColor(179,0,128);
								//$this->pdf->TextBoxes($w_oc,LINE_BREAK);	
								$this->pdf->SetFillColor(255,255,255);
								$this->pdf->MultiCell($w_oc-1, $height, $userAnswer['text'],1,'C',1,1,0,0,true,0);
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

			break;
			case 24: // Mesan multigrid
				$this->displayGridMultiple($spremenljivke);
			break;
			case 4: //text
				if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(180*$expand_width, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 1, 0 ,0, false);
					$this->pdf->SetTextColor(0,0,0);
				}
				else
				{
					$this->pdf->TextField('vpr_'. $spremenljivke['id'], 180*$expand_width, 18, array('multiline'=>true,'strokeColor'=>'dkGray'));
					$this->pdf->TextBox(180*$expand_width,18);
					$this->pdf->Ln(LINE_BREAK * 3);
				}
			break;
			case 21: //besedilo*				
			
				// Posebej ipisemo ce imamo samo en kos besedila brez pripisanega texta
				if($spremenljivke['text_kosov'] == 1 && $spremenljivke['text_orientation'] == 0){
					
					$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					
					$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					
					// imamo upload vprašanje
					if ($spremenljivke['upload'] == 1){
						# imena datotek
						if($userAnswer['text'] != '')
							$answer = ''.$site_url.'main/survey/download.php?anketa='.$this->anketa['id'].'&code='.$userAnswer['text'].'';
						else
							$answer = '';
					}
					// imamo signature vprašanje
					elseif($spremenljivke['signature'] == 1){
						$answer = $userAnswer['text'];
						
						// relativna pot
						$image = $site_url.'main/survey/uploads/'.$this->getUserId().'_'.$spremenljivke['id'].'_'.$this->anketa['id'].'.png';										
						$this->pdf->Image($image, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
					}
					else{
						$answer = $userAnswer['text'];
					}
					
					$this->pdf->SetTextColor(179,0,128);
					//$this->pdf->Write(0, $userAnswer['text'], '', 0, 'l', 1, 1);
					$this->pdf->MultiCell(180*$expand_width, 1,$this->encodeText($answer),0,'L',0,1,0,0,true,0);
					$this->pdf->SetTextColor(0,0,0);
				}
			
				else{
					$count = $spremenljivke['text_kosov'];
					$width = round(170*$expand_width/$count);
					$this->currentHeight = 4;
					
					if($spremenljivke['text_orientation'] == 1)
						$width -= 20*$expand_width;
						
					$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					for($i=0; $i<$count; $i++){
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						if($spremenljivke['text_orientation'] == 1){
							$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
							$this->pdf->MultiCell(20*$expand_width, 1,$stringHeader_title,0,'R',0,0,0,0,true,0);
							
							$linecount_vprasanja = $this->pdf->getNumLines($stringHeader_title, 20*$expand_width);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}

						if ( $this->getUserId() )
						{
							$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
							
							// imamo upload vprašanje
							if ($spremenljivke['upload'] == 1){
								# imena datotek
								if($userAnswer['text'] != '')
									$answer = ''.$site_url.'main/survey/download.php?anketa='.$this->anketa['id'].'&code='.$userAnswer['text'].'';
								else
									$answer = '';
							}
							else{
								$answer = $userAnswer['text'];
							}
							
							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell($width, 18, $this->encodeText($answer), 1, 'L', 0, 0, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
							
							$linecount_vprasanja = $this->pdf->getNumLines($answer, $width);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}
						else
						{
							$this->pdf->TextBoxes($width,18);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}
						//$this->pdf->setX($this->pdf->getX() + (10/$count)+$width);						
					}
					
					if($spremenljivke['text_orientation'] == 2){
						mysqli_data_seek($sqlVrednost, 0);
						$this->pdf->setY($this->pdf->getY() + 20);
						
						for($i=0; $i<$count; $i++){
							$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
							$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
							$this->pdf->MultiCell($width, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
							$this->pdf->setX($this->pdf->getX() + 10/$count);
							
							$linecount_vprasanja = $this->pdf->getNumLines($stringHeader_title, $width);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}
					}
					
					$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
				}
				//$this->pdf->Ln(LINE_BREAK * 3);
				
			break;
			case 5: //label
//				$this->pdf->Ln(6);
			break;

			case 7: //number
				error_log("Number @outputSpremenljivke");
				# z enoto na levi
				if($spremenljivke['enota'] == 1){
					# enota					
					$sqlVrednost = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					
					$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
					$this->pdf->MultiCell(30*$expand_width, 5,$stringHeader_title,0,'R',0,0,0,0,true,0);
					# odgovor
					if ( $this->getUserId() ) {
						$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."';";
						$sqlUserAnswer = sisplet_query($userAnswerString);
						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 0, 0 ,0, true);
						$this->pdf->SetTextColor(0,0,0);
					} else {
						$this->pdf->MultiCell(30*$expand_width, 5, '', 1, 'L', 0, 0, 0 ,0, true);
					}
					
					//dodatno polje
					if($spremenljivke['size'] == 2){

						#enota
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
						$this->pdf->MultiCell(30*$expand_width, 5,$stringHeader_title,0,'R',0,0,0,0,true,0);
						
						#odgovor
						if ( $this->getUserId() )
						{
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."';";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text2']), 1, 'L', 0, 1, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						}
						else {
							$this->pdf->MultiCell(30*$expand_width, 5, '', 1, 'L', 0, 1, 0 ,0, true);
						}
					}
					else
						$this->pdf->Ln(LINE_BREAK);	
				# z enoto na desni
				} else if($spremenljivke['enota'] == 2){
					#odgovor					
					if ( $this->getUserId() ) {
						$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
						$sqlUserAnswer = sisplet_query($userAnswerString);
						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text']), 1, 'R', 0, 0, 0 ,0, true);
						$this->pdf->SetTextColor(0,0,0);
					} else {
						$this->pdf->MultiCell(30*$expand_width, 5, '', 1, 'R', 0, 0, 0 ,0, true);
					}
					# enota
					$sqlVrednost = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
					$this->pdf->MultiCell(30*$expand_width, 5,$stringHeader_title,0,'L',0,0,0,0,true,0);
					
					//dodatno polje
					if($spremenljivke['size'] == 2){
					
						#enota
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
						
						
						#odgovor
						if ( $this->getUserId() ) {
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text2']), 1, 'R', 0, 0, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						} else {
							$this->pdf->MultiCell(30*$expand_width, 5, '', 1, 'R', 0, 0, 0 ,0, true);
						}
						$this->pdf->MultiCell(30*$expand_width, 5,$stringHeader_title,0,'L',0,1,0,0,true,0);
						/*
						#odgovor
						if ( $this->getUserId() ) {
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."';";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							$this->pdf->SetTextColor(0,128,0);
							$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text2']), 1, 'L', 0, 1, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						} else {
							$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 1, 0 ,0, true);
						}
						#enota						
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						$stringHeader_title = $rowVrednost['naslov'];
						$this->pdf->MultiCell(30, 5,$stringHeader_title,0,'L',0,0,0,0,true,0);
						*/
					} else {
						$this->pdf->Ln(LINE_BREAK);
					}	
				}
				//brez enote
				else{
					$this->pdf->setX(20*$expand_width);
					if ( $this->getUserId() )
					{
						$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
						$sqlUserAnswer = sisplet_query($userAnswerString);
						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 0, 0 ,0, true);
						$this->pdf->SetTextColor(0,0,0);
					}
					else
					{
						$this->pdf->MultiCell(30*$expand_width, 5, '', 1, 'L', 0, 0, 0 ,0, true);
					}
					
					//dodatno polje
					if($spremenljivke['size'] == 2){

						if ( $this->getUserId() )
						{
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->setX($this->pdf->getX() + 5);
							$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text2']), 1, 'L', 0, 1, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						}
						else
						{
							$this->pdf->setX($this->pdf->getX() + 5);
							$this->pdf->MultiCell(30*$expand_width, 5, '', 1, 'L', 0, 1, 0 ,0, true);
						}
					}
					else
						$this->pdf->Ln(LINE_BREAK);	
				}
				
				/*if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."';";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

					$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 1, 0 ,0, false);
				}
				else
				{
					$this->pdf->TextField('vpr_'. $spremenljivke['id'], 30, 5, array('multiline'=>true,'strokeColor'=>'dkGray'));
					$this->pdf->TextBox(50,LINE_BREAK);
					$this->pdf->Ln(LINE_BREAK);
				}*/
			break;
			case 8: //datum
				// če imamo odgovor od uporabnika, potem ga izpišemo, čene damo prazen box
				if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(30*$expand_width, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 1, 0 ,0, false);
					$this->pdf->SetTextColor(0,0,0);
				}
				else
				{
					$this->pdf->TextField('vpr_'. $spremenljivke['id'], 30*$expand_width, 5, array('multiline'=>true,'strokeColor'=>'dkGray'));
					$this->pdf->TextBox(50*$expand_width,LINE_BREAK);
					$this->pdf->Ln(LINE_BREAK);
				}
			break;
			case 17: //ranking
				
				// iz baze preberemo vse moznosti
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{	
						$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}

					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					$y=$this->pdf->GetY();
					$x=$this->pdf->GetX();

					$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					//stetje stevila vrstic
					$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 90*$expand_width);
					$y=$y+1;
					
					$this->pdf->MultiCell(90*$expand_width, 1, $stringTitle,1,'L',0,0,$x,$y);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(8, 6, $rowAnswers['vrstni_red'],1,'L',0,0,$x+100*$expand_width,$y);
					$this->pdf->SetTextColor(0,0,0);
					
					$stevec=1;
					while($stevec<$stetje_vrstic)
					{
						$this->pdf->Ln(LINE_BREAK);
						$stevec++;
					}
					$this->pdf->Ln(LINE_BREAK);
				}

			break;
			case 18: //vsota
				
				$sum = 0;
				
				// iz baze preberemo vse moznosti
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{	
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{			
						$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}
				
					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					$y=$this->pdf->GetY();
					$x=$this->pdf->GetX();

					$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					//stetje stevila vrstic
					$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 90*$expand_width);
					$y=$y+1;
					
					$this->pdf->MultiCell(60*$expand_width, 1, $stringTitle,0,'R',0,0,$x,$y);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(20*$expand_width, 6, $rowAnswers['text'],1,'L',0,0,$x+65*$expand_width,$y);
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
				$this->pdf->Line(15, $cy , 110*$expand_width, $cy , $this->currentStyle);
				
				//izris vsote
				$y=$y+10;
				$this->pdf->MultiCell(60*$expand_width, 1, $spremenljivke['vsota'],0,'R',0,0,$x,$y);
				$this->pdf->MultiCell(20*$expand_width, 6, $sum,1,'L',0,0,$x+65*$expand_width,$y);
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
					$this->pdf->MultiCell(50*$expand_width, 6, $limit,0,'L',0,0,$x+86,$y);
				}
				
				$this->pdf->Ln(LINE_BREAK);
				
			break;
			case 9: //SN-imena
				if ( $this->getUserId() ){
					$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");	
					while($userAnswer = mysqli_fetch_array($sqlUserAnswer)){
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(80, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 1, 0 ,0, false);
						$this->pdf->MultiCell(80, 1, '', 0, 'L', 0, 1, 0 ,0, false);
						$this->pdf->SetTextColor(0,0,0);
					}
				}
				else{
					$this->pdf->Ln(LINE_BREAK);
				}
			break;

			case 10: // SN - social support dve koloni po 5 celic
				$this->pdf->TabledTextBox(2,5);
			break;
			case 11: // SN - podvprasanje (podobno kot mgrid - z radio gumbki, 5 ponovitev)
			case 14: // AW - podvprasanje
				$sqlVrednosti = sisplet_query("SELECT naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				$rowHeaders = array();
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
                {
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
			case 27:
				//error_log("Heatmap @outputSpremenljivke");
				#spremenljivke##############################
				global $site_url;
				global $site_path;
				global $lang;
				$usr_id = $this->getUserId();
				$spr_id = $spremenljivke['id'];
				
				$landscapeBgImgWidthMm = 140;	//predefinirana sirina slike v mm za pdf dokument, ce je ta lezeca
				$portraitBgImgWidthMm = 100; //predefinirana sirina slike v mm za pdf dokument, ce je ta pokoncna
				#spremenljivke##############################konec
				
				#pridobitev informacij o sliki in klikih iz baze ###################################################
				$data4BgImage = sisplet_query("SELECT params from srv_spremenljivka WHERE id = $spr_id");
				$rowBgImageHtml = mysqli_fetch_assoc($data4BgImage);
				$spremenljivkaParams = new enkaParameters($rowBgImageHtml['params']);				
				$backgroundImgHtml = $spremenljivkaParams->get('hotspot_image');
				
				
				$heatmap_show_counter_clicks = ($spremenljivkaParams->get('heatmap_show_counter_clicks') ? $spremenljivkaParams->get('heatmap_show_counter_clicks') : 0); //za prikazovanje/skrivanje stevca klikov
				$heatmap_show_clicks = ($spremenljivkaParams->get('heatmap_show_clicks') ? $spremenljivkaParams->get('heatmap_show_clicks') : 0); //za prikazovanje/skrivanje klikov
				$heatmap_num_clicks = ($spremenljivkaParams->get('heatmap_num_clicks') ? $spremenljivkaParams->get('heatmap_num_clicks') : 1); //stevilo moznih klikov
				$heatmap_click_color = ($spremenljivkaParams->get('heatmap_click_color') ? $spremenljivkaParams->get('heatmap_click_color') : "");
				$heatmap_click_size = ($spremenljivkaParams->get('heatmap_click_size') ? $spremenljivkaParams->get('heatmap_click_size') : 5);
				$heatmap_click_shape = ($spremenljivkaParams->get('heatmap_click_shape') ? $spremenljivkaParams->get('heatmap_click_shape') : 1);
				#pridobitev informacij o sliki in klikih iz baze ###################################################konec
				
				#pridobitev slike za ozadje##############################################################				
				$position = strpos($backgroundImgHtml, 'src="');
				$backgroundImg = substr($backgroundImgHtml, $position+5);
				$position = strpos($backgroundImg, '"');
				$backgroundImg = str_replace(substr($backgroundImg, $position),"",$backgroundImg);				
				
				$bgImageType = substr($backgroundImg, -3);	//koncnica slike, tip slike
				//$bgImageType = 'png';
				//error_log($bgImageType);
				#pridobitev slike za ozadje##############################################################konec
				
				#pridobitev dimenzij slike za ozadje#####################################################
				
				//realna velikost slike pobrana iz datoteke - ni v redu, ker je potrebno dobiti dimenzije, ki jih je nastavil uporabnik
 				$bgImgSize = getimagesize($backgroundImg);
				//error_log("Width: $bgImgSize[0]");
				//error_log("Height: $bgImgSize[1]");
				$bgImgWidthPx = $bgImgSize[0];
				$bgImgHeightPx = $bgImgSize[1];
				//realna velikost slike pobrana iz datoteke - konec
				
				//visina slike, ki jo je nastavil uporabnik
				$positionImgHeight = strpos($backgroundImgHtml, 'height:');
				$ImgHeight = substr($backgroundImgHtml, $positionImgHeight+7);				
				$positionImgheightPx = strpos($ImgHeight, 'px');				
				$ImgHeight = str_replace(substr($ImgHeight, $positionImgheightPx),"",$ImgHeight);
				//error_log($ImgHeight);
				//visina slike, ki jo je nastavil uporabnik - konec
				
				//sirina slike, ki jo je nastavil uporabnik
				$positionImgWidth = strpos($backgroundImgHtml, 'width:');
				$ImgWidth = substr($backgroundImgHtml, $positionImgWidth+6);				
				$positionImgWidthPx = strpos($ImgWidth, 'px');				
				$ImgWidth = str_replace(substr($ImgWidth, $positionImgWidthPx),"",$ImgWidth);
				//error_log($ImgWidth);
				//sirina slike, ki jo je nastavil uporabnik - konec
				
				#pridobitev dimenzij slike za ozadje#####################################################konec
				
				#ureditev ustrezne velikosti slike za ozadje##############################################################
				$bgImgWidthInMm = ($ImgWidth > $ImgHeight ? $landscapeBgImgWidthMm : $portraitBgImgWidthMm);
				$bgImgHeightInMm = ($bgImgWidthInMm / $ImgWidth) * $ImgHeight;
				//error_log("bgImgHeightInMm: $bgImgHeightInMm");
				#ureditev ustrezne velikosti slike za ozadje##############################################################konec
				
				#pridobitev koordinat klikanih tock za uporabnika $usr_id#################################################
				$data4Coords = sisplet_query("SELECT lat, lng from srv_data_map WHERE usr_id = $usr_id AND spr_id = $spr_id");
				$i = 0;
				//error_log($spr_id);
				#pridobitev koordinat klikanih tock za uporabnika $usr_id#################################################konec
				
				#izris tock na sliko######################################################################################
				//if($heatmap_show_clicks == 1){					
					define('UPLOAD_DIR', $site_path.'admin/exportclases/temp/');
					$imageFinal = $this->pdf->prepareHeatmapImage($data4Coords, $backgroundImg, $lat, $lng, $ImgWidth, $ImgHeight, $heatmap_click_size, $heatmap_click_color, $heatmap_click_shape, $spr_id, $bgImageType, UPLOAD_DIR);
				//}			
				#izris tock na sliko######################################################################################konec			
				
				//izris slike v pdf
				//$this->pdf->Image($imageFinal, $x='', $y='', $w=$bgImgWidthInMm, $h, $type=$bgImageType);
				$this->pdf->Image($imageFinal, $x='', $y='', $w=$bgImgWidthInMm, $bgImgHeightInMm, $type='png');
				
				//izbris slike iz mape streznika
				//if($heatmap_show_clicks == 1){
					unlink($imageFinal);
				//}
				
				#izris stevca klikov######################################################################################
				if($heatmap_show_counter_clicks){
					//$this->pdf->Write(10, $lang['srv_vprasanje_heatmap_num_clicks'].': '.mysqli_num_rows($data4Coords).'/'.$heatmap_num_clicks);
					$y=$this->pdf->GetY();
					$this->pdf->setY($bgImgHeightInMm+$y);
					$this->pdf->Write(10, $lang['srv_vprasanje_heatmap_num_clicks'].': ');
					$this->pdf->Ln(LINE_BREAK);
					$this->pdf->Write(10, mysqli_num_rows($data4Coords).'/'.$heatmap_num_clicks);
				}
				#izris stevca klikov######################################################################################konec	
				$this->pdf->Ln(LINE_BREAK);
				
			break;
		}

	}
	

		
	function outputVprasanjeValues($spremenljivke)
	{	
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		// Izpisemo tekst vprasanja
		$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
		
		$pozicija_vprasanja = $this->pdf->getY();
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
        $rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 8);
		
		$naslov = $this->dataPiping($spremenljivke['naslov']);
		
		$linecount_vprasanja = $this->getCellHeight($naslov, 90);
		$this->currentHeight = $linecount_vprasanja;
		
		if($pozicija_vprasanja + $linecount_vprasanja > 240/$expand_width)
		{	
			$this->pdf->AddPage();								
		}	
		
		//izpis if-ov pri vprasanju
		if($this->showIf == 1){
			
			$sqlIf = sisplet_query("SELECT * FROM srv_branching WHERE element_spr='$spremenljivke[id]'");
			$rowIf = mysqli_fetch_array($sqlIf);
			
			if ($rowIf['parent'] > 0){			
				$rowb = Cache::srv_if($rowIf['parent']);
				
				if ($rowb['tip'] == 0)
					$this->displayIf($rowIf['parent']);
			}
		}		
		
		// stevilcenje vprasanj
		$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';
		
		$this->pdf->setFont('','B',$this->font);
		$this->pdf->MultiCell(80*$expand_width , 1, $numberingText . $this->encodeText($naslov),0,'L',0,0,0,0);
		$this->pdf->setFont('','',$this->font);
	}

	
	function outputSpremenljivkeValues($spremenljivke){
		global $site_url;
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		// Ce je spremenljivka v loopu
		$loop_id = $this->loop_id == null ? " IS NULL" : " = '".$this->loop_id."'";
		
		switch ( $spremenljivke['tip'] )
		{
			case 1: //radio
			case 2: //check
			case 3: //select -> radio
								
				// če imamo vnose, pogledamo kaj je odgovoril uporabnik
				$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spremenljivke[id]' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id";
				$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
				while ($rowAnswers = mysqli_fetch_assoc($sqlUserAnswer))
				$userAnswer[$rowAnswers['vre_id']] = $rowAnswers['vre_id'];
		
				// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta				
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				
				$resultString = '';
				
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					//izpisemo samo izbrane vrednosti
					if( isset($userAnswer[$rowVrednost['id']]) ){
														
						$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
						//stetje stevila vrstic
						$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 180*$expand_width);
						
						// še dodamo textbox če je polj other
						$_txt = '';
						if ( $rowVrednost['other'] == 1 && $this->getUserId() )
						{
							$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
							$row4 = mysqli_fetch_assoc($sqlOtherText);
							$_txt = ' '.$row4['text'];
						}
											
						$resultString .= ' '.$stringTitle.$_txt.','; 	
					}		
				}
				
				$resultString = substr($resultString, 0, -1);
				//$this->pdf->SetTextColor(0,128,0);
				$this->pdf->SetTextColor(179,0,128);
				$this->pdf->MultiCell(90*$expand_width, 1, $resultString,0,'L',0,0,95*$expand_width,0);
				$this->pdf->SetTextColor(0,0,0);
				
				$linecount_vprasanja = $this->getCellHeight($resultString, 90*$expand_width);
				$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
				
				$this->pdf->setY($this->pdf->getY() + $this->currentHeight);
				//$this->pdf->Ln(LINE_BREAK);
			break;

 			case 6: //multigrid
			case 16:// multicheckbox

				//izris dvojnega multigrida
				if($spremenljivke['enota'] == 3){			
					$this->displayDoubleGridValues($spremenljivke);					
					break;
				}
			
				$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
                $rowVrstic = mysqli_fetch_row($sqlVrstic);
				$visina = round(($rowVrstic[0]+2) * 8);
								
				$defw_fc = 85*$expand_width;
                $w_oc = 1;
                $halfWidth = $w_oc/2;
				
				$this->pdf->setX(15);
				
				$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
                {
					$skipRow = false;
					
					// Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici
					if($this->skipEmptySub == 1){
						
						$skipRow = true;
						if($spremenljivke['tip'] == 6)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
						else
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");

						if(mysqli_num_rows($sqlUserAnswer) > 0)
							$skipRow = false;
					}
								
					if($skipRow == false){
						$this->currentHeight = 0;
					
						$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
						$pozicija_boka = $this->pdf->getY();
						$pozicija_bokaX = $this->pdf->getX();
						
						// še dodamo textbox če je polj other
						$_txt = '';
						if ( $rowVrednost['other'] == 1 && $this->getUserId() )
						{
							$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
							$row4 = mysqli_fetch_assoc($sqlOtherText);
							$_txt = ' '.$row4['text'];
						}										
						$stringCell_title .= $_txt.':'; 	
						
						//$linecount_vprasanja = $this->pdf->getNumLines($stringCell_title, $defw_fc);
						//$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						$this->currentHeight = ($this->getCellHeight($stringCell_title, $defw_fc) > $this->currentHeight ? $this->getCellHeight($stringCell_title, $defw_fc) : $this->currentHeight);	
						
						if($pozicija_boka + ($this->currentHeight/**4.7*/) > 250/$expand_width)
						{	
							$this->pdf->AddPage();
							$pozicija_boka = $this->pdf->getY();
						}
						/*Izpis bočnega stolpca*/
						$this->pdf->MultiCell($defw_fc, 1,$stringCell_title,0,'R',0,1,0,0,true,0);
						$startY = $this->pdf->getY();				
						$endY = $this->pdf->getY();				
						$startX = $this->pdf->getX();				
						
						$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY 'vrstni_red'");
						$startX = $startX + $defw_fc;
						$startY = $pozicija_boka;
						
						//izpis rezultatov
						$resultString = '';
						while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
						{
							// poiščemo kaj je odgovoril uporabnik:
							if($spremenljivke['tip']==16)
								$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
							else
								$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
							
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					
							$this->pdf->SetXY($startX + $w_oc , ($startY+$endY-7) /2);	
					
							if($rowVsehVrednosti['id'] == $userAnswer['grd_id']){		
								$resultString .= ' '.$this->encodeText($rowVsehVrednosti['naslov']).',';
							}
						}
						$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
						$resultString = substr($resultString, 0, -1);
						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(100*$expand_width, 1, $resultString,0,'L',0,0,0,0);
						$this->pdf->SetTextColor(0,0,0);
						$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
						
						/*$linecount_vprasanja = $this->pdf->getNumLines($resultString, 100*$expand_width);
						$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;*/
						$this->currentHeight = ($this->getCellHeight($stringCell_title, $defw_fc) > $this->currentHeight ? $this->getCellHeight($stringCell_title, $defw_fc) : $this->currentHeight);	

						$this->pdf->setY($endY);
						//$this->pdf->setY($this->pdf->getY() + $this->currentHeight/**4.7*/);
					}
			   }

			break;
			
			case 21: //besedilo*				
				
				// Posebej ipisemo ce imamo samo en kos besedila brez pripisanega texta
				if($spremenljivke['text_kosov'] == 1 && $spremenljivke['text_orientation'] == 0 && $this->type == 0){
					
					$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					
					$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					
					// imamo upload vprašanje
					if ($spremenljivke['upload'] == 1){
						# imena datotek
						if($userAnswer['text'] != '')
							$answer = ''.$site_url.'main/survey/download.php?anketa='.$this->anketa['id'].'&code='.$userAnswer['text'].'';
						else
							$answer = '';
					}
					// imamo signature vprašanje
					elseif($spremenljivke['signature'] == 1){
						$answer = $userAnswer['text'];
						
						// relativna pot
						$image = $site_url.'main/survey/uploads/'.$this->getUserId().'_'.$spremenljivke['id'].'_'.$this->anketa['id'].'.png';										
						$this->pdf->Image($image, $x='', $y='', $w=140, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);	
					}
					else{
						$answer = $userAnswer['text'];
					}
					
					$this->pdf->SetTextColor(179,0,128);
					//$this->pdf->Write(0, $userAnswer['text'], '', 0, 'l', 1, 1);
					$this->pdf->MultiCell(180*$expand_width, 1,$this->encodeText($answer),0,'L',0,1,0,0,true,0);
					$this->pdf->SetTextColor(0,0,0);
				}
				
				else{
					$count = $spremenljivke['text_kosov'];
					$width = round(170/$count);
					$this->currentHeight = 0;
					if($spremenljivke['text_orientation'] == 1)
						$width -= 20;
					
					if($this->type == 2){
						$this->pdf->setX(80);
					}
					
					$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					for($i=0; $i<$count; $i++){
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						if($spremenljivke['text_orientation'] == 1){
							$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
							$this->pdf->MultiCell(20, 1,$stringHeader_title.': ',0,'R',0,0,0,0,true,0);
							
							$linecount_vprasanja = $this->pdf->getNumLines($stringHeader_title, 20);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}

						if ( $this->getUserId() )
						{
							$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
							
							// imamo upload vprašanje
							if ($spremenljivke['upload'] == 1){
								# imena datotek
								if($userAnswer['text'] != '')
									$answer = ''.$site_url.'main/survey/download.php?anketa='.$this->anketa['id'].'&code='.$userAnswer['text'].'';
								else
									$answer = '';
							}
							else{
								$answer = $userAnswer['text'];
							}
							
							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell($width, 1, $this->encodeText($answer), 0, 'L', 0, 0, 0 ,0, true, 0);
							$this->pdf->SetTextColor(0,0,0);
							
							$linecount_vprasanja = $this->pdf->getNumLines($answer, $width);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}
						else
						{
							$this->pdf->TextBoxes($width,18);
						}
						//$this->pdf->setX($this->pdf->getX() + (10/$count)+$width);						
					}
					
					if($spremenljivke['text_orientation'] == 2){
						mysqli_data_seek($sqlVrednost, 0);
						$this->pdf->setY($this->pdf->getY() + 20);
						
						for($i=0; $i<$count; $i++){
							$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
							$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
							$this->pdf->MultiCell($width, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
							$this->pdf->setX($this->pdf->getX() + 10/$count);
							
							$linecount_vprasanja = $this->pdf->getNumLines($stringHeader_title, $width);
							$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
						}
					}
					
					$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
				}				
				
			break;
			
			case 7: //number
				error_log("Number @outputSpremenljivkeValues");
				# z enoto na levi
				if($spremenljivke['enota'] == 1){
					# enota					
					$sqlVrednost = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					
					$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
					$this->pdf->MultiCell(20, 5,$stringHeader_title,0,'R',0,0,0,0,true,0);
					# odgovor
					if ( $this->getUserId() ) {
						$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
						$sqlUserAnswer = sisplet_query($userAnswerString);
						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(20, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 0, 0 ,0, true);
						$this->pdf->SetTextColor(0,0,0);
					} else {
						$this->pdf->MultiCell(20, 5, '', 1, 'L', 0, 0, 0 ,0, true);
					}
					
					//dodatno polje
					if($spremenljivke['size'] == 2){

						#enota
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
						$this->pdf->MultiCell(20, 5,$stringHeader_title,0,'R',0,0,0,0,true,0);
						
						#odgovor
						if ( $this->getUserId() )
						{
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell(20, 5, $this->encodeText($userAnswer['text2']), 1, 'L', 0, 1, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						}
						else {
							$this->pdf->MultiCell(20, 5, '', 1, 'L', 0, 1, 0 ,0, true);
						}
					}
					else
						$this->pdf->Ln(LINE_BREAK);	
				# z enoto na desni
				} else if($spremenljivke['enota'] == 2){
					#odgovor					
					if ( $this->getUserId() ) {
						$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
						$sqlUserAnswer = sisplet_query($userAnswerString);
						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(20, 5, $this->encodeText($userAnswer['text']), 1, 'R', 0, 0, 0 ,0, true);
						$this->pdf->SetTextColor(0,0,0);
					} else {
						$this->pdf->MultiCell(20, 5, '', 1, 'R', 0, 0, 0 ,0, true);
					}
					# enota
					$sqlVrednost = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowVrednost = mysqli_fetch_array($sqlVrednost);
					$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
					$this->pdf->MultiCell(20, 5,$stringHeader_title,0,'L',0,0,0,0,true,0);
					
					//dodatno polje
					if($spremenljivke['size'] == 2){
					
						#enota
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						
						$stringHeader_title = $this->encodeText($rowVrednost['naslov']);
						
						
						#odgovor
						if ( $this->getUserId() ) {
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell(20, 5, $this->encodeText($userAnswer['text2']), 1, 'R', 0, 0, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						} else {
							$this->pdf->MultiCell(20, 5, '', 1, 'R', 0, 0, 0 ,0, true);
						}
						$this->pdf->MultiCell(20, 5,$stringHeader_title,0,'L',0,1,0,0,true,0);
						/*
						#odgovor
						if ( $this->getUserId() ) {
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."';";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							$this->pdf->SetTextColor(0,128,0);
							$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text2']), 1, 'L', 0, 1, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						} else {
							$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 1, 0 ,0, true);
						}
						#enota						
						$rowVrednost = mysqli_fetch_array($sqlVrednost);
						$stringHeader_title = $rowVrednost['naslov'];
						$this->pdf->MultiCell(30, 5,$stringHeader_title,0,'L',0,0,0,0,true,0);
						*/
					} else {
						$this->pdf->Ln(LINE_BREAK);
					}	
				}
				//brez enote
				else{
					$this->pdf->setX(20);
					if ( $this->getUserId() )
					{
						$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
						$sqlUserAnswer = sisplet_query($userAnswerString);
						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

						//$this->pdf->SetTextColor(0,128,0);
						$this->pdf->SetTextColor(179,0,128);
						$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 0, 0 ,0, true);
						$this->pdf->SetTextColor(0,0,0);
					}
					else
					{
						$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 0, 0 ,0, true);
					}
					
					//dodatno polje
					if($spremenljivke['size'] == 2){

						if ( $this->getUserId() )
						{
							$userAnswerString = "SELECT text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
							$sqlUserAnswer = sisplet_query($userAnswerString);
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

							//$this->pdf->SetTextColor(0,128,0);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->setX($this->pdf->getX() + 5);
							$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text2']), 1, 'L', 0, 1, 0 ,0, true);
							$this->pdf->SetTextColor(0,0,0);
						}
						else
						{
							$this->pdf->setX($this->pdf->getX() + 5);
							$this->pdf->MultiCell(30, 5, '', 1, 'L', 0, 1, 0 ,0, true);
						}
					}
					else
						$this->pdf->Ln(LINE_BREAK);	
				}
				
				/*if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."';";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

					$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 1, 0 ,0, false);
				}
				else
				{
					$this->pdf->TextField('vpr_'. $spremenljivke['id'], 30, 5, array('multiline'=>true,'strokeColor'=>'dkGray'));
					$this->pdf->TextBox(50,LINE_BREAK);
					$this->pdf->Ln(LINE_BREAK);
				}*/
			break;
			case 8: //datum
				// če imamo odgovor od uporabnika, potem ga izpišemo, čene damo prazen box
				if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);

					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(30, 5, $this->encodeText($userAnswer['text']), 1, 'L', 0, 1, 0 ,0, false);
					$this->pdf->SetTextColor(0,0,0);
				}
				else
				{
					$this->pdf->TextField('vpr_'. $spremenljivke['id'], 30, 5, array('multiline'=>true,'strokeColor'=>'dkGray'));
					$this->pdf->TextBox(50,LINE_BREAK);
					$this->pdf->Ln(LINE_BREAK);
				}
			break;
			case 17: //ranking

				// iz baze preberemo vse moznosti
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{	
						$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}

					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					$y=$this->pdf->GetY();
					$x=65;

					$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					//stetje stevila vrstic
					$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 90);
					$y=$y+1;
					
					$this->pdf->MultiCell(30, 1, $stringTitle,0,'R',0,0,$x,$y);
					//$this->pdf->SetTextColor(0,128,0);
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(8, 6, $rowAnswers['vrstni_red'],1,'L',0,0,$x+35,$y);
					$this->pdf->SetTextColor(0,0,0);
					
					$stevec=1;
					while($stevec<$stetje_vrstic)
					{
						$this->pdf->Ln(LINE_BREAK);
						$stevec++;
					}
					$this->pdf->Ln(LINE_BREAK);
				}

			break;
			case 18: //vsota
				
				$sum = 0;
				$this->pdf->setX(15);
				
				// iz baze preberemo vse moznosti
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{	
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{			
						$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}
				
					//dodamo eno celico da zamaknemo malo
					$this->pdf->Cell(2, 5, '');
					$y=$this->pdf->GetY();
					$x=$this->pdf->GetX();

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
			case 9: //SN-imena
				if ( $this->getUserId() ){
					
					$answer = '';
					$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");	
					while($userAnswer = mysqli_fetch_array($sqlUserAnswer)){					
						$answer .= $this->encodeText($userAnswer['text']).', ';
					
					}
					$answer = substr($answer, 0, -2);
					
					$this->pdf->SetTextColor(179,0,128);
					$this->pdf->MultiCell(90*$expand_width, 1, $answer,0,'L',0,0,95*$expand_width,0);
					$this->pdf->SetTextColor(0,0,0);
					
					$linecount_vprasanja = $this->pdf->getNumLines($answer, 90*$expand_width);
					$this->currentHeight = ($linecount_vprasanja > $this->currentHeight) ? $linecount_vprasanja : $this->currentHeight;
					
					$this->pdf->setY($this->pdf->getY() + $this->currentHeight*4.7);
				}
				else{
					$this->pdf->Ln(LINE_BREAK);
				}
			break;
			case 27:
				error_log("Heatmap @outputSpremenljivkeValues");
			break;
		}
	}

	//izris kratkega nacina doubleGrid
	function displayDoubleGridValues($spremenljivke) {
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
		$rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 8);
						
		$defw_fc = 85;						
		$w_oc = 1;
		$halfWidth = $w_oc/2;
		
		//naslov prvega dela grida
		$this->pdf->MultiCell(180, 1, $this->encodeText($spremenljivke['grid_subtitle1']),0,'C',0,1,0,0);
		
		$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
			$pozicija_boka = $this->pdf->getY();
			$pozicija_bokaX = $this->pdf->getX();
			//$linecount_boka = $this->pdf->getNumLines($stringCell_title, $defw_fc);
			
			// še dodamo textbox če je polj other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 && $this->getUserId() )
			{
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
			}										
			$stringCell_title .= $_txt.':'; 	
			
			if($pozicija_boka + $visina > 250/$expand_width)
			{	
				$this->pdf->AddPage();
				$pozicija_boka = $this->pdf->getY();
			}
						
			/*Izpis bočnega stolpca*/
			$this->pdf->MultiCell($defw_fc, 0,$stringCell_title,0,'R',0,1,0,0,true,0);
			$startY = $this->pdf->getY();				
			$endY = $this->pdf->getY();				
			$startX = $this->pdf->getX();					
//					print_r($pozicija_boka. " : ". $startY."<br>");
			$linecount_boka--;
			$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' AND part='1' ORDER BY 'vrstni_red'");
			$startX = $startX + $defw_fc;
			$startY = $pozicija_boka;
			
			//izpis rezultatov
			$resultString = '';
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
			{
				// poiščemo kaj je odgovoril uporabnik:
				$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."'");
				
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		
				if($rowVsehVrednosti['id'] == $userAnswer['grd_id']){
					
					$this->pdf->SetXY($startX + $w_oc , ($startY+$endY-7) /2);						
					
					$resultString .= ' '.$this->encodeText($rowVsehVrednosti['naslov']).',';
				}
			}
			
			$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
			$resultString = substr($resultString, 0, -1);
			//$this->pdf->SetTextColor(0,128,0);
			$this->pdf->SetTextColor(179,0,128);
			$this->pdf->MultiCell(180, 1, $resultString,0,'L',0,0,0,0);
			$this->pdf->SetTextColor(0,0,0);
			$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
			
			$this->pdf->setY($endY);
		}
		
		//se drugi del grida
		
		//naslov drugega dela grida
		$this->pdf->MultiCell(180, 1, $this->encodeText($spremenljivke['grid_subtitle2']),0,'C',0,1,0,0);
		
		$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
			$pozicija_boka = $this->pdf->getY();
			$pozicija_bokaX = $this->pdf->getX();
			//$linecount_boka = $this->pdf->getNumLines($stringCell_title, $defw_fc);
			
			// še dodamo textbox če je polj other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 && $this->getUserId() )
			{
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
			}										
			$stringCell_title .= $_txt.':'; 	
			
			if($pozicija_boka + $visina > 250/$expand_width)
			{	
				$this->pdf->AddPage();
				$pozicija_boka = $this->pdf->getY();
			}
			/*Izpis bočnega stolpca*/
			$this->pdf->MultiCell($defw_fc, 0,$stringCell_title,0,'R',0,1,0,0,true,0);
			$startY = $this->pdf->getY();				
			$endY = $this->pdf->getY();				
			$startX = $this->pdf->getX();					
//					print_r($pozicija_boka. " : ". $startY."<br>");
			$linecount_boka--;
			$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' AND part='2' ORDER BY 'vrstni_red'");
			$startX = $startX + $defw_fc;
			$startY = $pozicija_boka;
			
			//izpis rezultatov
			$resultString = '';
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
			{
				// poiščemo kaj je odgovoril uporabnik:
				$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."'");
				
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		
				if($rowVsehVrednosti['id'] == $userAnswer['grd_id']){
					
					$this->pdf->SetXY($startX + $w_oc , ($startY+$endY-7) /2);						
					
					$resultString .= ' '.$this->encodeText($rowVsehVrednosti['naslov']).',';
				}
			}

			$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
			$resultString = substr($resultString, 0, -1);
			//$this->pdf->SetTextColor(0,128,0);
			$this->pdf->SetTextColor(179,0,128);
			$this->pdf->MultiCell(180, 1, $resultString,0,'L',0,0,0,0);
			$this->pdf->SetTextColor(0,0,0);
			$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
			
			$this->pdf->setY($endY);
		}
	}
	
	//izris dolgega nacina doubleGrid
	function displayDoubleGrid($spremenljivke) {
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
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
		$this->pdf->MultiCell(80, LINE_BREAK, $this->encodeText($spremenljivke['grid_subtitle1']),'B','C',0,0);
		$this->pdf->MultiCell(80, LINE_BREAK, $this->encodeText($spremenljivke['grid_subtitle2']),'B','C',0,1);
		
		//izpis vmesne crte
		$this->pdf->Line($defw_fc+95, $this->pdf->getY()-6, $defw_fc+95, $this->pdf->getY()+$visina);
		
		/*Izpis presledka na začetku*/
		$this->pdf->Cell($defw_fc, 0,'');
		// izišemo header celice
		$sqlVsehVrednsti = sisplet_query("SELECT naslov, variable,vrstni_red FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY part, vrstni_red");
		$linecount = 0;
		$maxlinecount = 0;
		$linecount1 = 0;
		$maxlinecount1 = 0;

		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			// če je naslov null izpišemo variable
			$stringHeader_title = $this->encodeText( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
			/*Zascita pred prepisovanjem na novo stran za zgornje parametre*/
			$pozicija_vrha = $this->pdf->getY();
			//$linecount_vrha = $this->pdf->getNumLines($stringHeader_title, $w_oc);
			if($pozicija_vrha + $visina > 250/$expand_width)
			{	
				$this->pdf->AddPage();
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
			$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
			$pozicija_boka = $this->pdf->getY();
			$pozicija_bokaX = $this->pdf->getX();
			//$linecount_boka = $this->pdf->getNumLines($stringCell_title, $defw_fc);
			
			// še dodamo textbox če je polj other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 && $this->getUserId() )
			{
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
			}										
			$stringCell_title .= $_txt; 
			
			if($pozicija_boka + $visina > 250/$expand_width)
			{	
				$this->pdf->AddPage();
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
				// poiščemo kaj je odgovoril uporabnik:
				if ($this->getGrupa())
				{
					$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");
				
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				}

				
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
	
	// Izris mesanega multigrida
	function displayGridMultiple($spremenljivke){
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
		$rowVrstic = mysqli_fetch_row($sqlVrstic);
		$visina = round(($rowVrstic[0]+2) * 8);
	
		$defw_full = 210*$expand_width;
		$defw_fc = 24*$expand_width; // sirina prve celice
		$defw_max = 35*$expand_width; // max sirina ostalih celic
		
		$kolon = 1;
		
		// Preverjamo ce imamo filter na podskupini grida - SAMO CE NE PRIHAJAMO IZ MAILA!
		$tmp_svp_pv = array();
		if($this->usr_type == null){
			$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
			$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
			
			$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);	
			foreach ( $tmp_svp_pv as $vid => $variable) {
				$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
			}
		}
		
		// Loop po podskupinah gridov
		$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id");
		while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
			
			if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
				// Ce gre za podskupino multigrid z dropdowni - potem izrisemo samo en stolpec
				if($rowSubGrid['tip'] == 6 && $rowSubGrid['enota'] == 2){
					$kolon ++;
				}
				else{
					$sqlStVrednosti = sisplet_query("SELECT count(*) FROM srv_grid WHERE spr_id='".$rowSubGrid['spr_id']."' ");
					$rowStVrednost = mysqli_fetch_array($sqlStVrednosti);
					
					$kolon += $rowStVrednost['count(*)'];
				}
			}
		}
		
		switch($kolon)
		{
			case 2:
				$defw_fc = 110*$expand_width;						 
			break;
			case 3:
				$defw_fc = 85*$expand_width;												 
			break;	
			case 4:
				$defw_fc = 60*$expand_width;						
			break;
			case 5:
				$defw_fc = 40*$expand_width;		
			break;
			case 6:
				$defw_fc = 35*$expand_width;						
			break;
			default:
			 $defw_fc = 24*$expand_width;					
		}			
		$w_oc = ( $defw_full - $defw_fc ) / $kolon;
		if ( $w_oc > $defw_max )
			$w_oc = $defw_max;

		$countVrednosti=0;
		$halfWidth = ($w_oc)/ 2;


		// Prelom strani ce je kateri od naslovov gridov predolg
		$sqlVsehVrednsti = sisplet_query("SELECT g.naslov, g.variable, s.tip, s.enota FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND g.spr_id=m.spr_id AND m.spr_id=s.id ");
		$linecount = 0;
		$break = false;
		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			// Pri multigrid dropdownih ne izpisujemo naslova
			if($rowVsehVrednosti['tip'] != 6 || $rowVsehVrednosti['enota'] != 2){
				
				// če je naslov null izpišemo variable
				$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
				/*Zascita pred prepisovanjem na novo stran za zgornje parametre*/
				$pozicija_vrha = $this->pdf->getY();					
				
				$linecount = $this->pdf->getNumLines($stringHeader_title, $w_oc);
				$this->currentHeight = ($linecount > $this->currentHeight) ? $linecount : $this->currentHeight;
				
				if(!$break && $pozicija_vrha + $linecount*4.7 > 250/$expand_width)
				{	
					$this->pdf->AddPage();
					$break = true;
				}
			}
		}
		
		/*Izpis presledka na začetku*/
		$this->pdf->Cell($defw_fc, 0,'');
		
		// izišemo header celice
		$sqlSubGrid = sisplet_query("SELECT m.spr_id, m.vrstni_red, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red");
		while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
		
			if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
				$sqlVsehVrednsti = sisplet_query("SELECT naslov, variable FROM srv_grid WHERE spr_id='".$rowSubGrid['spr_id']."' ");
				while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
				{
					// Pri multigrid dropdownih ne izpisujemo naslova
					if($rowSubGrid['tip'] != 6 || $rowSubGrid['enota'] != 2){
						// če je naslov null izpišemo variable
						$stringHeader_title =  $this->encodeText( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
						
						/*Izpis zgornje vrstice*/
						$this->pdf->MultiCell($w_oc, 1,$stringHeader_title,0,'C',0,0,0,0,true,0);
					}
					else{
						$this->pdf->MultiCell($w_oc, 1, '',0,'C',0,0,0,0,true,0);
						break;
					}
				}
			}
		}

		$this->pdf->setY($this->pdf->getY() + $this->currentHeight*6);
		
		$row_count = 1;
		$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$skipRow = false;
			
			// Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici
			if($this->skipEmptySub == 1){
				
				$skipRow = true;
				
				// Loop po podskupinah gridov
				$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red");
				while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
					
					if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
						$sqlSubVar = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$rowSubGrid['spr_id']."' AND vrstni_red='".$rowVrednost['vrstni_red']."' ");
						$rowSubVar = mysqli_fetch_array($sqlSubVar);
						
						if($rowSubGrid['tip'] == 6)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."'");
						elseif($rowSubGrid['tip'] == 16)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."'");
						else
							$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."'");

						if(mysqli_num_rows($sqlUserAnswer) > 0){
							$skipRow = false;
							break;			
						}
					}
				}
			}
						
			if($skipRow == false){
			
				// barva vrstice
				$row_color = $row_count%2;
			
				$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
				/*Zascita pred prepisovanjem na novo stran za bocne parametre*/
				$pozicija_boka = $this->pdf->getY();
				$pozicija_bokaX = $this->pdf->getX();
				//$linecount_boka = $this->pdf->getNumLines($stringCell_title, $defw_fc);
				
				// še dodamo textbox če je polj other
				$_txt = '';
				if ( $rowVrednost['other'] == 1 && $this->getUserId() )
				{
					$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
					$row4 = mysqli_fetch_assoc($sqlOtherText);
					$_txt = ' '.$row4['text'];
				}										
				$stringCell_title .= $_txt.':'; 

				// Dobimo visino prve (bocne) celice
				$height = $this->pdf->getNumLines($stringCell_title, $defw_fc);
				
				// Loop po podskupinah gridov - pri multitext, multinumber in mg dropdown loopamo cez vrstico da dobimo visino najvisje celice
				$sqlSubGrid = sisplet_query("SELECT m.spr_id, m.vrstni_red, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red");
				while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){

					if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
						if($rowSubGrid['tip'] == 19 || $rowSubGrid['tip'] == 20){
			
							$sqlSubVar = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$rowSubGrid['spr_id']."' AND vrstni_red='".$rowVrednost['vrstni_red']."' ");
							$rowSubVar = mysqli_fetch_array($sqlSubVar);
													
							$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$rowSubGrid['spr_id']."' ORDER BY vrstni_red");
							while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
								// poiščemo kaj je odgovoril uporabnik:
								if ($this->getGrupa())
								{
									$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");					
									$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								}
								$linecount = $this->pdf->getNumLines($userAnswer['text'], $w_oc);
								$height = ($linecount > $height) ? $linecount : $height;
							}	
						}
						
						// MG dropdown
						elseif($rowSubGrid['tip'] == 6 && $rowSubGrid['enota'] == 2){

							$sqlSubVar = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$rowSubGrid['spr_id']."' AND vrstni_red='".$rowVrednost['vrstni_red']."' ");
							$rowSubVar = mysqli_fetch_array($sqlSubVar);
															
							$sqlUserAnswer = sisplet_query("SELECT d.grd_id, g.naslov, g.variable FROM srv_grid g INNER JOIN srv_data_grid".$this->db_table." d ON g.id=d.grd_id AND g.spr_id=d.spr_id WHERE d.spr_id='".$rowSubGrid['spr_id']."' AND d.usr_id='".$this->getUserId()."' AND d.vre_id='".$rowSubVar['id']."'");
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
							
							$title =  $this->encodeText( $userAnswer['naslov'] ? $userAnswer['naslov'] :  $userAnswer['variable'] );
		
							$linecount = $this->pdf->getNumLines($title, $w_oc);
							$height = ($linecount > $height) ? $linecount : $height;
						}
					}
				}

				if($height > 1){		
					$height = $height*4.5;
				}
				else{	
					$height = LINE_BREAK;
				}
				
				
				// Naredimo pb ce prebija katera vrstica
				if($pozicija_boka + $height > 250/$expand_width)
				{	
					$this->pdf->AddPage();
					$pozicija_boka = $this->pdf->getY();
				}
				
				
				/*Izpis bočnega stolpca*/				
				$startY = $this->pdf->getY();
				$endY = $this->pdf->getY();	
				$this->pdf->MultiCell($defw_fc, 0,$stringCell_title,0,'C',0,1,0,0,true,0);														
				$startX = $this->pdf->getX();					
//					print_r($pozicija_boka. " : ". $startY."<br>");
				$linecount_boka--;
				
				$shtevec=0;
				$startX = $startX + $defw_fc;
				$startY = $pozicija_boka;
				
				$endY = $startY + $height + 3;
						
				
				// Vsaka druga vrstica ima sivo ozadje				
				$XX = $this->pdf->getX();
				$YY = $this->pdf->getY();
				$this->pdf->setXY(15, $startY);
				$this->pdf->SetFillColor(242,243,241);
				$this->pdf->MultiCell($defw_full, $endY-$startY,'',0,'C',$row_color,1,0,0,true,0);
				$this->pdf->SetFillColor(0);					
				$this->pdf->setXY($XX, $YY);				
				$row_count++;
						
				
				// Loop po podskupinah gridov
				$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND s.id=m.spr_id ORDER BY m.vrstni_red");
				while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
					
					if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
						// Dobimo se var_id od trenutne podskupine
						$sqlSubVar = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$rowSubGrid['spr_id']."' AND vrstni_red='".$rowVrednost['vrstni_red']."' ");
						$rowSubVar = mysqli_fetch_array($sqlSubVar);
						
						// dropdown (samo izpisemo naslov odgovora - kot textbox)
						if($rowSubGrid['tip'] == 6 && $rowSubGrid['enota'] == 2) {
							
							$sqlUserAnswer = sisplet_query("SELECT d.grd_id, g.naslov, g.variable FROM srv_grid g INNER JOIN srv_data_grid".$this->db_table." d ON g.id=d.grd_id AND g.spr_id=d.spr_id WHERE d.spr_id='".$rowSubGrid['spr_id']."' AND d.usr_id='".$this->getUserId()."' AND d.vre_id='".$rowSubVar['id']."'");
							if(mysqli_num_rows($sqlUserAnswer) > 0){
								$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								$title = $this->encodeText( $userAnswer['naslov'] ? $userAnswer['naslov'] :  $userAnswer['variable'] );
							}
							else
								$title = '';
							
							$this->pdf->SetXY($startX + $shtevec*$w_oc , ($startY+$endY)/2 - (CHCK_BTN_SIZE)/2);
							$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);

							$this->pdf->SetXY($this->pdf->getX() - $w_oc/2, $startY + (CHCK_BTN_SIZE/2));							
							
							$this->pdf->SetFont(FNT_MAIN_TEXT, '', 8);
							$this->pdf->SetTextColor(179,0,128);
							$this->pdf->MultiCell($w_oc-1, $height, $title,1,'C',0,0,0,0,true,0);
							$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
							$this->pdf->SetTextColor(0,0,0);	
							
							$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
							$shtevec++;
						}
						else{
							$sqlVsehVrednsti = sisplet_query("SELECT id, naslov, variable FROM srv_grid WHERE spr_id='".$rowSubGrid['spr_id']."' ORDER BY vrstni_red");
							while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
								// poiščemo kaj je odgovoril uporabnik:
								if ($this->getGrupa())
								{
									if($rowSubGrid['tip'] == 6)
										$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubVar['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."'");
									elseif($rowSubGrid['tip'] == 16)
										$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubVar['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");
									else
										$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubVar['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");
									
									$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								}
								
								$prop['full'] = ($rowVsehVrednosti['id'] == $userAnswer['grd_id']) ? true : false;

								$this->pdf->SetXY($startX + $shtevec*$w_oc , ($startY+$endY)/2 - (CHCK_BTN_SIZE)/2);
								$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
							
								// radio
								if($rowSubGrid['tip'] == 6) {							
									$this->pdf->SetXY($startX + $shtevec*$w_oc + $halfWidth, ($startY+$endY)/2 - (CHCK_BTN_SIZE*3/4));
									$this->pdf->RadioButton('vpr_'. $rowSubVar['id'], RADIO_BTN_SIZE, $prop);
								}
								// checkbox
								elseif($rowSubGrid['tip'] == 16) {								
									$this->pdf->SetXY($startX + $shtevec*$w_oc + $halfWidth, ($startY+$endY)/2 - (CHCK_BTN_SIZE)/2);
									$this->pdf->CheckBox('vpr_'. $rowSubGrid['id'].'_'.$rowSubVar['id'], CHCK_BTN_SIZE, $prop);
								}
								// textbox
								else{							
									$this->pdf->SetXY($this->pdf->getX() - $w_oc/2, $startY + (CHCK_BTN_SIZE/2));							
									
									$this->pdf->SetFont(FNT_MAIN_TEXT, '', 8);
									//$this->pdf->SetTextColor(0,128,0);
									$this->pdf->SetTextColor(179,0,128);
									//$this->pdf->TextBoxes($w_oc,LINE_BREAK);	
									$this->pdf->MultiCell($w_oc-1, $height, $userAnswer['text'],1,'C',0,0,0,0,true,0);
									$this->pdf->SetFont(FNT_MAIN_TEXT, '', $this->font);
									$this->pdf->SetTextColor(0,0,0);		
								}
								
								$this->pdf->Cell($halfWidth, 0, '',0,0,1,0);
								$shtevec++;
							}
						}
					}
				}
				$this->pdf->setY($endY);
			}
		}
	}
	
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace("&scaron;","š",$text);
		$text = trim($text);
		
		return strip_tags($text);
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
	function setUserId($usrId) {$this->usr_id = $usrId;}
	function getUserId() {return ($this->usr_id)?$this->usr_id:false;}
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
    function checkSpremenljivka ($spremenljivka, $gridMultiple=false) {
	
        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '".$spremenljivka."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);
				
		// Ce vprasanje ni vidno ali ce uporabnik nima dostopa do vprasanja
        if ($row['visible'] == 0 || !( ($this->admin_type <= $row['dostop'] && $this->admin_type>=0) || ($this->admin_type==-1 && $row['dostop']==4) ) )
			return false;			
		
		// Kalklulacije in kvote ne prikazujemo
       	if($row['tip'] == 22 || $row['tip'] == 25)
               return false;
				   	
		// Preverjamo ce je vprasanje prazno in ce preskakujemo prazne
		if($this->skipEmpty == 1 && !$gridMultiple){	
			
			$isEmpty = true;
			switch ( $row['tip'] ){
				case 1: //radio
				case 2: //check
				case 3: //select -> radio
					$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$row[id]' AND usr_id='".$this->getUserId()."' AND vre_id!='-2'");
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;
				break;

				case 6: //multigrid
				case 16:// multicheckbox
				case 19:// multitext
				case 20:// multinumber											
					if($row['tip'] == 6 && $row['enota'] != 3)
						$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->getUserId()."'");
					elseif($row['tip'] == 16 || ($row['tip'] == 6 && $row['enota'] == 3))
						$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->getUserId()."'");
					else
						$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->getUserId()."'");
	
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;
				break;
				
				case 7: //number
				case 8: //datum	
				case 18: //vsota
				case 21: //besedilo*				
					$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$row['id']."' AND usr_id='".$this->getUserId()."'");
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;				
				break;
				
				case 17: //ranking					
					$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id=".$row['id']." AND usr_id='".$this->getUserId()."'");
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;
				break;
				
				case 24: //mesan multigrid	
					// loop po podskupinah gridov
					$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivka."' AND m.spr_id=s.id");
					while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){				
						
						if($rowSubGrid['tip'] == 6)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."'");
						elseif($rowSubGrid['tip'] == 16)
							$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."'");
						else
							$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->getUserId()."'");

						if(mysqli_num_rows($sqlUserAnswer) > 0){
							$isEmpty = false;	
							break;
						}
					}	
				break;
				
				case 5: //nagovor	
					// Ce je nagovor v loopu, ga prikazemo
					if($this->loop_id != null)
						$isEmpty = false;
				break;
				
				default:
					$isEmpty = false;
				break;
			}
			
			if($isEmpty == true){
				return false;
			}
		}

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
                    $sql3 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_data_vrednost v
                                         WHERE c.cond_id='$condition' AND c.vre_id=v.vre_id
                                         AND v.spr_id='$row[spr_id]' AND usr_id='".$this->getUserId()."'");
                    if ($row['operator'] == 0 && mysqli_num_rows($sql3) == 0)
                        return false;
                    elseif ($row['operator'] == 1 && mysqli_num_rows($sql3) > 0)
                        return false;
                // multigrid
                } elseif ($row['vre_id'] > 0) {
                    $sql3 = sisplet_query("SELECT * FROM srv_condition_grid c, srv_data_grid d
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
	
	/**
	* poisce, ce ima podani element parenta, ki je loop
	* 
	*/
	function find_parent_loop ($element_spr, $element_if=0) {
		
		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='".$this->anketa['id']."'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);
		
		if ($row['parent'] == 0) return 0;
		
		$sql = sisplet_query("SELECT id FROM srv_if WHERE id = '$row[parent]' AND tip = '2'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0)
			return $row['parent'];
		else
			return $this->find_parent_loop(0, $row['parent']);
			
	}
	
	 /**
    * poisce naslednjo vre_id v loopu
    * 
    */
    function findNextLoopId ($if_id=0) {
		
		if ($if_id == 0) {
			$sql = sisplet_query("SELECT * FROM srv_loop_data WHERE id='$this->loop_id'");
			$row = mysqli_fetch_array($sql);
			$if_id = $row['if_id'];
			$loop_id = $this->loop_id;
		} else
			$loop_id = 0;
				
		$sql = sisplet_query("SELECT * FROM srv_loop WHERE if_id = '$if_id'");
		$row = mysqli_fetch_array($sql);
		$spr_id = $row['spr_id'];
		$max = $row['max'];
		
		$spr = Cache::srv_spremenljivka($spr_id);
		
		if ($spr['tip'] == 2 || $spr['tip'] == 3 || $spr['tip'] == 9) {
				
			$data_vrednost = array();
			if($spr['tip'] == 9)
				$sql1 = sisplet_query("SELECT vre_id FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->getUserId()."'");
			else
				$sql1 = sisplet_query("SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->getUserId()."'");

			while ($row1 = mysqli_fetch_array($sql1)) {
				$data_vrednost[$row1['vre_id']] = 1;
			}
			
			$vre_id = '';
			$i = 1;
			//$sql = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if_id'");
			$sql = sisplet_query("SELECT * FROM srv_loop_vre lv, srv_vrednost v WHERE lv.if_id='$if_id' AND lv.vre_id=v.id ORDER BY v.vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {
				
				if ($row['tip'] == 0) {			// izbran
					if ( isset($data_vrednost[$row['vre_id']]) ) {
						$vre_id .= ', '.$row['vre_id'];
						$i++;
					}
				} elseif ($row['tip'] == 1) {	// ni izbran
					if ( !isset($data_vrednost[$row['vre_id']]) ) {
						$vre_id .= ', '.$row['vre_id'];
						$i++;
					}
				} elseif ($row['tip'] == 2) {	// vedno
					$vre_id .= ', '.$row['vre_id'];
					$i++;
				}								// nikoli nimamo sploh v bazi, zato ni potrebno nic, ker se nikoli ne prikaze
				
				if ($i > $max && $max>0) break;
			}
			
			$vre_id = substr($vre_id, 2);
			
			if ($vre_id == '') return null;
			
			$sql = sisplet_query("SELECT l.* FROM srv_loop_data l, srv_vrednost v WHERE l.if_id='$if_id' AND l.id > '$loop_id' AND l.vre_id IN ($vre_id) AND l.vre_id=v.id ORDER BY l.id ASC");
			if (!$sql) { echo 'err56545'.mysqli_error($GLOBALS['connect_db']); die();}
			$row = mysqli_fetch_array($sql);
			
			if (mysqli_num_rows($sql) > 0)
				return $row['id'];
			else
				return null;
				
		// number
		} elseif ($spr['tip'] == 7) {
			
			$sql1 = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->getUserId()."'");
			$row1 = mysqli_fetch_array($sql1);
			
			$num = (int)$row1['text'];
			$sql2 = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id='$if_id' AND id <= '$loop_id'");
			if (mysqli_num_rows($sql2) >= $num || (mysqli_num_rows($sql2) >= $max && $max>0))
				return null;
			
			$sql = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id'");
			$row = mysqli_fetch_array($sql);
			
			if (mysqli_num_rows($sql) > 0)
				return $row['id'];
			else
				return null;
			
		}
    }

	/**
    * @desc V podanem stringu poisce spremenljivke in jih spajpa z vrednostmi
    */
    function dataPiping ($text) {

    	Common::getInstance()->Init($this->anketa['id']);
        return Common::getInstance()->dataPiping($text, $this->usr_id, $this->loop_id);
    }
	
	function displayIf($if){
		global $lang;
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
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
		$this->pdf->MultiCell(90*$expand_width, 1, $this->encodeText($output),0,'L',0,1,0,0);
		$this->pdf->SetTextColor(0,0,0);
		$this->pdf->setFont('','',$this->font);
	}
	
	
	function outputUser()
	{
		$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' ORDER BY vrstni_red";
		$sqlGrupe = sisplet_query($sqlGrupeString);
		
		// filtriramo spremenljivke glede na profil - SAMO CE NE PRIHAJAMO IZ MAILA!
		$tmp_svp_pv = array();
		if($this->usr_type == null){			
			SurveyVariablesProfiles :: Init($this->anketa['id'], $this->resp_id, true, false);
			
			$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
			$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
			
			$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);
			
			foreach ( $tmp_svp_pv as $vid => $variable) {
				$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
			}
		}
		
		while ( $rowGrupe = mysqli_fetch_assoc( $sqlGrupe ) )
		{ // sprehodmo se skozi grupe ankete
			$this->grupa = $rowGrupe['id'];

			// Pogledamo prvo spremenljivko v grupi ce je v loopu
			$sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
			$row = mysqli_fetch_array($sql);

			// ce je ima loop za parenta
			$if_id = $this->find_parent_loop($row['id']);
			if ($if_id > 0){
				$sql1 = sisplet_query("SELECT * FROM srv_loop WHERE if_id = '$if_id'");
				$row1 = mysqli_fetch_array($sql1);
				$this->loop_id = $this->findNextLoopId($row1['if_id']);
				
				$if = Cache::srv_if($if_id);
				$loop_title = $if['label'];
				
				// gremo cez vse spremenljivke v trenutnem loopu
				while($this->loop_id != null){
				
					
					// Izrisemo naslov loopa
					$this->pdf->SetTextColor(0,0,200);
					$this->pdf->SetDrawColor(0,0,200);
					$this->pdf->setFont('','B',$this->font);
					
					$this->pdf->Write (0, $this->encodeText($this->dataPiping($loop_title)), '', 0, 'L', 1, 1);
					
					$this->pdf->SetTextColor(0,0,200);
					$this->pdf->SetDrawColor(0,0,200);
					$this->pdf->setFont('','',$this->font);
					

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
									$this->pdf->SetTextColor(128,0	,128);
								}
								else
								{		// Če je skrito = rdeče
									$this->pdf->SetTextColor(255,0,0);
								}
							}
							else if ($rowSpremenljivke['sistem'] == 1)
							  $this->pdf->SetTextColor(0,0,255);


							$this->pdf->SetTextColor(0,0,0);
							$this->pdf->SetDrawColor(0,0,0);

							// Ce imamo kombinirano tabelo pogledamo ce prikazujemo katero od podtabel
							if($rowSpremenljivke['tip'] == 24){
								
								$subGrids = array();
								$showGridMultiple = false;
								
								// Loop po podskupinah gridov					
								$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivka."' AND m.spr_id=s.id");
								while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){					
									if(in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0){
										$showGridMultiple = true;
										break;
									}
								}
							}
							// ce je nastavljen profil s filtriranimi spremenljivkami
							if(in_array($spremenljivka,$tmp_svp_pv) || count($tmp_svp_pv) == 0 || ($rowSpremenljivke['tip'] == 24 && $showGridMultiple)){
								
								$this->currentHeight = 0;
								
								// NAVADEN IZPIS rezultatov spremenljivke - kratek samo pri radio, checkbox, multiradio, multicheckbox, besedilo	
								if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16,21,7,8)) ){
									if($rowSpremenljivke['tip'] < 4)
										$this->outputVprasanjeValues($rowSpremenljivke);
									else
										$this->outputVprasanje($rowSpremenljivke);
										
									$this->outputSpremenljivkeValues($rowSpremenljivke);						
								}
								
								// KRATEK IZPIS rezultatov spremenljivke
								elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
									$this->outputVprasanjeValues($rowSpremenljivke);
									$this->outputSpremenljivkeValues($rowSpremenljivke);
								}	
								
								// DOLG IZPIS rezultatov
								else{
									$this->outputVprasanje($rowSpremenljivke);
									$this->outputSpremenljivke($rowSpremenljivke);	
								}
								
								$this->pdf->Ln(LINE_BREAK);
							}
						}
					}
								
					$this->loop_id = $this->findNextLoopId();					
				}
			}
			// Navadne spremenljivke ki niso v loopu
			else{
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
								$this->pdf->SetTextColor(128,0	,128);
							}
							else
							{		// Če je skrito = rdeče
								$this->pdf->SetTextColor(255,0,0);
							}
						}
						else if ($rowSpremenljivke['sistem'] == 1)
						  $this->pdf->SetTextColor(0,0,255);


						$this->pdf->SetTextColor(0,0,0);
						$this->pdf->SetDrawColor(0,0,0);

						// Ce imamo kombinirano tabelo pogledamo ce prikazujemo katero od podtabel
						if($rowSpremenljivke['tip'] == 24){
							
							$subGrids = array();
							$showGridMultiple = false;
							
							// Loop po podskupinah gridov					
							$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivka."' AND m.spr_id=s.id");
							while($rowSubGrid = mysqli_fetch_array($sqlSubGrid) || count($tmp_svp_pv) == 0){					
								if(in_array($rowSubGrid['spr_id'],$tmp_svp_pv)){
									$showGridMultiple = true;
									break;
								}
							}
						}
						// ce je nastavljen profil s filtriranimi spremenljivkami
						if(in_array($spremenljivka,$tmp_svp_pv) || count($tmp_svp_pv) == 0 || ($rowSpremenljivke['tip'] == 24 && $showGridMultiple)){
							
							$this->currentHeight = 0;
							
							// NAVADEN IZPIS rezultatov spremenljivke - kratek samo pri radio, checkbox, multiradio, multicheckbox, besedilo	
							if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16,21,7,8)) ){
								if($rowSpremenljivke['tip'] < 4)
									$this->outputVprasanjeValues($rowSpremenljivke);
								else
									$this->outputVprasanje($rowSpremenljivke);
									
								$this->outputSpremenljivkeValues($rowSpremenljivke);						
							}
							
							// KRATEK IZPIS rezultatov spremenljivke
							elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
								$this->outputVprasanjeValues($rowSpremenljivke);
								$this->outputSpremenljivkeValues($rowSpremenljivke);
							}	
							
							// DOLG IZPIS rezultatov
							else{
								$this->outputVprasanje($rowSpremenljivke);
								$this->outputSpremenljivke($rowSpremenljivke);	
							}
							
							$this->pdf->Ln(LINE_BREAK);
						}
					}
				}
			}
		}
		if($this->pageBreak == 0){
			$this->pdf->Ln(LINE_BREAK);
			$this->drawLine();
			$this->pdf->Ln(LINE_BREAK);
		}
	}

	
	function getCellHeight($string, $width){
		
		$this->pdf->startTransaction();
		// get the number of lines calling you method
		$linecount = $this->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();
			
		$height = ($linecount <= 1) ? 4.7 : $linecount * ($this->pdf->getFontSize() * $this->pdf->getCellHeightRatio()) + 2;
		
		return $height;
	}
	
	function convertPx2Mm($WidthInMm, $WidthPx, $NPx){
		$convertedInMm = ($WidthInMm/$WidthPx)*$NPx;
		//error_log("convertedInMm: $convertedInMm");
		return $convertedInMm;
	}
}


?>