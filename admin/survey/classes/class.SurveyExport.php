<?php
/**
 * Created on Jun.2010
 *
 * @author: Gorazd Vesleič
 *
 * @desc: za izvoze
 *
 * funkcije:
 * 		- Init() - inicializacija
 *
 */

DEFINE (NEW_LINE, "\n");
DEFINE (TMP_EXT, '.tmp');
DEFINE (STR_DLMT, '|');
DEFINE (DAT_EXT, '.dat');

define("EXPORT_FOLDER", "admin/survey/SurveyData");

class SurveyExport
{
	private $sid = null; // id ankete
	private $folder = '';							# pot do folderja

	private $inited = false; 	# ali smo razred inicializirali
	private $SMV = false; 		# manjkajoče vrednosti
	
	private $survey = null;		# podatki ankete
	private $db_table = '';		# ali se uporablja aktivna tabela
	
	private $_CURRENT_STATUS_FILTER = ''; 	# filter po statusih, privzeto izvažamo 6 in 5
	public $_FILTRED_VARIABLES = array(); 			# filter po spremenljivkah
	
	# ali obstaja datoteka z podatki in ali je zadnja verzija
	private $_fileStatus = FILE_STATUS_NO_DATA;	# (FILE_STATUS_OK,FILE_STATUS_OLD,FILE_STATUS_NO_FILE,FILE_STATUS_NO_DATA)   
	private $_dataFileName = null;				# ime obstoječe datoteke na FS.   
	private $_headFileName = null;				# ime obstoječe datoteke na FS.   

	private $exportSettings = array();
	private $_EXPORT_FULL_META = false;			# ali izvažamo polne meta podatke   
	private $_EXPORT_HIDDEN_SYSTEM = false;		# ali izvažamo sistemske podatke (email, telefon)   
	private $_EXPORT_ONLY_DATA = false;			# ali izvažamo samo podatke brez parapodatkov   

	private $_EXPORT_SPSS_DATA = false;			# ali izvažamo podatke za spsss   
	private $_EXPORT_SPSS_HEAD = true;			# ali izvažamo header za spss   
	private $_EXPORT_EXCEL_HEAD = true;			# ali izvažamo header za excel   
	private $_EXPORT_EXCEL_REPLACE = array();	# ali izvažamo zamenjave za excel   

	private $_SPECIAL_EXPORT = false;			# samo za posebne primere kadar "moramo" izpisovati skrite sistemske variable (preko get se pošlje special_export=true  
	
	private $_HEADERS = array();					# array z header podatki	
   
	private $_FIELDS_ARRAY = array();			# array kamor shranimo polja katera za katera pobiramo podatke
	
	private $_QUOTE = '"';						# Kateri zanko uporabimo za nize " ali '   

	private $_VARIABLE_FILTER = '';				# sed string array z prikazanimi variablami z upoštevanjem filtrov
		
	private $_SVP_PV = array();					# array z prikazanimi variablami z upoštevanjem filtrov
	
	/** Inicializacija
	 * 
	 * @param $sid
	 */
	public function Init($sid = null) {	
		global $admin_type, $lang, $site_path, $global_user_id;
		
		if ($sid == null) {
			die('Error! Missing survey Id');
		}
		
		$this->folder = $site_path . EXPORT_FOLDER.'/';
		
		# nastavimo id ankete
		$this->sid = $sid;

		if (IS_WINDOWS) {
			$this->_QUOTE = '"';
		} else {
			$this->_QUOTE = '\'';
		}

		# informacije ankete
		SurveyInfo::getInstance()->SurveyInit($this->sid);
		$this->survey = SurveyInfo::getInstance()->resetSurveyData();
		$this->survey = SurveyInfo::getInstance()->getSurveyRow();
		
		# aktivne tabele
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
		// Preverimo ce ima user dostop
		$d = new Dostop();
		if(!$d->checkDostop($this->sid)){
			die('Error! Access to survey '.$this->sid.' denied.');
		}
		
		# vsilimo podstran zaradi profila statusov
		$_POST['podstran'] = A_COLLECT_DATA_EXPORT;
		
		$this->_CURRENT_STATUS_FILTER = STATUS_FIELD.' ~ /6|5/'; 
		# za profile statusov
		SurveyStatusProfiles :: Init($this->sid);
		
		# za profile variabel
		SurveyVariablesProfiles :: Init($this->sid, $global_user_id);
		
		# za profil ifov
		SurveyConditionProfiles :: Init($this->sid, $global_user_id);
		
		# za profil časov
		SurveyTimeProfiles :: Init($this->sid, $global_user_id);
		
		
		$result = sisplet_query ("SELECT value FROM misc WHERE what='SurveyExport'");
		list ($SurveyExport) = mysqli_fetch_row ($result);
		$adminTypes = array(0=>$lang['forum_admin'],1=>$lang['forum_manager'],2=>$lang['forum_clan'],3=>$lang['forum_registered'] );

		if ($SurveyExport<$admin_type) {
			die ($lang['srv_export_no_access'].$adminTypes[$admin_type]);
		}
		# manjkajoče vrednosti
		$this->SMV = new SurveyMissingValues($this->sid);

		#preverimo datoteke
		self::checkFile();

		self::setUpFilter();
		
		self::getHeaderData();	# header podatke rabimo vedno
	}
	
	/** Naredimo izvoz
	 * 
	 */
	public function DoExport() {
		# v odvisnosti kaj eksportiramo

		switch ($_GET['m']) {
			case 'sav':
				self::exportSav();
			break;
            case 'spss':
				self::exportSpss();
			break;
			case 'excel':
				self::exportExcel();
			break;
			case 'excel_xls':
				self::exportExcelXls();
			break;
			case 'txt':
				self::exportText();
			break;
		}
	}
	
