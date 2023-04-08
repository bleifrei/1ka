<?php 
/**
 * Created on Dec.2010
 *
 * @author: Gorazd Vesleič
 *
 * @desc: za kreacijo datoteke s podatki za SN (Alterji)
 * 			Datoteko z alterji skreira iz obstoječe datoteke s podatki
 *
 * funkcije:
 * 		- Init() - inicializacija
 *
 * CHANGELOG:
 * - 9.12.2011
 *   Po novem bo potrebno ločit tabele za vsak SN loop. Zato se bo kreiralo tudi več datotek (koliko je pač glavnih loopov)
 *   Hkrati je potrebno navest za kateri krog atunučuja gre :) 
 *   zato je na začetek dodana funkcija ki prešteje loope
 */

#KONSTANTE
define(EXPORT_FOLDER, "admin/survey/SurveyData");
DEFINE (STR_DLMT, "|");

class SurveySNDataFile {
	
	private $surveyId = null;			# Id ankete
	private $folder = null;				# pot do datotek s podatki
	private $_HEADERS = null;			# Header podatki
	
	private $headFileName = null;						# pot do header fajla
	private $dataFileName = null;						# pot do data fajla
	private $dataFileStatus = null;						# status data datoteke
	private $SDF = null;								# class za inkrementalno dodajanje fajlov
	
	private $sn_loop_parents = null;		#glavni loopi
	private $sn_loop_spremenljivke = null;	# loopi po spremenljivkah
	private $sn_loop_data = null;			# ime loop variable, antonucci...
	private $snCreateFullTable = true;		# Ali prikazujemo celotno tabelo
	
	
	private $_VARS = array();		
	
	function __construct ($sid) {
		# nastavimo privzeto pot do folderjev
		global $site_path, $global_user_id;
		
		$this->surveyId = $sid;
		$this->folder = $site_path . EXPORT_FOLDER.'/';
		
		#inicializiramo class za datoteke
		$this->SDF = SurveyDataFile::get_instance();
		$this->SDF->init($sid);
		$this->headFileName = $this->SDF->getHeaderFileName();
		$this->dataFileName = $this->SDF->getDataFileName();
		$this->dataFileStatus = $this->SDF->getStatus();

		session_start();
		
		$this->snCreateFullTable = $_SESSION['sid_'.$sid]['snCreateFullTable'];
		if ( $this->dataFileStatus == FILE_STATUS_NO_DATA
				|| $this->dataFileStatus == FILE_STATUS_SRV_DELETED) {
			Common::noDataAlert();
			return false;
		}
	}
	
	public function setVars($_VARS) {
		$this->_VARS = $_VARS;
	}
	public function setParameter($parameter,$value) {
		$this->{$parameter} = $value;
	}
	/** Preštejemo loope
	 * 
	 */
	function countLoops() {
		if ($this->dataFileStatus >= 0 && $this->headFileName != '') {
			$this -> _HEADERS = unserialize(file_get_contents($this->headFileName)) ;
			# poiščemo skevence za vse variable loopa
			#$_headers = $this -> _HEADERS;
			$_headers = $this->getCleanHeader();
			
			#preštejemo koliko loopov imamo
			$this->sn_loop_parents = array();
			$this->sn_loop_spremenljivke = array();
			
			if (count($_headers) > 0) {
				foreach ($_headers as $spr => $spremenljivka) {
					if ($spremenljivka['loop_parent'] > 0) {
						$this->sn_loop_data[$spremenljivka['loop_parent']]['antonucci'] = $spremenljivka['antonucci'];
						$this->sn_loop_data[$spremenljivka['loop_parent']]['spr'] = $spr;
						$this->sn_loop_data[$spremenljivka['loop_parent']]['variable'] = $spremenljivka['variable'];
						$this->sn_loop_data[$spremenljivka['loop_parent']]['naslov'] = $spremenljivka['naslov'];
					}
					# spremenljivka je parent za loop, preštejemo variable
					if (is_countable($spremenljivka['grids']) && count($spremenljivka['grids']) > 0) {
						foreach($spremenljivka['grids'] AS $gid => $grid) {
							if (count($grid['variables']) > 0) {
								foreach($grid['variables'] AS $vid => $variable) {
									# če smo v loop parent 
									if ($spremenljivka['loop_parent'] > 0) {
										$this->sn_loop_parents[$spremenljivka['loop_parent']][$variable['sequence']] = $variable['sequence'];

									}
									# če smo v loop spremenljivki
									if ($spremenljivka['parent_loop_id'] > 0) {
										$this->sn_loop_spremenljivke[$spremenljivka['parent_loop_id']][$spremenljivka['loop_id']][$variable['sequence']] = $variable['sequence'];
									}
								}
							}						
						}
					}				
				}
			}
		}
	}

