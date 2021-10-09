<?php
/***************************************
 * Description: Priprava Latex kode za Besedilo
 *
 * Vprašanje je prisotno:
 * tip 21 z vsemi orientacijami
 *
 * Autor: Patrik Pucer
 * Datum: 07-08/2017
 *****************************************/


define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip

class BesediloLatex extends LatexSurveyElement
{
	var $internalCellHeight;
	protected $texBigSkip = '\bigskip';
	protected $export_subtype;
	protected $path2SignatureImages;
	protected $texGapBeforeTable = '\vspace*{-\baselineskip}';	
	
    public function __construct()
    {
        //parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new BesediloLatex();
    }
	
	public function export($spremenljivke=null, $export_format='', $questionText=null, $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $anketa=null, $export_subtype='', $preveriSpremenljivko=null, $export_data_type=null, $loop_id=null){
		global $site_path;
		$this->path2HeatmapImages = $site_path.'main/survey/uploads/';
		
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		$this->export_subtype=$export_subtype;
		
		//preveri, ce je kaj v bazi		
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		//echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;
			global $site_url;
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			
			$tex = '';
			$podatekVBazi = 0;
			
			$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, 0);	//poberi simbol checkbox za other in missing moznosti odgovora
			
			//nastavitve text box-a iz baze##########################
			$steviloOken = $spremenljivke['text_kosov'];
			$postavitev = $spremenljivke['orientation'];
			
			$polozajBesedila = $spremenljivke['text_orientation'];	//polozaj besedila pred text box-om
			
			$textboxHeightOrig = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
			$textboxHeight = ($textboxHeightOrig*0.3).'cm';
			
			$textboxWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			if($textboxWidth == -1){	//ce je vrednost -1, je default t.j. 30 oz. 0.30 sirine
				$textboxWidth = 0.30;
			}else{	//drugace, izracunaj sirino
				$textboxWidth = $textboxWidth/100;	//pretvorimo sirino v odstotke oz. decimalke
			}
			//$textboxWidth = $textboxWidth / $steviloOken;	//ce je vec oken, se sirina text box-a ustrezno/proporcionalno zmanjsa
			$textboxWidth = $textboxWidth / ($steviloOken*1.1);	//ce je vec oken, se sirina text box-a ustrezno/proporcionalno zmanjsa
			$textboxWidth = (string)$textboxWidth; //pretvorimo stevilo (decimalke) v string

			/* echo "steviloOken: ".$steviloOken."</br>";
			echo "visina iz nastavitev: ".$textboxHeightOrig."</br>";
			echo "sirina iz nastavitev: ".$textboxWidth."</br>";
			echo "sirina izracunana: ".$textboxWidth."</br>"; */
			//textboxWidth se rocno povozi pod "ureditev parametrov za tabelo"
			
			//nastavitve text box-a iz baze - konec####################
			
			$array_others = array();	//polje za drugo, missing, ...		
			$besedila = array();	//polje, ki hrani besedila, ki pridejo poleg text box-ov
			$besedila = [];	
			$textBoxes = array();	//polje, ki hrani latex za prazne text box-e
			$textBoxes = [];
			$textboxAllignment = 'c';	//poravnava textboxa z besedilom
			
			$oznakaOdgovora = 'a';
			$indeksZaWhile = 1;
			$oznakaVprasanja = $this->UrediOznakoVprasanja($spremenljivke['id']);	//uredi oznako vprasanja, ker ne sme biti stevilska	
		
			//ureditev parametrov za tabelo#############################
			$parameterTabular = '';
			if($steviloOken == 1){	//ce je samo en okvir za vnos besedila
				if($polozajBesedila==0 || $polozajBesedila!=1){	//ce ni besedila ali besedilo ni SPREDAJ
					$steviloStolpcevTabele = $steviloOken;
				}elseif($polozajBesedila==1){	//ce je besedilo SPREDAJ
					$steviloStolpcevTabele = $steviloOken*2;
				}
			}else{	//ce je vec okvirjev za vnos besedila, se ignorira nastavitev za besedilo SPREDAJ, saj se bo prineslo na ZGORAJ
				if($polozajBesedila==1){
					$polozajBesedila=3;
				}
				$steviloStolpcevTabele = $steviloOken;
			}