	/** Nastavimo filtre
	 * 
	 */
    private function setUpFilter() {

    	if ($this->_fileStatus >= 0) {

			$this->_HEADERS = unserialize(file_get_contents($this->_headFileName));
			
	    	$this->exportSettings = array();
	    	foreach ($_SESSION AS $pkey => $pvalue)
	    	{
		    	// if starts with export
		    	if (!strncmp($pkey, "export", strlen("export")))
		    	{
		    		#ali iz seje ali preko requesta 
		    		$this->exportSettings[ltrim($pkey, "{export}")] = $pvalue
		    		|| $_REQUEST[lcfirst($pvalue)];
		    	}
	    	}
	    	
	    	$this->_EXPORT_HIDDEN_SYSTEM = ($this->exportSettings['HiddenSystem']) ? true : false;
	    	$this->_EXPORT_HIDDEN_SYSTEM = ((int)$_REQUEST['hiddenSystem'] == 1 || $_REQUEST['hiddenSystem'] == 'true') ? true : $this->_EXPORT_HIDDEN_SYSTEM;
			
			$this->_EXPORT_FULL_META = ($this->exportSettings['FullMeta'] || !$this->_EXPORT_HIDDEN_SYSTEM) ? true : false;
			$this->_EXPORT_ONLY_DATA = ($this->exportSettings['OnlyData'] || !$this->_EXPORT_HIDDEN_SYSTEM) ? true : false;
			
			$this->_SPECIAL_EXPORT = ($_REQUEST['special_export'] == 'true') ? true : false;
			
	    	# filtriranje po statusih
	    	$this->_CURRENT_STATUS_FILTER = SurveyStatusProfiles :: getStatusAsAWKString();
		    
	    	# ali imamo filter na testne podatke
	    	if (isset($this->_HEADERS['testdata']['grids'][0]['variables'][0]['sequence']) && (int)$this->_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'] > 0) {
	    		$test_data_sequence = $this->_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'];
	    		$filter_testdata = SurveyStatusProfiles :: getStatusTestAsAWKString($test_data_sequence);
	    	}
	    	
			# filtriranje po časih
		    $_time_profile_awk = SurveyTimeProfiles :: getFilterForAWK($this->_HEADERS['unx_ins_date']['grids']['0']['variables']['0']['sequence']);
		    
			# ali imamo filter na uporabnost
			if (isset($this->_HEADERS['usability']['variables'][0]['sequence']) && (int)$this->_HEADERS['usability']['variables'][0]['sequence'] > 0) {
				$usability_data_sequence = $this->_HEADERS['usability']['variables'][0]['sequence'];
				$filter_usability = SurveyStatusProfiles :: getStatusUsableAsAWKString($usability_data_sequence);
			}
			
		    # dodamo še ife
		    SurveyConditionProfiles :: setHeader($this->_HEADERS);
		    $_condition_profile_AWK = SurveyConditionProfiles:: getAwkConditionString();
		    
		    if (($_condition_profile_AWK != "" && $_condition_profile_AWK != null )
			    || ($_time_profile_awk != "" && $_time_profile_awk != null)
			    || ($filter_testdata != null)
				|| ($filter_usability != null)) {
		    	$this->_CURRENT_STATUS_FILTER = '('.$this->_CURRENT_STATUS_FILTER;
		    	if ($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) {
		    		$this->_CURRENT_STATUS_FILTER .= '&&'.$_condition_profile_AWK;
		    	}
		    	if ($_time_profile_awk != "" && $_time_profile_awk != null) {
		    		$this->_CURRENT_STATUS_FILTER .= '&&'.$_time_profile_awk;
		    	}
		    	if ($filter_testdata != null ) {
		    		$this->_CURRENT_STATUS_FILTER .= '&&('.$filter_testdata.')';
		    	}
				if ($filter_usability != null ) {
		    		$this->_CURRENT_STATUS_FILTER .= '&&('.$filter_usability.')';
		    	}
		    	$this->_CURRENT_STATUS_FILTER .= ')';
		    }
    	
			
			# FILTRI VARIABEL - Katere variable ne izpisujemo
			$svp_pv = array();
			# ne prikazujemo user-idja
			$not_svp_pv['uid'] = 'uid';

			# ne prikazujemo recnumberja
			//$not_svp_pv['recnum'] = 'recnum';
			
			# ne prikazujemo meta podatkov
			if ($this->_EXPORT_FULL_META == false) {
				$not_svp_pv['meta'] = 'meta';
				$not_svp_pv['recnum'] = 'recnum';
			}

			# filtriranje po spremenljivkah
   			$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
   			$this->_FILTRED_VARIABLES = SurveyVariablesProfiles :: getProfileVariables(SurveyVariablesProfiles :: checkDefaultProfile($dvp)); 

			# skreiramo filter variabel za podatke				
			if (count($this->_HEADERS) > 0) {
				// zloopamo skozi spremenljivke in sestavimo filter po stolpcih
				$_tmp_filter =  array();
				foreach ($this->_HEADERS AS $spid => $spremenljivka) {
					# privzeto spremenljivke ne prikazujemo 
					$_can_show = false;
					$tip = $spremenljivka['tip'];
					# če spremenljivka ni v neprikazanih jo prikažemo 
					if (!in_array($spid, $not_svp_pv)) {
						# če imamo sistemski email ali telefon, ime, priimek (v header je nastavljno "hide_system" = 1)
						# potem v odvisnosti od nastavitve prikazujemo samo navadne podatke ali pa samo te sistemske, zaradizaščite podatkov
						if ($this->_EXPORT_HIDDEN_SYSTEM == true && $spremenljivka['hide_system'] == '1' || $this->_SPECIAL_EXPORT == true) {
							# prikazujemo sistemske, in spremenljivka je sistemska
							$_can_show = true;
						} else
						if ( $this->_EXPORT_HIDDEN_SYSTEM == false && $spremenljivka['hide_system'] !== '1' ) {
							# prikazujemo nesistemske, in spremenljivka ni sistemska
							$_can_show = true;
						}
					}
					
					if ($_can_show == true) {
						# če mamo filter po variablah ga upoštevamo
						if ( ( $tip == 'm' || $tip == 'sm' )
				 			|| ( count($this->_FILTRED_VARIABLES) == 0 || (count($this->_FILTRED_VARIABLES) > 0 && isset($this->_FILTRED_VARIABLES[$spid])) )
				 			|| ( $this->_EXPORT_HIDDEN_SYSTEM  == true )
				 			){
								
							$svp_pv[$spid] = $spid;
							if (count($spremenljivka['grids']) > 0 ) {
								foreach ($spremenljivka['grids'] AS $gid => $grid) {
									if (count ($grid['variables']) > 0) {
										foreach ($grid['variables'] AS $vid => $variable ){
											
											if (($tip !== 'sm' && $tip !== 'm') || $this->exportSettings['FullMeta'] > 0) {
												$_tmp_filter[]= $variable['sequence'];
											}
										}
									}
								}
							}
						
							$spr_cont++;
						}
					}
				}
			}


			# prilagodimo array profilov variabel
			$this->_SVP_PV = $svp_pv;
			
			if (count($_tmp_filter) > 0) {
				$this->_VARIABLE_FILTER = implode(',',$_tmp_filter);
			}
			
    	}
    	
	}
	
	/** polovimo array z header podatki
	 * 
	 */
	function getHeaderData() {
		if ($this->_headFileName != null && $this->_headFileName != '') {
			$this->_HEADERS = unserialize(file_get_contents($this->_headFileName));
		} else {
			echo 'Error! Empty file name!';
		}
	}
	
