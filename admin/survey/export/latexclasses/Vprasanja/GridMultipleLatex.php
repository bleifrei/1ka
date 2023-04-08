<?php
/***************************************
 * Description: Priprava Latex kode za kombinirana tabela oz. GridMultiple
 *
 * Vprašanje je prisotno:
 * tip 24
 *
 * Autor: Patrik Pucer
 * Datum: 09/2017
 *****************************************/


define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip
define("RADIO_BTN_SIZE", 0.13);

class GridMultipleLatex extends LatexSurveyElement
{
	var $internalCellHeight;
	
    public function __construct()
    {
        //parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;
	protected $texBigSkip = '\bigskip ';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	protected $usr_id = null;
	protected $language;
	protected $prevod;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new GridMultipleLatex();
    }
	
	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype='', $preveriSpremenljivko=null, $export_data_type='', $loop_id=null, $language=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		$this->usr_id = $usr_id;
		
		$this->language = $language;
//print_r($spremenljivke);
		//preverjanje, ali je prevod
		if(isset($_GET['language'])){
			$this->language = $_GET['language'];
			$this->prevod = 1;
		}else{
			$this->prevod = 0;
		}
		//preverjanje, ali je prevod - konec


		//echo "<b>tip izpisa:  $export_data_type</b> </br>"; //$export_data_type: 1 - Razsirjen, 2 - Skrcen
		$presirokaTabela = 0;
		//preveri, ce je kaj v bazi
		$questionText=0;
		$userDataPresent=0;
		$izpisOdgovorov = 0;
		if($usr_id){	//ce je prisotne id uporabnika, je izpis odgovorov in je potrebno narediti naslednje stvari
			//$userAnswer = $this->GetUsersDataKombinirana($spremenljivke, $db_table, $usr_id, $questionText, $this->loop_id, $export_data_type);
			$userAnswer = $this->GetUsersDataKombinirana($spremenljivke, $db_table, $usr_id, $presirokaTabela, $this->loop_id, $export_data_type);
			foreach($userAnswer as $value){					
				if($value!=''){
					$userDataPresent=1;
				}
				//echo "Value: ".$value."</br>";
			}
			$izpisOdgovorov = 1;
		}		
		//echo "Usr_id: ".$usr_id.'</br>';
		//echo "Stevilo odgovorov: ".count($userAnswer).'</br>';
		
		
		if($userDataPresent!=0||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;		
		
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta
			//echo "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red"."</br>";
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);		//za filanje navpicnih odgovorov
			//echo "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red";
			//novo za kombinirano tabelo
			$sqlStVrednosti = sisplet_query("SELECT count(*) FROM srv_grid g, srv_grid_multiple m WHERE m.spr_id=g.spr_id AND m.parent='".$spremenljivke['id']."'");
			$rowStVrednost = mysqli_fetch_array($sqlStVrednosti);	//stevilo stolpcev		
			$numColSql = $rowStVrednost['count(*)'] + 1;	//stevilo vseh stolpcev upostevajoc prvega z navpicnimi odgovori
			
			$sqlMultipleSprId = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE parent='".$spremenljivke['id']."' ORDER BY vrstni_red");	//poizvedba spr_id dodanih moznosti v kombinirani tabeli
			$multipleSprId = array();	//polje za shranjevanje spr_id dodanih moznosti v kombinirano tabelo
			while ($rowMultipleSprId = mysqli_fetch_array($sqlMultipleSprId)) {
				$multipleSprId[] = $rowMultipleSprId['spr_id'];
			}
			
