<?php

/**
* Class, ki se uporablja za povezovanje podatkov iz razlicnih anket
* zaenkrat je opcija, da se povezuje podatke na podlagi identifikatorja (npr. emaila)
* 
*/

class SurveyConnect {
	
	var $anketa; // trenutna anketa
	
	var $db_table;
	
	function __construct () {
		
		// polovimo anketa ID
		if (isset ($_GET['anketa']))
			$this->anketa = $_GET['anketa'];
		elseif (isset ($_POST['anketa'])) 
			$this->anketa = $_POST['anketa'];
		elseif ($anketa != 0) 
			$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
	}
	
	function ajax () {
		
		if ( $_GET['a'] == 'display' ) {
			$this->ajax_display();
		}
		
	}
	
	function ajax_display () {
		global $global_user_id;
		global $site_url;
		global $lang;
		
		$usr_id = $_POST['usr_id'];
		
		$dostop = array();
		$sqld = sisplet_query("SELECT ank_id FROM srv_dostop WHERE uid = '$global_user_id' AND dostop LIKE '%data%' ORDER BY ank_id DESC");
		while ($rowd = mysqli_fetch_assoc($sqld)) {
			$dostop[] = $rowd['ank_id'];
		}
		unset($sqld, $rowd);
		
		$anydata = false;
		
		// izberemo vse sistemske spremenljivke
		$sql = sisplet_query("SELECT s.id, s.tip, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.tip IN (1,2,3,21) AND s.sistem='1' AND s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red, s.vrstni_red");
		while ($row = mysqli_fetch_assoc($sql)) {
			
			$hasdata = false;
			
			// besedilo
			if ( $row['tip'] == 21 ) {
				
				// poiscemo identifikator za trenutno spremenljivko (glede na userja)
				$sql1 = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='$row[id]' AND usr_id='$usr_id'");
				while ($row1 = mysqli_fetch_assoc($sql1)) {
					
					$unikat = $row1['text'];
					
					if ($unikat != '') {
						$echo = '<p><b>('.$row['variable'].') '.strip_tags($row['naslov']).'</b><br>';
						$echo .= '('.$unikat.')<br>';
						$echo .= '';
						
						foreach ($dostop AS $anketa) {
							
							$subsql = sisplet_query("SELECT s.id, a.naslov FROM srv_spremenljivka s, srv_grupa g, srv_anketa a WHERE s.tip IN (21) AND s.sistem='1' AND s.variable='$row[variable]' AND s.gru_id=g.id AND g.ank_id='$anketa' AND g.ank_id=a.id AND g.ank_id != '$this->anketa' AND a.active>='0'");
							if (!$subsql) echo mysqli_error($GLOBALS['connect_db']);
							while ($subrow = mysqli_fetch_assoc($subsql)) {
								
								$subsql1 = sisplet_query("SELECT t.* FROM srv_data_text".$this->db_table." t, srv_user u WHERE t.spr_id='$subrow[id]' AND t.text = '$unikat' AND u.id=t.usr_id AND u.deleted='0'");
								while ($subrow1 = mysqli_fetch_assoc($subsql1)) {
									
									$echo .= '<a href="'.$site_url.'admin/survey/index.php?anketa='.$anketa.'&a=data&m=quick_edit&usr_id='.$subrow1['usr_id'].'&quick_view=1">'.strip_tags($subrow['naslov']).'</a><br>';
									$hasdata = true;
								}						
							}						
						}
						
						$echo .= '</p>';
					}
				}
				
			// radio, checkbox, roleta
			} elseif ( $row['tip'] == 1 || $row['tip'] == 2 || $row['tip'] == 3 ) {
				
				// poiscemo identifikator za trenutno spremenljivko (glede na userja)
				$sql1 = sisplet_query("SELECT v.variable FROM srv_data_vrednost".$this->db_table." d, srv_vrednost v WHERE d.spr_id='$row[id]' AND d.usr_id='$usr_id' AND d.vre_id=v.id");
				while ($row1 = mysqli_fetch_assoc($sql1)) {
					
					$unikat = $row1['variable'];
					
					if ($unikat != '') {
						$echo = '<p><b>('.$row['variable'].') '.strip_tags($row['naslov']).'</b><br>';
						//$echo .= '('.$unikat.')<br>';
						$echo .= '';
						
						foreach ($dostop AS $anketa) {
							
							$subsql = sisplet_query("SELECT s.id, a.naslov, a.db_table FROM srv_spremenljivka s, srv_grupa g, srv_anketa a WHERE s.tip IN (1, 2, 3) AND s.sistem='1' AND s.variable='$row[variable]' AND s.gru_id=g.id AND g.ank_id='$anketa' AND g.ank_id=a.id AND g.ank_id != '$this->anketa' AND a.active>='0'");
							if (!$subsql) echo mysqli_error($GLOBALS['connect_db']);
							while ($subrow = mysqli_fetch_assoc($subsql)) {
	
								$db_table = ($subrow['db_table'] == 1) ? '_active' : '';
								
								$subsql1 = sisplet_query("SELECT d.* FROM srv_data_vrednost".$db_table." d, srv_vrednost v, srv_user u WHERE d.spr_id='$subrow[id]' AND d.vre_id=v.id AND v.variable='$unikat' AND u.id=d.usr_id AND u.deleted='0'");
								if (!$subsql1) echo mysqli_error($GLOBALS['connect_db']);
								while ($subrow1 = mysqli_fetch_assoc($subsql1)) {								
									$echo .= '<a href="'.$site_url.'admin/survey/index.php?anketa='.$anketa.'&a=data&m=quick_edit&usr_id='.$subrow1['usr_id'].'&quick_view=1">'.strip_tags($subrow['naslov']).'</a><br>';
									$hasdata = true;
								}
								
							}
							
						}
						
						$echo .= '</p>';
					}
				}
				
			}
			
			if ($hasdata) {
				echo $echo;
				$anydata = true;
			}
		}
		
		if ( $anydata == false ) {
			#echo '<tr><td class="left"></td><td class="right">'.$lang['srv_no_data'].'</td></tr>';
			echo ''.$lang['srv_no_data'].'';
		}
	}
	
}

?>