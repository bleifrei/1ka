<?php

class RecodeValues {
	private static $anketa = null;					# trenutna anketa
	private static $spremenljivka = null;			# trenutna spremenljivka
	private static $db_table = '';					# aktivnost
	private static $sprInfo = null;					# podatki o trenutni spremenljivki
	private static $cacheSpremenljivka = null;		# zakeširani podatki spremenljivke
	private static $smv = null;						# manjkajoe vrednosti ankete
	private static $options = null;					# možne izbire manjkajočih vrednosti
	private static $variables = null;				# Variable vprašanja
	private static $_operators = array(				# operatorji
							'0'=>'==',				# ==
							'1'=>'&ne;',	        # ≠
							'2'=>'&lt;',			# <
							'3'=>'&gt;',			# >
							'4'=>'&lt;=',			# <=
							'5'=>'&gt;=',			# >=
							'6'=>'interval'			# interval
							);	

	/**
	 * konstruktor
	 *
	 * @param mixed $anketa
	 */
	
	function __construct ($anketa = 0, $spremenljivka = 0) {
		if ((int)$anketa > 0 && (int)$spremenljivka > 0) {
			self::$anketa = $anketa;
			self::$spremenljivka = $spremenljivka;

			SurveyInfo::getInstance()->SurveyInit($anketa);	
            self::$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
			
			# polovimo manjkajoče privzete vrednosti ankete
			self::$smv = new SurveyMissingValues(self::$anketa); 
			self::$smv -> Init();
			
			self::$cacheSpremenljivka = Cache::srv_spremenljivka(self::$spremenljivka);
		}
	}
	
	public static function DisplayMissingValuesForQuestion($displayForm=true) {
		global $lang;
		
		echo '<form name="spremenljivka_recode" method="post">';
		echo '<div id="recodeToNewSpr">';
		self::displayRecodeType();
		echo '</div>';
		echo '</form>';
	}

	
	/** Naloži manjkajoče vrednosti za spremenljivko
	 * 
	 * 
	 * Enter description here ...
	 */
	private static function LoadSpremenljivkaMissingValues($spremenljivka=null) {
		global $lang;
		if ($spremenljivka == null) {
			$spremenljivka = self::$spremenljivka;
		}
		
		$cacheSpremenljivka = Cache::srv_spremenljivka($spremenljivka);
		
		self::$options = array();
		self::$variables = array();
		if ((int)$spremenljivka > 0) {
			self::$options['_0_'] = array('naslov'=>$lang['srv_recode_type0'], 'variable'=>null, 'type'=>0);
			switch ($cacheSpremenljivka['tip']) {
				case 1:		# radio
				case 3:		# select
					$sql1 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id = '".$spremenljivka."' ORDER BY vrstni_red ASC");
					if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
					if (mysqli_num_rows($sql1)) {
						
						while ($row1 = mysqli_fetch_assoc($sql1)) {
							self::$variables[$row1['id']] = array('naslov'=>$row1['naslov'], 'variable'=>$row1['variable']);
							self::$options[$row1['id']] = array('naslov'=>$row1['naslov'], 'variable'=>$row1['variable'], 'type'=>0);
						}
					}
				break;
				case 17:	# razvrščanje
					$sql1 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_vrednost WHERE spr_id = '".$spremenljivka."' ORDER BY vrstni_red ASC");
					if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
					if (mysqli_num_rows($sql1)) {
						while ($row1 = mysqli_fetch_assoc($sql1)) {
							self::$variables[$row1['id']] = array('naslov'=>$row1['naslov'], 'variable'=>$row1['vrstni_red']);
							self::$options[$row1['id']] = array('naslov'=>$row1['naslov'], 'variable'=>$row1['vrstni_red'], 'type'=>0);
						}
					}
				break;
				case 2:		# checkbox
					self::$variables['1'] = array('naslov'=>'izbran', 'variable'=>'1');
					self::$variables['0'] = array('naslov'=>'neizbran', 'variable'=>'0');
					self::$options['1'] = array('naslov'=>'izbran', 'variable'=>'1', 'type'=>0);
					self::$options['0'] = array('naslov'=>'neizbran', 'variable'=>'0', 'type'=>0);
				break;
				case 6:		# multiradio
				case 19:	# multitext
				case 20:	# multinumber
					$sql1 = sisplet_query("SELECT naslov, variable FROM srv_grid WHERE spr_id='".$spremenljivka."' ORDER BY vrstni_red ASC");
					if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
					if (mysqli_num_rows($sql1)) {
						while ($row1 = mysqli_fetch_assoc($sql1)) {
							self::$variables[$row1['variable']] = array('naslov'=>$row1['naslov'], 'variable'=>$row1['variable']);
							self::$options[$row1['variable']] = array('naslov'=>$row1['naslov'], 'variable'=>$row1['variable']);
						}
					}
				break;
				case 16:	# multicheckbox
					self::$variables['0'] = array('naslov'=>'izbran', 'variable'=>'0');
					self::$variables['1'] = array('naslov'=>'neizbran', 'variable'=>'1');
					self::$options['0'] = array('naslov'=>'izbran', 'variable'=>'0', 'type'=>0);
					self::$options['1'] = array('naslov'=>'neizbran', 'variable'=>'1', 'type'=>0);
				break;
			}
		}
		# dodamo manjkajoče vrednosti če še niso
		# preberemo možne manjkajoče vrednosti
		$smvs = self::$smv-> GetSystemFlterByType(array(1));
		# zgeneriramo matriko izbranih manjkajočih vrednosti za anketo (anketne oz. sistemske mv)
		self::$options['_1_'] = array('naslov'=>$lang['srv_recode_type1'], 'variable'=>null, 'type'=>0);
		if (count($smvs)> 0) {
			foreach ($smvs AS $key => $value) {
				self::$options[$key] = array('naslov'=>$value, 'variable'=>$key, 'type'=>1);
			} 
		}
		$smvs = self::$smv-> GetSystemFlterByType(array(2,3));
		# zgeneriramo matriko izbranih manjkajočih vrednosti za anketo (anketne oz. sistemske mv)
		self::$options['_2_'] = array('naslov'=>$lang['srv_recode_type2'], 'variable'=>null, 'type'=>0);
		if (count($smvs)> 0) {
			foreach ($smvs AS $key => $value) {
				self::$options[$key] = array('naslov'=>$value, 'variable'=>$key, 'type'=>2);
			} 
		}
	}
	
