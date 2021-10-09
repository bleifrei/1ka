<?php 

class SurveyRecoding
{
	private $anketa;										# id ankete
	private $db_table;								# katere tabele uporabljamo

	function __construct($anketa) {
		$this->anketa = $anketa;
		SurveyInfo::getInstance()->SurveyInit($anketa);
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			
		$this->db_table = '_active';
		
	}
	
	function Ajax() {
	
		switch ($_GET['a']) {
			case 'showQuestionRecode':
				$this->showQuestionRecode();
			break;
			case 'saveQuestionRecode':
				$this->saveQuestionRecode();
			break;
			case 'removeQuestionRecode':
				$this->removeQuestionRecode();
			break;
			case 'add_new_numeric':
				$this->addNewNumeric();
			break;
			case 'changeRecodeType':
				$this->changeRecodeType();
			break;
			case 'recodeSpremenljivkaNew':
				$this->recodeSpremenljivkaNew();
			break;
			case 'recodeVrednostNew':
				$this->recodeVrednostNew();
			break;
			case 'runRecodeVredonosti':
				$this->runRecodeVredonosti();
			break;
			case 'removeSpremenljivka':
				$this->removeSpremenljivka();
			break;
			case 'enableRecodeVariable':
				$this->enableRecodeVariable();
			break;
			case 'visibleRecodeVariable':
				$this->visibleRecodeVariable();
			break;
			
			default:
					print_r("<pre>Class:SurveyRecoding");
					print_r($_POST);
					print_r($_GET);
					exit();
			break;
		}
	}
	
