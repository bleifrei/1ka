<?php
/***************************************
 * Description: Priprava Latex kode za nagovor
 *
 * Vprašanje je prisotno:
 * tip 5
 *
 * Autor: Patrik Pucer
 * Datum: 09/2017
 *****************************************/

 define("NAGOVOR_LINE_WIDTH", 0.5);

class NagovorLatex extends LatexSurveyElement
{
	var $internalCellHeight;
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

        return new NagovorLatex();
    }
	

	public function export($spremenljivke=null, $export_format='', $questionText='', $fillablePdf=null, $texNewLine='', $export_subtype='', $preveriSpremenljivko=null, $loop_id=null){
		
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//preveri, ce je kaj v bazi
		//$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id);
		$userDataPresent = $this->GetUsersData($db_table, $spremenljivke['id'], $spremenljivke['tip'], $usr_id, $this->loop_id);
		//echo "userDataPresent za spremenljivko".$spremenljivke['id']." je: ".$userDataPresent."</br>";
		
		//if($userDataPresent||$export_subtype=='q_empty'){	//ce je kaj v bazi ali je prazen vprasalnik
		if($userDataPresent||$export_subtype=='q_empty'||$export_subtype=='q_comment'||$preveriSpremenljivko){	//ce je kaj v bazi ali je prazen vprasalnik ali je potrebno pokazati tudi ne odgovorjena vprasanja
			global $lang;
			
			$spremenljivkaParams = new enkaParameters($spremenljivke['params']);
			$nagovorLine = ($spremenljivkaParams->get('nagovorLine') ? $spremenljivkaParams->get('nagovorLine') : 0); //0-Privzeto, 1-Ne, 2-Da
			
			if($nagovorLine!=1){	//CE NI NE
				$tex .= '\rule{\textwidth}{'.NAGOVOR_LINE_WIDTH.'pt}';	//narisi crto pod tekstom
			}
			
/* 			$tex .= $texNewLine;
			$tex .= $texNewLine; */
			$tex .= $this->texBigSkip;
			
			if($export_format == 'pdf'){	//ce je pdf
				$tex .= '\\end{absolutelynopagebreak}';	//zakljucimo environment, da med vprasanji ne bo prelomov strani
			}else{	//ce je rtf

			}
			
			return $tex;
		}
	}	
}