			//poizvedba vseh potrebnih podatkov dodanih moznosti v kombinirani tabeli
			$sqlMultiple = sisplet_query("SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (".implode($multipleSprId, ',').") ORDER BY m.vrstni_red, g.vrstni_red");		
			//echo "SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (".implode($multipleSprId, ',').") ORDER BY m.vrstni_red, g.vrstni_red";
			//poizvedba podnaslovov v kombinirani tabeli
			$sqlMultiplePodNaslovi = sisplet_query("SELECT naslov FROM srv_spremenljivka WHERE id IN (".implode($multipleSprId, ',').")");
			//novo za kombinirano tabelo - konec
			
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			$isCheckBox = 0;
			$enota = $spremenljivke['enota'];
			$enotaNiNula = $enotaNiNulaTmp = 0;
			$trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0);
			$customColumnLabelOption = ($spremenljivkaParams->get('custom_column_label_option') ? $spremenljivkaParams->get('custom_column_label_option') : 1);	//1 - "vse" labele,  2 - "le koncne"  labele, 3 - "koncne in vmesna"  labele

			$tex = '';
			$navpicniOdgovori = array();	//shranjuje odgovore po vrsticah	
			$vodoravniOdgovori = array();	//shranjuje odgovore po stolpcih
			$vodoravniOdgovoriVsi = array();
			$vodoravniOdgovoriTip = array();
			$vodoravniOdgovoriSprId = array();
			$vodoravniOdgovoriEnota = array();
			$podNaslovi = array();
			$missingOdgovori = array();
			
			$texNewLineAfterTable = $texNewLine." ".$texNewLine." ".$texNewLine;		
			
			$oznakaVprasanja = $this->UrediOznakoVprasanja($spremenljivke['id']);	//uredi oznako vprasanja, ker ne sme biti stevilska


			//ce je prisoten id uporabnika - ureditev belezenja vnesenega odgovora pod Drugo:
			if($usr_id){
				$multipleVredIdDrugo = array();	//polje za shranjevanje vre_id, kjer je prisotna moznost Drugo:
				//poizvedba vrednosti polj, kjer se pojavijo polja Drugo:
				$sqlMultipleVredIdZaDrugo = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id IN (".implode($multipleSprId, ',').") AND other = 1");				
				while ($rowsqlMultipleVredIdZaDrugo = mysqli_fetch_array($sqlMultipleVredIdZaDrugo)) {
					$multipleVredIdDrugo[] = $rowsqlMultipleVredIdZaDrugo['id'];
				}

				if(!empty($multipleVredIdDrugo)){	
					$multipleBesediloDrugo = array();	//polje za shranjevanje vnesenega besedila iz strani respondenta, kjer je prisotna moznost Drugo:
					$sqlStavekMultipleBesediloDrugo = "SELECT text FROM srv_data_text_active WHERE vre_id IN (".implode($multipleVredIdDrugo, ',').") AND usr_id=".$usr_id." ";
					$sqlMultipleBesediloDrugo = sisplet_query($sqlStavekMultipleBesediloDrugo);
					while ($rowsqlMultipleBesediloDrugo = mysqli_fetch_array($sqlMultipleBesediloDrugo)) {
						$multipleBesediloDrugo[] = $rowsqlMultipleBesediloDrugo['text'];
					}
				}
				
				$indeksBesediloDrugo = 0; //definiranje indeksa za izpis vnesenega besedila v Drugo:
			}
			//ce je prisoten id uporabnika - ureditev belezenja vnesenega odgovora pod Drugo: - konec

			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - navpicni odgovori
			while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
				$stringTitleRow = '';
				/* $stringTitleRow = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
				# po potrebi prevedemo naslov 			
				$naslov = $this->srv_language_vrednost($rowVrednost['id']);
				if ($naslov != '') {
					//$rowVrednost['naslov'] = $naslov;
					$stringTitleRow = $naslov;
				}	 */	
				
				if($this->prevod){ //ce je prevod ankete
					# po potrebi prevedemo naslov 			
					$rowl = $this->srv_language_vrednost($rowVrednost['id']);					
					if ($rowl != '') {						
						$stringTitleRow = ((( $rowl['naslov'] ) ? $rowl['naslov'] : ( ( $rowl['naslov2'] ) ? $rowl['naslov2'] : $rowl['variable'] ) )); //prevod naslova v ustreznem jeziku;
					}
					if($stringTitleRow == ''){	//ce ni prevoda, prevzemi izvirno
						$stringTitleRow = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));	
					}
				}else{
					$stringTitleRow = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
				}


				//echo "vrednost: ".$rowVrednost['id']."</br>";
				//echo "kombo naslov1: $stringTitleRow</br>";

				$besediloDrugo = '';

				//ce je drugo vnesen kot odgovor in je prisoten id uporabnika
				if($rowVrednost['other'] && $usr_id){
					//zabelezi besedilo, ki je trenuten uporabnik za trenuten odgovor zapisal pod Drugo:					
					$besediloDrugo = $multipleBesediloDrugo[$indeksBesediloDrugo];
					$this->encodeText($besediloDrugo);
					$besediloDrugo = ' \\textcolor{crta}{\footnotesize{'.$besediloDrugo.'}} ';
					$indeksBesediloDrugo++;	//povecaj indeks za izpis vnesenega besedila v Drugo:
				}
				//ce je drugo vnesen kot odgovor in je prisoten id uporabnika - konec

				$stringTitleRow = Common::getInstance()->dataPiping($stringTitleRow, $usr_id, $loop_id);			
				array_push($navpicniOdgovori, $this->encodeText($stringTitleRow)." ".$besediloDrugo);	//filanje polja z navpicnimi odgovori (po vrsticah)
			}
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - navpicni odgovori - konec


			$sqlStolpciVrednosti = sisplet_query("SELECT g.spr_id, g.naslov, g.variable FROM srv_grid g, srv_grid_multiple m WHERE m.parent='".$spremenljivke['id']."' AND g.spr_id=m.spr_id");
					
			$steviloSkupinRoletSeznamov = 0;	//belezi stevilo enot z roletami ali seznamov (pomembno za pravilen izris, za presiroko tabelo)
			$steviloOdgovorovRoletSeznamov = 0;	//belezi stevilo posameznih odgovorov, ki sestavljajo razlicne rolete ali sezname
			$sprIdRoletSeznamov = 0;
			$sprIdRoletSeznamovTmp = 0;			

			//pregled vseh odgovorov po stolpcih po $sqlStolpciVrednosti - vodoravni odgovori
			while ($colVrednost = mysqli_fetch_assoc($sqlStolpciVrednosti)){
				$stringTitleCol = '';				
				$rowl = $this->srv_language_grid($colVrednost['variable'],$colVrednost['spr_id']);							
				if (strip_tags($rowl['naslov']) != '') $colVrednost['naslov'] = $rowl['naslov'];				
				$stringTitleCol = $colVrednost['naslov'];
				
				/* if($this->prevod){ //ce je prevod ankete
					# po potrebi prevedemo naslov 			
					$rowl = $this->srv_language_grid($colVrednost['variable'],$colVrednost['spr_id']);					
					if ($rowl != '') {						
						$stringTitleCol = $rowl['naslov']; //prevod naslova v ustreznem jeziku;
					}
					if($stringTitleCol == ''){	//ce ni prevoda, prevzemi izvirno
						$stringTitleCol = ((( $colVrednost['naslov'] ) ? $colVrednost['naslov'] : ( ( $colVrednost['naslov2'] ) ? $colVrednost['naslov2'] : $colVrednost['variable'] ) ));	
					}
				}else{
					$stringTitleCol = ((( $colVrednost['naslov'] ) ? $colVrednost['naslov'] : ( ( $colVrednost['naslov2'] ) ? $colVrednost['naslov2'] : $colVrednost['variable'] ) ));
				} */

				$stringTitleCol = Common::getInstance()->dataPiping($stringTitleCol, $usr_id, $loop_id);				
				$stringTitleCol = '\footnotesize{'.$this->encodeText($stringTitleCol, 0, 1).'}';	//zmanjsanje pisave za naslove stolpcev tabele
				array_push($vodoravniOdgovori, $stringTitleCol);	//filanje polja z vodoravnimi odgovori (po stolpcih)				

				$rowMultiple = mysqli_fetch_array($sqlMultiple);
				array_push($vodoravniOdgovoriTip, $rowMultiple['tip']);	//filanje polja s tipi spremenljivk
				array_push($vodoravniOdgovoriSprId, $rowMultiple['spr_id']); //filanje polja z id spremenljivk
				array_push($vodoravniOdgovoriEnota, $rowMultiple['enota']); //filanje polja z enoto spremenljivk
				
				//$sprIdRoletSeznamov = $rowMultiple['spr_id'];
				if(($rowMultiple['enota'] == 2 || $rowMultiple['enota'] == 6)){
					$sprIdRoletSeznamov = $rowMultiple['spr_id'];					
				}
				
				/* echo "enota ".$rowMultiple['enota']."</br>";
				echo "spr ".$rowMultiple['spr_id']."</br>"; */
				//if(($rowMultiple['enota'] == 2 || $rowMultiple['enota'] == 6)){	//ce je roleta ali seznam IN je izpis odgovorov 
				//if(($rowMultiple['enota'] == 2 || $rowMultiple['enota'] == 6)&&$izpisOdgovorov){	//ce je roleta ali seznam IN je izpis odgovorov 
				if((($rowMultiple['enota'] == 2 || $rowMultiple['enota'] == 6)&&$izpisOdgovorov) || ($izpisOdgovorov&&$rowMultiple['tip']==6&&$export_data_type==2)){	//(ce je roleta ALI seznam IN je izpis odgovorov) ALI (je izpis odgovorov IN je radio button)
					if($sprIdRoletSeznamovTmp != $sprIdRoletSeznamov){
						$steviloSkupinRoletSeznamov++;
					}
					$steviloOdgovorovRoletSeznamov++;		
					//echo "spr: $sprIdRoletSeznamov </br>";			
				}

				$sprIdRoletSeznamovTmp = $sprIdRoletSeznamov;				
			}
			//pregled vseh odgovorov po stolpcih po $sqlStolpciVrednosti - vodoravni odgovori - konec


			$vodoravniOdgovoriVsi = $vodoravniOdgovori;
			/* echo "stevilo skupin rolet/seznamov ".($steviloSkupinRoletSeznamov)." </br>";
			echo "stevilo odgovorov v roletah ali seznamu ".($steviloOdgovorovRoletSeznamov)." </br>"; */

