<?php
/***************************************
 * Description: Compute
 *
 * Vprašanje je prisotno:
 *  tip 25
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\JsController as Js;
use App\Models\Model;

// Iz admin/survey


// Vprašanja


class ComputeController extends Controller
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

        return new ComputeController();
    }

    public function display($spremenljivka)
    {
        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $sql1 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        $row1 = mysqli_fetch_array($sql1);
        echo '      <input type="text" name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '" value="' . $row1['text'] . '">';

        Js::getInstance()->generateCompute($spremenljivka);
    }
}