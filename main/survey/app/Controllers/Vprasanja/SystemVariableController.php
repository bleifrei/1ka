<?php
/***************************************
 * Description: Special system variables
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\Controller;
use App\Models\Model;

class SystemVariableController extends Controller
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

        return new SystemVariableController();
    }

    /**
     * @desc Prikaže checkboxe za vrednosti 99,98,97
     */
    public static function display($spremenljivka, $oblika)
    {
        $sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND other IN (99,98,97) ORDER BY vrstni_red ");
        $selected = Model::getOtherValue($spremenljivka);
        echo '<input name="other_selected_vrednost_' . $spremenljivka . '" id="other_selected_vrednost_' . $spremenljivka . '" value="' . $selected . '" type="hidden">';
        while ($row1 = mysqli_fetch_array($sql1)) {

            if ($selected == $row1['id'])
                $sel = true;
            else
                $sel = false;

            echo '<div class="variabla' . $oblika['cssFloat'] . '">';
            echo '<label for="missing_value_spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '">';
            echo '<input type="checkbox" name="vrednost_' . $spremenljivka . '[]" id="missing_value_spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '" value="' . $row1['id'] . '"' . ($sel ? ' checked' : '') . ' spremenljivka="' . $spremenljivka . '" onclick=" checkBranching();"> ';
			// Font awesome checkbox
			echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';
			echo '' . $row1['naslov'] . '</label>';
            echo '</div>' . "\n";

        }
    }

}