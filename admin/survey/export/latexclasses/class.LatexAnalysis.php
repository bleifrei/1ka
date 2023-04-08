<?php

/**
 *
 *	Class ki skrbi za izris porocila analiz v latex
 *
 *
 */


//include('../../function.php');
include('../../vendor/autoload.php');
include_once('../../function.php');
include_once('../survey/definition.php');

 
class LatexAnalysis{
	
	protected $anketa;
	protected $export_format;
	protected $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	protected $pdf;
	protected $currentStyle;
	protected $spremenljivka = null;
	
	private $headFileName = null;					# pot do header fajla
	
	protected $current_loop = 'undefined';
	
	protected $texNewLine = '\\\\ ';
	protected $texBigSkip = '\bigskip';
	
	
	function __construct($anketa=null, $export_format='', $sprID = null){
		global $site_path, $global_user_id, $admin_type, $lang;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){
		
			$this->anketa['id'] = $anketa;
			
			$this->spremenljivka = $sprID;
			
			$this->export_format = $export_format;
			//echo 'To je tip analysis za anketo: '.$anketa.' za '.$this->export_format.'</br>';
			
			SurveyAnalysis::Init($this->anketa['id']);
			SurveyAnalysis::$setUpJSAnaliza = false;
						
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa['id']);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
			
