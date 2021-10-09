<?php
/***************************************
 * Description: Priprava Xml kode za checkbox
 *
 *
 * Autor: Patrik Pucer
 * Datum: 10/2018
 *****************************************/

define("VARGRP_ELEMENT_TYPE", "multipleResp");
define("VARFORMAT_ELEMENT_TYPE", "numeric");

class CheckboxXml extends XmlSurveyElement
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

        return new CheckboxXml();
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
		//polja za shranjevanje podatkov za izpis - konec
		
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta
		$sqlVrednostiString = "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red";
		$sqlVrednosti = sisplet_query($sqlVrednostiString);
		
		//$numRowsSql = mysqli_num_rows($sqlVrednosti);
		
		//Pobiranje podatkov za izpis
		//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti ########################################################		
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			$mozenOdgovor = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
			
			$varName = strip_tags($rowVrednost['variable']);
			
			$qstnLit = strip_tags($mozenOdgovor);
			
			if($rowVrednost['other']<0){
				$missing = "Y";
			}else{
				$missing = "N";
			}	
			
			//belezenje podatkov za kasnejsi izpis
			$qstnLits[] = $qstnLit;
			$varNames[] = $varName;
			$missings[] = $missing;
			//belezenje podatkov za kasnejsi izpis - konec
				
		}
		//pregled vseh moznih vrednosti (kategorij, mozni odgovori) po $sqlVrednosti - konec
		//Pobiranje podatkov za izpis - konec

		//Zacetek elementa varGrp
/* 		xmlwriter_start_element($this->xml, 'varGrp');
			$attribute = 'name';
			$element = $spremenljivke['variable'];
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);
			
			$attribute = 'type';
			$element = VARGRP_ELEMENT_TYPE;
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);

			$attribute = 'var';
			$element = "";
			foreach($varNames as $key => $varName){
				if((int)$varName==0){	//ce ni missing vrednost
					if($key == 0){
						$element .= $varName;
					}else{
						$element .= " ".$varName;
					}
				}			
			}		
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);		
		xmlwriter_end_element($this->xml); // varGrp */
		//Konec elementa varGrp
	
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
					if($spremenljivke['label']){	//ce je Labela pod Napredno, jo dodamo					
						$element  = 'labl';
						$text = $spremenljivke['label'];		
						$this->writeXmlElement($this->xml, $text, $element);
					}
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
						$text = strip_tags($qstnLits[$key], '<a><img><ul><li><ol><br>');
						$this->writeXmlElement($this->xml, $text, $element);
					xmlwriter_end_element($this->xml);
					//Element qstn - konec					
					//Priprava besedila vprasanja in njen izpis - konec #################################################################
					
					//Element catgry
					$attribute = 'missing';
					$missingElement = $missings[$key];			
					$catValu = [1, 0];
					$lablCatgry = ['Izbran', 'Ni izbran'];
					foreach($catValu as $keyCatgry => $catVal){
						xmlwriter_start_element($this->xml, 'catgry');
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
					}
					//Element catgry - konec	
					
					//Element varFormat
					xmlwriter_start_element($this->xml, 'varFormat');
					$attribute = 'type';
					$element = VARFORMAT_ELEMENT_TYPE;
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);
					xmlwriter_end_element($this->xml);
					//Element varFormat - konec
				
				//Konec elementa var ###############################################################################
				xmlwriter_end_element($this->xml); // var
			//}			
		}
	}	
}