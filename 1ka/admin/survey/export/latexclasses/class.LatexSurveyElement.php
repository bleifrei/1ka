<?php

/**
 *
 *	Class ki skrbi za izris posameznega vprasanja za vprašalnik
 *
 *
 */


include('../../vendor/autoload.php');

define("MAX_STRING_LENGTH", 90);
define("LINE_BREAK_AT", '7 cm');
define("RADIO_BTN_SIZE", 0.13);
define("CHCK_BTN_SIZE", 0.13);
define("PIC_SIZE_ANS", "\includegraphics[width=3cm]"); 	//slika dolocene sirine
define("DROPDOWN_SIZE", 0.8);
define("VAS_SIZE", 0.04); //VAS_SIZE

 
class LatexSurveyElement{
	
	public $anketa;				// ID ankete
	public static $spremenljivka;
	public $spremenljivkaParams;
	public $stevilcenje;
	public $showIf = 0;			// izpis if-ov
	public $numbering = 0; 		// ostevillcevanje vprasanj
	public $texNewLine = '\\\\ ';
	//public $texNewLine = '\newline ';
	public $export_format;
	public $fillablePdf;
	public $questionText;
	protected $usr_id = null;			// id userja ki je odgovarjal (na katerega so vezani podatki)
	protected $db_table = '';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	protected $userAnswer = array();
	protected $texBigSkip = '\bigskip';
	//protected $userDataPresent = array();
	//protected $userDataPresent = 0;
	
	protected $skipEmpty = 0; 	// izpusti vprasanja brez odgovora
	protected $skipEmptySub = 0; 	// izpusti podvprasanja brez odgovora
	protected $path2Images;
	protected $path2UploadedImages;
	protected $language;
	protected $prevod;
	protected $admin_type;
	protected $variableName;
	
	
	function __construct($anketa=null, $export_format='', $fillablePdf=null, $usr_id=null, $export_subtype='', $language=null){
		global $site_path, $global_user_id, $admin_type, $lang;
		
		$this->anketa = $anketa;
		$this->path2Images = $site_path.'admin/survey/export/latexclasses/textemp/images/';
		$this->path2UploadedImages = $site_path.'uploadi/editor/';
		$this->path2UrlImages = $site_path.'uploadi/editor/';

		$this->admin_type = $admin_type;
		
/* 		$this->spremenljivka = $spremenljivka;
		$this->stevilcenje = $stevilcenje; */
		if($export_subtype=='q_empty'||$export_subtype=='q_comment'){
			$this->showIf = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_show_if');
			$this->numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_numbering');
		}elseif($export_subtype=='q_data'||$export_subtype=='q_data_all'){
			$this->showIf = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_if');
			$this->numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_numbering');
			$this->skipEmpty = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty');
			$this->skipEmptySub = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty_sub');
		}
		
		$this->export_format = $export_format;
		$this->fillablePdf = $fillablePdf;
		
		//$this->usr_id = $_GET['usr_id'];
		$this->usr_id = $usr_id;
		
		if ($this->usr_id  != '') {
			$sqlL = sisplet_query("SELECT language FROM srv_user WHERE id = '$this->usr_id ' AND ank_id='$this->anketa' ");
			$rowL = mysqli_fetch_array($sqlL);
			$this->language = $rowL['language'];			
		}

		//preverjanje, ali je prevod
		if(isset($_GET['language'])){
			$this->language = $_GET['language'];
			$this->prevod = 1;
		}else{
			$this->prevod = 0;
		}
		//preverjanje, ali je prevod - konec

		//if($language!=-1){ //ce ni default jezik, ampak je prevod
		if($this->prevod){ //ce ni default jezik, ampak je prevod
			$this->language = $language;			
		}

		if ( SurveyInfo::getInstance()->SurveyInit($anketa))
		{
			SurveyUserSetting::getInstance()->Init($anketa, $global_user_id);
			
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
				$this->db_table = '_active';
		}		
		else{
			return false;			
		}
		
	}
	
	
	#funkcija, ki pripravi latex kodo za prikazovanje besedila vprasanja ############################################################################
	public function displayQuestionText($spremenljivke=null, $zaporedna=null, $export_subtype='', $preveriSpremenljivko=null, $loop_id=null, $export_data_type=''){		
		$tex = '';
		$userDataPresent = null; //dodal definicijo spremenljivke zaradi intellisense napake
		//echo "Funkcija displayQuestionText </br>";
		self::$spremenljivka = $spremenljivke['id'];
		$row = Cache::srv_spremenljivka($spremenljivke['id']);
		$this->spremenljivkaParams = new enkaParameters($row['params']);

		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		#pridobitev podatkov o odgovorih respondenta na trenutno vprasanje
		if($export_subtype!='q_empty'){
			if( in_array($spremenljivke['tip'], array(1, 2, 3)) ){	//ce je radio,checkbox ali roleta
			//if( in_array($spremenljivke['tip'], array(1, 2, 3)) && $spremenljivke['orientation']!=5){
				//echo "orientation: ".$spremenljivke['orientation'];
				$userDataPresent = $this->GetUsersData($this->db_table, $spremenljivke['id'], $spremenljivke['tip'], $this->usr_id, $this->loop_id);
			}elseif( in_array($spremenljivke['tip'], array(6, 16, 19, 20)) ){	//ce je multigrid radio, checkbox, besedilo ali stevilo
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other, spr_id FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");			
				//echo "Funkcija displayQuestionText </br>";
				//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
					$indeksZaWhile = 1;
					//echo "rowVrednost['id']: ".$rowVrednost['id'].'</br>';
					$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY 'vrstni_red'");
					while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){						
						$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $this->db_table, $rowVrednost, $rowVsehVrednosti, $this->usr_id, 0, $this->loop_id);

						$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
						if($rowVsehVrednosti['id'] == $userAnswer['grd_id']){
							$indeksZaWhile++;
						}
						if($indeksZaWhile!=1){
							$userDataPresent = 1;
						}
					}
				}
			//}elseif(in_array($spremenljivke['tip'], array(21, 7, 8, 18, 17, 26, 27))){	//ce je besedilo ali stevilo ali datum ali vsota
			}elseif(in_array($spremenljivke['tip'], array(21, 4, 7, 8, 18, 17, 26, 27))){	//ce je besedilo ali staro besedilo (4) ali stevilo ali datum ali vsota
				$userDataPresent = $this->GetUsersData($this->db_table, $spremenljivke['id'], $spremenljivke['tip'], $this->usr_id, $this->loop_id);
			}elseif($spremenljivke['tip']==24){	//ce je kombinirana tabela
				//GetUsersDataKombinirana($spremenljivke, $db_table, $usr_id)
				$questionText=1;
				$indeksPolja = 0;
				$userDataPresentArray = $this->GetUsersDataKombinirana($spremenljivke, $this->db_table, $this->usr_id, $questionText, $this->loop_id);
				if (is_array($userDataPresentArray)){
					//echo "je polje".'</br>';
					$userDataPresent=0;
					//echo "Dolzina polja: ".count($userDataPresentArray)."</br>";
					//echo "Id: ".$spremenljivke['id']."</br>";
					foreach($userDataPresentArray as $key=>$value){
						if($key==$indeksPolja){
							if($value!=''){
								$userDataPresent=1;
							}
							//echo "Podatek z indeksom ".$key." je :".$value."</br>";
							$indeksPolja++;
						}
					}
				}else{
					if($userDataPresent!=0){
						$userDataPresent=1;
					}else{
						$userDataPresent=0;
					}
				}
			}
		}
		//echo "userDataPresent za spremenljivko ".$spremenljivke['id'].': '.$userDataPresent.'</br>';
		//echo "Preveri spremenljivko: $preveriSpremenljivko</br>";
		#pridobitev podatkov o odgovorih respondenta na trenutno vprasanje - konec ####################################		
				
		if(($export_subtype=='q_empty')||($export_subtype=='q_comment')||(($export_subtype=='q_data'||$export_subtype=='q_data_all')&&($userDataPresent!=0||$preveriSpremenljivko))){	//ce je prazen vprasalnik ali (je vprasalnik poln in (so podatki prisotni ali je potrebno pokazati vprasanje tudi, ce ni podatkov))
			$rowl = $this->srv_language_spremenljivka($spremenljivke);
			if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];
			if (strip_tags($rowl['info']) != '') $spremenljivke['info'] = $rowl['info'];
			
			#Pridobimo tekst vprasanja#################################################################################	
			
			$sqlVrstic = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."'");
			$rowVrstic = mysqli_fetch_row($sqlVrstic);
			$visina = round(($rowVrstic[0]+2) * 8);
			
			//$linecount_vprasanja = $this->pdf->getNumLines($spremenljivke['naslov'], $this->pdf->getPageWidth());
			
			//$tex = $spremenljivke['naslov'];		
			######################################### Pridobimo tekst vprasanja - konec
			
			#Stevilcenje vprasanj###############################################################
			//$numberingText = ($this->numbering == 1) ? $spremenljivke['variable'].' - ' : '';		
			$numberingText = ($this->numbering == 1) ? $this->encodeText($spremenljivke['variable']).' - ' : '';			
			######################################### Stevilcenje vprasanj - konec		
			//echo "goli naslov: ".$spremenljivke['naslov']."</br>";

			//belezenje imena spremenljivke, zaradi GDPR vprasanja
			$this->variableName =$spremenljivke['variable'];
			//belezenje imena spremenljivke, zaradi GDPR vprasanja - konec

			#Izris stevilke in besedila vprasanja ter IF ali BLOK, ce so prisotni ###############################################
			//$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
			$text = strip_tags($numberingText . $spremenljivke['naslov'], '<a><img><ul><li><ol><br><p>');	//je potrebno spustiti <p>, zaradi GDPR vprasanja
			
			//echo "naslov: ".$text."</br>";
			//$tex = $text." ".$texNewLine;
			if( !in_array($spremenljivke['tip'], array(1, 2, 3, 4, 7, 8, 6, 16, 19, 20, 21, 17, 18, 24, 26, 27)) ){	//ce ni radio, check, roleta, stevilo, datum, multigrid radio, checkbox, besedilo, stevilo, razvrscanje, vsota ali kombinirana tabela, lokacija, ali heatmap
				$tex .= ($this->export_format == 'pdf' ? '\\begin{absolutelynopagebreak} \\noindent ' : ' ');	//ce je pdf uredimo, da med vprasanji ne bo prelomov strani
			}
				
				#Izpis if-ov pri vprasanju#########################################################		
				if($this->showIf == 1){
					
					// TODO: Stara koda za iskanje po branchingu (briši, če je vse ok)
					//$b = new Branching($this->anketa);
					//$parents = $b->get_parents($spremenljivke['id']);
                    //$parents = explode('p_', $parents);
                    //foreach ($parents AS $key => $val) {
                    //    if ( is_numeric(trim($val)) ) {
                    //        $parents[$key] = (int)$val;
                    //    } else {
                    //        unset($parents[$key]);
                    //    }
					//}
					
					/* $b = new Branching($this->anketa);
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
						$tex .= $this->displayIf($if);
						$tex .= $this->texNewLine;
					}*/
					###### stara koda, ki je delavala


                    // Po novem izpisemo pred vsakim vprasanjem vse ife znotraj katerih se nahaja
                    Cache::cache_all_srv_branching($this->anketa);
					$parents = Cache::srv_branching($spremenljivke['id'], 0)['parent'];
					if($parents){
						$tex .= $this->displayIf($parents);
						$tex .= $this->texNewLine;
					}
					#preuredil kodo, da zadeva deluje tako kot ta stara, ki se nahaja nad tem

				}				
				######################################### Izpis if-ov pri vprasanju - konec

			//$tex .= '\textbf{'.$text.'} '.$texNewLine;	//izris besedila vprasanja
			
			if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je izpis odgovorov
				$text = $this->dataPiping($text);	//pokazi odgovore po zanki
			}			

			$tex .= ' \noindent '; //dodal pred vsakim tekstom vprasanja, da ni indent-a

			if($spremenljivke['orientation']==0){ //ce je vodoravno ob vprasanju
				//if($spremenljivke['tip'] == 21){ //ce je besedilo (vodoravno ob vprasanju)
				if($spremenljivke['tip'] == 21 || $spremenljivke['tip'] == 4){ //ce je besedilo (vodoravno ob vprasanju)
					$tex .= ' \par { ';	//dodaj zacetek odstavka, ki je pomemben za pravile izpis
				}
			}

			$tex .= '\textbf{'.$this->encodeText($text).'} ';	//izris besedila vprasanja
			
			$this->questionText = $text;	//zabelezimo tekst vprasanja, ki ga potrebujemo kasneje			
			
			#Izris stevilke in besedila vprasanja ter IF ali BLOK, ce so prisotni - konec ###############################################
			
			#Izris opombe ###############################################################################
			if($spremenljivke['orientation']!=0){	//ce ni vodoravno ob vprasanju,

				//ce imamo opombo, jo izpisi
				if($spremenljivke['info'] != ''){
					$tex .= $this->texNewLine;	
					$tex .= '\vspace{2 mm}';					
					//$tex .= ' {\noindent \\footnotesize '.$this->encodeText($spremenljivke['info']).'}';
					$tex .= ' \noindent \\footnotesize '.$this->encodeText($spremenljivke['info']).'  \\normalsize ';
				}

				if( !in_array($spremenljivke['tip'], array(4, 6, 16, 19, 20, 21, 7, 8, 18)) ){	//ce ni multigrid radio, checkbox, besedilo, stevilo, datum ali vsota ki ne potrebujejo prazne vrstice zaradi uporabe tabele
					$tex .= $this->texNewLine;
				}

				
				if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je izpis odgovorov					
					if($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz					
						if( in_array($spremenljivke['tip'], array(4, 6, 16, 19, 20, 21)) ){	//ce je multigrid radio, checkbox, besedilo ali stevilo in je Navaden ali Kratek izpis
							$tex .= $this->texNewLine;						
						}
					}else{
						if( in_array($spremenljivke['tip'], array(4, 21)) ){	//ce je besedilo
							$tex .= $this->texNewLine;						
						}
					}
				}
				
				####################
