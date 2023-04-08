<?php
/***************************************
 * Description: Priprava Latex kode za MultiGrid
 *
 * Vprašanje je prisotno:
 * tip 6, 16, 19, 20 z vsemi orientacijami
 *
 * Autor: Patrik Pucer
 * Datum: 07-08/2017
 *****************************************/


define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip
define("RADIO_BTN_SIZE", 0.13);
define("U_SHAPE_WIDTH_U", 4);
define("U_SHAPE_WIDTH_OKVIR", 3.62);
define("U_SHAPE_WIDTH_TEXT_U", 2.2);
define("U_SHAPE_WIDTH_TEXT_OKVIR", 1.81);
define("MAXSTEVILOSTOLPCEV", 21); 	//max Stevilo Stolpcev za prvo vrstico pod Drsnikom, zaradi tezav z izrisom, ce je teh vec kot toliko
define("VAS_SIZE", 0.04); //VAS_SIZE

class MultiGridLatex extends LatexSurveyElement
{
	var $internalCellHeight;
	protected $preveriSpremenljivko;
	protected $skipEmpty;
	protected $skipEmptySub;
	protected $userDataPresent;
	protected $texBigSkip = '\bigskip ';
	protected $texSmallSkip = '\smallskip ';
	public $texNewLine = '\\\\ ';
	protected $exportDataType;
	
	protected $textL = '';
	protected $textR = '';
	protected $textRArray = array();
	protected $textRArrayIndex = array();
	
	protected $textRVreId = array();	//belezi vre_id navpicnih odgovorov, ki so bili izbrani in morajo biti na desni strani povleci-spusti
	protected $navpicniOdgovoriVreId = array();		//belezi vre_id navpicnih odgovorov
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	protected $usr_id = null;
	
	protected $path2ImagesMulti;
	
	protected $language;
	protected $prevod;
   
   public function __construct()
    {
		global $site_path;
        //parent::getGlobalVariables();
		$this->path2ImagesMulti = $site_path.'uploadi/editor/';
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new MultiGridLatex();
    }
	
	#funkcija za izvoz vprasalnika za posameznega respondenta
	public function exportData($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype=''){
		global $lang;
		//echo "exportData";
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
		//$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other, spr_id FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other, spr_id FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red");
		$numRowsSql = mysqli_num_rows($sqlVrednosti);
		
		$sqlStolpciVrednosti = sisplet_query("SELECT id, naslov, vrstni_red, variable, other, part FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
		$numColSql = mysqli_num_rows($sqlStolpciVrednosti);
		
		$tex = '';
		$tex1 = '';
		
		$navpicniOdgovori = array();
		$navpicniOdgovori = [];
		$navpicniOdgovori2 = array();
		$navpicniOdgovori2 = [];
		$vodoravniOdgovori = array();
		$vodoravniOdgovori = [];
		$missingOdgovori = array();
		$missingOdgovori = [];
		
		$odgovoriRespondent = array();
		$odgovoriRespondent = [];
		$odgovoriRespondentTmp = array();
		
	
		$texNewLineAfterTable = $texNewLine." ".$texNewLine." ".$texNewLine;		
		if($spremenljivke['enota']==10){	//ce je image hotspot
			$indeksZaWhile = 1;
			//echo "Indeks je ena ce je hotspot </br>";
		}
		$indeksDvojnaTabela1 = 1;
		$indeksDvojnaTabela2 = 1;
		
		$IndeksZaMissing = 1;
		
		
		
		//pregled vseh odgovorov po stolpcih po $sqlStolpciVrednosti
		while ($colVrednost = mysqli_fetch_assoc($sqlStolpciVrednosti)){			
			if($colVrednost['other'] != 0){
				$stringMissingOdgovor = $colVrednost['naslov'];
				array_push($missingOdgovori, $this->encodeText($stringMissingOdgovor) );	//filanje polja z missing odgovori
			}else{
				$stringTitleCol = $colVrednost['naslov'];
				array_push($vodoravniOdgovori, $this->encodeText($stringTitleCol) );	//filanje polja z vodoravnimi odgovori (po stolpcih)
			}
		}
		//pregled vseh odgovorov po stolpcih po $sqlStolpciVrednosti - konec
		
		#potrebno urediti za prikazovanje podatkov, ce je missing v multi text ali multi number
		if($spremenljivke['tip']==19||$spremenljivke['tip']==20){	//ce je grid stevilk ali besedil
			if(count($missingOdgovori)){	//ce so missing-i
				$spremenljivkeData = array(); //pripravi polja, ki omogoca pobiranje podatkov za missing
				$spremenljivkeData['tip'] = 6;
				$spremenljivkeData['enota'] = 0;
			}
		}
		#potrebno urediti za prikazovanje podatkov, ce je missing v multi text ali multi number - konec
		
		$indeksZaWhile = 1;
		$vNovoVrstico = 1;
	
		//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			$indeksZaWhile = 1;
			//navpicni odgovori
			$stringCell_title = $this->encodeText(( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) );
			
			// še dodamo textbox če je polje other
			$_txt = '';
			if ( $rowVrednost['other'] == 1 ){
				//$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id='".$usr_id."' AND loop_id $loop_id");
				$sqlOtherText = sisplet_query("SELECT * FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND vre_id='".$rowVrednost['id']."' AND usr_id=".$usr_id);
				$row4 = mysqli_fetch_assoc($sqlOtherText);
				$_txt = ' '.$row4['text'];
				//if($_txt!=' '){
					//$stringCell_title .= $_txt.':';
				//}
			}
			//echo "zacetek funkcije </br>";
			//if($spremenljivke['enota']!=10){	//ce ni image hotspot
/* 			if($spremenljivke['enota']!=10&&$indeksZaWhile==1){	//ce ni image hotspot
				$indeksZaWhile = 1;
				echo "Indeks je ena ce ni hotspot </br>";
			} */
			//echo "odgovor: ".$stringCell_title." </br>";
			
			$IndeksZaMissing=1;
			
			$sqlVsehVrednsti = sisplet_query("SELECT id, naslov, other FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY 'vrstni_red'");
			
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
				
				$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $db_table, $rowVrednost, $rowVsehVrednosti, $usr_id,0);
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				
				if((count($missingOdgovori))&&($spremenljivke['tip']==19||$spremenljivke['tip']==20)){	//ce so missing-i
					//echo "missing</br>";
					$sqlUserAnswerMissing = $this->GetUsersDataGrid($spremenljivkeData, $db_table, $rowVrednost, $rowVsehVrednosti, $usr_id,0);
					$userAnswerMissing = mysqli_fetch_assoc($sqlUserAnswerMissing);
					//echo "userAnswer Missing: ".$userAnswerMissing['grd_id'].'</br>';
				}
				//echo "rowVsehVrednosti['id']: ".$rowVsehVrednosti['id'].'</br>';	
				//echo "userAnswer: ".$userAnswer['text'].'</br>';
				//echo "userAnswer: ".$userAnswer['grd_id'].'</br>';
				//echo "rowVrednost['spr_id']: ".$rowVrednost['spr_id'].'</br>';	
				
				if($rowVsehVrednosti['id'] == $userAnswer['grd_id']){	//ce je podatek

					if($indeksDvojnaTabela1==1&&$spremenljivke['enota']==3&&in_array($spremenljivke['tip'], array(6, 16))){	//samo prvic izpisi nadnaslov 1, ce je dvojna tabela
						//naslov prvega dela grida za dvojno tabelo
						$tex1 .= $this->encodeText($spremenljivke['grid_subtitle1']).$texNewLine;
					}
					if($spremenljivke['tip']==6||$spremenljivke['tip']==16){
						if(($vNovoVrstico==1&&$spremenljivke['tip']==16)||$spremenljivke['tip']==6){
							$tex1 .= $texNewLine;
							$stringCell_title .= $_txt.':';
							$tex1 .= $stringCell_title.' ';
							$vNovoVrstico=0;
						}
						if($spremenljivke['tip']==16&&$indeksZaWhile!=1){
							$tex1 .= ', ';
						}
						$tex1 .= ' '.$this->encodeText($rowVsehVrednosti['naslov']);						
						//$tex1 .= ' \\textcolor{crta}{'.$this->encodeText($rowVsehVrednosti['naslov']).'}';
					}	
					//echo "Sprememba indeksa </br>";
					$indeksZaWhile++;
					$podatek = 1;					
					//echo "Zgornji: ".$indeksZaWhile."</br>";
					//echo "Zgornji: ".$indeksZaWhile." za ".$this->encodeText($rowVsehVrednosti['naslov'])."</br>";
					//echo "tex: ".$tex1."</br>";
				}else{
					$podatek = 0;
				}
				
				//echo $this->encodeText($this->userAnswer['text']).'</br>';
				if($spremenljivke['tip']==19||$spremenljivke['tip']==20){	//ce je grid stevilk ali besedil

					//echo "Indeks missing: ".$IndeksZaMissing."</br>";
					if(($IndeksZaMissing > ($numColSql-count($missingOdgovori)))&&(count($missingOdgovori))){							
						//echo "userAnswer Missing je prisoten in njegov grd_id je: ".$userAnswerMissing['grd_id'].'</br>';
						$okvirZOdgovori = $this->getAnswerSymbol($export_format, $fillablePdf, 6, $spremenljivke['grids'], count($missingOdgovori), $userAnswerMissing['grd_id']);						
					}else{
						$okvirZOdgovori = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], count($missingOdgovori),$this->encodeText($userAnswer['text']));						
					}
					
