<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
/* 	include_once('../exportclases/class.pdfIzvozAnalizaFunctions.php');
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
	

/** Class za generacijo izvoza v Latex
 *
 * @desc: po novem je potrebno form elemente generirati rocno kot slike
 *
 */
class AnalizaMean extends LatexAnalysisElement{

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $meansClass = null;		//means class
	
	var $meanData1;
	var $meanData2;
	
	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
	protected $texNewLine = '\\\\ ';
	protected $export_format;
	protected $horizontalLineTex = "\\hline ";


	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $podstran = 'mean')
	{
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa['id']) )
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
	
	public function displayMeansTable($_means=null, $meansClass=null, $export_format='') {
		global $lang;
		$tabela = '';
		$this->meansClass = $meansClass;
		
		#število vratic in število kolon
		$cols = count($_means);
		
		# preberemo kr iz prvega loopa
		$rows = count($_means[0]['options']);
		
		// sirina ene celice
		$singleWidth = round( 180 / $cols / 2 );
		
		// visina prve vrstice
/* 		$firstHeight = 0;
		for ($i = 0; $i < $cols; $i++) {				
			$label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);
			$firstHeight = ($firstHeight > $this->getCellHeight($this->encodeText($label1), $singleWidth*2)) ? $firstHeight : $this->getCellHeight($this->encodeText($label1), $singleWidth*2);
		} */
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 3;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'c|');
				//$parameterTabular .= ($export_format == 'pdf' ? 'P|' : 'l|');
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
		
		
		//prva vrstica tabele
		$label2 = $this->meansClass->getSpremenljivkaTitle($_means[0]['v2']);	
					
		//$this->pdf->MultiCell(80, $firstHeight, $this->encodeText($label2), 'TLR', 'C', 0, 0, 0 ,0, true);
		$prva = '';
		for ($i = 0; $i < $cols; $i++) {
			$label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);
			//$this->pdf->MultiCell($singleWidth*2, $firstHeight, $this->encodeText($label1), 1, 'C', 0, 0, 0 ,0, true);
			$prva .= $label1.' ';
		}
		
		$steviloPodStolpcev1 = $cols+1;
		
		if($export_format != 'xls'){
			$poravnava = "C";
		}else{
			$poravnava = "c";
		}

		############
		$steviloTabColSep = ($steviloPodStolpcev1-1)*2;
		$steviloArrayrulewidth = ($steviloPodStolpcev1-1);
		
		if($export_format=='pdf'){
			$tabela .= $this->encodeText($label2)." & \multicolumn{".$steviloPodStolpcev1."}{>{\hsize=\dimexpr".$steviloPodStolpcev1."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}".$poravnava."|}{".$this->encodeText($prva)."} ".$this->texNewLine;
		}elseif($export_format=='rtf'){
			$tabela .= $this->encodeText($label2)." & \multicolumn{".$steviloPodStolpcev1."}{".$poravnava."|}{".$this->encodeText($prva)."} ".$this->texNewLine;
		}
		############
		
		//$tabela .= $this->encodeText($label2)." & \multicolumn{".$steviloPodStolpcev1."}{>{\hsize=\dimexpr".$steviloPodStolpcev1."\hsize+".$steviloPodStolpcev1."\\tabcolsep+\arrayrulewidth\\relax}".$poravnava."|}{".$this->encodeText($prva)."} ".$this->texNewLine;
		//$tabela .= $this->encodeText($label2)." & \multicolumn{".$steviloPodStolpcev1."}{X|}{".$this->encodeText($prva)."} ".$this->texNewLine;
		//$tabela .= $this->encodeText($label2)." & \multicolumn{".$steviloPodStolpcev1."}{".$poravnava."|}{".$this->encodeText($prva)."} ".$this->texNewLine;
		//$tabela .= $this->encodeText($label2)." & \multicolumn{".$steviloPodStolpcev1."}{C|}{".$this->encodeText($prva)."} ".$this->texNewLine;		
		//$tabela .= $this->encodeText($label2).' & \multicolumn{'.$steviloPodStolpcev1.'}{>{\hsize=\dimexpr '.($steviloPodStolpcev1).'\hsize + '.($steviloPodStolpcev1).'\tabcolsep + \arrayrulewidth}X|}{'.$this->encodeText($prva).'} '.$this->texNewLine;		
		
		//prva vrstica tabele - konec
		
		//druga vrstica tabele
		//$this->pdf->MultiCell(80, 7, $this->encodeText(''), 'BLR', 'C', 0, 0, 0 ,0, true);
		//echo "stolpci: ".$cols."</br>";
		//echo "vrstice: ".$rows."</br>";
		
		$druga = array();
		$steviloPodStolpcev = $steviloPodStolpcev1 + 1;
		//$tabela .= "\\cline{2-".$steviloPodStolpcev."} & ";	//horizontalna vrstica od 2 do zadnje celice
		if($export_format != 'xls'){			
			$tabela .= "\\cline{2-".$steviloPodStolpcev."} ";	//horizontalna vrstica od 2 do zadnje celice
		}
		
		$tabela .= " & ";
		
		$brezHline = $this->getBrezHline($export_format);
		
		for ($i = 0; $i < $cols; $i++) {
			$druga[] = $this->encodeText($lang['srv_means_label']);
			$druga[] = $this->encodeText($lang['srv_means_label4']);
			//$this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_means_label']), 1, 'C', 0, 0, 0 ,0, true);
			//$this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_means_label4']), 1, 'C', 0, 0, 0 ,0, true);
		}
		//$this->pdf->MultiCell(1, 7, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
		$tabela .= $this->tableRow($druga, $brezHline)." ";		
		//druga vrstica tabele - konec
		
		//vrstice s podatki
		if (count($_means[0]['options']) > 0) {
			
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {
				$dataVrstica = array();
				$variabla = $crossVariabla2['naslov'];
				# če ni tekstovni odgovor dodamo key
				if ($crossVariabla2['type'] !== 't' ) {
					if ($crossVariabla2['vr_id'] == null) {
						$variabla .= ' ( '.$ckey2.' )';
					} else {
						$variabla .= ' ( '.$crossVariabla2['vr_id'].' )';
					}
				}
				//$this->pdf->MultiCell(80, 7, $this->encodeText($variabla), 1, 'C', 0, 0, 0 ,0, true);
				$dataVrstica[] = $this->encodeText($variabla);
				
				# celice z vsebino
				for ($i = 0; $i < $cols; $i++) {	
					//$dataVrstica[] = $this->encodeText($this->meansClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL')));
					$dataVrstica[] = $this->encodeText(self::formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL')));
					$dataVrstica[] = $this->encodeText((int)$_means[$i]['sumaVrstica'][$ckey2]);
					//$this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
					//$this->pdf->MultiCell($singleWidth, 7, $this->encodeText((int)$_means[$i]['sumaVrstica'][$ckey2]), 1, 'C', 0, 0, 0 ,0, true);
				}
				//$this->pdf->MultiCell(1, 7, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
				$tabela .= $this->tableRow($dataVrstica, $brezHline)." ";
			}			
		}
		// vrstice s podatki - konec
		
		//SKUPAJ
		$skupajVrstica = array();
		//$this->pdf->MultiCell(80, 7, $this->encodeText($lang['srv_means_label3']), 1, 'C', 0, 0, 0 ,0, true);
		$skupajVrstica[] = $this->encodeText($lang['srv_means_label3']);
		for ($i = 0; $i < $cols; $i++) {
			//$skupajVrstica[] = $this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL')));
			$skupajVrstica[] = $this->encodeText(self::formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL')));
			//$this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
			$skupajVrstica[] = $this->encodeText((int)$_means[$i]['sumaSkupna']);
			//$this->pdf->MultiCell($singleWidth, 7, $this->encodeText((int)$_means[$i]['sumaSkupna']), 1, 'C', 0, 0, 0 ,0, true);
		}
		//$this->pdf->MultiCell(1, 7, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
		$tabela .= $this->tableRow($skupajVrstica, $brezHline)." ";
		//SKUPAJ - konec
		
		//zaljucek latex tabele z obrobo za prvo tabelo		
		$tabela .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za prvo tabelo - konec
		return $tabela;
	}
	
	public function displayChart($counter=null, $meanData1=null, $meanData2=null, $sessionData=null){
		global $lang, $site_path;
		$texImage = '';

		$path2ChartsImgs = $site_path.'admin/survey/pChart/Cache/';

		//echo $path2ChartsImgs."</br>";
		
		$variables1 = $meanData1;
		$variables2 = $meanData2;
		
 		$pos1 = floor($counter / count($variables2));
		$pos2 = $counter % count($variables2);
		
		$chartID = implode('_', $variables1[$pos1]).'_'.implode('_', $variables2[$pos2]);
		$chartID .= '_counter_'.$counter;
		
		$settings = $sessionData['mean_charts'][$chartID];
		$imgName = $settings['name'];
		
		$size = getimagesize('pChart/Cache/'.$imgName);
		$height = $size[1] / 4;
	
		copy('pChart/Cache/'.$imgName,'pChart/Cache/'.$imgName.'.png');
		//$this->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=200, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);
		
		$texImage .= "\\begin{center} \\includegraphics[scale=0.75]{".$path2ChartsImgs.$imgName."} \\end{center}";	//latex za sliko, ki je sredinsko poravnana

		//echo "img name: ".$imgName."</br>";
		
		//unlink('pChart/Cache/'.$imgName.'.png');
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