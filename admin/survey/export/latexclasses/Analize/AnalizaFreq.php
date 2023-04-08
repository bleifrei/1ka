<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
/* 	include_once('../exportclases/class.pdfIzvozAnalizaFrekvenca.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFunctions.php');
	require_once('../exportclases/class.enka.pdf.php'); */
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor");
	define("M_ANALIZA_FREQUENCY", "frequency");

	define("FNT_FREESERIF", "freeserif");
	define("FNT_FREESANS", "freesans");
	define("FNT_HELVETICA", "helvetica");

	define("FNT_MAIN_TEXT", FNT_FREESANS);
	define("FNT_QUESTION_TEXT", FNT_FREESANS);
	define("FNT_HEADER_TEXT", FNT_FREESANS);

	define("FNT_MAIN_SIZE", 10);
	define("FNT_QUESTION_SIZE", 9);
	define("FNT_HEADER_SIZE", 10);

	define("RADIO_BTN_SIZE", 3);
	define("CHCK_BTN_SIZE", 3);
	define("LINE_BREAK", 6);

	define ('PDF_MARGIN_HEADER', 8);
	define ('PDF_MARGIN_FOOTER', 12);
	define ('PDF_MARGIN_TOP', 18);
	define ('PDF_MARGIN_BOTTOM', 18);
	define ('PDF_MARGIN_LEFT', 15);
	define ('PDF_MARGIN_RIGHT', 15);

	define ('MEJA_DOLZINA_VPRASANJA', 132);

/** 
 * @desc Class za generacijo izvoza v Latex
 */

class AnalizaFreq extends LatexAnalysisElement{

	var $anketa;				// trenutna anketa (array)
	var $spremenljivka;		// trenutna spremenljivka
	
	private $headFileName = null;					# pot do header fajla
	
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	protected $tex;
	var $currentStyle;
	
	var $current_loop = 'undefined';
	
	static public $_FILTRED_OTHER = array(); 				# filter za polja drugo
	
