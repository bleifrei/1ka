<?php
/*
 * Created on 28.2.2009
 *
 */
require("class.enka.rtf.php");

require_once ('../../vendor/autoload.php');
require_once ('../survey/definition.php');


define("FNT_TIMES", "Times New Roman", true);
define("FNT_ARIAL", "Arial", true);

define("FNT_MAIN_TEXT", FNT_TIMES, true);
define("FNT_QUESTION_TEXT", FNT_TIMES, true);
define("FNT_HEADER_TEXT", FNT_TIMES, true);

define("FNT_MAIN_SIZE", 12, true);
define("FNT_QUESTION_SIZE", 10, true);
define("FNT_HEADER_SIZE", 10, true);


class RtfIzvozResults {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;					// trenutna grupa

	var $spremenljivka;					// trenutna spremenljivka
	var $printPreview = false;			// ali kliče konstruktor
	var $pi=array('canCreate'=>false);	// za shrambo parametrov in sporocil
	var $rtf;

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
			
			// create new RTF document
			$orientation = ($this->landscape == 1) ? true : false;
			$this->rtf = new enka_RTF($orientation);
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



	function init()
	{
		global $lang;
		
		$orientation = ($this->landscape == 1) ? true : false; 
		
		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();
		
		if($this->getUserId() && $this->showRecnum == 1){
			$sqlu = sisplet_query("SELECT * FROM srv_user WHERE id = '".$this->getUserId()."'");
			$rowu = mysqli_fetch_array($sqlu);
		
			$rightTitle = SurveyInfo::getInstance()->getSurveyAkronim().' (recnum '.$rowu['recnum'].') ';			
			$this->rtf->WriteHeader($this->enkaEncode($rightTitle), 'right', $orientation);
		}
		else
			$this->rtf->WriteHeader($this->enkaEncode(SurveyInfo::getInstance()->getSurveyAkronim()), 'right', $orientation);
		
		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right', $orientation);
		$this->rtf->set_default_font(FNT_TIMES, $this->font);
		return true;
	}

	function createRtf()
	{
		// Izpis vseh odgovorov (vsi respondenti -> max 300)
		if(!$this->getUserId())	
			$this->outputAllResults();	

		// Izpis vprasalnika oz odgovorov enega respondenta
		else
			$this->outputSurvey();
	}

