<?php
/***************************************
 * Description: Priprava Latex kode za Število
 *
 * Vprašanje je prisotno:
 * tip 7 z vsemi nacini vnosa (Stevilo, Drsnik) in postavitev (Vodoravno ob vprasanju in Vodoravno pod vprasanjem)
 *
 * Autor: Patrik Pucer
 * Datum: 08/2017
 *****************************************/


define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip
define("MAXSTEVILOSTOLPCEV", 21); 	//max Stevilo Stolpcev za prvo vrstico pod Drsnikom, zaradi tezav z izrisom, ce je teh vec kot toliko

class SteviloLatex extends LatexSurveyElement
{
	var $internalCellHeight;
	protected $texBigSkip = '\bigskip';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	
    public function __construct()
    {
        //parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new SteviloLatex();
    }
	
	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype=null, $preveriSpremenljivko=null, $export_data_type=null, $loop_id=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//preveri, ce je kaj v bazi
		//$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id);
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		//echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
		
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;
			
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			
			$tex = '';

			$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, 2, 10, 0, 0);	//poberi simbol checkbox za other in missing moznosti odgovora
			
			//nastavitve iz baze ##########################
			$steviloOken = $spremenljivke['size'];	//stevilo oken
			$postavitev = $spremenljivke['orientation'];	//0-vodoravno ob vprasanju, 1-vodoravno pod vprasanjem
			$polozajEnota = $spremenljivke['enota'];	//polozaj enote 0-Ne, 1-Na levi, 2-Na desni
			$nacinVnosa = $spremenljivke['ranking_k']; //nacin vnosa 0-Stevilo, 1-Drsnik
			
					
			$textboxHeightOrig = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
			$textboxHeight = ($textboxHeightOrig*0.3).'cm';		
			
			$textboxWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			if($textboxWidth == -1){	//ce je vrednost -1, je default t.j. 30 oz. 0.30 sirine
				$textboxWidth = 0.30;
			}else{	//drugace, izracunaj sirino
				$textboxWidth = $textboxWidth/100;	//pretvorimo sirino v odstotke oz. decimalke
			}
			
			if($textboxWidth == 1){	//ce je sirina 100, jo zmanjsaj za 5%
				$textboxWidth = $textboxWidth*0.95;
			}
			
			$textboxWidth = (string)$textboxWidth; //pretvorimo stevilo (decimalke) v string
			//echo "sirina: ".$textboxWidth."</br>";
			//textboxWidth se rocno povozi pod "ureditev parametrov za tabelo"	- NE VEC, ker se hoce sirino okvirja tako kot je nastavljena v nastavitvah
			
			//nastavitve iz baze - konec ####################
			
			$array_others = array();	//polje za drugo, missing, ...		
			$besedilaEnote = array();	//polje, ki hrani besedila enot
			$besedilaEnote = [];	
			$textBoxes = array();	//polje, ki hrani latex za prazne text box-e
			$textBoxes = [];
			$textboxAllignment = 'c';	//poravnava textboxa s stevilom
			
			$oznakaOdgovora = 'a';
			$indeksZaWhile = 1;
			$oznakaVprasanja = $this->UrediOznakoVprasanja($spremenljivke['id']);	//uredi oznako vprasanja, ker ne sme biti stevilska	

			$okvirVNovoVrstico = 0;
		
	/* 		if($postavitev!=0){
				$tex .= $texNewLine;
			} */
			
			//ureditev polja s podatki trenutnega uporabnika ######################################################
			//$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
			$sqlUserAnswer = sisplet_query("SELECT text, text2 FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ");
			$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
			//echo "userAnswer: ".$userAnswer['text']."</br>";				
			//ureditev polja s podatki trenutnega uporabnika - konec ##############################################
			
			
			
			if(($nacinVnosa==0) || ($nacinVnosa==1&&$export_format=='rtf')){	//ce je nacin vnosa Stevilo ali je Drsnik in je izvoz v rtf
				
				if($polozajEnota!=0){ //ce je izpis z besedilom enote
					$tex .= $texNewLine;
				}
				
				//ureditev parametrov za tabelo#############################
				$parameterTabular = '';
				if($polozajEnota==0){	//ce ni besedila za enoto
					$steviloStolpcevTabele = $steviloOken;					
				}else{	//ce je besedilo enote na LEVI ali DESNI
					$steviloStolpcevTabele = $steviloOken*2;
				}		
				for($i = 0; $i < $steviloStolpcevTabele; $i++){
					//echo "i%2: ".($i%2)."</br>";
					if($polozajEnota==1 && $i%2==0){	//ce je polozaj besedila enote na LEVI in je stolpec za besedilo
						//$parameterTabular .= ($export_format == 'pdf' ? 'r' : 'l');	//desna poravnava stolpca ali leva, ce je rtf
						//$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize=0.3\hsize}r' : 'l');	//desna poravnava stolpca fiksne dimenzije ali leva, ce je rtf
						$parameterTabular .= ($export_format == 'pdf' ? '>{\raggedleft}p{0.2\textwidth}' : 'l');	//desna poravnava stolpca fiksne dimenzije ali leva, ce je rtf						
					}elseif($polozajEnota==2 && $i%2==1){	//ce je polozaj besedila enote na DESNI in je stolpec za besedilo
						$parameterTabular .= ($export_format == 'pdf' ? '>{\raggedright\arraybackslash}p{0.2\textwidth}' : 'l');	//leva poravnava stolpca fiksne dimenzije ali leva, ce je rtf
					}else{
						//$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca
/* 						if($textboxWidth<0.80){	//ce je urejeni okvir manjsi od 80, naj bo sirina ta ki je bila nastavljen
							$parameterTabular .= ($export_format == 'pdf' ? 'p{'.$textboxWidth.'\textwidth}' : 'l');	//leva poravnava stolpca
						}else{	//drugace, ce je okvri vecji od 80, naj bo sirina fiksna na 75
							$parameterTabular .= ($export_format == 'pdf' ? 'p{0.75\textwidth}' : 'l');	//leva poravnava stolpca
							$textboxWidth = 0.75;
						} */

						if($textboxWidth>=0.80){	//ce je urejeni okvir vecji od 80, naj oznaci, saj moramo prenesti okvirje v novo vrstico
							$okvirVNovoVrstico = 1;
						}
						$parameterTabular .= ($export_format == 'pdf' ? 'p{'.$textboxWidth.'\textwidth}' : 'l');	//stolpec z nastavljeno sirino okvirja, rtf: leva poravnava
						
					}			
				}
				//echo "parametri tabele: ".$parameterTabular."</br>";
				//ureditev parametrov za tabelo - konec######################
				
				if($userAnswer['text']){
					$okvir = 0;
				}elseif($userAnswer['text'] == ''){	//ce nimamo odgovora
					$okvir = 1;	//rabimo okvir
				}
				
				#ZACETEK TABELE		
				//zacetek tabele
				if($polozajEnota!=0){	//ce je prisotno besedilo za enoto
					//$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular',1, 1);
					$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabular', 'tabular',1, 1);
				}elseif($polozajEnota==0&&$steviloOken>1){	//ce ni prisotno besedilo za enoto in je vec oken
					//$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular',1, 1);
					$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabular', 'tabular',1, 1);
				}
				
				$izpisStevilk = 0; 	//belezi, ali se je stevilo ali stevila ze izpisalo
				
				//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
				while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
					# po potrebi se prevede besedilo, ki se pojavi pred textbox-om 			
					$naslov = $this->srv_language_vrednost($rowVrednost['id']);
					if ($naslov != '') {
						$rowVrednost['naslov'] = $naslov;
					}
					
					//ce ni other ali missing
					
					if( (int)$rowVrednost['other'] == 0 && $rowVrednost['naslov']){	//in se ni se izpisalo stevila

						$drugoStevilo = $userAnswer['text2'];	//belezi drugo mozno stevilo
						
						if($izpisStevilk == 0 && $okvir == 0){	//ce ni bilo se izpisano nobeno stevilo in ne rabimo tabele za izpis
							$txtTmp = [];
							if($export_format=='pdf'){
								if($polozajEnota==0){ //ce ni besedila za enoto, rabimo odstavek za pravilen izpis
									$txtTmp1 = " \par { ";
									$txtTmp1 .= ' \\textcolor{crta}{'.$userAnswer['text'].'}';
								}else{
									$txtTmp1 = ' \\textcolor{crta}{'.$userAnswer['text'].'}';
								}								
								if($polozajEnota==0){ //ce ni besedila za enoto
									//$txtTmp1 .= " \par } ";
									$txtTmp1 .= "  } ";
								}								
							}elseif($export_format == 'rtf'){
								$txtTmp1 = $userAnswer['text'];	//prvo stevilo
							}							
							array_push($txtTmp, $txtTmp1);	//filanje polja z besedili
							if($drugoStevilo){	//ce je se drugo stevilo
								if($export_format=='pdf'){
									if($polozajEnota==0){ //ce ni besedila za enoto, rabimo odstavek za pravilen izpis
										$txtTmp2 = " \par { ";
										$txtTmp2 .= ' \\textcolor{crta}{'.$drugoStevilo.'}';
									}else{
										$txtTmp2 = ' \\textcolor{crta}{'.$drugoStevilo.'}';
									}								
									if($polozajEnota==0){ //ce ni besedila za enoto
										//$txtTmp2 .= " \par } ";
										$txtTmp2 .= "  } ";
									}
								}elseif($export_format == 'rtf'){
									$txtTmp2 = $drugoStevilo;
								}
								array_push($txtTmp, $txtTmp2);	//filanje polja z besedili
							}
						}
						
						if($okvir == 1){	//ce rabimo okvir, izpisi
							//$dataTextBox = $this->LatexTextBox($export_format, $textboxHeight, $textboxWidth, $txtTmp, $textboxAllignment, 0);
							if($polozajEnota==0){ //ce ni besedila za enoto, rabimo odstavek za pravilen izpis
								if($steviloOken>1){
									$dataTextBox = " \par { ";
								}								
								$dataTextBox .= $this->LatexTextBox($export_format, $textboxHeight, $textboxWidth, $txtTmp, $textboxAllignment, 0);
							}else{
								$dataTextBox = $this->LatexTextBox($export_format, $textboxHeight, $textboxWidth, $txtTmp, $textboxAllignment, 0);
							}
							
							if($polozajEnota==0){ //ce ni besedila za enoto
								if($steviloOken>1){
									//$dataTextBox .= " \par } ";
									$dataTextBox .= " } ";
								}
							}
							array_push($textBoxes, $dataTextBox);
							
						}
						
						
						if($polozajEnota==0){	//ce polozaja besedila enote je na NE
							if($indeksZaWhile!=1){
								//if($okvir == 1){	//ce rabimo prazen okvir, izpisi
								if($okvir == 1 && $textboxWidth<0.49){	//ce rabimo prazen okvir, izpisi
									$tex .= ' & ';	//skoci v nov stolpec
								}else{
									$tex .= ' \\\\ ';	//pojdi v novo vrstico
								}								
							}

							if($okvir == 1){	//ce rabimo prazen okvir, izpisi
								//izpis praznega text box-a dolocene sirine	in visine			
								//$tex .= ' & '.$dataTextBox;
								$tex .= ' '.$dataTextBox;
							}else{	//ce je podatek ob levi enoti
								//$tex .= ' & '.$txtTmp[$indeksZaWhile-1];
								$tex .= ' '.$txtTmp[$indeksZaWhile-1];
							}

						}
						//if($polozajEnota==1){	//ce je polozaj besedila enote na LEVI
						elseif($polozajEnota==1){	//ce je polozaj besedila enote na LEVI
							if($indeksZaWhile!=1){								
								if($okvir == 1 && $textboxWidth<0.30){	//ce rabimo prazen okvir in je njegova sirina manjsa od 30, izpisi
									$tex .= ' & ';	//skoci v nov stolpec
								}else{
									$tex .= ' \\\\ ';	//pojdi v novo vrstico
								}								
							}

							//izpis besedila enote
							$stringEnota = $rowVrednost['naslov'];
							$stringEnota = Common::getInstance()->dataPiping($stringEnota, $usr_id, $loop_id);							
							$tex .= $this->encodeText($stringEnota);
							
							if($okvir == 1){	//ce rabimo prazen okvir, izpisi
								//izpis praznega text box-a dolocene sirine	in visine
								if($okvirVNovoVrstico){	//v novo vrstico, ce je predolg
									$tex .= ' \\\\ '.$dataTextBox;
								}else{
									$tex .= ' & '.$dataTextBox;
								}
							}else{	//ce je podatek ob levi enoti
								$tex .= ' & '.$txtTmp[$indeksZaWhile-1];								
							}

							//echo "tex koda: ".$tex."</br>";
							
						}elseif($polozajEnota==2){	//ce je polozaj besedila enote na DESNI
							//if($indeksZaWhile!=1&&$export_format=='rtf'){//ce je drugi okvir in je rtf
							if($indeksZaWhile!=1){//ce je drugi okvir
								//if($okvir == 1){	//ce rabimo prazen okvir, izpisi
								//if($okvir == 1&& $textboxWidth<0.49){	//ce rabimo prazen okvir in je njegova sirina manjsa od 49, izpisi
								if($okvir == 1&& $textboxWidth<=0.25){	//ce rabimo prazen okvir in je njegova sirina manjsa od 25, izpisi
									$tex .= ' & ';	//skoci v nov stolpec
								}else{
									$tex .= ' \\\\ ';	//pojdi v novo vrstico
								}
							}
							
							if($okvir == 1){	//ce rabimo prazen okvir, izpisi
								//izpis praznega text box-a dolocene sirine	in visine			
								$tex .= $dataTextBox;
							}else{
								if($indeksZaWhile!=1){
									$tex .= ' \\\\ ';	//pojdi v novo vrstico
								}
								$tex .= $txtTmp[$indeksZaWhile-1].' ';
							}
							
							//izpis besedila enote							
							if($okvirVNovoVrstico){	//v novo vrstico, ce je predolg
								$tex .= ' \\\\ ';	//pojdi v novo vrstico
							}else{
								$tex .= ' & ';	//v nov stolpec tabele
							}	

							$stringEnota = $rowVrednost['naslov'];
							$stringEnota = Common::getInstance()->dataPiping($stringEnota, $usr_id, $loop_id);							
							$tex .= ' '.$this->encodeText($stringEnota);
							
							if($indeksZaWhile==1&&$export_format=='pdf'){	//ce je prvi okvir in je pdf
								//$tex .= ' \hspace{0.5cm} ';	//dodaj še nekaj prostora, za prvim okvirjem, da bo dovolj prostora
							}

							//echo "tex koda za na desni: ".$tex."</br>";
						}
						
						if($drugoStevilo){
							$izpisStevilk=1;
						}
						
					}					
					elseif((int)$rowVrednost['other'] != 0) {	//drugace, ce imamo missinge ali podobne, jih zabelezi v polju
						// imamo polje drugo - ne vem, zavrnil...						
						$array_others[$rowVrednost['id']] = array(
							'naslov'=>$rowVrednost['naslov'],
							'vrstni_red'=>$rowVrednost['vrstni_red'],
							'value'=>$text[$rowVrednost['vrstni_red']],
						);
						
					}			
					$oznakaOdgovora++;
					$indeksZaWhile++;			
				}
				//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - konec
				

