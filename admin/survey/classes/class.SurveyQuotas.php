<?php
/**
* @author 	Peter Hrvatin
* @date		December 2015
*
*/

	
class SurveyQuotas {
	
	private $anketa;									# id ankete	
	private $spremenljivka;							# id spremenljivke	

	
	/**
	* 	Konstruktor
	*/
	public function __construct( $anketa = null ) {
		global $global_user_id, $site_path;
	
		// če je podan anketa ID		
		if ((int)$anketa > 0) { 		

			$this->anketa = $anketa;
		}
		else {
			die("Napaka!");
		}
	}
	
	
	public function quota_display($condition, $long_alert=0) {
        global $lang;

        $echo = '';
        $echo .= '<span class="quota_display">';

        if ($condition < 0) {
	        $rowC = Cache::srv_spremenljivka($condition < 0 ? -$condition : $condition);
	        $echo .= $rowC['variable'].' = ';
		}

        $sql = sisplet_query("SELECT * FROM srv_quota WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $bracket = 0;
        $i = 0;
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['operator'] == 0)
                    $echo .= ' <span class="conjunction">+</span> ';
                elseif ($row['operator'] == 1)
                    $echo .= ' <span class="conjunction">-</span> ';
                elseif ($row['operator'] == 2)
                    $echo .= ' <span class="conjunction">*</span> ';
                elseif ($row['operator'] == 3)
                    $echo .= ' <span class="conjunction">/</span> ';

            for ($i=1; $i<=$row['left_bracket']; $i++)
                if ($long_alert == 1)
                    $echo .= ' <span class="bracket'.(($bracket++)%12).'">(</span> ';
                else
                    $echo .= ' ( ';

			// Pri kvotah imamo vedno count
			$echo .= $lang['srv_quota'].' (';
					
            // spremenljivke
            if ($row['spr_id'] > 0) {

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Cache::srv_spremenljivka($row['spr_id']);
                    if ($row1['tip'] != 7) {
                    	$variable = $row1['variable'];					
					}
					// number
					else {
						$variable = $row1['variable'].'['.($row['grd_id']+1).']';
					}
                } 
				// multigrid, checkbox, radio
				elseif ($row['grd_id'] == 0) {
                    $sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
                    
					$rowS = Cache::srv_spremenljivka($row['spr_id']);
					if ($rowS['tip'] != 1) {
						$variable = $row1['variable'];
					}
					else{
						$variable = $rowS['variable'].'_'.$row1['variable'];
					}
                } 
				// multichecckbox, multinumber
				elseif ($row['grd_id'] > 0) {
					$sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
                    $sql1g = sisplet_query("SELECT variable FROM srv_grid WHERE id = '$row[grd_id]'");
                    if (!$sql1g) echo mysqli_error($GLOBALS['connect_db']);
                    $row1g = mysqli_fetch_array($sql1g);
                    $variable = $row1['variable'].'['.$row1g['variable'].']';
                }

                if ($long_alert) $echo .= '<strong>';
                $echo .= $variable;
				if ($long_alert) $echo .= '</strong>';

            // konstante
            } elseif ($row['spr_id'] < 0) {

                $echo .= $lang['srv_quota_status_'.(-1*$row['spr_id'])];

            }
			
			// Pri kvotah imamo vedno count
			$echo .= ')';	

