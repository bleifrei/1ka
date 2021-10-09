<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');

	
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
	

/** Class za generacijo izvoza v Latex
 *
 * @desc: po novem je potrebno form elemente generirati ro�no kot slike
 *
 */
class AnalizaTTest extends LatexAnalysisElement{

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $ttestClass = null;		//ttest class
	
	var $ttestVars;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...

	protected $texNewLine = '\\\\ ';
	protected $export_format;
	protected $horizontalLineTex = "\\hline ";

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'ttest')
	{
		global $site_path;
		global $global_user_id;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->anketa['podstran'] = $podstran;
			// create new PDF document
			//$this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
	function getFile($fileName=null)
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
	
	public function displayTTestTable($ttest=null, $ttestClass=null, $export_format='', $sessionData=null) {
		global $lang;
		$tabela = '';
		
		$this->ttestClass = $ttestClass;		
		$this->sessionData = $sessionData;
		
		# preverimo ali imamo izbrano odvisno spremenljivko
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		
		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {
			
			$spr_data_1 = $this->ttestClass->_HEADERS[$spid1];
			if ($grid1 == 'undefined') {

				# imamp lahko več variabel
				$seq = $seq1;
				foreach ($spr_data_1['grids'] as $gkey => $grid ) {
						
					foreach ($grid['variables'] as $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($sequence == $seq) {
							$sprLabel1 = '('.$variable['variable'].') '. $variable['naslov'];
						}
					}
				}
			} else {
				# imamo subgrid
				$sprLabel1 = '('.$spr_data_1['grids'][$grid1]['variable'].') '. $spr_data_1['grids'][$grid1]['naslov'];
			}
			
			//Priprava parametrov za tabelo
			$steviloStolpcevParameterTabular = 10;
			$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
			$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
			$parameterTabular = '|';
			
			for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
				//ce je prvi stolpec
				if($i == 0){
					$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');					
					//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');					
				}else{
					$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
					//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
					//$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
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
				
			# polovio labele
			$spid2 = $this->sessionData['ttest']['spr2'];
			$sprLabel2 =  trim(str_replace('&nbsp;','',$this->sessionData['ttest']['label2']));
			$label1 = $this->ttestClass->getVariableLabels($this->sessionData['ttest']['sub_conditions'][0]);
			$label2 = $this->ttestClass->getVariableLabels($this->sessionData['ttest']['sub_conditions'][1]);
			
			$this->ttestVars = array($sprLabel1, $sprLabel2);
			
			if($export_format != 'xls'){
				//$poravnava = "C";
				$poravnava = "c";
			}else{
				$poravnava = "c";
			}
			
			$poravnava = "c";
			
			$tabela .= " & \multicolumn{".$steviloOstalihStolpcev."}{".$poravnava."|}{".$this->returnBold($this->encodeText($sprLabel1))."} ".$this->texNewLine;
			//$tabela .= ' & \multicolumn{'.$steviloOstalihStolpcev.'}{>{\hsize=\dimexpr '.($steviloOstalihStolpcev).'\hsize + '.($steviloOstalihStolpcev).'\tabcolsep + \arrayrulewidth}X|}{'.$this->returnBold($this->encodeText($sprLabel1)).'} '.$this->texNewLine;
			// prva vrstica - konec

			// druga vrstica
			if($export_format != 'xls'){
				$tabela .= "\\cline{2-".$steviloStolpcevParameterTabular."} ";	//horizontalna vrstica od 2 do zadnje celice
			}
			$druga = array();
			$druga[] = $this->returnBold($this->encodeText($sprLabel2));
			$druga[] = 'n';
			$druga[] = 'x';
			$druga[] = 's$^2$';
			$druga[] = 'se(x)';
			$druga[] = '$\pm$1,96$\times$se(x)';
			$druga[] = 'd';
			$druga[] = 'se(d)';
			$druga[] = 'Sig.';
			$druga[] = 't';
			
			$brezHline = $this->getBrezHline($export_format);
			
			$tabela .= $this->tableRow($druga, $brezHline)." ";	
			// druga vrstica - konec

			// vrstici s podatki
			$zadnjiStolpecDvojnihVrstic = 6;
			
			//tretja vrstica
			$tretja = array();			
			$tretja[] = $this->encodeText($label1); //1. stolpec
			$tretja[] = $this->formatNumber($ttest[1]['n'], 0);
			$tretja[] = $this->formatNumber($ttest[1]['x'], 3);
			$tretja[] = $this->formatNumber($ttest[1]['s2'], 3);
			$tretja[] = $this->formatNumber($ttest[1]['se'], 3);
			$tretja[] = $this->formatNumber($ttest[1]['margin'], 3);
			$tretja[] = '';
			$tretja[] = '';
			$tretja[] = '';
			$tretja[] = '';			
			$tabela .= $this->tableRow($tretja, 1)." ";	//izpisi tretjo vrstico brez horizontalne crte
			//tretja vrstica - konec

			//cetrta vrstica
			$cetrta = array();
			if($export_format != 'xls'){			
				$cetrta[] = '\cline{1-'.$zadnjiStolpecDvojnihVrstic.'} '.$this->encodeText($label2); //1. stolpec, //crta samo do dolocenega stolpca
			}else{
				$cetrta[] = $this->encodeText($label2); //1. stolpec, //crta samo do dolocenega stolpca
			}
			$cetrta[] = $this->formatNumber($ttest[2]['n'], 0);
			$cetrta[] = $this->formatNumber($ttest[2]['x'], 3);
			$cetrta[] = $this->formatNumber($ttest[2]['s2'], 3);
			$cetrta[] = $this->formatNumber($ttest[2]['se'], 3);
			$cetrta[] = $this->formatNumber($ttest[2]['margin'], 3);
			$cetrta[] = $this->formatNumber($ttest['d'], 3);
			$cetrta[] = $this->formatNumber($ttest['sed'], 3);
			$cetrta[] = $this->formatNumber($ttest['sig'], 3);
			$cetrta[] = $this->formatNumber($ttest['t'], 3);
			$tabela .= $this->tableRow($cetrta, $brezHline)." ";
			//cetrta vrstica - konec

			// vrstici s podatki - konec

			//zaljucek latex tabele z obrobo za prvo tabelo		
			$tabela .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
			//zaljucek latex tabele z obrobo za prvo tabelo - konec
		}
		
		return $tabela;
	}
	
	function displayChart($sessionData=null, $ttestClass=null, $anketa=null, $creport=false){
		global $lang;
		
		$this->sessionData = $sessionData;
		$this->ttestClass = $ttestClass;
		$this->anketa = $anketa;
		
		$texImage = '';
		
		$tableChart = new SurveyTableChart($this->anketa['id'], $this->ttestClass, 'ttest');
		$tableChart->setTTestChartSession();
		
		// updatamo session iz baze
		$this->sessionData = SurveyUserSession::getData();
		
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
		$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
		$chartID = $sub1.'_'.$sub2.'_'.$spid1.'_'.$seq1.'_'.$grid1;
	
		$settings = $this->sessionData['ttest_charts'][$chartID];
		$imgName = $settings['name'];
		
		copy('pChart/Cache/'.$imgName,'pChart/Cache/'.$imgName.'.png');
		
		if($creport==false){
			// Naslov posameznega grafa
			$title = $lang['srv_chart_ttest_title'].':'.$this->texNewLine;
			$title .= $this->encodeText($this->ttestVars[0]);
			$title .= $this->encodeText('/');
			$title .= $this->encodeText($this->ttestVars[1]);		
			$boldedTitle = $this->returnBold($title).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico		
		}else{
			$boldedTitle = '';
		}
		$texImageOnly = " \\includegraphics[scale=0.75]{".$imgName."} ";	//latex za sliko
		
		$texImage .= $this->returnCentered($boldedTitle.$texImageOnly); //vrni sredinsko poravnana naslov in slika
		
		return $texImage;
	}
	
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text='', $length=64, $tail="...")
	{
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length)
		{
			for($i=1;$text[$length-$i]!=" ";$i++)
			{
				if($i == $length)
				{
					return substr($text,0,$length) . $tail;
				}
			}
		$text = substr($text,0,$length-$i+1) . $tail;
		}
		return $text;
	}

	function drawLine()
	{
		$cy = $this->pdf->getY();
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
	}

	function setUserId($usrId=null) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

	function formatNumber($value=null, $digit=0, $sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
	
	function getCellHeight($string='', $width=null){

		$this->pdf->startTransaction();
		// get the number of lines calling you method
		$linecount = $this->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();

		$height = ($linecount <= 1) ? 4.7 : $linecount * ($this->pdf->getFontSize() * $this->pdf->getCellHeightRatio()) + 2;
		
		return $height;
	}
	
}

?>