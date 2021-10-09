<?php
/***************************************
 * Description: Priprava Xml kode za besedilo
 *
 *
 * Autor: Patrik Pucer
 * Datum: 10/2018
 *****************************************/

define("VARFORMAT_ELEMENT_TYPE_BESEDILO", "character");

class BesediloXml extends XmlSurveyElement
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

        return new BesediloXml();
    }
	
	public function export($spremenljivke=null, $db_table=null, $preveriSpremenljivko=null, $export_subtype=null, $loop_id=null, $xml=null){
		global $lang;
		
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		$this->xml = $xml;
		
		// iz baze preberemo vse moznosti - ko nimamo izpisa z odgovori respondenta
		$sqlVrednostiString = "SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red";
		$sqlVrednosti = sisplet_query($sqlVrednostiString);
		
		//$numRowsSql = mysqli_num_rows($sqlVrednosti);

		//Zacetek elementa var
		xmlwriter_start_element($this->xml, 'var');
		
		//izpis ID in name
		$attribute = 'ID';
		$element = $spremenljivke['variable'];
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
		$element  = 'qstnLit';
		$text = strip_tags($spremenljivke['naslov'], '<a><img><ul><li><ol><br>');
		$this->writeXmlElement($this->xml, $text, $element);
		xmlwriter_end_element($this->xml);
		//Element qstn - konec
		
		//Priprava besedila vprasanja in njen izpis - konec #################################################################
		
		//Element varFormat
		xmlwriter_start_element($this->xml, 'varFormat');
		$attribute = 'type';
		$element = VARFORMAT_ELEMENT_TYPE_BESEDILO;
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		xmlwriter_end_element($this->xml);
		//Element varFormat - konec
		
		//Konec elementa var
		xmlwriter_end_element($this->xml); // var
	}	
}