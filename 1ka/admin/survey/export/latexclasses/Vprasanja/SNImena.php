<?php
/***************************************
 * Description: Priprava Latex kode za SNImena
 *
 * Vprašanje je prisotno:
 * tip 9, 10, 11, 14, 12, 15, 13
 *
 * Autor: Patrik Pucer
 * Datum: 05/2018
 *****************************************/

 define("NAGOVOR_LINE_WIDTH", 0.5);

class SNImenaLatex extends LatexSurveyElement
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
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new SNImenaLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $fillablePdf=null, $texNewLine='', $export_subtype='', $db_table=null, $anketa=null, $usr_id=null, $loop_id_raw=null){
		
		global $lang;
		$tex = '';
		$textboxHeightL = 0;	//ker mora biti prilagojena visina tekstu damo na 0
		$textboxWidthL = 0.25;		
		$textboxAllignmentL = 'l';
		$noBorders = 0;
		$textVOkvirju = array();
		
		// Ce je spremenljivka v loopu
		//$this->loop_id = $loop_id;
		$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		
		
		if($export_subtype=='q_empty'||$export_subtype=='q_comment'){	//ce je prazen vprasalnik ali komentarji
			$steviloOkvirjev = 5;
			for($i=0; $i<$steviloOkvirjev;$i++){
				array_push($textVOkvirju, '');
			}
		}elseif($export_subtype=='q_data'||$export_subtype=='q_data_all'){
			if ($usr_id){
				$sqlUserAnswerString = "SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivke['id']."' AND usr_id='".$usr_id."' AND loop_id $loop_id ";
				$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
				while($userAnswer = mysqli_fetch_array($sqlUserAnswer)){
					array_push($textVOkvirju, $this->encodeText($userAnswer['text']));
				}
				$steviloOkvirjev=count($textVOkvirju);
			}
		}
		
		foreach($textVOkvirju AS $textOkvir){
			//izpis latex kode za okvir z odgovorom
			if($export_format == 'pdf'){	//ce je pdf
				$textOkvir = '\\textcolor{crta}{'.$textOkvir.'}';
				$tex .= $this->LatexTextBox($export_format, $textboxHeightL, $textboxWidthL, $textOkvir, $textboxAllignmentL, $noBorders);
				$tex .= $texNewLine;
			}elseif($export_format == 'rtf'){
				$tex .= '\begin{tabular}{l} ';	//izris s tabelo brez obrob
				//izpis latex kode za okvir brez besedila oz. z odgovorom respondenta
				$tex .= $this->LatexTextBox($export_format, $textboxHeightL, $textboxWidthL, $textOkvir, $textboxAllignmentL, $noBorders);
				$tex .= ' \end{tabular}';	//za zakljuciti izris v tabeli
			}
		}
		
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