	protected $texNewLine = '\\\\ ';
	protected $export_format;
	protected $horizontalLineTex = "\\hline ";
	protected $show_valid_percent;
	protected $texBigSkip = '\bigskip';
	protected $spaceBetweenTables = ' \newline \vspace*{1 cm} \newline';
    
    
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $sprID = null, $loop = null){	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa['id']) ){
			$this->anketa = $anketa;
			$this->spremenljivka = $sprID;
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
            $this->pi['canCreate'] = false;
            
			return false;
		}

        if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id'])){
            $this->anketa['uid'] = $global_user_id;
            SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
        }
        else
            return false;

		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	// SETTERS && GETTERS

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}
	
	
	function displayTableLatex($headFileName=null, $spremenljivka=null, $spid=null, $export_format='', $hideEmpty=null){
		global $site_path;
		global $lang;
		global $global_user_id;
		
		$this->export_format = $export_format;
		$this->hideEmpty = $hideEmpty;
		$tabela = '';
		
		$this->headFileName = $headFileName;

		//TODO: Omenjene datoteke že generiramo v LatexAnalysis in tukaj za vsako vrstico res ni potrebe še enkrat to generirat
        /*
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));

		# polovimo frekvence
		SurveyAnalysis::getFrequencys();

		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();

		
		###
		//SurveyMissingProfiles :: Init($spremenljivka['id'], $global_user_id);
        */
		
		
		####

        //TODO: Spremenljivki se nikjer ne kliče
		//$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		//$line_break = '';

		//echo "Spr tip v Sums: ".$spremenljivka['tip']."</br>";
		
		#prikazujemo v odvisnosti od kategorije spremenljivke
		switch ($spremenljivka['tip']) {
			case 1: # radio - prikjaže navpično					
			case 2: #checkbox  če je dihotomna:
			case 3: # dropdown - prikjaže navpično					
			case 6: # multigrid
			case 4:	# text
			case 7:# variabla tipa »število«
			case 8:	# datum
			case 16: #multicheckbox če je dihotomna:
			case 17: #razvrščanje  če je ordinalna 
			case 18: # vsota 
			case 19: # multitext
			case 20: # multi number
			case 21: # besedilo* 
			case 22: # kalkulacija 
				//self::frequencyVertical($spid);
				$tabela .= self::frequencyVertical($spid, $export_format);
				//$tabela .= $this->spaceBetweenTables;
			break;
			case 5:
				# nagovor
				//pdfIzvozAnalizaSums::sumNagovor($spid,'freq');
				$tabela .= $this->sumNagovor($spid,'freq');
				//$tabela .= $this->spaceBetweenTables;
			break;			
		}
		//echo "</br> Tex celotne tabele: ".$tabela."</br>";
		return $tabela;
	}
	
	
	/** Izriše frekvence v vertikalni obliki z Latex
	 * 
	 * @param unknown_type $spid
	 */
	function frequencyVertical($spid=null, $export_format='') {
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 6;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? 'P|' : 'l|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
			}else{
				$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
			}
			
		}
		//Priprava parametrov za tabelo - konec
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		//$tex .= '\keepXColumns';	//potrebno dodati pred tabelo, da se obdrzi sirina stolpca
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/		
		}
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/
		//prva vrstica tabele
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloOstalihStolpcev."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloOstalihStolpcev."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloOstalihStolpcev.'}{>{\hsize=\dimexpr '.($steviloOstalihStolpcev+1).'\hsize + '.($steviloOstalihStolpcev+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloOstalihStolpcev."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloOstalihStolpcev."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}	


		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//druga vrstica
		$tex .= self::tableHeader();		
		/*Konec naslovnih vrstic*/

		$_answersOther = array();
		
		/* dodamo opcijo kje izrisujemo legendo*/
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);

		/* izpis veljavnih odgovorov*/
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
		
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){
				
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if (($variable['text'] != true && $variable['other'] != true) 
				|| (in_array($spremenljivka['tip'],array(4,8,21,22)))){
					# dodamo ime podvariable
					//if ($_variables_count > 1 && in_array($spremenljivka['tip'],array(2,6,7,16,17,18,19,20,21))) {
					if ($inline_legenda) {
						$tex .= self::outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if ($vAnswer['cnt'] > 0 /*&& $counter < $maxAnswer*/ || true) { # izpisujemo samo tiste ki nisno 0
								if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21))) { // text, number, datum, mtext, mnumber, text* 
									$options['isTextAnswer'] = true;
									# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
									$options['textAnswerExceed'] = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								} else {
									$options['isTextAnswer'] = false;
									$options['textAnswerExceed'] = false;
								}
								//$counter = pdfIzvozAnalizaSums::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								$tex .= $this->outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
						}
						# izpišemo sumo veljavnih
						//$counter = pdfIzvozAnalizaSums::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
						//$tex .= $this->outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
						$tex .= self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						$_Z_MV = !$this->hideEmpty;							
						if($_Z_MV){	//ce je potrebno izpisati tudi manjkajoce
							$tex .= $this->encodeText($lang['srv_anl_missing1']);
						}
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								//$counter = pdfIzvozAnalizaSums::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
								$tex .= $this->outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						//$counter = pdfIzvozAnalizaSums::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
						$tex .= $this->outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					//$counter = pdfIzvozAnalizaSums::outputSumaVertical($counter,$_sequence,$spid,$options);
					$tex .= $this->outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		
		//zaljucek latex tabele z obrobo za prvo tabelo		
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za prvo tabelo - konec
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				$tex .= self::outputOtherAnswers($oAnswers, $parameterTabular, $export_format);
			}
		}
		//echo "tex: ".$tex;
		return $tex;
	}	
	
	function outputSubVariablaVertical($spremenljivka=null,$variable=null,$grid=null,$spid=null,$_options = array()) {
		global $lang;
		$texOutputSubVariablaVertical='';
		$text = array();
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$text[] = $this->encodeText($variable['variable']);
		
		$text[] = $this->encodeText($variable['naslov']);
		
		$text[] = '';
		$text[] = '';
		$text[] = '';
		$text[] = '';
		
		$texOutputSubVariablaVertical .= self::tableRow($text);
		
		return $texOutputSubVariablaVertical;
	}	
	

	 
	function setUserId($usrId=null) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

	function formatNumber($value=null, $digit=0, $sufix=""){
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
	
	function drawLine(){
		$cy = $this->pdf->getY();
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
	}
}
?>