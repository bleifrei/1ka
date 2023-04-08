<?php
/***************************************
 * Description: Priprava Latex kode za Kvota
 *
 * Vprašanje je prisotno:
 * tip 25
 *
 * Autor: Patrik Pucer
 * Datum: 05/2018
 *****************************************/

 define("NAGOVOR_LINE_WIDTH", 0.5);

class KvotaLatex extends LatexSurveyElement
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

        return new KvotaLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $fillablePdf=null, $texNewLine='', $export_subtype='', $db_table=null, $anketa=null, $usr_id=null){
		
		global $lang;
		$tex = '';
		
		$tex .= $spremenljivke['variable'];
		$tex .= ' = ';
		
		//pridobi potrebne podatke za izpis kvote  	spr_id 	vre_id 	grd_id 	operator 	value 	left_bracket 	right_bracket 	vrstni_red
		$sqlKvotaString = 'SELECT spr_id, vre_id, grd_id, operator, value, left_bracket, right_bracket, vrstni_red FROM srv_quota WHERE cnd_id =-'.$spremenljivke['id'].' ORDER BY vrstni_red';
		//echo $sqlKvotaString."</br>";
		$sqlKvota = sisplet_query($sqlKvotaString);
		
 		while ($rowKvota = mysqli_fetch_assoc($sqlKvota)){
			
			if($export_subtype=='q_empty'||$export_subtype=='q_comment'){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			
				//operator
				if($rowKvota['vrstni_red']!=1){
					$tex .= ' '.$this->GetOperator($rowKvota['operator']).' ';
					//$tex .= $this->GetOperator($rowKvota['operator']);
				}
				
				//levi oklepaj
				for ($i = 1; $i <= $rowKvota['left_bracket']; $i++){
					$tex .= '(';
				}
				
				//vrstni red vrednosti spremenljivke
				$sqlVariableVrednostVrstniRedString = 'SELECT vrstni_red FROM srv_vrednost WHERE id ='.$rowKvota['vre_id'].' ';
				$sqlVariableVrednostVrstniRed = sisplet_query($sqlVariableVrednostVrstniRedString);
				$rowVariableVrednostVrstniRed = mysqli_fetch_assoc($sqlVariableVrednostVrstniRed);
				//echo $rowVariableVrednostVrstniRed['vrstni_red']."</br>";
				
				//ime spremenljivke				
				if($rowKvota['spr_id']>0){	//ce je spr_id vecji od 0
					$rowVariableName = Cache::srv_spremenljivka($rowKvota['spr_id']);	//pridobitev imena spremenljivke iz njenega id
					$tex .= $lang['srv_vprasanje_tip_25'].'('.$rowVariableName['variable'].$this->encodeText('_').$rowVariableVrednostVrstniRed['vrstni_red'].')';
				}else{ //drugace, ko je spr_id manjsi od 0, je negativne vrednosti, so zapisani statusi in tipi odgovorov
					switch ( $rowKvota['spr_id'] ){
						case -1:
							// Kvota po statusu
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_1'].')';
						break;
						case -2:
							// Kvota po statusu
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_2'].')';
						break;
						case -3:
							// Kvota po statusu
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_3'].')';
						break;
						case -4:
							// Kvota po statusu
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_4'].')';
						break;
						case -5:
							// Kvota po statusu
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_5'].')';
						break;
						case -6:
							// Kvota po statusu
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_6'].')';
						break;
						case -7:
							// Kvota po ustreznih odgovorih
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_7'].')';
						break;
						case -8:
							// Kvota po vseh odgovorih
							$tex .= $lang['srv_vprasanje_tip_25'].'('.$lang['srv_quota_status_8'].')';
						break;
					}
				}
				
				//desni oklepaj
				for ($i = 1; $i <= $rowKvota['right_bracket']; $i++){
					$tex .= ')';
				}
				//echo $rowKvota['spr_id']."</br>";
			}				
		}
		
		/*izpis odgovorov respondentov*/
 		if($export_subtype=='q_data'||$export_subtype=='q_data_all'){	//ce je izpis odgovorov respondentov
			$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
			$userAnswer = mysqli_fetch_array($sqlUserAnswer);
			$tex .= $userAnswer['text'];
		}
		/*izpis odgovorov respondentov - konec*/
		
		//pridobi potrebne podatke za izpis kalkulacije - konec
		
		//echo $tex."</br>";
		
		$tex .= $texNewLine;
		$tex .= $this->texBigSkip;
		$tex .= $this->texBigSkip;
		
		if($export_format == 'pdf'){	//ce je pdf
			$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
		}else{	//ce je rtf

		}
		//echo $tex."</br>";
		return $tex;
	}	
}