					//$okvirZOdgovori = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], count($missingOdgovori),$this->encodeText($userAnswer['text']));
					//echo "okvirZOdgovori: ".$okvirZOdgovori."</br>";
					array_push($odgovoriRespondent, $okvirZOdgovori);
					
				}
				$indeksDvojnaTabela1++;
				$IndeksZaMissing++;
				//if(($indeksZaWhile!=1)&&in_array($spremenljivke['tip'], array(6, 16))){
				//if($podatek==1&&in_array($spremenljivke['tip'], array(6, 16))&&$vNovoVrstico){
				//if(($podatek==1&&$spremenljivke['tip']==6)||($indeksZaWhile==1&&$spremenljivke['tip']==16)){
				//if(($podatek==1&&$spremenljivke['tip']==6)||($podatek==1&&$indeksZaWhileOuter==1&&$spremenljivke['tip']==16)){				
					//$tex1 .= $texNewLine;
					//$vNovoVrstico=0;
				//}
			}
			//$tex .= $texNewLine;
			$vNovoVrstico=1;
		}
		
		$tex2 = '';
		
		$tex .= $tex1.$tex2;	//zdruzitev obeh delov latex kode
		//echo "tex: ".$tex." za ".$spremenljivke['variable']."</br>";
		if($spremenljivke['tip']==19||$spremenljivke['tip']==20){	//ce je grid stevilk ali besedil
			//echo "stevilo odgovorov: ".count($odgovoriRespondent)."</br>";
			//echo "stevilo odgovorov missing: ".count($userAnswerMissing)."</br>";
			//echo "stevilo odgovorov : ".count($userAnswer)."</br>";
			return $odgovoriRespondent;
		}else{
			return $tex;
		}
		
	}
	#funkcija za izvoz vprasalnika za posameznega respondenta - konec
	
	
	//public function export($spremenljivke, $export_format, $questionText, $fillablePdf, $texNewLine, $export_subtype){
	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype='', $preveriSpremenljivko=null, $skipEmptySub=null, $export_data_type='', $skipEmpty=null, $loop_id=null, $language=null){
		//echo $export_data_type."</br>";

		$this->exportDataType = $export_data_type;
		global $lang;
		
		$this->language = $language;
		
		//preverjanje, ali je prevod
		if($this->language){			
			$this->prevod = 1;
		}else{
			$this->prevod = 0;
		}
		//preverjanje, ali je prevod - konec
		
		$this->preveriSpremenljivko = $preveriSpremenljivko;
		$this->skipEmpty =$skipEmpty;
		$this->skipEmptySub = $skipEmptySub;
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		$this->usr_id = $usr_id;
		
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
		//$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other, spr_id FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other, spr_id FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red");
		$numRowsSql = mysqli_num_rows($sqlVrednosti);		
		
		$sqlStolpciVrednosti = sisplet_query("SELECT id, naslov, vrstni_red, variable, other, part FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY id");
		//$sqlStolpciVrednosti = sisplet_query("SELECT id, naslov, vrstni_red, variable, other, part FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY 'vrstni_red'");
		$numColSql = mysqli_num_rows($sqlStolpciVrednosti);
		
		$tex = '';
		
		if($export_subtype!='q_empty'&&$export_data_type!=1){	//ce ni prazen vprasalnik in izpis ni Dolg, dodaj prazno vrstico
			//$tex .= '\\\\';
		}
		
		$navpicniOdgovori = array();
		$navpicniOdgovori = [];
		$navpicniOdgovori2 = array();
		$navpicniOdgovori2 = [];
		$vodoravniOdgovori = array();
		$vodoravniOdgovori = [];
		$missingOdgovori = array();
		$missingOdgovori = [];
		$userAnswerData = array();
		//$textRVreId = array();
		
		$indeksOdgovorovTextR = 0;
		
		$texNewLineAfterTable = $texNewLine." ".$texNewLine." ".$texNewLine;
		
		$indeksZaWhile = 1;
		$indeksOdgovorov = 0;
		//echo "Funkcija export </br>";
		
		$nacinVnosa = $spremenljivke['ranking_k']; //nacin vnosa 0-Stevilo, 1-Drsnik
		
		//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			//$prop['full'] = ( isset($userAnswer[$rowVrednost['id']]) );

			//$stringTitleRow = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
			
			#ce je respondent odgovarjal v drugem jeziku ########################
			$rowl = $this->srv_language_vrednost($rowVrednost['id']);
			if (strip_tags($rowl['naslov']) != '') $rowVrednost['naslov'] = $rowl['naslov'];
			if (strip_tags($rowl['naslov2']) != '') $rowVrednost['naslov2'] = $rowl['naslov2'];							
			#ce je respondent odgovarjal v drugem jeziku - konec ################			

			$stringTitleRow = $rowVrednost['naslov']; //odgovori na levi strani (za tabela diferencial)
			$stringTitleRow2 = $rowVrednost['naslov2'];	//odgovori na desni strani (za tabela diferencial)

			
			//$naslov = $this->srv_language_vrednost($rowVrednost['id']);
		/* 	echo "prevod: ".$this->prevod." </br>";

			echo "stringTitleRow: ".$stringTitleRow."</br>";
			echo "stringTitleRow2: ".$stringTitleRow2."</br>"; */



			$stringTitleRow = Common::getInstance()->dataPiping($stringTitleRow, $usr_id, $loop_id);
			$stringTitleRow2 = Common::getInstance()->dataPiping($stringTitleRow2, $usr_id, $loop_id);

			array_push($navpicniOdgovori, $this->encodeText($stringTitleRow, $rowVrednost['id']) );	//filanje polja z navpicnimi odgovori (po vrsticah)
			array_push($navpicniOdgovori2, $this->encodeText($stringTitleRow2, $rowVrednost['id']) );	//filanje polja z navpicnimi odgovori2 (po vrsticah)		
			
			if($spremenljivke['enota']==9){		//ce je povleci-spusti
				array_push($this->navpicniOdgovoriVreId, $rowVrednost['id'] );	//filanje polja z vre_id navpicnih odgovorov (po vrsticah), potrebno za povleci-spusti
				//array_push($this->navpicniOdgovoriVreId, $rowVrednost['spr_id'] );	//filanje polja z vre_id navpicnih odgovorov (po vrsticah), potrebno za povleci-spusti
			}

			$indeksZaWhile++;
			//echo "rowVrednost['id']: ".$rowVrednost['id'].'</br>';
			$indeksZaWhileVsehVrednosti = 1;
			$indeksEnaMoznostProtiDrugi = 0; //belezi stevilo odgovorov v eni vrstici za enota=4
			
			$sqlVsehVrednsti = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY 'vrstni_red'");				
			//echo "začne drugi while </br>";
			while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednsti)){
				//$indeksZaWhile = $this->GetUsersDataGrid($spremenljivke, $this->db_table, $rowVrednost, $rowVsehVrednosti, $this->usr_id, 1);
				//$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $db_table, $rowVrednost, $rowVsehVrednosti, $usr_id, 0);
				$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $db_table, $rowVrednost, $rowVsehVrednosti, $usr_id, 0, $this->loop_id);
				
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				//echo "userAnswer: ".$userAnswer['grd_id'].'</br>';
				//echo "userAnswerVreId: ".$userAnswer['vre_id'].'</br>';
				//echo "text: ".$userAnswer['text'].'</br>';
				
