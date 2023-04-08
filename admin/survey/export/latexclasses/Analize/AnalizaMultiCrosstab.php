<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFunctions.php');
	require_once('../exportclases/class.enka.pdf.php');
	
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

	define ('SNIPPET_LENGTH', 300);
	

/** Class za generacijo latex
 *
 * 
 *
 */
class AnalizaMultiCrosstab extends LatexAnalysisElement {

	var $anketa;// = array();				// trenutna anketa

	var $pi=array('canCreate'=>false); 		// za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $multiCrosstabClass = null;		// crosstab class
	
	private $cellWidth = 1;					// sirina celice s podatki
	private $cellHeight = 1;				// visina celice s podadtki
	private $cellSpan = 1;					// stevilo vrstic v celici s podatki
	
	protected $texNewLine = '\\\\ ';
	protected $export_format;
	protected $horizontalLineTex = "\\hline ";
	protected $show_valid_percent;
	protected $texBigSkip = '\bigskip';
	protected $spaceBetweenTables = ' \newline \vspace*{1 cm} \newline';
	
	protected $tableSettingsNumerus;
	protected $tableSettingsPercent;
	protected $tableSettingsSums;
	protected $tableSettingsAvgVar;
	protected $tableSettingsDelezVar;
	

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null){
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		//if ( is_numeric($anketa) ){
		if ( is_numeric($anketa['id']) ){
			//$this->anketa['id'] = $anketa;
			$this->anketa = $anketa;
			// create new PDF document
			//$this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			
			return false;
		}
		$_GET['a'] = A_ANALYSIS;
		
		
		//ustvarimo multicrosstabs objekt		
		//$this->multiCrosstabClass = new SurveyMultiCrosstabs($anketa);

		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) ){
			$this->anketa['uid'] = $global_user_id;
			
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);		
			SurveyDataSettingProfiles::Init($this->anketa['id']);
		}
		else
			return false;
		
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	// SETTERS && GETTERS
	function checkCreate(){
		return $this->pi['canCreate'];
	}
	
	function getFile($fileName=''){
		//Close and output PDF document		
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}

	public function displayTable($multiCrosstabClass=null, $export_format=''){
		global $site_path;
		global $lang;
		
		$tabela = '';
		
		$this->multiCrosstabClass = $multiCrosstabClass;
		
		// Napolnimo variable ki so ze izbrane
		$this->multiCrosstabClass->getSelectedVars();
		
		// Izpisemo naslov tabele
		//echo "naslov tabele: ".$this->encodeText($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['title'])."</br>";
		$tabela .= '\textbf{'.$this->encodeText($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['title']).'}'.$this->texBigSkip.$this->texNewLine;
		//echo $tabela."</br>";
/*  		$this->pdf->setFont('','B','10');
		$this->pdf->MultiCell(150, 5, $this->encodeText($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['title']), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
		$this->pdf->setFont('','','6'); */
			
		
		// TABELA
		
		// Najprej izracunamo dimenzije
		$lineHeight = 6;
		$fullWidth = 270;
		
		if($this->multiCrosstabClass->rowSpan == 0)
			//$colspan = 1;
			$colspan = 2;
		elseif(!$this->multiCrosstabClass->rowLevel2)
			$colspan = 2;
		else
			$colspan = 4;
			
		$metaWidth = $colspan * 35;
		$dataWidth = $fullWidth - $metaWidth;
		
		$dataCellSpan = 0;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1)
			$dataCellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1)
			$dataCellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] != '')
			$dataCellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] != '')
			$dataCellSpan++;
		
		$this->cellSpan = ($dataCellSpan == 0) ? 1 : $dataCellSpan;		
		$this->cellHeight = ($this->cellSpan > 1) ? $this->cellSpan * 5 : $lineHeight;
		
		$this->tableSettingsNumerus = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'];
		$this->tableSettingsPercent = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'];
		$this->tableSettingsSums = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'];
		$this->tableSettingsAvgVar = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'];
		$this->tableSettingsDelezVar = $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'];
		
		$steviloPodstolpcevV1 = array();	//hrani stevilo podstolpcev za 1. vrstico
		$steviloPodstolpcevV2 = array();	//hrani stevilo podstolpcev za 2. vrstico
		$steviloPodstolpcevV3 = array();	//hrani stevilo podstolpcev za 3. vrstico
		$indeksMultiRow = array();	//hrani, kje je potrebna crta med vrsticami tabele (1) in kje ne (0)
		
		//$tabela .= $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'];
		
		$cntVerVars=0;
		foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
			$cntVerVars++;
		}
		//echo "cntVerVars: ".$cntVerVars."</br>";

		if($this->multiCrosstabClass->colSpan == 0){
			//$this->cellWidth = $dataWidth/2;
			//$this->pdf->MultiCell($metaWidth, 0, '', 'B', 'L', 0, 1, 0 ,0, true);
			$steviloPodstolpcev = 1;
			$steviloPodstolpcevV1[] = $steviloPodstolpcev;
		}
		// Imamo 2 nivoja
		elseif($this->multiCrosstabClass->colLevel2){
			//echo "vertikalno izbrane sprem. 1. vrstica, ko imamo 2 nivoja</br>";
			
			$this->cellWidth = $dataWidth / $this->multiCrosstabClass->colSpan;
			
			//pridobivanje podatkov zadnja vrstica
			if(count($this->multiCrosstabClass->selectedVars['ver'])){			
				$cetrtaVrsticaVert = array();
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					$steviloPodstolpcev = 0;					
					$cetrtaVrsticaVertTmp = array();	//za pridobitev stevila podstolpcev za posamezno spremenljivko, ce jih je vec
					// ce imamo childe na 2. nivoju
					if(count($parentVar['sub']) > 0){						
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							foreach($parentVar['sub'] as $var){				
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
									//$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->snippet($suboption, 25), 1, 'C', 0, 0, 0 ,0, true, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
									if($export_format != 'xls'){
										$cetrtaVrsticaVertText = $this->snippet($suboption, 25);
										$cetrtaVrsticaVertTmpText = $this->snippet($suboption, 25);
									}else{
										$cetrtaVrsticaVertText = $suboption;
										$cetrtaVrsticaVertTmpText = $suboption;
									}
									$cetrtaVrsticaVert[] = $cetrtaVrsticaVertText;
									$cetrtaVrsticaVertTmp[] = $cetrtaVrsticaVertTmpText;
									$indeksMultiRow[] = 1;
								}
								$steviloPodstolpcevV3[] = count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
							}
							$steviloPodstolpcev = count($cetrtaVrsticaVertTmp);
						}
					}
 					else{
						//$this->pdf->MultiCell($this->cellWidth*count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']), $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
						
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							//$this->pdf->MultiCell($this->cellWidth, $lineHeight*2, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);
						
							/* $cetrtaVrsticaVert[] = $this->snippet($option, 25);
							$cetrtaVrsticaVertTmp[] = $this->snippet($suboption, 25); */	
							if($export_format != 'xls'){
								$cetrtaVrsticaVertText = $this->snippet($option, 25);
								$cetrtaVrsticaVertTmpText = $this->snippet($suboption, 25);
							}else{
								$cetrtaVrsticaVertText = $option;
								$cetrtaVrsticaVertTmpText = $suboption;
							}
							$cetrtaVrsticaVert[] = $cetrtaVrsticaVertText;
							$cetrtaVrsticaVertTmp[] = $cetrtaVrsticaVertTmpText;
							$indeksMultiRow[] = 0;
						}
						$steviloPodstolpcev = count($cetrtaVrsticaVertTmp);
						$steviloPodstolpcevV3[] = count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']);
					}
					if($steviloPodstolpcev!=0){
						$steviloPodstolpcevV1[] = $steviloPodstolpcev;
					}						
				}
			}
			//pridobivanje podatkov zadnja vrstica - konec
			

			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			//$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'L', 0, 0, 0 ,0, true);
			
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$prvaVrsticaVert = array();
				
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					// Ce imamo tudi 2. nivo pri doloceni spremenljivki
					if(count($var['sub']) > 0){
						$rowspan = 1;
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['sub'][0]['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
						if($export_format != 'xls'){
							$naslov = $this->encodeText($this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25));
						}else{
							$naslov = $this->encodeText($this->multiCrosstabClass->variablesList[$var['spr']]['naslov']);
						}
					}
					else{
						$rowspan = 2;
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
						if($export_format != 'xls'){
							$naslov = '\multirow{2}{*}{ '.$this->encodeText($this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25)).' }';
						}else{
							$naslov = $this->encodeText($this->multiCrosstabClass->variablesList[$var['spr']]['naslov']);
						}
					}					
					//$this->pdf->MultiCell($width, $lineHeight*$rowspan, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 1, 'C', 0, 0, 0 ,0, true);
					
					//$naslov = $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
					//$naslov = $this->encodeText($this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25));
					
					$prvaVrsticaVert[] = $naslov;
					//echo "naslov: ".$naslov."</br>";
				}
			}
			//$this->pdf->MultiCell(1, $lineHeight, '', 0, 'L', 0, 1, 0 ,0, true);
			
			// Izris vrstic za 2. nivo - 3. in 4. vrstica
			//$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'L', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$tretjaVrsticaVert = array();
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){					
					// ce imamo childe na 2. nivoju
					if(count($parentVar['sub']) > 0){
						//$tretjaVrsticaVert = array();
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$parentVar['sub'][0]['spr']]['options']);
							foreach($parentVar['sub'] as $var){				
								//$this->pdf->MultiCell($width, $lineHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 1, 'C', 0, 0, 0 ,0, true);
								if($export_format != 'xls'){
									$tretjaVrsticaVert[] = $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
								}else{
									$tretjaVrsticaVert[] = $this->multiCrosstabClass->variablesList[$var['spr']]['naslov'];
								}
								
							}
							
						}
					}
					else{
						//$this->pdf->MultiCell($this->cellWidth*count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']), $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
						
						//$tretjaVrsticaVert[] = '';
						$tretjaVrsticaVert[] = '\multirow{2}{*}{}';
						
						
					}
				}
				//echo "3. vrstica podvrstice: ".count($tretjaVrsticaVert)."</br>";
			}
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			//$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'L', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$drugaVrsticaVert = array();
				
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					// Ce imamo tudi 2. nivo pri doloceni spremenljivki
					if(count($var['sub']) > 0){	
						$drugaVrsticaVertTmp = array();
						//$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['sub'][0]['spr']]['options']);
						foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
							//$this->pdf->MultiCell($width, $lineHeight, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);
							if($export_format != 'xls'){
								$drugaVrsticaVert[] = $this->snippet($option, 25);
								$drugaVrsticaVertTmp[] = $this->snippet($option, 25);
							}else{
								$drugaVrsticaVert[] = $option;
								$drugaVrsticaVertTmp[] = $option;
							}
						}
						
						foreach($drugaVrsticaVertTmp as $druga){
							$steviloPodstolpcev = 0;
							for($i=0;$i<count($var['sub']);$i++){
								$steviloPodstolpcev = $steviloPodstolpcev + count($this->multiCrosstabClass->variablesList[$var['sub'][$i]['spr']]['options']);								
							}
							$steviloPodstolpcevV2[] = $steviloPodstolpcev;
						}
					}
					else{
						$drugaVrsticaVert[] = '';
						$steviloPodstolpcevV2[] = count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
					}
				}
			}
			//$this->pdf->MultiCell(1, $lineHeight, '', 0, 'L', 0, 1, 0 ,0, true);		
		}
		// Imamo samo 1 nivo
		else{
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica
			//echo "Samo 1 nivo </br>";
			// Izracunamo sirine celic
			$this->cellWidth = $dataWidth / $this->multiCrosstabClass->fullColSpan;			
			
			//$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
			
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$prvaVrsticaVert = array();
				
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					$naslov = $this->multiCrosstabClass->variablesList[$var['spr']]['naslov'];
					
					if($naslov!=''||($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2)){
						if($export_format != 'xls'){
							$prvaVrsticaVert[] = $this->snippet($naslov, 35);
						}else{
							$prvaVrsticaVert[] = $naslov;
						}
					}
					
					//$prvaVrsticaVert[] = $this->snippet($naslov, 35);
					
					//$sprWidth = count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) * $this->cellWidth;
					//if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2)
						//$sprWidth += $this->cellWidth;					
					//$this->pdf->MultiCell($sprWidth, $lineHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 35), 1, 'C', 0, 0, 0 ,0, true);
					
				}
				
				//$this->pdf->MultiCell(1, $lineHeight, '', 0, 'C', 0, 1, 0 ,0, true);
				//$tabela .= $this->tableRow($prvaVrsticaVert);
				//echo $tabela;
			}
			// Nimamo nobene vertikalne spremenljivke in 2 horizontalni
			elseif($this->multiCrosstabClass->rowLevel2){
				//$this->pdf->MultiCell($dataWidth, $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
				echo "Nimamo nobene vertikalne spremenljivke in 2 horizontalni </br>";
			}

			
 			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$drugaVrsticaVert = array();
				
				// Iracunamo visino najvisje celice
				$cellHeight = $lineHeight;