	private static function show_new_mv_div() {
		echo '<div id="vprasanje_recode_new">';
		self::show_new_mv();
		echo '</div>';
	}
	
	private static function show_new_mv() {
		global $lang;
		
		echo 'Izberi vrednost:';
		echo '<input id="new_mv_value" type="text" size="5" autocomplete="off">';
		
		echo '<br/>';
		echo self::$spremenljivka;
		echo '<br/>';
		
		echo '<span class="buttonwrapper spaceRight floatRight">';
        echo '<a class="ovalbutton ovalbutton_orange" href="/" onclick="new_mv_add(\''.self::$spremenljivka.'\'); return false;"><span>'.$lang['add'].'</span></a>';
        echo '</span>';
		
		echo '<span class="buttonwrapper spaceRight floatRight">';
        echo '<a class="ovalbutton ovalbutton_gray" href="/" onclick="show_new_mv_cancel(); return false;"><span>'.$lang['srv_cancel'].'</span></a>';
        echo '</span>';        
	}
	
	private static function new_mv_add() {
		echo 'Napaka!!!';
		self::show_new_mv();
	}
	
	public static function Ajax() {
		switch ($_GET['a']) {
			case 'show_new_mv':
				self::show_new_mv_div();
			break;
			case 'new_mv_add':
				#self::new_mv_add();
			break;
			case 'refresh_mv':
				self::DisplayMissingValuesForQuestion();
			break;

			case 'add_new_numeric':
				self::AddNewNumericRecode();
			break;
			case 'changeRecodeType':
				self::changeRecodeType();
			break;
			default:
#				print_r($_POST);
#				print_r($_GET);
			break;
		}
	}
	
