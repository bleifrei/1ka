<?php

/**
* 
* V tem classu so funkcije, ki se ticejo postprocessinga podatkov
* ala compute, recode, code ...
* 
*/

class SurveyPostProcess {
	
	private $anketa;
	private $spremenljivka;
	
	private $db_table = '';
	
	private $SDF = null;
	
	/**
	* post procesiranje podatkov ankete
	* 
	* @param int $anketa ID ankete
	*/
	function __construct ($anketa) {
		
		$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
		#inicializiramo class za datoteke
		$this->SDF = SurveyDataFile::get_instance();
		$this->SDF->init($this->anketa);
	}
	
	/**
	* izris taba za kalkulacijo
	* 
	*/
	function displayTab () {
		global $lang;
		
		echo '<fieldset><legend>'.$lang['srv_compute'].'</legend>';
		echo '<script>__vnosi=1;</script>';
		
		$b = new Branching($this->anketa);
		
		$sql = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip='22'");
		if (mysqli_num_rows($sql) > 0) {
			
			echo '<p>'.$lang['srv_compute_list'].'</p>';
			
			while ($row = mysqli_fetch_array($sql)) {
				
				echo '<p>'.$row['naslov'].': <a href="" onclick="calculation_editing(\'-'.$row['id'].'\'); return false;"><b> '.$row['variable'].'= '.$b->calculations_display(-$row['id'], 1).'</b></a></p>';
			}
		}
		
		echo '<p><a href="" onclick="spremenljivka_new(0, 0, 1, 0, 22); return false;">'.$lang['srv_add_compute'].'</a></p>';
		
		if (mysqli_num_rows($sql) > 0) {
			echo '<span class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="postprocess_start_calculation(); return false;">'.$lang['srv_compute_start'].'</a></span>';
		}
		
		echo '</legend>';
	}
	
	/**
	* prikaze link za dodat novo kalkulacijo za post process
	* dodamo kar obicno kalkulacijo, pri zapiranju (detektiramo v JS) pa se poklice ajax_calculation_postprocess_save, ki gre cez celo bazo
	* 
	*/
	function displayLink () {
		global $lang;
		
		echo '<div style="float:right; margin-right:10px">';
		echo '<a href="" onclick="spremenljivka_new(0, 0, 1, 0, 22); return false;">'.$lang['srv_add_compute'].'</a>';
		echo '</div>';
		
	}
	
	/**
	* zavihek za avtomatsko rekodiranje
	* 
	*/
	function displayCodingAuto () {
		global $lang;
		
		echo '<fieldset><legend>'.$lang['srv_auto_coding'].'</legend>';
		
		SurveyAnalysis::Init($this->anketa);
		$freq = SurveyAnalysis::getFrequencys();
		
		$ok = false;
		
		echo '<p id="ok">'.$lang['srv_mass_coding'].': ';
		echo '<select name="spr_id" id="mass_coding_spr_id" style="width:250px">';
		
		foreach (SurveyAnalysis::$_HEADERS AS $key => $h) {
			
			$spr = explode('_', $key);
			
			if ( is_numeric( $spr['0'] ) ) {
				
				foreach ($h['grids'] AS $grids_k => $grids_v) {
					
					foreach ($grids_v['variables'] AS $variables_k => $variables_v) {
						
						if ($variables_v['text'] == '1'/* || $h['tip'] == 19*/) {
							$ok = true;
							echo '<option value="'.$variables_v['sequence'].'-'.$spr[0].'">('.$h['variable'].') '.$h['naslov'].($variables_v['naslov']!=''?' - '.$variables_v['naslov']:'').'</option>';
						}						
					}					
				}
			}
		}
		
		echo '</select> ';
		
		echo '<select name="coding_type" id="coding_type">';
		echo '<option value="1">'.$lang['srv_coding_one'].'</option>';
		echo '<option value="2">'.$lang['srv_coding_multi'].'</option>';
		echo '</select> ';
		
		echo '<input type="submit" value="'.$lang['srv_save_and_run_profile'].'" onclick="$(this).prop(\'disabled\', true); mass_coding($(\'#mass_coding_spr_id\').val(), $(\'#coding_type\').val()); return false;"></p>';
		
		
		if (!$ok) {
			?><script> $('#ok').hide(); </script><?
			echo '<p>'.$lang['srv_mass_coding_no_vars'].'</p>';
		}
		
		echo '<p>'.$lang['srv_mass_coding_txt'].'</p>';
		
		echo '</fieldset>';
	}
	
