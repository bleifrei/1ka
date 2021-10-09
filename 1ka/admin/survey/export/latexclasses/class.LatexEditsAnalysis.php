<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	//require_once('../exportclases/class.enka.pdf.php');
	
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge
	
	define("NUM_DIGIT_AVERAGE", 2); 	// stevilo digitalnih mest za povprecje
	define("NUM_DIGIT_DEVIATION", 2); 	// stevilo digitalnih mest za povprecje

	define("M_ANALIZA_DESCRIPTOR", "descriptor");
	define("M_ANALIZA_FREQUENCY", "frequency");

	define("RADIO_BTN_SIZE", 3);
	define("CHCK_BTN_SIZE", 3);
	define("LINE_BREAK", 6);
	
	define ('FRAME_TEXT_WIDTH', 0.3);
	define ('FRAME_WIDTH', 480);
	define ('FRAME_HEIGTH', 203);
	define ('GRAPH_LINE_WIDTH', 0.15);
	define ('GRAPH_LINE_LENGTH_MAX', 7);

	

/** Class za generacijo pdf-a
 *
 * @desc: po novem je potrebno form elemente generirati ro�no kot slike
 *
 */
class LatexEditsAnalysis {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	var $currentStyle;
	var $db_table = '';
	
	public static $ss = null;		//SurveyStatistic class
	public static $sas = null;		//		$sas = new SurveyAdminSettings();class
	
	protected $texNewLine = '\\\\ ';
	protected $texBigSkip = '\bigskip';
	protected $texSmallSkip = '\smallskip';
	protected $horizontalLineTex = "\\hline ";
	
	//nastavitve za prikazovanje
	protected $seansa = '30';
	protected $times = '1 month';
	protected $status = 0;
	protected $from = '';
	protected $to = '';
	protected $period = 'day';
	protected $user = 'all';
	//nastavitve za prikazovanje - konec
	
	protected $interval;
	protected $data;
	protected $statuses;
	
	protected $sum_data;
	
	protected $texTimeEdits='';
	
	protected $vrsticaTex = array();
	
