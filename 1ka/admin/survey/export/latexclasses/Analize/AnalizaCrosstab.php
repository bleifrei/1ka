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

/** 
 * @desc Class za generacijo izvoza v Latex
 */

class AnalizaCrosstab extends LatexAnalysisElement{

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
	
	public $crosstabClass = null;		//crosstab class
	
	protected $sessionData;
	protected $counter;
	
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $crosstabClass=null, $counter=null, $sprID = null, $loop = null)
	{	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		//if ( is_numeric($anketa) )
		if ( is_numeric($anketa['id']) )
		{
			$this->anketa = $anketa;
			$this->spremenljivka = $sprID;
			$this->counter = $counter;
			$this->crosstabClass = $crosstabClass;

			
			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->anketa['id']);
			$this->sessionData = SurveyUserSession::getData('crosstab_charts');
			//print_r($this->sessionData);
			//echo "sessionData: ".$this->sessionData[name]."</br>";
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


	function init()
	{
		global $lang;
		
		// array used to define the language and charset of the pdf file to be generated
		$language_meta = Array();
		$language_meta['a_meta_charset'] = 'UTF-8';
		$language_meta['a_meta_dir'] = 'ltr';
		$language_meta['a_meta_language'] = 'sl';
		$language_meta['w_page'] = $lang['page'];

		//set some language-dependent strings
	    $this->pdf->setLanguageArray($language_meta);

		//set margins
		$this->pdf->setPrintHeaderFirstPage(true);
		$this->pdf->setPrintFooterFirstPage(true);
		$this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set header and footer fonts
		$this->pdf->setHeaderFont(Array(FNT_HEADER_TEXT, "I", FNT_HEADER_SIZE));
		$this->pdf->setFooterFont(Array(FNT_HEADER_TEXT, 'I', FNT_HEADER_SIZE));


		// set document information
		$this->pdf->SetAuthor('An Order Form');
		$this->pdf->SetTitle('An Order');
		$this->pdf->SetSubject('An Order');

		// set default header data
		$this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()));

		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$this->pdf->SetFont(FNT_MAIN_TEXT, '', FNT_MAIN_SIZE);
		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		return true;
	}	

	public function showCrosstabsTable($crosstabClass=null, $export_format='', $creport=false) {
		global $lang;
		$tabela = '';
		$this->crosstabClass = $crosstabClass;		
		
		if ($this->crosstabClass->getSelectedVariables(1) !== null && $this->crosstabClass->getSelectedVariables(2) !== null) {
			if($creport){
				$variables2 = $this->crosstabClass->getSelectedVariables(1);
				$variables1 = $this->crosstabClass->getSelectedVariables(2);	
			}else{
				$variables1 = $this->crosstabClass->getSelectedVariables(1);
				$variables2 = $this->crosstabClass->getSelectedVariables(2);
			}

			$stevec = 0;
			
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {
					
					$crosstabs = null;
					$crosstabs_value = null;
					
					$crosstabs = $this->crosstabClass->createCrostabulation($v_first, $v_second);
					$crosstabs_value = $crosstabs['crosstab'];
					
					# podatki spremenljivk
					$spr1 = $this->crosstabClass->_HEADERS[$v_first['spr']];
					$spr2 = $this->crosstabClass->_HEADERS[$v_second['spr']];
					
					/* print_r($crosstabClass);
					echo "</br>"; */
					
					$grid1 = $spr1['grids'][$v_first['grd']];
					$grid2 = $spr2['grids'][$v_second['grd']];
					
					#število vrstic in število kolon
					$cols = count($crosstabs['options1']);
					$rows = count($crosstabs['options2']);

					# ali prikazujemo vrednosti variable pri spremenljivkah
					$show_variables_values = $this->crosstabClass->doValues;
	
					# nastavitve oblike
					if (($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) && ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR)) {
						# dodamo procente in residuale
						$rowSpan = 3;
						$numColumnPercent = $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3;						
						$numColumnResidual = $this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR;
						$tblColumn = max($numColumnPercent,$numColumnResidual);
					} else if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) { 
						# imamo samo procente
						$rowSpan = 2;
						$numColumnPercent = $this->crosstabClass->crossChk1 + $this->crosstabClass->crossChk2 + $this->crosstabClass->crossChk3;
						$numColumnResidual = 0;
						$tblColumn = $numColumnPercent;
					} else if ($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR) {
						# imamo samo residuale
						$rowSpan = 2;
						$numColumnPercent = 0;
						$numColumnResidual = $this->crosstabClass->crossChkEC + $this->crosstabClass->crossChkRE + $this->crosstabClass->crossChkSR + $this->crosstabClass->crossChkAR;
						$tblColumn = $numColumnResidual;
					} else {
						#prikazujemo samo podatke
						$rowSpan = 1;
						$numColumnPercent = 0;
						$numColumnResidual = 0;
						$tblColumn = 1;
					}
					
					
					# izrišemo tabelo
					
					# najprej izrišemo naslovne vrstice		
					# za multicheckboxe popravimo naslov, na podtip
					$sub_q1 = null;
					if ($spr1['tip'] == '6' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '19' || $spr1['tip'] == '20') {
						foreach ($spr1['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_first['seq']) {
									$sub_q1 = strip_tags($spr1['naslov']);
									if ($show_variables_values == true ) {
										$sub_q1 .= '&nbsp;('.strip_tags($spr1['variable']).')';
									}
									if ($spr1['tip'] == '16') {
										$sub_q1 .= ', ' . strip_tags($grid1['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid1['variable']) . ')' : '');
									} else {
										$sub_q1 .= ', ' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}
							}		
						}
					}
					if ($sub_q1 == null) {
						$sub_q1 .=  strip_tags($spr1['naslov']);
						$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
					}
					
					$sub_q2 = null;
					if ($spr2['tip'] == '6' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '19' || $spr2['tip'] == '20') {
						foreach ($spr2['grids'] AS $grid) {
							foreach ($grid['variables'] AS $variable) {
								if ($variable['sequence'] == $v_second['seq']) {
									$sub_q2 = strip_tags($spr2['naslov']);
									if ($show_variables_values == true) {
										$sub_q2 .= '&nbsp;('.strip_tags($spr2['variable']).')';
									}
									if ($spr2['tip'] == '16') {
										$sub_q2.= ', ' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($grid2['variable']) . ')' : '');
									} else {
										$sub_q2.= ', ' . strip_tags($variable['naslov']) . ($show_variables_values == true ? '&nbsp;(' . strip_tags($variable['variable']) . ')' : '');
									}
								}							
							}		
						}
					}
					if ($sub_q2 == null) {
						$sub_q2 .= strip_tags($spr2['naslov']);
						$sub_q2 .= ($show_variables_values == true ? '&nbsp;('.strip_tags($spr2['variable']).')' : '');
					}
					
					$this->crosstabVars = array($sub_q1, $sub_q2);
					
					//pridobitev stevila dodatnih podatkov v vsaki vmesni vrstici oz. stolpcu
					$steviloVmesnihVrstic =	$this->crosstabClass->crossChk0 + 
											($this->crosstabClass->crossChk1||$this->crosstabClass->crossChk2||$this->crosstabClass->crossChk3)	+
											($this->crosstabClass->crossChkEC||$this->crosstabClass->crossChkRE||$this->crosstabClass->crossChkSR||$this->crosstabClass->crossChkAR);
											
					$steviloVmesnihStolpcevPodvrstica2 = $this->crosstabClass->crossChk1 +
														 $this->crosstabClass->crossChk2 +
														 $this->crosstabClass->crossChk3;
					
					$steviloVmesnihStolpcevPodvrstica3 = $this->crosstabClass->crossChkEC +
														 $this->crosstabClass->crossChkRE +
														 $this->crosstabClass->crossChkSR +
														 $this->crosstabClass->crossChkAR;

					//echo "steviloVmesnihVrstic: ".$steviloVmesnihVrstic."</br>";
					//echo "steviloVmesnihStolpcevPodvrstica2: ".$steviloVmesnihStolpcevPodvrstica2."</br>";
					//echo "steviloVmesnihStolpcevPodvrstica3: ".$steviloVmesnihStolpcevPodvrstica3."</br>";
					
					$podVrstice = 0;
					
					//if($steviloVmesnihStolpcevPodvrstica2||$steviloVmesnihStolpcevPodvrstica3){
					if($steviloVmesnihStolpcevPodvrstica2&&$steviloVmesnihStolpcevPodvrstica3){
						//echo "1 </br>";
						if($steviloVmesnihStolpcevPodvrstica2 >= $steviloVmesnihStolpcevPodvrstica3){
							$steviloVmesnihStolpcevPodvrstic = $steviloVmesnihStolpcevPodvrstica2;
						//}else{
						}elseif(0 < $steviloVmesnihStolpcevPodvrstica2 && $steviloVmesnihStolpcevPodvrstica2 < $steviloVmesnihStolpcevPodvrstica3){
							$steviloVmesnihStolpcevPodvrstic = $steviloVmesnihStolpcevPodvrstica3;
						}
						//eksperiment
						$steviloVmesnihStolpcevPodvrstic =  $steviloVmesnihStolpcevPodvrstica2*$steviloVmesnihStolpcevPodvrstica3;
						$podVrstice = 1;
					}elseif($steviloVmesnihStolpcevPodvrstica2){
						//echo "2 </br>";
						$steviloVmesnihStolpcevPodvrstic = $steviloVmesnihStolpcevPodvrstica2;
						$podVrstice = 1;
					}elseif($steviloVmesnihStolpcevPodvrstica3){
						//echo "3 </br>";
						$steviloVmesnihStolpcevPodvrstic = $steviloVmesnihStolpcevPodvrstica3;
						$podVrstice = 1;
					}else{
						//echo "4 </br>";
						$steviloVmesnihStolpcevPodvrstic = 1;
					}
					//echo "steviloVmesnihStolpcevPodvrstic: ".$steviloVmesnihStolpcevPodvrstic."</br>";
					//echo "Podvrstice: ".$podVrstice."</br>";
					//pridobitev stevila dodatnih podatkov v vsaki vmesni vrstici oz. stolpcu - konec

					//za ureditev prepoznavanja presirokih tabele
					$mejaZaVelikeTabele = 8;
					$velikostTabele = $cols*$steviloVmesnihStolpcevPodvrstic + 1 + $steviloVmesnihStolpcevPodvrstic;	//surova velikost tabele, da prepoznamo, ce je presiroka ali ne					
					//echo "<b>velikost tabele: </b>".($velikostTabele)." podatkov je : $cols </br>";

					if($velikostTabele > $mejaZaVelikeTabele){	//ce imamo veliko tabelo, jo je potrebno razbiti na vec tabel, ker drugace je presiroka
						//echo "tabela je presiroka, ima ".($velikostTabele)." stolpcev</br>";
						$presirokaTabela = 1;

						$steviloTabelCelih = intval($velikostTabele / $mejaZaVelikeTabele);
						$steviloTabelMod = $velikostTabele % $mejaZaVelikeTabele;
						$delnaTabela = 0;
						if($steviloTabelMod != 0){
							$delnaTabela = 1;
						}
						$steviloTabel = $steviloTabelCelih + $delnaTabela;

						if($delnaTabela){	//ce je delna tabela, manjsa od velikosti mejnih stolpcev (8)
							$steviloStolpcevDelnaTabela = $velikostTabele - $steviloTabelCelih*$mejaZaVelikeTabele;
						}

						/* echo "stevilo podtabel celih ".($steviloTabelCelih)." </br>";
						echo "stevilo podtabel mod ".($steviloTabelMod)." </br>";
						echo "stevilo stolpcev delna podtabela ".($steviloStolpcevDelnaTabela)." </br>";
						echo "stevilo podtabel za izpis: ".($steviloTabel)." </br>"; */



						$cols = array();
						$crosstabsOptions1All = array();
						//$crosstabsOptions1 = array();

						//$crosstabs['options1']
						//print_r($crosstabs['options1']);
						//print_r(array_chunk($crosstabs['options1'], $mejaZaVelikeTabele, true));

						//priprava polja s stevilom stolpcev za vsako podtabelo
						//echo "stevilo vseh podatkov: ".count($crosstabs['options1'])."</br>";
						$crosstabsOptions1All = array_chunk($crosstabs['options1'], $mejaZaVelikeTabele, true);
						//print_r($crosstabsOptions1All);

						for($tab=0; $tab<$steviloTabel; $tab++){
							if($tab != ($steviloTabel-1)){	//ce ni zadnja podtabela
								$cols[$tab] = $mejaZaVelikeTabele;								
							}else{
								if($delnaTabela){
									$cols[$tab] = $steviloStolpcevDelnaTabela - 1;
								}else{
									$cols[$tab] = $mejaZaVelikeTabele;
								}
							}
						}
						
						/* foreach($crosstabsOptions1All as $crosstabsOptions1Index => $crosstabsOptions1){
							if($crosstabsOptions1Index != (count($crosstabsOptions1All)-1)){	//ce ni zadnja podtabela
								$cols[$crosstabsOptions1Index] = $mejaZaVelikeTabele;								
							}else{
								if($delnaTabela){
									$cols[$crosstabsOptions1Index] = $steviloStolpcevDelnaTabela;
								}else{
									$cols[$crosstabsOptions1Index] = $mejaZaVelikeTabele;
								}
							}
						} */
						//priprava polja s stevilom stolpcev za vsako podtabelo - konec

						//priprava polja s stevilom stolpcev za vsako podtabelo
						//echo "stevilo vseh podatkov: ".count($crosstabs['options1'])."</br>";
						/* $crosstabsOptions1All = array_chunk($crosstabs['options1'], $mejaZaVelikeTabele, true);
						//print_r($crosstabsOptions1All);

						$steviloVsehStolpcev = 0;
						
						foreach($crosstabsOptions1All as $crosstabsOptions1Index => $crosstabsOptions1){	//TOLE JE POTREBNO PREUREDITI, SAJ SE POJAVI TEŽAVA, KO PODATKA NI IN JE STOLPEC S SUMAMI (recimo)
							if($crosstabsOptions1Index != (count($crosstabsOptions1All)-1)){	//ce ni zadnja podtabela
								$cols[$crosstabsOptions1Index] = count($crosstabsOptions1All[$crosstabsOptions1Index]);
								$steviloVsehStolpcev = $steviloVsehStolpcev + (count($crosstabsOptions1All[$crosstabsOptions1Index]) + 1);							
							}else{
								if($delnaTabela){
									//$cols[$crosstabsOptions1Index] = count($crosstabsOptions1All[$crosstabsOptions1Index]) + 1;
									$cols[$crosstabsOptions1Index] = count($crosstabsOptions1All[$crosstabsOptions1Index]);
								}else{
									//$cols[$crosstabsOptions1Index] = count($crosstabsOptions1All[$crosstabsOptions1Index]) + 1;
									$cols[$crosstabsOptions1Index] = count($crosstabsOptions1All[$crosstabsOptions1Index]);
								}
								$steviloVsehStolpcev = $steviloVsehStolpcev + (count($crosstabsOptions1All[$crosstabsOptions1Index]) + 2);
							}
						} */
						//priprava polja s stevilom stolpcev za vsako podtabelo - konec
						//echo "steviloVsehStolpcev: $steviloVsehStolpcev </br>";
						
					}else{
						$presirokaTabela = 0;
					}
					//za ureditev prepoznavanja presirokih tabele - konec
					

					if($presirokaTabela == 0){ //ce ni presiroka tabela
						
						if($stevec == 0){
							//Priprava parametrov za tabelo						

							$steviloStolpcevParameterTabular = $cols*$steviloVmesnihStolpcevPodvrstic + 1 + $steviloVmesnihStolpcevPodvrstic;
							
							$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
							$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
							$parameterTabular = '|';
							
							for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
								//ce je prvi stolpec
								if($i == 0){
									//$parameterTabular .= ($export_format == 'pdf' ? 'P|' : 'l|');
									$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
								}else{
									//$parameterTabular .= ($export_format == 'pdf' ? ' >{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
									$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
									//$parameterTabular .= ($export_format == 'pdf' ? 'c|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/	//pred spremembo je bilo to
								}			
							}
							//Priprava parametrov za tabelo - konec
							
							//zacetek latex tabele z obrobo	za prvo tabelo	
							$pdfTable = 'tabularx';
							$rtfTable = 'tabular';
							$pdfTableWidth = 1;
							$rtfTableWidth = 1;
							//echo "Parametri tabele: $parameterTabular </br>";
							$tabela .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
							if($export_format != 'xls'){
								$tabela .= $this->horizontalLineTex; /*obroba*/
							}
							//zacetek latex tabele z obrobo za prvo tabelo - konec
						}
						
						//prva vrstica ######################################################################################
						/*prvi in zadnji stolpec prve vrstice prazna, ostali pa z ustreznim besedilom*/					
						$steviloMultiCol1 = $cols * $steviloVmesnihStolpcevPodvrstic;					

						##########					
						$steviloTabColSep = ($steviloMultiCol1-1)*2;
						$steviloArrayrulewidth = ($steviloMultiCol1-1);
						
						if($export_format=='pdf'){
							//$tabela .= " & \multicolumn{".$steviloMultiCol1."}{>{\hsize=\dimexpr".$steviloMultiCol1."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}C|}{".$this->encodeText($sub_q1)."} ";	//prvi (prazen) in stolpec z besedilom
							$tabela .= " & \multicolumn{".$steviloMultiCol1."}{>{\hsize=\dimexpr".$steviloMultiCol1."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}c|}{".$this->encodeText($sub_q1)."} ";	//prvi (prazen) in stolpec z besedilom
						}elseif($export_format=='rtf'){
							$tabela .= " & \multicolumn{".$steviloMultiCol1."}{c|}{".$this->encodeText($sub_q1)."} ";	//prvi (prazen) in stolpec z besedilom
						}
						
						###########

						if($cols!=0){
							if($steviloVmesnihStolpcevPodvrstic==1){ 					
								$tabela .= " & ";	//zadnji stolpec
							}else{
								if($export_format=='xls'){
									$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{c|}{}";	//zadnji stolpec
								}else{
									$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{X|}{}";	//zadnji stolpec
									//$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{C|}{}";	//zadnji stolpec
								}						
							}
						}

						
						
						$tabela .= $this->texNewLine;
						if($export_format != 'xls'){
							$tabela .= $this->horizontalLineTex; /*obroba*/
						}
						//prva vrstica - konec ##############################################################################

						// druga vrstica ####################################################################################				
						$tabela .=	$this->encodeText($sub_q2);	//prvi stolpec 2. vrstice
						//echo "testiram, kjer ssem: ".$this->encodeText($sub_q2)."</br>";
						//echo "testiram, kjer ssem: ".$steviloTabColSep."</br>";
						$drugaVrstica = array();
						if (count($crosstabs['options1']) > 0 ) {	//stolpci (izkljucno) med prvim in zadnjim
							foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
								#ime variable
								$text = $crossVariabla['naslov'];
								# če ni tekstovni odgovor dodamo key
								if ($crossVariabla['type'] != 't') {
									$text .= ' ( '.$ckey1.' )';
								}
								$tabela .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, $this->encodeText($text));
							}
						}
						//echo "test: ".$podVrstice."</br>";
						if($podVrstice){	//ce je potrebno multicol prikazovanje
							//spremenljivke za pravilno sirino
							$colNum = $steviloVmesnihStolpcevPodvrstic;
							$colNum2 = $steviloVmesnihStolpcevPodvrstic/$steviloVmesnihStolpcevPodvrstica2;	//stevilo podstolpcev za 2. podvrstico
							$colNum3 = $steviloVmesnihStolpcevPodvrstic/$steviloVmesnihStolpcevPodvrstica3;	//stevilo podstolpcev za 3. podvrstico
							//spremenljivke za pravilno sirino - konec
							
							$tabela .= $this->MultiColCellLatex($colNum, $this->encodeText($lang['srv_analiza_crosstab_skupaj']));	//izpis naslova zadnjega stolpca 2. vestice
						}else{
							$tabela .= " & ";
							$tabela .= $this->encodeText($lang['srv_analiza_crosstab_skupaj']);	//izpis naslova zadnjega stolpca 2. vestice
						}			
						
						$tabela .= $this->texNewLine;	/*nova vrstica*/
						if($export_format != 'xls'){					
							$tabela .= $this->horizontalLineTex; /*obroba*/
						}
						
						// druga vrstica - konec #########################################################################
						
						//izpis vmesnih vrstic tabele ####################################################################				
						if (count($crosstabs['options2']) > 0) {
							
							//POSAMEZNA VMESNA VRSTICA
							foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
								//priprava besedila za prvo celico 1. stolpca
								$text = $crossVariabla2['naslov'];
								if($export_format != 'xls'){
									$text = $this->snippet($this->encodeText($text), 25);
								}
								if ($crossVariabla2['type'] !== 't') {					
									$text .= ' ('.$ckey2.')';
								}
								//priprava besedila za prvo celico 1. stolpca - konec
								
								//izpis prve celice 1. stolpca
								if($export_format != 'xls'){
									$tabela .= $this->MultiRowCellLatex($steviloVmesnihVrstic, $this->encodeText($text));
								}else{
									$tabela .= $text;
								}
								
								$tabela1 = '';	//za belezenje 1. vrstice s frekvencami
								$tabela2 = '';	//za belezenje 2. vrstice z odstotki
								$tabela3 = '';	//za belezenje 3. vrstice z residuali
								
								foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {

									if ($this->crosstabClass->crossChk0) {
										# frekvence crostabov
										$crossChk0 = ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
										$tabela1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum);							
									}									
									if ($this->crosstabClass->crossChk1) {
										#procent vrstica
										$crossChk1 = $this->encodeText($this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), 2, '%'));
										$tabela2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2);
									}
									if ($this->crosstabClass->crossChk2) {
										#procent stolpec
										$crossChk2 = $this->encodeText($this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), 2, '%'));
										$tabela2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2);
									}
									if ($this->crosstabClass->crossChk3) {
										#procent skupni
										$crossChk3 = $this->encodeText($this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), 2, '%'));
										$tabela2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2);
									}

									# residuali
									if ($this->crosstabClass->crossChkEC) {
										$crossChkEC = $this->encodeText($this->formatNumber($crosstabs['exC'][$ckey1][$ckey2], 3, ''));
										$tabela3 .= $this->DisplayLatexCells($crossChkEC, $podVrstice, $colNum3);
									}
									if ($this->crosstabClass->crossChkRE) {
										$crossChkRE = $this->encodeText($this->formatNumber($crosstabs['res'][$ckey1][$ckey2], 3, ''));
										$tabela3 .= $this->DisplayLatexCells($crossChkRE, $podVrstice, $colNum3);
									}
									if ($this->crosstabClass->crossChkSR) {
										$crossChkSR = $this->encodeText($this->formatNumber($crosstabs['stR'][$ckey1][$ckey2], 3, ''));
										$tabela3 .= $this->DisplayLatexCells($crossChkSR, $podVrstice, $colNum3);
									}
									if ($this->crosstabClass->crossChkAR) {
										$crossChkAR = $this->encodeText($this->formatNumber($crosstabs['adR'][$ckey1][$ckey2], 3, ''));
										$tabela3 .= $this->DisplayLatexCells($crossChkAR, $podVrstice, $colNum3);
									}				
								}					
								
								//se zadnji stolpec - vedno risemo							
								if ($this->crosstabClass->crossChk0) {
									# suma po vrsticah
									$crossChk0 = (int)$crosstabs['sumaVrstica'][$ckey2];								
									$tabela1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum, $steviloVmesnihStolpcevPodvrstic);
								}
								if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
									# suma po vrsticah v procentih
									if ($this->crosstabClass->crossChk1) {
										$crossChk1 = $this->encodeText($this->formatNumber(100, 2, '%'));
										$tabela2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
									}
									if ($this->crosstabClass->crossChk2) {
										$crossChk2 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
										$tabela2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
									}
									if ($this->crosstabClass->crossChk3) {
										$crossChk3 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
										$tabela2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
									}
								}

								$tabela .= $tabela1;	//izpis 1. vrstice s freq							
								$tabela .= $this->texNewLine;
					
								if($steviloVmesnihVrstic > 1){	//ce je potrebno multirow prikazovanje								
									if($tabela2!=''){	//ce je 2. podvrstica
										if($export_format != 'xls'){
											$tabela .= ' \cline{2-'.$steviloStolpcevParameterTabular.'}';	//je potrebno urediti prvi stolpec tako, da ni crt med celicami
										}
										$tabela .= $tabela2;	//izpis 2. vrstice z odstotki
										$tabela .= $this->texNewLine;

									}
									if($tabela3!=''){	//ce je 3. podvrstica
										if($export_format != 'xls'){
											$tabela .= ' \cline{2-'.$steviloStolpcevParameterTabular.'}';	//je potrebno urediti prvi stolpec tako, da ni crt med celicami
										}
										$tabela .= $tabela3;	//izpis 3. vrstice z residuali
										$tabela .= $this->MultiColCellLatex($colNum, '');	//pri residualih je zadnja celica v zadnjem stolpcu prazna	
										$tabela .= $this->texNewLine;
										if($export_format != 'xls'){
											$tabela .= $this->horizontalLineTex;
										}
									}else{
										if($export_format != 'xls'){
											$tabela .= $this->horizontalLineTex;
										}
									}
								}else{
									if($export_format != 'xls'){
										$tabela .= $this->horizontalLineTex;
									}
								}
							}
						}
						//izpis vmesnih vrstic tabele - konec ##################################################################################
						
						
						// skupni sestevki po stolpcih - ZADNJA VRSTICA ########################################################################
						
						//izpis celice v prvem stolpcu
						if($export_format != 'xls'){					
							$tabela .= $this->MultiRowCellLatex($steviloVmesnihVrstic, $this->encodeText($lang['srv_analiza_crosstab_skupaj']), $tabela2, $tabela3, $cols);
						}else{
							$tabela .= $this->encodeText($lang['srv_analiza_crosstab_skupaj']);
						}
						//izpis celice v prvem stolpcu - konec
						
						if (count($crosstabs['options1']) > 0){
							$tabelaZadnja1 = ''; //belezi kodo za 1. podvrstico zadnje vrstice
							$tabelaZadnja2 = ''; //belezi kodo za 2. podvrstico zadnje vrstice
							foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								
								# prikazujemo eno od treh možnosti					
								if ($this->crosstabClass->crossChk0) {
									# suma po stolpcih
									$crossChk0 = (int)$crosstabs['sumaStolpec'][$ckey1];
									$tabelaZadnja1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum, $steviloVmesnihStolpcevPodvrstic);
								}					
								if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
									# suma po stolpcih v procentih
									if ($this->crosstabClass->crossChk1) {
										$crossChk1 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
										$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
									}
									if ($this->crosstabClass->crossChk2) {
										$crossChk2 = $this->encodeText($this->formatNumber(100, 2, '%'));
										$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
									}
									if ($this->crosstabClass->crossChk3){
										$crossChk3 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
										$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
									}
								}
							}
							
							# zadnja celica z skupno sumo						
							if ($this->crosstabClass->crossChk0) {
								# skupna suma
								$crossChk0 = (int)$crosstabs['sumaSkupna'];
								$tabelaZadnja1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum, $steviloVmesnihStolpcevPodvrstic);
							}
							if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
								# suma po stolpcih v procentih
								if ($this->crosstabClass->crossChk1) {
									$crossChk1 = $this->encodeText($this->formatNumber(100, 2, '%'));
									$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
								}
								if ($this->crosstabClass->crossChk2) {
									$crossChk2 = $this->encodeText($this->formatNumber(100, 2, '%'));
									$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
								}
								if ($this->crosstabClass->crossChk3) {
									$crossChk3 = $this->encodeText($this->formatNumber(100, 2, '%'));
									$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
								}
							}
						}
						
						$tabela .= $tabelaZadnja1;	//izpis 1. podvrstice s freq
						$tabela .= $this->texNewLine;

						if($steviloVmesnihVrstic > 1 && $tabela2!=''){	//ce je potrebno multicol prikazovanje
							if($export_format != 'xls'){
								$tabela .= ' \cline{2-'.$steviloStolpcevParameterTabular.'}';	//je potrebno urediti prvi stolpec tako, da ni crt med celicami
							}
						}else{
							if($export_format != 'xls'){
								$tabela .= $this->horizontalLineTex;
							}
						}							
						
						if($steviloVmesnihVrstic > 1 && $tabela2!=''){	//ce je potrebno multirow prikazovanje
							$tabela .= $tabelaZadnja2;	//izpis 2. vrstice z odstotki
							$tabela .= $this->texNewLine;
							if($export_format != 'xls'){
								$tabela .= $this->horizontalLineTex;
							}
						}
						// skupni sestevki po stolpcih - ZADNJA VRSTICA - konec #############################################################################
						
						$stevec++;
					}elseif($presirokaTabela == 1){ //ce je tabela presiroka
						//print_r($cols);

						//echo "<b>velikost tabele: </b>".($velikostTabele)." podatkov je : $cols </br>";
						//echo $velikostTabele % 2;
						//potrebno za ureditev zadnje podtabele s Skupaj
						if($velikostTabele % 2){
							$niSodo = 1;
							//$steviloTabel = $steviloTabel - 1;
						}else{
							$niSodo = 0;							
						}
						//echo "stevilo podtabel za izpis, če ni sodo: ".($steviloTabel)." </br>"; 
						//potrebno za ureditev predzadnje podtabele - konec

						if($steviloVmesnihStolpcevPodvrstic>1){
							$crosstabsOptions1All = array_chunk($crosstabs['options1'], 2, true);
						}
						

						//izpis vsake podtabele posebej
						for($t=0; $t<$steviloTabel; $t++){
							$stevec = 0;
							if($stevec == 0){

								//Priprava parametrov za tabelo						

								//$steviloStolpcevParameterTabular = $cols[$t]*$steviloVmesnihStolpcevPodvrstic + 1 + $steviloVmesnihStolpcevPodvrstic;
								//$steviloStolpcevParameterTabular = $cols[$t]*$steviloVmesnihStolpcevPodvrstic + $steviloVmesnihStolpcevPodvrstic;
								//$steviloStolpcevParameterTabular = $cols[$t]*$steviloVmesnihStolpcevPodvrstic + 1;

								if($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR){	//ce je potrebno izpisati tudi reziduale
									$reziduali = 1;
								}

								if($t != ($steviloTabel-1)){	//ce ni zadnja podtabela
									$steviloStolpcevParameterTabular = $mejaZaVelikeTabele + 1;								
								}else{
									if($delnaTabela){
										//echo "residuali: ".$this->crosstabClass->crossChkEC." ".$this->crosstabClass->crossChkRE." ".$this->crosstabClass->crossChkSR." ".$this->crosstabClass->crossChkAR."</br>";
										if($this->crosstabClass->crossChkEC || $this->crosstabClass->crossChkRE || $this->crosstabClass->crossChkSR || $this->crosstabClass->crossChkAR){	//ce je potrebno izpisati tudi reziduale
											//$reziduali = 1;
											$steviloStolpcevParameterTabular = $steviloStolpcevDelnaTabela + 1;
										}else{
											$steviloStolpcevParameterTabular = $steviloStolpcevDelnaTabela;
										}
										/* echo "steviloStolpcevDelnaTabela: $steviloStolpcevDelnaTabela </br>";
										echo "steviloStolpcevParameterTabular1: $steviloStolpcevParameterTabular </br>"; */
										//$steviloStolpcevParameterTabular++;
									}else{
										$steviloStolpcevParameterTabular = $mejaZaVelikeTabele + 1;										
									}
								}

								if($steviloStolpcevParameterTabular <= 2){									
									$steviloStolpcevParameterTabular++;
								}
								//echo "steviloStolpcevParameterTabular2: $steviloStolpcevParameterTabular </br>";
								$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
								$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
								$parameterTabular = '|';
								
								for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
									if($t != ($steviloTabel-1)){//ce ni zadnja podtabela
										//ce je prvi stolpec
										if($i == 0){
											//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
											$parameterTabular .= ($export_format == 'pdf' ? 'Y|' : 'l|');
										}else{
											//$parameterTabular .= ($export_format == 'pdf' ? ' >{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
											//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
											$parameterTabular .= ($export_format == 'pdf' ? 'Y|' : 'c|');
										}	
									}else{	//ce je zadnja podtabela
										$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
									}	
								}
								//Priprava parametrov za tabelo - konec
								
								//zacetek latex tabele z obrobo	za prvo tabelo	
								$pdfTable = 'tabularx';
								$rtfTable = 'tabular';
								$pdfTableWidth = 1;
								$rtfTableWidth = 1;
								//echo "Parametri tabele $t: $parameterTabular s številom stolpcev $cols[$t] </br>";
								$tabela .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
								if($export_format != 'xls'){
									$tabela .= $this->horizontalLineTex; /*obroba*/
								}
								//zacetek latex tabele z obrobo za prvo tabelo - konec
							}
							
							//prva vrstica ######################################################################################
							/*prvi in zadnji stolpec prve vrstice prazna, ostali pa z ustreznim besedilom*/					
							
							//$steviloMultiCol1 = $cols[$t] * $steviloVmesnihStolpcevPodvrstic;
							if($t == ($steviloTabel-1)){	//ce je zadnja podtabela
								//$steviloMultiCol1 = ($cols[$t]-1) * $steviloVmesnihStolpcevPodvrstic;
								$steviloMultiCol1 = ($steviloStolpcevParameterTabular - 2);
							}else{
								//$steviloMultiCol1 = $cols[$t] * $steviloVmesnihStolpcevPodvrstic;
								$steviloMultiCol1 = $steviloStolpcevParameterTabular - 1;
							}
							

							if($reziduali &&($t == ($steviloTabel-1))){ //ce so reziduali in je zadnja podtabela
								$steviloMultiCol1 = 0;
								$steviloVmesnihStolpcevPodvrstic=1;
							}
							//echo "steviloMultiCol1: ".$steviloMultiCol1." $reziduali</br>";
							##########					
							
							if($steviloMultiCol1){
								$steviloTabColSep = ($steviloMultiCol1-1)*2;
								$steviloArrayrulewidth = ($steviloMultiCol1-1);
								if($export_format=='pdf'){
									//$tabela .= " & \multicolumn{".$steviloMultiCol1."}{>{\hsize=\dimexpr".$steviloMultiCol1."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}C|}{".$this->encodeText($sub_q1)."} ";	//prvi (prazen) in stolpec z besedilom
									$tabela .= " & \multicolumn{".$steviloMultiCol1."}{>{\hsize=\dimexpr".$steviloMultiCol1."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}c|}{".$this->encodeText($sub_q1)."} ";	//prvi (prazen) in stolpec z besedilom
								}elseif($export_format=='rtf'){
									$tabela .= " & \multicolumn{".$steviloMultiCol1."}{c|}{".$this->encodeText($sub_q1)."} ";	//prvi (prazen) in stolpec z besedilom
								}
							}else{
								$tabela .= " & ".$this->encodeText($sub_q1)." ";
							}
							###########
							//echo "testiram, kjer ssem: ".$this->encodeText($sub_q1)."</br>";

							if($cols[$t]!=0 &&($t == ($steviloTabel-1))){								
								if($steviloVmesnihStolpcevPodvrstic==1){ 					
									$tabela .= " & ";	//zadnji stolpec
								}else{
									if($export_format=='xls'){
										$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{c|}{}";	//zadnji stolpec
									}else{
										//$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{X|}{}";	//zadnji stolpec
										$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{Y|}{}";	//zadnji stolpec
									}						
								}
							}

							if($reziduali &&($t == ($steviloTabel-1))){ //ce so reziduali in je zadnja podtabela
								$tabela .= " & ";	//zadnji stolpec
							}							
							
							$tabela .= $this->texNewLine;
							if($export_format != 'xls'){
								$tabela .= $this->horizontalLineTex; /*obroba*/
							}
							//prva vrstica - konec ##############################################################################
							//echo "$tabela</br>";
						
							// druga vrstica ####################################################################################				
							$tabela .=	$this->encodeText($sub_q2);	//prvi stolpec 2. vrstice
							//echo "testiram, kjer ssem: ".$this->encodeText($sub_q2)."</br>";
							//echo "testiram, kjer ssem: ".$steviloTabColSep."</br>";
							$drugaVrstica = array();
							
							//echo count($crosstabsOptions1All[$t])."</br>";
							//if (count($crosstabs['options1']) > 0 ) {	//stolpci (izkljucno) med prvim in zadnjim
							if (count($crosstabsOptions1All[$t]) > 0 ) {	//stolpci (izkljucno) med prvim in zadnjim
								foreach ($crosstabsOptions1All[$t] as $ckey1 =>$crossVariabla) {									
									#ime variable
									$text = $crossVariabla['naslov'];
									# če ni tekstovni odgovor dodamo key
									if ($crossVariabla['type'] != 't') {
										$text .= ' ( '.$ckey1.' )';
									}
									$tabela .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, $this->encodeText($text));
									//echo "steviloVmesnihStolpcevPodvrstic $t: $steviloVmesnihStolpcevPodvrstic</br>";
									//echo $this->encodeText($text)."</br>";
								}
								if($niSodo && $t == ($steviloTabel-2) && $reziduali){	//ce ni sodo in je predzadnja podtabela in so reziduali, dodaj se vse potrebno za prazen stolpec
								//if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
									$tabela .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');									
								}
							}else{
								$tabela .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
							}
							//echo "test: ".$podVrstice."</br>";
							if($podVrstice){	//ce je potrebno multicol prikazovanje							
								//spremenljivke za pravilno sirino
								$colNum = $steviloVmesnihStolpcevPodvrstic;
								$colNum2 = $steviloVmesnihStolpcevPodvrstic/$steviloVmesnihStolpcevPodvrstica2;	//stevilo podstolpcev za 2. podvrstico
								$colNum3 = $steviloVmesnihStolpcevPodvrstic/$steviloVmesnihStolpcevPodvrstica3;	//stevilo podstolpcev za 3. podvrstico
								//spremenljivke za pravilno sirino - konec
								if($t == ($steviloTabel-1)){	//ce je zadnji stolpec v podtabeli
									$tabela .= $this->MultiColCellLatex($colNum, $this->encodeText($lang['srv_analiza_crosstab_skupaj']));	//izpis naslova zadnjega stolpca 2. vrstice
								}
								
							//}else{
							}elseif(($t == ($steviloTabel-1))){	//ce je zadnja podtabela
							//}elseif(($t == ($steviloTabel-1)) && $niSodo == 1){
								$tabela .= " & ";
								$tabela .= $this->encodeText($lang['srv_analiza_crosstab_skupaj']);	//izpis naslova zadnjega stolpca 2. vrstice
							}			
														
							$tabela .= $this->texNewLine;	/*nova vrstica*/
							if($export_format != 'xls'){					
								$tabela .= $this->horizontalLineTex; /*obroba*/
							}
							
							// druga vrstica - konec #########################################################################
							
						
							//izpis vmesnih vrstic tabele ####################################################################				
							//echo count($crosstabs['options2'])."</br>"; 
							//if (count($crosstabs['options2']) > 0) {
							if (count($crosstabs['options2']) > 0) {
								
								//POSAMEZNA VMESNA VRSTICA
								foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
									//priprava besedila za prvo celico 1. stolpca
									$text = $crossVariabla2['naslov'];
									if($export_format != 'xls'){
										$text = $this->snippet($this->encodeText($text), 25);
									}
									if ($crossVariabla2['type'] !== 't') {					
										$text .= ' ('.$ckey2.')';
									}
									//priprava besedila za prvo celico 1. stolpca - konec
									
									//izpis prve celice 1. stolpca
									if($export_format != 'xls'){
										$tabela .= $this->MultiRowCellLatex($steviloVmesnihVrstic, $this->encodeText($text));										
									}else{
										$tabela .= $text;
									}
									
									$tabela1 = '';	//za belezenje 1. vrstice s frekvencami
									$tabela2 = '';	//za belezenje 2. vrstice z odstotki
									$tabela3 = '';	//za belezenje 3. vrstice z residuali
									
									//foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
									foreach ($crosstabsOptions1All[$t] as $ckey1 => $crossVariabla1) {
									
										if ($this->crosstabClass->crossChk0) {
											# frekvence crostabov
											$crossChk0 = ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
											$tabela1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum);
											/* if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
												$tabela1 .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
											} */
											
										}									
										if ($this->crosstabClass->crossChk1) {
											#procent vrstica
											$crossChk1 = $this->encodeText($this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]), 2, '%'));
											$tabela2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2);
										}
										if ($this->crosstabClass->crossChk2) {
											#procent stolpec
											$crossChk2 = $this->encodeText($this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaStolpec'][$ckey1], $crosstabs_value[$ckey1][$ckey2]), 2, '%'));
											$tabela2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2);
										}
										if ($this->crosstabClass->crossChk3) {
											#procent skupni
											$crossChk3 = $this->encodeText($this->formatNumber($this->crosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs_value[$ckey1][$ckey2]), 2, '%'));
											$tabela2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2);
										}

										# residuali										
										if ($this->crosstabClass->crossChkEC) {
											$crossChkEC = $this->encodeText($this->formatNumber($crosstabs['exC'][$ckey1][$ckey2], 3, ''));
											$tabela3 .= $this->DisplayLatexCells($crossChkEC, $podVrstice, $colNum3);
										}
										if ($this->crosstabClass->crossChkRE) {
											$crossChkRE = $this->encodeText($this->formatNumber($crosstabs['res'][$ckey1][$ckey2], 3, ''));
											$tabela3 .= $this->DisplayLatexCells($crossChkRE, $podVrstice, $colNum3);
										}
										if ($this->crosstabClass->crossChkSR) {
											$crossChkSR = $this->encodeText($this->formatNumber($crosstabs['stR'][$ckey1][$ckey2], 3, ''));
											$tabela3 .= $this->DisplayLatexCells($crossChkSR, $podVrstice, $colNum3);
										}
										if ($this->crosstabClass->crossChkAR) {
											$crossChkAR = $this->encodeText($this->formatNumber($crosstabs['adR'][$ckey1][$ckey2], 3, ''));
											$tabela3 .= $this->DisplayLatexCells($crossChkAR, $podVrstice, $colNum3);
										}
										//echo 	"tabela 3: $tabela3 </br>";
									}	

									//if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
									if($niSodo && $t == ($steviloTabel-2) && $reziduali){	//ce ni sodo in je predzadnja podtabela in je potrebno izpisati reziduale, dodaj se vse potrebno za prazen stolpec
										$tabela1 .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
									}
									
									if(!$crosstabsOptions1All[$t]){
										if($tabela1){
											//$tabela1 .= ' & ';
										}
										if($tabela2!=''){									
											//$tabela2 .= ' & ';
										}
										if($tabela3!=''){
											//$tabela3 .= ' & ';
										}
									}
									
									
									//se zadnji stolpec - risemo, ko je zadnja tabela
									if($t == ($steviloTabel-1)){										
										if ($this->crosstabClass->crossChk0) {
											# suma po vrsticah
											$crossChk0 = (int)$crosstabs['sumaVrstica'][$ckey2];
											if(!$crosstabsOptions1All[$t]){
												$tabela1 .= ' & ';
											}			
											$tabela1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum, $steviloVmesnihStolpcevPodvrstic, $niSodo);
										}
										if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
											if(!$crosstabsOptions1All[$t]){
												$tabela2 .= ' & ';
											}
											# suma po vrsticah v procentih
											if ($this->crosstabClass->crossChk1) {
												$crossChk1 = $this->encodeText($this->formatNumber(100, 2, '%'));
												$tabela2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic, $niSodo);
											}
											if ($this->crosstabClass->crossChk2) {
												$crossChk2 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
												$tabela2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic, $niSodo);
											}
											if ($this->crosstabClass->crossChk3) {
												$crossChk3 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaVrstica'][$ckey2] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
												$tabela2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic, $niSodo);
											}
										}
									}
									
									$tabela .= $tabela1;	//izpis 1. vrstice s freq
													
									$tabela .= $this->texNewLine;
									//echo "steviloStolpcevParameterTabular: $steviloStolpcevParameterTabular </br>";
									if($steviloVmesnihVrstic > 1){	//ce je potrebno multirow prikazovanje								
										if($tabela2!=''){	//ce je 2. podvrstica
											if($export_format != 'xls'){
												$tabela .= ' \cline{2-'.$steviloStolpcevParameterTabular.'}';	//je potrebno urediti prvi stolpec tako, da ni crt med celicami
											}

											
											if($niSodo && $t == ($steviloTabel-2) && $reziduali){	//ce ni sodo in je predzadnja podtabela in so reziduali, dodaj se vse potrebno za prazen stolpec
											//if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
												$tabela2 .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
											}

											$tabela .= $tabela2;	//izpis 2. vrstice z odstotki

											$tabela .= $this->texNewLine;

										}
										if($tabela3!=''){	//ce je 3. podvrstica
											if($export_format != 'xls'){
												$tabela .= ' \cline{2-'.$steviloStolpcevParameterTabular.'}';	//je potrebno urediti prvi stolpec tako, da ni crt med celicami
											}											

											if($niSodo && $t == ($steviloTabel-2) && $reziduali){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
											//if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec												
												$tabela3 .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
											}

											$tabela .= $tabela3;	//izpis 3. vrstice z residuali

											if($t == ($steviloTabel-1)){	//ce je zadnja podtabela
												$tabela .= $this->MultiColCellLatex($colNum, '');	//pri residualih je zadnja celica v zadnjem stolpcu prazna	
											}
											$tabela .= $this->texNewLine;
											if($export_format != 'xls'){
												$tabela .= $this->horizontalLineTex;
											}
										}else{
											if($export_format != 'xls'){
												$tabela .= $this->horizontalLineTex;
											}
										}
									}else{
										if($export_format != 'xls'){
											$tabela .= $this->horizontalLineTex;
										}
									}
								}
							}
							//izpis vmesnih vrstic tabele - konec ##################################################################################

							//echo "tabela 2: $tabela2 </br>";
							//echo "tabela 3: $tabela3 </br>";

							// skupni sestevki po stolpcih - ZADNJA VRSTICA ########################################################################
							
							//izpis celice v prvem stolpcu
							if($export_format != 'xls'){					
								$tabela .= $this->MultiRowCellLatex($steviloVmesnihVrstic, $this->encodeText($lang['srv_analiza_crosstab_skupaj']), $tabela2, $tabela3, $cols[$t]);
							}else{
								$tabela .= $this->encodeText($lang['srv_analiza_crosstab_skupaj']);
							}
							//izpis celice v prvem stolpcu - konec
							
							//if (count($crosstabs['options1']) > 0){
							//if (count($crosstabsOptions1All[$t]) > 0){
								//
								$tabelaZadnja1 = ''; //belezi kodo za 1. podvrstico zadnje vrstice
								$tabelaZadnja2 = ''; //belezi kodo za 2. podvrstico zadnje vrstice
								//foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
								foreach ($crosstabsOptions1All[$t] as $ckey1 => $crossVariabla1) {
									
									# prikazujemo eno od treh možnosti					
									if ($this->crosstabClass->crossChk0) {
										# suma po stolpcih
										$crossChk0 = (int)$crosstabs['sumaStolpec'][$ckey1];
										$tabelaZadnja1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum, $steviloVmesnihStolpcevPodvrstic);
									}					
									if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
										# suma po stolpcih v procentih
										if ($this->crosstabClass->crossChk1) {
											$crossChk1 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
											$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
										}
										if ($this->crosstabClass->crossChk2) {
											$crossChk2 = $this->encodeText($this->formatNumber(100, 2, '%'));
											$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
										}
										if ($this->crosstabClass->crossChk3){
											$crossChk3 = $this->encodeText($this->formatNumber( ($crosstabs['sumaSkupna'] > 0 ? (100 * $crosstabs['sumaStolpec'][$ckey1] / $crosstabs['sumaSkupna']) : 0), 2, '%'));
											$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
										}
									}
								}
								
								# zadnja celica z skupno sumo
								if($t == ($steviloTabel-1)){			
									if ($this->crosstabClass->crossChk0) {										
										# skupna suma
										$crossChk0 = (int)$crosstabs['sumaSkupna'];

										//echo "numr: ".$crosstabsOptions1All[$t]."</br>";
										//if (!$crosstabsOptions1All[$t]){
										/* if (!$crosstabsOptions1All[$t] && $reziduali == 0){
											$tabelaZadnja1 .= " & ";											
										} */
										$tabelaZadnja1 .= $this->DisplayLatexCells($crossChk0, $podVrstice, $colNum, $steviloVmesnihStolpcevPodvrstic);
									}
									if ($this->crosstabClass->crossChk1 || $this->crosstabClass->crossChk2 || $this->crosstabClass->crossChk3) {
										# suma po stolpcih v procentih
										if (!$crosstabsOptions1All[$t]){
											$tabelaZadnja2 .= " & ";
										}
										if ($this->crosstabClass->crossChk1) {
											$crossChk1 = $this->encodeText($this->formatNumber(100, 2, '%'));											
											$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk1, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
										}
										if ($this->crosstabClass->crossChk2) {
											$crossChk2 = $this->encodeText($this->formatNumber(100, 2, '%'));
											$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk2, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
										}
										if ($this->crosstabClass->crossChk3) {
											$crossChk3 = $this->encodeText($this->formatNumber(100, 2, '%'));
											$tabelaZadnja2 .= $this->DisplayLatexCells($crossChk3, $podVrstice, $colNum2, $steviloVmesnihStolpcevPodvrstic);
										}
									}
								}
							//}
							/* echo "tabelaZadnja1: $tabelaZadnja1</br>";
								echo "tabelaZadnja2: $tabelaZadnja2</br>"; */
							
							$tabela .= $tabelaZadnja1;	//izpis 1. podvrstice s freq
							if($niSodo && $t == ($steviloTabel-2) && $reziduali){	//ce ni sodo in je predzadnja podtabela in reziduali, dodaj se vse potrebno za prazen stolpec
							//if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
								$tabela .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
							}
							$tabela .= $this->texNewLine;

							if($steviloVmesnihVrstic > 1 && $tabela2!=''){	//ce je potrebno multicol prikazovanje
								if($export_format != 'xls'){
									$tabela .= ' \cline{2-'.$steviloStolpcevParameterTabular.'}';	//je potrebno urediti prvi stolpec tako, da ni crt med celicami
								}
							}else{
								if($export_format != 'xls'){
									$tabela .= $this->horizontalLineTex;
								}
							}							
							//echo "ni sodo $niSodo </br>";	
							if($steviloVmesnihVrstic > 1 && $tabela2!=''){	//ce je potrebno multirow prikazovanje
								$tabela .= $tabelaZadnja2;	//izpis 2. vrstice z odstotki
								if($niSodo && $t == ($steviloTabel-2)  && $reziduali){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
								//if($niSodo && $t == ($steviloTabel-2)){	//ce ni sodo in je predzadnja podtabela, dodaj se vse potrebno za prazen stolpec
									$tabela .= $this->MultiColCellLatex($steviloVmesnihStolpcevPodvrstic, '');
								}
								$tabela .= $this->texNewLine;
								if($export_format != 'xls'){
									$tabela .= $this->horizontalLineTex;
								}
							}
							// skupni sestevki po stolpcih - ZADNJA VRSTICA - konec #############################################################################
							
							$stevec++;
							$tabela .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
						}
					}
				}
			}
			
			/*zakljucek latex tabele*/
			if($presirokaTabela == 0){ //ce ni presiroka tabela
				$tabela .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			}
			/*zaljucek latex tabele - konec */
			
			//echo "showChart: ".$this->sessionData['showChart']."</br>";
			// Izris grafa (ce je vklopljena nastavitev)
			if($this->sessionData['showChart'] == '1' && $creport == false){
				$tabela .= $this->displayCrosstabChart();
			}			
		}		
		//echo "</br> Tex celotne tabele: ".$tabela."</br>";
		return $tabela;
	}
	
	function displayCrosstabChart(){
		global $lang;
		$chart = '';
		
		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		$chartID = implode('_', $this->crosstabClass->variabla1[0]).'_'.implode('_', $this->crosstabClass->variabla2[0]);		
		$chartID .= '_counter_'.$this->counter;
		
		$settings = $this->sessionData[$chartID];
		$imgName = $settings['name'];
		
		// Naslov posameznega grafa
		if($settings['type'] == 1 || $settings['type'] == 4){			
			$title = $this->crosstabVars[0].'/'.$this->crosstabVars[1];
		}
		else{
			$title = $this->crosstabVars[0];
		}
		
		$boldedTitle = $this->returnBold($this->encodeText($title)).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico		
		
		copy('pChart/Cache/'.$imgName,'pChart/Cache/'.$imgName.'.png');
		$texImageOnly = " \\includegraphics[scale=0.75]{".$imgName."} ";	//latex za sliko
		
		$chart .= $this->returnCentered($boldedTitle.$texImageOnly); //vrni sredinsko poravnana naslov in slika
		
		return $chart;
	}
	
	
}
?>