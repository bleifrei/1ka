<?php
/***************************************
 * Description: Priprava Xml kode za klasicne tabele
 *
 *
 * Autor: Patrik Pucer
 * Datum: 10/2018
 *****************************************/

define("VARGRP_ELEMENT_TYPE_GRID", "Grid");
define("VARGRP_ELEMENT_TYPE_MULTIRESP", "multipleResp");
define("VARFORMAT_ELEMENT_TYPE_GRID", "numeric");

class MultiGridXml extends XmlSurveyElement
{
    public function __construct()
    {
        //parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	protected $xml;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new MultiGridXml();
    }
	
	public function export($spremenljivke=null, $db_table=null, $preveriSpremenljivko=null, $export_subtype=null, $loop_id=null, $xml=null){
		global $lang;
		
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		$this->xml = $xml;
		
		//polja za shranjevanje podatkov za izpis
		$qstnLits = array();
		$varNames = array();
		$missings = array();
		$catValus = array();
		$labls = array();
		$varNamesDb = array();	//atribut name ob dvojni tabeli
		//polja za shranjevanje podatkov za izpis - konec
		
		//Pobiranje podatkov za izpis ######################################################################################################
		
		
		//VREDNOSTI
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta
		$sqlVrednostiString = "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red";
		$sqlVrednosti = sisplet_query($sqlVrednostiString);
		
		//$numRowsSql = mysqli_num_rows($sqlVrednosti);
		//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){			
			$mozenOdgovor = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
			
			$varName = strip_tags($rowVrednost['variable']);
			$qstnLit = strip_tags($mozenOdgovor);
			
			//belezenje podatkov za kasnejsi izpis
			$varNames[] = $varName;
			$qstnLits[] = $qstnLit;
			//belezenje podatkov za kasnejsi izpis - konec
		}
		//pregled vseh moznih vrednosti (kategorij, mozni odgovori) po $sqlVrednosti - konec
		//VREDNOSTI - Konec
		
		//MOZNI ODGOVORI
		$sqlMozniOdgovoriString = "SELECT naslov, variable, other FROM srv_grid WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red";
		$sqlMozniOdgovori = sisplet_query($sqlMozniOdgovoriString);
		while ($rowMozniOdgovori = mysqli_fetch_assoc($sqlMozniOdgovori)){
			$labl = strip_tags($rowMozniOdgovori['naslov']);
			if($rowMozniOdgovori['other']==0){	//ce ni missing
				$catValu = strip_tags($rowMozniOdgovori['variable']);
			}else{
				$catValu = $rowMozniOdgovori['other'];
			}
			
			if($rowMozniOdgovori['other']<0){
				$missing = "Y";
			}else{
				$missing = "N";
			}	
			
			//belezenje podatkov za kasnejsi izpis
			$labls[] = $labl;
			$missings[] = $missing;
			$catValus[] = $catValu;
			//belezenje podatkov za kasnejsi izpis - konec
		}
		//MOZNI ODGOVORI - KONEC		
		
		//Pobiranje podatkov za izpis - konec ###############################################################################################

		//Zacetek elementa varGrp
/* 		xmlwriter_start_element($this->xml, 'varGrp');
			$attribute = 'name';
			$element = $spremenljivke['variable'];
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);
			
			$attribute = 'type';
			$element = VARGRP_ELEMENT_TYPE_GRID;
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);

			$attribute = 'var';
			$element = "";			
			foreach($varNames as $key => $varName){
				if((int)$varName==0){	//ce ni missing vrednost
					if($key == 0){
						$element .= $varName;
					}elseif($key == 2&&$spremenljivke['enota']==3&&$spremenljivke['tip']==6){	//ce je index 2 in je dvojna tabela klasicne tabele, ne rabimo vec oznak spremenljivke
						break;
					}
					else{
						$element .= " ".$varName;
					}
				}			
			}	
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);

				//Element labl				
				if($spremenljivke['label']){	//ce je Labela pod Napredno, jo dodamo
					$element  = 'labl';
					$text = $spremenljivke['label'];
					$this->writeXmlElement($this->xml, $text, $element);
				}
				//Element labl - konec

		xmlwriter_end_element($this->xml); // varGrp */
		//Konec elementa varGrp
		
