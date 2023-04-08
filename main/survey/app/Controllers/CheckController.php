<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 12.02.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\FindController as Find;
use App\Models\Model;
use Cache;
use enkaParameters;
use SurveyInfo;
use SurveyMissingValues;
use Common;
use Mobile_Detect;
use AppSettings;


class CheckController extends Controller
{

    public function __construct()
    {
        parent::getGlobalVariables();
        parent::getAllVariables();

    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new CheckController();
    }

    private function refreshVariables()
    {
        return parent::getAllVariables();
    }

    /**
     * @desc preveri ali so na trenutni grupi prikazana vprasanja (zaradi branchinga)
     */
    public function checkGrupa()
    {

        $sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id = '" . get('grupa') . "' ORDER BY vrstni_red");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            if ($this->checkSpremenljivka($row['id']))
                return true;

        }

        return false;
    }

    /**
     * @desc preveri ali je spremenljivka vidna (zaradi branchinga) (oz. lahko je nastavljena vidnost samo administratorju)
     */
    public function checkSpremenljivka($spremenljivka, $isTestData=false)
    {
        $checkSpremenljivka = get('checkSpremenljivka');
        if (array_key_exists($spremenljivka, $checkSpremenljivka)) {
            return $checkSpremenljivka[$spremenljivka];
        }

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        // ce vprasanje ni vidno ali ce uporabnik nima dostopa do vprasanja
        if ($row['visible'] == 0 || !((self::$admin_type <= $row['dostop'] && self::$admin_type >= 0) || (self::$admin_type == -1 && $row['dostop'] == 4))) {
            return save('checkSpremenljivka[' . $spremenljivka . ']', false, 1);
        }

		// Pri testnih podatkih ne upostevamo ifov, ker drugace nic ne napolni
		if(!$isTestData){
			$sql1 = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$spremenljivka'");
			if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$row1 = mysqli_fetch_array($sql1);

			if (!$this->checkIf($row1['parent']))
				return save('checkSpremenljivka[' . $spremenljivka . ']', false, 1);
		}

        return save('checkSpremenljivka[' . $spremenljivka . ']', true, 1);
    }

    /**
     * @desc preveri ali se elementi v podanem IFu prikazejo ali ne
     */
    public function checkIf($if)
    {
        if ($if == 0) return true;

        $checkIf = get('checkIf');
        if (array_key_exists($if, $checkIf)) {
            return $checkIf[$if];
        }

        // preverimo po strukturi navzgor
        $sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_if = '$if'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);
        if (!$this->checkIf($row['parent'])) {
            return save('checkIf[' . $if . ']', false, 1);
        }

        $sql = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        // ce je IF oznacen da se vedno prikaze
        if ($row['enabled'] == 1)
            return save('checkIf[' . $if . ']', true, 1);

        // ce je IF oznacen da se nikoli ne prikaze
        if ($row['enabled'] == 2)
            return save('checkIf[' . $if . ']', false, 1);

        // ce je IF oznacen kot blok, potem se vedno prikaze
        if ($row['tip'] == 1)
            return save('checkIf[' . $if . ']', true, 1);


        $eval = "if (";

        //$sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if' ORDER BY vrstni_red ASC");
        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $i = 0;
        // zgeneriramo pogoje z oklepaji, ki jih potem spustimo skozi eval
        while ($row = mysqli_fetch_array($sql)) {
            if ($i++ != 0)
                if ($row['conjunction'] == 0)
                    $eval .= ' && ';
                else
                    $eval .= ' || ';

            if ($row['negation'] == 1)
                $eval .= ' ! ';

            for ($i = 1; $i <= $row['left_bracket']; $i++)
                $eval .= ' ( ';

            if ($this->checkCondition($row['id']))
                $eval .= ' true ';
            else
                $eval .= ' false ';

            for ($i = 1; $i <= $row['right_bracket']; $i++)
                $eval .= ' ) ';
        }
        $eval .= ") return true; else return false; ";

        // ne glih best practice, ampak takle mamo...
        $eval = @eval($eval);
        if ($eval === true)
            return save('checkIf[' . $if . ']', true, 1);

        return save('checkIf[' . $if . ']', false, 1);
    }

    /**
     * @desc preveri podani condition
     */
    public function checkCondition($condition)
    {

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        // obicne spremenljivke
        if ($row['spr_id'] > 0) {

            $row2 = Model::select_from_srv_spremenljivka($row['spr_id']);

            // radio, checkbox, dropdown
            if ($row2['tip'] <= 3) {

                $sql3 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_data_vrednost" . get('db_table') . " v
                                     WHERE c.cond_id='$condition' AND c.vre_id=v.vre_id
                                     AND v.spr_id='$row[spr_id]' AND usr_id='" . get('usr_id') . "'");

                // Dodatno preverjanje ce imamo pogoj -1
                $sql3b = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id='$condition' AND vre_id='-1'");
                $sql4b = sisplet_query("SELECT * FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND usr_id='" . get('usr_id') . "'");

                if ($row['operator'] == 0 && mysqli_num_rows($sql3) == 0 && mysqli_num_rows($sql3b) == 0)
                    return false;
                elseif ($row['operator'] == 0 && mysqli_num_rows($sql3b) > 0 && mysqli_num_rows($sql4b) > 0)
                    return false;
                elseif ($row['operator'] == 1 && mysqli_num_rows($sql3) > 0)
                    return false;
                elseif ($row['operator'] == 1 && mysqli_num_rows($sql3b) > 0 && mysqli_num_rows($sql4b) == 0)
                    return false;

                // multigrid
            } elseif ($row2['tip'] == 6 || $row2['tip'] == 16) {

                // tabela radio (brez dvojne tabele - ki gre v checkgrid)
                if ($row2['tip'] == 6 && $row2['enota'] != 3) {
                    $sql3 = sisplet_query("SELECT * FROM srv_condition_grid c, srv_data_grid" . get('db_table') . " d
	                                     WHERE c.cond_id='$condition' AND d.spr_id='$row[spr_id]'
	                                     AND c.grd_id=d.grd_id AND d.usr_id='" . get('usr_id') . "' AND d.vre_id='$row[vre_id]'");

                    // Dodatno preverjanje ce imamo pogoj -1
                    $sql3b = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$condition' AND grd_id='-1'");
                    $sql4b = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND vre_id='$row[vre_id]' AND usr_id='" . get('usr_id') . "'");
                } // tabela checkboxov
                else {
                    $sql3 = sisplet_query("SELECT * FROM srv_condition_grid c, srv_data_checkgrid" . get('db_table') . " d
	                                     WHERE c.cond_id='$condition' AND d.spr_id='$row[spr_id]'
	                                     AND c.grd_id=d.grd_id AND d.usr_id='" . get('usr_id') . "' AND d.vre_id='$row[vre_id]'");

                    // Dodatno preverjanje ce imamo pogoj -1
                    $sql3b = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$condition' AND grd_id='-1'");
                    $sql4b = sisplet_query("SELECT * FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND vre_id='$row[vre_id]' AND usr_id='" . get('usr_id') . "'");
                }
                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                if ($row['operator'] == 0 && !mysqli_num_rows($sql3) > 0 && !mysqli_num_rows($sql3b) > 0)
                    return false;
                elseif ($row['operator'] == 0 && mysqli_num_rows($sql3b) > 0 && mysqli_num_rows($sql4b) > 0)
                    return false;
                elseif ($row['operator'] == 1 && !mysqli_num_rows($sql3) == 0)
                    return false;
                elseif ($row['operator'] == 1 && mysqli_num_rows($sql3b) > 0 && mysqli_num_rows($sql4b) == 0)
                    return false;

            } elseif ($row2['tip'] == 19 || $row2['tip'] == 20) {

                $sql3 = sisplet_query("SELECT text FROM srv_data_textgrid" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND vre_id='$row[vre_id]' AND usr_id='" . get('usr_id') . "' AND grd_id='$row[grd_id]'");
                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                $row3 = mysqli_fetch_array($sql3);

                if ($row['operator'] == 0 && !($row3['text'] == $row['text']))
                    return false;
                elseif ($row['operator'] == 1 && !($row3['text'] != $row['text']))
                    return false;
                elseif ($row['operator'] == 2 && !($row3['text'] < $row['text']))
                    return false;
                elseif ($row['operator'] == 3 && !($row3['text'] <= $row['text']))
                    return false;
                elseif ($row['operator'] == 4 && !($row3['text'] > $row['text']))
                    return false;
                elseif ($row['operator'] == 5 && !($row3['text'] >= $row['text']))
                    return false;

                // textbox
            } elseif ($row2['tip'] == 21) {

                $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND vre_id='$row[vre_id]' AND usr_id='" . get('usr_id') . "'");
                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                $row3 = mysqli_fetch_array($sql3);

                if ($row['operator'] <= 5) {

                    if ($row['operator'] == 0 && !($row3['text'] == $row['text']))
                        return false;
                    elseif ($row['operator'] == 1 && !($row3['text'] != $row['text']))
                        return false;

                    // length
                } else {

                    if ($row['operator'] == 6 && !(strlen($row3['text']) == $row['text']))
                        return false;
                    elseif ($row['operator'] == 7 && !(strlen($row3['text']) < $row['text']))
                        return false;
                    elseif ($row['operator'] == 8 && !(strlen($row3['text']) > $row['text']))
                        return false;

                }

                // vsota
            } elseif ($row2['tip'] == 18) {

                $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND vre_id='$row[vre_id]' AND usr_id='" . get('usr_id') . "'");
                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                $row3 = mysqli_fetch_array($sql3);

                if ($row['operator'] == 0 && !($row3['text'] == $row['text']))
                    return false;
                elseif ($row['operator'] == 1 && !($row3['text'] != $row['text']))
                    return false;
                elseif ($row['operator'] == 2 && !($row3['text'] < $row['text']))
                    return false;
                elseif ($row['operator'] == 3 && !($row3['text'] <= $row['text']))
                    return false;
                elseif ($row['operator'] == 4 && !($row3['text'] > $row['text']))
                    return false;
                elseif ($row['operator'] == 5 && !($row3['text'] >= $row['text']))
                    return false;

                // razvrscanje
            } elseif ($row2['tip'] == 17) {

                $sql3 = sisplet_query("SELECT * FROM srv_condition_grid c, srv_data_rating d
	                                 WHERE c.cond_id='$condition' AND d.spr_id='$row[spr_id]'
	                                 AND d.vre_id='$row[vre_id]' AND d.usr_id='" . get('usr_id') . "' AND d.vrstni_red=c.grd_id");

                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                if ($row['operator'] == 0 && !mysqli_num_rows($sql3) > 0)
                    return false;
                elseif ($row['operator'] == 1 && !mysqli_num_rows($sql3) == 0)
                    return false;

                // number
            } else {

                if ($row['grd_id'] == 0) $text = 'text';
                else                        $text = 'text2';

                $sql3 = sisplet_query("SELECT " . $text . " AS text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row[spr_id]' AND usr_id='" . get('usr_id') . "'");
                if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);
                $row3 = mysqli_fetch_array($sql3);

                if ($row['operator'] == 0 && !($row3['text'] == $row['text']))
                    return false;
                elseif ($row['operator'] == 1 && !($row3['text'] != $row['text']))
                    return false;
                elseif ($row['operator'] == 2 && !($row3['text'] < $row['text']))
                    return false;
                elseif ($row['operator'] == 3 && !($row3['text'] <= $row['text']))
                    return false;
                elseif ($row['operator'] == 4 && !($row3['text'] > $row['text']))
                    return false;
                elseif ($row['operator'] == 5 && !($row3['text'] >= $row['text']))
                    return false;

            }

            // recnum
        } elseif ($row['spr_id'] == -1) {

            $sqlu = sisplet_query("SELECT recnum FROM srv_user WHERE id = '" . get('usr_id') . "'");
            $rowu = mysqli_fetch_array($sqlu);

            if (!($rowu['recnum'] % $row['modul'] == $row['ostanek']))
                return false;

            // calculations
        } elseif ($row['spr_id'] == -2) {

            $calculation = $this->checkCalculation($row['id']);

            if ($row['operator'] == 0 && !($calculation == $row['text']))
                return false;
            elseif ($row['operator'] == 1 && !($calculation != $row['text']))
                return false;
            elseif ($row['operator'] == 2 && !($calculation < $row['text']))
                return false;
            elseif ($row['operator'] == 3 && !($calculation <= $row['text']))
                return false;
            elseif ($row['operator'] == 4 && !($calculation > $row['text']))
                return false;
            elseif ($row['operator'] == 5 && !($calculation >= $row['text']))
                return false;

            // kvote
        } elseif ($row['spr_id'] == -3) {

            $quota = $this->checkQuota($row['id']);

            if ($row['operator'] == 0 && !($quota == $row['text']))
                return false;
            elseif ($row['operator'] == 1 && !($quota != $row['text']))
                return false;
            elseif ($row['operator'] == 2 && !($quota < $row['text']))
                return false;
            elseif ($row['operator'] == 3 && !($quota <= $row['text']))
                return false;
            elseif ($row['operator'] == 4 && !($quota > $row['text']))
                return false;
            elseif ($row['operator'] == 5 && !($quota >= $row['text']))
                return false;
        
			// naprava
        } elseif ($row['spr_id'] == -4) {

            if (in_array($row['text'], array('0','1','2','3'))){

                // Star nacin detekcije - vedno vezan na prvi prihod, po novem detektiramo vsakic posebej
				/*$sqlU = sisplet_query("SELECT device FROM srv_user WHERE id='".get('usr_id')."'");
				$rowU = mysqli_fetch_array($sqlU);

                if (!($rowU['device'] == $row['text']))
					return false;*/
                
                $device = 0;
                $useragent = $_SERVER['HTTP_USER_AGENT'];

                if ($useragent != '' && get_cfg_var('browscap')) {

                    $browser_detect = get_browser($useragent, true);

                    $detect = New Mobile_Detect();
                    $detect->setUserAgent($useragent);

                    // Detect naprave (pc, mobi, tablet, robot)
                    if ($detect->isMobile()) {
                        if ($detect->isTablet())
                            $device = 2;
                        else
                            $device = 1;
                    } 
                    elseif ($browser_detect['crawler'] == 1){
                        $device = 3;
                    }
                }

				if (!($device == $row['text']))
					return false;                
			}
        }

        return true;
    }

    /**
     * @desc izracuna kalkulacijo in vrne rezultat
     */
    public function checkCalculation($condition)
    {

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);


        $i = 0;
        $expression = '';
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['operator'] == 0)
                    $expression .= ' + ';
                elseif ($row['operator'] == 1)
                    $expression .= ' - ';
                elseif ($row['operator'] == 2)
                    $expression .= ' * ';
                elseif ($row['operator'] == 3)
                    $expression .= ' / ';

            for ($i = 1; $i <= $row['left_bracket']; $i++)
                $expression .= ' ( ';

            // spremenljivke
            if ($row['spr_id'] > 0) {

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {

					$row1 = Model::select_from_srv_spremenljivka($row['spr_id']);

                    if ($row1['tip'] <= 3) {         // radio, checkbox, dropdown
                        $sum = 0;
                        $sql2 = sisplet_query("SELECT v.variable FROM srv_data_vrednost" . get('db_table') . " d, srv_vrednost v WHERE d.vre_id=v.id AND d.spr_id='$row1[id]' AND d.usr_id='" . get('usr_id') . "'");
                        while ($row2 = mysqli_fetch_array($sql2)) {  // zanka je zaradi checkboxov
                            $sum += $row2['variable'];
                        }
                        $expression .= $sum;

                    } elseif ($row1['tip'] == 7 || $row1['tip'] == 22 || $row1['tip'] == 25) {   // number, calculation, quota

                        $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
                        $row2 = mysqli_fetch_array($sql2);
                        if ($row2['text'] == '') $row2['text'] = 0;
                        $expression .= $row2['text'];

                    } elseif ($row1['tip'] == 8) {    // datum

                        $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
                        $row2 = mysqli_fetch_array($sql2);
                        if ($row2['text'] == '') $row2['text'] = 0;

                        $row2['text'] = ceil(strtotime($row2['text']) / (60 * 60 * 24));

                        $expression .= $row2['text'];
                    }

                    // multigrid in checkbox
                } elseif ($row['vre_id'] > 0) {

					$row1 = Model::select_from_srv_spremenljivka($row['spr_id']);

					// checkbox
                    if ($row1['tip'] == 2) {
                        $sum = 0;
                        $sql2 = sisplet_query("SELECT v.variable FROM srv_data_vrednost" . get('db_table') . " d, srv_vrednost v WHERE d.vre_id=v.id AND d.spr_id='$row1[id]' AND d.usr_id='" . get('usr_id') . "'");
                        while ($row2 = mysqli_fetch_array($sql2)) {  // zanka je zaradi checkboxov
                            $sum += $row2['variable'];
                        }
                        $expression .= $sum;
                    }
					// multigrid
					else{
						$sql1 = sisplet_query("SELECT g.variable, g.other FROM srv_data_grid" . get('db_table') . " d, srv_grid g WHERE g.id=d.grd_id AND g.spr_id=d.spr_id AND d.spr_id='$row[spr_id]' AND d.vre_id='$row[vre_id]' AND d.usr_id='" . get('usr_id') . "'");
	                    $row1 = mysqli_fetch_array($sql1);

	                    // Pri multigridih se missingi pri kalkulacijah stejejo kot 0 (in ne -99, -98...)
	                    if ($row1['other'] < 0)
	                        $row1['variable'] = 0;

	                    $expression .= $row1['variable'];
					}
                }

                // konstante
            } elseif ($row['spr_id'] == -1) {

                $expression .= $row['number'];

            }

            for ($i = 1; $i <= $row['right_bracket']; $i++)
                $expression .= ' ) ';

        }

        $expression = 'return ' . $expression . ';';

        return eval($expression);

    }

    /**
     * @desc izracuna kvoto in vrne rezultat
     */
    public function checkQuota($condition)
    {

        $sql = sisplet_query("SELECT * FROM srv_quota WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $i = 0;
        $expression = '';
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['operator'] == 0)
                    $expression .= ' + ';
                elseif ($row['operator'] == 1)
                    $expression .= ' - ';
                elseif ($row['operator'] == 2)
                    $expression .= ' * ';
                elseif ($row['operator'] == 3)
                    $expression .= ' / ';

            for ($i = 1; $i <= $row['left_bracket']; $i++)
                $expression .= ' ( ';

            // spremenljivke
            if ($row['spr_id'] > 0) {

                $row1 = Model::select_from_srv_spremenljivka($row['spr_id']);

                // radio, checkbox, multigrid
                if ($row['vre_id'] > 0) {

                    // multigrid
                    if ($row1['tip'] == 6)
                        $sql2 = sisplet_query("SELECT COUNT(*) AS quota 
												FROM srv_data_grid" . get('db_table') . " d, srv_grid g, srv_user u 
												WHERE g.id=d.grd_id AND g.spr_id=d.spr_id AND d.spr_id='$row[spr_id]' AND d.vre_id='$row[vre_id]' AND d.grd_id='$row[grd_id]' AND d.usr_id=u.id AND u.deleted='0'");

                    // multicheckbox
                    elseif ($row1['tip'] == 16)
                        $sql2 = sisplet_query("SELECT COUNT(*) AS quota 
												FROM srv_data_checkgrid" . get('db_table') . " d, srv_grid g, srv_user u 
												WHERE g.id=d.grd_id AND g.spr_id=d.spr_id AND d.spr_id='$row[spr_id]' AND d.vre_id='$row[vre_id]' AND d.grd_id='$row[grd_id]' AND d.usr_id=u.id AND u.deleted='0'");

                    // radio, select
                    elseif ($row1['tip'] == 1 || $row1['tip'] == 3)
                        $sql2 = sisplet_query("SELECT COUNT(*) AS quota 
												FROM srv_data_vrednost" . get('db_table') . " d, srv_user u 
												WHERE d.vre_id='$row[vre_id]' AND d.spr_id='$row1[id]' AND d.usr_id=u.id AND u.deleted='0'");

                    // checkbox
                    elseif ($row1['tip'] == 2)
                        $sql2 = sisplet_query("SELECT COUNT(*) AS quota 
												FROM srv_data_vrednost" . get('db_table') . " d, srv_user u 
												WHERE d.vre_id='$row[vre_id]' AND d.spr_id='$row1[id]' AND d.usr_id=u.id AND u.deleted='0'");

                    $row2 = mysqli_fetch_array($sql2);

                    $expression .= $row2['quota'];
                }
            } // konstante (statusi...)
            elseif ($row['spr_id'] < 0) {

                $status = '';

                // Kvota ustreznih odgovorov
                if ($row['spr_id'] == -7) {
                    $status .= ' AND (last_status=\'5\' OR last_status=\'6\') AND lurker=\'0\' ';
                } // Kvota posameznih statusov
                elseif ($row['spr_id'] > -7) {
                    $status .= ' AND last_status=\'' . -$row['spr_id'] . '\' ';
                }

                $sqlU = sisplet_query("SELECT COUNT(id) FROM srv_user WHERE ank_id='".get('anketa')."' ".$status." AND deleted='0'");
                $rowU = mysqli_fetch_array($sqlU);

                $expression .= $rowU['COUNT(id)'];
            }

            for ($i = 1; $i <= $row['right_bracket']; $i++)
                $expression .= ' ) ';
        }

        $expression = 'return ' . $expression . ';';

        return eval($expression);
    }

    /************************************************
     * Preveri userja, ki se je vrnil in ga (če je tako nastavljeno) preusmeri na stran, kjer je končal
     ************************************************/
    public function check_cookie_return()
    {

        $row = \SurveyInfo::getInstance()->getSurveyRow();

        // EVALVACIJA - nastavljeno je da ga preusmerimo na stran kjer je koncal - za studentsko evalvacijo vedno preusmerjamo z ustreznimi parametri - ZAENKRAT NI VKLOPLJENO
        if (Common::checkModule('evalvacija') == '1' && false) {
            $sql1 = sisplet_query("SELECT g.id FROM srv_user_grupa" . get('db_table') . " u, srv_grupa g WHERE u.gru_id=g.id AND u.usr_id='" . get('usr_id') . "' ORDER BY g.vrstni_red DESC LIMIT 1");
            if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
            $row1 = mysqli_fetch_array($sql1);

            save('grupa', $row1['id']);
            $grupa = Find::getInstance()->findNextGrupa();

            if ($grupa > 0) {
                $params = $_SERVER['QUERY_STRING'];

                setcookie('ref', get('anketa'));    // cookie z referalom (da se ne sprozi redirect zaradi referala pri ?code= )
                return header("Location: " . \SurveyInfo::getSurveyLink() . "&grupa=$grupa&" . $params . get('cookie_url') . "&c");
                die();
            }
        }

        // nastavljeno je da ga preusmerimo na stran kjer je koncal
        if ($row['cookie_return'] == 1) {

            $sql1 = sisplet_query("SELECT g.id FROM srv_user_grupa" . get('db_table') . " u, srv_grupa g WHERE u.gru_id=g.id AND u.usr_id='" . get('usr_id') . "' ORDER BY g.vrstni_red DESC LIMIT 1");
            if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);

            if(mysqli_num_rows($sql1) > 0){

                $row1 = mysqli_fetch_array($sql1);
                
                save('grupa', $row1['id']);

                $grupa = Find::getInstance()->findNextGrupa();

                if ($grupa > 0) {
                    setcookie('ref', get('anketa'));    // cookie z referalom (da se ne sprozi redirect zaradi referala pri ?code= )
                    return header("Location: " . \SurveyInfo::getSurveyLink() . "&grupa=$grupa" . (isset($_GET['language']) ? '&language=' . $_GET['language'] : '') . (isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . get('cookie_url') . "&c");
                    die();
                }
            }
        }
    }

    /**
     * preveri, ce je katera od spremenljivk CAPTCHA in preveri, ce je pravilno vnesena
     * treba extra preverjat, ce je JS izklopljen
     *
     */
    public function check_captcha()
    {

        // Ce urejamo podatke v admin vmesniku ne preverjamo nicesar
        if(isset($_GET['t']) && $_GET['t'] == 'postprocess' || get('grupa') == '0' || get('grupa') == ''){
            return;
        }

        $sql = sisplet_query("SELECT id, params FROM srv_spremenljivka WHERE gru_id='" . get('grupa') . "' AND visible='1' AND tip='21' ORDER BY vrstni_red ASC");
        while ($row = mysqli_fetch_array($sql)) {

            $spremenljivkaParams = new enkaParameters($row['params']);
            $captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);

            if ($captcha == 1) {
                $recaptchaResponse = $_POST['g-recaptcha-response'];
                $request = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".AppSettings::getInstance()->getSetting('google-secret_captcha')."&response=".$recaptchaResponse);

                // zdaj pa zabeleži mail (pred pošiljanjem)    
                // zdaj pa še v bazi tistih ki so se ročno dodali
                if(strstr($request,"true")){
                    //
                }
                else {
                    echo '<p>Wrong CAPTCHA code.</p><p><a href="#" onclick="javascript:history.go(-1)">Back</a></p>';
                    die();
                }
            }
        }
    }
	
	/**
     * preveri, ce preveri, ce je CAPTCHA pravilno vnesena pri uvodu (ce imamo staticen uvod s preverjanjem captche)
     *
     */
    public function check_captcha_intro(){

		$recaptchaResponse = $_POST['g-recaptcha-response'];
		$request = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".AppSettings::getInstance()->getSetting('google-secret_captcha')."&response=".$recaptchaResponse);

		if(strstr($request,"true")){
			//
		}
		else {
            header('Location: ' . SurveyInfo::getSurveyLink(false, false).'?captcha_error=1');
			die();
		}
    }

    public function checkMissingForSpremenljivka($spremenljivka, $loop_id)
    {
        $missing = false;

        $smv = new SurveyMissingValues(get('anketa'));
        $missing_values = $smv->GetUnsetValuesForSurvey();

        if (count($missing_values) > 0) {

            $qry = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other IN ('" . implode('\',\'', array_keys($missing_values)) . "')");
            $missing_vrednosti = array();
            while ($row = mysqli_fetch_assoc($qry)) {
                $missing_vrednosti[] = $row[id];
            }
            # pogledamo ali imamo kak zapis v srv_data_vrednost. potem je to najbrž missing

            $srv_data_vrednost = array();
            $sql2_c = sisplet_query("SELECT vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            while ($row2_c = mysqli_fetch_array($sql2_c)) {
                #samo če je bil izbran missing -99 ne vem, -98 drugo .....
                if (in_array($row2_c['vre_id'], $missing_vrednosti) && $missing == false) {
                    $missing = $row2_c['vre_id'];
                }
            }
        }
        return $missing;
    }

    // Preverimo, ce je uporabnik ze bil na strani
    public function check_subsequent_visit(){

        $sql = sisplet_query("SELECT g.id 
                                FROM srv_user_grupa".get('db_table')." u, srv_grupa g 
                                WHERE u.gru_id=g.id AND u.usr_id='".get('usr_id')."' AND g.id='".get('grupa')."'
                            ");

        if(mysqli_num_rows($sql) > 0)
            return true;
        else
            return false;
    }
}