<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 01.03.2016
 *****************************************/

namespace App\Controllers;

use Cache;

class FindController extends Controller
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

        return new FindController();
    }


    /**
     * poisce, ce ima podani element parenta, ki je loop
     *
     */
    public static function find_parent_loop($element_spr, $element_if = 0)
    {
        $anketa = get('anketa');
        $sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='$anketa'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        if ($row['parent'] == 0) return 0;

        $sql = sisplet_query("SELECT id FROM srv_if WHERE id = '$row[parent]' AND tip = '2'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        if (mysqli_num_rows($sql) > 0)
            return $row['parent'];
        else
            return self::find_parent_loop(0, $row['parent']);

    }

    /**
     * poisce naslednjo vre_id v loopu
     *
     */
    public function findNextLoopId($if_id = 0)
    {

        if ($if_id == 0) {
            $sql = sisplet_query("SELECT if_id FROM srv_loop_data WHERE id='" . get('loop_id') . "'");
            $row = mysqli_fetch_array($sql);
            $if_id = $row['if_id'];
            $loop_id = get('loop_id');
        } 
        else
            $loop_id = 0;

        $sql = sisplet_query("SELECT spr_id, max FROM srv_loop WHERE if_id = '$if_id'");
        $row = mysqli_fetch_array($sql);
        $spr_id = $row['spr_id'];
        $max = $row['max'];

        $spr = Cache::srv_spremenljivka($spr_id);

        // Radio, checkbox, sn imena
        if ($spr['tip'] == 2 || $spr['tip'] == 3 || $spr['tip'] == 9) {

            $data_vrednost = array();
            if ($spr['tip'] == 9)
                $sql1 = sisplet_query("SELECT vre_id FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spr_id' AND usr_id='" . get('usr_id') . "'");
            else
                $sql1 = sisplet_query("SELECT vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spr_id' AND usr_id='" . get('usr_id') . "'");

            while ($row1 = mysqli_fetch_array($sql1)) {
                $data_vrednost[$row1['vre_id']] = 1;
            }

            $vre_id = '';
            $i = 1;
            $sql = sisplet_query("SELECT lv.vre_id, lv.tip FROM srv_loop_vre lv, srv_vrednost v WHERE lv.if_id='$if_id' AND lv.vre_id=v.id ORDER BY v.vrstni_red ASC");
            while ($row = mysqli_fetch_array($sql)) {

                if ($row['tip'] == 0) {            // izbran
                    if (isset($data_vrednost[$row['vre_id']])) {
                        $vre_id .= ', ' . $row['vre_id'];
                        $i++;
                    }
                } elseif ($row['tip'] == 1) {    // ni izbran
                    if (!isset($data_vrednost[$row['vre_id']])) {
                        $vre_id .= ', ' . $row['vre_id'];
                        $i++;
                    }
                } elseif ($row['tip'] == 2) {    // vedno
                    $vre_id .= ', ' . $row['vre_id'];
                    $i++;
                }                                // nikoli nimamo sploh v bazi, zato ni potrebno nic, ker se nikoli ne prikaze

                if ($i > $max && $max > 0) break;
            }

            $vre_id = substr($vre_id, 2);
            if ($vre_id == '') 
                return null;

            $sql = sisplet_query("SELECT l.id FROM srv_loop_data l, srv_vrednost v WHERE l.if_id='$if_id' AND l.id > '$loop_id' AND l.vre_id IN ($vre_id) AND l.vre_id=v.id ORDER BY l.id ASC");


            if (!$sql) {
                echo 'err56545' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
            $row = mysqli_fetch_array($sql);

            if (mysqli_num_rows($sql) > 0)
                return $row['id'];
            else
                return null;            
        } 
        // Number
        elseif ($spr['tip'] == 7) {

            $sql1 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spr_id' AND usr_id='" . get('usr_id') . "'");
            $row1 = mysqli_fetch_array($sql1);

            $num = (int)$row1['text'];
            $sql2 = sisplet_query("SELECT id FROM srv_loop_data WHERE if_id='$if_id' AND id <= '$loop_id'");
            if (mysqli_num_rows($sql2) >= $num || (mysqli_num_rows($sql2) >= $max && $max > 0))
                return null;

            $sql = sisplet_query("SELECT id FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id'");
            $row = mysqli_fetch_array($sql);

            if (mysqli_num_rows($sql) > 0)
                return $row['id'];
            else
                return null;

        }
        // Ranking
        elseif ($spr['tip'] == 17) {
            
            $data_vrednost = array();
            $sql1 = sisplet_query("SELECT vre_id FROM srv_data_rating WHERE spr_id='$spr_id' AND usr_id='" . get('usr_id') . "' ORDER BY vrstni_red ASC");

            while ($row1 = mysqli_fetch_array($sql1)) {
                $data_vrednost[$row1['vre_id']] = 1;
            }

            $vre_id = '';
            $i = 1;
            $sql = sisplet_query("SELECT lv.vre_id, lv.tip FROM srv_loop_vre lv, srv_vrednost v WHERE lv.if_id='$if_id' AND lv.vre_id=v.id ORDER BY v.vrstni_red ASC");
            while ($row = mysqli_fetch_array($sql)) {

                if ($row['tip'] == 0) {            // izbran
                    if (isset($data_vrednost[$row['vre_id']])) {
                        $vre_id .= ', ' . $row['vre_id'];
                        $i++;
                    }
                } elseif ($row['tip'] == 1) {    // ni izbran
                    if (!isset($data_vrednost[$row['vre_id']])) {
                        $vre_id .= ', ' . $row['vre_id'];
                        $i++;
                    }
                } elseif ($row['tip'] == 2) {    // vedno
                    $vre_id .= ', ' . $row['vre_id'];
                    $i++;
                }                                // nikoli nimamo sploh v bazi, zato ni potrebno nic, ker se nikoli ne prikaze

                if ($i > $max && $max > 0) break;
            }

            $vre_id = substr($vre_id, 2);
            if ($vre_id == '') 
                return null;


            // Ce gre za prvi loop poiscemo ranking odgovor na prvem mestu
            if($loop_id == 0){
                $sql = sisplet_query("SELECT l.id FROM srv_loop_data l, srv_data_rating dr
                                        WHERE l.if_id='$if_id' AND l.id > '$loop_id' AND l.vre_id IN ($vre_id) 
                                            AND l.vre_id=dr.vre_id AND dr.usr_id='".get('usr_id')."' AND dr.vrstni_red = '1'
                                    ");
            }
            // Ce gre za kasnejsi loop poiscemo naslednji ranking odgovor
            else{
                $sql2 = sisplet_query("SELECT dr.vrstni_red FROM srv_loop_data l, srv_data_rating dr WHERE l.if_id='$if_id' AND l.id='$loop_id' AND dr.vre_id=l.vre_id AND dr.usr_id='".get('usr_id')."'");
                if(mysqli_num_rows($sql2) > 0){
                    $row2 = mysqli_fetch_array($sql2);

                    $sql = sisplet_query("SELECT l.id FROM srv_loop_data l, srv_vrednost v, srv_data_rating dr
                                            WHERE l.if_id='$if_id' AND l.id != '$loop_id' AND l.vre_id IN ($vre_id) 
                                                AND l.vre_id=v.id 
                                                AND dr.vre_id=v.id AND dr.usr_id='".get('usr_id')."' AND dr.vrstni_red > '".$row2['vrstni_red']."'
                                            ORDER BY dr.vrstni_red ASC
                                        ");
                }
                else
                    return null;  
            }

            if (!$sql) {
                echo 'err56545' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
            $row = mysqli_fetch_array($sql);

            
            if (mysqli_num_rows($sql) > 0)
                return $row['id'];
            else
                return null;  
        } 
    }

    /**
     * @desc poisce prejsnjo stran - grupo, 0 pomeni konec
     */
    public static function findPrevGrupa()
    {
        $anketa = get('anketa');
        $grupa = get('grupa');

        $sql = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = '$grupa'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['vrstni_red'];

        $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$anketa' AND vrstni_red<'$vrstni_red' ORDER BY vrstni_red DESC LIMIT 1");

        // naslednja stran
        if (mysqli_num_rows($sql) > 0) {

            $row = mysqli_fetch_array($sql);
            return $row['id'];

            // konec
        } else {

            return 0;
        }
    }

    /**
     * @desc poisce naslednjo stran - grupo, 0 pomeni konec
     */
    public function findNextGrupa($gru_id=0)
    {
        //vrstni red trenutne grupe
        if (get('grupa') > 0) {
            $sql = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = '" . get('grupa') . "'");
            $row = mysqli_fetch_array($sql);
            $vrstni_red = $row['vrstni_red'];
        } 
        elseif ($gru_id > 0) {
            $sql = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = '" . $gru_id . "'");
            $row = mysqli_fetch_array($sql);
            $vrstni_red = $row['vrstni_red'];
        } 
        else {
            $vrstni_red = 0;
        }

        $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='" . get('anketa') . "' AND vrstni_red>'$vrstni_red' ORDER BY vrstni_red ASC LIMIT 1");

        // naslednja stran
        if (mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_array($sql);
            return $row['id'];
        } 
        // konec
        else {
            return 0;
        }
    }

}