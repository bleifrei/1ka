<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.rtfIzvozAnalizaFunctions.php');
	require_once("class.enka.rtf.php");

	define("FNT_TIMES", "Times New Roman", true);
	define("FNT_ARIAL", "Arial", true);

	define("FNT_MAIN_TEXT", FNT_TIMES, true);
	define("FNT_QUESTION_TEXT", FNT_TIMES, true);
	define("FNT_HEADER_TEXT", FNT_TIMES, true);

	define("FNT_MAIN_SIZE", 12, true);
	define("FNT_QUESTION_SIZE", 10, true);
	define("FNT_HEADER_SIZE", 10, true);
	
	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogo�imo delovanje prikazovanja/skrivanja ni�elnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogo�imo delovanje prikazovanja/skrivanja ni�elnih vnosti za missinge


/** Class za generacijo rtf-a
 */
class RtfIzvozAnalizaMultiCrosstab {

	var $anketa;// = array();					// trenutna anketa
	var $pi=array('canCreate'=>false); 			// za shrambo parametrov in sporocil
	var $rtf;

	public static $multiCrosstabClass = null;	// crosstab class

	private $cellWidth = 1;						// sirina celice s podatki
	private $cellSpan = 1;						// stevilo vrstic v celici s podatki
	
	private $table = '';
	private $tableHeader = '';
	private $tableEnd = '';
	
