<?php

/**
 *
 *	Class ki skrbi za izris posamezne tabele analiz
 *
 *
 */


include('../../vendor/autoload.php');
define("MAX_STRING_LENGTH", 20);

 
class LatexAnalysisElement{
	
	var $anketa;				// ID ankete
	var $spremenljivka;			// ID spremenljivke za katero izrisujemo frekvence
	protected $spid;
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $CID = null;							# class za inkrementalno dodajanje fajlov
	
	var $current_loop = 'undefined';
	
	protected $texNewLine = '\\\\ ';
	protected $horizontalLineTex = ' \hline ';
	protected $numbering = 0; 		// ostevillcevanje vprasanj
	protected $skin;	
	protected $frontpage;
	protected $showLegenda;
	protected $hideEmpty;
	protected $hideAllSystem;
	
	protected $export_subtype;
	protected $export_format;
	
	public $crosstabClass = null;		//crosstab class	
	protected $crossData1;
	protected $crossData2;
	
	public $multiCrosstabClass = null;		// crosstab class
	
	protected $meansClass;
	
	public $breakClass = null;			// break class	
	protected $spr = 0;			// spremenljivka za katero delamo razbitje
	protected $seq;				// sekvenca	
	protected $break_percent;		// opcija za odstotke	
	public $break_charts = 0;	// ali prikazujemo graf ali tabelo za razbitje
	
	protected $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	protected $user_id;
	protected $from;	
	protected $exportClass;		// instanca razreda v katerem izrisujemo PDF
	protected $analizaClass;	// instanca razreda kjer imamo analize (crosstab, means, ttest...)
	
	protected $path2Images;
	protected $path2Charts;
	
	
	//function __construct($anketa, $spremenljivka){	//$anketa, $export_format, $fillablePdf, $usr_id
	//function __construct($anketa, $export_format, $fillablePdf, $usr_id, $export_subtype){
	//function __construct($anketa, $spremenljivka=0, $export_format, $fillablePdf, $spid=0, $headFileName, $export_subtype, $crossData1=0, $crossData2=0){	//$anketa, $export_format, $fillablePdf, $usr_id
	function __construct($anketa=null, $spremenljivka=0, $export_format='', $fillablePdf = 0, $spid=0, $headFileName=null, $export_subtype=''){
		global $site_path, $global_user_id, $admin_type, $lang;
				
		$this->anketa = $anketa;	
		$this->spremenljivka = $spremenljivka;
		$this->spid = $spid;
		$this->headFileName = $headFileName;
		$this->export_subtype = $export_subtype;
		$this->export_format = $export_format;
		
		
		//Za pobiranje nastavitev
		SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);
		
		//Nastavitve - Splosni prikazi analiz
		# ali izrisujemo legendo
		$this->showLegenda = (SurveyDataSettingProfiles :: getSetting('analiza_legenda') == true) ? true : false;
		$this->hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
		//echo "this->hideEmpty: ".$this->hideEmpty."</br>";
		$this->hideAllSystem = SurveyDataSettingProfiles :: getSetting('hideAllSystem');
		//echo "hideAllSystem: ".$this->hideAllSystem."</br>";

		//Nastavitve - Splosni prikazi analiz - konec
		
		//Nastavitve grafov		
		$this->skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');
		$this->numbering = SurveyDataSettingProfiles :: getSetting('chartNumbering');
		$this->frontpage = SurveyDataSettingProfiles :: getSetting('chartFP');		
		//echo "Numbering: ".$this->numbering."</br>";
		//Nastavitve grafov - konec
		
		//echo $this->spremenljivka['tip']."</br>";
		//echo "Spid v construct: ".$spid."</br>";
		//echo "Spid v construct: ".$this->spid."</br>";
		//echo "head file name: ".$this->headFileName."</br>";
		
