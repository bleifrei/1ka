<?php
/***************************************
 * Description: Multigrid maxdiff
 *
 * Vprašanje je prisotno:
 *  tip 6 - enota 5
 *
 * Autor: Robert Šmalc
 * Created date: 01.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;


// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use enkaParameters;

class MaxDiffController extends Controller
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

        return new MaxDiffController();
    }

    /**
     * @desc prikaze vnosno polje za maxdiff
     */
    public function display($spremenljivka)
    {
        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
        
        $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
       
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';

        // izracuni za sirino celic
        $size = $row['grids'];

        # polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
        $mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
        
        # če polje other != 0 je grid kot missing
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' ORDER BY vrstni_red");

        while ($row_grid = mysqli_fetch_assoc($sql_grid)) {
            # priredimo naslov če prevajamo anketo
            $naslov = Language::srv_language_grid($row['id'], $row_grid['id']);
            if ($naslov != '') {
                $row_grid['naslov'] = $naslov;
            }
            $srv_grids[$row_grid['id']] = $row_grid;
            # če je označena kot manjkajoča jo prištejemo k manjkajočim
            if ($row_grid['other'] != 0) {
                $mv_count++;
            }

        }

        # če imamo mankajoče potem dodamo še en prazen stolpec za razmak
        if ($mv_count > 0) {
            $size += 1 + $mv_count;
        }
        // diferencial
        $size += 2;

        # če imamo nastavljno sirino prvega grida ostalih ne nastavljamo
        if ($gridWidth == 30) {
            //$cellsize = round(80/$size);
            $cellsize = round(160 / $size);
        } else {
            //$cellsize = 'auto';
            $cellsize = round(160 / $size);
        }

        //$spacesize = round(80/$size/4);
        $spacesize = round(240 / $size / 4);

        $bg = 1;

        echo '<table class="grid_table maxdiff">' . "\n";


        echo '<thead>';
        echo '	<tr>' . "\n";

        # Izpišemo TOP vrstico z labelami
        if (count($srv_grids) > 0) {
            $first_missing_value = true;
            foreach ($srv_grids AS $i => $srv_grid) {
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                    # dodamo spejs pred manjkajočimi vrednostmi
                    echo '		<td></td>' . "\n";
                    $first_missing_value = false;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                # izpišemo labelo grida
                echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
                //echo '		<td></td>'."\n";

                if ($i == 1) {
                    echo '		<td></td>' . "\n";
                }


            }
        }

        echo '	</tr>' . "\n";
        echo '</thead>';

        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        # cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }

        # loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
        $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
            # po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') {
                $row1['naslov'] = $naslov;
            }
            # preverimo izbrano vrednost
            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            # ugotovimo ali je na katerem gridu predhodno izbran missing
            $is_missing = false;
            if (count($srv_grids) > 0) {
                foreach ($srv_grids AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0 && $srv_grids[$i]['id'] == $grid_id) {
                        $is_missing = true;
                    }
                }
            }

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo '	<tr id="vrednost_if_' . $row1['id'] . '" ' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '">' . "\n";


            if (count($srv_grids) > 0) {
                $first_missing_value = true;
                foreach ($srv_grids AS $i => $srv_grid) {
                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '		<td></td>' . "\n";
                        $first_missing_value = false;
                    }

                    $value = $srv_grids[$i]['id'];

                    # izpišemo radio grida
                    if ($srv_grid['other'] != 0) {
                        echo '<td class="missing ' . $cssAlign . '">';
                        # imamo missing vrednost
                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input data-col="' . $i . $row[0] . '" type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                        
                        if ($i == 1) {
                            echo '		<td style="text-align: center;" class="question">';
                            echo $row1['naslov'];
                            if ($row1['other'] == 1) {
                                $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                                $row3 = mysqli_fetch_array($sql3);

                                $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                                $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                                if ($otherHeight > 1)
                                    echo ' <textarea name="textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($is_missing ? ' disabled' : '') . '>' . ($is_missing ? '' : $row3['text']) . '</textarea>';
                                else
                                    echo ' <input type="text" name="textfield_' . $row1['id'] . '" value="' . ($is_missing ? '' : $row3['text']) . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($is_missing ? ' disabled' : '') . ' />';
                            }
                            echo '</td>' . "\n";
                        }


                    } else {
                        echo '<td class="category ' . $cssAlign . '">';
                        # ni missing vrednost
                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input data-col="' . $i . $row[0] . '" type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";

                        //urejanje navpicnega dela grupiranja radio button - vodoravni je urejen po defaultu s pomočjo atributa name
                        echo '
							<script>

							$(document).ready(
								function(){
									var col, elem, ime;
									ime = "vrednost_' . $row1['id'] . '";

                                    $("input[name=" + ime + "]").click(function() {
                                        elem = $(this);
                                        col = elem.data("col");
                                        $("input[data-col=" + col + "]").prop("checked", false);
                                        elem.prop("checked", true);
                                    });
								}
							);
							</script>
						';

                        if ($i == 1) {
                            echo '		<td style="text-align: center;" class="question">';
                            echo $row1['naslov'];
                            if ($row1['other'] == 1) {
                                $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                                $row3 = mysqli_fetch_array($sql3);

                                $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                                $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                                if ($otherHeight > 1)
                                    echo ' <textarea name="textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($is_missing ? ' disabled' : '') . '>' . ($is_missing ? '' : $row3['text']) . '</textarea>';
                                else
                                    echo ' <input type="text" name="textfield_' . $row1['id'] . '" value="' . ($is_missing ? '' : $row3['text']) . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($is_missing ? ' disabled' : '') . ' />';
                            }

                            echo '</td>';
                        }
                    }
                }
            }

            echo '	</tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '</table>' . "\n";
    }

}