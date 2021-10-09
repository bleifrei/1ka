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
 * @desc Class za generacijo latex
 */

class AnalizaSums extends LatexAnalysisElement{

	var $anketa;				// trenutna anketa (array)
	var $spremenljivka;		// trenutna spremenljivka
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $CID = null;							# class za inkrementalno dodajanje fajlov
	
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
	function __construct ($anketa = null, $sprID = null, $loop = null)
	{	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		//if ( is_numeric($anketa) )
		if ( is_numeric($anketa['id']) )
		{
			//$this->anketa['id'] = $anketa;		
			//$this->anketa['id'] = $anketa['id'];
			$this->anketa = $anketa;
			$this->spremenljivka = $sprID;

			SurveyAnalysis::$setUpJSAnaliza = false;
			

			//SurveyZankaProfiles :: Init($this->anketa['id'], $global_user_id);
			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
			
			//$hideAllSystem = SurveyDataSettingProfiles :: getSetting('hideAllSystem');
			/* $hideAllSystem = SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT');
			echo "hideAllSystem: ".$hideAllSystem."</br>"; */
			//SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT')
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']))
		{
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
	function getFile($fileName='')
	{
		//Close and output PDF document
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}

	
	function displayTableLatex($headFileName='', $spremenljivka=null, $spid=null, $export_format='', $hideEmpty=null){
		global $site_path;
		global $lang;
		global $global_user_id;
		//echo "Spr tip v Sums: ".$spremenljivka['tip']."</br>";
		$export_format = $export_format;
		$this->hideEmpty = $hideEmpty;
		$tabela = '';
		
		$this->headFileName = $headFileName;
		
		#preberemo HEADERS iz datoteke
		//SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		# polovimo frekvence			
		//dump(SurveyAnalysis::getFrequencys());
		//die();
		
		#odstranimo sistemske variable
		//SurveyAnalysis::removeSystemVariables();
		
		###
		//SurveyMissingProfiles :: Init($spremenljivka['id'], $global_user_id);
		
		
		####
		
		//$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		//$line_break = '';

		//echo "Spr tip v Sums: ".$spremenljivka['tip']."</br>";
		switch ($spremenljivka['tip']) {
			case 1:
				# radio - prikaže navpično					
				$tabela .= self::sumVertical($spid,'sums', $export_format);				
			break;
			
			case 2:
				#checkbox  če je dihotomna:
				$tabela .= self::sumVerticalCheckbox($spid,'sums', $export_format);
			break;
			
			case 3:
				# dropdown - prikjaže navpično
				$tabela .= self::sumVertical($spid,'sums', $export_format);
			break;
			
			case 6:
				# multigrid
				$tabela .= self::sumHorizontal($spid,'sums', $export_format);
			break;
			
			case 16:
				#multicheckbox če je dihotomna:
				$tabela .= self::sumMultiHorizontalCheckbox($spid, 'sums', $export_format);
			break;
			
			case 17:
				#razvrščanje ce je ordinalna 
				$tabela .= self::sumHorizontal($spid,'sums', $export_format);
			break;
			
			case 4:	# text
			case 8:	# datum				
				$tabela .= self::sumTextVertical($spid,'sums', $export_format);
			break;
			
			case 21: # besedilo*
				# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
				if ($spremenljivka['cnt_all'] == 1) {
					// če je enodimenzionalna prikažemo kot frekvence
					// predvsem zaradi vprašanj tipa: language, email... 
					$tabela .= self::sumTextVertical($spid,'sums', $export_format);
				} else {
					$tabela .= self::sumMultiText($spid,'sums', $export_format);
				}
			break;

			case 4: # besedilo*
				# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
				if ($spremenljivka['cnt_all'] == 1) {
					// če je enodimenzionalna prikažemo kot frekvence
					// predvsem zaradi vprašanj tipa: language, email... 
					$tabela .= self::sumTextVertical($spid,'sums', $export_format);
				} else {
					$tabela .= self::sumMultiText($spid,'sums', $export_format);
				}
			break;
			
			case 19: # multitext
				$tabela .= self::sumMultiText($spid,'sums', $export_format);
			break;
			
			case 7:
			case 18:
			case 22:
				# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
				$tabela .= self::sumNumberVertical($spid,'sums', $export_format);
			break;
			
			case 20:
				# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
				$tabela .= self::sumMultiNumber($spid,'sums', $export_format);
			break;
			
			case 5:
				# nagovor
				$tabela .= self::sumNagovor($spid,'sums', $export_format);
			break;	

			case 26: # lokacija
				$tabela .= self::sumLokacija($spid,'sums', $export_format);
			break;
			
 			case 27: # heatmap
				$tabela .= self::sumHeatmap($spid, 'sums', $export_format);
			break;
		}
		//echo "</br> Tex celotne tabele: ".$tabela."</br>";
		return $tabela;
	}	

	/** Izriše sumarnik v vertikalni obliki z Latex
	 * 
	 * @param unknown_type $spid
	 */
	function sumVertical($spid=null,$_from=null, $export_format='') {
		//echo "sumVertical </br>";
		global $lang;
		
		$tex = '';
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# dodamo opcijo kje izrisujemo legendo
		$inline_legenda = false;
		//$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'exportFormat' => $export_format);
		
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
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
			}
			
		}
		//Priprava parametrov za tabelo - konec
		
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/
		//prva vrstica tabele
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}		
		
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{>{\hsize=\dimexpr 6\hsize+\arrayrulewidth}X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}	

		//druga vrstica tabele z naslovi stolpcev
		$tex .= $this->tableHeader($export_format);

		//$this->pdf->setFont('','','6');
		
		/*Naslovni vrstici tabele - konec*/
		
		$show_valid_percent = (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;
		$this->show_valid_percent = $show_valid_percent;
	
		$_answersOther = array();
		$sum_xi_fi=0;
		$N = 0;

		$_tmp_for_div = array();
		# izpis veljavnih odgovorov
		if (count($spremenljivka['grids']) > 0){
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				// dodamo dodatne vrstice z labelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['text'] != true && $variable['other'] != true) {


						$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
						//echo "tukaj: $maxAnswer </br>";
						$counter = 0;
						$_kumulativa = 0;

						if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
							foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
								if (/*$vAnswer['cnt'] > 0 &&*/ $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
									// za povprečje
									$xi = $vkey;
									$fi = $vAnswer['cnt'];
									
									$sum_xi_fi += $xi * $fi ;
									$N += $fi;

									if ($vAnswer['cnt'] > 0 || true) {	// izpisujemo samo tiste ki nisno 0									
										//$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
										$tex .= self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);		
									}
								
									// za poznejše računannje odklona
									$_tmp_for_div[] = array('xi'=>$xi, 'fi'=>$fi, 'sequence'=>$_sequence);
								}
								$counter++;
								//echo "stevec: $counter </br>";
							}
							// izpišemo sumo veljavnih
							//$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
							$tex .= self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
							//echo "tex testni: ".$tex."</br>";
						}
						if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
							$_Z_MV = !$this->hideEmpty;							
							if($_Z_MV){	//ce je potrebno izpisati tudi manjkajoce
								$tex .= $this->encodeText($lang['srv_anl_missing1']);
							}
							foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {								
								//echo "iAnswer cnt: ".$iAnswer['cnt']."</br>";
								if ($iAnswer['cnt'] > 0 ) { // izpisujemo samo tiste ki niso 0							
									//$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
									$tex .= self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
									//echo "Invalid: ".$tex."</br>";
									$counter++;
									//echo "stevec: $counter </br>";
								}
							}
							// izpišemo sumo veljavnih
							//$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
							$tex .= self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
						}
						//izpišemo še skupno sumo
						//$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
						$tex .= self::outputSumaVertical($counter,$_sequence,$spid,$options);
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
				}
			}
			//echo "koda: $tex </br>";
		}
		
		//zaljucek latex tabele z obrobo za prvo tabelo		
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za prvo tabelo - konec
		
		/* odklon */
		$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
		
		/* standardna diviacija */
 		$div = 0;
		$sum_pow_xi_fi_avg  = 0;
		foreach ( $_tmp_for_div as $tkey => $_tmp_div_data) {
			$xi = $_tmp_div_data['xi'];
			$fi =  $_tmp_div_data['fi'];
			
			$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
		}
		$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

 
		/* izpis st. odklona in povprecja */
		if ($show_valid_percent == 1 && SurveyAnalysis::$_HEADERS[$spid]['skala'] != 1) {
			$brezHline = 1;
			//zacetek latex tabele za drugo tabelo
			$pdfTable = 'tabularx';
			$rtfTable = 'tabular';
			$pdfTableWidth = 1;
			$rtfTableWidth = 1;
			
			$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
				
			//zacetek latex tabele za drugo tabelo - konec
		
			$text = array();
			
			//$text[] = '';
			//$text[] = '';

			$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje1']);			
			$text[] = $this->encodeText(self::formatNumber($avg,NUM_DIGIT_AVERAGE,''));
			
			$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);
			
			$text[] = $this->encodeText(self::formatNumber($div,NUM_DIGIT_AVERAGE,''));
						
			
			if($export_format == 'pdf'){
				$tex .= "\\cline{3-6}";	//horizontalna vrstica od 3 do 6 celice
				$tex .= "\multicolumn{1}{b}{} &  \multicolumn{1}{B|}{} & ";
				$tex .= $this->tableRow($text, $brezHline)." ";
				$tex .= "\\cline{3-6}";	//horizontalna vrstica od 3 do 6 celice		
			}elseif($export_format == 'xls'){
				$brezHline = 1;				
				$tex .= "\\multicolumn{1}{l}{} &  \\multicolumn{1}{l|}{} & ";
				$tex .= $this->tableRow($text, $brezHline)." ";
			}else{
				$tex .= "\\cline{3-6}";	//horizontalna vrstica od 3 do 6 celice
				$tex .= "\\multicolumn{1}{l}{} &  \\multicolumn{1}{l|}{} & ";
				$tex .= $this->tableRow($text, $brezHline)." ";
				$tex .= "\\cline{3-6}";	//horizontalna vrstica od 3 do 6 celice
			}
			
			//zaljucek latex tabele z obrobo za drugo tabelo
			$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			//zaljucek latex tabele z obrobo za drugo tabelo - konec

		}

		/* izpis tekstovnih odgovorov za polja drugo */
		//echo "štev drugih odgovorov: ".count($_answersOther)."</br>";
  		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				$tex .= self::outputOtherAnswers($oAnswers, $parameterTabular, $export_format);
			}
		}
		

		//echo "Latex tabele: ".$tex."</br>";
		return $tex;
	}

	
	/*Izpis sumarnika za check box z Latex*/
	function sumVerticalCheckbox($spid=null,$_from=null, $export_format='') {
		//echo "sumVerticalCheckbox </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		//TODO: Koda se nikjer ne uporablja
		//$inline_legenda = count ($spremenljivka['grids']) > 1;
		//if ($variable['other'] != '1' && $variable['text'] != '1') {
		//	$_tip =  SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		//	$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala');
		//} else {
		//	$_tip =  $lang['srv_analiza_vrsta_bese'];
		//	$_oblika =  $lang['srv_analiza_oblika_nomi'];
		//}
		
		/* ugotovimo koliko imamo kolon*/
		if (count($spremenljivka['grids']) > 0) 
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_valid_cnt[$gid] = max($_valid_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				$_valid[$gid][$vid] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'];
				$_navedbe[$gid] += SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
			}
		}
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 9;
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
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		//prva vrstica
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));

        if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{8}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
        }
        else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{8}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}	
			
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
				
		//druga vrstica		
		$tex .= " & ".$this->encodeText($lang['srv_analiza_opisne_subquestion1'])." & \multicolumn{5}{c|}{".$this->encodeText($lang['srv_analiza_opisne_units'])."} & \multicolumn{2}{c|}{".$this->encodeText($lang['srv_analiza_opisne_arguments'])."} ".$this->texNewLine;		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//tretja vrstica
		$text = array();		
		$text[] = '';
		$text[] = '';
		$text[] = $this->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_valid']);
		$text[] = $this->encodeText('% - '.$lang['srv_analiza_opisne_valid']);
		$text[] = $this->encodeText($lang['srv_analiza_num_units_valid']);
		$text[] = $this->encodeText('% - '.$lang['srv_analiza_num_units_valid']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = $this->encodeText('%');

		$brezHline = $this->getBrezHline($export_format);
		//echo "notnot: $brezHline </br>";
		
		$tex .= $this->tableRow($text, $brezHline);	//izpis tretje vrstice
		//konec naslovnih vrstic
		
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] as $gid => $grid) {
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				if ($variable['other'] != 1) {
					$_sequence = $variable['sequence'];
					$cssBack = "anl_bck_desc_2 ".($vid == 0 && $gid != 0 ? 'anl_double_bt ' : '');
					
					$text = array();
		
					$text[] = $this->encodeText($variable['variable']);
					$text[] = $this->encodeText($variable['naslov']);				
					
					// Frekvence
					$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					// Veljavni
					$text[] = $this->encodeText((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']));

					// Procent - veljavni					
					$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
					
					$text[] = $this->encodeText(self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					$_max_valid = max ($_max_valid, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
					
					// Ustrezni
					$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					// % Ustrezni
					$valid = (int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
					$valid = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$_percent = ($_max_appropriate > 0 ) ? 100*$valid / $_max_appropriate : 0;
					
					$text[] =  $this->encodeText(self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					
					$text[] =  $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					$_percent = ($_navedbe[$gid] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / $_navedbe[$gid] : 0;
					
					$text[] = $this->encodeText(self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));

					$tex .= $this->tableRow($text, $brezHline);	//izpis vrstic z odgovori
				} else {
					# drugo 
				}
			}
			
			$text = array();
			
			$text[] = '';
			
			$text[] = $this->encodeText($lang['srv_anl_suma_valid']);
			
			$text[] = '';
			
			$text[] = $this->encodeText($_max_valid);
			$text[] = '';			
			
			$text[] = $this->encodeText($_max_appropriate);	
			$text[] = '';
			
			$text[] = $this->encodeText($_navedbe[$gid]);
			
			$text[] = $this->encodeText(self::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));

			$tex .= $this->tableRow($text, $brezHline);	//izpis vrstice SKUPAJ
			
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

		//echo "Latex tabele: ".$tex."</br>";
		return $tex;		
	}
	
	/** Izriše nagovor
	 * 
	 */
	function sumNagovor($spid=null, $_from=null, $export_format='') {
		//echo "sumNagovor</br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		//$_tip = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		//$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala');
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 2;
		
		//$parameterTabular = '';
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				//$parameterTabular .= ($export_format == 'pdf' ? 'b|' : 'l|');
				$parameterTabular .= ($export_format == 'pdf' ? 's|' : 'c|');
			}else if($i == 1){
				$parameterTabular .= ($export_format == 'pdf' ? 'B|' : 'l|');
			}
			else{
				$parameterTabular .= ($export_format == 'pdf' ? 's|' : 'c|');
			}
			
		}
		//Priprava parametrov za tabelo - konec
		
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/
		//prva vrstica tabele
		$tex .= $this->encodeText($spremenljivka['variable'])." & ".$this->encodeText($spremenljivka['naslov'])." ".$this->texNewLine;
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}	

		//$this->pdf->setFont('','','6');
		
		/*Naslovni vrstici tabele - konec*/
/* 		$this->pdf->setFont('','b','6');
		
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true); */
		//echo "Latex tabele: ".$tex."</br>";
		return $tex;
	}
	
	/** Izriše number odgovore v vertikalni obliki z Latex
	 * 
	 * @param unknown_type $spid
	 */
	function sumNumberVertical($spid=null, $_from=null, $export_format='') {
		//echo "sumNumberVertical</br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if ((int)$spremenljivka['enota'] == 0 && SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1) {
			$show_enota = false;
		}
		
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				
				# za povprečje				
				$sum_xi_fi=0;
				$N = 0;
				$div=0;
				$min = null;
				$max = null;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {

						$fi = $_validFreq['cnt'];
						$sum_xi_fi += $xi * $fi ;
						$N += $fi;
						$min = $min != null ? min($min,$xi) : $xi;
						$max = max($max,$xi);
					}
				}

				#povprečje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
				$sum_avg += $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'] = $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'] = $min;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'] = $max;
				
				#standardna diviacija
				$div = 0;
				$sum_pow_xi_fi_avg  = 0;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {
						$fi = $_validFreq['cnt'];
						$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
					}
				}
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
				
				#določimo še polja drugo za kasnejši prikaz
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}		
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 8;
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
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		//prva vrstica
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}


		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//druga vrstica		
		$text = array();
		
		$text[] = '';
		
		if ($show_enota) {
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$text[] = $this->encodeText($lang['srv_analiza_opisne_subquestion1']);;
			} else {
				$text[] = $this->encodeText($lang['srv_analiza_opisne_variable_text1']);
			}
		} else {
			$text[] = '';
		}
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_m']);
		$text[] = $this->encodeText($lang['srv_analiza_num_units']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje1']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_max']);
		
		$brezHline = $this->getBrezHline($export_format);
		$tex .= $this->tableRow($text, $brezHline);
		
