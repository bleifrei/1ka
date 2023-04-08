<?php
/***************************************
 * Description: SN imena
 *
 * Vprašanje je prisotno:
 *  tip 9
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\Controller;
use App\Models\Model;

// Iz admin/survey
use enkaParameters;
use Common;

// Vprašanja

class ImenaController extends Controller
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

        return new ImenaController();
    }

    public function display($spremenljivka, $oblika)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $selected = Model::getOtherValue($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);

        if ($spremenljivkaParams->get('NG_cancelButton') == '1') {
            $cancelText = $spremenljivkaParams->get('NG_cancelText');
            $cancelButton = 1;
        } else {
            $cancelText = self::$lang['srv_NG_cancelText'];
            $cancelButton = 0;
        }

        // Dodajanje polj za vnos ime z gumbom (+)
        if ($row['design'] == 0 || $_GET['m'] == 'quick_edit') {
            $addText = ($spremenljivkaParams->get('NG_addText') ? $spremenljivkaParams->get('NG_addText') : self::$lang['srv_NG_addText']);

            $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "'");
            $first = true;
            $id = 1;

            // WebSM anketa ima fiksno napolnjena polja ker gre samo za primer in se nic ne shranjuje
            if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1') {

                echo '	<div id="row' . $id . '" class="sn_name"><input type="text" readonly name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $id . '" size="40" value="John">';
                echo '	</div>';

                echo '	<div id="row' . $id . '" class="sn_name"><input type="text" readonly name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $id . '" size="40" value="Sarah">';
                echo '	<img src="' . self::$site_url . 'main/survey/skins/Modern/blue_delete.png" style="cursor:pointer;" border="0">';
                echo '	</div>';

                echo '	<div id="row' . $id . '" class="sn_name"><input type="text" readonly name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $id . '" size="40" value="Kevin">';
                echo '	<img src="' . self::$site_url . 'main/survey/skins/Modern/blue_delete.png" style="cursor:pointer;" border="0">';
                echo '	</div>';

                //gumb za dodajanje polj
                echo '		<div id="divTxt' . $spremenljivka . '">';
                echo '		</div>';
                echo '		<p style="cursor:pointer;"><img src="' . self::$site_url . 'main/survey/skins/Modern/add.png" border="0"> ' . $addText . '</p>';
            } else {

                while ($row2 = mysqli_fetch_array($sql2)) {
                    echo '	<div id="row' . $id . '" class="sn_name"><input type="text" name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $id . '" size="40" value="' . $row2['text'] . '">';
                    if ($first || $_GET['m'] == 'quick_edit')
                        $first = false;
                    else
                        echo '	<a href="#" onClick="removeFormField(\'#row' . $id . '\'); return false;"><span class="faicon delete"></span></a>';

                    echo '	</div>';

                    $id++;
                }

                if ($_GET['m'] != 'quick_edit') {

                    //vedno imamo eno prazno vnosno polje
                    echo '      <div id="row' . $id . '" class="sn_name"><input type="text" value="" name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $id . '" size="40" onblur="checkName(\'' . $spremenljivka . '\', this); checkBranching();">';
                    if ($first)
                        $first = false;
                    else
                        echo '	<a href="#" onClick="removeFormField(\'#row' . $id . '\'); return false;"><span class="faicon delete"></span></a>';
                    echo '	</div>';
                    $id++;

                    // gumb za dodajanje polj
                    echo '		<input type="hidden" id="counter" value="' . $id . '">';

                    echo '		<div id="divTxt' . $spremenljivka . '">';
                    echo '		</div>';

                    echo '		<div class="sn_add_field"><a href="#" onClick="addFormField(' . $spremenljivka . '); return false;"><span class="faicon add"></span> ' . $addText . '</a></div>';
                }
            }
        } // Fiksno stevilo polj za imena
        elseif ($row['design'] == 1) {
            $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "'");
            $first = true;
            $id = 1;

            while ($row2 = mysqli_fetch_array($sql2)) {
                echo '	<div id="row' . $id . '" class="sn_name"><input type="text" name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $id . '" size="40" value="' . $row2['text'] . '"></div>';
                $id++;
            }

            // dodamo prazna vnosna polja
            for ($i = $id; $i <= $row['size']; $i++)
                echo '      <div id="row' . $i . '" class="sn_name"><input type="text" name="spremenljivka_' . $spremenljivka . '[]" id="txt' . $i . '" size="40" onblur="checkName(\'' . $spremenljivka . '\', this); checkBranching();"></div>';
        } // 1 textbox - loceni z entri
        elseif ($row['design'] == 2) {
            $values = '';

            $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "'");
            while ($row2 = mysqli_fetch_array($sql2)) {
                $values .= $row2['text'] . "\n";
            }

            echo '<textarea name="spremenljivka_' . $spremenljivka . '" style="width: 250px; height: 150px;">' . $values . '</textarea>';
        } // Vnos stevila polj
        elseif ($row['design'] == 3) {
            $countText = ($spremenljivkaParams->get('NG_countText') ? $spremenljivkaParams->get('NG_countText') : self::$lang['srv_design_count']);
            echo $countText . ': <input type="text" size="5" name="stImen_' . $spremenljivka . '" id="stImen_' . $spremenljivka . '" value="" onkeypress="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . ');" onkeyup="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . '); edit_size(' . $spremenljivka . ', stImen_' . $spremenljivka . '.value)">' . "\n";

            echo '<div id="imena_' . $spremenljivka . '">';
            echo '</div>';
        }


        //gumb za preskok (ce je vklopljen)
        if ($cancelButton == 1)
            echo '		<br><p><input class="prev" type="button" value="' . $cancelText . '" onclick="submitForm()"></p>';
    }
}