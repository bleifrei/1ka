<?php
/***************************************
 * Description: Priprava Latex kode za lokacija
 *
 * Vprašanje je prisotno:
 * tip 26
 *
 * Autor: Patrik Pucer
 * Datum: 08/2017
 *****************************************/


class LokacijaLatex extends LatexSurveyElement
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
	protected $texBigSkip = '\bigskip';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new LokacijaLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $usr_id=null, $db_table=null, $export_subtype='', $preveriSpremenljivko=null, $loop_id=null){
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;

                //za podtip izberi lokacijo je treba preverjat posebej, ker se vrstica pri userju vedno kreira ampak brez odgovora
                if($spremenljivke['enota'] == 3){
                        $loop_id_s = $loop_id == null ? " IS NULL" : " = '".$loop_id."'";
                        //ne sme biti negativna stevilka (ker je lahko neodgovor)
                        $qu = "SELECT count(*) as cnt FROM srv_data_map WHERE spr_id='".$spremenljivke['id']."' AND usr_id='$usr_id' AND loop_id $loop_id_s AND (text NOT REGEXP '^[+\-]?[0-9]+$' OR text>=0);";
                        $sql = sisplet_query($qu, 'obj');
                        $userDataPresent = $sql->cnt;
                        //da se kreira $this->userAnswer
                        $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
                }
                else{
                        //preveri, ce je kaj v bazi
                        //$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id);
                        $userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
                        //echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
                }

		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;

			$tex = '';
			
			$textboxWidth = '1';	//sirina okvirja z odgovorom
			$textboxHeight = '5cm';	//visina okvirja z odgovorom
			$odgovorLokacija = "Google Maps";	//odgovor
			$noBorders = 0;
			
			//priprava latex kode za text box dolocene sirine in visine glede na export format z besedilom
			$textboxAllignment = 't';
			//$answerTextBox = $this->LatexTextBox($export_format, $textboxHeight, $textboxWidth, $odgovorLokacija, $textboxAllignment, $noBorders);
			$answerTextBox = $odgovorLokacija;
			
			//$tex .= $answerTextBox;	//izris okvirja z odgovorom
			//parametri tabele
			$parameterTabular = 'l';
			
			//IZRIS
			#ZACETEK TABELE		
			//zacetek tabele
			//$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
			
			$tex .= $answerTextBox;
			
			//zakljucek tabele
			//$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
			#KONEC TABELE
			
			//$tex .= $texNewLine;
			
			if($userDataPresent){	//ce je kaj v bazi, je potrebno izrisati tabelo s koordinatami in naslovom
				$textboxWidthOdgovori = '1';	//sirina okvirja z odgovorom
				$textboxHeightOdgovori = 0;	//visina okvirja z odgovorom
				$noBordersOdgovori = 0;
				//echo "stevilo odgovorov: ".count($this->userAnswer)."</br>";

				for($i=0; $i<count($this->userAnswer);$i++){	
                                    //v primeru, da imamo izberi lokacijo (podtip 3) in brez texta, pomeni, da nimamo odgovora, zato ignoriraj
                                    if(!($spremenljivke['enota'] == 3 && ($this->userAnswer[$i]['text'] == "" || $this->userAnswer[$i]['text'] < 0))){
					//echo "rowAnswers: ".$this->userAnswer[$i]['address'].' za odgovore tip '.$spremenljivke['tip'].' id '.$spremenljivke['id'].' usr '.$usr_id.'</br>';		
					#priprava odgovora respondenta #######################################################################################
					$answer = "\\textcolor{crta}{".$this->userAnswer[$i]['lat'].", ".$this->userAnswer[$i]['lng']."}";
					if($this->userAnswer[$i]['address']){	//ce je prisoten tudi podatek o naslovu, ga dodaj
						$answer .= ", \\textcolor{crta}{".$this->userAnswer[$i]['address']."}";
					}	
					if($this->userAnswer[$i]['text']&&(/*!is_numeric($this->userAnswer[$i]['text']) ||*/ $this->userAnswer[$i]['text'] >= 0)){	//ce je prisoten tudi podatek 'text' (kjer je po navadi odgovor na podvprasanje), ga dodaj
						$answer .= $texNewLine;
						$answer .= $lang['srv_export_marker_podvpr_answer'].": \\textcolor{crta}{".$this->userAnswer[$i]['text']."}";
					}
					//echo $answer;
					#priprava odgovora respondenta - konec #######################################################################################
					
					//zacetek tabele
					$tex .= $this->StartLatexTable($export_format, $parameterTabular, 'tabularx', 'tabular', 1, 1);
					
					//izpis latex kode za prazen okvir oz. okvir z odgovori respondenta
					$tex .= $this->LatexTextBox($export_format, $textboxHeightOdgovori, $textboxWidthOdgovori, $answer, $textboxAllignment, $noBordersOdgovori);
					
					//zakljucek tabele
					$tex .= $this->EndLatexTable($export_format, 'tabularx', 'tabular');
					//$tex .= $texNewLine;
                                    }
				}
			}
			
			if($export_subtype=='q_empty'){
				$tex .= $texNewLine;
				$tex .= $texNewLine;
			}else{
				$tex .= $this->texBigSkip;
				$tex .= $this->texBigSkip;
			}
			
			if($export_format == 'pdf'){	//ce je pdf
				//$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
			}else{	//ce je rtf

			} 
			return $tex;
		}
	}	
}