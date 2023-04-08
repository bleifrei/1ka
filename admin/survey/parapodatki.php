<?php

include_once('../../function.php');
define('delimiter', ';');

header('Content-type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ankete.csv"');
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private',false);
header('Content-Transfer-Encoding:­ binary');

echo "\xEF\xBB\xBF";


if ($_GET['a'] == 'nejc' || $_GET['a'] == 'parapodatki') {
	
	echo 'ID SURVEY'.delimiter;
	echo 'ID USER'.delimiter;
	echo 'RECNUM'.delimiter;
	echo 'TIMESTAMP'.delimiter;
	echo 'EVENT'.delimiter;
	echo 'PAGE'.delimiter;
	echo 'QUESTION'.delimiter;
	echo 'ITEM'.delimiter;
	echo "\n";
	
	if (isset($_GET['anketa'])) $where = " AND p.ank_id = '$_GET[anketa]'";
	else $where = '';
	$sql = sisplet_query("SELECT p.*, u.recnum  FROM srv_parapodatki p, srv_user u WHERE u.id=p.usr_id $where ORDER BY p.id ASC");
	while ($row = mysqli_fetch_array($sql)) {
		echo $row['ank_id'].delimiter;
		echo $row['usr_id'].delimiter;
		echo $row['recnum'].delimiter;
		echo $row['datetime'].delimiter;
		echo $row['what'].delimiter;
		echo $row['gru_id'].delimiter;
		echo $row['spr_id'].delimiter;
		echo $row['item'].delimiter;
		
		echo "\n";
	}
	die();
}


//sisplet_query("USE www1kasi_2011");

if (isset($_GET['anketa'])) {
	// ID ankete
	if (is_numeric($_GET['anketa'])) {
		$ids = array((int)$_GET['anketa']);

	// ankete poberemo iz baze
	} else {
		
		if ($_GET['anketa'] == 'users') {
			
			$ids = array();
			$sql = sisplet_query("SELECT DISTINCT ank_id FROM srv_user WHERE last_status IN ('0', '1', '2')");
			while ($row = mysqli_fetch_array($sql)) {
				$ids[] = $row['ank_id'];
			}	
		}
		elseif ($_GET['anketa'] == 'huge') {
			
			$ids = array(12102,12248,12245,12212,12177,12152,11842,11795,11707,11653,10986,10968,10951,10879,10863,10493,10199,10022,12251,12188,12159,12123,12039,11933,11766,11489,11405,11386,11375,11124,10981,10881,10836,10822,10463,10457,10414,10404,10372,10280,9982,9903,12362,12354,12340,12307,12285,12283,12255,12253,12249,12223,12202,12199,12197,12189,12187,12185,12183,12176,12153,12139,12132,12113,12112,12109,12108,12096,12093,12075,12056,12053,12048,12038,12034,12033,12026,12022,12020,12018,11965,11953,11934,11918,11907,11892,11882,11881,11876,11841,11831,11825,11824,11815,11792,11784,11783,11778,11762,11754,11749,11748,11712,11697,11691,11685,11683,11680,11676,11667,11661,11652,11650,11649,11635,11632,11613,11584,11581,11580,11575,11559,11551,11536,11531,11528,11522,11518,11516,11508,11490,11466,11464,11463,11439,11438,11431,11422,11420,11419,11390,11379,11378,11377,11374,11365,11347,11346,11345,11339,11318,11317,11249,11234,11232,11225,11228,11174,11151,11153,11135,11130,11125,11122,11120,11111,11075,11072,11070,11068,11067,11050,11048,11027,11013,11012,11001,10995,10992,10990,10988,10983,10975,10952,10950,10937,10917,10911,10896,10893,10890,10880,10875,10869,10844,10843,10831,10825,10824,10823,10818,10809,10807,10806,10799,10797,10792,10776,10772,10763,10762,10750,10743,10739,10736,10718,10717,10714,10670,10668,10665,10660,10632,10630,10627,10615,10610,10601,10597,10585,10583,10579,10577,10573,10563,10539,10530,10520,10515,10494,10475,10456,10446,10437,10434,10431,10426,10412,10398,10391,10381,10375,10370,10361,10360,10359,10349,10338,10337,10333,10327,10319,10307,10304,10294,10291,10289,10279,10278,10277,10241,10233,10230,10227,10223,10203,10190,10152,10146,10130,10124,10113,10108,10106,10105,10104,10103,10100,10092,10087,10021,9994,9985,9984,9974,9971,9959,9966,9964,9956,9954,9951,9947,9928,9922,9913,9912,9910,9898,9891,9889,9880,9874,1585,2364,2511,2671,2696,2830,2836,3012,3531,4281,5013,5201,5710,5711,5771,7202,7333,7349,8447,8457,8651,8792,9286,9331,9399,9542);
			
		}
		// preberemo VSE ankete
		else if($_GET['anketa'] == 'all'){
		
			$ids = array();
			$sql = sisplet_query("SELECT id FROM srv_anketa WHERE id>'0'");
			while ($row = mysqli_fetch_array($sql)) {
				$ids[] = $row['id'];
			}
		}		
	}
} else {
	$ids = array(8566, 9541, 6156);
}

