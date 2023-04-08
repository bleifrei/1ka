<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.pdfIzvozAnalizaFunctions.php');
	require_once('../exportclases/class.enka.pdf.php');
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2, true); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2, true); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);

	define("FNT_FREESERIF", "freeserif", true);
	define("FNT_FREESANS", "freesans", true);
	define("FNT_HELVETICA", "helvetica", true);

	define("FNT_MAIN_TEXT", FNT_FREESANS, true);
	define("FNT_QUESTION_TEXT", FNT_FREESANS, true);
	define("FNT_HEADER_TEXT", FNT_FREESANS, true);

	define("FNT_MAIN_SIZE", 10, true);
	define("FNT_QUESTION_SIZE", 9, true);
	define("FNT_HEADER_SIZE", 10, true);

	define("RADIO_BTN_SIZE", 3, true);
	define("CHCK_BTN_SIZE", 3, true);
	define("LINE_BREAK", 6, true);

	define ('PDF_MARGIN_HEADER', 8);
	define ('PDF_MARGIN_FOOTER', 12);
	define ('PDF_MARGIN_TOP', 18);
	define ('PDF_MARGIN_BOTTOM', 18);
	define ('PDF_MARGIN_LEFT', 15);
	define ('PDF_MARGIN_RIGHT', 15);
	

/** Class za generacijo pdf-a
 *
 * @desc: po novem je potrebno form elemente generirati ro�no kot slike
 *
 */
class PdfIzvozAnalizaMultiCrosstab {

	var $anketa;// = array();				// trenutna anketa

	var $pi=array('canCreate'=>false); 		// za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public $multiCrosstabClass = null;		// crosstab class
	
