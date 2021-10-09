<?php

/**
* 
* V tem classu so funkcije, ki se ticejo appenda in merga podatkov
* 
*/

class SurveyAppendMerge {
	
	private $anketa;
	
	private $db_table = '';
	
	
	/**
	* 
	* 
	* @param int $anketa ID ankete
	*/
	function __construct ($anketa) {
		
		$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
	}
	
	function display ($merge = false) {
		
		if ( count($_POST) == 0 ) {
			
			echo '<div id="inv_import">';
			$this->displayAppendMerge($merge);
			echo '</div>';
		
		} else {
			
			$this->do_append_merge();
			
		}
	}
	
	function displayAppendMerge ( $merge = false ) {
		global $lang, $site_path, $site_url;
		
		$field_list = array();
		
		$sql = sisplet_query("SELECT s.id, s.variable, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip IN (1, 2, 3, 7, 8, 21) ORDER BY g.vrstni_red, s.vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {
			
			$field_list[$row['id']] = $row['variable'] . ' ('.($row['tip']==1?'radio':'').($row['tip']==2?'checkbox':'').($row['tip']==3?'dropdown':'').($row['tip']==21?'text':'').($row['tip']==7?'number':'').($row['tip']==8?'date':'').')';
		}
		
		$import_type = isset($_POST['import_type']) ? (int)$_POST['import_type'] : 2;
		session_start();
		
		
		// Append
		if (!$merge){

			echo '<fieldset><legend>'.$lang['srv_data_subnavigation_append'].'</legend>';

			echo '<form id="inv_recipients_upload_form" name="resp_uploader" enctype="multipart/form-data" method="POST" action="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m='.$_GET['m'].'">';
			echo '<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />';
			echo '<input type="hidden" name="do" value="0">';
			echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
			
			# sporočilo za personalizirana e-vabila in respondente iz baze
			echo $lang['srv_append_note'];
			
			// Korak 1
			echo $lang['srv_append_step1'];
			echo '<div class="append_step">';

			echo $lang['srv_append_step1_note'];
			
			echo '<div id="inv_field_container">';

			echo '<ul class="connectedSortable">';
			$field_lang = array();
			if (count($field_list ) > 0) {
				foreach ($field_list AS $field => $text_label) {
					# tukaj polja niso izbrana
					$is_selected = false;  
					
					# če je polje obkljukano
					$css =  $is_selected ? ' class="inv_field_enabled"' : '';
					

					# labela sproži klik checkboxa
					$label_for = ' for="'.$field.'_chk"';
					 
					echo '<li id="'.$field.'"'.$css.'>';
					echo '<input id="'.$field.'_chk" type="checkbox" name="fields[]" value="'.$field.'" class="inv_checkbox' . $hidden_checkbox . '"'.($is_selected == true ? ' checked="checked"' : '').' '.($merge?' onclick="merge_getItems();"':'').'>';
					echo '<label'.$label_for.' style="display: inline">'.$text_label.'</label>';
					echo '</li>';
					
					if ($is_selected == true) {
						$field_lang[] = $text_label;
					}
				}
			}
			echo '</ul><br />';
			echo '</div>';
			echo '</div>';
			
			
			// Korak 2
			echo $lang['srv_append_step2'];
			echo '<div class="append_step">';
			
			echo '<script type="text/javascript">';
			echo "$('ul.connectedSortable').sortable({update : function () { append_refreshFieldsList(); }, forcePlaceholderSize: 'true',tolerance: 'pointer',placeholder: 'inv_field_placeholder',});";
			echo '</script>';
			
			echo '<span><label><input name="inv_import_type" id="inv_import_type0" type="radio" value="0" onclick="append_change_import_type(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m='.$_GET['m'].'\');" checked="checked">';
			echo ''.$lang['srv_iz_seznama'].'</label></span>';
			echo '<span><label><input name="inv_import_type" id="inv_import_type1" type="radio" value="1" onclick="append_change_import_type(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m=upload_xls\');">';
			echo ''.$lang['srv_iz_excela'].'</label></span>';
			echo '<br class="clr"/>';		
			echo '<br class="clr"/>';		

			echo '<input type="hidden" name="do_merge" value="0" id="do_merge">';
			
			echo '</div>';

						
			# iz seznama
			echo '<div id="inv_import_list"'.($import_type != 1 ? '' : ' style="display:none"').'>' ;
			
			// Korak 3
			echo $lang['srv_append_step3'];
			echo '<div class="append_step">';
			
			echo $lang['srv_append_step3_note'];
			
			echo '<span class="inv_sample" >';
			echo $lang['srv_inv_recipiens_sample'].'&nbsp;</span><span class="inv_sample">';
			echo $lang['srv_inv_recipiens_sample1'];
			echo '</span>';
			echo '<br class="clr" />';
			echo '</span>';
			echo '<br class="clr" />'.$lang['srv_inv_recipiens_fields'].' <span id="inv_field_list" class="inv_type_0">';
			echo implode(',',$field_lang);
			echo '</span>';
			echo '<br class="clr" /><textarea id="inv_recipients_list" cols="50" rows="9" name="inv_recipients_list" style="margin-bottom: 7px;">';
			if (is_array($recipients_list) && count($recipients_list) > 0 ) {
				echo implode("\n",$recipients_list);
			}
			echo '</textarea>';

			echo '</div>';
			echo '</div>';	# id=inv_import_list
			
			
			# iz datoteke
			echo '<div id="inv_import_file"'.($import_type == 1 ? '' : ' style="display:none"').'>' ;
						
			// Korak 3
			echo $lang['srv_append_step3_xls'];
			echo '<div class="append_step">';
						
			echo $lang['srv_mailing_upload_list'];
			echo ' <input type="file" name="recipientsFile" id="recipientsFile" />';
			
			echo '<br /><br />';
			
			echo $lang['srv_excel_upload_note'];
			
			echo '</div>';
			echo '</div>'; # id=inv_import_file

			echo '<br class="clr" />';
			echo '<span id="inv_upload_recipients_nosbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" onclick="append_submit(); return false;">'.$lang['srv_inv_btn_add_recipients_add'].'</a></span>';
			
			$d = new Dostop();
			if ($d->checkDostopSub('edit')){
				echo '<span style="margin: 5px 20px; line-height:24px">';
				printf($lang['src_coding_alert'], $this->anketa);
				echo '</span>';
			}
						
			echo '</form>';
			
			echo '<br class="clr"/>';	
			
			//echo '</div>'; # id=inv_import_list_container
			
			echo '</fieldset>';
		
		}
		// Merge
		else{
		
			echo '<fieldset><legend>'.$lang['srv_data_subnavigation_merge'].'</legend>';

			echo '<form id="inv_recipients_upload_form" name="resp_uploader" enctype="multipart/form-data" method="POST" action="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m='.$_GET['m'].'">';
			echo '<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />';
			echo '<input type="hidden" name="do" value="0">';
			echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
			
			# sporočilo za personalizirana e-vabila in respondente iz baze
			echo $lang['srv_merge_note'];		
			
			// Korak 1
			echo $lang['srv_merge_step1'];
			echo '<div class="append_step">';

			echo $lang['srv_merge_step1_note'];
			
			echo '<div id="inv_field_container">';

			echo '<ul class="connectedSortable">';
			$field_lang = array();
			if (count($field_list ) > 0) {
				foreach ($field_list AS $field => $text_label) {
					# tukaj polja niso izbrana
					$is_selected = false;  
					
					# če je polje obkljukano
					$css =  $is_selected ? ' class="inv_field_enabled"' : '';
					

					# labela sproži klik checkboxa
					$label_for = ' for="'.$field.'_chk"';
					 
					echo '<li id="'.$field.'"'.$css.'>';
					echo '<input id="'.$field.'_chk" type="checkbox" name="fields[]" value="'.$field.'" class="inv_checkbox' . $hidden_checkbox . '"'.($is_selected == true ? ' checked="checked"' : '').' '.($merge?' onclick="merge_getItems();"':'').'>';
					echo '<label'.$label_for.' style="display: inline">'.$text_label.'</label>';
					echo '</li>';
					
					if ($is_selected == true) {
						$field_lang[] = $text_label;
					}
				}
			}
			echo '</ul><br />';
			echo '</div>';
			echo '</div>';
			
			
			// Korak 2
			echo $lang['srv_merge_step2'];
			echo '<div class="append_step">';
			
			echo '<script type="text/javascript">';
			echo "$('ul.connectedSortable').sortable({update : function () { append_refreshFieldsList(); }, forcePlaceholderSize: 'true',tolerance: 'pointer',placeholder: 'inv_field_placeholder',});";
			echo '</script>';
			
			echo '<span><label><input name="inv_import_type" id="inv_import_type0" type="radio" value="0" onclick="append_change_import_type(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m='.$_GET['m'].'\');" checked="checked">';
			echo ''.$lang['srv_iz_seznama'].'</label></span>';
			echo '<span><label><input name="inv_import_type" id="inv_import_type1" type="radio" value="1" onclick="append_change_import_type(\''.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m=upload_xls\');">';
			echo ''.$lang['srv_iz_excela'].'</label></span>';
			
			echo '<br class="clr"/>';	
			echo '<br class="clr"/>';	
			
			echo '</div>';		
			

			// Korak 3
			echo $lang['srv_merge_step3'];
			echo '<div class="append_step">';

			echo '<input type="hidden" name="do_merge" value="1" id="do_merge">';
			echo '<p>'.$lang['srv_izberite_identifikator'].'</p>';
			echo '<ul id="merge">';
			echo '</ul>';
			echo '</div>';
			
			
			# iz seznama
			echo '<div id="inv_import_list"'.($import_type != 1 ? '' : ' style="display:none"').'>' ;
			
			// Korak 4
			echo $lang['srv_merge_step4'];
			echo '<div class="append_step">';
			
			echo $lang['srv_merge_step4_note'];

			echo $lang['srv_inv_recipiens_sample'].'&nbsp;</span><span class="inv_sample">';
			echo $lang['srv_inv_recipiens_sample1'];
			echo '</span>';
			echo '<br class="clr" />';
			echo '</span>';
			echo '<br class="clr" />'.$lang['srv_inv_recipiens_fields'].' <span id="inv_field_list" class="inv_type_0">';
			echo implode(',',$field_lang);
			echo '</span>';
			echo '<br class="clr" /><textarea id="inv_recipients_list" cols="50" rows="9" name="inv_recipients_list" style="margin-bottom: 7px;">';
			if (is_array($recipients_list) && count($recipients_list) > 0 ) {
				echo implode("\n",$recipients_list);
			}
			echo '</textarea>';
			echo '<br class="clr"/>';
			
			echo '</div>';
			echo '</div>';	# id=inv_import_list
			
			# iz datoteke
			echo '<div id="inv_import_file"'.($import_type == 1 ? '' : ' style="display:none"').'>' ;
			
			// Korak 4
			echo $lang['srv_merge_step4'];
			echo '<div class="append_step">';
			
			echo $lang['srv_mailing_upload_list'];
			echo ' <input type="file" name="recipientsFile" id="recipientsFile" />';
			
			echo '<br class="clr" /><br />';
			
			echo $lang['srv_excel_upload_note'];
			
			echo '</div>';
			echo '</div>'; # id=inv_import_file

			
			echo '<br class="clr" />';
			echo '<span id="inv_upload_recipients_nosbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" onclick="append_submit(); return false;">'.$lang['srv_inv_btn_add_recipients_add'].'</a></span>';
			
			$d = new Dostop();
			if ($d->checkDostopSub('edit')){
				echo '<span style="margin: 5px 20px; line-height:24px">';
				printf($lang['src_coding_alert'], $this->anketa);
				echo '</span>';
			}
			
			echo '</form>';
			
			echo '<br class="clr"/>';	

			echo '</fieldset>';
		}
	}
	