/* 		$this->pdf->setFont('','','6');
		//konec naslovnih vrstic */

		$_answersOther = array();
		$_grupa_cnt = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			if (count($spremenljivka['grids']) > 1 && $_grupa_cnt !== 0 && $spremenljivka['tip'] != 6) {
				$grid['new_grid'] = true;
			}

			$_grupa_cnt ++;
			if (count($grid['variables']) > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) {
						$_sequence = $variable['sequence'];
			
						$text = array();
		
						if ($spremenljivka['tip'] != 7 ) {
							$text[] = $this->encodeText($variable['variable']);
						}
						else
							$text[] = '';
					
						if ($show_enota) {
							$text[] = $this->encodeText((count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov']);
						} else {
							$text[] = '';;
						}
						
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
						$text[] = (int)$_approp_cnt[$gid];
						
						$text[] = self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
						
						$text[] = self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),'');
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'];
						$text[] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'];

						$tex .= $this->tableRow($text, $brezHline);
						
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
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
		
		//echo "Latex tabele: ".$tex."</br>";
		return $tex;
	}

	/** Izriše sumarnik v horizontalni obliki za multigrid z Latex
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumHorizontal($spid=null,$_from=null, $export_format='') {
		//echo "sumHorizontal </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();
		$_clmn_cnt = count($spremenljivka['options']);

		# pri razvrščanju dodamo dva polja za povprečje in odklon
		$additional_field = false;
		$add_fld = 0;
		
		if ($spremenljivka['tip'] == 17 || $spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || ($spremenljivka['tip'] == 6 && $spremenljivka['skala'] != 1)) {
			$additional_field = true;
			$add_fld = 2;
		}
		
		# pri radiu in dropdown ne prikazujemo podvprašanj
		$_sub_question_col = 1;
		if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
			$_sub_question_col  = 0;
		}
		
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 7 + count($spremenljivka['options']);
		
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				//$parameterTabular .= ($export_format == 'pdf' ? 'b|' : 'l|');
				$parameterTabular .= ($export_format == 'pdf' ? 's|' : 'c|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
			}else if($i == 1){
				$parameterTabular .= ($export_format == 'pdf' ? 'B|' : 'l|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
			}
			else{
				$parameterTabular .= ($export_format == 'pdf' ? 's|' : 'c|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
			}
			
		}
		//Priprava parametrov za tabelo - konec

		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/* prva vrstica */
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}		{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
/* 		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, 5, $this->encodeText($spremenljivka['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, 5, $this->encodeText($spremenljivka['naslov']), 1, 'L', 0, 1, 0 ,0, true);	 */	
		/* prva vrstica - konec */
		
		/* druga vrstica */
		$steviloPodStolpcev2 = count($spremenljivka['options']) + 1;
		$tex .= " & ".$this->encodeText($lang['srv_analiza_opisne_subquestion1'])." & \multicolumn{".$steviloPodStolpcev2."}{c|}{".$this->encodeText($lang['srv_analiza_opisne_answers'])."} ";
		
/* 		$this->pdf->MultiCell(18, 5, $this->encodeText(''), 1, 'L', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(30, 5, $this->encodeText($lang['srv_analiza_opisne_subquestion']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(72, 5, $this->encodeText($lang['srv_analiza_opisne_answers']), 1, 'C', 0, 0, 0 ,0, true); */
		
		$text = array();
		if ($additional_field){
			$text[] = $this->encodeText($lang['srv_analiza_opisne_valid']);			
			$text[] = $this->encodeText($lang['srv_analiza_num_units']);			
			$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje1']);
			$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);			
		}
		else{	
			$text[] = $this->encodeText($lang['srv_analiza_opisne_valid']);			
			$text[] = $this->encodeText($lang['srv_analiza_num_units']);	
		}
		
		//$tex .= $this->tableRow($text);	//izpis ostalega dela vrstice	$arrayText, $brezHline=0, $brezNoveVrstice=0, $nadaljevanjeVrstice=0
		$brezHline = 1;
		$brezNoveVrstice = 1;
		$nadaljevanjeVrstice = 1;
		$tex .= $this->tableRow($text, $brezHline, $brezNoveVrstice, $nadaljevanjeVrstice);	//izpis ostalega dela vrstice
		
		$tex .= $this->texNewLine;	//nova vrstica
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		/* druga vrstica - konec	*/
		
		/* tretja vrstica */
		$brezHline3 = 1;
		$brezNoveVrstice3 = 1;
		$nadaljevanjeVrstice3 = 1;
		
		$textDynamicCells = array();
		$count = 0;
		$height_title = 0;
		
		if (count($spremenljivka['options']) > 0) {
		
			$singleWidth = round(57 / count($spremenljivka['options']));
			
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 		
				$textDynamicCells[] = $_label;
				//$height_title = ($height_title < $this->getCellHeight($_label, $singleWidth)) ? $this->getCellHeight($_label, $singleWidth) : $height_title;
				$count++;
			}
		}
		
		/*prva prazna stolpca*/
		$textPrazniStolpci = array();
		$steviloPraznihStolpcev = 2;
		for($i=0;$i<$steviloPraznihStolpcev;$i++){
			$textPrazniStolpci[$i] = '';
		}
		$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
		/*prva prazna stolpca - konec*/
		
		$tex .= $this->dynamicCells($textDynamicCells, $count);	//izpis celic z odgovori v stolpcih (npr. Sploh ne velja, ...)
				
		$tex .= " & ".$this->encodeText($lang['srv_anl_suma1']);	//Skupaj
		
		/*zadnji stolpci po Skupaj*/
		if ($additional_field){
			$textPrazniStolpci = array();
			$steviloPraznihStolpcev = 4;
			for($i=0;$i<$steviloPraznihStolpcev;$i++){
				$textPrazniStolpci[$i] = '';
			}
			$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
		}
		else{
			$textPrazniStolpci = array();
			$steviloPraznihStolpcev = 2;
			for($i=0;$i<$steviloPraznihStolpcev;$i++){
				$textPrazniStolpci[$i] = '';
			}
			$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
		}
		/*zadnji stolpci po Skupaj*/
		
		$tex .= $this->texNewLine;
		/* tretja vrstica - konec */
		//konec naslovnih vrstic
		

		#zlopamo skozi gride 
		$podtabela = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				if ($variable['other'] != true) {

					// Ce gre za dvojno tabelo naredimo vrstico s naslovom podtabele
					if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
						
						// Če začnemo z drugo podtabelo izpišemo vrstico z naslovom
						if($podtabela != $grid['part']){
							
							$subtitle = $spremenljivka['double'][$grid['part']]['subtitle'];
							$subtitle = $subtitle == '' ? $lang['srv_grid_subtitle_def'].' '.$grid['part'] : $subtitle;
							
/* 							$this->pdf->setFont('','b','6');
							$this->pdf->MultiCell(180, $height_title, $this->encodeText($subtitle), 1, 'C', 0, 1, 0 ,0, true);
							$this->pdf->setFont('','','6'); */
							$tex .= $this->encodeText($subtitle);
							
							$podtabela = $grid['part'];
						}
					}
				
					if($variable['naslov'] == '')
						$variable['naslov'] = '';
					
					/*$linecount = $this->pdf->getNumLines($this->encodeText($variable['naslov']), 30);
					$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/		

					//ce smo na prelomu strani
/* 					if( ($this->pdf->getY() + $height) > 270){					
						$this->drawLine();			
						$this->pdf->AddPage('P');
						$arrayParams['border'] .= 'T';
					} */
					