	public function removeRecodeForQuestion() {
		if ((int)self::$anketa > 0 && (int)self::$spremenljivka > 0) {
			# odstranimo rekodiranje za spremenljivko
			$sql = sisplet_query("DELETE FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			
			# preverimo ali imamo rekodiranje v novo spremenljivko, če ja, kasneje ponudimo tud izbris psremenljivke
			$qry = sisplet_query("SELECT to_spr_id FROM srv_recode_spremenljivka WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'");
			list($spr_id) = mysqli_fetch_row($qry);
			# pobrišemo še zapise v srv_recode_vrednost in srv_recode spremenljika
			$sql = sisplet_query("DELETE FROM srv_recode_spremenljivka WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

			$sql = sisplet_query("DELETE FROM srv_recode_vrednost WHERE ank_id = '".self::$anketa."' AND spr1 = '".self::$spremenljivka."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			
			sisplet_query("COMMIT");
			return (int)$spr_id;
		} else {
			return 0; 
		}
	}
	
	public function SetUpMissingValuesForQuestion() {
		if ((int)self::$anketa > 0 ) {
			# pobrišemo rekodirane vrednosti za morebitno rekodiranje v novo spremenljivko
			if (isset($_REQUEST['recodeToSpr']) && (int)$_REQUEST['recodeToSpr'] > 0) {
				$sql = sisplet_query("DELETE FROM srv_recode_vrednost WHERE ank_id = '".self::$anketa."' AND spr2 = '".(int)$_REQUEST['recodeToSpr']."'");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			}
		
		
			# če imamo spremembe je potrebno na novo zgenerirati datoteko
			$strSelect = "SELECT search, value, operator FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."' ORDER BY vrstni_red";
			$sqlSelect = sisplet_query($strSelect);
			$rowsBefore = array();
			$rowsAfter = array();
			while (list($search, $value, $operator) = mysqli_fetch_row($sqlSelect)) {
				$rowsBefore[] = $search."_".$value."_".(int)$operator;
			}
			
			# pobrišemo vse obstoječe zamenjave
			$sql = sisplet_query("DELETE FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			
			#rekodiramo kategorije
			if ( isset($_REQUEST['edit_recode_mv']) ) {
				# dodamo potrebne zamenjave za 
				$_for_replace = "";
				foreach ($_REQUEST as $key => $value) {
					$_tmp_value = substr($key, 5);
					if (substr($key, 0, 5) == 'MVFQ_' && $_tmp_value != $value) {
						if ($_for_replace != "") { $_for_replace .= ","; }
						$_for_replace .= " ('".self::$anketa."', '".self::$spremenljivka."', '$_tmp_value', '$value') ";
					}
				}
				
				
				# če moramo uporabiti zamenjave: 			
				if ($_for_replace != "") {
					$insertString = 'INSERT INTO srv_recode (ank_id, spr_id, search, value) VALUES '.$_for_replace;
					$sql1 = sisplet_query($insertString);
				}
			} else if( isset($_REQUEST['edit_recode_number']) ){
				# rekodiramo numerične
				$recoded = array();
				if (isset($_REQUEST['recode_number_operator'])) {
					foreach ($_REQUEST['recode_number_operator'] as $key => $value) {
						$recoded[$key]['operator'] = $value;
					}	
				}
				if (isset($_REQUEST['recode_number_search'])) {
					foreach ($_REQUEST['recode_number_search'] as $key => $value) {
						$recoded[$key]['search'] = trim($value);
					}	
				}
				if (isset($_REQUEST['recode_number_search1'])) {
					foreach ($_REQUEST['recode_number_search1'] as $key => $value) {
						$recoded[$key]['search1'] = trim($value);
					}	
				}
				if (isset($_REQUEST['recode_number_search2'])) {
					foreach ($_REQUEST['recode_number_search2'] as $key => $value) {
						$recoded[$key]['search2'] = trim($value);
					}	
				}
				if (isset($_REQUEST['recode_number_value'])) {
					foreach ($_REQUEST['recode_number_value'] as $key => $value) {
						$recoded[$key]['value'] = trim($value);
					}	
				}
				# pripravimo query
				if (count($recoded) > 0) {
					$_for_replace = "";
					$already_set = array();
					$vrstni_red = 1;
					foreach ($recoded AS $rKey => $recode) {
						$opreand = (int)$recode['operator'];
						if ( $opreand != 6) {
							$_search = $recode['search'];
						} else {
							$_search = $recode['search1'].','.$recode['search2'];
						}  
						if ( $_search != '' && $recode['value'] != ''
							#preprečimo zamenjavo iste vrednosti z istim operatorejm. 
							&& !( isset($already_set[$_search.'_'.$recode['operator']] ))
						) {
							if ($_for_replace != "") { 
								$_for_replace .= ","; 
							}
							
							$_for_replace .= " ('".self::$anketa."', '".self::$spremenljivka."', '$vrstni_red', '$_search', '".trim($recode['value'])."', '".(int)trim($recode['operator'])."') ";
							
							$already_set[$_search.'_'.$recode['operator']] = true;
						}
						$vrstni_red ++;
					}
					if ($_for_replace != "") {
						# izvedemo insert query
						$insertString = 'INSERT INTO srv_recode (ank_id, spr_id, vrstni_red, search, value, operator) VALUES '.$_for_replace;
						$sql1 = sisplet_query($insertString);
						if (!$sql1) {
							print_r(mysqli_error($GLOBALS['connect_db']));
						}
					}
				}
			
			}
			sisplet_query("COMMIT");
			
			# če imamo spremembe je potrebno na novo zgenerirati datoteko
			$strSelect = "SELECT search, value, operator FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."' ORDER BY vrstni_red";
			$sqlSelect = sisplet_query($strSelect);
			while (list($search, $value, $operator) = mysqli_fetch_row($sqlSelect)) {
				$rowsAfter[] = $search."_".$value."_".(int)$operator;
			}			
			if ( (count( array_diff($rowsBefore, $rowsAfter) ) +  count( array_diff($rowsAfter, $rowsBefore) ) ) > 0) {
				# array-a nista enaka, imamo spremembe
				# pobrišimo datoteko, da se podatki zgenerirajo na novo
				global $site_path;
				$data_file_name = $site_path.'admin/survey/SurveyData/export_data_'.self::$anketa.'.dat';
				if (file_exists($data_file_name)) {
					unlink($data_file_name);
				}
				return true;
				
			}
			return false;
		}
		return false;
	}
	
	
	public function SetRecodeNumberNewVrednost() {
		if ((int)self::$anketa > 0 && (int)$_REQUEST['recode_type']== 1 && (int)$_REQUEST['edit_recode_number'] == 1) {

			# pobrišemo rekodirane vrednosti za morebitno rekodiranje v novo spremenljivko
			if ((int)self::$spremenljivka > 0) {
				$sql = sisplet_query("DELETE FROM srv_recode_vrednost WHERE ank_id = '".self::$anketa."' AND spr1 = '".(int)self::$spremenljivka."'");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			}
	
			# pobrišemo vse mmorebitne obstoječe zamenjave za normalni number
			$sql = sisplet_query("DELETE FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
					
			# pobrišemo vse morebitne obstoječe zamenjave za number
			$sql = sisplet_query("DELETE FROM srv_recode_number WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
					
			if( isset($_REQUEST['recode_number_vrednost']) ){
				# rekodiramo numerične
				$recoded = array();
				if (isset($_REQUEST['recode_number_operator'])) {
					foreach ($_REQUEST['recode_number_operator'] as $key => $value) {
						$recoded[$key]['operator'] = $value;
					}
				}
				if (isset($_REQUEST['recode_number_search'])) {
					foreach ($_REQUEST['recode_number_search'] as $key => $value) {
						$recoded[$key]['search'] = trim($value);
					}
				}
				if (isset($_REQUEST['recode_number_search1'])) {
					foreach ($_REQUEST['recode_number_search1'] as $key => $value) {
						$recoded[$key]['search1'] = trim($value);
					}
				}
				if (isset($_REQUEST['recode_number_search2'])) {
					foreach ($_REQUEST['recode_number_search2'] as $key => $value) {
						$recoded[$key]['search2'] = trim($value);
					}
				}
				if (isset($_REQUEST['recode_number_vrednost'])) {
					foreach ($_REQUEST['recode_number_vrednost'] as $key => $value) {
						$recoded[$key]['value'] = trim($value);
					}
				}
				# pripravimo query
				if (count($recoded) > 0) {
					$_for_replace = "";
					$already_set = array();
					$vrstni_red = 1;
					foreach ($recoded AS $rKey => $recode) {
						$opreand = (int)$recode['operator'];
						if ( $opreand != 6) {
							$_search = $recode['search'];
						} else {
							$_search = $recode['search1'].','.$recode['search2'];
						}
						if ( $_search != '' && $recode['value'] != ''
								#preprečimo zamenjavo iste vrednosti z istim operatorejm.
								&& !( isset($already_set[$_search.'_'.$recode['operator']] ))
						) {
							if ($_for_replace != "") {
								$_for_replace .= ",";
							}
	
							$_for_replace .= " ('".self::$anketa."', '".self::$spremenljivka."', '$vrstni_red', '$_search', '".trim($recode['value'])."', '".(int)trim($recode['operator'])."') ";
	
							$already_set[$_search.'_'.$recode['operator']] = true;
						}
						$vrstni_red ++;
					}
					if ($_for_replace != "") {
						# izvedemo insert query
						$insertString = 'INSERT INTO srv_recode_number (ank_id, spr_id, vrstni_red, search, vred_id, operator) VALUES '.$_for_replace;
						$sql1 = sisplet_query($insertString);
						if (!$sql1) {
							print_r(mysqli_error($GLOBALS['connect_db']));
						}
					}
				}
	
			}
			sisplet_query("COMMIT");
		}
		return false;
	}
	
	public function AddNewNumericRecode() {
		global $lang;
		
		$recode_type = (int)$_POST['recode_type'];
		
		self::LoadSpremenljivkaMissingValues();
		
		if ( $recode_type == 0) {
			echo '<li>';
			echo '<span class="faicon move_updown moveY"></span>';
			printf($lang['srv_recode_number_if'],self::$cacheSpremenljivka['variable']);
			echo '&nbsp;<select name="recode_number_operator[]" onChange="recode_operator_changed(this);">';
			foreach (self::$_operators AS $value => $operator) {
				$selected = ($value == '0') ? ' selected="selected"' : ''; 
				echo '<option value="'.$value.'"'.$selected.'>'.$operator.'</option>';
			}
			echo '</select>';
			#echo '&nbsp;<input type="text" name="recode_number_search[]" value="0" size="5" autocomplete="off"/>';
			
			echo '<span class="recode_int_first">';
			echo '&nbsp;<input type="text" name="recode_number_search[]" value="0" size="12" autocomplete="off"/>';
			echo '</span>';
			echo '<span class="recode_int_seccond hidden">';
			echo '&nbsp;[<input type="text" name="recode_number_search1[]" value="0" size="5" autocomplete="off"/>,';
			echo '<input type="text" name="recode_number_search2[]" value="1" size="5" autocomplete="off"/>]';
			echo '</span>';
			
			echo '&nbsp;'.$lang['srv_recode_number_to'];
							
			echo '<select name="recode_number_type[]" onChange="recode_number_type_changed(this);">';
			foreach (self::$options AS $oKey => $option) {
				if ($option['variable'] == null && $oKey != '_0_') {
					echo '<option disabled="disabled">'.$option['naslov'].'</option>';
					$_value = null;								
				} else {
					$_value = ($oKey == '_0_') ? '_' : $option['variable'];
					$_selected = ($oKey == '_0_') ? 'selected="selected"' : '';
					$_label = ($oKey == '_0_') ? $option['naslov'] : '&nbsp;&nbsp;'.$option['naslov'].' ['.$option['variable'].']'; 
					echo '<option value="'.$_value.'" '.$_selected.'>'.$_label.'</option>';
				}
			}
			echo '</select>';
			
			echo '&nbsp;<input type="text" name="recode_number_value[]" value="0" size="5" autocomplete="off"/>';
			echo '&nbsp;<span class="floatRight spaceRight faicon delete_circle icon-orange_link" onclick="recode_delete_numeric(\''.self::$spremenljivka.'\',this);"></span>';
			echo '</li>';
		} else {
			# polovimo osnovne lastnosti rekodiranja
			$selectString = "SELECT to_spr_id FROM srv_recode_spremenljivka WHERE ank_id='".self::$anketa."' AND spr_id='".self::$spremenljivka."'";
			$sqlSelect = sisplet_query($selectString);
			if (!$sqlSelect) echo mysqli_error($GLOBALS['connect_db']);
			list($to_spr_id) = mysqli_fetch_row($sqlSelect);
			
			self::LoadSpremenljivkaMissingValues($to_spr_id);
			$new_spr_vrednosti = self::$options;
			self::LoadSpremenljivkaMissingValues(self::$spremenljivka);
				

			echo '<li>';
			echo '<span class="faicon move_updown moveY"></span>';
			printf($lang['srv_recode_number_if'],self::$cacheSpremenljivka['variable']);
			echo '&nbsp;<select name="recode_number_operator[]" onChange="recode_operator_changed(this);">';
			foreach (self::$_operators AS $value => $operator) {
				$selected = ($value == '0') ? ' selected="selected"' : '';
				echo '<option value="'.$value.'"'.$selected.'>'.$operator.'</option>';
			}
			echo '</select>';
			#echo '&nbsp;<input type="text" name="recode_number_search[]" value="0" size="5" autocomplete="off"/>';
				
			echo '<span class="recode_int_first">';
			echo '&nbsp;<input type="text" name="recode_number_search[]" value="0" size="12" autocomplete="off"/>';
			echo '</span>';
			echo '<span class="recode_int_seccond hidden">';
			echo '&nbsp;[<input type="text" name="recode_number_search1[]" value="0" size="5" autocomplete="off"/>,';
			echo '<input type="text" name="recode_number_search2[]" value="1" size="5" autocomplete="off"/>]';
			echo '</span>';
				
			echo '&nbsp;'.$lang['srv_recode_number_to'];
			# imamo rekodiranje v  novo spremenljivko
			$recode['vred_id'] = '-1';
				
			echo '<select name="recode_number_vrednost[]">';
			if (is_array($new_spr_vrednosti) && count($new_spr_vrednosti)>0) {
				foreach ($new_spr_vrednosti AS $oKey => $option) {
					if ($option['variable'] == null) {
						echo '<option disabled="disabled">'.$option['naslov'].'</option>';
					} else {
						$_selected = ($recode['vred_id'] == $option['variable']) ? ' selected="selected"' : '';
						$_label = $option['naslov'].' ['.$option['variable'].']';
						echo '<option value="'.$option['variable'].'"'.$_selected.'>&nbsp;&nbsp;'.$_label.'</option>';
					}
					#	$_label = $option['naslov'].' ['.$option['variable'].']';
					#	echo '<option value="'.$option['id'].'" '.$_selected.'>'.$_label.'</option>';
				}
			}
			echo '</select>';

			echo '&nbsp;<span class="floatRight spaceRight faicon delete_circle icon-orange_link" onclick="recode_delete_numeric(\''.self::$spremenljivka.'\',this);"></span>';
			echo '</li>';	
		}
		
		return;
	}
	
	public function hasRecodedValues() {
		#preštejemo koliko rekodiranj imamo za spremenljivko
		$strSelect = "SELECT count(*) FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."'";
		$sqlSelect = sisplet_query($strSelect);
		list($count) = mysqli_fetch_row($sqlSelect);
		return (int)$count;
	}
	
	
	public function changeRecodeType() {
		$insertString = "INSERT INTO srv_recode_spremenljivka (ank_id, spr_id, recode_type, to_spr_id) VALUES ('".self::$anketa."', '".self::$spremenljivka."', '".(int)$_POST['recode_type']."', '".(int)$_POST['recodeToSpr']."') ON DUPLICATE KEY UPDATE recode_type = '".(int)$_POST['recode_type']."',  to_spr_id = '".(int)$_POST['recodeToSpr']."'";
		$sqlInsert = sisplet_query($insertString);
		self::displayRecodeType();
	}
	
	public static function displayRecodeType() {
		global $lang;
		$tip = self::$cacheSpremenljivka['tip'];
		if (in_array($tip, array(
				1,	# radio
				2,	# checkbox
				3,	# select
				6,	# multiradio
				16,	# multicheck
				17	# razvrščanje
				))) {
				$displayType=1;
		} else if (in_array($tip, array(
				7,	# number
				18,	# vsota
				20,	# multinumber
				22,	# multinumber
				21,	# textovni
				))) {
				$displayType=2;
		}
		# polovimo osnovne lastnosti rekodiranja
		$selectString = "SELECT recode_type, to_spr_id FROM srv_recode_spremenljivka WHERE ank_id='".self::$anketa."' AND spr_id='".self::$spremenljivka."'";
		$sqlSelect = sisplet_query($selectString);
		if (!$sqlSelect) echo mysqli_error($GLOBALS['connect_db']);
		list($recode_type, $to_spr_id) = mysqli_fetch_row($sqlSelect);

		if ((int)$recode_type == 0) {
			self::LoadSpremenljivkaMissingValues(self::$spremenljivka);
			# polovimo missinge za spremenljivko
			$strSelect = "SELECT search, value, operator FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."' ORDER BY vrstni_red";
			$sqlSelect = sisplet_query($strSelect);
			if (!$sqlSelect) echo mysqli_error($GLOBALS['connect_db']);
			$recoded = array();
			if (mysqli_num_rows($sqlSelect)) {
				while ($rowSelect = mysqli_fetch_assoc($sqlSelect)) {
					$recoded[$rowSelect['search'].'_'.$rowSelect['operator']] = array('search'=>$rowSelect['search'],'value'=>$rowSelect['value'],'operator'=>$rowSelect['operator']);
				}
			}
		} else {
			
			if ($displayType == 1) {
				# polovimo še vrednosti če imamo rekodirano v drugo spremenljivko
				$qry_str = "select vre1, spr2, vre2 from srv_recode_vrednost WHERE ank_id = '".self::$anketa."' AND spr1 = '".self::$spremenljivka."'";
				$sql = sisplet_query($qry_str);
				$recodedFrom = array();
				while (list($vre1, $spr1, $vre2) = mysqli_fetch_row($sql)) {
					$recodedFrom[$vre1] = $vre2;
				}
			} else {
				# polovimo missinge za spremenljivko
				$strSelect = "SELECT search, vred_id, operator FROM srv_recode_number WHERE ank_id = '".self::$anketa."' AND spr_id = '".self::$spremenljivka."' ORDER BY vrstni_red";
				$sqlSelect = sisplet_query($strSelect);
				if (!$sqlSelect) echo mysqli_error($GLOBALS['connect_db']);
				$recoded = array();
				if (mysqli_num_rows($sqlSelect)) {
					while ($rowSelect = mysqli_fetch_assoc($sqlSelect)) {
						$recoded[$rowSelect['search'].'_'.$rowSelect['operator']] = array('search'=>$rowSelect['search'],'vred_id'=>$rowSelect['vred_id'],'operator'=>$rowSelect['operator']);
					}
				}
			}
		
			self::LoadSpremenljivkaMissingValues($to_spr_id);
			$new_spr_vrednosti = self::$options;
			self::LoadSpremenljivkaMissingValues(self::$spremenljivka);
			
			# preverimo ali spremenljvka obstaja
			#$new_spr_vrednosti = array();
			if ((int)$to_spr_id > 0) {
				$sqlSpremenljivka = sisplet_query("SELECT s.naslov, s.variable, s.tip FROM srv_spremenljivka s WHERE s.id = '$to_spr_id'");
				$rowSpremenljivka = mysqli_fetch_assoc($sqlSpremenljivka);
				
				if (mysqli_num_rows($sqlSpremenljivka) == 0) {
					$to_spr_id = 0;
				}			
			}
		}
		
		echo '<input type="hidden" name="anketa" value="'.self::$anketa.'" autocomplete="off"/>';
		echo '<input type="hidden" name="spr_id" value="'.self::$spremenljivka.'" autocomplete="off"/>';
		echo '<input type="hidden" name="recodeToSpr" value="'.$to_spr_id.'" autocomplete="off"/>';
		echo '<input type="hidden" id="recIsCharts" value="'.((int)(($_GET['t'] == 'charts')||((int)$_POST['recIsCharts'] == 1)) ).'" autocomplete="off"/>';
		

		if ( $_GET['t'] == 'charts' || $_POST['recIsCharts'] == 1 ) {
			# če smo v grafih in smo na spremenljivki katera je že rekodirana iz druge, potem onemogočimo nadaljnje kodiranje
			$strSel = "SELECT count(*) FROM srv_recode_spremenljivka WHERE  to_spr_id = '".self::$spremenljivka."'";
			$qry = sisplet_query($strSel);
			list($cnt) = mysqli_fetch_row($qry);
			if ((int)$cnt > 0) {
				echo $lang['srv_recoded_advancetNote_recoded3'];
				echo self::$cacheSpremenljivka['variable'].' - '.self::$cacheSpremenljivka['naslov'];
				echo $lang['srv_recoded_advancetNote_recoded4'];
				return;
			}
		}
		
		
		if (self::$cacheSpremenljivka['tip'] == 1 || self::$cacheSpremenljivka['tip'] == 3 || self::$cacheSpremenljivka['tip'] == 7) {
			
			if ( (int)$recode_type == 1 && ((int)(($_GET['t'] == 'charts')||((int)$_POST['recIsCharts'] == 1))) ) {
				echo '<br/>';
				echo $lang['srv_recoded_advancetNote'].'<a href="index.php?anketa='.self::$anketa.'&a='.A_COLLECT_DATA.'&m='.M_COLLECT_DATA_RECODING.'">'.$lang['srv_here'].'</a>';
				echo Help::display('srv_recode_chart_advanced');
				echo '<br/>';
				echo '<br/>';
				echo $lang['srv_recoded_advancetNote_recoded1'];
				echo self::$cacheSpremenljivka['variable'];
				echo ' - '.strip_tags(self::$cacheSpremenljivka['naslov']);
				echo $lang['srv_recoded_advancetNote_recoded2'];
				echo $rowSpremenljivka['variable'].' - '.$rowSpremenljivka['naslov'];
				return;
			}
			$sugestedName = strip_tags(self::$cacheSpremenljivka['naslov'].' - recoded');
			echo'<div id="divRecodeSprOption">';
			echo '<label>'.$lang['srv_recode_to_spr'].'</label>';
			if ( $_GET['t'] == 'charts' || $_POST['recIsCharts'] == 1 ) {
				echo $lang['srv_recode_to_spr_same'];
			} else {
				echo '<label><input type="radio" name="recode_type" value="0" '.((int)$recode_type == 0 ? ' checked="checked"' : '').'onchange="changeRecodeType();return false;">'.$lang['srv_recode_to_spr_same'].'</label>';
				echo '<label><input type="radio" name="recode_type" value="1" '.((int)$recode_type == 1 ? ' checked="checked"' : '').'onchange="changeRecodeType();return false;">'.$lang['srv_recode_to_spr_new'].'</label>';
			}
			echo '</div>';
			if ((int)$recode_type == 1) {
				
                echo'<span id="divRecodeSprNew" class="floatRecodeSpremenljivka">';
                
				if ((int)$to_spr_id == 0) {
					echo '<div class="new-spr" style="text-align: right">';
					echo $lang['srv_recode_to_spr_add_spr'].'<br/>';
					echo '<div id="coding_spr_new" >';
					echo '<p>'.$lang['srv_recode_to_spr_name'].' <input type="text" id="rec_spremenljivka_naslov" name="spremenljivka_naslov" default="1" contenteditable="true" value="'.$sugestedName.'" style="width: 123px" />';
					#echo '<a href="#" onclick="recodeSpremenljivkaNew(); return false;">'.$lang['add'].'</a>';
					echo '<a href="#" onclick="recodeSpremenljivkaNew(); return false;""><span class="faicon add small" title="'.$lang['add'].'"></span></a>';
					echo '</p>';
					echo '</div>';
					echo '</div>';
				} else {
					echo '<strong>'.$rowSpremenljivka['variable'].' - '.skrajsaj(strip_tags($rowSpremenljivka['naslov']), 40).'</strong><br/><br/>';
					$sql1 = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='$to_spr_id' ORDER BY vrstni_red ASC");
					while ($row1 = mysqli_fetch_array($sql1)) {
						if ($rowSpremenljivka['tip'] == 1) {
							echo '<input type="radio" onclick="return false;" /><label> '.$row1['naslov'].'</label>';
							echo '<br/>';
						}
					}
					if (mysqli_num_rows($sql1) == 0) {
						echo '&nbsp;'.$lang['srv_novavrednost'].': '; $margin='0';
					} else { $margin = '23px';
					}
					echo '<input type="text" name="vrednost_new" value="" '.(mysqli_num_rows($sql1)>0?' placeholder="'.$lang['srv_novavrednost'].'" ':'').' style="margin-left:'.$margin.'; width: 80px" /> ';
					echo '<a href="#" onclick="recodeVrednostNew(); return false;"><span class="faicon add small icon-as_link" title="'.$lang['add'].'"></span></a>';
					echo '<br>Napredne spremembe:'.Help::display('srv_recode_advanced_edit');
				}
				echo '</span>';
			}
			echo'<br class="clr" />';
		}
		if ( $displayType == 1 ) {
			if (count(self::$variables)> 0) {
				if ((int)$recode_type == 0) {
					echo '<input type="hidden" name="edit_recode_mv" value="1" autocomplete="off"/>';
				} else {
					# rekodiramo v novo spremenljivko
					echo '<input type="hidden" name="edit_recode_nsmv" value="1" autocomplete="off"/>';
				}
				//echo '<table style="width:65% !important;">';
				echo '<table class="question_recode_table">';
				echo '<tr>';
				echo '<th class="halfWidth" colspan="2">';
				echo $lang['srv_recode_original'];
				echo '</th>';
				echo '<td rowspan="2" style="vertical-align:top;">&nbsp;<span class="sprites arrow_switch"></span>&nbsp;</td>';
				echo '<th class="halfWidth" rowspan="2" style="vertical-align:top;">';
				echo $lang['srv_recode_to'];
				echo '</th>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="halfWidth anl_ac gray">';
				echo $lang['srv_recode_to_label'];
				echo '</td>';
				echo '<td class="halfWidth anl_ac gray">';
				echo $lang['srv_recode_to_value'];
				echo '</td>';
				echo '</tr>';
					
				foreach (self::$variables AS $vKey => $variable) {
					echo '<tr>';
					echo '<td class="anl_ac">';
					echo $variable['naslov'];
					echo '</td>';
					echo '<td class="anl_ac">';
					echo '&nbsp;&nbsp;['.$variable['variable'].']';
					echo '</td>';
					# spacer
					echo '<td>&nbsp</td>';
					#
					echo '<td>';
					if ((int)$recode_type == 0) {
						# imamo klasično rekodiranje
						echo '<select name="MVFQ_'.$variable['variable'].'">';
						foreach (self::$options AS $oKey => $option) {
								
							if ($option['variable'] == null) {
								echo '<option disabled="disabled">'.$option['naslov'].'</option>';
							} else {
								$_selected = ((isset($recoded[$variable['variable'].'_0']) && $recoded[$variable['variable'].'_0']['value'] == $option['variable'])
										|| (!isset($recoded[$variable['variable'].'_0']) && $variable['variable'] == $option['variable'])) ? 'selected="selected"' : '';
								$_label = (( $variable['variable'] == $option['variable']))
									? '('.$lang['srv_recode_valid'].') '.$option['naslov'].' ['.$option['variable'].']'
									: $option['naslov'].' ['.$option['variable'].']';
								echo '<option value="'.$option['variable'].'" '.$_selected.'>&nbsp;&nbsp;'.$_label.'</option>';
							}
						}
						echo '</select>';
					} else {
						# imamo rekodiranje v  novo spremenljivko
						
						if (!isset($recodedFrom[$vKey])) {
								$recodedFrom[$vKey] = '-1';
						}
						echo '<select name="MVSNFQ_'.$vKey.'">';
						if (is_array($new_spr_vrednosti) && count($new_spr_vrednosti)>0) {
							foreach ($new_spr_vrednosti AS $oKey => $option) {
								if ($option['variable'] == null) {
									echo '<option disabled="disabled">'.$option['naslov'].'</option>';
								} else {
									
									if (is_numeric($oKey)) {
										$_selected = (isset($recodedFrom[$vKey]) && $recodedFrom[$vKey] == $oKey)
										? ' selected="selected"'
										: '';
										$_label = $option['naslov'].' ['.$option['variable'].']';
										$value = (int)$oKey;
									} else {
										$_selected = (isset($recodedFrom[$vKey]) && $recodedFrom[$vKey] == $option['variable'])
											? ' selected="selected"' 
											: '';
										$_label = $option['naslov'].' ['.$option['variable'].']';
										$value = (int)$option['type'] == 0 && (int)$option['id'] > 0 ? $option['id'] : $option['variable'];
									}
									echo '<option value="'.$value.'" '.$_selected.'>&nbsp;&nbsp;'.$_label.'</option>';
								}
							}
						}
						echo '</select>';
					}
					echo '</td>';
					echo '</tr>';
				}
				echo '</table>';
			}
		} else if (	$displayType == 2) {
			# number
			if ((int)$recode_type == 0) {
				echo '<input type="hidden" name="edit_recode_number" value="1" autocomplete="off"/>';
				echo '<ul id="recode_number_sort" class="recode_number_sort">';
				if (count($recoded)> 0) {
					foreach ($recoded AS $rKey => $recode) {

						echo '<li>';
						echo '<span class="faicon move_updown moveY"></span>';
						printf($lang['srv_recode_number_if'],self::$cacheSpremenljivka['variable']);
						echo '&nbsp;<select name="recode_number_operator[]" onChange="recode_operator_changed(this);">';
						foreach (self::$_operators AS $value => $operator) {
							$selected = ($value == $recode['operator']) ? ' selected="selected"' : '';
							echo '<option value="'.$value.'"'.$selected.'>'.$operator.'</option>';
						}
						echo '</select>';
						$rSearchInterval = explode(',',$recode['search']);
	
						echo '<span class="recode_int_first'.($recode['operator'] != 6 ? '' : ' hidden').'">';
						echo '&nbsp;<input type="text" name="recode_number_search[]" value="'.($rSearchInterval[0]).'" size="12" autocomplete="off"/>';
						echo '</span>';
						echo '<span class="recode_int_seccond'.($recode['operator'] != 6 ? ' hidden' : '').'">';
						echo '&nbsp;[<input type="text" name="recode_number_search1[]" value="'.($rSearchInterval[0]).'" size="5" autocomplete="off"/>,';
						echo '<input type="text" name="recode_number_search2[]" value="'.($rSearchInterval[1]).'" size="5" autocomplete="off"/>]';
						echo '</span>';
	
						echo '&nbsp;'.$lang['srv_recode_number_to'];
						$is_missing_value = false;
						echo '<select name="recode_number_type" onChange="recode_number_type_changed(this);">';
						foreach (self::$options AS $oKey => $option) {
							if ($oKey == '_0_' || $oKey == '_1_' || $oKey == '_2_') {
								# privzeto izberemo prvi odgovor - veljavni, nato spodaj popravimo
								$_value = ($oKey == '_0_') ? '_' : null;
								$_selected = ($oKey == '_0_' ? 'selected="selected"' : 'disabled="disabled"');
								$_label = $option['naslov'];
							} else {
								$_value = $option['variable'];
								if ($oKey == $recode['value']) {
									$is_missing_value = true;
									$_selected = 'selected="selected"';
								} else {
									$_selected = '';
								}
								$_label = '&nbsp;&nbsp;'.$option['naslov'].' ['.$option['variable'].']';
							}
							echo '<option value="'.$_value.'" '.$_selected.'>'.$_label.'</option>';
						}
						echo '</select>';
	
						echo '&nbsp;<input type="text" name="recode_number_value[]" value="'.$recode['value'].'" size="5" '.($is_missing_value ? ' class="hidden"' : '').' autocomplete="off"/>';
						echo '&nbsp;<span class="floatRight spaceRight faicon delete_circle icon-orange_link" onclick="recode_delete_numeric(\''.self::$spremenljivka.'\',this);"></span>';
						echo '</li>';
					}
				}
				echo '</ul>';
				echo '<script>$(\'#recode_number_sort\').sortable({ items: \'li\', axis: \'y\', scroll: \'false\', handle: \'span.move_updown\', forcePlaceholderSize: \'true\', placeholder: "ui-recode-placeholder"});</script>';
				echo '<span onclick="recode_add_numeric(\''.self::$spremenljivka.'\');" class="pointer"><span class="faicon add small icon-as_link" title="'.$lang['srv_recode_add_number'].'"></span> '.$lang['srv_recode_add_number'].'</span>';
			} else {
				
				# number
				# recode to new spr
				echo '<input type="hidden" name="edit_recode_number" value="1" autocomplete="off"/>';
				echo '<ul id="recode_number_sort" class="recode_number_sort">';
				if (count($recoded)> 0) {
					foreach ($recoded AS $rKey => $recode) {

						echo '<li>';
						echo '<span class="faicon move_updown moveY"></span>';
						printf($lang['srv_recode_number_if'],self::$cacheSpremenljivka['variable']);
						echo '&nbsp;<select name="recode_number_operator[]" onChange="recode_operator_changed(this);">';
						foreach (self::$_operators AS $value => $operator) {
							$selected = ($value == $recode['operator']) ? ' selected="selected"' : '';
							echo '<option value="'.$value.'"'.$selected.'>'.$operator.'</option>';
						}
						echo '</select>';
						$rSearchInterval = explode(',',$recode['search']);
				
						echo '<span class="recode_int_first'.($recode['operator'] != 6 ? '' : ' hidden').'">';
						echo '&nbsp;<input type="text" name="recode_number_search[]" value="'.($rSearchInterval[0]).'" size="12" autocomplete="off"/>';
						echo '</span>';
						echo '<span class="recode_int_seccond'.($recode['operator'] != 6 ? ' hidden' : '').'">';
						echo '&nbsp;[<input type="text" name="recode_number_search1[]" value="'.($rSearchInterval[0]).'" size="5" autocomplete="off"/>,';
						echo '<input type="text" name="recode_number_search2[]" value="'.($rSearchInterval[1]).'" size="5" autocomplete="off"/>]';
						echo '</span>';
				
						echo '&nbsp;'.$lang['srv_recode_number_to'];
						if (!isset($recodedFrom[$rKey])) {
							$recodedFrom[$rKey] = '-1';
						}
						# imamo rekodiranje v  novo spremenljivko
						echo '<select name="recode_number_vrednost[]">';
						if (is_array($new_spr_vrednosti) && count($new_spr_vrednosti)>0) {
							foreach ($new_spr_vrednosti AS $oKey => $option) {
								if ($option['variable'] == null) {
									echo '<option disabled="disabled">'.$option['naslov'].'</option>';
								} else {
									if ($option['type'] == 0) {
										$value = $oKey;
										$_selected = ($recode['vred_id'] == $oKey) ? ' selected="selected"' : '';
									} else {
										$value = $option['variable'];
										$_selected = ($recode['vred_id'] == $option['variable']) ? ' selected="selected"' : '';
									} 
										
									$_label = $option['naslov'].' ['.$option['variable'].']';
									echo '<option value="'.$value.'"'.$_selected.'>&nbsp;&nbsp;'.$_label.'</option>';
								}
								
						
						
							#	$_label = $option['naslov'].' ['.$option['variable'].']';
							#	echo '<option value="'.$option['id'].'" '.$_selected.'>'.$_label.'</option>';
							}
						}
						echo '</select>';
						echo '&nbsp;<span class="floatRight spaceRight faicon delete_circle icon-orange_link" onclick="recode_delete_numeric(\''.self::$spremenljivka.'\',this);"></span>';
						echo '</li>';
					}
				}
				echo '</ul>';
				echo '<script>$(\'#recode_number_sort\').sortable({ items: \'li\', axis: \'y\', scroll: \'false\', handle: \'span.move_updown\', forcePlaceholderSize: \'true\', placeholder: "ui-recode-placeholder"});</script>';
				echo '<span onclick="recode_add_numeric(\''.self::$spremenljivka.'\');" class="pointer"><span class="faicon add small icon-as_link" title="'.$lang['srv_recode_add_number'].'"></span> '.$lang['srv_recode_add_number'].'</span>';			}
		} else {
			echo '<br>'.$lang['srv_recoding_not_set_jet'].'('.$tip.')<br>';
		}
	}
	
	function SetRecodeNewVrednost() {
		# pobrišemo morebtne vrednosti v srv_recode (če je bilo predhodno nastavljeno klasično rekodiranje)
		$sqlD = sisplet_query("DELETE FROM srv_recode WHERE ank_id = '".self::$anketa."' AND spr_id = '".$_REQUEST['spr_id']."'");
		if (!$sqlD) echo mysqli_error($GLOBALS['connect_db']);
		sisplet_query("COMMIT");
		
		$newSpr = $_REQUEST['recodeToSpr'];
		$_for_replace = "";
		if ((int)$newSpr > 0)
		foreach ($_REQUEST as $key => $newVrednost) {
			if (substr($key, 0, 7) == 'MVSNFQ_' && trim($newVrednost) != '') {
				# id stare vrednosti
				$oldVrednost = substr($key, 7);
				if ($_for_replace != "") {
					$_for_replace .= ",";
				}
				$_for_replace .= " ('".self::$anketa."','".self::$spremenljivka."','$oldVrednost','$newSpr','$newVrednost') ";
			}
			
		}
		# najprej pobrišemo vse obstoječe vrednosti
		$sql = sisplet_query("DELETE FROM srv_recode_vrednost WHERE ank_id = '".self::$anketa."' AND spr1 = '".self::$spremenljivka."'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		sisplet_query("COMMIT");
		
		# če moramo uporabiti zamenjave:
		if ($_for_replace != "") {
			$insertString = 'INSERT INTO srv_recode_vrednost (ank_id, spr1, vre1, spr2, vre2) VALUES '.$_for_replace;
			$sql1 = sisplet_query($insertString);
		}
		sisplet_query("COMMIT");
			
	}
	
}