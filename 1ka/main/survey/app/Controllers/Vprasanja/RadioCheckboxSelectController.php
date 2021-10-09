<?php
/***************************************
 * Description: Izpis sledečih tipov vprašanj: radio, checkbox in select
 *
 * Vprašanje je prisotno:
 *  tip 1
 *  tip 2
 *  tip 3
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

class RadioCheckboxSelectController extends Controller
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

        return new RadioCheckboxSelectController();
    }


    public function display($spremenljivka, $oblika = null)
    {
        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");
		
		$spremenljivkaParams = new enkaParameters($row['params']);
        $stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);
        $checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);
             
        // Ce imamo slucajno vklopljeno nastavitev da so odgovori disabled
        $disabled_vprasanje = ($spremenljivkaParams->get('disabled_vprasanje') == '1') ? true : false;

        SurveySetting::getInstance()->Init(get('anketa'));
        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        $srv_dropdown_select = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_dropdown_select' . $_lang);
        if ($srv_dropdown_select == '') $srv_dropdown_select = self::$lang['srv_dropdown_select'];

        // DROPDOWN
        if ($row['tip'] == 3) {
            $spremenljivkaParams = new enkaParameters($row['params']);
            $prvaVrstica_roleta = ($spremenljivkaParams->get('prvaVrstica_roleta') ? $spremenljivkaParams->get('prvaVrstica_roleta') : 1);

            echo '<div class="variabla' . $oblika['cssFloat'] . '">';

            if (!$row['info']) {
                echo '      <select name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '" '.($disabled_vprasanje ? ' disabled="disabled"' : '').' size="' . ($row['orientation'] == '6' ? (mysqli_num_rows($sql1) + 1) . '" multiple' : '1"') . ' onchange="drugo_' . $spremenljivka . '(); checkBranching(); clickSelectBox(' . $spremenljivka . ', '.$checkbox_limit.'); omejiSelectBox(' . $spremenljivka . ');">' . "\n";
            } 
            elseif ($row['info']) {
                echo '      <select name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '" '.($disabled_vprasanje ? ' disabled="disabled"' : '').' size="' . ($row['orientation'] == '6' ? (mysqli_num_rows($sql1) + 1) . '" multiple' : '1"') . ' onchange="drugo_' . $spremenljivka . '(); checkBranching(); ">' . "\n";
            }

            switch ($prvaVrstica_roleta) {
               
                case "1":
                    echo '        <option value=""></option>' . "\n";
                    break;
                
                case "2":
                    break;

                case "3":
                    echo '        <option value="">' . $srv_dropdown_select . '...</option>' . "\n";
                    break;
            }
        }

        // CHECKBOX
        if ($row['tip'] == 2) {
            
            if ($row['orientation'] != 6) {
                $selected = Model::getOtherValue($spremenljivka);
                echo '<input name="other_selected_vrednost_' . $spremenljivka . '" id="other_selected_vrednost_' . $spremenljivka . '" value="' . $selected . '" type="hidden">';
            }    
            // Izberite s seznama
            elseif ($row['orientation'] == 6) {
                $spremenljivkaParams = new enkaParameters($row['params']);
                $sbSize = ($spremenljivkaParams->get('sbSize') ? $spremenljivkaParams->get('sbSize') : 1);
                $prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
               
                if ($prvaVrstica != 1) {
                    $sbSize = $sbSize + 1;
                }

                echo '<div class="variabla' . $oblika['cssFloat'] . '">';
                
                if ($sbSize == 2) {    //potrebno dodati predefinirano visino min 36px, ce sta samo dve moznosti v selectu, ker v FF, ni videti scrollbar-a
                    echo '      <select multiple style="height: 36px;" name="vrednost_' . $spremenljivka . '[]" id="vrednost_' . $spremenljivka . '" size="' . $sbSize . '" onclick="drugo_' . $spremenljivka . '(); checkBranching(); clickSelectBox(' . $spremenljivka . ', '.$checkbox_limit.'); ">' . "\n";
                } 
                else {
                    echo '      <select multiple name="vrednost_' . $spremenljivka . '[]" id="vrednost_' . $spremenljivka . '" size="' . $sbSize . '" onclick="drugo_' . $spremenljivka . '(); checkBranching(); clickSelectBox(' . $spremenljivka . ', '.$checkbox_limit.'); ' . ($checkbox_limit > 0 ? 'checkboxLimit(\'' . $spremenljivka . '\', \'' . $row1['id'] . '\', \'' . $checkbox_limit . '\');' : '') . '">' . "\n";
                }

                switch ($prvaVrstica) {
                    case "1":

                        break;
                    case "2":
                        echo '        <option value=""></option>' . "\n";
                        break;
                    case "3":
                        echo '        <option value="">' . $srv_dropdown_select . '...</option>' . "\n";
                        break;
                }
            }
        }

        // RADIO
        if ($row['tip'] == 1) {

            if ($row['orientation'] == 6) {

                $spremenljivkaParams = new enkaParameters($row['params']);
                $sbSize = ($spremenljivkaParams->get('sbSize') ? $spremenljivkaParams->get('sbSize') : 1);
                $prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);

                if ($prvaVrstica != 1) {
                    $sbSize = $sbSize + 1;
                }

                echo '<div class="variabla' . $oblika['cssFloat'] . '">';
               
                if ($sbSize == 2) {    //potrebno dodati predefinirano visino min 36px, ce sta samo dve moznosti v selectu, ker v FF, ni videti scrollbar-a
                    echo '      <select style="height: 36px;" name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '"  size="' . $sbSize . '" onclick="drugo_' . $spremenljivka . '(); checkBranching(); clickSelectBox(' . $spremenljivka . ', '.$checkbox_limit.');">' . "\n";
                } 
                else {
                    echo '      <select name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '"  size="' . $sbSize . '" onclick="drugo_' . $spremenljivka . '(); checkBranching(); clickSelectBox(' . $spremenljivka . ','.$checkbox_limit.');">' . "\n";
                }

                switch ($prvaVrstica) {
                    case "1":
                        break;
                    case "2":
                        echo '        <option value=""></option>' . "\n";
                        break;
                    case "3":
                        echo '        <option value="">' . $srv_dropdown_select . '...</option>' . "\n";
                        break;
                }
            }
        }

        // cache, da vse preberemo naenkrat
        $srv_data_vrednost = array();
        $sql2_c = sisplet_query("SELECT vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row2_c = mysqli_fetch_array($sql2_c)) {
            $srv_data_vrednost[$row2_c['vre_id']] = true;
        }
        # preverimo ali imamo izbran kak missing
        $is_missing = false;
        while ($row1 = mysqli_fetch_array($sql1)) {
            if ($row1['other'] == 0 || $row1['other'] == 1) {
            } else {
                if ($srv_data_vrednost[$row1['id']]) {
                    $is_missing = true;
                }
            }
        }
        if (mysqli_num_rows($sql1))
            mysqli_data_seek($sql1, 0);

        $spremenljivkaParams = new enkaParameters($row['params']);
        $stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);
        $checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);
		$checkbox_min_limit = ($spremenljivkaParams->get('checkbox_min_limit') ? $spremenljivkaParams->get('checkbox_min_limit') : 0);

        // Ali skrivamo radio gumbe in checkboxe
        $hideRadio = ($spremenljivkaParams->get('hideRadio') == 1) ? ' hideRadio' : '';

        // Ali imamo prednastavljen radio ali checkbox (ce se nimamo odgovora)
        $presetValue = ($spremenljivkaParams->get('presetValue') > 0 && empty($srv_data_vrednost)) ? $spremenljivkaParams->get('presetValue') : 0;

        if ($stolpci > 1 && $row['orientation'] == 1 && get('mobile') != 1) {
            echo '<div class="floatLeft width_' . round(100 / $stolpci, 0) . '">';
            $kategorij = mysqli_num_rows($sql1);
            $v_stolpcu = ceil($kategorij / $stolpci);
        }

        $i = 0;
        while ($row1 = mysqli_fetch_array($sql1)) {

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            if ($row1['other'] == 0 || $row1['other'] == 1) {
                # normalna vrednost
                $_id = 'spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'];
                $missing = 0;
            } else {
                # missing vrednost
                $_id = 'missing_value_spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'];
                $missing = 1;
            }

            if (isset($srv_data_vrednost[$row1['id']])) {
                $sel = true;
            } else {
                $sel = false;
            }

            # če nimamo missingov in je trenutni enak izbranemu, ali če imamo misinge inje trenutni enak izbranemu misingu (pri radio buttonih ne rabimo disablat polj)
            $_checked = (($sel && !$is_missing) || ($sel && ($row1['other'] !== 0 && $row1['other'] != 1)) ? ' checked' : '');
            $_disabled = ($is_missing && ($row1['other'] == 0 || $row1['other'] == 1) && $row['tip'] != 1 ? true : false);

            // posebej za radio button opcijo da ne prikaže vprašanja in izpolni prvi odgovor
            if ($row['tip'] == 1 && $row['hidden_default'] == 1 && $i == 0)
                $_checked = ' checked';

            // Ali skrivamo missing ne vem in ga prikazemo sele ob opozorilu
            $hide_missing = false;
            if ((($row['alert_show_99'] > 0 && $row1['variable'] == '-99')
                    || ($row['alert_show_98'] > 0 && $row1['variable'] == '-98')
                    || ($row['alert_show_97'] > 0 && $row1['variable'] == '-97'))
                && $missing == 1 && $_checked == ''
            )
                $hide_missing = true;

            //v kolikor je bil odgovor skrit, ga uporabniku ne prikažemo
            if ($row1['hidden'] == 1)
                $hide_missing = true;

            // Ce imamo slucajno prednastavljeno vrednost
            if ($presetValue > 0 && $presetValue == $row1['id'])
                $_checked = ' checked';

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            // RADIO
            if ($row['tip'] == 1) {

                // Radio - izberite s seznama
                if ($row['orientation'] == 6) {
                    echo '<option value="' . $row1['id'] . '"' . ($sel ? ' selected' : '') . ' id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" ' . (($row1['hidden'] == 1) ? ' style="display:none"' : '') . (($row1['hidden'] == 2) ? ' disabled' : '') . '>' . $row1['naslov'] . '</option>' . "\n";
                
                } 
                // Radio - navpicno - text levo
                elseif ($row['orientation'] == 7) {
                    
                    echo '<div class="variabla' . $oblika['cssFloat'] . ' ' . ($missing == 1 ? 'missing' : '') . ' ' . $_checked . ' ' . $hideRadio . '" id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ($hide_missing ? ' style="display:none"' : '') . '>';
                    echo '<table class="width_30">';
                   
                    echo '<tr>';
                    echo '<td><label for="' . $_id . '">' . $row1['naslov'] . ' </label>';

                    if ($row1['other'] == 1) {
                        $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                        $row3 = mysqli_fetch_array($sql3);

                        $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                        $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                        if ($otherHeight > 1)
                            echo ' <textarea name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="$(\'#spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '\').attr(\'checked\',true); checkBranching();">' . $row3['text'] . '</textarea>';
                        else
                            echo ' <input type="text" name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" value="' . $row3['text'] . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="$(\'#spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '\').attr(\'checked\',true); checkBranching();" />';

                        //echo '   <input type="text" name="textfield_'.$row1['id'].'" id="spremenljivka_'.$spremenljivka.'_textfield_'.$row1['id'].'" value="'.$row3['text'].'" '.($_disabled ? ' disabled' : '').' onclick="$(\'#spremenljivka_'.$spremenljivka.'_vrednost_'.$row1['id'].'\').attr(\'checked\',true); checkBranching();">';
                    }
                    echo '</td>';

                    echo '<td align="right">';
                    echo '<label>';
                    echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $spremenljivka . '"' : '') . ' id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" onclick="checkChecked(this); checkBranching(); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . ' setCheckedClass(this, \'1\', \'' . $_id . '\');">';

                    // Font awesome checkbox
                    echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                        ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '') .
                        '></span>';

                    echo '</label></td>';
                    echo '</tr>';
                    echo '</table>';
                } 
                // Radio - custom checkobox??
                elseif ($row['orientation'] == 9) {
                    echo '<div class="variabla custom_radio_picture ' . (($row1['hidden'] == 2) ? ' answer-disabled ' : '') . $oblika['cssFloat'] . ' ' . ($missing == 1 ? 'missing' : '') . ' ' . $_checked . ' ' . $hideRadio . '" id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ($hide_missing ? ' style="display:none"' : '') . '>';
                    echo '<label for="' . $_id . '"><input type="radio" ' . (!$hideName ? ' name="vrednost_' . $spremenljivka . '"' : '') . ' id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" onclick="checkChecked(this); checkBranching(); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . ' setCheckedClass(this, \'1\'); customRadioSelect(' . $row1['id'] . ', ' . $row1['variable'] . ');"> ';

                    echo '<span class="enka-custom-radio ' .
                            ($spremenljivkaParams->get('customRadio') ?  $spremenljivkaParams->get('customRadio') : '') . '"' .
                             ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() .'px;"': '').
                            '></span>';

                    echo '<div class="custom_radio_answer">(' . $row1['naslov'] . ')</div>';
                    echo '</label>';

                    //Pri smeških moramo pognati JS, da doda ustrezen razred 'obarvan'
                    if($_checked == ' checked'){
                        echo '<script>
                                         $(document).ready( function(){ customRadioSelect(\'' . $row1['id'] . '\', \'' . $row1['variable'] . '\'); } );
                                 </script>';
                    }
                } 
                // Vizualna analogna skala
                elseif ($row['orientation'] == 11) {
                    $stOdgovorov =  mysqli_num_rows($sql1);

                    echo '<div class="variabla custom_radio visual-radio-scale ' . (($row1['hidden'] == 2) ? ' answer-disabled ' : '') . $oblika['cssFloat'] . ' ' . ($missing == 1 ? 'missing' : '') . ' ' . $_checked . ' ' . $hideRadio . '" id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ($hide_missing ? ' style="display:none"' : '') . '>';
                    echo '<label for="' . $_id . '"><input type="radio" ' . (!$hideName ? ' name="vrednost_' . $spremenljivka . '"' : '') . ' id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" onclick="checkChecked(this); checkBranching(); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . ' setCheckedClass(this, \'1\');"> ';
                    echo '<span class="enka-vizualna-skala siv-'.$stOdgovorov.$row1['naslov'].' '.((Helper::getCustomCheckbox() != 0) ? 'size-' . Helper::getCustomCheckbox(): '').'"></span>';
                    echo '<div class="custom_radio_answer '.((Helper::getCustomCheckbox() != 0) ? 'size-' . Helper::getCustomCheckbox(): '').'">(' . $row1['naslov'] . ')</div>';
                    echo '</label>';
                } 
                // Radio - standard
                else {
                    echo '<div class="variabla' . (($row1['hidden'] == 2) ? ' answer-disabled ' : '') . $oblika['cssFloat'] . ' ' . ($missing == 1 ? 'missing' : '') . ' ' . $_checked . ' ' . $hideRadio . '" id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ($hide_missing ? ' style="display:none"' : '') . '>';
                    echo '<label for="' . $_id . '"><input type="radio" ' . (!$hideName ? ' name="vrednost_' . $spremenljivka . '"' : '') . ' id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled || $disabled_vprasanje ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" onclick="checkChecked(this); checkBranching(); ' . ($row['onchange_submit'] == 1 ? ' submitForm();' : '') . ' setCheckedClass(this, \'1\');"> ';

                    // Font awesome checkbox
                    echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                        ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '') .
                        '></span>';
                    echo '' . $row1['naslov'] . '</label>';

                    // Ce je vprasanje disabled moramo vseeno postati vrednost
                    if($disabled_vprasanje){
                        echo '<input type="hidden" name="vrednost_'. $spremenljivka.'" value="'.key($srv_data_vrednost).'">';
                    }
                }
            } 
            // CHECKBOX
            elseif ($row['tip'] == 2 || $row['tip'] == 27) {

                // Checkbox - standard
                if ($row['orientation'] != 6 && $row['orientation'] != 7) {
                    echo '<div class="variabla' . (($row1['hidden'] == 2) ? ' answer-disabled ' : '') . $oblika['cssFloat'] . ' ' . ($missing == 1 ? 'missing' : '') . ' ' . $_checked . ' ' . $hideRadio . '"  id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ($hide_missing ? ' style="display:none"' : '') . '>';
                    
					if($row['tip'] == 2){
 						echo '<label for="' . $_id . '"><input type="checkbox" name="vrednost_' . $spremenljivka . '[]" id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : '1') . '" onclick="checkBranching(); ' . ($checkbox_limit > 0 ? 'checkboxLimit(\'' . $spremenljivka . '\', \'' . $row1['id'] . '\', \'' . $checkbox_limit . '\');' : '') . ' ' . ($missing == 1 ? ' checkMissing(this);' : '') . ' setCheckedClass(this, \'2\');"> ';
					}
					elseif($row['tip'] == 27){	//ce je heatmap, kjer je v uporabi checkbox za belezenje klikov na obmocja, je potrebno spremeniti "name"
						echo '<label for="' . $_id . '"><input type="checkbox" name="vrednostHeatmap_' . $spremenljivka . '[]" id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : '1') . '" onclick="checkBranching(); ' . ($checkbox_limit > 0 ? 'checkboxLimit(\'' . $spremenljivka . '\', \'' . $row1['id'] . '\', \'' . $checkbox_limit . '\');' : '') . ' ' . ($missing == 1 ? ' checkMissing(this);' : '') . ' setCheckedClass(this, \'2\');"> ';	
					}
					

                    // Font awesom checkbox custom
                    echo '<span class="enka-checkbox-radio' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '" ' .
                        ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                        '></span>';

                    echo '' . $row1['naslov'] . '</label>';
                    //v kolikor je odgovor skrit(1) ali disable(2), mu damo vrednost -2
                    if ($row1['hidden'] == 1 || $row1['hidden'] == 2)
                        echo '<input id="branch_' . $_id . '" name="cond_vrednost_' . $spremenljivka . '[]" value="' . $row1['id'] . '" type="hidden">';

                } 
                // Checkbox - izberite s seznama
                elseif ($row['orientation'] == 6) {
                    echo '<option value="' . $row1['id'] . '"' . ($sel ? ' selected' : '') . ' id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" ' . ($hide_missing ? ' style="display:none"' : '') . (($row1['hidden'] == 2) ? ' disabled' : '') . '>' . $row1['naslov'] . '</option>' . "\n";
                } 
                // Checkbox - navpicno - text levo
                elseif ($row['orientation'] == 7) {
                    echo '<div class="variabla' . $oblika['cssFloat'] . ' ' . ($missing == 1 ? 'missing' : '') . ' ' . $_checked . ' ' . $hideRadio . '"  id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ($hide_missing ? ' style="display:none"' : '') . (($row1['hidden'] == 2) ? ' disabled' : '') . '>';
                    echo '<table class="width_30">';
                    //echo '<table style="width:30%">';
                    echo '<tr>';
                    //echo '<td><label for="'.$_id.'">'.$row1['naslov'].'  </label></td>';
                    echo '<td><label for="' . $_id . '">' . $row1['naslov'] . '  </label>';

                    if ($row1['other'] == 1) {
                        $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                        $row3 = mysqli_fetch_array($sql3);

                        $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                        $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                        if ($otherHeight > 1)
                            echo ' <textarea name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="$(\'#spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '\').attr(\'checked\',true); checkBranching();">' . $row3['text'] . '</textarea>';
                        else
                            echo ' <input type="text" name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" value="' . $row3['text'] . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="$(\'#spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '\').attr(\'checked\',true); checkBranching();" />';

                        //echo '   <input type="text" name="textfield_'.$row1['id'].'" id="spremenljivka_'.$spremenljivka.'_textfield_'.$row1['id'].'" value="'.$row3['text'].'" '.($_disabled ? ' disabled' : '').' onclick="$(\'#spremenljivka_'.$spremenljivka.'_vrednost_'.$row1['id'].'\').attr(\'checked\',true); checkBranching();">';
                    }
                    echo '</td>';
                    echo '<td align="right">';
                    echo '<label>';
                    echo '<input type="checkbox" name="vrednost_' . $spremenljivka . '[]" id="' . $_id . '" value="' . $row1['id'] . '"' . $_checked . ($_disabled ? ' disabled' : '') . ' data-calculation="' . ($missing == 1 ? '0' : '1') . '" onclick="checkBranching(); ' . ($checkbox_limit > 0 ? 'checkboxLimit(\'' . $spremenljivka . '\', \'' . $row1['id'] . '\', \'' . $checkbox_limit . '\');' : '') . ' ' . ($missing == 1 ? ' checkMissing(this);' : '') . ' setCheckedClass(this, \'2\',' . $row1['id'] . ');">';

                    // Font awesome
                    echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                        ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                        '></span>';

                    echo '</label>';
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';
                }

            } 
            // DROPDOWN
            elseif ($row['tip'] == 3) {
                # imamo dropdown
                echo '<option value="' . $row1['id'] . '"' . ($sel ? ' selected' : '') . ' id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . ' data-calculation="' . ($missing == 1 ? '0' : $row1['variable']) . '" ' . ($hide_missing ? ' style="display:none"' : '') . (($row1['hidden'] == 2) ? ' disabled' : '') . '>' . $row1['naslov'] . '</option>' . "\n";
            }


            if ($row['tip'] != 3 && $row['orientation'] != 6) {//ce ni vprasanje tipa 3 (roleta) in ni orientacija 6 (select box)
                //if ($row['tip'] != 3 && $row['orientation']!=6) {
                if ($row['orientation'] != 7) { //ce ni orientacija 7 (postavitev: navpicno - tekst levo)
                    if ($row1['other'] == 1) {
                        $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                        $row3 = mysqli_fetch_array($sql3);

                        $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                        $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                        if ($otherHeight > 1)
                            echo ' <textarea name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="$(\'#spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '\').attr(\'checked\',true); checkBranching(); '.($checkbox_limit > 0 ? 'checkboxLimitTextbox(\'' . $spremenljivka . '\', \'' . $row1['id'] . '\', \'' . $checkbox_limit . '\');' : '').'" >' . $row3['text'] . '</textarea>';
                        else
                            echo ' <input type="text" name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" value="' . $row3['text'] . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="$(\'#spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '\').attr(\'checked\',true); checkBranching(); '.($checkbox_limit > 0 ? 'checkboxLimitTextbox(\'' . $spremenljivka . '\', \'' . $row1['id'] . '\', \'' . $checkbox_limit . '\');' : '').'" />';
                    }
                }
                echo '</div>' . "\n";
            }

            $i++;
            if ($stolpci > 1 && $row['orientation'] == 1 && get('mobile') != 1) {
                if ($i >= $v_stolpcu) {
                    echo '</div><div class="floatLeft width_' . round(100 / $stolpci, 0) . '">';
                    $i = 0;
                }
            }
        }

        if ($stolpci > 1 && $row['orientation'] == 1 && get('mobile') != 1)
            echo '</div>';

        // koncamo select
        if ($row['tip'] == 3 || $row['orientation'] == 6) {
            echo '      </select>' . "\n";

            if ($row['tip'] == 2 && $row['orientation'] == 6) {
                if (mysqli_num_rows($sql1) > 0) mysqli_data_seek($sql1, 0);
                while ($row1 = mysqli_fetch_assoc($sql1)) {
                    if ($row1['hidden'] == 1 || $row1['hidden'] == 2)
                        echo '<input id="branch_' . $_id . '" name="cond_vrednost_' . $spremenljivka . '[]" value="' . $row1['id'] . '" type="hidden">';
                }
            }
            // Ce je vprasanje disabled moramo vseeno postati vrednost
            elseif($disabled_vprasanje){
                echo '<input type="hidden" name="vrednost_'. $spremenljivka.'" value="'.key($srv_data_vrednost).'">';
            }

            if (mysqli_num_rows($sql1) > 0) mysqli_data_seek($sql1, 0);

            while ($row1 = mysqli_fetch_array($sql1)) {
                if ($row1['other'] == 1) {
                    $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                    $row3 = mysqli_fetch_array($sql3);

                    $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                    $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                    if ($otherHeight > 1)
                        echo '<br /><textarea name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" class="drugo_' . $spremenljivka . '" rows="' . $otherHeight . '" style="display:none; ' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="checkBranching();">' . $row3['text'] . '</textarea>';
                    else
                        echo '<br /><input type="text" name="textfield_' . $row1['id'] . '" id="spremenljivka_' . $spremenljivka . '_textfield_' . $row1['id'] . '" class="drugo_' . $spremenljivka . '" value="' . $row3['text'] . '" style="display:none; ' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" ' . ($_disabled ? ' disabled' : '') . ' onclick="checkBranching();" />';
                }
            }

            ?>
            <script>
                function drugo_<?=$spremenljivka?> () {
                    $('.drugo_<?=$spremenljivka?>').hide();
                    $('#spremenljivka_<?=$spremenljivka?>_textfield_' + $('#vrednost_<?=$spremenljivka?>').val()).show();
                }

            </script><?

            echo '</div>';

            // vedno prikazujemo novo roleto
            // text rolete ni pri multiple selectu in pri mobilni (pri tablici jo pustimo)
            if (
                $row['orientation'] != 6 && get('mobile') != 1 && get('forceShowSpremenljivka') !== true
            ) {
                ?>
                <script>
                    $('select#vrednost_<?=$spremenljivka?>').chosen({search_contains: true});
                </script>
                <?
            }
        }

    }


}