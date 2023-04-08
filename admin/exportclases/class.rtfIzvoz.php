<?php
/*
 * Created on 28.2.2009
 *
 */
require("class.enka.rtf.php");
require_once('../../vendor/autoload.php');


define("FNT_TIMES", "Times New Roman", true);
define("FNT_ARIAL", "Arial", true);

define("FNT_MAIN_TEXT", FNT_TIMES, true);
define("FNT_QUESTION_TEXT", FNT_TIMES, true);
define("FNT_HEADER_TEXT", FNT_TIMES, true);

define("FNT_MAIN_SIZE", 12, true);
define("FNT_QUESTION_SIZE", 10, true);
define("FNT_HEADER_SIZE", 10, true);


class RtfIzvoz {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kliče konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;

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

			// create new RTF document
			$this->rtf = new enka_RTF();
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

			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
				$this->db_table = '_active';
		}
		else
			return false;
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;
		return true;
	}

	function getAnketa()
	{ return $this->anketa['id']; }

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		//Close and output rtf document
//		$this->rtf->Output($fileName, 'I');
		$this->rtf->display($fileName = 'anketa'.time().'.rtf',true);
	}



	function init(){
		global $lang;

		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();

		if ($this->language != -1) {
			SurveySetting::getInstance()->Init($this->anketa['id']);
			$_lang = '_'.$this->language;
			$srv_novaanketa_kratkoime = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_novaanketa_kratkoime'.$_lang);
		}
		else{
			$srv_novaanketa_kratkoime = SurveyInfo::getInstance()->getSurveyAkronim();
		}
		$this->rtf->WriteHeader($this->enkaEncode($srv_novaanketa_kratkoime), 'left');
		$this->rtf->WriteHeader($this->enkaEncode($srv_novaanketa_kratkoime), 'right');

		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right');

		$this->rtf->set_default_font(FNT_TIMES, $this->font);

		return true;
	}

	function createRtf(){
		// Izpis vprasanj s komentarji
		if($this->type == 2)
			$this->outputCommentaries();

		// Izpis vprasalnika oz odgovorov enega respondenta
		else
			$this->outputSurvey();
	}

	// Izpis vprasalnika (z ali brez odgovorov)
	function outputSurvey(){
		global $lang;

		$rowA = SurveyInfo::getInstance()->getSurveyRow();


		// izpišemo prvo stran
		$this->createFrontPage();


		// Izpisemo vprasalnik

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
				
				// ce obstaja intro izpisemo intro
				$this->rtf->add_text($intro);
				$this->rtf->new_line(3);
			}
		}

		// filtriramo spremenljivke glede na profil
		SurveyVariablesProfiles :: Init($this->anketa['id']);

		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);

		$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);

		foreach ( $tmp_svp_pv as $vid => $variable) {
			$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
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

					// če imamo številčenje Type = 1 potem številčimo V1
					if (SurveyInfo::getInstance()->getSurveyCountType())
						$zaporedna++;
					$stevilcenje = ( SurveyInfo::getInstance()->getSurveyCountType() ) ?
					( ( SurveyInfo::getInstance()->getSurveyCountType() == 2 ) ? $rowSpremenljivke['variable'].") " : $zaporedna.") " ) : null;


					// izpis skrcenega vprasalnika (samo pri izpisu iz urejanja)
					if($rowA['expanded'] == 0 && $this->type == 1){
						$this->outputVprasanjeCollapsed($rowSpremenljivke, $stevilcenje);
					}

					// izpis navadnega vprasalnika
					else{
						$this->outputVprasanje($rowSpremenljivke, $stevilcenje);
						$this->outputSpremenljivke($rowSpremenljivke);
					}


					$this->rtf->new_line(1);
				}
			}
		}

		// če izpisujemo grupo, ne izpisujemo zakljucka
		if ( !$this->getGrupa() ){
			if ( SurveyInfo::getInstance()->getSurveyShowConcl() && SurveyInfo::getInstance()->getSurveyConcl() )
			{		// ce obstaja footer izpisemo footer
				$this->rtf->add_text(SurveyInfo::getInstance()->getSurveyConcl());
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

					$this->outputVprasanje($row, null);
					$this->outputSpremenljivke($row);

					if ($admin_type <= $question_note_view || $question_note_view == '') {

						if ($row1['note'] != '') {

							$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode($lang['hour_comment']).$this->rtf->bold(0));
							$this->rtf->new_line(1);

							$this->rtf->add_text($this->enkaEncode($row1['note']));
							$this->rtf->new_line(1);
						}
					}

					// komentarji na vprasanje
					if ($row1['thread'] > 0) {

						if (mysqli_num_rows($sqlt) > 0) {

							$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode($lang['srv_admin_comment']).$this->rtf->bold(0));
							$this->rtf->new_line(2);

							$i = 0;
							while ($rowt = mysqli_fetch_array($sqlt)) {

								$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode($f->user($rowt['uid'])).$this->rtf->bold(0).$this->enkaEncode(' ('.$f->datetime1($rowt['time']).'):'));
								$this->rtf->new_line(1);

								// Popravimo vsebino ce imamo replike
								$vsebina = iconv("iso-8859-2", "UTF-8", $rowt['vsebina']);
								$odgovori = explode("<blockquote style=\"margin-left:20px\">", $vsebina);

								$this->rtf->add_text($this->enkaEncode($odgovori[0]));
								$this->rtf->new_line(2);

								unset($odgovori[0]);
								foreach($odgovori as $odgovor){

									$odgovor = explode('<br />', $odgovor);
									$avtor = explode('</b> ', $odgovor[0]);

									$this->rtf->add_text(' \tab '.$this->rtf->bold(1).$this->enkaEncode($avtor[0]).$this->rtf->bold(0).$this->enkaEncode($avtor[1]));
									$this->rtf->new_line(1);

									$this->rtf->add_text(' \tab '.$this->enkaEncode($odgovor[1]));
									$this->rtf->new_line(2);
								}
							}
						}
					}

					// komentarji respondentov
					if ($row2['count'] > 0) {

						if ($admin_type <= $question_resp_comment_viewadminonly) {

							$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode($lang['srv_repondent_comment']).$this->rtf->bold(0));
							$this->rtf->new_line(2);

							if ($this->commentType == 1) $only_unresolved = " AND d.text2 <= 1 "; else $only_unresolved = " ";

							$sqlt = sisplet_query("SELECT d.*, u.time_edit FROM srv_data_text".$this->db_table." d, srv_user u WHERE d.spr_id='0' AND d.vre_id='$row[id]' AND u.id=d.usr_id $only_unresolved2 ORDER BY d.id ASC");
							if (!$sqlt) echo mysqli_error($GLOBALS['connect_db']);
							while ($rowt = mysqli_fetch_array($sqlt)) {

								$this->rtf->add_text($this->enkaEncode($f->datetime1($rowt['time_edit']).':'));
								$this->rtf->new_line(1);

								$this->rtf->add_text($this->enkaEncode($rowt['text']));
								$this->rtf->new_line(2);
							}
						}
					}
					
					$this->rtf->new_line(2);
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

		// Zamik zaradi ifov
		$zamik = ( $b->level($spremenljivke['id'],0) > 0 ? (($b->level($spremenljivke['id'],0)-1)) : 0 );
		for($i=0; $i<$zamik; $i++){
			$this->rtf->add_text('\tab');
		}

		$rowl = $this->srv_language_spremenljivka($spremenljivke);
		if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];

		//izpis if-ov pri vprasanju
		if(/*$this->showIf == 1*/ true){

			if ($rowIf['parent'] > 0){
				$rowb = Cache::srv_if($rowIf['parent']);

				if ($rowb['tip'] == 0){
					$this->displayIf($rowIf['parent']);
					$this->rtf->new_line(1);
					$zamik ++;
				}
			}
		}

		for($i=0; $i<$zamik; $i++){
			$this->rtf->add_text('\tab');
		}

		// stevilcenje vprasanj
		$numberingText = '('.$spremenljivke['variable'].') ';

		$this->rtf->add_text($this->rtf->color(11).$numberingText.$this->rtf->color(0));
		$this->rtf->add_text($this->enkaEncode($spremenljivke['naslov']));
		$this->rtf->add_text($this->rtf->color(15).$this->enkaEncode(' ( '.$lang['srv_vprasanje_tip_long_'.$spremenljivke['tip']].' )').$this->rtf->color(0));
		$this->rtf->new_line(1);

		// izpis pagebreaka
		if($b->pagebreak($spremenljivke['id'])){

			/*$this->currentStyle = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => '2,2', 'color' => array(128, 128, 128));
			$cy = $this->pdf->getY()+3;
			$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);*/
			$this->rtf->new_line(1);
			$this->rtf->TextCell('', array('width' => 9200, 'height' => 0,
				'align' => 'center', 'valign' => 'middle' , 'border' => 'top',
				'colorF' => "0", 'colorB' => "0" ) );
		}
	}

	function outputVprasanje($spremenljivke, $zaporedna){

		$rowl = $this->srv_language_spremenljivka($spremenljivke);
		if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];
		if (strip_tags($rowl['info']) != '') $spremenljivke['info'] = $rowl['info'];

		//izpis if-ov pri vprasanju
		if($this->showIf == 1){

			/*$sqlIf = sisplet_query("SELECT * FROM srv_branching WHERE element_spr='$spremenljivke[id]'");
			$rowIf = mysqli_fetch_array($sqlIf);

			if ($rowIf['parent'] > 0){
				$rowb = Cache::srv_if($rowIf['parent']);

				if ($rowb['tip'] == 0){
					$this->displayIf($rowIf['parent']);
					$this->rtf->new_line(1);
				}
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
				$this->rtf->new_line(1);
			}
		}

		// stevilcenje vprasanj
		$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';

		// pretvorimo html v rtf
		$text = $this->rtf->HTMLtoRTF($numberingText . $spremenljivke['naslov']);

		$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode($text).$this->rtf->bold(0));

		if($spremenljivke['orientation']!=0){	//ce ni vodoravno ob vprasanju, pejdi v novo vrstico
			// Izpisemo opombo, ce jo imamo
			if($spremenljivke['info'] != ''){
				$this->rtf->new_line(1);

				$this->rtf->set_font_size($this->font-2);
				$this->rtf->add_text($this->rtf->color(15).$this->enkaEncode($spremenljivke['info']).$this->rtf->color(0));
				$this->rtf->set_font_size($this->font);
			}

			//$this->rtf->add_text($numberingText . $this->enkaEncode($spremenljivke['naslov']));
		
			$this->rtf->new_line(1);
		}
	}

	function outputSpremenljivke($spremenljivke)
	{
		global $lang;
		switch ( $spremenljivke['tip'] )
		{
			case 1: //radio
			case 2: //check
			case 3: //select -> radio
				
				if($spremenljivke['orientation']!=0){	//ce ni vodoravno ob vprasanju, pejdi v novo vrstico
					$this->rtf->new_line(1);
				}

				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");

				//ce imamo prikaz v vec stoplcih
				$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
				$stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);
				$checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);

				if ($stolpci > 1 && $spremenljivke['orientation']==1) {
					//echo '<div style="float:left; width:'.(100/$stolpci).'%">';
					$kategorij = mysqli_num_rows($sqlVrednosti);
					$v_stolpcu = ceil($kategorij / $stolpci);

					$width = round(10000 / $stolpci);

					$this->rtf->MyRTF .= "{\par";
					$tableHeader = '\trowd\trql\trrh400';
				}
				

				$count = 0;
				$table = '';
				$tableEnd = '';
				$SeznamTable = array();
				$SeznamBorders = array();
				
				$PredefinedSeznamBorders = array();
				$PredefinedSeznamBorders[0] = array('top', 'left', 'right');
				$PredefinedSeznamBorders[1] = array('left', 'right');
				$PredefinedSeznamBorders[2] = array('right', 'left', 'bottom');
				$AllBorders = array('top', 'left', 'right', 'bottom');
				
				if($spremenljivke['orientation']!=10)
				{	//ce ni image hot spot
					while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
					{
						//popravimo lokacijo ce imamo postavitev v vec stolpcih
						if ( ($stolpci > 1) && ($spremenljivke['orientation']==1) && ($count % $v_stolpcu == 0) ) {

							$yPos = floor($count / $v_stolpcu) + 1;

							$table .= '\clvertalc\cellx'.( $yPos * $width );
							$tableEnd .= '\pard\intbl ';
						}

						# po potrebi prevedemo naslov
						$naslov = $this->srv_language_vrednost($rowVrednost['id']);
						if ($naslov != '') {
							$rowVrednost['naslov'] = $naslov;
						}

						$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						
						if($spremenljivke['orientation']==1){	//navpicno
							if( isset($userAnswer[$rowVrednost['id']]) ){
								if(($stolpci > 1) && ($spremenljivke['orientation']==1))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
							}
							else{
								if(($stolpci > 1) && ($spremenljivke['orientation']==1))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
							}

							if(($stolpci > 1) && ($spremenljivke['orientation']==1))
								$tableEnd .= ' '.$stringTitle.'\\line\n';
							else{
								$this->rtf->add_text(" ".$stringTitle);
								$this->rtf->new_line(1);
							}

							$count++;

							if ( ($stolpci > 1) && ($spremenljivke['orientation']==1) && ($count % $v_stolpcu == 0 || $count == $kategorij) ) {
								$tableEnd .= ' \ql\cell';
							}
						}elseif($spremenljivke['orientation']==7){	//navpicno - tekst levo
							
							if(($stolpci > 1) && ($spremenljivke['orientation']==7))
								$tableEnd .= ' '.$stringTitle.' ';
							else{
								$this->rtf->add_text(" ".$stringTitle. "\t");
							}
							
							if( isset($userAnswer[$rowVrednost['id']]) ){
								if(($stolpci > 1) && ($spremenljivke['orientation']==7)){
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
									$tableEnd .= '\\line\n';	
								}

								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
							}
							else{
								if(($stolpci > 1) && ($spremenljivke['orientation']==7)){
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
									$tableEnd .= '\\line\n';	
								}
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
							}

							$count++;

							if ( ($stolpci > 1) && ($spremenljivke['orientation']==7) && ($count % $v_stolpcu == 0 || $count == $kategorij) ) {
								$tableEnd .= ' \ql\cell';
							}else{
								$this->rtf->new_line(1);
							}
						}elseif($spremenljivke['orientation']==0){	//vodoravno ob vprasanju
							if( isset($userAnswer[$rowVrednost['id']]) ){
								if(($stolpci > 1) && ($spremenljivke['orientation']==0))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
							}
							else{
								if(($stolpci > 1) && ($spremenljivke['orientation']==0))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
							}

							if(($stolpci > 1) && ($spremenljivke['orientation']==0))
								//$tableEnd .= ' '.$stringTitle.'\\line\n';
							$tableEnd .= ' '.$stringTitle;
							else{
								$this->rtf->add_text(" ".$stringTitle);
								//$this->rtf->new_line(1);
							}

							$count++;

							if ( ($stolpci > 1) && ($spremenljivke['orientation']==0) && ($count % $v_stolpcu == 0 || $count == $kategorij) ) {
								$tableEnd .= ' \ql\cell';
							}
						} elseif($spremenljivke['orientation']==2){	//vodoravno pod vprasanjem
							if( isset($userAnswer[$rowVrednost['id']]) ){
								if(($stolpci > 1) && ($spremenljivke['orientation']==2))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
							}
							else{
								if(($stolpci > 1) && ($spremenljivke['orientation']==2))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
							}

							if(($stolpci > 1) && ($spremenljivke['orientation']==2))
								//$tableEnd .= ' '.$stringTitle.'\\line\n';
							$tableEnd .= ' '.$stringTitle;
							else{
								$this->rtf->add_text(" ".$stringTitle);
								//$this->rtf->new_line(1);
							}

							$count++;

							if ( ($stolpci > 1) && ($spremenljivke['orientation']==0) && ($count % $v_stolpcu == 0 || $count == $kategorij) ) {
								$tableEnd .= ' \ql\cell';
							}
						}elseif($spremenljivke['orientation']==6){	//izberite s seznama
							array_push( $SeznamTable, array($stringTitle) );

							if($count == 0){
								array_push( $SeznamBorders, $PredefinedSeznamBorders[0]);
							}elseif($count != 0 && $count != (mysqli_num_rows($sqlVrednosti) - 1)){
								array_push( $SeznamBorders, $PredefinedSeznamBorders[1]);
							} elseif($count == (mysqli_num_rows($sqlVrednosti) - 1)){
								array_push( $SeznamBorders, $PredefinedSeznamBorders[2]);
							}
							$count++;					
						}elseif($spremenljivke['orientation']==8){	//povleci-spusti
							array_push( $SeznamTable, array($stringTitle) );

							if($count == 0){
								array_push( $SeznamBorders, $PredefinedSeznamBorders[0]);
							}elseif($count != 0 && $count != (mysqli_num_rows($sqlVrednosti) - 1)){
								array_push( $SeznamBorders, $PredefinedSeznamBorders[1]);
							} elseif($count == (mysqli_num_rows($sqlVrednosti) - 1)){
								array_push( $SeznamBorders, $PredefinedSeznamBorders[2]);
							}


							$count++;					
						}else{
							if( isset($userAnswer[$rowVrednost['id']]) ){
								if(($stolpci > 1) && ($spremenljivke['orientation']==1))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox2.png" : "radio2.png"), "15");
							}
							else{
								if(($stolpci > 1) && ($spremenljivke['orientation']==1))
									$tableEnd .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
								else
									$this->rtf->MyRTF .= $this->rtf->ImageToString( ( ( $spremenljivke['tip'] == 2 ) ? "checkbox.png" : "radio.png"), "15");
							}

							if(($stolpci > 1) && ($spremenljivke['orientation']==1))
								$tableEnd .= ' '.$stringTitle.'\\line\n';
							else{
								$this->rtf->add_text(" ".$stringTitle);
								$this->rtf->new_line(1);
							}

							$count++;

							if ( ($stolpci > 1) && ($spremenljivke['orientation']==1) && ($count % $v_stolpcu == 0 || $count == $kategorij) ) {
								$tableEnd .= ' \ql\cell';
							}
						}

					}
				}
				
				if($spremenljivke['orientation']==6){	//izberite s seznama					
					$this->rtf->TableFromArraySelect( array( 4750, 4750 ), $SeznamTable, $SeznamBorders, mysqli_num_rows($sqlVrednosti));
				}
				if($spremenljivke['orientation']==8){	//povleci-spusti
					//$this->rtf->TableFromArrayDragDrop( array( 4750, 4750 ), $SeznamTable, $SeznamBorders, mysqli_num_rows($sqlVrednosti));
					$this->rtf->TableFromArrayDragDrop( array( 3000, 3000 ), $SeznamTable, $SeznamBorders, mysqli_num_rows($sqlVrednosti));
				}
				if($spremenljivke['orientation']==10)//image hot spot
				{
					$odgovor = "Image hot spot";
					$this->rtf->TextCell($odgovor, array('width' => 6500, 'height' => 5, 'border' => array('top','bottom', 'left','right') ) );
					$this->rtf->new_line(1);
				}

				if ($stolpci > 1 && $spremenljivke['orientation'] == 1) {

					$tableEnd .= '\pard\intbl\row';
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);

					$this->rtf->MyRTF .= "}";
				}
				
				if($spremenljivke['orientation']==0){	//ce je vodoravno ob vprasanju, pride opomba na koncu
					// Izpisemo opombo, ce jo imamo
					if($spremenljivke['info'] != ''){
						$this->rtf->new_line(1);

						$this->rtf->set_font_size($this->font-2);
						$this->rtf->add_text($this->rtf->color(15).$this->enkaEncode($spremenljivke['info']).$this->rtf->color(0));
						$this->rtf->set_font_size($this->font);
					}

					//$this->rtf->add_text($numberingText . $this->enkaEncode($spremenljivke['naslov']));				
					$this->rtf->new_line(1);
				}
				
				$this->rtf->new_line(1);
			break;

 			case 6: // multigrid
			case 16: // multicheckbox
			case 19: // multitext
			case 20: // multinumber

				//izris dvojnega multigrida
				if($spremenljivke['enota'] == 3){
					$this->displayDoubleGrid($spremenljivke);

					break;
				}
				
				if($spremenljivke['enota']!=10){	//ce ni image hot spot
				
					//za izberite s seznama in povleci-spusti
					if($spremenljivke['enota'] == 6)
					{
						$headerTitles = array();
						$stringCellTitles = array();
					}elseif($spremenljivke['enota'] == 9)
					{
						$headerTitles = array();
						//$headerTitles = array($lang['srv_drag_drop_answers'].": ");
						$stringCellTitles = array();
						//$stringCellTitles = array($lang['srv_ranking_avaliable_categories'].": ");
						$odgovor = "Drag and drop";
						$this->rtf->TextCell($odgovor, array('width' => 7000, 'height' => 1, 'border' => array('top','bottom', 'left','right') ) );
						$this->rtf->new_line(1);
						$this->rtf->TextCells($this->enkaEncode($lang['srv_ranking_avaliable_categories']), $lang['srv_drag_drop_answers']);
					}					
					//za izberite s seznama in povleci-spusti - konec
				
				
					$this->rtf->MyRTF .= "{\\par\\fs22";

					$sqlStVrednosti = sisplet_query("SELECT count(*) AS count FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
					$rowStVrednost = mysqli_fetch_row($sqlStVrednosti);

					$defw_full = 10500;
					if($rowStVrednost[0] < 6 && ($spremenljivke['tip'] != 6 || $spremenljivke['enota'] != 1)){
						$defw_fc = 4300; // first cell width
					}
					else{
						$defw_fc = 2000; // first cell width
					}

					$kolon = $rowStVrednost[0]+1;
					// Ce imamo diferencial
					if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1)
						$w_oc = ( $defw_full - (2*$defw_fc) ) / $kolon;
					else
						$w_oc = ( $defw_full - $defw_fc ) / $kolon;
					$defw_max = floor($w_oc);

					$tableHeader_base = "\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";
					$tableHeader_width = "\\cellx".$defw_fc;
					$tableHeader_title = "\\pard\\intbl\\qc{}\\cell";
					$tableHeader_finish = "\\pard\\intbl\\row";
					
					$sqlVsehVrednsti = sisplet_query("SELECT naslov, id, variable FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					$rowCnt = 0;
					while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
					{
						$rowCnt++;
						$tableHeader_width .= "\\cellx". ( $rowCnt * $defw_max + $defw_fc );

						# priredimo naslov če prevajamo anketo
						$naslov = $this->srv_language_grid($spremenljivke['id'], $rowVsehVrednosti['id']);
						if ($naslov != '') {
							$rowVsehVrednosti['naslov'] = $naslov;
						}

						// če ni naslova vzamemo variable
						$stringHeader_title = $this->enkaEncode( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] : $rowVsehVrednosti['variable'] );
						$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
						
						if($spremenljivke['enota'] == 6 || $spremenljivke['enota'] == 9)
						{
							array_push($headerTitles, $this->enkaEncode($stringHeader_title));
						}
					}
					
					if($spremenljivke['enota'] != 6 && $spremenljivke['enota'] != 9)
					{
						// izpišemo header celice
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);
					}
					// loopamo skozi vrstice in pripravimo podatke za tabelo z radii
					$row_count = 1;
					$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
					while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
						$i=1;

						// barva vrstice
						$row_color = ($row_count%2 == 1) ? '\\clcbpat18' : '';

						# po potrebi prevedemo naslov
						$naslov = $this->srv_language_vrednost($rowVrednost['id']);
						if ($naslov != '') {
							$rowVrednost['naslov'] = $naslov;
						}

						// če ni naslova vzamemo naslov2, če ne pa variable
						$stringCell_title = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
						
						if($spremenljivke['enota'] == 9)	//povleci-spusti
						{
							$tableHeader_width = "\\cellx".$defw_fc;
						}else
						{
							$tableHeader_width = $row_color."\\cellx".$defw_fc;
						}
						
						
						$tableHeader_title = "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title)."\\cf0\\cell";
						$tableHeader_finish = "\\pard\\intbl\\row";
						
						if($spremenljivke['enota'] == 6 && $spremenljivke['enota'] == 9)
						{
							array_push($stringCellTitles, $tableHeader_title);
						}

						$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
						while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){

							$full = false;

							if($spremenljivke['tip'] == 6 && ($spremenljivke['enota'] != 6 && $spremenljivke['enota'] != 9) )
							{
								$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
								if($full)
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio2.png", "15")."}\\cell";
								else
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio.png", "15")."}\\cell";
							}
							elseif($spremenljivke['tip'] == 16 && ($spremenljivke['enota'] != 6 && $spremenljivke['enota'] != 9) )
							{
								$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
								if($full)
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox2.png", "15")."}\\cell";
								else
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox.png", "15")."}\\cell";
							}
							//else{
							elseif($spremenljivke['enota'] != 6 && $spremenljivke['enota'] != 9)
							{
								$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
								$tableHeader_width .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
								$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->color(0).'\qc{}\\cell';
							}elseif($spremenljivke['enota'] == 6)	//izberite s seznama
							{
								if($i == 1)
								{									
									$odgovoriSeznam = implode("\\line ", $headerTitles);	//vsako vrednost iz polja dej v string vsaka v svojo vrstico
									//$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx7000";								
									$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\\cellx7000";								
									$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->color(0).'\qc{'.$odgovoriSeznam.'}\\cell';
								}
							}elseif($spremenljivke['enota'] == 9)	//povleci-spusti
							{
								if($i == 1 && $row_count == 1)
								{									
									$odgovoriSeznam = implode("\\line ", $headerTitles);	//vsako vrednost iz polja dej v string vsaka v svojo vrstico
									//$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx7000";								
									//$tableHeader_width .= "\clbrdrt".$row_color."\\cellx7000";								
									$tableHeader_width .= "\clbrdrt\\cellx7000";								
									$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->color(0).'\qc{'.$odgovoriSeznam.'}\\cell';
								}
							}
							$i++;
						}
						// Ce imamo diferencial
						if($spremenljivke['tip'] == 6 && $spremenljivke['enota'] == 1){
							$stringCell_title2 = $this->enkaEncode($rowVrednost['naslov2']);
							$tableHeader_width .= $row_color."\\cellx".( ($i-1) * $defw_max + 2*$defw_fc );
							$tableHeader_title .= "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title2)."\\cf0\\cell";
						}

						$row_count++;

						$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);
					}
					$this->rtf->MyRTF .= "}";				
				}
				
				if($spremenljivke['enota']==10)//image hot spot
				{	
					$odgovor = "Image hot spot";
					$this->rtf->TextCell($odgovor, array('width' => 6500, 'height' => 5, 'border' => array('top','bottom', 'left','right') ) );
					$this->rtf->new_line(1);
				}
/* 				if($spremenljivke['enota']==9){	//povleci-spusti
					//$this->rtf->TableFromArrayDragDrop( array( 4750, 4750 ), $SeznamTable, $SeznamBorders, mysqli_num_rows($sqlVrednosti));
					//$this->rtf->TableFromArrayDragDropGrid( array( 3000, 3000 ), $odgovoriSeznam, $SeznamBorders, mysqli_num_rows($sqlVrednosti));
					
					if(mysqli_num_rows($sqlVsehVrednsti) > mysqli_num_rows($sqlVrednosti))
					{
						$numOfRows = mysqli_num_rows($sqlVsehVrednsti);
					}else
					{
						$numOfRows = mysqli_num_rows($sqlVrednosti);
					}
					
					$this->rtf->TableFromArrayDragDropGrid( array( 3000, 3000 ), $stringCellTitles, $numOfRows);
					
				} */
				
				$this->rtf->new_line(1);
			break;

			case 24: // mesan multigrid
				$this->displayGridMultiple($spremenljivke);
			break;

			case 4: //text
				$this->rtf->TextCell("", array('width' => 9500, 'height' => 3, 'border' => array('top','bottom', 'left','right') ) );
				$this->rtf->new_line(1);
			break;

			case 21: //besedilo*
				$this->rtf->new_line(1);
				$list = array();

				$this->rtf->MyRTF .= "{\par";

				$defw_full = 9500;
                $defw_part = round($defw_full / $spremenljivke['text_kosov']);

				$tableHeader = '\trowd\trql\trrh800';
				$podnapisi = '\trowd\trql';

				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				for($i=0; $i<$spremenljivke['text_kosov']; $i++){

					$rowVrednost = mysqli_fetch_array($sqlVrednosti);

					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;

					if($spremenljivke['text_orientation'] == 1){
						$table .= '\cellx'.( $i * $defw_part + 1000).'\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( ($i+1) * $defw_part);
						$tableEnd .= '\pard\intbl '.$this->enkaEncode($stringTitle).'\qc\cell\pard\intbl'.$this->rtf->color(12).' '.$userAnswer['text'].$this->rtf->color(0).'\cell';
					}
					else{
						$table .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( ($i+1) * $defw_part );
						$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$userAnswer['text'].$this->rtf->color(0).'\cell';
					}

					if($spremenljivke['text_orientation'] == 2){
						$podnapisi .= '\cellx'.( ($i+1) * $defw_part ).'';
						$podnapisiEnd .= '\pard\intbl '.$this->enkaEncode($stringTitle).'\qc\cell';
					}
				}
				$tableEnd .= '\pard\intbl\row';
				$podnapisiEnd .= '\pard\intbl\row';

				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd.($spremenljivke['text_orientation'] == 2 ? $podnapisi.$podnapisiEnd : ''));
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);


				// Izris polj drugo - ne vem...
				$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' and other!='0' order BY vrstni_red");
				while($rowVrednost = mysqli_fetch_array($sqlVrednost)) {
					
					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$this->rtf->MyRTF .= $this->rtf->ImageToString("checkbox.png", "15").' '.$this->rtf->enkaEncode($rowVrednost['naslov']);
					$this->rtf->new_line(1);
				}

			break;

			case 5: //label
				$this->rtf->new_line(2);
			break;

			case 7: //number
				$this->rtf->new_line(1);
				$list = array();

				$this->rtf->MyRTF .= "{\par";

				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                $rowVrednost = mysqli_fetch_array($sqlVrednosti);

				# po potrebi prevedemo naslov
				$naslov = $this->srv_language_vrednost($rowVrednost['id']);
				if ($naslov != '') {
					$rowVrednost['naslov'] = $naslov;
				}

				$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
				$list[] = $stringTitle;

				if($spremenljivke['size'] == 1) {
					if ($spremenljivke['enota'] == 1) {
						#enota na levi
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000'
						.'\qc\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell\pard\intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell\pard\intbl\row';
					} elseif ($spremenljivke['enota'] == 2) {
						#enota na desni
						$table = '\trowd\trql\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx1500\cellx3000';
						$table .= '\qc\pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell\pard\intbl\row';
					} else {
						#brez enote
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000'
						.'\qc\pard\intbl '.
						'\qc\cell\pard\intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell\pard\intbl\row';
					}
				} else{
					$rowVrednost = mysqli_fetch_array($sqlVrednosti);

					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$stringTitle2 = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );

					if ($spremenljivke['enota'] == 1) {
						#enota na levi
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000';
						$table .= '\qc\pard \intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle2),20,'...') )
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl \row';
					} else if ($spremenljivke['enota'] == 2) {
						#enota na desni
						$table = '\trowd\trql\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx1500\cellx3000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx4500\cellx6000';
						$table .= '\qc\pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle2),20,'...') )
						.'\qc\cell \pard \intbl \row';

					} else {
						#brez eneote
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000';
						$table .= '\qc\pard \intbl '
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl '
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->rtf->color(0)
						.'\qc\cell \pard \intbl \row';

					}
				}

				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);

				// Izris polj drugo - ne vem...
				$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' and other!='0' order BY vrstni_red");
				while($rowVrednost = mysqli_fetch_array($sqlVrednost)) {

					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$this->rtf->MyRTF .= $this->rtf->ImageToString("checkbox.png", "15").' '.$this->enkaEncode($rowVrednost['naslov']);
					$this->rtf->new_line(1);
				}

			break;

			case 8: //datum
				$this->rtf->MyRTF .= $this->rtf->color(12);
				$this->rtf->TextCell($this->enkaEncode($userAnswer['text']), array('width' => 2000, 'height' => 1, 'border' => array('top','bottom', 'left','right') ) );
				$this->rtf->MyRTF .= $this->rtf->color(0);
				$this->rtf->new_line(1);

				// Izris polj drugo - ne vem...
				$sqlVrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' and other!='0' order BY vrstni_red");
				while($rowVrednost = mysqli_fetch_array($sqlVrednost)) {

					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$this->rtf->MyRTF .= $this->rtf->ImageToString("checkbox.png", "15").' '.$this->enkaEncode($rowVrednost['naslov']);
					$this->rtf->new_line(1);
				}

			break;

			case 18: //vsota
				$this->rtf->new_line(1);
				$list = array();

				$this->rtf->MyRTF .= "{\par";

				// iz baze preberemo vse moznosti
				$sum = 0;
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){

					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;

					$table .= '\trowd\trql\cellx5000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx5800\pard\intbl '.$this->snippet($this->enkaEncode($stringTitle),50,'...').'\~\~\qr\cell\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($rowAnswers['text']).$this->rtf->color(0).'\qc\cell\pard\intbl\row';

					$sum += (int)$rowAnswers['text'];
				}

				$table .= '\trowd \trql\clbrdrb\brdrs\brdrw10\cellx6000\pard \intbl \cell \pard \intbl \row';
				$table .= '\trowd \trql\cellx6000\pard \intbl \cell \pard \intbl \row';

				$stringTitle = $spremenljivke['vsota'];
				$table .= '\trowd \trql\cellx5000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx5800\pard \intbl '.$this->snippet($this->enkaEncode($stringTitle),50,'...').'\~\~\qr\cell \pard \intbl '.$sum.'\qc\cell \pard \intbl \row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);

				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);
			break;

			case 17: //ranking
				$this->rtf->new_line(1);
				$list = array();

				$this->rtf->MyRTF .= "{\par";

				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){

					# po potrebi prevedemo naslov
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}

					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;

					$table .= '\trowd \trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx2300\pard \intbl '.$this->enkaEncode($stringTitle).'   \qc\cell \pard \intbl'.$this->rtf->color(12).' '.$this->enkaEncode($rowAnswers['vrstni_red']).$this->rtf->color(0).'\qc\cell \pard \intbl \row';
				}

				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);
			break;
			case 26:	//lokacija
				$odgovor = "Google Maps";
				$this->rtf->TextCell($odgovor, array('width' => 6500, 'height' => 5, 'border' => array('top','bottom', 'left','right') ) );
				$this->rtf->new_line(1);
			break;
			case 27:	//heatmap
				$odgovor = "Heatmap";
				$this->rtf->TextCell($odgovor, array('width' => 6500, 'height' => 5, 'border' => array('top','bottom', 'left','right') ) );
				$this->rtf->new_line(1);
			break;
		}
	}

	function displayDoubleGrid($spremenljivke) {

		$this->rtf->MyRTF .= "{\\par\\fs22";
		$defw_full = 9500;
		$defw_fc = 1200; // first cell width
		$defw_max = 800; // max other cell width

		$maxcellx = 9500;

		$sqlStVrednosti = sisplet_query("SELECT count(*) AS count FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
		$rowStVrednost = mysqli_fetch_row($sqlStVrednosti);
		$kolon = $rowStVrednost[0]+1;
		$w_oc = ( $defw_full - $defw_fc ) / $kolon;
		if ( $w_oc > $defw_max )
			$w_oc = $defw_max;

		//izpis dveh podnaslovov gridov
		$tableHeader_base = "\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";

		$tableHeader_width = "\\cellx".$defw_fc;
		$tableHeader_title = "\\pard\\intbl\\qc{}\\cell";

		$tableHeader_width .= "\\clbrdrb\\brdrs\\brdrw10\\cellx". ( ($kolon-1)*400 + $defw_fc );
		$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($spremenljivke['grid_subtitle1'])."}\\cell";
		$tableHeader_width .= "\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\cellx". ( ($kolon-1)*800 + $defw_fc );
		$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($spremenljivke['grid_subtitle2'])."}\\cell";

		$tableHeader_finish = "\\pard\\intbl\\row";

		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);


		$tableHeader_base = "\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";
		$tableHeader_width = "\\cellx".$defw_fc;
		$tableHeader_title = "\\pard\\intbl\\qc{}\\cell";
		$tableHeader_finish = "\\pard\\intbl\\row";

		$sqlVsehVrednsti = sisplet_query("SELECT naslov, id, variable, part FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY part, vrstni_red");
		$rowCnt = 0;
		$border = false;
		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			//izris srednjega borderja
			if($border == false && $rowVsehVrednosti['part'] == 2){
				$border = true;
				$leftBorder = '\clbrdrl\brdrs\brdrw10';
			}
			else
				$leftBorder = '';

			$rowCnt++;
			$tableHeader_width .= $leftBorder."\\cellx". ( $rowCnt * 800 +$defw_fc );

			# priredimo naslov če prevajamo anketo
			$naslov = $this->srv_language_grid($spremenljivke['id'], $rowVsehVrednosti['id']);
			if ($naslov != '') {
				$rowVsehVrednosti['naslov'] = $naslov;
			}

			// če ni naslova vzamemo variable
			$stringHeader_title = $this->enkaEncode( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] : $rowVsehVrednosti['variable'] );
			$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
		}
		// izpišemo header celice
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);

		// loopamo skozi vrstice in pripravimo podatke za tabelo z radii
		$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			$i=1;

			# po potrebi prevedemo naslov
			$naslov = $this->srv_language_vrednost($rowVrednost['id']);
			if ($naslov != '') {
				$rowVrednost['naslov'] = $naslov;
			}

			// če ni naslova vzamemo naslov2, če ne pa variable
			$stringCell_title = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );

			// še dodamo textbox če je polj other
			$_txt = '';
			$stringCell_title .= $_txt;

			$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
			$tableHeader_width = "\\cellx".$defw_fc;
			$tableHeader_title = "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title)."\\cf0\\cell";
			$tableHeader_finish = "\\pard\\intbl\\row";

			$border = false;
			$sqlVsehVrednsti = sisplet_query("SELECT id, part FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY part, vrstni_red");
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){

				//izris srednjega borderja
				if($border == false && $rowVsehVrednosti['part'] == 2){
					$border = true;
					$leftBorder = '\clbrdrl\brdrs\brdrw10';
				}
				else
					$leftBorder = '';

				$full = false;

				if($spremenljivke['tip'] == 6){
					$tableHeader_width .= "\clvertalc".$leftBorder."\\cellx". ( $i * 800 +$defw_fc );
					if($full)
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio2.png", "15")."}\\cell";
					else
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio.png", "15")."}\\cell";
				}
				elseif($spremenljivke['tip'] == 16){
					$tableHeader_width .= "\clvertalc".$leftBorder."\\cellx". ( $i * 800 +$defw_fc );
					if($full)
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox2.png", "15")."}\\cell";
					else
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox.png", "15")."}\\cell";
				}
				$i++;
			}
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);
		}
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}

	// Izpis mesanega multigrida
	function displayGridMultiple($spremenljivke){

		$this->rtf->MyRTF .= "{\\par\\fs22";

		$defw_full = 10500;
		if($rowStVrednost[0] < 6){
			$defw_fc = 4300; // first cell width
		}
		else{
			$defw_fc = 2000; // first cell width
		}

		$sqlStVrednosti = sisplet_query("SELECT count(*) FROM srv_grid g, srv_grid_multiple m WHERE m.spr_id=g.spr_id AND m.parent='".$spremenljivke['id']."'");
		$rowStVrednost = mysqli_fetch_array($sqlStVrednosti);

		$kolon = $rowStVrednost['count(*)'] + 1;
		$w_oc = ( $defw_full - $defw_fc ) / $kolon;
		$defw_max = floor($w_oc);

		$tableHeader_base = "\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";
		$tableHeader_width = "\\cellx".$defw_fc;
		$tableHeader_title = "\\pard\\intbl\\qc{}\\cell";
		$tableHeader_finish = "\\pard\\intbl\\row";

		$sqlM = sisplet_query("SELECT * FROM srv_grid_multiple WHERE parent='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$multiple = array();
		while ($rowM = mysqli_fetch_array($sqlM)) {
			$multiple[] = $rowM['spr_id'];
		}

		$sqlVsehVrednsti = sisplet_query("SELECT g.id,g.naslov,g.variable,m.vrstni_red FROM srv_grid g, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND g.spr_id=m.spr_id ORDER BY m.vrstni_red");
		$sqlMultiple = sisplet_query("SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (".implode($multiple, ',').") ORDER BY m.vrstni_red, g.vrstni_red");
		$rowCnt = 0;
		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			$rowCnt++;
			$tableHeader_width .= "\\cellx". ( $rowCnt * $defw_max + $defw_fc );

			# priredimo naslov če prevajamo anketo
			$rowMultiple = mysqli_fetch_array($sqlMultiple);
			$naslov = $this->srv_language_grid($rowMultiple['spr_id'], $rowMultiple['id']);
			if ($naslov != '') {
				$rowVsehVrednosti['naslov'] = $naslov;
			}

			// če ni naslova vzamemo variable
			$stringHeader_title = $this->enkaEncode( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] : $rowVsehVrednosti['variable'] );
			$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
		}
		// izpišemo header celice
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);

		// loopamo skozi vrstice in pripravimo podatke za tabelo z radii
		$row_count = 1;
		$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			$i=1;

			// barva vrstice
			$row_color = ($row_count%2 == 1) ? '\\clcbpat18' : '';

			# po potrebi prevedemo naslov
			$naslov = $this->srv_language_vrednost($rowVrednost['id']);
			if ($naslov != '') {
				$rowVrednost['naslov'] = $naslov;
			}

			// če ni naslova vzamemo naslov2, če ne pa variable
			$stringCell_title = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
			$tableHeader_width = $row_color."\\cellx".$defw_fc;
			$tableHeader_title = "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title)."\\cf0\\cell";
			$tableHeader_finish = "\\pard\\intbl\\row";

			$sqlVsehVrednsti = sisplet_query("SELECT g.id AS id, s.tip AS tip, m.vrstni_red AS vrstni_red FROM srv_grid g, srv_spremenljivka s, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id AND s.id=g.spr_id ORDER BY m.vrstni_red");
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){

				$full = false;

				if($rowVsehVrednosti['tip'] == 6){
					$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
					if($full)
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio2.png", "15")."}\\cell";
					else
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio.png", "15")."}\\cell";
				}
				elseif($rowVsehVrednosti['tip'] == 16){
					$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
					if($full)
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox2.png", "15")."}\\cell";
					else
						$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox.png", "15")."}\\cell";
				}
				else{
					$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
					$tableHeader_width .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
					$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->color(0).'\qc{}\\cell';
				}
				$i++;
			}
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);

			$row_count++;
		}
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}


	function createFrontPage(){
		global $lang;

		if ($this->language != -1) {
			SurveySetting::getInstance()->Init($this->anketa['id']);
			$_lang = '_'.$this->language;
			$srv_anketa_naslov = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_novaanketa_kratkoime'.$_lang);
		}
		else{
			$srv_anketa_naslov = SurveyInfo::getInstance()->getSurveyAkronim();
		}

		$this->rtf->new_line(10);

		if($this->allResults == 1){
			$this->rtf->TextCell($this->rtf->bold(1).$this->enkaEncode($srv_anketa_naslov).$this->rtf->bold(0).'\\line\n '.$lang['export_firstpage_results'], array('width' => 9500, 'height' => 3,
			'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
			'colorF' => "0", 'colorB' => "0" ) );
		}
		elseif($this->allResults == 2){
			$this->rtf->TextCell($this->rtf->bold(1).$this->enkaEncode($srv_anketa_naslov).$this->rtf->bold(0).'\\line\n '.$lang['srv_testiranje_komentarji'], array('width' => 9500, 'height' => 3,
			'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
			'colorF' => "0", 'colorB' => "0" ) );
		}
		else{
			$this->rtf->TextCell($this->enkaEncode($srv_anketa_naslov), array('width' => 9500, 'height' => 3,
			'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
			'colorF' => "0", 'colorB' => "0" ) );
		}

		$this->rtf->new_line(3);
		// dodamo info:
		$this->rtf->TextCell("", array('width' => 9500, 'height' => 1,
		 'align' => 'left', 'valign' => 'bottom' , 'border' => array('bottom'),'colorF' => "0" ) );

		$infoTable = array();
		array_push( $infoTable, array( $lang['export_firstpage_shortname'].': '.$this->enkaEncode(SurveyInfo::getInstance()->getSurveyTitle()), "" ) );
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim() )
			array_push( $infoTable, array( $lang['export_firstpage_longname'].': '.$this->enkaEncode($srv_anketa_naslov), "" ) );
		array_push( $infoTable, array( $lang['export_firstpage_qcount'].': '.SurveyInfo::getInstance()->getSurveyQuestionCount(), "" ) );

		// Aktiviranost
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			array_push( $infoTable, array( $this->rtf->color(11).$this->enkaEncode($lang['srv_anketa_active2']).$this->rtf->color(0), "") );
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				array_push( $infoTable, array( $this->rtf->color(17).$this->enkaEncode($lang['srv_survey_non_active_notActivated']).$this->rtf->color(0), "") );
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				array_push( $infoTable, array( $this->rtf->color(17).$this->enkaEncode($lang['srv_survey_non_active']).$this->rtf->color(0), "") );
			}
		}

		// Aktivnost
		if( count($activity) > 0 ){
			array_push( $infoTable, array( $lang['export_firstpage_active_from'].': '.SurveyInfo::getInstance()->getSurveyStartsDate(), $lang['export_firstpage_active_until'].': '.SurveyInfo::getInstance()->getSurveyExpireDate() ) );
		}

		array_push( $infoTable, array( $lang['export_firstpage_author'].': '.SurveyInfo::getInstance()->getSurveyInsertName(), $lang['export_firstpage_edit'].': '.SurveyInfo::getInstance()->getSurveyEditName() ) );
		array_push( $infoTable, array( $lang['export_firstpage_date'].': '.SurveyInfo::getInstance()->getSurveyInsertDate(), $lang['export_firstpage_date'].': '.SurveyInfo::getInstance()->getSurveyEditDate() ) );
		array_push( $infoTable, array( $lang['export_firstpage_desc'].': '.SurveyInfo::getInstance()->getSurveyInfo(), "" ) );
		$this->rtf->TableFromArray( array( 4750, 4750 ), $infoTable, array('spacer' => 0));

		if($this->allResults != 1)
			$this->rtf->new_page();
	}

	function enkaEncode($text)
	{
		global $site_url;

		// preverimo text za img tage in jih zamenjamo z ustrezno sliko
		$pattern = '/<img[^>]+src[\\s=\'"]';
		$pattern .= '+([^"\'>\\s]+)/is';
		if(preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)){

			// relativna pot
			if(substr($match[1][0], 0, 1) == '/')
				$image = $site_url.$match[1][0];
			else
				$image = $match[1][0];

			$file = @file_get_contents($image);


			// Resizamo sliko na pravo velikost
			$imgSize = getimagesize($image);
			$origHeight = $imgSize[1];
			$origWidth = $imgSize[0];

			// Dobimo nastavljeno visino slike
			$pattern = '/<img[^>]+height[\\s=\'"]';
			$pattern .= '+([^"\'>\\s]+)/is';
			preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE);
			$height = round($match[1][0] / $origHeight) * 100;

			// Dobimo nastavljeno sirino slike
			$pattern = '/<img[^>]+width[\\s=\'"]';
			$pattern .= '+([^"\'>\\s]+)/is';
			preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE);
			$width = round($match[1][0] / $origWidth) * 100;


			$result .= "{";
			$result .= "\\pict\\jpegblip\\picscalex".$width."\\picscaley".$height."\\bliptag132000428 ";
			$result .= trim(bin2hex($file));
			$result .= "\n}\n";

			$text = preg_replace("/<img[^>]+\>/i", $result, $text);
		}

		// popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');

		$transliterationTable = array(
			'à' => 'a',
			'À' => 'A',
            'è' => 'e',
            'È' => 'E',
            'ì' => 'i',
            'Ì' => 'I',
            'ò' => 'o',
            'Ò' => 'O',
            'ù' => 'u',
            'Ù' => 'U',
            'ø' => 'o',
            'Ø' => 'O',
            'å' => 'a',
            'Å' => 'A',
            'Æ' => 'AE',
            'æ' => 'ae'
        );
		$text = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $text);

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

	function setGrupa($grupa) {$this->grupa = $grupa;}
	function getGrupa() {return $this->grupa;}
	function setUserId($usrId) {$this->usrId = $usrId;}
	function getUserId() {return ($this->usrId)?$this->usrId:false;}
	function setDisplayFrontPage($display) {$this->pi['displayFrontPage'] = $display;}
	function getDisplayFrontPage() {return ($this->pi['displayFrontPage'] == true || $this->pi['displayFrontPage'] == 1);}

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

	// preverja ce je v stringu img in vrne razbit niz ce je
	function checkImage($text)
	{
		$textArray = array();

		$pattern = '/<img[^>]+src[\\s=\'"]';
		$pattern .= '+([^"\'>\\s]+)/is';

		if(preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)){
			$textArray['image'] = $match[1][0];

			$text = preg_replace("/<img[^>]+\>/i", "", $text);
			$textArray['text1'] = substr($text, 0, $match[0][1]);
			$textArray['text2'] = substr($text, $match[0][1]);
		}

		return $textArray;
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

		$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode($output).$this->rtf->bold(0));
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

			if ($rowl['naslov'] != '') 
				return strip_tags($rowl['naslov']);
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
	 
}


?>
