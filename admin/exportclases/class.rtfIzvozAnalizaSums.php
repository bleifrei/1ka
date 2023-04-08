<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.rtfIzvozAnalizaFunctions.php');
	require_once("class.enka.rtf.php");

	define("FNT_TIMES", "Times New Roman", true);
	define("FNT_ARIAL", "Arial", true);

	define("FNT_MAIN_TEXT", FNT_TIMES, true);
	define("FNT_QUESTION_TEXT", FNT_TIMES, true);
	define("FNT_HEADER_TEXT", FNT_TIMES, true);

	define("FNT_MAIN_SIZE", 18, true);
	define("FNT_QUESTION_SIZE", 10, true);
	define("FNT_HEADER_SIZE", 10, true);
	
	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge


class RtfIzvozAnalizaSums {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	
	var $current_loop = 'undefined';
	
	static public $_FILTRED_OTHER = array(); 				# filter za polja drugo
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $sprID = null, $loop = null)
	{
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){

			$this->anketa['id'] = $anketa;
			$this->spremenljivka = $sprID;
			
			SurveyAnalysis::Init($this->anketa['id']);
			SurveyAnalysis::$setUpJSAnaliza = false;

			// create new RTF document
			$this->rtf = new enka_RTF();
			
            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->anketa['id']);           
            $SDF->prepareFiles();  

            $this->headFileName = $SDF->getHeaderFileName();
            $this->dataFileName = $SDF->getDataFileName();
            $this->dataFileStatus = $SDF->getStatus();
			
			SurveyZankaProfiles :: Init($this->anketa['id'], $global_user_id);
			$this->current_loop = ($loop != null) ? $loop : $this->current_loop;
		}
		else{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
			
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init()){
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}
		else
            return false;
            
		// ce smo prisli do tu je vse ok
        $this->pi['canCreate'] = true;
        
		return true;
	}

	function getAnketa()
	{ return $this->anketa['id']; }

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		//Close and output rtf document
//		$this->rtf->Output($fileName, 'I');
		$this->rtf->display($fileName = "analiza.rtf",true);
	}

	function init()
	{
		global $lang;
		
		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'left');
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'right');
		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right');
		$this->rtf->set_default_font(FNT_TIMES, FNT_MAIN_SIZE);
		return true;
	}

	function createRtf()
	{
		global $site_path;
		global $lang;
		   		
		// izpisemo prvo stran
		//$this->createFrontPage();
		
		$this->rtf->draw_title($lang['export_analisys_sums']);
		$this->rtf->new_line(1);

		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		
		# preberemo header
		if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE || $this->dataFileStatus == FILE_STATUS_SRV_DELETED) {
			
			$this->rtf->draw_title('NAPAKA!!! Manjkajo datoteke s podatki.');
		
		} else {

			//polovimo podatke o nastavitvah trenutnega profila (missingi..)
			SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);
		
			// Preverimo ce imamo zanke (po skupinah)
			SurveyAnalysis::$_LOOPS = SurveyZankaProfiles::getFiltersForLoops();

			# če nimamo zank
			if(count(SurveyAnalysis::$_LOOPS) == 0){
			
				$this->displayTables();
			}
			else{
				// izrisemo samo eno tabelo iz enega loopa
				if($this->current_loop > 0){
					
					$loop = SurveyAnalysis::$_LOOPS[(int)$this->current_loop-1];
					$loop['cnt'] = $this->current_loop;
					SurveyAnalysis::$_CURRENT_LOOP = $loop;
					
					// Izpisemo naslov zanke za skupino
					$this->rtf->new_line(1);
					$this->rtf->set_font("Arial Black", 9);
					$this->rtf->add_text($this->enkaEncode($lang['srv_zanka_note'].$loop['text']), 'left');
					$this->rtf->set_font("Arial", 8);
					$this->rtf->new_line();
				
					$this->displayTables();
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
						$this->rtf->new_line(1);
						$this->rtf->set_font("Arial Black", 9);
						$this->rtf->add_text($this->enkaEncode($lang['srv_zanka_note'].$loop['text']), 'left');
						$this->rtf->set_font("Arial", 8);
						$this->rtf->new_line();
						
						$this->displayTables();
					}
				}
			}
	
		} // end if else ($_headFileName == null)				
	}

	function displayTables(){
		global $site_path;
		global $lang;
		global $global_user_id;
	
		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		# polovimo frekvence			
		SurveyAnalysis::getFrequencys();
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		$vars_count = count(SurveyAnalysis::$_FILTRED_VARIABLES);
		$line_break = '';
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if (($spremenljivka['tip'] != 'm'
			 && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES )) 
			 && (!isset($_spid) || (isset($_spid) && $_spid == $spid))
			 &&	($this->spremenljivka == $spid || $this->spremenljivka == null) ) {
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]) ) ) {

					# 	prikazujemo v odvisnosti od kategorije spremenljivke
					switch ($spremenljivka['tip']) {
						case 1:
							# radio - prikaže navpično					
							self::sumVertical($spid,'sums');
						break;
						
						case 2:
							#checkbox  če je dihotomna:
							self::sumVerticalCheckbox($spid,'sums');
						break;
						
						case 3:
							# dropdown - prikjaže navpično					
							self::sumVertical($spid,'sums');
						break;
						
						case 6:
							# multigrid
							self::sumHorizontal($spid,'sums');
						break;
						
						case 16:
							#multicheckbox če je dihotomna:
							self::sumMultiHorizontalCheckbox($spid,'sums');
						break;
						
						case 17:
							#razvrščanje  če je ordinalna 
							self::sumHorizontal($spid,'sums');
						break;
						
						case 4:	# text
						case 8:	# datum
							self::sumTextVertical($spid,'sums');
						break;
						
						case 21: # besedilo*
							# varabla tipa »besedilo« je v sumarniku IDENTIČNA kot v FREKVENCAH.
							if ($spremenljivka['cnt_all'] == 1) {
								// če je enodimenzionalna prikažemo kot frekvence
								// predvsem zaradi vprašanj tipa: language, email... 
								self::sumTextVertical($spid,'sums');
							} else {
								self::sumMultiText($spid,'sums');
							}
						break;
						
						case 19: # multitext
							self::sumMultiText($spid,'sums');
						break;

						case 7:
						case 18:
						case 22:
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumNumberVertical($spid,'sums');
						break;
						
						case 20:
							# variabla tipa »število« je v sumarniku identična kot v DESCRIPTIVES.
							self::sumMultiNumber($spid,'sums');
						break;
						
						case 5:
							# nagovor
							self::sumNagovor($spid,'sums');
						break;		
                                            
                                                case 26: # lokacija
							self::sumLokacija($spid,'sums');
						break;
					}
					
				} 
					
			} // end if $spremenljivka['tip'] != 'm'
				
		} // end foreach self::$_HEADERS
	}


	/** Izriše sumarnik v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		# dodamo opcijo kje izrisujemo legendo
		$inline_legenda = false;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false);
		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$this->tableHeader();
		
		$show_valid_percent = (SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent'] == true) ? 1 : 0;											
		// konec naslovne vrstice

		
		$_answersOther = array();
		$sum_xi_fi=0;
		$N = 0;
									
		$_tmp_for_div = array();
		# izpišemo vlejavne odgovore
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			# dodamo dodatne vrstice z albelami grida
			if (count($grid['variables']) > 0 )
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['text'] != true && $variable['other'] != true) {
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;

					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							
							// za povprečje
							$xi = $vkey;
							$fi = $vAnswer['cnt'];
							
							$sum_xi_fi += $xi * $fi ;
							$N += $fi;

							if ($vAnswer['cnt'] > 0 /*&& $counter < $maxAnswer*/ || true) { # izpisujemo samo tiste ki nisno 0
								
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
							# za poznejše računannje odklona
							$_tmp_for_div[] = array('xi'=>$xi, 'fi'=>$fi, 'sequence'=>$_sequence); 
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}		
		}

		# odklon
		$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
		#standardna diviacija
		$div = 0;
		$sum_pow_xi_fi_avg  = 0;
		foreach ( $_tmp_for_div as $tkey => $_tmp_div_data) {
			$xi = $_tmp_div_data['xi'];
			$fi =  $_tmp_div_data['fi'];
			
			$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
		}
		$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;

		$this->rtf->MyRTF .= "}";
		
		# izpišemo še odklon in povprečje
		if ($show_valid_percent == 1 && SurveyAnalysis::$_HEADERS[$spid]['skala'] != 1) {
			
			$this->rtf->MyRTF .= "{\par";
			
			$defw_full = 10300;
			$defw_part0 = 1100;
			$defw_part = 1300;
			$defw_part2 = 4000;
			
			$bold = '\b0';
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';		
			
			$tableHeader = '\trowd\trql\trrh400';
			
			$table .= '\clvertalc\cellx'.( $defw_part0 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . '\qc\cell';
			
			$table .= '\clvertalc\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . '\cell';
				
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_povprecje']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2*$defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';	
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3*$defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_odklon']),20,'...').'\qc\cell';	
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4*$defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';	
			
			$tableEnd .= '\pard\intbl\row';
			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			$this->tableEnd();
		}		

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}		
		}
	}

	function sumVerticalCheckbox($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		$inline_legenda = count ($spremenljivka['grids']) > 1;
		if ($variable['other'] != '1' && $variable['text'] != '1') {
			$_tip =  SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
			$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala');
		} else {
			$_tip =  $lang['srv_analiza_vrsta_bese'];
			$_oblika =  $lang['srv_analiza_oblika_nomi'];
		}
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0) 
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_valid_cnt[$gid] = max($_valid_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				$_valid[$gid][$vid] = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'];
				$_navedbe[$gid] += SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
			}
		}
			
		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1000;
		$defw_part2 = 2400;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 5 * $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_units']),20,'...') . $align . '\qc\cell';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 7 * $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_arguments']),20,'...') . $align . '\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		//tretja vrstica
		$text = array();
		
		$text[] = '';
		$text[] = '';
		$text[] = $this->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_valid']);
		$text[] = $this->encodeText('% - '.$lang['srv_analiza_opisne_valid']);
		$text[] = $this->encodeText($lang['srv_analiza_num_units_valid']);
		$text[] = $this->encodeText('% - '.$lang['srv_analiza_num_units_valid']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_frequency']);
		$text[] = $this->encodeText('%');

		$this->tableRowVerticalCheckbox($text);
		//konec naslovnih vrstic
		
		$_max_valid = 0;
		$_max_appropriate = 0;
		if (count ($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] as $gid => $grid) {
			if (count ($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				if ($variable['other'] != 1) {
					$_sequence = $variable['sequence'];
					
					$text = array();
		
					$text[] = $this->encodeText($variable['variable']);
					$text[] = $this->encodeText($variable['naslov']);				
					
					// Frekvence
					$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					// Veljavni
					$text[] = $this->encodeText((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']));

					// Veljaveni %
					$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0; 
					$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					$_max_valid = max ($_max_valid, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
					
					// Ustrezni
					$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
					
					// % Ustrezni
					$valid = (int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt']);
					$valid = (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					$_percent = ($_max_appropriate > 0 ) ? 100*$valid / $_max_appropriate : 0;
					$text[] =  $this->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));			
					
					$text[] =  $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']);
					
					$_percent = ($_navedbe[$gid] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'] / $_navedbe[$gid] : 0;
					$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
					
					$this->tableRowVerticalCheckbox($text);
											
				} else {
					# drugo 
				}
			}
			
			$text = array();
			
			$text[] = '';
			
			$text[] = $this->encodeText($lang['srv_anl_suma_valid']);
			
			$text[] = '';
			
			$text[] = $this->encodeText($_max_valid);
			$text[] = '';			
			
			$text[] = $this->encodeText($_max_appropriate);	
			$text[] = '';
			
			$text[] = $this->encodeText($_navedbe[$gid]);
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber('100',SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'));
						
			$this->tableRowVerticalCheckbox($text);
		}
		$this->tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}	
		}		
	}
	
	/** Izriše nagovor
	 * 
	 */
	function sumNagovor($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_tip = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'izrazanje');
		$_oblika = SurveyAnalysis::getSpremenljivkaLegenda($spremenljivka,'skala'); 

		self::tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		self::tableEnd();
	}
	
	/** Izriše number odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumNumberVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);

		# ali izpisujemo enoto:
		$show_enota = true;
		if ((int)$spremenljivka['enota'] == 0 && SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1) {
			$show_enota = false;
		}
		
		# ugotovimo koliko imamo kolon
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_clmn_cnt[$gid] = $grid['cnt_vars']-$grid['cnt_other'];
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable) {
				$_sequence = $variable['sequence'];
				$_approp_cnt[$gid] = max($_approp_cnt[$gid], SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				
				# za povprečje				
				$sum_xi_fi=0;
				$N = 0;
				$div=0;
				$min = null;
				$max = null;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {

						$fi = $_validFreq['cnt'];
						$sum_xi_fi += $xi * $fi ;
						$N += $fi;
						$min = $min != null ? min($min,$xi) : $xi;
						$max = max($max,$xi);
					}
				}
				
				#povprečje
				$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
				$sum_avg += $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'] = $avg;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin'] = $min;
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax'] = $max;

				#standardna diviacija
				$div = 0;
				$sum_pow_xi_fi_avg  = 0;
				if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > 0 ) {
					foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $xi => $_validFreq) {
						$fi = $_validFreq['cnt'];
						$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
					}
				}
				SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'] = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
				
				#določimo še polja drugo za kasnejši prikaz
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}		
		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica		
		$text = array();
		
		$text[] = '&nbsp; ';
		
		if ($show_enota) {
			if  ($spremenljivka['tip'] == 20 || $spremenljivka['tip'] == 7) {
				$text[] = $this->encodeText($lang['srv_analiza_opisne_subquestion']);;
			} else {
				$text[] = $this->encodeText($lang['srv_analiza_opisne_variable_text']);
			}
		} else {
			$text[] = '&nbsp; ';
		}
		
		$text[] = $this->encodeText($lang['srv_analiza_opisne_m']);
		$text[] = $this->encodeText($lang['srv_analiza_num_units']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_povprecje']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_odklon']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_min']);
		$text[] = $this->encodeText($lang['srv_analiza_opisne_max']);

		$params = array('bold' => 'B');
		
		$this->tableRowNumberVertical($text, $params);
		//konec naslovnih vrstic

		$_answersOther = array();
		$_grupa_cnt = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			if (count($spremenljivka['grids']) > 1 && $_grupa_cnt !== 0 && $spremenljivka['tip'] != 6) {
				$grid['new_grid'] = true;
			}

			$_grupa_cnt ++;
			if (count($grid['variables']) > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					if ($variable['other'] != true) {
						$_sequence = $variable['sequence'];
			
						$text = array();
		
						if ($spremenljivka['tip'] != 7 ) {
							$text[] = $this->encodeText($variable['variable']);
						}
						else
							$text[] = '&nbsp; ';
						
						if ($show_enota) {
							$text[] = $this->encodeText((count($grid['variables']) > 1 && $spremenljivka['tip'] == 20 ? $grid['naslov'] . ' - ' : '' ).$variable['naslov']);
						} else {
							$text[] = '&nbsp; ';;
						}
						
						$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']);
						$text[] = $this->encodeText((int)$_approp_cnt[$gid]);
						$text[] = $this->encodeText(SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validAvg'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),''));
						$text[] = $this->encodeText(SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validDiv'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_DEVIATION'),''));
						$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMin']);
						$text[] = $this->encodeText((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validMax']);

						$this->tableRowNumberVertical($text);
						
					} else {
						$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
					}
					$grid['new_grid'] = false;
				}
				
			}
		}
		$this->tableEnd();
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}		
		}
	}

	/** Izriše sumarnik v horizontalni obliki za multigrid
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumHorizontal($spid,$_from) {
		global $lang;
		
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();
		$_clmn_cnt = count($spremenljivka['options']);

		# pri razvrščanju dodamo dva polja za povprečje in odklon
		$additional_field = false;
		$add_fld = 0;
		
		if ($spremenljivka['tip'] == 17 || $spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3 || ($spremenljivka['tip'] == 6 && $spremenljivka['skala'] != 1)) {
			$additional_field = true;
			$add_fld = 2;
		}

		# pri radiu in dropdown ne prikazujemo podvprašanj
		$_sub_question_col = 1;
		if ($spremenljivka['tip'] == 1 || $spremenljivka['tip'] == 3) {
			$_sub_question_col  = 0;
		}

		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);		
		
		//druga vrstica	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 750;
		$defw_part2 = 1800;
		$defw_part3 = 4600;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_answers']),20,'...') . $align . '\qc\cell';
		
		if ($additional_field){		
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_valid']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_num_units']),20,'...').'\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_povprecje']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_odklon']),20,'...').'\qc\cell';
		}
		else{
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_valid']),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_num_units']),20,'...').'\qc\cell';
		}	
		
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
					
		$_variables = $grid['variables'];
		
		//tretja vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 750;
		$defw_part2 = 1800;
		$defw_part3 = 4600;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '';
		$bold = '\b0';
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
			
		$defw_dynamic = round($defw_part3 / ($_clmn_cnt+1));	
		$count = 1;
		if (count($spremenljivka['options']) > 0) {
			foreach ( $spremenljivka['options'] as $key => $kategorija) {
				// misinge imamo zdruzene
				$_label =  $kategorija; 
				
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
				$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_label),20,'...').'\qc\cell';
				
				$count++;
			}
		}

		//suma
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_anl_suma1']),20,'...').'\qc\cell';
		
		if ($additional_field){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
		}
		else{
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';			
		}
		
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		//konec naslovnih vrstic
		

		#zlopamo skozi gride 
		$podtabela = 0;
		if (count($spremenljivka['grids']) > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$cssBack = "anl_bck_desc_2 ";
			# zloopamo skozi variable
			if (count($grid['variables']) > 0)
			foreach ($grid['variables'] AS $vid => $variable ) {
				$_sequence = $variable['sequence'];
				if ($variable['other'] != true) {

					$defw_full = 10300;
					$defw_part0 = 900;
					$defw_part = 750;
					$defw_part2 = 1800;
					$defw_part3 = 4600;
					
					//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
					$borderB = '\clbrdrb\brdrs\brdrw10';
					$borderT = '';		
					$align = '';
					$bold = '';
					
					// Ce gre za dvojno tabelo naredimo vrstico s naslovom podtabele
					if($spremenljivka['tip'] == 6 && $spremenljivka['enota'] == 3){
						
						// Če začnemo z drugo podtabelo izpišemo vrstico z naslovom
						if($podtabela != $grid['part']){
							
							$subtitle = $spremenljivka['double'][$grid['part']]['subtitle'];
							$subtitle = $subtitle == '' ? $lang['srv_grid_subtitle_def'].' '.$grid['part'] : $subtitle;
																			
							$tableHeader = '\trowd\trql\trrh400';
									
							$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
							$tableEnd = '\pard\intbl '.$this->enkaEncode($subtitle) . $align . '\qc\cell';
							
							$tableEnd .= '\pard\intbl\row';
							
							$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
							
							$podtabela = $grid['part'];
						}
					}
					
					$tableHeader = '\trowd\trql\trrh400';
					
					$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
					$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($variable['variable']),20,'...') . $align . '\qc\cell';
					
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
					$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($variable['naslov']),20,'...') . $align . '\cell';
						
					
					# za odklon in povprečje				
					$sum_xi_fi=0;
					$N = 0;
					$div=0;
					
					$defw_dynamic = round($defw_part3 / ($_clmn_cnt+1));	
					$count = 1;
					if (count($spremenljivka['options']) > 0) {	
						foreach ( $spremenljivka['options'] as $key => $kategorija) {
							if ($additional_field) { # za odklon in povprečje
								$xi = $key;
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'];
								$sum_xi_fi += $xi * $fi ;
								$N += $fi;
							}
							
							$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0 ) ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] * 100 / SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0;  
							
							$text = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$key]['cnt'] .' ('. SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%') .')';
							
							$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
							$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($text),20,'...').'\qc\cell';
							
							$count++;
						}
					}
					//suma
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3);	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode((int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'].' ('.SurveyAnalysis::formatNumber(100,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%').')'),20,'...').'\qc\cell';
					
					if ($additional_field){				
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']),20,'...').'\qc\cell';
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']),20,'...').'\qc\cell';

						# odklon
						$avg = ($N > 0) ? $sum_xi_fi / $N : 0;
						#standardna diviacija
						$div = 0;
						$sum_pow_xi_fi_avg  = 0;
						if (count($spremenljivka['options']) > 0) {
							foreach ( $spremenljivka['options'] as $xi => $kategorija) {
								$fi = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'][$xi]['cnt'];
								$sum_pow_xi_fi_avg += pow(($xi - $avg),2) * $fi;
							}
						}
						$div = (($N -1) > 0) ? sqrt($sum_pow_xi_fi_avg / ($N -1)) : 0;
						
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 3 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::formatNumber($avg,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::formatNumber($div,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';
					}
					else{
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']),20,'...').'\qc\cell';
						$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 4 * $defw_part + $defw_part2 + $defw_part3);	
						$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']),20,'...').'\qc\cell';
					}
					
					$tableEnd .= '\pard\intbl\row';
					
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);								
					
				} else {
					# immamo polje drugo
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}	
		}
		$this->tableEnd();
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}			
		}
	}
	
	/** Izriše tekstovne odgovore v vertikalni obliki
	 * 
	 * @param unknown_type $spid
	 */
	function sumTextVertical($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false
		$inline_legenda = (SurveyAnalysis::$_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'],array(1,8) ) ) ? false: true;
		$options=array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'textAnswerExceed' => false);
			
		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica	
		$this->tableHeader();		
		//konec naslovnih vrstic
		
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0)
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			$_variables_count = count($grid['variables']); 
			if ($_variables_count > 0)
			foreach ($grid['variables'] AS $vid => $variable ){
				$_sequence = $variable['sequence'];	# id kolone z podatki
				if ($variable['other'] != true) {
					# dodamo dodatne vrstice z labelami grida
					if ($_variables_count > 1) {
						self::outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$options);
					}
					
					$maxAnswer = (SurveyDataSettingProfiles :: getSetting('numOpenAnswers') > 0) ? SurveyDataSettingProfiles :: getSetting('numOpenAnswers') : 30;
					$counter = 0;
					$_kumulativa = 0;
					//SurveyAnalysis::$_FREQUENCYS[$_sequence]
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
							if (/*$vAnswer['cnt'] > 0 &&*/ $counter < $maxAnswer) { # izpisujemo samo tiste ki nisno 0
								# ali prikažemo vse odgovore ali pa samo toliko koliko je nastavljeno v TEXT_ANSWER_LIMIT 
								$textAnswerExceed = ($counter >= TEXT_ANSWER_LIMIT && count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) > TEXT_ANSWER_LIMIT+2) ? true : false; # ali začnemo skrivati tekstovne odgovore
								$options['isTextAnswer']=true;
								$options['textAnswerExceed'] = $textAnswerExceed;
								$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
						foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
							if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
								$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,$options);
							}
						}
						# izpišemo sumo veljavnih
						$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$options);
					}
					#izpišemo še skupno sumo
					$counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
				} else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
			}
		}
		$this->tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}		
		}
	}
	
	/** Izriše tekstovne odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiText($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# pogledamo koliko je max št odgovorov pri posameznem podvprašanju
/*		$_max_answers = array();
		$_max_answers_cnt = 0;
		foreach ($spremenljivka['grids'] AS $gid => $grid) {
			
			$_variables_count = count($grid['variables']);				
			if ($_variables_count > 0) {
				foreach ($grid['variables'] AS $vid => $variable ){
					$_sequence = $variable['sequence'];	# id kolone z podatki
					$_max_answers[$gid][$vid] = count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']);
					$_max_answers_cnt = max( $_max_answers_cnt, count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']) ); 
				}
			}
		}
*/		
		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();
		//$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2600;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_arguments']),20,'...').'\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		//konec naslovnih vrstic

		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2600;
			$defw_part3 = 6800;
			
			//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
				
			$var_count = count($_row['variables']);
			$defw_dynamic = round($defw_part3 / $var_count);	
			$count = 1;
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
				
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_col['naslov']),20,'...').'\qc\cell';
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}			
							
				$count++;
			}
					
			$tableEnd .= '\pard\intbl\row';			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
								
			
			//podatkovne vrstice
			$last = 0;
			$count = 1;
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$defw_full = 10300;
				$defw_part0 = 900;
				$defw_part2 = 2600;
				$defw_part3 = 6800;
				
				//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
				$borderB = '\clbrdrb\brdrs\brdrw10';
				$borderT = '';		
				$align = '';
				$bold = '';
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
				$tableEnd = '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($grid['variable']),20,'...') . $align . '\qc\cell';
				
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
				$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($grid['naslov']),20,'...') . $align . '\cell';
								
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					
					$defw_dynamic = round($defw_part3 / $_variables_count);	
					$count = 1;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$index=0;
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) { 
								$text = '';
								foreach ($_valid_answers AS $answer) {
									$index++;
									
									$_ans = $answer[$_sequence];

									if ($_ans != null && $_ans != '') {
										$text .=  $_ans.', ';
									}
								}
								
								$text = substr($text, 0, -2);
								$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
								$tableEnd .= '\pard\intbl'.$bold.' '.$this->enkaEncode($text).'\qc\cell';
							}
							else {
								
								$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
								$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp;'),20,'...').'\qc\cell';
							}
							
							$count++;
						}
					}
					$last = $_max_i;
				}
				
				$tableEnd .= '\pard\intbl\row';			
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);			
			}
			
			
			
			
		}
		$this->tableEnd();

		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}	
		}
	}
        
        /** Izriše lokacijske odgovore kot tabelo z navedbami
	 * 
	 * @param unknown_type $spid
	 */
	function sumLokacija($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
                $enota = $spremenljivka['enota'];
		
		# dodamo opcijo kje izrisujemo legendo
		# če je besedilo * in je samo ena kategorija je inline legenda false

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		# koliko zapisov prikažemo naenkrat
		$num_show_records = SurveyAnalysis::getNumRecords();
		//$num_show_records = $_max_answers_cnt <= (int)$num_show_records ? $_max_answers_cnt : $num_show_records;

		$_answers = SurveyAnalysis::getAnswers($spremenljivka,$num_show_records);
		
		$_all_valid_answers_cnt = $_answers['validCnt'];
		$_valid_answers = $_answers['valid'];
		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);	
		
		//druga vrstica		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2600;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		//konec naslovnih vrstic

		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2600;
			$defw_part3 = 6800;
			
			//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
				
			$var_count = count($_row['variables']);
			$defw_dynamic = round($defw_part3 / $var_count);	
			$count = 1;
			foreach ($_row['variables'] AS $rid => $_col ){
				$_sequence = $_col['sequence'];	# id kolone z podatki
				if ($_col['other'] != true) {
				
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_col['naslov']),20,'...').'\qc\cell';
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}			
							
				$count++;
			}
					
			$tableEnd .= '\pard\intbl\row';			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
								
			
			//podatkovne vrstice
			$last = 0;
			$count = 1;
			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$defw_full = 10300;
				$defw_part0 = 900;
				$defw_part2 = 2600;
				$defw_part3 = 6800;
				
				//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
				$borderB = '\clbrdrb\brdrs\brdrw10';
				$borderT = '';		
				$align = '';
				$bold = '';
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
				$tableEnd = '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($grid['variable']),20,'...') . $align . '\qc\cell';
								
				if ($_variables_count > 0) {
					# preštejemo max vrstic na grupo
					$_max_i = 0;
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						$_max_i = max($_max_i,min($num_show_records,SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt']));
					}
					
					# za barvanje
					$last = ($last & 1) ? 0 : 1 ;
					
					$defw_dynamic = round($defw_part3 / $_variables_count);	
					$count = 1;
                                        $answers = array();
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$index=0;
							# odvisno ali imamo odgovor
							if (count($_valid_answers) > 0) { 
								$text = '(';
								foreach ($_valid_answers AS $answer) {									
									$_ans = $answer[$_sequence];
                                                                        if($enota != 3)
                                                                            $_ans = str_replace("<br>","), (",$_ans);

									if ($_ans != null && $_ans != '') {
                                                                                if($enota == 3)
                                                                                    $text .=  $_ans."), (";
                                                                                else{
                                                                                    $answers[$count-1][$index]='('.$this->encodeText($_ans).')';	
                                                                                }
									}
                                                                    $index++;
								}
								
                                                                if($enota == 3){
                                                                    $text = substr($text, 0, -3);
                                                                    $table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
                                                                    $tableEnd .= '\pard\intbl'.$bold.' '.$this->enkaEncode($text).'\qc\cell';
                                                                }
							}
							else {
								
								$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * $count) );	
								$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp;'),20,'...').'\qc\cell';
							}
							
							$count++;
						}
					}                                        
					$last = $_max_i;
				}
				//ce je choose, izrisi, tako kot pri multitext, vse v eno
                                if($enota == 3){
                                    $tableEnd .= '\pard\intbl\row';			
                                    $this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
                                }
                                else{
                                    //za vsako vrstico
                                    for($i=0; $i<sizeof($answers[0]); $i++){
                                        $table1 = $table; //prva prazna
                                        $tableEnd1 = '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';//prva prazna
                                        //za vsak stolpec
                                        for($j=0; $j<$count-1; $j++){
                                            $table1 .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($defw_dynamic * ($j+1)) );	
                                            $tableEnd1 .= '\pard\intbl'.$bold.' '.$this->enkaEncode($answers[$j][$i]).'\qc\cell';
                                        }
                                        $tableEnd1 .= '\pard\intbl\row';
                                        $this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table1.$tableEnd1);
                                    }
                                }
			}
		}
		$this->tableEnd();
	}
	
	/** Izriše sumarnik v horizontalni obliki za multi checbox
	 * 
	 * @param unknown_type $spid - spremenljivka ID
	 */
	function sumMultiHorizontalCheckbox($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];
		$_answersOther = array();

		# ugotovimo koliko imamo kolon
		$gid=0;
		$_clmn_cnt = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_vars']-SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['cnt_other'];
		# tekst vprašanja
		
	
		/////////////////PRVA TABELA////////////////
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2200;
		$defw_part3 = 5000;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_answers']),20,'...') . $align . '\qc\cell';
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_valid']),20,'...').'\qc\cell';
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_num_units']),20,'...').'\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		//tretja vrstica
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2200;
		$defw_part3 = 5000;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b0';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
		
		$defw_dynamic = round($defw_part3 / count($_variables) );
		$count = 1;			
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
				$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($variable['naslov'].' ('.$variable['gr_id']. ')'),20,'...').'\qc\cell';
			}
			$count++;
		}
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...').'\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		//podatkovne vrstice
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			$_cnt = 0;
			
			# vodoravna vrstice s podatki
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part = 1100;
			$defw_part2 = 2200;
			$defw_part3 = 5000;
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '\ql';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($grids['variable']),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($grids['naslov']),20,'...') . $align . '\cell';

			$_arguments = 0;
			$_max_appropriate = 0;
			$_max_cnt = 0;
			
			// prikaz frekvenc
			$defw_dynamic = round($defw_part3 / count($grids['variables']) );
			$count = 1;	
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;
				
				$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}
		
				if ($variable['other'] != true) {					
										
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;
					
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_cnt.' ('.SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'). ')'),20,'...').'\qc\cell';
				}
								
				$count++;
			}
			
			# veljavno 
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_max_cnt),20,'...').'\qc\cell';
			#ustrezno
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + 2 * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_max_appropriate),20,'...').'\qc\cell';
					
			$tableEnd .= '\pard\intbl\row';
			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		$this->tableEnd();	
		/////////////////KONEC PRVE TABELE////////////////
		
	
		
		////////////DRUGA TABELA///////////////////
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_arguments']),20,'...') . $align . '\qc\cell';
		
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		//tretja vrstica
		$_variables = SurveyAnalysis::$_HEADERS[$spid]['grids'][$gid]['variables'];
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b0';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . $align . '\cell';
		
		$defw_dynamic = round($defw_part3 / (count($_variables)+1) );
		$count = 1;			
		foreach ($_variables AS $vkey => $variable) {
			if ($variable['other'] != true) {
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
				$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($variable['naslov'].' ('.$variable['gr_id']. ')'),20,'...').'\qc\cell';
			}
			$count++;
		}
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_anl_suma1']),20,'...').'\qc\cell';
			
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		//vrstice s podatki
		foreach (SurveyAnalysis::$_HEADERS[$spid]['grids'] AS $gid => $grids) {
			$_cnt = 0;
			
			# vodoravna vrstice s podatki
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2200;
			$defw_part3 = 7200;
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '\ql';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($grids['variable']),20,'...') . $align . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($grids['naslov']),20,'...') . $align . '\cell';
			
			$_arguments = 0;
			$_max_appropriate = 0;
			$_max_cnt = 0;			
		
			// prikaz frekvenc
			foreach ($grids['variables'] AS $vkey => $variable) {
				$_sequence = $variable['sequence'];
				$_valid = SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'];
				$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
				$_arguments += $_cnt;
				
				$_max_appropriate = max($_max_appropriate, (int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt']);
				$_max_cnt = max ($_max_cnt, ((int)(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt']+(int)SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['0']['cnt'])));
				
				if ($variable['other'] == true) {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vkey,'sequence'=>$_sequence);
				}
		
				if ($variable['other'] != true) {
					$_percent = ($_valid > 0 ) ? $_cnt * 100 / $_valid : 0;  
				}
			}

			$defw_dynamic = round($defw_part3 / (count($_variables)+1) );
			$count = 1;				
			foreach ($grids['variables'] AS $vkey => $variable) {				
				if ($variable['other'] != true) {
					$_sequence = $variable['sequence'];
					$_cnt = SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid']['1']['cnt'];
					
					$_percent = ($_arguments > 0 ) ? $_cnt * 100 / $_arguments : 0;  
					
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_cnt.' ('.SurveyAnalysis::formatNumber($_percent,SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'),'%'). ')'),20,'...').'\qc\cell';

					$count++;
				}
			}
			

			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_full );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_arguments),20,'...').'\qc\cell';
				
			$tableEnd .= '\pard\intbl\row';
			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		}
		
		$this->tableEnd();
		///////////KONEC DRUGE TABELE//////////////
		
		
		# izpišemo še tekstovne odgovore za polja drugo
		if (count($_answersOther) > 0 && SurveyAnalysis::$_FILTRED_OTHER) { 
			foreach ($_answersOther AS $oAnswers) {
				self::outputOtherAnswers($oAnswers);
				$this->tableEnd();
			}			
		}
	}
	
	/** Izriše multi number odgovore. izpiše samo povprečja
	 * 
	 * @param unknown_type $spid
	 */
	function sumMultiNumber($spid,$_from) {
		global $lang;
		$spremenljivka = SurveyAnalysis::$_HEADERS[$spid];

		$_cols = $spremenljivka['cnt_all'] / $spremenljivka['cnt_grids'];

		
		//prva vrstica			
		$this->tableFirstLine($spremenljivka['variable'], $spremenljivka['naslov']);
		
		//druga vrstica
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '';		
		$align = '\ql';
		$bold = '\b';
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_opisne_subquestion']),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($lang['srv_analiza_sums_average']),20,'...') . '\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	
		
		$_answersOther = array();
		$_grids_count = count($spremenljivka['grids']);
		if ($_grids_count > 0) {
			# naslovna vrstica
			$_row = $spremenljivka['grids'][0];
			
			
			$defw_full = 10300;
			$defw_part0 = 900;
			$defw_part2 = 2200;
			$defw_part3 = 7200;
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '';		
			$align = '\ql';
			$bold = '\b0';
			
			$tableHeader = '\trowd\trql\trrh400';
					
			$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . '\qc\cell';
			
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode('&nbsp; '),20,'...') . '\cell';
					
			$defw_dynamic = round($defw_part3 / count($_row['variables']) );
			$count = 1;			
			foreach ( $_row['variables'] AS $rid => $_col ) {
				$_sequence = $_col['sequence'];	# id kolone z podatki
				
				if ($_col['other'] != true) {
					$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
					$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($_col['naslov']),20,'...').'\qc\cell';
				} 
				else {
					$_answersOther[] = array('spid'=>$spid,'gid'=>$gid,'vid'=>$vid,'sequence'=>$_sequence);
				}
				
				$count++;
			}
			
			$tableEnd .= '\pard\intbl\row';			
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);

			
			$last = 0;

			foreach ($spremenljivka['grids'] AS $gid => $grid) {
				$_variables_count = count($grid['variables']);				
				
				$defw_full = 10300;
				$defw_part0 = 900;
				$defw_part2 = 2200;
				$defw_part3 = 7200;
				
				$borderB = '\clbrdrb\brdrs\brdrw10';
				$borderT = '';		
				$align = '\ql';
				$bold = '\b0';
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$table = '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($grid['variable']),20,'...') . '\qc\cell';
				
				$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
				$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($grid['naslov']),20,'...') . '\cell';						
				
				if ($_variables_count > 0) {
					
					$defw_dynamic = round($defw_part3 / $_variables_count );
					$count = 1;	
					foreach ($grid['variables'] AS $vid => $variable ){
						$_sequence = $variable['sequence'];	# id kolone z podatki
						if ($variable['other'] != true) {
							# tabela z navedbami
							$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + ($count * $defw_dynamic) );	
							$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode(SurveyAnalysis::formatNumber(SurveyAnalysis::$_FREQUENCYS[$_sequence]['average'],SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_AVERAGE'),'')),20,'...').'\qc\cell';
						}
						$count++;
					}
					$tableEnd .= '\pard\intbl\row';			
					$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				}	
			}
		}
		$this->tableEnd();
	}
	
	
	
	function outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,&$_kumulativa,$_options=array()) {
		global $lang;
		
		$text = array();
		
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
		if ($options['textAnswerExceed'] == true) {
			if ($counter == TEXT_ANSWER_LIMIT ) {
				# link za več
				$show_more = '<div id="valid_row_togle_'.$_sequence.'" class="floatRight blue pointer" onclick="showHidenTextRow(\''.$_sequence.'\');return false;">(več...)</div>'.NEW_LINE;
			} elseif ($counter > TEXT_ANSWER_LIMIT ) {
				$hide_row = ' hidden';
				$_exceed = true;
			}			
		}
		
		
		//if ($counter < TEXT_MAX_ANSWER_LIMIT) {
	 		$text[] = '&nbsp; ';

			$addText = (($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' ('.$vAnswer['text'] .')' : '');
			$text[] = $this->encodeText($vkey.$addText);

			$text[] = $this->encodeText((int)$vAnswer['cnt']);
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_valid, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_kumulativa, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));

		/*} elseif ($counter == TEXT_MAX_ANSWER_LIMIT ) {
	 		echo '<tr id="'.$spid.'_'.$_sequence.'_'.$counter.'" name="valid_row_'.$_sequence.'">';
	 		echo '<td class="anl_bl anl_ac anl_br gray anl_dash_bt anl_dash_bb" colspan="'.(6+(int)SurveyAnalysis::$_HEADERS[$spid]['show_valid_percent']+((int)SurveyAnalysis::$_SHOW_LEGENDA*2)).'"> . . . Prikazujemo samo prvih '.TEXT_MAX_ANSWER_LIMIT.' veljavnih odgovorov!</td>';
			echo '</tr>';
		}*/
		
		self::tableRow($text);
		
		$counter++;
		return $counter;
	}
	
	function outputSumaValidAnswerVertical($counter,$_sequence,$spid,$_options=array()) {
		global $lang;
		
		$text = array();	
		
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

		$_brez_MV = ((int)SurveyAnalysis::$currentMissingProfile === 2) ? TRUE : FALSE;
		
		$_sufix = '';

		$text[] = $this->encodeText($lang['srv_anl_valid']);
		$text[] = $this->encodeText($lang['srv_anl_suma1']);
		
		$text[] = $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['validCnt'] : 0);
		
		$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		$text[] = $this->encodeText(SurveyAnalysis::formatNumber(100, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
		
		$text[] = '&nbsp; ';

		
		self::tableRow($text);
		
		$counter++;
		return $counter;		
	}
	
	function outputInvalidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_options=array()) {
		global $lang;	
		
		$text = array();
		
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}

		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
		$_invalid = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0 ) ? 100*$vAnswer['cnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
 
		$_Z_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 2) ? TRUE : FALSE;		
		if($_Z_MV){	
			$text[] = '&nbsp; ';
			
			$text[] = $this->encodeText($vkey.' (' . $vAnswer['text'].')');
			//echo '<div class="floatRight anl_detail_percent anl_w50 anl_ac anl_dash_bl">'.SurveyAnalysis::formatNumber($_invalid, NUM_DIGIT_PERCENT, '%').'</div>'.NEW_LINE;
			//echo '<div class="floatRight anl_detail_percent anl_w30 anl_ac">'.$vAnswer['cnt'].'</div>'.NEW_LINE;
			
			$text[] = $this->encodeText((int)$vAnswer['cnt']);

			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			
			$text[] = '&nbsp; ';
			$text[] = '&nbsp; ';
			
			$this->tableRow($text);
		}
		
		$counter++;
		return $counter;
	}
	
	function outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,$_options = array()) {
		global $lang;
			
		# opcije	
		$options = array(	'isTextAnswer' => false, 	# ali je tekstovni odgovor
							'isOtherAnswer' => false, 	# ali je odgovor Drugo
							'inline_legenda' => true, 	# ali je legenda inline ali v headerju
							'textAnswerExceed'=>false	# ali presegamo število tekstovnih odgovorov za prikaz
		);
		foreach ($_options as $_oKey => $_option) {
			$options[$_oKey] = $_option;
		}
		$cssBck = ' '.SurveyAnalysis::$cssColors['text_' . ($counter & 1)];
		$_percent = (SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] > 0 ) ? 100*SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] / SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0;

		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		if(!$_brez_MV){
			$text = array();
			
			$text[] = $this->encodeText($lang['srv_anl_missing']);	
			
			$text[] = $this->encodeText(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt']);			
			
			$answer['cnt'] =  SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] > 0  ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalidCnt'] : 0;
			$text[] = $this->encodeText((int)$answer['cnt']);
			
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber($_percent, SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '&nbsp; ';
			$text[] = '&nbsp; ';
			
			$this->tableRow($text);
		}
			
		$counter++;
		return $counter;
	}
	
	function outputSumaVertical($counter,$_sequence,$spid, $_options = array()) {
		global $lang;
		
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
		
		$_brez_MV = ((int)SurveyAnalysis::$missingProfileData['display_mv_type'] === 0) ? TRUE : FALSE;
		if(!$_brez_MV){	
			$params = array('borderB' => 1);
			$text = array();
		
			$text[] = '&nbsp; ';
			$text[] = $this->encodeText($lang['srv_anl_suma2']);
			$text[] = $this->encodeText((SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] ? SurveyAnalysis::$_FREQUENCYS[$_sequence]['allCnt'] : 0));	
			$text[] = $this->encodeText(SurveyAnalysis::formatNumber('100', SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_PERCENT'), '%'));
			$text[] = '&nbsp; ';	
			$text[] = '&nbsp; ';
			
			$this->tableRow($text, $params);		
		}		
	}

	function outputOtherAnswers($oAnswers) {
		global $lang;
		$spid = $oAnswers['spid'];
		$_variable = SurveyAnalysis::$_HEADERS[$spid]['grids'][$oAnswers['gid']]['variables'][$oAnswers['vid']];
		$_sequence = $_variable['sequence'];
		$_frekvence = SurveyAnalysis::$_FREQUENCYS[$_variable['sequence']];
		
		//prva vrstica			
		$this->tableFirstLine($_variable['variable'], SurveyAnalysis::$_HEADERS[$oAnswers['spid']]['variable'].' ('.$_variable['naslov'].' )');	
		
		//druga vrstica
		$this->tableHeader();
		// konec naslovne vrstice				

		$counter = 1;
		$_kumulativa = 0;
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['valid'] AS $vkey => $vAnswer) {
				if ($vAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputValidAnswerVertical($counter,$vkey,$vAnswer,$_sequence,$spid,$_kumulativa,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaValidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		if (count(SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'])> 0 ) {
			foreach (SurveyAnalysis::$_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
				if ($iAnswer['cnt'] > 0 ) { # izpisujemo samo tiste ki nisno 0
					$counter = self::outputInvalidAnswerVertical($counter,$ikey,$iAnswer,$_sequence,$spid,array('isOtherAnswer'=>true));
				}
			}
			# izpišemo sumo veljavnih
			$counter = self::outputSumaInvalidAnswerVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
		}
		#izpišemo še skupno sumo
		$counter = self::outputSumaVertical($counter,$_sequence,$spid,array('isOtherAnswer'=>true));
	}
	
	/** za multi grid tekstovne vrstice doda vrstico z labeliami grida
	 * 
	 * @param $gkey
	 * @param $gAnswer
	 * @param $spid
	 * @param $_options
	 */
	function outputGridLabelVertical($gid,$grid,$vid,$variable,$spid,$_options=array()) {
 		
		$text = array();
					
		$text[] = $this->encodeText($variable['variable']);
		$text[] = $this->encodeText(($grid['naslov'] != '' ? $grid['naslov']. '&nbsp;-&nbsp;' : '').$variable['naslov']);
		
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		$text[] = '&nbsp; ';
		
		$this->tableRow($text);
		
		$counter++;
		return $counter;	
	}
	
	
	
	
	function createFrontPage(){
		global $lang;
		
		$this->rtf->new_line(10);
		$this->rtf->TextCell($this->encodeText( SurveyInfo::getInstance()->getSurveyTitle()), array('width' => 9500, 'height' => 3,
		 'align' => 'center', 'valign' => 'middle' , 'border' => array('top','bottom', 'left','right'),
		 'colorF' => "0", 'colorB' => "0" ) );
		$this->rtf->new_line(3);
		// dodamo info:
		$this->rtf->TextCell("", array('width' => 9500, 'height' => 1,
		 'align' => 'left', 'valign' => 'bottom' , 'border' => array('bottom'),'colorF' => "0" ) );

		$infoTable = array();

		$imenaTable = array();
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim() )
			$imenaTable[] = array($lang['export_firstpage_shortname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), "");
		if ( SurveyInfo::getInstance()->getSurveyTitle() != SurveyInfo::getInstance()->getSurveyAkronim() )
			$imenaTable[] = array($lang['export_firstpage_longname'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyTitle()), "");
			
		$imenaTable[] = array($lang['export_firstpage_qcount'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyQuestionCount()), "" );
		$imenaTable[] = array($lang['export_firstpage_active_from'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyStartsDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_active_until'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyExpireDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_author'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInsertName()),"" );
		$imenaTable[] = array($lang['export_firstpage_edit'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyEditName()),"" );
		$imenaTable[] = array($lang['export_firstpage_date'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInsertDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_date'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyEditDate()),"" );
		$imenaTable[] = array($lang['export_firstpage_desc'].': '.$this->encodeText(SurveyInfo::getInstance()->getSurveyInfo()),"" );			
		$this->rtf->TableFromArray( array( 4600, 4600 ), $imenaTable);

		$this->rtf->new_page();
	}

	function enkaEncode($text){ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return strip_tags($text);
	}	
	
	function encodeText($text){ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function snippet($text,$length=64,$tail="...")	{
		/*$text = trim($text);
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
		}*/
		return $text;
	}

	function setGrupa($grupa) {$this->grupa = $grupa;}
	function getGrupa() {return $this->grupa;}
	function setUserId($usrId) {$this->usrId = $usrId;}
	function getUserId() {return ($this->usrId)?$this->usrId:false;}
	function setDisplayFrontPage($display) {$this->pi['displayFrontPage'] = $display;}
	function getDisplayFrontPage() {return ($this->pi['displayFrontPage'] == true || $this->pi['displayFrontPage'] == 1);}

	
	function tableFirstLine($field1, $field2){
		global $lang;
		
		$defw_full = 10300;
		$defw_part = 900;
		$defw_part2 = 9400;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';		
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		$this->rtf->MyRTF .= "{\par";
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($field1),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($field2),20,'...') . '\ql\cell';
					
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
	}
	
	function tableHeader(){	
		global $lang;
		
		$naslov = array();
		$naslov[] = '&nbsp; ';
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleAnswers']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleFrekvenca']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleOdstotek']);
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleVeljavni']);	
		$naslov[] = $this->encodeText($lang['srv_analiza_frekvence_titleKumulativa']);	
		
		$params = array('borderB' => 1, 'bold' => 'B', 'align2' => 'C');
		
		$this->tableRow($naslov, $params);	
	}
	
	function tableRow($arrayText, $arrayParams=0){
		
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1300;
		$defw_part2 = 4200;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align2 = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') . $align2 . '\cell';
			
		for($i=0; $i<4; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
		
	function tableRowVerticalCheckbox($arrayText, $arrayParams=0){
	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1000;
		$defw_part2 = 2400;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
			
		for($i=0; $i<7; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}

	function tableRowNumberVertical($arrayText, $arrayParams=0){
	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2800;
		
		//$borderB = ($arrayParams['borderB'] == 1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT'] == 1 ? '\clbrdrt\brdrs\brdrw10' : '');
		$borderS = '\clbrdrl\brdrs\brdrw10';
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		if($arrayText[1] == '&nbsp; '){
			$borderB = '';
			$borderT = '';
			$borderS = '';
		}
			
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT. $borderS . $borderB . $borderS. '\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
			
		for($i=0; $i<6; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}

	function tableRowHorizontal($arrayText, $arrayParams=0){
			
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part = 1100;
		$defw_part2 = 2200;
		$defw_part3 = 3900;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[2]),20,'...') . $align . '\qc\cell';
			
		for($i=0; $i<3; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + ($i+1) * $defw_part + $defw_part2 + $defw_part3);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+3]),20,'...').'\qc\cell';
		}
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}

	function tableRowMultiText($arrayText, $arrayParams=0){
	
		$defw_full = 10300;
		$defw_part0 = 900;
		$defw_part2 = 2200;
		$defw_part3 = 7200;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
				
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . $align . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') . $align . '\cell';
			
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part0 + $defw_part2 + $defw_part3 );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[2]),20,'...') . $align . '\qc\cell';
				
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
	}
		
	
	function tableEnd(){
	
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}	
	
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
		$result = number_format($result, $digit, '.', ',') . $sufix;

		return $result;
	}
	
}


?>