	/**
	* zavihek za rocno rekodiranje
	* 
	*/
	function displayCoding () {
		global $lang;
		
		//$this->updateTracking($this->anketa, 4);
		echo '<fieldset><legend>'.$lang['srv_hand_coding'].'</legend>';
		echo '<div id="analiza_data">';
		Timer::StartTimer($lang['srv_collectData']);
		SurveyDataDisplay::Init($this->anketa);
		
		echo '<div id="div_analiza_filtri_right" class="analiza" style="left:auto"><a href="#" onclick="$(\'#filters\').slideToggle(); $(this).find(\'span\').toggleClass(\'plus\').toggleClass(\'minus\'); return false"><span class="faicon plus"></span> '.$lang['srv_advanced'].'</a></div>';
		
		echo '<div id="filters" style="display:none; height:80px; margin-top:10px">';
		SurveyDataDisplay::displayFilters();
		echo '</div>';
		
		SurveyAnalysis::Init($this->anketa);
		$freq = SurveyAnalysis::getFrequencys();
		
		echo '<p class="coding-refresh"><a href="index.php?anketa='.$this->anketa.'&a=data&m=coding">'.$lang['src_coding_refresh'].'</a></p>';
		
		
		SurveyVariablesProfiles::Init($this->anketa, $global_user_id);
		$variables = SurveyVariablesProfiles::getProfileVariables(-1);
		if (SurveyVariablesProfiles::getCurentProfileId() != -1)
			$variables = array();
		
		echo '<p>'.$lang['srv_hand_coding_text'] . '</p>';
		
		echo '<p><label id="link_variable_profile_remove" onclick="removeVariableProfile();"><input type="radio" name="filter" '.(count($variables)==0?'checked':'').'> '.$lang['srv_coding_spr_1'].'</label></p>';
		
		echo '<p><label onclick="coding_filter($(\'#mass_coding_spr_id\').val());"><input type="radio" name="filter" '.(count($variables)>0?'checked':'').' onclick="return false;"> '.$lang['srv_coding_spr'].':</label> ';
		echo '<select name="spr_id" id="mass_coding_spr_id" style="width:200px" onchange="coding_filter($(\'#mass_coding_spr_id\').val()); return false;">';
		
		SurveyAnalysis::Init($this->anketa);
		$freq = SurveyAnalysis::getFrequencys();
		
		foreach (SurveyAnalysis::$_HEADERS AS $key => $h) {
			
			$spr = explode('_', $key);
			
			if ( is_numeric( $spr['0'] ) ) {
				
				foreach ($h['grids'] AS $grids_k => $grids_v) {
					
					foreach ($grids_v['variables'] AS $variables_k => $variables_v) {
						
						if ($variables_v['text'] == '1'/* || $h['tip'] == 19*/) {
							
							echo '<option value="'.$variables_v['sequence'].'-'.$spr[0].'" '.(in_array($spr[0].'_0', $variables)?'selected':'').'>('.$h['variable'].') '.$h['naslov'].($variables_v['naslov']!=''?' - '.$variables_v['naslov']:'').'</option>';
						}	
					}			
				}
			}
		}
		
		echo '</select> ';
		//echo '<input type="submit" value="'.$lang['srv_coding_filter'].'" onclick="$(this).prop(\'disabled\', true); coding_filter($(\'#mass_coding_spr_id\').val()); return false;"> ('.$lang['srv_coding_spr2'].')</p>';
		if ( count($variables)>0 )
			echo '('.$lang['srv_coding_spr2'].')';
		echo '</p>';
		
		
		SurveyDataDisplay::displayVnosiHTML();
		echo '</div>'; // div_analiza_data
		Timer::GetTimer($lang['srv_collectData']);

		
		// div za popup editiranje
		echo '<div id="coding"></div>';
		echo '</fieldset>';
	}
	
	function ajax() {
		
		$this->anketa = $_REQUEST['anketa'];
		$this->spremenljivka = $_REQUEST['spremenljivka'];
		
		if ($_GET['a'] == 'postprocess_start_calculation') {
			$this->ajax_postprocess_start_calculation();
		
		} elseif ($_GET['a'] == 'edit_data_question') {
			$this->ajax_edit_data_question();
			
		} elseif ($_GET['a'] == 'edit_data_question_save') {
			$this->ajax_edit_data_question_save(0);
			
		} elseif ($_GET['a'] == 'get_inline_edit') {
			$this->ajax_get_inline_edit();
			
		} elseif ($_GET['a'] == 'get_inline_edit_all') {
			$this->ajax_get_inline_edit_all();
			
		} elseif ($_GET['a'] == 'coding') {
			$this->ajax_coding();
			
		} elseif ($_GET['a'] == 'coding_save') {
			$this->ajax_coding_save();
			
		} elseif ($_GET['a'] == 'vrednost_new') {
			$this->ajax_vrednost_new();
			
		} elseif ($_GET['a'] == 'spremenljivka_new') {
			$this->ajax_spremenljivka_new();
			
		} elseif ($_GET['a'] == 'tip') {
			$this->ajax_tip();
			
		} elseif ($_GET['a'] == 'mass_coding') {
			$this->ajax_mass_coding();
			
		} elseif ($_GET['a'] == 'coding_merge') {
			$this->ajax_coding_merge();
			
		} elseif ($_GET['a'] == 'coding_filter') {
			$this->ajax_coding_filter();
			
		}
		
		
	}
	
	/**
	* pozene racunanje kalkulacij na celotni anketi
	* 
	*/
	function ajax_postprocess_start_calculation () {
		Common::updateEditStamp();
		
		$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip='22'");
		if (!$sql) echo 'err34234'.mysqli_error($GLOBALS['connect_db']);
		
		$spremenljivk = mysqli_num_rows($sql);
		
		$i = 0;
		while ($row = mysqli_fetch_array($sql)) {
			$this->calculate_spremenljivka($row['id']);
		}
		
		self::forceRefreshData($this->anketa);
				
		// redirect
		echo 'index.php?anketa='.$this->anketa.'&a=data';
	}
	