	function do_append_merge() {
		global $lang;
		global $site_url;
		global $global_user_id;
		
		# dodamo uporabnike
		$fields = $_POST['fields'];
		$recipients_list = mysql_real_unescape_string( $_POST['inv_recipients_list'] );
		$merge = (int)$_POST['merge'];
		$do_merge = (int)$_POST['do_merge'];
		$import_type = (int)$_POST['inv_import_type'];
		if ($_POST['do']=='1') $do = true; else $do = false;
		
		
		// ce uploadamo datoteko
		if ($import_type == 1) {
			
			$file_name = $_FILES["recipientsFile"]["tmp_name"];
			
			$fh = @fopen($file_name, "rb");
                    if ($fh) {
                                    $recipients_list = fread($fh, filesize($file_name));
                            fclose($fh);
                    }

                    if (isset ($_POST['recipientsDelimiter'])) {
                        $recipients_list = str_replace($_POST['recipientsDelimiter'], "|~|", $recipients_list);
                    }
                    else {
                        $recipients_list = str_replace(",", "|~|", $recipients_list);
                    }
                }
		
		// append
		if ($do_merge == 0) {
			
			$result = $this->appendData($do, $fields, $recipients_list);
			
			if ($result == -1) {
				$output = $lang['srv_append-merge_required_field'];
			} elseif ($result == -3) {
				$output = $lang['srv_append-merge_required_data'];
			} elseif ($result >= 0) {
				$output = $lang['srv_append-merge_added_1'].' '.$result.' '.$lang['srv_append-merge_added_2'];
			}
			
		// merge
		} elseif ($do_merge == 1) {
			
			$result = $this->mergeData($do, $fields, $recipients_list, $merge);
			
			if ($result == -1) {
				$output = $lang['srv_append-merge_required_field'];
			} elseif ($result == -2) {
				$output = $lang['srv_append-merge_required_id'];
			} elseif ($result == -3) {
				$output = $lang['srv_append-merge_required_data'];
			} elseif ($result >= 0) {
				$output = $lang['srv_append-merge_merged_1'].' '.$result.' '.$lang['srv_append-merge_merged_2'];
			} 
			
		}
		
		// prikazemo obvestilo in formo za potrditev	
		if ($import_type == 1){
			if ($do_merge == 1)
				echo '<fieldset><legend>'.$lang['srv_data_subnavigation_merge'].'</legend>';
			else
				echo '<fieldset><legend>'.$lang['srv_data_subnavigation_append'].'</legend>';
		}
		
		if ( ! $do ) {
			
			// napaka
			if ($result <= 0) {
				echo '<h2>'.$lang['error'].'</h2>';
				
				if ($result < 0)
					echo '<p>'.$output.'</p>';
				else 
					echo '<p>'.$lang['srv_append-merge_error_value'].'</p>';
				
				echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_gray" onclick="append_submit_close(); return false;"><span>'.$lang['back'].'</span></a></span>';
				
			} else {
								
				echo '<h2>'.$lang['srv_potrditev'].'</h2>';
				
				if ($do_merge == 0)
					echo '<p>'.$lang['srv_append-merge_process_1'].' '.$result.' '.$lang['srv_append-merge_process_2'].'. '.'</p>';
				else
					echo '<p>'.$lang['srv_append-merge_process_o_1'].' '.$result.' '.$lang['srv_append-merge_process_o_2'].'</p>';
				
				
				echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_gray" onclick="append_submit_close(); return false;"><span>'.$lang['srv_cancel'].'</span></a></span>';
				echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange" onclick="append_submit(1); return false;"><span>'.$lang['srv_potrdi'].'</span></a></span>';
			}
			
		
		// shranjeno, prikazemo resultat
		} else {
			
			echo '<h2>'.$lang['fin_import_ok'].'</h2>';
			
			echo '<p>'.$output.'</p>';
			echo '<p>'.$lang['srv_append-merge_fin'].'</p>';
			
			echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" href="index.php?anketa='.$this->anketa.'&a=data'.(count($this->usr_ids) < 100?'&highlight_usr='.implode('-', $this->usr_ids).'':'').'"><span>'.$lang['data_show'].'</span></a></span>';
			
		}
		
		if ($import_type == 1){
			echo '<br /><br />';
			echo '</fieldset>';
		}
	}
	