/* 				if($polozajEnota==0){	//ce polozaja besedila enote je na NE
					//$tex .= $texNewLine;
					if($okvir == 1){
						//izpis praznih text box-ov dolocene sirine	in visine						
						$tex .= $this->izrisVrsticePoStolpcih($steviloStolpcevTabele, $textBoxes);
						//izpis praznih text box-ov dolocene sirine	in visine - konec
					}else{						
						$tex .= $this->izrisVrsticePoStolpcih($steviloStolpcevTabele, $txtTmp);
					}
				} */
				
				if($polozajEnota!=0){	//ce polozaja besedila enote ni na NE
					//zakljucek tabele
					//$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
					$tex .= $this->EndLatexTable($export_format, 'tabular', 'tabular');
					#KONEC TABELE
				}elseif($polozajEnota==0&&$steviloOken>1){
					//zakljucek tabele
					//$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
					$tex .= $this->EndLatexTable($export_format, 'tabular', 'tabular');
					#KONEC TABELE
				}
				//echo "tex koda: ".$tex."</br>";

/* 				$tex .= $this->texNewLine;
				$tex .= $this->texNewLine;
				if($okvir == 1){	//ce je prazen vprasalnik, dodaj se dve prazni vrstici
					$tex .= $this->texNewLine;
					$tex .= $this->texNewLine;
				} */
				
			}elseif($nacinVnosa==1&&$export_format=='pdf'){	//ce je drsnik in je izvoz v pdf
				
				if($export_data_type==2){	//ce je kratek izpis izvoza
					if($export_subtype=='q_data'||$export_subtype=='q_data_all'){
						$tex .= $texNewLine; //gremo v novo vrstico, da je odgovor pod vprasanjem
						//$tex .= '\\textcolor{crta}{'.$userAnswer['text'].'}'.$texNewLine; //izpis odgovora
						$tex .= '\\textcolor{crta}{'.$userAnswer['text'].'}'; //izpis odgovora
					}	
				}		
				
				if($export_data_type==1||$export_data_type==0){	//ce je dolg ali navaden izpis izvoza, izrisi drsnik
					
					if($export_subtype=='q_data'||$export_subtype=='q_data_all'){
						$tex .= $lang['srv_number_answer'].': \\textcolor{crta}{'.$userAnswer['text'].'}'.$texNewLine;	//izpis odgovora
					}				
					
					//zakljucek odstavka, da bo lahko drsnik sredinsko poravnan
					$tex .= '\par';	//odstavek

					#nastavitve iz baze za drsnik ##################################################################
					$slider_handle = ($spremenljivkaParams->get('slider_handle') ? $spremenljivkaParams->get('slider_handle') : 0);	//0-je rocaj, 1-ni rocaja
					
					$slider_window_number = ($spremenljivkaParams->get('slider_window_number') ? $spremenljivkaParams->get('slider_window_number') : 0);
					
					$slider_nakazi_odgovore = ($spremenljivkaParams->get('slider_nakazi_odgovore') ? $spremenljivkaParams->get('slider_nakazi_odgovore') : 0); //za checkbox
					
					$slider_MinMaxNumLabelNew = ($spremenljivkaParams->get('slider_MinMaxNumLabelNew') ? $spremenljivkaParams->get('slider_MinMaxNumLabelNew') : 0); //Prikaži labele za Min in Max: 0-prikazi, 1-skrij
					
					$slider_MinMaxLabel = ($spremenljivkaParams->get('slider_MinMaxLabel') ? $spremenljivkaParams->get('slider_MinMaxLabel') : 0);	//ali sta prisotni labeli nad drsnikom oz. nad min in max vrednostjo
					$MinLabel = ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : $lang['srv_new_text']); //labela na minumumu
					$MaxLabel = ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : $lang['srv_new_text']); //labela na maksimumu
					
					$slider_VmesneNumLabel = ($spremenljivkaParams->get('slider_VmesneNumLabel') ? $spremenljivkaParams->get('slider_VmesneNumLabel') : 0);	//ali naj drsnika prikazuje stevilske labele med maksimumom in minimumom na spodnji strani drsnika
					$slider_VmesneDescrLabel = ($spremenljivkaParams->get('slider_VmesneDescrLabel') ? $spremenljivkaParams->get('slider_VmesneDescrLabel') : 0); //ali naj drsnika prikazuje opisne labele med maksimumom in minimumom na spodnji strani drsnika
					
					$slider_VmesneCrtice = ($spremenljivkaParams->get('slider_VmesneCrtice') ? $spremenljivkaParams->get('slider_VmesneCrtice') : 0); //ali imamo vmesen crtice na drsniku, za izris izvoza se bo to ignoriralo
					
					$slider_handle_step = ($spremenljivkaParams->get('slider_handle_step') ? $spremenljivkaParams->get('slider_handle_step') : 1); //korak drsnika
					
					$slider_MinLabel = ($spremenljivkaParams->get('slider_MinLabel') ? $spremenljivkaParams->get('slider_MinLabel') : "Minimum");
					$slider_MaxLabel = ($spremenljivkaParams->get('slider_MaxLabel') ? $spremenljivkaParams->get('slider_MaxLabel') : "Maximum");
					
					//vnesena minimum pa maksimum drsnika
					$slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
					$slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);


					$slider_NumofDescrLabels = ($spremenljivkaParams->get('slider_NumofDescrLabels') ? $spremenljivkaParams->get('slider_NumofDescrLabels') : 5);	//stevilo opisnih label
					$slider_DescriptiveLabel_defaults = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults') : 0);			
					$slider_DescriptiveLabel_defaults_naslov1 = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') : 0); //besedilo/string z naslovi opisnih vmesnih label

					//spremenljivke za labele podrocij
					$slider_labele_podrocij = ($spremenljivkaParams->get('slider_labele_podrocij') ? $spremenljivkaParams->get('slider_labele_podrocij') : 0); //za checkbox
					$slider_StevLabelPodrocij = ($spremenljivkaParams->get('slider_StevLabelPodrocij') ? $spremenljivkaParams->get('slider_StevLabelPodrocij') : 3);
					//$slider_table_td_width = 100 / $slider_StevLabelPodrocij;    //spremenljivka za razporeditev sirine sliderja po podrocjih
					//spremenljivke za labele podrocij - konec
					#nastavitve iz baze za drsnik - konec ##################################################################
					
					//pridobitev missing-ov za njihov izris  ###############################################################
					while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
						if( (int)$rowVrednost['other']!=0 ){	//ce imamo missinge ali podobne, jih zabelezi v polju
							// imamo polje drugo - ne vem, zavrnil...
							$array_others[$rowVrednost['id']] = array(
								'naslov'=>$rowVrednost['naslov'],
								'vrstni_red'=>$rowVrednost['vrstni_red'],
								'value'=>$text[$rowVrednost['vrstni_red']],
							);					
						}		
					}
					//pridobitev missing-ov za njihov izris - konec ########################################################
					
					//pridobitev naslovov opisnih vmesnih label za kasnejsi izris ##########################################
					if($slider_VmesneDescrLabel){	//ce je potrebno izrisati vmesne opisne labele pod drsnikom
						$descriptiveLabels = [];
						if($slider_DescriptiveLabel_defaults!=0){	//ce so prednalozene opisne labele
							$descriptiveLabels = explode(';',$slider_DescriptiveLabel_defaults_naslov1);
						}else{	//ce so custom opisne labele
							for($i=1; $i<=$slider_NumofDescrLabels; $i++){
								$slider_CustomDescriptiveLabelsTmp = ($spremenljivkaParams->get('slider_Labela_opisna_'.$i) ? $spremenljivkaParams->get('slider_Labela_opisna_'.$i) : '');
								$slider_CustomDescriptiveLabelsTmp = preg_replace("/\s|&nbsp;/",' ',$slider_CustomDescriptiveLabelsTmp);  //za odstranitev morebitnih presledkov, ki lahko delajo tezave pri polju za drsnik
								if($i == 1){
									$slider_CustomDescriptiveLabels = $slider_CustomDescriptiveLabelsTmp;
								}else{
									$slider_CustomDescriptiveLabels .= "; ".$slider_CustomDescriptiveLabelsTmp;
								}	
							}
							$descriptiveLabels = explode(';', $slider_CustomDescriptiveLabels);
						}						
					}
					//pridobitev naslovov opisnih vmesnih label za kasnejsi izris - konec ##################################
					
				
					#IZRIS OPISNIH LABEL NAD DRSNIKOM #################################################
					if($slider_MinMaxLabel){
						//parametri tabele
						$parameterTabularLabeleNad = 'lR';	//leva (l) pa desna poravnava, ki se prilagaja (R)
						
						//zacetek tabele				
						$tex .= ($export_format == 'pdf' ? '\keepXColumns \begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabeleNad.'}' : '\begin{tabular*}{3 cm}{c}');
						
						//vrstice in stolpci v tabeli						
						$tex .= $this->encodeText($MinLabel).' & '.$this->encodeText($MaxLabel);	//izpis naslovov label v eni vrstici
						//vrstice in stolpci v tabeli - konec
						
						//konec tabele
						$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular*} \\noindent");
						
						//$tex .= $texNewLine;	//v novo vrstico po izrisu label
						$tex .= '\par';	//odstavek
					}			
					#IZRIS OPISNIH LABEL NAD DRSNIKOM - KONEC #########################################
					
					#IZRIS DRSNIKA {dolzina}{pozicija bunkice}####################################
					if($slider_handle==0){	//ce je rocaj na drsniku
						
						if($export_subtype=='q_data'||$export_subtype=='q_data_all'){
							$pozicijaBunkice = $userAnswer['text']/$slider_MaxNumLabel;
						}elseif($export_subtype=='q_empty'||$export_subtype=='q_comment'){
							$pozicijaBunkice=0.5;
						}
						//echo "pozicija bunkice na drsniku: ".$pozicijaBunkice."</br>";
						//$tex .= '\noindent \circleSLIDER{0.9\textwidth}{'.$pozicijaBunkice.'}';	//drsnik z rocajem						
						$tex .= '{\centering \circleSLIDER{0.85\textwidth}{'.$pozicijaBunkice.'} \par}';	//drsnik z rocajem
						
					}else{	//drugace, ce ni rocaja
						//$tex .= '\noindent \emptySLIDER{0.9\textwidth}';	//drsnik brez rocaja
						$tex .= '{\centering \emptySLIDER{0.85\textwidth} \par}';	//drsnik brez rocaja
					}
					#IZRIS DRSNIKA {dolzina}{pozicija bunkice} - KONEC ###########################

					#IZRIS PRVE VRSTICE POD DRSNIKOM ##############################################			
					//ureditev parametrov za tabelo
					$steviloStolpcevPrvaVrstica = $steviloStolpcevPrvaVrsticaOrig = ceil(($slider_MaxNumLabel-$slider_MinNumLabel+1)/$slider_handle_step);//zaokrozi navzgor izracun stevila stolpcev prve vrstice
					
					//zaradi tezav pri izrisu vmesnih stevilk, ce je teh vec kot 21 (MAXSTEVILOSTOLPCEV), je potrebno programsko omejiti stevilo stolpcev prve vrstice
					if($steviloStolpcevPrvaVrstica>MAXSTEVILOSTOLPCEV){
						$steviloStolpcevPrvaVrstica = MAXSTEVILOSTOLPCEV;
					}
					
					$parameterTabularLabelePrvaPod='';
					if($slider_VmesneDescrLabel){	//ce je potrebno izrisati vmesne opisne labele
						$steviloStolpcevPrvaVrstica = $slider_NumofDescrLabels;
					}
					
					for($i=0; $i<$steviloStolpcevPrvaVrstica; $i++){
						if($i==0){	//ce je prvi stolpec
							$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca prilagojena sirini					
						}elseif($i==$steviloStolpcevPrvaVrstica-1){	//ce je zadnji stolpec
							$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'R' : 'l');	//desna prilagojena poravnava stolpca
						}else{	//za vse ostale stolpce med prvi in zadnjim
							$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava
						}
					}
					//ureditev parametrov za tabelo - konec
					
					//zacetek tabele
					//$tex .= ($export_format == 'pdf' ? '\begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabelePrvaPod.'}' : '\begin{tabular}{'.$parameterTabularLabelePrvaPod.'}');
					$tex .= ($export_format == 'pdf' ? '\keepXColumns \begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabelePrvaPod.'}' : '\begin{tabular}{'.$parameterTabularLabelePrvaPod.'}');
					// \keepXColumns
					//izris vrstice in stolpcev v tabeli			
					for($i=0; $i<$steviloStolpcevPrvaVrstica; $i++){
						//if($slider_VmesneDescrLabel&&$slider_DescriptiveLabel_defaults!=0){//ce je potrebno izrisati vmesne opisne labele ###################
						if($slider_VmesneDescrLabel){//ce je potrebno izrisati vmesne opisne labele ###################
							if($i==$steviloStolpcevPrvaVrstica-1){	//ce je zadnji stolpec
								$tex .= $descriptiveLabels[$i];
							}else{
								$tex .= $descriptiveLabels[$i].' & ';
							}
						}else{//ce je potrebno izrisati vmesne stevilske labele #######################################
							if($i==0){	//ce je prvi stolpec
								if($slider_MinMaxNumLabelNew==0){
									$tex .= $slider_MinNumLabel.' & ';
								}else{
									$tex .= ' & ';
								}
							}
							elseif($i==$steviloStolpcevPrvaVrstica-1){	//ce je zadnji stolpec
								if($slider_MinMaxNumLabelNew==0){
									$tex .= $slider_MaxNumLabel;
								}
							}else{	//za vse ostale stolpce med prvi in zadnjim
								if($slider_VmesneNumLabel&&$steviloStolpcevPrvaVrsticaOrig<=MAXSTEVILOSTOLPCEV){	//ce so vmesne labele stevilske in je stevilo stolpcev manjsi od maximalnega dovoljenega za ustrezen izris
									if($i==1){
										$vmesnoStevilo=$slider_MinNumLabel+$slider_handle_step;
									}else{
										$vmesnoStevilo=$vmesnoStevilo+$slider_handle_step;
									}
								}else{
									$vmesnoStevilo='';
								}
								$tex .= $vmesnoStevilo.' & ';					
							}
						}
					}
					//izris vrstice in stolpcev v tabeli - konec
					
					//konec tabele
					$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular} \\noindent");
					#IZRIS PRVE VRSTICE POD DRSNIKOM - KONEC ######################################
					//echo $tex;
					
					#IZRIS DRUGE VRSTICE POD DRSNIKOM - LABELE PODROCIJ ###########################
					if($slider_labele_podrocij){	//ce imamo vklopljene labele podrocij
						$tex .= $texNewLine;
						//ureditev parametrov za tabeli
						$parameterTabularLabeleDrugaPod='';
						$prazniStolpciZaGraficneOznake = '';
						for($i=0; $i<$slider_StevLabelPodrocij; $i++){
							$parameterTabularLabeleDrugaPod .= ($export_format == 'pdf' ? '|C|' : '|c|');	//sredinska poravnava
							$parameterTabularLabeleTretjaPod .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava
							if($i!=0){
								$prazniStolpciZaGraficneOznake .= ' & ';	
							}					
						}
						$prazniStolpciZaGraficneOznake .= $texNewLine.'\hline';
						//ureditev parametrov za tabeli - konec
										
						//zacetek tabele z graficnimi oznakami
						$tex .= ($export_format == 'pdf' ? '\begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabeleDrugaPod.'}' : '\begin{tabular}{'.$parameterTabularLabeleDrugaPod.'}');

						//izris prazne vrstice z graficnimi oznakami label (crta horizontal)
						$tex .= $prazniStolpciZaGraficneOznake;
						//izris prazne vrstice z graficnimi oznakami label (crta horizontal) - konec				
						
						//konec tabele z graficnimi oznakami
						$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular} \\noindent");
						
						$tex .= $texNewLine;				
						
						//zacetek tabele z naslovi label
						$tex .= ($export_format == 'pdf' ? '\begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabeleTretjaPod.'}' : '\begin{tabular}{'.$parameterTabularLabeleTretjaPod.'}');
						
						//izris naslovov label
						$slider_Labela_podrocja = [];	//polje, ki hrani vpisane naslove labele podrocij
						for ($i = 1; $i <= $slider_StevLabelPodrocij; $i++) {	//priprava polja z naslovi
							$slider_Labela_podrocja[$i] = ($spremenljivkaParams->get('slider_Labela_podrocja_' . $i . '') ? $spremenljivkaParams->get('slider_Labela_podrocja_' . $i . '') : $lang['srv_new_text']);
							
							if($i==1){	//ce je prvi stolpec
								$tex .= $slider_Labela_podrocja[$i];
							}else{
								$tex .= ' & '.$slider_Labela_podrocja[$i];
							}
							
						}				
						//izris naslovov label - konec				
						
						//konec tabele z naslovi label
						$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular} \\noindent");
					}
					#IZRIS DRUGE VRSTICE POD DRSNIKOM - LABELE PODROCIJ ###########################
					//echo $tex;
					
				}		
			}
			//$tex .= ' \vspace{0.3cm} ';
			
			// Izris polj drugo - ne vem...
			if (count($array_others) > 0) {								
				if($export_data_type==2||$nacinVnosa==0){ //ce je skrcen izpis izvoza ALI je stevilo					
					$tex .= $texNewLine;				
				}
				//$tex .= $texNewLine;
				foreach ($array_others AS $oKey => $other) {
					$tex .= $symbol.' '.$other['naslov'].' ';
					if($postavitev!=0){
					//if($postavitev!=0&&$nacinVnosa!=1){	//ce je 0-vodoravno ob vprasanju IN ni drsnik
						//$tex .= $texNewLine;
					}				
				}
			}	
			
			if($nacinVnosa==1){	//ce je drsnik
				//if($export_data_type==1||$export_data_type==0){	//ce je dolg ali navaden izpis izvoza, ko se izrisuje drsnik
				if(($export_data_type==1||$export_data_type==0)&&(count($array_others)==0)){	//ce je dolg ALI navaden izpis izvoza IN ni missing, ko se izrisuje drsnik
					$tex .= $this->texBigSkip;
					$tex .= $this->texBigSkip." ";
				}elseif($export_data_type==2 || count($array_others) > 0){		//ce je skrcen izpis izvoza ALI so missingi
					$tex .= $this->texNewLine;
					$tex .= $this->texNewLine;
				}
			}else{
				$tex .= $this->texNewLine;
				$tex .= $this->texNewLine;
				if($okvir == 1){	//ce je prazen vprasalnik, dodaj se dve prazni vrstici
					$tex .= $this->texNewLine;
					//$tex .= $this->texNewLine;
				}
			}

			
/* 			$tex .= $texNewLine;
			$tex .= $texNewLine; */
			/* $tex .= $this->texBigSkip;
			$tex .= $this->texBigSkip." "; */
			
			if($export_format == 'pdf'){	//ce je pdf
				//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
			}else{	//ce je rtf

			}
			return $tex;
		}
			
	}
	
	#funkcija, ki skrbi za izris vrstice tabele po stolpcih
	function izrisVrsticePoStolpcih($steviloStolpcevTabele=null, $array=null){
		$tex = '';		
		for($i=0;$i<$steviloStolpcevTabele;$i++){
			if($i!=0){	//ce ni prvi stolpec
				$tex .= ' & ';	//dodaj oznako za prehod v nov stolpec
			}
			$tex .= $array[$i];			
		}		
		return $tex;
	}	
	#funkcija, ki skrbi za izris vrstice tabele po stolpcih - konec
	
}