<?php
/**
* @author 	Peter Hrvatin
* @date		Februar 2011
*/

define("SAA_FOLDER", "AnalysisArchive");
	
class SurveyChart {
	
	public static $anketa;									# id ankete
	public static $folder = '';							# pot do folderja
	private static $headFileName = null;					# pot do header fajla
	private static $dataFileName = null;					# pot do data fajla
	private static $dataFileStatus = null;					# status data datoteke
	private static $SDF = null;								# class za inkrementalno dodajanje fajlov
	
	public static $uid;									# id userja

	public static $inited = false; 						# ali smo razred inicializirali

	public static $current_loop = 'undefined';		 	# v kateri zanki smo (ce imamo skupine)

	public static $skin = '1ka';	# nastavitev skina za grafe
	public static $numbering = 0;		# stevilcenje vprasanj
	public static $fontSize = 8;		# velikost fonta v grafih
	public static $quality = 1;		# kvaliteta (sirina) slike (1 -> 800px, 2 -> 1600px)
	
	public static $num_records = 10;
	public static $numerusText = '';	// dodaten text pri numerusu (veljavni, navedbe)
	
	public static $settings = array();			// nastavitve grafa
	
	public static $settings_mode=0;			// zavihek nastavitev (osnovno/napredno)
	
	public static $returnChartAsHtml = false;					# ali vrne rezultat analiz kot html ali ga izpiše
	public static $isArchive = false;							# nastavimo na true če smo v arhivu
	public static $chartArchiveTime = '';						# unikatnost
	public static $publicChart = false;                         # ali smo preko public povezave
    
	private static $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
    private static $survey = null;                            # podatki ankete
    
    private static $baseImageUrl = "";                            

    
	/**
	* Inicializacija
	* 
	* @param int $anketa
	*/
	static function Init( $anketa = null ) {
		global $global_user_id, $site_path, $site_url;
				
		self::$folder = $site_path . EXPORT_FOLDER.'/';
	
		if ((int)$anketa > 0) { 	# če je poadan anketa ID	

			self::$anketa = $anketa;

            self::$baseImageUrl = $site_url . 'admin/survey/';
			#inicializiramo SurveyAnalasys
			SurveyAnalysis::Init(self::$anketa);
			
			//SurveyAnalysis::$setUpJSAnaliza = false;

			#inicializiramo class za datoteke
			self::$SDF = SurveyDataFile::get_instance();
			self::$SDF->init($anketa);
			self::$headFileName = self::$SDF->getHeaderFileName();
			self::$dataFileName = self::$SDF->getDataFileName();
			self::$dataFileStatus = self::$SDF->getStatus();
						
			if (self::$dataFileStatus == FILE_STATUS_NO_DATA 
				|| self::$dataFileStatus == FILE_STATUS_NO_FILE
				|| self::$dataFileStatus == FILE_STATUS_SRV_DELETED){
				
				// Zakaj je treba da je tukaj exit?
				//exit;
    			return false;
    		}
			
			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);

			#preberemo HEADERS iz datoteke
			SurveyAnalysis::$_HEADERS = unserialize(file_get_contents(self::$headFileName));

			# odstranimo sistemske variable tipa email, ime, priimek, geslo
			SurveyAnalysis::removeSystemVariables();
            
            SurveyInfo :: getInstance()->SurveyInit(self::$anketa);
            self::$survey = SurveyInfo::getInstance()->getSurveyRow();

		}
		else {
			die("Napaka!");
		}
		
		if ( SurveyInfo::getInstance()->SurveyInit(self::$anketa))
		{
			self::$uid = $global_user_id;
			SurveyUserSetting::getInstance()->Init(self::$anketa, self::$uid);
		}
		
		SurveyZankaProfiles :: Init(self::$anketa, $global_user_id);
					
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init(self::$anketa);
		self::$sessionData = SurveyUserSession::getData('charts');
		
		//$chartTableMore = SurveyDataSettingProfiles :: getSetting('chartTableMore');		
		//self::$num_records = ($chartTableMore == 0) ? 10 : 1000;
		$result = SurveyDataSettingProfiles :: getSetting('numOpenAnswers');
		self::$num_records = ($result > 0) ? $result : 30;
		
		self::$skin = (SurveyUserSetting::getInstance()->getSettings('default_chart_profile_skin') == null ? '1ka' : SurveyUserSetting::getInstance()->getSettings('default_chart_profile_skin'));
		self::$numbering = SurveyDataSettingProfiles :: getSetting('chartNumbering');
		self::$fontSize = SurveyDataSettingProfiles :: getSetting('chartFontSize');

