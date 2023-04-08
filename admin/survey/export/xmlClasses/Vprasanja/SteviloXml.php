<?php
/***************************************
 * Description: Priprava Xml kode za stevilo
 *
 *
 * Autor: Patrik Pucer
 * Datum: 10/2018
 *****************************************/

define("VARFORMAT_ELEMENT_TYPE_STEVILO", "numeric");

class SteviloXml extends XmlSurveyElement
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

        return new SteviloXml();
    }
	
	public function export($spremenljivke=null, $db_table=null, $preveriSpremenljivko=null, $export_subtype=null, $loop_id=null, $xml=null){
		global $lang;

		$this->xml = $xml;

		//Priprava besedila vprasanja in njen izpis #################################################################
		$rowl = $this->srv_language_spremenljivka($spremenljivke);
		if (strip_tags($rowl['naslov']) != '') $spremenljivke['naslov'] = $rowl['naslov'];
		if (strip_tags($rowl['info']) != '') $spremenljivke['info'] = $rowl['info'];

		//Ureditev izbire besedila vprasanja in enote
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta
		$sqlVrednostiString = "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red";
		$sqlVrednosti = sisplet_query($sqlVrednostiString);
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			$besediloEnota = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));
		}
		//Ureditev izbire besedila vprasanja in enote - konec

		//Zacetek elementa var
		xmlwriter_start_element($this->xml, 'var');
		
		//Izpis ID in name
		$attribute = 'ID';
		$element = $spremenljivke['variable'];
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		
		$attribute = 'name';
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		//Izpis ID in name - konec
		
		if($besediloEnota!=$lang['srv_new_text']){
			$attribute = 'measUnit';
			$element = $besediloEnota;
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		}
		
		//Element labl
		if($spremenljivke['label']){	//ce je Labela pod Naprednom jo dodamo
			$element  = 'labl';
			$text = $spremenljivke['label'];
			$this->writeXmlElement($this->xml, $text, $element);
		}
		//Element labl - konec
		
		//Element qstn
		xmlwriter_start_element($this->xml, 'qstn');
		$element  = 'qstnLit';
		$text = strip_tags($spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
		$this->writeXmlElement($this->xml, $text, $element);
		xmlwriter_end_element($this->xml);
		//Element qstn - konec		
		//Priprava besedila vprasanja in njen izpis - konec #################################################################
		
		//Element valrng / valRng
		if($spremenljivke['vsota_min']!=$spremenljivke['vsota_limit']){	//ce je prisoten vsaj en limit, dodaj element
			//xmlwriter_start_element($this->xml, 'valRng');
			xmlwriter_start_element($this->xml, 'valrng');
				xmlwriter_start_element($this->xml, 'range');	//element range
					$attribute = 'min';
					$element = $spremenljivke['vsota_min'];
					$this->writeXmlAttr4Element($this->xml, $attribute, $element);
					
					if($spremenljivke['vsota_limit']>$spremenljivke['vsota_min']){
						$attribute = 'max';
						$element = $spremenljivke['vsota_limit'];
						$this->writeXmlAttr4Element($this->xml, $attribute, $element);
					}
				xmlwriter_end_element($this->xml);	//element range - konec
			xmlwriter_end_element($this->xml);
		}
		//Element valrng / valRng - konec
		
		//Element varFormat
		xmlwriter_start_element($this->xml, 'varFormat');
			$attribute = 'type';
			$element = VARFORMAT_ELEMENT_TYPE_STEVILO;
			$this->writeXmlAttr4Element($this->xml, $attribute, $element);
			
/* 			if($besediloEnota!=$lang['srv_new_text']){
				$attribute = 'measUnit';
				$element = $besediloEnota;
				$this->writeXmlAttr4Element($this->xml, $attribute, $element);
			} */		
		xmlwriter_end_element($this->xml);
		//Element varFormat - konec
		
		//Konec elementa var
		xmlwriter_end_element($this->xml); // var
	}	
}