	/**
	* za spremenljivko (tipa 22 - kalkulacija) gre cez celo bazo (beremo iz tekstovne datoteke) in preracuna vrednosti
	* 
	*/
	function calculate_spremenljivka ($spremenljivka = 0) {
		
		require('definition.php');
		
		if ($spremenljivka != 0)
			$this->spremenljivka = $spremenljivka;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		if ($row['tip'] != 22) return;
				
		$_fileStatus = $this->SDF->getStatus() ;

		# če imamo data datoteko (novo=1 in staro=0)
		if ($_fileStatus == FILE_STATUS_OK || $_fileStatus == FILE_STATUS_OLD) {
			$_dataFileName = $this->SDF->getDataFileName();
			
			$missing = $this->generateCalculationMissing(-$this->spremenljivka);
			$calculation = $this->generateCalculationAWK(-$this->spremenljivka);			
			
			// for(i=1;i<=NF;i++) if ($i < 0) $i=0;	// nastavi vse <0 na 0
			
			if (IS_WINDOWS) {
				$cmd = 'awk -F"|" "{{OFS=\"\"} {ORS=\"\"}} { if ( '.$missing.' ) calc=-1; else calc = '.$calculation.' ; print \"('.$this->spremenljivka.',\",calc,\",\",$1,\"),\"}" '.$_dataFileName;
			} else {
				$cmd = 'awk -F"|" \'{{OFS=""} {ORS=""}} { if ( '.$missing.' ) calc=-1; else calc = '.$calculation.' ; print "('.$this->spremenljivka.',",calc,",",$1,"),"}\' '.$_dataFileName;
			}
			
			$out = shell_exec($cmd);
			
			// rezultat lahko vrne nan - spremenimo na -1
			$out = str_replace('-nan', '-1', $out);
			$out = str_replace('nan', '-1', $out);
			
			// pobrisemo zadnjo vejico
			$values = substr($out, 0, strrpos($out, ",") );
			
			// Zaokrozimo na doloceno stevilo decimalk
			$decimals = $row['decimalna'];
			$calc_val_array = explode(',', $values);
			for($i=1; $i<count($calc_val_array); $i+=3){			
				$calc_val_array[$i] = round(floatval($calc_val_array[$i]), $decimals);
			}
			$values = implode(',', $calc_val_array);
			
			$sql = sisplet_query("DELETE FROM srv_data_text".$this->db_table." WHERE spr_id='".$this->spremenljivka."'");
			if (!$sql) echo 'err3324'.mysqli_error($GLOBALS['connect_db']);
			
			if ($values != '') {
			
				$sql_query = "INSERT INTO srv_data_text".$this->db_table." (spr_id, text, usr_id) VALUES " . $values . "";
	
				$sql = sisplet_query($sql_query);
				if (!$sql) echo 'err766567'.' '.$sql_query.' '.mysqli_error($GLOBALS['connect_db']);
			
			}
			
			$sql = sisplet_query("UPDATE srv_anketa SET edit_time = NOW() WHERE id = '$this->anketa'");
			if (!$sql) echo 'err6563'.sisplet_query($sql);
			
		} else {
			//echo 'filestatus' . $_fileStatus;
		}
				
	}
	
	/**
	* vrne vrstni red stolpca v datoteki s podatki za podano spremenljivko
	* 
	* @param mixed $spr_id
	* @param mixed $vre_id
	* @return mixed
	*/
	function getSequence ($spr_id, $vre_id = null, $grd_id = null) {
		
		$header = $this->SDF->getHeaderVariable($spr_id.'_0');

	 	switch ($header['tip']) {
			case 1 :		// radio
			case 3 :		// dropdown
			case 4 :		// text -??
			case 21:		// beseilo?
			case 22:		// compute
			case 25:		// kvota
			    return $header['grids']['0']['variables']['0']['sequence'];
			break;
			case 7:			// number
				return $header['grids']['0']['variables'][$grd_id]['sequence'];
			case 16:		// multicheckbox
			case 20:		// multinumber
				foreach ($header['grids'] AS $grids) {
					foreach ($grids['variables'] AS $variables) {
						if ($variables['vr_id'] == $vre_id && $variables['gr_id'] == $grd_id)
							return ($variables['sequence']);
						}
				}
			case 17:		// razvrscanje
	        case 18:		// vsota
			default :		// multigrid, checkbox
				foreach ($header['grids'] AS $grids) {
					foreach ($grids['variables'] AS $variables) {
						if ($variables['vr_id'] == $vre_id)
							return ($variables['sequence']);
						}
				}
			    return ;
			break;
	 	}
	 }
	