// seznam anket
if ($_GET['a'] == 'ankete') {

	echo 'ID SURVEY'.delimiter;
	echo 'ID USER'.delimiter;
	echo 'FIRST RESP'.delimiter;
	echo 'LAST RESP'.delimiter;
	echo 'FP CONTENT'.delimiter;
	echo 'NO QUESTIONS'.delimiter;
	echo 'NO ITEMS'.delimiter;
	echo 'NO PAGES'.delimiter;
	echo 'EMAIL INVT'.delimiter;
	echo 'EMAIL INVT OTHERS'.delimiter;
	echo 'EMAIL INVITED';

	echo "\n";

	foreach ($ids AS $id) {
		
		$sql = sisplet_query("SELECT a.*, sl.a_first, sl.a_last FROM srv_anketa a, srv_survey_list sl WHERE a.id = '$id' AND sl.id=a.id");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			
			$row = mysqli_fetch_array($sql);
						
			echo $row['id'].delimiter;
			echo $row['insert_uid'].delimiter;
			echo $row['a_first'].delimiter;
			echo $row['a_last'].delimiter;
			echo str_replace("\n", '', str_replace(delimiter, '', $row['introduction']) ).delimiter;
			
			$sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_spremenljivka s, srv_grupa g WHERE s.tip!='5' AND s.gru_id=g.id AND g.ank_id='$id'");
			if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$row1 = mysqli_fetch_array($sql1);
			
			echo $row1['count'].delimiter;
			
			$sql1 = sisplet_query("SELECT s.id, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.tip!='5' AND s.gru_id=g.id AND g.ank_id='$id'");
			if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$items = 0;
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				switch ($row1['tip']) {
					case 2:		// checkbox
					case 21:	// besedilo
					case 6:		// multigrid
					case 7:		// number
					case 17:	// ranking
					case 18:	// vsota
					case 16:	// multicheck
					case 19:	// multitext
					case 20:	// multinumber
						//$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_vrednost WHERE spr_id = '$row1[id]'");
						//$row2 = mysqli_fetch_array($sql2);
						$items += srv_vrednost($row1['id']);
					break;
					
					default: 
						$items += 1;
					break;
				}
			}
			
			echo $items.delimiter;
			
			$sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_grupa g WHERE g.ank_id='$id'");
			if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$row1 = mysqli_fetch_array($sql1);
			
			echo $row1['count'].delimiter;
			
			echo $row['email'].delimiter;
			
			echo $row['usercode_skip'].delimiter;
			
			$sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_user u WHERE u.ank_id='$id' AND pass IS NOT NULL");
			if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$row1 = mysqli_fetch_array($sql1);
			
			echo $row1['count'];
			
			echo "\n";
			
		}
		
	}