	/** Skreira datoteko z alterji iz obstoječe datoteke s podatki
	 * 
	 */
	function createSNDataFile($lpid, $sn_loop_parent,$_SN_head_file_name,$_sn_filename) {
		if ($this->dataFileStatus >= 0 && $this->headFileName != '') {
			# če kreiramo nove, pobrišemo morebitne obstoječe datoteke 
			$this->deleteOldSnFiles();
			
			
			#popucamo headers
			$_headers = $this->getCleanHeader();
		
			$sn_loop_spremenljivke = $this->sn_loop_spremenljivke;

			# resetiramo array za SN_HEADER 
			$_empty_name_filter = array();
			$SN_HEADER = array();
			
			$sequences = array(); # kam shranimo sequence
			$loop_header_cnt = 0;
					
			if (count($sn_loop_parent) > 0 ) {
				foreach ($sn_loop_parent as $lsid => $loop_sequence) {
					$loop_header_cnt ++;
					$_new_sequence = 1;
					
					# poiščemo variable loopa
					if (isset($sn_loop_spremenljivke[$lpid]) && count($sn_loop_spremenljivke[$lpid]) > 0) {
						$_loop_cnt = array_shift($sn_loop_spremenljivke[$lpid]);
					} else {
						$_loop_cnt  = array();
					}
					
					$sequences[$lpid.'_'.$lsid] = array();

					# Zloopamo skozi vse spremenljivke in dodamo v primerno skupino
					if (count($_headers) > 0) { 
						foreach ($_headers as $spr => $spremenljivka) {
									
							if ($loop_header_cnt === 1 ) {
								$tmp_spremenljivka = $spremenljivka;
							}
							# odvisno ali smo v loopu ali ne dodamo primerne spremnljivke
							if ((int)$spremenljivka['loop_parent'] > 0 || (int)$spremenljivka['parent_loop_id']) {
								# ali smo v parent spremenljivki ali v loop spremenljivkah
								if ((int)$spremenljivka['loop_parent'] > 0 && $lpid == (int)$spremenljivka['loop_parent']){
									
									$_first_parent_variable = 0;

									#smo v parent splemenljivki, dodamo samo variablo z primerno sekvenco
									if (count($spremenljivka['grids']) > 0) {
										foreach($spremenljivka['grids'] AS $gid => $grid) {
											if (count($grid['variables']) > 0) {
												foreach($grid['variables'] AS $vid => $variable) {
													
													if ($variable['sequence'] == $loop_sequence) {
														#$sequences[$lpid.'_'.$lsid][$spr] = $variable['sequence'];
														$sequences[$lpid.'_'.$lsid][] = $variable['sequence'];
														# v sn imenih imamo samo 1 variablo
														if ($loop_header_cnt === 1 && $_first_parent_variable === 0) {
															$_empty_name_filter[] = '($'.$_new_sequence .' != -1 && $'.$_new_sequence .' != -2 && $'.$_new_sequence .' != -3 && $'.$_new_sequence .' != -4 && $'.$_new_sequence .' != -5)';
															$tmp_spremenljivka['grids'][$gid]['variables'][$vid]['sequence'] = $_new_sequence;
															$tmp_spremenljivka['sequences'] = $_new_sequence;
															$tmp_spremenljivka['cnt_grids'] = 1;
															$tmp_spremenljivka['cnt_all'] = 1;
															$tmp_spremenljivka['grids']['0']['cnt_vars'] = 1;
																													 
															$_new_sequence++;
															$_first_parent_variable ++;	
															
														}
													}
													# odstranimo ostale variable
													if ( $gid == 0 && $vid > 0) {
														unset($tmp_spremenljivka['grids'][$gid]['variables'][$vid]);
													} else if ( $gid > 0) {
														unset($tmp_spremenljivka['grids'][$gid]);
													}
																
												}
											}
										}
										
									}
									
									# dodamo spremenljivko v nov header
									if ($loop_header_cnt === 1) {
										$SN_HEADER[$spr] = $tmp_spremenljivka;
									}
								}
								# ali smo v spremenljivki v loopu
								if ((int)$spremenljivka['parent_loop_id'] > 0) {
									$add_this_spr = false;
									$sequenceses = array();
									# smo v loop spremenljivkah, dodamo variable s pravo frekvenco
									if (count($spremenljivka['grids']) > 0) {
										foreach($spremenljivka['grids'] AS $gid => $grid) {
											if (count($grid['variables']) > 0) {
												foreach($grid['variables'] AS $vid => $variable) {
													if (isset($_loop_cnt[$variable['sequence']])) {
														$add_this_spr = true;
														#$sequences[$lpid.'_'.$lsid][$spr] = $variable['sequence'];
														$sequences[$lpid.'_'.$lsid][] = $variable['sequence'];
														if ($loop_header_cnt === 1) {
															$tmp_spremenljivka['grids'][$gid]['variables'][$vid]['sequence'] = $_new_sequence;
															$sequenceses[] = $_new_sequence; 
															$_new_sequence++;
														}
													}
												}
											}
										}
									}
									
									# dodamo spremenljivko v nov header
									if ($loop_header_cnt === 1 && $add_this_spr == true) {
										$SN_HEADER[$spr] = $tmp_spremenljivka;
										$SN_HEADER[$spr]['sequences'] = implode('_',$sequenceses);
									}
								}
							} else {
									
								# nismo v loopu
								$sequenceses = array();
								if (is_countable($spremenljivka['grids']) && count($spremenljivka['grids']) > 0) {
									foreach($spremenljivka['grids'] AS $gid => $grid) {
										if (count($grid['variables']) > 0) {
											foreach($grid['variables'] AS $vid => $variable) {
												#$sequences[$lpid.'_'.$lsid][$spr] = $variable['sequence'];
												$sequences[$lpid.'_'.$lsid][] = $variable['sequence'];
												if ($loop_header_cnt === 1) {
													$tmp_spremenljivka['grids'][$gid]['variables'][$vid]['sequence'] = $_new_sequence;
													$sequenceses[] = $_new_sequence;
													$_new_sequence++;
												}
																										
											}
										}
									}
								}
								
								# dodamo spremenljivko v nov header
								if ($loop_header_cnt === 1) {
									$SN_HEADER[$spr] = $tmp_spremenljivka;
									$SN_HEADER[$spr]['sequences'] = implode('_',$sequenceses);
								}
							}
						}
					}
				}
			}

			# zapišemo header za SN datoteko
			if (is_array($SN_HEADER) && count($SN_HEADER) > 0) {
				#zapišemo SN header datoteko
				file_put_contents($_SN_head_file_name, serialize($SN_HEADER));
				
			}

			# KREACIJA DATA DATOTEKE
			# skreiramo fajle z potrebnimi skevencami
			if (count($sequences) > 0) {
				$_original_data_file = $this->dataFileName;
				$_paste_files = '';

				foreach ($sequences AS $skey => $sequence) {
					
					if (is_array($sequence) && count($sequence)>0) {
						$_sequence = implode(",",$sequence);

						if (IS_WINDOWS) {
							$cmdLn1 = 'cut -d "|" -f '.$_sequence.' '.$_original_data_file.' > '.$this->folder.'export_sn_data_'.$this->surveyId.'_'.$lpid.'_'.$skey.'.dat';
						} else {
							$cmdLn1 = 'cut -d \'|\' -f '.$_sequence.' '.$_original_data_file.' > '.$this->folder.'export_sn_data_'.$this->surveyId.'_'.$lpid.'_'.$skey.'.dat';
						}
						$out1 = shell_exec($cmdLn1);
						$_paste_files .= $this->folder.'export_sn_data_'.$this->surveyId.'_'.$lpid.'_'.$skey.'.dat ';
					}					
				}

				# združimo datoteke v eno
				$_orig_date = explode("_",$_original_data_file);
				$_orig_date = explode(".",$_orig_date[3]);
				
				#$_merged_file_name = $this->folder.'export_sn_data_'.$this->surveyId.'_'.$_orig_date[0].'.dat';
				$_merged_file_name = $_sn_filename;
				$tmp_merged_file_name = $this->folder.'tmp_export_sn_data_'.$this->surveyId.'_'.$lpid.'_'.$_orig_date[0].'.dat';
				if (IS_WINDOWS) {
					$cmdLn2 = 'paste -d "\n" '.$_paste_files .'> '.$tmp_merged_file_name;
				} 
                else {
					$cmdLn2 = 'paste -d \'\n\' '.$_paste_files. '>' .$tmp_merged_file_name;
				}
				$out2 = shell_exec($cmdLn2);

				# pripravimo filtre, za data datoteko, da odstranimo zapise ki nimajo imen ( če je nekdo dodal samo 2 imena nekdo pa 5)
				$_empty_name_filter = implode(' && ',$_empty_name_filter);
				
				# sfiltriramo zapise  ki nimajo imen 
				if (IS_WINDOWS) {
					$cmdLn3 = 'awk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.$_empty_name_filter.' { print $0 }" ' . $tmp_merged_file_name .' > ' . $_merged_file_name;
				} 
                else {
					$cmdLn3 = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.$_empty_name_filter.' { print $0 }\' ' . $tmp_merged_file_name . ' > ' . $_merged_file_name;
				}
				$out3 = shell_exec($cmdLn3);
			
				# pobrišemo odvečne datoteke
				foreach (explode(" ",$_paste_files) as $filename_to_delete) {
					if (trim($filename_to_delete) != '') {
						$this->SDF->deleteFile($filename_to_delete);
					}
				}

				if (trim($tmp_merged_file_name) != '') {
					$this->SDF->deleteFile($tmp_merged_file_name);
				}
			}
		}		
	}
	
