<?php
/**
* @author 	Peter Hrvatin
* @date		April 2012
*/

define("SAA_FOLDER", "AnalysisArchive");
	
class SurveyTableChart {
	
	public $anketa;									# id ankete
	public $folder = '';							# pot do folderja
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $SDF = null;							# class za inkrementalno dodajanje fajlov
	
	public $uid;									# id userja
	
	private $classInstance;			// instanca razreda (crosstabs, ttest...) z vsemi podatki
	private $podstran;				// podstran iz katere kreimramo graf (tip grafa -> crosstab, ttest, povprecje)
	private $counter;				// kateri graf po vrsti izrisemo (ce jih je vec)
	private $crossCheck = false;	// ce imamo odvisno spremenljivko pri crosstabu checkbox jo obravnavamo posebej

	public $skin = '1ka';	# nastavitev skina za grafe
	public $fontSize = 8;		# velikost fonta v grafih
	public $quality = 1;		# kvaliteta (sirina) slike (1 -> 800px, 2 -> 1600px)
	
	public $numerusText = '';	// dodaten text pri numerusu (veljavni, navedbe)
	
	private $crosstabVars;		// kateri graf po vrsti izrisemo (ce jih je vec)
	
	public $break_forSpr;				// break neodvisna spremenljivka
	public $break_frequencys;			// break izracunane frekvence
	public $break_spremenljivka;		// break odvisna spremenljivka
	public $break_crosstab = 0;				// break crosstab tabela
	
	public $settings = array();			// nastavitve grafa
	public $settings_mode=0;			// zavihek nastavitev (osnovno/napredno)
	
	public $returnChartAsHtml = false;					# ali vrne rezultat analiz kot html ali ga izpiše
	public $isArchive = false;							# nastavimo na true če smo v arhivu
	public $chartArchiveTime = '';						# unikatnost
	
