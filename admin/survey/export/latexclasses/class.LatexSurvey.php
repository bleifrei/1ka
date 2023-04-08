<?php

/**
 *
 *	Class ki skrbi za izris vprasalnika v latex
 *
 *
 */
//namespace Export\Latexclasses;

include('../../vendor/autoload.php');

define("RADIO_BTN_SIZE", 0.13);
 
class LatexSurvey{
	
	var $anketa; // ID ankete
	var $tex;	//shrani tex kodo
	var $texNewLine = '\\\\ ';
	//var $texPageBreak = "\\pagebreak";
	var $texPageBreak = "\\newpage";
	var $export_format;
	var $export_data_show_recnum;
	var $exportDataPageBreak=0; //vsak respondent na svoji strani
	
	var $commentType = 1;	// tip izpisa komentarjev
	
	var $loop_id = null;	// id trenutnega loopa ce jih imamo	
	
	var $db_table = '';
	
	protected $showIntro = 0;
	protected $showGDPRIntro = 0;
	protected $GDPRIntro = '';
	protected $type = 0;			// tip izpisa - 0->navaden, 1->iz prve strani, 2->s komentarji
	
	protected $showIf = 0;		// izpis if-ov
	
	var $skipEmpty = 0; 	// izpusti vprasanja brez odgovora
	var $skipEmptySub = 0; 	// izpusti podvprasanja brez odgovora
	
	protected $recnum = 0;
	protected $usr_id = 0;
	protected $texBigSkip = '\bigskip';

	protected $admin_type;

	protected $path2UploadedImages;
	
	protected $language = -1;		// Katero verzijo prevoda izvazamo	
	
