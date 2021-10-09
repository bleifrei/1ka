<?php
/***************************************
 * Description: Priprava Latex kode za Kalkulacija
 *
 * Vprašanje je prisotno:
 * tip 22
 *
 * Autor: Patrik Pucer
 * Datum: 05/2018
 *****************************************/

 define("NAGOVOR_LINE_WIDTH", 0.5);

class KalkulacijaLatex extends LatexSurveyElement
{
	protected $texBigSkip = '\bigskip';
	
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

        return new KalkulacijaLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $fillablePdf=null, $texNewLine='', $export_subtype='', $db_table=null, $usr_id=null){
		
		global $lang;
		$tex = '';
		
		$tex .= $spremenljivke['variable'];
		$tex .= ' = ';
		
		//pridobi potrebne podatke za izpis kalkulacije
		$sqlKalkulacijaString = 'SELECT spr_id,	vre_id, grd_id, operator, number, left_bracket,	right_bracket, vrstni_red FROM srv_calculation WHERE cnd_id =-'.$spremenljivke['id'].' ORDER BY vrstni_red';
		//echo $sqlKalkulacijaString."</br>";
		$sqlKalkulacija = sisplet_query($sqlKalkulacijaString);
		
		while ($rowKalkulacija = mysqli_fetch_assoc($sqlKalkulacija)){
			
			if($export_subtype=='q_empty'||$export_subtype=='q_comment'){	//ce je prazen vprasalnik ali je prazen vprasalnik s komentarji
				
				//operator
				if($rowKalkulacija['vrstni_red']!=1){
					$tex .= ' '.$this->GetOperator($rowKalkulacija['operator']).' ';
				}
				
				
				//levi oklepaj
				//if($rowKalkulacija['left_bracket']!=0){
				for ($i = 1; $i <= $rowKalkulacija['left_bracket']; $i++){
					$tex .= '(';
				}
				//}
				
 				//ime spremenljivke
				if($rowKalkulacija['vre_id']){	//ce potrebujemo poleg imena spremenljivke (Q1, Q2, ...) se podoznako (a, b, c, ...)
					$sqlVariableVrednostVrstniRedString = 'SELECT variable FROM srv_vrednost WHERE id ='.$rowKalkulacija['vre_id'].' ';
					$sqlVariableVrednostVrstniRed = sisplet_query($sqlVariableVrednostVrstniRedString);
					$rowVariableVrednostVrstniRed = mysqli_fetch_assoc($sqlVariableVrednostVrstniRed);
					$imeSpremenljivke = $rowVariableVrednostVrstniRed['variable'];
				}else{
					if($rowKalkulacija['spr_id']>0){
						$rowVariableName = Cache::srv_spremenljivka($rowKalkulacija['spr_id']);	//pridobitev imena spremenljivke iz njenega id
						$imeSpremenljivke = $rowVariableName['variable'];
					}else{
						$imeSpremenljivke = $rowKalkulacija['number'];
					}					
				}				
				$tex .= $imeSpremenljivke;
				//echo "ime variable:".$imeSpremenljivke."</br>";
				//ime spremenljivke - konec
				
				
				//desni oklepaj
				//if($rowKalkulacija['right_bracket']!=0){
				for ($i = 1; $i <= $rowKalkulacija['right_bracket']; $i++){
					//$tex .= $rowKalkulacija['right_bracket'];
					$tex .= ')';
				}
				
				//echo $tex."</br>";
			}				
		}
		
		if($export_subtype=='q_data'||$export_subtype=='q_data_all'){			
			$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
			$userAnswer = mysqli_fetch_array($sqlUserAnswer);
			$tex .= $userAnswer['text'];
		}
		
		//pridobi potrebne podatke za izpis kalkulacije - konec
		
		//echo $tex."</br>";		
		
/* 			$tex .= $texNewLine; */
		$tex .= $texNewLine;
		$tex .= $this->texBigSkip;
		$tex .= $this->texBigSkip;
		
		if($export_format == 'pdf'){	//ce je pdf
			$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
		}else{	//ce je rtf

		}
		
		return $tex;
	}	
}