	private $cellWidth = 1;					// sirina celice s podatki
	private $cellHeight = 1;				// visina celice s podadtki
	private $cellSpan = 1;					// stevilo vrstic v celici s podatki
	

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null){
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){
			$this->anketa['id'] = $anketa;

			// create new PDF document
			$this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			
			return false;
		}
		$_GET['a'] = A_ANALYSIS;
		
		
		//ustvarimo multicrosstabs objekt		
		$this->multiCrosstabClass = new SurveyMultiCrosstabs($anketa);

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
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
	
	function getFile($fileName){
		//Close and output PDF document		
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}


	function init(){
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


	function createPdf(){
		global $site_path;
		global $lang;
	   		
		$this->pdf->AddPage();
		
		$this->pdf->setFont('','B','11');
		$this->pdf->MultiCell(150, 5, $lang['srv_multicrosstabs'], 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
		
		$this->pdf->setDrawColor(128, 128, 128);
		$this->pdf->setFont('','','6');
		
		// Napolnimo variable s katerimi lahko operiramo
		$this->multiCrosstabClass->getVariableList();
		
		// Izris tabele
		$this->displayTable();
		
		// Izris legende
		$this->displayLegend();
	}	

	public function displayTable(){
		global $site_path;
		global $lang;
		
		// Napolnimo variable ki so ze izbrane
		$this->multiCrosstabClass->getSelectedVars();

		
		// Izpisemo naslov tabele
		$this->pdf->setFont('','B','10');
		$this->pdf->MultiCell(150, 5, $this->encodeText($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['title']), 0, 'L', 0, 1, 0 ,0, true);
		$this->pdf->ln(5);
		$this->pdf->setFont('','','6');
			
		
		// TABELA
		
		// Najprej izracunamo dimenzije
		$lineHeight = 6;
		$fullWidth = 270;
		
		if($this->multiCrosstabClass->rowSpan == 0)
			$colspan = 1;
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

			
		if($this->multiCrosstabClass->colSpan == 0){
			$this->cellWidth = $dataWidth/2;
			$this->pdf->MultiCell($metaWidth, 0, '', 'B', 'L', 0, 1, 0 ,0, true);
		}
		// Imamo 2 nivoja
		elseif($this->multiCrosstabClass->colLevel2){
			
			$this->cellWidth = $dataWidth / $this->multiCrosstabClass->colSpan;

			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'L', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					// Ce imamo tudi 2. nivo pri doloceni spremenljivki
					if(count($var['sub']) > 0){
						$rowspan = 1;
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['sub'][0]['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
					}
					else{
						$rowspan = 2;
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
					}
					
					$this->pdf->MultiCell($width, $lineHeight*$rowspan, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 1, 'C', 0, 0, 0 ,0, true);
				}
			}
			$this->pdf->MultiCell(1, $lineHeight, '', 0, 'L', 0, 1, 0 ,0, true);
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'L', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){

					// Ce imamo tudi 2. nivo pri doloceni spremenljivki
					if(count($var['sub']) > 0){
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['sub'][0]['spr']]['options']);
						foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
							$this->pdf->MultiCell($width, $lineHeight, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);
						}
					}
					else{
						$this->pdf->setXY($this->pdf->getX(), $this->pdf->getY() + $lineHeight);
						foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
							$this->pdf->MultiCell($this->cellWidth, $lineHeight*2, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);
						}
						$this->pdf->setXY($this->pdf->getX(), $this->pdf->getY() - $lineHeight);
					}
				}
			}
			$this->pdf->MultiCell(1, $lineHeight, '', 0, 'L', 0, 1, 0 ,0, true);
			
			// Izris vrstic za 2. nivo - 3. in 4. vrstica
			$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'L', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					
					// ce imamo childe na 2. nivoju
					if(count($parentVar['sub']) > 0){
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$parentVar['sub'][0]['spr']]['options']);
							foreach($parentVar['sub'] as $var){				
								$this->pdf->MultiCell($width, $lineHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 1, 'C', 0, 0, 0 ,0, true);
							}
						}
					}
					else{
						$this->pdf->MultiCell($this->cellWidth*count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']), $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
					}
				}
			}
			$this->pdf->MultiCell(1, $lineHeight, '', 0, 'L', 0, 1, 0 ,0, true);
				
			$this->pdf->MultiCell($metaWidth, $lineHeight, '', 'B', 'L', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					
					// ce imamo childe na 2. nivoju
					if(count($parentVar['sub']) > 0){
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							foreach($parentVar['sub'] as $var){				
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
									$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->snippet($suboption, 25), 1, 'C', 0, 0, 0 ,0, true, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
								}
							}
						}
					}
					else{
						$this->pdf->MultiCell($this->cellWidth*count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']), $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
					}
				}
			}
			$this->pdf->MultiCell(1, $lineHeight, '', 0, 'L', 0, 1, 0 ,0, true);
		}
		// Imamo samo 1 nivo
		else{
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica
			
			// Izracunamo sirine celic
			$this->cellWidth = $dataWidth / $this->multiCrosstabClass->fullColSpan;			
			
			$this->pdf->MultiCell($metaWidth, $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					$sprWidth = count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) * $this->cellWidth;
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2)
						$sprWidth += $this->cellWidth;
					
					$this->pdf->MultiCell($sprWidth, $lineHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 35), 1, 'C', 0, 0, 0 ,0, true);
				}
				
				$this->pdf->MultiCell(1, $lineHeight, '', 0, 'C', 0, 1, 0 ,0, true);
			}
			// Nimamo nobene vertikalne spremenljivke in 2 horizontalni
			elseif($this->multiCrosstabClass->rowLevel2){
				$this->pdf->MultiCell($dataWidth, $lineHeight, '', 0, 'C', 0, 0, 0 ,0, true);
			}

			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
			
				// Iracunamo visino najvisje celice
				$cellHeight = $lineHeight;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
										
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
						$height = $this->getCellHeight($this->snippet($option, 25), $this->cellWidth);
						
						$cellHeight = ($height > $cellHeight) ? $height : $cellHeight;
					}
				}
			
				$this->pdf->MultiCell($metaWidth, $cellHeight, '', 'B', 'C', 0, 0, 0 ,0, true);
			
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
						
					// Loop cez variable spremenljivke
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
						$this->pdf->MultiCell($this->cellWidth, $cellHeight, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);	
					}
					
					// Suma (ce jo imamo vklopljeno)
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2){						
						$this->pdf->MultiCell($this->cellWidth, $cellHeight, $lang['srv_analiza_crosstab_skupaj'], 1, 'C', 0, 0, 0 ,0, true);	
					}
				}
				$this->pdf->MultiCell(1, $cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
			}
		}

		
		
		// Izrisemo HORIZONTALNO izbrane variable
		if(count($this->multiCrosstabClass->selectedVars['hor'])){
		
			// Imamo 2 nivoja vrstic
			if($this->multiCrosstabClass->rowLevel2){
				foreach($this->multiCrosstabClass->selectedVars['hor'] as $parentVar){
					
					$cnt = 0;
					$order0 = 0;
					
					foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
					
						$cnt2 = 0;
					
						// ce imamo childe na 2. nivoju
						if(count($parentVar['sub']) > 0){
							
							$width = $metaWidth / 4;
							
							foreach($parentVar['sub'] as $var){
							
								$cnt3 = 0;
						
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
									
									// Spodnji border pri zadnjem
									if($cnt == count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) - 1)
										$border1 = 'B';
									else
										$border1 = '';
									if($cnt2 == count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])-1)
										$border2 = 'B';
									else
										$border2 = '';
									
									if($cnt == floor((count($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])) / 2)){
										$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);			
									}
									else{
										$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border1, 'C', 0, 0, 0 ,0, true);			
									}
									
									// Variabla
									if($cnt2 == floor(count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])/2)){										
										$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($option, 25), 'LR', 'C', 0, 0, 0 ,0, true);			
									}
									else{
										$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border2, 'C', 0, 0, 0 ,0, true);			
									}
									
									if($cnt3 == floor(count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])/2)){										
										$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);
									}
									else{
										$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border2, 'C', 0, 0, 0 ,0, true);			
									}
									
									// Variabla 2
									$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($suboption, 25), 1, 'C', 0, 0, 0 ,0, true);
									
									// Celice s podatki
									$this->displayDataCells($parentVar, $order0, $var, $cnt3);
									
									$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
									
									$cnt++;	
									$cnt2++;
									$cnt3++;
								}
								
								$order0++;
								
								// Izrisemo se sumo ce je vklopljena
								/*if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) == 0){
									
									$output .= '<td align="center">'.$lang['srv_analiza_crosstab_skupaj'].'</td>';
									
									$crosstabs = $this->multiCrosstabClass->crosstabData[$parentVar['spr'].'-'.$var['spr']];
								
									$keys1 = array_keys($crosstabs['options2']);
									$key = ceil($cnt / (count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])*count($parentVar['sub']))) - 1;
									$val = $keys1[$key];
								
									$this->displaySumsCell($parentVar, $var, $val, $orientation=0);
								}*/
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
								$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);			
							}
							else{
								$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR'.$border1, 'C', 0, 0, 0 ,0, true);			
							}
								
							// Variabla
							$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($option, 25), 1, 'C', 0, 0, 0 ,0, true);											
							
							// Celice s podatki
							$this->displayDataCells($parentVar, $cnt);
							
							$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
							
							$cnt++;
						}
					}
				}
			}
			// Imamo samo 1 nivo vrstic
			else{
				$width = $metaWidth / 2;
				
				foreach($this->multiCrosstabClass->selectedVars['hor'] as $var){
					
					// Ce imamo sumo
					$suma = ($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && count($this->multiCrosstabClass->selectedVars['ver']) > 0 && !$this->multiCrosstabClass->colLevel2) ? true : false;
					
					$cnt = 0;
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
												
						if($cnt == floor(count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) / 2)){	
							$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25), 'LR', 'C', 0, 0, 0 ,0, true);
						}
						elseif(($cnt == count($this->multiCrosstabClass->variablesList[$var['spr']]['options'])-1) && !$suma){
							$this->pdf->MultiCell($width, $this->cellHeight, '', 'BLR', 'C', 0, 0, 0 ,0, true);
						}
						else{
							$this->pdf->MultiCell($width, $this->cellHeight, '', 'LR', 'C', 0, 0, 0 ,0, true);
						}
						
						// Variabla
						$this->pdf->MultiCell($width, $this->cellHeight, $this->snippet($option, 25), '1', 'C', 0, 0, 0 ,0, true);										
						
						// Celice s podatki
						$this->displayDataCells($var, $cnt);

						
						$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
						
						$cnt++;
					}
					
					// Vrstica za sumo (ce jo imamo vklopljeno)
					if($suma){
						$this->pdf->MultiCell($width, $this->cellHeight, '', 'BLR', 'C', 0, 0, 0 ,0, true);
						$this->pdf->MultiCell($width, $this->cellHeight, $lang['srv_analiza_crosstab_skupaj'], '1', 'C', 0, 0, 0 ,0, true);
						
						// Loop cez vse stolpce
						foreach($this->multiCrosstabClass->selectedVars['ver'] as $spr2){
						
							// Loop cez variable trenutnega stolpca
							$cnt = 0;
							foreach($this->multiCrosstabClass->variablesList[$spr2['spr']]['options'] as $var2){
								
								$crosstabs = $this->multiCrosstabClass->crosstabData[$var['spr'].'-'.$spr2['spr']];
								
								$keys1 = array_keys($crosstabs['options1']);
								$val = $keys1[$cnt];
								
								$this->displaySumsCell($var, $spr2, $val, $orientation=1);
									
								$cnt++;
							}
							
							// Krizanje navpicne in vodoravne sume
							$this->displaySumsCell($var, $spr2, 0, $orientation=2);
						}
						
						$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
					}
				}
			}
		}
	}
	
	// Izpis celic v vrstici s podatki
	function displayDataCells($spr1, $var1, $spr2='', $var2=''){

		// Ce nimamo nobenega krizanja izpisemo prazne
		if($spr2 == '' && $this->multiCrosstabClass->colSpan == 0){
			
			for($i=0; $i<$this->multiCrosstabClass->colSpan; $i++){				
				//$this->pdf->MultiCell($width, $height, '', '1', 'C', 0, 0, 0 ,0, true);					
			}
			
			$this->pdf->MultiCell($dataWidth, $this->cellHeight, '', 1, 'C', 0, 0, 0 ,0, true);		
			$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
		}
	
		// Ce nimamo stolpcev - krizanje dveh vrstic
		elseif($spr2 != '' && $this->multiCrosstabClass->colSpan == 0){

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
					
					$this->displayDataCell($crosstab, $percent, $avg, $delez);
						
					$cnt++;
				}
				
				// Suma (ce jo imamo vklopljeno)
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2){						
					$this->displaySumsCell($spr1, $spr2, $val1, $orientation=0);
				}
			}
		}
		
		// Izpisemo vecnivojske podatke (krizanje 3 ali 4 spremenljivk)
		else{
		
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
					
						$this->displayDataCell($crosstab, $percent, $avg, $delez);
							
						$cnt++;
					}
				}
			}
			
			// Krizanje 1 vrstice in 2 stolpcev
			elseif($spr2 == ''){
							
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
								
									$this->displayDataCell($crosstab, $percent, $avg, $delez);
																			
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
						
							$this->displayDataCell($crosstab, $percent, $avg, $delez);						
						}
						
						$cnt2++;
					}
				}
			}

			
			
			// Krizanje 2 vrstic in 2 stolpcev
			else{
			
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
								
									$this->displayDataCell($crosstab, $percent, $avg, $delez);
										
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
						
							$this->displayDataCell($crosstab, $percent, $avg, $delez);
						}
						
						$cnt3++;
					}
				}	
			}
		}
	}
	
	// Izpis celic v vrstici s sumami ($orientation 0->vrstica, 1->stolpec, 2->skupaj)
	function displaySumsCell($spr1, $spr2, $val, $orientation){

		$startX = $this->pdf->getX();
		$startY = $this->pdf->getY();
	
		// Nastavimo visino posamezne vrstice
		$cellSpan = 0;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1)
			$cellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1)
			$cellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] != '')
			$cellSpan++;
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] != '')
			$cellSpan++;
		$cellSpan = ($cellSpan > 0) ? $cellSpan : 1;
		$lineHeight = $this->cellHeight / $cellSpan;

		// Nastavimo barvo texta
		$this->pdf->SetTextColor(160, 0, 0);
		$this->pdf->setFont('','B','6');
					
		$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
		
		// Celica s skupno sumo
		if($orientation == 2){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstabs['sumaSkupna'], 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
			
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
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
				
				$this->pdf->SetFillColor(220, 220, 255);
				$this->pdf->SetTextColor(0, 0, 230);
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);		
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
				
				$this->pdf->SetFillColor(255, 220, 220);	
				$this->pdf->SetTextColor(230, 0, 0);			
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
		}
		// Suma na koncu vrstice
		elseif($orientation == 0){
			// Izpisemo podatek
			if($crosstabs['sumaVrstica'][$val]){
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstabs['sumaVrstica'][$val], 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
				}
			}
			else{
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, '0', 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
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
				
				$this->pdf->SetFillColor(220, 220, 255);
				$this->pdf->SetTextColor(0, 0, 230);
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);		
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
				
				$this->pdf->SetFillColor(255, 220, 220);	
				$this->pdf->SetTextColor(230, 0, 0);			
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
		}
		// Suma za stolpce
		else{
			// Izpisemo podatek
			if(isset($crosstabs['sumaStolpec'][$val])){
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstabs['sumaStolpec'][$val], 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
				}
			}
			else{
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, '0', 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
					$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
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
				
				$this->pdf->SetFillColor(220, 220, 255);
				$this->pdf->SetTextColor(0, 0, 230);
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);		
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
				
				$this->pdf->SetFillColor(255, 220, 220);	
				$this->pdf->SetTextColor(230, 0, 0);			
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
				$this->pdf->SetFillColor(250, 250, 250);
				$this->pdf->SetTextColor(0, 0, 0);
				
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
		}
		
		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->setFont('','','6');
		$this->pdf->setXY($startX + $this->cellWidth, $startY);
	}
	
	// Izpis celice z vrednostmi
	function displayDataCell($crosstab, $percent, $avg, $delez){
		
		$startX = $this->pdf->getX();
		$startY = $this->pdf->getY();
			
		$lineHeight = ($this->cellSpan > 1) ? 5 : 6;
	
		if($crosstab > 0){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $crosstab, 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
		}
		else{		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, '0', 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 0, 0, 0 ,0, true);
				$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
			}
		}
		
		// Povprecje
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			$this->pdf->SetFillColor(220, 220, 255);
			$this->pdf->SetTextColor(0, 0, 230);
			$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')), 1, 'C', 1, 0, 0 ,0, true);
			$this->pdf->SetFillColor(250, 250, 250);
			$this->pdf->SetTextColor(0, 0, 0);
			
			$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);		
		}
		
		// Delez
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			$this->pdf->SetFillColor(255, 220, 220);	
			$this->pdf->SetTextColor(230, 0, 0);			
			$this->pdf->MultiCell($this->cellWidth, $lineHeight, $this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'), 1, 'C', 1, 0, 0 ,0, true);
			$this->pdf->SetFillColor(250, 250, 250);
			$this->pdf->SetTextColor(0, 0, 0);
			
			$this->pdf->setXY($this->pdf->getX() - $this->cellWidth, $this->pdf->getY() + $lineHeight);
		}
		
		$this->pdf->setXY($startX + $this->cellWidth, $startY);
	}

	// Izris legende na dnu
	function displayLegend(){
		global $lang;
		
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0 || $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			
			$this->pdf->setY($this->pdf->getY() + 2);
			
			$this->pdf->setX(245);			
			$this->pdf->MultiCell(40, 2, '', $border='B', $align='R', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			
				$this->pdf->setX(245);
			
				$this->pdf->SetTextColor(0, 0, 230);
				
				$this->pdf->MultiCell(15, 5, $lang['srv_multicrosstabs_avg'].': ', $border='L', $align='R', $fill=1, $ln=0, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);
				
				$this->pdf->SetTextColor(0, 0, 0);
				$this->pdf->MultiCell(25, 5, $this->multiCrosstabClass->variablesList[$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar']]['variable'], $border='R', $align='L', $fill=1, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);							
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				$this->pdf->setX(245);
				
				$this->pdf->SetTextColor(230, 0, 0);
				
				$this->pdf->MultiCell(15, 5, $lang['srv_multicrosstabs_delez'].': ', $border='L', $align='R', $fill=1, $ln=0, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);
				
				$delez = unserialize($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delez']);
				$string = '';
				$cnt = 1;
				foreach($delez as $val){
					if($val == 1)
						$string .= $cnt.', ';
					
					$cnt++;
				}	
				$string = $this->multiCrosstabClass->variablesList[$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar']]['variable'].' ('.substr($string, 0, -2).')';
				$this->pdf->SetTextColor(0, 0, 0);
				$this->pdf->MultiCell(25, 5, $string, $border='R', $align='L', $fill=1, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);						
			}
			
			$this->pdf->setX(245);			
			$this->pdf->MultiCell(40, 1, '', $border='T', $align='R', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);
		}
	}
	
	
	function formatNumber($value, $digit=0, $sufix=""){
	
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
	
	function encodeText($text){ 
		// popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		
		return strip_tags($text);
	}
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text,$length=64,$tail="..."){
		
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
	
	function getCellHeight($string, $width){
		
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