			for($i = 0; $i < $steviloStolpcevTabele; $i++){
				if($polozajBesedila==1 && $i%2==0){	//ce je polozaj besedila SPREDAJ in je stolpec za besedilo
					if($userDataPresent){
						$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');	//desna poravnava stolpca
					}else{
						$parameterTabular .= ($export_format == 'pdf' ? 'R' : 'l');	//desna poravnava stolpca
					}
				}else{
					$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca
				}			
			}
			//echo "stevilo oken: ".$steviloOken."</br>";
			//echo "parametri tabele: ".$parameterTabular."</br>";
			
			//$textboxWidth = 30 / $steviloStolpcevTabele / 100;	//povozil $textboxWidth tako, da zadeva je v skladu s prejsnjimi izvozi
			//echo "sirina 2: ".$textboxWidth."</br>";
					
			//ureditev parametrov za tabelo - konec######################		
			//if(0){
			//if($steviloOken == 1 && $polozajBesedila == 0){	//ce imamo samo en kos besedila brez pripisanega texta
				//ureditev polja s podatki trenutnega uporabnika ######################################################
				$rowVrednost = mysqli_fetch_array($sqlVrednosti);					
				
				if($spremenljivke['tip'] == 21){	//ce je ta novo besedilo, ki je v uporabi
					$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' ";
					if($loop_id){ //ce je prisoten se loop_id, je tega potrebno dodati sql stavku
						$sqlUserAnswerString .= " AND loop_id=$loop_id";
					}
					//echo $sqlUserAnswerString."</br>";
				}elseif($spremenljivke['tip'] == 4){	//ce je ta staro besedilo, ki ni vec v uporabi vsaj 9 let (2020)
					//$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ");
					$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ";
					if($loop_id){ //ce je prisoten se loop_id, je tega potrebno dodati sql stavku
						$sqlUserAnswerString .= " AND loop_id=$loop_id";
					}
				}
				$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				//echo "userAnswer: ".$userAnswer['text']."</br>";
				//ureditev polja s podatki trenutnega uporabnika - konec ##############################################
						
				if($userAnswer){	//ce je kaj v bazi oz. se izpisujejo odgovori respondenta
					$podatekVBazi = 1;
					//print_r($spremenljivke);
					
					// imamo upload vprašanje					
					if ($spremenljivke['upload']){
						//echo "Je upload za ".$spremenljivke['id']."</br>";
						# imena datotek
						if($userAnswer['text'] != ''){							
							$textUpload = (''.$site_url.'main/survey/download.php?anketa='.$anketa.'&code='.$userAnswer['text'].' ');
						}else{
							$tex .= '';
						}
					}
					// imamo signature vprašanje
					elseif($spremenljivke['signature'] == 1){						
						$imageName = $usr_id.'_'.$spremenljivke['id'].'_'.$anketa.'.png';	//ime slike						
						$image = PIC_SIZE."{".$this->path2HeatmapImages."".$imageName."}";	//priprave slike predefinirane dimenzije			
						$tex .= $image."".$texNewLine; //izris slike
						
						//$tex .= $lang['srv_signature_name'].' '.$userAnswer['text'].$texNewLine;
						$textSignature = $lang['srv_signature_name'].' '.$userAnswer['text'];
					}
					else{
						//$tex .= $userAnswer['text'];
						//$textboxHeight = 0;
					}

					if($export_data_type==2){	//ce je izpis skrcen in je prazen vprasalnik
						$okvir = 0;
						$izpisanoBesediloPoStarem = 0;
					}elseif($export_data_type==1){	//ce je izpis razsirjen
						$okvir = 1;	//rabimo okvir
						//$okvir = 0;	//ne rabimo okvir
					}
				}else{	//ce je prazen vprasalnik
					$okvir = 1;	//rabimo okvir
				}
				