	//function __construct($anketa, $export_format){
	function __construct($anketa=null, $export_format='', $export_show_intro=null, $export_show_if=null, $export_data_skip_empty=null, $export_data_skip_empty_sub=null){
		global $site_path, $global_user_id, $admin_type, $lang;
	
		$this->anketa = $anketa;
		$this->export_format = $export_format;

		$this->admin_type = $admin_type;
		
		$this->usr_id = $_GET['usr_id'];
		
		$this->showIntro = $export_show_intro;
		$this->showIf = $export_show_if;
		
		$this->skipEmpty = $export_data_skip_empty;
		
		$this->skipEmptySub = $export_data_skip_empty_sub;

		$this->path2Images = $site_path.'admin/survey/export/latexclasses/textemp/images/';

					
		//Prikazi GDPR v uvodu			
		$gdpr = new GDPR();
		$this->showGDPRIntro = $gdpr->isGDPRSurveyTemplate($this->anketa);
		//echo "gdpr nastavitve: ".$this->showGDPRIntro."</br>";
		if($this->showGDPRIntro){
			$this->GDPRIntro = $gdpr->getSurveyIntro($this->anketa);
			//echo "gdpr te: ".$this->GDPRIntro."</br>";
		}
		
		if ($this->usr_id  != '') {
			$sqlR = sisplet_query("SELECT recnum FROM srv_user WHERE id = '$this->usr_id '");
			$rowR = mysqli_fetch_array($sqlR);
			$this->recnum = $rowR['recnum'];
		}
		
		//pridobitev nastavitev izvoza
		SurveySetting::getInstance()->Init($this->anketa);
		$this->export_data_show_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum');	//ali je potrebno pokazati recnum ob vsakem respondentu
		$this->exportDataPageBreak = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_PB');	//ali mora vsak izpis odgovorov respondenta zaceti na svoji strani

		SurveyStatusProfiles::Init($this->anketa);
		
		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa) )
		{		
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		}
		else{
			return false;
		}		
	}
	
	#funkcija, ki skrbi za izpis praznega vprasalnika in vprasalnika z odgovori enega respondenta
	public function displaySurvey($export_subtype='', $export_data_type='', $language=null){
		global $lang, $site_url;
		//echo "Funkcija displaySurvey user: $this->usr_id</br>";
		$this->language = $language;
		if($this->language!=-1){
			// Naložimo jezikovno datoteko
			$file = '../../lang/'.$this->language.'.php';
			include($file);
			$_SESSION['langX'] = $site_url .'lang/'.$this->language.'.php';
		}		
		
		$surveyExpanded = SurveyInfo::getInstance()->getSurveyColumn('expanded');
		
		// filtriramo spremenljivke glede na profil	
		SurveyVariablesProfiles :: Init($this->anketa);
		
		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		
		$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);
		
		foreach ( $tmp_svp_pv as $vid => $variable) {
			$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));			
		}

				
		if($export_subtype=='q_data'){	//ce je vprasalnik za enega respondenta
			//pridobitev podatkov trenutnega respondenta za izpis Recnum
			$izbranStatusProfile = SurveyStatusProfiles :: getStatusAsQueryString();
			$sqluString = "SELECT id, last_status, lurker, recnum FROM srv_user WHERE ank_id = '".$this->anketa."' ".$izbranStatusProfile." AND deleted='0' AND preview='0' AND id='".$this->usr_id."' ORDER BY recnum";
			//echo $sqluString;
			$sqlu = sisplet_query($sqluString);		
			$rowu = mysqli_fetch_array($sqlu);
			$recnum = $rowu['recnum'];
			if($recnum && $this->export_data_show_recnum){
				$recnumBesedilo = "(Recnum $recnum)";
			}else{
				$recnumBesedilo = "";
			}
			//pridobitev podatkov trenutnega respondenta za izpis Recnum - konec
			
			
			
			//$tex .= $lang['srv_respondent_answer'].": ".$this->recnum;			
			$tex .= '\MakeUppercase{\huge \textbf{'.$lang['srv_respondent_answer'].' '.$recnumBesedilo.'}}';	//izpisi "Odgovori respondenta"
			$tex .= $this->texNewLine;
			$tex .= $this->texNewLine;
		}
		
		//ce je potrebno izpisati GDPR besedilo v intro
		if($this->showGDPRIntro == 1){
			//$GDPRintro = "gdpr INTRO";
			$GDPRintro = strip_tags($this->GDPRIntro, '<a><img><ul><li><ol><br><p>');
			$GDPRintro = $this->encodeTextHere($GDPRintro);
			$tex .= ' \textbf{'.$GDPRintro.'} ';

			//radio buttona
			if($export_subtype=='q_empty'){	//ce je prazen vprasalnik
				$radioButtonTex = ("\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio}");
				$tex .= '\\\\'.$radioButtonTex.' '.$lang['srv_gdpr_intro_no'];
				$tex .= '\\\\'.$radioButtonTex.' '.$lang['srv_gdpr_intro_yes'];
			}else{
				//pridobitev ID grupe
				$sqlGrupeStringGDPR = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa."' ORDER BY vrstni_red";		
				$sqlGrupeGDPR = sisplet_query($sqlGrupeStringGDPR);
				$rowGrupeGDPR = mysqli_fetch_array( $sqlGrupeGDPR );
				$grupa = $rowGrupeGDPR['id'];
				//pridobitev ID grupe - konec
				
				//pridobitev informacije obiska strani oz. ankete				
				$sqlStringStran = "SELECT time_edit FROM srv_user_grupa".$this->db_table." WHERE usr_id='".$this->usr_id."' AND gru_id='".$grupa."'";
				//echo $sqlStringStran;
				$sqlStran = sisplet_query($sqlStringStran);
				$rowStran = mysqli_fetch_array( $sqlStran );
				$stran = $rowStran['time_edit'];
				//echo $stran;
				//pridobitev informacije obiska strani oz. ankete - konec

				if($stran){
					$radioButtonTexNo = ("\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio}");
					$radioButtonTexYes = ("\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio2}");
				}else{
					$radioButtonTexNo = ("\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio2}");
					$radioButtonTexYes = ("\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio}");
				}

				$tex .= '\\\\'.$radioButtonTexNo.' '.$lang['srv_gdpr_intro_no'];
				$tex .= '\\\\'.$radioButtonTexYes.' '.$lang['srv_gdpr_intro_yes'];
				
			}
			
			
			//radio buttona - konec

			$tex .= $this->texNewLine;				
			$tex .= $this->texNewLine;
		}
		
		// ce obstaja intro izpisemo intro - pri izpisu vprasalnika brez odgovorov (ce smo na prvi strani moramo biti v razsirjenem nacinu)
		if( ($surveyExpanded != 0 || $this->type != 1) && $this->showIntro == 1 ){
			if ( SurveyInfo::getInstance()->getSurveyShowIntro() )
			{ 		
				//preverjanje, ali je prevod
				if(isset($_GET['language'])){
					$this->language = $_GET['language'];
					$prevod = 1;
				}else{
					$prevod = 0;
				}
				//preverjanje, ali je prevod - konec

				//if($this->language!=-1){ //ce je prevod ankete
				if($prevod){ //ce je prevod ankete
					$spr_id_uvod = -1;
					$sqll = sisplet_query("SELECT naslov, info FROM srv_language_spremenljivka WHERE ank_id='".$this->anketa."' AND spr_id='".$spr_id_uvod."' AND lang_id='".$this->language."'");
					$rowl = mysqli_fetch_array($sqll);	//pridobi prevod uvoda v ustreznem jeziku
					$intro = $rowl['naslov'];	//prevod uvoda v ustreznem jeziku
				}else{
					$intro = (SurveyInfo::getInstance()->getSurveyIntro() == '') ? $lang['srv_intro'] : SurveyInfo::getInstance()->getSurveyIntro();
				}					
				$tex .=  $this->encodeTextHere($intro);		
				if($intro){
					$tex .= $this->texNewLine;
					$tex .= $this->texNewLine;
				}
				
			}
		}
		
		$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa."' ORDER BY vrstni_red";		
		$sqlGrupe = sisplet_query($sqlGrupeString);

		//echo "__________________________________</br>";
		//echo "Funkcija displaySurvey user: $this->usr_id</br>";
		if($this->export_format == 'rtf'){
			$tex .= $this->texNewLine;
		}

		$question = new LatexSurveyElement($this->anketa, $this->export_format, 0, $this->usr_id, $export_subtype, $language);
		
		########
		while ( $rowGrupe = mysqli_fetch_assoc( $sqlGrupe ) )
		{ // sprehodmo se skozi grupe ankete
			$this->grupa = $rowGrupe['id'];

			// Pogledamo prvo spremenljivko v grupi ce je v loopu
			$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");			
			$row = mysqli_fetch_array($sql);

			// ce je ima loop za parenta
			$if_id = $this->find_parent_loop($row['id']);

			if ($if_id > 0 && ($export_subtype=='q_data'||$export_subtype=='q_data_all')){	//ce je loop in (je izpis odgovorov respondentov)
				$texLoop = '';
				//echo "Je v loopu $if_id </br>";
				$sql1 = sisplet_query("SELECT if_id FROM srv_loop WHERE if_id = '$if_id'");
				$row1 = mysqli_fetch_array($sql1);
				
				$this->loop_id = $this->findNextLoopId($row1['if_id']);
				//echo "Loop id: ".$this->loop_id."</br>";
				//echo " id: ".$row['id']."</br>";
				$if = Cache::srv_if($if_id);
				//echo "If label: ".$if['label']."</br>";
				//echo "If : ".print_r($if)."</br>";
				$loop_title = $if['label'];
				
				// gremo cez vse spremenljivke v trenutnem loopu
				while($this->loop_id != null){
					//echo "Loop id: ".$this->loop_id."</br>";
					
					// Izrisemo naslov loopa
					$tex .= $this->dataPiping($loop_title);
					
					//TODO: TUdi tu se celotna spremenljivka prenaša naprej
					$sqlSpremenljivke = sisplet_query("SELECT id, tip, visible, sistem, variable, naslov, info, orientation, dostop FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
					//echo "SELECT id, tip, visible, sistem, variable, naslov, info, orientation FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC "."</br>";
					
					while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke))
					{ // sprehodimo se skozi spremenljivke grupe					

						$spremenljivka = $rowSpremenljivke['id'];
						
						if ($rowSpremenljivke['visible'] == 0 || !( ($this->admin_type <= $rowSpremenljivke['dostop'] && $this->admin_type>=0) || ($this->admin_type==-1 && $rowSpremenljivke['dostop']==4) ) ) {
							//echo "ni admin za sprem: ".$rowSpremenljivke['id']."</br>";
							continue;						
						}
						
						if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je vprasalnik s podatki za enega ali vse respondente
							$preveriSpremenljivko = $this->checkSpremenljivkaData($spremenljivka, $this->loop_id);
						}else{	//ce je prazen vprasalnik ali vprasalnik s komentarji
							$preveriSpremenljivko = $this->checkSpremenljivka($spremenljivka);
						}
						
						//if ( $this->checkSpremenljivka ($spremenljivka) /*|| $this->showIf == 1*/ )
						if ($preveriSpremenljivko)	
						{ // lahko izrišemo spremenljivke

							// po potrebi obarvamo vprašanja
							//$this->pdf->SetTextColor(0);
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
								
								//izpisi tekst vprasanja
								$tex .= $question->displayQuestionText($rowSpremenljivke, $stevilcenje, $export_subtype, $preveriSpremenljivko, $this->loop_id, $export_data_type);	//izpisi tekst vprasanja
								
								//izpisi odgovore								
								$tex  .= $question->displayAnswers($rowSpremenljivke, $export_subtype, $preveriSpremenljivko, $export_data_type, $this->loop_id);	//izpisi odgovore
							}											
						}
					}								
					$this->loop_id = $this->findNextLoopId();
					
					//ce je prazen vprasalnik, ustavi
					if($export_subtype=='q_empty'){
						break;
					}
				}
			}			
			// Navadne spremenljivke ki niso v loopu
			else{
				//echo "Ni v loop-u </br>";
				$loop_id = 'IS NULL';
				$zaporedna = 0;
				//TODO: Omenjen query je potrebno optimizirati in da se naprej samo prenaša ID, ki se potem v podfunkcijah kliče ustrezen query
                // trenutno se potrebuje:  id, tip, visible, sistem, variable, params
				$sqlSpremenljivke = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
				
				//$testniStavek = "SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC";
				//echo "sql stavek za testiranje: ".$testniStavek."</br>";
				while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke)){ // sprehodimo se skozi spremenljivke grupe
					$spremenljivka = $rowSpremenljivke['id'];
					
					if ($rowSpremenljivke['visible'] == 0 || !( ($this->admin_type <= $rowSpremenljivke['dostop'] && $this->admin_type>=0) || ($this->admin_type==-1 && $rowSpremenljivke['dostop']==4) ) ) {
						//echo "ni admin za sprem: ".$rowSpremenljivke['id']."</br>";
						continue;						
					}

					//echo "Ni v loop-u:".$rowSpremenljivke['tip']."  </br>";
					if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je vprasalnik s podatki za enega ali vse respondente
						$preveriSpremenljivko = $this->checkSpremenljivkaData($spremenljivka, $loop_id);
					}else{	//ce je prazen vprasalnik ali vprasalnik s komentarji
						$preveriSpremenljivko = $this->checkSpremenljivka($spremenljivka);
					}
					
					//if ( $this->checkSpremenljivka ($spremenljivka) /*|| $this->showIf == 1*/ )
					if ($preveriSpremenljivko)
					{ // lahko izrišemo spremenljivke

						// po potrebi obarvamo vprašanja
						//$this->pdf->SetTextColor(0);
						if ($rowSpremenljivke['visible'] == 0)
						{
							if ($rowSpremenljivke['sistem'] == 1)
							{ 		// če je oboje = vijolčno
								//$this->pdf->SetTextColor(128,0,128);
							}
							else
							{		// Če je skrito = rdeče
								//$this->pdf->SetTextColor(255,0,0);
							}
						}
						else if ($rowSpremenljivke['sistem'] == 1)
						  //$this->pdf->SetTextColor(0,0,255);


						// če imamo številčenje Type = 1 potem številčimo V1
						if (SurveyInfo::getInstance()->getSurveyCountType())
							$zaporedna++;
						$stevilcenje = ( SurveyInfo::getInstance()->getSurveyCountType() ) ?
						( ( SurveyInfo::getInstance()->getSurveyCountType() == 2 ) ? $rowSpremenljivke['variable'].") " : $zaporedna.") " ) : null;

						$this->currentHeight = 0;
						
						// izpis skrcenega vprasalnika (samo pri izpisu iz urejanja)
						if($surveyExpanded == 0 && $this->type == 1){
							//$this->outputVprasanjeCollapsed($rowSpremenljivke, $stevilcenje);
						}
						
						// izpis navadnega vprasalnika
						else{
							//echo "while stavek za".$rowSpremenljivke['id']." </br>";							
							$tex .= $question->displayQuestionText($rowSpremenljivke, $stevilcenje, $export_subtype, $preveriSpremenljivko, $this->loop_id, $export_data_type);	//izpisi tekst vprasanja							
							$tex .= $question->displayAnswers($rowSpremenljivke, $export_subtype, $preveriSpremenljivko, $export_data_type, $this->loop_id);	//izpisi odgovore
						}
						//$this->pdf->Ln(LINE_BREAK);
					}
				}
				//echo "Končni tex brez loopa: ".$tex."</br>";
			}
			//$tex .= $this->texBigSkip;
			if($this->export_format == 'rtf'){
				$tex .= $this->texBigSkip;
			}
			//$tex .= $texLoop;
			//echo "Končni tex: ".$tex."</br>";
		}
		//echo "Končni tex: ".$tex."</br>";
		// če izpisujemo grupo, ne izpisujemo zakljucka
		if ( !$this->getGrupa() ){
			if ( SurveyInfo::getInstance()->getSurveyShowConcl() && SurveyInfo::getInstance()->getSurveyConcl() )
			{		// ce obstaja footer izpisemo footer

			}
		}


        // Izpis grafa in tabele za NIJZ na koncu dokumenta
        global $site_domain;
        if( ($site_domain == 'test.1ka.si' && $this->anketa == '8892') || ($site_domain == 'anketa.nijz.si' && $this->anketa == '126738') ){	

            // Page break
            $tex .= '\cleardoublepage';

            // Naslov "Ocena bivalnega okolja"
            $tex .= '\begin{center} \textbf{Ocena bivalnega okolja} \end{center}';

            $nijz = new SurveyNIJZ($this->anketa, $this->usr_id);
            
            // Latex nijz slika grafa
            $tex .= $nijz->displayRadarLatex();

            // Latex nijz tabela
            $tex .= $nijz->displayTableLatex();
        }
		
		return $tex;
	}
	#funkcija, ki skrbi za izpis praznega vprasalnika in vprasalnika z odgovori enega respondenta - konec
	
	
	#funkcija, ki skrbi za izpis vseh odgovorov (vsi respondenti -> max 300)	//po outputAllResults() iz class.pdfIzvozResults.php
	public function displayAllSurveys($export_subtype='', $export_format='', $export_data_type=''){
		global $lang, $site_url;
		$tex = '';
		$izbranStatusProfile = SurveyStatusProfiles :: getStatusAsQueryString();
		
		$sqluString = "SELECT id, last_status, lurker, recnum FROM srv_user WHERE ank_id = '".$this->anketa."' ".$izbranStatusProfile." AND deleted='0' AND preview='0' ORDER BY recnum";
		//echo $sqluString;
		$sqlu = sisplet_query($sqluString);
		
		$numOfUsers = mysqli_num_rows($sqlu);	//stevilo respondentov
		
		if($numOfUsers > 300){
			$tex .= $lang['srv_export_all_respondent_data_error'].' ('.$numOfUsers.').';
		}		
		else{								
			//za vsak user oz respondent
			while($rowu = mysqli_fetch_array($sqlu)){								
				//belezenje statusa
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
				
				#prenos odgovorov vsakega respondenta na novo stran, če je to potrebno
				//if($this->exportDataPageBreak == 1){	//ce mora vsak respondent imeti svoje odgovore na svoji strani
				if($this->exportDataPageBreak == 1 && $export_format == 'pdf'){	//ce mora vsak respondent imeti svoje odgovore na svoji strani in je pdf izvoz, saj za rtf pagebreak ne deluje
					$tex .= $this->texPageBreak;
				}
				#prenos odgovorov vsakega respondenta na novo stran, če je to potrebno - konec
				
				//$this->export_data_show_recnum = 1;
				#izpis statusa respondenta in anketiranja
				if($this->export_data_show_recnum == 1){	//ce je potrebno pokazati stevilko respondenta					
					$tex .= '{\\Large Status '.$rowu['last_status'].' - '.$status.' (Recnum '.$rowu['recnum'].')} '.$this->texNewLine.' \\par ';					
				}else{
					$tex .= '{\\Large Status '.$rowu['last_status'].' - '.$status.$this->texNewLine.' \\par}';					
				}
				#izpis statusa respondenta in anketiranja - konec
				
				
				#izpis odgovorov posameznega userja
				$this->usr_id = $rowu['id'];
				
				#za pridobitev jezika respondenta
				if ($this->usr_id  != '') {					
					$sqlL = sisplet_query("SELECT language FROM srv_user WHERE id = '$this->usr_id ' ");
					$rowL = mysqli_fetch_array($sqlL);
					$language = $rowL['language'];					
				}
				#za pridobitev jezika respondenta - konec

				$tex .= $this->displaySurvey($export_subtype, $export_data_type, $language);
				#izpis odgovorov posameznega userja - konec
				
			}
		}
		return $tex;
	}
	#funkcija, ki skrbi za izpis vseh odgovorov (vsi respondenti -> max 300) - konec
	
	
	#funkcija, ki skrbi za izpis praznega vprasalnika s komentarji	//po outputCommentaries() iz class.pdfIzvoz.php
	public function displaySurveyCommentaries($export_subtype='', $export_data_type=''){	
		
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;
		$texBigSkip = '\bigskip';
		$tex = '';
		
		$this->commentType = (isset($_GET['only_unresolved'])) ? $_GET['only_unresolved'] : 1;

		$f = new Forum;
		$c = 0;
		
		$b = new Branching($this->anketa);
		
		$rowi = SurveyInfo::getInstance()->getSurveyRow();

		SurveySetting::getInstance()->Init($this->anketa);
		$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');
		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
		$question_note_view = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_view');
		$addfieldposition = SurveySetting::getInstance()->getSurveyMiscSetting('addfieldposition');
		$commentmarks = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks');
		$commentmarks_who = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks_who');
			
		$sqlString = "SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='".$this->anketa."' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC";
		$sql = sisplet_query($sqlString);

		$question = new LatexSurveyElement($this->anketa, $this->export_format, 0, 0, 0);


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
					$sqlt = sisplet_query("SELECT uid, time, vsebina FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved ORDER BY time $orderby, id $orderby");

				} elseif ($question_comment_viewauthor==1) {	// vidi samo svoje komentarje
					$sqlt = sisplet_query("SELECT uid, time, vsebina FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' $only_unresolved AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");

				} else {												// ne vidi nobenih komentarjev
				    $sqlt = sisplet_query("SELECT uid, time, vsebina FROM post WHERE 1=0");
				}		
				
				$sql2 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_text".$this->db_table." WHERE spr_id='0' AND vre_id='$row[id]' $only_unresolved2");
				$row2 = mysqli_fetch_array($sql2);

				if ( mysqli_num_rows($sqlt) > 0 || $row2['count'] > 0 || $row1['note'] != '') {
					$c++;
					$preveriSpremenljivko = 0; //ni potrebno
					$tex .= $question->displayQuestionText($row, $stevilcenje, $export_subtype, $preveriSpremenljivko, null, $export_data_type);	//izpisi tekst vprasanja
					$tex .= $question->displayAnswers($row, $export_subtype, $preveriSpremenljivko, $export_data_type, null);	//izpisi odgovore
					
 					if ($admin_type <= $question_note_view || $question_note_view == '') {
						
						if ($row1['note'] != '') {

							//$tex .= $texNewLine;
							$tex .= $this->texNewLine;
							$tex .= $question->encodeText($lang['hour_comment']);

							//$tex .= $texNewLine;
							$tex .= $this->texNewLine;
							$tex .= $question->encodeText($row1['note']);
						}
					}
									
					// komentarji na vprasanje
					if ($row1['thread'] > 0) {
						
						if (mysqli_num_rows($sqlt) > 0) {							
							//$tex .= $texNewLine;
							$tex .= $this->texNewLine;
							//$tex .= '\textbf{'.$question->encodeText($lang['srv_admin_comment']).'}';	//izpis naslova komentarjev
							$tex .= '\textcolor{komentar}{\textbf{'.$question->encodeText($lang['srv_admin_comment']).'}}';	//izpis naslova komentarjev
							//$tex .= '\textcolor{komentar}{\textbf{'.$question->encodeText($lang['srv_admin_comment']).'}}';	//izpis naslova komentarjev
							$tex .= '\\\\';

							$i = 0;
							while ($rowt = mysqli_fetch_array($sqlt)) {

								$tex .= '\textbf{'.$question->encodeText($f->user($rowt['uid'])).'}';	//izpis imena trenutnega urednika
								
								$tex .= $question->encodeText(' ('.$f->datetime1($rowt['time']).'):');	//izpis datuma komentarja
								
								$tex .= '\\\\';

								// Popravimo vsebino ce imamo replike							
								$vsebina = $rowt['vsebina'];
								$odgovori = explode("<blockquote style=\"margin-left:20px\">", $vsebina);
								
								$tex .= $question->encodeText($odgovori[0]);	//izpis komentarja
								
								unset($odgovori[0]);
								foreach($odgovori as $odgovor){	//izpis replik
									$odgovor = explode('<br />', $odgovor);
									$avtor = explode('</b> ', $odgovor[0]);
									
									$tex .= '\\\\';
									$tex .= '\forceindent';	//da je indented
									$tex .= '\textbf{'.$question->encodeText($avtor[0]).'} ';	//avtor replike
									$tex .= $question->encodeText($avtor[1]); //izpis datuma replike
									
									$tex .= '\\\\';
									$tex .= '\forceindent ';	//da je indented
									$tex .= $question->encodeText($odgovor[1]); //izpis replike
								}
								
								// Crta
								$tex .= '\\\\';
								$tex .= '\noindent\rule{0.9\textwidth}{0.5pt} ';								
								$tex .= '\\\\';
							}			
						}
					}

					// komentarji respondentov
					if ($row2['count'] > 0) {
						
						if ($admin_type <= $question_resp_comment_viewadminonly) {

/* 							$this->pdf->Ln(3);
							$this->pdf->setFont('','B', 10);
							$this->pdf->Write  (0, $this->encodeText($lang['srv_repondent_comment']), '', 0, 'l', 1, 1); */
							//$tex .= $texNewLine;
							$tex .= $this->texNewLine;
							//$tex .= '\textbf{'.$question->encodeText($lang['srv_repondent_comment']).'}';	//izpisa naslova komentarjev
							$tex .= '\textcolor{komentar}{\textbf{'.$question->encodeText($lang['srv_repondent_comment']).'}}';	//izpisa naslova komentarjev
							$tex .= '\\\\';
							
							//$this->pdf->Ln(3);
							
							if ($this->commentType == 1) $only_unresolved = " AND d.text2 <= 1 "; else $only_unresolved = " ";
							
							$sqlt = sisplet_query("SELECT d.text AS text, u.time_edit AS time_edit FROM srv_data_text".$this->db_table." d, srv_user u WHERE d.spr_id='0' AND d.vre_id='$row[id]' AND u.id=d.usr_id $only_unresolved2 ORDER BY d.id ASC");
							if (!$sqlt) echo mysqli_error($GLOBALS['connect_db']);
							while ($rowt = mysqli_fetch_array($sqlt)) {
								
/* 								$this->pdf->setFont('','', 10);
								$this->pdf->Write(0, $this->encodeText($f->datetime1($rowt['time_edit']).':'), '', 0, 'l', 1, 1);*/
								
								$tex .= $question->encodeText($f->datetime1($rowt['time_edit']).':');	//izpis datuma komentarja
								
								$tex .= '\\\\';
								
								//$this->pdf->MultiCell(100, 0, $this->encodeText($rowt['text']),0,'L',0,1,0,0,true,0);
								$tex .= $question->encodeText($rowt['text']);	//izpis komentarja respondenta
								
								$tex .= '\\\\';
								
								// Crta
								$tex .= '\noindent\rule{0.9\textwidth}{0.5pt} ';								
								$tex .= '\\\\';
								

							}
						}
					}
					
					//$this->pdf->Ln(LINE_BREAK);
					$tex .= $texBigSkip;
					$tex .= $texBigSkip;
				}
			}
	
			
		}
		
	
		return $tex;	
	}
	
	function getGrupa() {return $this->grupa;}
	
	
	/**
    * @desc preveri ali je spremenljivka vidna (zaradi branchinga)
    */
    function checkSpremenljivka ($spremenljivka=null) {

        $row = sisplet_query("SELECT visible FROM srv_spremenljivka WHERE id = '".$spremenljivka."'", "obj");
        if ($row->visible == 0) {
            return false;
        }

        //TODO: Omenjen del kode je nepotreben, ker vedno vrne true
        //$sql1 = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '".$spremenljivka."'");
        //if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
        //$row1 = mysqli_fetch_array($sql1);

        /*if (!$this->checkIf($row1['parent']))
            return false;*/

        return true;
    }
	
    /**
    * @desc preveri ali je spremenljivka vidna (zaradi branchinga), ko je q_data ali q_data_all
    */
    //function checkSpremenljivkaData ($spremenljivka, $gridMultiple=false) {
    function checkSpremenljivkaData ($spremenljivka=null, $loop_id_raw=null, $gridMultiple=false) {
		
		$loop_id = $loop_id_raw == 'IS NULL' ? " IS NULL" : " = '".$loop_id_raw."'";
		
		//$row = sisplet_query("SELECT id, visible, dostop, tip, enota FROM srv_spremenljivka WHERE id = '".$spremenljivka."'", "array");
		$sql = sisplet_query("SELECT id, visible, dostop, tip, enota FROM srv_spremenljivka WHERE id = '".$spremenljivka."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);
		
		// Ce vprasanje ni vidno ali ce uporabnik nima dostopa do vprasanja
        if ($row['visible'] == 0 || !( ($this->admin_type <= $row['dostop'] && $this->admin_type>=0) || ($this->admin_type==-1 && $row['dostop']==4) ) ) {
            return false;
        }
			
		// Kalklulacije in kvote ne prikazujemo
       	if($row['tip'] == 22 || $row['tip'] == 25) {
            return false;
        }
			
		// Preverjamo ce je vprasanje prazno in ce preskakujemo prazne
		if($this->skipEmpty==1 && !$gridMultiple){
			
			$isEmpty = true;
			//echo "isEmpty: ".$isEmpty."</br>";
			switch ( $row['tip'] ){
				case 1: //radio
				case 2: //check
				case 3: //select -> radio
					//$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$row[id]' AND usr_id='".$this->usr_id."' AND vre_id!='-2'");
					$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$row[id]' AND usr_id='".$this->usr_id."' AND vre_id>'0' AND loop_id $loop_id ";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
					if($sqlUserAnswer->st > 0){
						$isEmpty = false;
					}						
				break;

				case 6: //multigrid
				case 16:// multicheckbox
				case 19:// multitext
				case 20:// multinumber											
					if($row['tip'] == 6 && $row['enota'] != 3){
						$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
					}
					elseif($row['tip'] == 16 || ($row['tip'] == 6 && $row['enota'] == 3)){
						//$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."'");
						$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
					}
					else{
						//$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."'");
						$sqlUserAnswerString ="SELECT COUNT(*) AS st FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
					}
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
					//echo "stevilo podatkov: ".mysqli_num_rows($sqlUserAnswer)."</br>";
					if($sqlUserAnswer->st > 0){
						$isEmpty = false;	
					}
				break;
				
				case 7: //number
				case 8: //datum	
				case 18: //vsota
				case 21: //besedilo*
					$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_text".$this->db_table." WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
					if($sqlUserAnswer->st > 0)
						$isEmpty = false;				
				break;

				case 4: //besedilo*
					$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_text".$this->db_table." WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
					if($sqlUserAnswer->st > 0)
						$isEmpty = false;				
				break;
				
				case 17: //ranking					
					$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_rating WHERE spr_id=".$row['id']." AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
					if($sqlUserAnswer->st > 0)
						$isEmpty = false;
				break;
				
				case 24: //mesan multigrid	
					// loop po podskupinah gridov
					$sqlSubGrid = sisplet_query("SELECT m.spr_id AS spr_id, s.tip AS tip, s.enota FROM srv_grid_multiple AS m LEFT JOIN srv_spremenljivka AS s ON m.spr_id=s.id WHERE m.parent='".$spremenljivka."'");
					while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
						if($rowSubGrid['tip'] == 6){
							$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
						}
						elseif($rowSubGrid['tip'] == 16){
							$sqlUserAnswerString ="SELECT COUNT(*) AS st FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
						}
						else{
							$sqlUserAnswerString = "SELECT COUNT(*) AS st FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
						}

						$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
						if($sqlUserAnswer->st > 0){
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
				
				case 26: //lokacija
                                        //za podtip izberi lokacijo je treba preverjat posebej, ker se vrstica pri userju vedno kreira ampak brez odgovora
                                        if($row['enota'] == 3){
                                            $qu = "SELECT count(*) as cnt FROM srv_data_map WHERE spr_id='".$row['id']."' AND usr_id='$this->usr_id' AND loop_id $loop_id AND (text NOT REGEXP '^[+\-]?[0-9]+$' OR text>=0);";
                                            $sql = sisplet_query($qu, 'obj');
                                            if($sql->cnt > 0)
						$isEmpty = false;
                                        }
                                        else{
                                            //$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' ";
                                            $sqlUserAnswerString ="SELECT COUNT(*) AS st FROM srv_data_map WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
                                            $sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
                                            if($sqlUserAnswer->st > 0)
                                                    $isEmpty = false;
                                        }
				break;
				
				case 27: //heatmap
					//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' ";
					$sqlUserAnswerString ="SELECT COUNT(*) AS st FROM srv_data_heatmap WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString, "obj");
					if($sqlUserAnswer->st > 0)
						$isEmpty = false;
				break;
				
				default:
					$isEmpty = false;
					//$isEmpty = true;
				break;
			}
			//echo "isEmpty na koncu: ".$isEmpty."</br>";
			if($isEmpty == true){
				return false;
			}
		}
        return true;
    }
	
	
	function displayIf($if=null){
		global $lang;
		//echo "</br> displayIf funkcija </br> ";
		$sql_if_string = "SELECT tip, number FROM srv_if WHERE id = '$if'";
		//echo "sql_if_string: ".$sql_if_string." </br>";
    	//$sql_if = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
    	$sql_if = sisplet_query($sql_if_string);
    	$row_if = mysqli_fetch_array($sql_if);
		//echo "tip: ".$row_if['tip']." </br>";

        // Blok
		if($row_if['tip'] == 1)
			$output = strtoupper($lang['srv_block']).' ';
		// Loop
		elseif($row_if['tip'] == 2)
			$output = strtoupper($lang['srv_loop']).' ';
		// IF
		else
			$output = 'IF ';

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
                    $sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
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
                        $sql2 = sisplet_query("SELECT v.variable AS variable FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    // multigrid
                    } elseif ($row['vre_id'] > 0) {
                        $sql2 = sisplet_query("SELECT g.variable AS variable FROM srv_condition_grid c, srv_grid g WHERE c.cond_id='$row[id]' AND c.grd_id=g.id AND g.spr_id='$row[spr_id]'");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    }

                    $output .= ']';

                // textbox in nubmer mata drugacne pogoje in opcije
                } elseif ($row2['tip'] == 4 ||$row2['tip'] == 21 || $row2['tip'] == 7 || $row2['tip'] == 22) {

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

		return $output;
	}

	/* poisce, ce ima podani element parenta, ki je loop
	* 
	*/
	function find_parent_loop ($element_spr=null, $element_if=0) {
		
		//$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='".$this->anketa['id']."'");
		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='".$this->anketa."'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);
		
		if ($row['parent'] == 0) return 0;
		
		$count = sisplet_query("SELECT COUNT(*) AS st FROM srv_if WHERE id = '$row[parent]' AND tip = '2'", "obj");
		if ($count->st > 0) {
            return $row['parent'];
        }else {
            return $this->find_parent_loop(0, $row['parent']);
        }
	}	
	
		 /**
    * poisce naslednjo vre_id v loopu
    * 
    */
    function findNextLoopId ($if_id=0) {
		if ($if_id == 0) {
			$sql = sisplet_query("SELECT if_id FROM srv_loop_data WHERE id='$this->loop_id'");
			$row = mysqli_fetch_array($sql);
			$if_id = $row['if_id'];
			$loop_id = $this->loop_id;
		} else{
			$loop_id = 0;
		}

		$sql = sisplet_query("SELECT spr_id, max FROM srv_loop WHERE if_id = '$if_id'");
		$row = mysqli_fetch_array($sql);
		$spr_id = $row['spr_id'];
		$max = $row['max'];
		
		$spr = Cache::srv_spremenljivka($spr_id);
		//echo "spr tip: ".$spr['tip']."</br>";


		if ($spr['tip'] == 2 || $spr['tip'] == 3 || $spr['tip'] == 9) {
			$data_vrednost = array();
			if($spr['tip'] == 9){
				if($this->usr_id){
					$sql1String = "SELECT vre_id FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->usr_id."' ";
				}else{
					$sql1String = "SELECT vre_id FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' ";
				}
			}
			else{
 				if($this->usr_id){
					$sql1String = "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->usr_id."'";
				}else{
					$sql1String = "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' ";
				}				
			}
			//echo "sql1String: ".$sql1String."</br>";
			$sql1 = sisplet_query($sql1String);
			while ($row1 = mysqli_fetch_array($sql1)) {
				$data_vrednost[$row1['vre_id']] = 1;
			}
			
			$vre_id = '';
			$i = 1;
			//$sql = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if_id'");

			$sql = sisplet_query("SELECT lv.vre_id AS vre_id, lv.tip AS tip FROM srv_loop_vre lv, srv_vrednost v WHERE lv.if_id='$if_id' AND lv.vre_id=v.id ORDER BY v.vrstni_red ASC");
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

			//Stari query srv_vrednost se ni nikjer uporabljal
			//$sql = sisplet_query("SELECT l.* FROM srv_loop_data l, srv_vrednost v WHERE l.if_id='$if_id' AND l.id > '$loop_id' AND l.vre_id IN ($vre_id) AND l.vre_id=v.id ORDER BY l.id ASC");
			//Problematicni sql, ker ocitno tale zadeva z obj ne dela najbolje
			//$sql = sisplet_query("SELECT id FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id' AND vre_id IN ($vre_id) ORDER BY id ASC", "obj");	
						
			$sqlTestString = "SELECT id FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id' AND vre_id IN ($vre_id) ORDER BY id ASC";
			//echo "sqlString: ".$sqlTestString."</br>";
			$sqlTest = sisplet_query($sqlTestString);
			$rowTest = mysqli_fetch_array($sqlTest);
			
			if (count($sql) > 0)
				return $rowTest['id'];
				//return $sql->id;
			else
				return null;
				
		// number
		} elseif ($spr['tip'] == 7) {
			
			//$sql1 = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->getUserId()."'");
			$sql1String = "SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->usr_id."'";
			//echo "sql1String: ".$sql1String."</br>";
			$sql1 = sisplet_query($sql1String);
			$row1 = mysqli_fetch_array($sql1);
			
			$num = (int)$row1['text'];

			//$countLoopManjsi = sisplet_query("SELECT COUNT(*) AS st FROM srv_loop_data WHERE if_id='$if_id' AND id <= '$loop_id'", "obj");
			$countLoopManjsiString = "SELECT COUNT(*) AS st FROM srv_loop_data WHERE if_id='$if_id' AND id <= '$loop_id'";
			$countLoopManjsi = sisplet_query($countLoopManjsiString);
			$rowcountLoopManjsi = mysqli_fetch_array($countLoopManjsi);
			//echo "countLoopManjsiString: ".$countLoopManjsiString."</br>";
			//if ($countLoopManjsi->st >= $num || ($countLoopManjsi->st >= $max && $max>0)) {
			if ($rowcountLoopManjsi['st'] >= $num || ($rowcountLoopManjsi['st'] >= $max && $max>0)) {
                return null;
            }

			//$loopVecji = sisplet_query("SELECT id FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id'", "obj");
			$loopVecjiString = "SELECT id FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id'";
			//echo "loopVecjiString: ".$loopVecjiString."</br>";
			$loopVecji = sisplet_query($loopVecjiString);
			$rowcountLoopVecji = mysqli_fetch_array($loopVecji);
			//if (count($loopVecji) > 0) {
			if (mysqli_num_rows($loopVecji) > 0) {
				//return $loopVecji->id;				
				return $rowcountLoopVecji['id'];
            }else {
                return null;
            }
			
		}
    }
	
		/**
    * @desc V podanem stringu poisce spremenljivke in jih spajpa z vrednostmi
    */
    function dataPiping ($text='') {
    	Common::getInstance()->Init($this->anketa);
        return Common::getInstance()->dataPiping($text, $this->usr_id, $this->loop_id);
	}
	
	#funkcija ki skrbi za encode dolocenih spornih delov besedila v latex-u prijazno
	function encodeTextHere($text='', $vre_id=0){
		global $site_path, $lang;
		
		//echo "Encoding ".$text."</br>";
		//echo "vre_id: ".$vre_id."</br>";
		
		$this->path2UploadedImages = $site_path.'uploadi/editor/';
		if($text == ''){	//ce ni teksta, vrni se
			return;			
		}
		$textOrig = $text;
		$findme = '<br />';
		$findmeLength = strlen($findme);
		$findImg = '<img';		
		$findImgLength = strlen($findImg);
		$findUl = '<ul';
		$findUlLength = strlen($findUl);
		$findLi = '<li';
		
		$findPar = '<p>';
		
		$pos = strpos($text, $findme);
		$posImg = strpos($text, $findImg);
		$posUl = strpos($text, $findUl);
		$posLi = strpos($text, $findLi);
		$posPar = strpos($text, $findPar);

        
		//ureditev posebnih karakterjev za Latex	http://www.cespedes.org/blog/85/how-to-escape-latex-special-characters, https://en.wikibooks.org/wiki/LaTeX/Special_Characters#Other_symbols
		$text = str_replace('\\','\textbackslash{} ',$text);
		//$text = str_replace('{','\{',$text);		
		//$text = str_replace('}','\}',$text);	
		$text = str_replace('$','\$ ',$text);
		$text = str_replace('#','\# ',$text);
		$text = str_replace('%','\% ',$text);		
		$text = str_replace('€','\euro',$text);		
		$text = str_replace('^','\textasciicircum{} ',$text);		
		$text = str_replace('_','\_ ',$text);	
		$text = str_replace('~','\textasciitilde{} ',$text);		
		$text = str_replace('&amp;','\&',$text);
		//$text = str_replace('&lt;','\textless ',$text);
		$text = str_replace('&lt;','\textless',$text);
		//$text = str_replace('&gt;','\textgreater ',$text);
		$text = str_replace('&gt;','\textgreater',$text);
		//ureditev posebnih karakterjev za Latex - konec
		
		//ureditev preureditve html kode ul in li v latex itemize
 		if($posUl !== false){			
			//echo "text prej: ".$text."</br>";
			$numOfUl = substr_count($text, $findUl);	//stevilo '<ul' v tekstu
			//echo "numOfUl ".$numOfUl."</br>";			
			######################
			//if($numOfUl!=0){
			if($numOfUl!=0 && $posLi !== false){	//ce imamo ul in li				
				$text = str_replace('<ul>','\begin{itemize} ', $text);
				$text = str_replace('<li>','\item ', $text);
				$text = str_replace('</ul>','\end{itemize} ', $text);					
			}
			//echo "prazno v html: ".strpos($text, '\r')."</br>";
			//echo "text potem: ".$text."</br>";
			######################
		}
		//ureditev preureditve html kode ul in li v latex itemize - konec
				
		if($posPar !== false){	//ce je kaksen html tag <p>, dodaj prazno vrstico oz. break			
			if($numOfUl!=0 && $posLi !== false){	//ce imamo ul in li	
				$divider = ' ';
			}else{
				$divider = ' \\ \\\\ ';
			}
			//$text = str_replace('<p>',' ', $text);
			//$text = str_replace('<p>',' \break ', $text);
			//$text = str_replace('<p>',' \\\\ ', $text);

			$text = str_replace('<p>', $divider, $text);
		}

 		if($pos === false && $posImg === false) {	//v tekstu ni br in img 		
			
			$text = preg_replace("/(\R){2,}/", "$1", $text);
			return strip_tags($text);
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
							//$text2Return .= $textPrej.' \break '.$textPotem;
							$text2Return .= $textPrej.' \\\\ '.$textPotem;
						}else{
							//$text2Return .= $textPrej.' \break ';
							$text2Return .= $textPrej.' \\\\ ';
						}
					}else{	//drugace
						$pos = strpos($textPotem, $findme);
						$textPrej = substr($textPotem, 0, $pos);
						$textPotem = substr($textPotem, $pos+$findmeLength);
						if($i == $numOfBr-1){
							//$text2Return .= $textPrej.' \break '.$textPotem;
							$text2Return .= $textPrej.' \\\\ '.$textPotem;
						}else{
							//$text2Return .= $textPrej.' \break ';
							$text2Return .= $textPrej.' \\\\ ';
						}
					}
				}
				$text = $text2Return;
			}			
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico - konec
			//echo "text potem: ".$text."</br>";
			$text = preg_replace("/(\R){2,}/", "$1", $text);
			return strip_tags($text);	//vrni tekst brez html tag-ov
		}
	}
	#funkcija ki skrbi za encode dolocenih spornih delov besedila v latex-u prijazno - konec
	
}