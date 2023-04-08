<?php
/***************************************
 * Description: Textbox, Multitext
 *
 * Vprašanje je prisotno:
 *  tip 21
 *  tip 19
 *  tip 20
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
use AppSettings;

class TextController extends Controller
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

        return new TextController();
    }


    /************************************************
     * Stara funkcija $this->displayMultitext($spremenljivka)
     ************************************************/
    public function multitext($spremenljivka)
    {
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // izracuni za sirino celic
        $size = $row['grids'];

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
        $gridAlign = (($spremenljivkaParams->get('gridAlign') > 0) ? $spremenljivkaParams->get('gridAlign') : 0);
        $cssAlign = '';
        if ($gridAlign == 1)
            $cssAlign = ' alignLeft';
        elseif ($gridAlign == 2)
            $cssAlign = ' alignRight';

        //$css = ' style = "width: '.$gridWidth.'%;" ';

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

        echo '     <table class="grid_table multitext">' . "\n";

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
            for ($i = 1; $i <= $mv_count; $i++)
                echo '<col>';
            echo '</colgroup>';
        }

        echo '<thead>';

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
        if ($taWidth == -1) {
            $taWidth = 80;
        } else {
            //$taWidth = $taWidth * 10 * 2; // da dobimo % (opcije se od 1 - 5)
            $taWidth = $taWidth * 10; // da dobimo % (opcije se od 1 - 9)
        }

        $bg = 1;

        # Izpišemo TOP vrstico z labelami
        if (count($srv_grids) > 0) {
            $first_missing_value = true;
            foreach ($srv_grids AS $g_id => $srv_grid) {
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                    # dodamo spejs pred manjkajočimi vrednostmi
                    echo '<td></td>';
                    $first_missing_value = false;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                # izpišemo labelo grida
                echo '<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
            }
        }
        echo '        </tr>' . "\n";

        echo '</thead>';

        echo '<tbody>';

        $bg++;

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


        $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
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

            echo '        <tr id="vrednost_if_' . $row1['id'] . '"'/*.($row1['if_id']>0?' style="display:none"':'')*/ . '' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '>' . "\n";

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
            if (count($srv_grids) > 0) {
                $first_missing_value = true;
                foreach ($srv_grids AS $i => $srv_grid) {

                    if ($srv_grid['other'] != 0)
                        $grid_id = $srv_data_grid[$row1['id']]['grd_id'];
                    else
                        $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];

                    $value = $srv_grid['id'];
                    $vsebina = '';

                    if ($grid_id == $value) {

                        $vsebina = $srv_data_cache[$row1['id']][$i]['text'];

                        //$row2 = mysqli_fetch_array($sql2);
                    }

                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '<td></td>';
                        $first_missing_value = false;
                    }
                    # izpišemo labelo grida
                    //multitext
                    if ($row['tip'] == 19) {
                        if ($srv_grid['other'] != 0) {
                            # imamo missing nardimo radio
                            echo '<td class="missing ' . $cssAlign . '">';
                            echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
							echo '<input type="radio" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching();">';
                            
							 // Font awesome
							echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
								((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
								'></span>';
							echo '</label>';
							echo '</td>' . "\n";
                        } else {
                            echo '<td class="category ' . $cssAlign . '">';
                            //echo '<input type="text" style="width:'.$taWidth.'em;" name="vrednost_'.$row1['id'].'_grid_'.$value.'" id="vrednost_'.$row1['id'].'_grid_'.$value.'" value="'.($is_missing ? '' : $vsebina).'" calculation="'.$srv_grid['variable'].'" '.($is_missing ? ' disabled class="disabled"' : '').'>';
                            echo '<textarea class="width_' . $taWidth . '" rows="' . $taHeight . '" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeyup="checkBranching();">' . ($is_missing ? '' : $vsebina) . '</textarea>';
                            echo '</td>' . "\n";
                        }

                        //multinumber - rabimo JS checkNumber
                    } else {
                        if ($srv_grid['other'] != 0) {
                            # imamo missing nardimo radio
                            echo '<td class="missing ' . $cssAlign . '">';
							echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                            echo '<input type="radio" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching();">';
                            
							 // Font awesome
							echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
								((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
								'></span>';
							echo '</label>';
							echo '</td>' . "\n";
                        } else {
                            echo '<td class="category ' . $cssAlign . '">';
                            echo '<input type="text" class="width_' . $taWidth . '" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . ($is_missing ? '' : $vsebina) . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeypress="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . ');" onkeyup="checkNumber(this, ' . $row['cela'] . ', ' . $row['decimalna'] . '); checkBranching();">';

                            //multislider
                            //if ($row['ranking_k'] == 1 && get('mobile') == 0) {
                            if ($row['ranking_k'] == 1) {    //slider na PC, tablici in mobilniku
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
								
                                //echo '<div style="width:100%">';
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

                                //echo '<div style="display:inline-block;">'.$row['vsota_min'].'</div>';
                                //echo '<div id="slider_'.$spremenljivka.'_'.$row1['id'].'" class="slider"></div>';
                                //echo '<div style="display:inline-block;">'.$row['vsota_limit'].'</div>';
                                if (get('mobile') == 0 || get('mobile') == 2) {//ce PC ali tablica
                                    echo '<div id="slider_' . $spremenljivka . '_' . $row1['id'] . '" class="slider"></div>';
                                } else if (get('mobile') == 1) {    //ce mobilnik
                                    echo '<div id="slider_' . $spremenljivka . '_' . $row1['id'] . '" class="slider_grid_mobile"></div>';
                                }

                                echo '</div>';
								
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

                                ?>
                                <script>
                                    $(function () {										
                                        slider_grid_init(<?=get('mobile')?>, <?=$spremenljivka?>, <?=$row1['id']?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$vrednost?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$default_value?>, <?=$slider_nakazi_odgovore?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>', '<?=$custom?>');										
                                    });
                                </script>
                                <?
                            }

                            echo '</td>' . "\n";

                        }
                    }
                }
            }

            echo '        </tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '      </table>' . "\n";
		
		// Izpis prejsnjih odgovorov (ce imamo vklopljeno nastavitev)
		$prevAnswers = ($spremenljivkaParams->get('prevAnswers') ? $spremenljivkaParams->get('prevAnswers') : 0);
		if($prevAnswers == 1)
			self::display_prevAnswers($spremenljivka);
    }

    /************************************************
     * Stara funkcija $this->displayTextbox($spremenljivka)
     ************************************************/
    public function textbox($spremenljivka, $oblika)
    {
        global $lang;
        
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);

        if($row['num_useMin'] == 1 && $row['num_useMax'] == 1 && $row['vsota_min'] == $row['vsota_limit'])
                $limit = '('.$lang['srv_text_length_char_num'].$row['vsota_min'].')';
        elseif($row['num_useMin'] == 1 && $row['num_useMax'] == 1)
                $limit = '('.$lang['srv_text_length_char_num'].'min '.$row['vsota_min'].', max '.$row['vsota_limit'].')';
        elseif($row['num_useMin'] == 1)
                $limit = '('.$lang['srv_text_length_char_num'].'min '.$row['vsota_min'].')';
        elseif($row['num_useMax'] == 1)
                $limit = '('.$lang['srv_text_length_char_num'].'max '.$row['vsota_limit'].')';
        else
                $limit = '';

        $taSize = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
        $taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
        $max_text_length = ($row['vsota_limit'] > 0 ? ' maxlength="'.$row['vsota_limit'].'"' : '');
		$email_verify = ($spremenljivkaParams->get('emailVerify') ? $spremenljivkaParams->get('emailVerify') : 0);

        //default sirina
        if ($taWidth == -1)
            $taWidth = 30;

        # preverimo ali je vrednost v bazi missing
        $missing = Check::getInstance()->checkMissingForSpremenljivka($spremenljivka, $loop_id);

        if ((int)$missing > 0) {
            $srv_data_vrednost[$missing] = true;
        }

        // Ce imamo slucajno vklopljeno nastavitev da so odgovori disabled
        $disabled_vprasanje = ($spremenljivkaParams->get('disabled_vprasanje') == '1') ? true : false;

        if ($row['upload'] == 1 || $row['upload'] == 2 )    // max size
            echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * 1024 * 100) . '">';

        echo '<table class="text_vrednost"><tbody>' . "\n";
        if ($row['orientation'] != 3)
            echo '<tr>';

        # za polja drugo
        $array_others = array();

        $sql1 = sisplet_query("SELECT id, naslov, vrstni_red, other, size, naslov2 FROM srv_vrednost WHERE spr_id='$row[id]' AND vrstni_red > 0 ORDER BY vrstni_red");

        $i = 1;
        while ($row1 = mysqli_fetch_array($sql1)) {
            
            # če ni polje drugo
            if ((int)$row1['other'] == 0) { 

                $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
                if ($naslov != '') $row1['naslov'] = $naslov;

                $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND vre_id='$row1[id]' AND loop_id $loop_id");
                $row2 = mysqli_fetch_array($sql2);

				
                // sirina celice td
                $cell = $row['text_kosov'] == 1 ? 100 : $row1['size'];
                $width = round(100 / $row['text_kosov'], 0);
                // sirina vnosnega polja
                $input = $taWidth;

                if ($row['orientation'] == 3)
                    echo '<tr>';
                echo '<td class="variabla">' . "\n";


                // Datapiping
                $row1['naslov'] = Helper::dataPiping($row1['naslov']);

                //izpisi
                if ($row['text_orientation'] == 1 || $row['text_orientation'] == 3) {
                    echo '<label for="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '">' . $row1['naslov'] . '</label>' . "\n";

                    if ($row['text_orientation'] == 3)
                        echo '<br>';
                }

				
                // Ni signature ali upload
                if ($row['upload'] == 0 && $row['signature'] == 0) {

                    // if captcha == 1
                    $spremenljivkaParams = new enkaParameters($row['params']);
                    $captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);

                    if ($captcha == 1) {
                        echo '<div class="g-recaptcha" data-sitekey="'.AppSettings::getInstance()->getSetting('google-recaptcha_sitekey').'"></div>';                    
                    }
                    else {
                        $char_counter_events = $limit ? 'charCounter(this);' : '';
                        
						// Ce gre za email preverjamo pravilnost na blur in ne na keyup
						$js_trigger = ($email_verify == 1) ? 'onBlur' : 'onKeyUp';
						
                        echo '<label for="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '">';
                        
                        if ($taSize > 1) {
                            echo '<textarea name="vrednost_' . $spremenljivka . '_kos_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '" '.($disabled_vprasanje ? ' disabled="disabled"' : '').' rows="' . $taSize . '" class="width_' . $input . '" onkeyup="checkBranching();'.$char_counter_events.'"' . (!$missing && !$disabled_vprasanje ? '' : ' disabled') . $max_text_length. '>' . (!$missing ? $row2['text'] : '') . '</textarea>';
                        } else {
                            echo '<input type="text" name="vrednost_' . $spremenljivka . '_kos_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '" class="width_' . $input . '" '.($disabled_vprasanje ? ' disabled="disabled"' : '').' '.$js_trigger.'="checkBranching();'.$char_counter_events.'" value="' . (!$missing ? $row2['text'] : '') . '" ' . (!$missing && !$disabled_vprasanje ? '' : ' disabled') . $max_text_length. '>';
                        }
                        
                        // Ce je vprasanje disabled moramo vseeno postati vrednost
                        if($disabled_vprasanje && !$missing){
                            echo '<input type="hidden" name="vrednost_' . $spremenljivka . '_kos_' . $row1['id'] . '" value="'.$row2['text'].'">';
                        }

                        // prikazi counter znakov
                        if ($limit && !get('printPreview')) {
                            echo '<span id="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '_counter" class="char_counter"></span>';
                            echo '<script>set_charCounter(\'spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '\');</script>';
                        }
                        
                        echo '</label>';
                    }

                } // Signature
                elseif ($row['signature'] != 0 && $row['upload'] == 0) {
                    //***novo
                    echo '  <script src="' . self::$site_url . 'main/survey/js/signature/jquery.signaturepad.js"></script>' . "\n";
                    echo '  <!--[if lt IE 9]><script src="' . self::$site_url . 'main/survey/js/signature/flashcanvas.js"></script><![endif]-->' . "\n";
					
					//za ureditev vpogleda v posamezno enoto
					$quick_view = json_encode(get('quick_view'));
					
					// options za risanje signature in ostale spremenljivke
					echo '<script>
							if('.$quick_view.'==1){
								$("#clear_spremenljivka_' . $spremenljivka . '").attr("disabled", true);
								//console.log("Izklopi gumb");
							}				
							optionsPodpis[' . $spremenljivka . '] = {	//options za signature knjižnico, ki niso default vrednosti
									drawOnly : true													//samo risanje podpisa
									, clear: "#clear_spremenljivka_' . $spremenljivka . '"		//gumb oz. polje za brisanje ima id
									, output: "#signature-data_spremenljivka_' . $spremenljivka . '"	//polje, kjer se prikaže output oz. podpis v json obliki
									, validateFields: false											//preverjanje vnosa podpisa in imena se ne izvaja
								};
							
						</script>	
						';

                    //***novo

                    //$sqlSignature = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id = '" . get('usr_id') . "'");
                    $sqlSignature = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id = '" . get('usr_id') . "' AND code='" . $spremenljivka . "' ");
                    if (mysqli_num_rows($sqlSignature) > 0) {
                        $rowSignature = mysqli_fetch_array($sqlSignature);
                        $signaturefile = $rowSignature[0];


                        echo '
							<script>
								var polje_imena ="#spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '"
								$(document).ready(
									function(){
										
										$("#canvas_' . $spremenljivka . '").hide();
										$("#podpis_slika_' . $spremenljivka . '").show();
										if(' . sizeof($row2['text']) . ' > 0){
											$("#spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '").val("' . $row2['text'] . '");
										}
										//$("#clear_spremenljivka_' . $spremenljivka . '").mousedown(function(){	//ob kliku na gumb miške dol
										$("#clear_spremenljivka_' . $spremenljivka . '").click(function(){	//ob kliku
											$("#canvas_' . $spremenljivka . '").show();							//pokazi canvas za podpis
											$("#canvas_' . $spremenljivka . ' .sigWrapper").addClass("current");
											podpisposlan[' . $spremenljivka . '] = 0;
											$("#podpis_slika_' . $spremenljivka . '").remove();					//odstrani sliko podpisa
											$.post("' . self::$site_url . 'main/survey/ajax.php?a=usr_id_data",		//poslji podatke za brisanje podatkov o sliki iz baze in brisanje datoteke iz diska
												{usr_id: ' . get('usr_id') . ', anketa: ' . srv_meta_anketa_id . ', spr_id: ' . $spremenljivka . ', vre_id: ' . $row1[id] . '});
										});
										
										$("#signature-pad_spremenljivka_' . $spremenljivka . '").signaturePad(optionsPodpis[' . $spremenljivka . ']);	//omogoci risanje na canvas

										$("#signature-pad_spremenljivka_' . $spremenljivka . '").mouseout(function(){	//ob izhodu miške iz površine za podpisovanje
											if(!$("#signature-data_spremenljivka_' . $spremenljivka . '").val()){	//če ni nobenega podpisa
												//console.log("Prazno!");
											}else{																//drugače, če je podpisa prisoten
												if(podpisposlan[' . $spremenljivka . '] == 0){
													podpisposlan[' . $spremenljivka . '] = 1;

													var signaturedata = $("#signature-pad_spremenljivka_' . $spremenljivka . '").signaturePad(optionsPodpis[' . $spremenljivka . ']).getSignatureImage();
													//console.log(signaturedata);
													var signaturename = $("#signature-name_spremenljivka_' . $spremenljivka . '").val();
													$("#signature-data_spremenljivka_' . $spremenljivka . '").val(signaturedata);
													$("#signature-name_spremenljivka_' . $spremenljivka . '").val(signaturename);
												}
											}
										});
									}
								);

							</script>
						';
                        //echo $rowSignature[0];
                    } else {

                        //predelano - začetek urejanja javascript kode za delovanje podpisa
                        echo '
						<script>
							$(document).ready(
							function(){
								
								podpisposlan[' . $spremenljivka . '] = 0;
								
								if('.$quick_view.'){
									$("#clear_spremenljivka_' . $spremenljivka . '").attr("disabled", true);
								}else{
									$("#signature-pad_spremenljivka_' . $spremenljivka . '").signaturePad(optionsPodpis[' . $spremenljivka . ']);	//omogoci risanje na canvas
								}
								


								$("#clear_spremenljivka_' . $spremenljivka . '").click(function(){	//ob kliku na gumb Clear
									//console.log("Brišem");
									podpisposlan[' . $spremenljivka . '] = 0;
									$("#spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '").val("");//počisti polje z imenom
								});

								$("#signature-pad_spremenljivka_' . $spremenljivka . '").mouseout(function(){	//ob izhodu miške iz površine za podpisovanje

									if(!$("#signature-data_spremenljivka_' . $spremenljivka . '").val()){	//če ni nobenega podpisa
										//console.log("Prazno!");
									}else{															//drugače, če je podpisa prisoten
										//console.log("Nekaj je");
										if(podpisposlan[' . $spremenljivka . '] == 0){
											podpisposlan[' . $spremenljivka . '] = 1;

											var signaturedata = $("#signature-pad_spremenljivka_' . $spremenljivka . '").signaturePad(optionsPodpis[' . $spremenljivka . ']).getSignatureImage();
											//console.log(signaturedata);
											var signaturename = $("#signature-name_spremenljivka_' . $spremenljivka . '").val();
											$("#signature-data_spremenljivka_' . $spremenljivka . '").val(signaturedata);
											$("#signature-name_spremenljivka_' . $spremenljivka . '").val(signaturename);
										}
									}
								});

								$("#signature-pad_spremenljivka_' . $spremenljivka . '").click(function(){	//ob kliku na gumb površino za podpisovanje
									podpisposlan[' . $spremenljivka . '] = 0;
								});
							}
							);
						</script>
						';


                        //predelano - konec
                    }

                    // Dobimo sirino polja - ce je mobile ga pomanjsamo
                    if(get('mobile') == 1){
                        $width = 300;
                        $height = 150;
                    }
                    else{
                        $width = 600;
                        $height = 200;
                    }

                    echo '					
						<div id="signature-pad_spremenljivka_' . $spremenljivka . '">
							<div id="canvas_' . $spremenljivka . '">

								<div class="sig sigWrapper">
									<canvas width="'.$width.'" height="'.$height.'" style="border:1px solid black"></canvas>
								</div>
							</div>
							<div id="podpis_slika_' . $spremenljivka . '" hidden>
								<img src="' . self::$site_url . 'main/survey/uploads/' . $signaturefile . '" style="width: '.$width.'px; height: '.$height.'px; border:1px solid black">
							</div>
							<div>

							<input id="clear_spremenljivka_' . $spremenljivka . '" type="button" value="' . self::$lang['srv_signature_clear'] . '" class="sig_clear_button" style="width: '.$width.'px;">
								
							<input id="signature-data_spremenljivka_' . $spremenljivka . '" type="text" value="" name="signature-data_spremenljivka_' . $spremenljivka . '" hidden><br />
					';


                    echo '
							' . self::$lang['srv_signature_name'] . ' ' . '<input type="text" name="vrednost_' . $spremenljivka . '_kos_' . $row1['id'] . '"  id="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '" ' . (!$missing ? '' : ' disabled') . '><br />

							</div>
						</div>
					';
                } 
                
                // Smo v admin podatkih in uplodamo datoteko ali fotografijo
                elseif( ($row['upload'] == 1 || $row['upload'] == 2) && ($_GET['t'] == 'postprocess' || $_GET['m'] == 'quick_edit') ){

                    $sqlUpload = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id='".get('usr_id')."' AND code='".$row2['text']."'");

                    // Ze imamo datoteko - moznost brisanja v adminu
                    if(mysqli_num_rows($sqlUpload) > 0){

                        $rowUpload = mysqli_fetch_array($sqlUpload);
                        $file = $rowUpload[0];
    
                        global $site_url;
                        echo '<div style="font-size:14px;"><a href="'.$site_url.'/main/survey/download.php?anketa='.get('anketa').'&code='.$row2['text'].'">'.$file.'</a></div>';

                        // Remove file button
                        if($_GET['quick_view'] != '1'){
                            echo '<div class="buttonwrapper floatLeft">
                                    <a 
                                        class="ovalbutton ovalbutton_orange btn_savesettings" 
                                        href="#"
                                        style="margin: 10px 0 0 0;"
                                        id="remove_file_'.$spremenljivka.'_vrednost_'.$i.'" 
                                        onClick="removeUploadFromData(\''.get('usr_id').'\', \''.$spremenljivka.'\', \''.$row2['text'].'\')"
                                    >';
                            echo self::$lang['srv_alert_upload_remove'];
                            echo '</div>';
                        }
                    }
                    // Uploadamo datoteko preko admina - TODO
                    else{

                        echo '<input name="vrednost_' . $spremenljivka . '_kos_' . $row1['id'] . '" 
                                type="file" 
                                id="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '"
                                class="pointer"
                        >';
                    }
                }

                // Upload
                elseif ($row['upload'] == 1) {

                    echo '<input name="vrednost_' . $spremenljivka . '_kos_' . $row1['id'] . '" 
                                type="file" 
                                id="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '"
                                    ' . (!$missing ? '' : ' disabled') . ' 
                                class="pointer"
                                onChange="checkUpload(this, \''.$spremenljivka.'_vrednost_'.$i.'\');"
                        >';

                    // Remove file button
                    echo '<div class="remove_file pointer" 
                                id="remove_file_'.$spremenljivka.'_vrednost_'.$i.'" 
                                style="display:none;" 
                                onClick="removeUpload(\'spremenljivka_'.$spremenljivka.'_vrednost_'.$i.'\')"
                            >';
                    echo self::$lang['srv_alert_upload_remove'];
                    echo '</div>';
                }

                // Fotografiranje
                elseif ($row['upload'] == 2) {

                    $inpname = 'vrednost_' . $spremenljivka . '_kos_' . $row1['id'];
                    $inpid = 'spremenljivka_' . $spremenljivka . '_vrednost_' . $i;
                    
                    echo '<label class="custom-foto-upload" for="'.$inpid. '"></label>';
                    echo '<input class="upload_foto_file" name="'.$inpname . '" type="file" accept="image/*" id="'.$inpid. '" capture="camera">';
                    echo '<div class="fotoresults_delete upload_fotoresults_delete pointer" id="upload_fotoresults_delete_'.$inpid.'" onClick="delete_upload_foto(\''.$inpid.'\');">'.$lang['srv_alert_foto_remove'].'</div>';
                    
                    echo '<div id="upload_foto_result_'.$inpid.'_holder">
                        <img id="upload_foto_result_'.$inpid. '" class="upload_foto_result" src="#">
                    </div>';
                    
                    echo '<div class="fotoparent" id="fotoparent_'.$inpid.'" style="visibility: hidden; display : none;">
                        <div class="fotoresults_div">
                            <p style="padding: 4px;"><i>'.self::$lang['srv_resevanje_foto_result_title'].'</i></p>
                            
                            <div class="fotoresults" id="fotoresults_'.$inpid.'">
                                <p>'.self::$lang['srv_resevanje_foto_pre_result'].'</p>
                            </div>
                            <div class="fotoresults_delete pointer" id="fotoresults_delete_'.$inpid.'" onClick="delete_snapshot(\''.$inpid.'\');">'.$lang['srv_alert_foto_remove'].'</div>
                        </div>
                        <input id="foto_'.$inpid.'" type="hidden" name="foto_'.$inpname.'" value=""/>

                        <div class="my_camera_div">
                            <p style="padding: 4px;"><i>'.self::$lang['srv_resevanje_foto_snap_title'].'</i></p>

                            <div class="my_camera" id="my_camera_'.$inpid.'"></div>

                            <!-- A button for taking snaps -->
                            <button type="button" class="record_foto" onClick="take_snapshot(\''.$inpid.'\');"></button>
                        </div>
                    </div>';
                    ?>	
                                
                    <!-- Configure a few settings and attach camera -->
                    <script language="JavaScript">
                        //dobi osnovni id iputa spremenljivke
                        var inpid = '<?php echo $inpid; ?>';
                        
                        //deklarira vse potrebno za funkcionalnost fotografiranja
                        FotoDeclaration(inpid, '<?php echo self::$site_url; ?>');
                        
                        //prikazi uploadano datoteko v primeru navadnega uploada ali prek mobilnika
                        function readURL(input) {
                            if (input.files && input.files[0]) {
                                var reader = new FileReader();

                                reader.onload = function (e) {
                                    $('#upload_foto_result_'+inpid).css("display", "inline"); 
                                    $('#upload_foto_result_'+inpid).attr('src', e.target.result);
                                    $('#upload_fotoresults_delete_'+inpid).show();
                                };

                                reader.readAsDataURL(input.files[0]);
                            }
                        }

                        //ko se navadni upload spremeni, zazeni F za prikaz slike
                        $("#"+inpid).change(function(){
                            readURL(this);
                        });
                    </script>
                    <?php
                }


                if ($row['text_orientation'] == 2) {
                    echo '<br><label for="spremenljivka_' . $spremenljivka . '_vrednost_' . $i . '">' . $row1['naslov'] . '</label>' . "\n";
                }
                   
                // omejitev vnosa
                if ($row['vsota_show'] == 1) {
                    echo '<label class="limit">' . $limit . '</label>';
                }

                echo '</td>' . "\n";
                if ($row['orientation'] == 3)
                    echo '</tr>';
            } 
            else {
                # imamo polje drugo - ne vem, zavrnil...
                $array_others[$row1['id']] = array(
                    'naslov' => $row1['naslov'],
                    'vrstni_red' => $row1['vrstni_red']
                );
            }

            $i++;
        }

        if ($row['orientation'] != 3)
            echo '</tr>';

        echo '</tbody></table>' . "\n";

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

                $naslov = Language::getInstance()->srv_language_vrednost($oKey);
                if ($naslov != '') $other['naslov'] = $naslov;

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

                $checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);

                echo '<div class="variabla' . $oblika['cssFloat'] . ' missing"  id="vrednost_if_' . $oKey . '"' . ' ' . ($hide_missing ? ' style="display:none"' : '') . '>';
                echo '<label for="' . $_id . '">';
                echo '<input type="checkbox" name="vrednost_mv_' . $spremenljivka . '[]" id="' . $_id . '" value="' . $oKey . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="1" onclick="checkBranching(); ' . ($checkbox_limit > 0 ? 'checkboxLimit(\'' . $spremenljivka . '\', \'' . $oKey . '\', \'' . $checkbox_limit . '\');' : '') . ' checkMissing(this);"> ';
                // Font awesome checkbox
                echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';
				echo '' . $other['naslov'] . '</label>';
                echo '</div>';
            }
        }

        SystemVariable::display($spremenljivka, $oblika);
		
		// Izpis prejsnjih odgovorov (ce imamo vklopljeno nastavitev)
		$prevAnswers = ($spremenljivkaParams->get('prevAnswers') ? $spremenljivkaParams->get('prevAnswers') : 0);
		if($prevAnswers == 1)
			self::display_prevAnswers($spremenljivka);
    }


	// Prikazemo stare odgovore pod vprasanjem (nastavitev prevAnswers)
	private function display_prevAnswers($spremenljivka){
						
		echo '<div class="text_prevAnswers">';
		echo self::$lang['srv_prevAnswers'].':';	
		
		echo '<div class="text_prevAnswers_list">';
		
		//$sql = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id!='" . get('usr_id') . "' AND vre_id='$row1[id]' AND loop_id $loop_id");
		$sql = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='".$spremenljivka."' AND usr_id!='".get('usr_id')."' ORDER BY id DESC limit 30");
		while($row = mysqli_fetch_array($sql)){	
			echo '<p>';
			echo $row['text'];
			echo '</p>';
		}
		
		$sqlC = sisplet_query("SELECT count(*) FROM srv_data_text" . get('db_table') . " WHERE spr_id='".$spremenljivka."' AND usr_id!='".get('usr_id')."'");
		$rowC = mysqli_fetch_array($sqlC);
		if($rowC['count(*)'] > 30){
			// gumb "vec" da prikazemo vse
			echo '<span class="more" onClick="show_prevAnswers_all(\''.$spremenljivka.'\');">('.self::$lang['srv_more'].'...)</span>';
			
			// div za izris vseh
			echo '<div id="text_prevAnswers_popup_'.$spremenljivka.'" class="text_prevAnswers_popup"></div>';
		}
		
		echo '</div>';
		
		echo '</div>';
	}
}