/* 				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){

					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
 						$height = $this->getCellHeight($this->snippet($option, 25), $this->cellWidth);
						
						$cellHeight = ($height > $cellHeight) ? $height : $cellHeight;
					}
				} */
			
				//$this->pdf->MultiCell($metaWidth, $cellHeight, '', 'B', 'C', 0, 0, 0 ,0, true);
			
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					$steviloPodstolpcev = 0;	
					// Loop cez variable spremenljivke
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
						//$this->pdf->MultiCell($this->cellWidth, $cellHeight, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);
						if($export_format != 'xls'){
							$drugaVrsticaVert[] = $this->snippet($option, 25);
						}else{
							$drugaVrsticaVert[] = $option;
						}
					}
					
					$steviloPodstolpcev = count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);					
					
					// Suma (ce jo imamo vklopljeno)
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2){						
						//$this->pdf->MultiCell($this->cellWidth, $cellHeight, $lang['srv_analiza_crosstab_skupaj'], 1, 'C', 0, 0, 0 ,0, true);	
						$drugaVrsticaVert[] = $lang['srv_analiza_crosstab_skupaj'];
						$steviloPodstolpcev++;
					}

/* 					if($colspan<4){
						$steviloPodstolpcevV2[] = $steviloPodstolpcev;
					}elseif($steviloPodstolpcev!=0&&$colspan==4){
						$steviloPodstolpcevV2[] = $steviloPodstolpcev;
					}	 */		
					if($steviloPodstolpcev!=0){		
						$steviloPodstolpcevV1[] = $steviloPodstolpcev;		
					}
					
					//$steviloPodstolpcevV2[] = $steviloPodstolpcev;
				}
				//$tabela .= $this->tableRow($drugaVrsticaVert);
				//$this->pdf->MultiCell(1, $cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
			}
		}
		
		##########################################################################################
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis*/
		$steviloStolpcevParameterTabular = $this->getSteviloPodstolpcev($steviloPodstolpcevV1);
		$steviloStolpcevParameterTabular = $steviloStolpcevParameterTabular + $colspan;
		//echo "Stevilo stolpcev: ".$steviloStolpcevParameterTabular."</br>";
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		
		$cntHorVars=0;
		foreach($this->multiCrosstabClass->selectedVars['hor'] as $var){
			$cntHorVars++;
		}
		
		//echo "cntHorVars: ".$cntHorVars."</br>";
		if($cntHorVars>=1||$this->multiCrosstabClass->rowSpan == 0){
			$parameterTabular = '|';
		}		
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			$parameterTabular .= ($export_format == 'pdf' ? 'c|' : 'c|');			
		}
		/*Priprava parametrov za tabelo in ostala polja za nadaljnji izpis - konec*/

		//zacetek latex tabele z obrobo	za prvo tabelo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tabela .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		##########################################################################################
		if($export_format != 'xls'){
			$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica na vrhu tabele
		}

		$tabela .= $this->MultiColCellLatex($colspan, '', 1); //prazne celice v prvi vrstici		
		//$tabela .= $this->tableRow($prvaVrsticaVert, 1, 0, 0, $steviloPodstolpcevV1);	//izpis prve vrstice tabele
		$tabela .= $this->tableRow($prvaVrsticaVert, 1, 0, 0, '', $export_format, $steviloPodstolpcevV1);	//izpis prve vrstice tabele
		
		//prekinjena horizontalna vrstica po prvi vrstici
		if($export_format != 'xls'){
			$tabela .= $this->urediCrteTabele($indeksMultiRow, $colspan, $steviloStolpcevParameterTabular);
		}
		//prekinjena horizontalna vrstica po prvi vrstici - konec
		
		if(count($drugaVrsticaVert)){
			$tabela .= $this->MultiColCellLatex($colspan, '', 1); //prazne celice v drugi vrstici
			if(count($steviloPodstolpcevV2)){
				//$tabela .= $this->tableRow($drugaVrsticaVert, 1, 0, 0, $steviloPodstolpcevV2);
				$tabela .= $this->tableRow($drugaVrsticaVert, 1, 0, 0, '', $export_format, $steviloPodstolpcevV2);
			}else{
				$tabela .= $this->tableRow($drugaVrsticaVert, 1);
			}		
			
			//prekinjena horizontalna vrstica po drugi vrstici
			if($export_format != 'xls'){
				$tabela .= $this->urediCrteTabele($indeksMultiRow, $colspan, $steviloStolpcevParameterTabular);		
			}
			//prekinjena horizontalna vrstica po drugi vrstici - konec
		}
		
		if(count($tretjaVrsticaVert)){
			$tabela .= $this->MultiColCellLatex($colspan, '', 1); //prazne celice v drugi vrstici
			if(count($steviloPodstolpcevV3)){
				//$tabela .= $this->tableRow($tretjaVrsticaVert, 1, 0, 0, $steviloPodstolpcevV3);
				$tabela .= $this->tableRow($tretjaVrsticaVert, 1, 0, 0, '', $export_format, $steviloPodstolpcevV3);
			}else{
				$tabela .= $this->tableRow($tretjaVrsticaVert, 1);
			}
			if($export_format != 'xls'){
				$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica po tretji vrstici
			}
		}
		
		if(count($cetrtaVrsticaVert)){
			$tabela .= $this->MultiColCellLatex($colspan, '', 1); //prazne celice v drugi vrstici
			$tabela .= $this->tableRow($cetrtaVrsticaVert, 1);	
		}
		
		
		// Izrisemo HORIZONTALNO izbrane variable
		if(count($this->multiCrosstabClass->selectedVars['hor'])){
		
			// Imamo 2 nivoja vrstic
			if($this->multiCrosstabClass->rowLevel2){
				if($export_format != 'xls'){
					$tabela .= ' \hline ';	//horizontalna crta na zacetku tabele
				}
				
				foreach($this->multiCrosstabClass->selectedVars['hor'] as $parentVar){
					
					$cnt = 0;
					$order0 = 0;
					
					foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
						//echo "option hor 2. nivoja: ".$option."</br>";
						$cnt2 = 0;

						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							
							$width = $metaWidth / 4;
							
							foreach($parentVar['sub'] as $var){
							
								$cnt3 = 0;
						
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
									
									// Spodnji border pri zadnjem
/* 									if($cnt == count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) - 1)
										$border1 = 'B';
									else
										$border1 = '';
									if($cnt2 == count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])-1)
										$border2 = 'B';
									else
										$border2 = ''; */
									
									//if($cnt == floor((count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])) / 2)){
									

									
									
									//$tabela .= ' \hline ';
									
									if($cnt == 0){
										if($export_format != 'xls'){
											$tabela .= $this->encodeText($this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25))." & ";
										}else{
											$tabela .= $this->encodeText($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'])." & ";
										}
										//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);
										//echo "naslov : ".$this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25)."</br>";
									}
									else{
										$tabela .= " & ";
										//$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border1, 'C', 0, 0, 0 ,0, true);
										//echo "naslov : </br>";
									}
									
									
									// Variabla
									//if($cnt2 == floor(count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])/2)){		
									if($cnt2 == 0){
										if($export_format != 'xls'){
											$tabela .= $this->encodeText($this->snippet($option, 25))." & ";
										}else{
											$tabela .= $this->encodeText($option)." & ";
										}
										//echo "variabla : ".$this->snippet($option, 25)."</br>";										
										//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($option, 25), 'LR', 'C', 0, 0, 0 ,0, true);
									}
									else{
										$tabela .= " & ";
										//echo "variabla : </br>";
										//$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border2, 'C', 0, 0, 0 ,0, true);			
									}
									
									//if($cnt3 == floor(count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])/2)){	  
									if($cnt3 == 0){
										if($export_format != 'xls'){
											$tabela .= $this->encodeText($this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25))." & ";
										}else{
											$tabela .= $this->encodeText($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'])." & ";
										}
										//echo "variabla cnt3: ".$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25)."</br>";							
										//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);
									}
									else{
										$tabela .= " & ";
										//echo "variabla cnt3: </br>";
										//$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border2, 'C', 0, 0, 0 ,0, true);			
									}
									
									// Variabla 2
									if($export_format != 'xls'){
										$tabela .= $this->encodeText($this->snippet($suboption, 25))." & ";
									}else{
										$tabela .= $this->encodeText($suboption)." & ";
									}
									//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($suboption, 25), 1, 'C', 0, 0, 0 ,0, true);
									//echo "option variabla 2: ".$this->snippet($suboption, 25)."</br>";
									
									// Celice s podatki							
									$vrsticaPodatki = $this->displayDataCells($parentVar, $order0, $var, $cnt3);
									//print_r($vrsticaPodatki);
									################# izpis celic s podatki
									$tabela .= $this->displayDataCellLatex($vrsticaPodatki, $this->tableSettingsNumerus, $this->tableSettingsAvgVar, $this->tableSettingsDelezVar, $colspan, $steviloStolpcevParameterTabular, $export_format);
									################# izpis celic s podatki - konec
									// Celice s podatki - konec
									
									//$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
									
									$cnt++;	
									$cnt2++;
									$cnt3++;
									
									//prekinjena horizontalna crta med moznostmi 2. nivoja horizontalne spremenljivke
									if($export_format != 'xls'){
										$tabela .= ' \cline {4-'.$steviloStolpcevParameterTabular.'}';
									}
								}
								
								//prekinjena horizontalna crta med moznostmi 1. nivoja horizontalne spremenljivke
								if($export_format != 'xls'){
									$tabela .= ' \cline {2-'.$steviloStolpcevParameterTabular.'}';					
								}
								
								$order0++;								
							}
						}
						else{
							$width = $metaWidth / 2;
							
							// Spodnji border pri zadnjem
							if($cnt == count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) - 1)
								$border1 = 'B';
							else
								$border1 = '';
							
							if($cnt == floor(count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'])/2)){
								//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);			
							}
							else{
								//$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border1, 'C', 0, 0, 0 ,0, true);			
							}
								
							// Variabla
							//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);											
							
							// Celice s podatki
							//$this->displayDataCells($parentVar, $cnt);
							
							//$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
							
							$cnt++;
						}
					}
				}
				
				if($export_format != 'xls'){
					$tabela .= ' \hline ';	//horizontalna crta na koncu tabele
				}
			
			}
			// Imamo samo 1 nivo vrstic
			else{
				//echo "1 nivo vrstic";
				$width = $metaWidth / 2;
				if($export_format != 'xls'){
					$tabela .= ' \hline ';	//horizontalna vrstica
				}
				$cntHorVars = 0;
				foreach($this->multiCrosstabClass->selectedVars['hor'] as $var){

					// Ce imamo sumo
					$suma = ($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) > 0 && !$this->multiCrosstabClass->colLevel2) ? true : false;
					
					$cnt = 0;
					$vrsticaPodatki = array();
					$vrstica = array();								
					
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){	//vrstice s spremenljivkami
						//echo "option hor 1. nivo: ".$option."</br>";						
						//Naslov horizontalne vrstice
						if($cnt==0){
							if($export_format != 'xls'){
								$naslovVarHor = $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25);
							}else{
								$naslovVarHor = $this->multiCrosstabClass->variablesList[$var['spr']]['naslov'];
							}
							//$tabela .= $naslovVarHor." & ";
						}else{
							$naslovVarHor = '';
						}						
						//echo "naslovVarHor: ".$naslovVarHor."</br>";
						$tabela .= $naslovVarHor." & ";
						
						// Variabla
						//$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($option, 25), '1', 'C', 0, 0, 0 ,0, true);
						//echo "variabla: ".$this->snippet($option, 25)."</br>";
						if($export_format != 'xls'){
							$tabela .= $this->snippet($option, 25)." & ";
						}else{
							$tabela .= $option." & ";
						}
						
						// Celice s podatki
						//echo "celica s podatki: ".$var."</br>";
						// Ce nimamo nobenega krizanja izpisemo prazne
						if($spr2 == '' && $this->multiCrosstabClass->colSpan == 0){
							//$tabela .= " & ";
							$tabela .= $this->texNewLine;
						}else{
							$vrsticaPodatki = $this->displayDataCells($var, $cnt);
							//print_r($vrsticaPodatki);
							################# izpis celic s podatki
							$tabela .= $this->displayDataCellLatex($vrsticaPodatki, $this->tableSettingsNumerus, $this->tableSettingsAvgVar, $this->tableSettingsDelezVar, $colspan, $steviloStolpcevParameterTabular, $export_format);
							################# izpis celic s podatki - konec
						}
						//$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
						
						if($export_format != 'xls'){
							$tabela .= "\\cline{".($colspan)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
						}
						
						$cnt++;
					}
					// Vrstica za sumo (ce jo imamo vklopljeno)
					if($suma){
						
						//$this->pdf->MultiCell($width, $this->cellHeight, '', 'BLR', 'C', 0, 0, 0 ,0, true);
						//$this->pdf->MultiCell($width, $this->cellHeight, $lang['srv_analiza_crosstab_skupaj'], '1', 'C', 0, 0, 0 ,0, true);
						$tabela .= " & ".$this->encodeText($lang['srv_analiza_crosstab_skupaj'])." & ";						
						//$tabela .= $this->encodeText($lang['srv_analiza_crosstab_skupaj'])." & ";						
						
						$vrsticaPodatkiSumNum = array();
						$vrsticaPodatkiSumPer = array();
						$vrsticaPodatkiSumAvg = array();
						$vrsticaPodatkiSumDelez = array();
						// Loop cez vse stolpce
						foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){
							
							// Loop cez variable trenutnega stolpca
							$cnt = 0;
							foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){
								//echo "var2: ".$var2."</br>";
								$crosstabs = $this->multiCrosstabClass->crosstabData[$var['spr'].'-'.$spr2['spr']];
								
								$keys1 = array_keys($crosstabs['options1']);
								$val = $keys1[$cnt];
								
								//$this->displaySumsCell($var, $spr2, $val, $orientation=1);
								$celicaSums = $this->displaySumsCell($var, $spr2, $val, $orientation=1);
								if($this->tableSettingsNumerus){
									$vrsticaPodatkiSumNum[] = $celicaSums['numerus'];
								}
								if($this->tableSettingsPercent){
									$vrsticaPodatkiSumPer[] = $celicaSums['percent'];
								}
								if($this->tableSettingsAvgVar){
									$vrsticaPodatkiSumAvg[] = $celicaSums['avg'];
								}
								if($this->tableSettingsDelezVar){
									$vrsticaPodatkiSumDelez[] = $celicaSums['delez'];
								}
								//echo "Loop cez variable trenutnega stolpca </br>";
								$cnt++;
							}
							
							// Krizanje navpicne in vodoravne sume
							$celicaSums = $this->displaySumsCell($var, $spr2, 0, $orientation=2);
							if($this->tableSettingsNumerus){
								$vrsticaPodatkiSumNum[] = $celicaSums['numerus'];
							}
							if($this->tableSettingsPercent){
								$vrsticaPodatkiSumPer[] = $celicaSums['percent'];
							}
							if($this->tableSettingsAvgVar){
								$vrsticaPodatkiSumAvg[] = $celicaSums['avg'];
							}
							if($this->tableSettingsDelezVar){
								$vrsticaPodatkiSumDelez[] = $celicaSums['delez'];
							}
							
							//echo "Krizanje navpicne in vodoravne sume </br>";
						}
						if($this->tableSettingsNumerus){
							$tabela .= $this->tableRow($vrsticaPodatkiSumNum,1);
							if($this->tableSettingsPercent||$this->tableSettingsAvgVar||$this->tableSettingsDelezVar){	//ce je potrebno izpisati se ostale vrstice izracunov
								if($export_format != 'xls'){
									$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
								}
								$tabela .= $this->AddEmptyCells($colspan);
							}
						}
						if($this->tableSettingsPercent){
							$tabela .= $this->tableRow($vrsticaPodatkiSumPer,1);
							if($this->tableSettingsAvgVar||$this->tableSettingsDelezVar){	//ce je potrebno izpisati se ostale vrstice izracunov
								if($export_format != 'xls'){
									$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
								}
								$tabela .= $this->AddEmptyCells($colspan);
							}
						}
						if($this->tableSettingsAvgVar!= ''){
							$color = 'blue';
							//$tabela .= $this->tableRow($vrsticaPodatkiSumAvg,1);
							$tabela .= $this->tableRow($vrsticaPodatkiSumAvg,1,0,0,$color, $export_format);
							if($this->tableSettingsDelezVar){	//ce je potrebno izpisati se ostale vrstice izracunov
								if($export_format != 'xls'){
									$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
								}
								$tabela .= $this->AddEmptyCells($colspan);
							}
						}
						if($this->tableSettingsDelezVar!= ''){
							$color = 'red';
							//$tabela .= $this->tableRow($vrsticaPodatkiSumDelez,1);
							$tabela .= $this->tableRow($vrsticaPodatkiSumDelez,1,0,0,$color, $export_format);
						}						
					}
					if($export_format != 'xls'){
						$tabela .= ' \hline ';	//horizontalna vrstica
					}
					$cntHorVars++;
				}

				if($cntVerVars==1&&$cntHorVars==0&&$this->multiCrosstabClass->rowSpan!=0){
					$tabela .= ' \multicolumn{1}{|c}{} & \multicolumn{3}{|c|}{}';
					//$tabela .= ' \multicolumn{1}{|X}{} & \multicolumn{3}{|X|}{}';
					$tabela .= $this->texNewLine;
					if($export_format != 'xls'){
						$tabela .= ' \hline ';
					}
				}				
			}
		}

		//zaljucek latex tabele z obrobo za drugo tabelo
		$tabela .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele z obrobo za drugo tabelo - konec
		//echo "tabela: ".$tabela;
		return $tabela;
	}
	
	// Izpis celic v vrstici s podatki
	function displayDataCells($spr1='', $var1='', $spr2='', $var2=''){
		$vrstica = '';
		$celica = array();
		$celicaSums = array();
		$superCelicaNum = array();
		$superCelicaPer = array();
		$superCelicaAvg = array();
		$superCelicaDelez = array();
		$superCelica = array();
		
		// Ce nimamo nobenega krizanja izpisemo prazne
		if($spr2 == '' && $this->multiCrosstabClass->colSpan == 0){
			
			for($i=0; $i<$this->multiCrosstabClass->colSpan; $i++){				
				//$this->pdf->MultiCell($width, $height, '', '1', 'C', 0, 0, 0 ,0, true);					
			}
			
			//$this->pdf->MultiCell($dataWidth, $this->cellHeight, '', 1, 'C', 0, 0, 0 ,0, true);		
			//$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
			echo "ni ničesar </br>";
		}
	
		// Ce nimamo stolpcev - krizanje dveh vrstic
		elseif($spr2 != '' && $this->multiCrosstabClass->colSpan == 0){
			echo "krizanje dveh vrstic </br>";
			$spr1_temp = explode('-', $spr1['spr']);
			$grd = $this->multiCrosstabClass->variablesList[$spr1['spr']]['grd_id'];
			$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
			
			$spr2_temp = explode('-', $spr2['spr']);
			$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
			$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
			
			// Ce se nimamo izracunanih rezultatov jih izracunamo
			if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
				$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
			else{							
				$variables = array();
				$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
				$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
					
				$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);

				$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
			}

			$keys1 = array_keys($crosstabs['options1']);
			$val1 = $keys1[$var1];
			
			$keys2 = array_keys($crosstabs['options2']);
			$val2 = $keys2[$var2];

			$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
			$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
			$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
			$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
			
			$this->displayDataCell($crosstab, $percent, $avg, $delez);
		}
		
		// Krizanje 1 vrstice in 1 stolpca
		elseif($spr2 == '' && !$this->multiCrosstabClass->colLevel2){
			//echo "Krizanje 1 vrstice in 1 stolpca </br>";
			// Loop cez vse stolpce
			foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){

				$spr1_temp = explode('-', $spr1['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr1['spr']]['grd_id'];
				$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);
				
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
				
				
				// Ce se nimamo izracunanih rezultatov jih izracunamo
				if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
					$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
				else{
					$variables = array();
					$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
					$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
					
					$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

					$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
				}

				$keys1 = array_keys($crosstabs['options1']);
				$val1 = $keys1[$var1];

				// Loop cez variable trenutnega stolpca
				$cnt = 0;
				foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){
				
					$keys2 = array_keys($crosstabs['options2']);
					$val2 = $keys2[$cnt];
		
					$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
					$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
					$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
					$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
					
					//echo "cnt: ".$cnt."</br>";					
					//$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez);
					$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez, $cnt);
					
					if($this->tableSettingsNumerus){
						$superCelicaNum[] = $celica['numerus'][$cnt];
						//echo "Celica izven numerus: ".$celica['numerus'][$cnt]."</br>";
					}
					
					if($this->tableSettingsPercent){
						$superCelicaPer[] = $celica['percent'][$cnt];
					}
					
					if($this->tableSettingsAvgVar){
						$superCelicaAvg[] = $celica['avg'][$cnt];
					}
					
					if($this->tableSettingsDelezVar){
						$superCelicaDelez[] = $celica['delez'][$cnt];
					}
					
					//echo "Celica: ".$celica['numerus'][$cnt]."</br>";
					
					$cnt++;
				}

				// Suma (ce jo imamo vklopljeno)
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2){
					$celicaSums = $this->displaySumsCell($spr1, $spr2, $val1, $orientation=0);
					if($this->tableSettingsNumerus){
						$superCelicaNum[] = $celicaSums['numerus'];
					}
					if($this->tableSettingsPercent){
						$superCelicaPer[] = $celicaSums['percent'];
					}
					if($this->tableSettingsAvgVar){
						$superCelicaAvg[] = $celicaSums['avg'];
					}
					if($this->tableSettingsDelezVar){
						$superCelicaDelez[] = $celicaSums['delez'];
					}
				}
			}			
		}
		
		// Izpisemo vecnivojske podatke (krizanje 3 ali 4 spremenljivk)
		else{
			//echo "Izpisemo vecnivojske podatke (krizanje 3 ali 4 spremenljivk) </br>";
			// Nastavimo 1. vrsticno variablo
			$spr1_temp = explode('-', $spr1['spr']);
			$grd = $this->multiCrosstabClass->variablesList[$spr1['spr']]['grd_id'];
			$variabla1 = array('seq' => $spr1_temp[1], 'spr' => $spr1_temp[0], 'grd' => $grd);

			// Krizanje 2 vrstic in 1 stolpca
			if(!$this->multiCrosstabClass->colLevel2){
			
				// Nastavimo 2. vrsticno variablo
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);			
			
				// Loop cez vse stolpce
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr3){

					$spr3_temp = explode('-', $spr3['spr']);
					$grd = $this->multiCrosstabClass->variablesList[$spr3['spr']]['grd_id'];
					$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);			
					
					// Ce se nimamo izracunanih rezultatov jih izracunamo
					if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
						$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
					else{
						$variables = array();
						$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
						$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
						$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
						
						$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

						$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
					}

					$keys1 = array_keys($crosstabs['options1']);
					$val1 = $keys1[$var1];
					
					$keys2 = array_keys($crosstabs['options2']);
					$val2 = $keys2[$var2];

					// Loop cez variable trenutnega stolpca
					$cnt = 0;
					foreach($this->multiCrosstabClass->variablesList[$spr3['spr']]['options'] as $var3){
					
						$keys3 = array_keys($crosstabs['options3']);
						$val3 = $keys3[$cnt];

						$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
						$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
						$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
						$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
					
						//$this->displayDataCell($crosstab, $percent, $avg, $delez);
						$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez, $cnt);
						
						if($this->tableSettingsNumerus){
							$superCelicaNum[] = $celica['numerus'][$cnt];
							//echo "Celica izven numerus: ".$celica['numerus'][$cnt]."</br>";
						}
						
						if($this->tableSettingsPercent){
							$superCelicaPer[] = $celica['percent'][$cnt];
						}
						
						if($this->tableSettingsAvgVar){
							$superCelicaAvg[] = $celica['avg'][$cnt];
						}
						
						if($this->tableSettingsDelezVar){
							$superCelicaDelez[] = $celica['delez'][$cnt];
						}
							
						$cnt++;
					}
				}
			}
			
			// Krizanje 1 vrstice in 2 stolpcev
			elseif($spr2 == ''){
				//echo "Krizanje 1 vrstice in 2 stolpcev </br>";
				// Loop cez vse stolpce 1. navpicne spremenljivke
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){

					$spr2_temp = explode('-', $spr2['spr']);
					$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
					$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);							

					// Loop cez variable 1. navpicne spremnljivke
					$cnt2 = 0;
					foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){	

						// Loop cez vse navpicne spremenljivke 2. nivoja - ce obstajajo							
						if(count($spr2['sub']) > 0){
							foreach($spr2['sub'] as $spr3){
								
								// Nastavimo navpicno spremenljivko 2. nivoja	
								$spr3_temp = explode('-', $spr3['spr']);
								$grd = $this->multiCrosstabClass->variablesList[$spr3['spr']]['grd_id'];
								$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);
								
								// Ce se nimamo izracunanih rezultatov jih izracunamo
								if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
								else{
									$variables = array();
									$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
									$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
									$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
									
									$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
								}

								$keys1 = array_keys($crosstabs['options1']);
								$val1 = $keys1[$var1];
								
								$keys2 = array_keys($crosstabs['options2']);
								$val2 = $keys2[$cnt2];

								// Loop cez variable spremenljivke 2. nivoja
								$cnt3 = 0;
								foreach($this->multiCrosstabClass->variablesList[$spr3['spr']]['options'] as $var3){
									
									$keys3 = array_keys($crosstabs['options3']);
									$val3 = $keys3[$cnt3];

									$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
									$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
									$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
									$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
								
									//$this->displayDataCell($crosstab, $percent, $avg, $delez);
									$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez, $cnt3);
									
									if($this->tableSettingsNumerus){
										$superCelicaNum[] = $celica['numerus'][$cnt3];
										//echo "Celica izven numerus: ".$celica['numerus'][$cnt]."</br>";
									}
									
									if($this->tableSettingsPercent){
										$superCelicaPer[] = $celica['percent'][$cnt3];
									}
									
									if($this->tableSettingsAvgVar){
										$superCelicaAvg[] = $celica['avg'][$cnt3];
									}
									
									if($this->tableSettingsDelezVar){
										$superCelicaDelez[] = $celica['delez'][$cnt3];
									}
									$cnt3++;
								}
							}
						}
						// 1 nivojska spremenljivka v stolpcu
						else{
							// Ce se nimamo izracunanih rezultatov jih izracunamo
							if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']]))
								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
							else{
								$variables = array();
								$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
								$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
								
								$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
							}

							$keys1 = array_keys($crosstabs['options1']);
							$val1 = $keys1[$var1];
							
							$keys2 = array_keys($crosstabs['options2']);
							$val2 = $keys2[$cnt2];

							$crosstab = (isset($crosstabs['crosstab'][$val1][$val2])) ? $crosstabs['crosstab'][$val1][$val2] : 0;
							$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
							$avg = (isset($crosstabs['avg'][$val1][$val2])) ? $crosstabs['avg'][$val1][$val2] : 0;
							$delez = (isset($crosstabs['delez'][$val1][$val2])) ? $crosstabs['delez'][$val1][$val2] : 0;
						
							//$this->displayDataCell($crosstab, $percent, $avg, $delez);
							$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez, $cnt2);
							
							if($this->tableSettingsNumerus){
								$superCelicaNum[] = $celica['numerus'][$cnt2];
								//echo "Celica izven numerus: ".$celica['numerus'][$cnt]."</br>";
							}
							
							if($this->tableSettingsPercent){
								$superCelicaPer[] = $celica['percent'][$cnt2];
							}
							
							if($this->tableSettingsAvgVar){
								$superCelicaAvg[] = $celica['avg'][$cnt2];
							}
							
							if($this->tableSettingsDelezVar){
								$superCelicaDelez[] = $celica['delez'][$cnt2];
							}							
						}
						
						$cnt2++;
					}
				}
			}

			
			
			// Krizanje 2 vrstic in 2 stolpcev
			else{
				//echo "Krizanje 2 vrstic in 2 stolpcev </br>";
				// Nastavimo 2. vrsticno variablo
				$spr2_temp = explode('-', $spr2['spr']);
				$grd = $this->multiCrosstabClass->variablesList[$spr2['spr']]['grd_id'];
				$variabla2 = array('seq' => $spr2_temp[1], 'spr' => $spr2_temp[0], 'grd' => $grd);
			
				// Loop cez vse stolpce 1. navpicne spremenljivke
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr3){

					$spr3_temp = explode('-', $spr3['spr']);
					$grd = $this->multiCrosstabClass->variablesList[$spr3['spr']]['grd_id'];
					$variabla3 = array('seq' => $spr3_temp[1], 'spr' => $spr3_temp[0], 'grd' => $grd);							

					// Loop cez variable 1. navpicne spremnljivke
					$cnt3 = 0;
					foreach($this->multiCrosstabClass->variablesList[$spr3['spr']]['options'] as $var3){	

						// Loop cez vse navpicne spremenljivke 2. nivoja								
						if(count($spr3['sub']) > 0){
							foreach($spr3['sub'] as $spr4){
								
								// Nastavimo navpicno spremenljivko 2. nivoja	
								$spr4_temp = explode('-', $spr4['spr']);
								$grd = $this->multiCrosstabClass->variablesList[$spr4['spr']]['grd_id'];
								$variabla4 = array('seq' => $spr4_temp[1], 'spr' => $spr4_temp[0], 'grd' => $grd);
								
								// Ce se nimamo izracunanih rezultatov jih izracunamo
								if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']]))
									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']];
								else{
									$variables = array();
									$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
									$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
									$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
									$variables[3] = array('seq' => $variabla4['seq'], 'spr' => $variabla4['spr'], 'grd' => $variabla4['grd']);
									
									$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

									$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr'].'-'.$spr4['spr']];
								}

								$keys1 = array_keys($crosstabs['options1']);
								$val1 = $keys1[$var1];
								
								$keys2 = array_keys($crosstabs['options2']);
								$val2 = $keys2[$var2];
								
								$keys3 = array_keys($crosstabs['options3']);
								$val3 = $keys3[$cnt3];

								// Loop cez variable spremenljivke 2. nivoja
								$cnt4 = 0;
								foreach($this->multiCrosstabClass->variablesList[$spr4['spr']]['options'] as $var4){
									
									$keys4 = array_keys($crosstabs['options4']);
									$val4 = $keys4[$cnt4];

									$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3][$val4])) ? $crosstabs['crosstab'][$val1][$val2][$val3][$val4] : 0;
									$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
									$avg = (isset($crosstabs['avg'][$val1][$val2][$val3][$val4])) ? $crosstabs['avg'][$val1][$val2][$val3][$val4] : 0;
									$delez = (isset($crosstabs['delez'][$val1][$val2][$val3][$val4])) ? $crosstabs['delez'][$val1][$val2][$val3][$val4] : 0;
								
									//$this->displayDataCell($crosstab, $percent, $avg, $delez);
									$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez, $cnt4);
							
									if($this->tableSettingsNumerus){
										$superCelicaNum[] = $celica['numerus'][$cnt4];
										//echo "Celica izven numerus: ".$celica['numerus'][$cnt]."</br>";
									}
									
									if($this->tableSettingsPercent){
										$superCelicaPer[] = $celica['percent'][$cnt4];
									}
									
									if($this->tableSettingsAvgVar){
										$superCelicaAvg[] = $celica['avg'][$cnt4];
									}
									
									if($this->tableSettingsDelezVar){
										$superCelicaDelez[] = $celica['delez'][$cnt4];
									}
										
									$cnt4++;
								}
							}
						}
						// 1 nivo navpicne spremenljivke
						else{							
							// Ce se nimamo izracunanih rezultatov jih izracunamo
							if(isset($this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']]))
								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
							else{
								$variables = array();
								$variables[0] = array('seq' => $variabla1['seq'], 'spr' => $variabla1['spr'], 'grd' => $variabla1['grd']);
								$variables[1] = array('seq' => $variabla2['seq'], 'spr' => $variabla2['spr'], 'grd' => $variabla2['grd']);
								$variables[2] = array('seq' => $variabla3['seq'], 'spr' => $variabla3['spr'], 'grd' => $variabla3['grd']);
								
								$this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']] = $this->multiCrosstabClass->createCrostabulation($variables);	

								$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr'].'-'.$spr3['spr']];
							}

							$keys1 = array_keys($crosstabs['options1']);
							$val1 = $keys1[$var1];
							
							$keys2 = array_keys($crosstabs['options2']);
							$val2 = $keys2[$var2];
							
							$keys3 = array_keys($crosstabs['options3']);
							$val3 = $keys3[$cnt3];

							$crosstab = (isset($crosstabs['crosstab'][$val1][$val2][$val3])) ? $crosstabs['crosstab'][$val1][$val2][$val3] : 0;
							$percent = ($crosstab > 0) ? $this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaVrstica'][$val1], $crosstab) : 0;
							$avg = (isset($crosstabs['avg'][$val1][$val2][$val3])) ? $crosstabs['avg'][$val1][$val2][$val3] : 0;
							$delez = (isset($crosstabs['delez'][$val1][$val2][$val3])) ? $crosstabs['delez'][$val1][$val2][$val3] : 0;
						
							//$this->displayDataCell($crosstab, $percent, $avg, $delez);
							$celica = $this->displayDataCell($crosstab, $percent, $avg, $delez, $cnt3);
							
							if($this->tableSettingsNumerus){
								$superCelicaNum[] = $celica['numerus'][$cnt3];
								//echo "Celica izven numerus: ".$celica['numerus'][$cnt]."</br>";
							}
							
							if($this->tableSettingsPercent){
								$superCelicaPer[] = $celica['percent'][$cnt3];
							}
							
							if($this->tableSettingsAvgVar){
								$superCelicaAvg[] = $celica['avg'][$cnt3];
							}
							
							if($this->tableSettingsDelezVar){
								$superCelicaDelez[] = $celica['delez'][$cnt3];
							}
							
						}
						
						$cnt3++;
					}
				}	
			}
		}
		
		if($this->tableSettingsNumerus){
			$superCelica['numerus'][] = $superCelicaNum;
		}	

		if($this->tableSettingsPercent){
			$superCelica['percent'][] = $superCelicaPer;
		}
		
		if($this->tableSettingsAvgVar){
			$superCelica['avg'][] = $superCelicaAvg;
		}	

		if($this->tableSettingsDelezVar){
			$superCelica['delez'][] = $superCelicaDelez;
		}		
		
		return $superCelica;		
	}
	
	// Izpis celic v vrstici s sumami ($orientation 0->vrstica, 1->stolpec, 2->skupaj)
	function displaySumsCell($spr1=null, $spr2=null, $val=null, $orientation=null){
		$celicaSums = array();
		
		//echo "Orientacija skupaj: ".$orientation."</br>";
/* 		$startX = $this->pdf->getX();
		$startY = $this->pdf->getY(); */
	
		// Nastavimo visino posamezne vrstice
/* 		$cellSpan = 0;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1)
			$cellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1)
			$cellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] != '')
			$cellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] != '')
			$cellSpan++;
		$cellSpan = ($cellSpan > 0) ? $cellSpan : 1;
		$lineHeight = $this->cellHeight / $cellSpan; */

		// Nastavimo barvo texta
/* 		$this->pdf->SetTextColor(160, 0, 0);
		$this->pdf->setFont('','B','6'); */

		$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
		
		// Celica s skupno sumo
		if($orientation == 2){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$celicaSums['numerus'] = $crosstabs['sumaSkupna'];
/* 				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstabs['sumaSkupna'], 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
			}
			
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				//$celicaSums['percent'] = $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$celicaSums['percent'] = $this->encodeText($this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
			}
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			
				// Loop cez vse in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab']){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'] as $key1 => $row){	
						foreach($row as $key2 => $count){
							$tempAvg += $count * $crosstabs['avg'][$key1][$key2];
						}
					}
					$avg = $tempAvg / $crosstabs['sumaSkupna'];
				}
				$celicaSums['avg'] =  $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
/* 				$this->pdf->SetFillColor(220, 220, 255);
				$this->pdf->SetTextColor(0, 0, 230);
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);	 */	
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez']){	
					foreach($crosstabs['delez'] as $row){	
						foreach($row as $tempDelez){
							$delez += $tempDelez;
						}
					}
				}
				//$celicaSums['delez'] =  $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$celicaSums['delez'] =  $this->encodeText($this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 				$this->pdf->SetFillColor(255, 220, 220);	
				$this->pdf->SetTextColor(230, 0, 0);			
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
			}
		}
		// Suma na koncu vrstice
		elseif($orientation == 0){
			
			// Izpisemo podatek
			if($crosstabs['sumaVrstica'][$val]){
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstabs['sumaVrstica'][$val], 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
					$celicaSums['numerus'] = $crosstabs['sumaVrstica'][$val];
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
					//$celicaSums['percent'] = $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$celicaSums['percent'] = $this->encodeText($this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
				}
			}
			else{
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$celicaSums['numerus'] = '0';
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, '0', 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					//$celicaSums['percent'] = $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$celicaSums['percent'] = $this->encodeText($this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				}
			}
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
				
				// Loop cez vrstico in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab'][$val]){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'][$val] as $key => $count){	
						$tempAvg += $count * $crosstabs['avg'][$val][$key];
					}
					$avg = $tempAvg / $crosstabs['sumaVrstica'][$val];
				}
				
				$celicaSums['avg'] = $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
				
