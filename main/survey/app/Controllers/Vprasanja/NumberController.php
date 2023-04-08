<?php
/***************************************
 * Description: Number
 *
 * Vprašanje je prisotno:
 *  tip 7
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\CheckController as Check;
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Controllers\Vprasanja\SystemVariableController as SystemVariable;
use App\Models\Model;
use enkaParameters;

class NumberController extends Controller
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

        return new NumberController();
    }

    public function display($spremenljivka, $oblika)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);

        $checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);

        //tvorjenje omejitve za sliderje v mobile razlicici********************************************************************************
        $slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
        $slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);


        $limit_slider_mobile = '(min ' . $slider_MinNumLabel . ', max ' . $slider_MaxNumLabel . ')';
        //**************************************************************************************************
        //***************************


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

        if ($row['size'] == 2) {
            if ($row['num_useMin2'] == 1 && $row['num_useMax2'] == 1 && $row['num_min2'] == $row['num_max2'])
                $limit2 = '(' . $row['num_min2'] . ')';
            elseif ($row['num_useMin2'] == 1 && $row['num_useMax2'] == 1)
                $limit2 = '(min ' . $row['num_min2'] . ', max ' . $row['num_max2'] . ')';
            elseif ($row['num_useMin2'] == 1)
                $limit2 = '(min ' . $row['num_min2'] . ')';
            elseif ($row['num_useMax2'] == 1)
                $limit2 = '(max ' . $row['num_max2'] . ')';
            else
                $limit2 = '';
        }

        # preverimo ali je vrednost v bazi missing
        $missing = Check::getInstance()->checkMissingForSpremenljivka($spremenljivka, $loop_id);

        if ((int)$missing > 0) {
            $srv_data_vrednost[$missing] = true;
        }

        echo '<div class="variabla' . $oblika['cssFloat'] . '">';
        $sql1 = sisplet_query("SELECT text, text2 FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        $row1 = mysqli_fetch_array($sql1);

        $taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
        //default sirina
        if ($taWidth == -1)
            $taWidth = 10;

        //preverjanje praznega vnosa (-1)
        $row1['text'] != -1 ? $text = $row1['text'] : $text = '';
        $row1['text2'] != -1 ? $text2 = $row1['text2'] : $text2 = '';

        $row1['text'] != -1 ? $value[1] = $row1['text'] : $value[1] = '';
        $row1['text2'] != -1 ? $value[2] = $row1['text2'] : $value[2] = '';

        $sql2 = sisplet_query("SELECT id, naslov, vrstni_red, other FROM srv_vrednost WHERE spr_id='$spremenljivka' order BY vrstni_red");
        $array_others = array();

        # zloopamo skozi vrednosti in ločeno pohandlamo veljavne in neveljavnej
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            # po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row2['id']);
            if ($naslov == null || $naslov == '') {
                $naslov = $row2['naslov'];
            }

            // Datapiping
            $naslov = Helper::dataPiping($naslov);

            if ((int)$row2['other'] == 0) {
                # normalna vrednost

                # po potrebi dodamo prelom
                if ($taWidth > 40 && $row2['vrstni_red'] > 1) {
                    echo '<br>';
                }

                echo '<label for="spremenljivka_' . $spremenljivka . '_vrednost_' . $row2['vrstni_red'] . '" ' . ($row['ranking_k'] == '1' && get('mobile') == 0 ? ' style="display:none;"' : '') . '>';

                # če imamo enoto na levi jo izpišemo
                if ($row['enota'] == 1) {
                    echo $naslov;
                }

                echo ' <input type="text" class="width_' . $taWidth . '" name="vrednost_' . $spremenljivka . '[]"'
                    #. ' id="vrednost_'.$spremenljivka.'_'.$row2['vrstni_red'].'" value="'.$value[$row2['vrstni_red']].'"'
                    . ' id="spremenljivka_' . $spremenljivka . '_vrednost_' . $row2['vrstni_red'] . '" value="' . (!$missing ? $value[$row2['vrstni_red']] : '') . '"'
                    . ' onkeypress="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . ');"'
                    . ' onkeyup="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . '); checkBranching();"'
                    . (!$missing ? '' : ' disabled') . '> ' . "\n";
                # če imamo enoto na desni jo izpišemo
                if ($row['enota'] == 2) {
                    echo $naslov;
                }

                echo '</label>';

            } else {
                # imamo polje drugo - ne vem, zavrnil...
                $array_others[$row2['id']] = array(
                    'naslov' => $naslov,
                    'vrstni_red' => $row2['vrstni_red'],
                    'value' => $text[$row2['vrstni_red']],
                );

            }

            //omejitev vnosa
            if ($row['vsota_show'] == 1 && ($row['ranking_k'] != '1' /*|| get('mobile') != 0*/)) {
                if ($row2['vrstni_red'] > 1)
                    echo ' 		<label class="limit">' . $limit2 . '</label>';
                else
                    echo ' 		<label class="limit">' . $limit . '</label>';
            }
        }

        // slider na PC, no mobile
        if ($row['ranking_k'] == '1') {    //slider na PC, tablici in mobilniku

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

            
            
			if(get('lang_id') != null){
				$lang_id = get('lang_id');
				$ank_id = get('anketa');				
                
                $sqlString = "SELECT label, label_id FROM srv_language_slider WHERE ank_id='$ank_id' AND spr_id='$spremenljivka' AND lang_id='$lang_id' ORDER BY label_id";
				$sqlSlider = sisplet_query($sqlString);				
                
                while ($row = mysqli_fetch_array($sqlSlider)) {
					if($row['label_id'] == 1){
						$MinLabel = $row['label'];
                    }
                    elseif($row['label_id'] == 2){
						$MaxLabel = $row['label'];
					}elseif($row['label_id'] == 0){
						$custom = $row['label'];
					}
                }
				
				if($custom!=''){
					$custom_ar = explode('; ', $custom);	//polje za prevedene opisne labele drsnika
				}				
                
                // Ce slucajno nimamo prevedeno uporabimo original
                $MinLabel = ($MinLabel == '') ? ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : self::$lang['srv_new_text']) : $MinLabel;
                $MaxLabel = ($MaxLabel == '') ? ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : self::$lang['srv_new_text']) : $MaxLabel;
            }
            else{
				$MinLabel = ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : self::$lang['srv_new_text']);
				$MaxLabel = ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : self::$lang['srv_new_text']);
			}

			
            $slider_NumofDescrLabels = ($spremenljivkaParams->get('slider_NumofDescrLabels') ? $spremenljivkaParams->get('slider_NumofDescrLabels') : 5);
            $slider_DescriptiveLabel_defaults = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults') : 0);
            $slider_DescriptiveLabel_defaults_naslov1 = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') : 0);
            $displayMinMaxLabel = ($slider_MinMaxLabel == 0) ? ' style="display:none;"' : '';
			
			//za custom opisne labele
			//moznosti urejanja opisnih label drsnika
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
			

            //spremenljivke za labele podrocij
            $slider_labele_podrocij = ($spremenljivkaParams->get('slider_labele_podrocij') ? $spremenljivkaParams->get('slider_labele_podrocij') : 0); //za checkbox
            $display_labele_podrocij = ($slider_labele_podrocij == 0) ? ' style="display:none;"' : '';
            $slider_StevLabelPodrocij = ($spremenljivkaParams->get('slider_StevLabelPodrocij') ? $spremenljivkaParams->get('slider_StevLabelPodrocij') : 3);
            $slider_table_td_width = 100 / $slider_StevLabelPodrocij;    //spremenljivka za razporeditev sirine sliderja po podrocjih
            
            for ($i = 1; $i <= $slider_StevLabelPodrocij; $i++) {
                $slider_Labela_podrocja[$i] = ($spremenljivkaParams->get('slider_Labela_podrocja_' . $i . '') ? $spremenljivkaParams->get('slider_Labela_podrocja_' . $i . '') : self::$lang['srv_new_text']);
            }


            echo '<div style="width:95%">';


            $default_value = round(($slider_MaxNumLabel - $slider_MinNumLabel) / 2) + $slider_MinNumLabel;
            $vrednost = ($value[1] == '') ? $default_value : $value[1];


            if (get('mobile') == 0 || get('mobile') == 2) {//ce PC ali tablica
                echo '<table ' . $displayMinMaxLabel . ' class="slider_minmaxlabel">';
            } 
            else if (get('mobile') == 1) {    //ce mobilnik
                echo '<table ' . $displayMinMaxLabel . ' class="slider_minmaxlabel_mobile">';
            }
            echo '<tr>';
            echo '<td align="left">' . $MinLabel . '</td>';
            echo '<td align="right">' . $MaxLabel . '</td>';
            echo '</tr>';
            echo '</table>';


            echo '<div class="sliderText" id="sliderText_' . $spremenljivka . '">' . $vrednost . '</div>';

            // ce PC ali tablica
            if (get('mobile') == 0 || get('mobile') == 2) {
                echo '<div id="slider_' . $spremenljivka . '" class="slider"></div>';
                $slider_podrocja_table_width = 85;
            } 
            // ce mobilnik
            else if (get('mobile') == 1) {    
                echo '<div id="slider_' . $spremenljivka . '" class="slider_mobile"></div>';
                $slider_podrocja_table_width = 100;
            }


            echo '</div>';

            ?>
            <script>
                $(function () {
                    slider_init(<?=get('mobile')?>, <?=$spremenljivka?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$vrednost?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$default_value?>, <?=$slider_nakazi_odgovore?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>', '<?=$custom?>');
                });
            </script>
            <?

            echo '<br /><br />';

            //tabela za labele podrocij in podrocja
            echo '<table ' . $display_labele_podrocij . ' style="width:' . $slider_podrocja_table_width . '%; left: 5px;">';
            
            //vrstica z graficnim prikazom podrocja
            echo '<tr>';
            for ($i = 1; $i <= $slider_StevLabelPodrocij; $i++) {
                echo '<td width="' . $slider_table_td_width . '%" class="label_podrocje_prikaz"><div ></div></td>';
            }
            echo '</tr>';

            //vrstica z labelami podrocji
            echo '<tr>';
            for ($j = 1; $j <= $slider_StevLabelPodrocij; $j++) {
                echo '<td class="inline_labele_podrocij"><div id="slider_Labela_podrocja_' . $j . '_' . $spremenljivka . '" name="slider_Labela_podrocja_' . $j . '" class="inline_labele_podrocij" style="float:none; display:inline" ' . (strpos($slider_Labela_podrocja[$j], self::$lang['srv_new_text']) !== false || get('lang_id') != null ? ' default="1"' : '') . '>' . $slider_Labela_podrocja[$j] . '</div></td>';
            }
            echo '</tr>';
            echo '</table>';
        }

        echo '</div>';
        if (count($array_others) > 0) {
            $missing = 1;
            foreach ($array_others AS $oKey => $other) {
                # missing vrednost
                $_id = 'missing_value_spremenljivka_' . $spremenljivka . '_vrednost_' . $oKey;

                if ($srv_data_vrednost[$oKey]) {
                    $sel = true;
                } else {
                    $sel = false;
                }
                # če nimamo missingov in je trenutni enak izbranemu, ali če imamo misinge inje trenutni enak izbranemu misingu
                $_checked = ($sel ? ' checked' : '');
                // Tole ni definirano in ne more delat?? zakaj bi sploh kadarkoli bil missing disabled??
                //$_disabled = ($is_missing && ($row1['other'] == 0 || $row1['other'] == 1) ? true : false);
                $_disabled = false;


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

                echo '<div class="variabla' . $oblika['cssFloat'] . ' missing"  id="vrednost_if_' . $oKey . '"' . ' ' . ($hide_missing ? ' style="display:none"' : '') . '>';
                echo '<label for="' . $_id . '">';
                echo '<input type="checkbox" name="vrednost_mv_' . $spremenljivka . '[]" id="' . $_id . '" value="' . $oKey . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="0" onclick="checkBranching(); ' . ($checkbox_limit > 0 ? 'checkboxLimit(\'' . $spremenljivka . '\', \'' . $oKey . '\', \'' . $checkbox_limit . '\');' : '') . ' checkMissing(this);"> ';
                // Font awesome checkbox
				echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';
				echo '' . $other['naslov'] . '</label>';
                echo '</div>';
            }
        }

        SystemVariable::display($spremenljivka, $oblika);
    }

}