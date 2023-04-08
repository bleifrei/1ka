<?php
/***************************************
 * Description: Priprava Latex kode za Heatmap
 *
 * Vprašanje je prisotno:
 * tip 27
 *
 * Autor: Patrik Pucer
 * Datum: 09/2017
 *****************************************/
//use enkaParameters;
define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip
define("RADIO_BTN_SIZE", 0.13);

class HeatmapLatex extends LatexSurveyElement
{	
	protected $polyX = array();
	protected $polyY = array();
	protected $path2Images;

    public function __construct()
    {
		global $site_path;
		$this->path2Images = $site_path.'uploadi/editor/';
        //parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;
	protected $texBigSkip = '\bigskip';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new HeatmapLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype='', $preveriSpremenljivko=null, $loop_id=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//preveri, ce je kaj v bazi
		//$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id);
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		//echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
		
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik	ali je potrebno pokazati tudi ne odgovorjena vprasanja	
			global $lang;
			
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);
			
			$tex = '';
			$point = array();
			//nastavitve iz baze ##########################
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			//nastavitve iz baze - konec ####################

			$navpicniOdgovori = array();
			$navpicniOdgovori = [];
			$obmocjaNaSliki = array();
			$coordsObmocijNaSliki = array();
			
			$texNewLineAfterTable = $texNewLine." ".$texNewLine." ".$texNewLine;
		
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
			while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
				$stringTitleRow = $rowVrednost['naslov']; //odgovori na levi strani
				$stringTitleRow = Common::getInstance()->dataPiping($stringTitleRow, $usr_id, $loop_id);
				array_push($navpicniOdgovori, $this->encodeText($stringTitleRow) );	//filanje polja z navpicnimi odgovori (po vrsticah)	
			}
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - konec
			
			
			//$tex .= $this->IzrisVsotaTabela($spremenljivke, $numRowsSql, $navpicniOdgovori, $texNewLine, $texNewLineAfterTable, $export_format, 0);			
			
			$imageName = $this->getImageName('hotspot', $spremenljivke['id'], 'hotspot_image=');
			//echo("iz heatmap ime slike: ".$imageName."</br>");
			$path2Images = $this->path2Images;
			
			$imageNameTest = $path2Images.$imageName.'.png';	//za preveriti, ali obstaja slikovna datoteka na strezniku
			
			//error_log("iz heatmap: ".$imageNameTest);
			//echo("iz heatmap: ".$imageNameTest."</br>");
			
			$imageName = $path2Images.$imageName;
			
			if(filesize($imageNameTest) > 0){
				$image = PIC_SIZE."{".$imageName."}";	//priprave slike predefinirane dimenzije
			}else{
				//$image = 'ni slike';
				$image = $lang['srv_pc_unavailable'];
			}

			$tex .= $image."".$texNewLine; //izris slike
			
			//iz baze poberi imena obmocij
			$sqlHotSpotRegions = sisplet_query("SELECT region_name, region_coords FROM srv_hotspot_regions WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");		

			//izris imen obmocij po $sqlHotSpotRegions
			$whileIndeks = 0;
			while ($rowHotSpotRegions = mysqli_fetch_assoc($sqlHotSpotRegions)){
				if($whileIndeks == 0){ //ce so prisotna imena obmocij, izpisi besedilo "Obmocja na sliki"
					$tex .= $lang['srv_export_hotspot_regions_names'].': '.$texNewLine;	//izpis besedila "Obmocja na sliki"
				}
				
				$regionName = $rowHotSpotRegions['region_name'];
				$regionName = Common::getInstance()->dataPiping($regionName, $usr_id, $loop_id);
				$regionName = $this->encodeText($regionName);
				$tex .= $regionName.''.$texNewLine;
				//echo "ime: $regionName </br>";
				
				if($regionName){				
					array_push($obmocjaNaSliki, $regionName);				
					$coordsObmocijNaSliki[$regionName]=$rowHotSpotRegions['region_coords'];					
					$point[$regionName] = 0;
				}
				$whileIndeks++;
			}
			
			$tex .= $texNewLine;
			
			//ureditev missing-ov
			if(count($missingOdgovori)!=0){	//ce so missing-i
				$vodoravniOdgovori = $this->AddMissingsToAnswers($vodoravniOdgovori, $missingOdgovori);
			}
			//ureditev missing-ov - konec
			
			
	/* 		//izris moznih odgovorov
			$tex .= $lang['srv_drag_drop_answers'].': '.$texNewLine;
			for($i=0; $i<$numColSql; $i++){
				$tex .= $vodoravniOdgovori[$i].$texNewLine;
			} */
			
			
			if($userDataPresent){	//ce je kaj v bazi, je potrebno izrisati tabelo s koordinatami in naslovom
				
				#pobiranje parametrov heatmap
				$sql = sisplet_query("SELECT params FROM srv_spremenljivka WHERE id = '".$spremenljivke['id']."'");
				$row = mysqli_fetch_assoc($sql);
				$spremenljivkaParams = new enkaParameters($row['params']);
				//html slike
				$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
				//stevilo dovoljenih klikov
				$heatmap_num_clicks = ($spremenljivkaParams->get('heatmap_num_clicks') ? $spremenljivkaParams->get('heatmap_num_clicks') : 1);
				#pobiranje parametrov heatmap - konec
				
				$textboxWidthOdgovori = '1';	//sirina okvirja z odgovorom
				$textboxHeightOdgovori = 0;	//visina okvirja z odgovorom
				$noBordersOdgovori = 0;
				$parameterTabular = 'l';
				//za ureditev stevila tock v izbranih obmocjih
				$dataPointValue = array();
				$data = array();
				$numOfAnswers=count($this->userAnswer);
				//za ureditev stevila tock v izbranih obmocjih - konec
				//echo "stevilo odgovorov: ".count($this->userAnswer)."</br>";
				
				#sporocilo o stevilu moznih klikov
				$tex .= $lang['srv_vprasanje_heatmap_num_clicks'].": ";
				//$tex .= $lang['srv_vprasanje_heatmap_num_clicks'].": ".$texNewLine;
				if($export_format == 'pdf'){
					$tex .= '\\textcolor{crta}{'.$numOfAnswers.'/'.$heatmap_num_clicks.'}'.$texNewLine;
				}else if($export_format == 'rtf'){
					$tex .= ' '.$numOfAnswers.'/'.$heatmap_num_clicks.' '.$texNewLine.$texNewLine;
				}
				
				
				#sporocilo o stevilu moznih klikov - konec
				
				#sporocilo o koordinatah klikov
				$tex .= $lang['srv_analiza_heatmap_clicked_coords'].": ";
 				
				
				for($i=0; $i<count($this->userAnswer);$i++){	//sprehodi se po tockah		
					
					//echo "rowAnswers: ".$this->userAnswer[$i]['address'].' za odgovore tip '.$spremenljivke['tip'].' id '.$spremenljivke['id'].' usr '.$usr_id.'</br>';		
					#priprava odgovora respondenta #######################################################################################
					if($export_format == 'pdf'){
						$answer = "\\textcolor{crta}{".$this->userAnswer[$i]['lat'].", ".$this->userAnswer[$i]['lng']."}";
					}else if($export_format == 'rtf'){
						$answer = " ".$this->userAnswer[$i]['lat'].", ".$this->userAnswer[$i]['lng']." ";
					}
					$lat = $this->userAnswer[$i]['lat'];
					$lng = $this->userAnswer[$i]['lng'];
					if($this->userAnswer[$i]['address']){	//ce je prisoten tudi podatek o naslovu, ga dodaj
						if($export_format == 'pdf'){
							$answer .= ", \\textcolor{crta}{".$this->userAnswer[$i]['address']."}";
						}else if($export_format == 'rtf'){
							$answer .= ", ".$this->userAnswer[$i]['address']." ";
						}
					}					
					if($this->userAnswer[$i]['text']&&!is_numeric($this->userAnswer[$i]['text'])){	//ce je prisoten tudi podatek 'text' (kjer je po navadi odgovor na podvprasanje) in ta ni stevilo, ga dodaj
						$answer .= $texNewLine;
						$answer .= $lang['srv_export_marker_podvpr_answer'].": \\textcolor{crta}{".$this->userAnswer[$i]['text']."}";
					}
					
					#pridobitev podatkov o obmocjih in podatka o prisotnosti tocke v obmocju
					if(count($obmocjaNaSliki)!=0){	//ce imamo obmocja na sliki
						$izpisiObmocij = 0;
						for($o=0; $o<count($obmocjaNaSliki);$o++){
							
							$this->polyX = array();
							$this->polyY = array();
							
							//pretvori koordinate obmocja
							$this->convertPolyString($coordsObmocijNaSliki[$obmocjaNaSliki[$o]]);
							
							//preveri, ce je trenutna tocka v trenutnem obmocju
							$inside=$this->insidePoly($this->polyX, $this->polyY, $lat, $lng);
							
							if($inside&&$izpisiObmocij==0){
								$answer .= " v ".$obmocjaNaSliki[$o];
								$izpisiObmocij = 1;
								$point[$obmocjaNaSliki[$o]]++;
							}elseif($inside&&$izpisiObmocij!=0){
								$answer .= ", ".$obmocjaNaSliki[$o];
								$point[$obmocjaNaSliki[$o]]++;
							}
						}
						//echo "stevilo tock znotraj obmocja: ".$point["Besedilo"]."</br>";						
					}
					#pridobitev podatkov o obmocjih in podatka o prisotnosti tocke v obmocju - konec
					//echo $answer."</br>";
					
					#priprava odgovora respondenta - konec ##############################################################################
					
					
					//zacetek tabele
					$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
					
					//izpis latex kode za prazen okvir oz. okvir z odgovori respondenta
					$tex .= $this->LatexTextBox($export_format, $textboxHeightOdgovori, $textboxWidthOdgovori, $answer, $textboxAllignment, $noBordersOdgovori);
					
					//zakljucek tabele
					$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
					//$tex .= $texNewLine;
				}
				
				if(count($obmocjaNaSliki)!=0){	//ce imamo obmocja na sliki
					//$tex .= '\par {';
					//$tex .= $texNewLine.$lang['srv_export_respondent_data_heatmap_regions_number'].": ".$texNewLine;
					$tex .= $lang['srv_export_respondent_data_heatmap_regions_number'].": ";
					//$tex .= '\par }';
					for($o=0; $o<count($obmocjaNaSliki);$o++){
						//echo "stevilo tock znotraj obmocja: ".$obmocjaNaSliki[$o]." je ".$point[$obmocjaNaSliki[$o]]."</br>";
						//srv_export_respondent_data_heatmap_regions_number
						
						if($export_format == 'pdf'){
							$answerRegions = $obmocjaNaSliki[$o].": \\textcolor{crta}{".$point[$obmocjaNaSliki[$o]]."}";
						}else if($export_format == 'rtf'){
							$answerRegions = $obmocjaNaSliki[$o].": ".$point[$obmocjaNaSliki[$o]]."";
						}					
						
						//zacetek tabele
						$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
						
						//izpis latex kode za prazen okvir oz. okvir z odgovori respondenta
						$tex .= $this->LatexTextBox($export_format, $textboxHeightOdgovori, $textboxWidthOdgovori, $answerRegions, $textboxAllignment, $noBordersOdgovori);
						
						//zakljucek tabele
						$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
						//$tex .= $texNewLine;
					}

				}
/* 				if($point){	//ce je kaksna tocka v obmocju, izpisi tabelo
					//zacetek tabele
					$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
					
					//izpis latex kode za prazen okvir oz. okvir z odgovori respondenta
					$tex .= $this->LatexTextBox($export_format, $textboxHeightOdgovori, $textboxWidthOdgovori, ': '.$point, $textboxAllignment, $noBordersOdgovori);
					
					//zakljucek tabele
					$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
					$tex .= $texNewLine;
				} */
			}
			
			$tex .= $this->texBigSkip;
			$tex .= $this->texBigSkip;
