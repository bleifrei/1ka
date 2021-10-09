<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	require_once('../exportclases/class.enka.rtf.php');

	define("FNT_TIMES", "Times New Roman", true);
	define("FNT_ARIAL", "Arial", true);

	define("FNT_MAIN_TEXT", FNT_TIMES, true);
	define("FNT_QUESTION_TEXT", FNT_TIMES, true);
	define("FNT_HEADER_TEXT", FNT_TIMES, true);

	define("FNT_MAIN_SIZE", 12, true);
	define("FNT_QUESTION_SIZE", 10, true);
	define("FNT_HEADER_SIZE", 10, true);
	
	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge


class RtfIzvozList {

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

    
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null, $sprID = null)
	{
		global $site_path;
		global $global_user_id;

		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
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
			
			// Nastavimo da izpisujemo samo prvih 5 spremenljivk
            $_GET['spr_limit'] = 5;
            
			// Nastavimo da nikoli ne izpisemo vabila
            $_GET['email'] = 0;
            
			SurveyDataDisplay::Init($this->anketa['id']);			
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
		
		$this->rtf->draw_title($lang['export_list']);
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
				
		$this->displayTable();								
	} 
	
	
	function  displayTable(){
		global $site_path;
		global $lang;

		
		$folder = $site_path . EXPORT_FOLDER.'/';

		//polovimo podatke o nastavitvah trenutnega profila (missingi..)
		SurveyAnalysis::$missingProfileData = SurveyMissingProfiles::getProfile(SurveyAnalysis::$currentMissingProfile);

		#preberemo HEADERS iz datoteke
		SurveyAnalysis::$_HEADERS = unserialize(file_get_contents($this->headFileName));
		
		#odstranimo sistemske variable
		SurveyAnalysis::removeSystemVariables();
		
		SurveyDataDisplay::$_VARS[VAR_DATA] = 1;
		SurveyDataDisplay::$_VARS[VAR_SPR_LIMIT] = 5;
		SurveyDataDisplay::$_VARS[VAR_META] = 0;
		SurveyDataDisplay::$_VARS[VAR_EMAIL] = 0;
		SurveyDataDisplay::$_VARS[VAR_RELEVANCE] = 0;
		SurveyDataDisplay::$_VARS[VAR_EDIT] = 0;
		SurveyDataDisplay::$_VARS[VAR_PRINT] = 0;
		SurveyDataDisplay::$_VARS[VAR_MONITORING] = 0;

		if(SurveyDataDisplay::$_VARS['view_date'])
			SurveyDataDisplay::$_VARS[VAR_SPR_LIMIT]++;
		
		# ponastavimo nastavitve- filter
		SurveyDataDisplay::setUpFilter();
		
		
		// Prestejemo stevilo stolpcev za vsako spremenljivko
		$spr_cont = 0;
		$rowArray = array();
		$row_count = 0;
		// visine naslovnih vrstic
		$first_height = 0;
		$second_height = 0;
		$third_height = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$row_count ++;
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if($spr_cont < 5) {
												
						$rowArray[$spr_cont]['cnt_grd'] = count($spremenljivka['grids']);		
						$rowArray[$spr_cont]['cnt_var'] = count($spremenljivka['grids'][0]['variables']);
						
						$row_count += count($spremenljivka['grids'][0]['variables']) * count($spremenljivka['grids']);
					}
					$spr_cont++;
				}
			}
		}

		$this->rtf->MyRTF .= "{\par";
		
		$max_width = 10300;
		$single_width = floor($max_width / $row_count);
		$single_width = ($single_width < 200) ? 200 : $single_width;

		
		// PRVA VRSTICA (naslovi spremenljivk)	
		$tableHeader = '\trowd\trql\trrh400';	
		$table = '';
		$tableEnd = '';
		$spr_cont = 0;
		$width = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$width += $single_width;
						
			$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
			$tableEnd .= '\pard\intbl\b  '.$this->snippet($this->enkaEncode($lang['srv_data_date']),20,'...') . '\ql\cell';	
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid]))) {
	
					// 	prikazemo samo prvih 5 spremenljivk
					if ($spr_cont < 5) {						
						$width += $single_width * $rowArray[$spr_cont]['cnt_var'] * $rowArray[$spr_cont]['cnt_grd'];
						
						$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
						$tableEnd .= '\pard\intbl\b  '.$this->snippet($this->enkaEncode($spremenljivka['naslov']),20,'...') . '\ql\cell';	
					}
					$spr_cont++;
				}
			}
		}
		$tableEnd .= '\pard\intbl\row';
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		// DRUGA VRSTICA (imena gridov)
		$tableHeader = '\trowd\trql\trrh400';	
		$table = '';
		$tableEnd = '';
		$spr_cont = 0;
		$width = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$width += $single_width;
						
			$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
			$tableEnd .= '\pard\intbl\b  '.$this->snippet($this->enkaEncode($lang['srv_data_date']),20,'...') . '\ql\cell';	
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if ($spr_cont < 5) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							$width += $single_width * $rowArray[$spr_cont]['cnt_var'];
							
							$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
							$tableEnd .= '\pard\intbl\b '.$this->snippet($this->enkaEncode($grid['naslov']),20,'...') . '\qc\cell';	
						}
					}
					$spr_cont++;
				}
			}
		}
		$tableEnd .= '\pard\intbl\row';
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);

		
		// TRETJA VRSTICA (imena variabel)
		$tableHeader = '\trowd\trql\trrh400';	
		$table = '';
		$tableEnd = '';
		$spr_cont = 0;
		$width = 0;
		if(SurveyDataDisplay::$_VARS['view_date']){		
			$width += $single_width;
						
			$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
			$tableEnd .= '\pard\intbl\b  '.$this->snippet($this->enkaEncode($lang['srv_data_date']),20,'...') . '\ql\cell';	
		}
		foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
			# preverjamo ali je meta
			if ($spremenljivka['tip'] != 'm' && in_array($spremenljivka['tip'], SurveyAnalysis::$_FILTRED_TYPES)){
				# ali imamo sfiltrirano spremenljivko
				if ($vars_count == 0 || ($vars_count > 0 && isset(SurveyAnalysis::$_FILTRED_VARIABLES[$spid])) && count($spremenljivka['grids']) > 0) {
				
					// 	prikazemo samo prvih 5 spremenljivk
					if($spr_cont < 5) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							if (count ($grid['variables']) > 0) {
								foreach ($grid['variables'] AS $vid => $variable ){
									
									$text = $variable['naslov'];		
									if ($variable['other'] == 1)
										$text .= '&nbsp;(text)';
										
									$width += $single_width;
									
									$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
									$tableEnd .= '\pard\intbl\b '.$this->snippet($this->enkaEncode($text),20,'...') . '\qc\cell';	
								}
							}
						}
					}
					$spr_cont++;
				}
			}
		}
		$tableEnd .= '\pard\intbl\row';
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
				
		
		// Nastavimo stevilo izpisov - prikazemo vse
		$_REC_LIMIT = '';
		//$_REC_LIMIT = ' NR==1,NR==50';			
						
		$_command = '';
		#preberemo podatke
		// polovimo vrstice z statusom 5,6 in jih damo v začasno datoteko
		if (IS_WINDOWS) {
			$_command = 'gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.SurveyDataDisplay::$_CURRENT_STATUS_FILTER.' { print $0 }" '.$this->dataFileName;
		}
		else {
			$_command = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.SurveyDataDisplay::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.$this->dataFileName;
		}

		// paginacija po stolpcih (spremenljivkah)
		if (IS_WINDOWS) {
			$_command .= ' | cut -d "|" -f 1,'.SurveyDataDisplay::$_VARIABLE_FILTER;
		} else {
			$_command .= ' | cut -d \'|\' -f 1,'.SurveyDataDisplay::$_VARIABLE_FILTER;
		}

		if ($_REC_LIMIT != '') {
			#paginating
			if (IS_WINDOWS) {
				$_command .= ' | awk '.$_REC_LIMIT;
			} else {
				$_command .= ' | awk '.$_REC_LIMIT;
			}
		} else {
			#$file_sufix = 'filtred_spr_pagination';
		}

		// zamenjamo | z </td><td> - NI POTREBNO
		if (IS_WINDOWS) {
			//$_command .= ' | sed "s*'.STR_DLMT.'*'.STR_LESS_THEN.'/td'.STR_GREATER_THEN.STR_LESS_THEN.'td'.STR_GREATER_THEN.'*g" >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
			$_command .= ' >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;
		} 
		else {
			//$_command .= ' | sed \'s*'.STR_DLMT.'*</td><td>*g\' >> '
			//.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;	
			$_command .= ' >> '.$folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT;	
		}

		if (IS_WINDOWS) {
			# ker so na WINsih težave z sortom, ga damo v bat fajl in izvedemo :D
			$file_handler = fopen($folder.'cmd_'.$this->anketa['id'].'_to_run.bat',"w");
			fwrite($file_handler,$_command);
			fclose($file_handler);
			$out_command = shell_exec($folder.'cmd_'.$this->anketa['id'].'_to_run.bat');
			unlink($folder.'cmd_'.$this->anketa['id'].'_to_run.bat');
		} else {
			$out_command = shell_exec($_command);
		}

		if (file_exists($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT)) {
			$f = fopen ($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT, 'r');

			while ($line = fgets ($f)) {
				
				
		
				$dataArray = array();
				$dataArray = explode('|', $line);
				
				// Ne upostevamo prve vrednosti (ID)
				array_shift($dataArray);

				$tableHeader = '\trowd\trql\trrh400';	
				$table = '';
				$tableEnd = '';
				$width = 0;
				foreach($dataArray as $key => $val){
					
					$break = ($spr_cont == 4 && $gid == $rowArray[$spr_cont]['cnt_grd']-1 && $vid == $rowArray[$spr_cont]['cnt_var']-1) ? 1 : 0;
					$width += $single_width;
					
					$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrb\brdrs\brdrw10\clbrdrr\brdrs\brdrw10\cellx'.( $width );	
					$tableEnd .= '\pard\intbl\b0 '.$this->snippet($this->enkaEncode($val),20,'...') . '\qc\cell';	
				}
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			}
		}
			
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
		
		if ($f) {
			fclose($f);
		}
		if (file_exists($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT)) {
			unlink($folder.'tmp_export_'.$this->anketa['id'].'_data'.TMP_EXT);
		}	
	}
	
	
	function createFrontPage()
	{
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

	function enkaEncode($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return strip_tags($text);
	}	
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
		return strip_tags($text);
	}

	function snippet($text,$length=64,$tail="...")
	{
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
		$defw_part = 1300;
		$defw_part2 = 9000;
		
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';		
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		$this->rtf->MyRTF .= "{\par";
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($field1),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($field2),20,'...') . '\cell';
					
		$tableEnd .= '\pard\intbl\row';
		
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);	
	}
		
	function tableRow($arrayText, $arrayParams=array()){
		
		$defw_full = 10300;
		$defw_part = 1300;
		$defw_part2 = 3800;
		
		//$borderB = ($arrayParams['borderB']==1 ? '\clbrdrb\brdrs\brdrw10' : '');
		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = ($arrayParams['borderT']==1 ? '\clbrdrt\brdrs\brdrw10' : '');		
		$align = ($arrayParams['align2']=='C' ? '\qc' : '\ql');
		$bold = ($arrayParams['bold']=='B' ? '\b' : '\b0');
		
		$tableHeader = '\trowd\trql\trrh400';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part );	
		$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[0]),20,'...') . '\qc\cell';
		
		$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( $defw_part + $defw_part2 );	
		$tableEnd .= '\pard\intbl'.$bold.'   '.$this->snippet($this->enkaEncode($arrayText[1]),20,'...') .$align.'\cell';
			
		for($i=0; $i<4; $i++){
			$table .= '\clvertalc'.$borderT.'\clbrdrl\brdrs\brdrw10' . $borderB . '\clbrdrr\brdrs\brdrw10\cellx'.( ($i+2) * $defw_part + $defw_part2);	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->snippet($this->enkaEncode($arrayText[$i+2]),20,'...').'\qc\cell';
		}		
		
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