// seznam anketirancev
} elseif ($_GET['a'] == 'anketiranci') {
	
	echo 'ID SURVEY'.delimiter;
	echo 'ID RESP'.delimiter;
	echo 'RESP STATUS'.delimiter;
	echo 'RESP LURKER'.delimiter;
	echo 'RESP IP'.delimiter;
	echo 'RESP BROWSER'.delimiter;
	echo 'RESP REFERAL'.delimiter;
	echo 'RESP ITEMS'.delimiter;
	echo 'RESP RESPONSE'.delimiter;
	echo 'TIME FIRST'.delimiter;
	echo 'TIME LAST'.delimiter;
	echo 'TIME INTRO'.delimiter;
	
	$groups = 0;
	foreach ($ids AS $id) {
		$sql1 = sisplet_query("SELECT COUNT(*) FROM srv_grupa WHERE ank_id='$id'");
		$row1 = mysqli_fetch_array($sql1);
		if ($row1[0] > $groups) $groups = $row1[0];
	}
	for ($i=1; $i<=$groups; $i++)
		echo 'TIME PAGE '.$i.delimiter;
	
	echo "\n";
	
	foreach ($ids AS $id) {
		
		$sql = sisplet_query("SELECT a.*, u.time_insert, u.time_edit, u.id AS uid, u.last_status, u.lurker, u.ip, u.useragent, u.referer FROM srv_anketa a, srv_user u WHERE a.id = '$id' AND u.ank_id=a.id");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			
			$sql1 = sisplet_query("SELECT s.id, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.tip!='5' AND s.gru_id=g.id AND g.ank_id='$id'");
			if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$items = 0;
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				switch ($row1['tip']) {
					case 2:		// checkbox
					case 21:	// besedilo
					case 6:		// multigrid
					case 7:		// number
					case 17:	// ranking
					case 18:	// vsota
					case 16:	// multicheck
					case 19:	// multitext
					case 20:	// multinumber
						//$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_vrednost WHERE spr_id = '$row1[id]'");
						//$row2 = mysqli_fetch_array($sql2);
						$items += srv_vrednost($row1['id']);
					break;
					
					default: 
						$items += 1;
					break;
				}
			}
			
			while ($row = mysqli_fetch_array($sql)) {
				
				switch($row['db_table']){

                    // Arhivska 1
                    case '0':
                        $db_table = '_archive1';
                        break;
        
                    // Arhivska 2
                    case '2':
                        $db_table = '_archive2';
                        break;
                    
                    // Aktivna anketa
                    case '1':
                    default:
                        $db_table = '_active';
                        break;
                }
							
				echo $row['id'].delimiter;
				echo $row['uid'].delimiter;
				echo $row['last_status'].delimiter;
				echo $row['lurker'].delimiter;
				echo $row['ip'].delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['useragent']) ).delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['referer']) ).delimiter;
				
				// stevilo itmov userja - kaj je videl
				$sql1 = sisplet_query("SELECT s.id, s.tip FROM srv_spremenljivka s, srv_grupa g, srv_user_grupa{$db_table} ug WHERE s.tip!='5' AND s.gru_id=g.id AND g.ank_id='$id' AND ug.gru_id=g.id AND ug.usr_id='$row[uid]'");
				if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
				$usertrueitems = 0;
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					$sql3 = sisplet_query("SELECT spr_id FROM srv_data_vrednost{$db_table} WHERE spr_id='$row1[id]' AND usr_id='$row[uid]' AND (vre_id='-2' OR vre_id='-4')");
					if (mysqli_num_rows($sql3) == 0) {
						
						switch ($row1['tip']) {
							case 2:		// checkbox
							case 21:	// besedilo
							case 6:		// multigrid
							case 7:		// number
							case 17:	// ranking
							case 18:	// vsota
							case 16:	// multicheck
							case 19:	// multitext
							case 20:	// multinumber
								//$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_vrednost WHERE spr_id = '$row1[id]'");
								//$row2 = mysqli_fetch_array($sql2);
								$usertrueitems += srv_vrednost($row1['id']);
							break;
							
							default: 
								$usertrueitems += 1;
							break;
						}
					}
				}
				
				// stevilo itmov na katere je odgovoril 
				$sql1 = sisplet_query("SELECT s.id, s.tip FROM srv_spremenljivka s, srv_grupa g, srv_user_grupa{$db_table} ug WHERE s.tip!='5' AND s.gru_id=g.id AND g.ank_id='$id' AND ug.gru_id=g.id AND ug.usr_id='$row[uid]'");
				//$sql1 = sisplet_query("SELECT s.id, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.tip!='5' AND s.gru_id=g.id AND g.ank_id='$id'");
				if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
				$useritems_resp = 0;
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					$sql3 = sisplet_query("SELECT spr_id FROM srv_data_vrednost{$db_table} WHERE spr_id='$row1[id]' AND usr_id='$row[uid]' AND (vre_id='-2' OR vre_id='-4')");
					if (mysqli_num_rows($sql3) == 0) {
							
						switch ($row1['tip']) {
							case 1:		// radio
							case 3:		// dropdown
								$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_data_vrednost{$db_table} WHERE spr_id='$row1[id]' AND vre_id > '0' AND usr_id='$row[uid]'");
								$row2 = mysqli_fetch_array($sql2);
								if ($row2[0] > 0) {
									$useritems_resp += 1;
								}
							break;
							case 2:		// checkbox
								$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_data_vrednost{$db_table} WHERE spr_id='$row1[id]' AND vre_id > '0' AND usr_id='$row[uid]'");
								$row2 = mysqli_fetch_array($sql2);
								if ($row2[0] > 0) {
									//$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_vrednost WHERE spr_id = '$row1[id]'");
									//$row2 = mysqli_fetch_array($sql2);
									$useritems_resp += srv_vrednost($row1['id']);
								}
							break;
							case 6:	// grid
								$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]'");
								while ($row2 = mysqli_fetch_array($sql2)) {
									$sql3 = sisplet_query("SELECT COUNT(*) FROM srv_data_grid{$db_table} WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$row[uid]'");
									$row3 = mysqli_fetch_array($sql3);
									if ($row3[0] > 0) {
										$useritems_resp += 1;
									}
								}
							break;
							case 16: // checkgrid
								$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]'");
								while ($row2 = mysqli_fetch_array($sql2)) {
									$sql3 = sisplet_query("SELECT COUNT(*) FROM srv_data_checkgrid{$db_table} WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$row[uid]'");
									$row3 = mysqli_fetch_array($sql3);
									if ($row3[0] > 0) {
										$useritems_resp += 1;
									}
								}
							break;
							case 19: // textgrid
							case 20: // numbergrid
								$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]'");
								while ($row2 = mysqli_fetch_array($sql2)) {
									$sql3 = sisplet_query("SELECT COUNT(*) FROM srv_data_textgrid{$db_table} WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$row[uid]'");
									$row3 = mysqli_fetch_array($sql3);
									if ($row3[0] > 0) {
										$useritems_resp += 1;
									}
								}
							break;
							
							case 21:	// besedilo
							case 7:		// number
							case 18:	// vsota
								$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]'");
								while ($row2 = mysqli_fetch_array($sql2)) {
									$sql3 = sisplet_query("SELECT COUNT(*) FROM srv_data_text{$db_table} WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$row[uid]'");
									$row3 = mysqli_fetch_array($sql3);
									if ($row3[0] > 0) {
										$useritems_resp += 1;
									}
								}
							break;
							
							case 17:	// ranking
								$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]'");
								while ($row2 = mysqli_fetch_array($sql2)) {
									$sql3 = sisplet_query("SELECT COUNT(*) FROM srv_data_rating WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$row[uid]'");
									$row3 = mysqli_fetch_array($sql3);
									if ($row3[0] > 0) {
										$useritems_resp += 1;
									}
								}
							break;
						}
						
					}
				}
				
				echo $usertrueitems.delimiter;
				echo $useritems_resp.delimiter;
				
				echo $row['time_insert'].delimiter;
				echo $row['time_edit'].delimiter;
				
				// uvod
				$sql1 = sisplet_query("SELECT ug.* FROM srv_user_grupa{$db_table} ug WHERE ug.usr_id = '$row[uid]' AND ug.gru_id='0'");
				if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
				$row1 = mysqli_fetch_array($sql1);
				echo $row1['time_edit'].delimiter;
				
				// ostale strani
				$sql1 = sisplet_query("SELECT ug.* FROM srv_user_grupa{$db_table} ug, srv_grupa g WHERE ug.usr_id = '$row[uid]' AND ug.gru_id=g.id AND g.ank_id='$id' ORDER BY g.vrstni_red ASC");
				if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
				while ($row1 = mysqli_fetch_array($sql1)) {
					echo $row1['time_edit'].delimiter;
				}
				
				echo "\n";
				
			}	
			
		}
		
		
	}
	
} elseif ($_GET['a'] == 'vprasanja') {
	
	echo 'ID SURVEY'.delimiter;
	echo 'ID QUESTION'.delimiter;
	echo 'ID PAGE'.delimiter;
	echo 'QUESTION NUMBER'.delimiter;
	
	echo 'naslov'.delimiter;
	echo 'info'.delimiter;
	echo 'variable'.delimiter;
	echo 'variable_custom'.delimiter;
	echo 'label'.delimiter;
	echo 'tip'.delimiter;
	echo 'vrstni_red'.delimiter;
	echo 'random'.delimiter;
	echo 'size'.delimiter;
	echo 'undecided'.delimiter;
	echo 'rejected'.delimiter;
	echo 'inappropriate'.delimiter;
	echo 'stat'.delimiter;
	echo 'orientation'.delimiter;
	echo 'checkboxhide'.delimiter;
	echo 'reminder'.delimiter;
	echo 'visible'.delimiter;
	echo 'textfield'.delimiter;
	echo 'textfield_label'.delimiter;
	echo 'cela'.delimiter;
	echo 'decimalna'.delimiter;
	echo 'enota'.delimiter;
	echo 'timer'.delimiter;
	echo 'sistem'.delimiter;
	echo 'folder'.delimiter;
	echo 'params'.delimiter;
	echo 'antonucci'.delimiter;
	echo 'design'.delimiter;
	echo 'podpora'.delimiter;
	echo 'grids'.delimiter;
	echo 'grids_edit'.delimiter;
	echo 'grid_subtitle1'.delimiter;
	echo 'grid_subtitle2'.delimiter;
	echo 'ranking_k'.delimiter;
	echo 'vsota'.delimiter;
	echo 'vsota_limit'.delimiter;
	echo 'vsota_min'.delimiter;
	echo 'skala'.delimiter;
	echo 'vsota_reminder'.delimiter;
	echo 'vsota_limittype'.delimiter;
	echo 'vsota_show'.delimiter;
	echo 'thread'.delimiter;
	echo 'text_kosov'.delimiter;
	echo 'text_orientation'.delimiter;
	echo 'note'.delimiter;
	echo 'upload'.delimiter;
	echo 'dostop'.delimiter;
	echo 'inline_edit'.delimiter;
	echo 'onchange_submit'.delimiter;
	echo 'hidden_default'.delimiter;
	echo 'naslov_graf'.delimiter;
	echo 'edit_graf'.delimiter;
	echo 'wide_graf'.delimiter;
	echo 'coding'.delimiter;
	echo 'dynamic_mg'.delimiter;
	echo 'QUESTION IF'.delimiter;
	
	echo "\n";

	foreach ($ids AS $id) {
		
		$sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$id' ORDER BY g.vrstni_red, s.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			
			$i = 0;
			
			while ($row = mysqli_fetch_array($sql)) {
				
				$i++;
				
				echo $id.delimiter;
				echo $row['id'].delimiter;
				echo $row['gru_id'].delimiter;
				echo $i.delimiter;
                
                // Posebej za Gregorja lahko izvozimo prevode - opcijski parameter "lang_id"
                if(isset($_GET['lang_id'])){

                    $lang_id = $_GET['lang_id'];
                    $sqlL = sisplet_query("SELECT naslov, info FROM srv_language_spremenljivka WHERE spr_id='".$row['id']."' AND lang_id='".$lang_id."'");
                    
                    if(mysqli_num_rows($sqlL) > 0){
                        $rowL = mysqli_fetch_array($sqlL);

                        $row['naslov'] = $rowL['naslov'];
                        $row['info'] = $rowL['info'];
                    }
                }

				echo str_replace("\n", '', str_replace(delimiter, '', $row['naslov']) ).delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['info']) ).delimiter;
				echo $row['variable'].delimiter;
				echo $row['variable_custom'].delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['label']) ).delimiter;
				echo $row['tip'].delimiter;
				echo $row['vrstni_red'].delimiter;
				echo $row['random'].delimiter;
				echo $row['size'].delimiter;
				echo $row['undecided'].delimiter;
				echo $row['rejected'].delimiter;
				echo $row['inappropriate'].delimiter;
				echo $row['stat'].delimiter;
				echo $row['orientation'].delimiter;
				echo $row['checkboxhide'].delimiter;
				echo $row['reminder'].delimiter;
				echo $row['visible'].delimiter;
				echo $row['textfield'].delimiter;
				echo $row['textfield_label'].delimiter;
				echo $row['cela'].delimiter;
				echo $row['decimalna'].delimiter;
				echo $row['enota'].delimiter;
				echo $row['timer'].delimiter;
				echo $row['sistem'].delimiter;
				echo $row['folder'].delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['params']) ).delimiter;
				echo $row['antonucci'].delimiter;
				echo $row['design'].delimiter;
				echo $row['podpora'].delimiter;
				echo $row['grids'].delimiter;
				echo $row['grids_edit'].delimiter;
				echo $row['grid_subtitle1'].delimiter;
				echo $row['grid_subtitle2'].delimiter;
				echo $row['ranking_k'].delimiter;
				echo $row['vsota'].delimiter;
				echo $row['vsota_limit'].delimiter;
				echo $row['vsota_min'].delimiter;
				echo $row['skala'].delimiter;
				echo $row['vsota_reminder'].delimiter;
				echo $row['vsota_limittype'].delimiter;
				echo $row['vsota_show'].delimiter;
				echo $row['thread'].delimiter;
				echo $row['text_kosov'].delimiter;
				echo $row['text_orientation'].delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['note']) ).delimiter;
				echo $row['upload'].delimiter;
				echo $row['dostop'].delimiter;
				echo $row['inline_edit'].delimiter;
				echo $row['onchange_submit'].delimiter;
				echo $row['hidden_default'].delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['naslov_graf']) ).delimiter;
				echo $row['edit_graf'].delimiter;
				echo $row['wide_graf'].delimiter;
				echo $row['coding'].delimiter;
				echo $row['dynamic_mg'].delimiter;
				
				$sql1 = sisplet_query("SELECT f.* FROM srv_branching b, srv_if f WHERE b.element_spr = '$row[id]' AND b.parent=f.id");
				$row1 = mysqli_fetch_array($sql1);
				echo parentIf($id, $row1['id']).delimiter;
	
				echo "\n";
			}
			
		}
		
		
	}
	
	
	
} elseif ($_GET['a'] == 'items') {
	
	
	
	echo 'ID SURVEY'.delimiter;
	echo 'ID QUESTION'.delimiter;
	echo 'ID ITEM'.delimiter;
	
	echo 'naslov'.delimiter;
	echo 'naslov2'.delimiter;
	echo 'variable'.delimiter;
	echo 'variable_custom'.delimiter;
	echo 'vrstni_red'.delimiter;
	echo 'random'.delimiter;
	echo 'other'.delimiter;
	echo 'if_id'.delimiter;
	echo 'size'.delimiter;
	echo 'naslov_graf'.delimiter;
	
	echo 'grid_vrstni_red'.delimiter;
	echo 'grid_variable'.delimiter;
	echo 'grid_other'.delimiter;
	echo 'grid_naslov'.delimiter;

	
	echo "\n";

	foreach ($ids AS $id) {
		
		$sql = sisplet_query("SELECT v.* FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='$id' ORDER BY g.vrstni_red, s.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
                    
            // Posebej za Gregorja lahko izvozimo prevode - opcijski parameter "lang_id"
            if(isset($_GET['lang_id'])){

                $lang_id = $_GET['lang_id'];
                $sqlL = sisplet_query("SELECT vre_id, naslov, naslov2 FROM srv_language_vrednost WHERE ank_id='".$id."' AND lang_id='".$lang_id."'");

                $translations = array();
                
                while($rowL = mysqli_fetch_array($sqlL)){
                    $translations[$rowL['vre_id']]['naslov'] = $rowL['naslov'];
                    $translations[$rowL['vre_id']]['naslov2'] = $rowL['naslov2'];
                }
            }

			while ($row = mysqli_fetch_array($sql)) {
								
				echo $id.delimiter;
				echo $row['spr_id'].delimiter;
				echo $row['id'].delimiter;

                // Posebej za Gregorja lahko izvozimo prevode - opcijski parameter "lang_id"
                if(isset($_GET['lang_id']) && isset($translations[$row['id']])){
                    $row['naslov'] = $translations[$row['id']]['naslov'];
                    $row['naslov2'] = $translations[$row['id']]['naslov2'];
                }

				echo str_replace("\n", '', str_replace(delimiter, '', $row['naslov']) ).delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['naslov2']) ).delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['variable']) ).delimiter;
				echo $row['variable_custom'].delimiter;
				echo $row['vrstni_red'].delimiter;
				echo $row['random'].delimiter;
				echo $row['other'].delimiter;
				echo $row['if_id'].delimiter;
				echo $row['size'].delimiter;
				echo str_replace("\n", '', str_replace(delimiter, '', $row['naslov_graf']) ).delimiter;
				
				$sql1 = sisplet_query("SELECT naslov, variable, vrstni_red, other FROM srv_grid WHERE spr_id = '$row[spr_id]' ORDER BY vrstni_red ASC");
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					echo str_replace("\n", '', str_replace(delimiter, '', $row1['vrstni_red']) ).delimiter;
					echo str_replace("\n", '', str_replace(delimiter, '', $row1['variable']) ).delimiter;
					echo str_replace("\n", '', str_replace(delimiter, '', $row1['other']) ).delimiter;
					echo str_replace("\n", '', str_replace(delimiter, '', $row1['naslov']) ).delimiter;				
				}
				
				echo "\n";
			}			
		}		
	}	
}

$srv_vrednost = null;
function srv_vrednost ($id) {
	global $srv_vrednost;
	
	if ( $srv_vrednost != null && array_key_exists($id, $srv_vrednost) )
		return $srv_vrednost[$id];
	
	$sql2 = sisplet_query("SELECT COUNT(*) FROM srv_vrednost WHERE spr_id = '$id'");
	$row2 = mysqli_fetch_array($sql2);
	$srv_vrednost[$id] = $row2[0];
	return $row2[0];
	
}

function parentIf($anketa, $element) {
	
	$sql = sisplet_query("SELECT tip FROM srv_if WHERE id = '$element'");
	$row = mysqli_fetch_array($sql);
	
	if ($row['tip'] == 0) return $element;
	
	$sql1 = sisplet_query("SELECT parent FROM srv_branching WHERE ank_id='$anketa' AND element_if = '$element'");
	$row1 = mysqli_fetch_array($sql1);
	
	return parentIf($anketa, $row1['parent']);
}

?>
