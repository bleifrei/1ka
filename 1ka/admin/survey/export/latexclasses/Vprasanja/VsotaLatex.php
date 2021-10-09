<?php
/***************************************
 * Description: Priprava Latex kode za Vsota
 *
 * Vprašanje je prisotno:
 * tip 18
 *
 * Autor: Patrik Pucer
 * Datum: 09/2017
 *****************************************/


define("PIC_SIZE", "\includegraphics[width=10cm]"); 	//slika sirine 50mm
define("ICON_SIZE", "\includegraphics[width=0.5cm]"); 	//za ikone @ slikovni tip
define("RADIO_BTN_SIZE", 0.13);

class VsotaLatex extends LatexSurveyElement
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

        return new VsotaLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype=null, $preveriSpremenljivko=null, $loop_id=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//preveri, ce je kaj v bazi
		//$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id);
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		//echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
		
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja		
			global $lang;
			
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' AND hidden='0' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);
			
			$tex = '';
			
			//nastavitve iz baze ##########################
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			//nastavitve iz baze - konec ####################
					
			$navpicniOdgovori = array();
			$navpicniOdgovori = [];
			
			$odgovoriRespondenta = array();
			$odgovoriRespondenta = [];
						
			$texNewLineAfterTable = $texNewLine." ".$texNewLine." ".$texNewLine;
			
			$vsota = 0;

			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
			while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
				$stringTitleRow = $rowVrednost['naslov']; //odgovori na levi strani
				array_push($navpicniOdgovori, $this->encodeText($stringTitleRow) );	//filanje polja z navpicnimi odgovori (po vrsticah)	
				
				//ureditev polja s podatki trenutnega uporabnika ######################################################
				//$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
				$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' ");
				$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				//echo "userAnswer: ".$userAnswer['text']." za vre_id: ".$rowVrednost['id']."</br>";
				array_push($odgovoriRespondenta, '\\textcolor{crta}{'.$userAnswer['text'].'}' );	//filanje polja z odgovori respondenta
				//ureditev polja s podatki trenutnega uporabnika - konec ##############################################
				
				$vsota += $userAnswer['text'];	//izracun  sprotne vsote
			}
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti - konec
			
			if($userDataPresent){
				//array_push($odgovoriRespondenta, $vsota );	//filanje polja z vsoto
				array_push($odgovoriRespondenta, '\\textcolor{crta}{'.$vsota.'}' );	//filanje polja z vsoto
			}
			
			
			$tex .= $this->IzrisVsotaTabela($spremenljivke, $numRowsSql, $navpicniOdgovori, $odgovoriRespondenta, $texNewLine, $texNewLineAfterTable, $export_format, 0, $userDataPresent);			
			
/* 			$tex .= ' \break ';
			$tex .= ' \break '; */
			$tex .= $this->texBigSkip;
			$tex .= $this->texBigSkip;
			$tex .= $this->texBigSkip;
			
			if($export_format == 'pdf'){	//ce je pdf
				//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
			}else{	//ce je rtf

			}			
			
			return $tex;
		}
	}
	
	#funkcija, ki skrbi za izris vsote v tabeli ################################
	function IzrisVsotaTabela($spremenljivke=null, $steviloVrstic=null, $navpicniOdgovori=null, $odgovoriRespondenta=null, $texNewLine='', $texNewLineAfterTable=null, $typeOfDocument=null, $fillablePdf=null, $userDataPresent=null){
		global $lang;
		
		//izpis kode tabela
		$tabela = '';
		
		$parameterTabularL = 'rl';	//parametri za tabelo
		
		$textVsota = $this->encodeText($spremenljivke['vsota']);
		
		if($textVsota==''){
			$textVsota = $lang['srv_vsota_text'];
		}
		
		//zacetek tabele
		$tabela .= $this->StartLatexTable($typeOfDocument, $parameterTabularL, 'tabularx', 'tabular*', 0.45, 0.2);
		
		//argumenti za leve okvirje
		$textboxWidthL = 0.2;
		$textboxHeightL = 0;	//ker mora biti prilagojena visina tekstu damo na 0
		$textboxAllignment = 'c';
		//$textboxAllignment = 'l';
		$textboxWidth = 0.1;
		$textboxHeight = '0.2cm';
		$noBorders = 0;
		
		for ($i = 1; $i <= $steviloVrstic; $i++){

			$tabela .= ' '.$navpicniOdgovori[$i-1];	//odgovor pred okvirjem
			
			//izpis latex kode za prazen okvir oz. okvir z odgovori respondenta
			$tabela .= ' & '.$this->LatexTextBox($typeOfDocument, $textboxHeight, $textboxWidth, $odgovoriRespondenta[$i-1], $textboxAllignment, $noBorders);
			//izpis latex kode za okvir z odgovorom oz. okvir z odgovori respondenta
			
			$tabela .= $texNewLine;

			if($i==$steviloVrstic){
				$tabela .= ' \hline ';
			}			
		}
		
		//besedilo in okvir pod crto, kjer je prikazana koncna vsota
		$tabela .= $textVsota.' & '.$this->LatexTextBox($typeOfDocument, $textboxHeight, $textboxWidth, $odgovoriRespondenta[$i-1], $textboxAllignment, $noBorders);
		//$tabela .= $lang['srv_vsota_text'].' & '.$this->LatexTextBox($typeOfDocument, $textboxHeight, $textboxWidth, $odgovoriRespondenta[$i-1], $textboxAllignment, $noBorders);
		
		//zakljucek tabele
		$tabela .= $this->EndLatexTable($typeOfDocument, 'tabularx', 'tabular*');

		//izpis kode tabela - konec
		
		return $tabela;
	}
	#funkcija, ki skrbi za izris vsote v tabeli - konec ################################	
}