			//}else{
				
				/* echo "postavitev besedila: ".$postavitev." ".$spremenljivke['id']."</br>";
				echo "položaj besedila: ".$polozajBesedila."</br>"; */
				if($okvir == 1){
					
					

					if(($postavitev!=0)){	//ce ni vodoravno ob vprasanju, uporabi za izpis tabelo	
						if($steviloOken>1){	//ce je stevilo oken vec kot 1, zacni novo tabelo
							//Ureditev dodajanja manjsega razmika med besedilom vprasanja in tabelo
							$tex .= $this->texGapBeforeTable;
							//Ureditev dodajanja manjsega razmika med besedilom vprasanja in tabelo - konec
							#ZACETEK TABELE
							//zacetek tabele
							$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
						}				
						
						//echo "ni vodoravno ob vprašanju </br>";
					//}elseif($postavitev==0&&($polozajBesedila!=0&&$polozajBesedila!=1)){	//ce je vodoravno ob vprasanju in ni dodatnega besedila ali ni besedila pred okvirjem, uporabi za izpis tabelo
					}elseif($postavitev==0&&$polozajBesedila==3){	//
						#ZACETEK TABELE
						//zacetek tabele
						$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
						//echo "je vodoravno ob vprašanju zgoraj</br>";
					}elseif($postavitev==0&&$polozajBesedila==2){
						//zacetek tabele
						$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
						//echo "je vodoravno ob vprašanju spodaj</br>";
					}elseif($postavitev==0&&$steviloOken>1){
						//zacetek tabele
						$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
						//echo "stevilo oken večje in ob vprašanju </br>";
					}
				}
				//echo "tex koda: ".$tex."</br>";
				// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
				$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
				
				//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
					# po potrebi se prevede besedilo, ki se pojavi pred textbox-om 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					
					//ureditev polja s podatki trenutnega uporabnika ######################################################
					if($spremenljivke['tip'] == 21){	//ce je ta novo besedilo, ki je v uporabi
						$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' ";
						if($loop_id){ //ce je prisoten se loop_id, je tega potrebno dodati sql stavku
							$sqlUserAnswerString .= " AND loop_id=$loop_id";
						}
						//echo "userAnswer: ".$userAnswer['text']."</br>";
					}elseif($spremenljivke['tip'] == 4){	//ce je ta staro besedilo, ki ni vec v uporabi vsaj 9 let (2020)						
						$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ";
						if($loop_id){ //ce je prisoten se loop_id, je tega potrebno dodati sql stavku
							$sqlUserAnswerString .= " AND loop_id=$loop_id";
						}
					}
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					//ureditev polja s podatki trenutnega uporabnika - konec ##############################################

					//priprava besedila za izpis
					$stringNaslov = $rowVrednost['naslov'];
					$stringNaslov = Common::getInstance()->dataPiping($stringNaslov, $usr_id, $loop_id);
					//priprava besedila za izpis - konec
					