	/** naredimo izvoz za excel - xls
	 * 
	 */
	function exportExcelXls() {
		global $site_path;
		global $site_path;
	
		$folder = $site_path . EXPORT_FOLDER.'/';
		
		if ($this->_fileStatus >= 0 && $this->_dataFileName !== null && $this->_dataFileName !== '' && $this->_headFileName !== null && $this->_headFileName !== '') {

			#zapišemo v temp file
	
			$file_handler = fopen($this->_dataFileName.'.xls',"w");

			$output1 = '';
			$output2 = '';
			
			# naredimo header row
			foreach ($this->_HEADERS AS $spid => $spremenljivka) {
				if (isset($this->_SVP_PV[$spid])) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						foreach ($grid['variables'] AS $vid => $variable ){

							if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){
								$output1 .= '<td>'.strip_tags($variable['variable']).'</td>';
								$output2 .= '<td>'.strip_tags($variable['naslov']).'</td>';
							}
						}
					}
				}
			}
			fwrite($file_handler,$output1."\r\n");
			fwrite($file_handler,$output2."\r\n");
			fclose($file_handler);
			
			# sfiltriramo podatke
			exec ('awk -F'.$this->_QUOTE.STR_DLMT.$this->_QUOTE.' '.$this->_QUOTE.$this->_CURRENT_STATUS_FILTER.' {print $0}'.$this->_QUOTE.' '.$this->_dataFileName.' > '.$this->_dataFileName .'_data1'.TMP_EXT);
			if (IS_WINDOWS) {
				#filtri spremenljivk
				$cmdLn1_1 = 'cut -d "|" -f '.$this->_VARIABLE_FILTER.' '.$this->_dataFileName .'_data1'.TMP_EXT.' > '.$this->_dataFileName .'_data1_1'.TMP_EXT;
			} else {
				#filtri spremenljivk
				$cmdLn1_1 = 'cut -d \'|\' -f '.$this->_VARIABLE_FILTER.' '.$this->_dataFileName .'_data1'.TMP_EXT.' > '.$this->_dataFileName .'_data1_1'.TMP_EXT;
			}
			$out1_1 = shell_exec($cmdLn1_1);
			
			# zamenjamo | z </td><td>
			exec('sed '.$this->_QUOTE.'s/|/<\/td><td align="center">/g'.$this->_QUOTE.' '.$this->_dataFileName .'_data1_1'.TMP_EXT.' >> '.$this->_dataFileName.'.xls');
	
			$convertType = 1; // kateri tip konvertiranja uporabimo
			$convertTypes[1] = array('charSet'	=> "windows-1250",
									 'delimit'	=> ";",
									 'newLine'	=> "\n",
									 'BOMchar'	=> "\xEF\xBB\xBF");
					
			# izvedemo download
			if ($fd = fopen ($this->_dataFileName.'.xls', "r")) {
				// clean the output buffer
				ob_clean();
				
			    $fsize = filesize($this->_dataFileName.'.xls');
			    $path_parts = pathinfo($this->_dataFileName.'.xls');
			    $ext = strtolower($path_parts["extension"]);
				header('Content-type: application/vnd.ms-excel; charset='.$convertTypes[$convertType]['charSet']);
				header('Content-Disposition: attachment; filename="anketa'.$this->sid.'-'.date('Y-m-d').'.xls"');
			   # header('Content-length: '.$fsize);
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Cache-Control: private',false);
				header('Content-Transfer-Encoding:­ binary'); 
				ob_flush();
								
			    # dodami boomchar za utf-8
				echo $convertTypes[$convertType]['BOMchar'];
				
				// Izpisemo celo tabelo
				echo '<table border="1">'."\r\n";
				$cnt=0;
				while ($line = fgets ($fd)) {
					
					if($cnt > 1) echo '<tr><td align="center">';
					else print('</tr>');
					
					$line = str_replace(array("\r","\n"), array("",""), $line);
					print($line);
					
					if($cnt > 1) print('</td></tr>');
					else print('</tr>');
					
					print("\r\n");
					
					$cnt++;
				}
				echo '</table>';
			} 
			else {
				echo "Napaka";
			}
			
			fclose ($fd);
			
			#pobrišemo vse tmp datoteke
			foreach (glob($folder . 'export_data_'.$this->sid.'_*'.TMP_EXT) as $fileToDelete) {
				unlink($fileToDelete);
			}
			unlink ($this->_dataFileName.'.xls');

		} else {
			echo '';
		}

		exit;
	}
	
	/** naredimo izvoz za excel
	 * 
	 */
	function exportExcel() {
		global $site_path;
		$folder = $site_path . EXPORT_FOLDER.'/';
		session_start();
		
		if ($this->_fileStatus >= 0 && $this->_dataFileName !== null && $this->_headFileName !== null) {

			if ($_POST['export_delimit'] == 0) {
				$field_delimit = ';';
				$replace_what = $_POST['replace_what0'];
				$replace_with = $_POST['replace_with0'];
			} else {
				$field_delimit = ',';
				$replace_what = $_POST['replace_what1'];
				$replace_with = $_POST['replace_with1'];
			}
			
			#zapišemo v temp file
			$file_handler = fopen($this->_dataFileName.'.csv',"w");
		
			$output1 = '';
			$output2 = '';
		
	
			fclose($file_handler);

			
			# sfiltriramo podatke
			exec ('awk -F'.$this->_QUOTE.STR_DLMT.$this->_QUOTE.' '.$this->_QUOTE.$this->_CURRENT_STATUS_FILTER.' {print $0}'.$this->_QUOTE.' '.$this->_dataFileName.' > '.$this->_dataFileName .'_data1'.TMP_EXT);
			
			if (IS_WINDOWS) {
				#filtri spremenljivk
				$cmdLn1_1 = 'cut -d "|" -f '.$this->_VARIABLE_FILTER.' '.$this->_dataFileName .'_data1'.TMP_EXT.' > '.$this->_dataFileName .'_data1_1'.TMP_EXT;
			} else {
				#filtri spremenljivk
				$cmdLn1_1 = 'cut -d \'|\' -f '.$this->_VARIABLE_FILTER.' '.$this->_dataFileName .'_data1'.TMP_EXT.' > '.$this->_dataFileName .'_data1_1'.TMP_EXT;
			}
			$out1_1 = shell_exec($cmdLn1_1);
			
			# zamenjamo uporabniške  znake
			if (is_array($replace_what) && count($replace_what) > 0 && is_array($replace_with) && count($replace_with) > 0) {
				$_new_filename = '_data1_1';
				$cnt_replace = min(count($replace_what),count($replace_with));
				for ($i = 0; $i < $cnt_replace; $i++) {
					exec('sed '.$this->_QUOTE .'s/'.$replace_what[$i].'/'.$replace_with[$i].'/g'.$this->_QUOTE .' '.$this->_dataFileName .'_data1_'.($i+1).TMP_EXT.' > '.$this->_dataFileName .'_data1_'.($i+2).TMP_EXT);
					$_new_filename = '_data1_'.($i+2);
				}
				
			} else {
				$_new_filename = '_data1_1';
			}

			# zamenjamo | z ;
			exec('sed '.$this->_QUOTE.'s/|/\x22'.$field_delimit.'=\x22/g'.$this->_QUOTE.' '.$this->_dataFileName .$_new_filename.TMP_EXT.' >> '.$this->_dataFileName.'.csv');
			
			$convertType = 1; // kateri tip konvertiranja uporabimo
			$convertTypes[1] = array('charSet'	=> 'windows-1250',
							 'delimit'	=> ';',
							 'newLine'	=> "\n",
							 'BOMchar'	=> "\xEF\xBB\xBF");

			
			# izvedemo download
			if ($fd = fopen ($this->_dataFileName.'.csv', "r")) {
			    $fsize = filesize($this->_dataFileName.'.csv');
			    $path_parts = pathinfo($this->_dataFileName.'.csv');
			    $ext = strtolower($path_parts["extension"]);
			    #ob_clean();
                #header('Content-type: application/vnd.ms-excel; charset='.$convertTypes[$convertType]['charSet']);
		        header('Content-type: text/csv; charset='.$convertTypes[$convertType]['charSet']);
				header('Content-Disposition: attachment; filename="anketa'.$this->sid.'-'.date('Y-m-d').'.csv"');
				# ker iz zacasne datoteke preberemo samo podatke brez headerja (header izpisujemo posebej)
				# ne moremo podati content-lenght. Razen če bi predhodno vsae zapisali v tmp datoteko in potem prebrali dolžino 
				# header('Content-length: '.$fsize);
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Cache-Control: private',false);
				header('Content-Transfer-Encoding:­ binary'); 
				#ob_flush();
				# dodami boomchar za utf-8
				echo $convertTypes[$convertType]['BOMchar'];
				# naredimo header row
				
				if ((int)$_POST['export_labels'] == 1) {
					foreach ($this->_HEADERS AS $spid => $spremenljivka) {
						if (isset($this->_SVP_PV[$spid])) {
							if (count($spremenljivka['grids']) > 0) {
								foreach ($spremenljivka['grids'] AS $gid => $grid) {
									foreach ($grid['variables'] AS $vid => $variable ){
										
										if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){
											$output1 .=  str_replace($replace_what, $replace_with, strip_tags($variable['variable'])) . $field_delimit;
											$output2 .=  str_replace($replace_what, $replace_with, strip_tags($variable['naslov'])) . $field_delimit;
										}
									}
								}
							}
						}
					}
					echo $output1."\r\n";
					echo $output2."\r\n";
				}

				while ($line= fgets ($fd)) {
					echo '="';
					$line = str_replace(array("\r","\n"), array("",""), $line);
					print ($line);
					print ('"');
					print ("\r\n");
				}
            }
            else {
				echo 'x1:Napaka';
			}
			fclose ($fd);
			
			#pobrišemo vse tmp datoteke
			foreach (glob($folder . 'export_data_'.$this->sid.'*dat_data*'.TMP_EXT) as $fileToDelete) {
				unlink($fileToDelete);
			}
			unlink ($this->_dataFileName.'.csv');
        } 
        else {
			echo 'x2:Napaka!';
        }
        
		exit;
	}

	/** naredimo izvoz za txt
	 * 
	 */
	function exportText() {
		global $site_path;
		
		$folder = $site_path . EXPORT_FOLDER.'/';
		
		if ($this->_fileStatus >= 0 && $this->_dataFileName !== null && $this->_dataFileName !== '' && $this->_headFileName !== null && $this->_headFileName !== '') {

			#zapišemo v temp file
	
			$file_handler = fopen($this->_dataFileName.'.txt',"w");

			$output1 = '';
			$output2 = '';
			
			// array za labele (ce jih izpisujemo)
			$display_labels = true;
			$labels = array();
			
			# naredimo header row
			foreach ($this->_HEADERS AS $spid => $spremenljivka) {
				if (isset($this->_SVP_PV[$spid])) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						foreach ($grid['variables'] AS $vid => $variable ){

							if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){
								$output1 .= strip_tags($variable['variable']).';';
								$output2 .= strip_tags($variable['naslov']).';';
							}
						}
					}
				}
				
				// Ce izpisujemo tudi labele
				if($display_labels && isset($spremenljivka['spr_id'])){
					if(in_array($spremenljivka['tip'], array('1','3','6','16'))){
						
						$sequences = array();
						$sequences = explode('_', $spremenljivka['sequences']);
						$vars = $this->getVariableLabels($spremenljivka['spr_id']);
						
						foreach($sequences as $sequence){
							$labels[$sequence] = $vars;
						}
					}
				}
			}
			fwrite($file_handler,$output1."\n");
			fwrite($file_handler,$output2."\n");
			fclose($file_handler);
			
			
			# sfiltriramo podatke
			exec ('awk -F'.$this->_QUOTE.STR_DLMT.$this->_QUOTE.' '.$this->_QUOTE.$this->_CURRENT_STATUS_FILTER.' {print $0}'.$this->_QUOTE.' '.$this->_dataFileName.' > '.$this->_dataFileName .'_data1'.TMP_EXT);
			if (IS_WINDOWS) {
				#filtri spremenljivk
				$cmdLn1_1 = 'cut -d "|" -f '.$this->_VARIABLE_FILTER.' '.$this->_dataFileName .'_data1'.TMP_EXT.' > '.$this->_dataFileName .'_data1_1'.TMP_EXT;
			} else {
				#filtri spremenljivk
				$cmdLn1_1 = 'cut -d \'|\' -f '.$this->_VARIABLE_FILTER.' '.$this->_dataFileName .'_data1'.TMP_EXT.' > '.$this->_dataFileName .'_data1_1'.TMP_EXT;
			}
			$out1_1 = shell_exec($cmdLn1_1);
			
			# zamenjamo | z ;
			exec('sed '.$this->_QUOTE.'s/|/;/g'.$this->_QUOTE.' '.$this->_dataFileName .'_data1_1'.TMP_EXT.' >> '.$this->_dataFileName.'.txt');
	
			$convertType = 1; // kateri tip konvertiranja uporabimo
			$convertTypes[1] = array('charSet'	=> "windows-1250",
									 'delimit'	=> ";",
									 'newLine'	=> "\n",
									 'BOMchar'	=> "\xEF\xBB\xBF");
					
			# izvedemo download
			if ($fd = fopen ($this->_dataFileName.'.txt', "r")) {
				// clean the output buffer
				ob_clean();
				
			    $fsize = filesize($this->_dataFileName.'.txt');
			    $path_parts = pathinfo($this->_dataFileName.'.txt');
			    $ext = strtolower($path_parts["extension"]);
				header('Content-type: text/plain; charset='.$convertTypes[$convertType]['charSet']);
				header('Content-Disposition: attachment; filename="anketa'.$this->sid.'-'.date('Y-m-d').'.txt"');
			   # header('Content-length: '.$fsize);
				header('Pragma: public');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Cache-Control: private',false);
				header('Content-Transfer-Encoding:­ binary'); 
				ob_flush();
				
				
			    # dodami boomchar za utf-8
				echo $convertTypes[$convertType]['BOMchar'];
				
			    /*while(!feof($fd)) {
			        $buffer = fread($fd, 2048);
			        echo $buffer.'';
			    }*/
				$i=0;
				// Loop po vrsticah
				while(($line = fgets($fd)) !== false) {
					
					// Samo naslovni vrstici z metapodatki oz. vse vrstice ce nimamo label
					if($i < 2 || !$display_labels){
						echo $line;
					}
					// Izpisujemo labele v podatkovnih vrsticah
					else{
						// Vrstico s podatki razbijemo, dodamo labele in jo nazaj sestavimo
						$line_array = explode(';', $line);
						if(count($line_array) > 0){
							foreach($line_array as $seq => $val){
								
								// Izpisemo vrednost
								echo $val;

								// Izpisemo labelo
								$seq += 3;
								if(isset($labels[$seq][0]['values'][$val])){
									echo ' ("'.$labels[$seq][0]['values'][$val].'")';
								}

								// Vsem razen zadnjemu dodamo se separator
								if($seq < count($line_array)+2)
									echo ';';
							}
						}
					}
					
					$i++;
				}				
			} 
			else {
				echo "Napaka";
			}
			fclose ($fd);
			
			#pobrišemo vse tmp datoteke
			foreach (glob($folder . 'export_data_'.$this->sid.'_*'.TMP_EXT) as $fileToDelete) {
				unlink($fileToDelete);
			}
			unlink ($this->_dataFileName.'.txt');

		} 
		else {
			echo '';
		}

		exit;
	}
	
    // shranim SPS in A00, potem pokličem spss in je.
    private function exportSav () {
        
        // izbriši staro
        // pač... RM dela :-)
        @unlink ($this->folder .'tmp_spss2sav' .$this->sid .'.a00');
        @unlink ($this->folder .'tmp_spss2sav' .$this->sid .'.sps');
        
        // spss + a00
        $this->exportSpss("save", true, true);
        $this->exportSpss("save", false, true);
        
        // convert
        //echo "Diagnostics for developers (link is below): <br>";
        passthru ('pspp ' .$this->folder .'tmp_spss2sav' .$this->sid .'.sps');
        
        //echo '<br><br><strong><a href="SurveyData/' .'tmp_spss2sav' .$this->sid .'.SAV">Link</a></strong>';
        
		// Tole ne dela ker kessira star file
		//header ('location: SurveyData/' .'tmp_spss2sav' .$this->sid .'.SAV');
		
		$file_url = 'SurveyData/' .'tmp_spss2sav' .$this->sid .'.SAV';

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private',false);
		header('Content-Transfer-Encoding:­ binary');

		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");

		readfile($file_url);
    }
        
        
	/**
	 * mode download - klasično, pač skrani SAV + A00
         * mode save - shrani fajla
         * 
         * pa porinem si noter še data_par (true - a00, false - sps).
	 */
	private function exportSpss($mode = "download", $data_param = false, $pspp=false) {
		global $site_path, $lang;
		
		$folder = $site_path . EXPORT_FOLDER.'/';

		$tmp_files = $this->folder.'tmp_spss_'.$this->sid.'.php';
		#polovimo max št znakov za posamezne sekvence
		if (IS_WINDOWS) {
			$command = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"} {FS=\"\x7C\"} {SUBSEP=\"\x7C\"}} '
			.'{if (MaxFields < NF) MaxFields = NF; for (i=1; i<=NF; i++) { Field[NR, i] = $i; l = length($i); if (l < 1) l = 1; if (Length[i] < l) Length[i] = l; };} '
			.'END {print\"\x3C\x3Fphp\"; for (i=1; i<=MaxFields; i++) { print \"$spss_length[\",i,\"]\",\"=\",Length[i],\";\"}; print\"\x3F\x3E\"}" '
			.$this->_dataFileName. ' > '.$tmp_files ;
		} else {
			$command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"} {FS="\x7C"} {SUBSEP="\x7C"}} '
			.'{if (MaxFields < NF) MaxFields = NF; for (i=1; i<=NF; i++) { Field[NR, i] = $i; l = length($i); if (l < 1) l = 1; if (Length[i] < l) Length[i] = l; };} '
			.'END {print"\x3C\x3Fphp"; for (i=1; i<=MaxFields; i++) { print "$spss_length[",i,"]","=",Length[i],";"}; print"\x3F\x3E"}\' '
			.$this->_dataFileName. ' > '.$this->folder.'tmp_spss_'.$this->sid.'.php';
		}
		$out = shell_exec($command);
		
		include($tmp_files );
		
		# pobrišemo inkludan fajl, ker jih več ne rabimo
		if (file_exists($tmp_files )) {
			unlink($tmp_files );
		}

        if ($this->_fileStatus >= 0 && $this->_dataFileName !== null && $this->_dataFileName !== '' && $this->_headFileName !== null && $this->_headFileName !== '') {

			#ali lovimo datoteko s strukturo ali datoteko z podatki
			$data = false;
			if ((isset($_REQUEST['exportData']) && (int)$_REQUEST['exportData'] == 1) || $data_param == true) {
				$data = true;
			}
	
			# delamo datoteko s strukturo
			if ( $data == false ) { 
				
				# TODO - odstranit šumnike iz header datoteke
				#$str = preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $str);
				
				$_value_labels_numbers = '';	# temp variabla kamor shranimo imena variabel numeričnih odgovorov
				$_value_labels_text = '';		# temp variabla kamor shranimo imena variabel tekstovnih odgovorov
				
				#preberemo HEADERS iz datoteke
				$this->_HEADERS = unserialize(file_get_contents($this->_headFileName));

				# poiščemo maximalno število znakov pri missing vrednostih za tekstovne in number odgovore 
				$_all_missing_values = SurveyMissingValues::GetMissingValuesForSurvey(array(1,2,3));
				$_max_text_missing_chars = 0; 
				$_max_number_missing_chars = 0;

				# polovimo maximalne dolžine znakov
				foreach ($_all_missing_values AS $mkey => $missing) {
					$_max_text_missing_chars = max($_max_text_missing_chars, strlen($mkey . ': '. $missing));
					$_max_number_missing_chars = max($_max_number_missing_chars, strlen($mkey));
				}
				$maxLengthForSpr = self::create_array_SPSS(max($_max_text_missing_chars,$_max_number_missing_chars));
                //$resultString .= .NEW_LINE;
				$resultString = $lang['srv_spss_export_base_instructions'];
				$resultString .= NEW_LINE.'.'.NEW_LINE.NEW_LINE;
				
				$resultString .= 'PRESERVE.'.NEW_LINE;
				// TOLE NE DELA OK ZA DECIMALKE V NOVIH SPSSih
				/*if ($pspp == false) {
                    $resultString .= 'SET UNICODE ON.'.NEW_LINE;
                }
                else {
                    $resultString .= "SET LOCALE='UTF-8'.".NEW_LINE;
                }*/
				$resultString .= "SET LOCALE='en_US'.".NEW_LINE;
				$resultString .= 'SET UNICODE ON.'.NEW_LINE;
                                
				$resultString .= 'SET DECIMAL DOT.'.NEW_LINE.NEW_LINE;

				# seznam spremenljivk in opis formata
				$resultString .= 'GET DATA'.NEW_LINE;
				$resultString .= '  /TYPE = TXT'.NEW_LINE;
                                
                                
                if ($mode == "download") {
                    $resultString .= '  /FILE = \'C:\anketa'.$this->sid.'-'.date('Y-m-d').'_podatki.txt\''.NEW_LINE; 
                }
                else {
                    $resultString .= '  /FILE = \'' .$this->folder .'tmp_spss2sav' .$this->sid .'.a00\''.NEW_LINE; 
                }                
                                
				//.$lang['srv_spss_export_file_instructions'].NEW_LINE;
				$resultString .= '  /ARRANGEMENT = DELIMITED'.NEW_LINE;
				$resultString .= '  /FIRSTCASE = 1'.NEW_LINE;
				$resultString .= '  /IMPORTCASE = ALL'.NEW_LINE; 
				$resultString .= '  /DELIMITERS = " "'.NEW_LINE;
				$resultString .= '  /QUALIFIER = "\'"'.NEW_LINE;
				$resultString .= '  /VARIABLES = '.NEW_LINE;

				# dodamo seznam variabel z tipi podatkov
				if (count($this->_HEADERS) > 0) {

                    $cnt = 1;

					foreach ($this->_HEADERS AS $spid => $spremenljivka) {
						if (isset($this->_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
							foreach ($spremenljivka['grids'] AS $gid => $grid) {
								if (count($grid['variables']) > 0) {
									foreach ($grid['variables'] AS $vid => $variable ){
										$seq=$variable['sequence'];
										
										if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){
											
											# vsako v svojo vrstico
											if (substr($variable['spss'],0,1) == 'F') {
												# pri številih
												$_number = explode('.',substr($variable['spss'],1));
												$_cela = isset($_number[0]) && $_number[0] > 0 ? $_number[0] : 0;
												$_decimalna =  isset($_number[1]) && $_number[1] > 0 ? $_number[1] : 0;
												$_spss_chars = 'F'.max($_max_number_missing_chars,$_cela,1).'.'.$_decimalna; 
											} else if ($variable['spss'] == 'DATETIMEw') {
												# pri tekstovnih odgovorih
												#$_spss_chars = 'A'.$_max_text_missing_chars;
												
												#polovimo po novi metodi

												$_spss_chars = 'A'.$spss_length[$seq];
											} else {
												# pri tekstovnih odgovorih
												#$_spss_chars = substr($variable['spss'],0,1) . max($maxLengthForSpr[$spid],$_max_text_missing_chars,substr($variable['spss'],1),1);
												
												#polovimo po novi metodi
												$_spss_chars = 'A'.$spss_length[$seq];
                                            }
                                            
                                            // Language meta moramo preimenovati za spss, ker drugace je podvojen z language spremenljivko
                                            if($variable['variable'] == 'Language')
                                                $resultString .= '    Language_meta '.$_spss_chars.NEW_LINE;
                                            else
											    $resultString .= '    ' . preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable['variable']) . ' '.$_spss_chars.NEW_LINE;
											
											# polovimo imena variable za missing vrednosti  Nagovora ne dodajmo
											if (isset($spremenljivka['tip']) && $spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm' && $spremenljivka['tip'] != 5) {
												if (substr($variable['spss'],0,1) == 'F') { 
                                                    $_value_labels_numbers .= $variable['variable'].' ';
                                                    
                                                    // Dodamo prelom vsakih 10 variabel zaradi max dolzine vrstice
                                                    if($cnt > 10){
                                                        $_value_labels_numbers .= NEW_LINE;
                                                        $cnt = 0;
                                                    }

                                                    $cnt++;
                                                } 
                                                else  if (substr($variable['spss'],0,1) == 'A') {
                                                    $_value_labels_text .= $variable['variable'].' ';
                                                    
                                                    // Dodamo prelom vsakih 10 variabel zaradi max dolzine vrstice
                                                    if($cnt > 10){
                                                        $_value_labels_text .= NEW_LINE;
                                                        $cnt = 0;
                                                    }

                                                    $cnt++;
												}
											}	
										}									
									}
								}
							}
						}
					}			
				}
				
		    	$resultString .= '    .'.NEW_LINE.NEW_LINE;
		    	
				#ime ankete brez presledkov
                // pspp ne mara _
                if ($pspp == false) 
					$resultString .= 'DATASET NAME ' . preg_replace("/[^a-zA-Z0-9_]/",'_',$this->survey['naslov']) . ' WINDOW=FRONT.' . NEW_LINE.NEW_LINE;
                else 
					$resultString .= 'DATASET NAME ' . preg_replace("/[^a-zA-Z0-9_]/",'',$this->survey['naslov']) . ' WINDOW=FRONT.' . NEW_LINE.NEW_LINE;
	
				#labele vprasanj ==> VARIABLE LABELS
				# seznam spremenljivk in opis formata (pika na koncu vsake labele)
			    if (count($this->_HEADERS) > 0) {
				    foreach ($this->_HEADERS AS $spid => $spremenljivka) {
				    	if (isset($this->_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
							foreach ($spremenljivka['grids'] AS $gid => $grid) {
								if (count($grid['variables']) > 0) {
									foreach ($grid['variables'] AS $vid => $variable ){

										if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){

											switch ($spremenljivka['tip']) {
												case '2':
												case '6':
													
													$variable_label = substr($spremenljivka['naslov'],0,30).': '.$variable['naslov'];
													$_variable = preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable['variable']);
												break;
												case '7':
													if (isset($spremenljivka['enota'])) {
														$variable_label= substr($spremenljivka['naslov'],0,30) .' ('.$variable['naslov'].')';
													} else {
														$variable_label= substr($spremenljivka['naslov'],0,30) ;
													}
													$_variable = preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable['variable']);
												break;
												case '16':
												case '19':
												case '20':
													$variable_label= substr($spremenljivka['naslov'],0,15) .': '.$grid['naslov'].': '.$variable['naslov'];
													$_variable = preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable['variable']);
												break;
												case '21':
													
													$variable_label= substr($spremenljivka['naslov'],0,30) .' ('.$variable['naslov'].')';
													$_variable = preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable['variable']);
												break;
												default:
													$variable_label = $variable['naslov'];
													$_variable = preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable['variable']);
												break;
											}
											if($variable['other'] == 1 && $variable['text'] == 1) {
												$variable_label .= ' '.$lang['srv_sppss_text_other'];
											}
											
											$variable_label = $this->getCleanString($variable_label);
                                            
                                            // Language meta moramo preimenovati za spss, ker drugace je podvojen z language spremenljivko
                                            if($variable['variable'] == 'Language')
                                                $resultString .= 'VARIABLE LABELS Language_meta \''.$variable_label.'\' .'.NEW_LINE;
                                            else
											    $resultString .= 'VARIABLE LABELS '.$_variable.' \''.$variable_label.'\' .'.NEW_LINE;
										}
									}
								}
							}
				    	}
					}
					$resultString .=NEW_LINE;
			    }			
				
				
				# labele vrednosti ==> VALUE LABELS
			    # seznam label  vrednosti spremenljivk (pika na koncu vsakega sklopa)
				if (count($this->_HEADERS) > 0) {
					foreach ($this->_HEADERS AS $spid => $spremenljivka) {
				    	if (isset($this->_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
					    	if (isset($spremenljivka['options'])) {
					    		$resultString .= 'VALUE LABELS';
								foreach ($spremenljivka['grids'] AS $gid => $grid) {
									if (count($grid['variables']) > 0) {
										foreach ($grid['variables'] AS $vid => $variable ){

											if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){

								    			if ($variable['other'] != 1) {
								    				$variable = $this->getCleanString($variable['variable']);
								    				$resultString .= ' '.preg_replace ("/[^a-zA-Z0-9_\/]/", "_", $variable);
								    			}
											}
										}
									}
						    	}
								
                                // pspp ne mara newline, hoče presledek vmes.
						    	if ($pspp == false) $resultString .= NEW_LINE;
                                else $resultString .= ' ';
                                                        
						    	if (count($spremenljivka['options']) > 0) {
							    	foreach ($spremenljivka['options'] AS $okey =>$option) {

										if(($spremenljivka['tip'] !== 'sm' && $spremenljivka['tip'] !== 'm') || $this->exportSettings['FullMeta'] > 0){
													
								    		if ($spremenljivka['tip'] == 2 || $spremenljivka['tip'] == 16) {
												# pri čekboxu prevedemo
												if ($pspp == false){ 
													$resultString .= $okey. ' \''.$lang['srv_sppss_checkbox_value_'.$option].'\''.NEW_LINE;								    			
												}
												else {
													$resultString .= $okey. ' \''.$lang['srv_sppss_checkbox_value_'.$option].'\''.' ';								    			
												}
								    		} 
											else {
								    			$option = $this->getCleanString($option);
                                                if ($pspp ==false)  $resultString .= $okey. ' \''.$option.'\''.NEW_LINE;	
                                                else {
                                                    if (strpos ($option, "&#39;")===false) {
                                                        $resultString .= $okey. ' \''.$option.'\''.' ';	
                                                    }
                                                    else {
                                                        $resultString .= $okey. ' \"'.str_replace ("&#39;", "'", $option) .'\"'.' ';	
                                                    }
                                                }
								    		}
							    		}
							    	}
						    	}
						    	$resultString .='.'.NEW_LINE.NEW_LINE;
							}
				    	}
					}
				}			

				# missing vrednosti ==> MISSING VALUES
				# seznam - razpon mising vrednosti ( pika na koncu ukaza)
				/*
				 * Manjkajoče vrednosti se definirajo samo za številske (format F) spremenljivke, saj določanje intervala za 
				 * tekstovne (format A) ni podprto. Interval manjkajočih vrednosti označi uporabnik v vmesniku za izvoz, privzeto 
				 * je od -999 do -1. Uporabnik lahko izbere tudi, da ne želi definirati manjkajočih vrednosti – v tem primeru se 
				 * spodnja koda ne vključi v sintakso. 
				 * MISSING VALUES Q1 Q2 (...) Qn (a thru b).
				 * Q1, Q2, ..., Qn: imena številskih spremenljivk.
				 */
				$_unset = SurveyMissingValues::GetUnsetValuesForSurvey();
				$_missings = SurveyMissingValues::GetMissingValuesForSurvey();

				#poiščemo razpon missingov, najmanjči in največji
				$_min = null;
				$_max = null;
				if (count($_unset) > 0) {
					foreach ( $_unset AS $key => $_mising_value) {
						$_min = ($_min == null) ? $key : min($_min,$key);
						$_max = ($_max == null) ? $key : max($_max,$key);
					}
				} 
				if (count($_missings) > 0) {
					foreach ( $_missings AS $key => $_mising_value) {
						$_min = ($_min == null) ? $key : min($_min,$key);
						$_max = ($_max == null) ? $key : max($_max,$key);
					}
				}
				
				if ($_min == null) $_min = -99; # privzeto
				if ($_max == null) $_max = -1;	# privzeto
				
				if (count($this->_HEADERS) > 0 && $_value_labels_numbers != '') {
					$resultString .='MISSING VALUES ';
					$resultString .= $_value_labels_numbers . ' ('.$_min.' thru '.$_max.')';				    
				    $resultString .='.'.NEW_LINE.NEW_LINE;
				}
				
				# missing labele ==> ADD VALUE LABELS
				# seznam label za missinge ( pika na koncu seznama) Naredimo dvakrat, 1x za števila in 1x za texte
				/** Labele za manjkajoče vrednosti se definirajo za številska in besedilna vprašanja in sicer ločeno. 
				 *  Labele se dobijo iz sistemskih nastavitev manjkajočih vrednosti v 1KA (npr. neodgovor itd.) 
				 */
				# za number odgovore
				if ( (count($_missings)+count($_unset)) > 0 && $_value_labels_numbers != '') {
                    if ($pspp == false) 
                        $resultString .='ADD VALUE LABELS ' . $_value_labels_numbers.NEW_LINE;
                    else 
                        $resultString .='VALUE LABELS ' . $_value_labels_numbers.' ';
                                            
					if (count($_missings) > 0) {
						foreach ( $_missings AS $mkey => $missing_value) {
                            if ($pspp == false) 
                                $resultString .= $mkey . ' \'' . $this->getCleanString($missing_value).'\''.NEW_LINE;
                            else {
                                if (strpos ($this->getCleanString($unset_value), "&#39;")===false) {
                                    $resultString .= $mkey . ' "' . $this->getCleanString($missing_value).'"'.' ';
                                }
                                else {
                                    $resultString .= $mkey . ' "' . str_replace ("&#39;", "'", $this->getCleanString($missing_value)) .'"'.' ';
                                }
                            }
						}
					}
					if (count($_unset) > 0) {
						foreach ( $_unset AS $ukey => $unset_value) {
							if ($pspp == false) $resultString .= $ukey . ' \'' . $this->getCleanString($unset_value).'\''.NEW_LINE;
							else {
                                if (strpos ($this->getCleanString($unset_value), "&#39;")===false) {
                                    $resultString .= $ukey . ' \'' . $this->getCleanString($unset_value).'\''.' ';
                                }
                                else {
                                    $resultString .= $ukey . ' "' . str_replace ("&#39;", "'", $this->getCleanString($unset_value)).'"'.' ';
                                }
                            }
						}
					}
					
					$resultString .='.'.NEW_LINE.NEW_LINE;
				}		
				
				# za tekstovne odgovore prekodiramo missinge
                // ne smeš, ker prideš preko dovoljene dolžine. V A5 pač ne gre 20 znakov...
                // SPSS ima sicer komando "alter" za spremeniti string, ampak ker pspp ne podpira, delam labele!

				if ( (count($_missings)+count($_unset)) > 0 && $_value_labels_text != '') {

                    $cnt = 1;
					$resultString .= 'VALUE LABELS ' .$_value_labels_text .' '.NEW_LINE;
                                        
					if (count($_missings) > 0) {
						foreach ( $_missings AS $mkey => $missing_value) {
                            $resultString .= '\'' .$mkey . '\' \'' .$mkey . ': '. $this->getCleanString($missing_value).'\' ';
                            
                            $resultString .= NEW_LINE;
						}
                    }

					if (count($_unset) > 0) {
						foreach ( $_unset AS $ukey => $unset_value) {
                            if ($pspp == false){
                                $resultString .= '\'' . $ukey . '\' \'' . $ukey . ': '. $this->getCleanString($unset_value).'\' ';

                                $resultString .= NEW_LINE;
                            }
						}
                    }	
                    				
					$resultString .= '.' . NEW_LINE.NEW_LINE;
				}		

                if ($mode != "download") {
                    $resultString .= 'SAVE /OUTFILE \'' .$this->folder .'tmp_spss2sav' .$this->sid .'.SAV\'.' .NEW_LINE;
                }
                                
				# povrnemo narejene spremembe v spssu
				$resultString .='EXECUTE.'.NEW_LINE;
				$resultString .='RESTORE.';
				
				$convertType = 1; // kateri tip konvertiranja uporabimo
				$convertTypes[1] = array('charSet'	=> "windows-1250",
										 'delimit'	=> ";",
										 'newLine'	=> "\n",
										 'BOMchar'	=> "\xEF\xBB\xBF");

                // downloadaj
                if ($mode == "download") {
                    ob_clean();
                
                    header('Content-type: text/plain; charset='.$convertTypes[$convertType]['charSet']);
                    header('Content-Disposition: attachment; filename="anketa'.$this->sid.'-'.date('Y-m-d').'.sps"');
                    # header("Content-length: $fsize");
                    header('Pragma: public');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Cache-Control: private',false);
                    header('Content-Transfer-Encoding:­ binary'); 
                    ob_flush();

                    #$resultString = iconv("UTF-8", "CP1250", $resultString);
                    # dodami boomchar za utf-8

                    echo $convertTypes[$convertType]['BOMchar'];
                    echo $resultString;
                }
                
                // shrani nekam na disk če imaš "save" namesto "download"
                else {
                    $fh = fopen($this->folder .'tmp_spss2sav' .$this->sid.'.sps', 'w');
                    fwrite($fh, $resultString);
                    fclose($fh);
                }
				// end if data = false
			} 
			else {
				#lovimo podatke
				
				// | -> \x7C 
				// ` -> \x60
				// ' -> \x27
				// " -> \x22	
				$tmp_files = array(	'original'	=> $this->_dataFileName,
					'first'		=> $this->_dataFileName.'_first',
					'first1'		=> $this->_dataFileName.'_first_1',
					'second'	=> $this->_dataFileName.'_second',
					'third'		=> $this->_dataFileName.'_third',
					'fourth'	=> $this->_dataFileName.'_fourth');

				if (IS_WINDOWS) {
					
					# polovimo vrstice z statusom
					$cmdLn1 = 'awk -F"'.STR_DLMT.'" "'.$this->_CURRENT_STATUS_FILTER.' {print $0}" '.$tmp_files['original'].' > '.$tmp_files['first'];
					#filtri spremenljivk
					$cmdLn1_1 = 'cut -d "|" -f '.$this->_VARIABLE_FILTER.' '.$tmp_files['first'].' > '.$tmp_files['first1'];
					#zamenjamo ' => `
					$cmdLn2 = 'sed "s/\x27/\x60/g" '.$tmp_files['first1'].' > '.$tmp_files['second'];
					# zamenjamo | z ' '
					$cmdLn3 = 'sed "s/'.STR_DLMT.'/\x27 \x27/g" '.$tmp_files['second'].' > '.$tmp_files['third'];
					# dodamo ' na začetek in konec
					$cmdLn4 = 'awk '.$this->_QUOTE.'{print \"\'\"$0\"\'\"}'.$this->_QUOTE.' '.$tmp_files['third'].' > '.$tmp_files['fourth']; 
				} else {
					# polovimo vrstice z statusom
					$cmdLn1 = "awk -F'\x7C' '".$this->_CURRENT_STATUS_FILTER." {print $0}' ".$tmp_files['original'].' > '.$tmp_files['first'];
					#filtri spremenljivk
					$cmdLn1_1 = 'cut -d \'|\' -f '.$this->_VARIABLE_FILTER.' '.$tmp_files['first'].' > '.$tmp_files['first1'];
					#zamenjamo ' => `
					$cmdLn2 = 'sed \'s/\x27/\x60/g\' '.$tmp_files['first1'].' > '.$tmp_files['second'];
					# zamenjamo | z ' '
					$cmdLn3 = 'sed \'s/'.STR_DLMT.'/\x27 \x27/g\' '.$tmp_files['second'].' > '.$tmp_files['third'];
					# dodamo ' na začetek in konec
					$this->_QUOTE = '\'';
					$cmdLn4 = 'awk \'{print "\x27"$0"\x27"}\' '.$tmp_files['third'].' > '.$tmp_files['fourth'];
				}
				
				$out1 = shell_exec($cmdLn1);
				$out1_1 = shell_exec($cmdLn1_1);
				$out2 = shell_exec($cmdLn2);
				$out3 = shell_exec($cmdLn3);
				$out4 = shell_exec($cmdLn4);
				
				if ($_GET['debug'] == 1) {
					print_r('<br>'.$cmdLn1);
					print_r('<br>'.$cmdLn2);
					print_r('<br>'.$cmdLn3);
					print_r('<br>'.$cmdLn4);
				}

				# nardimo output
				$convertType = 1; // kateri tip konvertiranja uporabimo
				$convertTypes[1] = array('charSet'	=> "windows-1250",
										 'delimit'	=> ";",
										 'newLine'	=> "\n",
										 'BOMchar'	=> "\xEF\xBB\xBF");
                if ($mode == "download") {
                    # izvedemo download
                    if ($fd = fopen ($tmp_files['fourth'], "r")) {
                            
						ob_clean();
                        
						$fsize = filesize($tmp_files['fourth']);
                        $path_parts = pathinfo($tmp_files['fourth']);
                        $ext = strtolower($path_parts["extension"]);
                            
						header('Content-type: text/plain; charset='.$convertTypes[$convertType]['charSet']);
                        header('Content-Disposition: attachment; filename="anketa'.$this->sid.'-'.date('Y-m-d').'_podatki.txt"');
                    	#header("Content-length: $fsize");
                        header('Pragma: public');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Cache-Control: private',false);
                        header('Content-Transfer-Encoding:­ binary'); 
                        ob_flush();

                        # dodami boomchar za utf-8
                        echo $convertTypes[$convertType]['BOMchar'];

                        while(!feof($fd)) {
                            $buffer = fread($fd, 2048);
                            echo $buffer;
                        }

                    } 
					else {
                            echo "Napaka";
                    }
					
                    fclose ($fd);
                }
                
                // priprava za SAV
                else {
                    copy ($tmp_files['fourth'], $this->folder .'tmp_spss2sav' .$this->sid.'.a00');
                }
	
				#pobrišemo vse tmp datoteke
				if (file_exists($tmp_files['first'])) { unlink($tmp_files['first']); }
				if (file_exists($tmp_files['first1'])) { unlink($tmp_files['first1']); }
				if (file_exists($tmp_files['second'])) { unlink($tmp_files['second']); }
				if (file_exists($tmp_files['third'])) { unlink($tmp_files['third']); }
				if (file_exists($tmp_files['fourth'])) { unlink($tmp_files['fourth']); }

			} // end if data = true
		} 
		else {

		}
                
        if ($mode == "download") {
            ob_flush();
            exit;
        }
        else {
            return;
        }
	}
	
	
	/** Preveri ali obstajata datoteki z podatki in headerji in ali sta zadnji ažurni
	 * 
	 */
	public function checkFile() {
		
		$SDF = SurveyDataFile::get_instance();
        $SDF->init($this->sid);
        
		$this->_headFileName = $SDF->getHeaderFileName();
		$this->_dataFileName = $SDF->getDataFileName();
		$this->_fileStatus = $SDF->getStatus();
        
        return $this->_fileStatus;
	}

	private function create_array_SPSS($max_missing) {
		$array_SPSS = array();

		$db_table = ($this->survey['db_table'] == 1) ? '_active' : '';

		# poberemo max dolžine iz srv_data_text max(text1,text2)
		$str_query = 'SELECT dt.spr_id, MAX(LENGTH(dt.text)) AS length, MAX(LENGTH(dt.text2)) AS length2 FROM srv_data_text'.$db_table.' dt, srv_grupa g, srv_spremenljivka s WHERE dt.spr_id = s.id AND s.gru_id=g.id AND g.ank_id='.$this->sid.' GROUP BY dt.spr_id';
		$_qry_SPSS = sisplet_query($str_query);
		while (list($spr_id,$text,$text2) = mysqli_fetch_row($_qry_SPSS)) {
			$array_SPSS[$spr_id] = max((int)$text,(int)$text2,$max_missing);
		}
		$str_query = 'SELECT dt.spr_id, MAX(LENGTH(dt.text)) AS length FROM srv_data_textgrid'.$db_table.' AS dt, srv_grupa g, srv_spremenljivka s  WHERE dt.spr_id = s.id AND s.gru_id=g.id AND g.ank_id='.$this->sid.' GROUP BY dt.spr_id';
		$_qry_SPSS = sisplet_query($str_query);
		while (list($spr_id,$text) = mysqli_fetch_row($_qry_SPSS)) {
			#$this->_array_SPSS[$spr_id]['text2'] = ((int)$text < $this->MISSING_MAX_LENGTH ? $this->MISSING_MAX_LENGTH :$text);
			$array_SPSS[$spr_id] = max((int)$text,$array_SPSS[$spr_id],$max_missing);
		}
		return $array_SPSS;
	}

	
	public function ajax() {
		if ($_GET['a'] == 'doexport') {
			self :: DoExport();
		}
	}
	
	function getCleanString($string) {

        // Replace quotov
        $string = preg_replace ("/'/", "`", $string);

        // Max dolžina stringa je 240 znakov 
        $string = $this->splitStringIntoLines($string);

		return $string;
    }
    
    // Max dolžina stringa je 200 znakov - razbijemo v chunke za spss
    private function splitStringIntoLines($string){
        
        if(strlen($string) <= 200)
            return $string;

        $new_string = chunk_split($string, 200, '\'+'.NEW_LINE.'\'');
        $new_string = substr($new_string, 0, -4);  

		return $new_string;
    }

	private function getVariableLabels($spr_id){
		global $lang;
		
		$s = sisplet_query("SELECT tip FROM srv_spremenljivka WHERE id = '$spr_id'");
		$r = mysqli_fetch_array($s);
		
		if ( in_array($r['tip'], array(1, 3)) ) {
		
			$output = array();
			
			$output['spr'] = $spr_id;
			$output['tip'] = $r['tip'];
			
			$output['values'] = array();
			
			$sql = sisplet_query("SELECT naslov, variable FROM srv_vrednost WHERE spr_id='$spr_id' ORDER BY vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {					
				$output['values'][$row['variable']] = strip_tags( $row['naslov'] );
			}
			
			$output['values']['-1'] = $lang['srv_bottom_data_legend_note_li1a'];
			$output['values']['-2'] = $lang['srv_bottom_data_legend_note_li2a'];
			$output['values']['-3'] = $lang['srv_bottom_data_legend_note_li3a'];
			$output['values']['-4'] = $lang['srv_bottom_data_legend_note_li4a'];
			$output['values']['-5'] = $lang['srv_bottom_data_legend_note_li5a'];
			
			$response[] = $output;
			
		} elseif ( in_array($r['tip'], array(6, 16)) ) {
			
			$output = array();
			
			$output['spr'] = $spr_id;
			$output['tip'] = $r['tip'];
			
			$output['values'] = array();
			
			$sql = sisplet_query("SELECT naslov, variable FROM srv_grid WHERE spr_id='$spr_id' ORDER BY vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {
				$output['values'][$row['variable']] = strip_tags( $row['naslov'] );
			}
			
			$output['values']['-1'] = $lang['srv_bottom_data_legend_note_li1a'];
			$output['values']['-2'] = $lang['srv_bottom_data_legend_note_li2a'];
			$output['values']['-3'] = $lang['srv_bottom_data_legend_note_li3a'];
			$output['values']['-4'] = $lang['srv_bottom_data_legend_note_li4a'];
			$output['values']['-5'] = $lang['srv_bottom_data_legend_note_li5a'];
			
			$response[] = $output;
		}
		
		return $response;
	}
}
?>