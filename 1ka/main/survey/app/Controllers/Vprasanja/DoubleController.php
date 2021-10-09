<?php
/***************************************
 * Description: Dobule grid in double checkbox
 *
 * Vprašanje je prisotno:
 *  tip 6 - enota 3
 *  tip 16 - enota 3
 *
 * Izris dvojnega multigrida/multicheckboxa
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


class DoubleController extends Controller
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

        return new DoubleController();
    }

    /**
     * @desc prikaze vnosno polje za doublegrid
     */
    public function grid($spremenljivka)
    {
        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loop spremenljivkah, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
        $hideLabels = false;
        $gridWidth = $spremenljivkaParams->get('gridWidth');
        switch ($gridWidth) {
            case -2:
                $hideLabels = true;
                $gridWidth = 30;    // just in case če se kje kaj računa, itak je skrit.
                break;
            case -1:
            case 0:
                $gridWidth = 30;
                break;
        }
         //$css = ' style = "width: '.$gridWidth.'%;" ';

        // izracuni za sirino celic
        $size = 2 * $row['grids'];
        $colspan = $row['grids'];

        # polovimo vrednosti gridov, prevedmo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
        $mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
        # če polje other != 0 je grid kot missing
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part='1' ORDER BY vrstni_red");

        $space = false;
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
                $colspan++;

                if (!$space) {
                    $colspan++;
                    $space = true;
                }
            }

        }

        //se za desni del grida
        $sql_grid2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part='2' ORDER BY vrstni_red");
		
		$indexLanguage = 1;
        while ($row_grid2 = mysqli_fetch_assoc($sql_grid2)) {
            # priredimo naslov če prevajamo anketo
            //$naslov = Language::srv_language_grid($row['id'], $row_grid2['id']);
            $naslov = Language::srv_language_grid($row['id'], $indexLanguage);
            if ($naslov != '') {
                $row_grid2['naslov'] = $naslov;
            }
            $srv_grids2[$row_grid2['id']] = $row_grid2;
            # če je označena kot manjkajoča jo prištejemo k manjkajočim
            if ($row_grid2['other'] != 0) {
                $mv_count++;
            }
			
			$indexLanguage++;
        }

        # če imamo mankajoče potem dodamo še en prazen stolpec za razmak
        if ($mv_count > 0) {
            $size += 1 + $mv_count;
        }

        # če imamo nastavljno sirino prvega grida ostalih ne nastavljamo
        if ($gridWidth == 30) {
            $cellsize = round(80 / $size);
        } else {
            $cellsize = 'auto';
        }

        $spacesize = round(80 / $size / 4);

        $bg = 1;

        echo '<table class="grid_table doublegrid">' . "\n";

        if ($hideLabels == false) {
            echo '<colgroup class="question">';
            echo '<col class="width_' . $gridWidth . '">';
            echo '</colgroup>';
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
        }
        
        echo '<colgroup class="category">';
        for ($i = 1; $i <= $row['grids']; $i++)
            echo '<col>';
        echo '</colgroup>';
        
        if ($mv_count > 0) {
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
            echo '<colgroup class="missing">';
            for ($i = 1; $i <= $mv_count / 2; $i++)
                echo '<col>';
            echo '</colgroup>';
        }

        echo '<colgroup>';
        echo '<col class="space">';
        echo '<col class="space">';
        echo '</colgroup>';

        echo '<colgroup class="category">';
        for ($i = 1; $i <= $row['grids']; $i++)
            echo '<col>';
        echo '</colgroup>';
        if ($mv_count > 0) {
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
            echo '<colgroup class="missing">';
            for ($i = 1; $i <= $mv_count / 2; $i++)
                echo '<col>';
            echo '</colgroup>';
        }

        echo '<thead>';
        
		//izpis podnaslovov posameznih delov grida
		# priredimo podnaslovov če je prevod ankete
		$podnaslov1 = Language::srv_language_grid_podnaslov($row['id'], 1);
		$podnaslov2 = Language::srv_language_grid_podnaslov($row['id'], 2);
		if ($podnaslov1 != '') {				
			$row['grid_subtitle1'] = $podnaslov1;
		}
		if ($podnaslov2 != '') {				
			$row['grid_subtitle2'] = $podnaslov2;
		}
		# priredimo podnaslovov če je prevod ankete - konec
		
        echo '	<tr>' . "\n\r";
        if ($hideLabels == false) {
            echo '		<td></td>' . "\n";
            echo '		<td></td>' . "\n";
        }
        echo '		<td colspan="' . $colspan . '">' . $row['grid_subtitle1'] . '</td>' . "\n";
        echo '		<td></td>';
        echo '		<td></td>';
        echo '		<td colspan="' . $colspan . '">' . $row['grid_subtitle2'] . '</td>' . "\n";
        echo '	</tr>' . "\n\r";

        echo '	<tr>' . "\n\r";
        if ($hideLabels == false) {
            echo '		<td></td>' . "\n";
            echo '		<td></td>' . "\n";
        }
        
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
                echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . '">' . $srv_grid['naslov'] . '</td>' . "\n";
            }
        }

        #double grid space        
        echo '<td></td>';
        echo '<td class="double"></td>';

        //se za desni del grida
        if (count($srv_grids2) > 0) {
            $first_missing_value = true;
            foreach ($srv_grids2 AS $i => $srv_grid) {
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                    # dodamo spejs pred manjkajočimi vrednostmi
                    echo '		<td></td>' . "\n";
                    $first_missing_value = false;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                # izpišemo labelo grida
                echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . '">' . $srv_grid['naslov'] . '</td>' . "\n";
            }
        }
        echo '	</tr>' . "\n";

        echo '</thead>';

        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        # cache tabele srv_data_checkgrid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja) - za part 1
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT d.* FROM srv_data_checkgrid" . get('db_table') . " d, srv_grid g WHERE d.spr_id='$row[id]' AND d.usr_id='" . get('usr_id') . "' AND d.loop_id $loop_id AND d.grd_id=g.id AND g.part='1' AND g.spr_id='$row[id]'");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }
        # cache tabele srv_data_checkgrid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja) - za part 2
        $srv_data_grid2 = array();
        $sql_grid = sisplet_query("SELECT d.* FROM srv_data_checkgrid" . get('db_table') . " d, srv_grid g WHERE d.spr_id='$row[id]' AND d.usr_id='" . get('usr_id') . "' AND d.loop_id $loop_id AND d.grd_id=g.id AND g.part='2' AND g.spr_id='$row[id]'");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid2[$row_grid['vre_id']] = $row_grid;
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
            $grid_id2 = $srv_data_grid2[$row1['id']]['grd_id'];

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

            echo '	<tr id="vrednost_if_' . $row1['id'] . '" ' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '>' . "\n";
            if ($hideLabels == false) {
                echo '		<td class="question">';
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

                    //echo ' <input type="text" name="textfield_'.$row1['id'].'" value="'.($is_missing ? '' : $row3['text']).'" '.($is_missing ? ' disabled' :'').'>';
                }
                echo '</td>' . "\n";
                echo '<td></td>' . "\n";
            }


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
                        # imamo missing vrednost
                        echo '<td class="missing">';
                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'6-3-1\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    } else {
                        # ni missing vrednost
                        echo '<td class="category">';
                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'6-3-1\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    }
                }
            }

            #double grid space
            echo '<td></td>';
            echo '<td class="double"></td>';


            //DESNI DEL GRIDA
            # ugotovimo ali je na katerem gridu predhodno izbran missing
            $is_missing = false;
            if (count($srv_grids2) > 0) {
                foreach ($srv_grids2 AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0 && $srv_grids2[$i]['id'] == $grid_id2) {
                        $is_missing = true;
                    }
                }
            }
            if (count($srv_grids2) > 0) {
                $first_missing_value = true;
                foreach ($srv_grids2 AS $i => $srv_grid) {
                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '		<td></td>' . "\n";
                        $first_missing_value = false;
                    }

                    $value = $srv_grids2[$i]['id'];
                    # izpišemo radio grida

                    if ($srv_grid['other'] != 0) {
                        # imamo missing vrednost
                        echo '<td class="missing">';
                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '_part_2">';
                        echo '<input type="radio" ' . (!$hideName ? '  name="vrednost_' . $row1['id'] . '_part_2"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '_part_2" value="' . $value . '"' . (($grid_id2 == $value && $grid_id2 != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'6-3-2\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    } else {
                        # ni missing vrednost
                        echo '<td class="category">';
                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '_part_2">';
                        echo '<input type="radio" ' . (!$hideName ? '  name="vrednost_' . $row1['id'] . '_part_2"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '_part_2" value="' . $value . '"' . (($grid_id2 == $value && $grid_id2 != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids2[$i]['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'6-3-2\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    }
                }
            }

            echo '	</tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '</table>' . "\n";
    }

    /**
     * @desc prikaze vnosno polje za doublecheckgrid - TODO!!
     */
    public function checkGrid($spremenljivka)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // izracuni za sirino celic
        $size = 2 * $row['grids'];
        $colspan = $row['grids'];

        $spremenljivkaParams = new enkaParameters($row['params']);
        $hideLabels == false;
        $gridWidth = $spremenljivkaParams->get('gridWidth');
        switch ($gridWidth) {
            case -2:
                $hideLabels = true;
                $gridWidth = 30;    // just in case če se kje kaj računa, itak je skrit.
                break;
            case -1:
            case 0:
                $gridWidth = 30;
                break;
        }
         
        $css = ' style = "width: ' . $gridWidth . '%;" ';

        # polovimo vrednosti gridov, prevedmo naslove in hkrati preverimo ali imamo missinge
        $srv_grids1 = array();
        $srv_grids2 = array();
        $mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
        # če polje other != 0 je grid kot missing
        //$sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' ORDER BY part, vrstni_red");
        $sql_grid1 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part = '1' ORDER BY vrstni_red");
		$sql_grid2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part = '2' ORDER BY vrstni_red");
        
		$space = false;
		
		//levi del
        while ($row_grid1 = mysqli_fetch_assoc($sql_grid1)) {
            # priredimo naslov če prevajamo anketo
            $naslov = Language::srv_language_grid($row['id'], $row_grid1['id']);
            if ($naslov != '') {
                $row_grid1['naslov'] = $naslov;
            }
            $srv_grids1[$row_grid1['id']] = $row_grid1;
            # če je označena kot manjkajoča jo prištejemo k manjkajočim
            if ($row_grid1['other'] != 0) {
                $mv_count++;

                if ($row_grid1['part'] == 1)
                    $colspan++;

                if (!$space) {
                    $colspan++;
                    $space = true;
                }
            }
        }
		
		//desni del
		$indexLanguage = 1;
        while ($row_grid2 = mysqli_fetch_assoc($sql_grid2)) {
            # priredimo naslov če prevajamo anketo
            $naslov = Language::srv_language_grid($row['id'], $indexLanguage);
            if ($naslov != '') {
                $row_grid2['naslov'] = $naslov;
            }
            $srv_grids2[$row_grid2['id']] = $row_grid2;
            # če je označena kot manjkajoča jo prištejemo k manjkajočim
            if ($row_grid2['other'] != 0) {
                $mv_count++;

                if ($row_grid2['part'] == 1)
                    $colspan++;

                if (!$space) {
                    $colspan++;
                    $space = true;
                }
            }
			$indexLanguage++;
        }

        echo '      <table class="grid_table doublecheckgrid">' . "\n";        
        if ($hideLabels == false) {
            echo '<colgroup class="question">';
            echo '<col class="width_' . $gridWidth . '">';
            echo '</colgroup>';
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
        }
        echo '<colgroup class="category">';
        for ($i = 1; $i <= $row['grids']; $i++)
            echo '<col>';
        echo '</colgroup>';
        if ($mv_count > 0) {
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
            echo '<colgroup class="missing">';
            for ($i = 1; $i <= $mv_count / 2; $i++)
                echo '<col>';
            echo '</colgroup>';
        }

        echo '<colgroup>';
        echo '<col class="space">';
        echo '<col class="space">';
        echo '</colgroup>';

        echo '<colgroup class="category">';
        for ($i = 1; $i <= $row['grids']; $i++)
            echo '<col>';
        echo '</colgroup>';
        if ($mv_count > 0) {
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
            echo '<colgroup class="missing">';
            for ($i = 1; $i <= $mv_count / 2; $i++)
                echo '<col>';
            echo '</colgroup>';
        }

        echo '<thead>';

        //ipis podnaslovom posameznih delv grida
        echo '	<tr>' . "\n\r";
        if ($hideLabels == false) {
            echo '		<td></td>' . "\n";
            echo '		<td></td>' . "\n";
        }
        echo '		<td colspan="' . $colspan . '">' . $row['grid_subtitle1'] . '</td>' . "\n";
        echo '		<td></td>';
        echo '		<td></td>';
        echo '		<td colspan="' . $colspan . '">' . $row['grid_subtitle2'] . '</td>' . "\n";
        echo '	</tr>' . "\n\r";

        echo '        <tr>' . "\n";
        if ($hideLabels == false) {
            echo '          <td></td>' . "\n";
            echo '          <td></td>' . "\n";
        }

        # če imamo mankajoče potem dodamo še en prazen stolpec za razmak
        if ($mv_count > 0) {
            $size += 1 + $mv_count;
        }
        # če imamo enoto povečamo št. stoplcev
        if ($row['enota'] == 1) {
            $size += 2;
        }

        //ce imamo nastavljno sirino prvega grida ostalih ne nastavljamo
        if ($gridWidth == 30)
            $cellsize = round(80 / $size);
        else
            $cellsize = 'auto';

        $spacesize = round(80 / $size / 4);

        $taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
        $taHeight = ($spremenljivkaParams->get('taHeight') ? $spremenljivkaParams->get('taHeight') : 1);
        //default sirina
        if ($taWidth == -1)
            //$taWidth = 10;
            $taWidth = round(50 / $size);

        $bg = 1;

        # Izpišemo TOP vrstico z labelami
        if (count($srv_grids1) > 0) {
            $first_missing_value = true;
            $count = 1;
            foreach ($srv_grids1 AS $g_id => $srv_grid) {
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                    # dodamo spejs pred manjkajočimi vrednostmi
                    echo '<td></td>';
                    $first_missing_value = false;

                    $count++;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                # izpišemo labelo grida
                echo '<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . '">' . $srv_grid['naslov'] . '</td>' . "\n";

                //vmesno polje z mejo
                if ($count == $colspan) {
                    echo '<td></td><td class="double"></td>';
                    $first_missing_value = true;
                }
                $count++;
            }
        }
		
		if (count($srv_grids2) > 0) {
            $first_missing_value = true;
            $count = 1;
            foreach ($srv_grids2 AS $g_id => $srv_grid) {
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                    # dodamo spejs pred manjkajočimi vrednostmi
                    echo '<td></td>';
                    $first_missing_value = false;

                    $count++;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                # izpišemo labelo grida
                echo '<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . '">' . $srv_grid['naslov'] . '</td>' . "\n";				
                $count++;
            }
        }
		
		
		
        echo '        </tr>' . "\n";

        echo '</thead>';

        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        $srv_data_cache = array();

        $sql2 = sisplet_query("SELECT * FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_cache[$row2['vre_id']][$row2['grd_id']] = $row2;
        }

        $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            # preverimo izbrano vrednost
            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            # ugotovimo ali je na katerem gridu predhodno izbran missing
            $is_missing = false;
            if (count($srv_grids1) > 0) {
                foreach ($srv_grids1 AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0) {
                        $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];
                        if ($srv_grids1[$i]['id'] == $grid_id) {
                            $is_missing = true;
                        }
                    }
                }
            }
			
			if (count($srv_grids2) > 0) {
                foreach ($srv_grids2 AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0) {
                        $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];
                        if ($srv_grids2[$i]['id'] == $grid_id) {
                            $is_missing = true;
                        }
                    }
                }
            }

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo '        <tr id="vrednost_if_' . $row1['id'] . '" ' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '>' . "\n";

            if ($hideLabels == false) {
                echo '          <td class="question">' . $row1['naslov'];
                if ($row1['other'] == 1) {
                    $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                    $row3 = mysqli_fetch_array($sql3);

                    $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                    $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                    if ($otherHeight > 1)
                        echo ' <textarea name="textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($is_missing ? ' disabled' : '') . '>' . ($is_missing ? '' : $row3['text']) . '</textarea>';
                    else
                        echo ' <input type="text" name="textfield_' . $row1['id'] . '" value="' . ($is_missing ? '' : $row3['text']) . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($is_missing ? ' disabled' : '') . ' />';

                    //echo '   <input type="text" name="textfield_'.$row1['id'].'" value="'.($is_missing ? '' : $row3['text']).'" '.($is_missing ? ' disabled' : '').'>';
                }
                echo '          </td>' . "\n";
                echo '          <td></td>' . "\n";
            }

            if (count($srv_grids1) > 0) {
                $first_missing_value = true;
                $count = 1;
                foreach ($srv_grids1 AS $i => $srv_grid) {

                    $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];

                    $value = $srv_grid['id'];

                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '<td></td>';
                        $first_missing_value = false;

                        $count++;
                    }
                    # izpišemo labelo grida
                    if ($srv_grid['other'] != 0) {
                        # imamo missing polje
                        echo '<td class="missing">';
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkBranching();">';
                        
						// Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';
						
						echo '</label>';
                        echo '</td>' . "\n";
                    } else {
                        echo '<td class="category">';
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grid['variable'] . '" onclick="checkChecked(this); checkBranching();">';
                        
						// Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';
						
						echo '</label>';
                        echo '</td>' . "\n";
                    }

                    //vmesno polje z mejo
                    if ($count == $colspan) {
                        echo '<td></td><td class="double"></td>';
                        $first_missing_value = true;
                    }
                    $count++;
                }
            }
			
            if (count($srv_grids2) > 0) {
                $first_missing_value = true;
                $count = 1;
                foreach ($srv_grids2 AS $i => $srv_grid) {

                    $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];

                    $value = $srv_grid['id'];

                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '<td></td>';
                        $first_missing_value = false;

                        $count++;
                    }
                    # izpišemo labelo grida
                    if ($srv_grid['other'] != 0) {
                        # imamo missing polje
                        echo '<td class="missing">';
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkBranching();">';
                        
						// Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';
						
						echo '</label>';
                        echo '</td>' . "\n";
                    } else {
                        echo '<td class="category">';
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grid['variable'] . '" onclick="checkChecked(this); checkBranching();">';
                        
						// Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';
						
						echo '</label>';
                        echo '</td>' . "\n";
                    }
                    $count++;
                }
            }

            echo '        </tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '      </table>' . "\n";
    }

}