/* 				$this->pdf->SetFillColor(220, 220, 255);
				$this->pdf->SetTextColor(0, 0, 230);
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);		 */
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez'][$val]){	
					foreach($crosstabs['delez'][$val] as $tempDelez){	
						$delez += $tempDelez;
					}
				}
				
				//$celicaSums['delez'] = $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$celicaSums['delez'] = $this->encodeText($this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
				
/* 				$this->pdf->SetFillColor(255, 220, 220);	
				$this->pdf->SetTextColor(230, 0, 0);			
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
			}
		}
		// Suma za stolpce
		else{
			// Izpisemo podatek
			if(isset($crosstabs['sumaStolpec'][$val])){
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$celicaSums['numerus'] = $crosstabs['sumaStolpec'][$val];
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstabs['sumaStolpec'][$val], 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					//$celicaSums['percent'] = $this->formatNumber($this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$celicaSums['percent'] = $this->encodeText($this->formatNumber($this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				}
			}
			else{
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$celicaSums['numerus'] = '0';
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, '0', 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					//$celicaSums['percent'] = $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$celicaSums['percent'] = $this->encodeText($this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				}
			}
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
				
				// Loop cez vrstico in izracunamo povprecje z ustreznimi utezmi
				$avg = 0;
				if($crosstabs['crosstab']){
					$tempAvg = 0;
					foreach($crosstabs['crosstab'] as $key => $row){
						if($row[$val] > 0)
							$tempAvg += $row[$val] * $crosstabs['avg'][$key][$val];
					}
					$avg = $tempAvg / $crosstabs['sumaStolpec'][$val];
				}
				$celicaSums['avg'] = $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
				
/* 				$this->pdf->SetFillColor(220, 220, 255);
				$this->pdf->SetTextColor(0, 0, 230);
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);	 */	
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				// Loop cez vrstico in izracunamo skupen delez
				$delez = 0;
				if($crosstabs['delez']){	
					foreach($crosstabs['delez'] as $tempDelez){	
						$delez += $tempDelez[$val];
					}
				}
				//$celicaSums['delez'] = $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$celicaSums['delez'] = $this->encodeText($this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 				$this->pdf->SetFillColor(255, 220, 220);	
				$this->pdf->SetTextColor(230, 0, 0);			
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
			}
		}
		