					//ce ni other ali missing
					if( (int)$rowVrednost['other'] == 0 ){
						
						
						//ureditev besedila odgovora respondenta v doloceno barvo
						if($export_format=='pdf'){
							$besedilo = '\\textcolor{crta}{';
						}else{
							$besedilo = '';
						}
						if($spremenljivke['signature'] == 1){
							$besedilo .= $textSignature;
						//}elseif($spremenljivke['upload'] == 1){
						}elseif($spremenljivke['upload']){							
							//$besedilo .= $textUpload;
							$besedilo .= $this->encodeText($textUpload);
						}else{
							//$besedilo .= $userAnswer['text'];
							$besedilo .= $this->encodeText($userAnswer['text']);
						}
						
						if($export_format=='pdf'){
							$besedilo .= '}';
						}
						//ureditev besedila odgovora respondenta v doloceno barvo - konec
						
						//priprava latex kode za text box dolocene sirine in visine glede na export format z ustreznim besedilom odgovora
						
						if($okvir == 1){	//ce rabimo okvir, izpisi							
							if($podatekVBazi && $export_format == 'pdf'){	//ce je podatek v bazi in je pdf oz. se izpisuje odgovore respondenta								
								$dataTextBox = $besedilo;	//izpis besedila brez okvirja								
							}else{
								//echo "Pos: ".$postavitev."</br>";
								if(($postavitev!=0)){	//ce ni vodoravno ob vprasanju
									$tex .= ' \\\\ ';	//skoci v novo vrstico
								}
								$dataTextBox = $this->LatexTextBox($export_format, $textboxHeight, $textboxWidth, $besedilo, $textboxAllignment, 0); //izpisi okvir
							}
						}else{
							$dataTextBox = $besedilo;	//izpis besedila brez okvirja
						}
						
						array_push($textBoxes, $dataTextBox);	//filanje polja s praznimi text box-i					
						array_push($besedila, $this->encodeText($stringNaslov));	//filanje polja z besedili

						if($okvir == 0){
							if($spremenljivke['tip'] == 21){	//ce je ta novo besedilo, ki je v uporabi
								if($indeksZaWhile!=1){								
									$tex .= ' \\\\ ';	//skoci v novo vrstico
								}
								
								//izpis besedila
								if($polozajBesedila!=0){	//ce je prisotno dodatno besedilo ob okvirju									
									$tex .= $this->encodeText($stringNaslov)." ";
								}
								$tex .= ' '.$dataTextBox;
							}elseif($spremenljivke['tip'] == 4){	//ce je ta staro besedilo, ki ni vec v uporabi vsaj 9 let (2020)
								if($izpisanoBesediloPoStarem == 0){
									$tex .= $dataTextBox;
									$izpisanoBesediloPoStarem = 1;
								}								
							}
							
						}elseif($okvir == 1){
							//if($polozajBesedila==1){	//ce je polozaj besedila SPREDAJ
							if($polozajBesedila==1 && $steviloOken==1){	//ce je polozaj besedila SPREDAJ in je samo 1 okvir za vnos besedila
								if($indeksZaWhile!=1){									
									$tex .= '  ';	//skoci v nov stolpec
								}
								
								//izpis besedila
								$tex .= $this->encodeText($stringNaslov)." ";

								//izpis text box-a dolocene sirine	in visine z besedilom odgovora								
								$tex .= '  '.$dataTextBox;
								
							}							
						}
					}
					else {	//drugace, ce imamo missinge ali podobne, jih zabelezi v polju
						// imamo polje drugo - ne vem, zavrnil...
						/* $array_others[$rowVrednost['id']] = array(
							'naslov'=>$rowVrednost['naslov'],
							'vrstni_red'=>$rowVrednost['vrstni_red'],
							'value'=>$text[$rowVrednost['vrstni_red']],
						); */
						$array_others[$rowVrednost['id']] = array(
							'naslov'=>$this->encodeText($stringNaslov),
							'vrstni_red'=>$rowVrednost['vrstni_red'],
							'value'=>$text[$rowVrednost['vrstni_red']],
						);
						
					}			
					$oznakaOdgovora++;
					$indeksZaWhile++;			
				}
				//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - konec
				
				if($okvir == 1){
					//ureditev polozaja besedila poleg text box-a ZGORAJ
					if($polozajBesedila!=0 && $polozajBesedila==3){	//ce je prisotno besedilo in ni pod text box-om
						$tex .= $this->izrisVrsticePoStolpcih($steviloStolpcevTabele, $besedila);
						$tex .= $texNewLine;	//dodaj po izpisu besedil še skok v novo vrstico
					}
					//ureditev polozaja besedila poleg text box-a ZGORAJ - konec		
					
					if($polozajBesedila!=1){	//ce ni polozaj besedila SPREDAJ
						//izpis praznih text box-ov dolocene sirine	in visine
						$tex .= $this->izrisVrsticePoStolpcih($steviloStolpcevTabele, $textBoxes);
						//izpis praznih text box-ov dolocene sirine	in visine - konec
					}

					//ureditev polozaja besedila poleg text box-a SPODAJ
					if($polozajBesedila!=0 && $polozajBesedila==2){	//ce je prisotno besedilo in ni pod text box-om
						$tex .= $texNewLine;	//dodaj po izpisu besedil še skok v novo vrstico
						$tex .= $this->izrisVrsticePoStolpcih($steviloStolpcevTabele, $besedila);
					} 
					//ureditev polozaja besedila poleg text box-a SPODAJ
				}
				

				if($okvir == 1){
					if($postavitev!=0){
						if($steviloOken>1){	//ce je stevilo oken vec kot 1, zakljuci tabelo
							//zakljucek tabele
							$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
							#KONEC TABELE
						}
					}elseif($postavitev==0&&$polozajBesedila==3){
						//zakljucek tabele
						$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
						#KONEC TABELE
					}elseif($postavitev==0&&$polozajBesedila==2){
						//zakljucek tabele
						$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
						#KONEC TABELE
					}elseif($postavitev==0&&$steviloOken>1){
						//zakljucek tabele
						$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
						#KONEC TABELE
					}
				}
			//}
			
			//$tex .= ' \vspace{0.3cm} ';
			
			// Izris polj drugo - ne vem...
			if (count($array_others) > 0) {	
				$tex .= $texNewLine;
				foreach ($array_others AS $oKey => $other) {
					$tex .= $symbol.' '.$other['naslov'].' ';
					if($postavitev!=0){
						$tex .= $texNewLine;
					}				
				}
			}		
			

			if(($postavitev==0)){ //ce je vodoravno ob vprasanju
				$tex .= ' \par } ';	//zakljuci odstavek
			}

			//echo "izpisani podatek: ".$podatekVBazi."</br>";			
			//if($podatekVBazi==1&&$export_data_type==2){	//ce je podatek v bazi in je izpis skrcen
			if($podatekVBazi==1){	//ce je podatek v bazi
				if($export_data_type==2 || $steviloOken == 1){	//ce je izpis skrcen ali je stevilo oken 1
					$tex .= " \ ";	//da ni tezave z "there is no line here to end"
					$tex .= $texNewLine;
					$tex .= $texNewLine;
				}
			}else{				
				$tex .= $this->texBigSkip;
				$tex .= $this->texBigSkip." \ ";
				$tex .= $texNewLine." ";
			}			
			
			
			if($export_format == 'pdf'){	//ce je pdf
				//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
				//echo "tex koda: ".$tex." in indeks $indeksZaWhile</br>";
			}else{	//ce je rtf

			} 
		
		}
		//echo "tex koda: ".$tex." in indeks $indeksZaWhile</br>";
		return $tex;	
	}
	
	#funkcija, ki skrbi za izris vrstice tabele po stolpcih
	function izrisVrsticePoStolpcih($steviloStolpcevTabele=null, $array=[]){
		$tex = '';
		for($i=0;$i<$steviloStolpcevTabele;$i++){
			if($i!=0){	//ce ni prvi stolpec
				$tex .= ' & ';	//dodaj oznako za prehod v nov stolpec
				//$tex .= ' \\\\ ';	//dodaj oznako za prehod v novo vrstico
			}			
			$tex .= $array[$i];
		}
		return $tex;
	}
	
	
	#funkcija, ki skrbi za izris - konec
	
}