	function DisplaySettings() {
		global $lang;
		echo '<fieldset><legend>'.$lang['srv_data_subnavigation_recode'].'</legend>';
		echo $lang['srv_recode_note'].'<br/><br/>';
		echo $lang['srv_recode_note_text'].'<br/><br/>';
		#poiščemo spremenljivke ki so morda rekodirane v drugo spremenljivko
		/*
		$qry_str = "select distinct spr1, spr2 from srv_recode_vrednost WHERE ank_id = '$this->anketa'";
		$sql = sisplet_query($qry_str);
		$recodedFrom = array();
		$recodedMaping = array();
		while ($row = mysqli_fetch_assoc($sql)) {
			$recodedFrom[] = $row['spr1']; 
			$recodedMaping[$row['spr1']] = $row['spr2']; 
		}
		*/
		$recodedFrom = array();
		$recodedMaping = array();
		# za barvanje celic
		$cnt_even_odd = 0;
		$qry_str = "select spr_id, enabled, to_spr_id from srv_recode_spremenljivka WHERE ank_id = '$this->anketa'";
		$sql = sisplet_query($qry_str);
		while ($row = mysqli_fetch_assoc($sql)) {
			$recodedFrom[] = $row['spr_id'];
			$recodedMaping[$row['spr_id']] = $row['to_spr_id'];
		}
		

		$recodedSpremenljivkaEnabled = array();
		#polovimo enabled za rekodiranje v novo spremenljivko
		$qry_str = "select spr_id, enabled, to_spr_id from srv_recode_spremenljivka WHERE ank_id = '$this->anketa' AND enabled='1'";
		$sql = sisplet_query($qry_str);
		while ($row = mysqli_fetch_assoc($sql)) {
			$recodedSpremenljivkaEnabled[$row['spr_id']] = (int)$row['enabled'];
		}
		# polovimo enabled še za normalno rekodiranje
		$qry_str = "select DISTINCT spr_id, enabled from srv_recode WHERE ank_id = '$this->anketa' AND enabled='1'";
		$sql = sisplet_query($qry_str);
		while ($row = mysqli_fetch_assoc($sql)) {
			$recodedSpremenljivkaEnabled[$row['spr_id']] = (int)$row['enabled'];
		}
		
		SurveyAnalysis::Init($this->anketa);
		SurveyAnalysis::$setUpJSAnaliza = false;
		/*kategorialne: 1,2,3,6,16,17
		 * number: 7,18,20,22,25
		*/
		$qry_str = "SELECT s.id, s.variable, s.naslov FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip IN (1,2,3,6,16,17,7,18,20,22,25)ORDER BY g.vrstni_red ASC, s.vrstni_red ASC";
		$sql = sisplet_query($qry_str);
		if (mysqli_num_rows($sql)) {
			echo '<table id="recode_table">';
			echo '<thead><tr>';
			echo '<th colspan="4">'.$lang['srv_recode_h_variable'].'</th>';
			echo '<th colspan="5">'.$lang['srv_recode_h_actions'].Help::display('srv_recode_h_actions').'</th>';
			echo '</tr></thead>';
			while (list($spr_id, $variable, $naslov) = mysqli_fetch_row($sql)) {
				$vmv = new RecodeValues($this->anketa,$spr_id);
				$hasRecoded = $vmv->hasRecodedValues() || in_array($spr_id,$recodedFrom);
				$enabled = (isset($recodedSpremenljivkaEnabled[$spr_id]) && $recodedSpremenljivkaEnabled[$spr_id] = 1) ? true : false;
				$row = Cache::srv_spremenljivka($spr_id);
				$is_recoded_new = false;
				if ((int)$hasRecoded > 0) {
					$css_strong = ' strong';
					# spremenljivka je rekodirana v drugo
					$icon1 =  $lang['srv_recoded_note_original'];
					$icon2 =  '';
					if ((int)$vmv->hasRecodedValues() > 0 &&  (int)in_array($spr_id,$recodedFrom) == 0) {
						$icon1 =  $lang['srv_recoded_note_recoded'];
						$icon2 =  '';
					}
				} else {
					$css_strong = '';
					$icon1 = '';
					$icon2 = '';
				}
				$key = array_search($spr_id, $recodedMaping);
				if ($key) {
					# spremenljivka je rekodirana iz druge variable
					$icon1 =  '';
					$icon2 =  $lang['srv_recoded_note_recoded'];
					$is_recoded_new = true;
				}
				$css_border = ' border_top_lite';
				if ((int)$key == 0) {
					$cnt_even_odd++;
					$css_border = ' border_top';
				}
				$css_even_odd =  ( ($cnt_even_odd & 1) ? ' odd' : ' even' );
				
				echo '<tr id="recoding_variable_div_'.$spr_id.'" class="recoding_variable_div'.$css_even_odd.$css_border.'">';
				echo '<td style="width:auto;">';
				SurveyAnalysis::showIcons($spr_id.'_0',$row,'desc',array('noReport' => true, 'showChart' => false));
				echo '</td>';
				echo '<td style="width:25px; text-align:center;" class="gray">';
				echo $icon1;
				echo '</td>';
				echo '<td>';
				echo '<span class="gray">'.$icon2.'</span>';
				echo '<span class="green'.$css_strong.'">';
				
				echo '<a onclick="showspremenljivkaSingleVarPopup(\''.$spr_id.'_0\'); return false;" href="#">';
				echo $variable;
				echo '</a>';
				echo '</span> - <span class="spaceRight'.$css_strong.'">'.skrajsaj(strip_tags($naslov),40).'</span>';

				if ($key) {
					$row1 = Cache::srv_spremenljivka($key);
					echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="italic gray">( '.$lang['srv_recode_ercode_from_spr'].$row1['variable'].' )</span>';
				}
			
				echo '</td>';
				
				echo '<td class="gray" style="padding-right:10px; padding-left:10px;">';
				$legend = Cache::spremenljivkaLegenda($spr_id);
				echo '('.$legend['izrazanje'].' - '.$legend['skala'].')';
				echo '</td>';
				echo '<td style="width:50px; text-align:center;" >';
				#dodaj
				if ($is_recoded_new == true) {
					echo '<span class="silver">&nbsp;</span>';
				} else {
					if ((int)$hasRecoded > 0) {
						echo '<span class="silver">'.$lang['srv_recode_add'].'</span>';
					} else {
						echo '<span class="as_link" onclick="showQuestionRecode(\''.$spr_id.'\');">'.$lang['srv_recode_add'].'</span>';
					}
				}
				echo '</td>';

				echo '<td style="padding-right:10px; padding-left:10px;text-align:center;">';
				# uredi
				if ($is_recoded_new == true) {
					echo '<span class="silver">&nbsp;</span>';
				} else {
				
					if ((int)$hasRecoded > 0) {
						echo '<span class="as_link" onclick="showQuestionRecode(\''.$spr_id.'\');">'.$lang['srv_recode_edit'] .'</span>';
					} else {
						echo '<span class="silver">'.$lang['srv_recode_edit'] .'</span>';
					}
				}
				echo '</td>';
				echo '<td style="padding-right:10px; padding-left:10px; text-align:center;">';
				# odstrani
				if ($is_recoded_new == true) {
					echo '<span class="silver">&nbsp;</span>';
				} else {
					if ((int)$hasRecoded > 0) {
						echo '<span class="as_link" onclick="removeQuestionRecode(\''.$spr_id.'\',\''.$lang['srv_recode_confirm_delete'].'\');">'.$lang['srv_recode_remove'].'</span>';
					} else {
						echo '<span class="silver" >'.$lang['srv_recode_remove'].'</span>';					
					}
				}
				echo '</td>';
				
				echo '<td style="padding-right:10px; padding-left:10px;">';
				if ($is_recoded_new == true) {
					echo '<span class="silver">&nbsp;</span>';
				} else {
					if ($hasRecoded) {
						echo '<label><span class="as_link" onclick="enableRecodeVariable(\''.$spr_id.'\',this);">'.($enabled ? $lang['srv_recode_enabled'] : $lang['srv_recode_disabled']).'</span></label>';
						#echo '<label><input type="checkbox" id="recoding_variable_cb_'.$spr_id.'" onchange="enableRecodeVariable(\''.$spr_id.'\',this);"'.($enabled?' checked="checked"':'').' autocomplete="off">'.$lang['srv_recode_enable'].'</label>';
					} else {
						echo '<span class="silver">'.$lang['srv_recode_enabled'].'</span>';
					}
				}
				echo '</td>';
				
				$visible= ((int)$row['visible'] == 1) ? true : false;
				
				echo '<td style="padding-right:10px; padding-left:10px;">';
				echo '<label><span class="as_link" onclick="visibleRecodeVariable(\''.$spr_id.'\',this);">'.($visible ? $lang['srv_recode_visible'] : $lang['srv_recode_invisible']).'</span></label>';
				echo '</td>';
				
				echo '</tr>';
				$vmv = null;
				unset($vmv);
			}
			echo '</table>';
		}		
		
        session_start();
        
		if ($_SESSION['showRunRecodeButton'][$this->anketa] == true) {
			echo '<br/><div class="buttonwrapper floatLeft" id="btnRunRecode" title="'.$lang['srv_compute_start'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="runRecodeVredonosti($(\'#question_recode_run_note\'));">'.$lang['srv_compute_start'].'</a></div>';
			echo '<br /><br />';
		}
		
		echo '</fieldset>';
		
		$this->DisplayDashboard();
	}
	
	function showQuestionRecode() {
		global $lang;
		
		$spr_id = $_POST['spr_id'];
		
		if ((int)$spr_id > 0 && (int)$this->anketa > 0) {
			$qry_str = "SELECT naslov, variable FROM srv_spremenljivka WHERE id = '$spr_id'";
			$qry = sisplet_query($qry_str);
			$spr_row = mysqli_fetch_assoc($qry);
			
            echo '<h2>'.$spr_row['variable'].' - '.strip_tags($spr_row['naslov']).'</h2>';
            
            echo '<div class="popup_close"><a href="#" onClick="cancelQuestionRecode(); return false;">✕</a></div>';
			
			#echo '<span id="vprasanje_edit_mv" style="outline:1px solid red;">';
			$vmv = new RecodeValues($this->anketa,$spr_id);
			$vmv->DisplayMissingValuesForQuestion();
			#echo '</span>';
			
			echo '<div class="recodeButtonHolder buttons_holder">';
			echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="saveQuestionRecode(); return false;">'.$lang['save'].'</a></div></span>';
			echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="cancelQuestionRecode(); return false;"><span>'.$lang['srv_cancel'].'</span></a></div></span>';
			echo '<br class="clr"/>';
			echo '</div>';
		} else {
			echo 'Napaka!';
		}
	}
	
	function saveQuestionRecode() {
		$spr_id = $_REQUEST['spr_id'];
		if ((int)$spr_id > 0 && (int)$this->anketa > 0) {
			if (isset($_REQUEST['edit_recode_nsmv']) && (int)$_REQUEST['edit_recode_nsmv'] == 1) {
				#rekodiramo v novo spremenljivko
				$vmv = new RecodeValues($this->anketa,$spr_id);
				$vmv->SetRecodeNewVrednost();
			} else {
				# Shranimo zamenjave manjkajočih vrednosti pri posameznem vprašanu za analize
				if (isset($_REQUEST['edit_recode_mv']) || isset($_REQUEST['edit_recode_number'])) {
					$vmv = new RecodeValues($this->anketa,$spr_id);
					if ((int)$_REQUEST['recode_type'] == 0) {
						$vmv->SetUpMissingValuesForQuestion();
					} else {
						# rekodiramo number v novo vrednost
						$vmv->SetRecodeNumberNewVrednost();
					}
				}
			}
		}
		#shranimo sejo da ob naslednjem prikaru (reload) prikažemo gumb za ponovo zaganjanje rekodiranih vrednosti
		session_start();
		$_SESSION['showRunRecodeButton'][$this->anketa] = true;
		session_commit();
	}
	function removeQuestionRecode() {
		global $lang;
		
		$spr_id = $_REQUEST['spr_id'];
		$result = array();
		$result['spr_id'] = 0;
		if ((int)$spr_id > 0 && (int)$this->anketa > 0) {
			# odstranimo rekodiranje za anketo
			$vmv = new RecodeValues($this->anketa,$spr_id);
			$result['spr_id'] = $vmv->removeRecodeForQuestion();
			if ((int)$result['spr_id']) {
				$qry_str = "SELECT variable, naslov FROM srv_spremenljivka s WHERE id = '".(int)$result['spr_id']."'";
				$sql = sisplet_query($qry_str);
				(list($variable, $naslov) = mysqli_fetch_row($sql));
				$result['confirmtext'] = $lang['srv_recode_confirm_delete_sub'].''.$variable.' '.strip_tags($naslov).'?';
			}
			
			# vsilimo preračunavanje
			$this->recalculateRecode();
			
			
		}
		echo json_encode($result);
	}
	
	function addNewNumeric() {
		$spremenljivka = $_REQUEST['spremenljivka'];
		if ((int)$spremenljivka > 0 && (int)$this->anketa > 0) {
			$vmv = new RecodeValues($this->anketa,$spremenljivka);
			$vmv->AddNewNumericRecode();
		}
	}
	function changeRecodeType() {
		$spremenljivka = $_REQUEST['spr_id'];
		
		if ((int)$spremenljivka > 0 && (int)$this->anketa > 0) {
			$vmv = new RecodeValues($this->anketa,$spremenljivka);
			$vmv->changeRecodeType();
			return;
		} 
		if ((int)$spremenljivka == 0) {
			echo 'Missing spr_id!';
		}
	}
	
	function recodeSpremenljivkaNew() {
		$spremenljivka = $_REQUEST['spr_id'];
		if ((int)$spremenljivka > 0 && (int)$this->anketa > 0) {
			if (trim($_POST['spremenljivka_naslov']) != '') {
				
				Common::updateEditStamp();
				
				ob_start();
				$ba = new BranchingAjax($this->anketa);
				$ba->ajax_spremenljivka_new($spremenljivka, 0, 0, 0, 1, 0);
				$spr_id = $ba->spremenljivka;
				ob_clean();
				global $global_user_id;
				$insertString = "INSERT INTO srv_recode_spremenljivka (ank_id,spr_id,recode_type,to_spr_id,usr_id,rec_date)".
				" VALUES ('".$this->anketa."', '".$spremenljivka."', '".(int)$_POST['recode_type']."', '$spr_id','$global_user_id',NOW())".
				" ON DUPLICATE KEY UPDATE recode_type='".(int)$_POST['recode_type']."',to_spr_id = '$spr_id', usr_id='$global_user_id',rec_date=NOW()";
				$sqlInsert = sisplet_query($insertString);
				
				# spremenimo ime, Q1_rec_1
		
				$var = Cache::get_spremenljivka($spremenljivka, 'variable');
				$i = 1;
				do {
					$variable = $var.'_rec_'.$i++;
					$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE variable='$variable'");
				} while (mysqli_num_rows($sql) > 0);
				
				$naslov = "naslov='".$_POST['spremenljivka_naslov']."',";
				
				$s = sisplet_query("UPDATE srv_spremenljivka SET $naslov visible='1', variable_custom='1', variable='".$variable."', size='0' WHERE id = '$spr_id'");
				if (!$s) echo "x1".mysqli_error($GLOBALS['connect_db']);
				
				$s = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id = '$spr_id'");
				if (!$s) echo "x2".mysqli_error($GLOBALS['connect_db']);
			} else {
				echo '<span class="red">Vpišite ime spremenljivke!</span><br/>';
			}		
			$vmv = new RecodeValues($this->anketa,$spremenljivka);
			$vmv->displayRecodeType();
		}		
		
	}
	function recodeVrednostNew() {
		$spremenljivka = $_REQUEST['recodeToSpr'];
		if ((int)$spremenljivka > 0) {
			if (trim($_POST['vrednost_new']) != '') {
				Common::updateEditStamp();
			
				$v = new Vprasanje($this->anketa);
				$v->spremenljivka = $spremenljivka;
				
				$v->vrednost_new($_POST['vrednost_new']);
			} else {
				echo '<span class="red">Vpišite ime kategorije!</span><br/>';
			}
		}
		$vmv = new RecodeValues($this->anketa,$_POST['spr_id']);
		$vmv->displayRecodeType();
	}
	function getProfileString() {
		global $lang;
		#preštejemo koliko rekodiranj imamo za anketo
		$strSelect = "SELECT count(*) FROM srv_recode WHERE ank_id = '".$this->anketa."'";
		$sqlSelect = sisplet_query($strSelect);
		list($count) = mysqli_fetch_row($sqlSelect);
		if ((int)$count > 0) {
			/*echo '<div id="recodingNote">';
			echo '<span>'.$lang['srv_data_recoded_note'].'</span>';
			echo '&nbsp;&nbsp;<a href="index.php?anketa=' . $this->anketa . '&a='.A_COLLECT_DATA.'&m='.M_COLLECT_DATA_RECODING.'">'.$lang['srv_profile_edit'].'</a>';
			echo '</div>';*/
			return true;
		}
		return false;
	}
	
	function removeSpremenljivka() {
		$spr_id = (int)$_POST['spr_id'];
		if ($spr_id > 0) {
			$s = sisplet_query("DELETE FROM srv_spremenljivka WHERE id = '$spr_id'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
	
	function DisplayDashboard() {
		global $lang;
		
		
		# zloopamo skozi vse spremenljivke
		$qry_str = "SELECT s.id, s.tip, s.variable, s.naslov FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC";
		$sql = sisplet_query($qry_str);
		$all_recoded = array();
		
		if (mysqli_num_rows($sql) > 0) {

			while (list($spr_id, $tip, $variable, $naslov) = mysqli_fetch_row($sql)) {
				# preverimo ali je rekodirana v novo spremenljivko
				# preverimo ali imamo rekodiranje v novo spremenljivko, če ja, kasneje ponudimo tud izbris psremenljivke
				$qry = sisplet_query("SELECT to_spr_id, usr_id, rec_date FROM srv_recode_spremenljivka WHERE ank_id = '".$this->anketa."' AND spr_id = '".$spr_id."'");
				list($to_spr_id,$usr_id,$rec_date) = mysqli_fetch_row($qry);
				if ((int)$to_spr_id > 0) {
					$row_to_spr_id = Cache::srv_spremenljivka($to_spr_id);
					$sel = "select count(*) from srv_recode_vrednost where ank_id = '".$this->anketa."' AND spr1 = '$spr_id' GROUP BY spr1";
					$qry = sisplet_query($sel);
					list($cnt1) = mysqli_fetch_row($qry);
					if ((int)$cnt1 > 0) {
						$all_recoded[] = array('txt'=>sprintf($lang['srv_recode_summary_new_spr'],$variable, $row_to_spr_id['variable'],strip_tags($row_to_spr_id['naslov']),(int)$cnt1),'usr_id'=>$usr_id,'rec_date'=>$rec_date);
					}
					# lahko da je numerična
					$sel = "select count(*) from srv_recode_number where ank_id = '".$this->anketa."' AND spr_id = '$spr_id'";
					$qry = sisplet_query($sel);
					list($cnt1) = mysqli_fetch_row($qry);
					if ((int)$cnt1 > 0) {
						$all_recoded[] = array('txt'=>sprintf($lang['srv_recode_summary_new_spr'],$variable, $row_to_spr_id['variable'],strip_tags($row_to_spr_id['naslov']),(int)$cnt1),'usr_id'=>$usr_id,'rec_date'=>$rec_date);
					}
						
				} else {
					
					$strSelect = "SELECT count(*) FROM srv_recode WHERE ank_id = '".$this->anketa."' AND spr_id = '$spr_id' GROUP BY spr_id";
					$qry = sisplet_query($strSelect);
					list($cnt2) = mysqli_fetch_row($qry);
					if ((int)$cnt2 > 0) {
						$all_recoded[] = array('txt'=>sprintf($lang['srv_recode_summary_same_spr'],$variable,(int)$cnt2),'usr_id'=>$usr_id,'rec_date'=>$rec_date);
					}
				}
				
			}
			
		}
		if (count($all_recoded) == 0) {
			echo $lang['srv_recode_summary_nothing'];
		} else {
			#var_dump($all_recoded);#
			echo '<span id="div_recent_recoding_1" class="as_link" onclick="$(\'#div_recent_recoding, #div_recent_recoding_1, #div_recent_recoding_2\').toggle();">+ '.$lang['srv_recode_summary'].'</span>';
			echo '<span id="div_recent_recoding_2" class="as_link displayNone" onclick="$(\'#div_recent_recoding, #div_recent_recoding_1, #div_recent_recoding_2\').toggle();">- '.$lang['srv_recode_summary'].'</span>';
			echo '<div id="div_recent_recoding" class="displayNone">';
			foreach ($all_recoded AS $msg) {
				echo $msg['txt']."<br/>";
			}
			echo '</div>';
		}

	}
	
	function enableRecodeVariable() {
		global $lang;
		# updejtamo polje enabled
		$spr_id = (int)$_POST['spr_id'];
		
		#polovimo enabled za rekodiranje v novo spremenljivko
		$enabled = false;
		$qry_str = "SELECT enabled FROM srv_recode_spremenljivka WHERE ank_id = '$this->anketa' AND spr_id='$spr_id'";
		$sql = sisplet_query($qry_str);
		while (list($_enabled) = mysqli_fetch_row($sql)) {
			if ((int)$_enabled == 1) {
				$enabled=true;
			}
		}
		# polovimo enabled še za normalno rekodiranje
		$qry_str = "SELECT enabled FROM srv_recode WHERE ank_id = '$this->anketa' AND spr_id='$spr_id'";
		$sql = sisplet_query($qry_str);
		while (list($_enabled) = mysqli_fetch_row($sql)) {
			if ((int)$_enabled == 1) {
				$enabled=true;
			}
		}
		# če je bilo prej omogočeno sedaj obrnemo
		$enabled_str = ($enabled) ? '0' : '1';

		global $global_user_id;
		#polovimo enabled za rekodiranje v novo spremenljivko
		$qry_str1 = "UPDATE srv_recode_spremenljivka SET enabled='$enabled_str',usr_id='$global_user_id',rec_date=NOW() WHERE ank_id = '$this->anketa' AND spr_id='$spr_id'";
		$s1 = sisplet_query($qry_str1);
		if (!$s1) echo 'e1:'.mysqli_error($GLOBALS['connect_db']);
		# polovimo enabled še za normalno rekodiranje
		$qry_str2 = "UPDATE srv_recode SET enabled='$enabled_str' WHERE ank_id = '$this->anketa' AND spr_id='$spr_id'";
		$s2 = sisplet_query($qry_str2);
		if (!$s2) echo 'e2:'.mysqli_error($GLOBALS['connect_db']);
		
		# popravimo timestamp
		Common::updateEditStamp();
		echo ($enabled == false) ? $lang['srv_recode_enabled'] : $lang['srv_recode_disabled'];
	}
	
	function visibleRecodeVariable() {
		global $lang;
		# updejtamo polje visible
		$spr_id = (int)$_POST['spr_id'];
		$row = Cache::srv_spremenljivka($spr_id);
		$visible= ((int)$row['visible'] == 1) ? true : false;
		#spremenimo vidnost spreenljivke
		$visible_str = ($visible) ? '0' : '1';
		$qry_str1 = "UPDATE srv_spremenljivka SET visible='$visible_str' WHERE id='$spr_id'";
		$s1 = sisplet_query($qry_str1);
		if (!$s1) echo 'e3:'.mysqli_error($GLOBALS['connect_db']);
		# popravimo timestamp
		Common::updateEditStamp();
		echo ($visible == false) ? $lang['srv_recode_visible'] : $lang['srv_recode_invisible'];
	}

	function runRecodeVredonosti($showButton=true) {
		global $lang;
		$this->recalculateRecode();
		if ($showButton == true) {
			echo '<div class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_gray" href="index.php?anketa=' . $this->anketa . '&a='.A_ANALYSIS.'&m='.M_ANALYSIS_SUMMARY.'"><span>'.$lang['srv_analiza'].'</span></a></div>';
		}
		echo '<script>$("#btnRunRecode").hide();$("#fullscreen").fadeOut("slow").html("");$("#fade").fadeOut("slow");</script>';
	}
	
	function recalculateRecode() {
		
		# pobrišemo vse obstoječe zamenjane vrednosti ankete
		#$strDel = "DELETE FROM srv_data_vrednost".$this->db_table." WHERE spr_id IN (SELECT to_spr_id from srv_recode_spremenljivka where ank_id = '".$this->anketa."')";
		#posodobljen query.. zdaj bi moglo letel :)
		$strDel = "DELETE sdv.* FROM srv_data_vrednost".$this->db_table." AS sdv INNER JOIN srv_recode_spremenljivka AS srs ON sdv.spr_id = srs.to_spr_id WHERE srs.ank_id = '".$this->anketa."'";
		$qD = sisplet_query($strDel);
		if (!$qD) echo mysqli_error($GLOBALS['connect_db']);
		sisplet_query("COMMIT");
		
		# najprej za katagorialne
		#updejtamo samo tiste katere so omogočene
		$sel = "select spr1,vre1,spr2,vre2 from srv_recode_vrednost WHERE ank_id = '".$this->anketa."' AND spr2 IN( SELECT to_spr_id from srv_recode_spremenljivka where ank_id = '".$this->anketa."' AND enabled='1' AND recode_type IN ('0','1'))";
		$qry = sisplet_query($sel);
		while (list($spr1, $vre1, $spr2, $vre2) = mysqli_fetch_row($qry)) {
			# podvojimo polja za novo variablo za vse userje in loope z novimi vrednostmi
			$insert = "INSERT INTO srv_data_vrednost".$this->db_table." (spr_id,vre_id,usr_id,loop_id)"
			." (SELECT \"$spr2\",\"$vre2\",usr_id,loop_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr1' AND vre_id='$vre1')"
			." ON DUPLICATE KEY UPDATE vre_id=\"$vre2\"";
			$qryI = sisplet_query($insert);
			sisplet_query("COMMIT");
		}
		
		
		# potem še za numerične
		$_operators = array(				# operatorji
				'0'=>'==',				# ==
				'1'=>'<>',		# <>
				'2'=>'<',			# <
				'3'=>'>',			# >
				'4'=>'<=',			# <=
				'5'=>'>=',			# >=
				'6'=>'interval'			# interval
		);
		
		#updejtamo samo tiste katere so omogočene
		$sprMapping = array();
		$selS ="SELECT spr_id, to_spr_id from srv_recode_spremenljivka where ank_id = '".$this->anketa."' AND enabled='1' AND recode_type='1'";
		
		$qryS = sisplet_query($selS);
		while ($rowS = mysqli_fetch_assoc($qryS)) {
		$sprMapping[$rowS['spr_id']] = $rowS['to_spr_id'];
		}
		$sel = "select * from srv_recode_number WHERE ank_id = '".$this->anketa."' AND spr_id IN( SELECT spr_id from srv_recode_spremenljivka where ank_id = '".$this->anketa."' AND enabled='1' AND recode_type='1') ORDER BY spr_id, vrstni_red";
		$qry = sisplet_query($sel);
		while ($row = mysqli_fetch_assoc($qry)) {
		if($row['operator'] != '6') {
		$selU = "SELECT ".$sprMapping[$row['spr_id']].", ".$row['vred_id'].", usr_id, loop_id FROM srv_data_text".$this->db_table." WHERE spr_id='$row[spr_id]' AND vre_id='0' AND text ".$_operators[$row['operator']]." $row[search]";
		} else {
		$_search = explode(',',$row['search']);
		$selU =  "SELECT ".$sprMapping[$row['spr_id']].", ".$row['vred_id'].", usr_id, loop_id FROM srv_data_text".$this->db_table." WHERE spr_id='$row[spr_id]' AND vre_id='0' AND text BETWEEN ".(int)$_search[0]." AND ".(int)$_search[1];
		}
		
		$insert = "INSERT INTO srv_data_vrednost".$this->db_table." (spr_id,vre_id,usr_id,loop_id) ($selU) ON DUPLICATE KEY UPDATE vre_id='".$row['vred_id']."'";
		$qryI = sisplet_query($insert);
				sisplet_query("COMMIT");
		}
				unset($_SESSION['showRunRecodeButton'][$this->anketa]);
				session_commit();
		Common::updateEditStamp();
	}


}
?>
