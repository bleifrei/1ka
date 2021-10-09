<?php
/***************************************
 * Description: Datum
 *
 * Vprašanje je prisotno:
 *  tip 8
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use enkaParameters;

// Iz admin/survey


// Vprašanja

class DatumController extends Controller
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

        return new DatumController();
    }

    public function display($spremenljivka, $oblika)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);


        # pogledamo ali imamo kak zapis v srv_data_vrednost. potem je to najbrž missing
        $is_missing = false;
        $srv_data_vrednost = array();
        # če je bilo vprašanje preskočeno se je vs srv_data_vrednost zapisalo -2, če se potem uporabnik vrne, in spremeni pogojno vprašanje
        # se potem datum ni prikazoval. ke je bilo v bazi -2, zato sem dal da naj poišče samo če vrednost ni -2
        $sql2_c = sisplet_query("SELECT vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id AND vre_id NOT IN ('-1','-2')");
        while ($row2_c = mysqli_fetch_assoc($sql2_c)) {
            $srv_data_vrednost[$row2_c['vre_id']] = true;
            $is_missing = true;
        }
        echo '<div class="variabla' . $oblika['cssFloat'] . '">' . "\n";
        $sql1 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        $row1 = mysqli_fetch_array($sql1);

        echo '<input type="text" id="vrednost_' . $spremenljivka . '" name="vrednost_' . $spremenljivka . '" value="' . $row1['text'] . '"
		 onkeyup="checkBranching();" ' . ($is_missing ? ' disabled' : '') . ' readonly="true"> ';

        echo '</div>' . "\n";

        $array_others = array();
        $sql_other = sisplet_query("SELECT id,naslov FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red > 0 AND other != '0' ORDER BY vrstni_red");
        while ($other = mysqli_fetch_array($sql_other)) {
            # imamo polje drugo - ne vem, zavrnil...
            $_id = 'missing_value_spremenljivka_' . $spremenljivka . '_vrednost_' . $other['id'];

            if ($srv_data_vrednost[$other['id']]) {
                $sel = true;
            } else {
                $sel = false;
            }
            # če nimamo missingov in je trenutni enak izbranemu, ali če imamo misinge inje trenutni enak izbranemu misingu
            $_checked = ($sel ? ' checked' : '');


            // Ali skrivamo missing ne vem in ga prikazemo sele ob opozorilu
            $hide_missing = false;

            $already_set_mv = array();
            $sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_vrednost WHERE spr_id='" . $spremenljivka . "' AND other != 0");
            while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
                $already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
            }

            if ((($row['alert_show_99'] > 0 && isset($already_set_mv['-99']) && $already_set_mv['-99'] == $other['naslov'])
                    || ($row['alert_show_98'] > 0 && isset($already_set_mv['-98']) && $already_set_mv['-98'] == $other['naslov'])
                    || ($row['alert_show_97'] > 0 && isset($already_set_mv['-97']) && $already_set_mv['-97'] == $other['naslov']))
                && $_checked == ''
            )
                $hide_missing = true;

            $naslov = Language::getInstance()->srv_language_vrednost($other['id']);
            if ($naslov != '') $other['naslov'] = $naslov;

            echo '<div class="variabla' . $oblika['cssFloat'] . ' missing"  id="vrednost_if_' . $other['id'] . '"' . ' ' . ($hide_missing ? ' style="display:none"' : '') . '>';
            echo '<label for="' . $_id . '">';
            echo '<input type="checkbox" name="vrednost_mv_' . $spremenljivka . '[]" id="' . $_id . '" value="' . $other['id'] . '"' . $_checked . ' onclick="checkBranching(); checkMissing(this);"> ';
			// Font awesome checkbox
			echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';
			echo '' . $other['naslov'] . '</label>';
            echo '</div>';
        }


        # če smo v quick_view mode ne omogočamo
        if (get('quick_view') == false) {
            $date_element = "#vrednost_" . $spremenljivka;
			
			// Ce izbiramo tudi cas - V DELU
			$timepicker = ($spremenljivkaParams->get('date_withTime') > 0) ? 'true' : 'false';

            ?>
            <script type="text/javascript">
                $(document).ready(function () {
                    datepicker("<?=$date_element?>", <?=($_GET['a'] != 'preview_spremenljivka' ? 'true' : 'false')?>, <?=$timepicker?>);


                    <?php
                    # dodamo date range
                    echo Helper::getDatepickerRange($spremenljivka, $date_element);

                    echo '$( "' . $date_element . '" ).datepicker( "option", "closeText", \'' . self::$lang['srv_clear'] . '\');';
                    echo '$( "' . $date_element . '" ).datepicker( "option", "showOn", \'button\');';
                    echo '$( "' . $date_element . '" ).datepicker( "option", "showButtonPanel", true);';

                    // Gumb pocisti vrednost na dnu
                    echo '$("' . $date_element . '").datepicker( "option", {
						beforeShow: function( input ) {
							setTimeout(function() {
							var clearButton = $(input )
								.datepicker( "widget" )
								.find( ".ui-datepicker-close" );
							clearButton.unbind("click").bind("click",function(){$.datepicker._clearDate( input );});
							}, 1 );
						}
					});';

					// Moznost, da so disablani specificni datumi - V DELU
					if(false){
						
						$disabled_dates = array('01-01-2017', '03-01-2017');
						
						// Ce imamo kaksen datum nastavljen
						if(!empty($disabled_dates)){
							$disabled_dates_string = implode('","', $disabled_dates);
							$disabled_dates_string = '"'.$disabled_dates_string.'"';

							echo '$("' . $date_element . '").datepicker("option", "beforeShowDay", DisableSpecificDates);';
							
							echo 'function DisableSpecificDates(date) {
										var disableddates = ['.$disabled_dates_string.'];
										
										var string = jQuery.datepicker.formatDate(\'dd-mm-yy\', date);
										return [disableddates.indexOf(string) == -1];
									}';
						}
					}
					
                    // TODO zakaj je tole? - $condition manjka in itak ne dela
                    # mogoče za missinge pr datumu ??
                    /*echo
                        '$("input#text_' . $condition . '").bind("keyup", {}, function(e) {' .
                        '  checkBranchingDate(); $(\'#vrednost_' . $spremenljivka . '\').trigger(\'change\'); return false;  ' .
                        '});';*/
                    ?>
                });

            </script>
            <?php

        }
    }
}