		self::$quality = (isset(self::$sessionData['hq']) && self::$sessionData['hq'] == 1) ? 3 : 1;
	}
	
	/**
	* Funkcija ki jo klicemo periodicno za vzdrzevanje cacha
	* 
	* @param int $charts_num - stevilo grafov ki jih ustvarimo za vsako anketo
	* @param int $expire_time - starejse grafe od $expire_time dni brisemo
	*/
	static function chartCache($charts_num = 5, $expire_time = 10){
	
		// loop cez vse ankete za katere urejamo cache
		$sql = sisplet_query("SELECT id FROM srv_anketa WHERE edit_time >= NOW() - INTERVAL ".$expire_time." DAY");
		while($row = mysqli_fetch_assoc($sql)){
			
			echo $row['id'].'<br>';
			
			self::Init($row['id']);
			self::createCache($charts_num);
			
			if (self::$returnChartAsHtml == false) {
				flush(); ob_flush();
			}
		}
		
		// Na koncu pobrisemo vse stare grafe - ki so bili ustvarjeni vec kot 3 mesece nazaj
		self::clearCache($expire_time * 9);
	}
	
	// Pobrisemo stare (starejse od $expire_time v dnevih) slike grafov iz cache folderja
	static function clearCache($expire_time = 14){

		$folderPath = dirname(__FILE__).'/../../pChart/Cache/';
		$fileTypes = '*';
		
		// Pobrisemo file starejse od
		$expire_time = $expire_time * 24 * 60 * 60; 
		 
		foreach (glob($folderPath . $fileTypes) as $Filename) {
			
			// preberemo cas dostopa do fila
			//$FileCreationTime = fileatime($Filename);
			// preberemo cas ustvarjanja fila
			$FileCreationTime = filemtime($Filename);
		 
			// starost v dnevih
			$FileAge = round( (time() - $FileCreationTime) / $expire_time );

			if ($FileAge >= ($expire_time)){
		 
				// brisemo stare file
				//echo 'Datoteka '.$Filename.' je starejša od '.$expire_time.' min in je bila zbrisana.<br />';
				unlink($Filename);
			}
		}
	}
	
	// Zgeneriramo prvih $charts_num grafov v cache
	static function createCache($charts_num = 5){
		global $site_path;
		
		# preberemo header
		if (self::$headFileName !== null ) {

			$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
			foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
				
				# preverjamo ali je meta
				if (($spremenljivka['tip'] != 'm'
				 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
				 && (!isset($_spid) || (isset($_spid) && $_spid == $spid)) && in_array($spremenljivka['tip'],array(1,2,3,6,7,8,16,17,18,20) )) {

					# ali imamo sfiltrirano spremenljivko
					if ( $vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) ) {
	
						// defult nastavitve posameznega grafa
						self::$settings = self::getDefaultSettings();
						
						$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
						
						// Napolnimo podatke za graf
						$DataSet = self::getDataSet($spid, self::$settings);
					
						// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0 in ni variabel v vprasanju preskocimo graf
						if($DataSet != 0 && $DataSet != -1){

							// Cache
							$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
							
							$ID = self::generateChartId($spid, self::$settings, $DataSet->GetNumerus());

							if($charts_num > 0){
								# 	prikazujemo v odvisnosti od kategorije spremenljivke
								switch ($spremenljivka['tip']) {
									case 1: # radio
									case 3:	# dropdown
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createHorBars($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;						
									
									case 2: #checkbox
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createHorBars($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;					
									
									case 6: # multigrid
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createHorBars($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;
									
									case 7:	# število
									case 8:	# datum
									case 22: # compute
									case 25: # kvota
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createHorBars($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;	
									
									case 16: # multicheckbox
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createVerStructBars($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;
								
									case 17: # razvrščanje
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createHorBars($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;					
									
									case 20: # multi number
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createRadar($DataSet, $spremenljivka);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;
									
									case 18: # vsota
										if( !$Cache->isInCache($ID, $DataSet->GetData()) ){
											$Test = self::createPie($DataSet, $spremenljivka, self::$settings['show_legend']);
											$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);
										}
										$charts_num--;
										break;
									
									default: # vsi ostali
										break;	
								}
							}
							
							// Dosezemo limit stevila grafov za generiranje
							else
								break;
							
						}
						
					}
						
				} // end if $spremenljivka['tip'] != 'm'
				
			} // end foreach self::$_HEADERS
			
		} // end if else ($_headFileName == null)
	}
	
	static function display(){
		global $site_path;
		global $lang;
		
		# zakeširamo vsebino, in jo nato po potrebi zapišpemo v html 
    	if (self::$returnChartAsHtml != false) {
			ob_start();
		}
		
		// prikazemo nastavitve
		if (self::$isArchive != true && self::$publicChart != true) {
			self::displayGlobalSettings();		
			echo "<br/>\n";
		}

		# preberemo header
		if (self::$headFileName !== null ) {

			echo '<div id="div_analiza_data" class="charts">';

			if(self::$isArchive != true && self::$publicChart != true) {
				SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
			}

			# če nimamo zank
			if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0){
				self::$current_loop = 'undefined';
				self::displayCharts();
			}
			else{
				$loop_cnt = 0;
				# če mamo zanke
				foreach(SurveyAnalysis::$_LOOPS AS $loop) {
					$loop_cnt++;
					$loop['cnt'] = $loop_cnt;
					SurveyAnalysis::$_CURRENT_LOOP = $loop;

					self::$current_loop = $loop_cnt;
					
					echo '<h2>'.$lang['srv_zanka_note'].$loop['text'].'</h2>';
					
					self::displayCharts();
				}
			}
		
			echo '</div>';

			if (self::$isArchive != true && self::$publicChart != true) {
				self::displayBottomSettings();
			}
			
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData(self::$sessionData, 'charts');
		
		} // end if else ($_headFileName == null)
		
		if (self::$returnChartAsHtml == false) {
			ob_flush(); flush();
			return;
		} else {
			$result = ob_get_clean();
			ob_flush(); flush();
			return $result;
		}
		
	}
	
	static function displayCharts(){
		global $lang;
		global $site_path;

		# polovimo frekvence			
		SurveyAnalysis::getFrequencys();

		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 && $spremenljivka['tip'] != 5) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {

					// preberemo ze nastavljene nastavitve posameznega grafa iz sessiona
					if(isset(self::$sessionData[$spid][self::$current_loop]) && self::$current_loop != 'undefined'){
						self::$settings = self::$sessionData[$spid][self::$current_loop];
					}
					else if(isset(self::$sessionData[$spid]) && self::$current_loop == 'undefined'){
						self::$settings = self::$sessionData[$spid];
					}
					// nastavimo default nastavitve za vsak graf
					else{
						self::$settings = self::getDefaultSettings();
			
						
						// ce imamo numeric dropdown popravimo default graf na skupinski
						if($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)){
							self::$settings['type'] = 5;
						}
						else{
							// Ce imamo radio tip in manj kot 5 variabel in numeric oz 2 variabli - po defaultu prikazemo piechart
							$vars = (is_countable($spremenljivka['options'])) ? count($spremenljivka['options']) : 0;
							if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && (($vars < 5 && $spremenljivka['skala'] == 1) || $vars < 3) ){
								self::$settings['type'] = 2;
							}							
							
							// Ce imamo radio tip in vec kot 20 variabel -> po defaultu ne prikazujemo praznih
							$vars = (is_countable($spremenljivka['options'])) ? count($spremenljivka['options']) : 0;
							if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $vars > 20 ){
								self::$settings['hideEmptyVar'] = 1;
							}
						}

						
						// Ce imamo checkbox ga po defaultu uredimo po velikosti
						if($spremenljivka['tip'] == 2){
							self::$settings['sort'] = 1;
						}
						// Ce imamo checkbox in vec kot 20 variabel -> po defaultu ne prikazujemo praznih
						$vars = (is_countable($spremenljivka['grids'][0]['variables'])) ? count($spremenljivka['grids'][0]['variables']) : 0;
						if( $spremenljivka['tip'] == 2 && $vars > 20 ){
							self::$settings['hideEmptyVar'] = 1;
						}
						
						
						// Ce imamo multigrid ali multicheckbox in vec kot 20 variabel -> po defaultu ne prikazujemo praznih
						$vars = (is_countable($spremenljivka['grids'])) ? count($spremenljivka['grids']) : 0;
						if( ($spremenljivka['tip'] == 6 || $spremenljivka['tip'] == 16) && $vars > 20 ){
							self::$settings['hideEmptyVar'] = 1;
						}
						
						// Ce imamo semanticni diferencial po defaultu prikazemo vertikalno crto
						$vars = (is_countable($spremenljivka['options'])) ? count($spremenljivka['options']) : 0;
						if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 1){
							self::$settings['type'] = 6;
						}						
						// Ce imamo mg tip in manj kot 4 gridov po defaultu prikazemo strukturne stolpce
						elseif($spremenljivka['tip'] == 6 && $vars < 4 && $spremenljivka['enota'] != 3){
							self::$settings['type'] = 2;
                        }		
                        						
						// Ce imamo MG vedno sortiramo po povprecijh razen ce imamo semanicni diferencial z 1 variablo
						$vars = (is_countable($spremenljivka['grids'])) ? count($spremenljivka['grids']) : 0;
						if($spremenljivka['tip'] == 6 && ($vars != 1 || $spremenljivka['enota'] != 1)){
							self::$settings['sort'] = 1;
						}
						// Pri multigridu imamo default obrnjene gride/variable ???
						/*if( $spremenljivka['tip'] == 6 ){
							self::$settings['rotate'] = 1;
						}*/
						
						
						// pri number po defaultu prikazemo legendo
						if($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 22){
							self::$settings['show_legend'] = 1;
						}
						
						
						// Ce imamo razvrscanje ga po defaultu uredimo po velikosti
						if($spremenljivka['tip'] == 17){
							self::$settings['sort'] = 1;
						}
						
								
						// Vsota ima po novem default hor. stolpce
						if($spremenljivka['tip'] == 18)
							self::$settings['type'] = 2;
							
							
						// Ce imamo multinumber in samo en grid po defaultu prikazemo stolpce in zarotiramo grids/vars
						$vars = $spremenljivka['grids']['0']['cnt_vars'];
						if( $spremenljivka['tip'] == 20 && $vars == 1 ){
							self::$settings['type'] = 1;
							self::$settings['rotate'] = 1;
						}							
					}
					
					// Spremenimo default alignment vseh tabel ce imamo vklopljeno levo poravnavo
					$chartTableAlign = SurveyDataSettingProfiles :: getSetting('chartTableAlign');
					if($chartTableAlign == 1){
						//popravimo tabele za other
						self::$settings['otherType'] = 1;
						
						//popravimo se vse ostale tabele
						if(in_array($spremenljivka['tip'], array(19,21,4))){
							self::$settings['show_legend'] = 1;
						}
					}
					else{
						//popravimo tabele za other
						self::$settings['otherType'] = 0;
						
						//popravimo se vse ostale tabele
						if(in_array($spremenljivka['tip'], array(19,21,4))){
							self::$settings['show_legend'] = 0;
						}
					}

					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1: # radio
						case 3:	# dropdown
							self::displayRadioChart($spid, self::$settings);
							break;						
						
						case 2: #checkbox
							self::displayCheckboxChart($spid, self::$settings);
							break;					
						
						case 6: # multigrid
							// dvojna tabela
							if($spremenljivka['enota'] == 3)
								self::displayDoubleMultigridChart($spid, self::$settings);
							else
								self::displayMultigridChart($spid, self::$settings);
							break;
						
						case 7:	# število
                                                case 22: # compute
							self::displayNumberChart($spid, self::$settings);
							break;
						
						case 8:	# datum
							self::displayDateChart($spid, self::$settings);
							break;
						
						case 25: # kvota
							break;
						
						case 16: # multicheckbox
							self::displayMulticheckboxChart($spid, self::$settings);
							break;
						
						case 17: # razvrščanje
							self::displayRankingChart($spid, self::$settings);
							break;					
						
						case 20: # multi number
							self::displayMultinumberChart($spid, self::$settings);
							break;
						
						case 18: # vsota
							self::displayVsotaChart($spid, self::$settings);
							break;
						
						case 4:	# text
							self::frequencyVertical($spid);
							break;
						
						case 5:	 # nagovor
							/*if(self::$view == 0)
								SurveyAnalysis::sumNagovor($spid,'sums');*/
							break;
						
						case 19: # multitext
							self::sumMultiText($spid);
							break;
						
						case 21: # besedilo*
							if ($spremenljivka['cnt_all'] == 1) {
								// če je enodimenzionalna prikažemo kot frekvence
								// predvsem zaradi vprašanj tipa: language, email... 
								self::frequencyVertical($spid);
							} else {
								self::frequencyVertical($spid);
							}	
							break;
						case 27:
							# heatmap
							SurveyAnalysis::heatmapGraph($spid,'sums',true, true);
							break;
						
						default:
							break;
					}
					//echo '</div>'.NEW_LINE;
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
			
		} // end foreach self::$_HEADERS

	}
	
	static function displaySingle($spid){
		global $site_path;
		global $lang;
		
		// Ce delamo arhiv iz custom reporta ne izvajamo ob_starta in ob_get_clean
		$archiveFromCReport = (($_GET['a'] == 'submitArchiveAnaliza' || $_GET['a'] == 'createArchiveBeforeEmail') && $_POST['podstran'] == 'analysis_creport') ? true : true;
		
		# zakeširamo vsebino, in jo nato po potrebi zapišpemo v html 
    	if (self::$returnChartAsHtml != false && $archiveFromCReport == false) {
			ob_start();
		}

		// prikazemo nastavitve
		/*self::displaySettings();		
		echo "<br/>\n";
		echo "<br/>\n";*/

		# preberemo header
		if (self::$headFileName !== null ) {

			echo '<div id="div_analiza_data" class="charts">';

			$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
			$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

			SurveyAnalysis::getFrequencys();
			
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 && $spremenljivka['tip'] != 5) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {
					
					echo '<div class="chart_holder" id="chart_'.$spid.'">';

					//div za pozicijo popupa
					echo '<div id="'.$spid.'"></div>';
					
					// defult nastavitve posameznega grafa
					if(isset(self::$sessionData[$spid])){
						self::$settings = self::$sessionData[$spid];
					}
					else{
					
						self::$settings = self::getDefaultSettings();
			
						// Ce imamo radio tip in manj kot 5 variabel po defaultu prikazemo piechart
						$vars = count($spremenljivka['options']);
						if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $vars < 5 && $spremenljivka['skala'] == 1){
							self::$settings['type'] = 2;
						}
						
						// Ce imamo mg tip in manj kot 5 variabel po defaultu prikazemo en strukturni stolpec
						/*$vars = $spremenljivka['cnt_all'];
						if( ($spremenljivka['tip'] == 6) && $vars < 5 ){
							self::$settings['type'] = 2;
						}*/
						// Ce imamo semanticni diferencial po defaultu prikazemo vertikalno crto
						if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 1){
							self::$settings['type'] = 6;
						}
						// Ce imamo mg tip in manj kot 5 gridov po defaultu prikazemo strukturne stolpce
						elseif($spremenljivka['tip'] == 6 && $vars < 5 && $spremenljivka['enota'] != 3){
							self::$settings['type'] = 2;
						}
													
						// Vsota ima po novem default hor. stolpce
						if($spremenljivka['tip'] == 18)
							self::$settings['type'] = 2;
							
						// Ce imamo multinumber in samo en grid po defaultu prikazemo stolpce in zarotiramo grids/vars
						$vars = $spremenljivka['grids']['0']['cnt_vars'];
						if( $spremenljivka['tip'] == 20 && $vars == 1 ){
							self::$settings['type'] = 1;
							self::$settings['rotate'] = 1;
						}
					}
					
					// Spremenimo default alignment vseh tabel ce imamo vklopljeno levo poravnavo
					$chartTableAlign = SurveyDataSettingProfiles :: getSetting('chartTableAlign');
					if($chartTableAlign == 1){
						//popravimo tabele za other
						self::$settings['otherType'] = 1;
						
						//popravimo se vse ostale tabele
						if(in_array($spremenljivka['tip'], array(19,21,4))){
							self::$settings['show_legend'] = 1;
						}
					}
					else{
						//popravimo tabele za other
						self::$settings['otherType'] = 0;
						
						//popravimo se vse ostale tabele
						if(in_array($spremenljivka['tip'], array(19,21,4))){
							self::$settings['show_legend'] = 0;
						}
					}

					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1: # radio
						case 3:	# dropdown
							self::displayRadioChart($spid, self::$settings);
							break;						
						case 2: #checkbox
							self::displayCheckboxChart($spid, self::$settings);
							break;					
						case 6: # multigrid
							// dvojna tabela
							if($spremenljivka['enota'] == 3)
								self::displayDoubleMultigridChart($spid, self::$settings);
							else
								self::displayMultigridChart($spid, self::$settings);
							break;
						case 7:	# število
                                                case 22: # compute
							self::displayNumberChart($spid, self::$settings);
							break;
						case 8:	# datum
							self::displayDateChart($spid, self::$settings);
							break;
						case 25: # kvota
							//self::displayNumberChart($spid, self::$settings);
							break;
						case 16: # multicheckbox
							self::displayMulticheckboxChart($spid, self::$settings);
							break;
						case 17: # razvrščanje
							self::displayRankingChart($spid, self::$settings);
							break;					
						case 20: # multi number
							self::displayMultinumberChart($spid, self::$settings);
							//self::frequencyVertical($spid);
							break;
						case 18: # vsota
							self::displayVsotaChart($spid, self::$settings);
							break;
						case 4:	# text
							//SurveyAnalysis::sumTextVertical($spid,'sums');
							self::frequencyVertical($spid);
							break;
						case 5:	 # nagovor
							/*if(self::$view == 0)
								SurveyAnalysis::sumNagovor($spid,'sums');*/
							break;
						case 19: # multitext
							//SurveyAnalysis::sumMultiText($spid,'sums');
							//self::frequencyVertical($spid);
							self::sumMultiText($spid);
							break;
						case 21: # besedilo*
							if ($spremenljivka['cnt_all'] == 1) {
								// če je enodimenzionalna prikažemo kot frekvence
								// predvsem zaradi vprašanj tipa: language, email... 
								//SurveyAnalysis::sumTextVertical($spid,'sums');
								self::frequencyVertical($spid);
							} else {
								//SurveyAnalysis::sumMultiText($spid,'sums');
								self::frequencyVertical($spid);
							}	
							break;
						default:
							break;
					}
					echo '</div>'.NEW_LINE;
					
					
					// Shranimo spremenjene nastavitve v bazo
					SurveyUserSession::saveData(self::$sessionData, 'charts');
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
			
			echo '</div>';
			
			//self::displayBottomSettings();
		
		} // end if else ($_headFileName == null)
		
		if (self::$returnChartAsHtml == false) {
			ob_flush(); flush();
			return;
		} 
		else {
			if($archiveFromCReport == false){
				$result = ob_get_clean();
				ob_flush(); flush();
				return $result;
			}
		}
	}
	
	/** 
	 *	Izrise graf za posamezno spremenljivko
	 */
	static function displayRadioChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
	
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		// Ce smo ravnokar preklopili na pieChart vklopimo sortiranje
		if(self::$current_loop != 'undefined'){
			if((self::$sessionData[$spid][self::$current_loop]['type'] != 2 && $settings['type'] == 2) || (self::$sessionData[$spid][self::$current_loop]['type'] != 8 && $settings['type'] == 8)){
				$settings['sort'] = 1;
				self::$settings['sort'] = 1;
			}
		}
		else{
			if((self::$sessionData[$spid]['type'] != 2 && $settings['type'] == 2) || (self::$sessionData[$spid]['type'] != 8 && $settings['type'] == 8)){
				$settings['sort'] = 1;
				self::$settings['sort'] = 1;
			}
		}
		
		// popravimo nastavitve za numeric dropdown
		if(self::$current_loop != 'undefined'){
			if($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)){
				// Ce smo ravnokar preklopili na linijski - po skupinah imamo default vse intervale
				if(self::$sessionData[$spid][self::$current_loop]['type'] != 7 && $settings['type'] == 7 ){
					$settings['interval'] = -1;
					self::$settings['interval'] = -1;
				}
				// Ce smo ravnokar preklopili na navaden - po skupinah imamo default 10 intervalov
				if(self::$sessionData[$spid][self::$current_loop]['type'] < 5 && $settings['type'] > 4){
					$settings['interval'] = 10;
					self::$settings['interval'] = 10;
				}
			}
		}
		else{
			if($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)){
				// Ce smo ravnokar preklopili na linijski - po skupinah imamo default vse intervale
				if(self::$sessionData[$spid]['type'] != 7 && $settings['type'] == 7 ){
					$settings['interval'] = -1;
					self::$settings['interval'] = -1;
				}
				// Ce smo ravnokar preklopili na navaden - po skupinah imamo default 10 intervalov
				if(self::$sessionData[$spid]['type'] < 5 && $settings['type'] > 4){
					$settings['interval'] = 10;
					self::$settings['interval'] = 10;
				}
			}
		}
		
		// Popravimo pri preklopu na povprecje - prikazujemo notranje vrednosti in izklopimo prikaz povprecja
		if(self::$sessionData[$spid]['type'] != 9 && $settings['type'] == 9){
			$settings['barLabel'] = 1;
			self::$settings['barLabel'] = 1;
			
			$settings['show_avg'] = 0;
			self::$settings['show_avg'] = 0;
		}
		
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());

		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa - ali ce refreshamo grafe
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
				
				// Horizontalni stolpci
				case 0:
				// Horizontalni stolpci - numeric dropdown
				case 5:
				// Horizontalen stolpec - povprecje
				case 9:
					$Test = self::createHorBars($DataSet, $spremenljivka);
				break;
				
				// Navpicni stolpci
				case 1:
				// Navpicni stolpci - numeric dropdown
				case 6:
					$Test = self::createVerBars($DataSet, $spremenljivka);
				break;
				
				// Pie chart
				case 2:
					$Test = self::createPie($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// 3D Pie chart
				case 8:
					$Test = self::create3DPie($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// Sestavljeni stolpec - horizontalen
				case 3:
					$Test = self::createHorStructBars($DataSet, $spremenljivka);
				break;
				
				// Sestavljeni stolpec - vertikalen
				case 4:
					$Test = self::createVerStructBars($DataSet, $spremenljivka);
				break;
				
				// Linijski graf - numeric dropdown
				case 7:
                                case 22:
					$Test = self::createLine($DataSet, $spremenljivka);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		} 
        $imgUrl = self::$baseImageUrl . $imgPath;
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;

		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}			
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}

		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
                $_sequence = $_variable['sequence'];	
                		
				if(is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
					self::outputOtherAnswers($oAnswers);
					echo '</div>';
					
					echo '<div class="chart_settings other_settings printHide iconHide">';
					self::displayOtherSettings($spid);
					echo '</div>';
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}
	
	static function displayCheckboxChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;		
						
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
		
		// Popravimo pri preklopu na enote (kjer ne moremo imeti strukturnih stolpcev)
		if( ($settings['type'] == 2 || $settings['type'] == 7 || $settings['type'] == 3 || $settings['type'] == 4) && $settings['base'] == 0 ){
			$settings['type'] = 0;
		}
		// Popravimo pri preklopu na navedbe (kjer ne moremo imeti radarja in linijskega grafa)
		if( ($settings['type'] == 5 || $settings['type'] == 6) && $settings['base'] == 1 ){
			$settings['type'] = 0;
		}
		
		// Ce smo ravnokar preklopili na pieChart vklopimo sortiranje
		if(self::$current_loop != 'undefined'){
			if((self::$sessionData[$spid][self::$current_loop]['type'] != 2 && $settings['type'] == 2) || (self::$sessionData[$spid][self::$current_loop]['type'] != 7 && $settings['type'] == 7)){
				$settings['sort'] = 1;
				self::$settings['sort'] = 1;
			}
		}
		else{
			if((self::$sessionData[$spid]['type'] != 2 && $settings['type'] == 2) || (self::$sessionData[$spid]['type'] != 7 && $settings['type'] == 7)){
				$settings['sort'] = 1;
				self::$settings['sort'] = 1;
			}
		}
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
				
				// Horizontalni stolpci
				case 0:
					$Test = self::createHorBars($DataSet, $spremenljivka);
				break;
				
				// Vodoravni stolpci
				case 1:
					$Test = self::createVerBars($DataSet, $spremenljivka);
				break;
				
				// Pie chart
				case 2:
					$Test = self::createPie($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// 3D Pie chart
				case 7:
                                case 22:
					$Test = self::create3DPie($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// Sestavljeni stolpec - horizontalen
				case 3:
					$Test = self::createHorStructBars($DataSet, $spremenljivka);
				break;
				
				// Sestavljeni stolpec - vertikalen
				case 4:
					$Test = self::createVerStructBars($DataSet, $spremenljivka);
				break;
				
				// Radar
				case 5:
					$Test = self::createRadar($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// Linijski graf
				case 6:
					$Test = self::createLine($DataSet, $spremenljivka);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;

		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			if($settings['base'] == 1)
				echo '(r = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			else
				echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '<br /><span class="subtitle">'.$lang['srv_info_checkbox'];
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		$addHeight = ($settings['type'] == 2) ? 'style="height: 245px"' : '';
		
		echo '<div class="chart_settings printHide iconHide" '.$addHeight.'>';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {			

				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
				$_sequence = $_variable['sequence'];			
                
                if(is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
				
					echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
					self::outputOtherAnswers($oAnswers);
					echo '</div>';
					
					echo '<div class="chart_settings other_settings printHide iconHide">';
					self::displayOtherSettings($spid);
					echo '</div>';
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}
	
	static function displayNumberChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;

		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
				
		if(self::$current_loop != 'undefined'){
			// Ce smo ravnokar preklopili na linijski - po skupinah imamo default vse intervale
			if(self::$sessionData[$spid][self::$current_loop]['type'] != 2 && $settings['type'] == 2){
				$settings['interval'] = -1;
				self::$settings['interval'] = -1;
			}
			// Ce smo ravnokar preklopili na navaden - po skupinah imamo default 10 intervalov
			if(self::$sessionData[$spid][self::$current_loop]['type'] >= 2 && $settings['type'] < 2){
				$settings['interval'] = 10;
				self::$settings['interval'] = 10;
			}
		}
		else{		
			// Ce smo ravnokar preklopili na linijski - po skupinah imamo default vse intervale
			if(self::$sessionData[$spid]['type'] != 2 && $settings['type'] == 2){
				$settings['interval'] = -1;
				self::$settings['interval'] = -1;
			}
			// Ce smo ravnokar preklopili na navaden - po skupinah imamo default 10 intervalov
			if(self::$sessionData[$spid]['type'] >= 2 && $settings['type'] < 2){
				$settings['interval'] = 10;
				self::$settings['interval'] = 10;
			}
		}
		
		// Popravimo pri preklopu na povprecje - prikazujemo notranje vrednosti in izklopimo prikaz povprecja
		if(self::$sessionData[$spid]['type'] != 9 && $settings['type'] == 9){
			$settings['barLabel'] = 1;
			self::$settings['barLabel'] = 1;
			
			$settings['show_avg'] = 0;
			self::$settings['show_avg'] = 0;
		}
		
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
			
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());		
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
							
				// Horizontalni stolpci - po skupinah ali navadno
				case 0:
				case 3:
				// Horizontalen stolpec - povprecje
				case 9:
					$Test = self::createHorBars($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// Vertikalni stolpci - po skupinah ali navadno
				case 1:
				case 4:
					$Test = self::createVerBars($DataSet, $spremenljivka, $settings['show_legend']);
				break;
				
				// Line chart - po skupinah
				case 2:
					$Test = self::createLine($DataSet, $spremenljivka, $settings['show_legend']);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;

		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}
		
		echo '</div>';
	}
	
	static function displayDateChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
							
				// Horizontalni stolpci - po skupinah ali navadno
				case 0:
				case 3:
					$Test = self::createHorBars($DataSet, $spremenljivka);
				break;
				
				// Vertikalni stolpci - po skupinah ali navadno
				case 1:
				case 4:
					$Test = self::createVerBars($DataSet, $spremenljivka);
				break;
				
				// Line chart
				case 2:
					$Test = self::createLine($DataSet, $spremenljivka);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			 
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;

		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}
		
		echo '</div>';
	}
	
	static function displayMultigridChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
		
		//ce imamo nominalno spremenljivko ali ce je samo 1 variabla nimamo povprecij
		if( ($spremenljivka['cnt_all'] == 1 || $spremenljivka['skala'] == 1) && ($settings['type'] == 0 || $settings['type'] == 5 || $settings['type'] == 6) ){
			$settings['type'] = 2;
		}
		
		//ce imamo navadne stolpce (ne povprecij) - ugasnemo labele vrednosti na stolpcih
		if( $settings['type'] == 3 || $settings['type'] == 4 ){
			$settings['barLabel'] = 0;
			self::$settings['barLabel'] = 0;
		}
				
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
				
				// Povprecja - horizontalni stolpci
				case 0:
					$Test = self::createHorBars($DataSet, $spremenljivka, $settings['show_legend'], $settings['noFixedScale']/*$fixedScale=1*/);
				break;
				
				// Povprecja - vertikalna crta
				case 6:
					$Test = self::createVerLine($DataSet, $spremenljivka, $settings['show_legend'], $settings['noFixedScale']);
				break;
				
				// Povprecja - radar
				case 5:
					$Test = self::createRadar($DataSet, $spremenljivka, $settings['show_legend'], $settings['noFixedScale']);
				break;
				
				// Sestavljeni stolpci - navpicni
				case 1:
					$Test = self::createVerStructBars($DataSet, $spremenljivka);
				break;
				
				// Sestavljeni stolpci - vodoravni
				case 2:
					$Test = self::createHorStructBars($DataSet, $spremenljivka);
				break;
				
				// Navpicni stolpci
				case 3:
					$Test = self::createVerBars($DataSet, $spremenljivka, 1);
				break;
				
				// Horizontalni stolpci
				case 4:
					$Test = self::createHorBars($DataSet, $spremenljivka, 1);
				break;
				
				// Nominalni radar
				case 7:
                                case 22:    
					$Test = self::createRadar($DataSet, $spremenljivka, 1, $fixedScale=0);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
				
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}

		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
                $_sequence = $_variable['sequence'];	
                		
				if(is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					
					echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
					self::outputOtherAnswers($oAnswers);
					echo '</div>';
					
					echo '<div class="chart_settings other_settings printHide iconHide">';
					self::displayOtherSettings($spid);
					echo '</div>';
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}
	
	static function displayDoubleMultigridChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
				
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
				
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
				
				// Horizontal chart
				case 0:
					$Test = self::createHorBars($DataSet, $spremenljivka, $legend=1, $settings['noFixedScale']);
				break;
				
				// Vertical chart
				case 1:
					$Test = self::createVerBars($DataSet, $spremenljivka, $legend=1, $settings['noFixedScale']);
				break;
				
				// Line chart
				case 2:
					$Test = self::createLine($DataSet, $spremenljivka, $legend=1, $settings['noFixedScale']);
				break;
				
				// Vertical line chart
				case 3:
					$Test = self::createVerLine($DataSet, $spremenljivka, $legend=1, $settings['noFixedScale']);
				break;
				
				// Radar chart
				case 4:
					$Test = self::createRadar($DataSet, $spremenljivka, $legend=1, $settings['noFixedScale']);
				break;		
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
				
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}

		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
                $_sequence = $_variable['sequence'];
                			
				if(is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
					self::outputOtherAnswers($oAnswers);
					echo '</div>';
					
					echo '<div class="chart_settings other_settings printHide iconHide">';
					self::displayOtherSettings($spid);
					echo '</div>';
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}
		
	static function displayMulticheckboxChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];			
		
		
		//Popravimo pri preklopu na enote (kjer ne moremo imeti strukturnih stolpcev)
		if( ($settings['type'] == 2 || $settings['type'] == 3) && $settings['base'] == 0 ){
			$settings['type'] = 0;
		}
		//Popravimo pri preklopu na navedbe (kjer ne moremo imeti radarja)
		if( $settings['type'] == 4 && $settings['base'] == 1 ){
			$settings['type'] = 0;
		}
			
		// Popravimo ce preklopimo iz veljavnih enot na navedbe
		if($settings['base'] == 1 && $settings['value_type'] == 0){
			$settings['value_type'] = 1;
		}
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);

		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){

			switch($settings['type']){
				
				// Povprecja
				/*case 0:
					$Test = self::createHorBars($DataSet, $spremenljivka);
				break;*/
				
				// Horizontalni stolpci
				case 0:
					$Test = self::createHorBars($DataSet, $spremenljivka, 1);
				break;
				
				// Navpicni stolpci
				case 1:
					$Test = self::createVerBars($DataSet, $spremenljivka, 1);
				break;
				
				// Sestavljeni stolpci - navpicni
				case 2:
					$Test = self::createVerStructBars($DataSet, $spremenljivka);
				break;
				
				// Sestavljeni stolpci - vodoravni
				case 3:
					$Test = self::createHorStructBars($DataSet, $spremenljivka);
				break;

				// Radar
				case 4:
					$Test = self::createRadar($DataSet, $spremenljivka, 1);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
				
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			
			if($settings['base'] == 1)
				echo '(r = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			else
				echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}
		
		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
                $_sequence = $_variable['sequence'];			
                
				if(is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
					self::outputOtherAnswers($oAnswers);
					echo '</div>';
					
					echo '<div class="chart_settings other_settings printHide iconHide">';
					self::displayOtherSettings($spid);
					echo '</div>';
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}
	
	static function displayVsotaChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
				
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
		
		// Ce smo ravnokar preklopili (je session se prazen) na pieChart vklopimo sortiranje
		if(self::$current_loop != 'undefined'){
			if((self::$sessionData[$spid][self::$current_loop]['type'] != 0 && $settings['type'] == 0) || (self::$sessionData[$spid][self::$current_loop]['type'] != 5 && $settings['type'] == 5)){
				$settings['sort'] = 1;
				self::$settings['sort'] = 1;
			}
		}
		else{
			if((self::$sessionData[$spid]['type'] != 0 && $settings['type'] == 0) || (self::$sessionData[$spid]['type'] != 5 && $settings['type'] == 5)){
				$settings['sort'] = 1;
				self::$settings['sort'] = 1;
			}
		}
		
		// Pri radarju ni sortiranja
		if($settings['type'] == 4){
			$settings['sort'] = 0;
			self::$settings['sort'] = 0;
		}
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
							
				// Pie chart - povprecja
				case 0:
					$Test = self::createPie($DataSet, $spremenljivka, $settings['show_legend']);
				break;	
				
				// 3D Pie chart - povprecja
				case 5:
					$Test = self::create3DPie($DataSet, $spremenljivka, $settings['show_legend']);
				break;	
				
				// Line chart
				case 1:
					$Test = self::createLine($DataSet, $spremenljivka);
				break;
				
				// Horizontal bars
				case 2:
					$Test = self::createHorBars($DataSet, $spremenljivka);
				break;
				
				// Vertical bars
				case 3:
					$Test = self::createVerBars($DataSet, $spremenljivka);
				break;
				
				// Radar
				case 4:
					$Test = self::createRadar($DataSet, $spremenljivka);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;

		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}
		
		echo '</div>';
	}
	
	static function displayRankingChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
			
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
		
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
			
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}	
		
		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		//$Cache->GetFromCache($ID,$DataSet->GetData());		

		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
				
				// Povprecja
				case 0:
					$Test = self::createHorBars($DataSet, $spremenljivka, $legend=0, $settings['noFixedScale']/*$fixedScale=1*/);
				break;
				
				// Sestavljeni stolpci - navpicni
				case 1:
					$Test = self::createHorStructBars($DataSet, $spremenljivka);
				break;
				
				// Sestavljeni stolpci - vodoravni
				case 2:
					$Test = self::createVerStructBars($DataSet, $spremenljivka);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
        $imgUrl = self::$baseImageUrl . $imgPath;
        
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
				
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}
		
		echo '</div>';
	}
	
	static function displayMultinumberChart($spid, $settings, $refresh=0){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];		
				
		// Napolnimo podatke za graf
		$DataSet = self::getDataSet($spid, $settings);
		
		// nimamo nobenih podatkov in imamo vklopljeno opcijo da ne prikazujemo praznih grafov - vrnemo 0
		if($DataSet == 0){
			self::displayEmptyWarning($spid);
			return;
		}

		// ni variabel v vprasanju preskocimo graf
		if($DataSet == -1){
			return;
		}
		
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'_loop_'.self::$current_loop.'"></div>';
		
		// Cache
		$Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
		
		$ID = self::generateChartId($spid, $settings, $DataSet->GetNumerus());
		
		// Ce se nimamo zgeneriranega grafa
		$refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : $refresh;
		if( (!$Cache->isInCache($ID, $DataSet->GetData())) || $refresh == 1 ){
			
			switch($settings['type']){
				
				// Povprecja - radar
				case 0:
					$Test = self::createRadar($DataSet, $spremenljivka, 1);
				break;
				
				// Povprecja - vertikalni stolpci
				case 1:
					$Test = self::createVerBars($DataSet, $spremenljivka, 1);
				break;
				
				// Povprecja - horizontalni stolpci
				case 2:
					$Test = self::createHorBars($DataSet, $spremenljivka, 1);
				break;
				
				// Povprecja - linijski graf
				case 3:
					$Test = self::createLine($DataSet, $spremenljivka, 1);
				break;
			}	
			
			// Shranimo v cache
			$Cache->WriteToCache($ID,$DataSet->GetData(),$Test);   			
		}
		
		// dobimo ime slike c cache-u
		$imgName = $Cache->GetHash($ID,$DataSet->GetData());
		if (self::$isArchive == false) {
			$imgPath = 'pChart/Cache/'.$imgName;
		} else {
			$imgPath = SAA_FOLDER.'/pChart/'.self::$anketa.'_'.self::$chartArchiveTime.'_'.$imgName;
			copy('pChart/Cache/'.$imgName, $imgPath);
		}
		$imgUrl = self::$baseImageUrl . $imgPath;
        
		// zapisemo ime slike v session za izvoze
		$settings['name'] = $imgName;
		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
				
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title;
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 0){
			echo '<span class="numerus">';
			echo '(n = '.$DataSet->GetNumerus()/*.self::$numerusText*/.')';
			echo '</span>';
		}
		echo '</div>';
		
		echo '<div class="chart_img" title="'.$lang['srv_chart_editirajspremenljivko'].'" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');" style="cursor:pointer">';	
		// dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
		echo 	'<img src="'.$imgUrl.'?'.time().'" />';		
		echo '</div>';
		
		echo '<div class="chart_settings printHide iconHide">';
		self::displaySingleSettings($spid, $settings);
		echo '</div>';
		
		// ce imamo vklopljen nuimerus pod grafom
		if(SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 3)
			self::displayBottomChartInfo($DataSet, $spremenljivka);
		
		if (self::$returnChartAsHtml == false) {
			flush(); ob_flush();
		}

		# izpišemo še tekstovne odgovore za polja drugo
		$_answersOther = $DataSet->GetOther();
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				
				$spid = $oAnswers['spid'];
				$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
                $_sequence = $_variable['sequence'];	
                		
				if(is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0){
					echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
					self::outputOtherAnswers($oAnswers);
					echo '</div>';
					
					echo '<div class="chart_settings other_settings printHide iconHide">';
					self::displayOtherSettings($spid);
					echo '</div>';
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}
	
	
	// Default nastavitve grafov
	public static function getDefaultSettings(){
		
		$colors = array_fill(0, 6, '');
		$limits = array('advanced_settings' => 0);
		
		$settings = array(
			'type' 			=> 0, 		// tip grafa
			'sort'			=> 0, 		// sortiranje po velikosti (0->brez, 1->padajoce, 2->narascajoce) 
										// ali MG (0->brez, 1->kategorije (trenutno), 2->povprecje, 3->prva ketegorija)
			'value_type' 	=> 0, 		// tip vrednosti (veljavni, frekvence, procenti...)
			'base'		 	=> 0, 		// checkbox / multicheckbox osnova (enote / navedbe)
			'show_legend' 	=> 0, 		// prikaz legende
			'scale_limit' 	=> 1, 		// zacni skalo z 0 / z najmanjso vrednostjo pri numericih ALI prikazi desno skalo pri semanticnem diferencialu
			'interval' 		=> 10, 		// stevilo intervalov pri numericih
			'min' 			=> '0', 	// minimalna vrednost po kateri delamo intervale pri numericih (max-min)/interval
			'max' 			=> '', 		// maximalna vrednost po kateri delamo intervale pri numericih (max-min)/interval
			'open_up' 		=> 0, 		// polodprt interval navzgor (ce so vrednosti nad max) pri numericih
			'open_down' 	=> 0, 		// polodprt interval navzdol (ce so vrednosti pod min) pri numericih
			'limits'		=> $limits, // napredne meje number grafov (custom intervali) - ce je $limits['advanced_settings']==1
			'radar_type' 	=> 0,		// tip radarja (crte / liki) 
			'radar_scale' 	=> 0,		// skala pri radarju (na osi / diagonalno)
			'3d_pie'		=> 0,		// tip kroznega grafa (navaden / 3d)
			'labelWidth' 	=> 50,		// sirina label (50% / 20%)
			'barLabel'	 	=> 0,		// prikaz label v stolpicnih grafih
			'barLabelSmall'	=> 1,		// prikaz label pod 5% v stolpicnih grafih (zraven stolpca)
			'rotate'	 	=> 0,		// obrnjeni gridi in variable (pri vseh MG - multiradio, multinumber...)
			'colors'		=> $colors,	// custom barve grafa
			'show_avg'	 	=> -1,		// prikaz povprecja na grafu (samo pri ordinalnih radio)
			'show_numerus'	=> -1,		// prikaz numerusa na grafu
			'otherType'		=> 0,		// poravnava other tabel
			'otherFreq'		=> 0,		// izpis frekvenc v other tabeli
			'hideEmptyVar'	=> 0,		// ali izpuscamo prazne opcije brez odgovora (ce je nad 20 variabel -> default 1)
			'noFixedScale'	=> 0,		// ce izklopimo skalo ki se zacne z 1 (samo pri multigrid povprecjih in ranking povprecjih) ALI prikazi polno skalo pri checkboxu (ce je 1)
		);

		return $settings;
	}
	
	// Zgeneriramo ID grafa za hash
	public static function generateChartId($spid, $settings, $numerus){
				
		// ce posebej prizgemo legendo pri pie chartu
		if($settings['show_legend'] == 1 && $settings['type'] == 2)
			$legend = '_legend';
		else
			$legend = '';

		$ID = self::$anketa.'_chart_'.$spid.'_mv_'.SurveyAnalysis::$missingProfileData['display_mv_type'];
		
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

		$ID .= '_skin_'.self::$skin;
		
		$ID .= '_numerus_'.$numerus.'_numerusText_'.SurveyDataSettingProfiles :: getSetting('chartNumerusText');
		
		$ID .= '_chartAvgText_'.SurveyDataSettingProfiles :: getSetting('chartAvgText');

		$ID .= '_pieZeros_'.SurveyDataSettingProfiles :: getSetting('chartPieZeros');
		
		$ID .= '_chartFontSize_'.SurveyDataSettingProfiles :: getSetting('chartFontSize');
		
		$ID .= '_hq_'.self::$quality;
		
		return $ID;
	}
	
	// nastavimo prave barve ustrezne skinu
	public static function setChartColors($chart, $skin){

		// Ce nimmo posebej nastavljenih barv
		if(self::$settings['colors'][0] == ''){
		
			// ce je nastavljen globalen custom skin
			if(is_numeric($skin)){
				$skin = self::getCustomSkin($skin);
				$colors = explode('_', $skin['colors']);
				
				$count = 0;
				foreach($colors as $color){

					$rgb = self::html2rgb($color);
					$chart->setColorPalette($count,$rgb[0],$rgb[1],$rgb[2]);
					$chart->setColorPalette($count+7,$rgb[0]+50,$rgb[1]+50,$rgb[2]+50);
					$chart->setColorPalette($count+14,$rgb[0]+100,$rgb[1]+100,$rgb[2]+100);
					
					$count++;
				}
			}
			
			// imamo nastavljenega enega od default skinov
			else{
				switch ($skin){

                    // nov 1ka default skin
                    case '1ka':
                    default:
						$chart->setColorPalette(0,30,136,229);
						$chart->setColorPalette(1,255,166,8);
						$chart->setColorPalette(2,72,229,194);
						$chart->setColorPalette(3,242,87,87);
						$chart->setColorPalette(4,117,70,68);
						$chart->setColorPalette(5,248,202,0);
						$chart->setColorPalette(6,255,112,166);
						
                        $chart->setColorPalette(7,63,81,180);
                        $chart->setColorPalette(8,76,174,80);                        
                        $chart->setColorPalette(9,204,219,57);                       
                        $chart->setColorPalette(10,255,235,59);              
                        $chart->setColorPalette(11,0,149,135);                       
                        $chart->setColorPalette(12,121,85,72);                       
                        $chart->setColorPalette(13,157,157,157);                        
                        $chart->setColorPalette(14,96,125,138);                       
                        $chart->setColorPalette(15,155,39,175);                        
                        $chart->setColorPalette(16,103,58,182);                       
                        $chart->setColorPalette(17,255,255,103);                     
						$chart->setColorPalette(18,255,249,100);
						$chart->setColorPalette(19,100,255,255);
						$chart->setColorPalette(20,255,100,255);
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
						
						$chart->setColorPalette(7,255,59,63);
						$chart->setColorPalette(8,54,73,255);
						$chart->setColorPalette(9,50,255,58);
						$chart->setColorPalette(10,255,255,53);
						$chart->setColorPalette(11,255,199,35);
						$chart->setColorPalette(12,50,255,255);
						$chart->setColorPalette(13,216,50,255);
						$chart->setColorPalette(14,255,109,113);
						$chart->setColorPalette(15,104,123,255);
						$chart->setColorPalette(16,100,255,108);
						$chart->setColorPalette(17,255,255,103);
						$chart->setColorPalette(18,255,249,100);
						$chart->setColorPalette(19,100,255,255);
						$chart->setColorPalette(20,255,100,255);
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
						
						$chart->setColorPalette(7,238,255,96);
						$chart->setColorPalette(8,255,150,96);
						$chart->setColorPalette(9,255,255,96);
						$chart->setColorPalette(10,96,201,255);
						$chart->setColorPalette(11,226,96,255);
						$chart->setColorPalette(12,255,96,167);
						$chart->setColorPalette(13,142,255,96);
						$chart->setColorPalette(14,255,255,146);
						$chart->setColorPalette(15,255,200,146);
						$chart->setColorPalette(16,255,255,146);
						$chart->setColorPalette(17,146,251,255);
						$chart->setColorPalette(18,255,146,255);
						$chart->setColorPalette(19,255,146,217);
						$chart->setColorPalette(20,192,255,146);
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
						
						$chart->setColorPalette(7,129,179,239);
						$chart->setColorPalette(8,242,130,127);
						$chart->setColorPalette(9,205,237,139);
						$chart->setColorPalette(10,178,150,212);
						$chart->setColorPalette(11,125,222,248);
						$chart->setColorPalette(12,255,200,120);
						$chart->setColorPalette(13,196,219,255);
						$chart->setColorPalette(14,179,229,255);
						$chart->setColorPalette(15,255,180,177);
						$chart->setColorPalette(16,255,255,189);
						$chart->setColorPalette(17,228,200,255);
						$chart->setColorPalette(18,175,255,255);
						$chart->setColorPalette(19,255,250,170);
						$chart->setColorPalette(20,226,255,255);
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
						
						$chart->setColorPalette(7,171,209,61);
						$chart->setColorPalette(8,255,211,87);
						$chart->setColorPalette(9,196,150,240);
						$chart->setColorPalette(10,74,182,182);
						$chart->setColorPalette(11,126,255,189);
						$chart->setColorPalette(12,188,186,85);
						$chart->setColorPalette(13,158,203,255);
						$chart->setColorPalette(14,221,255,111);
						$chart->setColorPalette(15,255,255,137);
						$chart->setColorPalette(16,246,200,255);
						$chart->setColorPalette(17,124,232,255);
						$chart->setColorPalette(18,176,255,239);
						$chart->setColorPalette(19,238,236,135);
						$chart->setColorPalette(20,208,253,255);
						break;
						
					// zelen skin
					case 'green':
						$chart->createColorGradientPalette(168,188,56,248,255,136,5);
						$chart->setColorPalette(5,255,255,0);
						$chart->setColorPalette(6,232,3,182);
						
						$chart->setColorPalette(7,$chart->Palette['0']['R'],$chart->Palette['0']['G'],$chart->Palette['0']['B']);
						$chart->setColorPalette(8,$chart->Palette['1']['R'],$chart->Palette['1']['G'],$chart->Palette['1']['B']);
						$chart->setColorPalette(9,$chart->Palette['2']['R'],$chart->Palette['2']['G'],$chart->Palette['2']['B']);
						$chart->setColorPalette(10,$chart->Palette['3']['R'],$chart->Palette['3']['G'],$chart->Palette['3']['B']);
						$chart->setColorPalette(11,$chart->Palette['4']['R'],$chart->Palette['4']['G'],$chart->Palette['4']['B']);
						$chart->setColorPalette(12,$chart->Palette['5']['R'],$chart->Palette['5']['G'],$chart->Palette['5']['B']);
						$chart->setColorPalette(13,$chart->Palette['6']['R'],$chart->Palette['6']['G'],$chart->Palette['6']['B']);
						$chart->setColorPalette(14,$chart->Palette['0']['R'],$chart->Palette['0']['G'],$chart->Palette['0']['B']);
						$chart->setColorPalette(15,$chart->Palette['1']['R'],$chart->Palette['1']['G'],$chart->Palette['1']['B']);
						$chart->setColorPalette(16,$chart->Palette['2']['R'],$chart->Palette['2']['G'],$chart->Palette['2']['B']);
						$chart->setColorPalette(17,$chart->Palette['3']['R'],$chart->Palette['3']['G'],$chart->Palette['3']['B']);
						$chart->setColorPalette(18,$chart->Palette['4']['R'],$chart->Palette['4']['G'],$chart->Palette['4']['B']);
						$chart->setColorPalette(19,$chart->Palette['5']['R'],$chart->Palette['5']['G'],$chart->Palette['5']['B']);
						$chart->setColorPalette(20,$chart->Palette['6']['R'],$chart->Palette['6']['G'],$chart->Palette['6']['B']);
						break;
						
					// moder skin
					case 'blue':
                        //$chart->createColorGradientPalette(82,124,148,174,216,240,5);
                        $chart->setColorPalette(0,30,136,229);
                        $chart->setColorPalette(1,59,151,234);
                        $chart->setColorPalette(2,110,166,238);
                        $chart->setColorPalette(3,137,181,243);
                        $chart->setColorPalette(4,162,196,247);
						$chart->setColorPalette(5,186,211,251);
						$chart->setColorPalette(6,209,227,255);
						
						$chart->setColorPalette(7,$chart->Palette['0']['R'],$chart->Palette['0']['G'],$chart->Palette['0']['B']);
						$chart->setColorPalette(8,$chart->Palette['1']['R'],$chart->Palette['1']['G'],$chart->Palette['1']['B']);
						$chart->setColorPalette(9,$chart->Palette['2']['R'],$chart->Palette['2']['G'],$chart->Palette['2']['B']);
						$chart->setColorPalette(10,$chart->Palette['3']['R'],$chart->Palette['3']['G'],$chart->Palette['3']['B']);
						$chart->setColorPalette(11,$chart->Palette['4']['R'],$chart->Palette['4']['G'],$chart->Palette['4']['B']);
						$chart->setColorPalette(12,$chart->Palette['5']['R'],$chart->Palette['5']['G'],$chart->Palette['5']['B']);
						$chart->setColorPalette(13,$chart->Palette['6']['R'],$chart->Palette['6']['G'],$chart->Palette['6']['B']);
						$chart->setColorPalette(14,$chart->Palette['0']['R'],$chart->Palette['0']['G'],$chart->Palette['0']['B']);
						$chart->setColorPalette(15,$chart->Palette['1']['R'],$chart->Palette['1']['G'],$chart->Palette['1']['B']);
						$chart->setColorPalette(16,$chart->Palette['2']['R'],$chart->Palette['2']['G'],$chart->Palette['2']['B']);
						$chart->setColorPalette(17,$chart->Palette['3']['R'],$chart->Palette['3']['G'],$chart->Palette['3']['B']);
						$chart->setColorPalette(18,$chart->Palette['4']['R'],$chart->Palette['4']['G'],$chart->Palette['4']['B']);
						$chart->setColorPalette(19,$chart->Palette['5']['R'],$chart->Palette['5']['G'],$chart->Palette['5']['B']);
						$chart->setColorPalette(20,$chart->Palette['6']['R'],$chart->Palette['6']['G'],$chart->Palette['6']['B']);
						break;
						
					// rdeč skin
					case 'red':
						$chart->createColorGradientPalette(255,0,0,80,10,10,5);
						$chart->setColorPalette(5,255,255,0);
						$chart->setColorPalette(6,232,3,182);
						
						$chart->setColorPalette(7,$chart->Palette['0']['R'],$chart->Palette['0']['G'],$chart->Palette['0']['B']);
						$chart->setColorPalette(8,$chart->Palette['1']['R'],$chart->Palette['1']['G'],$chart->Palette['1']['B']);
						$chart->setColorPalette(9,$chart->Palette['2']['R'],$chart->Palette['2']['G'],$chart->Palette['2']['B']);
						$chart->setColorPalette(10,$chart->Palette['3']['R'],$chart->Palette['3']['G'],$chart->Palette['3']['B']);
						$chart->setColorPalette(11,$chart->Palette['4']['R'],$chart->Palette['4']['G'],$chart->Palette['4']['B']);
						$chart->setColorPalette(12,$chart->Palette['5']['R'],$chart->Palette['5']['G'],$chart->Palette['5']['B']);
						$chart->setColorPalette(13,$chart->Palette['6']['R'],$chart->Palette['6']['G'],$chart->Palette['6']['B']);
						$chart->setColorPalette(14,$chart->Palette['0']['R'],$chart->Palette['0']['G'],$chart->Palette['0']['B']);
						$chart->setColorPalette(15,$chart->Palette['1']['R'],$chart->Palette['1']['G'],$chart->Palette['1']['B']);
						$chart->setColorPalette(16,$chart->Palette['2']['R'],$chart->Palette['2']['G'],$chart->Palette['2']['B']);
						$chart->setColorPalette(17,$chart->Palette['3']['R'],$chart->Palette['3']['G'],$chart->Palette['3']['B']);
						$chart->setColorPalette(18,$chart->Palette['4']['R'],$chart->Palette['4']['G'],$chart->Palette['4']['B']);
						$chart->setColorPalette(19,$chart->Palette['5']['R'],$chart->Palette['5']['G'],$chart->Palette['5']['B']);
						$chart->setColorPalette(20,$chart->Palette['6']['R'],$chart->Palette['6']['G'],$chart->Palette['6']['B']);
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
		
		// Graf ima posebej nastavljen skin
		else{
			for($i=0; $i<7; $i++){
				
				$color = self::$settings['colors'][$i];
				$rgb = self::html2rgb($color);
				
				$chart->setColorPalette($i,$rgb[0],$rgb[1],$rgb[2]);
				$chart->setColorPalette($i+7,$rgb[0]+50,$rgb[1]+50,$rgb[2]+50);
				$chart->setColorPalette($i+14,$rgb[0]+100,$rgb[1]+100,$rgb[2]+100);
			}
		}
		
		return $chart;
	}
	
	public static function getDefaultColors($skin){

		// ce je nastavljen globalen custom skin
		if(is_numeric($skin)){
			$skin = self::getCustomSkin($skin);
			$default_colors = explode('_', $skin['colors']);
		}
		
		else{
			switch($skin){
                case '1ka':	
					$default_colors = array(0=>'#1e88e5', 1=>'#ffa608', 2=>'#48e5c2', 3=>'#f25757', 4=>'#754668', 5=>'#f8ca00', 6=>'#ff70a6');
					break;
				case 'lively':	
					$default_colors = array(0=>'#e9090d', 1=>'#0417e3', 2=>'#00ff08', 3=>'#fff703', 4=>'#ff9500', 5=>'#00fbff', 6=>'#a600ff');
					break;
				case 'mild':	
					$default_colors = array(0=>'#bce02e', 1=>'#e0642e', 2=>'#e0d62e', 3=>'#2e97e0', 4=>'#b02ee0', 5=>'#00fbff', 6=>'#5ce02e');
					break;
				case 'office':	
					$default_colors = array(0=>'#4f81bd', 1=>'#c0504d', 2=>'#9bbb59', 3=>'#8064a2', 4=>'#4bacc6', 5=>'#f79646', 6=>'#92a9cf');
					break;
				case 'pastel':	
					$default_colors = array(0=>'#799f0b', 1=>'#d7a125', 2=>'#9264be', 3=>'#188484', 4=>'#4cc68b', 5=>'#8a8823', 6=>'#6c99d2');
					break;
				case 'green':	
					$default_colors = array(0=>'#a8bc38', 1=>'#b8c948', 2=>'#c8d658', 3=>'#d8e468', 4=>'#e8e178', 5=>'#ffff00', 6=>'#e803b6');
					break;
				case 'blue':	
					$default_colors = array(0=>'#1e88e5', 1=>'#4f97ea', 2=>'#6ea6ee', 3=>'#89b5f3', 4=>'#a2c4f7', 5=>'#bad3fb', 6=>'#d1e3ff');
					break;
				case 'red':	
					$default_colors = array(0=>'#ff0000', 1=>'#dc0202', 2=>'#b90404', 3=>'#960606', 4=>'#730808', 5=>'#ffff00', 6=>'#e803b6');
					break;
				case 'multi':	
					$default_colors = array(0=>'#8c0000', 1=>'#f00800', 2=>'#ff8a82', 3=>'#f2c4c8', 4=>'#0b0387', 5=>'#0400fc', 6=>'#9794f2');
					break;
			}
		}
		
		return $default_colors;
	}
	
	public static function html2rgb($color){
	
		if ($color[0] == '#')
			$color = substr($color, 1);

		if (strlen($color) == 6)
			list($r, $g, $b) = array($color[0].$color[1],
									 $color[2].$color[3],
									 $color[4].$color[5]);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		else
			return false;

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return array($r, $g, $b);
	}
	
	
	// Napolnimo podatke za posamezen graf
	public static function getDataSet($spid, $settings){
		global $site_path;
		global $lang;

		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		$dataArray = array();
		$fullPercent = 0;
		self::$numerusText = '';
		$_answersOther = array();
		
		$emptyData = true;
		
		// napolnimo podatke za DROPDOWN, ki ima samo numeric variable
		if($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)){

			$dataArray = array();
					
			$i=0;
			$N = 0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				
				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);
			
				$avg_count = 0;
				$avg_sum = 0;
			
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				
					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
				
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21)))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
							
							$N = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
							
							foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									
									$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
									$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;								
								
									$tempArray = array();
									
									$tempArray['freq'] = $vAnswer['cnt'];
									
									$tempArray['percent'] = $_percent;
									$tempArray['valid'] = $_valid;
									
									$tempArray['key'] = $vkey;																	
																			
									// ce je znotraj nastavljenih mej
									if( ($settings['max'] == '' || ($settings['open_up'] == 1 || (int)$vAnswer['text'] <= (int)$settings['max']))
										&& ($settings['min'] == '' || ($settings['open_down'] == 1 || (int)$vAnswer['text'] >= (int)$settings['min'])) ){

										$avg_count += $vAnswer['cnt'];
										$avg_sum += $vAnswer['cnt'] * (int)$vAnswer['text'];
									}
									else{
										$N -= $vAnswer['cnt'];
									}
									
									// nastavimo da graf ni prazen
									$emptyData = false;
									
									$text = $spremenljivka['edit_graf'] == 0 ? $vAnswer['text'] : $vAnswer['text_graf'];
									$tempArray['variable'] = $text;

									$dataArray[] = $tempArray;
								}
							}
						}				
					}
				}
				
				$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
				if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
						if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
							$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
							
							$tempArray = array();
							
							$tempArray['freq'] = $iAnswer['cnt'];
							
							//$N = ($settings['value_type'] == 0) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
							
							$tempArray['percent'] = $_percent;
							$tempArray['valid'] = $_invalid;
							
							$tempArray['key'] = $ikey;
							$tempArray['variable'] = $iAnswer['text'];
						
							$dataArray[] = $tempArray;
						}
					}
				}	
				
				$i++;
			}
			
			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;
			
			//polnimo podatke
			$DataSet = new pData;
			
			//nastavimo numerus, ki se izpise pod legendo
			$N = ((int)$N > 0) ? $N : 0;
			$DataSet->SetNumerus($N);
			
			// nastavimo POVPRECJE		
			$avg = ($avg_count > 0) ? $avg_sum / $avg_count : 0;
			$DataSet->SetAverage(round($avg, 1));
			
			
			// Sortiramo podatke - ce imamo izpis vsakega vnosa posebej sortiramo po freq, ce pa po skupinah pa po key
			if($settings['type'] > 4){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['key']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}
			elseif($settings['sort'] == 1){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			$max = (int)$dataArray[count($dataArray,0)-1]['variable'];
			$min = (int)$dataArray[0]['variable'];
			$stIntervalov = ((int)$settings['interval'] == 0 ? 10 : (int)$settings['interval']);

			
			// Ce imamo napredno napredne intervale
			if($settings['limits']['advanced_settings'] == 1){
				$limits = $settings['limits'];
				
				$max = $limits['interval_'. ($stIntervalov-1) ]['max'];
				$min = $limits['interval_0']['min'];
			}
			// Ce imamo osnovne intervale
			else{
				// Nastavimo custom zgornjo mejo skale (razen v primeru ko ne ignoriramo vrednosti ki padejo ven in ce je max vnos vecji od nastavljenega max)
				if($settings['max'] != '' /*&& ($settings['open_up'] == 0 || (int)$settings['max'] > $max)*/)
					$max = (int)$settings['max'];
				// Nastavimo custom spodnjo mejo skale (razen v primeru ko ne ignoriramo vrednosti ki padejo ven in ce je min vnos manjsi od nastavljenega min)			
				if($settings['min'] != '' /*&& ($settings['open_down'] == 0 || (int)$settings['min'] < $min)*/)
					$min = (int)$settings['min'];

				$stIntervalov = ($stIntervalov == -1 ? $max-$min : $stIntervalov);
				$part = ($max-$min) / $stIntervalov;
				$part = ($part < 1) ? 1 : round($part);
			}
			
			
			// Poberemo podatke v posamezne tabele - po intervalih oz normalno
			if($settings['type'] > 4){
				
				// Ce imamo polodprt intrerval navzdol
				if($settings['open_down'] == 1){
					$count = 0;
					$percent = 0;
					$valid = 0;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i]['variable'] < $min){
							$count += $dataArray[$i]['freq'];
							$percent += $dataArray[$i]['percent'];
							$valid += $dataArray[$i]['valid'];
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $count;
					$vrednostiPercent[] = $percent;
					$vrednostiValid[] = $valid;
					$vrednostiKey[] = $lang['srv_chart_less'].' '.$min;
					$vrednostiVariable[] = $lang['srv_chart_less'].' '.$min;
				}
				
				// loop cez intervale - default 10
				for($interval=0; $interval<$stIntervalov; $interval++){
				
					$count = 0;
					$percent = 0;
					$valid = 0;
					
					// Ce imamo napredno napredne intervale (custom dolocene)
					if($settings['limits']['advanced_settings'] == 1){
						$maxVal = $limits['interval_'.$interval]['max'];
						$minVal = $limits['interval_'.$interval]['min'];
					}
					// Ce imamo osnovne intervale (racunamo sproti)
					else{
						$maxVal = ($interval < ($stIntervalov-1) ? $min + (($interval+1) * $part) : $max);
						$minVal = ($interval > 0 ? $min + ($interval * $part) + 1 : $min);
					}
					
					// prekinemo ce zaradi zaokrozevanja pride do min > max
					if($minVal > $maxVal)
						break;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i]['variable'] <= $maxVal && $dataArray[$i]['variable'] >= $minVal && $dataArray[$i]['field'] == 0){
							$count += $dataArray[$i]['freq'];
							$percent += $dataArray[$i]['percent'];
							$valid += $dataArray[$i]['valid'];
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $count;
					$vrednostiPercent[] = $percent;
					$vrednostiValid[] = $valid;
		
					// Ce imamo napredne intervale (custom dolocene labele)
					if($settings['limits']['advanced_settings'] == 1 && $limits['interval_'.$interval]['label'] != ''){
						$vrednostiKey[] = $limits['interval_'.$interval]['label'];
						$vrednostiVariable[] = $limits['interval_'.$interval]['label'];
					}
					elseif($minVal == $maxVal){
						$vrednostiKey[] = $minVal;
						$vrednostiVariable[] = $minVal;
					}
					else{
						$vrednostiKey[] = $minVal.'-'.$maxVal;
						$vrednostiVariable[] = $minVal.'-'.$maxVal;
					}
				}
				
				// Ce imamo polodprt intrerval navzgor
				if($settings['open_up'] == 1){
					$count = 0;
					$percent = 0;
					$valid = 0;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i]['variable'] > $max){
							$count += $dataArray[$i]['freq'];
							$percent += $dataArray[$i]['percent'];
							$valid += $dataArray[$i]['valid'];
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $count;
					$vrednostiPercent[] = $percent;
					$vrednostiValid[] = $valid;
					$vrednostiKey[] = $lang['srv_chart_more'].' '.$max;
					$vrednostiVariable[] = $lang['srv_chart_more'].' '.$max;
				}
			}
			
			else{
				for($i=0; $i<count($dataArray,0); $i++){
					
					if($dataArray[$i]['field'] == 0){
						$vrednosti[] = $dataArray[$i]['freq'];
						$vrednostiPercent[] = $dataArray[$i]['percent'];
						$vrednostiValid[] = $dataArray[$i]['valid'];
					}
					else{
						$vrednosti[] = 0;
						$vrednostiPercent[] = 0;
						$vrednostiValid[] = 0;
					}
					
					$vrednostiKey[] = $dataArray[$i]['key'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];	
				}
			}
					
			if(count($vrednosti) > 0){
				if($settings['value_type'] == 0){
					$DataSet->AddPoint($vrednosti,'Vrednosti');
				}
				elseif($settings['value_type'] == 1){
					$DataSet->AddPoint($vrednostiPercent,'Vrednosti');
				}
				elseif($settings['value_type'] == 2){
					$DataSet->AddPoint($vrednostiValid,'Vrednosti');
				}
			}
			else
				$DataSet->AddPoint(array(0),'Vrednosti');
			
			$DataSet->AddSerie('Vrednosti');
			$var_title[0] = ($var_title[0] == '' ? 'Vrednosti' : $var_title[0]);
			$DataSet->SetSerieName($var_title[0],'Vrednosti');
			
			// Vedno izpisemo cela imena variabel
			$DataSet->AddPoint($vrednostiVariable,"Variable");
			//$DataSet->AddPoint($vrednostiKey,"Variable");
				
			$DataSet->SetAbsciseLabelSerie("Variable");
			
			if($settings['value_type'] > 0){
				$DataSet->SetYAxisUnit("%");
				$DataSet->SetYAxisFormat("number");
			}
		}
		
		// napolnimo podatke za RADIO, DROPDOWN
		elseif($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3){

			$dataArray = array();
					
			$i=0;
			$N = 0;
			$N_average = 0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				
				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);
			
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
				
					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
				
					$_sequence = $variable['sequence'];	# id kolone z podatki
					
					// Ce skrivamo prazne vrednosti
					if($settings['hideEmptyVar'] == 1){
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $key => $valid) {
							if ((int)$valid['cnt'] == 0) {
								unset (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]);
							}
						}
					}
					
					if (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21)))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
                            
                            foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0

									$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
									$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;								
								
									$tempArray = array();
									
									$tempArray['freq'] = $vAnswer['cnt'];
									
									// nastavimo da graf ni prazen
									if($vAnswer['cnt'] > 0)
										$emptyData = false;
									
									$N = ($settings['value_type'] == 0) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
									
									$tempArray['percent'] = $_percent;
									$tempArray['valid'] = $_valid;
									
									$tempArray['key'] = $vkey;

									$text = $spremenljivka['edit_graf'] == 0 ? $vAnswer['text'] : $vAnswer['text_graf'];
									$tempArray['variable'] = $text;

									$fullPercent += $tempArray['percent'];
									
									// ce imamo vklopljeno da izpuscamo 0 in prikazujemo pie chart spustimo nicelne vrednosti
									if($_valid != 0 || SurveyDataSettingProfiles :: getSetting('chartPieZeros') == 1 || ($settings['type'] != 2 && $settings['type'] != 8))
										$dataArray[] = $tempArray;
										
									// Ce je ordinalen racunamo povprecje	
									if($spremenljivka['skala'] != 1){
										$xi = (int)$vkey;
										$fi = (int)$vAnswer['cnt'];
										
										$sum_xi_fi += $xi * $fi;
										$N_average += $fi;
									}
								}
							}
						}				
					}
					// polnimo array za drugo
					else{
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
				}
				
				$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
				if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
						if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
							$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
							
							$tempArray = array();
							
							$tempArray['freq'] = $iAnswer['cnt'];
							
							$N = ($settings['value_type'] == 0) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
							
							$tempArray['percent'] = $_percent;
							$tempArray['valid'] = $_invalid;
							
							$tempArray['key'] = $ikey;
							$tempArray['variable'] = $iAnswer['text'];
							
							$fullPercent += $tempArray['percent'];
							
							// ce imamo vklopljeno da izpuscamo 0 in prikazujemo pie chart spustimo nicelne vrednosti
							if($_invalid != 0 || SurveyDataSettingProfiles :: getSetting('chartPieZeros') == 1 || ($settings['type'] != 2 && $settings['type'] != 8))
								$dataArray[] = $tempArray;
						}
					}
				}	
				
				$i++;
			}
			
			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;
			
			//polnimo podatke
			$DataSet = new pData;
			
			//nastavimo numerus, ki se izpise pod legendo
			$N = ((int)$N > 0) ? $N : 0;
			$DataSet->SetNumerus($N);
			self::$numerusText = ($settings['value_type'] == 0) ? ' ('.$lang['srv_analiza_frekvence_titleVeljavni'].')' : '';
			
			//nastavimo povprecje ce je ordinalen
			if($spremenljivka['skala'] != 1){			
				$avg = ($N_average > 0) ? $sum_xi_fi / $N_average : 0;
				$DataSet->SetAverage(round($avg, 1));
			}
			
			// Sortiramo podatke ce je potrebno
			if($settings['sort'] == 1){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			// Poberemo podatke v posamezne tabele
			for($i=0; $i<count($dataArray,0); $i++){			

				$vrednosti[] = $dataArray[$i]['freq'];
				$vrednostiPercent[] = $dataArray[$i]['percent'];
				$vrednostiValid[] = $dataArray[$i]['valid'];
		
				$vrednostiKey[] = $dataArray[$i]['key'];
				$vrednostiVariable[] = $dataArray[$i]['variable'];
			}
			
			
			if(is_countable($vrednosti) && count($vrednosti) > 0){
				if($settings['type'] < 3 || $settings['type'] == 8){
					if($settings['value_type'] == 1){
						$DataSet->AddPoint($vrednosti,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_freq']);
					}
					elseif($settings['value_type'] == 2){
						$DataSet->AddPoint($vrednostiPercent,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_percent']);
					}
					elseif($settings['value_type'] == 0){
						$DataSet->AddPoint($vrednostiValid,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_valid']);
					}
					
					$DataSet->AddSerie('Vrednosti');
					$DataSet->SetSerieName('Frekvence','Vrednosti');
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($vrednostiVariable,"Variable");
					//$DataSet->AddPoint($vrednostiKey,"Variable");
				}
				// Graf povprecja
				elseif($settings['type'] == 9){					
					$DataSet->AddPoint(round($avg, 1),'Vrednosti');
					
					$DataSet->AddSerie('Vrednosti');
					$DataSet->SetSerieName('Frekvence','Vrednosti');
				}
				else{
					for($i=0; $i<count($vrednosti); $i++){
						if($settings['value_type'] == 1){
							$DataSet->AddPoint($vrednosti[$i],'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_freq']);
						}
						elseif($settings['value_type'] == 2){
							$DataSet->AddPoint($vrednostiPercent[$i],'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_percent']);
						}
						elseif($settings['value_type'] == 0){
							$DataSet->AddPoint($vrednostiValid[$i],'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_valid']);
						}
						
						$DataSet->AddSerie('Vrednosti_'.$i);
						$DataSet->SetSerieName($vrednostiVariable[$i],'Vrednosti_'.$i);
					}
					
					$DataSet->AddPoint('','Variable');
					//$DataSet->AddPoint($vrednostiKey,"Variable");
				}
			}
			else
				$DataSet->AddPoint(array(0),'Vrednosti');

			$DataSet->SetAbsciseLabelSerie('Variable');		
			
			if($settings['value_type'] != 1 && $settings['type'] != 9){
				$DataSet->SetYAxisUnit("%");
				$DataSet->SetYAxisFormat("number");
			}
		}

		// napolnimo podatke za CHECKBOX
		elseif($spremenljivka['tip'] == 2){
			$dataArray = array();
			$fullPercent = 0;
		
			$i=0;
			$nValid = 0;
			$nAll = 0;
			$nNavedbe = 0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		

				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);
			
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){	
					
					if ($variable['text'] != true && $variable['other'] != true){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						
						$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
										
						$vAnswer = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][1]['cnt'];
						$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
						$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;					
						
						$tempArray = array();
										
						$tempArray['freq'] = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][1]['cnt'];
						
						// nastavimo da graf ni prazen
						if($tempArray['freq'] > 0)
							$emptyData = false;

						$nValid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > $nValid) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : $nValid;
						$nAll = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
						$nNavedbe += (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][1]['cnt'];
						
						$tempArray['percent'] = $_percent;
						$tempArray['valid'] = $_valid;
						
						$tempArray['key'] = $variable['variable'];
						
						$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
						$tempArray['variable'] = $text;
						
						$fullPercent += $tempArray['percent'];
						
						// ce imamo vklopljeno da izpuscamo 0 spustimo nicelne vrednosti
						if(($_valid != 0 || SurveyDataSettingProfiles :: getSetting('chartPieZeros') == 1 || ($settings['type'] != 2 && $settings['type'] != 7))
							&& ((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] > 0) || $settings['hideEmptyVar'] == 0)
							$dataArray[] = $tempArray;
					}					
					// polnimo array za drugo
					else{
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
				}
				
				$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
				if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
					
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
						if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
							
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
							$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
							
							$tempArray = array();
							
							$tempArray['freq'] = (int)$iAnswer['cnt'];
							
							//$nValid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'];
							$nAll = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
							$nNavedbe += (int)$iAnswer['cnt'];
							
							$tempArray['percent'] = $_percent;
							$tempArray['valid'] = $_invalid;
							
							$tempArray['key'] = $ikey;
							$tempArray['variable'] = $iAnswer['text'];
							
							$fullPercent += $tempArray['percent'];
							
							// ce imamo vklopljeno da izpuscamo 0 in prikazujemo pie chart spustimo nicelne vrednosti
							if($_invalid != 0 || SurveyDataSettingProfiles :: getSetting('chartPieZeros') == 1 || ($settings['type'] != 2 && $settings['type'] != 7))
								$dataArray[] = $tempArray;
						}
					}
				}	
				
				$i++;
			}	

			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;
					
			//polnimo podatke
			$DataSet = new pData;
			
			// Sortiramo podaatke ce je potrebno
			if($settings['sort'] == 1){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			// Poberemo podatke v posamezne tabele
			for($i=0; $i<count($dataArray,0); $i++){			
				
				if($settings['base'] == 0){
				
					//nastavimo numerus, ki se izpise pod legendo
					if($settings['value_type'] == 0){
						$numerus = $nValid;
						self::$numerusText = ' ('.$lang['srv_analiza_frekvence_titleVeljavni'].')';
					}
					else
						$numerus = $nAll;
						
					$numerus = ((int)$numerus > 0) ? $numerus : 0;
					$DataSet->SetNumerus($numerus);
					
					//$valid = ($fullPercent * $dataArray[$i]['percent'] > 0) ? 100 / $fullPercent * $dataArray[$i]['percent'] : 0;
					$valid = ($nValid > 0 ) ? $dataArray[$i]['freq'] * 100 / $nValid : 0;
				
					$vrednosti[] = $dataArray[$i]['freq'];
					$vrednostiPercent[] = $dataArray[$i]['percent'];
					$vrednostiValid[] = $valid;
			
					$vrednostiKey[] = $dataArray[$i]['key'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];
					
					
				}
				else{
					//nastavimo numerus, ki se izpise pod legendo
					$nNavedbe = ((int)$nNavedbe > 0) ? $nNavedbe : 0;
					$DataSet->SetNumerus($nNavedbe);
					self::$numerusText = ' ('.$lang['srv_analiza_opisne_arguments'].')';
					
					$percent = ($fullPercent * $dataArray[$i]['percent'] > 0) ? 100 / $fullPercent * $dataArray[$i]['percent'] : 0;

					$vrednosti[] = $dataArray[$i]['freq'];
					$vrednostiPercent[] = $percent;	
					$vrednostiValid[] = $percent;

					$vrednostiKey[] = $dataArray[$i]['key'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];
				}
			}
			
			if(count($vrednosti) > 0){
				if($settings['type'] < 3 || $settings['type'] == 5 || $settings['type'] == 6 || $settings['type'] == 7){
					if($settings['value_type'] == 1 || ($settings['value_type'] == 0 && $settings['base'] == 1)){
						$DataSet->AddPoint($vrednosti,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_freq']);
					}
					elseif($settings['value_type'] == 2){
						$DataSet->AddPoint($vrednostiPercent,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_percent']);
					}
					elseif($settings['value_type'] == 0){
						$DataSet->AddPoint($vrednostiValid,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_valid']);
					}
					
					$DataSet->AddSerie('Vrednosti');
					$DataSet->SetSerieName('Frekvence','Vrednosti');
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($vrednostiVariable,"Variable");
					//$DataSet->AddPoint($vrednostiKey,"Variable");
				}
				else{
					for($i=0; $i<count($vrednosti); $i++){
						if($settings['value_type'] == 1){
							$DataSet->AddPoint($vrednosti[$i],'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_freq']);
						}
						elseif($settings['value_type'] == 2){
							$DataSet->AddPoint($vrednostiPercent[$i],'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_percent']);
						}
						elseif($settings['value_type'] == 0){
							$DataSet->AddPoint($vrednostiValid[$i],'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_valid']);
						}
						
						$DataSet->AddSerie('Vrednosti_'.$i);
						$DataSet->SetSerieName($vrednostiVariable[$i],'Vrednosti_'.$i);
					}
					
					$DataSet->AddPoint('','Variable');
					//$DataSet->AddPoint($vrednostiKey,"Variable");
				}
			}
			else
				$DataSet->AddPoint(array(0),'Vrednosti');

			$DataSet->SetAbsciseLabelSerie('Variable');
			
			if(($settings['value_type'] == 0 && $settings['base'] == 0) || $settings['value_type'] == 2){
				$DataSet->SetYAxisUnit("%");
				$DataSet->SetYAxisFormat("number");
			}
		}
		
		// napolnimo podatke za DVOJNI MULTIGRID
		elseif($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
			$DataSet = new pData;	
			$dataArray = array();
			$fullPercent = array();

			$gridCount=0;
			$sql = sisplet_query("SELECT count(*) AS count FROM srv_grid WHERE spr_id='$spid'");
			$row = mysqli_fetch_array($sql);
			$_variables_count = $row['count'];

			$nArray = array();
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		
				
				// Prva podtabela
				if($grid['part'] == 1){
				
					# dodamo dodatne vrstice z albelami grida
					foreach ($grid['variables'] AS $vid => $variable ){

						if ($variable['text'] != true && $variable['other'] != true){
							
							$_sequence = $variable['sequence'];	# id kolone z podatki

							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							if (count($spremenljivka['options']) > 0) {
								foreach ( $spremenljivka['options'] as $key => $kategorija) {
									$xi = $key;
									$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
									$sum_xi_fi += $xi * $fi ;
									$N += $fi;				
								}
							} 			
							$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
							$nArray[] = $N;

							$tempArray = array();

							// nastavimo da graf ni prazen
							if($N > 0)
								$emptyData = false;
							
							$avg = $avg < 1 ? 1 : $avg;
							$tempArray['freq'] = $avg;
							$tempArray['percent'] = $avg;
							$tempArray['valid'] = $avg;

							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;
										
							$dataArray[] = $tempArray;
						}

						// polnimo array za drugo
						if ($variable['text'] == true || $variable['other'] == true){
							$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
						}
					}
					
					$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
					if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

								$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
								$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

								$tempArray = array();
								
								$tempArray['freq'] = $iAnswer['cnt'];
								$tempArray['percent'] = $_percent;
								$tempArray['valid'] = $_invalid;
								
								$tempArray['key'] = $ikey;
								$tempArray['variable'] = $iAnswer['text'];
								$tempArray['grid'] = $grid['variables'][0]['naslov'];
								
								$dataArray[] = $tempArray;
							}
						}
					}	
					
					$gridCount++;
				}
				
				else{
					# dodamo dodatne vrstice z albelami grida
					foreach ($grid['variables'] AS $vid => $variable ){

						if ($variable['text'] != true && $variable['other'] != true){
							
							$_sequence = $variable['sequence'];	# id kolone z podatki

							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							if (count($spremenljivka['options']) > 0) {
								foreach ( $spremenljivka['options'] as $key => $kategorija) {
									$xi = $key;
									$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
									$sum_xi_fi += $xi * $fi ;
									$N += $fi;				
								}
							} 			
							$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
							$nArray[] = $N;

							$tempArray = array();

							// nastavimo da graf ni prazen
							if($N > 0)
								$emptyData = false;
							
							$avg = $avg < 1 ? 1 : $avg;
							$tempArray['freq'] = $avg;
							$tempArray['percent'] = $avg;
							$tempArray['valid'] = $avg;

							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;
										
							$dataArray2[] = $tempArray;
						}

						// polnimo array za drugo
						if ($variable['text'] == true || $variable['other'] == true){
							$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
						}
					}
					
					$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
					if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

								$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
								$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

								$tempArray = array();
								
								$tempArray['freq'] = $iAnswer['cnt'];
								$tempArray['percent'] = $_percent;
								$tempArray['valid'] = $_invalid;
								
								$tempArray['key'] = $ikey;
								$tempArray['variable'] = $iAnswer['text'];
								$tempArray['grid'] = $grid['variables'][0]['naslov'];
								
								$dataArray2[] = $tempArray;
							}
						}
					}
				}
			}
			
			// zascita pred praznimi vprasanji (brez variabel)
			if(count($spremenljivka['grids']) == 0)
				return -1;
			
			//nastavimo numerus, ki se izpise pod legendo
			rsort($nArray);
			$numerus = ((int)$nArray[0] > 0) ? $nArray[0] : 0;
			$DataSet->SetNumerus($numerus);
			self::$numerusText = ($settings['value_type'] == 0) ? ' ('.$lang['srv_analiza_frekvence_titleVeljavni'].')' : '';
		
			// Poberemo podatke v posamezne tabele
			for($i=0; $i<count($dataArray,0); $i++){			
				
				$vrednosti[] = $dataArray[$i]['freq'];
				$vrednostiPercent[] = $dataArray[$i]['percent'];
				$vrednostiValid[] = $dataArray[$i]['valid'];
		
				$vrednostiKey[] = $dataArray[$i]['key'];
				$vrednostiVariable[] = $dataArray[$i]['variable'];
			}
			
			for($i=0; $i<count($dataArray2,0); $i++){			
				
				$vrednosti2[] = $dataArray2[$i]['freq'];
				$vrednostiPercent2[] = $dataArray2[$i]['percent'];
				$vrednostiValid2[] = $dataArray2[$i]['valid'];
		
				$vrednostiKey2[] = $dataArray2[$i]['key'];
				$vrednostiVariable2[] = $dataArray2[$i]['variable'];
			}
			
			if(count($vrednosti) > 0){
				if($settings['value_type'] == 1){
					$DataSet->AddPoint($vrednosti,'Vrednosti');
					$DataSet->AddPoint($vrednosti2,'Vrednosti2');
					//$DataSet->SetYAxisName($lang['srv_chart_freq']);
				}
				elseif($settings['value_type'] == 2){
					$DataSet->AddPoint($vrednostiPercent,'Vrednosti');
					$DataSet->AddPoint($vrednostiPercent2,'Vrednosti2');
					//$DataSet->SetYAxisName($lang['srv_chart_percent']);
				}
				elseif($settings['value_type'] == 0){
					$DataSet->AddPoint($vrednostiValid,'Vrednosti');
					$DataSet->AddPoint($vrednostiValid2,'Vrednosti2');
					//$DataSet->SetYAxisName($lang['srv_chart_valid']);
				}
			}
			else{
				$DataSet->AddPoint(array(0),'Vrednosti');
				$DataSet->AddPoint(array(0),'Vrednosti2');
			}
			
			$title1 = ($spremenljivka['double'][1]['subtitle'] != '') ? $spremenljivka['double'][1]['subtitle'] : 'Tabela 1';
			$title2 = ($spremenljivka['double'][2]['subtitle'] != '') ? $spremenljivka['double'][2]['subtitle'] : 'Tabela 2';
			
			$DataSet->AddSerie('Vrednosti');
			$DataSet->SetSerieName($title1,'Vrednosti');
			
			$DataSet->AddSerie('Vrednosti2');
			$DataSet->SetSerieName($title2,'Vrednosti2');
				
				
			$DataSet->AddPoint($vrednostiVariable,"Variable");
				
			$DataSet->SetAbsciseLabelSerie("Variable");	
		}
		
		// napolnimo podatke za MULTIGRID
		elseif($spremenljivka['tip'] == 6){
			$DataSet = new pData;	
			$dataArray = array();
			$fullPercent = array();

			$gridCount=0;
			
			
			// Prefiltriramo other, ki so manjkajoci
			$_invalidAnswers = SurveyAnalysis :: getInvalidAnswers (2);
			$noOthers = (isset($_invalidAnswers['-99'])) ? ' AND other!=-99' : '';
			$noOthers .= (isset($_invalidAnswers['-98'])) ? ' AND other!=-98' : '';
			$noOthers .= (isset($_invalidAnswers['-97'])) ? ' AND other!=-97' : '';
			
			// Napolnimo vse gride, ki jih obravnavamo
			$stolpci = array();	
			$sqlG = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$spid' ".$noOthers."  ");
			while($rowG = mysqli_fetch_array($sqlG)){
				$stolpci[] = $rowG;
			}	
			
			$_variables_count = count($stolpci);
			

			# odstranimo še možne nepotrebne zapise za multigride
			if($settings['hideEmptyVar'] == 1){
				$allGrids = count($spremenljivka['grids']);
				if (count($spremenljivka['grids']) > 0) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						$cntValidInGrid = 0;
						# dodamo dodatne vrstice z labelami grida
						if (count($grid['variables']) > 0 ) {
							foreach ($grid['variables'] AS $vid => $variable ){
								$_sequence = $variable['sequence'];	# id kolone z podatki
								foreach(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $valid){
									$cntValidInGrid+= (int)$valid['cnt'];
								}
							}
						}
						# preverjamo ali lahko prikazujemo podkategorije
						if((int)$cntValidInGrid > 0) {
							$gidsCanShow[$gid] = true;
						} else {
							$gidsCanShow[$gid] = false;
						}
					}
				}
			}
			
			
			$nArray = array();
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {

				$legendTitle = '';				
				
				// Kontrola ce ne prikazujemo praznih variabel
				if ((!is_array($gidsCanShow) && !isset($gidsCanShow[$gid])) 
					|| (is_array($gidsCanShow) && isset($gidsCanShow[$gid]) && $gidsCanShow[$gid]== true)){
					
					# dodamo dodatne vrstice z albelami grida
					foreach ($grid['variables'] AS $vid => $variable ){

						if ($variable['text'] != true && $variable['other'] != true){
							$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
							
							$_sequence = $variable['sequence'];	# id kolone z podatki
						
							// Ce izrisujemo graf za povprecja
							if($settings['type'] == 0 || $settings['type'] == 5 || $settings['type'] == 6){
								$sum_xi_fi=0;
								$N = 0;
								$div=0;
								if (count($spremenljivka['options']) > 0) {
									foreach ( $spremenljivka['options'] as $key => $kategorija) {
										$xi = $key;
										$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
										$sum_xi_fi += $xi * $fi ;
										$N += $fi;				
									}
								} 			
								$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
								$nArray[] = $N;

								$tempArray = array();

								// nastavimo da graf ni prazen
								if($N > 0)
									$emptyData = false;
								
								$avg = $avg < 1 ? 1 : $avg;
								$tempArray['freq'] = $avg;
								$tempArray['percent'] = $avg;
								$tempArray['valid'] = $avg;

								$tempArray['key'] = $variable['variable'];
								
								$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
								$tempArray['variable'] = $text;
								
								// dodamo vrednosti na desni ce imamo vklopljen diferencial
								if($spremenljivka['enota'] == 1){
									$sqlV = sisplet_query("SELECT naslov2 FROM srv_vrednost WHERE spr_id='$spid' AND id='$variable[vr_id]'");
									$rowV = mysqli_fetch_array($sqlV);								
									$tempArray['variable2'] = strip_tags($rowV['naslov2']);
								}
						
								$dataArray[] = $tempArray;
							}
							
							// izpisujemo navaden graf (ne povprecij)
							else{
								if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {	

									foreach ($stolpci as $key => $stolpec) {
										
										$vkey = $stolpec['vrstni_red'];
										
										// imamo OTHER grid (ne vem, zavrnil...)
										if($stolpec['other'] != 0){
											
											$vAnswer = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$stolpec['other']];
											
											if($vAnswer != null){										
												$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
												$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;								
												
												$tempArray = array();

												if($settings['value_type'] == 0){
													$nArray[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
												}
												else{
													$nArray[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
												}

												// nastavimo da graf ni prazen
												if($vAnswer['cnt'] > 0)
													$emptyData = false;
												
												$tempArray['freq'] = $vAnswer['cnt'];
												$tempArray['percent'] = $_percent;
												$tempArray['valid'] = $_valid;
											}
											
											// ce missling (-99, -98...) nima nobene vrednosti potem ga ni v tabeli - zato ga rocno napolnimo
											else{
												$tempArray = array();

												$nArray[] = 0;
												
												$tempArray['freq'] = 0;
												$tempArray['percent'] = 0;
												$tempArray['valid'] = 0;	
											}
											
											$tempArray['key'] = $vkey;

											$text = $stolpec['other'].' '.$stolpec['naslov'];
											$tempArray['variable'] = $text;
											
											$textGrid = $spremenljivka['edit_graf'] == 0 ? $grid['variables'][0]['naslov'] : $grid['variables'][0]['naslov_graf'];
											$tempArray['grid'] = $textGrid;
												
											$dataArray[] = $tempArray;
												
											$fullPercent[$gridCount] += $tempArray['percent'];
										}
											
										// imamo NAVADEN GRID
										else{
											$vAnswer = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$vkey];
											
											$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
											$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;								
																							
											// Za sortiranje po povprecju
											$sum_xi_fi=0;
											$N = 0;
											if (count($spremenljivka['options']) > 0) {
												foreach ( $spremenljivka['options'] as $key => $kategorija) {
													$xi = $key;
													$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
													$sum_xi_fi += $xi * $fi ;
													$N += $fi;				
												}
											} 			
											$avg = ($N > 0) ? $sum_xi_fi / $N : 0;	
											$avg = $avg < 1 ? 1 : $avg;								
											
											$tempArray = array();

											if($settings['value_type'] == 0){
												$nArray[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
											}
											else{
												$nArray[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
											}
											
											// nastavimo da graf ni prazen
											if($vAnswer['cnt'] > 0)
												$emptyData = false;
											
											$tempArray['avg'] = $avg;
											$tempArray['freq'] = $vAnswer['cnt'];
											$tempArray['percent'] = $_percent;
											$tempArray['valid'] = $_valid;
											
											$tempArray['key'] = $vkey;
											
											$text = ($spremenljivka['edit_graf'] == 0) ? $vAnswer['text'] : $vAnswer['text_graf'];
											$tempArray['variable'] = ($text == '') ? $vkey : $text;
											
											$textGrid = $spremenljivka['edit_graf'] == 0 ? $grid['variables'][0]['naslov'] : $grid['variables'][0]['naslov_graf'];
											$tempArray['grid'] = $textGrid;

											// dodamo vrednosti na desni ce imamo vklopljen diferencial
											if($spremenljivka['enota'] == 1){
												$sqlV = sisplet_query("SELECT naslov2 FROM srv_vrednost WHERE spr_id='$spid' AND id='$variable[vr_id]'");
												$rowV = mysqli_fetch_array($sqlV);								
												$tempArray['variable2'] = strip_tags($rowV['naslov2']);
											}
											
											$dataArray[] = $tempArray;
												
											$fullPercent[$gridCount] += $tempArray['percent'];
										}
									}
								}				
							}
						}

						// polnimo array za drugo
						if ($variable['text'] == true || $variable['other'] == true){
							$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
						}
					}
					
					$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
					if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

								$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
								$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
								
								$tempArray = array();
								
								$tempArray['freq'] = $iAnswer['cnt'];
								$tempArray['percent'] = $_percent;
								$tempArray['valid'] = $_invalid;
								
								$tempArray['key'] = $ikey;
								$tempArray['variable'] = $iAnswer['text'];
								$tempArray['grid'] = $grid['variables'][0]['naslov'];
								
								$dataArray[] = $tempArray;
								
								$fullPercent[$gridCount] += $tempArray['percent'];
							}
						}
					}	
					
					$gridCount++;			
				}
			}
			
			// zascita pred praznimi vprasanji (brez variabel)
			if(count($spremenljivka['grids']) == 0)
				return -1;
			
			//nastavimo numerus, ki se izpise pod legendo
			rsort($nArray);
			$numerus = ((int)$nArray[0] > 0) ? $nArray[0] : 0;
			$DataSet->SetNumerus($numerus);
			self::$numerusText = ($settings['value_type'] == 0) ? ' ('.$lang['srv_analiza_frekvence_titleVeljavni'].')' : '';

			// Ce prikazujemo POVPRECJA napolnimo podatke samo na koncu
			if($settings['type'] == 0 || $settings['type'] == 5 || $settings['type'] == 6){
			
				// Sortiramo podaatke ce je potrebno
				if($settings['sort'] == 1){
					$tmp = Array();
					foreach($dataArray as &$data) 
						$tmp[] = &$data['freq']; 				
					array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
				}
				elseif($settings['sort'] == 2){
					$tmp = Array();
					foreach($dataArray as &$data) 
						$tmp[] = &$data['freq']; 				
					array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
				}
			
				// Poberemo podatke v posamezne tabele
				for($i=0; $i<count($dataArray,0); $i++){			
					
					$vrednosti[] = $dataArray[$i]['freq'];
					$vrednostiPercent[] = $dataArray[$i]['percent'];
					$vrednostiValid[] = $dataArray[$i]['valid'];
			
					$vrednostiKey[] = $dataArray[$i]['key'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];
					
					// se vrednosti na desni pri sem. diferencialu
					if($spremenljivka['enota'] == 1)
						$vrednostiVariable2[] = $dataArray[$i]['variable2'];
				}
				
				if(count($vrednosti) > 0){
					if($settings['value_type'] == 1){
						$DataSet->AddPoint($vrednosti,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_freq']);
					}
					elseif($settings['value_type'] == 2){
						$DataSet->AddPoint($vrednostiPercent,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_percent']);
					}
					elseif($settings['value_type'] == 0){
						$DataSet->AddPoint($vrednostiValid,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_valid']);
					}
				}
				else
					$DataSet->AddPoint(array(0),'Vrednosti');
				
				$DataSet->AddSerie('Vrednosti');
				$DataSet->SetSerieName('Povprečja','Vrednosti');
				
				
				// Pri povprecjih vedno izpisemo cela imena variabel
				$DataSet->AddPoint($vrednostiVariable,"Variable");
				//$DataSet->AddPoint($vrednostiKey,"Variable");
				
				// se vrednosti na desni pri sem. diferencialu
				if($spremenljivka['enota'] == 1){
					$DataSet->AddPoint($vrednostiVariable2,"Variable2");
					$DataSet->SetRightLabelSerie("Variable2");		
				}
				$DataSet->SetAbsciseLabelSerie("Variable");
			}
			
			// Prikazujemo navadne podatke
			else{

				// Normalno obrnjen graf - gridi v stolpcih, variable v legendi (deli stolpcev)
				if($settings['rotate'] != 1){
				
					// Sortiramo podaatke ce je potrebno - Po kategorijah				
					if($settings['sort'] == 1){

						$tmp = Array();
						
						// preberemo prve vrednosti iz vsakega stolpca
						for($j=0; $j<$_variables_count; $j++){
							$offset = $j;						
							$tmp[] = (int)$dataArray[$offset]['valid'];							
						}
						
						// sortiramo vrednosti in preberemo kljuce
						arsort($tmp);
						$sorted_keys = array_keys($tmp);
					}
					// Sort po povprecjih
					elseif($settings['sort'] == 2){

						$tmp = Array();
						
						// preberemo povprecje iz vsake prve vrednosti vrstice
						for($j=0; $j<$gridCount; $j++){
							$offset = $j*$_variables_count;						
							$tmp[] = $dataArray[$offset]['avg'];	
						}
						
						// sortiramo vrednosti in preberemo kljuce
						arsort($tmp);
						$sorted_keys = array_keys($tmp);
					}
					// Sort po prvi kategoriji
					elseif($settings['sort'] == 3){

						$tmp = Array();
						
						// preberemo prve vrednosti iz vsake vrstice
						for($j=0; $j<$gridCount; $j++){
							$offset = $j*$_variables_count;						
							$tmp[] = (int)$dataArray[$offset]['valid'];							
						}
						
						// sortiramo vrednosti in preberemo kljuce
						arsort($tmp);
						$sorted_keys = array_keys($tmp);
					}
				
					for($i=0; $i<$_variables_count; $i++){

						unset($vrednosti);
						unset($vrednostiPercent);
						unset($vrednostiValid);
						unset($vrednostiKey);
						unset($vrednostiVariable);
						unset($vrednostiGrid);
						unset($vrednostiVariable2);
						
						// Poberemo podatke v posamezne tabele
						for($j=0; $j<$gridCount; $j++){			
						
							// ce sortiramo uporabimo sortirane kljuce
							if($settings['sort'] == 1)
								$offset = $sorted_keys[$i] + ($j*$_variables_count);
							
							// sort po povprecjih
							elseif($settings['sort'] == 2)
								$offset = ($sorted_keys[$j]*$_variables_count) + $i;
							
							// sort po prvi kategoriji
							elseif($settings['sort'] == 3)
								$offset = ($sorted_keys[$j]*$_variables_count) + $i;
							
							else
								$offset = $i + ($j*$_variables_count);

								
							$vrednosti[] = $dataArray[$offset]['freq'];						
							$vrednostiPercent[] = $dataArray[$offset]['percent'];
							$vrednostiValid[] = $dataArray[$offset]['valid'];
				
							$vrednostiKey[] = $dataArray[$offset]['key'];
							$vrednostiVariable[] = $dataArray[$offset]['variable'];	
							$vrednostiGrid[] = $dataArray[$offset]['grid'];
							
							// se vrednosti na desni pri sem. diferencialu
							if($spremenljivka['enota'] == 1)
								$vrednostiVariable2[] = $dataArray[$offset]['variable2'];
						}

						if(count($vrednosti) > 0){
							if($settings['value_type'] == 1){
								$DataSet->AddPoint($vrednosti,'Vrednosti_'.$i);
								//$DataSet->SetYAxisName($lang['srv_chart_freq']);
							}
							elseif($settings['value_type'] == 2){
								$DataSet->AddPoint($vrednostiPercent,'Vrednosti_'.$i);
								//$DataSet->SetYAxisName($lang['srv_chart_percent']);
							}
							elseif($settings['value_type'] == 0){
								$DataSet->AddPoint($vrednostiValid,'Vrednosti_'.$i);
								//$DataSet->SetYAxisName($lang['srv_chart_valid']);
							}
						}
						else
							$DataSet->AddPoint(array(0),'Vrednosti_'.$i);
						
						$DataSet->AddSerie('Vrednosti_'.$i);
						$DataSet->SetSerieName($vrednostiVariable[0],'Vrednosti_'.$i);
					}					
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($vrednostiGrid,"Variable");
					
					// se vrednosti na desni pri sem. diferencialu
					if($spremenljivka['enota'] == 1){
						$DataSet->AddPoint($vrednostiVariable2,"Variable2");
						$DataSet->SetRightLabelSerie("Variable2");		
					}
				
					$DataSet->SetAbsciseLabelSerie("Variable");
				}
				
				// Obratno obrnjen graf - gridi v legendi (deli stolpca), variable v stolpcih - default ce imamo samo en grid
				else{			
					// prej moramo napolniti imena serij (variabel)
					for($i=0; $i<$gridCount; $i++){
						$vrednostiGrid[] = $dataArray[$i*$_variables_count]['grid'];
					}
					
					// Sortiramo podaatke ce je potrebno				
					if($settings['sort'] == 1){

						$tmp = Array();
						
						// preberemo prve vrednosti iz vsakega stolpca
						for($j=0; $j<$gridCount; $j++){
							$offset = $j*$_variables_count;						
							$tmp[] = (int)$dataArray[$offset]['valid'];							
						}
						
						// sortiramo vrednosti in preberemo kljuce
						arsort($tmp);
						$sorted_keys = array_keys($tmp);
					}
					// Sort po prvi kategoriji
					elseif($settings['sort'] == 3){

						$tmp = Array();
						
						// preberemo prve vrednosti iz vsake vrstice
						for($j=0; $j<$_variables_count; $j++){
							$offset = $j;						
							$tmp[] = (int)$dataArray[$offset]['valid'];
						}
						
						// sortiramo vrednosti in preberemo kljuce
						arsort($tmp);
						$sorted_keys = array_keys($tmp);
					}
					
					for($i=0; $i<$gridCount; $i++){

						unset($vrednosti);
						unset($vrednostiPercent);
						unset($vrednostiValid);
						unset($vrednostiKey);
						unset($vrednostiVariable);
						unset($vrednostiVariable2);
						
						// Poberemo podatke v posamezne tabele
						for($j=0; $j<$_variables_count; $j++){			
						
							// ce sortiramo uporabimo sortirane kljuce
							if($settings['sort'] == 1)
								$offset = ($sorted_keys[$i]*$_variables_count) + $j;
							
							// sort po prvi kategoriji
							elseif($settings['sort'] == 3)
								$offset = $sorted_keys[$j] + ($i*$_variables_count);
								
							else
								$offset = ($i*$_variables_count) + $j;

				
							$vrednosti[] = $dataArray[$offset]['freq'];						
							$vrednostiPercent[] = $dataArray[$offset]['percent'];
							$vrednostiValid[] = $dataArray[$offset]['valid'];
						
							$vrednostiKey[] = $dataArray[$offset]['key'];
							$vrednostiVariable[] = $dataArray[$offset]['variable'];	
							
							// se vrednosti na desni pri sem. diferencialu
							if($spremenljivka['enota'] == 1)
								$vrednostiVariable2[] = $dataArray[$offset]['variable2'];
						}

						if(count($vrednosti) > 0){
							if($settings['value_type'] == 1){
								$DataSet->AddPoint($vrednosti,'Vrednosti_'.$i);
								//$DataSet->SetYAxisName($lang['srv_chart_freq']);
							}
							elseif($settings['value_type'] == 2){
								$DataSet->AddPoint($vrednostiPercent,'Vrednosti_'.$i);
								//$DataSet->SetYAxisName($lang['srv_chart_percent']);
							}
							elseif($settings['value_type'] == 0){
								$DataSet->AddPoint($vrednostiValid,'Vrednosti_'.$i);
								//$DataSet->SetYAxisName($lang['srv_chart_valid']);
							}
						}
						else
							$DataSet->AddPoint(array(0),'Vrednosti_'.$i);
						
						$DataSet->AddSerie('Vrednosti_'.$i);
						if($settings['sort'] == 1)
							$DataSet->SetSerieName($vrednostiGrid[$sorted_keys[$i]],'Vrednosti_'.$i);
						else
							$DataSet->SetSerieName($vrednostiGrid[$i],'Vrednosti_'.$i);
					}					
					
					// Vedno izpisemo cela imena variabel
					$DataSet->AddPoint($vrednostiVariable,"Variable");
						
					// se vrednosti na desni pri sem. diferencialu
					if($spremenljivka['enota'] == 1){
						$DataSet->AddPoint($vrednostiVariable2,"Variable2");
						$DataSet->SetRightLabelSerie("Variable2");		
					}	
						
					$DataSet->SetAbsciseLabelSerie("Variable");
				}
			}
		
			
			if( $settings['value_type'] != 1 && $settings['type'] != 0 && $settings['type'] != 6 ){
				$DataSet->SetYAxisUnit("%");
				$DataSet->SetYAxisFormat("number");
			}
		}
		
		// napolnimo podatke za NUMBER
		elseif($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 22){				
			$dataArray = array();

            $has_decimal = false;

			$i=0;
			$N=0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		
				
				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);
				$field = 0;
				
				$avg_count = 0;
				$avg_sum = 0;
				$avg_count2 = 0;
				$avg_sum2 = 0;
				
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){

					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
					$var_title[] = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
				
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($spremenljivka['tip'] == 22 || (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21))))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
						
							if($field == 0)
								$N = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
						
							foreach ( SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									
									$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
									$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
									
									$tempArray = array();

									$tempArray['freq'] = $vAnswer['cnt'];
									$tempArray['percent'] = $_percent;
									$tempArray['valid'] = $_valid;
									
                                    $tempArray['key'] = $vkey;
                                    
                                    if(floor($vkey) != $vkey)
                                        $has_decimal = true;
									
									// racunamo povprecje (za prvo variablo) 
									if($field == 0){
										
										// ce je znotraj nastavljenih mej
										if( ($settings['max'] == '' || ($settings['open_up'] == 1 || (int)$vAnswer['text'] <= (int)$settings['max']))
											&& ($settings['min'] == '' || ($settings['open_down'] == 1 || (int)$vAnswer['text'] >= (int)$settings['min'])) ){
	
											$avg_count += $vAnswer['cnt'];
											$avg_sum += $vAnswer['cnt'] * (int)$vAnswer['text'];
										}
										else{
											$N--;
										}
									}
									// racunamo povprecje (samo za drugo variablo) 
									if($field == 1){
										
										// ce je znotraj nastavljenih mej
										if( ($settings['max'] == '' || ($settings['open_up'] == 1 || (int)$vAnswer['text'] <= (int)$settings['max']))
											&& ($settings['min'] == '' || ($settings['open_down'] == 1 || (int)$vAnswer['text'] >= (int)$settings['min'])) ){
	
											$avg_count2 += $vAnswer['cnt'];
											$avg_sum2 += $vAnswer['cnt'] * (int)$vAnswer['text'];
										}
										else{
											$N--;
										}
									}
									
									// nastavimo da graf ni prazen
									$emptyData = false;
									
									$text = $vAnswer['text'];
									$tempArray['variable'] = $text;
									
									$tempArray['field'] = $field;
									
									$dataArray[] = $tempArray;
								}
							}
						}				
					}
					$field++;
				}
				
				$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
				if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
						if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
							$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

							$tempArray = array();
							
							//$N = ($settings['value_type'] == 2) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
					
							$tempArray['freq'] = $iAnswer['cnt'];
							$tempArray['percent'] = $_percent;
							$tempArray['valid'] = $_invalid;
							
							$tempArray['key'] = $ikey;
							$tempArray['variable'] = $iAnswer['text'];
							
							$dataArray[] = $tempArray;
						}
					}
				}	
				
				$i++;
			}		

			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;
			
			//polnimo podatke
			$DataSet = new pData;

			
			// nastavimo NUMERUS, ki se izpise pod legendo
			$N = ((int)$N > 0) ? $N : 0;
			$DataSet->SetNumerus($N);
			
			// nastavimo POVPRECJE		
			$avg = ($avg_count > 0) ? $avg_sum / $avg_count : 0;
			$DataSet->SetAverage(round($avg, 1));
			
			// Povprecje za 2. variablo (rabimo pri grafu povprecja)
			$avg2 = ($avg_count2 > 0) ? $avg_sum2 / $avg_count2 : 0;
			
			// Sortiramo podatke - ce imamo izpis vsakega vnosa posebej sortiramo po freq, ce pa po skupinah pa po key
			if($settings['type'] <= 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['key']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}
			elseif($settings['sort'] == 1){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			$max = (double)$dataArray[count($dataArray,0)-1]['variable'];
			$min = (double)$dataArray[0]['variable'];
			$stIntervalov = ((int)$settings['interval'] == 0 ? 10 : (int)$settings['interval']);
			

			// Ce imamo napredno napredne intervale
			if($settings['limits']['advanced_settings'] == 1){
				$limits = $settings['limits'];
				
				$max = $limits['interval_'. ($stIntervalov-1) ]['max'];
				$min = $limits['interval_0']['min'];
			}
			// Ce imamo osnovne intervale
			else{
				// Nastavimo custom zgornjo mejo skale (razen v primeru ko ne ignoriramo vrednosti ki padejo ven in ce je max vnos vecji od nastavljenega max)
				if($settings['max'] != '' /*&& ($settings['open_up'] == 0 || (int)$settings['max'] > $max)*/)
					$max = (double)$settings['max'];
				// Nastavimo custom spodnjo mejo skale (razen v primeru ko ne ignoriramo vrednosti ki padejo ven in ce je min vnos manjsi od nastavljenega min)			
				if($settings['min'] != '' /*&& ($settings['open_down'] == 0 || (int)$settings['min'] < $min)*/)
					$min = (double)$settings['min'];
				
				
				$stIntervalov = ($stIntervalov == -1 ? $max-$min : $stIntervalov);
                $part = ($max-$min) / $stIntervalov;

                if(!$has_decimal)
				    $part = ($part < 1) ? 1 : round($part);
			}
			
			// Poberemo podatke v posamezne tabele - po intervalih oz normalno
			if($settings['type'] <= 2){
				
				// Ce imamo polodprt intrerval navzdol
				if($settings['open_down'] == 1){
					$count = 0;
					$percent = 0;
					$valid = 0;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i]['variable'] < $min && $dataArray[$i]['field'] == 0){
							$count += $dataArray[$i]['freq'];
							$percent += $dataArray[$i]['percent'];
							$valid += $dataArray[$i]['valid'];
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $count;
					$vrednostiPercent[] = $percent;
					$vrednostiValid[] = $valid;
					$vrednostiKey[] = $lang['srv_chart_less'].' '.$min;
					$vrednostiVariable[] = $lang['srv_chart_less'].' '.$min;
				}
				
				// loop cez intervale - default 10
				for($interval=0; $interval<$stIntervalov; $interval++){
				
					$count = 0;
					$percent = 0;
					$valid = 0;
					
					// Ce imamo napredno napredne intervale (custom dolocene)
					if($settings['limits']['advanced_settings'] == 1){
						$maxVal = $limits['interval_'.$interval]['max'];
						$minVal = $limits['interval_'.$interval]['min'];
					}
					// Ce imamo osnovne intervale (racunamo sproti)
					else{
                        // Ce imamo decimalke
                        if($has_decimal){
                            $maxVal = ($interval < ($stIntervalov-1) ? $min-0.01 + (($interval+1) * $part) : $max);
                            $minVal = ($interval > 0 ? $min + ($interval * $part) : $min);
                        }
                        else{
						    $maxVal = ($interval < ($stIntervalov-1) ? $min + (($interval+1) * $part) : $max);
                            $minVal = ($interval > 0 ? $min + ($interval * $part) + 1 : $min);
                        }
                    }

					// prekinemo ce zaradi zaokrozevanja pride do min > max
					if($minVal > $maxVal)
						break;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){

						// ce pripada intervalu	
						if($dataArray[$i]['variable'] <= $maxVal && $dataArray[$i]['variable'] >= $minVal && $dataArray[$i]['field'] == 0){
							$count += $dataArray[$i]['freq'];
							$percent += $dataArray[$i]['percent'];
                            $valid += $dataArray[$i]['valid'];
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $count;
					$vrednostiPercent[] = $percent;
					$vrednostiValid[] = $valid;

					// Ce imamo napredne intervale (custom dolocene labele)
					if($settings['limits']['advanced_settings'] == 1 && $limits['interval_'.$interval]['label'] != ''){
						$vrednostiKey[] = $limits['interval_'.$interval]['label'];
						$vrednostiVariable[] = $limits['interval_'.$interval]['label'];
					}
					elseif($minVal == $maxVal){
						$vrednostiKey[] = $minVal;
						$vrednostiVariable[] = $minVal;
					}
					else{
						$vrednostiKey[] = $minVal.'-'.$maxVal;
						$vrednostiVariable[] = $minVal.'-'.$maxVal;
					}
				}
				
				// Ce imamo polodprt intrerval navzgor
				if($settings['open_up'] == 1){
					$count = 0;
					$percent = 0;
					$valid = 0;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i]['variable'] > $max && $dataArray[$i]['field'] == 0){
							$count += $dataArray[$i]['freq'];
							$percent += $dataArray[$i]['percent'];
							$valid += $dataArray[$i]['valid'];
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $count;
					$vrednostiPercent[] = $percent;
					$vrednostiValid[] = $valid;
					$vrednostiKey[] = $lang['srv_chart_more'].' '.$max;
					$vrednostiVariable[] = $lang['srv_chart_more'].' '.$max;
				}
				
				//ponovimo ce imamo 2 polja
				if($field == 2){
				
					// Ce imamo polodprt intrerval navzdol
					if($settings['open_down'] == 1){
						$count = 0;
						$percent = 0;
						$valid = 0;
						
						// loop cez vse podatke
						for($i=0; $i<count($dataArray,0); $i++){
							
							// ce pripada intervalu	
							if($dataArray[$i]['variable'] < $min && $dataArray[$i]['field'] == 1){
								$count += $dataArray[$i]['freq'];
								$percent += $dataArray[$i]['percent'];
								$valid += $dataArray[$i]['valid'];
							}
						}
						
						// vnesemo podatke za interval
						$vrednosti2[] = $count;
						$vrednostiPercent2[] = $percent;
						$vrednostiValid2[] = $valid;
					}
				
					// loop cez intervale - default 10
					for($interval=0; $interval<$stIntervalov; $interval++){
					
						$count = 0;
						$percent = 0;
						$valid = 0;
						
						// Ce imamo napredno napredne intervale (custom dolocene)
						if($settings['limits']['advanced_settings'] == 1){
							$maxVal = $limits['interval_'.$interval]['max'];
							$minVal = $limits['interval_'.$interval]['min'];
						}
						// Ce imamo osnovne intervale (racunamo sproti)
						else{
                            // Ce imamo decimalke
                            if($has_decimal){
                                $maxVal = ($interval < ($stIntervalov-1) ? $min-0.01 + (($interval+1) * $part) : $max);
                                $minVal = ($interval > 0 ? $min + ($interval * $part) : $min);
                            }
                            else{
                                $maxVal = ($interval < ($stIntervalov-1) ? $min + (($interval+1) * $part) : $max);
                                $minVal = ($interval > 0 ? $min + ($interval * $part) + 1 : $min);
                            }
						}
						
						// prekinemo ce zaradi zaokrozevanja pride do min > max
						if($minVal > $maxVal)
							break;						
						
						// loop cez vse podatke
						for($i=0; $i<count($dataArray,0); $i++){
							
							// ce pripada intervalu	
							if($dataArray[$i]['variable'] <= $maxVal && $dataArray[$i]['variable'] >= $minVal && $dataArray[$i]['field'] == 1){
								$count += $dataArray[$i]['freq'];
								$percent += $dataArray[$i]['percent'];
								$valid += $dataArray[$i]['valid'];
							}
						}
						
						// vnesemo podatke za interval
						$vrednosti2[] = $count;
						$vrednostiPercent2[] = $percent;
						$vrednostiValid2[] = $valid;
					}
					
					// Ce imamo polodprt intrerval navzgor
					if($settings['open_up'] == 1){
						$count = 0;
						$percent = 0;
						$valid = 0;
						
						// loop cez vse podatke
						for($i=0; $i<count($dataArray,0); $i++){
							
							// ce pripada intervalu	
							if($dataArray[$i]['variable'] > $max && $dataArray[$i]['field'] == 1){
								$count += $dataArray[$i]['freq'];
								$percent += $dataArray[$i]['percent'];
								$valid += $dataArray[$i]['valid'];
							}
						}
						
						// vnesemo podatke za interval
						$vrednosti2[] = $count;
						$vrednostiPercent2[] = $percent;
						$vrednostiValid2[] = $valid;
					}
				}
			}
			
			else{
				for($i=0; $i<count($dataArray,0); $i++){
					
					if($dataArray[$i]['field'] == 0){
						$vrednosti[] = $dataArray[$i]['freq'];
						$vrednostiPercent[] = $dataArray[$i]['percent'];
						$vrednostiValid[] = $dataArray[$i]['valid'];
					}
					else{
						$vrednosti[] = 0;
						$vrednostiPercent[] = 0;
						$vrednostiValid[] = 0;
					}
					
					$vrednostiKey[] = $dataArray[$i]['key'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];	
				}
				
				//ponovimo ce imamo 2 polja
				if($field == 2){
					for($i=0; $i<count($dataArray,0); $i++){
						
						if($dataArray[$i]['field'] == 1){
							$vrednosti2[] = $dataArray[$i]['freq'];
							$vrednostiPercent2[] = $dataArray[$i]['percent'];
							$vrednostiValid2[] = $dataArray[$i]['valid'];
						}
						else{
							$vrednosti2[] = 0;
							$vrednostiPercent2[] = 0;
							$vrednostiValid2[] = 0;
						}
					}
				}
			}
					
			if(count($vrednosti) > 0){
			
				// Graf povprecja
				if($settings['type'] == 9){					
					$DataSet->AddPoint(round($avg, 1),'Vrednosti');
					if($field == 2)
						$DataSet->AddPoint(round($avg2, 1),'Vrednosti2');
				}
				else{
					if($settings['value_type'] == 0){
						$DataSet->AddPoint($vrednosti,'Vrednosti');
						if($field == 2)
							$DataSet->AddPoint($vrednosti2,'Vrednosti2');
						//$DataSet->SetYAxisName($lang['srv_chart_freq']);
					}
					elseif($settings['value_type'] == 1){
						$DataSet->AddPoint($vrednostiPercent,'Vrednosti');
						if($field == 2)
							$DataSet->AddPoint($vrednostiPercent2,'Vrednosti2');
						//$DataSet->SetYAxisName($lang['srv_chart_percent']);
					}
					elseif($settings['value_type'] == 2){
						$DataSet->AddPoint($vrednostiValid,'Vrednosti');
						if($field == 2)
							$DataSet->AddPoint($vrednostiValid2,'Vrednosti2');
						//$DataSet->SetYAxisName($lang['srv_chart_valid']);
					}
				}
			}
			else
				$DataSet->AddPoint(array(0),'Vrednosti');
			
			$DataSet->AddSerie('Vrednosti');
			$var_title[0] = ($var_title[0] == '' ? 'Vrednosti' : $var_title[0]);
			$DataSet->SetSerieName($var_title[0],'Vrednosti');
			
			if($field == 2){
				$DataSet->AddSerie('Vrednosti2');
				$var_title[1] = ($var_title[1] == '' ? 'Vrednosti 2' : $var_title[1]);
				$DataSet->SetSerieName($var_title[1],'Vrednosti2');
			}
			
			// Vedno izpisemo cela imena variabel
			if($settings['type'] != 9)
				$DataSet->AddPoint($vrednostiVariable,"Variable");
				//$DataSet->AddPoint($vrednostiKey,"Variable");
				
			$DataSet->SetAbsciseLabelSerie("Variable");
			
			if($settings['value_type'] > 0){
				$DataSet->SetYAxisUnit("%");
				$DataSet->SetYAxisFormat("number");
			}
		}
		
		// napolnimo podatke za DATUM
		elseif($spremenljivka['tip'] == 8){				
			$dataArray = array();

			$i=0;
			$N=0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		
				
				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);

				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){

					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
					$var_title[] = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
				
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21)))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
							
							$N = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
							
							foreach ( SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									
									$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
									$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
									
									$tempArray = array();
									
																
									// ce je zunaj nastavljenih mej
									if( ($settings['max'] != '' && ($settings['open_up'] == 0 && (int)$vAnswer['text'] > (int)$settings['max']))
										|| ($settings['min'] != '' && ($settings['open_down'] == 0 && (int)$vAnswer['text'] < (int)$settings['min'])) ){
									
										$N--;
									}
									
									$date = strtotime($vkey);
									
									$tempArray['day'] = date('j', $date);
									$tempArray['month'] = date('n', $date);
									$tempArray['year'] = date('Y', $date);
									
									$tempArray['freq'] = $vAnswer['cnt'];
									
									// nastavimo da graf ni prazen
									$emptyData = false;
									
									$text = $vAnswer['text'];
									$tempArray['variable'] = $text;
									
									$dataArray[] = $tempArray;
								}
							}
						}				
					}
				}
				
				$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
				if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
						if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
							$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

							$tempArray = array();
							
							//$N = ($settings['value_type'] == 2) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
					
							$date = strtotime($ikey);
					
							$tempArray['day'] = date('j', $date);
							$tempArray['month'] = date('n', $date);
							$tempArray['year'] = date('Y', $date);
							
							$tempArray['freq'] = $iAnswer['cnt'];
							$tempArray['variable'] = $iAnswer['text'];
							
							$dataArray[] = $tempArray;
						}
					}
				}	
				
				$i++;
			}		

			if($settings['value_type'] == 0)
				$key = 'day';
			elseif($settings['value_type'] == 1)
				$key = 'month';
			else
				$key = 'year';
			
			
			//polnimo podatke
			$DataSet = new pData;

			//nastavimo numerus, ki se izpise pod legendo
			$N = ((int)$N > 0) ? $N : 0;
			$DataSet->SetNumerus($N);
			
			// Sortiramo podatke - ce imamo izpis vsakega vnosa posebej sortiramo po datumu, ce pa po skupinah pa po key
			if($settings['type'] < 3){
				if($settings['value_type'] == 0){
					$tmp = Array();
					foreach($dataArray as &$data) 
						$tmp[] = &$data['day']; 				
					array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
				}
				elseif($settings['value_type'] == 1){
					$tmp = Array();
					foreach($dataArray as &$data) 
						$tmp[] = &$data['month']; 				
					array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
				}
				elseif($settings['value_type'] == 2){
					$tmp = Array();
					foreach($dataArray as &$data) 
						$tmp[] = &$data['year']; 				
					array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
				}				
			}
			elseif($settings['sort'] == 1 ){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2 ){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['freq']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			$max = (int)$dataArray[count($dataArray,0)-1][$key];
			$min = (int)$dataArray[0][$key];
			$stIntervalov = ((int)$settings['interval'] == 0 ? 10 : (int)$settings['interval']);
			
			
			// Ce imamo napredno napredne intervale
			if($settings['limits']['advanced_settings'] == 1){
				$limits = $settings['limits'];
				
				$max = $limits['interval_'. ($stIntervalov-1) ]['max'];
				$min = $limits['interval_0']['min'];
			}
			// Ce imamo osnovne intervale
			else{
				// Nastavimo custom zgornjo mejo skale (razen v primeru ko ne ignoriramo vrednosti ki padejo ven in ce je max vnos vecji od nastavljenega max)
				if($settings['max'] != '' /*&& ($settings['open_up'] == 0 || (int)$settings['max'] > $max)*/)
					$max = (int)$settings['max'];
				// Nastavimo custom spodnjo mejo skale (razen v primeru ko ne ignoriramo vrednosti ki padejo ven in ce je min vnos manjsi od nastavljenega min)			
				if($settings['min'] != '' /*&& ($settings['open_down'] == 0 || (int)$settings['min'] < $min)*/)
					$min = (int)$settings['min'];
				
				$stIntervalov = ($stIntervalov == -1 ? $max-$min : $stIntervalov);
				$part = round( ($max-$min) / $stIntervalov );
			}

			// Poberemo podatke v posamezne tabele - po intervalih oz normalno
			if($settings['type'] < 3){
				
				// Ce imamo polodprt intrerval navzdol
				if($settings['open_down'] == 1){

					$value = 0;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i][$key] < $min){
							$value ++;
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $value;
					$vrednostiVariable[] = $lang['srv_chart_less'].' '.$min;
				}
				
				// loop cez intervale - default 10
				for($interval=0; $interval<$stIntervalov; $interval++){
				
					$value = 0;
					
					// Ce imamo napredno napredne intervale (custom dolocene)
					if($settings['limits']['advanced_settings'] == 1){
						$maxVal = $limits['interval_'.$interval]['max'];
						$minVal = $limits['interval_'.$interval]['min'];
					}
					// Ce imamo osnovne intervale (racunamo sproti)
					else{
						$maxVal = ($interval < ($stIntervalov-1) ? $min + (($interval+1) * $part) : $max);
						$minVal = ($interval > 0 ? $min + ($interval * $part) + 1 : $min);
					}
					
					// prekinemo ce zaradi zaokrozevanja pride do min > max
					if($minVal > $maxVal)
						break;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i][$key] <= $maxVal && $dataArray[$i][$key] >= $minVal){
							$value ++;
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $value;
		
					// Ce imamo napredne intervale (custom dolocene labele)
					if($settings['limits']['advanced_settings'] == 1 && $limits['interval_'.$interval]['label'] != ''){
						$vrednostiVariable[] = $limits['interval_'.$interval]['label'];
					}
					elseif($minVal == $maxVal){
						$vrednostiVariable[] = $minVal;
					}
					else{
						$vrednostiVariable[] = $minVal.'-'.$maxVal;
					}
				}
				
				// Ce imamo polodprt intrerval navzgor
				if($settings['open_up'] == 1){

					$value = 0;
					
					// loop cez vse podatke
					for($i=0; $i<count($dataArray,0); $i++){
						
						// ce pripada intervalu	
						if($dataArray[$i][$key] > $max){
							$value ++;
						}
					}
					
					// vnesemo podatke za interval
					$vrednosti[] = $value;
					$vrednostiVariable[] = $lang['srv_chart_more'].' '.$max;
				}
			}
			
			else{
				for($i=0; $i<count($dataArray,0); $i++){

					$vrednosti[] = $dataArray[$i]['freq'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];	
				}
			}
					
			if(count($vrednosti) > 0){
				$DataSet->AddPoint($vrednosti,'Vrednosti');
			}
			else
				$DataSet->AddPoint(array(0),'Vrednosti');
			
			$DataSet->AddSerie('Vrednosti');
			$var_title[0] = ($var_title[0] == '' ? 'Vrednosti' : $var_title[0]);
			$DataSet->SetSerieName($var_title[0],'Vrednosti');
			
			// Vedno izpisemo cela imena variabel
			$DataSet->AddPoint($vrednostiVariable,"Variable");
				
			$DataSet->SetAbsciseLabelSerie("Variable");
		}
		
		// napolnimo podatke za MULTICHECKBOX
		elseif($spremenljivka['tip'] == 16){
			$DataSet = new pData;	
			$dataArray = array();
			$fullPercent = array();

			
			# odstranimo še možne nepotrebne zapise
			if($settings['hideEmptyVar'] == 1){
				$allGrids = count($spremenljivka['grids']);
				if (count($spremenljivka['grids']) > 0) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						$cntValidInGrid = 0;
						# dodamo dodatne vrstice z labelami grida
						if (count($grid['variables']) > 0 ) {
							foreach ($grid['variables'] AS $vid => $variable ){
								$_sequence = $variable['sequence'];	# id kolone z podatki
								foreach(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $valid){
									$cntValidInGrid+= (int)$valid['cnt'];
								}
							}
						}
						# preverjamo ali lahko prikazujemo podkategorije
						if((int)$cntValidInGrid > 0) {
							$gidsCanShow[$gid] = true;
						} else {
							$gidsCanShow[$gid] = false;
						}
					}
				}
			}
			
			
			$gridCount=0;
			$nValid = array();
			$nAll = 0;
			$nNavedbe = array();
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {

				$legendTitle = '';			

				// Kontrola ce ne prikazujemo praznih variabel
				if ((!is_array($gidsCanShow) && !isset($gidsCanShow[$gid])) 
					|| (is_array($gidsCanShow) && isset($gidsCanShow[$gid]) && $gidsCanShow[$gid]== true)){
				
					# dodamo dodatne vrstice z albelami grida
					$_variables_count=0;
					foreach ($grid['variables'] AS $vid => $variable ){
						
						if ($variable['text'] != true && $variable['other'] != true){
							$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
							
							$_sequence = $variable['sequence'];	# id kolone z podatki
							
							$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
						
							$vAnswer = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][1]['cnt'];
							$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;								
							
							$tempArray = array();
											
							$nValid[] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
							$nAll = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
							$nNavedbe[$gid] += SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][1]['cnt'];
											
							$tempArray['freq'] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][1]['cnt'];
							$tempArray['percent'] = $_percent;
							$tempArray['valid'] = $_valid;
							
							// nastavimo da graf ni prazen
							if($vAnswer > 0)
								$emptyData = false;
							
							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;
							
							$textGrid = $spremenljivka['edit_graf'] == 0 ? $grid['naslov'] : $grid['naslov_graf'];
							$tempArray['grid'] = $textGrid;
							
							$dataArray[] = $tempArray;

							$fullPercent[$gridCount] += $tempArray['percent'];
							$fullPercentReverse[$_variables_count] += $tempArray['percent'];
							
							$_variables_count++;	
						}
										
						// polnimo array za drugo
						if ($variable['text'] == true || $variable['other'] == true){
							$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
						}
					}
					
					$displayMV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;	
					if ( (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0) && $displayMV) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0

								$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
								$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;

								$tempArray = array();
								
								$tempArray['freq'] = $iAnswer['cnt'];
								$tempArray['percent'] = $_percent;
								$tempArray['valid'] = $_invalid;
								
								$tempArray['key'] = $ikey;
								$tempArray['variable'] = $iAnswer['text'];
								$tempArray['grid'] = $grid['naslov'];
								
								$fullPercent[$gridCount] += $tempArray['percent'];
								$fullPercentReverse[$_variables_count] += $tempArray['percent'];
								
								$dataArray[] = $tempArray;
							}
						}
					}	
					
					$gridCount++;		
				}
			}
			
			// zascita pred praznimi vprasanji (brez variabel)
			if(count($spremenljivka['grids']) == 0)
				return -1;
			

			// Normalno obrnjen graf - gridi v stolpcih, variable v legendi (deli stolpcev)
			if($settings['rotate'] != 1){
			
				// Sortiramo podaatke ce je potrebno				
				if($settings['sort'] == 1){

					$tmp = Array();
					
					// preberemo prve vrednosti iz vsakega stolpca
					for($j=0; $j<$_variables_count; $j++){
						$offset = $j;						
						$tmp[] = (int)$dataArray[$offset]['valid'];							
					}
					
					// sortiramo vrednosti in preberemo kljuce
					arsort($tmp);
					$sorted_keys = array_keys($tmp);
				}
				// Sort po prvi kategoriji
				elseif($settings['sort'] == 3){

					$tmp = Array();
					
					// preberemo prve vrednosti iz vsake vrstice
					for($j=0; $j<$gridCount; $j++){
						$offset = $j*$_variables_count;						
						$tmp[] = (int)$dataArray[$offset]['valid'];							
					}
					
					// sortiramo vrednosti in preberemo kljuce
					arsort($tmp);
					$sorted_keys = array_keys($tmp);
				}
			
				// Prikazujemo podatke
				for($i=0; $i<$_variables_count; $i++){

					unset($vrednosti);
					unset($vrednostiPercent);
					unset($vrednostiValid);
					unset($vrednostiKey);
					unset($vrednostiVariable);
					unset($vrednostiGrid);
					
					// Poberemo podatke v posamezne tabele
					for($j=0; $j<$gridCount; $j++){
						
						// ce sortiramo uporabimo sortirane kljuce
						if($settings['sort'] == 1)
							$offset = $sorted_keys[$i] + ($j*$_variables_count);
						
						// sort po prvi kategoriji
						elseif($settings['sort'] == 3)
							$offset = ($sorted_keys[$j]*$_variables_count) + $i;
						
						else
							$offset = $i + ($j*$_variables_count);


						// Enote
						if($settings['base'] != 1){
							$vrednosti[] = $dataArray[$offset]['freq'];						
							$vrednostiPercent[] = $dataArray[$offset]['percent'];
							$vrednostiValid[] = $dataArray[$offset]['valid'];
							
							//nastavimo numerus, ki se izpise pod legendo
							if($settings['value_type'] == 0){	
								rsort($nValid);
								$numerus = ((int)$nValid[0] > 0) ? $nValid[0] : 0;
								$DataSet->SetNumerus($numerus);
								self::$numerusText = ' ('.$lang['srv_analiza_frekvence_titleVeljavni'].')';
							}
							else
								$numerus = ((int)$nAll > 0) ? $nAll : 0;	
								
							$DataSet->SetNumerus($numerus);
						}
						// Navedbe
						else{
							$percent = ($fullPercent[$j] * $dataArray[$offset]['percent'] > 0) ? 100 / $fullPercent[$j] * $dataArray[$offset]['percent'] : 0;
							
							$vrednosti[] = $dataArray[$offset]['freq'];	
							$vrednostiPercent[] = $percent;	
							$vrednostiValid[] = $percent;

							//nastavimo numerus, ki se izpise pod legendo
							rsort($nNavedbe);
							$numerus = ((int)$nNavedbe[0] > 0) ? $nNavedbe[0] : 0;
							$DataSet->SetNumerus($numerus);
							self::$numerusText = ' ('.$lang['srv_analiza_opisne_arguments'].')';
						}
					
						$vrednostiKey[] = $dataArray[$offset]['key'];
						$vrednostiVariable[] = $dataArray[$offset]['variable'];	
						$vrednostiGrid[] = $dataArray[$offset]['grid'];
					}

					if(count($vrednosti) > 0){
						if($settings['value_type'] == 2){
							$DataSet->AddPoint($vrednosti,'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_freq']);
						}
						elseif($settings['value_type'] == 1){
							$DataSet->AddPoint($vrednostiPercent,'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_percent']);
						}
						elseif($settings['value_type'] == 0){
							$DataSet->AddPoint($vrednostiValid,'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_valid']);
						}
					}
					else
						$DataSet->AddPoint(array(0),'Vrednosti_'.$i);

					$DataSet->AddSerie('Vrednosti_'.$i);
					$DataSet->SetSerieName($vrednostiVariable[0],'Vrednosti_'.$i);
				}

				// Vedno izpisemo cela imena variabel
				$DataSet->AddPoint($vrednostiGrid,"Variable");
					
				$DataSet->SetAbsciseLabelSerie("Variable");
			}
			
			// Obratno obrnjen graf - gridi v legendi (deli stolpca), variable v stolpcih - default ce imamo samo en grid
			else{	
				// prej moramo napolniti imena serij (variabel)
				for($i=0; $i<$gridCount; $i++){
					$vrednostiGrid[] = $dataArray[$i*$_variables_count]['grid'];
				}
			
				// Sortiramo podaatke ce je potrebno				
				if($settings['sort'] == 1){			

					$tmp = Array();
					
					// preberemo prve vrednosti iz vsakega stolpca
					for($j=0; $j<$gridCount; $j++){
						$offset = $j*$_variables_count;						
						$tmp[] = (int)$dataArray[$offset]['valid'];							
					}
					
					// sortiramo vrednosti in preberemo kljuce
					arsort($tmp);
					$sorted_keys = array_keys($tmp);
				}
				// Sort po prvi kategoriji
				elseif($settings['sort'] == 3){

					$tmp = Array();
					
					// preberemo prve vrednosti iz vsake vrstice
					for($j=0; $j<$_variables_count; $j++){
						$offset = $j;						
						$tmp[] = (int)$dataArray[$offset]['valid'];
					}
					
					// sortiramo vrednosti in preberemo kljuce
					arsort($tmp);
					$sorted_keys = array_keys($tmp);
				}
			
				for($i=0; $i<$gridCount; $i++){
					unset($vrednosti);
					unset($vrednostiPercent);
					unset($vrednostiValid);
					unset($vrednostiKey);
					unset($vrednostiVariable);
					
					// Poberemo podatke v posamezne tabele
					for($j=0; $j<$_variables_count; $j++){
						
						if($settings['sort'] == 1)
							$offset = ($sorted_keys[$i]*$_variables_count) + $j;

						// sort po prvi kategoriji
						elseif($settings['sort'] == 3)
							$offset = $sorted_keys[$j] + ($i*$_variables_count);
						
						else
							$offset = ($i*$_variables_count) + $j;

						// Enote
						if($settings['base'] != 1){
							$vrednosti[] = $dataArray[$offset]['freq'];						
							$vrednostiPercent[] = $dataArray[$offset]['percent'];
							$vrednostiValid[] = $dataArray[$offset]['valid'];
							
							//nastavimo numerus, ki se izpise pod legendo
							if($settings['value_type'] == 0){	
								rsort($nValid);
								$numerus = ((int)$nValid[0] > 0) ? $nValid[0] : 0;
								$DataSet->SetNumerus($numerus);
								self::$numerusText = ' ('.$lang['srv_analiza_frekvence_titleVeljavni'].')';
							}
							else
								$numerus = ((int)$nAll > 0) ? $nAll : 0;	
								
							$DataSet->SetNumerus($numerus);
						}
						// Navedbe
						else{
							$percent = ($fullPercentReverse[$j] * $dataArray[$offset]['percent'] > 0) ? 100 / $fullPercentReverse[$j] * $dataArray[$offset]['percent'] : 0;
							
							$vrednosti[] = $dataArray[$offset]['freq'];	
							$vrednostiPercent[] = $percent;	
							$vrednostiValid[] = $percent;

							//nastavimo numerus, ki se izpise pod legendo
							rsort($nNavedbe);
							$numerus = ((int)$nNavedbe[0] > 0) ? $nNavedbe[0] : 0;
							$DataSet->SetNumerus($numerus);
							self::$numerusText = ' ('.$lang['srv_analiza_opisne_arguments'].')';
						}
					
						$vrednostiKey[] = $dataArray[$offset]['key'];
						$vrednostiVariable[] = $dataArray[$offset]['variable'];	
					}

					if(count($vrednosti) > 0){
						if($settings['value_type'] == 2){
							$DataSet->AddPoint($vrednosti,'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_freq']);
						}
						elseif($settings['value_type'] == 1){
							$DataSet->AddPoint($vrednostiPercent,'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_percent']);
						}
						elseif($settings['value_type'] == 0){
							$DataSet->AddPoint($vrednostiValid,'Vrednosti_'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_valid']);
						}
					}
					else
						$DataSet->AddPoint(array(0),'Vrednosti_'.$i);

					$DataSet->AddSerie('Vrednosti_'.$i);
					if($settings['sort'] == 1)
						$DataSet->SetSerieName($vrednostiGrid[$sorted_keys[$i]],'Vrednosti_'.$i);
					else
						$DataSet->SetSerieName($vrednostiGrid[$i],'Vrednosti_'.$i);
				}

				// Vedno izpisemo cela imena variabel
				$DataSet->AddPoint($vrednostiVariable,"Variable");
					
				$DataSet->SetAbsciseLabelSerie("Variable");
			}
			
			if($settings['value_type'] == 1 || $settings['value_type'] == 0){
				$DataSet->SetYAxisUnit("%");
				$DataSet->SetYAxisFormat("number");
			}
		}
		
		// napolnimo podatke za VSOTO
		elseif($spremenljivka['tip'] == 18){				
			$dataArray = array();		
			
			$i=0;
			$numerus=0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		
				
				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);
			
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){

					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
				
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21)))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
						
							# za povprečje				
							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							$min = null;
							$max = null;
							foreach ( SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
									
								$fi = $vAnswer['cnt'];
								$sum_xi_fi += $vkey * $fi ;
								$N += $fi;
								$min = $min != null ? min($min,$vkey) : $vkey;
								$max = max($max,$vkey);	
							}
									
							#povprečje
							$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
							
							// nastavimo da graf ni prazen
							if($avg > 0)
								$emptyData = false;
							
							$numerus = ($numerus > $N) ? $numerus : $N;
							
							$tempArray = array();
								
							$tempArray['avg'] = $avg;
							$tempArray['max'] = $max;
							$tempArray['min'] = $min;
							
							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;

							// ce imamo vklopljeno da izpuscamo 0 in prikazujemo pie chart spustimo nicelne vrednosti
							if($avg != 0 || SurveyDataSettingProfiles :: getSetting('chartPieZeros') == 1 || ($settings['type'] != 0 && $settings['type'] != 5))
								$dataArray[] = $tempArray;
						}				
					}
				}	
				
				$i++;
			}	

			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;

			//polnimo podatke
			$DataSet = new pData;
			
			//nastavimo numerus, ki se izpise pod legendo
			$numerus = ((int)$numerus > 0) ? $numerus : 0;
			$DataSet->SetNumerus($numerus);

			// Sortiramo podatke in jih razvrstimo po skupinah
			if($settings['sort'] == 1){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['avg']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['avg']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			// Poberemo podatke v posamezne tabele
			for($i=0; $i<count($dataArray,0); $i++){			
				$vrednosti[] = $dataArray[$i]['avg'];
				$vrednostiMax[] = $dataArray[$i]['max'];
				$vrednostiMin[] = $dataArray[$i]['min'];
		
				$vrednostiKey[] = $dataArray[$i]['key'];
				$vrednostiVariable[] = $dataArray[$i]['variable'];
			}
					
			if(is_countable($vrednosti) && count($vrednosti) > 0){
				if($settings['value_type'] == 0){
					$DataSet->AddPoint($vrednosti,'Vrednosti');
					//$DataSet->SetYAxisName($lang['srv_chart_freq']);
				}
				elseif($settings['value_type'] == 1){
					$DataSet->AddPoint($vrednostiMax,'Vrednosti');
					//$DataSet->SetYAxisName($lang['srv_chart_percent']);
				}
				elseif($settings['value_type'] == 2){
					$DataSet->AddPoint($vrednostiMin,'Vrednosti');
					//$DataSet->SetYAxisName($lang['srv_chart_valid']);
				}
			}
			else
				$DataSet->AddPoint(array(0),'Vrednosti');
			
			$DataSet->AddSerie('Vrednosti');
			$DataSet->SetSerieName('Povprečja','Vrednosti');
			
			// Vedno izpisemo cela imena variabel
			$DataSet->AddPoint($vrednostiVariable,"Variable");
			//$DataSet->AddPoint($vrednostiKey,"Variable");
				
			$DataSet->SetAbsciseLabelSerie("Variable");
			if($settings['type'] != 2)
				$DataSet->SetYAxisName($lang['srv_analiza_sums_average']);
		}
		
		// napolnimo podatke za RANKING
		elseif($spremenljivka['tip'] == 17){
			$dataArray = array();		
			
			$i=0;
			$numerus=0;
			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		
				
				$legendTitle = '';
					
				$_variables_count = count($grid['variables']);
			
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){

					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));
				
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21)))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
						
							$rankingArray = array();
							$_variables_count=0;
						
							# za povprečje				
							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							$min = null;
							$max = null;
							foreach ( SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {

								$fi = $vAnswer['cnt'];
								$sum_xi_fi += $vkey * $fi ;
								$N += $fi;
								$min = $min != null ? min($min,$vkey) : $vkey;
								$max = max($max,$vkey);	
								
								$rankingArray[] = $vAnswer['cnt'];
								$_variables_count++;
							}
							
							// nastavimo da graf ni prazen
							if($N > 0)
								$emptyData = false;
							
							#povprečje
							$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
							$avg = $avg < 1 ? 1 : $avg;
							
							if($settings['type'] == 0){
								$numerus = ($numerus > $N) ? $numerus : $N;
							}
							else{
								$numerus = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'];
							}
							
							$tempArray = array();
							
							$tempArray['cnt'] = $N;
							$tempArray['avg'] = $avg;
							$tempArray['max'] = $max;
							$tempArray['min'] = $min;
							
							$tempArray['rankings'] = $rankingArray;
							
							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;

							$dataArray[] = $tempArray;
						}				
					}
				}	
				
				$i++;
			}		

			//polnimo podatke
			$DataSet = new pData;
			
			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;
			
			//nastavimo numerus, ki se izpise pod legendo
			$numerus = ((int)$numerus > 0) ? $numerus : 0;
			$DataSet->SetNumerus($numerus);

			// Sortiramo podatke in jih razvrstimo po skupinah
			if($settings['sort'] == 1){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['avg']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_DESC, $dataArray);
			}
			elseif($settings['sort'] == 2){
				$tmp = Array();
				foreach($dataArray as &$data) 
					$tmp[] = &$data['avg']; 				
				array_multisort($tmp, SORT_NUMERIC, SORT_ASC, $dataArray);
			}

			if($settings['type'] == 0){
				// Poberemo podatke v posamezne tabele
				for($i=0; $i<count($dataArray,0); $i++){			
					$vrednosti[] = $dataArray[$i]['avg'];
					$vrednostiMax[] = $dataArray[$i]['max'];
					$vrednostiMin[] = $dataArray[$i]['min'];
			
					$vrednostiKey[] = $dataArray[$i]['key'];
					$vrednostiVariable[] = $dataArray[$i]['variable'];
				}
						
				if(count($vrednosti) > 0){
					if($settings['value_type'] == 0){
						$DataSet->AddPoint($vrednosti,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_freq']);
					}
					elseif($settings['value_type'] == 1){
						$DataSet->AddPoint($vrednostiMax,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_percent']);
					}
					elseif($settings['value_type'] == 2){
						$DataSet->AddPoint($vrednostiMin,'Vrednosti');
						//$DataSet->SetYAxisName($lang['srv_chart_valid']);
					}
				}
				else
					$DataSet->AddPoint(array(0),'Vrednosti');
					
				$DataSet->AddSerie('Vrednosti');
				$DataSet->SetSerieName('Povprečja','Vrednosti');
			}
			
			//polnimo podatke po posameznih serijah
			else{			
				// loop cez variable za posamezno serijo
				for($i=0; $i<$_variables_count; $i++){
					
					unset($vrednosti);
					unset($vrednostiPercent);
					unset($vrednostiKey);
					unset($vrednostiVariable);
					
					for($j=0; $j<count($dataArray,0); $j++){			
						$vrednosti[] = $dataArray[$j]['rankings'][$i];
						
						if($dataArray[$j]['cnt'] > 0)
							$percent = $dataArray[$j]['rankings'][$i] / $dataArray[$j]['cnt'] * 100;
						else
							$percent = 0;
							
						$vrednostiPercent[] = $percent;
				
						$vrednostiKey[] = $dataArray[$j]['key'];
						$vrednostiVariable[] = $dataArray[$j]['variable'];
					}
					
					if(count($vrednosti) > 0){
						if($settings['value_type'] == 0){
							$DataSet->AddPoint($vrednosti,'Vrednosti'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_freq']);
						}
						elseif($settings['value_type'] == 1){
							$DataSet->AddPoint($vrednostiPercent,'Vrednosti'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_percent']);
						}
						elseif($settings['value_type'] == 2){
							$DataSet->AddPoint($vrednostiPercent,'Vrednosti'.$i);
							//$DataSet->SetYAxisName($lang['srv_chart_valid']);
						}
					}
					else
						$DataSet->AddPoint(array(0),'Vrednosti'.$i);

					$DataSet->AddSerie('Vrednosti'.$i);
					$DataSet->SetSerieName($i+1,'Vrednosti'.$i);
				}
			}		
			
			
			
			// Vedno izpisemo cela imena variabel
			$DataSet->AddPoint($vrednostiVariable,"Variable");
			//$DataSet->AddPoint($vrednostiKey,"Variable");
				
			$DataSet->SetAbsciseLabelSerie("Variable");
			if($settings['type'] > 1)
				$DataSet->SetYAxisName("Povprečje");
		}
		
		// napolnimo podatke za MULTINUMBER
		elseif($spremenljivka['tip'] == 20){				
			$dataArray = array();		
			
			$i=0;
			$numerus=0;
			
			$sql = sisplet_query("SELECT count(*) AS count FROM srv_grid WHERE spr_id='$spid'");
			$row = mysqli_fetch_array($sql);
			$_variables_count = $row['count'];	
			
			$vrednostiGrid = array();

			if (count($spremenljivka['grids']) > 0)	
			foreach ($spremenljivka['grids'] AS $gid => $grid) {		

				$legendTitle = '';
				$vrednostiGrid[] = $spremenljivka['edit_graf'] == 0 ? $grid['naslov'] : $grid['naslov_graf'];
					
				//$_variables_count = count($grid['variables']);
			
				# dodamo dodatne vrstice z albelami grida
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){

					$legendTitle = substr($variable['variable'],0,strpos($variable['variable'],'_'));

					$_sequence = $variable['sequence'];	# id kolone z podatki
					if (($variable['text'] != true && $variable['other'] != true) || (in_array($spremenljivka['tip'],array(4,8,21)))){

						if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
						
							# za povprečje				
							$sum_xi_fi=0;
							$N = 0;
							$div=0;
							$min = null;
							$max = null;
							foreach ( SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
									
								$fi = $vAnswer['cnt'];
								$sum_xi_fi += $vkey * $fi ;
								$N += $fi;
								$min = $min != null ? min($min,$vkey) : $vkey;
								$max = max($max,$vkey);	
							}
									
							#povprečje
							$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
							
							// nastavimo da graf ni prazen
							if($avg > 0)
								$emptyData = false;
							
							$numerus = ($numerus > $N) ? $numerus : $N;
							
							$tempArray = array();
								
							$tempArray['avg'] = $avg;
							$tempArray['max'] = $max;
							$tempArray['min'] = $min;
							
							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;

							$dataArray[] = $tempArray;
						}

						// ce missling (-99, -98...) nima nobene vrednosti potem ga ni v tabeli - zato ga rocno napolnimo
						else{
							$tempArray = array();
								
							$tempArray['avg'] = 0;
							$tempArray['max'] = 0;
							$tempArray['min'] = 0;
							
							$tempArray['key'] = $variable['variable'];
							
							$text = $spremenljivka['edit_graf'] == 0 ? $variable['naslov'] : $variable['naslov_graf'];
							$tempArray['variable'] = $text;
							
							$dataArray[] = $tempArray;
						}
					}
					
					// polnimo array za drugo
					if ($variable['text'] == true || $variable['other'] == true){
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
				}	
				
				$i++;
			}	

			// zascita pred praznimi vprasanji (brez variabel)
			if($_variables_count == 0)
				return -1;

			//polnimo podatke
			$DataSet = new pData;
			
			//nastavimo numerus, ki se izpise pod legendo
			$numerus = ((int)$numerus > 0) ? $numerus : 0;
			$DataSet->SetNumerus($numerus);

			
			// Normalno obrnjen graf - gridi v stolpcih, variable v legendi (deli stolpcev)
			if($settings['rotate'] != 1 /*$_variables_count > 1*/){
				
				// Sortiramo podaatke ce je potrebno				
				if($settings['sort'] == 1){

					$tmp = Array();
					
					// preberemo prve vrednosti iz vsakega stolpca
					for($j=0; $j<$_variables_count; $j++){
						$offset = $j*$_variables_count;						
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
					for($j=0; $j<$_variables_count; $j++){
						$offset = $j;
						$tmp[] = (int)$dataArray[$offset]['avg'];
					}
					
					// sortiramo vrednosti in preberemo kljuce
					arsort($tmp);
					$sorted_keys = array_keys($tmp);
				}
				else{
					for($j=0; $j<(count($spremenljivka['grids'])*$_variables_count); $j++){
						$sorted_keys[] = $j;						
					}
				}
				
				// Poberemo podatke v posamezne tabele
				for($j=0; $j<count($spremenljivka['grids']); $j++){
					
					unset($vrednosti);
					unset($vrednostiKey);
					unset($vrednostiVariable);
					
					// odmik glede na sortirane po prvem gridu (sort po kategorijah ali brez)
					if($settings['sort'] < 3){
						$offset = $sorted_keys[$j] /*$j*/ * $_variables_count;

						for($i=0; $i<$_variables_count; $i++){			
							$vrednosti[] = $dataArray[$i+$offset]['avg'];
							$vrednostiMax[] = $dataArray[$i+$offset]['max'];
							$vrednostiMin[] = $dataArray[$i+$offset]['min'];
					
							$vrednostiKey[] = $dataArray[$i+$offset]['key'];
							$vrednostiVariable[] = $dataArray[$i+$offset]['variable'];
						}
								
						if(count($vrednosti) > 0)
							$DataSet->AddPoint($vrednosti,'Vrednosti'.$sorted_keys[$j]);
						else
							$DataSet->AddPoint(array(0),'Vrednosti'.$sorted_keys[$j]);
						
						$DataSet->AddSerie('Vrednosti'.$sorted_keys[$j]);
						$DataSet->SetSerieName($vrednostiGrid[$sorted_keys[$j]],'Vrednosti'.$sorted_keys[$j]);
						
						// Vedno izpisemo cela imena variabel
						$DataSet->AddPoint($vrednostiVariable,'Variable'.$sorted_keys[$j]);
						//$DataSet->AddPoint($vrednostiKey,"Variable");
							
						$DataSet->SetAbsciseLabelSerie('Variable'.$sorted_keys[$j]);
					}
						
					// sort po prvi kategoriji
					else{
						for($i=0; $i<$_variables_count; $i++){			
							$vrednosti[] = $dataArray[$j*$_variables_count + $sorted_keys[$i]]['avg'];
							$vrednostiMax[] = $dataArray[$j*$_variables_count + $sorted_keys[$i]]['max'];
							$vrednostiMin[] = $dataArray[$j*$_variables_count + $sorted_keys[$i]]['min'];
					
							$vrednostiKey[] = $dataArray[$j*$_variables_count + $sorted_keys[$i]]['key'];
							$vrednostiVariable[] = $dataArray[$j*$_variables_count + $sorted_keys[$i]]['variable'];
						}
								
						if(count($vrednosti) > 0)
							$DataSet->AddPoint($vrednosti,'Vrednosti'.$j);
						else
							$DataSet->AddPoint(array(0),'Vrednosti'.$j);
						
						$DataSet->AddSerie('Vrednosti'.$j);
						$DataSet->SetSerieName($vrednostiGrid[$j],'Vrednosti'.$j);
						
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
					for($j=0; $j<$_variables_count; $j++){
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
					for($j=0; $j<count($spremenljivka['grids']); $j++){
						$offset = $j * $_variables_count;						
						$tmp[] = (int)$dataArray[$offset]['avg'];
					}
					
					// sortiramo vrednosti in preberemo kljuce
					arsort($tmp);
					$sorted_keys = array_keys($tmp);
				}
				else{
					for($j=0; $j<$_variables_count; $j++){
						$sorted_keys[] = $j;						
					}
				}

				// Poberemo podatke v posamezne tabele
				for($j=0; $j<$_variables_count; $j++){
				
					// odmik glede na sortirane po prvem gridu (sort po kategorijah ali brez)
					if($settings['sort'] < 3){
						$offset = $sorted_keys[$j];

						unset($vrednosti);
						unset($vrednostiKey);
						unset($vrednostiVariable);
					
						for($i=0; $i<count($spremenljivka['grids']); $i++){
						
							$vrednosti[] = $dataArray[$i*$_variables_count+$offset]['avg'];
							$vrednostiMax[] = $dataArray[$i*$_variables_count+$offset]['max'];
							$vrednostiMin[] = $dataArray[$i*$_variables_count+$offset]['min'];
					
							$vrednostiKey[] = $dataArray[$i*$_variables_count+$offset]['key'];
							$vrednostiVariable[] = $dataArray[$i*$_variables_count+$offset]['variable'];
						}
								
						if(count($vrednosti) > 0)
							$DataSet->AddPoint($vrednosti,'Vrednosti'.$vrednostiKey[0]);
						else
							$DataSet->AddPoint(array(0),'Vrednosti'.$vrednostiKey[0]);
						
						$DataSet->AddSerie('Vrednosti'.$vrednostiKey[0]);
						$DataSet->SetSerieName($vrednostiVariable[0],'Vrednosti'.$vrednostiKey[0]);
						
						// Vedno izpisemo cela imena variabel
						$DataSet->AddPoint($vrednostiGrid,'Variable'.$vrednostiKey[0]);
						//$DataSet->AddPoint($vrednostiKey,"Variable");
							
						$DataSet->SetAbsciseLabelSerie('Variable'.$vrednostiKey[0]);
					}
					
					// sort po prvi kategoriji
					else{						
						$offset = $sorted_keys[$j];

						unset($vrednosti);
						unset($vrednostiKey);
						unset($vrednostiVariable);
					
						for($i=0; $i<count($spremenljivka['grids']); $i++){

							$vrednosti[] = $dataArray[$sorted_keys[$i]*$_variables_count + $j]['avg'];
							$vrednostiMax[] = $dataArray[$sorted_keys[$i]*$_variables_count + $j]['max'];
							$vrednostiMin[] = $dataArray[$sorted_keys[$i]*$_variables_count + $j]['min'];
					
							$vrednostiKey[] = $dataArray[$sorted_keys[$i]*$_variables_count + $j]['key'];
							$vrednostiVariable[] = $dataArray[$sorted_keys[$i]*$_variables_count + $j]['variable'];
						}
								
						if(count($vrednosti) > 0)
							$DataSet->AddPoint($vrednosti,'Vrednosti'.$vrednostiKey[0]);
						else
							$DataSet->AddPoint(array(0),'Vrednosti'.$vrednostiKey[0]);
						
						$DataSet->AddSerie('Vrednosti'.$vrednostiKey[0]);
						$DataSet->SetSerieName($vrednostiVariable[0],'Vrednosti'.$vrednostiKey[0]);
						
						// Vedno izpisemo cela imena variabel
						$DataSet->AddPoint($vrednostiGrid,'Variable'.$vrednostiKey[0]);
						//$DataSet->AddPoint($vrednostiKey,"Variable");
							
						$DataSet->SetAbsciseLabelSerie('Variable'.$vrednostiKey[0]);
					}
				}
			}
			
			//$DataSet->SetYAxisName($lang['srv_analiza_sums_average']);
		}
                		
		// Nastavimo other vrednosti
		$DataSet->SetOther($_answersOther);
                
		// ce imamo prazno in de prikazujemo praznih grafov
		$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
		if($emptyData && $hideEmpty == 1)
			return 0;
		else
			return $DataSet;
	}
	
	
	// Preverimo ce ima dropdown samo numeric vrednosti -> potem ga obravnavamo kot tip number
	static function checkDropdownNumeric($spid){
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		$check = true;
		
		foreach ($spremenljivka['options'] AS $option) {					
			if(!is_numeric($option))
				$check = false;
		}
		
		return $check;
	}
	
	
	// Funkcije za izris posameznih tipov grafov - vertikalni stolpci
	static function createVerBars($DataSet, $spremenljivka, $show_legend=0, $fixedScale=0){
		
		$Data = $DataSet->GetData();
		$countGrids = count($Data);
		
		$angle = 0; 
		$addHeight = 0;
		$roundText = 15;
		if($countGrids > 5){
			$angle = 45;
			$addHeight = 110;
			$roundText = 25;
		}
		if($show_legend == 1)
			$addHeight += 70;
		
		// Initialise the graph
		$Test = new pChart(self::$quality*800,self::$quality*(250+$addHeight));

		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);

		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		$Test->setGraphArea(self::$quality*100,self::$quality*40,self::$quality*650,self::$quality*220);
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		
		// Pri checkboxu lahko naredimo fiksno skalo
		if($spremenljivka['tip'] == 2 && self::$settings['noFixedScale'] == 1){
			// Frekvence
			if(self::$settings['value_type'] == 1){
				// Dobimo sum frekvenc
				$sum = 0;
				foreach($Data as $vrednost){
					$sum += $vrednost['Vrednosti'];
				}
				$Test->setFixedScale(0, $sum);
			}
			// Odstotki
			else
				$Test->setFixedScale(0, 100);
		}

		// Pri dvojnem multigridu prikazemo skalo od 1 do stevila variabel
		if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
			
			$VMax = count($spremenljivka['options']);
			
			// Zacnemo skalo z 1
			if($fixedScale == 0){
				$Divisions = $VMax-1;
				$VMin = 1;
			}
			// Zacnemo skalo z 0
			else{
				$VMax--;
				$Divisions = $VMax;
				$VMin = 0;	
			}

			$Test->setFixedScale($VMin, $VMax, $Divisions);
		}
		
		$Test->drawScale($Data,$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,$angle,0,TRUE,1,FALSE,$roundText);
		$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		// Draw the bar graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		$Test->drawBarGraph($Data,$DataSet->GetDataDescription(), false, 95, self::$settings['barLabel'], self::$settings['barLabelSmall']);
		
		// Finish the graph
		if($show_legend == 1)
			//$Test->drawLegend(self::$quality*680,self::$quality*30,$DataSet->GetDataDescription(),255,255,255);
			// pri vodoravnih strukturnih stolpcih izrisemo legendo na dnu
			$Test->drawVerticalLegend(self::$quality*400,self::$quality*(190+$addHeight),$DataSet->GetDataDescription(),255,255,255);	
			
		//$Test->setFontProperties("Fonts/verdana.ttf",10);
		//$Test->drawTitle(50,22,$spremenljivka['variable'].' - '.$spremenljivka['naslov'],50,50,50,585);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';	
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		$Test->drawTextBox(self::$quality*680,self::$quality*210,self::$quality*795,self::$quality*220,$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		
		
		$vars = isset($spremenljivka['options']) ? count($spremenljivka['options']) : 0;
		// Prikaz povprecja na grafu (samo pri ordinalnem radiu)
		if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) 
			&& $spremenljivka['skala'] != 1 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
				
				$Test->drawTextBox(self::$quality*680,self::$quality*40,self::$quality*795,self::$quality*45,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*680,self::$quality*45,self::$quality*795,self::$quality*50,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		// Prikaz povprecja za number
		if( ($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 22)
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
			
				$Test->drawTextBox(self::$quality*680,self::$quality*90,self::$quality*795,self::$quality*95,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*680,self::$quality*95,self::$quality*795,self::$quality*100,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}

		
		return $Test;
	}

	// Funkcije za izris posameznih tipov grafov - horizontalni stolpci
	static function createHorBars($DataSet, $spremenljivka, $show_legend=0, $fixedScale=0){
		global $lang;
		
		// Nastavimo visino grafa (ce imamo vec kot 7 variabel/gridov)
		$Data = $DataSet->GetData();
		$countGrids = count($Data);	
		$addHeight = $countGrids > 5 ? ($countGrids-5)*30 : 0;
		
		// Dodamo prostor na dnu za legendo pri multigrid povprecjih
		$addLegendSpace = 0;
		if($show_legend == 1 && $spremenljivka['tip'] == 6 && self::$settings['type'] == 0 && $spremenljivka['enota'] != 3)
			$addLegendSpace = 70;
			
		
		// Initialise the graph
		$Test = new MyHorBar(self::$quality*800,self::$quality*(250+$addHeight+$addLegendSpace));
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Sirina label
		// Ce iamo povprecje ni labele
		if(in_array($spremenljivka['tip'],array(1,3,7)) && self::$settings['type'] == 9){
			$startX = 160;
			$roundText = 35;
		}
		elseif(self::$settings['labelWidth'] == 20){
			$startX = 225;
			$roundText = 35;
		}
		elseif(self::$settings['labelWidth'] == 75){
			$startX = 500;
			$roundText = 80;
		}
		else{
			$startX = 360;
			$roundText = 60;
		}
		
		$Test->setGraphArea(self::$quality*$startX,self::$quality*50,self::$quality*650,self::$quality*(220+$addHeight));
		
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*(243+$addHeight+$addLegendSpace),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*(245+$addHeight+$addLegendSpace),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		
		// Pri ordinalnih multigridih prikazemo skalo od 1 do stevila variabel (ce prikazujemo povprecja)
		if( ($spremenljivka['tip'] == 6 && self::$settings['type'] == 0) || ($spremenljivka['tip'] == 17 && self::$settings['type'] == 0) ){
			
			$VMax = count($spremenljivka['options']);
			
			// Zacnemo skalo z 1
			if($fixedScale == 0){
				$Divisions = $VMax-1;
				$VMin = 1;
			}
			// Zacnemo skalo z 0
			else{
				$VMax--;
				$Divisions = $VMax;
				$VMin = 0;
			}

			$Test->setFixedScale($VMin, $VMax, $Divisions);
		}
		
		// Pri checkboxu lahko naredimo fiksno skalo
		if($spremenljivka['tip'] == 2 && self::$settings['noFixedScale'] == 1){
			// Frekvence
			if(self::$settings['value_type'] == 1){
				// Dobimo sum frekvenc
				$sum = 0;
				foreach($DataSet->GetData() as $vrednost){
					$sum += $vrednost['Vrednosti'];
				}
				$Test->setFixedScale(0, $sum);
			}
			// Odstotki
			else
				$Test->setFixedScale(0, 100);
		}
		
		// Pri povprecju (radio) poiscemo najvecjo vrednost
		if(in_array($spremenljivka['tip'],array(1,3)) && self::$settings['type'] == 9){
			
			$VMax = count($spremenljivka['options']);
			foreach($DataSet->GetData() as $vrednost){
				$VMax = ($VMax < $vrednost['Vrednosti']) ? $vrednost['Vrednosti'] : $VMax;
			}
			
			$VMin = 0;

			$Test->setFixedScale($VMin, $VMax);
		}
		// Pri povprecju (numeric) poiscemo najvecjo vrednost
		elseif(($spremenljivka['tip'] == 7  || $spremenljivka['tip'] == 22) && self::$settings['type'] == 9){
						
			$VMax = 1;		
			$sequences = explode('_', $spremenljivka['sequences']);
			foreach($sequences as $sequence){
				if (count(SurveyAnalysis::$_FREQUENCYS[$sequence]['valid']) > 0 ) {		
					foreach(SurveyAnalysis::$_FREQUENCYS[$sequence]['valid'] AS $vkey => $vAnswer) {
						$VMax = ($VMax < (int)$vAnswer['text']) ? (int)$vAnswer['text'] : $VMax;
					}
				}
			}
			
			$VMin = 0;

			$Test->setFixedScale($VMin, $VMax);
		}
		
		$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,0,0,TRUE,1,FALSE,$roundText);
		$Test->drawHorGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Draw the bar graph
		$Test->drawHorBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(), self::$settings['barLabel'], self::$settings['barLabelSmall']);

		// Finish the graph
		if($show_legend == 1){
			// posebna legenda pri povprecjih
			if($spremenljivka['tip'] == 6 && self::$settings['type'] == 0 && $spremenljivka['enota'] != 3)
				//$Test->drawAvgVerticalLegend(self::$quality*680,self::$quality*30,$spremenljivka['options'],255,255,255);
				$Test->drawAvgVerticalLegend(self::$quality*400,self::$quality*(190+$addHeight+$addLegendSpace),$spremenljivka['options'],255,255,255);	
			else
				$Test->drawLegend(self::$quality*680,self::$quality*30,$DataSet->GetDataDescription(),255,255,255);
		}
					
		if($spremenljivka['tip'] == 18 || $spremenljivka['tip'] == 20)
			$Test->drawTitle(self::$quality*200,self::$quality*22,$lang['srv_analiza_sums_average'],0,0,0,self::$quality*680);
			
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
			
		$Test->drawTextBox(self::$quality*680,self::$quality*(210+$addHeight),self::$quality*795,self::$quality*(220+$addHeight),$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		

		$vars = isset($spremenljivka['options']) ? count($spremenljivka['options']) : 0;		
		// Prikaz povprecja na grafu (samo pri ordinalnem radiu)
		if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) 
			&& $spremenljivka['skala'] != 1 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
			
				$Test->drawTextBox(self::$quality*680,self::$quality*50,self::$quality*795,self::$quality*55,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*680,self::$quality*55,self::$quality*795,self::$quality*60,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		// Prikaz povprecja za number
		if( ($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 22) 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
			
				$Test->drawTextBox(self::$quality*680,self::$quality*90,self::$quality*795,self::$quality*95,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*680,self::$quality*95,self::$quality*795,self::$quality*100,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - vertikalni sestavljeni stolpci
	static function createVerStructBars($DataSet, $spremenljivka){
		
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
		$Test = new pChart(self::$quality*800,self::$quality*(250+$addHeight));
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Pri navadnem radio in checkbox vprasanju imamo samo en stolpec - zato so dimenzije drugacne
		if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3)
			$Test->setGraphArea(self::$quality*250,self::$quality*40,self::$quality*500,self::$quality*220);
		else
			$Test->setGraphArea(self::$quality*100,self::$quality*40,self::$quality*650,self::$quality*220);
		
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,0,0,0,TRUE,$angle,0,TRUE,1,FALSE,$roundText);
		$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		// Draw the bar graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		$Test->drawStackedBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(), self::$settings['barLabel'], 95);
		
		// Finish the graph		
		if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3)
			$Test->drawLegend(self::$quality*580,self::$quality*30,$DataSet->GetDataDescription(),255,255,255,$Rs=-1,$Gs=-1,$Bs=-1,$Rt=0,$Gt=0,$Bt=0,$Border=false,$reverse=true);
		else
			$Test->drawLegend(self::$quality*680,self::$quality*30,$DataSet->GetDataDescription(),255,255,255,$Rs=-1,$Gs=-1,$Bs=-1,$Rt=0,$Gt=0,$Bt=0,$Border=false,$reverse=true);
			
		$Test->setFontProperties("Fonts/verdana.ttf",self::$quality*10);
		//$Test->drawTitle(50,22,$spremenljivka['variable'].' - '.$spremenljivka['naslov'],50,50,50,585);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3)
			$Test->drawTextBox(self::$quality*540,self::$quality*210,self::$quality*645,self::$quality*220,$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		else
			$Test->drawTextBox(self::$quality*680,self::$quality*210,self::$quality*795,self::$quality*220,$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		
		
		$vars = count($spremenljivka['options']);
		// Prikaz povprecja na grafu (samo pri ordinalnem radiu)
		if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) 
			&& $spremenljivka['skala'] != 1 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
			
				$Test->drawTextBox(self::$quality*350,self::$quality*25,self::$quality*400,self::$quality*30,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*350,self::$quality*30,self::$quality*400,self::$quality*35,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - horizontalni sestavljeni stolpci
	static function createHorStructBars($DataSet, $spremenljivka){
		
		// Nastavimo visino graffa (ce imamo vec kot 7 variabel/gridov)
		$Data = $DataSet->GetData();
		$countGrids = count($Data);	
		$addHeight = $countGrids > 5 ? ($countGrids-5)*30 : 0;
		
		// Imamo semanticni dif. - izpisujemo labele na desni
		$rightScale = ($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 1 && self::$settings['scale_limit'] == 1) ? true : false;		
		
		// Initialise the graph
		$Test = new MyHorBar(self::$quality*800,self::$quality*(250+$addHeight+50));
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		$Test->setLineStyle(self::$quality,$DotSize=0);		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Sirina label
		if(self::$settings['labelWidth'] == 20){
			$startX = 225;
			$roundText = 35;
		}
		elseif(self::$settings['labelWidth'] == 75){
			$startX = 500;
			$roundText = 80;
		}
		else{
			$startX = 360;
			$roundText = 60;
		}
		
		// Pri navadnem radio in checkbox vprasanju imamo samo en stolpec - zato so dimenzije drugacne
		if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3){
			$Test->setGraphArea(self::$quality*200,self::$quality*50,self::$quality*630,self::$quality*220);
			$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*243,5,255,255,255);
			//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
			$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*295,200,200,200);
			$Test->drawGraphArea(255,255,255,TRUE);
			$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,0,0,0,TRUE,0,0,TRUE);
			$Test->drawHorGrid(4,TRUE,230,230,230,50);
		}
		// Semanticni diferencial s skalo na desni
		elseif($rightScale){
			$Test->setGraphArea(self::$quality*270,self::$quality*50,self::$quality*530,self::$quality*(220+$addHeight));
			$Test->drawFilledRoundedRectangle(7,7,793,243+$addHeight,5,255,255,255);
			//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
			$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*(295+$addHeight),200,200,200);
			$Test->drawGraphArea(255,255,255,TRUE);
					
			$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0/*SCALE_START0*/,0,0,0,TRUE,0,0,TRUE,1,$rightScale,$roundText=40);
			$Test->drawHorGrid(4,false,230,230,230,50);
		}
		else{
			$Test->setGraphArea(self::$quality*$startX,self::$quality*50,self::$quality*650,self::$quality*(220+$addHeight));
			$Test->drawFilledRoundedRectangle(7,7,793,243+$addHeight,5,255,255,255);
			//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
			$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*(295+$addHeight),200,200,200);
			$Test->drawGraphArea(255,255,255,TRUE);
			$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,0,0,0,TRUE,0,0,TRUE,1,FALSE,$roundText);
			$Test->drawHorGrid(4,TRUE,230,230,230,50);
		}	

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Draw the bar graph
		$Test->drawStackedHorBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),self::$settings['barLabel'],95);
		
		// Finish the graph	
		/*if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3)
			$Test->drawLegend(560,30,$DataSet->GetDataDescription(),255,255,255);
		else
			$Test->drawLegend(680,30,$DataSet->GetDataDescription(),255,255,255);*/
			
		// pri vodoravnih strukturnih stolpcih izrisemo legendo na dnu
		$Test->drawVerticalLegend(self::$quality*400,self::$quality*(240+$addHeight),$DataSet->GetDataDescription(),255,255,255);
		
		$Test->setFontProperties("Fonts/verdana.ttf",self::$quality*10);
		//$Test->drawTitle(50,22,$spremenljivka['variable'].' - '.$spremenljivka['naslov'],50,50,50,585);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		/*if($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 3)
			$Test->drawTextBox(570,210+$addHeight,795,220+$addHeight,'n = '.$DataSet->GetNumerus().self::$numerusText,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		else*/
		$Test->drawTextBox(self::$quality*680,self::$quality*(210+$addHeight),self::$quality*795,self::$quality*(220+$addHeight),$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		
		
		$vars = count($spremenljivka['options']);		
		// Prikaz povprecja na grafu (samo pri ordinalnem radiu)
		if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) 
			&& $spremenljivka['skala'] != 1 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
			
				$Test->drawTextBox(self::$quality*680,self::$quality*50,self::$quality*795,self::$quality*55,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*680,self::$quality*55,self::$quality*795,self::$quality*60,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - krozni graf
	static function createPie($DataSet, $spremenljivka, $show_legend=1){
		global $lang;
	
		// Initialise the graph
		$Test = new pChart(self::$quality*800,self::$quality*280);
		
		// Pri pie grafu uporabimo antialiasing
		$Test->setAntialias(true, 20);
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		//$Test->setGraphArea(50,40,685,220);
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*273,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*275,200,200,200);
		//$Test->createColorGradientPalette(195,204,56,223,110,41,3);
		//$Test->createColorGradientPalette(168,188,56,248,255,136,5);
		
		
		// Pri vsoti ne izpisujemo procentov
		if($spremenljivka['tip'] == 18 || ($spremenljivka['tip'] == 1 && self::$settings['type'] == 2 && self::$settings['value_type'] == 1))
			$labels = (self::$settings['sort'] == 1) ? 'custom_sort' : 'custom';
		else
			$labels = (self::$settings['sort'] == 1) ? 'custom_percent_sort' : 'custom_percent';
		

		// Izrisemo navaden krozni graf
		$Test->drawFlatPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),self::$quality*390,self::$quality*145,self::$quality*95,$labels);
		
			
		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		//$Test->drawLegend(700,30,$DataSet->GetDataDescription(),255,255,255);
		
		if($show_legend == 1)
			$Test->drawPieLegend(self::$quality*600,self::$quality*50,$DataSet->GetData(),$DataSet->GetDataDescription(),255,255,255);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*10);
		
		if($spremenljivka['tip'] == 18)
			$Test->drawTitle(self::$quality*180,self::$quality*30,$lang['srv_analiza_sums_average'],0,0,0,self::$quality*610);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		$Test->setAntialias(false, 0);
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		$Test->drawTextBox(self::$quality*600,self::$quality*220,self::$quality*715,self::$quality*230,$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		

		$vars = count($spremenljivka['options']);	
		// Prikaz povprecja na grafu (samo pri ordinalnem radiu)
		if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) 
			&& $spremenljivka['skala'] != 1 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
				
				$Test->drawTextBox(self::$quality*600,self::$quality*240,self::$quality*715,self::$quality*245,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*600,self::$quality*245,self::$quality*715,self::$quality*250,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - 3D krozni graf
	static function create3DPie($DataSet, $spremenljivka, $show_legend=1){
		global $lang;
	
		// Initialise the graph
		$Test = new pChart(self::$quality*800,self::$quality*280);
		
		// Pri 3d pie grafu uporabimo antialiasing
		$Test->setAntialias(true, 20);
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		//$Test->setGraphArea(50,40,685,220);
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*273,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*275,200,200,200);
		//$Test->createColorGradientPalette(195,204,56,223,110,41,3);
		//$Test->createColorGradientPalette(168,188,56,248,255,136,5);
		
		
		// Pri vsoti ne izpisujemo procentov
		if($spremenljivka['tip'] == 18 || ($spremenljivka['tip'] == 1 && self::$settings['type'] == 2 && self::$settings['value_type'] == 1))
			$labels = (self::$settings['sort'] == 1) ? 'custom_sort' : 'custom';
		else
			$labels = (self::$settings['sort'] == 1) ? 'custom_percent_sort' : 'custom_percent';
		
		
		// Izrisemo 3d krozni graf
		$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),self::$quality*390,self::$quality*130,self::$quality*95,$labels,$EnhanceColors=true,$Skew=50,$SpliceHeight=self::$quality*20,$SpliceDistance=0,$Decimals=0);
		
			
		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		//$Test->drawLegend(700,30,$DataSet->GetDataDescription(),255,255,255);
		
		if($show_legend == 1)
			$Test->drawPieLegend(self::$quality*600,self::$quality*50,$DataSet->GetData(),$DataSet->GetDataDescription(),255,255,255);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*10);
		
		if($spremenljivka['tip'] == 18)
			$Test->drawTitle(self::$quality*180,self::$quality*30,$lang['srv_analiza_sums_average'],0,0,0,self::$quality*610);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		$Test->setAntialias(false, 0);
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		$Test->drawTextBox(self::$quality*600,self::$quality*220,self::$quality*715,self::$quality*230,$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		

		$vars = count($spremenljivka['options']);	
		// Prikaz povprecja na grafu (samo pri ordinalnem radiu)
		if( ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) 
			&& $spremenljivka['skala'] != 1 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
				
				$Test->drawTextBox(self::$quality*600,self::$quality*240,self::$quality*715,self::$quality*245,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*600,self::$quality*245,self::$quality*715,self::$quality*250,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - linijski graf
        //spremenljivka je lahko null za linijski graf analize editiranja (SurveyEditsAnalysis)
	static function createLine($DataSet, $spremenljivka, $show_legend=0, $fixedScale=0){
            
		// Initialise the graph
		$Test = new pChart(self::$quality*800,self::$quality*280);
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		if($spremenljivka != null && ($spremenljivka['tip'] == 6 && $fixedScale == 1 && $spremenljivka['enota'] != 3) || ($spremenljivka['tip'] == 6 && $fixedScale == 0 && $spremenljivka['enota'] == 3)){
			
			$VMax = count($spremenljivka['options']);
			$Divisions = $VMax-1;
		
			$Test->setFixedScale($VMin=1, $VMax, $Divisions);
		}
				
		$count = count($DataSet->GetData());
                
		// Ce imamo numeric vse vrednosti in jih je vec kot 20 omejimo max 20 label na X osi
		$SkipLabels = 1;
		if($spremenljivka != null && ($spremenljivka['tip'] == 7  || $spremenljivka['tip'] == 22) && $count > 20)
			$SkipLabels = $count / 20;
		
		// Kot label na x osi
		$angle = 0;
		if($count > 6)
			$angle = 45;
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		$Test->setGraphArea(self::$quality*100,self::$quality*40,self::$quality*650,self::$quality*220);
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*273,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*275,200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
		
		// Pri checkboxu lahko naredimo fiksno skalo
		if($spremenljivka != null  && $spremenljivka['tip'] == 2 && self::$settings['noFixedScale'] == 1){
			// Frekvence
			if(self::$settings['value_type'] == 1){
				// Dobimo sum frekvenc
				$sum = 0;
				foreach($DataSet->GetData() as $vrednost){
					$sum += $vrednost['Vrednosti'];
				}
				$Test->setFixedScale(0, $sum);
			}
			// Odstotki
			else
				$Test->setFixedScale(0, 100);
		}
		
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,$angle,0,TRUE, $SkipLabels);
		if($count <= 20)
			$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		//$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		// Draw the bar graph
		$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
		if($count <= 20)
			$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),self::$quality*3,self::$quality*2,255,255,255);
		
		if($show_legend == 1)
			$Test->drawLegend(self::$quality*680,self::$quality*30,$DataSet->GetDataDescription(),255,255,255);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka != null && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16))) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		$Test->drawTextBox(self::$quality*690,self::$quality*(210+$addHeight),self::$quality*795,self::$quality*(220+$addHeight),$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		
		
		// Prikaz povprecja za number
		if($spremenljivka != null && ($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 22) 
			&& (self::$settings['show_avg'] == 1 || (self::$settings['show_avg'] == -1 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ){
			
				$Test->drawTextBox(self::$quality*690,self::$quality*80,self::$quality*795,self::$quality*85,'x = '.$DataSet->GetAverage(),$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
				$Test->drawTextBox(self::$quality*690,self::$quality*85,self::$quality*795,self::$quality*90,'&#8254;',$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);		
		}
		
		
		return $Test;
	}
	
	// Funkcije za izris posameznih tipov grafov - linijski graf
	static function createVerLine($DataSet, $spremenljivka, $show_legend=0, $fixedScale=0){
		
		// Nastavimo visino grafa (ce imamo vec kot 7 variabel/gridov)
		$Data = $DataSet->GetData();
		$countGrids = count($Data);	
		$addHeight = $countGrids > 5 ? ($countGrids-5)*30 : 0;

		// Imamo semanticni dif. - izpisujemo labele na desni
		$rightScale = ($spremenljivka['enota'] == 1 && self::$settings['scale_limit'] == 1) ? true : false;
		
		// Initialise the graph
		$Test = new MyHorBar(self::$quality*800,self::$quality*(250+$addHeight));
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		if($spremenljivka['tip'] == 6 && $fixedScale == 0){
			
			$VMax = count($spremenljivka['options']);
			$Divisions = $VMax-1;
		
			$Test->setFixedScale($VMin=1, $VMax, $Divisions);
		}
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
				
		$Test->setGraphArea(self::$quality*270,self::$quality*50,self::$quality*530,self::$quality*(220+$addHeight));
		
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*(243+$addHeight),5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*(245+$addHeight),200,200,200);
		$Test->drawGraphArea(255,255,255,TRUE);
				
		$Test->drawHorScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,0,0,0,TRUE,0,0,TRUE,1,$rightScale,$roundText=40);
		$Test->drawHorGrid(4,false,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*6);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Draw the line graph
		$Test->drawVerLineGraph($DataSet->GetData(),$DataSet->GetDataDescription(), $insideValues=false);
		
		// Finish the graph
		if($show_legend == 1){
			// posebna legenda pri povprecjih
			if($spremenljivka['tip'] == 6 && self::$settings['type'] == 6)
				$Test->drawAvgLegend(self::$quality*680,self::$quality*30,$spremenljivka['options'],255,255,255);
			else
				$Test->drawLegend(self::$quality*680,self::$quality*30,$DataSet->GetDataDescription(),255,255,255);
		}
			
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		if($spremenljivka['tip'] == 18)
			$Test->drawTitle(self::$quality*200,self::$quality*22,'Povprečje',150,150,150,self::$quality*585);
			
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		$Test->drawTextBox(self::$quality*680,self::$quality*(210+$addHeight),self::$quality*795,self::$quality*(220+$addHeight),$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		
		return $Test;		
	}
	
	// Funkcije za izris posameznih tipov grafov - vertikalni stolpci
	static function createRadar($DataSet, $spremenljivka, $show_legend=0, $fixedScale=0){
		
		$Data = $DataSet->GetData();
		$countGrids = count($Data);
		
		// Initialise the graph
		$Test = new pChart(self::$quality*800,self::$quality*350);

		// Pri radarju uporabimo antialiasing
		$Test->setAntialias(true, 20);
		
		// Nastavimo barve grafu glede na skin
		$Test = self::setChartColors($Test, self::$skin);
		
		$Test->setLineStyle(self::$quality,$DotSize=0);
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		$Test->setGraphArea(self::$quality*100,self::$quality*40,self::$quality*650,self::$quality*320);
		$Test->drawFilledRoundedRectangle(self::$quality*7,self::$quality*7,self::$quality*793,self::$quality*343,5,255,255,255);
		//$Test->drawRoundedRectangle(5,5,795,245,5,128,128,128);
		$Test->drawRectangle(self::$quality*5,self::$quality*5,self::$quality*795,self::$quality*345,200,200,200);
		//$Test->drawGraphArea(255,255,255,TRUE);
		//$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,20,20,20,TRUE,$angle,0,TRUE,1,FALSE,$roundText);
		//$Test->drawGrid(4,TRUE,230,230,230,50);

		// Draw the 0 line
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

		
		// Pri ordinalnih multigridih prikazemo skalo od 1 do stevila variabel (ce prikazujemo povprecja)
		if($spremenljivka['tip'] == 6 /*&& $spremenljivka['skala'] == 0*/ && $fixedScale == 0){
			
			$VMax = count($spremenljivka['options']) - 1;
			$Divisions = $VMax-1;
		
			$Test->setFixedScale($VMin=1, $VMax, $Divisions);
		}
		else
			$VMax = -1;

			
		// Draw the radar
		$Test->drawRadarAxis($DataSet->GetData(),$DataSet->GetDataDescription(),true,5,0,0,0,160,160,160,$VMax,self::$settings['radar_scale']);
		// Tip radarja - navaden ali samo crte
		if(self::$settings['radar_type'] == 1)
			$Test->drawFilledRadar($DataSet->GetData(),$DataSet->GetDataDescription(),50,5,$VMax);
		else{
			$Test->setLineStyle($Width=(2*self::$quality),$DotSize=0);
			$Test->drawRadar($DataSet->GetData(),$DataSet->GetDataDescription(),5,$VMax);
		}
		
		$Test->setAntialias(false, 0);
		
		// Finish the graph
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		if($show_legend == 1){		
			// posebna legenda pri povprecjih
			if($spremenljivka['tip'] == 6 && self::$settings['type'] == 5)
				$Test->drawAvgLegend(self::$quality*680,self::$quality*30,$spremenljivka['options'],255,255,255);
			else
				$Test->drawLegend(self::$quality*680,self::$quality*30,$DataSet->GetDataDescription(),255,255,255);
		}
				
		$Test->setFontProperties("Fonts/verdana.ttf",self::$quality*10);
		//$Test->drawTitle(50,22,$spremenljivka['variable'].' - '.$spremenljivka['naslov'],50,50,50,585);
		
		$Test->setFontProperties(dirname(__FILE__).'/../../pChart/Fonts/verdana.ttf',self::$quality*self::$fontSize);
		
		// Prikaz numerusa na grafu
		$char = (self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16)) ? 'r' : 'n';
		if( self::$settings['show_numerus'] == 1 || (self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1) )
			$numerus = $char.' = '.$DataSet->GetNumerus();
		elseif( self::$settings['show_numerus'] == -1 && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 2 )
			$numerus = $char.' = ' . $DataSet->GetNumerus() . self::$numerusText;
		else
			$numerus = '';
		
		$Test->drawTextBox(self::$quality*600,self::$quality*220,self::$quality*715,self::$quality*230,$numerus,$Angle=0,$R=0,$G=0,$B=0,$Align=ALIGN_LEFT,$Shadow=FALSE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=0);
		
		return $Test;
	}					
								
	
	//	Nastavitve na vrhu
	static function displayGlobalSettings(){
		global $lang;
		global $admin_type;
		global $site_url;
		
		self::$skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');

		//moznost osvezevanja grafov - ne uporabljamo zaenkrat
		if($admin_type < 2 && false)
			echo '<a href="'.$site_url.'?anketa='.self::$anketa.'&a=analysis&m=charts&refresh=1"><img src="img_0/random_off.png" title="Osveži grafe" /></a>';	
		
		// Izrisemo ostale filtre
		SurveyAnalysis::DisplayFilters(self::$quality);
		
		// prklop na vecjo resolucijo grafov (zaenkrat tukaj - kasneje v globalne nastavitve)
		//self::displayHQSetting();
		

		echo '<div id="displayFilterNotes">';
		# če imamo filter zoom ga izpišemo
		SurveyZoom::getConditionString();
		# če imamo filter ifov ga izpišemo
		SurveyConditionProfiles:: getConditionString();
		# če imamo filter ifov za inspect ga izpišemo
		$SI = new SurveyInspect(self::$anketa);
		$SI->getConditionString();
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		SurveyTimeProfiles :: printIsDefaultProfile();
		# če imamo filter spremenljivk ga izpišemo
		SurveyVariablesProfiles:: getProfileString(true);
		
		# če imamo rekodiranje
		$SR = new SurveyRecoding(self::$anketa);
		$SR -> getProfileString();
		
		SurveyDataSettingProfiles :: getVariableTypeNote($doNewLine);
		echo '</div>';
	}

	public static function displayHQSetting(){
		global $lang;
		
		echo '<div id="chart_hq_setting" class="analiza">';
		
		echo '<ul>';
		echo '<li>';
		echo $lang['srv_chart_hq'].': ';
		echo '<input type="checkbox" name="chart_hq" id="chart_hq" onClick="changeChartHq(this)" '.(self::$quality == 3 ? ' checked="checked"' : '').'>';			
		echo '</li>';
		
		# nastavitev skina grafov
		echo '<li>';
		$SSH = new SurveyStaticHtml(self::$anketa);
		$SSH -> displayLinkChart(false);
		echo '</li>';
		
		echo '</div>';
	}
	
	// Nastavitve na dnu
	static function displayBottomSettings(){
		global $site_path;
		global $lang;
		
		echo '<div class="chart_bottom_settings">';
		
		echo '<a href="#" onClick="addCustomReportAllElementsAlert(4);" title="'.$lang['srv_custom_report_comments_add_hover'].'" style="margin-right: 40px;"><span class="spaceRight faicon comments_creport" ></span><span class="bold">'.$lang['srv_custom_report_comments_add'].'</span></a>';
		
		echo '<a href="#" onClick="printAnaliza(\'Grafi\'); return false;" title="'.$lang['hour_print2'].'" class="srv_ico"><span class="faicon print icon-grey_dark_link"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts&anketa=' . self::$anketa) . '" target="_blank" title="'.$lang['PDF_Izpis'].'"><span class="faicon pdf black very_large" ></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts_rtf&anketa=' . self::$anketa) . '" target="_blank" title="'.$lang['RTF_Izpis'].'"><span class="faicon rtf black very_large"></span></a>';
		echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts_ppt&anketa=' . self::$anketa) . '" target="_blank" title="'.$lang['PPT_Izpis'].'"><span class="faicon ppt black very_large"></span></a>';
		
		echo '<a href="#" onclick="doArchiveAnaliza();" title="'.$lang['srv_analiza_arhiviraj_ttl'].'"><span class="faicon arhiv black very_large"></span></a>';
		echo '<a href="#" onclick="createArchiveBeforeEmail();" title="'.$lang['srv_analiza_arhiviraj_email_ttl'] . '"><span class="faicon arhiv_mail black very_large"></span></a>';			
		
		echo '</div>';
	}
	
	// Pripis pod grafom (numerus, povprecje, spremenljivka...)
	static function displayBottomChartInfo($DataSet, $spremenljivka){
		global $site_path;
		global $lang;
		
		echo '<div class="chart_bottom_info">';
		echo '<ul>';
		
		// spremenljivka
		echo '<li>'.$lang['srv_spremenljivka'].': <span class="strong">'.$spremenljivka['variable'].'</span> <span class="anl_ita">('.$lang['srv_vprasanje_tip_'.$spremenljivka['tip']].')</span></li>';
		
		// numerus
		if(self::$settings['base'] == 1 && ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16))
			echo '<li>,&nbsp; r = <span class="strong">'.$DataSet->GetNumerus().'</span> <span class="anl_ita">'.self::$numerusText.'</span></li>';	
		else
			echo '<li>,&nbsp; n = <span class="strong">'.$DataSet->GetNumerus().'</span> <span class="anl_ita">'.self::$numerusText.'</span></li>';
		
		// povprecje (ce je radio ali droipdown ordinalna)
		if(($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) && $spremenljivka['skala'] != 1)
			echo '<li>,&nbsp; <span style="text-decoration: overline;">x</span> = <span class="strong">'.$DataSet->GetAverage().'</span></li>';
			
		echo '</ul>';
		echo '</div>';
	}
	
	
	// Nastavitve posameznega grafa
	static function displaySingleSettings($spid, $settings=0){
		global $site_path;
		global $lang;
		if (self::$publicChart == true) {
            return false;
        }
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		// Ikone izvoza na vrhu posameznih nastavitev
		self::displayExportIcons($spid);
		
		
		echo '<div id="switch_left_'.$spid.'_loop_'.self::$current_loop.'" class="switch_left '.(self::$settings_mode == 1 ? ' non-active' : '').'" onClick="chartSwitchSettings(\''.$spid.'\', \'0\', \''.self::$current_loop.'\')">'.$lang['srv_chart_settings_basic'].'</div>';		
		//echo '<span id="switch_middle_'.$spid.'_loop_'.self::$current_loop.'" class="'.(self::$settings_mode == 1 ? 'rightHighlight' : 'leftHighlight').'"></span>';	
		echo '<div id="switch_right_'.$spid.'_loop_'.self::$current_loop.'" class="switch_right '.(self::$settings_mode == 0 ? ' non-active' : '').'" onClick="chartSwitchSettings(\''.$spid.'\', \'1\', \''.self::$current_loop.'\')">'.$lang['srv_chart_settings_advanced'].'</div>';
		
		
		// OSNOVNE NASTAVITVE
		echo '<div class="chart_settings_inner" id="chart_settings_basic_'.$spid.'_loop_'.self::$current_loop.'" '.(self::$settings_mode == 1 ? ' style="display:none;"' : '').'>';
		
		//echo '<span class="title">'.$lang['srv_chart_settings'].'</span>';

		switch($spremenljivka['tip']){
			case 1:
			case 3:
				self::displayRadioSettings($spid, $settings);
				break;
			
			case 2:
				self::displayCheckboxSettings($spid, $settings);
				break;
			
			case 6:
				if($spremenljivka['enota'] == 3)
					self::displayDoubleMultigridSettings($spid, $settings);
				else
					self::displayMultigridSettings($spid, $settings);
				break;
			
			case 7:
                        case 22:
				self::displayNumberSettings($spid, $settings);
				break;
			
			case 8:
				self::displayDateSettings($spid, $settings);
				break;
								
			case 16:
				self::displayMulticheckboxSettings($spid, $settings);
				break;
				
			case 17:
				self::displayRankingSettings($spid, $settings);
				break;	
				
			case 18:
				self::displayVsotaSettings($spid, $settings);
				break;
				
			case 20:
				self::displayMultinumberSettings($spid, $settings);
				break;
				
			case 21:
			case 4:
				self::displayTableSettings($spid);
				break;
				
			case 19:
				self::displayMultitextSettings($spid, $settings);
				break;
				
			default:
				break;
		}
		
		// Preview vprasanja
		//SurveyAnalysis::showVariable($spid, $spremenljivka['variable']);
		echo '<div class="chart_setting" style="text-align: center; margin-top: 20px;">';
		echo '<span style="margin-right: 6px; line-height: 6px; font-weight: 600; font-size: 11px;">';
		//echo $lang['srv_vprasanje'].': ';
		echo $spremenljivka['variable'];
		echo '</span>';
		//echo '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="preview_spremenljivka_analiza(\'' . $spid . '\'); return false;"><span class="sprites preview"></span></a>';
		echo '<a href="#" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\''.$spid.'\'); return false;"><span class="faicon preview"></span></a> ';	
		//echo '</div>';
			
		//echo '<div class="chart_setting" style="text-align: center;">';
		SurveyAnalysis::showIcons($spid,$spremenljivka,$_from='charts');
		echo '</div>';
				
		echo '</div>';
		
		
		// NAPREDNE NASTAVITVE
		echo '<div class="chart_settings_inner" id="chart_settings_advanced_'.$spid.'_loop_'.self::$current_loop.'" '.(self::$settings_mode == 0 ? ' style="display:none;"' : '').'>';
		
		switch($spremenljivka['tip']){
			case 1:
			case 3:
				self::displayAdvancedRadioSettings($spid, $settings);
				break;
			
			case 2:
				self::displayAdvancedCheckboxSettings($spid, $settings);
				break;
			
			case 6:
				if($spremenljivka['enota'] == 3)
					self::displayAdvancedDoubleMultigridSettings($spid, $settings);
				else
					self::displayAdvancedMultigridSettings($spid, $settings);
				break;
			
			case 7:
                        case 22:
				self::displayAdvancedNumberSettings($spid, $settings);
				break;
			
			case 8:
				self::displayAdvancedDateSettings($spid, $settings);
				break;
								
			case 16:
				self::displayAdvancedMulticheckboxSettings($spid, $settings);
				break;
				
			case 17:
				self::displayAdvancedRankingSettings($spid, $settings);
				break;	
				
			case 18:
				self::displayAdvancedVsotaSettings($spid, $settings);
				break;
				
			case 20:
				self::displayAdvancedMultinumberSettings($spid, $settings);
				break;
								
			default:
				break;
		}
		
		// Link na urejanje label
		echo '<span class="edit" style="margin-top:15px;" onclick="chartAdvancedSettings(\''.$spid.'\', 1, \''.self::$current_loop.'\');">'.$lang['srv_chart_advancedLink_labels'].'</span>';	
		// Vprasajcek za pomoc
		echo Help :: display('displaychart_settings_labels');
		
		// Link na urejanje barv
		echo '<span class="edit" onclick="chartAdvancedSettings(\''.$spid.'\', \'2\', \''.self::$current_loop.'\');">'.$lang['srv_chart_advancedLink_colors'].'</span>';	
		// Vprasajcek za pomoc
		echo Help :: display('displaychart_settings_colors');		
		
		// Link na rekodiranje
		echo '<span class="edit" onclick="chartAdvancedSettings(\''.$spid.'\', \'3\', \''.self::$current_loop.'\');">'.$lang['srv_chart_advancedLink_recoding'].'</span>';	
		// Vprasajcek za pomoc
		echo Help :: display('displaychart_settings_recoding');
		
		// Link na napredne number (radio dropdown numeric, number, date) nastavitve
		if(($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)) || $spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8 || $spremenljivka['tip'] == 22){
			echo '<span class="edit" onclick="chartAdvancedSettings(\''.$spid.'\', \'4\', \''.self::$current_loop.'\');">'.$lang['srv_chart_advancedLink_limits'].'</span>';	
			// Vprasajcek za pomoc
			//echo Help :: display('displaychart_settings_number');
		}
			
			
		echo '</div>';
	}
	
	// ikone na vrhu posameznih nastavitev (izvozi)
	static function displayExportIcons($spid){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		$loop = (isset(SurveyAnalysis::$_CURRENT_LOOP)) ? SurveyAnalysis::$_CURRENT_LOOP['cnt'] : 'undefined';
		
		// linki
		echo '<div class="chart_setting_exportLinks">';
				
			// Ikona za print
			echo '<a href="#" onclick="showAnalizaSingleChartPopup(\''.$spid.'\',\''.M_ANALYSIS_CHARTS.'\'); return false;">';		
			echo '<span class="faicon print_small icon-grey_dark_link" title="' . $lang['PRN_Izpis'] . '"></span>';
			echo '</a>';

			// Izvoz posameznega grafa v PDF/RTF/PPT
			echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts&anketa='.self::$anketa.'&sprID='.$spid.'&loop='.$loop).'" target="_blank" title="'.$lang['PDF_Izpis'].'"><span class="faicon pdf"></span></a>';
			echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts_rtf&anketa='.self::$anketa.'&sprID='.$spid.'&loop='.$loop).'" target="_blank" title="'.$lang['RTF_Izpis'].'"><span class="faicon rtf"></span></a>';
			// V PPT zaenkrat ne izvazamo tabel
			if($spremenljivka['tip'] != 4 && $spremenljivka['tip'] != 19 && $spremenljivka['tip'] != 21)
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?m=charts_ppt&anketa='.self::$anketa.'&sprID='.$spid.'&loop='.$loop).'" target="_blank" title="'.$lang['PPT_Izpis'].'"><span class="faicon ppt"></span></a>';
			
		echo '</div>';
	}
	
	// Nastavitve za radio grafe (tip 1,3)
	static function displayRadioSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		// Nastavitve numeric dropdowna - obravnavamo kot number
		if($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)){
			// Tip grafa
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 1, \'type\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="5" '.($settings['type']=='5'?' selected="selected"':'').'>'.$lang['srv_chart_group_horizontal'].'</option>';
			echo '  <option value="6" '.($settings['type']=='6'?' selected="selected"':'').'>'.$lang['srv_chart_group_vertical'].'</option>';
			echo '  <option value="7" '.($settings['type']=='7'?' selected="selected"':'').'>'.$lang['srv_chart_group_line'].'</option>';
			echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
			echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
			
			echo '</select>';
			echo '</div>';
			
			// tip izpisa vrednosti
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 1, \'value_type\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
			echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';
			echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';
			
			echo '</select>';
			echo '</div>';
			
			// sortiranje
			if($settings['type'] < 5){
				echo '<div class="chart_setting">';
				
				echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 1, \'sort\', \''.self::$current_loop.'\');">';
				
				echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
				echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
				echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
				
				echo '</select>';
				
				echo '</div>';
			}
			
			// stevilo intervalov
			if($settings['type'] > 4){
				echo '<div class="chart_setting">';
				echo $lang['srv_chart_interval'].': <select id="chart_interval_'.$spid.'_loop_'.self::$current_loop.'" name="chart_interval" onchange="changeChart(\''.$spid.'\', 1, \'interval\', \''.self::$current_loop.'\');">';
				
				for($i=3; $i<=10; $i++){				
					echo '  <option value="'.$i.'" '.($settings['interval']==$i ?' selected="selected"':'').'>'.$i.'</option>';
				}
				echo '  <option value="20" '.($settings['interval']=='20'?' selected="selected"':'').'>20</option>';
				//echo '  <option value="50" '.($settings['interval']=='50'?' selected="selected"':'').'>50</option>';
				//echo '  <option value="100" '.($settings['interval']=='100'?' selected="selected"':'').'>100</option>';
				echo '  <option value="-1" '.($settings['interval']=='-1'?' selected="selected"':'').'>Vsi</option>';
				
				echo '</select>';
				echo '</div>';
			}
			
			// prikaz label v stolpcih
			if($settings['type'] == 0 || $settings['type'] == 1 || $settings['type'] == 3 || $settings['type'] == 4){
				echo '<div class="chart_setting">';
				
				echo $lang['srv_chart_barLabel'].': ';
				echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 1, \'barLabel\', \''.self::$current_loop.'\');">';

				echo '</div>';
			}
		}
		
		// Nastavitve radia in navadnega dropdowna
		else{
			// Tip grafa
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 1, \'type\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
			echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
			echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_pie'].'</option>';
			echo '  <option value="8" '.($settings['type']=='8'?' selected="selected"':'').'>'.$lang['srv_chart_3Dpie'].'</option>';
			echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_structure1_hor'].'</option>';
			echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_structure1_ver'].'</option>';
			if($spremenljivka['skala'] != 1)
				echo '  <option value="9" '.($settings['type']=='9'?' selected="selected"':'').'>'.$lang['srv_chart_avg_single'].'</option>';
			
			echo '</select>';
			echo '</div>';
			
			// sortiranje
			if($settings['type'] != 9){
				echo '<div class="chart_setting">';
				
				echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 1, \'sort\', \''.self::$current_loop.'\');">';
					
				echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
				echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
				echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
				
				echo '</select>';
				
				echo '</div>';
			}
				
			// tip izpisa vrednosti	
			if($settings['type'] != 9){
				echo '<div class="chart_setting">';
				echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 1, \'value_type\', \''.self::$current_loop.'\');">';
					
				echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';
				echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
				if($settings['type'] != 2 && $settings['type'] != 8)
					echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';
				
				echo '</select>';
				echo '</div>';
			}
					
			// prikaz legende
			if($settings['type'] == 2 || $settings['type'] == 8){
				echo '<div class="chart_setting">';
				
				echo $lang['srv_analiza_legenda'].': ';
				echo '<input type="checkbox" id="chart_show_legend_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_legend" '.($settings['show_legend']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 1, \'show_legend\', \''.self::$current_loop.'\');">';

				echo '</div>';
			}
			
			// prikaz label v stolpcih
			if($settings['type'] == 0 || $settings['type'] == 1 || $settings['type'] == 3 || $settings['type'] == 4 || $settings['type'] == 9){
				echo '<div class="chart_setting">';
				
				echo $lang['srv_chart_barLabel'].': ';
				echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 1, \'barLabel\', \''.self::$current_loop.'\');">';

				echo '</div>';
			}
		}
	}
	
	// Nastavitve za radio grafe (tip 1,3) - NAPREDNO
	static function displayAdvancedRadioSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 1, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// prikaz povprecja
		if($spremenljivka['skala'] != 1 && $settings['type'] != 9){
			echo '<div class="chart_setting">';
			
			$vars = count($spremenljivka['options']);	
			$checked = ($settings['show_avg']=='1' || ($settings['show_avg']=='-1' && $vars == 5 && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ? ' checked="checked"': '';
			
			echo $lang['srv_chart_showAvg'].': ';
			echo '<input type="checkbox" id="chart_show_avg_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_avg" '.$checked.' onchange="changeChart(\''.$spid.'\', 1, \'show_avg\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
			
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 1, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// 3D strukturni krog
		/*if($settings['type'] == 2){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_3d_pie'].': ';
			echo '<input type="checkbox" id="chart_3d_pie_'.$spid.'_loop_'.self::$current_loop.'" name="chart_3d_pie" '.($settings['3d_pie']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 1, \'3d_pie\', \''.self::$current_loop.'\');">';

			echo '</div>';	
		}*/
		
		// Izpusti variable brez odgovora
		if(($spremenljivka['tip'] != 3 || !self::checkDropdownNumeric($spid)) && $settings['type'] != 9){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_hideEmtyVar'].': ';
			echo '<input type="checkbox" id="chart_hideEmptyVar_'.$spid.'_loop_'.self::$current_loop.'" name="chart_hideEmptyVar" '.($settings['hideEmptyVar']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 1, \'hideEmptyVar\', \''.self::$current_loop.'\');">';

			echo '</div>';	
		}
		
		// prikaz label majhnih vrednosti zraven stolpcov
		if($settings['barLabel'] == 1 && ($settings['type'] == 0 || $settings['type'] == 1)){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabelSmall'].': ';
			echo '<input type="checkbox" id="chart_barLabelSmall_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabelSmall" '.($settings['barLabelSmall']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 1, \'barLabelSmall\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// Preklop med ordinalno in nominalno spremenljivko - ce imamo povprecja ne smemo preklopiti na nominalno
		if($settings['type'] != 9){
			echo '<div class="chart_setting">';
			
			$lestvica = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skalaAsValue');
						
			echo $lang['srv_skala'].': ';
			// Vprasajcek za pomoc
			echo Help :: display('srv_skala_edit');
			
			echo '<span class="spaceLeft"></span>';
			echo '<a onclick="chartAdvancedSettingsSkala(\''.$spid.'\', \'0\', \''.self::$current_loop.'\'); return false;" href="#" title="'.$lang['srv_skala_long_0'].'"><span '.($lestvica == 0 ? ' class="strong"' : '').'>'.$lang['srv_skala_short_0'].'</span></a>';
			echo '<span class="blue"> / </span>';
			echo '<a onclick="chartAdvancedSettingsSkala(\''.$spid.'\', \'1\', \''.self::$current_loop.'\'); return false;" href="#" title="'.$lang['srv_skala_long_1'].'"><span '.($lestvica == 1 ? ' class="strong"' : '').'>'.$lang['srv_skala_short_1'].'</span></a>';
					
			echo '</div>';	
		}
	}
		
	// Nastavitve za checkbox grafe (tip 2)
	static function displayCheckboxSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// omejitev skale
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_base'].': <select id="chart_base_'.$spid.'_loop_'.self::$current_loop.'" name="chart_base" onchange="changeChart(\''.$spid.'\', 2, \'base\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['base']=='0'?' selected="selected"':'').'>'.$lang['srv_analiza_opisne_units'].'</option>';
		echo '  <option value="1" '.($settings['base']=='1'?' selected="selected"':'').'>'.$lang['srv_analiza_opisne_arguments'].'</option>';

		echo '</select>';
		echo '</div>';
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 2, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		if($settings['base'] != '0'){
			echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_pie'].'</option>';
			echo '  <option value="7" '.($settings['type']=='7'?' selected="selected"':'').'>'.$lang['srv_chart_3Dpie'].'</option>';
			echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_structure1_hor'].'</option>';
			echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_structure1_ver'].'</option>';
		}
		else{
			echo '  <option value="5" '.($settings['type']=='5'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
			echo '  <option value="6" '.($settings['type']=='6'?' selected="selected"':'').'>'.$lang['srv_chart_line'].'</option>';
		}
		
		echo '</select>';
		echo '</div>';
		
		// Tip radarja		
		if($settings['type'] == '5'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="chart_radar_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_type" onchange="changeChart(\''.$spid.'\', 2, \'radar_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
			
		// Postavitev skale pri radarju
		if($settings['type'] == '5'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="chart_radar_scale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_scale" onchange="changeChart(\''.$spid.'\', 2, \'radar_scale\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// tip izpisa vrednosti
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 2, \'value_type\', \''.self::$current_loop.'\');" '.($settings['type'] == 2 ? 'disabled="disabled"' : '').'>';
			
		if($settings['base'] != '1')
			echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';
		echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
		echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// sortiranje
		if($settings['type'] != 5){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 2, \'sort\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
			echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
			
			echo '</select>';

			echo '</div>';
		}	
		
		// prikaz legende
		if($settings['type'] == 2 || $settings['type'] == 7){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_analiza_legenda'].': ';
			echo '<input type="checkbox" id="chart_show_legend_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_legend" '.($settings['show_legend']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 2, \'show_legend\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz label v stolpcih
		if($settings['type'] == 0 || $settings['type'] == 1 || $settings['type'] == 3 || $settings['type'] == 4){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 2, \'barLabel\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
	}
	
	// Nastavitve za checkbox grafe (tip 2)
	static function displayAdvancedCheckboxSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 2, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
			
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 2, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// Izpusti variable brez odgovora
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_hideEmtyVar'].': ';
		echo '<input type="checkbox" id="chart_hideEmptyVar_'.$spid.'_loop_'.self::$current_loop.'" name="chart_hideEmptyVar" '.($settings['hideEmptyVar']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 2, \'hideEmptyVar\', \''.self::$current_loop.'\');">';

		echo '</div>';	
		
		// prikaz polne skale
		if($settings['type'] == 0 || $settings['type'] == 1 || $settings['type'] == 6){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_settings_fullScale'].': ';
			echo '<input type="checkbox" id="chart_noFixedScale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_noFixedScale" '.($settings['noFixedScale']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 2, \'noFixedScale\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz label majhnih vrednosti zraven stolpcov
		if($settings['barLabel'] == 1 && ($settings['type'] == 0 || $settings['type'] == 1)){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabelSmall'].': ';
			echo '<input type="checkbox" id="chart_barLabelSmall_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabelSmall" '.($settings['barLabelSmall']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 2, \'barLabelSmall\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}		
		
		// 3D strukturni krog
		/*if($settings['type'] == 2){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_3d_pie'].': ';
			echo '<input type="checkbox" id="chart_3d_pie_'.$spid.'_loop_'.self::$current_loop.'" name="chart_3d_pie" '.($settings['3d_pie']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 2, \'3d_pie\', \''.self::$current_loop.'\');">';

			echo '</div>';	
		}*/
	}
	
	// Nastavitve za number grafe (tip 7)
	static function displayNumberSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 7, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_group_horizontal'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_group_vertical'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_group_line'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		echo '  <option value="9" '.($settings['type']=='9'?' selected="selected"':'').'>'.$lang['srv_chart_avg_single'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// tip izpisa vrednosti
		if($settings['type'] != 9){
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 7, \'value_type\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
			echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';
			echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// sortiranje
		if($settings['type'] > 2 && $settings['type'] != 9){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 7, \'sort\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
			echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
			
			echo '</select>';
				
			echo '</div>';
		}
		
		// stevilo intervalov
		if($settings['type'] < 3){
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_interval'].': <select id="chart_interval_'.$spid.'_loop_'.self::$current_loop.'" name="chart_interval" onchange="changeChart(\''.$spid.'\', 7, \'interval\', \''.self::$current_loop.'\');">';
				
			for($i=3; $i<=10; $i++){				
				echo '  <option value="'.$i.'" '.($settings['interval']==$i ?' selected="selected"':'').'>'.$i.'</option>';
			}
			echo '  <option value="20" '.($settings['interval']=='20'?' selected="selected"':'').'>20</option>';
			//echo '  <option value="50" '.($settings['interval']=='50'?' selected="selected"':'').'>50</option>';
			//echo '  <option value="100" '.($settings['interval']=='100'?' selected="selected"':'').'>100</option>';
			echo '  <option value="-1" '.($settings['interval']=='-1'?' selected="selected"':'').'>Vsi</option>';
			
			echo '</select>';
			echo '</div>';
		}
						
		// prikaz legende
		echo '<div class="chart_setting">';
		
		echo $lang['srv_analiza_legenda'].': ';
		echo '<input type="checkbox" id="chart_show_legend_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_legend" '.($settings['show_legend']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 7, \'show_legend\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// div z nastavitvami za zgornjo in spodnjo mejo
		/*echo '<fieldset class="chart_num_limits"><legend>'.$lang['srv_chart_num_limit'].'</legend>';
		
		// min
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_min'].': ';
		echo '<input type="text" id="chart_min_'.$spid.'_loop_'.self::$current_loop.'" name="chart_min" value="'.$settings['min'].'" onBlur="changeChart(\''.$spid.'\', 7, \'min\', \''.self::$current_loop.'\');" onkeypress="checkNumber(this, 6, 0);" onkeyup="checkNumber(this, 6, 0);" />';
		
		echo '</div>';
		
		// polodprt interval navzdol
		echo '<div class="chart_setting" style="text-align:right;">';
		
		echo $lang['srv_chart_open_down'].': ';
		echo '<input type="checkbox" id="chart_open_down_'.$spid.'_loop_'.self::$current_loop.'" name="chart_open_down" '.($settings['open_down']=='1'?' checked="checked"':'').' '.($settings['min']==''?' disabled="disabled"':'').' onchange="changeChart(\''.$spid.'\', 7, \'open_down\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// max
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_max'].': ';
		echo '<input type="text" id="chart_max_'.$spid.'_loop_'.self::$current_loop.'" name="chart_max" value="'.$settings['max'].'" onBlur="changeChart(\''.$spid.'\', 7, \'max\', \''.self::$current_loop.'\');" onkeypress="checkNumber(this, 6, 0);" onkeyup="checkNumber(this, 6, 0);" />';
		
		echo '</div>';
		
		// polodprt interval navzgor
		echo '<div class="chart_setting" style="text-align:right;">';
		
		echo $lang['srv_chart_open_up'].': ';
		echo '<input type="checkbox" id="chart_open_up_'.$spid.'_loop_'.self::$current_loop.'" name="chart_open_up" '.($settings['open_up']=='1'?' checked="checked"':'').' '.($settings['max']==''?' disabled="disabled"':'').' onchange="changeChart(\''.$spid.'\', 7, \'open_up\', \''.self::$current_loop.'\');">';

		echo '</div>';
				
		echo '</fieldset>';*/
	}
	
	// Nastavitve za number grafe (tip 7)
	static function displayAdvancedNumberSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 7, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// prikaz povprecja
		if($settings['type'] != 9){
			echo '<div class="chart_setting">';
			
			$checked = ($settings['show_avg']=='1' || ($settings['show_avg']=='-1' && SurveyDataSettingProfiles :: getSetting('chartAvgText') == 1)) ? ' checked="checked"': '';

			echo $lang['srv_chart_showAvg'].': ';
			echo '<input type="checkbox" id="chart_show_avg_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_avg" '.$checked.' onchange="changeChart(\''.$spid.'\', 7, \'show_avg\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz label v stolpcih
		if($settings['type'] != 2){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 7, \'barLabel\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz label majhnih vrednosti zraven stolpcov
		if($settings['barLabel'] == 1 && $settings['type'] != 2 && $settings['type'] != 9){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabelSmall'].': ';
			echo '<input type="checkbox" id="chart_barLabelSmall_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabelSmall" '.($settings['barLabelSmall']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 7, \'barLabelSmall\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 7, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
	}
	
	// Nastavitve za datum grafe (tip 8)
	static function displayDateSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 8, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_group_horizontal'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_group_vertical'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_line'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// tip izpisa vrednosti
		if($settings['type'] < 3){
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 8, \'value_type\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_day'].'</option>';
			echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_month'].'</option>';
			echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_year'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// sortiranje
		if($settings['type'] > 2){
			echo '<div class="chart_setting">';

			echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 8, \'sort\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
			echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
			
			echo '</select>';

			echo '</div>';
		}
		
		// stevilo intervalov
		if($settings['type'] < 3){
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_interval'].': <select id="chart_interval_'.$spid.'_loop_'.self::$current_loop.'" name="chart_interval" onchange="changeChart(\''.$spid.'\', 8, \'interval\', \''.self::$current_loop.'\');">';
				
			for($i=3; $i<=10; $i++){				
				echo '  <option value="'.$i.'" '.($settings['interval']==$i ?' selected="selected"':'').'>'.$i.'</option>';
			}
			echo '  <option value="20" '.($settings['interval']=='20'?' selected="selected"':'').'>20</option>';
			//echo '  <option value="50" '.($settings['interval']=='50'?' selected="selected"':'').'>50</option>';
			//echo '  <option value="100" '.($settings['interval']=='100'?' selected="selected"':'').'>100</option>';
			echo '  <option value="-1" '.($settings['interval']=='-1'?' selected="selected"':'').'>Vsi</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// div z nastavitvami za zgornjo in spodnjo mejo
		/*echo '<fieldset class="chart_num_limits"><legend>'.$lang['srv_chart_num_limit'].'</legend>';
		
		// min
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_min'].': ';
		echo '<input type="text" id="chart_min_'.$spid.'_loop_'.self::$current_loop.'" name="chart_min" value="'.$settings['min'].'" onBlur="changeChart(\''.$spid.'\', 8, \'min\', \''.self::$current_loop.'\');" onkeypress="checkNumber(this, 6, 0);" onkeyup="checkNumber(this, 6, 0);" />';
		
		echo '</div>';
		
		// polodprt interval navdol
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_open_down'].': ';
		echo '<input type="checkbox" id="chart_open_down_'.$spid.'_loop_'.self::$current_loop.'" name="chart_open_down" '.($settings['open_down']=='1'?' checked="checked"':'').' '.($settings['min']==''?' disabled="disabled"':'').' onchange="changeChart(\''.$spid.'\', 8, \'open_down\', \''.self::$current_loop.'\');">';

		echo '</div>';	
		
		// max
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_max'].': ';
		echo '<input type="text" id="chart_max_'.$spid.'_loop_'.self::$current_loop.'" name="chart_max" value="'.$settings['max'].'" onBlur="changeChart(\''.$spid.'\', 8, \'max\', \''.self::$current_loop.'\');" onkeypress="checkNumber(this, 6, 0);" onkeyup="checkNumber(this, 6, 0);" />';
		
		echo '</div>';
		
		// polodprt interval navgor
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_open_up'].': ';
		echo '<input type="checkbox" id="chart_open_up_'.$spid.'_loop_'.self::$current_loop.'" name="chart_open_up" '.($settings['open_up']=='1'?' checked="checked"':'').' '.($settings['max']==''?' disabled="disabled"':'').' onchange="changeChart(\''.$spid.'\', 8, \'open_up\', \''.self::$current_loop.'\');">';

		echo '</div>';	

		echo '</fieldset>';*/
	}
	
	// Nastavitve za datum grafe (tip 8)
	static function displayAdvancedDateSettings($spid, $settings){
		global $site_path;
		global $lang;
				
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 8, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 8, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
	}
	
	// Nastavitve za multigrid grafe (tip 6)
	static function displayMultigridSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 6, \'type\', \''.self::$current_loop.'\');">';
		
		// Pri nominalnih ne prikazujemo povprecij
		if($spremenljivka['skala'] != 1 && $spremenljivka['cnt_all'] != 1){
			echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_avg_hor'].'</option>';
			echo '  <option value="5" '.($settings['type']=='5'?' selected="selected"':'').'>'.$lang['srv_chart_avg_radar'].'</option>';
			echo '  <option value="6" '.($settings['type']=='6'?' selected="selected"':'').'>'.$lang['srv_chart_avg_line'].'</option>';
		}
		// Pri nominalnih pokazemo posebej radar
		if($spremenljivka['skala'] == 1){
			echo '  <option value="7" '.($settings['type']=='7'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
		}
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_structure_ver'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_structure_hor'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
				
		echo '</select>';
		echo '</div>';
		
		// Obrnjeni gridi in variable
		if(($settings['type'] > 0 && $settings['type'] < 5) || $settings['type'] == 7){
			echo '<div class="chart_setting">';	
			if($settings['rotate']=='1'){
				echo $lang['srv_chart_rotate_grids'].' ';
				//echo '<input type="checkbox" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" '.($settings['rotate']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');">';
				echo '<span onclick="changeChart(\''.$spid.'\', 6, \'rotate\', \''.self::$current_loop.'\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
				echo '<input type="hidden" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" value="0">';
				echo ' '.$lang['srv_chart_rotate_vars'].' ';
			}
			else{
				echo $lang['srv_chart_rotate_vars'].' ';
				//echo '<input type="checkbox" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" '.($settings['rotate']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');">';
				echo '<span onclick="changeChart(\''.$spid.'\', 6, \'rotate\', \''.self::$current_loop.'\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
				echo '<input type="hidden" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" value="1">';
				echo ' '.$lang['srv_chart_rotate_grids'];
			}
			echo '</div>';
		}
		
		// sortiranje - pri povprecjih sortiramo po velikosti (brez, narascajoce, padajoce)
		if($settings['type'] == 0 || $settings['type'] == 5 || $settings['type'] == 6){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 6, \'sort\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
			echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
			
			echo '</select>';

			echo '</div>';
		}
		// sortiranje - pri navadnih grafih za gride (ne povprecja) sortiramo po povprecju, 1. kategoriji ali kategorijah
		else{
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 6, \'sort\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_category'].'</option>';
			if($settings['rotate']=='0')
				echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_avg'].'</option>';
			echo '  <option value="3" '.($settings['sort']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_sort_first'].'</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
						
		// tip izpisa vrednosti
		if(($settings['type'] > 0 && $settings['type'] < 5) || $settings['type'] == 7){
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 6, \'value_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';	
			echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
			echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';			
			
			echo '</select>';
			echo '</div>';
		}

		// Tip radarja		
		if($settings['type'] == 5 || $settings['type'] == 7){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="chart_radar_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_type" onchange="changeChart(\''.$spid.'\', 6, \'radar_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == 5 || $settings['type'] == 7){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="chart_radar_scale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_scale" onchange="changeChart(\''.$spid.'\', 6, \'radar_scale\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// prikaz desne skale pri sem. diferencialu (ver. linijski graf ali hor. strukturni stolpci)
		if($spremenljivka['enota'] == 1 && ($settings['type'] == 2 || $settings['type'] == 6)){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_right_scale'].': ';
			echo '<input type="checkbox" id="chart_scale_limit_'.$spid.'_loop_'.self::$current_loop.'" name="chart_scale_limit" '.($settings['scale_limit']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 6, \'scale_limit\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz legende - opcija samo pri povprecjih (drugje je vedno vklopljena)
		if($settings['type'] == 0 || $settings['type'] == 5 || $settings['type'] == 6){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_analiza_legenda'].': ';
			echo '<input type="checkbox" id="chart_show_legend_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_legend" '.($settings['show_legend']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 6, \'show_legend\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz label v stolpcih
		if($settings['type'] == 0 || $settings['type'] == 1 || $settings['type'] == 2){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 6, \'barLabel\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
	}
	
	// Nastavitve za multigrid grafe (tip 6)
	static function displayAdvancedMultigridSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
					
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 6, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 2 || $settings['type'] == 4){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 6, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// Izpusti variable brez odgovora
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_hideEmtyVar'].': ';
		echo '<input type="checkbox" id="chart_hideEmptyVar_'.$spid.'_loop_'.self::$current_loop.'" name="chart_hideEmptyVar" '.($settings['hideEmptyVar']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 6, \'hideEmptyVar\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// prikaz label majhnih vrednosti zraven stolpcov
		if($settings['barLabel'] == 1 && $settings['type'] == 0){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabelSmall'].': ';
			echo '<input type="checkbox" id="chart_barLabelSmall_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabelSmall" '.($settings['barLabelSmall']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 6, \'barLabelSmall\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// zacni skalo z 0 (samo pri povprecju)
		if($settings['type'] == 0 || $settings['type'] == 5 || $settings['type'] == 6){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_noFixedScale'].': ';
			echo '<input type="checkbox" id="chart_noFixedScale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_noFixedScale" '.($settings['noFixedScale']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 6, \'noFixedScale\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// Preklop med ordinalno in nominalno spremenljivko	
		echo '<div class="chart_setting">';
		
		$lestvica = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skalaAsValue');
			
		echo $lang['srv_skala'].': ';
		// Vprasajcek za pomoc
		echo Help :: display('srv_skala_edit');
		
		echo '<span class="spaceLeft"></span>';
		echo '<a onclick="chartAdvancedSettingsSkala(\''.$spid.'\', \'0\', \''.self::$current_loop.'\'); return false;" href="#" title="'.$lang['srv_skala_long_0'].'"><span '.($lestvica == 0 ? ' class="strong"' : '').'>'.$lang['srv_skala_short_0'].'</span></a>';
		echo '<span class="blue"> / </span>';
		echo '<a onclick="chartAdvancedSettingsSkala(\''.$spid.'\', \'1\', \''.self::$current_loop.'\'); return false;" href="#" title="'.$lang['srv_skala_long_1'].'"><span '.($lestvica == 1 ? ' class="strong"' : '').'>'.$lang['srv_skala_short_1'].'</span></a>';

		echo '</div>';
	}
	
	// Nastavitve za dvojne multigrid grafe (tip 6, enota 3)
	static function displayDoubleMultigridSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 62, \'type\', \''.self::$current_loop.'\');">';
		
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_line_hor'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_line_ver'].'</option>';
		echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
		
		
		echo '</select>';
		echo '</div>';
		
		// Tip radarja		
		if($settings['type'] == '4'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="chart_radar_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_type" onchange="changeChart(\''.$spid.'\', 62, \'radar_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == '4'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="chart_radar_scale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_scale" onchange="changeChart(\''.$spid.'\', 62, \'radar_scale\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// prikaz label v stolpcih
		if($settings['type'] == 0 || $settings['type'] == 1){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 62, \'barLabel\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
	}
	
	// Nastavitve za dvojne multigrid grafe (tip 6, enota 3)
	static function displayAdvancedDoubleMultigridSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
			
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 62, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// sirina label
		if($settings['type'] == 0){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 62, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// zacni skalo z 0
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_noFixedScale'].': ';
		echo '<input type="checkbox" id="chart_noFixedScale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_noFixedScale" '.($settings['noFixedScale']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 62, \'noFixedScale\', \''.self::$current_loop.'\');">';

		echo '</div>';
	}
	
	// Nastavitve za multicheckbox grafe (tip 16)
	static function displayMulticheckboxSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// omejitev skale
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_base'].': <select id="chart_base_'.$spid.'_loop_'.self::$current_loop.'" name="chart_base" onchange="changeChart(\''.$spid.'\', 16, \'base\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['base']=='0'?' selected="selected"':'').'>'.$lang['srv_analiza_opisne_units'].'</option>';
		echo '  <option value="1" '.($settings['base']=='1'?' selected="selected"':'').'>'.$lang['srv_analiza_opisne_arguments'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 16, \'type\', \''.self::$current_loop.'\');">';
			
		if($settings['base'] == '1'){
			echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_structure_ver'].'</option>';
			echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_structure_hor'].'</option>';
		}
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		if($settings['base'] == '0'){
			echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
		}
		
		echo '</select>';
		echo '</div>';
		
		// sortiranje
		echo '<div class="chart_setting">';

		echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 16, \'sort\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
		echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_category'].'</option>';
		echo '  <option value="3" '.($settings['sort']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_sort_first'].'</option>';
		
		echo '</select>';

		echo '</div>';
		
		// Obrnjeni gridi in variable	
		echo '<div class="chart_setting">';
		
		if($settings['rotate']=='1'){
			echo $lang['srv_chart_rotate_grids'].' ';
			//echo '<input type="checkbox" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" '.($settings['rotate']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');">';
			echo '<span onclick="changeChart(\''.$spid.'\', 16, \'rotate\', \''.self::$current_loop.'\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
			echo '<input type="hidden" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" value="0">';
			echo ' '.$lang['srv_chart_rotate_vars'].' ';
		}
		else{
			echo $lang['srv_chart_rotate_vars'].' ';
			//echo '<input type="checkbox" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" '.($settings['rotate']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');">';
			echo '<span onclick="changeChart(\''.$spid.'\', 16, \'rotate\', \''.self::$current_loop.'\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
			echo '<input type="hidden" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" value="1">';
			echo ' '.$lang['srv_chart_rotate_grids'];
		}
		echo '</div>';
		
		// Tip radarja		
		if($settings['type'] == '4'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="chart_radar_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_type" onchange="changeChart(\''.$spid.'\', 16, \'radar_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == '4'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="chart_radar_scale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_scale" onchange="changeChart(\''.$spid.'\', 16, \'radar_scale\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// tip izpisa vrednosti
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 16, \'value_type\', \''.self::$current_loop.'\');">';
		
		echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';	
		echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
		if($settings['base'] == '0')
			echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';
		
		echo '</select>';
		echo '</div>';	
		
		// prikaz label v stolpcih
		if($settings['type'] == 2 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 16, \'barLabel\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}	
	}
	
	// Nastavitve za multicheckbox grafe (tip 16)
	static function displayAdvancedMulticheckboxSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 16, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 16, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// Izpusti variable brez odgovora
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_hideEmtyVar'].': ';
		echo '<input type="checkbox" id="chart_hideEmptyVar_'.$spid.'_loop_'.self::$current_loop.'" name="chart_hideEmptyVar" '.($settings['hideEmptyVar']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 16, \'hideEmptyVar\', \''.self::$current_loop.'\');">';

		echo '</div>';
	}
	
	// Nastavitve za vsoto (tip 18)
	static function displayVsotaSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 18, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_pie'].'</option>';
		echo '  <option value="5" '.($settings['type']=='5'?' selected="selected"':'').'>'.$lang['srv_chart_3Dpie'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_line'].'</option>';
		echo '  <option value="4" '.($settings['type']=='4'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		
		echo '</select>';
		echo '</div>';
			
		// Tip radarja		
		if($settings['type'] == '4'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="chart_radar_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_type" onchange="changeChart(\''.$spid.'\', 18, \'radar_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == '4'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="chart_radar_scale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_scale" onchange="changeChart(\''.$spid.'\', 18, \'radar_scale\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}		
		
		// sortiranje
		if($settings['type'] != '4'){
			echo '<div class="chart_setting">';

			echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 18, \'sort\', \''.self::$current_loop.'\');">';
				
			echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
			echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
			echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
				
		// prikaz legende
		if($settings['type'] == 0 || $settings['type'] == 5){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_analiza_legenda'].': ';
			echo '<input type="checkbox" id="chart_show_legend_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_legend" '.($settings['show_legend']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 18, \'show_legend\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// prikaz label v stolpcih
		if($settings['type'] == 2 || $settings['type'] == 3){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabel'].': ';
			echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 18, \'barLabel\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
	}
	
	// Nastavitve za vsoto (tip 18)
	static function displayAdvancedVsotaSettings($spid, $settings){
		global $site_path;
		global $lang;
				
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 18, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
					
		// sirina label
		if($settings['type'] == 2){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 18, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// prikaz label majhnih vrednosti zraven stolpcov
		if($settings['barLabel'] == 1 && ($settings['type'] == 2 || $settings['type'] == 3)){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabelSmall'].': ';
			echo '<input type="checkbox" id="chart_barLabelSmall_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabelSmall" '.($settings['barLabelSmall']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 18, \'barLabelSmall\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// 3D strukturni krog
		/*if($settings['type'] == 0){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_3d_pie'].': ';
			echo '<input type="checkbox" id="chart_3d_pie_'.$spid.'_loop_'.self::$current_loop.'" name="chart_3d_pie" '.($settings['3d_pie']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 18, \'3d_pie\', \''.self::$current_loop.'\');">';

			echo '</div>';	
		}*/
	}
	
	// Nastavitve za ranking grafe (tip 17)
	static function displayRankingSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 17, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_avg'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_structure_hor'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_structure_ver'].'</option>';
		//echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_structure'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// sortiranje
		echo '<div class="chart_setting">';

		echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 17, \'sort\', \''.self::$current_loop.'\');">';
				
		echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
		echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_desc'].'</option>';
		echo '  <option value="2" '.($settings['sort']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_sort_asc'].'</option>';
		
		echo '</select>';

		echo '</div>';
		
		// tip izpisa vrednosti
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_valtype'].': <select id="chart_value_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_value_type" onchange="changeChart(\''.$spid.'\', 17, \'value_type\', \''.self::$current_loop.'\');" '.($settings['type'] == 0 ? 'disabled="disabled"' : '').'>';
			
		echo '  <option value="0" '.($settings['value_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_freq'].'</option>';
		echo '  <option value="1" '.($settings['value_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_percent'].'</option>';
		//echo '  <option value="2" '.($settings['value_type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_valid'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// prikaz label v stolpcih
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_barLabel'].': ';
		echo '<input type="checkbox" id="chart_barLabel_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabel" '.($settings['barLabel']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 17, \'barLabel\', \''.self::$current_loop.'\');">';

		echo '</div>';
	}
	
	// Nastavitve za ranking grafe (tip 17)
	static function displayAdvancedRankingSettings($spid, $settings){
		global $site_path;
		global $lang;
				
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 17, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// sirina label
		if($settings['type'] == 0 || $settings['type'] == 1){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 17, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
		
		// prikaz label majhnih vrednosti zraven stolpcov
		if($settings['barLabel'] == 1 && $settings['type'] == 0){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_barLabelSmall'].': ';
			echo '<input type="checkbox" id="chart_barLabelSmall_'.$spid.'_loop_'.self::$current_loop.'" name="chart_barLabelSmall" '.($settings['barLabelSmall']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 17, \'barLabelSmall\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
		
		// zacni skalo z 0 (samo pri povprecju)
		if($settings['type'] == 0){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_chart_noFixedScale'].': ';
			echo '<input type="checkbox" id="chart_noFixedScale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_noFixedScale" '.($settings['noFixedScale']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 17, \'noFixedScale\', \''.self::$current_loop.'\');">';

			echo '</div>';
		}
	}
	
	// Nastavitve za multinumber (tip 20)
	static function displayMultinumberSettings($spid, $settings){
		global $site_path;
		global $lang;
		
		// Tip grafa
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 20, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar'].'</option>';
		echo '  <option value="1" '.($settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_vertical'].'</option>';
		echo '  <option value="2" '.($settings['type']=='2'?' selected="selected"':'').'>'.$lang['srv_chart_horizontal'].'</option>';
		echo '  <option value="3" '.($settings['type']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_line'].'</option>';
		
		echo '</select>';
		echo '</div>';
		
		// sortiranje
		echo '<div class="chart_setting">';

		echo $lang['srv_chart_sort'].': <select id="chart_sort_'.$spid.'_loop_'.self::$current_loop.'" name="chart_sort" onchange="changeChart(\''.$spid.'\', 20, \'sort\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.($settings['sort']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_sort_no'].'</option>';
		echo '  <option value="1" '.($settings['sort']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_sort_category'].'</option>';
		echo '  <option value="3" '.($settings['sort']=='3'?' selected="selected"':'').'>'.$lang['srv_chart_sort_first'].'</option>';
		
		echo '</select>';

		echo '</div>';
		
		// Obrnjeni gridi in variable	
		echo '<div class="chart_setting">';
		
		if($settings['rotate']=='1'){
			echo $lang['srv_chart_rotate_grids'].' ';
			//echo '<input type="checkbox" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" '.($settings['rotate']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');">';
			echo '<span onclick="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
			echo '<input type="hidden" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" value="0">';
			echo ' '.$lang['srv_chart_rotate_vars'].' ';
		}
		else{
			echo $lang['srv_chart_rotate_vars'].' ';
			//echo '<input type="checkbox" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" '.($settings['rotate']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');">';
			echo '<span onclick="changeChart(\''.$spid.'\', 20, \'rotate\', \''.self::$current_loop.'\');" style="cursor: pointer;"><img src="img_0/random_off.png" title="Obrni grafe/variable" /></span>';
			echo '<input type="hidden" id="chart_rotate_'.$spid.'_loop_'.self::$current_loop.'" name="chart_rotate" value="1">';
			echo ' '.$lang['srv_chart_rotate_grids'];
		}
		echo '</div>';
		
		// Tip radarja		
		if($settings['type'] == '0'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_type'].': <select id="chart_radar_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_type" onchange="changeChart(\''.$spid.'\', 20, \'radar_type\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type0'].'</option>';
			echo '  <option value="1" '.($settings['radar_type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_type1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
		
		// Postavitev skale pri radarju
		if($settings['type'] == '0'){		
			echo '<div class="chart_setting">';
			echo $lang['srv_chart_radar_scale'].': <select id="chart_radar_scale_'.$spid.'_loop_'.self::$current_loop.'" name="chart_radar_scale" onchange="changeChart(\''.$spid.'\', 20, \'radar_scale\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="0" '.($settings['radar_scale']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale0'].'</option>';
			echo '  <option value="1" '.($settings['radar_scale']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_radar_scale1'].'</option>';
			
			echo '</select>';
			echo '</div>';
		}
	}
	
	// Nastavitve za multinumber (tip 20)
	static function displayAdvancedMultinumberSettings($spid, $settings){
		global $site_path;
		global $lang;
					
		// prikaz numerusa
		echo '<div class="chart_setting">';
		
		$checked = ($settings['show_numerus']=='1' || ($settings['show_numerus']=='-1' && SurveyDataSettingProfiles :: getSetting('chartNumerusText') == 1)) ? ' checked="checked"': '';

		echo $lang['srv_chart_showNumerus'].': ';
		echo '<input type="checkbox" id="chart_show_numerus_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_numerus" '.$checked.' onchange="changeChart(\''.$spid.'\', 20, \'show_numerus\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		// sirina label
		if($settings['type'] == 2){
			echo '<div class="chart_setting">';
			
			echo $lang['srv_wide_chart'].': <select id="chart_labelWidth_'.$spid.'_loop_'.self::$current_loop.'" name="chart_labelWidth" onchange="changeChart(\''.$spid.'\', 20, \'labelWidth\', \''.self::$current_loop.'\');">';
			
			echo '  <option value="75" '.($settings['labelWidth']=='75'?' selected="selected"':'').'>75%</option>';
			echo '  <option value="50" '.($settings['labelWidth']=='50'?' selected="selected"':'').'>50%</option>';
			echo '  <option value="20" '.($settings['labelWidth']=='20'?' selected="selected"':'').'>20%</option>';
			
			echo '</select>';
			
			echo '</div>';
		}
	}
	
	// Nastavitve za vse tabele
	static function displayTableSettings($spid){
		global $site_path;
		global $lang;
		
		// Tip tabele
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_table_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 21, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.(self::$settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_table_type_0'].'</option>';
		echo '  <option value="1" '.(self::$settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_table_type_1'].'</option>';
		
		echo '</select>';
		echo '</div>';	
		
		//Poravnava texta
		echo '<div class="chart_setting">';
			
		echo $lang['srv_chart_table_align'].': ';
		echo '<input type="checkbox" id="chart_show_legend_'.$spid.'_loop_'.self::$current_loop.'" name="chart_show_legend" '.(self::$settings['show_legend']=='1'?' checked="checked"':'').' onchange="changeChart(\''.$spid.'\', 21, \'show_legend\', \''.self::$current_loop.'\');">';

		echo '</div>';
	}
	
	// Nastavitve za vse multitext tabele
	static function displayMultitextSettings($spid){
		global $site_path;
		global $lang;
		
		// Tip tabele
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_table_type'].':<br /> <select style="width:140px;" id="chart_type_'.$spid.'_loop_'.self::$current_loop.'" name="chart_type" onchange="changeChart(\''.$spid.'\', 19, \'type\', \''.self::$current_loop.'\');">';
			
		echo '  <option value="0" '.(self::$settings['type']=='0'?' selected="selected"':'').'>'.$lang['srv_chart_table_type_0'].'</option>';
		echo '  <option value="1" '.(self::$settings['type']=='1'?' selected="selected"':'').'>'.$lang['srv_chart_table_type_1'].'</option>';
		
		echo '</select>';
		echo '</div>';	
	}
	
	// Nastavitve za other tabele
	static function displayOtherSettings($spid){
		global $site_path;
		global $lang;
		
		echo '<div class="chart_settings_inner">';
		echo '<span class="title">'.$lang['srv_chart_settings'].'</span>';
				
		//Poravnava texta
		echo '<div class="chart_setting">';
			
		echo $lang['srv_chart_table_align'].': ';
		echo '<input type="checkbox" id="chart_other_otherType_'.$spid.'_loop_'.self::$current_loop.'" name="chart_other_otherType" '.(self::$settings['otherType']=='1'?' checked="checked"':'').' onchange="changeOther(\''.$spid.'\', \'otherType\', \''.self::$current_loop.'\');">';

		echo '</div>';		
		
		//Prikaz frekvenc
		echo '<div class="chart_setting">';
			
		echo $lang['srv_chart_table_freq'].': ';
		echo '<input type="checkbox" id="chart_other_otherFreq_'.$spid.'_loop_'.self::$current_loop.'" name="chart_other_otherFreq" '.(self::$settings['otherFreq']=='1'?' checked="checked"':'').' onchange="changeOther(\''.$spid.'\', \'otherFreq\', \''.self::$current_loop.'\');">';

		echo '</div>';
		
		echo '</div>';
	}
	
	
	// Napredne nastavitve za posamezen graf (popup)
	static function displayAdvancedSettings($spid){
		global $site_path;
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

        echo '<h2>'.$spremenljivka['variable'].' - '.$spremenljivka['naslov'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="chartCloseAdvancedSettings(); return false;">✕</a></div>';
		
		echo '<form method="post" name="chart_advanced_settings" onsubmit="chartSaveAdvancedSettings(\''.$spid.'\', \''.self::$current_loop.'\'); return false;">';
		
		echo '<input type="hidden" name="anketa" value="'.self::$anketa.'" />';
		echo '<input type="hidden" name="spid" value="'.$spid.'" />';
		echo '<input type="hidden" name="loop" value="'.self::$current_loop.'" />';
		echo '<input type="hidden" name="spr_type" value="'.$spremenljivka['tip'].'" />';
				
		// urejanje label
		echo '<div id="chartSettingsArea1" class="chartSettingsArea">';
		self::displayAdvancedSettingsLabels($spid);	
		echo '</div>';
		
		// urejanje barv
		echo '<div id="chartSettingsArea2" class="chartSettingsArea" style="visibility: hidden;">';
		self::displayAdvancedSettingsColors($spid);
		echo '</div>';	
			
		// urejanje mej pri numericih
		if(($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)) || $spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8 || $spremenljivka['tip'] == 22){
			echo '<div id="chartSettingsArea4" class="chartSettingsArea" style="visibility: hidden;">';
			self::displayAdvancedSettingsLimits($spid, self::$settings['limits']['advanced_settings']);
			echo '</div>';	
		}
		
		/* REKODIRANJE */
		echo '<div id="chartSettingsArea3" class="chartSettingsArea" style="visibility: hidden;">';
		$spr_id=explode('_',$spid);
	
		$vmv = new RecodeValues(self::$anketa,$spr_id[0]);
		$vmv->DisplayMissingValuesForQuestion(false);
		echo '</div>';
		
		echo '</form>';
	
		
		/* ZAVIHKI NA DESNI */
		echo '<div id="chartTabs" class="chartSettingsTabs">';
		
		echo '<ul>';	
		echo '<li id="chartTab1" class="chartTab active" onClick="chartTabAdvancedSettings(\'1\');">';
		echo  $lang['srv_chart_advanced_labels'];
		echo '</li>';
		echo '<li id="chartTab2" class="chartTab" onClick="chartTabAdvancedSettings(\'2\');">';
		echo  $lang['srv_chart_advanced_colors'];
		echo '</li>';
		echo '<li id="chartTab3" class="chartTab" onClick="chartTabAdvancedSettings(\'3\');">';
		echo  $lang['srv_chart_advanced_recoding'];
        echo '</li>';
		// Tab za meje numericov
		if(($spremenljivka['tip'] == 3 && self::checkDropdownNumeric($spid)) || $spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 8 || $spremenljivka['tip'] == 22){
			echo '<li id="chartTab4" class="chartTab" onClick="chartTabAdvancedSettings(\'4\');">';
			echo  $lang['srv_chart_advanced_limits'];
			echo '</li>';
		}
		echo '</ul>';	
		
		echo '</div>';		
		
		
		/* GUMBI NA DNU */
		echo '<div id="chartSettingsButtons" class="buttons_holder">';
		
		echo '<span class="buttonwrapper spaceRight floatLeft">';
		echo '<a class="ovalbutton ovalbutton_gray" onclick="chartCloseAdvancedSettings(); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
		echo '</span>';	
		
		echo '<span class="buttonwrapper floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" onclick="chartSaveAdvancedSettings(\''.$spid.'\', \''.self::$current_loop.'\'); return false;">'.$lang['srv_potrdi'].'</a>';
        echo '</span>';		
	
		echo '</div>';		
	}
	
	// Urejanje barv posameznega grafa
	static function displayAdvancedSettingsColors($spid){
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
		
		$default_colors = self::getDefaultColors(self::$skin);
		
		for($i=0; $i<7; $i++){
			$name = 'color'.($i+1);
			$value = (self::$settings['colors'][$i] != '') ? self::$settings['colors'][$i] : $default_colors[$i];
			
			echo '  <div class="form-item"><label for="'.$name.'">'.$lang['srv_color'].' '.($i+1).': </label><input type="text" id="'.$name.'" name="'.$name.'" class="colorwell" value="'.$value.'" /></div>';
		}
		
		// reset na default barvo
		echo '<br /><span class="as_link clr" onClick="chartAdvancedSettingsSetColor(\''.(is_numeric(self::$skin) ? implode("_",$default_colors) : self::$skin).'\')">'.$lang['srv_chart_advanced_default_color'].'</span>';
		
		// nastavitev ene od palet
		echo '<br /><span class="clr">'.$lang['srv_chart_advanced_skin'].': ';
		echo '<select name="chart_advanced_color" id="chart_advanced_color" onChange="chartAdvancedSettingsSetColor(this.value)">';
		echo '	<option' . (self::$skin == '1ka' ? ' selected="selected"' : '') . ' value="1ka">'.$lang['srv_chart_skin_1ka'].'</option>';
		echo '	<option' . (self::$skin == 'lively' ? ' selected="selected"' : '') . ' value="lively">'.$lang['srv_chart_skin_0'].'</option>';
		echo '	<option' . (self::$skin == 'mild' ? ' selected="selected"' : '') . ' value="mild">'.$lang['srv_chart_skin_1'].'</option>';
		echo '	<option' . (self::$skin == 'office' ? ' selected="selected"' : '') . ' value="office">'.$lang['srv_chart_skin_6'].'</option>';
		echo '	<option' . (self::$skin == 'pastel' ? ' selected="selected"' : '') . ' value="pastel">'.$lang['srv_chart_skin_7'].'</option>';
		echo '	<option' . (self::$skin == 'green' ? ' selected="selected"' : '') . ' value="green">'.$lang['srv_chart_skin_2'].'</option>';
		echo '	<option' . (self::$skin == 'blue' ? ' selected="selected"' : '') . ' value="blue">'.$lang['srv_chart_skin_3'].'</option>';
		echo '	<option' . (self::$skin == 'red' ? ' selected="selected"' : '') . ' value="red">'.$lang['srv_chart_skin_4'].'</option>';
		echo '	<option' . (self::$skin == 'multi' ? ' selected="selected"' : '') . ' value="multi">'.$lang['srv_chart_skin_5'].'</option>';
		
		$customSkins = self::getCustomSkins();
		foreach($customSkins as $customSkin){					
			echo '	<option' . (self::$skin == $customSkin['id'] ? ' selected="selected"' : '') . ' value="'.$customSkin['colors'].'">'.$customSkin['name'].'</option>';
		}
		
		
		echo '</select></span>';
		
	}
	
	// Urejanje label posameznega grafa
	static function displayAdvancedSettingsLabels($spid){
		global $site_path;
		global $lang;
		
		$row = Cache::srv_spremenljivka($spid);
		$disabled = ($row['edit_graf'] == 0) ? ' disabled="disabled"' : '';
				
		
		echo '<div><p>';
		echo $lang['srv_chart_advanced_useLabels'].':';		
		echo '<label for="edit_graf_0"><input type="radio" value="0" name="edit_graf" id="edit_graf_0" '.(($row['edit_graf'] == 0) ? ' checked="checked" ' : '').' onClick="edit_labels(\'0\');" />';
		echo $lang['no'].'</label>';
		echo ' <label for="edit_graf_1"><input type="radio" value="1" name="edit_graf" id="edit_graf_1" '.(($row['edit_graf'] == 1) ? ' checked="checked" ' : '').' onClick="edit_labels(\'1\');" />';	
		echo $lang['yes'].'</label>';
		echo '</p></div>';
		
		
		echo '<div class="chart_editing">';		
		
		// Urejanje naslova spremenljivke
		$text = $row['naslov_graf'] == '<p></p>' ? $row['naslov'] : $row['naslov_graf'];
		if (strtolower(substr($text, 0, 3)) == '<p>' && strtolower(substr($text, -4)) == '</p>' && strrpos($text, '<p>') == 0) {
			$text = substr($text, 3);
			$text = substr($text, 0, -4);
		}		
		echo '<p>';
		echo '<textarea style="width:99%; height:50px;" name="naslov_graf" id="naslov_graf" class="chart_label" '.$disabled.'>'.$text.'</textarea>';
		echo '</p>';		
			

		// Urejanje label za gride
		if($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20){
			
			echo '<div class="grid_settings">';
			echo '<input type="hidden" name="edit_grid_graf" value="1" />';

			echo '<table id="grids" style="width:100%">';
			
			echo '<tr>';
			for ($i=1; $i<=$row['grids']; $i++) {
				echo '<td>'.$i.'</td>';
			}
			
			//dodatne vrednosti (ne vem, zavrnil...)
			if (count($already_set_mv) > 0 ) {
				echo '<td></td>';
				if (count($missing_values) > 0) {
					foreach ($missing_values AS $mv_key => $mv_text) {
						if (isset($already_set_mv[$mv_key])) {
							echo '<td>'.$mv_key.'</td>';
						}
					}
				}
			}
			echo '</tr>';
			
			echo '<tr>';
			for ($i=1; $i<=$row['grids']; $i++) {
				$sql1 = sisplet_query("SELECT naslov, naslov_graf FROM srv_grid WHERE id='$i' AND spr_id='$spid'");
				$row1 = mysqli_fetch_array($sql1);
				$text = $row1['naslov_graf'] == '' ? $row1['naslov'] : $row1['naslov_graf'];
				echo '<td><input type="text" maxlength="30" name="grid_graf_'.$i.'" id="grid_naslov_'.$i.'_graf" class="chart_label" value="'.$text.'" '.$disabled.' /></td>';
			}
			
			//dodatne vrednosti (ne vem, zavrnil...)
			if (count($already_set_mv) > 0 ) {
				echo '<td></td>';
				if (count($missing_values) > 0) {
					foreach ($missing_values AS $mv_key => $mv_text) {
						if (isset($already_set_mv[$mv_key])) {
							echo '<td><input type="text" maxlength="30" name="grid_'.$mv_key.'_graf" class="chart_label" value="'.$already_set_mv[$mv_key].'" '.$disabled.' /></td>';
						}
					}
				}
			}
			echo '</tr>';
			
			echo '</table>';
			echo '</div>';
		}
			
			
		// Urejanje naslovov variabel
		$sql1 = sisplet_query("SELECT id, variable, naslov, REPLACE(REPLACE(REPLACE(naslov_graf,'\n',' '),'\r',' '),'|',' ') as naslov_graf, other FROM srv_vrednost WHERE spr_id = '$spid' ORDER BY vrstni_red ASC");
		if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
		
		echo '<input type="hidden" name="edit_vrednost_graf" value="1" />';
		
		echo '<div id="vrednosti_holder"><ul class="vrednost_sort">';
		while ($row1 = mysqli_fetch_array($sql1)) {
			
			$text = $row1['naslov_graf'] == '' ? $row1['naslov'] : $row1['naslov_graf'];
			
			echo '<li id="vrednost_'.$vrednost.'" '.($row1['other'] == 1 ? 'class="li_other"' : '').'>';
					
			echo '<textarea maxlength="30" name="vrednost_graf_'.$row1['id'].'" id="'.$row1['variable'].'_graf" class="vrednost_textarea chart_label" style="width:60%; height:15px;" '.$disabled.'>'.$text.'</textarea> ';
			echo '['.$row1['variable'].']</span>';
			if ($row1['other'] == 1) echo ' <input type="text" disabled style="width:40px" />';

			echo '</li>';			
		}		
		echo '</ul></div>';
		
		echo '<span class="red" style="font-size:11px;">'.$lang['srv_chart_advanced_labelsWarning'].'</span>';
				
		echo '</div>';
	}
	
	// Urejanje mej za numericne tipe (radio dropdown number, number, date)
	static function displayAdvancedSettingsLimits($spid, $mode=0){
		global $site_path;
		global $lang;
		
		$spremenljivka = Cache::srv_spremenljivka($spid);
		$limits = self::$settings['limits'];
					
		
		// preklop med navadnimi mejami (zgornja/spodnja) in naprednimi (custom za vsak interval)
		echo '<div class="chart_setting">';
		echo '<span class="bold">'.$lang['srv_chart_num_limit_basic'].'<input type="radio" name="chart_number_limits_switch" value="0" '.($mode=='0'?' checked="checked"':'').' onClick="chartAdvancedSettingsLimitSwitch(\'0\');" /></span>';
		echo '<span class="spaceLeft bold">'.$lang['srv_chart_num_limit_advanced'].'<input type="radio" name="chart_number_limits_switch" value="1" '.($mode=='1'?' checked="checked"':'').' onClick="chartAdvancedSettingsLimitSwitch(\'1\');" /></span>';
		echo '</div>';
		
		
		// OSNOVNE NASTAVITVE MEJ
		echo '<div id="chart_number_limits_basic" '.($mode=='1'?' style="display:none;"':'').'>';
		
		// stevilo intervalov
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_interval'].': <select id="chart_interval_'.$spid.'_loop_'.self::$current_loop.'" name="chart_interval">';
			
		for($i=3; $i<=10; $i++){				
			echo '  <option value="'.$i.'" '.(self::$settings['interval']==$i ?' selected="selected"':'').'>'.$i.'</option>';
		}
		echo '  <option value="20" '.(self::$settings['interval']=='20'?' selected="selected"':'').'>20</option>';
		echo '  <option value="-1" '.(self::$settings['interval']=='-1'?' selected="selected"':'').'>Vsi</option>';
		
		echo '</select>';
		echo '</div>';
		
		// Naslov "zgornja in spodnja meja"
		echo '<div class="chart_setting">';
		echo '<span class="bold">'.$lang['srv_chart_num_limit'].':</span>';	
		echo '</div>';
		
		// min in polodprtost navzdol
		echo '<div class="chart_setting">';
		
		echo $lang['srv_chart_min'].': ';
		echo '<input type="text" id="chart_min_'.$spid.'_loop_'.self::$current_loop.'" name="chart_min" value="'.self::$settings['min'].'" onkeyup="checkNumber(this, 6, 2);" onkeypress="checkNumber(this, 6, 2);" />';
		
		echo '<span style="padding-left:20px;">'.$lang['srv_chart_open_down'].': </span>';
		echo '<input type="checkbox" id="chart_basic_open_down_'.$spid.'_loop_'.self::$current_loop.'" name="chart_basic_open_down" value="1" '.(self::$settings['open_down']=='1'?' checked="checked"':'').' />';
		
		echo '</div>';
			
		// max in polodprtost navzgor
		echo '<div class="chart_setting">';	
		
		echo $lang['srv_chart_max'].': ';
		echo '<input type="text" id="chart_max_'.$spid.'_loop_'.self::$current_loop.'" name="chart_max" value="'.self::$settings['max'].'" onkeyup="checkNumber(this, 6, 2);" onkeypress="checkNumber(this, 6, 2);" />';		
		
		echo '<span style="padding-left:20px;">'.$lang['srv_chart_open_up'].': </span>';
		echo '<input type="checkbox" id="chart_basic_open_up_'.$spid.'_loop_'.self::$current_loop.'" name="chart_basic_open_up" value="1" '.(self::$settings['open_up']=='1'?' checked="checked"':'').' />';
		
		echo '</div>';
		echo '</div>';
		
		
		// NAPREDNE NASTAVITVE MEJ
		echo '<div id="chart_number_limits_advanced" '.($mode=='0'?' style="display:none;"':'').'>';

		// stevilo intervalov
		echo '<div class="chart_setting">';
		echo $lang['srv_chart_interval'].': <select id="chart_interval_'.$spid.'_loop_'.self::$current_loop.'" name="chart_interval" onChange="chartAdvancedSettingsLimitInterval(this.value, \''.$spid.'\', \''.self::$current_loop.'\');">';
			
		for($i=3; $i<=10; $i++){				
			echo '  <option value="'.$i.'" '.(self::$settings['interval']==$i ?' selected="selected"':'').'>'.$i.'</option>';
		}
		echo '  <option value="20" '.(self::$settings['interval']=='20'?' selected="selected"':'').'>20</option>';
		echo '  <option value="-1" '.(self::$settings['interval']=='-1'?' selected="selected"':'').'>Vsi</option>';
		
		echo '</select>';
		echo '</div>';
		
		// Polodprtost navzdol
		echo '<span style="padding-left:20px;">'.$lang['srv_chart_open_down'].': </span>';
		echo '<input type="checkbox" id="chart_advanced_open_down_'.$spid.'_loop_'.self::$current_loop.'" name="chart_advanced_open_down" value="1" '.(self::$settings['open_down']=='1'?' checked="checked"':'').' />';
	
		echo '<ul>';
		for($i=0; $i<self::$settings['interval']; $i++){
			echo '<li>';
			
			echo '<span class="bold">'.$lang['interval'].' '. ($i+1) .': </span>';
			echo '<span class="spaceLeft">'.$lang['srv_chart_num_limit_from'].' <input type="text" id="interval_'.$i.'_min_'.$spid.'_loop_'.self::$current_loop.'" name="interval_'.$i.'_min" value="'.$limits['interval_'.$i]['min'].'" class="advanced_interval" style="width:40px;" onBlur="chartAdvancedSettingsLimitLabel(\''.$i.'\', \''.$spid.'\', \''.self::$current_loop.'\'); chartAdvancedSettingsLimitCheck(\''.$i.'\', \''.$spid.'\', \''.self::$current_loop.'\');" onkeyup="checkNumber(this, 6, 2);" onkeypress="checkNumber(this, 6, 2);" /></span>';			
			echo '<span class="spaceLeft">'.$lang['srv_chart_num_limit_to'].' <input type="text" id="interval_'.$i.'_max_'.$spid.'_loop_'.self::$current_loop.'" name="interval_'.$i.'_max" value="'.$limits['interval_'.$i]['max'].'" class="advanced_interval" style="width:40px;" onBlur="chartAdvancedSettingsLimitLabel(\''.$i.'\', \''.$spid.'\', \''.self::$current_loop.'\'); chartAdvancedSettingsLimitCheck(\''.$i.'\', \''.$spid.'\', \''.self::$current_loop.'\');" onkeyup="checkNumber(this, 6, 2);" onkeypress="checkNumber(this, 6, 2);" /></span>';
			
			// labela intervala
			$label = ($limits['interval_'.$i]['label'] == '') ? $limits['interval_'.$i]['min'].'-'.$limits['interval_'.$i]['max'] : $limits['interval_'.$i]['label'];
			echo '<span class="spaceLeft">'.$lang['srv_chart_num_limit_label'].': <input type="text" id="interval_'.$i.'_label_'.$spid.'_loop_'.self::$current_loop.'" name="interval_'.$i.'_label" value="'.$label.'" style="width:120px;" /></span>';
			
			// Warningi, ce niso intervali v redu nastavljeni po velikosti
			$show = ($limits['interval_'.$i]['min'] >= $limits['interval_'.$i]['max'] && $limits['interval_'.$i]['min'] != '' && $limits['interval_'.$i]['max'] != '') ? '' : ' style="display:none;"';
			echo '<div id="chart_advanced_warning_1_interval_'.$i.'" class="chart_advanced_warning" '.$show.'>';
			echo $lang['srv_chart_num_limit_warning1'];
			echo '</div>';
			
			$show = ($limits['interval_'.$i]['min'] <= $limits['interval_'. ($i-1) ]['max'] && $limits['interval_'.$i]['min'] != '' && $limits['interval_'. ($i-1) ]['max'] != '') ? '' : ' style="display:none;"';
			echo '<div id="chart_advanced_warning_2_interval_'.$i.'" class="chart_advanced_warning" '.$show.'>';
			echo $lang['srv_chart_num_limit_warning2'];
			echo '</div>';

			echo '</li>';
		}
		echo '</ul>';
		
		// Polodprtost navzgor
		echo '<span style="padding-left:20px;">'.$lang['srv_chart_open_up'].': </span>';
		echo '<input type="checkbox" id="chart_advanced_open_up_'.$spid.'_loop_'.self::$current_loop.'" name="chart_advanced_open_up" value="1" '.(self::$settings['open_up']=='1'?' checked="checked"':'').' />';
		
		echo '</div>';
	}

	// Izpis opozorila ce ni vnesenih podatkov in ne prikazujemo grafa
	static function displayEmptyWarning($spid){
		
		//$spremenljivka = SurveyAnalysis::$_HEADERS[$spid]; 
		
		// Naslov posameznega grafa
		//echo '<div class="chart_title">Graf '.$spremenljivka['variable'].' nima veljavnih podatkov!</div>';
	}
	
	/** Izriše frekvence v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	static function frequencyVertical($spid) {
		global $lang;

		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = (self::$num_records == 0) ? 10 : self::$num_records;
		//$num_show_records = SurveyAnalysis::getNumRecords();
		
		// ce imamo prazno in ne prikazujemo praznih tabel
		$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
		if($hideEmpty == 1){
			
			$emptyData = true;
		
			if (count($spremenljivka['grids']) > 0)
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);
				
				if ($_variables_count > 0 )
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					
					if(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0)
						$emptyData = false;
				}
			}
		
			if($emptyData){
				self::displayEmptyWarning($spid);
				return;
			}
		}
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'"></div>';
		
		echo '<div id="freq_'.$spid.'_loop_'.self::$current_loop.'" class="freq_chart_table">';
		
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title.'</div>';		
		
		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_br anl_bb" style="font-size: '.(self::$fontSize+3).'px !important; padding:0px; margin-top:5px !important; border-collapse: collapse; width: 800px;">';
		
		if(self::$settings['type']==1){
			echo '<tr>';
			#odgovori								
			echo '<td class="anl_bl anl_br anl_bb anl_ac">'.$lang['srv_analiza_frekvence_titleAnswers'] . '</td>';
			echo '<td class="anl_br anl_bb anl_ac anl_w70">'. $lang['srv_analiza_frekvence_titleFrekvenca'] .'</td>';

			echo '</tr>';				
			// konec naslovne vrstice
		}

		$_answersOther = array();
		
		# dodamo opcijo kje izrisujemo legendo
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

		# izpišemo vlejavne odgovore
		$_current_grid = null;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']);
			
			# dodamo dodatne vrstice z albelami grida
			if ($_variables_count > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){

				$_sequence = $variable['sequence'];	# id kolone z podatki
				if (($variable['text'] != true && $variable['other'] != true) 
				|| (in_array($spremenljivka['tip'],array(4,8,21,22,25)))){
					# dodamo ime podvariable
					//if ($_variables_count > 1 && in_array($spremenljivka['tip'],array(2,6,7,16,17,18,19,20,21))) {
					if ($inline_legenda) {
						# ali rišemo dvojno črto med grupami
						if ( $_current_grid != $gid && $_current_grid !== null && $spremenljivka['tip'] != 6) {
							$options['doubleTop'] = true;
							$_current_grid = $gid;
						} else {
							$options['doubleTop'] = false;
							$_current_grid = $gid;
						}
						self::outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$options);
					}
					$counter = 0;
					$_kumulativa = 0;
					
					
					#po potrebi posortiramo podatke
					if (($spremenljivka['tip'] == 7 || $spremenljivka['tip'] == 22) && is_array(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])) {
						ksort(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']); 
					}
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							if ($counter < $num_show_records) {
								if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
									if (in_array($spremenljivka['tip'],array(4,7,8,19,20,21))) { // text, number, datum, mtext, mnumber, text* 
										$options['isTextAnswer'] = true;
									} else {
										$options['isTextAnswer'] = false;
									}
									$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
								}
							}
						}
						# izpišemo sumo veljavnih
						if(self::$settings['type'] == 1)
							$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 && self::$settings['type'] == 1) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					if(self::$settings['type'] == 1)
						$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}

		echo '</table>'.NEW_LINE;
		echo '</div>';
		
		// Izpisemo nastavitve za tabele
		echo '<div class="chart_settings printHide iconHide" style="margin-top: 5px;">';
		self::displaySingleSettings($spid);
		echo '</div>';	
			
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				echo '<div id="chart_other_text_'.$spid.'_loop_'.self::$current_loop.'" class="chart_other_text">';
				self::outputOtherAnswers($oAnswers);
				echo '</div>';
				
				echo '<div class="chart_settings other_settings printHide iconHide">';
				self::displayOtherSettings($spid);
				echo '</div>';
			}
			if (self::$returnChartAsHtml == false) {
				ob_flush(); flush();
			}
		}
		
		echo '</div>';
	}

	static function outputSubVariablaVertical($spremenljivka,$variable,$grid,$spid,$_options = array()) {
		global $lang;
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'doubleTop'	=>false,		# ali imamo novo grupa in nardimo dvojni rob
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$css_bck = 'anl_bck_freq_2 ';
		echo '<tr'.($options['doubleTop'] ? ' class="anl_double_bt"' : '').'>';
		
		echo '<td class="anl_bl anl_bt anl_bb anl_br anl_al anl_str">';
		echo $variable['naslov'];
		echo '</td>';

		if(self::$settings['type'] == 1)
			echo '<td class="anl_bb anl_br anl_w70">&nbsp;</td>';
		
		echo '</tr>';
	}
	
	static function outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,&$_kumulativa,$_options=array()) {
		global $lang;
		# opcije
			
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ($counter % 2 == 1) ? ' anl_bck_0_0' : '';

		$_valid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_kumulativa += $_valid; 

 		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'" >';
 		//echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
		
		if($options['isOtherAnswer'] == 1){
			// poravnava celice
			$cellAlign = (self::$settings['otherType'] == 0) ? ' anl_al' : ' anl_ac';
			
			echo '<td class="anl_bl anl_br '.$cellAlign.' '.$cssBck.'">'.$vkey;
			
			if(self::$settings['otherFreq'] == 1){
				echo '<td class="anl_ac anl_br '.$cssBck.'">';
				echo (int)$vAnswer['cnt'];
				echo '</td>';
			}
		}
		elseif(self::$settings['type'] == 0){
			// poravnava celice
			$cellAlign = (self::$settings['show_legend'] == 0) ? ' anl_al' : ' anl_ac';
			
			echo '<td class="anl_bl anl_br '.$cellAlign.' '.$cssBck.'">'.$vkey;
		}		
		else{
			// poravnava celice
			$cellAlign = (self::$settings['show_legend'] == 0) ? ' style="float: none;"' : '';
		
			echo '<td class="anl_bl anl_br '.$cssBck.'">';
			echo '<div class="anl_user_text_more_charts"  '.$cellAlign.'>'.$vkey.'</div>';
			echo (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
		}
		
		if ( $counter+1 == $options['num_show_records'] && $options['num_show_records'] < count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])) {
			echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatRight '.(self::$publicChart != true ? ' blue pointer' : '').' anl_more" onclick="showHidenTextTable(\''.$spid.'\', \''.$options['num_show_records'].'\', \''.self::$current_loop.'\');return false;">'.$lang['srv_anl_more'].'</div>'.NEW_LINE;
		}
		echo '</td>';	

		if(self::$settings['type'] == 1){
			echo '<td class="anl_ac anl_br '.$cssBck.'">';
			echo (int)$vAnswer['cnt'];
			echo '</td>';
		}
		
		echo '</tr>';
		$counter++;
		return $counter;
	}
	
	static function outputSumaValidAnswerVertical($counter,$_sequence,$spid,$_options=array()) {
		global $lang;
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$cssBck = ($counter % 2 == 1) ? ' anl_bck_0_0' : '';
		
		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0 ) ? TRUE : FALSE;
		$_hide_minus = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2 ) ? TRUE : FALSE;
		$value =((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0 ) ? 0 : 1;
		
		$_sufix = (SurveyAnalysis::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine		
		if (isset(SurveyAnalysis::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.SurveyAnalysis::$_CURRENT_LOOP['cnt'].$_sufix;
		}
		
		echo '<tr id="anl_click_missing_tr_'.$_sequence.$_sufix.'" class="'.($_brez_MV ? 'anl_bb' : 'anl_dash_red_bb').'">';
		
		echo '<td class="anl_bl anl_br anl_al anl_ita red '.$cssBck.'" >'.$lang['srv_anl_suma1'].'</td>';

		echo '<td class="anl_ita red anl_br anl_ac '.$cssBck.'" >';

		echo SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0; 
		echo '</td>';
		
		$_percent = SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0
			? 100 * SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']
			: 0;  
		
		echo '</tr>';
		return $counter;
		
	}
	
	static function outputInvalidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array()) {
		global $lang;	
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.SurveyAnalysis::$cssColors['text_' . ($counter & 1)];
		$cssBck = ($counter % 2 == 1) ? ' anl_bck_0_0' : '';

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
		
		$_sufix = (SurveyAnalysis::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine		
		if (isset(SurveyAnalysis::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.SurveyAnalysis::$_CURRENT_LOOP['cnt'].$_sufix;
		}
		
		$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;
		echo '<tr name="missing_detail_'.$_sequence.$_sufix.'"'.($_Z_MV ? '': ' class="hidden"').'>';
		//echo '<td class="anl_bl anl_br anl_ac gray" style="width:10px">&nbsp;</td>';
		echo '<td class="anl_bl anl_br">';
		echo '<div class="floatLeft"><div class="anl_tin2">'.'<span class="anl_user_text">' . $vkey . '</span>' . ' (' . $vAnswer['text'].')'.'</div></div>'.NEW_LINE;
		echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.SurveyAnalysis::formatNumber($_invalid, 2, '%').'</div>'.NEW_LINE;
		echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>'.NEW_LINE;
		echo '</td>';

		echo '<td class="anl_ac anl_br">';
		echo (int)$vAnswer['cnt'];
		echo '</td>';
		
		echo '</tr>';
		$counter++;
		return $counter;
	}
	
	static function outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$_options = array()) {
		global $lang;
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		//$cssBck = ' '.SurveyAnalysis::$cssColors['text_' . ($counter & 1)];
		$cssBck = ' '.SurveyAnalysis::$cssColors['text_1'];
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		$_hide_minus = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 1 || (int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		
		$_sufix = (SurveyAnalysis::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine		
		if (isset(SurveyAnalysis::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.SurveyAnalysis::$_CURRENT_LOOP['cnt'].$_sufix;
		}
		
		echo '<tr id="click_missing_1_'.$_sequence.$_sufix.'" class="anl_dash_red_bb'.($_brez_MV ?' hidden' : '').'">';
		
		echo '<td class="anl_bl anl_br anl_ita red" >';
		echo $lang['srv_analiza_manjkajocevrednosti'];
		// podrobno za missinge
		echo '<span id="single_missing_0'.$_sequence.$_sufix.'" class="printHide anl_ita anl_detail_percent'.($_hide_minus ? '' : ' hidden').'">&nbsp;&nbsp;';
		echo '<a href="#single_missing_'.$_sequence.$_sufix.'" onclick="show_single_missing(\''.$_sequence.$_sufix.'\', 0);return false;" > ' ;
		//echo  $lang['srv_analiza_missingSpremenljivke'] ;
		echo  ' <span class="faicon plus_orange folder_plusminus"></span> </a>';		
		echo '</span>';
		echo '<span id="single_missing_1'.$_sequence.$_sufix.'" class="printHide anl_ita anl_detail_percent'.($_hide_minus ? ' hidden' : '').'">&nbsp;&nbsp;';
		echo '<a href="#single_missing_'.$_sequence.$_sufix.'" onclick="show_single_missing(\''.$_sequence.$_sufix.'\', 1);return false;" > ' ;
		// echo  $lang['srv_analiza_missingSpremenljivke'] ;
		echo  ' <span class="faicon minus_orange folder_plusminus"></span> </a>';		
		echo '</span>';

		echo '<div id="single_missing_suma_'.$_sequence.$_sufix.'" class="floatRight anl_w50 anl_dash_bl anl_dash_bt  anl_ac anl_detail_percent hidden">100.0%</div>'.NEW_LINE;
		echo '<div id="single_missing_suma_freq_'.$_sequence.$_sufix.'" class="floatRight anl_w30 anl_dash_bt anl_ac anl_detail_percent hidden">'.SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'].'</div>'.NEW_LINE;
		echo '</td>';	

		echo '<td class="anl_ac anl_br anl_detail_cnt anl_ita red">';
		$answer['cnt'] =  SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
		echo (int)$answer['cnt'];
		echo '</td>';
		
		echo '</tr>';
		$counter++;
		return $counter;
	}
	
	static function outputSumaVertical($counter,$_sequence,$spid, $_options = array()) {
		global $lang;
		# opcije	

		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		
		$cssBck = ' anl_bck_text_0';
		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;

		$_sufix = (SurveyAnalysis::$podstran == M_ANALYSIS_SUMMARY_NEW ? '_NEW' : '');
		# da deluje razpiranje manjkajočih tudi kadar imamo skupine		
		if (isset(SurveyAnalysis::$_CURRENT_LOOP['cnt'])) {
			$_sufix = '_loop'.SurveyAnalysis::$_CURRENT_LOOP['cnt'].$_sufix;
		}
		
		echo '<tr id="click_missing_suma_'.$_sequence.$_sufix.'"  class="'.($_brez_MV ? 'hidden' : '').'">';

		//echo '<td class="anl_bl anl_ac anl_dash_bt anl_bb red anl_ita'.$cssBck.'">'.$lang['srv_anl_suma2'].'</td>'; 
		echo '<td class="anl_bl anl_dash_bt anl_br anl_bb">&nbsp;</td>';
		
		echo '<td class="anl_ac anl_dash_bt anl_br anl_bb anl_ita red" >' . (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0) . '</td>';
		
		echo '</tr>';		
	}
	
	/** izpišemo tabelo z tekstovnimi odgovori drugo
	 * 
	 * @param $skey
	 * @param $oAnswers
	 * @param $spid
	 */
	static function outputOtherAnswers($oAnswers) {
		global $lang;
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];

		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $_variable['variable'].' - ' : '');
		$title = SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )';
		echo '<div class="chart_title">'.$stevilcenje . $title.'</div>';
		
		
		echo '<table class="anl_tbl anl_bt anl_br anl_bb" style="font-size: '.(self::$fontSize+3).'px !important; padding:0px; margin:0px; border-collapse: collapse;">' . NEW_LINE;		

		$counter = 0;
		$_kumulativa = 0;
		if (is_countable(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			//$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		//$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));

		echo '</table>'.NEW_LINE;
	}

	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	static function sumMultiText($spid) {
		global $lang;

		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		if(!is_countable(SurveyAnalysis::$_LOOPS) || count(SurveyAnalysis::$_LOOPS) == 0)
			self::$sessionData[$spid] = $settings;	
		else
			self::$sessionData[$spid][SurveyAnalysis::$_CURRENT_LOOP['cnt']] = $settings;
		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = (self::$num_records == 0) ? 10 : self::$num_records;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		// ce imamo prazno in de prikazujemo praznih tabel
		$hideEmpty = SurveyDataSettingProfiles :: getSetting('hideEmpty');
		if($_answers['validCnt'] == 0 && $hideEmpty == 1){
			self::displayEmptyWarning($spid);
			return;
		}
		
		echo '<div class="chart_holder" id="chart_'.$spid.'_loop_'.self::$current_loop.'">';			
		//div za pozicijo popupa
		echo '<div id="'.$spid.'"></div>';
		
		echo '<div id="freq_'.$spid.'_loop_'.self::$current_loop.'" class="freq_chart_table">';
		
		// Naslov posameznega grafa
		$stevilcenje = (self::$numbering == 1 ? $spremenljivka['variable'].' - ' : '');
		$title = $spremenljivka['edit_graf'] == 0 ? $spremenljivka['naslov'] : $spremenljivka['naslov_graf'];
		echo '<div class="chart_title">'.$stevilcenje . $title.'</div>';	
			
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];
	
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		# tekst vprašanja
		echo '<table class="anl_tbl anl_bt anl_bb" style="font-size: '.(self::$fontSize+3).'px !important; padding:0px; margin:0px;  border-collapse: collapse;">' . NEW_LINE;
		
		# naslovna vrstica	
		if(self::$settings['type']==1){
			echo '<tr>';
			#odgovori											
			echo '<td class="anl_br anl_bl anl_bb anl_ac">'.$lang['srv_analiza_opisne_subquestion'] . '</td>';

			echo '<td class="anl_br anl_bb anl_ac" colspan="'.($_cols).'">'. $lang['srv_analiza_opisne_arguments'] .'</td>';

			echo '</tr>';			
		}
		// konec naslovne vrstice
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			echo '<tr>';
			echo '<td class="anl_bl anl_br anl_bb">&nbsp;</td>';

			if (count($_row['variables'])>0)
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
					echo '<td class="anl_br anl_bb anl_ac anl_str">';
					// echo $_col['variable'];
					echo $_col['naslov'];
					echo '</td>';
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
			echo '</tr>';
			$last = 0;
			//anl_bck_desc_2 anl_bl anl_br anl_variabla_sub 
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				echo '<tr class="anl_ac anl_bb">';
				echo '<td class="anl_br anl_bl anl_ac anl_str">';
				echo $grid['naslov'];
				echo '</td>';
				
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}

					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					
					foreach ($grid['variables'] AS $vid => $variable ){
                        
                        $_sequence = $variable['sequence'];	# id kolone z podatki
                        
                        if ($variable['other'] != true) {

							# tabela z navedbami
							echo '<td class=" anl_at" style="padding: 0pt; margin: 0pt; border-collapse: collapse; vertical-align:top;" >';
							echo '<table class="fullWidth anl_ac" style="padding:0; margin:0; border-collapse: collapse; vertical-align:top;">';

							$index=0;
							if (count($_valid_answers) > 0) { 
								foreach ($_valid_answers AS $answer) {

									$cssBck = ($index % 2 == 1) ? ' anl_bck_0_0' : '';
								
									$index++;
									$_ans = $answer[$_sequence];
									echo '<tr>';
									echo '<td class="anl_br '.$cssBck.'">';
									if ($_ans != null && $_ans != '') {
										echo $_ans;
									} else {
										echo '&nbsp;';
									}
									echo '</td>';
									echo '</tr>';
								}
							}
							if ($_all_valid_answers_cnt > $index) {
								$index++;
								echo '<tr>';
								echo '<td class="anl_br anl_user_text">';
								echo '<div id="valid_row_togle_more_'.$vid.'" class="floatRight '.(self::$publicChart != true ? ' blue pointer' : '').' anl_more" onclick="showHidenTextTable(\''.$spid.'\', \''.$num_show_records.'\', \''.self::$current_loop.'\');return false;">'.$lang['srv_anl_more'].'</div>'.NEW_LINE;
								echo '</td>';
								echo '</tr>';
							}

							echo '</table>';

							echo '</td>';
						}
					}
					$last = $_max_i;
				}
				echo '</tr>';
			}
		}
		echo '</table>'.NEW_LINE;
		echo '</div>';
		
		// Izpisemo nastavitve za tabele
		echo '<div class="chart_settings printHide iconHide" style="margin-top: 5px;">';
		self::displaySingleSettings($spid);
		echo '</div>';	
		
		echo '</div>';
	}
	
	
	/** Funkcije ki skrbijo za ajax del
	 * 
	 */
	public static function ajax() {
		global $global_user_id;
		
		
		if (isset ($_POST['anketa'])) {
			$anketa = $_POST['anketa'];
			self::$anketa = $_POST['anketa'];
		}
		if (isset ($_POST['spid']))
			$spid = $_POST['spid'];
		if (isset ($_POST['spr_type']))
			$spr_type = $_POST['spr_type'];
		
		
		// Ce imamo nastavljene loope (Skupine) - potem nastavimo trenuten loop v katerem se nahaja graf
		self::$current_loop = (isset ($_POST['loop'])) ? $_POST['loop'] : 'undefined';	
		if(self::$current_loop != 'undefined'){	
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();
			
			$loop = SurveyAnalysis::$_LOOPS[ (int)self::$current_loop-1 ];
			$loop['cnt'] = self::$current_loop;
			SurveyAnalysis::$_CURRENT_LOOP = $loop;
		}
		
		SurveyAnalysis::$podstran = 'charts';
		SurveyAnalysis::getFrequencys();

		if (isset ($_POST['settings_mode']))
			self::$settings_mode = $_POST['settings_mode'];
		

		// dobimo vse nastavitve iz sessiona
		if(self::$current_loop != 'undefined'){
			if(isset(self::$sessionData[$spid][self::$current_loop]))
				self::$settings = self::$sessionData[$spid][self::$current_loop];
			else
				self::$settings = self::getDefaultSettings();
		}
		else{
			if(isset(self::$sessionData[$spid]))
				self::$settings = self::$sessionData[$spid];
			else
				self::$settings = self::getDefaultSettings();
		}
		
		
		
		if (isset ($_POST['what']))
			$what = $_POST['what'];	
		if (isset ($_POST['value']))
			$value = $_POST['value'];	
		
		self::$settings[$what] = $value;
		
		
		if (isset ($_POST['num_records'])){
			$textAnswersMore = array('0'=>'10','10'=>'30','30'=>'300','300'=>'600','600'=>'900','900'=>'100000');
			self::$num_records = $textAnswersMore[$_POST['num_records']];
		}
		if ($_GET['a'] == 'change_chart') {
						
			switch ($spr_type) {
				case 1: # radio
				case 3:	# dropdown
					self::displayRadioChart($spid, self::$settings, $refresh=1);
					break;						
				case 2: #checkbox
					self::displayCheckboxChart($spid, self::$settings, $refresh=1);
					break;					
				case 6: # multigrid
					self::displayMultigridChart($spid, self::$settings, $refresh=1);
					break;
				case 62: # dvojni multigrid
					self::displayDoubleMultigridChart($spid, self::$settings, $refresh=1);
					break;
				case 7:	# število
                                case 22: # compute
					self::displayNumberChart($spid, self::$settings, $refresh=1);
					break;
				case 8:	# datum
					self::displayDateChart($spid, self::$settings, $refresh=1);
					break;	
				case 16: # multicheckbox
					self::displayMulticheckboxChart($spid, self::$settings, $refresh=1);
					break;
				case 17: # razvrščanje
					self::displayRankingChart($spid, self::$settings, $refresh=1);
					break;
				case 20: # multi number
					self::displayMultinumberChart($spid, self::$settings, $refresh=1);
					//self::frequencyVertical($spid);
					break;
				case 18: # vsota 
					self::displayVsotaChart($spid, self::$settings, $refresh=1);
					break;
				case 4:	# text
				case 5:	 # nagovor
				case 21: # besedilo* 		
				case 25: # kvota		
					self::frequencyVertical($spid);
					break;
				case 19: # multitext	
					self::sumMultiText($spid);
					break;
			}
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData(self::$sessionData, 'charts');
		}
		
		// nastavitve tabel za drugo
		if($_GET['a'] == 'change_other'){
			
			if(self::$current_loop != 'undefined'){
				self::$sessionData[$spid][self::$current_loop][$what] = $value;
				self::$settings = self::$sessionData[$spid][self::$current_loop];
			}
			else{
				self::$sessionData[$spid][$what] = $value;
				self::$settings = self::$sessionData[$spid];
			}
			
			// Napolnimo podatke za graf
			$DataSet = self::getDataSet($spid, self::$settings);

			$_answersOther = $DataSet->GetOther();
			if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
				foreach ($_answersOther AS $oAnswers) {
					self::outputOtherAnswers($oAnswers);
				}
			}
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData(self::$sessionData, 'charts');
		}

		// Brisanje cacha za grafe
		if ($_GET['a'] == 'clear_cache') {
			
			self::clearCache();
		}
		
		if ($_GET['a'] == 'show_spid_more_table') {
			
			// tabela besedilo
			if(SurveyAnalysis::$_HEADERS[$spid]['tip'] == 19)
				self::sumMultiText($spid);
						
			// navadno besedilo
			else
				self::frequencyVertical($spid);
		}		
		
		if ($_GET['a'] == 'chart_advanced_settings') {
			self::displayAdvancedSettings($spid);
		}
		
		if ($_GET['a'] == 'change_hq_settings') {
			
			self::$sessionData['hq'] = $value;
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData(self::$sessionData, 'charts');
		}
			
		if ($_GET['a'] == 'chart_save_advanced_settings') {
			
			# shranimo rekodiranje in po potrebi popravimo datoteko s podatki
			if ((int)$spid > 0 && (int)self::$anketa > 0) {
				# Shranimo zamenjave manjkajočih vrednosti pri posameznem vprašanu za analize
				if (isset($_REQUEST['edit_recode_mv']) || isset($_REQUEST['edit_recode_number'])) {
					$vmv = new RecodeValues(self::$anketa,$spid);
					$dataChanged = $vmv->SetUpMissingValuesForQuestion();
					//print_r("changed:".(int)$dataChanged );
					if ($dataChanged == true ) {
						$SDF = SurveyDataFile::get_instance();
						$SDF->init(self::$anketa);
						//print_r("new:".$createdNewFile);
						self::$headFileName = $SDF->getHeaderFileName();
						self::$dataFileName = $SDF->getDataFileName();
						self::$dataFileStatus = $SDF->getStatus();
					}
				}
			}
			
			// headers, ki ga popravimo in prepisemo originalnega (zaradi refresha label)
			$newHeaders = SurveyAnalysis::$_HEADERS;
			
			
			// SHRANIMO BARVE
			// preverimo najprej ce shranjujemo vrednosti, ki so enake kot izbran skin
			$default = true;
			$default_colors = self::getDefaultColors(self::$skin);
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
						
				self::$settings['colors'][$i-1] = $color;		
			}
			
			
			// SHRANIMO MEJE
			if (isset($_POST['chart_interval'])) {
				
				$limits = array();
				
				$interval = $_POST['chart_interval'];
				self::$settings['interval'] = $interval;
								
				if (isset($_POST['chart_number_limits_switch']))
					$limits['advanced_settings'] = $_POST['chart_number_limits_switch'];
				
				
				// Shranjevanje osnovnih nastavitev mej pri number
				if($limits['advanced_settings'] == 0){
				
					// shranimo polodprtost
					self::$settings['open_down'] = (isset($_POST['chart_basic_open_down']) && self::$settings['min'] != '' && $_POST['chart_basic_open_down'] == '1') ? 1: 0;
					self::$settings['open_up'] = (isset($_POST['chart_basic_open_up']) && self::$settings['max'] != '' && $_POST['chart_basic_open_up'] == '1') ? 1 : 0;
				
					if (isset($_POST['chart_min']))
						self::$settings['min'] = $_POST['chart_min'];
						
					if (isset($_POST['chart_max']))
						self::$settings['max'] = $_POST['chart_max'];
						
					self::$settings['limits'] = $limits;
				}	
				
				// Shranjevanje naprednih nastavitev mej pri number
				else{
					for($i=0; $i<$interval; $i++){
						
						// shranimo polodprtost
						self::$settings['open_down'] = (isset($_POST['chart_advanced_open_down']) && $_POST['chart_advanced_open_down'] == '1') ? 1 : 0;
						self::$settings['open_up'] = (isset($_POST['chart_advanced_open_up']) && $_POST['chart_advanced_open_up'] == '1') ? 1 : 0;
						
						// Minimum posameznega intervala
						if (isset($_POST['interval_'.$i.'_min']))
							$limits['interval_'.$i]['min'] = $_POST['interval_'.$i.'_min'];
						
						// Maximuma posameznega intervala
						if (isset($_POST['interval_'.$i.'_max']))
							$limits['interval_'.$i]['max'] = $_POST['interval_'.$i.'_max'];
							
						// Labela posameznega intervala
						if (isset($_POST['interval_'.$i.'_label']))
							$limits['interval_'.$i]['label'] = $_POST['interval_'.$i.'_label'];					
					}
					
					
					self::$settings['limits'] = $limits;
				}
			}
			
			
			// SHRANIMO LABELE		
			if (isset($_POST['edit_graf'])) {
				$edit_graf = $_POST['edit_graf'];
				
				$s = sisplet_query("UPDATE srv_spremenljivka SET edit_graf='$edit_graf' WHERE id='$spid'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				// popravimo upostevanje label v HEADER
				$newHeaders[$spid]['edit_graf'] = $edit_graf;
			}
			
			// naslov spremenljivke za graf
			if (isset($_POST['naslov_graf'])) {
				$naslov = $_POST['naslov_graf'];
				/*if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
					//$naslov = '<p>'.nl2br($naslov).'</p>';
					$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
				}*/
				
				/*$purifier = New Purifier();
				$naslov = $purifier->purify_DB($naslov);*/
				
				$s = sisplet_query("UPDATE srv_spremenljivka SET naslov_graf='$naslov' WHERE id='$spid'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				// popravimo naslov spremenljivke v HEADER
				$newHeaders[$spid]['naslov_graf'] = $naslov;
			}
		
			// shrani dodatne naslove variabel za graf
			if (isset($_POST['edit_vrednost_graf'])) {
				
				$i = 1;
				foreach ($_POST as $key => $v) {
					
					if (substr($key, 0, 14) == 'vrednost_graf_') {
						$vrednost = substr($key, 14);
						
						$naslov = str_replace(array('\n', '\t', '\r'), '', $_POST['vrednost_graf_'.$vrednost]);
						
						$s = sisplet_query("UPDATE srv_vrednost SET naslov_graf='".$naslov."' WHERE id = '$vrednost'");

						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
						
						
						// Popravimo variable v HEADER
						if($spr_type == 1 || $spr_type == 3){
							$newHeaders[$spid]['options_graf'][$i] = $naslov;
						}
						elseif($spr_type == 6 ){
							$newHeaders[$spid]['grids'][$i-1]['variables'][0]['naslov_graf'] = $naslov;
						}
						elseif($spr_type == 16 || $spr_type == 20){
							$newHeaders[$spid]['grids'][$i-1]['naslov_graf'] = $naslov;
						}
						elseif($spr_type != 7 || $i < 3){
							$newHeaders[$spid]['grids'][0]['variables'][$i-1]['naslov_graf'] = $naslov;
						}
						
						$i++;
					}
				}
			}
			
			// shrani dodatne naslove gridov za graf
			if (isset($_POST['edit_grid_graf'])) {
				
				$vrstni_red = 0;
				foreach ($_POST as $key => $v) {
					if (substr($key, 0, 10) == 'grid_graf_') {
						$vrstni_red++;
						
						$grid = substr($key, 10);
						$naslov = $_POST['grid_graf_'.$grid];
						$variable = $grid;
						$id= $vrstni_red;
						
						$other = '0';
						# manjkoajoče vrednosti (ne vem, zavrnil ...
						if (isset($_POST['missing_value_checkbox']) && is_array($_POST['missing_value_checkbox'])) {
							if (in_array($grid, $_POST['missing_value_checkbox'])) {
								# grid je manjkajoča vrednost
								$other = $grid.'';
								$id =  $grid;
							} 
						}
						$s = sisplet_query("UPDATE srv_grid SET naslov_graf='$naslov' WHERE id='$id' AND spr_id='$spid'");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
						
						
						// Popravimo gride v HEADER
						if($spr_type == 6 ){
							$newHeaders[$spid]['options_graf'][$vrstni_red] = $naslov;
						}
						elseif($spr_type == 16 || $spr_type == 20){
						
							foreach($newHeaders[$spid]['grids'] as $grdKey => $grdVal){
								$newHeaders[$spid]['grids'][$grdKey]['variables'][$vrstni_red-1]['naslov_graf'] = $naslov;
							}	
						}
					}
				}
			}
			
			
			// pobrisemo star header
			if (file_exists(self::$headFileName))
				unlink(self::$headFileName);
				
			// shranimo popravljen headers v novo datoteko	
			file_put_contents(self::$headFileName, serialize($newHeaders));
			
			SurveyAnalysis::$podstran = 'charts';
			SurveyAnalysis::$_HEADERS = $newHeaders;
			SurveyAnalysis::getFrequencys();

			
			// Na novo zgeneriramo graf
			switch ($spr_type) {
				case 1: # radio
				case 3:	# dropdown
					self::displayRadioChart($spid, self::$settings, $refresh=1);
					break;						
				case 2: #checkbox
					self::displayCheckboxChart($spid, self::$settings, $refresh=1);
					break;					
				case 6: # multigrid
					self::displayMultigridChart($spid, self::$settings, $refresh=1);
					break;
				case 62: # dvojni multigrid
					self::displayDoubleMultigridChart($spid, self::$settings, $refresh=1);
					break;
                                case 22: # compute	
				case 7:	# število
					self::displayNumberChart($spid, self::$settings, $refresh=1);
					break;
				case 8:	# datum
					self::displayDateChart($spid, self::$settings, $refresh=1);
					break;	
				case 16: # multicheckbox
					self::displayMulticheckboxChart($spid, self::$settings, $refresh=1);
					break;
				case 17: # razvrščanje
					self::displayRankingChart($spid, self::$settings, $refresh=1);
					break;
				case 20: # multi number
					self::displayMultinumberChart($spid, self::$settings, $refresh=1);
					//self::frequencyVertical($spid);
					break;
				case 18: # vsota 
					self::displayVsotaChart($spid, self::$settings, $refresh=1);
					break;
				case 4:	# text
				case 5:	 # nagovor
				case 21: # besedilo* 
				case 25: # kvota
					self::frequencyVertical($spid);
					break;
				case 19: # multitext	
					self::sumMultiText($spid);
					break;
			}
			
			// Shranimo spremenjene nastavitve v bazo
			SurveyUserSession::saveData(self::$sessionData, 'charts');
		}
		
		// spremenimo skalo spremenljivke (ordinalna/nominalna)
		if ($_GET['a'] == 'chart_advanced_settings_skala') {

			$spremenljivka = $_POST['spid'];
			$skala = $_POST['skala'];
			
			# popravimo skalo spremenljivke
			# skala - 0 Ordinalna
			# skala - 1 Nominalna
			if ( isset($skala) && (int)$spremenljivka) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET skala='".$skala."' WHERE id='$spremenljivka'");

				# popravimo v header datoteki
				SurveyAnalysis::$_HEADERS[$spremenljivka]['skala'] = $skala;
				file_put_contents(self::$headFileName, serialize(SurveyAnalysis::$_HEADERS));
			}
		}
		
		// Globalne nastavitve za vse grafe
		if ($_GET['a'] == 'save_global_settings') {
			
			SurveyUserSetting :: getInstance()->saveSettings('default_chart_profile_'.$what, $value);
		}
		
		// Odpremo okno za izbiro globalnega skina
		if($_GET['a'] == 'analiza_show_chart_color') {

			$skin = (SurveyUserSetting::getInstance()->getSettings('default_chart_profile_skin') == null ? '1ka' : SurveyUserSetting::getInstance()->getSettings('default_chart_profile_skin'));
			
			self::displaySettingsProfiles($skin);
		}
		
		// Spreminjamo globalen skin
		if($_GET['a'] == 'analiza_change_chart_color') {
			
			//$skin = SurveyUserSetting :: getInstance()->getSettings('default_chart_profile_skin');
			if (isset ($_POST['skin']))
				$skin = $_POST['skin'];
			
			self::displaySettingsProfiles($skin);
		}
		
		// Preimenujemo globalen skin
		if($_GET['a'] == 'renameSkin') {

			if (isset ($_POST['id']))
				$id = $_POST['id'];
			if (isset ($_POST['name']))
				$name = $_POST['name'];
			
			$s = sisplet_query("UPDATE srv_chart_skin SET name='$name' WHERE id='$id'");
			
			self::displaySettingsProfiles($id);
		}
		
		// Pobrisemo globalen skin
		if($_GET['a'] == 'deleteSkin') {

			if (isset ($_POST['id']))
				$id = $_POST['id'];
			
			$s = sisplet_query("DELETE FROM srv_chart_skin WHERE id='$id'");
			
			self::displaySettingsProfiles();
		}
		
		// Dodamo nov globalen skin
		if($_GET['a'] == 'newSkin') {
			
			if (isset ($_POST['name']))
				$name = $_POST['name'];
			if (isset ($_POST['colors']))
				$colors = $_POST['colors'];
			
			$s = sisplet_query("INSERT INTO srv_chart_skin (name, colors, usr_id) VALUES('$name', '$colors', '$global_user_id')");
			$id = mysqli_insert_id($GLOBALS['connect_db']);
			
			self::displaySettingsProfiles($id);
		}
		
		// Popravimo obstojec custom skin
		if($_GET['a'] == 'editSkin') {
			
			if (isset ($_POST['id']))
				$id = $_POST['id'];
			if (isset ($_POST['colors']))
				$colors = $_POST['colors'];
			
			$s = sisplet_query("UPDATE srv_chart_skin SET colors='$colors' WHERE id='$id'");
		}
		
		// Preklop stevila intervalov pri mejah v naprednih nastavitvah
		if($_GET['a'] == 'analiza_num_limit_interval') {
			
			if (isset ($_POST['interval']))
				self::$settings['interval'] = $_POST['interval'];
			
			self::displayAdvancedSettingsLimits($spid, $mode=1);
		}	
		
		echo '<script>charts_init();</script>';
	}
	
	static function setUpReturnAsHtml($returnAsHtml = false) {
   		self::$returnChartAsHtml = $returnAsHtml;					# ali vrne rezultat analiz kot html ali ga izpiše
    }
  
    static function setUpIsForArchive($isArchive = false) {
    	#nastavimo timestamp, katerega dodamo imenu slike, za unikatnost
    	list($usec, $sec) = explode(" ", microtime());
    	self::$chartArchiveTime = $sec;
    	self::$isArchive = $isArchive;					# nastavimo da smo v arhivu
    	return self::$chartArchiveTime;
    }

	
	static function displaySettingsProfiles($skin='1ka'){
		global $site_path;
		global $lang;

		
		echo '<h2 style="margin-bottom:5px;">'.$lang['srv_chart_skin_long'].'</h2>';
		echo '<span style="font-size: 12px; font-style: italic;">'.$lang['srv_chart_skin_info'].'</span><br/><br/>';
		
		// Opozorilo na vrhu
		if(!is_numeric($skin)){
			echo '<div id="chart_skin_note">';
			echo $lang['srv_chart_skin_warning'];
			echo '</div>'; 
			echo '<br class="clr" />'."\n";
		}	
		
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
		
		
		echo '<div id="chart_settings_profiles_left">';	
		
		// Prednastavljeni skini
		echo '<span class="bold">'.$lang['srv_chart_skin_default'].':</span>';
       	echo '<span class="chart_profiles_holder" style="margin-bottom: 10px; height: 144px;">';
       	echo '<span id="chart_profiles" class="chart_profiles select">';

		echo '<div class="option'.($skin == '1ka' ? ' active' : '').'" id="chart_profile_skin_1ka" value="1ka">'.$lang['srv_chart_skin_1ka'].'</div>';	
		echo '<div class="option'.($skin == 'lively' ? ' active' : '').'" id="chart_profile_skin_0" value="lively">'.$lang['srv_chart_skin_0'].'</div>';	
		echo '<div class="option'.($skin == 'mild' ? ' active' : '').'" id="chart_profile_skin_1" value="mild">'.$lang['srv_chart_skin_1'].'</div>';
		echo '<div class="option'.($skin == 'office' ? ' active' : '').'" id="chart_profile_skin_6" value="office">'.$lang['srv_chart_skin_6'].'</div>';
		echo '<div class="option'.($skin == 'pastel' ? ' active' : '').'" id="chart_profile_skin_7" value="pastel">'.$lang['srv_chart_skin_7'].'</div>';
		echo '<div class="option'.($skin == 'green' ? ' active' : '').'" id="chart_profile_skin_2" value="green">'.$lang['srv_chart_skin_2'].'</div>';
		echo '<div class="option'.($skin == 'blue' ? ' active' : '').'" id="chart_profile_skin_3" value="blue">'.$lang['srv_chart_skin_3'].'</div>';
		echo '<div class="option'.($skin == 'red' ? ' active' : '').'" id="chart_profile_skin_4" value="red">'.$lang['srv_chart_skin_4'].'</div>';
		echo '<div class="option'.($skin == 'multi' ? ' active' : '').'" id="chart_profile_skin_5" value="multi">'.$lang['srv_chart_skin_5'].'</div>';
		
		echo '</span>';
		echo '</span>';			
		
		
		// Custom kreirani skini
		$custom_skins = self::getCustomSkins();
		
		echo '<span class="bold">'.$lang['srv_chart_skin_custom'].':</span>';
       	echo '<span class="chart_profiles_holder">';
       	echo '<span id="chart_profiles_custom" class="chart_profiles select">';

		foreach ($custom_skins as $custom_skin ){
			echo '<div class="option'.($skin == $custom_skin['id'] ? ' active' : '').'" id="chart_profile_skin_'.$custom_skin['id'].'" value="'.$custom_skin['id'].'">'.$custom_skin['name'].'</div>';
		}

		echo '</span>';
		echo '</span>';		
		
		// Ce je izbran custom skin imamo na dnu gumba brisi in preimenuj
		if(is_numeric($skin)){
        	echo '<a href="#" onclick="chart_skin_action(\'show_rename\'); return false;">'.$lang['srv_rename_profile'].'</a><br/>'."\n";
			echo '<a href="#" onclick="chart_skin_action(\'show_delete\'); return false;">'.$lang['srv_delete_profile'].'</a>'."\n";
		}
				
		echo '</div>';
		

		echo '<div id="chart_settings_profiles_right">';

		// ce je numeric je custom skin
		if(is_numeric($skin)){			
			
			$custom_skin = self::getCustomSkin($skin);
			
			$colors = explode('_', $custom_skin['colors']);
			$default_colors = $colors;
		}
		else{
			// preview za default skine
			echo '<div id="div_chart_skin_previews">';
			echo $lang['srv_chart_skin_preview'].':';
			self::displayChartSkinPreview($skin);
			echo '</div>';
			
			$default_colors = self::getDefaultColors($skin);
		}
		
		// Izbira custom skina
		echo '<div id="chart_custom_skin">';
		
		echo '  <div id="picker" style="float: right;"></div>';	


		for($i=0; $i<7; $i++){
			$name = 'color'.($i+1);
			//$value = (self::$settings['colors'][$i] != '') ? self::$settings['colors'][$i] : $default_colors[$i];
			$value = $default_colors[$i];
			
			echo '  <div class="form-item"><label for="'.$name.'">'.$lang['srv_color'].' '.($i+1).': </label><input type="text" id="'.$name.'" name="'.$name.'" class="colorwell" value="'.$value.'" /></div>';
		}
		
		// reset na default barvo
		echo '<br /><span class="as_link clr" onClick="chartAdvancedSettingsSetColor(\''.(is_numeric($skin) ? implode("_",$colors) : $skin).'\')">'.$lang['srv_chart_advanced_default_color'].'</span>';
				
		echo '</div>';

		echo '</div>';

		
		// cover Div
        echo '<div id="dsp_cover_div"></div>'."\n";
		
        // div za kreacijo novega
        echo '<div id="newChartSkin">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="newChartSkinName" name="newChartSkinName" type="text" value="" size="50"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="chart_skin_action(\'new\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="chart_skin_action(\'cancel_new\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
        
        // div za preimenovanje
        echo '<div id="renameChartSkin">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="renameChartSkinName" name="renameChartSkinName" type="text" value="' . $custom_skin['name'] . '" size="50"  />'."\n";
        echo '<input id="renameChartSkinId" type="hidden" value="' . $custom_skin['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="chart_skin_action(\'rename\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="chart_skin_action(\'cancel_rename\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
                
        // div za brisanje
        echo '<div id="deleteChartSkin">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . $custom_skin['name'] . '</b>?'."\n";
        echo '<input id="deleteChartSkinId" type="hidden" value="' . $custom_skin['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="chart_skin_action(\'delete\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="chart_skin_action(\'cancel_delete\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
	
		
		echo '<span class="clr"></span>';

		echo '<div style="position:absolute; bottom:20px; right:20px;">';
		
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="save_chartColor(); return false;"><span>'.$lang['save'].'</span></a></div></span>';	
		echo '<span class="floatRight spaceRight" title="'.$lang['srv_save_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="chart_skin_action(\'show_new\'); return false;"><span>'.$lang['srv_save_new_profile'] . '</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="close_chartColor(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
	
		echo '</div>';
	}
	
	static function displayChartSkinPreview ($skin) {
		global $lang;

        echo '<div id="div_chart_skin_preview_1ka" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color1ka.png\'); '.($skin == '1ka' ? ' display:block;' : '').'">';
		echo '</div>';

		echo '<div id="div_chart_skin_preview_0" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color0.png\'); '.($skin == 'lively' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_1" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color1.png\'); '.($skin == 'mild' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_2" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color2.png\'); '.($skin == 'green' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_3" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color3.png\'); '.($skin == 'blue' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_4" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color4.png\'); '.($skin == 'red' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_5" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color5.png\'); '.($skin == 'multi' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_6" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color6.png\'); '.($skin == 'office' ? ' display:block;' : '').'">';
		echo '</div>';
		
		echo '<div id="div_chart_skin_preview_7" class="div_chart_skin_preview" style="background-image: url(\'pChart/preview/color7.png\'); '.($skin == 'pastel' ? ' display:block;' : '').'">';
		echo '</div>';
	}
	
	static function getCustomSkins(){
		global $global_user_id;
		
		$skins = array();
		
		$sql = sisplet_query("SELECT * FROM srv_chart_skin WHERE usr_id='$global_user_id'");
		while($row = mysqli_fetch_array($sql)){
			$skins[] = $row;
		}
		
		return $skins;
	}
	
	static function getCustomSkin($id){
		global $global_user_id;
		
		$sql = sisplet_query("SELECT * FROM srv_chart_skin WHERE usr_id='$global_user_id' AND id='$id'");
		$skin = mysqli_fetch_array($sql);
		
		return $skin;
	}
    
    static function displayPublicChart($properties = array()) {
        global $lang;
        global $site_url;
    
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
		
        $anketa = self::$anketa;
		
        if ($anketa > 0) {
            $sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
            $row = mysqli_fetch_assoc($sql);
            $lang_admin = $row['lang_admin'];
        } else {
            $sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
            $row = mysqli_fetch_assoc($sql);
            $lang_admin = $row['value'];
        }
		

        #izpišemo HTML
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
        echo '<script type="text/javascript" src="'.$site_url.'admin/survey/script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
        echo '<script type="text/javascript" src="'.$site_url.'admin/survey/minify/g=jsnew"></script>';
        echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=css" media="screen" rel="stylesheet" />';
        echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=cssPrint" media="print" rel="stylesheet" />';
        echo '<style>';
        echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
        echo '</style>';
        echo '<!--[if lt IE 7]>';
        echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
        echo '<![endif]-->';
        echo '<!--[if IE 7]>';
        echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
        echo '<![endif]-->';
        echo '<!--[if IE 8]>';
        echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
        echo '<![endif]-->';
        echo '<style>';
        echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
        echo '</style>';
        echo '<script>';
        echo 'function chkstate(){';
        echo '    if(document.readyState=="complete"){';
        echo '        window.close()';
        echo '    }';
        echo '    else{';
        echo '        setTimeout("chkstate()",2000)';
        echo '    }';
        echo '}';
        echo 'function print_win(){';
        echo '    window.print();';
        echo '    chkstate();';
        echo '}';
        echo 'function close_win(){';
        echo '    window.close();';
        echo '}';
        echo '</script>';
        echo '</head>';
    
        echo '<body style="margin:5px; padding:5px;" >';
        echo '<h2>'.$lang['srv_publc_chart_title_for'] . self::$survey['naslov'].'</h2>';

        echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $anketa . '" />';
        echo '<div id="analiza_data">';
        
        # ponastavimo nastavitve- filter
        self::Display();
        echo '</div>';
            
        echo '<div id="navigationBottom" class="printHide">';   
        echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/printer.png" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';

        echo '<br class="clr" />';
        echo '</div>';
    
        echo '</body>';
        echo '</html>';
    }
}
?>