	function displayFullTableCheckbox() {
		global $lang;
		session_start();
		echo '<label><input id="snCreateFullTable" name="snCreateFullTable" onclick="setSnDisplayFullTableCheckbox();" type="checkbox"'.($this->snCreateFullTable==true?' checked="checked"':'').'>Prikaži razširjeno tabelo</label>';
	}
	
	
	function outputSNDataFile() {
		global $lang;
		$this->countLoops();
		
		# forsamo novo kreiranje! malo slaba rešitev - mogoče dodat enako kontrolao na zadnjega userja v SN fajlih
		$this->deleteOldSnFiles();
		
		$_original_head_file = $this->headFileName;
		$_original_data_file = $this->dataFileName;
		
		# timestam head datoteke
		$_orig_date_h = explode("_",$_original_head_file);
		$_orig_date_h = explode(".",$_orig_date_h[3]);
		# združimo datoteke v eno
		$_orig_date_d = explode("_",$_original_data_file);
		$_orig_date_d = explode(".",$_orig_date_d[3]);

		
		$this-> displayFullTableCheckbox();
		
		# Tukaj začnemo loopat po glavnih  loopih in nardimo ločene tabele za vsak loop

		# zloopamo tolikokrat koliko imamo variabel za loop ( v loop_parent)
		if (count($this->sn_loop_parents) > 0) {
			foreach ($this->sn_loop_parents as $lpid => $sn_loop_parent) {

				# head ime datoteke za loop
				$_SN_head_file_name = $this->folder.'export_sn_header_'.$this->surveyId.'_'.$lpid.'_'.$_orig_date_h[0].'.dat';
				# data ime datoteke za loop
				$_sn_filename = $this->folder.'export_sn_data_'.$this->surveyId.'_'.$lpid.'_'.$_orig_date_d[0].'.dat';
				# začasno ime datoteke za loop		
				$_sn_tmp1 = $this->folder.'tmp_1_export_sn_data_'.$this->surveyId.'_'.$lpid.'_'.$_orig_date_d[0].'.dat';
				# če SN header in SN data datoteka obstaja
				if (!file_exists($_SN_head_file_name) || !file_exists($_sn_filename)) {
					$this->createSNDataFile($lpid, $sn_loop_parent,$_SN_head_file_name,$_sn_filename);
				}
				
				# če SN header in SN data datoteka obstaja
				if (file_exists($_SN_head_file_name) && file_exists($_sn_filename)) {
					
					# naložimo header
					$SN_HEADER = unserialize(file_get_contents($_SN_head_file_name));
					echo '<div id="tableContainer" class="tableContainer">';

					echo '<h3>'.$lang['srv_loop_for_variable'].' <b>['. $this->sn_loop_data[$lpid]['variable']. '] - '. $this->sn_loop_data[$lpid]['naslov']. '</b>  ('.$lang['srv_loop_antonucci_circle'].' '.$this->sn_loop_data[$lpid]['antonucci'].')</h3>';
					

                    // TABELA
                    echo '<table id="dataTable" border="0" cellpadding="0" cellspacing="0" width="100%" class="scrollTable no_wrap_td social_network">';
					

                    // COLGROUP - Nastavimo colgroup, da na njega vezemo vse sirine v tabeli, zaradi resizinga stolpcev
                    echo '<colgroup>';

					$spr_cont = 0;
					foreach ($SN_HEADER AS $spid => $spremenljivka) {
						
                        if ($spr_cont > 0 && $spid != 'uid' && count($spremenljivka['grids']) > 0) {
								
                            foreach ($spremenljivka['grids'] AS $gid => $grid) {
                                
                                if (count ($grid['variables']) > 0) {
                                    
                                    foreach ($grid['variables'] AS $vid => $variable ){
                                        echo '<col>';
                                    }
                                }
                            }
						}
						$spr_cont++;
					}	

                    echo '</colgroup>';


                    // THEAD
                    echo '<thead class="fixedHeader">';
					echo '<tr>';
		
					# dodamo skrit stolpec uid
					echo '<th class="data_uid">&nbsp;</th>';
					
					$spr_cont = 0;
					foreach ($SN_HEADER AS $spid => $spremenljivka) {
						
                        if ($spr_cont > 0 && $spid != 'uid') {
							echo '<th colspan="'.$spremenljivka['cnt_all'].'" title="'.$spremenljivka['naslov'].'">';
							echo '<div class="headerCell">'.$spremenljivka['naslov'].'</div>';
							echo '</th>';
						}

						$spr_cont++;
					}

					# nova vrstica
					echo '</tr><tr>';
					
					# dodamo skrit stolpec uid
					echo '<th class="data_uid">&nbsp;</th>';
								
					$spr_cont = 0;
					foreach ($SN_HEADER AS $spid => $spremenljivka) {
						if ($spr_cont > 0 && $spid != 'uid') {
							if (count($spremenljivka['grids']) > 0) {
								
                                foreach ($spremenljivka['grids'] AS $gid => $grid) {
									echo '<th colspan="'.$grid['cnt_vars'].'" title="'.$grid['naslov'].'">';
									echo '<div class="headerCell">'.$grid['naslov'].'</div>';
									echo '</th>';
								}
							}	
						}

						$spr_cont++;
					}

					# nova vrstica
					echo '</tr><tr>';
					
					## dodamo skrit stolpec uid
					echo '<th class="data_uid">&nbsp;</th>';
								
					$spr_cont = 0;
					foreach ($SN_HEADER AS $spid => $spremenljivka) {
						
                        if ($spr_cont > 0 && $spid != 'uid' && count($spremenljivka['grids']) > 0) {
								
                            foreach ($spremenljivka['grids'] AS $gid => $grid) {
                                
                                if (count ($grid['variables']) > 0) {
                                    
                                    foreach ($grid['variables'] AS $vid => $variable ){
                                        echo '<th title="'.$variable['naslov'].($variable['other'] ? '&nbsp;(text)' : '').'">';
                                        echo '<div class="dataCell">'.$variable['naslov'];
                                        
                                        if ($variable['other'] == 1) {
                                            echo '&nbsp;(text)';
                                        }

                                        echo '</div>';
                                        echo '</th>';
                                    }
                                }
                            }
						}
						$spr_cont++;
					}			
					echo'</tr>';
					echo '</thead>';


					// TBODY - dodamo podatke
					if (file_exists($_sn_filename)) {
						
						// zamenjamo | z </td><td>
						if (IS_WINDOWS) {
							$cmdLn3 = 'sed "s*'.STR_DLMT.'*</td><td>*g" '.$_sn_filename.' > '.$_sn_tmp1;
						} else {
							$cmdLn3 = 'sed \'s*'.STR_DLMT.'*</td><td>*g\' '.$_sn_filename.' > '.$_sn_tmp1;
						}
						$out3 = shell_exec($cmdLn3);
		
						echo '<tbody class="scrollContent">';

						$file_handler = fopen ($_sn_tmp1, 'r');
					    while ($line = fgets ($file_handler)) {
					    	echo '<tr>';
							echo '<td class="data_uid">'.$line.'</td>';
                            echo '</tr>'; 
					    }

					    echo '</tbody>';
		
						if ($file_handler) {	
							fclose($file_handler);
						}
						
						# pobrišemo tmp falj
						if (trim($_sn_tmp1) != '') {
							$this->SDF->deleteFile($_sn_tmp1);
						}
					}

                    echo '</table>';

                    echo '</div>';
				}	
		
			}
		}
		
	}
	/** Pobriše morebitne stare SN daoteke 
	 * 
	 */
	function deleteOldSnFiles() {
		if ($this->surveyId > 0) {
			
			# odstranimo morebitne SN datoteke - header
			$files = glob($this->folder.'export_sn_header_'.$this->surveyId.'_*.dat');
			if(count($files ) > 0) {
				foreach ($files AS $file) {
					unlink($file);
				}
			}
			# odstranimo morebitne SN datoteke - data
			$files = glob($this->folder.'export_sn_data_'.$this->surveyId.'_*.dat');
			if(count($files ) > 0) {
				foreach ($files AS $file) {
					unlink($file);
				}
			}
			
		}
	}
	
