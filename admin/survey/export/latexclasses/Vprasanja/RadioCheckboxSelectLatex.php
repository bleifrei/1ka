<?php
/***************************************
 * Description: Priprava Latex kode za radio, checkbox, select
 *
 * Vprašanje je prisotno:
 * tip 1, 2, 3 z vsemi orientacijami
 *
 * Autor: Patrik Pucer
 * Datum: 06-07/2017
 *****************************************/

//namespace Export\Latexclasses\Vprasanja;

define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip

class RadioCheckboxSelectLatex extends LatexSurveyElement
{
    public function __construct()
    {
        //parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;
	protected $texBigSkip = ' \bigskip ';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	protected $path2ImagesRadio;
	protected $language;
	protected $prevod;

    public static function getInstance()
    {		
        if (self::$_instance)
            return self::$_instance;

        return new RadioCheckboxSelectLatex();
    }
	
	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $preveriSpremenljivko=null, $export_data_type=null, $export_subtype=null, $loop_id=null, $language=null){
		global $lang, $site_path;
		
		$this->language = $language;
		$this->path2ImagesRadio = $site_path.'uploadi/editor/';

		//preverjanje, ali je prevod
		if(isset($_GET['language'])){
			$this->language = $_GET['language'];
			$this->prevod = 1;
		}else{
			$this->prevod = 0;
		}
		//preverjanje, ali je prevod - konec
		
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//echo "exportData za user: ".$usr_id." in language ".$language."</br>";
		//echo "__________________________________</br>";
		
		$texBigSkip = ' \bigskip ';
		$userAnswerData = array();		//belezi podatke respondenta
		$textRArray = array();	//belezi odgovore respondenta, ki se nahajajo v desnem delu vprasanja
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta
		//echo "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red";
		$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red");
		$numRowsSql = mysqli_num_rows($sqlVrednosti);
		$tex = '';
		$oznakaOdgovora = 'a';
		$indeksZaWhile = 1;
		$indeksOdgovorov = 0;
		$oznakaVprasanja = $this->UrediOznakoVprasanja($spremenljivke['id']);	//uredi oznako vprasanja, ker ne sme biti stevilska
		$prviOdgovorSlikovniTip = 0;
		
		if ($usr_id){
			$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);	//zgenerira podatke z odgovori respondenta v $this->userAnswer, zabelezi, ce so podatki prisotni
		}

		//echo "test: ".$userDataPresent."</br>";
		
		#izpis izvoza kratek ali zelo kratek ###############################################################################
		if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je izvoz odgovorov respondenta/respodentov
			//if(($userDataPresent!=0||$preveriSpremenljivko)&&($export_data_type==0||$export_data_type==2)){	//ce (so podatki prisotni ali je potrebno pokazati tudi ne odgovorjena vprasanja) in (je tip izvoza kratek ali zelo kratek)		
			if($userDataPresent!=0||$preveriSpremenljivko){	//ce (so podatki prisotni ali je potrebno pokazati tudi ne odgovorjena vprasanja)
				$prviOdgovorSlikovniTip = 1;
				if($export_data_type==0||$export_data_type==2){	//ce je tip izvoza kratek ali zelo kratek
					while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){				
						if( isset($this->userAnswer[$rowVrednost['id']]) ){	//ce je podatek prisoten
						
							#ce je respondent odgovarjal v drugem jeziku ########################
							$rowl = $this->srv_language_vrednost($rowVrednost['id']);							
 							if (strip_tags($rowl['naslov']) != '') $rowVrednost['naslov'] = $rowl['naslov'];
							if (strip_tags($rowl['naslov2']) != '') $rowVrednost['naslov2'] = $rowl['naslov2'];							
							#ce je respondent odgovarjal v drugem jeziku - konec ################
							
							$stringTitle = ( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] );						
							$stringTitle = Common::getInstance()->dataPiping($stringTitle, $usr_id, $loop_id);
							//$stringTitle = '\\textcolor{crta}{'.$this->encodeText($stringTitle).'}';
							$stringTitle = '\\textcolor{crta}{'.$this->encodeText($stringTitle, 0, '', $indeksZaWhile).'}'; //encodeText($text='', $vre_id=0, $naslovStolpca = 0, $img_id=0)
							
							//echo $stringTitle."za indeks: ".$indeksZaWhile."</br>";
							//stetje stevila vrstic
							//$stetje_vrstic = $this->pdf->getNumLines($stringTitle, 180*$expand_width);					
							// še dodamo textbox če je polj other
							$_txt = '';
							if ( $rowVrednost['other'] == 1 && $usr_id ){
								//$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
								$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id);
								$row4 = mysqli_fetch_assoc($sqlOtherText);
								$_txt = ' \\textcolor{crta}{'.$row4['text'].'}';
							}
							//$tex .= ' '.$stringTitle.$_txt.',';
							if($indeksZaWhile==1){
								//$tex .= ' '.$stringTitle.$_txt.' ';
								$tex .= $stringTitle.$_txt;
							}else{
								//$tex .= ', '.$stringTitle.$_txt;
								$tex .= ', \\\\'.$stringTitle.$_txt;
							}				
							$indeksZaWhile++;
						}
						