	// Izpis vprasalnika z odgovori
	function outputSurvey(){
		global $lang;
		
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
			
		// izpišemo prvo stran
		if (false)
			$this->createFrontPage();

		
		// Izpisemo vprasalnik
		
		// ce obstaja intro izpisemo intro - pri izpisu vprasalnika brez odgovorov (ce smo na prvi strani moramo biti v razsirjenem nacinu)
		/*if(($rowA['expanded'] == 1 && $this->allResults == 3) || (!$this->getUserId() && $this->allResults == 0)){
			if ( SurveyInfo::getInstance()->getSurveyShowIntro() )
			{ 		
				$intro = (SurveyInfo::getInstance()->getSurveyIntro() == '') ? $lang['srv_intro'] : SurveyInfo::getInstance()->getSurveyIntro();

				// ce obstaja intro izpisemo intro
				$this->rtf->add_text($intro);
				$this->rtf->new_line(3);
			}
		}*/
		
		// filtriramo spremenljivke glede na profil	- SAMO CE NE PRIHAJAMO IZ MAILA!
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
		
			
		if ( $this->getGrupa() )
		{
			$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' AND id = '".$this->getGrupa()."' ORDER BY vrstni_red";
		}
		else
		{
			$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa['id']."' ORDER BY vrstni_red";
		}
		$sqlGrupe = sisplet_query($sqlGrupeString);

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
					$this->rtf->add_text($this->rtf->color(9).$this->rtf->bold(1).$this->enkaEncode($this->dataPiping($loop_title)).$this->rtf->bold(0).$this->rtf->color(0));				
					$this->rtf->new_line(1);
					
					$sqlSpremenljivke = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
					while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke))
					{ // sprehodimo se skozi spremenljivke grupe
						$spremenljivka = $rowSpremenljivke['id'];
						if ( $this->checkSpremenljivka ($spremenljivka) /*|| $this->showIf == 1*/ )
						{ // lahko izrišemo spremenljivke
							
							//nastavimo velikost pisave
							$this->rtf->MyRTF .= $this->rtf->_font_size($this->font * 2);
							
							// izpis vprasalnika z rezultati

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
								
								// NAVADEN IZPIS rezultatov spremenljivke - kratek samo pri radio, checkbox, multiradio, multicheckbox, besedilo	
								if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16)) ){
									if($rowSpremenljivke['tip'] > 3)
										$this->outputVprasanje($rowSpremenljivke);									
										
									$this->outputSpremenljivkeValues($rowSpremenljivke);						
								}
								
								// KRATEK IZPIS rezultatov spremenljivke
								elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
									$this->outputSpremenljivkeValues($rowSpremenljivke);
								}	
								
								// DOLG IZPIS rezultatov
								else{
									$this->outputVprasanje($rowSpremenljivke);
									$this->outputSpremenljivke($rowSpremenljivke);	
								}
								
								$this->rtf->new_line(1);
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

						//nastavimo velikost pisave
						$this->rtf->MyRTF .= $this->rtf->_font_size($this->font * 2);
					
						// izpis vprasalnika z rezultati

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
							
							// NAVADEN IZPIS rezultatov spremenljivke - kratek samo pri radio, checkbox, multiradio, multicheckbox, besedilo	
							if( $this->type == 0 && in_array($rowSpremenljivke['tip'], array(1,2,3,6,16)) ){
								if($rowSpremenljivke['tip'] > 3)
									$this->outputVprasanje($rowSpremenljivke);									
									
								$this->outputSpremenljivkeValues($rowSpremenljivke);						
							}
							
							// KRATEK IZPIS rezultatov spremenljivke
							elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
								$this->outputSpremenljivkeValues($rowSpremenljivke);
							}	
							
							// DOLG IZPIS rezultatov
							else{
								$this->outputVprasanje($rowSpremenljivke);
								$this->outputSpremenljivke($rowSpremenljivke);	
							}
							
							$this->rtf->new_line(1);
						}
					}
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
	
	// Izpis vseh userjev, ki so odgovorili
	function outputAllResults(){
		global $lang;
		
		//loop cez vse userje, ki so odgovorili
		//$izbranStatusProfile = SurveyUserSetting :: getInstance()->getSettings('default_status_profile_export');	
		$izbranStatusProfile = SurveyStatusProfiles :: getStatusAsQueryString();
		
		$sqlu = sisplet_query("SELECT * FROM srv_user WHERE ank_id = '".$this->anketa['id']."' ".$izbranStatusProfile." AND deleted='0' AND preview='0' ORDER BY recnum");			
		
		//ce imamo vec kot 300 anketirancev ne izpisemo
		$count = mysqli_num_rows($sqlu);
		if( $count > 300 ){
				
			$this->rtf->set_font_size(14);
			$this->rtf->add_text($this->rtf->bold(1).'NAPAKA!'.$this->rtf->bold(0));
			$this->rtf->new_line(2);
			$this->rtf->add_text($this->rtf->bold(1).'Izpis ni možen zaradi prevelikega števila odgovorov ('.$count.')'.$this->rtf->bold(0));
		}		
		else{
			// izpišemo prvo stran
			$this->createFrontPage();
			if($this->pageBreak == 0)
				$this->rtf->new_page();
		
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
					$this->rtf->new_page();
				
				$this->rtf->set_font_size(14);

				if($this->showRecnum == 1)
					$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode('Recnum '.$rowu['recnum'].' (status '.$rowu['last_status'].' - '.$status.')').$this->rtf->bold(0));
				else
					$this->rtf->add_text($this->rtf->bold(1).$this->enkaEncode('Status '.$rowu['last_status'].' - '.$status).$this->rtf->bold(0));
				
				$this->rtf->new_line(2);
				
				//izpis posameznega userja
				$this->usr_id = $rowu['id'];
				$this->outputUser();
			}
		}
	}
	
	
	function outputVprasanje($spremenljivke)
	{
		//nastavimo velikost pisave
		$this->rtf->MyRTF .= $this->rtf->_font_size($this->font * 2);
	
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
		
		$this->rtf->add_text($numberingText . $this->enkaEncode($this->dataPiping($spremenljivke['naslov'])));
		
		if($spremenljivke['tip'] != 5)
			$this->rtf->new_line(1);
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
				
				// če imamo vnose, pogledamo kaj je odgovoril uporabnik
				if ($this->getUserId()) {
					$sqlUserAnswer = sisplet_query("SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spremenljivke[id]' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
					while ($rowAnswers = mysqli_fetch_assoc($sqlUserAnswer))
					$userAnswer[$rowAnswers['vre_id']] = $rowAnswers['vre_id'];
				}
				
				$this->rtf->new_line(1);
				$list = array();
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
					
					$width = round(10000*$expand_width / $stolpci);
					
					$this->rtf->MyRTF .= "{\par";			
					$tableHeader = '\trowd\trql\trrh400';
				}				
								
				$count = 0;
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{				
					//popravimo lokacijo ce imamo postavitev v vec stolpcih
					if ( ($stolpci > 1) && ($spremenljivke['orientation']==1) && ($count % $v_stolpcu == 0) ) {			
						
						$yPos = floor($count / $v_stolpcu) + 1;
						
						$table .= '\clvertalc\cellx'.( $yPos * $width );
						$tableEnd .= '\pard\intbl ';			
					}
					
					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;
					
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
						$tableEnd .= '\qc\cell';			
					}
				}
				
				if ($stolpci > 1 && $spremenljivke['orientation']==1) {
					$tableEnd .= '\pard\intbl\row';				
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					
					$this->rtf->MyRTF .= "}";
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
			
				$this->rtf->MyRTF .= "{\\par\\fs22";

                $sqlStVrednosti = sisplet_query("SELECT count(*) AS count FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
                $rowStVrednost = mysqli_fetch_row($sqlStVrednosti);
				
				$defw_full = 10500*$expand_width;
				if($rowStVrednost[0] < 6 && ($spremenljivke['tip'] != 6 || $spremenljivke['enota'] != 1)){              			
					$defw_fc = 4300*$expand_width; // first cell width
				}
				else{              
					$defw_fc = 2000*$expand_width; // first cell width
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
                	// če ni naslova vzamemo variable
                	$stringHeader_title = $this->enkaEncode( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] : $rowVsehVrednosti['variable'] );
                	$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
                }
				// izpišemo header celice
                $this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);

                // loopamo skozi vrstice in pripravimo podatke za tabelo z radii
				$row_count = 1;
				$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
                {
					$skipRow = false;
					
					// barva vrstice
					$row_color = ($row_count%2 == 1) ? '\\clcbpat18' : '';	
					
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
						$i=1;
						// če ni naslova vzamemo naslov2, če ne pa variable
						$stringCell_title = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
						$tableHeader_width = $row_color."\\cellx".$defw_fc;
						$tableHeader_title = "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title)."\\cf0\\cell";
						$tableHeader_finish = "\\pard\\intbl\\row";

						$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
						while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
								
							// poiščemo kaj je odgovoril uporabnik:
							if($spremenljivke['tip'] == 6)
								$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
							elseif($spremenljivke['tip'] == 16)
								$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
							else
								$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
								
							$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
							
							$full = ($rowVsehVrednosti['id'] == $userAnswer['grd_id']) ? true : false;
							
							if($spremenljivke['tip'] == 6){
								$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
								if($full)
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio2.png", "15")."}\\cell";
								else
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio.png", "15")."}\\cell";
							}
							elseif($spremenljivke['tip'] == 16){
								$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
								if($full)
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox2.png", "15")."}\\cell";
								else
									$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox.png", "15")."}\\cell";
							}
							else{	
								$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
								$tableHeader_width .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
								$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc{}\\cell';
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
                }
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);
			break;
			
			case 24: // mesan multigrid
				$this->displayGridMultiple($spremenljivke);
			break;

			case 4: //text
				$this->rtf->TextCell("", array('width' => 9500*$expand_width, 'height' => 3, 'border' => array('top','bottom', 'left','right') ) );
				$this->rtf->new_line(1);
			break;
			
			case 21: //besedilo*
				$this->rtf->new_line(1);
				$list = array();
				
				$this->rtf->MyRTF .= "{\par";
				
				$defw_full = 9500*$expand_width;
                $defw_part = round($defw_full / $spremenljivke['text_kosov']);
				
				$tableHeader = '\trowd\trql\trrh800';
				$podnapisi = '\trowd\trql';
				
				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				for($i=0; $i<$spremenljivke['text_kosov']; $i++){
				
					$rowVrednost = mysqli_fetch_array($sqlVrednosti);
					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;
					
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
						// imamo signature vprašanje
						elseif($spremenljivke['signature'] == 1){
							$answer = $userAnswer['text'];
							
							// relativna pot
							$image = $site_url.'main/survey/uploads/'.$this->getUserId().'_'.$spremenljivke['id'].'_'.$this->anketa['id'].'.png';
								
							$file = @file_get_contents($image);
							
							$answer .= "{";
							$answer .= "\\pict\\jpegblip\\picscalex100\\picscaley100\\bliptag132000428 ";
							$answer .= trim(bin2hex($file));
							$answer .= "\n}\n";							
						}
						else{
							$answer = $userAnswer['text'];
						}
					}
					
					if($spremenljivke['text_orientation'] == 1){
						$table .= '\cellx'.( $i * $defw_part + 1000*$expand_width).'\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( ($i+1) * $defw_part);
						$tableEnd .= '\pard\intbl '.$this->enkaEncode($stringTitle).'\qc\cell\pard\intbl'.$this->rtf->color(12).' '.$answer.$this->rtf->color(0).'\cell';
					}
					else{
						$table .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( ($i+1) * $defw_part );	
						$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$answer.$this->rtf->color(0).'\cell';
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
				
			break;
			
			case 5: //label
				$this->rtf->new_line(2);
			break;
			
			case 7: //number
				$this->rtf->new_line(1);
				$list = array();
				
				$this->rtf->MyRTF .= "{\par";
				
				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                $rowVrednost = mysqli_fetch_array($sqlVrednosti);
				$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
				$list[] = $stringTitle;
					
				$userAnswerString = "SELECT text, text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
				$sqlUserAnswer = sisplet_query($userAnswerString);
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								
				if($spremenljivke['size'] == 1) {
					if ($spremenljivke['enota'] == 1) { 
						#enota na levi
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000'
						.'\qc\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0)
						.'\qc\cell\pard\intbl\row';
					} elseif ($spremenljivke['enota'] == 2) {
						#enota na desni
						$table = '\trowd\trql\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx1500\cellx3000';
						$table .= '\qc\pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell\pard\intbl\row';
					} else {
						#brez enote
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000'
						.'\qc\pard\intbl '.
						'\qc\cell\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0)
						.'\qc\cell\pard\intbl\row';
					}
				} else{
					$rowVrednost = mysqli_fetch_array($sqlVrednosti);
					$stringTitle2 = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );

					
					if ($spremenljivke['enota'] == 1) {
						#enota na levi
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000';
						$table .= '\qc\pard \intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle2),20,'...') )
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text2']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl \row';										
					} else if ($spremenljivke['enota'] == 2) {
						#enota na desni
						$table = '\trowd\trql\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx1500\cellx3000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx4500\cellx6000';
						$table .= '\qc\pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') )
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text2']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl '.($this->snippet($this->enkaEncode($stringTitle2),20,'...') )
						.'\qc\cell \pard \intbl \row';										
						
					} else {
						#brez eneote
						$table = '\trowd\trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000';
						$table .= '\qc\pard \intbl '
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl '
						.'\qc\cell \pard \intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text2']).$this->rtf->color(0)
						.'\qc\cell \pard \intbl \row';										
						
					}
				}
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);
				
			break;
			
			case 8: //datum
				if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				}
				$this->rtf->MyRTF .= $this->rtf->color(12);
				$this->rtf->TextCell($this->enkaEncode($userAnswer['text']), array('width' => 2000*$expand_width, 'height' => 1, 'border' => array('top','bottom', 'left','right') ) );
				$this->rtf->MyRTF .= $this->rtf->color(0);
				$this->rtf->new_line(1);
			break;
			
			case 18: //vsota		
				$this->rtf->new_line(1);
				$list = array();
				
				$this->rtf->MyRTF .= "{\par";
				
				// iz baze preberemo vse moznosti
				$sum = 0;
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{			
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{			
						$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}
					
					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;
					
					$table .= '\trowd\trql\cellx'. 5000*$expand_width .'\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'. 5800*$expand_width .'\pard\intbl '.$this->snippet($this->enkaEncode($stringTitle),50,'...').'\~\~\qr\cell\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($rowAnswers['text']).$this->rtf->color(0).'\qc\cell\pard\intbl\row';
				
					$sum += (int)$rowAnswers['text'];
				}
				
				$table .= '\trowd \trql\clbrdrb\brdrs\brdrw10\cellx'. 6000*$expand_width .'\pard \intbl \cell \pard \intbl \row';
				$table .= '\trowd \trql\cellx'. 6000*$expand_width .'\pard \intbl \cell \pard \intbl \row';

				$stringTitle = $spremenljivke['vsota'];
				$table .= '\trowd \trql\cellx'. 5000*$expand_width .'\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'. 5800*$expand_width .'\pard \intbl '.$this->snippet($this->enkaEncode($stringTitle),50,'...').'\~\~\qr\cell \pard \intbl '.$sum.'\qc\cell \pard \intbl \row';
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
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{	
						$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}
				
					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;
					
					$table .= '\trowd \trql\cellx'. 1500*$expand_width .'\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'. 2300*$expand_width .'\pard \intbl '.$this->enkaEncode($stringTitle).'   \qc\cell \pard \intbl'.$this->rtf->color(12).' '.$this->enkaEncode($rowAnswers['vrstni_red']).$this->rtf->color(0).'\qc\cell \pard \intbl \row';					
				}
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);
				$this->rtf->MyRTF .= "}";
				$this->rtf->new_line(1);
			break;
			
			case 9: //generator imen
				if ( $this->getUserId() ){
					$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");	
					while($userAnswer = mysqli_fetch_array($sqlUserAnswer)){
						$this->rtf->MyRTF .= $this->rtf->color(12);
						$this->rtf->TextCell($this->enkaEncode($userAnswer['text']), array('width' => 4000, 'height' => 1, 'border' => array('top','bottom', 'left','right') ) );
						$this->rtf->MyRTF .= $this->rtf->color(0);
					}
				}
				$this->rtf->new_line(1);
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
					$imageFinal = $this->rtf->prepareHeatmapImage($data4Coords, $backgroundImg, $lat, $lng, $ImgWidth, $ImgHeight, $heatmap_click_size, $heatmap_click_color, $heatmap_click_shape, $spr_id, $bgImageType, UPLOAD_DIR);
				//}			
				#izris tock na sliko######################################################################################konec
				
				#izris slike v rtf######################################################################################
				$file = @file_get_contents($imageFinal);			
				$image = "{";
				$image .= "\\pict\\jpegblip\\picscalex100\\picscaley100\\bliptag132000428 ";
				$image .= trim(bin2hex($file));
				$image .= "\n}\n";
				$this->rtf->MyRTF .= $image;
				//izris slike v rtf######################################################################################konec
				
				//izbris slike iz mape streznika
				//if($heatmap_show_clicks == 1){
					unlink($imageFinal);
				//}
				
				#izris stevca klikov######################################################################################
 				if($heatmap_show_counter_clicks){
					$this->rtf->new_line(1);
					$clickCounter = $this->rtf->enkaEncode($lang['srv_vprasanje_heatmap_num_clicks']).': \line';		
					$clickCounter .= ' '.mysqli_num_rows($data4Coords).'/'.$heatmap_num_clicks;					
					$this->rtf->MyRTF .= $clickCounter;
					$this->rtf->new_line(1);
				}
				#izris stevca klikov######################################################################################konec		
			break;
		}
	}
	
	function outputSpremenljivkeValues($spremenljivke){
		global $site_url;
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
	
		// stevilcenje vprasanj
		$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';
	
		// Ce je spremenljivka v loopu
		$loop_id = $this->loop_id == null ? " IS NULL" : " = '".$this->loop_id."'";
	
		switch ( $spremenljivke['tip'] )
		{
			case 1: //radio
			case 2: //check
			case 3: //select -> radio

				$this->rtf->MyRTF .= "{\par";
				$tableHeader = '\trowd\trql\trrh400';
				
				//IZPIS NASLOVA VPRASANJA
				$table = '\cellx'.( 5000*$expand_width );	
				$tableEnd = '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])). ' \ql\cell';
				
				
				// če imamo vnose, pogledamo kaj je odgovoril uporabnik
				if ($this->getUserId())
				{
					$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spremenljivke[id]' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					while ($rowAnswers = mysqli_fetch_assoc($sqlUserAnswer))
					$userAnswer[$rowAnswers['vre_id']] = $rowAnswers['vre_id'];
				}
		
				// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta				
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				
				$resultString = '';
				
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					//izpisemo samo izbrane vrednosti
					if( isset($userAnswer[$rowVrednost['id']]) ){
														
						$stringTitle = ($this->enkaEncode(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
						
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
				
				$table .= '\cellx'.( 10000*$expand_width );	
				$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($resultString).' '.$this->rtf->color(0).'\ql\cell';
							
				$tableEnd .= '\pard\intbl\row';
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
			
				$this->rtf->MyRTF .= "}";
								
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
				
				$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                $this->rtf->MyRTF .= "{\par";
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
						$stringCell_title = $this->enkaEncode(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
						
						// še dodamo textbox če je polj other
						$_txt = '';
						if ( $rowVrednost['other'] == 1 && $this->getUserId() )
						{
							$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
							$row4 = mysqli_fetch_assoc($sqlOtherText);
							$_txt = ' '.$row4['text'];
						}										
						$stringCell_title .= $_txt.':';

						$tableHeader = '\trowd\trql\trrh400';

						/*Izpis bočnega stolpca*/				
						$table = '\cellx'.( 5000*$expand_width );	
						$tableEnd = '\pard\intbl '.$this->enkaEncode($stringCell_title). '\qr\cell';
						
						$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY 'vrstni_red'");
						
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
					
							if($rowVsehVrednosti['id'] == $userAnswer['grd_id'])						
								$resultString .= ' '.$this->enkaEncode($rowVsehVrednosti['naslov']).',';
						}
						$resultString = substr($resultString, 0, -1);
						
						$table .= '\cellx'.( 10000*$expand_width );	
						$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($resultString).' '.$this->rtf->color(0).'\ql\cell';
									
						$tableEnd .= '\pard\intbl\row';
						
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					}
                }
				$this->rtf->MyRTF .= "}";

			break;
			
			case 21: //besedilo*
				$list = array();
				
				$this->rtf->MyRTF .= "{\par";
				
				$defw_full = 7500*$expand_width;
                $defw_part = round($defw_full / $spremenljivke['text_kosov']);
				
				$tableHeader = '\trowd\trql\trrh800';
				$podnapisi = '\trowd\trql';
				
				$table .= '\cellx'.(2000*$expand_width);
				$tableEnd .= '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])). ' \ql\cell';
				
				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				for($i=0; $i<$spremenljivke['text_kosov']; $i++){
				
					$rowVrednost = mysqli_fetch_array($sqlVrednosti);
					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;
					
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
						// imamo signature vprašanje
						elseif($spremenljivke['signature'] == 1){ 
							$answer = $userAnswer['text'];
							
							// relativna pot
							$image = $site_url.'main/survey/uploads/'.$this->getUserId().'_'.$spremenljivke['id'].'_'.$this->anketa['id'].'.png';
								
							$file = @file_get_contents($image);
							
							$answer .= "{";
							$answer .= "\\pict\\jpegblip\\picscalex100\\picscaley100\\bliptag132000428 ";
							$answer .= trim(bin2hex($file));
							$answer .= "\n}\n";							
						}
						else{
							$answer = $userAnswer['text'];
						}
					}
					
					if($spremenljivke['text_orientation'] == 1){
						$table .= '\cellx'.( $i * $defw_part + ((1000 + 2000)*$expand_width)).'\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( ($i+1) * $defw_part + 2000*$expand_width);
						$tableEnd .= '\pard\intbl '.$this->enkaEncode($stringTitle).'\qc\cell\pard\intbl'.$this->rtf->color(12).' '.$answer.$this->rtf->color(0).'\cell';
					}
					else{
						$table .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( ($i+1) * $defw_part + 2000*$expand_width);	
						$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$answer.$this->rtf->color(0).'\cell';
					}
					
					if($spremenljivke['text_orientation'] == 2){
						$podnapisi .= '\cellx'.( ($i+1) * $defw_part + 2000*$expand_width).'';
						$podnapisiEnd .= '\pard\intbl '.$this->enkaEncode($stringTitle).'\qc\cell';
					}
				}
				$tableEnd .= '\pard\intbl\row';
				$podnapisiEnd .= '\pard\intbl\row';
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd.($spremenljivke['text_orientation'] == 2 ? $podnapisi.$podnapisiEnd : ''));
				$this->rtf->MyRTF .= "}";
				
			break;
			
			case 7: //number
				$list = array();
				
				$this->rtf->MyRTF .= "{\par";
				
				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                $rowVrednost = mysqli_fetch_array($sqlVrednosti);
				$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
				$list[] = $stringTitle;
					
				$userAnswerString = "SELECT text, text2 FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
				$sqlUserAnswer = sisplet_query($userAnswerString);
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
							
				if($spremenljivke['size'] == 1) {
					if ($spremenljivke['enota'] == 1) { 
						#enota na levi
						$table = '\trowd\trql\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000'
						.'\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])).'\ql\cell'
						.'\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') ).'\qc\cell'
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl\row';
					} elseif ($spremenljivke['enota'] == 2) {
						#enota na desni
						$table = '\trowd\trql\cellx3000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx4500\cellx6000';
						$table .= '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])).'\ql\cell'
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') ).'\qc\cell'
						.'\pard\intbl\row';
					} else {
						#brez enote
						$table = '\trowd\trql\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000'
						.'\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])).'\ql\cell'
						.'\pard\intbl '
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl\row';
					}
				} else{
					$rowVrednost = mysqli_fetch_array($sqlVrednosti);
					$stringTitle2 = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );

					
					if ($spremenljivke['enota'] == 1) {
						#enota na levi
						$table = '\trowd\trql\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000\cellx7500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx9000';
						$table .= '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])).'\ql\cell'
						.'\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') ).'\qc\cell'
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle2),20,'...') ).'\qc\cell'
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text2']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl \row';
					} else if ($spremenljivke['enota'] == 2) {
						#enota na desni
						$table = '\trowd\trql\cellx3000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx4500\cellx7000\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6500\cellx9000';
						$table .= '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])).'\ql\cell'
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle),20,'...') ).'\qc\cell'
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text2']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl '.($this->snippet($this->enkaEncode($stringTitle2),20,'...') ).'\qc\cell'
						.'\pard\intbl\row';										
						
					} else {
						#brez eneote
						$table = '\trowd\trql\cellx3000\cellx4500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx6000\cellx7500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx9000';
						$table .= '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])).'\ql\cell'
						.'\pard\intbl '
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl '
						.'\pard\intbl '.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text2']).$this->rtf->color(0).'\qc\cell'
						.'\pard\intbl\row';										
						
					}
				}
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);
				$this->rtf->MyRTF .= "}";
				
			break;
			
			case 8: //datum
				if ( $this->getUserId() )
				{
					$userAnswerString = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id;";
					$sqlUserAnswer = sisplet_query($userAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				}
				
				$this->rtf->MyRTF .= "{\par";
				$tableHeader = '\trowd\trql\trrh400';
				
				//IZPIS NASLOVA VPRASANJA
				$table = '\cellx'.( 5000*$expand_width );	
				$tableEnd = '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])). ' \ql\cell';
				
				$table .= '\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( 7000*$expand_width );	
				$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($userAnswer['text']).' '.$this->rtf->color(0).'\ql\cell';
							
				$tableEnd .= '\pard\intbl\row';
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
			
				$this->rtf->MyRTF .= "}";

			break;
			
			case 18: //vsota		
				$this->rtf->new_line(1);
				$list = array();
				
				// Izpisemo tekst vprasanja
				$this->rtf->add_text($numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])));
				$this->rtf->new_line(1);
				
				$this->rtf->MyRTF .= "{\par";
				
				// iz baze preberemo vse moznosti
				$sum = 0;
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{			
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{			
						$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
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
			break;
			
			case 17: //ranking
				$this->rtf->new_line(1);
				$list = array();
				
				// Izpisemo tekst vprasanja
				$this->rtf->add_text($numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])));
				$this->rtf->new_line(1);
				
				$this->rtf->MyRTF .= "{\par";
				
				// iz baze preberemo vse moznosti
                $sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
                while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
				{
					// če imamo vnose, pogledamo kaj je odgovoril uporabnik
					if ($this->getUserId())
					{	
						$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
						$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
					}
				
					$stringTitle = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
					$list[] = $stringTitle;
					
					$table .= '\trowd \trql\cellx1500\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx2300\pard \intbl '.$this->enkaEncode($stringTitle).'   \qc\cell \pard \intbl'.$this->rtf->color(12).' '.$this->enkaEncode($rowAnswers['vrstni_red']).$this->rtf->color(0).'\qc\cell \pard \intbl \row';					
				}
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($table);
				$this->rtf->MyRTF .= "}";
			break;
			
			case 9: //generator imen
				
				$this->rtf->MyRTF .= "{\par";
				$tableHeader = '\trowd\trql\trrh400';
				
				//IZPIS NASLOVA VPRASANJA
				$table = '\cellx'.( 5000*$expand_width );	
				$tableEnd = '\pard\intbl '.$numberingText.$this->enkaEncode($this->dataPiping($spremenljivke['naslov'])). ' \ql\cell';
				
				$answer = '';
				if ( $this->getUserId() ){			
					$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");	
					while($userAnswer = mysqli_fetch_array($sqlUserAnswer)){					
						$answer .= $this->enkaEncode($userAnswer['text']).', ';			
					}
					$answer = substr($answer, 0, -2);
				}
				
				$table .= '\cellx'.( 10000*$expand_width );	
				$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($answer).' '.$this->rtf->color(0).'\ql\cell';
							
				$tableEnd .= '\pard\intbl\row';
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
			
				$this->rtf->MyRTF .= "}";
			break;
		}
	}

	function displayDoubleGridValues($spremenljivke) {
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		//prvi del grida
		//naslov 1. grida
		$this->rtf->TextCell($this->enkaEncode($spremenljivke['grid_subtitle1']), array('width' => 9500*$expand_width, 'height' => 1, 'align' => 'center') );
		
		$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$this->rtf->MyRTF .= "{\par";
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$stringCell_title = $this->enkaEncode(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			
			// še dodamo textbox če je polj other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 && $this->getUserId() )
			{
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
			}										
			$stringCell_title .= $_txt.':';

			$tableHeader = '\trowd\trql\trrh400';

			/*Izpis bočnega stolpca*/				
			$table = '\cellx'.( 5000*$expand_width );	
			$tableEnd = '\pard\intbl '.$this->enkaEncode($stringCell_title). '\qr\cell';
			
			$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' AND part='1' ORDER BY 'vrstni_red'");
			
			//izpis rezultatov
			$resultString = '';
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
			{
				// poiščemo kaj je odgovoril uporabnik:
				$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."'");				
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		
				if($rowVsehVrednosti['id'] == $userAnswer['grd_id'])						
					$resultString .= ' '.$this->enkaEncode($rowVsehVrednosti['naslov']).',';
			}
			$resultString = substr($resultString, 0, -1);
			
			$table .= '\cellx'.( 10000*$expand_width );	
			$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($resultString).' '.$this->rtf->color(0).'\ql\cell';
						
			$tableEnd .= '\pard\intbl\row';
			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);		
		}
		$this->rtf->MyRTF .= "}";
		
		
		//drugi del grida
		//naslov 2. grida
		$this->rtf->TextCell($this->enkaEncode($spremenljivke['grid_subtitle2']), array('width' => 9500*$expand_width, 'height' => 1, 'align' => 'center') );
		
		$sqlVrednosti = sisplet_query("SELECT *,id, naslov, naslov2, variable FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$this->rtf->MyRTF .= "{\par";
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$stringCell_title = $this->enkaEncode(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			
			// še dodamo textbox če je polj other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 && $this->getUserId() )
			{
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
			}										
			$stringCell_title .= $_txt.':';

			$tableHeader = '\trowd\trql\trrh400';

			/*Izpis bočnega stolpca*/				
			$table = '\cellx'.( 5000*$expand_width );	
			$tableEnd = '\pard\intbl '.$this->enkaEncode($stringCell_title). '\qr\cell';
			
			$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' AND part='2' ORDER BY 'vrstni_red'");
			
			//izpis rezultatov
			$resultString = '';
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
			{
				// poiščemo kaj je odgovoril uporabnik:
				$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."'");				
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		
				if($rowVsehVrednosti['id'] == $userAnswer['grd_id'])						
					$resultString .= ' '.$this->enkaEncode($rowVsehVrednosti['naslov']).',';
			}
			$resultString = substr($resultString, 0, -1);
			
			$table .= '\cellx'.( 10000*$expand_width );	
			$tableEnd .= '\pard\intbl'.$this->rtf->color(12).' '.$this->enkaEncode($resultString).' '.$this->rtf->color(0).'\ql\cell';
						
			$tableEnd .= '\pard\intbl\row';
			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);		
		}
		$this->rtf->MyRTF .= "}";

	}
	
	function displayDoubleGrid($spremenljivke) {
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		$this->rtf->MyRTF .= "{\\par\\fs22";
		$defw_full = 9500*$expand_width;
		$defw_fc = 1200*$expand_width; // first cell width
		$defw_max = 800*$expand_width; // max other cell width

		$maxcellx = 9500*$expand_width;

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
			// če ni naslova vzamemo variable
			$stringHeader_title = $this->enkaEncode( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] : $rowVsehVrednosti['variable'] );
			$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
		}
		// izpišemo header celice
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);

		// loopamo skozi vrstice in pripravimo podatke za tabelo z radii
		$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$i=1;
			// če ni naslova vzamemo naslov2, če ne pa variable
			$stringCell_title = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			
			// še dodamo textbox če je polj other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 && $this->getUserId() )
			{
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."'");
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
			}										
			$stringCell_title .= $_txt;
			
			$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
			$tableHeader_width = "\\cellx".$defw_fc;
			$tableHeader_title = "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title)."\\cf0\\cell";
			$tableHeader_finish = "\\pard\\intbl\\row";

			$border = false;
			$sqlVsehVrednsti = sisplet_query("SELECT id, part FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY part, vrstni_red");		
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
					
				// poiščemo kaj je odgovoril uporabnik:
				$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				
				//izris srednjega borderja
				if($border == false && $rowVsehVrednosti['part'] == 2){
					$border = true;
					$leftBorder = '\clbrdrl\brdrs\brdrw10';
				}
				else
					$leftBorder = '';					
				
				$full = ($rowVsehVrednosti['id'] == $userAnswer['grd_id']) ? true : false;
				
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
	
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
	
		$this->rtf->MyRTF .= "{\\par\\fs22";
		
		$kolon = 0;
		
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
		
		$defw_full = 10500*$expand_width;
		if($kolon < 6){              			
			$defw_fc = 4300*$expand_width; // first cell width
		}
		else{              
			$defw_fc = 2000*$expand_width; // first cell width
		}
		
		$kolon ++;
		$w_oc = ( $defw_full - $defw_fc ) / $kolon;
		$defw_max = floor($w_oc);	
		
		$tableHeader_base = "\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";
		$tableHeader_width = "\\cellx".$defw_fc;
		$tableHeader_title = "\\pard\\intbl\\qc{}\\cell";
		$tableHeader_finish = "\\pard\\intbl\\row";

		/*$sqlVsehVrednsti = sisplet_query("SELECT g.naslov,g.variable,m.vrstni_red FROM srv_grid g, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND g.spr_id=m.spr_id ORDER BY m.vrstni_red");
		$rowCnt = 0;
		while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
		{
			$rowCnt++;
			$tableHeader_width .= "\\cellx". ( $rowCnt * $defw_max + $defw_fc );
			// če ni naslova vzamemo variable
			$stringHeader_title = $this->enkaEncode( ( $rowVsehVrednosti['naslov'] ) ? $rowVsehVrednosti['naslov'] : $rowVsehVrednosti['variable'] );
			$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
		}
		// izpišemo header celice
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);*/
				
		
		// izišemo header celice
		$rowCnt = 0;
		$sqlSubGrid = sisplet_query("SELECT m.spr_id, m.vrstni_red, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red");
		while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
			
			if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
				$sqlVsehVrednsti = sisplet_query("SELECT naslov, variable FROM srv_grid WHERE spr_id='".$rowSubGrid['spr_id']."' ");
				while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti))
				{
					// Pri multigrid dropdownih ne izpisujemo naslova
					if($rowSubGrid['tip'] != 6 || $rowSubGrid['enota'] != 2){					
						
						$rowCnt++;	
						$tableHeader_width .= "\\cellx". ( $rowCnt * $defw_max + $defw_fc );
						
						// če je naslov null izpišemo variable
						$stringHeader_title =  $this->enkaEncode( $rowVsehVrednosti['naslov'] ? $rowVsehVrednosti['naslov'] :  $rowVsehVrednosti['variable'] );
						$tableHeader_title .= "\\pard\\intbl\\qc{".$this->enkaEncode($stringHeader_title)."}\\cell";
					}
					else{
						$rowCnt++;	
						$tableHeader_width .= "\\cellx". ( $rowCnt * $defw_max + $defw_fc );

						$tableHeader_title .= "\\pard\\intbl\\qc{}\\cell";

						break;
					}
				}
			}
		}
		// izpišemo header celice
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);
		

		// loopamo skozi vrstice in pripravimo podatke za tabelo z radii
		$row_count = 1;
		$sqlVrednosti = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti))
		{
			$skipRow = false;
			
			// barva vrstice
			$row_color = ($row_count%2 == 1) ? '\\clcbpat18' : '';	
			
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
				$i=1;
				// če ni naslova vzamemo naslov2, če ne pa variable
				$stringCell_title = $this->enkaEncode( ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
				$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
				$tableHeader_width = $row_color."\\cellx".$defw_fc;
				$tableHeader_title = "\\pard\\intbl\\ql\cf0 ".$this->enkaEncode($stringCell_title)."\\cf0\\cell";
				$tableHeader_finish = "\\pard\\intbl\\row";

				
				// Loop po podskupinah gridov
				$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND s.id=m.spr_id ORDER BY m.vrstni_red");
				while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
					
					if((in_array($rowSubGrid['spr_id'],$tmp_svp_pv) || count($tmp_svp_pv) == 0) && $this->checkSpremenljivka($rowSubGrid['spr_id'], $gridMultiple=true)){
						// Dobimo se var_id od trenutne podskupine
						$sqlSubVar = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$rowSubGrid['spr_id']."' AND vrstni_red='".$rowVrednost['vrstni_red']."' ");
						$rowSubVar = mysqli_fetch_array($sqlSubVar);
						
						// Ce imamo dropdown mg izpisemo samo izbrani odg.
						if($rowSubGrid['tip'] == 6 && $rowSubGrid['enota'] == 2){
							
							$sqlUserAnswer = sisplet_query("SELECT d.grd_id, g.naslov, g.variable FROM srv_grid g INNER JOIN srv_data_grid".$this->db_table." d ON g.id=d.grd_id AND g.spr_id=d.spr_id WHERE d.spr_id='".$rowSubGrid['spr_id']."' AND d.usr_id='".$this->getUserId()."' AND d.vre_id='".$rowSubVar['id']."'");
							if(mysqli_num_rows($sqlUserAnswer) > 0){
								$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								$title = $this->enkaEncode( $userAnswer['naslov'] ? $userAnswer['naslov'] :  $userAnswer['variable'] );
							}
							else
								$title = '';
							
							$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
							$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->enkaEncode($title).$this->rtf->color(0).'\qc{}\\cell';

							$i++;
						}
						else{
							$sqlVsehVrednsti = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$rowSubGrid['spr_id']."' ORDER BY vrstni_red");
							while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
									
								// poiščemo kaj je odgovoril uporabnik:
								if($rowSubGrid['tip'] == 6)
									$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubVar['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."'");
								elseif($rowSubGrid['tip'] == 16)
									$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubVar['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");
								else
									$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubVar['spr_id']."' AND usr_id = '".$this->getUserId()."' AND vre_id = '".$rowSubVar['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' ");
									
								$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
								
								$full = ($rowVsehVrednosti['id'] == $userAnswer['grd_id']) ? true : false;
								
								if($rowSubGrid['tip'] == 6){
									$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
									if($full)
										$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio2.png", "15")."}\\cell";
									else
										$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("radio.png", "15")."}\\cell";
								}
								elseif($rowSubGrid['tip'] == 16){
									$tableHeader_width .= "\clvertalc".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
									if($full)
										$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox2.png", "15")."}\\cell";
									else
										$tableHeader_title .= "\\pard\\intbl\\qc{". $this->rtf->ImageToString("checkbox.png", "15")."}\\cell";
								}
								else{	
									$tableHeader_width .= "\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10".$row_color."\\cellx". ( $i * $defw_max + $defw_fc );
									$tableHeader_title .= '\\pard\\intbl'.$this->rtf->color(12).' '.$this->rtf->enkaEncode($userAnswer['text']).$this->rtf->color(0).'\qc{}\\cell';
								}
								$i++;
							}
						}
					}
				}
				
				$row_count++;
				
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish);
			}
		}
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}
	
	
	function createFrontPage()
	{
		global $lang;
		
		// razsiritev ce imamo landscape postavitev
		$expand_width = $this->landscape == 1 ? 1.5 : 1;
		
		$this->rtf->new_line(10);
		
		if(!$this->getUserId()){
			$this->rtf->TextCell($this->rtf->bold(1).$this->enkaEncode( SurveyInfo::getInstance()->getSurveyTitle()).$this->rtf->bold(0).'\\line\n '.$lang['export_firstpage_results'], array('width' => 9500*$expand_width, 'height' => 3,
			'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
			'colorF' => "0", 'colorB' => "0" ) );
		}
		else{
			$this->rtf->TextCell($this->enkaEncode( SurveyInfo::getInstance()->getSurveyTitle()), array('width' => 9500*$expand_width, 'height' => 3,
			'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
			'colorF' => "0", 'colorB' => "0" ) );
		}
		
		$this->rtf->new_line(3);
		// dodamo info:
		$this->rtf->TextCell("", array('width' => 9500*$expand_width, 'height' => 1,
		 'align' => 'left', 'valign' => 'bottom' , 'border' => array('bottom'),'colorF' => "0" ) );

		$infoTable = array();
		array_push( $infoTable, array( $lang['export_firstpage_shortname'].': '.$this->enkaEncode(SurveyInfo::getInstance()->getSurveyTitle()), "" ) );
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim() )
			array_push( $infoTable, array( $lang['export_firstpage_longname'].': '.$this->enkaEncode(SurveyInfo::getInstance()->getSurveyAkronim()), "" ) );
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
		$this->rtf->TableFromArray( array( 4750*$expand_width, 4750*$expand_width ), $infoTable, array('spacer' => 0));

		if($this->getUserId())
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
			
			$result .= "{";
			$result .= "\\pict\\jpegblip\\picscalex100\\picscaley100\\bliptag132000428 ";
			$result .= trim(bin2hex($file));
			$result .= "\n}\n";
	
			$text = preg_replace("/<img[^>]+\>/i", $result, $text); 
		} 					
		
		// popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
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
	function setUserId($usrId) {$this->usr_id = $usrId;}
	function getUserId() {return ($this->usr_id)?$this->usr_id:false;}
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
					$this->rtf->add_text($this->rtf->color(9).$this->rtf->bold(1).$this->enkaEncode($this->dataPiping($loop_title)).$this->rtf->bold(0).$this->rtf->color(0));				
					$this->rtf->new_line(1);
					
					$sqlSpremenljivke = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
					while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke))
					{ // sprehodimo se skozi spremenljivke grupe
						$spremenljivka = $rowSpremenljivke['id'];
						if ( $this->checkSpremenljivka ($spremenljivka) /*|| $this->showIf == 1*/ )
						{ // lahko izrišemo spremenljivke

							//nastavimo velikost pisave
							$this->rtf->MyRTF .= $this->rtf->_font_size($this->font * 2);
							
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
						
								//kratek izpis rezultatov spremenljivke - samo pri radio, checkbox, multiradio, multicheckbox, besedilo			
								if( $this->type == 0 && $this->getUserId() && ($rowSpremenljivke['tip'] < 4 || $rowSpremenljivke['tip'] == 6 || $rowSpremenljivke['tip'] == 16 /*|| $rowSpremenljivke['tip'] == 21*/) ){					
									if($rowSpremenljivke['tip'] > 3	)
										$this->outputVprasanje($rowSpremenljivke);
										
									$this->outputSpremenljivkeValues($rowSpremenljivke);						
								}
								elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
									$this->outputSpremenljivkeValues($rowSpremenljivke);
								}
								else{
									$this->outputVprasanje($rowSpremenljivke);
									$this->outputSpremenljivke($rowSpremenljivke);				
								}
								
								// dodamo presledek (prazno vrstico)
								$this->rtf->new_line(1);
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

						//nastavimo velikost pisave
						$this->rtf->MyRTF .= $this->rtf->_font_size($this->font * 2);
						
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
					
							//kratek izpis rezultatov spremenljivke - samo pri radio, checkbox, multiradio, multicheckbox, besedilo			
							if( $this->type == 0 && $this->getUserId() && ($rowSpremenljivke['tip'] < 4 || $rowSpremenljivke['tip'] == 6 || $rowSpremenljivke['tip'] == 16 /*|| $rowSpremenljivke['tip'] == 21*/) ){					
								if($rowSpremenljivke['tip'] > 3	)
									$this->outputVprasanje($rowSpremenljivke);
									
								$this->outputSpremenljivkeValues($rowSpremenljivke);						
							}
							elseif($this->type == 2 && $rowSpremenljivke['tip'] != 24){
								$this->outputSpremenljivkeValues($rowSpremenljivke);
							}
							else{
								$this->outputVprasanje($rowSpremenljivke);
								$this->outputSpremenljivke($rowSpremenljivke);				
							}
							
							// dodamo presledek (prazno vrstico)
							$this->rtf->new_line(1);
						}						
					}
				}
			}
		}
		
		if($this->pageBreak == 0){
			$this->rtf->TextCell('', array('width' => 9500, 'height' => 0, 
				'align' => 'center', 'valign' => 'middle' , 'border' => 'top',
				'colorF' => "0", 'colorB' => "0" ) );	
			$this->rtf->new_line(1);
		}
	}	
	
}


?>
