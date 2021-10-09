<?php
/***************************************
 * Description: Priprava Latex kode za datum
 *
 * Vprašanje je prisotno:
 * tip 8
 *
 * Autor: Patrik Pucer
 * Datum: 08/2017
 *****************************************/


class DatumLatex extends LatexSurveyElement
{
	var $internalCellHeight;
	protected $texBigSkip = '\bigskip ';
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

        return new DatumLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype='', $preveriSpremenljivko=null, $loop_id=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//preveri, ce je kaj v bazi
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		//echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
		
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;
			
			// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta			
			$sqlVrednosti = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
			$numRowsSql = mysqli_num_rows($sqlVrednosti);
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			
			$array_others = array();	//polje za drugo, missing, ...	
			
			$tex = '';		
			$textboxWidth = '0.15';	//sirina okvirja z odgovorom
			$textboxHeight = '0.3cm';	//visina okvirja z odgovorom
			$noBorders = 0;
			
			//priprava latex kode za text box dolocene sirine in visine glede na export format brez besedila
			$textboxAllignment = 'c';
			//ureditev polja s podatki trenutnega uporabnika ######################################################
			$rowVrednost = mysqli_fetch_array($sqlVrednosti);					
			//$sqlUserAnswer = sisplet_query("SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
			//$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND vre_id='".$rowVrednost['id']."' ";
			$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ";
			//echo $sqlUserAnswerString;
			$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
			$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
			//echo "userAnswer: ".$userAnswer['text']."</br>";
			//ureditev polja s podatki trenutnega uporabnika - konec ##############################################
			
			//ureditev besedila odgovora respondenta v doloceno barvo
			$besedilo = '\\textcolor{crta}{';			
			$besedilo .= $userAnswer['text'];			
			$besedilo .= '}';
			//ureditev besedila odgovora respondenta v doloceno barvo - konec
			
			if($userAnswer['text']==''){	//ce ni izpisa odgovorov respondentov, priprava izpis s tabelo
				$answerTextBox = $this->LatexTextBox($export_format, $textboxHeight, $textboxWidth, $besedilo, $textboxAllignment, $noBorders);

				//parametri tabele
				//$parameterTabular = 'l';			
				if($export_format == 'pdf'){	//ce je pdf
					$parameterTabular = 'X';
				}else{	//ce je rtf
					$parameterTabular = 'l';
				} 
				
				//IZRIS
				#ZACETEK TABELE		
				//zacetek tabele
				$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
				
				$tex .= $answerTextBox;	//izris znotraj tabele
				
				//zakljucek tabele
				$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
				#KONEC TABELE
				$tex .= $this->texBigSkip;
			}else{	//ce je izpis odgovorov respondentov
				$tex .= $this->texNewLine;
				$tex .= $besedilo;
				//$tex .= " \ ";	//da ni tezave z "there is no line here to end"
				$tex .= $texNewLine;
				$tex .= $texNewLine;
			}

			//priprava missing-ov
			//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
			while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
				if((int)$rowVrednost['other']!=0){	//ce so missing ali drugo
					$array_others[$rowVrednost['id']] = array(
						'naslov'=>$rowVrednost['naslov'],
						'vrstni_red'=>$rowVrednost['vrstni_red'],
						'value'=>$text[$rowVrednost['vrstni_red']],
					);
				}
			}
			$symbol = $this->getAnswerSymbol($export_format, $fillablePdf, $spremenljivke['tip'], $spremenljivke['grids'], 0, 0);	//poberi simbol checkbox za other in missing moznosti odgovora
			//priprava missing-ov - konec
			
			// Izris polj drugo - ne vem...
			if (count($array_others) > 0) {
				$tex .= ' \vspace{0.3cm} ';	//prostor med okvirjem in missing
				$tex .= $texNewLine;
				foreach ($array_others AS $oKey => $other) {
					$tex .= $symbol.' '.$other['naslov'].' ';
					$tex .= $texNewLine;								
				}
				$tex .= $texNewLine;
			}	
			
			//IZRIS - KONEC
			
			return $tex;
		}
	}	
}