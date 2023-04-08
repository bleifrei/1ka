<?php
/***************************************
 * Description: Quota
 *
 * Vprašanje je prisotno:
 *  tip 25
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

use App\Controllers\CheckController as Check;
use App\Controllers\Controller;
use App\Models\Model;


class QuotaController extends Controller
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

        return new QuotaController();
    }

    public function display($spremenljivka)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Izračunamo kvoto
        $quota = Check::getInstance()->checkQuota(-$spremenljivka);

        $sql1 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        if (mysqli_num_rows($sql1) > 0) {
            $row1 = mysqli_fetch_array($sql1);
            $quota = $row1['text'];
        }

        echo '      <input type="text" name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '" value="' . $quota . '">';
    }

}