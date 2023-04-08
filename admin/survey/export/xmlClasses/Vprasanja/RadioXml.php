<?php
/***************************************
 * Description: Priprava Xml kode za radio
 *
 *
 * Autor: Patrik Pucer
 * Datum: 10/2018
 *****************************************/

define("VARFORMAT_ELEMENT_TYPE", "numeric");

class RadioXml extends XmlSurveyElement
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

        return new RadioXml();
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
		
		//self::$spremenljivka = $spremenljivke['id'];
		$row = Cache::srv_spremenljivka($spremenljivke['id']);
		$this->spremenljivkaParams = new enkaParameters($row['params']);
		
		// Ce je spremenljivka v loopu
		$this->loop_id = $loop_id;
		
		//izpis ID in name
		$attribute = 'ID';
		$element = $spremenljivke['variable'];
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		
		$attribute = 'name';
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		//izpis ID in name - konec
		
		//Element labl
		if($spremenljivke['label']){	//ce je Labela pod Naprednom jo dodamo
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
		
		//Izpis moznih moznih odgovorov
		//pregled vseh moznih vrednosti (kategorij) po $sqlVrednosti ########################################################
		
		while ($rowVrednost = mysqli_fetch_assoc($sqlVrednosti)){
			$mozenOdgovor = ((( $rowVrednost['naslov'] ) ? $rowVrednost['naslov'] : ( ( $rowVrednost['naslov2'] ) ? $rowVrednost['naslov2'] : $rowVrednost['variable'] ) ));

			//if($rowVrednost['other']!=0){
			if($rowVrednost['other']<0){
				$missing = "Y";
			}else{
				$missing = "N";
			}			
			$catValu = strip_tags($rowVrednost['variable']);
			$labl = strip_tags($mozenOdgovor);			
			
			//Element catgry
			xmlwriter_start_element($this->xml, 'catgry');
			$attribute = 'missing';
			$element = $missing;			
				xmlwriter_write_attribute($this->xml, $attribute, $element);	//zacetek missing
					//Element catValu
					$element  = 'catValu';
					$text = $catValu;		
					$this->writeXmlElement($this->xml, $text, $element);
					//Element catValu - konec					
					//Element labl		
					$element  = 'labl';
					$text = $labl;		
					$this->writeXmlElement($this->xml, $text, $element);
					//Element labl - konec
				xmlwriter_end_attribute($this->xml);	//konec missing
			xmlwriter_end_element($this->xml);
			//Element catgry - konec			
		}
		//pregled vseh moznih vrednosti (kategorij, mozni odgovori) po $sqlVrednosti - konec
		
		//Element varFormat
		xmlwriter_start_element($this->xml, 'varFormat');
		$attribute = 'type';
		//$element = 'numeric';
		$element = VARFORMAT_ELEMENT_TYPE;
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		xmlwriter_end_element($this->xml);
		//Element varFormat - konec
		
		//Izpis moznih moznih odgovorov	- konec ########################################################
		
		//Konec elementa var
		xmlwriter_end_element($this->xml); // var
	}	
}