						$indeksOdgovorov++;				
					}
				//echo "končni tex: ".$tex."</br>";
				}
			}
		}
		#izpis izvoza kratek ali zelo kratek - konec ###########################################################################
		
		
		#izpis praznega vprasalnika ali dolgega izvoza (vprasalnika z odgovori respondenta) ##################################################
		if($export_subtype=='q_empty'||$export_data_type==1||$export_subtype=='q_comment'){	//ce je izpis praznega vprasalnika ali dolgega izvoza
			/* echo "orientacija: ".$spremenljivke['orientation']."</br>";
			echo "tip: ".$spremenljivke['tip']."</br>"; */
			if($spremenljivke['orientation']==5){	//ce je postavitev Potrditev
				if($export_format == 'pdf'){	//ce je pdf
					$tex .= $this->texBigSkip;
					$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
				}else{	//ce je rtf
					//if($spremenljivke['orientation']==0 || $spremenljivke['orientation']==2){	//ce sta vodoravni orientaciji
						//$tex .= $texNewLine;	//dodaj na koncu vprasanja prazno vrstico
						$tex .= $this->texBigSkip;
					//}
				}
				//echo "tukaj";
				return $tex;
			}
			
			#za ureditev preloma odgovorov, ce so odgovori ob vprasanju - najprej je potrebno zabeleziti dolzino besedila vprasanja
			if($spremenljivke['orientation']==0 && $export_format == 'pdf'){	//vodoravno ob vprasanju, ce je pdf					
					$tex .= '\settowidth{\questionLength}{'.$this->encodeText($questionText).'}'; //v definirano dolzino shranimo trenutno dolzino teksta vprasanja
					$tex .= '\addtolength{\questionTotalLength}{\questionLength}'; //celotni dolzini dodamo dolzino vprasanja
					
					//ce je opomba prisotna, daj spremenljivko na 2
					if($spremenljivke['info'] != ''){	
						$tex .= '\setcounter{opomba}{2}';
					}else{
						$tex .= '\setcounter{opomba}{0}';
					}						
			}
			#za ureditev preloma odgovorov, ce so odgovori ob vprasanju - konec
			
			if($spremenljivke['orientation']==7){	//navpicno - tekst levo
				//$tex .= '\begin{tabular}{l l}'.$texNewLine;
				if($export_format == 'pdf'){
					$tex .= '\begin{tabularx}{.5\textwidth}{l l}';
				}else{
					$tex .= '\begin{tabular}{l l}'.$texNewLine;	//za omogociti izris odgovorov v tabeli
				}				
			}elseif($spremenljivke['orientation']==8){	//ce je "povleci-spusti"			
				$tex .= '\setlength{\parindent}{0.1\textwidth} ';			
				//prva vrstica pred tabelo z odgovori
				if($export_format == 'pdf'){	//ce je pdf
					$tex .= '\begin{tabular}{l c l} ';	//izris z vecstolpicno tabelo
					//$tex .= '\begin{tabularx}{.5\textwidth}{l c l} ';	//izris z vecstolpicno tabelo
					$tex .= $lang['srv_ranking_available_categories'].': & \hspace{0.1\textwidth} & '.$lang['srv_drag_drop_answers'].': '.$texNewLine;
					$tex .= '\rule{0.4\textwidth}{0.4 pt} &  & \rule{0.4\textwidth}{0.4 pt} \end{tabular} '.$texBigSkip;
					$tex .= $texNewLine;
				}else{	//ce je rtf
					$tex .= '\begin{tabular}{l} ';	//izris z enostolpicno tabelo
					$tex .= $lang['srv_ranking_available_categories'].': '.$texNewLine;	//Rapolozljive kategorije					
					//$tex .= '\hline \end{tabular} '.$texBigSkip;
					$tex .= '\hline \end{tabular} ';
				}
				//prva vrstica pred tabelo z odgovori - konec
				
				if($export_format == 'pdf'){	//ce je pdf					
					//$tex .= '\begin{tabular}{c c c} ';	//izris s tabelo
					$tex .= '\begin{tabularx}{.5\textwidth}{c c c} ';	//izris s tabelo
				}
				
			}elseif($spremenljivke['orientation']==10){	//image hot-spot
				
				$imageName = $this->getImageName('hotspot', $spremenljivke['id'], 'hotspot_image=');
				$imageNameTest = $this->path2ImagesRadio.$imageName.'.png';	//za preveriti, ali obstaja slikovna datoteka na strezniku
				//error_log("za image hot spot ne grid: ".$imageNameTest);
				//echo("za image hot spot ne grid: ".$imageNameTest."</br>");
				if(filesize($imageNameTest) > 0){
					$image = PIC_SIZE."{".$this->path2ImagesRadio."".$imageName."}";	//priprave slike predefinirane dimenzije			
				}else{
					//$image = 'ni slike';
					$image = $lang['srv_pc_unavailable'];
				}
				
				$tex .= $image."".$texNewLine; //izris slike

				//iz baze poberi imena obmocij
				$sqlHotSpotRegions = sisplet_query("SELECT region_name FROM srv_hotspot_regions WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");	
				
				//izris imen obmocij po $sqlHotSpotRegions
				$tex .= $lang['srv_export_hotspot_regions_names'].': '.$texNewLine;	//besedilo "Obmocja na sliki"
				while ($rowHotSpotRegions = mysqli_fetch_assoc($sqlHotSpotRegions))
				{
					$tex .= $rowHotSpotRegions['region_name'].''.$texNewLine;
				}
				
				if($export_data_type==1){	//ce je dolg izvoz, pokazi katera obmocja so bila izbrana
					$tex .= $texNewLine.$lang['srv_export_hotspot_chosen_regions_names'].': '.$texNewLine;	//besedilo "Izbrana obmocja na sliki"
					while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
						if( isset($this->userAnswer[$rowVrednost['id']]) ){	//ce je podatek prisoten
							$stringTitle = ($this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));				
							// še dodamo textbox če je polj other
							$_txt = '';
							if ( $rowVrednost['other'] == 1 && $usr_id ){
								//$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$this->getUserId()."' AND loop_id $loop_id");
								$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id);
								$row4 = mysqli_fetch_assoc($sqlOtherText);
								$_txt = ' '.$row4['text'];
							}
							$tex .= $stringTitle.$_txt.$texNewLine;							
						}
					}
				}
			}elseif($spremenljivke['orientation']==9 || $spremenljivke['orientation']==11){	//ce je "slikovni tip" ali VAS
				if($spremenljivke['orientation']==11){	//ce je VAS
					$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
					$vizualnaSkalaNumber = $spremenljivkaParams->get('vizualnaSkalaNumber');
					$numRowsSql = $vizualnaSkalaNumber;
				}

				//echo "stevilo zadev: ".$numRowsSql."</br>";
				if($spremenljivke['orientation']==9){
					$mejaVAS = 20;
				}elseif($spremenljivke['orientation']==11){
					$mejaVAS = 8;
					$numRowsSql = mysqli_num_rows($sqlVrednosti);
				}
				//if($numRowsSql<20){	//ce je manj kot x slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število
				if($numRowsSql<$mejaVAS){	//ce je manj kot x slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število
					$tableParamsSlikovniTip = '';
					for($i=0; $i<$numRowsSql;$i++){
						$tableParamsSlikovniTip .= ' c ';
					}							
					$tex .= '\begin{tabular}{'.$tableParamsSlikovniTip.'} ';	//izris s tabelo
				}
				//echo "parametri tabele: ".$tableParamsSlikovniTip."</br>";
			}
			
			if($spremenljivke['orientation']!=10){	//ce ni image hot-spot
				
				//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
					$prop['full'] = ( isset($userAnswer[$rowVrednost['id']]) );	
					
					if($this->prevod){ //ce je prevod ankete
						$rowl = $this->srv_language_vrednost($rowVrednost['id']);	//pridobi prevod naslova v ustreznem jeziku						
						$stringTitle = ((( $rowl['naslov'] ) ? $rowl['naslov'] : ( ( $rowl['naslov2'] ) ? $rowl['naslov2'] : $rowl['variable'] ) )); //prevod naslova v ustreznem jeziku
						if($stringTitle == ''){	//ce ni prevoda, prevzemi izvirno
							//$stringTitleRow = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
							$stringTitle = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
						}
					}else{						
						$stringTitle = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
					}

					$stringTitle = Common::getInstance()->dataPiping($stringTitle, $usr_id, $loop_id);					
					
					//echo "naslov: $stringTitle</br>";
					//echo "jezik: ".$this->language."</br>";
					if ( $spremenljivke['tip'] == 1 || $spremenljivke['tip'] == 3 ){											
						$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, $this->userAnswer[$rowVrednost['id']], $spremenljivke['orientation'], $indeksZaWhile, $vizualnaSkalaNumber);
						//$tex .= '{\ChoiceMenu[radio,radiosymbol=\ding{108},name=myGroupOfRadiobuttons]{}{='.$stringTitle.'}}'.$stringTitle.' '.$this->texNewLine;
						$internalCellHeight = '0.3 cm';	//visina praznega okvirja @povleci-spusti						
					}else if ( $spremenljivke['tip']  == 2 ){						
						$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, $this->userAnswer[$rowVrednost['id']]);
						$internalCellHeight = '3 cm'; //visina praznega okvirja @povleci-spusti
					}
					
					if($spremenljivke['orientation']==1&&$spremenljivke['tip'] != 3){	//navpicno						
						//$tex .= $symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).' '.$texNewLine;
						$tex .= $symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).' ';
						//$test = $symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).' '.$texNewLine;
						//echo "tukaj! $test </br>";													
						if($rowVrednost['other'] == 1){	//ce je odgovor Drugo:, izpisi se tabelo za drugo
							$tex .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
							if(isset($this->userAnswer[$rowVrednost['id']])){								
								$sqlOtheText1 = "SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id;								
								$sqlOtherText = sisplet_query($sqlOtheText1);
								$row4 = mysqli_fetch_assoc($sqlOtherText);								
								$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$row4['text'].' }} ';
							}else{
								$tex .= '\fbox{\parbox{0.2\textwidth}{ \hphantom{\hspace{0.2\textwidth}} }} ';								
							}							
							$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
						}
						$tex .= $texNewLine;
					}elseif($spremenljivke['orientation']==7){	//navpicno - tekst levo						
						$text = $this->encodeText($stringTitle, $rowVrednost['id']).' & '.$symbol.' '.$texNewLine;
						$textLength = strlen($text);
						if($textLength > MAX_STRING_LENGTH){						
							$tex .= '\vspace{2 mm}';
							$tex .= '\parbox{'.LINE_BREAK_AT.'}{'.$this->encodeText($stringTitle, $rowVrednost['id']).'} & '.$symbol.' '.$texNewLine;	//tekst odgovora razbij pri LINE_BREAK_AT (5 cm) in zraven dodaj ustrezni simbol
						}else{
							$tex .= $this->encodeText($stringTitle, $rowVrednost['id']).' & ';							
							if($rowVrednost['other'] == 1){	//ce je odgovor Drugo:, izpisi se tabelo za drugo								
								$tex .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
								if(isset($this->userAnswer[$rowVrednost['id']])){								
									$sqlOtheText1 = "SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id;								
									$sqlOtherText = sisplet_query($sqlOtheText1);
									$row4 = mysqli_fetch_assoc($sqlOtherText);								
									$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$row4['text'].' }} ';
								}else{
									$tex .= '\fbox{\parbox{0.2\textwidth}{ \hphantom{\hspace{0.2\textwidth}} }} ';								
								}							
								$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
							}
							$tex .= $symbol.' '.$texNewLine;
						}
						//echo $tex."</br>";
					}elseif($spremenljivke['orientation']==0){	//vodoravno ob vprasanju
						$tex .= ' '.$symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).'  ';
						if($rowVrednost['other'] == 1){	//ce je odgovor Drugo:, izpisi se tabelo za drugo
							$tex .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
							if(isset($this->userAnswer[$rowVrednost['id']])){								
								$sqlOtheText1 = "SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id;								
								$sqlOtherText = sisplet_query($sqlOtheText1);
								$row4 = mysqli_fetch_assoc($sqlOtherText);								
								$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$row4['text'].' }} ';
							}else{
								$tex .= '\fbox{\parbox{0.2\textwidth}{ \hphantom{\hspace{0.2\textwidth}} }} ';								
							}							
							$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
						}
					}elseif($spremenljivke['orientation']==2){	//vodoravno pod vprasanjem					
						$tex .= ' '.$symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).'  ';
						if($rowVrednost['other'] == 1){	//ce je odgovor Drugo:, izpisi se tabelo za drugo
							$tex .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
							if(isset($this->userAnswer[$rowVrednost['id']])){								
								$sqlOtheText1 = "SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id;								
								$sqlOtherText = sisplet_query($sqlOtheText1);
								$row4 = mysqli_fetch_assoc($sqlOtherText);								
								$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$row4['text'].' }} ';
							}else{
								$tex .= '\fbox{\parbox{0.2\textwidth}{ \hphantom{\hspace{0.2\textwidth}} }} ';								
							}							
							$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
						}
					}elseif(($spremenljivke['tip'] == 3&&$spremenljivke['orientation']==1)||$spremenljivke['orientation']==6){	//roleta ali izberite s seznama
						if($export_data_type==1&&isset($this->userAnswer[$rowVrednost['id']])){	//ce je dolg izvoz in je podatek za odgovor
							//$tex .= ' \textbf{'.$this->encodeText($stringTitle, $rowVrednost['id']).'} '.$texNewLine;
							//$tex .= ' \textbf{'.$this->encodeText($stringTitle, $rowVrednost['id']).' ';
							$tex .= ' \textbf{'.$this->encodeText($stringTitle, $rowVrednost['id']).' }';
						}else{
							//$tex .= $this->encodeText($stringTitle, $rowVrednost['id']).' '.$texNewLine;
							$tex .= $this->encodeText($stringTitle, $rowVrednost['id']).' ';
						}
						if($rowVrednost['other'] == 1){	//ce je odgovor Drugo:, izpisi se tabelo za drugo
							$tex .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
							if(isset($this->userAnswer[$rowVrednost['id']])){								
								$sqlOtheText1 = "SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id;								
								$sqlOtherText = sisplet_query($sqlOtheText1);
								$row4 = mysqli_fetch_assoc($sqlOtherText);								
								$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$row4['text'].' }} ';
							}else{
								$tex .= '\fbox{\parbox{0.2\textwidth}{ \hphantom{\hspace{0.2\textwidth}} }} ';								
							}							
							$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
							if($export_data_type==1&&isset($this->userAnswer[$rowVrednost['id']])){	//ce je dolg izvoz in je podatek za odgovor
								$tex .= '}';
							}
							
						}
						$tex .= $texNewLine;
					}elseif($spremenljivke['orientation']==8){	//povleci-spusti
						
						if(isset($this->userAnswer[$rowVrednost['id']])){
							$textR = $this->encodeText($stringTitle, $rowVrednost['id']);
							$textRArray[$indeksZaWhile] = $textR;	//rabimo kasneje, za izpis rtf desne strani vprasanja, ce izpisujemo odgovore respondenta
							$textL = '';
						}else{
							$textL = $this->encodeText($stringTitle, $rowVrednost['id']);
							$textR = '';
						}
						
						if($export_format == 'pdf'){	//ce je pdf							

							if($textL){
								$tex .= '\indent \fbox{\parbox{0.2\textwidth}{ \centering '.$textL.' }} & \hspace{0.2\textwidth} ';	//prva dva stolpca
							}else{
								$tex .= '\indent \hspace{0.2\textwidth} ';
							}							
							
							if($indeksZaWhile == 1&&($export_subtype=='q_empty')){								
								$tex .= '& \hspace{1.2 cm}  \multirow{'.$numRowsSql.'}{*}{\fbox{\parbox[t]['.$internalCellHeight.']{0.2\textwidth}{ \hphantom{\hspace{0.2\textwidth}}} } } ';	//v prvi vrstici izrisi prazen okvir, ki se razpotegne skozi vse vrstice						
							}elseif($export_subtype=='q_empty'){
								$tex .= '& ';	//izrisi potrebno praznino za multirow okvir iz prve vrstice
							}else{
								if($textR){
									$tex .= ' & \hspace{0.3\textwidth} & \fbox{\parbox{0.2\textwidth}{ \centering '.$textR.' }} ';	//izpisi okvir z odgovorom, ce je ta prisoten
								}else{
									$tex .= '& ';	//izpisi neviden okvir
								}								
							}
							$tex .= $texBigSkip;
							$tex .= $texNewLine;
						}else{	//ce je rtf, uredi izvoz leve strani vprasanja
							if((!isset($this->userAnswer[$rowVrednost['id']])&&$export_data_type==1)||$export_subtype=='q_empty'){	//ce je podatek in je dolg izvoz ali je izvoz praznega vprasalnika								
								$tex .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
								//$tex .= '\begin{tabular}{|c|} \hline';	//izris s tabelo z obrobama levo desno in zgoraj					
								//$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$this->encodeText($stringTitle).' }} ';
								$tex .= '\fbox{\parbox{0.2\textwidth}{ '.$textL.' }} ';
								$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
							}
						}
						
					}elseif($spremenljivke['orientation']==9){	//ce je "slikovni tip"
						/* if($numRowsSql>=20){	
							$tex .= '| ';
						} */
						if($indeksZaWhile == 1){					
							$tex .= ICON_SIZE."{".$this->path2Images."".$this->getCustomRadioSymbol($spremenljivke['id'], $prviOdgovorSlikovniTip)."}";						
						}else{
							if($numRowsSql<20){	//ce je manj kot 20 slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število
								$tex .= ' & ';
							}
							$tex .= ICON_SIZE."{".$this->path2Images."".$this->getCustomRadioSymbol($spremenljivke['id'], $prviOdgovorSlikovniTip)."}";
							//$tex .= ' & '.ICON_SIZE."{".$this->path2Images."".$this->getCustomRadioSymbol($spremenljivke['id'], $prviOdgovorSlikovniTip)."}";							
						}
						if($numRowsSql>=20){	
							$tex .= ' ('.$indeksZaWhile.') ';
							//$tex .= ' ('.$indeksZaWhile.')| ';
						}
						
						if(isset($this->userAnswer[$rowVrednost['id']])&&$export_data_type==1){
							$prviOdgovorSlikovniTip = 0;
						}elseif($export_data_type==1&&$prviOdgovorSlikovniTip==1){
							$prviOdgovorSlikovniTip = 1;
						}
						
					}elseif($spremenljivke['orientation']==11){	//ce je VAS
						//$tex .= ' '.$symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).'  ';
						if($indeksZaWhile == 1){
							if($numRowsSql<=7){	//ce je manj kot 7 slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število											
								$tex .= ' '.$symbol;
							}
						}else{
							if($numRowsSql<=7){	//ce je manj kot 7 slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število
								$tex .= ' & '.$symbol;
							}
						}
						if($numRowsSql>7){	
							$tex .= ' ('.$indeksZaWhile.') ';
						}
					}else{	//ce ni urejenega izrisa naj bo default oz. navpicno
						$tex .= $symbol.' '.$this->encodeText($stringTitle, $rowVrednost['id']).' '.$texNewLine;
					}				
					
					$oznakaOdgovora++;
					$indeksZaWhile++;
				}
				//pregled vseh moznih vrednosti (kategorij, mozni odgovori) po $sqlVrednosti - konec
			}
			
			if($spremenljivke['orientation']==9 || $spremenljivke['orientation']==11){	//ce je "slikovni tip" ali VAS - izrisi se spodnjo vrstico odgovorov s stevilkami v oklepaju
				//if($numRowsSql<20){	//ce je manj kot 20 slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število
				if($numRowsSql<$mejaVAS){	//ce je manj kot 20 slikovnih tipov, izpisemo s tabelo, drugace simbol in zraven število
					for($i=1;$i<=$numRowsSql;$i++){
						if($i==1){
							$tex .= ' \\\\ ('.$i.')';
						}else{
							$tex .= ' & ('.$i.')';
						}
					}
					$tex .= ' \end{tabular}';	//zakljuci izris odgovorov v tabeli za "slikovni tip"					
				}
				//$tex .= $texNewLine;
				$tex .= $texNewLine;
			}elseif($spremenljivke['orientation']==8 || $spremenljivke['orientation']==7 ){	//ce je "povleci-spusti" ali "navpicno - tekst levo"
				if($export_format == 'pdf'|| $spremenljivke['orientation']==7){	//ce je pdf
					//if($spremenljivke['orientation']==7 && $export_format == 'pdf'){
					if($export_format == 'pdf'){
						$tex .= '\end{tabularx}';	//za zakljuciti izrisa odgovorov v tabeli//tabularx
						$tex .= $this->texBigSkip;
						$tex .= $this->texBigSkip;
						$tex .= ' \noindent ';
					}else{
						$tex .= ' \end{tabular}';	//za zakljuciti izris odgovorov v tabeli
						$tex .= $texNewLine;
						$tex .= $texNewLine;
					}
					
				}elseif($export_format == 'rtf'&&$spremenljivke['orientation']==8){	//ce je rtf in povleci-spusti, uredi izvoz desne strani vprasanja 
					//izpis opisnega teksta za Odgovori:
					$tex .= '\begin{tabular}{l} ';	//izris z enostolpicno tabelo
					$tex .= $lang['srv_drag_drop_answers'].': '.$texNewLine;	//Odgovori:
					//$tex .= '\hline \end{tabular} '.$texBigSkip;
					$tex .= '\hline \end{tabular} ';
					//izpis opisnega teksta za Odgovori: - konec					

					if($export_data_type==1){	//ce je dolg izvoz, podatkov respondenta
						foreach($textRArray as $odgovor){						
							//izpis okvirja z odgovorom respondenta							
							$tex .= '\begin{tabular}{c} ';	//izris z enostolpicno tabelo brez obrob
							//$tex .= '\begin{tabular}{|c|} \hline';	//izris z enostolpicno tabelo z obrabama levo desno in zgoraj							
							
							$tex .= $odgovor.$texNewLine;							
							
							$tex .= '\end{tabular} ';
							//izpis okvirja z odgovorom respondenta - konec
						}				
						
					}else{	//ce je izvoz praznega vprasalnika
						//izpis praznega okvirja
						$tex .= '\begin{tabular}{c} ';	//izris z enostolpicno tabelo brez obrob
						//$tex .= '\begin{tabular}{|c|} \hline';	//izris z enostolpicno tabelo z obrabama levo desno in zgoraj
						//$tex .= ' opsasa '.$texNewLine;
						$tex .= $texBigSkip;
						$tex .= '\end{tabular} '.$texBigSkip;
						//izpis praznega okvirja - konec
					}

				}
				//$tex .= $texBigSkip;
			}
			
			if(($spremenljivke['orientation']==0||$spremenljivke['orientation']==2)){	//(ce je vodoravno ob vprasanju ali pod vprasanjem)
				$tex .= $texNewLine;
			}
		
		}
		#izpis praznega vprasalnika ali dolgega izvoza (vprasalnika z odgovori respondenta) - konec ####################################
		
/* 		$tex .= $texNewLine;
		$tex .= $texNewLine; */

		if(($spremenljivke['orientation'] == 7 || $spremenljivke['orientation'] == 8) && $export_data_type==2){ 	//ce (je tekst na levi ALI povleci spusti) IN je skrcen izpis
			$tex .= $texNewLine;
			$tex .= $texNewLine;
		}

		if( !in_array($spremenljivke['orientation'], array(7, 8)) ){
			$tex .= $this->texBigSkip;
			$tex .= $texNewLine;
		}
		
		if($export_format == 'pdf'){	//ce je pdf			
			//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
		}
		
		return $tex;	
	}	
}