/* 				if($spremenljivke['enota']==4&&$userAnswer['grd_id']){
					$userAnswerData[$indeksOdgovorov] = $userAnswer['grd_id'];
					echo "userAnswerData: ".$userAnswerData[$indeksOdgovorov].'</br>';
					//echo 'je 4 </br>';
					$indeksOdgovorov++; */
				if($spremenljivke['enota']==4){	//ce je ena moznost proti drugi
					//echo 'je 4 '.$indeksOdgovorov.'</br>';
					if($userAnswer['grd_id']){	//ce je podatek, ga zabelezi
						$userAnswerData[$indeksOdgovorov] = $userAnswer['grd_id'];
						//echo "userAnswerData s podatkom: ".$userAnswerData[$indeksOdgovorov].'</br>';
						//echo 'je 4 '.$indeksOdgovorov.'</br>';
						$indeksOdgovorov++;
					}else{	//drugace					
						if($indeksEnaMoznostProtiDrugi>=1){
							$userAnswerData[$indeksOdgovorov] = $userAnswer['grd_id'];
							//echo "rabim prazno polje </br>";
							//echo "userAnswerData brez podatka: ".$userAnswerData[$indeksOdgovorov].'</br>';
							//echo 'je 4 '.$indeksOdgovorov.'</br>';
							$indeksOdgovorov++;
						}
						//echo "indeks se spremeni </br>";
						$indeksEnaMoznostProtiDrugi++;
					}
					//echo "indeksEnaMoznostProtiDrugi: ".$indeksEnaMoznostProtiDrugi.'</br>';
					//echo "userAnswerData: ".$userAnswerData[$indeksOdgovorov].'</br>';
				}elseif($spremenljivke['enota']!=4){								
					$userAnswerData[$indeksOdgovorov] = $userAnswer['grd_id'];
					$userAnswerDataText[$indeksOdgovorov] = $userAnswer['text'];
					if($spremenljivke['enota']==9){	//povleci spusti
						$this->textRVreId[$indeksOdgovorov] = $userAnswer['vre_id'];
						if(isset($userAnswer['vre_id'])){
							//$sqlTextRString = 'SELECT naslov FROM srv_vrednost WHERE id='.$userAnswer['vre_id'].' ';
							$sqlTextRString = 'SELECT naslov FROM srv_vrednost WHERE id='.$userAnswer['vre_id'].' AND hidden="0" ';
							//echo $sqlTextRString."</br>";
							$sqlTextR = sisplet_query($sqlTextRString);
							$rowTextR = mysqli_fetch_assoc($sqlTextR);
							//echo $rowTextR['naslov']."</br>";
							//echo $indeksOdgovorovTextR.'</br>';
							
							//$this->textRArray[$indeksOdgovorovTextR] = $rowTextR['naslov'];
							
							//$this->textRArrayIndex[$userAnswer['grd_id']] = array($this->textRArray[$indeksOdgovorovTextR]);
							//echo "this->textRArray indeks ".$userAnswer['grd_id']." ".$this->textRArrayIndex[$userAnswer['grd_id']][$indeksOdgovorovTextR].'</br>';
							
							$this->textRArray[$indeksOdgovorovTextR][$userAnswer['grd_id']]=$rowTextR['naslov'];
							//echo "this->textRArray: ".$this->textRArray[$indeksOdgovorovTextR][$userAnswer['grd_id']].'</br>';							
							//echo "this->textRArray indeks ".$indeksOdgovorov." ".$textRVreId[$indeksOdgovorov].'</br>';
							$indeksOdgovorovTextR++;
						}
					}
					
					$indeksOdgovorov++;				
				}				
				//echo "rowVrednost['spr_id']: ".$rowVrednost['spr_id'].'</br>';
				//echo "rowVrednost['id']: ".$rowVrednost['id'].'</br>';
				//echo "rowVsehVrednosti['id']: ".$rowVsehVrednosti['id'].'</br>';
				if($rowVsehVrednosti['id'] == $userAnswer['grd_id']){
					$indeksZaWhileVsehVrednosti++;
				}
				if($indeksZaWhileVsehVrednosti!=1){
					$userDataPresent = 1;
				}
				//echo "userAnswerData: ".$userAnswerData[$indeksOdgovorov].'</br>';
				//echo "enota: ".$spremenljivke['enota'].'</br>';
				//echo "indeksOdgovorov: ".$indeksOdgovorov.'</br>';
				//$indeksOdgovorov++;
			}
		}
		//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - konec
		
		/* echo "userDataPresent: ".$userDataPresent."</br>";
		echo "preveriSpremenljivko: ".$preveriSpremenljivko."</br>"; */
		
		$this->userDataPresent = $userDataPresent;
		
		if($userDataPresent!=0||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			//echo count($userAnswerData)." za ".$spremenljivke['id']."</br>";
			//pregled vseh odgovorov po stolpcih po $sqlStolpciVrednosti
			while ($colVrednost = mysqli_fetch_assoc($sqlStolpciVrednosti)){				
				if($colVrednost['other'] != 0){
					$stringMissingOdgovor = $colVrednost['naslov'];
					array_push($missingOdgovori, $this->encodeText($stringMissingOdgovor) );	//filanje polja z missing odgovori
				}else{
					#ce je respondent odgovarjal v drugem jeziku ########################
 					$rowl = $this->srv_language_grid($colVrednost['id'],$spremenljivke['id']);							
					if (strip_tags($rowl['naslov']) != '') $colVrednost['naslov'] = $rowl['naslov'];
					#ce je respondent odgovarjal v drugem jeziku - konec ################
					
					$stringTitleCol = $colVrednost['naslov'];
					$stringTitleCol = str_replace('<br />','',$stringTitleCol);	//odstranitev odvecnih </br> iz naslova stolpcev
					$stringTitleCol = Common::getInstance()->dataPiping($stringTitleCol, $usr_id, $loop_id);
					//echo "test: $stringTitleCol </br>";
					array_push($vodoravniOdgovori, $this->encodeText($stringTitleCol) );	//filanje polja z vodoravnimi odgovori (po stolpcih)
				}
			}
			//pregled vseh odgovorov po stolpcih po $sqlStolpciVrednosti - konec
			
			if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){	//ce je dolg izvoz
				//pridobitev ustreznega simbola (ali podatkov) za izris odgovorov
				if($spremenljivke['tip']==6){	//grid radio
					//if($spremenljivke['enota']!=11){	//ce ni VAS
					if($spremenljivke['enota']!=11 && $spremenljivke['enota']!=12){	//ce ni VAS in ni slikovni tip
						$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, 0);
						//$tex .= '{\ChoiceMenu[radio,radiosymbol=\ding{108},name=myGroupOfRadiobuttons]{}{='.$stringTitle.'}}'.$stringTitle.' '.$this->texNewLine;
						//echo "simbol radio grid: ".$symbol."</br>";
					}else{	//drugace, ce je VAS
						//$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, 0, $spremenljivke['enota']);
						//echo "simbol VAS: ".$symbol."</br>";
						//echo "simbol radio grid: ".$spremenljivke['enota']."</br>";
					}
					$internalCellHeight = '1 cm';	//visina praznega okvirja @povleci-spusti
				}else if($spremenljivke['tip']==16){	//grid checkbox
					$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, 0);
					$internalCellHeight = '3 cm'; //visina praznega okvirja @povleci-spusti
				}else if($spremenljivke['tip']==19||$spremenljivke['tip']==20){//multi text	ali multinumber
					if($export_subtype=='q_empty'||$export_subtype=='q_comment'){	//ce je prazen vprasalnik ali vprasalnik s komentarji
						$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], count($missingOdgovori), 0);
						//$internalCellHeight = '3 cm'; //visina praznega okvirja @povleci-spusti
					}else{	//ce je vprasalnik s podatki
						$symbol = $this->exportData($spremenljivke, $export_format, $questionText, $fillablePdf, $texNewLine, $usr_id, $db_table, $export_subtype);
					}
				}
				$this->internalCellHeight = $internalCellHeight;
				//pridobitev ustreznega simbola (ali podatkov) za izris odgovorov - konec	
			}
			
			$fillablePdf = 0;						
			if((($spremenljivke['enota']==0)&&($spremenljivke['tip']==6||$spremenljivke['tip']==16))||($spremenljivke['tip']==19||$spremenljivke['tip']==20)){	//klasicna tabela ali multitext ali multinumber
				
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve					
					if($spremenljivke['tip']==20){ //ce je tip vprasanja stevilo
						if(($nacinVnosa == 0)){	//ce je nacin vnosa Stevilo
							$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerDataText, $export_subtype);						
						}else if($nacinVnosa == 1 && $export_format=='pdf'){	//ce so drsniki in je pdf		
							$tex .= $this->IzrisGridDrsnikov($spremenljivke, $navpicniOdgovori, $export_format, $export_subtype, $missingOdgovori, $userAnswerDataText);
						}else if($nacinVnosa == 1 && $export_format=='rtf'){	//ce so drsniki in je rtf
							$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerDataText, $export_subtype);
						}
					}else{
						$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
					}

				}elseif($export_data_type==0||$export_data_type==2){	//ce je Skrcen izvoz
					//$tex .= "Navaden ali Kratek izvoz ".$texNewLine;
					//echo "export_data_type $export_data_type </br>";
					
 					$navpicniOdgovori2 = 0;					
					//if($spremenljivke['tip']==20){ //ce je tip vprasanja stevilo
					if($spremenljivke['tip']==20 || $spremenljivke['tip']==19){ //ce je tip vprasanja multi stevilo ali multi besedilo
						$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerDataText, $export_subtype);
					}else{	//ce je grid en in vec odgovorov						
						$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2, $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);
						//$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2, $export_format, $fillablePdf, $missingOdgovori, $userAnswerDataText, $export_subtype);
					}
				}
			}elseif($spremenljivke['enota']==1){	//tabela diferencial
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					$navpicniOdgovori2 = 0;
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);					
				}
			}elseif($spremenljivke['enota']==2 || $spremenljivke['enota']==6){	//roleta ali izberite s seznama
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}elseif($spremenljivke['enota']==4){	//ena moznost proti drugi
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					//$tex .= "Navaden ali Kratek izvoz ".$texNewLine;
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}elseif($spremenljivke['enota']==5){	//maxdiff
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					//$tex .= "Navaden ali Kratek izvoz ".$texNewLine;
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}elseif($spremenljivke['enota']==8){	//tabela da/ne
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					//$tex .= "Navaden ali Kratek izvoz ".$texNewLine;
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);
					
				}
			}elseif($spremenljivke['enota']==10){	//image hotspot
				//if($export_subtype=='q_empty'){	//ce je prazen vprasalnik
				//if($export_subtype=='q_empty'||$export_subtype=='q_comment'){	//ce je prazen vprasalnik
				if($export_data_type==1||$export_subtype=='q_empty'||$export_subtype=='q_comment'){	//ce je prazen vprasalnik
					$imageName = $this->getImageName('hotspot', $spremenljivke['id'], 'hotspot_image=');
					$imageNameTest = $this->path2ImagesMulti.$imageName.'.png';	//za preveriti, ali obstaja slikovna datoteka na strezniku
					//echo("za image hot spot grid: ".$imageNameTest."</br>");
					if(filesize($imageNameTest) > 0){
						$image = PIC_SIZE."{".$this->path2ImagesMulti."".$imageName."}";	//priprave slike predefinirane dimenzije			
					}else{
						//$image = 'ni slike';
						$image = $lang['srv_pc_unavailable'];
					}
					
					$tex .= $texNewLine; //prazna vrstica po vprasanju
					$tex .= $image."".$texNewLine; //izris slike
					
					//iz baze poberi imena obmocij
					$sqlHotSpotRegions = sisplet_query("SELECT region_name FROM srv_hotspot_regions WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");		
					
					//izris imen obmocij po $sqlHotSpotRegions
					$tex .= $lang['srv_export_hotspot_regions_names'].': '.$texNewLine;
					while ($rowHotSpotRegions = mysqli_fetch_assoc($sqlHotSpotRegions))
					{
						$tex .= $rowHotSpotRegions['region_name'].''.$texNewLine;
					}
					
					//$tex .= $texNewLine;
					
					//ureditev missing-ov
					if(count($missingOdgovori)!=0){	//ce so missing-i
						$vodoravniOdgovori = $this->AddMissingsToAnswers($vodoravniOdgovori, $missingOdgovori);
					}
					//ureditev missing-ov - konec
					
					
					//izris moznih odgovorov
					$tex .= $lang['srv_drag_drop_answers'].': '.$texNewLine;
					for($i=0; $i<$numColSql; $i++){
						$tex .= $vodoravniOdgovori[$i].$texNewLine;
					}
				}/* else{	//ce je vprasalnik s podatki
					$tex .= $this->exportData($spremenljivke, $export_format, $questionText, $fillablePdf, $texNewLine, $usr_id, $db_table, $export_subtype);
				} */
				if(($export_data_type==1||$export_data_type==0||$export_data_type==2)&&($export_subtype=='q_data'||$export_subtype=='q_data_all')){	//ce je Dolg, Navaden ali Kratek izvoz
					if($export_data_type==1){	//ce je Dolg izvoz
						$tex .= $this->texNewLine;
						$tex .= $lang['srv_respondent_answer'].": ".$this->texNewLine;							
					}
					
					$navpicniOdgovori2 = 0;				
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}

				//prostor po izpisu tabele
				//$tex .= $texNewLine;
				//$tex .= $texNewLine;
				
			}elseif($spremenljivke['enota']==3){	//dvojna tabela
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					$navpicniOdgovori2 = 0;					
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}elseif($spremenljivke['enota']==9){	//povleci spusti

				//if($export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){
				//if($export_data_type==1||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){					
				if($export_data_type==1||$export_subtype=='q_empty'||$export_subtype=='q_comment'||($preveriSpremenljivko&&$export_data_type==1)){
					//$this->exportDataType = $export_data_type;
					
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					$navpicniOdgovori2 = 0;				
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}elseif($spremenljivke['enota']==11){	//VAS
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Navaden ali Kratek izvoz
					$navpicniOdgovori2 = 0;				
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}elseif($spremenljivke['enota']==12){	//slikovni tip
				if($export_data_type==1||($export_subtype=='q_empty'||$export_subtype=='q_comment')){//ce je dolg izvoz ali(prazen vprasalnik ali vpr. s komentarji)
					//izris tabel ustrezne postavitve
					$tex .= $this->IzrisTabeleMultiGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, 0, $symbol, $texNewLine, $texNewLineAfterTable, $export_format, 0, $missingOdgovori, $userAnswerData, $export_subtype);
				}elseif($export_data_type==0||$export_data_type==2){	//ce je Skrcen izvoz
					$navpicniOdgovori2 = 0;				
					$tex .= $this->IzpisOdgovorovGrid($spremenljivke, $numColSql, $numRowsSql, $vodoravniOdgovori, $navpicniOdgovori, $navpicniOdgovori2,  $export_format, $fillablePdf, $missingOdgovori, $userAnswerData, $export_subtype);						
				}
			}
			
 			//$tex .= $texNewLine;
			//$tex .= $this->texBigSkip;
			//$tex .= $this->texBigSkip; 
			
			if($export_format == 'pdf'){	//ce je pdf
				//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
			}else{	//ce je rtf

			}
			return $tex;
		}		
	}
	#konec export funkcije
	
	
	#funkcija, ki skrbi za izpis odgovor za grid vprasanja ########################################################################
	function IzpisOdgovorovGrid($spremenljivke=null, $steviloStolpcev=null, $steviloVrstic=null, $vodoravniOdgovori=null, $navpicniOdgovori=null, $navpicniOdgovori2=null,  $export_format='', $fillablePdf=null, $missingOdgovori=null, $data=null, $export_subtype=''){
		global $lang;
		$skipRow = false;
		$izpis = '';		

		
		#missingi##################
		if(count($missingOdgovori)!=0){	//ce so missingi, jih je potrebno dodati polju z odgovori po stolpcih ($vodoravniOdgovori)
			for($m=0;$m<count($missingOdgovori);$m++){
				array_push($vodoravniOdgovori, $missingOdgovori[$m]);
			}
		}
		#missingi - konec###########

        // TODO: Zanke, ki ne izpisujejo ničesar
		/* foreach($vodoravniOdgovori AS $key => $vodoravniOdgovor){	//za vsak odgovor v vrstici
			echo $vodoravniOdgovor."</br>";
			//echo $key."</br>";
		} */
		//
		/* foreach($data AS $key => $datum){	//za vsak odgovor v vrstici
			echo "Podatek: ".$datum."</br>";
			//echo $key."</br>";
		} */
		
		$z = $j = $i = 0;

		if($spremenljivke['enota']==3){	//dvojna tabela
			//ureditev podnaslovov za izpis
			$podnaslovi = array();
			$podnaslov1 = $spremenljivke['grid_subtitle1'];	//podnaslova @dvojna tabela
			$podnaslov2 = $spremenljivke['grid_subtitle2'];
			
			array_push($podnaslovi,$podnaslov1);
			array_push($podnaslovi,$podnaslov2);
				
			
			#priprava razdeljenih podatkov za izpis odgovorov respondenta za dvojna tabela
			$odgovoriLevo = array();
			$odgovoriDesno = array();
			$preklopPolj = 0;

			$steviloPodatkovVPaketu = $steviloStolpcev/2;
			
			for($iP=0; $iP < count($data); ($iP++)){				
				$paket = array_slice($data, $iP, $steviloPodatkovVPaketu);
				if($preklopPolj == 0){					
					$odgovoriLevo = array_merge($odgovoriLevo, $paket);
					$preklopPolj = 1;
				}else{					
					$odgovoriDesno = array_merge($odgovoriDesno, $paket);			
					$preklopPolj = 0;
				}
				$iP = $iP + $steviloPodatkovVPaketu - 1;
			}

			$steviloStolpcev = $steviloStolpcev/2;
			#priprava razdeljenih podatkov za izpis odgovorov respondenta za dvojna tabela - konec
			
			foreach($podnaslovi AS $indeksPodnaslov => $podnaslov){	//za vsak odgovor v vrstici
				$j=0;	//indeks za podatke
				$i=0;	//indeks za odgovore po stolpcih - vodoravniOdgovori
				$z=0;	//indeks za preverjanje preskakovanja manjkajocih podvprasanj
				$izpis .= '\textbf{'.$podnaslov.'}: '.$this->texNewLine;	//izpis podnaslova
				
				if($indeksPodnaslov == 0){	//ce je leva tabela
					$data = array();
					$data = array_merge($data, $odgovoriLevo);
				}elseif($indeksPodnaslov == 1){ //ce je desna tabela
					$data = array();
					$data = array_merge($data, $odgovoriDesno);
				}

				foreach($navpicniOdgovori AS $key => $navpicniOdgovor){	//za vsak odgovor v vrstici
					//echo $navpicniOdgovor.": ";
					#Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici ###############################
					if($this->skipEmptySub == 1){
						$skipRow = true;
						for($z=$z;$z<($steviloStolpcev*($key+1));$z++){
							if(isset($data[$z])){	//ce je podatek
								$skipRow = false;
							}
							//echo "surov podatek: ".$data[$z]."</br>";
						}
					}
					#Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici - konec #######################
					
					$steviloSlikovnihIkon = 0;	//belezi stevilo slikovnih ikon
					
					if(!$skipRow){						
						if($spremenljivke['enota']!=4){
							$izpis .= $navpicniOdgovor.": ";
						}						
						for($j=$j;$j<($steviloStolpcev*($key+1));$j++){						
							if($i==$steviloStolpcev){
								$i=0;
							}
							if(isset($data[$j])){	//ce je podatek								
								$izpis .= '\\textcolor{crta}{'.$vodoravniOdgovori[$i].'}';
							}
							$i++;							
						}
						$izpis .= $this->texNewLine;
					}
				}
			}

		}else{	//ce ni dvojna tabela
			if($spremenljivke['enota']==4){	//ce je ena proti drugi
				$steviloStolpcev = 1;
			}
			foreach($navpicniOdgovori AS $key => $navpicniOdgovor){	//za vsak odgovor v vrstici
				//echo $navpicniOdgovor.": ";
				#Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici ###############################
				if($this->skipEmptySub == 1){
					$skipRow = true;
					for($z=$z;$z<($steviloStolpcev*($key+1));$z++){
						if(isset($data[$z])){	//ce je podatek
							$skipRow = false;
						}
						//echo "surov podatek: ".$data[$z]."</br>";
					}
				}
				#Ce imamo nastavljeno preskakovanje podvprasanj preverimo ce je kaksen odgovor v vrstici - konec #######################
				
				$steviloSlikovnihIkon = 0;	//belezi stevilo slikovnih ikon
				
				
				
				
				if(!$skipRow){
					//$izpis .= ' \hspace*{0.25\textwidth} ';	//da je indent do 25 % sirine strani
					if($spremenljivke['enota']!=4){
						$izpis .= $navpicniOdgovor.": ";
					}
					$odgovorPrisoten = 0;	//zastavica za ureditev izpisa vejice, ko je vec odgovorov v eni vrstici
					for($j=$j;$j<($steviloStolpcev*($key+1));$j++){
						//echo $j.' ';
						//echo "surov podatek: ".$data[$j]."</br>";
						if($spremenljivke['enota']==4){	//ce je ena proti drugi						
							if($data[$j]==2){
								$odgovorEnaProtiDrugi = '\\textcolor{crta}{'.$navpicniOdgovori2[$key].'}';
							}else{
								$odgovorEnaProtiDrugi = '\\textcolor{crta}{'.$navpicniOdgovor.'}';
							}						
							$izpis .= $odgovorEnaProtiDrugi;
						}/* elseif($spremenljivke['enota']==11){	//ce je VAS
							//echo "surov podatek: ".$data[$j]."</br>";
							//echo "stevilo stolpcev VAS: ".$steviloStolpcev."</br>";
							//$izpis .= "smily ";
							if($data[$j]){
								 //$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, 0, $spremenljivke['enota'], $data[$j]);
								 //$VASNumber = $data[$j];
								 ##########
								 switch ($steviloStolpcev) {
									case 1:
										$VAS = "";
										break;
									case 2:
										$arrayVAS = ['vas3checked', 'vas5checked'];
										break;
									case 3:
										$arrayVAS = ['vas3checked', 'vas4checked', 'vas5checked'];
									   break;
									case 4:
										$arrayVAS = ['vas2checked', 'vas3checked', 'vas5checked', 'vas6checked'];
									   break;
									case 5:
										$arrayVAS = [ 'vas2checked', 'vas3checked', 'vas4checked', 'vas5checked', 'vas6checked'];
										break;
									case 6:
										$arrayVAS = ['vas1checked', 'vas2checked', 'vas3checked', 'vas5checked', 'vas6checked', 'vas7checked'];
										break;
									case 7:
										$arrayVAS = ['vas1checked', 'vas2checked', 'vas3checked', 'vas4checked', 'vas5checked', 'vas6checked', 'vas7checked'];
										break;
								}
	
								 ##########
								 if($steviloStolpcev > 1){
									$VAS = $arrayVAS[($data[$j]-1)];
									$symbol = "\\includegraphics[scale=".VAS_SIZE."]{".$this->path2Images."".$VAS."}";
								 }
								 
								 $izpis .= $symbol;
								// echo "symbol za VAS odgovor: ".$symbol."</br>";
							}
						} elseif($spremenljivke['enota']==12){	//ce je slikovni tip
							if($data[$j]){	//ce je podatek
								$steviloSlikovnihIkon = $data[$j];
								//echo "stevilo slikovnih ikon: ".$steviloSlikovnihIkon."</br>";
								//echo "_______________________</br>";
							}
						}*/else{
							if($i==$steviloStolpcev){
								$i=0;
							}
							/* echo "tip: ".$spremenljivke['tip']."</br>";
							echo "enota: ".$spremenljivke['enota']."</br>"; */
							if(isset($data[$j])){	//ce je podatek
								//echo $vodoravniOdgovori[$i].", ";
								//echo $data[$j].", ";
								if($odgovorPrisoten==0){
									$odgovorPrisoten = 1;						
								}else{
								//}elseif($odgovorPrisoten==1){
									$izpis .= ", ";
								}
								//if($spremenljivke['tip']==20){	//ce je tip vprasanja stevilo								
								if($spremenljivke['tip']==20||$spremenljivke['enota']==11||$spremenljivke['enota']==12){	//ce je tip vprasanja stevilo ALI VAS ALI slikovni tip
									$izpis .= '\\textcolor{crta}{'.$data[$j].'}';
								}else{
									if($spremenljivke['tip']==19){	//ce je tip vprasanja besedilo
										$izpis .= '\\textcolor{crta}{'.$data[$j].'}';
									}else{
										$izpis .= '\\textcolor{crta}{'.$vodoravniOdgovori[$i].'}';
									}
								}							
							}
							$i++;
						}
					}
					if($spremenljivke['enota']!=12){	//ce ni slikovni tip, dodaj novo vrstico		
						$izpis .= $this->texNewLine;
					}
				}
				if($spremenljivke['enota']==12){	//ce je slikovni tip, izpisi ustrezno stevilo simbololov
					$prviOdgovorSlikovniTip = 1;
					//echo "izpis kode: ".$izpis."</br>";
					for($p=0; $p<$steviloSlikovnihIkon; $p++){					
						$izpis .=  ICON_SIZE."{".$this->path2Images."".$this->getCustomRadioSymbol($spremenljivke['id'], $prviOdgovorSlikovniTip)."}";
					}
					$izpis .= $this->texNewLine;
					
				}
			}
		}	//konec, ce ni dvojna tabela	
		
		$izpis .= $this->texNewLine;
  		/* if($spremenljivke['enota']==3){
			echo $izpis;
		} */
		return $izpis;
	}
	#funkcija, ki skrbi za izpis odgovor za grid vprasanja - konec ########################################################################
	
	#funkcija, ki skrbi za izris Grida radio buttonov ali checkboxov za klasicno postavitev tabele ################################
	function IzrisTabeleMultiGrid($spremenljivke=null, $steviloStolpcev=null, $steviloVrstic=null, $vodoravniOdgovori=null, $navpicniOdgovori=null, $navpicniOdgovori2=null, $simbolTex=null, $texNewLine='', $texNewLineAfterTable=null, $typeOfDocument=null, $fillablePdf=null, $missingOdgovori=null, $data=null, $export_subtype=''){
		global $lang;		
		//$this->exportDataType = ;
		$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
		$isCheckBox = 0;
		$enota = $spremenljivke['enota'];
	
		//ce je prevod, naj pobere prevedene razlicice podnaslovov
		$rowl1 = $this->srv_language_grid(1,$spremenljivke['id']);							
		if (strip_tags($rowl1['podnaslov']) != '') $spremenljivke['grid_subtitle1'] = $rowl1['podnaslov'];		
		$rowl2 = $this->srv_language_grid(2,$spremenljivke['id']);							
		if (strip_tags($rowl2['podnaslov']) != '') $spremenljivke['grid_subtitle2'] = $rowl2['podnaslov'];		
		//ce je prevod, naj pobere prevedene razlicice podnaslovov - konec

		$podnaslov1 = $spremenljivke['grid_subtitle1'];	//podnaslova @dvojna tabela
		$podnaslov2 = $spremenljivke['grid_subtitle2'];
		
		$trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0);
		$customColumnLabelOption = ($spremenljivkaParams->get('custom_column_label_option') ? $spremenljivkaParams->get('custom_column_label_option') : 1);	//1 - "vse" labele,  2 - "le koncne"  labele, 3 - "koncne in vmesna"  labele
		
		
		//$radioButtonTex = ($export_format=='pdf'?"{\Large $\ocircle$}" : "\\includegraphics[scale=".RADIO_BTN_SIZE."]{radio}");
		
		//ce je izbrana oblika traku, poberi potrebne parametre spremenljivke ##################
		if($trak == 1){
			//stevilo s katerim se zacenja trak
			$trakStartingNumber = ($spremenljivkaParams->get('diferencial_trak_starting_num') ? $spremenljivkaParams->get('diferencial_trak_starting_num') : 0);
			
			//stevilo naslovov nad trakom
			$trakNumOfTitles = ($spremenljivkaParams->get('trak_num_of_titles') ? $spremenljivkaParams->get('trak_num_of_titles') : 0);
			
			//polje za naslove nad trakom
			$trakTitles = [];
			
			//naslovi nad trakom
			for($i=0; $i<$trakNumOfTitles; $i++){
				$trakTitles[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) ? $spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) : '');
			}			
		}
		//ce je izbrana oblika traku, poberi potrebne parametre spremenljivke - konec ##########
		
		//echo "Grids: ".$spremenljivke['grids']." ";
		//echo "Stevilo stolpcev: ".$steviloStolpcev." ";
		
		//ureditev stevila stolpcev (za parametre tabele in nadaljnji izris) glede na izbrano postavitev #################################################

		//if(($enota == 0||$enota == 3)||$spremenljivke['tip']==19||$spremenljivke['tip']==20){	//klasika ali dvojna tabela ali je multitext ali multinumber
		//if(($enota == 0||$enota == 3 || $enota == 11)||$spremenljivke['tip']==19||$spremenljivke['tip']==20){	//klasika ali dvojna tabela ali je multitext ali multinumber ali VAS
		if(($enota == 0||$enota == 3 || $enota == 11 || $enota == 12)||$spremenljivke['tip']==19||$spremenljivke['tip']==20){	//klasika ali dvojna tabela ali je multitext ali multinumber ali VAS ali slikovni tip
			//if($trak == 0 || $enota == 3 || ($trak == 1 && $spremenljivke['tip'] == 16)){
			if(($trak == 0 || $enota == 3 || ($trak == 1 && $spremenljivke['tip'] == 16))||($spremenljivke['tip']==19||$spremenljivke['tip']==20)){
				$steviloStolpcevParameterTabular = $steviloStolpcev = $steviloStolpcev + 1; //ker je prvi stolpec prazen, je potrebno dodati + 1				
			}elseif($trak == 1 && $enota == 0 && $spremenljivke['tip'] == 6){
				$steviloStolpcevParameterTabular = $steviloStolpcev + 1;
			}
		}elseif($enota == 1){	//diferencial
			if($trak == 0){	//ce ni na traku
				//$steviloStolpcevParameterTabular = $steviloStolpcev = $steviloStolpcev + 2; //ker sta prvi in zadnji stolpec prazna, je potrebno dodati + 2
				if(count($missingOdgovori)!=0){	//ce so missingi
					$steviloStolpcevParameterTabular = $steviloStolpcev + 2; //ker sta prvi in zadnji stolpec prazna, je potrebno dodati + 2
					$steviloStolpcev = $steviloStolpcev + 2 - count($missingOdgovori);
				}else{
					$steviloStolpcevParameterTabular = $steviloStolpcev = $steviloStolpcev + 2; //ker sta prvi in zadnji stolpec prazna, je potrebno dodati + 2
				}
			}else{	//ce je na traku
				$steviloStolpcevParameterTabular = $steviloStolpcev + 2;
				$steviloStolpcev = $steviloStolpcev + 2 + count($missingOdgovori);
			}
		}elseif($enota == 2 || $enota == 6){	//roleta ali izberite s seznama
			$steviloStolpcevParameterTabular = 2; //pri roleti sta potrebna le dva stolpca
			if(count($missingOdgovori)!=0){	//ce so missingi
				$steviloStolpcev = $spremenljivke['grids'] + count($missingOdgovori) + 1; //+1, ker se pri izrisu indeks zacne z 1
			}
		}elseif($enota == 4){	//ena moznost proti drugi
			$steviloStolpcevParameterTabular = 5;
			if(count($missingOdgovori)!=0){
				$steviloStolpcevParameterTabular = $steviloStolpcevParameterTabular+count($missingOdgovori);
				$steviloStolpcev = $steviloStolpcev-count($missingOdgovori);
			}
		}elseif($enota == 5){	//maxdiff
			$steviloStolpcevParameterTabular = 3;
			if(count($missingOdgovori)!=0){
				$steviloStolpcevParameterTabular = $steviloStolpcevParameterTabular+count($missingOdgovori);
				$steviloStolpcev = $steviloStolpcev-count($missingOdgovori);
			}
		}elseif($enota == 8){	//tabela da/ne
			$steviloStolpcevParameterTabular = $steviloStolpcev = 3;
 			if(count($missingOdgovori)!=0){
				$steviloStolpcevParameterTabular = $steviloStolpcevParameterTabular+count($missingOdgovori);
				$steviloStolpcev = $steviloStolpcev+count($missingOdgovori);
			}
		}
		//echo $steviloStolpcev.'</br>';
		//ureditev stevila stolpcev (za parametre tabele in nadaljnji izris) glede na izbrano postavitev - konec #################################################

		//ureditev parametrov za tabelo #################################################################################################
		$parameterTabular = '';
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec in ni "ena moznost proti drugi" ALI je zadnji stolpec (pred missing-i) in je "diferencial" ali "ena moznost proti drugi" z missing-i
			if( ($i == 0 && $enota != 4) || ($i == $spremenljivke['grids']+1 && $enota == 1) || (($i == $spremenljivke['grids']+2 && $enota == 4)&&(count($missingOdgovori)!=0)) )  {
				//$parameterTabular .= ($typeOfDocument == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca
				//if($enota == 0 || $enota == 1 || $enota == 3){ //ce je "klasicna tabela" ali diferencial ali dvojna tabela
				//if($enota == 0 || $enota == 1 || $enota == 3 || $enota == 11){ //ce je "klasicna tabela" ali diferencial ali dvojna tabela ali VAS
				if($enota == 0 || $enota == 1 || $enota == 3 || $enota == 11 || $enota == 12){ //ce je "klasicna tabela" ali diferencial ali dvojna tabela ali VAS ali slikovni tip
					if($enota == 1){	//ce je diferencial
						if($i == 0){	//ce je prvi stolpec
							$parameterTabular .= ($typeOfDocument == 'pdf' ? 'A' : 'l');	//leva poravnava stolpca fiksne sirine
						}elseif($i == $spremenljivke['grids']+1){	//ce je zadnji stolpec
							$parameterTabular .= ($typeOfDocument == 'pdf' ? 'R' : 'r');	//desna poravnava stolpca fiksne sirine
						}
					}else{
						$parameterTabular .= ($typeOfDocument == 'pdf' ? 'A' : 'l');	//leva poravnava stolpca fiksne sirine
					}
				}else{
					$parameterTabular .= ($typeOfDocument == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca prilagojena sirini
				}
			}elseif($i == $spremenljivke['grids']+2 && $enota == 4){	//ce je zadnji stolpec in je "ena moznost proti drugi" brez missing-ov
				$parameterTabular .= ($typeOfDocument == 'pdf' ? 'r' : 'r');	//desna poravnava
			}
			elseif($i == 0 && $enota == 4){	//ce je prvi stolpec in "ena moznost proti drugi"
				//$parameterTabular .= ($typeOfDocument == 'pdf' ? 'r' : 'r');	//desna poravnava stolpca
				$parameterTabular .= ($typeOfDocument == 'pdf' ? 'l' : 'l');	//leva poravnava prvega stolpca
			}elseif($i == (intval($steviloStolpcev/2)) && $enota == 3){	//ce smo na sredini stolpcev in je dvojna tabela
				$parameterTabular .= ($typeOfDocument == 'pdf' ? 'C|' : 'c|');	//sredinska poravnava stolpca
			}elseif($i == ($steviloStolpcev) && $enota == 5){	//ce je zadnji stolpec in je maxdiff
				$parameterTabular .= ($typeOfDocument == 'pdf' ? 'R' : 'r');	//desna za pdf in sredinska poravnava stolpca za rtf
			}else{
				$parameterTabular .= ($typeOfDocument == 'pdf' ? 'C' : 'c');	//sredinska poravnava stolpca
			}
		}
		//ureditev parametrov za tabelo - konec	##########################################################################################
		//echo 'Param: '.$parameterTabular.' enota param: '.$enota.'</br>';				
		
		//izpis tabela
		$tabela = '';
		//echo $enota;
		
		#IZPIS ZA POVLECI SPUSTI
		if($enota == 9){	//ce je povleci spusti
			//za pridobitev informacij o obliki odgovorov na desni strani (0 - okvir, 1 - skatla)
			$display_drag_and_drop_new_look = ($spremenljivkaParams->get('display_drag_and_drop_new_look') ? $spremenljivkaParams->get('display_drag_and_drop_new_look') : 0);
						
			//ureditev missing-ov
			if(count($missingOdgovori)!=0){	//ce so missing-i
				$vodoravniOdgovori = $this->AddMissingsToAnswers($vodoravniOdgovori, $missingOdgovori);
			}
			//ureditev missing-ov - konec
 			
			#pred zacetkom tabel za povleci spusti#######################################################################					
			//prva vrstica pred tabelo z odgovori			
			if($typeOfDocument == 'pdf'){	//ce je pdf
				$tabela .= '\keepXColumns\begin{tabularx}{0.45\textwidth}{C} ';	//izris s tabelo				
				$tabela .= $lang['srv_ranking_available_categories'].': '.$texNewLine;
				$tabela .= '\rule{0.4\textwidth}{0.7 pt} \end{tabularx}';				
			}else{	//ce je rtf
				$tabela .= '\begin{tabular}{l} ';	//izris z enostolpicno tabelo
				$tabela .= $lang['srv_ranking_available_categories'].': '.$texNewLine;	//Razpolozljive kategorije				
				$tabela .= '\hline \end{tabular} ';
			}			
			//prva vrstica pred tabelo z odgovori - konec
			
			#pred zacetkom tabel za povleci spusti - konec ###############################################################

			#tabela s kategorijami odgovorov iz levega okvirja ###########################################################
			$tableCentering = ($typeOfDocument == 'pdf' ? ' \centering ' : '');
			
			$parameterTabularL = 'C';	//parameter za levo tabelo
			
			//zacetek tabele
			if($typeOfDocument == 'pdf'){	//ce je pdf
				$tabela .= $this->StartLatexTable($typeOfDocument, $parameterTabularL, 'tabularx', 'tabular*', 0.45, 0.2);
			}
			
			//argumenti za leve okvirje
			$textboxWidthL = 0.2;
			$textboxHeightL = 0;	//ker mora biti prilagojena visina tekstu damo na 0
			$textboxAllignmentL = 'c';
			
			
			//izris notranjosti leve tabele			
 			//if($this->exportDataType){	//ce je dolg izpis izvoza odgovorov respondenta/respondentov
 			if($this->exportDataType&&$spremenljivke['tip']==6){	//ce je dolg izpis izvoza odgovorov respondenta/respondentov in je tabela en odgovor
				//najdi razlike med poljema, kjer se belezijo vre_id odgovorov iz leve in desni strani @ povleci-spusti				
				$navpicniOdgovori = array();
				$diffArray = array_merge(array_diff($this->navpicniOdgovoriVreId, $this->textRVreId), array_diff($this->textRVreId, $this->navpicniOdgovoriVreId));
				if(count($diffArray)){	//ce je kaksna razlika oz. se je premaknilo odgovore iz leve v desno stran povleci-spusti				
					foreach($diffArray AS $diff){
						if($diff){
							// iz baze preberemo naslove odgovorov za levo stran povleci-spusti		
							//$sqlTextLString = "SELECT naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND id='".$diff."' ORDER BY vrstni_red";
							$sqlTextLString = "SELECT naslov FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND id='".$diff."' AND hidden='0' ORDER BY vrstni_red";
							
							$sqlTextL = sisplet_query($sqlTextLString);
							$rowTextL = mysqli_fetch_assoc($sqlTextL);
							array_push($navpicniOdgovori, $this->encodeText($rowTextL['naslov']));
						}					
					}
				}

				$steviloVrstic = count($navpicniOdgovori);
				if($steviloVrstic==0){	//ce ni odgovorov na levi strani povleci-spusti
					$tabela .= ' \hspace{0.05\textwidth}'; //dodaj nekaj praznega prostora, da bo leva stran poravnana pravilno
				}
			}
			
			for ($i = 1; $i <= $steviloVrstic; $i++){
				
				$textL = $tableCentering.' '.$navpicniOdgovori[$i-1];	//odgovor znotraj okvirja	
				
				//$tabela .= ' \indent ';	//da je okvir premaknjen proti sredini stolpca
				
				if($typeOfDocument == 'rtf'){	//ce je rtf
					$tabela .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
					//$tabela .= '\begin{tabular}{|c|} \hline';	//izris s tabelo z obrobama levo desno in zgoraj	
				}
				//izpis latex kode za okvir z odgovorom
				$tabela .= $this->LatexTextBox($typeOfDocument, $textboxHeightL, $textboxWidthL, $textL, $textboxAllignmentL, 0);
				
				if($typeOfDocument == 'rtf'){	//ce je rtf
					$tabela .= ' \end{tabular}';	//za zakljuciti izris v tabeli
				}else{	//ce je pdf					
					$tabela .= ' '.$this->texBigSkip;
					$tabela .= $texNewLine;	
				}				
			}
			
			//zakljucek tabele
			if($typeOfDocument == 'pdf'){	//ce je pdf
				$tabela .= $this->EndLatexTable($typeOfDocument, 'tabularx', 'tabular*');
			}
			#tabela s kategorijami odgovorov iz levega okvirja - konec ##################################################################
			
			//prostor med tabelama, ki sta sedaj ena pod drugo			
			$tabela .= ' '.$this->texBigSkip;

			#pred zacetkom "desne" tabele za povleci spusti#######################################################################						
			//prva vrstica pred tabelo z odgovori
			if($typeOfDocument == 'pdf'){	//ce je pdf
				$tabela .= '\keepXColumns\begin{tabularx}{0.45\textwidth}{C} ';	//izris s tabelo
				$tabela .= $lang['srv_drag_drop_answers'].': '.$texNewLine;
				$tabela .= '\rule{0.4\textwidth}{0.7 pt} \end{tabularx}';				
			}else{	//ce je rtf
				$tabela .= '\begin{tabular}{l} ';	//izris z enostolpicno tabelo
				//$tabela .= $lang['srv_ranking_available_categories'].': '.$texNewLine;	//Razpolozljive kategorije
				$tabela .= $lang['srv_drag_drop_answers'].': '.$texNewLine;	//Razpolozljive kategorije				
				$tabela .= '\hline \end{tabular} ';
			}			
			//prva vrstica pred tabelo z odgovori - konec
			
			#pred zacetkom "desne" tabele za povleci spusti - konec ###############################################################
			
			#tabela s kategorijami odgovorov iz desnega okvirja  ########################################################################			
 			
	 		$parameterTabularD = 'c';	//parameter za desno tabelo
			
			//argumenti za desne okvirje
			$textboxWidthDV = $textboxWidthDE = 0.2;	//sirina okvirja z vsebino in empty
			$textboxHeightDV = 0;	//ker mora biti prilagojena visina tekstu damo na 0
			$textboxAllignmentDV = 'c';
			$textboxAllignmentDE = 't';	//allignment desnega okvirja, ki je empty
			$uShapeHeight = 1.5;
			if($steviloStolpcev > 9){	//ce je stevilo desnih okvirjev vec kot 9, je potrebno visino okvirja zmanjsati
				$this->internalCellHeight = '0.3 cm';
			}			
			
			for ($i = 0; $i < $steviloStolpcev; $i++){				
				$jeOdgovor = 0;
				$izpisano = 0;
				$textRIzpis = '';
				$textIzpis = '';
				$textNaslovOkvir = '';
				
				if($typeOfDocument == 'pdf'){	//ce je pdf					
					
					if($display_drag_and_drop_new_look == 0){	//ce je oblika okvirja (tekst na vrhu+(prazen okvir spodaj oz. okvir z odgovori))						
						$texSmallSkip = '';	
						$textDV = $vodoravniOdgovori[$i]; //besedilo odgovora v okvirju						
						//izpis latex kode za okvir z odgovorom
						//$textRIzpis .= ' '.$textDV.' '.$this->texNewLine.' \hline';
						
						//$textRIzpis .= ' '.$textDV;
						$textNaslovOkvir .= ' '.$textDV;
						
						//$tabela .= $texNewLine;
						//$tabela .= '\indent ';
					}else{	//drugace, ce je oblika skatle (prazen okvir na vrhu+tekst spodaj)
						$texSmallSkip = $this->texSmallSkip;
					}
					
					if($this->exportDataType){	//ce je dolg izpis izvoza odgovorov respondenta/respondentov
						$textNaslovOkvir = $textNaslovOkvir.$this->texNewLine;
						
						foreach($data AS $key => $datum){
							if(isset($data[$key])){	//ce je izpis latex kode za skatlo z besedilom odgovora									
								if(($i+1)==$datum){	//ce v se nahaja odgovor v tej skatli
									$jeOdgovor = 1;										
									if(!$izpisano){
										foreach($this->textRArray AS $indeksTextRArray => $textR){
											if($this->textRArray[$indeksTextRArray][$datum]){
												$textR = Common::getInstance()->dataPiping($textR, $this->usr_id, $this->loop_id);	
												$textR = $this->encodeText($textR[$datum]);																			
												$textR = $tableCentering.' '.$textR;											
												//$textRIzpis .= '\fbox{\parbox{0.2\textwidth}{'.$textR.'}} '.$texSmallSkip.' '.$this->texNewLine;	//zacetna varianta
												$textIzpis .= ' \vspace{0.5\baselineskip} ';
												$textIzpis .= '\fbox{\parbox{0.2\textwidth}{'.$textR.'}} '.$texSmallSkip.' '.$this->texNewLine;
												//$textRIzpis .= ' \hline \fbox{\parbox{0.2\textwidth}{'.$textR.'}} '.$texSmallSkip.' '.$this->texNewLine;
												$izpisano = 1;												
											}
										}
									}else{
										//$uShapeHeight=$uShapeHeight+1.5;	//
										$uShapeHeight=$uShapeHeight+0.5;	//ureditev visine skatle
									}
								}
							}
						}
						
					}
					
					if(!$jeOdgovor){ //ce ni odgovora
						
						if($display_drag_and_drop_new_look == 0){	//ce je oblika okvirja, izrisi prazen okvir							
							$tabela .= $this->LatexTextGridOfBoxes($typeOfDocument, $uShapeHeight, U_SHAPE_WIDTH_TEXT_U, $textNaslovOkvir, $jeOdgovor);
							//textNaslovOkvir
						}else{	//drugace, ce je skatla, izrisi prazno skatlo
							//izpis latex kode za skatlo
							$tabela .= $this->LatexUShape($typeOfDocument, $this->internalCellHeight, U_SHAPE_WIDTH_U, '');
							//$tabela .= $texNewLine;
						}
					}else{	//drugace, izpisi odgovore
						if($display_drag_and_drop_new_look == 0){	//ce je oblika okvirja							
							$tabela .= $this->LatexTextGridOfBoxes($typeOfDocument, $uShapeHeight, U_SHAPE_WIDTH_TEXT_OKVIR, $textNaslovOkvir, $jeOdgovor, $textIzpis);
						}else{	//drugace, ce je skatla
							$tabela .= $this->LatexTextInUShape($typeOfDocument, $uShapeHeight, U_SHAPE_WIDTH_TEXT_U, $textIzpis);
						}							
					}	
					
					$uShapeHeight = 1.5;
					//$tabela .= $texNewLine;						
					if($display_drag_and_drop_new_look == 1){	//ce je skatla
						//izpis latex kode za okvir (brez obrobe oz. fbox) z odgovorom
						//$tabela .= ' \indent \parbox{0.2\textwidth}{ '.$tableCentering.' '.$vodoravniOdgovori[$i].' } '.$texNewLine;						
						$tabela .= ' \parbox{0.2\textwidth}{ '.$tableCentering.' '.$vodoravniOdgovori[$i].' } '.$texNewLine;
						$tabela .= '\end{tabularx}'; //konec tabele, ki se je zacela pri izpisu skatle
					}
					//echo "TABELA TEX: ".$tabela."</br>";
				}elseif($typeOfDocument == 'rtf'){
					if($i != 0){	//ce ni prvi, dodaj prostor						
						$tabela .= ' '.$this->texBigSkip;
					}
					
					$tabela .= '\begin{tabular}{c} ';	//izris s tabelo brez obrob
					if($this->exportDataType){	//ce je dolg izpis izvoza odgovorov respondenta/respondentov					
						foreach($data AS $key => $datum){
							if(isset($data[$key])){	//ce je izpis latex kode za skatlo z besedilom odgovora									
								if(($i+1)==$datum){	//ce v se nahaja odgovor v tej skatli
									$jeOdgovor = 1;										
									if(!$izpisano){
										foreach($this->textRArray AS $indeksTextRArray => $textR){
											if($this->textRArray[$indeksTextRArray][$datum]){
												$textR = $this->encodeText($textR[$datum]);
												$textR = $tableCentering.' '.$textR;
												$textRIzpis .= '\fbox{\parbox{0.2\textwidth}{'.$textR.'}} '.$this->texNewLine;
												$izpisano = 1;
											}
										}
									}
								}
							}
						}						
					}
					
					if($display_drag_and_drop_new_look == 0){	//ce je oblika okvirja (tekst na vrhu+(prazen okvir spodaj oz. okvir z odgovori))						
						//izpis latex kode za okvir (brez obrobe oz. fbox) z odgovorom
						$tabela .= ' \parbox{0.2\textwidth}{ '.$tableCentering.' '.$vodoravniOdgovori[$i].' } '.$texNewLine;
						if($jeOdgovor){	//ce je odgovor, dodaj crto
							$tabela .= '\rule{40mm}{.1pt} '.$texNewLine;
						}
					}

					if(!$jeOdgovor){ //ce ni odgovora
						//izpis latex kode za okvir brez besedila
						$tabela .= $this->LatexTextBox($typeOfDocument, 0, $textboxWidthDE, '', $textboxAllignmentDE, 0);						
						$tabela .= $texNewLine;					
					}else{	//drugace, izpisi odgovore
						$tabela .= $textRIzpis;
					}

					if($display_drag_and_drop_new_look == 1){	//ce je skatla (prazen okvir na vrhu+tekst spodaj)
						//izpis latex kode za okvir (brez obrobe oz. fbox) z odgovorom
						if($jeOdgovor){	//ce je odgovor, dodaj crto
							$tabela .= '\rule{40mm}{.1pt} '.$texNewLine;
						}
						$tabela .= ' \parbox{0.2\textwidth}{ '.$tableCentering.' '.$vodoravniOdgovori[$i].' } '.$texNewLine;						
					}
					$tabela .= ' \end{tabular}';	//za zakljuciti izris v tabeli 
				}
			}
			#tabela s kategorijami odgovorov iz desnega okvirja - konec ##########################################################################
			
		}		
		#IZPIS ZA POVLECI SPUSTI - KONEC
		
		
		if($enota != 9){	//ce ni povleci spusti		
		
			#ZACETEK MAIN TABELE #########################################################################
			$tabela .= $this->StartLatexTable($typeOfDocument, $parameterTabular, 'tabularx', 'tabular', 1, 1);
			
			#nad prvo vrstico, ampak se vedno v tabeli - naslovi trakov, podnaslovi dvojne tabele ################################################
			if(  ($enota == 3) && ($podnaslov1 || $podnaslov2) ){	//ce je dvojna tabela in sta prisotna podnaslova 
				$tabela .= ' & \multicolumn{'.intval($steviloStolpcev/2).'}{c}{'.$podnaslov1.'} & \multicolumn{'.intval($steviloStolpcev/2).'}{c}{'.$podnaslov2.'} '.$texNewLine;
				//$tabela .= ' & \multicolumn{'.intval($steviloStolpcev/2).'}{c}{'.$podnaslov1.'} & \multicolumn{'.intval($steviloStolpcev/2).'}{c}{'.$podnaslov2.'} ';				
			}elseif($trak == 1 && $spremenljivke['tip'] == 6 && ($enota == 0 || $enota == 1)){	//ce imamo obliko traku, uredi nadnaslove traka
				if($spremenljivke['grids']%$trakNumOfTitles == 0){	//ce je stevilo stolpcev deljivo s trenutnim izbranim stevilom nadnaslovov
					for($i=0; $i<$trakNumOfTitles; $i++){
						$trakTitles[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) ? $spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) : '');
						if($i==0){	//ce je prvi stolpec nadnaslovov
							$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles).'}{l}{'.$trakTitles[$i].'}';

						}elseif( $i==($trakNumOfTitles-1) ){ //ce je zadnji stolpec nadnaslovov
							$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles).'}{r}{'.$trakTitles[$i].'}';
						}else{
							$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles).'}{c}{'.$trakTitles[$i].'}';
						}
					}				
				}elseif($spremenljivke['grids']%$trakNumOfTitles == 2){ //ce po deljenju ostane 2
					for($i=0; $i<$trakNumOfTitles; $i++){
						$trakTitles[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) ? $spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) : '');
						if($i != 0 && $i != ($trakNumOfTitles-1) ){							
							$multiColParameter = intval($spremenljivke['grids']/$trakNumOfTitles);
						}else{
							$multiColParameter = 1 + intval($spremenljivke['grids']/$trakNumOfTitles);
						}
						if($i==0){	//ce je prvi stolpec nadnaslovov
							$tabela .= ' & \multicolumn{'.$multiColParameter.'}{l}{'.$trakTitles[$i].'}';
						}elseif( $i==($trakNumOfTitles-1) ){ //ce je zadnji stolpec nadnaslovov
							$tabela .= ' & \multicolumn{'.$multiColParameter.'}{r}{'.$trakTitles[$i].'}';
						}else{
							$tabela .= ' & \multicolumn{'.$multiColParameter.'}{c}{'.$trakTitles[$i].'}';
						}
					}					
				}else if($trakNumOfTitles == 2){	//ce sta izbrana samo dva nadnaslova
 					if($spremenljivke['grids']%$trakNumOfTitles == 0){
						for($i=0; $i<$trakNumOfTitles; $i++){
							$trakTitles[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) ? $spremenljivkaParams->get('trak_nadnaslov_'.($i+1)) : '');
							if($i==0){	//ce je prvi stolpec nadnaslovov
								$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles).'}{l}{'.$trakTitles[$i].'}';
							}elseif( $i==($trakNumOfTitles-1) ){ //ce je zadnji stolpec nadnaslovov
								$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles).'}{r}{'.$trakTitles[$i].'}';
							}else{
								$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles).'}{c}{'.$trakTitles[$i].'}';
							}
						}
					}else{
						for($i=0; $i<$trakNumOfTitles; $i++){
							if($i==0){	//ce je prvi stolpec nadnaslovov
								$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles + 0.5).'}{l}{'.$trakTitles[$i].'}';
							}else{
								$tabela .= ' & \multicolumn{'.intval($spremenljivke['grids']/$trakNumOfTitles - 0.5).'}{r}{'.$trakTitles[$i].'}';
							}							
						}
					}
				}
				if($enota == 1){ //ce je diferencial tabela
					$tabela .= ' & ';
				}	
				//echo $tabela;
				
				//missingi
 				if(count($missingOdgovori)!=0 && ($enota==0||$enota==1)){	//ce so missingi in (je klasicna tabela ali diferencial)
					for($m=0;$m<count($missingOdgovori);$m++){
						$tabela .= " & ".$missingOdgovori[$m];						
					}
				}				
				//missingi - konec
			}
			#nad prvo vrstico, ampak se vedno v tabeli - konec#######################################################
			
			//echo $steviloStolpcev."</br>";
			
			//ureditev missing-ov za tabela da/ne, klasicna ali dvojna tabela #############################
			if($enota==8){	//ce je tabela da/ne
				if(count($missingOdgovori)!=0){	//ce so missing-i
					$vodoravniOdgovori = $this->AddMissingsToAnswers($vodoravniOdgovori, $missingOdgovori);
				}
			}elseif($enota==0 || $enota==3){	//ce je klasicna ali dvojna tabela
				if(count($missingOdgovori)!=0){	//ce so missing-i
					if($enota==0){	//ce je klasicna tabela
						$vodoravniOdgovori = $this->AddMissingsToAnswers($vodoravniOdgovori, $missingOdgovori);
					}elseif($enota==3){	//ce je dvojna tabela
						for($m=0;$m<count($missingOdgovori);$m++){
							$start = $spremenljivke['grids']+($spremenljivke['grids']+1)*$m;
							array_splice($vodoravniOdgovori,$start,0,$missingOdgovori[$m]);	//v trenutno polje z vodoravnimi odgovori dodaj na dolocenem mestu se missinge
						}
					}
				}
/*  				for($m=0;$m<count($vodoravniOdgovori);$m++){
					echo $vodoravniOdgovori[$m].'</br>';
				} */
			}
			//ureditev missing-ov za tabela da/ne, klasicna ali dvojna tabela - konec #####################

			#prva vrstica tabele ####################################################################################
			$tabela .= $this->LatexPrvaVrsticaMultiGrid($steviloStolpcev, $enota, $trak, $customColumnLabelOption, $spremenljivke, $vodoravniOdgovori, $missingOdgovori);			
			#prva vrstica tabele - konec ##################################################################################

			if($enota!=2 && $enota!=6){	//ce ni roleta in izberite s seznama in ena moznost proti drugi
				$tabela .= $texNewLine;	//skok v drugo vrstico, kjer se zacnejo navpicni odgovori
			}
			
			//echo "koda za tabelo: ".$tabela."</br>";
			//preureditev stevila stolpcev za pravilen izris####################################
			
			if($enota == 1){ //ce je diferencial tabela
				if($trak == 0){	//ce ni na traku
					$steviloStolpcev = $steviloStolpcev - 1 + count($missingOdgovori);
				}else{	//ce je na traku
					$steviloStolpcev = $steviloStolpcev - count($missingOdgovori);
				}			
			}
			
			if($enota == 0 && $trak == 1 && $spremenljivke['tip'] == 6){	//ce je klasicna tabela na traku in je tabela en odgovor
				$steviloStolpcev = $steviloStolpcev + 1;	// +1, ker se ob izrisu zacne z indeksom 1
			}
			
			if(($enota == 0) && count($missingOdgovori)!=0){	//ce je klasicna tabela in so missingi
				//$steviloStolpcev = $steviloStolpcev + count($missingOdgovori);
			}
			
			//echo $steviloStolpcev.'</br>';
			//echo $spremenljivke['grids'].'</br>';
			
			//preureditev stevila stolpcev za pravilen izris - konec ############################
			
			//ureditev missing-ov za roleta in izberite iz seznama ##############################
			if($enota == 2 || $enota == 6){	//roleta ali izberite s seznama
				if(count($missingOdgovori)!=0){	//ce so missing-i
					$vodoravniOdgovori = $this->AddMissingsToAnswers($vodoravniOdgovori, $missingOdgovori);
				}
			}
			//ureditev missing-ov za roleta in izberite iz seznama - konec ######################
						
			#izris vrstic tabele
			$trakStartingNumberTmp = null;		
			//$tabela .= $this->LatexVrsticeMultigrid($steviloVrstic, $typeOfDocument, $enota, $simbolTex, $navpicniOdgovori, $trakStartingNumberTmp, $fillablePdf, $steviloStolpcev, $spremenljivke, $trak, $vodoravniOdgovori, $texNewLine, $navpicniOdgovori2, $missingOdgovori, 0, 0, 0, $data, $export_subtype);
			$tabela .= $this->LatexVrsticeMultigrid($steviloVrstic, $typeOfDocument, $enota, $simbolTex, $navpicniOdgovori, $trakStartingNumberTmp, $fillablePdf, $steviloStolpcev, $spremenljivke, $trak, $vodoravniOdgovori, $texNewLine, $navpicniOdgovori2, $missingOdgovori, 0, 0, 0, $data, $export_subtype, $this->preveriSpremenljivko, $this->userDataPresent, null, $this->exportDataType);
			#izris vrstic tabele - konec