		//echo 'To je construct tip analysis </br>';
		
	}
	
	
	
	public function displayTablesLatex($spid=0, $export_format=''){		
		global $site_path;
		global $lang;
		global $global_user_id;
		
		
		//echo 'funkcija displayTableLatex</br>';
		//echo "Tip: ".$this->spremenljivka['tip']."</br>";
		//echo "Export subtype: ".$this->export_subtype."</br>";

        //TODO: Omenjene funkcije se ne potrebujejo
		//# polovimo frekvence
		//SurveyAnalysis::getFrequencys();
		//
		//#odstranimo sistemske variable
		//SurveyAnalysis::removeSystemVariables();
        //
		//$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		
		$tabela = '';

		switch ( $this->export_subtype ){
			case 'sums':
				$sums = new AnalizaSums($this->anketa);
				$tabela .= $sums->displayTableLatex($this->headFileName, $this->spremenljivka, $spid, $export_format, $this->hideEmpty);
			break;
			case 'freq':
				$freq = new AnalizaFreq($this->anketa);
				$tabela .= $freq->displayTableLatex($this->headFileName, $this->spremenljivka, $spid, $export_format, $this->hideEmpty);
			break;
			case 'desc':
				$desc = new AnalizaDesc($this->anketa);
				//$tabela .= $desc->displayTableLatex($this->headFileName, $this->spremenljivka, $spid, $export_format, $this->hideEmpty);
				$tabela .= $desc->displayTableLatex($this->headFileName, $this->spremenljivka, $export_format, $this->hideEmpty);
			break;
		}

		return $tabela;
	}
	
	public function displayCrosstabsTablesLatex($crossData1=null, $crossData2=null){		
		global $site_path;
		global $lang;
		global $global_user_id;
		$tabela = '';

		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData('crosstab_charts');
		
		//ustvarimo crosstab objekt in mu napolnimo variable (var1, var2, checkboxi)		
		$this->crosstabClass = new SurveyCrosstabs();
		$this->crosstabClass->Init($this->anketa['id']);

		for($i=0; $i<sizeof($crossData1)/3; $i++){
			$index = $i * 3;
			$this->crossData1[$i] = array($crossData1[$index],$crossData1[$index+1],$crossData1[$index+2]);
		}
		for($i=0; $i<sizeof($crossData2)/3; $i++){
			$index = $i * 3;
			$this->crossData2[$i] = array($crossData2[$index],$crossData2[$index+1],$crossData2[$index+2]);
		}		
		
		switch ( $this->export_subtype ){
			case 'crosstab':
				$this->crosstabClass->_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
				//echo "stevilo crosstab loopov: ".count($this->crosstabClass->_LOOPS)."</br>";
				if (count($this->crosstabClass->_LOOPS) > 0) {
					# če mamo zanke
					foreach ( $this->crosstabClass->_LOOPS AS $loop) {
						$this->crosstabClass->_CURRENT_LOOP = $loop;
						$tabela .= $this->displayCrosstabsTable();
					}
				} else {
				
					// loopamo cez vse izbrane variable in izrisemo vse tabele
					$addPage = false;
					$this->counter = 0;
					for($j=0; $j<sizeof($this->crossData2); $j++){
						for($i=0; $i<sizeof($this->crossData1); $i++){

							if($addPage)
								$this->pdf->AddPage();
							else
								$addPage = true;
							
							/*$this->pdf->ln(5);*/
							$this->crosstabClass->setVariables($this->crossData2[$j][0],$this->crossData2[$j][1],$this->crossData2[$j][2],$this->crossData1[$i][0],$this->crossData1[$i][1],$this->crossData1[$i][2]);
							$tabela .= $this->displayCrosstabsTable($this->counter);
							
							$this->counter++;
						}
					}		
				}
				
				//$tabela .= $crosstab->displayTableLatex($this->headFileName, $this->spremenljivka, $spid, $export_format, $this->hideEmpty);
				//$tabela .= 'To je podtip crosstab';
			break;
		}
		//echo "tabela: ".$tabela."</br>";
		return $tabela;
	}
	
	
	public function displayMultiCrosstabsTablesLatex(){
		global $site_path;
		global $lang;
		global $global_user_id;
		$tabela = '';
		
		//ustvarimo multicrosstabs objekt		
		$this->multiCrosstabClass = new SurveyMultiCrosstabs($this->anketa['id']);
		
		if (class_exists('AnalizaMultiCrosstab')) {
			$multiCrossTabs = new AnalizaMultiCrosstab($this->anketa);
		}
		
		// Napolnimo variable s katerimi lahko operiramo
		$this->multiCrosstabClass->getVariableList();
		
		// Izris tabele
		$tabela .= $multiCrossTabs->displayTable($this->multiCrosstabClass, $this->export_format);
		
		// Izris legende
		$tabela .= $multiCrossTabs->displayLegend($this->export_format);		
		
		return $tabela;
	}
	
	public function displayBreakTablesLatex(){
		global $site_path;
		global $lang;
		global $global_user_id;
		$tabela = '';
		
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData();
		
		// ustvarimo break objekt
		$this->breakClass = new SurveyBreak($this->anketa['id']);
		$this->spr = $this->sessionData['break']['spr'];
		// poiščemo sekvenco
		$this->seq = $this->sessionData['break']['seq'];
		
		$this->break_percent = (isset($this->sessionData['break']['break_percent']) && $this->sessionData['break']['break_percent'] == false) ? false : true;
		
		// ali prikazujemo tabele ali grafe
		$this->break_charts = (isset($this->sessionData['break']['break_show_charts']) && (int)$this->sessionData['break']['break_show_charts'] == 1) ? 1 : 0;
		
		if ($this->spr != 0){
			
			if (class_exists('AnalizaBreak')) {
				$break = new AnalizaBreak($this->anketa);
			}
			
			// poiščemo pripadajoče variable
			$_spr_data = $this->breakClass->_HEADERS[$this->spr];
			
			// poiščemo opcije
			$options = $_spr_data['options'];
			
			// za vsako opcijo posebej izračunamo povprečja za vse spremenljivke
			$frequencys = null;
			if (count($options) > 0) {
				foreach ($options as $okey => $option) {
					
					// zloopamo skozi variable
					$okeyfrequencys = $this->breakClass->getAllFrequencys($okey, $this->seq, $this->spr);
					if ($okeyfrequencys != null) {
						if ($frequencys == null) {
							$frequencys = array();
						}
						$frequencys[$okey] = $okeyfrequencys;
					} 
				}
			}
			$tabela .= $break->displayBreak($this->spr, $this->seq, $frequencys, $this->breakClass, $this->break_charts, $this->export_format);
		} else {
			//$this->pdf->MultiCell(150, 5, $lang['srv_break_error_note_1'], 0, 'L', 0, 1, 0 ,0, true);
			$tabela .= $lang['srv_break_error_note_1'];
		}
		
		//echo "TABELA: ".$tabela."</br>";
		return $tabela;
	}
	
	public function displayMeanTablesLatex(){		
		global $site_path;
		global $lang;
		global $global_user_id;
		$tabela = '';
		
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData();					
		// ustvarimo means objekt
		$this->meansClass = new SurveyMeans($this->anketa['id']);
		
		$meanData1 = $this->sessionData['means']['means_variables']['variabla1'];
		$meanData2 = $this->sessionData['means']['means_variables']['variabla2'];
		
		$means = array();
		
		if (meanData1 !== null && $meanData2 !== null) {
			$variables1 = $meanData2;
			$variables2 = $meanData1;
			$c1=0;
			$c2=0;
			
			if (class_exists('AnalizaMean')) {
				$mean = new AnalizaMean($this->anketa);
			}
			
			if(is_array($variables2) && count($variables2) > 0){
				#prikazujemo ločeno
				if ($this->sessionData['means']['meansSeperateTables'] == true || $this->sessionData['mean_charts']['showChart'] == '1') {
					foreach ($variables2 AS $v_second) {
						if (is_array($variables1) && count($variables1) > 0) {
							foreach ($variables1 AS $v_first) {
								$_means = $this->meansClass->createMeans($v_first, $v_second);
								if ($_means != null) {
									$means[$c1][0] = $_means;
								}
								$c1++;
							}
						}
					}
				}
				#prikazujemo skupaj
				else {
					foreach ($variables2 AS $v_second) {
						if (is_array($variables1) && count($variables1) > 0) {
							foreach ($variables1 AS $v_first) {
								$_means = $this->meansClass->createMeans($v_first, $v_second);
								if ($_means != null) {
									$means[$c1][$c2] = $_means;
								}
								$c2++;
							}
						}
						$c1++;
						$c2=0;
					}
				}
			}
			
			
			if (is_array($means) && count($means) > 0) {
				$count = 0;
				foreach ($means AS $mean_sub_grup) {

					if($this->sessionData['mean_charts']['showChart'] == '1'){						
						//$this->displayMeansTable($mean_sub_grup);
						$tabela .= $mean->displayMeansTable($mean_sub_grup, $this->meansClass, $this->export_format);
						
						//$this->displayChart($count);
						$tabela .= $mean->displayChart($count, $meanData1, $meanData2, $this->sessionData);						
					}
					else{			
						$tabela .= $mean->displayMeansTable($mean_sub_grup, $this->meansClass, $this->export_format);
					}					
					$count++;
				}
			}
		}
		
		//echo "tabela: ".$tabela."</br>";
		return $tabela;
	}
	
	public function displayTTestTablesLatex(){		
		global $site_path;
		global $lang;
		global $global_user_id;
		$tabela = '';
		
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData();
		
		// ustvarimo ttest objekt
		$this->ttestClass = new SurveyTTest($this->anketa['id']);	

		if (class_exists('AnalizaTTest')) {
			$tTest = new AnalizaTTest($this->anketa);
		}		
		
		if (count($this->sessionData['ttest']['sub_conditions']) > 1 ) {
			$variables1 = $this->ttestClass->getSelectedVariables();
			if (count($variables1) > 0) {
				foreach ($variables1 AS $v_first) {
					$ttest = null;
					$ttest = $this->ttestClass->createTTest($v_first, $this->sessionData['ttest']['sub_conditions']);
					$tabela .= $tTest->displayTTestTable($ttest, $this->ttestClass, $this->export_format, $this->sessionData);
					// Izrisemo graf za tabelo - zaenkrat samo admin
					if(isset($this->sessionData['ttest_charts']['showChart']) && $this->sessionData['ttest_charts']['showChart'] == true){
						$tabela .= $tTest->displayChart($this->sessionData, $this->ttestClass, $this->anketa);						
					}
				}
			}
		}		

		//echo "tabela: ".$tabela."</br>";
		return $tabela;
	}
	
	
	function displayCrosstabsTable() {
		global $lang;
		$tabela = '';
		$crosstab = new AnalizaCrosstab($this->anketa, $this->crosstabClass, $this->counter);
		$tabela .= $crosstab->showCrosstabsTable($this->crosstabClass, $this->export_format);
		return $tabela;
	}
	
	
	public function displayChartLatex($spr_id_0=null){
		global $site_path;
		global $lang;
		global $global_user_id;
		$graf = '';
		# preberemo header
		if ($this->headFileName !== null) {
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			//SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
		
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
			if (class_exists('AnalizaCharts')) {
				$chart = new AnalizaCharts($this->anketa, $this->export_format, $spr_id_0);
			}
			# če nimamo zank
			if(count(SurveyAnalysis::$_LOOPS) == 0){

				$graf .= $chart->displayCharts();
			}
			else{
				// izrisemo samo eno tabelo iz enega loopa
				if($this->current_loop > 0){
					
					$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
					$loop['cnt'] = $this->current_loop;
					SurveyAnalysis::$_CURRENT_LOOP = $loop;
					
					// Izpisemo naslov zanke za skupino
/* 					$this->pdf->setFont('','B','10');
					$this->pdf->ln(5);
					$this->pdf->MultiCell(200, 5, $this->encodeText($lang['srv_zanka_note'].$loop['text']), 0, 'L', 0, 1, 0 ,0, true);
					$this->pdf->ln(5);
					$this->pdf->setFont('','','6');	 */
					
					//$graf .=  $this->displayCharts();
					$graf .= $chart->displayCharts();

				}
				// Izrisemo vse tabele spremenljivka (iz vseh loopov)
				else{
					$loop_cnt = 0;
					# če mamo zanke
					foreach(SurveyAnalysis::$_LOOPS AS $loop) {
						$loop_cnt++;
						$loop['cnt'] = $loop_cnt;
						SurveyAnalysis::$_CURRENT_LOOP = $loop;
						
						// Izpisemo naslov zanke za skupino
/* 						$this->pdf->setFont('','B','10');
						$this->pdf->ln(5);
						$this->pdf->MultiCell(200, 5, $this->encodeText($lang['srv_zanka_note'].$loop['text']), 0, 'L', 0, 1, 0 ,0, true);
						$this->pdf->ln(5);
						$this->pdf->setFont('','','6');	 */
						
						//$graf .= $this->displayCharts();
						$graf .= $chart->displayCharts();
					}
				}
			}
			
		} // end if else ($_headFileName == null)
		
		
		return $graf;
	}
	
	
	public function displayCreportLatex(){
		global $site_path;
		global $lang;
		global $global_user_id;
		$creportLatex = '';
		
		$creport = new AnalizaCReport($this->anketa, $this->export_format);
		//$creport = new AnalizaCReport($this->anketa);
		$anketaId = $this->anketa['id'];
		
		
		//*******************************************************************
		$creportProfile= $creport->getCreportProfile();
		$what = 'creport_title_profile_'.$creportProfile;
		
		$sqlT = sisplet_query("SELECT value FROM srv_user_setting_for_survey WHERE sid='$this->ank_id' AND uid='$this->usr_id' AND what='$what'");
		
		if(mysqli_num_rows($sqlT) == 0){
			$titleString = $lang['export_analisys_creport'].': '.SurveyInfo::getInstance()->getSurveyTitle();
		}
		else{
			$rowT = mysqli_fetch_array($sqlT);		
			$titleString = $rowT['value'];
		}
		
		$naslovIzvoza = $this->encodeText($titleString);
		//$creportLatex .= '\textbf{'.$naslovIzvoza.'}'.$this->texBigSkip.$this->texNewLine;
		$creportLatex .= '\MakeUppercase{\huge \textbf{'.$naslovIzvoza.'}}'.$this->texBigSkip.$this->texNewLine;

		if($this->export_format == 'pdf'){
			$creportLatex .= '\begin{tableAnalysis}';	/*zacetek environmenta z manjsim fontom*/
		}			
		
		if ($creport->getDataFileStatus() == FILE_STATUS_NO_DATA || $creport->getDataFileStatus() == FILE_STATUS_NO_FILE || $creport->getDataFileStatus() == FILE_STATUS_SRV_DELETED) {
			$creportLatex .= 'NAPAKA!!! Manjkajo datoteke s podatki.'.$this->texNewLine;
		}		
		else{
			$sqlString = "SELECT * FROM srv_custom_report WHERE ank_id='$anketaId' AND usr_id='$global_user_id' AND profile='$creportProfile' ORDER BY vrstni_red ASC";
			$sql = sisplet_query($sqlString);			
			if(mysqli_num_rows($sql) > 0){
				// Loop po vseh dodanih elementih porocila
				while($row = mysqli_fetch_array($sql)){
					//echo "tipi spremenljivk: ".$row['type']."</br>";
					
					switch($row['type']){
						// sumarnik
						case '1':
							// naslov elementa
 							$creportLatex .= $creport->displayTitle($row);
							
							$creportLatex .= $creport->displaySum($row);
							
							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);
							
							break;
						
						// frekvence
						case '2':
							// naslov elementa
							$creportLatex .= $creport->displayTitle($row);
							
							$creportLatex .= $creport->displayFreq($row);
							
							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);

							break;
						
						// opisne
						case '3':
							// naslov elementa
 							$creportLatex .= $creport->displayTitle($row);
							
							$creportLatex .= $creport->displayDesc($row);
							
							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);	

							break;
						
						// grafi
						case '4':
							// naslov elementa
							$creportLatex .= $creport->displayTitle($row);
							
							$creportLatex .= $creport->displayChart($row);
							
							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);							
							
							break;
						
						// crosstab
						case '5':
							// naslov elementa
							$creportLatex .= $creport->displayTitle($row);
							
							// tabela
							if($row['sub_type'] == '0'){
								$creportLatex .= $creport->displayCrosstab($row);
							}								
							// graf
							else{
								$creportLatex .= $creport->displayCrosstabChart($row);
							}								

							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);
							
							break;
						
						// mean
						case '6':
							// naslov elementa
 							$creportLatex .= $creport->displayTitle($row);
							
							// tabela
							if($row['sub_type'] == '0'){
								$creportLatex .= $creport->displayMean($row);
							}								
							// graf	
							else{
								$creportLatex .= $creport->displayMeanChart($row);
							}

							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);
							
							break;
						
						// ttest
						case '7':
							// naslov elementa
					 		$creportLatex .= $creport->displayTitle($row);
							
							// tabela
							if($row['sub_type'] == '0')
								$creportLatex .= $creport->displayTTest($row);
							// graf
							else
								$creportLatex .= $creport->displayTTestChart($row);
								
							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);

							break;
							
						// text
						case '8':
							$creportLatex .= $creport->displayText($row['text']);							
							break;
							
						// break
						case '9':							
							// naslov elementa
 							$creportLatex .= $creport->displayTitle($row);
							
 							// tabela
							if($row['sub_type'] == '0'){
								$creportLatex .= $creport->displayBreak($row);
							}								
							// graf
							else{
								$creportLatex .= $creport->displayBreakChart($row);
							}							

							// Komentar elementa
							$creportLatex .= $creport->displayComment($row['text']);
							
							break;
						
						// page break