	/**
    * @desc zgenerira kalkulacijo za vstavitev v awk
    */
    function generateCalculationAWK ($condition) {
		
        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
                
        $i = 0;
        $expression = '';
        while ($row = mysqli_fetch_array($sql)) {

        	$row1 = Cache::srv_spremenljivka($row['spr_id']);
        	
            if ($i++ != 0)
                if ($row['operator'] == 0)
                    $expression .= ' + ';
                elseif ($row['operator'] == 1)
                    $expression .= ' - ';
                elseif ($row['operator'] == 2)
                    $expression .= ' * ';
                elseif ($row['operator'] == 3)
                    $expression .= ' / ';

            for ($i=1; $i<=$row['left_bracket']; $i++)
                $expression .= ' ( ';

            // spremenljivke
            if ($row['spr_id'] > 0) {

                $seq = $this->getSequence($row['spr_id'], $row['vre_id'], $row['grd_id']);
                if ($seq > 0)
                	$expression .= ' $'.$seq.' ';
                else
                	$expression .= ' 0 ';

            // konstante
            } elseif ($row['spr_id'] == -1) {

                $expression .= $row['number'];

            }

            for ($i=1; $i<=$row['right_bracket']; $i++)
                $expression .= ' ) ';

        }

        return '('.$expression.')';
    }
	
	/**
    * @desc zgenerira pogoj, ki preveri, ce je kateri od odgovorov missing - potem je tudi kalkulacija missing
    */
    function generateCalculationMissing ($condition) {

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
                
        $i = 0;
        $expression = '';
        while ($row = mysqli_fetch_array($sql)) {

        	$row1 = Cache::srv_spremenljivka($row['spr_id']);

            // spremenljivke
            if ($row['spr_id'] > 0) {

				$seq = $this->getSequence($row['spr_id'], $row['vre_id'], $row['grd_id']);
                if ($seq > 0) {
                	if ($expression != '') $expression .= ' || ';
                	$expression .= ' $'.$seq.' < 0 ';
				}
            
            }

        }

        if ($expression == '') $expression = 'false';
        return $expression;
    }
	
	function ajax_edit_data_question() {
		global $lang;
		
		/*?>
		  <link rel="stylesheet" href="http://localhost/fdv/cms2/main/survey/skins/Modern.css" type="text/css" media="screen" />
		<?*/
		
		$spr_id = $_POST['spr_id'];
		$usr_id = $_POST['usr_id'];
		
		// preverimo, ce gre za multiple tabelo
		$sql = sisplet_query("SELECT parent FROM srv_grid_multiple WHERE spr_id='$spr_id' AND ank_id='$this->anketa'");
		$row = mysqli_fetch_array($sql);
		if (mysqli_num_rows($sql) > 0)
			$spr_id = $row['parent'];
		
		echo '<div id="preview_spremenljivka">';
		echo '<div id="spremenljivka_preview">';
        
        echo '<div class="popup_close"><a href="#" onClick="edit_data_close(); return false;">✕</a></div>';

		echo '<form name="edit_data" method="post" action="ajax.php?t=postprocess&a=edit_data_question_save&anketa='.$this->anketa.'" onsubmit="edit_data_question_save(); return false;">';
		echo '<input type="submit" id="submit" style="display:none" />'; // workaround, ker se drugace ne poklice onsubmit
		echo '<input type="hidden" name="spr_id" value="'.$spr_id.'" />';
		echo '<input type="hidden" name="usr_id" value="'.$usr_id.'" />';

		include_once('../../main/survey/app/global_function.php');
		new \App\Controllers\SurveyController(true);
		save('anketa', $this->anketa);
		save('forceShowSpremenljivka', true);
		save('question_edit', true);
		\App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($spr_id);
		
		echo '<div class="buttons_holder">';
		echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="javascript:$(\'#submit\').click(); return false;"><span>'.$lang['srv_potrdi'].'</span></a></div></span>';				
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="javascript:edit_data_close();"><span>'.$lang['srv_cancel'].'</span></a></div></span>';				
		echo '</div>';
		
		echo '</form>';
		
		/*echo '<div class="clr"></div>';*/
		
		echo '</div>';
		echo '</div>';
		
	}
	
	function ajax_edit_data_question_save ($refresh = 1) {
		Common::updateEditStamp();
		
		// Preverimo ce gre za prvo popravljanje podatkov in avtomatskoustvarimo arhiv podatkov ce je potrebno
		$sas = new SurveyAdminSettings();
		$sas->checkFirstDataChange();
		
		$spr_id = $_POST['spr_id'];
		$usr_id = $_POST['usr_id'];

		include_once('../../main/survey/app/global_function.php');
		new \App\Controllers\SurveyController(true);
		save('anketa', $this->anketa);
		save('forceShowSpremenljivka', true);
		save('usr_id', $usr_id);
		\App\Models\SaveSurvey::getInstance()->posted(0, $spr_id);
		
		/*if ($refresh == 1)
			header("Location: index.php?anketa=".$this->anketa."&a=data&m=edit");*/
	}
	
	function ajax_get_inline_edit () {
		
		$spr_id = $_POST['spr_id'];
		
		echo '<form action="ajax.php?t=postprocess&a=edit_data_question_save&anketa='.$this->anketa.'">';
		echo '<input type="hidden" name="spr_id" value="'.$spr_id.'" />';
		echo '<input type="hidden" name="visible_'.$spr_id.'" value="1" />';
		
		echo '<select name="vrednost_'.$spr_id.'" onchange="edit_data_inline_edit_save(this.parentNode.getAttribute(\'name\'));">';
		$sql = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$spr_id' ORDER BY vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {
			
			echo '<option value="'.$row['id'].'" variable="'.$row['variable'].'" class="'.$row['variable'].'">'.$row['naslov'].'</option>';
		}
		