/*  			if($enota==12||$enota==11){
				echo "tabela tex: ".$tabela."</br>";
			} */
			$tabela .= $this->EndLatexTable($typeOfDocument, 'tabularx', 'tabular');	//zakljucek tabele
			//$tabela .= $texNewLineAfterTable;			
			#KONEC MAIN TABELE #########################################################################
		}		
		
		//prostor po izpisu tabele	
		$tabela .= $this->texBigSkip;
		$tabela .= $this->texBigSkip;
		
		
		//izpis tabela - konec
		return $tabela;
	}
	#funkcija, ki skrbi za izris Grida radio buttonov ali checkboxov - konec ################################

	#funkcija, ki skrbi za izris Grida drsnikov ################################
	function IzrisGridDrsnikov($spremenljivke=null, $navpicniOdgovori=null, $export_format='', $export_subtype='', $missingOdgovori=null, $userAnswer=null){
			global $lang;
			$tex = '';
			#############################################################################################################
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
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
			
			//pridobitev naslovov opisnih vmesnih label za kasnejsi izris ##########################################
			if($slider_VmesneDescrLabel){	//ce je potrebno izrisati vmesne opisne labele pod drsnikom
				$descriptiveLabels = [];
				if($slider_DescriptiveLabel_defaults!=0){	//ce so prednalozene opisne labele
					$descriptiveLabels = explode(';',$slider_DescriptiveLabel_defaults_naslov1);
				}else{	//ce so custom opisne labele
					$slider_CustomDescriptiveLabels = '';
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

			
			#IZRIS ODGOVORA IN DRSNIKA V GRID ############################################################################################
			
			foreach($navpicniOdgovori AS $key => $navpicniOdgovor){	//za vsak odgovor v vrstici

				################# izpis s samo eno tabelo, saj zaradi ltablex, to ni vec mozno
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

				//dodamo se en stolpec za prvi stolpec
				//$steviloStolpcevPrvaVrstica = $steviloStolpcevPrvaVrstica + 1;
				$steviloStolpcevPrvaVrstica = $steviloStolpcevPrvaVrstica + 1 + 1;
				
				for($i=0; $i<$steviloStolpcevPrvaVrstica; $i++){
					if($i==0){	//ce je prvi stolpec
						//$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca prilagojena sirini
						$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'A' : 'l');	//leva poravnava stolpca dolocen sirine
					}elseif($i==1){	//ce je drugi stolpec
						$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'X' : 'l');	//leva poravnava stolpca prilagojena sirini						
					}elseif($i==$steviloStolpcevPrvaVrstica-1){	//ce je zadnji stolpec
						$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'R' : 'l');	//desna prilagojena poravnava stolpca
					}else{	//za vse ostale stolpce med tretjim in zadnjim
						$parameterTabularLabelePrvaPod .= ($export_format == 'pdf' ? 'C' : 'c');	//sredinska poravnava
					}
				}
				//ureditev parametrov za tabelo - konec
				//echo "parameterTabularLabelePrvaPod: ".$parameterTabularLabelePrvaPod."</br>";
				
				//zacetek tabele				
				$tex .= ($export_format == 'pdf' ? '{ \begin{tabularx}{1\textwidth}{'.$parameterTabularLabelePrvaPod.'}' : '\begin{tabular}{'.$parameterTabularLabelePrvaPod.'}');
				
				//prva vrstica, ce so opisne labele
				#IZRIS OPISNIH LABEL NAD DRSNIKOM #################################################
				if($slider_MinMaxLabel){

					#Ureditev preklopa barve vrstice \rowcolor[gray]{.9}				
					if($key%2 == 0 && $export_format == 'pdf'){
						$tex .= ' \rowcolor[gray]{.9} ';
					}				
					#Ureditev preklopa barve vrstice - konec
					
					//prvi prazen stolpec
					$tex .= ' & ';

					//labela na levi strani
					$tex .= $this->encodeText($MinLabel);

					//vmesni prazni stolpci
					$steviloPraznihStolpcev = $steviloStolpcevPrvaVrstica - 2 + 1;
					for($p=0; $p<$steviloPraznihStolpcev; $p++){
						if($p==$steviloPraznihStolpcev-2){	//ce je pred-zadnji stolpec
							$tex .= '\multicolumn{2}{c}{'.$this->encodeText($MaxLabel).'}';	//labela na desni v zadnji dveh desnih stolpcih
						}elseif($p==$steviloPraznihStolpcev-1){	//ce je zadnji stolpec

						}else{	//drugace
							$tex .= ' & ';
						}
						
					}

					//labela na desni strani
					//$tex .= $this->encodeText($MaxLabel);

					//v novo vrstico po izrisu opisnih label
					$tex .= $this->texNewLine;
				}	
				#IZRIS OPISNIH LABEL NAD DRSNIKOM - KONEC #########################################
				//prva vrstica, ce so opisne labele - konec
				
				//druga vrstica, ce so opisne labele
				#Ureditev preklopa barve vrstice \rowcolor[gray]{.9}				
				if($key%2 == 0 && $export_format == 'pdf'){
					$tex .= ' \rowcolor[gray]{.9} ';
				}				
				#Ureditev preklopa barve vrstice - konec

				#IZRIS MOZNIH ODGOVOROV							
				$tex .= $navpicniOdgovor;
				#IZRIS MOZNIH ODGOVOROV - KONEC
				
				$tex .= ' & ';
				//echo $userAnswer[$key]."</br>";
				if($export_format == 'pdf'){	//ce je pdf, pokazi drsnike tudi graficno					
					#IZRIS DRSNIKA {dolzina}{pozicija bunkice}####################################
					$steviloStolpcevZaSlider = $steviloStolpcevPrvaVrstica - 1;
					if($slider_handle==0){	//ce je rocaj na drsniku
						if($export_subtype=='q_data'||$export_subtype=='q_data_all'){							
							$pozicijaBunkice = $userAnswer[$key]/$slider_MaxNumLabel;							
						}elseif($export_subtype=='q_empty'||$export_subtype=='q_comment'){
							$pozicijaBunkice=0.5;
						}						
						$tex .= '\multicolumn{'.$steviloStolpcevZaSlider.'}{c}{ \circleSLIDER{0.7\textwidth}{'.$pozicijaBunkice.'}}';	//drsnik z rocajem
					}else{	//drugace, ce ni rocaja
						$tex .= '\multicolumn{'.$steviloStolpcevZaSlider.'}{c}{ \emptySLIDER{0.7\textwidth} }';	//drsnik brez rocaja
					}
					$tex .= $this->texNewLine;	//v novo vrstico po izrisu drsnika
					#IZRIS DRSNIKA {dolzina}{pozicija bunkice} - KONEC ###########################
					//druga vrstica, ce so opisne labele - konec
										
					//tretja vrstica
					#IZRIS PRVE VRSTICE POD DRSNIKOM ##############################################
					#Ureditev preklopa barve vrstice \rowcolor[gray]{.9}				
					if($key%2 == 0 && $export_format == 'pdf'){
						$tex .= ' \rowcolor[gray]{.9} ';
					}				
					#Ureditev preklopa barve vrstice - konec

					//za prazen prvi stolpec
					if($userAnswer[$key]){	//ce je prisoten odgovor
						$tex .= $lang['srv_analiza_frekvence_titleAnswer'].': \\textcolor{crta}{'.$userAnswer[$key].'} ';
					}
					$tex .= ' & ';

					//izris vrstice in stolpcev v tabeli
					$steviloStolpcevPrvaVrstica = $steviloStolpcevPrvaVrstica - 1;
					for($i=0; $i<$steviloStolpcevPrvaVrstica; $i++){
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
							}//elseif($i==$steviloStolpcevPrvaVrstica-1){	//ce je zadnji stolpec
							elseif($i==$steviloStolpcevPrvaVrstica-2){	//ce je pred-zadnji stolpec
								if($slider_MinMaxNumLabelNew==0){
									//$tex .= $slider_MaxNumLabel;
									$tex .= '\multicolumn{2}{c}{'.$slider_MaxNumLabel.'}';
								}
							}elseif($i==$steviloStolpcevPrvaVrstica-1){	//ce je zadnji stolpec

							}else{	//za vse ostale stolpce med prvi in zadnjim
								if($slider_VmesneNumLabel&&$steviloStolpcevPrvaVrsticaOrig<=MAXSTEVILOSTOLPCEV){	//ce so vmesne labele stevilske in je stevilo stolpcev manjsi od maximalnega dovoljenega za ustrezen izris
									$vmesnoStevilo = null;
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
					
					$tex .= $this->texNewLine;	//v novo vrstico po izrisu prve vrstice pod drsnikom
					#IZRIS PRVE VRSTICE POD DRSNIKOM - KONEC ######################################
					//tretja vrstica - konec
					
				}else{	//ce je rtf, napisi le, da je drsnik na tem mestu in stevilko odgovora, ce je izpis podatkov
					if($export_subtype=='q_data'||$export_subtype=='q_data_all'){						
						//$tex .= $lang['srv_number_insert_1'].": ".$userAnswer[$key];
						$tex .= $userAnswer[$key];
					}elseif($export_subtype=='q_empty'||$export_subtype=='q_comment'){						
						$tex .= $lang['srv_number_insert_1'];
					}
				}

				//konec tabele						
				$tex .= ($export_format == 'pdf' ? "\\end{tabularx} }" : "\\end{tabular} \\noindent");
				//echo $tex;	
				################# izpis s samo eno tabelo, saj zaradi ltablex, to ni vec mozno - konec

				#Konec tabele za izris odgovora in drsnika - konec #################################################################################
								
			} //konec foreach
			#IZRIS ODGOVORA IN DRSNIKA V GRID - KONEC ############################################################################################
			
			#ZA ENKRAT TEGA NE POTREBUJEMO, SAJ V GRIDU TEGA TRENUTNO NI
	/* 						#IZRIS DRUGE VRSTICE POD DRSNIKOM - LABELE PODROCIJ ###########################
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
				$tex .= ($export_format == 'pdf' ? '\begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabeleDrugaPod.'}' : '\begin{tabular}{'.parameterTabularLabeleDrugaPod.'}');

				//izris prazne vrstice z graficnimi oznakami label (crta horizontal)
				$tex .= $prazniStolpciZaGraficneOznake;
				//izris prazne vrstice z graficnimi oznakami label (crta horizontal) - konec				
				
				//konec tabele z graficnimi oznakami
				$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular} \\noindent");
				
				$tex .= $texNewLine;				
				
				//zacetek tabele z naslovi label
				$tex .= ($export_format == 'pdf' ? '\begin{tabularx}{0.9\textwidth}{'.$parameterTabularLabeleTretjaPod.'}' : '\begin{tabular}{'.parameterTabularLabeleTretjaPod.'}');
				
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
			#IZRIS DRUGE VRSTICE POD DRSNIKOM - LABELE PODROCIJ ########################### */
			#ZA ENKRAT TEGA NE POTREBUJEMO, SAJ V GRIDU TEGA TRENUTNO NI ##################
			
			#############################################################################################################
			
			//se ena tabela, da naredimo dovolj prostora na koncu vprasanja
			$tex .= ($export_format == 'pdf' ? '\begin{tabularx}{0.9\textwidth}{X}' : '');
			$tex .= $this->texNewLine;			
			$tex .= ($export_format == 'pdf' ? '\end{tabularx}' : '');

			return $tex;
	}
	#funkcija, ki skrbi za izris Grida drsnikov - konec ################################
}