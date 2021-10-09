<?php
/***************************************
 * Description: Multigrid - na mobilnikih
 *
 * Vprašanje je prisotno:
 *  tip 6
 *  tip 16
 *  tip 19
 *  tip 20
 *
 * Autor: Peter Hrvatin
 * Created date: 9.05.2019
 *****************************************/

namespace App\Controllers\Vprasanja;


// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use enkaParameters;
use SurveySetting;


class MultigridMobileController extends Controller{

    protected $spremenljivka;

    public function __construct(){
        parent::getGlobalVariables();
    }

    // Get instance
    private static $_instance;

    public static function getInstance(){
        
        if (self::$_instance)
            return self::$_instance;

        return new MultigridMobileController();
    }


    /**
     * @desc prikaze vnosno polje za tabelo radio
     */
    public function radioMultigrid($spremenljivka){
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
		
		// Nastavitev za prilagoditev mobilnih tabel (z razpiranjem ali brez)
		SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		
       
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';
		
        # polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
		
		# koliko je stolpcev z manjkajočimi vrednostmi
        $mv_count = 0; 
		
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
			
		
		// Izrisemo celotno vsebino tabele za mobietl
		echo '<div class="grid_mobile radio '.($row['enota'] == 11 ? 'visual_scale' : '').' '.($row['enota'] == 12 ? 'smiley_scale' : '').' '.($mobile_tables == 2 ? 'mobile_expanding' : '').'">';
		
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        // Cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }
		
		// Ali skrivamo radio gumbe in checkboxe
		$presetValue = ($spremenljivkaParams->get('presetValue') > 0 && empty($srv_data_grid)) ? $spremenljivkaParams->get('presetValue') : 0;

        // Loop po posameznih vprasanjih (vrsticah)
		$first = true;
        $sql1 = sisplet_query("SELECT id, naslov, naslov2, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
			echo '<div class="grid_mobile_question" id="vrednost_if_'.$row1['id'].'" '.(($row1['hidden'] == 1) ? 'style="display:none"' : '').(($row1['hidden'] == 2) ? ' class="answer-disabled"' : '').'">';

			
			// NASLOV posameznega vprasanja
			echo '<div class="grid_mobile_title">';
            
            echo '  <div class="grid_mobile_title_text">';
            
			// po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '')
                $row1['naslov'] = $naslov;
				
			// Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo $row1['naslov'];
            
            // preverimo izbrano vrednost
            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            // Ugotovimo ali je na katerem gridu predhodno izbran missing oz. pridobimo text. vrednost izpolnjenega odg.
            $is_missing = false;
            $grid_data_value = '';
            if (count($srv_grids) > 0) {
                foreach ($srv_grids AS $i => $srv_grid) {

                    // Ugotovimo ali je na katerem gridu predhodno izbran missing
                    if ($srv_grid['other'] != 0 && $srv_grids[$i]['id'] == $grid_id) {
                        $is_missing = true;
                    }

                    // Pridobimo text. vrednost izpolnjenega odg.
                    if($srv_grids[$i]['id'] == $grid_id){
                        $grid_data_value = $srv_grids[$i]['naslov'];
                    }
                }
            }

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

            echo '  </div>';
			
			// Puscica za razpiranje ce imamo vklopljene mobilne tabele z razpiranjem
			if($mobile_tables == 2)
				echo '<span class="faicon arrow_up mobile_expanding_arrow"></span>';
			
            echo '</div>';


            // IZPOLNJENA VREDNOST, ce obstaja in ce imamo vklopljene mobilne tabele z razpiranjem
            if($mobile_tables == 2){
                echo '<div class="grid_mobile_result">'.($grid_id != '' ? $grid_data_value : '').'</div>';
            }
			
			
			// VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables">';
			
			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids) > 0) {

                $cnt = 0;

				foreach ($srv_grids AS $i => $srv_grid) {
					
                    // izpišemo radio grida
                    // Other oz. missing
                    if ($srv_grid['other'] != 0) {

						$value = $srv_grids[$i]['id'];
                        
						$is_checked = (($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? true : false;
                        
						echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.($is_checked ? ' checked' : '').'">';                     
						
                        echo '<span class="missing ' . $cssAlign . '">';
						
                        # imamo missing vrednost   
                        echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ($is_checked ? ' checked' : '') . ' data-calculation="0" vre_id="' . $row1['id'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
						
                        echo '</span>' . "\n";

                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // Navadna variabla
					else {

                        $value = $srv_grids[$i]['id'];

                        $is_checked = ((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? true : false;

                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.($is_checked ? ' checked' : '').'">';                

			
                        // Vizualna skala
						if($row['enota'] == 11){
                            echo '<span class="category visual-radio-scale visual-radio-table '.$cssAlign.' '.($is_checked ? ' checked' : '').'"><label>';

                            echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="'.$value.'"' . ( $is_checked ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                            echo '<span class="enka-vizualna-skala siv-'.$row['grids'].$value.'"></span>';
                            
                            echo '</label></span>' . "\n";
                        }
                        // Smile-iji
						elseif($row['enota'] == 12){
                            echo '<span class="category custom_radio_picture custom-radio-table '.$cssAlign.' '.($is_checked ? ' obarvan' : '').'"><label>';

                            echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="'.$value.'"' . ( $is_checked ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . '); customRadioTableSelectMobile(' . $row1['id'] . ', ' . $value. ');">';
                            echo '<span class="enka-custom-radio '.$spremenljivkaParams->get('customRadio').'"></span>';
                            
                            echo '</label></span>' . "\n";
                        }
                        // Max Diff
                        elseif($row['enota'] == 5){
                            echo '<span class="category ' . $cssAlign . '">';

                            echo '<input data-col="'.$i.$row[0].'" type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="'.$value.'"' . ( $is_checked ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

                            // Font awesome
							echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
                            
                            echo '</span>' . "\n";
                        }
						else {
                            echo '<span class="category ' . $cssAlign . '">';

                            echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="'.$value.'"' . ( $is_checked ? ' checked' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

							// Font awesome
							echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
								((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                                '></span>';
                                
                             echo '</span>' . "\n";
						}

                        
                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti) - pri one against another tega ni
                        if($row['enota'] != 4)
                            echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';

                        // Vizualna skala - pobarvamo zvezdice
                        if($row['enota'] == 12 && $is_checked){
                            echo '<script>
                                $(document).ready( function(){ customRadioTableSelectMobile(\''.$row1['id'].'\', \''.$value.'\'); } );
                            </script>';
                        }
                    }

                    // Max Diff - urejanje navpicnega dela grupiranja radio button - vodoravni je urejen po defaultu s pomočjo atributa name
                    if($row['enota'] == 5){
                        echo '<script>
                            $(document).ready(
                                function(){
                                    var col, elem, ime;
                                    ime = "vrednost_' . $row1['id'] . '";

                                    $("input[name=" + ime + "]").click(function() {
                                        elem = $(this);
                                        col = elem.data("col");

                                        $("input[data-col=" + col + "]").prop("checked", false);
                                        $("input[data-col=" + col + "]").parent().parent().removeClass("checked");

                                        elem.prop("checked", true);
                                        elem.parent().parent().addClass("checked");
                                    });
                                }
                            );
                        </script>';
                    }

                    // One against another - beseda "ali"
                    if($row['enota'] == 4 && $cnt == 0){

                        echo '<div class="grid_mobile_title">';
                        echo self::$lang['srv_tip_sample_t6_4_vmes'];
                        echo '</div>';
                    }
					
					$cnt++;
                }
			}
			
			// END grid_mobile_variables
			echo '</div>';
					
			// Semanticni diferencial in one against another - desna labela
			if($row['enota'] == 1 || $row['enota'] == 4){
				
				echo '<div class="grid_mobile_title">';
				
				# po potrebi prevedemo naslov2 za semanticni diferencial
				$naslov2 = Language::getInstance()->srv_language_vrednost($row1['id'], true);
				if ($naslov2 != '') {
					$row1['naslov2'] = $naslov2;
				}
			
				// Datapiping
				$row1['naslov2'] = Helper::dataPiping($row1['naslov2']);

				echo $row1['naslov2'];
				
				echo '</div>';
            }
			
			// END grid_mobile_question
			echo '</div>';
			
			$first = false;
        }
		
		
		// END grid_mobile
		echo '</div>';
    }

	/**
     * @desc prikaze vnosno polje za dvojno tabelo radio
     */
    public function radioDoubleMultigrid($spremenljivka){
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
		
		// Nastavitev za prilagoditev mobilnih tabel (z razpiranjem ali brez)
		SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		
       
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';
		
        # polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
		
		# koliko je stolpcev z manjkajočimi vrednostmi
        $mv_count = 0; 
		
        # če polje other != 0 je grid kot missing
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part='1' ORDER BY vrstni_red");

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

        // se za desni del grida
        $sql_grid2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part='2' ORDER BY vrstni_red");
		
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
            }
			
			$indexLanguage++;
        }
			
		
		// Izrisemo celotno vsebino tabele za mobietl
		echo '<div class="grid_mobile radio double '.($mobile_tables == 2 ? 'mobile_expanding' : '').'">';
		
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        // Cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT d.* FROM srv_data_checkgrid" . get('db_table') . " d, srv_grid g WHERE d.spr_id='$row[id]' AND d.usr_id='" . get('usr_id') . "' AND d.loop_id $loop_id AND d.grd_id=g.id AND g.part='1' AND g.spr_id='$row[id]'");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }
        // Cache tabele srv_data_checkgrid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja) - za part 2
        $srv_data_grid2 = array();
        $sql_grid = sisplet_query("SELECT d.* FROM srv_data_checkgrid" . get('db_table') . " d, srv_grid g WHERE d.spr_id='$row[id]' AND d.usr_id='" . get('usr_id') . "' AND d.loop_id $loop_id AND d.grd_id=g.id AND g.part='2' AND g.spr_id='$row[id]'");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid2[$row_grid['vre_id']] = $row_grid;
        }
		
		// Ali skrivamo radio gumbe in checkboxe
		$presetValue = ($spremenljivkaParams->get('presetValue') > 0 && empty($srv_data_grid)) ? $spremenljivkaParams->get('presetValue') : 0;

        // Loop po posameznih vprasanjih (vrsticah)
		$first = true;
        $sql1 = sisplet_query("SELECT id, naslov, naslov2, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
			echo '<div class="grid_mobile_question" id="vrednost_if_'.$row1['id'].'" '.(($row1['hidden'] == 1) ? 'style="display:none"' : '').(($row1['hidden'] == 2) ? ' class="answer-disabled"' : '').'">';

			
			// NASLOV posameznega vprasanja
            echo '<div class="grid_mobile_title">';
            
            echo '  <div class="grid_mobile_title_text">';
			
			// po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '')
                $row1['naslov'] = $naslov;
				
			// Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo $row1['naslov'];
            
            // preverimo izbrano vrednost
            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];
            $grid_id2 = $srv_data_grid2[$row1['id']]['grd_id'];

            // ugotovimo ali je na katerem gridu predhodno izbran missing
            $is_missing = false;
            if (count($srv_grids) > 0) {
                foreach ($srv_grids AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0 && $srv_grids[$i]['id'] == $grid_id) {
                        $is_missing = true;
                    }
                }
            }

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

            echo '  </div>';
			
			// Puscica za razpiranje ce imamo vklopljene mobilne tabele z razpiranjem
			/*if($mobile_tables == 2)
				echo '<span class="faicon arrow_up mobile_expanding_arrow"></span>';*/

			echo '</div>';
            
            
            // Podnaslov prve podtabele
            if($row['grid_subtitle1'] != '')
                echo '<div class="grid_mobile_double_subtitle">'.$row['grid_subtitle1'].'</div>';

			
			// VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables part_1">';
			
			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids) > 0) {

				foreach ($srv_grids AS $i => $srv_grid) {				
					
                    // izpišemo radio grida
                    // Other oz. missing
                    if ($srv_grid['other'] != 0) {

                        $value = $srv_grids[$i]['id'];

                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '').'">';        
                        						
                        echo '<span class="missing ' . $cssAlign . '">';
						
                        # imamo missing vrednost   
                        echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '') . ' data-calculation="0" vre_id="' . $row1['id'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mmt6-3-1\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
						
                        echo '</span>' . "\n";

                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // Navadna variabla
					else {

                        $value = $srv_grids[$i]['id'];

                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.(((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '').'">';                

						echo '<span class="category ' . $cssAlign . '">';
						# ni missing vrednost
						
						echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt6-3-1\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';

                        echo '</span>' . "\n";
                        
                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    }
                }
			}
			
			// END grid_mobile_variables
            echo '</div>';
            

            // Vmesna crta med prvim in drugim delom dvojne tabele
            echo '<div class="grid_mobile_double_separator"></div>';


            // Podnaslov druge podtabele
            if($row['grid_subtitle2'] != '')
                echo '<div class="grid_mobile_double_subtitle">'.$row['grid_subtitle2'].'</div>';


            // VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables part_2">';

			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids2) > 0) {

				foreach ($srv_grids2 AS $j => $srv_grid) {				
					
                    // izpišemo radio grida
                    // Other oz. missing
                    if ($srv_grid['other'] != 0) {

                        $value = $srv_grids2[$j]['id'];

                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '_part_2">';
                        echo '<div class="grid_mobile_variable '.((($grid_id2 == $value && $grid_id2 != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '').'">';             
						
                        echo '<span class="missing ' . $cssAlign . '">';
						
                        # imamo missing vrednost   
                        echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '_part_2"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '_part_2" value="' . $value . '"' . ((($grid_id2 == $value && $grid_id2 != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '') . ' data-calculation="0" vre_id="' . $row1['id'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mmt6-3-2\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
						
                        echo '</span>' . "\n";

                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // Navadna variabla
					else {

                        $value = $srv_grids2[$j]['id'];

                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '_part_2">';
                        echo '<div class="grid_mobile_variable '.(((($grid_id2 == $value && $grid_id2 != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '').'">';                

						echo '<span class="category ' . $cssAlign . '">';
						# ni missing vrednost
						
						echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '_part_2"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '_part_2" value="' . $value . '"' . (((($grid_id2 == $value && $grid_id2 != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids2[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt6-3-2\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';

                        echo '</span>' . "\n";
                        
                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    }
                }
			}
			
			// END grid_mobile_variables part2
            echo '</div>';
            
			
			// END grid_mobile_question
			echo '</div>';
			
			$first = false;
		}
		
		
		// END grid_mobile
		echo '</div>';
    }

	
    /**
     * @desc prikaze vnosno polje za tabelo checkbox
     */
    public function checkboxMultigrid($spremenljivka){
        global $lang;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
		
		// Nastavitev za prilagoditev mobilnih tabel (z razpiranjem ali brez)
		SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		
		
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
        
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';

        # polovimo vrednosti gridov, prevedmo naslove in hkrati preverimo ali imamo missinge
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


        // Izrisemo celotno vsebino tabele za mobitel
		echo '<div class="grid_mobile checkbox '.($mobile_tables == 2 ? 'mobile_expanding' : '').'">';
		
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        // Cache izpolnjenih podatkov
        $srv_data_cache = array();
        $sql2 = sisplet_query("SELECT * FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_cache[$row2['vre_id']][$row2['grd_id']] = $row2;
        }
		
		// Ali skrivamo radio gumbe in checkboxe
		$presetValue = ($spremenljivkaParams->get('presetValue') > 0 && empty($srv_data_grid)) ? $spremenljivkaParams->get('presetValue') : 0;


        // Loop po posameznih vprasanjih (vrsticah)
        $sql1 = sisplet_query("SELECT id, naslov, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {

			echo '<div class="grid_mobile_question" id="vrednost_if_'.$row1['id'].'" '.(($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '').'>';

			
			// NASLOV posameznega vprasanja
            echo '<div class="grid_mobile_title">';
            
            echo '  <div class="grid_mobile_title_text">';
            
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo $row1['naslov'];

            # preverimo izbrano vrednost
            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            # ugotovimo ali je na katerem gridu predhodno izbran missing
            $is_missing = false;
            if (count($srv_grids) > 0) {
                foreach ($srv_grids AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0) {
                        $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];
                        if ($srv_grids[$i]['id'] == $grid_id) {
                            $is_missing = true;
                        }
                    }
                }
            }

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

            echo '</div>';
			
			// Puscica za razpiranje ce imamo vklopljene mobilne tabele z razpiranjem
			if($mobile_tables == 2)
				echo '<span class="faicon arrow_up mobile_expanding_arrow"></span>';

			echo '</div>';
			
			
			// VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables">';
						
		
			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids) > 0) {

				foreach ($srv_grids AS $i => $srv_grid) {	
                    
                    $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];
                    
                    $value = $srv_grid['id'];

                    // izpišemo radio grida
                    // Other oz. missing
                    if ($srv_grid['other'] != 0) {

						$is_checked = ($grid_id == $value && $grid_id != '') ? true : false;
					
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.($is_checked ? ' checked' : '').'">';                                                     
                        
                        echo '<span class="missing ' . $cssAlign . '">';

                        # imamo missing polje    
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ($is_checked ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

                        // Font awesome checkbox
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() .'px;"' : '').
                            '></span>';

                        echo '</span>' . "\n";

                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                                                
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';

                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // Navadna variabla
					else {

						$is_checked = ($grid_id == $value && $grid_id != '' && !$is_missing) ? true : false;
						
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';                  
						echo '<div class="grid_mobile_variable '.($is_checked ? ' checked' : '').'">';                
                            
                        echo '<span class="category ' . $cssAlign . '">';
                        
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ($is_checked ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="1" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

                        // Font awesome checkbox
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() .'px;"' : '').
                            '></span>';

                        echo '</span>' . "\n";                        
                            
                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';

                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    }	
                }
			}
			
			// END grid_mobile_variables
			echo '</div>';
			
			
			// END grid_mobile_question
			echo '</div>';
		}
		
		
		// END grid_mobile
		echo '</div>';
    }

	/**
     * @desc prikaze vnosno polje za dvojno tabelo checkbox
     */
    public function checkboxDoubleMultigrid($spremenljivka){
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
		
		// Nastavitev za prilagoditev mobilnih tabel (z razpiranjem ali brez)
		SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		
       
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';
		
        # polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
        $srv_grids2 = array();
		
		# koliko je stolpcev z manjkajočimi vrednostmi
        $mv_count = 0; 
		
        # če polje other != 0 je grid kot missing
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part='1' ORDER BY vrstni_red");

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

        // se za desni del grida
        $sql_grid2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND part='2' ORDER BY vrstni_red");
		
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
            }
			
			$indexLanguage++;
        }
			
		
		// Izrisemo celotno vsebino tabele za mobietl
		echo '<div class="grid_mobile checkbox double '.($mobile_tables == 2 ? 'mobile_expanding' : '').'">';
		
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        // Cache tabele srv_data_checkgrid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_cache = array();

        $sql2 = sisplet_query("SELECT * FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_cache[$row2['vre_id']][$row2['grd_id']] = $row2;
        }
		
		// Ali skrivamo radio gumbe in checkboxe
		$presetValue = ($spremenljivkaParams->get('presetValue') > 0 && empty($srv_data_cache)) ? $spremenljivkaParams->get('presetValue') : 0;

        // Loop po posameznih vprasanjih (vrsticah)
		$first = true;
        $sql1 = sisplet_query("SELECT id, naslov, naslov2, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
			echo '<div class="grid_mobile_question" id="vrednost_if_'.$row1['id'].'" '.(($row1['hidden'] == 1) ? 'style="display:none"' : '').(($row1['hidden'] == 2) ? ' class="answer-disabled"' : '').'">';

			
			// NASLOV posameznega vprasanja
			echo '<div class="grid_mobile_title">';
            
            echo '  <div class="grid_mobile_title_text">';

			// po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '')
                $row1['naslov'] = $naslov;
				
			// Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo $row1['naslov'];

             # ugotovimo ali je na katerem gridu predhodno izbran missing
             $is_missing = false;
             if (count($srv_grids) > 0) {
                 foreach ($srv_grids AS $i => $srv_grid) {
                     if ($srv_grid['other'] != 0) {
                         $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];
                         if ($srv_grids[$i]['id'] == $grid_id) {
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
            
            echo '  </div>';

			// Puscica za razpiranje ce imamo vklopljene mobilne tabele z razpiranjem
			/*if($mobile_tables == 2)
				echo '<span class="faicon arrow_up mobile_expanding_arrow"></span>';*/            
            
			echo '</div>';
            
            // Podnaslov prve podtabele
            if($row['grid_subtitle1'] != '')
                echo '<div class="grid_mobile_double_subtitle">'.$row['grid_subtitle1'].'</div>';

			// VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables part_1">';
			
			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids) > 0) {

				foreach ($srv_grids AS $i => $srv_grid) {				
                    
                    $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];

                    // izpišemo radio grida
                    // Other oz. missing
                    if ($srv_grid['other'] != 0) {

                        $value = $srv_grids[$i]['id'];

                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        
                        echo '<div class="grid_mobile_variable '.((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '').'">';        
                        						
                        echo '<span class="missing ' . $cssAlign . '">';
						
                        # imamo missing vrednost   0
                        //echo '<input type="checkbox" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '') . ' data-calculation="0" vre_id="' . $row1['id'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
						
                        echo '</span>' . "\n";

                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // Navadna variabla
					else {

                        $value = $srv_grids[$i]['id'];

                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.(((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '').'">';                

						echo '<span class="category ' . $cssAlign . '">';
						# ni missing vrednost
						
						//echo '<input type="checkbox" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grid['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                        
                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';

                        echo '</span>' . "\n";
                        
                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    }
                }
			}
			
			// END grid_mobile_variables
            echo '</div>';
            

            // Vmesna crta med prvim in drugim delom dvojne tabele
            echo '<div class="grid_mobile_double_separator"></div>';

            // Podnaslov druge podtabele
            if($row['grid_subtitle2'] != '')
                echo '<div class="grid_mobile_double_subtitle">'.$row['grid_subtitle2'].'</div>';

            // VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables part_2">';

			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids2) > 0) {

				foreach ($srv_grids2 AS $j => $srv_grid) {				
                    
                    $grid_id = $srv_data_cache[$row1['id']][$j]['grd_id'];

                    // izpišemo radio grida
                    // Other oz. missing
                    if ($srv_grid['other'] != 0) {

                        $value = $srv_grids2[$j]['id'];

                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '').'">';             
						
                        echo '<span class="missing ' . $cssAlign . '">';
						
                        # imamo missing vrednost   
                        //echo '<input type="checkbox" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ((($grid_id2 == $value && $grid_id2 != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '') . ' data-calculation="0" vre_id="' . $row1['id'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                        
                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
						
                        echo '</span>' . "\n";

                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // Navadna variabla
					else {

                        $value = $srv_grids2[$j]['id'];

                        echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.(((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '').'">';                

						echo '<span class="category ' . $cssAlign . '">';
						# ni missing vrednost
						
						//echo '<input type="checkbox" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (((($grid_id2 == $value && $grid_id2 != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids2[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grid['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mmt\', ' . $row1['id'] . ');">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';

                        echo '</span>' . "\n";
                        
                        // Datapiping
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        
                        // Text grida (posamezne vrednosti)
                        echo '<span class="grid_mobile_variable_title '.($srv_grid['other'] == 0 ? 'category' : 'missing').'">' . $srv_grid['naslov'] . '</span>';
                            
                            
                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    }
                }
			}
			
			// END grid_mobile_variables part2
            echo '</div>';
            
			
			// END grid_mobile_question
			echo '</div>';
			
			$first = false;
		}
		
		
		// END grid_mobile
		echo '</div>';
    }

	
    /**
     * @desc prikaze vnosno polje za tabelo text in number
     */
    public function textMultigrid($spremenljivka){
        global $lang;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);

		// Nastavitev za prilagoditev mobilnih tabel (z razpiranjem ali brez)
		SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		
		
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';

			
        if ($row['num_useMin'] == 1 && $row['num_useMax'] == 1 && $row['vsota_min'] == $row['vsota_limit'])
            $limit = '(' . $row['vsota_min'] . ')';
        elseif ($row['num_useMin'] == 1 && $row['num_useMax'] == 1)
            $limit = '(min ' . $row['vsota_min'] . ', max ' . $row['vsota_limit'] . ')';
        elseif ($row['num_useMin'] == 1)
            $limit = '(min ' . $row['vsota_min'] . ')';
        elseif ($row['num_useMax'] == 1)
            $limit = '(max ' . $row['vsota_limit'] . ')';
        else
            $limit = '';

        //************Params za omejitev sliderjev
        //tvorjenje omejitve********************************************************************************
        $slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
        $slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);
        $limit_slider_mobile = '(min ' . $slider_MinNumLabel . ', max ' . $slider_MaxNumLabel . ')';
        //************

        // omejitev vnosa
        if ($row['vsota_show'] == 1 && $row['ranking_k'] != '1' && $row['tip'] == 20) {
            echo ' 		<label class="limit">' . $limit . '</label>';
        }

        # polovimo vrednosti gridov, prevedmo naslove in hkrati preverimo ali imamo missinge
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



        // Izrisemo celotno vsebino tabele za mobitel
		echo '<div class="grid_mobile text '.($mobile_tables == 2 ? 'mobile_expanding' : '').'">';
		
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        // Cache textovnih odgovorov
        $srv_data_cache = array();
        $sql2 = sisplet_query("SELECT * FROM srv_data_textgrid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_cache[$row2['vre_id']][$row2['grd_id']] = $row2;
        }

        // Cache missingov
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }


        // Loop po posameznih vprasanjih (vrsticah)
        $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {

			echo '<div class="grid_mobile_question" id="vrednost_if_'.$row1['id'].'" '.(($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '').'>';

			
			// NASLOV posameznega vprasanja
            echo '<div class="grid_mobile_title">';

            echo '  <div class="grid_mobile_title_text">';
            
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            # preverimo izbrano vrednost
            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            # ugotovimo ali je na katerem gridu predhodno izbran missing
            $is_missing = false;
            if (count($srv_grids) > 0) {
                foreach ($srv_grids AS $i => $srv_grid) {
                    if ($srv_grid['other'] != 0) {
                        $grid_id = $srv_data_grid[$row1['id']]['grd_id'];
                        if ($srv_grids[$i]['id'] == $grid_id) {
                            $is_missing = true;
                        }
                    }
                }
            }

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

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

            echo '  </div>';
			
			// Puscica za razpiranje ce imamo vklopljene mobilne tabele z razpiranjem
			if($mobile_tables == 2)
				echo '<span class="faicon arrow_up mobile_expanding_arrow"></span>';
            
			echo '</div>';
			
			
			// VREDNOSTI znotraj vprasanja
			echo '<div class="grid_mobile_variables">';
						
		
			// Loop po posameznih VREDNOSTIH (stolpcih)
			if (count($srv_grids) > 0) {

				foreach ($srv_grids AS $i => $srv_grid) {				
                    
                    if ($srv_grid['other'] != 0)
                        $grid_id = $srv_data_grid[$row1['id']]['grd_id'];
                    else
                        $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];

                    $value = $srv_grid['id'];
                    $vsebina = '';

                    if ($grid_id == $value) {
                        $vsebina = $srv_data_cache[$row1['id']][$i]['text'];
                    }


                    // Missing
                    if ($srv_grid['other'] != 0) {

						$is_checked = ($grid_id == $value && $grid_id != '') ? true : false;
						
                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<div class="grid_mobile_variable '.($is_checked ? ' checked' : '').'">';  

                        # imamo missing nardimo radio
                        echo '<span class="missing ' . $cssAlign . '">';
                        echo '<input type="radio" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ($is_checked ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching();">';
                        
                         // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
                            
                        echo '</span>';
                        
                        // izpišemo labelo grida
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        echo '<span class=" grid_mobile_variable_title ' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</span>';

                        // END grid_mobile_variable
                        echo '</div>';
                        echo '</label>';
                    } 
                    // multitext
                    elseif ($row['tip'] == 19) {
                        
                        echo '<div class="grid_mobile_variable">';  

                        // izpišemo labelo grida
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        echo '<span class="grid_mobile_variable_title ' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</span>';

                        echo '<span class="category ' . $cssAlign . '">';
                        echo '<textarea class="width_' . $taWidth . '" rows="' . $taHeight . '" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeyup="checkBranching();">' . ($is_missing ? '' : $vsebina) . '</textarea>';
                        echo '</span>' . "\n";

                        // END grid_mobile_variable
                        echo '</div>';
                    } 
                    // multinumber - rabimo JS checkNumber
                    else {

                        echo '<div class="grid_mobile_variable">';  

                        // izpišemo labelo grida
                        $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                        echo '<span class="grid_mobile_variable_title ' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</span>';

                        echo '<span class="category ' . $cssAlign . '">';
                        echo '<input type="text" class="width_' . $taWidth . '" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . ($is_missing ? '' : $vsebina) . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeypress="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . ');" onkeyup="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . '); checkBranching();">';

                        // multislider
                        //slider na PC, tablici in mobilniku
                        if ($row['ranking_k'] == 1) {

                            $spremenljivkaParams = new enkaParameters($row['params']);
                            $slider_handle = ($spremenljivkaParams->get('slider_handle') ? $spremenljivkaParams->get('slider_handle') : 0);
                            $slider_window_number = ($spremenljivkaParams->get('slider_window_number') ? $spremenljivkaParams->get('slider_window_number') : 0);
                            $slider_nakazi_odgovore = ($spremenljivkaParams->get('slider_nakazi_odgovore') ? $spremenljivkaParams->get('slider_nakazi_odgovore') : 0); //za checkbox
                            $slider_MinMaxNumLabelNew = ($spremenljivkaParams->get('slider_MinMaxNumLabelNew') ? $spremenljivkaParams->get('slider_MinMaxNumLabelNew') : 0);
                            $slider_MinMaxLabel = ($spremenljivkaParams->get('slider_MinMaxLabel') ? $spremenljivkaParams->get('slider_MinMaxLabel') : 0);
                            $slider_VmesneNumLabel = ($spremenljivkaParams->get('slider_VmesneNumLabel') ? $spremenljivkaParams->get('slider_VmesneNumLabel') : 0);
                            $slider_VmesneDescrLabel = ($spremenljivkaParams->get('slider_VmesneDescrLabel') ? $spremenljivkaParams->get('slider_VmesneDescrLabel') : 0);
                            $slider_VmesneCrtice = ($spremenljivkaParams->get('slider_VmesneCrtice') ? $spremenljivkaParams->get('slider_VmesneCrtice') : 0);
                            $slider_handle_step = ($spremenljivkaParams->get('slider_handle_step') ? $spremenljivkaParams->get('slider_handle_step') : 1);
                            $slider_MinLabel = ($spremenljivkaParams->get('slider_MinLabel') ? $spremenljivkaParams->get('slider_MinLabel') : "Minimum");
                            $slider_MaxLabel = ($spremenljivkaParams->get('slider_MaxLabel') ? $spremenljivkaParams->get('slider_MaxLabel') : "Maximum");
                            //$slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
                            //$slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);
							
                            $slider_NumofDescrLabels = ($spremenljivkaParams->get('slider_NumofDescrLabels') ? $spremenljivkaParams->get('slider_NumofDescrLabels') : 5);
                            $slider_DescriptiveLabel_defaults = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults') : 0);
                            $slider_DescriptiveLabel_defaults_naslov1 = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') : 0);
                            $displayMinMaxLabel = ($slider_MinMaxLabel == 0) ? ' style="display:none;"' : '';
							
							
							if(get('lang_id') != null){
								$lang_id = get('lang_id');
								$ank_id = get('anketa');				
								
								$sqlString = "SELECT label, label_id FROM srv_language_slider WHERE ank_id='$ank_id' AND spr_id='$spremenljivka' AND lang_id='$lang_id' ORDER BY label_id";
								$sqlSlider = sisplet_query($sqlString);				
								
								while ($rowsqlSlider = mysqli_fetch_array($sqlSlider)) {
									if($rowsqlSlider['label_id'] == 1){
										$MinLabel = $rowsqlSlider['label'];
									}
									elseif($rowsqlSlider['label_id'] == 2){
										$MaxLabel = $rowsqlSlider['label'];
									}elseif($row['label_id'] == 0){
										$custom = $rowsqlSlider['label'];
									}
								}
								
								if($slider_DescriptiveLabel_defaults && $custom==''){	//ce so prednalozene opisne labele drsnika in nimamo se prevoda
									$custom_ar = explode(';', $slider_DescriptiveLabel_defaults_naslov1);
								}else{	//ce so custom opisne labele drsnika
									$custom_ar = explode('; ', $custom);
								}
								
								// Ce slucajno nimamo prevedeno uporabimo original
								$MinLabel = ($MinLabel == '') ? ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : self::$lang['srv_new_text']) : $MinLabel;
								$MaxLabel = ($MaxLabel == '') ? ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : self::$lang['srv_new_text']) : $MaxLabel;
							}
							else{
								$MinLabel = ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : self::$lang['srv_new_text']);
								$MaxLabel = ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : self::$lang['srv_new_text']);
							}                           
                            
                            echo '<div style="width:100%; height:150px">';

                            //$default_value = round(($row['vsota_limit']-$row['vsota_min']) / 2) + $row['vsota_min'];
                            $default_value = round(($slider_MaxNumLabel - $slider_MinNumLabel) / 2) + $slider_MinNumLabel;
                            $vrednost = ($vsebina == '') ? $default_value : $vsebina;

                            //labeli nad min in max drsnikov ############################################################                                
                            if (get('mobile') == 0 || get('mobile') == 2) {//ce PC ali tablica
                                echo '<table ' . $displayMinMaxLabel . ' class="slider_grid_minmaxlabel">';
                            } else if (get('mobile') == 1) {    //ce mobilnik                                    
                                echo '<table ' . $displayMinMaxLabel . ' class="slider_grid_minmaxlabel_mobile">';
                            }
                            echo '<tbody>';
                            echo '<tr>';
                            echo '<td align="left">' . $MinLabel . '</td>';
                            echo '<td align="right">' . $MaxLabel . '</td>';
                            echo '</tr>';
                            echo '</tbody>';
                            echo '</table>';
                            //labeli nad min in max drsnikov - konec ####################################################
                            
                            echo '<div class="sliderText" id="sliderText_' . $spremenljivka . '_' . $row1['id'] . '">' . $vrednost . '</div>';

                            if (get('mobile') == 0 || get('mobile') == 2) {//ce PC ali tablica
                                echo '<div id="slider_' . $spremenljivka . '_' . $row1['id'] . '" class="slider"></div>';
                            } 
                            else if (get('mobile') == 1) {    //ce mobilnik
                                echo '<div id="slider_' . $spremenljivka . '_' . $row1['id'] . '" class="slider_grid_mobile"></div>';
                            }

                            echo '</div>';
                            
                            // za custom opisne labele
                            // moznosti urejanja opisnih label drsnika
                            if($slider_VmesneDescrLabel){									
                                for($i=1; $i<=$slider_NumofDescrLabels; $i++){	
									if($custom == ''){	//ce nimamo prevoda opisnih label za drsnika								
										$slider_CustomDescriptiveLabelsTmp = ($spremenljivkaParams->get('slider_Labela_opisna_'.$i) ? $spremenljivkaParams->get('slider_Labela_opisna_'.$i) : '');
									}else{	//ce mamo prevod opisnih label za drsnika
										$slider_CustomDescriptiveLabelsTmp = $custom_ar[$i-1];
									}
                                    $slider_CustomDescriptiveLabelsTmp = preg_replace("/\s|&nbsp;/",' ',$slider_CustomDescriptiveLabelsTmp);  //za odstranitev morebitnih presledkov, ki lahko delajo tezave pri polju za drsnik										
                                    if($i == 1){
                                        $slider_CustomDescriptiveLabels = $slider_CustomDescriptiveLabelsTmp;
                                    }else{
                                        $slider_CustomDescriptiveLabels .= "; ".$slider_CustomDescriptiveLabelsTmp;
                                    }									
                                }
                            }
                            //za custom opisne labele - konec

                            ?>
                            <script>
                                $(function () {
                                    slider_grid_init(<?=get('mobile')?>, <?=$spremenljivka?>, <?=$row1['id']?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$vrednost?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$default_value?>, <?=$slider_nakazi_odgovore?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>');
                                });
                            </script>
                            <?
                        }

                        echo '</span>';

                        // END grid_mobile_variable
                        echo '</div>';
                    }			
                }
			}
			
			// END grid_mobile_variables
			echo '</div>';
			
			
			// END grid_mobile_question
			echo '</div>';
		}
		
		
		// END grid_mobile
		echo '</div>';
    }
}