		echo '</select>';
		echo '</form>';	
	}
	
	function ajax_get_inline_edit_all () {
		
		$spr = $_POST['spr'];
		
		$response = array();
		
		foreach ($spr AS $spr_id) {
			
			$output = array();
			
			$output['spr'] = $spr_id;
			$output['html'] = '';
			
			$output['html'] .= '<form action="ajax.php?t=postprocess&a=edit_data_question_save&anketa='.$this->anketa.'">';
			$output['html'] .= '<input type="hidden" name="spr_id" value="'.$spr_id.'" />';
			$output['html'] .= '<input type="hidden" name="visible_'.$spr_id.'" value="1" />';
			
			$output['html'] .= '<select name="vrednost_'.$spr_id.'" onchange="edit_data_inline_edit_save(this.parentNode.getAttribute(\'name\'));">';
						
			$sql = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$spr_id' ORDER BY naslov ASC");
			while ($row = mysqli_fetch_array($sql)) {
				
				$output['html'] .= '<option value="'.$row['id'].'" variable="'.$row['variable'].'" class="'.$row['variable'].'">'.$row['naslov'].'</option>';	
			}
			
			$output['html'] .= '</select>';
			$output['html'] .= '</form>';
		
			$response[] = $output;
		}
		
		echo json_encode($response);
	}
	
	function ajax_coding ($editing = false) {
		global $lang;
		
		$usr_id = $_POST['usr_id'];
		$spr_id = $_POST['spr_id'];
		
		$row = Cache::srv_spremenljivka($spr_id);
		if ($row['coding'] > 0) {
			$coding_id = $row['coding']; 
		} else {
			$coding_id = $spr_id;
		}
		echo '<h3 style="margin: 7px 10px">'.$lang['srv_hand_coding'].'</h3>';
		echo '<form id="coding_'.$usr_id.'">';
		
		echo '<input type="hidden" value="'.$usr_id.'" name="usr_id">';
		echo '<input type="hidden" value="'.$this->anketa.'" name="anketa">';
		
		$sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.coding='$coding_id' ORDER BY g.vrstni_red, s.vrstni_red");
		if (mysqli_num_rows($sql) > 0) {
			
			while ($row = mysqli_fetch_array($sql)) {
				
				echo '<fieldset style="position: relative">';
				
				echo '<p style="float:left; min-height:20px"><strong>'.skrajsaj(strip_tags($row['naslov']), 20).'</strong> <a href="" onclick="$(\'.edit_'.$row['id'].'\').toggle(); return false;" title="'.$lang['edit2'].'"><span class="faicon edit"></span></a></p>';
				echo '<p class="edit_'.$row['id'].'" style="display:none; float:left; margin-left:10px">'.$lang['srv_tip'].': ';
				echo '<select name="tip" onchange="if (confirm(\''.$lang['srv_change_q_tip'].'\')) {coding_tip(\''.$row['id'].'\', \''.$usr_id.'\', $(this).val());} return false;"><option value="1" '.($row['tip']==1?'selected':'').'>'.$lang['srv_vprasanje_tip_1'].'</option><option value="2" '.($row['tip']==2?'selected':'').'>'.$lang['srv_vprasanje_tip_2'].'</option><option value="3" '.($row['tip']==3?'selected':'').'>'.$lang['srv_vprasanje_tip_3'].'</option></select>';
				//echo '<a href="#" onclick="if (confirm(\''.$lang['srv_change_q_tip'].'\')) {coding_tip(\''.$row['id'].'\', \''.$usr_id.'\', \''.($row['tip']==1?'2':'1').'\');} return false;" title="'.$lang['srv_change_q_tip'].'">'.($row['tip']==1?$lang['srv_vprasanje_tip_1']:$lang['srv_vprasanje_tip_2']).'</a>';
				echo ' <a href="index.php?anketa='.$this->anketa.'&spr_id='.$row['id'].'#branching_'.$row['id'].'">'.$lang['srv_napredno_urejanje'].'</a> <a href="" onclick="brisi_spremenljivko(\''.$row['id'].'\'); return false;" title="'.$lang['srv_brisispremenljivko'].'"><span class="faicon delete icon-grey_dark_link"></span></a></p>';
				
				echo '<input id="visible_'.$row['row'].'" type="hidden" value="1" name="visible_'.$row['id'].'">';
				
				//echo '<input type="hidden" value="'.$row['id'].'" name="spr_id">';
				
				echo '<p style="clear:both">';
				if ($row['tip'] == 3) {
					echo '<select name="vrednost_'.$row['id'].'">';
				}
				$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red ASC");
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					$sql2 = sisplet_query("SELECT * FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$row[id]' AND vre_id='$row1[id]' AND usr_id='$usr_id'");
					if (mysqli_num_rows($sql2) > 0)
						$checked = 'checked="checked"'; else $checked = '';
					
					if ($row['tip'] == 1) {
						echo '<input type="radio" name="vrednost_'.$row['id'].'" id="spremenljivka_'.$row['id'].'_vrednost_'.$row1['id'].'" value="'.$row1['id'].'" '.$checked.' /><label for="spremenljivka_'.$row['id'].'_vrednost_'.$row1['id'].'"> '.$row1['naslov'].'</label>';
					} elseif ($row['tip'] == 2) {
						echo '<input type="checkbox" name="vrednost_'.$row['id'].'[]" id="spremenljivka_'.$row['id'].'_vrednost_'.$row1['id'].'" value="'.$row1['id'].'" '.$checked.' /><label for="spremenljivka_'.$row['id'].'_vrednost_'.$row1['id'].'"> '.$row1['naslov'].'</label>';
					} elseif ($row['tip'] == 3) {
						echo '<option value="'.$row1['id'].'" '.($checked!=''?'selected':'').'>'.$row1['naslov'].'</option>';
					}
					
					if ($row['tip'] == 1 || $row['tip'] == 2) {
						echo '<span style="display:none; color:gray; float:right" class="edit_'.$row['id'].'"><select onchange="coding_merge(\''.$row['id'].'\', \''.$row1['id'].'\', \''.$usr_id.'\', this.value)" style="width:150px">';
						echo '<option value="0">'.$lang['srv_coding_merge'].':</option>';
						
						$sql2 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]' AND id != '$row1[id]' ORDER BY vrstni_red ASC");
						while ($row2 = mysqli_fetch_array($sql2))
							echo '<option value="'.$row2['id'].'">'.$row2['naslov'].'</option>';
						
						echo '</select></span><br />';
					}
					
				}
				if ($row['tip'] == 3) {
					echo '</select>';
				}
				echo '<input type="text" name="vrednost_new_'.$row['id'].'" value="" placeholder="'.$lang['srv_novavrednost'].'" style="margin-left:23px; width: 80px" /> <a href="#" onclick="coding_vrednost_new(\''.$row['id'].'\', \''.$usr_id.'\', $(\'input[name=vrednost_new_'.$row['id'].']\').val()); return false;"><span class="faicon add small icon-as_link" title="'.$lang['add'].'"></span></a>';
				echo '</p>';
				echo '</fieldset>';
			}
		} else {
			echo '<fieldset>';
			echo '<p>'.$lang['srv_coding_no_spr'].'</p>';
			echo '</fieldset>';
		}
		
		echo '<div class="new-spr">';
		echo '<a href="#" onclick="'.(true?' $(\'#coding_spr_new\').toggle();':'').' return false;">'.$lang['srv_coding_new'].'</a>';
		echo '<div id="coding_spr_new" '.(true?' style="display:none"':'').'>';
		echo '<p>'.$lang['name'].': <input type="text" name="spremenljivka_new" value="" style="width: 123px" /> <a href="#" onclick="coding_spremenljivka_new(\''.$coding_id.'\', \''.$usr_id.'\', $(\'input[name=spremenljivka_new]\').val()); return false;">'.$lang['add'].'</a></p>';
		echo '</div>';
		echo '</div>';
		
		
			
		echo '<p style="margin:20px 10px 10px 10px">';
		
		if (mysqli_num_rows($sql) > 0) {
			//echo '<input type="submit" value="'.$lang['srv_close_profile'].'" onclick="coding_save(\''.$usr_id.'\'); return false;"> ';
		}
		echo '<a href="#" onclick="coding_save(\''.$usr_id.'\'); return false;">'.$lang['srv_close_profile'].'</a>';
		echo '</p>';
		
		echo '</form>';
		
		if ($editing !== false) {
			echo '<script> $(\'.edit_'.$editing.'\').show(); </script>';
		}
		
	}
	
	function ajax_coding_save () {
		Common::updateEditStamp();
		
		$usr_id = $_POST['usr_id'];
		
		foreach ($_POST AS $key => $val) {
			
			if ( substr($key, 0, 8) == 'visible_' ) {
				
				$spr_id = substr($key, 8);

				include_once('../../main/survey/app/global_function.php');
				new \App\Controllers\SurveyController(true);
				save('anketa', $this->anketa);
				save('forceShowSpremenljivka', true);
				save('usr_id', $usr_id);
				\App\Models\SaveSurvey::getInstance()->posted(0, $spr_id);
			
			}
		}
		
		self::forceRefreshData($this->anketa);
		
	}
	
	function ajax_vrednost_new () {
		Common::updateEditStamp();
		
		$usr_id = $_POST['usr_id'];
		
		$v = new Vprasanje($this->anketa);
		$v->spremenljivka = $_POST['spr_id'];
		
		$v->vrednost_new($_POST['naslov']);
		
		$this->ajax_coding();
		
		self::forceRefreshData($this->anketa);
		
	}
	
	function ajax_spremenljivka_new () {
		Common::updateEditStamp();
		global $global_user_id;
		
		$usr_id = $_POST['usr_id'];
		$spr_id = $_POST['spr_id'];
		
		ob_start();
		$ba = new BranchingAjax($this->anketa);
		$ba->ajax_spremenljivka_new($spr_id, 0, 0, 0, 2, 0);
		$spr_new = $ba->spremenljivka;
		ob_clean();
		
		SurveyVariablesProfiles::Init($this->anketa, $global_user_id);
		$variables = SurveyVariablesProfiles::getProfileVariables(0);
		if ( count($variables['variables']) > 0 ) {
			SurveyVariablesProfiles::setProfileVariables(0, implode(',', $variables['variables']).','.$spr_new.'_0');
			SurveyVariablesProfiles::setDefaultProfile(0);
		}
		
		// spremenimo ime, coding
		$s = sisplet_query("UPDATE srv_spremenljivka SET variable_custom='1' WHERE id='$spr_id'");
					
		$var = Cache::get_spremenljivka($spr_id, 'variable');
		$i = 1;
		do {
			$variable = $var.'_coding_'.$i++;
			$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE variable='$variable' AND s.gru_id=g.id AND g.ank_id='".$this->anketa."'");
		} while (mysqli_num_rows($sql) > 0);
		
		if ($_POST['naslov'] != '')
			$naslov = $_POST['naslov'];
		else
			$naslov = $variable;
		
		$s = sisplet_query("UPDATE srv_spremenljivka SET coding='$spr_id', visible='0', naslov='".$naslov."', variable='".$variable."', variable_custom='1', size='0' WHERE id='$spr_new'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$s = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_new'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id = '$spr_new'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$this->ajax_coding();
		
		self::forceRefreshData($this->anketa);
		
	}
	
	
	function ajax_tip () {
		Common::updateEditStamp();
		
		$spr_id = $_POST['spr_id'];
		$tip = $_POST['tip'];
		
		$v = new Vprasanje($this->anketa);
		$v->change_tip($spr_id, $tip);
		
		Common::prestevilci($spr_id);
		
		$this->ajax_coding();
		
		self::forceRefreshData($this->anketa);
		
	}
	
	function ajax_coding_merge () {
		Common::updateEditStamp();
		
		$spr_id = $_POST['spr_id'];
		$vre_id = $_POST['vre_id'];
		$merge = $_POST['merge'];
		
		$s = sisplet_query("UPDATE srv_data_vrednost$this->db_table SET vre_id='$merge' WHERE vre_id='$vre_id' AND spr_id='$spr_id'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = sisplet_query("DELETE FROM srv_vrednost WHERE id = '$vre_id'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$this->ajax_coding($spr_id);
		
		self::forceRefreshData($this->anketa);
		
	}
	
	function ajax_mass_coding () {
		global $lang;
				
		$spr_new = $this->mass_coding_auto();
		
		$link = '<a href="index.php?anketa='.$this->anketa.'&a=data&m=coding&highlight_spr='.$spr_new.'&relevance=0">'.$lang['srv_hand_coding'].'</a>';
		
		// enajblamo labele
		session_start();
		$_SESSION['sid_'.$this->anketa]['dataIcons_labels'] = true;
		
		echo '<p>';
		
		printf($lang['srv_auto_coding_end'], $this->mass_coding_auto_vars, $link);
		
		echo '</p>';
		
	}
	
	private $mass_coding_auto_vars = 0;
	
	function mass_coding_auto () {
		global $global_user_id;
		Common::updateEditStamp();
		
		global $lang;
		
		$coding_type = $_POST['coding_type'];
		
		$seq = $_POST['seq'];
		$seq = explode('-', $seq);
		
		$spr_id = (int)$seq[1];
		$sequence = (int)$seq[0];

		// frekvence besed poberemo iz analize
		SurveyAnalysis::Init($this->anketa);
		SurveyAnalysis::setUpFilter(1);
		$freq = SurveyAnalysis::getFrequencys();
		
		// imamo sekvenco polja (pozicijo v text fajlu)
		if ($sequence != false) {
			
			$besede = $freq[$sequence]['valid'];
			$koreni = array();
			
			// sestavimo array korenov
			foreach ($besede AS $beseda => $count) {
				
				if (strlen( iconv("UTF-8", "ISO-8859-2", $beseda) ) >= 3) {	// manjsih od  3 znakov ne upostevamo
					
					if ($coding_type == 1) {
					
						$k = iconv("ISO-8859-2", "UTF-8", substr( iconv("UTF-8", "ISO-8859-2", $beseda), 0, round(strlen( iconv("UTF-8", "ISO-8859-2", $beseda) )*0.75) ) );
					
						if ( array_key_exists($k, $koreni) ) {
							$koreni[$k] += $count;
						} else {
							$koreni[$k] = $count;
						}
						
					} elseif ($coding_type == 2) {
						
						$razbita = explode(",", $beseda);
						foreach ($razbita AS $part) {
							$k = iconv("ISO-8859-2", "UTF-8", substr( iconv("UTF-8", "ISO-8859-2", $part), 0, round(strlen( iconv("UTF-8", "ISO-8859-2", $part) )*0.75) ) );
					
							if ( array_key_exists($k, $koreni) ) {
								$koreni[$k] += $count;
							} else {
								$koreni[$k] = $count;
							}
						}
						
					}
				}
			}
			
			// array obrnemo, da imamo vrstni_red => koren
			$i = 1;
			foreach ($koreni AS $key => $val) {
				$koren[$i++] = $key;
			}
			
			if (count($koren) > 0) {
				
				// naredimo novo spremenljivko
				ob_start();
				$ba = new BranchingAjax($this->anketa);
				$ba->ajax_spremenljivka_new($spr_id, 0, 0, 0, $coding_type, 0);
				$spr_new = $ba->spremenljivka;
				ob_clean();
				
				if ($spr_new > 0) {
					
					// spremenimo ime, coding
					$s = sisplet_query("UPDATE srv_spremenljivka SET variable_custom='1' WHERE id='$spr_id'");
					
					$var = Cache::get_spremenljivka($spr_id, 'variable');
					$i = 1;
					do {
						$variable = $var.'_coding_'.$i++;
						$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='$variable' AND s.gru_id=g.id AND g.ank_id='".$this->anketa."'");
					} while (mysqli_num_rows($sql) > 0);
					
					$s = sisplet_query("UPDATE srv_spremenljivka SET coding='$spr_id', visible='0', naslov='".$variable."', variable='".$variable."', variable_custom='1', size='".count($koren)."' WHERE id='$spr_new'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					$s = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_new'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					
					// napolnimo vrednosti spremenljivke s koreni
					$koren_new = array();
					foreach ($koren AS $key => $val) {
						$val = ucfirst(strtolower( $val ));
						$s = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$spr_new', '$val', '$key', '$key')");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
						
						$koren_new[strtolower($val)] = mysqli_insert_id($GLOBALS['connect_db']);
					}
					
					// gremo cez tekstovne podatke in vnasamo vrednosti
					$sql = sisplet_query("SELECT usr_id, text FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id'");
					while ($row = mysqli_fetch_array($sql)) {
						
						if ($coding_type == 1) {
							
							$k = iconv("ISO-8859-2", "UTF-8", substr( iconv("UTF-8", "ISO-8859-2", $row['text']), 0, round(strlen( iconv("UTF-8", "ISO-8859-2", $row['text']) )*0.75) ) );
							$k = strtolower($k);
							
							if ( array_key_exists($k, $koren_new) ) {
							
								$s = sisplet_query("DELETE FROM srv_data_vrednost$this->db_table WHERE spr_id='$spr_new' AND usr_id='$row[usr_id]'");	// ker se zafilajo z -4
								$s = sisplet_query("INSERT INTO srv_data_vrednost$this->db_table (spr_id, vre_id, usr_id, loop_id) VALUES('$spr_new', '$koren_new[$k]', '$row[usr_id]', NULL)");
								if (!$s) echo mysqli_error($GLOBALS['connect_db']);
								
								$this->mass_coding_auto_vars++;
							}
						
						} elseif ($coding_type == 2) {
							
							$s = sisplet_query("DELETE FROM srv_data_vrednost$this->db_table WHERE spr_id='$spr_new' AND usr_id='$row[usr_id]'");	// ker se zafilajo z -4
							
							foreach ($koren_new AS $k => $v) {
								if ( strpos($row['text'], $k) !== false ) {
									
									$s = sisplet_query("INSERT INTO srv_data_vrednost$this->db_table (spr_id, vre_id, usr_id, loop_id) VALUES('$spr_new', '$koren_new[$k]', '$row[usr_id]', NULL)");
									if (!$s) echo mysqli_error($GLOBALS['connect_db']);
									
									$this->mass_coding_auto_vars++;
									
								}
									
							}
							
						}
						
					}
					
					$variables = $spr_id.'_0,'.$spr_new.'_0';
					SurveyVariablesProfiles::Init($this->anketa, $global_user_id);
					SurveyVariablesProfiles::setProfileVariables(0, $variables);
					SurveyVariablesProfiles::setDefaultProfile(0);
					
					self::forceRefreshData($this->anketa);
					
					return $spr_new;
					
				}
				
			}
			
		}
		
		return 0;
		
	}
	
	function ajax_coding_filter () {
		global $global_user_id;
		global $lang;
		
		$seq = $_POST['seq'];
		$seq = explode('-', $seq);
		
		$spr_id = (int)$seq[1];
		$sequence = (int)$seq[0];
		$variables = array();
		$variables[] = $spr_id.'_0';
		
		$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE coding = '$spr_id'");
		while ($row = mysqli_fetch_array($sql)) {
			$variables[] = $row['id'].'_0';
		}
		
		$variables = serialize($variables);
		
		SurveyVariablesProfiles::Init($this->anketa, $global_user_id);
		SurveyVariablesProfiles::setProfileVariables(-1, $variables);
		SurveyVariablesProfiles::setDefaultProfile(-1);
	}
	
	static function forceRefreshData( $anketa ) {
		
		if ( file_exists( dirname(__FILE__) . '/../surveyData/export_dashboard_'.$anketa.'.html' ) )
			unlink( dirname(__FILE__) . '/../surveyData/export_dashboard_'.$anketa.'.html' );
		
		if ( file_exists( dirname(__FILE__) . '/../surveyData/export_data_'.$anketa.'.dat' ) )
			unlink( dirname(__FILE__) . '/../surveyData/export_data_'.$anketa.'.dat' );
		
		if ( file_exists( dirname(__FILE__) . '/../surveyData/export_header_'.$anketa.'.dat' ) )
			unlink( dirname(__FILE__) . '/../surveyData/export_header_'.$anketa.'.dat' );
		
	}
	
}
 
?>