/* 		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->setFont('','','6');
		$this->pdf->setXY($startX + $this->cellWidth, $startY); */
		
		return $celicaSums;
	}
	
	// Izpis celice z vrednostmi
	//function displayDataCell($crosstab, $percent, $avg, $delez){
	function displayDataCell($crosstab=null, $percent=null, $avg=null, $delez=null, $cnt=null){
		
		//$podatekCelice = '';
		$podatekCelice = array();
		//$startX = $this->pdf->getX();
		//$startY = $this->pdf->getY();
			
		$lineHeight = ($this->cellSpan > 1) ? 5 : 6;
		
		if($crosstab > 0){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
/* 				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstab, 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				//$podatekCelice = $crosstab;
				$podatekCelice['numerus'][$cnt] = $crosstab;
				//echo "Crosstab ce crosstab > 0: ".$crosstab."</br>";				
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
/* 				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				//$podatekCelice = $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				//$podatekCelice['percent'][$cnt] = $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$podatekCelice['percent'][$cnt] = $this->encodeText($this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
				//echo "Procenti ce crosstab > 0: ".$podatekCelice."</br>";
			}
		}
		else{		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
/* 				$this->pdf->MultiCell($this->cellWidth, $lineHeight, '0', 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				$podatekCelice['numerus'][$cnt] = '0';
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
/* 				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
				//$podatekCelice = $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				//$podatekCelice['percent'][$cnt] = $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$podatekCelice['percent'][$cnt] = $this->encodeText($this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			}
		}
		
		// Povprecje
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			//$podatekCelice = $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
			//echo "Povprecje: ".$podatekCelice."</br>";
			$podatekCelice['avg'][$cnt] = $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'));
/* 			$this->pdf->SetFillColor(220, 220, 255);
			$this->pdf->SetTextColor(0, 0, 230);
			$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
			$this->pdf->SetFillColor(250, 250, 250);
			$this->pdf->SetTextColor(0, 0, 0);
			
			$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);	 */	
		}
		
		// Delez
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			//$podatekCelice = $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
			//echo "Delez: ".$podatekCelice."</br>";
			$podatekCelice['delez'][$cnt] = $this->encodeText($this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
/* 			$this->pdf->SetFillColor(255, 220, 220);	
			$this->pdf->SetTextColor(230, 0, 0);			
			$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
			$this->pdf->SetFillColor(250, 250, 250);
			$this->pdf->SetTextColor(0, 0, 0);
			
			$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight); */
		}
		
		//$this->pdf->setXY($startX + $this->cellWidth, $startY);
		return $podatekCelice;
	}

	// Izris legende na dnu
	function displayLegend($export_format){
		global $lang;
		$legend = '';
		
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0 || $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){			
			//za zacetek sredinske poravnave
			
			$legend .= ' \begin{center} ';

			if($export_format == 'rtf'){	//ce je rtf dodaj tole besedilo, ker drugace prva od izpisanih zadev v legendi ni sredinsko poravnana
				$legend .= $lang['srv_analiza_legenda'].': \\\\';
			}
			
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
				$text = $lang['srv_multicrosstabs_avg'].': ';				
				if($export_format == 'pdf'){
					$color = 'crta';
				}else{	//ce je rtf
					$color = 'cyan';	//v rtf pride modra
				}
				
				
				$legend .= $this->coloredTextLatex($color, $text);
				
				$legend .= $this->multiCrosstabClass->variablesList[$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar']]['variable'];
				$legend .= $this->texNewLine;						
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				$text = $lang['srv_multicrosstabs_delez'].': ';
				
				if($export_format == 'pdf'){
					$color = 'crtaGraf';
				}else{	//ce je rtf
					$color = 'yellow';	//v rtf pride rdece
				}

				$legend .= $this->coloredTextLatex($color, $text);
				
				$delez = unserialize($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delez']);
				$string = '';
				$cnt = 1;
				foreach($delez as $val){
					if($val == 1)
						$string .= $cnt.', ';
					$cnt++;
				}	
				$string = $this->multiCrosstabClass->variablesList[$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar']]['variable'].' ('.substr($string, 0, -2).')';
				
				$legend .= $string;
				$legend .= $this->texNewLine;					
			}
			
			//za konec sredinske poravnave
			$legend .= ' \end{center} ';
			
		}
		
		return $legend;
	}
	
	
	function formatNumber($value=null, $digit=0, $sufix=""){
	
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		# polovimo decimalna mesta in vejice za tisočice
		$decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
		$thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			
		$result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;
		
		return $result;
	}
	
/* 	function encodeText($text){ 
		// popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		
		return strip_tags($text);
	} */
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text='', $length=64, $tail="..."){	
		$length=SNIPPET_LENGTH;
		
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length){
			for($i=1;$text[$length-$i]!=" ";$i++){
				if($i == $length)
				{
					return substr($text,0,$length) . $tail;
				}
			}
			$text = substr($text,0,$length-$i+1) . $tail;
		}
		
		return $text;
	}

	function drawLine(){
	
		$cy = $this->pdf->getY();
		$this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
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