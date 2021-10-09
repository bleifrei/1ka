<?php
/***************************************
 * Description: Vsota
 *
 * Vprašanje je prisotno:
 *  tip 18
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use enkaParameters;

class VsotaController extends Controller
{

    public function __construct()
    {
        parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {

        if (self::$_instance)
            return self::$_instance;

        return new VsotaController();
    }

    public function display($spremenljivka, $oblika)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);


        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
        $sql1 = sisplet_query("SELECT id, naslov, if_id FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");

        $max = mysqli_num_rows($sql1);
        $counter = 0;
        $sum = 0;

        $spremenljivkaParams = new enkaParameters($row['params']);
        $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);

        while ($row1 = mysqli_fetch_array($sql1)) {

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            $row2 = mysqli_fetch_array($sql2);

            //preverjanje skritega vprasanja(-2)
            $row2['text'] != -2 ? $text = $row2['text'] : $text = '';


            echo '<div class="variabla_sum width_' . $gridWidth . '" id="vrednost_if_' . $row1['id'] . '" ' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . '>';
            //echo '		<label for="spremenljivka_' . $spremenljivka . '_sestevanec_' . $counter . '">' . $row1['naslov'] . '</label>';
            //echo '		<label style="width:200px;display: inline-block;" for="spremenljivka_' . $spremenljivka . '_sestevanec_' . $counter . '">' . $row1['naslov'] . '</label>';
            echo '		<label class="vsota_besedilo" for="spremenljivka_' . $spremenljivka . '_sestevanec_' . $counter . '">' . $row1['naslov'] . '</label>';
            echo '		<input type="text" name="spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_sestevanec_' . $counter . '" value="' . $text . '" onkeypress="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . ');" onkeyup="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . '); calcSum(' . $spremenljivka . ', ' . $max . ', ' . $row['vsota_limit'] . ');" onBlur="checkBranching();" ' . ($selected ? ' disabled' : '') . '>';

            echo '</div>' . "\n";
            $counter++;
            $sum += (double)$text;
        }

        if ($row['vsota_limit'] != 0 && $row['vsota_limit'] == $row['vsota_min'])
            $limit = '(' . $row['vsota_min'] . ')';
        elseif ($row['vsota_limit'] != 0 && $row['vsota_min'] != 0)
            $limit = '(min ' . $row['vsota_min'] . ', max ' . $row['vsota_limit'] . ')';
        elseif ($row['vsota_limit'] != 0)
            $limit = '(max ' . $row['vsota_limit'] . ')';
        elseif ($row['vsota_min'] != 0)
            $limit = '(min ' . $row['vsota_min'] . ')';

        $rowl = Language::srv_language_spremenljivka($spremenljivka);
        if ($rowl['vsota'] != '') $row['vsota'] = $rowl['vsota'];
        if ($row['vsota'] == '') $row['vsota'] = self::$lang['srv_vsota_text'];

        echo '<div class="variabla_sum width_' . $gridWidth . ' sum" id="vsota_' . $row['id'] . '">';
        //echo '		<label for="spremenljivka_' . $spremenljivka . '_vsota">' . $row['vsota'] . '</label>';
        //echo '		<label style="width:200px;display: inline-block;" for="spremenljivka_' . $spremenljivka . '_vsota">' . $row['vsota'] . '</label>';
        echo '		<label class="vsota_besedilo" for="spremenljivka_' . $spremenljivka . '_vsota">' . $row['vsota'] . '</label>';
        echo '		<input type="text" name="spremenljivka_' . $spremenljivka . '_vsota_' . $row['id'] . '" id="spremenljivka_' . $spremenljivka . '_vsota" value="' . $sum . '" disabled class="def">';
        echo '</div>' . "\n";

        if ($row['vsota_show'] == 1)
            echo ' 		<label class="limit_vsota">' . $limit . '</label>';

    }
}