<?php
/***************************************
 * Description: Prikaže vsa polja iz dinamičnega multigrida/multicheckbox horizontalni/vertikalni
 *
 * Vprašanje je prisotno:
 *  tip 6 - enota 3 - dynamic_mg 1, 3, 5
 *  tip 16 - enota 3 - dynamic_mg 1, 3, 5
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
use SurveySetting;

// Iz admin/survey


// Vprašanja

class DynamicController extends Controller
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

        return new DynamicController();
    }

    /**
     * @desc prikaze vnosno polje za dinamicen multigrid
     */
    public function multigrid($spremenljivka)
    {
        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
        $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
        //$css = ' style = "width: '.$gridWidth.'%;" ';
		
		
		//************************ za izris traku
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox trak
		$trak_num_of_titles = ($spremenljivkaParams->get('trak_num_of_titles') ? $spremenljivkaParams->get('trak_num_of_titles') : 0); //belezi stevilo nadnaslovov

		if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen @ diferencial ali klasicna tabela
			SurveySetting::getInstance()->Init(get('anketa'));
			$mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
			
			$trak_class = 'trak_class';
			$trak_class_input = 'trak_class_input';	
			$question = 'question_trak';
			if ($row['enota'] == 1){	//ce je diferencial
				$gridWidth = 15;	//za sirino celic skrajno levo pa desno od traku	
			}
			if($trak_num_of_titles != 0){
				$display_trak_num_of_titles = 'style="display:none;"';
				$trak_nadnaslov_table_td_width = 100 / $trak_num_of_titles;	//spremenljivka za razporeditev sirine nadnaslovov @ traku
			}
			$display_trak_num_of_titles = '';

/* 			?>
				<script>
					$(document).ready(function(){
						trak_edit_num_titles(<?=$row['grids']?>, <?=$spremenljivka?>, <?=$trak_num_of_titles?>, <?=json_encode($trak_nadnaslov)?>);
					});
				</script>
			<?	 */		
		}else{
			$trak_class = '';
			$trak_class_input = '';
			$question = 'question';
			$display_trak_num_of_titles = 'style="display:none;"';
		}
		
		for($i = 1; $i <= $trak_num_of_titles; $i++){
			$trak_nadnaslov[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.$i.'') ? $spremenljivkaParams->get('trak_nadnaslov_'.$i.'') : $lang['srv_new_text']);
		}
		//********************** za izris traku - konec
		
		
		

        // izracuni za sirino celic
        $size = $row['grids'];

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

        # če imamo mankajoče potem dodamo še en prazen stolpec za razmak
        if ($mv_count > 0) {
            $size += 1 + $mv_count;
        }
        if ($row['enota'] == 1) {
            $size += 2;
        }

        $size += 1;

        # če imamo nastavljno sirino prvega grida ostalih ne nastavljamo
        if ($gridWidth == 30) {
            $cellsize = round(80 / $size);
        } else {
            $cellsize = 'auto';
        }

        $spacesize = round(80 / $size / 4);

        $bg = 1;

        echo '<table class="grid_table dynamicmultigrid">' . "\n";

        echo '<colgroup class="question">';
        echo '<col class="width_' . $gridWidth . '">';
        echo '</colgroup>';
        echo '<colgroup>';
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
            for ($i = 1; $i <= $mv_count; $i++)
                echo '<col>';
            echo '</colgroup>';
        }
        if ($row['enota'] > 0) {
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
            echo '<colgroup class="differential">';
            echo '<col class="width_' . $gridWidth . '">';
            echo '</colgroup>';
        }
        if (!$row['dynamic_mg'] == 3) {
            echo '<colgroup>';
            echo '<col>';
            echo '</colgroup>';
        }

        echo '<thead>';
		
		//vrstica z nadnaslovi
		echo '<tr '.$display_trak_num_of_titles.' class="display_trak_num_of_titles_respondent_'.$row['id'].'">';
		echo '          <td></td>';
		echo '          <td></td>';
		//for($j = 1; $j <= $trak_num_of_titles; $j++){
		for ($j = 1; $j <= $row['grids']; $j++) {
			//echo '<td>'.$j.'</td>';
			if($j == 1){
				$nadnaslov_floating = 'left';
			}else if($j == $row['grids']){
				$nadnaslov_floating = 'right';
			}else{
				$nadnaslov_floating = 'none';
			}
			echo '<td class="trak_inline_nadnaslov" grd="gr_'.$j.'"><div id="trak_nadnaslov_'.$j.'_'.$spremenljivka.'" name="trak_nadnaslov_'.$j.'" class="trak_inline_nadnaslov" style="float:'.$nadnaslov_floating.'; display:inline" '.(strpos($trak_nadnaslov[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $trak_nadnaslov[$j] . '</div></td>';
			
		}
		echo '</tr>';	
		//vrstica z nadnaslovi - konec
		
        echo '	<tr>' . "\n";
        echo '		<td></td>' . "\n";
        echo '		<td></td>' . "\n";

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
				
				
				if($diferencial_trak != 1){	//ce ni traku
					# izpišemo labelo grida
					echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . '">' . $srv_grid['naslov'] . '</td>' . "\n";
				}elseif($diferencial_trak == 1){	//ce je trak
					# izpišemo ustrezno labelo grida
					if($srv_grid['other'] == 0){	//ce je labela za kategorijo odgovora, naj bo prazno
						echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '"></td>' . "\n";
					}else {	//drugace, ce je labela za missing, izpisi labelo
						echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
					}
				}
            }
        }

        if (!$row['dynamic_mg'] == 3) {
            echo '		<td></td>' . "\n";
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
        $gridRow = 1;
        $hide = false;
        $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        $countVar = mysqli_num_rows($sql1);

        // Stevilo vseh vrednosti
        echo '<input type="hidden" id="dynamic_multigrid_' . $spremenljivka . '" value="' . $countVar . '" />';
        // Indikator da v js vemo ali smo ravnokar naloudali stran ali pa smo samo premaknili vrstico (potrebno zaradi prikazovanja glede na ife v dolocenih primerih)
        echo '<input type="hidden" id="dynamic_multigrid_' . $spremenljivka . '_load" value="1" />';

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

            $ifDisplay = ($row1['if_id'] > 0 ? ' if_hide' : '');
            // Ce lahko prikazemo vrstico (se ni nobena izrisana in ni skrita z ifom) -> naslednjih ne izrisujemo vec
            if ($hide == false && $row1['hidden'] == 0 && $row1['if_id'] == 0) {
                $gridRowDisplay = '';
                $hide = true;
            } else
                $gridRowDisplay = ' style="display:none;"';

            $colspan = 0;

            echo '	<tr id="vrednost_if_'. $row1['id'].'" seq="'.$gridRow.'" class="'.$spremenljivka.'_gridRow '.$spremenljivka.'_gridRow_'.$gridRow.' '.$ifDisplay.'" '.$gridRowDisplay.'>' . "\n";
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

            $colspan += 2;

            if (count($srv_grids) > 0) {
                $first_missing_value = true;
                foreach ($srv_grids AS $i => $srv_grid) {
                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '		<td></td>' . "\n";
                        $first_missing_value = false;

                        $colspan++;
                    }

                    $value = $srv_grids[$i]['id'];
                    # izpišemo radio grida
                    if ($srv_grid['other'] != 0) {
                        # imamo missing vrednost
                        echo '<td class="missing">';
                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        if ($gridRow < $countVar)
                            echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\'); setCheckedClass(this, null, ' . $row1['id'] . ');">';
                        else
                            echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . ');' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . '">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    } else {
						if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0) ){	//ce je trak
							if ($gridRow < $countVar){
								echo '<td onClick="checkBranching(); trak_change_bg(this, '.$diferencial_trak.', '.$srv_grid['spr_id'].', 0); rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');" id="trak_tbl_' . $row1['id'] . '_'.$srv_grid['vrstni_red'].'" class="category' . ((Helper::getCustomCheckbox() != 0) ? ' custom-radio custom-size-' . Helper::getCustomCheckbox() : '') . ' '.$trak_class.' '.(((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? 'trak_container_bg' : '').'">';
							}else{
								echo '<td onClick="checkBranching(); trak_change_bg(this, '.$diferencial_trak.', '.$srv_grid['spr_id'].', 0); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . '" id="trak_tbl_' . $row1['id'] . '_'.$srv_grid['vrstni_red'].'" class="category' . ((Helper::getCustomCheckbox() != 0) ? ' custom-radio custom-size-' . Helper::getCustomCheckbox() : '') . ' '.$trak_class.' '.(((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? 'trak_container_bg' : '').'">';
							}
							# ni missing vrednost
							echo '<input vre_id = '.$row1['id'].' class="'.$trak_class_input.'" type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '">';
							echo '<label class="radio-button-label">'.$srv_grid['variable'];							
							//echo '<span ' . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '></span>'; //custom radio button
							echo '</label>';
							echo '</td>' . "\n";						
						}else{
							# ni missing vrednost
							echo '<td class="category">';
							echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
							if ($gridRow < $countVar)
								echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkBranching(); rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\'); setCheckedClass(this, null, ' . $row1['id'] . ');">';
							else
								echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . '); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . '">';

							// Font awesome
							echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
								((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
								'></span>';

							echo '</label>';
							echo '</td>' . "\n";
						}
                    }

                    $colspan++;
                }
            }

            # dodamo še enoto
            if ($row['enota'] == 1) {
                echo '		<td></td>' . "\n";
                echo '		<td>' . $row1['naslov2'] . '</td>' . "\n";
                $colspan += 2;
            }

            // puscice levo/desno
            if ($row['dynamic_mg'] == 3) {

                //echo '		<td style="height: 80px;">'."\n";
                echo '</tr><tr class="gridRowArrows ' . $spremenljivka . '_gridRowArrows_' . $gridRow . ' ' . $ifDisplay . '" ' . $gridRowDisplay . '><td colspan="' . $colspan . '">';
                echo '		<table class="dynamic_mg_orientation"><tr>' . "\n";
                if ($gridRow > 1)
                    echo '		<td><div title="' . self::$lang['back'] . '" id="arrow_left" class="arrow arrow_back" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow - 1) . '\');"></div></td>';
                else
                    echo '		<td><div style="height: 24px;"></div></td>';
                echo '		<td><div id="dynamic_count" style="text-align: center;">'.($countVar == 1 ? '' : $gridRow.' / '.$countVar).'</div></td>';
                if ($gridRow < $countVar)
                    echo '		<td><div title="' . self::$lang['forward'] . '" id="arrow_right" class="arrow arrow_forward" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');"></div></td>';
                else
                    echo '		<td><div style="height: 24px;"></div></td>';
                echo '	</tr></table>' . "\n";
                echo '</td>';
                //echo '		</td>'."\n";

                // paginacija
            } elseif ($row['dynamic_mg'] == 5 || (get('mobile') == 1 && $mobile_tables > 0)) {

                echo '</tr><tr class="gridRowArrows ' . $spremenljivka . '_gridRowArrows_' . $gridRow . ' ' . $ifDisplay . '" ' . $gridRowDisplay . '><td colspan="' . $colspan . '">';
                echo '		<table class="dynamic_mg_orientation"><tr>' . "\n";
                for ($i = 1; $i <= $countVar; $i++) {
                    echo '		<td><div title="' . $i . '" id="sequence_number_' . $i . '" class="sequence_number sequence_number_'.$i.' ' . ($i == $gridRow ? ' active' : '') . '" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . $i . '\');">' . $i . '</div></td>';
                }
                echo '	</tr></table>' . "\n";
                echo '</td>';

                // puscice za naprej/nazaj
            } else {

                echo '		<td style="height: 80px;">' . "\n";
                if ($gridRow > 1)
                    echo '		<div title="' . self::$lang['back'] . '" id="arrow_up" class="arrow arrow_back" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow - 1) . '\');"></div>';
                else
                    echo '		<div style="height: 24px;"></div>';
                echo '		<div id="dynamic_count">'.($countVar == 1 ? '' : $gridRow.' / '.$countVar).'</div>';
                if ($gridRow < $countVar)
                    echo '		<div title="' . self::$lang['forward'] . '" id="arrow_down" class="arrow arrow_forward" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');"></div>';
                else
                    echo '		<div style="height: 24px;"></div>';
                echo '		</td>' . "\n";
            }

            echo '	</tr>' . "\n";

            $bg++;
            $gridRow++;
        }

        echo '</table>' . "\n";
		
		//************* za ureditev prilagajanja label stolpcev	@TRAK
			//prilagajanje trem opisnim nadnaslovom
			$custom_column_label_option = ($spremenljivkaParams->get('custom_column_label_option') ? $spremenljivkaParams->get('custom_column_label_option') : 1);
			echo '
				<script>
					change_custom_column_label_respondent(\'' . $row['grids'] . '\', \'' . $row['id'] . '\', \'' . $custom_column_label_option . '\');
				</script>				
			';			
			if($trak_num_of_titles != 0){
				//prilagajanje stevilu izbranih nadnaslovov
				?>
					<script>
						$(document).ready(function(){
							trak_edit_num_titles_respondent(<?=$row['grids']?>, <?=$spremenljivka?>, <?=$trak_num_of_titles?>, <?=json_encode($trak_nadnaslov)?>);
						});
					</script>
				<?
			}
		//************* za ureditev prilagajanja label stolpcev @TRAK - konec
    }

    /**
     * @desc prikaze vnosno polje za vertikalen dinamicen multigrid
     */
    public function verticalMultigrid($spremenljivka)
    {
        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
        $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
        //$css = ' style = "width: '.$gridWidth.'%;" ';
		
			
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox trak
		//echo '<script>console.log("diferencial_trak: "+'.$diferencial_trak.');  </script>';
		//echo '<script>console.log("dynamic_mg vertikal: "+'.$row['dynamic_mg'].');  </script>';
		
		//CE JE TRAK - POJDI NA IZRIS VODORAVNE RAZLICICE
		if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen @ diferencial ali klasicna tabela
			$this->multigrid($spremenljivka); //pojdi na zgornjo funkcijo za izris vodoravne različice	
		}else{	//CE NI TRAK, NADALJUJ Z IZRISOM NAVPICNE RAZLICICE

			// izracuni za sirino celic
			$size = $row['grids'];

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

			# če imamo mankajoče potem dodamo še en prazen stolpec za razmak
			if ($mv_count > 0) {
				$size += 1 + $mv_count;
			}
			if ($row['enota'] == 1) {
				$size += 2;
			}

			$size += 1;


			$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

			# cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
			$srv_data_grid = array();
			$sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
			while ($row_grid = mysqli_fetch_array($sql_grid)) {
				$srv_data_grid[$row_grid['vre_id']] = $row_grid;
			}

			# loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
			$gridRow = 1;
			$hide = false;
			$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
			$countVar = mysqli_num_rows($sql1);

			// Stevilo vseh vrednosti
			echo '<input type="hidden" id="dynamic_multigrid_' . $spremenljivka . '" value="' . $countVar . '" />';
			// Indikator da v js vemo ali smo ravnokar naloudali stran ali pa smo samo premaknili vrstico (potrebno zaradi prikazovanja glede na ife v dolocenih primerih)
			echo '<input type="hidden" id="dynamic_multigrid_' . $spremenljivka . '_load" value="1" />';

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

				$ifDisplay = ($row1['if_id'] > 0 ? ' if_hide' : '');
				// Ce lahko prikazemo vrstico (se ni nobena izrisana in ni skrita z ifom) -> naslednjih ne izrisujemo vec
				if ($hide == false && $row1['hidden'] == 0 && $row1['if_id'] == 0) {
					$gridRowDisplay = '';
					$hide = true;
				} else
					$gridRowDisplay = ' style="display:none;"';

				echo '	<div id="vrednost_if_'.$row1['id'].'" seq="'.$gridRow.'" class="'.$spremenljivka.'_gridRow '.$spremenljivka.'_gridRow_'.$gridRow.' '.$ifDisplay.'" '.$gridRowDisplay.'>' . "\n";

				echo '<div ' . ($row['dynamic_mg'] == 4 ? ' style="float: left; width: 70%;"' : '') . '>';

				echo '<div class="dynamic_mg_vrednost">';
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
				echo '</div>';

				if (count($srv_grids) > 0) {
					$first_missing_value = true;
					foreach ($srv_grids AS $i => $srv_grid) {
						if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
							# dodamo spejs pred manjkajočimi vrednostmi
							$first_missing_value = false;
						}
						$value = $srv_grids[$i]['id'];
						$grid_title = $srv_grids[$i]['naslov'];

						// Datapiping
						$grid_title = Helper::dataPiping($grid_title);

                        $smeski ='';
                        if(in_array($row['enota'], [11,12])){
                            $smeski = 'visual-radio-scale visual-radio-block';
                        }

						# izpišemo radio grida
						echo '<div class="variabla '.$smeski.'">';
						if ($srv_grid['other'] != 0) {
							# imamo missing vrednost
							echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
							if ($gridRow < $countVar)
								echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mm\', ' . $row1['id'] . '); rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');">';
							else
								echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, \'mm\', ' . $row1['id'] . ');' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . '">';

							// Font awesome
							echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
								((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
								'></span>';

							echo ' ' . $grid_title . '</label>';
						} else {
							# ni missing vrednost
							echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
							if ($gridRow < $countVar)
								echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mm\', ' . $row1['id'] . '); rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');">';
							else
								echo '<input type="radio" name="vrednost_' . $row1['id'] . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, \'mm\', ' . $row1['id'] . '); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . '">';

							// Font awesome
                            if($row['enota'] == 11){
                                echo '<span class="enka-vizualna-skala siv-'.$row['grids'].$value.'"></span>';
                            }elseif($row['enota'] == 12){
                                echo '<span class="enka-custom-radio '.$spremenljivkaParams->get('customRadio').'"></span>';
                            }else {
                                echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                                    ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                                    '></span>';
                            }

							echo ' ' . $grid_title . '</label>';
						}
						echo '</div>';
					}
				}

				// Dodamo se drug naslov ce imamo semanticni diferencial
				if ($row['enota'] == 1) {

					// Datapiping
					$row1['naslov2'] = Helper::dataPiping($row1['naslov2']);

					echo '<div class="dynamic_mg_vrednost">';
					echo $row1['naslov2'];
					echo '</div>';
				}

				echo '</div>';

				SurveySetting::getInstance()->Init(get('anketa'));
				$mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');

				// puscice za naprej/nazaj
				if ($row['dynamic_mg'] == 4) {
					$position = ($size - 2) * 10;
					echo '		<div style="margin-top:' . $position . 'px; float: right; text-align: center;">' . "\n";
					if ($gridRow > 1)
						echo '		<div title="' . self::$lang['back'] . '" id="arrow_up" class="arrow arrow_back" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow - 1) . '\');"></div>';
					else
						echo '		<div style="height: 24px;"></div>';
					echo '		<div id="dynamic_count">'.($countVar == 1 ? '' : $gridRow.' / '.$countVar).'</div>';
					if ($gridRow < $countVar)
						echo '		<div title="' . self::$lang['forward'] . '" id="arrow_down" class="arrow arrow_forward" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');"></div>';
					else
						echo '		<div style="height: 24px;"></div>';
					echo '		</div>' . "\n";

					// Paginacija
				} elseif ($row['dynamic_mg'] == 6 || (get('mobile') == 1 && $mobile_tables > 0)) {

					echo '</tr><tr class="gridRowArrows ' . $spremenljivka . '_gridRowArrows_' . $gridRow . ' ' . $ifDisplay . '" ' . $gridRowDisplay . '><td colspan="' . $colspan . '">';
					echo '		<table class="dynamic_mg_orientation"><tr>' . "\n";
					for ($i = 1; $i <= $countVar; $i++) {
						echo '		<td><div title="' . $i . '" id="sequence_number_' . $i . '" class="sequence_number sequence_number_'.$i.' ' . ($i == $gridRow ? ' active' : '') . '" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . $i . '\');">' . $i . '</div></td>';
					}
					echo '	</tr></table>' . "\n";
					echo '</td>';

					// puscice levo/desno
				} else {
					echo '		<table class="dynamic_mg_orientation"><tr>' . "\n";
					if ($gridRow > 1)
						echo '		<td><div title="' . self::$lang['back'] . '" id="arrow_left" class="arrow arrow_back" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow - 1) . '\');"></div></td>';
					else
						echo '		<td><div style="height: 24px;"></div></td>';
					echo '		<td><div id="dynamic_count" style="text-align: center;">'.($countVar == 1 ? '' : $gridRow.' / '.$countVar).'</div></td>';
					if ($gridRow < $countVar)
						echo '		<td><div title="' . self::$lang['forward'] . '" id="arrow_right" class="arrow arrow_forward" onClick="rowSlide(\'' . $spremenljivka . '\', \'' . $gridRow . '\', \'' . ($gridRow + 1) . '\');"></div></td>';
					else
						echo '		<td><div style="height: 24px;"></div></td>';
					echo '	</tr></table>' . "\n";
				}

				echo '		</div>' . "\n";

				$bg++;
				$gridRow++;
			}
		}	//KONEC IZRISA NAVPICNE / VERTICAL RAZLICICE
    }


}