	private $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
	/**
	* Konsturktor
	* 
	* @param int $anketa
	*/
	function __construct($anketa, $classInstance=null, $podstran='crosstab', $counter=0) {
		global $global_user_id, $site_path;

		$this->folder = $site_path . EXPORT_FOLDER.'/';

		$this->anketa = $anketa;

		if ((int)$this->anketa > 0) { 	# če je poadan anketa ID					
			
			$this->classInstance = $classInstance;
			$this->podstran = $podstran;
			$this->counter = $counter;

			#inicializiramo SurveyAnalasys
			SurveyAnalysis::Init($this->anketa);
			//SurveyAnalysis::$setUpJSAnaliza = false;

			#inicializiramo class za datoteke
			$this->SDF = SurveyDataFile::get_instance();
			$this->SDF->init($this->anketa);
			$this->headFileName = $this->SDF->getHeaderFileName();
			$this->dataFileName = $this->SDF->getDataFileName();
			$this->dataFileStatus = $this->SDF->getStatus();
			
			if ($this->dataFileStatus == FILE_STATUS_NO_DATA 
				|| $this->dataFileStatus == FILE_STATUS_NO_FILE
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED){
				exit;
    			return false;
    		}
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
		
			#preberemo HEADERS iz datoteke
			SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
			
			# odstranimo sistemske variable tipa email, ime, priimek, geslo
			SurveyAnalysis::removeSystemVariables();
			
			# polovimo frekvence			
			SurveyAnalysis::getFrequencys();
			
			// preberemo nastavitve iz baze (prej v sessionu) 
			SurveyUserSession::Init($this->anketa);
			$this->sessionData = SurveyUserSession::getData();
		} 
		else {
			//die("Napaka!");
		}
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa))
		{
			$this->uid = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa, $this->uid);
		}
		

		$this->skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');
		$this->fontSize = SurveyDataSettingProfiles :: getSetting('chartFontSize');
		$this->quality = (isset($this->sessionData['charts']['hq']) && $this->sessionData['charts']['hq'] == 1) ? 3 : 1;
	}
	
	
	function display(){
		global $site_path;
		global $lang;

		$chartID = $this->getChartID();

		switch($this->podstran){
			
			case 'crosstab':
				echo '<div class="crosstab_chart_holder tableChart" id="tableChart_'.$chartID.'">';
				$this->displayCrosstabChart($chartID);
				echo '</div>';
								
				break;
				
			case 'ttest':
				echo '<div class="ttest_chart_holder tableChart" id="tableChart_'.$chartID.'">';
				$this->displayTTestChart($chartID);
				echo '</div>';
	
				break;
			
			case 'mean':
				echo '<div class="mean_chart_holder tableChart" id="tableChart_'.$chartID.'">';
				$this->displayMeanChart($chartID);
				echo '</div>';
				
				break;
				
			case 'break':
				echo '<div class="break_chart_holder tableChart" id="tableChart_'.$chartID.'">';
				$this->displayBreakChart($chartID);
				echo '</div>';
				
				break;		
		}		
	}
	

	// Izrisemo graf v crosstabih
	function displayCrosstabChart($chartID){
		global $site_path;
		global $lang;

		// preverimo ce imamo checkbox v odvisni spr - imamo posebne nastavitve
		if (count($this->classInstance->variabla2) > 0) {
			foreach ($this->classInstance->variabla2 AS $key => $var) {
				$spr_tip = $this->classInstance->_HEADERS[$var['spr']]['tip'];
				if ( $spr_tip == 2 || $spr_tip == 16 ) {
						$this->crossCheck = true;
				}
			}
		}
		if (count($this->classInstance->variabla1) > 0 && $is_check == false ) { # če še ni bil checkbox
			foreach ($this->classInstance->variabla1 AS $key => $var) {
				$spr_tip = $this->classInstance->_HEADERS[$var['spr']]['tip'];
				if ( $spr_tip == 2 || $spr_tip == 16 ) {
						$this->crossCheck = true;
				}
			}
		}
		// zaenkrat ne upoastevamo
		$this->crossCheck = false;
		
		// defult nastavitve posameznega grafa
		if(isset($this->sessionData['crosstab_charts'][$chartID]))
			$this->settings = $this->sessionData['crosstab_charts'][$chartID];
		else		
			$this->settings = $this->getDefaultSettings();
		
		
		// Napolnimo podatke za graf
		$DataSet = $this->getCrosstabDataSet($chartID, $this->settings);


		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = $this->generateChartId($chartID, $this->settings, $DataSet->GetNumerus());

		// Ce se nimamo zgeneriranega grafa
		if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
			
			switch($this->settings['type']){
				
				// Sestavljeni stolpec - horizontalen
				case 0:
					$Test = $this->createHorStructBars($DataSet);
				break;
				
				// Sestavljeni stolpec - vertikalen
				case 1:
					$Test = $this->createVerStructBars($DataSet);
				break;
				
				// Horizontalni stolpci
				case 3:
					$Test = $this->createHorBars($DataSet, 1);
				break;
				
				// Navpicni stolpci
				case 4:
					$Test = $this->createVerBars($DataSet, 1);
				break;
				
				// Pie chart
				case 2:
					$Test = $this->createPie($DataSet, $this->settings['show_legend']);
				break;
				
				// Pie chart
				case 5:
					$Test = $this->create3DPie($DataSet, $this->settings['show_legend']);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}

		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());

		if ($this->isArchive == false ) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.$this->anketa.'_'.$this->chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		} 
		
		// zapisemo ime slike v session za izvoze
		$this->settings['name'] = $imgName;
		$this->sessionData['crosstab_charts'][$chartID] = $this->settings;
		
		// Zapisemo se variable v session
		$this->sessionData['crosstab_charts'][$chartID]['spr1'] = $this->classInstance->variabla1[0];
		$this->sessionData['crosstab_charts'][$chartID]['spr2'] = $this->classInstance->variabla2[0];
		
		// Naslov posameznega grafa
		echo '<div class="chart_title">';
		
		if($this->settings['type'] == 1 || $this->settings['type'] == 4){
			$title = '<table><tr>';
			$title .= '<td style="width:380px;">'.$this->crosstabVars[0] . '</td><td style="width:40px;"> / </td><td style="width:380px;">' . $this->crosstabVars[1].'</td>';
			$title .= '</tr></table>';
		}
		else{
			$title = $this->crosstabVars[0];
		}
		echo $title;
		
		echo '</div>';
		
		echo '<div class="chart_img" id="chart_img_'.$chartID.'" onclick="tableChartAdvancedSettings(\''.$chartID.'\', \'crosstab\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgPath.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		$this->displaySingleSettings($chartID, $this->settings);
		echo '</div>';
		
		// Zvezdica za vkljucitev v porocilo
		$variables1 = $this->classInstance->getSelectedVariables(2);
		$variables2 = $this->classInstance->getSelectedVariables(1);
		$counter = 0;
		$var1 = array();
		$var2 = array();
		foreach ($variables1 AS $v_first) {
			foreach ($variables2 AS $v_second) {
				if($counter == $this->counter){
					$var1 = $v_first;
					$var2 = $v_second;
					
					break 2;
				}
				else
					$counter++;
			}
		}
		
		$spr2 = $var1['seq'].'-'.$var1['spr'].'-'.$var1['grd'];
		$spr1 = $var2['seq'].'-'.$var2['spr'].'-'.$var2['grd'];
		SurveyAnalysis::addCustomReportElement($type=5, $sub_type=1, $spr1, $spr2);
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	// Izrisemo graf v ttestu
	function displayTTestChart($chartID){
		global $site_path;
		global $lang;
		
		// defult nastavitve posameznega grafa
		if(isset($this->sessionData['ttest_charts'][$chartID]))
			$this->settings = $this->sessionData['ttest_charts'][$chartID];
		else		
			$this->settings = $this->getDefaultSettings();
		
		
		// Napolnimo podatke za graf
		$DataSet = $this->getTTestDataSet($chartID, $this->settings);


		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = $this->generateChartId($chartID, $this->settings, $DataSet->GetNumerus());

		// Ce se nimamo zgeneriranega grafa
		if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
			
			switch($this->settings['type']){
								
				// Horizontalni stolpci
				case 0:
					$Test = $this->createHorBars($DataSet, $legend=0);
				break;			
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		
		if ($this->isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.$this->anketa.'_'.$this->chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		} 
		
		// zapisemo ime slike v session za izvoze
		$this->settings['name'] = $imgName;
		$this->sessionData['ttest_charts'][$chartID] = $this->settings;

		// Naslov posameznega grafa
		echo '<div class="chart_title">';	
		$title = $lang['srv_chart_ttest_title'].':<br />';
		$title .= '<table><tr>';
		$title .= '<td style="width:380px; text-align: right;">'.$this->crosstabVars[0] . '</td><td style="width:40px;"> / </td><td style="width:380px; text-align: left;">' . $this->crosstabVars[1].'</td>';
		$title .= '</tr></table>';
		echo $title;		
		echo '</div>';
		
		echo '<div class="chart_img" id="chart_img_'.$chartID.'" onclick="tableChartAdvancedSettings(\''.$chartID.'\', \'ttest\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgPath.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		$this->displaySingleSettings($chartID, $this->settings);
		echo '</div>';
		
		// Zvezdica za vkljucitev v porocilo
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
		$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
		
		$spid2 = $this->sessionData['ttest']['spr2'];
		$seq2 = $this->sessionData['ttest']['seq2'];
		$grid2 = $this->sessionData['ttest']['grid2'];
		
		$spr1 = $seq2.'-'.$spid2.'-'.$grid2.'-'.$sub1.'-'.$sub2;
		$spr2 = $seq1.'-'.$spid1.'-'.$grid1;
		SurveyAnalysis::addCustomReportElement($type=7, $sub_type=1, $spr1, $spr2);
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	// Klicemo pri pdf/rtf izpisih da se pravilno nastavi session
	function setTTestChartSession(){
		
		// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
		$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
		$chartID = $sub1.'_'.$sub2.'_'.$spid1.'_'.$seq1.'_'.$grid1;
				
		// defult nastavitve posameznega grafa
		if(isset($this->sessionData['ttest_charts'][$chartID]))
			$this->settings = $this->sessionData['ttest_charts'][$chartID];
		else		
			$this->settings = $this->getDefaultSettings();
				
		// Napolnimo podatke za graf
		$DataSet = $this->getTTestDataSet($chartID, $this->settings);

		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = $this->generateChartId($chartID, $this->settings, $DataSet->GetNumerus());
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		
		// zapisemo ime slike v session za izvoze
		$this->settings['name'] = $imgName;
		$this->sessionData['ttest_charts'][$chartID] = $this->settings;
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	// Izrisemo graf v povprecjih
	function displayMeanChart($chartID){
		global $site_path;
		global $lang;	
		
		// defult nastavitve posameznega grafa
		if(isset($this->sessionData['mean_charts'][$chartID]))
			$this->settings = $this->sessionData['mean_charts'][$chartID];
		else		
			$this->settings = $this->getDefaultSettings();
		
		
		// Napolnimo podatke za graf
		$DataSet = $this->getMeanDataSet($chartID, $this->settings);


		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = $this->generateChartId($chartID, $this->settings, $DataSet->GetNumerus());

		// Ce se nimamo zgeneriranega grafa
		if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
			
			switch($this->settings['type']){
								
				// Povprecja - horizontalni stolpci
				case 0:
					$Test = $this->createHorBars($DataSet, $legend=0, $fixedScale=1);
				break;
				
				// Povprecja - radar
				case 1:
					$Test = $this->createRadar($DataSet, 1, $fixedScale=1);
				break;
				
				// Povprecja - vertikalna crta
				case 2:
					$Test = $this->createVerLine($DataSet, $legend=0, $fixedScale=1);
				break;				
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		
		if ($this->isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.$this->anketa.'_'.$this->chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		} 
		
		// zapisemo ime slike v session za izvoze
		$this->settings['name'] = $imgName;
		$this->sessionData['mean_charts'][$chartID] = $this->settings;

		// Naslov posameznega grafa
		$title = '';
		echo '<div class="chart_title">';
		echo $title;
		echo '</div>';
		
		echo '<div class="chart_img" id="chart_img_'.$chartID.'" onclick="tableChartAdvancedSettings(\''.$chartID.'\', \'mean\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgPath.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		$this->displaySingleSettings($chartID, $this->settings);
		echo '</div>';
		
		// Zvezdica za vkljucitev v porocilo
		$variables1 = $this->classInstance->getSelectedVariables(1);
		$variables2 = $this->classInstance->getSelectedVariables(2);
			
		$pos1 = floor($this->counter / count($variables2));
		$pos2 = $this->counter % count($variables2);
			
		$spr1 = $variables1[$pos1]['seq'].'-'.$variables1[$pos1]['spr'].'-'.$variables1[$pos1]['grd'];
		$spr2 = $variables2[$pos2]['seq'].'-'.$variables2[$pos2]['spr'].'-'.$variables2[$pos2]['grd'];
		SurveyAnalysis::addCustomReportElement($type=6, $sub_type=1, $spr1, $spr2);
		
		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
	}
	
	// Izrisemo graf v breaku
	function displayBreakChart($chartID){
		global $site_path;
		global $lang;

		$tip = $this->break_spremenljivka['tip'];
		$skala = $this->break_spremenljivka['skala'];

		// Izrisemo poseben break graf (multigrid, multicheckbox, multitext, multinumber)
		if( in_array($tip, array(7,17,18,20,)) || ($tip == 6 && $skala == 0) ){
			
			// defult nastavitve posameznega grafa
			if(isset($this->sessionData['break_charts'][$chartID]))
				$this->settings = $this->sessionData['break_charts'][$chartID];
			else
				$this->settings = $this->getDefaultSettings();
			
			
			// Pri number ne dovolimo radarja in ne sortiranja po kategorijah
			if($tip == 7){
				if($this->settings['type'] == 0)
					$this->settings['type'] = 1;
					
				if($this->settings['sort'] == 1)
					$this->settings['sort'] = 0;
			}
			
			
			// Napolnimo podatke za graf
			$DataSet = $this->getBreakDataSet($chartID, $this->settings);


			// Cache
			$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
			
			$ID = $this->generateChartId($chartID, $this->settings, $DataSet->GetNumerus());

			// Ce se nimamo zgeneriranega grafa
			if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
				
				switch($this->settings['type']){
					
					// Povprecja - radar
					case 0:
						$Test = $this->createRadar($DataSet, 1);
					break;
					
					// Povprecja - vertikalni stolpci
					case 1:
						$Test = $this->createVerBars($DataSet, 1);
					break;
					
					// Povprecja - horizontalni stolpci
					case 2:
						$Test = $this->createHorBars($DataSet, 1);
					break;
					
					// Povprecja - linijski graf
					case 3:
						$Test = $this->createLine($DataSet, 1);
					break;
				}	
				
				// Shranimo v cache
				$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
			}
		
			// dobimo ime slike c cache-u
			$imgName = $Cache->GetHash($ID,$DataSet->GetData());
			
			if ($this->isArchive == false) {
				$imgPath = 'pChart/Cache/'.$imgName;
			} else {
				$imgPath = SAA_FOLDER.'/pChart/'.$this->anketa.'_'.$this->chartArchiveTime.'_'.$imgName;
				copy('pChart/Cache/'.$imgName, $imgPath);
			} 
			
			// zapisemo ime slike v session za izvoze
			$this->settings['name'] = $imgName;
			$this->sessionData['break_charts'][$chartID] = $this->settings;
			
			// Zapisemo se variable v session
			$this->sessionData['break_charts'][$chartID]['forSpr'] = $this->break_forSpr;
			$this->sessionData['break_charts'][$chartID]['frequencys'] = $this->break_frequencys;
			$this->sessionData['break_charts'][$chartID]['spremenljivka'] = $this->break_spremenljivka;

			
			// Naslov posameznega grafa
			echo '<div class="chart_title">';
			
			//var_dump($this->break_spremenljivka);
			$title = $this->break_spremenljivka['naslov'] . ' ('.$this->break_spremenljivka['variable'].')';
			
			if($tip == 20){
			
				$gkey = $this->break_spremenljivka['break_sub_table']['key'];			
				$grid = $this->break_spremenljivka['grids'][$gkey];
				$subtitle = $grid['naslov'] . ' ('.$grid['variable'].')';
			
				$title .= '<br />'.$subtitle;
			}

			echo $title;
			
			echo '</div>';
			
			
			echo '<div class="chart_img" id="chart_img_'.$chartID.'" onclick="tableChartAdvancedSettings(\''.$chartID.'\', \'break\');" style="cursor:pointer">';	
			// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
			echo 	'<img src="'.$imgPath.'?'.time().'" />';		
			echo '</div>';
			
			echo '<div class="chart_settings printHide iconHide">';
			$this->displaySingleSettings($chartID, $this->settings);
			echo '</div>';
			

			// Zvezdica za vkljucitev v porocilo -  Multinumber
			if($tip == 20){
				
				// Preberemo za kateri grid izrisujemo tabelo
				$gkey = $this->break_spremenljivka['break_sub_table']['key'];
				
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $this->break_spremenljivka['grids'][$gkey]['variables'][0]['sequence'].'-'.$this->break_spremenljivka['id'].'-undefined';
				SurveyAnalysis::Init($this->anketa);
				SurveyAnalysis::addCustomReportElement($type=9, $sub_type=1, $spr1, $spr2);
			}
			// Zvezdica za vkljucitev v porocilo - multigrid, number, vsota, ranking
			else{
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $this->break_spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$this->break_spremenljivka['id'].'-undefined';
				SurveyAnalysis::Init($this->anketa);
				SurveyAnalysis::addCustomReportElement($type=9, $sub_type=1, $spr1, $spr2);
			}
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData($this->sessionData);
		}
		
		// Izrisemo crosstab graf
		else{
			$this->podstran = 'crosstab';
			$this->displayCrosstabChart($chartID);
		}
	}
	
	
	// Napolnimo podatke za crosstab graf
	public function getCrosstabDataSet($chartID, $settings, $refresh=0){
		global $site_path;
		global $lang;
	
				
		$dataArray = array();
		$dataPercentArray = array();
		$gridArray = array();
		$variableArray = array();
		
		
		if ($this->classInstance->getSelectedVariables(1) !== null && $this->classInstance->getSelectedVariables(2) !== null) {
			$variables1 = $this->classInstance->getSelectedVariables(2);
			$variables2 = $this->classInstance->getSelectedVariables(1);
			$counter = 0;
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {
					
					// izrisemo graf ki ustreza vrstnemu redu
					if($counter == $this->counter){
						$crosstabs = null;
						$crosstabs_value = null;
							
						$crosstabs = $this->classInstance->createCrostabulation($v_first, $v_second);
						$crosstabs_value = $crosstabs['crosstab'];
							
						# podatki spremenljivk
						$spr1 = $this->classInstance->_HEADERS[$v_first['spr']];
						$spr2 = $this->classInstance->_HEADERS[$v_second['spr']];

						$grid1 = $spr1['grids'][$v_first['grd']];
						$grid2 = $spr2['grids'][$v_second['grd']];
							
						#število vratic in število kolon
						$cols = count($crosstabs['options1']);
						$rows = count($crosstabs['options2']);

						# ali prikazujemo vrednosti variable pri spremenljivkah
						$show_variables_values = $this->classInstance->doValues;

						
						# za multicheckboxe popravimo naslov, na podtip
						$sub_q1 = null;
						$sub_q2 = null;
						if ($spr1['tip'] == '6' || $spr1['tip'] == '7' || $spr1['tip'] == '16' || $spr1['tip'] == '17' || $spr1['tip'] == '18' || $spr1['tip'] == '19' || $spr1['tip'] == '20' || $spr1['tip'] == '21' ) {
							foreach ($spr1['grids'] AS $grid) {
								foreach ($grid['variables'] AS $variable) {
									if ($variable['sequence'] == $v_first['seq']) {
										$sub_q1 .= strip_tags($spr1['naslov']);
										if ($show_variables_values == true ) {
											$sub_q1 .= ' ('.strip_tags($spr1['variable']).')';
										}
										if ($spr1['tip'] == '16') {
											$sub_q1 .= '<br />'. strip_tags($grid1['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid1['variable']) . ')' : '');
										} else {
											$sub_q1 .= '<br />' . strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
										}
									}
								}
							}
						}
						if ($sub_q1 == null) {
							$sub_q1 .=  strip_tags($spr1['naslov']);
							$sub_q1 .=  ($show_variables_values == true ? '&nbsp;('.strip_tags($spr1['variable']).')' : '');
						}
						if ($spr2['tip'] == '6' || $spr2['tip'] == '7' || $spr2['tip'] == '16' || $spr2['tip'] == '17' || $spr2['tip'] == '18' || $spr2['tip'] == '19' || $spr2['tip'] == '20' || $spr2['tip'] == '21') {
							foreach ($spr2['grids'] AS $grid) {
								foreach ($grid['variables'] AS $variable) {
									if ($variable['sequence'] == $v_second['seq']) {
										$sub_q2 .= strip_tags($spr2['naslov']);
										if ($show_variables_values == true) {
											$sub_q2 .= ' ('.strip_tags($spr2['variable']).')';
										}
										if ($spr2['tip'] == '16') {
											$sub_q2.= '<br />' . strip_tags($grid2['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($grid2['variable']) . ')' : '');
										} else {
											$sub_q2.= '<br />' . strip_tags($variable['naslov']) . ($show_variables_values == true ? ' (' . strip_tags($variable['variable']) . ')' : '');
										}
									}
								}
							}
						}
						if ($sub_q2 == null) {
							$sub_q2 .= strip_tags($spr2['naslov']);
							$sub_q2 .= ($show_variables_values == true ? ' ('.strip_tags($spr2['variable']).')' : '');
						}

						// ZGORNJA LABELA - NEODVISNA
						//$sub_q2;
						// STRANSKA LABELA - ODVISNA
						//$sub_q1;
						
						$this->crosstabVars = array($sub_q1, $sub_q2);

						// NASLOVI GRIDOV
						$grid_cnt=0;
						if (count($crosstabs['options1']) > 0 ) {
							foreach ($crosstabs['options1'] as $ckey1 =>$crossVariabla) {
								$grid_cnt++;
								
								#ime variable
								$gridArray[$grid_cnt] = $crossVariabla['naslov'];
								
								/*echo  $crossVariabla['naslov'];
								# če ni tekstovni odgovor dodamo key
								if ($crossVariabla['type'] != 't' && $show_variables_values == true) {
									if ($crossVariabla['vr_id'] == null  ) {
										echo '<br/> ( '.$ckey1.' )';
									} else {
										echo '<br/> ( '.$crossVariabla['vr_id'].' )';
									}
								}*/
							}
						}	
						
						
						$var_cnt=0;
						if (count($crosstabs['options2']) > 0) {
							foreach ($crosstabs['options2'] as $ckey2 =>$crossVariabla2) {
								$var_cnt++;
								
								// NASLOVI VARIABEL	
								$variableArray[$var_cnt] = $crossVariabla2['naslov'];
								
								/*echo $crossVariabla2['naslov'];
								# če ni tekstovni odgovor dodamo key
								if ($crossVariabla2['type'] !== 't' && $show_variables_values == true ) {
									if ($crossVariabla2['vr_id'] == null) {
										echo '<br/> ( '.$ckey2.' )';
									} else {
										echo '<br/> ( '.$crossVariabla2['vr_id'].' )';
									}
								}*/

							
								// VREDNOSTI
								$cnt=0;
								foreach ($crosstabs['options1'] as $ckey1 => $crossVariabla1) {
									$cnt++;
								
									$dataArray[$cnt][] =  ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
									$dataPercentArray[$cnt][] = $this->classInstance->getCrossTabPercentage($crosstabs['sumaVrstica'][$ckey2], $crosstabs_value[$ckey1][$ckey2]);

									/*
									# celica z vebino
									{
										# prikazujemo eno ali več od: frekvenc, odstotkov, residualov
										if ($this->classInstance->crossChk0) {
											# izpišemo frekvence crostabov
											echo ((int)$crosstabs_value[$ckey1][$ckey2] > 0) ? $crosstabs_value[$ckey1][$ckey2] : 0;
										}
									}*/
								}						
							}
						}
					
					}
					
					$counter++;
				}
			}
		}

		//polnimo podatke
		$DataSet = new pData;		
		
		// PRAVILNO OBRNJENA - GRIDI SO SERIJE
		for($i=1; $i<=$grid_cnt; $i++){
			
			// procenti
			if($settings['value_type'] == 0){
				if(count($dataPercentArray[$i]) > 0)
					$DataSet->AddPoint($dataPercentArray[$i],'Vrednosti_'.$i);
				else
					$DataSet->AddPoint(array(0),'Vrednosti_'.$i);
			}
			// frekvence
			elseif($settings['value_type'] == 1){
				if(count($dataArray[$i]) > 0)
					$DataSet->AddPoint($dataArray[$i],'Vrednosti_'.$i);
				else
					$DataSet->AddPoint(array(0),'Vrednosti_'.$i);
			}			
		
			$DataSet->AddSerie('Vrednosti_'.$i);
			$DataSet->SetSerieName($gridArray[$i],'Vrednosti_'.$i);
		}

		// Vedno izpisemo cela imena variabel
		$DataSet->AddPoint($variableArray,"Variable");			
		$DataSet->SetAbsciseLabelSerie("Variable");
		
		if($settings['value_type'] == 0){
			$DataSet->SetYAxisUnit("%");
			$DataSet->SetYAxisFormat("number");
		}
		
		// NAROBE OBRNJENA - VARIABLE SO SERIJE
		/*for($i=1; $i<=$var_cnt; $i++){
			
			if(count($dataArray[$i]) > 0)
				$DataSet->AddPoint($dataArray[$i],'Vrednosti_'.$i);
			else
				$DataSet->AddPoint(array(0),'Vrednosti_'.$i);
		
			$DataSet->AddSerie('Vrednosti_'.$i);
			$DataSet->SetSerieName($variableArray[$i],'Vrednosti_'.$i);
		}
		// Vedno izpisemo cela imena variabel
		$DataSet->AddPoint($gridArray,"Variable");			
		$DataSet->SetAbsciseLabelSerie("Variable");*/

		
		return $DataSet;
	}
	
	// Napolnimo podatke za ttest graf
	public function getTTestDataSet($chartID, $settings, $refresh=0){
		global $site_path;
		global $lang;

		$DataSet = null;
		
		
		$variables1 = $this->classInstance->getSelectedVariables();
		foreach ($variables1 AS $v_first) {		
			if($this->counter == $counter){
				$ttest = null;
				$ttest = $this->classInstance->createTTest($v_first, $this->sessionData['ttest']['sub_conditions']);
				
				break;
			}
		}
		
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		
		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {

			$spr_data_1 = $this->classInstance->_HEADERS[$spid1];
			if ($grid1 == 'undefined') {

				# imamp lahko več variabel
				$seq = $seq1;
				foreach ($spr_data_1['grids'] as $gkey => $grid ) {
						
					foreach ($grid['variables'] as $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($sequence == $seq) {
							$sprLabel1 = '('.$variable['variable'].') '. $variable['naslov'];
						}
					}
				}
			} else {
				# imamo subgrid
				$sprLabel1 = '('.$spr_data_1['grids'][$grid1]['variable'].') '. $spr_data_1['grids'][$grid1]['naslov'];
			}
				
			$sprLabel2 =  trim(str_replace('&nbsp;','',$this->sessionData['ttest']['label2']));
			
			$this->crosstabVars = array($sprLabel1, $sprLabel2);
			
			$label1 = $this->classInstance->getVariableLabels($this->sessionData['ttest']['sub_conditions'][0]);
			$label2 = $this->classInstance->getVariableLabels($this->sessionData['ttest']['sub_conditions'][1]);
			
			//polnimo podatke
			$DataSet = new pData;
			
			//nastavimo t, ki se izpise pod legendo
			$t = $this->classInstance->formatNumber($ttest['t'],3);
			$DataSet->SetNumerus($t);
			
			$DataSet->AddPoint($this->classInstance->formatNumber($ttest[1]['x'],3),'Vrednost');
			$DataSet->AddPoint($this->classInstance->formatNumber($ttest[2]['x'],3),'Vrednost');

			$DataSet->AddSerie('Vrednost');
			
			$DataSet->AddPoint(array(0 => $label1, 1 => $label2),"Variable");
			$DataSet->SetAbsciseLabelSerie("Variable");	
		}
		
		return $DataSet;
	}
	
	// Napolnimo podatke za mean graf
	public function getMeanDataSet($chartID, $settings, $refresh=0){
		global $site_path;
		global $lang;
	
		
		$dataArray = array();
		$gridArray = array();
		$variableArray = array();
		

		$variables1 = $this->classInstance->getSelectedVariables(2);
		$variables2 = $this->classInstance->getSelectedVariables(1);
		
		if (is_array($variables2) && count($variables2) > 0) {
			foreach ($variables2 AS $v_second) {
				if (is_array($variables1) && count($variables1) > 0) {
					foreach ($variables1 AS $v_first) {
						$_means = $this->classInstance->createMeans($v_first, $v_second);
						if ($_means != null) {
							$means[$c1][0] = $_means;
						}
						$c1++;
					}
				}
			}
		}
			
		// Zaenkrat prikazemo samo graf za prvo tabelo	
		if (is_array($means) && count($means) > 0) {
			$counter=0;
			foreach ($means AS $mean_sub_grup) {
				
				// Izrisemo pravi graf po vrsti ki pripada tabeli
				if($counter == $this->counter){
					$_means = $mean_sub_grup;
					break;
				}
				
				$counter++;
			}
		}

				
		#število vratic in število kolon
		$cols = count($_means);
		# preberemo kr iz prvega loopa
		$rows = count($_means[0]['options']);
		
		
		// loop po vrsticah
		if (count($_means[0]['options']) > 0) {
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {

				// IME VARIABLE
				$variableArray[] = $crossVariabla2['naslov'];

				// VREDNOST VARIABLE
				$dataArray[] = $this->classInstance->formatNumber($_means[0]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'));
			}
		}

	
		//polnimo podatke
		$DataSet = new pData;
		

		// Sortiramo podaatke ce je potrebno
		if($settings['sort'] == 1)	
			array_multisort($dataArray, SORT_DESC, $variableArray);

		
		if(count($dataArray) > 0)
			$DataSet->AddPoint($dataArray,'Vrednosti');
		else
			$DataSet->AddPoint(array(0),'Vrednosti');
		
		$DataSet->AddSerie('Vrednosti');
		$DataSet->SetSerieName('Povprečja','Vrednosti');		
		
		// Pri povprecjih vedno izpisemo cela imena variabel
		$DataSet->AddPoint($variableArray,"Variable");
		
		$DataSet->SetAbsciseLabelSerie("Variable");

		
		return $DataSet;
	}
	
	// Napolnimo podatke za break graf
	public function getBreakDataSet($chartID, $settings, $refresh=0){
		global $site_path;
		global $lang;
		
		$keysCount = count($this->break_frequencys);
		$sequences = explode('_',$this->break_spremenljivka['sequences']);
		$forSpremenljivka = $this->classInstance->_HEADERS[$this->break_forSpr];
		$tip = $this->break_spremenljivka['tip'];
		
		# izračunamo povprečja za posamezne sekvence
		$means = array();
		$totalMeans = array();
		$totalFreq = array();
		foreach ($this->break_frequencys AS $fkey => $options) {
			foreach ($options AS $oKey => $option) {
				foreach ($sequences AS $sequence) {
					$means[$fkey][$oKey][$sequence] = $this->classInstance->getMeansFromKey($option[$sequence]);
				}
			}
		}
				
		
		//polnimo podatke
		$DataSet = new pData;
		
		$dataArray = array();
		$variableArray = array();
		
		
		// Polnimo podatke za multigrid dropdown, number, vsoto, ranking
		if($this->break_spremenljivka['tip'] != 20){
			
			$cnt=0;
			foreach ($this->break_frequencys AS $fkey => $fkeyFrequency) {
				$variableArray[] = $forSpremenljivka['grids'][0]['variables'][$cnt]['naslov'];
				$cnt++;
				foreach ($options AS $oKey => $option) {
					
					$grid_count = 0;					
					
					foreach ($this->break_spremenljivka['grids'] AS $gkey => $grid) {
						
						foreach ($grid['variables'] AS $vkey => $variable) {
							$sequence = $variable['sequence'];
							if ($variable['other'] != 1) {
							
								$grid_count++;
								
								#povprečja
								$avg = $this->classInstance->formatNumber($means[$fkey][$oKey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
								# enote
								$enote = (int)$this->break_frequencys[$fkey][$oKey][$sequence]['validCnt'];
								
								$tempArray = array();
								
								$tempArray['avg'] =  str_replace(",","",$avg);
								$tempArray['unit'] = $enote;				
								$tempArray['key'] = $variable['variable'];
								$tempArray['variable'] = $variable['naslov'];

								$dataArray[] = $tempArray;		
								
								$totalMeans[$sequence] += ($means[$fkey][$oKey][$sequence]*(int)$this->break_frequencys[$fkey][$oKey][$sequence]['validCnt']);
								$totalFreq[$sequence]+= (int)$this->break_frequencys[$fkey][$oKey][$sequence]['validCnt'];
							}
				
						}
							
					}
				}
			}
		}
				
		// Polnimo podatke za multinumber
		else{
			// Nastavimo pravo podtabelo
			$gkey = $this->break_spremenljivka['break_sub_table']['key'];			
			$grid = $this->break_spremenljivka['grids'][$gkey];

			$cnt=0;
			foreach ($this->break_frequencys AS $fkey => $fkeyFrequency) {
				$variableArray[] = $forSpremenljivka['grids'][0]['variables'][$cnt]['naslov'];
				$cnt++;
				foreach ($forSpremenljivka['options'] AS $oKey => $option) {
					
					# če je osnova checkbox vzamemo samo tam ko je 1
					if(($forSpremenljivka['tip'] == 2 && $option == 1) || $forSpremenljivka['tip'] != 2 ) {
					
						$grid_count = 0;
												
						foreach ($grid['variables'] AS $vkey => $variable) {
						
							$grid_count++;
						
							$sequence = $variable['sequence'];
						
							#povprečja
							$avg = $this->classInstance->formatNumber($means[$fkey][$oKey][$sequence],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'');
							# enote
							$enote = (int)$this->break_frequencys[$fkey][$oKey][$sequence]['validCnt'];
							
							$tempArray = array();
							
							$tempArray['avg'] = str_replace(",","",$avg);
							$tempArray['unit'] = $enote;				
							$tempArray['key'] = $variable['variable'];
							$tempArray['variable'] = $variable['naslov'] . ' ('.$variable['variable'].')';

							$dataArray[] = $tempArray;		
							
							$totalMeans[$sequence] += ($means[$fkey][$oKey][$sequence]*(int)$this->break_frequencys[$fkey][$oKey][$sequence]['validCnt']);
							$totalFreq[$sequence]+= (int)$this->break_frequencys[$fkey][$oKey][$sequence]['validCnt'];
							
						}
					}
				}
			}
		}
		
		$variable_count = count($variableArray);
		
		// Normalno obrnjen graf - gridi v stolpcih, variable v legendi (deli stolpcev)
		if($settings['rotate'] != 1){
			
			// Sortiramo podaatke ce je potrebno				
			if($settings['sort'] == 1){

				$tmp = Array();
				
				// preberemo prve vrednosti iz vsakega stolpca
				for($j=0; $j<$grid_count; $j++){
					$offset = $j*$grid_count;						
					$tmp[] = (int)$dataArray[$offset]['avg'];						
				}
				
				// sortiramo vrednosti in preberemo kljuce
				arsort($tmp);
				$sorted_keys = array_keys($tmp); 
			}
			// Sortiramo podaatke po prvi kategoriji
			elseif($settings['sort'] == 3){

				$tmp = Array();
				
				// preberemo prve vrednosti iz vsakega stolpca
				for($j=0; $j<$grid_count; $j++){
					$offset = $j;
					$tmp[] = (int)$dataArray[$offset]['avg'];
				}
				
				// sortiramo vrednosti in preberemo kljuce
				arsort($tmp);
				$sorted_keys = array_keys($tmp);
			}
			else{
				for($j=0; $j<($variable_count*$grid_count); $j++){
					$sorted_keys[] = $j;						
				}
			}
			
			// Poberemo podatke v posamezne tabele
			for($j=0; $j<$variable_count; $j++){
				
				unset($vrednosti);
				unset($vrednostiEnote);
				unset($vrednostiKey);
				unset($vrednostiVariable);
				
				// odmik glede na sortirane po prvem gridu (sort po kategorijah ali brez)
				if($settings['sort'] < 3){
					$offset = $sorted_keys[$j] /*$j*/ * $grid_count;

					for($i=0; $i<$grid_count; $i++){			
						$vrednosti[] = $dataArray[$i+$offset]['avg'];
						$vrednostiEnote[] = $dataArray[$i+$offset]['unit'];
				
						$vrednostiKey[] = $dataArray[$i+$offset]['key'];
						$vrednostiVariable[] = $dataArray[$i+$offset]['variable'];
					}
							
					if(count($vrednosti) > 0)
						$DataSet->AddPoint($vrednosti,'Vrednosti'.$sorted_keys[$j]);
					else
						$DataSet->AddPoint(array(0),'Vrednosti'.$sorted_keys[$j]);
					
					$DataSet->AddSerie('Vrednosti'.$sorted_keys[$j]);
					$DataSet->SetSerieName($variableArray[$sorted_keys[$j]],'Vrednosti'.$sorted_keys[$j]);
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($vrednostiVariable,'Variable'.$sorted_keys[$j]);
					//$DataSet->AddPoint($vrednostiKey,"Variable");
						
					$DataSet->SetAbsciseLabelSerie('Variable'.$sorted_keys[$j]);
				}
					
				// sort po prvi kategoriji
				else{
					for($i=0; $i<$grid_count; $i++){			
						$vrednosti[] = $dataArray[$j*$grid_count + $sorted_keys[$i]]['avg'];
						$vrednostiEnote[] = $dataArray[$j*$grid_count + $sorted_keys[$i]]['unit'];
				
						$vrednostiKey[] = $dataArray[$j*$grid_count + $sorted_keys[$i]]['key'];
						$vrednostiVariable[] = $dataArray[$j*$grid_count + $sorted_keys[$i]]['variable'];
					}
							
					if(count($vrednosti) > 0)
						$DataSet->AddPoint($vrednosti,'Vrednosti'.$j);
					else
						$DataSet->AddPoint(array(0),'Vrednosti'.$j);
					
					$DataSet->AddSerie('Vrednosti'.$j);
					$DataSet->SetSerieName($variableArray[$j],'Vrednosti'.$j);
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($vrednostiVariable,'Variable'.$j);
					//$DataSet->AddPoint($vrednostiKey,"Variable");
						
					$DataSet->SetAbsciseLabelSerie('Variable'.$j);			
				}					
			}
		}
		// Obratno obrnjen graf - gridi v legendi (deli stolpca), variable v stolpcih - default ce imamo samo en grid
		else{	

			// Sortiramo podaatke ce je potrebno				
			if($settings['sort'] == 1){

				$tmp = Array();
				
				// preberemo prve vrednosti iz vsakega stolpca
				for($j=0; $j<$grid_count; $j++){
					$offset = $j;
					$tmp[] = (int)$dataArray[$offset]['avg'];							
				}
				
				// sortiramo vrednosti in preberemo kljuce
				arsort($tmp);
				$sorted_keys = array_keys($tmp);
			}
			// Sortiramo podaatke po prvi kategoriji
			elseif($settings['sort'] == 3){

				$tmp = Array();
				
				// preberemo prve vrednosti iz vsakega stolpca
				for($j=0; $j<$variable_count; $j++){
					$offset = $j * $grid_count;						
					$tmp[] = (int)$dataArray[$offset]['avg'];
				}
				
				// sortiramo vrednosti in preberemo kljuce
				arsort($tmp);
				$sorted_keys = array_keys($tmp);
			}
			else{
				for($j=0; $j<$grid_count; $j++){
					$sorted_keys[] = $j;						
				}
			}

			// Poberemo podatke v posamezne tabele
			for($j=0; $j<$grid_count; $j++){
			
				// odmik glede na sortirane po prvem gridu (sort po kategorijah ali brez)
				if($settings['sort'] < 3){
					$offset = $sorted_keys[$j];

					unset($vrednosti);
					unset($vrednostiEnote);
					unset($vrednostiKey);
					unset($vrednostiVariable);
				
					for($i=0; $i<$variable_count; $i++){
					
						$vrednosti[] = $dataArray[$i*$grid_count+$offset]['avg'];
						$vrednostiEnote[] = $dataArray[$i*$grid_count+$offset]['unit'];
				
						$vrednostiKey[] = $dataArray[$i*$grid_count+$offset]['key'];
						$vrednostiVariable[] = $dataArray[$i*$grid_count+$offset]['variable'];
					}
							
					if(count($vrednosti) > 0)
						$DataSet->AddPoint($vrednosti,'Vrednosti'.$vrednostiKey[0]);
					else
						$DataSet->AddPoint(array(0),'Vrednosti'.$vrednostiKey[0]);
					
					$DataSet->AddSerie('Vrednosti'.$vrednostiKey[0]);
					$DataSet->SetSerieName($vrednostiVariable[0],'Vrednosti'.$vrednostiKey[0]);
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($variableArray,'Variable'.$vrednostiKey[0]);
					//$DataSet->AddPoint($vrednostiKey,"Variable");
						
					$DataSet->SetAbsciseLabelSerie('Variable'.$vrednostiKey[0]);
				}
				
				// sort po prvi kategoriji
				else{						
					$offset = $sorted_keys[$j];

					unset($vrednosti);
					unset($vrednostiEnote);
					unset($vrednostiKey);
					unset($vrednostiVariable);
				
					for($i=0; $i<$variable_count; $i++){

						$vrednosti[] = $dataArray[$sorted_keys[$i]*$grid_count + $j]['avg'];
						$vrednostiEnote[] = $dataArray[$sorted_keys[$i]*$grid_count + $j]['unit'];
				
						$vrednostiKey[] = $dataArray[$sorted_keys[$i]*$grid_count + $j]['key'];
						$vrednostiVariable[] = $dataArray[$sorted_keys[$i]*$grid_count + $j]['variable'];
					}
							
					if(count($vrednosti) > 0)
						$DataSet->AddPoint($vrednosti,'Vrednosti'.$vrednostiKey[0]);
					else
						$DataSet->AddPoint(array(0),'Vrednosti'.$vrednostiKey[0]);
					
					$DataSet->AddSerie('Vrednosti'.$vrednostiKey[0]);
					$DataSet->SetSerieName($vrednostiVariable[0],'Vrednosti'.$vrednostiKey[0]);
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($variableArray,'Variable'.$vrednostiKey[0]);
					//$DataSet->AddPoint($vrednostiKey,"Variable");
						
					$DataSet->SetAbsciseLabelSerie('Variable'.$vrednostiKey[0]);
				}
			}
		}
		
		return $DataSet;
	}
	
	public function setBreakVariables($forSpr,$frequencys,$spremenljivka){
	
		$this->break_forSpr = $forSpr;
		$this->break_frequencys = $frequencys;
		$this->break_spremenljivka = $spremenljivka;
	}
	
	
	// Default nastavitve grafov
	public function getDefaultSettings(){
		
		$colors = array_fill(0, 6, '');
		
		$settings = array(
			'type' 			=> 0, 	// tip radarja
			'sort'			=> 0, 	// sortiranje po velikosti
			'value_type' 	=> 0, 	// tip vrednosti (veljavni, frekvence, procenti...)
			'show_legend' 	=> 0, 	// prikaz legende
			'scale_limit' 	=> 0, 	// zacni skalo z 0 / z najmanjso vrednostjo pri numericih
			'interval' 		=> 10, 	// stevilo intervalov pri numericih
			'radar_type' 	=> 0,	// tip radarj (crte / liki)
			'radar_scale' 	=> 0,	// skala pri radarju (na osi / diagonalno)
			'labelWidth' 	=> 50,	// sirina label (50% / 20%)
			'barLabel'	 	=> 0,	// prikaz label v stolpicnih grafih
			'rotate'	 	=> 0,	// obrnjeni gridi in variable (pri multinumber...)
			'hq'	 		=> 1,	// visoka locljivost grafa
			'show_numerus'	=> 1,	// prikaz numerusa
			'colors'		=> $colors	// custom barve grafa
		);
							
		return $settings;
	}
	
	// ID grafa glede na podstran
	public function getChartID(){
		global $lang;
		
		// crosstab
		if($this->podstran == 'crosstab' || ($this->podstran == 'break' && $this->break_crosstab == 1)){
			$variables1 = $this->classInstance->getSelectedVariables(2);
			$variables2 = $this->classInstance->getSelectedVariables(1);
			$counter = 0;
			$var1 = array();
			$var2 = array();
			foreach ($variables1 AS $v_first) {
				foreach ($variables2 AS $v_second) {
					if($counter == $this->counter){
						$var1 = $v_first;
						$var2 = $v_second;
						
						break 2;
					}
					else
						$counter++;
				}
			}
			
			// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
			$chartID = implode('_', $var1).'_'.implode('_', $var2);
			$chartID .= '_counter_'.$this->counter;
		}
		
		// ttest
		elseif($this->podstran == 'ttest'){
			// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
			$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
			$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
			$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
			$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
			$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
			
			$spid2 = $this->sessionData['ttest']['spr2'];
			$seq2 = $this->sessionData['ttest']['seq2'];
			$grid2 = $this->sessionData['ttest']['grid2'];
			
			$chartID = $sub1.'_'.$sub2.'_'.$spid1.'_'.$seq1.'_'.$grid1;
		}
		
		// means
		elseif($this->podstran == 'mean'){
			// Zgeneriramo id vsake tabele (glede na izbrani spremenljivki za generiranje)
			$variables1 = $this->classInstance->getSelectedVariables(1);
			$variables2 = $this->classInstance->getSelectedVariables(2);
				
			$pos1 = floor($this->counter / count($variables2));
			$pos2 = $this->counter % count($variables2);

			$chartID = implode('_', $variables1[$pos1]).'_'.implode('_', $variables2[$pos2]);
			$chartID .= '_counter_'.$this->counter;
		}
		
		// break
		else{
			if($this->break_spremenljivka['tip'] == 20){
				// Preberemo za kateri grid izrisujemo tabelo
				$gkey = $this->break_spremenljivka['break_sub_table']['key'];
				
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $this->break_spremenljivka['grids'][$gkey]['variables'][0]['sequence'].'-'.$this->break_spremenljivka['id'].'-undefined';
			}
			else{
				$spr1 = $this->sessionData['break']['seq'].'-'. $this->sessionData['break']['spr'].'-undefined';
				$spr2 = $this->break_spremenljivka['grids'][0]['variables'][0]['sequence'].'-'.$this->break_spremenljivka['id'].'-undefined';
			}
			
			$chartID = $spr1.'_'.$spr2;
		}
		
		return $chartID;
	}
	
	// Zgeneriramo unikaten hash ID grafa
	public function generateChartId($chartID, $settings, $numerus){
				
		// ce posebej prizgemo legendo pri pie chartu
		if($settings['show_legend'] == 1 && ($settings['type'] == 2 || $settings['type'] == 5))
			$legend = '_legend';
		else
			$legend = '';

		$ID = $this->anketa.'_chart_'.$chartID.'_counter_'.$counter.'_mv_'.SurveyAnalysis::$missingProfileData['display_mv_type'];
		
		foreach ($settings AS $key => $val) {
			if($key == 'colors'){
				
				$ID .= '_colors';
				
				foreach ($val AS $colKey => $color){
					$ID .= '_'.$color;
				}
			}
			
			elseif($key != 'name')
				$ID .= '_'.$key.'_'.$val;	
		}

		$ID .= '_skin_'.$this->skin;
		
		$ID .= '_numerus_'.$numerus.'_numerusText_'.SurveyDataSettingProfiles :: getSetting('chartNumerusText');

		$ID .= '_pieZeros_'.SurveyDataSettingProfiles :: getSetting('chartPieZeros');
		
		$ID .= '_chartFontSize_'.SurveyDataSettingProfiles :: getSetting('chartFontSize');
		
		//$ID .= '_hq_'.$this->quality;
		
		return $ID;
	}
	
	// nastavimo prave barve ustrezne skinu
	public function setChartColors($chart, $skin){
		
		// Ce nimmo posebej nastavljenih barv
		if($this->settings['colors'][0] == ''){	
			// ce je nastavljen globalen custom skin
			if(is_numeric($skin)){
				$skin = SurveyChart::getCustomSkin($skin);
				$colors = explode('_', $skin['colors']);
				
				$count = 0;
				foreach($colors as $color){

					$rgb = SurveyChart::html2rgb($color);
					$chart->setColorPalette($count,$rgb[0],$rgb[1],$rgb[2]);
					
					$count++;
				}
			}
			
			// imamo nastavljenega enega od default skinov
			else{
				switch ($skin){

                    // 1ka skin
                    case '1ka':	
                    default:
						$chart->setColorPalette(0,30,136,229);
						$chart->setColorPalette(1,255,166,8);
						$chart->setColorPalette(2,72,229,194);
						$chart->setColorPalette(3,242,87,87);
						$chart->setColorPalette(4,117,70,68);
						$chart->setColorPalette(5,248,202,0);
						$chart->setColorPalette(6,255,112,166);
                        break;
                        
					// zivahen skin
					case 'lively':	
						$chart->setColorPalette(0,224,9,13);
						$chart->setColorPalette(1,4,23,227);
						$chart->setColorPalette(2,0,255,8);
						$chart->setColorPalette(3,255,247,3);
						$chart->setColorPalette(4,255,149,0);
						$chart->setColorPalette(5,0,251,255);
						$chart->setColorPalette(6,166,0,255);
						break;
						
					// blag skin
					case 'mild':	
						$chart->setColorPalette(0,188,224,46);
						$chart->setColorPalette(1,224,100,46);
						$chart->setColorPalette(2,224,214,46);
						$chart->setColorPalette(3,46,151,224);
						$chart->setColorPalette(4,176,46,224);
						$chart->setColorPalette(5,224,46,117);
						$chart->setColorPalette(6,92,224,46);
						break;
						
					// Office skin
					case 'office':	
						$chart->setColorPalette(0,79,129,189);
						$chart->setColorPalette(1,192,80,77);
						$chart->setColorPalette(2,155,187,89);
						$chart->setColorPalette(3,128,100,162);
						$chart->setColorPalette(4,75,172,198);
						$chart->setColorPalette(5,247,150,70);
						$chart->setColorPalette(6,146,169,207);
						break;
						
					// Pastel skin
					case 'pastel':	
						$chart->setColorPalette(0,121,159,11);
						$chart->setColorPalette(1,215,161,37);
						$chart->setColorPalette(2,146,100,190);
						$chart->setColorPalette(3,24,132,132);
						$chart->setColorPalette(4,76,198,139);
						$chart->setColorPalette(5,138,136,35);
						$chart->setColorPalette(6,108,153,210);
						break;
						
					// zelen skin
					case 'green':
						$chart->createColorGradientPalette(168,188,56,248,255,136,5);
						$chart->setColorPalette(5,255,255,0);
						$chart->setColorPalette(6,232,3,182);
						break;
						
					// moder skin
					case 'blue':
						$chart->createColorGradientPalette(82,124,148,174,216,240,5);
						$chart->setColorPalette(5,255,255,0);
						$chart->setColorPalette(6,232,3,182);
						break;
						
					// rdeč skin
					case 'red':
						$chart->createColorGradientPalette(255,0,0,80,10,10,5);
						$chart->setColorPalette(5,255,255,0);
						$chart->setColorPalette(6,232,3,182);
						break;
						
					// skin za vec kot 5 moznosti
					case 'multi':
						$chart->setColorPalette(0,140,0,0);
						$chart->setColorPalette(1,240,8,0);
						$chart->setColorPalette(2,255,138,130);
						$chart->setColorPalette(3,242,196,200);
						$chart->setColorPalette(4,11,3,135);
						$chart->setColorPalette(5,4,0,252);
						$chart->setColorPalette(6,151,148,242);
						$chart->setColorPalette(7,0,133,31);
						$chart->setColorPalette(8,24,217,3);
						$chart->setColorPalette(9,139,245,157);
						$chart->setColorPalette(10,237,202,45);
						$chart->setColorPalette(11,253,255,120);
						$chart->setColorPalette(12,156,0,125);
						$chart->setColorPalette(13,255,0,246);
						$chart->setColorPalette(14,242,3,162);
						$chart->setColorPalette(15,237,154,216);
						$chart->setColorPalette(16,0,123,145);
						$chart->setColorPalette(17,0,204,250);
						$chart->setColorPalette(18,174,238,245);
						$chart->setColorPalette(19,0,255,200);
						$chart->setColorPalette(20,255,111,0);
						$chart->setColorPalette(21,255,162,0);
						$chart->setColorPalette(22,255,201,120);
						$chart->setColorPalette(23,161,92,133);
						$chart->setColorPalette(24,205,159,245);
						$chart->setColorPalette(25,179,245,103);
						$chart->setColorPalette(26,135,171,108);
						$chart->setColorPalette(27,73,132,145);
						$chart->setColorPalette(28,70,96,99);
						$chart->setColorPalette(29,156,95,103);
						break;
				}
			}
		}
		else{
			for($i=0; $i<7; $i++){
				
				$color = $this->settings['colors'][$i];
				$color = substr($color, 1);
				
				list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
			
				$r = hexdec($r); 
				$g = hexdec($g); 
				$b = hexdec($b);
				
				$chart->setColorPalette($i,$r,$g,$b);
			}
		}
		
		return $chart;
	}
	
	
	// Funkcije za izris posameznih tipov grafov - vertikalni stolpci
	function createVerBars($DataSet, $show_legend=0){
		global $lang;
		
		$Data = $DataSet->GetData();
		$countGrids = count($Data);
		
		$angle = 0; 
		$addHeight = 0;
		$roundText = 15;
		if($countGrids > 5){
			$angle = 45;
			$addHeight = 110;
			$roundText = 30;
		}
		
		// Initialise the graph
		$Test = new pChart($this->settings['hq']*800,$this->settings['hq']*(250+$addHeight));

		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);

		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		$Test->setGraphArea($this->settings['hq']*100,$this->settings['hq']*40,$this->settings['hq']*650,$this->settings['hq']*220);
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,$angle,0,TRUE,1,FALSE,$roundText);
		$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Draw the bar graph
		$Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(), false, 95, $this->settings['barLabel']);
		
		if($show_legend == 1)
			$Test->drawLegend($this->settings['hq']*680,$this->settings['hq']*30,$DataSet->GetDataDescription(),255,255,255);
					
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);


		// Prikaz napisa frekvence/odstotki (samo crosstabi)
		if($this->podstran == 'crosstab'){
			if($this->settings['value_type'] == '0')
				$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*210,$this->settings['hq']*60,$this->settings['hq']*110,$lang['srv_chart_percent'],$Angle=90,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);			
			else
				$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*210,$this->settings['hq']*60,$this->settings['hq']*110,$lang['srv_chart_freq'],$Angle=90,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);			
		}

		
		return $Test;
	}

	// Funkcije za izris posameznih tipov grafov - horizontalni stolpci
	function createHorBars($DataSet, $show_legend=0, $fixedScale=0){
		global $lang;
		
		// Nastavimo visino grafa (ce imamo vec kot 7 variabel/gridov)
		$Data = $DataSet->GetData();
		$countGrids = count($Data);	
		$addHeight = $countGrids > 5 ? ($countGrids-5)*30 : 0;
		
		// Initialise the graph
		$Test = new MyHorBar($this->settings['hq']*800,$this->settings['hq']*(250+$addHeight));
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Dolge labele
		$startX = ($this->settings['labelWidth'] == 20) ? 225 : 360;
		$roundText = ($this->settings['labelWidth'] == 20) ? 35 : 65;
		
		$Test->setGraphArea($this->settings['hq']*$startX,$this->settings['hq']*70,$this->settings['hq']*650,$this->settings['hq']*(220+$addHeight));
		
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);

		// Če gre za hierarhijo, potem je fiksna skala
        if(SurveyInfo::checkSurveyModule('hierarhija', $this->anketa))
            $Test->setFixedScale(1,5,4);

		$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,0,0,TRUE,1,FALSE,$roundText);
		$Test->drawHorGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Draw the bar graph
		$Test->drawHorBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(), $this->settings['barLabel']);
		
		// Finish the graph
		if($show_legend == 1)
			$Test->drawLegend($this->settings['hq']*680,$this->settings['hq']*60,$DataSet->GetDataDescription(),255,255,255);
					
			
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		
		// Prikaz napisa frekvence in 1. spremenljivke na vrhu (samo crosstabi)
		if($this->podstran == 'crosstab'){
			if($this->settings['value_type'] == '0')
				$Test->drawTextBox($this->settings['hq']*480,$this->settings['hq']*30,$this->settings['hq']*580,$this->settings['hq']*40,$lang['srv_chart_percent'],$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
			else
				$Test->drawTextBox($this->settings['hq']*480,$this->settings['hq']*30,$this->settings['hq']*580,$this->settings['hq']*40,$lang['srv_chart_freq'],$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
			
			
			$strings = explode('<br />',$this->crosstabVars[1]);
			$substr1 = (strlen($strings[0]) > 50) ? substr($strings[0], 0, 47).'...' : $strings[0];
			$substr2 = (strlen($strings[1]) > 50) ? substr($strings[1], 0, 47).'...' : $strings[1];
			
			$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*20,$this->settings['hq']*280,$this->settings['hq']*30,$substr1,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_CENTER,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
			$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*38,$this->settings['hq']*280,$this->settings['hq']*43,$substr2,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_CENTER,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		}
		
		// Prikaz t vrednosti pri ttest grafu
		if($this->podstran == 'ttest' && $this->settings['show_numerus'] == '1'){
			$t = 't = '.$DataSet->GetNumerus();			
			$Test->drawTextBox($this->settings['hq']*680,$this->settings['hq']*210,$this->settings['hq']*795,$this->settings['hq']*220,$t,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		}
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - vertikalni sestavljeni stolpci
	function createVerStructBars($DataSet){
		global $lang;
		
		$Data = $DataSet->GetData();
		$countGrids = count($Data);
		
		$angle = 0; 
		$addHeight = 0;
		$roundText = 15;
		if($countGrids > 5){
			$angle = 45;
			$addHeight = 110;
			$roundText = 30;
		}
		
		// Initialise the graph
		$Test = new pChart($this->settings['hq']*800,$this->settings['hq']*(250+$addHeight));
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Pri navadnem radio in checkbox vprasanju imamo samo en stolpec - zato so dimenzije drugacne
		/*if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3)
			$Test->setGraphArea($this->settings['hq']*250,$this->settings['hq']*40,$this->settings['hq']*500,$this->settings['hq']*220);
		else*/
			$Test->setGraphArea($this->settings['hq']*100,$this->settings['hq']*40,$this->settings['hq']*650,$this->settings['hq']*220);
		
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,0,0,0,TRUE,$angle,0,TRUE,1,FALSE,$roundText);
		$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		// Draw the bar graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		$Test->drawStackedBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(), $this->settings['barLabel'], 95);
		
		$Test->drawLegend($this->settings['hq']*680,$this->settings['hq']*30,$DataSet->GetDataDescription(),255,255,255,$Rs=-1,$Gs=-1,$Bs=-1,$Rt=0,$Gt=0,$Bt=0,$Border=false,$reverse=true);
			
		$Test->setFontProperties("Fonts/verdana.ttf",$this->settings['hq']*10);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		
		// Prikaz napisa frekvence (samo crosstabi)
		if($this->podstran == 'crosstab'){
			if($this->settings['value_type'] == '0')
				$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*210,$this->settings['hq']*60,$this->settings['hq']*110,$lang['srv_chart_percent'],$Angle=90,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);			
			else
				$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*210,$this->settings['hq']*60,$this->settings['hq']*110,$lang['srv_chart_freq'],$Angle=90,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);			
		}
		
				
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - horizontalni sestavljeni stolpci
	function createHorStructBars($DataSet){
		global $lang;

		// Nastavimo visino graffa (ce imamo vec kot 7 variabel/gridov)
		$Data = $DataSet->GetData();
		$countGrids = count($Data);	
		$addHeight = $countGrids > 5 ? ($countGrids-5)*30 : 0;
		
		// Initialise the graph
		$Test = new MyHorBar($this->settings['hq']*800,$this->settings['hq']*(250+$addHeight+50));
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Dolge labele
		$startX = ($this->settings['labelWidth'] == 20) ? 225 : 360;
		$roundText = ($this->settings['labelWidth'] == 20) ? 35 : 65;
		
		// Pri navadnem radio in checkbox vprasanju imamo samo en stolpec - zato so dimenzije drugacne
		/*if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3){
			$Test->setGraphArea($this->settings['hq']*200,$this->settings['hq']*50,$this->settings['hq']*630,$this->settings['hq']*220);
			$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*243,5,255,255,255);
			//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
			$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*295,200,200,200);
			$Test->drawGraphArea(255,255,255,TRUE);
			$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,0,0,0,TRUE,0,0,TRUE);
			$Test->drawHorGrid(4,TRUE,230,230,230,50);
		}
		else{*/
			$Test->setGraphArea($this->settings['hq']*$startX,$this->settings['hq']*70,$this->settings['hq']*650,$this->settings['hq']*(220+$addHeight));
			$Test->drawFilledRoundedRectangle(7,7,793,243+$addHeight,5,255,255,255);
			//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
			$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*(295+$addHeight),200,200,200);
			$Test->drawGraphArea(255,255,255,TRUE);
			$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,0,0,0,TRUE,0,0,TRUE,1,FALSE,$roundText);
			$Test->drawHorGrid(4,TRUE,230,230,230,50);
		//}	

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Draw the bar graph
		$Test->drawStackedHorBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),$this->settings['barLabel'],95);
		
			
		// pri vodoravnih strukturnih stolpcih izrisemo legendo na dnu
		$Test->drawVerticalLegend($this->settings['hq']*400,$this->settings['hq']*(240+$addHeight),$DataSet->GetDataDescription(),255,255,255);
		
		$Test->setFontProperties("Fonts/verdana.ttf",$this->settings['hq']*10);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
				
		// Prikaz napisa frekvence in 1. spremenljivke na vrhu (samo crosstabi)
		if($this->podstran == 'crosstab'){
			if($this->settings['value_type'] == '0')
				$Test->drawTextBox($this->settings['hq']*480,$this->settings['hq']*30,$this->settings['hq']*580,$this->settings['hq']*40,$lang['srv_chart_percent'],$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
			else
				$Test->drawTextBox($this->settings['hq']*480,$this->settings['hq']*30,$this->settings['hq']*580,$this->settings['hq']*40,$lang['srv_chart_freq'],$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
			
			$strings = explode('<br />',$this->crosstabVars[1]);
			$substr1 = (strlen($strings[0]) > 50) ? substr($strings[0], 0, 47).'...' : $strings[0];
			$substr2 = (strlen($strings[1]) > 50) ? substr($strings[1], 0, 47).'...' : $strings[1];
			
			$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*20,$this->settings['hq']*280,$this->settings['hq']*30,$substr1,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_CENTER,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
			$Test->drawTextBox($this->settings['hq']*50,$this->settings['hq']*38,$this->settings['hq']*280,$this->settings['hq']*43,$substr2,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_CENTER,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		}	
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - krozni graf
	function createPie($DataSet, $show_legend=1){
		global $lang;
	
		// Initialise the graph
		$Test = new pChart($this->settings['hq']*800,$this->settings['hq']*280);
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		// Pri pie grafu uporabimo antialiasing
		$Test->setAntialias(true, 20);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		//$Test->setGraphArea(50,40,685,220);
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*273,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*275,200,200,200);
		//$Test->createColorGradientPalette(195,204,56,223,110,41,3);
		//$Test->createColorGradientPalette(168,188,56,248,255,136,5);
		
		// Draw the pie graph
		$labels = ($this->settings['sort'] == 1) ? 'custom_percent_sort' : 'custom_percent';
		$Test->drawFlatPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),$this->settings['hq']*390,$this->settings['hq']*145,$this->settings['hq']*95,$labels);
		
		$Test->setAntialias(false, 0);
		
		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		//$Test->drawLegend(700,30,$DataSet->GetDataDescription(),255,255,255);
		
		if($show_legend == 1)
			$Test->drawPieLegend($this->settings['hq']*600,$this->settings['hq']*50,$DataSet->GetData(),$DataSet->GetDataDescription(),255,255,255);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*10);
				
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
				
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - krozni graf
	function create3DPie($DataSet, $show_legend=1){
		global $lang;
	
		// Initialise the graph
		$Test = new pChart($this->settings['hq']*800,$this->settings['hq']*280);
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		// Pri pie grafu uporabimo antialiasing
		$Test->setAntialias(true, 20);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		//$Test->setGraphArea(50,40,685,220);
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*273,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*275,200,200,200);
		//$Test->createColorGradientPalette(195,204,56,223,110,41,3);
		//$Test->createColorGradientPalette(168,188,56,248,255,136,5);
		
		// Draw the pie graph
		$labels = ($this->settings['sort'] == 1) ? 'custom_percent_sort' : 'custom_percent';
		$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),$this->settings['hq']*390,$this->settings['hq']*130,$this->settings['hq']*95,$labels,$EnhanceColors=true,$Skew=50,$SpliceHeight=$this->settings['hq']*20,$SpliceDistance=0,$Decimals=0);
		
		$Test->setAntialias(false, 0);
		
		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		//$Test->drawLegend(700,30,$DataSet->GetDataDescription(),255,255,255);
		
		if($show_legend == 1)
			$Test->drawPieLegend($this->settings['hq']*600,$this->settings['hq']*50,$DataSet->GetData(),$DataSet->GetDataDescription(),255,255,255);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*10);
				
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
				
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - linijski graf
	function createVerLine($DataSet, $show_legend=0, $fixedScale=1){
		global $lang;
		
		// Nastavimo visino grafa (ce imamo vec kot 7 variabel/gridov)
		$Data = $DataSet->GetData();
		$countGrids = count($Data);	
		$addHeight = $countGrids > 5 ? ($countGrids-5)*30 : 0;
		
		// Initialise the graph
		$Test = new MyHorBar($this->settings['hq']*800,$this->settings['hq']*(250+$addHeight));
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
				
		$Test->setGraphArea($this->settings['hq']*270,$this->settings['hq']*50,$this->settings['hq']*530,$this->settings['hq']*(220+$addHeight));
		
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		
		
		/*$VMax = count($spremenljivka['options']);
		$Divisions = $VMax-1;
		
		$Test->setFixedScale($VMin=1, $VMax, $Divisions);*/

		
		$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,0,0,TRUE,1,$rightScale=FALSE,$roundText=40);
		$Test->drawHorGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		// Draw the line graph
		$Test->drawVerLineGraph($DataSet->GetData(),$DataSet->GetDataDescription(), $insideValues=false);
		
		// Finish the graph
		if($show_legend == 1)
			$Test->drawLegend($this->settings['hq']*680,$this->settings['hq']*30,$DataSet->GetDataDescription(),255,255,255);
			
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
					
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		

		return $Test;		
	}
	
	// Funkcije za izris posameznih tipov grafov - radar
	function createRadar($DataSet, $show_legend=0, $fixedScale=0){
		global $lang;
		
		$Data = $DataSet->GetData();
		$countGrids = count($Data);
				
		// Initialise the graph
		$Test = new pChart($this->settings['hq']*800,$this->settings['hq']*350);

		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
		
		// Pri radar grafu uporabimo antialiasing
		$Test->setAntialias(true, 20);
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		$Test->setGraphArea($this->settings['hq']*100,$this->settings['hq']*40,$this->settings['hq']*650,$this->settings['hq']*320);
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*343,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*345,200,200,200);
		//$Test->drawGraphArea(255,255,255,TRUE);
		//$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,20,20,20,TRUE,$angle,0,TRUE,1,FALSE,$roundText);
		//$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$VMax = -1;
		
		// Draw the radar
		$Test->drawRadarAxis($DataSet->GetData(),$DataSet->GetDataDescription(),true,5,0,0,0,160,160,160,$VMax,$this->settings['radar_scale']);
		// Tip radarja - navaden ali samo crte
		if($this->settings['radar_type'] == 1)
			$Test->drawFilledRadar($DataSet->GetData(),$DataSet->GetDataDescription(),50,5,$VMax);
		else{
			$Test->setLineStyle($Width=(2*$this->settings['hq']),$DotSize=0);
			$Test->drawRadar($DataSet->GetData(),$DataSet->GetDataDescription(),5,$VMax);
		}

		$Test->setAntialias(false, 0);
		
		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		if($show_legend == 1)
			$Test->drawLegend($this->settings['hq']*680,$this->settings['hq']*30,$DataSet->GetDataDescription(),255,255,255);
			
		$Test->setFontProperties("Fonts/verdana.ttf",$this->settings['hq']*10);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
				
		return $Test;
	}					
	
	// Funkcije za izris posameznih tipov grafov - linijski graf
	function createLine($DataSet, $show_legend=0, $fixedScale=0){
				
		// Initialise the graph
		$Test = new pChart($this->settings['hq']*800,$this->settings['hq']*280);
		
		// Nastavimo barve grafu glede na skin
		$Test = $this->setChartColors($Test, $this->skin);
						
		$count = count($DataSet->GetData());
				
		// Kot label na x osi
		$angle = 0;
		if($count > 6)
			$angle = 45;
		
		$Test->setLineStyle($this->settings['hq'],$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		$Test->setGraphArea($this->settings['hq']*100,$this->settings['hq']*40,$this->settings['hq']*650,$this->settings['hq']*220);
		$Test->drawFilledRoundedRectangle($this->settings['hq']*7,$this->settings['hq']*7,$this->settings['hq']*793,$this->settings['hq']*273,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle($this->settings['hq']*5,$this->settings['hq']*5,$this->settings['hq']*795,$this->settings['hq']*275,200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,$angle,0,TRUE);
		if($count <= 20)
			$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		//$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		// Draw the bar graph
		$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
		if($count <= 20)
			$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),$this->settings['hq']*3,$this->settings['hq']*2,255,255,255);
		
		if($show_legend == 1)
			$Test->drawLegend($this->settings['hq']*680,$this->settings['hq']*30,$DataSet->GetDataDescription(),255,255,255);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',$this->settings['hq']*$this->fontSize);
		
		
		$Test->drawTextBox($this->settings['hq']*690,$this->settings['hq']*(210+$addHeight),$this->settings['hq']*795,$this->settings['hq']*(220+$addHeight),$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		
		return $Test;
	}
	

	// Nastavitve posameznega grafa
	function displaySingleSettings($chartID, $settings=0){
		global $site_path;
		global $lang;
				
		// Ikone izvoza na vrhu posameznih nastavitev
		//$this->displayExportIcons($chartID);
		
		
		echo '<div id="switch_left_'.$chartID.'_loop_0" class="switch_left '.($this->settings_mode == 1 ? ' non-active' : '').'" onClick="chartSwitchSettings(\''.$chartID.'\', \'0\', \'0\')">'.$lang['srv_chart_settings_basic'].'</div>';		
		//echo '<span id="switch_middle_'.$chartID.'_loop_0" class="'.($this->settings_mode == 1 ? 'rightHighlight' : 'leftHighlight').'"></span>';	
		echo '<div id="switch_right_'.$chartID.'_loop_0" class="switch_right '.($this->settings_mode == 0 ? ' non-active' : '').'" onClick="chartSwitchSettings(\''.$chartID.'\', \'1\', \'0\')">'.$lang['srv_chart_settings_advanced'].'</div>';
		
		
		// OSNOVNE NASTAVITVE
		echo '<div class="chart_settings_inner" id="chart_settings_basic_'.$chartID.'_loop_0" '.($this->settings_mode == 1 ? ' style="display:none;"' : '').'>';
		
		//echo '<span class="title">'.$lang['srv_chart_settings'].'</span>';

		switch($this->podstran){
			case 'crosstab':
				$this->displayCrosstabSettings($chartID, $settings);
				break;
			
			case 'ttest':
				$this->displayTTestSettings($chartID, $settings);
				break;
			
			case 'mean':
				$this->displayMeanSettings($chartID, $settings);
				break;
				
			case 'break':
				$this->displayBreakSettings($chartID, $settings);
				break;
				
			default:
				break;
		}	
		
		echo '</div>';	
		
		
		// NAPREDNE NASTAVITVE
		echo '<div class="chart_settings_inner" id="chart_settings_advanced_'.$chartID.'_loop_0" '.($this->settings_mode == 0 ? ' style="display:none;"' : '').'>';
		
		switch($this->podstran){
			
			case 'crosstab':			
			case 'mean':
			case 'break':
				// visoka locljivost grafa
				echo '<div class="chart_setting">';
					
				echo $lang['srv_chart_hq'].': ';
				echo '<input type="checkbox" id="tablechart_hq_'.$chartID.'" name="tablechart_hq" '.($settings['hq']=='3'?' checked="checked"':'').' onchange="changeTableChart(\''.$chartID.'\', \''.$this->podstran.'\', \'hq\');">';

				echo '</div>';
				break;
			
			case 'ttest':					
				// prikaz numerusa
				echo '<div class="chart_setting">';
				
				$checked = ($settings['show_numerus']=='1') ? ' checked="checked"': '';

				echo $lang['srv_chart_showNumerus'].': ';
				echo '<input type="checkbox" id="tablechart_show_numerus_'.$chartID.'" name="tablechart_show_numerus" '.$checked.' onchange="changeTableChart(\''.$chartID.'\', \'ttest\', \'show_numerus\');">';

				echo '</div>';
				
			default:
				break;
		}
		
		
		// Link na urejanje label
		//echo '<span class="edit" style="margin-top:15px;" onclick="chartAdvancedSettings(\''.$this->counter.'\');">'.$lang['srv_chart_advancedLink_labels'].'</span>';	
		// Vprasajcek za pomoc
		//echo Help :: display('displaychart_settings_labels');
		
		// Link na urejanje barv
		echo '<span class="edit" onclick="tableChartAdvancedSettings(\''.$chartID.'\', \''.$this->podstran.'\')">'.$lang['srv_chart_advancedLink_colors'].'</span>';	
		// Vprasajcek za pomoc
		echo Help :: display('displaychart_settings_colors');		
		
		// Link na rekodiranje
		//echo '<span class="edit" onclick="chartAdvancedSettings(\''.$this->counter.'\', \'3\');">'.$lang['srv_chart_advancedLink_recoding'].'</span>';	
		// Vprasajcek za pomoc
		//echo Help :: display('displaychart_settings_recoding');
			
			
		echo '</div>';
	}
	
	// ikone na vrhu posameznih nastavitev (izvozi)
	function displayExportIcons($chartID){
		global $site_path;
		global $lang;
		
		// linki
		echo '<div class="chart_setting_exportLinks">';
				
			// Ikona za print
			echo '<a href="#" onclick="showAnalizaSingleChartPopup(\''.$chartID.'\',\''.M_ANALYSIS_CHARTS.'\'); return false;">';		
			echo '<span class="faicon print_small icon-grey_dark_link" title="' . $lang['PRN_Izpis'] . '"></span>';
			echo '</a>';

			// Izvoz posameznega grafa v PDF/RTF/PPT
			echo '&nbsp;<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts&anketa='.$this->anketa.'&sprID='.$chartID).'" target="_blank" title="'.$lang['PDF_Izpis'].'"><span class="faicon pdf"></span>&nbsp;</a>';
			echo '&nbsp;<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts_rtf&anketa='.$this->anketa.'&sprID='.$chartID).'" target="_blank" title="'.$lang['RTF_Izpis'].'"><span class="faicon rtf"></span>&nbsp;</a>';
			echo '&nbsp;<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts_ppt&anketa='.$this->anketa.'&sprID='.$chartID).'" target="_blank" title="'.$lang['PPT_Izpis'].'"><span class="faicon ppt"></span>&nbsp;</a>';
			
		echo '</div>';
	}
	
	// Nastavitve za crosstab graf
	function displayCrosstabSettings($chartID, $settings){
		global $site_path;
		global $lang;

		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="tablechart_type_'.$chartID.'" name="tablechart_type" onchange="changeTableChart(\''.$chartID.'\', \'crosstab\', \'type\');">';

		if($this->crossCheck){
			// navedbe
			if($this->classInstance->crossNavVsEno == 0){
				echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
				echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';		
				echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_pie'].'</option>';
				echo '  <option value="5" '.($settings['type']=='5'?' selected="selected"':'').'>'.$lang['srv_chart_3Dpie'].'</option>';
				echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_structure_hor'].'</option>';
				echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_structure_ver'].'</option>';				
			}
			// enote
			else{
				echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_structure_hor'].'</option>';
				echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_structure_ver'].'</option>';
				echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
				echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';	
			}
		}
		else{
			echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_structure_hor'].'</option>';
			echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_structure_ver'].'</option>';
			echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
			echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		}
			
		echo '</select>';
		echo '</div>';
			
			
		// tip izpisa vrednosti		
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_valtype'].': <select id="tablechart_value_type_'.$chartID.'" name="tablechart_value_type" onchange="changeTableChart(\''.$chartID.'\', \'crosstab\', \'value_type\');">';
			
		echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';
		echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		
		// prikaz label v stolpcih
		//if($settings['type'] == 0 || $settings['type'] == 1){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="tablechart_barLabel_'.$chartID.'" name="tablechart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeTableChart(\''.$chartID.'\', \'crosstab\', \'barLabel\');">';

			echo '</div>';
		//}
		
		
		// sirina label
		/*if($settings['type'] == 0 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="tablechart_labelWidth_'.$chartID.'" name="tablechart_labelWidth" onchange="changeTableChart(\''.$chartID.'\', \'crosstab\', \'labelWidth\');">';
			
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}*/
	}
	
	// Nastavitve za ttest graf
	function displayTTestSettings($chartID, $settings){
		global $site_path;
		global $lang;
	
		// sirina label
		echo '<div class="chart_setting">';
		
		echo $lang['srv_wide_chart'].': <select id="tablechart_labelWidth_'.$chartID.'" name="tablechart_labelWidth" onchange="changeTableChart(\''.$chartID.'\', \'ttest\', \'labelWidth\');">';
		
		echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
		echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
		
		echo '</select>';
		
		echo '</div>';
		
		
		// visoka locljivost grafa
		echo '<div class="chart_setting">';
			
		echo $lang['srv_chart_hq'].': ';
		echo '<input type="checkbox" id="tablechart_hq_'.$chartID.'" name="tablechart_hq" '.($settings['hq']=='3'?' checked="checked"':'').' onchange="changeTableChart(\''.$chartID.'\', \'ttest\', \'hq\');">';

		echo '</div>';
	}
	
	// Nastavitve za mean graf
	function displayMeanSettings($chartID, $settings){
		global $site_path;
		global $lang;
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="tablechart_type_'.$chartID.'" name="tablechart_type" onchange="changeTableChart(\''.$chartID.'\', \'mean\', \'type\');">';

		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_avg_hor'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_avg_radar'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_avg_line'].'</option>';
			
		echo '</select>';
		echo '</div>';
		
		
		// sortiranje
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_sort'].': ';
		echo '<input type="checkbox" id="tablechart_sort_'.$chartID.'" name="tablechart_sort" '.($settings['sort']=='1'?' checked="checked"':'').' onchange="changeTableChart(\''.$chartID.'\', \'mean\', \'sort\');">';

		echo '</div>';
			
			
		// sirina label
		if($settings['type'] == 0){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="tablechart_labelWidth_'.$chartID.'" name="tablechart_labelWidth" onchange="changeTableChart(\''.$chartID.'\', \'mean\', \'labelWidth\');">';
			
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		
		// Tip radarja		
		if($settings['type'] == 1){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="tablechart_radar_type_'.$chartID.'" name="tablechart_radar_type" onchange="changeTableChart(\''.$chartID.'\', \'mean\', \'radar_type\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == 1){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="tablechart_radar_scale_'.$chartID.'" name="tablechart_radar_scale" onchange="changeTableChart(\''.$chartID.'\', \'mean\', \'radar_scale\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
	}

	// Nastavitve za crosstab graf
	function displayBreakSettings($chartID, $settings){
		global $site_path;
		global $lang;				
		
		$tip = $this->break_spremenljivka['tip'];
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="tablechart_type_'.$chartID.'" name="tablechart_type" onchange="changeTableChart(\''.$chartID.'\', \'break\', \'type\');">';
		
		if($tip != 7)
			echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_line'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// sortiranje
		echo '<div class="chart_setting">';

		echo $lang['srv_chart_sort'].': <select id="tablechart_sort_'.$chartID.'" name="tablechart_sort" onchange="changeTableChart(\''.$chartID.'\', \'break\', \'sort\');">';
			
		echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
		if($tip != 7)
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_category'].'</option>';
		echo '  <option value="3" '.($settings['sort']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_sort_first'].'</option>';
		
		echo '</select>';

		echo '</div>';
		
		// Obrnjeni gridi in variable	
		echo '<div class="chart_setting">';
		
		if($settings['rotate']=='1'){
			echo $lang['srv_chart_rotate_grids'].' ';
			echo '<span onclick="changeTableChart(\''.$chartID.'\', \'break\', \'rotate\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
			echo '<input type="hidden" id="tablechart_rotate_'.$chartID.'" name="tablechart_rotate" value="0">';
			echo ' '.$lang['srv_chart_rotate_vars'].' ';
		}
		else{
			echo $lang['srv_chart_rotate_vars'].' ';
			echo '<span onclick="changeTableChart(\''.$chartID.'\', \'break\', \'rotate\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
			echo '<input type="hidden" id="tablechart_rotate_'.$chartID.'" name="tablechart_rotate" value="1">';
			echo ' '.$lang['srv_chart_rotate_grids'];
		}
		echo '</div>';
		
		// Tip radarja		
		if($settings['type'] == '0'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="tablechart_radar_type_'.$chartID.'" name="tablechart_radar_type" onchange="changeTableChart(\''.$chartID.'\', \'break\', \'radar_type\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == '0'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="tablechart_radar_scale_'.$chartID.'" name="tablechart_radar_scale" onchange="changeTableChart(\''.$chartID.'\', \'break\', \'radar_scale\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
	}
		
	// Napredne nastavitve grafa
	function displayAdvancedSettings($chartID){
		global $site_path;
		global $lang;

        echo '<h2>'.$lang['srv_detail_settings'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="chartCloseAdvancedSettings(); return false;">✕</a></div>';
		
		echo '<form method="post" name="table_chart_advanced_settings" onsubmit="tableChartSaveAdvancedSettings(\''.$chartID.'\'); return false;">';
		
		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'" />';
		echo '<input type="hidden" name="podstran" value="'.$this->podstran.'" />';
		echo '<input type="hidden" name="chartID" value="'.$chartID.'" />';
		
		// urejanje barv
		echo '<div id="chartSettingsArea1" class="chartSettingsArea">';
		$this->displayAdvancedSettingsColors($chartID);
		echo '</div>';	
		
		// urejanje label
		echo '<div id="chartSettingsArea2" class="chartSettingsArea" style="visibility: hidden;">';
		//$this->displayAdvancedSettingsLabels($chartID);	
		echo '</div>';
		
		echo '</form>';
		
		/* ZAVIHKI NA DESNI */
		echo '<div id="chartTabs" class="chartSettingsTabs">';
		
		echo '<ul>';	
		echo '<li id="chartTab1" class="chartTab active" onClick="chartTabAdvancedSettings(\'1\');">';
		echo  $lang['srv_chart_advanced_colors'];
		echo '</li>';
		echo '</ul>';	
		
		echo '</div>';		
		
		
		/* GUMBI NA DNU */
		echo '<div id="chartSettingsButtons" class="buttons_holder">';
		
		echo '<span class="buttonwrapper spaceRight floatLeft">';
		echo '<a class="ovalbutton ovalbutton_gray" onclick="chartCloseAdvancedSettings(); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
		echo '</span>';	
		
		echo '<span class="buttonwrapper floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" onclick="tableChartSaveAdvancedSettings(\''.$chartID.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        echo '</span>';		
	
		echo '</div>';	
	}
	
	// Urejanje barv posameznega grafa
	function displayAdvancedSettingsColors($chartID){
		global $site_path;
		global $lang;
		
		echo '<script type="text/javascript" charset="utf-8">
			  $(document).ready(function() {
				var f = $.farbtastic(\'#picker\');
				var p = $(\'#picker\').css(\'opacity\', 0.25);
				var selected;
				$(\'.colorwell\')
				  .each(function () { f.linkTo(this); $(this).css(\'opacity\', 0.75); })
				  .focus(function() {
					if (selected) {
					  $(selected).css(\'opacity\', 0.75).removeClass(\'colorwell-selected\');
					}
					f.linkTo(this);
					p.css(\'opacity\', 1);
					$(selected = this).css(\'opacity\', 1).addClass(\'colorwell-selected\');
				  });
			  });
			 </script>';
		
 
		echo '  <div id="picker" style="float: right;"></div>';
		
		$default_colors = SurveyChart::getDefaultColors($this->skin);
		
		for($i=0; $i<7; $i++){
			$name = 'color'.($i+1);
			$value = ($this->settings['colors'][$i] != '') ? $this->settings['colors'][$i] : $default_colors[$i];
			
			echo '  <div class="form-item"><label for="'.$name.'">'.$lang['srv_color'].' '.($i+1).': </label><input type="text" id="'.$name.'" name="'.$name.'" class="colorwell" value="'.$value.'" /></div>';
		}
		
		// reset na default barvo
		echo '<br /><span class="as_link clr" onClick="chartAdvancedSettingsSetColor(\''.(is_numeric($this->skin) ? implode("_",$default_colors) : $this->skin).'\')">'.$lang['srv_chart_advanced_default_color'].'</span>';
		
		// nastavitev ene od palet
		echo '<br /><span class="clr">'.$lang['srv_chart_advanced_skin'].': ';
		echo '<select name="chart_advanced_color" id="chart_advanced_color" onChange="chartAdvancedSettingsSetColor(this.value)">';
		echo '	<option' . ($this->skin == '1ka' ? ' selected="selected"' : '') . ' value="1ka">'.$lang['srv_chart_skin_1ka'].'</option>';
		echo '	<option' . ($this->skin == 'lively' ? ' selected="selected"' : '') . ' value="lively">'.$lang['srv_chart_skin_0'].'</option>';
		echo '	<option' . ($this->skin == 'mild' ? ' selected="selected"' : '') . ' value="mild">'.$lang['srv_chart_skin_1'].'</option>';
		echo '	<option' . ($this->skin == 'office' ? ' selected="selected"' : '') . ' value="office">'.$lang['srv_chart_skin_6'].'</option>';
		echo '	<option' . ($this->skin == 'pastel' ? ' selected="selected"' : '') . ' value="pastel">'.$lang['srv_chart_skin_7'].'</option>';
		echo '	<option' . ($this->skin == 'green' ? ' selected="selected"' : '') . ' value="green">'.$lang['srv_chart_skin_2'].'</option>';
		echo '	<option' . ($this->skin == 'blue' ? ' selected="selected"' : '') . ' value="blue">'.$lang['srv_chart_skin_3'].'</option>';
		echo '	<option' . ($this->skin == 'red' ? ' selected="selected"' : '') . ' value="red">'.$lang['srv_chart_skin_4'].'</option>';
		echo '	<option' . ($this->skin == 'multi' ? ' selected="selected"' : '') . ' value="multi">'.$lang['srv_chart_skin_5'].'</option>';
			
		$customSkins = $this->getCustomSkins();
		foreach($customSkins as $customSkin){					
			echo '	<option' . ($this->skin == $customSkin['id'] ? ' selected="selected"' : '') . ' value="'.$customSkin['colors'].'">'.$customSkin['name'].'</option>';
		}
		echo '</select></span>';
	}
	
	function getCustomSkins(){
		global $global_user_id;
		
		$skins = array();
		
		$sql = sisplet_query("SELECT * FROM srv_chart_skin WHERE usr_id='$global_user_id'");
		while($row = mysqli_fetch_array($sql)){
			$skins[] = $row;
		}
		
		return $skins;
	}
	
	
	/** Funkcije ki skrbijo za ajax del
	 * 
	 */
	public function ajax() {
			
		if (isset ($_POST['anketa'])) {
			$anketa = $_POST['anketa'];
			$this->anketa = $_POST['anketa'];
		}
		if (isset ($_POST['chartID']))
			$chartID = $_POST['chartID'];
		if (isset ($_POST['chart_type']))
			$chart_type = $_POST['chart_type'];
		if (isset ($_POST['podstran']))
			$this->podstran = $_POST['podstran'];	

		
		// dobimo vse nastavitve iz sessiona
		if(isset($this->sessionData[$this->podstran.'_charts'][$chartID]))
			$this->settings = $this->sessionData[$this->podstran.'_charts'][$chartID];
		else
			$this->settings = $this->getDefaultSettings();
		
		
		if (isset ($_POST['what']))
			$what = $_POST['what'];	
		if (isset ($_POST['value']))
			$value = $_POST['value'];	
		
		$this->settings[$what] = $value;		
		$this->sessionData[$this->podstran.'_charts'][$chartID] = $this->settings;		

		// Shranimo spremenjene nastavitve v bazo
		SurveyUserSession::saveData($this->sessionData);
		
		if ($_GET['a'] == 'table_chart_advanced_settings') {
			$this->displayAdvancedSettings($chartID);
		}
		
		if ($_GET['a'] == 'chart_save_advanced_settings') {
			// SHRANIMO BARVE
			// preverimo najprej ce shranjujemo vrednosti, ki so enake kot izbran skin
			$default = true;
			$default_colors = SurveyChart::getDefaultColors($this->skin);
			for($i=1; $i<8; $i++){
				if($_POST['color'.$i] != $default_colors[$i-1]){
					$default = false;
					break;
				}				
			}
			for($i=1; $i<8; $i++){
				
				// ce niso default vrednosti shranimo nastavljeno barvo
				if($default == false)
					$color = $_POST['color'.$i];
				// ce so default vrednosti shranimo prazno
				else
					$color = '';
						
				$this->settings['colors'][$i-1] = $color;		
			}
			
			$this->sessionData[$this->podstran.'_charts'][$chartID] = $this->settings;
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData($this->sessionData);
		}
		
		// Globalne nastavitve za vse grafe
		if ($_GET['a'] == 'change_global_settings') {
			SurveyUserSetting :: getInstance()->saveSettings('default_chart_profile_'.$what, $value);
			//$this->display();
		}
		
		if ($_GET['a'] == 'change_chart') {
			if($this->podstran == 'break'){
				$this->classInstance = new SurveyBreak($this->anketa);
				
				$this->break_forSpr = $this->sessionData['break_charts'][$chartID]['forSpr'];
				$this->break_frequencys = $this->sessionData['break_charts'][$chartID]['frequencys'];
				$this->break_spremenljivka = $this->sessionData['break_charts'][$chartID]['spremenljivka'];
			}
			
			// imamo crosstab graf
			else{
				$this->classInstance = new SurveyCrosstabs();
				$this->classInstance->Init($this->anketa);
				
				// Napolnimo podatke crosstabu
				$crossData1 = $this->sessionData['crosstab_charts'][$chartID]['spr1'];
				$crossData2 = $this->sessionData['crosstab_charts'][$chartID]['spr2'];

				$this->classInstance->setVariables($crossData1['seq'],$crossData1['spr'],$crossData1['grd'],$crossData2['seq'],$crossData2['spr'],$crossData2['grd']);
				
				$this->break_spremenljivka['tip'] = 1;
				$this->break_spremenljivka['skala'] = 0;
			}

			$this->displayBreakChart($chartID);
		}
		
		if ($_GET['a'] == 'chart_reload_advanced_settings') {
			// SHRANIMO BARVE
			// preverimo najprej ce shranjujemo vrednosti, ki so enake kot izbran skin
			$default = true;
			$default_colors = SurveyChart::getDefaultColors($this->skin);
			for($i=1; $i<8; $i++){
				if($_POST['color'.$i] != $default_colors[$i-1]){
					$default = false;
					break;
				}				
			}
			for($i=1; $i<8; $i++){
				
				// ce niso default vrednosti shranimo nastavljeno barvo
				if($default == false)
					$color = $_POST['color'.$i];
				// ce so default vrednosti shranimo prazno
				else
					$color = '';
						
				$this->settings['colors'][$i-1] = $color;		
			}
			
			$this->sessionData[$this->podstran.'_charts'][$chartID] = $this->settings;

			if($this->podstran == 'break'){
				$this->classInstance = new SurveyBreak($this->anketa);
				
				$this->break_forSpr = $this->sessionData['break_charts'][$chartID]['forSpr'];
				$this->break_frequencys = $this->sessionData['break_charts'][$chartID]['frequencys'];
				$this->break_spremenljivka = $this->sessionData['break_charts'][$chartID]['spremenljivka'];
			}
			
			// imamo crosstab graf
			else{
				$this->classInstance = new SurveyCrosstabs();
				$this->classInstance->Init($this->anketa);
				
				// Napolnimo podatke crosstabu
				$crossData1 = $this->sessionData['crosstab_charts'][$chartID]['spr1'];
				$crossData2 = $this->sessionData['crosstab_charts'][$chartID]['spr2'];

				$this->classInstance->setVariables($crossData1['seq'],$crossData1['spr'],$crossData1['grd'],$crossData2['seq'],$crossData2['spr'],$crossData2['grd']);
				
				$this->break_spremenljivka['tip'] = 1;
				$this->break_spremenljivka['skala'] = 0;
			}
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData($this->sessionData);
			
			$this->displayBreakChart($chartID);
		}
	}
		
}
?>