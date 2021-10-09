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

class AnalizaDesc extends LatexAnalysisElement{

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
			

			$loop = SurveyZankaProfiles :: Init($this->anketa['id'], $global_user_id);
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
		
	
	//function displayTableLatex($headFileName, $spremenljivka, $spid, $export_format, $hideEmpty){
	function displayTableLatex($headFileName=null, $spremenljivka=null, $export_format='', $hideEmpty=null){
		global $site_path;
		global $lang;
		global $global_user_id;
		//echo 'funkcija displayTableLatex</br>';
		$this->export_format = $export_format;
		$this->hideEmpty = $hideEmpty;
		$tabela = '';
		
		$this->headFileName = $headFileName;
		
		#preberemo HEADERS iz datoteke
		//SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		# polovimo opisne podatke
		SurveyAnalysis::getDescriptives();

		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 8;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? 'P|' : 'l|');
			}else{
				$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
			}			
		}
		//Priprava parametrov za tabelo - konec
		
		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;

		$tabela .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		if($export_format != 'xls'){
			$tabela .= $this->horizontalLineTex; /*obroba*/
		}		
		//zacetek latex tabele z obrobo za prvo tabelo - konec
		
		/*prva vrstica tabele*/
		/*priprava polja z naslovi stolpcev*/
		$text = array();
		
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_variable']).'}';
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_variable_text1']).'}';
		
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_m']).'}';
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_num_units']).'}';	
		//$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_povprecje1']).'}';
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_povprecje_odstotek1']).'}';
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_odklon']).'}';
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_min']).'}';
		$text[] = '\textbf{'.$this->encodeText($lang['srv_analiza_opisne_max']).'}';
		/*priprava polja z naslovi stolpcev - konec*/
		
		$tabela .= $this->tableRow($text);		
		/*prva vrstica tabele - konec*/		
		
		# dodamo še kontrolo če kličemo iz displaySingleVar 
		if (isset($_spid) && $_spid !== null) {
			SurveyAnalysis::$_HEADERS = array($_spid => SurveyAnalysis::$_HEADERS[$_spid]);
		}

		/*izpis ostalih vrstic*/
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			/* preverjamo ali je meta*/
			if ($spremenljivka['tip'] != 'm'
			 && ( count(SurveyAnalysis::$_FILTRED_VARIABLES) == 0 || (count(SurveyAnalysis::$_FILTRED_VARIABLES) > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ))
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES) 
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {

				$show_enota = false;
				/* preverimo ali imamo samo eno variablo in če iammo enoto*/
				if ((int)$spremenljivka['enota'] != 0 || $spremenljivka['cnt_all'] > 1 ) {
					$show_enota = true;
				}
				
				/* izpišemo glavno vrstico z podatki*/
				$_sequence  = null;
				/* za enodimenzijske tipe izpišemo podatke kar v osnovni vrstici*/
				if (!$show_enota) {  
//				 	if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3  
//				 		|| $spremenljivka['tip'] == 4 || $spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					//self::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
					$tabela .= $this->displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
				} else {
				if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
					$variable = $spremenljivka['grids'][0]['variables'][0];
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$show_enota = false;
				}
					//self::displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
					$tabela .= $this->displayDescriptivesSpremenljivkaRow($spid, $spremenljivka,$show_enota,$_sequence);
					/*zloopamo skozi variable*/
					$_sequence = null;
					$grd_cnt=0;
					if (count($spremenljivka['grids']) > 0)				 	
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						
						if (count($spremenljivka['grids']) > 1 && $grd_cnt !== 0 && $spremenljivka['tip'] != 6) {
							$grid['new_grid'] = true;
						}
						$grd_cnt++;
						/* dodamo dodatne vrstice z albelami grida*/
						if (count ($grid['variables']) > 0)
						foreach ($grid['variables'] AS $vid => $variable ){
							/* dodamo ostale vrstice*/
							$do_show = ($variable['other'] !=1 && ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || $spremenljivka['tip'] == 5 || $spremenljivka['tip'] == 8 )) 
								? false
								: true;
								if ($do_show) {
									//self::displayDescriptivesVariablaRow($spremenljivka,$grid,$variable,$_css);
									$tabela .= $this->displayDescriptivesVariablaRow($spremenljivka,$grid,$variable,$_css);
									
								}
							$grid['new_grid'] = false;
								
						}
					}
				 } //else: if (!$show_enota)
			 } // end if $spremenljivka['tip'] != 'm'
		} // end foreach  SurveyAnalysis::$_HEADERS
		
		
		/*zakljucek latex tabele*/
		$tabela .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		/*zaljucek latex tabele - konec */
		//echo "</br> Tex celotne tabele: ".$tabela."</br>";
		return $tabela;
	}
}
?>