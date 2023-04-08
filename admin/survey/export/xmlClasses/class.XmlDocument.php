<?php

/**
 *
 *	Class ki skrbi inicializacijo xml dokumenta
 *
 *
 */
 
####################################

####################################konec

include('../../vendor/autoload.php');
define("CODEBOOK_VERSION", 2.1);
 
class XmlDocument{
	
	var $export_type;			// Tip izvoza (vprašalnik, analize...)
	var $export_subtype;		// Podtip izvoza
	var $export_format;			// Format izvoza (latex->pdf, latex->rtf, xls...)
	
	var $anketa;				// ID ankete
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	
	//spremenljivke za Nastavitve pdf/rtf izvozov	
	var $export_font_size = 10;
	var $export_numbering = 0;
	var $export_show_if = 0;
	var $export_show_intro = 0;
	var $export_data_type = 0;	// nacin izpisa vprasanlnika - kratek -> 0, dolg -> 1, zelo kratek -> 2
	var $export_data_font_size;
	var $export_data_numbering;
	var $export_data_show_recnum;
	var $export_data_show_if;
	var $export_data_PB;
	var $export_data_skip_empty;
	var $export_data_skip_empty_sub;
	var $export_data_landscape;
	//spremenljivke za Nastavitve pdf/rtf izvozov - konec
	
	var $head;	// za shrambo tex preamble in zacetek dokumenta
	var $tail;		// za shrambo tex zakljucka dokumenta
	var $naslovnicaUkaz; //za shrambo ukaza za izris naslovnice dokumenta
	var $headerAndFooter; //za shrambo ukaza za izris glave in noge dokumenta
	protected $surveyStyle; //za shrambo environmenta vprasalnika (omogoca spreminjanje velikosti besedila glede na izbrano nastavitev)
	protected $analysisStyle; //za shrambo environmenta vprasalnika (omogoca spreminjanje velikosti besedila glede na izbrano nastavitev)
	protected $statusStyle; //za shrambo environmenta vprasalnika (omogoca spreminjanje velikosti besedila glede na izbrano nastavitev)
	
	protected $isAnswer = '';
	protected $isAnswerBreakPodVprasanjem = '';
	
	protected $xml='';
	
	function __construct($anketa=null){
		global $site_path, $global_user_id, $admin_type, $lang;		
		$this->anketa = $anketa;
	}
	###################################### konec construct-a
	