/* 			echo "vodoravniOdgovoriSprId: ";
			print_r($vodoravniOdgovoriSprId);
			echo "</br>";
			echo "Enote: ";
			print_r($vodoravniOdgovoriEnota);
			echo "</br>"; */

			//ureditev polja z nadnaslovi kombinirane tabele
			while ($rowMultiplePodNaslovi = mysqli_fetch_array($sqlMultiplePodNaslovi)){			
				array_push($podNaslovi, $this->encodeText($rowMultiplePodNaslovi['naslov']));				
			}
			//ureditev polja z nadnaslovi kombinirane tabele - konec

			//za ureditev presirokih tabele
			//$steviloPodstolpcev = $numColSql;
			$steviloPodatkovZaIzpis = $numColSql-1;			
			$steviloPodstolpcev = $numColSql - $steviloOdgovorovRoletSeznamov + $steviloSkupinRoletSeznamov;			
			$mejaZaVelikeTabele = 7;
			$velikostTabele = $steviloPodstolpcev-1;
			//echo "<b>velikost tabele: </b>".($velikostTabele)." </br>";
			if($velikostTabele > $mejaZaVelikeTabele){	//ce imamo veliko tabelo, jo je potrebno razbiti na vec tabel, ker drugace je presiroka
				//echo "tabela je prevelika, ima ".($velikostTabele)." stolpcev</br>";
				
				$presirokaTabela = 1;
				$steviloTabelCelih = intval($velikostTabele / $mejaZaVelikeTabele);
				$steviloTabelMod = $velikostTabele % $mejaZaVelikeTabele;
				$delnaTabela = 0;
				if($steviloTabelMod != 0){
					$delnaTabela = 1;
				}
				$steviloTabel = $steviloTabelCelih + $delnaTabela;

				//echo "stevilo podtabel celih ".($steviloTabelCelih)." </br>";
				//echo "stevilo podtabel mod ".($steviloTabelMod)." </br>";
				//echo "stevilo podtabel za izpis: ".($steviloTabel)." </br>";
				//echo "stevilo podatkov za izpis: ".($steviloPodatkovZaIzpis)." </br>";
				
			}else{
				$presirokaTabela = 0;
			}
			//za ureditev presirokih tabele - konec

		
			if($presirokaTabela == 0){	//ce tabela ni presiroka	#################################################			
				
				
				//ureditev parametrov za tabelo, pridobitev stevila stolpcev za vsak tip dodanega vprasanja (podstolpec), priprava polj in spremenljivk, ce enota je razlicna od nula oz. izris ni klasicna tabela########
				$parameterTabular = '';
				$parameterTabular .= ($export_format == 'pdf' ? 'A' : 'l');	//leva poravnava stolpca fiksne sirine ZA PRVI STOLPEC (parameteri tabele)			
				$indeksParameterTabular=1;		
				
				$indeksPodStolpci=0;
				$steviloPodStolpcev = array();
				
				//za preureditev polj, ko imamo seznam ali roleto
				$indeksVodoravniOdgovori = 0;
				$vodoravniOdgovoriPrva = $vodoravniOdgovori;	//hrani preurejene vodoravne odgovore za prvo vrstico	
				$splice=0;
				$toDelete = array();	//polje, ki hrani, katere dele polja je potrebno odstraniti, ce imamo seznam ali roleto
				//za preureditev polj, ko imamo seznam ali roleto - konec

				$valueTmp = null;
				
				foreach($vodoravniOdgovoriSprId as $value){	//parametri tabele ZA OSTALE STOLPCE+pridobitev stevila stolpcev+priprava polj in spremenljivk ###############
					//echo $vodoravniOdgovoriEnota[$indeksParameterTabular-1]."</br>";
	 				//echo "value: ".$value."</br>";
					//echo "valueTmp: ".$valueTmp."</br>";
					//echo "indeksParameterTabular: ".$indeksParameterTabular."</br>";
					//echo "Stevilo vodoravniOdgovoriSprId: ".count($vodoravniOdgovoriSprId)."</br>";
										
					if($izpisOdgovorov == 0 || ($izpisOdgovorov == 1 && $vodoravniOdgovoriEnota[$indeksParameterTabular-1]==0 && $export_data_type == 1) || ($izpisOdgovorov == 1 && $vodoravniOdgovoriEnota[$indeksParameterTabular-1]==0 && $vodoravniOdgovoriTip[$indeksParameterTabular-1]!=6 && $export_data_type == 2)){	//ce je prazen vprasalnik ALI (je izpis odgovorov IN ni roleta/seznam IN je Razsirjen izvoz) ALI (je izpis odgovorov IN ni roleta/seznam IN ni radio button IN je skrcen izvoz)
						if($indeksParameterTabular==1){	//ce je prvi podstolpec
							$parameterTabular .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava stolpca				
							
							$steviloPodStolpcev[$indeksPodStolpci] = 0;
							$steviloPodStolpcev[$indeksPodStolpci]++;
						}else{	//ce so ostali podstolpci
							//if($value!=$valueTmp&&$indeksParameterTabular!=count($vodoravniOdgovoriSprId)){					
							if($value!=$valueTmp){					
								$parameterTabular .= ($export_format == 'pdf' ? '|C' : '|c');	//sredinska poravnava stolpca z locilom
								//echo "Sprememba </br>";
								$indeksPodStolpci++;
								$steviloPodStolpcev[$indeksPodStolpci] = 0;
								$steviloPodStolpcev[$indeksPodStolpci]++;					
							}elseif($indeksParameterTabular==count($vodoravniOdgovoriSprId)){
								$parameterTabular .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava stolpca
												
								$steviloPodStolpcev[$indeksPodStolpci]++;
							}else{
								$parameterTabular .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava stolpca
								
								$steviloPodStolpcev[$indeksPodStolpci]++;
							}
						}
					}

					if(($vodoravniOdgovoriEnota[$indeksParameterTabular-1]!=0&&$izpisOdgovorov&&$export_data_type == 1) || ($izpisOdgovorov&&$export_data_type == 2&&$vodoravniOdgovoriTip[$indeksParameterTabular-1]==6)){	//(ce je roleta ali izberite iz seznama IN je izpis odgovorov IN razsirjen izvoz) ALI (je izpis odgovorov IN skrcen izvoz IN radio button)
						//echo "tabela ni presiroka </br>";
						while($enotaNiNulaTmp==0){	//omejimo zanko le na en prehod
							if($indeksParameterTabular==1){	//ce je prvi podstolpec
								$parameterTabular .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava stolpca				
							
								$steviloPodStolpcev[$indeksPodStolpci] = 0;
								$steviloPodStolpcev[$indeksPodStolpci]++;
								
								if($splice==0){
									array_splice($vodoravniOdgovoriPrva, $indeksVodoravniOdgovori, 1, ''); //na mesto vodoravnega odgovora dodaj prazen tekst
									$splice=1;							
								}
							}else{	//ce so ostali podstolpci					
								if($value!=$valueTmp){	//ce je sprememba spremenljivke					
									$parameterTabular .= ($export_format == 'pdf' ? '|C' : '|c');	//sredinska poravnava stolpca z locilom
									
									//echo "</br> sprememba spremenljivke iz $valueTmp v $value ".'</br>';
									
									$indeksPodStolpci++;
									$steviloPodStolpcev[$indeksPodStolpci] = 0;
									$steviloPodStolpcev[$indeksPodStolpci]++;
									
									array_splice($vodoravniOdgovoriPrva, $indeksVodoravniOdgovori, 1, ''); //na mesto vodoravnega odgovora dodaj prazen tekst
									$splice=1;							
								}else{	//ce ni spremembe spremenljivke
									array_push($toDelete, $indeksVodoravniOdgovori);	//zabelezi indeks elementa polja, ki ga je potrebno izbrisati, da bo stevilo stolpec ustrezno
								}
							}
							$enotaNiNulaTmp = 1;						
						}						
						$enotaNiNulaTmp = 0;
						$enotaNiNula = 1;						
					}	//ce je roleta ali izberite iz seznama in je izpis odgovorov - konec#####################################

					$valueTmp = $value;		
					$indeksParameterTabular++;
					$indeksVodoravniOdgovori++;
				}	//parametri tabele ZA OSTALE STOLPCE+pridobitev stevila stolpcev+priprava polj in spremenljivk - konec ##########################################
				
				//echo "Parametri tabel: ".$parameterTabular."</br>";
				
				//brisanje odvecnih elementov polja in ponovna indeksacija polja
				foreach($toDelete as $value){
					unset($vodoravniOdgovoriPrva[$value]);	//brisanje ustreznih elementov polja
					unset($vodoravniOdgovoriEnota[$value]);	
					unset($vodoravniOdgovoriTip[$value]);
				}		
				$vodoravniOdgovoriPrva = array_values($vodoravniOdgovoriPrva); // reindeksiranje polja za naslovno vrstico tabele
				$vodoravniOdgovoriEnota = array_values($vodoravniOdgovoriEnota); // reindeksiranje polja za enote vprasanja
				$vodoravniOdgovoriTip = array_values($vodoravniOdgovoriTip); // reindeksiranje polja za tip vprasanja
				//brisanje odvecnih elementov polja in ponovna indeksacija polja - konec
				
				//dodatna priprava polj in spremenljivk, ce enota je razlicna od nula oz. izris ni klasicna tabela #####################################

				if($enotaNiNula==1&&$izpisOdgovorov){	//ce ni klasicna tabela IN je izpis odgovorov
					$steviloPravihStolpcev = 0;
					foreach($steviloPodStolpcev as $value){
						$steviloPravihStolpcev = $steviloPravihStolpcev +  $value;
					}			
					
					$numColSqlPrva = $steviloPravihStolpcev+1;	//stevilo stolpcev z vodoravnimi odgovori+stolpec z navpicnimi odgovori

				}else{
					$numColSqlPrva = $numColSql;
				}
				//echo "stevilo stolpcev prva vrstica $numColSqlPrva </br>";
				//dodatna priprava polj in spremenljivk, ce enota je razlicna od nula oz. izris ni klasicna tabela - konec #############################
				
				//ureditev parametrov za tabelo, pridobitev stevila stolpcev za vsak tip dodanega vprasanja (podstolpec), priprava polj in spremenljivk, ce enota je razlicna od nula oz. izris ni klasicna tabela - konec ########
				
				
				//IZRIS TABELE
				$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);	//zacetek tabele
				
				#nadnaslovi nad prvo vrstico #########################################################
				if($spremenljivke['grid_subtitle1']==1){	//ce so podnaslovi
					for($n=0;$n<count($podNaslovi);$n++){
						//echo $podNaslovi[$n].'</br>';
						//$tex .= ' & \multicolumn{'.$steviloPodStolpcev[$n].'}{c}{'.$podNaslovi[$n].'}';
						$tex .= ' & \multicolumn{'.$steviloPodStolpcev[$n].'}{C}{'.$podNaslovi[$n].'}';
					}		
				}
				$tex .= $texNewLine;		
				#nadnaslovi nad prvo vrstico - konec #########################################################		
				
				#prva vrstica tabele ####################################################################################					
				//izris prve vrstice tabele
				$tex .= $this->LatexPrvaVrsticaMultiGrid($numColSqlPrva, $enota, $trak, $customColumnLabelOption, $spremenljivke, $vodoravniOdgovoriPrva, 0);
				#prva vrstica tabele - konec ##################################################################################
				
				$tex .= $texNewLine;
				
				//print_r($userAnswer);

				#izris vrstic tabele
				$fillablePdf = 0;
				//echo "stevilo stolpcev tabele $numColSql </br>";
				$tex .= $this->LatexVrsticeMultigrid($numRowsSql, $export_format, $enota, $simbolTex, $navpicniOdgovori, 0, $fillablePdf, $numColSql, $spremenljivke, $trak, $vodoravniOdgovori, $texNewLine, 0, 0, $vodoravniOdgovoriTip, $vodoravniOdgovoriEnota, $vodoravniOdgovoriSprId, $userAnswer, $export_subtype, $preveriSpremenljivko, $userDataPresent, $presirokaTabela, $export_data_type, $this->usr_id, $this->loop_id);
				#izris vrstic tabele - konec
				
				$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');	//zakljucek tabele
				//IZRIS TABELE - KONEC

				$tex .= $this->texBigSkip;
				$tex .= $this->texBigSkip;

				return $tex;

			}elseif($presirokaTabela == 1){	//ce je tabela presiroka #################################################

				
				//za vsako spremenljivko oz. podtabelo
				foreach($multipleSprId AS $sprId){
					//echo "$sprId</br>";

					#pobiranje podatkov o odgovorih respondenta za vsako podtabelo posebej
					if($usr_id){	//ce je prisotne id uporabnika, je izpis odgovorov in je potrebno narediti naslednje stvari						
						$spremenljivke['id'] = $sprId;
						$userAnswer = $this->GetUsersDataKombinirana($spremenljivke, $db_table, $usr_id, $presirokaTabela, $this->loop_id, $export_data_type);						
						foreach($userAnswer as $value){					
							if($value!=''){
								$userDataPresent=1;
							}
							//echo "Value: ".$value."</br>";
						}
						$izpisOdgovorov = 1;
					}
					//echo "podatek je: ".$userDataPresent."</br>";
					/* print_r($userAnswer);
					echo "</br>"; */
					#pobiranje podatkov o odgovorih respondenta za vsako podtabelo posebej - konec

					$vodoravniOdgovoriTip = array();
					$vodoravniOdgovoriSprId = array();
					$vodoravniOdgovoriEnota = array();
					$vodoravniOdgovori = array();
					$podNaslovi = array();
					//poizvedba vseh potrebnih podatkov dodanih moznosti v podtabeli kombinirane tabele
					$sqlStavekPodTabele = "SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id = ".$sprId." ORDER BY m.vrstni_red, g.vrstni_red";
					//echo "$sqlStavekPodTabele</br>";
					$sqlPodTabele = sisplet_query($sqlStavekPodTabele);
					
					while($rowMultiple = mysqli_fetch_array($sqlPodTabele)){
						array_push($vodoravniOdgovoriTip, $rowMultiple['tip']);	//filanje polja s tipi spremenljivk
						array_push($vodoravniOdgovoriSprId, $rowMultiple['spr_id']); //filanje polja z id spremenljivk
						array_push($vodoravniOdgovoriEnota, $rowMultiple['enota']); //filanje polja z enoto spremenljivk
						//print_r($vodoravniOdgovoriTip);
						
						//$stringTitleCol = $this->encodeText($rowMultiple['naslov'], 0, 1);
						//$stringTitleCol = '\footnotesize{'.$stringTitleCol.'}';	//zmanjsanje pisave za naslove stolpcev tabele
						$stringTitleCol = $rowMultiple['naslov'];						
						$stringTitleCol = Common::getInstance()->dataPiping($stringTitleCol, $usr_id, $loop_id);				
						$stringTitleCol = '\footnotesize{'.$this->encodeText($stringTitleCol, 0, 1).'}';	//zmanjsanje pisave za naslove stolpcev tabele
						
						//if(($vodoravniOdgovoriEnota[0] == 2 || $vodoravniOdgovoriEnota[0] == 6) && $izpisOdgovorov && $export_data_type == 1){	//(ce je roleta ALI seznam) IN je izpis odgovorov IN razsirjen izvoz
						if(($vodoravniOdgovoriEnota[0] == 2 || $vodoravniOdgovoriEnota[0] == 6) && $izpisOdgovorov && $export_data_type == 1 || ($izpisOdgovorov && $export_data_type == 2 && $vodoravniOdgovoriTip[0] == 6 )){	//(ce je roleta ALI seznam) IN je izpis odgovorov IN razsirjen izvoz ALI (je izpis odgovorov IN skrcen izvoz IN radio button)
							
						}else{
							array_push($vodoravniOdgovori, $stringTitleCol);	//filanje polja z vodoravnimi odgovori (po stolpcih)							
						}
						//array_push($vodoravniOdgovori, $stringTitleCol);	//filanje polja z vodoravnimi odgovori (po stolpcih)
						//echo $rowMultiple['naslov']."</br>";
					}

					//poizvedba podnaslovov v podtabeli kombinirane tabele
					$sqlMultiplePodNaslovi = sisplet_query("SELECT naslov FROM srv_spremenljivka WHERE id =".$sprId);
					//ureditev polja z nadnaslovi kombinirane tabele
					while ($rowMultiplePodNaslovi = mysqli_fetch_array($sqlMultiplePodNaslovi)){			
						array_push($podNaslovi, $this->encodeText($rowMultiplePodNaslovi['naslov']));				
					}
					//ureditev polja z nadnaslovi kombinirane tabele - konec
				

					$enota = $vodoravniOdgovoriEnota[0];
					/* print_r($vodoravniOdgovoriTip);
					echo "</br>";
					print_r($vodoravniOdgovoriSprId);
					echo "</br>";*/
					/* print_r($vodoravniOdgovoriEnota);
					echo "</br>";  */
					/* print_r($vodoravniOdgovori);
					echo "</br>"; */
					
					//ureditev parametrov tabele
					/* echo "enota:".$vodoravniOdgovoriEnota[0]."</br>";
					echo "izpisOdgovorov:".$izpisOdgovorov."</br>";
					echo "tip:".$vodoravniOdgovoriTip[0]."</br>";
					echo "export_data_type:".$export_data_type."</br>"; */
					//if(($enota == 2 || $enota == 6) && $izpisOdgovorov && $export_data_type == 1){	//(ce je roleta ALI seznam) IN je izpis odgovorov IN razsirjen izvoz
					if(($enota == 2 || $enota == 6) && $izpisOdgovorov && $export_data_type == 1 || ($izpisOdgovorov && $export_data_type == 2 && $vodoravniOdgovoriTip[0] == 6 )){	//(ce je roleta ALI seznam) IN je izpis odgovorov IN razsirjen izvoz ALI (je izpis odgovorov IN skrcen izvoz IN radio button)
						$steviloStolpcev = 1 + 1;
					}else{
						$steviloStolpcev = count($vodoravniOdgovoriSprId) + 1;
					}
					//echo "stevilo stolpcev: $steviloStolpcev</br>";

					/* if((($vodoravniOdgovoriEnota[0] == 2 || $vodoravniOdgovoriEnota[0] == 6)&&$izpisOdgovorov&&$export_data_type == 1) || ($izpisOdgovorov&&$export_data_type == 2&&$vodoravniOdgovoriTip[$indeksParameterTabular-1]==6)){	//(ce je roleta ali izberite iz seznama IN je izpis odgovorov IN razsirjen izvoz) ALI (je izpis odgovorov IN skrcen izvoz IN radio button)
					
					} */

					
					//echo "stevilo stolpcev: $steviloStolpcev</br>";

					$parameterTabular = '';
					$parameterTabular .= ($export_format == 'pdf' ? 'A' : 'l');	//leva poravnava stolpca fiksne sirine ZA PRVI STOLPEC (parameteri tabele)	
					
					for($i=1; $i<$steviloStolpcev;$i++){
						$parameterTabular .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava stolpca
					}					
					//echo "parametri tabele: $parameterTabular </br>";
					//ureditev parametrov tabele - konec


					//IZRIS TABELE
					$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);	//zacetek tabele
									
					#nadnaslovi nad prvo vrstico #########################################################
					$steviloPodStolpcev = $steviloStolpcev - 1;
					if($spremenljivke['grid_subtitle1']==1){	//ce so podnaslovi
						for($n=0;$n<count($podNaslovi);$n++){
							//echo "podnaslov za sprem ".$vodoravniOdgovoriSprId[0]." je: ".$podNaslovi[$n]." število stolpcev pa $steviloPodStolpcev</br>";
							//$tex .= ' & \multicolumn{'.$steviloPodStolpcev.'}{c}{'.$podNaslovi[$n].'}';
							$tex .= ' & \multicolumn{'.$steviloPodStolpcev.'}{C}{'.$podNaslovi[$n].'}';
						}		
					}
					$tex .= $texNewLine;		
					#nadnaslovi nad prvo vrstico - konec #########################################################		

					#prva vrstica tabele ####################################################################################					
					//izris prve vrstice tabele
					$tex .= $this->LatexPrvaVrsticaMultiGrid($steviloStolpcev, $enota, $trak, $customColumnLabelOption, $spremenljivke, $vodoravniOdgovori, 0);
					#prva vrstica tabele - konec ##################################################################################

					$tex .= $texNewLine;

					#izris vrstic tabele
					$fillablePdf = 0;					
					//echo "stevilo stolpcev tabele $steviloStolpcev </br>";
					$tex .= $this->LatexVrsticeMultigrid($numRowsSql, $export_format, $enota, $simbolTex, $navpicniOdgovori, 0, $fillablePdf, $steviloStolpcev, $spremenljivke, $trak, $vodoravniOdgovori, $texNewLine, 0, 0, $vodoravniOdgovoriTip, $vodoravniOdgovoriEnota, $vodoravniOdgovoriSprId, $userAnswer, $export_subtype, $preveriSpremenljivko, $userDataPresent, $presirokaTabela, $export_data_type, $this->usr_id, $this->loop_id);
					#izris vrstic tabele - konec

					$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');	//zakljucek tabele
					//IZRIS TABELE - KONEC

					$tex .= $this->texBigSkip;
					$tex .= $this->texBigSkip;


				}

				return $tex;
			}
		}		
	}	
}