<?php
/***************************************
 * Description: Priprava Latex kode za Razvrscanje
 *
 * Vprašanje je prisotno:
 * tip 17
 *
 * Autor: Patrik Pucer
 * Datum: 08-09/2017
 *****************************************/


define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip
define("RADIO_BTN_SIZE", 0.13);

class RazvrscanjeLatex extends LatexSurveyElement
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

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new RazvrscanjeLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype='', $preveriSpremenljivko=null, $export_data_type=null, $loop_id=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//preveri, ce je kaj v bazi
		//$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id);
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;
			
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
			//$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);
			
			$tex = '';
			
			//nastavitve iz baze ##########################
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);		
			$tipRazvrscanja = $spremenljivke['design'];	//0-Prestavljanje, 1-Ostevilcevanje, 2-Premikanje
			$steviloDesnihOkvirjev = $spremenljivke['ranking_k']; //nastavitev Moznosti: 0-Vsi, 1....
			if($steviloDesnihOkvirjev==0){	//ce je 0, je stevilo desnih okvirjev enako stevilo vnesenih odgovorov na levi strani
				$steviloDesnihOkvirjev=$numRowsSql;
			}
			//nastavitve iz baze - konec ####################
					
			$navpicniOdgovori = array();
			$navpicniOdgovori = [];
			
			$odgovoriRespondenta = array();
			$odgovoriRespondenta = [];
			
			$texNewLineAfterTable = $texNewLine." ".$texNewLine." ".$texNewLine;
								
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
			while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
				$jeOdgovor = 0;	//belezi, ali je trenutna vrednost odgovora, odgovor respondenta ali ne
				$stringTitleRow = $rowVrednost['naslov']; //odgovori na levi strani
				
				if($userDataPresent){	//ce so prisotni podatki respondenta
					//preverjanje podatkov trenutnega uporabnika ######################################################
					//$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
					$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' ");
					
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);					
					
					if($userAnswer){	//ce je kaj v bazi
						if($tipRazvrscanja==1){	//ce je Ostevilcevanje
							$odgovorRespondenta = $userAnswer['vrstni_red'];
							array_push($odgovoriRespondenta, $odgovorRespondenta);	//filanje polja z odgovori respondenta (stevilke)
						}
						$jeOdgovor = 1;
					}					
					//preverjanje podatkov trenutnega uporabnika - konec ##############################################
				}
				
				if($jeOdgovor==0||$tipRazvrscanja==1){	//ce ni odgovor respondenta, bo naslov na levi strani; ali je Ostevilcevanje
					array_push($navpicniOdgovori, $this->encodeText($stringTitleRow, $rowVrednost['id']) );	//filanje polja z navpicnimi odgovori (po vrsticah)
				}
			}
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - konec
			
			if($userDataPresent&&$tipRazvrscanja!=1){	//ce so prisotni podatki respondenta in ni Ostevilcevanje
				#ureditev polja s podatki trenutnega uporabnika ######################################################
				//$sqlOdgovoriRespondentaString = "SELECT v.naslov, v.id from srv_vrednost v, srv_data_rating r WHERE r.spr_id=v.spr_id AND r.usr_id=".$usr_id." AND r.vre_id=v.id AND r.spr_id=".$spremenljivke['id']." ORDER BY r.vrstni_red";
				$sqlOdgovoriRespondentaString = "SELECT v.naslov, v.id from srv_vrednost v, srv_data_rating r WHERE r.spr_id=v.spr_id AND r.usr_id=".$usr_id." AND r.vre_id=v.id AND r.spr_id=".$spremenljivke['id']." AND hidden='0' ORDER BY r.vrstni_red";
				//echo $sqlOdgovoriRespondentaString."</br>";
				$sqlOdgovoriRespondenta = sisplet_query($sqlOdgovoriRespondentaString);
				//pregled vseh odgovorov respondenta razvrsceni kot morajo biti
				while ($rowOdgovoriRespondenta = mysqli_fetch_assoc($sqlOdgovoriRespondenta)){
					$odgovorRespondenta = $this->encodeText($rowOdgovoriRespondenta['naslov'], $rowOdgovoriRespondenta['id']);					
					array_push($odgovoriRespondenta, $odgovorRespondenta);	//filanje polja z odgovori respondenta
				}
				//pregled vseh odgovorov respondenta razvrsceni kot morajo biti
				#ureditev polja s podatki trenutnega uporabnika - konec ##############################################
			}
			
			//izris tabel dolocenega razvrscanja
			if($export_data_type==2){	//ce je kratek izpis izvoza
				$tex .= $this->IzrisRazvrscanjaKratko($spremenljivke, $steviloDesnihOkvirjev, $numRowsSql, $navpicniOdgovori, $texNewLine, $texNewLineAfterTable, $export_format, 0, $tipRazvrscanja, $odgovoriRespondenta, $export_subtype);
			}elseif($export_data_type==0||$export_data_type==1){ //ce je navaden ali dolg izpis izvoza
				if($tipRazvrscanja==0||$tipRazvrscanja==2){	//ce je Prestavljanje ali Premikanje
					$tex .= $this->IzrisRazvrscanjaTabele($spremenljivke, $steviloDesnihOkvirjev, $numRowsSql, $navpicniOdgovori, $texNewLine, $texNewLineAfterTable, $export_format, 0, $tipRazvrscanja, $odgovoriRespondenta, $export_subtype);
				}elseif($tipRazvrscanja==1){	//ce je Ostevilcevanje
					$tex .= $this->IzrisRazvrscanja($spremenljivke, $numRowsSql, $navpicniOdgovori, $odgovoriRespondenta, $texNewLine, $export_format, 0);
				}
			}
			//izris tabel dolocenega razvrscanja - konec
						
			
			if($export_data_type!=2){	//ce ni skrcen izpis izvoza
				if($tipRazvrscanja==1){ //ce je Ostevilcevanje
					$tex .= $this->texNewLine;
					$tex .= $this->texNewLine;
				}else{
					$tex .= $this->texBigSkip;
				}
			}		
			
			return $tex;
		}
	}


	#funkcija, ki skrbi za izris ustreznih tabel za razvrscanje (postavitev: Prestavljanje in Premikanje) ################################
	function IzrisRazvrscanjaTabele($spremenljivke=null, $steviloDesnihOkvirjev=null, $steviloVrstic=null, $navpicniOdgovori=null, $texNewLine='', $texNewLineAfterTable=null, $typeOfDocument=null, $fillablePdf=null, $tipRazvrscanja=null, $odgovoriRespondenta=null, $export_subtype=null){
		global $lang;
		
		//izpis kode tabela
		$tabela = '';
		
		if($tipRazvrscanja==0){	//ce je postavitev Prestavljanje
			#pred zacetkom tabel s kategorijami #######################################################################
			$tabela .= '\setlength{\parindent}{0.1\textwidth} ';			
			//prva vrstica pred tabelo z odgovori
			if($typeOfDocument == 'pdf'){	//ce je pdf				
				$tabela .= '\begin{tabularx}{\textwidth}{l c l} ';	//izris s tabelo tabularx
				$tabela .= $lang['srv_ranking_available_categories'].': & \hspace{0.1\textwidth} & '.$lang['srv_ranking_ranked_categories1'].': '.$texNewLine;
				$tabela .= '\rule{0.4\textwidth}{0.7 pt} &  & \rule{0.4\textwidth}{0.4 pt} \end{tabularx} ';				
			}else{	//ce je rtf				
				$tabela .= '\begin{tabular}{l c l} ';	//izris s tabelo				
				$tabela .= $lang['srv_ranking_available_categories'].': & & '.$lang['srv_ranking_ranked_categories1'].': '.$texNewLine;				
				$tabela .= '\rule{0.4\textwidth}{0.7 pt} &  & \rule{0.4\textwidth}{0.4 pt} \end{tabular} ';				
			}
			//prva vrstica pred tabelo z odgovori - konec
			#pred zacetkom tabel s kategorijami  - konec ###############################################################
			
			$parameterTabularL = 'ccc';	//parameter za celotno tabelo z levimi in desnimi okvirji odgovorov za Prestavljanje
		}
		
		
		$tableCentering = ($typeOfDocument == 'pdf' ? ' \centering ' : '');
		
		if($tipRazvrscanja==2){	//ce je postavitev Premikanje
			$parameterTabularL = 'c';	//parameter za celotno tabelo z levimi in desnimi okvirji odgovorov za Premikanje
			if($typeOfDocument == 'pdf'){
				$tabela .= '\begin{center}';	//naj bo tabela na sredini lista, zacetek obmocja za center
			}
		}
		
		#################################################
		//zacetek tabele
		$tabela .= $this->StartLatexTable($typeOfDocument, $parameterTabularL, 'tabularx', 'tabular', 1, 0.2);
				
		//argumenti za leve okvirje		
		$textboxWidthL = 0.25;		
		$textboxAllignmentL = 'c';
		$indeksZaStevilaL=1;
		$indeksZaStevilaD=1;
		
		//if($tipRazvrscanja==0||($tipRazvrscanja==2&&count($odgovoriRespondenta)==0)){ //ce je Prestavljanje ali Premikanje in ni podatkov respondenta
		if(($tipRazvrscanja==2&&count($odgovoriRespondenta)==0)){ //Premikanje in ni podatkov respondenta
			$steviloOdgovorov=count($navpicniOdgovori);
			$textboxHeightL = 0;
		}elseif($tipRazvrscanja==2&&count($odgovoriRespondenta)!=0){	//ce je postavitev Premikanje in imamo odgovore respondenta
			$steviloOdgovorov=count($odgovoriRespondenta);
			$textboxHeightL = 0;	//ker mora biti prilagojena visina tekstu damo na 0
		}elseif($tipRazvrscanja==0){ //ce je Prestavljanje
			//$steviloOdgovorov=count($navpicniOdgovori)+$steviloDesnihOkvirjev;
			$steviloOdgovorov=$steviloDesnihOkvirjev;
			$textboxHeightL = 0;
		}
		
		//echo "textboxHeightL: ".$textboxHeightL."</br>";
/* 		echo "odgovori respondenta: ".count($odgovoriRespondenta)."</br>";
		echo "navpični odgovori: ".count($navpicniOdgovori)."</br>";		
		echo "stevilo Odgovorov: ".$steviloOdgovorov."</br>";
		echo "stevilo desnih okvirjev: ".$steviloDesnihOkvirjev."</br>"; */

		//izris notranjosti tabele
		for ($i = 1; $i <= $steviloOdgovorov; $i++){

			$textL = $tableCentering.' '.$navpicniOdgovori[$i-1];	//odgovor znotraj okvirja
			
			if($tipRazvrscanja==2){	//ce je postavitev Premikanje
				
				$tabela .= $indeksZaStevilaL.'. ';	//pred okvirjem s kategorijo odgovora dodaj stevilko s piko

			}elseif($tipRazvrscanja==0&&$typeOfDocument == 'rtf'){
				//$tabela .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
			}

			//izpis latex kode za okvir z odgovorom
			if($tipRazvrscanja==0||($tipRazvrscanja==2&&count($odgovoriRespondenta)==0)){ //ce je Prestavljanje ali Premikanje in ni podatkov respondenta
				if($navpicniOdgovori[$i-1]!=''){	//ce so prisotni odgovori
					$textVOkvirju = $textL;
				}
			}elseif($tipRazvrscanja==2&&count($odgovoriRespondenta)!=0){	//ce je postavitev Premikanje in imamo odgovore respondenta				
				$textVOkvirju = $odgovoriRespondenta[$i-1];
			}

			//echo "text V Okvirju: ".$textVOkvirju."</br>";
			
			//izpis latex kode za okvir z odgovorom
			if($tipRazvrscanja==0&&$navpicniOdgovori[$i-1]!=''){
				if($typeOfDocument == 'pdf'){	//ce je pdf
					$tabela .= $this->LatexTextBox($typeOfDocument, $textboxHeightL, $textboxWidthL, $textVOkvirju, $textboxAllignmentL, 0);
				}else{
					$tabela .= $textVOkvirju;
				}
			}elseif($tipRazvrscanja==2||$tipRazvrscanja==1){
				if($typeOfDocument == 'pdf'){	//ce je pdf
					$tabela .= $this->LatexTextBox($typeOfDocument, $textboxHeightL, $textboxWidthL, $textVOkvirju, $textboxAllignmentL, 0);
				}else{
					$tabela .= $textVOkvirju;
				}
			} 
/* 			if(($tipRazvrscanja==0&&$navpicniOdgovori[$i-1]!='')||($tipRazvrscanja==2||$tipRazvrscanja==1)){
				if($typeOfDocument == 'pdf'){	//ce je pdf
					$tabela .= $this->LatexTextBox($typeOfDocument, $textboxHeightL, $textboxWidthL, $textVOkvirju, $textboxAllignmentL, 0);
				}else{
					$tabela .= $textVOkvirju;
				}
			} */
			
			if($typeOfDocument == 'pdf'){
				$tabela .= ' \bigskip ';							
			}

			if($tipRazvrscanja==2){ //ce je Premikanje				
				$tabela .= $texNewLine;				
			}

			################
			if($tipRazvrscanja==0){	//ce je postavitev Prestavljanje
				//prazen prostor med levim delom in desnim delom 
				$tabela .= '& \hspace{0.2\textwidth} &';	
				
				//desni del vprasanja
				$textboxWidthDE = 0.25;	//sirina okvirja z vsebino in empty
				$textboxAllignmentDE = 'c';	//allignment desnega okvirja, ki je empty
				if($indeksZaStevilaD <= $steviloDesnihOkvirjev){ //ce se ni preseglo zeleno stevilo desnih okvirjev					
					$tabela .= $indeksZaStevilaD.'. ';
					$odgovorZaIzpis = $odgovoriRespondenta[$i-1];
					if($typeOfDocument == 'pdf'){
						//echo "odgovori respondenta na desni: ".$odgovoriRespondenta[$i-1]."</br>";
						if($odgovorZaIzpis){	//ce je odgovor respondenta
							$textboxHeight = 0; //ker mora biti prilagojena visina tekstu damo na 0
						}else{
							$textboxHeight = '0.3cm';
						}
						//izpis latex kode za okvir brez besedila oz. z odgovorom respondenta						
						$tabela .= $this->LatexTextBox($typeOfDocument, $textboxHeight, $textboxWidthDE, $odgovorZaIzpis, $textboxAllignmentDE, 0);
						$tabela .= $texNewLine;					
					}elseif($typeOfDocument == 'rtf'){											
						//izpis latex kode za okvir brez besedila oz. z odgovorom respondenta						
						$tabela .= $this->LatexTextBox($typeOfDocument, 0, $textboxWidthDE, $odgovorZaIzpis, $textboxAllignmentDE, 0);
						$tabela .= $texNewLine;
					}
		
					$indeksZaStevilaD++;
				}else{	//ce se je preseglo stevilo zelenih okvirjev na desni strani, izpisi prazno celico
					$tabela .= ' '.$texNewLine;				
				}				
			}
			################
			$indeksZaStevilaL++;

		}
				
		//$tabela .= ' \bigskip ';
		
		
		//if(count($navpicniOdgovori)==0){	//ce ni odgovorov na desni strani, uredi prazen neviden okvir
		if(count($navpicniOdgovori)==0 && $typeOfDocument == 'pdf'){	//ce ni odgovorov na desni strani, uredi prazen neviden okvir
			$tabela .= $this->LatexTextBox($typeOfDocument, $textboxHeightL, $textboxWidthL, '', $textboxAllignmentL, 1);
		}
			
		//zakljucek tabele
		$tabela .= $this->EndLatexTable($typeOfDocument, 'tabularx', 'tabular');
		#################################################
					
		if($tipRazvrscanja==2){	//ce je postavitev Premikanje
			if($typeOfDocument == 'pdf'){
				$tabela .= '\end{center}';	//naj bo tabela na sredini lista, konec obmocja za center
			}			
		}
		//izpis kode tabela - konec
		
		return $tabela;
	}
	#funkcija, ki skrbi za izris ustreznih tabel za razvrscanje (postavitev: Prestavljanje in Premikanje) - konec ################################


	
	#funkcija, ki skrbi za izris ustreznih tabel za razvrscanje (postavitev: Prestavljanje in Premikanje) ################################

	#funkcija, ki skrbi za izris razvrscanja (postavitev: Ostevilcevanje) ################################
	function IzrisRazvrscanja($spremenljivke=null, $steviloVrstic=null, $navpicniOdgovori=null, $odgovoriRespondenta=null, $texNewLine='', $typeOfDocument=null, $fillablePdf=null){
		$tex = '';
		$textboxWidth = 0.1;
		$textboxHeight = '0.2cm';
		$textboxAllignment = 'c';	//dummy spremenljivka

		if($typeOfDocument == 'rtf'){	//ce je rtf, zacetek tabele, kjer sta dva stolpca (prazen okvir + okvir z odgovorom)
			//$tex .= '\begin{tabular}{l l} ';	//izris z enostolpicno tabelo
			$tex .= '\begin{tabular}{c l} ';	//izris z enostolpicno tabelo
		}		
		
		for ($i = 1; $i <= $steviloVrstic; $i++){
			$tex .= ' \noindent ';	//da ni premika besedila v desno			
			
			//izpis latex kode za prazen okvir oz. okvirjem z ustreznim stevilskim odgovorom
			//$tex .= $this->LatexTextBox($typeOfDocument, $textboxHeight, $textboxWidth, $odgovoriRespondenta[$i-1], $textboxAllignment, 0);
			$tex .= $this->LatexTextBox($typeOfDocument, $textboxHeight, $textboxWidth, '\\textcolor{crta}{'.$odgovoriRespondenta[$i-1].'}', $textboxAllignment, 0);
			
			if($typeOfDocument == 'rtf'){	//ce je rtf
				$tex .= ' & ';	//meja med stolpcema tabele 1. prazen okvir (okvir s stevilskim odgovorom), 2. navpicni odgovor
			}
			
			//odgovor ob praznem okvirju
			$tex .= ' '.$navpicniOdgovori[$i-1];
			
			//v novo vrstico
			$tex .= $texNewLine;
		}
		
		if($typeOfDocument == 'rtf'){		// ce je rtf, zakljuci tabelo s stolpcema
			$tex .= ' \end{tabular} ';
		}
		return $tex;		
	}	
	#funkcija, ki skrbi za izris razvrscanja (postavitev: Ostevilcevanje) - konec ################################
	
	#funkcija, ki skrbi za izris razvrscanje za kratek izpis izvoza ################################
	function IzrisRazvrscanjaKratko($spremenljivke=null, $steviloDesnihOkvirjev=null, $steviloVrstic=null, $navpicniOdgovori=null, $texNewLine='', $texNewLineAfterTable=null, $typeOfDocument=null, $fillablePdf=null, $tipRazvrscanja=null, $odgovoriRespondenta=null, $export_subtype=null){
		global $lang;
		
		$indeksZaStevila=1;
		$steviloOdgovorov=count($navpicniOdgovori);
		$steviloOdgovorov=count($odgovoriRespondenta);
		
		$tex = '';
	
		//izpis stevil in odgovorov
		for ($i = 1; $i <= $steviloOdgovorov; $i++){
			if($tipRazvrscanja==0||$tipRazvrscanja==2){ //ce je Prestavljanje ali Premikanje
				$tex .= $indeksZaStevila.'. ';	//stevilka pred odgovorom
				//$tex .= $odgovoriRespondenta[$i-1];	//odgovor
				$tex .= '\\textcolor{crta}{'.$odgovoriRespondenta[$i-1].'}';	//odgovor
			}elseif($tipRazvrscanja==1){	//ce je Ostevilcevanje
				$tex .= $navpicniOdgovori[$i-1].': ';
				//$tex .= $odgovoriRespondenta[$i-1];	//odgovor				
				$tex .= '\\textcolor{crta}{'.$odgovoriRespondenta[$i-1].'}';	//odgovor				
			}			
			$tex .= '; ';
			$indeksZaStevila++;
		}
		
		$tex .= ' \\\\ ';
		$tex .= ' \\\\ ';
		
		return $tex;
	}
	#funkcija, ki skrbi za izris razvrscanje za kratek izpis izvoza - konec ################################
	
}