	private $XPosition = 0;					// Trenuten X polozaj
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null){
		global $site_path;
		global $global_user_id;
		
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){
			$this->anketa['id'] = $anketa;

			// create new RTF document
			$this->rtf = new enka_RTF(true);
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

	function checkCreate(){
		return $this->pi['canCreate'];
	}

	function getFile($fileName){
		$this->rtf->display($fileName = "analiza.rtf",true);
	}

	function init(){
		global $lang;
		
		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'left');
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'right');
		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right');
		$this->rtf->set_default_font(FNT_TIMES, FNT_MAIN_SIZE);
		
		return true;
	}

	
	function createRtf(){
		global $site_path;
		global $lang;
		
		$this->rtf->draw_title($lang['srv_multicrosstabs']);
		
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
		$this->rtf->set_font("Arial Black", 12);
		$text = $this->rtf->bold(1) . $this->encodeText($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['title']) . $this->rtf->bold(0);
		$this->rtf->add_text($text);
		$this->rtf->new_line();
		$this->rtf->set_font("Times New Roman", 10);
		
		
		// Izrisemo tabelo
		// Najprej izracunamo dimenzije
		$fullWidth = 14000;
		
		if($this->multiCrosstabClass->rowSpan == 0)
			$colspan = 1;
		elseif(!$this->multiCrosstabClass->rowLevel2)
			$colspan = 2;
		else
			$colspan = 4;
			
		$metaWidth = $colspan * 1500;
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

		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';
		$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$bold = '\b';	

		
		// Zacetek tabele
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		$this->rtf->MyRTF .= "{\par";
		
		
		if($this->multiCrosstabClass->colSpan == 0){
			$this->cellWidth = round($dataWidth/2);
		}
		// Imamo 2 nivoja
		elseif($this->multiCrosstabClass->colLevel2){
			
			$this->cellWidth = round($dataWidth / $this->multiCrosstabClass->colSpan);
				
			$this->tableHeader = '\trowd\trql\trrh400';

			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica	
			$this->table = '\clvertalc\cellx'.( $metaWidth );	
			$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';		
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$cnt=1;
				$this->XPosition = $metaWidth;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					// Ce imamo tudi 2. nivo pri doloceni spremenljivki
					if(count($var['sub']) > 0){
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['sub'][0]['spr']]['options']) * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
					
						$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $width );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25). '\qc\cell';
					}
					else{
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
						
						$this->table .= '\clvertalc'.$border.'\clvmgf\cellx'.( $this->XPosition + $width );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25). '\qc\cell';
					}
					
					$cnt++;	
					$this->XPosition += $width;					
				}
			}
			$this->tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			$this->table = '\clvertalc\cellx'.( $metaWidth );	
			$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$cnt=1;
				$this->XPosition = $metaWidth;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){

					// Ce imamo tudi 2. nivo pri doloceni spremenljivki
					if(count($var['sub']) > 0){
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['sub'][0]['spr']]['options']);
						foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
							$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $width );	
							$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($option, 25). '\qc\cell';
							
							$cnt++;
							$this->XPosition += $width;
						}
					}
					else{
						$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$var['spr']]['options']);
					
						$this->table .= '\clvertalc'.$border.'\clvmrg\cellx'.( $this->XPosition + $width );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
	
						$cnt++;
						$this->XPosition += $width;
					}
				}
			}
			$this->tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
			
			// Izris vrstic za 2. nivo - 3. in 4. vrstica
			$this->table = '\clvertalc\cellx'.( $metaWidth );	
			$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$cnt=1;
				$this->XPosition = $metaWidth;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					
					// ce imamo childe na 2. nivoju
					if(count($parentVar['sub']) > 0){
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							$width = $this->cellWidth * count($this->multiCrosstabClass->variablesList[$parentVar['sub'][0]['spr']]['options']);
							foreach($parentVar['sub'] as $var){				
								$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $width );	
								$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25). '\qc\cell';
								
								$cnt++;
								$this->XPosition += $width;
							}
						}
					}
					else{
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							$this->table .= '\clvertalc'.$border.'\clvmgf\cellx'.( $this->XPosition + $this->cellWidth );	
							$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($option, 25). '\qc\cell';
							
							$cnt++;
							$this->XPosition += $this->cellWidth;
						}
					}
				}
			}
			$this->tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
				
			$this->table = '\clvertalc\cellx'.( $metaWidth );	
			$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				$cnt=1;
				$this->XPosition = $metaWidth;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $parentVar){
					
					// ce imamo childe na 2. nivoju
					if(count($parentVar['sub']) > 0){
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							foreach($parentVar['sub'] as $var){				
								foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $suboption){
									$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $this->cellWidth );	
									$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($suboption, 25). '\qc\cell';
								
									$cnt++;
									$this->XPosition += $this->cellWidth;
								}
							}
						}
					}
					else{
						foreach($this->multiCrosstabClass->variablesList[$parentVar['spr']]['options'] as $option){
							$this->table .= '\clvertalc'.$border.'\clvmrg\cellx'.( $this->XPosition + $this->cellWidth );	
							$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
							
							$cnt++;
							$this->XPosition += $this->cellWidth;
						}
					
						$cnt++;
					}	
				}
			}
			$this->tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
		}
		// Imamo samo 1 nivo
		else{
			// Izrisemo VERTIKALNO izbrane spremenljivkec - 1. vrstica
			
			// Izracunamo sirine celic
			$this->cellWidth = round($dataWidth / $this->multiCrosstabClass->fullColSpan);
				
			$this->tableHeader = '\trowd\trql\trrh400';
					
			$this->table = '\clvertalc\cellx'.( $metaWidth );	
			$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
				
				$cnt = 1;
				$this->XPosition = $metaWidth;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
					
					$sprWidth = count($this->multiCrosstabClass->variablesList[$var['spr']]['options']) * $this->cellWidth;
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2)
						$sprWidth += $this->cellWidth;
					
					$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $sprWidth );	
					$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 35). '\qc\cell';
				
					$cnt++;
					$this->XPosition += $sprWidth;
				}
			}
			// Nimamo nobene vertikalne spremenljivke in 2 horizontalni
			elseif($this->multiCrosstabClass->rowLevel2){
				$this->table .= '\clvertalc\cellx'.( $metaWidth + $dataWidth );	
				$this->tableEnd .= '\pard\intbl '.$this->encodeText('&nbsp; '). '\qc\cell';
			}
			$this->tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
			
			
			// Izrisemo VARIABLE za spremenljivko - 2. vrstica
			if(count($this->multiCrosstabClass->selectedVars['ver'])){
						
				$this->tableHeader = '\trowd\trql\trrh400';
					
				$this->table = '\clvertalc\cellx'.( $metaWidth );	
				$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';

				$cnt=1;
				foreach($this->multiCrosstabClass->selectedVars['ver'] as $var){
						
					// Loop cez variable spremenljivke
					foreach($this->multiCrosstabClass->variablesList[$var['spr']]['options'] as $option){
						$this->table .= '\clvertalc'.$border.'\cellx'.( $metaWidth + ($cnt*$this->cellWidth) );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($option, 25). '\qc\cell';
						
						$cnt++;
					}
					
					// Suma (ce jo imamo vklopljeno)
					if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['sums'] == 1 && !$this->multiCrosstabClass->rowLevel2){						
						$this->table .= '\clvertalc'.$border.'\cellx'.( $metaWidth + ($cnt*$this->cellWidth) );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->rtf->color(13).$lang['srv_analiza_crosstab_skupaj'].$this->rtf->color(0). '\qc\cell';
					
						$cnt++;
					}
				}
				$this->tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
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
									
									$this->tableHeader = '\trowd\trql\trrh400';
																		
									if($cnt == 0){
										$this->table = '\clvertalc'.$border.'\clvmgf\cellx'.( $width );
										$this->tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25). '\qc\cell';
									}
									else{
										$this->table = '\clvertalc'.$border.'\clvmrg\cellx'.( $width );
										$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
									}
									
									// Variabla
									if($cnt2 == 0){										
										$this->table .= '\clvertalc'.$border.'\clvmgf\cellx'.( 2 * $width );
										$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($option, 25). '\qc\cell';									
									}
									else{
										$this->table .= '\clvertalc'.$border.'\clvmrg\cellx'.( 2 * $width );
										$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
									}
									
									if($cnt3 == 0){										
										$this->table .= '\clvertalc'.$border.'\clvmgf\cellx'.( 3 * $width );
										$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25). '\qc\cell';									
									}
									else{
										$this->table .= '\clvertalc'.$border.'\clvmrg\cellx'.( 3 * $width );
										$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
									}
									
									// Variabla 2
									$this->table .= '\clvertalc'.$border.'\cellx'.( 4 * $width );
									$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($suboption, 25). '\qc\cell';									
									
									// Celice s podatki
									$this->XPosition = $metaWidth;
									$this->displayDataCells($parentVar, $order0, $var, $cnt3);								
									
									$this->tableEnd .= '\pard\intbl\row';
									$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
									
									$cnt++;	
									$cnt2++;
									$cnt3++;
								}
								
								$order0++;
							}
						}
						else{
							$width = $metaWidth / 2;
							
							$this->tableHeader = '\trowd\trql\trrh400';
														
							if($cnt == 0){
								$this->table = '\clvertalc'.$border.'\clvmgf\cellx'.( $width );
								$this->tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$parentVar['spr']]['naslov'], 25). '\qc\cell';								
							}
							else{
								$this->table = '\clvertalc'.$border.'\clvmrg\cellx'.( $width );
								$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';		
							}
								
							// Variabla
							$this->table .= '\clvertalc'.$border.'\clvmgf\cellx'.( 2 * $width );
							$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($option, 25). '\qc\cell';											
							
							// Celice s podatki
							$this->XPosition = $metaWidth;
							$this->displayDataCells($parentVar, $cnt);
							
							$this->tableEnd .= '\pard\intbl\row';
							$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
							
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

						$this->tableHeader = '\trowd\trql\trrh400';
						
						if($cnt == 0){
							$this->table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $width );
							$this->tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->multiCrosstabClass->variablesList[$var['spr']]['naslov'], 25). '\qc\cell';
						}
						else{
							$this->table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $width );
							$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
						}
						
						// Variabla
						$this->table .= '\clvertalc'.$border.'\cellx'.( $width*2 );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($option, 25). '\qc\cell';						
						
						// Celice s podatki
						$this->XPosition = $metaWidth;
						$this->displayDataCells($var, $cnt);

						
						$this->tableEnd .= '\pard\intbl\row';
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
						
						$cnt++;
					}
					
					// Vrstica za sumo (ce jo imamo vklopljeno)
					if($suma){
						$this->XPosition = $metaWidth;
						
						$this->tableHeader = '\trowd\trql\trrh400';
					
						$this->table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $width );
						$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
						$this->table .= '\clvertalc'.$border.'\cellx'.( $width*2 );	
						$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->rtf->color(13).$lang['srv_analiza_crosstab_skupaj'].$this->rtf->color(0). '\qc\cell';	
						
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
						
						$this->tableEnd .= '\pard\intbl\row';
						$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
					}
				}
				
				$this->tableHeader = '\trowd\trql\trrh400';
				$this->table = '\clvertalc'.$borderT.'\cellx'.( $fullWidth );	
				$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';	
				$this->tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
			}
		}
		
		//konec tabele
		$this->rtf->MyRTF .= "}";
	}
	
	// Izpis celic v vrstici s podatki
	function displayDataCells($spr1, $var1, $spr2='', $var2=''){

		// Ce nimamo nobenega krizanja izpisemo prazne
		if($spr2 == '' && $this->multiCrosstabClass->colSpan == 0){
			
			/*for($i=0; $i<$this->multiCrosstabClass->colSpan; $i++){				
				$this->pdf->MultiCell($width, $height, '', '1', 'C', 0, 0, 0 ,0, true);					
			}*/
			
			//$this->pdf->MultiCell($dataWidth, $this->cellHeight, '', 1, 'C', 0, 0, 0 ,0, true);		
			//$this->pdf->MultiCell(1, $this->cellHeight, '', 0, 'C', 0, 1, 0 ,0, true);
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
	
		$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$bold = '\b';
	
		$text = '';
							
		$crosstabs = $this->multiCrosstabClass->crosstabData[$spr1['spr'].'-'.$spr2['spr']];
		
		// Celica s skupno sumo
		if($orientation == 2){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$text .= $crosstabs['sumaSkupna'];
				$text .= '\\line\n ';
			}
			
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$text .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$text .= '\\line\n ';
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
				
				$text .= $this->rtf->color(2).$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).$this->rtf->color(0);
				$text .= '\\line\n ';
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
				
				$text .= $this->rtf->color(6).$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').$this->rtf->color(0);
				$text .= '\\line\n ';
			}
		}
		// Suma na koncu vrstice
		elseif($orientation == 0){
			// Izpisemo podatek
			if($crosstabs['sumaVrstica'][$val]){
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$text .= $crosstabs['sumaVrstica'][$val];
					$text .= '\\line\n ';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$text .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$text .= '\\line\n ';
				}
			}
			else{
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$text .= '0';
					$text .= '\\line\n ';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$text .= $this->formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$text .= '\\line\n ';
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
				
				$text .= $this->rtf->color(2).$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).$this->rtf->color(0);
				$text .= '\\line\n ';
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
				
				$text .= $this->rtf->color(6).$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').$this->rtf->color(0);
				$text .= '\\line\n ';
			}
		}
		// Suma za stolpce
		else{
			// Izpisemo podatek
			if(isset($crosstabs['sumaStolpec'][$val])){
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$text .= $crosstabs['sumaStolpec'][$val];
					$text .= '\\line\n ';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$text .= $this->formatNumber($this->multiCrosstabClass->getCrossTabPercentage($crosstabs['sumaSkupna'], $crosstabs['sumaStolpec'][$val]), SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$text .= '\\line\n ';
				}
			}
			else{
		
				// Numerus
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
					$text .= '0';
					$text .= '\\line\n ';
				}
				// Procenti
				if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
					$text .= $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
					$text .= '\\line\n ';
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
				
				$text .= $this->rtf->color(2).$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).$this->rtf->color(0);
				$text .= '\\line\n ';
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
				
				$text .= $this->rtf->color(6).$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').$this->rtf->color(0);
				$text .= '\\line\n ';
			}
		}
		
		$text = substr($text, 0, -8);
		
		$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $this->cellWidth );	
		$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->rtf->color(13).$text.$this->rtf->color(0). '\qc\cell';
		
		$this->XPosition += $this->cellWidth;
	}
	
	// Izpis celice z vrednostmi
	function displayDataCell($crosstab, $percent, $avg, $delez){

		$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$bold = '\b';
	
		$text = '';
	
		if($crosstab > 0){
		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$text .= $crosstab;
				$text .= '\\line\n ';
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$text .= $this->formatNumber($percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$text .= '\\line\n ';
			}
		}
		else{		
			// Numerus
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['numerus'] == 1){
				$text .= '0';
				$text .= '\\line\n ';
			}
			// Procenti
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['percent'] == 1){
				$text .= $this->formatNumber(0, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%');
				$text .= '\\line\n ';
			}
		}
		
		// Povprecje
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			$text .= $this->rtf->color(2).$this->formatNumber($avg, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE')).$this->rtf->color(0);
			$text .= '\\line\n ';
		}
		
		// Delez
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			$text .= $this->rtf->color(6).$this->formatNumber($delez*100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%').$this->rtf->color(0);
			$text .= '\\line\n ';
		}
		
		$text = substr($text, 0, -8);
		
		$this->table .= '\clvertalc'.$border.'\cellx'.( $this->XPosition + $this->cellWidth );	
		$this->tableEnd .= '\pard\intbl\b0 '.$text. '\qc\cell';
		
		$this->XPosition += $this->cellWidth;
	}

	// Izris legende na dnu
	function displayLegend(){
		global $lang;
				
		if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0 || $this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			
			$this->rtf->MyRTF .= $this->rtf->_font_size(16);
			$this->rtf->MyRTF .= "{\par";	
			
			// Povprecje
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] > 0){
			
				$this->tableHeader = '\trowd\trql\trrh400';
			
				$this->table = '\clvertalc\cellx'.( 11500 );	
				$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
				
				$this->table .= '\clvertalc'.$borderT.$borderLR.($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] == 0 ? $borderB : '').'\cellx'.( 14000 );	
				$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->rtf->color(2).$lang['srv_multicrosstabs_avg'].': '.$this->rtf->color(0).$this->multiCrosstabClass->variablesList[$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar']]['variable']. '\qc\cell';

				$this->tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
			}
			
			// Delez
			if($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar'] > 0){
				
				$this->tableHeader = '\trowd\trql\trrh400';
			
				$this->table = '\clvertalc\cellx'.( 11500 );	
				$this->tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';
				
				$delez = unserialize($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delez']);
				$string = '';
				$cnt = 1;
				foreach($delez as $val){
					if($val == 1)
						$string .= $cnt.', ';
					
					$cnt++;
				}	
				$string = $this->multiCrosstabClass->variablesList[$this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['delezVar']]['variable'].' ('.substr($string, 0, -2).')';
				$this->table .= '\clvertalc'.$borderLR.$borderB.($this->multiCrosstabClass->table_settings[$this->multiCrosstabClass->table_id]['avgVar'] == 0 ? $borderT : '').'\cellx'.( 14000 );	
				$this->tableEnd .= '\pard\intbl'.$bold.' '.$this->rtf->color(6).$lang['srv_multicrosstabs_delez'].': '.$this->rtf->color(0).$string. '\qc\cell';

				$this->tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($this->tableHeader.$this->table.$this->tableEnd);
			}		
			
			$this->rtf->MyRTF .= "}";
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
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text,$length=64,$tail="..."){
		
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length){
			for($i=1;$text[$length-$i]!=" ";$i++){
				if($i == $length){
					return substr($text,0,$length) . $tail;
				}
			}
			$text = substr($text,0,$length-$i+1) . $tail;
		}
		
		return $text;
	}

	function encodeText($text){ 
	
		// popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		
		return strip_tags($text);
	}
	
}

?>