	private $usr_ids = array();
	
	function appendData($do = true, $fields, $rawdata) {
		if ($do) TrackingClass::update($this->anketa, 1);
		
		if (false) {
			echo 'dumping for append:';
			echo '<pre>';
			print_r($fields);
			echo '</pre>';
			echo '<pre>';
			print_r($rawdata);
			echo '</pre>';
		}
		
		$data = explode("\n", $rawdata);
		
		if (count($fields) <= 0) return -1;
		if ($rawdata == '') return -3;
		if (count($data) <= 0) return -3;
		
		$tip = array();
		$vre_id = array();
		$sql = sisplet_query("SELECT id, tip FROM srv_spremenljivka WHERE id IN (".implode(',', $fields).")");
		while ($row = mysqli_fetch_array($sql)) {
			$tip[$row['id']] = $row['tip'];
			
			if ( in_array($row['tip'], array(1, 2, 3)) ) {
				
				$s = sisplet_query("SELECT id, variable FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				while ($r = mysqli_fetch_array($s)) {
					$vre_id[$row['id']][$r['variable']] = $r['id'];
				}		
						
			} elseif ( in_array($row['tip'], array(7, 21,8)) ) {
				// v tekstovno spremenljivko pisemo v prvo polje
				$s = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				$r = mysqli_fetch_array($s);
				$vre_id[$row['id']] = $r['id'];
			}
		}
		$added = 0;
		foreach ($data AS $dataline) {
			$line = explode(',', $dataline);
				
			foreach ($line AS $key => $val) {
				$line[$key] = trim($val);
			}
				
			$added += $this->insertLine($line,$fields,$tip,$vre_id,$do);
		}
		
		if ($do) SurveyPostProcess::forceRefreshData($this->anketa);
		
		return $added;
	}
	