            for ($i=1; $i<=$row['right_bracket']; $i++)
                if ($long_alert == 1)
                    $echo .= ' <span class="bracket'.((--$bracket)%12).'">)</span> ';
                else
                    $echo .= ' ) ';

        }

        $echo .= '</span>';

        if ($long_alert) {
            $quota_check = $this->quota_check($condition);

            if ( $quota_check != 0) {

                if ($quota_check == 1)
                	$echo .= '<br /><span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_spremenljivka'].'</span>';
                elseif ($quota_check == 2)
                	$echo .= '<br /><span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_spremenljivka'].'</span>';
                elseif ($quota_check == 3)
                	$echo .= '<br /><span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_oklepaji'].'</span>';

			}
        }

        return $echo;
    }


	// Izpise urejanje kvote
    private function quota_editing ($condition, $vrednost=0) {
        global $lang;

        echo '<div class="popup_close"><a href="#" onClick="quota_editing_close(\''.$condition.'\', \''.$vrednost.'\'); return false;">✕</a></div>';

        echo '<div id="quota_editing_inner">';
        $this->quota_editing_inner($condition, $vrednost);
        echo '</div>';

        echo '<div id="bottom_space">';

		$row = Cache::srv_spremenljivka(-$condition);
        if ($condition < 0) {		
			echo '<p style="float:left; padding:0; margin:0; margin-left:20px">'.$lang['srv_variable'].': <input type="text" id="variable_'.(-$condition).'" value="'.$row['variable'].'" onkeyup="quota_edit_variable(\''.-$condition.'\');" style="width:60px" /></p>';
        }
		

        echo '<div id="condition_editing_close">';

        // kvota kot spremenljivka (lahko jo zbrisemo)
        if ($condition < 0) {
	        echo '<span class="buttonwrapper spaceRight floatLeft">';
	        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="brisi_spremenljivko(\''.(-$condition).'\'); return false;"><span>'.$lang['srv_anketadelete_txt'].'</span></a>';
	        echo '</span>';
		}

		echo '<span class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="quota_editing_close(\''.$condition.'\', \''.$vrednost.'\'); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
        echo '</span>';

        echo '<span class="buttonwrapper floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="quota_editing_close(\''.$condition.'\', \''.$vrednost.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        echo '</span>';

        echo '</div>';
        echo '</div>';
    }

    private function quota_editing_inner ($condition, $vrednost=0) {
		global $lang;

        echo '<div class="quota_editing_preview">';

        echo '<h2>'.$lang['srv_quota'].'</h2>';
        echo '<div id="quota_editing_calculations">';
        echo $this->quota_display($condition, 1);
        echo '</div>';
		echo '</div>';

        echo '<div class="condition_editing_body">';
        echo '<h2>'.$lang['srv_edit_quota'].'</h2>';
        echo '</div>';
		
		// Vrednost kvote
		/*$rowS = Cache::srv_spremenljivka(-$condition);
		echo '<div id="quota_value_holder">';
		echo $lang['srv_quota_value'].': ';
		echo ' <input type="text" name="value" id="quota_value" value="'.$rowS['vsota_limit'].'" style="width:50px" onkeypress="checkNumber(this, 6, 0);" onkeyup="checkNumber(this, 9, 0);" onBlur="quota_value_edit(\''.$rowS['id'].'\');">';
		echo '</div>';*/
		
        $sql = sisplet_query("SELECT id FROM srv_quota WHERE cnd_id = '$condition' ORDER BY vrstni_red");
        if (mysqli_num_rows($sql) == 0) {
            sisplet_query("INSERT INTO srv_quota (id, cnd_id, vrstni_red) VALUES ('', '$condition', '1')");
            $sql = sisplet_query("SELECT id FROM srv_quota WHERE cnd_id = '$condition' ORDER BY vrstni_red");
        }
        while ($row = mysqli_fetch_array($sql)) {
			$this->quota_edit($row['id'], $vrednost);
        }

        echo '<p id="quota_editing_operators" style="margin-left:62px">'.$lang['srv_add_cond'].':
        <a href="#" onclick="quota_add(\''.$condition.'\', \'0\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;+&nbsp;</strong></a>,
        <a href="#" onclick="quota_add(\''.$condition.'\', \'1\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;-&nbsp;</strong></a>,
        <a href="#" onclick="quota_add(\''.$condition.'\', \'2\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;*&nbsp;</strong></a>,
        <a href="#" onclick="quota_add(\''.$condition.'\', \'3\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;/&nbsp;</strong></a>
        </p>';
    }

    /**
    * @desc vrstica v urejanju kvot
    */
    private function quota_edit ($quota, $vrednost=0) {
        global $lang;

		$b = new Branching($this->anketa);
		
        $sql = sisplet_query("SELECT * FROM srv_quota WHERE id = '$quota'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $sql1 = sisplet_query("SELECT if_id FROM srv_condition WHERE id = '{$row['cnd_id']}'");
        $row1 = mysqli_fetch_array($sql1);

        if ($row['cnd_id'] > 0) {	// kalkulacija znotraj pogoja
        	$vrstni_red = $b->vrstni_red($b->find_before_if($row1['if_id']));
		} else {		// kalkulacija kot spremenljivka
			$vrstni_red = $b->vrstni_red(-$row['cnd_id']) - 1;	// -1 je da ne prikaze se trenutne kalkulacije
		}

        $sql_count = sisplet_query("SELECT COUNT(*) AS count FROM srv_quota WHERE cnd_id='$row[cnd_id]'");
        if (!$sql_count) die();
        $row_count = mysqli_fetch_array($sql_count);

        if ($row['vrstni_red'] == 1 && $row_count['count'] > 1) {
			echo '<table class="tbl_condition_editing">';
			echo '<tr>';
			echo '<th style="text-align:center; width:50px">'.$lang['srv_oklepaji'].'</th>'; // gumbi oklepaji
			echo '<th  style="width:70px">&nbsp;</th>'; // logicni operatorji
			echo '<th style="width:50px">&nbsp;</th>'; // oklepaji
			echo '<th >&nbsp;</th>';
			echo '<th style="text-align:center; width:50px;">'.$lang['srv_zaklepaji'].'</th>'; // gumbi zaklepaji
			echo '<th style="text-align:center; width:60px">'.$lang['edit2'].'</th>'; // move
			echo '</tr>';
        	echo '</table>';

        } elseif ($row['vrstni_red'] == 1) {
			echo '<table class="tbl_condition_editing"><tr><th>&nbsp;</th></tr></table>';
        }

        // form
        echo '<form name="quota_'.$quota.'" id="quota_'.$quota.'" action="" method="post" onsubmit="quota_edit(\''.$quota.'\'); return false;">'."\n\r";

        echo '<table class="tbl_condition_editing" style="margin-bottom:10px; padding-bottom:10px; background-color:white">';
        echo '<tr>';


        // left_bracket
		if ($row_count['count'] != 1 || $row['left_bracket']>0 || $row['right_bracket']>0) {
            echo '<td class="tbl_ce_lol white" style="width:50px; text-align:center;" >';
			echo '<a href="#" onclick="javascript:quota_bracket_edit_new(\''.$quota.'\', \''.$vrednost.'\', \'left\', \'plus\' ); return false;" title="'.$lang['srv_oklepaj_add'].'"><span class="faicon add small"></span></a>';
			if ($row['left_bracket'] > 0)
				echo '<a href="#" onclick="javascript:quota_bracket_edit_new(\''.$quota.'\', \''.$vrednost.'\', \'left\', \'minus\'); return false;" title="'.$lang['srv_oklepaj_rem'].'"><span class="faicon add small"></span></a>';
			else
				echo '<span class="faicon delete_circle icon-grey_normal"></span>';
		} 
		else {
            echo '<td class="tbl_ce_lol white" style="width:50px; text-align:center;" >';
		}
		echo '</td>';

        // operator
        echo '<td class="tbl_ce_tb white" style="width:77px; text-align:center">';

        if ($row['vrstni_red'] == 1) {
            // nimamo nic..
        } else {

            if ($row['operator']==0)
				echo '<a href="#" onclick="quota_operator_edit(\''.$quota.'\', \'1\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;+&nbsp;</a>';
			if ($row['operator']==1)
				echo '<a href="#" onclick="quota_operator_edit(\''.$quota.'\', \'2\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;-&nbsp;</a>';
			if ($row['operator']==2)
				echo '<a href="#" onclick="quota_operator_edit(\''.$quota.'\', \'3\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;*&nbsp;</a>';
			if ($row['operator']==3)
				echo '<a href="#" onclick="quota_operator_edit(\''.$quota.'\', \'0\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;/&nbsp;</a>';
        }

        echo '</td>';


        // left_bracket
        echo '<td class="tbl_ce_tb white" style="width:40px; text-align:center">';
        for ($i=$row['left_bracket']; $i>0; $i--) {
            echo ' ( ';        }
        echo '</td>';

        // spremenljivka
        echo '<td class="tbl_ce_tb white" style="width:auto">';
        if ($row['spr_id']==0) echo '<span class="red">'.$lang['srv_select_spr'].'!</span>';
        echo '<br />';
        echo '<select name="quota_spremenljivka_'.$quota.'" id="quota_spremenljivka_'.$quota.'" size="1" style="width:150px" onchange="javascript:quota_edit(\''.$quota.'\', \''.$vrednost.'\');">'."\n\r";

        echo '<option value="0"></option>';
		
		// Kvote po statusu
        echo '<option value="-1"'. ($row['spr_id']==-1 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_1'].'</option>';
        echo '<option value="-2"'. ($row['spr_id']==-2 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_2'].'</option>';
        echo '<option value="-3"'. ($row['spr_id']==-3 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_3'].'</option>';
        echo '<option value="-4"'. ($row['spr_id']==-4 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_4'].'</option>';
        echo '<option value="-5"'. ($row['spr_id']==-5 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_5'].'</option>';
        echo '<option value="-6"'. ($row['spr_id']==-6 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_6'].'</option>';
		// Kvota po ustreznih odgovorih
        echo '<option value="-7"'. ($row['spr_id']==-7 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_7'].'</option>';
		// Kvota po vseh odgovorih
        echo '<option value="-8"'. ($row['spr_id']==-8 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota_status_8'].'</option>';

        $sql1 = sisplet_query("SELECT s.id, s.gru_id, s.naslov, s.variable, s.tip, g.naslov AS grupa_naslov
                            FROM srv_spremenljivka s, srv_grupa g
                            WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.tip IN (1, 2, 3, 6, 7, 22, 16, 20, 17, 18)
                            ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");

        if(!$sql1) echo mysqli_error($GLOBALS['connect_db']);

        $prev_grupa = 0;

        while ($row1 = mysqli_fetch_array($sql1)) {

            if ($b->vrstni_red($row1['id']) <= $vrstni_red) {

            	if ($row1['gru_id'] != $prev_grupa) {
					echo '<option value="0" disabled style="font-style: italic;">'.$row1['grupa_naslov'].'</option>';
					$prev_grupa = $row1['gru_id'];
            	}


				// radio, select, checkbox
                if ($row1['tip'] == 1 || $row1['tip'] == 2 || $row1['tip'] == 3) {

                	echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>';

                    $sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        if ($row2['id'] == $row['vre_id'] && $row['grd_id'] == 0)
                            $selected = ' selected="selected"';
                        else
                            $selected = '';

                        echo '<option value="vre_'.$row2['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.($row1['tip'] == 1 ? $row1['variable'].'_' : '').''.$row2['variable'].') '.$row2['naslov'].'</option>'."\n\r";
                    }
				} 
				// multigrid, multichecbox
				elseif ($row1['tip'] == 6 || $row1['tip'] == 16) {

					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>';

                    $sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.$row2['naslov'].'</option>'."\n\r";

                        $sql3 = sisplet_query("SELECT id, naslov, variable FROM srv_grid WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                        while ($row3 = mysqli_fetch_array($sql3)) {

							if ($row1['id']==$row['spr_id'] && $row2['id']==$row['vre_id'] && $row3['id']==$row['grd_id'])
		                        $selected = ' selected="selected"';
		                    else
		                        $selected = '';

		                    echo '<option value="mlti_'.$row1['id'].'_'.$row2['id'].'_'.$row3['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ('.$row3['variable'].') '.$row3['naslov'].'</option>'."\n\r";
                        }
                    }
                } 
				// multinumber
				elseif ($row1['tip'] == 20) {

					/*echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>';

                    $sql2 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.$row2['naslov'].'</option>'."\n\r";

                        $sql3 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                        while ($row3 = mysqli_fetch_array($sql3)) {

							if ($row1['id']==$row['spr_id'] && $row2['id']==$row['vre_id'] && $row3['id']==$row['grd_id'])
		                        $selected = ' selected="selected"';
		                    else
		                        $selected = '';

		                    echo '<option value="mlti_'.$row1['id'].'_'.$row2['id'].'_'.$row3['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ('.$row3['variable'].') '.$row3['naslov'].'</option>'."\n\r";
                        }
                    }*/
                } 
				// number
				elseif ($row1['tip'] == 7) {

					/*echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>'."\n\r";

					// number ima lahko dva polja
					for ($i=0; $i<$row1['size']; $i++) {

						if ($row1['id'] == $row['spr_id'] && $i == $row['grd_id'])
	                        $selected = ' selected="selected"';
	                    else
	                        $selected = '';

	                    echo '<option value="num_'.$row1['id'].'_'.$i.'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.($i+1).') '.($i+1).'. '.$lang['srv_field'].'</option>'."\n\r";
					}*/				
				} 
				// vsi ostali (numericni)
				else {
                    /*if ($row1['id'] == $row['spr_id'])
                        $selected = ' selected="selected"';
                    else
                        $selected = '';

                    echo '<option value="'.$row1['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>'."\n\r";*/
                }
            }
        }

        echo '</select>';


		// Vrednost kvote
		/*echo ' <input type="text" name="value" id="quota_value_'.$quota.'" value="'.$row['value'].'" style="width:40px" >';
		echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
			echo '$(document).ready(function() {' .
			'  $("input#quota_value_'.$quota.'").bind("blur", {}, function(e) {' .
			'    quota_edit(\''.$quota.'\', \''.$vrednost.'\'); return false;  ' .
			'  });' .
			'});';
		echo '</script>';*/


        echo '<br />&nbsp;'."\n\r";
        echo '</td>';


        // right_bracket
        echo '<td class="tbl_ce_tb white" style="width:40px; text-align:center">';
        for ($i=$row['right_bracket']; $i>0; $i--) {
            echo ' ) ';
        }
        echo '</td>';


        // right_bracket buttons
		if ($row_count['count'] != 1 || $row['right_bracket']>0 || $row['left_bracket']>0) {
            echo '<td class="tbl_ce_lor" style="width:50px; text-align:center" nowrap>';
			if ($row['right_bracket'] > 0)
				echo '<a href="#" onclick="javascript:quota_bracket_edit_new(\''.$quota.'\', \''.$vrednost.'\', \'right\', \'minus\'); return false;" title="'.$lang['srv_zaklepaj_rem'].'"><span class="faicon delete_circle"></span></a>';
			else
				echo '<span class="faicon delete_circle icon-grey_normal"></span>';

			echo '<a href="#" onclick="javascript:quota_bracket_edit_new(\''.$quota.'\', \''.$vrednost.'\', \'right\', \'plus\' ); return false;" title="'.$lang['srv_zaklepaj_add'].'"><span class="faicon add small"></span></a>';
		} 
		else {
            echo '<td class="tbl_ce_lor white" style="width:50px; text-align:center" nowrap>';
		}
		echo '</td>';

		// move
        echo '<td class="tbl_ce_bck_blue white" style="text-align:right; width:30px">';
        if ($row_count['count'] != 1 )
        	echo '<img src="img_0/move_updown.png" class="move" title="'.$lang['srv_move'].'" />';
        echo '</td>';

        // remove
        echo '<td class="tbl_ce_bck_blue white" style="text-align:left; width:30px">';
        $sql3 = sisplet_query("SELECT id FROM srv_quota WHERE cnd_id='$row[cnd_id]'");
        if (mysqli_num_rows($sql3) != 1 )
            echo ' <a href="#" onclick="quota_remove(\''.$row['cnd_id'].'\', \''.$quota.'\', \''.$vrednost.'\'); return false;" title="'.$lang['srv_if_rem'].'"><span class="faicon delete icon-grey_dark_link delte-if-block"></span></a>'."\n\r";
        echo '</td>';


        echo '</tr>';
        echo '</table>';

        echo '</form>'."\n\r";
    }

	 /**
    * @desc preveri ali so oklepaji pravilno postavljeni v kalkulacijah (presteje predoklepaje in zaklepaje)
    * in pa ce je izbrana spremenljivka
    */
    private function quota_check ($condition) {

        $sql = sisplet_query("SELECT spr_id, left_bracket, right_bracket FROM srv_quota WHERE cnd_id='$condition' ORDER BY vrstni_red");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

		if (mysqli_num_rows($sql) == 0) return 1;	// ni sploh se izbrana spremenljivka (ker ni quota sploh se inicializiran z default vrstico)

        $bracket = 0;
        while ($row = mysqli_fetch_array($sql)) {
        	if ($row['spr_id'] == 0)	// ce ni izbrana spremenljivka v eni od vrstic
        		return 2;

            $bracket = $bracket + $row['left_bracket'] - $row['right_bracket'];
            if ($bracket < 0)
                return 3;		// oklepaj
        }

        if ($bracket == 0)
            return 0;			// vse ok
        else
            return 3;			// zaklepaj
    }


	
	public function ajax() {
		global $global_user_id;
		global $lang;
		global $site_path;
			
		if (isset($_POST['anketa'])) $this->anketa = $_POST['anketa'];
        if (isset($_POST['spremenljivka'])) $this->spremenljivka = $_POST['spremenljivka'];
		
		$ajax = 'ajax_' . $_GET['a'];
		
		if ( method_exists('SurveyQuotas', $ajax) )
			$this->$ajax();
		else
			echo 'method '.$ajax.' does not exist';
	}
	
	private function ajax_quota_editing () {
    	if ($_POST['noupdate'] != 1) {
        	Common::updateEditStamp();
		}
		
        $condition = $_POST['condition'];
        $vrednost = $_POST['vrednost'];
        
        $sql = sisplet_query("SELECT vre_id FROM srv_condition WHERE id = '$condition'");
        $row = mysqli_fetch_array($sql);
        
        $quota = $this->quota_editing($condition, $vrednost);
        
        if ($row['vre_id'] == 0) {
            $s = sisplet_query("UPDATE srv_condition SET vre_id='$quota' WHERE id='$condition'");
            if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        }
    }
    
    private function ajax_quota_editing_close () {
        
        $condition = $_POST['condition'];
        $vrednost = $_POST['vrednost'];
        
        $sql = sisplet_query("SELECT if_id FROM srv_condition WHERE id = '$condition'");
        $row = mysqli_fetch_array($sql);
        
        $b = new Branching($this->anketa);
        if ($condition >= 0)
        	$b->condition_editing($row['if_id'], $vrednost);
        else {
        	$row = SurveyInfo::getInstance()->getSurveyRow();
        	if ($row['expanded'] == 1) {
				$b->vprasanje(-$condition);
			} else {
        		$b->spremenljivka_name(-$condition);
			}
		}
    }
    
    private function ajax_quota_save () {
    	if ($_POST['noupdate'] != 1) {
    		Common::updateEditStamp();
		}
		
        $quota = $_POST['quota'];
        $expression = $_POST['expression'];
        
        sisplet_query("UPDATE srv_quota SET expression='$expression' WHERE id = '$quota'");
    }
    
    private function ajax_quota_add () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $condition = $_POST['condition'];
        $operator = $_POST['operator'];
        $vrednost = $_POST['vrednost'];
        
        $sql = sisplet_query("SELECT MAX(vrstni_red) AS max FROM srv_quota WHERE cnd_id = '$condition'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['max'] + 1;

        $s = sisplet_query("INSERT INTO srv_quota (id, cnd_id, operator, vrstni_red) VALUES ('', '$condition', '$operator', '$vrstni_red')");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        
        $this->quota_editing_inner($condition, $vrednost);
    }
    
    private function ajax_quota_operator_edit () {
		if ($_POST['noupdate'] != 1) {
			Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
		$quota = $_POST['quota'];
		$operator = $_POST['operator'];
		
        sisplet_query("UPDATE srv_quota SET operator='$operator' WHERE id = '$quota'");

        $sql = sisplet_query("SELECT cnd_id FROM srv_quota WHERE id = '$quota'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $this->quota_editing_inner($row['cnd_id'], $_POST['vrednost']);
    }
    
    private function ajax_quota_edit () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $spremenljivka = $_POST['spremenljivka'];
        $quota = $_POST['quota'];
        $value = $_POST['value'];
        $vrednost = $_POST['vrednost'];
        $grd_id = 0;
        
        // obicna spremenljivka
        if (is_numeric($spremenljivka)) {
            $spr_id = $spremenljivka;
            $vre_id = 0;
            
        // checkbox, multigrid
        } elseif ( strpos($spremenljivka, 'vre_') !== false ) {
            $e = explode('_', $spremenljivka);
			list( , $vre_id, $grd_id) = $e;
            $sql2 = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id = '$vre_id'");
            $row2 = mysqli_fetch_array($sql2);
            $spr_id = $row2['spr_id'];
        
        // number
		} elseif ( strpos($spremenljivka, 'num_') !== false ) {
        	
        	$e = explode('_', $spremenljivka);
			list( , $spr_id, $grd_id) = $e;
        	$vre_id = 0;
        	
        // multicheckbox, multinumber
        } else {
			$e = explode('_', $spremenljivka);
			list( , $spr_id, $vre_id, $grd_id) = $e;
        }
        
        if (!is_numeric($value)) $value = 0;
        
        $s = sisplet_query("UPDATE srv_quota SET spr_id='$spr_id', vre_id='$vre_id', grd_id='$grd_id', value='$value' WHERE id = '$quota'");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        
        $sql = sisplet_query("SELECT cnd_id FROM srv_quota WHERE id = '$quota'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $this->quota_editing_inner($row['cnd_id'], $vrednost);
    }

	private function ajax_quota_value_edit (){
		
		if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
		if(isset($_POST['spremenljivka']) && $_POST['spremenljivka'] > 0){
			$spremenljivka = $_POST['spremenljivka'];
			$value = $_POST['value'];
			
			$s = sisplet_query("UPDATE srv_spremenljivka SET vsota_limit='$value' WHERE id = '$spremenljivka'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
    
    private function ajax_quota_remove () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $quota = $_POST['quota'];
        $vrednost = $_POST['vrednost'];

        sisplet_query("DELETE FROM srv_quota WHERE id='$quota'");

        $b = new Branching($this->anketa);
        $b->repare_calculation($_POST['condition']);

        $this->quota_editing_inner($_POST['condition'], $vrednost);
    }
    
    private function ajax_quota_bracket_edit_new () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $who = $_POST['who'];
        $what = $_POST['what'];
        $left_bracket = $_POST['left_bracket'];
        $quota = $_POST['quota'];

        if ($who == 'left')
        	if ($what == 'plus')
        		$bracket = 'left_bracket=left_bracket+1';
        	else
        		$bracket = 'left_bracket=left_bracket-1';
        else
        	if ($what == 'plus')
        		$bracket = 'right_bracket=right_bracket+1';
        	else
        		$bracket = 'right_bracket=right_bracket-1';
        		
        
        sisplet_query("UPDATE srv_quota SET $bracket WHERE id = '$quota'");

        $sql = sisplet_query("SELECT cnd_id FROM srv_quota WHERE id = '$quota'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $this->quota_editing_inner($row['cnd_id'], $_POST['vrednost']);
    }
    	
	private function ajax_quota_sort() {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
    	$condition = $_POST['condition'];
    	$sortable = $_POST['sortable'];
    	$sortable = explode('&', $sortable);
    	
    	$i=1;
    	foreach ($sortable AS $calc) {
			$quota = explode('=', $calc);
			$quota = $quota[1];
			
			$s = sisplet_query("UPDATE srv_quota SET vrstni_red='{$i}' WHERE id='{$quota}'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);

			$i++;
    	}
    	
		$b = new Branching($this->anketa);
		$b->repare_condition($if);
		$this->quota_editing_inner($condition);
	}
	
	private function ajax_quota_edit_variable () {
		Common::updateEditStamp();
		
		if(isset($_POST['spremenljivka']) && $_POST['spremenljivka'] > 0){
			$spremenljivka = $_POST['spremenljivka'];
			$variable = $_POST['variable'];
			
			// preverimo, da ni se kje drugje v anekti tako ime spremenljivke
			$sqlv = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
			$ok = true;
			while ($rowv = mysqli_fetch_array($sqlv)) {
				if ($rowv['variable'] == $variable) 
					$ok = false;
			}

			// Ce se ime ze pojavi v anketi mu dodamo stevilko
			if(!$ok){
				$ok = false;
				$i = 2;
				while(!$ok){				
					$ok = true;
					$variable = $_POST['variable'].'_'.$i;
					
					$sqlv = sisplet_query("SELECT s.variable, s.id as id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
					while ($rowv = mysqli_fetch_array($sqlv)) {
						if ($rowv['variable'] == $variable && $spremenljivka != $rowv['id']){
							$ok = false;
							$i++;
						}
					}
				}
			}
			
			sisplet_query("UPDATE srv_spremenljivka SET variable='$variable', variable_custom='1' WHERE id = '$spremenljivka'");
		}
	}
	
}
?>