			$loop = SurveyZankaProfiles :: Init($this->anketa, $global_user_id);

			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
		}else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) ){
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}else{
			return false;
		}
			
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;		
	}
	
		
	public function displayAnalysis($export_subtype=''){
		global $lang;
		$tex = '';

		// Pripravimo podatke, ki se uporabijo v tabelah		
		# preberemo header
		if ($this->headFileName !== null) {
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			//SurveyMissingProfiles :: Init(self::$sid,$global_user_id);
			SurveyMissingProfiles :: Init($this->spremenljivka,$this->anketa['uid']);
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
			//echo "SurveyAnalysis::missingProfileData in displayAnalysis: ".SurveyAnalysis::$missingProfileData." </br>";
			//echo "Indeksi SurveyAnalysis::missingProfileData v displayAnalysis: ".print_r(array_keys(SurveyAnalysis::$missingProfileData))." </br>";

			//echo "display_mv_type in displayAnalysis: ".SurveyAnalysis::$missingProfileData['display_mv_type']." </br>";
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();

			#preberemo HEADERS iz datoteke
			SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
			//echo $this->headFileName."</br>";

			# polovimo frekvence
			SurveyAnalysis::getFrequencys();

			#odstranimo sistemske variable
			SurveyAnalysis::removeSystemVariables();

			//echo in_array($this->$spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES );
			
			//$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
			
			//echo 'sprID: '.$_GET['sprID'].'</br>';
			//echo 'export_subtype: '.$export_subtype.'</br>';
			
			/*Izpis naslova izvoza*/
			switch ( $export_subtype ){
				case 'sums':
					$naslovIzvoza = $lang['export_analisys_sums'];
				break;
				case 'freq':
					$naslovIzvoza = $lang['export_analisys_freq'];
				break;
				case 'desc':
					$naslovIzvoza = $lang['export_analisys_desc'];
				break;
				case 'chart':
					$naslovIzvoza = $lang['export_analisys_charts'];
				break;
				case 'crosstab':
					$naslovIzvoza = $lang['export_analisys_crosstabs'];
				break;
				case 'multicrosstab':
					$naslovIzvoza = $lang['export_analisys_multicrosstabs'];
				break;
				case 'mean':
					$naslovIzvoza = $lang['export_analisys_means'];
				break;
				case 'ttest':
					$naslovIzvoza = $lang['export_analisys_ttest'];
				break;
				case 'break':
					$naslovIzvoza = $lang['export_analisys_break'];
				break;
				case 'heatmap_image_pdf':
					$naslovIzvoza = $lang['export_analysis_heatmap_image'];
				break;
			}			
			
			if($export_subtype!='creport'){
				//$tex .= '\textbf{'.$naslovIzvoza.'}'.$this->texBigSkip.$this->texNewLine;
				$tex .= '\MakeUppercase{\huge \textbf{'.$naslovIzvoza.'}}'.$this->texBigSkip.$this->texNewLine;	//{\\huge {'.$imeAnkete.'} \\par}
			}

			
			/*Izpis naslova izvoza - konec*/
			
			//if($this->export_format == 'pdf'){
			if($this->export_format == 'pdf'&&$export_subtype!='creport'){
				$tex .= '\begin{tableAnalysis}';	/*zacetek environmenta z manjsim fontom*/
			}

 			switch ($export_subtype){
				case 'sums':
				case 'freq':
					foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
						$analysisTable = new LatexAnalysisElement($this->anketa, $spremenljivka, $this->export_format, 0, $spid, $this->headFileName, $export_subtype);
						//if(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]){							
						if (($spremenljivka['tip'] != 'm'
						 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
						 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
						 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ){
							//echo "spremenljivka tip : ".$spremenljivka['tip']."</br>";
 							/*echo "spr_id: ".$this->spremenljivka."</br>";
							echo "spid : ".$spid."</br>"; */
							# če nimamo zank
							if(count(SurveyAnalysis::$_LOOPS) == 0){
								$tex .= $analysisTable->displayTablesLatex($spid, $this->export_format);
							}
							else{
								// izrisemo samo eno tabelo iz enega loopa
								if($this->current_loop > 0){
									
									$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
									$loop['cnt'] = $this->current_loop;
									SurveyAnalysis::$_CURRENT_LOOP = $loop;
									
									// Izpisemo naslov zanke za skupino
									$tex .= $analysisTable->displayTablesLatex($spid, $this->export_format);
								}
								// Izrisemo vse tabele spremenljivka (iz vseh loopov)
								else{
									$loop_cnt = 0;
									# če mamo zanke
									foreach(SurveyAnalysis::$_LOOPS AS $loop) {
										$loop_cnt++;
										$loop['cnt'] = $loop_cnt;
										SurveyAnalysis::$_CURRENT_LOOP = $loop;
										$tex .= $analysisTable->displayTablesLatex($spid, $this->export_format);
									}
								}
							}
						}					
					} // end foreach SurveyAnalysis::$_HEADERS
				break;
				case 'desc':
					//$analysisTable = new LatexAnalysisElement($this->anketa, $spremenljivka, $this->export_format, 0, $spid, $this->headFileName, $export_subtype);
					
 					$analysisTable = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
						
						# če nimamo zank
						if(count(SurveyAnalysis::$_LOOPS) == 0){
							$tex .= $analysisTable->displayTablesLatex(0, $this->export_format);
						}
						else{
							// izrisemo samo eno tabelo iz enega loopa
							if($this->current_loop > 0){
								
								$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
								$loop['cnt'] = $this->current_loop;
								SurveyAnalysis::$_CURRENT_LOOP = $loop;
								
								// Izpisemo naslov zanke za skupino
								$tex .= $analysisTable->displayTablesLatex(0, $this->export_format);
							}
							// Izrisemo vse tabele spremenljivka (iz vseh loopov)
							else{
								$loop_cnt = 0;
								# če mamo zanke
								foreach(SurveyAnalysis::$_LOOPS AS $loop) {
									$loop_cnt++;
									$loop['cnt'] = $loop_cnt;
									SurveyAnalysis::$_CURRENT_LOOP = $loop;
									$tex .= $analysisTable->displayTablesLatex(0, $this->export_format);
								}
							}
						}
					
				break;
				case 'crosstab':
					$crossData1 = explode(",", $_GET['data1']);
					$crossData2 = explode(",", $_GET['data2']);					
					$analysisTable = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisTable->displayCrosstabsTablesLatex($crossData1, $crossData2);
				break;
				case 'multicrosstab':
					$analysisTable = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisTable->displayMultiCrosstabsTablesLatex();
				break;
				case 'mean':
					$analysisTable = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisTable->displayMeanTablesLatex();					
				break;
				case 'ttest':
					$analysisTable = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisTable->displayTTestTablesLatex();
				break;
				case 'break':
					$analysisTable = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisTable->displayBreakTablesLatex();
				break;
				case 'heatmap_image_pdf':
					$analysisHeatmapImage = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisHeatmapImage->displayHeatmapImageLatex($_GET['sprID']);
				break;
				case 'chart':
					$analysisChart = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisChart->displayChartLatex($_GET['sprID']);
				break;
				case 'creport':
					$analysisCreport = new LatexAnalysisElement($this->anketa, 0, $this->export_format, 0, 0, $this->headFileName, $export_subtype);
					$tex .= $analysisCreport->displayCreportLatex();
				break;
			}
			
			
			//if($this->export_format == 'pdf'){
			if($this->export_format == 'pdf'&&$export_subtype!='creport'){
				$tex .= '\end{tableAnalysis}';	/*zakljucek environmenta z manjsim fontom*/
			}
			
		} // end if else ($_headFileName == null)
		return $tex;
		// Loop cez vsa vprasanja
		// Znotraj loopa vsak element posebej izrisemo kot objekt LatexFreqElement - pomembno, ker zelimo recimo posamezno tabelo frekvenc (sa specificno vprasanje) izrisati tudi v kaksnem drugem porocilu (npr custom report). Zato se mora vsak element neodvisno izrisovati.
	}
}