/* 			$tex .= $texNewLine;
			$tex .= $texNewLine; */
			if($export_format == 'pdf'){	//ce je pdf
				//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
			}else{	//ce je rtf

			}			
			
			return $tex;
		}
	}
	
	
	#funkcija, ki skrbi za pretvorbo stringa koordinat obmocja v polja
	function convertPolyString($polypoints=null){
		$poly = [];
		//$tmpX;
		//$tmpY;
		$j = 0;
		
		$poly = explode(",", $polypoints);
		//echo count($poly);
		
 		for($i=0; $i<count($poly); $i++){
			if($i == 0 || $i%2 == 0){
				$tmpX = (int)$poly[$i];
				//echo "x: ".$tmpX."</br>";
			}else{
				$tmpY = (int)$poly[$i];
				//echo "y: ".$tmpY."</br>";
				array_push($this->polyX, $tmpX);
				array_push($this->polyY, $tmpY);
			}
		}		
		//echo ("dolzina polja za x je: ".count($this->polyX));
		//echo ("dolzina polja za y je: ".count($this->polyY));		
	}
	#funkcija, ki skrbi za pretvorbo stringa koordinat obmocja v polja - konec
	
	#funkcija, ki preveri, ali je dolocena tocka v notranjosti dolocenega obmocja
	function insidePoly($polyX=null, $polyY=null, $pointx=null, $pointy=null) {
		//echo("Za poly: je x: ".$pointx." y: ".$pointy."</br>");		
		$inside = false;
		for ($i = 0, $j = count($polyX) - 1; $i < count($polyX); $j = $i++) {
			//echo $polyX[$i]." ".$polyY[$i]."</br>";
			if((($polyY[$i] > $pointy) != ($polyY[$j] > $pointy)) && ($pointx < ($polyX[$j]-$polyX[$i]) * ($pointy-$polyY[$i]) / ($polyY[$j]-$polyY[$i]) + $polyX[$i]) ) $inside = !$inside;		
		}		
		//echo "inside je: ".$inside."</br>";
		return $inside;
	}
	#funkcija, ki preveri, ali je dolocena tocka v notranjosti dolocenega obmocja - konec
	
}