	public static $sea = null;	//SurveyEditsAnalysis class

	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $ssData = null)
	{
		global $site_path;
		global $global_user_id;
		global $lang;
                
        //error_log(json_encode($_GET));
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->anketa['podstran'] = $podstran;
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
		
		$this->sea=new SurveyEditsAnalysis($this->anketa['id']);
		
		if ($_GET['seansa'] > 0){
		//if (isset ($_GET['seansa'])){
			$seansa = $_GET['seansa'];
		}else{
			$seansa = '30';
		}						
		if (isset ($_GET['time'])){
			$time = $_GET['time'];
		}else{
			$time = '1 month';
		}						
		if (isset ($_GET['status'])){
			$status = $_GET['status'];
		}else{
			$status = 0;
		}						
		if (isset ($_GET['from'])){
			$from = $_GET['from'];
		}else{
			$from = '';
		}						
		if (isset ($_GET['to'])){
			$to = $_GET['to'];
		}else{
			$to = '';
		}
		if (isset ($_GET['user'])){
			$user = $_GET['user'];
		}else{
			$user = 'all';	
		}	
		if (isset ($_GET['period'])){
			$period = $_GET['period'];
		}else{
			$period = 'day';
		}
		
		$this->seansa = $seansa;
		$this->times = $time;
		$this->status = $status;
		$this->from = $from;
		$this->to = $to;
		$this->period = $period;
		$this->user = $user;
		
		// Legenda statusov
		$this->statuses = array(
			-1 => $lang['srv_vsi'],
			0 => $lang['srv_urejanje'],
			1 => $lang['import_data'],
			2 => $lang['export_analisys'],
			3 => $lang['srv_reporti'],
			4 => $lang['srv_podatki'],
			5 => $lang['srv_inv_nav_email'],
			20 => $lang['srv_hierarchy'], // Splošni podatki o hierarhiji
			21 => $lang['srv_hierarchy_structure'], // Grajenje hierarhije
			22 => $lang['srv_hierarchy_users'], // Urejanje uporabnikov
		);
		
		// Legenda seans
		$this->seanse = array(
			5 => $lang['srv_edits_analysis_seansa_5min'],
			10 => $lang['srv_edits_analysis_seansa_10min'],
			30 => $lang['srv_edits_analysis_seansa_30min'], // Grajenje hierarhije
			60 => $lang['srv_edits_analysis_seansa_60min'], // Urejanje uporabnikov
		);
		
		// Legenda casov
		$this->timings = array(
			'lifetime' => $lang['srv_edits_analysis_period_lifetime'],
			'1 hour' => $lang['srv_diagnostics_1 hour'],
			'6 hour' => $lang['srv_diagnostics_6 hour'],
			'12 hour' => $lang['srv_diagnostics_12 hour'],
			'1 day' => $lang['srv_diagnostics_1 day'],
			'2 day' => $lang['srv_diagnostics_2 day'],
			'5 day' => $lang['srv_diagnostics_5 day'],
			'7 day' => $lang['srv_diagnostics_7 day'],
			'14 day' => $lang['srv_diagnostics_14 day'],
			'1 month' => $lang['srv_diagnostics_1 month'],
			'3 month' => $lang['srv_diagnostics_3 month'],
			'6 month' => $lang['srv_diagnostics_6 month'],
			'99date' => $lang['srv_diagnostics_choose_date'],			
		);
		
		// Legenda intervalov
		$this->interval_criteria = array(
			'hour' => $lang['srv_statistic_period_hour_period'],
			'day' => $lang['srv_statistic_period_day_period'],
		);
		

		//print_r($_GET);
		
	   //create iterval - SQL where statement
		$this->interval = $this->sea->createInterval($time, $from, $to);
		
		//get object of all edits data
		$this->data = $this->sea->getData($status, $this->interval);
		
		$sum_data = $this->TimeEdits($this->data['timeEdits'], $this->seansa*60, $status);
		$this->sum_data = $sum_data;
		
		$this->sas = new SurveyAdminSettings(0,$this->anketa['id']);
		//ustvarimo SurveyStatistic objekt in mu napolnimo variable
		$this->ss = new SurveyStatistic();
		$this->ss->Init($this->anketa['id'],true);
		/*
		
		$this->ss->realUsersByStatus_base = $ssData[0];

		$this->ss->type = $ssData[1];
		$this->ss->period = $ssData[2];
	*/	
		/* intervali se več ne pošiljajo preko get, ker se polovijo iz porfila 
		if($ssData[1] != 'undefined')
			$this->ss->startDate = $ssData[1];
		if($ssData[2] != 'undefined')
			$this->ss->endDate = $ssData[2];
		$this->ss->type = $ssData[3];
		$this->ss->period = $ssData[4];
		//$this->ss->isDefaultFilters = false; 
		*/
		
		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
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
	function getFile($fileName='')
	{
		//Close and output PDF document		
		ob_end_clean();
		$this->pdf->Output($fileName, 'I');
	}

	public function displayEditAnalysis() {
		global $lang;		
		
		$texEditAnalysis = '';
		
		// imamo vnose, prikažemo statistiko
		//$this->ss->PrepareDateView();
		//$this->ss->PrepareStatusView();
		
		//naslov izvoza
		//$texEditAnalysis .= $this->returnBold($lang['srv_edits_analysis']).$this->texNewLine.$this->texNewLine;
		$texEditAnalysis .= '\MakeUppercase{\huge \textbf{Status - '.$lang['srv_edits_analysis'].'}}'.$this->texBigSkip.$this->texNewLine;
		
		//$texStatus .= '\begin{tableStatus}';	/*zacetek environmenta z manjsim fontom*/
		$texEditAnalysis .= '\begin{tableStatus}';	/*zacetek environmenta z manjsim fontom*/
		
		//vrstica z nastavitvami #########################
		$texEditAnalysis .= $this->returnBold($lang['srv_edits_analysis_seansa']).''.$this->seanse[$this->seansa].' '.$this->returnBold($lang['status']).''.$this->statuses[$this->status].' '.$this->returnBold($lang['in']).':'.$this->timings[$this->times].''.$this->returnBold($lang['srv_diagnostics_orfrom']).' '.$this->from.' '.$this->returnBold($lang['srv_diagnostics_to']).' '.$this->to.'';
		$texEditAnalysis .= $this->texBigSkip.$this->texNewLine;
		
		
		if(sizeof($this->data) == 0){
            $texEditAnalysis .= $lang['srv_edits_analysis_no_data'];			
		}
        else{		
			//Akcije urejanja	#########################
			$texEditAnalysis .= $this->DisplayCounter();		
			
			//prostor med tabelama
			$texEditAnalysis .= $this->texBigSkip.$this->texBigSkip.$this->texNewLine;			
			
			//Neprekinjeno urejanje	#########################
			$texEditAnalysis .= $this->DisplayContinu();
			
			//prostor med tabelama
			//$texEditAnalysis .= $this->texBigSkip.$this->texBigSkip.$this->texNewLine;
			$texEditAnalysis .= $this->texBigSkip.$this->texBigSkip;
			
			//Podrobnosti urejanja	#########################
			$texEditAnalysis .= $this->DisplayTimeEdits();

		}
		//$texStatus .= '\end{tableStatus}';	/*zakljucek environmenta z manjsim fontom*/
		$texEditAnalysis .= '\end{tableStatus}';	/*zakljucek environmenta z manjsim fontom*/
		//echo $texEditAnalysis;
		return $texEditAnalysis;

	}
	
	/** Funkcija prikaze Akcije urejanja
	 * 
	 */
	function DisplayCounter() {
		global $lang;
		global $site_url;

		$tex = '';
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_edits_analysis_counter']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		if(sizeof($this->data) == 0)
            $tex .= $lang['srv_edits_analysis_no_data'];
        else{

			$sum_data = $this->sum_data;
			
			//izpis stevila urejevalcev
			$tex .= $lang['srv_edits_analysis_counter_editors'].': '.sizeof($sum_data).$this->texBigSkip.$this->texNewLine;

			$sum_akcij = 0;
			$sum_time = 0;
			$sum_seans = 0;

			//Priprava parametrov za tabelo s podatki o anketi
			$steviloStolpcevParameterTabular = 4;
			$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
			$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
			$parameterTabular = '';
			$export_format = 'pdf';			
			
			for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
				//ce je prvi stolpec
				if($i == 0){				
					//$parameterTabular .= ($export_format == 'pdf' ? 'X|' : 'l|');
					$parameterTabular .= 'l|';
				}elseif($i == $steviloOstalihStolpcev){
					//$parameterTabular .= ($export_format == 'pdf' ? 'C' : 'c');
					$parameterTabular .='c';
				}else{
					//$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');
					$parameterTabular .= 'c|';
				}			
			}
			//Priprava parametrov za tabelo s podatki o anketi - konec
			
			//zacetek latex tabele z obrobo	za prvo tabelo	
			$pdfTable = 'tabular';
			$rtfTable = 'tabular';
			$pdfTableWidth = 1;
			$rtfTableWidth = 1;
			
			$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
			
			//Priprava parametrov za tabelo s podatki o anketi - konec

			//Priprava podatkov za izpis vrstic tabele in njihov izpis
			$prvaVrstica = array();
			$prvaVrstica[] = $this->encodeText($lang['srv_edits_analysis_counter_editor']);
			$prvaVrstica[] = $this->encodeText($lang['srv_edits_analysis_time_time']);
			$prvaVrstica[] = $this->encodeText($lang['srv_edits_analysis_num_sessions']);
			$prvaVrstica[] = $this->encodeText($lang['srv_edits_analysis_time_actions']);

			$tex .= $this->tableRow($prvaVrstica);	//Izpis 1. vrstice tabele
			
			
			foreach ($sum_data as $key => $value) {
				$vrsticaPodatki = array();
				$vrsticaPodatki[] = $key;
				$vrsticaPodatki[] = $this->sea->calculateTimeFromSeconds($value['time_sum']);
				$vrsticaPodatki[] = $value['st_seans_sum'];
				$vrsticaPodatki[] = $value['st_akcij_sum'];
				$tex .= $this->tableRow($vrsticaPodatki);	//Izpis vrstic tabele s podatki
				
				$sum_akcij += $value['st_akcij_sum'];
				$sum_time += $value['time_sum'];
				$sum_seans += $value['st_seans_sum'];
			}

			
			// vsota veljavnih
			$vrsticaSum = array();
			$vrsticaSum[] = $this->encodeText($lang['srv_edits_analysis_time_total']);
			$vrsticaSum[] = $this->sea->calculateTimeFromSeconds($sum_time);
			$vrsticaSum[] = $sum_seans;
			$vrsticaSum[] = $sum_akcij;
			$tex .= $this->tableRow($vrsticaSum);	//Izpis vrstice s sumo			
			
			//Priprava podatkov za izpis vrstic tabele in njihov izpis - konec			
		
			//zaljucek latex tabele s podatki
			$tex .= "\\end{".$pdfTable."}";
			//zaljucek latex tabele s podatki - konec
		}
		//izpis tabele v okvir
		//$texText = $this->FrameText($title.$tex);
		$texText = ($title.$tex);
		
		//echo $tex;
		return $texText;
	}

	 /** Funkcija prikaze Neprekinjeno urejanje
	 * 
	 */
	function DisplayContinu() {	
		global $lang;
		
		$tex = '';
		$sum_data = $this->sum_data;
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_edits_analysis_countinu']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		//izpis nastavitev
		if($this->user == 'all'){
			$user = $lang['srv_edits_analysis_counter_all'];
		}else{
			foreach ($sum_data as $email => $row) {
				if($row['user_id'] == $this->user){
					$user = $email;
				}
			}			
		}
		$tex .= $lang['srv_edits_analysis_counter_editors'].': '.$user.'; '.$lang['srv_statistic_period'].': '.$this->interval_criteria[$this->period].$this->texBigSkip.$this->texNewLine;
		//izpis nastavitev - konec
		
		
		$interval_criteria = $this->period;
		$user_criteria = $this->user;
		$continu_data = $this->sea->continuEditsQuery($this->status, $this->interval, $interval_criteria, $user_criteria);
		
		$data = $continu_data;
		
		##################
		$maxValue = 0;
        
        $interval_seconds = ($interval_criteria == 'day') ? 86400 : 3600;
        $interval_crit = ($interval_criteria == 'day') ? '' : ' H';
        
        if ($data) {
            $temp_time = null;
            //units
            $zapored = 0;
            $results = array();
			
            foreach ($data as $row) {
                if($temp_time == null){
					//$format = 'Y-m-d'.$interval_crit;
					$temp_time = DateTime::createFromFormat('Y-m-d'.$interval_crit, $row['formatdate']);
					//$temp_time = DateTime::createFromFormat($format, $row['formatdate']);
					
				}
                else{
                    //calculate seconds between actions (rounded on 3600 or 86400)
                    $interval = $this->sea->calculateTimeBetweenActions($temp_time, DateTime::createFromFormat('Y-m-d'.$interval_crit, $row['formatdate']));
                    
					//if interval between actions are 1 unit (1 hour or 1 day), add it to continued editing session
                    if($interval/$interval_seconds-$zapored < 2){
                        $zapored++;
                        //set maxValue, needed for width of bars
                        $maxValue = max($maxValue, $zapored);
                    }
                    //interval is more than 1 unit apart, not in continued editing session
                    else{
                        //if there is continued editing session until previous action, store it to array - ignore otherwise
                        if($zapored > 0){
							array_push($results, array('time' => $temp_time, 'zapored' => $zapored));
						}
                        //restart all
                        $temp_time = DateTime::createFromFormat('Y-m-d'.$interval_crit, $row['formatdate']);
                        $zapored = 0;
                    }
                }
            }
            //if there is continued editing session in last actions, store it to array - ignore otherwise
            if($zapored > 0){
				//$this->ContinuRow($temp_time, $zapored, $maxValue, $value);
                array_push($results, array('time' => $temp_time, 'zapored' => $zapored));				
			}
            if(!$results){
				//$this->echoNoData();
				$tex .= $this->encodeText($lang['srv_no_data']).$this->texNewLine;
			}
            else{
				//Priprava parametrov za tabelo s podatki o anketi
				$steviloStolpcevParameterTabular = 2;
				$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
				$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
				$parameterTabular = '';
				$export_format = 'pdf';
				//$parameterTabular = '|';
				
				for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
					if($i == 0){
						$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize=.3\hsize}X' : 'l');	//fiksna sirina prvega stolpca, da sprejme datum in uro
					}else{
						$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
					}
				}		
				
				$pdfTable = 'tabularx';
				$rtfTable = 'tabular';
				$pdfTableWidth = 1;
				$rtfTableWidth = 1;
				
				$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);				
				//Priprava parametrov za tabelo s podatki o anketi - konec
				
				//Priprava podatkov za izpis vrstic tabele in izpis vrstic	
                //reduce bars a little
                $maxValue *= GRAPH_REDUCE;//najvecje stevilo
				
                //draw all data and bars				
                foreach ($results as $row) {
                    $text = $this->ContinuRow($row['time'], $row['zapored'], $maxValue, $interval_criteria);
					$tex .= $this->displayLineWithGraph($text, ($row['zapored']+1), $maxValue);
                }				
				//Priprava podatkov za izpis vrstic tabele in izpis vrstic	- konec
				
				//zaljucek latex tabele s podatki
				$tex .= "\\end{".$pdfTable."}";
				//zaljucek latex tabele s podatki - konec
            }
        } else{
			//$this->echoNoData();
			$tex .= $this->encodeText($lang['srv_no_data']).$this->texNewLine;
		}
		#################
		
		//izpis tabele in besedila v okvir
		//$texText = $this->FrameText($title.$tex);
		$texText = ($title.$tex);
		
		return $texText;
	}
	
	    /**
     * Returns time string in exact format
     * @param type $temp_time - the last edit
     * @param type $zapored - hour of continuoed editing
     * @param type $maxValue - max value of bars
     * @param type $interval_criteria - criteria for interval - continued 'day' or 'hour'
     */
    function ContinuRow($temp_time=null, $zapored=null, $maxValue=null, $interval_criteria=null){
		$s_time = '';
		
        $time_last = clone $temp_time;
        //edit DateTime get starting of continued editting session by subtracting units
        $temp_time->modify('- '.$zapored.' '.$interval_criteria);
		
        //if hour criteria
        if($interval_criteria == 'hour'){
            //add 1 hour because of from to view
            $time_last->modify('+ 1 '.$interval_criteria);
            $s_time = $temp_time->format('Y-m-d H:00') .' - '. $time_last->format('H:00');
        }
        elseif($interval_criteria == 'day'){
			$s_time = $temp_time->format('Y-m-d') .' - '. $time_last->format('Y-m-d');
		}

		return $s_time;

    }
	
	/** Funkcija prikaze Podrobnosti urejanja
	 * 
	 */
	function DisplayTimeEdits() {
		global $lang;
		
		$tex = '';
		$sum_data = $this->sum_data;
		
		//naslov okvirja
		$titleText = $this->encodeText($lang['srv_edits_analysis_editing_details']).$this->texNewLine;
		$title = $this->returnBoldAndRed($titleText);
		
		$tex .= $this->texTimeEdits;
		
		
		//izpis tabele in besedila v okvir
		//$texText = $this->FrameText($title.$tex);
		$texText = ($title.$tex);
		
		return $texText;		
	}
	
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text='', $length=64, $tail="..."){
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
	
		#moja funkcija encodeText
	function encodeText($text=''){
		// popravimo sumnike ce je potrebno
		//$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		//$text = str_replace("&scaron;","š",$text);
		//echo "Encoding ".$text."</br>";
		if($text == ''){	//ce ni teksta, vrni se
			return;			
		}
		$textOrig = $text;
		$findme = '<br />';
		$findmeLength = strlen($findme);
		$findImg = '<img';
		$findImgLength = strlen($findImg);
		
		$pos = strpos($text, $findme);
		$posImg = strpos($text, $findImg);
		
		//ureditev izrisa slike
		if($posImg !== false){
			$numOfImgs = substr_count($text, $findImg);	//stevilo '<br />' v tekstu
			$posImg = strpos($text, $findImg);
			$textPrej = '';
			$textPotem = '';				
			for($i=0; $i<$numOfImgs; $i++){					
				$posImg = strpos($text, $findImg);
				$textPrej = substr($text, 0, $posImg);	//tekst do img
				$textPotem = substr($text, $posImg);	//tekst po img, z vkljuceno hmlt kodo z img
				$posImgEnd = strpos($textPotem, '/>');	//pozicija, kjer se konca html koda za img
				$textPotem = substr($textPotem, $posImgEnd+strlen('/>'));	//tekst od konca html kode za img dalje
				
				$text = $textPrej.' '.PIC_SIZE_ANS."{".$this->getImageName($text, 0, '<img')."}".' '.$textPotem;
				//$text2Return = $textPrej.' '.PIC_SIZE_ANS."{".$this->getImageName($text2Return, 0, 'img')."}".' '.$textPotem;				
			}
			
			//pred ureditvijo posebnih karakterjev, odstrani del teksta s kodo za sliko, da se ne pojavijo tezave zaradi imena datoteke od slike
			$findImgCode = '\includegraphics';
			$posOfImgCode = strpos($text, $findImgCode);
			//echo $posOfImgCode."</br>";
			$textToImgCode = substr($text, 0, $posOfImgCode);	//tekst do $findImgCode
			//echo $textToImgCode."</br>";
			$textFromImgCode = substr($text, $posOfImgCode);	//tekst po $findImgCode
			//echo $textFromImgCode."</br>";
			$findImgCodeEnd = '}';
			//$posOfImgCodeEnd = strpos($text,  $findImgCodeEnd);
			$posOfImgCodeEnd = strpos($textFromImgCode, $findImgCodeEnd);
			//echo $posOfImgCodeEnd."</br>";
			$textAfterImgCode = substr($textFromImgCode, $posOfImgCodeEnd+1);	//tekst po $findImgCodeEnd
			//echo $textAfterImgCode."</br>";
			$textOfImgCode = substr($text, $posOfImgCode, $posOfImgCodeEnd+1);
			//echo $textOfImgCode."</br>";
			
			$text = $textToImgCode.$textAfterImgCode;
			
			//pred ureditvijo posebnih karakterjev, odstrani del teksta s kodo za sliko, da se ne pojavijo tezave zaradi imena datoteke od slike - konec
		}
		//ureditev izrisa slike - konec	
		
		//ureditev posebnih karakterjev za Latex	http://www.cespedes.org/blog/85/how-to-escape-latex-special-characters, https://en.wikibooks.org/wiki/LaTeX/Special_Characters#Other_symbols
		$text = str_replace('\\','\textbackslash{} ',$text);
		//$text = str_replace('{','\{',$text);		
		//$text = str_replace('}','\}',$text);	
		$text = str_replace('$','\$ ',$text);
		$text = str_replace('#','\# ',$text);
		$text = str_replace('%','\% ',$text);		
		$text = str_replace('€','\euro',$text);		
		$text = str_replace('^','\textasciicircum{} ',$text);		
		//$text = str_replace('_','\_ ',$text);	
		$text = str_replace('_','\_',$text);	
		$text = str_replace('~','\textasciitilde{} ',$text);		
		$text = str_replace('&amp;','\&',$text);
		$text = str_replace('&','\&',$text);
		//$text = str_replace('&lt;','\textless ',$text);
		$text = str_replace('&lt;','\textless',$text);
		//$text = str_replace('&gt;','\textgreater ',$text);
		$text = str_replace('&gt;','\textgreater',$text);
		$text = str_replace('&nbsp;',' ',$text);
		//ureditev posebnih karakterjev za Latex - konec
		
		//po ureditvi posebnih karakterjev, dodati del teksta s kodo za sliko, ce je slika prisotna
		if($posImg !== false){
			$text = substr_replace($text, $textOfImgCode, $posOfImgCode, 0);
		}
		//po ureditvi posebnih karakterjev, dodati del teksta s kodo za sliko, ce je slika prisotna		

 		if($pos === false && $posImg === false) {	//v tekstu ni br in img
			//return $text;
/* 			echo "encode pred strip: ".$text."</br>";
			echo "encode po strip: ".strip_tags($text)."</br>";			
			return strip_tags($text); */
		}else {	//v tekstu sta prisotna br ali img
			$text2Return = '';	//tekst ki bo vrnjen
			
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico
			if($pos !== false){
				$pos = strpos($text, $findme);
				$numOfBr = substr_count($text, $findme);	//stevilo '<br />' v tekstu
				for($i=0; $i<$numOfBr; $i++){
					if($i == 0){	//ce je prvi najdeni '<br />'
						$textPrej = substr($text, 0, $pos);
						$textPotem = substr($text, $pos+$findmeLength);
						if($i == $numOfBr-1){
							$text2Return .= $textPrej.' \break '.$textPotem;
						}else{
							$text2Return .= $textPrej.' \break ';
						}
					}else{	//drugace
						$pos = strpos($textPotem, $findme);
						$textPrej = substr($textPotem, 0, $pos);
						$textPotem = substr($textPotem, $pos+$findmeLength);
						if($i == $numOfBr-1){
							$text2Return .= $textPrej.' \break '.$textPotem;
						}else{
							$text2Return .= $textPrej.' \break ';
						}
					}
				}
				$text = $text2Return;
			}			
			//ureditev preureditev html kode za novo vrstico v latex, ureditev prenosa v novo vrstico - konec
/* 			echo "encode pred strip: ".$text."</br>";
			echo "encode po strip: ".strip_tags($text)."</br>";
			return strip_tags($text);	//vrni tekst brez html tag-ov */
		}
		
		//preveri, ce je url v besedilu (http:// ... ) in uredi Latex izpis le-tega
		$findHttp = 'http://';
		$findHttps = 'https://';
		$posHttp = strpos($text, $findHttp);
		$posHttps = strpos($text, $findHttps);
		
 		if($posHttp !== false || $posHttps !== false) {	//v imamo URL naslov
			$space = ' ';
			if($posHttp !== false){
				$text = substr_replace($text, $space, ($posHttp+7), 0);
			}elseif($posHttps !== false){
				$text = substr_replace($text, $space, ($posHttps+8), 0);
			}
		}
		//preveri, ce je url v besedilu (http:// ... ) in uredi Latex izpis le-tega - konec

		return strip_tags($text); //vrni tekst brez html tag-ov
	}
	
	function returnBold($text=''){
		$boldedText = '';
		$boldedText .= '\textbf{'.$text.'}';
		return $boldedText;
	}
	
	function returnBoldAndRed($text=''){
		//$this->naslovnicaUkaz .= ' {\\textcolor{red}{'.$lang['srv_survey_non_active1'].'}} \\\\';
		$tex = '';
		$tex .= ' {\\textcolor{red}{'.$text.'}} ';
		return $tex;
	}
	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele ##################################################################################
	#argumenti 1. export_format, 2. parametri tabele, 3. tip tabele za pdf, 4. tip tabele za rtf, 5. sirina pdf tabele (delez sirine strani), 6. sirina rtf tabele (delez sirine strani)
	function StartLatexTable($export_format='', $parameterTabular='', $pdfTable='', $rtfTable='', $pdfTableWidth='', $rtfTableWidth=''){
		$tex = '';
		//$tex .= '\keepXColumns';
 		if($export_format == 'pdf'){
			$tex .= '\begin{'.$pdfTable.'}';
			if($pdfTable=='tabularx'){
				//$tex .= '{'.$pdfTableWidth.'\textwidth}';
				$tex .= '{\hsize}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}elseif($export_format == 'rtf'){
			$tex .= '\begin{'.$rtfTable.'}';
			if($rtfTable=='tabular*'){
				$tex .= '{'.$pdfTableWidth.'\textwidth}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}	
		return $tex;
	}	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele - konec ##########################################################################
	
	//omogoca izpis okvirja z dolocene sirine in visine s tekstom dolocene sirine
	function FrameText($text=''){
		$framedText = '';		
		//$framedText .= '\framebox('.FRAME_WIDTH.','.FRAME_HEIGTH.')[t]{ \parbox[t]{'.FRAME_TEXT_WIDTH.'\textwidth}{'.$this->texSmallSkip.$text.'} }';
		$framedText .= '\framebox('.FRAME_WIDTH.','.FRAME_HEIGTH.')[t]{ \parbox[t]{'.FRAME_WIDTH.'pt}{'.$this->texSmallSkip.$text.'} }';
		return $framedText;		
	}
	
	//function tableRow($arrayText, $brezHline=0, $brezNoveVrstice=0, $nadaljevanjeVrstice=0, $steviloPodstolpcev){
	function tableRow($arrayText=null, $brezHline=0, $brezNoveVrstice=0, $nadaljevanjeVrstice=0, $color='', $export_format = null, $steviloPodstolpcev = null){
		$tableRow = '';
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = 1; //$height = $this->getCellHeight($this->encodeText($arrayText[1]), 90);

		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
				//echo "velikost polja s tekstom: ".count($arrayText)."</br>";
		
		if($export_format == 'pdf'){			
			if($color=='blue'){
				$cellBgColor = 'cyan';		
			}elseif($color=='red'){
				$cellBgColor = 'pink';
			}
			$cellColoring = ' \cellcolor{'.$cellBgColor.'} ';
		}else{
			$cellColoring = '';
		}
		
		for($i=0;$i<count($arrayText);$i++){
			//echo "array text: ".$arrayText[$i]."</br>";
			if($color!=''){	//ce je potrebno besedilo dolocene barve
				//$text = ' \cellcolor{'.$cellBgColor.'} '.$this->coloredTextLatex($color, $arrayText[$i]);
				$text = $cellColoring.''.$this->coloredTextLatex($color, $arrayText[$i]);
			}else{
				$text = $arrayText[$i];
			}
			if($i==0&&!$nadaljevanjeVrstice&&!count($steviloPodstolpcev)){
				$tableRow .= $text;
			}
			elseif($i==0&&!$nadaljevanjeVrstice&&count($steviloPodstolpcev)){
				$tableRow .= ' \multicolumn{'.$steviloPodstolpcev[$i].'}{c|}{ '.$text.' }';
			}elseif(count($steviloPodstolpcev)){	//ce rabimo multicolumn
				$tableRow .= ' & \multicolumn{'.$steviloPodstolpcev[$i].'}{c|}{ '.$text.' }';
			}
			else{
				$tableRow .= ' & '.$text;
			}
		}
		
		if(!$brezNoveVrstice){
			$tableRow .= $this->texNewLine;	/*nova vrstica*/
		}

		if (!$brezHline) {	//dodaj se horizontal line, ce je to potrebno (po navadi vse povsod razen npr. za tabelo s st. odklonom in povprecjem)
			if($export_format != 'xls'){
				$tableRow .= $this->horizontalLineTex; /*obroba*/
			}
		}
		
		//echo "Vrstica tabele: ".$tableRow."</br>";
		
		return $tableRow;
	}
	
	//funkcija, ki skrbi za izris grafa ustrezne dolzine
	function drawGraphLatex($graphLineLength=null, $value=null){
		$texGraph = '';
		$texGraph .= '\begin{tikzpicture} \fill[crtaGraf] (0,0) -- ('.$graphLineLength.',0) -- ('.$graphLineLength.','.GRAPH_LINE_WIDTH.') -- (0,'.GRAPH_LINE_WIDTH.') -- (0,0); \end{tikzpicture} '.$value;
		return $texGraph;		
	}
	
	function displayLineWithGraph($text='', $value=null, $maxValue=null){
		$texStatusLine = '';
		$vrsticaPodatki = array();
		$vrsticaPodatki[] = $text;
		if($value){	//ce vrednost ni nula
			$graphLineLength = (GRAPH_LINE_LENGTH_MAX/$maxValue)*$value;
			$vrsticaPodatki[] = $this->drawGraphLatex($graphLineLength, $value);
		}else{
			$vrsticaPodatki[] = 0;
		}					
		$texStatusLine .= $this->tableRow($vrsticaPodatki,1);		
		
		return $texStatusLine;
	}
	
	
	function TimeEdits($data=null, $seansa=null, $status=null){
        global $lang;
		$tex = '';
        $sum_data = array();
        
        $datetime_last = null;
        $datetime_start = null;
        $st_akcij = 0;
        $st_akcij_sum = 0;
        $st_seans_sum = 0;
        $time_sum = 0;
        $user_temp = null;
        $user_id = 0;
        $row_id = 0;
        $action_type = null;
        $action_type_sum = null;
        $statuses = null;
        
        if($status == -1){
            $statuses = array(
                0 => array("name"=>$lang['srv_urejanje'], "sum"=>0),
                1 => array("name"=>$lang['import_data'], "sum"=>0),
                2 => array("name"=>$lang['export_analisys'], "sum"=>0),
                3 => array("name"=>$lang['srv_reporti'], "sum"=>0),
                4 => array("name"=>$lang['srv_podatki'], "sum"=>0),
                5 => array("name"=>$lang['srv_inv_nav_email'], "sum"=>0),
                //20 => array("name"=>$lang['srv_hierarchy'],  "sum"=>0),// Splošni podatki o hierarhiji
                //21 => array("name"=>$lang['srv_hierarchy_structure'],  "sum"=>0),// Grajenje hierarhije
                //22 => array("name"=>$lang['srv_hierarchy_users'],  "sum"=>0),// Urejanje uporabnikov
            );
            $action_type = $statuses;
            $action_type_sum = $statuses;
        }
        else if($status == 0){
            $statuses = array();
            $action_type = array();
            $action_type_sum = array();
        }
        
        //echo '<h2>'.$lang["srv_edits_analysis_editing_details"].'</h2>';
        
        foreach ($data as $rowGrupa) {
            //$post = $this->convertToJSON($rowGrupa['post']);
            $akcija = null;
            if($status == -1)
                $akcija = $rowGrupa['status'];
            else if($status == 0){
                $get = $this->convertToJSON($rowGrupa['get']);
                $akcija = $get['a'];
            }
            
            //zacetek risanja
            if(!isset($user_temp)){
                $user_temp = $rowGrupa['email'];
                $user_id = $rowGrupa['id'];
				//izpis zacetka tabele za prvega urejevalca (prvi dve vrstici z naslovi)
				$tex .= $this->zacetekTabelePodrobnostiLatex($user_temp, $status, $user_id);
				//izpis zacetka tabele za prvega urejevalca (prvi dve vrstici z naslovi) - konec
				
            }           
            //naslednji editor
            else if($user_temp != $rowGrupa['email']){            
				
				//Priprava podatkov za izpis vrstic tabele in izpis vrstic /////////////////////////////////////////////////////
				
				$time_sum += $this -> TimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);
				
				//Izpis posameznih vrstic
				foreach($this->vrsticaTex as $vrstica){
					$tex .= $vrstica;
				}
				//Izpis posameznih vrstic - konec
				
				//izrisi se zadnjo vrstico prejsnjega urejevalca
				//izpis vrstice podatkov za Skupaj
				$tex .= $this -> echoTimeEditsFootRow($time_sum, $st_akcij_sum, $action_type_sum, $user_id.'_sum');
				//izpis vrstice podatkov za Skupaj - konec
				//izrisi se zadnjo vrstico prejsnjega urejevalca - konec
				
				//Priprava podatkov za izpis vrstic tabele in izpis vrstic	- konec /////////////////////////////////////////////

				//zaljucek latex tabele s podatki za prvega urejevalca
				$tex .= $this->konecTabelePodrobnostiLatex();
				//zaljucek latex tabele s podatki za prvega urejevalca - konec
				
				//prostor po tabeli
				$tex .= $this->texBigSkip;
				$tex .=$this->texNewLine;
				//prostor po tabeli - konec
				
                $sum_data[$user_temp]['time_sum']=$time_sum;
                $sum_data[$user_temp]['st_akcij_sum']=$st_akcij_sum;
                $sum_data[$user_temp]['st_seans_sum']=$st_seans_sum;
                $sum_data[$user_temp]['user_id']=$user_id;
                $action_type_sum = $statuses;
           
                //nova tabela - nov urejevalec
                $user_temp = $rowGrupa['email'];
                $user_id = $rowGrupa['id'];
				$this->vrsticaTex = array();	//resetiranje polja s kodo vrstic ostalih urejevalcev
				//izpis zacetka tabele za naslednje urejevalce (prvi dve vrstici z naslovi)
				$tex .= $this->zacetekTabelePodrobnostiLatex($user_temp, $status, $user_id);
				//izpis zacetka tabele za naslednje urejevalce (prvi dve vrstici z naslovi) - konec
                
                //ponastavi spremenljivke
                $datetime_last = null;
                $datetime_start = null;
                $st_akcij = 0;
                $st_akcij_sum = 0;
                $st_seans_sum = 0;
                $time_sum = 0;
            }    
            
            //izpis vrstic
            //nov start seanse
            if(!isset($datetime_start)){
                $datetime_start = new DateTime($rowGrupa['datetime']);
                $st_akcij++;
                $st_seans_sum++;
                $action_type = $statuses;
            }
            //se ni druge akcije
            else if(!isset($datetime_last)){
                $temp_time = new DateTime($rowGrupa['datetime']);
                $interval = $this->sea->calculateTimeBetweenActions($datetime_start, $temp_time);

                //ce je akcija od starta v kriteriju seanse, jo dodaj k seansi
                if($interval <= $seansa){
                    $datetime_last = clone $temp_time;
                    $st_akcij++;
                }
                //akcija je izven kriterija seanse, izpisi samo to akcijo
                else{
                    $datetime_last = clone $datetime_start;
                    $datetime_last->add(new DateInterval('PT5S'));
                    $time_sum += $this -> TimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);				
                    $st_akcij = 1;
                    $st_seans_sum++;
                    $datetime_start = clone $temp_time;
                    $datetime_last = null;
                    $action_type = $statuses;
                }
            }
            //seasna ze ima vsaj dve akciji
            else{
                $temp_time = new DateTime($rowGrupa['datetime']);
                $interval = $this->sea->calculateTimeBetweenActions($datetime_last, $temp_time);
                
                //ce je akcija od prejsnje v kriteriju seanse, jo dodaj k seansi
                if($interval <= $seansa){
                    $datetime_last = clone $temp_time;
                    $st_akcij++;
                }
                //akcija je izven kriterija seanse, izpisi vse prejsnje akcije
                else{
                    $time_sum += $this -> TimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);                    
					$st_akcij = 1;
                    $st_seans_sum++;
                    $datetime_start = clone $temp_time;
                    $datetime_last = null;
                    $action_type = $statuses;
                }
            }
            $st_akcij_sum++;
            $row_id++;
            if($status == -1){
                $action_type[$akcija]['sum'] ++;
                $action_type_sum[$akcija]['sum'] ++;
            }
            else if($status == 0){
                $action_type[$akcija] = isset($action_type[$akcija]) ? $action_type[$akcija]+1 : 1;
                $action_type_sum[$akcija] = isset($action_type_sum[$akcija]) ? $action_type_sum[$akcija]+1 : 1;
            }
        }
        
        //izrisi se zadnjo vrstico, ki jo ni foreach ter footer
        if($datetime_last == null){
            $datetime_last = clone $datetime_start;
            $datetime_last->add(new DateInterval('PT5S'));
        }

        $time_sum += $this -> TimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);

		//Izpis posameznih vrstic
		foreach($this->vrsticaTex as $vrstica){
			$tex .= $vrstica;
		}
		//Izpis posameznih vrstic - konec

		$tex .= $this -> echoTimeEditsFootRow($time_sum, $st_akcij_sum, $action_type_sum, $user_id.'_sum');
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic	- konec

		//zaljucek latex tabele s podatki
		$tex .= $this->konecTabelePodrobnostiLatex();
		//zaljucek latex tabele s podatki - konec
		
		
		$this->texTimeEdits = $tex;
		//echo "tex: ".$tex."</br>";
		
        $sum_data[$user_temp]['time_sum']=$time_sum;
        $sum_data[$user_temp]['st_akcij_sum']=$st_akcij_sum;
        $sum_data[$user_temp]['st_seans_sum']=$st_seans_sum;
        $sum_data[$user_temp]['user_id']=$user_id;
		
/* 		echo "time_sum: ".$time_sum."</br>";
		echo "st_akcij_sum: ".$st_akcij_sum."</br>";
		echo "st_seans_sum: ".$st_seans_sum."</br>";
		echo "user_id: ".$user_id."</br>";
		echo "action_type_sum: ".$action_type_sum."</br>"; */
        
        //echo '</table>';
        
        return $sum_data;
    }
	
	 /**
     * Nastavi in izrise vrstico urejanja
     * 
     * @param type $datetime_start - datetime start of editing
     * @param type $datetime_last - datetime end of editing
     * @param type $st_akcij - num ob actions during editing
     * @param type $action_type - string of type of action
     * @param type $row_id - int sequence nuber of row (unique, for this site, no need to be ID)
     * @return type int - calculated second of editing session
     */
    function TimeEditsRow($datetime_start=null, $datetime_last=null, $st_akcij=null, $action_type = null, $row_id = null){	
 		$seconds = 0;
        $tex = '';
        //create string of actions type
        $action_type_string = ($action_type != null) ? $this -> createActionsTypeString($action_type, $row_id) : null;
        if(isset($datetime_last)){
            $seconds = $this->sea->calculateTimeBetweenActions($datetime_start, $datetime_last);
            $this->vrsticaTex[] = $this -> echoTimeEditsRow($datetime_last->format('Y-m-d H:i:s') .' - '. $datetime_start->format('Y-m-d H:i:s'), $this->sea->calculateTimeFromSeconds($seconds), $st_akcij, $action_type_string);
        }
        //ce je samo ena akcija
        else{
			$this->vrsticaTex[] = $this -> echoTimeEditsRow($datetime_start->format('Y-m-d H:i:s'), 0 ,1, $action_type_string);
		}

		return $seconds;
    }
	
	/**
     * Convert false JSON (with keys without quotes and no stat and end braces) 
     * from DB to valid JSON
     * @param type $toJSON string to convert to JSON (with keys without 
     * quotes and no stat and end braces)
     * @return type valid converted JSON
     */
    function convertToJSON($toJSON=null){
        $toJSON = preg_replace('/("(.*?)"|(\w+))(\s*:\s*(".*?"|.))/s', '"$2$3"$4', $toJSON);
        $toJSON = '{'.$toJSON.'}';
        return json_decode($toJSON, true);
    }
	
	    /**
     * Izrise vrstico urejanja
     * @param type $datetime - string from to editing
     * @param type $cas_seanse - editing time
     * @param type $st_akcij - num of editing actions
     * @param type $action_type - string of type of action
     */
    function echoTimeEditsRow($datetime=null, $cas_seanse=null, $st_akcij=null, $action_type = null){
        $tex = '';

		$latexVrstica = array();

		//casovni razpon urejanja
		$latexVrstica[] = $this->encodeText($datetime);

        //cas urejanja
		$latexVrstica[] = $this->encodeText($cas_seanse);

        //stevilo akcij
		$latexVrstica[] = $this->encodeText($st_akcij);
       
	   if($action_type != null){
			//vrsta akcij
			$latexVrstica[] = $this->encodeText($action_type.' ');			
		}
		$tex .= $this->tableRow($latexVrstica);
		
		//echo $tex;
		return $tex;
    }
	
	    /**
     * Create/convert array of action types to string for table cell
     * @param type $action_type - array of action types
     * @param type $row_id - int sequence nuber of row (unique user int and row in table)
     * @return string - converter array to string to put it in table cell
     */
    function createActionsTypeString($action_type=null, $row_id=null){
        $action_type_string = '';
        //urejanje - ali drug specificen status
        if(!isset($action_type[0]['sum'])){
            global $lang;
            $i = 0;
            foreach ($action_type as $key => $at){
/*                 if($i == 3)
                    $action_type_string .= '<div class="srv_edits_analysis_'.$row_id.' as_link" onclick="$(\'.srv_edits_analysis_'.$row_id.'\').toggle();">'.$lang['srv_more'].'</div>';	
                if($i < 3)
                    $action_type_string .= '<div>'.$key.' ('.$at.')'.'</div>';
                else
                    $action_type_string .= '<div class="srv_edits_analysis_'.$row_id.' displayNone">'.$key.' ('.$at.')'.'</div>';
                $i++; */
				$action_type_string .= $key.' ('.$at.')'.'; ';
            }
/*             if($i > 3)
                $action_type_string .= '<div class="srv_edits_analysis_'.$row_id.' as_link displayNone" onclick="$(\'.srv_edits_analysis_'.$row_id.'\').toggle();">'.$lang['srv_less'].'</div>'; */	
        }
        //vsi statusi
        else{
            foreach ($action_type as $at){
                if($at['sum'] > 0){
                    if($action_type_string != '')
                        $action_type_string .= ' ';
                    $action_type_string .= $at['name'].' ('.$at['sum'].')';
                }
            }
        }
		
        return $action_type_string;
    }
	
	 /**
     * Izrise total/footer vrstico urejanja
     * @param type $time - seconds of editing
     * @param type $st_akcij - num of editing actions
     * @param type $action_type - string of type of actions
     * @param type $row_id - int sequence nuber of user (unique, for this site, no need to be ID)
     */
    function echoTimeEditsFootRow($time=null, $st_akcij=null, $action_type = null, $row_id = 0){
        global $lang;
        $tex = '';
		$vrsticaPodatkovSkupaj = array();
        //casovni razpon urejanja
        $vrsticaPodatkovSkupaj[] = $this->returnBold($this->encodeText($lang['srv_edits_analysis_time_total']));
        
		//cas urejanja
		$vrsticaPodatkovSkupaj[] = $this->returnBold($this->sea->calculateTimeFromSeconds($time));
        
        //stevilo akcij
       $vrsticaPodatkovSkupaj[] = $this->returnBold($st_akcij);
	   
        if($action_type != null){
			//vrsta akcij
			$vrsticaPodatkovSkupaj[] = $this->returnBold($this->encodeText($this->createActionsTypeString($action_type, $row_id)));
		}
		
		$tex .= $this->tableRow($vrsticaPodatkovSkupaj);	//Izpis vrstic tabele s podatki o sumi
		return $tex;
    }
	
	function zacetekTabelePodrobnostiLatex($user_temp=null, $status=null, $user_id=null){
		global $lang;
		$tex = '';
		//Priprava parametrov za tabelo s podatki o anketi
		$steviloStolpcevParameterTabular = 4;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;		
		$parameterTabular = '';
		$export_format = 'pdf';
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			/* if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize=.3\hsize}X' : 'l');	//fiksna sirina prvega stolpca, da sprejme datum in uro
			}else{
				$parameterTabular .= ($export_format == 'pdf' ? 'X' : 'l');
			} */
			$parameterTabular .= ($export_format == 'pdf' ? 'C|' : 'c|');
		}		
		
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$tex .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth);				
		//Priprava parametrov za tabelo s podatki o anketi - konec
		
		//Priprava podatkov za izpis vrstic tabele in izpis vrstic
		if($export_format != 'xls'){
			$tex .= $this->horizontalLineTex;
		}
		
		
		$prvaVrstica = array();
		$prvaVrstica[] = ' \multicolumn{'.$steviloStolpcevParameterTabular.'}{|c|}{ '.$user_temp.' }';		
		$tex .= $this->tableRow($prvaVrstica);
		
		$drugaVrstica = array();
		$drugaVrstica[] = $this->encodeText($lang['srv_edits_analysis_time_span']);
		$drugaVrstica[] = $this->encodeText($lang['srv_edits_analysis_time_time']);
		$drugaVrstica[] = $this->encodeText($lang['srv_edits_analysis_time_actions']);
		if($status < 1){
			$drugaVrstica[] = $this->encodeText($lang['srv_edits_analysis_action_type']);
		}else{
			$drugaVrstica[] = '';
		}				
		$tex .= $this->tableRow($drugaVrstica);
		return $tex;
	}
	
	function konecTabelePodrobnostiLatex(){
		$tex = "\\end{tabularx}";
		return $tex;
	}
}

?>