/* 					$this->pdf->MultiCell(18, $height, $this->encodeText($variable['variable']), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->MultiCell(30, $height, $this->encodeText($variable['naslov']), 1, 'C', 0, 0, 0 ,0, true); */
					$tex .= $this->encodeText($variable['variable']);
					$tex .= " & ".$this->encodeText($variable['naslov']);
					
					
					# za odklon in povprečje				
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					
					$count = 0;
					$text = array();					
					if (count($spremenljivka['options']) > 0) {
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;  
							
							$text[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'].' ('.self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')';
													
							$count++;
						}
					}
					
					$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)
					
					// suma
					$tex .= " & ".$this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'].' ('.self::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')');
					
					// zamenjano veljavni ustrezni
					if ($additional_field){
/* 						$this->pdf->MultiCell(15, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']), 1, 'C', 0, 0, 0 ,0, true);
						$this->pdf->MultiCell(15, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']), 1, 'C', 0, 0, 0 ,0, true); */
						$tex .= " & ".$this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
						$tex .= " & ".$this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					}
					else{
/* 						$this->pdf->MultiCell(30, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']), 1, 'C', 0, 0, 0 ,0, true);
						$this->pdf->MultiCell(30, $height, $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']), 1, 'C', 0, 1, 0 ,0, true); */
						$tex .= " & ".$this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
						$tex .= " & ".$this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					}
					
					# za odklon in povprečje
					if ($additional_field){
						# odklon
						$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
						#standardna diviacija
						$div = 0;
						$sum_pow_xi_fi_avg  = 0;
						if (count($spremenljivka['options']) > 0) {
							foreach ( $spremenljivka['options'] as $xi => $kategorija) {
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$xi]['cnt'];
								$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
							}
						}
						$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
						
						$tex .= " & ".$this->encodeText(self::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
						$tex .= " & ".$this->encodeText(self::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
					}
					
					/*zakljucek vrstice*/
					$tex .= $this->texNewLine;	//nova vrstica
					if($export_format != 'xls'){
						$tex .= $this->horizontalLineTex; /*horizontalna linija*/
					}
					
				} 
				else {
					# immamo polje drugo
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
		
		//echo "Latex tabele: ".$tex."</br>";
		return $tex;
	}
	
	/** Izriše tekstovne odgovore v vertikalni obliki z Latex
	 * 
	 * @param unknown_type $spid
	 */
	 
	 function sumTextVerticalNew($spid=null, $_from=null, $export_format='') {
		//echo "sumTextVertical </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false, 'exportFormat' => $export_format);
		
		#Priprava prve tabele, z imenom vprasanja/spremenljivke in besedilom vprasanja#######################################################
		
		//Priprava parametrov za tabelo z imenom vprasanja/spremenljivke in besedilom vprasanja
		$steviloStolpcevParameterTabular = 2;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? 'A|' : 'l|');				
			}else{
				$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
			}
			
		}
		//Priprava parametrov za tabelo - konec
		
		//zacetek latex tabele z obrobo	za tabelo z imenom vprasanja/spremenljivke in besedilom vprasanja
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za tabelo - konec
		
		/*Naslovna vrstica tabele*/		
		//prva vrstica tabele
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{>{\hsize=\dimexpr 6\hsize+\arrayrulewidth}X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		$tex .= $this->encodeText($spremenljivka['variable']).' & '.$this->encodeText($spremenljivka['naslov']).' '.$this->texNewLine;
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//zaljucek latex tabele z obrobo za tabelo z imenom vprasanja/spremenljivke in besedilom vprasanja
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za prvo tabelo - konec
		
		#Priprava prve tabele, z imenom vprasanja/spremenljivke in besedilom vprasanja - konec #############################################
		
		#Priprava druge tabele, z odgovori #############################################
		//Priprava parametrov za tabelo s podatki oz. odgovori
		$steviloStolpcevParameterTabular = 6;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				//$parameterTabular .= ($export_format == 'pdf' ? 'P|' : 'l|');
				$parameterTabular .= ($export_format == 'pdf' ? 'A|' : 'l|');
			}elseif($i == 1){	//ce je drugi stolpec z odgovori
				$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize=0.3\textwidth}X|' : 'l|');
			}else{
				//$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
				$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');
			}
			
		}
		//Priprava parametrov za tabelo - konec
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
	
		//druga vrstica tabele z naslovi stolpcev
		$tex .= $this->tableHeader($export_format);		
		/*Naslovni vrstici tabele - konec*/
		
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']); 
			if ($_variables_count > 0)
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['other'] != true) {
					# dodamo dodatne vrstice z labelami grida
					if ($_variables_count > 1) {
						self::outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if (/*$vAnswer['cnt'] > 0 &&*/ $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
								# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
								$textAnswerExceed = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								$options['isTextAnswer']=true;
								$options['textAnswerExceed'] = $textAnswerExceed;
								/*$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);*/
								$tex .= self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
							$counter++;
						}
						# izpišemo sumo veljavnih
						/*$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);*/
						$tex .= self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						$_Z_MV = !$this->hideEmpty;							
						if($_Z_MV){	//ce je potrebno izpisati tudi manjkajoce
							$tex .= $this->encodeText($lang['srv_anl_missing1']);
						}
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								/*$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);*/
								$tex .= self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						/*$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);*/
						$tex .= self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					/*$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);*/
					$tex .= self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		//zaljucek latex tabele z obrobo za prvo tabelo		
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za prvo tabelo - konec
		
		#Priprava druge tabele, z odgovori - konec #############################################
	
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				/*self::outputOtherAnswers($oAnswers);*/
				$tex .= self::outputOtherAnswers($oAnswers, $parameterTabular, $export_format);
			}
		}
		
		return $tex;
	}
	 
	function sumTextVertical($spid=null, $_from=null, $export_format='') {
		//echo "sumTextVertical </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false, 'exportFormat' => $export_format);
		
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
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/		
		//prva vrstica tabele
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{>{\hsize=\dimexpr 6\hsize+\arrayrulewidth}X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{5}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}			
		
		//druga vrstica tabele z naslovi stolpcev
		$tex .= $this->tableHeader($export_format);		
		/*Naslovni vrstici tabele - konec*/
		
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']); 
			if ($_variables_count > 0)
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['other'] != true) {
					# dodamo dodatne vrstice z labelami grida
					if ($_variables_count > 1) {
						self::outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if (/*$vAnswer['cnt'] > 0 &&*/ $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
								# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
								$textAnswerExceed = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								$options['isTextAnswer']=true;
								$options['textAnswerExceed'] = $textAnswerExceed;
								/*$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);*/
								$tex .= self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
							$counter++;
						}
						# izpišemo sumo veljavnih
						/*$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);*/
						$tex .= self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						$_Z_MV = !$this->hideEmpty;							
						if($_Z_MV){	//ce je potrebno izpisati tudi manjkajoce
							$tex .= $this->encodeText($lang['srv_anl_missing1']);
						}
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								/*$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);*/
								$tex .= self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						/*$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);*/
						$tex .= self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					/*$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);*/
					$tex .= self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		//zaljucek latex tabele z obrobo za prvo tabelo		
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za prvo tabelo - konec
	
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				/*self::outputOtherAnswers($oAnswers);*/
				$tex .= self::outputOtherAnswers($oAnswers, $parameterTabular, $export_format);
			}
		}
		
		return $tex;
	}
        
     /** Izriše lokacijske odgovore kot tabelo z navedbami z Latex
	 * 
	 * @param unknown_type $spid
	 */
	function sumLokacija($spid=null, $_from=null, $export_format='') {
		//echo "sumLokacija </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
        $enota = $spremenljivka['enota'];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];
	
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		
		
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis*/
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$height = 0;
		
			$count = 0;
			$text = array();
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$text[] = $_col['naslov'];
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			
				$count++;
			}
		}
		
		$steviloStolpcevParameterTabular = 1 + $count;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		//$parameterTabular = '';
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
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis - konec*/

		//zacetek latex tabele z obrobo	za prvo tabelo		
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;

		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		
		/*Naslovna vrstica tabele*/	
		/*prva vrstica*/
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		/*Konec naslovne vrstice*/

		if ($_grids_count > 0) {		
			$height = 0;			
/* 			// Testiramo visino vrstice glede na najdaljsi text
			foreach ($text AS $string){
				$singleWidth = ($count > 0) ? round(162 / $count): 162;					
				//$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
				$height = 1;
			} */
			
			/*$this->pdf->MultiCell(18, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);*/
			
			/*$this->dynamicCells($text, $count, 162, $height);*/
			/*$this->pdf->ln($height);*/
			/*druga vrstica*/
			$brezHline3 = 1;
			$brezNoveVrstice3 = 1;
			$nadaljevanjeVrstice3 = 1;
			/*prva prazna stolpca v 2. vrstici*/
			$textPrazniStolpci = array();
			$steviloPraznihStolpcev = 1;
			for($i=0;$i<$steviloPraznihStolpcev;$i++){
				$textPrazniStolpci[$i] = '';
			}
			$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
			
			$tex .= $this->dynamicCells($text, $count);	//izpis celic z odgovori v stolpcih (npr. Sploh ne velja, ...)
			
			$tex .= $this->texNewLine;
			if($export_format != 'xls'){
				$tex .= $this->horizontalLineTex;
			}
			/*prva prazna stolpca v 2. vrstici - konec*/
			/*druga vrstica - konec*/
			$last = 0;
			/*izpis vrstic s podatki*/
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);		
				$height = 0;
								
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					
					$count = 0;
					$text = array();
                                        
					$answers = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$index=0;
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) {
								$text2 = '(';
								foreach ($_valid_answers AS $answer) {

									$_ans = $answer[$_sequence];
                                                                        if($enota != 3)
                                                                            $_ans = str_replace("<br>","), (",$_ans);

									if ($_ans != null && $_ans != '') {
                                                                            if($enota == 3)
										$text2 .= $_ans."), (";
                                                                            else
                                                                                $answers[$count][$index]='('.$this->encodeText($_ans).')';
									}
                                                                        
                                                                    $index++;
								}
                                                                if($enota == 3)
                                                                    $text[] = substr($text2, 0, -3);
							}
							else {
								$text[] = '&nbsp;';
							}
							
							$count++;
						}
						
					}
					$last = $_max_i;			
				}
                                
                                if($enota != 3){
                                    for($i=0; $i<sizeof($answers[0]); $i++){
                                        $row = array();
                                        for($j=0; $j<$count; $j++){
                                            // Testiramo visino vrstice glede na najdaljsi text
                                            $singleWidth = ($count > 0) ? round(162 / $count): 162;					
                                            //$height = ($this->getCellHeight($answers[$j][$i], $singleWidth) > $height) ? $this->getCellHeight($answers[$j][$i], $singleWidth) : $height;
                                            $height = 1;
                                            $row[$j] = $answers[$j][$i];
                                        }
										
										
										//$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)
										$tex .= $this->sumLokacijaRowOutput($row, $count, $height, $grid['variable']);
                                        /*$this->sumLokacijaRowOutput($row, $count, $height, $grid['variable']);*/
										
										$tex .= $this->texNewLine;	//nova vrstica
										if($export_format != 'xls'){
											$tex .= $this->horizontalLineTex; /*horizontalna crta*/
										}
                                    }
                                }
                                else{
                                    // Testiramo visino vrstice glede na najdaljsi text
                                    foreach ($text AS $string){
                                            $singleWidth = ($count > 0) ? round(162 / $count): 162;					
                                            //$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
                                            $height = 1;
                                    }
									
									//$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)
									$tex .= $this->sumLokacijaRowOutput($text, $count, $height, $grid['variable']);
                                    /*$this->sumLokacijaRowOutput($text, $count, $height, $grid['variable']);*/
                                }
				/*zakljucek vrstice s podatki*/