/* 						case '-1':
							if($this->pdf->getY() > 30)
								$this->pdf->AddPage();
							
							break; */
					}					
				}
			}
		}
		
		if($this->export_format == 'pdf'){
			$creportLatex .= '\end{tableAnalysis}';	/*zakljucek environmenta z manjsim fontom*/
		}
		
		//*******************************************************************
		//echo $creportLatex."</br>";
		return $creportLatex;
	}
	
	/*Moje funkcije*/
	
	// Izrisujemo NAVADEN GRAF
	public function displayChartsInLatex($spid=null, $type=0, $fromCharts=false, $anketa=null, $from='sums', $exportClass=null, $export_format='', $analizaClass=null){
		global $site_path;
		global $lang;
		global $global_user_id;
		
		//iniciacija spremenljivk
		//$charts = '';
		//$charts = '\begin{absolutelynopagebreak}';	//da se naslov in graf pojavljata na eni strani
		$charts = '';	//da se naslov in graf pojavljata na eni strani
		$this->anketa = $anketa;
		$this->user_id = $global_user_id;		
		$this->from = $from;		
		$this->exportClass = $exportClass;
		$this->export_format = $export_format;
		
		if($analizaClass != null){
			$this->analizaClass = $analizaClass;
		}

		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		// preberemo nastavitve iz baze (prej v sessionu)		
		
		SurveyUserSession::Init($this->anketa['id']);		
		$this->sessionData = SurveyUserSession::getData();
				
		// ce ga imamo v sessionu ga preberemo
		if(isset($this->sessionData['charts'][$spid])){
			
			// Napolnimo podatke za graf - rabimo za cache
			if(count(SurveyAnalysis::$_LOOPS) == 0){
				$settings = $this->sessionData['charts'][$spid];
			
			}else{
				$settings = $this->sessionData['charts'][$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']];
			}				
			
			$DataSet = SurveyChart::getDataSet($spid, $settings);
			
			// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
			if($DataSet == 0){
				//self::displayEmptyWarning($spid);
				return;
			}
			
			// preberemo ime slike iz sessiona
			$imgName = $settings['name'];

		}
		// ce ga nimamo v sessionu
		else{
			// Napolnimo podatke za graf - rabimo za cache
			$settings = SurveyChart::getDefaultSettings();
			
			$DataSet = SurveyChart::getDataSet($spid, $settings);
			
			// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
			if($DataSet == 0){
				//self::displayEmptyWarning($spid);
				return;
			}

			$ID = SurveyChart::generateChartId($spid, $settings, $DataSet->GetNumerus());		
			
			$Cache = new pCache('pChart/Cache/');
			$Cache = new pCache('../survey/pChart/Cache/');
			$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		}
		
		// Izrisemo naslov (v creportu ne, ker imamo drugacne naslove)
		if($fromCharts){
			
			$stevilcenje = ($this->exportClass->numbering == 1 ? $spremenljivka['variable'].' - ' : '');
			$title = $stevilcenje . $spremenljivka['naslov'];
			
			if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
				$title .= ' (n = '.$DataSet->GetNumerus().')';
			}
			
			// Preverimo ce prebija slika stran
/* 			if(isset($this->sessionData['charts'][$spid])){			
				if(count(SurveyAnalysis::$_LOOPS) == 0)
					$settings = $this->sessionData['charts'][$spid];
				else
					$settings = $this->sessionData['charts'][$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']];

				$imgName = $settings['name'];
				$size = getimagesize('pChart/Cache/'.$imgName);
				$ratio = ($size[0] / 800) < 1 ? 1 : ($size[0] / 800);
				$height = $size[1] / 5;
			} */
				
			//self::$exportClass->pdf->setFont('','b','6');
			//self::$exportClass->pdf->MultiCell(165, 5, $title, 0, 'C', 0, 1, 0 ,0, true);	
			$boldedTitle = $this->returnBold($this->encodeText($title)).$this->texNewLine;	//vrni boldan naslov in skoci v novo vrstico
			
			if($spremenljivka['tip'] == 2){
				//self::$exportClass->pdf->setFont('','','5');
				//self::$exportClass->pdf->MultiCell(165, 1, $lang['srv_info_checkbox'], 0, 'C', 0, 1, 0 ,0, true);
				$boldedSubTitle .= $lang['srv_info_checkbox'].$this->texNewLine;
			}
			//self::$exportClass->pdf->setFont('','','6');
		}
	
		
		
		// IZRIS GRAFA
		$this->path2Charts = $site_path.'admin/survey/pChart/Cache/';
		
		//kopiranje slik kot png, ker latex mora imeti extension za prikazovanje slike
		//copy('pChart/Cache/'.$imgName,'pChart/Cache/'.$imgName.'.png');

		##### ZA TESTIRANJE  ureditev pretvorbe slike v pdf
		chdir($this->path2Charts);
		$pretvoriPng_v_Pdf = "/usr/bin/convert $imgName $imgName.pdf";
		shell_exec($pretvoriPng_v_Pdf);
		##### ZA TESTIRANJE ureditev pretvorbe slike v pdf - konec
		
		$texImageOnly = " \\includegraphics[scale=0.66]{".$this->path2Charts."".$imgName."} ";	//latex za sliko
		//$texImageOnly = " \\includegraphics[scale=0.66, draft=false]{".$this->path2Charts."".$imgName."} ";	//latex za sliko
		//$texImageOnly = " \\includegraphics[scale=0.85]{".$this->path2Charts."".$imgName."} ";	//latex za sliko
		//echo "ime slike: $texImageOnly </br>";
		
				
		$charts .= $this->returnCentered($boldedTitle.$boldedSubTitle.$texImageOnly, $export_format); //vrni sredinsko poravnana naslov in slika
		
		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) {
			foreach ($_answersOther AS $oAnswers) {				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
				if(count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){				
					$charts .= $this->outputOtherAnswers($oAnswers, '', $export_format);
				}
			}
		}
		
		
		// Dodamo space (v creportu ne, ker imamo drugacen izpis)
		//if($fromCharts)
			//self::$exportClass->pdf->setY(self::$exportClass->pdf->getY() + 10);
		
		//$charts .= '\end{absolutelynopagebreak}';
		//echo "Charts: ".$charts."</br>";
		return $charts;
	}
	
	
	#moja funkcija encodeText
	function encodeText($text=''){
		// popravimo sumnike ce je potrebno
		//$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		//$text = str_replace("&scaron;","š",$text);
		//echo "Encoding ".$text."</br>";

		//resevanje razbirajanja predolgih neprekinjenih besed in URL - spremenljivke za kasnejsi prilagojen izpis
		$numOfWords = str_word_count($text, 0); //stevilo besed v besedilu
		$numOfSpacesPrej = substr_count($text, ' '); //stevilo presledkov v besedilu
		$stringLength = strlen($text);	//dolzina besedila

		$findSpace = ' ';
		$findHttp = 'http://';
		$findHttps = 'https://';
		$posHttp = strpos($text, $findHttp);
		$posHttps = strpos($text, $findHttps);
		$isURL = 0;
		/* if($posHttp !== false || $posHttps !== false) {	//imamo URL naslov			
			$isURL = 1;
		} */
		//resevanje razbirajanja predolgih neprekinjenih besed in URL - konec

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
				
				//$text = $textPrej.' '.PIC_SIZE_ANS."{".$this->getImageName($text, 0, '<img')."}".' '.$textPotem;
				$text = $textPrej.' '.PIC_SIZE_ANS."{".$this->path2Images."".$this->getImageName($text, 0, '<img')."}".' '.$textPotem;
				
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
		//$text = str_replace('%','\% ',$text);
		$text = str_replace('%','\%',$text);
		$text = str_replace('€','\euro',$text);		
		$text = str_replace('^','\textasciicircum{} ',$text);		
		//$text = str_replace('_','\_ ',$text);	
		$text = str_replace('_','\_',$text);	
		$text = str_replace('~','\textasciitilde{} ',$text);
		if(strpos($text, '&amp;')){	//ce je prisotno v besedilu &amp;'
			$text = str_replace('&amp;','\& ',$text);
		}else{
			$text = str_replace('&','\& ',$text);
		}		
		
		$andSymbolPresent = 0;
		$posAndSymbolPresent = strpos($text,'&amp;');
		if($posAndSymbolPresent !== false){	//ce je v besedilu prisoten '&' zapisan kot '&amp;'
			$text = str_replace('&amp;','\&',$text);
			$andSymbolPresent = 1;
		}
		if($andSymbolPresent == 0){
			$text = str_replace('&','\&',$text);
		}
		
		//$text = str_replace('&lt;','\textless ',$text);
		$text = str_replace('&lt;','\textless',$text);
		//$text = str_replace('&gt;','\textgreater ',$text);
		$text = str_replace('&gt;','\textgreater',$text);
		$text = str_replace('&nbsp;',' ',$text);
		//ureditev posebnih karakterjev za Latex - konec

		//ureditev grskih crk
		$text = str_replace('α','\textalpha ',$text);
		$text = str_replace('β','\textbeta ',$text);
		$text = str_replace('γ','\textgamma ',$text);
		$text = str_replace('δ','\textdelta ',$text);
		$text = str_replace('ε','\textepsilon ',$text);
		$text = str_replace('ζ','\textzeta ',$text);
		$text = str_replace('η','\texteta ',$text);
		$text = str_replace('θ','\texttheta ',$text);
		$text = str_replace('ι','\textiota ',$text);
		$text = str_replace('κ','\textkappa ',$text);
		$text = str_replace('λ','\textlambda ',$text);
		$text = str_replace('μ','\textmugreek ',$text);
		$text = str_replace('ν','\textnu ',$text);
		$text = str_replace('ξ','\textxi ',$text);
		//$text = str_replace('ο','\textomikron ',$text);
		$text = str_replace('π','\textpi ',$text);
		$text = str_replace('ρ','\textrho ',$text);
		$text = str_replace('σ','\textsigma ',$text);
		$text = str_replace('τ','\texttau ',$text);
		$text = str_replace('υ','\textupsilon ',$text);
		$text = str_replace('φ','\textphi ',$text);
		$text = str_replace('χ','\textchi ',$text);
		$text = str_replace('ψ','\textpsi ',$text);
		$text = str_replace('ω','\textomega ',$text);
		//ureditev grskih crk - konec
		
		//ureditev preureditve html kode ul in li v latex itemize
		$findUl = '<ul';
		$findUlLength = strlen($findUl);
		$posUl = strpos($text, $findUl);
		if($posUl !== false){			
			//echo "text prej: ".$text."</br>";
			$numOfUl = substr_count($text, $findUl);	//stevilo '<ul' v tekstu
			//echo "numOfUl ".$numOfUl."</br>";			
			######################
			if($numOfUl!=0){
				$text = str_replace('<ul>','\begin{itemize} ', $text);
				$text = str_replace('<li>','\item ', $text);
				$text = str_replace('</ul>','\end{itemize} ', $text);					
			}
			//echo "prazno v html: ".strpos($text, '\r')."</br>";
			//echo "text potem: ".$text."</br>";
			######################
		}
		
		//ureditev preureditve html kode ul in li v latex itemize - konec
		
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

		//ureditev odstranjevanja presledkov, ce so na zacetku ali koncu besedila
		if(($numOfSpacesPrej)){	//ce so prisotni presledki
			$odstranjeno = 0;	//belezi, ali so bili presledki odstranjeni iz zacetka ali konca
			for($numPresledkovTmp = 1; $numPresledkovTmp <= $numOfSpacesPrej; $numPresledkovTmp++){	//za vsak presledek
				$posSpace = strpos($text, $findSpace);	//najdi pozicijo presledka v besedilu//preveri, kje se nahaja
				if($posSpace==0){	//ce je presledek na zacetku besedila
					$text = substr_replace($text, '', $posSpace, 1);	//odstrani presledek iz besedila
					$stringLength = strlen($text);
					$odstranjeno = 1;
				}elseif($posSpace==$stringLength){	//ce je presledek na koncu besedila
					$text = substr_replace($text, '', $posSpace, 1);	//odstrani presledek iz besedila
					$stringLength = strlen($text);
					$odstranjeno = 1;
				}
			}
			$numOfSpacesPrej = substr_count($text, ' '); //stevilo presledkov v besedilu
		}
		//ureditev odstranjevanja presledkov, ce so na zacetku ali koncu besedila - konec
		
		//echo "v besedilu $text je stevilo presledkov $numOfSpacesPrej in besed $numOfWords </br>";
		//priprava izpisa zelo dolgega besedila brez presledkov s seqsplit (URL, email, ...)
		if( ($numOfSpacesPrej == 0 && $stringLength >= MAX_STRING_LENGTH && $odstranjeno) ){	//ce v besedilu ni presledkov in je besedilo daljse od max dovoljene dolzine
			$text = "\seqsplit{".$text."}"; //ni v redu seqsplit, ker ne dela, če so posebni znaki			
		}
		//priprava izpisa zelo dolgega besedila brez presledkov - konec

		return strip_tags($text); //vrni tekst brez html tag-ov
	}
	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele ##################################################################################
	#argumenti 1. export_format, 2. parametri tabele, 3. tip tabele za pdf, 4. tip tabele za rtf, 5. sirina pdf tabele (delez sirine strani), 6. sirina rtf tabele (delez sirine strani)
	function StartLatexTable($export_format='', $parameterTabular='', $pdfTable='', $rtfTable='', $pdfTableWidth=null, $rtfTableWidth=null){
		$tex = '';
		$tex .= '\keepXColumns';
 		if($export_format == 'pdf'){
			$tex .= '\begin{'.$pdfTable.'}';
			if($pdfTable=='tabularx'){
				//$tex .= '{'.$pdfTableWidth.'\textwidth}';
				$tex .= '{\hsize}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}elseif($export_format == 'rtf'||$export_format == 'xls'){
			$tex .= '\begin{'.$rtfTable.'}';
			if($rtfTable=='tabular*'){
				$tex .= '{'.$pdfTableWidth.'\textwidth}';
			}
			$tex .= '{ '.$parameterTabular.' }';
		}	
		return $tex;
	}	
	#funkcija, ki skrbi za izpis latex kode za zacetek tabele - konec ##########################################################################
	
	function outputSumaValidAnswerVertical($counter=null,$_sequence=null,$spid=null,$_options=array()) {
		global $lang;
		
		$text = array();
		$texoutputSumaValidAnswerVertical = '';
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$currentMissingProfile == 2) ? TRUE : FALSE;
		
		$_sufix = '';

		$text[] = $this->encodeText($lang['srv_anl_valid']);
		$text[] = $this->encodeText($lang['srv_anl_suma1']);
		
		$text[] = $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0);
		
		//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		$text[] = $this->encodeText(self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		//$text[] = $this->encodeText(SurveyAnalysis::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		$text[] = $this->encodeText(self::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		$text[] = '';
		
		
		$exportformat = $options['exportFormat'];
		$brezHline = $this->getBrezHline($exportformat);
		
		$outputSumaValidAnswerVertical .= self::tableRow($text, $brezHline);
		return $outputSumaValidAnswerVertical;
		//$counter++;
		//return $counter;		
	}
		
	function outputValidAnswerVertical($counter=null,$vkey='', $vAnswer=null, $_sequence=null,$spid=null, &$_kumulativa=null,$_options=array()) {
		global $lang;
		//echo "funkcija outputValidAnswerVertical </br>";
		$text = array();
		
		$texoutputValidAnswerVertical = '';
		
		# opcije		
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.SurveyAnalysis::$cssColors['0_' . ($counter & 1)];

		$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid;
		
		# ali presegamo število prikazanih vrstic, takrat v zadnji prikazani dodamo link več.. ostale vrstice pa skrijemo
/* 		if ($options['textAnswerExceed'] == true) {
			if ($counter == TEXT_ANSWER_LIMIT ) {
				# link za več
				$show_more = '<div id="valid_row_togle_'.$_sequence.'" class="floatRight blue pointer" onclick="showHidenTextRow(\''.$_sequence.'\');return false;">(več...)</div>'.NEW_LINE;
			} elseif ($counter > TEXT_ANSWER_LIMIT ) {
				$hide_row = ' hidden';
				$_exceed = true;
			}			
		} */
		
		//if ($counter < TEXT_MAX_ANSWER_LIMIT) {
	 		$text[] = '';

			$addText = (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
			//$text[] = $this->encodeText('  '.$vkey.$addText);
			$text[] = $this->snippet($this->encodeText('  '.$vkey.$addText), 400);
			
			$text[] = (int)$vAnswer['cnt'];

			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = $this->encodeText(self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = $this->encodeText(self::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
						
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = $this->encodeText(self::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));

		/*} elseif ($counter == TEXT_MAX_ANSWER_LIMIT ) {
	 		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'">';
	 		echo '<td class="anl_bl anl_ac anl_br gray anl_dash_bt anl_dash_bb" colspan="'.(6+(int)SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']+((int)SurveyAnalysis::$_SHOW_LEGENDA*2)).'"> . . . Prikazujemo samo prvih '.TEXT_MAX_ANSWER_LIMIT.' veljavnih odgovorov!</td>';
			echo '</tr>';
		}*/
		
		$exportformat = $options['exportFormat'];
		$brezHline = $this->getBrezHline($exportformat);
		
		$texoutputValidAnswerVertical .= self::tableRow($text, $brezHline);
		//echo "Besedilo na koncu funkcije outputValidAnswerVertical:".$texoutputValidAnswerVertical."</br>";
		return $texoutputValidAnswerVertical;
/*  		$counter++;
		return $counter; */
	}
	
	function outputInvalidAnswerVertical($counter=null,$vkey='', $vAnswer=null, $_sequence=null, $spid=null, $_options=array()) {
		global $lang;	
		
		$text = array();
		$texoutputInvalidAnswerVertical = '';
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$exportformat = $options['exportFormat'];
		$brezHline = $this->getBrezHline($exportformat);

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
 
		$_sufix = '';
		
		//$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	//po tej stari kodi ne pridem do zelene informacije, tudi stari izvozi ne delajo pravilno, ce se zeli pokazati missinge
		
		$_Z_MV = !$this->hideEmpty;
		
		//$_Z_MV = 1;
		if($_Z_MV){
			//echo "this->hideEmpty: ".$this->hideEmpty."</br>";
			//$text[] = $this->encodeText($lang['srv_anl_missing1']);
			//$text[] = '\multirow{ '.$vAnswer['cnt'].'}{*}{ '.$this->encodeText($lang['srv_anl_missing1']).' }';
			
			$text[] = '';
			
			$text[] = $this->encodeText($vkey.' (' . $vAnswer['text'].')');
			
			$text[] = $this->encodeText((int)$vAnswer['cnt']);

			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = $this->encodeText(self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = '';
			$text[] = '';
			
			$texoutputInvalidAnswerVertical .= $this->tableRow($text, $brezHline);
		}
		$counter++;
		//echo "Besedilo na koncu funkcije outputInvalidAnswerVertical:".$texoutputInvalidAnswerVertical."</br>";
		return $texoutputInvalidAnswerVertical;
 
		/*return $counter; */
	}

	function outputSumaInvalidAnswerVertical($counter=null, $_sequence=null, $spid=null, $_options = array()) {
		global $lang;
		
		$texoutputSumaInvalidAnswerVertical = '';
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$exportformat = $options['exportFormat'];
		$brezHline = $this->getBrezHline($exportformat);
		
		$cssBck = ' '.SurveyAnalysis::$cssColors['text_' . ($counter & 1)];
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		//$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		$_brez_MV = $this->hideEmpty;
		if(!$_brez_MV){
			$text = array();
			
			//$text[] = $this->encodeText($lang['srv_anl_missing1']);
			$text[] = '';
			
			$text[] = $this->encodeText($lang['srv_anl_suma1']);
			//$text[] = $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt']);			
			
			$answer['cnt'] =  SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
			$text[] = $this->encodeText((int)$answer['cnt']);
			
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = $this->encodeText(self::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '';
			$text[] = '';
			
			$texoutputSumaInvalidAnswerVertical .= $this->tableRow($text, $brezHline);
		}
		//echo $texoutputSumaInvalidAnswerVertical."</br>";	
		return $texoutputSumaInvalidAnswerVertical;
/* 		$counter++;
		return $counter; */
	}
	
	function outputSumaVertical($counter=null, $_sequence=null, $spid=null, $_options = array()) {
		global $lang;
		
		$texoutputSumaVertical = '';
		# opcije			
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$cssBck = ' '.SurveyAnalysis::$cssColors['0_' .($counter & 1)];

		//$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		
/* 		if($options['exportFormat'] == 'xls'){
			$brezHline = 1;
		}else{
			$brezHline = 0;
		} */
		
		$exportformat = $options['exportFormat'];
		$brezHline = $this->getBrezHline($exportformat);
		
		$_brez_MV = $this->hideEmpty;
		if(!$_brez_MV){
		
			$text = array();
		
			$text[] = '';
			$text[] = $this->encodeText($lang['srv_anl_suma2']);
			$text[] = $this->encodeText((SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0));	
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = $this->encodeText(self::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '';	
			$text[] = '';
			
			
			$texoutputSumaVertical .= $this->tableRow($text, $brezHline);
		}
		return $texoutputSumaVertical;
		
	}
	
	function outputOtherAnswers($oAnswers=null, $parameterTabular='', $export_format='') {
		global $lang;
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];
		
		$this->export_format = $export_format;
		
		$texOutputOtherAnswers = '';
		
		//Priprava parametrov za tabelo
		$steviloStolpcevParameterTabular = 6;
		$steviloOstalihStolpcev = $steviloStolpcevParameterTabular - 1; /*stevilo stolpcev brez prvega stolpca, ki ima fiksno sirino*/
		$sirinaOstalihStolpcev = 0.9/$steviloOstalihStolpcev;
		$parameterTabular = '|';
		
		for($i = 0; $i < $steviloStolpcevParameterTabular; $i++){
			//ce je prvi stolpec
			if($i == 0){
				$parameterTabular .= ($export_format == 'pdf' ? 'P|' : 'l|');
			}else{
				$parameterTabular .= ($export_format == 'pdf' ? '>{\hsize='.$sirinaOstalihStolpcev.'\hsize \centering\arraybackslash}X|' : 'c|');	/*sirina ostalih je odvisna od njihovega stevila, da se sirine razporedijo po celotni sirini tabele*/
			}
			
		}
		//Priprava parametrov za tabelo - konec
		
		//zacetek latex tabele z obrobo	za Drugo	
		$pdfTable = 'tabularx';
		$rtfTable = 'tabular';
		$pdfTableWidth = 1;
		$rtfTableWidth = 1;
		
		$texOutputOtherAnswers .= $this->StartLatexTable($export_format, $parameterTabular, $pdfTable, $rtfTable, $pdfTableWidth, $rtfTableWidth); /*zacetek tabele*/
		if($export_format != 'xls'){
			$texOutputOtherAnswers .= $this->horizontalLineTex; /*obroba*/		
		}
		//zacetek latex tabele z obrobo za Drugo - konec
		
		/*Naslovni vrstici tabele*/
		//prva vrstica tabele
		$texOutputOtherAnswers .= $this->encodeText($_variable['variable'])." & \multicolumn{5}{l|}{".$this->encodeText(SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )')."} ".$this->texNewLine;
		//$texOutputOtherAnswers .= $this->encodeText($_variable['variable'])." & \multicolumn{5}{X|}{".$this->encodeText(SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )')."} ".$this->texNewLine;
		if($export_format != 'xls'){
			$texOutputOtherAnswers .= $this->horizontalLineTex; /*obroba*/
			$brezHline = 1;
		}

		//druga vrstica tabele z naslovi stolpcev
		$texOutputOtherAnswers .= $this->tableHeader();

		//$this->pdf->setFont('','','6');
		
		/*Naslovni vrstici tabele - konec*/
		
		
		
		//prva vrstica			
/* 		$this->pdf->setFont('','b','6');
		$this->pdf->ln(5);
		$this->pdf->MultiCell(18, $height, $this->encodeText($_variable['variable']), 1, 'C', 0, 0, 0 ,0, true);
		$this->pdf->MultiCell(162, $height, $this->encodeText(SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )'), 1, 'L', 0, 1, 0 ,0, true);		 */
		
		//druga vrstica
/* 		$this->tableHeader();
		$this->pdf->setFont('','','6');		 */
		// konec naslovne vrstice				

		$counter = 1;
		$_kumulativa = 0;
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					//$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
					$texOutputOtherAnswers .= self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true, 'exportFormat'=>$export_format));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
			$texOutputOtherAnswers .= self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true, 'exportFormat'=>$export_format));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			$_Z_MV = !$this->hideEmpty;							
			if($_Z_MV){	//ce je potrebno izpisati tudi manjkajoce			
				$texOutputOtherAnswers .= $this->encodeText($lang['srv_anl_missing1']);
			}
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					//$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
					$texOutputOtherAnswers .= self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true, 'exportFormat'=>$export_format));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
			//$texOutputOtherAnswers .= self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
			$texOutputOtherAnswers .= self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true, 'exportFormat'=>$export_format));
		}
		#izpišemo še skupno sumo
		//$texOutputOtherAnswers .= self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		$texOutputOtherAnswers .= self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true, 'exportFormat'=>$export_format));
		
		//zaljucek latex tabele za Drugo
		$texOutputOtherAnswers .= ($export_format == 'pdf' ? "\\end{tabularx}" : "\\end{tabular}");
		//zaljucek latex tabele za Drugo - konec
		
		return $texOutputOtherAnswers;
	}
	
	function tableHeader($export_format=''){	
		global $lang;
		
		$tableHeader = '';
		
		$naslov = array();
		$naslov[] = '';
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('border' => 'TB', 'bold' => 'B', 'align2' => 'C');
		
		//$tableHeader .= $this->tableRow($naslov, $params);	
		if($export_format=='xls'){
			$brezHline = 1;
		}else{
			$brezHline = 0;
		}
		
		$tableHeader .= $this->tableRow($naslov, $brezHline);	
				/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = 1; //$height = $this->getCellHeight($this->encodeText($arrayText[1]), 90);
		
		//ce smo na prelomu strani
/* 		if( ($this->pdf->getY() + $height) > 270){					
			$this->drawLine();			
			$this->pdf->AddPage('P');
			$arrayParams['border'] .= 'T';
		} */
/* 		
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';

		for($i=0;$i<count($naslov);$i++){
			$tableHeader .= $naslov[$i].$this->texNewLine;
			echo "naslovi v tabeli: ".$naslov[$i]."</br>";
		} */
		
		return $tableHeader;
	}
	
	//funkcija skrbi za izpis multicol celice
	function MultiColCellLatex($steviloVmesnihStolpcevPodvrstic=null, $text='', $odZacetka=0){			
			$tabela = '';
			//echo "steviloVmesnihStolpcevPodvrstic: $steviloVmesnihStolpcevPodvrstic</br>";
			if($steviloVmesnihStolpcevPodvrstic==1){	//ce je 1, ne sme biti multicolumn{1}, saj so drugace tezave z izpisom
				$tabela .= " & ".$text." ";
			}else{
				$steviloTabColSep = ($steviloVmesnihStolpcevPodvrstic-1)*2;
				$steviloArrayrulewidth = ($steviloVmesnihStolpcevPodvrstic-1);
				if($odZacetka==0){					
					//$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{X|}{";//zacetek multicol
					$tabela .= " & \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{>{\hsize=\dimexpr".$steviloVmesnihStolpcevPodvrstic."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}C|}{";//zacetek multicol
				}else{					
					//$tabela .= " \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{X|}{";//zacetek multicol
					$tabela .= " \multicolumn{".$steviloVmesnihStolpcevPodvrstic."}{>{\hsize=\dimexpr".$steviloVmesnihStolpcevPodvrstic."\hsize+".$steviloTabColSep."\\tabcolsep+".$steviloArrayrulewidth."\arrayrulewidth\\relax}C|}{";//zacetek multicol
				}				
				$tabela .= $text;
				if($odZacetka==0){
					$tabela .= '} ';//zakljucek multicol
				}else{
					$tabela .= '} &';//zakljucek multicol
				}				
			}

			//echo "fukcija s tekstom: ".$tabela." </br>";
			return $tabela;
	}
	
	//funkcija skrbi za izpis multirow celice, ce je ta potrebna
	function MultiRowCellLatex($steviloVmesnihVrstic=null, $text='', $tabela2 = '', $tabela3 = '', $cols=1){
		$tabela = '';
		global $lang;
		//echo "cols: $cols</br>";
		//if($steviloVmesnihVrstic > 1){	//ce je potrebno multirow prikazovanje
		if($steviloVmesnihVrstic > 1 && (($tabela2!=''&&$tabela3=='') || ($tabela2!=''&&$tabela3!=''))){	//ce je potrebno multirow prikazovanje
			$tabela .= '\multirow{'.$steviloVmesnihVrstic.'}{*}{'; //zacetek multirow
		}
		$tabela .= $text;
		/* if($cols==0 && $text==$this->encodeText($lang['srv_analiza_crosstab_skupaj'])){	//premaknil nize, ker je delalo težave pri izpisu daljsih tabel
			$tabela .= ' & ';
		} */
		//if($steviloVmesnihVrstic > 1){	//ce je potrebno multirow prikazovanje
		if($steviloVmesnihVrstic > 1 && (($tabela2!=''&&$tabela3=='') || ($tabela2!=''&&$tabela3!=''))){	//ce je potrebno multirow prikazovanje
			$tabela .= '}'; //konec multirow
		}

		if($cols==0 && $text==$this->encodeText($lang['srv_analiza_crosstab_skupaj'])){
			$tabela .= ' & ';
		}
		//echo $tabela."</br>";
		return $tabela;
	}
	
	function DisplayLatexCells($crossChk='', $podVrstice=null, $colNum=null, $steviloVmesnihStolpcevPodvrstic=2, $niSodo = 0){
		$tabela = '';
		//echo "steviloVmesnihStolpcevPodvrstic: $steviloVmesnihStolpcevPodvrstic </br>";
		//echo "crossChk: $crossChk </br>";
		if($steviloVmesnihStolpcevPodvrstic > 1 && $podVrstice){	//ce je potrebno multicol prikazovanje
		//if($niSodo == 1 && $podVrstice && $steviloVmesnihStolpcevPodvrstic > 1){	//ce je potrebno multicol prikazovanje
			$tabela .= $this->MultiColCellLatex($colNum, $crossChk);
		}else{
			$tabela .= " & ";
			$tabela .= $crossChk;
		}
		return $tabela;
	}
	
	//function tableRow($arrayText, $brezHline=0, $brezNoveVrstice=0, $nadaljevanjeVrstice=0, $steviloPodstolpcev){
	//function tableRow($arrayText, $brezHline=0, $brezNoveVrstice=0, $nadaljevanjeVrstice=0, $color='', $export_format, $steviloPodstolpcev){
	function tableRow($arrayText=[], $brezHline=0, $brezNoveVrstice=0, $nadaljevanjeVrstice=0, $color='', $export_format='', $steviloPodstolpcev=[]){
		$tableRow = '';
		/*$linecount = $this->pdf->getNumLines($this->encodeText($arrayText[1]), 90);
		$linecount == 1 ? $height = 4.7 : $height = 4.7 + ($linecount-1)*3.3;*/
		$height = 1; //$height = $this->getCellHeight($this->encodeText($arrayText[1]), 90);
/* 		echo $arrayText[0]."</br>";
		echo $arrayText[1]."</br>";
		echo "brez hline: ".$brezHline."</br>"; */
		
		if($arrayParams['align2'] != 'C')
			$arrayParams['align2'] = 'L';
				//echo "velikost polja s tekstom: ".count($arrayText)."</br>";
		
		if($export_format == 'pdf'){		
			if($color=='blue'){
				//$cellBgColor = 'cyan';
				$cellBgColor = 'crta';
				$color = 'besedilo';
			}elseif($color=='red'){
				//$cellBgColor = 'pink';
				$cellBgColor = 'crtaGraf';
				$color = 'besedilo';
			}
			$cellColoring = ' \cellcolor{'.$cellBgColor.'} ';
		}else{	//drugace, ce je rtf
			$cellColoring = '';
			if($color=='blue'){				
				$color = 'cyan';	//v rtf pride modra
			}elseif($color=='red'){				
				//$color = 'green';	//v rtf pride cyan
				//$color = 'red';	//v rtf pride viola
				$color = 'yellow';	//v rtf pride rdece
			}
		}
		
		for($i=0;$i<count($arrayText);$i++){
			//echo "array text: ".$arrayText[$i]."</br>";
			
			####### koda, kjer sem testiral seqsplit za ureditev dolgih besed
/* 			if($arrayText[$i]==''){	//ce je prazen
				$arrayBesedilo = $arrayText[$i];
			}else{			
				##################### preveri, ali ima podatek, ki mora se pojaviti v celici na koncu presledek, to je pomembno za delovanje razbijanja dolgih besed v celici tabele oz. \seqsplit
				$zadnjiChar = substr($arrayText[$i], -1);
				if($zadnjiChar == ' '){
					$arrayText[$i] = substr($arrayText[$i], 0, -1);
				}
				#####################
				##################### ureditev specialnih znakov, da se jih da izpisati
				//$arrayText[$i] = $this->pripraviBesediloZaSeqsplit($arrayText[$i]);
				$arrayText[$i] = $this->pripraviBesediloZaSeqsplit($arrayText[$i]);
				#####################
				$arrayBesedilo = '\seqsplit{'.$arrayText[$i].'}';
			}	*/
			####### koda, kjer sem testiral seqsplit za ureditev dolgih besed - konec
			
			$arrayBesedilo = $arrayText[$i];
			
			if($color!=''){	//ce je potrebno besedilo dolocene barve				
				//$text = ' \cellcolor{'.$cellBgColor.'} '.$this->coloredTextLatex($color, $arrayText[$i]);
				//$text = $cellColoring.''.$this->coloredTextLatex($color, '\seqsplit{'.$arrayText[$i].'}');
				$text = $cellColoring.''.$this->coloredTextLatex($color, $arrayBesedilo);				
			}else{				
				//$text = $arrayText[$i];
				//$text = '\seqsplit{'.$arrayText[$i].'}';
				$text = $arrayBesedilo;
			}
			if($i==0&&!$nadaljevanjeVrstice&&!count($steviloPodstolpcev)){
				$tableRow .= $text;
			}
			elseif($i==0&&!$nadaljevanjeVrstice&&count($steviloPodstolpcev)){
				//$tableRow .= ' \multicolumn{'.$steviloPodstolpcev[$i].'}{c|}{ '.$text.' }';
				$tableRow .= ' \multicolumn{'.$steviloPodstolpcev[$i].'}{X|}{ '.$text.' }';
			}elseif(count($steviloPodstolpcev)){	//ce rabimo multicolumn
				//$tableRow .= ' & \multicolumn{'.$steviloPodstolpcev[$i].'}{c|}{ '.$text.' }';
				$tableRow .= ' & \multicolumn{'.$steviloPodstolpcev[$i].'}{X|}{ '.$text.' }';
			}
			else{
				$tableRow .= ' & '.$text;
			}
		}
		
		if(!$brezNoveVrstice){
			$tableRow .= $this->texNewLine;	/*nova vrstica*/
		}

		if (!$brezHline) {	//dodaj se horizontal line, ce je to potrebno (po navadi vse povsod razen npr. za tabelo s st. odklonom in povprecjem)
			//if($export_format != 'xls'){			
			if($this->export_format != 'xls'){
				$tableRow .= $this->horizontalLineTex; /*obroba*/
			}
		}
		
		//echo "Vrstica tabele: ".$tableRow."</br>";
		
		return $tableRow;
	}
	
	function getSteviloPodstolpcev($steviloPodstolpcevPolje=null){
		$steviloPodstolpcev = 0;
		foreach($steviloPodstolpcevPolje as $stevilo){
			$steviloPodstolpcev = $steviloPodstolpcev + $stevilo;
		}
		return $steviloPodstolpcev;
	}
	
	function AddEmptyCells($colspan=null){
		$tabela = '';
		if($colspan){	//ce imamo tudi horizontalne spremenljivke
			for($i=0;$i<$colspan;$i++){	//dodamo ustrezno stevilo praznih celic v ustezni vrstici
				$tabela .= ' & ';
			}
		}
		return $tabela;
	}
	
	function urediCrteTabele($indeksMultiRow=null, $colspan=null, $steviloStolpcevParameterTabular=null){
		$tabela = '';
		if (in_array(0, $indeksMultiRow)){	//ce v polju je 0, ce ne potrebujemo vse povsod crte
			for($j=0;$j<2;$j++){	//inicializacija indeksMultiRow, saj prva dva stolpca ne potrebujeta crt
				array_unshift($indeksMultiRow,0);	//dodaj na zacetku polja se 2 nicli
			}	
			$clinesPrvi = array();
			$clinesZadnji = array();
			$prviZabelezen = 0;
			foreach($indeksMultiRow as $indeks=>$vrednost){
				if($vrednost==1&&$prviZabelezen==0){
					$clinesPrvi[] = ($indeks+1);
					$prviZabelezen = 1;
				}elseif($vrednost==0&&$prviZabelezen==1){
					$clinesZadnji[] = ($indeks);
					$prviZabelezen = 0;
				}
			}			
			if(count($clinesPrvi)!=count($clinesZadnji)){	//ce ni istega stevila indeksov za cline
				$clinesZadnji[] = $steviloStolpcevParameterTabular;	//je zadnji indeks stevilo vseh stolpcev
			}			
			foreach($clinesPrvi as $indeksPrvi=>$clinePrvi){
				$tabela .= "\\cline{".$clinePrvi."-".$clinesZadnji[$indeksPrvi]."}";
			}			
		}else{
			$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";
		}
		return $tabela;
	}
	
	function displayDataCellLatex($vrsticaPodatki=null, $tableSettingsNumerus=null, $tableSettingsAvgVar=null, $tableSettingsDelezVar=null, $colspan=null, $steviloStolpcevParameterTabular=null, $export_format=''){
		$tabela = '';
		
		if($this->tableSettingsNumerus){							
			if($this->tableSettingsPercent||$this->tableSettingsAvgVar||$this->tableSettingsDelezVar){	//ce je potrebno izpisati se ostale vrstice izracunov
				$tabela .= $this->tableRow($vrsticaPodatki['numerus'][0],1);
				if($export_format != 'xls'){
					$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
				}
				$tabela .= $this->AddEmptyCells($colspan); //dodaj prazne celice
			}else{
				$tabela .= $this->tableRow($vrsticaPodatki['numerus'][0],1);
			}
		}
		if($this->tableSettingsPercent){
			if($this->tableSettingsAvgVar||$this->tableSettingsDelezVar){	//ce je potrebno izpisati se ostale vrstice izracunov
				$tabela .= $this->tableRow($vrsticaPodatki['percent'][0],1);
				if($export_format != 'xls'){
					$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
				}
				$tabela .= $this->AddEmptyCells($colspan); //dodaj prazne celice
			}else{
				$tabela .= $this->tableRow($vrsticaPodatki['percent'][0],1);
			}
		}
		if($this->tableSettingsAvgVar!= ''){
			$color = 'blue';			
			if($this->tableSettingsDelezVar){	//ce je potrebno izpisati se ostale vrstice izracunov				
				$tabela .= $this->tableRow($vrsticaPodatki['avg'][0],1,0,0,$color, $export_format);
				//$tabela .= $this->tableRow($vrsticaPodatki['avg'][0],1);
				if($export_format != 'xls'){
					$tabela .= "\\cline{".($colspan+1)."-".$steviloStolpcevParameterTabular."}";	//prekinjena horizontalna vrstica
				}
				$tabela .= $this->AddEmptyCells($colspan); //dodaj prazne celice
			}else{				
				$tabela .= $this->tableRow($vrsticaPodatki['avg'][0],1,0,0,$color, $export_format);
				//$tabela .= $this->tableRow($vrsticaPodatki['avg'][0],1);
			}
		}
		if($this->tableSettingsDelezVar!= ''){
			$color = 'red';			
			$tabela .= $this->tableRow($vrsticaPodatki['delez'][0],1,0,0,$color, $export_format);
			//$tabela .= $this->tableRow($vrsticaPodatki['delez'][0],1);
		}
		
		return $tabela;
	}
	
	function coloredTextLatex($color='', $text=''){		
		$coloredText = '';
		$coloredText .=	'\textcolor{'.$color.'}{'.$text.'}';
		return 	$coloredText;	
	}
	
	/** Izriše vrstico z opisnimi z Latex
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	function displayDescriptivesSpremenljivkaRow($spid=null,$spremenljivka=null,$show_enota=null,$_sequence = null) {
		global $lang;
		//echo "funkcija displayDescriptivesSpremenljivkaRow: ".$spremenljivka['variable']." </br>";
		$texDisplayDescriptivesSpremenljivkaRow = '';
		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		$text = array();		
		$text[] = '\textbf{'.$this->encodeText($spremenljivka['variable']).'}';
		$text[] = '\textbf{'.$this->encodeText($spremenljivka['naslov']).'}';
		
		#veljavno
		$text[] = $this->encodeText((!$show_enota ? (int)$_desc['validCnt'] : ''));
		
		#ustrezno
		$text[] = $this->encodeText((!$show_enota ? (int)$_desc['allCnt'] : ''));
		
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
			$text[] = $this->encodeText(self::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		else
			$text[] = $this->encodeText('');
			
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
			$text[] = $this->encodeText(self::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = $this->encodeText('');
		
		//$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		//$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');
		$text[] = (int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '';		
		$text[] = (int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '';
		
		
		//$texDisplayDescriptivesSpremenljivkaRow .= $this->descTableRow($text);
		$texDisplayDescriptivesSpremenljivkaRow .= $this->tableRow($text);
		
		//echo "tex iz funkcije displayDescriptivesSpremenljivkaRow: ".$texDisplayDescriptivesSpremenljivkaRow."</br>";
		return $texDisplayDescriptivesSpremenljivkaRow;
	}
	
	/** Izriše vrstico z opisnimi
	 * 
	 * @param unknown_type $spremenljivka
	 * @param unknown_type $variable
	 */
	function displayDescriptivesVariablaRow($spremenljivka=null, $grid=null, $variable=null) {
		global $lang;
		//echo "funkcija displayDescriptivesVariablaRow: ".$spremenljivka['variable']." </br>";
		$texDescriptivesVariablaRow = '';
		$_sequence = $variable['sequence'];	# id kolone z podatki
		if ($_sequence != null) {
			$_desc = SurveyAnalysis::$_DESCRIPTIVES[$_sequence];
		}
		
		$text = array();
			
		$text[] = $this->encodeText($variable['variable']);
		$text[] = $this->encodeText($variable['naslov']);
		
		#veljavno
		$text[] = $this->encodeText((int)$_desc['validCnt']);
		
		#ustrezno
		$text[] = $this->encodeText((int)$_desc['allCnt']);
		
		//if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1)
		//if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1 && $spremenljivka['tip'] != 16)
		if (isset($_desc['avg']) && (int)$spremenljivka['skala'] !== 1 && ($spremenljivka['tip'] != 16 && $spremenljivka['tip'] != 2))
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
			$text[] = $this->encodeText(self::formatNumber($_desc['avg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
		//else if (isset($_desc['avg']) && $spremenljivka['tip'] == 2 && (int)$spremenljivka['skala'] == 1 )
		else if (isset($_desc['avg']) && $spremenljivka['tip'] == 2 && (int)$spremenljivka['skala'] !== 1 && ($spremenljivka['tip'] != 16))
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%'));
			$text[] = $this->encodeText(self::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%'));
		else
			$text[] = $this->encodeText('');
			
		//if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1)
		//if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1 && $spremenljivka['tip'] != 16)
		if (isset($_desc['div']) && (int)$spremenljivka['skala'] !== 1 && ($spremenljivka['tip'] != 16 && $spremenljivka['tip'] != 2))
			//$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
			$text[] = $this->encodeText(self::formatNumber($_desc['div'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
		else
			$text[] = $this->encodeText('');
		
		//if ((int)$spremenljivka['skala'] !== 1 && $spremenljivka['tip'] != 16){
		if ((int)$spremenljivka['skala'] !== 1 && ($spremenljivka['tip'] != 16 && $spremenljivka['tip'] != 2)){
			$text[] = $this->encodeText($_desc['min']);
			$text[] = $this->encodeText($_desc['max']);
		}else{
			$text[] = $this->encodeText('');
			$text[] = $this->encodeText('');
		}

		//$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['min'] : '');
		//$text[] = $this->encodeText((int)$spremenljivka['skala'] !== 1 ? $_desc['max'] : '');

				
		$texDescriptivesVariablaRow .= $this->tableRow($text);
		//echo "nekaj: ".$this->encodeText(self::formatNumber($_desc['avg']*100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'&nbsp;%'))."  ".$spremenljivka['tip']."</br>";
		//echo "tex iz funkcije displayDescriptivesVariablaRow: ".$texDescriptivesVariablaRow."</br>";
		return $texDescriptivesVariablaRow;		
	}
	
	function returnBold($text=''){
		$boldedText = '';
		$boldedText .= '\textbf{'.$text.'}';
		return $boldedText;
	}
	
	function returnCentered($text='', $export_format = ''){
		//echo "$export_format </br>";
		$centeredText = '';
		if($export_format == 'pdf'){		
			$centeredText .= ' \begin{absolutelynopagebreak} ';
		}
		$centeredText .= '\begin{center}{'.$text.'} \end{center}';
		if($export_format == 'pdf'){			
			$centeredText .= ' \end{absolutelynopagebreak} ';
		}
		//$centeredText .= ' \begin{absolutelynopagebreak} \begin{center}{'.$text.'} \end{center} \end{absolutelynopagebreak} ';
		
		return $centeredText;
	}
	
	function GetSprId($spid=null){
		$find = '_';
		$findPos = strpos($spid, $find);
		$sprId = '';		
		$sprId = substr_replace($spid,'',$findPos);		
		return $sprId;
	}
	
	function displayHeatmapImageLatex($sprId=null){
		global $site_path;
		$tex = '';
		$this->path2HeatmapImages = $site_path.'main/survey/uploads/';
		
		//$heatmapImageFileName = $site_url.'main/survey/uploads/heatmap'.$sprId.'.png';
		$heatmapImageFileName = 'heatmap'.$sprId;	
		$tex .= '\includegraphics[scale=0.5]{'.$this->path2HeatmapImages.''.$heatmapImageFileName.'}';		
		return $tex;
	}

	//funkcija, ki okrog posebnih crk dodaja {}, da lahko knjiznica seqsplit lahko deluje
	function pripraviBesediloZaSeqsplit($besedilo=''){		
		//najdi posebno crko in okoli nje dodaj {}	
		//echo "array text: ".$besedilo."</br>";
		$chars = array('č', 'ć', 'ž', 'š', 'đ', 'Č', 'Ć', 'Ž', 'Š', 'Đ');	//polje s najbolj pogostimi posebnimi crkami, ki jih seqsplit ne sprejema
		foreach($chars AS $char){	//za vsako posebno crko, uredi {}				
			$moreChars = 0;
			$pozicijaChar = '';
			$pozicijaChar = strpos($besedilo, $char);	//najdi pozicijo posebne crke			
			if(is_numeric($pozicijaChar)){	//ce je prisotna posebna crka v besedilu
			
				//echo "črka: ".$char."</br>";
				//echo "pozicija črke: ".$pozicijaChar."</br>";
				$textToChar = substr($besedilo, 0, $pozicijaChar);	//tekst do posebne crke
				//echo $textToChar."</br>";
				$tmpTextFromChar = substr($besedilo, $pozicijaChar);	//tekst po posebne crke posebno crko
				//echo $tmpTextFromChar."</br>";
				$textFromChar = substr($tmpTextFromChar, 2);	//tekst po posebni crki dalje
				//echo $textFromChar."</br>";				
				
				//$besediloTmp = $textToChar."{".$char."}".$textFromChar;				
				$besedilo = $textToChar."{".$char."}".$textFromChar;				
				$besediloTmp = $textToChar."{".$char."}";				
 				//echo "besedilo: ".$besedilo."</br>";
				//$besedilo = $besediloTmp;
				
				do{
					//ce je prisotna se kaksna posebna crka v drugem delu besedila, ponovi
					$pozicijaChar = '';
					$pozicijaChar = strpos($textFromChar, $char);	//najdi pozicijo posebne crke v ostalem delu besedila
					if(is_numeric($pozicijaChar)){	//ce je prisotna posebna crka v besedilu v ostalem delu besedila
						$moreChars = 1;
						$textToChar = substr($textFromChar, 0, $pozicijaChar);	//tekst do posebne crke
						$tmpTextFromChar = substr($textFromChar, $pozicijaChar);	//tekst po posebne crke posebno crko
						$textFromChar = substr($tmpTextFromChar, 2);	//tekst po posebni crki dalje									
						$besediloTmp .= $textToChar."{".$char."}";
					}else{
						$moreChars = 0;
						$besediloTmp .= $textFromChar;
					}					
					//echo "moreChars: ".$moreChars."</br>";
					$besedilo = $besediloTmp;
				}while($moreChars == 1);
				
			}
		}
		//echo "besedilo končno: ".$besedilo."</br>";
		return $besedilo;
	}
	//funkcija, ki okrog posebnih crk dodaja {}, da lahko knjiznica seqsplit lahko deluje - konec
	
	/*Moje funkcije - konec*/
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text='', $length=64, $tail="...")
	{
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
	
	function formatNumber($value=null, $digit=0, $sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
		return $result;
	}
	
	function getBrezHline($exportformat=''){		
		if($exportformat=='xls'){
			$brezHline = 1;
		}else{
			$brezHline = 0;
		}
		return $brezHline;	
	}

}