	public function createXmlDocument($export_type='', $export_subtype='', $export_format=''){
		global $lang, $site_path;
		
		// Ustvarimo ogrodje dokumenta (locena funkcija), glavo, nogo, naslovnico...		
		$this->InitDocumentVars($export_type, $export_subtype, $export_format);	//pridobi vse potrebne spremenljivke za ustvarjanje ogrodja dokumenta
		
		#spremenljivke#################################################################
		$datumGeneriranjaIzvoza = date("d. m. Y");
		
		$anketaUstvarjena = SurveyInfo::getInstance()->getSurveyInsertDate();
		$dolgoImeAnkete = SurveyInfo::getSurveyColumn('naslov');
		$kratkoImeAnkete = SurveyInfo::getSurveyColumn('akronim');		
		$steviloVprasanj = SurveyInfo::getSurveyQuestionCount();		
		$anketaSpremenjena = SurveyInfo::getSurveyEditDate();		
		$avtorAnkete = SurveyInfo::getSurveyInsertName();
		$avtorSpremenilAnketo = SurveyInfo::getSurveyEditName();		
		$surveyId = SurveyInfo::getSurveyId();
		$userName = SurveyInfo::getUserInsertInfo('name');
		$userSurname = SurveyInfo::getUserInsertInfo('surname');
		$enkaVersion = SurveyInfo::getEnkaVersion('value');
		$datumAktivacije = SurveyInfo::getSurveyStartsDate();
		$datumDeaktivacije = SurveyInfo::getSurveyExpireDate();
		$firstEntryDate = SurveyInfo::getSurveyFirstEntryDate();
		$lastEntryDate = SurveyInfo::getSurveyLastEntryDate();		
		$completedSurveys = SurveyInfo::getValidSurveysCount();
		$partiallyCompletedSurveys = SurveyInfo::getPartiallyValidSurveysCount();			
		$emptySurveys = SurveyInfo::getInvalidSurveysCount();
		
		$sas = new SurveyAdminSettings(0, $surveyId);
		$skupni_cas = $sas->testiranje_cas(1);
		
		################################################
		#spremenljivke################################################################# konec
		
		$xml = xmlwriter_open_memory(); //Creating new xmlwriter using memory for string output
		$this->xml = $xml;
		
		//zacetek xml dokumenta
		xmlwriter_set_indent($this->xml, 1);
		$res = xmlwriter_set_indent_string($this->xml, ' ');
		xmlwriter_start_document($this->xml, '1.0', 'UTF-8');
		//zacetek xml dokumenta - konec
		
		//Dodajanje DOCTYPE'<!DOCTYPE codeBook SYSTEM "http://www.ddialliance.org/sites/default/files/Version2-1.dtd">'
		xmlwriter_start_dtd($this->xml, 'codeBook', null, 'http://www.ddialliance.org/sites/default/files/Version2-1.dtd');
		xmlwriter_end_dtd($this->xml);
		//Dodajanje DOCTYPE - konec
		
		//Zacetek elementa codeBook
		xmlwriter_start_element($this->xml, 'codeBook');
		$attribute = 'version';
		$element = CODEBOOK_VERSION;
		$this->writeXmlAttr4Element($this->xml, $attribute, $element);
		//Zacetek elementa codeBook - konec
				
		//Element stdyDscr ##################################################################
		xmlwriter_start_element($this->xml, 'stdyDscr'); //Zacetek elementa stdyDscr
		
			xmlwriter_start_element($this->xml, 'citation'); //Zacetek elementa citation
			
				xmlwriter_start_element($this->xml, 'titlStmt'); //Zacetek elementa titlStmt					
					//Element titl		
					$element  = 'titl';
					$text = $dolgoImeAnkete;		
					$this->writeXmlElement($this->xml, $text, $element);
					//Element titl - konec
					
					//Element altTitl
					$element  = 'altTitl';
					$text = $kratkoImeAnkete;		
					$this->writeXmlElement($this->xml, $text, $element);
					//Element altTitl - konec
					
					//Element IDNo
					$element  = 'IDNo';
					$text = $surveyId;		
					$this->writeXmlElement($this->xml, $text, $element);
					//Element IDNo - konec					
				xmlwriter_end_element($this->xml); //Zakljucek elementa titlStmt
				
				xmlwriter_start_element($this->xml, 'rspStmt'); //Zacetek elementa rspStmt				
					//Element AuthEnty
					$element  = 'AuthEnty';
					//$text = $userName.' '.$userSurname;					
					$text = $avtorAnkete;					
					$this->writeXmlElement($this->xml, $text, $element);
					//Element AuthEnty - konec				
				xmlwriter_end_element($this->xml); //Zakljucek elementa rspStmt
				
				xmlwriter_start_element($this->xml, 'prodStmt'); //Zacetek elementa prodStmt				
					xmlwriter_start_element($this->xml, 'prodDate');	//Zacetek elementa prodDate
						$attribute = 'date';
						$element = date('Y-m-d');
						$writeAttribute = 1;
						$this->writeXmlAttr4Element($this->xml, $attribute, $element, $writeAttribute);
					xmlwriter_end_element($this->xml); //Zakljucek elementa prodDate

					xmlwriter_start_element($this->xml, 'software');	//Zacetek elementa software
						$attribute = 'version';
						$element = $enkaVersion;
						$writeAttribute = 0;
						$string = 1;
						$text = '1KA - OneClick Survey';
						$this->writeXmlAttr4Element($this->xml, $attribute, $element, $writeAttribute, $string, $text);
					xmlwriter_end_element($this->xml); //Zakljucek elementa software					
				xmlwriter_end_element($this->xml); //Zakljucek elementa prodStmt
				
				xmlwriter_start_element($this->xml, 'verStmt'); //Zacetek elementa verStmt				
					xmlwriter_start_element($this->xml, 'version');	//Zacetek elementa version
						
						//izpis type in date
						$attribute = 'type';
						$element = 'version';
						$this->writeXmlAttr4Element($this->xml, $attribute, $element);
						
						$attribute = 'date';
						$element = date('Y-m-d');
						$writeAttribute = 1;
						$this->writeXmlAttr4Element($this->xml, $attribute, $element, $writeAttribute);
					xmlwriter_end_element($this->xml); //Zakljucek elementa version					
				xmlwriter_end_element($this->xml); //Zakljucek elementa verStmt
			
			xmlwriter_end_element($this->xml); //Zakljucek elementa citation
			
			xmlwriter_start_element($this->xml, 'stdyInfo'); //Zacetek elementa stdyInfo
			
				xmlwriter_start_element($this->xml, 'sumDscr'); //Zacetek elementa sumDscr
				
					xmlwriter_start_element($this->xml, 'collDate');	//Zacetek elementa collDate						
						//izpis event in date
						$attribute = 'event';
						$element = 'start';
						$this->writeXmlAttr4Element($this->xml, $attribute, $element);
						
						$attribute = 'date';
						$date = date_create($datumAktivacije);
						$element = date_format($date, 'Y-m-d');
						$writeAttribute = 1;
						$this->writeXmlAttr4Element($this->xml, $attribute, $element, $writeAttribute);
					xmlwriter_end_element($this->xml); //Zakljucek elementa collDate
					
					xmlwriter_start_element($this->xml, 'collDate');	//Zacetek elementa collDate						
						//izpis event in date
						$attribute = 'event';
						$element = 'end';
						$this->writeXmlAttr4Element($this->xml, $attribute, $element);
						
						$attribute = 'date';
						$date = date_create($datumDeaktivacije);
						$element = date_format($date, 'Y-m-d');
						$writeAttribute = 1;
						$this->writeXmlAttr4Element($this->xml, $attribute, $element, $writeAttribute);
					xmlwriter_end_element($this->xml); //Zakljucek elementa collDate
					
					//Element dataKind
					$element  = 'dataKind';				
					$text = 'survey';					
					$this->writeXmlElement($this->xml, $text, $element);
					//Element dataKind - konec	
					
				xmlwriter_end_element($this->xml); //Zakljucek elementa sumDscr
				
			xmlwriter_end_element($this->xml); //Zakljucek elementa stdyInfo
			
			xmlwriter_start_element($this->xml, 'method'); //Zacetek elementa method
				
				xmlwriter_start_element($this->xml, 'dataCol'); //Zacetek elementa dataCol
					//Element collMode		
					$element  = 'collMode';
					$text = 'web survey';		
					$this->writeXmlElement($this->xml, $text, $element);
					//Element collMode - konec
					
					//Element collSitu
					$dateFirstEntryDate = date_create($firstEntryDate);
					$elementFirstEntryDate = date_format($dateFirstEntryDate, 'Y-m-d');
					$dateLastEntryDate = date_create($lastEntryDate);
					$elementLastEntryDate = date_format($dateLastEntryDate, 'Y-m-d');
					
					$element  = 'collSitu';
 					$text = '
						Completed questionnaires: '.$completedSurveys.' 
						Partially completed questionnaires:  '.$partiallyCompletedSurveys.' 
						Empty questionnaires: '.$emptySurveys.' 
						First entry: '.$elementFirstEntryDate.' 
						Last entry: '.$elementLastEntryDate.' 
						Average duration: '.$skupni_cas.' '; 
					xmlwriter_start_cdata($this->xml, $text);					
					xmlwriter_end_cdata($this->xml);
					
					$this->writeXmlElement($this->xml, $text, $element);
					//Element collSitu - konec
					
				xmlwriter_end_element($this->xml); //Zakljucek elementa dataCol
				
			xmlwriter_end_element($this->xml); //Zakljucek elementa method

			
		xmlwriter_end_element($this->xml); //Zakljucek elementa stdyDscr
		//Element stdyDscr - konec ##################################################################
		
		//Glede na tip in podtip poklicemo ustrezen razred za izris vsebine xml porocila
		switch ($export_type){
			case 'survey':
				$survey = new XmlSurvey($this->anketa, $export_format, $xml);
 				switch ($export_subtype){
					case 'q_empty_xml':
						$survey->displaySurvey($export_subtype, $this->export_data_type);
					break;
				}
			break;	
		}
		
		//Glede na tip in podtip poklicemo ustrezen razred za izris vsebine xml porocila - konec
		
		//Zakljucek elementa Codebook
		xmlwriter_end_element($this->xml);
		//Zakljucek elementa Codebook - konec
		
		//Zakljucek xml dokumenta
		xmlwriter_end_document($this->xml);

		$this->xml = xmlwriter_output_memory($xml);	//Returns current buffer with xml
		
		# generating xml file
 		$filename = 'export_'.$export_subtype.'_'.$surveyId.'.xml';

		$fp = fopen(admin_temp($filename), "w") or
				die ("cannot generate file $filename<br>\n");
		fwrite($fp, $this->xml) or
				die ("cannot send data to file<br>\n");
		fclose($fp);
		# generating xml file - konec
		
		# generating xml output
		$this->OutputXml($filename);
		# generating xml output - konec
		
	}
	###################################### konec funkcije createDocument
	
	
	#####################################################################################################
	//Podporne funkcije za delovanje createDocument
	#####################################################################################################
	function InitDocumentVars($export_type='', $export_subtype='', $export_format=''){
		global $site_path;
		
		$this->export_type = $export_type;
		$this->export_subtype = $export_subtype;
		$this->export_format = $export_format;		
		
		if($export_type == 'survey'){	//ce je format 'survey', potrebuje naslednje nastavitve			
			SurveySetting::getInstance()->Init($this->anketa);
		}
		
	}
	###################################### konec InitDocumentVars
	
	//Funkcija za generiranje in brisanje datotek za pdf izvoz
	function OutputXml($filename=''){
		//priprava header za xml in forced download
		header('Content-type: application/xml');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		//priprava header za xml in forced download - konec
		
		readfile(admin_temp($filename));
		
		//brisanje temp xml datoteke
		unlink(admin_temp($filename));
		//brisanje temp xml datoteke - konec
	}
	#############################################	
		
	function writeXmlAttr4Element($xml=null, $attribute=null, $element=null, $writeAttribute=0, $string=0, $text=''){
		$this->xml = $xml;		
		if($writeAttribute){
			xmlwriter_write_attribute($this->xml, $attribute, $element);
		}else{
			xmlwriter_start_attribute($this->xml,  $attribute);
		}
		xmlwriter_text($this->xml, $element);		
		xmlwriter_end_attribute($this->xml);
		
		if($string){
			xmlwriter_text($this->xml, $text);
		}		
	}
	
	function writeXmlElement($xml=null, $text=null, $element=null){
		$this->xml = $xml;
		xmlwriter_start_element($this->xml,  $element);
		xmlwriter_text($this->xml, $text);
		xmlwriter_end_element($this->xml);
	}	
}