/* 				$tex .= $this->texNewLine;	//nova vrstica
				$tex .= $this->horizontalLineTex;	//horizontalna crta */
			}
			/*izpis vrstic s podatki - konec*/
			
			
			//zaljucek latex tabele z obrobo
			$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			//zaljucek latex tabele z obrobo - konec
		}
		//echo "tex: ".$tex."</br>";
		return $tex;
	}
        
        /**
         * Izrise vrstico prilagojeno za lokacijo
         * 
         * @param type $text - array odgovorov
         * @param type $count - st variabel/stolpcev
         * @param type $height - izracunana najvisja visina celice v vrstici
         * @param type $variable - array variabel/stolpcev
         */
	function sumLokacijaRowOutput($text='', $count, $height=null, $variable='') {
			$texSumLokacijaRowOutput = '';
			$texSumLokacijaRowOutput .= " & ".$this->encodeText($variable);
/*          $this->pdf->MultiCell(18, $height, $this->encodeText($variable), 1, 'C', 0, 0, 0 ,0, true);
            $this->dynamicCells($text, $count, 162, $height);	
            $this->pdf->ln($height); */
			$texSumLokacijaRowOutput .= $this->dynamicCells($text, $count);
			
			return $texSumLokacijaRowOutput;
	}
	
	/** Izriše tekstovne odgovore kot tabelo z navedbami z Latex
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiText($spid=null, $_from, $export_format='') {
		//echo "sumMultiText </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# pogledamo koliko je max št odgovorov pri posameznem podvprašanju
/*		$_max_answers = array();
		$_max_answers_cnt = 0;
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			
			$_variables_count = count($grid['variables']);				
			if ($_variables_count > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_answers[$gid][$vid] = count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
					$_max_answers_cnt = max( $_max_answers_cnt, count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) ); 
				}
			}
		}
*/		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();
		//$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		
		
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis*/
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			$count = 0;
			$text = array();
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$text[] = $_col['naslov'];
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			
				$count++;
			}
		}		
		
		$steviloStolpcevParameterTabular = 2 + $count;
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
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis - konec*/
		
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/
		//prva vrstica tabele
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//druga vrstica
		$tex .= " & ".$this->encodeText($lang['srv_analiza_opisne_subquestion1'])." & \multicolumn{".$count."}{c|}{".$this->encodeText($lang['srv_analiza_opisne_arguments'])."} ".$this->texNewLine;		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		//konec naslovnih vrstic
		
		if ($_grids_count > 0) {		
			/*$height = 0;*/
			
			// Testiramo visino vrstice glede na najdaljsi text
/* 			foreach ($text AS $string){
				$singleWidth = ($count > 0) ? round(108 / $count): 108;					
				//$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
				$height = 1;
			} */
			
			/*tretja vrstica*/
			$brezHline3 = 1;
			$brezNoveVrstice3 = 1;
			$nadaljevanjeVrstice3 = 1;
			/*prva prazna stolpca v 3. vrstici*/
			$textPrazniStolpci = array();
			$steviloPraznihStolpcev = 2;
			for($i=0;$i<$steviloPraznihStolpcev;$i++){
				$textPrazniStolpci[$i] = '';
			}
			$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
			
			$tex .= $this->dynamicCells($text, $count);	//izpis celic z odgovori v stolpcih (npr. Sploh ne velja, ...)
			
			$tex .= $this->texNewLine;
			if($export_format != 'xls'){
				$tex .= $this->horizontalLineTex; /*obroba*/
			}
			/*prva prazna stolpca v 3. vrstici - konec*/

			$last = 0;
			/*izpis vrstic s podatki*/
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);		
				$height = 0;

				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					
					$count = 0;
					$text = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$index=0;
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) {
								$text2 = '';
								foreach ($_valid_answers AS $answer) {
									$index++;

									$_ans = $answer[$_sequence];

									if ($_ans != null && $_ans != '') {
										$text2 .= $_ans.', ';
									}
								}
								$text[] = substr($text2, 0, -2);
							}
							else {
								$text[] = '&nbsp;';
							}
							
							$count++;
						}
						
					}
					$last = $_max_i;			
				}
				
				// Testiramo visino vrstice glede na najdaljsi text
				foreach ($text AS $string){
					$singleWidth = ($count > 0) ? round(108 / $count): 108;					
					//$height = ($this->getCellHeight($string, $singleWidth) > $height) ? $this->getCellHeight($string, $singleWidth) : $height;
					$height = 1;
				}
				
				$tex .= $this->encodeText($grid['variable']);
				$tex .= " & ".$this->encodeText($grid['naslov']);				

				
				$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)

				
				/*zakljucek vrstice s podatki*/
				$tex .= $this->texNewLine;	//nova vrstica
				if($export_format != 'xls'){
					$tex .= $this->horizontalLineTex; /*horizontalna crta*/
				}
			}
			/*izpis vrstic s podatki - konec*/
			
			//zaljucek latex tabele z obrobo za drugo tabelo
			$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			//zaljucek latex tabele z obrobo za drugo tabelo - konec
		}

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				$tex .= self::outputOtherAnswers($oAnswers, $parameterTabular, $export_format);
			}
		}
		return $tex;
	}
                
	/** Izriše multi number odgovore. izpiše samo povprečja z Latex
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiNumber($spid=null, $_from=null, $export_format='') {
		//echo "sumMultiNumber </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis*/
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			$count = 0;
			$text = array();
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$text[] = $_col['naslov'];
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			
				$count++;
			}
		}		
		
		$steviloStolpcevParameterTabular = 2 + $count;
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
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis - konec*/

		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/		
		//prva vrstica tabele
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//druga vrstica
		$tex .= " & ".$this->encodeText($lang['srv_analiza_opisne_subquestion1'])." & \multicolumn{".$count."}{c|}{".$this->encodeText($lang['srv_analiza_sums_average'])."} ".$this->texNewLine;		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		/*Konec naslovnih vrstic*/

		if ($_grids_count > 0) {			
			/*tretja vrstica*/
			$brezHline3 = 1;
			$brezNoveVrstice3 = 1;
			$nadaljevanjeVrstice3 = 1;
			/*prva prazna stolpca v 3. vrstici*/
			$textPrazniStolpci = array();
			$steviloPraznihStolpcev = 2;
			for($i=0;$i<$steviloPraznihStolpcev;$i++){
				$textPrazniStolpci[$i] = '';
			}
			$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
			
			$tex .= $this->dynamicCells($text, $count);	//izpis celic z odgovori v stolpcih (npr. Sploh ne velja, ...)
			
			$tex .= $this->texNewLine;
			if($export_format != 'xls'){
				$tex .= $this->horizontalLineTex; /*obroba*/
			}
			/*prva prazna stolpca v 3. vrstici - konec*/

			$last = 0;
			/*izpis vrstic s podatki*/
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$tex .= $this->encodeText($grid['variable']);
				$tex .= " & ".$this->encodeText($grid['naslov']);	

				if ($_variables_count > 0) {
					
					$count = 0;
					$text = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami							
							$text[] = self::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
							$count++;
						}
						//$count++;
					}
					$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)
				}
				/*zakljucek vrstice s podatki*/
				$tex .= $this->texNewLine;	//nova vrstica
				if($export_format != 'xls'){
					$tex .= $this->horizontalLineTex; /*horizontalna linija*/
				}			
			}
			/*izpis vrstic s podatki - konec*/
			
			//zaljucek latex tabele z obrobo za drugo tabelo
			$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			//zaljucek latex tabele z obrobo za drugo tabelo - konec

		}
		/*echo "Latex tabele: ".$tex."</br>";*/
		return $tex;		
	}
	
	
	/** Izriše sumarnik v horizontalni obliki za multi checkbox z Latex
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumMultiHorizontalCheckbox($spid=null, $_from=null, $export_format='') {
		//echo "sumMultiHorizontalCheckbox </br>";
		global $lang;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		# tekst vprašanja		
	
		/*Priprava parametrov za tabelo in polja za 3. vrstico */
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		$count = 0;
		$height = 0;
		$textVrstica3 = array();
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$textVrstica3[] = $variable['naslov'].' ('.$variable['gr_id']. ')';
				
				/*$singleWidth = round(54 / (count($_variables) + 1));
				$height = 1; //$height = ($height < $this->getCellHeight($variable['naslov'].' ('.$variable['gr_id']. ')', $singleWidth)) ? $this->getCellHeight($variable['naslov'].' ('.$variable['gr_id']. ')', $singleWidth) : $height;	*/			
			}	
			$count++;
		}
		
		$steviloStolpcevParameterTabular = 5 + 2*$count;
		
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				//$parameterTabular .= ($export_format == 'pdf' ? 'b|' : 'l|');
				$parameterTabular .= ($export_format == 'pdf' ? 's|' : 'c|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
			}else if($i == 1){
				$parameterTabular .= ($export_format == 'pdf' ? 'B|' : 'l|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
			}
			else{
				$parameterTabular .= ($export_format == 'pdf' ? 's|' : 'c|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
			}
			
		}
		/*Priprava parametrov za tabelo in polja za 3. vrstico	- konec*/

		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/* prva vrstica */
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		/* prva vrstica - konec */
		
		/* druga vrstica*/	
		//$steviloPodStolpcev2 = count($spremenljivka['options']) + 1;
		$steviloPodStolpcev2 = $count;
		$tex .= " & ".$this->encodeText($lang['srv_analiza_opisne_subquestion1'])." & \multicolumn{".$steviloPodStolpcev2."}{c|}{".$this->encodeText($lang['srv_analiza_opisne_answers'])."} ";
		
		$tex .= " & ".$this->encodeText($lang['srv_analiza_opisne_valid']);
		$tex .= " & ".$this->encodeText($lang['srv_analiza_num_units']);
		
		$steviloPodStolpcev3 = $count+1;
		$tex .= " & \multicolumn{".$steviloPodStolpcev3."}{c|}{".$this->encodeText($lang['srv_analiza_opisne_arguments'])."} ";
		
		$tex .= $this->texNewLine;	//nova vrstica
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		/* druga vrstica - konec*/

		
		/*tretja vrstica*/
		$brezHline3 = 1;
		$brezNoveVrstice3 = 1;
		$nadaljevanjeVrstice3 = 1;
		/*prva prazna stolpca 3. vrstice*/
		$textPrazniStolpci = array();
		$steviloPraznihStolpcev = 2;
		for($i=0;$i<$steviloPraznihStolpcev;$i++){
			$textPrazniStolpci[$i] = '';
		}
		$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
		/*prva prazna stolpca 3. vrstice - konec*/
		
		$tex .= $this->dynamicCells($textVrstica3, $count);	//izpis celic z odgovori v stolpcih (npr. Sploh ne velja, ...)
		
		/*se dva prazna stolpca 3. vrstice*/
		$textPrazniStolpci = array();
		$steviloPraznihStolpcev = 2;
		for($i=0;$i<$steviloPraznihStolpcev;$i++){
			$textPrazniStolpci[$i] = '';
		}
		$tex .= $this->tableRow($textPrazniStolpci, $brezHline3, $brezNoveVrstice3, $nadaljevanjeVrstice3);	//izpis ostalega dela vrstice
		/*se dva prazna stolpca 3. vrstice - konec*/
		
		$tex .= " & ".$this->dynamicCells($textVrstica3, $count); //izpis celic z odgovori v stolpcih (npr. Sploh ne velja, ...)
		
/* 		$count = 0;
		$text = array();
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$text[] = $variable['naslov'].' ('.$variable['gr_id']. ')';
			}
			$count++;
		}
		$this->dynamicCells($text, $count, 44, $height); */
		
		$tex .= " & ".$this->encodeText($lang['srv_anl_suma1']);	//Skupaj
		
		$tex .= $this->texNewLine;
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}	//horizontalna crta
		/*tretja vrstica - konec*/

		/*vrstice s podatki*/
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			
			$_cnt = 0;
			$height = 1;//$height = $this->getCellHeight($this->encodeText($grids['naslov']), 18);
			$height = ($height < 8 ? 8 : $height);
			
			# vodoravna vrstice s podatki
			$tex .= $this->encodeText($grids['variable']);
			$tex .= " & ".$this->encodeText($grids['naslov']);

			$_arguments = 0;

			$_max_appropriate = 0;
			$_max_cnt = 0;
			// prikaz frekvenc
			$count = 0;
			$text = array();
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;
				
				$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}
		
				if ($variable['other'] != true) {
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0; 
										
					$text[] = $_cnt . ' (' . self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') . ')';
					$count++;
				}
				
			}
			
			$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)
			
			# veljavno 
			$tex .= " & ".$_max_cnt;

			#ustrezno
			$tex .= " & ".$_max_appropriate;			
			
			$count = 0;
			$text = array();
			foreach ($grids['variables'] AS $vkey => $variable) {
				if ($variable['other'] != true) {
					$_sequence = $variable['sequence'];
					$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					
					$_percent = ($_arguments > 0 ) ? $_cnt * 100 / $_arguments : 0;  
										
					$text[] = $_cnt . ' (' . self::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') . ')';
					$count++;
				}			
			}

			$tex .= " & ".$this->dynamicCells($text, $count);	//izpis celic z izracuni odgovorov v stolpcih (npr. Sploh ne velja, ...)
		
			$tex .= " & ".$_arguments;
			
			/*zakljucek vrstice*/
			$tex .= $this->texNewLine;	//nova vrstica
			if($export_format != 'xls'){
				$tex .= $this->horizontalLineTex; /*obroba*/
			}	//horizontalna crta
		}
		/*vrstice s podatki - konec*/
		
		/*zaljucek latex tabele z obrobo za prvo tabelo*/
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		/*zaljucek latex tabele z obrobo za prvo tabelo - konec*/

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				$tex .= self::outputOtherAnswers($oAnswers, $parameterTabular, $export_format);
			}
		}
		//echo "tex: ".$tex."</br>";
		return $tex;
	}
	
	/** za multi grid tekstovne vrstice doda vrstico z labeliami grida
	 * 
	 * @param $gkey
	 * @param $gAnswer
	 * @param $spid
	 * @param $_options
	 */
	function outputGridLabelVertical($gid=null, $grid=null, $vid=null, $variable=null, $spid=null, $_options=array()) {
 		//echo "outputGridLabelVertical </br>";
		$text = array();
					
		$text[] = $this->encodeText($variable['variable']);
		$text[] = $this->encodeText(($grid['naslov'] != '' ? $grid['naslov']. '&nbsp;-&nbsp;' : '').$variable['naslov']);
		
		$text[] = '';
		$text[] = '';
		$text[] = '';
		$text[] = '';
		
		$this->tableRow($text);
		
		$counter++;
		return $counter;	
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

	
	
	
	/** Izriše heatmap odgovore.
	 * 
	 * @param unknown_type $spid
	 */
	function sumHeatmap($spid=null, $_from=null, $export_format='') {
		//echo "sumHeatmap </br>";
		global $lang;
		global $site_url;
		global $site_path;
		$tex = '';
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		//Priprava podatkov za tabelo
		# preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
		$only_valid = 0;
		if (count($spremenljivka['grids']) > 0) {
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				# dodamo dodatne vrstice z albelami grida
				if (count($grid['variables']) > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$only_valid += (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];				
				}
			}
		}
 		if (SurveyDataSettingProfiles :: getSetting('hideEmpty') == 1 && $only_valid == 0 && SurveyAnalysis::$_forceShowEmpty == false) {
			return;
		}
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

 		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];
		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();
		
 		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		$export = 1;
		
 		//2. stolpec - Veljavni za x in y koordinati
		$validHeatmapRegion = SurveyAnalysis::validHeatmapRegion($spremenljivka['grids'], $spid, $_valid_answers, $export);		
		//3. stolpec - Ustrezni za x in y koordinati
		$ustrezniHeatmapRegion = SurveyAnalysis::ustrezniHeatmapRegion($spid, $_valid_answers, $_sequence); //vsi mozni kliki		
		//4. stolpec - Povprecje za x in y koordinati		
		$povprecjeHeatmapClicksX = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'povprecje', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');		
		$povprecjeHeatmapClicksY = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'povprecje', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		//5. stolpec - Standardni odklon za x in y koordinati		
		$stdevHeatmapClicksX = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'stdev', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');		
		$stdevHeatmapClicksY = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'stdev', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		//6. stolpec - Minimum za x in y koordinati
		$minHeatmapClicksX = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'min', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');		
		$minHeatmapClicksY = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'min', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
		//7. stolpec - Max za x in y koordinati
		$maxHeatmapClicksX = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'x', $validHeatmapRegion, 'max', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');	
		$maxHeatmapClicksY = self::formatNumber(SurveyAnalysis::heatmapClicksCalc($spremenljivka['grids'], $spid, $_valid_answers, 'y', $validHeatmapRegion, 'max', $export),SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');		
		//Priprava podatkov za tabelo - konec
		
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis*/
		$steviloStolpcevParameterTabular = 7;
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
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis - konec*/

		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*Naslovni vrstici tabele*/		
		//prva vrstica tabele
		$steviloPodStolpcev1 = $steviloStolpcevParameterTabular - 1;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		//$tex .= $this->encodeText($spremenljivka['variable']).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1+1).'\hsize + '.($steviloPodStolpcev1+1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($spremenljivka['naslov']).'} '.$this->texNewLine;
		$dolzinaVprasanja = strlen($this->encodeText($spremenljivka['naslov']));
		//echo $dolzinaVprasanja."</br>";
		if($dolzinaVprasanja > MEJA_DOLZINA_VPRASANJA){	//ce je dolzina vprasanja daljsa od ene vrstice v tabeli			
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}else{
			$tex .= $this->encodeText($spremenljivka['variable'])." & \multicolumn{".$steviloPodStolpcev1."}{l|}{".$this->encodeText($spremenljivka['naslov'])."} ".$this->texNewLine;
		}
		
		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		
		//druga vrstica		
		$spr_id = $this->GetSprId($spid);
		
		$heatmapImageFileName = 'heatmap'.$spr_id;
		$heatmapImageSrc = $site_path.'main/survey/uploads/'.$heatmapImageFileName.'.png';
 		$heatmapImageFileNamePresent = file_exists($heatmapImageSrc);
		if($heatmapImageFileNamePresent){	//ce je prisotna datoteka heatmap slike
			$heatmapImage = '\includegraphics[scale=0.5]{'.$heatmapImageFileName.'}';
		}else{	//ce ni
			//$heatmapImage = 'Pred izvozom, zgenerirajte heatmap';
			$heatmapImage = $lang['export_analysis_heatmap_msg'];
		}		
		$tex .= " & \multicolumn{".$steviloPodStolpcev1."}{c|}{".$heatmapImage."} ".$this->texNewLine;		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		/*Konec naslovnih vrstic*/
		
		//tretja vrstica
		$tex .= " \multicolumn{".$steviloStolpcevParameterTabular."}{|c|}{".$this->encodeText($lang['srv_analiza_heatmap_clicked_coords'])."} ".$this->texNewLine;		
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex; /*obroba*/
		}
		//tretja vrstica - konec
		
		
		$brezHline = $this->getBrezHline($export_format);
		
		//cetrta vrstica
 		$cetrtaVrstica = array();
		$cetrtaVrstica[] = $this->encodeText($lang['coordinates']);
		$cetrtaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_valid_heatmap']);
		$cetrtaVrstica[] = $this->encodeText($lang['srv_analiza_num_units_valid_heatmap']);
		$cetrtaVrstica[] = $this->encodeText($lang['srv_means_label']);
		$cetrtaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);
		$cetrtaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_min']);
		$cetrtaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_max']);
		$tex .= $this->tableRow($cetrtaVrstica, $brezHline);
		//cetrta vrstica - konec
		
		//vrstici s podatki za x in y koordinati
		//peta vrstica x
		$petaVrstica = array();
		$petaVrstica[] = 'x';
		$petaVrstica[] = $this->encodeText($validHeatmapRegion);
		$petaVrstica[] = $this->encodeText($ustrezniHeatmapRegion);
		$petaVrstica[] = $this->encodeText($povprecjeHeatmapClicksX);
		$petaVrstica[] = $this->encodeText($stdevHeatmapClicksX);
		$petaVrstica[] = $this->encodeText($minHeatmapClicksX);
		$petaVrstica[] = $this->encodeText($maxHeatmapClicksX);
		$tex .= $this->tableRow($petaVrstica, $brezHline);
		//peta vrstica x - konec
		
		//sesta vrstica y
		$sestaVrstica = array();
		$sestaVrstica[] = 'y';
		$sestaVrstica[] = $this->encodeText($validHeatmapRegion);
		$sestaVrstica[] = $this->encodeText($ustrezniHeatmapRegion);
		$sestaVrstica[] = $this->encodeText($povprecjeHeatmapClicksY);
		$sestaVrstica[] = $this->encodeText($stdevHeatmapClicksY);
		$sestaVrstica[] = $this->encodeText($minHeatmapClicksY);
		$sestaVrstica[] = $this->encodeText($maxHeatmapClicksY);
		$tex .= $this->tableRow($sestaVrstica, $brezHline);
		//sesta vrstica y - konec
		//vrstici s podatki za x in y koordinati - konec
		
		//preveri, ali je prisotno kaksno obmocje, nadaljuj izris tabele
		$RegionPresent = self::HeatmapRegionPresence($spremenljivka['grids'], $spid, $_valid_answers);
		//preveri, ali je prisotno kaksno obmocje, nadaljuj izris tabele - konec
		
		if($RegionPresent){	//ce imamo obmocja
			//7. vrstica - naslovna za obmocja
			$tex .= " \multicolumn{".$steviloStolpcevParameterTabular."}{|c|}{".$this->encodeText($lang['srv_analiza_heatmap_clicked_regions'])."} ".$this->texNewLine;		
			if($export_format != 'xls'){
				$tex .= $this->horizontalLineTex; /*obroba*/
			}
			//konec - 7. vrstice

			//8. vrstica
			$osmaVrstica = array();
			$osmaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_frequency_heatmap']);//od tretjega stolpca dalje, ker prva dva sta za naslov Obmocja kot multicolumn
			$osmaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_valid_heatmap']);
			$osmaVrstica[] = $this->encodeText('% - '.$lang['srv_analiza_opisne_valid_heatmap']);
			$osmaVrstica[] = $this->encodeText($lang['srv_analiza_num_units_valid_heatmap']);
			$osmaVrstica[] = $this->encodeText('% - '.$lang['srv_analiza_num_units_valid_heatmap']);
			
			$tex .= " \multicolumn{2}{|c|}{".$this->encodeText($lang['srv_hot_spot_regions_menu'])."} ";
			$tex .= $this->tableRow($osmaVrstica, 0, 0, 1);
			//echo $tex;
			//8. vrstica - konec
			
			$_answersOther = array();
			$_grids_count = count($spremenljivka['grids']);
			$_css_bck = 'anl_bck_desc_2 anl_ac anl_bt_dot ';
			$last = 0;
			
			if ($_grids_count > 0) {	
 				$_row = $spremenljivka['grids'][0];
				$indeks = 0;
				//$veljavnaSkupnaFreq = 0;
				if (count($_row['variables'])>0){
					foreach ($_row['variables'] AS $rid => $_col ){
					$_sequence = $_col['sequence'];	# id kolone z podatki
						if ($_col['other'] != true) {
							if($indeks != 0){
								//echo "_col: ".strip_tags ($_col['naslov'])."</br>";
								//od 9. vrstice dalje, kjer so po vrsticah obmocja in njihovi podatki
								$devetaVrstica = array();
								//1. stolpcev z imenom obmocja
								//echo $_col['naslov'];
								//$devetaVrstica[] = $this->encodeText($_col['naslov']);
								$devetaVrstica[] = " \multicolumn{2}{|c|}{".$this->encodeText($_col['naslov'])."} ";
								//$tex .= " \multicolumn{2}{|c|}{".$this->encodeText($_col['naslov'])."} ";
								//1. stolpcev z imenom obmocja - konec
								
								//2. stolpec - Frekvenca
								$freqHeatmapRegion = SurveyAnalysis::freqHeatmapRegion($spremenljivka['grids'], $spid, $_valid_answers, $indeks, $export);
								$veljavnaSkupnaFreq = $veljavnaSkupnaFreq + $freqHeatmapRegion;
								//echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$freqHeatmapRegion.'</td>';
								//$devetaVrstica[] = $this->encodeText($freqHeatmapRegion);
								$devetaVrstica[] = $freqHeatmapRegion;								
								//2. stolpec - Frekvenca - konec
								
								//3. stolpec - Veljavni
								//$validHeatmapRegion = self::validHeatmapRegion($spremenljivka['grids'], $spid, $_valid_answers);
								//echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$validHeatmapRegion.'</td>';
								$devetaVrstica[] = $this->encodeText($validHeatmapRegion);
								//3. stolpec - Veljavni - konec
								
								//4. stolpec - % Veljavni
								$_procentValidHeatmapRegion = ($validHeatmapRegion > 0 ) ? 100*$freqHeatmapRegion / $validHeatmapRegion : 0;							
								$_procentValidHeatmapRegion = self::formatNumber($_procentValidHeatmapRegion, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
								//echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$_procentValidHeatmapRegion.'</td>';
								$devetaVrstica[] = $this->encodeText($_procentValidHeatmapRegion);
								//4. stolpec - % Veljavni - konec
								
								//5. stolpec - Ustrezni
								$ustrezniHeatmapRegion = SurveyAnalysis::ustrezniHeatmapRegion($spid, $_valid_answers, $_sequence); //vsi mozni kliki
								//echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$ustrezniHeatmapRegion.'</td>';
								$devetaVrstica[] = $this->encodeText($ustrezniHeatmapRegion);
								//5. stolpec - Ustrezni - konec
								
								//6. stolpec - % Ustrezni
								$_procentUstrezniHeatmapRegion = ($ustrezniHeatmapRegion > 0 ) ? 100*$freqHeatmapRegion / $ustrezniHeatmapRegion : 0;
								$_procentUstrezniHeatmapRegion = self::formatNumber($_procentUstrezniHeatmapRegion, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%');
								//echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$_procentUstrezniHeatmapRegion.'</td>';
								$devetaVrstica[] = $this->encodeText($_procentUstrezniHeatmapRegion);
								//6. stolpec - % Ustrezni - konec

								$tex .= $this->tableRow($devetaVrstica, $brezHline);
								//echo $tex;
								//od 9. vrstice dalje, kjer so po vrsticah obmocja in njihovi podatki - konec
								
								//*********** Izris veljavnih in manjkajocih vrednosti
		 							$counter = 0;
									$options['isTextAnswer'] = false;
									$manjkajoci = $ustrezniHeatmapRegion - $validHeatmapRegion;
									
									//10. vrstica
									//$validHeatmapRegion
									//echo $validHeatmapRegion;
									//echo "validHeatmapRegion: ".$validHeatmapRegion."</br>";
									//$counter = SurveyAnalysis::outputSumaValidAnswerHeatmap($counter,$_sequence,$spid,$options, $validHeatmapRegion);
									$desetaVrstica = array();
									$desetaVrstica[] = $this->encodeText($lang['srv_analiza_opisne_valid']);
									$desetaVrstica[] = $this->encodeText($lang['srv_analiza_manjkajocevrednosti']);
									$desetaVrstica[] = $validHeatmapRegion;
									$desetaVrstica[] = " \multicolumn{4}{|c|}{ } ";									
									$tex .= $this->tableRow($desetaVrstica, $brezHline);									
									//10. vrstica - konec
									
									//11. vrstica
									$enajstaVrstica = array();
									$enajstaVrstica[] = $this->encodeText($lang['srv_anl_missing1']);
									$enajstaVrstica[] = $this->encodeText($lang['srv_analiza_manjkajocevrednosti']);
									$enajstaVrstica[] = $manjkajoci;
									$enajstaVrstica[] = " \multicolumn{4}{|c|}{ } ";									
									$tex .= $this->tableRow($enajstaVrstica, $brezHline);									
									//11. vrstica - konec
									
									//12. vrstica
									$dvanajstaVrstica = array();
									$dvanajstaVrstica[] = " \multicolumn{2}{|c|}{".$this->encodeText($lang['srv_anl_suma_valid'])." } ";
									$dvanajstaVrstica[] = $ustrezniHeatmapRegion;
									$dvanajstaVrstica[] = " \multicolumn{4}{|c|}{ } ";									
									$tex .= $this->tableRow($dvanajstaVrstica, $brezHline);									
									//12. vrstica - konec

/* 									if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
										foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
											if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki niso 0
													//$counter = SurveyAnalysis::outputInvalidAnswerHeatmap($counter,$ikey,$iAnswer,$_sequence,$spid,$options, $manjkajoci);
												//$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$iAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
												$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$iAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
												echo "_invalid: ".$_invalid."</br>";
												//echo "_percent: ".$_percent."</br>";
											} 
										}
										# izpišemo sumo neveljavnih
										//$counter = SurveyAnalysis::outputSumaInvalidAnswerHeatmap($counter,$_sequence,$spid,$options, $manjkajoci);
										echo "manjkajoci: ".$manjkajoci."</br>";
									} */
									#izpišemo še skupno sumo
									//$counter = SurveyAnalysis::outputSumaHeatmap($counter,$_sequence,$spid,$options, $ustrezniHeatmapRegion);
									//$ustrezniHeatmapRegion
									//echo "ustrezniHeatmapRegion: ".$ustrezniHeatmapRegion."</br>";
								$veljavnaSkupnaFreq = 0;
							}
							//*********** Izris veljavnih in manjkajocih vrednosti - konec
							
						} else {
							$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
						}
					$indeks++;
					}
				}		
			}
		}
		
		
		# izpišemo še tekstovne odgovore za polja drugo
/* 		if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {
				echo '<div class="div_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
			}
		} */
		
		
		//zaljucek latex tabele z obrobo za drugo tabelo
		$tex .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za drugo tabelo - konec

/* 		if (count($spremenljivka['grids']) > 0) {

		} */
		//echo "Latex tabele: ".$tex."</br>";
		
		return $tex;		
	}
	
	

	//izrisemo dinamicne celice (podamo sirino, stevilo celic in vsebino)
	//function dynamicCells($arrayText, $count, $width, $height, $arrayParams=array()){
	//izrisemo dinamicne celice (podamo stevilo celic in vsebino)
	function dynamicCells($arrayText=null, $count=null){
		$texDynamicCells = '';
		if($arrayText[0] == '')
			$arrayText[0] = '';
			
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 30);
		$linecount == 1 ? $height = 1 : $height = 4.7 + ($linecount-1)*3.3;*/
		$text = array();
		for($i=0; $i<$count-1; $i++){
		//for($i=0; $i<$count; $i++){
			if($arrayText[$i] == '')
				$arrayText[$i] = '';
			/*$this->pdf->MultiCell($singleWidth, $height, $this->encodeText($arrayText[$i]), 1, 'C', 0, 0, 0 ,0, true);*/
			
			/*$texDynamicCells .= $this->encodeText($arrayText[$i]);*/
			$text[$i] = $this->encodeText($arrayText[$i]);
		}
		
		//zadnje polje izrisemo druge sirine ker se drugace zaradi zaokrozevanja tabela porusi		
		/*$lastWidth = ($lastWidth < 4) ? 4 : $lastWidth;*/
		if($count > 0){
			/*$this->pdf->MultiCell($lastWidth, $height, $this->encodeText($arrayText[$count-1]), 1, 'C', 0, 0, 0 ,0, true);*/
			
			/*$texDynamicCells .= $this->encodeText($arrayText[$count-1]);*/
			$text[$count-1] = $this->encodeText($arrayText[$count-1]);
		}else{
			/*$this->pdf->MultiCell($lastWidth, $height, $this->encodeText(''), 1, 'C', 0, 0, 0 ,0, true);*/
			
			/*$texDynamicCells .= $this->encodeText('');*/
			$text[$count-1] = $this->encodeText('');
		}
		
		$brezHline = 1;
		$brezNoveVrstice = 1;
		$texDynamicCells .= $this->tableRow($text, $brezHline, $brezNoveVrstice);
		//echo "texDynamicCells: ".$texDynamicCells."</br>";
		return $texDynamicCells;
	}

	function getCellHeight($string='', $width=null){
		
		// Star nacin
		//$linecount = $this->pdf->getNumLines($this->encodeText($string), $width);
		//$height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.5) );
		
		$this->pdf->startTransaction();
		// get the number of lines calling you method
		$linecount = $this->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();

		$height = ($linecount <= 1) ? 4.7 : $linecount * ($this->pdf->getFontSize() * $this->pdf->getCellHeightRatio()) + 2;
		
		return $height;
	}
	
	static function HeatmapRegionPresence($spremenljivkaGrids=null, $spid=null, $_valid_answers=null){		
		$HeatmapRegionPresence = false;
 		foreach ($spremenljivkaGrids AS $gid => $grid){
			$_variables_count = count($grid['variables']);
 			if ($_variables_count > 0){
				# preštejemo max vrstic na grupo
				$_max_i = 0;
				//$numObmocij = 0;
 				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					//$numObmocij++;
				}				
				$indeksZaObmocja = 0;
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) 
					{						
						if (count($_valid_answers) > 0) {
							
							foreach ($_valid_answers AS $answer) {
								$_ans = $answer[$_sequence];
								if ($_ans != null && $_ans != '' && $indeksZaObmocja >= count($_valid_answers)) 
								{
									$HeatmapRegionPresence = true;
								}
								$indeksZaObmocja++;
							}
						}							
					}
					
				}				
			}				
		}
		return $HeatmapRegionPresence;
	}
}

?>