		//CE JE DVOJNA TABELA KLASICNE TABELE ###############################################################
		if($spremenljivke['enota']==3&&$spremenljivke['tip']==6){	//ce je dvojna tabela klasicne tabele
		
			//Preureditev $catValus, da se razpolovi dolzino polja
			$startIndexCatValus = count($catValus)/2 - 1;
			$numOfCatValus = count($catValus)/2;
			array_splice($catValus, $startIndexCatValus, $numOfCatValus);
			//Preureditev $catValus, da se razpolovi dolzino polja - konec			

			//Preureditev $labls in $qstnLits, da se doda se enkrat toliko odgovorov
			foreach($labls as $labl){
				$labls[] = $labl;
			}
			foreach($qstnLits as $qstnLit){
				$qstnLits[] = $qstnLit;
			}
			//Preureditev $labls in $qstnLit, da se doda se enkrat toliko odgovorov - konec				
		
			//Zacetek elementa varGrp za prvo podtabelo
/* 			xmlwriter_start_element($this->xml, 'varGrp');
				$attribute = 'name';
				$element = $spremenljivke['variable'].'a';
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);
				
				$attribute = 'type';
				$element = VARGRP_ELEMENT_TYPE_GRID;
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);

				$attribute = 'var';
				$element = "";
				$char = 'a';	//za dodatno oznacevanje var
				foreach($varNames as $key => $varName){					
					if((int)$varName==0){	//ce ni missing vrednost
						if($key == 0){
							//$element .= $spremenljivke['variable'].'a'.$char;
							$elementTmp = $spremenljivke['variable'].'a'.$char;
							$element .= $elementTmp;
						}else{							
							//$element .= " ".$spremenljivke['variable'].'a'.$char;
							$elementTmp = " ".$spremenljivke['variable'].'a'.$char;
							$element .= $elementTmp;
						}
						$varNamesDb[] = $elementTmp;
					}
					$char++;
				}		
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);

					//Element labl
					if($spremenljivke['grid_subtitle1']){	//ce je label za podnaslov 1
						$element  = 'labl';
						$text = $spremenljivke['grid_subtitle1'];
						$this->writeXmlElement($this->xml, $text, $element);
					}
					//Element labl - konec
				
			xmlwriter_end_element($this->xml); // varGrp */
			//Konec elementa varGrp	za prvo podtabelo
			
			//Zacetek elementa varGrp za drugo podtabelo
/* 			xmlwriter_start_element($this->xml, 'varGrp');
				$attribute = 'name';
				$element = $spremenljivke['variable'].'b';
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);
				
				$attribute = 'type';
				$element = VARGRP_ELEMENT_TYPE_GRID;
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);

				$attribute = 'var';
				$element = "";
				$char = 'a';	//za dodatno oznacevanje var
				foreach($varNames as $key => $varName){					
					if((int)$varName==0){	//ce ni missing vrednost
						$elementTmpDb = $spremenljivke['variable'].'b'.$char;
						if($key == 0){							
							$element .= $elementTmpDb;
						}else{
							$elementTmp = " ".$elementTmpDb;
							$element .= $elementTmp;
						}
						$varNamesDb[] = $elementTmpDb;
					}
					$char++;
				}
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);
				
					//Element labl
					if($spremenljivke['grid_subtitle2']){	//ce je label za podnaslov 2			
						$element  = 'labl';
						$text = $spremenljivke['grid_subtitle2'];
						$this->writeXmlElement($this->xml, $text, $element);
					}
					//Element labl - konec

				xmlwriter_end_element($this->xml); // varGrp */
			//Konec elementa varGrp	za drugo podtabelo
			
			//$char = 'a';	//za dodatno oznacevanje var v naslednjih elementih
			$varNames = $varNamesDb;
		}
		//CE JE DVOJNA TABELA KLASICNE TABELE - KONEC ###############################################################