	/* Tukaj pripravimo redosled in prikaz glavnih spremenljivk
	 * 
	 */
	function getCleanHeader() {
		# poiščemo skevence za vse variable loopa
		$header = $this -> _HEADERS;
		$cleanHeader = array();
		if (count($header) > 0) {
			foreach ($header AS $spr_id => $spremenljivka) {

				if ($this->_VARS[VAR_DATA] == '1') {
					$add_data = true;
				} else {
					$add_data = false;
				}
				
				# preverimo ali delamo kompleksno tabelo al samo simpl
				if ($this->snCreateFullTable == false) {
					$add_data = $add_data && ((int)$spremenljivka['loop_parent'] > 0 || (int)$spremenljivka['parent_loop_id'] > 0);
				}
				if ( $spremenljivka['tip'] == 'm' || $spremenljivka['tip'] == 'sm') {
					$add_data = false;
					switch ($spremenljivka['variable']) {
						case 'uid':
						case 'recnum':
							$add_data = true;
						break;
						case 'code':
							# ce prikazujemo sistemske ne prikazujemo recnumber
							if (!$this->_VARS[VAR_SHOW_SYSTEM] && $this->_VARS[VAR_META] && $this->_VARS[VAR_METAFULL]) {
								$add_data = true;
							}
							break;
						case 'status':
						case 'lurker':
							if ($this->_VARS[VAR_META] && $this->_VARS[VAR_METAFULL]) {
								$add_data = true;
							}
							break;
						case 'relevance':
							if ($this->_VARS[VAR_RELEVANCE] && $this->canDisplayRelevance) {
								$add_data = true;
							}
							break;
						case 'invitation':
							if ($this->_VARS[VAR_EMAIL]) {
								$add_data = true;
							}
							break;
						case 'testdata':
							$header = $this->SDF->getHeader();
							if (isset($header['testdata'])) {
								$add_data = true;
							}
							break;
						case 'smeta':
						case 'meta':
							if ($this->_VARS[VAR_METAFULL]) {
								$add_data = true;
							}
							break;
						case 'itime':
							if ($this->showItime == true) {
								$add_data = true;
							}
							break;
						}
					}
					if ($spremenljivka['hide_system'] == '1') {
						$add_data = false;
					}
					if ($add_data == true ) {
						$cleanHeader[$spr_id] = $spremenljivka;
					}
			}
		}
		return $cleanHeader;
	}
}
?>