/* 				if( !in_array($spremenljivke['tip'], array(6, 16, 19, 20, 21, 7)) ){	//ce ni multigrid radio, checkbox, besedilo ali stevilo
					
					//preveri, ce je itemize v besedilu in ustrezno uredi prazno vrstico					
					$findItemize = '\begin{itemize}';
					$posItemize = strpos($tex, $findItemize);	//v trenutni tex kodi najdi prisotnost besedila za itemize					
					if($posItemize === false){	//ce ni besedila itemize, dodaj prazno vrstico
						//pejdi v novo vrstico
						$tex .= $this->texNewLine;
					}
					//preveri, ce je itemize v besedilu in ustrezno uredi prazno vrstico - konec
				}
				
				if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je izpis odgovorov
					if( in_array($spremenljivke['tip'], array(6, 16, 19, 20, 21, 7)) ){	//ce je multigrid radio, checkbox, besedilo ali stevilo
						$tex .= $this->texNewLine;
						//echo "ni vodoravno ob vprašanju in je število ali besedilo </br>";
					}
				}
				
				//ce imamo opombo, jo izpisi
				if($spremenljivke['info'] != ''){
 					if( in_array($spremenljivke['tip'], array(6, 16, 19, 20, 21, 7)) ){	//ce je multigrid radio, checkbox, besedilo ali stevilo
						$tex .= $this->texNewLine;
						echo "ni vodoravno ob vprašanju in je število ali besedilo </br>";
					}
					//$tex .= $this->texNewLine;	
					$tex .= '\vspace{2 mm}';					
					$tex .= ' {\indent \\footnotesize '.$this->encodeText($spremenljivke['info']).'}';					
					if( !in_array($spremenljivke['tip'], array(6, 16, 19, 20, 21, 7)) ){	//ce ni multigrid radio, checkbox, besedilo ali stevilo
						$tex .= $this->texNewLine;						
					}
				} */
				####################
			}else{	//ce je vodoravno ob vprasanju				
				//ce imamo opombo, jo izpisi
				if($spremenljivke['info'] != ''){
					//pejdi v novo vrstico
					$tex .= $this->texNewLine;
					$tex .= '\vspace{2 mm}';
					//$tex .= ' {\indent \\footnotesize '.$this->encodeText($spremenljivke['info']).'} ';
					$tex .= ' {\noindent \\footnotesize '.$this->encodeText($spremenljivke['info']).' \\normalsize } ';
				}

				if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je izpis odgovorov
					if(!in_array($spremenljivke['tip'], array(8))){	//ce ni datum
						$tex .= $this->texNewLine;	//dodaj prazno vrstico
					}					
				}
			}
			#Izris opombe - konec #########################################################################		
			
			#ce vprasanje nima moznih odgovorov, je potrebno zakljuciti environment (absolutelynopagebreak) pri pdf
			//echo $rowVrstic[0]."za spremenljivko: ".$spremenljivke['tip']."</br>";
			if($rowVrstic[0]==0 && (in_array($spremenljivke['tip'], array(1, 2, 3, 6, 16, 17, 20, 9, 19, 17))) ){
				if($this->export_format == 'pdf'){	//ce je pdf
					if($spremenljivke['orientation']==0 || $spremenljivke['orientation']==2){	//ce sta vodoravni orientaciji						
						$tex .= $this->texNewLine;	//dodaj na koncu vprasanja prazno vrstico
					}
					$tex .= $this->texBigSkip;
					$tex .= $this->texBigSkip;
					$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
				}else{	//ce je rtf
					if($spremenljivke['orientation']==0 || $spremenljivke['orientation']==2){	//ce sta vodoravni orientaciji						
						$tex .= $this->texNewLine;	//dodaj na koncu vprasanja prazno vrstico
					}
					$tex .= $this->texBigSkip;
					$tex .= $this->texBigSkip;
				}
			}
			#ce vprasanje nima moznih odgovorov, je potrebno zakljuciti environment (absolutelynopagebreak) pri pdf - konec			
		}
		//echo "tex: ".$tex."</br>";
		return $tex;
	}
	#funkcija, ki pripravi latex kodo za prikazovanje besedila vprasanja - konec ############################################################################
	
	
	#funkcija, ki pripravi latex kodo za prikazovanje moznih odgovorov glede na tip vprasanja################################################################
	public function displayAnswers($spremenljivke=null, $export_subtype='', $preveriSpremenljivko=null, $export_data_type='', $loop_id=null){		
		
		switch ( $spremenljivke['tip'] )
		{
			case 1: //radio
			case 2: //check
			case 3: //select -> radio
				return RadioCheckboxSelectLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $preveriSpremenljivko, $export_data_type, $export_subtype, $loop_id, $this->language);
			break;
			case 6: //multigrid
			case 16:// multicheckbox
			case 19:// multitext
			case 20:// multinumber
				return MultiGridLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $this->skipEmptySub, $export_data_type, $this->skipEmpty, $loop_id, $this->language);
			break;
			case 21: //besedilo
				return BesediloLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $this->anketa, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;
			case 4: //besedilo staro
				return BesediloLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $this->anketa, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;
			case 7: //stevilo
				return SteviloLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;
			case 8:	//datum
				return DatumLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 17: //ranking
				return RazvrscanjeLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;
			case 18: //vsota
				return VsotaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 24: // kombinirana tabela
				return GridMultipleLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;			
			case 26: //lokacija
				return LokacijaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 27: //heatmap
				return HeatmapLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 5:	//nagovor
				return NagovorLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 22: //kalkulacija
				return KalkulacijaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->fillablePdf, $this->texNewLine, $export_subtype, $this->db_table, $this->getUserId(), $loop_id);
			break;
			case 25: //kvota
				return KvotaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->fillablePdf, $this->texNewLine, $export_subtype, $this->db_table, $this->anketa, $this->getUserId(), $loop_id);
			break;
			case 9: //SN-imena
				return SNImenaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->fillablePdf, $this->texNewLine, $export_subtype, $this->db_table, $this->anketa, $this->getUserId(), $loop_id);
			break;
		}
	}
	#funkcija, ki pripravi latex kodo za prikazovanje moznih odgovorov glede na tip vprasanja - konec #######################################################

	/**
	 * prevod za srv_spremenljivka
	 */
	 function srv_language_spremenljivka ($spremenljivka=null) {
		 
		// if ($this->language != -1) {
		if ($this->prevod) {
			$sqll = sisplet_query("SELECT naslov, info FROM srv_language_spremenljivka WHERE ank_id='".$this->anketa."' AND spr_id='".$spremenljivka['id']."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			return $rowl;
		 }
		
		return false;
	 }
	 
	function displayIf($if=null){
		global $lang;
		//echo "funckija za if </br>";
    	$sql_if = sisplet_query("SELECT tip FROM srv_if WHERE id = '$if'");
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
      
		$sql_if = sisplet_query("SELECT number, label FROM srv_if WHERE id = '$if'");
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
                    $sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    $row1 = mysqli_fetch_array($sql1);
                } else
                    $row1 = null;

                $output .= $this->encodeText($row1['variable']);
                // radio, checkbox, dropdown in multigrid
                if (($row2['tip'] <= 3 || $row2['tip'] == 6) && ($row['spr_id'] || $row['vre_id'])) {

                    if ($row['operator'] == 0)
                        $output .= ' = ';
                    else
                        $output .= ' != ';

                    $output .= '[';

                    // obicne spremenljivke
                    if ($row['vre_id'] == 0) {
                        $sql2 = sisplet_query("SELECT v.variable as variable FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    // multigrid
                    } elseif ($row['vre_id'] > 0) {
                        $sql2 = sisplet_query("SELECT g.variable as variable FROM srv_condition_grid c, srv_grid g WHERE c.cond_id='$row[id]' AND c.grd_id=g.id AND g.spr_id='$row[spr_id]'");

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
			
		return $output;
	}
	
	#funkcija, ki skrbi za izbiro radio, checkbox ali ostale simbole, ki so potrebni za izris odgovorov #############################################################
	function getAnswerSymbol($export_format='', $fillablePdf=null, $spremenljivkeTip=null, $spremenljivkeGrids=null, $numOfMissings=null, $data=null, $enota='', $indeksVASIcon='', $VASNumberRadio='', $spremenljivkeId=null){
		$tip=$spremenljivkeTip;
		global $site_path;
		$this->path2Images = $site_path.'admin/survey/export/latexclasses/textemp/images/';
		//echo $tip;
		//$numGrids=$spremenljivke['grids'];
		$numGrids=$spremenljivkeGrids;
		//echo "Data:".($data)."</br>";		
		//echo "DataCount:".count($data)."</br>";
		//if($tip==21||$tip==8){	//ce je besedilo ali datum,
		if($tip==21||$tip==4||$tip==8){	//ce je besedilo ali datum,
			$tip=2; //naj se pobere checkbox
		}
				
		if( ($export_format=='pdf'&&$fillablePdf==0)||$export_format=='rtf'){//ce je navaden pdf ali rtf dokument (brez moznosti izbire ali vnosa v polja)

			if($data){
				$data = $this->encodeText($data);
			}

			if($tip==1||$tip==6){	//radio ali multigrid z radio
				if($data){	//ce je odgovor respondenta
					if($enota!=11&&$enota!=12){	//ce ni VAS ali slikovni tip
						$radioButtonTex = ($export_format=='pdf'?"{\\radio}" : "\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio2}");	//\radio je newcommand
					}elseif($enota==11){ //drugace, ce je VAS
						if($tip==1){
							$VASNumber = $VASNumberRadio;
						}else{
							$spremenljivkeGrids = $spremenljivkeGrids - 1;
							$VASNumber = $spremenljivkeGrids;
						}
						$indeksVASIcon = $indeksVASIcon - 1;
						$radioButtonTex = [];
						if($VASNumber>1){						
							switch ($VASNumber) {
								case 1:
									$radioButtonTex = "";
									break;
								case 2:
									$arrayVAS = ['vas3checked', 'vas5checked'];
 									foreach($arrayVAS AS $VAS){										
										//$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
								case 3:
									$arrayVAS = ['vas3checked', 'vas4checked', 'vas5checked'];
									foreach($arrayVAS AS $VAS){									   
									   $radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
								   }
								   break;
								case 4:
									$arrayVAS = ['vas2checked', 'vas3checked', 'vas5checked', 'vas6checked'];
									foreach($arrayVAS AS $VAS){									   
									   $radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
								   }
								   break;
								case 5:
									$arrayVAS = [ 'vas2checked', 'vas3checked', 'vas4checked', 'vas5checked', 'vas6checked'];
 									foreach($arrayVAS AS $VAS){										
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
								case 6:
									$arrayVAS = ['vas1checked', 'vas2checked', 'vas3checked', 'vas5checked', 'vas6checked', 'vas7checked'];
 									foreach($arrayVAS AS $VAS){										
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
								case 7:
									$arrayVAS = ['vas1checked', 'vas2checked', 'vas3checked', 'vas4checked', 'vas5checked', 'vas6checked', 'vas7checked'];
 									foreach($arrayVAS AS $VAS){										
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
							}
							return $radioButtonTex[$indeksVASIcon];	//$indeksVASIcon
						}
					}elseif($enota==12){ //drugace, ce je slikovni tip
						$prviOdgovorSlikovniTip = 1;
						$radioButtonTex = ICON_SIZE."{".$this->path2Images."".$this->getCustomRadioSymbol($spremenljivkeId, $prviOdgovorSlikovniTip)."}";
					}
				}else{
					if($enota!=11&&$enota!=12){	//ce ni VAS ali slikovni tip
						$radioButtonTex = ($export_format=='pdf'?"{\Large $\ocircle$}" : "\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio}");
					}elseif($enota==11){ //drugace, ce je VAS
						if($tip==1){
							$VASNumber = $VASNumberRadio;
						}else{
							$spremenljivkeGrids = $spremenljivkeGrids - 1;
							$VASNumber = $spremenljivkeGrids;
						}						
						$indeksVASIcon = $indeksVASIcon - 1;
						$radioButtonTex = [];
						if($VASNumber>1){						
							switch ($VASNumber) {
								case 1:
									$radioButtonTex = "";
									break;
								case 2:
									$arrayVAS = ['vas3', 'vas5'];
 									foreach($arrayVAS AS $VAS){
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
								case 3:
									$arrayVAS = ['vas3', 'vas4', 'vas5'];
									foreach($arrayVAS AS $VAS){
									   $radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
								   }
								   break;
								case 4:
									$arrayVAS = ['vas2', 'vas3', 'vas5', 'vas6'];
									foreach($arrayVAS AS $VAS){
									   $radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
								   }
								   break;
								case 5:
									$arrayVAS = [ 'vas2', 'vas3', 'vas4', 'vas5', 'vas6'];
 									foreach($arrayVAS AS $VAS){
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
								case 6:
									$arrayVAS = ['vas1', 'vas2', 'vas3', 'vas5', 'vas6', 'vas7'];
 									foreach($arrayVAS AS $VAS){										
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
								case 7:
									$arrayVAS = ['vas1', 'vas2', 'vas3', 'vas4', 'vas5', 'vas6', 'vas7'];
 									foreach($arrayVAS AS $VAS){
										$radioButtonTex[] = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
									}
									break;
							}
							return $radioButtonTex[$indeksVASIcon];	//$indeksVASIcon
						}							
							
					}elseif($enota==12){
						$prviOdgovorSlikovniTip = 0;
						$radioButtonTex = ICON_SIZE."{".$this->path2Images."".$this->getCustomRadioSymbol($spremenljivkeId, $prviOdgovorSlikovniTip)."}";				
					}
				}
				return $radioButtonTex;
			}else if($tip==2||$tip==16){	//checkbox ali multigrid s checkbox
				if($data){
					$checkboxTex = ($export_format=='pdf'?'{\Large \CheckedBox}' : "\\includegraphics[scale=".CHCK_BTN_SIZE."]{".$this->path2Images."checkbox2}");
				}else{
					$checkboxTex = ($export_format=='pdf'?'{\Large \Square}' : "\\includegraphics[scale=".CHCK_BTN_SIZE."]{".$this->path2Images."checkbox}");
				}
				return $checkboxTex;
			}elseif($tip==19||$tip==20){	//multi text ali multi number
				$textboxWidth = 0.5/($numGrids+$numOfMissings);	//sirina praznega textbox-a				
				//priprava latex kode za prazen text box dolocene sirine in visine glede na export format
				if($export_format == 'pdf'){
					if($data!=''){						
						$TextBoxWithText = ' \\textcolor{crta}{\footnotesize{'.$data.'}} ';
						//$TextBoxWithText = ' \\textcolor{crta}{\footnotesize{'.$this->encodeText($data).'}} ';
						$emptyTextBox = $TextBoxWithText;						
					}else{						
						if($this->export_subtype == 'q_empty' || $this->export_subtype == 'q_comments'){
							$emptyTextBox = ' \fbox{\parbox{'.$textboxWidth.'\textwidth}{ \hphantom{\hspace{'.$textboxWidth.'\textwidth}} }} ';
						}else{
							$emptyTextBox = ' ';
						}
						
					}
				}elseif($export_format == 'rtf'){
					if($data!=''){						
						$TextBoxWithText = ' '.$data.' ';
						$emptyTextBox = $TextBoxWithText;
					}else{
						if($this->export_subtype == 'q_empty' || $this->export_subtype == 'q_comments'){
							$emptyTextBox =' \fbox{\parbox{'.$textboxWidth.'\textwidth}{ \hphantom{\hspace{'.$textboxWidth.'\textwidth}} }} ';
						}else{
							$emptyTextBox = ' ';
						}						
					}
				}
				return $emptyTextBox;
			}
		}else if($export_format=='pdf'&&$fillablePdf==1){//ce je pdf dokument, kjer je mozno vpisati v polja
			$radioButtonTex ="{\Large $\ocircle$}";
			$checkboxTex ='{\Large \Square}';	
		}		
	}
	#funkcija, ki skrbi za izbiro radio, checkbox ali ostale simbole, ki so potrebni za izris odgovorov - konec #####################################################
	
	#funkcija, ki ureja pretvorbo stevilskega ID vprasanja v "crkovsko" identifikacijo, ker Latex ne podpira imen s stevilkami ######################################
	function UrediOznakoVprasanja($sprId=null){
		$sprId = (string) $sprId;
		$sprIdArray = str_split($sprId);
		$temp='';		
		foreach($sprIdArray as $data){
			$temp .= chr($data+65);
		}		
		return $temp;		
	}
	#funkcija, ki ureja pretvorbo stevilskega ID vprasanja v "crkovsko" identifikacijo, ker Latex ne podpira imen s stevilkami - konec #############################
		
	#funkcija ki skrbi za encode dolocenih spornih delov besedila v latex-u prijazno
	function encodeText($text='', $vre_id=0, $naslovStolpca = 0){
		global $site_path, $lang;
		//$text = str_replace(' ','X',$text);	//nadomesti presledke
		//echo "Encoding ".$text."</br>";
		//echo "vre_id: ".$vre_id."</br>";
		//echo "ime spremenljivke ".$this->variableName."</br>";
				
		$text = htmlspecialchars_decode($text);		//vse html special chars kot je &amp; spremeni v ustrezne simbole (npr. &amp;=>&) 
		
		//resevanje razbirajanja predolgih neprekinjenih besed in URL - spremenljivke za kasnejsi prilagojen izpis
		//$numOfWords = str_word_count($text, 0);
		$numOfSpacesPrej = substr_count($text, ' '); //stevilo presledkov v besedilu
		$stringLength = strlen($text);

		$findSpace = ' ';
		$posSpace1 = strpos($text, $findSpace);	//najdi pozicijo prvega presledka v besedilu
		$posSpace2 = strripos($text, $findSpace);	//najdi pozicijo zadnjega presledka v besedilu
		
		
		$findHttp = 'http://';
		$findHttps = 'https://';
		$posHttp = strpos($text, $findHttp);
		$posHttps = strpos($text, $findHttps);
		$isURL = 0;
		/* if($posHttp !== false || $posHttps !== false) {	//imamo URL naslov			
			$isURL = 1;
		} */
		//resevanje razbirajanja predolgih neprekinjenih besed in URL - konec
		
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
		$findOl = '<ol';		
		$findLi = '<li';
		
		$findPar = '<p>';
		
		$pos = strpos($text, $findme);
		$posImg = strpos($text, $findImg);
		$posUl = strpos($text, $findUl);
		$posOl = strpos($text, $findOl);
		$posLi = strpos($text, $findLi);
		$posPar = strpos($text, $findPar);

		//echo "pozicija paragrafa: $posPar </br>";

		//ureditev izrisa slike
		if($posImg !== false){
			$numOfImgs = substr_count($text, $findImg);	//stevilo '<img	' v tekstu
			$posImg = strpos($text, $findImg);
			$textPrej = '';
			$textPotem = '';			
			for($i=0; $i<$numOfImgs; $i++){				
				$posImg = strpos($text, $findImg);
				$textPrej = substr($text, 0, $posImg);	//tekst do img
				$textPotem = substr($text, $posImg);	//tekst po img, z vkljuceno hmlt kodo z img
				$posImgEnd = strpos($textPotem, '/>');	//pozicija, kjer se konca html koda za img
				$textPotem = substr($textPotem, $posImgEnd+strlen('/>'));	//tekst od konca html kode za img dalje

				//$text = $textPrej.' '.PIC_SIZE_ANS."{".$this->path2UploadedImages."".$this->getImageName($text, 0, '<img')."}".' '.$textPotem;				
				//$text = $textPrej.' '.PIC_SIZE_ANS."{".$this->path2UploadedImages."".$this->getImageName($text, 0, '<img', $vre_id)."}".' '.$textPotem;
				$imageName = $this->path2UploadedImages."".$this->getImageName($text, 0, '<img', $vre_id);
				$imageNameTest = $imageName.'.png';	//za preveriti, ali obstaja slikovna datoteka na strezniku
				//error_log("iz survey element: ".$imageNameTest);
				//echo("iz survey element: ".$imageNameTest."</br>");
				if(filesize($imageNameTest) > 0){
					$text = $textPrej.' '.PIC_SIZE_ANS."{".$imageName."}".' '.$textPotem;
				}else{
					$image = $lang['srv_pc_unavailable'];
					$text = $textPrej.' '.$image.' '.$textPotem;
				}				
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
		$text = str_replace('_','\_ ',$text);	
		$text = str_replace('~','\textasciitilde{} ',$text);		
		if(strpos($text, '&amp;')){	//ce je prisotno v besedilu &amp;'
			$text = str_replace('&amp;','\& ',$text);	
		}else{
			$text = str_replace('&','\& ',$text);				
		}
		$text = str_replace('&nbsp;','~',$text);
		//$text = str_replace('&lt;','\textless ',$text);
		$text = str_replace('&lt;',' \textless ',$text);
		//$text = str_replace('&gt;','\textgreater ',$text);
		$text = str_replace('&gt;',' \textgreater ',$text);
		//ureditev posebnih karakterjev za Latex - konec

		//ureditev grskih crk
		$text = str_replace('α','\textalpha ',$text);
		$text = str_replace('β','\textbeta ',$text);
		$text = str_replace('γ','\textgamma ',$text);
		$text = str_replace('δ','\textdelta ',$text);
		$text = str_replace('ε','\textepsilon ',$text);
		$text = str_replace('ζ','\textzeta ',$text);
		$text = str_replace('η','\texteta ',$text);
		$text = str_replace('θ','\texttheta ',$text);
		$text = str_replace('ι','\textiota ',$text);
		$text = str_replace('κ','\textkappa ',$text);
		$text = str_replace('λ','\textlambda ',$text);
		$text = str_replace('μ','\textmugreek ',$text);
		$text = str_replace('ν','\textnu ',$text);
		$text = str_replace('ξ','\textxi ',$text);
		//$text = str_replace('ο','\textomikron ',$text);
		$text = str_replace('π','\textpi ',$text);
		$text = str_replace('ρ','\textrho ',$text);
		$text = str_replace('σ','\textsigma ',$text);
		$text = str_replace('τ','\texttau ',$text);
		$text = str_replace('υ','\textupsilon ',$text);
		$text = str_replace('φ','\textphi ',$text);
		$text = str_replace('χ','\textchi ',$text);
		$text = str_replace('ψ','\textpsi ',$text);
		$text = str_replace('ω','\textomega ',$text);
		//ureditev grskih crk - konec

		//ureditev preureditve html kode ul in li v latex itemize
 		if($posUl !== false){			
			//echo "text prej: ".$text."</br>";
			$numOfUl = substr_count($text, $findUl);	//stevilo '<ul' v tekstu
			//echo "numOfUl ".$numOfUl."</br>";			
			######################
			//if($numOfUl!=0){
			if($numOfUl!=0 && $posLi !== false){	//ce imamo ul in li				
				$text = str_replace('<ul>','\begin{itemize} ', $text);
				$text = str_replace('<ul','\begin{itemize} ', $text);
				$text = str_replace('<li>','\item ', $text);
				$text = str_replace('</ul>','\end{itemize} \ ', $text);					
			}
			//echo "prazno v html: ".strpos($text, '\r')."</br>";
			//echo "text potem: ".$text."</br>";
			######################
		}
		//ureditev preureditve html kode ul in li v latex itemize - konec

		//ureditev preureditve html kode ol in li v latex enumerate, ki je ostevilcen
		if($posOl !== false){			
			//echo "text prej: ".$text."</br>";
			$numOfOl = substr_count($text, $findOl);	//stevilo '<ol' v tekstu
			//echo "numOfUl ".$numOfUl."</br>";			
			######################
			//if($numOfUl!=0){
			if($numOfOl!=0 && $posLi !== false){	//ce imamo ol in li				
				$text = str_replace('<ol>','\begin{enumerate} ', $text);
				$text = str_replace('<li>','\item ', $text);
				$text = str_replace('</ol>','\end{enumerate} \ ', $text);					
			}
			//echo "prazno v html: ".strpos($text, '\r')."</br>";
			//echo "text potem: ".$text."</br>";
			######################
		}
		//ureditev preureditve html kode ol in li v latex enumerate, ki je ostevilcen - konec
		
		//po ureditvi posebnih karakterjev, dodati del teksta s kodo za sliko, ce je slika prisotna
		if($posImg !== false){
			$text = substr_replace($text, $textOfImgCode, $posOfImgCode, 0);
		}
		//po ureditvi posebnih karakterjev, dodati del teksta s kodo za sliko, ce je slika prisotna	
		
		if($posPar !== false){	//ce je kaksen html tag <p>, dodaj prazno vrstico oz. break			
			if($this->variableName=='gdpr'){
				if($numOfUl!=0 && $posLi !== false){	//ce imamo ul in li	
					$divider = ' ';
				}else{
					$divider = ' \\ \\\\ ';
				}
				$text = str_replace('<p>',$divider, $text);
			}else{				
				$text = str_replace('<p>',' ', $text);
			}			
		}

		
		//priprava izpisa zelo dolgega besedila brez presledkov (URL, email, ...)
		//if($numOfSpacesPrej == 0 && ($stringLength >= MAX_STRING_LENGTH)){	//ce v besedilu ni presledkov in je besedilo daljse od max dovoljene dolzine
		if( ($numOfSpacesPrej == 0 && ($stringLength >= MAX_STRING_LENGTH)) || ($numOfSpacesPrej == 1 && $posSpace1 == $posSpace2 && $stringLength >= MAX_STRING_LENGTH)){	//ce v besedilu ni presledkov in je besedilo daljse od max dovoljene dolzine
			//$text = "\seqsplit{".$text."}"; //ni v redu seqsplit, ker ne dela, če so posebni znaki			
			$text = substr_replace($text, $this->texNewLine, MAX_STRING_LENGTH, 0);	//dodaj na ustrezni dolzini besedila prehod v novo vrstico
		}
		//priprava izpisa zelo dolgega besedila brez presledkov - konec
		//echo "text potem: ".$text."</br>";
		
		//detekcija prisotnosti e-naslova v besedilu in primerna preureditev, da pride do pravilnega izpisa
		$findAt = '@';
		$numOfAt = substr_count($text, $findAt);	//stevilo '@' v besedilu

		$posAt = strpos($text, $findAt);
		if($posAt){	//ce je prisotna afna
			//echo "afna je: $posAt </br>";
			//echo "Encoding: ".$text."</br>";

			//najdi prvi presledek po afni
			//echo substr($text, $posAt) ."</br>";
			$posSpace1Mail = strpos(substr($text, $posAt), $findSpace);	//najdi pozicijo prvega presledka v besedilu po e-naslovu
			$posSpace1Mail = $posSpace1Mail+$posAt;	//koncna pozicija, ce se gleda celotno besedilo
			//echo $posSpace1Mail."</br>";			
			
			//najdi prvi presledek pred afno			
			$posSpace2Mail = strripos(substr($text, 0, $posAt), $findSpace);	//najdi pozicijo zadnjega presledka v besedilu pred e-naslovom
			//echo $posSpace2Mail."</br>";
			
			//dodaj po e-naslovu potrebno latex kodo za zakljucek url
			$text = substr_replace($text, '}', $posSpace1Mail, 0);
			
			//dodaj pred e-naslovom potrebno latex kodo za url			
			//substr_replace(string_name, replacement_string, start_pos, length) 
			$text = substr_replace($text, ' \url{', $posSpace2Mail+1, 0);
			//echo $text."</br>";
		}
		//detekcija prisotnosti e-naslova v besedilu  in primerna preureditev, da pride do pravilnega izpisa - konec


		//RESEVANJE BESEDILA V CIRILICI
		$contains_cyrillic = (bool) preg_match('/[\p{Cyrillic}]/u', $text);	//ali je v besedilu cirilica?		
		if($contains_cyrillic){	// ce je cirilica v besedilu
			$text = '\foreignlanguage{russian}{'.$text.'}';
		}
		//RESEVANJE BESEDILA V CIRILICI - konec

		

		//RESEVANJE odstranitve dodatnih style tag-ov po ul, ipd. #######################################################
		$findStyleTag = 'style="';		
		$findStyleTagEnd = '"';
		$numOfStyleTags = substr_count($text, $findStyleTag);	//stevilo 'style=" ' v tekstu
		//echo "stevilo style: ".$numOfStyleTags." </br>";		
		for($s=0; $s<$numOfStyleTags; $s++){	//za vsako najdeno 'style=" ' besedilo, uredi njeno odstranitev			
			$posStyleTag = strpos($text, $findStyleTag);			
			$posStyleTagEnd = strpos($text, $findStyleTagEnd, $posStyleTag);	//strpos(string,find,start) najdi $findStyleTagEnd v $text, isci od $posStyleTag dalje
			$dolzinaOff = $posStyleTagEnd - $posStyleTag + 2;
			$text = substr_replace($text, "", $posStyleTag, $dolzinaOff);
		
		}		
		//RESEVANJE odstranitve dodatnih style tag-ov po ul, ipd. - konec #################################################

 		if($pos === false && $posImg === false) {	//v tekstu ni br in img 		
			
			$text = preg_replace("/(\R){2,}/", "$1", $text);
			return strip_tags($text);
		}else {	//v tekstu sta prisotna br ali img		
			$text2Return = '';	//tekst ki bo vrnjen
										
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico
			if($naslovStolpca==0){	// ce besedilo ni naslov stolpca tabele
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
			}
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico - konec
			//echo "text potem: ".$text."</br>";
			$text = preg_replace("/(\R){2,}/", "$1", $text);
			return strip_tags($text);	//vrni tekst brez html tag-ov
		}
	}
	#funkcija ki skrbi za encode dolocenih spornih delov besedila v latex-u prijazno - konec
		
	#funkcija, ki skrbi za pridobitev imena slike, ki jo je potrebno izrisati ######################################
	function getImageName($text='', $sprId=null, $findme='', $vre_id=0){
		global $site_path;
		$imageName = '';

		if($text == 'hotspot' && $findme == 'hotspot_image='){
			$sqlParametrov = sisplet_query("SELECT params FROM srv_spremenljivka WHERE id='".$sprId."'");
			$rowParametrov = mysqli_fetch_row($sqlParametrov);
			$text = $rowParametrov[0];
		}

		$pos = strpos($text, $findme);	//najdi pozicijo teksta v $findme
		//echo "text za echo: ".$text."</br>";
		//if($pos!=''){	//ce je slika v bazi
		if($pos!=''||$pos==0){	//ce je slika v bazi
			$imageName = substr($text,$pos);	//pokazi le del text od besedila $findme dalje (vkljucno z besedilom)
			//echo "imageName prej: ".$imageName."</br>";
			
			$findme = $site_path.'uploadi/editor/';
			//$findme = 'uploadi/editor/';
			
			$pos = strpos($imageName, $findme);	//najdi pozicijo teksta v $findme
			//echo "najdi tole: ".$findme."</br>";
			//echo "najdi tukaj: ".$imageName."</br>";
			//echo "pozicija tega: ".$pos."</br>";
			if($pos){	//ce je slika na strezniku
				$slikaNaStrezniku = 1;
			}else{//ce slike ni na strezniku
				$slikaNaStrezniku = 0;
			}
			//echo "ali je slika na strežniku: ".$slikaNaStrezniku."</br>";
			if($slikaNaStrezniku==1){	//ce je slika na strezniku
				$imageName = substr($imageName,$pos+7);	//pokazi le del params od besedila 'editor/' dalje, brez besedila 'editor/'
				$pos = $this->getEndPosition($imageName);	//najdi pozicijo konca URL slike	
				$imageExtension = substr($imageName, $pos-3, 3);	//pridobi koncnico slike (za gif je potrebno sliko pretvoriti v png, saj latex ne podpira gif)
	/* 			echo "exr1: ".$imageExtension."</br>";
				$imageExtension = strrchr($imageName, '.');	//pridobi koncnico slike (za gif je potrebno sliko pretvoriti v png, saj latex ne podpira gif, jpg in jpeg)
				echo "exr2: ".$imageExtension."</br>"; */
				
				$imageName = substr($imageName, 0, $pos);	//pokazi le del params od zacetka besedila do '"' oz. konca URL slike
				
				$path = $site_path.'uploadi/editor/'.$imageName;			
				
				if($imageExtension == 'gif'){	//ce je slika gif, jo je potrebno pretvoriti v png					
					$this->convertGifToPng($path, $slikaNaStrezniku);
				}

				if($imageExtension == 'jpg' || $imageExtension == 'peg'){	//ce je slika jpg ali jpeg, jo je potrebno pretvoriti v png					
					$this->convertJpgToPng($path, $slikaNaStrezniku, $imageExtension);
				}
			}elseif($slikaNaStrezniku==0){	//ce slike ni na strezniku
				//echo "vre_id: $vre_id </br>";
				$imageName = $this->getOnlineImageName($imageName, $slikaNaStrezniku, $vre_id);	//pridobi njen URL
			}

			$imageName = substr($imageName, 0, strrpos($imageName, '.'));
		
		}
		
		//echo "imagename pred return: ".$imageName."</br>";
		return $imageName;
	}
	#funkcija, ki skrbi za pridobitev imena slike, ki jo je potrebno izrisati - konec ###############################
	
	
	#funkcija, ki skrbi za pridobitev ustrezne strezniske poti do slike - trenutno ni v uporabi ########################################################
	function getPath2Images($text='', $sprId=null, $findme=''){
		global $site_path;
		$imageName = '';
		
		if($text == 'hotspot' && $findme == 'hotspot_image='){
			$sqlParametrov = sisplet_query("SELECT params FROM srv_spremenljivka WHERE id='".$sprId."'");
			$rowParametrov = mysqli_fetch_row($sqlParametrov);
			$text = $rowParametrov[0];
		}

		$pos = strpos($text, $findme);	//najdi pozicijo teksta v $findme

		if($pos!=''||$pos==0){	//ce je slika v bazi
			$imageName = substr($text,$pos);	//pokazi le del text od besedila $findme dalje (vkljucno z besedilom)

			$findme = 'editor/';
			$pos = strpos($imageName, $findme);	//najdi pozicijo teksta 'editor/'
			
			if($pos){	//ce je slika na strezniku
				$slikaNaStrezniku = 1;
			}else{//ce slike ni na strezniku
				$slikaNaStrezniku = 0;
			}
			
			if($slikaNaStrezniku==1){	//ce slika je bila prenesena na streznik			
				$path2Images = $site_path.'admin/survey/export/latexclasses/textemp/images/';
			}elseif($slikaNaStrezniku==0){ //ce slika je bila na dolocenem URL
				$path2Images = $site_path.'uploadi/editor/';
			}
	
		}
		//echo "pot do slik: ".$path2Images."</br>";
		return $path2Images;
	}
	#funkcija, ki skrbi za pridobitev ustrezne strezniske poti do slike - konec ########################################################
	
	
	#funkcija, ki skrbi za pridobitev slike, ki se nahaja nekje online in jo je potrebno izrisati, in vrne lokalno ime slike ######################################
	function getOnlineImageName($imageName='', $slikaNaStrezniku=null, $vre_id=null){
		global $site_path;	
		//echo "imageName v getOnlineImageName nekje vmes 1: ".$imageName."</br>";
		$row = Cache::srv_spremenljivka(self::$spremenljivka);
		//echo "sprem: ".self::$spremenljivka."</br>";
		$spremenljivkaParams = new enkaParameters($row['params']);
		//echo "params: ".$spremenljivkaParams->get('hotspot_image')."</br>";		
		$imageNameTmp = $spremenljivkaParams->get('hotspot_image');
		//if($imageNameTmp!=''){	//ce je hotspot
		if($imageNameTmp!=''&&$vre_id==0){	//ce je hotspot
			$imageName = $imageNameTmp;
		}
		
		$findHttp = 'http';
		$posHttp = strpos($imageName, $findHttp);		
		$imageName = substr($imageName,$posHttp);	//besedilo do zacetka http		
		
		$pos = $this->getEndPosition($imageName);	//najdi pozicijo konca URL slike
		$imageName = substr($imageName, 0, $pos);	//pokazi le del params od zacetka besedila do '"' oz. konca URL slike
		//echo "imageName v getOnlineImageName nekje vmes 2: ".$imageName."</br>";
		$imageExtension = substr($imageName, $pos-3, 3);	//pridobi koncnico slike
		//echo "imageExtension: ".$imageExtension."</br>";
		
		if($imageExtension!='jpg'&&$imageExtension!='png'&&$imageExtension!='gif'&&$imageExtension!='jpeg'){	//ce ni veljavnen extension, spremeni ga v png
			$imageExtension='png';
		}
		
		if($vre_id){	//ce se pridobiva imena tmp slik iz vrednosti vprasanja
			$imgFilename = self::$spremenljivka.'_'.$vre_id.'_tmpImage.'.$imageExtension;	//tmp ime slike, ki je sestavljeno iz id spremenljivke+tmpImage+extension
		}else{
			$imgFilename = self::$spremenljivka.'_tmpImage.'.$imageExtension;	//tmp ime slike, ki je sestavljeno iz id spremenljivke+tmpImage+extension
		}
		
		$pathDir = $site_path.'uploadi/editor/';	//pot za novo mapo, kjer se bodo shranjevale slike za trenutno anketo	
		$path = $pathDir.$imgFilename;	//pot do datoteke z imenom datoteke
		
		# ukaz za pretakanje slike
		if(IS_WINDOWS){
			//za windows sisteme	//powershell -command "& { iwr URL -OutFile 'PATH' }"			
			$command = 'powershell -command "& { iwr \''.$imageName.'\' -OutFile \''.$path.'\' }"';
			//$command = 'wget -O \''.$imageName.'\' -O '.$path.'  ';
		}elseif(IS_LINUX){
			//za linux sisteme //exec('wget URL -P PATH ');
			//$command = 'wget \''.$imageName.'\' -P '.$path.' ';
			$command = 'wget -O '.$path.' \''.$imageName.'\' ';
		}		
		
		//echo "command: ".$command."</br>";
		exec($command); //pretoci sliko
		
		//$path = $pathDir.$imgFilename;	//pot do datoteke z imenom datoteke
		
/* 		if($imageExtension == 'gif'){	//ce je slika gif, jo je potrebno pretvoriti v png,  saj latex ne podpira gif
			$this->convertGifToPng($path, $slikaNaStrezniku);
		} */
		
		if($imageExtension != 'png'){ //ce slika ni png, jo pretvori
			if($imageExtension == 'gif'){	//ce je slika gif, jo je potrebno pretvoriti v png,  saj latex ne podpira gif
				$this->convertGifToPng($path, $slikaNaStrezniku);				
			}else{
				$this->convertJpgToPng($path, $slikaNaStrezniku, $imageExtension);				
			}
		}
		
		//echo "imgfilename: ".$imgFilename."</br>";
		return $imgFilename;
	}	
	#funkcija, ki skrbi za pridobitev slike, ki se nahaja nekje online in jo je potrebno izrisati, in vrne lokalno ime slike - konec ###############################
	
	
	function convertGifToPng($path='', $slikaNaStrezniku=null){
		//echo "path: ".$path."</br>";
		$image = imagecreatefromgif($path);	//pripravi sliko iz gif za pretvorbo
		$imageName = substr($path, 0, -3);	//ime slike brez extension-a
		//echo $imageName."</br>";
		$imageNamePNG = $imageName.'png';	//ime slike z ustreznim extension		
		imagepng($image, $imageNamePNG);	//pretvori pripravljeno gif sliko v png
		
		if($slikaNaStrezniku==0){	//ce slika je iz URL in ni na strezniku, GIF izbrisi
			unlink($imageName.'gif');	//izbrisi gif sliko
		}		
	}	
	
	function convertJpgToPng($path='', $slikaNaStrezniku=null, $imageExtension=''){
		$image = imagecreatefromjpeg($path);	//pripravi sliko iz jpg za pretvorbo
		$imageName = substr($path, 0, strrpos($path, '.')); //ime slike brez extension-a
		
		$imageNamePNG = $imageName.'.png';	//ime slike z ustreznim extension		
		imagepng($image, $imageNamePNG);	//pretvori pripravljeno jpg sliko v png
		
		//$origExtension = strrchr($path, '.');
		
		if($slikaNaStrezniku==0){	//ce slika je iz URL in ni na strezniku, izbrisi jo
			if($imageExtension == 'jpg'){
				unlink($imageName.'.'.$imageExtension);	//izbrisi sliko
			}elseif($imageExtension == 'peg'){
				unlink($imageName.'.j'.$imageExtension);	//izbrisi sliko
			}
		}		
	}	
	
	function getEndPosition($imageName=''){
		$findme = '"';
		$pos = strpos($imageName, $findme);	//najdi pozicijo teksta '"'
		return $pos;
	}
	
	
	#funkcija, ki skrbi za pridobitev simbola za slikovni tip ######################################
	function getCustomRadioSymbol($sprId=null, $odgovor=null){
		$customRadioSymbol = '';	
		$findme = 'customRadio=';
		$finishAt = 'customRadioNumber';
		
		$row = Cache::srv_spremenljivka($sprId);
		$this->spremenljivkaParams = new enkaParameters($row['params']);
		$customRadioSymbol = $this->spremenljivkaParams->get('customRadio');
		
		if($odgovor){	//ce je odgovor
			$customRadioSymbol = $customRadioSymbol."Inverted";	//preuredi, da bo razvidna grafika, ko je prisoten odgovor respondenta
		}
		//echo $customRadioSymbol.' asca</br>';
		return $customRadioSymbol;
	}
	#funkcija, ki skrbi za pridobitev simbola za slikovni tip - konec ###############################

	 
	 /**
	 * vrne prevod za srv_vrednost
	 * 
	 * @param mixed $vrednost
	 */
	 function srv_language_vrednost ($vre_id=null) {		
		 //if ($this->language != -1) {
		if ($this->prevod) {
			$sqllString = "SELECT naslov, naslov2 FROM srv_language_vrednost WHERE vre_id='".$vre_id."' AND lang_id='".$this->language."'";
			$sqll = sisplet_query($sqllString);
			$rowl = mysqli_fetch_array($sqll);			
			return $rowl;
		 }		 
		 return false;	 
	 }
	 
	 /**
	 * vrne prevod za srv_grid
	 * 
	 * @param mixed $vrednost
	 */
	function srv_language_grid ($grd_id=null, $spr_id=null) {
		 
		 //if ($this->language != -1) {
		if ($this->prevod) {
			$sqllString = "SELECT naslov FROM srv_language_grid WHERE spr_id = '".$spr_id."' AND  grd_id='".$grd_id."' AND lang_id='".$this->language."'";
			$sqll = sisplet_query($sqllString);
			$rowl = mysqli_fetch_array($sqll);			
			return $rowl;
		 }
		
		return false;
	 }
/* 	 function srv_language_grid ($spremenljivka, $grid) {
	 	 
		 if ($this->language != -1) {
			$sqll = sisplet_query("SELECT * FROM srv_language_grid WHERE ank_id='".$this->anketa['id']."' AND spr_id='".$spremenljivka."' AND grd_id='".$grid."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			if ($rowl['naslov'] != '') return $rowl['naslov'];	
		 }
		 
		 return false;		 
	 } */
	 
	#funkcija, ki skrbi za filanje obstojecega polja z odgovori z missing odgovori #############################################################
	function AddMissingsToAnswers($vodoravniOdgovori=[], $missingOdgovori=[]){		
		for($m=0;$m<count($missingOdgovori);$m++){
			array_push($vodoravniOdgovori,$missingOdgovori[$m]);
		}
		return $vodoravniOdgovori;
	}	
	#funkcija, ki skrbi za filanje obstojecega polja z odgovori z missing odgovori - konec #####################################################
	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele ##################################################################################
	#argumenti 1. export_format, 2. parametri tabele, 3. tip tabele za pdf, 4. tip tabele za rtf, 5. sirina pdf tabele (delez sirine strani), 6. sirina rtf tabele (delez sirine strani)
	function StartLatexTable($export_format='', $parameterTabular='', $pdfTable=null, $rtfTable=null, $pdfTableWidth=null, $rtfTableWidth=null){
		$tex = '';
		$tex .= '\keepXColumns';
 		if($export_format == 'pdf'){
			$tex .= '\begin{'.$pdfTable.'}';
			if($pdfTable=='tabularx'){
				$tex .= '{'.$pdfTableWidth.'\textwidth}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}elseif($export_format == 'rtf'){
			$tex .= '\begin{'.$rtfTable.'}';
			if($rtfTable=='tabular*'){
				$tex .= '{'.$pdfTableWidth.'\textwidth}';
			}
			//$tex .= '{ '.$parameterTabular.' }';
			$tex .= '{@{}  '.$parameterTabular.' }';	//dodal @{} , da ni indent-a
		}
		
		return $tex;
	}	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele - konec ##########################################################################
	
	#funkcija, ki skrbi za izpis latex kode za zakljucek tabele ##################################################################################
	#argumenti 1. export_format, 2. tip tabele za pdf, 3. tip tabele za rtf
	function EndLatexTable($export_format='', $pdfTable=null, $rtfTable=null){
		$tex = '';
		$tex .= ($export_format == 'pdf' ? '\end{'.$pdfTable.'}' : '\end{'.$rtfTable.'}');
		return $tex;
	}	
	#funkcija, ki skrbi za izpis latex kode za zakljucek tabele - konec ##########################################################################
	
	#funkcija, ki skrbi za pripravo latex kode za text box (okvirja) (prazen ali z besedilom) dolocene sirine in visine glede na export format  ####################
	#argumenti 1. export_format, 2. visina okvirja, 3. sirina okvirja, 4. besedilo v okvirju, 5. poravnava, 6. obrobe
	function LatexTextBox($export_format='', $textboxHeight=null, $textboxWidth=null, $text='', $textboxAllignment=null, $noBorders=null){
		$tex = '';		
		
		//zacetek okvirja		
		if($export_format == 'pdf'&&$textboxHeight!=0&&$noBorders==0){		
			$tex .= ' \fbox{\parbox['.$textboxAllignment.']['.$textboxHeight.']{'.$textboxWidth.'\textwidth}';
		}elseif((($export_format == 'pdf'&&$textboxHeight==0))&&$noBorders==0){		
			$tex .= ' \fbox{\parbox{'.$textboxWidth.'\textwidth}';
		}elseif( ($export_format == 'rtf'||$export_format == 'pdf')&&$noBorders ){
			$tex .= ' {\parbox{'.$textboxWidth.'\textwidth}';			
		}
		
		
		if($text==''){	//ce ni teksta, je okvir prazen
			if($export_format == 'pdf'){
				$tex .= '{ \hphantom{\hspace{'.$textboxWidth.'\textwidth}} }}';
			}elseif($export_format == 'rtf'){				
				//$tex .= ' \rule{40mm}{.1pt} ';	//izpisi neprekinjeno crto, ki je dolga 40mmm in debela 0.1pt
				$tex .= ' \rule{'.$textboxWidth.'\textwidth}{.1pt} ';	//izpisi neprekinjeno crto, ki je dolga 40mmm in debela 0.1pt
			}		
		}else{	//drugace, izpisi besedilo
			if($export_format == 'pdf'){
				$tex .= '{ '.$text.' }}';	//izpis besedila v okvirju				
			}elseif($export_format == 'rtf'){
				$tex .= '{ '.$text.' }';	//izpis besedila v okvirju
			}			
		}
		
		return $tex;
	}
	#funkcija, ki skrbi za pripravo latex kode za text box (okvirja) (prazen ali z besedilom) dolocene sirine in visine glede na export format - konec ############
	
	#funkcija, ki skrbi za pripravo latex U oblike dolocene sirine in visine glede na export format  ####################
	#argumenti 1. export_format, 2. visina okvirja, 3. sirina okvirja
	function LatexUShape($export_format='', $textboxHeight=null, $textboxWidth=null){
		$tex = '';
		if($export_format == 'pdf'&&$textboxHeight!=0){	
			$tex .= '\keepXColumns\begin{tabularx}{0.25\textwidth}{C} ';	//zacetek tabele, ki bo zgledala kot okvir	
			$tex .= ' \begin{tikzpicture} ';			
			//$tex .= ' \draw (0,0) -- (4,0) -- (4,4)  (0,4) -- (0,0);';
			$tex .= ' \draw (0,0) -- ('.$textboxWidth.',0) -- ('.$textboxWidth.','.$textboxHeight.')  (0,'.$textboxHeight.') -- (0,0);';			
			$tex .= ' \end{tikzpicture} ';
			
		}elseif($export_format == 'rtf'||($export_format == 'pdf'&&$textboxHeight==0)){
			$tex .= ' \fbox{\parbox{'.$textboxWidth.'\textwidth}';
		}
		return $tex;
	}
	#funkcija, ki skrbi za pripravo latex U oblike dolocene sirine in visine glede na export format - konec ############
	
	#funkcija, ki skrbi za pripravo latex U oblike s tekstom dolocene sirine in visine glede na export format  ####################
	#argumenti 1. export_format, 2. visina okvirja, 3. sirina okvirja, 4. tekst oz. koda za odgovore v okvirjih
	function LatexTextInUShape($export_format='', $textboxHeight=null, $textboxWidth=null, $text=''){
		//\begin{tikzpicture} \draw (-2,0) -- (2,0) -- (2,1.5 cm) (-2,1.5 cm) -- (-2,0)  node[above right] {\begin{tabular}{c}  \fbox{\parbox{0.2\textwidth}{  \centering  Vpišite besedilo odgovora 11 }}  \\ \fbox{\parbox{0.2\textwidth}{  \centering  Vpišite besedilo odgovora 12 }} \end{tabular} }; \end{tikzpicture}
		$tex = '';
		if($export_format == 'pdf'&&$textboxHeight!=0){

			$tex .= '\keepXColumns\begin{tabularx}{0.25\textwidth}{C} ';	//zacetek tabele, ki bo zgledala kot okvir
			$tex .= ' \begin{tikzpicture} ';			
			
			//\draw (-2,0) -- (2,0) -- (2,1.5 cm) (-2,1.5 cm) -- (-2,0)	
			$tex .= ' \draw (-'.$textboxWidth.',0) -- ('.$textboxWidth.',0) -- ('.$textboxWidth.','.$textboxHeight.' cm)  (-'.$textboxWidth.','.$textboxHeight.' cm) -- (-'.$textboxWidth.',0) node[above right] { ';			
			
			$tex .= '\begin{tabular}{c} ';	//zacetek tabele znotraj skatle, da je lahko vec odgovorov (eden pod drugim) znotraj skatle
			
			$tex .= $text;
			
			$tex .= ' \end{tabular} '; //konec tabele znotraj skatle
			
			
			$tex .= ' }; \end{tikzpicture} ';
			
		}elseif($export_format == 'rtf'||($export_format == 'pdf'&&$textboxHeight==0)){
			$tex .= ' \fbox{\parbox{'.$textboxWidth.'\textwidth}';
		}
		return $tex;
	}
	#funkcija, ki skrbi za pripravo latex U oblike s tekstom  dolocene sirine in visine glede na export format - konec ############	
	
	#funkcija, ki skrbi za pripravo latex okvirja za grid drag and drop  ####################
	#argumenti 1. export_format, 2. visina okvirja, 3. sirina okvirja, 4. tekst naslova okvirja, 5. ali je odgovor prisoten, 6. tekst za odgovore v okvirjih
	function LatexTextGridOfBoxes($export_format='', $textboxHeight=null, $textboxWidth=null, $textNaslovOkvir='', $jeOdgovor=null, $textIzpis=''){		
		$tex = '';
		if($export_format == 'pdf'&&$textboxHeight!=0){
			if($jeOdgovor==0){
				$tex .= '\keepXColumns\begin{tabularx}{0.25\textwidth}{|C|} ';	//zacetek tabele, ki bo zgledala kot okvir
				$tex .= '\hline';	//izris horizontalne obrobe za zacetek tabele
				$tex .= '{\parbox{0.25\textwidth}{\vspace{0.5\baselineskip} \centering ';
				$tex .= $textNaslovOkvir;
				$tex .= '\vspace{0.5\baselineskip}}}';
				$tex .= $this->texNewLine;
				$tex .= '\hline';	//izris horizontalne obrobe za zakljuciti tabelo
				$tex .= '{\parbox{0.25\textwidth}{\vspace{0.5\baselineskip} \centering ';
				$tex .= '';
				$tex .= '\vspace{0.5\baselineskip}}}';
				$tex .= $this->texNewLine;
				$tex .= '\hline';	//izris horizontalne obrobe za zakljuciti tabelo
				$tex .= '\end{tabularx}'; //konec tabele znotraj skatle
			}else{
				$tex .= '\keepXColumns\begin{tabularx}{0.25\textwidth}{|C|} ';	//zacetek tabele, ki bo zgledala kot okvir
				$tex .= '\hline';	//izris horizontalne obrobe za zacetek tabele
				$tex .= $textNaslovOkvir;				
				$tex .= '\hline';	//izris horizontalne obrobe za zakljuciti tabelo				
				$tex .= $textIzpis;				
				$tex .= '\hline';	//izris horizontalne obrobe za zakljuciti tabelo
				$tex .= '\end{tabularx}'; //konec tabele znotraj skatle			
			}			
		}elseif($export_format == 'rtf'||($export_format == 'pdf'&&$textboxHeight==0)){
			$tex .= ' \fbox{\parbox{'.$textboxWidth.'\textwidth}';
		}
		return $tex;
	}
	#funkcija, ki skrbi za pripravo latex okvirja za grid drag and drop - konec ############


	
	#funkcija, ki skrbi za pravilen izris prve vrstice v tabelah (vrstica z vodoravnimi naslovi multigridov) #############################
	function LatexPrvaVrsticaMultiGrid($steviloStolpcev=null, $enota=null, $trak=null, $customColumnLabelOption=null, $spremenljivke=null, $vodoravniOdgovori=null, $missingOdgovori=null){
		$tex = '';
		for($i = 0; $i < $steviloStolpcev; $i++){
			if ($i != 0){	//ce ni prvi stolpec
				//if($enota==11 || $enota==12 || ($enota==0 && ($trak==0&&$customColumnLabelOption==1)) ||($enota==0 && $spremenljivke['tip']==16) ||($enota==1 && ($trak==0&&$customColumnLabelOption==1)) || $enota==8 || $enota==3){	//klasicna ali diferencial tabela (brez traku) ali tabela da/ne ali dvojna tabela ali VAS ali slikovni tip
				if(($enota==0 && ($trak==0&&$customColumnLabelOption==1)) ||($enota==0 && $spremenljivke['tip']==16) ||($enota==1 && ($trak==0&&$customColumnLabelOption==1)) || $enota==8 || $enota==3 || $enota==11 || $enota==12 || ($enota==2 && $spremenljivke['tip']==24)){ // ce je klasicna ali diferencial tabela (brez traku) ali tabela da/ne ali dvojna tabela ali VAS ali slikovni tip ali roleta/seznam v kombinirani tabeli
					if($i==$steviloStolpcev-1 && $enota==1){	//ce je zadnji stolpec in je diferencial						
						for($m=0;$m<count($missingOdgovori);$m++){
							$tex .= " & ".$missingOdgovori[$m];
						}
						$tex .= " & ";
					}else{
						$tex .= " & ".$vodoravniOdgovori[$i-1];						
					}
				}elseif($enota == 5){	//maxdiff
					if($i == 1){
						$tex .= ' & ';
					}
					$tex .= $vodoravniOdgovori[$i];
				}				
			}elseif($i == 0 && $enota != 5){	//ce je prvi stolpec tabele in ni "maxdiff"
				$tex .= '';
			}elseif($i == 0 && $enota == 5){	//ce je prvi stolpec tabele in "maxdiff"
				$tex .= $vodoravniOdgovori[$i].' & ';
			//}elseif( ($i == $steviloStolpcev-1 && $enota != 5) || $enota == 1){	//ce je zadnji stolpec tabele in ni "maxdiff" ali je diferencial
			}elseif( ($i == $steviloStolpcev-1 && $enota != 5) ){	//ce je zadnji stolpec tabele in ni "maxdiff" ali je diferencial
				$tex .= ' & ';				
			}

			
			//echo "odgovori ".$i.": ".$vodoravniOdgovori[$i-1]." </br>";
			//echo "koda za indeks ".$i.": ".$tex." </br>";
		}
		
		#Nastavitev UPORABA LABEL
		if( $customColumnLabelOption!=1 && $trak==0 && ($enota==0||$enota==1) && $spremenljivke['tip'] == 6 ){	//ce ni potrebno izrisati vseh label vodoravnih odgovorov in je "klasicna tabela" ali "diferencial" (uredi vodoravne labele nad radio buttoni)
			$numGrids = $spremenljivke['grids'];
			if($customColumnLabelOption == 2){	//ce je trenutna moznost prilagajanja "le koncne"
				if(($numGrids%2) == 0){	//ce je parno stevilo, spoji polovico label na vsako skupino label	
					$colParameter1 = $colParameter2 = intval(($numGrids)/2);
				}else if(($numGrids%2) != 0){	//ce ni parno stevilo, spoji prvi skupini label eno celico vec kot pri drugi skupini label
					$colParameter1 = intval(($numGrids)/2 + 0.5);
					$colParameter2 = intval(($numGrids)/2 - 0.5);
				}
				for($i=0; $i<$numGrids; $i++){
					if($i==0){	//ce je prvi stolpec nadnaslovov
						$tex .= ' & \multicolumn{'.$colParameter1.'}{l}{'.$vodoravniOdgovori[$i].'}';
					}elseif( $i==($numGrids-1) ){ //ce je zadnji stolpec nadnaslovov
						$tex .= ' & \multicolumn{'.$colParameter2.'}{r}{'.$vodoravniOdgovori[$i].'}';
					}
				}
			}else if($customColumnLabelOption == 3){	//ce je trenutna moznost prilagajanja "koncne in vmesna"											
				if(($numGrids%3) == 0){	//ce je velikost deljiva s 3, spoji vsako tretjino label
					$colParameter1 = $colParameter2 = $colParameter3 = $numGrids/3;
					$sredina = $numGrids/3;
				}else if(($numGrids%3) == 1){	//ce pri deljenju z 3 je ostanek 1
					$colParameter1 = $colParameter2 = intval($numGrids/3);
					$colParameter3 = intval($numGrids/3)+1;
					$sredina = intval(1 + $numGrids/3);
				}elseif(($numGrids%3) == 2){	//ce pri deljenju z 3 je ostanek 2
					$colParameter1 = $colParameter2 = 1 + intval($numGrids/3);
					$colParameter3 = intval($numGrids/3);
					$sredina = $numGrids%3 + intval($numGrids/3);
				}
				
				for($i=0; $i<$numGrids; $i++){
					if($i==0){	//ce je prvi stolpec nadnaslovov (prva labela)
						$tex .= ' & \multicolumn{'.$colParameter1.'}{l}{'.$vodoravniOdgovori[$i].'}';
					}elseif($i==$sredina){	//ce je sredina (vmesna labela)
						$tex .= ' & \multicolumn{'.$colParameter3.'}{c}{'.$vodoravniOdgovori[$i].'}';
					}elseif( $i==(($numGrids)-1) ){ //ce je zadnji stolpec nadnaslovov (zadnja labela)
						$tex .= ' & \multicolumn{'.$colParameter2.'}{r}{'.$vodoravniOdgovori[$i].'}';
					}
					
				}
			}
		}
		#Nastavitev UPORABA LABEL - KONEC
		//echo "</br>";
		//$tex .= '\endhead'; 	//da se naslovna vrstica ponovi na vsaki strani, ce tabela gre na novo stran
		//echo "koda: ".$tex."</br>";
		return $tex;		
	}
	#funkcija, ki skrbi za pravilen izris prve vrstice v tabelah (vrstica z vodoravnimi naslovi multigridov) - konec #####################
	
	#funkcija, ki skrbi za izris vrstic tabele (z multigrid) ###########################################################
	function LatexVrsticeMultigrid($numRowsSql=null, $export_format='', $enota=null, $simbolTex=null, $navpicniOdgovori=null, $trakStartingNumberTmp=null, $fillablePdf=null, $numColSql=null, $spremenljivke=null, $trak=null, $vodoravniOdgovori=null, $texNewLine='', $navpicniOdgovori2=null, $missingOdgovori=null, $vodoravniOdgovoriTip=null, $vodoravniOdgovoriEnota=null, $vodoravniOdgovoriSprId=null, $data=null, $export_subtype=null, $preveriSpremenljivko=null, $userDataPresent=null, $presirokaKombo = null, $export_data_type=null){
		$this->export_subtype = $export_subtype;
		$tex = '';
		global $lang, $site_path;
		$this->path2Images = $site_path.'admin/survey/export/latexclasses/textemp/images/';
		//$radioButtonTex = ($export_format=='pdf'?"{\Large $\ocircle$}" : "\\includegraphics[scale=".RADIO_BTN_SIZE."]{radio}");		
		$indeksOdgovorovRespondentMultiNumText = 0;

		if($spremenljivke['enota']==2||$spremenljivke['enota']==6){	//ce je seznam ali roleta	//$enota == 2 || $enota == 6
			if(count($missingOdgovori)==0){	//ce ni missing vrednosti
				$numColSql = $numColSql + 1;
			}			
		}
		$userAnswerIndex = array();
 		$userAnswerIndex[$spremenljivke['id']] = 0;
		$z = 0;
		$skipRow = false;
		
		$this->skipEmpty = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty'); // izpusti vprasanja brez odgovora
		$this->skipEmptySub = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty_sub'); // izpusti podvprasanja brez odgovora

		if($spremenljivke['tip']==24){	//ce je kombinirana tabela			
			//echo "stevilo stolpcev za izpis kombinirane podan: ".$numColSql."</br>";
			//print_r($data);
			//if($presirokaKombo == 1 && count($data) != 0 && ($enota == 2 || $enota == 6)){
			if($presirokaKombo == 1 && count($data) != 0 && (($enota == 2 || $enota == 6) || $export_data_type == 2 && $vodoravniOdgovoriTip[0] == 6)){
				$numColSql = 1 + 1;
				//echo "stevilo stolpcev za izpis kombinirane izračunan: ".$numColSql."</br>";
			}else{
				$numColSql = count($vodoravniOdgovoriEnota) + 1;
			}
		}

		//echo "stevilo vrstic: $numRowsSql za enoto: ".$spremenljivke['enota']."</br>";
		
		//IZRIS PO VRSTICAH
		for ($i = 1; $i <= $numRowsSql; $i++){	//za vsako vrstico

			if($i == 1 && ($enota == 2 || $enota == 6)&&$spremenljivke['tip']!=24){	//ce je prvi dogovor IN je roleta ALI seznam IN ni kombinirana tabela
				if($export_format == 'rtf'){	//ce je rtf						
					$tex .= ' \hline '; //dodaj crto
				}
			}

			//echo "preskakovanje vprašanj: ".$this->skipEmptySub."</br>";

			// Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici
			if($this->skipEmptySub == 1){
				$skipRow = true;
				for($z=$z;$z<$i*($numColSql-1);$z++){
					//echo "Podatek: ".$data[$z]."</br>";
					if($data[$z]){
						//echo "Podatek je"."</br>";
						$skipRow = false;
						$podatekZaSlikovniTip = $data[$z];	//belezi podatek za slikovni tip, ki pride prav za pravilen izpis izvoza						
					}
				}
			}
			
			/* echo "skipRow je $skipRow"."</br>";
			echo "userDataPresent je $userDataPresent"."</br>";
			echo "skipEmpty je ".$this->skipEmpty."</br>"; */
			//echo "____________________________________________</br>";
			// Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici - konec
			//echo "za vrstico $i je $skipRow na stolpcih: $numColSql</br>";
			//if(!$skipRow||(!$userDataPresent&&$this->skipEmpty==0)){	/* ce je potrebno preskociti vrstico ALI (ni podatkov za prikaz, vendar je potrebno pokazati vprasanja brez odgovorov) */
			if(!$skipRow||(!$userDataPresent&&$this->skipEmpty==0)||$export_subtype == 'q_empty'){	/* ce je potrebno preskociti vrstico ALI (ni podatkov za prikaz, vendar je potrebno pokazati vprasanja brez odgovorov) */
				if($i%2 != 0 && $export_format == 'pdf'){
					if($enota == 5){	//ce je maxdiff
						//$tex .= "\\rowcolor[gray]{.9}".$simbolTex.' & ';	//pobarvaj ozadje vrstice
						$tex .= "\\rowcolor[gray]{.9}".$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]).' & ';	//pobarvaj ozadje vrstice
						$userAnswerIndex[$spremenljivke['id']]++;
					}else{
						$tex .= "\\rowcolor[gray]{.9}".$navpicniOdgovori[$i-1];	//pobarvaj ozadje vrstice
					}				
				}else{
					if($enota == 5){	//ce je maxdiff
						//$tex .= $simbolTex.' & ';
						$tex .= $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]).' & ';
						$userAnswerIndex[$spremenljivke['id']]++;
					}else{
						$tex .= $navpicniOdgovori[$i-1];
					}
				}
				
				//tmp spremenljivka, ki je potrebna za pravilen izris stevilk, ce imamo trak				
				//$trakStartingNumberTmp = intval($trakStartingNumber);
				$trakStartingNumberTmp = intval($trakStartingNumberTmp);	//spremenil, zaradi intelephense napake
				
				//ureditev spremenljivk za pravilen kasnejsi izris seznama ali rolete
				$roletaAliSeznam = 0;	//belezi, ali je tak tip podtabele ali tabele prisoten
				$indeksRoleta = 1;
				$noItem = 1;
				if($spremenljivke['tip']==24){	//ce je kombinirana tabela, uredi enote znotraj te tabele			
					foreach($vodoravniOdgovoriEnota as $enota){
						if($enota == 2 || $enota == 6){	//roleta ali izberite s seznama, uredi ogrodje za itemize, da se bo dalo zadevo pravilno izrisati
							$roletaAliSeznam = 1;
							$indeksRoleta = 1;
							$noItem = 1;							
						}				
					}
					
					
					//$numColSql = count($vodoravniOdgovoriEnota) + 1;
					
					
					//echo "stevilo stolpcev za izpis kombinirane izračunan: ".$numColSql."</br>";
					//echo "stevilo stolpcev za izpis kombinirane izračunan: ".count($vodoravniOdgovoriEnota)."</br>";
					//print_r($vodoravniOdgovoriEnota);
					//$numColSql = 4;
					/* echo "Enote: ";
					print_r($vodoravniOdgovoriEnota);
					echo "</br>";  
					echo "Vodoravni odgvoroi spr id: ";							
					print_r($vodoravniOdgovoriSprId);
					echo "</br>";
					echo "Podatki: ";
					print_r($data);
					echo "</br>"; 
					echo "Tip: ";
					print_r($vodoravniOdgovoriTip);
					echo "</br>"; */
				}
				//ureditev spremenljivk za pravilen kasnejsi izris seznama ali rolete - konec
 				//echo "___________________________________________________</br>";
				

				$izpisRoletePresiroka = 0;
				
				if($fillablePdf == 0){	//naveden pdf (brez vnosnih polj) in rtf
					//echo "INDEKS vrstic: ".$i."</br>";
					//IZRIS PO STOLPCIH
					//echo "___________________________________________________</br>";
					//echo "stevilo stolpcev izpis: ".$numColSql."</br>";
					for($j = 1; $j < $numColSql; $j++){	//izris posameznega stolpca v vrstici
						
					//for($j = 1; $j <= $numColSql; $j++){	//izris posameznega stolpca v vrstici #################### PAZI!!! DODAL ROČNO ZA TESTIRANJE
						if($spremenljivke['tip']==24){	//ce je kombinirana tabela, uredi enote znotraj te tabele
							
							if($presirokaKombo == 1 && count($data) != 0 && ($enota == 2 || $enota == 6)){
								$enota = $vodoravniOdgovoriEnota[0];
							}else{
								$enota = $vodoravniOdgovoriEnota[$j-1];
							}
							
							//$enota = $vodoravniOdgovoriEnota[$j-1];
							$sprID = $vodoravniOdgovoriSprId[$j-1];
							
							
							//echo "Odgovori: ".$vodoravniOdgovori[$j-1]."</br>";
							//echo "INDEKS: ".$j."</br>";
							//echo "id spremenljivke: ".$sprID."</br>";
							//echo "enota tukaj: ".$enota."</br>";
							//if($j == 1){
   		 						/* echo "___________________________________________________</br>";
								echo "Enote: ";
								print_r($vodoravniOdgovoriEnota);
								echo "</br>";  
								echo "Vodoravni odgovori spr id: ";							
								print_r($vodoravniOdgovoriSprId);
								echo "</br>";
								echo "Odgovori: ";
								print_r($vodoravniOdgovori);
								echo "</br>";
								echo "Tip: ";
								print_r($vodoravniOdgovoriTip);
								echo "</br>";  */   
								
								/* echo "indeks for zanke: ".($j-1)."</br>";
								echo "enota for zanke: ".($enota)."</br>";
								echo "spr for zanke: ".($sprID )."</br>"; 
								echo "___________________________________________________</br>"; */
							//}
						}
						
						if($enota==0||$enota==1||$enota==3||$enota==11||$enota==12||$spremenljivke['tip']==19||$spremenljivke['tip']==20){		//klasika ali diferencial ali VAS ali slikovni tip ali multitext ali multinumber
							if(($trak == 1 && $enota != 3 && $spremenljivke['tip'] == 6)||($spremenljivke['tip']==19||$spremenljivke['tip']==20)){	//ce je trak ali multitext
								if($j<=$spremenljivke['grids']){	//ce so stolpci, ki vsebujejo trak s stevilkami ali textbox-e						
									if($spremenljivke['tip']==19||$spremenljivke['tip']==20){ //ce je multitext ali multinumber
										if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je odgovor respondenta ali vec respondentov
											$tex .= "& ".$simbolTex[$indeksOdgovorovRespondentMultiNumText];
										}elseif($export_subtype=='q_empty'||$export_subtype=='q_comment'){
											$tex .= "& ".$simbolTex;
										}
									}else{
										$tex .= '& '.($trakStartingNumberTmp);	//prikazovanje brez obrob celic
										
										//$tabela .= '& \multicolumn{1}{|c|}{'.($trakStartingNumberTmp).'} ';	//1. moznost z navpicnimi obrobami

										//2. moznost z navpicnimi obrobami
										/*if($j==1){ //ce je prvi stolpec, ko je trak
											$tabela .= '& \multicolumn{1}{|c}{'.($trakStartingNumberTmp).'} ';
										}else{
											$tabela .= '& \multicolumn{1}{c|}{'.($trakStartingNumberTmp).'} ';
										} */
										$trakStartingNumberTmp++;
									}
								}else{	//drugace so missing-i, kjer je potrebno izrisati ustrezen simbol (radio button)									
									if($enota==0&&($spremenljivke['tip']==6||$spremenljivke['tip']==16)){	//ce je klasicna tabela ali multitext ali multinumber
										$tex .= "& ".$simbolTex;
									}elseif($spremenljivke['tip']==19||$spremenljivke['tip']==20){//ce je multitext ali multinumber, izrisi missing simbol kot radio
										if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je odgovor respondenta ali vec respondentov
											$tex .= "& ".$simbolTex[$indeksOdgovorovRespondentMultiNumText];
										}else{
											$radioButtonTex = ($export_format=='pdf'?"{\Large $\ocircle$}" : "\\includegraphics[scale=".RADIO_BTN_SIZE."]{".$this->path2Images."radio}");
											$tex .= "& ".$radioButtonTex;
										}
										//$tex .= "& ".$radioButtonTex;
										
										//echo "radio button, ko je missing: ".$radioButtonTex."</br>";
									}									
								}
							}else{								
								if($spremenljivke['tip']==24){	//ce je kombinirana tabela, s klasicno podtabelo
									
									//$tex .= "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $vodoravniOdgovoriTip[$j-1], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
									//$test = "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $vodoravniOdgovoriTip[$j-1], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
									//echo "vodoravni odgovori tip ".$vodoravniOdgovoriTip[$j-1]." $i</br>";
									//echo "tip exp: ".$export_data_type."</br>";
									//echo "testna koda $test z enoto $enota</br>";
									
									if($export_data_type==0 || $export_data_type==1 || ($export_data_type==2 && $vodoravniOdgovoriTip[$j-1] != 6)){ //ce je razsirjen izvoz ALI je skrcen izvoz IN ni klasicna tabela
										$tex .= "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $vodoravniOdgovoriTip[$j-1], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
										//$test = "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $vodoravniOdgovoriTip[$j-1], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
										//echo "testna koda $test z enoto $enota</br>";
										//echo "tip exp: ".$export_data_type."</br>";
										//echo $vodoravniOdgovoriTip[$j-1]."</br>";
										
									}else{
										$tex .= ' & \\textcolor{crta}{\footnotesize{'.$data[$userAnswerIndex[$spremenljivke['id']]].'}}';
										//echo "vodoravni odgovori tip ".$vodoravniOdgovoriTip[$j-1]." $i</br>";
									}
									/* elseif($export_data_type==2 && $vodoravniOdgovoriTip[$j-1]){ //ce je skrcen izvoz IN 

									} */
									
								}else{	//ce so ostali tipi vprasanj
									if($enota == 12){ //ce je slikovni tip										
										$podatekSlikovniTip = $podatekZaSlikovniTip;										
										if($j <= $podatekSlikovniTip){
											$podatekSlikovniTipTmp = $podatekSlikovniTip;
										}else{
											$podatekSlikovniTipTmp = 0;
										}										
										$tex .= "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, 
										$podatekSlikovniTipTmp, $enota, $j, '', $spremenljivke['id']);
									}else{
										$tex .= "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]], $enota, $j, '', $spremenljivke['id']);
									}									
									//echo "tukaj ".$spremenljivke['id']." </br>";									
									//echo "userAnswerData: ".$data[$userAnswerIndex[$spremenljivke['id']]]."</br>";
								}
							}
						}elseif($enota==2||$enota==6){	//roleta ali izberite s seznama
							if($export_format == 'pdf'){	//ce je pdf
								$beginItemize = '& \begin{itemize}[leftmargin=*]';	//zacetek itemize, ce je pdf
							}else{
								$beginItemize = '& \begin{itemize}';	//zacetek itemize, ce je rtf
							}
							if($spremenljivke['tip']!=24){ //ce ni kombinirana tabela
								if($j==1){	//ce je prvi mozen odgovor v roleti ali seznamu
									$tex .= $beginItemize;	//zacetek itemize
								}
								//$tex .= '\item[] '.$vodoravniOdgovori[$j-1];	//izris odgovora v roleti ali seznamu kot item							
							}/* elseif($spremenljivke['tip']==24){	//ce je kombinirana tabela
								if($presirokaKombo == 0){	//ce ni presiroka kombinirana tabela, zacni z itemize
									if($roletaAliSeznam){ 	//ce je zacetek seznama ali rolete
										//$tex .= $beginItemize;	//zacetek itemize
										//$tex .= ' & ';
										$roletaAliSeznam = 0;
									}
								}								
							} */
							
							if($export_subtype=='q_empty'){	//ce je prazen vprasalnik
								//echo count($vodoravniOdgovori);								
								if($spremenljivke['tip']==24){	//ce je kombinirana tabela z izberite s seznama (ali roleto)
									//echo "id spremenljivk $sprID  ".$vodoravniOdgovoriSprId[$j]." za indeks $j </br>";									
									//$tex .= ' & '.$vodoravniOdgovori[$j-1];	//izpis odgovora
									//$tex .= ' & radio';	//izpis kot radio odgovora
									$tex .= "& ".$this->getAnswerSymbol($export_format, $fillablePdf, $vodoravniOdgovoriTip[$j-1], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
								}
								else{	//ce je roleta ali seznam
									if($i==1){	//samo za prvo vrstico izpisi vse mozne odgovore v roleti
										$tex .= '\item[] '.$vodoravniOdgovori[$j-1];	//izris odgovora v roleti kot item
									}elseif($j==1){
										$tex .= '\item[] ';	//prazno vrstico
									}
								}
							}else{	//drugace, ce je vprasalnik z odgovori
								if($spremenljivke['tip'] != 24){	//ce ni kombinirana tabela z izberite s seznama (ali roleto)
									if($data[$userAnswerIndex[$spremenljivke['id']]]==($indeksRoleta)){	//ce je prisoten podatek za doloceni indeks seznama, ga izpisi
										//$tex .= '& \\textcolor{crta}{'.$vodoravniOdgovori[$j-1].'}';	//izris odgovora respondenta v roleti ali seznamu
										$tex .= '& \\textcolor{crta}{\footnotesize{'.$vodoravniOdgovori[$j-1].'}}';	//izris odgovora respondenta v roleti ali seznamu									
										$noItem = 0;
										//echo "podatek je prisoten: ".$vodoravniOdgovori[$j-1]."</br>";
									}else{
										$tex .= ' & '.$vodoravniOdgovori[$j-1];
									}
								}else{ //ce je kombinirana tabela z izberite s seznama (ali roleto)
									$tex .= ' & \\textcolor{crta}{\footnotesize{'.$data[$userAnswerIndex[$spremenljivke['id']]].'}}';
									/* echo "odgovor : ".$data[$userAnswerIndex[$spremenljivke['id']]]."</br>";
									print_r($data); */
								}
								
							}
							
							$indeksRoleta++;
	 						/* echo "indeks za testirati tale del: ".$j."</br>";							
							echo "spremenljivka sprID: ".$sprID."</br>";
							echo "spremenljivka odoravniOdgovoriSprId: ".$vodoravniOdgovoriSprId[$j]."</br>"; */
							
							

							if($spremenljivke['tip']==24&&$sprID!=$vodoravniOdgovoriSprId[$j]){//ce je naslednji ID spremenljivke razlicen od trenutnega ID
								if($presirokaKombo == 0){
									//$tex .= '\end{itemize}';	//zakljucek itemize
								}
								$roletaAliSeznam = 1;
							}
						
						}elseif($enota == 4){	//ena moznost proti drugi
							//$tex .= '& '.$simbolTex.' & '.$lang['srv_tip_sample_t6_4_vmes'].' & '.$simbolTex;
							//$this->getAnswerSymbol($export_format, $fillablePdf, $vodoravniOdgovoriTip[$j-1], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]])
							//echo "userAnswerData: ".$data[$userAnswerIndex[$spremenljivke['id']]]." ".$spremenljivke['id']."</br>";
							//echo "V latexSurveyElement </br>";
							//echo "userAnswerData: ".$data[$userAnswerIndex[$spremenljivke['id']]]." </br>";
							//echo "indeks: ".$userAnswerIndex[$spremenljivke['id']]." </br>";
							if($data[$userAnswerIndex[$spremenljivke['id']]]==1){
								$simbolTex1=$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
								$simbolTex2=$simbolTex;							
								//echo "levo </br>";
							}elseif($data[$userAnswerIndex[$spremenljivke['id']]]==2){
								$simbolTex1=$simbolTex;
								$simbolTex2=$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, 1);
								//echo "desno</br>";
							}elseif($data[$userAnswerIndex[$spremenljivke['id']]]==''){
								$simbolTex1=$simbolTex;
								$simbolTex2=$simbolTex;
								$simbolTex3='';
							}						
							$tex .= '& '.$simbolTex1.' & '.$lang['srv_tip_sample_t6_4_vmes'].' & '.$simbolTex2;
							//echo "userAnswerData: ".$data[$userAnswerIndex[$spremenljivke['id']]]."</br>";
						}elseif($enota == 5){	//maxdiff
							//$tex .= $navpicniOdgovori[$i-1].' & '.$simbolTex;
							$tex .= $navpicniOdgovori[$i-1].' & '.$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);					
						}elseif($enota == 8){	//tabela da/ne
							//$tex .= ' & '.$simbolTex;
							$tex .= ' & '.$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);
						}
						
						$indeksOdgovorovRespondentMultiNumText++;

						$userAnswerIndex[$spremenljivke['id']]++;
						//echo "simbolTex: ".$simbolTex."</br>";
					}	//IZRIS PO STOLPCIH - KONEC
				}else if($fillablePdf == 1){
					$isCheckBox = null;
					for($j = 1; $j < $numColSql; $j++){
						if($isCheckBox){
							$tex .= '& {\CheckBox[name=checkbox_'.$j.'_'.$i.',checkboxsymbol=\ding{56}]{}}';
						}else{
							$tex .= '& {\ChoiceMenu[radio, name=radio_'.$i.',radiosymbol=\ding{108}]{}{='.$j.'}}';
						}
					}
				}
				
				if($enota==1 || $enota==4){	//ce je "diferencial tabela" ali "ena moznost proti drugi", dodaj se tekst v zadnjem stolpcu tabele
					$tex .= ' & '.$navpicniOdgovori2[$i-1].' ';	//tekst v drugem stolpcu ob symbol
					
					if(($enota==4 && count($missingOdgovori)!=0)||($enota==1 && $trak==1 && count($missingOdgovori)!=0)){	//ce je "ena moznost proti drugi" in so missingi ALI je "diferencial tabela" na traku in so missingi
						for($m=0;$m<count($missingOdgovori);$m++){						
							//$tex .= ' & '.$simbolTex;						
							$tex .= ' & '.$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]);						
							$userAnswerIndex[$spremenljivke['id']]++;
							if($enota==4){	//ce je "ena moznost proti drugi"
								$tex .= ' '.$missingOdgovori[$m];	//izpisi se missing odgovor
							}
						}
					}
					
				}
				
				if($enota==5&&count($missingOdgovori)!=0){	//ce je maxdiff in so missingi
					 for($m=0;$m<count($missingOdgovori);$m++){
						//$tex .= ' & '.$simbolTex.' '.$missingOdgovori[$m];
						$tex .= ' & '.$this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $numColSql, 0, $data[$userAnswerIndex[$spremenljivke['id']]]).' '.$missingOdgovori[$m];
						$userAnswerIndex[$spremenljivke['id']]++;
					}
				}
							
				if(($enota == 2 || $enota == 6)&&$spremenljivke['tip']!=24){	//ce je roleta ali seznam in ni kombinirana tabela
					$tex .= '\end{itemize}';	//zakljucek itemize

					/* if($export_format == 'rtf'){	//ce je rtf						
						$tex .= ' \hline '; //dodaj crto na koncu vrstice
					} */

				}
				
				$tex .= $texNewLine;
				//echo "tex: ".$tex."</br>";
				if($spremenljivke['tip']==24){
					//$userAnswerIndex++;
				}
			}else{
				$userAnswerIndex[$spremenljivke['id']]=$z;
			}
			if(($enota == 2 || $enota == 6)&&$spremenljivke['tip']!=24){	//ce je roleta ali seznam in ni kombinirana tabela
				if($export_format == 'rtf'){	//ce je rtf						
					$tex .= ' \hline '; //dodaj crto na koncu vrstice
				}
			}
		}
		//IZRIS PO VRSTICAH - KONEC
		//echo "tex: ".$tex."</br>";
		return $tex;
	}
	#funkcija, ki skrbi za izris vrstic tabele (z multigrid) - konec ###########################################################
		
	function getUserId() {return ($this->usr_id)?$this->usr_id:false;}
		
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja, ki niso grid ali kombinirana tabela
	function GetUsersData($db_table=null, $spremenljivkeId=null, $spremenljivkeTip=null, $usr_id=null, $loop_id_raw=null){
		$userDataPresent = 0;	//belezi, ali je odgovor respondenta prisoten in je indeks za določena polja, ki shranjujejo podatke o odgovorih respondenta
		$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		//echo "loop_id v GetUsersData: ".$loop_id."</br>";
		
		// če imamo vnose, pogledamo kaj je odgovoril uporabnik
		//if( in_array($spremenljivkeTip, array(21, 7, 8, 18)) ){	//ce je tip besedilo ali stevilo ali datum ali vsota
		if( in_array($spremenljivkeTip, array(21, 4, 7, 8, 18)) ){	//ce je tip besedilo ali besedilo staro (4) ali stevilo ali datum ali vsota
			$sqlUserAnswerString ="SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' AND loop_id $loop_id ";
		}elseif($spremenljivkeTip==17){		//ce je razvrscanje
			//$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
			//$sqlUserAnswerString = "SELECT vrstni_red FROM srv_data_rating WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswerString = "SELECT vrstni_red FROM srv_data_rating WHERE spr_id='".$spremenljivkeId."' AND usr_id='$usr_id' AND loop_id $loop_id ";
			//echo $sqlUserAnswerString."</br>";
		}elseif($spremenljivkeTip==26){		//ce je lokacija
			//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswerString ="SELECT IF(dm.lat > 0, dm.lat, vm.lat) as lat, IF(dm.lng > 0, dm.lng, vm.lng) as lng, IF(dm.address != \"\", dm.address, vm.address) as address, text FROM srv_data_map as dm "
                                . "LEFT JOIN (SELECT lat, lng, address, vre_id FROM srv_vrednost_map) AS vm on vm.vre_id=dm.vre_id "
                                . "WHERE spr_id='".$spremenljivkeId."' AND usr_id='$usr_id' AND loop_id $loop_id ";
                        //echo $sqlUserAnswerString."</br>";
		}elseif($spremenljivkeTip==27){		//ce je heatmap
			//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$spremenljivkeId."' AND usr_id='$usr_id' AND loop_id $loop_id ";
			//echo $sqlUserAnswerString."</br>";
		}else{	
			//$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$db_table." WHERE spr_id='$spremenljivkeId' AND usr_id=$usr_id";
			$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$db_table." WHERE spr_id='$spremenljivkeId' AND usr_id='$usr_id' AND loop_id $loop_id";
			//echo $sqlUserAnswerString."</br>";
		}
		
		$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
		
		if( in_array($spremenljivkeTip, array(21, 4, 7, 8, 18, 17)) ){//ce je tip besedilo ali stevilo ali datum ali vsota ali razvrscanje
			$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
			if($rowAnswers){	//ce je kaj v bazi
				//echo "Nekaj je v bazi za spremenljivko".$spremenljivkeId." in usr".$usr_id."</br>";
				$userDataPresent++;
			}
		}else{
			if($sqlUserAnswer){	//ce je kaj v bazi
				while ($rowAnswers = mysqli_fetch_assoc($sqlUserAnswer)){
					if($spremenljivkeTip==26||$spremenljivkeTip==27){
						//$this->userAnswer = $rowAnswers;
						$this->userAnswer[$userDataPresent] = $rowAnswers;
						//echo "rowAnswers: ".$this->userAnswer['address'].' za odgovore tip '.$spremenljivkeTip.' id '.$spremenljivkeId.' usr '.$usr_id.'</br>';	
						$userDataPresent++;
					}else{
						$this->userAnswer[$rowAnswers['vre_id']] = $rowAnswers['vre_id'];
						//echo "rowAnswers: ".$rowAnswers['vre_id'].' za odgovore tip '.$spremenljivkeTip.' id '.$spremenljivkeId.' usr '.$usr_id.'</br>';
						if($rowAnswers['vre_id']>0){
							$userDataPresent++;
						}
					}				
				}		
			}
		}
		//echo "userDataPresent za tip ".$spremenljivkeTip." id".$spremenljivkeId." usr ".$usr_id." je:".$userDataPresent."</br>";
		return $userDataPresent;
	}
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja, ki niso grid ali kombinirana tabela - konec
	
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja z grid	
	function GetUsersDataGrid($spremenljivke=null, $db_table=null, $rowVrednost=null, $rowVsehVrednosti=null, $usr_id=null, $subtip=null, $loop_id_raw=null, $export_data_type=null){
		$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		
		// poiščemo kaj je odgovoril uporabnik: PREVERITI, CE JE POTREBEN STAVEK Z LOOP IN KDAJ JE TO AKTUALNO
		if(($spremenljivke['tip']==16)||($spremenljivke['tip']==6&&$spremenljivke['enota']==3)){	//ce je grid checkbox ali dvojna tabela
			//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
			//$sqlString = "SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			
			//$sqlString = "SELECT grd_id, vre_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			$sqlString = "SELECT grd_id, vre_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
			
			//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']);
			$sqlUserAnswer = sisplet_query($sqlString);
		//}elseif($spremenljivke['tip']==6){	//ce je grid radio
		}elseif($spremenljivke['tip']==6){	//ce je grid radio
			//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");

			//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			
			//$sqlString ="SELECT grd_id, vre_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			$sqlString ="SELECT grd_id, vre_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
			//echo $sqlString."</br>";
			$sqlUserAnswer = sisplet_query($sqlString);
			
			//echo $sqlString."</br>";
		}elseif($spremenljivke['tip']==19||$spremenljivke['tip']==20){	//ce je grid besedila ali stevil
			$sqlString = "SELECT grd_id, text, vre_id FROM srv_data_textgrid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			//$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']);
			//echo "sqlString: ".$sqlString."</br>";
			$sqlUserAnswer = sisplet_query($sqlString);
		}elseif($spremenljivke['tip']==24){	//ce je kombo
			//echo "Subtip kombo vprasanja: ".$subtip."</br>";
			//echo "enota kombo vprasanja: ".$rowVrednost['enota']."</br>";
			
			if($subtip==6){	//ce je grid radio
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = ".$rowVrednost['id']);
				//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = ".$rowVrednost['id'];
				//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
				//if($rowVrednost['enota'] != 2 && $rowVrednost['enota'] != 6){	//ce ni roleta ali seznam
				if($rowVrednost['enota'] != 2 && $rowVrednost['enota'] != 6 && ($export_data_type == 1)){	//ce ni roleta in seznam IN je razsirjen izvoz
					$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
				}else{	//ce je roleta ali seznam
					//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id";
					$sqlString ="SELECT g.naslov, gdata.grd_id FROM srv_grid g, srv_data_grid".$db_table." gdata WHERE g.id=gdata.grd_id AND g.spr_id = '".$rowVrednost['spr_id']."' AND gdata.usr_id = '".$usr_id."' AND gdata.vre_id = '".$rowVrednost['id']."' AND gdata.loop_id $loop_id";
				}
				
				//echo $sqlString."</br>";
				$sqlUserAnswer = sisplet_query($sqlString);
				
				//echo $sqlString."</br>";
			}elseif($subtip==16){	//ce je grid checkbox ali dvojna tabela
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
				
				//$sqlString = "SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
				$sqlString = "SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']);
				$sqlUserAnswer = sisplet_query($sqlString);				
			}elseif($subtip==19||$subtip==20){	//ce je grid besedila ali stevil
				$sqlString = "SELECT grd_id, text FROM srv_data_textgrid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];

				$sqlUserAnswer = sisplet_query($sqlString);
				
				//$this->userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				//echo "userAnswer v funkciji: ".$this->userAnswer['text'].'</br>';
			}
		}
		
		//echo $sqlString."</br>";
		//$this->userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		//$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		//echo "userAnswer v funkciji: ".$this->userAnswer['grd_id'].'</br>';
		//echo "userAnswer v funkciji: ".$userAnswer['text'].'</br>';
		//echo "userAnswer v funkciji: ".$userAnswer['grd_id'].'</br>';
		
		return $sqlUserAnswer;

	}
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja z grid - konec
	
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja s kombinirano tabelo
	function GetUsersDataKombinirana($spremenljivke=null, $db_table=null, $usr_id=null, $presirokaTabela=0, $loop_id_raw=null, $export_data_type=null){
	//function GetUsersDataKombinirana($spremenljivke=null, $db_table=null, $usr_id=null, $questionText=null, $loop_id_raw=null, $export_data_type=null){
		$userDataPresent = 0;	//belezi, ali je odgovor respondenta prisoten in je indeks za določena polja, ki shranjujejo podatke o odgovorih respondenta
		$userAnswerSprIds = array();
		$userAnswerSprTip = array();
		$userAnswerSprIdsIndex = 0;
		$orStavek = '';
		//$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		$loop_id = $loop_id_raw;
		
		#za pridobitev stevila vrstic
		//echo "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red </br>";
		$sqlVrednostiKombo = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");		
		$numRowsSql = mysqli_num_rows($sqlVrednostiKombo);
		//echo $numRowsSql."</br>";
		//echo $spremenljivke['id']."</br>";

		#za pridobitev stevila vrstic - konec
		
		#za pridobitev stevila stolpcev
		$sqlStVrednostiKombo = sisplet_query("SELECT count(*) FROM srv_grid g, srv_grid_multiple m WHERE m.spr_id=g.spr_id AND m.parent='".$spremenljivke['id']."'");
		$rowStVrednost = mysqli_fetch_array($sqlStVrednostiKombo);	//stevilo stolpcev		
		$numColSql = $rowStVrednost['count(*)'];	//stevilo vseh stolpcev
		//echo "stevilo stolpcev: ".$numColSql."</br>";
		#za pridobitev stevila stolpcev - konec

		//echo "presirokaTabela: ".$presirokaTabela."</br>";
		if($presirokaTabela==0){	//ce tabela ni presiroka
			$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red");	//pridobimo spr_id in tip podvprasanj, ki sestavljajo kombinirano tabelo
			//echo "SELECT m.spr_id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red"."</br>";
			
			while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
				array_push($userAnswerSprIds, $rowSubGrid['spr_id'] );	//filanje polja s spr_id podvprasanj
				array_push($userAnswerSprTip, $rowSubGrid['tip'] );	//filanje polja s tip podvprasanj
				if($userAnswerSprIdsIndex){
					$orStavek .= ' OR ';
				}
				//$orStavek .= "spr_id='".$rowSubGrid['spr_id']."' ";
				$orStavek .= "v.spr_id='".$rowSubGrid['spr_id']."' ";
				$userAnswerSprIdsIndex++;
			}
		}else{
			$orStavek = "v.spr_id='".$spremenljivke['id']."' ";
		}	
			//echo $orStavek."</br>";
		
		//echo count($userAnswerSprTip)."</br>";
		
		for($i=1;$i<=$numRowsSql;$i++){
			//$sqlVrednostiString = "SELECT id, naslov, spr_id FROM srv_vrednost WHERE (".$orStavek.") AND vrstni_red=".($i).";";
			//$sqlVrednostiString = "SELECT v.spr_id, v.naslov, s.tip, v.id FROM srv_vrednost v, srv_spremenljivka s WHERE v.spr_id=s.id AND (".$orStavek.") AND v.vrstni_red=".($i).";";
			$sqlVrednostiString = "SELECT v.spr_id, v.naslov, s.tip, v.id, s.enota FROM srv_vrednost v, srv_spremenljivka s WHERE v.spr_id=s.id AND (".$orStavek.") AND v.vrstni_red=".($i).";";
			//echo $sqlVrednostiString."</br>";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);
			while($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){
				$sqlVsehVrednostiString = "SELECT id, naslov FROM srv_grid WHERE spr_id='".$rowVrednosti['spr_id']."' ORDER BY 'vrstni_red'";
				
				//echo $sqlVsehVrednostiString."</br>";
				//echo $rowVrednosti['tip']."</br>";
				//echo $rowVrednosti['other']."</br>";
				//echo "Vrednost: ".$rowVrednosti['spr_id']."</br>";
				$sqlVsehVrednosti = sisplet_query($sqlVsehVrednostiString);
				//echo mysqli_num_rows($sqlVsehVrednosti)."</br>";

				
				
				$roletaZabelezena = 0;

				while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednosti)){
					if($roletaZabelezena == 0){
						$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $db_table, $rowVrednosti, $rowVsehVrednosti, $usr_id, $rowVrednosti['tip'], $loop_id, $export_data_type);
					}										
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					if($rowVrednosti['tip']==19||$rowVrednosti['tip']==20){							
						$userAnswers[$userDataPresent] = $userAnswer['text'];
					}else{
						if($roletaZabelezena == 0){
							//$userAnswers[$userDataPresent] = $userAnswer['grd_id'];
							$userAnswers[$userDataPresent] = $userAnswer['naslov'];
							//if($rowVrednosti['enota']==2 || $rowVrednosti['enota']==6){ //ce je roleta ali seznam
							if($rowVrednosti['enota']==2 || $rowVrednosti['enota']==6 || ($export_data_type==2 && $rowVrednosti['tip']==6)){ //ce je roleta ali seznam
								$roletaZabelezena = 1;
								$userDataPresent++;
							}else{
								$userAnswers[$userDataPresent] = $userAnswer['grd_id'];
							}
							
						}
					}
					//echo $this->userAnswer[$userDataPresent]."</br>";
					//echo "odgovori respondenta: ".$userAnswers[$userDataPresent]."</br>";
					//echo "index userDataPresent: ".$userDataPresent."</br>";
					/* if($rowVrednosti['enota']==2){
						$roletaZabelezena = 1;
					}*/
					if($roletaZabelezena == 0){ 
						$userDataPresent++;
					}
				}

			}
			
		}
		//if($questionText){
			//return $userDataPresent;			
		//}else{
			//return $this->userAnswer;
			return $userAnswers;
		//}		
	}
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja s kombinirano tabelo - konec
	
	#funkcija, ki skrbi za pridobitev operatorja iz stevilskega podatka ###########################################################
	function GetOperator($operatorNum=null){
		if ($operatorNum == 0){
			$operator = $this->encodeText('+');
		}elseif ($operatorNum == 1){
			$operator = $this->encodeText('-');
		}elseif ($operatorNum == 2){
			$operator = $this->encodeText('*');
		}elseif ($operatorNum == 3){
			$operator = $this->encodeText('/');
		}
		return $operator;
	}
	#funkcija, ki skrbi za pridobitev operatorja iz stevilskega podatka - konec ###################################################
	
			/**
    * @desc V podanem stringu poisce spremenljivke in jih spajpa z vrednostmi
    */
    function dataPiping ($text='') {
/* 		echo "text: ".$text."</br>";
		echo "Usr_id: ".$this->usr_id."</br>";
		echo "Loop_id: ".$this->loop_id."</br>";
		echo "_____________________</br>"; */
    	Common::getInstance()->Init($this->anketa);
		//echo Common::getInstance()->dataPiping($text, $this->usr_id, $this->loop_id);
        return Common::getInstance()->dataPiping($text, $this->usr_id, $this->loop_id);
    }
	

}