	function insertLine($line,$fields,$tip,$vre_id,$do,$fromExcel=false){
	
		$added=0;
		
		// izberemo random hash, ki se ni v bazi
        $ip = GetIP();
		do {
			$rand = md5(mt_rand(1, mt_getrandmax()).'@'.$ip);
			$sql = sisplet_query("SELECT id FROM srv_user WHERE cookie = '$rand'");
		} while (mysqli_num_rows($sql) > 0);
			
		// nov user
		if ($do) {
			$sql = sisplet_query("INSERT INTO srv_user (id, ank_id, cookie, time_insert, time_edit, last_status, lurker) VALUES ('', '$this->anketa', '$rand', NOW(), NOW(), '5', '0')");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			$usr_id = mysqli_insert_id($GLOBALS['connect_db']);
		}
			
		if ($usr_id > 0 || $do==false) {
		
			$this->usr_ids[] = $usr_id;
		
			$i = 0;
			foreach ($fields AS $id) {
					
				if ($do) {
					
					if ( in_array($tip[$id], array(1, 3)) ) {
							
						$vre = $vre_id[$id][$line[$i]];
							
						if ($vre != '') {
							$sql = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id, loop_id) VALUES ('$id', '$vre', '$usr_id', NULL)");
							if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
						}
		
					} elseif ( in_array($tip[$id], array(2)) ) {
		
						$checks = explode(' ', $line[$i]);
							
						if ( count($checks) > 0 ) {
		
							$sql = sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$id' AND usr_id='$usr_id' AND loop_id IS NULL");
							if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
							foreach ($checks AS $k => $v) {
									
								$vre = $vre_id[$id][$v];
									
								if ($vre != '') {
		
									$sql = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id, loop_id) VALUES ('$id', '$vre', '$usr_id', NULL)");
									if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
								}
		
							}
						}
							
					} elseif ( in_array($tip[$id], array(7, 21, 8)) ) {
							
						if ($tip[$id] == 21) {
							$vre = $vre_id[$id];
						} elseif ($tip[$id] == 7 || $tip[$id] == 8) {
							$vre = 0;
						}

						if ($tip[$id] == 8) 
						{
							#datum prekodiramo v pravo obliko
							$value = PHPExcel_Style_NumberFormat::toFormattedString($line[$i], "D.M.YYYY");
						}
						else
						{
							$value = $line[$i];
						}							
						$sql = sisplet_query("INSERT INTO srv_data_text".$this->db_table." (id, spr_id, vre_id, text, text2, usr_id, loop_id) VALUES ('', '$id', '$vre', '$value', '', '$usr_id', NULL)");
						if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
					}
						
				}
					
				$i++;
			}
			$added++;
		}
		return $added;
	}
	
	function mergeData($do = true, $fields, $rawdata, $merge) {
		if ($do) TrackingClass::update($this->anketa, 1);
		
		if (false) {
			echo 'dumping for merge:';
			echo '<pre>';
			print_r($fields);
			echo '</pre>';
			echo '<pre>';
			print_r($rawdata);
			echo '</pre>';
			echo '<pre>';
			print_r($merge);
			echo '</pre>';
		}
		
		if (count($fields) <= 0) return -1;
		if ($merge <= 0) return -2;
		
		$merge_key = array_keys($fields, $merge); 
		$merge_key = $merge_key[0];
		
		$data = explode("\n", $rawdata);
		
		if ($rawdata == '') return -3;
		if (count($data) <= 0) return -3;
		
		$tip = array();
		$vre_id = array();
		$sql = sisplet_query("SELECT id, tip FROM srv_spremenljivka WHERE id IN (".implode(',', $fields).")");
		while ($row = mysqli_fetch_array($sql)) {
			$tip[$row['id']] = $row['tip'];
			
			if ( in_array($row['tip'], array(1, 2, 3)) ) {
				
				$s = sisplet_query("SELECT id, variable FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				while ($r = mysqli_fetch_array($s)) {
					$vre_id[$row['id']][$r['variable']] = $r['id'];
				}
						
			} elseif ( in_array($row['tip'], array(7, 21, 8)) ) {
				// v tekstovno spremenljivko pisemo v prvo polje
				$s = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				$r = mysqli_fetch_array($s);
				$vre_id[$row['id']] = $r['id'];
			}
		}
		
		$merge_tip = $tip[$merge];
		
		$merged = 0;
		
		foreach ($data AS $dataline) {
			
			$line = explode(',', $dataline);
			
			foreach ($line AS $key => $val) {
				$line[$key] = trim($val);
			}
			
			if ($line[$merge_key] != '') {
				
				// poiscemo userja za merge
				if ( in_array($merge_tip, array(1, 2, 3)) ) {
					
					$sqlu = sisplet_query("SELECT usr_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$merge' AND vre_id='".$vre_id[$merge][$line[$merge_key]]."'");
					
				} elseif ( in_array($merge_tip, array(7, 21, 8)) ) {
					if ($merge_tip == 21) {
						$vre = $vre_id[$merge];
					} elseif ($merge_tip == 7 || $merge_tip == 8) {
						$vre = 0;
					}
					
					$sqlu = sisplet_query("SELECT usr_id FROM srv_data_text".$this->db_table." WHERE spr_id='$merge' AND vre_id='$vre' AND text='$line[$merge_key]'");
				}
				if (!$sqlu) echo mysqli_error($GLOBALS['connect_db']);
				
				while ($rowu = mysqli_fetch_array($sqlu)) {
				
					if ($do) {
						$usr_id = $rowu['usr_id'];
						
						$s = sisplet_query("UPDATE srv_user SET time_edit=NOW() WHERE id='$usr_id'");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
						
						$this->usr_ids[] = $usr_id;
					}
					
					$i = 0;
					foreach ($fields AS $id) {
					
						if ($id != $merge) {
							
							if ($do) {
								
								if ( in_array($tip[$id], array(1, 3)) ) {
							
									$vre = $vre_id[$id][$line[$i]];

									if ($vre != '') {
										$sql = sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$id' AND usr_id='$usr_id' AND loop_id IS NULL");
										if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
										$sql = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id, loop_id) VALUES ('$id', '$vre', '$usr_id', NULL)");
										if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
									}
							
								} elseif ( in_array($tip[$id], array(2)) ) {
									
									$checks = explode(' ', $line[$i]);
									
									if ( count($checks) > 0 ) {
										
										$sql = sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$id' AND usr_id='$usr_id' AND loop_id IS NULL");
										if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
										
										foreach ($checks AS $k => $v) {
											
											$vre = $vre_id[$id][$v];
											
											if ($vre != '') {
												
												$sql = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id, loop_id) VALUES ('$id', '$vre', '$usr_id', NULL)");
												if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
											}
										}
									}
								
								} elseif ( in_array($tip[$id], array(7, 21, 8)) ) {
								
									if ($tip[$id] == 21) {
										$vre = $vre_id[$id];
									} elseif ($tip[$id] == 7 || $tip[$id] == 8) {
										$vre = 0;
									}
								
									// ker je primary nastavljen na ID, moramo najprej pobrisat
									$sql = sisplet_query("DELETE FROM srv_data_text".$this->db_table." WHERE spr_id='$id' AND vre_id='$vre' AND usr_id='$usr_id'");
									if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
									$sql = sisplet_query("INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, text, usr_id) VALUES ('$id', '$vre', '$line[$i]', '$usr_id')");
									if (!$sql) echo mysqli_error($GLOBALS['connect_db']);						
								}						
							}						
						}
						
						$i++;
					}
					$merged++;
				}
			}
		}
		
		if ($do) SurveyPostProcess::forceRefreshData($this->anketa);
		
		return $merged;
	}
	
	function upload_xls(){
		global $lang, $site_url,$site_path,$global_user_id;
		
		echo '<fieldset><legend>'.$lang['srv_data_subnavigation_append'].'</legend>';	
		
		if (isset($_POST['fields']) && count($_POST['fields']) > 0){
		
			if (isset($_FILES['recipientsFile']) 
					&& is_array($_FILES['recipientsFile'])
					&& isset($_FILES['recipientsFile']['size']) && $_FILES['recipientsFile']['size'] > 0
					&& (int)$_FILES['recipientsFile']['error'] == 0
					&& (pathinfo($_FILES['recipientsFile']['name'],PATHINFO_EXTENSION) == 'xls' || pathinfo($_FILES['recipientsFile']['name'],PATHINFO_EXTENSION) == 'xlsx') )
			{
				
				$orig_parts = pathinfo($_FILES["recipientsFile"]["name"]);
				$path_parts = pathinfo($_FILES["recipientsFile"]["tmp_name"]);
				
				$fileName = $this->anketa.'_'.$global_user_id.'_'.$path_parts['filename'].'.'.$orig_parts['extension'];	
				$file = $site_path.'admin/survey/tmp/'.$fileName;
				$move = move_uploaded_file($_FILES['recipientsFile']['tmp_name'], $file);
				
				if ($move == true){
					#spremenljivke
					$field_list = array();
					$sql = sisplet_query("SELECT s.id, s.variable, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip IN (1, 2, 3, 7, 8, 21) ORDER BY g.vrstni_red, s.vrstni_red");
					while ($row = mysqli_fetch_array($sql)) {
						$field_list[$row['id']] = $row['variable'] . ' ('.($row['tip']==1?'radio':'').($row['tip']==2?'checkbox':'').($row['tip']==3?'dropdown':'').($row['tip']==21?'text':'').($row['tip']==7?'number':'').($row['tip']==8?'date':'').')';
					}
					
					include_once("./excel/PHPExcel.php");
					require_once("./excel/PHPExcel/IOFactory.php");
						
					$result = array();
					$objPHPExcel = PHPExcel_IOFactory::load($file);
					$objWorksheet = $objPHPExcel->getActiveSheet();
					
					//  Get worksheet dimensions
					$highestRow = $objWorksheet->getHighestRow();
					$highestColumn = $objWorksheet->getHighestColumn();
					$columns = array();
					
					$highestColumn++;
					for ($column = 'A'; $column != $highestColumn; $column++) 
					{
					    $columns[] = $column;
					}

					echo '<form id="append_xml" name="resp_uploader" enctype="multipart/form-data" method="POST" action="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=data&m=append_xls">';
					echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
					echo '<input type="hidden" name="file" value="'.$fileName.'">';
					foreach ($_POST['fields'] AS $field)
					{
						echo '<input id="'.$field.'_chk" type="hidden" name="fields[]" value="'.$field.'" />';
					}
						
					echo $lang['srv_append_xls_note'];
										
					echo '<div id="inv_field_container">';
					echo '<div class="append_step">';
					
					echo '<ul class="connectedSortable">';
					foreach ($_POST['fields'] AS $spr_id)
					{
						echo '<li class="inv_field_enabled"><label>'.$field_list[$spr_id].'</label></li>';
					}
					echo '</ul>';
					echo '</div>';
					echo '</div>';
						
						
					if (count($columns) > 0){
						echo $lang['srv_append_xls_step1'];
						
						echo '<div id="inv_field_container">';
						echo '<div class="append_step">';
						echo '<ul class="connectedSortable">';
						foreach ($columns AS $column){
							echo '<li><label><input type="checkbox" name="xls_column[]" value="'.$column.'">'.$column.'</label></li>';
						}
						echo '</ul>';
						echo '</div>';
						echo '</div>';
					}
					
					
					echo $lang['srv_append_xls_step1'];
					
					echo '<div class="append_step">';
					echo '<p>'.$lang['srv_append_xls_step2_begin'].': <input name="start_row" type="number" min="1" max="'.(int)$highestRow.'" value="1"></p>';
					echo '<p>'.$lang['srv_append_xls_step2_end'].': <input name="end_row" type="number" min="1" max="'.(int)$highestRow.'" value="'.(int)$highestRow.'"></p>';
					echo '<span id="inv_upload_recipients_nosbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" onclick="$(\'#append_xml\').submit();">'.$lang['srv_inv_btn_add_recipients_add'].'</a></span>';
					echo '<br /><br />';
					echo '</div>';
					
					
					echo '</form>';
				}
			}
			elseif(pathinfo($_FILES['recipientsFile']['name'],PATHINFO_EXTENSION) != 'xls' && pathinfo($_FILES['recipientsFile']['name'],PATHINFO_EXTENSION) != 'xlsx'){
				echo $lang['srv_iz_excela_xls_error'];
			}
			else{
				echo 'File error. #'.(int)$_FILES['recipientsFile']['error'];
			}
		}
		else
		{
			echo 'No list';
		}
		
		echo '</fieldset>';
	}
	
	function append_xls() {
		global $lang, $site_url,$site_path,$global_user_id;

		$fileName = $_POST['file'];
		$file = $site_path.'admin/survey/tmp/'.$fileName;
		
		$columns = $_POST['xls_column'];
		$fields = $_POST['fields'];
		
		$do_merge = (int)$_POST['do_merge'];
		
		if (file_exists($file)
				&& is_array($columns) && count($columns ) > 0
				&& is_array($fields) && count($fields ) > 0)
		{
			$do = 1;
			
			include_once("./excel/PHPExcel.php");
			require_once("./excel/PHPExcel/IOFactory.php");
		
			$objPHPExcel = PHPExcel_IOFactory::load($file);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			
			//  Get worksheet dimensions
			$highestRow = $objWorksheet->getHighestRow();
			$highestColumn = $objWorksheet->getHighestColumn();

			
				
			$start_row = min($_POST['start_row'],$highestRow);
			$end_row = min($_POST['end_row'],$highestRow);
			if ($end_row < $start_row)
			{
				$end_row = $start_row;
			}
			
			#podatki spremenljivk
			$ids = array();
			$tip = array();
			$vre_id = array();
			$sql = sisplet_query("SELECT id, tip FROM srv_spremenljivka WHERE id IN (".implode(',', $fields).")");
			while ($row = mysqli_fetch_array($sql)) {
				$tip[$row['id']] = $row['tip'];
					
				if ( in_array($row['tip'], array(1, 2, 3)) ) {
			
					$s = sisplet_query("SELECT id, variable FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
					while ($r = mysqli_fetch_array($s)) {
						$vre_id[$row['id']][$r['variable']] = $r['id'];
					}
			
				} elseif ( in_array($row['tip'], array(7, 21,8)) ) {
					// v tekstovno spremenljivko pisemo v prvo polje
					$s = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
					$r = mysqli_fetch_array($s);
					$vre_id[$row['id']] = $r['id'];
				}
			}
			
			$added = 0;
				
			for ($row = $start_row; $row <= $end_row; ++$row)
			{
				$line=array();
				foreach ($columns AS $column)
				{
					$value = $objWorksheet->getCell("$column$row")->getCalculatedValue();
					$line[] = mysqli_real_escape_string($GLOBALS['connect_db'], $value);
					#$line[] = mysqli_real_escape_string($GLOBALS['connect_db'], $objWorksheet->getCell("$column$row")->getValue());
				}
				$added += $this->insertLine($line,$fields,$tip,$vre_id,$do);
			}
			
			$result = $added;
		}
		else
		{
			echo $lang['srv_append-merge_error_file'];
		}
		
		if ($result == -1) {
			$output = $lang['srv_append-merge_required_field'];
		} elseif ($result == -3) {
			$output = $lang['srv_append-merge_required_data'];
		} elseif ($result >= 0) {
			$output = $lang['srv_append-merge_added_1'].' '.$result.' '.$lang['srv_append-merge_added_2'];
		}
		
		
		// prikazemo obvestilo in formo za potrditev
		if ($do_merge == 1)
			echo '<fieldset><legend>'.$lang['srv_data_subnavigation_merge'].'</legend>';
		else
			echo '<fieldset><legend>'.$lang['srv_data_subnavigation_append'].'</legend>';
			
		if ( ! $do ) {
				
			// napaka
			if ($result <= 0) {
				echo '<h2>'.$lang['error'].'</h2>';
		
				if ($result < 0)
					echo '<p>'.$output.'</p>';
				else
					echo '<p>'.$lang['merge_error_value'].'.</p>';
		
				echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_gray" onclick="append_submit_close(); return false;"><span>'.$lang['back'].'</span></a></span>';
		
			} else {
		
				echo '<h2>'.$lang['srv_potrditev'].'</h2>';
		
				if ($do_merge == 0)
					echo '<p>'.$lang['srv_append-merge_process_1'].' '.$result.' '.$lang['srv_append-merge_process_2'].'. </p>';
				else
					echo '<p>'.$lang['srv_append-merge_process_o_1'].' '.$result.' '.$lang['srv_append-merge_process_o_2'].'</p>';
		
		
				echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" onclick="append_submit(1); return false;"><span>'.$lang['srv_potrdi'].'</span></a></span>';
				echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_gray" onclick="append_submit_close(); return false;"><span>'.$lang['srv_cancel'].'</span></a></span>';
			}
						
			// shranjeno, prikazemo resultat
		} else {

			echo '<h2>'.$lang['fin_import_ok'].'</h2>';
				
			echo '<p>'.$output.'</p>';
			echo '<p>'.$lang['fin_import_ok_text'].'</p>';
				
			echo '<span id="inv_upload_recipients_no_sbmt" class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange" href="index.php?anketa='.$this->anketa.'&a=data"><span>'.$lang['data_show'].'</span></a></span>';			
		}
		
		echo '<br /><br />';
		echo '</fieldset>';
	}
	
	function ajax() {
		
		$this->anketa = $_REQUEST['anketa'];
		
		if ($_GET['a'] == 'change_import_type') {
			$this->ajax_change_import_type();
		
		} elseif ($_GET['a'] == 'add_recipients') {
			$this->ajax_addRecipients();
			
		} elseif ($_GET['a'] == 'submit') {
			$this->ajax_submit();
		}
	}
	
	function ajax_change_import_type() {
		$this->displayAppendMerge();
	}
	
	function ajax_addRecipients() {
		
		$this->do_append_merge();
	}
	
	function ajax_submit () {
		
		$this->do_append_merge();	
	}
	
}
 
?>