		if($spremenljivke['tip']==16){	//ce je multicheckbox, uredi varGrp
			
/* 			for($l=0;$l<count($varNames);$l++){
				//Preureditev $labls in $qstnLits, da je dovolj elementov v polju za izpis
				foreach($labls as $labl){
					$labls[] = $labl;
				}
				foreach($qstnLits as $qstnLit){
					$qstnLits[] = $qstnLit;
				}
				//Preureditev $labls in $qstnLit, da je dovolj elementov v polju za izpis - konec	
			}
			
			foreach($varNames as $key => $varName){	//za vsak mozen odgovor
				//Zacetek elementa varGrp za prvo podtabelo
				xmlwriter_start_element($this->xml, 'varGrp');
					$attribute = 'name';
					$element = $varName;
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);
					
					$attribute = 'type';
					$element = VARGRP_ELEMENT_TYPE_MULTIRESP;
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);

					$attribute = 'var';
					$element = "";
					$char = 'a';	//za dodatno oznacevanje var
 					foreach($varNames as $key2 => $varName2){				
						if((int)$varName2==0){	//ce ni missing vrednost
							$elementTmpDb = $varName.$char;
							if($key2 == 0){								
								$element .= $elementTmpDb;
							}else{								
								$elementTmp = " ".$elementTmpDb;
								$element .= $elementTmp;
							}
							$varNamesDb[] = $elementTmpDb;
						}
						$char++;
					}
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);

						//Element labl
						if($spremenljivke['grid_subtitle1']){	//ce je label za podnaslov 1
							$element  = 'labl';
							$text = $spremenljivke['grid_subtitle1'];
							$this->writeXmlElement($this->xml, $text, $element);
						}
						//Element labl - konec
					
				xmlwriter_end_element($this->xml); // varGrp
				//Konec elementa varGrp	za prvo podtabelo
			}
			$varNames = $varNamesDb; */
			
		}
		
		
		foreach($varNames as $key => $varName){	//za vsak mozen odgovor
		
			//if((int)$varName==0){	//ce ni missing vrednost
				//Zacetek elementa var ###############################################################################
				xmlwriter_start_element($this->xml, 'var');
					
					//izpis ID in name
					$attribute = 'ID';
					$element = $varName;
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);					
					$attribute = 'name';
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);
					//izpis ID in name - konec
					
					//Element labl		
					$element  = 'labl';
					$text = strip_tags($qstnLits[$key], '<a><img><ul><li><ol><br>');
					$this->writeXmlElement($this->xml, $text, $element);
					//Element labl - konec
					
					//Priprava besedila vprasanja in njen izpis #################################################################
					$rowl = $this->srv_language_spremenljivka($spremenljivke);
					if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];
					if (strip_tags($rowl['info']) != '') $spremenljivke['info'] = $rowl['info'];					
					//Element qstn
					xmlwriter_start_element($this->xml, 'qstn');						
						$element  = 'preQTxt';
						$text = strip_tags($spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
						$this->writeXmlElement($this->xml, $text, $element);

						$element  = 'qstnLit';
						//$text = strip_tags($qstnLits[$key], '<a><img><ul><li><ol><br>');
						$text = strip_tags($qstnLits[$key], '<a><img><ul><li><ol><br>');
						$this->writeXmlElement($this->xml, $text, $element);
					xmlwriter_end_element($this->xml);
					//Element qstn - konec					
					//Priprava besedila vprasanja in njen izpis - konec #################################################################
					
					//Element catgry
					$attribute = 'missing';
					if($spremenljivke['tip']==16){	//ce je multicheckbox,
						//$missingElement = $missings[$key];		
/* 						$catValu = [1, 0];
						$lablCatgry = ['Izbran', 'Ni izbran'];
						foreach($catValu as $keyCatgry => $catVal){
							xmlwriter_start_element($this->xml, 'catgry');
								$missingElement = $missings[$keyCatgry];
								xmlwriter_write_attribute($this->xml, $attribute, $missingElement);	//zacetek missing
									//Element catValu
									$element  = 'catValu';
									$text = $catVal;		
									$this->writeXmlElement($this->xml, $text, $element);
									//Element catValu - konec					
									//Element labl		
									$element  = 'labl';
									$text = $lablCatgry[$keyCatgry];		
									$this->writeXmlElement($this->xml, $text, $element);
									//Element labl - konec
								xmlwriter_end_attribute($this->xml);	//konec missing
							xmlwriter_end_element($this->xml);
						} */
					}else{
						foreach($catValus as $keyCatgry => $catVal){
							xmlwriter_start_element($this->xml, 'catgry');
								$missingElement = $missings[$keyCatgry];
								xmlwriter_write_attribute($this->xml, $attribute, $missingElement);	//zacetek missing
									//Element catValu
									$element  = 'catValu';
									$text = $catVal;		
									$this->writeXmlElement($this->xml, $text, $element);
									//Element catValu - konec					
									//Element labl		
									$element  = 'labl';
									$text = $labls[$keyCatgry];		
									$this->writeXmlElement($this->xml, $text, $element);
									//Element labl - konec
								xmlwriter_end_attribute($this->xml);	//konec missing
							xmlwriter_end_element($this->xml);
						}
					}
					//Element catgry - konec
					
					//Element varFormat
					xmlwriter_start_element($this->xml, 'varFormat');
					$attribute = 'type';
					$element = VARFORMAT_ELEMENT_TYPE_GRID;
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);
					xmlwriter_end_element($this->xml);
					//Element varFormat - konec
				//Konec elementa var ###############################################################################
				xmlwriter_end_element($this->xml); // var
			//}			
		}
	}	
}